<?php
/**
 * Per-part pages and part-vs-part pages (deep audit D10, items 5.4 / 5.3).
 *
 *  - /hardware/{slug}/ (cs_hardware): one page for every CPU and GPU in
 *    data/hardware-data.php, with specs, estimated FPS, VRAM verdict, the
 *    CPU (or GPU) it pairs with, PSU size, similar parts and its combo pages.
 *  - /comparisons/{a}-vs-{b}/ (cs_comparison): the head-to-head pairs in
 *    cs_pp_vs_pairs(), people's most-searched "X vs Y" matchups.
 *
 * Same pattern as the tier lists (inc/tier-list.php): each post is created
 * once and holds only a shortcode, so every number is rendered from the
 * hardware data and the same engine the calculators use, and stays right
 * when the data changes. Posts are marked with the _cs_generated meta
 * ('part' or 'vs') so the blog hub can leave them out.
 *
 * To add a page: add the part to data/hardware-data.php (part page) or a
 * line to cs_pp_vs_pairs() (comparison), then push. Existing posts, and
 * posts an editor drafted or trashed, are never touched.
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Head-to-head pairs: [ type, a, b ]. Chosen from what people search
 * ("rtx 5060 vs rx 9060 xt", "5600 vs 12400f", "7800x3d vs 9800x3d"):
 * same-class rivals across brands and the generation-to-generation steps.
 */
function cs_pp_vs_pairs() {
	return array(
		// GPUs: current rivals.
		array( 'gpu', 'rtx-5060', 'rx-9060-xt' ),
		array( 'gpu', 'rtx-5060-ti', 'rx-9060-xt' ),
		array( 'gpu', 'rtx-5070', 'rx-9070' ),
		array( 'gpu', 'rtx-5070', 'rx-9070-xt' ),
		array( 'gpu', 'rtx-5070-ti', 'rx-9070-xt' ),
		array( 'gpu', 'rtx-5080', 'rx-7900-xtx' ),
		array( 'gpu', 'rtx-4060', 'rx-7600' ),
		array( 'gpu', 'rtx-4060-ti', 'rx-7700-xt' ),
		array( 'gpu', 'rtx-4070', 'rx-7800-xt' ),
		array( 'gpu', 'rtx-4070-super', 'rx-7900-gre' ),
		array( 'gpu', 'arc-b580', 'rtx-4060' ),
		array( 'gpu', 'arc-b580', 'rtx-5060' ),
		array( 'gpu', 'arc-b580', 'rx-7600' ),
		array( 'gpu', 'rtx-3060', 'rx-6600' ),
		array( 'gpu', 'rtx-3060-ti', 'rx-6700-xt' ),
		array( 'gpu', 'rtx-3070', 'rx-6700-xt' ),
		// GPUs: generation steps.
		array( 'gpu', 'rtx-4060', 'rtx-5060' ),
		array( 'gpu', 'rtx-4060-ti', 'rtx-5060-ti' ),
		array( 'gpu', 'rtx-4070', 'rtx-5070' ),
		array( 'gpu', 'rtx-4070-super', 'rtx-5070' ),
		array( 'gpu', 'rtx-4080', 'rtx-5080' ),
		array( 'gpu', 'rtx-4090', 'rtx-5090' ),
		array( 'gpu', 'rtx-4090', 'rtx-5080' ),
		array( 'gpu', 'rtx-3060', 'rtx-4060' ),
		array( 'gpu', 'rtx-3080', 'rtx-4070' ),
		array( 'gpu', 'rtx-2060', 'rtx-3060' ),
		array( 'gpu', 'gtx-1660-super', 'rtx-3060' ),
		array( 'gpu', 'rtx-5050', 'rtx-4060' ),
		array( 'gpu', 'rx-6600', 'rx-7600' ),
		array( 'gpu', 'rx-7600', 'rx-9060-xt' ),
		array( 'gpu', 'rx-7900-xt', 'rx-9070-xt' ),
		array( 'gpu', 'rx-9070', 'rx-9070-xt' ),
		// K4 (GSC keyword gap, 20 Oct 2026): same-generation tier steps.
		array( 'gpu', 'rtx-5060', 'rtx-5060-ti' ),
		array( 'gpu', 'rtx-5070', 'rtx-5070-ti' ),
		// CPUs.
		array( 'cpu', 'ryzen-5-5600', 'core-i5-12400f' ),
		array( 'cpu', 'ryzen-5-5600', 'ryzen-5-5600x' ),
		array( 'cpu', 'ryzen-5-3600', 'ryzen-5-5600' ),
		array( 'cpu', 'core-i3-12100f', 'ryzen-5-5600' ),
		array( 'cpu', 'ryzen-5-5600', 'ryzen-7-5700x3d' ),
		array( 'cpu', 'ryzen-7-5700x3d', 'ryzen-7-5800x3d' ),
		array( 'cpu', 'core-i5-12400f', 'core-i5-13400f' ),
		array( 'cpu', 'core-i5-13400f', 'core-i5-14400f' ),
		array( 'cpu', 'ryzen-5-7600', 'core-i5-13400f' ),
		array( 'cpu', 'ryzen-5-7600', 'ryzen-5-7600x' ),
		array( 'cpu', 'ryzen-5-7500f', 'ryzen-5-7600' ),
		array( 'cpu', 'ryzen-5-7600', 'ryzen-5-9600x' ),
		array( 'cpu', 'ryzen-5-7600x', 'core-i5-13600k' ),
		array( 'cpu', 'core-i5-13600k', 'core-i5-14600k' ),
		array( 'cpu', 'core-ultra-5-245k', 'ryzen-5-9600x' ),
		array( 'cpu', 'ryzen-7-7700x', 'ryzen-7-9700x' ),
		array( 'cpu', 'core-ultra-7-265k', 'ryzen-7-9700x' ),
		array( 'cpu', 'core-i5-14600k', 'ryzen-7-7800x3d' ),
		array( 'cpu', 'core-i7-14700k', 'ryzen-7-7800x3d' ),
		array( 'cpu', 'ryzen-7-7800x3d', 'ryzen-7-9800x3d' ),
		array( 'cpu', 'ryzen-7-9800x3d', 'core-i9-14900k' ),
		array( 'cpu', 'ryzen-7-9800x3d', 'core-ultra-9-285k' ),
	);
}

function cs_pp_data( $type ) {
	return 'cpu' === $type ? cs_get_cpu_data() : cs_get_gpu_data();
}

function cs_pp_short( $part ) {
	return cs_bn_short_name( $part['name'] );
}

function cs_pp_part_title( $type, $part ) {
	return 'gpu' === $type
		/* translators: %s: GPU name */
		? sprintf( __( '%s Specs, Gaming FPS, Best CPU and PSU', 'computingskill' ), cs_pp_short( $part ) )
		/* translators: %s: CPU name */
		: sprintf( __( '%s Specs, Gaming Performance and Best GPU', 'computingskill' ), cs_pp_short( $part ) );
}

function cs_pp_part_excerpt( $type, $part ) {
	return 'gpu' === $type
		? sprintf( __( '%s specs, estimated FPS at 1080p, 1440p and 4K, whether its VRAM is enough, the CPU to pair it with and the power supply it needs.', 'computingskill' ), $part['name'] )
		: sprintf( __( '%s specs, gaming FPS ceiling, the fastest graphics card it drives without a bottleneck, its platform and upgrade path.', 'computingskill' ), $part['name'] );
}

function cs_pp_vs_slug( $a, $b ) {
	return $a . '-vs-' . $b;
}

function cs_pp_vs_title( $type, $a, $b ) {
	/* translators: 1, 2: part names */
	return sprintf( __( '%1$s vs %2$s: Specs, Gaming FPS and Which to Buy', 'computingskill' ), cs_pp_short( $a ), cs_pp_short( $b ) );
}

/**
 * E3 (SEO-03): <title> for a comparison page, at most 60 characters. The
 * post title (the H1) keeps the full "Specs, Gaming FPS and Which to Buy"
 * wording; the title tag falls back to shorter endings for long names.
 * '' when the post is not one of the generated pairs (Rank Math's title
 * is kept then).
 */
function cs_pp_vs_title_tag( $post_id ) {
	$slug = get_post_field( 'post_name', $post_id );
	foreach ( cs_pp_vs_pairs() as $p ) {
		list( $type, $a, $b ) = $p;
		$data = cs_pp_data( $type );
		if ( cs_pp_vs_slug( $a, $b ) !== $slug || ! isset( $data[ $a ], $data[ $b ] ) ) {
			continue;
		}
		$pair = cs_pp_short( $data[ $a ] ) . ' vs ' . cs_pp_short( $data[ $b ] );
		foreach ( array(
			__( ': Specs, Gaming FPS and Which to Buy', 'computingskill' ),
			__( ': Specs, FPS & Which to Buy', 'computingskill' ),
			__( ': Gaming FPS & Specs', 'computingskill' ),
			__( ' Gaming FPS', 'computingskill' ),
		) as $suffix ) {
			if ( cs_title_length( $pair . $suffix ) <= 60 ) {
				return $pair . $suffix;
			}
		}
		return $pair;
	}
	return '';
}

/* ------------------------------------------------------------------ */
/* Creating the posts                                                  */
/* ------------------------------------------------------------------ */

/**
 * Wanted posts: [ post_type, slug, title, excerpt, shortcode, kind ].
 */
function cs_pp_wanted() {
	$out = array();
	foreach ( array( 'gpu', 'cpu' ) as $type ) {
		foreach ( cs_pp_data( $type ) as $slug => $part ) {
			$out[] = array( 'cs_hardware', $slug, cs_pp_part_title( $type, $part ), cs_pp_part_excerpt( $type, $part ), '[cs_part type="' . $type . '" slug="' . $slug . '"]', 'part' );
		}
	}
	foreach ( cs_pp_vs_pairs() as $p ) {
		list( $type, $a, $b ) = $p;
		$data = cs_pp_data( $type );
		if ( ! isset( $data[ $a ], $data[ $b ] ) ) {
			continue;
		}
		$out[] = array(
			'cs_comparison',
			cs_pp_vs_slug( $a, $b ),
			cs_pp_vs_title( $type, $data[ $a ], $data[ $b ] ),
			sprintf( __( '%1$s or %2$s? Specs side by side, estimated gaming performance, power, and which one fits your build.', 'computingskill' ), $data[ $a ]['name'], $data[ $b ]['name'] ),
			'[cs_versus type="' . $type . '" a="' . $a . '" b="' . $b . '"]',
			'vs',
		);
	}
	return $out;
}

/**
 * Creates the missing posts once per change of the wanted list (hash in
 * an option, add_option() lock), like inc/combo-pages.php.
 */
function cs_pp_sync() {
	if ( wp_installing() || ! post_type_exists( 'cs_hardware' ) || ! post_type_exists( 'cs_comparison' ) ) {
		return;
	}
	$wanted = cs_pp_wanted();
	$hash   = md5( wp_json_encode( $wanted ) );
	if ( get_option( 'cs_part_pages_synced' ) === $hash ) {
		return;
	}
	if ( ! add_option( 'cs_part_pages_lock', time(), '', 'no' ) ) {
		if ( time() - (int) get_option( 'cs_part_pages_lock' ) > 300 ) {
			delete_option( 'cs_part_pages_lock' );
		}
		return;
	}

	$existing = get_posts( array(
		'post_type'      => array( 'cs_hardware', 'cs_comparison' ),
		'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future', 'trash' ),
		'posts_per_page' => -1,
		'no_found_rows'  => true,
	) );
	$have = array();
	foreach ( $existing as $post ) {
		$have[ $post->post_type . '|' . preg_replace( '/__trashed$/', '', $post->post_name ) ] = true;
	}
	$done = (array) get_option( 'cs_part_pages_created', array() );
	$ref  = get_page_by_path( 'methodology' );
	$author = $ref ? (int) $ref->post_author : 0;
	$hubs   = array();

	foreach ( $wanted as $w ) {
		list( $pt, $slug, $title, $excerpt, $content, $kind ) = $w;
		$key = $pt . '|' . $slug;
		// A post we created and an editor later deleted stays deleted.
		if ( isset( $have[ $key ] ) || in_array( $key, $done, true ) ) {
			continue;
		}
		$id = wp_insert_post( array(
			'post_type'      => $pt,
			'post_status'    => 'publish',
			'post_name'      => $slug,
			'post_title'     => $title,
			'post_excerpt'   => $excerpt,
			'post_content'   => $content,
			'post_author'    => $author,
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
		), true );
		if ( is_wp_error( $id ) ) {
			continue;
		}
		update_post_meta( $id, '_cs_generated', $kind );
		$done[] = $key;
		$hubs[ 'part' === $kind ? 'hardware' : 'comparisons' ] = true;
	}
	// E3 (ONP-03): the hub that lists the new pages is updated too.
	if ( $hubs && function_exists( 'cs_touch_hub_pages' ) ) {
		cs_touch_hub_pages( array_keys( $hubs ) );
	}
	update_option( 'cs_part_pages_created', array_values( array_unique( $done ) ), true );
	update_option( 'cs_part_pages_synced', $hash, true );
	delete_option( 'cs_part_pages_lock' );
}
add_action( 'init', 'cs_pp_sync', 31 );

