<?php
/**
 * Text and trust consistency (user-side audit U2, 20 Oct 2026).
 *
 * The page text below lives in the database. Like inc/calculator-pages.php,
 * each fix swaps one sentence found by its ASCII opening and closing words
 * (cs_page_text_swap()), so the rest of the page and any later wp-admin
 * edit stay as they are, and a pattern that no longer matches does nothing.
 *
 *  - U-05: About, /calculators/, the Disclaimer and the published posts said
 *    "specs-based ... core counts, clock speeds" while the tools, Methodology
 *    and Editorial Policy say the CPU/GPU scores are placed from review
 *    averages. They now all describe the method as cs_method_sentence()
 *    (inc/aeo.php) does.
 *  - U-34: /calculators/ says 16 calculators; Can I Run It joins the card grid.
 *  - U-42: the RAM guide's repeated short answer, wrong cross-link, dual
 *    channel claim and two 1080p editing figures.
 *  - U-47: About links the Editorial Policy.
 *
 * Runs at priority 8, before wpautop, wptexturize and shortcodes.
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Replacement list per page slug: array( opening words, closing words, new text ).
 */
function cs_u2_page_fixes() {
	$index = __( 'our own gaming-performance index for CPUs and GPUs (placed from published review averages)', 'computingskill' );

	return array(
		'about-us' => array(
			array(
				'Instead, each calculator is built from published', 'generally known to behave.',
				__( 'Instead, the numbers come from three places. Hardware specs (cores, clocks, power figures, VRAM) come from the manufacturers’ own pages. CPU and GPU performance comes from ComputingSkill’s own gaming-performance index, where each part is placed from publicly reported third-party review averages rather than computed from core counts or clock speeds. The other tools (power, PSU, RAM, storage, internet and display) use the formulas shown on each tool page.', 'computingskill' ),
			),
			array( 'than a specs-based model can honestly offer', 'than a specs-based model can honestly offer', __( 'than an estimate can honestly offer', 'computingskill' ) ),
			array( 'an estimate derived from specifications', 'an estimate derived from specifications', __( 'an estimate (from manufacturer specs, our gaming-performance index or a stated formula)', 'computingskill' ) ),
			array(
				'built from published specifications and established formulas', 'in a lab.',
				/* translators: %s: how the CPU/GPU index is made */
				sprintf( __( 'built from manufacturer specs, %s and the formulas shown on each tool page, not from running benchmarks on physical hardware in a lab.', 'computingskill' ), $index ),
			),
		),
		'calculators' => array(
			array( 'a specs-based estimate', 'a specs-based estimate', __( 'an estimate', 'computingskill' ) ),
			array( 'the calculator applies published specifications and standard formulas', 'the calculator applies published specifications and standard formulas', __( 'the calculator applies manufacturer specs, our gaming-performance index (for CPUs and GPUs) or a stated formula', 'computingskill' ) ),
			array(
				'They use published specifications and established formulas', 'They use published specifications and established formulas',
				/* translators: %s: how the CPU/GPU index is made */
				sprintf( __( 'They use manufacturer specs, %s and stated formulas', 'computingskill' ), $index ),
			),
			array(
				'It helps, since more specific inputs', 'in mind yet.',
				__( 'The CPU and GPU tools ask you to pick your models from a list. If yours isn’t listed, choose “Other / not listed” where the tool offers it and then the listed part closest to yours, and the estimate uses that part’s data. The power, storage, internet and display tools take plain numbers such as watts, GB, Mbps or inches.', 'computingskill' ),
			),
		),
		'disclaimer' => array(
			array( 'produces a specs-based estimate', 'produces a specs-based estimate', __( 'produces an estimate', 'computingskill' ) ),
			array(
				'Estimates are built from published specifications and standard formulas', 'Estimates are built from published specifications and standard formulas',
				/* translators: %s: how the CPU/GPU index is made */
				sprintf( __( 'Estimates are built from manufacturer specifications, %s and the formulas stated on each tool page', 'computingskill' ), $index ),
			),
			array( 'based on component specifications and general performance tiers', 'based on component specifications and general performance tiers', __( 'based on our gaming-performance scores for each CPU and GPU', 'computingskill' ) ),
			array( 'specs-based estimates, not lab-measured', 'specs-based estimates, not lab-measured', __( 'estimates built from manufacturer specs, our gaming-performance index and stated formulas, not lab-measured', 'computingskill' ) ),
		),
		'how-much-ram-do-i-need' => array(
			array(
				'is one of those questions with a different correct answer', 'rule of thumb.',
				__( 'has a different answer for everyone, because it depends on what you run. This guide gives a minimum and a recommended figure for each common use and explains why.', 'computingskill' ),
			),
			array(
				'which noticeably helps GPU-bound', 'including gaming.',
				__( 'which helps most in CPU-limited games and other memory-heavy work; when the graphics card is the limit, the gain is small.', 'computingskill' ),
			),
			array(
				'A short 1080p project with a few clips', 'GB.',
				__( 'A short 1080p project with a few clips is comfortable at 32GB, and very simple edits still run on 16GB.', 'computingskill' ),
			),
		),
	);
}

