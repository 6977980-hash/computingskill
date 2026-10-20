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
	const LOCK       = 'csf_theme_sync_lock';

	public static function init() {
		register_activation_hook( CSF_FILE, array( __CLASS__, 'sync' ) );
		// plugins_loaded runs before WordPress loads the theme, so a broken
		// live copy is replaced before it can fatal the request that would
		// repair it (E2 audit DEP-01).
		add_action( 'plugins_loaded', array( __CLASS__, 'maybe_sync' ), 1 );
		add_action( 'after_switch_theme', array( __CLASS__, 'copy_settings' ), 1, 2 );
	}

	public static function source() {
		return CSF_DIR . 'themes/' . self::SLUG;
	}

	public static function target() {
		return get_theme_root() . '/' . self::SLUG;
	}

	/**
	 * Re-checks at most once a minute whether the deployed theme changed,
	 * or whether the live copy no longer matches it (a broken or partial
	 * copy, or a hand edit), and re-syncs if so.
	 */
	public static function maybe_sync() {
		if ( get_transient( self::CHECK_TIME ) ) {
			return;
		}
		set_transient( self::CHECK_TIME, 1, MINUTE_IN_SECONDS );

		$state = get_option( self::OPTION, array() );
		if ( empty( $state['hash'] ) || self::hash() !== $state['hash'] || self::hash( self::manifest( self::target() ) ) !== $state['hash'] ) {
			self::sync();
		}
	}

	/**
	 * Every file's relative path and content hash.
	 *
	 * @return array<string,string>
	 */
	public static function manifest( $dir = null ) {
		$dir = null === $dir ? self::source() : $dir;
		$out = array();
		foreach ( self::files( $dir ) as $rel ) {
			$out[ $rel ] = (string) md5_file( $dir . '/' . $rel );
		}
		return $out;
	}

	/**
	 * Fingerprint of the bundled theme: every file's path and content hash.
	 */
	public static function hash( $manifest = null ) {
		$manifest = null === $manifest ? self::manifest() : $manifest;
		$parts    = array();
		foreach ( $manifest as $rel => $md5 ) {
			$parts[] = $rel . ':' . $md5;
		}
		return md5( implode( '|', $parts ) );
	}

	/**
	 * Mirrors the bundled theme into wp-content/themes.
	 *
	 * The new version is first copied into a hidden staging folder and
	 * checked file by file, so a copy that fails (disk quota, permissions,
	 * time limit) never touches the live theme. Then each changed file is
	 * moved into place with rename(), which is atomic: a visitor's request
	 * sees the whole old file or the whole new one, never half a file, and
	 * no file is ever missing. New files go in first and functions.php
	 * last, so the loader never requires a file that isn't there yet.
	 * If Hostinger's git checkout is still writing the plugin folder, the
	 * source changes under us: nothing is applied and no hash is stored, so
	 * the next minute tries again (DEP-02).
	 *
	 * A whole-folder swap was tried and dropped: on Linux it needs two
	 * renames, and requests that hit the moment between them failed
	 * (2 of 400 in a 20-deploy load test, against 0 for per-file renames).
	 *
	 * @return bool
	 */
	public static function sync() {
		$src  = self::source();
		$dest = self::target();
		if ( ! is_dir( $src ) ) {
			return false;
		}
		if ( ! add_option( self::LOCK, time(), '', false ) ) {
			if ( time() - (int) get_option( self::LOCK ) < 300 ) {
				return false; // Another request is syncing.
			}
			update_option( self::LOCK, time(), false );
		}

		$before = self::manifest( $src );
		$stage  = get_theme_root() . '/.' . self::SLUG . '-new';
		$note   = '';
		$ok     = isset( $before['functions.php'], $before['style.css'] );
		if ( ! $ok ) {
			$note = 'source incomplete';
		}
		if ( $ok ) {
			self::rmdir( $stage );
			$ok = self::copy_all( $src, $stage, $before );
			if ( ! $ok ) {
				$note = 'copy to staging folder failed';
			}
		}
		// The source must not have changed while we copied it.
		if ( $ok && self::manifest( $src ) !== $before ) {
			$ok   = false;
			$note = 'source changed during copy (deploy still running)';
		}
		if ( $ok ) {
			$ok = is_dir( $dest ) ? self::apply( $stage, $dest, $before ) : @rename( $stage, $dest ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
			if ( ! $ok ) {
				$note = 'moving files into the live theme failed';
			}
		}
		self::rmdir( $stage );

		if ( function_exists( 'opcache_reset' ) ) {
			@opcache_reset(); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		}
		wp_clean_themes_cache();

		update_option(
			self::OPTION,
			array(
				'hash' => $ok ? self::hash( $before ) : '',
				'time' => time(),
				'ok'   => $ok,
				'note' => $note,
			),
			false
		);
		delete_option( self::LOCK );
		if ( ! $ok ) {
			error_log( '[ComputingSkill Fixes] Theme sync to ' . $dest . ' failed: ' . $note . '.' );
		}
		return $ok;
	}

	/**
	 * Copies every file into $to and checks each copy against $manifest.
	 */
	private static function copy_all( $from, $to, $manifest ) {
		foreach ( $manifest as $rel => $md5 ) {
			$file = $to . '/' . $rel;
			if ( ! is_dir( dirname( $file ) ) && ! wp_mkdir_p( dirname( $file ) ) ) {
				return false;
			}
			if ( ! @copy( $from . '/' . $rel, $file ) || md5_file( $file ) !== $md5 ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors
				return false;
			}
		}
		return true;
	}

	/**
	 * Moves the changed files from the staging folder into the live theme
	 * (new files, then changed files, functions.php last), then deletes
	 * live files that are gone from the repo.
	 */
	private static function apply( $stage, $dest, $manifest ) {
		$new = array();
		$changed = array();
		foreach ( $manifest as $rel => $md5 ) {
			if ( 'functions.php' === $rel ) {
				continue;
			}
			if ( ! is_file( $dest . '/' . $rel ) ) {
				$new[] = $rel;
			} elseif ( md5_file( $dest . '/' . $rel ) !== $md5 ) {
				$changed[] = $rel;
			}
		}
		$order = array_merge( $new, $changed );
		if ( ! is_file( $dest . '/functions.php' ) || md5_file( $dest . '/functions.php' ) !== $manifest['functions.php'] ) {
			$order[] = 'functions.php';
		}
		foreach ( $order as $rel ) {
			$file = $dest . '/' . $rel;
			if ( ! is_dir( dirname( $file ) ) && ! wp_mkdir_p( dirname( $file ) ) ) {
				return false;
			}
			if ( ! @rename( $stage . '/' . $rel, $file ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors
				// Same folder tree, so rename should work; if not, copy to a
				// temp file next to the target and rename that.
				$tmp = $file . '.csf-tmp';
				if ( ! @copy( $stage . '/' . $rel, $tmp ) || ! @rename( $tmp, $file ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors
					@unlink( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
					return false;
				}
			}
		}
		foreach ( array_diff( self::files( $dest ), array_keys( $manifest ) ) as $rel ) {
			@unlink( $dest . '/' . $rel ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		}
		return true;
	}

	/**
	 * Deletes the staging folder.
	 */
	private static function rmdir( $dir ) {
		if ( ! is_dir( $dir ) ) {
			return;
		}
		$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
		foreach ( $it as $item ) {
			$item->isDir() ? @rmdir( $item->getPathname() ) : @unlink( $item->getPathname() ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		}
		@rmdir( $dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
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
