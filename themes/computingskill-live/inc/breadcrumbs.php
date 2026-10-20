<?php
/**
 * Lightweight breadcrumbs. Deliberately does NOT try to be an SEO-plugin
 * replacement — it only renders visible breadcrumb HTML. BreadcrumbList
 * schema is emitted separately in inc/schema.php only when no SEO plugin
 * is already providing breadcrumb schema.
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Echoes a breadcrumb trail for the current request.
 */
function cs_breadcrumbs() {
	$trail = array();
	$trail[] = array( 'label' => __( 'Home', 'computingskill' ), 'url' => home_url( '/' ) );

	if ( is_front_page() ) {
		$trail = array( array( 'label' => __( 'Home', 'computingskill' ), 'url' => '' ) );
	} elseif ( is_category() || is_tax() ) {
		$term = get_queried_object();
		$trail[] = array( 'label' => $term->name, 'url' => '' );
	} elseif ( is_post_type_archive() ) {
		$trail[] = array( 'label' => post_type_archive_title( '', false ), 'url' => '' );
	} elseif ( is_search() ) {
		$trail[] = array( 'label' => sprintf( __( 'Search results for "%s"', 'computingskill' ), get_search_query() ), 'url' => '' );
	} elseif ( is_404() ) {
		$trail[] = array( 'label' => __( '404', 'computingskill' ), 'url' => '' );
	} elseif ( is_singular( 'cs_bottleneck' ) ) {
		// Parent is the /bottleneck/ hub (Sep 2026 audit): gives the hub a
		// crawlable link from every one of the 30 combination pages.
		// U4 (U-38): /bottleneck/ itself sits under Comparisons.
		$trail[] = array( 'label' => __( 'Comparisons', 'computingskill' ), 'url' => home_url( '/comparisons/' ) );
		$trail[] = array( 'label' => __( 'Bottleneck Comparisons', 'computingskill' ), 'url' => home_url( '/bottleneck/' ) );
		$trail[] = array( 'label' => get_the_title(), 'url' => '' );
	} elseif ( is_singular() ) {
		$post_type = get_post_type();
		$archive_map = array(
			'cs_calculator' => array( __( 'Calculators', 'computingskill' ), '/calculators/' ),
			'cs_tool'       => array( __( 'Computer Tools', 'computingskill' ), '/computer-tools/' ),
			'cs_hardware'   => array( __( 'Hardware', 'computingskill' ), '/hardware/' ),
			'cs_guide'      => array( __( 'Guides', 'computingskill' ), '/guides/' ),
			'cs_comparison' => array( __( 'Comparisons', 'computingskill' ), '/comparisons/' ),
			'cs_resource'   => array( __( 'Blog', 'computingskill' ), '/blog/' ), // /resources/ 301s to /blog/ (inc/redirects.php).
			'cs_game'       => array( __( 'Games', 'computingskill' ), '/games/' ),
		);
		if ( isset( $archive_map[ $post_type ] ) ) {
			$trail[] = array( 'label' => $archive_map[ $post_type ][0], 'url' => home_url( $archive_map[ $post_type ][1] ) );
		} elseif ( $parent = cs_breadcrumb_parent() ) { // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition
			$trail[] = array( 'label' => $parent[0], 'url' => $parent[1] );
		}
		$trail[] = array( 'label' => get_the_title(), 'url' => '' );
	}

	if ( count( $trail ) < 2 ) {
		return;
	}

	echo '<nav class="cs-breadcrumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'computingskill' ) . '"><ol>';
	$last_key = count( $trail ) - 1;
	foreach ( $trail as $i => $crumb ) {
		if ( $i === $last_key || empty( $crumb['url'] ) ) {
			echo '<li aria-current="page">' . esc_html( $crumb['label'] ) . '</li>';
		} else {
			echo '<li><a href="' . esc_url( $crumb['url'] ) . '">' . esc_html( $crumb['label'] ) . '</a></li>';
		}
	}
	echo '</ol></nav>';
}

/**
 * Middle breadcrumb level for Pages and posts (deep audit D6, 2.18): the
 * trail went straight from Home to the page (Home › FPS Calculator). A page
 * listed in the content index (inc/content-index.php) gets the hub of its
 * group (Home › Calculators › FPS Calculator); the storage, internet and
 * display hubs sit under Computer Tools; blog posts under Blog. Returns
 * array( label, url ) or null. Used by the visible trail and the
 * BreadcrumbList schema alike.
 */
function cs_breadcrumb_parent() {
	if ( ! is_singular( array( 'page', 'post' ) ) ) {
		return null;
	}
	$hub_page = function ( $slug, $fallback ) {
		$page = get_page_by_path( $slug );
		if ( ! $page || 'publish' !== $page->post_status ) {
			return null;
		}
		$title = wp_strip_all_tags( get_the_title( $page ) );
		return array( '' !== $title ? $title : $fallback, get_permalink( $page ) );
	};
	if ( is_singular( 'post' ) ) {
		return $hub_page( 'blog', __( 'Blog', 'computingskill' ) );
	}
	$slug = get_post_field( 'post_name', get_queried_object_id() );
	if ( in_array( $slug, array( 'storage-tools', 'internet-tools', 'display-tools' ), true ) ) {
		return $hub_page( 'computer-tools', __( 'Computer Tools', 'computingskill' ) );
	}
	if ( ! function_exists( 'cs_content_index_groups' ) ) {
		return null;
	}
	foreach ( cs_content_index_groups() as $group ) {
		$hub = trim( $group['archive'], '/' );
		if ( $hub === $slug ) {
			return null;
		}
		foreach ( $group['items'] as $item ) {
			if ( $item['slug'] === $slug ) {
				return $hub_page( $hub, $group['label'] );
			}
		}
	}
	return null;
}

/**
 * Returns the breadcrumb trail as an array (used by schema.php so the
 * visible trail and the JSON-LD trail always match).
 */
function cs_get_breadcrumb_items() {
	ob_start();
	cs_breadcrumbs();
	ob_end_clean();
	// Rebuilt directly to avoid parsing HTML back out.
	return null;
}
