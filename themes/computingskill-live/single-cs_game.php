<?php
/**
 * Individual game page — /games/{slug}/. Written intro/context comes from
 * the post's own content (the_content()); the structured min/recommended
 * spec tables come from data/games-data.php, matched by post_name, the
 * same "static verified data + real post content" split already used for
 * the hardware dataset and the /bottleneck/ combination pages.
 *
 * @package ComputingSkill
 */

get_header();

while ( have_posts() ) :
	the_post();

	$slug  = get_post_field( 'post_name' );
	$games = cs_get_games_data();
	$g     = isset( $games[ $slug ] ) ? $games[ $slug ] : null;
	?>
	<main id="cs-main" class="cs-main cs-single-page">
		<div class="cs-container cs-narrow">
			<?php cs_breadcrumbs(); ?>

			<article <?php post_class( 'cs-article cs-game-page' ); ?>>
				<header class="cs-article-header">
					<h1 class="cs-article-title"><?php the_title(); ?></h1>
					<?php cs_article_meta(); ?>
					<?php if ( $g ) : ?>
						<p class="cs-lede">
							<?php echo esc_html( $g['developer'] . ' · ' . $g['release_year'] . ' · ' . $g['genre'] ); ?>
						</p>
					<?php endif; ?>
				</header>

				<div class="cs-article-content">
					<?php the_content(); ?>
				</div>

				<?php if ( $g ) : ?>
				<section class="cs-game-specs">
					<h2><?php esc_html_e( 'System Requirements', 'computingskill' ); ?></h2>
					<div class="cs-table-wrap">
						<table class="cs-hardware-table cs-game-specs-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Requirement', 'computingskill' ); ?></th>
									<th><?php esc_html_e( 'Minimum', 'computingskill' ); ?></th>
									<th><?php esc_html_e( 'Recommended', 'computingskill' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<tr><td><?php esc_html_e( 'CPU', 'computingskill' ); ?></td><td><?php echo esc_html( $g['min']['cpu'] ); ?></td><td><?php echo esc_html( $g['rec']['cpu'] ); ?></td></tr>
								<tr><td><?php esc_html_e( 'GPU', 'computingskill' ); ?></td><td><?php echo esc_html( $g['min']['gpu'] ); ?></td><td><?php echo esc_html( $g['rec']['gpu'] ); ?></td></tr>
								<tr><td><?php esc_html_e( 'RAM', 'computingskill' ); ?></td><td><?php echo esc_html( $g['min']['ram'] . ' GB' ); ?></td><td><?php echo esc_html( $g['rec']['ram'] . ' GB' ); ?></td></tr>
								<tr><td><?php esc_html_e( 'Storage', 'computingskill' ); ?></td><td><?php echo esc_html( $g['min']['storage_gb'] . ' GB' ); ?></td><td><?php echo esc_html( $g['rec']['storage_gb'] . ' GB' ); ?></td></tr>
								<tr><td><?php esc_html_e( 'OS', 'computingskill' ); ?></td><td><?php echo esc_html( $g['min']['os'] ); ?></td><td><?php echo esc_html( $g['rec']['os'] ); ?></td></tr>
							</tbody>
						</table>
					</div>
					<?php if ( ! cs_game_rec_official( $g ) ) : ?>
						<p class="cs-disclaimer"><?php echo esc_html( sprintf( __( '%s\'s publisher lists a minimum spec only. The Recommended column is our own suggestion for smoother play, not an official figure.', 'computingskill' ), $g['name'] ) ); ?></p>
					<?php endif; ?>
					<p class="cs-disclaimer">
						<strong><?php esc_html_e( 'Source:', 'computingskill' ); ?></strong>
						<?php if ( ! empty( $g['source_url'] ) ) : ?>
							<?php
							printf(
								/* translators: 1: link open tag, 2: link close tag */
								esc_html__( 'the publisher\'s own officially listed system requirements (%1$sview source%2$s). Actual performance still depends on drivers, background load and in-game settings.', 'computingskill' ),
								'<a href="' . esc_url( $g['source_url'] ) . '" rel="nofollow noopener" target="_blank">',
								'</a>'
							);
							?>
						<?php else : ?>
							<?php esc_html_e( "the publisher's own officially listed system requirements. Actual performance still depends on drivers, background load and in-game settings.", 'computingskill' ); ?>
						<?php endif; ?>
					</p>

					<div class="cs-game-cta">
						<p><?php esc_html_e( "Want to know how YOUR specific CPU and GPU actually pair up, not just whether you clear the minimum?", 'computingskill' ); ?></p>
						<a class="cs-calc-submit cs-game-cta-button" href="<?php echo esc_url( home_url( '/pc-bottleneck-calculator/' ) ); ?>"><?php esc_html_e( 'Test Your Configuration', 'computingskill' ); ?></a>
					</div>
				</section>

				<?php echo cs_game_why_specs_matter_section( $g ); // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped sub-output only, see cs_game_why_specs_matter_section(). ?>

				<?php echo cs_game_compare_section( $slug, $g ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside. ?>

				<?php echo cs_game_faq_section( $g ); // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped sub-output only, see cs_game_faq_section(). ?>
				<?php endif; ?>

				<?php
				// Sep 2026 audit: 11 of the 16 game pages had a single inbound
				// internal link (the /games/ archive). Every game page now
				// links to every other one, same genre first.
				if ( $g ) {
					$same  = array();
					$other = array();
					foreach ( $games as $other_slug => $other_game ) {
						if ( $other_slug === $slug ) {
							continue;
						}
						if ( $other_game['genre'] === $g['genre'] ) {
							$same[] = $other_slug;
						} else {
							$other[] = $other_slug;
						}
					}
					echo cs_related_shortcode( array(
						'title' => __( 'More PC Game System Requirements', 'computingskill' ),
						'links' => implode( ',', array_merge( $same, $other ) ),
					) ); // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped sub-output only, see cs_related_shortcode().
				}

				echo cs_related_shortcode( array(
					'title' => __( 'Check Your Own Build', 'computingskill' ),
					'links' => 'pc-bottleneck-calculator,fps-calculator,gpu-vram-calculator,ram-calculator,pc-upgrade-calculator',
				) ); // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped sub-output only, see cs_related_shortcode().
				?>
			</article>
		</div>
	</main>
	<?php
endwhile;

get_footer();