/* ------------------------------------------------------------------ */
/* Links                                                               */
/* ------------------------------------------------------------------ */

/**
 * Published generated posts: [ 'cs_hardware|slug' => url ].
 */
function cs_pp_urls() {
	static $urls = null;
	if ( null !== $urls ) {
		return $urls;
	}
	$urls  = array();
	// E1 (PERF-01): full posts in one query, not one get_post() per ID.
	$posts = get_posts( array(
		'post_type'              => array( 'cs_hardware', 'cs_comparison' ),
		'post_status'            => 'publish',
		'posts_per_page'         => -1,
		'no_found_rows'          => true,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
	) );
	foreach ( $posts as $p ) {
		$urls[ $p->post_type . '|' . $p->post_name ] = get_permalink( $p );
	}
	return $urls;
}

function cs_pp_part_url( $slug ) {
	$urls = cs_pp_urls();
	return isset( $urls[ 'cs_hardware|' . $slug ] ) ? $urls[ 'cs_hardware|' . $slug ] : '';
}

/**
 * Part name, linked to its page when that page is live (and isn't the
 * page being viewed).
 */
function cs_pp_part_link( $slug, $part, $text = '' ) {
	$text = '' === $text ? cs_pp_short( $part ) : $text;
	$url  = cs_pp_part_url( $slug );
	if ( ! $url || ( is_singular( 'cs_hardware' ) && get_post_field( 'post_name' ) === $slug ) ) {
		return esc_html( $text );
	}
	return '<a href="' . esc_url( $url ) . '">' . esc_html( $text ) . '</a>';
}

/**
 * Live comparison pages that include a part: [ url => title text ].
 */
function cs_pp_vs_links_for( $slug ) {
	$urls = cs_pp_urls();
	$out  = array();
	foreach ( cs_pp_vs_pairs() as $p ) {
		list( $type, $a, $b ) = $p;
		if ( $a !== $slug && $b !== $slug ) {
			continue;
		}
		$key  = 'cs_comparison|' . cs_pp_vs_slug( $a, $b );
		$data = cs_pp_data( $type );
		if ( isset( $urls[ $key ], $data[ $a ], $data[ $b ] ) && ! ( is_singular( 'cs_comparison' ) && get_post_field( 'post_name' ) === cs_pp_vs_slug( $a, $b ) ) ) {
			$out[ $urls[ $key ] ] = cs_pp_short( $data[ $a ] ) . ' vs ' . cs_pp_short( $data[ $b ] );
		}
	}
	return $out;
}

function cs_pp_link_list( $links ) {
	$items = array();
	foreach ( $links as $url => $text ) {
		$items[] = '<a href="' . esc_url( $url ) . '">' . esc_html( $text ) . '</a>';
	}
	return cs_bn_join( $items );
}

/* ------------------------------------------------------------------ */
/* Shared facts                                                        */
/* ------------------------------------------------------------------ */

/**
 * Upscaling and frame generation each card supports, from its maker's
 * published feature lists (by architecture):
 *  - NVIDIA RTX 50 (Blackwell): DLSS 4 with Multi Frame Generation (up to 4x).
 *  - RTX 40 (Ada): DLSS 3 Frame Generation (2x) + DLSS 4 upscaling.
 *  - RTX 20/30 (Turing/Ampere): DLSS upscaling only.
 *  - GTX 16: no DLSS.
 *  - Radeon RX 9000 (RDNA 4): FSR 4 upscaling + frame generation.
 *  - RX 6000/7000: FSR 3 upscaling + frame generation, AFMF in the driver.
 *  - Intel Arc: XeSS 2 upscaling + frame generation.
 * FSR 3 upscaling and frame generation work on any modern card in the games
 * that ship them. Also read by calculators.js (window.csHardwareData via
 * the 'upscaler' key added in cs_pp_hardware_upscaler()).
 *
 * @return array [ 'upscaler' => name, 'fg' => 'mfg'|'fg'|'fsr' , 'text' => sentence ]
 */
function cs_pp_gpu_features( $gpu ) {
	$arch = isset( $gpu['architecture'] ) ? $gpu['architecture'] : '';
	$gtx  = false !== strpos( $gpu['name'], 'GTX' );
	if ( 'Blackwell' === $arch ) {
		return array( 'upscaler' => 'DLSS 4', 'fg' => 'mfg', 'text' => __( 'DLSS 4 upscaling and Multi Frame Generation (up to 4x)', 'computingskill' ) );
	}
	if ( 'Ada Lovelace' === $arch ) {
		return array( 'upscaler' => 'DLSS 4', 'fg' => 'fg', 'text' => __( 'DLSS 4 upscaling and DLSS 3 Frame Generation (2x)', 'computingskill' ) );
	}
	if ( in_array( $arch, array( 'Ampere', 'Turing' ), true ) && ! $gtx ) {
		return array( 'upscaler' => 'DLSS', 'fg' => 'fsr', 'text' => __( 'DLSS upscaling (no DLSS Frame Generation; FSR 3 frame generation works in games that offer it)', 'computingskill' ) );
	}
	if ( 'RDNA 4' === $arch ) {
		return array( 'upscaler' => 'FSR 4', 'fg' => 'fg', 'text' => __( 'FSR 4 upscaling and frame generation, plus AFMF in the driver', 'computingskill' ) );
	}
	if ( 0 === strpos( $arch, 'RDNA' ) ) {
		return array( 'upscaler' => 'FSR 3', 'fg' => 'fg', 'text' => __( 'FSR 3 upscaling and frame generation, plus AFMF in the driver', 'computingskill' ) );
	}
	if ( 'Intel' === $gpu['brand'] ) {
		return array( 'upscaler' => 'XeSS 2', 'fg' => 'fg', 'text' => __( 'XeSS 2 upscaling and frame generation', 'computingskill' ) );
	}
	return array( 'upscaler' => 'FSR 3', 'fg' => 'fsr', 'text' => __( 'no DLSS; FSR 3 upscaling and frame generation work in games that offer them', 'computingskill' ) );
}

/**
 * The upscaler / frame generation keys handed to calculators.js with the
 * hardware data, so the FPS Calculator names the right technology.
 */
function cs_pp_hardware_upscaler( $gpus ) {
	foreach ( $gpus as $slug => $g ) {
		$f                         = cs_pp_gpu_features( $g );
		$gpus[ $slug ]['upscaler'] = $f['upscaler'];
		$gpus[ $slug ]['fg']       = $f['fg'];
	}
	return $gpus;
}

/**
 * Lowest-scoring CPU that runs this GPU at $res with no noticeable CPU
 * bottleneck (under 15%, typical AAA game at high), among CPUs from the
 * newest four release years so the advice is something people can buy.
 */
function cs_pp_min_cpu( $gpu, $res ) {
	$cpus   = cs_get_cpu_data();
	$newest = max( array_map( function ( $c ) {
		return (int) $c['release_year'];
	}, $cpus ) );
	uasort( $cpus, function ( $a, $b ) {
		return $a['score'] - $b['score'] ?: strcmp( $a['name'], $b['name'] );
	} );
	foreach ( $cpus as $slug => $c ) {
		if ( (int) $c['release_year'] < $newest - 3 ) {
			continue;
		}
		$r = cs_calculate_bottleneck( $c, $gpu )[ $res ];
		if ( 'cpu' !== $r['component'] || $r['percent'] < 15 ) {
			return array( $slug, $c );
		}
	}
	return array( '', null );
}

/**
 * Similar parts by score: up to $n each side.
 */
function cs_pp_neighbours( $type, $slug, $n = 2 ) {
	$parts = cs_tl_sorted( cs_pp_data( $type ) );
	$keys  = array_keys( $parts );
	$i     = array_search( $slug, $keys, true );
	if ( false === $i ) {
		return array();
	}
	$out = array();
	foreach ( array_merge( array_slice( $keys, max( 0, $i - $n ), min( $n, $i ) ), array_slice( $keys, $i + 1, $n ) ) as $k ) {
		$out[ $k ] = $parts[ $k ];
	}
	return $out;
}

function cs_pp_tier( $type, $part ) {
	return cs_tl_tier_of( $part['score'], 'cpu' === $type ? cs_tl_cpu_tiers() : cs_tl_gpu_tiers() );
}

function cs_pp_tier_badge( $letter ) {
	return '<strong class="cs-tier cs-tier-' . esc_attr( strtolower( $letter ) ) . '">' . esc_html( $letter ) . '</strong>';
}

function cs_pp_table( $caption, $head, $rows ) {
	$html = '<div class="cs-table-wrap"><table class="cs-hardware-table"><caption>' . esc_html( $caption ) . '</caption><thead><tr>';
	foreach ( $head as $h ) {
		// E5 (A11Y-06): an empty corner cell still gets a name for screen readers.
		$html .= '<th scope="col">' . ( '' === $h ? '<span class="screen-reader-text">' . esc_html__( 'Spec', 'computingskill' ) . '</span>' : esc_html( $h ) ) . '</th>';
	}
	$html .= '</tr></thead><tbody>';
	foreach ( $rows as $row ) {
		$html .= '<tr>';
		foreach ( $row as $i => $cell ) {
			$html .= 0 === $i ? '<th scope="row">' . $cell . '</th>' : '<td>' . $cell . '</td>';
		}
		$html .= '</tr>';
	}
	return $html . '</tbody></table></div>';
}

function cs_pp_source( $parts ) {
	$links = array();
	foreach ( $parts as $p ) {
		if ( ! empty( $p['source_url'] ) ) {
			$links[] = '<a href="' . esc_url( $p['source_url'] ) . '" rel="noopener" target="_blank">' . esc_html( sprintf( __( '%s official page', 'computingskill' ), cs_pp_short( $p ) ) ) . '</a>';
		}
	}
	return $links ? '<p class="cs-pp-source">' . esc_html__( 'Specs:', 'computingskill' ) . ' ' . cs_bn_join( $links ) . '. ' . sprintf(
		/* translators: %s: link */
		esc_html__( 'Scores are ComputingSkill’s gaming index, positioned from publicly reported review averages, not our own benchmarks (%s).', 'computingskill' ),
		cs_tl_link( 'methodology', __( 'methodology', 'computingskill' ) )
	) . '</p>' : '';
}

/**
 * Lower-cases the first letter unless the first word is an acronym
 * ("DDR4 only" stays as it is).
 */
function cs_pp_lc( $text ) {
	return preg_match( '/^[A-Z][a-z]/', $text ) ? lcfirst( $text ) : $text;
}

function cs_pp_res_labels() {
	return array( '1080p' => '1080p', '1440p' => '1440p', '4k' => '4K' );
}

/**
 * U2 (U-45): GB of VRAM a card is short of the VRAM rule at a resolution
 * and preset (0 when it has enough). The FPS engine takes 5% off per
 * missing GB there (cs_bottleneck_gpu_memory_factor()), which made tables
 * look contradictory without a marker, e.g. RTX 3060 12GB ahead of the
 * RTX 4060 8GB at 4K high.
 */
function cs_pp_vram_short( $gpu, $res, $preset ) {
	$need = cs_bottleneck_vram_need();
	return isset( $need[ $res ][ $preset ] ) ? max( 0, (int) $need[ $res ][ $preset ] - (int) $gpu['vram_gb'] ) : 0;
}

