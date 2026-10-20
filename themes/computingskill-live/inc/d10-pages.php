<?php
/**
 * Deep audit D10: new tool and guide pages.
 *
 *  - /can-i-run-it/ (5.2): checks every game in data/games-data.php
 *    against the visitor's CPU, GPU and RAM (CS_DEFS.canirun in
 *    calculators.js), with a server-side example table.
 *  - /dlss-fsr-frame-generation-explained/ (5.7): upscaling and frame
 *    generation explainer, matching the FPS Calculator's new toggles.
 *  - /low-gpu-usage-in-games/ (5.8): troubleshooting low GPU usage,
 *    CPU at 100%, stutter and FPS drops.
 *
 * Same pattern as the tier lists: each page is created once, published,
 * holding only its shortcode, so the text lives in the theme. A page an
 * editor deletes is not recreated.
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * slug => [ title, template, shortcode ].
 */
function cs_d10_pages() {
	return array(
		'can-i-run-it'                        => array( __( 'Can I Run It? Check Which Games Your PC Can Run', 'computingskill' ), 'page-templates/template-calculator.php', '[cs_can_i_run]' ),
		'dlss-fsr-frame-generation-explained' => array( __( 'DLSS vs FSR vs XeSS: Upscaling and Frame Generation Explained', 'computingskill' ), 'page-templates/template-guide.php', '[cs_d10_guide name="upscaling"]' ),
		'low-gpu-usage-in-games'              => array( __( 'Low GPU Usage in Games: Causes and Fixes', 'computingskill' ), 'page-templates/template-guide.php', '[cs_d10_guide name="low-gpu-usage"]' ),
	);
}

function cs_d10_pages_create() {
	if ( wp_installing() ) {
		return;
	}
	$done = (array) get_option( 'cs_d10_pages_created', array() );
	$todo = array_diff( array_keys( cs_d10_pages() ), $done );
	if ( empty( $todo ) ) {
		return;
	}
	if ( ! add_option( 'cs_d10_pages_lock', time(), '', 'no' ) ) {
		if ( time() - (int) get_option( 'cs_d10_pages_lock' ) > 300 ) {
			delete_option( 'cs_d10_pages_lock' );
		}
		return;
	}
	$ref    = get_page_by_path( 'methodology' );
	$author = $ref ? (int) $ref->post_author : 0;
	$pages  = cs_d10_pages();
	foreach ( $todo as $slug ) {
		if ( ! get_page_by_path( $slug ) ) {
			$id = wp_insert_post( array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'post_name'      => $slug,
				'post_title'     => $pages[ $slug ][0],
				'post_author'    => $author,
				'post_content'   => $pages[ $slug ][2],
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
			), true );
			if ( is_wp_error( $id ) ) {
				continue;
			}
			update_post_meta( $id, '_wp_page_template', $pages[ $slug ][1] );
		}
		$done[] = $slug;
	}
	update_option( 'cs_d10_pages_created', array_values( array_unique( $done ) ), true );
	delete_option( 'cs_d10_pages_lock' );
}
add_action( 'init', 'cs_d10_pages_create', 32 );

/**
 * Rank Math meta descriptions for the three pages.
 */
function cs_d10_meta_description( $desc ) {
	if ( ! is_page() ) {
		return $desc;
	}
	$map = array(
		'can-i-run-it'                        => __( 'Pick your CPU, graphics card and RAM to see which popular PC games your computer can run, at what settings, and at what estimated frame rate.', 'computingskill' ),
		'dlss-fsr-frame-generation-explained' => __( 'How DLSS, FSR and XeSS upscaling and frame generation work, which graphics cards support each, how much FPS they add and when to turn them on.', 'computingskill' ),
		'low-gpu-usage-in-games'              => __( 'Why your graphics card shows low usage in games, how to tell a CPU bottleneck from a frame cap, and the fixes for low GPU usage, CPU at 100% and stutter.', 'computingskill' ),
	);
	$slug = get_post_field( 'post_name' );
	return isset( $map[ $slug ] ) ? $map[ $slug ] : $desc;
}
add_filter( 'rank_math/frontend/description', 'cs_d10_meta_description', 20 );

/* ------------------------------------------------------------------ */
/* Can I Run It                                                        */
/* ------------------------------------------------------------------ */

/**
 * U1 (U-01): a game's GPU load next to others of its type, from its
 * publisher-recommended GPU on our score scale (gameGpuLoad() in
 * calculators.js, same constants): sqrt( type median / recommended ),
 * kept within 0.75-1.3. Medians of the games list: AAA 30, esports 11,
 * CPU-heavy 32.
 */
function cs_game_gpu_load( $g, $type ) {
	$ref = array( 'aaa' => 30, 'esports' => 11, 'cpu-heavy' => 32 );
	$rec = isset( $g['req']['rec']['gpu'] ) ? (float) $g['req']['rec']['gpu'] : 0;
	if ( ! $rec || ! isset( $ref[ $type ] ) ) {
		return 1.0;
	}
	return max( 0.75, min( 1.3, sqrt( $ref[ $type ] / $rec ) ) );
}

