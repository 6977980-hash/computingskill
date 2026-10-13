<?php
/**
 * Extra, data-driven content for individual /games/{slug}/ pages.
 *
 * Why this file exists: each game page previously rendered only the
 * post's own short intro plus the shared spec table (~200 words per
 * page, structurally identical across all 16 titles). That is exactly
 * the kind of thin, templated content Google's quality systems tend to
 * deprioritize for crawling — a real, checkable contributor to pages
 * sitting in Search Console's "Discovered - currently not indexed"
 * queue. Rather than padding every page with the same boilerplate
 * paragraph (which would just create 16 more near-duplicates), the
 * functions below build sentences FROM each game's own data — its
 * genre and its actual min/recommended numbers — so the extra content
 * is genuinely different from one game to the next, not reworded
 * filler.
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * One genre-specific paragraph explaining what tends to bottleneck that
 * kind of game. Genres repeat across the dataset (three Battle Royale
 * titles, two Open-World RPGs, etc.) by design — this is standard,
 * defensible technical commentary about the genre itself, not a claim
 * about one specific title, so reuse across a genre's titles is
 * accurate rather than duplicated filler.
 */
function cs_game_genre_insight( $genre ) {
	$notes = array(
		'Open-World Action'  => __( 'Open-world action games stream in traffic, pedestrians, physics objects and long draw distances continuously, which leans harder on the CPU (and on fast storage) than a linear, level-based game — a stronger CPU pays off more here than in most genres, even before touching graphics settings.', 'computingskill' ),
		'Open-World RPG'     => __( 'Large open-world RPGs combine dense scenery with real-time lighting, physics and NPC simulation, so both the CPU (world logic) and the GPU (render detail) matter — a mismatched pairing usually shows up as stutter when entering a busy area, not just a lower average framerate.', 'computingskill' ),
		'Tactical Shooter'   => __( 'Competitive tactical shooters are deliberately light on hardware so more players can hit high, stable framerates, which means even a modest GPU clears the recommended spec — the bigger lever for competitive play is a high-refresh-rate monitor and a CPU that keeps the 1% low framerate steady during firefights.', 'computingskill' ),
		'Battle Royale'      => __( 'Battle royale maps load dozens of players, vehicles and destructible objects into memory at once, so CPU load climbs sharply in the endgame even when the GPU still has spare headroom — this genre tends to be more CPU-sensitive in practice than its recommended GPU spec alone suggests.', 'computingskill' ),
		'Sandbox / Survival' => __( 'Sandbox and survival titles are usually CPU- and RAM-bound rather than GPU-bound — world generation, entity ticking and (for engines with garbage collection) memory management matter more than raw graphics horsepower, which is why the GPU requirement here looks modest next to the RAM figure.', 'computingskill' ),
		'MOBA'               => __( 'MOBAs render a fixed, zoomed-out view rather than a full first-person 3D world, so hardware requirements stay modest across the board — almost any PC from the last decade clears the recommended spec, and perceived smoothness is limited more by network/server tick rate than by local hardware.', 'computingskill' ),
		'Action RPG'         => __( 'Action RPGs with dense combat and particle-heavy skills lean on the GPU for frame-rate stability during large fights, while area transitions and enemy AI keep a steady background load on the CPU — both parts matter, but players typically feel a GPU upgrade first.', 'computingskill' ),
		'Role-Playing'       => __( 'Party-based RPGs generally favor the CPU for game logic, dialogue systems and companion AI running underneath the scene, while the GPU workload scales heavily with visual settings — it is usually safe to drop GPU-heavy settings here without hurting the actual gameplay experience.', 'computingskill' ),
		'Hero Shooter'       => __( 'Hero shooters mix competitive pacing with more elaborate character abilities and effects than a straight tactical shooter, so they sit between the two: a mid-range GPU is usually the limiting part at high framerates, more than the CPU.', 'computingskill' ),
		'Co-op Shooter'      => __( 'Co-op shooters with large enemy counts on screen (swarms, destructible terrain, chaotic effects) put steady load on both the CPU and GPU at the same time, so a bottleneck here tends to show up as dropped frames specifically during the busiest fights rather than a flat, consistently low average.', 'computingskill' ),
	);

	return isset( $notes[ $genre ] ) ? $notes[ $genre ] : '';
}

