<?php
/**
 * AEO / GEO fixes (deep audit D8, items 3.1-3.6 and 3.9).
 *
 *  - 3.2 Calculator pages were empty in the raw HTML: every field and
 *    result is built by calculators.js, so crawlers that don't run
 *    JavaScript saw no numbers. Each calculator now prints a server-side
 *    reference table under the tool (cs_aeo_calc_reference()), computed
 *    with the same formulas as calculators.js.
 *  - 3.3 The same block names its sources and links to the tool's formula
 *    on /methodology/.
 *  - 3.4 /methodology/ explained 6 tools; it now has a formula section for
 *    every tool (cs_aeo_methodology_sections()), with anchors.
 *  - 3.5 Quick answer boxes on the hubs, About, Methodology and the RAM
 *    gaming + streaming guide (cs_aeo_quick_answers()).
 *  - 3.6 Byline and "Last updated" on /, /blog/ and /games/
 *    (cs_hub_meta()), and the RSS feed credits Ali Ahmad.
 *  - 3.1 /llms.txt is generated from the content index, combos, games and
 *    posts (cs_llms_txt()) and written over the old static file.
 *
 * Keep the reference formulas in sync with calculators.js: they are the
 * same constants (PSU tally, power shares, VRAM table, data rates, transfer
 * presets). Any change there must be made here too.
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'CS_AEO_REV' ) ) {
	define( 'CS_AEO_REV', '2026-10-18-d8b' );
}

/* ------------------------------------------------------------------ */
/* Small helpers (mirrors of calculators.js)                           */
/* ------------------------------------------------------------------ */

function cs_aeo_num( $n, $d = 0 ) {
	return number_format_i18n( round( $n, $d ), $d );
}

/**
 * Same output as secondsToHuman() in calculators.js.
 */
function cs_aeo_duration( $s ) {
	if ( $s < 1 ) {
		return '< 1 second';
	}
	$s     = (int) round( $s );
	$parts = array();
	foreach ( array( 'd' => 86400, 'h' => 3600, 'm' => 60 ) as $unit => $len ) {
		if ( $s >= $len ) {
			$parts[] = floor( $s / $len ) . $unit;
			$s      %= $len;
		}
	}
	if ( $s || ! $parts ) {
		$parts[] = $s . 's';
	}
	return implode( ' ', $parts );
}

function cs_aeo_gcd( $a, $b ) {
	$a = abs( (int) round( $a ) );
	$b = abs( (int) round( $b ) );
	while ( $b ) {
		$t = $b;
		$b = $a % $b;
		$a = $t;
	}
	return $a ? $a : 1;
}

/**
 * describeRatio() in calculators.js: exact ratio plus the marketed name
 * when within 3% of a common one.
 */
function cs_aeo_ratio( $w, $h ) {
	$d      = cs_aeo_gcd( $w, $h );
	$sw     = (int) round( $w / $d );
	$sh     = (int) round( $h / $d );
	$actual = $w / $h;
	$best   = null;
	$diff   = INF;
	foreach ( array( array( 1, 1, '1:1' ), array( 5, 4, '5:4' ), array( 4, 3, '4:3' ), array( 3, 2, '3:2' ), array( 16, 10, '16:10' ), array( 16, 9, '16:9' ), array( 21, 9, '21:9' ), array( 32, 9, '32:9' ) ) as $r ) {
		$x = abs( $actual - $r[0] / $r[1] ) / ( $r[0] / $r[1] );
		if ( $x < $diff ) {
			$diff = $x;
			$best = $r;
		}
	}
	$exact    = $best && $sw * $best[1] === $sh * $best[0];
	$marketed = $best && $diff <= 0.03 ? ( $exact ? $best[2] : '≈ ' . $best[2] ) : '';
	return array( 'simple' => $sw . ':' . $sh, 'marketed' => $marketed );
}

/**
 * CPU draw figures used by the PSU, power and cooling calculators.
 */
function cs_aeo_cpu_power( $c ) {
	$max = ! empty( $c['max_power_w'] ) ? $c['max_power_w'] : $c['tdp_w'];
	return array(
		'tdp'    => $c['tdp_w'],
		'max'    => $max,
		'gaming' => $max * ( false !== strpos( $c['name'], 'X3D' ) ? 0.45 : 0.55 ),
	);
}

/**
 * CS_DEFS.psu with its default extras (2 RAM sticks, 2 drives, 3 fans,
 * no overclock): [ load W, recommended PSU W ].
 */
function cs_aeo_psu( $cpu, $gpu ) {
	$p    = cs_aeo_cpu_power( $cpu );
	$base = $p['max'] + $gpu['tdp_w'] + 2 * 5 + 2 * 6 + 3 * 3 + 50;
	$rec  = (int) ( ceil( ( $base * 1.3 ) / 50 ) * 50 );
	return array( $base, max( $rec, (int) $gpu['recommended_psu_w'] ) );
}

function cs_aeo_pick( $data, $slugs ) {
	$out = array();
	foreach ( $slugs as $s ) {
		if ( isset( $data[ $s ] ) ) {
			$out[ $s ] = $data[ $s ];
		}
	}
	return $out;
}

function cs_aeo_short( $part ) {
	return cs_bn_short_name( $part['name'] );
}

/**
 * Combo pages for a reference table, spread across budgets and seeded per
 * calculator so each page links different pairs: [ [cpu, gpu, url] ].
 */
function cs_aeo_pairs( $seed, $count = 6 ) {
	$cpus = cs_get_cpu_data();
	$gpus = cs_get_gpu_data();
	$out  = array();
	$keys = function_exists( 'cs_il_combo_sample' ) ? cs_il_combo_sample( $count, 'ref-' . $seed ) : array();
	foreach ( $keys as $key => $url ) {
		list( $c, $g ) = explode( '|', $key );
		$out[] = array( $cpus[ $c ], $gpus[ $g ], $url );
	}
	if ( ! $out ) {
		foreach ( array( array( 'ryzen-5-5600', 'rtx-4060' ), array( 'ryzen-5-7600', 'rtx-4070-super' ), array( 'ryzen-7-7800x3d', 'rtx-4080' ) ) as $p ) {
			if ( isset( $cpus[ $p[0] ], $gpus[ $p[1] ] ) ) {
				$out[] = array( $cpus[ $p[0] ], $gpus[ $p[1] ], '' );
			}
		}
	}
	return $out;
}

function cs_aeo_pair_cell( $p ) {
	$label = esc_html( cs_aeo_short( $p[0] ) . ' + ' . cs_aeo_short( $p[1] ) );
	return $p[2] ? '<a href="' . esc_url( $p[2] ) . '">' . $label . '</a>' : $label;
}

/* ------------------------------------------------------------------ */
/* 3.2 / 3.3 Reference tables                                          */
/* ------------------------------------------------------------------ */

/**
 * Methodology anchor for each calculator type.
 */
function cs_aeo_method_anchor( $type ) {
	$map = array(
		'bottleneck'         => 'm-bottleneck',
		'fps'                => 'm-fps',
		'psu'                => 'm-psu',
		'ram'                => 'm-ram',
		'power-consumption'  => 'm-power-consumption',
		'build-cost'         => 'm-power-consumption',
		'cpu-vs-gpu'         => 'm-cpu-vs-gpu',
		'cpu-performance'    => 'm-part-performance',
		'gpu-performance'    => 'm-part-performance',
		'upgrade'            => 'm-upgrade',
		'compatibility'      => 'm-compatibility',
		'vram'               => 'm-vram',
		'cooling'            => 'm-cooling',
		'cpu-power'          => 'm-part-power',
		'gpu-power'          => 'm-part-power',
		'ssd-lifespan'       => 'm-ssd',
		'ssd-tbw'            => 'm-ssd',
		'storage'            => 'm-storage',
		'storage-converter'  => 'm-storage-converter',
		'download-time'      => 'm-transfer',
		'upload-time'        => 'm-transfer',
		'file-transfer-time' => 'm-transfer',
		'mbps-to-mbs'        => 'm-mbps',
		'mbs-to-mbps'        => 'm-mbps',
		'bandwidth'          => 'm-bandwidth',
		'data-usage'         => 'm-data-usage',
		'ppi'                => 'm-density',
		'pixel-density'      => 'm-density',
		'dpi'                => 'm-density',
		'monitor-size'       => 'm-screen-size',
		'screen-size'        => 'm-screen-size',
		'aspect-ratio'       => 'm-aspect',
		'resolution'         => 'm-aspect',
		'refresh-rate'       => 'm-refresh-rate',
	);
	return isset( $map[ $type ] ) ? $map[ $type ] : '';
}

/**
 * Sources named under each table: [ [ label, url or '' ] ].
 */
