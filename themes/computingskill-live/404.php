<?php
/**
 * 404 error template.
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main id="cs-main" class="cs-main cs-404">
	<div class="cs-container cs-narrow">
		<h1><?php esc_html_e( 'Page Not Found', 'computingskill' ); ?></h1>
		<p><?php esc_html_e( "The page you're looking for doesn't exist or may have moved. Try searching, or jump straight to one of our most popular tools:", 'computingskill' ); ?></p>

		<form class="cs-search-form cs-404-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<label class="screen-reader-text" for="cs-404-search-input"><?php esc_html_e( 'Search ComputingSkill', 'computingskill' ); ?></label>
			<input type="search" id="cs-404-search-input" name="s" placeholder="<?php esc_attr_e( 'Search calculators, tools, guides…', 'computingskill' ); ?>" />
			<button type="submit"><?php esc_html_e( 'Search', 'computingskill' ); ?></button>
		</form>

		<?php
		cs_card_grid( array(
			array( 'title' => __( 'PC Bottleneck Calculator', 'computingskill' ), 'url' => home_url( '/pc-bottleneck-calculator/' ) ),
			array( 'title' => __( 'PSU Calculator', 'computingskill' ), 'url' => home_url( '/psu-calculator/' ) ),
			array( 'title' => __( 'FPS Calculator', 'computingskill' ), 'url' => home_url( '/fps-calculator/' ) ),
			array( 'title' => __( 'All Calculators', 'computingskill' ), 'url' => home_url( '/calculators/' ) ),
		) );
		?>
	</div>
</main>
<?php
get_footer();
