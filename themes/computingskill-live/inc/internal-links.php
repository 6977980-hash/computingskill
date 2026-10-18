<?php
/**
 * Deep audit D7 (Oct 2026): internal links, near-duplicates and pages that
 * compete for the same searches (audit items 2.16, 2.17, 2.19-2.22).
 *
 *  - Combo pages link to game pages (results by game type) and to the
 *    hardware explainers; game pages link to combo pages (this game's type
 *    on pairings across budgets); hardware pages and calculators link to
 *    combos and games (2.16).
 *  - Every calculator, tool, hardware page and guide links its own hub in
 *    the body, and the low-linked pages get links from their topic group
 *    (2.17).
 *  - The eight hubs get a "which one do you need" section, and stale
 *    sentences on them are corrected (2.19).
 *  - Combo pages whose CPU has a near-twin on the same GPU get a section on
 *    choosing between the two (2.20).
 *  - Overlapping pages stay separate (Ali, 18 Oct 2026: no merges). Each
 *    topic group gets a "which page do you need" box, and /ram/, /psu/ and
 *    /gpu/ become "What is" explainers so the guides own the "how much" and
 *    "how to choose" searches (2.21).
 *  - /blog/ lists articles (posts, guides, comparisons) instead of
 *    repeating every hub (2.22).
 *
 * Every number comes from cs_calculate_bottleneck(), so it matches the
 * combo pages and the calculators.
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'CS_LINKS_REV' ) ) {
	define( 'CS_LINKS_REV', '2026-10-18-d7' );
}

/**
 * Game type labels as the calculators name them.
 */
function cs_il_game_type_labels() {
	return array(
		'aaa'       => __( 'Typical AAA games', 'computingskill' ),
		'esports'   => __( 'Esports games', 'computingskill' ),
		'cpu-heavy' => __( 'CPU-heavy games', 'computingskill' ),
	);
}

/**
 * Lower-cases the first letter for use mid-sentence, unless the first word
 * is an acronym ("CPU-heavy", "GPU-limited").
 */
function cs_il_lc( $text ) {
	return preg_match( '/^[A-Z]{2}/', $text ) ? $text : lcfirst( $text );
}

/**
 * Game slugs grouped by the profile the FPS Calculator uses for them
 * (games without 'profile' are typical AAA).
 */
function cs_il_games_by_type() {
	$out = array( 'aaa' => array(), 'esports' => array(), 'cpu-heavy' => array() );
	foreach ( cs_get_games_data() as $slug => $g ) {
		$type = isset( $g['profile'], $out[ $g['profile'] ] ) ? $g['profile'] : 'aaa';
		$out[ $type ][ $slug ] = $g;
	}
	return $out;
}

/**
 * Game pages that are live, slug => URL.
 */
function cs_il_published_games() {
	static $games = null;
	if ( null !== $games ) {
		return $games;
	}
	$games = array();
	$posts = get_posts( array(
		'post_type'      => 'cs_game',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'no_found_rows'  => true,
	) );
	foreach ( $posts as $post ) {
		$games[ $post->post_name ] = get_permalink( $post );
	}
	return $games;
}

/**
 * Links to up to $max live game pages of one type, "A, B and C".
 */
function cs_il_game_links( $type, $max = 3, $offset = 0 ) {
	$live  = cs_il_published_games();
	$games = cs_il_games_by_type()[ $type ];
	$keys  = array_keys( $games );
	$n     = count( $keys );
	$links = array();
	for ( $i = 0; $i < $n && count( $links ) < $max; $i++ ) {
		$slug = $keys[ ( $i + $offset ) % $n ];
		if ( isset( $live[ $slug ] ) ) {
			$links[] = '<a href="' . esc_url( $live[ $slug ] ) . '">' . esc_html( $games[ $slug ]['name'] ) . '</a>';
		}
	}
	return $links ? cs_bn_join( $links ) : '';
}

/**
 * Combo page section: the same pair for each game type, with the game pages
 * of that type linked (2.16 combo -> game, and unique numbers per pair for
 * 2.20).
 */
function cs_il_combo_game_types_html( $cpu_slug, $gpu_slug, $cpu, $gpu ) {
	$labels  = cs_il_game_type_labels();
	$short   = cs_bn_res_short();
	$offset  = abs( crc32( $cpu_slug . $gpu_slug ) );
	$results = array();
	foreach ( array_keys( $labels ) as $type ) {
		$results[ $type ] = cs_calculate_bottleneck( $cpu, $gpu, array( 'game' => $type ) );
	}

	// One sentence on where the game type matters most for this pair.
	$e = $results['esports']['1080p'];
	$h = $results['cpu-heavy']['1080p'];
	$a = $results['aaa']['1080p'];
	if ( 'cpu' === $a['component'] ) {
		$lead = sprintf(
			/* translators: 1: CPU, 2-4: result labels */
			__( 'The %1$s already limits a typical AAA game at 1080p (%2$s), and the game type moves that number: %3$s in esports games and %4$s in CPU-heavy games.', 'computingskill' ),
			$cpu['name'],
			cs_il_lc( cs_bn_label( $a ) ),
			cs_il_lc( cs_bn_label( $e ) ),
			cs_il_lc( cs_bn_label( $h ) )
		);
	} elseif ( 'cpu' === $e['component'] || 'cpu' === $h['component'] ) {
		$worst = ( 'cpu' === $h['component'] && ( 'cpu' !== $e['component'] || $h['percent'] >= $e['percent'] ) ) ? 'cpu-heavy' : 'esports';
		$lead  = sprintf(
			/* translators: 1: CPU, 2: game type label, 3: result label, 4: result label */
			__( 'The game type changes the picture most at 1080p: in %2$s the %1$s becomes the limit (%3$s), while a typical AAA game at the same resolution reads %4$s.', 'computingskill' ),
			$cpu['name'],
			cs_il_lc( $labels[ $worst ] ),
			cs_il_lc( cs_bn_label( $results[ $worst ]['1080p'] ) ),
			cs_il_lc( cs_bn_label( $a ) )
		);
	} else {
		$lead = sprintf(
			/* translators: 1: CPU, 2: GPU */
			__( 'Even in esports and CPU-heavy games the %1$s keeps up with the %2$s at 1080p, so the graphics card sets the frame rate in every game type below.', 'computingskill' ),
			$cpu['name'],
			$gpu['name']
		);
	}

	ob_start();
	?>
	<section class="cs-bn-gametypes">
		<h2><?php echo esc_html( sprintf( __( '%1$s + %2$s by Game Type', 'computingskill' ), cs_bn_short_name( $cpu['name'] ), cs_bn_short_name( $gpu['name'] ) ) ); ?></h2>
		<p><?php echo esc_html( $lead ); ?></p>
		<div class="cs-table-wrap">
			<table class="cs-hardware-table cs-bn-gametype-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Game type', 'computingskill' ); ?></th>
						<?php foreach ( $short as $label ) : ?>
							<th><?php echo esc_html( $label ); ?></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $labels as $type => $label ) : ?>
						<?php $games = cs_il_game_links( $type, 3, $offset ); ?>
						<tr>
							<td>
								<strong><?php echo esc_html( $label ); ?></strong>
								<?php if ( $games ) : ?>
									<br><span class="cs-bn-gametype-games"><?php echo wp_kses_post( sprintf( __( 'e.g. %s', 'computingskill' ), $games ) ); ?></span>
								<?php endif; ?>
							</td>
							<?php foreach ( array_keys( $short ) as $res ) : ?>
								<td><?php echo esc_html( cs_bn_cell( $results[ $type ][ $res ] ) ); ?></td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<p class="cs-disclaimer"><?php echo wp_kses_post( sprintf(
			/* translators: %s: link to the games archive */
			__( 'High settings throughout. Game types are rough classes, not per-game benchmarks: the linked pages list each game’s official requirements (%s).', 'computingskill' ),
			'<a href="' . esc_url( home_url( '/games/' ) ) . '">' . esc_html__( 'all games we cover', 'computingskill' ) . '</a>'
		) ); ?></p>
	</section>
	<?php
	return ob_get_clean();
}

/**
 * The CPU closest in score to this one that has a live page with the same
 * GPU, when the two are near-twins (within 8 points): slug, or ''.
 */
