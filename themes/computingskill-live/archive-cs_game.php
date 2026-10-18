<?php
/**
 * Games hub — /games/. Auto-generated CPT archive (has_archive => 'games'
 * in inc/cpt.php), so any new cs_game post added later shows up here
 * automatically with no theme changes needed. Same visual language as the
 * Blog hub (dark hero + search + card grid) so the site's newer sections
 * feel consistent, but scoped to system-requirements content: "what do I
 * need to run this game" is a very different search intent from the
 * calculator/guide content the Blog hub indexes.
 *
 * @package ComputingSkill
 */

get_header();

$games_data  = cs_get_games_data();
$total_games = count( $games_data );
?>
<main id="cs-main" class="cs-main cs-games-hub">

	<section class="cs-games-hero">
		<div class="cs-container cs-narrow">
			<?php cs_breadcrumbs(); ?>
			<h1 class="cs-games-title"><?php esc_html_e( 'Can I Run This Game?', 'computingskill' ); ?></h1>
			<?php if ( function_exists( 'cs_hub_meta' ) ) { cs_hub_meta( array( 'cs_game' ) ); } ?>
			<p class="cs-games-intro">
				<?php
				printf(
					/* translators: %d: number of games covered */
					esc_html__( 'Minimum and recommended PC specs for %d popular games, plus a one-click link to check your exact CPU and GPU pairing against each title with our bottleneck calculator.', 'computingskill' ),
					(int) $total_games
				);
				?>
			</p>
			<?php echo function_exists( 'cs_aeo_quick_answer_html' ) ? cs_aeo_quick_answer_html( 'games' ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside. ?>
			<form class="cs-games-search-form" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<label class="screen-reader-text" for="cs-games-search-input"><?php esc_html_e( 'Search games', 'computingskill' ); ?></label>
				<input type="search" id="cs-games-search-input" name="s" placeholder="<?php esc_attr_e( 'Search for a game…', 'computingskill' ); ?>" value="<?php echo esc_attr( get_search_query() ); ?>" />
				<button type="submit"><?php esc_html_e( 'Search', 'computingskill' ); ?></button>
			</form>
		</div>
	</section>

	<div class="cs-container">
		<?php if ( have_posts() ) : ?>
			<div class="cs-card-grid cs-games-grid">
				<?php while ( have_posts() ) : the_post();
					$slug = get_post_field( 'post_name' );
					$g    = isset( $games_data[ $slug ] ) ? $games_data[ $slug ] : null;
					?>
					<a class="cs-card cs-game-card" href="<?php the_permalink(); ?>">
						<span class="cs-blog-card-tag"><?php echo esc_html( $g ? $g['genre'] : __( 'Game', 'computingskill' ) ); ?></span>
						<span class="cs-card-title"><?php the_title(); ?></span>
						<?php if ( $g ) : ?>
							<span class="cs-card-desc"><?php echo esc_html( $g['developer'] . ' · ' . $g['release_year'] ); ?></span>
							<span class="cs-card-desc"><?php echo esc_html( sprintf( __( '%1$dGB RAM recommended · %2$dGB install', 'computingskill' ), (int) $g['rec']['ram'], cs_game_storage_gb( $g ) ) ); ?></span>
						<?php endif; ?>
					</a>
				<?php endwhile; ?>
			</div>
			<?php the_posts_pagination(); ?>
			<?php echo cs_games_hub_overview( $games_data ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside. ?>
		<?php else : ?>
			<p><?php esc_html_e( 'No games found yet — check back soon.', 'computingskill' ); ?></p>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
