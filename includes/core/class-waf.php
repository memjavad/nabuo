<?php
/**
 * Naboo Web Application Firewall (WAF)
 * Filters incoming requests for malicious patterns.
 *
 * @package ArabPsychology\NabooDatabase\Core
 */

namespace ArabPsychology\NabooDatabase\Core;

/**
 * Class WAF
 */
class WAF {

	/**
	 * Malicious patterns to block.
	 * @var array
	 */
	private $patterns = array(
		'sqli' => '/(union\s+select|insert\s+into|update\s+.*\s+set|delete\s+from|drop\s+table|information_schema|benchmark\(|pg_sleep\()/i',
		'xss'  => '/(<script|script>|alert\(|onerror=|onload=|eval\(|javascript:|base64_decode)/i',
		'lfi'  => '/(\.\.\/|\.\.\\\\|etc\/passwd|proc\/self|php:\/\/filter)/i',
		'rce'  => '/(system\(|passthru\(|exec\(|shell_exec\(|popen\(|proc_open\()/i',
		// SSRF: attempts to access internal/cloud metadata endpoints
		'ssrf' => '/(169\.254\.169\.254|localhost|127\.\d+\.\d+\.\d+|::1|0\.0\.0\.0|file:\/\/|dict:\/\/|gopher:\/\/|ftp:\/\/[^"\']+@)/i',
		// XXE: XML external entity injection
		'xxe'  => '/<!ENTITY\s|SYSTEM\s+"(file|php|expect|http|https|ftp):\/\//i',
	);

	/**
	 * Cookie keys whose values are known-safe WP internals — skip XSS scan on them.
	 * @var array
	 */
	private $safe_cookie_prefixes = array(
		'wordpress_',
		'wp-settings-',
		'wordpress_logged_in',
		'wordpress_sec',
		'comment_author_',
		'PHPSESSID',
	);

	/**
	 * Constructor
	 */
	public function __construct() {
		// Initialization
	}

	/**
	 * Run the firewall check.
	 */
	public function run_firewall() {
		// Skip for CLI or cron
		if ( PHP_SAPI === 'cli' || ( \defined('DOING_CRON') && DOING_CRON ) ) {
			return;
		}

		$current_ip = $this->get_ip();

		// Skip internal loopbacks and Cloudflare-proxied internal requests
		// (Site Health checks, local requests, WP-Cron)
		if ( $this->is_internal_request() ) {
			return;
		}

		// Check if IP is temporarily banned
		if ( \get_transient( 'naboo_waf_banned_' . \md5( $current_ip ) ) ) {
			\status_header( 403 );
			\wp_die( 
				'<h1>' . \esc_html__( 'Access Denied', 'naboodatabase' ) . '</h1>' .
				'<p>' . \esc_html__( 'Your IP address has been temporarily banned due to repeated suspicious activity.', 'naboodatabase' ) . '</p>',
				\__( '403 Forbidden', 'naboodatabase' ),
				array( 'response' => 403 )
			);
		}

		// Pass HEAD and OPTIONS through — used by CORS preflights and search-engine crawlers.
		$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
		if ( \in_array( $method, array( 'HEAD', 'OPTIONS' ), true ) ) {
			return;
		}

		// Skip for logged-in administrators (safety measure)
		if ( \function_exists( 'current_user_can' ) && \current_user_can( 'manage_options' ) ) {
			return;
		}

		$options = \get_option( 'naboodatabase_security_options', array() );
		if ( empty( $options['enable_waf'] ) ) {
			return;
		}

		// Skip for whitelisted IPs
		if ( ! empty( $options['waf_whitelist'] ) ) {
			$whitelist = array_map( 'trim', \explode( "\n", $options['waf_whitelist'] ) );
			if ( \in_array( $current_ip, $whitelist, true ) ) {
				return;
			}
		}

		// Check GET and POST with all patterns (including SSRF/XXE).
		$this->check_input( $_GET, 'GET', $this->patterns );
		$this->check_input( $_POST, 'POST', $this->patterns );

		// Check COOKIE with a restricted set — skip XSS on known-safe WP cookies
		// to prevent false positives from the session/auth cookie values.
		$cookie_patterns = $this->patterns;
		unset( $cookie_patterns['ssrf'] );
		unset( $cookie_patterns['xxe'] );
		$this->check_input( $_COOKIE, 'COOKIE', $cookie_patterns, true );
	}

	/**
	 * Check input array for malicious patterns.
	 *
	 * @param array  $input          Superglobal array.
	 * @param string $method         Label for logging.
	 * @param array  $patterns       Pattern set to run.
	 * @param bool   $is_cookie      Whether we're scanning cookies (enables safe-prefix skip).
	 */
	private function check_input( $input, $method, $patterns, $is_cookie = false ) {
		if ( empty( $input ) ) {
			return;
		}

		foreach ( $input as $key => $value ) {
			if ( is_array( $value ) ) {
				$this->check_input( $value, $method, $patterns, $is_cookie );
				continue;
			}

			// Skip known-safe WP cookie keys for the XSS pattern.
			if ( $is_cookie ) {
				foreach ( $this->safe_cookie_prefixes as $prefix ) {
					if ( \strpos( (string) $key, $prefix ) === 0 ) {
						// Only skip XSS check but still scan for SQLi/LFI/RCE.
						$patterns_to_run = $patterns;
						unset( $patterns_to_run['xss'] );
						foreach ( $patterns_to_run as $type => $pattern ) {
							if ( \preg_match( $pattern, $value ) ) {
								$this->block_request( $type, $method, $key, $value );
							}
						}
						continue 2; // Skip the full loop below.
					}
				}

				// URL-decode cookie values before scanning (encoded payloads).
				$value = \urldecode( $value );
			}

			foreach ( $patterns as $type => $pattern ) {
				if ( \preg_match( $pattern, $value ) ) {
					$this->block_request( $type, $method, $key, $value );
				}
			}
		}
	}