function cs_il_twin_cpu( $cpu_slug, $gpu_slug ) {
	$cpus = cs_get_cpu_data();
	$best = '';
	$gap  = 9;
	foreach ( array_keys( cs_bn_published_pairs() ) as $key ) {
		list( $c, $g ) = explode( '|', $key );
		if ( $g !== $gpu_slug || $c === $cpu_slug || ! isset( $cpus[ $c ] ) ) {
			continue;
		}
		$d = abs( $cpus[ $c ]['score'] - $cpus[ $cpu_slug ]['score'] );
		if ( $d < $gap ) {
			$gap  = $d;
			$best = $c;
		}
	}
	return $best;
}

/**
 * CPU bottleneck % of a result, 0 when balanced or GPU-limited.
 */
function cs_il_cpu_pct( $r ) {
	return 'cpu' === $r['component'] ? (int) $r['percent'] : 0;
}

/**
 * Combo page section for near-twin CPUs on the same GPU (2.20: 5600 vs
 * 5600X + RTX 4060 shared 78% of their text). States, from the engine, at
 * which resolutions and game types the two give a different result.
 */
function cs_il_combo_twin_html( $cpu_slug, $gpu_slug, $cpu, $gpu ) {
	// Pages merged into this one come first: this page is now their answer.
	$merged = function_exists( 'cs_il_merged_twins' ) ? cs_il_merged_twins( $cpu_slug . '-' . $gpu_slug ) : array();
	if ( $merged ) {
		$html = '';
		foreach ( $merged as $twin_slug ) {
			$html .= cs_il_combo_twin_section( $cpu_slug, $gpu_slug, $cpu, $gpu, $twin_slug, '' );
		}
		return $html;
	}
	$twin_slug = cs_il_twin_cpu( $cpu_slug, $gpu_slug );
	if ( '' === $twin_slug ) {
		return '';
	}
	$pairs = cs_bn_published_pairs();
	return cs_il_combo_twin_section( $cpu_slug, $gpu_slug, $cpu, $gpu, $twin_slug, $pairs[ $twin_slug . '|' . $gpu_slug ] );
}

/**
 * One "X or Y for this GPU?" section; $url '' when Y's page was merged here.
 */
function cs_il_combo_twin_section( $cpu_slug, $gpu_slug, $cpu, $gpu, $twin_slug, $url ) {
	$cpus   = cs_get_cpu_data();
	$twin   = $cpus[ $twin_slug ];
	$short  = cs_bn_res_short();
	$labels = cs_il_game_type_labels();

	$diffs = array();
	foreach ( $labels as $type => $label ) {
		$mine   = cs_calculate_bottleneck( $cpu, $gpu, array( 'game' => $type ) );
		$theirs = cs_calculate_bottleneck( $twin, $gpu, array( 'game' => $type ) );
		foreach ( $short as $res => $res_label ) {
			// A real gap only: 8+ points of CPU bottleneck (GPU-limited and
			// balanced count as 0), not a 29% vs 30% band edge.
			if ( abs( cs_il_cpu_pct( $mine[ $res ] ) - cs_il_cpu_pct( $theirs[ $res ] ) ) >= 8 ) {
				$diffs[] = array( 'type' => $type, 'res' => $res, 'mine' => $mine[ $res ], 'theirs' => $theirs[ $res ] );
			}
		}
	}

	$faster    = $cpu['score'] >= $twin['score'] ? $cpu : $twin;
	$slower    = $cpu['score'] >= $twin['score'] ? $twin : $cpu;
	$score_gap = abs( $cpu['score'] - $twin['score'] );
	$gap_pct   = $slower['score'] > 0 ? (int) round( $score_gap / $slower['score'] * 100 ) : 0;

	if ( 0 === $score_gap ) {
		$intro = sprintf(
			/* translators: 1: this CPU, 2: other CPU, 3: GPU */
			__( 'The %1$s and the %2$s have the same gaming score in our database, so with the %3$s they give identical estimates in every game type and resolution. Pick between them on price, platform and cooler, not on bottleneck.', 'computingskill' ),
			$cpu['name'], $twin['name'], $gpu['name']
		);
	} else {
		$intro = sprintf(
			/* translators: 1: faster CPU, 2: slower CPU, 3: percent, 4: GPU */
			__( 'The %1$s scores about %3$d%% higher than the %2$s in our gaming index. With the %4$s that gap can only matter where the CPU is the limit.', 'computingskill' ),
			$faster['name'], $slower['name'], $gap_pct, $gpu['name']
		);
	}

	ob_start();
	?>
	<section class="cs-bn-twin">
		<h2><?php echo esc_html( sprintf( __( '%1$s or %2$s for the %3$s?', 'computingskill' ), cs_bn_short_name( $cpu['name'] ), cs_bn_short_name( $twin['name'] ), cs_bn_short_name( $gpu['name'] ) ) ); ?></h2>
		<p><?php echo esc_html( $intro ); ?></p>
		<?php if ( $score_gap ) : ?>
			<?php if ( $diffs ) : ?>
				<p><?php esc_html_e( 'The gap shows here:', 'computingskill' ); ?></p>
				<ul>
					<?php foreach ( $diffs as $d ) : ?>
						<li><?php echo esc_html( sprintf(
							/* translators: 1: game type, 2: resolution, 3: this CPU, 4: result, 5: other CPU, 6: result */
							__( '%1$s at %2$s: %3$s %4$s, %5$s %6$s.', 'computingskill' ),
							$labels[ $d['type'] ], $short[ $d['res'] ],
							cs_bn_short_name( $cpu['name'] ), cs_il_lc( cs_bn_cell( $d['mine'] ) ),
							cs_bn_short_name( $twin['name'] ), cs_il_lc( cs_bn_cell( $d['theirs'] ) )
						) ); ?></li>
					<?php endforeach; ?>
				</ul>
				<p><?php echo esc_html( sprintf(
					/* translators: 1: faster CPU, 2: slower CPU */
					__( 'Everywhere else the two stay within a few points of each other. The %1$s is worth paying more for only if you play the game types above at those resolutions, or plan a faster graphics card later; otherwise the %2$s gets the same result from this card.', 'computingskill' ),
					$faster['name'], $slower['name']
				) ); ?></p>
			<?php else : ?>
				<p><?php echo esc_html( sprintf(
					/* translators: 1: slower CPU, 2: GPU, 3: faster CPU */
					__( 'Here the two stay within a few points of each other in every game type and resolution we estimate, so the %1$s already gets nearly everything the %2$s can deliver. The %3$s mainly adds headroom for a faster graphics card later.', 'computingskill' ),
					$slower['name'], $gpu['name'], $faster['name']
				) ); ?></p>
			<?php endif; ?>
		<?php endif; ?>
		<?php if ( $url ) : ?>
			<p><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( sprintf( __( 'See the full %1$s + %2$s estimate', 'computingskill' ), $twin['name'], $gpu['name'] ) ); ?></a></p>
		<?php else : ?>
			<p><?php echo esc_html( sprintf(
				/* translators: 1: other CPU, 2: GPU */
				__( 'The %1$s + %2$s lands within a few points of this page’s estimates in every game type and resolution, so this page covers both pairings.', 'computingskill' ),
				$twin['name'], $gpu['name']
			) ); ?></p>
		<?php endif; ?>
	</section>
	<?php
	return ob_get_clean();
}

/**
 * Posts written about a CPU (e.g. best-gpu-for-ryzen-5-5600 covers the
 * 5600 and 5600X), matched on the slug: [ [title, url], ... ].
 */
function cs_il_posts_for_cpu( $cpu_slug ) {
	static $posts = null;
	if ( null === $posts ) {
		$posts = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 50, 'no_found_rows' => true ) );
	}
	$base = preg_replace( '/x$/', '', $cpu_slug );
	$out  = array();
	foreach ( $posts as $post ) {
		if ( preg_match( '/(^|-)' . preg_quote( $base, '/' ) . 'x?($|-)/', $post->post_name ) ) {
			$out[] = array( get_the_title( $post ), get_permalink( $post ) );
		}
	}
	return $out;
}

/**
 * Combo page: links to the hardware explainers next to the spec lists.
 */
