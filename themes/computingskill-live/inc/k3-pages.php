<?php
/**
 * K3 (GSC keyword gap, 20 Oct 2026): nine existing tools with search
 * volume but no Search Console presence get the answer their searches
 * ask for right under the calculator, one worked table and a few FAQ:
 *
 * - /aspect-ratio-calculator/: 16:9 and 21:9 resolutions, how to work out 16:9.
 * - /download-time-calculator/: how long 25-150GB takes at 50 Mbps-1 Gbps.
 * - /monitor-size-calculator/: screen sizes, best size for 1080p/1440p/4K.
 * - /ppi-calculator/: common monitors and a laptop-size PPI grid.
 * - /dpi-calculator/: largest print at 300 DPI, print DPI vs mouse DPI.
 * - /cpu-power-consumption-calculator/: how many watts a CPU uses.
 * - /pc-power-consumption-calculator/: gaming PC watts and running cost.
 * - /pc-compatibility-checker/: CPU and GPU compatibility, what must match.
 * - /cpu-bottleneck-explained/: "How to Fix a CPU Bottleneck" steps.
 *
 * Titles and descriptions are in inc/seo-compat.php, the bottleneck H2 and
 * the monitor worked-example fix in inc/calculator-pages.php. Every number
 * is computed from the stated formula or from the same data and rules the
 * calculators and the reference tables (inc/aeo.php) use. Tables and links
 * reuse the K1 helpers (inc/k1-pages.php).
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------ */
/* Data                                                                */
/* ------------------------------------------------------------------ */

/** Best-case download time in seconds: GB × 8,000 megabits ÷ Mbps. */
function cs_k3_dl_seconds( $gb, $mbps ) {
	return $gb * 8000 / $mbps;
}

/** Width and height in inches of a diagonal at ratio a:b. */
function cs_k3_screen( $diag, $a, $b ) {
	$k = sqrt( $a * $a + $b * $b );
	return array( $diag * $a / $k, $diag * $b / $k );
}

function cs_k3_ppi( $diag, $w, $h ) {
	return sqrt( $w * $w + $h * $h ) / $diag;
}

/** "23.5 × 13.2 in (59.8 × 33.6 cm)" for a screen. */
function cs_k3_size_label( $diag, $a, $b ) {
	list( $w, $h ) = cs_k3_screen( $diag, $a, $b );
	return cs_aeo_num( $w, 1 ) . ' × ' . cs_aeo_num( $h, 1 ) . ' in (' . cs_aeo_num( $w * 2.54, 1 ) . ' × ' . cs_aeo_num( $h * 2.54, 1 ) . ' cm)';
}

/** CPU draw from the hardware database, same rule as the calculator. */
function cs_k3_cpu( $slug ) {
	$cpus = function_exists( 'cs_get_cpu_data' ) ? cs_get_cpu_data() : array();
	if ( ! isset( $cpus[ $slug ] ) ) {
		return null;
	}
	$p         = cs_aeo_cpu_power( $cpus[ $slug ] );
	$p['idle'] = $cpus[ $slug ]['tdp_w'] * 0.15;
	$p['name'] = cs_aeo_short( $cpus[ $slug ] );
	return $p;
}

/**
 * Lowest and highest wall draw (gaming, idle) and monthly cost in the
 * PC Power Consumption Calculator's example builds (inc/aeo.php).
 */
function cs_k3_build_range() {
	$data = cs_aeo_reference_data( 'power-consumption' );
	if ( empty( $data['rows'] ) ) {
		return null;
	}
	$rows  = $data['rows'];
	$first = $rows[0];
	$last  = $rows[ count( $rows ) - 1 ];
	$clean = function ( $cell ) {
		return html_entity_decode( wp_strip_all_tags( $cell ), ENT_QUOTES, 'UTF-8' );
	};
	return array(
		'low_name'  => $clean( $first[0] ),
		'low_load'  => (int) $first[1],
		'low_idle'  => (int) $first[2],
		'low_cost'  => $clean( $first[3] ),
		'high_name' => $clean( $last[0] ),
		'high_load' => (int) $last[1],
		'high_idle' => (int) $last[2],
		'high_cost' => $clean( $last[3] ),
	);
}

/** Running cost of a wall draw in watts for some hours at a $/kWh rate. */
function cs_k3_cost( $watts, $hours, $rate ) {
	return $watts / 1000 * $hours * $rate;
}

/* ------------------------------------------------------------------ */
/* Sections                                                            */
/* ------------------------------------------------------------------ */

/**
 * Sections per page, as in K1: 'lead' before the page's first H2 (right
 * under the calculator and its reference table), 'more' before "Related
 * Calculators & Guides".
 */
