<?php
/**
 * GPU and CPU tier lists (deep audit D8, items 3.7 and 5.1).
 *
 * The CPU and GPU scores were the site's own data but lived only inside
 * JavaScript (window.csHardwareData), so nothing on the site could be
 * quoted. /gpu-tier-list/ and /cpu-tier-list/ print them as server-rendered
 * tables, with every number taken from data/hardware-data.php and the same
 * engine the calculators use:
 *  - GPU tiers follow the PC Upgrade Calculator's own targets (score 33 for
 *    1080p, 50 for 1440p, 70 for 4K, cs_bn_upgrade_targets()), so a card the
 *    tier list calls "1440p" is never one that tool calls too weak.
 *  - CPU tiers follow the CPU Performance Calculator's labels (Flagship 77+,
 *    High-end 66+, Mid-range 54+), with an S tier for the X3D-class chips.
 *  - FPS figures use the FPS Calculator's formula (cs_tl_fps(), a mirror of
 *    CS_DEFS.fps in calculators.js): keep the two in sync.
 *
 * The pages are created once, holding only their shortcode, so the text
 * stays in the theme (same pattern as the D1 policy pages).
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Same 2.5 FPS per GPU score point at 1080p high as CS_DEFS.fps.
if ( ! defined( 'CS_TL_FPS_PER_POINT' ) ) {
	define( 'CS_TL_FPS_PER_POINT', 2.5 );
}

function cs_tier_pages() {
	return array(
		'gpu-tier-list' => __( 'GPU Tier List 2026: Every Graphics Card Ranked for Gaming', 'computingskill' ),
		'cpu-tier-list' => __( 'CPU Tier List 2026: Gaming Processors Ranked', 'computingskill' ),
	);
}

/**
 * Creates each page once, published, with the guide template. A page that
 * already exists (or was deleted after being created) is never touched.
 */
function cs_tier_pages_create() {
	if ( wp_installing() ) {
		return;
	}
	$done = (array) get_option( 'cs_tier_pages_created', array() );
	$todo = array_diff( array_keys( cs_tier_pages() ), $done );
	if ( empty( $todo ) ) {
		return;
	}
	if ( ! add_option( 'cs_tier_pages_lock', time(), '', 'no' ) ) {
		if ( time() - (int) get_option( 'cs_tier_pages_lock' ) > 300 ) {
			delete_option( 'cs_tier_pages_lock' );
		}
		return;
	}
	$ref    = get_page_by_path( 'methodology' );
	$author = $ref ? (int) $ref->post_author : 0;
	$titles = cs_tier_pages();
	foreach ( $todo as $slug ) {
		if ( ! get_page_by_path( $slug ) ) {
			$id = wp_insert_post( array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'post_name'      => $slug,
				'post_title'     => $titles[ $slug ],
				'post_author'    => $author,
				'post_content'   => '[cs_tier_list type="' . ( 'gpu-tier-list' === $slug ? 'gpu' : 'cpu' ) . '"]',
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
			), true );
			if ( is_wp_error( $id ) ) {
				continue;
			}
			update_post_meta( $id, '_wp_page_template', 'page-templates/template-guide.php' );
		}
		$done[] = $slug;
	}
	update_option( 'cs_tier_pages_created', array_values( array_unique( $done ) ), true );
	delete_option( 'cs_tier_pages_lock' );
}
add_action( 'init', 'cs_tier_pages_create', 30 );

/* ------------------------------------------------------------------ */
/* Data                                                                */
/* ------------------------------------------------------------------ */

/**
 * The PC Upgrade Calculator's GPU score targets per resolution.
 */
function cs_tl_gpu_targets() {
	$t = function_exists( 'cs_bn_upgrade_targets' ) ? cs_bn_upgrade_targets() : array();
	return isset( $t['gpu'] ) ? $t['gpu'] : array( '1080p' => 33, '1440p' => 50, '4k' => 70 );
}

/**
 * GPU tiers, highest first: letter => [ min score, label ].
 */
