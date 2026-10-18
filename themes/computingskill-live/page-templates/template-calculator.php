<?php
/**
 * Template Name: Calculator Page
 * Template Post Type: page, cs_calculator, cs_tool
 *
 * Structure per project spec section 20: breadcrumbs, H1, short intro,
 * calculator (near the top), result, explanation, formula, example,
 * limitations, related calculators/guides, FAQs — all authored inside
 * the_content() in the order the content was written, with the
 * [cs_calculator] shortcode placed right after the intro paragraph.
 *
 * @package ComputingSkill
 */

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<main id="cs-main" class="cs-main cs-single-page cs-calculator-page">
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
