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
	$storage = (int) $g['rec']['storage_gb'];

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

	return $ram_sentence . ' ' . $storage_sentence;
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
	$storage    = (int) $g['rec']['storage_gb'];
	$genre      = $g['genre'];
	$cpu_bound_genres = array( 'Open-World Action', 'Battle Royale', 'Sandbox / Survival', 'Open-World RPG' );
	$is_cpu_leaning   = in_array( $genre, $cpu_bound_genres, true );

	$q1 = sprintf( __( 'Is %1$dGB of RAM enough to run %2$s?', 'computingskill' ), $min_ram, $name );
	if ( $rec_ram > $min_ram ) {
		$a1 = sprintf(
			__( "%1\$dGB is the publisher's stated minimum, meaning %2\$s will start and run, but %3\$dGB (the recommended figure) avoids the stuttering that comes from paging assets off your storage drive mid-session. Use our %4\$s to check your exact total against everything else you run alongside it.", 'computingskill' ),
			$min_ram,
			$name,
			$rec_ram,
			'<a href="' . esc_url( home_url( '/ram-calculator/' ) ) . '">' . esc_html__( 'RAM calculator', 'computingskill' ) . '</a>'
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
			)
		);
	}

	return $html;
}
