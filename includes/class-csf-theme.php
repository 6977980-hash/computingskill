<?php
/**
 * Serves the ComputingSkill theme from this plugin, so theme fixes pushed
 * to GitHub go live through the same Hostinger auto-deploy.
 *
 * The bundled theme appears in Appearance > Themes as "ComputingSkill Live".
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CSF_Theme {

	const SLUG = 'computingskill-live';

	public static function init() {
		register_theme_directory( CSF_DIR . 'themes' );
		add_action( 'after_switch_theme', array( __CLASS__, 'copy_settings' ), 1, 2 );
	}

	/**
	 * When switching to the Live theme for the first time, carry over the
	 * previous theme's menus, logo and Customizer settings.
	 *
	 * @param string   $old_name  Previous theme name.
	 * @param WP_Theme $old_theme Previous theme.
	 */
	public static function copy_settings( $old_name, $old_theme = null ) {
		if ( self::SLUG !== get_stylesheet() || ! $old_theme instanceof WP_Theme ) {
			return;
		}
		if ( false !== get_option( 'theme_mods_' . self::SLUG ) ) {
			return;
		}
		$old_mods = get_option( 'theme_mods_' . $old_theme->get_stylesheet() );
		if ( is_array( $old_mods ) ) {
			update_option( 'theme_mods_' . self::SLUG, $old_mods );
		}
	}
}
