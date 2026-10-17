<?php
/**
 * Static, verified content index powering the Blog / Articles hub
 * (page-templates/template-blog.php).
 *
 * This mirrors the actual CATEGORY: header authored in every content
 * file at build time (build/content/pages/*.html) -- it is ground
 * truth for how each page/calculator was categorised when it was
 * written, not a guess. Kept as static data (rather than a live
 * taxonomy query) so this hub works immediately with zero extra
 * WXR/taxonomy setup, and never depends on a DB query that could
 * silently return nothing on a fresh install.
 *
 * To add a NEW article to this hub later: either add one line to
 * the relevant group below (slug/title/desc), or simply publish it
 * as a cs_calculator / cs_tool / cs_hardware / cs_guide /
 * cs_comparison post through the CPT system -- cs_get_blog_index()
 * automatically merges in any such published posts that aren't
 * already listed here, keyed by slug so nothing is ever duplicated.
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cs_content_index_groups() {
	return array(
		'calculators' => array(
			'label'   => __( 'PC Hardware Calculators', 'computingskill' ),
			'desc'    => __( 'Free, instant calculators for every stage of building, buying or upgrading a PC.', 'computingskill' ),
			'cpt'     => 'cs_calculator',
			'archive' => '/calculators/',
			'items'   => array(
				array( 'slug' => 'cpu-performance-calculator', 'title' => __( 'CPU Performance Calculator', 'computingskill' ), 'desc' => __( 'Estimate a CPU\'s relative performance tier from its cores, clock speeds, and generation to see where it stands for gaming or work.', 'computingskill' ) ),
				array( 'slug' => 'cpu-power-consumption-calculator', 'title' => __( 'CPU Power Consumption Calculator', 'computingskill' ), 'desc' => __( 'Estimate how much power your CPU actually draws at idle, typical, and full load compared to its rated TDP.', 'computingskill' ) ),
				array( 'slug' => 'cpu-vs-gpu-calculator', 'title' => __( 'CPU vs GPU Calculator', 'computingskill' ), 'desc' => __( 'Compare a specific CPU and GPU pairing to see which one is likely to hold the other back and by roughly how much.', 'computingskill' ) ),
				array( 'slug' => 'fps-calculator', 'title' => __( 'FPS Calculator', 'computingskill' ), 'desc' => __( 'Get an estimated frames-per-second range for a CPU and GPU pairing at your chosen resolution, settings and game type.', 'computingskill' ) ),
				array( 'slug' => 'gpu-performance-calculator', 'title' => __( 'GPU Performance Calculator', 'computingskill' ), 'desc' => __( 'Estimate a graphics card\'s relative performance tier from its cores, clock speed, and architecture to compare GPUs quickly.', 'computingskill' ) ),
				array( 'slug' => 'gpu-power-consumption-calculator', 'title' => __( 'GPU Power Consumption Calculator', 'computingskill' ), 'desc' => __( 'Estimate how much power your graphics card actually draws at idle, typical gaming, and full load compared to its rated TDP.', 'computingskill' ) ),
				array( 'slug' => 'gpu-vram-calculator', 'title' => __( 'GPU VRAM Calculator', 'computingskill' ), 'desc' => __( 'Estimate how much GPU VRAM you need for your resolution, settings, and ray tracing preferences before you buy a graphics card.', 'computingskill' ) ),
				array( 'slug' => 'bottleneck', 'title' => __( 'PC Bottleneck Comparisons', 'computingskill' ), 'desc' => __( 'Browse pre-built bottleneck estimates for common CPU and GPU pairings at 1080p, 1440p and 4K.', 'computingskill' ) ),
				array( 'slug' => 'pc-bottleneck-calculator', 'title' => __( 'PC Bottleneck Calculator', 'computingskill' ), 'desc' => __( 'Check whether your CPU or GPU is holding back your PC\'s performance, with results broken down by 1080p, 1440p, and 4K.', 'computingskill' ) ),
				array( 'slug' => 'pc-build-cost-calculator', 'title' => __( 'PC Build Cost Calculator', 'computingskill' ), 'desc' => __( 'Estimate the total cost of your PC build and see how your budget typically splits across components.', 'computingskill' ) ),
				array( 'slug' => 'pc-compatibility-checker', 'title' => __( 'PC Compatibility Checker', 'computingskill' ), 'desc' => __( 'Check CPU socket and BIOS support, RAM type, case fit for the motherboard, cooler and GPU, and PSU headroom and cables before you buy or build.', 'computingskill' ) ),
				array( 'slug' => 'pc-cooling-calculator', 'title' => __( 'PC Cooling Calculator', 'computingskill' ), 'desc' => __( 'Estimate how much cooling your CPU and GPU combined heat output needs, and whether air or AIO liquid cooling fits your build.', 'computingskill' ) ),
				array( 'slug' => 'pc-power-consumption-calculator', 'title' => __( 'PC Power Consumption Calculator', 'computingskill' ), 'desc' => __( 'Estimate your gaming PC\'s power draw and rough electricity cost, at idle and under load, from your actual component list.', 'computingskill' ) ),
				array( 'slug' => 'pc-upgrade-calculator', 'title' => __( 'PC Upgrade Calculator', 'computingskill' ), 'desc' => __( 'Find out whether upgrading your CPU, GPU, RAM, or storage will actually help most, based on your current parts.', 'computingskill' ) ),
				array( 'slug' => 'psu-calculator', 'title' => __( 'PSU Calculator', 'computingskill' ), 'desc' => __( 'Estimate the power supply wattage your PC build needs, with safe headroom for efficiency loss and GPU power spikes.', 'computingskill' ) ),
				array( 'slug' => 'ram-calculator', 'title' => __( 'RAM Calculator', 'computingskill' ), 'desc' => __( 'Find out how much RAM you actually need based on what you use your PC for, from light browsing to gaming and creative work.', 'computingskill' ) ),
			),
		),
		'storage-tools' => array(
			'label'   => __( 'Storage Tools', 'computingskill' ),
			'desc'    => __( 'Converters and estimators for storage capacity, drive lifespan and file sizes.', 'computingskill' ),
			'cpt'     => 'cs_tool',
			'archive' => '/storage-tools/',
			'items'   => array(
				array( 'slug' => 'data-storage-converter', 'title' => __( 'Data Storage Converter', 'computingskill' ), 'desc' => __( 'Convert between bits, bytes, KB, MB, GB, TB and PB, and understand decimal vs binary storage units.', 'computingskill' ) ),
				array( 'slug' => 'ssd-lifespan-calculator', 'title' => __( 'SSD Lifespan Calculator', 'computingskill' ), 'desc' => __( 'Estimate how many years your SSD should last based on its TBW rating and your daily write volume.', 'computingskill' ) ),
				array( 'slug' => 'ssd-tbw-calculator', 'title' => __( 'SSD TBW Calculator', 'computingskill' ), 'desc' => __( 'Understand what a drive\'s TBW rating actually means and use it to compare SSD endurance before you buy.', 'computingskill' ) ),
				array( 'slug' => 'storage-calculator', 'title' => __( 'Storage Calculator', 'computingskill' ), 'desc' => __( 'Estimate how much drive space a set of files or a media library needs, and whether it fits on your storage.', 'computingskill' ) ),
			),
		),
		'internet-tools' => array(
			'label'   => __( 'Internet Tools', 'computingskill' ),
			'desc'    => __( 'Converters and calculators for internet speed, bandwidth and transfer times.', 'computingskill' ),
			'cpt'     => 'cs_tool',
			'archive' => '/internet-tools/',
			'items'   => array(
				array( 'slug' => 'bandwidth-calculator', 'title' => __( 'Bandwidth Calculator', 'computingskill' ), 'desc' => __( 'Calculate the bandwidth needed to move a given amount of data in a target amount of time, for streaming or backups.', 'computingskill' ) ),
				array( 'slug' => 'download-time-calculator', 'title' => __( 'Download Time Calculator', 'computingskill' ), 'desc' => __( 'Estimate how long a download will take based on file size and connection speed, with real-world overhead factored in.', 'computingskill' ) ),
				array( 'slug' => 'file-transfer-time-calculator', 'title' => __( 'File Transfer Time Calculator', 'computingskill' ), 'desc' => __( 'Estimate transfer time for files over LAN, Wi-Fi, USB, or Ethernet, not just internet downloads.', 'computingskill' ) ),
				array( 'slug' => 'internet-data-usage-calculator', 'title' => __( 'Internet Data Usage Calculator', 'computingskill' ), 'desc' => __( 'Estimate your monthly internet data usage from streaming, browsing, gaming, and video calls to stay under a data cap.', 'computingskill' ) ),
				array( 'slug' => 'mb-s-to-mbps-converter', 'title' => __( 'MB/s to Mbps Converter', 'computingskill' ), 'desc' => __( 'Convert MB/s to Mbps quickly, useful for comparing a measured transfer speed against your advertised plan.', 'computingskill' ) ),
				array( 'slug' => 'mbps-to-mb-s-converter', 'title' => __( 'Mbps to MB/s Converter', 'computingskill' ), 'desc' => __( 'Convert Mbps to MB/s instantly and understand why ISPs advertise speed in megabits, not megabytes.', 'computingskill' ) ),
				array( 'slug' => 'upload-time-calculator', 'title' => __( 'Upload Time Calculator', 'computingskill' ), 'desc' => __( 'Estimate how long it will take to upload a file or backup based on your upload speed, which is often far slower than download.', 'computingskill' ) ),
			),
		),
		'display-tools' => array(
			'label'   => __( 'Display Tools', 'computingskill' ),
			'desc'    => __( 'Calculators for screen resolution, pixel density, aspect ratio and refresh rate.', 'computingskill' ),
			'cpt'     => 'cs_tool',
			'archive' => '/display-tools/',
			'items'   => array(
				array( 'slug' => 'aspect-ratio-calculator', 'title' => __( 'Aspect Ratio Calculator', 'computingskill' ), 'desc' => __( 'Simplify any resolution to its aspect ratio and identify common names like 16:9, 21:9, or 4:3 instantly.', 'computingskill' ) ),
				array( 'slug' => 'dpi-calculator', 'title' => __( 'DPI Calculator', 'computingskill' ), 'desc' => __( 'Calculate print resolution (dots per inch) from image pixel dimensions and print size to check if a photo will print sharp.', 'computingskill' ) ),
				array( 'slug' => 'monitor-size-calculator', 'title' => __( 'Monitor Size Calculator', 'computingskill' ), 'desc' => __( 'Work out a monitor\'s real width and height before buying, so it fits your desk and suits your viewing distance.', 'computingskill' ) ),
				array( 'slug' => 'ppi-calculator', 'title' => __( 'PPI Calculator', 'computingskill' ), 'desc' => __( 'Calculate a screen\'s pixels per inch from its diagonal size and resolution, and see how PPI differs from print DPI.', 'computingskill' ) ),
				array( 'slug' => 'refresh-rate-calculator', 'title' => __( 'Refresh Rate Calculator', 'computingskill' ), 'desc' => __( 'Convert monitor refresh rate (Hz) to frame time in milliseconds and see why higher Hz reduces input lag.', 'computingskill' ) ),
				array( 'slug' => 'resolution-calculator', 'title' => __( 'Resolution Calculator', 'computingskill' ), 'desc' => __( 'Look up a resolution\'s standard name (720p, 1080p, 1440p, 4K), megapixel count, and aspect ratio in one step.', 'computingskill' ) ),
			),
		),
		'hardware' => array(
			'label'   => __( 'Hardware', 'computingskill' ),
			'desc'    => __( 'Plain-English reference pages covering every major PC component.', 'computingskill' ),
			'cpt'     => 'cs_hardware',
			'archive' => '/hardware/',
			'items'   => array(
				array( 'slug' => 'cpu-cooler', 'title' => __( 'CPU Cooler: What It Is & How to Choose One', 'computingskill' ), 'desc' => __( 'Learn how CPU coolers work, air vs liquid cooling differences, and how to pick the right cooler for your processor.', 'computingskill' ) ),
				array( 'slug' => 'cpu', 'title' => __( 'CPU: What It Is & How to Choose One', 'computingskill' ), 'desc' => __( 'Learn what a CPU does, which specs actually matter, and how to pick the right processor for gaming, work, or a budget build.', 'computingskill' ) ),
				array( 'slug' => 'gpu', 'title' => __( 'GPU: What It Is & How to Choose One', 'computingskill' ), 'desc' => __( 'Understand what a graphics card actually does, which specs matter most, and how to pick the right GPU for your budget and resolution.', 'computingskill' ) ),
				array( 'slug' => 'hdd', 'title' => __( 'HDD: What It Is & When It Still Makes Sense', 'computingskill' ), 'desc' => __( 'Learn how hard drives work, how they compare to SSDs, and when an HDD is still the smart choice for storage.', 'computingskill' ) ),
				array( 'slug' => 'motherboard', 'title' => __( 'Motherboard: What It Is & How to Choose One', 'computingskill' ), 'desc' => __( 'Learn what a motherboard does, which specs and chipsets matter, and how to pick the right board for your build.', 'computingskill' ) ),
				array( 'slug' => 'pc-case', 'title' => __( 'PC Case: What It Is & How to Choose One', 'computingskill' ), 'desc' => __( 'Learn how PC cases affect airflow, compatibility, and noise, and how to pick the right case for your build.', 'computingskill' ) ),
				array( 'slug' => 'psu', 'title' => __( 'PSU (Power Supply): What It Is & How to Choose One', 'computingskill' ), 'desc' => __( 'Learn what a power supply does, which ratings actually matter, and how to pick the right wattage and efficiency for your PC.', 'computingskill' ) ),
				array( 'slug' => 'ram', 'title' => __( 'RAM: What It Is & How Much You Need', 'computingskill' ), 'desc' => __( 'Learn what RAM does, how much you actually need for gaming or work, and which memory specs matter when buying.', 'computingskill' ) ),
				array( 'slug' => 'ssd', 'title' => __( 'SSD: What It Is & How to Choose One', 'computingskill' ), 'desc' => __( 'Learn how SSDs work, the difference between NVMe and SATA, and how to choose the right drive for your PC.', 'computingskill' ) ),
				array( 'slug' => 'vram', 'title' => __( 'VRAM: What It Is & Why It Matters', 'computingskill' ), 'desc' => __( 'Learn what VRAM does, why it\'s different from system RAM, and how much video memory you actually need for gaming.', 'computingskill' ) ),
			),
		),
		'guides' => array(
			'label'   => __( 'Guides', 'computingskill' ),
			'desc'    => __( 'In-depth, practical guides for building, upgrading and troubleshooting a PC.', 'computingskill' ),
			'cpt'     => 'cs_guide',
			'archive' => '/guides/',
			'items'   => array(
				array( 'slug' => 'cpu-bottleneck-explained', 'title' => __( 'CPU Bottleneck Explained', 'computingskill' ), 'desc' => __( 'What a CPU bottleneck really is, when it happens, and how to tell your CPU is holding back your GPU instead of the other way around.', 'computingskill' ) ),
				array( 'slug' => 'gpu-bottleneck-explained', 'title' => __( 'GPU Bottleneck Explained', 'computingskill' ), 'desc' => __( 'What a GPU bottleneck really is, why it\'s common at high resolutions, and how it differs from a CPU bottleneck.', 'computingskill' ) ),
				array( 'slug' => 'how-much-ram-do-i-need-for-gaming-and-streaming', 'title' => __( 'How Much RAM Do You Need for Gaming and Streaming at the Same Time?', 'computingskill' ), 'desc' => __( 'How much RAM you actually need to game and live stream at once, with a real breakdown of where your memory goes and whether 16GB, 32GB or 64GB fits your setup.', 'computingskill' ) ),
				array( 'slug' => 'how-much-ram-do-i-need', 'title' => __( 'How Much RAM Do I Need?', 'computingskill' ), 'desc' => __( 'How much RAM you actually need for browsing, gaming, streaming, content creation, and dev work, with concrete GB recommendations.', 'computingskill' ) ),
				array( 'slug' => 'how-much-vram-do-i-need', 'title' => __( 'How Much VRAM Do I Need?', 'computingskill' ), 'desc' => __( 'How much GPU VRAM you need by resolution, settings, and ray tracing, with real GPU examples and concrete GB targets.', 'computingskill' ) ),
				array( 'slug' => 'how-to-choose-a-gaming-gpu', 'title' => __( 'How to Choose a Gaming GPU', 'computingskill' ), 'desc' => __( 'A balanced, practical framework for choosing a gaming GPU by budget, resolution, VRAM, and power needs, AMD vs NVIDIA.', 'computingskill' ) ),
				array( 'slug' => 'how-to-choose-a-psu', 'title' => __( 'How to Choose a PSU', 'computingskill' ), 'desc' => __( 'How to choose a PC power supply: wattage headroom, 80+ efficiency ratings, modular cables, and modern GPU connectors.', 'computingskill' ) ),
				array( 'slug' => 'how-to-improve-fps', 'title' => __( 'How to Improve FPS on Your PC', 'computingskill' ), 'desc' => __( 'Practical software and hardware fixes to improve FPS on your gaming PC, from graphics settings to component upgrades.', 'computingskill' ) ),
				array( 'slug' => 'how-to-upgrade-a-gaming-pc', 'title' => __( 'How to Upgrade a Gaming PC', 'computingskill' ), 'desc' => __( 'A step-by-step framework for upgrading a gaming PC: diagnosing bottlenecks, the right upgrade order, and compatibility checks.', 'computingskill' ) ),
			),
		),
		'comparisons' => array(
			'label'   => __( 'Comparisons', 'computingskill' ),
			'desc'    => __( 'Side-by-side breakdowns to help you choose between two technologies or formats.', 'computingskill' ),
			'cpt'     => 'cs_comparison',
			'archive' => '/comparisons/',
			'items'   => array(
				array( 'slug' => '1080p-vs-1440p-vs-4k', 'title' => __( '1080p vs 1440p vs 4K', 'computingskill' ), 'desc' => __( '1080p vs 1440p vs 4K compared honestly — visual difference, GPU power needed, ideal monitor size, and which resolution fits your setup.', 'computingskill' ) ),
				array( 'slug' => 'ddr4-vs-ddr5', 'title' => __( 'DDR4 vs DDR5', 'computingskill' ), 'desc' => __( 'DDR4 vs DDR5 RAM compared honestly — bandwidth, latency, price, platform compatibility, and whether DDR5 is worth it today.', 'computingskill' ) ),
				array( 'slug' => 'nvme-vs-sata', 'title' => __( 'NVMe vs SATA', 'computingskill' ), 'desc' => __( 'NVMe vs SATA SSDs compared honestly — real-world speed differences, where they actually matter, and compatibility basics like M.2 slots.', 'computingskill' ) ),
				array( 'slug' => 'ssd-vs-hdd', 'title' => __( 'SSD vs HDD', 'computingskill' ), 'desc' => __( 'A practical, technical comparison of SSDs and HDDs covering speed, price per GB, durability, and which one actually fits your use case.', 'computingskill' ) ),
			),
		),
	);
}

/**
 * Returns the same grouped structure as cs_content_index_groups(), but
 * with any live, published cs_calculator/cs_tool/cs_hardware/cs_guide/
 * cs_comparison posts merged in (matched into the group with the same
 * 'cpt' key) that aren't already present by slug. This is what lets the
 * Blog hub automatically pick up genuinely new content an editor
 * publishes later through the custom post types, without ever editing
 * this file.
 */
