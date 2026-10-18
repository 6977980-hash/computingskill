<?php
/**
 * ComputingSkill theme functions loader.
 *
 * Keeps functions.php thin — all real logic lives in inc/ and data/ so
 * nothing is one enormous PHP file (per project requirements).
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CS_THEME_VERSION', '1.0.46' );
define( 'CS_THEME_DIR', get_template_directory() );
define( 'CS_THEME_URI', get_template_directory_uri() );

require_once CS_THEME_DIR . '/data/hardware-data.php';
require_once CS_THEME_DIR . '/data/games-data.php';
require_once CS_THEME_DIR . '/inc/setup.php';
require_once CS_THEME_DIR . '/inc/cpt.php';
require_once CS_THEME_DIR . '/inc/breadcrumbs.php';
require_once CS_THEME_DIR . '/inc/content-index.php';
require_once CS_THEME_DIR . '/inc/seo-compat.php';
require_once CS_THEME_DIR . '/inc/author.php';
require_once CS_THEME_DIR . '/inc/schema.php';
require_once CS_THEME_DIR . '/inc/shortcodes.php';
require_once CS_THEME_DIR . '/inc/games-content.php';
require_once CS_THEME_DIR . '/inc/extra-faq.php';
require_once CS_THEME_DIR . '/inc/quick-answers.php';
require_once CS_THEME_DIR . '/inc/template-tags.php';
require_once CS_THEME_DIR . '/inc/design.php';
require_once CS_THEME_DIR . '/inc/bottleneck.php';
require_once CS_THEME_DIR . '/inc/bottleneck-content.php';
require_once CS_THEME_DIR . '/inc/calculator-pages.php';
require_once CS_THEME_DIR . '/inc/combo-pages.php';
require_once CS_THEME_DIR . '/inc/blog.php';
require_once CS_THEME_DIR . '/inc/internal-links.php';
require_once CS_THEME_DIR . '/inc/tier-list.php';
require_once CS_THEME_DIR . '/inc/aeo.php';
require_once CS_THEME_DIR . '/inc/contact-form.php';
require_once CS_THEME_DIR . '/inc/redirects.php';
require_once CS_THEME_DIR . '/inc/technical-seo.php';
require_once CS_THEME_DIR . '/inc/analytics.php';
require_once CS_THEME_DIR . '/inc/legal.php';
require_once CS_THEME_DIR . '/inc/fixes-loader.php';

/**
 * Security hardening basics (per project spec section 37).
 */
remove_action( 'wp_head', 'wp_generator' );                 // Don't broadcast exact WP version.
add_filter( 'xmlrpc_enabled', '__return_false' );             // Disable XML-RPC (common attack surface).

/**
 * Make sure only logged-in users with edit_theme_options can see PHP
 * notices, etc. WP_DEBUG display is controlled in wp-config.php, not here.
 */