function cs_il_combo_specs_links( $gpu, $cpu_slug = '', $gpu_slug = '', $cpu = null ) {
	$links = array(
		'<a href="' . esc_url( home_url( '/cpu/' ) ) . '">' . esc_html__( 'what CPU cores, clocks and TDP mean', 'computingskill' ) . '</a>',
		'<a href="' . esc_url( home_url( '/gpu/' ) ) . '">' . esc_html__( 'how a graphics card works', 'computingskill' ) . '</a>',
		'<a href="' . esc_url( home_url( '/vram/' ) ) . '">' . esc_html( sprintf( __( 'what %dGB of VRAM is for', 'computingskill' ), (int) $gpu['vram_gb'] ) ) . '</a>',
		'<a href="' . esc_url( home_url( '/psu/' ) ) . '">' . esc_html__( 'how PSU ratings work', 'computingskill' ) . '</a>',
	);
	$html = '<p class="cs-bn-learn">' . esc_html__( 'New to these specs? Read', 'computingskill' ) . ' ' . cs_bn_join( $links ) . '.</p>';
	// D10 (5.4): each part's own page.
	if ( $cpu && function_exists( 'cs_pp_part_url' ) && ( cs_pp_part_url( $cpu_slug ) || cs_pp_part_url( $gpu_slug ) ) ) {
		$parts = array();
		foreach ( array( array( $cpu_slug, $cpu ), array( $gpu_slug, $gpu ) ) as $p ) {
			if ( cs_pp_part_url( $p[0] ) ) {
				$parts[] = cs_pp_part_link( $p[0], $p[1], sprintf( __( 'the %s page', 'computingskill' ), cs_bn_short_name( $p[1]['name'] ) ) );
			}
		}
		$html .= '<p class="cs-bn-learn">' . esc_html__( 'Full specs, other pairings and PSU advice for each part:', 'computingskill' ) . ' ' . cs_bn_join( $parts ) . '.</p>';
	}
	return $html;
}

/**
 * Live combo pages spread across GPU budgets, rotated by $seed so pages
 * that show this list link to different combos: [ key => url ].
 */
function cs_il_combo_sample( $count, $seed, $filter = null ) {
	$cpus  = cs_get_cpu_data();
	$gpus  = cs_get_gpu_data();
	$pairs = array();
	foreach ( cs_bn_published_pairs() as $key => $url ) {
		list( $c, $g ) = explode( '|', $key );
		if ( ! isset( $cpus[ $c ], $gpus[ $g ] ) || ( $filter && ! call_user_func( $filter, $c, $g ) ) ) {
			continue;
		}
		$pairs[ $key ] = $gpus[ $g ]['score'] * 1000 + $cpus[ $c ]['score'];
	}
	if ( ! $pairs ) {
		return array();
	}
	asort( $pairs );
	$keys  = array_keys( $pairs );
	$n     = count( $keys );
	$count = min( $count, $n );
	$step  = $n / $count;
	$shift = abs( crc32( (string) $seed ) ) % max( 1, (int) floor( $step ) );
	$out   = array();
	$all   = cs_bn_published_pairs();
	for ( $i = 0; $i < $count; $i++ ) {
		$key         = $keys[ min( $n - 1, (int) floor( $i * $step ) + $shift ) ];
		$out[ $key ] = $all[ $key ];
	}
	return $out;
}

/**
 * Game page section: this game's type on combo pages across budgets (2.16
 * game -> combo), plus the FPS Calculator pre-set to the game.
 */
function cs_il_game_combos_html( $slug, $g ) {
	$type   = isset( $g['profile'] ) && isset( cs_il_game_type_labels()[ $g['profile'] ] ) ? $g['profile'] : 'aaa';
	$label  = cs_il_game_type_labels()[ $type ];
	$sample = cs_il_combo_sample( 6, $slug );
	if ( ! $sample ) {
		return '';
	}
	$cpus  = cs_get_cpu_data();
	$gpus  = cs_get_gpu_data();
	$short = cs_bn_res_short();
	unset( $short['4k'] );
	$fps = add_query_arg( 'game', $slug, home_url( '/fps-calculator/' ) );

	ob_start();
	?>
	<section class="cs-game-combos">
		<h2><?php echo esc_html( sprintf( __( '%s on Common CPU + GPU Pairings', 'computingskill' ), $g['name'] ) ); ?></h2>
		<p><?php echo esc_html( sprintf(
			/* translators: 1: game, 2: game type label (lower case) */
			__( 'The Bottleneck Calculator treats %1$s as one of the %2$s. Here is which part would set the frame rate on %3$d pairings from budget to high-end at high settings; each pairing links to its full page.', 'computingskill' ),
			$g['name'], cs_il_lc( $label ), count( $sample )
		) ); ?></p>
		<div class="cs-table-wrap">
			<table class="cs-hardware-table cs-game-combo-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'CPU + GPU', 'computingskill' ); ?></th>
						<?php foreach ( $short as $res_label ) : ?>
							<th><?php echo esc_html( $res_label ); ?></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $sample as $key => $url ) : ?>
						<?php
						list( $c, $gp ) = explode( '|', $key );
						$r              = cs_calculate_bottleneck( $cpus[ $c ], $gpus[ $gp ], array( 'game' => $type ) );
						?>
						<tr>
							<td><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( cs_bn_short_name( $cpus[ $c ]['name'] ) . ' + ' . cs_bn_short_name( $gpus[ $gp ]['name'] ) ); ?></a></td>
							<?php foreach ( array_keys( $short ) as $res ) : ?>
								<td><?php echo esc_html( cs_bn_cell( $r[ $res ] ) ); ?></td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<p>
			<a href="<?php echo esc_url( $fps ); ?>" rel="nofollow"><?php echo esc_html( sprintf( __( 'Estimate %s FPS for your own CPU and GPU', 'computingskill' ), $g['name'] ) ); ?></a>
			&middot;
			<a href="<?php echo esc_url( home_url( '/can-i-run-it/' ) ); ?>"><?php esc_html_e( 'Check every game against your PC', 'computingskill' ); ?></a>
			&middot;
			<a href="<?php echo esc_url( home_url( '/bottleneck/' ) ); ?>"><?php esc_html_e( 'All CPU + GPU comparisons', 'computingskill' ); ?></a>
		</p>
		<p class="cs-bn-learn"><?php echo wp_kses_post( sprintf(
			/* translators: links */
			__( 'What the parts in the table above do: %s.', 'computingskill' ),
			cs_bn_join( array(
				'<a href="' . esc_url( home_url( '/cpu/' ) ) . '">' . esc_html__( 'CPU', 'computingskill' ) . '</a>',
				'<a href="' . esc_url( home_url( '/gpu/' ) ) . '">' . esc_html__( 'GPU', 'computingskill' ) . '</a>',
				'<a href="' . esc_url( home_url( '/ram/' ) ) . '">' . esc_html__( 'RAM', 'computingskill' ) . '</a>',
				'<a href="' . esc_url( home_url( '/ssd/' ) ) . '">' . esc_html__( 'SSD', 'computingskill' ) . '</a>',
			) )
		) ); ?></p>
	</section>
	<?php
	return ob_get_clean();
}

/* ------------------------------------------------------------------ */
/* Topic groups that overlap (2.21) and hubs (2.17)                   */
/* ------------------------------------------------------------------ */

/**
 * Pages that answer neighbouring questions: one box per group says which
 * page is for what. Order = order shown.
 */
