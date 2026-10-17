<?php
/**
 * Technical SEO cleanup (deep audit D6, Oct 2026): URL guards, sitemap and
 * archive indexing, Open Graph type, social images and image alt text.
 *
 * Titles and meta descriptions stay in inc/seo-compat.php, structured data
 * in inc/schema.php and breadcrumbs in inc/breadcrumbs.php; this file holds
 * the rest of the D6 items (2.1-2.5, 2.8, 2.9).
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bump when a D6-style one-time job below needs to run again (it updates
 * image alt text, renames combo titles, clears the sitemap cache and purges
 * the page cache once).
 */
if ( ! defined( 'CS_SEO_REV' ) ) {
	define( 'CS_SEO_REV', '2026-10-17-d6' );
}

/**
 * Answers the current request with the theme's 404 page. Called from the
 * `wp` action, before any template or redirect runs.
 */
function cs_force_404() {
	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();
	// Otherwise core's redirect_canonical() "guesses" a page for the 404
	// and 301s there, which is how /fps-calculator/attachment/1/ ended up
	// on an unrelated guide.
	add_filter( 'redirect_canonical', '__return_false' );
	add_filter( 'do_redirect_guess_404_permalink', '__return_false' );
}

/**
 * Published combo post for a slug, or null.
 */
function cs_combo_post_by_slug( $slug ) {
	$post = get_page_by_path( sanitize_title( $slug ), OBJECT, 'cs_bottleneck' );
	return ( $post && 'publish' === $post->post_status ) ? $post : null;
}

/**
 * URL guards (audit 2.1, 2.2, 2.5). Runs on `wp` at priority 1, before
 * Rank Math's attachment redirect (`wp`, 10) and core's redirect_canonical.
 *
 * - 2.1 Any /bottleneck/x/y/ URL used to return a copy of the homepage with
 *   200. The nested form of a real pair (/bottleneck/{cpu}/{gpu}/) and
 *   anything under a real combo page (/feed/, /2/, ...) now 301 to that
 *   combo page; everything else (/bottleneck/page/2/, made-up pairs) is a
 *   404.
 * - 2.2 Pages never paginate on this site, so /blog/page/999/ (any page
 *   plus /page/N/) is a 404 instead of a 200 copy of the page.
 * - 2.5 /FPS-Calculator/ (a page URL in the wrong case) 301s to the real
 *   lowercase URL. /{page}/attachment/{x}/ only resolves when {page} is that
 *   image's own parent; WordPress matched the attachment by name alone, so
 *   /fps-calculator/attachment/1/ redirected to an unrelated guide.
 */
function cs_url_guards() {
	if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}
	$path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
	if ( ! is_string( $path ) || '' === $path ) {
		return;
	}

	// 2.1 Nested /bottleneck/ paths.
	if ( preg_match( '#^/bottleneck/([^/]+)/(.+?)/?$#i', $path, $m ) ) {
		$rest   = explode( '/', $m[2] );
		$target = cs_combo_post_by_slug( $m[1] );
		if ( ! $target && 1 === count( $rest ) ) {
			$target = cs_combo_post_by_slug( $m[1] . '-' . $rest[0] );
		}
		if ( $target ) {
			wp_safe_redirect( get_permalink( $target ), 301 );
			exit;
		}
		cs_force_404();
		return;
	}

	// 2.5 Attachment URLs under the wrong parent.
	if ( preg_match( '#^(.*)/attachment/[^/]+/?$#i', $path, $m ) && is_attachment() ) {
		$parent = (int) get_post_field( 'post_parent', get_queried_object_id() );
		$expect = $parent ? untrailingslashit( (string) wp_parse_url( get_permalink( $parent ), PHP_URL_PATH ) ) : '';
		if ( '' === $expect || strtolower( untrailingslashit( $m[1] ) ) !== strtolower( $expect ) ) {
			cs_force_404();
			return;
		}
	}

	if ( ! is_singular() ) {
		return;
	}

	// 2.2 /page/N/ on a single page or post.
	if ( (int) get_query_var( 'paged' ) > 1 ) {
		cs_force_404();
		return;
	}

	// 2.5 Wrong-case URL of a real page.
	if ( preg_match( '/[A-Z]/', $path ) ) {
		$canonical = (string) wp_parse_url( get_permalink(), PHP_URL_PATH );
		if ( '' !== $canonical && strtolower( $path ) === strtolower( $canonical ) ) {
			$query = isset( $_SERVER['QUERY_STRING'] ) && '' !== $_SERVER['QUERY_STRING'] ? '?' . wp_unslash( $_SERVER['QUERY_STRING'] ) : '';
			wp_safe_redirect( get_permalink() . $query, 301 );
			exit;
		}
	}
}
add_action( 'wp', 'cs_url_guards', 1 );

