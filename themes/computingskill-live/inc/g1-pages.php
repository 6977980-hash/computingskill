<?php
/**
 * G1 (live GSC pull, 1 Nov 2026): answers for queries that already reach
 * the site but have no section that matches them.
 *
 * - /gpu-power-consumption-calculator/: monthly electricity cost and
 *   "sizing power for peak GPU demand" (spikes, several GPUs, cooling).
 * - /bandwidth-calculator/: "How to calculate bandwidth" with the formula
 *   and two worked examples.
 * - Extra FAQ on the resolution, TBW, storage, upload and 1080p vs 1440p
 *   vs 4K pages ("66% of 1080p", "Total LBAs Written", "how long will 1TB
 *   last", "1GB at 10 Mbps", "difference between 1080p, 1440p and 4K").
 *
 * Titles and descriptions are in inc/seo-compat.php. Every number is
 * computed here from the stated assumption or the hardware database.
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Share of board power a GPU draws while gaming (same ~70% as the calculator). */
function cs_g1_gaming_share() {
	return 0.7;
}

/** Hours of gaming a day used in the electricity table. */
function cs_g1_hours_per_day() {
	return 3;
}

/** kWh a month (30 days) for a draw in watts at cs_g1_hours_per_day(). */
function cs_g1_kwh_month( $watts ) {
	return $watts * cs_g1_hours_per_day() * 30 / 1000;
}

/** Seconds to move $gb gigabytes (decimal) at $mbps megabits per second. */
function cs_g1_seconds( $gb, $mbps ) {
	return $gb * 8000 / $mbps;
}

/**
 * Sections per page, same placement rules as inc/k1-pages.php: 'lead'
 * before the page's first H2, 'more' before "Related Calculators & Guides".
 */
