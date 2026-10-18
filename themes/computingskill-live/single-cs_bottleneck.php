<?php
/**
 * Template for programmatic /bottleneck/{cpu}/{gpu}/ combination pages.
 *
 * Content is generated from the verified hardware dataset + the estimate
 * engine in inc/bottleneck.php, so every page has genuinely different
 * numbers, specs and recommendations — not just a swapped product name.
 * Post meta 'cs_cpu' / 'cs_gpu' (set by the WXR import) select which pair.
 *
 * @package ComputingSkill
 */

get_header();

while ( have_posts() ) :
	the_post();

	$cpu_slug = get_post_meta( get_the_ID(), 'cs_cpu', true );
	$gpu_slug = get_post_meta( get_the_ID(), 'cs_gpu', true );
	$cpus     = cs_get_cpu_data();
	$gpus     = cs_get_gpu_data();

	if ( ! isset( $cpus[ $cpu_slug ] ) || ! isset( $gpus[ $gpu_slug ] ) ) {
		?>
		<main id="cs-main" class="cs-main cs-narrow cs-container">
			<h1><?php esc_html_e( 'Combination not found', 'computingskill' ); ?></h1>
			<p><?php esc_html_e( 'This CPU/GPU pairing is not in our database yet.', 'computingskill' ); ?> <a href="<?php echo esc_url( home_url( '/pc-bottleneck-calculator/' ) ); ?>"><?php esc_html_e( 'Try the full bottleneck calculator instead.', 'computingskill' ); ?></a></p>
		</main>
		<?php
		get_footer();
		return;
	}

	$cpu     = $cpus[ $cpu_slug ];
	$gpu     = $gpus[ $gpu_slug ];
	$results = cs_calculate_bottleneck( $cpu, $gpu );
	$labels  = array( '1080p' => '1080p (Full HD)', '1440p' => '1440p (QHD)', '4k' => '4K (UHD)' );

	?>
	<main id="cs-main" class="cs-main cs-single-page">
		<div class="cs-container cs-narrow">
			<?php cs_breadcrumbs(); ?>

			<article class="cs-article cs-bottleneck-page">
				<header class="cs-article-header">
					<?php /* translators: 1: CPU name, 2: GPU name. Not "Calculator": the inputs live on the calculator page (D6, 2.10). */ ?>
					<h1><?php printf( esc_html__( '%1$s + %2$s Bottleneck at 1080p, 1440p and 4K', 'computingskill' ), esc_html( $cpu['name'] ), esc_html( $gpu['name'] ) ); ?></h1>
					<?php cs_article_meta(); ?>
					<p class="cs-lede">
						<?php
						printf(
							/* translators: 1: CPU name, 2: GPU name */
							esc_html__( 'A bottleneck estimate for pairing the %1$s with the %2$s, built from our gaming-performance scores and broken down by resolution — plus what it means for your build.', 'computingskill' ),
							esc_html( $cpu['name'] ),
							esc_html( $gpu['name'] )
						);
						?>
					</p>
				</header>

				<section class="cs-bn-summary">
					<h2><?php esc_html_e( 'Quick Summary', 'computingskill' ); ?></h2>
					<p><?php echo wp_kses_post( cs_bottleneck_summary( $cpu['name'], $gpu['name'], $results, $labels, cs_bn_target_res( $gpu ) ) ); ?></p>
					<?php cs_estimate_disclaimer( 'bottleneck estimate' ); ?>
					<?php
					// This page shows fixed results at High settings for a typical
					// AAA game; the calculator takes the other inputs. Pre-filled
					// through the share-link fields (cs_share_param_keys), whose
					// canonical is the clean calculator URL; nofollow so crawlers
					// don't queue one URL per pair.
					$cs_calc_link = add_query_arg( array( 'cpu' => $cpu_slug, 'gpu' => $gpu_slug ), home_url( '/pc-bottleneck-calculator/' ) );
					?>
					<p class="cs-bn-calc-link"><a class="cs-btn" href="<?php echo esc_url( $cs_calc_link ); ?>" rel="nofollow"><?php esc_html_e( 'Change the game type or settings in the Bottleneck Calculator', 'computingskill' ); ?></a></p>
				</section>

				<section class="cs-bn-specs">
					<h2><?php esc_html_e( 'CPU & GPU Specifications', 'computingskill' ); ?></h2>
					<div class="cs-spec-columns">
						<div>
							<h3><?php echo esc_html( $cpu['name'] ); ?></h3>
							<ul class="cs-spec-list">
								<li><?php printf( esc_html__( 'Cores / Threads: %s', 'computingskill' ), esc_html( $cpu['cores'] . ' / ' . $cpu['threads'] ) ); ?></li>
								<li><?php printf( esc_html__( 'Base / Boost Clock: %s', 'computingskill' ), esc_html( $cpu['base_clock_ghz'] . ' GHz / ' . $cpu['boost_clock_ghz'] . ' GHz' ) ); ?></li>
								<li><?php printf( esc_html__( 'TDP: %s', 'computingskill' ), esc_html( $cpu['tdp_w'] . 'W' ) ); ?></li>
								<li><?php printf( esc_html__( 'Socket: %s', 'computingskill' ), esc_html( $cpu['socket'] ) ); ?></li>
								<li><?php printf( esc_html__( 'Generation: %s', 'computingskill' ), esc_html( $cpu['generation'] ) ); ?></li>
							</ul>
						</div>
						<div>
							<h3><?php echo esc_html( $gpu['name'] ); ?></h3>
							<ul class="cs-spec-list">
								<li><?php printf( esc_html__( 'VRAM: %s', 'computingskill' ), esc_html( $gpu['vram_gb'] . ' GB ' . $gpu['memory_type'] ) ); ?></li>
								<li><?php printf( esc_html__( 'Memory Bus: %s', 'computingskill' ), esc_html( $gpu['memory_bus'] . '-bit' ) ); ?></li>
								<li><?php printf( esc_html__( 'Boost Clock: %s', 'computingskill' ), esc_html( $gpu['boost_clock_mhz'] . ' MHz' ) ); ?></li>
								<li><?php printf( esc_html__( 'TDP: %s', 'computingskill' ), esc_html( $gpu['tdp_w'] . 'W' ) ); ?></li>
								<li><?php printf( esc_html__( 'Recommended PSU: %s', 'computingskill' ), esc_html( $gpu['recommended_psu_w'] . 'W+' ) ); ?></li>
							</ul>
						</div>
					</div>
					<?php
					cs_hardware_source_line( array(
						array( 'name' => $cpu['name'], 'source_url' => isset( $cpu['source_url'] ) ? $cpu['source_url'] : null ),
						array( 'name' => $gpu['name'], 'source_url' => isset( $gpu['source_url'] ) ? $gpu['source_url'] : null ),
					) );
					// D7 (2.16): combo pages linked to no hardware page.
					echo cs_il_combo_specs_links( $gpu ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside.
					?>
				</section>

				<section class="cs-bn-breakdown">
					<h2><?php esc_html_e( 'Bottleneck Estimate by Resolution', 'computingskill' ); ?></h2>
					<div class="cs-bn-visual cs-bn-visual-page"><?php echo cs_bn_load_bars( $results ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside. ?></div>
					<div class="cs-table-wrap">
						<table class="cs-hardware-table cs-bn-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Resolution', 'computingskill' ); ?></th>
									<th><?php esc_html_e( 'Estimate', 'computingskill' ); ?></th>
									<th><?php esc_html_e( 'Likely Limiting Part', 'computingskill' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $results as $res => $r ) : ?>
									<tr>
										<td><?php echo esc_html( $labels[ $res ] ); ?></td>
										<td><?php echo esc_html( cs_bn_label( $r ) ); ?></td>
										<td>
											<?php
											if ( 'balanced' === $r['component'] ) {
												esc_html_e( 'Balanced', 'computingskill' );
											} elseif ( 'cpu' === $r['component'] ) {
												echo esc_html( $cpu['name'] ) . ' (' . esc_html__( 'CPU', 'computingskill' ) . ')';
											} else {
												echo esc_html( $gpu['name'] ) . ' (' . esc_html__( 'GPU', 'computingskill' ) . ')';
											}
											?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>

					<?php // D7 (2.20): shortened; this paragraph was the longest text every combo page repeated word for word. The full explanation is on /methodology/. ?>
					<p class="cs-bn-meaning"><?php printf(
						/* translators: %s: methodology link */
						esc_html__( 'How to read this: a CPU bottleneck %% is the share of the graphics card left idle (under 6%% balanced, 6–14%% mild, 15–29%% noticeable, 30%%+ severe). GPU-limited is normal; there the %% is spare CPU headroom. Typical AAA game, high settings (%s).', 'computingskill' ),
						'<a href="' . esc_url( home_url( '/methodology/' ) ) . '">' . esc_html__( 'how we estimate', 'computingskill' ) . '</a>'
					); ?></p>
					<?php foreach ( $results as $res => $r ) : ?>
						<p><strong><?php echo esc_html( $labels[ $res ] ); ?>:</strong> <?php echo wp_kses_post( cs_bottleneck_sentence( $cpu['name'], $gpu['name'], $labels[ $res ], $r ) ); ?></p>
					<?php endforeach; ?>
				</section>

				<?php
				// D7 (2.16 / 2.20): the same pair per game type, linking the game pages.
				echo cs_il_combo_game_types_html( $cpu_slug, $gpu_slug, $cpu, $gpu ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside.
				?>

				<section class="cs-bn-recommend">
					<h2><?php esc_html_e( 'Recommended Settings & Upgrade Path', 'computingskill' ); ?></h2>
				<?php
				// Phase 2 (Oct 2026): the engine can now return a different
				// limiting part per resolution, so each branch also states
				// the opposite end of the shift when there is one, and the
				// "well-balanced" text is only used when no resolution
				// shows a 15%+ estimate.
				$r1080 = $results['1080p'];
				$r1440 = $results['1440p'];
				$r4k   = $results['4k'];
				// Phase 3: the branch comes from cs_bottleneck_primary_limit()
				// so the Verdict below always names the same main limit.
				$primary = cs_bottleneck_primary_limit( $results );
				?>
				<?php if ( 'gpu' === $primary ) : ?>
					<p><?php printf( esc_html__( 'The %2$s sets the frame rate in this pairing, which is the normal case for gaming. If you want more FPS before considering a GPU upgrade, lowering shadow quality, ambient occlusion and render resolution scale typically helps more than any other settings on a GPU-bound system.', 'computingskill' ), esc_html( $cpu['name'] ), esc_html( $gpu['name'] ) ); ?></p>
					<?php if ( 'cpu' === $r1080['component'] ) : ?>
						<p><?php printf( esc_html__( 'At 1080p the balance tips the other way: the %1$s becomes the likely limit (~%2$s CPU bottleneck), mainly in CPU-heavy games or when chasing very high frame rates.', 'computingskill' ), esc_html( $cpu['name'] ), esc_html( cs_bn_pct( $r1080 ) ) ); ?></p>
					<?php endif; ?>
				<?php elseif ( 'cpu' === $primary ) : ?>
					<p><?php printf( esc_html__( 'The %1$s is the real bottleneck in this pairing, especially at 1080p and in CPU-heavy games (large open worlds, simulation and strategy titles, competitive shooters running uncapped framerates). A CPU upgrade — or simply raising the resolution/settings to shift load back onto the GPU — will get more out of the %2$s.', 'computingskill' ), esc_html( $cpu['name'] ), esc_html( $gpu['name'] ) ); ?></p>
					<?php if ( 'gpu' === $r4k['component'] ) : ?>
						<p><?php printf( esc_html__( 'At 4K the load moves onto the graphics card and the %1$s sets the frame rate instead, which is normal at that resolution.', 'computingskill' ), esc_html( $gpu['name'] ) ); ?></p>
					<?php endif; ?>
				<?php elseif ( 'gpu4k' === $primary ) : ?>
					<p><?php printf( esc_html__( 'The %1$s and %2$s are well matched at 1080p and 1440p. At 4K the %2$s sets the frame rate, which is normal at that resolution: lowering shadow quality, ambient occlusion or render resolution scale (or using upscaling) helps most there.', 'computingskill' ), esc_html( $cpu['name'] ), esc_html( $gpu['name'] ) ); ?></p>
				<?php else : ?>
					<p><?php printf( esc_html__( 'This is a well-balanced pairing. Neither the %1$s nor the %2$s is likely to hold the other back significantly across common resolutions, so upgrade decisions should be driven by which resolution and framerate target you actually want, not by rebalancing this pair.', 'computingskill' ), esc_html( $cpu['name'] ), esc_html( $gpu['name'] ) ); ?></p>
				<?php endif; ?>
					<p><?php esc_html_e( 'Also worth checking before you upgrade anything:', 'computingskill' ); ?></p>
					<ul>
						<li><a href="<?php echo esc_url( home_url( '/ram-calculator/' ) ); ?>"><?php esc_html_e( 'Is 16GB or 32GB of RAM enough for this build?', 'computingskill' ); ?></a></li>
						<li><a href="<?php echo esc_url( home_url( '/psu-calculator/' ) ); ?>"><?php esc_html_e( 'Does your power supply have enough headroom?', 'computingskill' ); ?></a></li>
						<li><a href="<?php echo esc_url( home_url( '/gpu-vram-calculator/' ) ); ?>"><?php esc_html_e( 'Is ' . $gpu['vram_gb'] . 'GB of VRAM enough at your target resolution?', 'computingskill' ); ?></a></li>
						<?php foreach ( cs_il_posts_for_cpu( $cpu_slug ) as $cs_post ) : ?>
							<li><a href="<?php echo esc_url( $cs_post[1] ); ?>"><?php echo esc_html( $cs_post[0] ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</section>

				<section class="cs-bn-verdict">
					<h2><?php esc_html_e( 'Verdict', 'computingskill' ); ?></h2>
					<?php foreach ( cs_bottleneck_verdict( $cpu_slug, $cpu, $gpu, $results ) as $para ) : ?>
						<p><?php echo esc_html( $para ); ?></p>
					<?php endforeach; ?>
				</section>

				<?php
				// D7 (2.20): near-twin CPUs on this GPU, e.g. 5600 vs 5600X.
				echo cs_il_combo_twin_html( $cpu_slug, $gpu_slug, $cpu, $gpu ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside.
				?>

				<?php
				// Oct 2026: same-CPU / same-GPU comparison tables from the
				// other live combo pages (inc/bottleneck-content.php).
				echo cs_bn_neighbours_html( $cpu_slug, $gpu_slug, $cpu, $gpu ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside.
				?>

				<?php
				// Phase 3 (Oct 2026): pair-specific questions built from this
				// page's own data and results (inc/bottleneck-content.php),
				// replacing three answers every combo page used to share.
				echo cs_bottleneck_faq_html( cs_bottleneck_faq_items( $cpu_slug, $cpu, $gpu, $results ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- [cs_faq] escapes its own output.
				?>

				<?php
				echo cs_related_shortcode( array(
					'title' => __( 'Related Combinations & Calculators', 'computingskill' ),
					'links' => 'pc-bottleneck-calculator,fps-calculator,gpu-vram-calculator,pc-upgrade-calculator,cpu-vs-gpu-calculator,cpu-bottleneck-explained,gpu-bottleneck-explained',
				) ); // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped sub-output only, see cs_related_shortcode().
				?>
			</article>
		</div>
	</main>
	<?php
endwhile;

get_footer();
