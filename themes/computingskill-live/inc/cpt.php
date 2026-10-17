<?php
/**
 * Custom post types & taxonomies.
 *
 * Registered so the site can grow past its initial 57-page launch set via
 * Custom Post Types rather than ad-hoc pages: calculator, tool, hardware,
 * guide, comparison, resource, bottleneck.
 *
 * IMPORTANT URL/architecture note: the site's initial required landing
 * pages (/pc-bottleneck-calculator/, /cpu/, /how-much-ram-do-i-need/, etc.)
 * are shipped as flat, top-level WordPress Pages via the bundled WXR
 * import — that's the only way to guarantee the exact flat slugs the spec
 * requires without fragile catch-all rewrite rules. The category landing
 * pages (/calculators/, /computer-tools/, /hardware/, /guides/,
 * /comparisons/, /resources/) are ALSO shipped as Pages, with authored
 * intro copy and a card grid built from the [cs_related] shortcode, so
 * has_archive is intentionally left OFF below to keep those slugs free
 * for the Pages. These CPTs remain fully registered and public so that,
 * as the site grows past 100/500 pages, an editor can add NEW calculators,
 * tools, hardware pages or guides here (each gets its own indexable
 * /calculators/{slug}/ etc. URL and renders via single.php's clean
 * non-blog layout) without ever touching theme code.
 *
 * All are registered with 'public' => true so Yoast/Rank Math metaboxes
 * attach automatically — no special-casing needed here.
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cs_register_post_types() {

	$common_supports = array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'custom-fields' );

	register_post_type( 'cs_calculator', array(
		'labels'       => array(
			'name'          => __( 'Calculators', 'computingskill' ),
			'singular_name' => __( 'Calculator', 'computingskill' ),
			'add_new_item'  => __( 'Add New Calculator', 'computingskill' ),
		),
		'public'       => true,
		'has_archive'  => false,
		'rewrite'      => array( 'slug' => 'calculators', 'with_front' => false ),
		'menu_icon'    => 'dashicons-calculator',
		'supports'     => $common_supports,
		'show_in_rest' => true,
		'template'     => array( 'page-templates/template-calculator.php' ),
	) );

	register_post_type( 'cs_tool', array(
		'labels'       => array(
			'name'          => __( 'Computer Tools', 'computingskill' ),
			'singular_name' => __( 'Tool', 'computingskill' ),
		),
		'public'       => true,
		'has_archive'  => false,
		'rewrite'      => array( 'slug' => 'tools', 'with_front' => false ),
		'menu_icon'    => 'dashicons-admin-tools',
		'supports'     => $common_supports,
		'show_in_rest' => true,
	) );

	register_post_type( 'cs_hardware', array(
		'labels'       => array(
			'name'          => __( 'Hardware', 'computingskill' ),
			'singular_name' => __( 'Hardware Page', 'computingskill' ),
		),
		'public'       => true,
		'has_archive'  => false,
		'rewrite'      => array( 'slug' => 'hardware', 'with_front' => false ),
		'menu_icon'    => 'dashicons-desktop',
		'supports'     => $common_supports,
		'show_in_rest' => true,
	) );

	register_post_type( 'cs_guide', array(
		'labels'       => array(
			'name'          => __( 'Guides', 'computingskill' ),
			'singular_name' => __( 'Guide', 'computingskill' ),
		),
		'public'       => true,
		'has_archive'  => false,
		'rewrite'      => array( 'slug' => 'guides', 'with_front' => false ),
		'menu_icon'    => 'dashicons-book-alt',
		'supports'     => $common_supports,
		'show_in_rest' => true,
	) );

	register_post_type( 'cs_comparison', array(
		'labels'       => array(
			'name'          => __( 'Comparisons', 'computingskill' ),
			'singular_name' => __( 'Comparison', 'computingskill' ),
		),
		'public'       => true,
		'has_archive'  => false,
		'rewrite'      => array( 'slug' => 'comparisons', 'with_front' => false ),
		'menu_icon'    => 'dashicons-randomize',
		'supports'     => $common_supports,
		'show_in_rest' => true,
	) );

	register_post_type( 'cs_resource', array(
		'labels'       => array(
			'name'          => __( 'Resources', 'computingskill' ),
			'singular_name' => __( 'Resource', 'computingskill' ),
		),
		'public'       => true,
		'has_archive'  => false,
		'rewrite'      => array( 'slug' => 'resources', 'with_front' => false ),
		'menu_icon'    => 'dashicons-media-document',
		'supports'     => $common_supports,
		'show_in_rest' => true,
	) );

	// Games hub: /games/ (auto-generated archive, so new games added later
	// show up automatically without any theme changes) and individual
	// pages at /games/{slug}/. Unlike the CPTs above, has_archive is ON
	// here because there is no pre-existing hand-authored Page at /games/
	// to protect — this section did not exist before.
	register_post_type( 'cs_game', array(
		'labels'       => array(
			'name'          => __( 'Games', 'computingskill' ),
			'singular_name' => __( 'Game', 'computingskill' ),
			'add_new_item'  => __( 'Add New Game', 'computingskill' ),
		),
		'public'       => true,
		'has_archive'  => 'games',
		'rewrite'      => array( 'slug' => 'games', 'with_front' => false ),
		'menu_icon'    => 'dashicons-games',
		'supports'     => $common_supports,
		'show_in_rest' => true,
	) );

	// Programmatic bottleneck combination pages: /bottleneck/{cpu}/{gpu}/.
	register_post_type( 'cs_bottleneck', array(
		'labels'       => array(
			'name'          => __( 'Bottleneck Pages', 'computingskill' ),
			'singular_name' => __( 'Bottleneck Page', 'computingskill' ),
		),
		'public'       => true,
		'has_archive'  => false,
		'rewrite'      => array( 'slug' => 'bottleneck', 'with_front' => false ),
		'menu_icon'    => 'dashicons-performance',
		'supports'     => $common_supports,
		'show_in_rest' => true,
	) );
}
add_action( 'init', 'cs_register_post_types' );

/*
 * Combo pages live at /bottleneck/{cpu}-{gpu}/, the post type's own
 * permalink. The old two-segment rewrite (/bottleneck/{cpu}/{gpu}/) was
 * removed in D6 (deep audit 2.1): it made every /bottleneck/x/y/ URL return
 * a 200 copy of the homepage. Nested /bottleneck/ URLs are now redirected
 * or answered with a 404 by cs_url_guards() in inc/technical-seo.php.
 */

