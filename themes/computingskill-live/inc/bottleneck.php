<?php
/**
 * Bottleneck estimate engine (server-side mirror of assets/js/calculators.js
 * "bottleneck" logic) — used to render the programmatic /bottleneck/*
 * pages so search engines see the full estimate in the HTML, not just
 * after JS runs.
 *
 * This is a deliberately simple, transparent, labeled ESTIMATE model. It
 * is not a benchmark result.
 *
 * How it works (Phase 2 audit fix, Oct 2026 — the JS mirror
 * bottleneckEstimate() in assets/js/calculators.js must stay identical):
 *
 *  1. A CPU's score is its gaming frame-rate ceiling, which barely changes
 *     with resolution. Converted to the GPU scale it is
 *     cpu_score * CS_BN_CPU_PARITY.
 *  2. A GPU's score is its throughput at 1080p. Each resolution has more
 *     pixels to shade, so the GPU's effective score is
 *     gpu_score * CS_BN_GPU_RES_FACTOR[res] (1.00 / 0.68 / 0.42 — the same
 *     resolution factors the FPS calculator uses).
 *  3. The two effective scores are compared SEPARATELY at every
 *     resolution. Whichever is lower is the likely limiting part there, so
 *     a pair can be CPU-bound at 1080p and GPU-bound at 4K.
 *  4. The estimated bottleneck % is the share of the stronger part's
 *     capacity the weaker part leaves unused:
 *     (stronger - weaker) / stronger * 100, rounded, capped at 60.
 *     Under 6% is shown as "balanced".
 *
 * The previous model took ONE score gap and applied it to all three
 * resolutions, so the CPU-vs-GPU direction could never change with
 * resolution (e.g. Ryzen 5 5600 + RTX 4090 read ~16% CPU-bound even at 4K),
 * which contradicted the site's own guides and FAQs.
 *
 * Calibration: 0.85 parity puts a Ryzen 5 5600 + RTX 4070 slightly
 * CPU-bound at 1080p and clearly GPU-bound at 4K, and a Ryzen 7 7700X +
 * RTX 4070 close to balanced at 1080p, in line with published review
 * frame rates for those parts (see data/hardware-data.php's score note).
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'CS_BN_CPU_PARITY' ) ) {
	define( 'CS_BN_CPU_PARITY', 0.85 );
}

function cs_bottleneck_gpu_res_factors() {
	return array(
		'1080p' => 1.0,
		'1440p' => 0.68,
		'4k'    => 0.42,
	);
}

function cs_calculate_bottleneck( $cpu, $gpu ) {
	$cpu_eff = $cpu['score'] * CS_BN_CPU_PARITY;

	$results = array();
	foreach ( cs_bottleneck_gpu_res_factors() as $res => $factor ) {
		$gpu_eff  = $gpu['score'] * $factor;
		$stronger = max( $cpu_eff, $gpu_eff );
		// Round to 4 decimals first so PHP and JS agree on values that land
		// exactly on .5 after float math.
		$share     = $stronger > 0 ? round( abs( $gpu_eff - $cpu_eff ) / $stronger * 100, 4 ) : 0;
		$percent   = (int) min( 60, floor( $share + 0.5 ) );
		$component = $gpu_eff > $cpu_eff ? 'cpu' : 'gpu'; // GPU has spare capacity => CPU is the limit.

		if ( $percent < 6 ) {
			$component = 'balanced';
		}

		$results[ $res ] = array(
			'percent'   => $percent,
			'component' => $component,
		);
	}

	return $results;
}

/**
 * True when the likely limiting part differs between resolutions.
 */
function cs_bottleneck_has_shift( $results ) {
	$components = array();
	foreach ( $results as $r ) {
		$components[ $r['component'] ] = true;
	}
	return count( $components ) > 1;
}

/**
 * One-paragraph headline for a pair: describes the shift when the limiting
 * part changes with resolution, otherwise the single most significant
 * resolution's verdict.
 */