function cs_tl_gpu_tiers() {
	$t = cs_tl_gpu_targets();
	return array(
		'S' => array( 85, __( 'Fastest cards: 4K at high settings with room to spare', 'computingskill' ) ),
		'A' => array( $t['4k'], __( '4K gaming at high settings', 'computingskill' ) ),
		'B' => array( $t['1440p'], __( '1440p gaming at high settings', 'computingskill' ) ),
		'C' => array( $t['1080p'], __( '1080p gaming at high settings', 'computingskill' ) ),
		'D' => array( 0, __( 'Older or entry cards: 1080p at medium or low settings', 'computingskill' ) ),
	);
}

/**
 * CPU tiers, highest first. A-C match the CPU Performance Calculator's
 * Flagship / High-end / Mid-range thresholds (calculators.js).
 */
function cs_tl_cpu_tiers() {
	return array(
		'S' => array( 85, __( 'Fastest gaming CPUs (large-cache X3D chips)', 'computingskill' ) ),
		'A' => array( 77, __( 'Flagship: a match for flagship cards like the RTX 4090', 'computingskill' ) ),
		'B' => array( 66, __( 'High-end: a strong match for high-end cards', 'computingskill' ) ),
		'C' => array( 54, __( 'Mid-range: fine for mid-range cards', 'computingskill' ) ),
		'D' => array( 0, __( 'Entry-level: pair with budget cards', 'computingskill' ) ),
	);
}

function cs_tl_tier_of( $score, $tiers ) {
	foreach ( $tiers as $letter => $t ) {
		if ( $score >= $t[0] ) {
			return $letter;
		}
	}
	return 'D';
}

/**
 * The CPU Performance Calculator's own label for a score.
 */
function cs_tl_cpu_label( $score ) {
	return $score >= 77 ? __( 'Flagship', 'computingskill' ) : ( $score >= 66 ? __( 'High-end', 'computingskill' ) : ( $score >= 54 ? __( 'Mid-range', 'computingskill' ) : __( 'Entry-level', 'computingskill' ) ) );
}

function cs_tl_sorted( $parts ) {
	uasort( $parts, function ( $a, $b ) {
		return $b['score'] - $a['score'] ?: strcmp( $a['name'], $b['name'] );
	} );
	return $parts;
}

/**
 * FPS Calculator estimate (CS_DEFS.fps): the lower of the GPU's and the
 * CPU's frame-rate ceilings. Returns [ fps, 'cpu'|'gpu' limiter ].
 */
function cs_tl_fps( $cpu, $gpu, $res, $preset = 'high', $game = 'aaa' ) {
	$profiles = cs_bottleneck_game_profiles();
	$profile  = $profiles[ $game ];
	$gpu_fps  = $gpu['score'] * CS_TL_FPS_PER_POINT * cs_bottleneck_gpu_res_factors()[ $res ] * cs_bottleneck_preset_factors()[ $preset ] * $profile['gpu'] * cs_bottleneck_gpu_memory_factor( $gpu, $res, $preset, $game );
	$cpu_cap  = $cpu['score'] * CS_BN_CPU_PARITY * CS_TL_FPS_PER_POINT * $profile['cpu'];
	return $gpu_fps <= $cpu_cap ? array( $gpu_fps, 'gpu' ) : array( $cpu_cap, 'cpu' );
}

/**
 * Fastest CPU in the dataset, used as the "no CPU limit" reference for the
 * GPU FPS columns.
 */
function cs_tl_top_cpu() {
	$cpus = cs_tl_sorted( cs_get_cpu_data() );
	$slug = key( $cpus );
	return array( $slug, $cpus[ $slug ] );
}

/**
 * The fastest GPU in the dataset a CPU drives at a resolution without a
 * noticeable CPU bottleneck (engine result is balanced, GPU-limited or a
 * mild CPU bottleneck under 15%), typical AAA game at high settings.
 */
function cs_tl_cpu_max_gpu( $cpu, $res = '1440p' ) {
	foreach ( cs_tl_sorted( cs_get_gpu_data() ) as $g_slug => $gpu ) {
		$r = cs_calculate_bottleneck( $cpu, $gpu )[ $res ];
		if ( 'cpu' !== $r['component'] || $r['percent'] < 15 ) {
			return array( $g_slug, $gpu );
		}
	}
	return array( '', null );
}

