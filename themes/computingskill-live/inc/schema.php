<?php
/**
 * JSON-LD structured data.
 *
 * Prints real, accurate schema only — no fake ratings, reviews, authors
 * or organizations. Respects cs_schema_should_emit() from seo-compat.php
 * so we never duplicate what Rank Math / Yoast already output.
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cs_print_schema() {
	$graphs = array();

	// WebSite (front page only).
	if ( is_front_page() && cs_schema_should_emit( 'WebSite' ) ) {
		$graphs[] = array(
			'@type'           => 'WebSite',
			'@id'             => home_url( '/#website' ),
			'url'             => home_url( '/' ),
			'name'            => get_bloginfo( 'name' ),
			'description'     => get_bloginfo( 'description' ),
			'potentialAction' => array(
				'@type'       => 'SearchAction',
				'target'      => array(
					'@type'       => 'EntryPoint',
					'urlTemplate' => home_url( '/?s={search_term_string}' ),
				),
				'query-input' => 'required name=search_term_string',
			),
		);
	}

	// BreadcrumbList.
	if ( cs_schema_should_emit( 'BreadcrumbList' ) ) {
		$items = cs_build_breadcrumb_schema_items();
		if ( count( $items ) > 1 ) {
			$graphs[] = array(
				'@type'           => 'BreadcrumbList',
				'itemListElement' => $items,
			);
		}
	}

	// SoftwareApplication for calculator pages/CPT — genuinely appropriate
	// since each calculator is a small client-side application.
	if ( cs_schema_should_emit( 'SoftwareApplication' ) && is_singular() && cs_is_calculator_page() ) {
		$graphs[] = array(
			// WebApplication (a SoftwareApplication subtype) — these are
			// in-browser tools, which is exactly what this subtype is for.
			'@type'                 => 'WebApplication',
			'browserRequirements'   => 'Requires JavaScript',
			'name'                  => get_the_title(),
			'applicationCategory'   => 'UtilitiesApplication',
			'operatingSystem'       => 'Any (runs in browser)',
			'url'                   => get_permalink(),
			'offers'                => array(
				'@type' => 'Offer',
				'price' => '0',
				'priceCurrency' => 'USD',
			),
		);
	}

	// FAQPage — built from the [cs_faq] queue populated while the_content()
	// rendered on this request (see inc/shortcodes.php). Excluded on legal
	// pages (found in the Sep 2026 audit): Privacy Policy and Disclaimer
	// each carry a short, genuine [cs_faq] block for readability, but a
	// policy page marked up as an FAQPage rich-result candidate isn't the
	// kind of content that schema is meant to represent, and Google has in
	// any case restricted FAQ rich results to a narrow set of sites since
	// 2023 — so this earns no benefit, only avoids the mismatch. The visible
	// FAQ text on those pages is untouched; only the structured-data emission
	// is skipped.
	$cs_faq_schema_excluded_slugs = array( 'privacy-policy', 'disclaimer' );
	if ( cs_schema_should_emit( 'FAQPage' ) && is_singular() && ! is_page( $cs_faq_schema_excluded_slugs ) ) {
		global $cs_faq_schema_queue;
		if ( ! empty( $cs_faq_schema_queue ) ) {
			$main_entity = array();
			foreach ( $cs_faq_schema_queue as $qa ) {
				$main_entity[] = array(
					'@type'          => 'Question',
					'name'           => $qa['question'],
					'acceptedAnswer' => array(
						'@type' => 'Answer',
						'text'  => $qa['answer'],
					),
				);
			}
			$graphs[] = array(
				'@type'      => 'FAQPage',
				'mainEntity' => $main_entity,
			);
		}
	}

	// ItemList — only on the Blog / Articles hub page, listing the real,
	// currently-published calculators/tools/guides it displays. Kept out
	// of the "always safe" default list in cs_schema_should_emit() checks
	// below since it's specific to this one page rather than structural
	// site-wide data.
	if ( is_page( 'blog' ) && cs_schema_should_emit( 'ItemList' ) && function_exists( 'cs_get_blog_index_flat' ) ) {
		$flat = cs_get_blog_index_flat();
		if ( ! empty( $flat ) ) {
			$list_items = array();
			$position   = 0;
			foreach ( $flat as $entry ) {
				$position++;
				$list_items[] = array(
					'@type'    => 'ListItem',
					'position' => $position,
					'url'      => home_url( '/' . $entry['slug'] . '/' ),
					'name'     => $entry['title'],
				);
			}
			$graphs[] = array(
				'@type'           => 'ItemList',
				'name'            => get_the_title(),
				'numberOfItems'   => count( $list_items ),
				'itemListElement' => $list_items,
			);
		}
	}

	// VideoGame — individual /games/{slug}/ pages only, built from our own
	// verified data/games-data.php (min/recommended specs), never from
	// guessed or invented review/rating data.
	if ( cs_schema_should_emit( 'VideoGame' ) && is_singular( 'cs_game' ) && function_exists( 'cs_get_games_data' ) ) {
		global $post;
		$games_data = cs_get_games_data();
		$slug       = $post ? $post->post_name : '';
		if ( isset( $games_data[ $slug ] ) ) {
			$g        = $games_data[ $slug ];
			$graphs[] = array(
				'@type'            => 'VideoGame',
				'name'             => $g['name'],
				'genre'            => $g['genre'],
				'datePublished'    => (string) $g['release_year'],
				'author'           => array(
					'@type' => 'Organization',
					'name'  => $g['developer'],
				),
				'url'              => get_permalink(),
				'operatingSystem'  => $g['min']['os'],
			);
		}
	}

	// Merge Rank Math's own entities (captured and held back from wp_head by
	// cs_capture_rank_math_schema() below) into this same graph, so every
	// page ships exactly ONE JSON-LD block instead of two.
	global $cs_rank_math_schema;
	if ( ! empty( $cs_rank_math_schema ) && is_array( $cs_rank_math_schema ) ) {
		$graphs = array_merge( array_values( $cs_rank_math_schema ), $graphs );
	}

	if ( empty( $graphs ) ) {
		return;
	}

	$graphs = cs_schema_unify_author( $graphs );
	$graphs = cs_schema_normalize_types( $graphs );

	$output = array(
		'@context' => 'https://schema.org',
		'@graph'   => $graphs,
	);

	echo "\n<script type=\"application/ld+json\" class=\"cs-schema\">" . wp_json_encode( wp_kses_post_deep( $output ), JSON_UNESCAPED_SLASHES ) . "</script>\n";
}
// Priority 20: after the_content() has run for the main query (needed so
// the FAQ schema queue is populated) but still inside wp_footer.
add_action( 'wp_footer', 'cs_print_schema', 20 );

/**
 * Single-graph fix (Sep 2026 audit): Rank Math prints its JSON-LD in
 * wp_head, but this theme's BreadcrumbList / SoftwareApplication / FAQPage
 * nodes can only be built in wp_footer — the FAQ items don't exist until
 * the_content() and the per-page FAQ helpers have actually rendered. That
 * split is why every inner page carried two separate JSON-LD blocks.
 *
 * Rank Math's JsonLD::json_ld() returns early without printing anything when
 * its final `rank_math/schema/validated_data` filter hands back an empty
 * array. So we take Rank Math's fully built and validated entities here,
 * hold them in a global, and hand back nothing; cs_print_schema() then
 * prints Rank Math's entities and ours together as one @graph from the
 * footer. JSON-LD is valid anywhere in the document, head or body.
 *
 * If a future Rank Math release renames this filter, the capture simply
 * never runs: Rank Math prints its own block as before and the theme prints
 * its own — back to two blocks, but nothing breaks or disappears.
 */