/**
 * 2.3 The homepage was listed in both the post and the page sitemap, with
 * different lastmod dates. Rank Math adds it to the post sitemap as a "first
 * link" that the per-entry filter never sees, so it is dropped here, where
 * the post sitemap prints each URL. The page sitemap keeps it.
 */
function cs_post_sitemap_url( $url, $generator ) {
	if ( isset( $url['loc'] ) && untrailingslashit( $url['loc'] ) === untrailingslashit( home_url( '/' ) ) ) {
		return '';
	}
	return $generator->sitemap_url( $url );
}
add_filter( 'rank_math/sitemap/post_sitemap_url', 'cs_post_sitemap_url', 10, 2 );

/**
 * 2.4 Category and tag archives with fewer than this many posts are thin
 * (/category/buying-guides/ had 1 post and 43 words): noindex,follow and
 * out of the sitemap. They index again on their own once they grow.
 */
function cs_thin_term_min_posts() {
	return 3;
}

function cs_is_thin_term( $term ) {
	return $term instanceof WP_Term && in_array( $term->taxonomy, array( 'category', 'post_tag' ), true ) && (int) $term->count < cs_thin_term_min_posts();
}

function cs_thin_archive_robots( $robots ) {
	if ( ( is_category() || is_tag() ) && cs_is_thin_term( get_queried_object() ) ) {
		$robots['index']  = 'noindex';
		$robots['follow'] = 'follow';
	}
	return $robots;
}
add_filter( 'rank_math/frontend/robots', 'cs_thin_archive_robots' );

function cs_sitemap_skip_thin_terms( $url, $type, $object ) {
	if ( 'term' === $type && cs_is_thin_term( $object ) ) {
		return false;
	}
	return $url;
}
add_filter( 'rank_math/sitemap/entry', 'cs_sitemap_skip_thin_terms', 10, 3 );

/**
 * With every term of a taxonomy thin, its sitemap would be listed in the
 * index but empty, so the whole taxonomy sitemap is left out.
 */
function cs_sitemap_skip_thin_taxonomy( $exclude, $taxonomy ) {
	if ( $exclude || ! in_array( $taxonomy, array( 'category', 'post_tag' ), true ) ) {
		return $exclude;
	}
	$big = get_terms( array(
		'taxonomy'   => $taxonomy,
		'hide_empty' => true,
		'fields'     => 'ids',
		'number'     => 1,
		'orderby'    => 'count',
		'order'      => 'DESC',
	) );
	if ( empty( $big ) || is_wp_error( $big ) ) {
		return true;
	}
	return cs_is_thin_term( get_term( $big[0], $taxonomy ) );
}
add_filter( 'rank_math/sitemap/exclude_taxonomy', 'cs_sitemap_skip_thin_taxonomy', 10, 2 );

/**
 * A term archive with no description of its own gets a plain one built from
 * its name and post count (the Buying Guides archive had none).
 */
function cs_term_archive_description( $description ) {
	if ( ( is_category() || is_tag() ) && '' === trim( wp_strip_all_tags( (string) $description ) ) ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term ) {
			/* translators: 1: number of articles, 2: category name */
			return sprintf( _n( '%1$d Computing Skill article filed under %2$s, with links to the calculators it uses.', '%1$d Computing Skill articles filed under %2$s, with links to the calculators they use.', (int) $term->count, 'computingskill' ), (int) $term->count, $term->name );
		}
	}
	return $description;
}
add_filter( 'rank_math/frontend/description', 'cs_term_archive_description', 30 );

/**
 * 2.9 og:type was "article" on every page. Calculators, hubs, About,
 * Contact, legal pages and archives are "website"; guides, posts, combo and
 * game pages stay "article". Uses the same page kinds as the schema fixes.
 */
function cs_og_type( $type ) {
	if ( is_front_page() ) {
		return 'website';
	}
	if ( ! is_singular() ) {
		return 'website';
	}
	if ( function_exists( 'cs_schema_page_kind' ) && in_array( cs_schema_page_kind(), array( 'app', 'hub', 'about', 'contact', 'legal' ), true ) ) {
		return 'website';
	}
	return $type;
}
add_filter( 'rank_math/opengraph/type', 'cs_og_type' );