/**
 * U2 (U-48): "an S-tier", "an A-tier", "a B-tier" (the letter as it is
 * said), for the tier sentences that read "a S-tier".
 */
function cs_pp_a_tier( $letter ) {
	return ( false !== strpos( 'AEFHILMNORSX', substr( (string) $letter, 0, 1 ) ) ? __( 'an', 'computingskill' ) : __( 'a', 'computingskill' ) ) . ' ' . $letter;
}

/** The note under a table that uses the † marker. */
function cs_pp_vram_note() {
	return '<p class="cs-vram-note">' . esc_html__( '† Held back by VRAM: below the memory our VRAM rule asks for at that resolution and texture level, so the estimate drops 5% per missing GB (at most 25%), as games then stutter or lower textures. See the VRAM section below.', 'computingskill' ) . '</p>';
}

/**
 * VRAM verdict per resolution from the VRAM Calculator's minimums:
 * [ res => 'ok' | 'high-only' | 'short' ].
 */
function cs_pp_vram_fit( $gpu ) {
	$need = cs_bottleneck_vram_need();
	$out  = array();
	foreach ( cs_pp_res_labels() as $res => $l ) {
		$v = (int) $gpu['vram_gb'];
		$out[ $res ] = $v >= $need[ $res ]['ultra'] ? 'ok' : ( $v >= $need[ $res ]['high'] ? 'high-only' : 'short' );
	}
	return $out;
}

function cs_pp_vram_sentence( $gpu ) {
	$need  = cs_bottleneck_vram_need();
	$fit   = cs_pp_vram_fit( $gpu );
	$label = cs_pp_res_labels();
	$by    = array( 'ok' => array(), 'high-only' => array(), 'short' => array() );
	foreach ( $fit as $res => $f ) {
		$by[ $f ][] = $label[ $res ];
	}
	$ok    = $by['ok'];
	$high  = $by['high-only'];
	$short = $by['short'];
	$parts = array();
	if ( $ok ) {
		$parts[] = sprintf( __( 'enough for ultra textures at %s', 'computingskill' ), cs_bn_join( $ok ) );
	}
	if ( $high ) {
		$parts[] = sprintf( __( 'enough for high (not ultra) textures at %s', 'computingskill' ), cs_bn_join( $high ) );
	}
	if ( $short ) {
		$res     = array_search( end( $short ), $label, true );
		$parts[] = sprintf( __( 'below the %1$dGB our VRAM rule asks for at %2$s high settings, so expect to lower texture quality there', 'computingskill' ), $need[ $res ]['high'], cs_bn_join( $short ) );
	}
	/* translators: 1: GB, 2: sentence parts */
	return sprintf( __( '%1$dGB of VRAM is %2$s.', 'computingskill' ), (int) $gpu['vram_gb'], cs_bn_join( $parts ) );
}

/* ------------------------------------------------------------------ */
/* GPU page                                                            */
/* ------------------------------------------------------------------ */

function cs_pp_part_shortcode( $atts ) {
	$atts = shortcode_atts( array( 'type' => 'gpu', 'slug' => '' ), $atts, 'cs_part' );
	$type = 'cpu' === $atts['type'] ? 'cpu' : 'gpu';
	$data = cs_pp_data( $type );
	if ( ! isset( $data[ $atts['slug'] ] ) ) {
		return '';
	}
	return 'gpu' === $type ? cs_pp_gpu_html( $atts['slug'], $data[ $atts['slug'] ] ) : cs_pp_cpu_html( $atts['slug'], $data[ $atts['slug'] ] );
}
add_shortcode( 'cs_part', 'cs_pp_part_shortcode' );

function cs_pp_gpu_html( $slug, $gpu ) {
	$name   = cs_pp_short( $gpu );
	$labels = cs_pp_res_labels();
	$letter = cs_pp_tier( 'gpu', $gpu );
	$tiers  = cs_tl_gpu_tiers();
	$target = cs_bn_target_res( $gpu );
	$feat   = cs_pp_gpu_features( $gpu );
	list( $top_slug, $top ) = cs_tl_top_cpu();
	$fps = array();
	foreach ( $labels as $res => $l ) {
		$fps[ $res ] = cs_tl_fps( $top, $gpu, $res );
	}
	list( $min1080_slug, $min1080 ) = cs_pp_min_cpu( $gpu, '1080p' );
	list( $min1440_slug, $min1440 ) = cs_pp_min_cpu( $gpu, '1440p' );
	$psu_cpu = $min1080 ? $min1080 : $top;
	$psu     = cs_aeo_psu( $psu_cpu, $gpu );
	$targets = cs_tl_gpu_targets();

	$html = cs_tl_quick_answer( esc_html( sprintf(
		/* translators: 1: GPU, 2: tier, 3: score, 4: resolution, 5-7: FPS, 8: VRAM sentence, 9: PSU W */
		__( 'The %1$s is %2$s-tier graphics card in our GPU tier list (gaming score %3$d, RTX 4090 = 100), built for %4$s gaming. With a fast CPU it averages about %5$d FPS at 1080p, %6$d FPS at 1440p and %7$d FPS at 4K in a typical AAA game at high settings. %8$s Its maker recommends a %9$dW power supply.', 'computingskill' ),
		$name, cs_pp_a_tier( $letter ), (int) $gpu['score'], $labels[ $target ],
		round( $fps['1080p'][0] ), round( $fps['1440p'][0] ), round( $fps['4k'][0] ),
		cs_pp_vram_sentence( $gpu ),
		(int) $gpu['recommended_psu_w']
	) ) );

	// Specs.
	$html .= '<h2>' . esc_html( sprintf( __( '%s Specs', 'computingskill' ), $name ) ) . '</h2>';
	$rows  = array(
		array( esc_html__( 'Maker', 'computingskill' ), esc_html( $gpu['brand'] ) ),
		array( esc_html__( 'Architecture', 'computingskill' ), esc_html( $gpu['architecture'] ) ),
		array( esc_html__( 'VRAM', 'computingskill' ), esc_html( $gpu['vram_gb'] . 'GB ' . $gpu['memory_type'] ) ),
		array( esc_html__( 'Memory bus', 'computingskill' ), esc_html( $gpu['memory_bus'] . '-bit' ) ),
		array( esc_html__( 'Boost clock', 'computingskill' ), esc_html( $gpu['boost_clock_mhz'] . ' MHz' ) ),
		array( esc_html( $gpu['core_label'] ), esc_html( number_format_i18n( $gpu['cores'] ) ) ),
		array( esc_html__( 'Board power', 'computingskill' ), esc_html( $gpu['tdp_w'] . 'W' ) ),
		array( esc_html__( 'Maker’s recommended PSU', 'computingskill' ), esc_html( $gpu['recommended_psu_w'] . 'W' ) ),
		array( esc_html__( 'Upscaling / frame generation', 'computingskill' ), esc_html( ucfirst( $feat['text'] ) ) ),
		array( esc_html__( 'Released', 'computingskill' ), (int) $gpu['release_year'] ),
		array( esc_html__( 'ComputingSkill gaming score', 'computingskill' ), (int) $gpu['score'] . ' ' . sprintf( esc_html__( '(%s tier)', 'computingskill' ), cs_pp_tier_badge( $letter ) ) ),
	);
	$html .= cs_pp_table( sprintf( __( '%s specifications', 'computingskill' ), $name ), array( __( 'Spec', 'computingskill' ), $name ), $rows );
	$html .= cs_pp_source( array( $gpu ) );

	// FPS.
	$html .= '<h2>' . esc_html( sprintf( __( '%s Gaming Performance (Estimated FPS)', 'computingskill' ), $name ) ) . '</h2>';
	$html .= '<p>' . sprintf(
		/* translators: 1: CPU, 2: link */
		esc_html__( 'Average FPS from the %2$s’s formula for a typical AAA game, paired with the %1$s so the card is the limit wherever it can be. Figures marked * are held back by the CPU instead. Ray tracing, upscaling and frame generation are not included; they are estimates, not benchmarks.', 'computingskill' ),
		esc_html( cs_pp_short( $top ) ),
		cs_tl_link( 'fps-calculator', __( 'FPS Calculator', 'computingskill' ) )
	) . '</p>';
	$rows      = array();
	$vram_mark = false;
	foreach ( array( 'low' => __( 'Low', 'computingskill' ), 'medium' => __( 'Medium', 'computingskill' ), 'high' => __( 'High', 'computingskill' ), 'ultra' => __( 'Ultra', 'computingskill' ) ) as $preset => $pl ) {
		$row = array( esc_html( $pl ) );
		foreach ( $labels as $res => $l ) {
			list( $f, $lim ) = cs_tl_fps( $top, $gpu, $res, $preset );
			$short = cs_pp_vram_short( $gpu, $res, $preset );
			$vram_mark = $vram_mark || $short;
			$row[] = (int) round( $f ) . ( 'cpu' === $lim ? '*' : '' ) . ( $short ? '†' : '' );
		}
		$rows[] = $row;
	}
	$row = array( esc_html__( 'Esports titles, high', 'computingskill' ) );
	foreach ( $labels as $res => $l ) {
		list( $f, $lim ) = cs_tl_fps( $top, $gpu, $res, 'high', 'esports' );
		$row[] = (int) round( $f ) . ( 'cpu' === $lim ? '*' : '' );
	}
	$rows[] = $row;
	$html  .= cs_pp_table( sprintf( __( '%s estimated average FPS', 'computingskill' ), $name ), array( __( 'Settings', 'computingskill' ), '1080p', '1440p', '4K' ), $rows );
	$html  .= $vram_mark ? cs_pp_vram_note() : '';
	$html  .= '<p>' . sprintf(
		/* translators: 1: GPU, 2: tier letter, 3: tier meaning, 4: link */
		esc_html__( 'In the %4$s the %1$s sits in %2$s tier: %3$s.', 'computingskill' ),
		esc_html( $name ),
		esc_html( $letter ),
		esc_html( cs_pp_lc( $tiers[ $letter ][1] ) ),
		cs_tl_link( 'gpu-tier-list', __( 'GPU tier list', 'computingskill' ) )
	) . ' ' . esc_html( sprintf(
		/* translators: 1: upscaling sentence */
		__( 'In games that support it, %1$s can raise these frame rates, at some cost in image quality or input latency.', 'computingskill' ),
		$feat['text']
	) ) . ' ' . cs_tl_link( 'dlss-fsr-frame-generation-explained', __( 'How upscaling and frame generation work', 'computingskill' ) ) . '.</p>';

	// VRAM.
	$html .= '<h2>' . esc_html( sprintf( __( 'Is %1$dGB of VRAM Enough on the %2$s?', 'computingskill' ), (int) $gpu['vram_gb'], $name ) ) . '</h2>';
	$html .= '<p>' . esc_html( cs_pp_vram_sentence( $gpu ) ) . ' ' . sprintf(
		/* translators: %s: link */
		esc_html__( 'The rule is the %s’s minimum for each resolution and texture level (1080p: 6GB at high, 8GB at ultra; 1440p: 8GB / 10GB; 4K: 12GB / 14GB). Short on VRAM, a game stutters or swaps textures rather than simply losing frames.', 'computingskill' ),
		cs_tl_link( 'gpu-vram-calculator', __( 'GPU VRAM Calculator', 'computingskill' ) )
	) . '</p>';

	// Best CPU.
	$html .= '<h2>' . esc_html( sprintf( __( 'Best CPU for the %s', 'computingskill' ), $name ) ) . '</h2>';
	$p     = array();
	if ( $min1080 ) {
		$p[] = sprintf(
			/* translators: 1: CPU link, 2: GPU */
			esc_html__( 'At 1080p, the %1$s is the most modest current CPU that runs the %2$s without a noticeable CPU bottleneck (under 15%% in our engine, typical AAA game at high settings).', 'computingskill' ),
			cs_pp_part_link( $min1080_slug, $min1080 ),
			esc_html( $name )
		);
	}
	if ( ! $min1080 ) {
		$p[] = sprintf( esc_html__( 'At 1080p even the fastest CPUs in our list leave part of the %s unused, which is normal for the fastest cards.', 'computingskill' ), esc_html( $name ) );
	}
	if ( $min1440 && $min1440_slug !== $min1080_slug ) {
		$p[] = $min1080
			? sprintf( esc_html__( 'At 1440p the card does more of the work, so the %s is already enough.', 'computingskill' ), cs_pp_part_link( $min1440_slug, $min1440 ) )
			: sprintf( esc_html__( 'At 1440p the %s is the most modest current CPU that keeps it under a noticeable bottleneck.', 'computingskill' ), cs_pp_part_link( $min1440_slug, $min1440 ) );
	}
	$p[]   = esc_html__( 'A faster CPU still helps in esports and CPU-heavy games, where the CPU sets the frame rate far more often.', 'computingskill' );
	$html .= '<p>' . implode( ' ', $p ) . '</p>';

	$pairs   = cs_bn_published_pairs();
	$default = array( 'core-i3-12100f', 'ryzen-5-5600', 'core-i5-12400f', 'ryzen-5-7600', 'core-i5-14600k', 'ryzen-7-7800x3d', 'ryzen-7-9800x3d' );
	$cpus    = cs_get_cpu_data();
	$list    = array();
	foreach ( $cpus as $c_slug => $c ) {
		if ( in_array( $c_slug, $default, true ) || isset( $pairs[ $c_slug . '|' . $slug ] ) ) {
			$list[ $c_slug ] = $c;
		}
	}
	uasort( $list, function ( $a, $b ) {
		return $a['score'] - $b['score'];
	} );
	$rows = array();
	foreach ( $list as $c_slug => $c ) {
		$r    = cs_calculate_bottleneck( $c, $gpu );
		$cell = cs_pp_part_link( $c_slug, $c );
		$row  = array( $cell );
		foreach ( $labels as $res => $l ) {
			$row[] = esc_html( cs_bn_cell( $r[ $res ] ) );
		}
		$row[]  = isset( $pairs[ $c_slug . '|' . $slug ] ) ? '<a href="' . esc_url( $pairs[ $c_slug . '|' . $slug ] ) . '">' . esc_html__( 'Full pairing', 'computingskill' ) . '</a>' : '—';
		$rows[] = $row;
	}
	$html .= cs_pp_table( sprintf( __( '%s with popular CPUs (typical AAA game, high settings)', 'computingskill' ), $name ), array( __( 'CPU', 'computingskill' ), '1080p', '1440p', '4K', __( 'Pair page', 'computingskill' ) ), $rows );
	$html .= '<p>' . sprintf(
		/* translators: %s: link */
		esc_html__( '“GPU-limited” is normal and means the card is the part working hardest. A CPU %% is how much of the card goes unused. Check any other pair in the %s.', 'computingskill' ),
		cs_tl_link( 'pc-bottleneck-calculator', __( 'PC Bottleneck Calculator', 'computingskill' ) )
	) . '</p>';

	// PSU.
	$html .= '<h2>' . esc_html( sprintf( __( 'What PSU Does the %s Need?', 'computingskill' ), $name ) ) . '</h2>';
	$html .= '<p>' . sprintf(
		/* translators: 1: GPU, 2: W, 3: board power, 4: CPU, 5: load W, 6: rec W, 7: link */
		esc_html__( 'The %1$s’s maker recommends a %2$dW power supply, and the card itself draws up to about %3$dW. With a %4$s, the %7$s estimates a full-load draw of about %5$dW for the whole PC and recommends a %6$dW unit (30%% headroom, rounded up). A power-hungry CPU such as a Core i9 needs more, so check your exact parts.', 'computingskill' ),
		esc_html( $name ),
		(int) $gpu['recommended_psu_w'],
		(int) $gpu['tdp_w'],
		esc_html( cs_pp_short( $psu_cpu ) ),
		(int) round( $psu[0] ),
		(int) $psu[1],
		cs_tl_link( 'psu-calculator', __( 'PSU Calculator', 'computingskill' ) )
	) . '</p>';

	// Similar.
	$html .= '<h2>' . esc_html__( 'Similar Graphics Cards', 'computingskill' ) . '</h2>';
	$rows  = array();
	foreach ( array( $slug => $gpu ) + cs_pp_neighbours( 'gpu', $slug ) as $g_slug => $g ) {
		list( $f ) = cs_tl_fps( $top, $g, '1440p' );
		$rows[ $g['score'] . '-' . $g_slug ] = array( cs_pp_part_link( $g_slug, $g ), (int) $g['score'], esc_html( $g['vram_gb'] . 'GB' ), (int) round( $f ), esc_html( $g['tdp_w'] . 'W' ), (int) $g['release_year'] );
	}
	krsort( $rows, SORT_NATURAL );
	$html .= cs_pp_table( __( 'Cards close to this one in our ranking', 'computingskill' ), array( __( 'Graphics card', 'computingskill' ), __( 'Score', 'computingskill' ), __( 'VRAM', 'computingskill' ), __( 'FPS 1440p high', 'computingskill' ), __( 'Board power', 'computingskill' ), __( 'Released', 'computingskill' ) ), array_values( $rows ) );
	$vs = cs_pp_vs_links_for( $slug );
	if ( $vs ) {
		$html .= '<p>' . esc_html__( 'Head-to-head comparisons:', 'computingskill' ) . ' ' . cs_pp_link_list( $vs ) . '.</p>';
	}
	$html .= '<p>' . sprintf(
		/* translators: 1, 2: links */
		esc_html__( 'Try the %1$s with your own CPU, resolution and settings in the %2$s, or see which games it runs in %3$s.', 'computingskill' ),
		esc_html( $name ),
		'<a href="' . esc_url( add_query_arg( 'gpu', $slug, cs_pp_page_url( 'fps-calculator' ) ) ) . '">' . esc_html__( 'FPS Calculator', 'computingskill' ) . '</a>',
		cs_tl_link( 'can-i-run-it', __( 'Can I Run It', 'computingskill' ) )
	) . '</p>';

	// FAQ.
	$res_good = $gpu['score'] >= $targets['1440p'];
	$f1440    = round( $fps['1440p'][0] );
	$html    .= cs_tl_faq( array(
		sprintf( __( 'Is the %s good for 1440p gaming?', 'computingskill' ), $name ) => $res_good
			? sprintf( __( 'Yes. Its gaming score of %1$d clears the 1440p line (%2$d) our PC Upgrade Calculator uses, and it averages about %3$d FPS at 1440p high in a typical AAA game with a fast CPU.', 'computingskill' ), (int) $gpu['score'], (int) $targets['1440p'], $f1440 )
			: ( $f1440 >= 60
				? sprintf( __( 'It manages about %3$d FPS at 1440p high in a typical AAA game with a fast CPU, but it is below the level our PC Upgrade Calculator calls comfortable for 1440p (score %1$d vs %2$d), so newer, heavier games will need medium settings or upscaling.', 'computingskill' ), (int) $gpu['score'], (int) $targets['1440p'], $f1440 )
				: sprintf( __( 'It runs 1440p, but below the level our PC Upgrade Calculator calls comfortable for high settings (score %1$d vs %2$d needed). Expect about %3$d FPS at 1440p high in a typical AAA game; medium settings or upscaling get you closer to 60.', 'computingskill' ), (int) $gpu['score'], (int) $targets['1440p'], $f1440 ) ),
		sprintf( __( 'What CPU should I pair with the %s?', 'computingskill' ), $name ) => $min1080
			? ( $min1440 && $min1440_slug !== $min1080_slug
				? sprintf( __( 'For 1080p, at least a %1$s to avoid a noticeable CPU bottleneck; at 1440p, a %2$s is enough. Faster CPUs help in esports and strategy games.', 'computingskill' ), cs_pp_short( $min1080 ), cs_pp_short( $min1440 ) )
				: sprintf( __( 'At least a %s, at 1080p and 1440p alike, to avoid a noticeable CPU bottleneck. Faster CPUs help in esports and strategy games.', 'computingskill' ), cs_pp_short( $min1080 ) ) )
			: __( 'Even the fastest CPUs in our list leave some of this card unused at 1080p, which is normal for the fastest cards. Pair it with a top gaming CPU and play at 1440p or 4K.', 'computingskill' ),
		sprintf( __( 'What power supply does the %s need?', 'computingskill' ), $name ) => sprintf( __( 'Its maker recommends %1$dW. With a %2$s our PSU Calculator suggests %3$dW for the whole PC.', 'computingskill' ), (int) $gpu['recommended_psu_w'], cs_pp_short( $psu_cpu ), (int) $psu[1] ),
		sprintf( __( 'Is %1$dGB of VRAM enough on the %2$s?', 'computingskill' ), (int) $gpu['vram_gb'], $name ) => cs_pp_vram_sentence( $gpu ),
	) );
	return $html;
}

