<?php
/**
 * Template Name: Blog / Articles Hub
 * Template Post Type: page
 *
 * D7 (Oct 2026, audit 2.22): /blog/ used to repeat every hub ("56
 * resources": calculators, tools, hardware pages, guides, comparisons).
 * It now lists what is actually read as an article: the published posts
 * first, then the guides and comparisons (cs_blog_article_groups() in
 * inc/internal-links.php). Calculators, tools and hardware pages keep
 * their own hubs, linked once from the intro.
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	$groups      = cs_blog_article_groups();
	$total_count = count( cs_blog_article_items() );
	$hub_links   = cs_blog_hub_links();
	?>
	<main id="cs-main" class="cs-main cs-blog-hub">

		<section class="cs-blog-hero">
			<div class="cs-container cs-narrow">
				<?php cs_breadcrumbs(); ?>
				<h1 class="cs-blog-title"><?php the_title(); ?></h1>
				<?php if ( function_exists( 'cs_hub_meta' ) ) { cs_hub_meta( array( 'post', 'page' ) ); } ?>
				<p class="cs-blog-intro">
					<?php
					printf(
						/* translators: %d: number of articles */
						esc_html__( 'Articles on choosing, building and upgrading a gaming PC: new posts first, then our step-by-step guides and side-by-side comparisons (%d in all).', 'computingskill' ),
						(int) $total_count
					);
					?>
				</p>
				<?php if ( $hub_links ) : ?>
					<p class="cs-blog-intro"><?php echo wp_kses_post( sprintf( __( 'Looking for a calculator, a tool or a part explainer instead? See %s.', 'computingskill' ), cs_bn_join( $hub_links ) ) ); ?></p>
				<?php endif; ?>
				<?php echo function_exists( 'cs_aeo_quick_answer_html' ) ? cs_aeo_quick_answer_html( 'blog' ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside. ?>

				<form class="cs-blog-search-form" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
					<label class="screen-reader-text" for="cs-blog-search-input"><?php esc_html_e( 'Search ComputingSkill', 'computingskill' ); ?></label>
					<input type="search" id="cs-blog-search-input" name="s" placeholder="<?php esc_attr_e( 'Search guides, tools and calculators…', 'computingskill' ); ?>" value="<?php echo esc_attr( get_search_query() ); ?>" />
					<button type="submit"><?php esc_html_e( 'Search', 'computingskill' ); ?></button>
				</form>

				<nav class="cs-blog-quicklinks" aria-label="<?php esc_attr_e( 'Jump to category', 'computingskill' ); ?>">
					<?php foreach ( $groups as $key => $group ) : ?>
						<a href="#<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $group['label'] ); ?></a>
					<?php endforeach; ?>
				</nav>
			</div>
		</section>

		<div class="cs-container">

			<?php echo cs_latest_posts_section( 12, __( 'Latest Posts', 'computingskill' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside. ?>

			<?php foreach ( $groups as $key => $group ) : ?>
				<?php if ( empty( $group['items'] ) ) continue; ?>
				<section class="cs-blog-category" id="<?php echo esc_attr( $key ); ?>" aria-labelledby="cs-blog-cat-<?php echo esc_attr( $key ); ?>">
					<div class="cs-blog-category-header">
						<div>
							<h2 id="cs-blog-cat-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $group['label'] ); ?></h2>
							<p class="cs-blog-section-desc"><?php echo esc_html( $group['desc'] ); ?></p>
						</div>
						<a class="cs-blog-view-all" href="<?php echo esc_url( home_url( $group['archive'] ) ); ?>">
							<?php esc_html_e( 'View category page', 'computingskill' ); ?> &rarr;
						</a>
					</div>
					<div class="cs-card-grid cs-blog-category-grid">
						<?php foreach ( $group['items'] as $item ) : ?>
							<?php cs_card( $item['title'], home_url( '/' . $item['slug'] . '/' ), wp_trim_words( $item['desc'], 18 ) ); ?>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endforeach; ?>

		</div>
	</main>
	<?php
endwhile;

get_footer();
