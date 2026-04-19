<?php
/**
 * Email Notifications System
 *
 * @package ArabPsychology\NabooDatabase\Admin
 */

namespace ArabPsychology\NabooDatabase\Admin;

/**
 * Email_Notifications_System class - Comprehensive email notification management.
 */
class Email_Notifications_System {

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
		
		add_action( 'naboo_daily_cron', array( $this, 'purge_old_logs' ) );
	}

	/**
	 * Purge email logs older than 30 days
	 */
	public function purge_old_logs() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_email_logs';
		$wpdb->query( $wpdb->prepare(
			"DELETE FROM {$table_name} WHERE sent_at < %s",
			gmdate( 'Y-m-d H:i:s', strtotime( '-30 days' ) )
		) );
	}

	/**
	 * Create email log table
	 */
	private function create_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_email_logs';

		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === null ) {
			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE $table_name (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				recipient varchar(255),
				subject varchar(255),
				event_type varchar(100),
				status varchar(50),
				sent_at datetime DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY event_type (event_type),
				KEY sent_at (sent_at)
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
			'/notifications/settings',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_settings' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/notifications/settings',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'update_settings' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/notifications/logs',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_email_logs' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/notifications/send-test',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'send_test_email' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}

	/**
	 * Get notification settings
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_settings( $request ) {
		$settings = array(
			'submission_notification'     => get_option( 'naboo_notify_submission', true ),
			'approval_notification'       => get_option( 'naboo_notify_approval', true ),
			'rejection_notification'      => get_option( 'naboo_notify_rejection', true ),
			'comment_notification'        => get_option( 'naboo_notify_comment', true ),
			'rating_notification'         => get_option( 'naboo_notify_rating', true ),
			'daily_digest'                => get_option( 'naboo_daily_digest', true ),
			'admin_email'                 => get_option( 'admin_email' ),
			'notification_from_name'      => get_option( 'naboo_notification_from_name', get_bloginfo( 'name' ) ),
			'notification_from_email'     => get_option( 'naboo_notification_from_email', get_option( 'admin_email' ) ),
		);

		return new \WP_REST_Response( $settings, 200 );
	}

	/**
	 * Update notification settings
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function update_settings( $request ) {
		$settings = $request->get_json_params();

		if ( isset( $settings['submission_notification'] ) ) {
			update_option( 'naboo_notify_submission', (bool) $settings['submission_notification'] );
		}

		if ( isset( $settings['approval_notification'] ) ) {
			update_option( 'naboo_notify_approval', (bool) $settings['approval_notification'] );
		}

		if ( isset( $settings['rejection_notification'] ) ) {
			update_option( 'naboo_notify_rejection', (bool) $settings['rejection_notification'] );
		}

		if ( isset( $settings['comment_notification'] ) ) {
			update_option( 'naboo_notify_comment', (bool) $settings['comment_notification'] );
		}

		if ( isset( $settings['rating_notification'] ) ) {
			update_option( 'naboo_notify_rating', (bool) $settings['rating_notification'] );
		}

		if ( isset( $settings['daily_digest'] ) ) {
			update_option( 'naboo_daily_digest', (bool) $settings['daily_digest'] );
		}

		if ( isset( $settings['notification_from_name'] ) ) {
			update_option( 'naboo_notification_from_name', sanitize_text_field( $settings['notification_from_name'] ) );
		}

		if ( isset( $settings['notification_from_email'] ) ) {
			update_option( 'naboo_notification_from_email', sanitize_email( $settings['notification_from_email'] ) );
		}

		return new \WP_REST_Response(
			array( 'message' => 'Notification settings updated' ),
			200
		);
	}

	/**
	 * Get email logs
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_email_logs( $request ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_email_logs';

		$logs = $wpdb->get_results(
			"SELECT * FROM $table_name ORDER BY sent_at DESC LIMIT 50"
		);

		return new \WP_REST_Response( array( 'logs' => $logs ), 200 );
	}

	/**
	 * Send test email
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function send_test_email( $request ) {
		$to = sanitize_email( $request->get_param( 'email' ) ) ?? get_option( 'admin_email' );

		$subject = sprintf(
			__( '[%s] Test Email from Naboo Database', 'naboodatabase' ),
			get_bloginfo( 'name' )
		);

		$message_text = sprintf(
			__( 'This is a test email from your Naboo Database plugin.

If you received this, your email notifications are working correctly.

Best regards,
%s',
			'naboodatabase'
		),
			get_bloginfo( 'name' )
		);

		$message = $this->wrap_in_template( $subject, $message_text );

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			sprintf( 'From: %s <%s>', get_option( 'naboo_notification_from_name' ), get_option( 'naboo_notification_from_email' ) ),
		);

		$result = wp_mail( $to, $subject, $message, $headers );

		// Log email
		$this->log_email( $to, $subject, 'test', $result ? 'sent' : 'failed' );

		if ( $result ) {
			return new \WP_REST_Response(
				array( 'message' => 'Test email sent successfully' ),
				200
			);
		}

		return new \WP_REST_Response(
			array( 'error' => 'Failed to send test email' ),
			500
		);
	}

	/**
	 * Wrap message in a standard HTML template
	 *
	 * @param string $subject The email subject.
	 * @param string $content The email content.
	 * @return string The compiled HTML template.
	 */
	private function wrap_in_template( $subject, $content ) {
		$site_name = get_bloginfo( 'name' );
		$site_url  = get_bloginfo( 'url' );
		
		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="utf-8">
			<title><?php echo esc_html( $subject ); ?></title>
		</head>
		<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f5; margin: 0; padding: 20px;">
			<div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
				<div style="background-color: #0052cc; color: #ffffff; padding: 20px 30px; text-align: center;">
					<h1 style="margin: 0; font-size: 24px; font-weight: 600;"><?php echo esc_html( $site_name ); ?></h1>
				</div>
				<div style="padding: 30px;">
					<h2 style="margin-top: 0; color: #172b4d; font-size: 20px;"><?php echo esc_html( $subject ); ?></h2>
					<div style="color: #42526e; font-size: 16px;">
						<?php echo wp_kses_post( wpautop( $content ) ); ?>
					</div>
				</div>
				<div style="background-color: #f4f5f7; padding: 20px 30px; text-align: center; color: #6b778c; font-size: 13px;">
					<p style="margin: 0;">&copy; <?php echo date('Y'); ?> <a href="<?php echo esc_url( $site_url ); ?>" style="color: #0052cc; text-decoration: none;"><?php echo esc_html( $site_name ); ?></a>. All rights reserved.</p>
					<p style="margin: 5px 0 0 0;">This is an automated notification, please do not reply to this email.</p>
				</div>
			</div>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}

	/**
	 * Log email
	 *
	 * @param string $recipient The recipient email.
	 * @param string $subject   The email subject.
	 * @param string $event_type The event type.
	 * @param string $status    The status.
	 */
	public function log_email( $recipient, $subject, $event_type, $status = 'sent' ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_email_logs';

		$wpdb->insert(
			$table_name,
			array(
				'recipient'   => $recipient,
				'subject'     => $subject,
				'event_type'  => $event_type,
				'status'      => $status,
			),
			array( '%s', '%s', '%s', '%s' )
		);
	}

	/**
	 * Send notification email
	 *
	 * @param string $event_type The event type.
	 * @param array  $data       The event data.
	 */
	public function send_notification( $event_type, $data ) {
		// Check if notification enabled
		$option_key = "naboo_notify_{$event_type}";
		if ( ! get_option( $option_key, true ) ) {
			return;
		}

		$to      = isset( $data['to'] ) ? $data['to'] : get_option( 'admin_email' );
		$subject = isset( $data['subject'] ) ? $data['subject'] : 'Naboo Database Notification';
		$raw_msg = isset( $data['message'] ) ? $data['message'] : '';
		
		$message = $this->wrap_in_template( $subject, $raw_msg );

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			sprintf( 'From: %s <%s>', get_option( 'naboo_notification_from_name' ), get_option( 'naboo_notification_from_email' ) ),
		);

		$result = wp_mail( $to, $subject, $message, $headers );

		// Log email
		$this->log_email( $to, $subject, $event_type, $result ? 'sent' : 'failed' );
	}

	/**
	 * Register Admin Menu
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'naboo-dashboard',
			__( 'Email Notifications', 'naboodatabase' ),
			__( '📧 Emails', 'naboodatabase' ),
			'manage_options',
			'naboo-emails',
			array( $this, 'render_admin_page' ),
			9
		);
	}

	/**
	 * Enqueue Admin Scripts
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( false === strpos( $hook, 'naboo-emails' ) ) {
			return;
		}

		wp_enqueue_script(
			$this->plugin_name . '-emails',
			plugin_dir_url( __FILE__ ) . 'js/emails-admin.js',
			array( 'jquery', 'wp-api-fetch' ),
			$this->version,
			true
		);

		wp_localize_script(
			$this->plugin_name . '-emails',
			'apaEmails',
			array(
				'apiUrl' => rest_url( 'apa/v1/notifications' ),
				'nonce'  => wp_create_nonce( 'wp_rest' ),
			)
		);

		wp_enqueue_style(
			$this->plugin_name . '-emails',
			plugin_dir_url( __FILE__ ) . 'css/emails-admin.css',
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

		// Enqueue Inter font
		echo '<link rel="preconnect" href="https://fonts.googleapis.com">
			  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
			  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">';
		?>
		<div class="wrap naboo-admin-page naboo-emails-wrap" style="font-family: 'Inter', sans-serif;">
			
			<!-- Header -->
			<div class="naboo-admin-header" style="margin-bottom: 32px; padding: 40px; background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border-radius: 16px; color: white; box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1); position: relative; overflow: hidden;">
				<div style="position: absolute; top: -50px; right: -50px; width: 200px; height: 200px; background: rgba(79, 70, 229, 0.1); filter: blur(80px); border-radius: 50%;"></div>
				<h1 style="color: white !important; font-size: 36px !important; margin: 0 !important; font-weight: 800; letter-spacing: -0.025em; display: flex; align-items: center; gap: 20px;">
					<span style="background: rgba(255,255,255,0.1); width: 64px; height: 64px; display: flex; align-items: center; justify-content: center; border-radius: 16px; backdrop-filter: blur(4px); border: 1px solid rgba(255,255,255,0.1);">📧</span>
					<?php esc_html_e( 'Email Notifications', 'naboodatabase' ); ?>
				</h1>
				<p style="margin: 16px 0 0 84px !important; color: #94a3b8; font-size: 18px; max-width: 600px; line-height: 1.6;"><?php esc_html_e( 'Configure automated email alerts and monitor delivery history. Keep your contributors and admins informed with real-time system notifications.', 'naboodatabase' ); ?></p>
			</div>

			<div class="naboo-email-tabs" style="margin-bottom: 32px; display: flex; gap: 4px; background: #f1f5f9; padding: 6px; border-radius: 14px; width: fit-content; border: 1px solid #e2e8f0;">
				<button class="naboo-tab-btn active" data-tab="settings" style="padding: 12px 24px; border-radius: 10px; border: none; font-weight: 700; cursor: pointer; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); background: white; color: #1e293b; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1); border: 1px solid #e2e8f0;"><?php esc_html_e( 'Settings', 'naboodatabase' ); ?></button>
				<button class="naboo-tab-btn" data-tab="logs" style="padding: 12px 24px; border-radius: 10px; border: none; font-weight: 600; cursor: pointer; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); background: transparent; color: #64748b;"><?php esc_html_e( 'Delivery Logs', 'naboodatabase' ); ?></button>
			</div>

			<div class="naboo-email-container">
				
				<!-- Settings Tab -->
				<div id="naboo-email-settings" class="naboo-tab-content">
					<form id="naboo-email-config-form">
						<div class="naboo-email-grid" style="display: grid; grid-template-columns: 1fr 350px; gap: 24px;">
							<div class="naboo-email-main">
								
								<div class="naboo-email-section-title" style="font-size: 20px; font-weight: 800; color: #1e293b; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; letter-spacing: -0.01em;">
									<span style="background: #e0f2fe; color: #0284c7; width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;">✉️</span>
									<?php esc_html_e( 'Sender Configuration', 'naboodatabase' ); ?>
								</div>

								<div class="naboo-admin-card" style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; padding: 32px; margin-bottom: 40px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);">
									<div class="naboo-form-row" style="margin-bottom: 24px;">
										<label style="display: block; font-weight: 700; font-size: 14px; color: #334155; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.05em;"><?php esc_html_e( 'From Name', 'naboodatabase' ); ?></label>
										<input type="text" name="notification_from_name" id="naboo-email-from-name" class="regular-text" style="width: 100%; border-radius: 10px; border: 1px solid #cbd5e1; padding: 12px 16px; font-size: 15px; transition: all 0.2s;" placeholder="<?php esc_attr_e( 'e.g. Naboo Database Alerts', 'naboodatabase' ); ?>">
									</div>
									<div class="naboo-form-row">
										<label style="display: block; font-weight: 700; font-size: 14px; color: #334155; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.05em;"><?php esc_html_e( 'From Email', 'naboodatabase' ); ?></label>
										<input type="email" name="notification_from_email" id="naboo-email-from-email" class="regular-text" style="width: 100%; border-radius: 10px; border: 1px solid #cbd5e1; padding: 12px 16px; font-size: 15px; transition: all 0.2s;" placeholder="<?php esc_attr_e( 'e.g. alerts@yourdomain.com', 'naboodatabase' ); ?>">
										<p class="description" style="color: #64748b; font-size: 13px; margin-top: 10px; line-height: 1.5;"><?php esc_html_e( 'Outgoing notifications will appear to come from this address. Ensure your server is authorized to send for this domain.', 'naboodatabase' ); ?></p>
									</div>
								</div>

								<div class="naboo-email-section-title" style="font-size: 20px; font-weight: 800; color: #1e293b; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; letter-spacing: -0.01em;">
									<span style="background: #fff7ed; color: #ea580c; width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;">🔔</span>
									<?php esc_html_e( 'Notification Triggers', 'naboodatabase' ); ?>
								</div>

								<div class="naboo-admin-card" style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; padding: 8px; margin-bottom: 40px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);">
									<?php
									$toggles = array(
										'submission_notification' => array(
											'label' => __( 'New Submissions (Admin)', 'naboodatabase' ),
											'desc'  => __( 'Notify admin when a new scale is submitted for review.', 'naboodatabase' ),
											'icon'  => '📥',
										),
										'approval_notification' => array(
											'label' => __( 'Submission Approved (User)', 'naboodatabase' ),
											'desc'  => __( 'Notify author when their scale submission is published.', 'naboodatabase' ),
											'icon'  => '✅',
										),
										'rejection_notification' => array(
											'label' => __( 'Submission Rejected (User)', 'naboodatabase' ),
											'desc'  => __( 'Notify author when their scale submission is rejected.', 'naboodatabase' ),
											'icon'  => '❌',
										),
										'comment_notification' => array(
											'label' => __( 'New Comments', 'naboodatabase' ),
											'desc'  => __( 'Notify scale authors on new comments or discussions.', 'naboodatabase' ),
											'icon'  => '💬',
										),
										'rating_notification' => array(
											'label' => __( 'New Ratings', 'naboodatabase' ),
											'desc'  => __( 'Notify scale authors when someone rates their scale.', 'naboodatabase' ),
											'icon'  => '⭐',
										),
										'daily_digest' => array(
											'label' => __( 'Daily Activity Digest', 'naboodatabase' ),
											'desc'  => __( 'Send admin a daily summary of all dashboard activity.', 'naboodatabase' ),
											'icon'  => '📊',
										),
									);

									foreach ( $toggles as $id => $info ) : ?>
										<label class="naboo-toggle-row" style="display: flex; align-items: center; gap: 20px; padding: 20px 24px; border-radius: 12px; cursor: pointer; transition: all 0.2s; border-bottom: 1px solid #f1f5f9; margin: 0 4px;">
											<div style="background: #f8fafc; width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; border: 1px solid #e2e8f0;">
												<?php echo $info['icon']; ?>
											</div>
											<div class="toggle-info" style="flex: 1;">
												<strong style="display: block; font-size: 15px; color: #1e293b; font-weight: 700;"><?php echo esc_html( $info['label'] ); ?></strong>
												<span style="display: block; font-size: 13px; color: #64748b; margin-top: 2px;"><?php echo esc_html( $info['desc'] ); ?></span>
											</div>
											<div class="naboo-switch">
												<input type="checkbox" name="<?php echo esc_attr( $id ); ?>" id="naboo-email-notify-<?php echo esc_attr( substr($id, 0, 3) ); ?>" style="width: 22px; height: 22px; cursor: pointer; accent-color: #4f46e5;">
											</div>
										</label>
									<?php endforeach; ?>
								</div>

							</div>

							<div class="naboo-email-side">
								<div class="naboo-admin-card" style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; padding: 32px; position: sticky; top: 32px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);">
									<h2 style="margin-top: 0; font-size: 18px; font-weight: 800; color: #1e293b; display: flex; align-items: center; gap: 12px; letter-spacing: -0.01em;">
										<span style="background: #eef2ff; color: #4f46e5; width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px;">🎯</span>
										<?php esc_html_e( 'Test Delivery', 'naboodatabase' ); ?>
									</h2>
									<p style="color: #64748b; font-size: 14px; margin: 16px 0 24px; line-height: 1.6;"><?php esc_html_e( 'Ensure your server is correctly configured to send notifications by triggering a test email.', 'naboodatabase' ); ?></p>
									<div style="position: relative; margin-bottom: 16px;">
										<span style="position: absolute; left: 14px; top: 12px; color: #94a3b8;">📧</span>
										<input type="email" id="naboo-test-email-addr" placeholder="<?php esc_attr_e( 'Test email address...', 'naboodatabase' ); ?>" style="width: 100%; border-radius: 10px; border: 1px solid #cbd5e1; padding: 12px 14px 12px 40px; font-size: 14px; transition: all 0.2s;">
									</div>
									<button type="button" id="naboo-send-test-btn" class="naboo-btn" style="width: 100%; background: #4f46e5; color: white; border: none; padding: 14px; border-radius: 10px; font-weight: 700; cursor: pointer; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2);"><?php esc_html_e( 'Send Test Email', 'naboodatabase' ); ?></button>
									<div id="naboo-test-status" style="margin-top: 16px; font-size: 14px; text-align: center; font-weight: 600;"></div>
								</div>
							</div>
						</div>

						<!-- Sticky Save Bar -->
						<div class="naboo-save-bar" style="position: sticky; bottom: 24px; z-index: 100; background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(12px); padding: 24px 40px; border-radius: 20px; border: 1px solid rgba(226, 232, 240, 0.8); box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1); display: flex; justify-content: flex-end; align-items: center; margin-top: 60px;">
							<button type="submit" class="naboo-btn" style="background: #4f46e5; color: white; border: none; padding: 14px 32px; border-radius: 12px; font-weight: 700; font-size: 15px; cursor: pointer; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2);"><?php esc_html_e( 'Save Email Settings', 'naboodatabase' ); ?></button>
						</div>
					</form>
				</div>

				<!-- Logs Tab -->
				<div id="naboo-email-logs" class="naboo-tab-content" style="display:none;">
					<div class="naboo-admin-card" style="background: white; border-radius: 20px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);">
						<div class="naboo-admin-card-header" style="padding: 24px 32px; border-bottom: 1px solid #f1f5f9; background: #f8fafc; display: flex; align-items: center; justify-content: space-between;">
							<h2 style="margin: 0; font-size: 20px; font-weight: 800; color: #1e293b; display: flex; align-items: center; gap: 12px; letter-spacing: -0.01em;">
								<span style="background: #f1f5f9; color: #475569; width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;">📝</span>
								<?php esc_html_e( 'Log History', 'naboodatabase' ); ?>
							</h2>
							<span style="background: #eef2ff; color: #4f46e5; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 700;"><?php esc_html_e( 'System Activity - Last 30 Days', 'naboodatabase' ); ?></span>
						</div>
						<div style="padding: 0; overflow-x: auto;">
							<table class="wp-list-table widefat fixed striped" style="border: none; box-shadow: none; width: 100%; border-collapse: collapse;">
								<thead>
									<tr style="background: #fafafa;">
										<th style="padding: 16px 32px; border-bottom: 2px solid #f1f5f9; font-weight: 800; color: #475569; text-transform: uppercase; font-size: 12px; letter-spacing: 0.05em;"><?php esc_html_e( 'Recipient', 'naboodatabase' ); ?></th>
										<th style="padding: 16px 32px; border-bottom: 2px solid #f1f5f9; font-weight: 800; color: #475569; text-transform: uppercase; font-size: 12px; letter-spacing: 0.05em;"><?php esc_html_e( 'Subject', 'naboodatabase' ); ?></th>
										<th style="padding: 16px 32px; border-bottom: 2px solid #f1f5f9; font-weight: 800; color: #475569; text-transform: uppercase; font-size: 12px; letter-spacing: 0.05em;"><?php esc_html_e( 'Event', 'naboodatabase' ); ?></th>
										<th style="padding: 16px 32px; border-bottom: 2px solid #f1f5f9; font-weight: 800; color: #475569; text-transform: uppercase; font-size: 12px; letter-spacing: 0.05em;"><?php esc_html_e( 'Status', 'naboodatabase' ); ?></th>
										<th style="padding: 16px 32px; border-bottom: 2px solid #f1f5f9; font-weight: 800; color: #475569; text-transform: uppercase; font-size: 12px; letter-spacing: 0.05em;"><?php esc_html_e( 'Sent At', 'naboodatabase' ); ?></th>
									</tr>
								</thead>
								<tbody id="naboo-email-logs-list">
									<!-- AJAX results -->
								</tbody>
							</table>
						</div>
					</div>
				</div>

			</div>
		</div>
		<style>
			.naboo-toggle-row { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
			.naboo-toggle-row:hover { background: #f8fafc; transform: translateX(4px); }
			.naboo-toggle-row:last-child { border-bottom: none; }
			.naboo-btn:hover { transform: translateY(-1px); filter: brightness(1.1); }
			.status-sent { background: #ecfdf5; color: #065f46; padding: 6px 12px; border-radius: 999px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; border: 1px solid #d1fae5; }
			.status-failed { background: #fef2f2; color: #991b1b; padding: 6px 12px; border-radius: 999px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; border: 1px solid #fee2e2; }
			#naboo-email-logs-list tr td { padding: 20px 32px; border-bottom: 1px solid #f1f5f9; font-size: 14px; vertical-align: middle; color: #334155; }
			#naboo-email-logs-list tr:hover { background: #f8fafc; }
			#naboo-email-logs-list code { background: #f1f5f9; color: #475569; padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 600; }
			
			/* Custom Checkbox Styling */
			input[type="checkbox"] { width: 22px; height: 22px; border-radius: 6px; border: 2px solid #cbd5e1; appearance: none; -webkit-appearance: none; background: white; cursor: pointer; position: relative; transition: all 0.2s; }
			input[type="checkbox"]:checked { background: #4f46e5; border-color: #4f46e5; }
			input[type="checkbox"]:checked::after { content: '✓'; position: absolute; color: white; font-size: 14px; top: 50%; left: 50%; transform: translate(-50%, -50%); font-weight: 800; }
		</style>
		<?php
	}

}
