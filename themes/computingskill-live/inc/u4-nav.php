<?php
/**
 * Navigation and layout (user-side audit U4, 20 Oct 2026).
 *
 *  - U-21 / U-32: the main menu (header.php fallback) gets a Tier Lists item
 *    and marks the section the visitor is in (aria-current + style).
 *  - U-22 / U-31: hub layout: a section heading straight above a
 *    [cs_related] box no longer repeats as the box's own heading;
 *    /comparisons/ intro, jump links, /bottleneck/ CPU jump list.
 *  - U-21: a GPU / CPU switch at the top of both tier lists.
 *  - U-35: "Best match" tools above search results for intent words.
 *  - U-39: the 404 page guesses the page from the URL.
 *
 * Page text in the database is changed with cs_page_text_swap() (inc/
 * calculator-pages.php): a pattern that no longer matches does nothing.
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------ */
/* Main menu: items and the current section (U-21, U-32)               */
/* ------------------------------------------------------------------ */

/**
 * Menu items in order: key => array( label, path ).
 */
function cs_nav_items() {
	return array(
		'calculators' => array( __( 'Calculators', 'computingskill' ), '/calculators/' ),
		'tools'       => array( __( 'Tools', 'computingskill' ), '/computer-tools/' ),
		'blog'        => array( __( 'Blog', 'computingskill' ), '/blog/' ),
		'games'       => array( __( 'Games', 'computingskill' ), '/games/' ),
		'hardware'    => array( __( 'Hardware', 'computingskill' ), '/hardware/' ),
		'tiers'       => array( __( 'Tier Lists', 'computingskill' ), '/gpu-tier-list/' ),
		'guides'      => array( __( 'Guides', 'computingskill' ), '/guides/' ),
		'comparisons' => array( __( 'Comparisons', 'computingskill' ), '/comparisons/' ),
	);
}

/**
 * The menu section the current page belongs to (a cs_nav_items() key), or ''.
 * Same parents as the breadcrumb (cs_breadcrumb_parent()).
 */
function cs_nav_section() {
	if ( is_front_page() || is_404() || is_search() ) {
		return '';
	}
	if ( is_post_type_archive( 'cs_game' ) || is_singular( 'cs_game' ) ) {
		return 'games';
	}
	if ( is_singular( array( 'post', 'cs_resource' ) ) || is_home() || is_category() || is_tag() || is_author() || is_date() ) {
		return 'blog';
	}
	$by_type = array(
		'cs_bottleneck' => 'comparisons',
		'cs_comparison' => 'comparisons',
		'cs_hardware'   => 'hardware',
		'cs_calculator' => 'calculators',
		'cs_tool'       => 'tools',
		'cs_guide'      => 'guides',
	);
	foreach ( $by_type as $type => $section ) {
		if ( is_singular( $type ) ) {
			return $section;
		}
	}
	if ( ! is_singular( 'page' ) ) {
		return '';
	}
	$slug  = get_post_field( 'post_name', get_queried_object_id() );
	$pages = array(
		'calculators'    => 'calculators',
		'computer-tools' => 'tools',
		'storage-tools'  => 'tools',
		'internet-tools' => 'tools',
		'display-tools'  => 'tools',
		'blog'           => 'blog',
		'hardware'       => 'hardware',
		'gpu-tier-list'  => 'tiers',
		'cpu-tier-list'  => 'tiers',
		'guides'         => 'guides',
		'comparisons'    => 'comparisons',
	);
	if ( isset( $pages[ $slug ] ) ) {
		return $pages[ $slug ];
	}
	$groups = array(
		'calculators'    => 'calculators',
		'storage-tools'  => 'tools',
		'internet-tools' => 'tools',
		'display-tools'  => 'tools',
		'hardware'       => 'hardware',
		'guides'         => 'guides',
		'comparisons'    => 'comparisons',
	);
	if ( function_exists( 'cs_content_index_groups' ) ) {
		foreach ( cs_content_index_groups() as $key => $group ) {
			foreach ( $group['items'] as $item ) {
				if ( $item['slug'] === $slug && isset( $groups[ $key ] ) ) {
					return $groups[ $key ];
				}
			}
		}
	}
	return '';
}

/**
 * The fallback main menu (no menu assigned in wp-admin, as on the live site).
 * The section link gets aria-current: "page" on the hub itself, "true" on a
 * page inside that section.
 */
