<?php
/**
 * A small number of extra, single-question FAQ additions for specific
 * existing pages, targeting long-tail search phrasing confirmed (via live
 * search checks in September 2026) to be dominated by small, beatable
 * niche sites rather than major hardware-media brands — e.g. "[cpu]
 * bottleneck [gpu]", "how many watts psu do I need for [gpu]", "1440p vs
 * 4k for gaming", "is my cpu bottlenecking my gpu". Each existing page
 * already carries its own 4-6 item [cs_faq] block authored in the post
 * content; these are additive, non-duplicate questions this research
 * showed were still missing, added at the theme/template level (like
 * inc/shortcodes.php's cs_maybe_print_games_crosslink()) rather than by
 * editing the live post content directly.
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * One extra Q&A definition per target keyword gap, each listing every
 * page slug it should appear on. Kept as one item per page (not a whole
 * new FAQ block) so it reads as a natural addition next to the page's
 * existing FAQ rather than a second, redundant section.
 */
function cs_extra_faq_definitions() {
	return array_merge( cs_extra_faq_base(), function_exists( 'cs_k1_extra_faqs' ) ? cs_k1_extra_faqs() : array() );
}

function cs_extra_faq_base() {
	return array(
		array(
			'slugs' => array( 'gpu-vram-calculator', 'vram' ),
			'q'     => __( 'How much VRAM do I need for 1440p gaming specifically?', 'computingskill' ),
			'a'     => __( "For 1440p at high settings in most current titles, 12GB is the more realistic comfortable minimum — 8GB can still work, but leaves little headroom for texture packs, ray tracing or heavy modding, and you may need to drop a texture setting even when the frame rate itself still looks fine. 16GB is only worth specifically chasing at 1440p if you want ray tracing or 4K-quality texture packs turned on; for standard high/ultra settings without ray tracing, 12GB clears comfortably.", 'computingskill' ),
		),
		array(
			'slugs' => array( 'ram-calculator', 'ram' ),
			'q'     => __( "What's the real difference between 16GB and 32GB of RAM for gaming?", 'computingskill' ),
			'a'     => __( 'For gaming alone — one game running, nothing else demanding open — 16GB is still enough for the large majority of current titles, and moving to 32GB usually will not raise in-game FPS by itself. Where 32GB actually helps is everything running alongside the game: streaming software, Discord, a browser with many tabs, or background recording all compete for that same 16GB. If you mostly just play with little else open, 16GB remains the better value; if you regularly game while streaming or multitasking heavily, 32GB removes a real, everyday slowdown rather than a theoretical one.', 'computingskill' ),
		),
		array(
			'slugs' => array( 'psu-calculator', 'how-to-choose-a-psu', 'psu' ),
			'q'     => __( 'How many watts of PSU do I actually need for a card like the RTX 4070 or RX 7800 XT?', 'computingskill' ),
			'a'     => __( "As a rule of thumb: an RTX 4070 (200W TDP) is comfortable on a 650W PSU, an RTX 4070 Super or RX 7800 XT (220-265W) is comfortable on 650-750W, and anything at the RTX 4080/4090 or RX 7900 XT/XTX tier (320W and up) is safer on 750-850W or more. These figures already build in headroom for the rest of a typical build and for the GPU's real transient power spikes, which briefly exceed its rated TDP. Enter your exact GPU and CPU in the calculator above for a number specific to your build rather than a generic rule of thumb.", 'computingskill' ),
		),
		array(
			'slugs' => array( 'storage-calculator', 'drive-space-calculator' ),
			'q'     => __( 'How much SSD storage do I actually need for a gaming PC?', 'computingskill' ),
			'a'     => __( "1TB is the realistic minimum for a primary gaming drive today — a handful of modern AAA titles alone can use 100-150GB each, so a 500GB drive fills up after installing just three or four large games plus Windows and updates. If you regularly play multiple large, actively-updated titles (live-service shooters and battle royales are usually the biggest offenders) or would rather not keep uninstalling games to make room, 2TB is the more comfortable target.", 'computingskill' ),
		),
		array(
			'slugs' => array( '1080p-vs-1440p-vs-4k' ),
			'q'     => __( '1440p vs 4K for gaming — which one should I actually pick?', 'computingskill' ),
			'a'     => __( "1440p is the better pick for most gamers: it's a clearly sharper step up from 1080p, but needs roughly half the GPU power that native 4K does to hit the same frame rate, so a mid-range GPU can sustain a high refresh rate at 1440p while it would have to make heavy compromises at 4K. 4K is worth choosing specifically if you already have (or plan to buy) a high-end GPU — RTX 4080-tier or above — and care more about visual fidelity than chasing very high frame rates; otherwise, the GPU budget 4K requires usually buys a bigger real improvement spent on 1440p at a higher refresh rate instead.", 'computingskill' ),
		),
		array(
			'slugs' => array( 'fps-calculator', 'how-to-improve-fps' ),
			'q'     => __( 'How do I check my FPS in a game without installing extra software?', 'computingskill' ),
			'a'     => __( 'Windows\' built-in Game Bar shows a live FPS counter with no extra download: press Win+G while in a game, open the Performance widget and pin it, and it overlays your current FPS during play (enable it first in Settings → Gaming → Xbox Game Bar if it\'s off). Steam has its own equivalent for anything launched through it: Steam Settings → In-Game → "In-game FPS counter". Either gives you a real, live number to compare against the estimate above.', 'computingskill' ),
		),
		array(
			'slugs' => array( 'pc-build-cost-calculator' ),
			'q'     => __( 'How much does it actually cost to build a gaming PC in 2026?', 'computingskill' ),
			'a'     => __( 'A capable 1080p/1440p build (a current-gen mid-range CPU and an RTX 4060-tier GPU, 16GB of RAM, a 1TB SSD, and a decent case and PSU) lands in a moderate budget range; a build aimed at 1440p/4K with a higher-tier GPU costs meaningfully more, driven mostly by the GPU alone. Component prices move throughout the year, so treat any fixed total as a snapshot rather than a fact — use the calculator above with your own target parts list for a figure that reflects current prices.', 'computingskill' ),
		),
		array(
			'slugs' => array( 'refresh-rate-calculator' ),
			'q'     => __( 'Is 240Hz actually worth it over 144Hz, or is it overkill?', 'computingskill' ),
			'a'     => __( "For most players, 144Hz already delivers the large, easily-felt jump over 60Hz; 240Hz is a smaller, more marginal step on top of that, and it matters most in fast, competitive titles where players are specifically chasing lower input lag and smoother tracking — and only if the GPU can actually sustain 200+ FPS in that game, since a high-refresh monitor paired with a GPU that can't hit those frame rates gains nothing. For story-driven or GPU-heavy titles, the difference between 144Hz and 240Hz is far less noticeable, and that GPU headroom is usually better spent on resolution or settings instead.", 'computingskill' ),
		),
		array(
			'slugs' => array( 'pc-bottleneck-calculator', 'cpu-bottleneck-explained', 'gpu-bottleneck-explained' ),
			'q'     => __( 'How can I tell if my CPU is bottlenecking my GPU without using a calculator?', 'computingskill' ),
			'a'     => __( "Open Task Manager's Performance tab (or a live overlay like MSI Afterburner/HWiNFO) while playing and watch both usage numbers: if GPU usage sits well below 95-99% while the CPU sits near 100%, the CPU is the more likely limiting part; if the GPU sits near 99-100% while the CPU has spare headroom, the GPU is the limiting part. Lowering only the resolution is a useful second check — if FPS barely changes when resolution drops, that points to a CPU bottleneck, since a true GPU bottleneck usually improves noticeably at a lower resolution.", 'computingskill' ),
		),
	);
}

