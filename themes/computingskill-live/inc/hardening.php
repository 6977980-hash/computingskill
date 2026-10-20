<?php
/**
 * Site hardening (E2 audit, Oct 2026).
 *
 * - XML-RPC: off in PHP (no methods, no RSD link, no pingback header) and
 *   blocked at the web server, so `system.multicall` password guessing and
 *   pingback reflection never reach WordPress (SEC-01).
 * - Security headers on every response, not just theme-rendered pages:
 *   login, wp-admin, REST and static files too (SEC-02).
 * - readme/license/changelog files that show plugin and core versions are
 *   denied; the feed generator tag is removed (WP-01).
 * - Versioned CSS/JS, fonts and images are cached by browsers for a year
 *   instead of 7 days (PERF-06). CSS/JS URLs carry ?ver= so they change on
 *   every theme version; the font files have none, so rename a font file
 *   if it ever changes.
 * - Comments are off: the site has none and no comments.php (WP-05).
 *
 * The server rules go in a marked block of the site's root .htaccess
 * (# BEGIN ComputingSkill Hardening). After writing it, the site loads its
 * homepage and stylesheet; anything but a 200 puts the old .htaccess back
 * at once. The block is re-checked daily and rewritten when CS_HARDEN_REV
 * changes. To remove it by hand, delete the lines between the two markers.
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CS_HARDEN_REV', '2026-10-20-e2' );
define( 'CS_HARDEN_MARKER', 'ComputingSkill Hardening' );

/* ------------------------------------------------------------------ */
/* PHP side (always on)                                                */
/* ------------------------------------------------------------------ */

add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter( 'xmlrpc_methods', '__return_empty_array' );
add_filter( 'pings_open', '__return_false' );
add_filter( 'comments_open', '__return_false' );
add_filter( 'the_generator', '__return_empty_string' );
remove_action( 'wp_head', 'rsd_link' );

function cs_harden_no_pingback_header( $headers ) {
	unset( $headers['X-Pingback'] );
	return $headers;
}
add_filter( 'wp_headers', 'cs_harden_no_pingback_header' );

/**
 * True once the .htaccess block is in place and the self-test saw its
 * headers on a static file; cs_security_headers() then leaves those
 * headers to the server instead of sending them twice.
 */
function cs_harden_server_headers() {
	$state = get_option( 'cs_harden', array() );
	return is_array( $state ) && ! empty( $state['headers'] ) && 'ok' === ( $state['status'] ?? '' );
}

/* ------------------------------------------------------------------ */
/* Server side (.htaccess block)                                       */
/* ------------------------------------------------------------------ */

/**
 * @return string[] Lines between the markers.
 */
function cs_harden_rules() {
	$deny_on  = array( '<IfModule mod_authz_core.c>', "\tRequire all denied", '</IfModule>', '<IfModule !mod_authz_core.c>', "\tOrder allow,deny", "\tDeny from all", '</IfModule>' );
	$deny     = static function ( $open, $close ) use ( $deny_on ) {
		return array_merge( array( $open ), array_map( static function ( $l ) {
			return "\t" . $l;
		}, $deny_on ), array( $close ) );
	};
	$year     = '"access plus 1 year"';
	$expires  = array();
	foreach ( array( 'text/css', 'application/javascript', 'text/javascript', 'application/x-javascript', 'font/woff2', 'application/font-woff2', 'font/woff', 'image/webp', 'image/png', 'image/jpeg', 'image/gif', 'image/svg+xml', 'image/avif', 'image/x-icon' ) as $type ) {
		$expires[] = "\tExpiresByType " . $type . ' ' . $year;
	}

	return array_merge(
		array( '# Added by the ComputingSkill Live theme (inc/hardening.php). Safe to delete.' ),
		$deny( '<Files "xmlrpc.php">', '</Files>' ),
		$deny( '<FilesMatch "^(readme|license|licence|changelog)\.(txt|html|md)$">', '</FilesMatch>' ),
		array(
			'<IfModule mod_headers.c>',
			"\tHeader always set Strict-Transport-Security \"max-age=31536000\"",
			"\tHeader always set X-Content-Type-Options \"nosniff\"",
			"\tHeader always set Referrer-Policy \"strict-origin-when-cross-origin\"",
			"\tHeader always set Permissions-Policy \"camera=(), microphone=(), geolocation=(), payment=(), usb=()\"",
			'</IfModule>',
			'<IfModule mod_expires.c>',
			"\tExpiresActive On",
		),
		$expires,
		array( '</IfModule>' )
	);
}

function cs_harden_htaccess_file() {
	return ABSPATH . '.htaccess';
}

/**
 * Loads a URL from the site itself.
 *
 * @return array{code:int,headers:array}
 */
function cs_harden_probe( $url, $method = 'GET' ) {
	$res = wp_remote_request(
		$url,
		array(
			'method'      => $method,
			'timeout'     => 15,
			'redirection' => 0,
			'sslverify'   => apply_filters( 'https_local_ssl_verify', false ),
			'headers'     => array( 'Cache-Control' => 'no-cache' ),
		)
	);
	if ( is_wp_error( $res ) ) {
		return array( 'code' => 0, 'headers' => array(), 'error' => $res->get_error_message() );
	}
	return array( 'code' => (int) wp_remote_retrieve_response_code( $res ), 'headers' => wp_remote_retrieve_headers( $res ) );
}