function cs_nav_menu_html() {
	$current = cs_nav_section();
	$here    = untrailingslashit( (string) wp_parse_url( home_url( add_query_arg( array() ) ), PHP_URL_PATH ) );
	$html    = '<ul id="cs-primary-menu">';
	foreach ( cs_nav_items() as $key => $item ) {
		$attr = '';
		if ( $key === $current ) {
			$is_hub = untrailingslashit( (string) wp_parse_url( home_url( $item[1] ), PHP_URL_PATH ) ) === $here;
			$attr   = ' class="is-current" aria-current="' . ( $is_hub ? 'page' : 'true' ) . '"';
		}
		$html .= '<li><a href="' . esc_url( home_url( $item[1] ) ) . '"' . $attr . '>' . esc_html( $item[0] ) . '</a></li>';
	}
	return $html . '</ul>';
}

/* ------------------------------------------------------------------ */
/* Hub layout (U-22, U-31, U-38)                                        */
/* ------------------------------------------------------------------ */

/**
 * Raw page text (priority 8, before shortcodes). /comparisons/ said "Five
 * comparisons live here" and counted the CPU vs GPU Calculator as one, while
 * the page also lists the tier lists and the head-to-head pages.
 */
function cs_u4_page_fixes() {
	$vs = function_exists( 'cs_pp_vs_pairs' ) ? count( cs_pp_vs_pairs() ) : 0;
	return array(
		'comparisons' => array(
			array(
				'Five comparisons live here:', 'for the two parts you enter.',
				/* translators: %d: number of head-to-head pages */
				sprintf( __( 'Four written comparisons live here: SSD vs HDD (which storage type makes sense for your use case), NVMe vs SATA (the two common SSD interfaces and when the difference matters), DDR4 vs DDR5 (whether the newer memory standard is worth it for your build) and 1080p vs 1440p vs 4K (how resolution affects both picture quality and the hardware you need to drive it). Below them are the GPU and CPU tier lists, which rank every graphics card and processor we cover, and %d head-to-head pages for specific graphics cards and processors, grouped by class. For two parts of your own, the CPU vs GPU Calculator puts their scores side by side.', 'computingskill' ), $vs ),
			),
		),
	);
}

function cs_u4_page_text( $content ) {
	if ( ! is_singular( 'page' ) || ! in_the_loop() || ! is_main_query() || ! function_exists( 'cs_page_text_swap' ) ) {
		return $content;
	}
	$fixes = cs_u4_page_fixes();
	$slug  = get_post_field( 'post_name' );
	return isset( $fixes[ $slug ] ) ? cs_page_text_swap( $content, $fixes[ $slug ] ) : $content;
}
add_filter( 'the_content', 'cs_u4_page_text', 8 );

/**
 * Inserts $html after the first paragraph of the page text, skipping the
 * quick answer box (inc/aeo.php) when the page opens with one.
 */
function cs_after_first_paragraph( $content, $html ) {
	$from = 0;
	$box  = strpos( $content, '<div class="cs-quick-answer">' );
	if ( false !== $box ) {
		$end  = strpos( $content, '</div></div>', $box );
		$from = false === $end ? 0 : $end + 12;
	}
	$pos = strpos( $content, '</p>', $from );
	if ( false === $pos ) {
		return $content . $html;
	}
	return substr( $content, 0, $pos + 4 ) . "\n" . $html . substr( $content, $pos + 4 );
}

/**
 * A row of in-page links: array( '#id' or URL => label ).
 */
function cs_jump_nav( $links, $label, $class = '' ) {
	$html = '<nav class="cs-jump-nav' . ( $class ? ' ' . esc_attr( $class ) : '' ) . '" aria-label="' . esc_attr( $label ) . '"><span class="cs-jump-label">' . esc_html( $label ) . ':</span> <ul>';
	foreach ( $links as $href => $text ) {
		$html .= '<li><a href="' . esc_url( $href ) . '">' . esc_html( $text ) . '</a></li>';
	}
	return $html . '</ul></nav>';
}

/**
 * After the shortcodes (priority 15):
 *  - U-31: a section h2 right above a [cs_related] box ("Performance &
 *    Compatibility" then "Performance & Compatibility Calculators") keeps
 *    only the section heading.
 *  - /comparisons/: the box under the H1 "Comparisons" was headed
 *    "Comparisons" too; jump links under the intro.
 *  - U-38: /guides/ "Bottleneck Guides" lists two calculators as well.
 *  - /bottleneck/: a CPU jump list near the top (the table index below the
 *    FAQ is ~11,000px long).
 */
