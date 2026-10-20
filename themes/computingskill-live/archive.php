<?php
/**
 * Archive template for the custom post types (calculators, tools,
 * hardware, guides, comparisons, resources) and standard category/tag
 * archives. Renders as a proper category landing page per project spec
 * section 13: H1, SEO intro, tool/page cards, internal links, FAQs.
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$post_type = get_post_type();
$is_cs_cpt = in_array( $post_type, array( 'cs_calculator', 'cs_tool', 'cs_hardware', 'cs_guide', 'cs_comparison', 'cs_resource' ), true );
$intro     = $is_cs_cpt ? cs_archive_intro( $post_type ) : array( 'title' => '', 'intro' => '' );
?>
<main id="cs-main" class="cs-main cs-archive">
	<div class="cs-container">
		<?php cs_breadcrumbs(); ?>

		<header class="cs-archive-header">
			<h1>
				<?php
				if ( $is_cs_cpt ) {
					echo esc_html( $intro['title'] );
				} elseif ( is_category() || is_tag() || is_tax() ) {
					single_term_title();
				} else {
					the_archive_title();
				}
				?>
			</h1>
			<?php if ( $is_cs_cpt && $intro['intro'] ) : ?>
				<p class="cs-archive-intro"><?php echo esc_html( $intro['intro'] ); ?></p>
			<?php elseif ( ! $is_cs_cpt ) : ?>
				<div class="cs-archive-intro"><?php the_archive_description(); ?></div>
			<?php endif; ?>
		</header>

		<?php if ( have_posts() ) : ?>
			<?php if ( is_category() || is_tag() || is_tax() ) : ?>
				<?php /* translators: %s: category name. D6 (2.4): the page jumped from the H1 to the footer's H3s. */ ?>
				<h2 class="cs-archive-subhead"><?php printf( esc_html__( 'Articles in %s', 'computingskill' ), esc_html( single_term_title( '', false ) ) ); ?></h2>
			<?php endif; ?>
			<div class="cs-card-grid cs-archive-grid">
				<?php
				while ( have_posts() ) :
					the_post();
					cs_card( get_the_title(), get_permalink(), get_the_excerpt() );
				endwhile;
				?>
			</div>
			<?php the_posts_pagination(); ?>
		<?php else : ?>
			<p><?php esc_html_e( 'No pages found yet — check back soon.', 'computingskill' ); ?></p>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