function cs_bottleneck_summary( $cpu_name, $gpu_name, $results, $labels ) {
	if ( ! cs_bottleneck_has_shift( $results ) ) {
		$worst_res = '1080p';
		foreach ( $results as $res => $r ) {
			if ( $r['percent'] > $results[ $worst_res ]['percent'] ) {
				$worst_res = $res;
			}
		}
		return cs_bottleneck_sentence( $cpu_name, $gpu_name, $labels[ $worst_res ], $results[ $worst_res ] );
	}

	$short = array( '1080p' => '1080p', '1440p' => '1440p', '4k' => '4K' );
	$parts = array();
	foreach ( $results as $res => $r ) {
		if ( 'balanced' === $r['component'] ) {
			$parts[] = sprintf( __( 'balanced at %s', 'computingskill' ), $short[ $res ] );
		} elseif ( 'cpu' === $r['component'] ) {
			$parts[] = sprintf( __( 'CPU-limited at %1$s (~%2$d%%)', 'computingskill' ), $short[ $res ], $r['percent'] );
		} else {
			$parts[] = sprintf( __( 'GPU-limited at %1$s (~%2$d%%)', 'computingskill' ), $short[ $res ], $r['percent'] );
		}
	}

	return sprintf(
		/* translators: 1: CPU name, 2: GPU name, 3: list of per-resolution results */
		__( 'The limiting part shifts with resolution for the %1$s and %2$s: our estimate is %3$s. As resolution rises the GPU has more pixels to draw per frame, so more of the load lands on the graphics card.', 'computingskill' ),
		$cpu_name,
		$gpu_name,
		$parts[0] . ', ' . $parts[1] . ' ' . __( 'and', 'computingskill' ) . ' ' . $parts[2]
	);
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

/**
 * Keeps the /methodology/ (and CPU Performance Calculator) page wording true for the Phase 2 engine and
 * score scale (Oct 2026), without hand-editing the live post content.
 *
 * Runs on the raw content before shortcodes (priority 8), so the FAQ answer
 * that [cs_faq] also queues for FAQPage schema gets the corrected text too.
 * Each rule matches a distinctive phrase of the old text and does nothing
 * if that phrase is gone (e.g. after the page is edited by hand).
 */
function cs_methodology_engine_text( $content ) {
	if ( ! is_singular( 'page' ) ) {
		return $content;
	}

	$slug = get_post_field( 'post_name' );

	// The CPU Performance Calculator's tier now comes from the gaming index.
	if ( 'cpu-performance-calculator' === $slug ) {
		return preg_replace(
			'#,\s*even though this calculator returns one general-purpose tier\.#u',
			__( ', and this calculator’s tier follows its gaming-focused score.', 'computingskill' ),
			$content
		);
	}

	// GPU VRAM Calculator worked example: the calculator itself returns 8GB
	// (1440p, high textures) + 2GB (ray tracing) = 10GB minimum, not "8-9GB",
	// with 12GB as its comfortable figure.
	if ( 'gpu-vram-calculator' === $slug ) {
		return preg_replace(
			'#The calculator starts from a 1440p rasterization baseline[^<]*?for the same settings\.#u',
			__( 'The calculator starts from its 1440p high-texture baseline of 8GB (frame buffers, high-resolution textures and typical open-world asset streaming), then adds 2GB for ray tracing (BVH structures and denoising buffers), so it recommends at least 10GB of VRAM, with 12GB as the comfortable figure. That is why the GeForce RTX 4060’s 8GB of GDDR6 falls short for ray-traced 1440p gaming, while the GeForce RTX 4070’s 12GB of GDDR6X has comfortable headroom for the same settings.', 'computingskill' ),
			$content
		);
	}

	// Internet Data Usage Calculator: SD streaming is up to ~1GB/h (Netflix's
	// own figure, the rate the calculator now uses), not "1-3GB".
	if ( 'internet-data-usage-calculator' === $slug ) {
		return preg_replace(
			'#roughly 1(?:-|&\#8211;|\x{2013})3GB per hour at standard definition, and considerably more at HD or 4K#u',
			__( 'up to about 1GB per hour at standard definition and about 3GB at HD, per Netflix’s own figures, and considerably more at 4K', 'computingskill' ),
			$content
		);
	}

	if ( 'methodology' !== $slug ) {
		return $content;
	}

	$bottleneck = __( 'Compares your CPU and GPU separately at 1080p, 1440p and 4K. The CPU score stands for how many frames per second the CPU can prepare, which barely changes with resolution. The GPU score is scaled for each resolution by its pixel load (1080p ×1.00, 1440p ×0.68, 4K ×0.42, the same factors the FPS Calculator uses), because every extra pixel is extra work for the graphics card. Whichever part comes out lower at a resolution is the more likely limiting one there, so the same pairing can be CPU-bound at 1080p and GPU-bound at 4K. The estimated bottleneck percentage is the share of the stronger part’s capacity that goes unused (capped at 60%); anything under 6% is shown as balanced.', 'computingskill' );

	$score = __( 'We generate this as a simplified relative gaming-performance index per part. A GPU’s score stands for its average gaming throughput at high settings, with the RTX 4090 at 100 (a faster card, such as the RTX 5090, scores above 100). A CPU’s score stands for the frame rate it can sustain in games when the graphics card isn’t the limit, so it ranks gaming speed, not multi-core work. Both are built from published specs and positioned using publicly reported review averages, never as an absolute performance claim; we don’t run our own benchmarks. CPU and GPU scores are separate scales, so the calculators convert between them instead of comparing the raw numbers.', 'computingskill' );
	// (The page's own next paragraph already says the score is not a
	// benchmark result; repeating it here printed it twice.)

	// Whole block element (p or li) containing the phrase.
	$element = '#<(p|li)\b[^>]*>(?:(?!</\1>).)*?%s(?:(?!</\1>).)*?</\1>#su';

	$content = preg_replace( sprintf( $element, 'resolution-specific sensitivity curves' ), '<p>' . $bottleneck . '</p>', $content, 1 );
	$content = preg_replace( sprintf( $element, 'relative index per part' ), '<p>' . $score . '</p>', $content, 1 );

	// FAQ answers (plain text inside [cs_faq_item]).
	$content = preg_replace(
		'#The calculator models that shift with different sensitivity curves per resolution rather than giving one fixed number\.#u',
		__( 'The calculator models that shift by comparing the CPU and GPU separately at each resolution, with the GPU’s workload scaled by pixel count, rather than giving one fixed number.', 'computingskill' ),
		$content
	);
	$content = preg_replace(
		'#It(?:&\#8217;|&rsquo;|\x{2019}|\')s a simplified 1(?:-|&\#8211;|&ndash;|\x{2013})100 index built from published specs#u',
		__( 'It’s a simplified gaming-performance index built from published specs and public review averages', 'computingskill' ),
		$content
	);

	// PSU Calculator: it counts the CPU at its maximum turbo/boost power
	// (calculators.js CS_DEFS.psu), not its base TDP, as the PSU page says.
	$content = preg_replace(
		'#from your CPU(?:&\#8217;|&rsquo;|\x{2019}|\')s TDP, your GPU#u',
		__( 'from your CPU’s maximum turbo/boost power (Intel’s “Maximum Turbo Power”, AMD’s PPT, not its lower base TDP), your GPU', 'computingskill' ),
		$content
	);

	return $content;
}
add_filter( 'the_content', 'cs_methodology_engine_text', 8 );