/**
 * Prints the extra FAQ items for the current page whose slugs match a
 * definition above, and queues it for FAQPage JSON-LD the same way
 * [cs_faq] does (see inc/shortcodes.php / inc/schema.php). Call from a
 * page template right after the_content(), same pattern as
 * cs_maybe_print_games_crosslink().
 */
function cs_maybe_print_extra_faq() {
	$slug = get_post_field( 'post_name' );
	if ( ! $slug ) {
		return;
	}

	foreach ( cs_extra_faq_definitions() as $def ) {
		if ( ! in_array( $slug, $def['slugs'], true ) ) {
			continue;
		}

		$q = $def['q'];
		$a = $def['a'];
		?>
		<div class="cs-faq cs-faq-extra">
			<div class="cs-faq-item">
				<h3 class="cs-faq-question"><?php echo esc_html( $q ); ?></h3>
				<div class="cs-faq-answer"><p><?php echo esc_html( $a ); ?></p></div>
			</div>
		</div>
		<?php
		global $cs_faq_schema_queue;
		if ( ! is_array( $cs_faq_schema_queue ) ) {
			$cs_faq_schema_queue = array();
		}
		$cs_faq_schema_queue[] = array(
			'question' => wp_strip_all_tags( $q ),
			'answer'   => wp_strip_all_tags( $a ),
		);

		// K1: a page can now carry more than one extra question.
	}
}
