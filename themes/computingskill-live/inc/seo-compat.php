<?php
/**
 * SEO plugin compatibility layer.
 *
 * The whole point of this file: the THEME NEVER fights Rank Math or Yoast
 * for control of title tags, meta descriptions, canonical URLs, robots
 * meta, Open Graph tags or Twitter Card tags. We detect whether a
 * recognised SEO plugin is active and only ever fall back to a minimal,
 * safe default when NONE is installed (so the site is never left with a
 * blank <title> on a fresh install), and we skip our own JSON-LD schema
 * types that those plugins already generate.
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * True if Yoast SEO or Rank Math (or another well-known SEO plugin) is
 * active and therefore already controls meta tags.
 */
function cs_seo_plugin_active() {
	static $active = null;
	if ( null !== $active ) {
		return $active;
	}
	$active = (
		defined( 'WPSEO_VERSION' ) ||           // Yoast SEO.
		defined( 'RANK_MATH_VERSION' ) ||        // Rank Math.
		class_exists( 'RankMath' ) ||
		defined( 'AIOSEO_VERSION' ) ||            // All in One SEO (bonus compatibility).
		defined( 'SEOPRESS_VERSION' )             // SEOPress (bonus compatibility).
	);
	return $active;
}

/**
 * Minimal fallback <title> only when no SEO plugin is active.
 * add_theme_support( 'title-tag' ) already lets WordPress core build the
 * tag via wp_get_document_title() — we simply don't override it, and we
 * never print our own <title> tag in header.php. This function exists so
 * other theme code can ask "should I show my own share of meta?".
 */
function cs_should_render_fallback_meta() {
	return ! cs_seo_plugin_active();
}

/**
 * Fallback meta description — ONLY printed when no SEO plugin is active,
 * so a brand-new install never ships with zero meta descriptions. Any
 * Rank Math / Yoast install immediately supersedes this.
 */
function cs_fallback_meta_description() {
	if ( ! cs_should_render_fallback_meta() ) {
		return;
	}

	$description = '';
	if ( is_singular() ) {
		global $post;
		if ( $post ) {
			$excerpt    = has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_trim_words( wp_strip_all_tags( $post->post_content ), 30 );
			$description = $excerpt;
		}
	} elseif ( is_front_page() ) {
		$description = __( 'Free PC hardware calculators, computer tools and computing guides. Calculate, compare and optimize your PC before you buy, build or upgrade.', 'computingskill' );
	} elseif ( is_category() || is_tax() || is_post_type_archive() ) {
		$description = trim( strip_tags( term_description() ) );
	}

	if ( $description ) {
		echo '<meta name="description" content="' . esc_attr( wp_trim_words( $description, 40 ) ) . '" />' . "\n";
	}
}
add_action( 'wp_head', 'cs_fallback_meta_description', 5 );

/**
 * Fallback canonical URL — again, only without an SEO plugin, since Yoast
 * and Rank Math both output rel=canonical themselves (and WordPress core
 * already outputs one by default via wp_head -> rel_canonical(), so we
 * don't need to add anything here; this function is left as a documented
 * no-op to make the intent explicit for future maintainers).
 */
function cs_fallback_canonical_notice() {
	// Intentionally empty: WordPress core's rel_canonical() in wp-includes
	// already prints a canonical tag on singular views when no SEO plugin
	// removes/replaces it. Do not duplicate it here.
}

/**
 * Tell the theme whether it's safe to print our own JSON-LD (see
 * inc/schema.php). We still emit BreadcrumbList and SoftwareApplication
 * schema even when an SEO plugin is active, since those are structural
 * content facts the plugins do not always generate (Rank Math only adds
 * FAQ/HowTo schema when its blocks are used) — but we NEVER emit
 * competing WebPage/Article/title/description schema when a plugin is
 * active, to avoid duplicate structured data.
 */
