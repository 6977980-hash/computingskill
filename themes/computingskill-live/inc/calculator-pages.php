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
			// D10 (audit 5.7): the tool now has upscaling and frame generation.
			array(
				'This calculator also does not account for upscaling', 'beyond a single FPS number.',
				sprintf(
					/* translators: %s: link */
					__( 'Upscaling (DLSS, FSR, XeSS) and frame generation are optional inputs: they apply typical gains, need the game to support them, and frame generation raises the frame rate you see without making the game respond faster (%s). The calculator does not account for ray tracing, monitor refresh rate caps, or frame-time consistency (which affects how smooth a given average FPS actually feels), all of which meaningfully affect the experience beyond a single FPS number.', 'computingskill' ),
					'<a href="' . esc_url( home_url( '/dlss-fsr-frame-generation-explained/' ) ) . '">' . esc_html__( 'how they work', 'computingskill' ) . '</a>'
				),
			),
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
			// K3: "How to fix" is what the searches ask (steps in inc/k3-pages.php).
			// U1: moved here; a second 'cpu-bottleneck-explained' key further
			// down replaced this whole list, so the D3 sentence above was lost.
			array( 'What to Do About a CPU Bottleneck', 'What to Do About a CPU Bottleneck', __( 'How to Fix a CPU Bottleneck', 'computingskill' ) ),
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
				sprintf( __( 'from its gaming score on %s, so it ranks how fast a CPU runs games rather than its core count or clock speed.', 'computingskill' ), $score ),
			),
			// E3 (ONP-02): the intro and two FAQ answers still described the
			// old specs-based method.
			array(
				'This calculator takes a CPU', 'without needing to memorize benchmark charts.',
				__( 'This calculator shows a CPU’s gaming-performance score and the tier it falls in, so you can quickly gauge whether a chip is a budget, mid-range, upper mid-range or high-end choice for gaming without needing to memorize benchmark charts.', 'computingskill' ),
			),
			array(
				'Clock speed and architecture generation both affect performance', 'differ substantially in real performance.',
				__( 'Because the score follows how fast each CPU runs games in published reviews, not its core count. Architecture, cache size and clock speed all change how much work each core gets done, so two six-core chips can land far apart: the Ryzen 5 7600 scores 67 and the Core i5-12400F 55.', 'computingskill' ),
			),
			array(
				'This calculator produces a general estimate based on published specifications and known generational performance patterns.', 'known generational performance patterns.',
				__( 'The score is ComputingSkill’s own gaming index, placed from publicly reported third-party review averages, not the result of running Cinebench, PassMark or any other benchmark ourselves.', 'computingskill' ),
			),
			array(
				'The estimate weighs core and thread count', 'several generations apart.',
				__( 'The tier comes from the CPU’s gaming-performance score: ComputingSkill’s own index of the frame rate a CPU can sustain in games when the graphics card isn’t the limit, placed from publicly reported third-party review averages (we don’t benchmark parts ourselves). The tier follows where a chip sits for gamers today: a score of 80 or more is High-end, 70 to 79 Upper mid-range, 60 to 69 Mid-range, and below 60 Budget. The result also shows the score as a share of the fastest gaming CPU we list (the Ryzen 7 9800X3D, score 95), so the Ryzen 5 5600 at 52 reads as about 55% of it. Cores, threads, clocks and TDP are shown next to the result for reference, but they don’t set the score, because they predict gaming speed poorly: the 8-core Ryzen 7 9800X3D outscores the 24-core Core i9-13900K in games.', 'computingskill' ),
			),
			array(
				'As a worked example, compare the AMD Ryzen 5 7600', 'real-world CPU performance.',
				__( 'As a worked example, compare the AMD Ryzen 5 7600 (6C/12T, Zen 4) with the Intel Core i5-12400F (6C/12T, Alder Lake). Both have six cores and twelve threads, but the Ryzen 5 7600 scores 67 (Mid-range) and the i5-12400F scores 55 (Budget), because published reviews show the 7600 running games noticeably faster. It is a reminder that core count alone is a poor proxy for real-world CPU performance.', 'computingskill' ),
			),
			array(
				'At the other end, a chip like the Intel Core i9-13900K', 'trading off against each other.',
				__( 'At the other end, the Intel Core i9-13900K (24C/32T) scores 78, an Upper mid-range result for gaming well above the Ryzen 5 5600 (6C/12T) at 52 (Budget). It still sits behind the 8-core Ryzen 7 9800X3D at 95, whose large 3D V-Cache helps games far more than extra cores do.', 'computingskill' ),
			),
			array( 'This is a relative estimate built from published specifications', 'published specifications', __( 'This is a relative estimate built from published specifications and third-party review averages', 'computingskill' ) ),
			// U1 (U-10): the gaming tiers were renamed (budget, mid-range, upper
			// mid-range, high-end) and the score is shown as a share of the
			// fastest gaming CPU.
			array( 'relative performance tier', 'from its gaming score', __( 'gaming tier (budget, mid-range, upper mid-range or high-end) from its gaming score', 'computingskill' ) ),
			array( 'such as entry-level, mid-range, high-end, or flagship) along with an approximate score', 'not an absolute unit of measurement.', __( 'budget, mid-range, upper mid-range or high-end for gaming) along with the score as a share of the fastest gaming CPU we list, meant for comparison against other CPUs, not an absolute unit of measurement.', 'computingskill' ) ),
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
			// E3 (ONP-02): three FAQ answers still described the old method.
			array(
				'Because the calculator adjusts for architecture generation and efficiency', 'use different core designs.',
				__( 'The score follows how each card performs in games in published third-party reviews rather than its core count, so NVIDIA, AMD and Intel cards sit on one scale, with the RTX 4090 at 100.', 'computingskill' ),
			),
			array(
				'A newer, more efficient architecture with fewer cores', 'as much as raw specifications.',
				__( 'So a newer card with fewer cores can run games faster than an older one with more. The calculator doesn’t count cores at all: each card’s score is placed from how it performs in published reviews, which already takes that into account.', 'computingskill' ),
			),
			array(
				'It reflects general architecture-level improvements', 'precise ray tracing benchmark.',
				__( 'No. The score stands for average rasterized gaming performance at high settings, without ray tracing, so cards with stronger ray tracing hardware can pull further ahead in ray-traced games. For ray-tracing-heavy use, treat the tier as a general guide rather than a ray tracing benchmark.', 'computingskill' ),
			),
		),

		// Audit 1.18.
		'cpu-power-consumption-calculator' => array(
			array(
				'by scaling from its rated TDP', 'slightly above it.',
				// D5 (audit 1.19): gaming is now a share of max turbo/boost power.
				__( 'using its rated TDP for idle (about 15%), about 55% of the manufacturer’s maximum turbo/boost power for gaming or everyday use (45% for AMD X3D chips), and that maximum power for full load, since unlocked chips can draw far more than their TDP in sustained heavy work (the Core i9-13900K: 125W TDP, 253W maximum turbo power).', 'computingskill' ),
			),
			array(
				'The calculator starts from the CPU', 'power limit settings.',
				__( 'The calculator uses two manufacturer figures. Idle is scaled from the CPU’s rated TDP: about 15%, because most cores drop to low-power states. Gaming and everyday use take about 55% of the manufacturer’s maximum turbo/boost power (Intel’s Maximum Turbo Power, or AMD’s package power limit, PPT), or 45% for AMD X3D chips, whose extra cache keeps clocks and power lower; that tracks review measurements better than the TDP, which over-states X3D chips and under-states unlocked Intel K chips. Full load uses the maximum turbo/boost power itself, because many boards let a CPU hold that limit through long all-core work, well above its TDP.', 'computingskill' ),
			),
			array(
				'As a worked example, take the Intel Core i5-13600K', 'above the base power limit.',
				__( 'As a worked example, take the Intel Core i5-13600K, rated at 125W TDP with 14 cores and 20 threads. The calculator puts it at about 19W at idle (15% of 125W) and about 100W in gaming or everyday use (55% of its 181W Maximum Turbo Power). Under sustained full multi-core load, such as a long video export, it uses Intel’s 181W Maximum Turbo Power, well above the 125W TDP, because most boards let the chip hold that limit.', 'computingskill' ),
			),
			array(
				'Compare that to a lower-TDP chip like the AMD Ryzen 5 7600', 'flat number for every chip.',
				__( 'Compare that to the AMD Ryzen 5 7600 (65W TDP): about 10W at idle, 48W in gaming and 88W at full load (its PPT), roughly half the i5-13600K’s full-load figure. That is why the calculator reads each CPU’s own figures rather than using one flat number for every chip.', 'computingskill' ),
			),
			array( 'These figures are estimates derived from rated TDP', 'typical scaling behavior', __( 'These figures are estimates derived from rated TDP, maximum turbo/boost power and typical scaling behavior', 'computingskill' ) ),
		),

		// D5 (Oct 2026, audit 1.16): the checker now also covers BIOS
		// support, board size, cooler height and the 16-pin GPU cable.
		'pc-compatibility-checker' => array(
			array(
				'and flags the classic compatibility problems', 'without enough headroom.',
				__( 'and flags the classic compatibility problems: wrong socket, a motherboard that needs a BIOS update for your CPU, wrong RAM type, a motherboard, cooler or GPU too big for the case, a PSU without enough headroom, or a 16-pin graphics card without the right PSU cable.', 'computingskill' ),
			),
			array(
				'This checker compares your chosen CPU, motherboard', 'every part will fit.',
				__( 'This checker compares your chosen CPU, motherboard, RAM, GPU, case and PSU on specific compatibility points: CPU-to-motherboard socket match, whether the board’s chipset needs a BIOS update for your CPU, RAM type (DDR4 vs DDR5), motherboard size, cooler height and GPU length against the case, PSU wattage headroom, and whether the PSU has the 16-pin 12V-2x6 cable newer NVIDIA cards use. It flags a pass, a step to check, or a specific mismatch for each. Checks you leave on “Not sure” are skipped. A pass means those particular checks found no conflict, not a full guarantee every part will fit.', 'computingskill' ),
			),
			array(
				'For PSU, it checks the GPU', 'falls short of it.',
				__( 'For PSU, it checks the GPU’s TDP and the rest of your component list against the PSU’s rated wattage, using the same headroom logic as the PSU Calculator, and never asks for less than the GPU manufacturer’s recommended PSU wattage. If you pick the motherboard’s chipset, it also says whether that board needs a BIOS update for your CPU, following AMD’s and Intel’s support lists: Ryzen 5000 on 300- and 400-series AM4 boards, Ryzen 9000 on 600-series AM5 boards, and 13th or 14th Gen Intel on 600-series LGA 1700 boards all need one. Optional inputs check the motherboard size (ATX, Micro-ATX, Mini-ITX, E-ATX) against the largest board the case takes, an air cooler’s height against the case’s cooler limit, and the GPU’s power plug against the PSU’s cables. Every RTX 4080, 4090, 5080 and 5090 uses the 16-pin 12V-2x6 plug, so for those cards the checker raises the cable question even if you leave it blank.', 'computingskill' ),
			),
			array(
				'It also can', 'this tool does not check.',
				__( 'It also can’t check every physical constraint that matters in a real build: radiator mounting space for liquid coolers, RAM height under a large air cooler, front-panel connector compatibility and cable routing space are real concerns this tool does not check, and its BIOS note says which board series need an update, not the exact BIOS version for your model.', 'computingskill' ),
			),
			array( 'Why isn', 'compatibility here?', __( 'Does this checker check my CPU cooler?', 'computingskill' ) ),
			array(
				'Cooler compatibility depends on case height clearance', 'spec sheet separately.',
				__( 'Partly. Enter your air cooler’s height and the case’s maximum cooler height, and it checks that the cooler fits. It can’t check radiator mounting space for an AIO liquid cooler, or RAM clearance under a large air cooler, because those depend on the exact case and cooler model, so check them against both spec sheets.', 'computingskill' ),
			),
		),

		// D5 (audit 1.19): CPU gaming draw from max turbo/boost power, and
		// PSU efficiency loss included in the wall figures.
		'pc-power-consumption-calculator' => array(
			array(
				'combining baseline draw from the motherboard', 'near-TDP draw under load',
				__( 'combining baseline draw from the motherboard, RAM, storage, and fans with the CPU’s typical gaming draw and the GPU’s rated board power, then adding the power supply’s own efficiency loss', 'computingskill' ),
			),
			array(
				'Load power is estimated by taking the CPU and GPU close to their rated TDP figures', 'baseline system draw on top.',
				__( 'Load power takes the GPU at its rated board power and the CPU at its typical gaming draw: about 55% of the manufacturer’s maximum turbo/boost power, or 45% for AMD X3D chips, which run cooler in games. That is closer to review measurements than the rated TDP, which over-states X3D chips and under-states unlocked Intel K chips. The same baseline system draw is added on top. Both figures are then divided by the power supply’s efficiency (about 88% under gaming load and 80% at idle), because the wall socket, and your bill, pays for the PSU’s losses too.', 'computingskill' ),
			),
			array(
				'Consider a system idling around', 'higher-power build.',
				__( 'Take a Ryzen 5 7600 and an RTX 4070 with the default two RAM sticks, two drives and three fans. While gaming, the parts draw about 329W (the 7600 at about 48W, 55% of its 88W PPT; the RTX 4070 at 200W; 81W for the rest), which is about 374W from the wall. At idle the parts draw about 64W, or 80W from the wall. If you game for 3 hours a day and leave the PC idling for another 3 hours of light use, that is (374W × 3h) + (80W × 3h) ≈ 1.36 kWh per day. At an electricity price of $0.15 per kWh, that is about $0.20 per day, or roughly $6.13 a month: small on its own, but a useful number when comparing it against a higher-power build.', 'computingskill' ),
			),
			array(
				'PSU efficiency also means', 'actually receive.',
				__( 'PSU efficiency is included as a typical figure (about 88% under gaming load, 80% at idle); a high-efficiency 80+ Gold or Platinum unit wastes a little less, and an older or budget unit a little more.', 'computingskill' ),
			),
		),

		// D5 (audit 1.20): case fans follow the combined heat load.
		'pc-cooling-calculator' => array(
			array(
				'It then compares the result against typical cooling capacities', 'case airflow configurations.',
				__( 'It then compares the result against typical cooling capacities for different cooler classes, and sizes the case fans to the same combined heat load: under about 350W, 350–549W, or 550W and above, each step adding intake and exhaust fans (in a mid tower, 3 fans, then 4–5, then 5–6 with a mesh front panel). In a compact case it warns when the heat load is high enough that a mid tower would be easier to cool.', 'computingskill' ),
			),
		),

		// K3: the worked example said 23.6 × 13.3 for a 27-inch screen; the
		// formula and the reference table give 23.5 × 13.2.
		'monitor-size-calculator' => array(
			array( '(about 23.6', '13.3 inches)', __( '(about 23.5 × 13.2 inches)', 'computingskill' ) ),
		),

		// K2: a one-sentence definition with the full name first.
		'vram' => array(
			array( 'VRAM, or video memory, is', 'VRAM, or video memory, is', __( 'VRAM (video RAM, short for video random access memory) is', 'computingskill' ) ),
		),

		// Audit 1.14 / 1.15: the tool now has the breakdown and budget field.
		'pc-build-cost-calculator' => array(
			array( 'what percentage of your budget went to each category', 'each category', __( 'what percentage of your PC parts spending went to each category', 'computingskill' ) ),
			array(
				'That breakdown is the more useful part', 'general build-planning guidance.',
				__( 'That breakdown is the more useful part for planning purposes: it lets you see at a glance whether, say, your GPU is eating an unusually large or small share of the total compared to general build-planning guidance. If you enter your budget, the result also shows how much is left or how far over you are. Peripherals and tax or shipping are added to the total but kept out of the percentage split, so they don’t skew the balance between parts.', 'computingskill' ),
			),
			array( 'It does not track sales, regional pricing differences, currency', 'currency', __( 'It does not track sales, regional pricing differences, currency conversion (the currency choice only changes the symbol)', 'computingskill' ) ),
			// U1 (U-11): the GPU guide was "a quarter to a third", which flagged a
			// normal 5600 + RTX 4070 build (GPU 50%) as too GPU-heavy. Gaming
			// build guides put the GPU at about 35-50% of the parts budget; the
			// calculator now uses that band.
			array( 'such as the GPU commonly taking roughly a quarter', 'a gaming budget.', __( 'such as the GPU commonly taking roughly 35–50% of a gaming build’s parts budget.', 'computingskill' ) ),
			array( 'commonly landing somewhere in the neighborhood of a quarter', 'of total budget,', __( 'commonly landing at roughly 35–50% of the PC parts budget,', 'computingskill' ) ),
			array(
				'works out to about 38% of the $1,040 spent', 'intentional.',
				__( 'works out to about 38% of the $1,040 spent, inside the 35–50% range many gaming build guides use, while the CPU, motherboard and RAM together ($370, about 36%) take a similar share. Had one part landed far outside its usual range, the breakdown is where you would spot it and decide whether that is intentional.', 'computingskill' ),
			),
			array( 'many builders aim for roughly a quarter', 'on the GPU,', __( 'many builders put roughly 35–50% of the PC parts budget into the GPU,', 'computingskill' ) ),
		),

		// U1 (U-08): ray tracing raises the recommended VRAM, not the minimum.
		'gpu-vram-calculator' => array(
			array(
				'The calculator starts from its 1440p high-texture baseline of 8GB', 'for the same settings.',
				__( 'The calculator starts from its 1440p high-texture minimum of 8GB (frame buffers, high-resolution textures and typical open-world asset streaming). Ray tracing (BVH structures and denoising buffers) adds about 2GB, so with it on the calculator recommends 10GB or more, and 12GB as the comfortable figure. That is why the GeForce RTX 4060’s 8GB of GDDR6 is tight for ray-traced 1440p gaming, while the GeForce RTX 4070’s 12GB of GDDR6X has comfortable headroom for the same settings.', 'computingskill' ),
			),
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
	return cs_page_text_swap( $content, $fixes[ $slug ] );
}
add_filter( 'the_content', 'cs_calculator_page_text', 8 );

