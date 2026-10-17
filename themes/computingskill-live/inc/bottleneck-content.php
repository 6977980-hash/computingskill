<?php
/**
 * Pair-specific verdict and FAQ text for /bottleneck/{cpu}-{gpu}/ pages
 * (Phase 3 audit fix, Oct 2026).
 *
 * The combo pages used to share three identical FAQ answers. Everything
 * below is built only from this pair's own data (data/hardware-data.php)
 * and engine results (cs_calculate_bottleneck()), plus the same rules the
 * site's calculators use, so the text can't disagree with the table on the
 * page or with the calculators:
 *  - "Who it suits" uses the PC Upgrade Calculator's GPU targets
 *    (assets/js/calculators.js CS_DEFS.upgrade targetGpuScore). Keep
 *    cs_bn_upgrade_targets() identical to it. Since D3 the Upgrade tool takes
 *    its CPU decision from the bottleneck engine, so there are no CPU targets.
 *  - VRAM uses the GPU VRAM Calculator's guideline (CS_DEFS.vram "base").
 *  - Power uses the PSU Calculator's formula with its default extras
 *    (CS_DEFS.psu: 2 RAM sticks, 2 drives, 3 fans, no overclock).
 *  - Socket memory matches the Compatibility Checker's SOCKET_MEMORY.
 * No FPS numbers or benchmark claims are made.
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Which part the "Recommended Settings & Upgrade Path" block treats as the
 * main limit. Shared by that block and the verdict so they always agree.
 *
 * @return string 'gpu' (GPU-limited 15%+ at 1440p), 'cpu' (CPU-limited 15%+
 *                at 1080p), 'gpu4k' (only 4K is GPU-limited 15%+) or 'balanced'.
 */
function cs_bottleneck_primary_limit( $results ) {
	if ( 'gpu' === $results['1440p']['component'] && $results['1440p']['percent'] >= 15 ) {
		return 'gpu';
	}
	if ( 'cpu' === $results['1080p']['component'] && $results['1080p']['percent'] >= 15 ) {
		return 'cpu';
	}
	if ( 'gpu' === $results['4k']['component'] && $results['4k']['percent'] >= 15 ) {
		return 'gpu4k';
	}
	return 'balanced';
}

function cs_bn_upgrade_targets() {
	return array(
		'gpu' => array( '1080p' => 33, '1440p' => 50, '4k' => 70 ),
	);
}

function cs_bn_res_short() {
	return array( '1080p' => '1080p', '1440p' => '1440p', '4k' => '4K' );
}

/**
 * "a", "a and b", "a, b and c".
 */
function cs_bn_join( $items ) {
	$items = array_values( $items );
	if ( count( $items ) < 2 ) {
		return implode( '', $items );
	}
	$last = array_pop( $items );
	return implode( ', ', $items ) . ' ' . __( 'and', 'computingskill' ) . ' ' . $last;
}

/**
 * "a noticeable CPU bottleneck at 1080p (~20%)" / "GPU-limited at 4K (~48%
 * CPU headroom)" / "balanced at 4K" (D3 meaning, see cs_calculate_bottleneck()).
 */
function cs_bn_phrase( $res, $r ) {
	$short = cs_bn_res_short();
	if ( 'balanced' === $r['component'] ) {
		return sprintf( __( 'balanced at %s', 'computingskill' ), $short[ $res ] );
	}
	if ( 'cpu' === $r['component'] ) {
		$sev = array(
			'mild'       => __( 'a mild', 'computingskill' ),
			'noticeable' => __( 'a noticeable', 'computingskill' ),
			'severe'     => __( 'a severe', 'computingskill' ),
		);
		/* translators: 1: "a mild" etc., 2: resolution, 3: percent */
		return sprintf( __( '%1$s CPU bottleneck at %2$s (~%3$s)', 'computingskill' ), $sev[ $r['severity'] ], $short[ $res ], cs_bn_pct( $r ) );
	}
	/* translators: 1: resolution, 2: percent */
	return sprintf( __( 'GPU-limited at %1$s (~%2$s CPU headroom)', 'computingskill' ), $short[ $res ], cs_bn_pct( $r ) );
}