/**
 * Highest preset that reaches $target FPS (CS_DEFS.canirun cirBest()):
 * [ preset or '', fps ]. U1: with the game's load and frame-rate cap.
 */
function cs_cir_best( $cpu, $gpu, $res, $target, $game_type, $g = null ) {
	$fps  = 0;
	$load = $g ? cs_game_gpu_load( $g, $game_type ) : 1.0;
	$cap  = $g && ! empty( $g['fps_cap'] ) ? (int) $g['fps_cap'] : 0;
	foreach ( array( 'ultra', 'high', 'medium', 'low' ) as $preset ) {
		list( $fps ) = cs_tl_fps( $cpu, $gpu, $res, $preset, $game_type, $load );
		if ( $cap ) {
			$fps = min( $fps, $cap );
		}
		if ( $fps >= $target ) {
			return array( $preset, $fps );
		}
	}
	return array( '', $fps );
}

/**
 * Publisher tier a PC meets for a game (CS_DEFS.canirun cirLevel()):
 * [ 'rec'|'min'|'below', parts short of the next tier ].
 */
function cs_cir_level( $cpu, $gpu, $ram, $g ) {
	$req   = isset( $g['req'] ) ? $g['req'] : array( 'min' => array( 'cpu' => 0, 'gpu' => 0 ), 'rec' => array( 'cpu' => 0, 'gpu' => 0 ) );
	$below = array();
	$under = array();
	foreach ( array( 'CPU' => array( $cpu['score'], $req['min']['cpu'], $req['rec']['cpu'] ), 'GPU' => array( $gpu['score'], $req['min']['gpu'], $req['rec']['gpu'] ), 'RAM' => array( $ram, $g['min']['ram'], $g['rec']['ram'] ) ) as $part => $x ) {
		if ( $x[0] < $x[1] ) {
			$below[] = $part;
		} elseif ( $x[0] < $x[2] ) {
			$under[] = $part;
		}
	}
	return $below ? array( 'below', $below ) : array( $under ? 'min' : 'rec', $under );
}

/**
 * Example PCs for the server-side table: [ label, cpu, gpu, res ].
 */
function cs_cir_examples() {
	return array(
		array( __( 'Budget', 'computingskill' ), 'core-i3-12100f', 'rtx-3060', '1080p' ),
		array( __( 'Mainstream', 'computingskill' ), 'ryzen-5-5600', 'rtx-5060', '1080p' ),
		array( __( 'Mid-range', 'computingskill' ), 'ryzen-5-7600', 'rtx-5070', '1440p' ),
		array( __( 'High-end', 'computingskill' ), 'ryzen-7-9800x3d', 'rtx-5080', '4k' ),
	);
}