function cs_k3_sections( $slug ) {
	if ( ! function_exists( 'cs_k1_table' ) || ! function_exists( 'cs_aeo_num' ) ) {
		return array();
	}
	switch ( $slug ) {
		case 'aspect-ratio-calculator':
			$sets = array(
				'16:9' => array( array( 1280, 720, 'HD (720p)' ), array( 1600, 900, 'HD+' ), array( 1920, 1080, 'Full HD (1080p)' ), array( 2560, 1440, 'QHD (1440p)' ), array( 3200, 1800, 'QHD+' ), array( 3840, 2160, '4K UHD' ), array( 5120, 2880, '5K' ), array( 7680, 4320, '8K UHD' ) ),
				'21:9' => array( array( 2560, 1080, 'UW-FHD' ), array( 3440, 1440, 'UWQHD' ), array( 3840, 1600, 'UW-QHD+' ), array( 5120, 2160, '5K2K' ) ),
			);
			$rows = array();
			foreach ( $sets as $label => $list ) {
				foreach ( $list as $r ) {
					$ratio  = cs_aeo_ratio( $r[0], $r[1] );
					$rows[] = array( $r[0] . ' × ' . $r[1], $r[2], $label, $ratio['simple'], cs_aeo_num( $r[0] / $r[1], 2 ) . ':1' );
				}
			}
			$lead  = '<h2>' . esc_html__( '16:9 and 21:9 Resolutions', 'computingskill' ) . '</h2>';
			$lead .= '<p>' . esc_html__( 'To work out a 16:9 size, divide the width by 16 and multiply by 9 for the height (1920 wide is 1080 tall), or divide the height by 9 and multiply by 16 for the width (1440 tall is 2560 wide). Every common 16:9 resolution below simplifies exactly to 16:9. Ultrawide “21:9” monitors do not: their real ratios are a little wider, from about 2.37:1 to 2.40:1 against 2.33:1 for a true 21:9, so makers round them to the nearest familiar name.', 'computingskill' ) . '</p>';
			$lead .= cs_k1_table(
				__( 'Common 16:9 and 21:9 resolutions', 'computingskill' ),
				array( __( 'Resolution', 'computingskill' ), __( 'Name', 'computingskill' ), __( 'Sold as', 'computingskill' ), __( 'Exact ratio', 'computingskill' ), __( 'Width ÷ height', 'computingskill' ) ),
				$rows
			);
			$lead .= '<p class="cs-calc-ref-note">' . esc_html__( 'Exact ratio = width and height divided by their greatest common divisor, as in the calculator. A 16:9 screen is 1.78 times as wide as it is tall, a true 21:9 screen 2.33 times.', 'computingskill' ) . '</p>';
			return array( 'lead' => $lead );

		case 'download-time-calculator':
			$speeds = array( 50, 100, 200, 500, 1000 );
			$rows   = array();
			foreach ( array( 25, 50, 100, 150 ) as $gb ) {
				$row = array( $gb . 'GB' );
				foreach ( $speeds as $mbps ) {
					$row[] = cs_aeo_duration( cs_k3_dl_seconds( $gb, $mbps ) );
				}
				$rows[] = $row;
			}
			$lead  = '<h2>' . esc_html__( 'How Long Does It Take to Download 50GB, 100GB or 150GB?', 'computingskill' ) . '</h2>';
			$lead .= '<p>' . esc_html( sprintf(
				/* translators: 1: 100GB at 100 Mbps, 2: same +10%, 3: same +20%, 4: 100GB at 1 Gbps */
				__( 'On a 100 Mbps connection, a 100GB game takes at least %1$s, and usually %2$s to %3$s once real-world overhead is added. On 1 Gbps the same download takes at least %4$s, if the store’s servers and your Wi-Fi keep up. Big game installs and updates range from a few GB to 150GB or more, so check the size on the store page and find it in the table below.', 'computingskill' ),
				cs_aeo_duration( cs_k3_dl_seconds( 100, 100 ) ),
				cs_aeo_duration( cs_k3_dl_seconds( 100, 100 ) * 1.1 ),
				cs_aeo_duration( cs_k3_dl_seconds( 100, 100 ) * 1.2 ),
				cs_aeo_duration( cs_k3_dl_seconds( 100, 1000 ) )
			) ) . '</p>';
			$lead .= cs_k1_table(
				__( 'Download time for game-sized files (best case)', 'computingskill' ),
				array( __( 'Download size', 'computingskill' ), '50 Mbps', '100 Mbps', '200 Mbps', '500 Mbps', '1 Gbps' ),
				$rows
			);
			$lead .= '<p class="cs-calc-ref-note">' . esc_html__( 'Time = size in GB × 8,000 ÷ speed in Mbps (1GB = 1,000MB = 8,000 megabits). Expect real downloads to take 10–20% longer. Game launchers usually show the download rate in MB/s: multiply it by 8 to compare it with your plan’s Mbps.', 'computingskill' ) . '</p>';
			return array( 'lead' => $lead );

		case 'monitor-size-calculator':
			$lead  = '<h2>' . esc_html__( 'How Big Is a 24, 27, 32 or 34-Inch Monitor?', 'computingskill' ) . '</h2>';
			$lead .= '<p>' . esc_html( sprintf(
				/* translators: 1-4: width × height of each size */
				__( 'The size on the box is the screen’s diagonal. A 24-inch 16:9 monitor’s screen is %1$s, a 27-inch %2$s and a 32-inch %3$s. A 34-inch 21:9 ultrawide is %4$s: much wider than a 27-inch, but about the same height. Add the bezels and stand to these panel sizes when you measure your desk.', 'computingskill' ),
				cs_k3_size_label( 24, 16, 9 ),
				cs_k3_size_label( 27, 16, 9 ),
				cs_k3_size_label( 32, 16, 9 ),
				cs_k3_size_label( 34, 21, 9 )
			) ) . '</p>';

			$sizes = array( 24, 27, 32 );
			$res   = array( '1080p' => array( 1920, 1080 ), '1440p' => array( 2560, 1440 ), '4K' => array( 3840, 2160 ) );
			$rows  = array();
			foreach ( $sizes as $d ) {
				$row = array( $d . '″ 16:9' );
				foreach ( $res as $r ) {
					$row[] = cs_aeo_num( cs_k3_ppi( $d, $r[0], $r[1] ), 1 ) . ' PPI';
				}
				$rows[] = $row;
			}
			$more  = '<h2>' . esc_html__( 'Best Monitor Size for 1080p, 1440p and 4K', 'computingskill' ) . '</h2>';
			$more .= '<p>' . esc_html( sprintf(
				/* translators: 1: 24in 1080p PPI, 2: 27in 1440p PPI, 3: 27in 1080p PPI, 4: 32in 1440p PPI, 5: 27in 4K PPI, 6: 32in 4K PPI */
				__( 'Windows sizes text and icons for about 96 pixels per inch at 100%% scaling, so a screen near 90–110 PPI shows text at a comfortable size without scaling. That makes 24 inches the natural size for 1080p (%1$s PPI) and 27 inches the natural size for 1440p (%2$s PPI). At 27 inches, 1080p drops to %3$s PPI and text looks soft from a normal desk distance. A 32-inch 1440p screen (%4$s PPI) is as sharp as a 24-inch 1080p one, which suits sitting a little farther back. 4K is sharp at 27 inches (%5$s PPI) and 32 inches (%6$s PPI); use 125–150%% scaling in Windows so text isn’t tiny.', 'computingskill' ),
				cs_aeo_num( cs_k3_ppi( 24, 1920, 1080 ), 1 ),
				cs_aeo_num( cs_k3_ppi( 27, 2560, 1440 ), 1 ),
				cs_aeo_num( cs_k3_ppi( 27, 1920, 1080 ), 1 ),
				cs_aeo_num( cs_k3_ppi( 32, 2560, 1440 ), 1 ),
				cs_aeo_num( cs_k3_ppi( 27, 3840, 2160 ), 1 ),
				cs_aeo_num( cs_k3_ppi( 32, 3840, 2160 ), 1 )
			) ) . '</p>';
			$more .= cs_k1_table(
				__( 'Pixel density by monitor size and resolution', 'computingskill' ),
				array( __( 'Size', 'computingskill' ), '1080p', '1440p', '4K' ),
				$rows
			);
			$more .= '<p>' . sprintf(
				/* translators: %s: link to the PPI calculator */
				esc_html__( 'Check any other size and resolution in the %s.', 'computingskill' ),
				cs_k1_link( 'ppi-calculator', __( 'PPI Calculator', 'computingskill' ) )
			) . '</p>';
			return array( 'lead' => $lead, 'more' => $more );

		case 'ppi-calculator':
			$lead  = '<h2>' . esc_html__( 'Pixel Density of Common Monitors', 'computingskill' ) . '</h2>';
			$lead .= '<p>' . esc_html( sprintf(
				/* translators: 1: 27in 1440p, 2: 24in 1080p, 3: 27in 4K, 4: 27in 1080p, 5: 15.6in 1080p */
				__( 'A 27-inch 1440p monitor has %1$s pixels per inch, about the same as a 34-inch 3440 × 1440 ultrawide. A 24-inch 1080p monitor has %2$s PPI and a 27-inch 4K monitor %3$s PPI, while 1080p stretched to 27 inches drops to %4$s PPI. Laptops pack the same pixels into smaller screens, so a 15.6-inch 1080p laptop already reaches %5$s PPI.', 'computingskill' ),
				cs_aeo_num( cs_k3_ppi( 27, 2560, 1440 ), 1 ),
				cs_aeo_num( cs_k3_ppi( 24, 1920, 1080 ), 1 ),
				cs_aeo_num( cs_k3_ppi( 27, 3840, 2160 ), 1 ),
				cs_aeo_num( cs_k3_ppi( 27, 1920, 1080 ), 1 ),
				cs_aeo_num( cs_k3_ppi( 15.6, 1920, 1080 ), 1 )
			) ) . '</p>';

			$res  = array( '1920 × 1080' => array( 1920, 1080 ), '1920 × 1200' => array( 1920, 1200 ), '2560 × 1600' => array( 2560, 1600 ), '3840 × 2160' => array( 3840, 2160 ) );
			$rows = array();
			foreach ( array( 13.3, 14, 15.6, 16, 17.3 ) as $d ) {
				$row = array( cs_aeo_num( $d, floor( $d ) == $d ? 0 : 1 ) . '″' );
				foreach ( $res as $r ) {
					$row[] = cs_aeo_num( cs_k3_ppi( $d, $r[0], $r[1] ) );
				}
				$rows[] = $row;
			}
			$more  = '<h2>' . esc_html__( 'Laptop Screen PPI by Size and Resolution', 'computingskill' ) . '</h2>';
			$more .= '<p>' . esc_html__( 'Laptop screens are viewed closer than monitors and come in more resolutions. Find your screen’s diagonal and resolution in this grid:', 'computingskill' ) . '</p>';
			$more .= cs_k1_table(
				__( 'PPI by laptop screen size (rounded)', 'computingskill' ),
				array_merge( array( __( 'Screen size', 'computingskill' ) ), array_keys( $res ) ),
				$rows
			);
			$more .= '<p class="cs-calc-ref-note">' . esc_html__( 'PPI = √(width² + height²) ÷ diagonal in inches. Not every size is sold in every resolution; the grid is the formula applied to each pair.', 'computingskill' ) . '</p>';
			return array( 'lead' => $lead, 'more' => $more );

		case 'dpi-calculator':
			$images = array(
				array( __( 'Full HD frame (2.1MP)', 'computingskill' ), 1920, 1080 ),
				array( __( '4K UHD frame (8.3MP)', 'computingskill' ), 3840, 2160 ),
				array( __( '12MP photo (4:3)', 'computingskill' ), 4000, 3000 ),
				array( __( '24MP photo (3:2)', 'computingskill' ), 6000, 4000 ),
				array( __( '48MP photo (4:3)', 'computingskill' ), 8000, 6000 ),
			);
			$rows = array();
			foreach ( $images as $im ) {
				$row = array( $im[0] . ' ' . cs_aeo_num( $im[1] ) . ' × ' . cs_aeo_num( $im[2] ) );
				foreach ( array( 300, 150 ) as $dpi ) {
					$w     = $im[1] / $dpi;
					$h     = $im[2] / $dpi;
					$row[] = cs_aeo_num( $w, 1 ) . ' × ' . cs_aeo_num( $h, 1 ) . ' in (' . cs_aeo_num( $w * 2.54 ) . ' × ' . cs_aeo_num( $h * 2.54 ) . ' cm)';
				}
				$rows[] = $row;
			}
			$lead  = '<h2>' . esc_html__( 'How Big Can You Print a Photo at 300 DPI?', 'computingskill' ) . '</h2>';
			$lead .= '<p>' . esc_html( sprintf(
				/* translators: 1: 12MP width at 300, 2: height at 300, 3: width at 150, 4: height at 150 */
				__( 'Divide the image’s pixels by 300 to get the largest photo-quality print in inches. A 12MP photo (4000 × 3000 pixels) prints sharp up to %1$s × %2$s inches at 300 DPI, or %3$s × %4$s inches at 150 DPI for a poster seen from farther away.', 'computingskill' ),
				cs_aeo_num( 4000 / 300, 1 ),
				cs_aeo_num( 3000 / 300 ),
				cs_aeo_num( 4000 / 150, 1 ),
				cs_aeo_num( 3000 / 150 )
			) ) . '</p>';
			$lead .= cs_k1_table(
				__( 'Largest print size by image resolution', 'computingskill' ),
				array( __( 'Image', 'computingskill' ), __( 'At 300 DPI (photo quality)', 'computingskill' ), __( 'At 150 DPI (posters)', 'computingskill' ) ),
				$rows
			);

			$more  = '<h2>' . esc_html__( 'Print DPI vs Mouse DPI', 'computingskill' ) . '</h2>';
			$more .= '<p>' . esc_html__( 'Mouse DPI shares the name but measures something else: how many counts a mouse sensor reports for each inch you move it (some makers call it CPI, counts per inch). At 800 DPI, moving the mouse one inch sends 800 counts, and at 1,600 DPI the cursor goes twice as far for the same hand movement. It has nothing to do with print sharpness, and this calculator does not work it out. Gamers compare overall aim sensitivity as eDPI, mouse DPI × in-game sensitivity, because the same DPI feels different in every game.', 'computingskill' ) . '</p>';
			$more .= '<p>' . sprintf(
				/* translators: %s: link to the PPI calculator */
				esc_html__( 'A screen’s “DPI” in Windows display settings is really its pixel density: for that, use the %s.', 'computingskill' ),
				cs_k1_link( 'ppi-calculator', __( 'PPI Calculator', 'computingskill' ) )
			) . '</p>';
			return array( 'lead' => $lead, 'more' => $more );

		case 'cpu-power-consumption-calculator':
			$small = cs_k3_cpu( 'ryzen-5-7600' );
			$x3d   = cs_k3_cpu( 'ryzen-7-7800x3d' );
			$big   = cs_k3_cpu( 'core-i9-14900k' );
			if ( ! $small || ! $x3d || ! $big ) {
				return array();
			}
			$lead  = '<h2>' . esc_html__( 'How Many Watts Does a CPU Use?', 'computingskill' ) . '</h2>';
			$lead .= '<p>' . esc_html( sprintf(
				/* translators: 1: CPU, 2: TDP, 3: idle, 4: gaming, 5: full, 6: CPU, 7: gaming, 8: full, 9: CPU, 10: TDP, 11: idle, 12: gaming, 13: full */
				__( 'It depends on the chip and the work. By the calculator’s estimates, a %1$s (%2$dW TDP) uses about %3$dW at idle, %4$dW while gaming and %5$dW at full load. A %6$s uses about %7$dW in games and %8$dW at full load. A %9$s, also sold as a %10$dW TDP chip, uses about %11$dW at idle, %12$dW in games and up to %13$dW at full load. So the TDP on the box is a cooling figure, not the wattage: high-end unlocked chips can draw about twice their TDP in long all-core work.', 'computingskill' ),
				$small['name'],
				(int) $small['tdp'],
				round( $small['idle'] ),
				round( $small['gaming'] ),
				(int) $small['max'],
				$x3d['name'],
				round( $x3d['gaming'] ),
				(int) $x3d['max'],
				$big['name'],
				(int) $big['tdp'],
				round( $big['idle'] ),
				round( $big['gaming'] ),
				(int) $big['max']
			) ) . '</p>';
			$lead .= '<p>' . sprintf(
				/* translators: 1: link to the PC Power Consumption Calculator, 2: link to the PSU Calculator */
				esc_html__( 'The CPU is one part of the total. For the whole PC’s wattage and electricity cost, use the %1$s; for the power supply size, the %2$s.', 'computingskill' ),
				cs_k1_link( 'pc-power-consumption-calculator', __( 'PC Power Consumption Calculator', 'computingskill' ) ),
				cs_k1_link( 'psu-calculator', __( 'PSU Calculator', 'computingskill' ) )
			) . '</p>';
			return array( 'lead' => $lead );

		case 'pc-power-consumption-calculator':
			$b = cs_k3_build_range();
			if ( ! $b ) {
				return array();
			}
			$lead  = '<h2>' . esc_html__( 'How Many Watts Does a Gaming PC Use?', 'computingskill' ) . '</h2>';
			$lead .= '<p>' . esc_html( sprintf(
				/* translators: 1: low build, 2: its gaming W, 3: high build, 4: its gaming W, 5: low idle, 6: high idle, 7: low cost, 8: high cost */
				__( 'In the example builds above, a gaming PC draws about %2$dW from the wall while gaming with a %1$s, and about %4$dW with a %3$s. At the desktop the same PCs draw only %5$dW to %6$dW. The graphics card is the biggest share under load. With 3 hours of gaming and 3 hours of light use a day at $0.15 per kWh, that is about %7$s to %8$s a month.', 'computingskill' ),
				$b['low_name'],
				$b['low_load'],
				$b['high_name'],
				$b['high_load'],
				$b['low_idle'],
				$b['high_idle'],
				$b['low_cost'],
				$b['high_cost']
			) ) . '</p>';

			$rows = array();
			foreach ( array( 300, 400, 500, 600, 800 ) as $w ) {
				$rows[] = array(
					$w . 'W',
					'$' . cs_aeo_num( cs_k3_cost( $w, 1, 0.15 ), 3 ),
					'$' . cs_aeo_num( cs_k3_cost( $w, 90, 0.15 ), 2 ),
					'$' . cs_aeo_num( cs_k3_cost( $w, 90, 0.30 ), 2 ),
				);
			}
			$more  = '<h2>' . esc_html__( 'How Much Does It Cost to Run a Gaming PC?', 'computingskill' ) . '</h2>';
			$more .= '<p>' . esc_html__( 'Cost = watts ÷ 1,000 × hours × your price per kWh. For gaming time alone, at two common electricity prices:', 'computingskill' ) . '</p>';
			$more .= cs_k1_table(
				__( 'Cost of gaming time by wall draw', 'computingskill' ),
				array( __( 'Draw from the wall', 'computingskill' ), __( 'Per hour at $0.15/kWh', 'computingskill' ), __( 'Per month, 3 h a day, $0.15/kWh', 'computingskill' ), __( 'Per month, 3 h a day, $0.30/kWh', 'computingskill' ) ),
				$rows
			);
			$more .= '<p class="cs-calc-ref-note">' . esc_html__( 'A month here is 30 days (90 hours of gaming). Your bill shows your own price per kWh; enter it in the calculator above.', 'computingskill' ) . '</p>';
			return array( 'lead' => $lead, 'more' => $more );

		case 'pc-compatibility-checker':
			$lead  = '<h2>' . esc_html__( 'Are All CPUs and GPUs Compatible?', 'computingskill' ) . '</h2>';
			$lead .= '<p>' . esc_html__( 'For desktop PCs, yes: any current graphics card works with any current CPU and motherboard that has a PCIe x16 slot. PCIe works across generations, so a PCIe 5.0 card runs in a PCIe 4.0 or 3.0 slot, at that slot’s speed. What decides whether a CPU and GPU suit each other is balance (one holding the other back) and power (the PSU), not a compatibility rule. The parts that must match are the CPU and motherboard socket, the RAM type, and what fits inside the case.', 'computingskill' ) . '</p>';
			$lead .= '<p>' . esc_html__( 'Two catches: a CPU without integrated graphics (Intel “F” models, Ryzen 5000 and older chips without a “G”, and Ryzen “F” models) needs a graphics card to show a picture at all, and some budget cards use only 8 PCIe lanes (the RTX 4060 and RTX 4060 Ti, for example), which can cost a little performance in an older PCIe 3.0 slot.', 'computingskill' ) . '</p>';

			$more  = '<h2>' . esc_html__( 'How to Check PC Part Compatibility', 'computingskill' ) . '</h2>';
			$more .= '<p>' . esc_html__( 'These are the pairs that must match, and where to find each spec. The checker above runs the same checks:', 'computingskill' ) . '</p>';
			$more .= cs_k1_table(
				__( 'What has to match in a PC build', 'computingskill' ),
				array( __( 'Parts', 'computingskill' ), __( 'What must match', 'computingskill' ), __( 'Where to find it', 'computingskill' ) ),
				array(
					array( __( 'CPU + motherboard', 'computingskill' ), __( 'Same socket (AM5, AM4, LGA 1851, LGA 1700), and a BIOS that supports the CPU', 'computingskill' ), __( 'The board maker’s CPU support list for that exact model', 'computingskill' ) ),
					array( __( 'RAM + motherboard', 'computingskill' ), __( 'DDR4 or DDR5, whichever the board takes', 'computingskill' ), __( 'The board’s memory specs', 'computingskill' ) ),
					array( __( 'Motherboard + case', 'computingskill' ), __( 'Board size (ATX, Micro-ATX, Mini-ITX) no larger than the case supports', 'computingskill' ), __( 'The case’s spec sheet', 'computingskill' ) ),
					array( __( 'GPU + case', 'computingskill' ), __( 'Card length (and thickness) within the case’s GPU clearance', 'computingskill' ), __( 'Card length on the GPU maker’s page; clearance on the case’s', 'computingskill' ) ),
					array( __( 'CPU cooler + case', 'computingskill' ), __( 'Air cooler height under the case limit; radiator size for an AIO', 'computingskill' ), __( 'Both spec sheets', 'computingskill' ) ),
					array( __( 'PSU + everything', 'computingskill' ), __( 'Enough wattage with headroom, and the right GPU power cables (8-pin or 16-pin 12V-2×6)', 'computingskill' ), __( 'The GPU maker’s recommended PSU; the PSU’s cable list', 'computingskill' ) ),
					array( __( 'CPU + GPU', 'computingskill' ), __( 'Nothing has to match; check the balance instead', 'computingskill' ), __( 'The PC Bottleneck Calculator', 'computingskill' ) ),
				)
			);
			$more .= '<p>' . sprintf(
				/* translators: %s: link to the PC Bottleneck Calculator */
				esc_html__( 'To see whether your CPU and GPU are a good match for your resolution, use the %s.', 'computingskill' ),
				cs_k1_link( 'pc-bottleneck-calculator', __( 'PC Bottleneck Calculator', 'computingskill' ) )
			) . '</p>';
			return array( 'lead' => $lead, 'more' => $more );

		case 'cpu-bottleneck-explained':
			$more  = '<p>' . esc_html__( 'Step by step, from free fixes to an upgrade:', 'computingskill' ) . '</p><ol>';
			$more .= '<li><strong>' . esc_html__( 'Confirm it first.', 'computingskill' ) . '</strong> ' . esc_html__( 'GPU usage well under 95% while FPS stays the same at a lower resolution means the CPU is the limit. If GPU usage is near 100%, it is a GPU limit and none of the steps below will help.', 'computingskill' ) . '</li>';
			$more .= '<li><strong>' . esc_html__( 'Use the spare GPU power.', 'computingskill' ) . '</strong> ' . esc_html__( 'When the CPU is the limit, a higher resolution, sharper anti-aliasing or higher texture and effects settings cost little or no FPS, because the graphics card was waiting anyway.', 'computingskill' ) . '</li>';
			$more .= '<li><strong>' . esc_html__( 'Lower the CPU-heavy settings.', 'computingskill' ) . '</strong> ' . esc_html__( 'View distance, crowd or NPC density, physics and simulation detail load the CPU; turning them down raises the frame rate ceiling.', 'computingskill' ) . '</li>';
			$more .= '<li><strong>' . esc_html__( 'Check your RAM.', 'computingskill' ) . '</strong> ' . esc_html__( 'Turn on XMP or EXPO in the BIOS so memory runs at its rated speed, and use two sticks so it runs in dual channel.', 'computingskill' ) . '</li>';
			$more .= '<li><strong>' . esc_html__( 'Free up the CPU.', 'computingskill' ) . '</strong> ' . esc_html__( 'Close background apps, overlays and browser tabs, and check that the CPU isn’t throttling from heat (HWiNFO shows clock speeds and temperatures).', 'computingskill' ) . '</li>';
			$more .= '<li><strong>' . esc_html__( 'Cap the frame rate.', 'computingskill' ) . '</strong> ' . esc_html__( 'A cap a little below what the CPU can hold steadily gives smoother frame pacing than an uncapped frame rate that swings up and down.', 'computingskill' ) . '</li>';
			$more .= '<li><strong>' . esc_html__( 'Upgrade the CPU.', 'computingskill' ) . '</strong> ' . esc_html__( 'The real fix. A faster chip on the same socket is the cheapest route, since the motherboard and RAM stay.', 'computingskill' ) . '</li>';
			$more .= '</ol><p>' . sprintf(
				/* translators: 1: link to the DLSS guide, 2: link to the PC Upgrade Calculator, 3: link to the CPU tier list */
				esc_html__( 'Frame generation can raise the frame rate you see even when the CPU is the limit, though it doesn’t make the game respond faster (%1$s). To weigh a CPU upgrade, see the %2$s and the %3$s.', 'computingskill' ),
				cs_k1_link( 'dlss-fsr-frame-generation-explained', __( 'how frame generation works', 'computingskill' ) ),
				cs_k1_link( 'pc-upgrade-calculator', __( 'PC Upgrade Calculator', 'computingskill' ) ),
				cs_k1_link( 'cpu-tier-list', __( 'CPU Tier List', 'computingskill' ) )
			) . '</p>';
			return array( 'more' => $more );
	}
	return array();
}