function cs_bn_phrases( $results, $only = null ) {
	$out = array();
	foreach ( $results as $res => $r ) {
		if ( null === $only || in_array( $res, $only, true ) ) {
			$out[] = cs_bn_phrase( $res, $r );
		}
	}
	return cs_bn_join( $out );
}

/**
 * "~20%" for one resolution, "~54% at 1080p and ~32% at 1440p" for more.
 */
function cs_bn_pcts( $results, $only ) {
	$short = cs_bn_res_short();
	if ( 1 === count( $only ) ) {
		return '~' . cs_bn_pct( $results[ $only[0] ] );
	}
	$out = array();
	foreach ( $only as $res ) {
		$out[] = sprintf( __( '~%1$s at %2$s', 'computingskill' ), cs_bn_pct( $results[ $res ] ), $short[ $res ] );
	}
	return cs_bn_join( $out );
}

function cs_bn_socket_memory( $socket ) {
	$map = array(
		'AM4'      => __( 'DDR4 only', 'computingskill' ),
		'AM5'      => __( 'DDR5 only', 'computingskill' ),
		'LGA 1700' => __( 'DDR4 or DDR5, depending on the board (never both)', 'computingskill' ),
		'LGA 1851' => __( 'DDR5 only', 'computingskill' ),
	);
	return isset( $map[ $socket ] ) ? $map[ $socket ] : '';
}

/**
 * Splits the three resolutions into: suits (GPU meets the upgrade
 * advisor's target and the CPU doesn't hold it back by 15%+), held back by
 * the CPU, and below the GPU target.
 */
function cs_bn_fit( $cpu, $gpu, $results ) {
	$targets = cs_bn_upgrade_targets();
	$fit     = array( 'suits' => array(), 'cpu_held' => array(), 'gpu_low' => array() );

	foreach ( $results as $res => $r ) {
		if ( $gpu['score'] < $targets['gpu'][ $res ] ) {
			$fit['gpu_low'][] = $res;
		} elseif ( 'cpu' === $r['component'] && $r['percent'] >= 15 ) {
			$fit['cpu_held'][] = $res;
		} else {
			$fit['suits'][] = $res;
		}
	}
	return $fit;
}

/**
 * Strongest gaming CPU on the same socket in the dataset, if it scores
 * higher than this one.
 */
function cs_bn_socket_upgrade( $cpu_slug, $cpu ) {
	$best = null;
	foreach ( cs_get_cpu_data() as $slug => $c ) {
		if ( $slug === $cpu_slug || $c['socket'] !== $cpu['socket'] || $c['score'] <= $cpu['score'] ) {
			continue;
		}
		if ( null === $best || $c['score'] > $best['score'] ) {
			$best = $c;
		}
	}
	return $best;
}

/**
 * Highest-scoring gaming CPU in the dataset.
 */
function cs_bn_top_cpu() {
	$top = null;
	foreach ( cs_get_cpu_data() as $c ) {
		if ( null === $top || $c['score'] > $top['score'] ) {
			$top = $c;
		}
	}
	return $top;
}

/**
 * Lowest-scoring faster GPU in the dataset that, with this CPU, takes the
 * 1440p estimate out of the "GPU-limited 15%+" range.
 */
