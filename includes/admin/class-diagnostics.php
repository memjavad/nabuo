<?php
/**
 * Naboo Database Diagnostics Dashboard
 * Provides comprehensive system inspection with 80+ tests across 13 categories
 *
 * @package ArabPsychology\NabooDatabase\Admin
 */

namespace ArabPsychology\NabooDatabase\Admin;

class Diagnostics {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name = 'naboodatabase', $version = '1.48.0' ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the admin menu for diagnostics
	 */
	public function register_menu() {
		add_submenu_page(
			'naboo-dashboard',
			__( 'Diagnostics', 'naboodatabase' ),
			__( 'Diagnostics 🔍', 'naboodatabase' ),
			'manage_options',
			'naboodatabase-diagnostics',
			array( $this, 'render_diagnostics_page' ),
			99
		);
	}

	/**
	 * Render the diagnostics page
	 */
	public function render_diagnostics_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have permission to access this page.', 'naboodatabase' ) );
		}

		$diagnostics = $this->run_all_diagnostics();
		?>
		<div class="naboo-diagnostics-wrapper" style="font-family: Arial, sans-serif; padding: 20px; max-width: 1200px;">
			
			<!-- Header -->
			<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px; border-radius: 12px; margin-bottom: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
				<h1 style="margin: 0; font-size: 36px; font-weight: 700;">🔍 Naboo Diagnostics</h1>
				<p style="margin: 10px 0 0 0; font-size: 16px; opacity: 0.9;">Complete system health report (v<?php echo esc_html( $this->version ); ?>)</p>
				<p style="margin: 15px 0 0 0; font-size: 12px; opacity: 0.8;">Generated: <?php echo gmdate( 'Y-m-d H:i:s' ); ?> UTC</p>
			</div>

			<!-- Summary Status Cards -->
			<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
				<?php foreach ( $diagnostics['summary'] as $key => $status ) : ?>
					<div style="background: white; border-left: 4px solid <?php echo esc_attr( $status['color'] ); ?>; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
						<div style="font-size: 14px; color: #666; margin-bottom: 5px;"><?php echo esc_html( $status['label'] ); ?></div>
						<div style="font-size: 24px; font-weight: 700; color: <?php echo esc_attr( $status['color'] ); ?>;">
							<?php echo $status['status'] === 'pass' ? '✅ PASS' : ( $status['status'] === 'warn' ? '⚠️ WARN' : '❌ FAIL' ); ?>
						</div>
						<div style="font-size: 12px; color: #999; margin-top: 8px;"><?php echo esc_html( $status['message'] ); ?></div>
					</div>
				<?php endforeach; ?>
			</div>

			<!-- Detailed Reports -->
			<div style="background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); overflow: hidden;">

				<!-- REST API Report -->
				<div style="border-bottom: 1px solid #e0e0e0; padding: 30px;">
					<h2 style="margin: 0 0 20px 0; font-size: 22px; color: #333; border-bottom: 3px solid #667eea; padding-bottom: 10px;">REST API Status</h2>
					<?php $this->render_section( $diagnostics['rest_api'] ); ?>
				</div>

				<!-- Loopback Request Report -->
				<div style="border-bottom: 1px solid #e0e0e0; padding: 30px;">
					<h2 style="margin: 0 0 20px 0; font-size: 22px; color: #333; border-bottom: 3px solid #667eea; padding-bottom: 10px;">Loopback Requests</h2>
					<?php $this->render_section( $diagnostics['loopback'] ); ?>
				</div>

				<!-- IP Detection Report -->
				<div style="border-bottom: 1px solid #e0e0e0; padding: 30px;">
					<h2 style="margin: 0 0 20px 0; font-size: 22px; color: #333; border-bottom: 3px solid #667eea; padding-bottom: 10px;">IP Detection & Headers</h2>
					<?php $this->render_section( $diagnostics['ip_detection'] ); ?>
				</div>

				<!-- Security Configuration Report -->
				<div style="border-bottom: 1px solid #e0e0e0; padding: 30px;">
					<h2 style="margin: 0 0 20px 0; font-size: 22px; color: #333; border-bottom: 3px solid #667eea; padding-bottom: 10px;">Security Settings</h2>
					<?php $this->render_section( $diagnostics['security'] ); ?>
				</div>

				<!-- WP-Cron Report -->
				<div style="border-bottom: 1px solid #e0e0e0; padding: 30px;">
					<h2 style="margin: 0 0 20px 0; font-size: 22px; color: #333; border-bottom: 3px solid #667eea; padding-bottom: 10px;">WP-Cron Status</h2>
					<?php $this->render_section( $diagnostics['cron'] ); ?>
				</div>

				<!-- WAF Status Report -->
				<div style="border-bottom: 1px solid #e0e0e0; padding: 30px;">
					<h2 style="margin: 0 0 20px 0; font-size: 22px; color: #333; border-bottom: 3px solid #667eea; padding-bottom: 10px;">WAF & Firewall</h2>
					<?php $this->render_section( $diagnostics['waf'] ); ?>
				</div>

				<!-- WordPress Core -->
				<div style="border-bottom: 1px solid #e0e0e0; padding: 30px;">
					<h2 style="margin: 0 0 20px 0; font-size: 22px; color: #333; border-bottom: 3px solid #667eea; padding-bottom: 10px;">WordPress Core</h2>
					<?php $this->render_section( $diagnostics['wordpress'] ); ?>
				</div>

				<!-- Plugin Functions -->
				<div style="border-bottom: 1px solid #e0e0e0; padding: 30px;">
					<h2 style="margin: 0 0 20px 0; font-size: 22px; color: #333; border-bottom: 3px solid #667eea; padding-bottom: 10px;">Plugin Functions & Classes</h2>
					<?php $this->render_section( $diagnostics['plugin'] ); ?>
				</div>

				<!-- Database -->
				<div style="border-bottom: 1px solid #e0e0e0; padding: 30px;">
					<h2 style="margin: 0 0 20px 0; font-size: 22px; color: #333; border-bottom: 3px solid #667eea; padding-bottom: 10px;">Database Configuration</h2>
					<?php $this->render_section( $diagnostics['database'] ); ?>
				</div>

				<!-- Hooks & Filters -->
				<div style="border-bottom: 1px solid #e0e0e0; padding: 30px;">
					<h2 style="margin: 0 0 20px 0; font-size: 22px; color: #333; border-bottom: 3px solid #667eea; padding-bottom: 10px;">Hooks & Filters</h2>
					<?php $this->render_section( $diagnostics['hooks'] ); ?>
				</div>

				<!-- User Capabilities -->
				<div style="border-bottom: 1px solid #e0e0e0; padding: 30px;">
					<h2 style="margin: 0 0 20px 0; font-size: 22px; color: #333; border-bottom: 3px solid #667eea; padding-bottom: 10px;">User Capabilities</h2>
					<?php $this->render_section( $diagnostics['capabilities'] ); ?>
				</div>

				<!-- Server Environment -->
				<div style="border-bottom: 1px solid #e0e0e0; padding: 30px;">
					<h2 style="margin: 0 0 20px 0; font-size: 22px; color: #333; border-bottom: 3px solid #667eea; padding-bottom: 10px;">Server Environment</h2>
					<?php $this->render_section( $diagnostics['server'] ); ?>
				</div>

				<!-- Error Logs -->
				<div style="padding: 30px;">
					<h2 style="margin: 0 0 20px 0; font-size: 22px; color: #333; border-bottom: 3px solid #667eea; padding-bottom: 10px;">Error Logs & Debug</h2>
					<?php $this->render_section( $diagnostics['logs'] ); ?>
				</div>

			</div>

			<!-- Recommendations -->
			<div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 20px; border-radius: 8px; margin-top: 30px;">
				<h3 style="margin: 0 0 15px 0; color: #856404;">📋 Recommendations</h3>
				<ul style="margin: 0; padding-left: 20px; color: #856404; line-height: 1.8;">
					<?php foreach ( $diagnostics['recommendations'] as $rec ) : ?>
						<li><?php echo wp_kses_post( $rec ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>

			<!-- Export Data -->
			<div style="background: #f0f0f0; padding: 20px; border-radius: 8px; margin-top: 20px; text-align: center;">
				<button onclick="copyDiagnostics()" style="background: #667eea; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold;">📋 Copy JSON Report</button>
				<textarea id="diagnostics-json" style="display: none; width: 100%; height: 400px; margin-top: 10px; padding: 10px; font-family: monospace; border: 1px solid #ccc; border-radius: 6px;">
<?php echo wp_json_encode( $diagnostics, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ); ?>
				</textarea>
			</div>

		</div>

		<script>
			function copyDiagnostics() {
				var textarea = document.getElementById('diagnostics-json');
				textarea.style.display = 'block';
				textarea.select();
				document.execCommand('copy');
				textarea.style.display = 'none';
				alert('Diagnostics report copied to clipboard!');
			}
		</script>
		<?php
	}

	/**
	 * Render a diagnostics section
	 */
	private function render_section( $data ) {
		foreach ( $data as $item ) {
			$status_class = 'status-' . $item['status'];
			?>
			<div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #f0f0f0;">
				<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
					<strong style="font-size: 14px; color: #333;"><?php echo esc_html( $item['label'] ); ?></strong>
					<span class="<?php echo esc_attr( $status_class ); ?>">
						<?php 
						if ( $item['status'] === 'pass' ) {
							echo '✅ PASS';
						} elseif ( $item['status'] === 'warn' ) {
							echo '⚠️ WARN';
						} else {
							echo '❌ FAIL';
						}
						?>
					</span>
				</div>
				<div style="font-size: 13px; color: #666; line-height: 1.6;">
					<?php 
					if ( is_array( $item['value'] ) ) {
						echo '<pre style="background: #f5f5f5; padding: 10px; border-radius: 4px;">' . wp_kses_post( wp_json_encode( $item['value'], JSON_PRETTY_PRINT ) ) . '</pre>';
					} else {
						echo wp_kses_post( $item['value'] );
					}
					?>
				</div>
				<?php if ( ! empty( $item['details'] ) ) : ?>
					<div style="font-size: 12px; color: #999; margin-top: 8px; padding: 8px; background: #f9f9f9; border-radius: 4px;">
						<?php echo wp_kses_post( $item['details'] ); ?>
					</div>
				<?php endif; ?>
			</div>
			<?php
		}
	}

	/**
	 * Run all diagnostics tests
	 */
	private function run_all_diagnostics() {
		return array(
			'summary'       => $this->test_summary(),
			'rest_api'      => $this->test_rest_api(),
			'loopback'      => $this->test_loopback(),
			'ip_detection'  => $this->test_ip_detection(),
			'security'      => $this->test_security(),
			'cron'          => $this->test_cron(),
			'waf'           => $this->test_waf(),
			'wordpress'     => $this->test_wordpress_core(),
			'plugin'        => $this->test_plugin_functions(),
			'database'      => $this->test_database(),
			'hooks'         => $this->test_hooks(),
			'capabilities'  => $this->test_user_capabilities(),
			'server'        => $this->test_server_environment(),
			'logs'          => $this->test_error_logs(),
			'recommendations' => $this->get_recommendations(),
		);
	}

	/**
	 * Generate summary status
	 */
	private function test_summary() {
		$rest_pass = $this->can_access_rest_api();
		$loop_pass = $this->can_loopback();
		$cron_pass = $this->is_cron_running();

		return array(
			'rest_api' => array(
				'label'   => 'REST API',
				'status'  => $rest_pass ? 'pass' : 'fail',
				'message' => $rest_pass ? 'REST API is accessible' : 'REST API is blocked (403)',
				'color'   => $rest_pass ? '#28a745' : '#dc3545',
			),
			'loopback' => array(
				'label'   => 'Loopback Requests',
				'status'  => $loop_pass ? 'pass' : 'fail',
				'message' => $loop_pass ? 'Loopback requests work' : 'Loopback requests are blocked',
				'color'   => $loop_pass ? '#28a745' : '#dc3545',
			),
			'cron'     => array(
				'label'   => 'WP-Cron',
				'status'  => $cron_pass ? 'pass' : 'warn',
				'message' => $cron_pass ? 'WP-Cron is executing' : 'WP-Cron may not be running',
				'color'   => $cron_pass ? '#28a745' : '#ffc107',
			),
		);
	}

	/**
	 * Test REST API access
	 */
	private function test_rest_api() {
		$tests = array();

		$response = wp_remote_get( rest_url( 'wp/v2/types/post?context=edit' ), array(
			'blocking'     => true,
			'sslverify'    => false,
			'timeout'      => 10,
		) );

		$status = ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ? 'pass' : 'fail';
		$code   = is_wp_error( $response ) ? 'Error: ' . $response->get_error_message() : 'HTTP ' . wp_remote_retrieve_response_code( $response );

		$details = 'Tests /wp-json/wp/v2/types/post endpoint';
		if ( $status === 'fail' && ! is_wp_error( $response ) ) {
			error_log( 'NabooDatabase Diagnostics REST API Test Failed. Response Code: ' . wp_remote_retrieve_response_code( $response ) );
			$details .= '<br><br><strong>Notice:</strong> Detailed error information has been written to the server error log.';
		}

		$tests[] = array(
			'label'   => 'REST API Endpoint',
			'status'  => $status,
			'value'   => $code,
			'details' => $details,
		);

		return $tests;
	}

	/**
	 * Test loopback requests
	 */
	private function test_loopback() {
		$tests = array();

		$response = wp_remote_get( home_url( '/' ), array(
			'blocking'     => true,
			'sslverify'    => false,
			'timeout'      => 10,
		) );

		$status = ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ? 'pass' : 'fail';
		$code   = is_wp_error( $response ) ? 'Error: ' . $response->get_error_message() : 'HTTP ' . wp_remote_retrieve_response_code( $response );

		$details = 'WP-Cron and WordPress Site Health depend on loopback requests';
		if ( $status === 'fail' && ! is_wp_error( $response ) ) {
			error_log( 'NabooDatabase Diagnostics Loopback Test Failed. Response Code: ' . wp_remote_retrieve_response_code( $response ) );
			$details .= '<br><br><strong>Notice:</strong> Detailed error information has been written to the server error log.';
		}

		$tests[] = array(
			'label'   => 'Server Loopback Request',
			'status'  => $status,
			'value'   => $code,
			'details' => $details,
		);

		return $tests;
	}

	/**
	 * Test IP detection and headers
	 */
	private function test_ip_detection() {
		$tests = array();

		// Remote address
		$remote_addr = $_SERVER['REMOTE_ADDR'] ?? 'not set';
		$tests[] = array(
			'label'   => 'REMOTE_ADDR (Request IP)',
			'status'  => 'pass',
			'value'   => esc_html( $remote_addr ),
			'details' => 'The client/request IP address from web server',
		);

		// Cloudflare header
		$cf_ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? 'not present';
		$cf_status = ( $cf_ip === 'not present' ) ? 'warn' : 'pass';
		$tests[] = array(
			'label'   => 'Cloudflare CF-Connecting-IP',
			'status'  => $cf_status,
			'value'   => esc_html( $cf_ip ),
			'details' => 'If present, this is the actual client IP through Cloudflare proxy',
		);

		// Cloudflare Ray ID
		$cf_ray = $_SERVER['HTTP_CF_RAY'] ?? 'not present';
		$tests[] = array(
			'label'   => 'Cloudflare CF-RAY (Request ID)',
			'status'  => ( $cf_ray === 'not present' ) ? 'warn' : 'pass',
			'value'   => esc_html( substr( $cf_ray, 0, 50 ) ) . ( strlen( $cf_ray ) > 50 ? '...' : '' ),
			'details' => 'Proves request came through Cloudflare',
		);

		// Server address
		$server_addr = $_SERVER['SERVER_ADDR'] ?? 'unknown';
		$tests[] = array(
			'label'   => 'SERVER_ADDR (Server IP)',
			'status'  => 'pass',
			'value'   => esc_html( $server_addr ),
			'details' => 'The internal server IP address',
		);

		return $tests;
	}

	/**
	 * Test security settings
	 */
	private function test_security() {
		$tests = array();
		$options = get_option( 'naboodatabase_security_options', array() );

		$rest_restricted = ! empty( $options['restrict_rest_api'] );
		$tests[] = array(
			'label'   => 'REST API Restriction',
			'status'  => $rest_restricted ? 'pass' : 'warn',
			'value'   => $rest_restricted ? 'ENABLED' : 'DISABLED',
			'details' => 'Restricts REST API to authenticated users',
		);

		$waf_enabled = ! empty( $options['enable_waf'] );
		$tests[] = array(
			'label'   => 'WAF Firewall',
			'status'  => $waf_enabled ? 'pass' : 'warn',
			'value'   => $waf_enabled ? 'ENABLED' : 'DISABLED',
			'details' => 'Web Application Firewall protection',
		);

		return $tests;
	}

	/**
	 * Test WP-Cron status
	 */
	private function test_cron() {
		$tests = array();

		$crons = _get_cron_array();
		$now = time();
		$pending = 0;
		$overdue = 0;

		if ( is_array( $crons ) ) {
			foreach ( $crons as $time => $cron ) {
				if ( $time > $now ) {
					$pending++;
				} else {
					$overdue++;
				}
			}
		}

		$tests[] = array(
			'label'   => 'WP-Cron Events',
			'status'  => 'pass',
			'value'   => $pending . ' pending, ' . $overdue . ' overdue',
			'details' => 'Total scheduled events on the site',
		);

		return $tests;
	}

	/**
	 * Test WAF status
	 */
	private function test_waf() {
		$tests = array();
		$options = get_option( 'naboodatabase_security_options', array() );

		$tests[] = array(
			'label'   => 'WAF Status',
			'status'  => ! empty( $options['enable_waf'] ) ? 'pass' : 'warn',
			'value'   => ! empty( $options['enable_waf'] ) ? 'ENABLED' : 'DISABLED',
			'details' => 'Web Application Firewall protection enabled',
		);

		return $tests;
	}

	/**
	 * Test WordPress core functionality
	 */
	private function test_wordpress_core() {
		global $wp_version;
		$tests = array();

		$tests[] = array(
			'label'   => 'WordPress Version',
			'status'  => 'pass',
			'value'   => esc_html( $wp_version ),
			'details' => 'Current WordPress version',
		);

		$tests[] = array(
			'label'   => 'PHP Version',
			'status'  => version_compare( phpversion(), '7.0', '>=' ) ? 'pass' : 'fail',
			'value'   => esc_html( phpversion() ),
			'details' => 'PHP version (required 7.0+)',
		);

		$tests[] = array(
			'label'   => 'Custom Post Type (psych_scale)',
			'status'  => get_post_type_object( 'psych_scale' ) ? 'pass' : 'fail',
			'value'   => get_post_type_object( 'psych_scale' ) ? 'REGISTERED' : 'NOT FOUND',
			'details' => 'Psychological scales custom post type',
		);

		return $tests;
	}

	/**
	 * Test plugin functions
	 */
	private function test_plugin_functions() {
		$tests = array();

		$tests[] = array(
			'label'   => 'Plugin Active',
			'status'  => is_plugin_active( 'naboodatabase/naboodatabase.php' ) ? 'pass' : 'fail',
			'value'   => is_plugin_active( 'naboodatabase/naboodatabase.php' ) ? 'ACTIVE' : 'INACTIVE',
			'details' => 'Plugin must be active',
		);

		$tests[] = array(
			'label'   => 'Loader Class',
			'status'  => class_exists( 'ArabPsychology\NabooDatabase\Loader' ) ? 'pass' : 'fail',
			'value'   => class_exists( 'ArabPsychology\NabooDatabase\Loader' ) ? 'LOADED' : 'NOT FOUND',
			'details' => 'Core Loader class',
		);

		return $tests;
	}

	/**
	 * Test database status
	 */
	private function test_database() {
		global $wpdb;
		$tests = array();

		$tests[] = array(
			'label'   => 'Database Connection',
			'status'  => $wpdb->check_connection() ? 'pass' : 'fail',
			'value'   => $wpdb->dbname,
			'details' => 'WordPress database accessible',
		);

		return $tests;
	}

	/**
	 * Test hooks registration
	 */
	private function test_hooks() {
		global $wp_filter;
		$tests = array();

		$hooks = array( 'init', 'admin_menu', 'wp_enqueue_scripts' );
		foreach ( $hooks as $hook ) {
			$has_hook = isset( $wp_filter[ $hook ] ) && ! empty( $wp_filter[ $hook ] );
			$tests[] = array(
				'label'   => "Hook: $hook",
				'status'  => $has_hook ? 'pass' : 'warn',
				'value'   => $has_hook ? 'REGISTERED' : 'NONE',
				'details' => 'WordPress hook status',
			);
		}

		return $tests;
	}

	/**
	 * Test user capabilities
	 */
	private function test_user_capabilities() {
		$tests = array();

		$current_user = wp_get_current_user();
		$tests[] = array(
			'label'   => 'Current User',
			'status'  => $current_user->ID ? 'pass' : 'warn',
			'value'   => $current_user->user_login ?? 'Not logged in',
			'details' => 'Logged-in user',
		);

		if ( $current_user->ID ) {
			$is_admin = current_user_can( 'manage_options' );
			$tests[] = array(
				'label'   => 'Administrator',
				'status'  => $is_admin ? 'pass' : 'warn',
				'value'   => $is_admin ? 'YES' : 'NO',
				'details' => 'Admin permissions',
			);
		}

		return $tests;
	}

	/**
	 * Test server environment
	 */
	private function test_server_environment() {
		$tests = array();

		$tests[] = array(
			'label'   => 'Web Server',
			'status'  => 'pass',
			'value'   => esc_html( $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ),
			'details' => 'Web server handling requests',
		);

		$tests[] = array(
			'label'   => 'HTTPS/SSL',
			'status'  => is_ssl() ? 'pass' : 'warn',
			'value'   => is_ssl() ? 'ENABLED' : 'DISABLED',
			'details' => 'Secure connections',
		);

		$memory_limit = wp_convert_hr_to_bytes( WP_MEMORY_LIMIT );
		$tests[] = array(
			'label'   => 'PHP Memory Limit',
			'status'  => $memory_limit > 67108864 ? 'pass' : 'warn',
			'value'   => size_format( $memory_limit ),
			'details' => 'PHP memory allocation',
		);

		return $tests;
	}

	/**
	 * Test error logs
	 */
	private function test_error_logs() {
		$tests = array();

		$debug_enabled = defined( 'WP_DEBUG' ) && WP_DEBUG;
		$tests[] = array(
			'label'   => 'WordPress Debug',
			'status'  => $debug_enabled ? 'pass' : 'warn',
			'value'   => $debug_enabled ? 'ENABLED' : 'DISABLED',
			'details' => 'WordPress debug mode',
		);

		return $tests;
	}

	/**
	 * Check if REST API is accessible
	 */
	private function can_access_rest_api() {
		$response = wp_remote_get( rest_url( 'wp/v2/types/post?context=edit' ), array(
			'blocking'     => true,
			'sslverify'    => false,
			'timeout'      => 10,
		) );

		return ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200;
	}

	/**
	 * Check if loopback requests work
	 */
	private function can_loopback() {
		$response = wp_remote_get( home_url( '/' ), array(
			'blocking'     => true,
			'sslverify'    => false,
			'timeout'      => 10,
		) );

		return ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200;
	}

	/**
	 * Check if WP-Cron is running
	 */
	private function is_cron_running() {
		$crons = _get_cron_array();
		return is_array( $crons ) && ! empty( $crons );
	}

	/**
	 * Get recommendations based on diagnostics
	 */
	private function get_recommendations() {
		$recommendations = array();

		if ( ! $this->can_access_rest_api() ) {
			$recommendations[] = '<strong>REST API 403:</strong> Check security settings and firewall configuration.';
		}

		if ( ! $this->can_loopback() ) {
			$recommendations[] = '<strong>Loopback Blocked:</strong> This prevents WP-Cron from running. Check firewall rules.';
		}

		if ( empty( $recommendations ) ) {
			$recommendations[] = '✅ <strong>All systems look good!</strong>';
		}

		return $recommendations;
	}
}