function cs_aeo_sources( $type ) {
	$hw  = array( __( 'part specs from each maker’s official product page (our hardware database)', 'computingskill' ), '' );
	$rev = array( __( 'scores positioned from publicly reported review averages', 'computingskill' ), '' );
	$map = array(
		'bottleneck'         => array( $hw, $rev ),
		'fps'                => array( $hw, $rev ),
		'cpu-vs-gpu'         => array( $hw, $rev ),
		'cpu-performance'    => array( $hw, $rev ),
		'gpu-performance'    => array( $hw, $rev ),
		'upgrade'            => array( $hw, $rev ),
		'psu'                => array( $hw, array( __( 'GPU makers’ recommended PSU wattage', 'computingskill' ), '' ) ),
		'power-consumption'  => array( $hw ),
		'cooling'            => array( $hw ),
		'cpu-power'          => array( $hw, array( __( 'Intel “Maximum Turbo Power” and AMD PPT specs', 'computingskill' ), '' ) ),
		'gpu-power'          => array( $hw ),
		'vram'               => array( array( __( 'VRAM minimums listed in recent games’ published system requirements', 'computingskill' ), '' ) ),
		'compatibility'      => array(
			array( 'AMD AM5', 'https://www.amd.com/en/products/processors/chipsets/am5.html' ),
			array( 'Socket AM4', 'https://en.wikipedia.org/wiki/Socket_AM4' ),
			array( 'LGA 1700', 'https://en.wikipedia.org/wiki/LGA_1700' ),
			array( 'LGA 1851', 'https://en.wikipedia.org/wiki/LGA_1851' ),
		),
		'ssd-tbw'            => array( array( __( 'Kingston, “Understanding SSD endurance: TBW and DWPD”', 'computingskill' ), '' ) ),
		'ssd-lifespan'       => array( array( __( 'drive makers’ TBW warranty ratings', 'computingskill' ), '' ) ),
		'file-transfer-time' => array(
			array( 'USB 2.0', 'https://en.wikipedia.org/wiki/USB_2.0' ),
			array( 'USB 3.x', 'https://en.wikipedia.org/wiki/USB_3.0' ),
			array( 'SATA', 'https://en.wikipedia.org/wiki/SATA' ),
			array( 'PCI Express', 'https://en.wikipedia.org/wiki/PCI_Express' ),
		),
		'data-usage'         => array( array( __( 'Netflix Help Center: how much data Netflix uses', 'computingskill' ), 'https://help.netflix.com/en/node/87' ) ),
		'storage-converter'  => array( array( __( 'IEC binary prefixes (KiB, MiB, GiB)', 'computingskill' ), 'https://en.wikipedia.org/wiki/Binary_prefix' ) ),
	);
	return isset( $map[ $type ] ) ? $map[ $type ] : array();
}

/**
 * Builds the reference table for a calculator type:
 * [ title, head => [], rows => [ [ html cells ] ], note ] or null.
 * Cells are already escaped HTML.
 */
