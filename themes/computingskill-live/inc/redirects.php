<?php
/**
 * Site-wide 301 redirects added during the Sep 2026 audit.
 *
 * These consolidate confirmed duplicate pages and disable a stock
 * WordPress archive that never had a chance of being useful on this
 * site — done as explicit 301s per project rule (never change a live
 * URL's behaviour silently), and never by deleting any page: every old
 * page/post stays in the database exactly as before, only the live URL
 * now forwards.
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The single source of truth for "this page slug now lives at that page
 * slug". Used by the 301 below, by [cs_related] (so internal links point
 * straight at the destination instead of through a redirect), by the blog
 * hub's content index, and by the Rank Math sitemap filter (so redirected
 * URLs stop being announced to search engines and crawlers).
 *
 * - screen-size / pixel-density / drive-space: confirmed literal
 *   duplicates. Each pair shares the EXACT same calculator definition in
 *   assets/js/calculators.js (screenSizeFields + screenSizeCompute;
 *   densityFields + densityCompute) or the same [cs_calculator
 *   type="storage"] mount. The more commonly searched slug is kept.
 * - resources -> blog: /resources/ was a short "start here" page sampling
 *   the same sections that /blog/ already lists completely (every
 *   calculator, tool, hardware page, guide and comparison, searchable, with
 *   ItemList schema). Two hub pages for one job split the internal links;
 *   the complete, stronger page keeps its URL and the thin one 301s to it.
 */
function cs_get_redirect_map() {
	return array(
		'screen-size-calculator'   => 'monitor-size-calculator',
		'pixel-density-calculator' => 'ppi-calculator',
		'drive-space-calculator'   => 'storage-calculator',
		'resources'                => 'blog',
	);
}

/**
 * Resolves a slug through the redirect map (returns it unchanged if it
 * isn't redirected).
 */
function cs_canonical_slug( $slug ) {
	$map = cs_get_redirect_map();
	return isset( $map[ $slug ] ) ? $map[ $slug ] : $slug;
}

function cs_redirect_duplicate_calculators() {
	if ( ! is_singular() ) {
		return;
	}

	global $post;
	if ( ! $post ) {
		return;
	}

	$map = cs_get_redirect_map();
	if ( isset( $map[ $post->post_name ] ) ) {
		wp_safe_redirect( home_url( '/' . $map[ $post->post_name ] . '/' ), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'cs_redirect_duplicate_calculators', 5 );

/**
 * Keep redirected URLs out of the Rank Math XML sitemap. They were still
 * listed after the 301s went live, so crawlers (and the audit tool) kept
 * requesting them, followed the 301, and reported "two URLs with the same
 * title" — the sitemap should only ever list final destination URLs.
 * Rank Math skips any entry this filter returns empty for.
 */
function cs_sitemap_skip_redirected( $url, $type, $object ) {
	if ( 'post' !== $type || ! is_object( $object ) || empty( $object->post_name ) ) {
		return $url;
	}
	$map = cs_get_redirect_map();
	if ( isset( $map[ $object->post_name ] ) ) {
		return false;
	}
	return $url;
}
add_filter( 'rank_math/sitemap/entry', 'cs_sitemap_skip_redirected', 10, 3 );

/**
 * The author archive (/author/{nicename}/) is a stock WordPress feature
 * that only ever queries the 'post' post type. Every content page on
 * this site is a Page or custom post type, so the archive is always
 * empty ("No pages found yet") no matter which user it's for — and its
 * URL slug is derived from the WP user's nicename, which (unlike the
 * display name) doesn't update when the account's display name changes,
 * which is why it still reads /author/6977980gmail-com/ after the
 * display name was changed to "Computing Skill". Rather than editing the
 * nicename (a second URL to manage) or trying to populate an archive
 * this site structurally can't use, it's redirected to the homepage.
 * (The same URL is also removed from structured data — see
 * cs_schema_unify_author() in inc/schema.php.)
 */
function cs_redirect_author_archive() {
	if ( is_author() ) {
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'cs_redirect_author_archive', 5 );

/**
 * Old Blogger URL patterns (Sep 2026 audit). This domain was previously a
 * Blogger/Blogspot blog, whose label pages lived at /search/label/{Label}.
 * On WordPress those URLs fall through to a search-results page and return
 * 200 with unrelated results, so they 301 to the article hub instead.
 */
function cs_redirect_old_blogger_urls() {
	$path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
	if ( is_string( $path ) && preg_match( '#^/search/label/#i', $path ) ) {
		wp_safe_redirect( home_url( '/blog/' ), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'cs_redirect_old_blogger_urls', 1 );

/**
 * Internal-link hygiene (Sep 2026 audit): 14 links written into page
 * content as href="/some-page" (no trailing slash) each cost visitors and
 * crawlers an extra redirect hop to "/some-page/", WordPress's canonical
 * form. Rather than hand-editing each page, internal page links without a
 * trailing slash get one at render time. Only path-only links to this site
 * are touched — anything with a file extension, query string or fragment
 * is left exactly as written.
 */
function cs_add_trailing_slash_to_internal_links( $content ) {
	if ( false === strpos( $content, 'href=' ) ) {
		return $content;
	}
	$host = preg_quote( untrailingslashit( home_url() ), '#' );
	return preg_replace_callback(
		'#href=(["\'])((?:' . $host . ')?/[a-z0-9][a-z0-9/_-]*?[a-z0-9_-])\1#i',
		function ( $m ) {
			return 'href=' . $m[1] . $m[2] . '/' . $m[1];
		},
		$content
	);
}
add_filter( 'the_content', 'cs_add_trailing_slash_to_internal_links', 20 );
