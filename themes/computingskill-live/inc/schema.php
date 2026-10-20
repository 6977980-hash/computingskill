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
			// @id, creator and publisher added in D6 (2.12) so the tool is
			// tied to its page, its author and the site.
			'@type'                 => 'WebApplication',
			'@id'                   => get_permalink() . '#app',
			'browserRequirements'   => 'Requires JavaScript',
			'name'                  => get_the_title(),
			'applicationCategory'   => 'UtilitiesApplication',
			'operatingSystem'       => 'Any (runs in browser)',
			'url'                   => get_permalink(),
			'isAccessibleForFree'   => true,
			'mainEntityOfPage'      => array( '@id' => get_permalink() . '#webpage' ),
			'creator'               => array( '@id' => cs_site_author()['id'] ),
			'publisher'             => array( '@id' => cs_org_schema_id() ),
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
	// E3 (ONP-04): the homepage FAQ (front-page.php) is marked up too.
	if ( cs_schema_should_emit( 'FAQPage' ) && ( is_singular() || is_front_page() ) && ! is_page( $cs_faq_schema_excluded_slugs ) ) {
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
	if ( is_page( 'blog' ) && cs_schema_should_emit( 'ItemList' ) && function_exists( 'cs_blog_article_items' ) ) {
		$flat = cs_blog_article_items(); // D7 (2.22): the articles the page shows.
		if ( ! empty( $flat ) ) {
			$list_items = array();
			$position   = 0;
			foreach ( $flat as $entry ) {
				$position++;
				$list_items[] = array(
					'@type'    => 'ListItem',
					'position' => $position,
					'url'      => $entry['url'],
					'name'     => wp_strip_all_tags( $entry['title'] ),
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

	// ItemList on the other hubs (D6, 2.14): /bottleneck/, /games/,
	// /calculators/ and the tool/hardware/guide hubs, listing exactly the
	// pages each hub links to (see cs_hub_list_items()).
	if ( cs_schema_should_emit( 'ItemList' ) && ! is_page( 'blog' ) ) {
		$hub_items = cs_hub_list_items();
		if ( ! empty( $hub_items ) ) {
			$list_items = array();
			foreach ( array_values( $hub_items ) as $i => $entry ) {
				$list_items[] = array(
					'@type'    => 'ListItem',
					'position' => $i + 1,
					'url'      => $entry['url'],
					'name'     => $entry['name'],
				);
			}
			$graphs[] = array(
				'@type'           => 'ItemList',
				'name'            => is_post_type_archive() ? post_type_archive_title( '', false ) : wp_strip_all_tags( get_the_title() ),
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
			$g         = $games_data[ $slug ];
			$game_node = array(
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
				// D6 (2.15): these pages are about the PC version.
				'gamePlatform'     => 'PC',
			);
			if ( ! empty( $g['publisher'] ) ) {
				$game_node['publisher'] = array(
					'@type' => 'Organization',
					'name'  => $g['publisher'],
				);
			}
			// No box art is used on the site (see data/games-data.php), so the
			// image is the page's own share card, when one could be drawn.
			$card = function_exists( 'cs_og_card_url' ) ? cs_og_card_url() : '';
			if ( $card ) {
				$game_node['image'] = $card;
			}
			$graphs[] = $game_node;
		}
	}

	// Merge Rank Math's own entities (captured and held back from wp_head by
	// cs_capture_rank_math_schema() below) into this same graph, so every
	// page ships exactly ONE JSON-LD block instead of two.
	global $cs_rank_math_schema;
	if ( ! empty( $cs_rank_math_schema ) && is_array( $cs_rank_math_schema ) ) {
		$graphs = array_merge( array_values( $cs_rank_math_schema ), $graphs );
	}

	// WebPage with author and dates for combo and game pages (Oct 2026),
	// and since E3 (ONP-01) the part and comparison pages too.
	// Rank Math outputs no WebPage/Article node for these post types
	// (checked live 13 and 20 Oct 2026), so this cannot duplicate one; the
	// guard below skips it if a future Rank Math setting adds its own.
	if ( is_singular( array( 'cs_bottleneck', 'cs_game', 'cs_hardware', 'cs_comparison' ) ) ) {
		$has_page = false;
		foreach ( $graphs as $node ) {
			$types = is_array( $node ) && isset( $node['@type'] ) ? (array) $node['@type'] : array();
			if ( array_intersect( $types, array( 'WebPage', 'Article', 'BlogPosting', 'CollectionPage' ) ) ) {
				$has_page = true;
				break;
			}
		}
		if ( ! $has_page ) {
			$page_node = array(
				'@type'         => 'WebPage',
				'@id'           => get_permalink() . '#webpage',
				'url'           => get_permalink(),
				'name'          => wp_strip_all_tags( get_the_title() ),
				'datePublished' => get_the_date( 'c' ),
				'dateModified'  => get_the_modified_date( 'c' ),
				'inLanguage'    => get_bloginfo( 'language' ),
				'author'        => array( '@id' => cs_site_author()['id'] ),
				'publisher'     => array(
					'@type' => 'Organization',
					'@id'   => cs_org_schema_id(),
					'name'  => get_bloginfo( 'name' ),
					'url'   => home_url( '/' ),
				),
			);
			if ( is_singular( array( 'cs_hardware', 'cs_comparison' ) ) ) {
				// The excerpt is the page's meta description (inc/part-pages.php).
				$excerpt = wp_strip_all_tags( get_the_excerpt() );
				if ( '' !== $excerpt ) {
					$page_node['description'] = $excerpt;
				}
				// The page's own share card, the image these pages show in
				// og:image (inc/technical-seo.php).
				$card = function_exists( 'cs_og_card_url' ) ? cs_og_card_url() : '';
				if ( $card ) {
					$page_node['primaryImageOfPage'] = array(
						'@type'  => 'ImageObject',
						'url'    => $card,
						'width'  => '1200',
						'height' => '630',
					);
				}
			}
			$graphs[] = $page_node;
		}
	}

	// D8: extra nodes from other files (tier list Dataset).
	$graphs = apply_filters( 'cs_schema_graphs', $graphs );

	if ( empty( $graphs ) ) {
		return;
	}

	$graphs = cs_schema_link_page_author( $graphs );
	$graphs = cs_schema_unify_author( $graphs );
	$graphs = cs_schema_normalize_types( $graphs );
	$graphs = cs_schema_organization( $graphs );
	$graphs = cs_schema_page_image( $graphs );

	$output = array(
		'@context' => 'https://schema.org',
		'@graph'   => $graphs,
	);

	// wp_kses turns "&" into "&amp;", and JSON-LD is not HTML-decoded, so
	// names like "Calculators & Tools" reached Google as "&amp;". Decode the
	// entities back to plain text; JSON_HEX_TAG keeps any "<" escaped.
	$output = map_deep( wp_kses_post_deep( $output ), function ( $value ) {
		return is_string( $value ) ? html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) : $value;
	} );

	echo "\n<script type=\"application/ld+json\" class=\"cs-schema\">" . wp_json_encode( $output, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG ) . "</script>\n";
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
		if ( 'cs_bottleneck' === $post_type ) {
			// U4 (U-38): same trail as the visible one (inc/breadcrumbs.php).
			$trail[] = array( 'name' => __( 'Comparisons', 'computingskill' ), 'url' => home_url( '/comparisons/' ) );
		}
		if ( isset( $archive_map[ $post_type ] ) ) {
			$trail[] = array( 'name' => $archive_map[ $post_type ][0], 'url' => home_url( $archive_map[ $post_type ][1] ) );
		} elseif ( function_exists( 'cs_breadcrumb_parent' ) && ( $parent = cs_breadcrumb_parent() ) ) { // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition
			$trail[] = array( 'name' => $parent[0], 'url' => $parent[1] );
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
	// Combo pages (cs_bottleneck) are not included since D6 (2.10): they show
	// fixed results and no inputs, so they are a WebPage, not a
	// WebApplication. Their inputs live on /pc-bottleneck-calculator/.
	if ( is_singular( 'cs_calculator' ) ) {
		return true;
	}
	global $post;
	// E3 (SEO-04): Can I Run It is a tool with its own shortcode
	// (inc/d10-pages.php), so it is a WebApplication like the others.
	return $post && ( has_shortcode( $post->post_content, 'cs_calculator' ) || has_shortcode( $post->post_content, 'cs_can_i_run' ) );
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
 *    site/brand, not a person), including the homepage, where Rank Math
 *    types it Person only. The @id is left as Rank Math set it so every
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
	if ( in_array( $slug, array( 'privacy-policy', 'disclaimer', 'terms-of-use', 'editorial-policy' ), true ) ) {
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
		// D6 (2.13): Rank Math builds the Article headline from the title
		// tag, so it ended in " - Computing Skill". The headline is the
		// article's own title; the publisher is stated separately.
		if ( array_intersect( $types, $article_types ) ) {
			foreach ( array( 'headline', 'name' ) as $key ) {
				if ( isset( $node[ $key ] ) && is_string( $node[ $key ] ) ) {
					$node[ $key ] = preg_replace( '/\s+[-–|]\s+Computing Skill$/u', '', $node[ $key ] );
				}
			}
			if ( is_singular() && isset( $node['headline'] ) ) {
				$node['headline'] = wp_strip_all_tags( html_entity_decode( get_the_title( get_queried_object_id() ), ENT_QUOTES, 'UTF-8' ) );
			}
		}
		if ( in_array( 'Person', $types, true ) && in_array( 'Organization', $types, true ) ) {
			$node['@type'] = 'Organization';
		}
		// On the homepage Rank Math types the same publisher node as Person
		// only, with the logo under `image`. Same brand node, so same fix.
		if ( array( 'Person' ) === $types && isset( $node['@id'] ) && home_url( '/#person' ) === $node['@id'] ) {
			$node['@type'] = 'Organization';
			if ( isset( $node['image'] ) && ! isset( $node['logo'] ) ) {
				$node['logo'] = $node['image'];
				unset( $node['image'] );
			}
		}
		if ( isset( $webpage_types[ $kind ] ) && array( 'WebPage' ) === $types ) {
			$node['@type'] = $webpage_types[ $kind ];
		}
		$out[] = $node;
	}
	return $out;
}


/**
 * Deep audit D6 (2.11): the site's Organization node used Rank Math's
 * "#person" @id (a leftover of the site once being typed as a Person) and
 * had no url or sameAs. Every reference is moved to "#organization", and
 * the node gets the homepage url and any official profiles listed in
 * cs_org_same_as().
 */
function cs_org_schema_id() {
	return home_url( '/#organization' );
}

/**
 * The site's own official profiles (not the author's), deep audit 3.9.
 * Only profiles Ali has confirmed belong in this list.
 */
function cs_org_same_as() {
	return array();
}

function cs_schema_organization( $graphs ) {
	$old = home_url( '/#person' );
	$new = cs_org_schema_id();
	$swap = function ( $value ) use ( &$swap, $old, $new ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $k => $child ) {
				$value[ $k ] = $swap( $child );
			}
			return $value;
		}
		return $old === $value ? $new : $value;
	};
	$graphs = $swap( $graphs );

	foreach ( $graphs as $i => $node ) {
		if ( ! is_array( $node ) || ! isset( $node['@id'] ) || $new !== $node['@id'] ) {
			continue;
		}
		// Only the full node (the one with a name), not inline references.
		if ( ! isset( $node['name'] ) ) {
			continue;
		}
		$graphs[ $i ]['url'] = home_url( '/' );
		$same_as             = cs_org_same_as();
		if ( $same_as ) {
			$graphs[ $i ]['sameAs'] = array_values( $same_as );
		}
	}
	return $graphs;
}

/**
 * Deep audit D6 (2.12): on calculators, hubs, About, Contact and legal pages
 * the author Person was in the graph but nothing pointed to it. The page's
 * own WebPage node now names Ali Ahmad as author (he writes and maintains
 * every page) unless it already has one; cs_schema_unify_author() then adds
 * the Person node. Runs before cs_schema_unify_author().
 */
function cs_schema_link_page_author( $graphs ) {
	if ( ! is_singular() || ! function_exists( 'cs_site_author' ) ) {
		return $graphs;
	}
	$page_id = get_permalink() . '#webpage';
	foreach ( $graphs as $i => $node ) {
		if ( ! is_array( $node ) || ! isset( $node['@id'] ) || $page_id !== $node['@id'] ) {
			continue;
		}
		if ( empty( $node['author'] ) ) {
			$graphs[ $i ]['author'] = array( '@id' => cs_site_author()['id'] );
		}
		if ( cs_is_calculator_page() && empty( $node['mainEntity'] ) ) {
			$graphs[ $i ]['mainEntity'] = array( '@id' => get_permalink() . '#app' );
		}
	}
	return $graphs;
}

/**
 * Deep audit D6 (2.9, 2.13): Rank Math's ImageObject for a page without a
 * featured image is the site-wide default share image (so the post's
 * BlogPosting image was the default too). Where the page has its own share
 * card (inc/technical-seo.php), the schema uses that card instead.
 */
function cs_schema_page_image( $graphs ) {
	if ( ! function_exists( 'cs_og_card_url' ) || ! class_exists( '\RankMath\Helper' ) ) {
		return $graphs;
	}
	$default_id = (int) \RankMath\Helper::get_settings( 'titles.open_graph_image_id' );
	$default    = $default_id ? wp_get_attachment_image_url( $default_id, 'full' ) : '';
	if ( ! $default ) {
		return $graphs;
	}
	$uses_default = false;
	foreach ( $graphs as $node ) {
		if ( is_array( $node ) && isset( $node['@type'], $node['url'] ) && 'ImageObject' === $node['@type'] && $default === $node['url'] ) {
			$uses_default = true;
		}
	}
	if ( ! $uses_default || is_front_page() ) {
		return $graphs;
	}
	$card = cs_og_card_url();
	if ( ! $card ) {
		return $graphs;
	}
	$spec = cs_og_card_spec();
	$swap = function ( $value ) use ( &$swap, $default, $card ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $k => $child ) {
				$value[ $k ] = $swap( $child );
			}
			return $value;
		}
		return $default === $value ? $card : $value;
	};
	$graphs = $swap( $graphs );
	foreach ( $graphs as $i => $node ) {
		if ( is_array( $node ) && isset( $node['@type'], $node['url'] ) && 'ImageObject' === $node['@type'] && $card === $node['url'] ) {
			$graphs[ $i ]['width']   = '1200';
			$graphs[ $i ]['height']  = '630';
			$graphs[ $i ]['caption'] = $spec['title'];
		}
	}
	return $graphs;
}