function cs_can_i_run_shortcode() {
	$games  = cs_get_games_data();
	$cpus   = cs_get_cpu_data();
	$gpus   = cs_get_gpu_data();
	$labels = array( 'ultra' => __( 'Ultra', 'computingskill' ), 'high' => __( 'High', 'computingskill' ), 'medium' => __( 'Medium', 'computingskill' ), 'low' => __( 'Low', 'computingskill' ) );
	$res_l  = array( '1080p' => '1080p', '1440p' => '1440p', '4k' => '4K' );
	$ex     = array();
	foreach ( cs_cir_examples() as $e ) {
		if ( isset( $cpus[ $e[1] ], $gpus[ $e[2] ] ) ) {
			$ex[] = $e;
		}
	}

	$html = cs_tl_quick_answer( esc_html( sprintf(
		/* translators: %d: number of games */
		__( 'Pick your CPU, graphics card and RAM below and this tool checks all %d games in our database at once: whether your PC meets each publisher’s minimum or recommended specs, the highest graphics preset that reaches your target frame rate, and the estimated FPS there.', 'computingskill' ),
		count( $games )
	) ) );
	$html .= '<p>' . sprintf(
		/* translators: 1, 2: links */
		esc_html__( '“Can I run it?” is really two questions: does my PC meet the game’s requirements, and how well will it run? Publisher requirements answer the first only roughly, so this tool answers the second with the same formula as our %1$s, applied to each game’s type. For one game in more detail, use the FPS Calculator or open the game’s page in our %2$s.', 'computingskill' ),
		cs_tl_link( 'fps-calculator', __( 'FPS Calculator', 'computingskill' ) ),
		'<a href="' . esc_url( home_url( '/games/' ) ) . '">' . esc_html__( 'games library', 'computingskill' ) . '</a>'
	) . '</p>';

	$html .= do_shortcode( '[cs_calculator type="canirun" title="' . esc_attr__( 'Can I Run It checker', 'computingskill' ) . '"]' );

	if ( $ex ) {
		$html .= '<h2>' . esc_html__( 'What Typical Gaming PCs Can Run', 'computingskill' ) . '</h2>';
		$html .= '<p>' . esc_html__( 'Four common builds with 16GB of RAM: whether each meets the publisher’s recommended or minimum specs, and the highest preset that holds 60 FPS or more (estimated FPS in brackets; “under 60” means not even at low settings). The FPS follows the game’s type and how demanding its published recommended graphics card is; where a build only meets the minimum, expect less. Elden Ring is locked to 60 FPS, a build below a game’s minimum gets no FPS figure, and Minecraft’s FPS depends on render distance and mods more than on the parts, so it has none.', 'computingskill' ) . '</p>';
		$html .= '<div class="cs-table-wrap"><table class="cs-hardware-table cs-cir-ref"><caption>' . esc_html__( 'Publisher specs met, and highest settings at 60+ FPS (estimated)', 'computingskill' ) . '</caption><thead><tr><th scope="col">' . esc_html__( 'Game', 'computingskill' ) . '</th>';
		foreach ( $ex as $e ) {
			$html .= '<th scope="col">' . esc_html( $e[0] ) . '<br><small>' . cs_pp_part_link( $e[1], $cpus[ $e[1] ] ) . ' + ' . cs_pp_part_link( $e[2], $gpus[ $e[2] ] ) . ', ' . esc_html( $res_l[ $e[3] ] ) . '</small></th>';
		}
		$html .= '</tr></thead><tbody>';
		$lvl_l = array( 'rec' => __( 'Recommended', 'computingskill' ), 'min' => __( 'Minimum', 'computingskill' ), 'below' => __( 'Below minimum', 'computingskill' ) );
		foreach ( $games as $slug => $g ) {
			$type  = isset( $g['profile'] ) ? $g['profile'] : 'aaa';
			$html .= '<tr><th scope="row"><a href="' . esc_url( home_url( '/games/' . $slug . '/' ) ) . '">' . esc_html( $g['name'] ) . '</a></th>';
			foreach ( $ex as $e ) {
				list( $lvl ) = cs_cir_level( $cpus[ $e[1] ], $gpus[ $e[2] ], 16, $g );
				// U1 (U-01): no FPS figure below the publisher minimum or for a
				// game whose FPS follows its own settings.
				if ( 'below' === $lvl || ! empty( $g['fps_varies'] ) ) {
					$html .= '<td>' . esc_html( $lvl_l[ $lvl ] ) . ( 'below' === $lvl ? '' : ' · ' . esc_html__( 'no FPS estimate', 'computingskill' ) ) . '</td>';
					continue;
				}
				list( $preset, $fps ) = cs_cir_best( $cpus[ $e[1] ], $gpus[ $e[2] ], $e[3], 60, $type, $g );
				$capped = ! empty( $g['fps_cap'] ) && $fps >= (int) $g['fps_cap'];
				$html .= '<td>' . esc_html( $lvl_l[ $lvl ] ) . ' · ' . ( $preset ? esc_html( $labels[ $preset ] ) : esc_html__( 'under 60', 'computingskill' ) ) . ' (' . (int) round( $fps ) . ( $capped ? ' ' . esc_html__( 'cap', 'computingskill' ) : '' ) . ')</td>';
			}
			$html .= '</tr>';
		}
		$html .= '</tbody></table></div>';
	}

	$html .= '<h2>' . esc_html__( 'How the Checker Works', 'computingskill' ) . '</h2><ol>';
	$html .= '<li>' . esc_html__( 'Each game in our database has a type: typical AAA, esports (light competitive shooters and MOBAs) or CPU-heavy (strategy, simulation, MMOs, big open worlds).', 'computingskill' ) . '</li>';
	$html .= '<li>' . sprintf( esc_html__( 'For each game the tool estimates FPS at ultra, high, medium and low settings with the %s’s formula: the lower of what your graphics card can draw and what your CPU can prepare.', 'computingskill' ), cs_tl_link( 'fps-calculator', __( 'FPS Calculator', 'computingskill' ) ) ) . '</li>';
	$html .= '<li>' . esc_html__( 'Each game’s estimate is then scaled by how demanding its published recommended graphics card is next to other games of its type, so a heavier game shows lower FPS. Games that lock their frame rate (Elden Ring at 60 FPS) never show more than the lock.', 'computingskill' ) . '</li>';
	$html .= '<li>' . esc_html__( 'It shows the highest preset that reaches your target frame rate, and the FPS there. A game your PC is below the publisher’s minimum for gets no FPS figure and isn’t counted as playable.', 'computingskill' ) . '</li>';
	$html .= '<li>' . esc_html__( 'Your CPU, graphics card and RAM are compared with the publisher’s own minimum and recommended specs for that game. Those parts are placed on our score scale (where a listed part isn’t in our database, approximately, from public review averages), so a newer chip of a different brand is compared fairly.', 'computingskill' ) . '</li></ol>';
	$html .= '<p>' . sprintf(
		/* translators: 1, 2: links */
		esc_html__( 'The CPU and GPU scores are ComputingSkill’s gaming index, positioned from publicly reported review averages; we do not benchmark games ourselves (%1$s). A game type is a rough class, so a particular game can run better or worse than its estimate, and ray tracing is not included. Upscaling and frame generation can add more: see %2$s.', 'computingskill' ),
		cs_tl_link( 'methodology', __( 'how the scores work', 'computingskill' ) ),
		cs_tl_link( 'dlss-fsr-frame-generation-explained', __( 'DLSS, FSR and XeSS explained', 'computingskill' ) )
	) . '</p>';

	$html .= cs_tl_faq( array(
		__( 'How do I know if my PC can run a game?', 'computingskill' ) => __( 'Compare your CPU, graphics card and RAM with the game’s published minimum and recommended specs, or pick your parts in the checker above. It estimates the highest settings each game reaches at your target frame rate, which tells you more than a pass/fail check.', 'computingskill' ),
		__( 'What if my CPU or graphics card is not listed?', 'computingskill' ) => __( 'Choose “Other / not listed” and then the listed part that reviews place closest to yours in performance. The estimate then uses that part’s data.', 'computingskill' ),
		__( 'Is meeting the minimum requirements enough?', 'computingskill' ) => __( 'Minimum requirements usually mean 1080p at low settings and around 30 FPS. For 60 FPS at higher settings, aim for the recommended specs or above.', 'computingskill' ),
		__( 'Does RAM affect whether I can run a game?', 'computingskill' ) => __( 'Below the publisher’s minimum, a game may stutter badly or refuse to start. Between minimum and recommended it usually runs but can stutter in busy scenes. Our RAM Calculator suggests a kit size for gaming.', 'computingskill' ),
	) );
	return $html;
}
add_shortcode( 'cs_can_i_run', 'cs_can_i_run_shortcode' );