function cs_g1_sections( $slug ) {
	switch ( $slug ) {
		case 'gpu-power-consumption-calculator':
			$gpus = cs_get_gpu_data();
			$rows = array();
			foreach ( array( 'rtx-4060', 'rtx-4070', 'rtx-5070', 'rtx-4080', 'rtx-4090' ) as $g ) {
				if ( empty( $gpus[ $g ]['tdp_w'] ) ) {
					continue;
				}
				$board  = (int) $gpus[ $g ]['tdp_w'];
				$gaming = $board * cs_g1_gaming_share();
				$kwh    = cs_g1_kwh_month( $gaming );
				$rows[] = array(
					cs_bn_short_name( $gpus[ $g ]['name'] ),
					$board . 'W',
					cs_aeo_num( $gaming ) . 'W',
					cs_aeo_num( $kwh, 1 ) . ' kWh',
					'$' . cs_aeo_num( $kwh * 0.15, 2 ),
					'$' . cs_aeo_num( $kwh * 0.30, 2 ),
				);
			}
			$more  = '<h2>' . esc_html__( 'GPU Electricity Cost per Month', 'computingskill' ) . '</h2>';
			$more .= '<p>' . esc_html( sprintf(
				/* translators: %d: hours a day */
				__( 'Cost = watts × hours ÷ 1,000 × your price per kWh. The table assumes %d hours of gaming a day for 30 days, with the card drawing about 70%% of its board power, the same gaming estimate the calculator uses. Prices of $0.15 and $0.30 per kWh cover most US and European tariffs; use the price on your own bill for an exact figure.', 'computingskill' ),
				cs_g1_hours_per_day()
			) ) . '</p>';
			$more .= cs_k1_table(
				__( 'Graphics card electricity use per month (graphics card only)', 'computingskill' ),
				array( __( 'GPU', 'computingskill' ), __( 'Board power', 'computingskill' ), __( 'Gaming draw', 'computingskill' ), __( 'Energy a month', 'computingskill' ), '$0.15/kWh', '$0.30/kWh' ),
				$rows
			);
			$more .= '<p>' . sprintf(
				/* translators: %s: link */
				esc_html__( 'The rest of the PC (CPU, motherboard, fans, monitor) adds to this. For the whole system, use the %s.', 'computingskill' ),
				cs_k1_link( 'pc-power-consumption-calculator', __( 'PC Power Consumption Calculator', 'computingskill' ) )
			) . '</p>';

			$four = 4 * 450;
			$more .= '<h2>' . esc_html__( 'Sizing Power for Peak GPU Demand', 'computingskill' ) . '</h2>';
			$more .= '<p>' . esc_html__( 'Peak demand is higher than the average a monitoring app shows. Modern graphics cards make very short power spikes (transients) above their board power, lasting from microseconds to a few milliseconds. Power supplies built to the ATX 3.0 standard are designed to ride through short excursions of up to twice their rated output; some older units shut down on them instead. So size the power supply from board power, never from the gaming average:', 'computingskill' ) . '</p><ul>';
			$more .= '<li>' . sprintf(
				/* translators: %s: link */
				esc_html__( 'One GPU: add the CPU’s and the card’s rated power plus the rest of the system, then leave headroom. The %s does this with 30%% headroom.', 'computingskill' ),
				cs_k1_link( 'psu-calculator', __( 'PSU Calculator', 'computingskill' ) )
			) . '</li>';
			$more .= '<li>' . esc_html( sprintf(
				/* translators: 1: watts */
				__( 'Several GPUs: add the board power of every card, because they can all peak at the same moment. Four 450W cards need %1$sW for the GPUs alone.', 'computingskill' ),
				cs_aeo_num( $four )
			) ) . '</li>';
			$more .= '<li>' . esc_html( sprintf(
				/* translators: 1: GPUs per megawatt */
				__( 'Servers and clusters: data-centre GPUs are rated far higher, for example up to 700W for an NVIDIA H100 SXM. One megawatt covers about %1$s of them for the GPUs alone, before CPUs, networking, power losses and cooling.', 'computingskill' ),
				cs_aeo_num( floor( 1000000 / 700 ) )
			) ) . '</li>';
			$more .= '<li>' . esc_html( sprintf(
				/* translators: 1: watts, 2: BTU per hour */
				__( 'Cooling load: nearly every watt a GPU draws ends up as heat, and 1W equals 3.412 BTU per hour. %1$sW of GPUs needs about %2$s BTU/h of cooling.', 'computingskill' ),
				cs_aeo_num( $four ),
				cs_aeo_num( $four * 3.412 )
			) ) . '</li></ul>';
			return array( 'more' => $more );

		case 'bandwidth-calculator':
			$backup = cs_g1_seconds( 50, 1 ) / ( 8 * 3600 ); // Mbps for 50GB in 8 hours.
			$office = array(
				array( __( '8 people on HD video calls', 'computingskill' ), '8 × 3 Mbps', 24 ),
				array( __( '12 people on email, web and cloud apps', 'computingskill' ), '12 × 1 Mbps', 12 ),
				array( __( 'Cloud backup during the day', 'computingskill' ), '10 Mbps', 10 ),
			);
			$sum  = 0;
			$rows = array();
			foreach ( $office as $o ) {
				$sum   += $o[2];
				$rows[] = array( $o[0], $o[1], $o[2] . ' Mbps' );
			}
			$rows[] = array( __( 'Total', 'computingskill' ), '', $sum . ' Mbps' );
			$rows[] = array( __( 'With 25% headroom', 'computingskill' ), '', cs_aeo_num( $sum * 1.25, 1 ) . ' Mbps' );

			$lead  = '<h2>' . esc_html__( 'How to Calculate Bandwidth', 'computingskill' ) . '</h2>';
			$lead .= '<p>' . esc_html__( 'Bandwidth (Mbps) = data in megabytes × 8 ÷ time in seconds. The × 8 turns bytes into bits, because files are measured in megabytes and connections in megabits per second.', 'computingskill' ) . '</p>';
			$lead .= '<p>' . esc_html( sprintf(
				/* translators: 1: Mbps */
				__( 'Example: a 50GB backup that must finish in 8 hours is 50,000MB × 8 ÷ 28,800 seconds = %1$s Mbps. Real transfers lose some speed to protocol overhead and other traffic, so plan a little above the result.', 'computingskill' ),
				cs_aeo_num( $backup, 1 )
			) ) . '</p>';
			$lead .= '<p>' . esc_html__( 'For a home or office, add up what runs at the same time instead. Example for a 20-person office at its busiest hour (per-person rates are typical figures; check your video call app’s own guide):', 'computingskill' ) . '</p>';
			$lead .= cs_k1_table(
				__( 'Bandwidth requirement for a 20-person office (example)', 'computingskill' ),
				array( __( 'Use', 'computingskill' ), __( 'Calculation', 'computingskill' ), __( 'Bandwidth', 'computingskill' ) ),
				$rows
			);
			$lead .= '<p class="cs-calc-ref-note">' . esc_html__( 'Video calls need this much in both directions, so check the upload speed of your plan as well as the download speed.', 'computingskill' ) . '</p>';
			return array( 'lead' => $lead );
	}
	return array();
}