/**
 * Pages linked from the current hub, in page order, as name + url (D6,
 * 2.14). /bottleneck/ lists every published combo page (the index printed
 * under its content), /games/ the games the archive shows, and the other
 * hubs the internal pages their content links to (collected while the
 * content renders, by cs_collect_hub_links()).
 */
function cs_hub_list_slugs() {
	return array( 'calculators', 'computer-tools', 'storage-tools', 'internet-tools', 'display-tools', 'hardware', 'guides', 'comparisons' );
}

function cs_hub_list_items() {
	if ( is_post_type_archive( 'cs_game' ) ) {
		global $wp_query;
		$items = array();
		foreach ( (array) $wp_query->posts as $post ) {
			$items[] = array( 'name' => wp_strip_all_tags( get_the_title( $post ) ), 'url' => get_permalink( $post ) );
		}
		return $items;
	}
	if ( is_page( 'bottleneck' ) ) {
		$items = array();
		$posts = get_posts( array(
			'post_type'      => 'cs_bottleneck',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		) );
		foreach ( $posts as $post ) {
			$items[] = array( 'name' => wp_strip_all_tags( get_the_title( $post ) ), 'url' => get_permalink( $post ) );
		}
		return $items;
	}
	if ( is_page( cs_hub_list_slugs() ) ) {
		global $cs_hub_links;
		return is_array( $cs_hub_links ) ? $cs_hub_links : array();
	}
	return array();
}