function cs_schema_should_emit( $type ) {
	if ( ! cs_seo_plugin_active() ) {
		return true;
	}
	// With an SEO plugin active, only add the structural types it is
	// unlikely to already provide from plain theme markup.
	// ItemList is included because it only ever prints on the Blog hub
	// page, built from our own real content index -- Rank Math/Yoast
	// don't generate list schema from plain card markup, so there's
	// nothing for it to conflict with. VideoGame is included for the
	// same reason on individual /games/{slug}/ pages: it's built from
	// our own verified data/games-data.php, not something an SEO plugin
	// infers from plain spec-table markup.
	$always_safe = array( 'BreadcrumbList', 'SoftwareApplication', 'FAQPage', 'ItemList', 'VideoGame', 'Dataset' );
	return in_array( $type, $always_safe, true );
}

/**
 * Force-include specific custom post types in the Rank Math XML sitemap.
 *
 * Confirmed live on computingskill.com: /cs_bottleneck-sitemap.xml exists
 * and is populated, but /cs_game-sitemap.xml returns 404 — the sitemap
 * index only lists page-sitemap.xml and cs_bottleneck-sitemap.xml, even
 * though `cs_game` is registered with the exact same public/show_in_rest
 * args as `cs_bottleneck` (see inc/cpt.php). The difference is Rank
 * Math's per-post-type "Include in sitemap" toggle under Titles & Meta →
 * Post Types, which does not automatically turn on for a post type
 * registered after that settings screen was last saved. All 16 Games
 * pages have therefore never been announced to Google via the sitemap —
 * Google can only learn about them by crawling internal links, which is
 * a classic cause of Search Console's "Discovered - currently not
 * indexed" status. This filter guarantees the sitemap inclusion at the
 * code level, independent of that admin toggle.
 */
function cs_force_sitemap_post_types( $post_types ) {
	foreach ( array( 'cs_game', 'cs_bottleneck' ) as $pt ) {
		if ( post_type_exists( $pt ) ) {
			$post_types[ $pt ] = $pt;
		}
	}
	return $post_types;
}
add_filter( 'rank_math/sitemap/post_types', 'cs_force_sitemap_post_types' );

/**
 * Belt-and-suspenders companion to cs_force_sitemap_post_types(): Rank
 * Math also checks a per-post-type "excluded" flag when deciding what to
 * render. Force it to false for the post types above so neither toggle
 * can leave them out.
 */
function cs_never_exclude_from_sitemap( $is_excluded, $post_type ) {
	if ( in_array( $post_type, array( 'cs_game', 'cs_bottleneck' ), true ) ) {
		return false;
	}
	return $is_excluded;
}
add_filter( 'rank_math/sitemap/exclude_post_type', 'cs_never_exclude_from_sitemap', 10, 2 );

/**
 * Targeted <title> length fixes found in the Sep 2026 audit — done through
 * Rank Math's own `rank_math/frontend/title` filter (the same pattern as
 * the sitemap filters above), never by editing the Homepage/Post-Type
 * title fields in Rank Math's admin UI, so this travels with the theme
 * and needs no live-site settings change:
 *
 * - Homepage title measured 91 characters live — well past the ~60 that
 *   Google reliably shows before truncating in search results.
 * - Every /bottleneck/{cpu}-{gpu}/ page's title measured ~73 characters,
 *   because the post_title alone (e.g. "AMD Ryzen 5 5600 + Radeon RX 7600
 *   Bottleneck Calculator") is already long, then Rank Math's title
 *   template appends its usual " - Computing Skill" suffix on top. The
 *   post_title is already unique and descriptive, so we drop the suffix
 *   for this post type only rather than shorten 30+ hardware names.
 *
 * - /games/{slug}/ page titles were just the bare game name (e.g.
 *   "Cyberpunk 2077 - Computing Skill") found during the Sep 2026 audit.
 *   People searching for this content type almost always search "<game>
 *   system requirements" or "can I run <game>", not the bare game name, so
 *   the bare-name title wasn't matching that intent in the title tag even
 *   though the page itself is exactly a system-requirements page. Appending
 *   "System Requirements" (and dropping the site suffix, same reasoning as
 *   the bottleneck pages above, to stay under ~60 characters even for the
 *   longest game names) fixes that without touching post_title, the H1, or
 *   the URL.
 */
