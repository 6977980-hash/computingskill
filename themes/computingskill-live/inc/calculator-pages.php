<?php
/**
 * Calculator page text that matches the tools (deep audit D2, Oct 2026).
 *
 * The calculator pages' explanations live in the database and were written
 * before the October engine work, so several described methods the tools
 * don't use: scores "from core count, clock speeds and architecture" (the
 * scores are a gaming index placed from review averages), a CPU full load
 * "close to TDP" (the tool uses max turbo/boost power), an FPS example the
 * tool doesn't produce. Each fix below swaps one sentence or paragraph,
 * found by its ASCII opening and closing words, so the rest of the page and
 * any later edit Ali makes in wp-admin stay as they are. A pattern that no
 * longer matches simply does nothing.
 *
 * Runs at priority 8, before wptexturize and shortcodes, like
 * cs_methodology_engine_text() in inc/bottleneck.php.
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Replacement list per page slug: array( opening words, closing words, new text ).
 * The match runs from the opening to the closing words without crossing a
 * tag or shortcode bracket, so it stays inside one paragraph.
 */
function cs_calculator_page_fixes() {
	$score = __( 'ComputingSkill’s own gaming-performance index, placed from publicly reported third-party review averages', 'computingskill' );

	return array(
		// Audit 1.5 (D2), then 1.2 (D3) below.
		'pc-bottleneck-calculator' => array(
			array(
				'Instead, it uses published', 'relative performance score.',
				sprintf(
					/* translators: %s: link to the methodology page */
					__( 'Instead, it uses a gaming-performance score for each part: a GPU’s score stands for its average gaming throughput at high settings, and a CPU’s for the frame rate it can sustain when the graphics card isn’t the limit. These scores are ComputingSkill’s own index, placed from publicly reported third-party review averages rather than computed from core counts or clock speeds, and we don’t benchmark parts ourselves (%s).', 'computingskill' ),
					'<a href="' . esc_url( home_url( '/methodology/' ) ) . '">' . esc_html__( 'how the scores work', 'computingskill' ) . '</a>'
				),
			),
			array( 'spec-based performance tiers', 'performance tiers', __( 'gaming-performance scores', 'computingskill' ) ),
			array( 'This tool is a specs-based estimate', 'specs-based estimate', __( 'This tool is an estimate built from those scores', 'computingskill' ) ),
			array( 'which is also spec-based', 'benchmark-based', __( 'which also works from estimated scores rather than benchmark runs', 'computingskill' ) ),
			// D3 (audit 1.2): the result is one % with a clear meaning, not a
			// "CPU-bound vs GPU-bound split" where 50/50 is balanced. The quick
			// answer has a <strong> in the middle, so it is two replacements.
			array(
				'This calculator compares your CPU and GPU', 'at your chosen resolution, since',
				__( 'This calculator compares your CPU and GPU’s gaming-performance scores at 1080p, 1440p and 4K and tells you which part limits your frame rate at your chosen resolution, since', 'computingskill' ),
			),
			array(
				'as pixel count grows.', 'capping your frame rate.',
				__( 'as pixel count grows. When the CPU is the limit, the % is a CPU bottleneck: the share of your graphics card left idle (under 6% balanced, 6–14% mild, 15–29% noticeable, 30% or more severe). When the GPU is the limit, that is normal for gaming, and the % shows how much spare headroom the CPU has.', 'computingskill' ),
			),
			array(
				'The result is expressed as a rough percentage split', 'working as hard per frame.',
				__( 'The result names the part that limits your frame rate at the resolution you selected, with one percentage whose meaning depends on that part. If the CPU is the limit, the percentage is a CPU bottleneck: the share of the graphics card’s capacity left unused because the CPU can’t prepare frames fast enough, roughly 100 minus the GPU usage a monitoring overlay would show. Under 6% counts as balanced, 6–14% as mild, 15–29% as noticeable and 30% or more as severe; anything above 60% is shown as 60%+. If the GPU is the limit, the result says GPU-limited, which is the normal and usually desirable state for gaming, and the percentage is the CPU’s spare headroom: a faster CPU reads higher there, and only a faster graphics card would raise frame rates.', 'computingskill' ),
			),
			array(
				'to read this as a directional signal', 'running on the machine.',
				__( 'to read this as a directional signal, not a guarantee. Two systems with the same 20% CPU bottleneck reading can behave differently depending on the game, the settings used, and background software running on the machine; the Game type and Graphics settings inputs cover the biggest of those differences.', 'computingskill' ),
			),
			array(
				'rather than giving one fixed answer regardless of resolution.', 'rather than giving one fixed answer regardless of resolution.',
				__( 'rather than giving one fixed answer regardless of resolution. The Game type and Graphics settings inputs shift the balance the same way: esports titles and low settings lean on the CPU, CPU-heavy strategy and simulation games load it much more per frame, and cards with less VRAM than our GPU VRAM Calculator suggests, or a 128-bit memory bus, are scaled down further at 1440p and 4K. Ray tracing, upscaling and frame generation are not modelled.', 'computingskill' ),
			),
			array(
				'At 1080p, where CPU throughput matters more', 'squarely GPU work.',
				__( 'At 1080p the calculator shows a noticeable CPU bottleneck of about 20%: roughly a fifth of the RTX 4070’s capacity goes unused, so GPU usage would sit around 80%. At 1440p the pair is GPU-limited with about 15% CPU headroom, and at 4K it is GPU-limited with about 48% headroom. That is normal, because the RTX 4070 now has to render four times the pixels per frame, which is squarely GPU work.', 'computingskill' ),
			),
		),

		// Audit 1.5 and 1.10.
		'fps-calculator' => array(
			array( 'based on relative performance tiers', 'performance tiers', sprintf( __( 'based on %s,', 'computingskill' ), $score ) ),
			array(
				'The output is typically a frame-rate range', 'false sense of precision.',
				__( 'The output is an estimated average with a frame-rate range around it, for example a midpoint of 105 FPS with a range of roughly 90–121 FPS (the worked example below). The range reflects genuine variability across different games and scenes at the same hardware and settings, rather than a false sense of precision.', 'computingskill' ),
			),
			array(
				'The calculator starts from the same kind of relative performance tiering', 'relative performance levels',
				sprintf( __( 'The calculator starts from the same scores the bottleneck calculator uses, %s', 'computingskill' ), $score ),
			),
			array(
				'Take an RTX 4070 Super', 'toward the lower end.',
				__( 'Take an RTX 4070 Super paired with a Ryzen 5 7600 at 1440p on high settings. The calculator puts the graphics card’s ceiling at about 105 FPS and the CPU’s at about 142 FPS, so the GPU is the limit: the estimated average is 105 FPS, shown as a range of roughly 90–121 FPS. Lighter or more CPU-friendly titles can run above that range, and the most GPU-demanding titles sit toward the lower end. Pick Esports as the game type (or a game such as Valorant) and the same parts are estimated at about 316 FPS, because competitive shooters ask far less of the graphics card per frame.', 'computingskill' ),
			),
			array( 'This estimate is based on component specs', 'general scaling patterns', __( 'This estimate is based on the gaming-performance scores and general scaling patterns', 'computingskill' ) ),
			// D3 (audit 1.9): presets, game type and memory now change the result.
			array(
				'and then applies scaling factors for resolution and settings level', 'how much work the GPU does per frame)',
				__( 'and then applies scaling factors for resolution, settings level (Low runs about 2.1 times the frame rate of Ultra on the graphics card’s side), game type (esports titles run several times faster than a typical AAA game, while strategy and simulation games load the CPU much more) and VRAM (cards below our GPU VRAM Calculator’s minimum, or with a 128-bit memory bus, lose more at 1440p and 4K)', 'computingskill' ),
			),
		),

		// D3 (audit 1.13): the CPU decision now comes from the bottleneck engine.
		'pc-upgrade-calculator' => array(
			array(
				'even before touching the CPU or GPU.', 'even before touching the CPU or GPU.',
				__( 'even before touching the CPU or GPU. The suggestions are listed by how much each is likely to help, and for gaming the CPU is flagged only when the PC Bottleneck Calculator’s engine shows it leaving 15% or more of your graphics card unused at your target resolution, so the two tools always agree.', 'computingskill' ),
			),
		),

		// D3 (audit 3.8): one meaning of "bottleneck %" across the guides and the tool.
		'cpu-bottleneck-explained' => array(
			array(
				'more likely to be limiting your performance at a given resolution.', 'more likely to be limiting your performance at a given resolution.',
				__( 'more likely to be limiting your performance at a given resolution. Its CPU bottleneck percentage is the same signal seen from the other side: it estimates the share of the GPU left idle, so a 20% CPU bottleneck corresponds to GPU usage of roughly 80%. When the GPU is the limit (usage near 100%), the calculator calls the result GPU-limited, which is normal, and shows the CPU’s spare headroom instead.', 'computingskill' ),
			),
		),
		'gpu-bottleneck-explained' => array(
			array(
				'so you know where an upgrade would actually help before you buy one.', 'so you know where an upgrade would actually help before you buy one.',
				__( 'so you know where an upgrade would actually help before you buy one. It labels this case GPU-limited and treats it as normal; the percentage it shows there is how much spare headroom your CPU has, not a problem score. Only a CPU-side result is reported as a bottleneck, as the share of the GPU left idle.', 'computingskill' ),
			),
		),

		// Audit 1.6.
		'cpu-performance-calculator' => array(
			array(
				'by weighing core and thread count', 'than an older one.',
				sprintf( __( 'from its gaming-performance score, %s, so it ranks how fast a CPU runs games rather than its core count or clock speed.', 'computingskill' ), $score ),
			),
			array(
				'The estimate weighs core and thread count', 'several generations apart.',
				__( 'The tier comes from the CPU’s gaming-performance score: ComputingSkill’s own index of the frame rate a CPU can sustain in games when the graphics card isn’t the limit, placed from publicly reported third-party review averages (we don’t benchmark parts ourselves). A score of 77 or more is Flagship, 66 to 76 High-end, 54 to 65 Mid-range, and below 54 Entry-level. Cores, threads, clocks and TDP are shown next to the result for reference, but they don’t set the score, because they predict gaming speed poorly: the 8-core Ryzen 7 9800X3D outscores the 24-core Core i9-13900K in games.', 'computingskill' ),
			),
			array(
				'As a worked example, compare the AMD Ryzen 5 7600', 'real-world CPU performance.',
				__( 'As a worked example, compare the AMD Ryzen 5 7600 (6C/12T, Zen 4) with the Intel Core i5-12400F (6C/12T, Alder Lake). Both have six cores and twelve threads, but the Ryzen 5 7600 scores 67 (High-end) and the i5-12400F scores 55 (Mid-range), because published reviews show the 7600 running games noticeably faster. It is a reminder that core count alone is a poor proxy for real-world CPU performance.', 'computingskill' ),
			),
			array(
				'At the other end, a chip like the Intel Core i9-13900K', 'trading off against each other.',
				__( 'At the other end, the Intel Core i9-13900K (24C/32T) scores 78, a Flagship result well above the Ryzen 5 5600 (6C/12T) at 52 (Entry-level). It still sits behind the 8-core Ryzen 7 9800X3D at 95, whose large 3D V-Cache helps games far more than extra cores do.', 'computingskill' ),
			),
			array( 'This is a relative estimate built from published specifications', 'published specifications', __( 'This is a relative estimate built from published specifications and third-party review averages', 'computingskill' ) ),
		),

		// Audit 1.6.
		'gpu-performance-calculator' => array(
			array(
				'from its core count, boost clock, and architecture generation', 'raw core counts alone.',
				__( 'from its gaming-performance score, ComputingSkill’s own index of average gaming throughput at high settings (RTX 4090 = 100), placed from publicly reported third-party review averages. Because the score follows how cards perform in games rather than their core counts, NVIDIA and AMD cards can be compared on a level footing.', 'computingskill' ),
			),
			array(
				'The estimate combines core count', 'adjusted for a generation.',
				__( 'The result comes from two things stored for each card: its gaming-performance score, ComputingSkill’s own index of average rasterized gaming throughput at high settings with the RTX 4090 at 100, and a tier label (such as Mid-range / 1440p) that says what resolution the card suits. Both are placed from publicly reported third-party review averages; we don’t benchmark cards ourselves. Core counts and clocks aren’t compared directly, because they mean different things across architectures: a newer architecture can get more performance from a similar core count.', 'computingskill' ),
			),
			array(
				'As a worked example, compare the GeForce RTX 4070', 'entry/1080p tier.',
				__( 'As a worked example, compare the GeForce RTX 4070 (5888 CUDA cores, Ada Lovelace, 200W) with the GeForce RTX 3060 12GB (3584 CUDA cores, Ampere, 170W). The RTX 4070 scores 55 and is rated Mid-range / 1440p; the RTX 3060 scores 34 and is rated Entry / 1080p.', 'computingskill' ),
			),
			array(
				'Cross-brand comparisons work the same way', 'raw specification numbers.',
				__( 'Cross-brand comparisons work the same way: the Radeon RX 7800 XT (60 compute units, RDNA 3) and the GeForce RTX 4070 Super (7168 CUDA cores, Ada Lovelace) can’t be compared by core count, so the calculator compares their scores instead: 58 for the RX 7800 XT and 62 for the RTX 4070 Super, both rated Mid-range / 1440p.', 'computingskill' ),
			),
			array( 'This tool estimates a tier from published specifications', 'published specifications', __( 'This tool reports a tier and score placed from published specifications and third-party review averages', 'computingskill' ) ),
		),

		// Audit 1.18.
		'cpu-power-consumption-calculator' => array(
			array(
				'by scaling from its rated TDP', 'slightly above it.',
				__( 'using its rated TDP for idle (about 15%) and gaming or everyday use (about 70%), and the manufacturer’s maximum turbo/boost power for full load, since unlocked chips can draw far more than their TDP in sustained heavy work (the Core i9-13900K: 125W TDP, 253W maximum turbo power).', 'computingskill' ),
			),
			array(
				'The calculator starts from the CPU', 'power limit settings.',
				__( 'The calculator uses two manufacturer figures. Idle and typical load are scaled from the CPU’s rated TDP: about 15% of TDP at idle, because most cores drop to low-power states, and about 70% for gaming or everyday use. Full load uses the manufacturer’s maximum turbo/boost power instead (Intel’s Maximum Turbo Power, or AMD’s package power limit, PPT), because many boards let a CPU hold that limit through long all-core work, well above its TDP.', 'computingskill' ),
			),
			array(
				'As a worked example, take the Intel Core i5-13600K', 'above the base power limit.',
				__( 'As a worked example, take the Intel Core i5-13600K, rated at 125W TDP with 14 cores and 20 threads. The calculator puts it at about 19W at idle (15% of 125W) and about 88W in gaming or everyday use (70%). Under sustained full multi-core load, such as a long video export, it uses Intel’s 181W Maximum Turbo Power, well above the 125W TDP, because most boards let the chip hold that limit.', 'computingskill' ),
			),
			array(
				'Compare that to a lower-TDP chip like the AMD Ryzen 5 7600', 'flat number for every chip.',
				__( 'Compare that to the AMD Ryzen 5 7600 (65W TDP): about 10W at idle, 46W in gaming and 88W at full load (its PPT), roughly half the i5-13600K’s full-load figure. That is why the calculator reads each CPU’s own figures rather than using one flat number for every chip.', 'computingskill' ),
			),
			array( 'These figures are estimates derived from rated TDP', 'typical scaling behavior', __( 'These figures are estimates derived from rated TDP, maximum turbo/boost power and typical scaling behavior', 'computingskill' ) ),
		),

		// Audit 1.14 / 1.15: the tool now has the breakdown and budget field.
		'pc-build-cost-calculator' => array(
			array( 'what percentage of your budget went to each category', 'each category', __( 'what percentage of your PC parts spending went to each category', 'computingskill' ) ),
			array(
				'That breakdown is the more useful part', 'general build-planning guidance.',
				__( 'That breakdown is the more useful part for planning purposes: it lets you see at a glance whether, say, your GPU is eating an unusually large or small share of the total compared to general build-planning guidance. If you enter your budget, the result also shows how much is left or how far over you are. Peripherals and tax or shipping are added to the total but kept out of the percentage split, so they don’t skew the balance between parts.', 'computingskill' ),
			),
			array( 'It does not track sales, regional pricing differences, currency', 'currency', __( 'It does not track sales, regional pricing differences, currency conversion (the currency choice only changes the symbol)', 'computingskill' ) ),
		),
	);
}