function cs_bn_gpu_step( $cpu, $gpu ) {
	$gpus = cs_get_gpu_data();
	uasort( $gpus, function ( $a, $b ) {
		return $a['score'] - $b['score'];
	} );
	// D4 (Oct 2026): the database now holds older cards (GTX 16, RTX 20/30,
	// RX 6000) for owners of those parts. An upgrade suggestion should be a
	// card people can still buy new, so only cards from the newest three
	// release years in the data are suggested (2023-2025 today).
	$newest = max( array_map( function ( $g ) {
		return (int) $g['release_year'];
	}, $gpus ) );
	foreach ( $gpus as $g ) {
		if ( $g['score'] <= $gpu['score'] || (int) $g['release_year'] < $newest - 2 ) {
			continue;
		}
		$r = cs_calculate_bottleneck( $cpu, $g );
		if ( ! ( 'gpu' === $r['1440p']['component'] && $r['1440p']['percent'] >= 15 ) ) {
			return array( 'gpu' => $g, 'results' => $r );
		}
	}
	return null;
}

/**
 * Verdict paragraphs (plain text, already translated).
 *
 * @return string[]
 */
function cs_bottleneck_verdict( $cpu_slug, $cpu, $gpu, $results ) {
	$short = cs_bn_res_short();
	$fit   = cs_bn_fit( $cpu, $gpu, $results );
	$names = function ( $list ) use ( $short ) {
		return cs_bn_join( array_map( function ( $res ) use ( $short ) {
			return $short[ $res ];
		}, $list ) );
	};

	// 1. Who it suits.
	if ( $fit['suits'] ) {
		$p1 = sprintf(
			/* translators: 1: CPU, 2: GPU, 3: GPU tier label, 4: resolutions */
			__( 'Going by this page’s estimates and the %2$s’s class (%3$s), the %1$s + %2$s suits %4$s gaming.', 'computingskill' ),
			$cpu['name'], $gpu['name'], $gpu['tier'], $names( $fit['suits'] )
		);
	} else {
		$p1 = sprintf(
			__( 'By this page’s estimates, none of the three resolutions is a clean fit for the %1$s + %2$s.', 'computingskill' ),
			$cpu['name'], $gpu['name']
		);
	}
	if ( $fit['cpu_held'] ) {
		$p1 .= ' ' . sprintf(
			__( 'At %1$s the %2$s holds the card back (%3$s).', 'computingskill' ),
			$names( $fit['cpu_held'] ), $cpu['name'], cs_bn_pcts( $results, $fit['cpu_held'] )
		);
	}
	if ( $fit['gpu_low'] ) {
		$p1 .= ' ' . sprintf(
			__( 'For %1$s the %2$s sits below the GPU level our PC Upgrade Calculator looks for.', 'computingskill' ),
			$names( $fit['gpu_low'] ), $gpu['name']
		);
	}

	// 2. Upgrade path, from the same main limit as the block above.
	$primary = cs_bottleneck_primary_limit( $results );
	if ( 'gpu' === $primary ) {
		$p2   = sprintf( __( 'The %s sets the frame rate at 1440p while the CPU still has headroom, so a faster graphics card is the upgrade that adds the most FPS.', 'computingskill' ), $gpu['name'] );
		$step = cs_bn_gpu_step( $cpu, $gpu );
		if ( $step ) {
			$p2 .= ' ' . sprintf(
				__( 'If you want a stronger 1440p setup, the smallest step up among current cards in our database that uses most of the %1$s’s headroom at 1440p (under 15%% left) is the %2$s, which our engine rates as %3$s.', 'computingskill' ),
				$cpu['name'], $step['gpu']['name'], cs_bn_phrases( $step['results'] )
			);
		}
	} elseif ( 'cpu' === $primary ) {
		$p2  = sprintf( __( 'The %s is the real bottleneck here, so a CPU upgrade is what lets the graphics card run at full speed.', 'computingskill' ), $cpu['name'] );
		$up  = cs_bn_socket_upgrade( $cpu_slug, $cpu );
		if ( $up ) {
			$r2  = cs_calculate_bottleneck( $up, $gpu );
			$p2 .= ' ' . sprintf(
				__( 'On %1$s, the strongest gaming CPU in our database is the %2$s; our engine rates it with the %3$s as %4$s.', 'computingskill' ),
				$cpu['socket'], $up['name'], $gpu['name'], cs_bn_phrases( $r2 )
			);
		} else {
			$p2 .= ' ' . sprintf(
				__( 'No faster %s CPU is in our database, so a bigger CPU step means a new motherboard (and new RAM if the memory type changes).', 'computingskill' ),
				$cpu['socket']
			);
			$top = cs_bn_top_cpu();
			if ( $top && $top['score'] > $cpu['score'] ) {
				$p2 .= ' ' . sprintf(
					__( 'The strongest gaming CPU we list, the %1$s (%2$s), is rated with the %3$s as %4$s.', 'computingskill' ),
					$top['name'], $top['socket'], $gpu['name'], cs_bn_phrases( cs_calculate_bottleneck( $top, $gpu ) )
				);
			}
		}
	} elseif ( 'gpu4k' === $primary ) {
		$p2 = sprintf(
			__( 'There is no pressing upgrade: being GPU-limited at 4K is normal, and upscaling or lower settings usually help more there than new parts.', 'computingskill' )
		);
	} else {
		$p2 = __( 'Neither part needs replacing to rebalance this pair; upgrade when your resolution or frame-rate target changes.', 'computingskill' );
	}

	return array( $p1, $p2 );
}