function cs_get_blog_index() {
	$groups = cs_content_index_groups();

	$known_slugs = array();
	foreach ( $groups as $group ) {
		foreach ( $group['items'] as $item ) {
			$known_slugs[ $item['slug'] ] = true;
		}
	}

	$cpts = array();
	foreach ( $groups as $key => $group ) {
		$cpts[ $group['cpt'] ][] = $key;
	}

	$live = new WP_Query( array(
		'post_type'      => array_keys( $cpts ),
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'no_found_rows'  => true,
		'orderby'        => 'date',
		'order'          => 'DESC',
	) );

	if ( $live->have_posts() ) {
		while ( $live->have_posts() ) {
			$live->the_post();
			$slug = get_post_field( 'post_name' );
			if ( isset( $known_slugs[ $slug ] ) ) {
				continue;
			}
			$post_type   = get_post_type();
			$target_keys = isset( $cpts[ $post_type ] ) ? $cpts[ $post_type ] : array();
			if ( empty( $target_keys ) ) {
				continue;
			}
			$groups[ $target_keys[0] ]['items'][] = array(
				'slug'  => $slug,
				'title' => get_the_title(),
				'desc'  => get_the_excerpt(),
			);
		}
		wp_reset_postdata();
	}

	return $groups;
}

/**
 * Flattens cs_get_blog_index() into one list, each item tagged with its
 * group key/label, for building the "Featured Articles" grid and the
 * ItemList schema.
 */
function cs_get_blog_index_flat() {
	$flat = array();
	foreach ( cs_get_blog_index() as $key => $group ) {
		foreach ( $group['items'] as $item ) {
			$item['group_key']   = $key;
			$item['group_label'] = $group['label'];
			$flat[]               = $item;
		}
	}
	return $flat;
}

/**
 * Hand-picked featured slugs shown at the top of the Blog hub -- chosen to
 * span every category rather than being a fabricated "most popular" or
 * "trending" claim. Falls back gracefully if a slug is ever removed.
 */
function cs_get_blog_featured_slugs() {
	return array(
		'pc-bottleneck-calculator',
		'psu-calculator',
		'ram-calculator',
		'fps-calculator',
		'how-much-ram-do-i-need-for-gaming-and-streaming',
		'gpu-vram-calculator',
		'ddr4-vs-ddr5',
		'cpu-bottleneck-explained',
		'ssd-lifespan-calculator',
		'how-to-upgrade-a-gaming-pc',
	);
}
