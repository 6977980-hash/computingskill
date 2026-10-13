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
 *  - "Who it suits" uses the PC Upgrade Calculator's targets
 *    (assets/js/calculators.js CS_DEFS.upgrade). Keep CS_BN_UPGRADE_*
 *    identical to that file's targetGpuScore / targetCpuScore.
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
		'cpu' => array( '1080p' => 55, '1440p' => 53, '4k' => 50 ),
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
 * "CPU-limited at 1080p (~20%)" / "balanced at 4K".
 */
function cs_bn_phrase( $res, $r ) {
	$short = cs_bn_res_short();
	if ( 'balanced' === $r['component'] ) {
		return sprintf( __( 'balanced at %s', 'computingskill' ), $short[ $res ] );
	}
	if ( 'cpu' === $r['component'] ) {
		return sprintf( __( 'CPU-limited at %1$s (~%2$d%%)', 'computingskill' ), $short[ $res ], $r['percent'] );
	}
	return sprintf( __( 'GPU-limited at %1$s (~%2$d%%)', 'computingskill' ), $short[ $res ], $r['percent'] );
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
		return '~' . $results[ $only[0] ]['percent'] . '%';
	}
	$out = array();
	foreach ( $only as $res ) {
		$out[] = sprintf( __( '~%1$d%% at %2$s', 'computingskill' ), $results[ $res ]['percent'], $short[ $res ] );
	}
	return cs_bn_join( $out );
}

/**
 * Resolutions where the PC Upgrade Calculator's CPU target isn't met.
 */
function cs_bn_cpu_flags( $cpu ) {
	$targets = cs_bn_upgrade_targets();
	$short   = cs_bn_res_short();
	$out     = array();
	foreach ( $targets['cpu'] as $res => $t ) {
		if ( $cpu['score'] < $t ) {
			$out[] = $short[ $res ];
		}
	}
	return $out;
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
	foreach ( $gpus as $g ) {
		if ( $g['score'] <= $gpu['score'] ) {
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
	$flags = cs_bn_cpu_flags( $cpu );
	if ( $flags ) {
		$p1 .= ' ' . sprintf(
			__( 'Our PC Upgrade Calculator would also flag the %1$s as a possible limit in CPU-heavy games at %2$s.', 'computingskill' ),
			$cpu['name'], cs_bn_join( $flags )
		);
	}

	// 2. Upgrade path, from the same main limit as the block above.
	$primary = cs_bottleneck_primary_limit( $results );
	if ( 'gpu' === $primary ) {
		$p2   = sprintf( __( 'The %s is the part that limits most at 1440p, so a graphics card upgrade changes this estimate most.', 'computingskill' ), $gpu['name'] );
		$step = cs_bn_gpu_step( $cpu, $gpu );
		if ( $step ) {
			$p2 .= ' ' . sprintf(
				__( 'If you want a stronger 1440p setup, the smallest step up in our database that brings 1440p under 15%% with the %1$s is the %2$s, which our engine rates as %3$s.', 'computingskill' ),
				$cpu['name'], $step['gpu']['name'], cs_bn_phrases( $step['results'] )
			);
		}
	} elseif ( 'cpu' === $primary ) {
		$p2  = sprintf( __( 'The %s is the part that limits most, so a CPU upgrade changes this estimate most.', 'computingskill' ), $cpu['name'] );
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
			__( 'There is no pressing upgrade: the GPU-side limit at 4K (~%d%%) is normal at that resolution, and upscaling or lower settings usually help more there than new parts.', 'computingskill' ),
			$results['4k']['percent']
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
	$note  = __( 'These are specs-based estimates, not benchmark results.', 'computingskill' );

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
	$flags = cs_bn_cpu_flags( $cpu );
	if ( $flags ) {
		$a .= ' ' . sprintf( __( 'Our PC Upgrade Calculator still flags it as a possible limit in CPU-heavy games at %s.', 'computingskill' ), cs_bn_join( $flags ) );
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
