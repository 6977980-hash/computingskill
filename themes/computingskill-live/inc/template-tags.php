<?php
/**
 * Reusable template helper functions (card grids, meta rows, etc).
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders a simple card linking to a page/post, with optional description.
 */
function cs_card( $title, $url, $description = '', $icon = '' ) {
	echo '<a class="cs-card" href="' . esc_url( $url ) . '">';
	if ( $icon ) {
		echo '<span class="cs-card-icon" aria-hidden="true">' . esc_html( $icon ) . '</span>';
	}
	echo '<span class="cs-card-title">' . esc_html( $title ) . '</span>';
	if ( $description ) {
		echo '<span class="cs-card-desc">' . esc_html( $description ) . '</span>';
	}
	echo '</a>';
}

/**
 * Renders a grid of cards from an array of ['title','url','desc','icon'].
 */
function cs_card_grid( $items ) {
	echo '<div class="cs-card-grid">';
	foreach ( $items as $item ) {
		cs_card( $item['title'], $item['url'], isset( $item['desc'] ) ? $item['desc'] : '', isset( $item['icon'] ) ? $item['icon'] : '' );
	}
	echo '</div>';
}

/**
 * Prints the "estimate disclaimer" strip used on every calculator result
 * area, satisfying the "never present fake scientific precision" rule.
 */
function cs_estimate_disclaimer( $context = 'calculation' ) {
	echo '<p class="cs-disclaimer"><strong>' . esc_html__( 'Estimate only:', 'computingskill' ) . '</strong> ' . esc_html__( 'this ' . $context . ' is a simplified model meant to guide your decision, not a lab-measured result. Real-world numbers vary by brand, drivers, cooling, game engine and settings.', 'computingskill' ) . '</p>';
}

/**
 * Renders a "Sources:" citation line linking out to the manufacturer's own
 * product/specifications page for one or more hardware parts, added during
 * the Sep 2026 trust/AI-search audit so every page showing a spec figure
 * (bottleneck pages, the [cs_hardware_table] shortcode) links back to where
 * that figure came from, not just an unlinked "manufacturer data" claim.
 *
 * @param array $parts Each item: array( 'name' => string, 'source_url' => string|null ).
 */
function cs_hardware_source_line( $parts ) {
	$links = array();
	foreach ( $parts as $part ) {
		if ( empty( $part['source_url'] ) || empty( $part['name'] ) ) {
			continue;
		}
		$links[] = '<a href="' . esc_url( $part['source_url'] ) . '" rel="nofollow noopener" target="_blank">' . esc_html( $part['name'] ) . '</a>';
	}

	if ( empty( $links ) ) {
		return;
	}

	echo '<p class="cs-disclaimer cs-source-line"><strong>' . esc_html__( 'Sources:', 'computingskill' ) . '</strong> ' . esc_html__( 'manufacturer specifications for', 'computingskill' ) . ' ' . implode( ', ', $links ) . '.</p>'; // phpcs:ignore WordPress.Security.EscapeOutput -- $links built from esc_url()/esc_html() above.
}

/**
 * Post-type archive intro copy, keyed by post type, used in archive.php.
 */
function cs_archive_intro( $post_type ) {
	$copy = array(
		'cs_calculator' => array(
			'title' => __( 'PC Hardware Calculators', 'computingskill' ),
			'intro' => __( 'Free, instant calculators for every stage of building, buying or upgrading a PC — bottleneck estimates, power supply sizing, FPS estimates, RAM sizing and more. Every result includes a plain-English explanation of what it means and how it was calculated.', 'computingskill' ),
		),
		'cs_tool' => array(
			'title' => __( 'Computer Tools', 'computingskill' ),
			'intro' => __( 'Quick converters and utilities for everyday computing questions: storage units, data transfer speeds, download times and more.', 'computingskill' ),
		),
		'cs_hardware' => array(
			'title' => __( 'Hardware', 'computingskill' ),
			'intro' => __( 'Plain-English reference pages covering every major PC component — what it does, how to read its spec sheet, and how to choose one.', 'computingskill' ),
		),
		'cs_guide' => array(
			'title' => __( 'Guides', 'computingskill' ),
			'intro' => __( 'In-depth, practical guides for building, upgrading and troubleshooting a PC — written for beginners, useful for enthusiasts.', 'computingskill' ),
		),
		'cs_comparison' => array(
			'title' => __( 'Comparisons', 'computingskill' ),
			'intro' => __( 'Side-by-side breakdowns to help you choose between two technologies, formats or components.', 'computingskill' ),
		),
		'cs_resource' => array(
			'title' => __( 'Resources', 'computingskill' ),
			'intro' => __( 'Supporting reference material for the calculators and guides on ComputingSkill.', 'computingskill' ),
		),
	);
	return isset( $copy[ $post_type ] ) ? $copy[ $post_type ] : array( 'title' => post_type_archive_title( '', false ), 'intro' => '' );
}


/**
 * "By Computing Skill · Last updated <date>" line under each page's H1
 * (Sep 2026 audit: no page showed who wrote it or when it was last
 * revised). The date is the post's real last-modified date from
 * WordPress, never a hand-set "freshness" date. The name links to About
 * Us rather than the author archive, which 301s to the homepage (see
 * inc/redirects.php).
 */
function cs_article_meta() {
	if ( ! is_singular() || is_front_page() ) {
		return;
	}
	$author_id = (int) get_post_field( 'post_author' );
	$name      = $author_id ? get_the_author_meta( 'display_name', $author_id ) : '';
	if ( '' === $name ) {
		$name = get_bloginfo( 'name' );
	}
	$modified_iso  = get_the_modified_date( 'c' );
	$modified_nice = get_the_modified_date( get_option( 'date_format' ) );
	?>
	<p class="cs-article-meta">
		<?php esc_html_e( 'By', 'computingskill' ); ?>
		<a href="<?php echo esc_url( home_url( '/about-us/' ) ); ?>"><?php echo esc_html( $name ); ?></a>
		<span aria-hidden="true">·</span>
		<?php esc_html_e( 'Last updated', 'computingskill' ); ?>
		<time datetime="<?php echo esc_attr( $modified_iso ); ?>"><?php echo esc_html( $modified_nice ); ?></time>
	</p>
	<?php
}