function cs_aeo_reference_data( $type ) {
	$cpus = cs_get_cpu_data();
	$gpus = cs_get_gpu_data();
	$e    = 'esc_html';
	$res3 = array( '1080p' => '1080p', '1440p' => '1440p', '4k' => '4K' );

	switch ( $type ) {
		case 'bottleneck':
			$rows = array();
			foreach ( cs_aeo_pairs( $type ) as $p ) {
				$r   = cs_calculate_bottleneck( $p[0], $p[1] );
				$row = array( cs_aeo_pair_cell( $p ) );
				foreach ( array_keys( $res3 ) as $res ) {
					$row[] = $e( cs_bn_label( $r[ $res ] ) );
				}
				$rows[] = $row;
			}
			return array(
				'title' => __( 'Example results', 'computingskill' ),
				'head'  => array( __( 'CPU + GPU', 'computingskill' ), '1080p', '1440p', '4K' ),
				'rows'  => $rows,
				'note'  => __( 'Typical AAA game at high settings, the calculator’s defaults. A CPU bottleneck % is the share of the GPU left idle; GPU-limited is normal and shows the CPU’s headroom.', 'computingskill' ),
			);

		case 'fps':
			$rows = array();
			foreach ( cs_aeo_pairs( $type ) as $p ) {
				$row = array( cs_aeo_pair_cell( $p ) );
				foreach ( array_keys( $res3 ) as $res ) {
					list( $fps, $lim ) = cs_tl_fps( $p[0], $p[1], $res );
					$row[] = $e( sprintf( __( '%1$d FPS (%2$s-limited)', 'computingskill' ), round( $fps ), strtoupper( $lim ) ) );
				}
				$rows[] = $row;
			}
			return array(
				'title' => __( 'Example estimates (midpoint)', 'computingskill' ),
				'head'  => array( __( 'CPU + GPU', 'computingskill' ), '1080p', '1440p', '4K' ),
				'rows'  => $rows,
				'note'  => __( 'Typical AAA game at high settings. The calculator shows a range around this midpoint (about ±15%, ±22% when CPU and GPU are close); esports games run several times higher.', 'computingskill' ),
			);

		case 'cpu-vs-gpu':
			$rows = array();
			foreach ( cs_aeo_pairs( $type, 5 ) as $p ) {
				$r   = cs_calculate_bottleneck( $p[0], $p[1] );
				$row = array( cs_aeo_pair_cell( $p ), (int) $p[0]['score'], (int) $p[1]['score'] );
				foreach ( array_keys( $res3 ) as $res ) {
					$row[] = $e( cs_bn_cell( $r[ $res ] ) );
				}
				$rows[] = $row;
			}
			return array(
				'title' => __( 'Example pairs', 'computingskill' ),
				'head'  => array( __( 'CPU + GPU', 'computingskill' ), __( 'CPU score', 'computingskill' ), __( 'GPU score', 'computingskill' ), '1080p', '1440p', '4K' ),
				'rows'  => $rows,
				'note'  => __( 'CPU and GPU scores are separate scales, so the balance comes from the bottleneck engine at each resolution, not from comparing the two numbers.', 'computingskill' ),
			);

		case 'cpu-performance':
			$rows = array();
			foreach ( cs_aeo_pick( $cpus, array( 'ryzen-7-9800x3d', 'core-i9-14900k', 'core-ultra-7-265k', 'ryzen-5-7600', 'core-i5-13400f', 'ryzen-5-5600', 'core-i3-12100f' ) ) as $c ) {
				$rows[] = array( $e( cs_aeo_short( $c ) ), (int) $c['score'], $e( cs_tl_cpu_label( $c['score'] ) ), (int) $c['cores'] . ' / ' . (int) $c['threads'], $e( $c['socket'] ) );
			}
			return array(
				'title' => __( 'Example results', 'computingskill' ),
				'head'  => array( __( 'CPU', 'computingskill' ), __( 'Gaming score', 'computingskill' ), __( 'Tier', 'computingskill' ), __( 'Cores / threads', 'computingskill' ), __( 'Socket', 'computingskill' ) ),
				'rows'  => $rows,
				'note'  => sprintf( __( 'Every CPU in our list, ranked, is on the %s.', 'computingskill' ), cs_tl_link( 'cpu-tier-list', __( 'CPU tier list', 'computingskill' ) ) ),
			);

		case 'gpu-performance':
			$rows = array();
			foreach ( cs_aeo_pick( $gpus, array( 'rtx-5090', 'rtx-4080', 'rx-9070-xt', 'rtx-4070-super', 'rx-7800-xt', 'rtx-4060', 'rx-6600' ) ) as $g ) {
				$rows[] = array( $e( cs_aeo_short( $g ) ), (int) $g['score'], $e( $g['tier'] ), $e( $g['vram_gb'] . 'GB' ), (int) $g['recommended_psu_w'] . 'W+' );
			}
			return array(
				'title' => __( 'Example results', 'computingskill' ),
				'head'  => array( __( 'GPU', 'computingskill' ), __( 'Score (RTX 4090 = 100)', 'computingskill' ), __( 'Tier', 'computingskill' ), __( 'VRAM', 'computingskill' ), __( 'Recommended PSU', 'computingskill' ) ),
				'rows'  => $rows,
				'note'  => sprintf( __( 'Every GPU in our list, ranked, is on the %s.', 'computingskill' ), cs_tl_link( 'gpu-tier-list', __( 'GPU tier list', 'computingskill' ) ) ),
			);

		case 'upgrade':
			$rows    = array();
			$targets = cs_tl_gpu_targets() + array( 'productivity' => 55 );
			$names   = array( '1080p' => __( 'Smooth 1080p gaming', 'computingskill' ), '1440p' => __( '1440p gaming', 'computingskill' ), '4k' => __( '4K gaming', 'computingskill' ), 'productivity' => __( 'Productivity / creative work', 'computingskill' ) );
			$sorted  = array_reverse( cs_tl_sorted( $gpus ), true );
			foreach ( $targets as $goal => $min ) {
				$first = null;
				foreach ( $sorted as $g ) {
					if ( $g['score'] >= $min ) {
						$first = $g;
						break;
					}
				}
				$rows[] = array( $e( $names[ $goal ] ), (int) $min, $first ? $e( cs_aeo_short( $first ) ) : '—', 'productivity' === $goal ? $e( __( 'CPU score under 59', 'computingskill' ) ) : $e( __( 'CPU bottleneck of 15%+ at that resolution', 'computingskill' ) ) );
			}
			return array(
				'title' => __( 'What the calculator checks for each goal', 'computingskill' ),
				'head'  => array( __( 'Goal', 'computingskill' ), __( 'GPU score needed', 'computingskill' ), __( 'Slowest card that meets it', 'computingskill' ), __( 'CPU is flagged when', 'computingskill' ) ),
				'rows'  => $rows,
				'note'  => __( 'Storage is flagged when the main drive is a hard disk, RAM when it is under 16GB. Upgrades are listed biggest gain first.', 'computingskill' ),
			);

		case 'psu':
			$rows = array();
			$a    = isset( $cpus['ryzen-5-7600'] ) ? $cpus['ryzen-5-7600'] : reset( $cpus );
			$b    = isset( $cpus['core-i7-14700k'] ) ? $cpus['core-i7-14700k'] : end( $cpus );
			foreach ( cs_aeo_pick( $gpus, array( 'rtx-4060', 'rtx-5060-ti', 'rtx-4070-super', 'rx-7800-xt', 'rx-9070-xt', 'rtx-5070-ti', 'rtx-5080', 'rtx-4090', 'rtx-5090' ) ) as $g ) {
				list( $la, $ra ) = cs_aeo_psu( $a, $g );
				list( $lb, $rb ) = cs_aeo_psu( $b, $g );
				$rows[] = array( $e( cs_aeo_short( $g ) ), (int) $g['recommended_psu_w'] . 'W', round( $la ) . 'W → <strong>' . $ra . 'W</strong>', round( $lb ) . 'W → <strong>' . $rb . 'W</strong>' );
			}
			return array(
				'title' => __( 'Recommended PSU for common builds', 'computingskill' ),
				'head'  => array( __( 'GPU', 'computingskill' ), __( 'GPU maker’s PSU', 'computingskill' ), sprintf( __( 'With %s: load → PSU', 'computingskill' ), cs_aeo_short( $a ) ), sprintf( __( 'With %s: load → PSU', 'computingskill' ), cs_aeo_short( $b ) ) ),
				'rows'  => $rows,
				'note'  => __( 'Calculator defaults: 2 RAM sticks, 2 drives, 3 fans, no overclock. The load counts the CPU at its maximum turbo/boost power; the PSU adds 30% headroom, rounded up to 50W, and never goes below the GPU maker’s figure.', 'computingskill' ),
			);

		case 'power-consumption':
			$rows = array();
			foreach ( array( array( 'ryzen-5-5600', 'rtx-4060' ), array( 'ryzen-5-7600', 'rtx-4070-super' ), array( 'ryzen-7-7800x3d', 'rtx-4080' ), array( 'core-i9-14900k', 'rtx-4090' ) ) as $p ) {
				if ( ! isset( $cpus[ $p[0] ], $gpus[ $p[1] ] ) ) {
					continue;
				}
				$c    = cs_aeo_cpu_power( $cpus[ $p[0] ] );
				$g    = $gpus[ $p[1] ];
				$rest = 2 * 5 + 2 * 6 + 3 * 3 + 50;
				$load = ( $c['gaming'] + $g['tdp_w'] + $rest ) / 0.88;
				$idle = ( ( $c['tdp'] + $g['tdp_w'] ) * 0.15 + $rest * 0.3 ) / 0.8;
				$cost = ( $load * 3 + $idle * 3 ) / 1000 * 0.15 * 30;
				$rows[] = array( $e( cs_aeo_short( $cpus[ $p[0] ] ) . ' + ' . cs_aeo_short( $g ) ), round( $load ) . 'W', round( $idle ) . 'W', '$' . cs_aeo_num( $cost, 2 ) );
			}
			return array(
				'title' => __( 'Example builds', 'computingskill' ),
				'head'  => array( __( 'Build', 'computingskill' ), __( 'From the wall, gaming', 'computingskill' ), __( 'From the wall, idle', 'computingskill' ), __( 'Per month', 'computingskill' ) ),
				'rows'  => $rows,
				'note'  => __( 'Per month = 3 hours of gaming and 3 hours of idle or light use a day for 30 days, at $0.15 per kWh, with 2 RAM sticks, 2 drives and 3 fans.', 'computingskill' ),
			);

		case 'build-cost':
			$rows = array();
			foreach ( array( 800, 1000, 1200, 1500, 2000 ) as $budget ) {
				$rows[] = array( '$' . cs_aeo_num( $budget ), '$' . cs_aeo_num( $budget * 0.25 ) . ' – $' . cs_aeo_num( $budget * 0.33 ) );
			}
			return array(
				'title' => __( 'GPU share at common budgets', 'computingskill' ),
				'head'  => array( __( 'PC parts budget', 'computingskill' ), __( 'GPU at the 25–33% guide', 'computingskill' ) ),
				'rows'  => $rows,
				'note'  => __( 'The calculator compares your GPU’s share of the parts subtotal with this 25–33% gaming guide; peripherals and tax are kept out of the split. It is a guide, not a rule.', 'computingskill' ),
			);

		case 'cooling':
			$rows = array();
			foreach ( cs_aeo_pick( $cpus, array( 'ryzen-5-5600', 'ryzen-5-7600', 'ryzen-7-7800x3d', 'core-i5-13600k', 'core-i7-14700k', 'core-i9-14900k' ) ) as $c ) {
				$heat   = cs_aeo_cpu_power( $c )['max'];
				$cooler = $heat <= 120 ? __( 'Single-tower air cooler', 'computingskill' ) : ( $heat <= 200 ? __( 'Large dual-tower air or 240mm AIO', 'computingskill' ) : __( '280–360mm AIO or top dual-tower air', 'computingskill' ) );
				$rows[] = array( $e( cs_aeo_short( $c ) ), (int) $heat . 'W', $e( $cooler ) );
			}
			return array(
				'title' => __( 'Recommended CPU cooler by processor', 'computingskill' ),
				'head'  => array( __( 'CPU', 'computingskill' ), __( 'Max turbo/boost power', 'computingskill' ), __( 'Cooler (no overclock)', 'computingskill' ) ),
				'rows'  => $rows,
				'note'  => __( 'Case fans step up with the combined CPU + GPU heat: in a mid tower 3 fans under 350W, 4–5 fans up to 550W, 5–6 fans above that.', 'computingskill' ),
			);

		case 'cpu-power':
			$rows = array();
			foreach ( cs_aeo_pick( $cpus, array( 'ryzen-5-5600', 'ryzen-5-7600', 'ryzen-7-7800x3d', 'ryzen-7-9800x3d', 'core-i5-13400f', 'core-i5-14600k', 'core-i9-14900k' ) ) as $c ) {
				$p      = cs_aeo_cpu_power( $c );
				$rows[] = array( $e( cs_aeo_short( $c ) ), (int) $c['tdp_w'] . 'W', round( $c['tdp_w'] * 0.15 ) . 'W', round( $p['gaming'] ) . 'W', (int) $p['max'] . 'W' );
			}
			return array(
				'title' => __( 'Estimated CPU power draw', 'computingskill' ),
				'head'  => array( __( 'CPU', 'computingskill' ), __( 'Rated TDP', 'computingskill' ), __( 'Idle', 'computingskill' ), __( 'Gaming', 'computingskill' ), __( 'Full load', 'computingskill' ) ),
				'rows'  => $rows,
				'note'  => __( 'Idle is about 15% of TDP, gaming about 55% of the max turbo/boost power (45% for X3D chips), full load the maker’s max power spec.', 'computingskill' ),
			);

		case 'gpu-power':
			$rows = array();
			foreach ( cs_aeo_pick( $gpus, array( 'rtx-4060', 'rtx-5060-ti', 'rtx-4070-super', 'rx-7800-xt', 'rx-9070-xt', 'rtx-5080', 'rtx-4090', 'rtx-5090' ) ) as $g ) {
				$rows[] = array( $e( cs_aeo_short( $g ) ), (int) $g['tdp_w'] . 'W', round( $g['tdp_w'] * 0.15 ) . 'W', round( $g['tdp_w'] * 0.7 ) . 'W', round( $g['tdp_w'] * 1.08 ) . 'W' );
			}
			return array(
				'title' => __( 'Estimated GPU power draw', 'computingskill' ),
				'head'  => array( __( 'GPU', 'computingskill' ), __( 'Board power', 'computingskill' ), __( 'Idle', 'computingskill' ), __( 'Gaming', 'computingskill' ), __( 'Full load', 'computingskill' ) ),
				'rows'  => $rows,
				'note'  => __( 'Idle is about 15% of rated board power, gaming about 70%, full load about 108% for boost spikes.', 'computingskill' ),
			);

		case 'vram':
			$base  = cs_bottleneck_vram_need();
			$comfy = array( '1080p' => 8, '1440p' => 12, '4k' => 16 );
			$rows  = array();
			foreach ( $res3 as $res => $label ) {
				$rows[] = array( $e( $label ), $base[ $res ]['medium'] . 'GB', $base[ $res ]['high'] . 'GB', $base[ $res ]['ultra'] . 'GB', max( $comfy[ $res ], $base[ $res ]['high'] ) . 'GB' );
			}
			return array(
				'title' => __( 'Minimum VRAM by resolution and texture quality', 'computingskill' ),
				'head'  => array( __( 'Resolution', 'computingskill' ), __( 'Medium', 'computingskill' ), __( 'High', 'computingskill' ), __( 'Ultra', 'computingskill' ), __( 'Comfortable', 'computingskill' ) ),
				'rows'  => $rows,
				'note'  => __( 'Add 2GB for ray tracing and 2GB to hold up for 3+ years. Comfortable is at least 8GB at 1080p, 12GB at 1440p and 16GB at 4K, rounded up to a size cards ship with.', 'computingskill' ),
			);

		case 'ram':
			$uses = array( 'basic' => array( __( 'Web browsing / office work', 'computingskill' ), 8 ), 'gaming' => array( __( 'Gaming', 'computingskill' ), 16 ), 'streaming' => array( __( 'Gaming + streaming/recording', 'computingskill' ), 16 ), 'creation' => array( __( 'Video/photo editing', 'computingskill' ), 32 ), 'dev' => array( __( 'Programming / development', 'computingskill' ), 16 ), 'heavy' => array( __( 'Virtual machines / heavy multitasking', 'computingskill' ), 32 ) );
			$kit  = function ( $gb ) {
				foreach ( array( 8, 16, 32, 64, 128 ) as $k ) {
					if ( $k >= $gb ) {
						return $k;
					}
				}
				return 128;
			};
			$rows = array();
			foreach ( $uses as $u ) {
				$rows[] = array( $e( $u[0] ), $kit( $u[1] ) . 'GB', $kit( $u[1] + 4 ) . 'GB', $kit( $u[1] + 8 ) . 'GB' );
			}
			return array(
				'title' => __( 'Recommended RAM kit by use', 'computingskill' ),
				'head'  => array( __( 'Main use', 'computingskill' ), __( 'A few apps open', 'computingskill' ), __( 'A moderate amount', 'computingskill' ), __( 'A lot, all the time', 'computingskill' ) ),
				'rows'  => $rows,
				'note'  => __( 'Kit sizes as sold (two sticks for dual channel). A general guideline, not measured memory use.', 'computingskill' ),
			);

		case 'compatibility':
			$mem  = array( 'AM5' => 'DDR5', 'AM4' => 'DDR4', 'LGA 1851' => 'DDR5', 'LGA 1700' => __( 'DDR4 or DDR5 (each board takes one)', 'computingskill' ), 'LGA 1200' => 'DDR4' );
			$bios = array(
				'AM5'      => __( 'Ryzen 9000 on a 600-series board needs a BIOS from mid-2024 or later', 'computingskill' ),
				'AM4'      => __( 'Ryzen 5000 on 300/400-series boards needs a BIOS update (not every A320 board got it)', 'computingskill' ),
				'LGA 1851' => __( 'Supported on every 800-series board', 'computingskill' ),
				'LGA 1700' => __( '13th/14th Gen on a 600-series board needs a BIOS update', 'computingskill' ),
				'LGA 1200' => '—',
			);
			$rows = array();
			foreach ( $mem as $socket => $ram ) {
				$list = array();
				foreach ( $cpus as $c ) {
					if ( $c['socket'] === $socket ) {
						$list[] = $c;
					}
				}
				$rows[] = array( $e( $socket ), $e( $ram ), $e( $list ? cs_tl_names( array_slice( cs_tl_sorted( $list ), 0, 3 ) ) : '—' ), $e( $bios[ $socket ] ) );
			}
			return array(
				'title' => __( 'Socket rules the checker uses', 'computingskill' ),
				'head'  => array( __( 'Socket', 'computingskill' ), __( 'Memory', 'computingskill' ), __( 'Examples in our list', 'computingskill' ), __( 'BIOS note', 'computingskill' ) ),
				'rows'  => $rows,
				'note'  => __( 'The PSU check uses the PSU Calculator’s sum for the picked CPU and GPU; every RTX 4080, 4090, 5080 and 5090 takes the 16-pin 12V-2x6 plug.', 'computingskill' ),
			);

		case 'ssd-lifespan':
			$rows = array();
			foreach ( array( 300, 600, 1200 ) as $tbw ) {
				$row = array( $tbw . ' TBW' );
				foreach ( array( 20, 50, 100 ) as $gb ) {
					$row[] = cs_aeo_num( $tbw * 1000 / ( $gb * 365 ), 1 ) . ' ' . __( 'years', 'computingskill' );
				}
				$rows[] = $row;
			}
			return array(
				'title' => __( 'Rated lifespan at common write rates', 'computingskill' ),
				'head'  => array( __( 'Endurance rating', 'computingskill' ), __( '20GB/day', 'computingskill' ), __( '50GB/day', 'computingskill' ), __( '100GB/day', 'computingskill' ) ),
				'rows'  => $rows,
				'note'  => __( 'TBW is a warranty floor, not a hard cutoff; most SSDs keep working past it.', 'computingskill' ),
			);

		case 'ssd-tbw':
			$rows = array();
			foreach ( array( 500, 1000, 2000, 4000 ) as $cap ) {
				$row = array( ( $cap >= 1000 ? ( $cap / 1000 ) . 'TB' : $cap . 'GB' ) );
				foreach ( array( 0.3, 0.6, 1 ) as $dwpd ) {
					$row[] = cs_aeo_num( $dwpd * $cap * 365 * 5 / 1000 ) . ' TBW';
				}
				$rows[] = $row;
			}
			return array(
				'title' => __( 'TBW for a 5-year warranty', 'computingskill' ),
				'head'  => array( __( 'Capacity', 'computingskill' ), '0.3 DWPD', '0.6 DWPD', '1 DWPD' ),
				'rows'  => $rows,
				'note'  => __( 'TBW = DWPD × capacity (GB) × 365 × warranty years ÷ 1,000.', 'computingskill' ),
			);

		case 'storage':
			$rows = array();
			foreach ( array( 5, 50, 500, 50000 ) as $mb ) {
				$row = array( $mb >= 1000 ? ( $mb / 1000 ) . 'GB' : $mb . 'MB' );
				foreach ( array( 500, 1000, 2000 ) as $gb ) {
					$row[] = cs_aeo_num( floor( $gb * 1000 / $mb ) );
				}
				$rows[] = $row;
			}
			return array(
				'title' => __( 'How many files fit on an empty drive', 'computingskill' ),
				'head'  => array( __( 'Average file size', 'computingskill' ), '500GB', '1TB', '2TB' ),
				'rows'  => $rows,
				'note'  => __( 'Decimal units (1GB = 1,000MB), as drive makers count. Windows shows a 1TB drive as about 931GB because it counts in 1,024s.', 'computingskill' ),
			);

		case 'storage-converter':
			$rows = array();
			foreach ( array( 'KB' => array( 1e3, 1024, 'KiB' ), 'MB' => array( 1e6, pow( 1024, 2 ), 'MiB' ), 'GB' => array( 1e9, pow( 1024, 3 ), 'GiB' ), 'TB' => array( 1e12, pow( 1024, 4 ), 'TiB' ) ) as $u => $v ) {
				$rows[] = array( '1 ' . $u, cs_aeo_num( $v[0] ) . ' bytes', '1 ' . $v[2] . ' = ' . cs_aeo_num( $v[1] ) . ' bytes', '1 ' . $u . ' = ' . cs_aeo_num( $v[0] / $v[1], 4 ) . ' ' . $v[2] );
			}
			return array(
				'title' => __( 'Decimal vs binary units', 'computingskill' ),
				'head'  => array( __( 'Unit', 'computingskill' ), __( 'Decimal (drive makers)', 'computingskill' ), __( 'Binary (Windows)', 'computingskill' ), __( 'Conversion', 'computingskill' ) ),
				'rows'  => $rows,
				'note'  => __( '1 byte = 8 bits. A “1TB” drive holds 1,000,000,000,000 bytes, which is about 931 GiB.', 'computingskill' ),
			);

		case 'download-time':
		case 'upload-time':
			$speeds = array( 25, 100, 300, 1000 );
			$rows   = array();
			foreach ( array( 1, 10, 50, 100 ) as $gb ) {
				$row = array( $gb . 'GB' );
				foreach ( $speeds as $mbps ) {
					$row[] = $e( cs_aeo_duration( $gb * 1000 / ( $mbps / 8 ) ) );
				}
				$rows[] = $row;
			}
			return array(
				'title' => 'download-time' === $type ? __( 'Download times at common speeds', 'computingskill' ) : __( 'Upload times at common speeds', 'computingskill' ),
				'head'  => array( __( 'File size', 'computingskill' ), '25 Mbps', '100 Mbps', '300 Mbps', '1 Gbps' ),
				'rows'  => $rows,
				'note'  => __( 'Theoretical best case (speed ÷ 8 = MB/s, 1GB = 1,000MB). Real transfers usually take 10–20% longer.', 'computingskill' ),
			);

		case 'file-transfer-time':
			$presets = array( 'USB 2.0' => 53, 'USB 3.2 Gen 1 (5 Gbps)' => 500, 'USB 3.2 Gen 2 (10 Gbps)' => 1212, 'SATA III' => 600, 'NVMe PCIe 3.0 x4' => 3938, 'NVMe PCIe 4.0 x4' => 7877, 'Gigabit Ethernet' => 125, '2.5 Gigabit Ethernet' => 312.5 );
			$rows    = array();
			foreach ( $presets as $label => $mbs ) {
				$rows[] = array( $e( $label ), cs_aeo_num( $mbs ) . ' MB/s', $e( cs_aeo_duration( 50000 / ( $mbs * 0.8 ) ) ), $e( cs_aeo_duration( 50000 / ( $mbs * 0.5 ) ) ) );
			}
			return array(
				'title' => __( 'Time to copy 50GB', 'computingskill' ),
				'head'  => array( __( 'Interface', 'computingskill' ), __( 'Usable maximum', 'computingskill' ), __( 'Large files (~80%)', 'computingskill' ), __( 'Many small files (~50%)', 'computingskill' ) ),
				'rows'  => $rows,
				'note'  => __( 'The slowest part of the chain sets the real speed: a hard drive (about 80–160 MB/s) caps a fast interface well below its maximum.', 'computingskill' ),
			);

		case 'mbps-to-mbs':
		case 'mbs-to-mbps':
			$rows = array();
			if ( 'mbps-to-mbs' === $type ) {
				foreach ( array( 10, 25, 50, 100, 200, 300, 500, 1000, 2500 ) as $v ) {
					$rows[] = array( cs_aeo_num( $v ) . ' Mbps', cs_aeo_num( $v / 8, 2 ) . ' MB/s' );
				}
				$head = array( __( 'Speed (Mbps)', 'computingskill' ), __( 'Speed (MB/s)', 'computingskill' ) );
			} else {
				foreach ( array( 1, 5, 10, 12.5, 25, 50, 100, 125 ) as $v ) {
					$rows[] = array( cs_aeo_num( $v, 1 ) . ' MB/s', cs_aeo_num( $v * 8 ) . ' Mbps' );
				}
				$head = array( __( 'Speed (MB/s)', 'computingskill' ), __( 'Speed (Mbps)', 'computingskill' ) );
			}
			return array(
				'title' => __( 'Common speeds converted', 'computingskill' ),
				'head'  => $head,
				'rows'  => $rows,
				'note'  => __( '8 bits = 1 byte, so MB/s = Mbps ÷ 8 and Mbps = MB/s × 8.', 'computingskill' ),
			);

		case 'bandwidth':
			$rows = array();
			foreach ( array( 1, 10, 50, 100 ) as $gb ) {
				$row = array( $gb . 'GB' );
				foreach ( array( 600, 3600, 28800 ) as $sec ) {
					$row[] = cs_aeo_num( $gb * 8000 / $sec, 1 ) . ' Mbps';
				}
				$rows[] = $row;
			}
			return array(
				'title' => __( 'Bandwidth needed to move data in time', 'computingskill' ),
				'head'  => array( __( 'Data', 'computingskill' ), __( 'In 10 minutes', 'computingskill' ), __( 'In 1 hour', 'computingskill' ), __( 'In 8 hours', 'computingskill' ) ),
				'rows'  => $rows,
				'note'  => __( 'Mbps = data in megabits (1GB = 8,000 megabits) ÷ seconds. Leave 10–20% spare for overhead.', 'computingskill' ),
			);

		case 'data-usage':
			$rates = array( __( 'Video streaming, SD', 'computingskill' ) => 1.0, __( 'Video streaming, HD', 'computingskill' ) => 3.0, __( 'Video streaming, 4K', 'computingskill' ) => 7.0, __( 'Video calls (720p)', 'computingskill' ) => 1.0, __( 'Online gaming', 'computingskill' ) => 0.1, __( 'Browsing / social media', 'computingskill' ) => 0.06 );
			$rows  = array();
			foreach ( $rates as $label => $gbh ) {
				$rows[] = array( $e( $label ), cs_aeo_num( $gbh, 2 ) . ' GB', cs_aeo_num( $gbh * 30, 1 ) . ' GB' );
			}
			return array(
				'title' => __( 'Data used per activity', 'computingskill' ),
				'head'  => array( __( 'Activity', 'computingskill' ), __( 'Per hour', 'computingskill' ), __( 'Per month at 1 hour a day', 'computingskill' ) ),
				'rows'  => $rows,
				'note'  => __( 'Streaming rates are Netflix’s published figures; game downloads and updates are not included in online gaming.', 'computingskill' ),
			);

		case 'ppi':
		case 'pixel-density':
			$rows = array();
			foreach ( array( array( 24, 1920, 1080 ), array( 27, 1920, 1080 ), array( 27, 2560, 1440 ), array( 32, 2560, 1440 ), array( 27, 3840, 2160 ), array( 32, 3840, 2160 ), array( 34, 3440, 1440 ) ) as $m ) {
				$ppi    = sqrt( $m[1] * $m[1] + $m[2] * $m[2] ) / $m[0];
				$cat    = $ppi < 100 ? __( 'Standard density', 'computingskill' ) : ( $ppi < 150 ? __( 'Sharp', 'computingskill' ) : ( $ppi < 300 ? __( 'High density', 'computingskill' ) : __( 'Very high density', 'computingskill' ) ) );
				$rows[] = array( $m[0] . '″ ' . $m[1] . '×' . $m[2], cs_aeo_num( $ppi, 1 ), $e( $cat ) );
			}
			return array(
				'title' => __( 'PPI of common monitors', 'computingskill' ),
				'head'  => array( __( 'Monitor', 'computingskill' ), 'PPI', __( 'Category', 'computingskill' ) ),
				'rows'  => $rows,
				'note'  => __( 'PPI = √(width² + height²) ÷ diagonal in inches.', 'computingskill' ),
			);

		case 'dpi':
			$rows = array();
			foreach ( array( 2000, 3000, 4000, 6000 ) as $px ) {
				$row = array( cs_aeo_num( $px ) . ' px' );
				foreach ( array( 6, 8, 10, 16 ) as $in ) {
					$row[] = cs_aeo_num( $px / $in ) . ' DPI';
				}
				$rows[] = $row;
			}
			return array(
				'title' => __( 'Print resolution by image width and print width', 'computingskill' ),
				'head'  => array( __( 'Image width', 'computingskill' ), __( '6 in', 'computingskill' ), __( '8 in', 'computingskill' ), __( '10 in', 'computingskill' ), __( '16 in', 'computingskill' ) ),
				'rows'  => $rows,
				'note'  => __( 'DPI = image width in pixels ÷ print width in inches; 300 DPI or more is the usual mark for photo-quality prints.', 'computingskill' ),
			);

		case 'monitor-size':
		case 'screen-size':
			$rows = array();
			foreach ( array( array( 24, 16, 9 ), array( 27, 16, 9 ), array( 32, 16, 9 ), array( 34, 21, 9 ), array( 49, 32, 9 ) ) as $m ) {
				$k      = sqrt( $m[1] * $m[1] + $m[2] * $m[2] );
				$w      = $m[0] * $m[1] / $k;
				$h      = $m[0] * $m[2] / $k;
				$rows[] = array( $m[0] . '″ ' . $m[1] . ':' . $m[2], cs_aeo_num( $w, 1 ) . ' in (' . cs_aeo_num( $w * 2.54, 1 ) . ' cm)', cs_aeo_num( $h, 1 ) . ' in (' . cs_aeo_num( $h * 2.54, 1 ) . ' cm)' );
			}
			return array(
				'title' => __( 'Screen width and height by size', 'computingskill' ),
				'head'  => array( __( 'Diagonal and ratio', 'computingskill' ), __( 'Width', 'computingskill' ), __( 'Height', 'computingskill' ) ),
				'rows'  => $rows,
				'note'  => __( 'Panel area only; the stand and bezels add a little.', 'computingskill' ),
			);

		case 'aspect-ratio':
		case 'resolution':
			$names = array( '1280x720' => 'HD (720p)', '1366x768' => 'WXGA', '1920x1080' => 'Full HD (1080p)', '2560x1080' => 'UW-FHD', '2560x1440' => 'QHD (1440p)', '2560x1600' => 'WQXGA', '3440x1440' => 'UWQHD (ultrawide)', '3840x2160' => '4K UHD (2160p)' );
			$rows  = array();
			foreach ( $names as $key => $name ) {
				list( $w, $h ) = array_map( 'intval', explode( 'x', $key ) );
				$r      = cs_aeo_ratio( $w, $h );
				$rows[] = array( $w . '×' . $h, $e( $name ), $e( $r['simple'] . ( $r['marketed'] && $r['marketed'] !== $r['simple'] ? ' (' . $r['marketed'] . ')' : '' ) ), cs_aeo_num( $w * $h / 1e6, 2 ) . ' MP' );
			}
			return array(
				'title' => __( 'Common resolutions', 'computingskill' ),
				'head'  => array( __( 'Resolution', 'computingskill' ), __( 'Name', 'computingskill' ), __( 'Aspect ratio', 'computingskill' ), __( 'Megapixels', 'computingskill' ) ),
				'rows'  => $rows,
				'note'  => __( '≈ marks panels sold under the nearest round ratio, such as 3440×1440 (43:18) as 21:9.', 'computingskill' ),
			);

		case 'refresh-rate':
			$rows = array();
			foreach ( array( 30, 60, 75, 120, 144, 165, 240, 360, 500 ) as $hz ) {
				$rows[] = array( $hz . ' Hz', cs_aeo_num( 1000 / $hz, 2 ) . ' ms' );
			}
			return array(
				'title' => __( 'Refresh rate to frame time', 'computingskill' ),
				'head'  => array( __( 'Refresh rate', 'computingskill' ), __( 'Time per frame', 'computingskill' ) ),
				'rows'  => $rows,
				'note'  => __( 'Frame time (ms) = 1,000 ÷ Hz.', 'computingskill' ),
			);
	}
	return null;
}