/**
 * Pair-specific FAQ items.
 *
 * @return array[] each array( 'q' => ..., 'a' => ... ), plain text.
 */
function cs_bottleneck_faq_items( $cpu_slug, $cpu, $gpu, $results ) {
	$short = cs_bn_res_short();
	$items = array();
	$note  = __( 'These are estimates from our gaming-performance scores, not benchmark results.', 'computingskill' );

	// 1. Is the CPU fast enough?
	$cpu_res = array();
	$max_cpu = 0;
	foreach ( $results as $res => $r ) {
		if ( 'cpu' === $r['component'] ) {
			$cpu_res[] = $res;
			$max_cpu   = max( $max_cpu, $r['percent'] );
		}
	}
	$other = array_values( array_diff( array_keys( $results ), $cpu_res ) );
	if ( ! $cpu_res ) {
		$a = sprintf(
			__( 'Yes, by our bottleneck estimate. The %1$s is not the limiting part at 1080p, 1440p or 4K with the %2$s: the pairing is %3$s, so the processor isn’t what holds frame rates back in most games.', 'computingskill' ),
			$cpu['name'], $gpu['name'], cs_bn_phrases( $results )
		);
	} elseif ( ! $other ) {
		$a = sprintf(
			__( 'Not fully. Our estimate shows the %1$s as the limit at all three resolutions (%2$s), so the %3$s has capacity it can’t use, most of all at 1080p.', 'computingskill' ),
			$cpu['name'], cs_bn_phrases( $results ), $gpu['name']
		);
	} else {
		$a = sprintf(
			__( '%1$s At %2$s the %3$s is the more likely limit (%4$s), so the %5$s has unused capacity there in CPU-heavy or high-frame-rate games. At %6$s it isn’t: the pairing is %7$s.', 'computingskill' ),
			$max_cpu >= 15 ? __( 'Not at every resolution.', 'computingskill' ) : __( 'Mostly.', 'computingskill' ),
			cs_bn_join( array_map( function ( $res ) use ( $short ) { return $short[ $res ]; }, $cpu_res ) ),
			$cpu['name'],
			cs_bn_pcts( $results, $cpu_res ),
			$gpu['name'],
			cs_bn_join( array_map( function ( $res ) use ( $short ) { return $short[ $res ]; }, $other ) ),
			cs_bn_phrases( $results, $other )
		);
	}
	$items[] = array(
		'q' => sprintf( __( 'Is the %1$s fast enough for the %2$s?', 'computingskill' ), $cpu['name'], $gpu['name'] ),
		'a' => $a . ' ' . $note,
	);

	// 2. VRAM, using the GPU VRAM Calculator's guideline.
	$need  = array(
		'1080p' => array( 'high' => 6, 'ultra' => 8 ),
		'1440p' => array( 'high' => 8, 'ultra' => 10 ),
		'4k'    => array( 'high' => 12, 'ultra' => 14 ),
	);
	$vram  = (int) $gpu['vram_gb'];
	$high  = array();
	$ultra = array();
	foreach ( $need as $res => $n ) {
		if ( $vram >= $n['high'] ) {
			$high[] = $short[ $res ];
		}
		if ( $vram >= $n['ultra'] ) {
			$ultra[] = $short[ $res ];
		}
	}
	$a = sprintf(
		__( 'Our GPU VRAM Calculator’s guideline is about 6GB at 1080p, 8GB at 1440p and 12GB at 4K for high textures, 2GB more for ultra, and about 2GB more again for ray tracing.', 'computingskill' )
	);
	if ( count( $ultra ) === 3 ) {
		$a .= ' ' . sprintf( __( 'The %1$s’s %2$dGB covers ultra textures at all three resolutions.', 'computingskill' ), $gpu['name'], $vram );
	} elseif ( $ultra ) {
		$a .= ' ' . sprintf( __( 'The %1$s’s %2$dGB covers high textures at %3$s and ultra at %4$s.', 'computingskill' ), $gpu['name'], $vram, cs_bn_join( $high ), cs_bn_join( $ultra ) );
	} elseif ( $high ) {
		$a .= ' ' . sprintf( __( 'The %1$s’s %2$dGB covers high textures at %3$s, but ultra textures call for more at every resolution.', 'computingskill' ), $gpu['name'], $vram, cs_bn_join( $high ) );
	} else {
		$a .= ' ' . sprintf( __( 'The %1$s’s %2$dGB is below that guideline for high textures, so medium textures are the safer setting.', 'computingskill' ), $gpu['name'], $vram );
	}
	// Oct 2026: the calculator's figures are minimums; its "comfortable"
	// figure follows the site's VRAM guides (calculators.js CS_DEFS.vram).
	$comfy    = array( '1080p' => 8, '1440p' => 12, '4k' => 16 );
	$comfy_ok = array();
	foreach ( $comfy as $res => $gb ) {
		if ( $vram >= $gb ) {
			$comfy_ok[] = $short[ $res ];
		}
	}
	$a .= ' ' . __( 'Those are minimums; for comfortable headroom in newer games our VRAM guide suggests about 8GB at 1080p, 12GB at 1440p and 16GB at 4K.', 'computingskill' );
	if ( count( $comfy_ok ) === 3 ) {
		$a .= ' ' . __( 'This card meets that comfortable figure at every resolution.', 'computingskill' );
	} elseif ( $comfy_ok ) {
		$a .= ' ' . sprintf( __( 'This card meets it at %s.', 'computingskill' ), cs_bn_join( $comfy_ok ) );
	} else {
		$a .= ' ' . __( 'This card is below it at every resolution, so expect to lower texture quality in some newer games.', 'computingskill' );
	}
	$fit = cs_bn_fit( $cpu, $gpu, $results );
	if ( $fit['suits'] ) {
		$top = end( $fit['suits'] );
		if ( $vram >= $need[ $top ]['ultra'] ) {
			$verdict = __( 'enough even with ultra textures', 'computingskill' );
		} elseif ( $vram >= $need[ $top ]['high'] ) {
			$verdict = __( 'enough at high textures, while ultra may need lowering', 'computingskill' );
		} else {
			$verdict = __( 'tight, so medium textures are the safer choice', 'computingskill' );
		}
		$a .= ' ' . sprintf( __( 'At %1$s, the highest resolution this pairing suits, that is %2$s.', 'computingskill' ), $short[ $top ], $verdict );
	}
	$items[] = array(
		'q' => sprintf( __( 'Is %1$dGB of VRAM enough for the %2$s + %3$s?', 'computingskill' ), $vram, $cpu['name'], $gpu['name'] ),
		'a' => $a,
	);

	// 3. Same-socket CPU upgrade.
	$up  = cs_bn_socket_upgrade( $cpu_slug, $cpu );
	$mem = cs_bn_socket_memory( $cpu['socket'] );
	if ( $up ) {
		$r2 = cs_calculate_bottleneck( $up, $gpu );
		$a  = sprintf(
			__( 'Yes, within %1$s. The strongest gaming CPU on that socket in our database is the %2$s; our engine rates it with the %3$s as %4$s, compared with %5$s for the %6$s. Check your motherboard’s CPU support list first, as an older board may need a BIOS update.', 'computingskill' ),
			$cpu['socket'], $up['name'], $gpu['name'], cs_bn_phrases( $r2 ), cs_bn_phrases( $results ), $cpu['name']
		);
	} else {
		$a = sprintf(
			__( 'Not to anything faster in our database: the %1$s is already the strongest gaming CPU on %2$s that we list. A bigger step means a new motherboard, and new RAM if the memory type changes.', 'computingskill' ),
			$cpu['name'], $cpu['socket']
		);
	}
	if ( $mem ) {
		$a .= ' ' . sprintf( __( '%1$s boards take %2$s.', 'computingskill' ), $cpu['socket'], $mem );
	}
	$items[] = array(
		'q' => sprintf( __( 'Can I upgrade from the %1$s without changing the motherboard?', 'computingskill' ), $cpu['name'] ),
		'a' => $a,
	);

	// 4. Power, using the PSU Calculator's formula at its defaults.
	$cpu_max   = isset( $cpu['max_power_w'] ) ? (int) $cpu['max_power_w'] : (int) $cpu['tdp_w'];
	$draw      = $cpu_max + (int) $gpu['tdp_w'] + ( 2 * 5 ) + ( 2 * 6 ) + ( 3 * 3 ) + 50;
	$estimated = (int) ( ceil( ( $draw * 1.3 ) / 50 ) * 50 );
	$vendor    = (int) $gpu['recommended_psu_w'];
	$rec       = max( $estimated, $vendor );
	$a         = sprintf(
		__( 'Our PSU Calculator, with its default extras (2 RAM sticks, 2 drives, 3 fans, no overclock), estimates about %1$dW under load for this pair and recommends at least a %2$dW power supply.', 'computingskill' ),
		$draw, $rec
	);
	$a .= ' ' . sprintf(
		__( 'That counts the %1$s at its %2$dW maximum boost power (%3$dW TDP) and the %4$s at %5$dW; %6$s’s own recommendation for the card is %7$dW.', 'computingskill' ),
		$cpu['name'], $cpu_max, (int) $cpu['tdp_w'], $gpu['name'], (int) $gpu['tdp_w'], $gpu['brand'], $vendor
	);
	$items[] = array(
		'q' => sprintf( __( 'What power supply does the %1$s + %2$s need?', 'computingskill' ), $cpu['name'], $gpu['name'] ),
		'a' => $a,
	);

	return $items;
}