function cs_u4_hub_layout( $content ) {
	if ( ! is_singular( 'page' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	$slug = get_post_field( 'post_name' );

	$content = preg_replace( '#(<h2[^>]*>(?:(?!</h2>).)+</h2>\s*(?:<p>\s*)?<div class="cs-related">)\s*<h2>(?:(?!</h2>).)*</h2>#su', '$1', $content );

	if ( 'comparisons' === $slug ) {
		$content = preg_replace( '#(<div class="cs-related">\s*)<h2>Comparisons</h2>#u', '$1<h2 id="written">' . esc_html__( 'Written Comparisons', 'computingskill' ) . '</h2>', $content, 1 );
		$links   = array(
			'#written'  => __( 'Written comparisons', 'computingskill' ),
			'#rankings' => __( 'Tier lists', 'computingskill' ),
			'#vs-gpu'   => __( 'Graphics card head-to-heads', 'computingskill' ),
			'#vs-cpu'   => __( 'Processor head-to-heads', 'computingskill' ),
		);
		$content = cs_after_first_paragraph( $content, cs_jump_nav( $links, __( 'On this page', 'computingskill' ) ) );
	} elseif ( 'hardware' === $slug ) {
		$links   = array(
			'#every-part'  => __( 'Every CPU and GPU page', 'computingskill' ),
			'#part-gpu'    => __( 'Graphics cards', 'computingskill' ),
			'#part-cpu'    => __( 'Processors', 'computingskill' ),
			home_url( '/gpu-tier-list/' ) => __( 'GPU tier list', 'computingskill' ),
			home_url( '/cpu-tier-list/' ) => __( 'CPU tier list', 'computingskill' ),
		);
		$content = cs_after_first_paragraph( $content, cs_jump_nav( $links, __( 'Jump to', 'computingskill' ) ) );
	} elseif ( 'guides' === $slug ) {
		$content = preg_replace( '#(<div class="cs-related">\s*<h2>)Bottleneck Guides(</h2>)#u', '$1' . esc_html__( 'Bottleneck Guides and Calculators', 'computingskill' ) . '$2', $content, 1 );
	} elseif ( 'bottleneck' === $slug ) {
		$content = cs_after_first_paragraph( $content, cs_bn_cpu_jump_nav() );
	}
	return $content;
}
add_filter( 'the_content', 'cs_u4_hub_layout', 15 );

/**
 * Anchor id for a CPU group in the /bottleneck/ index.
 */
function cs_bn_cpu_anchor( $cpu_slug ) {
	return 'bn-' . sanitize_title( $cpu_slug );
}

/**
 * "Find your CPU" links to each CPU group of the /bottleneck/ table index,
 * AMD and Intel on their own rows, in the index order (slowest first).
 */
function cs_bn_cpu_jump_nav() {
	if ( ! function_exists( 'cs_bn_published_pairs' ) ) {
		return '';
	}
	$cpus = cs_get_cpu_data();
	$have = array();
	foreach ( array_keys( cs_bn_published_pairs() ) as $key ) {
		list( $c ) = explode( '|', $key );
		if ( isset( $cpus[ $c ] ) ) {
			$have[ $c ] = $cpus[ $c ];
		}
	}
	if ( ! $have ) {
		return '';
	}
	uasort( $have, function ( $a, $b ) {
		return $a['score'] - $b['score'] ?: strcmp( $a['name'], $b['name'] );
	} );
	$by = array();
	foreach ( $have as $c => $cpu ) {
		$by[ $cpu['brand'] ][ '#' . cs_bn_cpu_anchor( $c ) ] = cs_bn_short_name( $cpu['name'] );
	}
	$html = '<div class="cs-jump-group"><p class="cs-jump-title"><a href="#bn-index">' . esc_html__( 'Find your CPU in the table below', 'computingskill' ) . '</a> ' . esc_html__( '(slowest first):', 'computingskill' ) . '</p>';
	foreach ( $by as $brand => $links ) {
		/* translators: %s: CPU brand */
		$html .= cs_jump_nav( $links, sprintf( __( '%s CPUs', 'computingskill' ), $brand ) );
	}
	return $html . '</div>';
}

/**
 * U-21: GPU / CPU switch at the top of both tier lists, so the menu's one
 * Tier Lists link reaches both.
 */
function cs_tier_switch( $content ) {
	if ( ! is_singular( 'page' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	$slug = get_post_field( 'post_name' );
	$all  = array(
		'gpu-tier-list' => __( 'GPU tier list', 'computingskill' ),
		'cpu-tier-list' => __( 'CPU tier list', 'computingskill' ),
	);
	if ( ! isset( $all[ $slug ] ) ) {
		return $content;
	}
	$html = '<nav class="cs-tier-switch" aria-label="' . esc_attr__( 'Tier lists', 'computingskill' ) . '"><ul>';
	foreach ( $all as $s => $label ) {
		$html .= '<li>' . ( $s === $slug
			? '<a href="' . esc_url( home_url( '/' . $s . '/' ) ) . '" aria-current="page">' . esc_html( $label ) . '</a>'
			: '<a href="' . esc_url( home_url( '/' . $s . '/' ) ) . '">' . esc_html( $label ) . '</a>' ) . '</li>';
	}
	return $html . '</ul></nav>' . "\n" . $content;
}
add_filter( 'the_content', 'cs_tier_switch', 16 );

/* ------------------------------------------------------------------ */
/* Search: best-match tools for intent words (U-35)                     */
/* ------------------------------------------------------------------ */

/**
 * Pattern => page slugs, first match first. Words people type when they
 * want a tool, which WordPress search matched only by text ("psu for rtx
 * 4080" missed the PSU Calculator, "can i run cyberpunk" gave PUBG).
 */
function cs_search_intents() {
	return array(
		'#\bcan\s*i\s*run\b|\bsystem\s+requirements?\b#'                             => array( 'can-i-run-it' ),
		'#\bbottle\s*neck#'                                                          => array( 'pc-bottleneck-calculator', 'bottleneck' ),
		'#\bpsu\b|\bpower\s+supply\b|\bwatt(age|s)?\b#'                              => array( 'psu-calculator', 'how-to-choose-a-psu' ),
		'#\bpower\s+(draw|consumption|usage)\b|\belectricity\b#'                     => array( 'pc-power-consumption-calculator' ),
		'#\bvram\b#'                                                                 => array( 'how-much-vram-do-i-need', 'gpu-vram-calculator' ),
		'#\bram\b|\bmemory\b#'                                                       => array( 'how-much-ram-do-i-need', 'ram-calculator' ),
		'#\bfps\b|\bframe\s*rate\b|\bframes\b#'                                      => array( 'fps-calculator', 'how-to-improve-fps' ),
		'#\btier\s*lists?\b|\brank(ing|ed)\b#'                                     => array( 'gpu-tier-list', 'cpu-tier-list' ),
		'#\bupgrade\b#'                                                              => array( 'pc-upgrade-calculator', 'how-to-upgrade-a-gaming-pc' ),
		'#\bcompatib#'                                                               => array( 'pc-compatibility-checker' ),
		'#\bbuild\s+cost\b|\bbudget\b|\bhow\s+much\s+(does|is|will)\b.*\bcost\b#'     => array( 'pc-build-cost-calculator' ),
		'#\bcool(ing|er)\b|\baio\b#'                                                 => array( 'pc-cooling-calculator' ),
	);
}

/**
 * Up to three published pages for the query, or array().
 */
function cs_search_suggestions( $query ) {
	$q = strtolower( trim( (string) $query ) );
	if ( '' === $q ) {
		return array();
	}
	$slugs = array();
	foreach ( cs_search_intents() as $pattern => $list ) {
		if ( preg_match( $pattern, $q ) ) {
			$slugs = array_merge( $slugs, $list );
		}
	}
	$out = array();
	foreach ( array_unique( $slugs ) as $slug ) {
		$page = get_page_by_path( $slug );
		if ( $page && 'publish' === $page->post_status ) {
			$out[ $slug ] = $page;
		}
		if ( count( $out ) >= 3 ) {
			break;
		}
	}
	return $out;
}

/* ------------------------------------------------------------------ */
/* 404: guess the page from the URL (U-39)                              */
/* ------------------------------------------------------------------ */

/**
 * The words of the missing URL's last part ("/psu-calculater/" → "psu
 * calculater"), for the search box.
 */
function cs_404_words() {
	$path = (string) wp_parse_url( add_query_arg( array() ), PHP_URL_PATH );
	$last = basename( untrailingslashit( $path ) );
	$last = preg_replace( '#\.(html?|php)$#i', '', rawurldecode( $last ) );
	$words = trim( preg_replace( '#[^a-z0-9]+#i', ' ', strtolower( $last ) ) );
	return mb_substr( $words, 0, 80 );
}

/**
 * Closest published page to the missing URL: array( title, url ) or null.
 * Compares the last part of the path with every page, combo, part and
 * comparison slug (edit distance, at most about a third of the length).
 */
function cs_404_guess() {
	$want = str_replace( ' ', '-', cs_404_words() );
	if ( strlen( $want ) < 3 || strlen( $want ) > 80 ) {
		return null;
	}
	global $wpdb;
	$rows = $wpdb->get_results( "SELECT ID, post_name FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ('page','post','cs_bottleneck','cs_hardware','cs_comparison','cs_game','cs_calculator','cs_tool','cs_guide') AND post_name <> ''" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$best = null;
	$dist = PHP_INT_MAX;
	foreach ( (array) $rows as $row ) {
		$d = levenshtein( $want, substr( $row->post_name, 0, 255 ) );
		if ( $d < $dist ) {
			$dist = $d;
			$best = $row;
		}
	}
	if ( ! $best || $dist > max( 2, (int) floor( strlen( $want ) / 3 ) ) ) {
		return null;
	}
	return array( wp_strip_all_tags( get_the_title( $best->ID ) ), get_permalink( $best->ID ) );
}
