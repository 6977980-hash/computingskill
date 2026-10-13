<?php
/**
 * Template Name: Blog / Articles Hub
 * Template Post Type: page
 *
 * A dedicated "blog"-style hub: a search bar, a hand-picked "Featured
 * Articles" grid (10 items spanning every category -- never framed as
 * fake "trending" or "most popular" data), then every calculator, tool,
 * hardware page, guide and comparison on the site grouped into its own
 * category section, exactly matching how each piece of content was
 * actually authored (see inc/content-index.php). Uses the shared site
 * header/footer for branding, navigation and SEO continuity, but the
 * content area is deliberately laid out as its own magazine-style
 * interface rather than a plain article page.
 *
 * @package ComputingSkill
 */

get_header();

while ( have_posts() ) :
	the_post();

	$groups         = cs_get_blog_index();
	$flat           = cs_get_blog_index_flat();
	$flat_by_slug   = array();
	foreach ( $flat as $item ) {
		$flat_by_slug[ $item['slug'] ] = $item;
	}

	$featured = array();
	foreach ( cs_get_blog_featured_slugs() as $slug ) {
		if ( isset( $flat_by_slug[ $slug ] ) ) {
			$featured[] = $flat_by_slug[ $slug ];
		}
	}

	$total_count = count( $flat );
	?>
	<main id="cs-main" class="cs-main cs-blog-hub">

		<section class="cs-blog-hero">
			<div class="cs-container cs-narrow">
				<?php cs_breadcrumbs(); ?>
				<h1 class="cs-blog-title"><?php the_title(); ?></h1>
				<p class="cs-blog-intro">
					<?php
					printf(
						/* translators: %d: total number of calculators, tools and guides */
						esc_html__( 'Every calculator, tool, hardware page, guide and comparison on ComputingSkill in one place — %d free resources and counting. Search for something specific, jump to a category, or browse the featured picks below.', 'computingskill' ),
						(int) $total_count
					);
					?>
				</p>

				<form class="cs-blog-search-form" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
					<label class="screen-reader-text" for="cs-blog-search-input"><?php esc_html_e( 'Search ComputingSkill', 'computingskill' ); ?></label>
					<input type="search" id="cs-blog-search-input" name="s" placeholder="<?php esc_attr_e( 'Search calculators, tools, hardware, guides…', 'computingskill' ); ?>" value="<?php echo esc_attr( get_search_query() ); ?>" />
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

			<?php if ( ! empty( $featured ) ) : ?>
			<section class="cs-blog-featured" aria-labelledby="cs-blog-featured-heading">
				<h2 id="cs-blog-featured-heading"><?php esc_html_e( 'Featured Articles', 'computingskill' ); ?></h2>
				<p class="cs-blog-section-desc"><?php esc_html_e( 'A hand-picked starting point spanning calculators, hardware guides and comparisons.', 'computingskill' ); ?></p>
				<div class="cs-card-grid cs-blog-featured-grid">
					<?php foreach ( $featured as $item ) : ?>
						<a class="cs-card cs-blog-card" href="<?php echo esc_url( home_url( '/' . $item['slug'] . '/' ) ); ?>">
							<span class="cs-blog-card-tag"><?php echo esc_html( $item['group_label'] ); ?></span>
							<span class="cs-card-title"><?php echo esc_html( $item['title'] ); ?></span>
							<span class="cs-card-desc"><?php echo esc_html( wp_trim_words( $item['desc'], 22 ) ); ?></span>
						</a>
					<?php endforeach; ?>
				</div>
			</section>
			<?php endif; ?>

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