/**
 * Renders the items through [cs_faq] so markup and FAQPage schema stay the
 * same as every other FAQ on the site (one queue entry per question).
 */
function cs_bottleneck_faq_html( $items ) {
	$clean = function ( $s ) {
		return str_replace( array( '[', ']', '"' ), array( '(', ')', '' ), $s );
	};
	$sc = '[cs_faq]';
	foreach ( $items as $item ) {
		$sc .= '[cs_faq_item q="' . $clean( $item['q'] ) . '"]' . $clean( $item['a'] ) . '[/cs_faq_item]';
	}
	$sc .= '[/cs_faq]';
	return do_shortcode( $sc );
}

/**
 * Published combo pages keyed "cpu|gpu" => URL. Read from the posts, so a
 * row only links to a page that is live (same source as window.csComboPages
 * in inc/setup.php).
 */
function cs_bn_published_pairs() {
	static $pairs = null;
	if ( null !== $pairs ) {
		return $pairs;
	}
	$pairs = array();
	$ids   = get_posts( array(
		'post_type'      => 'cs_bottleneck',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	) );
	foreach ( $ids as $id ) {
		$c = get_post_meta( $id, 'cs_cpu', true );
		$g = get_post_meta( $id, 'cs_gpu', true );
		if ( $c && $g ) {
			$pairs[ $c . '|' . $g ] = get_permalink( $id );
		}
	}
	return $pairs;
}

