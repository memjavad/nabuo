<?php
/**
 * API Rate Limiting & Throttling
 *
 * @package ArabPsychology\NabooDatabase\Admin
 */

namespace ArabPsychology\NabooDatabase\Admin;

/**
 * API_Rate_Limiting class - Rate limit and throttle API requests.
 */
class API_Rate_Limiting {

	/**
	 * Plugin name
	 *
	 * @var string
	 */
	private $plugin_name;

	/**
	 * Plugin version
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Constructor
	 *
	 * @param string $plugin_name The plugin name.
	 * @param string $version     The plugin version.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->create_table();
	}

	/**
	 * Create rate limit table
	 */
	private function create_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_rate_limits';

		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === null ) {
			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE $table_name (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				identifier varchar(255),
				endpoint varchar(255),
				request_count int DEFAULT 0,
				first_request datetime,
				last_request datetime,
				is_blocked int DEFAULT 0,
				reset_at datetime,
				PRIMARY KEY (id),
				KEY identifier (identifier),
				KEY endpoint (endpoint),
				KEY reset_at (reset_at)
			) $charset_collate;";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
		}
	}

	/**
	 * Register REST API endpoints
	 */
	public function register_endpoints() {
		register_rest_route(
			'apa/v1',
			'/rate-limit/stats',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_rate_limit_stats' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/rate-limit/config',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_rate_limit_config' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/rate-limit/config',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'update_rate_limit_config' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/rate-limit/unblock',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'unblock_identifier' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/rate-limit/blocked',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_blocked_identifiers' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}

	/**
	 * Get rate limit statistics
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_rate_limit_stats( $request ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_rate_limits';

		$stats = array(
			'total_tracked'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" ),
			'currently_blocked' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE is_blocked = 1" ),
			'total_requests'    => (int) $wpdb->get_var( "SELECT SUM(request_count) FROM $table_name" ),
			'avg_requests'      => (float) $wpdb->get_var( "SELECT AVG(request_count) FROM $table_name" ),
		);

		// Top endpoints by requests
		$top_endpoints = $wpdb->get_results(
			"SELECT endpoint, SUM(request_count) as requests, COUNT(*) as unique_users 
			FROM $table_name 
			GROUP BY endpoint 
			ORDER BY requests DESC 
			LIMIT 10"
		);

		$stats['top_endpoints'] = $top_endpoints;

		return new \WP_REST_Response( $stats, 200 );
	}

	/**
	 * Get rate limit configuration
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_rate_limit_config( $request ) {
		$config = array(
			'authenticated_limit'   => get_option( 'naboo_rate_limit_authenticated', 1000 ),
			'authenticated_window'  => get_option( 'naboo_rate_limit_window_auth', 3600 ),
			'anonymous_limit'       => get_option( 'naboo_rate_limit_anonymous', 100 ),
			'anonymous_window'      => get_option( 'naboo_rate_limit_window_anon', 3600 ),
			'block_duration'        => get_option( 'naboo_rate_limit_block_duration', 3600 ),
			'enabled'               => (bool) get_option( 'naboo_rate_limiting_enabled', true ),
		);

		return new \WP_REST_Response( $config, 200 );
	}

	/**
	 * Update rate limit configuration
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function update_rate_limit_config( $request ) {
		$auth_limit = (int) $request->get_param( 'authenticated_limit' ) ?? 1000;
		$auth_window = (int) $request->get_param( 'authenticated_window' ) ?? 3600;
		$anon_limit = (int) $request->get_param( 'anonymous_limit' ) ?? 100;
		$anon_window = (int) $request->get_param( 'anonymous_window' ) ?? 3600;
		$block_duration = (int) $request->get_param( 'block_duration' ) ?? 3600;
		$enabled = (bool) $request->get_param( 'enabled' ) ?? true;

		update_option( 'naboo_rate_limit_authenticated', $auth_limit );
		update_option( 'naboo_rate_limit_window_auth', $auth_window );
		update_option( 'naboo_rate_limit_anonymous', $anon_limit );
		update_option( 'naboo_rate_limit_window_anon', $anon_window );
		update_option( 'naboo_rate_limit_block_duration', $block_duration );
		update_option( 'naboo_rate_limiting_enabled', $enabled );

		return new \WP_REST_Response(
			array( 'message' => 'Rate limiting configuration updated' ),
			200
		);
	}

	/**
	 * Unblock identifier
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function unblock_identifier( $request ) {
		$identifier = sanitize_text_field( $request->get_param( 'identifier' ) );

		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_rate_limits';

		$wpdb->update(
			$table_name,
			array( 'is_blocked' => 0 ),
			array( 'identifier' => $identifier ),
			array( '%d' ),
			array( '%s' )
		);

		return new \WP_REST_Response( array( 'message' => 'Identifier unblocked' ), 200 );
	}

	/**
	 * Get blocked identifiers
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_blocked_identifiers( $request ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_rate_limits';

		$blocked = $wpdb->get_results(
			"SELECT identifier, endpoint, request_count, reset_at FROM $table_name 
			WHERE is_blocked = 1 
			ORDER BY reset_at DESC 
			LIMIT 50"
		);

		return new \WP_REST_Response( array( 'blocked' => $blocked ), 200 );
	}

	/**
	 * Check and enforce rate limit
	 *
	 * @param string $identifier The identifier (IP or user ID).
	 * @param string $endpoint   The API endpoint.
	 * @return bool|string True if allowed, error message if blocked.
	 */
	public function check_rate_limit( $identifier, $endpoint ) {
		if ( ! get_option( 'naboo_rate_limiting_enabled', true ) ) {
			return true;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_rate_limits';

		// Check if blocked
		$blocked = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT reset_at FROM $table_name WHERE identifier = %s AND is_blocked = 1",
				$identifier
			)
		);

		if ( $blocked && strtotime( $blocked->reset_at ) > time() ) {
			return 'Rate limit exceeded. Try again later.';
		}

		// Get current limit
		$is_authenticated = is_user_logged_in();
		$limit = $is_authenticated 
			? get_option( 'naboo_rate_limit_authenticated', 1000 )
			: get_option( 'naboo_rate_limit_anonymous', 100 );
		$window = $is_authenticated 
			? get_option( 'naboo_rate_limit_window_auth', 3600 )
			: get_option( 'naboo_rate_limit_window_anon', 3600 );

		// Check request count
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT request_count, first_request FROM $table_name 
				WHERE identifier = %s AND endpoint = %s AND first_request > DATE_SUB(NOW(), INTERVAL %d SECOND)",
				$identifier,
				$endpoint,
				$window
			)
		);

		if ( $row ) {
			if ( $row->request_count >= $limit ) {
				// Block user
				$reset_time = date( 'Y-m-d H:i:s', time() + get_option( 'naboo_rate_limit_block_duration', 3600 ) );
				$wpdb->update(
					$table_name,
					array( 'is_blocked' => 1, 'reset_at' => $reset_time ),
					array( 'identifier' => $identifier, 'endpoint' => $endpoint ),
					array( '%d', '%s' ),
					array( '%s', '%s' )
				);

				return 'Rate limit exceeded.';
			}

			// Increment count
			$wpdb->update(
				$table_name,
				array( 'request_count' => $row->request_count + 1, 'last_request' => current_time( 'mysql' ) ),
				array( 'identifier' => $identifier, 'endpoint' => $endpoint ),
				array( '%d', '%s' ),
				array( '%s', '%s' )
			);
		} else {
			// Create new entry
			$wpdb->insert(
				$table_name,
				array(
					'identifier'     => $identifier,
					'endpoint'       => $endpoint,
					'request_count'  => 1,
					'first_request'  => current_time( 'mysql' ),
					'last_request'   => current_time( 'mysql' ),
					'reset_at'       => date( 'Y-m-d H:i:s', time() + $window ),
				),
				array( '%s', '%s', '%d', '%s', '%s', '%s' )
			);
		}

		return true;
	}

	/**
	 * Get identifier (IP or user ID)
	 *
	 * @return string
	 */
	public function get_identifier() {
		if ( is_user_logged_in() ) {
			return 'user_' . get_current_user_id();
		}

		// Get client IP
		$ip = '';
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = $_SERVER['REMOTE_ADDR'];
		}

		return 'ip_' . sanitize_text_field( $ip );
	}

	/**
	 * Register Admin Menu
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'naboo-dashboard',
			__( 'API Rate Limiting', 'naboodatabase' ),
			__( '🚦 API Limits', 'naboodatabase' ),
			'manage_options',
			'naboo-api-limits',
			array( $this, 'render_admin_page' ),
			7
		);
	}

	/**
	 * Enqueue Admin Scripts
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( false === strpos( $hook, 'naboo-api-limits' ) ) {
			return;
		}

		wp_enqueue_script(
			$this->plugin_name . '-api-limits',
			plugin_dir_url( __FILE__ ) . 'js/api-limits-admin.js',
			array( 'jquery', 'wp-api-fetch' ),
			$this->version,
			true
		);

		wp_localize_script(
			$this->plugin_name . '-api-limits',
			'apaApiLimits',
			array(
				'apiUrl' => rest_url( 'apa/v1/rate-limit' ),
				'nonce'  => wp_create_nonce( 'wp_rest' ),
			)
		);

		wp_enqueue_style(
			$this->plugin_name . '-api-limits',
			plugin_dir_url( __FILE__ ) . 'css/api-limits-admin.css',
			array(),
			$this->version
		);
	}

	/**
	 * Render Admin Page UI
	 */
	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'naboodatabase' ) );
		}
		?>
		<div class="wrap naboo-admin-wrap">
			<h1><?php esc_html_e( 'API Rate Limiting & Traffic Control', 'naboodatabase' ); ?></h1>
			<p><?php esc_html_e( 'Monitor API traffic and configure request thresholds to prevent abuse.', 'naboodatabase' ); ?></p>

			<div class="naboo-api-grid">
				<div class="naboo-api-main">
					<div class="naboo-api-stats-row">
						<div class="naboo-api-stat-card">
							<span class="naboo-api-label"><?php esc_html_e( 'Blocked Entities', 'naboodatabase' ); ?></span>
							<span class="naboo-api-value" id="naboo-blocked-count">0</span>
						</div>
						<div class="naboo-api-stat-card">
							<span class="naboo-api-label"><?php esc_html_e( 'Total API Requests', 'naboodatabase' ); ?></span>
							<span class="naboo-api-value" id="naboo-total-requests">0</span>
						</div>
					</div>

					<div class="naboo-api-card">
						<h2><?php esc_html_e( 'Active Blocks', 'naboodatabase' ); ?></h2>
						<table class="wp-list-table widefat fixed striped">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Identifier', 'naboodatabase' ); ?></th>
									<th><?php esc_html_e( 'Endpoint', 'naboodatabase' ); ?></th>
									<th><?php esc_html_e( 'Requests', 'naboodatabase' ); ?></th>
									<th><?php esc_html_e( 'Reset At', 'naboodatabase' ); ?></th>
									<th><?php esc_html_e( 'Action', 'naboodatabase' ); ?></th>
								</tr>
							</thead>
							<tbody id="naboo-blocked-list">
								<!-- AJAX results -->
							</tbody>
						</table>
					</div>

					<div class="naboo-api-card">
						<h2><?php esc_html_e( 'Top Endpoints', 'naboodatabase' ); ?></h2>
						<div id="naboo-top-endpoints-list">
							<!-- AJAX results -->
						</div>
					</div>
				</div>

				<div class="naboo-api-side">
					<div class="naboo-api-card">
						<h2><?php esc_html_e( 'Configuration', 'naboodatabase' ); ?></h2>
						<form id="naboo-api-config-form">
							<div class="naboo-config-group">
								<label><?php esc_html_e( 'Enable Rate Limiting', 'naboodatabase' ); ?></label>
								<input type="checkbox" id="naboo-api-enabled" name="enabled" value="1">
							</div>
							
							<h3><?php esc_html_e( 'Authenticated Users', 'naboodatabase' ); ?></h3>
							<div class="naboo-config-row">
								<label><?php esc_html_e( 'Limit (requests)', 'naboodatabase' ); ?></label>
								<input type="number" name="authenticated_limit" id="naboo-api-auth-limit">
							</div>
							<div class="naboo-config-row">
								<label><?php esc_html_e( 'Window (seconds)', 'naboodatabase' ); ?></label>
								<input type="number" name="authenticated_window" id="naboo-api-auth-window">
							</div>

							<h3><?php esc_html_e( 'Anonymous Users', 'naboodatabase' ); ?></h3>
							<div class="naboo-config-row">
								<label><?php esc_html_e( 'Limit (requests)', 'naboodatabase' ); ?></label>
								<input type="number" name="anonymous_limit" id="naboo-api-anon-limit">
							</div>
							<div class="naboo-config-row">
								<label><?php esc_html_e( 'Window (seconds)', 'naboodatabase' ); ?></label>
								<input type="number" name="anonymous_window" id="naboo-api-anon-window">
							</div>

							<div class="naboo-config-row">
								<label><?php esc_html_e( 'Block Duration (s)', 'naboodatabase' ); ?></label>
								<input type="number" name="block_duration" id="naboo-api-block-duration">
							</div>

							<div class="naboo-config-actions">
								<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Configuration', 'naboodatabase' ); ?></button>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}