/**
 * Post text (the published "best X for Y" articles): one method line, and
 * no "specs-based" left.
 */
function cs_u2_post_fixes() {
	$scores = __( 'estimates from our gaming-performance scores (placed from published review averages)', 'computingskill' );
	return array(
		/* translators: %s: "estimates from our gaming-performance scores ..." */
		array( 'These are specs-based estimates, not benchmarks.', 'These are specs-based estimates, not benchmarks.', sprintf( __( 'These are %s, not our own benchmarks.', 'computingskill' ), $scores ) ),
		/* translators: %s: "estimates from our gaming-performance scores ..." */
		array( 'The results below are specs-based estimates', 'The results below are specs-based estimates', sprintf( __( 'The results below are %s', 'computingskill' ), $scores ) ),
	);
}

function cs_u2_page_text( $content ) {
	if ( is_singular( 'post' ) ) {
		$content = cs_page_text_swap( $content, cs_u2_post_fixes() );
		// The closing notes ("All figures are specs-based estimates from
		// ComputingSkill's engine ...").
		return str_replace( 'specs-based estimates', __( 'estimates', 'computingskill' ), $content );
	}
	if ( ! is_singular( 'page' ) ) {
		return $content;
	}
	$slug  = get_post_field( 'post_name' );
	$fixes = cs_u2_page_fixes();
	if ( ! empty( $fixes[ $slug ] ) ) {
		$content = cs_page_text_swap( $content, $fixes[ $slug ] );
	}

	if ( 'about-us' === $slug && false === strpos( $content, 'editorial-policy' ) ) {
		// U-47: the Related Pages grid names Methodology, Disclaimer, Privacy...
		$content = preg_replace( '#(\[cs_related[^\]]*links=["\'][^"\']*\bmethodology)(?=[,"\'])#', '$1,editorial-policy', $content, 1 );
	} elseif ( 'calculators' === $slug && false === strpos( $content, 'can-i-run-it' ) ) {
		// U-34: the grid showed 15 of the 16 calculators the page counts.
		$content = preg_replace( '#(\[cs_related[^\]]*links=["\'][^"\']*\bfps-calculator)(?=[,"\'])#', '$1,can-i-run-it', $content, 1 );
	} elseif ( 'how-much-ram-do-i-need' === $slug ) {
		// U-42: "The Short Answer" repeated the quick answer box above it word
		// for word; its table stays under a plain heading.
		$content = preg_replace(
			'#(<h2[^>]*>)\s*The Short Answer\s*(</h2>)(\s*<!-- /wp:heading -->)?\s*(?:<!-- wp:paragraph -->\s*)?(?:<p>)?\s*For most people building or buying[^<\n]*(?:</p>)?\s*(?:<!-- /wp:paragraph -->)?#',
			'$1' . esc_html__( 'RAM by Use Case', 'computingskill' ) . '$2$3',
			$content,
			1
		);
		// The Bottleneck Calculator has no RAM input.
		$content = preg_replace(
			'#(?:<p>)?\s*If you want to size your gaming build.*?otherwise capable build\.\s*(?:</p>)?#s',
			'<p>' . sprintf(
				/* translators: %s: link to the RAM Calculator */
				esc_html__( 'To size RAM for your own games and background apps, use the %s.', 'computingskill' ),
				'<a href="' . esc_url( home_url( '/ram-calculator/' ) ) . '">' . esc_html__( 'RAM Calculator', 'computingskill' ) . '</a>'
			) . '</p>',
			$content,
			1
		);
	}
	return $content;
}
add_filter( 'the_content', 'cs_u2_page_text', 8 );