function cs_il_topic_groups() {
	return array(
		'ram'        => array(
			'ram'                                            => __( 'what RAM does and which specs (speed, timings, channels) matter', 'computingskill' ),
			'how-much-ram-do-i-need'                         => __( 'how many GB you need for browsing, gaming, creating or development', 'computingskill' ),
			'how-much-ram-do-i-need-for-gaming-and-streaming' => __( 'gaming and live streaming on one PC at the same time', 'computingskill' ),
			'ram-calculator'                                 => __( 'a GB figure for your own mix of apps', 'computingskill' ),
		),
		'psu'        => array(
			'psu'                => __( 'how a power supply works and what its ratings mean', 'computingskill' ),
			'how-to-choose-a-psu' => __( 'step-by-step buying: wattage headroom, 80 PLUS, cables, GPU connectors', 'computingskill' ),
			'psu-calculator'     => __( 'the wattage your own parts need', 'computingskill' ),
		),
		'gpu'        => array(
			'gpu'                        => __( 'how a graphics card works and what its specs mean', 'computingskill' ),
			'how-to-choose-a-gaming-gpu' => __( 'step-by-step buying by budget, resolution, VRAM and power', 'computingskill' ),
			'gpu-performance-calculator' => __( 'where a specific card sits on our score scale', 'computingskill' ),
		),
		'bottleneck' => array(
			'pc-bottleneck-calculator' => __( 'any CPU + GPU pair, with game type and graphics settings', 'computingskill' ),
			'bottleneck'               => __( 'ready-made results for common pairs, side by side', 'computingskill' ),
			'cpu-vs-gpu-calculator'    => __( 'a quick look at the two scores and the balance, no settings', 'computingskill' ),
			'cpu-bottleneck-explained' => __( 'what a CPU bottleneck is and how to spot one', 'computingskill' ),
		),
		'speed'      => array(
			'mbps-to-mb-s-converter' => __( 'turning an advertised plan speed (Mbps) into file speed (MB/s)', 'computingskill' ),
			'mb-s-to-mbps-converter' => __( 'turning a measured transfer speed (MB/s) back into Mbps', 'computingskill' ),
		),
		'transfer'   => array(
			'download-time-calculator'      => __( 'how long a download takes on your internet plan', 'computingskill' ),
			'upload-time-calculator'        => __( 'how long an upload or cloud backup takes on your upload speed', 'computingskill' ),
			'file-transfer-time-calculator' => __( 'copies over USB, Wi-Fi, Ethernet or between drives', 'computingskill' ),
			'bandwidth-calculator'          => __( 'the speed you need to move data in a set time', 'computingskill' ),
		),
		'ssd'        => array(
			'ssd-lifespan-calculator' => __( 'how many years a drive lasts at your daily writes', 'computingskill' ),
			'ssd-tbw-calculator'      => __( 'converting a DWPD rating to TBW (or back) to compare drives', 'computingskill' ),
		),
	);
}

/**
 * Title shown for a page in the boxes: the index title, else the post title.
 */
function cs_il_page_title( $slug ) {
	foreach ( cs_content_index_groups() as $group ) {
		foreach ( $group['items'] as $item ) {
			if ( $item['slug'] === $slug ) {
				return $item['title'];
			}
		}
	}
	$page = get_page_by_path( $slug );
	return $page ? get_the_title( $page ) : $slug;
}

function cs_il_topic_box_html( $slug ) {
	foreach ( cs_il_topic_groups() as $group ) {
		if ( ! isset( $group[ $slug ] ) ) {
			continue;
		}
		ob_start();
		?>
		<aside class="cs-related cs-topic-box">
			<h2><?php esc_html_e( 'Which Page Do You Need?', 'computingskill' ); ?></h2>
			<ul>
				<?php foreach ( $group as $other => $use ) : ?>
					<?php $page = get_page_by_path( $other ); ?>
					<?php if ( ! $page || 'publish' !== $page->post_status ) continue; ?>
					<li>
						<?php if ( $other === $slug ) : ?>
							<strong><?php echo esc_html( cs_il_page_title( $other ) ); ?></strong> (<?php esc_html_e( 'this page', 'computingskill' ); ?>):
						<?php else : ?>
							<a href="<?php echo esc_url( get_permalink( $page ) ); ?>"><?php echo esc_html( cs_il_page_title( $other ) ); ?></a>:
						<?php endif; ?>
						<?php echo esc_html( $use ); ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</aside>
		<?php
		return ob_get_clean();
	}
	return '';
}

/**
 * Extra links per page, for the pages the crawl found with no combo, game or
 * hardware links (2.16) or few inbound links (2.17). Slugs are resolved by
 * cs_related_shortcode(), so a missing page is skipped.
 */
function cs_il_context_links() {
	return array(
		'cpu'             => array( __( 'Put It to Use', 'computingskill' ), 'pc-bottleneck-calculator,cpu-tier-list,cpu-performance-calculator,cpu-bottleneck-explained,motherboard,cpu-cooler', 'combos' ),
		'gpu'             => array( __( 'Put It to Use', 'computingskill' ), 'how-to-choose-a-gaming-gpu,gpu-tier-list,best-gpu-for-ryzen-5-5600,gpu-bottleneck-explained,cyberpunk-2077,red-dead-redemption-2,starfield', 'combos' ),
		'gpu-tier-list'   => array( __( 'Check a Card in Your PC', 'computingskill' ), 'fps-calculator,pc-bottleneck-calculator,gpu-performance-calculator,how-to-choose-a-gaming-gpu,how-much-vram-do-i-need,cpu-tier-list', 'combos' ),
		'cpu-tier-list'   => array( __( 'Check a CPU in Your PC', 'computingskill' ), 'pc-bottleneck-calculator,fps-calculator,cpu-performance-calculator,pc-compatibility-checker,cpu-bottleneck-explained,gpu-tier-list', 'combos' ),
		'vram'            => array( __( 'VRAM in Practice', 'computingskill' ), 'how-much-vram-do-i-need,gpu-vram-calculator,cyberpunk-2077,starfield,elden-ring,gta-5-enhanced' ),
		'ram'             => array( __( 'RAM in Practice', 'computingskill' ), 'ddr4-vs-ddr5,call-of-duty-warzone,baldurs-gate-3,helldivers-2' ),
		'psu'             => array( __( 'Power in Practice', 'computingskill' ), 'pc-power-consumption-calculator,gpu-power-consumption-calculator,pc-compatibility-checker' ),
		'ssd'             => array( __( 'Storage in Practice', 'computingskill' ), 'nvme-vs-sata,ssd-vs-hdd,ssd-lifespan-calculator,ssd-tbw-calculator,storage-calculator,call-of-duty-warzone,baldurs-gate-3' ),
		'hdd'             => array( __( 'Storage in Practice', 'computingskill' ), 'ssd-vs-hdd,storage-calculator,data-storage-converter,ssd' ),
		'motherboard'     => array( __( 'Before You Buy a Board', 'computingskill' ), 'pc-compatibility-checker,ddr4-vs-ddr5,cpu,ram' ),
		'pc-case'         => array( __( 'Before You Buy a Case', 'computingskill' ), 'pc-compatibility-checker,pc-cooling-calculator,cpu-cooler' ),
		'cpu-cooler'      => array( __( 'Sizing Your Cooling', 'computingskill' ), 'pc-cooling-calculator,cpu-power-consumption-calculator,pc-case' ),
		'fps-calculator'  => array( __( 'Game Requirements', 'computingskill' ), 'games', 'combos' ),
		'cpu-vs-gpu-calculator'      => array( '', '', 'combos' ),
		'gpu-performance-calculator' => array( '', '', 'combos' ),
		'cpu-performance-calculator' => array( '', '', 'combos' ),
		'pc-upgrade-calculator'      => array( '', '', 'combos' ),
		'gpu-vram-calculator'        => array( '', '', 'combos' ),
		'psu-calculator'             => array( '', '', 'combos' ),
		'pc-build-cost-calculator'   => array( '', '', 'combos' ),
		'refresh-rate-calculator'    => array( __( 'Matching FPS to Your Monitor', 'computingskill' ), 'fps-calculator,how-to-improve-fps,1080p-vs-1440p-vs-4k' ),
		'dpi-calculator'             => array( __( 'Screen Sharpness', 'computingskill' ), 'ppi-calculator,resolution-calculator' ),
		'ppi-calculator'             => array( __( 'More Screen Maths', 'computingskill' ), 'dpi-calculator,monitor-size-calculator,refresh-rate-calculator' ),
		'how-to-improve-fps'         => array( __( 'Check Your Numbers', 'computingskill' ), 'fps-calculator,refresh-rate-calculator,pc-bottleneck-calculator' ),
		'1080p-vs-1440p-vs-4k'       => array( __( 'Size Up Your Monitor', 'computingskill' ), 'ppi-calculator,monitor-size-calculator,refresh-rate-calculator,dpi-calculator' ),
		'download-time-calculator'   => array( __( 'Unit Converters', 'computingskill' ), 'mbps-to-mb-s-converter,mb-s-to-mbps-converter,internet-data-usage-calculator' ),
		'how-much-ram-do-i-need'     => array( __( 'Games and Their RAM Needs', 'computingskill' ), 'call-of-duty-warzone,baldurs-gate-3,cyberpunk-2077,minecraft' ),
	);
}

/**
 * The hub a page belongs to, from the content index: [label, url] or null.
 */