/**
 * A second, numbers-driven paragraph built from each game's own
 * min → recommended RAM jump (or lack of one) and storage footprint.
 * Every sentence here is computed from that title's actual data array
 * in data/games-data.php, so it is factually specific to the game even
 * when the surrounding sentence structure is shared.
 */
function cs_game_ram_storage_note( $g ) {
	$min_ram = (int) $g['min']['ram'];
	$rec_ram = (int) $g['rec']['ram'];
	$storage = cs_game_storage_gb( $g );

	if ( $rec_ram > $min_ram ) {
		$ram_sentence = sprintf(
			/* translators: 1: minimum RAM, 2: recommended RAM, 3: game name */
			__( 'The jump from %1$dGB to %2$dGB of RAM between the minimum and recommended spec is where %3$s stops paging textures and level data from disk mid-play — below %2$dGB, that shows up as short stutters and pop-in rather than a flat drop in average framerate.', 'computingskill' ),
			$min_ram,
			$rec_ram,
			$g['name']
		);
	} else {
		$ram_sentence = sprintf(
			/* translators: 1: RAM amount, 2: game name */
			__( '%2$s lists %1$dGB of RAM as both the minimum and recommended figure, which usually means the engine itself has a fairly fixed memory footprint — extra RAM beyond that mostly helps your other background apps and browser tabs, not %2$s directly.', 'computingskill' ),
			$min_ram,
			$g['name']
		);
	}

	$storage_sentence = sprintf(
		/* translators: 1: storage in GB, 2: game name */
		__( 'At roughly %1$dGB, plan storage for %2$s the same way you would for any modern title: an NVMe SSD keeps load times and texture streaming noticeably smoother than a hard drive at the same free-space total.', 'computingskill' ),
		$storage,
		$g['name']
	);

	$note = cs_game_storage_mismatch_note( $g );

	return $ram_sentence . ' ' . $storage_sentence . ( $note ? ' ' . $note : '' );
}

/**
 * False when the publisher lists no recommended tier and the 'rec' row is
 * our own suggestion (Counter-Strike 2).
 */
function cs_game_rec_official( $g ) {
	return ! isset( $g['rec_official'] ) || false !== $g['rec_official'];
}

function cs_game_rec_gpu_label( $g ) {
	return cs_game_rec_official( $g ) ? $g['rec']['gpu'] : $g['rec']['gpu'] . ' ' . __( '(our suggestion)', 'computingskill' );
}

/**
 * Space to plan for: the larger of the two listed figures. Helldivers 2's
 * Steam listing (checked 13 Oct 2026) gives 135GB at minimum but 40GB at
 * recommended, and a PC that meets the recommended spec still installs the
 * same game, so the smaller number alone would under-plan.
 */
function cs_game_storage_gb( $g ) {
	return max( (int) $g['min']['storage_gb'], (int) $g['rec']['storage_gb'] );
}

/**
 * One sentence when the publisher's two storage figures differ.
 */
function cs_game_storage_mismatch_note( $g ) {
	$min = (int) $g['min']['storage_gb'];
	$rec = (int) $g['rec']['storage_gb'];
	if ( $min > $rec ) {
		return sprintf(
			/* translators: 1: game, 2: minimum GB, 3: recommended GB */
			__( 'The official listing for %1$s gives %2$dGB at the minimum tier but only %3$dGB at the recommended tier; the larger figure is the safe one to plan around, which is why this page uses %2$dGB.', 'computingskill' ),
			$g['name'], $min, $rec
		);
	}
	if ( $rec > $min ) {
		return sprintf(
			/* translators: 1: game, 2: minimum GB, 3: recommended GB */
			__( 'The publisher lists %2$dGB at the minimum tier and %3$dGB at the recommended tier for %1$s, so this page plans around %3$dGB.', 'computingskill' ),
			$g['name'], $min, $rec
		);
	}
	return '';
}

