<?php
/**
 * GTA 6 (Nov 2026, asked for by Ali): /gta-6-system-requirements/, a
 * release-status page for "GTA 6 PC requirements" searches, plus links
 * to it from the GTA V Enhanced and RDR2 game pages, Can I Run It, How
 * Much VRAM Do I Need and the /games/ hub.
 *
 * Grand Theft Auto VI launched on 19 Nov 2026 on PS5 and Xbox Series X|S
 * only. Rockstar has announced no PC version and published no PC system
 * requirements (Rockstar Newswire and rockstargames.com/VI, checked
 * 21 Nov 2026). So the page never states a GTA 6 spec or FPS figure:
 *  - official facts and reports are kept apart, each with its source;
 *  - the "what Rockstar asked for last time" table is read from
 *    data/games-data.php (GTA V Enhanced, RDR2: publisher specs);
 *  - the console-level table compares graphics cards from
 *    data/hardware-data.php with the PS5's GPU, clearly labelled as a
 *    comparison, not a requirement.
 *
 * GTA 6 is NOT in data/games-data.php on purpose: Can I Run It, the FPS
 * Calculator, the games hub and the VideoGame schema all read that list,
 * and they need the publisher's own specs. When Rockstar publishes PC
 * requirements, add a 'gta-6' entry there (official min/rec, req scores,
 * source_url) and update cs_gta6_status() below; the game page follows.
 *
 * Same create-once pattern as inc/d10-pages.php: the page holds only its
 * shortcode, so the text lives here. A page an editor deletes is not
 * recreated. Title and description: inc/seo-compat.php.
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Bump when the page text or the links change (purges cache, refreshes llms.txt). */
if ( ! defined( 'CS_GTA6_REV' ) ) {
	define( 'CS_GTA6_REV', '2026-11-21-b' );
}

/** The slug of the status page. */
function cs_gta6_slug() {
	return 'gta-6-system-requirements';
}

/**
 * What we know, and when we last checked. Update this (and CS_GTA6_REV)
 * whenever Rockstar announces anything about PC.
 */
function cs_gta6_status() {
	return array(
		'checked'      => '2026-11-21',
		'pc_announced' => false,
	);
}

/** "21 November 2026", from the checked date. */
function cs_gta6_checked_label() {
	return date_i18n( 'j F Y', strtotime( cs_gta6_status()['checked'] . ' 12:00:00 UTC' ) );
}

/* ------------------------------------------------------------------ */
/* Page creation                                                       */
/* ------------------------------------------------------------------ */

function cs_gta6_page_create() {
	if ( wp_installing() || get_option( 'cs_gta6_page_created' ) ) {
		return;
	}
	if ( ! add_option( 'cs_gta6_page_lock', time(), '', 'no' ) ) {
		if ( time() - (int) get_option( 'cs_gta6_page_lock' ) > 300 ) {
			delete_option( 'cs_gta6_page_lock' );
		}
		return;
	}
	if ( ! get_page_by_path( cs_gta6_slug() ) ) {
		$ref = get_page_by_path( 'methodology' );
		$id  = wp_insert_post( array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'post_name'      => cs_gta6_slug(),
			'post_title'     => __( 'GTA 6 PC System Requirements and Release Status', 'computingskill' ),
			'post_author'    => $ref ? (int) $ref->post_author : 0,
			'post_content'   => '[cs_gta6_status]',
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
		), true );
		if ( is_wp_error( $id ) ) {
			delete_option( 'cs_gta6_page_lock' );
			return;
		}
		update_post_meta( $id, '_wp_page_template', 'page-templates/template-guide.php' );
	}
	update_option( 'cs_gta6_page_created', 1, true );
	delete_option( 'cs_gta6_page_lock' );
}
add_action( 'init', 'cs_gta6_page_create', 34 );

/**
 * Once per CS_GTA6_REV: date the pages whose text gained a GTA 6 link,
 * refresh llms.txt and the sitemap, and purge the page cache.
 */
