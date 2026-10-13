<?php
/**
 * Tools > ComputingSkill Fixes: shows which fixes are loaded.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CSF_Admin {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
	}

	public static function menu() {
		add_management_page(
			'ComputingSkill Fixes',
			'ComputingSkill Fixes',
			'manage_options',
			'computingskill-fixes',
			array( __CLASS__, 'render' )
		);
	}

	public static function render() {
		$status = CSF_Loader::status();
		echo '<div class="wrap"><h1>ComputingSkill Fixes</h1>';
		printf( '<p>Version %s</p>', esc_html( CSF_VERSION ) );

		if ( defined( 'CSF_DISABLE_FIXES' ) && CSF_DISABLE_FIXES ) {
			echo '<p><strong>All fixes are disabled by CSF_DISABLE_FIXES.</strong></p>';
		}

		if ( empty( $status ) ) {
			echo '<p>No fixes loaded yet.</p></div>';
			return;
		}

		echo '<table class="widefat striped"><thead><tr><th>Fix</th><th>Status</th></tr></thead><tbody>';
		foreach ( $status as $name => $state ) {
			printf( '<tr><td>%s</td><td>%s</td></tr>', esc_html( $name ), esc_html( $state ) );
		}
		echo '</tbody></table></div>';
	}
}
