<?php
/**
 * ComputingSkill verified hardware specification dataset.
 *
 * Sources: manufacturer specifications and Wikipedia GPU/CPU architecture
 * pages (GeForce RTX 50/40/30 series, Radeon RX 7000/6000 series, Zen 3/4,
 * Alder Lake/Raptor Lake), cross-checked September 2026. Figures are the
 * manufacturer reference-spec numbers; individual board partner cards can
 * vary slightly (factory overclocks, custom power limits).
 *
 * "score" fields are ComputingSkill's own simplified relative GAMING
 * performance index, used ONLY to drive the estimate calculators on this
 * site. They are not benchmark results and are clearly labeled as estimates
 * everywhere they are shown to a user.
 *
 * What the score represents (Phase 2 audit fix, Oct 2026):
 *  - GPU score = average rasterized gaming throughput at high settings,
 *    RTX 4090 = 100. Higher-end parts can exceed 100 (RTX 5090 = 123, see
 *    its note). Resolution is NOT baked in: inc/bottleneck.php scales GPU
 *    throughput by pixel load per resolution.
 *  - CPU score = average gaming frame-rate ceiling (how many frames per
 *    second the CPU can prepare when the GPU is not the limit). It is a
 *    gaming index, not a multi-core/productivity index, which is why an
 *    8-core X3D chip outranks 24-core parts.
 *  - The two indexes are separate scales; inc/bottleneck.php's
 *    CS_BN_CPU_PARITY converts between them. Compare CPUs with CPUs and
 *    GPUs with GPUs, never a raw CPU score against a raw GPU score.
 *
 * Oct 2026 recalibration: scores were reviewed as one gaming index and
 * outliers fixed. The biggest was ryzen-7-9800x3d at 78, below the
 * i7-13700K (84) and i9-13900K (92) although it is the fastest gaming CPU
 * here. Positions follow publicly reported review averages, e.g. TechSpot's
 * 45-game 1080p tests (RTX 4090): 9800X3D ~18% ahead of the i9-14900K and
 * ~24% ahead of the Core Ultra 9 285K
 * (https://www.techspot.com/review/2931-amd-ryzen-9800x3d-vs-intel-core-14900k/),
 * and GamersNexus's RX 9070 XT review (roughly RTX 4080-class raster, ahead
 * of the RX 7900 XT, behind the RX 7900 XTX, clearly ahead of the RTX 5070:
 * https://gamersnexus.net/gpus/amd-radeon-rx-9070-xt-gpu-review-benchmarks-vs-5070-ti-5070-7900-xt-sapphire-pulse).
 * The remaining in-between positions are ComputingSkill's approximations
 * from the same kind of public review averages; the Ryzen 5 5600 (52) and
 * RTX 4090 (100) anchor the two scales. We do not run our own benchmarks.
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns the GPU dataset.
 */