/**
 * 2.8 Real alt text for the guide charts, which used their file names
 * ("vram-needed-by-resolution-chart"). Each line describes what the image
 * actually shows. Keyed by the file-name alt they carried; the one-time job
 * below writes these to the media library (which Rank Math uses for
 * og:image:alt), and the_content filter fixes the alt written into each
 * page's HTML.
 */
function cs_image_alt_text() {
	return array(
		'1080p-1440p-4k-resolution-comparison-chart' => __( 'Chart comparing 1080p, 1440p and 4K: about 2.1, 3.7 and 8.3 million pixels, with 1440p at roughly 1.8x and 4K at roughly 4x the GPU load of 1080p', 'computingskill' ),
		'cpu-bottleneck-flow-diagram'                => __( 'Diagram of a CPU bottleneck: the CPU prepares each frame, the GPU finishes fast and waits with usage below 99%, so FPS is lower than the GPU alone could deliver', 'computingskill' ),
		'ddr4-vs-ddr5-speed-bandwidth-chart'         => __( 'Bar chart of typical RAM speeds: DDR4 at 3200 and 3600 MT/s, DDR5 at 5600 and 6400+ MT/s', 'computingskill' ),
		'gpu-bottleneck-flow-diagram'                => __( 'Diagram of a GPU bottleneck: the CPU prepares frames quickly, the GPU runs near 99-100% usage, and FPS follows the GPU, which is the normal pattern for gaming PCs', 'computingskill' ),
		'gpu-buying-checklist-diagram'               => __( 'GPU buying checklist: match the card to your monitor resolution and refresh rate, enough VRAM, PSU wattage and connectors, and case length and slot clearance', 'computingskill' ),
		'ram-capacity-by-use-case-chart'             => __( 'Bar chart of RAM by use: 8GB for browsing and office, 16GB for gaming, 32GB for gaming and streaming, 64GB for heavy content creation and 3D', 'computingskill' ),
		'vram-needed-by-resolution-chart'            => __( 'Bar chart of VRAM for modern games at high settings: 6-8GB at 1080p, 8-12GB at 1440p and 12-16GB or more at 4K', 'computingskill' ),
		'psu-wattage-headroom-diagram'               => __( 'PSU headroom example: a system that peaks around 420W on a roughly 650W power supply, leaving 130W of safety headroom and 100W for later upgrades', 'computingskill' ),
		'fps-improvement-impact-chart'               => __( 'Illustrative ranking of FPS fixes: closing background apps has a small effect, lowering shadows and effects medium, lowering render resolution large, and upgrading the GPU the largest', 'computingskill' ),
		'pc-upgrade-priority-order-diagram'          => __( 'PC upgrade priority order: GPU first, then CPU if it holds back a strong GPU, then RAM, then storage for load times', 'computingskill' ),
		'nvme-vs-sata-speed-comparison-chart'        => __( 'Bar chart of SSD sequential read speeds: SATA about 550 MB/s, NVMe Gen3 about 3,500, Gen4 about 7,000 and Gen5 12,000+ MB/s', 'computingskill' ),
		'ssd-vs-hdd-comparison-chart'                => __( 'SSD vs HDD comparison: HDDs run at about 80-160 MB/s with moving parts and the lowest cost per GB; SSDs run at 550 to 12,000+ MB/s, silent and more shock-resistant', 'computingskill' ),
	);
}

function cs_fix_image_alts_in_content( $content ) {
	if ( false === strpos( $content, '<img' ) ) {
		return $content;
	}
	$map = cs_image_alt_text();
	return preg_replace_callback(
		'/\balt=(["\'])([a-z0-9-]+)\1/i',
		function ( $m ) use ( $map ) {
			$key = strtolower( $m[2] );
			return isset( $map[ $key ] ) ? 'alt="' . esc_attr( $map[ $key ] ) . '"' : $m[0];
		},
		$content
	);
}
add_filter( 'the_content', 'cs_fix_image_alts_in_content', 25 );

/**
 * One-time D6 job: image alt text in the media library, combo titles
 * renamed from "... Bottleneck Calculator" (2.10; they have no calculator on
 * them), Rank Math sitemap cache and the page cache cleared so the new
 * titles, robots and sitemaps go out at once.
 */
