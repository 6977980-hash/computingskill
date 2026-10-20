<?php
/**
 * ComputingSkill games dataset — minimum and recommended PC system
 * requirements for a pilot batch of popular titles.
 *
 * Sources: each game's official Steam/publisher store page system
 * requirements listing, cross-checked September 2026. These are the
 * publisher's own published minimum/recommended specs — standard,
 * publicly-stated technical facts, not benchmark results and not any
 * copyrighted box art, screenshots or marketing copy.
 *
 * Keyed by slug, which must match the cs_game post's post_name so
 * single-cs_game.php can pull structured spec data for the CPT post
 * whose written content lives in the post itself.
 *
 * 'publisher' (D6, Oct 2026): the publisher named on each game's store
 * page, used in the VideoGame schema.
 *
 * 'req' (D10, Oct 2026): the publisher's minimum and recommended CPU and
 * GPU placed on ComputingSkill's own score scale (data/hardware-data.php),
 * so Can I Run It can say whether a PC meets them. Where a tier lists two
 * parts, the slower one is used (either meets the publisher's bar). Parts
 * that aren't in our database are positioned the same way as our scores,
 * from publicly reported review averages relative to listed parts, e.g.
 * GTX 1060 6GB ~20 (between the GTX 1650 at 17 and GTX 1660 Super at 25),
 * GTX 1070 ~25, RTX 2060 Super ~34 (RTX 3060 class), RTX 2080 ~41, Ryzen 5
 * 1600 ~28, Core i7-8700K ~40 (Ryzen 5 3600 is 42). They are approximate
 * bars, not benchmarks.
 *
 * 'profile' (D3, Oct 2026): the game type the FPS Calculator applies when
 * the game is picked ('esports' or 'cpu-heavy', see
 * cs_bottleneck_game_profiles() in inc/bottleneck.php). Games without it
 * use the typical AAA profile. It is a rough class, not per-game data.
 *
 * 'upscaling' / 'framegen' (E4, Oct 2026, FUN-06): whether the game has
 * its own upscaling (DLSS, FSR or XeSS, any version) and frame generation
 * (DLSS, FSR or XeSS) option. Set only where the state is clear from the
 * game's settings and patch notes / press coverage, checked 20 Oct 2026; a
 * missing key means "not confirmed" and the FPS Calculator then applies
 * the visitor's choice with its usual "needs game support" note. Only
 * `false` changes a result: the FPS Calculator ignores that setting for the
 * game and says so. Mods don't count. Notes: Counter-Strike 2 has FSR 1
 * only; PUBG gained DLSS/FSR upscaling (2026) and Helldivers 2 DLSS/FSR/
 * XeSS upscaling (May 2026), frame generation unconfirmed for both;
 * Baldur's Gate 3 and Red Dead Redemption 2 have upscaling but no frame
 * generation; Minecraft: Java Edition, Valorant, League of Legends and
 * Elden Ring have neither. Re-check when a game adds either.
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cs_get_games_data() {
	static $games = null;
	if ( null !== $games ) {
		return $games;
	}

	$games = array(
		// Corrected during Sep 2026 audit — the previous entry carried the
		// ORIGINAL 2015 "Grand Theft Auto V" spec, not the 2025 "Enhanced"
		// PC edition's own requirements. Verified directly against Rockstar
		// Support's own Enhanced-edition requirements page
		// ( https://support.rockstargames.com/articles/lMQXeP2Z1mN3g9oZiBZFR/grand-theft-auto-v-pc-system-requirements ,
		// fetched Sep 2026 ). Note: an SSD is a hard minimum requirement per
		// Rockstar, not merely recommended.
		'gta-5-enhanced' => array(
			'name' => 'Grand Theft Auto V: Enhanced', 'developer' => 'Rockstar Games', 'publisher' => 'Rockstar Games', 'release_year' => 2025,
			'genre' => 'Open-World Action',
			'min'  => array( 'cpu' => 'Intel Core i7-4770 / AMD FX-9590', 'gpu' => 'NVIDIA GTX 1630 4GB / AMD RX 6400 4GB', 'ram' => 8, 'storage_gb' => 105, 'os' => 'Windows 10 64-bit' ),
			'rec'  => array( 'cpu' => 'Intel Core i5-9600K / AMD Ryzen 5 3600', 'gpu' => 'NVIDIA RTX 3060 8GB / AMD RX 6600XT 8GB', 'ram' => 16, 'storage_gb' => 105, 'os' => 'Windows 11 64-bit' ),
			'req'  => array( 'min' => array( 'cpu' => 22, 'gpu' => 7 ), 'rec' => array( 'cpu' => 40, 'gpu' => 31 ) ),
			'upscaling' => true, 'framegen' => true,
			'source_url' => 'https://support.rockstargames.com/articles/lMQXeP2Z1mN3g9oZiBZFR/grand-theft-auto-v-pc-system-requirements',
		),
		// Corrected during Sep 2026 audit: only the recommended-tier OS field
		// was wrong. CD Projekt Red's own Steam listing states the recommended
		// OS is 64-bit Windows 10, same as minimum — Windows 11 is not stated
		// for either tier. ( https://store.steampowered.com/app/1091500/Cyberpunk_2077/ , Sep 2026 )
		'cyberpunk-2077' => array(
			'name' => 'Cyberpunk 2077', 'developer' => 'CD Projekt Red', 'publisher' => 'CD Projekt Red', 'release_year' => 2020,
			'genre' => 'Open-World RPG',
			'min'  => array( 'cpu' => 'Intel Core i7-6700 / AMD Ryzen 5 1600', 'gpu' => 'NVIDIA GTX 1060 6GB / AMD RX 580 8GB', 'ram' => 12, 'storage_gb' => 70, 'os' => 'Windows 10 64-bit' ),
			'rec'  => array( 'cpu' => 'Intel Core i7-12700 / AMD Ryzen 7 7800X3D', 'gpu' => 'NVIDIA RTX 2060 SUPER / AMD RX 5700 XT', 'ram' => 16, 'storage_gb' => 70, 'os' => 'Windows 10 64-bit' ),
			'req'  => array( 'min' => array( 'cpu' => 28, 'gpu' => 20 ), 'rec' => array( 'cpu' => 64, 'gpu' => 34 ) ),
			'upscaling' => true, 'framegen' => true,
			'source_url' => 'https://store.steampowered.com/app/1091500/Cyberpunk_2077/',
		),
		// Corrected during Sep 2026 audit against Riot's own published specs
		// ( https://support.riotgames.com/en-us/valorant/support-tools/minimum-recommended-pc-specs ,
		// checked Sep 2026 ). Riot actually publishes THREE tiers (Minimum/30fps,
		// Recommended/60fps, High-end/144fps); this dataset only has two fields,
		// so the previous entry had wrongly copied the High-end tier's GPU
		// (GTX 1050 Ti) into the "rec" (Recommended/60fps) field — Riot's real
		// Recommended-tier GPU is the much lower NVIDIA GT 730 / AMD Radeon R7
		// 240. The minimum CPU was also wrong (site said Core 2 Duo E8400;
		// Riot's site lists Intel i3-540 / AMD Athlon 200G). RAM, storage and OS
		// are left as-is — Riot's spec page doesn't itemize those per tier, so
		// they aren't independently confirmed either way.
		'valorant' => array(
			'name' => 'Valorant', 'developer' => 'Riot Games', 'publisher' => 'Riot Games', 'release_year' => 2020,
			'genre' => 'Tactical Shooter', 'profile' => 'esports',
			'min'  => array( 'cpu' => 'Intel Core i3-540 / AMD Athlon 200G', 'gpu' => 'Intel HD 4000 / AMD Radeon R5 220', 'ram' => 4, 'storage_gb' => 25, 'os' => 'Windows 10/11 64-bit' ),
			'rec'  => array( 'cpu' => 'Intel Core i3-4150 / AMD Ryzen 3 1200', 'gpu' => 'NVIDIA GeForce GT 730 / AMD Radeon R7 240', 'ram' => 8, 'storage_gb' => 25, 'os' => 'Windows 10/11 64-bit' ),
			'req'  => array( 'min' => array( 'cpu' => 10, 'gpu' => 1 ), 'rec' => array( 'cpu' => 20, 'gpu' => 2 ) ),
			'upscaling' => false, 'framegen' => false,
			'source_url' => 'https://support.riotgames.com/en-us/valorant/support-tools/minimum-recommended-pc-specs',
		),
		// Sep 2026 audit note: Valve's own Steam listing for CS2
		// ( https://store.steampowered.com/app/730/CounterStrike_2/ ) only
		// publishes a MINIMUM spec — there is no official "Recommended" tier
		// at all. The 'min' row below matches Valve's listing exactly. The
		// 'rec' row has no official Valve source; left unchanged rather than
		// inventing a number, but flagged here since this schema requires a
		// 'rec' value and none officially exists.
		'counter-strike-2' => array(
			'name' => 'Counter-Strike 2', 'developer' => 'Valve', 'publisher' => 'Valve', 'release_year' => 2023,
			'genre' => 'Tactical Shooter', 'profile' => 'esports',
			'min'  => array( 'cpu' => 'Intel Core i5-750 / AMD equivalent', 'gpu' => 'NVIDIA GTX 660 / AMD equivalent, 1GB VRAM', 'ram' => 8, 'storage_gb' => 85, 'os' => 'Windows 10 64-bit' ),
			'rec'  => array( 'cpu' => 'Intel Core i5-9600K / AMD Ryzen 5 3600', 'gpu' => 'NVIDIA GTX 1650 / AMD RX 570', 'ram' => 16, 'storage_gb' => 85, 'os' => 'Windows 11 64-bit' ),
			'req'  => array( 'min' => array( 'cpu' => 12, 'gpu' => 6 ), 'rec' => array( 'cpu' => 40, 'gpu' => 15 ) ),
			'upscaling' => true, 'framegen' => false,
			// Oct 2026: shown as our suggestion, not Valve's (Steam API checked
			// 13 Oct 2026 still lists a minimum tier only). See cs_game_rec_official().
			'rec_official' => false,
			'source_url' => 'https://store.steampowered.com/app/730/CounterStrike_2/',
		),
		// Corrected during Sep 2026 audit: minimum OS narrowed to match Epic's
		// own support page, which lists "Windows 10, Version 22H2 (64-bit) or
		// Windows 10 Enterprise 21H2" for the minimum tier — Windows 11 is not
		// stated as a minimum-tier option (recommended tier does list 10/11).
		// Storage figures are UNABLE TO VERIFY — Epic's page gives no GB figure
		// for either tier, so the existing 40GB values are left as-is.
		// ( https://www.epicgames.com/help/en-US/fortnite-c75/pc-support-c80/what-are-the-system-requirements-for-fortnite-on-pc-and-mac-a3332 , Sep 2026 )
		'fortnite' => array(
			'name' => 'Fortnite', 'developer' => 'Epic Games', 'publisher' => 'Epic Games', 'release_year' => 2017,
			'genre' => 'Battle Royale', 'profile' => 'esports',
			'min'  => array( 'cpu' => 'Intel Core i3-3225', 'gpu' => 'Intel HD 4000', 'ram' => 8, 'storage_gb' => 40, 'os' => 'Windows 10 64-bit' ),
			'rec'  => array( 'cpu' => 'Intel Core i5-7300U / AMD Ryzen 3 3300U', 'gpu' => 'NVIDIA GTX 960 / AMD R9 280', 'ram' => 16, 'storage_gb' => 40, 'os' => 'Windows 10/11 64-bit' ),
			'req'  => array( 'min' => array( 'cpu' => 15, 'gpu' => 1 ), 'rec' => array( 'cpu' => 20, 'gpu' => 11 ) ),
			'source_url' => 'https://www.epicgames.com/help/en-US/fortnite-c75/pc-support-c80/what-are-the-system-requirements-for-fortnite-on-pc-and-mac-a3332',
		),
		// Corrected during Sep 2026 audit: Mojang raised Java Edition's official
		// PC requirements on 21 Jul 2026 (first change in 17 years, tied to the
		// OpenGL-to-Vulkan rendering switch / Vibrant Visuals update). The old
		// row below reflected the pre-2026 baseline. minecraft.net's own spec
		// page is JS-rendered and could not be scraped directly, so this is
		// sourced to independent corroborating reporting that quotes Mojang's
		// announcement consistently (Softonic, GamingHQ), cross-checked Sep 2026:
		// https://en.softonic.com/articles/minecraft-java-edition-updates-pc-requirements-after-17-years-vulkan-and-more-ram
		// https://gaminghq.eu/2026/07/28/minecraft-java-edition-pc-requirements/
		// Minimum RAM is 8GB with a discrete GPU or 12GB with integrated graphics;
		// we record 8GB (the discrete-GPU baseline) since the schema has one RAM
		// field. Storage figures are UNABLE TO VERIFY (no source found gives a GB
		// number for the new spec), so the existing values are left unchanged.
		// Recommend a direct check of minecraft.net by someone with a browser
		// given how consequential this change is.
		'minecraft' => array(
			'name' => 'Minecraft: Java Edition', 'developer' => 'Mojang Studios', 'publisher' => 'Mojang Studios', 'release_year' => 2011,
			'genre' => 'Sandbox / Survival', 'profile' => 'cpu-heavy',
			'min'  => array( 'cpu' => 'Intel Core i3-10100 / AMD Ryzen 3 3100', 'gpu' => 'Vulkan 1.3-compatible, 2GB VRAM (e.g. NVIDIA GTX 950 / AMD RX 460)', 'ram' => 8, 'storage_gb' => 4, 'os' => 'Windows 10 64-bit' ),
			'rec'  => array( 'cpu' => 'Intel Core i5-12400 / AMD Ryzen 5 5600', 'gpu' => 'NVIDIA RTX 2060 / AMD RX 5600 XT 6GB', 'ram' => 16, 'storage_gb' => 4, 'os' => 'Windows 11 64-bit' ),
			'req'  => array( 'min' => array( 'cpu' => 34, 'gpu' => 9 ), 'rec' => array( 'cpu' => 52, 'gpu' => 30 ) ),
			'upscaling' => false, 'framegen' => false,
			'source_url' => 'https://www.minecraft.net/en-us/article/minecraft-java-edition-system-requirements',
		),
		// Corrected during Sep 2026 audit against Respawn/EA's own Steam listing
		// ( https://store.steampowered.com/app/1172470/Apex_Legends/ , fetched
		// Sep 2026 ). Minimum GPU corrected (official lists AMD Radeon HD 7790
		// 2GB / NVIDIA GTX 950, not GT 640 / HD 7730). Recommended CPU corrected
		// to match the official listing exactly, which states only "Ryzen 5 CPU
		// or Equivalent" — no specific Intel pairing is published, so the
		// previous i5-3570K figure (which is not on Respawn's page) was dropped
		// rather than replaced with another guess.
		'apex-legends' => array(
			'name' => 'Apex Legends', 'developer' => 'Respawn Entertainment', 'publisher' => 'Electronic Arts', 'release_year' => 2019,
			'genre' => 'Battle Royale', 'profile' => 'esports',
			'min'  => array( 'cpu' => 'Intel Core i3-6300 / AMD FX-4350', 'gpu' => 'NVIDIA GTX 950 / AMD Radeon HD 7790 2GB', 'ram' => 6, 'storage_gb' => 75, 'os' => 'Windows 10 64-bit' ),
			'rec'  => array( 'cpu' => 'AMD Ryzen 5 or equivalent', 'gpu' => 'NVIDIA GTX 970 / AMD R9 290', 'ram' => 8, 'storage_gb' => 75, 'os' => 'Windows 10 64-bit' ),
			'req'  => array( 'min' => array( 'cpu' => 14, 'gpu' => 6 ), 'rec' => array( 'cpu' => 28, 'gpu' => 17 ) ),
			'source_url' => 'https://store.steampowered.com/app/1172470/Apex_Legends/',
		),
		// Corrected during Sep 2026 audit against Activision Support's official
		// Warzone PC requirements page
		// ( https://support.activision.com/warzone-2/articles/pc-system-requirements-for-warzone-2 ,
		// fetched Sep 2026 ). Nearly every field had drifted from current specs.
		// Note for site owner: Warzone is no longer a fully standalone product —
		// it now installs through the unified "Call of Duty" (Call of Duty HQ)
		// app, so the 116GB storage figure is Activision's own stated Warzone
		// figure, but a full Call of Duty HQ install may require more.
		'call-of-duty-warzone' => array(
			'name' => 'Call of Duty: Warzone', 'developer' => 'Infinity Ward / Raven Software', 'publisher' => 'Activision', 'release_year' => 2020,
			'genre' => 'Battle Royale',
			'min'  => array( 'cpu' => 'Intel Core i5-6600 / AMD Ryzen 5 1400', 'gpu' => 'NVIDIA GTX 970/1060 / AMD RX 470 / Intel Arc A580', 'ram' => 8, 'storage_gb' => 116, 'os' => 'Windows 10 64-bit' ),
			'rec'  => array( 'cpu' => 'Intel Core i7-6700K / AMD Ryzen 5 1600X', 'gpu' => 'NVIDIA RTX 3060 / AMD RX 6600XT / Intel Arc B580', 'ram' => 12, 'storage_gb' => 116, 'os' => 'Windows 11 64-bit' ),
			'req'  => array( 'min' => array( 'cpu' => 24, 'gpu' => 15 ), 'rec' => array( 'cpu' => 29, 'gpu' => 34 ) ),
			'upscaling' => true, 'framegen' => true,
			'source_url' => 'https://support.activision.com/warzone-2/articles/pc-system-requirements-for-warzone-2',
		),
		// Corrected during Sep 2026 audit against Steam's official listing
		// ( https://store.steampowered.com/app/578080/PUBG_BATTLEGROUNDS/ ,
		// fetched Sep 2026 ): storage figures and OS were overstated, and the
		// recommended-tier GPU VRAM was wrong (GTX 1060 is listed at 3GB by
		// Krafton for this tier, not 6GB).
		'pubg-battlegrounds' => array(
			'name' => 'PUBG: Battlegrounds', 'developer' => 'Krafton', 'publisher' => 'Krafton', 'release_year' => 2017,
			'genre' => 'Battle Royale',
			'min'  => array( 'cpu' => 'Intel Core i5-4430 / AMD FX-6300', 'gpu' => 'NVIDIA GTX 960 2GB / AMD R7 370 2GB', 'ram' => 8, 'storage_gb' => 40, 'os' => 'Windows 10 64-bit' ),
			'rec'  => array( 'cpu' => 'Intel Core i5-6600K / AMD Ryzen 5 1600', 'gpu' => 'NVIDIA GTX 1060 3GB / AMD RX 580 4GB', 'ram' => 16, 'storage_gb' => 50, 'os' => 'Windows 10 64-bit' ),
			'req'  => array( 'min' => array( 'cpu' => 15, 'gpu' => 11 ), 'rec' => array( 'cpu' => 28, 'gpu' => 18 ) ),
			'upscaling' => true,
			'source_url' => 'https://store.steampowered.com/app/578080/PUBG_BATTLEGROUNDS/',
		),
		// Corrected during Sep 2026 audit against Riot's own official support
		// page ( https://support.riotgames.com/en-us/league-of-legends/performance/minimum-and-recommended-system-requirements-league-of-legends ,
		// fetched Sep 2026, last updated by Riot 22 Sep 2026 ): minimum GPU
		// reference updated (Riot now lists a GeForce 400-series card, not the
		// much older 9600GT) and recommended CPU corrected (Riot lists
		// i5-8250 / Ryzen 3 1200, not i5-3300, which isn't a real SKU).
		// Recommended OS is now Windows 11 only (TPM 2.0 required per Riot).
		'league-of-legends' => array(
			'name' => 'League of Legends', 'developer' => 'Riot Games', 'publisher' => 'Riot Games', 'release_year' => 2009,
			'genre' => 'MOBA', 'profile' => 'esports',
			'min'  => array( 'cpu' => 'Intel Core i3-530', 'gpu' => 'NVIDIA GeForce 400 series / AMD Radeon HD 6570 / Intel HD 4600', 'ram' => 2, 'storage_gb' => 16, 'os' => 'Windows 10 64-bit' ),
			'rec'  => array( 'cpu' => 'Intel Core i5-8250 / AMD Ryzen 3 1200', 'gpu' => 'NVIDIA GTX 560 / AMD Radeon HD 6950', 'ram' => 4, 'storage_gb' => 16, 'os' => 'Windows 11 64-bit' ),
			'req'  => array( 'min' => array( 'cpu' => 10, 'gpu' => 2 ), 'rec' => array( 'cpu' => 22, 'gpu' => 4 ) ),
			'upscaling' => false, 'framegen' => false,
			'source_url' => 'https://support.riotgames.com/en-us/league-of-legends/performance/minimum-and-recommended-system-requirements-league-of-legends',
		),
		'elden-ring' => array(
			'name' => 'Elden Ring', 'developer' => 'FromSoftware', 'publisher' => 'Bandai Namco Entertainment', 'release_year' => 2022,
			'genre' => 'Action RPG',
			'min'  => array( 'cpu' => 'Intel Core i5-8400 / AMD Ryzen 3 3300X', 'gpu' => 'NVIDIA GTX 1060 3GB / AMD RX 580 4GB', 'ram' => 12, 'storage_gb' => 60, 'os' => 'Windows 10 64-bit' ),
			'rec'  => array( 'cpu' => 'Intel Core i7-8700K / AMD Ryzen 5 3600X', 'gpu' => 'NVIDIA GTX 1070 8GB / AMD RX Vega 56 8GB', 'ram' => 16, 'storage_gb' => 60, 'os' => 'Windows 10/11 64-bit' ),
			'req'  => array( 'min' => array( 'cpu' => 33, 'gpu' => 18 ), 'rec' => array( 'cpu' => 40, 'gpu' => 25 ) ),
			'upscaling' => false, 'framegen' => false,
			'source_url' => 'https://store.steampowered.com/app/1245620/ELDEN_RING/',
		),
		// Corrected during Sep 2026 audit (citation pass) against Steam's official
		// listing ( https://store.steampowered.com/app/1086940/Baldurs_Gate_3/ ,
		// fetched Sep 2026): minimum GPU said "AMD RX 470", Larian's actual listed
		// card is "AMD RX 480". Recommended OS narrowed to Windows 10 64-bit only —
		// Larian's page does not list Windows 11 for either tier.
		'baldurs-gate-3' => array(
			'name' => "Baldur's Gate 3", 'developer' => 'Larian Studios', 'publisher' => 'Larian Studios', 'release_year' => 2023,
			'genre' => 'Role-Playing', 'profile' => 'cpu-heavy',
			'min'  => array( 'cpu' => 'Intel Core i5-4690 / AMD FX-8350', 'gpu' => 'NVIDIA GTX 970 4GB / AMD RX 480 4GB', 'ram' => 8, 'storage_gb' => 150, 'os' => 'Windows 10 64-bit' ),
			'rec'  => array( 'cpu' => 'Intel Core i7-8700K / AMD Ryzen 5 3600', 'gpu' => 'NVIDIA RTX 2060 Super / AMD RX 5700 XT', 'ram' => 16, 'storage_gb' => 150, 'os' => 'Windows 10 64-bit' ),
			'req'  => array( 'min' => array( 'cpu' => 18, 'gpu' => 17 ), 'rec' => array( 'cpu' => 40, 'gpu' => 34 ) ),
			'upscaling' => true, 'framegen' => false,
			'source_url' => 'https://store.steampowered.com/app/1086940/Baldurs_Gate_3/',
		),
		'red-dead-redemption-2' => array(
			'name' => 'Red Dead Redemption 2', 'developer' => 'Rockstar Games', 'publisher' => 'Rockstar Games', 'release_year' => 2019,
			'genre' => 'Open-World Action',
			'min'  => array( 'cpu' => 'Intel Core i5-2500K / AMD FX-6300', 'gpu' => 'NVIDIA GTX 770 2GB / AMD R9 280 3GB', 'ram' => 8, 'storage_gb' => 150, 'os' => 'Windows 10 64-bit' ),
			'rec'  => array( 'cpu' => 'Intel Core i7-4770K / AMD Ryzen 5 1500X', 'gpu' => 'NVIDIA GTX 1060 6GB / AMD RX 480 4GB', 'ram' => 12, 'storage_gb' => 150, 'os' => 'Windows 10 64-bit' ),
			'req'  => array( 'min' => array( 'cpu' => 15, 'gpu' => 9 ), 'rec' => array( 'cpu' => 25, 'gpu' => 17 ) ),
			'upscaling' => true, 'framegen' => false,
			'source_url' => 'https://store.steampowered.com/app/1174180/Red_Dead_Redemption_2/',
		),
		// Corrected during Sep 2026 audit against Blizzard's own Steam listing
		// ( https://store.steampowered.com/app/2357570/Overwatch/ , fetched
		// Sep 2026, mirrors Blizzard's Battle.net spec for the same product ):
		// recommended GPU and OS both corrected to match Blizzard's exact
		// wording (no "6GB" qualifier on the GTX 1060, and Windows 10 only —
		// not "10/11" — for the recommended tier).
		'overwatch-2' => array(
			'name' => 'Overwatch 2', 'developer' => 'Blizzard Entertainment', 'publisher' => 'Blizzard Entertainment', 'release_year' => 2022,
			'genre' => 'Hero Shooter',
			'min'  => array( 'cpu' => 'Intel Core i3 or AMD Phenom X3 8650', 'gpu' => 'NVIDIA GTX 600 series / AMD Radeon HD 7000 series', 'ram' => 6, 'storage_gb' => 50, 'os' => 'Windows 10 64-bit' ),
			'rec'  => array( 'cpu' => 'Intel Core i7 / AMD Ryzen 5', 'gpu' => 'NVIDIA GTX 1060/1650 / AMD R9 380/RX 6400', 'ram' => 8, 'storage_gb' => 50, 'os' => 'Windows 10 64-bit' ),
			'req'  => array( 'min' => array( 'cpu' => 10, 'gpu' => 5 ), 'rec' => array( 'cpu' => 28, 'gpu' => 9 ) ),
			'source_url' => 'https://store.steampowered.com/app/2357570/Overwatch/',
		),
		// Corrected during Sep 2026 audit against Steam's official listing
		// ( https://store.steampowered.com/app/1716740/Starfield/ , fetched
		// Sep 2026 ): minimum-tier OS narrowed to Windows 10 — Bethesda's listing
		// specifies "Windows 10 version 21H1" for minimum and does not offer
		// Windows 11 at that tier (recommended tier does list 10/11, unchanged).
		'starfield' => array(
			'name' => 'Starfield', 'developer' => 'Bethesda Game Studios', 'publisher' => 'Bethesda Softworks', 'release_year' => 2023,
			'genre' => 'Open-World RPG',
			'min'  => array( 'cpu' => 'Intel Core i7-6800K / AMD Ryzen 5 2600X', 'gpu' => 'NVIDIA GTX 1070 Ti / AMD RX 5700', 'ram' => 16, 'storage_gb' => 125, 'os' => 'Windows 10 64-bit' ),
			'rec'  => array( 'cpu' => 'Intel Core i5-10600K / AMD Ryzen 5 3600X', 'gpu' => 'NVIDIA RTX 2080 / AMD RX 6800 XT', 'ram' => 16, 'storage_gb' => 125, 'os' => 'Windows 10/11 64-bit' ),
			'req'  => array( 'min' => array( 'cpu' => 30, 'gpu' => 28 ), 'rec' => array( 'cpu' => 43, 'gpu' => 41 ) ),
			'upscaling' => true, 'framegen' => true,
			'source_url' => 'https://store.steampowered.com/app/1716740/Starfield/',
		),
		// Corrected during Sep 2026 audit against Arrowhead's own Steam listing
		// ( https://store.steampowered.com/app/553850/HELLDIVERS_2/ , fetched twice
		// Sep 2026 with identical results ). Minimum GPU, minimum AMD CPU pairing,
		// and both storage figures were wrong. Note the recommended-tier storage
		// (40GB) is genuinely lower than the minimum-tier figure (135GB) per
		// Arrowhead's current listing — unusual, but that is what the official
		// page states, likely reflecting post-launch optimization patches.
		'helldivers-2' => array(
			'name' => 'Helldivers 2', 'developer' => 'Arrowhead Game Studios', 'publisher' => 'PlayStation Publishing LLC', 'release_year' => 2024,
			'genre' => 'Co-op Shooter',
			'min'  => array( 'cpu' => 'Intel Core i7-4790K / AMD Ryzen 5 1500X', 'gpu' => 'NVIDIA GTX 1050 Ti / AMD RX 470', 'ram' => 8, 'storage_gb' => 135, 'os' => 'Windows 10 64-bit' ),
			'rec'  => array( 'cpu' => 'Intel Core i7-9700K / AMD Ryzen 7 3700X', 'gpu' => 'NVIDIA RTX 2060 / AMD RX 6600XT', 'ram' => 16, 'storage_gb' => 40, 'os' => 'Windows 10 64-bit' ),
			'req'  => array( 'min' => array( 'cpu' => 25, 'gpu' => 9 ), 'rec' => array( 'cpu' => 43, 'gpu' => 30 ) ),
			'upscaling' => true,
			'source_url' => 'https://store.steampowered.com/app/553850/HELLDIVERS_2/',
		),
	);

	return $games;
}