/**
 * Extra FAQ items (merged into cs_extra_faq_definitions()).
 */
function cs_k3_extra_faqs() {
	if ( ! function_exists( 'cs_aeo_num' ) ) {
		return array();
	}
	$faqs = array(
		// Aspect ratio.
		array(
			'slugs' => array( 'aspect-ratio-calculator' ),
			'q'     => __( 'How do I calculate a 16:9 resolution?', 'computingskill' ),
			'a'     => __( 'Height = width × 9 ÷ 16, and width = height × 16 ÷ 9. For example, 1600 pixels wide gives 1600 × 9 ÷ 16 = 900 pixels tall, so 1600 × 900 is 16:9; 1440 pixels tall gives 1440 × 16 ÷ 9 = 2560 pixels wide.', 'computingskill' ),
		),
		array(
			'slugs' => array( 'aspect-ratio-calculator' ),
			'q'     => __( 'Is 3440×1440 really 21:9?', 'computingskill' ),
			'a'     => sprintf(
				/* translators: 1: 3440x1440 ratio, 2: 21:9 ratio */
				__( 'Not exactly. 3440 × 1440 simplifies to 43:18, or %1$s:1, while a true 21:9 is %2$s:1. It is sold as 21:9 because that is the nearest familiar name; 2560 × 1080 (64:27) and 3840 × 1600 (12:5) are rounded the same way.', 'computingskill' ),
				cs_aeo_num( 3440 / 1440, 2 ),
				cs_aeo_num( 21 / 9, 2 )
			),
		),
		array(
			'slugs' => array( 'aspect-ratio-calculator' ),
			'q'     => __( 'What aspect ratio is 1920×1200?', 'computingskill' ),
			'a'     => __( '16:10 (it simplifies to 8:5). It has the same width as 1080p with 120 extra rows of pixels, which is why 16:10 is popular on laptops and work monitors.', 'computingskill' ),
		),
		// Download time.
		array(
			'slugs' => array( 'download-time-calculator' ),
			'q'     => __( 'How long does it take to download 100GB?', 'computingskill' ),
			'a'     => sprintf(
				/* translators: 1-4: times */
				__( 'At best, about %1$s at 50 Mbps, %2$s at 100 Mbps, %3$s at 300 Mbps and %4$s at 1 Gbps. Real downloads usually take 10–20%% longer because of network overhead, Wi-Fi and server limits.', 'computingskill' ),
				cs_aeo_duration( cs_k3_dl_seconds( 100, 50 ) ),
				cs_aeo_duration( cs_k3_dl_seconds( 100, 100 ) ),
				cs_aeo_duration( cs_k3_dl_seconds( 100, 300 ) ),
				cs_aeo_duration( cs_k3_dl_seconds( 100, 1000 ) )
			),
		),
		array(
			'slugs' => array( 'download-time-calculator' ),
			'q'     => __( 'How do I calculate download time?', 'computingskill' ),
			'a'     => sprintf(
				/* translators: %s: time */
				__( 'Time in seconds = file size in GB × 8,000 ÷ speed in Mbps. For a 20GB file on 50 Mbps: 20 × 8,000 ÷ 50 = 3,200 seconds, about %s. The 8,000 turns gigabytes into megabits (1GB = 1,000MB, 1 byte = 8 bits).', 'computingskill' ),
				cs_aeo_duration( cs_k3_dl_seconds( 20, 50 ) )
			),
		),
		array(
			'slugs' => array( 'download-time-calculator' ),
			'q'     => __( 'How long does 1GB take to download?', 'computingskill' ),
			'a'     => sprintf(
				/* translators: 1-4: times */
				__( 'About %1$s at 25 Mbps, %2$s at 100 Mbps, %3$s at 300 Mbps and %4$s at 1 Gbps, as a best case.', 'computingskill' ),
				cs_aeo_duration( cs_k3_dl_seconds( 1, 25 ) ),
				cs_aeo_duration( cs_k3_dl_seconds( 1, 100 ) ),
				cs_aeo_duration( cs_k3_dl_seconds( 1, 300 ) ),
				cs_aeo_duration( cs_k3_dl_seconds( 1, 1000 ) )
			),
		),
		// Monitor size.
		array(
			'slugs' => array( 'monitor-size-calculator' ),
			'q'     => __( 'What is the best monitor size for 1440p?', 'computingskill' ),
			'a'     => sprintf(
				/* translators: 1: 27in 1440p PPI, 2: 32in 1440p PPI */
				__( '27 inches for most desks: 1440p at 27 inches is %1$s PPI, sharp at a normal desk distance with no scaling needed. A 32-inch 1440p screen is %2$s PPI, the same as a 24-inch 1080p one, so it suits sitting a little farther back.', 'computingskill' ),
				cs_aeo_num( cs_k3_ppi( 27, 2560, 1440 ), 1 ),
				cs_aeo_num( cs_k3_ppi( 32, 2560, 1440 ), 1 )
			),
		),
		array(
			'slugs' => array( 'monitor-size-calculator' ),
			'q'     => __( 'How wide is a 27-inch monitor?', 'computingskill' ),
			'a'     => sprintf(
				/* translators: %s: size */
				__( 'A 27-inch 16:9 screen is %s. The bezels add a little, and the stand needs some desk depth too, so check the maker’s spec sheet for the full dimensions.', 'computingskill' ),
				cs_k3_size_label( 27, 16, 9 )
			),
		),
		array(
			'slugs' => array( 'monitor-size-calculator' ),
			'q'     => __( 'How big is a 34-inch ultrawide monitor?', 'computingskill' ),
			'a'     => sprintf(
				/* translators: 1: size, 2: 27in height */
				__( 'A 34-inch 21:9 ultrawide’s screen is %1$s. That is about as tall as a 27-inch 16:9 monitor (%2$s inches) but about 8 inches wider.', 'computingskill' ),
				cs_k3_size_label( 34, 21, 9 ),
				cs_aeo_num( cs_k3_screen( 27, 16, 9 )[1], 1 )
			),
		),
		// PPI.
		array(
			'slugs' => array( 'ppi-calculator' ),
			'q'     => __( 'What is the PPI of a 27-inch 1440p monitor?', 'computingskill' ),
			'a'     => sprintf(
				/* translators: 1: 27in 1440p, 2: 27in 1080p, 3: 27in 4K */
				__( '%1$s PPI. For comparison, a 27-inch 1080p monitor is %2$s PPI and a 27-inch 4K monitor %3$s PPI.', 'computingskill' ),
				cs_aeo_num( cs_k3_ppi( 27, 2560, 1440 ), 1 ),
				cs_aeo_num( cs_k3_ppi( 27, 1920, 1080 ), 1 ),
				cs_aeo_num( cs_k3_ppi( 27, 3840, 2160 ), 1 )
			),
		),
		array(
			'slugs' => array( 'ppi-calculator' ),
			'q'     => __( 'How do I calculate pixel density?', 'computingskill' ),
			'a'     => sprintf(
				/* translators: 1: diagonal pixels, 2: PPI */
				__( 'Pixel density (PPI) = √(width² + height²) ÷ diagonal in inches. For a 24-inch 1920 × 1080 screen: √(1920² + 1080²) = %1$s pixels along the diagonal, ÷ 24 = %2$s PPI.', 'computingskill' ),
				cs_aeo_num( sqrt( 1920 * 1920 + 1080 * 1080 ), 1 ),
				cs_aeo_num( cs_k3_ppi( 24, 1920, 1080 ), 1 )
			),
		),
		array(
			'slugs' => array( 'ppi-calculator' ),
			'q'     => __( 'Does Windows display scaling change PPI?', 'computingskill' ),
			'a'     => __( 'No. PPI is fixed by the panel’s size and resolution. Scaling (125%, 150%…) only draws text and icons with more pixels so they stay a readable size on a high-PPI screen; the screen still has the same pixels per inch.', 'computingskill' ),
		),
		// DPI.
		array(
			'slugs' => array( 'dpi-calculator' ),
			'q'     => __( 'Is mouse DPI the same as print DPI?', 'computingskill' ),
			'a'     => __( 'No. Print DPI is how many dots a printer puts on each inch of paper, which this calculator works out. Mouse DPI (or CPI) is how many counts a mouse sensor reports per inch of movement, which sets how far the cursor moves. The two only share the name.', 'computingskill' ),
		),
		array(
			'slugs' => array( 'dpi-calculator' ),
			'q'     => __( 'How big can I print a 12MP photo?', 'computingskill' ),
			'a'     => sprintf(
				/* translators: 1-4: sizes */
				__( 'A 12MP photo is 4000 × 3000 pixels, so about %1$s × %2$s inches at 300 DPI for photo quality, or %3$s × %4$s inches at 200 DPI, which still looks good from a normal viewing distance.', 'computingskill' ),
				cs_aeo_num( 4000 / 300, 1 ),
				cs_aeo_num( 3000 / 300 ),
				cs_aeo_num( 4000 / 200 ),
				cs_aeo_num( 3000 / 200 )
			),
		),
		array(
			'slugs' => array( 'dpi-calculator' ),
			'q'     => __( 'What DPI is a 1080p image?', 'computingskill' ),
			'a'     => sprintf(
				/* translators: 1: width, 2: height */
				__( 'None until you print it: DPI depends on the print size. A 1920 × 1080 image printed at %1$s × %2$s inches is 300 DPI; printed twice as wide, it is 150 DPI.', 'computingskill' ),
				cs_aeo_num( 1920 / 300, 1 ),
				cs_aeo_num( 1080 / 300, 1 )
			),
		),
		// Compatibility.
		array(
			'slugs' => array( 'pc-compatibility-checker' ),
			'q'     => __( 'Is any GPU compatible with any CPU?', 'computingskill' ),
			'a'     => __( 'On desktops, yes: the graphics card connects to the motherboard’s PCIe slot, not to the CPU directly, so any current card works with any current CPU. Whether they are a good pair is a question of balance (a bottleneck) and of the PSU’s wattage and cables.', 'computingskill' ),
		),
		array(
			'slugs' => array( 'pc-compatibility-checker' ),
			'q'     => __( 'Will a PCIe 4.0 or 5.0 graphics card work in a PCIe 3.0 slot?', 'computingskill' ),
			'a'     => __( 'Yes. PCIe is backward compatible, so the card runs at the slot’s speed. Cards with all 16 lanes lose little or nothing; cards with only 8 lanes, such as the RTX 4060, can lose a little more in a PCIe 3.0 slot.', 'computingskill' ),
		),
		// CPU bottleneck.
		array(
			'slugs' => array( 'cpu-bottleneck-explained' ),
			'q'     => __( 'What is a CPU bottleneck?', 'computingskill' ),
			'a'     => __( 'A CPU bottleneck is when the processor can’t prepare frames as fast as the graphics card can draw them, so the GPU waits and the frame rate stops at the CPU’s limit. The signs are GPU usage well below 100%, one or more CPU cores near 100%, and FPS that doesn’t rise when you lower the resolution.', 'computingskill' ),
		),
		array(
			'slugs' => array( 'cpu-bottleneck-explained' ),
			'q'     => __( 'How do I fix a CPU bottleneck?', 'computingskill' ),
			'a'     => __( 'Raise GPU-heavy settings or resolution (they cost little FPS when the CPU is the limit), lower CPU-heavy settings like view distance and crowd density, run RAM at its rated speed (XMP or EXPO) in dual channel, close background apps, and cap the frame rate for smoother play. The lasting fix is a faster CPU, ideally one that fits your current motherboard.', 'computingskill' ),
		),
	);

	// Wattage questions use the hardware database and example builds.
	$small = cs_k3_cpu( 'ryzen-5-7600' );
	$big   = cs_k3_cpu( 'core-i9-14900k' );
	if ( $small && $big ) {
		$faqs[] = array(
			'slugs' => array( 'cpu-power-consumption-calculator' ),
			'q'     => __( 'How many watts does a CPU use while gaming?', 'computingskill' ),
			'a'     => sprintf(
				/* translators: 1: CPU, 2: W, 3: CPU, 4: W */
				__( 'Usually far less than at full load. Our estimates run from about %2$dW for a %1$s to about %4$dW for a %3$s, because games rarely load every core. Pick your CPU above for its idle, gaming and full-load figures.', 'computingskill' ),
				$small['name'],
				round( $small['gaming'] ),
				$big['name'],
				round( $big['gaming'] )
			),
		);
		$faqs[] = array(
			'slugs' => array( 'cpu-power-consumption-calculator' ),
			'q'     => __( 'How much electricity does a CPU use per hour?', 'computingskill' ),
			'a'     => sprintf(
				/* translators: 1: cost */
				__( 'A CPU drawing 100W uses 0.1 kWh in an hour: about $%s at $0.15 per kWh. Multiply your CPU’s watts by your hours and your price per kWh, then divide by 1,000.', 'computingskill' ),
				cs_aeo_num( cs_k3_cost( 100, 1, 0.15 ), 3 )
			),
		);
	}
	$b = cs_k3_build_range();
	if ( $b ) {
		$faqs[] = array(
			'slugs' => array( 'pc-power-consumption-calculator' ),
			'q'     => __( 'How many watts does a gaming PC use?', 'computingskill' ),
			'a'     => sprintf(
				/* translators: 1: low W, 2: high W, 3: low idle, 4: high idle, 5: low build, 6: high build */
				__( 'In our example builds, about %1$dW to %2$dW from the wall while gaming, from a %5$s to a %6$s, and %3$dW to %4$dW at the desktop. The graphics card is the biggest share. Enter your own parts above for your PC’s figure.', 'computingskill' ),
				$b['low_load'],
				$b['high_load'],
				$b['low_idle'],
				$b['high_idle'],
				$b['low_name'],
				$b['high_name']
			),
		);
		$faqs[] = array(
			'slugs' => array( 'pc-power-consumption-calculator' ),
			'q'     => __( 'How much does it cost to run a gaming PC per month?', 'computingskill' ),
			'a'     => sprintf(
				/* translators: 1: low cost, 2: high cost, 3: 500W cost */
				__( 'For 3 hours of gaming and 3 hours of light use a day at $0.15 per kWh, about %1$s to %2$s a month in our example builds. As a rule of thumb, every 100W for 3 hours a day costs about $%3$s a month at that price.', 'computingskill' ),
				$b['low_cost'],
				$b['high_cost'],
				cs_aeo_num( cs_k3_cost( 100, 90, 0.15 ), 2 )
			),
		);
	}
	return $faqs;
}

