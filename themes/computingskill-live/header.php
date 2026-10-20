<?php
/**
 * The theme header.
 *
 * Deliberately prints NO <title>, <meta name="description">, canonical,
 * robots, OG or Twitter tags of its own beyond the safe, plugin-aware
 * fallback in inc/seo-compat.php — wp_head() is where Rank Math / Yoast
 * inject all of that.
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<link rel="profile" href="https://gmpg.org/xfn/11" />
<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#cs-content"><?php esc_html_e( 'Skip to content', 'computingskill' ); ?></a>

<header id="cs-masthead" class="cs-site-header">
	<div class="cs-container cs-header-inner">
		<div class="cs-branding">
			<?php // Phase 8: theme wordmark instead of the PNG logo, which is unreadable on the dark header (see cs_wordmark()). ?>
			<a class="cs-site-title cs-wordmark" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"><?php echo cs_wordmark(); // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped parts. ?></a>
		</div>

		<button class="cs-menu-toggle" aria-expanded="false" aria-controls="cs-primary-menu" type="button">
			<span class="screen-reader-text"><?php esc_html_e( 'Menu', 'computingskill' ); ?></span>
			<span class="cs-menu-icon" aria-hidden="true"></span>
		</button>

		<nav id="cs-primary-nav" class="cs-primary-navigation" aria-label="<?php esc_attr_e( 'Primary', 'computingskill' ); ?>">
			<?php
			if ( has_nav_menu( 'primary' ) ) {
				wp_nav_menu( array(
					'theme_location' => 'primary',
					'menu_id'        => 'cs-primary-menu',
					'container'      => false,
					'depth'          => 2,
				) );
			} else {
				?>
				<ul id="cs-primary-menu">
					<li><a href="<?php echo esc_url( home_url( '/calculators/' ) ); ?>"><?php esc_html_e( 'Calculators', 'computingskill' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/computer-tools/' ) ); ?>"><?php esc_html_e( 'Tools', 'computingskill' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/blog/' ) ); ?>"><?php esc_html_e( 'Blog', 'computingskill' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/games/' ) ); ?>"><?php esc_html_e( 'Games', 'computingskill' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/hardware/' ) ); ?>"><?php esc_html_e( 'Hardware', 'computingskill' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/guides/' ) ); ?>"><?php esc_html_e( 'Guides', 'computingskill' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/comparisons/' ) ); ?>"><?php esc_html_e( 'Comparisons', 'computingskill' ); ?></a></li>
				</ul>
				<?php
			}
			?>
		</nav>

		<form class="cs-search-form" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<label class="screen-reader-text" for="cs-search-input"><?php esc_html_e( 'Search ComputingSkill', 'computingskill' ); ?></label>
			<input type="search" id="cs-search-input" name="s" placeholder="<?php esc_attr_e( 'Search calculators, tools, guides…', 'computingskill' ); ?>" value="<?php echo esc_attr( get_search_query() ); ?>" />
			<button type="submit"><span class="screen-reader-text"><?php esc_html_e( 'Search', 'computingskill' ); ?></span><?php echo cs_icon_svg( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput -- fixed theme SVG. ?></button>
		</form>
	</div>
</header>

<div id="cs-content" class="cs-site-content">