function cs_shorten_long_titles( $title ) {
	if ( is_front_page() ) {
		return __( 'Computing Skill – Calculate & Optimize Your PC Build', 'computingskill' );
	}
	if ( is_singular( 'cs_bottleneck' ) ) {
		return cs_bn_title_tag( get_queried_object_id() );
	}
	// E3 (SEO-03): 35 of the 54 comparison titles were 61-72 characters.
	if ( is_singular( 'cs_comparison' ) && function_exists( 'cs_pp_vs_title_tag' ) ) {
		$vs = cs_pp_vs_title_tag( get_queried_object_id() );
		if ( '' !== $vs ) {
			return $vs;
		}
	}
	if ( is_singular( 'cs_game' ) ) {
		/* translators: %s: game name */
		$base = sprintf( __( '%s System Requirements', 'computingskill' ), get_the_title() );
		// D6 (2.6): short game names gave 28-character titles. Say what the
		// page lists when it still fits in 60 characters.
		/* translators: %s: game name */
		$long = sprintf( __( '%s PC System Requirements (Minimum & Recommended)', 'computingskill' ), get_the_title() );
		return cs_title_length( $long ) <= 60 ? $long : $base;
	}
	$overrides = cs_title_overrides();
	if ( is_post_type_archive( 'cs_game' ) && isset( $overrides['archive:cs_game'] ) ) {
		return $overrides['archive:cs_game'];
	}
	if ( is_category() || is_tag() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term && isset( $overrides[ 'term:' . $term->slug ] ) ) {
			return $overrides[ 'term:' . $term->slug ];
		}
	}
	if ( is_singular() ) {
		$slug = get_post_field( 'post_name', get_queried_object_id() );
		if ( isset( $overrides[ $slug ] ) ) {
			return $overrides[ $slug ];
		}
	}
	// D6 (2.6): a title over 60 characters drops the " - Computing Skill"
	// suffix first; the page name is what has to show in search results.
	if ( cs_title_length( $title ) > 60 ) {
		$short = preg_replace( '/\s+[-–|]\s+Computing Skill$/u', '', $title );
		if ( $short !== $title && cs_title_length( $short ) >= 30 ) {
			return $short;
		}
	}
	return $title;
}

/**
 * Character count of a title as a searcher sees it (entities decoded).
 */
function cs_title_length( $title ) {
	return mb_strlen( html_entity_decode( wp_strip_all_tags( (string) $title ), ENT_QUOTES, 'UTF-8' ) );
}

/**
 * Deep audit D6 (2.6): titles under 30 characters (hubs, About, Contact,
 * legal pages, the Games archive) said too little, and the new post's title
 * was 82 characters. Each line is 30-60 characters and says what the page
 * really holds. Keys are page/post slugs, "archive:{post type}" or
 * "term:{slug}". The H1 and the page text are not changed.
 */