function cs_get_gpu_data() {
	static $gpus = null;
	if ( null !== $gpus ) {
		return $gpus;
	}

	// 'recommended_psu_w' re-checked against each card's own official spec
	// page (same source_url below) during the Sep 2026 audit. Five entries
	// were stale and corrected: rtx-5080 800->850, rtx-4060 500->550,
	// rx-7900-xtx 850->800, rx-7900-xt 800->750, rx-7800-xt 750->700.
	$gpus = array(
		'rtx-3060' => array(
			'name' => 'GeForce RTX 3060 (12GB)', 'brand' => 'NVIDIA', 'architecture' => 'Ampere',
			'vram_gb' => 12, 'memory_type' => 'GDDR6', 'memory_bus' => 192, 'boost_clock_mhz' => 1777,
			'tdp_w' => 170, 'cores' => 3584, 'core_label' => 'CUDA Cores', 'recommended_psu_w' => 550,
			'tier' => 'Entry / 1080p', 'score' => 34, 'release_year' => 2021,
			'source_url' => 'https://www.nvidia.com/en-us/geforce/graphics-cards/30-series/rtx-3060-3060ti/',
		),
		'rtx-4060' => array(
			'name' => 'GeForce RTX 4060', 'brand' => 'NVIDIA', 'architecture' => 'Ada Lovelace',
			'vram_gb' => 8, 'memory_type' => 'GDDR6', 'memory_bus' => 128, 'boost_clock_mhz' => 2460,
			'tdp_w' => 115, 'cores' => 3072, 'core_label' => 'CUDA Cores', 'recommended_psu_w' => 550,
			'tier' => 'Entry / 1080p', 'score' => 38, 'release_year' => 2023,
			'source_url' => 'https://www.nvidia.com/en-us/geforce/graphics-cards/40-series/rtx-4060-4060ti/',
		),
		'rtx-4070' => array(
			'name' => 'GeForce RTX 4070', 'brand' => 'NVIDIA', 'architecture' => 'Ada Lovelace',
			'vram_gb' => 12, 'memory_type' => 'GDDR6X', 'memory_bus' => 192, 'boost_clock_mhz' => 2475,
			'tdp_w' => 200, 'cores' => 5888, 'core_label' => 'CUDA Cores', 'recommended_psu_w' => 650,
			'tier' => 'Mid-range / 1440p', 'score' => 55, 'release_year' => 2023,
			'source_url' => 'https://www.nvidia.com/en-us/geforce/graphics-cards/40-series/rtx-4070-family/',
		),
		'rtx-4070-super' => array(
			'name' => 'GeForce RTX 4070 Super', 'brand' => 'NVIDIA', 'architecture' => 'Ada Lovelace',
			'vram_gb' => 12, 'memory_type' => 'GDDR6X', 'memory_bus' => 192, 'boost_clock_mhz' => 2475,
			'tdp_w' => 220, 'cores' => 7168, 'core_label' => 'CUDA Cores', 'recommended_psu_w' => 650,
			'tier' => 'Mid-range / 1440p', 'score' => 62, 'release_year' => 2024,
			'source_url' => 'https://www.nvidia.com/en-us/geforce/graphics-cards/40-series/rtx-4070-family/',
		),
		'rtx-4080' => array(
			'name' => 'GeForce RTX 4080', 'brand' => 'NVIDIA', 'architecture' => 'Ada Lovelace',
			'vram_gb' => 16, 'memory_type' => 'GDDR6X', 'memory_bus' => 256, 'boost_clock_mhz' => 2505,
			'tdp_w' => 320, 'cores' => 9728, 'core_label' => 'CUDA Cores', 'recommended_psu_w' => 750,
			'tier' => 'High-end / 4K', 'score' => 80, 'release_year' => 2022,
			'source_url' => 'https://www.nvidia.com/en-us/geforce/graphics-cards/40-series/rtx-4080-family/',
		),
		'rtx-4090' => array(
			'name' => 'GeForce RTX 4090', 'brand' => 'NVIDIA', 'architecture' => 'Ada Lovelace',
			'vram_gb' => 24, 'memory_type' => 'GDDR6X', 'memory_bus' => 384, 'boost_clock_mhz' => 2520,
			'tdp_w' => 450, 'cores' => 16384, 'core_label' => 'CUDA Cores', 'recommended_psu_w' => 850,
			'tier' => 'Flagship / 4K', 'score' => 100, 'release_year' => 2022,
			'source_url' => 'https://www.nvidia.com/en-us/geforce/graphics-cards/40-series/rtx-4090/',
		),
		'rtx-5070' => array(
			'name' => 'GeForce RTX 5070', 'brand' => 'NVIDIA', 'architecture' => 'Blackwell',
			'vram_gb' => 12, 'memory_type' => 'GDDR7', 'memory_bus' => 192, 'boost_clock_mhz' => 2510,
			'tdp_w' => 250, 'cores' => 6144, 'core_label' => 'CUDA Cores', 'recommended_psu_w' => 650,
			'tier' => 'Mid-range / 1440p', 'score' => 65, 'release_year' => 2025,
			'source_url' => 'https://www.nvidia.com/en-us/geforce/graphics-cards/50-series/rtx-5070-family/',
		),
		'rtx-5080' => array(
			'name' => 'GeForce RTX 5080', 'brand' => 'NVIDIA', 'architecture' => 'Blackwell',
			'vram_gb' => 16, 'memory_type' => 'GDDR7', 'memory_bus' => 256, 'boost_clock_mhz' => 2620,
			'tdp_w' => 360, 'cores' => 10752, 'core_label' => 'CUDA Cores', 'recommended_psu_w' => 850,
			'tier' => 'High-end / 4K', 'score' => 88, 'release_year' => 2025,
			'source_url' => 'https://www.nvidia.com/en-us/geforce/graphics-cards/50-series/rtx-5080/',
		),
		'rtx-5090' => array(
			'name' => 'GeForce RTX 5090', 'brand' => 'NVIDIA', 'architecture' => 'Blackwell',
			'vram_gb' => 32, 'memory_type' => 'GDDR7', 'memory_bus' => 512, 'boost_clock_mhz' => 2410,
			'tdp_w' => 575, 'cores' => 21760, 'core_label' => 'CUDA Cores', 'recommended_psu_w' => 1000,
			// D3 (Oct 2026, audit 1.12): re-anchored on the same kind of measure
			// as every other score, a general review average across 1440p and
			// 4K, instead of a native-4K-only ratio (+33%, dsogaming). Broad
			// review averages put the 5090 about 20% ahead of the 4090 at 1440p
			// and about 26% at 4K (TechRadar's RTX 5090 vs RTX 4090 comparison,
			// https://www.techradar.com/computing/gpu/nvidia-rtx-5090-vs-rtx-4090 ;
			// TechSpot's review shows the same spread per game,
			// https://www.techspot.com/review/2944-nvidia-geforce-rtx-5090/ ).
			// The midpoint, +23%, gives 123 (was 133). The 4090 stays at 100.
			'tier' => 'Flagship / 4K', 'score' => 123, 'release_year' => 2025,
			'source_url' => 'https://www.nvidia.com/en-us/geforce/graphics-cards/50-series/rtx-5090/',
		),
		'rx-6600' => array(
			'name' => 'Radeon RX 6600', 'brand' => 'AMD', 'architecture' => 'RDNA 2',
			'vram_gb' => 8, 'memory_type' => 'GDDR6', 'memory_bus' => 128, 'boost_clock_mhz' => 2491,
			'tdp_w' => 132, 'cores' => 28, 'core_label' => 'Compute Units', 'recommended_psu_w' => 500,
			'tier' => 'Entry / 1080p', 'score' => 33, 'release_year' => 2021,
			'source_url' => 'https://www.amd.com/en/products/graphics/desktops/radeon/6000-series/amd-radeon-rx-6600.html',
		),
		'rx-7600' => array(
			'name' => 'Radeon RX 7600', 'brand' => 'AMD', 'architecture' => 'RDNA 3',
			'vram_gb' => 8, 'memory_type' => 'GDDR6', 'memory_bus' => 128, 'boost_clock_mhz' => 2655,
			'tdp_w' => 165, 'cores' => 32, 'core_label' => 'Compute Units', 'recommended_psu_w' => 550,
			'tier' => 'Entry / 1080p', 'score' => 37, 'release_year' => 2023,
			'source_url' => 'https://www.amd.com/en/products/graphics/desktops/radeon/7000-series/amd-radeon-rx-7600.html',
		),
		'rx-7700-xt' => array(
			'name' => 'Radeon RX 7700 XT', 'brand' => 'AMD', 'architecture' => 'RDNA 3',
			'vram_gb' => 12, 'memory_type' => 'GDDR6', 'memory_bus' => 192, 'boost_clock_mhz' => 2544,
			'tdp_w' => 245, 'cores' => 54, 'core_label' => 'Compute Units', 'recommended_psu_w' => 700,
			'tier' => 'Mid-range / 1440p', 'score' => 52, 'release_year' => 2023,
			'source_url' => 'https://www.amd.com/en/products/graphics/desktops/radeon/7000-series/amd-radeon-rx-7700-xt.html',
		),
		'rx-7800-xt' => array(
			'name' => 'Radeon RX 7800 XT', 'brand' => 'AMD', 'architecture' => 'RDNA 3',
			'vram_gb' => 16, 'memory_type' => 'GDDR6', 'memory_bus' => 256, 'boost_clock_mhz' => 2430,
			'tdp_w' => 263, 'cores' => 60, 'core_label' => 'Compute Units', 'recommended_psu_w' => 700,
			'tier' => 'Mid-range / 1440p', 'score' => 58, 'release_year' => 2023,
			'source_url' => 'https://www.amd.com/en/products/graphics/desktops/radeon/7000-series/amd-radeon-rx-7800-xt.html',
		),
		'rx-7900-xt' => array(
			'name' => 'Radeon RX 7900 XT', 'brand' => 'AMD', 'architecture' => 'RDNA 3',
			'vram_gb' => 20, 'memory_type' => 'GDDR6', 'memory_bus' => 320, 'boost_clock_mhz' => 2400,
			'tdp_w' => 315, 'cores' => 84, 'core_label' => 'Compute Units', 'recommended_psu_w' => 750,
			'tier' => 'High-end / 4K', 'score' => 70, 'release_year' => 2022,
			'source_url' => 'https://www.amd.com/en/products/graphics/desktops/radeon/7000-series/amd-radeon-rx-7900xt.html',
		),
		'rx-7900-xtx' => array(
			'name' => 'Radeon RX 7900 XTX', 'brand' => 'AMD', 'architecture' => 'RDNA 3',
			'vram_gb' => 24, 'memory_type' => 'GDDR6', 'memory_bus' => 384, 'boost_clock_mhz' => 2500,
			'tdp_w' => 355, 'cores' => 96, 'core_label' => 'Compute Units', 'recommended_psu_w' => 800,
			'tier' => 'Flagship / 4K', 'score' => 85, 'release_year' => 2022,
			'source_url' => 'https://www.amd.com/en/products/graphics/desktops/radeon/7000-series/amd-radeon-rx-7900xtx.html',
		),
		// RTX 5060 Ti and RX 9070 XT were added directly on the live site
		// (Theme File Editor) after v1.0.21 and are carried over here so a
		// theme upload doesn't wipe them. Spec fields re-checked against each
		// maker's own page, Sep 2026: NVIDIA RTX 5060 family page — 4608 CUDA
		// cores, 2.57 GHz boost, 16GB GDDR7, 128-bit, 180W TGP, 600W required
		// system power; AMD RX 9070 XT page — 64 CUs, up to 2970 MHz, 16GB
		// GDDR6, 256-bit, 304W typical board power, 750W recommended PSU
		// (source_url switched from Wikipedia to that AMD page). The 'score'
		// values are this site's own relative index, as for every other part.
		'rtx-5060-ti' => array(
			'name' => 'GeForce RTX 5060 Ti (16GB)', 'brand' => 'NVIDIA', 'architecture' => 'Blackwell',
			'vram_gb' => 16, 'memory_type' => 'GDDR7', 'memory_bus' => 128, 'boost_clock_mhz' => 2570,
			'tdp_w' => 180, 'cores' => 4608, 'core_label' => 'CUDA Cores', 'recommended_psu_w' => 600,
			'tier' => 'Mid-range / 1440p', 'score' => 50, 'release_year' => 2025,
			'source_url' => 'https://www.nvidia.com/en-us/geforce/graphics-cards/50-series/rtx-5060-family/',
		),
		'rx-9070-xt' => array(
			'name' => 'Radeon RX 9070 XT', 'brand' => 'AMD', 'architecture' => 'RDNA 4',
			'vram_gb' => 16, 'memory_type' => 'GDDR6', 'memory_bus' => 256, 'boost_clock_mhz' => 2970,
			'tdp_w' => 304, 'cores' => 64, 'core_label' => 'Compute Units', 'recommended_psu_w' => 750,
			'tier' => 'High-end / 1440p-4K', 'score' => 78, 'release_year' => 2025,
			'source_url' => 'https://www.amd.com/en/products/graphics/desktops/radeon/9000-series/amd-radeon-rx-9070xt.html',
		),
		// Phase 7 (Oct 2026): three current cards added for new combo pages.
		// Spec fields copied from each maker's own page, checked 13 Oct 2026:
		// NVIDIA RTX 5060 family page — RTX 5060: 3840 CUDA cores, 2.50 GHz
		// boost, 8GB GDDR7, 128-bit, 145W TGP, 550W required system power.
		// NVIDIA RTX 5070 family page — RTX 5070 Ti: 8960 CUDA cores, 2.45 GHz
		// boost, 16GB GDDR7, 256-bit, 300W TGP, 750W required system power.
		// AMD RX 9060 XT page (16GB model) — 32 CUs, up to 3130 MHz, 16GB
		// GDDR6, 128-bit, 160W typical board power, 450W minimum PSU.
		// Scores are this site's own gaming index (see the file header),
		// placed from public review averages: RTX 5060 a little above the
		// RTX 4060 and below the RX 9060 XT 16GB, which sits a few percent
		// behind the RTX 5060 Ti 16GB; RTX 5070 Ti slightly ahead of the
		// RX 9070 XT and slightly behind the RTX 4080. The two sub-50 cards
		// are labeled 1080p because the PC Upgrade Calculator's 1440p GPU
		// target is 50 (assets/js/calculators.js CS_DEFS.upgrade).
		'rtx-5060' => array(
			'name' => 'GeForce RTX 5060', 'brand' => 'NVIDIA', 'architecture' => 'Blackwell',
			'vram_gb' => 8, 'memory_type' => 'GDDR7', 'memory_bus' => 128, 'boost_clock_mhz' => 2500,
			'tdp_w' => 145, 'cores' => 3840, 'core_label' => 'CUDA Cores', 'recommended_psu_w' => 550,
			'tier' => 'Mainstream / 1080p', 'score' => 45, 'release_year' => 2025,
			'source_url' => 'https://www.nvidia.com/en-us/geforce/graphics-cards/50-series/rtx-5060-family/',
		),
		'rx-9060-xt' => array(
			'name' => 'Radeon RX 9060 XT (16GB)', 'brand' => 'AMD', 'architecture' => 'RDNA 4',
			'vram_gb' => 16, 'memory_type' => 'GDDR6', 'memory_bus' => 128, 'boost_clock_mhz' => 3130,
			'tdp_w' => 160, 'cores' => 32, 'core_label' => 'Compute Units', 'recommended_psu_w' => 450,
			'tier' => 'Mainstream / 1080p', 'score' => 47, 'release_year' => 2025,
			'source_url' => 'https://www.amd.com/en/products/graphics/desktops/radeon/9000-series/amd-radeon-rx-9060xt.html',
		),
		'rtx-5070-ti' => array(
			'name' => 'GeForce RTX 5070 Ti', 'brand' => 'NVIDIA', 'architecture' => 'Blackwell',
			'vram_gb' => 16, 'memory_type' => 'GDDR7', 'memory_bus' => 256, 'boost_clock_mhz' => 2450,
			'tdp_w' => 300, 'cores' => 8960, 'core_label' => 'CUDA Cores', 'recommended_psu_w' => 750,
			'tier' => 'High-end / 4K', 'score' => 79, 'release_year' => 2025,
			'source_url' => 'https://www.nvidia.com/en-us/geforce/graphics-cards/50-series/rtx-5070-family/',
		),
		// D4 (Oct 2026, deep audit 1.11): 17 popular GPUs added. Spec fields
		// copied from each maker's own page, checked 17 Oct 2026. GTX 16 and
		// RTX 20 cards: NVIDIA's compare page (the series pages carry no
		// specs); GTX 1650 = original GDDR5 model, RTX 2060 = original 6GB.
		// RTX 3060 Ti = GDDR6 model, RTX 3080 = 10GB model, RTX 4060 Ti = 8GB
		// model. AMD figures from each product page (typical board power,
		// "Minimum PSU Recommendation"). The RX 7900 GRE page lists no PSU
		// figure, so 700W is used: AMD's figure for the RX 7800 XT, whose
		// board power (263W) is about the same as the GRE's 260W. Arc B580
		// and A750: Intel's specifications pages (TBP, graphics clock); the
		// A750 page lists no PSU, so 600W is Intel's own minimum from its Arc
		// support article (intel.com/content/www/us/en/support/articles/000094366/graphics.html).
		// Release years for NVIDIA cards are not on their pages; they are the
		// launch years. Scores on the same gaming index, placed from public
		// review averages: RTX 3060 Ti a little below the RTX 5060, RTX 3070
		// just under 50, RTX 3080 10GB a little above the RTX 4070, RTX 4060 Ti
		// 8GB just above the RTX 5060, RX 6700 XT just under the RTX 3070,
		// RX 6800 between the RX 7700 XT and RTX 4070, RX 7900 GRE level with
		// the RTX 4070 Super, RX 9070 about 12% behind the RX 9070 XT, Arc B580
		// a little above the RTX 4060, Arc A750 close to the RTX 3060, RTX 5050
		// about level with the RTX 4060, GTX 1650 about half an RTX 3060. Under
		// 50 is labelled 1080p (the Upgrade Calculator's 1440p GPU target).
		'gtx-1650' => array(
			'name' => 'GeForce GTX 1650', 'brand' => 'NVIDIA', 'architecture' => 'Turing',
			'vram_gb' => 4, 'memory_type' => 'GDDR5', 'memory_bus' => 128, 'boost_clock_mhz' => 1665,
			'tdp_w' => 75, 'cores' => 896, 'core_label' => 'CUDA Cores', 'recommended_psu_w' => 300,
			'tier' => 'Entry / 1080p', 'score' => 17, 'release_year' => 2019,
			'source_url' => 'https://www.nvidia.com/en-us/geforce/graphics-cards/compare/?section=compare-16',
		),
		'gtx-1660-super' => array(
			'name' => 'GeForce GTX 1660 Super', 'brand' => 'NVIDIA', 'architecture' => 'Turing',
			'vram_gb' => 6, 'memory_type' => 'GDDR6', 'memory_bus' => 192, 'boost_clock_mhz' => 1785,
			'tdp_w' => 125, 'cores' => 1408, 'core_label' => 'CUDA Cores', 'recommended_psu_w' => 450,
			'tier' => 'Entry / 1080p', 'score' => 25, 'release_year' => 2019,
			'source_url' => 'https://www.nvidia.com/en-us/geforce/graphics-cards/compare/?section=compare-16',
		),
		'rtx-2060' => array(
			'name' => 'GeForce RTX 2060 (6GB)', 'brand' => 'NVIDIA', 'architecture' => 'Turing',
			'vram_gb' => 6, 'memory_type' => 'GDDR6', 'memory_bus' => 192, 'boost_clock_mhz' => 1680,
			'tdp_w' => 160, 'cores' => 1920, 'core_label' => 'CUDA Cores', 'recommended_psu_w' => 500,
			'tier' => 'Entry / 1080p', 'score' => 30, 'release_year' => 2019,
			'source_url' => 'https://www.nvidia.com/en-us/geforce/graphics-cards/compare/?section=compare-20',
		),
		'rtx-3060-ti' => array(
			'name' => 'GeForce RTX 3060 Ti', 'brand' => 'NVIDIA', 'architecture' => 'Ampere',
			'vram_gb' => 8, 'memory_type' => 'GDDR6', 'memory_bus' => 256, 'boost_clock_mhz' => 1670,
			'tdp_w' => 200, 'cores' => 4864, 'core_label' => 'CUDA Cores', 'recommended_psu_w' => 600,
			'tier' => 'Mainstream / 1080p', 'score' => 43, 'release_year' => 2020,
			'source_url' => 'https://www.nvidia.com/en-us/geforce/graphics-cards/30-series/rtx-3060-3060ti/',
		),
		'rtx-3070' => array(
			'name' => 'GeForce RTX 3070', 'brand' => 'NVIDIA', 'architecture' => 'Ampere',
			'vram_gb' => 8, 'memory_type' => 'GDDR6', 'memory_bus' => 256, 'boost_clock_mhz' => 1730,
			'tdp_w' => 220, 'cores' => 5888, 'core_label' => 'CUDA Cores', 'recommended_psu_w' => 650,
			'tier' => 'Mainstream / 1080p', 'score' => 49, 'release_year' => 2020,
			'source_url' => 'https://www.nvidia.com/en-us/geforce/graphics-cards/30-series/rtx-3070-3070ti/',
		),
		'rtx-3080' => array(
			'name' => 'GeForce RTX 3080 (10GB)', 'brand' => 'NVIDIA', 'architecture' => 'Ampere',
			'vram_gb' => 10, 'memory_type' => 'GDDR6X', 'memory_bus' => 320, 'boost_clock_mhz' => 1710,
			'tdp_w' => 320, 'cores' => 8704, 'core_label' => 'CUDA Cores', 'recommended_psu_w' => 750,
			'tier' => 'Mid-range / 1440p', 'score' => 57, 'release_year' => 2020,
			'source_url' => 'https://www.nvidia.com/en-us/geforce/graphics-cards/30-series/rtx-3080-3080ti/',
		),
		'rtx-4060-ti' => array(
			'name' => 'GeForce RTX 4060 Ti (8GB)', 'brand' => 'NVIDIA', 'architecture' => 'Ada Lovelace',
			'vram_gb' => 8, 'memory_type' => 'GDDR6', 'memory_bus' => 128, 'boost_clock_mhz' => 2540,
			'tdp_w' => 160, 'cores' => 4352, 'core_label' => 'CUDA Cores', 'recommended_psu_w' => 550,
			'tier' => 'Mainstream / 1080p', 'score' => 46, 'release_year' => 2023,
			'source_url' => 'https://www.nvidia.com/en-us/geforce/graphics-cards/40-series/rtx-4060-4060ti/',
		),
		'rtx-5050' => array(
			'name' => 'GeForce RTX 5050', 'brand' => 'NVIDIA', 'architecture' => 'Blackwell',
			'vram_gb' => 8, 'memory_type' => 'GDDR6', 'memory_bus' => 128, 'boost_clock_mhz' => 2570,
			'tdp_w' => 130, 'cores' => 2560, 'core_label' => 'CUDA Cores', 'recommended_psu_w' => 550,
			'tier' => 'Entry / 1080p', 'score' => 37, 'release_year' => 2025,
			'source_url' => 'https://www.nvidia.com/en-us/geforce/graphics-cards/50-series/rtx-5050/',
		),
		'rx-6600-xt' => array(
			'name' => 'Radeon RX 6600 XT', 'brand' => 'AMD', 'architecture' => 'RDNA 2',
			'vram_gb' => 8, 'memory_type' => 'GDDR6', 'memory_bus' => 128, 'boost_clock_mhz' => 2589,
			'tdp_w' => 160, 'cores' => 32, 'core_label' => 'Compute Units', 'recommended_psu_w' => 500,
			'tier' => 'Entry / 1080p', 'score' => 38, 'release_year' => 2021,
			'source_url' => 'https://www.amd.com/en/products/graphics/desktops/radeon/6000-series/amd-radeon-rx-6600-xt.html',
		),
		'rx-6650-xt' => array(
			'name' => 'Radeon RX 6650 XT', 'brand' => 'AMD', 'architecture' => 'RDNA 2',
			'vram_gb' => 8, 'memory_type' => 'GDDR6', 'memory_bus' => 128, 'boost_clock_mhz' => 2635,
			'tdp_w' => 180, 'cores' => 32, 'core_label' => 'Compute Units', 'recommended_psu_w' => 500,
			'tier' => 'Entry / 1080p', 'score' => 40, 'release_year' => 2022,
			'source_url' => 'https://www.amd.com/en/products/graphics/desktops/radeon/6000-series/amd-radeon-rx-6650-xt.html',
		),
		'rx-6700-xt' => array(
			'name' => 'Radeon RX 6700 XT', 'brand' => 'AMD', 'architecture' => 'RDNA 2',
			'vram_gb' => 12, 'memory_type' => 'GDDR6', 'memory_bus' => 192, 'boost_clock_mhz' => 2581,
			'tdp_w' => 230, 'cores' => 40, 'core_label' => 'Compute Units', 'recommended_psu_w' => 650,
			'tier' => 'Mainstream / 1080p', 'score' => 47, 'release_year' => 2021,
			'source_url' => 'https://www.amd.com/en/products/graphics/desktops/radeon/6000-series/amd-radeon-rx-6700-xt.html',
		),
		'rx-6800' => array(
			'name' => 'Radeon RX 6800', 'brand' => 'AMD', 'architecture' => 'RDNA 2',
			'vram_gb' => 16, 'memory_type' => 'GDDR6', 'memory_bus' => 256, 'boost_clock_mhz' => 2105,
			'tdp_w' => 250, 'cores' => 60, 'core_label' => 'Compute Units', 'recommended_psu_w' => 650,
			'tier' => 'Mid-range / 1440p', 'score' => 53, 'release_year' => 2020,
			'source_url' => 'https://www.amd.com/en/products/graphics/desktops/radeon/6000-series/amd-radeon-rx-6800.html',
		),
		'rx-7600-xt' => array(
			'name' => 'Radeon RX 7600 XT', 'brand' => 'AMD', 'architecture' => 'RDNA 3',
			'vram_gb' => 16, 'memory_type' => 'GDDR6', 'memory_bus' => 128, 'boost_clock_mhz' => 2755,
			'tdp_w' => 190, 'cores' => 32, 'core_label' => 'Compute Units', 'recommended_psu_w' => 600,
			'tier' => 'Entry / 1080p', 'score' => 39, 'release_year' => 2024,
			'source_url' => 'https://www.amd.com/en/products/graphics/desktops/radeon/7000-series/amd-radeon-rx-7600-xt.html',
		),
		'rx-7900-gre' => array(
			'name' => 'Radeon RX 7900 GRE', 'brand' => 'AMD', 'architecture' => 'RDNA 3',
			'vram_gb' => 16, 'memory_type' => 'GDDR6', 'memory_bus' => 256, 'boost_clock_mhz' => 2245,
			'tdp_w' => 260, 'cores' => 80, 'core_label' => 'Compute Units', 'recommended_psu_w' => 700,
			'tier' => 'Mid-range / 1440p', 'score' => 62, 'release_year' => 2023,
			'source_url' => 'https://www.amd.com/en/products/graphics/desktops/radeon/7000-series/amd-radeon-rx-7900-gre.html',
		),
		'rx-9070' => array(
			'name' => 'Radeon RX 9070', 'brand' => 'AMD', 'architecture' => 'RDNA 4',
			'vram_gb' => 16, 'memory_type' => 'GDDR6', 'memory_bus' => 256, 'boost_clock_mhz' => 2520,
			'tdp_w' => 220, 'cores' => 56, 'core_label' => 'Compute Units', 'recommended_psu_w' => 650,
			'tier' => 'High-end / 1440p', 'score' => 69, 'release_year' => 2025,
			'source_url' => 'https://www.amd.com/en/products/graphics/desktops/radeon/9000-series/amd-radeon-rx-9070.html',
		),
		'arc-b580' => array(
			'name' => 'Intel Arc B580', 'brand' => 'Intel', 'architecture' => 'Xe2 (Battlemage)',
			'vram_gb' => 12, 'memory_type' => 'GDDR6', 'memory_bus' => 192, 'boost_clock_mhz' => 2670,
			'tdp_w' => 190, 'cores' => 20, 'core_label' => 'Xe-cores', 'recommended_psu_w' => 600,
			'tier' => 'Mainstream / 1080p', 'score' => 41, 'release_year' => 2024,
			'source_url' => 'https://www.intel.com/content/www/us/en/products/sku/241598/intel-arc-b580-graphics/specifications.html',
		),
		'arc-a750' => array(
			'name' => 'Intel Arc A750', 'brand' => 'Intel', 'architecture' => 'Xe HPG (Alchemist)',
			'vram_gb' => 8, 'memory_type' => 'GDDR6', 'memory_bus' => 256, 'boost_clock_mhz' => 2050,
			'tdp_w' => 225, 'cores' => 28, 'core_label' => 'Xe-cores', 'recommended_psu_w' => 600,
			'tier' => 'Entry / 1080p', 'score' => 33, 'release_year' => 2022,
			'source_url' => 'https://www.intel.com/content/www/us/en/products/sku/227954/intel-arc-a750-graphics/specifications.html',
		),
	);

	return $gpus;
}