/**
 * "CPU ~20%" / "GPU-limited" / "Balanced" for a table cell. Since D3 only a
 * CPU-side % is a bottleneck; GPU-limited is normal.
 */
function cs_bn_cell( $r ) {
	if ( 'balanced' === $r['component'] ) {
		return __( 'Balanced', 'computingskill' );
	}
	if ( 'gpu' === $r['component'] ) {
		return __( 'GPU-limited', 'computingskill' );
	}
	return sprintf( __( 'CPU ~%s', 'computingskill' ), cs_bn_pct( $r ) );
}

/**
 * Ranking-readiness fix (Oct 2026): two comparison tables built from the
 * other live combo pages that share this page's CPU or GPU, with the
 * engine's result for each. Every number comes from cs_calculate_bottleneck(),
 * so it matches those pages exactly, and the rows differ on every page.
 */
function cs_bn_neighbours_html( $cpu_slug, $gpu_slug, $cpu, $gpu ) {
	$cpus      = cs_get_cpu_data();
	$gpus      = cs_get_gpu_data();
	$published = cs_bn_published_pairs();
	$short     = cs_bn_res_short();

	$same_cpu = array();
	$same_gpu = array();
	foreach ( $published as $key => $url ) {
		list( $c, $g ) = explode( '|', $key );
		if ( $c === $cpu_slug && isset( $gpus[ $g ] ) ) {
			$same_cpu[ $g ] = array( 'part' => $gpus[ $g ], 'url' => $url, 'results' => cs_calculate_bottleneck( $cpu, $gpus[ $g ] ) );
		}
		if ( $g === $gpu_slug && isset( $cpus[ $c ] ) ) {
			$same_gpu[ $c ] = array( 'part' => $cpus[ $c ], 'url' => $url, 'results' => cs_calculate_bottleneck( $cpus[ $c ], $gpu ) );
		}
	}
	$by_score = function ( $a, $b ) {
		return $a['part']['score'] - $b['part']['score'];
	};
	uasort( $same_cpu, $by_score );
	uasort( $same_gpu, $by_score );

	$table = function ( $rows, $current, $head ) use ( $short ) {
		ob_start();
		?>
		<div class="cs-table-wrap">
			<table class="cs-hardware-table cs-bn-compare-table">
				<thead>
					<tr>
						<th><?php echo esc_html( $head ); ?></th>
						<?php foreach ( $short as $label ) : ?>
							<th><?php echo esc_html( $label ); ?></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rows as $slug => $row ) : ?>
						<tr>
							<td>
								<?php if ( $slug === $current ) : ?>
									<strong><?php echo esc_html( $row['part']['name'] ); ?></strong> (<?php esc_html_e( 'this page', 'computingskill' ); ?>)
								<?php else : ?>
									<a href="<?php echo esc_url( $row['url'] ); ?>"><?php echo esc_html( $row['part']['name'] ); ?></a>
								<?php endif; ?>
							</td>
							<?php foreach ( $row['results'] as $r ) : ?>
								<td><?php echo esc_html( cs_bn_cell( $r ) ); ?></td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
		return ob_get_clean();
	};

	$html = '';

	if ( count( $same_cpu ) > 1 ) {
		// Closest match at 1440p: lowest estimate; on a tie, the cheaper
		// (lower-scoring) card, since it gets the same balance for less.
		$best = null;
		foreach ( $same_cpu as $slug => $row ) {
			if ( null === $best || $row['results']['1440p']['percent'] < $same_cpu[ $best ]['results']['1440p']['percent'] ) {
				$best = $slug;
			}
		}
		$note = sprintf(
			/* translators: 1: CPU, 2: GPU, 3: 1440p phrase */
			__( 'Of the graphics cards we pair with the %1$s, the %2$s is the closest match at 1440p (%3$s).', 'computingskill' ),
			$cpu['name'], $same_cpu[ $best ]['part']['name'], cs_bn_phrase( '1440p', $same_cpu[ $best ]['results']['1440p'] )
		);
		if ( $best !== $gpu_slug ) {
			$note .= ' ' . sprintf(
				/* translators: 1: GPU, 2: 1440p phrase */
				__( 'This page’s %1$s is %2$s.', 'computingskill' ),
				$gpu['name'], cs_bn_phrase( '1440p', $same_cpu[ $gpu_slug ]['results']['1440p'] )
			);
		}
		$html .= '<section class="cs-bn-compare"><h2>' . esc_html( sprintf( __( 'Other Graphics Cards with the %s', 'computingskill' ), $cpu['name'] ) ) . '</h2>';
		$html .= '<p>' . esc_html( sprintf( __( 'Every %1$s pairing we have a page for, weakest card first, with the same estimate engine as above.', 'computingskill' ), $cpu['name'] ) . ' ' . $note ) . '</p>';
		$html .= $table( $same_cpu, $gpu_slug, __( 'Graphics card', 'computingskill' ) );
		$html .= '</section>';
	}

	if ( count( $same_gpu ) > 1 ) {
		// Smallest CPU in the list that keeps 1080p below the 15% line the
		// verdict uses for "holds the card back".
		$enough = null;
		foreach ( $same_gpu as $slug => $row ) {
			$r = $row['results']['1080p'];
			if ( 'cpu' !== $r['component'] || $r['percent'] < 15 ) {
				$enough = $slug;
				break;
			}
		}
		if ( null === $enough ) {
			$note = sprintf(
				/* translators: 1: GPU */
				__( 'Every CPU in this list is estimated to hold the %1$s back by 15%% or more at 1080p, which is common for a card this fast; higher resolutions move the load back onto the GPU.', 'computingskill' ),
				$gpu['name']
			);
		} else {
			$first = array_keys( $same_gpu )[0] === $enough;
			$note  = sprintf(
				$first
					/* translators: 1: CPU, 2: GPU, 3: 1080p phrase */
					? __( 'Even the least powerful CPU here, the %1$s, does not hold the %2$s back by 15%% or more at 1080p; our engine rates that pair as %3$s.', 'computingskill' )
					/* translators: 1: CPU, 2: GPU, 3: 1080p phrase */
					: __( 'The least powerful CPU here that does not hold the %2$s back by 15%% or more at 1080p is the %1$s; our engine rates that pair as %3$s.', 'computingskill' ),
				$same_gpu[ $enough ]['part']['name'], $gpu['name'], cs_bn_phrase( '1080p', $same_gpu[ $enough ]['results']['1080p'] )
			);
		}
		$html .= '<section class="cs-bn-compare"><h2>' . esc_html( sprintf( __( 'Other CPUs with the %s', 'computingskill' ), $gpu['name'] ) ) . '</h2>';
		$html .= '<p>' . esc_html( sprintf( __( 'Every %1$s pairing we have a page for, weakest CPU first.', 'computingskill' ), $gpu['name'] ) . ' ' . $note ) . '</p>';
		$html .= $table( $same_gpu, $cpu_slug, __( 'Processor', 'computingskill' ) );
		$html .= '</section>';
	}

	return $html;
}