function cs_title_overrides() {
	return array(
		'about-us'                  => __( 'About Computing Skill: Who Makes These PC Tools', 'computingskill' ),
		'blog'                      => __( 'PC Building Blog: Guides, Comparisons & Articles', 'computingskill' ),
		'cpu-vs-gpu-calculator'     => __( 'CPU vs GPU Calculator: Side-by-Side Score Comparison', 'computingskill' ),
		'ram'                       => __( 'What Is RAM? How Memory Works & Which Specs Matter', 'computingskill' ),
		'psu'                       => __( 'What Is a PSU? How Power Supplies Work & Their Ratings', 'computingskill' ),
		'gpu'                       => __( 'What Is a GPU? How Graphics Cards Work & Key Specs', 'computingskill' ),
		'comparisons'               => __( 'PC Hardware Comparisons: SSD vs HDD, DDR4 vs DDR5', 'computingskill' ),
		'contact'                   => __( 'Contact Computing Skill: Questions & Spec Corrections', 'computingskill' ),
		'disclaimer'                => __( 'Disclaimer: Estimates & Trademarks - Computing Skill', 'computingskill' ),
		'guides'                    => __( 'PC Building, Upgrade & Gaming Guides - Computing Skill', 'computingskill' ),
		'hardware'                  => __( 'PC Hardware Explained: CPU, GPU, RAM, PSU & More', 'computingskill' ),
		'methodology'               => __( 'Methodology: How Our PC Calculators Work', 'computingskill' ),
		'ssd-vs-hdd'                => __( 'SSD vs HDD: Speed, Durability, Price & Which to Pick', 'computingskill' ),
		'best-gpu-for-ryzen-5-5600' => __( 'Best GPU for Ryzen 5 5600 & 5600X (2026, No Bottleneck)', 'computingskill' ),
		'gpu-tier-list'             => __( 'GPU Tier List 2026: Graphics Cards Ranked for Gaming', 'computingskill' ),
		'cpu-tier-list'             => __( 'CPU Tier List 2026: Best Gaming Processors Ranked', 'computingskill' ),
		// E3 (SEO-03): the post title is 61 characters.
		'dlss-fsr-frame-generation-explained' => __( 'DLSS vs FSR vs XeSS: Upscaling & Frame Generation Explained', 'computingskill' ),
		'archive:cs_game'           => __( 'PC Game System Requirements: Can Your PC Run It?', 'computingskill' ),
		'term:buying-guides'        => __( 'PC Buying Guides: Articles - Computing Skill', 'computingskill' ),
	);
}

/**
 * Short part name for titles and cards: drops the maker prefix ("Intel",
 * "AMD", "GeForce", "Radeon") and the brackets around a VRAM size, so
 * "GeForce RTX 5060 Ti (16GB)" reads "RTX 5060 Ti 16GB".
 */
function cs_bn_short_name( $name ) {
	$name = preg_replace( '/^(Intel|AMD|NVIDIA)\s+/', '', $name );
	$name = preg_replace( '/^(GeForce|Radeon)\s+/', '', $name );
	$name = preg_replace( '/\s*\((\d+GB)\)/', ' $1', $name );
	return trim( $name );
}

/**
 * Deep audit D6 (2.6, 2.10): combo title tags were "{CPU} + {GPU}
 * Bottleneck Calculator", up to 71 characters, on pages that have no
 * calculator. Now short part names plus the resolutions the page covers,
 * in at most 60 characters.
 */
function cs_bn_title_tag( $post_id ) {
	$cpus = cs_get_cpu_data();
	$gpus = cs_get_gpu_data();
	$c    = get_post_meta( $post_id, 'cs_cpu', true );
	$g    = get_post_meta( $post_id, 'cs_gpu', true );
	if ( ! isset( $cpus[ $c ], $gpus[ $g ] ) ) {
		return get_the_title( $post_id );
	}
	$pair = cs_bn_short_name( $cpus[ $c ]['name'] ) . ' + ' . cs_bn_short_name( $gpus[ $g ]['name'] );
	foreach ( array( __( ' Bottleneck: 1080p, 1440p & 4K', 'computingskill' ), __( ' Bottleneck at 1080p-4K', 'computingskill' ) ) as $suffix ) {
		if ( cs_title_length( $pair . $suffix ) <= 60 ) {
			return $pair . $suffix;
		}
	}
	return $pair . __( ' Bottleneck', 'computingskill' );
}
add_filter( 'rank_math/frontend/title', 'cs_shorten_long_titles' );

