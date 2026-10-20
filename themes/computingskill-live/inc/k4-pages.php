<?php
/**
 * K4 (GSC keyword gap, 20 Oct 2026): "rtx 5060 ti 8gb vs 16gb" and
 * "rx 9060 xt 8gb vs 16gb" on /how-much-vram-do-i-need/.
 *
 * - H2 "8GB vs 16GB: RTX 5060 Ti and RX 9060 XT" with the two cards'
 *   memory specs and an estimated-FPS table for both memory sizes.
 * - Two FAQ items (printed and marked up by inc/extra-faq.php).
 *
 * The 8GB models use the same GPU chip and 128-bit bus as the 16GB models
 * in data/hardware-data.php (NVIDIA RTX 5060 family page and AMD's RX 9060
 * XT 8GB / 16GB pages, checked 20 Oct 2026), so the estimate takes the
 * 16GB card's data and changes only vram_gb. Every FPS figure comes from
 * cs_tl_fps() with the fastest CPU in the database, the same engine as the
 * FPS Calculator and tier lists, including its VRAM penalty (-5% per GB
 * short of the GPU VRAM Calculator's target, floor 0.75).
 *
 * Other K4 work lives in data: two pairs in cs_pp_vs_pairs()
 * (inc/part-pages.php), one combo in cs_get_bottleneck_pairs() and four
 * draft posts in data/blog-drafts/ (06-08, 02 rebuilt).
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The two cards sold in 8GB and 16GB versions: [ data slug, label,
 * memory line, power line, both from the maker's own spec pages ].
 */
function cs_k4_vram_cards() {
	return array(
		'rtx-5060-ti' => array(
			'label' => __( 'RTX 5060 Ti', 'computingskill' ),
			'specs' => __( 'GeForce RTX 5060 Ti: 4,608 CUDA cores, GDDR7 on a 128-bit bus, 180W and 600W required system power for both the 8GB and 16GB models (NVIDIA lists them in one column).', 'computingskill' ),
			'url'   => 'https://www.nvidia.com/en-us/geforce/graphics-cards/50-series/rtx-5060-family/',
		),
		'rx-9060-xt'  => array(
			'label' => __( 'RX 9060 XT', 'computingskill' ),
			'specs' => __( 'Radeon RX 9060 XT: 32 compute units, GDDR6 at up to 20 Gbps on a 128-bit bus (320 GB/s) for both; 150W typical board power for the 8GB model and 160W for the 16GB, with a 450W minimum PSU for either.', 'computingskill' ),
			'url'   => 'https://www.amd.com/en/products/graphics/desktops/radeon/9000-series/amd-radeon-rx-9060xt-8gb.html',
		),
	);
}

/**
 * Estimated FPS for a card at a given VRAM size: typical AAA game, fastest
 * CPU in the database, so only the card (and its memory) limits it.
 */
function cs_k4_fps( $slug, $vram, $res, $preset ) {
	$gpus = cs_get_gpu_data();
	if ( ! isset( $gpus[ $slug ] ) || ! function_exists( 'cs_tl_fps' ) ) {
		return 0;
	}
	$gpu            = $gpus[ $slug ];
	$gpu['vram_gb'] = $vram;
	return (int) round( cs_tl_fps( cs_tl_top_cpu()[1], $gpu, $res, $preset )[0] );
}

/** "9" or "9-10": how far the 8GB models trail, across both cards. */
function cs_k4_loss_range( $res, $preset ) {
	$l = array();
	foreach ( array_keys( cs_k4_vram_cards() ) as $slug ) {
		$l[] = cs_k4_loss( $slug, $res, $preset );
	}
	return min( $l ) === max( $l ) ? (string) min( $l ) : min( $l ) . '-' . max( $l );
}

/** Percent the 8GB model trails the 16GB model by (0 when level). */
function cs_k4_loss( $slug, $res, $preset ) {
	$big = cs_k4_fps( $slug, 16, $res, $preset );
	return $big ? (int) round( ( 1 - cs_k4_fps( $slug, 8, $res, $preset ) / $big ) * 100 ) : 0;
}

