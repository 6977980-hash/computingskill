<?php
/**
 * Speed / Core Web Vitals fixes (deep audit D9, items 4.1-4.5).
 *
 *  - 4.1 The main image of 13 guides was lazy-loaded by LiteSpeed behind a
 *    placeholder GIF while WordPress marked it fetchpriority="high", which
 *    delays the largest paint. Any image WordPress picks as the LCP
 *    candidate now carries data-no-lazy="1" (LiteSpeed skips those) and
 *    never loading="lazy" (cs_perf_lcp_image()).
 *  - 4.2 Calculator fields are built by calculators.js, so the page jumped
 *    once they appeared. The empty field and result areas now reserve
 *    their height in fixes.css; share links mark the calculator with
 *    data-shared so the result area is reserved too (inc/shortcodes.php).
 *  - 4.3 Images were PNG only. A WebP copy ("file.png.webp") is written
 *    next to every size of every uploaded PNG/JPEG when it is at least 10%
 *    smaller, and page images use it (cs_perf_webp_*). A small src that
 *    WordPress left on a wide image (the RAM gaming + streaming guide's
 *    300px files) is swapped for the right size.
 *  - 4.4 Pages without block markup no longer get the block library,
 *    classic-theme and global-styles CSS, and the emoji script/CSS is gone
 *    site-wide. The per-tool hardware/games data is in inc/setup.php.
 *  - 4.5 Front-end HTML for visitors gets a short browser cache header.
 *
 * Social cards (og:image) keep their PNG files on purpose.
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'CS_PERF_REV' ) ) {
	define( 'CS_PERF_REV', '2026-10-18-d9' );
}

/* ------------------------------------------------------------------ */
/* 4.1 LCP image: never lazy                                           */
/* ------------------------------------------------------------------ */

function cs_perf_lcp_tag( $tag ) {
	if ( false === strpos( $tag, 'fetchpriority="high"' ) ) {
		return $tag;
	}
	$tag = preg_replace( '/\sloading=("|\')lazy\1/i', '', $tag );
	if ( false === strpos( $tag, 'data-no-lazy' ) ) {
		$tag = preg_replace( '/^<img\b/i', '<img data-no-lazy="1"', $tag );
	}
	return $tag;
}

function cs_perf_lcp_image( $content ) {
	if ( false === strpos( $content, 'fetchpriority="high"' ) ) {
		return $content;
	}
	return preg_replace_callback( '/<img\b[^>]*>/i', function ( $m ) {
		return cs_perf_lcp_tag( $m[0] );
	}, $content );
}
add_filter( 'the_content', 'cs_perf_lcp_image', 99 );

function cs_perf_lcp_attachment_attrs( $attr ) {
	if ( isset( $attr['fetchpriority'] ) && 'high' === $attr['fetchpriority'] ) {
		$attr['data-no-lazy'] = '1';
		unset( $attr['loading'] );
	}
	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'cs_perf_lcp_attachment_attrs', 20 );

/* ------------------------------------------------------------------ */
/* 4.2 Reserved calculator height                                      */
/* ------------------------------------------------------------------ */

/**
 * Height of each tool's field area once calculators.js has built it, at
 * 390px (one column) and 1280px (two columns, 640px and up), plus the
 * result height for the tools that share links calculate on load.
 * Measured with the default inputs in Chromium, Oct 2026. Only used while
 * the area is still empty, so a tool that gains a field is never cut off;
 * re-measure when fields change a lot.
 *
 * @return array type => array( fields mobile, fields wide, result|null ).
 */
function cs_perf_calc_heights() {
	return array(
		'aspect-ratio'       => array( 140, 60, null ),
		'bandwidth'          => array( 300, 140, null ),
		'bottleneck'         => array( 510, 310, array( 1620, 850 ) ),
		'build-cost'         => array( 980, 500, null ),
		'compatibility'      => array( 1780, 920, null ),
		'cooling'            => array( 480, 270, null ),
		'cpu-performance'    => array( 60, 60, null ),
		'cpu-power'          => array( 140, 60, null ),
		'cpu-vs-gpu'         => array( 190, 80, null ),
		'data-usage'         => array( 380, 220, null ),
		'download-time'      => array( 300, 140, null ),
		'dpi'                => array( 140, 60, null ),
		'file-transfer-time' => array( 490, 220, null ),
		'fps'                => array( 910, 460, array( 790, 500 ) ), // D10: + upscaling and frame generation.
		'canirun'            => array( 480, 275, null ),
		'gpu-performance'    => array( 60, 60, null ),
		'gpu-power'          => array( 140, 60, null ),
		'mbps-to-mbs'        => array( 60, 60, null ),
		'mbs-to-mbps'        => array( 60, 60, null ),
		'monitor-size'       => array( 140, 60, null ),
		'power-consumption'  => array( 880, 520, null ),
		'ppi'                => array( 220, 140, null ),
		'psu'                => array( 660, 350, array( 1370, 710 ) ),
		'ram'                => array( 190, 80, null ),
		'refresh-rate'       => array( 180, 80, null ),
		'resolution'         => array( 140, 60, null ),
		'ssd-lifespan'       => array( 180, 80, null ),
		'ssd-tbw'            => array( 420, 240, null ),
		'storage'            => array( 300, 140, null ),
		'storage-converter'  => array( 300, 140, null ),
		'upgrade'            => array( 430, 250, null ),
		'upload-time'        => array( 300, 140, null ),
		'vram'               => array( 240, 120, null ),
	);
}