	/**
	 * Block the malicious request.
	 */
	private function block_request( $type, $method, $key, $value ) {
		$ip = $this->get_ip();
		
		// Log the event
		$logger = new Security_Logger();
		$logger->log( 
			'waf_block', 
			\sprintf( \__( 'WAF blocked a %s attack in %s parameter "%s".', 'naboodatabase' ), \strtoupper($type), $method, $key ), 
			'danger', 
			array( 'type' => $type, 'method' => $method, 'parameter' => $key, 'value' => $value ) 
		);

		// IP Block Rate Limiting
		$block_key = 'naboo_waf_blocks_' . \md5( $ip );
		$blocks    = (int) \get_transient( $block_key );
		$blocks++;
		
		if ( $blocks >= 5 ) {
			// Ban for 1 hour
			\set_transient( 'naboo_waf_banned_' . \md5( $ip ), true, HOUR_IN_SECONDS );
			// Reset block count
			\delete_transient( $block_key );
			
			$logger->log( 
				'waf_ip_ban', 
				\sprintf( \__( 'IP %s has been temporarily banned for repeated WAF violations.', 'naboodatabase' ), $ip ), 
				'danger', 
				array( 'ip' => $ip ) 
			);
		} else {
			// Keep count up to 15 mins
			\set_transient( $block_key, $blocks, 15 * MINUTE_IN_SECONDS );
		}

		// Trigger alert if enabled (rate-limited internally in Security::send_critical_alert).
		$security = new Security();
		$security->send_critical_alert( \__( 'WAF Threat Detected', 'naboodatabase' ), \sprintf( \__( 'IP Address %s attempted a %s attack. Currently at %d/5 strikes.', 'naboodatabase' ), $ip, \strtoupper($type), $blocks ) );

		// Set status and die
		\status_header( 403 );
		\wp_die( 
			'<h1>' . \esc_html__( 'Access Denied', 'naboodatabase' ) . '</h1>' .
			'<p>' . \esc_html__( 'Our firewall has blocked your request because it looks potentially harmful. If you believe this is an error, please contact the administrator.', 'naboodatabase' ) . '</p>' .
			'<p><strong>' . \esc_html__( 'Reference ID:', 'naboodatabase' ) . '</strong> ' . \esc_html( \md5($ip . \time()) ) . '</p>',
			\__( '403 Forbidden', 'naboodatabase' ),
			array( 'response' => 403 )
		);
	}

	/**
	 * Helper to get user IP, accounting for Cloudflare and proxies.
	 * Cloudflare provides CF-Connecting-IP (actual client IP).
	 * Other proxies use X-Forwarded-For or similar headers.
	 */
	private function get_ip() {
		// IMPORTANT: Check Cloudflare header FIRST (CF-Connecting-IP is the actual client IP)
		if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
			return \sanitize_text_field( $_SERVER['HTTP_CF_CONNECTING_IP'] );
		}

		$remote = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

		// Only trust forwarded headers if REMOTE_ADDR is a private-range proxy.
		$is_private_proxy = filter_var(
			$remote,
			FILTER_VALIDATE_IP,
			FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
		) === false;

		if ( $is_private_proxy ) {
			$headers = array( 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED' );
			foreach ( $headers as $key ) {
				if ( ! empty( $_SERVER[ $key ] ) ) {
					foreach ( explode( ',', $_SERVER[ $key ] ) as $ip ) {
						$ip = trim( $ip );
						if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) !== false ) {
							return $ip;
						}
					}
				}
			}
		}

		return $remote;
	}

	/**
	 * Check if the current request is from an internal/trusted source.
	 * Accounts for Cloudflare and local loopback requests.
	 * 
	 * @return bool True if request is from internal server, Cloudflare, or localhost.
	 */
	private function is_internal_request() {
		$ip = $this->get_ip();

		// Direct internal loopback
		if ( \in_array( $ip, array( '127.0.0.1', '::1' ), true ) ) {
			return true;
		}

		// Server's own IP (localhost via different binding)
		if ( ! empty( $_SERVER['SERVER_ADDR'] ) && $ip === $_SERVER['SERVER_ADDR'] ) {
			return true;
		}

		// Check if request is from Cloudflare by looking for Cloudflare headers.
		// If CF-Connecting-IP header exists, this is a Cloudflare-proxied request.
		// We trust Cloudflare because it's configured in the plugin.
		if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
			// This is a Cloudflare-proxied request. Trust it if it has CF headers.
			if ( ! empty( $_SERVER['HTTP_CF_RAY'] ) || ! empty( $_SERVER['HTTP_CF_WORKER'] ) ) {
				return true;
			}
		}

		return false;
	}
}