function cs_tl_names( $parts, $max = 0 ) {
	$names = array();
	foreach ( $parts as $p ) {
		$names[] = cs_bn_short_name( $p['name'] );
	}
	if ( $max && count( $names ) > $max ) {
		return implode( ', ', array_slice( $names, 0, $max ) );
	}
	return function_exists( 'cs_bn_join' ) ? cs_bn_join( $names ) : implode( ', ', $names );
}

function cs_tl_link( $slug, $text ) {
	$page = get_page_by_path( $slug );
	if ( ! $page || 'publish' !== $page->post_status ) {
		return esc_html( $text );
	}
	return '<a href="' . esc_url( get_permalink( $page ) ) . '">' . esc_html( $text ) . '</a>';
}

/* ------------------------------------------------------------------ */
/* Output                                                              */
/* ------------------------------------------------------------------ */

function cs_tier_list_shortcode( $atts ) {
	$atts = shortcode_atts( array( 'type' => 'gpu' ), $atts, 'cs_tier_list' );
	return 'cpu' === $atts['type'] ? cs_tl_cpu_html() : cs_tl_gpu_html();
}
add_shortcode( 'cs_tier_list', 'cs_tier_list_shortcode' );

function cs_tl_quick_answer( $text ) {
	return '<div class="cs-quick-answer"><p class="cs-quick-answer-label">' . esc_html__( 'Quick answer', 'computingskill' ) . '</p><div class="cs-quick-answer-body"><p>' . $text . '</p></div></div>';
}

function cs_tl_faq( $items ) {
	$sc = '[cs_faq]';
	foreach ( $items as $q => $a ) {
		$sc .= '[cs_faq_item q="' . esc_attr( $q ) . '"]' . $a . '[/cs_faq_item]';
	}
	return do_shortcode( $sc . '[/cs_faq]' );
}

function cs_tl_tier_summary( $by_tier, $tiers, $what ) {
	$html  = '<div class="cs-table-wrap"><table class="cs-hardware-table cs-tier-table"><caption>' . esc_html( sprintf( __( '%s tiers at a glance', 'computingskill' ), $what ) ) . '</caption>';
	$html .= '<thead><tr><th>' . esc_html__( 'Tier', 'computingskill' ) . '</th><th>' . esc_html__( 'Score', 'computingskill' ) . '</th><th>' . esc_html__( 'What it means', 'computingskill' ) . '</th><th>' . esc_html( $what ) . '</th></tr></thead><tbody>';
	$prev  = null;
	foreach ( $tiers as $letter => $t ) {
		$range = null === $prev ? $t[0] . '+' : ( $t[0] ? $t[0] . '–' . ( $prev - 1 ) : sprintf( __( 'under %d', 'computingskill' ), $prev ) );
		$prev  = $t[0];
		$parts = isset( $by_tier[ $letter ] ) ? $by_tier[ $letter ] : array();
		$html .= '<tr><td><strong class="cs-tier cs-tier-' . esc_attr( strtolower( $letter ) ) . '">' . esc_html( $letter ) . '</strong></td><td>' . esc_html( $range ) . '</td><td>' . esc_html( $t[1] ) . '</td><td>' . esc_html( $parts ? cs_tl_names( $parts ) : '—' ) . '</td></tr>';
	}
	return $html . '</tbody></table></div>';
}

