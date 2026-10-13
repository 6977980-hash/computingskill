<?php
/**
 * Fallback singular template.
 *
 * Handles two cases: (1) real blog posts ('post') get date/category meta
 * and comments; (2) the site's custom post types (cs_calculator, cs_tool,
 * cs_hardware, cs_guide, cs_comparison, cs_resource) fall through to this
 * file whenever no page-templates/*.php is assigned, and get the same
 * clean breadcrumbs + H1 + content layout as page.php, without blog
 * chrome. This keeps one file in charge instead of duplicating six
 * near-identical single-{post_type}.php files.
 *
 * @package ComputingSkill
 */

get_header();

$cs_is_blog_post = ( 'post' === get_post_type() );
?>
<main id="cs-main" class="cs-main cs-single-page">
	<div class="cs-container cs-narrow">
		<?php cs_breadcrumbs(); ?>

		<?php while ( have_posts() ) : the_post(); ?>
			<article <?php post_class( 'cs-article' ); ?>>
				<header class="cs-article-header">
					<h1 class="cs-article-title"><?php the_title(); ?></h1>
					<?php if ( $cs_is_blog_post ) : ?>
						<?php cs_article_meta(); ?>
						<p class="cs-article-meta">
							<?php esc_html_e( 'Published', 'computingskill' ); ?>
							<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
							<?php
							$categories = get_the_category_list( ', ' );
							if ( $categories ) {
								echo ' &middot; ' . wp_kses_post( $categories );
							}
							?>
						</p>
					<?php endif; ?>
				</header>

				<?php if ( $cs_is_blog_post && has_post_thumbnail() ) : ?>
					<div class="cs-article-thumb"><?php the_post_thumbnail( 'large' ); ?></div>
				<?php endif; ?>

				<div class="cs-article-content">
					<?php the_content(); ?>
				</div>

				<?php
				if ( $cs_is_blog_post ) {
					echo cs_related_shortcode( array(
						'title' => __( 'Tools for This Article', 'computingskill' ),
						'links' => implode( ',', cs_post_tool_links( get_the_ID() ) ),
					) ); // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped sub-output only, see cs_related_shortcode().
				}
				?>
			</article>

			<?php if ( $cs_is_blog_post && ( comments_open() || get_comments_number() ) ) : ?>
				<?php comments_template(); ?>
			<?php endif; ?>
		<?php endwhile; ?>
	</div>
</main>
<?php
get_footer();