function cs_k4_vram_section() {
	if ( ! function_exists( 'cs_k1_table' ) || ! function_exists( 'cs_tl_fps' ) || ! function_exists( 'cs_bottleneck_vram_need' ) ) {
		return '';
	}
	$need  = cs_bottleneck_vram_need();
	$cards = cs_k4_vram_cards();
	$cases = array(
		array( '1080p', 'high', __( '1080p high', 'computingskill' ) ),
		array( '1080p', 'ultra', __( '1080p ultra', 'computingskill' ) ),
		array( '1440p', 'high', __( '1440p high', 'computingskill' ) ),
		array( '1440p', 'ultra', __( '1440p ultra', 'computingskill' ) ),
		array( '4k', 'high', __( '4K high', 'computingskill' ) ),
		array( '4k', 'ultra', __( '4K ultra', 'computingskill' ) ),
	);

	$html  = '<h2 id="8gb-vs-16gb">' . esc_html__( '8GB vs 16GB: RTX 5060 Ti and RX 9060 XT', 'computingskill' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'Two current cards are sold in both sizes, and in each case the 8GB and 16GB models use the same graphics chip and the same 128-bit memory bus. The only real difference is how much fits in memory before the card has to fall back on much slower system RAM:', 'computingskill' ) . '</p><ul>';
	foreach ( $cards as $card ) {
		$html .= '<li>' . esc_html( $card['specs'] ) . ' <a href="' . esc_url( $card['url'] ) . '" rel="noopener" target="_blank">' . esc_html__( 'Specifications', 'computingskill' ) . '</a></li>';
	}
	$html .= '</ul>';

	$rows = array();
	foreach ( $cases as $c ) {
		$row = array( $c[2], $need[ $c[0] ][ $c[1] ] . 'GB' );
		foreach ( array_keys( $cards ) as $slug ) {
			$row[] = (string) cs_k4_fps( $slug, 8, $c[0], $c[1] );
			$row[] = (string) cs_k4_fps( $slug, 16, $c[0], $c[1] );
		}
		$rows[] = $row;
	}
	$html .= '<p>' . esc_html__( 'Estimated FPS in a typical AAA game, without ray tracing, with a CPU fast enough that only the graphics card limits it. "VRAM target" is our GPU VRAM Calculator\'s minimum for that resolution and preset; in our model a card short of it loses about 5% per missing GB.', 'computingskill' ) . '</p>';
	$html .= cs_k1_table(
		__( 'RTX 5060 Ti and RX 9060 XT: 8GB vs 16GB, estimated FPS', 'computingskill' ),
		array( __( 'Resolution and preset', 'computingskill' ), __( 'VRAM target', 'computingskill' ), __( 'RTX 5060 Ti 8GB', 'computingskill' ), __( 'RTX 5060 Ti 16GB', 'computingskill' ), __( 'RX 9060 XT 8GB', 'computingskill' ), __( 'RX 9060 XT 16GB', 'computingskill' ) ),
		$rows
	);

	$html .= '<p>' . esc_html( sprintf(
		/* translators: 1: 1440p ultra loss %, 2: 4K high loss %, 3: 4K ultra loss % */
		__( 'At 1080p and at 1440p high, 8GB covers the target and the two versions are level. At 1440p ultra the 8GB models fall about %1$s%% behind, and at 4K about %2$s%% (high) to %3$s%% (ultra). Average FPS is only part of it: when a game runs out of VRAM, the first signs are usually stutter, textures loading late or the game lowering texture quality on its own. Ray tracing and frame generation add to VRAM use, so they push an 8GB card past its limit sooner.', 'computingskill' ),
		cs_k4_loss_range( '1440p', 'ultra' ),
		cs_k4_loss_range( '4k', 'high' ),
		cs_k4_loss_range( '4k', 'ultra' )
	) ) . '</p>';
	$html .= '<p>' . wp_kses_post( sprintf(
		/* translators: 1: comparison link, 2: VRAM calculator link, 3: RTX 5060 vs 5060 Ti link */
		__( '<strong>Which to buy:</strong> the 8GB version is enough for 1080p and for esports games at any resolution. Pick 16GB if you play at 1440p with ultra textures or ray tracing, or plan to keep the card for several years. To choose between the two cards themselves, see %1$s; to check a specific game and resolution, use the %2$s. Moving up from an RTX 5060 (8GB only)? See %3$s.', 'computingskill' ),
		'<a href="' . esc_url( home_url( '/comparisons/rtx-5060-ti-vs-rx-9060-xt/' ) ) . '">' . esc_html__( 'RTX 5060 Ti vs RX 9060 XT', 'computingskill' ) . '</a>',
		cs_k1_link( 'gpu-vram-calculator', __( 'GPU VRAM Calculator', 'computingskill' ) ),
		'<a href="' . esc_url( home_url( '/comparisons/rtx-5060-vs-rtx-5060-ti/' ) ) . '">' . esc_html__( 'RTX 5060 vs RTX 5060 Ti', 'computingskill' ) . '</a>'
	) ) . '</p>';
	return $html;
}