function cs_gta6_rev_run() {
	if ( wp_installing() || ! get_option( 'cs_gta6_page_created' ) || get_option( 'cs_gta6_rev' ) === CS_GTA6_REV ) {
		return;
	}
	if ( ! add_option( 'cs_gta6_rev_lock', time(), '', 'no' ) ) {
		if ( time() - (int) get_option( 'cs_gta6_rev_lock' ) > 300 ) {
			delete_option( 'cs_gta6_rev_lock' );
		}
		return;
	}
	if ( function_exists( 'cs_set_page_modified' ) ) {
		$now = current_time( 'mysql', true );
		foreach ( cs_gta6_linked_pages() as $slug => $type ) {
			$post = get_page_by_path( $slug, OBJECT, $type );
			if ( $post && 'publish' === $post->post_status ) {
				cs_set_page_modified( $post->ID, $now );
			}
		}
	}
	if ( function_exists( 'cs_llms_write' ) ) {
		cs_llms_write();
	}
	if ( class_exists( '\RankMath\Sitemap\Cache' ) && method_exists( '\RankMath\Sitemap\Cache', 'invalidate_storage' ) ) {
		\RankMath\Sitemap\Cache::invalidate_storage();
	}
	do_action( 'litespeed_purge_all' );
	update_option( 'cs_gta6_rev', CS_GTA6_REV, true );
	delete_option( 'cs_gta6_rev_lock' );
}
add_action( 'init', 'cs_gta6_rev_run', 41 );

/* ------------------------------------------------------------------ */
/* Links to the page                                                   */
/* ------------------------------------------------------------------ */

/** slug => post type of the pages that link to the status page. */
function cs_gta6_linked_pages() {
	$pages = array(
		'gta-5-enhanced'          => 'cs_game',
		'red-dead-redemption-2'   => 'cs_game',
		'can-i-run-it'            => 'page',
		'how-much-vram-do-i-need' => 'cs_guide',
	);
	// How Much VRAM Do I Need may be a page rather than a guide.
	if ( ! get_page_by_path( 'how-much-vram-do-i-need', OBJECT, 'cs_guide' ) ) {
		$pages['how-much-vram-do-i-need'] = 'page';
	}
	return $pages;
}

function cs_gta6_page_url() {
	$page = get_page_by_path( cs_gta6_slug() );
	return $page && 'publish' === $page->post_status ? get_permalink( $page ) : '';
}

/** The short note added to the linked pages. */
function cs_gta6_note_html( $slug ) {
	$url = cs_gta6_page_url();
	if ( ! $url ) {
		return '';
	}
	$link = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'GTA 6 PC system requirements and release status', 'computingskill' ) . '</a>';
	if ( 'can-i-run-it' === $slug ) {
		/* translators: %s: link */
		$text = __( '<strong>Looking for GTA 6?</strong> Grand Theft Auto VI is out on PS5 and Xbox Series X|S only, and Rockstar has not published PC requirements, so it is not in this checker yet. See %s for what is official and how to get your PC ready.', 'computingskill' );
	} elseif ( 'how-much-vram-do-i-need' === $slug ) {
		/* translators: %s: link */
		$text = __( '<strong>Planning for GTA 6?</strong> There is no PC version or official VRAM figure yet. Our %s page compares graphics cards with the PS5 and explains why 8GB cards may need lower textures in recent console ports.', 'computingskill' );
	} else {
		/* translators: %s: link */
		$text = __( '<strong>Looking for GTA 6 on PC?</strong> Grand Theft Auto VI is out on PS5 and Xbox Series X|S only, and Rockstar has not announced a PC version or PC requirements yet. See %s, which compares this game’s official specs with what a console-level PC needs.', 'computingskill' );
	}
	return '<p class="cs-gta6-note">' . wp_kses_post( sprintf( $text, $link ) ) . '</p>';
}

function cs_gta6_content_links( $content ) {
	if ( is_admin() || ! in_the_loop() || ! is_main_query() || ! is_singular() ) {
		return $content;
	}
	$slug  = get_post_field( 'post_name' );
	$pages = cs_gta6_linked_pages();
	if ( ! isset( $pages[ $slug ] ) || get_post_type() !== $pages[ $slug ] ) {
		return $content;
	}
	return $content . cs_gta6_note_html( $slug );
}
add_filter( 'the_content', 'cs_gta6_content_links', 30 );