/**
 * Applies a list of array( opening words, closing words, new text ) to
 * $content (shared with the U2 page text in inc/u2-text.php).
 */
function cs_page_text_swap( $content, $list ) {
	foreach ( $list as $fix ) {
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
	define( 'CS_CALC_PAGES_REV', '2026-10-20-u3' );
}

function cs_calc_pages_changed() {
	return array(
		// U3 (user-side audit): tools that changed what they ask or show
		// (PSU "Other GPU", Compatibility "Other" boxes, CPU vs GPU scales,
		// FPS game type, Can I Run It cards). The type-to-find box on every
		// picker is a layout change only. U2, U1, K4, K3, K2, K1, D2, D3, D5
		// and D10 ran with earlier revs; their pages are dated already.
		'psu-calculator', 'pc-compatibility-checker', 'cpu-vs-gpu-calculator', 'fps-calculator', 'can-i-run-it',
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
	$touched = array();
	foreach ( cs_calc_pages_changed() as $slug ) {
		$page = get_page_by_path( $slug );
		if ( ! $page || 'publish' !== $page->post_status ) {
			continue;
		}
		$touched[] = $page->ID;
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
	// Cached copies still carry the old text and dates (no-op without
	// LiteSpeed). E1 (SEO-01): only these pages, not the whole cache.
	cs_perf_purge_posts( $touched );
	update_option( 'cs_calc_pages_rev', CS_CALC_PAGES_REV, true );
	delete_option( 'cs_calc_pages_lock' );
}
add_action( 'init', 'cs_calc_pages_touch', 41 );