function cs_pp_page_url( $slug ) {
	$page = get_page_by_path( $slug );
	return $page ? get_permalink( $page ) : home_url( '/' . $slug . '/' );
}

/* ------------------------------------------------------------------ */
/* CPU page                                                            */
/* ------------------------------------------------------------------ */

function cs_pp_cpu_ceilings( $cpu ) {
	$base = $cpu['score'] * CS_BN_CPU_PARITY * CS_TL_FPS_PER_POINT;
	$out  = array();
	foreach ( cs_bottleneck_game_profiles() as $k => $p ) {
		$out[ $k ] = (int) round( $base * $p['cpu'] );
	}
	return $out;
}

function cs_pp_cpu_html( $slug, $cpu ) {
	$name    = cs_pp_short( $cpu );
	$labels  = cs_pp_res_labels();
	$letter  = cs_pp_tier( 'cpu', $cpu );
	$ceil    = cs_pp_cpu_ceilings( $cpu );
	$max     = array();
	foreach ( $labels as $res => $l ) {
		$max[ $res ] = cs_tl_cpu_max_gpu( $cpu, $res );
	}
	$upgrade = cs_bn_socket_upgrade( $slug, $cpu );
	$pairs   = cs_bn_published_pairs();
	$power   = cs_aeo_cpu_power( $cpu );

	$html = cs_tl_quick_answer( esc_html( sprintf(
		/* translators: 1: CPU, 2: tier, 3: label, 4: score, 5: FPS, 6: FPS, 7: GPU, 8: socket */
		__( 'The %1$s is %2$s-tier gaming CPU in our CPU tier list (%3$s, gaming score %4$d). It can prepare about %5$d FPS in a typical AAA game and about %6$d FPS in esports titles, whatever the graphics card. The fastest card it drives at 1440p without a noticeable bottleneck is the %7$s. It uses the %8$s socket.', 'computingskill' ),
		$name, cs_pp_a_tier( $letter ), cs_tl_cpu_label( $cpu['score'] ), (int) $cpu['score'], $ceil['aaa'], $ceil['esports'],
		$max['1440p'][1] ? cs_pp_short( $max['1440p'][1] ) : '—',
		$cpu['socket']
	) ) );

	$html .= '<h2>' . esc_html( sprintf( __( '%s Specs', 'computingskill' ), $name ) ) . '</h2>';
	$rows  = array(
		array( esc_html__( 'Maker', 'computingskill' ), esc_html( $cpu['brand'] ) ),
		array( esc_html__( 'Generation', 'computingskill' ), esc_html( isset( $cpu['generation'] ) ? $cpu['generation'] : '' ) ),
		array( esc_html__( 'Cores / threads', 'computingskill' ), (int) $cpu['cores'] . ' / ' . (int) $cpu['threads'] ),
		array( esc_html__( 'Base / boost clock', 'computingskill' ), esc_html( $cpu['base_clock_ghz'] . ' / ' . $cpu['boost_clock_ghz'] . ' GHz' ) ),
		array( esc_html__( 'Rated power (TDP)', 'computingskill' ), esc_html( $cpu['tdp_w'] . 'W' ) ),
		array( esc_html__( 'Maximum power', 'computingskill' ), esc_html( (int) $power['max'] . 'W' ) ),
		array( esc_html__( 'Socket', 'computingskill' ), esc_html( $cpu['socket'] ) ),
		array( esc_html__( 'Memory', 'computingskill' ), esc_html( cs_bn_socket_memory( $cpu['socket'] ) ) ),
		array( esc_html__( 'Released', 'computingskill' ), (int) $cpu['release_year'] ),
		array( esc_html__( 'ComputingSkill gaming score', 'computingskill' ), (int) $cpu['score'] . ' ' . sprintf( esc_html__( '(%s tier)', 'computingskill' ), cs_pp_tier_badge( $letter ) ) ),
	);
	$html .= cs_pp_table( sprintf( __( '%s specifications', 'computingskill' ), $name ), array( __( 'Spec', 'computingskill' ), $name ), $rows );
	$html .= cs_pp_source( array( $cpu ) );

	$html .= '<h2>' . esc_html( sprintf( __( '%s Gaming Performance', 'computingskill' ), $name ) ) . '</h2>';
	$html .= '<p>' . sprintf(
		/* translators: %s: link */
		esc_html__( 'A CPU sets a ceiling on frame rate: the frames per second it can prepare when the graphics card isn’t the limit. These are the %s’s ceilings for each game type; your real FPS is the lower of this and what your graphics card manages.', 'computingskill' ),
		cs_tl_link( 'fps-calculator', __( 'FPS Calculator', 'computingskill' ) )
	) . '</p>';
	$html .= cs_pp_table( sprintf( __( '%s FPS ceiling by game type', 'computingskill' ), $name ), array( __( 'Game type', 'computingskill' ), __( 'FPS ceiling', 'computingskill' ) ), array(
		array( esc_html__( 'Typical AAA game', 'computingskill' ), $ceil['aaa'] ),
		array( esc_html__( 'Esports (Valorant, CS2, Fortnite)', 'computingskill' ), $ceil['esports'] ),
		array( esc_html__( 'CPU-heavy (strategy, simulation, MMO)', 'computingskill' ), $ceil['cpu-heavy'] ),
	) );

	// U1 (U-40): a ceiling, not a buying recommendation ("up to the RTX 5090"
	// read as "pair a $100 CPU with a 5090").
	$html .= '<h2>' . esc_html( sprintf( __( 'Best GPU for the %s: the Fastest Card It Keeps Up With', 'computingskill' ), $name ) ) . '</h2>';
	$items = array();
	foreach ( $labels as $res => $l ) {
		list( $g_slug, $g ) = $max[ $res ];
		if ( $g ) {
			$link    = isset( $pairs[ $slug . '|' . $g_slug ] ) ? ' (<a href="' . esc_url( $pairs[ $slug . '|' . $g_slug ] ) . '">' . esc_html__( 'pair page', 'computingskill' ) . '</a>)' : '';
			$items[] = '<li><strong>' . esc_html( $l ) . ':</strong> ' . sprintf( esc_html__( 'keeps up with cards as fast as the %s', 'computingskill' ), cs_pp_part_link( $g_slug, $g ) ) . $link . '</li>';
		}
	}
	$html .= '<p>' . esc_html__( 'This is a ceiling, not a buying recommendation: the fastest graphics card in our list this CPU runs without a noticeable CPU bottleneck (under 15%, typical AAA game at high settings). Any card up to it is a good match, so pick one for your budget and screen; a faster card still works, but part of it goes unused at that resolution.', 'computingskill' ) . '</p><ul>' . implode( '', $items ) . '</ul>';
	$html .= '<p>' . sprintf(
		/* translators: %s: link */
		esc_html__( 'To choose a card for your budget and resolution, see the %s.', 'computingskill' ),
		cs_tl_link( 'gpu-tier-list', __( 'GPU tier list', 'computingskill' ) )
	) . '</p>';

	$default = array( 'rtx-3060', 'rtx-4060', 'rtx-5060', 'rx-9060-xt', 'rtx-5070', 'rx-9070-xt', 'rtx-5070-ti', 'rtx-5080', 'rtx-4090' );
	$list    = array();
	foreach ( cs_get_gpu_data() as $g_slug => $g ) {
		if ( in_array( $g_slug, $default, true ) || isset( $pairs[ $slug . '|' . $g_slug ] ) ) {
			$list[ $g_slug ] = $g;
		}
	}
	uasort( $list, function ( $a, $b ) {
		return $a['score'] - $b['score'];
	} );
	$rows = array();
	foreach ( $list as $g_slug => $g ) {
		$r   = cs_calculate_bottleneck( $cpu, $g );
		$row = array( cs_pp_part_link( $g_slug, $g ) );
		foreach ( $labels as $res => $l ) {
			$row[] = esc_html( cs_bn_cell( $r[ $res ] ) );
		}
		$row[]  = isset( $pairs[ $slug . '|' . $g_slug ] ) ? '<a href="' . esc_url( $pairs[ $slug . '|' . $g_slug ] ) . '">' . esc_html__( 'Full pairing', 'computingskill' ) . '</a>' : '—';
		$rows[] = $row;
	}
	$html .= cs_pp_table( sprintf( __( '%s with popular graphics cards (typical AAA game, high settings)', 'computingskill' ), $name ), array( __( 'Graphics card', 'computingskill' ), '1080p', '1440p', '4K', __( 'Pair page', 'computingskill' ) ), $rows );
	$html .= '<p>' . sprintf(
		esc_html__( '“GPU-limited” is normal: the card is the part working hardest. A CPU %% is how much of the card goes unused. Check any other pair in the %s.', 'computingskill' ),
		cs_tl_link( 'pc-bottleneck-calculator', __( 'PC Bottleneck Calculator', 'computingskill' ) )
	) . '</p>';

	$html .= '<h2>' . esc_html__( 'Platform and Upgrade Path', 'computingskill' ) . '</h2>';
	$p     = sprintf(
		/* translators: 1: CPU, 2: socket, 3: memory */
		esc_html__( 'The %1$s uses the %2$s socket (%3$s).', 'computingskill' ),
		esc_html( $name ), esc_html( $cpu['socket'] ), esc_html( cs_pp_lc( cs_bn_socket_memory( $cpu['socket'] ) ) )
	);
	if ( $upgrade ) {
		$u_slug = array_search( $upgrade, cs_get_cpu_data(), true );
		$p     .= ' ' . sprintf(
			/* translators: 1: CPU link, 2: score, 3: percent */
			esc_html__( 'The fastest gaming CPU in our list for the same socket is the %1$s (score %2$d, about %3$d%% higher FPS ceiling), usually a drop-in upgrade after a BIOS update.', 'computingskill' ),
			cs_pp_part_link( $u_slug, $upgrade ),
			(int) $upgrade['score'],
			(int) round( ( $upgrade['score'] / $cpu['score'] - 1 ) * 100 )
		);
	} else {
		$p .= ' ' . esc_html__( 'It is already the fastest gaming CPU in our list for this socket.', 'computingskill' );
	}
	$p    .= ' ' . sprintf(
		/* translators: 1: max W, 2, 3: links */
		esc_html__( 'It can draw up to about %1$dW, which the %2$s and %3$s account for.', 'computingskill' ),
		(int) $power['max'],
		cs_tl_link( 'psu-calculator', __( 'PSU Calculator', 'computingskill' ) ),
		cs_tl_link( 'pc-cooling-calculator', __( 'PC Cooling Calculator', 'computingskill' ) ) // U-36: the tool's real name and page.
	);
	$html .= '<p>' . $p . '</p>';

	$html .= '<h2>' . esc_html__( 'Similar Processors', 'computingskill' ) . '</h2>';
	$rows  = array();
	foreach ( array( $slug => $cpu ) + cs_pp_neighbours( 'cpu', $slug ) as $c_slug => $c ) {
		$cc = cs_pp_cpu_ceilings( $c );
		$rows[ $c['score'] . '-' . $c_slug ] = array( cs_pp_part_link( $c_slug, $c ), (int) $c['score'], (int) $c['cores'] . ' / ' . (int) $c['threads'], esc_html( $c['socket'] ), $cc['aaa'], (int) $c['release_year'] );
	}
	krsort( $rows, SORT_NATURAL );
	$html .= cs_pp_table( __( 'Processors close to this one in our ranking', 'computingskill' ), array( __( 'Processor', 'computingskill' ), __( 'Score', 'computingskill' ), __( 'Cores / threads', 'computingskill' ), __( 'Socket', 'computingskill' ), __( 'FPS ceiling, AAA', 'computingskill' ), __( 'Released', 'computingskill' ) ), array_values( $rows ) );
	$vs = cs_pp_vs_links_for( $slug );
	if ( $vs ) {
		$html .= '<p>' . esc_html__( 'Head-to-head comparisons:', 'computingskill' ) . ' ' . cs_pp_link_list( $vs ) . '.</p>';
	}
	$html .= '<p>' . sprintf(
		esc_html__( 'See where it ranks in the %1$s, or check it with your own graphics card in the %2$s.', 'computingskill' ),
		cs_tl_link( 'cpu-tier-list', __( 'CPU tier list', 'computingskill' ) ),
		'<a href="' . esc_url( add_query_arg( 'cpu', $slug, cs_pp_page_url( 'pc-bottleneck-calculator' ) ) ) . '">' . esc_html__( 'PC Bottleneck Calculator', 'computingskill' ) . '</a>'
	) . '</p>';

	$g1440 = $max['1440p'][1];
	$html .= cs_tl_faq( array(
		sprintf( __( 'Is the %s good for gaming?', 'computingskill' ), $name ) => sprintf( __( 'It is %1$s-tier, %2$s gaming CPU in our list (score %3$d). It prepares about %4$d FPS in a typical AAA game and %5$d FPS in esports titles, so it suits high-refresh gaming up to that point.', 'computingskill' ), cs_pp_a_tier( $letter ), strtolower( cs_tl_cpu_label( $cpu['score'] ) ), (int) $cpu['score'], $ceil['aaa'], $ceil['esports'] ),
		sprintf( __( 'What is the best GPU for the %s?', 'computingskill' ), $name ) => $g1440 ? sprintf( __( 'It depends on your budget and screen: any card up to the fastest one this CPU keeps up with is a good match. That ceiling is the %1$s at 1440p without a noticeable bottleneck%2$s. At 4K the card does more of the work, so faster cards still make sense. The GPU tier list helps you pick one for your budget.', 'computingskill' ), cs_pp_short( $g1440 ), $max['1080p'][1] ? sprintf( __( ', and the %s at 1080p', 'computingskill' ), cs_pp_short( $max['1080p'][1] ) ) : '' ) : __( 'Every card in our list leaves this CPU waiting at 1440p.', 'computingskill' ),
		sprintf( __( 'What can I upgrade the %s to?', 'computingskill' ), $name ) => $upgrade ? sprintf( __( 'On the same %1$s motherboard, the fastest gaming option in our list is the %2$s (score %3$d). A BIOS update may be needed first.', 'computingskill' ), $cpu['socket'], cs_pp_short( $upgrade ), (int) $upgrade['score'] ) : sprintf( __( 'It is already the fastest gaming CPU in our list for %s; a bigger step means a new platform.', 'computingskill' ), $cpu['socket'] ),
		sprintf( __( 'Does the %s support DDR5?', 'computingskill' ), $name ) => sprintf( __( 'Its %1$s platform supports %2$s.', 'computingskill' ), $cpu['socket'], cs_pp_lc( cs_bn_socket_memory( $cpu['socket'] ) ) ),
	) );
	return $html;
}