/** Banner on /games/ under the Can I Run It link (archive-cs_game.php). */
function cs_gta6_hub_link() {
	$url = cs_gta6_page_url();
	if ( ! $url ) {
		return '';
	}
	return '<a class="cs-games-cir cs-gta6-hub" href="' . esc_url( $url ) . '">'
		. '<span class="cs-games-cir-title">' . esc_html__( 'GTA 6 on PC: release status and requirements', 'computingskill' ) . '</span>'
		. '<span class="cs-games-cir-desc">' . esc_html__( 'Out on PS5 and Xbox Series X|S; no PC version or official PC specs yet. What is confirmed, and how to get your PC ready.', 'computingskill' ) . '</span>'
		. '<span class="cs-games-cir-go" aria-hidden="true">→</span></a>';
}

/* ------------------------------------------------------------------ */
/* Page content                                                        */
/* ------------------------------------------------------------------ */

/** Outbound source link. */
function cs_gta6_src( $url, $text ) {
	return '<a href="' . esc_url( $url ) . '" rel="noopener" target="_blank">' . esc_html( $text ) . '</a>';
}

/**
 * The PS5's GPU on our score scale. Sony's spec: AMD RDNA 2, 36 compute
 * units at up to 2.23 GHz (10.3 TFLOPS). Reviews usually compare it with
 * a desktop Radeon RX 6700, which sits between the RX 6650 XT (40) and the
 * RX 6700 XT (47) here. An approximate bar, not a benchmark.
 */
function cs_gta6_console_gpu_score() {
	return 42;
}

/** "Below", "About" or "Above" console level for a GPU score. */
function cs_gta6_band( $score ) {
	$c = cs_gta6_console_gpu_score();
	if ( $score < $c - 4 ) {
		return __( 'Below PS5 level', 'computingskill' );
	}
	if ( $score <= $c + 4 ) {
		return __( 'About PS5 level', 'computingskill' );
	}
	return __( 'Above PS5 level', 'computingskill' );
}

/** Graphics cards shown in the console-level table. */
function cs_gta6_table_gpus() {
	return array( 'gtx-1650', 'gtx-1660-super', 'rtx-2060', 'rx-6600', 'rtx-3060', 'rtx-4060', 'rx-6650-xt', 'arc-b580', 'rtx-3060-ti', 'rtx-5060', 'rtx-4060-ti', 'rx-6700-xt', 'rx-9060-xt', 'rtx-3070', 'rtx-5060-ti', 'rtx-4070', 'rx-7800-xt', 'rtx-5070', 'rx-9070-xt', 'rtx-5080' );
}

