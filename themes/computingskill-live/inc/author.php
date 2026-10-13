<?php
/**
 * The site's named author (Oct 2026 audit, FIX-15).
 *
 * Every byline and the schema `author` used to show the WordPress account's
 * display name, "Computing Skill" — the brand, not a person. The site is run
 * by Ali Ahmad, so the author identity now comes from here, in the theme,
 * instead of depending on a wp-admin profile setting. "Computing Skill"
 * stays the publisher (Organization) in schema and in the footer.
 *
 * Only facts Ali Ahmad has given go in here. cs_site_author()['bio'] stays
 * empty until he supplies his own lines; never fill it with invented
 * background, credentials or experience.
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The public author: name, role, profile URL (About Us — the author
 * archive 301s to the homepage, see inc/redirects.php), schema @id, and
 * optional bio paragraphs.
 */
function cs_site_author() {
	return array(
		'name'     => 'Ali Ahmad',
		'role'     => __( 'Founder and writer', 'computingskill' ),
		'url'      => home_url( '/about-us/' ),
		'id'       => home_url( '/about-us/#ali-ahmad' ),
		'bio'      => array(),
		// Profiles Ali Ahmad gave as his own; used for schema sameAs and the About Us link.
		'profiles' => array(
			'LinkedIn' => 'https://www.linkedin.com/in/ali-ahmad-chaudhry-12777486/',
		),
	);
}

/**
 * schema.org Person node for the author. worksFor points at the publisher
 * Organization node when its @id is known.
 */
function cs_site_author_schema_node( $publisher_id = '' ) {
	$author = cs_site_author();
	$node   = array(
		'@type'    => 'Person',
		'@id'      => $author['id'],
		'name'     => $author['name'],
		'url'      => $author['url'],
		'jobTitle' => $author['role'],
	);
	if ( ! empty( $author['profiles'] ) ) {
		$node['sameAs'] = array_values( $author['profiles'] );
	}
	if ( '' !== $publisher_id ) {
		$node['worksFor'] = array( '@id' => $publisher_id );
	}
	if ( ! empty( $author['bio'] ) ) {
		$node['description'] = wp_strip_all_tags( implode( ' ', $author['bio'] ) );
	}
	return $node;
}

/**
 * Adds a "Who runs Computing Skill" section to About Us, just above its
 * "Related Pages" heading (or at the end if that heading is ever renamed).
 * The page body lives in the database, so this is done as a content filter,
 * the same way inc/bottleneck.php corrects the Methodology page.
 */
function cs_about_author_section( $content ) {
	if ( ! is_singular( 'page' ) || ! in_the_loop() || ! is_main_query() || 'about-us' !== get_post_field( 'post_name' ) ) {
		return $content;
	}
	if ( false !== strpos( $content, 'id="who-runs-computing-skill"' ) ) {
		return $content;
	}

	$author = cs_site_author();

	$html  = '<h2 id="who-runs-computing-skill">' . esc_html__( 'Who Runs Computing Skill', 'computingskill' ) . '</h2>';
	$html .= '<p>' . sprintf(
		/* translators: 1: author name (bold), 2: author role, lowercase, 3: author name */
		esc_html__( 'Computing Skill is an independent site run by %1$s, its %2$s. The “By %3$s” line at the top of each page links here.', 'computingskill' ),
		'<strong>' . esc_html( $author['name'] ) . '</strong>',
		esc_html( strtolower( $author['role'] ) ),
		esc_html( $author['name'] )
	) . '</p>';
	foreach ( $author['bio'] as $line ) {
		$html .= '<p>' . esc_html( $line ) . '</p>';
	}
	if ( ! empty( $author['profiles'] ) ) {
		$links = array();
		foreach ( $author['profiles'] as $label => $url ) {
			$links[] = '<a href="' . esc_url( $url ) . '" rel="me noopener" target="_blank">' . esc_html( $label ) . '</a>';
		}
		$html .= '<p>' . sprintf(
			/* translators: 1: author name, 2: profile link(s) */
			esc_html__( 'Find %1$s on %2$s.', 'computingskill' ),
			esc_html( $author['name'] ),
			implode( ', ', $links )
		) . '</p>';
	}
	$html .= '<p>' . sprintf(
		/* translators: %s: contact page link */
		esc_html__( 'Everything described on this page, from estimates instead of benchmark claims to correcting errors once they are confirmed, applies to what is published here. Spotted a mistake? Send it through the %s page.', 'computingskill' ),
		'<a href="' . esc_url( home_url( '/contact/' ) ) . '">' . esc_html__( 'Contact', 'computingskill' ) . '</a>'
	) . '</p>';

	$replaced = preg_replace(
		'#<h([2-3])\b[^>]*>\s*Related Pages\s*</h\1>#i',
		$html . '$0',
		$content,
		1,
		$count
	);
	return $count ? $replaced : $content . $html;
}
add_filter( 'the_content', 'cs_about_author_section', 9 );