/* ------------------------------------------------------------------ */
/* Versus pages                                                        */
/* ------------------------------------------------------------------ */

function cs_pp_versus_shortcode( $atts ) {
	$atts = shortcode_atts( array( 'type' => 'gpu', 'a' => '', 'b' => '' ), $atts, 'cs_versus' );
	$type = 'cpu' === $atts['type'] ? 'cpu' : 'gpu';
	$data = cs_pp_data( $type );
	if ( ! isset( $data[ $atts['a'] ], $data[ $atts['b'] ] ) ) {
		return '';
	}
	return 'gpu' === $type
		? cs_pp_vs_gpu_html( $atts['a'], $data[ $atts['a'] ], $atts['b'], $data[ $atts['b'] ] )
		: cs_pp_vs_cpu_html( $atts['a'], $data[ $atts['a'] ], $atts['b'], $data[ $atts['b'] ] );
}
add_shortcode( 'cs_versus', 'cs_pp_versus_shortcode' );

/**
 * "about 12% faster" / "within 5%, about even".
 */
function cs_pp_gap( $x, $y ) {
	return $y > 0 ? (int) round( ( $x / $y - 1 ) * 100 ) : 0;
}

function cs_pp_vs_gpu_html( $a_slug, $a, $b_slug, $b ) {
	$an     = cs_pp_short( $a );
	$bn     = cs_pp_short( $b );
	$labels = cs_pp_res_labels();
	list( $top_slug, $top ) = cs_tl_top_cpu();
	$fa = $fb = array();
	foreach ( $labels as $res => $l ) {
		$fa[ $res ] = cs_tl_fps( $top, $a, $res );
		$fb[ $res ] = cs_tl_fps( $top, $b, $res );
	}
	$fast   = $a['score'] >= $b['score'];
	$w      = $fast ? $a : $b;
	$lose   = $fast ? $b : $a;
	$gap    = cs_pp_gap( $w['score'], $lose['score'] );
	// U1 (U-46): from the FPS as shown (rounded), so the % matches the table.
	$gap1440 = cs_pp_gap( round( max( $fa['1440p'][0], $fb['1440p'][0] ) ), round( min( $fa['1440p'][0], $fb['1440p'][0] ) ) );
	$feat_a = cs_pp_gpu_features( $a );
	$feat_b = cs_pp_gpu_features( $b );
	// U2 (U-45): where the faster card loses because its VRAM falls short.
	$vram_wins = array();
	foreach ( array( 'high' => __( 'high', 'computingskill' ), 'ultra' => __( 'ultra', 'computingskill' ) ) as $preset => $pl ) {
		foreach ( $labels as $res => $l ) {
			if ( cs_pp_vram_short( $w, $res, $preset ) && ! cs_pp_vram_short( $lose, $res, $preset )
				&& cs_pp_gap( round( cs_tl_fps( $top, $lose, $res, $preset )[0] ), round( cs_tl_fps( $top, $w, $res, $preset )[0] ) ) >= 3 ) {
				$vram_wins[] = $l . ' ' . $pl;
			}
		}
	}

	if ( $gap < 5 ) {
		$lead = sprintf( __( 'The %1$s and %2$s are about even for gaming in our index (scores %3$d and %4$d, within 5%%).', 'computingskill' ), $an, $bn, (int) $a['score'], (int) $b['score'] );
	} else {
		$lead = sprintf( __( 'The %1$s is the faster card: about %2$d%% ahead of the %3$s in our gaming index (score %4$d vs %5$d), or about %6$d%% more FPS at 1440p high.', 'computingskill' ), cs_pp_short( $w ), $gap, cs_pp_short( $lose ), (int) $w['score'], (int) $lose['score'], $gap1440 );
	}
	$extra = array();
	if ( $a['vram_gb'] !== $b['vram_gb'] ) {
		$more    = $a['vram_gb'] > $b['vram_gb'] ? $a : $b;
		$less    = $a['vram_gb'] > $b['vram_gb'] ? $b : $a;
		$extra[] = sprintf( __( 'The %1$s has more VRAM (%2$dGB vs %3$dGB).', 'computingskill' ), cs_pp_short( $more ), (int) $more['vram_gb'], (int) $less['vram_gb'] );
	}
	if ( abs( $a['tdp_w'] - $b['tdp_w'] ) >= 20 ) {
		$low     = $a['tdp_w'] < $b['tdp_w'] ? $a : $b;
		$high    = $a['tdp_w'] < $b['tdp_w'] ? $b : $a;
		$extra[] = sprintf( __( 'The %1$s draws less power (%2$dW vs %3$dW).', 'computingskill' ), cs_pp_short( $low ), (int) $low['tdp_w'], (int) $high['tdp_w'] );
	}
	if ( $vram_wins && $gap >= 5 ) {
		$extra[] = sprintf(
			/* translators: 1: slower card, 2: settings list, 3: faster card, 4: its VRAM */
			__( 'The %1$s still comes out ahead at %2$s, where the %3$s’s %4$dGB of VRAM falls short.', 'computingskill' ),
			cs_pp_short( $lose ),
			cs_bn_join( $vram_wins ),
			cs_pp_short( $w ),
			(int) $w['vram_gb']
		);
	}
	$html = cs_tl_quick_answer( esc_html( $lead . ' ' . implode( ' ', $extra ) ) );

	$html .= '<p>' . sprintf(
		/* translators: 1, 2: part links */
		esc_html__( 'A side-by-side look at the %1$s and the %2$s using the specs from each maker and ComputingSkill’s gaming scores, the same numbers our calculators and tier list use. Prices change too often to compare here, so check current prices against the performance gap below.', 'computingskill' ),
		cs_pp_part_link( $a_slug, $a, $a['name'] ),
		cs_pp_part_link( $b_slug, $b, $b['name'] )
	) . '</p>';

	$html .= '<h2>' . esc_html__( 'Specs Side by Side', 'computingskill' ) . '</h2>';
	$spec  = function ( $g, $f ) {
		return array(
			esc_html( $g['architecture'] ),
			esc_html( $g['vram_gb'] . 'GB ' . $g['memory_type'] ),
			esc_html( $g['memory_bus'] . '-bit' ),
			esc_html( $g['boost_clock_mhz'] . ' MHz' ),
			esc_html( number_format_i18n( $g['cores'] ) . ' ' . $g['core_label'] ),
			esc_html( $g['tdp_w'] . 'W' ),
			esc_html( $g['recommended_psu_w'] . 'W' ),
			esc_html( ucfirst( $f['text'] ) ),
			(int) $g['release_year'],
			(int) $g['score'] . ' (' . cs_pp_tier_badge( cs_pp_tier( 'gpu', $g ) ) . ')',
		);
	};
	$sa    = $spec( $a, $feat_a );
	$sb    = $spec( $b, $feat_b );
	$rows  = array();
	foreach ( array( __( 'Architecture', 'computingskill' ), __( 'VRAM', 'computingskill' ), __( 'Memory bus', 'computingskill' ), __( 'Boost clock', 'computingskill' ), __( 'Shaders', 'computingskill' ), __( 'Board power', 'computingskill' ), __( 'Maker’s recommended PSU', 'computingskill' ), __( 'Upscaling / frame generation', 'computingskill' ), __( 'Released', 'computingskill' ), __( 'Gaming score (tier)', 'computingskill' ) ) as $i => $h ) {
		$rows[] = array( esc_html( $h ), $sa[ $i ], $sb[ $i ] );
	}
	$html .= cs_pp_table( sprintf( __( '%1$s vs %2$s specifications', 'computingskill' ), $an, $bn ), array( '', $an, $bn ), $rows );
	$html .= cs_pp_source( array( $a, $b ) );
	if ( $a['brand'] !== $b['brand'] ) {
		$html .= '<p>' . esc_html__( 'Clock speeds and shader counts can’t be compared across brands, which is why the gaming score is the fairer measure.', 'computingskill' ) . '</p>';
	}

	$html .= '<h2>' . esc_html__( 'Gaming Performance', 'computingskill' ) . '</h2>';
	$html .= '<p>' . sprintf(
		esc_html__( 'Estimated average FPS in a typical AAA game, from the %2$s’s formula, with the %1$s so the cards are the limit. * marks a figure held back by the CPU. Ray tracing, upscaling and frame generation are not included.', 'computingskill' ),
		esc_html( cs_pp_short( $top ) ),
		cs_tl_link( 'fps-calculator', __( 'FPS Calculator', 'computingskill' ) )
	) . '</p>';
	$rows      = array();
	$vram_mark = false;
	foreach ( array( 'high' => __( 'high', 'computingskill' ), 'ultra' => __( 'ultra', 'computingskill' ) ) as $preset => $pl ) {
		foreach ( $labels as $res => $l ) {
			list( $x, $xl ) = cs_tl_fps( $top, $a, $res, $preset );
			list( $y, $yl ) = cs_tl_fps( $top, $b, $res, $preset );
			// U1 (U-46): the difference comes from the FPS shown (46 vs 45
			// read "+4%" when worked out from 46.2 vs 44.6).
			$x      = round( $x );
			$y      = round( $y );
			$d      = cs_pp_gap( $x, $y );
			$sx     = cs_pp_vram_short( $a, $res, $preset ) ? '†' : '';
			$sy     = cs_pp_vram_short( $b, $res, $preset ) ? '†' : '';
			$vram_mark = $vram_mark || $sx || $sy;
			$rows[] = array( esc_html( $l . ', ' . $pl ), (int) round( $x ) . ( 'cpu' === $xl ? '*' : '' ) . $sx, (int) round( $y ) . ( 'cpu' === $yl ? '*' : '' ) . $sy, esc_html( abs( $d ) < 3 ? __( 'about even', 'computingskill' ) : sprintf( __( '%1$s +%2$d%%', 'computingskill' ), $d > 0 ? $an : $bn, abs( $d > 0 ? $d : cs_pp_gap( $y, $x ) ) ) ) );
		}
	}
	$html .= cs_pp_table( sprintf( __( '%1$s vs %2$s estimated FPS', 'computingskill' ), $an, $bn ), array( __( 'Resolution, settings', 'computingskill' ), $an, $bn, __( 'Difference', 'computingskill' ) ), $rows );
	$html .= $vram_mark ? cs_pp_vram_note() : '';

	$html .= '<h2>' . esc_html__( 'VRAM', 'computingskill' ) . '</h2>';
	$html .= '<p>' . esc_html( $an . ': ' . cs_pp_vram_sentence( $a ) ) . '</p><p>' . esc_html( $bn . ': ' . cs_pp_vram_sentence( $b ) ) . '</p>';

	$html .= '<h2>' . esc_html__( 'With Your CPU', 'computingskill' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'A faster card only pays off when your CPU can feed it. Here is each card with common processors in our bottleneck engine (typical AAA game, high settings). “GPU-limited” is normal; a CPU % is how much of the card goes unused.', 'computingskill' ) . '</p>';
	$pairs = cs_bn_published_pairs();
	$rows  = array();
	foreach ( cs_aeo_pick( cs_get_cpu_data(), array( 'core-i3-12100f', 'ryzen-5-5600', 'core-i5-12400f', 'ryzen-5-7600', 'core-i5-14600k', 'ryzen-7-7800x3d' ) ) as $c_slug => $c ) {
		$ra  = cs_calculate_bottleneck( $c, $a );
		$rb  = cs_calculate_bottleneck( $c, $b );
		$row = array( cs_pp_part_link( $c_slug, $c ) );
		foreach ( array( array( $a_slug, $ra ), array( $b_slug, $rb ) ) as $x ) {
			$cell = esc_html( cs_bn_cell( $x[1]['1080p'] ) . ' / ' . cs_bn_cell( $x[1]['1440p'] ) );
			if ( isset( $pairs[ $c_slug . '|' . $x[0] ] ) ) {
				$cell = '<a href="' . esc_url( $pairs[ $c_slug . '|' . $x[0] ] ) . '">' . $cell . '</a>';
			}
			$row[] = $cell;
		}
		$rows[] = $row;
	}
	$html .= cs_pp_table( __( 'Bottleneck result at 1080p / 1440p', 'computingskill' ), array( __( 'CPU', 'computingskill' ), $an, $bn ), $rows );

	$html .= '<h2>' . esc_html__( 'Power and PSU', 'computingskill' ) . '</h2>';
	$mid   = cs_get_cpu_data()['ryzen-5-7600'] ?? cs_bn_top_cpu();
	$pa    = cs_aeo_psu( $mid, $a );
	$pb    = cs_aeo_psu( $mid, $b );
	$html .= '<p>' . sprintf(
		esc_html__( 'With a %1$s, the %2$s recommends %3$dW for a PC with the %4$s and %5$dW with the %6$s. The makers’ own recommendations are %7$dW and %8$dW.', 'computingskill' ),
		esc_html( cs_pp_short( $mid ) ),
		cs_tl_link( 'psu-calculator', __( 'PSU Calculator', 'computingskill' ) ),
		(int) $pa[1], esc_html( $an ), (int) $pb[1], esc_html( $bn ),
		(int) $a['recommended_psu_w'], (int) $b['recommended_psu_w']
	) . '</p>';

	$html .= '<h2>' . esc_html__( 'Which Should You Buy?', 'computingskill' ) . '</h2><ul>';
	if ( $gap >= 5 ) {
		$html .= '<li>' . esc_html( sprintf( __( 'Buy the %1$s for more performance: about %2$d%% higher in our index. It is worth it if the price difference is smaller than that.', 'computingskill' ), cs_pp_short( $w ), $gap ) ) . '</li>';
		$html .= '<li>' . esc_html( sprintf( __( 'The %s makes sense when it costs clearly less, or when its other strengths below matter more to you.', 'computingskill' ), cs_pp_short( $lose ) ) ) . '</li>';
	} else {
		$html .= '<li>' . esc_html__( 'Performance is close enough that price, VRAM and features should decide.', 'computingskill' ) . '</li>';
	}
	if ( $a['vram_gb'] !== $b['vram_gb'] ) {
		$html .= '<li>' . esc_html( sprintf( __( 'For 1440p, 4K or keeping the card for years, the %s’s extra VRAM is the safer choice.', 'computingskill' ), cs_pp_short( $a['vram_gb'] > $b['vram_gb'] ? $a : $b ) ) ) . '</li>';
	}
	if ( $feat_a['fg'] !== $feat_b['fg'] || $feat_a['upscaler'] !== $feat_b['upscaler'] ) {
		$html .= '<li>' . esc_html( sprintf( __( 'Features differ: the %1$s offers %2$s; the %3$s offers %4$s.', 'computingskill' ), $an, $feat_a['text'], $bn, $feat_b['text'] ) ) . '</li>';
	}
	$html .= '<li>' . sprintf( esc_html__( 'Not sure your CPU keeps up? Check your exact pair in the %s.', 'computingskill' ), cs_tl_link( 'pc-bottleneck-calculator', __( 'PC Bottleneck Calculator', 'computingskill' ) ) ) . '</li></ul>';

	$more = cs_pp_vs_links_for( $a_slug ) + cs_pp_vs_links_for( $b_slug );
	if ( $more ) {
		$html .= '<p>' . esc_html__( 'More comparisons:', 'computingskill' ) . ' ' . cs_pp_link_list( array_slice( $more, 0, 6, true ) ) . '. ' . sprintf( esc_html__( 'Every card ranked: %s.', 'computingskill' ), cs_tl_link( 'gpu-tier-list', __( 'GPU tier list', 'computingskill' ) ) ) . '</p>';
	}

	$html .= cs_tl_faq( array(
		sprintf( __( 'Is the %1$s better than the %2$s?', 'computingskill' ), $an, $bn ) => $gap < 5 ? sprintf( __( 'They are about even for gaming in our index (%1$d vs %2$d). Pick on price, VRAM and features.', 'computingskill' ), (int) $a['score'], (int) $b['score'] ) : sprintf( __( 'For raw gaming performance the %1$s is about %2$d%% faster (score %3$d vs %4$d).', 'computingskill' ), cs_pp_short( $w ), $gap, (int) $w['score'], (int) $lose['score'] ),
		sprintf( __( 'Which is better for 1440p, the %1$s or the %2$s?', 'computingskill' ), $an, $bn ) => sprintf( __( 'At 1440p high the %1$s averages about %2$d FPS and the %3$s about %4$d FPS in a typical AAA game with a fast CPU. %5$s', 'computingskill' ), $an, round( $fa['1440p'][0] ), $bn, round( $fb['1440p'][0] ), cs_pp_vram_fit( $a )['1440p'] !== cs_pp_vram_fit( $b )['1440p'] ? sprintf( __( 'The %s has more VRAM headroom there.', 'computingskill' ), cs_pp_short( $a['vram_gb'] > $b['vram_gb'] ? $a : $b ) ) : '' ),
		sprintf( __( 'Which uses less power?', 'computingskill' ) ) => $a['tdp_w'] === $b['tdp_w'] ? sprintf( __( 'Both are rated at %dW board power.', 'computingskill' ), (int) $a['tdp_w'] ) : sprintf( __( 'The %1$s (%2$dW board power vs %3$dW).', 'computingskill' ), cs_pp_short( $a['tdp_w'] < $b['tdp_w'] ? $a : $b ), (int) min( $a['tdp_w'], $b['tdp_w'] ), (int) max( $a['tdp_w'], $b['tdp_w'] ) ),
	) );
	return $html;
}

