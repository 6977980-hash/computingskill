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
 *  4. The % is (stronger - weaker) / stronger * 100, rounded, capped at 60
 *     (under 6% = "balanced"). Since D3 (Oct 2026) it is read two ways:
 *     CPU-bound = the share of the GPU left idle (the bottleneck, with a
 *     severity label); GPU-bound = normal, and the % is CPU headroom. See
 *     cs_calculate_bottleneck().
 *  5. D3 also adds a graphics preset, a game type (AAA / esports /
 *     CPU-heavy) and a VRAM / memory-bus penalty. Combo pages use the
 *     defaults: high preset, AAA game.
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

/**
 * D3 (Oct 2026, audit 1.7 / 1.9): graphics preset. Scales the GPU's work
 * per frame only; the CPU prepares about the same frames at any preset.
 * Low vs Ultra is about 2.1x, in line with typical AAA preset scaling
 * (was 1.3 / 1.1 / 1.0 / 0.82, only 1.59x).
 */
function cs_bottleneck_preset_factors() {
	return array(
		'low'    => 1.7,
		'medium' => 1.3,
		'high'   => 1.0,
		'ultra'  => 0.8,
	);
}

/**
 * D3 (audit 1.7 / 1.9): game type. Multiplies the CPU's and the GPU's
 * frame-rate ceilings relative to a typical AAA game at the same settings.
 *  - esports: light competitive shooters / MOBAs (Valorant, CS2, Fortnite,
 *    Apex, League). Both parts run several times faster, the GPU a little
 *    more than the CPU, so these games lean CPU-bound and reach several
 *    hundred FPS on fast hardware.
 *  - cpu-heavy: strategy, simulation, big open worlds and MMOs, where the
 *    CPU's per-frame work is much higher.
 * Rough class profiles, not per-game benchmarks.
 */
function cs_bottleneck_game_profiles() {
	return array(
		'aaa'       => array( 'cpu' => 1.0, 'gpu' => 1.0 ),
		'esports'   => array( 'cpu' => 2.8, 'gpu' => 3.0 ),
		'cpu-heavy' => array( 'cpu' => 0.7, 'gpu' => 1.0 ),
	);
}

/**
 * D3 (audit 1.8): the GPU VRAM Calculator's minimum VRAM per resolution and
 * texture level (calculators.js CS_DEFS.vram "base"; low uses medium).
 */
function cs_bottleneck_vram_need() {
	return array(
		'1080p' => array( 'low' => 6, 'medium' => 6, 'high' => 6, 'ultra' => 8 ), // D5: 1080p floor 6GB (audit 1.21).
		'1440p' => array( 'low' => 6, 'medium' => 6, 'high' => 8, 'ultra' => 10 ),
		'4k'    => array( 'low' => 8, 'medium' => 8, 'high' => 12, 'ultra' => 14 ),
	);
}

/**
 * D3 (audit 1.8): memory penalty on top of the shared resolution factor.
 *  - VRAM short of the VRAM Calculator's minimum: -5% per missing GB
 *    (floor 0.75), e.g. an 8GB card at 4K high (needs 12GB) gets x0.80.
 *    Not applied to esports titles, which use little VRAM.
 *  - 128-bit memory bus: x0.96 at 1440p and x0.92 at 4K, where narrow-bus
 *    cards (RTX 4060 / 5060 / 5060 Ti, RX 7600 / 9060 XT) fall further
 *    behind in reviews than their 1080p standing suggests.
 */
function cs_bottleneck_gpu_memory_factor( $gpu, $res, $preset = 'high', $game = 'aaa' ) {
	$factor = 1.0;
	$need   = cs_bottleneck_vram_need();
	if ( 'esports' !== $game && isset( $gpu['vram_gb'], $need[ $res ][ $preset ] ) ) {
		$short = $need[ $res ][ $preset ] - (int) $gpu['vram_gb'];
		if ( $short > 0 ) {
			$factor *= max( 0.75, 1 - 0.05 * $short );
		}
	}
	if ( isset( $gpu['memory_bus'] ) && (int) $gpu['memory_bus'] <= 128 ) {
		$bus     = array( '1080p' => 1.0, '1440p' => 0.96, '4k' => 0.92 );
		$factor *= $bus[ $res ];
	}
	return $factor;
}

