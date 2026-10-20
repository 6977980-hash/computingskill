<?php
/**
 * The main template file — required by WordPress, used as the final
 * fallback for any view without a more specific template.
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main id="cs-main" class="cs-main">
	<div class="cs-container">
		<?php cs_breadcrumbs(); ?>

		<?php if ( have_posts() ) : ?>
			<div class="cs-post-grid">
				<?php
				while ( have_posts() ) :
					the_post();
					?>
					<article <?php post_class( 'cs-post-card' ); ?>>
						<?php if ( has_post_thumbnail() ) : ?>
							<a class="cs-post-card-thumb" href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'medium' ); ?></a>
						<?php endif; ?>
						<h2 class="cs-post-card-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
						<div class="cs-post-card-excerpt"><?php the_excerpt(); ?></div>
					</article>
					<?php
				endwhile;
				?>
			</div>
			<?php the_posts_pagination(); ?>
		<?php else : ?>
			<p><?php esc_html_e( 'Nothing found.', 'computingskill' ); ?></p>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