/**
 * Extra FAQ items (merged into cs_extra_faq_definitions()).
 */
function cs_k4_extra_faqs() {
	if ( ! function_exists( 'cs_tl_fps' ) || ! function_exists( 'cs_get_gpu_data' ) ) {
		return array();
	}
	return array(
		array(
			'slugs' => array( 'how-much-vram-do-i-need' ),
			'q'     => __( 'Should I buy the RTX 5060 Ti 8GB or 16GB?', 'computingskill' ),
			'a'     => sprintf(
				/* translators: 1: 1440p ultra loss %, 2: 4K high loss % */
				__( 'Both use the same chip, so at 1080p and 1440p high they perform the same in our estimates. The 16GB model pulls ahead once a game needs more than 8GB: about %1$s%% at 1440p ultra and %2$s%% at 4K high, and more with ray tracing, where an 8GB card also tends to stutter. For 1080p the 8GB model is enough; for 1440p or keeping the card for years, get 16GB.', 'computingskill' ),
				cs_k4_loss( 'rtx-5060-ti', '1440p', 'ultra' ),
				cs_k4_loss( 'rtx-5060-ti', '4k', 'high' )
			),
		),
		array(
			'slugs' => array( 'how-much-vram-do-i-need' ),
			'q'     => __( 'Is the RX 9060 XT 8GB enough?', 'computingskill' ),
			'a'     => sprintf(
				/* translators: %s: 1440p ultra loss % */
				__( 'For 1080p, yes: it uses the same 32 compute units and 128-bit GDDR6 memory as the 16GB model and matches it at 1080p and 1440p high in our estimates. At 1440p ultra it falls about %s%% behind, and further at 4K or with ray tracing, so for 1440p gaming the 16GB model is the safer buy.', 'computingskill' ),
				cs_k4_loss( 'rx-9060-xt', '1440p', 'ultra' )
			),
		),
	);
}

/**
 * Inserts the section before "Does Ray Tracing Really Need…" (after "Real
 * GPU VRAM Examples"). Falls back to before "Key Takeaways", "Related
 * Calculators & Guides", then the end. Priority 20, like K1-K3.
 */
function cs_k4_page_sections( $content ) {
	if ( ! is_singular( 'page' ) || ! in_the_loop() || ! is_main_query() || 'how-much-vram-do-i-need' !== get_post_field( 'post_name' ) || false !== strpos( $content, 'cs-k4-' ) ) {
		return $content;
	}
	$html = cs_k4_vram_section();
	if ( ! $html ) {
		return $content;
	}
	$html = '<div class="cs-k1-section cs-k4-section">' . $html . '</div>';
	if ( preg_match( '#<h2[^>]*>\s*(Does Ray Tracing|Key Takeaways|Related Calculators|Frequently Asked)#', $content, $m, PREG_OFFSET_CAPTURE ) ) {
		return substr_replace( $content, $html, $m[0][1], 0 );
	}
	return $content . $html;
}
add_filter( 'the_content', 'cs_k4_page_sections', 20 );
