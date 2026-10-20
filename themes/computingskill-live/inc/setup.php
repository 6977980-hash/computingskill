<?php
/**
 * Core theme setup: supports, menus, enqueues.
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Theme setup.
 */
function cs_theme_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'custom-logo', array(
		'height'      => 60,
		'width'       => 220,
		'flex-height' => true,
		'flex-width'  => true,
	) );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'script', 'style' ) );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );

	register_nav_menus( array(
		'primary' => __( 'Primary Menu', 'computingskill' ),
		'footer'  => __( 'Footer Menu', 'computingskill' ),
	) );

	load_theme_textdomain( 'computingskill', get_template_directory() . '/languages' );
}
add_action( 'after_setup_theme', 'cs_theme_setup' );

/**
 * Header logo (Oct 2026 audit, FIX-06). The uploaded logo is 1819×497 and
 * WordPress gave it sizes="(max-width: 1819px) 100vw, 1819px", so phones
 * fetched a large file and the logo filled the whole header row (the CSS
 * cap is in style.css, .cs-branding .custom-logo). This tells the browser
 * the real slot width so it picks a small srcset file, and keeps LiteSpeed
 * from lazy-loading it, since it's always above the fold.
 */
function cs_custom_logo_attributes( $attr ) {
	$attr['sizes']        = '190px';
	$attr['data-no-lazy'] = '1';
	return $attr;
}
add_filter( 'get_custom_logo_image_attributes', 'cs_custom_logo_attributes' );

/**
 * Enqueue styles and scripts.
 *
 * IMPORTANT: calculator JS is only enqueued on pages that actually contain
 * a calculator mount point, decided in inc/shortcodes.php via a global
 * flag set while the_content() renders the [cs_calculator] shortcode.
 * The stylesheet and small main.js (nav, search toggle) load site-wide.
 */
function cs_scripts() {
	$theme_version = wp_get_theme()->get( 'Version' );

	wp_enqueue_style( 'computingskill-style', get_template_directory_uri() . '/assets/css/style.css', array(), $theme_version );

	wp_enqueue_script( 'computingskill-main', get_template_directory_uri() . '/assets/js/main.js', array(), $theme_version, true );
}
add_action( 'wp_enqueue_scripts', 'cs_scripts' );

/**
 * Conditionally enqueue the calculators bundle. Called from the
 * [cs_calculator] shortcode handler in inc/shortcodes.php each time that
 * shortcode renders, so pages with no calculator never load the extra JS.
 *
 * D9 (audit 4.4): the data blocks are added per tool type. The hardware
 * list (about 15KB) only goes to tools with a CPU/GPU picker, the games
 * list only to the FPS calculator, and the combo page links only to the
 * two tools that show them (bottleneck and FPS, `comboLink` in
 * calculators.js). calculators.js falls back to empty data, so a tool that
 * gets none works as before.
 *
 * @param string $type Calculator type (data-calc), '' = add everything.
 */