/**
 * The block printed under each calculator: table, note, sources and the
 * link to the formula on /methodology/.
 */
function cs_aeo_calc_reference( $type ) {
	$data = cs_aeo_reference_data( $type );
	if ( ! $data || empty( $data['rows'] ) ) {
		return '';
	}
	$html  = '<div class="cs-calc-ref">';
	$html .= '<div class="cs-table-wrap"><table class="cs-hardware-table cs-ref-table"><caption>' . esc_html( $data['title'] ) . '</caption><thead><tr>';
	foreach ( $data['head'] as $h ) {
		$html .= '<th scope="col">' . esc_html( $h ) . '</th>';
	}
	$html .= '</tr></thead><tbody>';
	foreach ( $data['rows'] as $row ) {
		$html .= '<tr>';
		foreach ( $row as $i => $cell ) {
			$html .= ( 0 === $i ? '<th scope="row">' : '<td>' ) . $cell . ( 0 === $i ? '</th>' : '</td>' );
		}
		$html .= '</tr>';
	}
	$html .= '</tbody></table></div>';
	if ( ! empty( $data['note'] ) ) {
		$html .= '<p class="cs-calc-ref-note">' . wp_kses_post( $data['note'] ) . '</p>';
	}

	$sources = array();
	foreach ( cs_aeo_sources( $type ) as $s ) {
		$sources[] = $s[1] ? '<a href="' . esc_url( $s[1] ) . '" rel="noopener" target="_blank">' . esc_html( $s[0] ) . '</a>' : esc_html( $s[0] );
	}
	$anchor = cs_aeo_method_anchor( $type );
	$method = get_page_by_path( 'methodology' );
	$line   = '';
	if ( $method && $anchor ) {
		$line = sprintf(
			/* translators: %s: link */
			__( 'How this is calculated: %s.', 'computingskill' ),
			'<a href="' . esc_url( get_permalink( $method ) . '#' . $anchor ) . '">' . esc_html__( 'the formula on our Methodology page', 'computingskill' ) . '</a>'
		);
	}
	if ( $sources ) {
		$line .= ( $line ? ' ' : '' ) . esc_html__( 'Sources:', 'computingskill' ) . ' ' . implode( '; ', $sources ) . '.';
	}
	if ( $line ) {
		$html .= '<p class="cs-calc-sources">' . $line . '</p>';
	}
	return $html . '</div>';
}