/**
 * Normalised options: preset (high) and game type (aaa) by default, which
 * is what the combo pages use.
 */
function cs_bottleneck_opts( $opts = array() ) {
	$opts = wp_parse_args( (array) $opts, array( 'preset' => 'high', 'game' => 'aaa' ) );
	if ( ! isset( cs_bottleneck_preset_factors()[ $opts['preset'] ] ) ) {
		$opts['preset'] = 'high';
	}
	if ( ! isset( cs_bottleneck_game_profiles()[ $opts['game'] ] ) ) {
		$opts['game'] = 'aaa';
	}
	return $opts;
}

/**
 * Per-resolution estimate.
 *
 * D3 (Oct 2026, audit 1.1 / 1.4 / 3.8), what the number means:
 *  - CPU-bound ('cpu'): percent = the share of the GPU left idle because
 *    the CPU can't feed it, i.e. roughly 100 minus the GPU usage you'd see
 *    in a monitoring overlay. This is the real bottleneck. Severity:
 *    under 6 balanced, 6-14 mild, 15-29 noticeable, 30+ severe.
 *  - GPU-bound ('gpu'): normal for gaming. percent = the CPU's spare
 *    headroom, so a faster CPU reads higher, which is good, not worse.
 *  - percent is capped at 60; 'capped' is true when the real value is
 *    higher, so the UI can say "60%+".
 */
function cs_calculate_bottleneck( $cpu, $gpu, $opts = array() ) {
	$opts    = cs_bottleneck_opts( $opts );
	$profile = cs_bottleneck_game_profiles()[ $opts['game'] ];
	$preset  = cs_bottleneck_preset_factors()[ $opts['preset'] ];
	$cpu_eff = $cpu['score'] * CS_BN_CPU_PARITY * $profile['cpu'];

	$results = array();
	foreach ( cs_bottleneck_gpu_res_factors() as $res => $factor ) {
		$gpu_eff  = $gpu['score'] * $factor * $preset * $profile['gpu'] * cs_bottleneck_gpu_memory_factor( $gpu, $res, $opts['preset'], $opts['game'] );
		$stronger = max( $cpu_eff, $gpu_eff );
		// Round to 4 decimals first so PHP and JS agree on values that land
		// exactly on .5 after float math.
		$share     = $stronger > 0 ? round( abs( $gpu_eff - $cpu_eff ) / $stronger * 100, 4 ) : 0;
		$raw       = (int) floor( $share + 0.5 );
		$percent   = min( 60, $raw );
		$component = $gpu_eff > $cpu_eff ? 'cpu' : 'gpu'; // GPU has spare capacity => CPU is the limit.

		if ( $percent < 6 ) {
			$component = 'balanced';
		}

		$results[ $res ] = array(
			'percent'   => $percent,
			'component' => $component,
			'capped'    => $raw > 60,
			'severity'  => cs_bottleneck_severity( $component, $percent ),
		);
	}

	return $results;
}

/**
 * 'balanced', 'mild', 'noticeable', 'severe' (CPU-bound) or 'normal' (GPU-bound).
 */
function cs_bottleneck_severity( $component, $percent ) {
	if ( 'balanced' === $component ) {
		return 'balanced';
	}
	if ( 'gpu' === $component ) {
		return 'normal';
	}
	if ( $percent >= 30 ) {
		return 'severe';
	}
	return $percent >= 15 ? 'noticeable' : 'mild';
}

/**
 * Display percent: "60%+" when capped, else "23%".
 */
function cs_bn_pct( $r ) {
	return $r['percent'] . ( ! empty( $r['capped'] ) ? '%+' : '%' );
}

/**
 * Short label for a result: "Balanced", "Noticeable CPU bottleneck (23%)",
 * "GPU-limited, normal (33% CPU headroom)".
 */