function cs_calculator_page_text( $content ) {
	if ( ! is_singular( 'page' ) ) {
		return $content;
	}
	$fixes = cs_calculator_page_fixes();
	$slug  = get_post_field( 'post_name' );
	if ( empty( $fixes[ $slug ] ) ) {
		return $content;
	}
	foreach ( $fixes[ $slug ] as $fix ) {
		list( $open, $close, $text ) = $fix;
		if ( $open === $close || false !== strpos( $open, $close ) ) {
			// The closing words are part of the opening words: replace just that phrase.
			$pattern = '#' . preg_quote( $open, '#' ) . '#u';
		} else {
			$pattern = '#' . preg_quote( $open, '#' ) . '[^<\[\]]*?' . preg_quote( $close, '#' ) . '#u';
		}
		$content = preg_replace( $pattern, str_replace( array( '\\', '$' ), array( '\\\\', '\$' ), $text ), $content, 1 );
	}
	return $content;
}
add_filter( 'the_content', 'cs_calculator_page_text', 8 );

/* ------------------------------------------------------------------ */
/* "Last updated" on calculator pages (audit 1.22)                      */
/* ------------------------------------------------------------------ */

/**
 * The bylines still said September 23/24 although the engines and these
 * texts changed in October. When CS_CALC_PAGES_REV changes, the pages
 * listed below get their modified date set once to the moment the new code
 * first runs (byline, schema dateModified and sitemap lastmod all read it).
 * Bump it only in a push that really changes these tools or their text, and
 * list only the pages that changed.
 */