/* ------------------------------------------------------------------ */
/* 3.4 Methodology: a formula for every tool                           */
/* ------------------------------------------------------------------ */

function cs_aeo_methodology_sections() {
	$t = cs_tl_gpu_targets();
	return array(
		'm-cpu-vs-gpu'         => array( __( 'CPU vs GPU Calculator', 'computingskill' ), __( 'Shows each part’s score next to the highest score in our list, and the bottleneck engine’s result at 1080p, 1440p and 4K for a typical AAA game at high settings. The headline comes from that engine, never from comparing the raw CPU and GPU scores, which are separate scales.', 'computingskill' ) ),
		'm-part-performance'   => array( __( 'CPU and GPU Performance Calculators, and the Tier Lists', 'computingskill' ), sprintf( __( 'The CPU Performance Calculator labels a CPU’s gaming score: Flagship 77 or more, High-end 66–76, Mid-range 54–65, Entry-level below 54. The GPU Performance Calculator shows the card’s score (RTX 4090 = 100) and its tier label from our hardware data, which names the resolution it is built for. Cores, clocks, VRAM, bus width, TDP and the maker’s recommended PSU are published specs. The GPU tier list uses the PC Upgrade Calculator’s targets as its lines (S 85+, A %3$d+, B %2$d+, C %1$d+, D below) and the CPU tier list the same lines as the CPU Performance Calculator (S 85+, A 77+, B 66+, C 54+); their FPS columns use the FPS Calculator formula above.', 'computingskill' ), (int) $t['1080p'], (int) $t['1440p'], (int) $t['4k'] ) ),
		'm-upgrade'            => array( __( 'PC Upgrade Calculator', 'computingskill' ), sprintf( __( 'Lists upgrades biggest gain first. The GPU is flagged when its score is under the target for your goal (1080p %1$d, 1440p %2$d, 4K %3$d, creative work 55), ranked by how far below it is in percent. For gaming, the CPU is flagged when the bottleneck engine shows a CPU bottleneck of 15% or more at your target resolution (typical AAA game, high settings); for creative work, when its score is under 59. A hard disk as the main drive ranks at 30, RAM at 8GB or less at 40 and under 16GB at 20.', 'computingskill' ), (int) $t['1080p'], (int) $t['1440p'], (int) $t['4k'] ) ),
		'm-compatibility'      => array( __( 'PC Compatibility Checker', 'computingskill' ), __( 'Each check is a published platform rule: the CPU’s socket against the board’s; the memory each socket takes (AM4 and LGA 1200 DDR4 only, AM5 and LGA 1851 DDR5 only, LGA 1700 either, one type per board); BIOS needs by chipset generation from AMD’s and Intel’s CPU support lists; board size against the case; cooler height and GPU length against the case’s limits; the PSU against the PSU Calculator’s sum for the picked CPU and GPU with its default extras; and a 16-pin 12V-2x6 GPU plug against the PSU’s cables. Checks left on “Not sure” are skipped.', 'computingskill' ) ),
		'm-vram'               => array( __( 'GPU VRAM Calculator', 'computingskill' ), __( 'Minimum VRAM starts from a baseline per resolution and texture quality (1080p 6/6/8GB for medium/high/ultra, 1440p 6/8/10GB, 4K 8/12/14GB), then adds 2GB for ray tracing and 2GB to hold up for three or more years. The comfortable figure is the larger of the minimum and 8GB at 1080p, 12GB at 1440p or 16GB at 4K, rounded up to a size cards ship with. The cards listed are those in our data with at least that much VRAM. The bottleneck engine uses the same minimums.', 'computingskill' ) ),
		'm-cooling'            => array( __( 'PC Cooling Calculator', 'computingskill' ), __( 'Uses the CPU’s maximum turbo/boost power, not its base TDP, as its heat. Up to 120W without overclocking: a single-tower air cooler; up to 200W: a large dual-tower air cooler or 240mm AIO; above that: a 280–360mm AIO or a top dual-tower cooler. Case fans step up with the combined CPU and GPU heat (plus 30W when overclocking) at 350W and 550W, with counts set per case size.', 'computingskill' ) ),
		'm-part-power'         => array( __( 'CPU and GPU Power Consumption Calculators', 'computingskill' ), __( 'Idle is about 15% of the rated TDP. Gaming: a CPU at about 55% of its maximum turbo/boost power (45% for X3D chips, whose cache keeps clocks and power lower), a GPU at about 70% of its board power. Full load: a CPU at the maker’s maximum turbo/boost power spec, a GPU at its board power plus about 8% for boost spikes.', 'computingskill' ) ),
		'm-ssd'                => array( __( 'SSD Lifespan and SSD TBW Calculators', 'computingskill' ), __( 'Lifespan in years = TBW × 1,000 ÷ (GB written per day × 365). TBW and DWPD convert with the relationship drive makers publish: DWPD = TBW × 1,000 ÷ (365 × warranty years × capacity in GB), so TBW = DWPD × capacity × 365 × years ÷ 1,000 (Kingston’s SSD endurance guide). Both are warranty ratings, not a failure point.', 'computingskill' ) ),
		'm-storage'            => array( __( 'Storage Calculator', 'computingskill' ), __( 'Space needed = average file size × number of files, in decimal units (1GB = 1,000MB); space left = drive capacity − space already used − space needed.', 'computingskill' ) ),
		'm-storage-converter'  => array( __( 'Data Storage Converter', 'computingskill' ), __( 'Decimal units step by 1,000 (KB, MB, GB, as drive makers count). Binary units step by 1,024 and are shown with their IEC names (KiB, MiB, GiB), as Windows counts. 1 byte = 8 bits.', 'computingskill' ) ),
		'm-transfer'           => array( __( 'Download, Upload and File Transfer Time Calculators', 'computingskill' ), __( 'Time = file size in MB ÷ speed in MB/s, with Mbps ÷ 8 = MB/s and 1GB = 1,000MB. Download and upload results are the theoretical best case; real transfers usually run 10–20% slower. The File Transfer Time Calculator uses each interface’s usable data rate after line encoding (USB 2.0 53MB/s, USB 3.2 Gen 1 500MB/s, Gen 2 1,212MB/s, Gen 2x2 2,424MB/s, SATA III 600MB/s, PCIe 3.0 x4 3,938MB/s, PCIe 4.0 x4 7,877MB/s, 1/2.5/10 Gigabit Ethernet 125/312.5/1,250MB/s) times a real-world factor of 100%, 80% or 50%.', 'computingskill' ) ),
		'm-mbps'               => array( __( 'Mbps and MB/s Converters', 'computingskill' ), __( 'MB/s = Mbps ÷ 8 and Mbps = MB/s × 8, because a byte is 8 bits.', 'computingskill' ) ),
		'm-bandwidth'          => array( __( 'Bandwidth Calculator', 'computingskill' ), __( 'Required Mbps = data in megabits (1MB = 8 megabits, 1GB = 8,000) ÷ time in seconds; MB/s is that ÷ 8.', 'computingskill' ) ),
		'm-data-usage'         => array( __( 'Internet Data Usage Calculator', 'computingskill' ), __( 'Daily GB = streaming hours × 1 (SD), 3 (HD) or 7 (4K) GB per hour, Netflix’s published rates, + browsing hours × 0.06 + online gaming hours × 0.1 + video call hours × 1.0 (a 720p call). Monthly = daily × 30.', 'computingskill' ) ),
		'm-density'            => array( __( 'PPI and DPI Calculators', 'computingskill' ), __( 'PPI = √(width² + height²) in pixels ÷ the diagonal in inches, labelled standard under 100, sharp 100–149, high density 150–299 and very high 300 or more. Print DPI = image width in pixels ÷ print width in inches; 300 DPI or more is the usual mark for photo-quality prints.', 'computingskill' ) ),
		'm-screen-size'        => array( __( 'Monitor Size Calculator', 'computingskill' ), __( 'Width = diagonal × ratio width ÷ √(ratio width² + ratio height²), and height the same with the ratio height; × 2.54 gives centimetres.', 'computingskill' ) ),
		'm-aspect'             => array( __( 'Aspect Ratio and Resolution Calculators', 'computingskill' ), __( 'Width and height are divided by their greatest common divisor for the exact ratio, then the closest common ratio (1:1, 5:4, 4:3, 3:2, 16:10, 16:9, 21:9, 32:9) is named when it is within 3%, marked ≈ when it is not exact. The Resolution Calculator adds megapixels (width × height ÷ 1,000,000) and the standard name of common sizes.', 'computingskill' ) ),
		'm-refresh-rate'       => array( __( 'Refresh Rate Calculator', 'computingskill' ), __( 'Frame time in milliseconds = 1,000 ÷ refresh rate in Hz, and Hz = 1,000 ÷ frame time.', 'computingskill' ) ),
	);
}