function cs_seo_rev_run() {
	if ( wp_installing() || get_option( 'cs_seo_rev' ) === CS_SEO_REV ) {
		return;
	}
	if ( ! add_option( 'cs_seo_rev_lock', time(), '', 'no' ) ) {
		if ( time() - (int) get_option( 'cs_seo_rev_lock' ) > 300 ) {
			delete_option( 'cs_seo_rev_lock' );
		}
		return;
	}

	global $wpdb;

	// Media library alt text: only where the alt is still the file name.
	foreach ( cs_image_alt_text() as $old => $alt ) {
		$ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attachment_image_alt' AND meta_value = %s",
			$old
		) );
		foreach ( $ids as $id ) {
			update_post_meta( (int) $id, '_wp_attachment_image_alt', $alt );
		}
	}

	// Combo titles. Direct update (no revisions, no save hooks); a title an
	// editor changed by hand no longer ends in " Bottleneck Calculator" and
	// is left alone.
	$rows = $wpdb->get_results( "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = 'cs_bottleneck' AND post_title LIKE '% Bottleneck Calculator'" );
	foreach ( $rows as $row ) {
		$wpdb->update(
			$wpdb->posts,
			array( 'post_title' => preg_replace( '/ Bottleneck Calculator$/', ' Bottleneck', $row->post_title ) ),
			array( 'ID' => (int) $row->ID ),
			array( '%s' ),
			array( '%d' )
		);
		clean_post_cache( (int) $row->ID );
	}

	if ( class_exists( '\RankMath\Sitemap\Cache' ) && method_exists( '\RankMath\Sitemap\Cache', 'invalidate_storage' ) ) {
		\RankMath\Sitemap\Cache::invalidate_storage();
	}
	do_action( 'litespeed_purge_all' );

	update_option( 'cs_seo_rev', CS_SEO_REV, false );
	delete_option( 'cs_seo_rev_lock' );
}
add_action( 'init', 'cs_seo_rev_run', 32 );

/* -------------------------------------------------------------------------
 * 2.9 Social images: 146 of 160 pages shared the one default image. Pages
 * without an image of their own now get a 1200x630 card with the page
 * title (and, on combo pages, the result at each resolution), drawn once
 * with GD and stored in uploads/cs-og/. Pages with a featured or content
 * image keep it. Without GD/FreeType the default image is used as before.
 * ---------------------------------------------------------------------- */

/**
 * Card text for the current page: title, kicker line, optional rows.
 * Empty array when the page should keep Rank Math's choice.
 */
function cs_og_card_spec() {
	if ( is_front_page() ) {
		return array();
	}
	if ( is_post_type_archive( 'cs_game' ) ) {
		return array(
			'key'    => 'archive-games',
			'kicker' => __( 'Game requirements', 'computingskill' ),
			'title'  => __( 'Can your PC run it? System requirements for popular games', 'computingskill' ),
			'rows'   => array(),
		);
	}
	if ( ! is_singular() ) {
		return array();
	}

	$post  = get_queried_object();
	$title = wp_strip_all_tags( html_entity_decode( get_the_title( $post ), ENT_QUOTES, 'UTF-8' ) );
	$spec  = array(
		'key'    => 'post-' . $post->ID,
		'kicker' => '',
		'title'  => $title,
		'rows'   => array(),
	);

	if ( 'cs_bottleneck' === $post->post_type ) {
		$cpus = cs_get_cpu_data();
		$gpus = cs_get_gpu_data();
		$c    = get_post_meta( $post->ID, 'cs_cpu', true );
		$g    = get_post_meta( $post->ID, 'cs_gpu', true );
		if ( isset( $cpus[ $c ], $gpus[ $g ] ) ) {
			$spec['kicker'] = __( 'CPU + GPU bottleneck', 'computingskill' );
			$spec['title']  = cs_bn_short_name( $cpus[ $c ]['name'] ) . ' + ' . cs_bn_short_name( $gpus[ $g ]['name'] );
			$labels         = array( '1080p' => '1080p', '1440p' => '1440p', '4k' => '4K' );
			foreach ( cs_calculate_bottleneck( $cpus[ $c ], $gpus[ $g ] ) as $res => $r ) {
				$spec['rows'][] = array( $labels[ $res ], cs_bn_cell( $r ) );
			}
		}
		return $spec;
	}
	if ( 'cs_game' === $post->post_type ) {
		$spec['kicker'] = __( 'PC system requirements', 'computingskill' );
		$games          = cs_get_games_data();
		if ( isset( $games[ $post->post_name ] ) ) {
			$gm             = $games[ $post->post_name ];
			$spec['title']  = $gm['name'];
			$spec['rows'][] = array( __( 'Minimum', 'computingskill' ), sprintf( '%dGB RAM', (int) $gm['min']['ram'] ) );
			$spec['rows'][] = array( __( 'Recommended', 'computingskill' ), sprintf( '%dGB RAM', (int) $gm['rec']['ram'] ) );
			$spec['rows'][] = array( __( 'Storage', 'computingskill' ), sprintf( '%dGB', (int) $gm['rec']['storage_gb'] ) );
		}
		return $spec;
	}

	$kinds          = array(
		'app'     => __( 'Free calculator', 'computingskill' ),
		'hub'     => __( 'Tools and guides', 'computingskill' ),
		'about'   => __( 'About', 'computingskill' ),
		'contact' => __( 'Contact', 'computingskill' ),
		'legal'   => __( 'Site policy', 'computingskill' ),
		'article' => 'post' === $post->post_type ? __( 'Buying guide', 'computingskill' ) : __( 'Guide', 'computingskill' ),
	);
	$kind           = function_exists( 'cs_schema_page_kind' ) ? cs_schema_page_kind() : 'article';
	$spec['kicker'] = isset( $kinds[ $kind ] ) ? $kinds[ $kind ] : '';
	// The suffix belongs in the browser tab, not on the card.
	$spec['title'] = preg_replace( '/\s+[-–|]\s+Computing Skill$/u', '', $spec['title'] );
	return $spec;
}