/* ------------------------------------------------------------------ */
/* Guides                                                              */
/* ------------------------------------------------------------------ */

function cs_d10_guide_shortcode( $atts ) {
	$atts = shortcode_atts( array( 'name' => '' ), $atts, 'cs_d10_guide' );
	if ( 'upscaling' === $atts['name'] ) {
		return cs_d10_upscaling_html();
	}
	if ( 'low-gpu-usage' === $atts['name'] ) {
		return cs_d10_low_gpu_html();
	}
	return '';
}
add_shortcode( 'cs_d10_guide', 'cs_d10_guide_shortcode' );

function cs_d10_upscaling_html() {
	$link = 'cs_tl_link';
	$html = cs_tl_quick_answer( esc_html__( 'Upscaling (DLSS, FSR, XeSS) renders each frame at a lower resolution and rebuilds it at your screen’s resolution, so the graphics card does less work and FPS rises, typically by about 25-85% depending on the mode and resolution. Frame generation goes further and inserts generated frames between rendered ones, which makes motion smoother but does not make the game respond faster. DLSS needs an NVIDIA RTX card, FSR 4 needs an AMD Radeon RX 9000 card, and FSR 3 and XeSS work on most modern cards.', 'computingskill' ) );

	$html .= '<h2>' . esc_html__( 'What Upscaling Does', 'computingskill' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'Drawing every pixel at 1440p or 4K is the most expensive part of a modern game. An upscaler renders fewer pixels, then uses motion data from the game (and, in DLSS, FSR 4 and XeSS, a trained neural network) to rebuild a sharp full-resolution image. The quality modes set how low the render resolution goes:', 'computingskill' ) . '</p>';
	$html .= cs_pp_table( __( 'Upscaling modes and render resolution', 'computingskill' ), array( __( 'Mode', 'computingskill' ), __( 'Render scale (per axis)', 'computingskill' ), __( 'At 1440p output', 'computingskill' ), __( 'At 4K output', 'computingskill' ) ), array(
		array( esc_html__( 'Quality', 'computingskill' ), '~67%', '~960p', '~1440p' ),
		array( esc_html__( 'Balanced', 'computingskill' ), '~58%', '~835p', '~1253p' ),
		array( esc_html__( 'Performance', 'computingskill' ), '50%', '720p', '1080p' ),
		array( esc_html__( 'Ultra Performance', 'computingskill' ), '~33%', '~480p', '720p' ),
	) );
	$html .= '<p>' . esc_html__( 'The higher your output resolution, the better upscaling looks and the more it gains, because there are more real pixels to rebuild from. At 4K, Quality and even Performance mode are often hard to tell from native; at 1080p, upscaling starts from very few pixels, so stay on Quality. DLAA (NVIDIA) and FSR Native AA use the same technology at full resolution purely to improve anti-aliasing, with no FPS gain.', 'computingskill' ) . '</p>';

	$html .= '<h2>' . esc_html__( 'DLSS vs FSR vs XeSS', 'computingskill' ) . '</h2>';
	$html .= cs_pp_table( __( 'Upscaling and frame generation by maker', 'computingskill' ), array( __( 'Technology', 'computingskill' ), __( 'Works on', 'computingskill' ), __( 'Frame generation', 'computingskill' ) ), array(
		array( esc_html__( 'NVIDIA DLSS 4', 'computingskill' ), esc_html__( 'Upscaling on every GeForce RTX card (RTX 20 and newer); not on GTX cards', 'computingskill' ), esc_html__( 'Frame Generation (2x) on RTX 40 and 50; Multi Frame Generation (up to 4x) on RTX 50 only', 'computingskill' ) ),
		array( esc_html__( 'AMD FSR 4', 'computingskill' ), esc_html__( 'Machine-learning upscaling on Radeon RX 9000 (RDNA 4) cards', 'computingskill' ), esc_html__( 'Yes (2x)', 'computingskill' ) ),
		array( esc_html__( 'AMD FSR 3 / 3.1', 'computingskill' ), esc_html__( 'Almost any modern graphics card, including NVIDIA and Intel', 'computingskill' ), esc_html__( 'Yes (2x), also on non-AMD cards in games that offer it', 'computingskill' ) ),
		array( esc_html__( 'AMD Fluid Motion Frames (AFMF)', 'computingskill' ), esc_html__( 'Radeon RX 6000 and newer, switched on in the AMD driver', 'computingskill' ), esc_html__( 'Driver-level 2x for games without built-in frame generation; lower quality than in-game FSR', 'computingskill' ) ),
		array( esc_html__( 'Intel XeSS 2', 'computingskill' ), esc_html__( 'Best on Intel Arc; a fallback version runs on other modern cards', 'computingskill' ), esc_html__( 'Frame generation (2x) on Intel Arc', 'computingskill' ) ),
	) );
	$html .= '<p>' . sprintf(
		/* translators: %s: link */
		esc_html__( 'Which one you can use depends on your card and on the game: each game ships its own list of supported upscalers. Every part page in our %s lists the upscaling and frame generation that card supports.', 'computingskill' ),
		'<a href="' . esc_url( home_url( '/hardware/#every-part' ) ) . '">' . esc_html__( 'hardware section', 'computingskill' ) . '</a>'
	) . '</p>';

	$html .= '<h2>' . esc_html__( 'How Much FPS Does Upscaling Add?', 'computingskill' ) . '</h2>';
	$html .= '<p>' . sprintf(
		/* translators: %s: link */
		esc_html__( 'These are the typical gains our %s applies when you switch upscaling on. They lift only the graphics card’s side: if your CPU is already the limit, upscaling adds little, which is a quick way to spot a CPU bottleneck.', 'computingskill' ),
		$link( 'fps-calculator', __( 'FPS Calculator', 'computingskill' ) )
	) . '</p>';
	$html .= cs_pp_table( __( 'Typical FPS gain from upscaling (GPU-limited)', 'computingskill' ), array( __( 'Mode', 'computingskill' ), '1080p', '1440p', '4K' ), array(
		array( esc_html__( 'Quality', 'computingskill' ), '+25%', '+35%', '+40%' ),
		array( esc_html__( 'Balanced', 'computingskill' ), '+35%', '+50%', '+60%' ),
		array( esc_html__( 'Performance', 'computingskill' ), '+50%', '+70%', '+85%' ),
	) );
	$html .= '<p>' . esc_html__( 'Real gains vary by game and card: the upscaler itself costs a little time per frame, and parts of the frame (shadows, physics, the CPU’s work) don’t shrink with resolution.', 'computingskill' ) . '</p>';

	$html .= '<h2>' . esc_html__( 'What Frame Generation Does', 'computingskill' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'Frame generation renders frames as usual, then creates extra frames in between from the motion of the real ones. With 2x, every rendered frame is followed by one generated frame; with 4x (RTX 50 only), by three. The FPS counter and the smoothness of motion go up close to 2x or 4x, minus the cost of generating them (about 10% of the base frame rate in our calculator).', 'computingskill' ) . '</p>';
	$html .= '<p>' . esc_html__( 'What doesn’t go up is responsiveness. The game still reads your mouse and keyboard at the rendered frame rate, and holding back a frame to generate the in-between one adds a little input lag. NVIDIA Reflex, AMD Anti-Lag and Intel XeLL are switched on with frame generation to offset that. The makers suggest a base of about 60 FPS before you turn it on: from 30 FPS, 2x looks like 60 but still feels like 30, with more visual artifacts.', 'computingskill' ) . '</p>';
	$html .= '<ul><li>' . esc_html__( 'Good for: single-player games, high-refresh monitors, and getting a 60-80 FPS game up to 120+ for smoother motion.', 'computingskill' ) . '</li>';
	$html .= '<li>' . esc_html__( 'Not for: competitive shooters, where input lag matters most, or rescuing a game that runs at 30 FPS.', 'computingskill' ) . '</li>';
	$html .= '<li>' . esc_html__( 'Memory: frame generation needs some extra VRAM, which can push an 8GB card over the edge at high settings.', 'computingskill' ) . '</li></ul>';

	$html .= '<h2>' . esc_html__( 'Should You Turn Them On?', 'computingskill' ) . '</h2><ul>';
	$html .= '<li>' . esc_html__( 'GPU-limited at 1440p or 4K: yes, start with upscaling on Quality. It is the cheapest FPS you will get.', 'computingskill' ) . '</li>';
	$html .= '<li>' . esc_html__( 'Already at your monitor’s refresh rate: use DLAA or native resolution for the sharpest image instead.', 'computingskill' ) . '</li>';
	$html .= '<li>' . sprintf( esc_html__( 'CPU-limited (low GPU usage): upscaling won’t help much; see %s.', 'computingskill' ), $link( 'low-gpu-usage-in-games', __( 'low GPU usage in games', 'computingskill' ) ) ) . '</li>';
	$html .= '<li>' . esc_html__( 'Frame generation: when the base frame rate is already about 60 and you want smoother motion in a single-player game.', 'computingskill' ) . '</li></ul>';
	$html .= '<p>' . sprintf(
		/* translators: 1-3: links */
		esc_html__( 'Try it with your own parts in the %1$s, which now has upscaling and frame generation options, compare cards in the %2$s, or check which games your PC runs with %3$s.', 'computingskill' ),
		$link( 'fps-calculator', __( 'FPS Calculator', 'computingskill' ) ),
		$link( 'gpu-tier-list', __( 'GPU tier list', 'computingskill' ) ),
		$link( 'can-i-run-it', __( 'Can I Run It', 'computingskill' ) )
	) . '</p>';

	$html .= cs_tl_faq( array(
		__( 'Is DLSS better than FSR?', 'computingskill' ) => __( 'DLSS and FSR 4 both use machine learning and look close to each other; both usually look better than FSR 3, which works on almost any card. Which you can use depends on your graphics card and on what the game supports.', 'computingskill' ),
		__( 'Does frame generation increase input lag?', 'computingskill' ) => __( 'A little. The game still responds at the rendered frame rate, and holding a frame back to generate the in-between one adds some delay, which Reflex, Anti-Lag or XeLL offset. It feels best from a base of about 60 FPS.', 'computingskill' ),
		__( 'Can my GTX card use DLSS?', 'computingskill' ) => __( 'No. DLSS needs the Tensor cores in GeForce RTX cards. GTX cards can use FSR 3 or XeSS in games that offer them.', 'computingskill' ),
		__( 'Is RTX 50 Multi Frame Generation worth it?', 'computingskill' ) => __( 'On a high-refresh monitor in single-player games, it can turn about 60 rendered FPS into 200+ shown. It doesn’t make the game respond faster, so it matters less in competitive games.', 'computingskill' ),
	) );
	return $html;
}

