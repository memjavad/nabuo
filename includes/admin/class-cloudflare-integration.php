<?php
/**
 * Cloudflare Edge Caching Integration
 *
 * @package ArabPsychology\NabooDatabase\Admin
 */

namespace ArabPsychology\NabooDatabase\Admin;

class Cloudflare_Integration {
	
	private $api_base = 'https://api.cloudflare.com/client/v4/';

	/**
	 * Register actions for automatic cache clearing.
	 */
	public function init_hooks() {
		// Only run if active
		if ( ! $this->is_active() ) {
			return;
		}

		add_action( 'save_post_psych_scale', array( $this, 'trigger_purge_on_save' ), 99, 3 );
		add_action( 'trash_post', array( $this, 'trigger_purge_on_delete' ) );
		add_action( 'delete_post', array( $this, 'trigger_purge_on_delete' ) );
	}

	/**
	 * Purge and re-warm edge cache on post save/update.
	 *
	 * @param int      $post_id
	 * @param \WP_Post $post
	 * @param bool     $update
	 */
	public function trigger_purge_on_save( $post_id, $post, $update ) {
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		$url = get_permalink( $post_id );
		if ( ! $url ) {
			return;
		}

		// Purge the URL from edge cache
		$success = $this->purge_url( $url );

		// If successful and published, fire a warm up request 
		if ( $success && $post->post_status === 'publish' ) {
			$this->warm_edge_cache( $url );
		}
	}

	/**
	 * Purge edge cache when a post is trashed or deleted.
	 *
	 * @param int $post_id
	 */
	public function trigger_purge_on_delete( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post || $post->post_type !== 'psych_scale' ) {
			return;
		}

