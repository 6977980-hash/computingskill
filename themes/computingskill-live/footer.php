<?php
/**
 * The theme footer.
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
</div><!-- #cs-content -->

<footer id="cs-colophon" class="cs-site-footer">
	<div class="cs-container cs-footer-inner">
		<div class="cs-footer-col cs-footer-brand">
			<p class="cs-footer-logo cs-wordmark"><?php echo cs_wordmark(); // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped parts. ?></p>
			<p><?php esc_html_e( 'Free PC hardware calculators, computer tools and computing guides — built to help you buy, build and upgrade with confidence.', 'computingskill' ); ?></p>
		</div>

		<div class="cs-footer-col">
			<h3><?php esc_html_e( 'Categories', 'computingskill' ); ?></h3>
			<ul>
				<li><a href="<?php echo esc_url( home_url( '/calculators/' ) ); ?>"><?php esc_html_e( 'Calculators', 'computingskill' ); ?></a></li>
				<li><a href="<?php echo esc_url( home_url( '/computer-tools/' ) ); ?>"><?php esc_html_e( 'Computer Tools', 'computingskill' ); ?></a></li>
				<li><a href="<?php echo esc_url( home_url( '/hardware/' ) ); ?>"><?php esc_html_e( 'Hardware', 'computingskill' ); ?></a></li>
				<li><a href="<?php echo esc_url( home_url( '/guides/' ) ); ?>"><?php esc_html_e( 'Guides', 'computingskill' ); ?></a></li>
				<li><a href="<?php echo esc_url( home_url( '/comparisons/' ) ); ?>"><?php esc_html_e( 'Comparisons', 'computingskill' ); ?></a></li>
				<li><a href="<?php echo esc_url( home_url( '/bottleneck/' ) ); ?>"><?php esc_html_e( 'Bottleneck Comparisons', 'computingskill' ); ?></a></li>
				<li><a href="<?php echo esc_url( home_url( '/blog/' ) ); ?>"><?php esc_html_e( 'All Articles', 'computingskill' ); ?></a></li>
			</ul>
		</div>

		<div class="cs-footer-col">
			<h3><?php esc_html_e( 'Legal', 'computingskill' ); ?></h3>
			<ul>
				<li><a href="<?php echo esc_url( home_url( '/about-us/' ) ); ?>"><?php esc_html_e( 'About Us', 'computingskill' ); ?></a></li>
				<li><a href="<?php echo esc_url( home_url( '/methodology/' ) ); ?>"><?php esc_html_e( 'Methodology', 'computingskill' ); ?></a></li>
				<li><a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>"><?php esc_html_e( 'Privacy Policy', 'computingskill' ); ?></a></li>
				<li><a href="<?php echo esc_url( home_url( '/disclaimer/' ) ); ?>"><?php esc_html_e( 'Disclaimer', 'computingskill' ); ?></a></li>
				<li><a href="<?php echo esc_url( home_url( '/terms-of-use/' ) ); ?>"><?php esc_html_e( 'Terms of Use', 'computingskill' ); ?></a></li>
				<li><a href="<?php echo esc_url( home_url( '/editorial-policy/' ) ); ?>"><?php esc_html_e( 'Editorial Policy', 'computingskill' ); ?></a></li>
				<li><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php esc_html_e( 'Contact', 'computingskill' ); ?></a></li>
				<?php // Shown by the consent script in inc/analytics.php only when analytics is on. ?>
				<li><button type="button" class="cs-cookie-settings" data-cs-consent-open hidden><?php esc_html_e( 'Cookie settings', 'computingskill' ); ?></button></li>
			</ul>
		</div>

		<?php if ( is_active_sidebar( 'footer-1' ) ) : ?>
			<div class="cs-footer-col cs-footer-widgets">
				<?php dynamic_sidebar( 'footer-1' ); ?>
			</div>
		<?php endif; ?>
	</div>

	<div class="cs-container cs-footer-bottom">
		<p>&copy; <?php echo esc_html( date_i18n( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. <?php esc_html_e( 'All rights reserved.', 'computingskill' ); ?></p>
		<p class="cs-footer-trademarks"><?php echo esc_html( cs_trademark_sentence() ); ?></p>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