/**
 * Inserts the sections into the page content at priority 20, like K1.
 */
function cs_k3_page_sections( $content ) {
	if ( ! is_singular( 'page' ) || ! in_the_loop() || ! is_main_query() || false !== strpos( $content, 'cs-k3-' ) ) {
		return $content;
	}
	$s = cs_k3_sections( (string) get_post_field( 'post_name' ) );
	if ( ! $s ) {
		return $content;
	}
	if ( ! empty( $s['lead'] ) ) {
		$lead    = '<div class="cs-k1-section cs-k3-section">' . $s['lead'] . '</div>';
		$pos     = strpos( $content, '<h2' );
		$content = false === $pos ? $content . $lead : substr_replace( $content, $lead, $pos, 0 );
	}
	if ( ! empty( $s['more'] ) ) {
		$more = '<div class="cs-k1-section cs-k3-section">' . $s['more'] . '</div>';
		if ( preg_match( '#<h2[^>]*>\s*(Related Calculators|Frequently Asked)#', $content, $m, PREG_OFFSET_CAPTURE ) ) {
			$content = substr_replace( $content, $more, $m[0][1], 0 );
		} else {
			$content .= $more;
		}
	}
	return $content;
}
add_filter( 'the_content', 'cs_k3_page_sections', 20 );