/**
 * Writes the block, tests the site, and puts the old file back on any
 * failure. Runs once per CS_HARDEN_REV and again if a daily check finds
 * the block gone.
 */
function cs_harden_apply() {
	$file   = cs_harden_htaccess_file();
	$state  = array( 'rev' => CS_HARDEN_REV, 'checked' => time(), 'headers' => false );
	$exists = file_exists( $file );
	$before = $exists ? (string) file_get_contents( $file ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions

	if ( 'apache' !== cs_harden_server_kind() ) {
		$state['status'] = 'no-htaccess-server';
	} elseif ( ( $exists && ! wp_is_writable( $file ) ) || ( ! $exists && ! wp_is_writable( ABSPATH ) ) ) {
		$state['status'] = 'not-writable';
	} else {
		require_once ABSPATH . 'wp-admin/includes/misc.php';
		if ( ! insert_with_markers( $file, CS_HARDEN_MARKER, cs_harden_rules() ) ) {
			$state['status'] = 'write-failed';
		} else {
			$bust = 'cs_harden=' . wp_rand();
			$home = cs_harden_probe( home_url( '/?' . $bust ) );
			$css  = cs_harden_probe( get_template_directory_uri() . '/style.css?' . $bust );
			$xml  = cs_harden_probe( site_url( '/xmlrpc.php' ), 'POST' );

			$state['home']   = $home['code'];
			$state['css']    = $css['code'];
			$state['xmlrpc'] = $xml['code'];
			if ( 200 !== $home['code'] || 200 !== $css['code'] ) {
				// Something in the block broke the site: restore the old file.
				if ( $exists ) {
					file_put_contents( $file, $before, LOCK_EX ); // phpcs:ignore WordPress.WP.AlternativeFunctions
				} else {
					@unlink( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
				}
				$state['status'] = 'reverted';
				$state['error']  = $home['error'] ?? ( $css['error'] ?? '' );
			} else {
				$state['status']  = 'ok';
				$state['headers'] = isset( $css['headers']['x-content-type-options'] ) && isset( $css['headers']['strict-transport-security'] );
			}
		}
	}

	update_option( 'cs_harden', $state, true );
	if ( 'ok' !== $state['status'] ) {
		error_log( '[ComputingSkill] .htaccess hardening not applied: ' . $state['status'] ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
	}
	return $state;
}

/**
 * Apache and LiteSpeed read .htaccess; nginx does not.
 */
function cs_harden_server_kind() {
	$soft = isset( $_SERVER['SERVER_SOFTWARE'] ) ? strtolower( (string) $_SERVER['SERVER_SOFTWARE'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	return ( false !== strpos( $soft, 'apache' ) || false !== strpos( $soft, 'litespeed' ) ) ? 'apache' : 'other';
}

/**
 * Is the job due? New rev, or the daily check finds the block missing.
 */
function cs_harden_due() {
	$state = get_option( 'cs_harden', array() );
	if ( ! is_array( $state ) || ( $state['rev'] ?? '' ) !== CS_HARDEN_REV ) {
		return true;
	}
	if ( time() - (int) ( $state['checked'] ?? 0 ) < DAY_IN_SECONDS ) {
		return false;
	}
	if ( 'ok' !== ( $state['status'] ?? '' ) ) {
		// A failed or reverted attempt is not retried every day; a new
		// CS_HARDEN_REV tries again.
		$state['checked'] = time();
		update_option( 'cs_harden', $state, true );
		return false;
	}
	$text = (string) @file_get_contents( cs_harden_htaccess_file() ); // phpcs:ignore WordPress.WP.AlternativeFunctions, WordPress.PHP.NoSilencedErrors
	if ( false !== strpos( $text, '# BEGIN ' . CS_HARDEN_MARKER ) ) {
		$state['checked'] = time();
		update_option( 'cs_harden', $state, true );
		return false;
	}
	return true;
}

/**
 * Runs after the response is sent, so the self-test's three loopback
 * requests never slow a visitor down.
 */
function cs_harden_maybe_run() {
	if ( wp_installing() || ( defined( 'WP_CLI' ) && WP_CLI ) || isset( $_GET['cs_harden'] ) || ! cs_harden_due() ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}
	if ( ! add_option( 'cs_harden_lock', time(), '', false ) ) {
		if ( time() - (int) get_option( 'cs_harden_lock' ) > 300 ) {
			delete_option( 'cs_harden_lock' );
		}
		return;
	}
	if ( function_exists( 'fastcgi_finish_request' ) ) {
		fastcgi_finish_request();
	} elseif ( function_exists( 'litespeed_finish_request' ) ) {
		litespeed_finish_request();
	}
	cs_harden_apply();
	delete_option( 'cs_harden_lock' );
}
// Last shutdown action, so cache-plugin headers and buffers are done first.
add_action( 'shutdown', 'cs_harden_maybe_run', 999999 );
