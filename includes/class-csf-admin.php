<?php
/**
 * Tools > ComputingSkill Fixes: theme sync status, "Sync now" button and
 * which theme fixes are loaded.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CSF_Admin {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_csf_sync', array( __CLASS__, 'handle_sync' ) );
	}

	public static function menu() {
		add_management_page( 'ComputingSkill Fixes', 'ComputingSkill Fixes', 'manage_options', 'computingskill-fixes', array( __CLASS__, 'render' ) );
	}

	public static function handle_sync() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Not allowed.' );
		}
		check_admin_referer( 'csf_sync' );
		CSF_Theme::sync();
		wp_safe_redirect( admin_url( 'tools.php?page=computingskill-fixes' ) );
		exit;
	}

	public static function render() {
		$state = get_option( CSF_Theme::OPTION, array() );
		echo '<div class="wrap"><h1>ComputingSkill Fixes</h1>';
		printf( '<p>Plugin version %s. Theme copied to <code>%s</code>.</p>', esc_html( CSF_VERSION ), esc_html( CSF_Theme::target() ) );

		if ( empty( $state['time'] ) ) {
			echo '<p>Theme not synced yet.</p>';
		} else {
			printf(
				'<p>Last sync: %s — %s</p>',
				esc_html( wp_date( 'Y-m-d H:i:s', $state['time'] ) ),
				! empty( $state['ok'] ) ? 'OK' : '<strong>FAILED (check file permissions)</strong>'
			);
		}

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'csf_sync' );
		echo '<input type="hidden" name="action" value="csf_sync" />';
		submit_button( 'Sync theme now', 'secondary', 'submit', false );
		echo '</form>';

		if ( get_stylesheet() !== CSF_Theme::SLUG ) {
			echo '<p><strong>Note:</strong> activate the "ComputingSkill Live" theme in Appearance → Themes for fixes to show on the site.</p>';
		} elseif ( function_exists( 'cs_fixes_status' ) ) {
			$status = cs_fixes_status();
			echo '<h2>Theme fixes</h2><table class="widefat striped"><thead><tr><th>Fix</th><th>Status</th></tr></thead><tbody>';
			foreach ( $status as $name => $s ) {
				printf( '<tr><td>%s</td><td>%s</td></tr>', esc_html( $name ), esc_html( $s ) );
			}
			echo '</tbody></table>';
		}
		echo '</div>';
	}
}