/**
 * Games archive (/games/) meta description found live during the audit:
 * Rank Math had no explicit description configured for the cs_game
 * archive, so it fell back to reusing the title verbatim ("Games Archive
 * - Computing Skill") as the description too — not a real summary, and
 * identical to the title tag right above it in search results. Same
 * pattern as above: fixed through Rank Math's own filter, not its admin
 * UI, so it isn't lost on a future settings change.
 */
function cs_games_archive_description( $description ) {
	if ( is_post_type_archive( 'cs_game' ) ) {
		// D6 (2.7): was 165 characters.
		return __( 'Minimum and recommended PC specs for popular games: CPU, GPU, RAM and storage, so you can check whether your PC runs them before you buy or upgrade.', 'computingskill' );
	}
	return $description;
}
add_filter( 'rank_math/frontend/description', 'cs_games_archive_description' );


/**
 * Meta description fixes (Sep 2026 audit): 29 pages had descriptions under
 * ~110 characters — too short to use the space Google shows (roughly
 * 150–160) and in two cases containing a stray "computingskill.com/" typo.
 * Written against each page's actual content, applied through Rank Math's
 * own description filter (same pattern as the title fixes above) so they
 * travel with the theme. Only these slugs are touched; every other page
 * keeps the description set in Rank Math.
 */