/**
 * Extra FAQ items (printed and marked up by inc/extra-faq.php).
 */
function cs_g1_extra_faqs() {
	$p1080 = 1920 * 1080;
	$p1440 = 2560 * 1440;
	$p4k   = 3840 * 2160;
	return array(
		array(
			'slugs' => array( 'resolution-calculator' ),
			'q'     => __( 'What is 66% of 1080p?', 'computingskill' ),
			'a'     => sprintf(
				/* translators: 1-2: 66% size, 3: pixel share */
				__( '66%% of 1080p per axis is %1$s × %2$s, about %3$s%% of the pixels. A 66.7%% (two-thirds) scale, the DLSS and FSR Quality mode, renders exactly 1280 × 720. 66%% of 1440p is 1690 × 950 and 66%% of 4K is 2534 × 1426.', 'computingskill' ),
				round( 1920 * 0.66 ),
				round( 1080 * 0.66 ),
				cs_aeo_num( 0.66 * 0.66 * 100 )
			),
		),
		array(
			'slugs' => array( 'resolution-calculator' ),
			'q'     => __( 'What is linear resolution scale?', 'computingskill' ),
			'a'     => __( 'A linear (per-axis) scale multiplies both the width and the height by the percentage, so the pixel count drops by its square: 50% linear scale of 4K is 1920 × 1080, only 25% of the pixels. Most games and upscalers use a linear scale. To render half the pixels you need about 70.7% per axis.', 'computingskill' ),
		),
		array(
			'slugs' => array( 'ssd-tbw-calculator' ),
			'q'     => __( 'What happens when an SSD reaches its TBW?', 'computingskill' ),
			'a'     => __( 'Usually nothing at that moment: TBW is a warranty limit, not a switch, and many drives keep working well past it. What ends is the warranty. As the flash wears, the drive uses up its spare blocks; when they run out, many SSDs switch to read-only so your data can still be copied off. Watch the wear figure in the drive’s health tool and keep backups.', 'computingskill' ),
		),
		array(
			'slugs' => array( 'ssd-tbw-calculator', 'ssd-lifespan-calculator' ),
			'q'     => __( 'How do I convert Total LBAs Written to TB?', 'computingskill' ),
			'a'     => sprintf(
				/* translators: %s: TB */
				__( 'Many SATA SSDs report “Total LBAs Written” in their S.M.A.R.T. data. With the usual 512-byte sectors, TB written = LBAs × 512 ÷ 1,000,000,000,000, so 20,000,000,000 LBAs is %s TB. Some drives count in other units, so if the result looks far off, read the total writes in the maker’s app instead.', 'computingskill' ),
				cs_aeo_num( 20000000000 * 512 / 1e12, 2 )
			),
		),
		array(
			'slugs' => array( 'storage-calculator' ),
			'q'     => __( 'How long will 1TB of storage last?', 'computingskill' ),
			'a'     => sprintf(
				/* translators: 1: years, 2: weeks, 3: months */
				__( 'It depends on how fast you add files. Keeping 10%% free (900GB usable), 10 phone photos a day (about 40MB) take about %1$s years to fill it, one hour of 4K video a week (10.2GB) about %2$s weeks, and one new 100GB game a month %3$s months. For how long an SSD lasts before it wears out, see the SSD Lifespan Calculator.', 'computingskill' ),
				cs_aeo_num( 900 / 0.04 / 365 ),
				cs_aeo_num( 900 / 10.2 ),
				cs_aeo_num( 900 / 100 )
			),
		),
		array(
			'slugs' => array( 'upload-time-calculator' ),
			'q'     => __( 'How long does it take to upload 1GB at 10 Mbps?', 'computingskill' ),
			'a'     => sprintf(
				/* translators: 1: 1GB at 10, 2: 1GB at 50, 3: 10GB at 10 */
				__( 'At least %1$s: 1GB is 8,000 megabits, and 8,000 ÷ 10 = 800 seconds. Real uploads take a little longer because of protocol overhead and other traffic. At 50 Mbps the same 1GB takes about %2$s, and 10GB at 10 Mbps takes about %3$s.', 'computingskill' ),
				cs_aeo_duration( cs_g1_seconds( 1, 10 ) ),
				cs_aeo_duration( cs_g1_seconds( 1, 50 ) ),
				cs_aeo_duration( cs_g1_seconds( 10, 10 ) )
			),
		),
		array(
			'slugs' => array( 'upload-time-calculator' ),
			'q'     => __( 'How long does a YouTube upload take?', 'computingskill' ),
			'a'     => sprintf(
				/* translators: 1: 2GB at 10, 2: 2GB at 50 */
				__( 'The upload itself depends on the file size and your upload speed: a 2GB video takes at least %1$s at 10 Mbps and %2$s at 50 Mbps. After that YouTube still processes the video, and HD and 4K versions can take longer to appear than the first low-resolution version. Enter your file size above for your own estimate.', 'computingskill' ),
				cs_aeo_duration( cs_g1_seconds( 2, 10 ) ),
				cs_aeo_duration( cs_g1_seconds( 2, 50 ) )
			),
		),
		array(
			'slugs' => array( '1080p-vs-1440p-vs-4k' ),
			'q'     => __( 'What is the difference between 1080p, 1440p and 4K?', 'computingskill' ),
			'a'     => sprintf(
				/* translators: 1-3: pixels, 4: 1440p vs 1080p, 5: 4K vs 1440p */
				__( 'The number of pixels: 1080p (1920 × 1080) has %1$s, 1440p (2560 × 1440) has %2$s and 4K (3840 × 2160) has %3$s. 1440p is %4$s× the pixels of 1080p, and 4K is 4× 1080p and %5$s× 1440p. More pixels look sharper, most visibly on bigger screens, but the graphics card has to render every one, so each step up costs FPS.', 'computingskill' ),
				cs_aeo_num( $p1080 ),
				cs_aeo_num( $p1440 ),
				cs_aeo_num( $p4k ),
				cs_aeo_num( $p1440 / $p1080, 2 ),
				cs_aeo_num( $p4k / $p1440, 2 )
			),
		),
	);
}