/**
 * Show every Games hub entry on one page instead of paginating after the
 * site-wide "Blog pages show at most" setting (WordPress core default:
 * 10). Confirmed live: with 16 published games, /games/ page 1 only
 * linked to 10 of them — the other 6 (including several with no other
 * inbound link on the site) were reachable only via a second,
 * weaker-signal pagination page (/games/page/2/). Fewer link "hops" from
 * the homepage to a page is one of the concrete, checkable factors that
 * affects how quickly Google crawls and indexes it, so keep the whole
 * hub one click deep as the section grows, up to a sane ceiling.
 */
function cs_games_archive_query( $query ) {
	if ( ! $query->is_main_query() || ! $query->is_post_type_archive( 'cs_game' ) ) {
		return;
	}
	$query->set( 'posts_per_page', 60 );
}
add_action( 'pre_get_posts', 'cs_games_archive_query' );

/**
 * Flush rewrite rules once on theme activation so the custom bottleneck
 * rule above and CPT slugs take effect immediately.
 */
function cs_flush_rewrites_on_switch() {
	cs_register_post_types();
	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'cs_flush_rewrites_on_switch' );

/**
 * Shared taxonomy: Hardware Brand (NVIDIA / AMD / Intel), used across
 * cs_hardware, cs_bottleneck and cs_guide so brand archives work.
 */
function cs_register_taxonomies() {
	register_taxonomy( 'cs_brand', array( 'cs_hardware', 'cs_bottleneck', 'cs_guide', 'cs_calculator' ), array(
		'labels'            => array(
			'name'          => __( 'Brands', 'computingskill' ),
			'singular_name' => __( 'Brand', 'computingskill' ),
		),
		'public'            => true,
		'hierarchical'      => true,
		'show_in_rest'      => true,
		'rewrite'           => array( 'slug' => 'brand' ),
	) );
}
add_action( 'init', 'cs_register_taxonomies' );