function cs_page_description_overrides() {
	return array(
		'gpu-tier-list' => __( 'Every gaming GPU ranked in S to D tiers by score, with estimated FPS at 1080p, 1440p and 4K, VRAM, power and the best graphics cards for each resolution.', 'computingskill' ),
		'cpu-tier-list' => __( 'Every gaming CPU ranked in S to D tiers by score, with FPS ceilings, cores, socket, the fastest GPU each drives at 1440p and the best CPU per platform.', 'computingskill' ),
		'fps-calculator' => __( 'Estimate FPS for any CPU and GPU pairing at 1080p, 1440p or 4K and your graphics preset, see which part limits frame rate, and compare a game\'s official specs.', 'computingskill' ),
		'hdd' => __( 'How hard drives work, the specs that matter (capacity, RPM, cache), how HDDs compare to SSDs, and when an HDD is still the smart choice for bulk storage.', 'computingskill' ),
		'mb-s-to-mbps-converter' => __( 'Convert MB/s to Mbps instantly (multiply by 8) to compare a measured download or copy speed against your internet plan\'s advertised megabit speed.', 'computingskill' ),
		'mbps-to-mb-s-converter' => __( 'Convert Mbps to MB/s instantly (divide by 8) and see why ISPs advertise speed in megabits while downloads and file copies show megabytes per second.', 'computingskill' ),
		'internet-tools' => __( 'Free internet calculators: download, upload and file transfer time, bandwidth planning, monthly data usage, and Mbps to MB/s conversions in both directions.', 'computingskill' ),
		'comparisons' => __( 'Side-by-side comparisons of SSD vs HDD, NVMe vs SATA, DDR4 vs DDR5 and 1080p vs 1440p vs 4K — real differences, where they matter, and which to choose.', 'computingskill' ),
		'contact' => __( 'Contact ComputingSkill to ask a question, report a hardware spec that looks wrong so we can check it against the manufacturer\'s source, or send feedback.', 'computingskill' ),
		'cpu-power-consumption-calculator' => __( 'Estimate your CPU\'s power draw at idle, gaming and full load, using the manufacturer\'s rated TDP and maximum turbo/boost power for Intel and AMD processors.', 'computingskill' ),
		'aspect-ratio-calculator' => __( 'Simplify any resolution to its exact aspect ratio and see the common name it\'s marketed under, like 16:9, 21:9 ultrawide, 16:10 or 4:3 — with the math shown.', 'computingskill' ),
		'display-tools' => __( 'Free display calculators for DPI, PPI, monitor size, resolution names, aspect ratio and refresh rate, to compare screens and check print sharpness.', 'computingskill' ),
		'data-storage-converter' => __( 'Convert between bits, bytes, KB, MB, GB, TB and PB, and see why a drive sold as 1TB shows about 931GB in Windows (decimal vs binary units explained).', 'computingskill' ),
		'file-transfer-time-calculator' => __( 'Estimate file transfer time over USB 2.0 to USB 3.2, SATA, NVMe or 1/2.5/10 Gigabit Ethernet using each interface\'s real usable speed, or enter your own.', 'computingskill' ),
		'ssd' => __( 'How SSDs work, NVMe vs SATA, and the specs that matter — interface, NAND type, DRAM cache, TBW endurance — plus how to choose the right SSD for your PC.', 'computingskill' ),
		'storage-calculator' => __( 'Estimate how much space your files, games or media library need, and check whether they\'ll fit on your drive with sensible headroom left over.', 'computingskill' ),
		'storage-tools' => __( 'Free storage calculators: SSD lifespan and TBW/DWPD endurance, drive space planning, and storage unit conversions, plus SSD, HDD and NVMe background guides.', 'computingskill' ),
		'pc-compatibility-checker' => __( 'Check socket and BIOS support, DDR4 vs DDR5, board size, cooler height and GPU length vs your case, PSU headroom and the 16-pin GPU cable before you build a PC.', 'computingskill' ),
		'ssd-lifespan-calculator' => __( 'Estimate how many years your SSD should last from its TBW endurance rating and your daily write volume — and why TBW is a warranty limit, not a failure date.', 'computingskill' ),
		'ssd-tbw-calculator' => __( 'Convert an SSD\'s DWPD rating to TBW (or TBW to DWPD) from capacity and warranty length, and compare drives fairly with a TBW-per-terabyte figure.', 'computingskill' ),
		'cpu-performance-calculator' => __( 'See a CPU\'s gaming-performance score and tier, from Entry-level to Flagship, placed from published review averages, with its cores, clocks, TDP and socket.', 'computingskill' ),
		'gpu-performance-calculator' => __( 'See a graphics card\'s gaming-performance score (RTX 4090 = 100) and tier, placed from published review averages, with its VRAM, memory bus, TDP and PSU need.', 'computingskill' ),
		'pc-build-cost-calculator' => __( 'Add up your PC build part by part, see what share goes to the CPU, GPU, motherboard, RAM, storage, PSU, case and cooling, and how much of your budget is left.', 'computingskill' ),
		'pc-case' => __( 'How PC case size, airflow, GPU clearance, cooler height and radiator support affect your build, and how to choose the right case form factor and layout.', 'computingskill' ),
		'refresh-rate-calculator' => __( 'Convert monitor refresh rate (Hz) to frame time in milliseconds and back — for example 144Hz = 6.94ms — and see why higher refresh rates feel smoother.', 'computingskill' ),
		'resolution-calculator' => __( 'Look up a resolution\'s standard name (720p, 1080p, 1440p, 4K, ultrawide), its total megapixels and its exact aspect ratio in one step.', 'computingskill' ),
		'privacy-policy' => __( 'How ComputingSkill handles contact messages, server logs, cookies and Google Analytics (only with your consent), what we never sell, and your privacy rights.', 'computingskill' ),
		'terms-of-use' => __( 'The terms for using ComputingSkill.com: free calculators and guides, estimates rather than guarantees, acceptable use, our content and trademarks.', 'computingskill' ),
		// D6 (2.7): the post's own description was 164 characters.
		'best-gpu-for-ryzen-5-5600' => __( 'Which graphics card suits a Ryzen 5 5600 or 5600X without a bottleneck? Estimates for 20 GPUs at 1080p, 1440p and 4K, with the best match for each.', 'computingskill' ),
		'editorial-policy' => __( 'How ComputingSkill writes and checks its calculators and guides: who writes them, where the numbers come from, independence, and how corrections are made.', 'computingskill' ),
	);
}