function cs_capture_rank_math_schema( $data ) {
	if ( is_admin() || ! is_array( $data ) || empty( $data ) ) {
		return $data;
	}
	global $cs_rank_math_schema;
	$cs_rank_math_schema = $data;
	return array();
}
add_filter( 'rank_math/schema/validated_data', 'cs_capture_rank_math_schema', 999 );

/**
 * Author identity fix (Sep 2026 audit): Rank Math emits a separate
 * `Person` node for the WordPress post author, with @id / url set to the
 * author archive — /author/6977980gmail-com/, a slug WordPress derived
 * from the account's email address at signup. That URL already 301s to the
 * homepage (inc/redirects.php) and the archive itself has no content, so
 * it's the wrong identity to publish in structured data.
 *
 * This drops the author-archive Person node and points every `author`
 * reference (archive URL, or the "Computing Skill" publisher node) at the
 * site's real author, Ali Ahmad: a Person node from cs_site_author()
 * (inc/author.php) with url → /about-us/ and worksFor → the publisher
 * Organization (Oct 2026 audit, FIX-15). Publisher stays "Computing Skill".
 * The email-derived slug never appears in any page's schema.
 */
function cs_schema_unify_author( $graphs ) {
	$author_urls = array();
	if ( is_singular() ) {
		$author_id = (int) get_post_field( 'post_author' );
		if ( $author_id ) {
			$author_urls[] = get_author_posts_url( $author_id );
		}
	}

	// Find the publisher node's @id (Rank Math: "<home>/#person").
	$publisher_id = '';
	foreach ( $graphs as $node ) {
		if ( ! is_array( $node ) || empty( $node['@id'] ) ) {
			continue;
		}
		$types = isset( $node['@type'] ) ? (array) $node['@type'] : array();
		if ( in_array( 'Organization', $types, true ) && false !== strpos( $node['@id'], '#' ) ) {
			$publisher_id = $node['@id'];
			break;
		}
	}
	if ( '' === $publisher_id ) {
		$publisher_id = home_url( '/#person' );
	}

	// Any node whose @id is an /author/ archive URL is the author Person node.
	$is_author_url = function ( $value ) use ( $author_urls ) {
		if ( ! is_string( $value ) ) {
			return false;
		}
		return in_array( $value, $author_urls, true ) || false !== strpos( $value, '/author/' );
	};

	$clean = array();
	foreach ( $graphs as $node ) {
		if ( is_array( $node ) && isset( $node['@id'] ) && $is_author_url( $node['@id'] ) ) {
			continue; // Drop the author-archive Person node.
		}
		$clean[] = $node;
	}

	$person_id   = cs_site_author()['id'];
	$used_person = false;
	$rewrite     = function ( $value ) use ( &$rewrite, &$used_person, $is_author_url, $publisher_id, $person_id ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}
		foreach ( $value as $key => $child ) {
			if ( 'author' === $key && is_array( $child ) ) {
				$ref = isset( $child['@id'] ) ? $child['@id'] : ( isset( $child['url'] ) ? $child['url'] : '' );
				if ( $is_author_url( $ref ) || $publisher_id === $ref || $person_id === $ref ) {
					$value[ $key ] = array( '@id' => $person_id );
					$used_person   = true;
					continue;
				}
			}
			$value[ $key ] = $rewrite( $child );
		}
		return $value;
	};

	$clean = array_values( $rewrite( $clean ) );

	// Add the author Person node wherever something references it, and on
	// About Us (the author's profile page).
	$has_person = false;
	foreach ( $clean as $node ) {
		if ( is_array( $node ) && isset( $node['@id'] ) && $person_id === $node['@id'] ) {
			$has_person = true;
		}
	}
	if ( ! $has_person && ( $used_person || ( is_singular( 'page' ) && 'about-us' === get_post_field( 'post_name' ) ) ) ) {
		$clean[] = cs_site_author_schema_node( $publisher_id );
	}

	return $clean;
}