/**
 * style="" for a calculator's empty field / result area (custom
 * properties read by fixes.css), or ''.
 */
function cs_perf_calc_reserve_style( $type, $part ) {
	$h = cs_perf_calc_heights();
	if ( empty( $h[ $type ] ) ) {
		return '';
	}
	if ( 'fields' === $part ) {
		return sprintf( ' style="--cs-reserve:%dpx;--cs-reserve-wide:%dpx"', $h[ $type ][0], $h[ $type ][1] );
	}
	if ( 'result' === $part && ! empty( $h[ $type ][2] ) ) {
		return sprintf( ' style="--cs-reserve:%dpx;--cs-reserve-wide:%dpx"', $h[ $type ][2][0], $h[ $type ][2][1] );
	}
	return '';
}

/* ------------------------------------------------------------------ */
/* 4.3 Right-size src + WebP                                           */
/* ------------------------------------------------------------------ */

/**
 * When an <img> is shown wider than its src file (width="641" with a
 * 300px src) and its srcset has a larger file, use the smallest srcset
 * file that is at least as wide. Browsers with srcset support pick from
 * srcset anyway; this fixes the fallback and what crawlers index.
 */
function cs_perf_fix_small_src( $tag ) {
	if ( ! preg_match( '/\swidth="(\d+)"/', $tag, $w ) || ! preg_match( '/\s(?:data-)?srcset="([^"]+)"/', $tag, $ss ) ) {
		return $tag;
	}
	if ( ! preg_match( '/\s(src|data-src)="([^"]+-(\d+)x\d+\.(?:png|jpe?g|webp))"/i', $tag, $src ) || (int) $src[3] >= (int) $w[1] ) {
		return $tag;
	}
	$best = null;
	foreach ( explode( ',', $ss[1] ) as $cand ) {
		$parts = preg_split( '/\s+/', trim( $cand ) );
		if ( count( $parts ) < 2 || ! preg_match( '/^(\d+)w$/', $parts[1], $cw ) ) {
			continue;
		}
		$cw = (int) $cw[1];
		if ( $cw >= (int) $w[1] && ( null === $best || $cw < $best[1] ) ) {
			$best = array( $parts[0], $cw );
		}
	}
	if ( ! $best ) {
		return $tag;
	}
	return str_replace( ' ' . $src[1] . '="' . $src[2] . '"', ' ' . $src[1] . '="' . esc_url( $best[0] ) . '"', $tag );
}

/**
 * WebP URL for an uploads image URL, or the same URL when no WebP copy
 * exists. Only files under the uploads folder are considered.
 */
function cs_perf_webp_url( $url ) {
	static $uploads = null, $seen = array();
	if ( ! preg_match( '/\.(png|jpe?g)$/i', $url ) ) {
		return $url;
	}
	if ( isset( $seen[ $url ] ) ) {
		return $seen[ $url ];
	}
	if ( null === $uploads ) {
		$uploads = wp_get_upload_dir();
	}
	$base = preg_replace( '#^https?:#', '', $uploads['baseurl'] );
	$rel  = preg_replace( '#^https?:#', '', $url );
	$out  = $url;
	if ( 0 === strpos( $rel, $base . '/' ) ) {
		$file = $uploads['basedir'] . substr( $rel, strlen( $base ) );
		if ( false === strpos( $file, '..' ) && is_file( $file . '.webp' ) ) {
			$out = $url . '.webp';
		}
	}
	$seen[ $url ] = $out;
	return $out;
}

function cs_perf_webp_srcset( $srcset ) {
	return implode( ', ', array_map( function ( $cand ) {
		$parts    = preg_split( '/\s+/', trim( $cand ), 2 );
		$parts[0] = cs_perf_webp_url( $parts[0] );
		return implode( ' ', $parts );
	}, explode( ',', $srcset ) ) );
}

