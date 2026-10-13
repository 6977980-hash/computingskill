<?php
/**
 * Copies the bundled ComputingSkill Live theme from this plugin into
 * wp-content/themes/computingskill-live whenever a new version is deployed.
 *
 * The theme then lives in the normal themes folder, so every fix stays on
 * the site even if this plugin is deactivated or deleted.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CSF_Theme {

	const SLUG       = 'computingskill-live';
	const OPTION     = 'csf_theme_sync';
	const CHECK_TIME = 'csf_theme_sync_checked';

	public static function init() {
		register_activation_hook( CSF_FILE, array( __CLASS__, 'sync' ) );
		add_action( 'init', array( __CLASS__, 'maybe_sync' ), 1 );
		add_action( 'after_switch_theme', array( __CLASS__, 'copy_settings' ), 1, 2 );
	}

	public static function source() {
		return CSF_DIR . 'themes/' . self::SLUG;
	}

	public static function target() {
		return get_theme_root() . '/' . self::SLUG;
	}

	/**
	 * Re-checks at most once a minute whether the deployed theme changed.
	 */
	public static function maybe_sync() {
		if ( get_transient( self::CHECK_TIME ) ) {
			return;
		}
		set_transient( self::CHECK_TIME, 1, MINUTE_IN_SECONDS );

		$state = get_option( self::OPTION, array() );
		if ( empty( $state['hash'] ) || self::hash() !== $state['hash'] || ! is_dir( self::target() ) ) {
			self::sync();
		}
	}

	/**
	 * Fingerprint of the bundled theme: every file's path, size and content hash.
	 */
	public static function hash() {
		$parts = array();
		foreach ( self::files( self::source() ) as $rel ) {
			$parts[] = $rel . ':' . md5_file( self::source() . '/' . $rel );
		}
		return md5( implode( '|', $parts ) );
	}

	/**
	 * Mirrors the bundled theme into wp-content/themes.
	 *
	 * @return bool
	 */
	public static function sync() {
		$src  = self::source();
		$dest = self::target();
		if ( ! is_dir( $src ) ) {
			return false;
		}

		$ok    = true;
		$files = self::files( $src );
		foreach ( $files as $rel ) {
			$to = $dest . '/' . $rel;
			if ( ! is_dir( dirname( $to ) ) && ! wp_mkdir_p( dirname( $to ) ) ) {
				$ok = false;
				continue;
			}
			if ( ! file_exists( $to ) || md5_file( $to ) !== md5_file( $src . '/' . $rel ) ) {
				$ok = copy( $src . '/' . $rel, $to ) && $ok;
			}
		}

		// Remove files that were deleted from the repo.
		if ( is_dir( $dest ) ) {
			foreach ( array_diff( self::files( $dest ), $files ) as $rel ) {
				@unlink( $dest . '/' . $rel );
			}
		}

		if ( function_exists( 'opcache_reset' ) ) {
			@opcache_reset();
		}
		wp_clean_themes_cache();

		update_option(
			self::OPTION,
			array(
				'hash' => $ok ? self::hash() : '',
				'time' => time(),
				'ok'   => $ok,
			),
			false
		);
		if ( ! $ok ) {
			error_log( '[ComputingSkill Fixes] Theme sync to ' . $dest . ' failed (file permissions?).' );
		}
		return $ok;
	}

	/**
	 * @return string[] File paths relative to $dir.
	 */
	private static function files( $dir ) {
		$out = array();
		if ( ! is_dir( $dir ) ) {
			return $out;
		}
		$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ) );
		foreach ( $it as $file ) {
			if ( $file->isFile() ) {
				$out[] = ltrim( str_replace( '\\', '/', substr( $file->getPathname(), strlen( $dir ) ) ), '/' );
			}
		}
		sort( $out );
		return $out;
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
