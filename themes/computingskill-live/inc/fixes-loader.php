<?php
/**
 * Loads every fix file from the theme's /fixes folder and enqueues the
 * CSS/JS fix files.
 *
 * Each fix is loaded on its own inside try/catch, so one broken fix is
 * logged and skipped instead of taking the whole site down.
 * Prefix a fix file with "_" to disable it.
 * Emergency off switch: define( 'CS_DISABLE_FIXES', true ); in wp-config.php.
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return array<string,string> fix file => 'loaded' | 'disabled' | error message
 */
function cs_fixes_status( $set = null ) {
	static $status = array();
	if ( is_array( $set ) ) {
		$status = $set;
	}
	return $status;
}

function cs_load_fixes() {
	if ( defined( 'CS_DISABLE_FIXES' ) && CS_DISABLE_FIXES ) {
		return;
	}

	$files = glob( CS_THEME_DIR . '/fixes/*.php' );
	if ( ! $files ) {
		return;
	}
	sort( $files );

	$status = array();
	foreach ( $files as $file ) {
		$name = basename( $file );
		if ( 0 === strpos( $name, '_' ) ) {
			$status[ $name ] = 'disabled';
			continue;
		}
		try {
			include_once $file;
			$status[ $name ] = 'loaded';
		} catch ( \Throwable $e ) {
			$status[ $name ] = $e->getMessage();
			error_log( sprintf( '[ComputingSkill fixes] %s failed: %s (line %d)', $name, $e->getMessage(), $e->getLine() ) );
		}
	}
	cs_fixes_status( $status );
}
cs_load_fixes();

function cs_enqueue_fix_assets() {
	foreach ( array( 'css' => 'assets/css/fixes.css', 'js' => 'assets/js/fixes.js' ) as $type => $path ) {
		$file = CS_THEME_DIR . '/' . $path;
		if ( ! file_exists( $file ) || filesize( $file ) < 100 ) {
			continue; // Skip the empty placeholder files.
		}
		if ( 'css' === $type ) {
			wp_enqueue_style( 'cs-fixes', CS_THEME_URI . '/' . $path, array(), (string) filemtime( $file ) );
		} else {
			wp_enqueue_script( 'cs-fixes', CS_THEME_URI . '/' . $path, array(), (string) filemtime( $file ), true );
		}
	}
}
add_action( 'wp_enqueue_scripts', 'cs_enqueue_fix_assets', 999 );