function cs_gta6_status_shortcode() {
	$link    = function ( $slug, $text ) {
		return function_exists( 'cs_tl_link' ) ? cs_tl_link( $slug, $text ) : esc_html( $text );
	};
	$checked = cs_gta6_checked_label();
	$games   = cs_get_games_data();

	$html = cs_tl_quick_answer( esc_html( sprintf(
		/* translators: %s: date */
		__( 'No. There are no official GTA 6 PC system requirements yet, because Rockstar has not announced a PC version. Grand Theft Auto VI launched on 19 November 2026 on PlayStation 5 and Xbox Series X|S only. Any “GTA 6 PC requirements” table you see elsewhere is a guess. Last checked: %s.', 'computingskill' ),
		$checked
	) ) );

	$html .= '<p>' . esc_html__( 'This page keeps official facts apart from reports and rumours, shows Rockstar’s own PC requirements for GTA V and Red Dead Redemption 2, and compares common graphics cards with the PS5 so you can judge how ready your PC is. We will update it the day Rockstar announces anything about PC.', 'computingskill' ) . '</p>';

	// Facts vs reports.
	$html .= '<h2 id="is-gta-6-on-pc">' . esc_html__( 'Is GTA 6 on PC? Official Facts vs Reports', 'computingskill' ) . '</h2>';
	$rows = array(
		array( esc_html__( 'Platforms at launch', 'computingskill' ), esc_html__( 'PlayStation 5 and Xbox Series X|S, released 19 November 2026', 'computingskill' ), esc_html__( 'Official', 'computingskill' ), cs_gta6_src( 'https://www.rockstargames.com/newswire/article/39akk424a9oo53/grand-theft-auto-vi-now-available', __( 'Rockstar Newswire, 19 Nov 2026', 'computingskill' ) ) ),
		array( esc_html__( 'PC version', 'computingskill' ), esc_html__( 'Not announced. The official GTA VI page lists only PS5 and Xbox Series X|S', 'computingskill' ), esc_html__( 'Official', 'computingskill' ), cs_gta6_src( 'https://www.rockstargames.com/VI', __( 'rockstargames.com/VI', 'computingskill' ) ) ),
		array( esc_html__( 'PC system requirements', 'computingskill' ), esc_html__( 'None published', 'computingskill' ), esc_html__( 'Official', 'computingskill' ), cs_gta6_src( 'https://www.rockstargames.com/VI', __( 'rockstargames.com/VI', 'computingskill' ) ) ),
		array( esc_html__( 'Who will announce PC', 'computingskill' ), esc_html__( 'Take-Two’s CEO says Rockstar starts on console and that PC news will come from Rockstar itself', 'computingskill' ), esc_html__( 'Official (statements)', 'computingskill' ), cs_gta6_src( 'https://www.pcgamer.com/gaming-industry/pc-players-arent-grand-theft-auto-6s-core-audience-take-two-ceo-says-and-thats-why-we-have-to-wait-for-it/', __( 'PC Gamer', 'computingskill' ) ) ),
		array( esc_html__( 'PC release in early 2027', 'computingskill' ), esc_html__( 'GamesBeat reported on 13 Oct 2026, from four unnamed sources, that Rockstar plans PC for the first quarter of 2027. Rockstar did not comment', 'computingskill' ), esc_html__( 'Report, not confirmed', 'computingskill' ), cs_gta6_src( 'https://techspot.com/news/114187-gta-6-reportedly-coming-pc-q1-2027-months.html', __( 'TechSpot', 'computingskill' ) ) ),
		array( esc_html__( '“GTA 6 PC requirements” tables online', 'computingskill' ), esc_html__( 'Estimates made by websites, not Rockstar figures', 'computingskill' ), esc_html__( 'Guesswork', 'computingskill' ), '—' ),
	);
	$html .= cs_pp_table( sprintf( __( 'GTA 6 on PC: what is confirmed (checked %s)', 'computingskill' ), $checked ), array( __( 'Question', 'computingskill' ), __( 'Answer', 'computingskill' ), __( 'Status', 'computingskill' ), __( 'Source', 'computingskill' ) ), $rows );

	$html .= '<h3>' . esc_html__( 'How long Rockstar has made PC players wait before', 'computingskill' ) . '</h3>';
	$html .= cs_pp_table( __( 'Rockstar console and PC release dates', 'computingskill' ), array( __( 'Game', 'computingskill' ), __( 'Console release', 'computingskill' ), __( 'PC release', 'computingskill' ), __( 'Wait', 'computingskill' ) ), array(
		array( esc_html__( 'Grand Theft Auto V', 'computingskill' ), esc_html__( '17 Sep 2013', 'computingskill' ), esc_html__( '14 Apr 2015', 'computingskill' ), esc_html__( 'about 19 months', 'computingskill' ) ),
		array( esc_html__( 'Red Dead Redemption 2', 'computingskill' ), esc_html__( '26 Oct 2018', 'computingskill' ), esc_html__( '5 Nov 2019', 'computingskill' ), esc_html__( 'about 12 months', 'computingskill' ) ),
		array( esc_html__( 'Grand Theft Auto VI', 'computingskill' ), esc_html__( '19 Nov 2026', 'computingskill' ), esc_html__( 'Not announced', 'computingskill' ), '—' ),
	) );
	$html .= '<p>' . esc_html__( 'Past gaps are history, not a promise: the reported early-2027 window would be much shorter than either, and only Rockstar can confirm a date.', 'computingskill' ) . '</p>';

	// Last two PC ports.
	$html .= '<h2 id="rockstar-pc-requirements">' . esc_html__( 'Rockstar’s Own PC Requirements for GTA V and RDR2', 'computingskill' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'These are Rockstar’s own published PC requirements for Grand Theft Auto V Enhanced (2025) and Red Dead Redemption 2 (2019), its two big open-world games in our library. They are not GTA 6 requirements, but they show how Rockstar sets its minimum and recommended tiers, and both games are a fair test of an open-world PC today.', 'computingskill' ) . '</p>';
	$rows = array();
	foreach ( array( 'gta-5-enhanced', 'red-dead-redemption-2' ) as $slug ) {
		if ( ! isset( $games[ $slug ] ) ) {
			continue;
		}
		$g      = $games[ $slug ];
		$name   = '<a href="' . esc_url( home_url( '/games/' . $slug . '/' ) ) . '">' . esc_html( $g['name'] ) . '</a>';
		$rows[] = array( $name . ' ' . esc_html__( '(minimum)', 'computingskill' ), esc_html( $g['min']['cpu'] ), esc_html( $g['min']['gpu'] ), esc_html( $g['min']['ram'] . 'GB' ), esc_html( $g['min']['storage_gb'] . 'GB' ) );
		$rows[] = array( $name . ' ' . esc_html__( '(recommended)', 'computingskill' ), esc_html( $g['rec']['cpu'] ), esc_html( $g['rec']['gpu'] ), esc_html( $g['rec']['ram'] . 'GB' ), esc_html( $g['rec']['storage_gb'] . 'GB' ) );
	}
	if ( $rows ) {
		$html .= cs_pp_table( __( 'Rockstar’s published PC requirements (not GTA 6)', 'computingskill' ), array( __( 'Game and tier', 'computingskill' ), __( 'CPU', 'computingskill' ), __( 'GPU', 'computingskill' ), __( 'RAM', 'computingskill' ), __( 'Storage', 'computingskill' ) ), $rows );
	}
	$html .= '<p>' . wp_kses_post( sprintf(
		/* translators: %s: link */
		__( 'GTA V Enhanced (2025) lists an SSD as a minimum requirement, not just a recommendation. To see how your own PC handles both games today, use %s.', 'computingskill' ),
		$link( 'can-i-run-it', __( 'Can I Run It', 'computingskill' ) )
	) ) . '</p>';

	// Console-level comparison.
	$html .= '<h2 id="console-level-check">' . esc_html__( 'Can My PC Run GTA 6? A Console-Level Check', 'computingskill' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'Until Rockstar publishes PC requirements, the only solid reference is the hardware GTA 6 already runs on. Sony lists the PS5 as an 8-core AMD Zen 2 CPU at up to 3.5 GHz, an AMD RDNA 2 GPU with 36 compute units at up to 2.23 GHz (10.3 TFLOPS) and 16GB of GDDR6 memory shared by the game and the system. The Xbox Series X is a little faster on paper (12 TFLOPS) and the Series S much slower (4 TFLOPS).', 'computingskill' ) . '</p>';
	$html .= '<p>' . esc_html__( 'Reviews usually compare the PS5’s graphics with a desktop Radeon RX 6700, which sits between the RX 6650 XT and RX 6700 XT on our score scale. The table places common graphics cards against that level.', 'computingskill' ) . '</p>';

	$gpus = cs_get_gpu_data();
	$rows = array();
	foreach ( cs_gta6_table_gpus() as $slug ) {
		if ( ! isset( $gpus[ $slug ] ) ) {
			continue;
		}
		$p      = $gpus[ $slug ];
		$rows[] = array( cs_pp_part_link( $slug, $p, trim( preg_replace( '/\s+\d+GB$/', '', cs_pp_short( $p ) ) ) ), esc_html( (int) $p['vram_gb'] . 'GB' ), esc_html( (string) (int) $p['score'] ), esc_html( cs_gta6_band( (int) $p['score'] ) ) );
	}
	$html .= cs_pp_table( __( 'Graphics cards compared with the PS5 GPU (a comparison, not GTA 6 requirements)', 'computingskill' ), array( __( 'Graphics card', 'computingskill' ), __( 'VRAM', 'computingskill' ), __( 'Our gaming score', 'computingskill' ), __( 'Compared with PS5', 'computingskill' ) ), $rows );
	$html .= '<p>' . wp_kses_post( sprintf(
		/* translators: 1: methodology link, 2: GPU tier list link */
		__( 'Scores are ComputingSkill’s gaming index (RTX 4090 = 100), positioned from public review averages; the PS5 level is approximate. See %1$s, and the %2$s for every card. Matching a console on paper does not mean matching its result: consoles run a fixed set of settings, often with upscaling and a 30 or 60 FPS target, and PC versions usually add higher settings.', 'computingskill' ),
		$link( 'methodology', __( 'how the scores work', 'computingskill' ) ),
		$link( 'gpu-tier-list', __( 'GPU tier list', 'computingskill' ) )
	) ) . '</p>';

	$html .= '<h3>' . esc_html__( 'VRAM, CPU, RAM and storage', 'computingskill' ) . '</h3><ul>';
	$html .= '<li>' . wp_kses_post( sprintf(
		/* translators: %s: link */
		__( '<strong>VRAM:</strong> a console can give a large part of its 16GB to graphics, while an 8GB card cannot. In several recent console-first PC ports, 8GB cards have needed lower texture settings to avoid stutter, so 12GB or more is the safer choice for a new card. See %s.', 'computingskill' ),
		$link( 'how-much-vram-do-i-need', __( 'How Much VRAM Do I Need?', 'computingskill' ) )
	) ) . '</li>';
	$html .= '<li>' . wp_kses_post( sprintf(
		/* translators: %s: link */
		__( '<strong>CPU:</strong> the consoles have 8 Zen 2 cores at modest clocks. Most current 6- and 8-core desktop CPUs, such as the Ryzen 5 5600 or Core i5-12400F, are faster per core; big open worlds with crowds and traffic lean on the CPU, so a 4-core chip is the weak spot to watch. Check your pair with the %s.', 'computingskill' ),
		$link( 'pc-bottleneck-calculator', __( 'PC Bottleneck Calculator', 'computingskill' ) )
	) ) . '</li>';
	$html .= '<li>' . wp_kses_post( sprintf(
		/* translators: %s: link */
		__( '<strong>RAM:</strong> GTA V Enhanced recommends 16GB and RDR2 12GB. 16GB is the sensible floor for a new open-world game, 32GB if you stream or keep a lot open. See the %s.', 'computingskill' ),
		$link( 'ram-calculator', __( 'RAM Calculator', 'computingskill' ) )
	) ) . '</li>';
	$html .= '<li>' . wp_kses_post( sprintf(
		/* translators: %s: link */
		__( '<strong>Storage:</strong> both consoles load from a fast SSD, and GTA V Enhanced already requires an SSD on PC. Rockstar has not stated a PC install size; plan for an NVMe SSD with plenty of free space (GTA V Enhanced needs 105GB and RDR2 150GB). The %s helps you plan space.', 'computingskill' ),
		$link( 'storage-calculator', __( 'Storage Calculator', 'computingskill' ) )
	) ) . '</li>';
	$html .= '<li>' . wp_kses_post( sprintf(
		/* translators: %s: link */
		__( '<strong>Power:</strong> if you upgrade the graphics card, check the power supply too with the %s.', 'computingskill' ),
		$link( 'psu-calculator', __( 'PSU Calculator', 'computingskill' ) )
	) ) . '</li></ul>';

	// Laptops.
	$html .= '<h2 id="gta-6-laptop">' . esc_html__( 'GTA 6 on a Laptop: What to Look For', 'computingskill' ) . '</h2>';
	$html .= '<p>' . esc_html__( 'There are no laptop requirements either, but the same console-level logic applies, with a few laptop-specific traps:', 'computingskill' ) . '</p><ul>';
	$html .= '<li>' . esc_html__( 'A dedicated graphics card (GeForce RTX or Radeon RX). Laptops with only integrated graphics are far below console level for a game like this.', 'computingskill' ) . '</li>';
	$html .= '<li>' . esc_html__( 'Laptop GPUs are slower than desktop cards with the same name, and the same laptop GPU varies with the power limit (TGP) the maker sets. Compare reviews of the exact model, not just the GPU name.', 'computingskill' ) . '</li>';
	$html .= '<li>' . esc_html__( '8GB of VRAM is common on mid-range gaming laptops; it works, but expect to lower textures in demanding games. Higher-end laptop GPUs with 12GB or more give more room.', 'computingskill' ) . '</li>';
	$html .= '<li>' . esc_html__( '16GB of RAM (two sticks, dual channel) and a 1TB SSD at least, since big games fill a 512GB drive quickly.', 'computingskill' ) . '</li>';
	$html .= '<li>' . wp_kses_post( sprintf(
		/* translators: %s: link */
		__( 'Play plugged in, in the laptop’s performance mode, and make sure the game runs on the dedicated GPU. %s covers the common causes of low FPS on laptops.', 'computingskill' ),
		$link( 'low-gpu-usage-in-games', __( 'Low GPU usage in games', 'computingskill' ) )
	) ) . '</li></ul>';

	// Safety.
	$html .= '<div class="cs-quick-answer cs-gta6-warning"><p class="cs-quick-answer-label">' . esc_html__( 'Safety warning', 'computingskill' ) . '</p><div class="cs-quick-answer-body"><p>' . esc_html__( 'There is no official GTA 6 PC download, beta, demo or “early access”. Sites and videos offering one are scams that spread malware or steal accounts. When the PC version is real, Rockstar will announce it on its own website, and it will be sold through official stores.', 'computingskill' ) . '</p></div></div>';

	// When Rockstar announces.
	$html .= '<h2 id="when-rockstar-announces">' . esc_html__( 'What Happens When Rockstar Announces the PC Version', 'computingskill' ) . '</h2>';
	$html .= '<p>' . wp_kses_post( sprintf(
		/* translators: 1: games link, 2: Can I Run It link, 3: FPS calculator link */
		__( 'As soon as Rockstar publishes official PC requirements, we will add GTA 6 to our %1$s with its minimum and recommended specs, and to %2$s and the %3$s, so you can check your own CPU and graphics card against them. Until then, this page is the place to check.', 'computingskill' ),
		'<a href="' . esc_url( home_url( '/games/' ) ) . '">' . esc_html__( 'game requirements library', 'computingskill' ) . '</a>',
		$link( 'can-i-run-it', __( 'Can I Run It', 'computingskill' ) ),
		$link( 'fps-calculator', __( 'FPS Calculator', 'computingskill' ) )
	) ) . '</p>';

	$html .= cs_tl_faq( array(
		__( 'Is GTA 6 available on PC?', 'computingskill' ) => sprintf(
			/* translators: %s: date */
			__( 'No. Grand Theft Auto VI launched on 19 November 2026 on PlayStation 5 and Xbox Series X|S only, and Rockstar has not announced a PC version (checked %s).', 'computingskill' ),
			$checked
		),
		__( 'When is GTA 6 coming to PC?', 'computingskill' ) => __( 'There is no official date. GamesBeat reported in October 2026, from unnamed sources, that Rockstar plans PC for the first quarter of 2027, but Rockstar has not confirmed it. GTA V took about 19 months to reach PC and Red Dead Redemption 2 about 12.', 'computingskill' ),
		__( 'What are the official GTA 6 PC system requirements?', 'computingskill' ) => __( 'There are none yet. Rockstar has not published PC requirements, so any minimum or recommended table online is an estimate. For a reference, see Rockstar’s published requirements for GTA V Enhanced and Red Dead Redemption 2 above.', 'computingskill' ),
		__( 'Can my laptop run GTA 6?', 'computingskill' ) => __( 'Nobody can say for certain until Rockstar publishes PC requirements. A gaming laptop with a dedicated RTX or RX graphics card near PS5 level, 16GB of RAM and a fast SSD is the safest starting point; laptops with only integrated graphics are well below console level.', 'computingskill' ),
		__( 'Will an RTX 3060 or RTX 4060 be enough for GTA 6?', 'computingskill' ) => __( 'Without official requirements, the honest answer is a comparison: on our scale the RTX 3060 sits below the PS5’s graphics and the RTX 4060 about level with it, and the RTX 4060 has only 8GB of VRAM, so expect console-like settings rather than high or ultra. Cards from the RTX 5060 Ti 16GB and RX 9060 XT 16GB up are above console level with more memory.', 'computingskill' ),
		__( 'Is there a GTA 6 PC download?', 'computingskill' ) => __( 'No. Rockstar has released no PC version, beta or demo. Any site offering a GTA 6 PC download is a scam.', 'computingskill' ),
	) );
	return $html;
}
add_shortcode( 'cs_gta6_status', 'cs_gta6_status_shortcode' );