function cs_d10_low_gpu_html() {
	$link = 'cs_tl_link';
	$html = cs_tl_quick_answer( esc_html__( 'Low GPU usage in a game almost always means something other than the graphics card is setting the frame rate: most often the CPU (a CPU bottleneck), a frame cap such as V-Sync or an FPS limit, or the game engine itself. If your FPS is at your cap, low usage is normal and fine. If FPS is low and GPU usage is under about 90%, raise the resolution or settings: if FPS doesn’t drop, the CPU is the limit.', 'computingskill' ) );

	$html .= '<h2>' . esc_html__( 'Is Low GPU Usage a Problem?', 'computingskill' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'Not always. A graphics card at 95-99% usage is doing all the work it can, which is what you want in most games. Usage below that means the card is waiting. That’s fine when the frame rate is already where you want it, for example capped at your monitor’s refresh rate, and in light games such as esports titles at 1080p. It’s a problem only when FPS is lower than you want and the card still isn’t busy.', 'computingskill' ) . '</p>';

	$html .= '<h2>' . esc_html__( 'How to Check What Is Holding It Back', 'computingskill' ) . '</h2><ol>';
	$html .= '<li>' . esc_html__( 'Turn on an overlay that shows FPS, GPU usage and CPU usage per core: MSI Afterburner with RivaTuner, the NVIDIA app overlay, AMD Adrenalin’s metrics overlay or the Xbox Game Bar (Win + G).', 'computingskill' ) . '</li>';
	$html .= '<li>' . esc_html__( 'Play a demanding scene. If FPS sits exactly at a number such as 60, 144 or 165, a cap is the limit (see below).', 'computingskill' ) . '</li>';
	$html .= '<li>' . esc_html__( 'If not, raise the resolution or graphics settings. If FPS stays about the same while GPU usage rises, the CPU was the limit. If FPS drops in step, the GPU was the limit and the low usage came from somewhere else.', 'computingskill' ) . '</li>';
	$html .= '<li>' . esc_html__( 'Look at the busiest CPU core, not the total. Many games lean on one or two threads, so one core at 100% can hold the game back while total CPU usage reads 40%.', 'computingskill' ) . '</li></ol>';
	$html .= '<p>' . sprintf(
		/* translators: %s: link */
		esc_html__( 'To see whether your CPU and graphics card are a mismatch before you test, check your pair in the %s.', 'computingskill' ),
		$link( 'pc-bottleneck-calculator', __( 'PC Bottleneck Calculator', 'computingskill' ) )
	) . '</p>';

	$html .= '<h2>' . esc_html__( 'Common Causes and Fixes', 'computingskill' ) . '</h2>';
	$causes = array(
		array( __( 'CPU bottleneck', 'computingskill' ), __( 'The CPU can’t prepare frames as fast as the card can draw them. Most common at 1080p, in esports titles, with high-refresh targets and in strategy or simulation games.', 'computingskill' ), __( 'Raise resolution or settings to use the spare GPU power, cap FPS where it’s steady, or upgrade the CPU. Check the fastest CPU your motherboard supports first.', 'computingskill' ) ),
		array( __( 'Frame cap or V-Sync', 'computingskill' ), __( 'V-Sync, an in-game FPS limit, a driver limit (NVIDIA Max Frame Rate, AMD Chill) or Battery Boost stops the card from going faster.', 'computingskill' ), __( 'Nothing to fix if the cap is where you want it. Otherwise raise or remove it.', 'computingskill' ) ),
		array( __( 'Game or engine limit', 'computingskill' ), __( 'Some engines lean on one thread, cap FPS internally, or load the world as you move. Menus and loading screens also show low usage.', 'computingskill' ), __( 'Check the game’s settings and patch notes; a faster CPU helps most here.', 'computingskill' ) ),
		array( __( 'Single-channel or slow RAM', 'computingskill' ), __( 'One memory stick, or memory running at its default speed because XMP / EXPO is off, slows the CPU in games.', 'computingskill' ), __( 'Use two sticks in the slots the motherboard manual names, and turn on XMP or EXPO in the BIOS.', 'computingskill' ) ),
		array( __( 'Laptop running on the wrong GPU or power mode', 'computingskill' ), __( 'The game runs on the integrated graphics, or the laptop is on battery or a quiet power mode.', 'computingskill' ), __( 'Plug in, choose the performance mode, and set the game to “High performance” in Windows Settings > System > Display > Graphics.', 'computingskill' ) ),
		array( __( 'Background apps and overlays', 'computingskill' ), __( 'Browsers, launchers, recording and RGB software take CPU time from the game.', 'computingskill' ), __( 'Close what you don’t need while gaming.', 'computingskill' ) ),
		array( __( 'Heat', 'computingskill' ), __( 'A CPU near its temperature limit slows itself down (thermal throttling), which shows as low GPU usage.', 'computingskill' ), __( 'Check CPU temperatures under load, clean dust, and check the cooler is seated and its fans spin.', 'computingskill' ) ),
		array( __( 'Drivers, BIOS and slot', 'computingskill' ), __( 'Old graphics or chipset drivers, an old BIOS, or a card in a secondary slot or on a cheap riser running at fewer PCIe lanes.', 'computingskill' ), __( 'Update the graphics driver, chipset driver and BIOS, and use the top full-length PCIe slot.', 'computingskill' ) ),
	);
	$rows = array();
	foreach ( $causes as $c ) {
		$rows[] = array( esc_html( $c[0] ), esc_html( $c[1] ), esc_html( $c[2] ) );
	}
	$html .= cs_pp_table( __( 'Why GPU usage is low, and what to do', 'computingskill' ), array( __( 'Cause', 'computingskill' ), __( 'What happens', 'computingskill' ), __( 'Fix', 'computingskill' ) ), $rows );

	$html .= '<h2>' . esc_html__( 'CPU at 100% in Games', 'computingskill' ) . '</h2>';
	$html .= '<p>' . sprintf(
		/* translators: %s: link */
		esc_html__( 'A CPU at or near 100%% with the graphics card well below that is the clearest sign of a CPU bottleneck. Older 4- and 6-core chips hit it first in big open-world and multiplayer games. Close background apps and check temperatures first; if it stays at 100%%, a faster CPU is the fix, and our %s shows how much a new chip would add with your card.', 'computingskill' ),
		$link( 'pc-upgrade-calculator', __( 'PC Upgrade Calculator', 'computingskill' ) )
	) . '</p>';

	$html .= '<h2>' . esc_html__( 'Stutter and FPS Drops', 'computingskill' ) . '</h2><ul>';
	$html .= '<li>' . esc_html__( 'Stutter in the first minutes of a new game or after a driver update is often shader compilation; it usually settles once the shaders are built.', 'computingskill' ) . '</li>';
	$html .= '<li>' . sprintf( esc_html__( 'Stutter that gets worse at higher texture settings points to running out of VRAM; lower texture quality first. Our %s shows how much a resolution needs.', 'computingskill' ), $link( 'gpu-vram-calculator', __( 'GPU VRAM Calculator', 'computingskill' ) ) ) . '</li>';
	$html .= '<li>' . sprintf( esc_html__( 'Drops when lots happens on screen (crowds, explosions, big battles) are usually the CPU. Drops after long sessions point to heat.', 'computingskill' ) ) . '</li>';
	$html .= '<li>' . sprintf( esc_html__( 'Too little RAM causes stutter when the game streams new areas; check our %s.', 'computingskill' ), $link( 'ram-calculator', __( 'RAM Calculator', 'computingskill' ) ) ) . '</li></ul>';

	$html .= '<p>' . sprintf(
		/* translators: 1-3: links */
		esc_html__( 'More on the theory in %1$s. If the card is the limit and you want more FPS, %2$s can add a lot; for an upgrade plan, start with the %3$s.', 'computingskill' ),
		$link( 'cpu-bottleneck-explained', __( 'CPU bottleneck explained', 'computingskill' ) ),
		$link( 'dlss-fsr-frame-generation-explained', __( 'upscaling and frame generation', 'computingskill' ) ),
		$link( 'pc-upgrade-calculator', __( 'PC Upgrade Calculator', 'computingskill' ) )
	) . '</p>';

	$html .= cs_tl_faq( array(
		__( 'Why is my GPU usage low but FPS also low?', 'computingskill' ) => __( 'Something else is setting the frame rate, most often the CPU. Raise the resolution: if FPS stays the same, the CPU is the limit. Also check for an FPS cap, single-channel RAM and a laptop running on battery.', 'computingskill' ),
		__( 'Is 100% GPU usage bad?', 'computingskill' ) => __( 'No. In games it means the graphics card is doing all the work it can, which is normal and healthy as long as temperatures are in range.', 'computingskill' ),
		__( 'Does upscaling help with low GPU usage?', 'computingskill' ) => __( 'Usually not. Upscaling makes the graphics card’s job lighter, but when usage is already low, the CPU or a cap is the limit. Raising settings or resolution uses the spare power better.', 'computingskill' ),
		__( 'Why is my GPU usage low in esports games?', 'computingskill' ) => __( 'Esports titles are light on the graphics card and run at hundreds of FPS, so the CPU sets the frame rate. That is normal; a faster CPU is what raises FPS there.', 'computingskill' ),
	) );
	return $html;
}