/**
 * Anchors on the existing formula headings, and the new sections before
 * "What These Formulas Deliberately Don't Account For". Runs after the
 * D3 text swaps (priority 8) and before shortcodes.
 */
function cs_aeo_methodology( $content ) {
	if ( ! is_singular( 'page' ) || 'methodology' !== get_post_field( 'post_name' ) || false !== strpos( $content, 'id="m-cpu-vs-gpu"' ) ) {
		return $content;
	}
	$ids = array(
		'PC Bottleneck Calculator'  => 'm-bottleneck',
		'FPS Calculator'            => 'm-fps',
		'PSU Calculator'            => 'm-psu',
		'RAM Calculator'            => 'm-ram',
		'PC Power Consumption'      => 'm-power-consumption',
	);
	foreach ( $ids as $text => $id ) {
		$content = preg_replace( '#<h3(?![^>]*\bid=)([^>]*)>(\s*' . preg_quote( $text, '#' ) . ')#u', '<h3 id="' . $id . '"$1>$2', $content, 1 );
	}

	$html = '';
	foreach ( cs_aeo_methodology_sections() as $id => $s ) {
		$html .= '<h3 id="' . esc_attr( $id ) . '">' . esc_html( $s[0] ) . '</h3><p>' . esc_html( $s[1] ) . '</p>';
	}
	$count = 0;
	$content = preg_replace( '#(<h2[^>]*>\s*What These Formulas)#u', $html . '$1', $content, 1, $count );
	if ( ! $count ) {
		$content .= $html;
	}
	return $content;
}
add_filter( 'the_content', 'cs_aeo_methodology', 9 );