/**
 * Ranking-readiness fix (Oct 2026): where this game sits among every title
 * in data/games-data.php, and the titles with the closest requirements.
 * Built only from the published specs, so it changes per game and stays
 * true when games are added.
 */
function cs_game_compare_section( $slug, $g ) {
	$games = cs_get_games_data();
	$total = count( $games );
	if ( $total < 3 ) {
		return '';
	}

	$rec_ram  = (int) $g['rec']['ram'];
	$storage  = cs_game_storage_gb( $g );
	$more_ram = 0;
	$same_ram = 0;
	$bigger   = 0;
	$dist     = array();
	foreach ( $games as $other_slug => $o ) {
		if ( $other_slug === $slug ) {
			continue;
		}
		$o_ram = (int) $o['rec']['ram'];
		$o_sto = cs_game_storage_gb( $o );
		if ( $o_ram > $rec_ram ) {
			$more_ram++;
		} elseif ( $o_ram === $rec_ram ) {
			$same_ram++;
		}
		if ( $o_sto > $storage ) {
			$bigger++;
		}
		// RAM steps count more than storage: 4GB of RAM ~ 25GB of disk.
		$dist[ $other_slug ] = abs( $o_ram - $rec_ram ) * 6 + abs( $o_sto - $storage ) / 4 + abs( (int) $o['min']['ram'] - (int) $g['min']['ram'] ) * 3;
	}
	asort( $dist );
	$closest = array_slice( array_keys( $dist ), 0, 3 );

	if ( ! cs_game_rec_official( $g ) ) {
		$ram_line = $more_ram
			/* translators: 1: game, 2: RAM GB, 3: count with more, 4: total other games */
			? sprintf( __( '%1$s has no official recommended tier; against our suggested %2$dGB of RAM, %3$d of the other %4$d games we cover recommend more.', 'computingskill' ), $g['name'], $rec_ram, $more_ram, $total - 1 )
			/* translators: 1: game, 2: RAM GB, 3: total other games */
			: sprintf( __( '%1$s has no official recommended tier; our suggested %2$dGB of RAM matches the highest recommended figure among the other %3$d games we cover.', 'computingskill' ), $g['name'], $rec_ram, $total - 1 );
	} elseif ( $more_ram ) {
		$ram_line = sprintf(
			/* translators: 1: game, 2: RAM GB, 3: count with more, 4: total other games */
			__( '%1$s recommends %2$dGB of RAM; %3$d of the other %4$d games we cover recommend more.', 'computingskill' ),
			$g['name'], $rec_ram, $more_ram, $total - 1
		);
	} else {
		$ram_line = sprintf(
			/* translators: 1: game, 2: RAM GB, 3: total other games */
			__( 'At %2$dGB, %1$s asks for as much recommended RAM as any of the other %3$d games we cover.', 'computingskill' ),
			$g['name'], $rec_ram, $total - 1
		);
	}
	if ( $same_ram ) {
		$ram_line .= ' ' . sprintf( _n( '%d other game asks for the same amount.', '%d others ask for the same amount.', $same_ram, 'computingskill' ), $same_ram );
	}

	$rank       = $bigger + 1;
	$storage_ln = 1 === $rank
		? sprintf( __( 'Its %1$dGB install is the largest in our list.', 'computingskill' ), $storage )
		: sprintf( __( 'Its %1$dGB install ranks #%2$d of %3$d by size, so %4$d of the games we cover need more free space.', 'computingskill' ), $storage, $rank, $total, $bigger );

	ob_start();
	?>
	<section class="cs-game-compare">
		<h2><?php echo esc_html( sprintf( __( 'How %s Compares', 'computingskill' ), $g['name'] ) ); ?></h2>
		<p><?php echo esc_html( $ram_line . ' ' . $storage_ln ); ?></p>
		<p><?php esc_html_e( 'Games with the closest published requirements (recommended RAM, install size and minimum RAM):', 'computingskill' ); ?></p>
		<div class="cs-table-wrap">
			<table class="cs-hardware-table cs-game-compare-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Game', 'computingskill' ); ?></th>
						<th><?php esc_html_e( 'Recommended RAM', 'computingskill' ); ?></th>
						<th><?php esc_html_e( 'Install size', 'computingskill' ); ?></th>
						<th><?php esc_html_e( 'Recommended GPU', 'computingskill' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><strong><?php echo esc_html( $g['name'] ); ?></strong> (<?php esc_html_e( 'this page', 'computingskill' ); ?>)</td>
						<td><?php echo esc_html( $rec_ram . ' GB' ); ?></td>
						<td><?php echo esc_html( $storage . ' GB' ); ?></td>
						<td><?php echo esc_html( cs_game_rec_gpu_label( $g ) ); ?></td>
					</tr>
					<?php foreach ( $closest as $other_slug ) : $o = $games[ $other_slug ]; ?>
						<tr>
							<td><a href="<?php echo esc_url( home_url( '/games/' . $other_slug . '/' ) ); ?>"><?php echo esc_html( $o['name'] ); ?></a></td>
							<td><?php echo esc_html( (int) $o['rec']['ram'] . ' GB' ); ?></td>
							<td><?php echo esc_html( cs_game_storage_gb( $o ) . ' GB' ); ?></td>
							<td><?php echo esc_html( cs_game_rec_gpu_label( $o ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/**
 * Renders the "Why These Specs Matter" section for a single game page.
 * Call from single-cs_game.php right after the system-requirements
 * table, passing that page's data array from cs_get_games_data().
 */
function cs_game_why_specs_matter_section( $g ) {
	$genre_note = cs_game_genre_insight( $g['genre'] );
	ob_start();
	?>
	<section class="cs-game-why-specs">
		<h2><?php esc_html_e( 'Why These Specs Matter', 'computingskill' ); ?></h2>
		<?php if ( $genre_note ) : ?>
			<p><?php echo esc_html( $genre_note ); ?></p>
		<?php endif; ?>
		<p><?php echo esc_html( cs_game_ram_storage_note( $g ) ); ?></p>
	</section>
	<?php
	return ob_get_clean();
}

/**
 * Renders a per-game FAQ block built from that title's own numbers
 * (min/recommended RAM, storage, genre) rather than generic filler
 * questions, using the existing [cs_faq] shortcode so answers keep the
 * same accessible markup and FAQPage schema handling as every other
 * FAQ block on the site (see inc/shortcodes.php, cs_faq_shortcode()).
 */
function cs_game_faq_section( $g ) {
	$name       = $g['name'];
	$min_ram    = (int) $g['min']['ram'];
	$rec_ram    = (int) $g['rec']['ram'];
	$storage    = cs_game_storage_gb( $g );
	$genre      = $g['genre'];
	$cpu_bound_genres = array( 'Open-World Action', 'Battle Royale', 'Sandbox / Survival', 'Open-World RPG' );
	$is_cpu_leaning   = in_array( $genre, $cpu_bound_genres, true );

	$q1 = sprintf( __( 'Is %1$dGB of RAM enough to run %2$s?', 'computingskill' ), $min_ram, $name );
	if ( $rec_ram > $min_ram ) {
		$a1 = sprintf(
			__( "%1\$dGB is the publisher's stated minimum, meaning %2\$s will start and run, but %3\$dGB (%5\$s) avoids the stuttering that comes from paging assets off your storage drive mid-session. Use our %4\$s to check your exact total against everything else you run alongside it.", 'computingskill' ),
			$min_ram,
			$name,
			$rec_ram,
			'<a href="' . esc_url( home_url( '/ram-calculator/' ) ) . '">' . esc_html__( 'RAM calculator', 'computingskill' ) . '</a>',
			cs_game_rec_official( $g ) ? __( 'the recommended figure', 'computingskill' ) : __( 'our suggested figure; the publisher lists no recommended tier', 'computingskill' )
		);
	} else {
		$a1 = sprintf(
			__( 'Yes — %1$s lists %2$dGB as both the minimum and recommended amount, so there is no extra headroom figure to chase; %2$dGB free (beyond what Windows and your other background apps already use) is the real target.', 'computingskill' ),
			$name,
			$min_ram
		);
	}

	$q2 = sprintf( __( 'Should I prioritize my CPU or my GPU for %s?', 'computingskill' ), $name );
	if ( $is_cpu_leaning ) {
		$a2 = sprintf(
			__( "For %s specifically, the %s tends to be the more common limiting part in practice, not just the GPU — check your exact pairing with our %s before assuming a GPU upgrade alone will fix low or inconsistent framerates.", 'computingskill' ),
			$name,
			esc_html__( 'CPU', 'computingskill' ),
			'<a href="' . esc_url( home_url( '/pc-bottleneck-calculator/' ) ) . '">' . esc_html__( 'PC Bottleneck Calculator', 'computingskill' ) . '</a>'
		);
	} else {
		$a2 = sprintf(
			__( 'For %s, the GPU is the more common limiting part at higher resolutions and settings — our %s breaks this down by your exact CPU and GPU pairing instead of a generic rule of thumb.', 'computingskill' ),
			$name,
			'<a href="' . esc_url( home_url( '/pc-bottleneck-calculator/' ) ) . '">' . esc_html__( 'PC Bottleneck Calculator', 'computingskill' ) . '</a>'
		);
	}

	$q3 = sprintf( __( 'How much free storage should I actually set aside for %s?', 'computingskill' ), $name );
	$a3 = sprintf(
		__( 'Plan for at least %1$dGB free, not exactly %1$dGB — day-one patches, DLC and shader/texture caches routinely add several more gigabytes after install. Our %2$s can help you plan an upgrade if you are close to full.', 'computingskill' ),
		$storage,
		'<a href="' . esc_url( home_url( '/storage-calculator/' ) ) . '">' . esc_html__( 'Storage Calculator', 'computingskill' ) . '</a>'
	);

	$q4    = sprintf( __( 'What graphics card do I need for %s?', 'computingskill' ), $name );
	$tools = sprintf(
		/* translators: 1: FPS calculator link, 2: VRAM calculator link */
		__( 'To see how your own card is likely to perform at your resolution, try our %1$s, and check memory needs with the %2$s.', 'computingskill' ),
		'<a href="' . esc_url( home_url( '/fps-calculator/' ) ) . '">' . esc_html__( 'FPS Calculator', 'computingskill' ) . '</a>',
		'<a href="' . esc_url( home_url( '/gpu-vram-calculator/' ) ) . '">' . esc_html__( 'GPU VRAM Calculator', 'computingskill' ) . '</a>'
	);
	if ( cs_game_rec_official( $g ) ) {
		$a4 = sprintf( __( 'The publisher lists %1$s as the minimum and %2$s as the recommended graphics card for %3$s.', 'computingskill' ), $g['min']['gpu'], $g['rec']['gpu'], $name );
	} else {
		$a4 = sprintf( __( 'The publisher lists %1$s as the minimum for %3$s and publishes no recommended tier; %2$s is our own suggestion for smoother play.', 'computingskill' ), $g['min']['gpu'], $g['rec']['gpu'], $name );
	}
	$a4 .= ' ' . $tools;

	$q5 = sprintf( __( 'Which version of Windows does %s need?', 'computingskill' ), $name );
	if ( ! cs_game_rec_official( $g ) ) {
		$a5 = sprintf( __( 'The publisher lists %1$s for %2$s.', 'computingskill' ), $g['min']['os'], $name );
	} elseif ( $g['min']['os'] === $g['rec']['os'] ) {
		$a5 = sprintf( __( 'The publisher lists %1$s for both the minimum and recommended specs of %2$s.', 'computingskill' ), $g['min']['os'], $name );
	} else {
		$a5 = sprintf( __( 'The publisher lists %1$s for the minimum spec and %2$s for the recommended spec of %3$s.', 'computingskill' ), $g['min']['os'], $g['rec']['os'], $name );
	}

	ob_start();
	?>
	<div class="cs-faq">
		<h2><?php esc_html_e( 'Frequently Asked Questions', 'computingskill' ); ?></h2>
		<div class="cs-faq-item">
			<h3 class="cs-faq-question"><?php echo esc_html( $q1 ); ?></h3>
			<div class="cs-faq-answer"><p><?php echo wp_kses_post( $a1 ); ?></p></div>
		</div>
		<div class="cs-faq-item">
			<h3 class="cs-faq-question"><?php echo esc_html( $q2 ); ?></h3>
			<div class="cs-faq-answer"><p><?php echo wp_kses_post( $a2 ); ?></p></div>
		</div>
		<div class="cs-faq-item">
			<h3 class="cs-faq-question"><?php echo esc_html( $q3 ); ?></h3>
			<div class="cs-faq-answer"><p><?php echo wp_kses_post( $a3 ); ?></p></div>
		</div>
		<div class="cs-faq-item">
			<h3 class="cs-faq-question"><?php echo esc_html( $q4 ); ?></h3>
			<div class="cs-faq-answer"><p><?php echo wp_kses_post( $a4 ); ?></p></div>
		</div>
		<div class="cs-faq-item">
			<h3 class="cs-faq-question"><?php echo esc_html( $q5 ); ?></h3>
			<div class="cs-faq-answer"><p><?php echo esc_html( $a5 ); ?></p></div>
		</div>
	</div>
	<?php
	$html = ob_get_clean();

	// Queue these for FAQPage JSON-LD the same way [cs_faq] normally would,
	// so search engines can pick up the same rich-result markup on game
	// pages that they already can on calculator/guide pages.
	if ( cs_schema_should_emit( 'FAQPage' ) ) {
		global $cs_faq_schema_queue;
		if ( ! is_array( $cs_faq_schema_queue ) ) {
			$cs_faq_schema_queue = array();
		}
		$cs_faq_schema_queue = array_merge(
			$cs_faq_schema_queue,
			array(
				array( 'question' => wp_strip_all_tags( $q1 ), 'answer' => wp_strip_all_tags( $a1 ) ),
				array( 'question' => wp_strip_all_tags( $q2 ), 'answer' => wp_strip_all_tags( $a2 ) ),
				array( 'question' => wp_strip_all_tags( $q3 ), 'answer' => wp_strip_all_tags( $a3 ) ),
				array( 'question' => wp_strip_all_tags( $q4 ), 'answer' => wp_strip_all_tags( $a4 ) ),
				array( 'question' => $q5, 'answer' => $a5 ),
			)
		);
	}

	return $html;
}

/**
 * Ranking-readiness fix (Oct 2026): the /games/ hub was a card grid with
 * one intro line (~175 words). This adds a guide to reading the specs and
 * a table of every game's published figures, all from data/games-data.php.
 */
function cs_games_hub_overview( $games ) {
	if ( empty( $games ) ) {
		return '';
	}
	uasort( $games, function ( $a, $b ) {
		return strcasecmp( $a['name'], $b['name'] );
	} );

	$total     = count( $games );
	$ram16     = 0;
	$largest   = null;
	$smallest  = null;
	$genres    = array();
	foreach ( $games as $slug => $g ) {
		if ( cs_game_rec_official( $g ) && (int) $g['rec']['ram'] >= 16 ) {
			$ram16++;
		}
		$size = cs_game_storage_gb( $g );
		if ( null === $largest || $size > cs_game_storage_gb( $games[ $largest ] ) ) {
			$largest = $slug;
		}
		if ( null === $smallest || $size < cs_game_storage_gb( $games[ $smallest ] ) ) {
			$smallest = $slug;
		}
		$genres[ $g['genre'] ] = true;
	}

	ob_start();
	?>
	<section class="cs-games-overview cs-narrow">
		<h2><?php esc_html_e( 'How to Read PC Game System Requirements', 'computingskill' ); ?></h2>
		<p><?php esc_html_e( 'The minimum spec is the weakest hardware the publisher says will start and run the game, usually at low settings. The recommended spec is what the publisher suggests for a smoother experience at higher settings; some publishers state a target frame rate or resolution for each tier, many do not. Neither tier promises a frame rate on your PC, because drivers, background apps, resolution and in-game settings all change the result.', 'computingskill' ); ?></p>
		<p><?php esc_html_e( 'Clearing the recommended spec on paper also does not tell you whether your CPU and graphics card are well matched. A fast GPU paired with an older CPU can still stutter in busy scenes. That is why every game page here links to our PC Bottleneck Calculator and FPS Calculator, which estimate how your exact parts pair up at 1080p, 1440p and 4K.', 'computingskill' ); ?></p>
		<p>
			<?php
			echo esc_html( sprintf(
				/* translators: 1: total games, 2: genre count, 3: games recommending 16GB+, 4: largest game, 5: its GB, 6: smallest game, 7: its GB */
				__( 'Across the %1$d games covered in %2$d genres, the publishers of %3$d recommend 16GB of RAM or more. The largest install is %4$s at %5$dGB and the smallest is %6$s at %7$dGB, so free storage matters as much as RAM when you plan a build.', 'computingskill' ),
				$total,
				count( $genres ),
				$ram16,
				$games[ $largest ]['name'],
				cs_game_storage_gb( $games[ $largest ] ),
				$games[ $smallest ]['name'],
				cs_game_storage_gb( $games[ $smallest ] )
			) );
			?>
		</p>

		<h2><?php esc_html_e( 'All Games at a Glance', 'computingskill' ); ?></h2>
		<div class="cs-table-wrap">
			<table class="cs-hardware-table cs-games-overview-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Game', 'computingskill' ); ?></th>
						<th><?php esc_html_e( 'Genre', 'computingskill' ); ?></th>
						<th><?php esc_html_e( 'RAM (min / rec)', 'computingskill' ); ?></th>
						<th><?php esc_html_e( 'Install size', 'computingskill' ); ?></th>
						<th><?php esc_html_e( 'Recommended GPU', 'computingskill' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $games as $slug => $g ) : ?>
						<tr>
							<td><a href="<?php echo esc_url( home_url( '/games/' . $slug . '/' ) ); ?>"><?php echo esc_html( $g['name'] ); ?></a></td>
							<td><?php echo esc_html( $g['genre'] ); ?></td>
							<td><?php echo esc_html( (int) $g['min']['ram'] . ' / ' . (int) $g['rec']['ram'] . ' GB' ); ?></td>
							<td><?php echo esc_html( cs_game_storage_gb( $g ) . ' GB' ); ?></td>
							<td><?php echo esc_html( cs_game_rec_gpu_label( $g ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<p class="cs-disclaimer"><?php esc_html_e( 'Figures come from each publisher\'s own listing, linked on every game page. Install size is the larger of the minimum and recommended storage figures.', 'computingskill' ); ?></p>
	</section>
	<?php
	return ob_get_clean();
}
