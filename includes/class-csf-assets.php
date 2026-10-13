<?php
/**
 * Enqueues the front-end CSS/JS fixes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CSF_Assets {

	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ), 999 );
	}

	public static function enqueue() {
		self::style( 'csf-fixes', 'assets/css/fixes.css' );
		self::script( 'csf-fixes', 'assets/js/fixes.js' );
	}

	private static function style( $handle, $path ) {
		$file = CSF_DIR . $path;
		if ( file_exists( $file ) && filesize( $file ) > 0 ) {
			wp_enqueue_style( $handle, CSF_URL . $path, array(), (string) filemtime( $file ) );
		}
	}

	private static function script( $handle, $path ) {
		$file = CSF_DIR . $path;
		if ( file_exists( $file ) && filesize( $file ) > 0 ) {
			wp_enqueue_script( $handle, CSF_URL . $path, array(), (string) filemtime( $file ), true );
		}
	}
}