/* ------------------------------------------------------------------ */
/* 3.5 Quick answers                                                   */
/* ------------------------------------------------------------------ */

/**
 * Pages that had no quick answer (crawl, 18 Oct 2026). Legal pages and
 * Contact are left out: a policy has no single answer to give.
 */
function cs_aeo_quick_answers() {
	$n_calc = 0;
	$groups = cs_content_index_groups();
	foreach ( $groups as $key => $g ) {
		if ( 'calculators' === $key ) {
			foreach ( $g['items'] as $item ) {
				$n_calc += 'bottleneck' !== $item['slug'] ? 1 : 0;
			}
		}
	}
	$combos = function_exists( 'cs_bn_published_pairs' ) ? count( cs_bn_published_pairs() ) : 0;
	return array(
		'how-much-ram-do-i-need-for-gaming-and-streaming' => __( 'For gaming and streaming on the same PC, 32GB of RAM is the sweet spot for most setups. 16GB can run both but leaves almost no margin once the game, OBS or Streamlabs, Discord and a browser are open together, and 64GB only pays off for heavier work alongside the stream, such as 4K capture or editing while live.', 'computingskill' ),
		'methodology'    => __( 'Every result on ComputingSkill is an estimate built from manufacturers’ published specs and documented formulas, not from our own benchmarks. CPU and GPU scores are a relative gaming index positioned from public review averages (RTX 4090 = 100 for GPUs), and each calculator’s formula is listed below.', 'computingskill' ),
		'about-us'       => __( 'ComputingSkill is a free set of PC hardware calculators, tools and guides run by Ali Ahmad. The calculators turn published specs and documented formulas into estimates; we do not run a hardware lab or our own benchmarks, and the Methodology page shows how each number is produced.', 'computingskill' ),
		'calculators'    => sprintf( __( 'ComputingSkill has %d free PC hardware calculators, covering bottlenecks, FPS, PSU wattage, power draw and electricity cost, cooling, RAM, VRAM, build cost, upgrades and part compatibility. Start with the PC Bottleneck Calculator to see whether your CPU or GPU limits your frame rate.', 'computingskill' ), $n_calc ),
		'bottleneck'     => sprintf( __( 'This page lists %d CPU and GPU pairings with their estimated bottleneck at 1080p, 1440p and 4K for a typical AAA game at high settings. A CPU bottleneck %% is the share of the GPU left idle; GPU-limited is normal for gaming. For a pair that isn’t listed, use the PC Bottleneck Calculator.', 'computingskill' ), $combos ),
		'computer-tools' => __( 'Computer Tools are ComputingSkill’s everyday utilities, in three groups: storage (drive space, SSD lifespan and TBW, unit conversion), internet (download, upload and transfer time, bandwidth, data usage, Mbps and MB/s) and display (PPI, DPI, monitor size, aspect ratio, resolution, refresh rate).', 'computingskill' ),
		'storage-tools'  => __( 'Storage tools convert storage units (decimal and binary), estimate how much space files need and how long an SSD lasts from its TBW rating, and convert between TBW and DWPD.', 'computingskill' ),
		'internet-tools' => __( 'Internet tools estimate download, upload and file transfer times, the bandwidth you need, and your monthly data use, and convert between Mbps and MB/s. The key rule: divide an internet speed in Mbps by 8 to get MB/s.', 'computingskill' ),
		'display-tools'  => __( 'Display tools calculate a screen’s pixel density (PPI), print resolution (DPI), a monitor’s real width and height, aspect ratio and resolution names, and the frame time for a refresh rate.', 'computingskill' ),
		'hardware'       => __( 'The Hardware section has one plain-English page per PC component (CPU, GPU, RAM, VRAM, SSD, HDD, PSU, motherboard, CPU cooler and case), explaining what each part does and which specs matter before you buy. Ranked lists are on the GPU and CPU tier lists.', 'computingskill' ),
		'guides'         => __( 'The guides answer the bigger PC questions step by step: how much RAM and VRAM you need, how to choose a gaming GPU and a PSU, how to upgrade a gaming PC, how to improve FPS, and what CPU and GPU bottlenecks really are.', 'computingskill' ),
		'comparisons'    => __( 'The comparisons put two options side by side and recommend one by use case: SSD vs HDD, NVMe vs SATA, DDR4 vs DDR5 and 1080p vs 1440p vs 4K, plus ranked GPU and CPU tier lists.', 'computingskill' ),
		'home'           => __( 'ComputingSkill is a free set of PC hardware calculators: check whether your CPU or GPU limits your frame rate at 1080p, 1440p and 4K, estimate FPS, PSU wattage, RAM and VRAM needs, and compare parts on our GPU and CPU tier lists. Results are estimates from manufacturer specs and documented formulas, not lab benchmarks.', 'computingskill' ),
		'games'          => __( 'Pick a game to see its publisher’s official minimum and recommended CPU, GPU, RAM and storage, then check your own CPU and GPU against it in the FPS Calculator. The FPS figure is a general estimate for that type of game, not a benchmark of the game itself.', 'computingskill' ),
		'blog'           => __( 'The ComputingSkill blog collects our articles, guides and comparisons on building, upgrading and choosing PC parts. Calculators, tools and part explainers have their own hubs, linked above.', 'computingskill' ),
	);
}

function cs_aeo_quick_answer_box( $content ) {
	if ( ! is_singular( 'page' ) || ! in_the_loop() || ! is_main_query() || false !== strpos( $content, 'cs-quick-answer' ) ) {
		return $content;
	}
	$box = cs_aeo_quick_answer_html( get_post_field( 'post_name' ) );
	return $box ? $box . "\n" . $content : $content;
}

function cs_aeo_quick_answer_html( $slug ) {
	$answers = cs_aeo_quick_answers();
	if ( ! isset( $answers[ $slug ] ) ) {
		return '';
	}
	return '<div class="cs-quick-answer"><p class="cs-quick-answer-label">' . esc_html__( 'Quick answer', 'computingskill' ) . '</p><div class="cs-quick-answer-body"><p>' . esc_html( $answers[ $slug ] ) . '</p></div></div>';
}
add_filter( 'the_content', 'cs_aeo_quick_answer_box', 13 );

/* ------------------------------------------------------------------ */
/* 3.6 Byline on /, /blog/ and /games/, and the feed author            */
/* ------------------------------------------------------------------ */

/**
 * "By Ali Ahmad · Last updated {date}" for pages that list other content:
 * the date is the newest change among what they list.
 */
function cs_hub_meta( $post_types = array( 'page', 'post' ) ) {
	if ( ! function_exists( 'cs_site_author' ) ) {
		return;
	}
	$latest = get_posts( array(
		'post_type'      => $post_types,
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'orderby'        => 'modified',
		'order'          => 'DESC',
		'no_found_rows'  => true,
	) );
	if ( ! $latest ) {
		return;
	}
	$author = cs_site_author();
	$p      = $latest[0];
	?>
	<p class="cs-article-meta cs-hub-meta">
		<?php esc_html_e( 'By', 'computingskill' ); ?>
		<a href="<?php echo esc_url( $author['url'] ); ?>" rel="author"><?php echo esc_html( $author['name'] ); ?></a>
		<span aria-hidden="true">·</span>
		<?php esc_html_e( 'Last updated', 'computingskill' ); ?>
		<time datetime="<?php echo esc_attr( get_post_modified_time( 'c', false, $p ) ); ?>"><?php echo esc_html( get_post_modified_time( get_option( 'date_format' ), false, $p, true ) ); ?></time>
	</p>
	<?php
}

/**
 * The RSS feed showed the WordPress account's display name ("Computing
 * Skill"); every post is written by Ali Ahmad (inc/author.php).
 */
function cs_feed_author( $name ) {
	if ( is_feed() && function_exists( 'cs_site_author' ) ) {
		return cs_site_author()['name'];
	}
	return $name;
}
add_filter( 'the_author', 'cs_feed_author' );

/* ------------------------------------------------------------------ */
/* 3.1 llms.txt                                                        */
/* ------------------------------------------------------------------ */

function cs_llms_line( $title, $url, $desc = '' ) {
	$title = wp_strip_all_tags( html_entity_decode( $title, ENT_QUOTES, 'UTF-8' ) );
	$desc  = wp_strip_all_tags( html_entity_decode( $desc, ENT_QUOTES, 'UTF-8' ) );
	return '- [' . $title . '](' . $url . ')' . ( '' !== $desc ? ': ' . $desc : '' ) . "\n";
}

function cs_llms_page_url( $slug ) {
	if ( function_exists( 'cs_get_redirect_map' ) && isset( cs_get_redirect_map()[ $slug ] ) ) {
		return '';
	}
	$page = get_page_by_path( $slug );
	return $page && 'publish' === $page->post_status ? get_permalink( $page ) : '';
}

/**
 * llms.txt (llmstxt.org format) built from the same data the site uses:
 * the content index (hubs and every tool, guide and comparison), the tier
 * lists, every published post, game page and combo page.
 */