/* ------------------------------------------------------------------ */
/* Published post rewrite                                              */
/* ------------------------------------------------------------------ */

/**
 * slug => [ file in data/post-rewrites/, text only the old version has ].
 */
function cs_d10_post_rewrite_list() {
	return array(
		'best-gpu-for-ryzen-5-5600' => array( 'best-gpu-for-ryzen-5-5600.php', 'means the graphics card is the likely limit by roughly that much' ),
	);
}

/**
 * Replaces a published post's content once, only while it still holds the
 * old text (an edit Ali makes in wp-admin is never overwritten). The old
 * version stays as a revision.
 */
function cs_d10_post_rewrites() {
	if ( wp_installing() ) {
		return;
	}
	$done = (array) get_option( 'cs_d10_post_rewrites', array() );
	$todo = array_diff( array_keys( cs_d10_post_rewrite_list() ), $done );
	if ( empty( $todo ) || ! add_option( 'cs_d10_post_rewrites_lock', time(), '', 'no' ) ) {
		if ( $todo && time() - (int) get_option( 'cs_d10_post_rewrites_lock' ) > 300 ) {
			delete_option( 'cs_d10_post_rewrites_lock' );
		}
		return;
	}
	foreach ( $todo as $slug ) {
		list( $file, $marker ) = cs_d10_post_rewrite_list()[ $slug ];
		$post = get_page_by_path( $slug, OBJECT, 'post' );
		if ( ! $post ) {
			continue; // Not there (yet); checked again on a later request.
		}
		if ( false !== strpos( $post->post_content, $marker ) ) {
			$content = include CS_THEME_DIR . '/data/post-rewrites/' . $file;
			if ( is_string( $content ) && '' !== $content ) {
				wp_update_post( array( 'ID' => $post->ID, 'post_content' => wp_slash( $content ) ) );
			}
		}
		$done[] = $slug;
	}
	update_option( 'cs_d10_post_rewrites', array_values( array_unique( $done ) ), true );
	delete_option( 'cs_d10_post_rewrites_lock' );
}
add_action( 'init', 'cs_d10_post_rewrites', 33 );
