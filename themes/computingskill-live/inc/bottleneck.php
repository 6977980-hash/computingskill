<?php
/**
 * Bottleneck estimate engine (server-side mirror of assets/js/calculators.js
 * "bottleneck" logic) — used to render the programmatic /bottleneck/*
 * pages so search engines see the full estimate in the HTML, not just
 * after JS runs.
 *
 * This is a deliberately simple, transparent, labeled ESTIMATE model: it
 * compares each part's relative performance score (see data/hardware-data.php)
 * and weights that gap differently per resolution, because GPU load
 * increases and CPU load's share of the frame-time budget shrinks as
 * resolution rises. It is not a benchmark result.
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cs_calculate_bottleneck( $cpu, $gpu ) {
	$gap = $gpu['score'] - $cpu['score']; // > 0: GPU relatively stronger than CPU (CPU-bound).

	// Fixed during Sep 2026 audit — kept in sync with the JS mirror in
	// assets/js/calculators.js's bottleneckEstimate(). A single sensitivity
	// curve (1.5 / 1.0 / 0.55 going 1080p -> 4K) was being applied regardless
	// of which part was the constraint. That's correct for a CPU-bound pair
	// (CPU load is roughly resolution-independent, so a weak CPU matters
	// less as resolution rises and GPU load dominates), but backwards for a
	// GPU-bound pair, where GPU workload scales UP with resolution, so a
	// weak GPU should look like a bigger bottleneck at 4K, not a smaller
	// one. Every generated /bottleneck/ page for a GPU-bound pairing was
	// showing the estimate shrinking from 1080p to 4K when it should grow.
	$cpu_bound_sensitivity = array(
		'1080p' => 1.5,
		'1440p' => 1.0,
		'4k'    => 0.55,
	);
	$gpu_bound_sensitivity = array(
		'1080p' => 0.55,
		'1440p' => 1.0,
		'4k'    => 1.5,
	);

	$abs_gap        = abs( $gap );
	$base_component = $gap > 0 ? 'cpu' : ( $gap < 0 ? 'gpu' : 'balanced' );
	$sensitivity    = 'cpu' === $base_component ? $cpu_bound_sensitivity : $gpu_bound_sensitivity;

	// Cap raised from 40 to 60 during the Sep 2026 audit: a flat 40% ceiling
	// meant any sufficiently mismatched pair (e.g. a budget Ryzen 5 5600
	// with a flagship RTX 5090) hit the cap at BOTH 1080p and 1440p, making
	// those two resolutions show the identical 40% and erasing the very
	// differentiation this per-resolution model exists to show. 60 still
	// keeps the number bounded and plausible (nothing here claims a part is
	// "100% bottlenecked"), but gives large, real score gaps enough room to
	// differ across resolutions again.
	$results = array();
	foreach ( $sensitivity as $res => $multiplier ) {
		$magnitude = $abs_gap * $multiplier;
		$percent   = (int) min( 60, round( $magnitude * 0.6 ) );
		$component = $base_component;

		if ( $magnitude < 4 ) {
			$component = 'balanced';
			$percent   = min( $percent, 5 );
		}

		$results[ $res ] = array(
			'percent'   => $percent,
			'component' => $component,
		);
	}

	return $results;
}

/**
 * Human-readable verdict sentence for a resolution's result.
 */
function cs_bottleneck_sentence( $cpu_name, $gpu_name, $res_label, $result ) {
	if ( 'balanced' === $result['component'] ) {
		return sprintf(
			/* translators: 1: CPU name, 2: GPU name, 3: resolution label */
			__( 'At %3$s, the %1$s and %2$s are well matched — neither part is holding the other back in any meaningful way for most games.', 'computingskill' ),
			$cpu_name,
			$gpu_name,
			$res_label
		);
	}

	if ( 'cpu' === $result['component'] ) {
		return sprintf(
			/* translators: 1: CPU name, 2: GPU name, 3: resolution label, 4: percent */
			__( 'At %3$s, the %1$s is the more likely limiting factor — our estimate puts the CPU-side bottleneck at roughly %4$d%%. The %2$s may not reach its full potential in CPU-heavy or high-framerate titles at this resolution.', 'computingskill' ),
			$cpu_name,
			$gpu_name,
			$res_label,
			$result['percent']
		);
	}

	return sprintf(
		/* translators: 1: CPU name, 2: GPU name, 3: resolution label, 4: percent */
		__( 'At %3$s, the %2$s is the more likely limiting factor — our estimate puts the GPU-side bottleneck at roughly %4$d%%. The %1$s has more headroom than the graphics card can use at this resolution.', 'computingskill' ),
		$cpu_name,
		$gpu_name,
		$res_label,
		$result['percent']
	);
}