function cs_il_page_hub( $slug ) {
	foreach ( cs_content_index_groups() as $group ) {
		foreach ( $group['items'] as $item ) {
			if ( $item['slug'] === $slug ) {
				$hub = get_page_by_path( trim( $group['archive'], '/' ) );
				return $hub ? array( $group['label'], get_permalink( $hub ) ) : null;
			}
		}
	}
	return null;
}

/**
 * Printed under the content of calculator, tool, hardware and guide pages
 * (the templates call it after cs_maybe_print_bottleneck_index()).
 */
function cs_maybe_print_context_links() {
	if ( ! is_singular( 'page' ) ) {
		return;
	}
	$slug = get_post_field( 'post_name' );

	echo cs_il_topic_box_html( $slug ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside.

	$map = cs_il_context_links();
	if ( isset( $map[ $slug ] ) ) {
		list( $title, $links ) = $map[ $slug ];
		$combos                = isset( $map[ $slug ][2] );
		if ( 'games' === $links ) {
			$links = implode( ',', array_keys( cs_il_published_games() ) );
		}
		if ( $links ) {
			echo cs_related_shortcode( array( 'title' => $title, 'links' => $links ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside.
		}
		if ( $combos ) {
			$sample = cs_il_combo_sample( 8, $slug );
			if ( $sample ) {
				$cpus = cs_get_cpu_data();
				$gpus = cs_get_gpu_data();
				echo '<div class="cs-related cs-combo-sample"><h2>' . esc_html__( 'Example CPU + GPU Pairings', 'computingskill' ) . '</h2><ul class="cs-related-grid">';
				foreach ( $sample as $key => $url ) {
					list( $c, $g ) = explode( '|', $key );
					echo '<li><a href="' . esc_url( $url ) . '">' . esc_html( cs_bn_short_name( $cpus[ $c ]['name'] ) . ' + ' . cs_bn_short_name( $gpus[ $g ]['name'] ) ) . '</a></li>';
				}
				echo '</ul><p class="cs-related-more"><a href="' . esc_url( home_url( '/bottleneck/' ) ) . '">' . esc_html( sprintf( __( 'All %d CPU + GPU comparisons →', 'computingskill' ), count( cs_bn_published_pairs() ) ) ) . '</a></p></div>';
			}
		}
	}

	// Every calculator, tool, hardware page and guide links its own hub and,
	// for the ones that show estimates, the methodology (2.17).
	$hub   = cs_il_page_hub( $slug );
	$parts = array();
	if ( $hub ) {
		$parts[] = sprintf(
			/* translators: %s: hub link */
			__( 'More like this: %s.', 'computingskill' ),
			'<a href="' . esc_url( $hub[1] ) . '">' . esc_html( sprintf( __( 'all %s', 'computingskill' ), $hub[0] ) ) . '</a>'
		);
	}
	if ( is_page_template( 'page-templates/template-calculator.php' ) && get_page_by_path( 'methodology' ) ) {
		$parts[] = sprintf(
			/* translators: %s: link */
			__( 'How the numbers are worked out: %s.', 'computingskill' ),
			'<a href="' . esc_url( home_url( '/methodology/' ) ) . '">' . esc_html__( 'our methodology', 'computingskill' ) . '</a>'
		);
	}
	if ( $parts ) {
		echo '<p class="cs-hub-backlink">' . wp_kses_post( implode( ' ', $parts ) ) . '</p>';
	}
}

/* ------------------------------------------------------------------ */
/* Page text: explainers vs guides (2.21), hubs (2.19)                */
/* ------------------------------------------------------------------ */

/**
 * New titles for the hardware explainers that competed with a guide: post
 * title (H1) and title tag. The one-time job writes the post titles.
 */
function cs_il_explainer_titles() {
	return array(
		'ram' => array( __( 'What Is RAM? How Memory Works and Which Specs Matter', 'computingskill' ), __( 'What Is RAM? How Memory Works & Which Specs Matter', 'computingskill' ), 'RAM: What It Is & How Much You Need' ),
		'psu' => array( __( 'What Is a PSU? How a Power Supply Works and Its Ratings', 'computingskill' ), __( 'What Is a PSU? How Power Supplies Work & Their Ratings', 'computingskill' ), 'PSU (Power Supply): What It Is & How to Choose One' ),
		'gpu' => array( __( 'What Is a GPU? How a Graphics Card Works and Its Key Specs', 'computingskill' ), __( 'What Is a GPU? How Graphics Cards Work & Key Specs', 'computingskill' ), 'GPU: What It Is & How to Choose One' ),
	);
}

/**
 * Sentence placed under the "How to Choose" heading of each explainer, so
 * the full buying answer clearly lives on the guide.
 */
function cs_il_explainer_pointers() {
	return array(
		'ram' => array( 'How to Choose the Right Amount of RAM', sprintf(
			/* translators: 1, 2, 3: links */
			__( 'The short version is below. For GB figures by use case see %1$s, for streaming while gaming see %2$s, and for your own mix of apps use the %3$s.', 'computingskill' ),
			'<a href="/how-much-ram-do-i-need/">' . esc_html__( 'How Much RAM Do I Need?', 'computingskill' ) . '</a>',
			'<a href="/how-much-ram-do-i-need-for-gaming-and-streaming/">' . esc_html__( 'RAM for gaming and streaming', 'computingskill' ) . '</a>',
			'<a href="/ram-calculator/">' . esc_html__( 'RAM Calculator', 'computingskill' ) . '</a>'
		) ),
		'psu' => array( 'How to Choose the Right PSU', sprintf(
			/* translators: 1, 2: links */
			__( 'The short version is below. The full step-by-step is in %1$s, and the %2$s gives the wattage for your parts.', 'computingskill' ),
			'<a href="/how-to-choose-a-psu/">' . esc_html__( 'How to Choose a PSU', 'computingskill' ) . '</a>',
			'<a href="/psu-calculator/">' . esc_html__( 'PSU Calculator', 'computingskill' ) . '</a>'
		) ),
		'gpu' => array( 'How to Choose the Right GPU', sprintf(
			/* translators: 1, 2: links */
			__( 'The short version is below. The full step-by-step is in %1$s, and %2$s shows how cards pair with common CPUs.', 'computingskill' ),
			'<a href="/how-to-choose-a-gaming-gpu/">' . esc_html__( 'How to Choose a Gaming GPU', 'computingskill' ) . '</a>',
			'<a href="/bottleneck/">' . esc_html__( 'our CPU + GPU comparisons', 'computingskill' ) . '</a>'
		) ),
	);
}

/**
 * Guides point back to the explainer once, after their first paragraph.
 */
function cs_il_guide_pointers() {
	return array(
		'how-to-choose-a-psu'        => array( 'psu', __( 'What Is a PSU?', 'computingskill' ), __( 'If you want to know how a power supply works first, start with %s.', 'computingskill' ) ),
		'how-to-choose-a-gaming-gpu' => array( 'gpu', __( 'What Is a GPU?', 'computingskill' ), __( 'If you want to know how a graphics card works first, start with %s.', 'computingskill' ) ),
		'how-much-ram-do-i-need'     => array( 'ram', __( 'What Is RAM?', 'computingskill' ), __( 'For how memory works and which specs matter, see %s.', 'computingskill' ) ),
	);
}

/**
 * Hub additions (2.19): a "which one do you need" list, written per hub,
 * question => slug. The links also feed the hub ItemList (D6).
 */
function cs_il_hub_guides() {
	return array(
		'calculators'    => array(
			'intro' => __( 'Not sure where to start? Match your question to the tool:', 'computingskill' ),
			'items' => array(
				'pc-bottleneck-calculator'  => __( 'Will my CPU hold back my graphics card?', 'computingskill' ),
				'fps-calculator'            => __( 'Roughly what frame rate will my parts reach in a game?', 'computingskill' ),
				'psu-calculator'            => __( 'How many watts does my power supply need?', 'computingskill' ),
				'pc-compatibility-checker'  => __( 'Do my parts fit and work together?', 'computingskill' ),
				'pc-upgrade-calculator'     => __( 'Which upgrade helps my PC most?', 'computingskill' ),
				'ram-calculator'            => __( 'How much RAM do I need for what I run?', 'computingskill' ),
				'gpu-vram-calculator'       => __( 'Is my card’s VRAM enough for my resolution?', 'computingskill' ),
				'pc-build-cost-calculator'  => __( 'What will this build cost and where does the money go?', 'computingskill' ),
				'pc-power-consumption-calculator' => __( 'What does my PC cost to run in electricity?', 'computingskill' ),
				'pc-cooling-calculator'     => __( 'Is air cooling enough or do I need an AIO?', 'computingskill' ),
			),
			'outro' => __( 'Every estimate starts from published specs, and the CPU and GPU tools share one score scale, so the bottleneck, FPS, upgrade and comparison pages never disagree about the same parts.', 'computingskill' ),
		),
		'computer-tools' => array(
			'intro' => __( 'The questions people bring here most often, and the tool that answers each:', 'computingskill' ),
			'items' => array(
				'download-time-calculator' => __( 'How long will this download take on my plan?', 'computingskill' ),
				'mbps-to-mb-s-converter'   => __( 'Why does my 100 Mbps plan download at 12 MB/s?', 'computingskill' ),
				'data-storage-converter'   => __( 'Why does a 1TB drive show as 931GB?', 'computingskill' ),
				'ssd-lifespan-calculator'  => __( 'How many years will my SSD last?', 'computingskill' ),
				'ppi-calculator'           => __( 'How sharp is this monitor at its size?', 'computingskill' ),
				'monitor-size-calculator'  => __( 'Will this monitor fit on my desk?', 'computingskill' ),
			),
			'outro' => __( 'None of these need hardware knowledge: each page shows the formula it uses, so you can check the arithmetic yourself.', 'computingskill' ),
		),
		'storage-tools'  => array(
			'intro' => __( 'Which storage tool answers your question:', 'computingskill' ),
			'items' => array(
				'storage-calculator'      => __( 'Will my files, games or backups fit on this drive?', 'computingskill' ),
				'data-storage-converter'  => __( 'How many GB in a TB, and why Windows shows less?', 'computingskill' ),
				'ssd-lifespan-calculator' => __( 'How many years will my SSD last at my daily writes?', 'computingskill' ),
				'ssd-tbw-calculator'      => __( 'A drive lists DWPD, another TBW: how do they compare?', 'computingskill' ),
				'nvme-vs-sata'            => __( 'Is an NVMe drive worth it over SATA?', 'computingskill' ),
				'ssd-vs-hdd'              => __( 'SSD or hard drive for this job?', 'computingskill' ),
			),
			'outro' => __( 'Game installs are a common reason to plan space: each game requirements page lists the publisher’s install size.', 'computingskill' ),
		),
		'internet-tools' => array(
			'intro' => __( 'Which internet tool answers your question:', 'computingskill' ),
			'items' => array(
				'download-time-calculator'       => __( 'How long will a game or file take to download?', 'computingskill' ),
				'upload-time-calculator'         => __( 'How long will a video upload or cloud backup take?', 'computingskill' ),
				'file-transfer-time-calculator'  => __( 'How long will a copy over USB, Wi-Fi or Ethernet take?', 'computingskill' ),
				'bandwidth-calculator'           => __( 'What speed do I need to move this much data in time?', 'computingskill' ),
				'internet-data-usage-calculator' => __( 'Will my streaming and gaming fit under my data cap?', 'computingskill' ),
				'mbps-to-mb-s-converter'         => __( 'What is my plan’s speed in MB/s?', 'computingskill' ),
				'mb-s-to-mbps-converter'         => __( 'What is a measured MB/s speed in Mbps?', 'computingskill' ),
			),
			'outro' => __( 'Upload speed is often a fraction of download speed on home plans, so check the upload figure on your plan before timing a backup.', 'computingskill' ),
		),
		'display-tools'  => array(
			'intro' => __( 'Which display tool answers your question:', 'computingskill' ),
			'items' => array(
				'ppi-calculator'          => __( 'How sharp is a screen at its size and resolution?', 'computingskill' ),
				'monitor-size-calculator' => __( 'How wide and tall is a 27" or 34" monitor?', 'computingskill' ),
				'resolution-calculator'   => __( 'What is 2560 × 1440 called, and how many megapixels?', 'computingskill' ),
				'aspect-ratio-calculator' => __( 'Is this resolution 16:9, 21:9 or something else?', 'computingskill' ),
				'refresh-rate-calculator' => __( 'What frame time does 144Hz or 240Hz give?', 'computingskill' ),
				'dpi-calculator'          => __( 'Will this photo print sharp at this size?', 'computingskill' ),
				'1080p-vs-1440p-vs-4k'    => __( 'Which resolution should my next monitor be?', 'computingskill' ),
			),
			'outro' => __( 'Resolution also decides how hard your graphics card works: the FPS Calculator and the bottleneck pages show the same parts at 1080p, 1440p and 4K.', 'computingskill' ),
		),
		'hardware'       => array(
			'intro' => __( 'Each page explains one part. If you have a buying question rather than a “what is it” question, these pages answer it directly:', 'computingskill' ),
			'items' => array(
				'how-to-choose-a-gaming-gpu' => __( 'Which graphics card should I buy?', 'computingskill' ),
				'gpu-tier-list'              => __( 'Which graphics cards are fastest for gaming?', 'computingskill' ),
				'cpu-tier-list'              => __( 'Which CPUs are fastest for gaming?', 'computingskill' ),
				'how-to-choose-a-psu'        => __( 'Which power supply should I buy?', 'computingskill' ),
				'how-much-ram-do-i-need'     => __( 'How much RAM should I get?', 'computingskill' ),
				'how-much-vram-do-i-need'    => __( 'How much VRAM do I need?', 'computingskill' ),
				'pc-compatibility-checker'   => __( 'Will these parts work together?', 'computingskill' ),
				'bottleneck'                 => __( 'How do common CPUs and GPUs pair up?', 'computingskill' ),
			),
			'outro' => __( 'The calculators these pages link to use specs from the makers’ own product pages; our methodology page explains how they turn them into estimates.', 'computingskill' ),
		),
		'guides'         => array(
			'intro' => __( 'Pick a guide by the problem you have:', 'computingskill' ),
			'items' => array(
				'how-to-improve-fps'                             => __( 'My games stutter or run below my monitor’s refresh rate.', 'computingskill' ),
				'how-to-upgrade-a-gaming-pc'                     => __( 'I have a budget and want the upgrade that helps most.', 'computingskill' ),
				'cpu-bottleneck-explained'                       => __( 'My GPU usage sits well below 100% in games.', 'computingskill' ),
				'how-to-choose-a-gaming-gpu'                     => __( 'I am buying a graphics card.', 'computingskill' ),
				'how-much-ram-do-i-need-for-gaming-and-streaming' => __( 'I want to stream while I play.', 'computingskill' ),
				'how-much-vram-do-i-need'                        => __( 'I am moving to 1440p or 4K.', 'computingskill' ),
			),
			'outro' => __( 'Each guide links to the calculator that puts its advice into numbers for your own parts.', 'computingskill' ),
		),
		'comparisons'    => array(
			'intro' => __( 'The short answer to each comparison, before you read the full page:', 'computingskill' ),
			'items' => array(
				'ssd-vs-hdd'            => __( 'An SSD for Windows and games; a hard drive only for bulk storage and backups.', 'computingskill' ),
				'nvme-vs-sata'          => __( 'NVMe if your board has an M.2 slot that supports it; SATA mainly to reuse or add to older PCs.', 'computingskill' ),
				'ddr4-vs-ddr5'          => __( 'Your CPU and motherboard decide it: AM5 and Intel Core Ultra boards take DDR5 only, AM4 takes DDR4, and Intel 12th-14th gen boards come in either version.', 'computingskill' ),
				'1080p-vs-1440p-vs-4k'  => __( 'Match the resolution to the graphics card you can afford to drive it.', 'computingskill' ),
				'cpu-vs-gpu-calculator' => __( 'For a specific pair of parts, compare their scores directly.', 'computingskill' ),
				'gpu-tier-list'         => __( 'Every graphics card ranked from S to D tier, with the best cards for 1080p, 1440p and 4K.', 'computingskill' ),
				'cpu-tier-list'         => __( 'Every gaming CPU ranked from S to D tier, with the fastest graphics card each one drives.', 'computingskill' ),
			),
			'outro' => __( 'For CPU and GPU pairings by name, the bottleneck comparisons cover every pair we have estimated.', 'computingskill' ),
		),
	);
}

/**
 * Stale sentences on hub pages (pages merged by earlier 301s are still
 * named, and the /bottleneck/ intro still had the pre-D3 meaning of the %).
 * Each rule matches the old wording and does nothing once it is gone.
 */
function cs_il_hub_text_fixes() {
	return array(
		'display-tools'  => array(
			'#the Screen Size and Monitor Size Calculators can help you compare#u' => __( 'the Monitor Size Calculator can help you compare', 'computingskill' ),
			'#Eight tools live in this section\..*?perceived motion smoothness\.#su' => __( 'Six tools live in this section. The PPI Calculator measures screen sharpness (pixels per inch), while the DPI Calculator covers print resolution (dots per inch), which is a different question. The Monitor Size Calculator works out a screen’s real width and height from its diagonal and aspect ratio. The Aspect Ratio Calculator and Resolution Calculator handle the relationship between a display’s width-to-height ratio and its pixel resolution, useful when comparing a 16:9 monitor to a 21:9 ultrawide. And the Refresh Rate Calculator turns refresh rate (Hz) into frame time and shows how it relates to motion smoothness.', 'computingskill' ),
		),
		'storage-tools'  => array(
			'#What(?:\'|&\#0?39;|&\#8217;|’)s the difference between the Storage Calculator and Drive Space Calculator\?#u' => __( 'Which tool tells me if my files will fit on a drive?', 'computingskill' ),
			'#They use the same underlying engine but are framed for slightly different questions.*?accounting for files or installs\.#su' => __( 'The Storage Calculator: enter an average file size, how many files, the drive’s capacity and what is already used, and it shows whether they fit and how much space is left. The Data Storage Converter only converts between units, including the binary GiB and TiB figures Windows shows.', 'computingskill' ),
			'#Two of these tools focus specifically on SSD endurance:.*?counterparts\.#su' => __( 'Two of these tools focus on SSD endurance: the SSD Lifespan Calculator estimates how many years a drive might last given your typical daily writes, and the SSD TBW Calculator converts between the two endurance ratings makers publish, DWPD (drive writes per day) and TBW (terabytes written), so drives of different sizes can be compared. The other two are general-purpose: the Storage Calculator helps you plan how much capacity you need for files, backups, games or media libraries, and the Data Storage Converter handles unit conversion between bytes, KB, MB, GB, TB and their binary (KiB/MiB/GiB/TiB) counterparts.', 'computingskill' ),
		),
		'computer-tools' => array(
			'#DPI/PPI, screen size, resolution, aspect ratio, refresh rate, and pixel density calculators\.#u' => __( 'PPI and DPI, monitor size, resolution, aspect ratio and refresh rate calculators.', 'computingskill' ),
		),
		'bottleneck'     => array(
			'#A higher percentage means more of that part.{1,12}s potential is going unused because the other component can.{1,12}t keep up; it doesn.{1,12}t mean the pairing performs badly overall\.#u' => __( 'A CPU bottleneck percentage is the share of the graphics card left idle because the CPU can’t keep up (under 6% balanced, 6–14% mild, 15–29% noticeable, 30% or more severe). “GPU-limited” is the normal case for gaming and means the graphics card sets the frame rate. The table below shows every pairing at a glance; each row links to its full page.', 'computingskill' ),
		),
	);
}

/**
 * the_content (priority 9, before the shortcodes and the hub link
 * collector): stale hub text, explainer/guide pointers and hub sections.
 */
function cs_il_page_text( $content ) {
	if ( ! is_singular( 'page' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	$slug = get_post_field( 'post_name' );

	$fixes = cs_il_hub_text_fixes();
	if ( isset( $fixes[ $slug ] ) ) {
		foreach ( $fixes[ $slug ] as $pattern => $new ) {
			$content = preg_replace( $pattern, $new, $content, 1 );
		}
	}

	$pointers = cs_il_explainer_pointers();
	if ( isset( $pointers[ $slug ] ) ) {
		list( $heading, $text ) = $pointers[ $slug ];
		$content = preg_replace( '#(<h2[^>]*>\s*' . preg_quote( $heading, '#' ) . '[^<]*</h2>)#u', '$1' . "\n<p class=\"cs-guide-pointer\">" . $text . '</p>', $content, 1 );
	}

	$guides = cs_il_guide_pointers();
	if ( isset( $guides[ $slug ] ) ) {
		list( $target, $label, $sentence ) = $guides[ $slug ];
		$p = '<p class="cs-guide-pointer">' . sprintf( esc_html( $sentence ), '<a href="/' . $target . '/">' . esc_html( $label ) . '</a>' ) . '</p>';
		$content = preg_replace( '#</p>#', '</p>' . "\n" . $p, $content, 1 );
	}

	$hubs = cs_il_hub_guides();
	if ( isset( $hubs[ $slug ] ) ) {
		$h    = $hubs[ $slug ];
		$html = '<h2>' . esc_html__( 'Which One Do You Need?', 'computingskill' ) . '</h2><p>' . esc_html( $h['intro'] ) . '</p><ul class="cs-hub-guide">';
		foreach ( $h['items'] as $item_slug => $question ) {
			$html .= '<li><strong>' . esc_html( $question ) . '</strong> <a href="/' . esc_attr( $item_slug ) . '/">' . esc_html( cs_il_page_title( $item_slug ) ) . '</a></li>';
		}
		$html .= '</ul><p>' . esc_html( $h['outro'] ) . '</p>';
		// Before the FAQ when there is one, else at the end.
		$pos = strpos( $content, '[cs_faq' );
		if ( false === $pos ) {
			$pos = strpos( $content, 'class="cs-faq' );
			$pos = false === $pos ? false : strrpos( substr( $content, 0, $pos ), '<' );
		}
		$content = false === $pos ? $content . $html : substr( $content, 0, $pos ) . $html . substr( $content, $pos );
	}

	return $content;
}
add_filter( 'the_content', 'cs_il_page_text', 9 );

/**
 * H1 for the three explainers comes from the post title; the job below
 * writes it, and this filter covers the time before it runs.
 */
function cs_il_explainer_h1( $title, $post_id = 0 ) {
	if ( ! $post_id || 'page' !== get_post_type( $post_id ) ) {
		return $title;
	}
	$titles = cs_il_explainer_titles();
	$slug   = get_post_field( 'post_name', $post_id );
	if ( isset( $titles[ $slug ] ) && html_entity_decode( $title, ENT_QUOTES, 'UTF-8' ) === $titles[ $slug ][2] ) {
		return esc_html( $titles[ $slug ][0] );
	}
	return $title;
}
add_filter( 'the_title', 'cs_il_explainer_h1', 10, 2 );

/* ------------------------------------------------------------------ */
/* /blog/ (2.22)                                                       */
/* ------------------------------------------------------------------ */

/**
 * The content-index groups that are articles (read, not used): /blog/
 * lists these plus the posts; tools and hardware pages keep their hubs.
 */
function cs_blog_article_groups() {
	$out = array();
	foreach ( cs_get_blog_index() as $key => $group ) {
		if ( in_array( $key, array( 'guides', 'comparisons' ), true ) ) {
			$out[ $key ] = $group;
		}
	}
	return $out;
}

/**
 * Posts + article groups as one flat list (template and ItemList schema).
 */
function cs_blog_article_items() {
	$items = array();
	$posts = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 50, 'no_found_rows' => true ) );
	foreach ( $posts as $post ) {
		$items[] = array( 'slug' => $post->post_name, 'title' => get_the_title( $post ), 'url' => get_permalink( $post ) );
	}
	foreach ( cs_blog_article_groups() as $group ) {
		foreach ( $group['items'] as $item ) {
			$items[] = array( 'slug' => $item['slug'], 'title' => $item['title'], 'url' => home_url( '/' . $item['slug'] . '/' ) );
		}
	}
	return $items;
}

/**
 * The tool and reference hubs, linked once from /blog/.
 */
function cs_blog_hub_links() {
	$out = array();
	foreach ( array( 'calculators', 'computer-tools', 'hardware', 'bottleneck' ) as $slug ) {
		$page = get_page_by_path( $slug );
		if ( $page && 'publish' === $page->post_status ) {
			$out[] = '<a href="' . esc_url( get_permalink( $page ) ) . '">' . esc_html( get_the_title( $page ) ) . '</a>';
		}
	}
	if ( post_type_exists( 'cs_game' ) && get_post_type_archive_link( 'cs_game' ) ) {
		$out[] = '<a href="' . esc_url( get_post_type_archive_link( 'cs_game' ) ) . '">' . esc_html__( 'Game System Requirements', 'computingskill' ) . '</a>';
	}
	return $out;
}

/* ------------------------------------------------------------------ */
/* One-time job                                                        */
/* ------------------------------------------------------------------ */

/**
 * Writes the explainer titles (only where the title is still the old one,
 * so a hand edit is kept), clears Rank Math's sitemap cache and the page
 * cache once.
 */
function cs_links_rev_run() {
	if ( wp_installing() || get_option( 'cs_links_rev' ) === CS_LINKS_REV ) {
		return;
	}
	if ( ! add_option( 'cs_links_rev_lock', time(), '', 'no' ) ) {
		if ( time() - (int) get_option( 'cs_links_rev_lock' ) > 300 ) {
			delete_option( 'cs_links_rev_lock' );
		}
		return;
	}
	global $wpdb;
	foreach ( cs_il_explainer_titles() as $slug => $t ) {
		$page = get_page_by_path( $slug );
		if ( $page && html_entity_decode( $page->post_title, ENT_QUOTES, 'UTF-8' ) === $t[2] ) {
			$wpdb->update( $wpdb->posts, array( 'post_title' => $t[0] ), array( 'ID' => $page->ID ), array( '%s' ), array( '%d' ) );
			clean_post_cache( $page->ID );
		}
	}
	if ( class_exists( '\RankMath\Sitemap\Cache' ) && method_exists( '\RankMath\Sitemap\Cache', 'invalidate_storage' ) ) {
		\RankMath\Sitemap\Cache::invalidate_storage();
	}
	do_action( 'litespeed_purge_all' );
	update_option( 'cs_links_rev', CS_LINKS_REV, false );
	delete_option( 'cs_links_rev_lock' );
}
add_action( 'init', 'cs_links_rev_run', 32 );

/* ------------------------------------------------------------------ */
/* Merged near-twin combo pages (2.20, Ali 18 Oct 2026)                */
/* ------------------------------------------------------------------ */

if ( ! defined( 'CS_COMBO_MERGE_REV' ) ) {
	define( 'CS_COMBO_MERGE_REV', '2026-10-18-d7-merge' );
}

/**
 * Combo pages folded into a near-identical page: merged slug => kept slug.
 * Chosen from the live crawl: same GPU, CPUs of the same maker and socket,
 * 73%+ shared text, and no game type or resolution where the two CPUs'
 * bottleneck differs by more than 6 points. The kept page is the CPU with
 * more combo pages (tie: the longer-standing page). The merged posts become
 * drafts (kept in the database) and their URLs 301 to the kept page, which
 * names the merged CPU (cs_il_merged_twin()). To undo one, remove its line
 * and publish the post again.
 */
function cs_merged_combo_map() {
	return array(
		'core-i5-14400f-rtx-5060-ti'   => 'core-i5-13400f-rtx-5060-ti',
		'ryzen-5-7500f-rtx-5060-ti'    => 'ryzen-5-7600-rtx-5060-ti',
		'core-i7-14700k-rtx-4090'      => 'core-i9-13900k-rtx-4090',
		'ryzen-5-7500f-rx-7800-xt'     => 'ryzen-5-7600-rx-7800-xt',
		'ryzen-5-7600x-rx-7800-xt'     => 'ryzen-5-7600-rx-7800-xt',
		'core-i5-13600k-rtx-4070-super' => 'core-i5-14600k-rtx-4070-super',
		'core-i5-14400f-rtx-5070'      => 'core-i5-13400f-rtx-5070',
		'ryzen-5-5600x-rtx-4060'       => 'ryzen-5-5600-rtx-4060',
		'ryzen-7-9700x-rtx-5070'       => 'ryzen-7-7700x-rtx-5070',
	);
}

/**
 * CPU slugs merged into the kept page $kept_slug (a kept page can absorb
 * two, e.g. Ryzen 5 7500F and 7600X into 7600 + RX 7800 XT).
 */
function cs_il_merged_twins( $kept_slug ) {
	$out = array();
	foreach ( cs_merged_combo_map() as $from => $to ) {
		if ( $to !== $kept_slug ) {
			continue;
		}
		$post = get_page_by_path( $from, OBJECT, 'cs_bottleneck' );
		$cpu  = $post ? get_post_meta( $post->ID, 'cs_cpu', true ) : '';
		if ( $cpu && isset( cs_get_cpu_data()[ $cpu ] ) ) {
			$out[] = $cpu;
		}
	}
	return $out;
}

/**
 * 301 for the merged URLs, both /bottleneck/{cpu}-{gpu}/ and the nested
 * /bottleneck/{cpu}/{gpu}/ form, before the D6 URL guards would 404 them.
 */
function cs_merged_combo_redirect() {
	$path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
	if ( ! is_string( $path ) || ! preg_match( '#^/bottleneck/([a-z0-9-]+)(?:/([a-z0-9-]+))?/?$#i', $path, $m ) ) {
		return;
	}
	$slug = strtolower( $m[1] . ( empty( $m[2] ) ? '' : '-' . $m[2] ) );
	$map  = cs_merged_combo_map();
	if ( isset( $map[ $slug ] ) ) {
		wp_safe_redirect( home_url( '/bottleneck/' . $map[ $slug ] . '/' ), 301 );
		exit;
	}
}
add_action( 'wp', 'cs_merged_combo_redirect', 0 );

/**
 * The calculators link a result to its combo page (window.csComboPages):
 * a merged pair links to the kept page.
 */
function cs_merged_combo_links( $combos ) {
	foreach ( cs_merged_combo_map() as $from => $to ) {
		$post = get_page_by_path( $from, OBJECT, 'cs_bottleneck' );
		if ( ! $post ) {
			continue;
		}
		$key = get_post_meta( $post->ID, 'cs_cpu', true ) . '|' . get_post_meta( $post->ID, 'cs_gpu', true );
		if ( '|' !== $key && ! isset( $combos[ $key ] ) ) {
			$combos[ $key ] = home_url( '/bottleneck/' . $to . '/' );
		}
	}
	return $combos;
}

/**
 * One-time job: merged posts to draft (only if still published), sitemap
 * and page cache cleared, llms.txt rewritten without them.
 */
function cs_combo_merge_run() {
	if ( wp_installing() || get_option( 'cs_combo_merge_rev' ) === CS_COMBO_MERGE_REV ) {
		return;
	}
	if ( ! add_option( 'cs_combo_merge_lock', time(), '', 'no' ) ) {
		if ( time() - (int) get_option( 'cs_combo_merge_lock' ) > 300 ) {
			delete_option( 'cs_combo_merge_lock' );
		}
		return;
	}
	global $wpdb;
	foreach ( array_keys( cs_merged_combo_map() ) as $slug ) {
		$post = get_page_by_path( $slug, OBJECT, 'cs_bottleneck' );
		if ( $post && 'publish' === $post->post_status ) {
			$wpdb->update( $wpdb->posts, array( 'post_status' => 'draft' ), array( 'ID' => $post->ID ), array( '%s' ), array( '%d' ) );
			clean_post_cache( $post->ID );
		}
	}
	if ( class_exists( '\RankMath\Sitemap\Cache' ) && method_exists( '\RankMath\Sitemap\Cache', 'invalidate_storage' ) ) {
		\RankMath\Sitemap\Cache::invalidate_storage();
	}
	if ( function_exists( 'cs_llms_write' ) ) {
		cs_llms_write();
	}
	do_action( 'litespeed_purge_all' );
	update_option( 'cs_combo_merge_rev', CS_COMBO_MERGE_REV, false );
	delete_option( 'cs_combo_merge_lock' );
}
add_action( 'init', 'cs_combo_merge_run', 33 );

/**
 * Links written into post content (e.g. the Ryzen 5 5600 GPU post links
 * the 5600X + RTX 4060 page) point straight at the kept page, not through
 * the 301.
 */
function cs_merged_combo_content_links( $content ) {
	if ( false === strpos( $content, '/bottleneck/' ) ) {
		return $content;
	}
	foreach ( cs_merged_combo_map() as $from => $to ) {
		$content = str_replace( '/bottleneck/' . $from . '/', '/bottleneck/' . $to . '/', $content );
	}
	return $content;
}
add_filter( 'the_content', 'cs_merged_combo_content_links', 21 );