/**
 * Inserts the sections. Priority 21: right after the K1 sections (20).
 */
function cs_g1_page_sections( $content ) {
	if ( ! is_singular( 'page' ) || ! in_the_loop() || ! is_main_query() || false !== strpos( $content, 'cs-g1-' ) ) {
		return $content;
	}
	$s = cs_g1_sections( (string) get_post_field( 'post_name' ) );
	if ( ! $s ) {
		return $content;
	}
	if ( ! empty( $s['lead'] ) ) {
		$lead    = '<div class="cs-k1-section cs-g1-section">' . $s['lead'] . '</div>';
		$pos     = strpos( $content, '<h2' );
		$content = false === $pos ? $content . $lead : substr_replace( $content, $lead, $pos, 0 );
	}
	if ( ! empty( $s['more'] ) ) {
		$more = '<div class="cs-k1-section cs-g1-section">' . $s['more'] . '</div>';
		if ( preg_match( '#<h2[^>]*>\s*(Related Calculators|Frequently Asked)#', $content, $m, PREG_OFFSET_CAPTURE ) ) {
			$content = substr_replace( $content, $more, $m[0][1], 0 );
		} else {
			$content .= $more;
		}
	}
	return $content;
}
add_filter( 'the_content', 'cs_g1_page_sections', 21 );