		$url = get_permalink( $post_id );
		if ( $url ) {
			$this->purge_url( $url );
		}
	}

	/**
	 * Get the Cloudflare settings from the DB.
	 *
	 * @return array
	 */
	private function get_settings() {
		return get_option( 'naboodatabase_performance_options', array() );
	}

	/**
	 * Check if Cloudflare integration is active and properly mapped.
	 *
	 * @return bool
	 */
	public function is_active() {
		$settings = $this->get_settings();
		return ! empty( $settings['cf_enable_integration'] ) && ! empty( $settings['cf_zone_id'] ) && ! empty( $settings['cf_api_token'] );
	}

	private function get_headers() {
		$settings = $this->get_settings();
		$headers  = array(
			'Content-Type' => 'application/json',
		);

		if ( ! empty( $settings['cf_email'] ) ) {
			$headers['X-Auth-Email'] = trim( $settings['cf_email'] );
			$headers['X-Auth-Key']   = trim( $settings['cf_api_token'] );
		} else {
			$headers['Authorization'] = 'Bearer ' . trim( $settings['cf_api_token'] );
		}

		return $headers;
	}

	/**
	 * Purge a specific URL from the Cloudflare cache.
	 *
	 * @param string $url The absolute URL to purge.
	 * @return bool True on success, false on failure.
	 */
	public function purge_url( $url ) {
		if ( ! $this->is_active() ) {
			return false;
		}

		$settings = $this->get_settings();
		$zone_id  = $settings['cf_zone_id'];
		$endpoint = $this->api_base . 'zones/' . $zone_id . '/purge_cache';

		$body = array(
			'files' => array( $url )
		);

		$response = wp_remote_post( $endpoint, array(
			'headers' => $this->get_headers(),
			'body'    => wp_json_encode( $body ),
			'timeout' => 10,
		) );

		if ( is_wp_error( $response ) ) {
			error_log( 'Naboo CF Purge URL Error: ' . $response->get_error_message() );
			return false;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code === 200 ) {
			return true;
		}

		$body = wp_remote_retrieve_body( $response );
		error_log( 'Naboo CF Purge URL Failed: HTTP ' . $code . ' - ' . $body );
		return false;
	}

	/**
	 * Purge the entire Cloudflare cache for the zone.
	 *
	 * @return array Response array [ 'success' => bool, 'message' => string ]
	 */
	public function purge_all() {
		if ( ! $this->is_active() ) {
			return array( 'success' => false, 'message' => 'Cloudflare integration is not configured or disabled.' );
		}

		$settings = $this->get_settings();
		$zone_id  = $settings['cf_zone_id'];
		$endpoint = $this->api_base . 'zones/' . $zone_id . '/purge_cache';

		$body = array(
			'purge_everything' => true
		);

		$response = wp_remote_post( $endpoint, array(
			'headers' => $this->get_headers(),
			'body'    => wp_json_encode( $body ),
			'timeout' => 15,
		) );

		if ( is_wp_error( $response ) ) {
			return array( 'success' => false, 'message' => 'WP HTTP Error: ' . $response->get_error_message() );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code === 200 && ! empty( $body['success'] ) ) {
			return array( 'success' => true, 'message' => 'Zone cache successfully purged.' );
		}

		$error_msg = isset( $body['errors'][0]['message'] ) ? $body['errors'][0]['message'] : 'Unknown Cloudflare API Error.';
		return array( 'success' => false, 'message' => 'Cloudflare API Error: ' . $error_msg );
	}

	/**
	 * Whitelist the server's own public IP address in the Cloudflare Zone Firewall.
	 *
	 * @return array Response array [ 'success' => bool, 'message' => string ]
	 */
	public function whitelist_server_ip() {
		if ( ! $this->is_active() ) {
			return array( 'success' => false, 'message' => 'Cloudflare integration is not configured or disabled.' );
		}

		// Try to determine the public egress IP of the server
		$server_ip = $_SERVER['SERVER_ADDR'] ?? '';
		
		// If it looks like a private IP, fetch the public one
		if ( empty( $server_ip ) || filter_var( $server_ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) === false ) {
			$resp = wp_remote_get( 'http://checkip.amazonaws.com/', array( 'timeout' => 5 ) );
			if ( ! is_wp_error( $resp ) && wp_remote_retrieve_response_code( $resp ) === 200 ) {
				$fetched_ip = trim( wp_remote_retrieve_body( $resp ) );
				if ( filter_var( $fetched_ip, FILTER_VALIDATE_IP ) !== false ) {
					$server_ip = $fetched_ip;
				}
			}
		}

		if ( empty( $server_ip ) || filter_var( $server_ip, FILTER_VALIDATE_IP ) === false ) {
			return array( 'success' => false, 'message' => 'Could not reliably determine the public IP address of the server.' );
		}

		$settings = $this->get_settings();
		$zone_id  = $settings['cf_zone_id'];
		
		// Endpoint for IP Access Rules (Zone level)
		$endpoint = $this->api_base . 'zones/' . $zone_id . '/firewall/access_rules/rules';

		$body = array(
			'mode'  => 'whitelist',
			'configuration' => array(
				'target' => 'ip',
				'value'  => $server_ip
			),
			'notes' => 'Naboo Database: WordPress Server IP (Loopback Bypass)'
		);

		$response = wp_remote_post( $endpoint, array(
			'headers' => $this->get_headers(),
			'body'    => wp_json_encode( $body ),
			'timeout' => 15,
		) );

		if ( is_wp_error( $response ) ) {
			return array( 'success' => false, 'message' => 'WP HTTP Error: ' . $response->get_error_message() );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code === 200 && ! empty( $response_body['success'] ) ) {
			return array( 'success' => true, 'message' => sprintf( 'Server IP (%s) was successfully whitelisted in Cloudflare.', $server_ip ) );
		}

		// 81053 means the rule already exists
		$error_msg = isset( $response_body['errors'][0]['message'] ) ? $response_body['errors'][0]['message'] : 'Unknown Cloudflare API Error.';
		$error_code = isset( $response_body['errors'][0]['code'] ) ? $response_body['errors'][0]['code'] : 0;
		
		if ( $error_code === 81053 ) {
			return array( 'success' => true, 'message' => sprintf( 'Server IP (%s) is already whitelisted in Cloudflare.', $server_ip ) );
		}

		return array( 'success' => false, 'message' => 'Cloudflare API Error: ' . $error_msg );
	}

	/**
	 * Warm the edge cache for a specific URL asynchronously.
	 *
	 * @param string $url The URL to warm up.
	 */
	public function warm_edge_cache( $url ) {
		// Non-blocking request
		wp_remote_get( $url, array(
			'timeout'   => 0.01,
			'blocking'  => false,
			'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
		) );
	}

	/**
	 * Update a specific setting for the zone via API.
	 *
	 * @param string $setting_id The setting identifier (e.g., 'brotli', 'early_hints').
	 * @param mixed  $value      The value to set.
	 * @return bool
	 */
	public function update_zone_setting( $setting_id, $value ) {
		if ( ! $this->is_active() ) {
			return false;
		}

		$settings = $this->get_settings();
		$zone_id  = $settings['cf_zone_id'];
		$endpoint = $this->api_base . 'zones/' . $zone_id . '/settings/' . $setting_id;

		$body = array(
			'value' => $value
		);

		$response = wp_remote_request( $endpoint, array(
			'method'  => 'PATCH',
			'headers' => $this->get_headers(),
			'body'    => wp_json_encode( $body ),
			'timeout' => 10,
		) );

		return ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200;
	}

	/**
	 * Get the Zone Name (domain) from Cloudflare using the Zone ID.
	 *
	 * @return string|false The name of the zone (e.g. example.com), or false on error.
	 */
	public function get_zone_name() {
		if ( ! $this->is_active() ) {
			return false;
		}

		$settings = $this->get_settings();
		$zone_id  = $settings['cf_zone_id'];
		$endpoint = $this->api_base . 'zones/' . $zone_id;

		$response = wp_remote_get( $endpoint, array(
			'headers' => $this->get_headers(),
			'timeout' => 10,
		) );

		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! empty( $body['success'] ) && isset( $body['result']['name'] ) ) {
			return $body['result']['name'];
		}

		return false;
	}

	/**
	 * Upload and deploy a worker script.
	 *
	 * @param string $script_name The name of the worker script.
	 * @param string $script_content The raw JS content of the worker.
	 * @return array Response array [ 'success' => bool, 'message' => string ]
	 */
	public function deploy_worker( $script_name, $script_content ) {
		if ( ! $this->is_active() ) {
			return array( 'success' => false, 'message' => 'Cloudflare integration not configured.' );
		}

		$settings = $this->get_settings();
		
		if ( empty( $settings['cf_account_id'] ) ) {
			return array( 'success' => false, 'message' => 'Cloudflare Account ID is missing. Required for Workers deployment.' );
		}

		$account_id = $settings['cf_account_id'];
		$endpoint   = $this->api_base . 'accounts/' . $account_id . '/workers/scripts/' . rawurlencode( $script_name );

		// We must PUT multipart/form-data or application/javascript
		$headers = $this->get_headers();
		$headers['Content-Type'] = 'application/javascript';

		$response = wp_remote_request( $endpoint, array(
			'method'  => 'PUT',
			'headers' => $headers,
			'body'    => $script_content,
			'timeout' => 20,
		) );

		if ( is_wp_error( $response ) ) {
			return array( 'success' => false, 'message' => 'WP HTTP Error: ' . $response->get_error_message() );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code === 200 && ! empty( $body['success'] ) ) {
			return array( 'success' => true, 'message' => 'Worker uploaded successfully.' );
		}

		$error_msg = isset( $body['errors'][0]['message'] ) ? $body['errors'][0]['message'] : 'Unknown Cloudflare API Error.';
		
		if ( $code === 401 || $code === 403 || strpos( $error_msg, 'authenticate' ) !== false ) {
			$error_msg .= ' (Troubleshoot: If using a Token, ensure it has `Account -> Workers Scripts -> Edit` permissions. If using a Global API Key, ensure you also filled out the Cloudflare Email field in settings).';
		}

		return array( 'success' => false, 'message' => 'Cloudflare API Error: ' . $error_msg );
	}

	/**
	 * Bind a route to a worker.
	 *
	 * @param string $pattern The route pattern (e.g. *example.com/psych_scale/*).
	 * @param string $script_name The script name to bind.
	 * @return array
	 */
	public function bind_worker_route( $pattern, $script_name ) {
		if ( ! $this->is_active() ) {
			return array( 'success' => false, 'message' => 'Cloudflare integration not configured.' );
		}

		$settings = $this->get_settings();
		$zone_id  = $settings['cf_zone_id'];
		
		// 1. Get existing routes to check if it's already mapped
		$routes_endpoint = $this->api_base . 'zones/' . $zone_id . '/workers/routes';
		$routes_response = wp_remote_get( $routes_endpoint, array(
			'headers' => $this->get_headers(),
			'timeout' => 10,
		) );

		if ( ! is_wp_error( $routes_response ) && wp_remote_retrieve_response_code( $routes_response ) === 200 ) {
			$routes_body = json_decode( wp_remote_retrieve_body( $routes_response ), true );
			if ( ! empty( $routes_body['success'] ) && is_array( $routes_body['result'] ) ) {
				foreach ( $routes_body['result'] as $route ) {
					if ( $route['pattern'] === $pattern && $route['script'] === $script_name ) {
						return array( 'success' => true, 'message' => 'Route already binds to the worker.' );
					}
					// If pattern exists but mapped somewhere else, we should theoretically update,
					// but Cloudflare API treats route patterns uniquely.
				}
			}
		}

		// 2. Create the new route mapping
		$payload = array(
			'pattern' => $pattern,
			'script'  => $script_name
		);

		$response = wp_remote_post( $routes_endpoint, array(
			'headers' => $this->get_headers(),
			'body'    => wp_json_encode( $payload ),
			'timeout' => 10,
		) );

		if ( is_wp_error( $response ) ) {
			return array( 'success' => false, 'message' => 'WP HTTP Error: ' . $response->get_error_message() );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		// 409 Conflict often means route pattern already exists to another script...
		// In a production plugin we'd handle PUT updates here.
		if ( $code === 200 && ! empty( $body['success'] ) ) {
			return array( 'success' => true, 'message' => 'Route configured successfully.' );
		}
		
		if ( $code === 409 ) {
			return array( 'success' => true, 'message' => 'Route is already mapped.' );
		}

		$error_msg = isset( $body['errors'][0]['message'] ) ? $body['errors'][0]['message'] : 'Unknown Cloudflare API Error.';
		return array( 'success' => false, 'message' => 'Failed to bind route: ' . $error_msg );
	}

	/**
	 * Create Page Rules for "Cache Everything" with an Exception for Admin.
	 *
	 * @return array
	 */
	public function create_cache_rule() {
		if ( ! $this->is_active() ) {
			return array( 'success' => false, 'message' => 'Cloudflare integration not configured.' );
		}

		$settings = $this->get_settings();
		$zone_id  = $settings['cf_zone_id'];
		$host     = $this->get_zone_name();

		if ( ! $host ) {
			$host = parse_url( site_url(), PHP_URL_HOST );
		}

		$endpoint = $this->api_base . 'zones/' . $zone_id . '/pagerules';
		$headers  = $this->get_headers();

		// RULE 1: BYPASS CACHE FOR ADMIN & LOGIN (Priority 1)
		$bypass_payload = array(
			'targets' => array(
				array(
					'target' => 'url',
					'constraint' => array(
						'operator' => 'matches',
						'value'    => '*' . $host . '/wp-admin*' // Catch /wp-admin and /wp-admin/
					)
				)
			),
			'actions'  => array( array( 'id' => 'cache_level', 'value' => 'bypass' ) ),
			'priority' => 1,
			'status'   => 'active'
		);

		$bypass_response = wp_remote_post( $endpoint, array(
			'headers' => $headers,
			'body'    => wp_json_encode( $bypass_payload ),
			'timeout' => 15,
		) );

		// RULE 2: CACHE EVERYTHING (Priority 2)
		$cache_payload = array(
			'targets' => array(
				array(
					'target' => 'url',
					'constraint' => array(
						'operator' => 'matches',
						'value'    => '*' . $host . '/*'
					)
				)
			),
			'actions' => array(
				array( 'id' => 'cache_level', 'value' => 'cache_everything' ),
				array( 'id' => 'edge_cache_ttl', 'value' => 14400 )
			),
			'priority' => 2,
			'status'   => 'active'
		);

		$cache_response = wp_remote_post( $endpoint, array(
			'headers' => $headers,
			'body'    => wp_json_encode( $cache_payload ),
			'timeout' => 15,
		) );

		// Check responses
		$success_count = 0;
		$errors = array();

		foreach ( array( 'Admin Bypass' => $bypass_response, 'Cache Everything' => $cache_response ) as $name => $resp ) {
			if ( is_wp_error( $resp ) ) {
				$errors[] = "$name: " . $resp->get_error_message();
				continue;
			}
			$code = wp_remote_retrieve_response_code( $resp );
			$body = json_decode( wp_remote_retrieve_body( $resp ), true );

			if ( $code === 200 && ! empty( $body['success'] ) ) {
				$success_count++;
			} else {
				$msg = isset( $body['errors'][0]['message'] ) ? $body['errors'][0]['message'] : 'Unknown error';
				if ( strpos( strtolower( $msg ), 'already exists' ) !== false || strpos( strtolower( $msg ), 'duplicate' ) !== false ) {
					$success_count++; // Treat as success for the final message
				} else {
					$errors[] = "$name: " . $msg;
				}
			}
		}

		if ( $success_count === 2 ) {
			return array( 'success' => true, 'message' => 'Cloudflare Page Rules (Admin Bypass & Cache Everything) configured successfully.' );
		}

		if ( ! empty( $errors ) ) {
			return array( 'success' => false, 'message' => 'Rule integration partially failed: ' . implode( '; ', $errors ) . '. Note: Free Cloudflare plans only allow 3 Page Rules total.' );
		}

		return array( 'success' => true, 'message' => 'Cloudflare Page Rules are up to date.' );
	}

}