/**
 * Returns the CPU dataset.
 */
function cs_get_cpu_data() {
	static $cpus = null;
	if ( null !== $cpus ) {
		return $cpus;
	}

	// 'max_power_w' added during the Sep 2026 audit: the CPU power
	// calculator's "full load" estimate used to be a flat tdp_w * 1.08 for
	// every CPU, which badly understated real full-load draw for unlocked
	// Intel K-series chips — Intel's own spec pages (same source_url as
	// below, checked Sep 2026) publish a "Maximum Turbo Power" figure far
	// above the "Processor Base Power" (tdp_w) for those parts (e.g.
	// i9-13900K: 125W base vs 253W max turbo). For AMD, Ryzen's own official
	// rule is "TDP*1.35 is the standard calculation for TDP v. PPT for AMD
	// sockets in the 'Zen' era" (AMD, via Tom's Hardware's report of AMD's
	// Ryzen 7000 clarification, checked Sep 2026:
	// https://www.tomshardware.com/news/amd-corrects-socket-am5-for-ryzen-7000-power-specs-230w-peak-power-170w-tdp
	// — confirmed 170W TDP -> 230W PPT exactly; the 65W/105W tiers below
	// apply that same official 1.35x rule). assets/js/calculators.js and
	// inc/bottleneck.php's CPU power path use this field for "full load"
	// instead of the flat multiplier; GPUs are unaffected (a GPU's tdp_w
	// already represents near-peak board power, not a separate base rating).
	$cpus = array(
		'ryzen-5-5600' => array(
			'name' => 'AMD Ryzen 5 5600', 'brand' => 'AMD', 'generation' => 'Zen 3 (5000 Series)',
			'cores' => 6, 'threads' => 12, 'base_clock_ghz' => 3.5, 'boost_clock_ghz' => 4.4,
			'tdp_w' => 65, 'max_power_w' => 88, 'socket' => 'AM4', 'score' => 52, 'release_year' => 2022,
			'source_url' => 'https://www.amd.com/en/support/downloads/drivers.html/processors/ryzen/ryzen-5000-series/amd-ryzen-5-5600.html',
		),
		'ryzen-5-5600x' => array(
			'name' => 'AMD Ryzen 5 5600X', 'brand' => 'AMD', 'generation' => 'Zen 3 (5000 Series)',
			'cores' => 6, 'threads' => 12, 'base_clock_ghz' => 3.7, 'boost_clock_ghz' => 4.6,
			'tdp_w' => 65, 'max_power_w' => 88, 'socket' => 'AM4', 'score' => 54, 'release_year' => 2020,
			'source_url' => 'https://www.amd.com/en/products/processors/desktops/ryzen/5000-series/amd-ryzen-5-5600x.html',
		),
		'ryzen-7-5700x' => array(
			'name' => 'AMD Ryzen 7 5700X', 'brand' => 'AMD', 'generation' => 'Zen 3 (5000 Series)',
			'cores' => 8, 'threads' => 16, 'base_clock_ghz' => 3.4, 'boost_clock_ghz' => 4.6,
			'tdp_w' => 65, 'max_power_w' => 88, 'socket' => 'AM4', 'score' => 55, 'release_year' => 2022,
			'source_url' => 'https://www.amd.com/en/support/downloads/drivers.html/processors/ryzen/ryzen-5000-series/amd-ryzen-7-5700x.html',
		),
		'ryzen-5-7600' => array(
			'name' => 'AMD Ryzen 5 7600', 'brand' => 'AMD', 'generation' => 'Zen 4 (7000 Series)',
			'cores' => 6, 'threads' => 12, 'base_clock_ghz' => 3.8, 'boost_clock_ghz' => 5.1,
			'tdp_w' => 65, 'max_power_w' => 88, 'socket' => 'AM5', 'score' => 67, 'release_year' => 2023,
			'source_url' => 'https://www.amd.com/en/products/processors/desktops/ryzen/7000-series/amd-ryzen-5-7600.html',
		),
		'ryzen-5-7600x' => array(
			'name' => 'AMD Ryzen 5 7600X', 'brand' => 'AMD', 'generation' => 'Zen 4 (7000 Series)',
			'cores' => 6, 'threads' => 12, 'base_clock_ghz' => 4.7, 'boost_clock_ghz' => 5.3,
			'tdp_w' => 105, 'max_power_w' => 142, 'socket' => 'AM5', 'score' => 69, 'release_year' => 2022,
			'source_url' => 'https://www.amd.com/en/products/processors/desktops/ryzen/7000-series/amd-ryzen-5-7600x.html',
		),
		'ryzen-7-7700x' => array(
			'name' => 'AMD Ryzen 7 7700X', 'brand' => 'AMD', 'generation' => 'Zen 4 (7000 Series)',
			'cores' => 8, 'threads' => 16, 'base_clock_ghz' => 4.5, 'boost_clock_ghz' => 5.4,
			'tdp_w' => 105, 'max_power_w' => 142, 'socket' => 'AM5', 'score' => 73, 'release_year' => 2022,
			'source_url' => 'https://www.amd.com/en/products/processors/desktops/ryzen/7000-series/amd-ryzen-7-7700x.html',
		),
		// Boost clock corrected during Sep 2026 audit: was listed as 5.4GHz;
		// AMD's own product page (amd.com/en/products/processors/desktops/
		// ryzen/7000-series/amd-ryzen-9-7900x.html, checked Sep 2026) states
		// "Up to 5.6 GHz". Everything else on this row was already correct.
		'ryzen-9-7900x' => array(
			'name' => 'AMD Ryzen 9 7900X', 'brand' => 'AMD', 'generation' => 'Zen 4 (7000 Series)',
			'cores' => 12, 'threads' => 24, 'base_clock_ghz' => 4.7, 'boost_clock_ghz' => 5.6,
			'tdp_w' => 170, 'max_power_w' => 230, 'socket' => 'AM5', 'score' => 72, 'release_year' => 2022,
			'source_url' => 'https://www.amd.com/en/products/processors/desktops/ryzen/7000-series/amd-ryzen-9-7900x.html',
		),
		'core-i5-12400f' => array(
			'name' => 'Intel Core i5-12400F', 'brand' => 'Intel', 'generation' => '12th Gen (Alder Lake)',
			'cores' => 6, 'threads' => 12, 'base_clock_ghz' => 2.5, 'boost_clock_ghz' => 4.4,
			'tdp_w' => 65, 'max_power_w' => 117, 'socket' => 'LGA 1700', 'score' => 55, 'release_year' => 2022,
			'source_url' => 'https://www.intel.com/content/www/us/en/products/sku/134587/intel-core-i512400f-processor-18m-cache-up-to-4-40-ghz/specifications.html',
		),
		// Thread count corrected during Sep 2026 audit: was listed as 14;
		// Intel's own specifications page (intel.com/content/www/us/en/
		// products/sku/230501/.../specifications.html, checked Sep 2026)
		// states Total Threads: 16 (6 P-cores x2 Hyper-Threading + 4 E-cores
		// x1 = 16). Core count (10) and clocks were already correct.
		'core-i5-13400f' => array(
			'name' => 'Intel Core i5-13400F', 'brand' => 'Intel', 'generation' => '13th Gen (Raptor Lake)',
			'cores' => 10, 'threads' => 16, 'base_clock_ghz' => 2.5, 'boost_clock_ghz' => 4.6,
			'tdp_w' => 65, 'max_power_w' => 148, 'socket' => 'LGA 1700', 'score' => 59, 'release_year' => 2023,
			'source_url' => 'https://www.intel.com/content/www/us/en/products/sku/230501/intel-core-i513400f-processor-20m-cache-up-to-4-60-ghz/specifications.html',
		),
		'core-i5-13600k' => array(
			'name' => 'Intel Core i5-13600K', 'brand' => 'Intel', 'generation' => '13th Gen (Raptor Lake)',
			'cores' => 14, 'threads' => 20, 'base_clock_ghz' => 3.5, 'boost_clock_ghz' => 5.1,
			'tdp_w' => 125, 'max_power_w' => 181, 'socket' => 'LGA 1700', 'score' => 70, 'release_year' => 2022,
			'source_url' => 'https://www.intel.com/content/www/us/en/products/sku/230493/intel-core-i513600k-processor-24m-cache-up-to-5-10-ghz/specifications.html',
		),
		'core-i7-13700k' => array(
			'name' => 'Intel Core i7-13700K', 'brand' => 'Intel', 'generation' => '13th Gen (Raptor Lake)',
			'cores' => 16, 'threads' => 24, 'base_clock_ghz' => 3.4, 'boost_clock_ghz' => 5.4,
			'tdp_w' => 125, 'max_power_w' => 253, 'socket' => 'LGA 1700', 'score' => 75, 'release_year' => 2022,
			'source_url' => 'https://www.intel.com/content/www/us/en/products/sku/230500/intel-core-i713700k-processor-30m-cache-up-to-5-40-ghz/specifications.html',
		),
		'core-i9-13900k' => array(
			'name' => 'Intel Core i9-13900K', 'brand' => 'Intel', 'generation' => '13th Gen (Raptor Lake)',
			'cores' => 24, 'threads' => 32, 'base_clock_ghz' => 3.0, 'boost_clock_ghz' => 5.8,
			'tdp_w' => 125, 'max_power_w' => 253, 'socket' => 'LGA 1700', 'score' => 78, 'release_year' => 2022,
			'source_url' => 'https://www.intel.com/content/www/us/en/products/sku/230496/intel-core-i913900k-processor-36m-cache-up-to-5-80-ghz/specifications.html',
		),
		// Added on the live site after v1.0.21 and carried over here.
		// Re-checked Sep 2026: AMD's 9800X3D page — 8C/16T, 4.7 GHz base,
		// up to 5.2 GHz, 120W default TDP (max_power_w 162 = AMD's TDP × 1.35
		// PPT rule, see the note above); Intel's 285K page — 24 cores / 24
		// threads, 3.7 GHz P-core base, 5.7 GHz max turbo, 125W Processor Base
		// Power, 250W Maximum Turbo Power, FCLGA1851, DDR5 only.
		'ryzen-7-9800x3d' => array(
			'name' => 'AMD Ryzen 7 9800X3D', 'brand' => 'AMD', 'generation' => 'Zen 5 (9000 Series, 3D V-Cache)',
			'cores' => 8, 'threads' => 16, 'base_clock_ghz' => 4.7, 'boost_clock_ghz' => 5.2,
			'tdp_w' => 120, 'max_power_w' => 162, 'socket' => 'AM5', 'score' => 95, 'release_year' => 2024,
			'source_url' => 'https://www.amd.com/en/products/processors/desktops/ryzen/9000-series/amd-ryzen-7-9800x3d.html',
		),
		'core-ultra-9-285k' => array(
			'name' => 'Intel Core Ultra 9 285K', 'brand' => 'Intel', 'generation' => 'Arrow Lake (Core Ultra 200S)',
			'cores' => 24, 'threads' => 24, 'base_clock_ghz' => 3.7, 'boost_clock_ghz' => 5.7,
			'tdp_w' => 125, 'max_power_w' => 250, 'socket' => 'LGA 1851', 'score' => 77, 'release_year' => 2024,
			'source_url' => 'https://www.intel.com/content/www/us/en/products/sku/241060/intel-core-ultra-9-processor-285k-36m-cache-up-to-5-70-ghz/specifications.html',
		),
		// Phase 7 (Oct 2026): five CPUs added for new combo pages. Spec fields
		// copied from each maker's own page, checked 13 Oct 2026:
		// Intel i3-12100F — 4C/8T, 3.3 GHz base, 4.3 GHz max turbo, 58W base,
		// 89W max turbo, FCLGA1700. Intel i5-14600K — 14C/20T, 3.5 GHz P-core
		// base, 5.3 GHz max turbo, 125W base, 181W max turbo, FCLGA1700.
		// Intel i7-14700K — 20C/28T, 3.4 GHz P-core base, 5.6 GHz max turbo,
		// 125W base, 253W max turbo, FCLGA1700. Intel Core Ultra 5 245K —
		// 14C/14T, 4.2 GHz P-core base, 5.2 GHz max turbo, 125W base, 159W max
		// turbo, FCLGA1851. AMD Ryzen 7 9700X — 8C/16T, 3.8 GHz base, up to
		// 5.5 GHz, 65W default TDP (max_power_w 88 = AMD's TDP x 1.35 rule),
		// AM5. Scores placed from public review averages on the same gaming
		// index: i3-12100F below the i5-12400F (4 cores), i5-14600K a step above
		// the i5-13600K, i7-14700K level with the i9-13900K, Ultra 5 245K below
		// the i5-13600K, Ryzen 7 9700X a little above the Ryzen 7 7700X.
		// The Ryzen 5 5500 was considered but left out: AMD only publishes it
		// on a driver/support page, and source_url must be a product page.
		'core-i3-12100f' => array(
			'name' => 'Intel Core i3-12100F', 'brand' => 'Intel', 'generation' => '12th Gen (Alder Lake)',
			'cores' => 4, 'threads' => 8, 'base_clock_ghz' => 3.3, 'boost_clock_ghz' => 4.3,
			'tdp_w' => 58, 'max_power_w' => 89, 'socket' => 'LGA 1700', 'score' => 48, 'release_year' => 2022,
			'source_url' => 'https://www.intel.com/content/www/us/en/products/sku/132223/intel-core-i312100f-processor-12m-cache-up-to-4-30-ghz/specifications.html',
		),
		'core-i5-14600k' => array(
			'name' => 'Intel Core i5-14600K', 'brand' => 'Intel', 'generation' => '14th Gen (Raptor Lake Refresh)',
			'cores' => 14, 'threads' => 20, 'base_clock_ghz' => 3.5, 'boost_clock_ghz' => 5.3,
			'tdp_w' => 125, 'max_power_w' => 181, 'socket' => 'LGA 1700', 'score' => 72, 'release_year' => 2023,
			'source_url' => 'https://www.intel.com/content/www/us/en/products/sku/236799/intel-core-i5-processor-14600k-24m-cache-up-to-5-30-ghz/specifications.html',
		),
		'core-i7-14700k' => array(
			'name' => 'Intel Core i7-14700K', 'brand' => 'Intel', 'generation' => '14th Gen (Raptor Lake Refresh)',
			'cores' => 20, 'threads' => 28, 'base_clock_ghz' => 3.4, 'boost_clock_ghz' => 5.6,
			'tdp_w' => 125, 'max_power_w' => 253, 'socket' => 'LGA 1700', 'score' => 78, 'release_year' => 2023,
			'source_url' => 'https://www.intel.com/content/www/us/en/products/sku/236783/intel-core-i7-processor-14700k-33m-cache-up-to-5-60-ghz/specifications.html',
		),
		'core-ultra-5-245k' => array(
			'name' => 'Intel Core Ultra 5 245K', 'brand' => 'Intel', 'generation' => 'Arrow Lake (Core Ultra 200S)',
			'cores' => 14, 'threads' => 14, 'base_clock_ghz' => 4.2, 'boost_clock_ghz' => 5.2,
			'tdp_w' => 125, 'max_power_w' => 159, 'socket' => 'LGA 1851', 'score' => 68, 'release_year' => 2024,
			'source_url' => 'https://www.intel.com/content/www/us/en/products/sku/241067/intel-core-ultra-5-processor-245k-24m-cache-up-to-5-20-ghz/specifications.html',
		),
		'ryzen-7-9700x' => array(
			'name' => 'AMD Ryzen 7 9700X', 'brand' => 'AMD', 'generation' => 'Zen 5 (9000 Series)',
			'cores' => 8, 'threads' => 16, 'base_clock_ghz' => 3.8, 'boost_clock_ghz' => 5.5,
			'tdp_w' => 65, 'max_power_w' => 88, 'socket' => 'AM5', 'score' => 75, 'release_year' => 2024,
			'source_url' => 'https://www.amd.com/en/products/processors/desktops/ryzen/9000-series/amd-ryzen-7-9700x.html',
		),
		// D4 (Oct 2026, deep audit 1.11): 15 popular CPUs added so owners of
		// common parts can use the tools. Spec fields copied from each maker's
		// own page, checked 17 Oct 2026 (AMD: product page, or for the
		// 5700X3D and 3600 the AMD spec/support page, the same page type the
		// 5600 and 5700X use; Intel: the specifications page). AMD max_power_w
		// = default TDP x 1.35 (AMD's PPT rule, see the note above); Intel
		// max_power_w = Maximum Turbo Power. Scores on the same gaming index,
		// placed from public review averages: 7800X3D a step below the
		// 9800X3D, 9950X3D level with it, 7600X3D between them and the
		// non-X3D chips; 5800X3D about level with the 7600X, 5700X3D a little
		// lower; i9-14900K about 18% behind the 9800X3D (TechSpot, see the
		// file header); 265K close to the 285K; 9600X and 9900X with the
		// 7600X/9700X; 7500F just under the 7600; 14400F a step above the
		// 13400F; 12600K/12700K between the 13400F and the 13600K; 5800X a
		// little above the 5700X; 3600 (Zen 2) well below the 5600.
		'ryzen-7-7800x3d' => array(
			'name' => 'AMD Ryzen 7 7800X3D', 'brand' => 'AMD', 'generation' => 'Zen 4 (7000 Series, 3D V-Cache)',
			'cores' => 8, 'threads' => 16, 'base_clock_ghz' => 4.2, 'boost_clock_ghz' => 5.0,
			'tdp_w' => 120, 'max_power_w' => 162, 'socket' => 'AM5', 'score' => 87, 'release_year' => 2023,
			'source_url' => 'https://www.amd.com/en/products/processors/desktops/ryzen/7000-series/amd-ryzen-7-7800x3d.html',
		),
		'ryzen-5-7600x3d' => array(
			'name' => 'AMD Ryzen 5 7600X3D', 'brand' => 'AMD', 'generation' => 'Zen 4 (7000 Series, 3D V-Cache)',
			'cores' => 6, 'threads' => 12, 'base_clock_ghz' => 4.1, 'boost_clock_ghz' => 4.7,
			'tdp_w' => 65, 'max_power_w' => 88, 'socket' => 'AM5', 'score' => 80, 'release_year' => 2024,
			'source_url' => 'https://www.amd.com/en/products/processors/desktops/ryzen/7000-series/amd-ryzen-5-7600x3d.html',
		),
		'ryzen-7-5800x3d' => array(
			'name' => 'AMD Ryzen 7 5800X3D', 'brand' => 'AMD', 'generation' => 'Zen 3 (5000 Series, 3D V-Cache)',
			'cores' => 8, 'threads' => 16, 'base_clock_ghz' => 3.4, 'boost_clock_ghz' => 4.5,
			'tdp_w' => 105, 'max_power_w' => 142, 'socket' => 'AM4', 'score' => 70, 'release_year' => 2022,
			'source_url' => 'https://www.amd.com/en/products/processors/desktops/ryzen/5000-series/amd-ryzen-7-5800x3d.html',
		),
		'ryzen-7-5700x3d' => array(
			'name' => 'AMD Ryzen 7 5700X3D', 'brand' => 'AMD', 'generation' => 'Zen 3 (5000 Series, 3D V-Cache)',
			'cores' => 8, 'threads' => 16, 'base_clock_ghz' => 3.0, 'boost_clock_ghz' => 4.1,
			'tdp_w' => 105, 'max_power_w' => 142, 'socket' => 'AM4', 'score' => 66, 'release_year' => 2024,
			'source_url' => 'https://www.amd.com/en/support/downloads/drivers.html/processors/ryzen/ryzen-5000-series/amd-ryzen-7-5700x3d.html',
		),
		'ryzen-7-5800x' => array(
			'name' => 'AMD Ryzen 7 5800X', 'brand' => 'AMD', 'generation' => 'Zen 3 (5000 Series)',
			'cores' => 8, 'threads' => 16, 'base_clock_ghz' => 3.8, 'boost_clock_ghz' => 4.7,
			'tdp_w' => 105, 'max_power_w' => 142, 'socket' => 'AM4', 'score' => 57, 'release_year' => 2020,
			'source_url' => 'https://www.amd.com/en/products/processors/desktops/ryzen/5000-series/amd-ryzen-7-5800x.html',
		),
		'ryzen-5-3600' => array(
			'name' => 'AMD Ryzen 5 3600', 'brand' => 'AMD', 'generation' => 'Zen 2 (3000 Series)',
			'cores' => 6, 'threads' => 12, 'base_clock_ghz' => 3.6, 'boost_clock_ghz' => 4.2,
			'tdp_w' => 65, 'max_power_w' => 88, 'socket' => 'AM4', 'score' => 42, 'release_year' => 2019,
			'source_url' => 'https://www.amd.com/en/support/downloads/drivers.html/processors/ryzen/ryzen-3000-series/amd-ryzen-5-3600.html',
		),
		'ryzen-5-9600x' => array(
			'name' => 'AMD Ryzen 5 9600X', 'brand' => 'AMD', 'generation' => 'Zen 5 (9000 Series)',
			'cores' => 6, 'threads' => 12, 'base_clock_ghz' => 3.9, 'boost_clock_ghz' => 5.4,
			'tdp_w' => 65, 'max_power_w' => 88, 'socket' => 'AM5', 'score' => 72, 'release_year' => 2024,
			'source_url' => 'https://www.amd.com/en/products/processors/desktops/ryzen/9000-series/amd-ryzen-5-9600x.html',
		),
		'ryzen-5-7500f' => array(
			'name' => 'AMD Ryzen 5 7500F', 'brand' => 'AMD', 'generation' => 'Zen 4 (7000 Series)',
			'cores' => 6, 'threads' => 12, 'base_clock_ghz' => 3.7, 'boost_clock_ghz' => 5.0,
			'tdp_w' => 65, 'max_power_w' => 88, 'socket' => 'AM5', 'score' => 65, 'release_year' => 2023,
			'source_url' => 'https://www.amd.com/en/products/processors/desktops/ryzen/7000-series/amd-ryzen-5-7500f.html',
		),
		'ryzen-9-9900x' => array(
			'name' => 'AMD Ryzen 9 9900X', 'brand' => 'AMD', 'generation' => 'Zen 5 (9000 Series)',
			'cores' => 12, 'threads' => 24, 'base_clock_ghz' => 4.4, 'boost_clock_ghz' => 5.6,
			'tdp_w' => 120, 'max_power_w' => 162, 'socket' => 'AM5', 'score' => 75, 'release_year' => 2024,
			'source_url' => 'https://www.amd.com/en/products/processors/desktops/ryzen/9000-series/amd-ryzen-9-9900x.html',
		),
		'ryzen-9-9950x3d' => array(
			'name' => 'AMD Ryzen 9 9950X3D', 'brand' => 'AMD', 'generation' => 'Zen 5 (9000 Series, 3D V-Cache)',
			'cores' => 16, 'threads' => 32, 'base_clock_ghz' => 4.3, 'boost_clock_ghz' => 5.7,
			'tdp_w' => 170, 'max_power_w' => 230, 'socket' => 'AM5', 'score' => 94, 'release_year' => 2025,
			'source_url' => 'https://www.amd.com/en/products/processors/desktops/ryzen/9000-series/amd-ryzen-9-9950x3d.html',
		),
		'core-i5-14400f' => array(
			'name' => 'Intel Core i5-14400F', 'brand' => 'Intel', 'generation' => '14th Gen (Raptor Lake Refresh)',
			'cores' => 10, 'threads' => 16, 'base_clock_ghz' => 2.5, 'boost_clock_ghz' => 4.7,
			'tdp_w' => 65, 'max_power_w' => 148, 'socket' => 'LGA 1700', 'score' => 60, 'release_year' => 2024,
			'source_url' => 'https://www.intel.com/content/www/us/en/products/sku/236777/intel-core-i5-processor-14400f-20m-cache-up-to-4-70-ghz/specifications.html',
		),
		'core-i5-12600k' => array(
			'name' => 'Intel Core i5-12600K', 'brand' => 'Intel', 'generation' => '12th Gen (Alder Lake)',
			'cores' => 10, 'threads' => 16, 'base_clock_ghz' => 3.7, 'boost_clock_ghz' => 4.9,
			'tdp_w' => 125, 'max_power_w' => 150, 'socket' => 'LGA 1700', 'score' => 62, 'release_year' => 2021,
			'source_url' => 'https://www.intel.com/content/www/us/en/products/sku/134589/intel-core-i512600k-processor-20m-cache-up-to-4-90-ghz/specifications.html',
		),
		'core-i7-12700k' => array(
			'name' => 'Intel Core i7-12700K', 'brand' => 'Intel', 'generation' => '12th Gen (Alder Lake)',
			'cores' => 12, 'threads' => 20, 'base_clock_ghz' => 3.6, 'boost_clock_ghz' => 5.0,
			'tdp_w' => 125, 'max_power_w' => 190, 'socket' => 'LGA 1700', 'score' => 66, 'release_year' => 2021,
			'source_url' => 'https://www.intel.com/content/www/us/en/products/sku/134594/intel-core-i712700k-processor-25m-cache-up-to-5-00-ghz/specifications.html',
		),
		'core-i9-14900k' => array(
			'name' => 'Intel Core i9-14900K', 'brand' => 'Intel', 'generation' => '14th Gen (Raptor Lake Refresh)',
			'cores' => 24, 'threads' => 32, 'base_clock_ghz' => 3.2, 'boost_clock_ghz' => 6.0,
			'tdp_w' => 125, 'max_power_w' => 253, 'socket' => 'LGA 1700', 'score' => 81, 'release_year' => 2023,
			'source_url' => 'https://www.intel.com/content/www/us/en/products/sku/236773/intel-core-i9-processor-14900k-36m-cache-up-to-6-00-ghz/specifications.html',
		),
		'core-ultra-7-265k' => array(
			'name' => 'Intel Core Ultra 7 265K', 'brand' => 'Intel', 'generation' => 'Arrow Lake (Core Ultra 200S)',
			'cores' => 20, 'threads' => 20, 'base_clock_ghz' => 3.9, 'boost_clock_ghz' => 5.5,
			'tdp_w' => 125, 'max_power_w' => 250, 'socket' => 'LGA 1851', 'score' => 75, 'release_year' => 2024,
			'source_url' => 'https://www.intel.com/content/www/us/en/products/sku/241063/intel-core-ultra-7-processor-265k-30m-cache-up-to-5-50-ghz/specifications.html',
		),
	);

	return $cpus;
}