function cs_tl_gpu_html() {
	$gpus  = cs_tl_sorted( cs_get_gpu_data() );
	$tiers = cs_tl_gpu_tiers();
	list( $top_cpu_slug, $top_cpu ) = cs_tl_top_cpu();

	$by_tier = array();
	foreach ( $gpus as $slug => $g ) {
		$by_tier[ cs_tl_tier_of( $g['score'], $tiers ) ][ $slug ] = $g;
	}
	$first = reset( $gpus );
	$keys  = array_keys( $gpus );
	$t     = cs_tl_gpu_targets();
	$vram  = array( '1080p' => 8, '1440p' => 12, '4k' => 16 ); // GPU VRAM Calculator's comfortable figures.
	$res_l = array( '1080p' => '1080p', '1440p' => '1440p', '4k' => '4K' );

	$fits = array();
	foreach ( $t as $res => $min ) {
		foreach ( $gpus as $slug => $g ) {
			if ( $g['score'] >= $min && $g['vram_gb'] >= $vram[ $res ] ) {
				$fits[ $res ][ $slug ] = $g;
			}
		}
	}
	$entry = function ( $res ) use ( $fits ) {
		$list = isset( $fits[ $res ] ) ? $fits[ $res ] : array();
		uasort( $list, function ( $a, $b ) {
			return $a['score'] - $b['score'];
		} );
		return $list;
	};

	$html = cs_tl_quick_answer( sprintf(
		/* translators: 1-3: GPU names, 4: count */
		esc_html__( 'The fastest gaming graphics card in our list is the %1$s, followed by the %2$s and the %3$s. Our tier list ranks %4$d GPUs by ComputingSkill’s gaming score (RTX 4090 = 100): S and A tier cards handle 4K, B tier is for 1440p, C tier for 1080p at high settings and D tier for 1080p at lower settings.', 'computingskill' ),
		esc_html( cs_bn_short_name( $first['name'] ) ),
		esc_html( cs_bn_short_name( $gpus[ $keys[1] ]['name'] ) ),
		esc_html( cs_bn_short_name( $gpus[ $keys[2] ]['name'] ) ),
		count( $gpus )
	) );

	$html .= '<p>' . sprintf(
		/* translators: 1: methodology link, 2: date */
		esc_html__( 'Every graphics card in ComputingSkill’s hardware database, ranked for gaming. The score is our relative gaming-performance index: average rasterized throughput at high settings, with the RTX 4090 at 100. It is positioned from publicly reported review averages and the makers’ published specs; we do not run our own benchmarks. %1$s explains how the scores are built. The same scores drive the Bottleneck, FPS and GPU Performance calculators, so this list always agrees with them.', 'computingskill' ),
		cs_tl_link( 'methodology', __( 'Our methodology', 'computingskill' ) )
	) . '</p>';

	$html .= '<h2>' . esc_html__( 'How the GPU Tiers Work', 'computingskill' ) . '</h2>';
	$html .= '<p>' . sprintf(
		/* translators: 1-3: score thresholds, 4: link */
		esc_html__( 'The tier lines are the same performance levels the %4$s uses: a score of %1$d or more for 1080p at high settings, %2$d for 1440p and %3$d for 4K. S tier is the small group well above the 4K line.', 'computingskill' ),
		(int) $t['1080p'], (int) $t['1440p'], (int) $t['4k'],
		cs_tl_link( 'pc-upgrade-calculator', __( 'PC Upgrade Calculator', 'computingskill' ) )
	) . '</p>';
	$html .= cs_tl_tier_summary( $by_tier, $tiers, __( 'Graphics cards', 'computingskill' ) );

	// Full ranking.
	$html .= '<h2>' . esc_html__( 'Full GPU Ranking', 'computingskill' ) . '</h2>';
	$html .= '<p>' . sprintf(
		/* translators: %s: CPU name */
		esc_html__( 'Estimated average FPS is the FPS Calculator’s figure for a typical AAA game at high settings, paired with the %s (the fastest gaming CPU in our list) so the card is the limit wherever it can be. Cells marked * are held back by the CPU instead. Esports titles run several times faster; ray tracing, upscaling and frame generation are not included.', 'computingskill' ),
		esc_html( cs_bn_short_name( $top_cpu['name'] ) )
	) . '</p>';
	$html .= '<div class="cs-table-wrap"><table class="cs-hardware-table cs-tier-ranking"><caption>' . esc_html__( 'GPU ranking by gaming score', 'computingskill' ) . '</caption><thead><tr>';
	foreach ( array( '#', __( 'Graphics card', 'computingskill' ), __( 'Tier', 'computingskill' ), __( 'Score', 'computingskill' ), __( 'VRAM', 'computingskill' ), __( 'FPS 1080p', 'computingskill' ), __( 'FPS 1440p', 'computingskill' ), __( 'FPS 4K', 'computingskill' ), __( 'Board power', 'computingskill' ), __( 'Released', 'computingskill' ) ) as $h ) {
		$html .= '<th scope="col">' . esc_html( $h ) . '</th>';
	}
	$html .= '</tr></thead><tbody>';
	$i = 0;
	foreach ( $gpus as $slug => $g ) {
		$i++;
		$html .= '<tr><td>' . $i . '</td><td>' . ( function_exists( 'cs_pp_part_link' ) ? cs_pp_part_link( $slug, $g ) : esc_html( cs_bn_short_name( $g['name'] ) ) ) . '</td><td><strong class="cs-tier cs-tier-' . esc_attr( strtolower( cs_tl_tier_of( $g['score'], $tiers ) ) ) . '">' . esc_html( cs_tl_tier_of( $g['score'], $tiers ) ) . '</strong></td><td>' . (int) $g['score'] . '</td><td>' . esc_html( $g['vram_gb'] . 'GB ' . $g['memory_type'] ) . '</td>';
		foreach ( array_keys( $res_l ) as $res ) {
			list( $fps, $lim ) = cs_tl_fps( $top_cpu, $g, $res );
			$html .= '<td>' . (int) round( $fps ) . ( 'cpu' === $lim ? '*' : '' ) . '</td>';
		}
		$html .= '<td>' . (int) $g['tdp_w'] . 'W</td><td>' . (int) $g['release_year'] . '</td></tr>';
	}
	$html .= '</tbody></table></div>';

	// Best by resolution.
	$html .= '<h2>' . esc_html__( 'Best GPU for 1080p, 1440p and 4K', 'computingskill' ) . '</h2>';
	$html .= '<p>' . sprintf(
		/* translators: %s: link */
		esc_html__( 'A card suits a resolution here when it clears that resolution’s tier line and has the VRAM the %s calls comfortable for it (8GB at 1080p, 12GB at 1440p, 16GB at 4K). Prices change too often to rank by value, so each list starts with the lowest-scoring card that qualifies and ends with the fastest.', 'computingskill' ),
		cs_tl_link( 'gpu-vram-calculator', __( 'GPU VRAM Calculator', 'computingskill' ) )
	) . '</p>';
	foreach ( $res_l as $res => $label ) {
		$list = $entry( $res );
		if ( ! $list ) {
			continue;
		}
		$low  = reset( $list );
		$high = end( $list );
		$html .= '<h3>' . esc_html( sprintf( __( 'Best GPU for %s gaming', 'computingskill' ), $label ) ) . '</h3>';
		$html .= '<p>' . esc_html( sprintf(
			/* translators: 1: resolution, 2: count, 3: entry card, 4: fastest card, 5: names */
			count( $list ) > 10 ? __( '%2$d cards meet both bars for %1$s. The most modest is the %3$s and the fastest is the %4$s. The entry options, slowest first: %5$s, then every faster card in the ranking above with enough VRAM.', 'computingskill' ) : __( '%2$d cards meet both bars for %1$s. The most modest is the %3$s and the fastest is the %4$s. All of them, slowest first: %5$s.', 'computingskill' ),
			$label,
			count( $list ),
			cs_bn_short_name( $low['name'] ),
			cs_bn_short_name( $high['name'] ),
			cs_tl_names( $list, 10 )
		) ) . '</p>';
		$short = array();
		foreach ( $gpus as $g ) {
			if ( $g['score'] >= $t[ $res ] && $g['vram_gb'] < $vram[ $res ] ) {
				$short[] = $g;
			}
		}
		if ( $short ) {
			$html .= '<p>' . esc_html( sprintf(
				/* translators: 1: names, 2: resolution, 3: GB */
				__( 'Fast enough but short on VRAM for %2$s (under %3$dGB): %1$s. They run it, with less headroom for high-resolution textures in newer games.', 'computingskill' ),
				cs_tl_names( $short ),
				$label,
				$vram[ $res ]
			) ) . '</p>';
		}
	}

	$html .= '<h2>' . esc_html__( 'Sources and Limits', 'computingskill' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'Specs (VRAM, memory, board power, release year) come from each maker’s official product page, as listed in our hardware database. Scores are positioned from publicly reported review averages, for example TechSpot’s and GamersNexus’s published GPU reviews, and anchored on the RTX 4090 at 100. They are an average across games: a single game can favour one brand or need more VRAM, and ray tracing changes the order.', 'computingskill' ) . '</p>';

	$html .= cs_tl_faq( array(
		__( 'What is the best GPU for gaming right now?', 'computingskill' ) => sprintf( __( 'In our list the fastest is the %1$s (score %2$d, RTX 4090 = 100). For most people a B or A tier card is the better buy: B tier handles 1440p at high settings and A tier handles 4K.', 'computingskill' ), cs_bn_short_name( $first['name'] ), (int) $first['score'] ),
		__( 'What GPU tier do I need for 1440p?', 'computingskill' ) => sprintf( __( 'B tier or higher: a score of %1$d or more, the same level the PC Upgrade Calculator uses for 1440p. Pick one with at least 12GB of VRAM for comfortable headroom; the most modest card that meets both is the %2$s.', 'computingskill' ), (int) $t['1440p'], ( $l = $entry( '1440p' ) ) ? cs_bn_short_name( reset( $l )['name'] ) : '' ),
		__( 'Is this tier list based on benchmarks?', 'computingskill' ) => __( 'Not our own. The scores are ComputingSkill’s relative gaming index, positioned from publicly reported review averages and the makers’ specs. We do not test cards ourselves, and the methodology page explains how the index is built.', 'computingskill' ),
		__( 'Why does a card show a * in the FPS columns?', 'computingskill' ) => __( 'The * means even the fastest CPU in our list would hold that card back at that resolution in a typical AAA game, so the figure is the CPU’s ceiling, not the card’s. That happens with the fastest cards at 1080p.', 'computingskill' ),
	) );

	return $html;
}