function cs_pp_vs_cpu_html( $a_slug, $a, $b_slug, $b ) {
	$an     = cs_pp_short( $a );
	$bn     = cs_pp_short( $b );
	$labels = cs_pp_res_labels();
	$ca     = cs_pp_cpu_ceilings( $a );
	$cb     = cs_pp_cpu_ceilings( $b );
	$fast   = $a['score'] >= $b['score'];
	$w      = $fast ? $a : $b;
	$lose   = $fast ? $b : $a;
	$gap    = cs_pp_gap( $w['score'], $lose['score'] );
	$same   = $a['socket'] === $b['socket'];
	$pa     = cs_aeo_cpu_power( $a );
	$pb     = cs_aeo_cpu_power( $b );

	$lead = $gap < 5
		? sprintf( __( 'The %1$s and %2$s are about even for gaming in our index (scores %3$d and %4$d, within 5%%).', 'computingskill' ), $an, $bn, (int) $a['score'], (int) $b['score'] )
		: sprintf( __( 'The %1$s is the faster gaming CPU: its FPS ceiling is about %2$d%% higher than the %3$s’s (score %4$d vs %5$d).', 'computingskill' ), cs_pp_short( $w ), $gap, cs_pp_short( $lose ), (int) $w['score'], (int) $lose['score'] );
	$lead .= ' ' . ( $same
		? sprintf( __( 'Both use the %s socket, so they fit the same motherboards.', 'computingskill' ), $a['socket'] )
		: sprintf( __( 'They use different platforms (%1$s vs %2$s), so the motherboard and memory differ too.', 'computingskill' ), $a['socket'], $b['socket'] ) );
	$lead .= ' ' . __( 'With a mid-range graphics card at 1440p or 4K, the gap mostly disappears because the card sets the frame rate.', 'computingskill' );
	$html  = cs_tl_quick_answer( esc_html( $lead ) );

	$html .= '<p>' . sprintf(
		esc_html__( 'A side-by-side look at the %1$s and the %2$s using each maker’s specs and ComputingSkill’s gaming scores, the same numbers our calculators and tier list use. Prices change too often to compare here.', 'computingskill' ),
		cs_pp_part_link( $a_slug, $a, $a['name'] ),
		cs_pp_part_link( $b_slug, $b, $b['name'] )
	) . '</p>';

	$html .= '<h2>' . esc_html__( 'Specs Side by Side', 'computingskill' ) . '</h2>';
	$spec  = function ( $c, $p ) {
		return array(
			esc_html( isset( $c['generation'] ) ? $c['generation'] : '' ),
			(int) $c['cores'] . ' / ' . (int) $c['threads'],
			esc_html( $c['base_clock_ghz'] . ' / ' . $c['boost_clock_ghz'] . ' GHz' ),
			esc_html( $c['tdp_w'] . 'W / ' . (int) $p['max'] . 'W' ),
			esc_html( $c['socket'] ),
			esc_html( cs_bn_socket_memory( $c['socket'] ) ),
			(int) $c['release_year'],
			(int) $c['score'] . ' (' . cs_pp_tier_badge( cs_pp_tier( 'cpu', $c ) ) . ')',
		);
	};
	$sa    = $spec( $a, $pa );
	$sb    = $spec( $b, $pb );
	$rows  = array();
	foreach ( array( __( 'Generation', 'computingskill' ), __( 'Cores / threads', 'computingskill' ), __( 'Base / boost clock', 'computingskill' ), __( 'TDP / max power', 'computingskill' ), __( 'Socket', 'computingskill' ), __( 'Memory', 'computingskill' ), __( 'Released', 'computingskill' ), __( 'Gaming score (tier)', 'computingskill' ) ) as $i => $h ) {
		$rows[] = array( esc_html( $h ), $sa[ $i ], $sb[ $i ] );
	}
	$html .= cs_pp_table( sprintf( __( '%1$s vs %2$s specifications', 'computingskill' ), $an, $bn ), array( '', $an, $bn ), $rows );
	$html .= cs_pp_source( array( $a, $b ) );

	$html .= '<h2>' . esc_html__( 'Gaming Performance', 'computingskill' ) . '</h2>';
	$html .= '<p>' . sprintf( esc_html__( 'The FPS ceiling is the frame rate each CPU can prepare when the graphics card isn’t the limit (%s formula).', 'computingskill' ), cs_tl_link( 'fps-calculator', __( 'FPS Calculator', 'computingskill' ) ) ) . '</p>';
	$rows = array();
	foreach ( array( 'aaa' => __( 'Typical AAA game', 'computingskill' ), 'esports' => __( 'Esports titles', 'computingskill' ), 'cpu-heavy' => __( 'CPU-heavy games', 'computingskill' ) ) as $k => $t ) {
		$d      = cs_pp_gap( $ca[ $k ], $cb[ $k ] );
		$rows[] = array( esc_html( $t ), $ca[ $k ], $cb[ $k ], esc_html( abs( $d ) < 3 ? __( 'about even', 'computingskill' ) : sprintf( __( '%1$s +%2$d%%', 'computingskill' ), $d > 0 ? $an : $bn, abs( $d > 0 ? $d : cs_pp_gap( $cb[ $k ], $ca[ $k ] ) ) ) ) );
	}
	$html .= cs_pp_table( sprintf( __( '%1$s vs %2$s FPS ceiling', 'computingskill' ), $an, $bn ), array( __( 'Game type', 'computingskill' ), $an, $bn, __( 'Difference', 'computingskill' ) ), $rows );

	$html .= '<h2>' . esc_html__( 'Which Graphics Card Each One Can Drive', 'computingskill' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'The fastest card in our list each CPU runs without a noticeable CPU bottleneck (under 15%, typical AAA game at high settings).', 'computingskill' ) . '</p>';
	$rows  = array();
	foreach ( $labels as $res => $lab ) {
		list( $gs_a, $ga ) = cs_tl_cpu_max_gpu( $a, $res );
		list( $gs_b, $gb ) = cs_tl_cpu_max_gpu( $b, $res );
		$rows[] = array( esc_html( $lab ), $ga ? cs_pp_part_link( $gs_a, $ga ) : '—', $gb ? cs_pp_part_link( $gs_b, $gb ) : '—' );
	}
	$html .= cs_pp_table( __( 'Fastest GPU without a noticeable bottleneck', 'computingskill' ), array( __( 'Resolution', 'computingskill' ), $an, $bn ), $rows );

	$pairs = cs_bn_published_pairs();
	$rows  = array();
	foreach ( cs_aeo_pick( cs_get_gpu_data(), array( 'rtx-4060', 'rx-9060-xt', 'rtx-5070', 'rx-9070-xt', 'rtx-5080' ) ) as $g_slug => $g ) {
		$row = array( cs_pp_part_link( $g_slug, $g ) );
		foreach ( array( array( $a_slug, $a ), array( $b_slug, $b ) ) as $x ) {
			$r    = cs_calculate_bottleneck( $x[1], $g );
			$cell = esc_html( cs_bn_cell( $r['1080p'] ) . ' / ' . cs_bn_cell( $r['1440p'] ) );
			if ( isset( $pairs[ $x[0] . '|' . $g_slug ] ) ) {
				$cell = '<a href="' . esc_url( $pairs[ $x[0] . '|' . $g_slug ] ) . '">' . $cell . '</a>';
			}
			$row[] = $cell;
		}
		$rows[] = $row;
	}
	$html .= cs_pp_table( __( 'Bottleneck result at 1080p / 1440p with popular cards', 'computingskill' ), array( __( 'Graphics card', 'computingskill' ), $an, $bn ), $rows );

	$html .= '<h2>' . esc_html__( 'Platform, Power and Upgrades', 'computingskill' ) . '</h2><ul>';
	foreach ( array( array( $a_slug, $a, $pa ), array( $b_slug, $b, $pb ) ) as $x ) {
		$up    = cs_bn_socket_upgrade( $x[0], $x[1] );
		$html .= '<li><strong>' . esc_html( cs_pp_short( $x[1] ) ) . ':</strong> ' . esc_html( sprintf(
			/* translators: 1: socket, 2: memory, 3: W, 4: upgrade sentence */
			__( '%1$s, %2$s, up to about %3$dW. %4$s', 'computingskill' ),
			$x[1]['socket'],
			cs_pp_lc( cs_bn_socket_memory( $x[1]['socket'] ) ),
			(int) $x[2]['max'],
			$up ? sprintf( __( 'Upgrade path on the same board: up to the %s.', 'computingskill' ), cs_pp_short( $up ) ) : __( 'Already the fastest gaming CPU in our list for this socket.', 'computingskill' )
		) ) . '</li>';
	}
	$html .= '</ul>';

	$html .= '<h2>' . esc_html__( 'Which Should You Buy?', 'computingskill' ) . '</h2><ul>';
	if ( $gap >= 5 ) {
		$html .= '<li>' . esc_html( sprintf( __( 'For high-refresh gaming, esports or a fast graphics card, the %1$s is about %2$d%% quicker.', 'computingskill' ), cs_pp_short( $w ), $gap ) ) . '</li>';
		$html .= '<li>' . esc_html( sprintf( __( 'With a mid-range card at 1440p or 4K the %s gives up little, so it makes sense when it costs clearly less.', 'computingskill' ), cs_pp_short( $lose ) ) ) . '</li>';
	} else {
		$html .= '<li>' . esc_html__( 'Gaming performance is close, so platform cost, the upgrade path and price should decide.', 'computingskill' ) . '</li>';
	}
	if ( ! $same ) {
		$html .= '<li>' . esc_html__( 'Count the motherboard and RAM in the price: a new socket can mean a new board and, for DDR5-only platforms, new memory.', 'computingskill' ) . '</li>';
	}
	if ( $a['cores'] !== $b['cores'] ) {
		$more  = $a['cores'] > $b['cores'] ? $a : $b;
		$html .= '<li>' . esc_html( sprintf( __( 'For streaming, video editing or other multi-threaded work, the %1$s’s %2$d cores help more than our gaming score shows.', 'computingskill' ), cs_pp_short( $more ), (int) $more['cores'] ) ) . '</li>';
	}
	$html .= '</ul>';

	$more = cs_pp_vs_links_for( $a_slug ) + cs_pp_vs_links_for( $b_slug );
	if ( $more ) {
		$html .= '<p>' . esc_html__( 'More comparisons:', 'computingskill' ) . ' ' . cs_pp_link_list( array_slice( $more, 0, 6, true ) ) . '. ' . sprintf( esc_html__( 'Every processor ranked: %s.', 'computingskill' ), cs_tl_link( 'cpu-tier-list', __( 'CPU tier list', 'computingskill' ) ) ) . '</p>';
	}

	$html .= cs_tl_faq( array(
		sprintf( __( 'Is the %1$s better than the %2$s for gaming?', 'computingskill' ), $an, $bn ) => $gap < 5 ? sprintf( __( 'They are about even in our gaming index (%1$d vs %2$d).', 'computingskill' ), (int) $a['score'], (int) $b['score'] ) : sprintf( $fast ? __( 'Yes. The %1$s’s gaming FPS ceiling is about %2$d%% higher (score %3$d vs %4$d).', 'computingskill' ) : __( 'No. The %1$s is the faster gaming CPU, with an FPS ceiling about %2$d%% higher (score %3$d vs %4$d).', 'computingskill' ), cs_pp_short( $w ), $gap, (int) $w['score'], (int) $lose['score'] ),
		sprintf( __( 'Do the %1$s and %2$s use the same motherboard?', 'computingskill' ), $an, $bn ) => $same ? sprintf( __( 'Yes, both are %s. A BIOS update may be needed for the newer chip.', 'computingskill' ), $a['socket'] ) : sprintf( __( 'No. The %1$s is %2$s and the %3$s is %4$s.', 'computingskill' ), $an, $a['socket'], $bn, $b['socket'] ),
		sprintf( __( 'Which uses less power, the %1$s or the %2$s?', 'computingskill' ), $an, $bn ) => $pa['max'] == $pb['max'] ? sprintf( __( 'Both peak at about %dW.', 'computingskill' ), (int) $pa['max'] ) : sprintf( __( 'The %1$s (up to about %2$dW vs %3$dW).', 'computingskill' ), cs_pp_short( $pa['max'] < $pb['max'] ? $a : $b ), (int) min( $pa['max'], $pb['max'] ), (int) max( $pa['max'], $pb['max'] ) ),
	) );
	return $html;
}