if ( ! defined( 'CS_CALC_PAGES_REV' ) ) {
	define( 'CS_CALC_PAGES_REV', '2026-10-17-d3' );
}

function cs_calc_pages_changed() {
	return array(
		// D3 text or result changes (D2's list ran with CS_CALC_PAGES_REV
		// 2026-10-17-d2 and is already done).
		'pc-bottleneck-calculator', 'fps-calculator', 'pc-upgrade-calculator', 'cpu-vs-gpu-calculator',
		'cpu-bottleneck-explained', 'gpu-bottleneck-explained', 'methodology',
	);
}

function cs_calc_pages_touch() {
	if ( wp_installing() || get_option( 'cs_calc_pages_rev' ) === CS_CALC_PAGES_REV ) {
		return;
	}
	if ( ! add_option( 'cs_calc_pages_lock', time(), '', 'no' ) ) {
		if ( time() - (int) get_option( 'cs_calc_pages_lock' ) > 300 ) {
			delete_option( 'cs_calc_pages_lock' );
		}
		return;
	}
	global $wpdb;
	foreach ( cs_calc_pages_changed() as $slug ) {
		$page = get_page_by_path( $slug );
		if ( ! $page || 'publish' !== $page->post_status ) {
			continue;
		}
		// Direct update: wp_update_post() would add a revision for a date change.
		$wpdb->update(
			$wpdb->posts,
			array( 'post_modified' => current_time( 'mysql' ), 'post_modified_gmt' => current_time( 'mysql', true ) ),
			array( 'ID' => $page->ID ),
			array( '%s', '%s' ),
			array( '%d' )
		);
		clean_post_cache( $page->ID );
	}
	if ( class_exists( '\RankMath\Sitemap\Cache' ) && method_exists( '\RankMath\Sitemap\Cache', 'invalidate_storage' ) ) {
		\RankMath\Sitemap\Cache::invalidate_storage();
	}
	// Cached copies still carry the old text and dates (no-op without LiteSpeed).
	do_action( 'litespeed_purge_all' );
	update_option( 'cs_calc_pages_rev', CS_CALC_PAGES_REV, false );
	delete_option( 'cs_calc_pages_lock' );
}
add_action( 'init', 'cs_calc_pages_touch', 41 );