/**
 * Slug helper: builds the working /bottleneck/{cpu}-{gpu}/ URL.
 *
 * Fixed during Sep 2026 audit: this used to build a nested
 * /bottleneck/{cpu}/{gpu}/ path, matching a custom two-segment rewrite
 * rule in inc/cpt.php (cs_bottleneck_rewrite_rules). Live testing showed
 * that rule never actually resolves (the nested URL silently rendered the
 * homepage instead) — most likely because it depends on a rewrite-rules
 * flush that only runs on theme *activation*, not on every theme-file
 * update, so a live site that's had this theme active since before this
 * rule existed never picks it up. The single-segment slug below needs no
 * custom rewrite at all — it's just the CPT's own post_name (set to
 * "{cpu}-{gpu}" by the WXR import), matched by the standard rewrite
 * WordPress already generates for the `cs_bottleneck` post type — and was
 * confirmed working live for every existing combo page. This is the only
 * URL-building path used anywhere in the theme (front-page.php and the
 * browse-all-combinations index below both call this function), so fixing
 * it here fixes every generated link site-wide in one place.
 */
function cs_bottleneck_url( $cpu_slug, $gpu_slug ) {
	return home_url( '/bottleneck/' . $cpu_slug . '-' . $gpu_slug . '/' );
}

/**
 * Prints a "browse every CPU + GPU combination" link grid, grouped by CPU,
 * covering every published cs_bottleneck page. Call from a page template
 * right after the_content() on the page(s) this should appear on (see
 * cs_maybe_print_bottleneck_index()'s eligible-slugs list) — never by
 * hand-editing live page content, so this works for every combo added in
 * the future too.
 *
 * Added during the Sep 2026 audit: all 30 bottleneck combo pages were
 * found to be internal-linking orphans — reachable only via the XML
 * sitemap or a direct URL, with zero click-path from normal site
 * browsing (the only on-page attempt, the homepage's 6 example links, was
 * itself broken — see cs_bottleneck_url() above). This is the fix: a
 * single real index of links to every combo page.
 */
function cs_maybe_print_bottleneck_index() {
	// 'bottleneck' added during the Sep 2026 audit: /bottleneck/ itself
	// (the CPT's own rewrite base) had no Page at that slug at all, so it
	// 404'd — the CPT has has_archive off by design (see inc/cpt.php), same
	// as every other calculator/tool/hardware/guide category, and relies on
	// a real Page at the category's own slug instead. That Page now exists
	// (content/pages/bottleneck.html) and lists every combo through this
	// same index.
	$eligible_slugs = array( 'pc-bottleneck-calculator', 'cpu-bottleneck-explained', 'gpu-bottleneck-explained', 'bottleneck' );
	$slug           = get_post_field( 'post_name' );
	if ( ! in_array( $slug, $eligible_slugs, true ) ) {
		return;
	}

	$posts = get_posts( array(
		'post_type'      => 'cs_bottleneck',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	) );

	if ( empty( $posts ) ) {
		return;
	}

	echo '<div class="cs-related cs-bottleneck-index">';
	echo '<h2>' . esc_html__( 'Browse Every CPU + GPU Combination', 'computingskill' ) . '</h2>';
	echo '<ul class="cs-related-grid">';
	foreach ( $posts as $post ) {
		echo '<li><a href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html( get_the_title( $post ) ) . '</a></li>';
	}
	echo '</ul>';
	if ( 'bottleneck' !== $slug ) {
		echo '<p class="cs-related-more"><a href="' . esc_url( home_url( '/bottleneck/' ) ) . '">' . esc_html__( 'All bottleneck comparisons →', 'computingskill' ) . '</a></p>';
	}
	echo '</div>';
}