/* ------------------------------------------------------------------ */
/* Hubs, blog index, sitemap                                           */
/* ------------------------------------------------------------------ */

/**
 * Directory of every part page on /hardware/ and every comparison on
 * /comparisons/, so each generated page is linked from its hub.
 *
 * U4 (U-22, 20 Oct 2026): the lists came after the FAQ, and the 56
 * head-to-heads were one unsorted list. They now sit above "Which One Do
 * You Need?" and the FAQ (cs_pp_hub_insert()); comparisons are grouped by
 * the class of the faster part (the tier list lines), fastest first, under
 * a tier-list box.
 */
function cs_pp_hub_html( $content ) {
	if ( ! is_singular( 'page' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	$slug = get_post_field( 'post_name' );
	if ( 'hardware' !== $slug && 'comparisons' !== $slug ) {
		return $content;
	}
	$urls = cs_pp_urls();
	if ( 'hardware' === $slug ) {
		$html = '<h2 id="every-part">' . esc_html__( 'Every CPU and GPU We Cover', 'computingskill' ) . '</h2><p>' . esc_html__( 'One page per part, with specs, estimated FPS, the parts it pairs with and the PSU it needs. Fastest first.', 'computingskill' ) . '</p>';
		foreach ( array( 'gpu' => __( 'Graphics cards', 'computingskill' ), 'cpu' => __( 'Processors', 'computingskill' ) ) as $type => $label ) {
			$by = array();
			foreach ( cs_tl_sorted( cs_pp_data( $type ) ) as $p_slug => $p ) {
				if ( isset( $urls[ 'cs_hardware|' . $p_slug ] ) ) {
					$by[ $p['brand'] ][] = '<a href="' . esc_url( $urls[ 'cs_hardware|' . $p_slug ] ) . '">' . esc_html( cs_pp_short( $p ) ) . '</a>';
				}
			}
			if ( ! $by ) {
				continue;
			}
			$html .= '<h3 id="part-' . esc_attr( $type ) . '">' . esc_html( $label ) . '</h3><ul class="cs-pp-hub">';
			foreach ( $by as $brand => $links ) {
				$html .= '<li><strong>' . esc_html( $brand ) . ':</strong> ' . implode( ', ', $links ) . '</li>';
			}
			$html .= '</ul>';
		}
		return cs_pp_hub_insert( $content, $html );
	}
	if ( 'comparisons' === $slug ) {
		$classes = array(
			'gpu' => array(
				'4k'    => __( '4K cards', 'computingskill' ),
				'1440p' => __( '1440p cards', 'computingskill' ),
				'1080p' => __( '1080p and budget cards', 'computingskill' ),
			),
			'cpu' => array(
				'high'  => __( 'High-end CPUs', 'computingskill' ),
				'upper' => __( 'Upper mid-range CPUs', 'computingskill' ),
				'mid'   => __( 'Mid-range CPUs', 'computingskill' ),
				'low'   => __( 'Budget CPUs', 'computingskill' ),
			),
		);
		$gpu_t  = cs_tl_gpu_targets();
		$groups = array();
		foreach ( cs_pp_vs_pairs() as $p ) {
			list( $type, $a, $b ) = $p;
			$data = cs_pp_data( $type );
			$key  = 'cs_comparison|' . cs_pp_vs_slug( $a, $b );
			if ( ! isset( $urls[ $key ], $data[ $a ], $data[ $b ] ) ) {
				continue;
			}
			$top = max( $data[ $a ]['score'], $data[ $b ]['score'] );
			if ( 'gpu' === $type ) {
				$class = $top >= $gpu_t['4k'] ? '4k' : ( $top >= $gpu_t['1440p'] ? '1440p' : '1080p' );
			} else {
				$class = $top >= 80 ? 'high' : ( $top >= 70 ? 'upper' : ( $top >= 60 ? 'mid' : 'low' ) );
			}
			$groups[ $type ][ $class ][] = array( $top, min( $data[ $a ]['score'], $data[ $b ]['score'] ), '<li><a href="' . esc_url( $urls[ $key ] ) . '">' . esc_html( cs_pp_short( $data[ $a ] ) . ' vs ' . cs_pp_short( $data[ $b ] ) ) . '</a></li>' );
		}
		if ( ! $groups ) {
			return $content;
		}
		$html  = '<div class="cs-related" id="rankings"><h2>' . esc_html__( 'Tier Lists: Every Card and CPU Ranked', 'computingskill' ) . '</h2><ul class="cs-related-grid">';
		$html .= '<li><a href="' . esc_url( home_url( '/gpu-tier-list/' ) ) . '">' . esc_html__( 'GPU Tier List', 'computingskill' ) . '</a></li>';
		$html .= '<li><a href="' . esc_url( home_url( '/cpu-tier-list/' ) ) . '">' . esc_html__( 'CPU Tier List', 'computingskill' ) . '</a></li>';
		$html .= '<li><a href="' . esc_url( home_url( '/bottleneck/' ) ) . '">' . esc_html__( 'CPU + GPU Bottleneck Comparisons', 'computingskill' ) . '</a></li>';
		$html .= '</ul></div>';
		$html .= '<h2 id="head-to-head">' . esc_html__( 'Head-to-Head: CPU and GPU Comparisons', 'computingskill' ) . '</h2><p>' . esc_html__( 'Two parts side by side, with specs, estimated FPS, power and which one fits your build. Grouped by the class of the faster part, as in the tier lists, fastest first.', 'computingskill' ) . '</p>';
		foreach ( array( 'gpu' => __( 'Graphics cards', 'computingskill' ), 'cpu' => __( 'Processors', 'computingskill' ) ) as $type => $label ) {
			if ( empty( $groups[ $type ] ) ) {
				continue;
			}
			$html .= '<h3 id="vs-' . esc_attr( $type ) . '">' . esc_html( $label ) . '</h3>';
			foreach ( $classes[ $type ] as $class => $class_label ) {
				if ( empty( $groups[ $type ][ $class ] ) ) {
					continue;
				}
				$rows = $groups[ $type ][ $class ];
				usort( $rows, function ( $x, $y ) {
					return $y[0] - $x[0] ?: $y[1] - $x[1];
				} );
				$html .= '<h4>' . esc_html( $class_label ) . '</h4><ul class="cs-pp-hub cs-pp-cols">' . implode( '', array_column( $rows, 2 ) ) . '</ul>';
			}
		}
		return cs_pp_hub_insert( $content, $html );
	}
	return $content;
}

/**
 * Puts a hub list before "Which One Do You Need?", else before the FAQ,
 * else at the end.
 */
function cs_pp_hub_insert( $content, $html ) {
	foreach ( array( '#<h2[^>]*>\s*Which One Do You Need\?\s*</h2>#u', '#<div class="cs-faq"#' ) as $pattern ) {
		if ( preg_match( $pattern, $content, $m, PREG_OFFSET_CAPTURE ) ) {
			return substr( $content, 0, $m[0][1] ) . $html . "\n" . substr( $content, $m[0][1] );
		}
	}
	return $content . $html;
}
add_filter( 'the_content', 'cs_pp_hub_html', 14 );

function cs_pp_sitemap_types( $post_types ) {
	foreach ( array( 'cs_hardware', 'cs_comparison' ) as $pt ) {
		if ( post_type_exists( $pt ) ) {
			$post_types[ $pt ] = $pt;
		}
	}
	return $post_types;
}
add_filter( 'rank_math/sitemap/post_types', 'cs_pp_sitemap_types', 25 );

function cs_pp_sitemap_not_excluded( $is_excluded, $post_type ) {
	return in_array( $post_type, array( 'cs_hardware', 'cs_comparison' ), true ) ? false : $is_excluded;
}
add_filter( 'rank_math/sitemap/exclude_post_type', 'cs_pp_sitemap_not_excluded', 10, 2 );

/**
 * Rank Math meta description for the generated pages: the excerpt.
 */
function cs_pp_meta_description( $desc ) {
	if ( is_singular( array( 'cs_hardware', 'cs_comparison' ) ) && get_post_meta( get_the_ID(), '_cs_generated', true ) ) {
		return wp_strip_all_tags( get_the_excerpt() );
	}
	return $desc;
}
add_filter( 'rank_math/frontend/description', 'cs_pp_meta_description', 20 );