function cs_bn_label( $r ) {
	$sev = array(
		'mild'       => __( 'Mild', 'computingskill' ),
		'noticeable' => __( 'Noticeable', 'computingskill' ),
		'severe'     => __( 'Severe', 'computingskill' ),
	);
	if ( 'balanced' === $r['component'] ) {
		return __( 'Balanced', 'computingskill' );
	}
	if ( 'gpu' === $r['component'] ) {
		/* translators: %s: percent */
		return sprintf( __( 'GPU-limited, normal (%s CPU headroom)', 'computingskill' ), cs_bn_pct( $r ) );
	}
	/* translators: 1: severity word, 2: percent */
	return sprintf( __( '%1$s CPU bottleneck (%2$s)', 'computingskill' ), $sev[ $r['severity'] ], cs_bn_pct( $r ) );
}

/**
 * The resolution a card is built for, from its tier label ("Mid-range /
 * 1440p" => 1440p, "High-end / 1440p-4K" => 1440p). Audit 1.3: the combo
 * Quick Summary leads with this instead of the worst resolution.
 */
function cs_bn_target_res( $gpu ) {
	$tier = isset( $gpu['tier'] ) ? $gpu['tier'] : '';
	foreach ( array( '1080p' => '1080p', '1440p' => '1440p', '4K' => '4k' ) as $needle => $res ) {
		if ( false !== strpos( $tier, $needle ) ) {
			return $res;
		}
	}
	return '1080p';
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
 * One-paragraph headline for a pair. D3 (audit 1.3): it leads with the
 * resolution the GPU is built for (its tier label), not the worst
 * resolution, which for every GPU-limited pair used to be 4K. The other
 * resolutions follow in one sentence.
 */
function cs_bottleneck_summary( $cpu_name, $gpu_name, $results, $labels, $target = '1080p' ) {
	if ( ! isset( $results[ $target ] ) ) {
		$target = '1080p';
	}
	$out = cs_bottleneck_sentence(
		$cpu_name,
		$gpu_name,
		/* translators: %s: resolution label */
		sprintf( __( '%s, the resolution this card is built for', 'computingskill' ), $labels[ $target ] ),
		$results[ $target ]
	);

	if ( cs_bottleneck_has_shift( $results ) ) {
		$out .= ' ' . sprintf(
			/* translators: %s: list of per-resolution results */
			__( 'Across resolutions the limiting part shifts: our estimate is %s. As resolution rises the GPU has more pixels to draw per frame, so more of the load lands on the graphics card.', 'computingskill' ),
			cs_bn_phrases( $results )
		);
	} else {
		$out .= ' ' . sprintf(
			/* translators: %s: list of per-resolution results */
			__( 'Across all three resolutions our estimate is %s.', 'computingskill' ),
			cs_bn_phrases( $results )
		);
	}
	return $out;
}

/**
 * Human-readable verdict sentence for a resolution's result (D3 meaning:
 * a CPU-side % is GPU capacity left idle; GPU-limited is normal and its %
 * is CPU headroom).
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
		$sev = array(
			'mild'       => __( 'mild', 'computingskill' ),
			'noticeable' => __( 'noticeable', 'computingskill' ),
			'severe'     => __( 'severe', 'computingskill' ),
		);
		$usage = ! empty( $result['capped'] )
			? __( 'under 40%', 'computingskill' )
			: sprintf( __( 'around %d%%', 'computingskill' ), 100 - $result['percent'] );
		return sprintf(
			/* translators: 1: CPU name, 2: GPU name, 3: resolution label, 4: severity, 5: percent, 6: GPU usage */
			__( 'At %3$s, the %1$s is the likely limit: our estimate is a %4$s CPU bottleneck of about %5$s, meaning roughly that share of the %2$s’s capacity goes unused (GPU usage %6$s in a monitoring overlay), most of all in CPU-heavy or high-frame-rate games.', 'computingskill' ),
			$cpu_name,
			$gpu_name,
			$res_label,
			$sev[ $result['severity'] ],
			cs_bn_pct( $result ),
			$usage
		);
	}

	$out = sprintf(
		/* translators: 1: CPU name, 2: GPU name, 3: resolution label, 4: percent */
		__( 'At %3$s, the %2$s sets the frame rate, which is normal for gaming: the graphics card is fully used and the %1$s has %4$s of spare headroom.', 'computingskill' ),
		$cpu_name,
		$gpu_name,
		$res_label,
		! empty( $result['capped'] ) ? __( 'more than 60%', 'computingskill' ) : sprintf( __( 'about %d%%', 'computingskill' ), $result['percent'] )
	);
	if ( $result['percent'] >= 30 ) {
		$out .= ' ' . __( 'For higher frame rates here, a faster graphics card is what helps; a faster CPU would not.', 'computingskill' );
	}
	return $out;
}