function cs_perf_img_tag( $tag ) {
	$tag = cs_perf_fix_small_src( $tag );
	return preg_replace_callback( '/\s(src|data-src|srcset|data-srcset)="([^"]+)"/i', function ( $m ) {
		$val = false !== stripos( $m[1], 'srcset' ) ? cs_perf_webp_srcset( $m[2] ) : cs_perf_webp_url( $m[2] );
		return ' ' . $m[1] . '="' . $val . '"';
	}, $tag );
}

function cs_perf_content_images( $content ) {
	if ( false === stripos( $content, '<img' ) ) {
		return $content;
	}
	return preg_replace_callback( '/<img\b[^>]*>/i', function ( $m ) {
		return cs_perf_img_tag( $m[0] );
	}, $content );
}
add_filter( 'the_content', 'cs_perf_content_images', 98 );
add_filter( 'post_thumbnail_html', 'cs_perf_content_images', 20 );

/**
 * Write "file.ext.webp" next to an image file. Kept only when it is at
 * least 10% smaller than the original; lossless and lossy (q 85) are both
 * tried and the smaller one wins, so charts with text stay sharp when
 * lossless is already small enough.
 *
 * @return string 'made', 'skip' (not worth it / not possible) or 'exists'.
 */
function cs_perf_make_webp( $file ) {
	$target = $file . '.webp';
	if ( is_file( $target ) ) {
		return 'exists';
	}
	if ( ! is_file( $file ) || ! function_exists( 'imagewebp' ) || ! preg_match( '/\.(png|jpe?g)$/i', $file, $ext ) ) {
		return 'skip';
	}
	$size = filesize( $file );
	if ( ! $size || $size > 8 * MB_IN_BYTES ) {
		return 'skip';
	}
	$img = 'png' === strtolower( $ext[1] ) ? @imagecreatefrompng( $file ) : @imagecreatefromjpeg( $file );
	if ( ! $img ) {
		return 'skip';
	}
	if ( ! imageistruecolor( $img ) ) {
		imagepalettetotruecolor( $img );
	}
	imagealphablending( $img, false );
	imagesavealpha( $img, true );

	$best = null;
	$qualities = array( 85 );
	if ( 'png' === strtolower( $ext[1] ) && defined( 'IMG_WEBP_LOSSLESS' ) ) {
		array_unshift( $qualities, IMG_WEBP_LOSSLESS );
	}
	foreach ( $qualities as $q ) {
		ob_start();
		$ok   = imagewebp( $img, null, $q );
		$data = ob_get_clean();
		if ( $ok && $data && ( null === $best || strlen( $data ) < strlen( $best ) ) ) {
			$best = $data;
		}
		// Lossless already saves 25%+: keep the exact pixels.
		if ( $best && IMG_WEBP_LOSSLESS === $q && strlen( $best ) <= $size * 0.75 ) {
			break;
		}
	}
	imagedestroy( $img );
	if ( ! $best || strlen( $best ) > $size * 0.9 ) {
		return 'skip';
	}
	return false !== file_put_contents( $target, $best ) ? 'made' : 'skip';
}

/** Every file (original + sizes) of one image attachment. */
function cs_perf_attachment_files( $id, $meta = null ) {
	$file = get_attached_file( $id );
	if ( ! $file ) {
		return array();
	}
	$files = array( $file );
	$meta  = is_array( $meta ) ? $meta : wp_get_attachment_metadata( $id );
	$dir   = dirname( $file );
	if ( ! empty( $meta['original_image'] ) ) {
		$files[] = $dir . '/' . $meta['original_image'];
	}
	if ( ! empty( $meta['sizes'] ) && is_array( $meta['sizes'] ) ) {
		foreach ( $meta['sizes'] as $s ) {
			if ( ! empty( $s['file'] ) ) {
				$files[] = $dir . '/' . $s['file'];
			}
		}
	}
	return array_unique( $files );
}

/** New uploads get their WebP copies straight away. */
function cs_perf_webp_on_upload( $meta, $id ) {
	if ( wp_attachment_is_image( $id ) ) {
		foreach ( cs_perf_attachment_files( $id, $meta ) as $f ) {
			cs_perf_make_webp( $f );
		}
	}
	return $meta;
}
add_filter( 'wp_generate_attachment_metadata', 'cs_perf_webp_on_upload', 20, 2 );

/** Remove the WebP copies when an attachment is deleted. */
function cs_perf_webp_on_delete( $id ) {
	foreach ( cs_perf_attachment_files( $id ) as $f ) {
		if ( is_file( $f . '.webp' ) ) {
			wp_delete_file( $f . '.webp' );
		}
	}
}
add_action( 'delete_attachment', 'cs_perf_webp_on_delete' );

/**
 * One-time job per CS_PERF_REV: WebP copies for existing images, 10
 * attachments per request, then purge the page cache so pages pick up
 * the new image URLs.
 */