function cs_improve_short_descriptions( $description ) {
	if ( is_front_page() ) {
		// D6 (2.7): was 163 characters.
		return __( 'Free PC hardware calculators: estimate CPU/GPU bottlenecks, PSU wattage, FPS, RAM and VRAM needs, and game requirements before you buy, build or upgrade.', 'computingskill' );
	}
	if ( is_singular( array( 'page', 'post' ) ) ) {
		$map  = cs_page_description_overrides();
		$slug = get_post_field( 'post_name' );
		if ( isset( $map[ $slug ] ) ) {
			return $map[ $slug ];
		}
	}
	// Game pages with a short description get their real published RAM and
	// storage figures appended from data/games-data.php.
	if ( is_singular( 'cs_game' ) && function_exists( 'cs_get_games_data' ) && strlen( wp_strip_all_tags( (string) $description ) ) < 120 ) {
		$games = cs_get_games_data();
		$slug  = get_post_field( 'post_name' );
		if ( isset( $games[ $slug ] ) ) {
			$g   = $games[ $slug ];
			$ram = (int) $g['min']['ram'] === (int) $g['rec']['ram']
				? sprintf( '%dGB RAM', (int) $g['rec']['ram'] )
				: sprintf( '%d–%dGB RAM', (int) $g['min']['ram'], (int) $g['rec']['ram'] );
			$extra = sprintf( __( ' Needs %1$s and %2$dGB of storage.', 'computingskill' ), $ram, (int) $g['rec']['storage_gb'] );
			if ( strlen( $description . $extra ) <= 160 ) {
				$description .= $extra;
			}
		}
	}
	return $description;
}
add_filter( 'rank_math/frontend/description', 'cs_improve_short_descriptions', 20 );

/**
 * Shareable calculator links (Phase 5, Oct 2026): calculators.js writes the
 * inputs into the address (?cpu=…&gpu=…). Those URLs show the same page, so
 * the canonical must always be the clean page URL. Rank Math and core both
 * use the permalink already; this strips the share parameters as a guard in
 * case a setting or plugin update ever starts keeping the query string.
 * Keep the list in sync with the field ids of share-enabled calculators.
 */
function cs_share_param_keys() {
	return array( 'cpu', 'gpu', 'resolution', 'preset', 'gametype', 'game', 'cpuTdp', 'ramSticks', 'storageDrives', 'fans', 'overclock', 'upscaling', 'framegen' );
}

function cs_canonical_without_share_params( $url ) {
	if ( ! is_string( $url ) || false === strpos( $url, '?' ) ) {
		return $url;
	}
	return remove_query_arg( cs_share_param_keys(), $url );
}
add_filter( 'rank_math/frontend/canonical', 'cs_canonical_without_share_params', 20 );
add_filter( 'get_canonical_url', 'cs_canonical_without_share_params', 20 );

/**
 * Privacy Policy: say that share links carry calculator choices in the URL,
 * so "inputs are used only to compute the result" stays complete and true.
 * Matches the existing sentence; does nothing if that text is edited away.
 */
function cs_privacy_share_link_text( $content ) {
	if ( ! is_page( 'privacy-policy' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	$needle = 'are used only to compute the result shown to you.';
	if ( false === strpos( $content, $needle ) || false !== strpos( $content, 'cs-privacy-share' ) ) {
		return $content;
	}
	$extra = ' <span class="cs-privacy-share">' . esc_html__( 'On the Bottleneck, FPS and PSU calculators, your selections are also added to the page address after you press Calculate, so you can copy or bookmark a link to that result. We do not save results on our side. When you or anyone you share the link with opens it, those selections reach our server as part of the address, like any web request, and can appear in the standard server logs described under Log Data.', 'computingskill' ) . '</span>';
	return preg_replace( '/' . preg_quote( $needle, '/' ) . '/', $needle . $extra, $content, 1 );
}
add_filter( 'the_content', 'cs_privacy_share_link_text', 20 );