/* ------------------------------------------------------------------ */
/* "Last updated" for generated pages U2 changed                        */
/* ------------------------------------------------------------------ */

/**
 * Like CS_U1_TOUCH_REV (inc/combo-pages.php): only the generated pages whose
 * text U2 changed get a new modified date, once, so game pages keep their
 * honest dates. Bump CS_U2_TOUCH_REV only with a change to these targets.
 *  - every combo page (U-43 shorter, less repetitive text),
 *  - part pages (CPU: U-36 PC Cooling Calculator link; GPU: U-45 VRAM
 *    marker in the FPS table) and GPU vs GPU comparison pages (U-45),
 *  - published posts that said "specs-based" (U-05).
 * Database pages (About, /calculators/, the RAM guide, Methodology, Contact)
 * are dated by CS_CALC_PAGES_REV, Privacy and the Disclaimer by CS_LEGAL_REV.
 */
if ( ! defined( 'CS_U2_TOUCH_REV' ) ) {
	define( 'CS_U2_TOUCH_REV', '2026-10-20-u2' );
}

function cs_u2_touch_targets() {
	$ids = array();
	foreach ( get_posts( array( 'post_type' => array( 'cs_bottleneck', 'cs_hardware', 'cs_comparison', 'post' ), 'post_status' => 'publish', 'posts_per_page' => -1, 'no_found_rows' => true ) ) as $p ) {
		if ( 'cs_hardware' === $p->post_type ) {
			// Part pages only, not the component explainers (/hardware/ram/ ...).
			if ( isset( cs_get_cpu_data()[ $p->post_name ] ) || isset( cs_get_gpu_data()[ $p->post_name ] ) ) {
				$ids[] = $p->ID;
			}
		} elseif ( 'post' === $p->post_type ) {
			if ( false !== strpos( $p->post_content, 'specs-based' ) ) {
				$ids[] = $p->ID;
			}
		} elseif ( 'cs_comparison' === $p->post_type ) {
			// GPU vs GPU pages only (SSD vs HDD, CPU vs CPU ... unchanged).
			$two = explode( '-vs-', $p->post_name );
			if ( 2 === count( $two ) && isset( cs_get_gpu_data()[ $two[0] ], cs_get_gpu_data()[ $two[1] ] ) ) {
				$ids[] = $p->ID;
			}
		} else {
			$ids[] = $p->ID;
		}
	}
	return $ids;
}

function cs_u2_pages_touch() {
	if ( wp_installing() || get_option( 'cs_u2_touch_rev' ) === CS_U2_TOUCH_REV ) {
		return;
	}
	if ( ! add_option( 'cs_u2_touch_lock', time(), '', 'no' ) ) {
		if ( time() - (int) get_option( 'cs_u2_touch_lock' ) > 300 ) {
			delete_option( 'cs_u2_touch_lock' );
		}
		return;
	}
	global $wpdb;
	$ids = cs_u2_touch_targets();
	foreach ( $ids as $id ) {
		// Direct update: wp_update_post() would add a revision for a date change.
		$wpdb->update(
			$wpdb->posts,
			array( 'post_modified' => current_time( 'mysql' ), 'post_modified_gmt' => current_time( 'mysql', true ) ),
			array( 'ID' => $id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
		clean_post_cache( $id );
	}
	if ( class_exists( '\RankMath\Sitemap\Cache' ) && method_exists( '\RankMath\Sitemap\Cache', 'invalidate_storage' ) ) {
		\RankMath\Sitemap\Cache::invalidate_storage();
	}
	if ( function_exists( 'cs_perf_purge_posts' ) ) {
		cs_perf_purge_posts( $ids );
	}
	update_option( 'cs_u2_touch_rev', CS_U2_TOUCH_REV, true );
	delete_option( 'cs_u2_touch_lock' );
}
add_action( 'init', 'cs_u2_pages_touch', 34 );
