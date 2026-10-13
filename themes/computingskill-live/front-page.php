<?php
/**
 * Homepage.
 *
 * @package ComputingSkill
 */

get_header();
?>
<main id="cs-main" class="cs-main cs-front-page">

	<section class="cs-hero">
		<div class="cs-container cs-hero-grid">
			<div class="cs-hero-copy">
				<p class="cs-eyebrow"><?php esc_html_e( 'PC hardware calculators', 'computingskill' ); ?></p>
				<h1><?php esc_html_e( 'Free PC Hardware Calculators & Computer Tools', 'computingskill' ); ?></h1>
				<p class="cs-hero-sub"><?php esc_html_e( 'Calculate, compare and optimize your PC before you buy, build or upgrade.', 'computingskill' ); ?></p>
				<ul class="cs-hero-facts">
					<li><?php esc_html_e( 'Free', 'computingskill' ); ?></li>
					<li><?php esc_html_e( 'No sign-up', 'computingskill' ); ?></li>
					<li><?php esc_html_e( 'Runs in your browser', 'computingskill' ); ?></li>
					<li><?php esc_html_e( 'Formula shown on every tool', 'computingskill' ); ?></li>
				</ul>
				<form class="cs-search-form cs-hero-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
					<label class="screen-reader-text" for="cs-hero-search-input"><?php esc_html_e( 'Search ComputingSkill', 'computingskill' ); ?></label>
					<input type="search" id="cs-hero-search-input" name="s" placeholder="<?php esc_attr_e( 'Search calculators, tools, guides…', 'computingskill' ); ?>" />
					<button type="submit"><?php esc_html_e( 'Search', 'computingskill' ); ?></button>
				</form>
			</div>
			<?php
			// Example panel: a real engine result for one pair whose limiting
			// part shifts with resolution. Skipped if either part is missing.
			$hero_cpus = cs_get_cpu_data();
			$hero_gpus = cs_get_gpu_data();
			if ( isset( $hero_cpus['ryzen-5-5600'], $hero_gpus['rtx-4070'] ) ) :
				$hero_cpu = $hero_cpus['ryzen-5-5600'];
				$hero_gpu = $hero_gpus['rtx-4070'];
				?>
				<div class="cs-telemetry">
					<p class="cs-telemetry-label"><?php esc_html_e( 'Bottleneck check · example', 'computingskill' ); ?></p>
					<div class="cs-telemetry-pair">
						<div class="cs-telemetry-part"><small><?php esc_html_e( 'CPU', 'computingskill' ); ?></small><strong><?php echo esc_html( $hero_cpu['name'] ); ?></strong></div>
						<span class="cs-telemetry-plus" aria-hidden="true">+</span>
						<div class="cs-telemetry-part"><small><?php esc_html_e( 'GPU', 'computingskill' ); ?></small><strong><?php echo esc_html( $hero_gpu['name'] ); ?></strong></div>
					</div>
					<?php echo cs_bn_load_bars( cs_calculate_bottleneck( $hero_cpu, $hero_gpu ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside. ?>
					<p class="cs-telemetry-note"><?php esc_html_e( 'Specs-based estimate. Try your own parts in the full calculator.', 'computingskill' ); ?></p>
					<a class="cs-btn" href="<?php echo esc_url( home_url( '/pc-bottleneck-calculator/' ) ); ?>"><?php esc_html_e( 'Open Bottleneck Calculator', 'computingskill' ); ?> →</a>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<div class="cs-container">

		<section class="cs-home-section cs-home-intro">
			<p>
				<?php
				printf(
					/* translators: %s: link to the Methodology page. */
					esc_html__( 'ComputingSkill is a free collection of PC hardware calculators and computer tools for anyone planning a new build, an upgrade or their first gaming PC. Choose your parts and the calculators estimate how well a CPU and GPU pairing is balanced at 1080p, 1440p and 4K, roughly what FPS to expect, how much power supply wattage the build needs, and how much RAM, VRAM and storage you are likely to use. Every tool runs in your browser with no account and no sign-up. Results are specs-based estimates, not lab benchmarks, and each calculator explains its formula and its limits so you can see how a number was reached. Hardware specs come from manufacturer pages, and our %s describes the full approach.', 'computingskill' ),
					'<a href="' . esc_url( home_url( '/methodology/' ) ) . '">' . esc_html__( 'methodology page', 'computingskill' ) . '</a>'
				);
				?>
			</p>
		</section>

		<section class="cs-home-section cs-featured">
			<h2><?php esc_html_e( 'Featured Calculators', 'computingskill' ); ?></h2>
			<?php
			cs_card_grid( array(
				array( 'title' => __( 'PC Bottleneck Calculator', 'computingskill' ), 'url' => home_url( '/pc-bottleneck-calculator/' ), 'desc' => __( 'See which part of your CPU/GPU pairing is holding the other back.', 'computingskill' ), 'icon' => 'cpu' ),
				array( 'title' => __( 'PSU Calculator', 'computingskill' ), 'url' => home_url( '/psu-calculator/' ), 'desc' => __( 'Work out the power supply wattage your build actually needs.', 'computingskill' ), 'icon' => 'psu' ),
				array( 'title' => __( 'FPS Calculator', 'computingskill' ), 'url' => home_url( '/fps-calculator/' ), 'desc' => __( 'Estimate gaming FPS from your CPU, GPU and resolution.', 'computingskill' ), 'icon' => 'fps' ),
				array( 'title' => __( 'PC Build Cost Calculator', 'computingskill' ), 'url' => home_url( '/pc-build-cost-calculator/' ), 'desc' => __( 'Add up a full build and see where your budget is going.', 'computingskill' ), 'icon' => 'cost' ),
			) );
			?>
		</section>

		<section class="cs-home-section">
			<h2><?php esc_html_e( 'PC Hardware Calculators', 'computingskill' ); ?></h2>
			<?php
			cs_card_grid( array(
				array( 'title' => __( 'RAM Calculator', 'computingskill' ), 'url' => home_url( '/ram-calculator/' ) ),
				array( 'title' => __( 'GPU VRAM Calculator', 'computingskill' ), 'url' => home_url( '/gpu-vram-calculator/' ) ),
				array( 'title' => __( 'PC Power Consumption Calculator', 'computingskill' ), 'url' => home_url( '/pc-power-consumption-calculator/' ) ),
				array( 'title' => __( 'PC Upgrade Calculator', 'computingskill' ), 'url' => home_url( '/pc-upgrade-calculator/' ) ),
				array( 'title' => __( 'PC Compatibility Checker', 'computingskill' ), 'url' => home_url( '/pc-compatibility-checker/' ) ),
				array( 'title' => __( 'CPU vs GPU Calculator', 'computingskill' ), 'url' => home_url( '/cpu-vs-gpu-calculator/' ) ),
			) );
			?>
			<p class="cs-section-more"><a href="<?php echo esc_url( home_url( '/calculators/' ) ); ?>"><?php esc_html_e( 'View all calculators →', 'computingskill' ); ?></a></p>
		</section>

		<section class="cs-home-section">
			<h2><?php esc_html_e( 'Computer Tools', 'computingskill' ); ?></h2>
			<?php
			cs_card_grid( array(
				array( 'title' => __( 'Download Time Calculator', 'computingskill' ), 'url' => home_url( '/download-time-calculator/' ) ),
				array( 'title' => __( 'Data Storage Converter', 'computingskill' ), 'url' => home_url( '/data-storage-converter/' ) ),
				array( 'title' => __( 'DPI Calculator', 'computingskill' ), 'url' => home_url( '/dpi-calculator/' ) ),
				array( 'title' => __( 'Aspect Ratio Calculator', 'computingskill' ), 'url' => home_url( '/aspect-ratio-calculator/' ) ),
			) );
			?>
			<p class="cs-section-more"><a href="<?php echo esc_url( home_url( '/computer-tools/' ) ); ?>"><?php esc_html_e( 'View all tools →', 'computingskill' ); ?></a></p>
		</section>

		<section class="cs-home-section">
			<h2><?php esc_html_e( 'Hardware Resources', 'computingskill' ); ?></h2>
			<?php
			cs_card_grid( array(
				array( 'title' => __( 'CPU', 'computingskill' ), 'url' => home_url( '/cpu/' ) ),
				array( 'title' => __( 'GPU', 'computingskill' ), 'url' => home_url( '/gpu/' ) ),
				array( 'title' => __( 'RAM', 'computingskill' ), 'url' => home_url( '/ram/' ) ),
				array( 'title' => __( 'SSD', 'computingskill' ), 'url' => home_url( '/ssd/' ) ),
				array( 'title' => __( 'PSU', 'computingskill' ), 'url' => home_url( '/psu/' ) ),
				array( 'title' => __( 'Motherboard', 'computingskill' ), 'url' => home_url( '/motherboard/' ) ),
			) );
			?>
			<p class="cs-section-more"><a href="<?php echo esc_url( home_url( '/hardware/' ) ); ?>"><?php esc_html_e( 'View all hardware pages →', 'computingskill' ); ?></a></p>
		</section>

		<section class="cs-home-section">
			<h2><?php esc_html_e( 'Popular Guides', 'computingskill' ); ?></h2>
			<?php
			cs_card_grid( array(
				array( 'title' => __( 'How Much RAM Do I Need?', 'computingskill' ), 'url' => home_url( '/how-much-ram-do-i-need/' ) ),
				array( 'title' => __( 'How to Choose a Gaming GPU', 'computingskill' ), 'url' => home_url( '/how-to-choose-a-gaming-gpu/' ) ),
				array( 'title' => __( 'SSD vs HDD', 'computingskill' ), 'url' => home_url( '/ssd-vs-hdd/' ) ),
				array( 'title' => __( '1080p vs 1440p vs 4K', 'computingskill' ), 'url' => home_url( '/1080p-vs-1440p-vs-4k/' ) ),
			) );
			?>
			<p class="cs-section-more"><a href="<?php echo esc_url( home_url( '/guides/' ) ); ?>"><?php esc_html_e( 'View all guides →', 'computingskill' ); ?></a></p>
		</section>

		<section class="cs-home-section">
			<h2><?php esc_html_e( 'Popular GPU & CPU Bottleneck Pages', 'computingskill' ); ?></h2>
			<?php
			$pairs = cs_get_bottleneck_pairs();
			$cpus  = cs_get_cpu_data();
			$gpus  = cs_get_gpu_data();
			echo '<div class="cs-card-grid cs-combo-grid">';
			foreach ( array_slice( $pairs, 0, 6 ) as $pair ) {
				if ( isset( $cpus[ $pair['cpu'] ] ) && isset( $gpus[ $pair['gpu'] ] ) ) {
					$c = $cpus[ $pair['cpu'] ];
					$g = $gpus[ $pair['gpu'] ];
					echo '<a class="cs-card cs-combo-card" href="' . esc_url( cs_bottleneck_url( $pair['cpu'], $pair['gpu'] ) ) . '">';
					echo '<span class="cs-card-title">' . esc_html( $c['name'] . ' + ' . $g['name'] ) . '</span>';
					echo cs_bn_load_bars( cs_calculate_bottleneck( $c, $g ), true ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside.
					echo '</a>';
				}
			}
			echo '</div>';
			?>
			<p class="cs-lb-legend"><span class="cs-lb-key cs-lb-cpu"><?php esc_html_e( 'CPU-bound', 'computingskill' ); ?></span> <span class="cs-lb-key cs-lb-gpu"><?php esc_html_e( 'GPU-bound', 'computingskill' ); ?></span> <span class="cs-lb-key cs-lb-balanced"><?php esc_html_e( 'Balanced (under 6%)', 'computingskill' ); ?></span></p>
		</section>

		<?php
		$latest_guides = new WP_Query( array(
			'post_type'      => 'cs_guide',
			'posts_per_page' => 4,
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );
		if ( $latest_guides->have_posts() ) :
			?>
			<section class="cs-home-section">
				<h2><?php esc_html_e( 'Latest Guides', 'computingskill' ); ?></h2>
				<div class="cs-card-grid">
					<?php
					while ( $latest_guides->have_posts() ) :
						$latest_guides->the_post();
						cs_card( get_the_title(), get_permalink(), get_the_excerpt() );
					endwhile;
					wp_reset_postdata();
					?>
				</div>
			</section>
		<?php endif; ?>

		<section class="cs-home-section cs-why-us">
			<h2><?php esc_html_e( 'Why Use ComputingSkill?', 'computingskill' ); ?></h2>
			<div class="cs-why-grid">
				<div><span class="cs-card-icon" aria-hidden="true"><?php echo cs_icon_svg( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><h3><?php esc_html_e( 'Free, no sign-up', 'computingskill' ); ?></h3><p><?php esc_html_e( 'Every calculator and tool works instantly in your browser — no account, no email wall.', 'computingskill' ); ?></p></div>
				<div><span class="cs-card-icon" aria-hidden="true"><?php echo cs_icon_svg( 'doc' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><h3><?php esc_html_e( 'Transparent methodology', 'computingskill' ); ?></h3><p><?php esc_html_e( 'Every result explains its formula and its limitations. We never dress up an estimate as a lab measurement.', 'computingskill' ); ?></p></div>
				<div><span class="cs-card-icon" aria-hidden="true"><?php echo cs_icon_svg( 'link' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><h3><?php esc_html_e( 'Manufacturer-sourced specifications', 'computingskill' ); ?></h3><p><?php esc_html_e( 'Hardware specs used in our calculators come from manufacturer data, not guesswork.', 'computingskill' ); ?></p></div>
				<div><span class="cs-card-icon" aria-hidden="true"><?php echo cs_icon_svg( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><h3><?php esc_html_e( 'Built for mobile too', 'computingskill' ); ?></h3><p><?php esc_html_e( 'Every tool is designed to work as well on a phone as it does on a desktop.', 'computingskill' ); ?></p></div>
			</div>
		</section>

		<?php
		echo do_shortcode( '[cs_faq]
[cs_faq_item q="Are the calculators on ComputingSkill really free?"]Yes. Every calculator, converter and guide on this site is free to use, with no account required.[/cs_faq_item]
[cs_faq_item q="How accurate are the bottleneck and FPS estimates?"]They are specs-based estimates designed to catch obviously mismatched hardware pairings and give you a general sense of what to expect — not lab-measured benchmark results. Actual performance depends on the specific game, drivers, RAM and settings.[/cs_faq_item]
[cs_faq_item q="Where do the hardware specifications come from?"]From manufacturer and publisher specification pages. We do not invent specifications or benchmark scores — and if you spot a spec that looks wrong, please use the contact page to flag it so we can check it against the source and correct it.[/cs_faq_item]
[/cs_faq]' );
		?>

	</div>
</main>
<?php
get_footer();