/**
 * Collects the content links of a hub page for cs_hub_list_items(). Only
 * links to other pages of this site, each once; the author box and policy
 * pages are not part of the list.
 */
function cs_collect_hub_links( $content ) {
	if ( ! is_page( cs_hub_list_slugs() ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	global $cs_hub_links;
	$cs_hub_links = array();
	$skip         = array( 'about-us', 'contact', 'privacy-policy', 'disclaimer', 'terms-of-use', 'editorial-policy', 'methodology', get_post_field( 'post_name' ) );
	$home         = preg_quote( untrailingslashit( home_url() ), '#' );
	if ( preg_match_all( '#<a\s[^>]*href=(["\'])(?:' . $home . ')?/([a-z0-9-]+)/\1[^>]*>(.*?)</a>#is', $content, $m, PREG_SET_ORDER ) ) {
		foreach ( $m as $link ) {
			$slug = $link[2];
			$name = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $link[3] ) ) );
			if ( in_array( $slug, $skip, true ) || isset( $cs_hub_links[ $slug ] ) || '' === $name ) {
				continue;
			}
			$page = get_page_by_path( $slug );
			if ( $page && 'publish' === $page->post_status ) {
				$cs_hub_links[ $slug ] = array( 'name' => html_entity_decode( $name, ENT_QUOTES, 'UTF-8' ), 'url' => get_permalink( $page ) );
			}
		}
	}
	return $content;
}
add_filter( 'the_content', 'cs_collect_hub_links', 99 );