function cs_perf_rev_run() {
	if ( wp_installing() || get_option( 'cs_perf_rev' ) === CS_PERF_REV ) {
		return;
	}
	if ( ! add_option( 'cs_perf_rev_lock', time(), '', 'no' ) ) {
		if ( time() - (int) get_option( 'cs_perf_rev_lock' ) > 300 ) {
			delete_option( 'cs_perf_rev_lock' );
		}
		return;
	}
	$after = (int) get_option( 'cs_perf_webp_after', 0 );
	$ids   = get_posts( array(
		'post_type'        => 'attachment',
		'post_status'      => 'inherit',
		'post_mime_type'   => array( 'image/png', 'image/jpeg' ),
		'posts_per_page'   => 10,
		'orderby'          => 'ID',
		'order'            => 'ASC',
		'fields'           => 'ids',
		'no_found_rows'    => true,
		'suppress_filters' => false,
		'cs_perf_after'    => $after,
	) );
	$ids = array_values( array_filter( $ids, function ( $id ) use ( $after ) {
		return $id > $after;
	} ) );
	foreach ( $ids as $id ) {
		foreach ( cs_perf_attachment_files( $id ) as $f ) {
			cs_perf_make_webp( $f );
		}
		$after = $id;
	}
	if ( count( $ids ) ) {
		update_option( 'cs_perf_webp_after', $after, false );
	} else {
		delete_option( 'cs_perf_webp_after' );
		do_action( 'litespeed_purge_all' );
		update_option( 'cs_perf_rev', CS_PERF_REV, false );
	}
	delete_option( 'cs_perf_rev_lock' );
}
add_action( 'init', 'cs_perf_rev_run', 45 );

/** Lets the job above page through attachments by ID. */
function cs_perf_after_where( $where, $query ) {
	$after = $query->get( 'cs_perf_after' );
	if ( '' !== $after && null !== $after ) {
		global $wpdb;
		$where .= $wpdb->prepare( " AND {$wpdb->posts}.ID > %d", (int) $after );
	}
	return $where;
}
add_filter( 'posts_where', 'cs_perf_after_where', 10, 2 );

/* ------------------------------------------------------------------ */
/* 4.4 CSS nobody uses                                                 */
/* ------------------------------------------------------------------ */

/**
 * True when the current singular post's content has no block markup that
 * needs core block CSS (images, buttons, columns, colours, alignments).
 * Plain paragraphs, headings, lists and shortcodes need none of it; the
 * theme styles .screen-reader-text and images itself.
 */
function cs_perf_needs_block_css() {
	if ( ! is_singular() ) {
		return true;
	}
	$post = get_queried_object();
	if ( ! $post instanceof WP_Post ) {
		return true;
	}
	return (bool) preg_match( '/wp-block-|has-|wp-image-|is-layout|is-style-|align(?:wide|full|left|right|center)|<!--\s*wp:(?!paragraph|heading|list|shortcode|html|separator)/i', $post->post_content );
}

function cs_perf_dequeue_block_css() {
	if ( is_admin() || cs_perf_needs_block_css() ) {
		return;
	}
	foreach ( array( 'wp-block-library', 'wp-block-library-theme', 'classic-theme-styles', 'global-styles' ) as $handle ) {
		wp_dequeue_style( $handle );
	}
}
add_action( 'wp_enqueue_scripts', 'cs_perf_dequeue_block_css', 100 );
// Block themes print global styles in the footer too; harmless no-op here.
add_action( 'wp_footer', 'cs_perf_dequeue_block_css', 1 );

// Every current browser draws emoji itself: no detection script or CSS.
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' );
remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

/* ------------------------------------------------------------------ */
/* 4.5 Browser cache header for HTML                                   */
/* ------------------------------------------------------------------ */

/**
 * Visitors' browsers may reuse a page for 10 minutes (back/forward,
 * re-opening a tab). Short enough that a deploy shows up quickly; never
 * for logged-in users, previews, search, 404s, feeds or POST requests.
 */
function cs_perf_cache_header() {
	if ( headers_sent() || is_admin() || is_user_logged_in() || is_preview() || is_search() || is_404() || is_feed() || is_customize_preview() ) {
		return;
	}
	if ( ! in_array( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : 'GET', array( 'GET', 'HEAD' ), true ) ) {
		return;
	}
	foreach ( array_keys( $_COOKIE ) as $c ) {
		if ( 0 === strpos( $c, 'comment_author_' ) || 0 === strpos( $c, 'wp-postpass_' ) ) {
			return;
		}
	}
	header( 'Cache-Control: public, max-age=600' );
}
add_action( 'template_redirect', 'cs_perf_cache_header', 99 );
