<?php
/**
 * Search results template.
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main id="cs-main" class="cs-main cs-search-results">
	<div class="cs-container">
		<?php cs_breadcrumbs(); ?>

		<header class="cs-archive-header">
			<h1>
				<?php
				printf(
					/* translators: %s: search query */
					esc_html__( 'Search results for: %s', 'computingskill' ),
					'<span>' . esc_html( get_search_query() ) . '</span>'
				);
				?>
			</h1>
		</header>

		<?php
		// U4 (U-35): tools for intent words ("psu for rtx 4080", "can i run
		// cyberpunk", "how much ram"), which text search ranked low or missed.
		$best = ( ! is_paged() && function_exists( 'cs_search_suggestions' ) ) ? cs_search_suggestions( get_search_query( false ) ) : array();
		if ( $best ) :
			?>
			<section class="cs-search-best" aria-labelledby="cs-search-best-title">
				<h2 id="cs-search-best-title"><?php esc_html_e( 'Best match', 'computingskill' ); ?></h2>
				<div class="cs-card-grid">
					<?php
					foreach ( $best as $page ) {
						cs_card( get_the_title( $page ), get_permalink( $page ), get_the_excerpt( $page ) );
					}
					?>
				</div>
			</section>
			<?php if ( have_posts() ) : ?>
				<h2 class="cs-search-more-title"><?php esc_html_e( 'All results', 'computingskill' ); ?></h2>
			<?php endif; ?>
		<?php endif; ?>

		<?php if ( have_posts() ) : ?>
			<div class="cs-card-grid">
				<?php
				while ( have_posts() ) :
					the_post();
					cs_card( get_the_title(), get_permalink(), get_the_excerpt() );
				endwhile;
				?>
			</div>
			<?php the_posts_pagination(); ?>
		<?php else : ?>
			<p><?php esc_html_e( 'No results found. Try a different search term, or browse the calculators and guides below.', 'computingskill' ); ?></p>
			<?php
			cs_card_grid( array(
				array( 'title' => __( 'All Calculators', 'computingskill' ), 'url' => home_url( '/calculators/' ) ),
				array( 'title' => __( 'All Guides', 'computingskill' ), 'url' => home_url( '/guides/' ) ),
				array( 'title' => __( 'All Hardware Pages', 'computingskill' ), 'url' => home_url( '/hardware/' ) ),
			) );
			?>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