function cs_llms_txt() {
	$author = function_exists( 'cs_site_author' ) ? cs_site_author()['name'] : 'Ali Ahmad';
	$out    = "# Computing Skill\n\n";
	$out   .= '> ' . __( 'Free PC hardware calculators, tier lists and guides: CPU/GPU bottlenecks, FPS, PSU wattage, VRAM and RAM needs, power draw, plus storage, internet and display tools. Results are estimates from manufacturer specs and documented formulas, not lab benchmarks.', 'computingskill' ) . "\n\n";
	$out   .= sprintf( __( 'Computing Skill is written by %1$s. CPU and GPU scores are the site’s own relative gaming index (GPUs: RTX 4090 = 100; CPUs: the frame rate a CPU can prepare in games), positioned from public review averages. A CPU bottleneck percentage means the share of the GPU left idle (under 6%% balanced, 6–14%% mild, 15–29%% noticeable, 30%%+ severe); GPU-limited is normal for gaming. Every formula is on the Methodology page. Last generated %2$s.', 'computingskill' ), $author, gmdate( 'Y-m-d' ) ) . "\n\n";

	$out .= "## Start here\n\n";
	foreach ( array(
		''                        => array( __( 'Computing Skill home', 'computingskill' ), __( 'all calculators and tools', 'computingskill' ) ),
		'methodology'             => array( __( 'Methodology', 'computingskill' ), __( 'the formula behind every calculator and what the hardware scores mean', 'computingskill' ) ),
		'gpu-tier-list'           => array( __( 'GPU Tier List', 'computingskill' ), __( 'every graphics card ranked by gaming score, with FPS estimates at 1080p, 1440p and 4K', 'computingskill' ) ),
		'cpu-tier-list'           => array( __( 'CPU Tier List', 'computingskill' ), __( 'every processor ranked by gaming score, with FPS ceilings and the fastest GPU each drives at 1440p', 'computingskill' ) ),
		'pc-bottleneck-calculator' => array( __( 'PC Bottleneck Calculator', 'computingskill' ), __( 'main tool: which part limits a CPU + GPU pair at 1080p, 1440p and 4K', 'computingskill' ) ),
		'about-us'                => array( __( 'About Us', 'computingskill' ), __( 'who runs the site and how content is sourced', 'computingskill' ) ),
		'editorial-policy'        => array( __( 'Editorial Policy', 'computingskill' ), __( 'how pages are written, checked and corrected', 'computingskill' ) ),
	) as $slug => $l ) {
		$url = '' === $slug ? home_url( '/' ) : cs_llms_page_url( $slug );
		if ( $url ) {
			$out .= cs_llms_line( $l[0], $url, $l[1] );
		}
	}

	foreach ( cs_content_index_groups() as $key => $group ) {
		$out .= "\n## " . wp_strip_all_tags( $group['label'] ) . "\n\n";
		$hub  = cs_llms_page_url( $key );
		if ( $hub ) {
			$out .= cs_llms_line( sprintf( __( '%s hub', 'computingskill' ), $group['label'] ), $hub, $group['desc'] );
		}
		foreach ( $group['items'] as $item ) {
			$url = cs_llms_page_url( $item['slug'] );
			if ( $url ) {
				$out .= cs_llms_line( $item['title'], $url, $item['desc'] );
			}
		}
	}

	$posts = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'date', 'order' => 'DESC' ) );
	$blog  = cs_llms_page_url( 'blog' );
	if ( $posts || $blog ) {
		$out .= "\n## " . __( 'Articles', 'computingskill' ) . "\n\n";
		if ( $blog ) {
			$out .= cs_llms_line( __( 'Blog', 'computingskill' ), $blog, __( 'latest articles, guides and comparisons', 'computingskill' ) );
		}
		foreach ( $posts as $p ) {
			$out .= cs_llms_line( get_the_title( $p ), get_permalink( $p ), get_the_excerpt( $p ) );
		}
	}

	$games = get_posts( array( 'post_type' => 'cs_game', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
	if ( $games ) {
		$out .= "\n## " . __( 'Game system requirements', 'computingskill' ) . "\n\n";
		$archive = get_post_type_archive_link( 'cs_game' );
		if ( $archive ) {
			$out .= cs_llms_line( __( 'Can I Run This Game? (all games)', 'computingskill' ), $archive, __( 'minimum and recommended PC specs from each publisher', 'computingskill' ) );
		}
		foreach ( $games as $p ) {
			$out .= cs_llms_line( get_the_title( $p ), get_permalink( $p ) );
		}
	}

	$pairs = function_exists( 'cs_bn_published_pairs' ) ? cs_bn_published_pairs() : array();
	if ( $pairs ) {
		$cpus = cs_get_cpu_data();
		$gpus = cs_get_gpu_data();
		$out .= "\n## " . __( 'CPU + GPU bottleneck comparisons', 'computingskill' ) . "\n\n";
		$out .= __( 'Each page shows the estimate for one pair at 1080p, 1440p and 4K (typical AAA game, high settings).', 'computingskill' ) . "\n\n";
		$rows = array();
		foreach ( $pairs as $key => $url ) {
			list( $c, $g ) = explode( '|', $key );
			if ( ! isset( $cpus[ $c ], $gpus[ $g ] ) ) {
				continue;
			}
			$r      = cs_calculate_bottleneck( $cpus[ $c ], $gpus[ $g ] );
			$cells  = array();
			foreach ( cs_bn_res_short() as $res => $label ) {
				$cells[] = $label . ' ' . cs_il_lc_safe( cs_bn_cell( $r[ $res ] ) );
			}
			$rows[ cs_bn_short_name( $cpus[ $c ]['name'] ) . ' + ' . cs_bn_short_name( $gpus[ $g ]['name'] ) ] = cs_llms_line( cs_bn_short_name( $cpus[ $c ]['name'] ) . ' + ' . cs_bn_short_name( $gpus[ $g ]['name'] ), $url, implode( ', ', $cells ) );
		}
		ksort( $rows, SORT_NATURAL );
		$out .= implode( '', $rows );
	}

	$out .= "\n## " . __( 'Optional', 'computingskill' ) . "\n\n";
	foreach ( array( 'privacy-policy', 'terms-of-use', 'disclaimer', 'contact' ) as $slug ) {
		$url = cs_llms_page_url( $slug );
		if ( $url ) {
			$out .= cs_llms_line( get_the_title( get_page_by_path( $slug ) ), $url );
		}
	}
	return $out;
}

function cs_il_lc_safe( $text ) {
	return function_exists( 'cs_il_lc' ) ? cs_il_lc( $text ) : $text;
}

/**
 * Writes llms.txt to the site root (it replaces the static file uploaded
 * in Phase 6). Returns true when the file holds the new text.
 */
function cs_llms_write() {
	$file = ABSPATH . 'llms.txt';
	$text = cs_llms_txt();
	if ( file_exists( $file ) && file_get_contents( $file ) === $text ) { // phpcs:ignore WordPress.WP.AlternativeFunctions
		return true;
	}
	if ( file_exists( $file ) && ! file_exists( ABSPATH . 'llms.txt.phase6.bak' ) ) {
		@copy( $file, ABSPATH . 'llms.txt.phase6.bak' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	}
	$ok = false !== @file_put_contents( $file, $text ); // phpcs:ignore WordPress.WP.AlternativeFunctions, WordPress.PHP.NoSilencedErrors
	update_option( 'cs_llms_written', array( 'ok' => $ok, 'time' => time(), 'bytes' => strlen( $text ) ), false );
	return $ok;
}

/**
 * Fallback: if no llms.txt file is on disk (or it could not be written),
 * WordPress answers /llms.txt itself.
 */
function cs_llms_serve() {
	$path = wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '', PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	if ( '/llms.txt' !== $path ) {
		return;
	}
	status_header( 200 );
	header( 'Content-Type: text/plain; charset=utf-8' );
	header( 'X-Robots-Tag: noindex' );
	echo cs_llms_txt(); // phpcs:ignore WordPress.Security.EscapeOutput
	exit;
}
add_action( 'parse_request', 'cs_llms_serve', 0 );

/**
 * Refresh llms.txt once a day so new combo, game and blog pages appear.
 */
function cs_llms_daily() {
	$last = (array) get_option( 'cs_llms_written', array() );
	if ( empty( $last['time'] ) || time() - (int) $last['time'] > DAY_IN_SECONDS ) {
		cs_llms_write();
	}
}
add_action( 'shutdown', function () {
	if ( ! is_admin() && ! wp_doing_ajax() && get_option( 'cs_aeo_rev' ) === CS_AEO_REV ) {
		cs_llms_daily();
	}
} );

/* ------------------------------------------------------------------ */
/* One-time job                                                        */
/* ------------------------------------------------------------------ */

/**
 * When CS_AEO_REV changes: write llms.txt, clear Rank Math's sitemap cache
 * (new tier list pages) and purge the page cache.
 */
function cs_aeo_rev_run() {
	if ( wp_installing() || get_option( 'cs_aeo_rev' ) === CS_AEO_REV ) {
		return;
	}
	// The tier list pages must exist before llms.txt lists them.
	if ( count( (array) get_option( 'cs_tier_pages_created', array() ) ) < count( cs_tier_pages() ) ) {
		return;
	}
	if ( ! add_option( 'cs_aeo_rev_lock', time(), '', 'no' ) ) {
		if ( time() - (int) get_option( 'cs_aeo_rev_lock' ) > 300 ) {
			delete_option( 'cs_aeo_rev_lock' );
		}
		return;
	}
	cs_llms_write();
	if ( class_exists( '\RankMath\Sitemap\Cache' ) ) {
		\RankMath\Sitemap\Cache::invalidate_storage();
	}
	do_action( 'litespeed_purge_all' );
	update_option( 'cs_aeo_rev', CS_AEO_REV, false );
	delete_option( 'cs_aeo_rev_lock' );
}
add_action( 'init', 'cs_aeo_rev_run', 40 );
