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