function cs_tl_cpu_html() {
	$cpus  = cs_tl_sorted( cs_get_cpu_data() );
	$tiers = cs_tl_cpu_tiers();
	$pairs = function_exists( 'cs_bn_published_pairs' ) ? cs_bn_published_pairs() : array();

	$by_tier = array();
	foreach ( $cpus as $slug => $c ) {
		$by_tier[ cs_tl_tier_of( $c['score'], $tiers ) ][ $slug ] = $c;
	}
	$keys  = array_keys( $cpus );
	$first = $cpus[ $keys[0] ];

	$html = cs_tl_quick_answer( sprintf(
		/* translators: 1-3: CPU names, 4: count */
		esc_html__( 'The fastest gaming CPU in our list is the %1$s, followed by the %2$s and the %3$s. Our tier list ranks %4$d processors by ComputingSkill’s gaming score, which measures how many frames per second a CPU can prepare in games, not multi-core work: large-cache X3D chips lead, and core count matters less than you might expect.', 'computingskill' ),
		esc_html( cs_bn_short_name( $first['name'] ) ),
		esc_html( cs_bn_short_name( $cpus[ $keys[1] ]['name'] ) ),
		esc_html( cs_bn_short_name( $cpus[ $keys[2] ]['name'] ) ),
		count( $cpus )
	) );

	$html .= '<p>' . sprintf(
		/* translators: 1: methodology link, 2: date */
		esc_html__( 'Every processor in ComputingSkill’s hardware database, ranked for gaming. The score is our gaming index: the frame rate a CPU can sustain when the graphics card isn’t the limit. It is positioned from publicly reported review averages (for example TechSpot’s 45-game tests) and the makers’ specs; we do not run our own benchmarks. %1$s explains how it is built. The same scores drive the Bottleneck, FPS and CPU Performance calculators, so this list always agrees with them.', 'computingskill' ),
		cs_tl_link( 'methodology', __( 'Our methodology', 'computingskill' ) )
	) . '</p>';

	$html .= '<h2>' . esc_html__( 'How the CPU Tiers Work', 'computingskill' ) . '</h2>';
	$html .= '<p>' . sprintf(
		/* translators: %s: link */
		esc_html__( 'Tiers A to D use the same lines as the %s (Flagship 77+, High-end 66+, Mid-range 54+, Entry-level below that). S tier is the group of large-cache chips well above the rest.', 'computingskill' ),
		cs_tl_link( 'cpu-performance-calculator', __( 'CPU Performance Calculator', 'computingskill' ) )
	) . '</p>';
	$html .= cs_tl_tier_summary( $by_tier, $tiers, __( 'Processors', 'computingskill' ) );

	$html .= '<h2>' . esc_html__( 'Full CPU Ranking', 'computingskill' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'The FPS ceiling is the FPS Calculator’s CPU limit: the average frame rate the CPU can prepare in a typical AAA game, and in esports titles, whatever the graphics card. “Fastest GPU at 1440p” is the quickest card in our list it runs at 1440p high settings without a noticeable CPU bottleneck (under 15% in the PC Bottleneck Calculator).', 'computingskill' ) . '</p>';
	$html .= '<div class="cs-table-wrap"><table class="cs-hardware-table cs-tier-ranking"><caption>' . esc_html__( 'CPU ranking by gaming score', 'computingskill' ) . '</caption><thead><tr>';
	foreach ( array( '#', __( 'Processor', 'computingskill' ), __( 'Tier', 'computingskill' ), __( 'Score', 'computingskill' ), __( 'Cores / threads', 'computingskill' ), __( 'Socket', 'computingskill' ), __( 'FPS ceiling, AAA', 'computingskill' ), __( 'FPS ceiling, esports', 'computingskill' ), __( 'Fastest GPU at 1440p', 'computingskill' ), __( 'Released', 'computingskill' ) ) as $h ) {
		$html .= '<th scope="col">' . esc_html( $h ) . '</th>';
	}
	$html .= '</tr></thead><tbody>';
	$profiles = cs_bottleneck_game_profiles();
	$i        = 0;
	foreach ( $cpus as $slug => $c ) {
		$i++;
		$letter = cs_tl_tier_of( $c['score'], $tiers );
		$base   = $c['score'] * CS_BN_CPU_PARITY * CS_TL_FPS_PER_POINT;
		list( $g_slug, $g ) = cs_tl_cpu_max_gpu( $c );
		$gpu_cell = $g ? esc_html( cs_bn_short_name( $g['name'] ) ) : '—';
		if ( $g && isset( $pairs[ $slug . '|' . $g_slug ] ) ) {
			$gpu_cell = '<a href="' . esc_url( $pairs[ $slug . '|' . $g_slug ] ) . '">' . $gpu_cell . '</a>';
		}
		$html .= '<tr><td>' . $i . '</td><td>' . ( function_exists( 'cs_pp_part_link' ) ? cs_pp_part_link( $slug, $c ) : esc_html( cs_bn_short_name( $c['name'] ) ) ) . '</td><td><strong class="cs-tier cs-tier-' . esc_attr( strtolower( $letter ) ) . '">' . esc_html( $letter ) . '</strong></td><td>' . (int) $c['score'] . '</td><td>' . (int) $c['cores'] . ' / ' . (int) $c['threads'] . '</td><td>' . esc_html( $c['socket'] ) . '</td><td>' . (int) round( $base * $profiles['aaa']['cpu'] ) . '</td><td>' . (int) round( $base * $profiles['esports']['cpu'] ) . '</td><td>' . $gpu_cell . '</td><td>' . (int) $c['release_year'] . '</td></tr>';
	}
	$html .= '</tbody></table></div>';

	// Best per platform.
	$by_socket = array();
	foreach ( $cpus as $slug => $c ) {
		$by_socket[ $c['socket'] ][ $slug ] = $c;
	}
	$html .= '<h2>' . esc_html__( 'Best Gaming CPU for Each Platform', 'computingskill' ) . '</h2>';
	$html .= '<p>' . sprintf(
		/* translators: %s: link */
		esc_html__( 'If you are keeping your motherboard, only CPUs for its socket fit (the %s checks this, including BIOS updates). The fastest gaming CPU in our list for each socket:', 'computingskill' ),
		cs_tl_link( 'pc-compatibility-checker', __( 'PC Compatibility Checker', 'computingskill' ) )
	) . '</p><ul>';
	uasort( $by_socket, function ( $a, $b ) {
		return reset( $b )['score'] - reset( $a )['score'];
	} );
	foreach ( $by_socket as $socket => $list ) {
		$best   = reset( $list );
		$others = array_slice( $list, 1, 2 );
		$html  .= '<li><strong>' . esc_html( $socket ) . ':</strong> ' . esc_html( sprintf(
			/* translators: 1: CPU, 2: score, 3: next names */
			__( '%1$s (score %2$d)', 'computingskill' ),
			cs_bn_short_name( $best['name'] ),
			(int) $best['score']
		) ) . ( $others ? esc_html( sprintf( __( ', then the %s', 'computingskill' ), cs_tl_names( $others ) ) ) : '' ) . '</li>';
	}
	$html .= '</ul>';

	$html .= '<h2>' . esc_html__( 'Sources and Limits', 'computingskill' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'Cores, threads, socket and release year come from AMD’s and Intel’s official product pages, as listed in our hardware database. Scores are positioned from publicly reported review averages, anchored on the Ryzen 5 5600 at 52. They rank gaming only: video export, compiling and other multi-threaded work can order these chips very differently, and a single game can favour one design.', 'computingskill' ) . '</p>';

	$second = $cpus[ $keys[1] ];
	$html  .= cs_tl_faq( array(
		__( 'What is the best CPU for gaming?', 'computingskill' ) => sprintf( __( 'In our list the %1$s (score %2$d) is the fastest gaming CPU, with the %3$s (score %4$d) close behind. Their large 3D V-Cache is what puts them ahead of chips with more cores.', 'computingskill' ), cs_bn_short_name( $first['name'] ), (int) $first['score'], cs_bn_short_name( $second['name'] ), (int) $second['score'] ),
		__( 'Do more cores mean better gaming performance?', 'computingskill' ) => __( 'Not past a point. Most games lean on a few fast cores and on cache, which is why 8-core X3D chips outrank 24-core parts in this list. Extra cores help streaming, video export and other multi-threaded work.', 'computingskill' ),
		__( 'Which CPU tier do I need for my graphics card?', 'computingskill' ) => __( 'Use the “Fastest GPU at 1440p” column: any card up to the one listed runs without a noticeable CPU bottleneck at 1440p. At 4K the graphics card does more of the work, so a lower tier CPU goes further; at 1080p you need a faster CPU for the same card. The PC Bottleneck Calculator checks an exact pair.', 'computingskill' ),
		__( 'Is this CPU tier list based on benchmarks?', 'computingskill' ) => __( 'Not our own. The scores are ComputingSkill’s gaming index, positioned from publicly reported review averages and the makers’ specs. We do not test CPUs ourselves.', 'computingskill' ),
	) );

	return $html;
}

