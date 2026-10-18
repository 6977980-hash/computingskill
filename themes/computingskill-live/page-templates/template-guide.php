<?php
/**
 * Template Name: Guide Page
 * Template Post Type: page, cs_guide, cs_comparison, cs_resource
 *
 * @package ComputingSkill
 */

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<main id="cs-main" class="cs-main cs-single-page cs-guide-page">
		<div class="cs-container cs-narrow">
			<?php cs_breadcrumbs(); ?>

			<article <?php post_class( 'cs-article' ); ?>>
				<header class="cs-article-header">
					<h1 class="cs-article-title"><?php the_title(); ?></h1>
					<?php cs_article_meta(); ?>
				</header>

				<div class="cs-article-content">
					<?php the_content(); ?>
				</div>

				<?php cs_maybe_print_extra_faq(); ?>
				<?php cs_maybe_print_bottleneck_index(); ?>
				<?php if ( function_exists( 'cs_maybe_print_context_links' ) ) { cs_maybe_print_context_links(); } ?>
				<?php cs_maybe_print_games_crosslink(); ?>
			</article>
		</div>
	</main>
	<?php
endwhile;

get_footer();
