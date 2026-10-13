<?php
/**
 * Plugin Name:       ComputingSkill Fixes
 * Plugin URI:        https://github.com/6977980-hash/computingskill
 * Description:       Site-specific bug and error fixes for computingskill.com. Deployed from GitHub via Hostinger Git auto-deploy.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            ComputingSkill
 * License:           GPL-2.0-or-later
 * Text Domain:       computingskill-fixes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CSF_VERSION', '1.0.0' );
define( 'CSF_FILE', __FILE__ );
define( 'CSF_DIR', plugin_dir_path( __FILE__ ) );
define( 'CSF_URL', plugin_dir_url( __FILE__ ) );

require_once CSF_DIR . 'includes/class-csf-loader.php';
require_once CSF_DIR . 'includes/class-csf-assets.php';
require_once CSF_DIR . 'includes/class-csf-admin.php';

CSF_Loader::init();
CSF_Assets::init();
CSF_Admin::init();
