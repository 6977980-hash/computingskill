<?php
/**
 * Default page template — also the fallback for the custom post types
 * registered in inc/cpt.php that don't set a more specific template.
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main id="cs-main" class="cs-main cs-single-page">
	<div class="cs-container cs-narrow">
		<?php cs_breadcrumbs(); ?>

		<?php while ( have_posts() ) : the_post(); ?>
			<article <?php post_class( 'cs-article' ); ?>>
				<header class="cs-article-header">
					<h1 class="cs-article-title"><?php the_title(); ?></h1>
					<?php cs_article_meta(); ?>
				</header>

				<div class="cs-article-content">
					<?php the_content(); ?>
				</div>

				<?php if ( function_exists( 'cs_maybe_print_extra_faq' ) ) : ?>
					<?php cs_maybe_print_extra_faq(); ?>
				<?php endif; ?>
				<?php if ( function_exists( 'cs_maybe_print_bottleneck_index' ) ) : ?>
					<?php cs_maybe_print_bottleneck_index(); ?>
					<?php if ( function_exists( 'cs_maybe_print_context_links' ) ) { cs_maybe_print_context_links(); } ?>
				<?php endif; ?>
			</article>
		<?php endwhile; ?>
	</div>
</main>
<?php
get_footer();