/**
 * URL of the card for the current page (drawn on first use), or ''.
 */
function cs_og_card_url() {
	static $url = null;
	if ( null !== $url ) {
		return $url;
	}
	$url  = '';
	$spec = cs_og_card_spec();
	if ( empty( $spec ) || ! function_exists( 'imagettftext' ) || ! function_exists( 'imagepng' ) ) {
		return $url;
	}
	$uploads = wp_upload_dir( null, false );
	if ( ! empty( $uploads['error'] ) ) {
		return $url;
	}
	$hash = substr( md5( wp_json_encode( $spec ) . '|v1' ), 0, 10 );
	$name = sanitize_file_name( $spec['key'] . '-' . $hash . '.png' );
	$dir  = trailingslashit( $uploads['basedir'] ) . 'cs-og';
	$file = $dir . '/' . $name;
	if ( ! file_exists( $file ) ) {
		if ( ! wp_mkdir_p( $dir ) || ! cs_og_card_draw( $spec, $file ) ) {
			return $url;
		}
		// Older cards for the same page are no longer referenced.
		foreach ( (array) glob( $dir . '/' . $spec['key'] . '-*.png' ) as $old ) {
			if ( $old && $old !== $file ) {
				@unlink( $old ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
			}
		}
	}
	$url = trailingslashit( $uploads['baseurl'] ) . 'cs-og/' . $name;
	return $url;
}

/**
 * Wraps text to lines that fit $max_w pixels at the given size.
 */
function cs_og_wrap( $text, $font, $size, $max_w ) {
	$lines = array();
	$line  = '';
	foreach ( preg_split( '/\s+/', trim( $text ) ) as $word ) {
		$try = '' === $line ? $word : $line . ' ' . $word;
		$box = imagettfbbox( $size, 0, $font, $try );
		if ( '' !== $line && ( $box[2] - $box[0] ) > $max_w ) {
			$lines[] = $line;
			$line    = $word;
		} else {
			$line = $try;
		}
	}
	if ( '' !== $line ) {
		$lines[] = $line;
	}
	return $lines;
}

/**
 * Draws the card. Colours are the dark-theme design tokens from style.css
 * (--cs-bg, --cs-surface, --cs-border, --cs-text, --cs-text-muted,
 * --cs-primary, --cs-gpu, --cs-accent); GD cannot read CSS variables.
 */
function cs_og_card_draw( $spec, $file ) {
	$head = CS_THEME_DIR . '/assets/fonts/og/chakra-petch-700.ttf';
	$mono = CS_THEME_DIR . '/assets/fonts/og/jetbrains-mono-500.ttf';
	if ( ! is_readable( $head ) || ! is_readable( $mono ) ) {
		return false;
	}
	$w  = 1200;
	$h  = 630;
	$im = imagecreatetruecolor( $w, $h );
	if ( ! $im ) {
		return false;
	}
	$rgb = function ( $hex ) use ( $im ) {
		$hex = ltrim( $hex, '#' );
		return imagecolorallocate( $im, hexdec( substr( $hex, 0, 2 ) ), hexdec( substr( $hex, 2, 2 ) ), hexdec( substr( $hex, 4, 2 ) ) );
	};
	$bg      = $rgb( '#0b0f14' );
	$surface = $rgb( '#121821' );
	$border  = $rgb( '#263345' );
	$text    = $rgb( '#e6edf5' );
	$muted   = $rgb( '#9aa8bb' );
	$primary = $rgb( '#4fb4ff' );
	$gpu     = $rgb( '#46d6e8' );
	$accent  = $rgb( '#5ee08f' );

	imagefilledrectangle( $im, 0, 0, $w, $h, $bg );
	// Accent bar on the left, faint grid on the right (the site's telemetry look).
	imagefilledrectangle( $im, 0, 0, 11, $h, $primary );
	for ( $x = 760; $x < $w; $x += 40 ) {
		imageline( $im, $x, 0, $x, $h, $surface );
	}
	for ( $y = 0; $y < $h; $y += 40 ) {
		imageline( $im, 760, $y, $w, $y, $surface );
	}

	$x = 72;

	// Kicker.
	if ( '' !== $spec['kicker'] ) {
		imagettftext( $im, 22, 0, $x, 110, $gpu, $mono, strtoupper( $spec['kicker'] ) );
	}

	// Title: largest size that fits in 3 lines.
	$max_w = 1050;
	foreach ( array( 64, 56, 48, 42, 36 ) as $size ) {
		$lines = cs_og_wrap( $spec['title'], $head, $size, $max_w );
		if ( count( $lines ) <= 3 ) {
			break;
		}
	}
	$lines = array_slice( $lines, 0, 3 );
	$y     = 190;
	foreach ( $lines as $line ) {
		imagettftext( $im, $size, 0, $x, $y, $text, $head, $line );
		$y += (int) round( $size * 1.25 );
	}

	// Result rows as tiles.
	if ( ! empty( $spec['rows'] ) ) {
		$tile_w = 330;
		$tile_h = 112;
		$gap    = 24;
		$ty     = max( $y + 10, 360 );
		foreach ( array_slice( $spec['rows'], 0, 3 ) as $i => $row ) {
			$tx = $x + $i * ( $tile_w + $gap );
			imagefilledrectangle( $im, $tx, $ty, $tx + $tile_w, $ty + $tile_h, $surface );
			imagerectangle( $im, $tx, $ty, $tx + $tile_w, $ty + $tile_h, $border );
			imagefilledrectangle( $im, $tx, $ty, $tx + 5, $ty + $tile_h, $accent );
			imagettftext( $im, 20, 0, $tx + 26, $ty + 42, $muted, $mono, $row[0] );
			imagettftext( $im, 28, 0, $tx + 26, $ty + 88, $text, $head, $row[1] );
		}
	}

	// Wordmark.
	imagettftext( $im, 30, 0, $x, 574, $text, $head, 'Computing' );
	$box = imagettfbbox( 30, 0, $head, 'Computing ' );
	imagettftext( $im, 30, 0, $x + ( $box[2] - $box[0] ), 574, $primary, $head, 'Skill' );
	$host = wp_parse_url( home_url(), PHP_URL_HOST );
	$hb   = imagettfbbox( 20, 0, $mono, $host );
	imagettftext( $im, 20, 0, $w - 72 - ( $hb[2] - $hb[0] ), 572, $muted, $mono, $host );

	$ok = imagepng( $im, $file, 6 );
	imagedestroy( $im );
	return $ok;
}

/**
 * Hands the card to Rank Math for og:image and twitter:image when the page
 * has no image of its own (featured or content image). Runs after Rank
 * Math's own image search and before its default image.
 */
function cs_og_add_card( $image ) {
	if ( ! is_object( $image ) || ! method_exists( $image, 'has_images' ) || $image->has_images() ) {
		return;
	}
	$url = cs_og_card_url();
	if ( '' === $url ) {
		return;
	}
	$spec = cs_og_card_spec();
	$image->add_image( array(
		'url'    => $url,
		'width'  => 1200,
		'height' => 630,
		'type'   => 'image/png',
		'alt'    => $spec['title'],
	) );
}
add_action( 'rank_math/opengraph/facebook/add_additional_images', 'cs_og_add_card' );
add_action( 'rank_math/opengraph/twitter/add_additional_images', 'cs_og_add_card' );
