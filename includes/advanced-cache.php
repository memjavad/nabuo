<?php
/**
 * Naboo Page Cache Drop-in
 * A lightning-fast disk-based static HTML page cache for WordPress.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access not permitted.' );
}

// Only run on GET requests
if ( $_SERVER['REQUEST_METHOD'] !== 'GET' ) {
	return;
}

// Detect nocache query param
if (isset($_GET['nocache'])) {
	return;
}

// Do not cache for logged in users or commenters
$has_cookie = false;
foreach ( $_COOKIE as $key => $value ) {
	if ( preg_match( '/^(wp-saving-post|wordpress_logged_in_|comment_author_|wp-settings-|wp-postpass_)/', $key ) ) {
		$has_cookie = true;
		break;
	}
}

if ( $has_cookie ) {
	return;
}

// Load dynamic TTL from config file if exists
$cache_ttl = 3600; // 1 hour default
if ( file_exists( WP_CONTENT_DIR . '/naboo-page-cache-config.php' ) ) {
	include_once WP_CONTENT_DIR . '/naboo-page-cache-config.php';
	if ( defined( 'NABOO_PAGE_CACHE_TTL' ) ) {
		$cache_ttl = (int) NABOO_PAGE_CACHE_TTL;
	}
}

// Define Cache Directory
$cache_dir = WP_CONTENT_DIR . '/naboo_page_cache/';

// Get current URL path
$host = isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : '';
$uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
$path = parse_url( $uri, PHP_URL_PATH );

if ( ! $host || ! $path ) {
	return;
}

// Avoid caching common dynamic/system paths
$exclude_paths = array( 'wp-admin', 'wp-login.php', 'wp-json', 'wp-cron.php', 'xmlrpc.php' );
foreach ( $exclude_paths as $exclude ) {
	if ( strpos( $path, $exclude ) !== false ) {
		return;
	}
}

// Ensure trailing slash for directory structure
$path = rtrim( $path, '/' ) . '/';
$cache_file = $cache_dir . $host . $path . 'index.html';

// Check if cache file exists and is valid
if ( file_exists( $cache_file ) ) {
	if ( filemtime( $cache_file ) > ( time() - $cache_ttl ) ) {
		// Serve the cache!
		header( 'X-Naboo-Cache: HIT' );
		header( 'X-Naboo-Can-Cache: 1' );
		header( 'Cache-Control: public, max-age=' . $cache_ttl . ', s-maxage=' . $cache_ttl );
		header( 'Content-Type: text/html; charset=UTF-8' );
		readfile( $cache_file );
		exit;
	}
}

// If we got here, we need to generate or serve cache.
// Add signaling headers for the Cloudflare Edge Worker to pick up.
header( 'X-Naboo-Can-Cache: 1' );
header( 'Cache-Control: public, max-age=' . $cache_ttl . ', s-maxage=' . $cache_ttl );

// LITESPEED NATIVE INTEGRATION (NO LSCACHE PLUGIN REQUIRED)
// If we are running on a LiteSpeed web server, we can instruct the server daemon itself to cache the page.
// The LiteSpeed web server will intercept these headers and cache the HTML output at the server level,
// entirely bypassing PHP and the disk for all subsequent requests.
$is_litespeed = isset( $_SERVER['SERVER_SOFTWARE'] ) && strpos( $_SERVER['SERVER_SOFTWARE'], 'LiteSpeed' ) !== false;

if ( $is_litespeed ) {
	// Instruct LiteSpeed to cache this page
	header( 'X-LiteSpeed-Cache-Control: public, max-age=' . $cache_ttl );
	
	// Tag the cache so we can purge it selectively later
	header( 'X-LiteSpeed-Tag: naboo_pages' );
	
	// Let LiteSpeed handle the actual caching; we don't need to write to disk.
	// We just let WordPress continue to render the page, and LiteSpeed will grab the output.
	return;
}

// Start output buffering to capture the page content for non-LiteSpeed servers
ob_start( function( $buffer ) use ( $cache_file, $cache_dir, $host, $path ) {
	// Only cache if the request was successful and returned HTML
	if ( ! http_response_code() || http_response_code() === 200 ) {
		$headers = headers_list();
		$is_html = true; // Assume HTML unless proven otherwise
		foreach ( $headers as $header ) {
			if ( stripos( $header, 'Content-Type:' ) === 0 && stripos( $header, 'text/html' ) === false ) {
				$is_html = false;
				break;
			}
		}

		if ( $is_html && strlen( $buffer ) > 255 ) {
			$file_dir = dirname( $cache_file );
			if ( ! is_dir( $file_dir ) ) {
				@mkdir( $file_dir, 0755, true );
			}
			
			// Add an HTML comment to the end of the buffer
			$buffer_with_stamp = $buffer . "\n<!-- Cached by Naboo Page Cache @ " . gmdate('Y-m-d H:i:s') . " GMT -->";
			@file_put_contents( $cache_file, $buffer_with_stamp, LOCK_EX );
		}
	}
	return $buffer;
} );
