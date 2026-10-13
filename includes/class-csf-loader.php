<?php
/**
 * Loads every fix file from the /fixes folder.
 *
 * Each fix is loaded on its own inside try/catch, so one broken fix is
 * logged and skipped instead of taking the whole site down.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CSF_Loader {

	/** @var array<string,string> fix file => 'loaded' | error message */
	private static $status = array();

	public static function init() {
		// Emergency kill switch: define( 'CSF_DISABLE_FIXES', true ); in wp-config.php.
		if ( defined( 'CSF_DISABLE_FIXES' ) && CSF_DISABLE_FIXES ) {
			return;
		}

		$files = glob( CSF_DIR . 'fixes/*.php' );
		if ( ! $files ) {
			return;
		}
		sort( $files );

		foreach ( $files as $file ) {
			$name = basename( $file );

			// Files starting with "_" are disabled.
			if ( 0 === strpos( $name, '_' ) ) {
				self::$status[ $name ] = 'disabled';
				continue;
			}

			try {
				include_once $file;
				self::$status[ $name ] = 'loaded';
			} catch ( \Throwable $e ) {
				self::$status[ $name ] = $e->getMessage();
				error_log( sprintf( '[ComputingSkill Fixes] %s failed: %s (line %d)', $name, $e->getMessage(), $e->getLine() ) );
			}
		}
	}

	public static function status() {
		return self::$status;
	}
}
