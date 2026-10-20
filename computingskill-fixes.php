<?php
/**
 * Plugin Name:       ComputingSkill Fixes
 * Plugin URI:        https://github.com/6977980-hash/computingskill
 * Description:       Deploys the ComputingSkill Live theme (with all site fixes) from GitHub into wp-content/themes. Fixes stay even if this plugin is removed.
 * Version:           1.3.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            ComputingSkill
 * License:           GPL-2.0-or-later
 * Text Domain:       computingskill-fixes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CSF_VERSION', '1.3.0' );
define( 'CSF_FILE', __FILE__ );
define( 'CSF_DIR', plugin_dir_path( __FILE__ ) );
define( 'CSF_URL', plugin_dir_url( __FILE__ ) );

require_once CSF_DIR . 'includes/class-csf-admin.php';
require_once CSF_DIR . 'includes/class-csf-theme.php';

CSF_Admin::init();
CSF_Theme::init();