/**
 * Rebuilds the same trail cs_breadcrumbs() renders, as schema items.
 * Kept independent of the HTML renderer to avoid parsing HTML back out.
 */
function cs_build_breadcrumb_schema_items() {
	$trail = array();
	$trail[] = array( 'name' => __( 'Home', 'computingskill' ), 'url' => home_url( '/' ) );

	if ( is_front_page() ) {
		return array();
	} elseif ( is_category() || is_tax() ) {
		$term    = get_queried_object();
		$trail[] = array( 'name' => $term->name, 'url' => get_term_link( $term ) );
	} elseif ( is_post_type_archive() ) {
		$trail[] = array( 'name' => post_type_archive_title( '', false ), 'url' => get_post_type_archive_link( get_query_var( 'post_type' ) ) );
	} elseif ( is_singular() ) {
		$post_type   = get_post_type();
		$archive_map = array(
			'cs_calculator' => array( __( 'Calculators', 'computingskill' ), '/calculators/' ),
			'cs_tool'       => array( __( 'Computer Tools', 'computingskill' ), '/computer-tools/' ),
			'cs_hardware'   => array( __( 'Hardware', 'computingskill' ), '/hardware/' ),
			'cs_guide'      => array( __( 'Guides', 'computingskill' ), '/guides/' ),
			'cs_comparison' => array( __( 'Comparisons', 'computingskill' ), '/comparisons/' ),
			'cs_resource'   => array( __( 'Blog', 'computingskill' ), '/blog/' ), // /resources/ 301s to /blog/ (inc/redirects.php).
			'cs_bottleneck' => array( __( 'Bottleneck Comparisons', 'computingskill' ), '/bottleneck/' ),
			'cs_game'       => array( __( 'Games', 'computingskill' ), '/games/' ),
		);
		if ( isset( $archive_map[ $post_type ] ) ) {
			$trail[] = array( 'name' => $archive_map[ $post_type ][0], 'url' => home_url( $archive_map[ $post_type ][1] ) );
		}
		$trail[] = array( 'name' => get_the_title(), 'url' => get_permalink() );
	} else {
		return array();
	}

	$items = array();
	foreach ( $trail as $i => $crumb ) {
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => $i + 1,
			'name'     => $crumb['name'],
			'item'     => $crumb['url'],
		);
	}
	return $items;
}