/**
 * Curated CPU/GPU pairs for the programmatic bottleneck cluster.
 * Only pairs with genuine, distinct search intent are generated as pages.
 */
function cs_get_bottleneck_pairs() {
	return array(
		array( 'cpu' => 'ryzen-5-5600', 'gpu' => 'rtx-3060' ),
		array( 'cpu' => 'ryzen-5-5600', 'gpu' => 'rtx-4060' ),
		array( 'cpu' => 'ryzen-5-5600', 'gpu' => 'rtx-4070' ),
		array( 'cpu' => 'ryzen-5-7600', 'gpu' => 'rtx-4060' ),
		array( 'cpu' => 'ryzen-5-7600', 'gpu' => 'rtx-4070' ),
		array( 'cpu' => 'ryzen-5-7600', 'gpu' => 'rtx-4070-super' ),
		array( 'cpu' => 'core-i5-12400f', 'gpu' => 'rtx-4060' ),
		array( 'cpu' => 'core-i5-12400f', 'gpu' => 'rtx-3060' ),
		array( 'cpu' => 'core-i5-13400f', 'gpu' => 'rtx-4070' ),
		array( 'cpu' => 'ryzen-7-5700x', 'gpu' => 'rtx-4070' ),
		array( 'cpu' => 'ryzen-7-7700x', 'gpu' => 'rtx-4080' ),
		array( 'cpu' => 'core-i7-13700k', 'gpu' => 'rtx-4080' ),
		array( 'cpu' => 'ryzen-9-7900x', 'gpu' => 'rtx-4090' ),
		array( 'cpu' => 'core-i9-13900k', 'gpu' => 'rtx-4090' ),
		array( 'cpu' => 'ryzen-5-7600x', 'gpu' => 'rx-7800-xt' ),
		// Batch 2 (Sep 2026): fills two gaps confirmed by live keyword research —
		// AMD-GPU pairings (only one existed before) and the RTX 50-series /
		// newest AMD flagships (none existed before), both checked against
		// live search results showing small, beatable niche sites (not big
		// hardware-media brands) ranking for "[cpu] bottleneck [gpu]" queries.
		array( 'cpu' => 'ryzen-5-5600', 'gpu' => 'rx-7600' ),
		array( 'cpu' => 'ryzen-5-7600', 'gpu' => 'rx-7800-xt' ),
		array( 'cpu' => 'core-i5-13400f', 'gpu' => 'rx-7700-xt' ),
		array( 'cpu' => 'core-i5-12400f', 'gpu' => 'rx-6600' ),
		array( 'cpu' => 'ryzen-5-5600x', 'gpu' => 'rtx-4060' ),
		array( 'cpu' => 'core-i5-13600k', 'gpu' => 'rtx-4070-super' ),
		array( 'cpu' => 'ryzen-7-7700x', 'gpu' => 'rtx-5070' ),
		array( 'cpu' => 'core-i7-13700k', 'gpu' => 'rtx-5080' ),
		array( 'cpu' => 'ryzen-9-7900x', 'gpu' => 'rtx-5090' ),
		array( 'cpu' => 'core-i9-13900k', 'gpu' => 'rtx-5090' ),
		array( 'cpu' => 'core-i5-13600k', 'gpu' => 'rtx-5070' ),
		array( 'cpu' => 'ryzen-9-7900x', 'gpu' => 'rx-7900-xt' ),
		array( 'cpu' => 'core-i9-13900k', 'gpu' => 'rx-7900-xtx' ),
		array( 'cpu' => 'ryzen-7-5700x', 'gpu' => 'rtx-4060' ),
		array( 'cpu' => 'core-i5-13400f', 'gpu' => 'rtx-4070-super' ),
		// Batch 3 (Phase 7, Oct 2026): budget and mid-range builds first,
		// mostly with the newly added parts (RTX 5060 / 5070 Ti, RX 9060 XT,
		// i3-12100F, i5-14600K, i7-14700K, Core Ultra 5 245K, Ryzen 7 9700X).
		// inc/combo-pages.php creates the missing cs_bottleneck posts from
		// this list; every page's text comes from the engine and
		// inc/bottleneck-content.php, so no pair gets hand-written copy.
		array( 'cpu' => 'core-i3-12100f', 'gpu' => 'rtx-3060' ),
		array( 'cpu' => 'core-i3-12100f', 'gpu' => 'rx-6600' ),
		array( 'cpu' => 'core-i3-12100f', 'gpu' => 'rtx-4060' ),
		array( 'cpu' => 'core-i3-12100f', 'gpu' => 'rtx-5060' ),
		array( 'cpu' => 'ryzen-5-5600', 'gpu' => 'rx-6600' ),
		array( 'cpu' => 'ryzen-5-5600', 'gpu' => 'rtx-5060' ),
		array( 'cpu' => 'ryzen-5-5600', 'gpu' => 'rx-9060-xt' ),
		array( 'cpu' => 'ryzen-5-5600', 'gpu' => 'rtx-5060-ti' ),
		array( 'cpu' => 'ryzen-5-5600', 'gpu' => 'rtx-5070' ),
		array( 'cpu' => 'core-i5-12400f', 'gpu' => 'rtx-5060' ),
		array( 'cpu' => 'core-i5-12400f', 'gpu' => 'rx-9060-xt' ),
		array( 'cpu' => 'core-i5-12400f', 'gpu' => 'rtx-5060-ti' ),
		array( 'cpu' => 'core-i5-12400f', 'gpu' => 'rtx-4070' ),
		array( 'cpu' => 'core-i5-13400f', 'gpu' => 'rtx-4060' ),
		array( 'cpu' => 'core-i5-13400f', 'gpu' => 'rtx-5060' ),
		array( 'cpu' => 'core-i5-13400f', 'gpu' => 'rtx-5060-ti' ),
		array( 'cpu' => 'core-i5-13400f', 'gpu' => 'rtx-5070' ),
		array( 'cpu' => 'ryzen-5-7600', 'gpu' => 'rtx-5060' ),
		array( 'cpu' => 'ryzen-5-7600', 'gpu' => 'rx-9060-xt' ),
		array( 'cpu' => 'ryzen-5-7600', 'gpu' => 'rtx-5060-ti' ),
		array( 'cpu' => 'ryzen-5-7600', 'gpu' => 'rtx-5070' ),
		array( 'cpu' => 'ryzen-5-7600', 'gpu' => 'rx-9070-xt' ),
		array( 'cpu' => 'ryzen-7-5700x', 'gpu' => 'rtx-5060-ti' ),
		array( 'cpu' => 'core-i5-14600k', 'gpu' => 'rtx-4070-super' ),
		array( 'cpu' => 'core-i5-14600k', 'gpu' => 'rtx-5070' ),
		array( 'cpu' => 'core-i5-14600k', 'gpu' => 'rtx-5070-ti' ),
		array( 'cpu' => 'core-i5-14600k', 'gpu' => 'rx-9070-xt' ),
		array( 'cpu' => 'core-ultra-5-245k', 'gpu' => 'rtx-5070' ),
		array( 'cpu' => 'core-ultra-5-245k', 'gpu' => 'rtx-5070-ti' ),
		array( 'cpu' => 'ryzen-7-9700x', 'gpu' => 'rtx-5070' ),
		array( 'cpu' => 'ryzen-7-9700x', 'gpu' => 'rtx-5070-ti' ),
		array( 'cpu' => 'ryzen-7-9700x', 'gpu' => 'rx-9070-xt' ),
		array( 'cpu' => 'ryzen-7-7700x', 'gpu' => 'rx-9070-xt' ),
		array( 'cpu' => 'core-i7-14700k', 'gpu' => 'rtx-5070-ti' ),
		array( 'cpu' => 'core-i7-14700k', 'gpu' => 'rtx-5080' ),
		array( 'cpu' => 'core-i7-14700k', 'gpu' => 'rtx-4090' ),
		array( 'cpu' => 'ryzen-7-9800x3d', 'gpu' => 'rx-9070-xt' ),
		array( 'cpu' => 'ryzen-7-9800x3d', 'gpu' => 'rtx-5070-ti' ),
		array( 'cpu' => 'ryzen-7-9800x3d', 'gpu' => 'rtx-5080' ),
		array( 'cpu' => 'ryzen-7-9800x3d', 'gpu' => 'rtx-5090' ),
		// Batch 4 (D4, Oct 2026, deep audit 5.5): pairs people search for
		// with the D4 parts: 5600X with current mid-range cards, X3D builds,
		// and popular older GPUs (RTX 3060 Ti / 3070 / 2060, GTX 1660 Super,
		// RX 6700 XT) with the budget CPUs they are usually paired with.
		array( 'cpu' => 'ryzen-5-5600x', 'gpu' => 'rtx-5060' ),
		array( 'cpu' => 'ryzen-5-5600x', 'gpu' => 'rtx-5070' ),
		array( 'cpu' => 'ryzen-5-5600x', 'gpu' => 'rx-9060-xt' ),
		array( 'cpu' => 'ryzen-5-5600x', 'gpu' => 'rtx-3060-ti' ),
		array( 'cpu' => 'ryzen-5-5600x', 'gpu' => 'rx-6700-xt' ),
		array( 'cpu' => 'ryzen-5-5600', 'gpu' => 'rtx-3060-ti' ),
		array( 'cpu' => 'ryzen-5-5600', 'gpu' => 'rtx-3070' ),
		array( 'cpu' => 'ryzen-5-5600', 'gpu' => 'rx-6700-xt' ),
		array( 'cpu' => 'ryzen-5-5600', 'gpu' => 'rx-6650-xt' ),
		array( 'cpu' => 'ryzen-5-5600', 'gpu' => 'arc-b580' ),
		array( 'cpu' => 'ryzen-5-5600', 'gpu' => 'rtx-5050' ),
		array( 'cpu' => 'ryzen-5-5600', 'gpu' => 'rtx-2060' ),
		array( 'cpu' => 'core-i5-12400f', 'gpu' => 'rtx-3060-ti' ),
		array( 'cpu' => 'core-i5-12400f', 'gpu' => 'rx-6700-xt' ),
		array( 'cpu' => 'core-i5-12400f', 'gpu' => 'arc-b580' ),
		array( 'cpu' => 'core-i5-12400f', 'gpu' => 'gtx-1660-super' ),
		array( 'cpu' => 'core-i5-12400f', 'gpu' => 'rtx-4060-ti' ),
		array( 'cpu' => 'core-i3-12100f', 'gpu' => 'gtx-1650' ),
		array( 'cpu' => 'core-i3-12100f', 'gpu' => 'arc-a750' ),
		array( 'cpu' => 'core-i3-12100f', 'gpu' => 'rtx-5050' ),
		array( 'cpu' => 'ryzen-5-3600', 'gpu' => 'rtx-3060' ),
		array( 'cpu' => 'ryzen-5-3600', 'gpu' => 'rtx-4060' ),
		array( 'cpu' => 'ryzen-5-3600', 'gpu' => 'gtx-1660-super' ),
		array( 'cpu' => 'ryzen-5-3600', 'gpu' => 'rx-6600' ),
		array( 'cpu' => 'ryzen-7-5700x3d', 'gpu' => 'rtx-4070-super' ),
		array( 'cpu' => 'ryzen-7-5700x3d', 'gpu' => 'rtx-5070' ),
		array( 'cpu' => 'ryzen-7-5700x3d', 'gpu' => 'rx-9070-xt' ),
		array( 'cpu' => 'ryzen-7-5700x3d', 'gpu' => 'rx-6800' ),
		array( 'cpu' => 'ryzen-7-5800x3d', 'gpu' => 'rtx-4080' ),
		array( 'cpu' => 'ryzen-7-5800x3d', 'gpu' => 'rtx-5070-ti' ),
		array( 'cpu' => 'ryzen-7-5800x3d', 'gpu' => 'rtx-4090' ),
		array( 'cpu' => 'ryzen-7-5800x', 'gpu' => 'rtx-3070' ),
		array( 'cpu' => 'ryzen-7-5800x', 'gpu' => 'rtx-3080' ),
		array( 'cpu' => 'ryzen-7-7800x3d', 'gpu' => 'rtx-4070-super' ),
		array( 'cpu' => 'ryzen-7-7800x3d', 'gpu' => 'rtx-5070' ),
		array( 'cpu' => 'ryzen-7-7800x3d', 'gpu' => 'rtx-5070-ti' ),
		array( 'cpu' => 'ryzen-7-7800x3d', 'gpu' => 'rx-9070-xt' ),
		array( 'cpu' => 'ryzen-7-7800x3d', 'gpu' => 'rtx-5080' ),
		array( 'cpu' => 'ryzen-7-7800x3d', 'gpu' => 'rtx-4090' ),
		array( 'cpu' => 'ryzen-5-7600x3d', 'gpu' => 'rtx-5070' ),
		array( 'cpu' => 'ryzen-5-7600x3d', 'gpu' => 'rx-9070' ),
		array( 'cpu' => 'ryzen-5-9600x', 'gpu' => 'rtx-5060-ti' ),
		array( 'cpu' => 'ryzen-5-9600x', 'gpu' => 'rtx-5070' ),
		array( 'cpu' => 'ryzen-5-9600x', 'gpu' => 'rx-9070' ),
		array( 'cpu' => 'ryzen-5-7500f', 'gpu' => 'rtx-4060-ti' ),
		array( 'cpu' => 'ryzen-5-7500f', 'gpu' => 'rtx-5060-ti' ),
		array( 'cpu' => 'ryzen-5-7500f', 'gpu' => 'rx-7800-xt' ),
		array( 'cpu' => 'ryzen-5-7600', 'gpu' => 'rx-7600-xt' ),
		array( 'cpu' => 'ryzen-5-7600', 'gpu' => 'rx-7900-gre' ),
		array( 'cpu' => 'ryzen-5-7600', 'gpu' => 'rx-9070' ),
		array( 'cpu' => 'core-i5-14400f', 'gpu' => 'rtx-4060-ti' ),
		array( 'cpu' => 'core-i5-14400f', 'gpu' => 'rtx-5060-ti' ),
		array( 'cpu' => 'core-i5-14400f', 'gpu' => 'rtx-5070' ),
		array( 'cpu' => 'core-i5-14400f', 'gpu' => 'rx-9060-xt' ),
		array( 'cpu' => 'core-i5-12600k', 'gpu' => 'rtx-3070' ),
		array( 'cpu' => 'core-i5-12600k', 'gpu' => 'rtx-4070' ),
		array( 'cpu' => 'core-i5-12600k', 'gpu' => 'rtx-4070-super' ),
		array( 'cpu' => 'core-i7-12700k', 'gpu' => 'rtx-3080' ),
		array( 'cpu' => 'core-i7-12700k', 'gpu' => 'rtx-4070-super' ),
		array( 'cpu' => 'core-i9-14900k', 'gpu' => 'rtx-4090' ),
		array( 'cpu' => 'core-i9-14900k', 'gpu' => 'rtx-5090' ),
		array( 'cpu' => 'core-ultra-7-265k', 'gpu' => 'rtx-5070-ti' ),
		array( 'cpu' => 'core-ultra-7-265k', 'gpu' => 'rtx-5080' ),
		array( 'cpu' => 'ryzen-9-9900x', 'gpu' => 'rtx-5080' ),
		array( 'cpu' => 'ryzen-9-9950x3d', 'gpu' => 'rtx-5090' ),
		// K4 (GSC keyword gap, 20 Oct 2026): searched in GSC ("i5 13th
		// 13600k rtx 5070 ti") with no page for it.
		array( 'cpu' => 'core-i5-13600k', 'gpu' => 'rtx-5070-ti' ),
	);
}
