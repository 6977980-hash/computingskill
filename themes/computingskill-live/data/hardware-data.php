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
 * "score" fields are ComputingSkill's own simplified relative-performance
 * index (1-100) used ONLY to drive the estimate calculators on this site.
 * They are not benchmark results and are clearly labeled as estimates
 * everywhere they are shown to a user.
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
			'tier' => 'Mid-range / 1440p', 'score' => 61, 'release_year' => 2024,
			'source_url' => 'https://www.nvidia.com/en-us/geforce/graphics-cards/40-series/rtx-4070-family/',
		),
		'rtx-4080' => array(
			'name' => 'GeForce RTX 4080', 'brand' => 'NVIDIA', 'architecture' => 'Ada Lovelace',
			'vram_gb' => 16, 'memory_type' => 'GDDR6X', 'memory_bus' => 256, 'boost_clock_mhz' => 2505,
			'tdp_w' => 320, 'cores' => 9728, 'core_label' => 'CUDA Cores', 'recommended_psu_w' => 750,
			'tier' => 'High-end / 4K', 'score' => 75, 'release_year' => 2022,
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
			'tier' => 'Mid-range / 1440p', 'score' => 63, 'release_year' => 2025,
			'source_url' => 'https://www.nvidia.com/en-us/geforce/graphics-cards/50-series/rtx-5070-family/',
		),
		'rtx-5080' => array(
			'name' => 'GeForce RTX 5080', 'brand' => 'NVIDIA', 'architecture' => 'Blackwell',
			'vram_gb' => 16, 'memory_type' => 'GDDR7', 'memory_bus' => 256, 'boost_clock_mhz' => 2620,
			'tdp_w' => 360, 'cores' => 10752, 'core_label' => 'CUDA Cores', 'recommended_psu_w' => 850,
			'tier' => 'High-end / 4K', 'score' => 82, 'release_year' => 2025,
			'source_url' => 'https://www.nvidia.com/en-us/geforce/graphics-cards/50-series/rtx-5080/',
		),
		'rtx-5090' => array(
			'name' => 'GeForce RTX 5090', 'brand' => 'NVIDIA', 'architecture' => 'Blackwell',
			'vram_gb' => 32, 'memory_type' => 'GDDR7', 'memory_bus' => 512, 'boost_clock_mhz' => 2410,
			'tdp_w' => 575, 'cores' => 21760, 'core_label' => 'CUDA Cores', 'recommended_psu_w' => 1000,
			// Fixed during Sep 2026 audit: this used to be capped at 100, tying it
			// with the RTX 4090 below — meaning the bottleneck estimator and FPS
			// estimator both treated the two cards as identically fast, which is
			// wrong. Multi-outlet native-4K benchmark aggregates (no upscaling/
			// frame-gen) consistently show the 5090 well ahead of the 4090 —
			// e.g. dsogaming.com's 20-game native 4K test: "the NVIDIA RTX 5090
			// is 33% faster than the RTX 4090" (checked Sep 2026:
			// https://www.dsogaming.com/articles/nvidia-rtx-5090-vs-rtx-4090-native-4k-gaming-benchmarks/ ).
			// 100 * 1.33 ≈ 133, so the 4090 (still 100) keeps its exact prior
			// score/ranking relative to every other card — only the new
			// flagship's own score moved, which is the minimal change that
			// removes the tie. See also assets/js/calculators.js's score-max
			// display fix, needed because this is now the first score > 100.
			'tier' => 'Flagship / 4K', 'score' => 133, 'release_year' => 2025,
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
			'tier' => 'Mid-range / 1440p', 'score' => 56, 'release_year' => 2023,
			'source_url' => 'https://www.amd.com/en/products/graphics/desktops/radeon/7000-series/amd-radeon-rx-7700-xt.html',
		),
		'rx-7800-xt' => array(
			'name' => 'Radeon RX 7800 XT', 'brand' => 'AMD', 'architecture' => 'RDNA 3',
			'vram_gb' => 16, 'memory_type' => 'GDDR6', 'memory_bus' => 256, 'boost_clock_mhz' => 2430,
			'tdp_w' => 263, 'cores' => 60, 'core_label' => 'Compute Units', 'recommended_psu_w' => 700,
			'tier' => 'Mid-range / 1440p', 'score' => 62, 'release_year' => 2023,
			'source_url' => 'https://www.amd.com/en/products/graphics/desktops/radeon/7000-series/amd-radeon-rx-7800-xt.html',
		),
		'rx-7900-xt' => array(
			'name' => 'Radeon RX 7900 XT', 'brand' => 'AMD', 'architecture' => 'RDNA 3',
			'vram_gb' => 20, 'memory_type' => 'GDDR6', 'memory_bus' => 320, 'boost_clock_mhz' => 2400,
			'tdp_w' => 315, 'cores' => 84, 'core_label' => 'Compute Units', 'recommended_psu_w' => 750,
			'tier' => 'High-end / 4K', 'score' => 78, 'release_year' => 2022,
			'source_url' => 'https://www.amd.com/en/products/graphics/desktops/radeon/7000-series/amd-radeon-rx-7900xt.html',
		),
		'rx-7900-xtx' => array(
			'name' => 'Radeon RX 7900 XTX', 'brand' => 'AMD', 'architecture' => 'RDNA 3',
			'vram_gb' => 24, 'memory_type' => 'GDDR6', 'memory_bus' => 384, 'boost_clock_mhz' => 2500,
			'tdp_w' => 355, 'cores' => 96, 'core_label' => 'Compute Units', 'recommended_psu_w' => 800,
			'tier' => 'Flagship / 4K', 'score' => 88, 'release_year' => 2022,
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
			'tier' => 'Mid-range / 1440p', 'score' => 56, 'release_year' => 2025,
			'source_url' => 'https://www.nvidia.com/en-us/geforce/graphics-cards/50-series/rtx-5060-family/',
		),
		'rx-9070-xt' => array(
			'name' => 'Radeon RX 9070 XT', 'brand' => 'AMD', 'architecture' => 'RDNA 4',
			'vram_gb' => 16, 'memory_type' => 'GDDR6', 'memory_bus' => 256, 'boost_clock_mhz' => 2970,
			'tdp_w' => 304, 'cores' => 64, 'core_label' => 'Compute Units', 'recommended_psu_w' => 750,
			'tier' => 'High-end / 1440p-4K', 'score' => 84, 'release_year' => 2025,
			'source_url' => 'https://www.amd.com/en/products/graphics/desktops/radeon/9000-series/amd-radeon-rx-9070xt.html',
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
			'source_url' => 'https://www.amd.com/en/products/processors/desktops/ryzen/5000-series/amd-ryzen-5-5600.html',
		),
		'ryzen-5-5600x' => array(
			'name' => 'AMD Ryzen 5 5600X', 'brand' => 'AMD', 'generation' => 'Zen 3 (5000 Series)',
			'cores' => 6, 'threads' => 12, 'base_clock_ghz' => 3.7, 'boost_clock_ghz' => 4.6,
			'tdp_w' => 65, 'max_power_w' => 88, 'socket' => 'AM4', 'score' => 55, 'release_year' => 2020,
			'source_url' => 'https://www.amd.com/en/products/processors/desktops/ryzen/5000-series/amd-ryzen-5-5600x.html',
		),
		'ryzen-7-5700x' => array(
			'name' => 'AMD Ryzen 7 5700X', 'brand' => 'AMD', 'generation' => 'Zen 3 (5000 Series)',
			'cores' => 8, 'threads' => 16, 'base_clock_ghz' => 3.4, 'boost_clock_ghz' => 4.6,
			'tdp_w' => 65, 'max_power_w' => 88, 'socket' => 'AM4', 'score' => 61, 'release_year' => 2022,
			'source_url' => 'https://www.amd.com/en/products/processors/desktops/ryzen/5000-series/amd-ryzen-7-5700x.html',
		),
		'ryzen-5-7600' => array(
			'name' => 'AMD Ryzen 5 7600', 'brand' => 'AMD', 'generation' => 'Zen 4 (7000 Series)',
			'cores' => 6, 'threads' => 12, 'base_clock_ghz' => 3.8, 'boost_clock_ghz' => 5.1,
			'tdp_w' => 65, 'max_power_w' => 88, 'socket' => 'AM5', 'score' => 66, 'release_year' => 2023,
			'source_url' => 'https://www.amd.com/en/products/processors/desktops/ryzen/7000-series/amd-ryzen-5-7600.html',
		),
		'ryzen-5-7600x' => array(
			'name' => 'AMD Ryzen 5 7600X', 'brand' => 'AMD', 'generation' => 'Zen 4 (7000 Series)',
			'cores' => 6, 'threads' => 12, 'base_clock_ghz' => 4.7, 'boost_clock_ghz' => 5.3,
			'tdp_w' => 105, 'max_power_w' => 142, 'socket' => 'AM5', 'score' => 68, 'release_year' => 2022,
			'source_url' => 'https://www.amd.com/en/products/processors/desktops/ryzen/7000-series/amd-ryzen-5-7600x.html',
		),
		'ryzen-7-7700x' => array(
			'name' => 'AMD Ryzen 7 7700X', 'brand' => 'AMD', 'generation' => 'Zen 4 (7000 Series)',
			'cores' => 8, 'threads' => 16, 'base_clock_ghz' => 4.5, 'boost_clock_ghz' => 5.4,
			'tdp_w' => 105, 'max_power_w' => 142, 'socket' => 'AM5', 'score' => 74, 'release_year' => 2022,
			'source_url' => 'https://www.amd.com/en/products/processors/desktops/ryzen/7000-series/amd-ryzen-7-7700x.html',
		),
		// Boost clock corrected during Sep 2026 audit: was listed as 5.4GHz;
		// AMD's own product page (amd.com/en/products/processors/desktops/
		// ryzen/7000-series/amd-ryzen-9-7900x.html, checked Sep 2026) states
		// "Up to 5.6 GHz". Everything else on this row was already correct.
		'ryzen-9-7900x' => array(
			'name' => 'AMD Ryzen 9 7900X', 'brand' => 'AMD', 'generation' => 'Zen 4 (7000 Series)',
			'cores' => 12, 'threads' => 24, 'base_clock_ghz' => 4.7, 'boost_clock_ghz' => 5.6,
			'tdp_w' => 170, 'max_power_w' => 230, 'socket' => 'AM5', 'score' => 85, 'release_year' => 2022,
			'source_url' => 'https://www.amd.com/en/products/processors/desktops/ryzen/7000-series/amd-ryzen-9-7900x.html',
		),
		'core-i5-12400f' => array(
			'name' => 'Intel Core i5-12400F', 'brand' => 'Intel', 'generation' => '12th Gen (Alder Lake)',
			'cores' => 6, 'threads' => 12, 'base_clock_ghz' => 2.5, 'boost_clock_ghz' => 4.4,
			'tdp_w' => 65, 'max_power_w' => 117, 'socket' => 'LGA 1700', 'score' => 58, 'release_year' => 2022,
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
			'tdp_w' => 65, 'max_power_w' => 148, 'socket' => 'LGA 1700', 'score' => 65, 'release_year' => 2023,
			'source_url' => 'https://www.intel.com/content/www/us/en/products/sku/230501/intel-core-i513400f-processor-20m-cache-up-to-4-60-ghz/specifications.html',
		),
		'core-i5-13600k' => array(
			'name' => 'Intel Core i5-13600K', 'brand' => 'Intel', 'generation' => '13th Gen (Raptor Lake)',
			'cores' => 14, 'threads' => 20, 'base_clock_ghz' => 3.5, 'boost_clock_ghz' => 5.1,
			'tdp_w' => 125, 'max_power_w' => 181, 'socket' => 'LGA 1700', 'score' => 77, 'release_year' => 2022,
			'source_url' => 'https://www.intel.com/content/www/us/en/products/sku/230493/intel-core-i513600k-processor-24m-cache-up-to-5-10-ghz/specifications.html',
		),
		'core-i7-13700k' => array(
			'name' => 'Intel Core i7-13700K', 'brand' => 'Intel', 'generation' => '13th Gen (Raptor Lake)',
			'cores' => 16, 'threads' => 24, 'base_clock_ghz' => 3.4, 'boost_clock_ghz' => 5.4,
			'tdp_w' => 125, 'max_power_w' => 253, 'socket' => 'LGA 1700', 'score' => 84, 'release_year' => 2022,
			'source_url' => 'https://www.intel.com/content/www/us/en/products/sku/230500/intel-core-i713700k-processor-30m-cache-up-to-5-40-ghz/specifications.html',
		),
		'core-i9-13900k' => array(
			'name' => 'Intel Core i9-13900K', 'brand' => 'Intel', 'generation' => '13th Gen (Raptor Lake)',
			'cores' => 24, 'threads' => 32, 'base_clock_ghz' => 3.0, 'boost_clock_ghz' => 5.8,
			'tdp_w' => 125, 'max_power_w' => 253, 'socket' => 'LGA 1700', 'score' => 92, 'release_year' => 2022,
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
			'tdp_w' => 120, 'max_power_w' => 162, 'socket' => 'AM5', 'score' => 78, 'release_year' => 2024,
			'source_url' => 'https://www.amd.com/en/products/processors/desktops/ryzen/9000-series/amd-ryzen-7-9800x3d.html',
		),
		'core-ultra-9-285k' => array(
			'name' => 'Intel Core Ultra 9 285K', 'brand' => 'Intel', 'generation' => 'Arrow Lake (Core Ultra 200S)',
			'cores' => 24, 'threads' => 24, 'base_clock_ghz' => 3.7, 'boost_clock_ghz' => 5.7,
			'tdp_w' => 125, 'max_power_w' => 250, 'socket' => 'LGA 1851', 'score' => 93, 'release_year' => 2024,
			'source_url' => 'https://www.intel.com/content/www/us/en/products/sku/241060/intel-core-ultra-9-processor-285k-36m-cache-up-to-5-70-ghz/specifications.html',
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
	);
}