function cs_enqueue_calculators( $type = '' ) {
	static $added = array();
	$theme_version = wp_get_theme()->get( 'Version' );
	if ( empty( $added ) ) {
		wp_enqueue_script( 'computingskill-calculators', get_template_directory_uri() . '/assets/js/calculators.js', array(), $theme_version, true );
		$added['script'] = true;
	}

	// Tools whose fields never read window.csHardwareData.
	$no_hardware = array( 'ram', 'ssd-lifespan', 'ssd-tbw', 'storage', 'storage-converter', 'download-time', 'upload-time', 'file-transfer-time', 'mbps-to-mbs', 'mbs-to-mbps', 'bandwidth', 'data-usage', 'ppi', 'pixel-density', 'dpi', 'screen-size', 'monitor-size', 'aspect-ratio', 'resolution', 'refresh-rate' );
	$wants = array(
		'hardware' => '' === $type || ! in_array( $type, $no_hardware, true ),
		'games'    => '' === $type || in_array( $type, array( 'fps', 'canirun' ), true ),
		'combos'   => '' === $type || in_array( $type, array( 'bottleneck', 'fps' ), true ),
	);

	// Hand the verified hardware dataset to the browser so calculators
	// (bottleneck, PSU, VRAM, power) never hard-code specs in JS — single
	// source of truth is data/hardware-data.php.
	if ( $wants['hardware'] && empty( $added['hardware'] ) ) {
		$added['hardware'] = true;
		$data = array(
			// D10: plus each card's upscaler / frame generation (FPS Calculator).
			'gpu' => function_exists( 'cs_get_gpu_data' ) ? ( function_exists( 'cs_pp_hardware_upscaler' ) ? cs_pp_hardware_upscaler( cs_get_gpu_data() ) : cs_get_gpu_data() ) : array(),
			'cpu' => function_exists( 'cs_get_cpu_data' ) ? cs_get_cpu_data() : array(),
		);
		wp_add_inline_script( 'computingskill-calculators', 'window.csHardwareData = ' . wp_json_encode( $data ) . ';', 'before' );
	}

	// Game requirements for the FPS calculator's optional game picker —
	// only the fields it displays, straight from data/games-data.php.
	if ( $wants['games'] && empty( $added['games'] ) ) {
		$added['games'] = true;
		$games = array();
		if ( function_exists( 'cs_get_games_data' ) ) {
			foreach ( cs_get_games_data() as $slug => $g ) {
				$games[ $slug ] = array(
					'name'    => $g['name'],
					'url'     => home_url( '/games/' . $slug . '/' ),
					'profile' => isset( $g['profile'] ) ? $g['profile'] : 'aaa',
					'min'     => array( 'cpu' => $g['min']['cpu'], 'gpu' => $g['min']['gpu'], 'ram' => $g['min']['ram'] ),
					'rec'     => array( 'cpu' => $g['rec']['cpu'], 'gpu' => $g['rec']['gpu'], 'ram' => $g['rec']['ram'] ),
					'req'     => isset( $g['req'] ) ? $g['req'] : null, // D10: Can I Run It.
					// E4 (FUN-06): false = the game has no such option.
					'upscaling' => isset( $g['upscaling'] ) ? (bool) $g['upscaling'] : null,
					'framegen'  => isset( $g['framegen'] ) ? (bool) $g['framegen'] : null,
					// U1 (U-03): a built-in frame-rate lock, and games whose FPS
					// follows their own settings more than the hardware.
					'fps_cap'    => isset( $g['fps_cap'] ) ? (int) $g['fps_cap'] : null,
					'fps_varies' => ! empty( $g['fps_varies'] ),
				);
			}
		}
		wp_add_inline_script( 'computingskill-calculators', 'window.csGamesData = ' . wp_json_encode( $games ) . ';', 'before' );
	}

	// Published /bottleneck/{cpu}-{gpu}/ pages, keyed "cpu|gpu", so the
	// bottleneck and FPS results can link to a pair's static page when one
	// exists (Phase 5, Oct 2026). Read from the posts themselves, so a link
	// is never shown for a page that isn't live.
	if ( $wants['combos'] && empty( $added['combos'] ) ) {
		$added['combos'] = true;
		// E1 (PERF-01): same map as the combo tables, built once per request.
		$combos = function_exists( 'cs_bn_published_pairs' ) ? cs_bn_published_pairs() : array();
		// D7: merged near-twin pairs link to the page they were merged into.
		if ( function_exists( 'cs_merged_combo_links' ) ) {
			$combos = cs_merged_combo_links( $combos );
		}
		wp_add_inline_script( 'computingskill-calculators', 'window.csComboPages = ' . wp_json_encode( $combos ) . ';', 'before' );
	}
}

/**
 * Register widget areas (footer).
 */
function cs_widgets_init() {
	register_sidebar( array(
		'name'          => __( 'Footer', 'computingskill' ),
		'id'            => 'footer-1',
		'description'   => __( 'Appears in the site footer.', 'computingskill' ),
		'before_widget' => '<div class="footer-widget">',
		'after_widget'  => '</div>',
		'before_title'  => '<h3 class="footer-widget-title">',
		'after_title'   => '</h3>',
	) );
}
add_action( 'widgets_init', 'cs_widgets_init' );

/**
 * Content width for embeds/images.
 */
if ( ! isset( $content_width ) ) {
	$content_width = 780;
}

/**
 * Excerpt length & more string.
 */
add_filter( 'excerpt_length', function ( $length ) {
	return 30;
} );
add_filter( 'excerpt_more', function ( $more ) {
	return '&hellip;';
} );
