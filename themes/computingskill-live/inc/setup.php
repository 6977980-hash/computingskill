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

	if ( is_singular() ) {
		wp_enqueue_script( 'computingskill-comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'cs_scripts' );

/**
 * Conditionally enqueue the calculators bundle. Called from the
 * [cs_calculator] shortcode handler in inc/shortcodes.php the first time
 * that shortcode renders on a request, so pages with no calculator never
 * load the extra JS.
 */
function cs_enqueue_calculators() {
	static $done = false;
	if ( $done ) {
		return;
	}
	$done          = true;
	$theme_version = wp_get_theme()->get( 'Version' );
	wp_enqueue_script( 'computingskill-calculators', get_template_directory_uri() . '/assets/js/calculators.js', array(), $theme_version, true );

	// Hand the verified hardware dataset to the browser so calculators
	// (bottleneck, PSU, VRAM, power) never hard-code specs in JS — single
	// source of truth is data/hardware-data.php.
	$data = array(
		'gpu' => function_exists( 'cs_get_gpu_data' ) ? cs_get_gpu_data() : array(),
		'cpu' => function_exists( 'cs_get_cpu_data' ) ? cs_get_cpu_data() : array(),
	);
	wp_add_inline_script( 'computingskill-calculators', 'window.csHardwareData = ' . wp_json_encode( $data ) . ';', 'before' );

	// Game requirements for the FPS calculator's optional game picker —
	// only the fields it displays, straight from data/games-data.php.
	$games = array();
	if ( function_exists( 'cs_get_games_data' ) ) {
		foreach ( cs_get_games_data() as $slug => $g ) {
			$games[ $slug ] = array(
				'name' => $g['name'],
				'url'  => home_url( '/games/' . $slug . '/' ),
				'min'  => array( 'cpu' => $g['min']['cpu'], 'gpu' => $g['min']['gpu'], 'ram' => $g['min']['ram'] ),
				'rec'  => array( 'cpu' => $g['rec']['cpu'], 'gpu' => $g['rec']['gpu'], 'ram' => $g['rec']['ram'] ),
			);
		}
	}
	wp_add_inline_script( 'computingskill-calculators', 'window.csGamesData = ' . wp_json_encode( $games ) . ';', 'before' );

	// Published /bottleneck/{cpu}-{gpu}/ pages, keyed "cpu|gpu", so the
	// bottleneck and FPS results can link to a pair's static page when one
	// exists (Phase 5, Oct 2026). Read from the posts themselves, so a link
	// is never shown for a page that isn't live.
	$combos = array();
	$posts  = get_posts( array(
		'post_type'      => 'cs_bottleneck',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	) );
	foreach ( $posts as $post_id ) {
		$cpu = get_post_meta( $post_id, 'cs_cpu', true );
		$gpu = get_post_meta( $post_id, 'cs_gpu', true );
		if ( $cpu && $gpu ) {
			$combos[ $cpu . '|' . $gpu ] = get_permalink( $post_id );
		}
	}
	wp_add_inline_script( 'computingskill-calculators', 'window.csComboPages = ' . wp_json_encode( $combos ) . ';', 'before' );
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