/**
 * True when the current singular post is a calculator-type page: either
 * the cs_calculator CPT, or a regular Page whose content contains a
 * [cs_calculator] shortcode.
 */
function cs_is_calculator_page() {
	if ( is_singular( 'cs_calculator' ) || is_singular( 'cs_bottleneck' ) ) {
		return true;
	}
	global $post;
	return $post && has_shortcode( $post->post_content, 'cs_calculator' );
}


/**
 * Page-type fix (Sep 2026 audit). Rank Math applies its default "Article"
 * type to every Page, so calculators, category hubs and legal pages were
 * all described as articles, and the site identity was typed as both a
 * Person and an Organization. This keeps Article only where the page
 * really is editorial (guides, hardware explainers, comparisons, game
 * requirement write-ups, methodology) and otherwise:
 *  - calculators / tools / bottleneck pages: no Article — the page is
 *    described by WebPage + WebApplication (+ breadcrumbs, FAQ);
 *  - category hubs: WebPage → CollectionPage, no Article;
 *  - About Us → AboutPage, Contact → ContactPage, legal pages → plain
 *    WebPage, none with Article;
 *  - the "Computing Skill" publisher node → Organization only (it's a
 *    site/brand, not a person). The @id is left as Rank Math set it so every
 *    reference to it keeps resolving.
 */
function cs_schema_page_kind() {
	if ( ! is_singular() ) {
		return 'other';
	}
	if ( function_exists( 'cs_is_calculator_page' ) && cs_is_calculator_page() ) {
		return 'app';
	}
	$slug = get_post_field( 'post_name' );
	$hubs = array( 'blog', 'calculators', 'computer-tools', 'storage-tools', 'internet-tools', 'display-tools', 'hardware', 'guides', 'comparisons', 'bottleneck' );
	if ( in_array( $slug, $hubs, true ) ) {
		return 'hub';
	}
	if ( 'about-us' === $slug ) {
		return 'about';
	}
	if ( 'contact' === $slug ) {
		return 'contact';
	}
	if ( in_array( $slug, array( 'privacy-policy', 'disclaimer' ), true ) ) {
		return 'legal';
	}
	return 'article';
}

function cs_schema_normalize_types( $graphs ) {
	$kind          = cs_schema_page_kind();
	$drop_article  = in_array( $kind, array( 'app', 'hub', 'about', 'contact', 'legal' ), true );
	$webpage_types = array( 'hub' => 'CollectionPage', 'about' => 'AboutPage', 'contact' => 'ContactPage' );
	$article_types = array( 'Article', 'BlogPosting', 'NewsArticle' );

	$out = array();
	foreach ( $graphs as $node ) {
		if ( ! is_array( $node ) ) {
			$out[] = $node;
			continue;
		}
		$types = isset( $node['@type'] ) ? (array) $node['@type'] : array();

		if ( $drop_article && array_intersect( $types, $article_types ) ) {
			continue;
		}
		if ( in_array( 'Person', $types, true ) && in_array( 'Organization', $types, true ) ) {
			$node['@type'] = 'Organization';
		}
		if ( isset( $webpage_types[ $kind ] ) && array( 'WebPage' ) === $types ) {
			$node['@type'] = $webpage_types[ $kind ];
		}
		$out[] = $node;
	}
	return $out;
}