/**
 * U2 (U-43): the per-resolution lines under a combo page's table. The
 * Quick Summary already gives the full sentence (and why GPU-limited is
 * normal), so these say only what differs at each resolution, and the
 * "a faster graphics card is what helps" advice is printed once.
 *
 * @param array $said Carries what was already said across the three calls.
 */
function cs_bottleneck_sentence_brief( $cpu_name, $gpu_name, $result, &$said ) {
	if ( 'balanced' === $result['component'] ) {
		/* translators: 1: CPU name, 2: GPU name */
		return sprintf( __( 'The %1$s and %2$s are well matched here.', 'computingskill' ), $cpu_name, $gpu_name );
	}
	if ( 'cpu' === $result['component'] ) {
		$sev   = array(
			'mild'       => __( 'mild', 'computingskill' ),
			'noticeable' => __( 'noticeable', 'computingskill' ),
			'severe'     => __( 'severe', 'computingskill' ),
		);
		$usage = ! empty( $result['capped'] )
			? __( 'under 40%', 'computingskill' )
			: sprintf( __( 'around %d%%', 'computingskill' ), 100 - $result['percent'] );
		return sprintf(
			/* translators: 1: CPU name, 2: GPU name, 3: severity, 4: percent, 5: GPU usage */
			__( 'The %1$s is the likely limit: a %3$s CPU bottleneck of about %4$s, so roughly that share of the %2$s’s capacity goes unused (GPU usage %5$s).', 'computingskill' ),
			$cpu_name,
			$gpu_name,
			$sev[ $result['severity'] ],
			cs_bn_pct( $result ),
			$usage
		);
	}
	$out = sprintf(
		/* translators: 1: CPU name, 2: GPU name, 3: percent */
		__( 'The %2$s is the limit, and the %1$s has %3$s to spare.', 'computingskill' ),
		$cpu_name,
		$gpu_name,
		! empty( $result['capped'] ) ? __( 'more than 60%', 'computingskill' ) : sprintf( __( 'about %d%%', 'computingskill' ), $result['percent'] )
	);
	if ( $result['percent'] >= 30 && empty( $said['gpu_upgrade'] ) ) {
		$out               .= ' ' . __( 'For higher frame rates here, a faster graphics card is what helps; a faster CPU would not.', 'computingskill' );
		$said['gpu_upgrade'] = true;
	}
	return $out;
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

	// D7 (audit 2.20/2.21): the calculator and both guides printed the same
	// 135-link list as /bottleneck/, which made up most of the text those
	// pages shared. /bottleneck/ now shows every pair as a results table,
	// grouped by CPU; the other pages show a sample across budgets.
	if ( 'bottleneck' !== $slug ) {
		$sample = function_exists( 'cs_il_combo_sample' ) ? cs_il_combo_sample( 12, $slug ) : array();
		if ( ! $sample ) {
			return;
		}
		$cpus = cs_get_cpu_data();
		$gpus = cs_get_gpu_data();
		echo '<div class="cs-related cs-bottleneck-index">';
		echo '<h2>' . esc_html__( 'Example CPU + GPU Combinations', 'computingskill' ) . '</h2>';
		echo '<ul class="cs-related-grid">';
		foreach ( $sample as $key => $url ) {
			list( $c, $g ) = explode( '|', $key );
			echo '<li><a href="' . esc_url( $url ) . '">' . esc_html( cs_bn_short_name( $cpus[ $c ]['name'] ) . ' + ' . cs_bn_short_name( $gpus[ $g ]['name'] ) ) . '</a></li>';
		}
		echo '</ul>';
		echo '<p class="cs-related-more"><a href="' . esc_url( home_url( '/bottleneck/' ) ) . '">' . esc_html( sprintf( __( 'All %d bottleneck comparisons, with results →', 'computingskill' ), count( cs_bn_published_pairs() ) ) ) . '</a></p>';
		echo '</div>';
		return;
	}

	$cpus   = cs_get_cpu_data();
	$gpus   = cs_get_gpu_data();
	$by_cpu = array();
	foreach ( cs_bn_published_pairs() as $key => $url ) {
		list( $c, $g ) = explode( '|', $key );
		if ( isset( $cpus[ $c ], $gpus[ $g ] ) ) {
			$by_cpu[ $c ][ $g ] = $url;
		}
	}
	if ( ! $by_cpu ) {
		return;
	}
	uksort( $by_cpu, function ( $a, $b ) use ( $cpus ) {
		return $cpus[ $a ]['score'] - $cpus[ $b ]['score'] ?: strcmp( $cpus[ $a ]['name'], $cpus[ $b ]['name'] );
	} );
	$short = cs_bn_res_short();

	echo '<div class="cs-bottleneck-index cs-bottleneck-table-index">';
	echo '<h2>' . esc_html__( 'Every CPU + GPU Combination at a Glance', 'computingskill' ) . '</h2>';
	echo '<p>' . esc_html__( 'Grouped by CPU, slowest first; within each CPU the graphics cards go from slowest to fastest. Typical AAA game at high settings, as on each page.', 'computingskill' ) . '</p>';
	foreach ( $by_cpu as $c => $rows ) {
		uksort( $rows, function ( $a, $b ) use ( $gpus ) {
			return $gpus[ $a ]['score'] - $gpus[ $b ]['score'];
		} );
		echo '<h3>' . esc_html( $cpus[ $c ]['name'] ) . '</h3>';
		echo '<div class="cs-table-wrap"><table class="cs-hardware-table cs-bn-index-table"><thead><tr><th>' . esc_html__( 'Graphics card', 'computingskill' ) . '</th>';
		foreach ( $short as $label ) {
			echo '<th>' . esc_html( $label ) . '</th>';
		}
		echo '</tr></thead><tbody>';
		foreach ( $rows as $g => $url ) {
			$r = cs_calculate_bottleneck( $cpus[ $c ], $gpus[ $g ] );
			echo '<tr><td><a href="' . esc_url( $url ) . '">' . esc_html( cs_bn_short_name( $cpus[ $c ]['name'] ) . ' + ' . cs_bn_short_name( $gpus[ $g ]['name'] ) ) . '</a></td>';
			foreach ( array_keys( $short ) as $res ) {
				echo '<td>' . esc_html( cs_bn_cell( $r[ $res ] ) ) . '</td>';
			}
			echo '</tr>';
		}
		echo '</tbody></table></div>';
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

	$bottleneck = __( 'Compares your CPU and GPU separately at 1080p, 1440p and 4K. The CPU score stands for how many frames per second the CPU can prepare, which barely changes with resolution. The GPU score is scaled for each resolution by its pixel load (1080p ×1.00, 1440p ×0.68, 4K ×0.42, the same factors the FPS Calculator uses), because every extra pixel is extra work for the graphics card. Cards below the GPU VRAM Calculator’s minimum for the chosen settings lose 5% per missing GB (at most 25%), and cards with a 128-bit memory bus lose a further 4% at 1440p and 8% at 4K. The Graphics settings input scales the GPU’s work (Low ×1.7, Medium ×1.3, High ×1.0, Ultra ×0.8), and the Game type input sets the balance: esports titles run the CPU ×2.8 and the GPU ×3.0 faster than a typical AAA game, CPU-heavy strategy and simulation games run the CPU at ×0.7. Whichever part comes out lower at a resolution is the more likely limiting one there, so the same pairing can be CPU-bound at 1080p and GPU-bound at 4K. If the CPU is the limit, the percentage is a CPU bottleneck: the share of the GPU’s capacity left unused, roughly 100 minus the GPU usage you would see in a monitoring overlay (under 6% balanced, 6–14% mild, 15–29% noticeable, 30% or more severe, shown as 60%+ above 60%). If the GPU is the limit, the result is GPU-limited, which is normal for gaming, and the percentage is the CPU’s spare headroom. Combo pages use a typical AAA game at high settings.', 'computingskill' );

	$score = __( 'We generate this as a simplified relative gaming-performance index per part. A GPU’s score stands for its average gaming throughput at high settings, with the RTX 4090 at 100 (a faster card, such as the RTX 5090, scores above 100). A CPU’s score stands for the frame rate it can sustain in games when the graphics card isn’t the limit, so it ranks gaming speed, not multi-core work. Both are built from published specs and positioned using publicly reported review averages, never as an absolute performance claim; we don’t run our own benchmarks. CPU and GPU scores are separate scales, so the calculators convert between them instead of comparing the raw numbers.', 'computingskill' );
	// (The page's own next paragraph already says the score is not a
	// benchmark result; repeating it here printed it twice.)

	// Whole block element (p or li) containing the phrase.
	$element = '#<(p|li)\b[^>]*>(?:(?!</\1>).)*?%s(?:(?!</\1>).)*?</\1>#su';

	$content = preg_replace( sprintf( $element, 'resolution-specific sensitivity curves' ), '<p>' . $bottleneck . '</p>', $content, 1 );
	$content = preg_replace( sprintf( $element, 'relative index per part' ), '<p>' . $score . '</p>', $content, 1 );

	// D3 (Oct 2026, audit 1.9): the FPS formula now also uses the game type
	// and the VRAM / memory-bus factors, and a wider preset range.
	$fps = __( 'Estimates a GPU-driven frame rate from the GPU’s score (2.5 FPS per point at 1080p high), scaled by the same resolution, graphics-preset, VRAM and memory-bus factors as the bottleneck calculator, then caps it at a CPU-driven ceiling from the CPU’s score. Both ceilings are multiplied by the game type’s profile (esports titles run several times faster than a typical AAA game; picking a game such as Valorant uses its type), so the limiting part always matches the bottleneck calculator for the same choices. The lower of the two becomes the estimate, shown as a range (roughly ±18%) rather than a single misleadingly precise figure, since actual FPS depends heavily on the specific game engine, drivers and settings we can’t see. Ray tracing is not modelled. Optional upscaling (DLSS / FSR / XeSS) multiplies only the GPU ceiling by a typical gain for the mode and resolution (Quality +25% at 1080p to +40% at 4K, Balanced +35% to +60%, Performance +50% to +85%), so a CPU-limited PC gains little; frame generation multiplies the result by 2, 3 or 4 (3x and 4x on RTX 50 cards only) after a 10% cost to the rendered frame rate, and the result shows the rendered rate separately because responsiveness follows it.', 'computingskill' );
	$content = preg_replace( sprintf( $element, 'Estimates a GPU-driven frame rate' ), '<p>' . $fps . '</p>', $content, 1 );

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

	// PC Power Consumption Calculator (Oct 2026): separate idle and load
	// figures instead of one 65% average (calculators.js).
	$content = preg_replace(
		'#Power consumption estimates an average wattage draw at roughly 65% typical load \(CPU TDP \+ GPU TDP \+ a fixed allowance for the rest of the system\), then multiplies by your stated daily usage hours and electricity rate\.#u',
		// D5 (audit 1.19): CPU gaming draw from max turbo/boost power, and
		// PSU efficiency loss included.
		__( 'Power consumption estimates two figures: load draw (the CPU at about 55% of its maximum turbo/boost power, 45% for AMD X3D chips, + GPU TDP + the PSU Calculator’s allowance for RAM, drives, fans and motherboard) and idle draw (CPU and GPU at about 15% of TDP, the rest of the system at about 30% of its allowance). Each is divided by a typical PSU efficiency (88% under load, 80% at idle) to get the draw from the wall, then multiplied by your gaming and idle hours per day and your electricity rate.', 'computingskill' ),
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