/**
 * Dataset node on the tier list pages, so the scores are described as the
 * site's own data table (with the page as the citation target).
 */
function cs_tl_dataset_schema( $graphs ) {
	if ( ! is_singular( 'page' ) || ! in_array( get_post_field( 'post_name' ), array_keys( cs_tier_pages() ), true ) ) {
		return $graphs;
	}
	$gpu      = 'gpu-tier-list' === get_post_field( 'post_name' );
	$graphs[] = array(
		'@type'         => 'Dataset',
		'@id'           => get_permalink() . '#dataset',
		'name'          => $gpu ? __( 'ComputingSkill GPU gaming scores', 'computingskill' ) : __( 'ComputingSkill CPU gaming scores', 'computingskill' ),
		'description'   => $gpu
			? sprintf( __( 'Relative gaming-performance index for %d graphics cards (RTX 4090 = 100), with tier, VRAM, board power, release year and estimated FPS at 1080p, 1440p and 4K.', 'computingskill' ), count( cs_get_gpu_data() ) )
			: sprintf( __( 'Gaming-performance index for %d processors (the frame rate a CPU can prepare in games), with tier, cores, threads, socket and FPS ceilings.', 'computingskill' ), count( cs_get_cpu_data() ) ),
		'url'           => get_permalink(),
		'creator'       => array( '@id' => cs_org_schema_id() ),
		'author'        => array( '@id' => cs_site_author()['id'] ),
		'dateModified'  => get_the_modified_date( 'c' ),
		'isAccessibleForFree' => true,
		'keywords'      => $gpu ? array( 'GPU tier list', 'graphics card ranking', 'gaming GPU benchmark index' ) : array( 'CPU tier list', 'gaming CPU ranking' ),
		'measurementTechnique' => __( 'Positioned from publicly reported review averages and manufacturer specifications; not ComputingSkill’s own benchmarks.', 'computingskill' ),
	);
	return $graphs;
}
add_filter( 'cs_schema_graphs', 'cs_tl_dataset_schema' );
