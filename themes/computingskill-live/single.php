<?php
/**
 * Fallback singular template.
 *
 * Handles two cases: (1) real blog posts ('post') get date/category meta; (2) the site's custom post types (cs_calculator, cs_tool,
 * cs_hardware, cs_guide, cs_comparison, cs_resource) fall through to this
 * file whenever no page-templates/*.php is assigned, and get the same
 * clean breadcrumbs + H1 + content layout as page.php, without blog
 * chrome. This keeps one file in charge instead of duplicating six
 * near-identical single-{post_type}.php files.
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
					<?php
					// E3 (ONP-01): every page here gets the byline and date, not
					// only posts; the part and comparison pages (D10) had none.
					cs_article_meta();
					?>
					<?php if ( $cs_is_blog_post ) : ?>
						<p class="cs-article-meta">
							<?php esc_html_e( 'Published', 'computingskill' ); ?>
							<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
							<?php
							// E3 (SEO-07): no link to a category archive that is
							// noindexed for being thin (inc/technical-seo.php).
							$categories = array();
							foreach ( get_the_category() as $cs_cat ) {
								if ( ! function_exists( 'cs_is_thin_term' ) || ! cs_is_thin_term( $cs_cat ) ) {
									$categories[] = '<a href="' . esc_url( get_category_link( $cs_cat ) ) . '" rel="category tag">' . esc_html( $cs_cat->name ) . '</a>';
								}
							}
							if ( $categories ) {
								echo ' &middot; ' . wp_kses_post( implode( ', ', $categories ) );
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

		<?php endwhile; ?>
	</div>
</main>
<?php
get_footer();
