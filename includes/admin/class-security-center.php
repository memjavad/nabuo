<?php
/**
 * Naboo Security Center
 *
 * @package ArabPsychology\NabooDatabase\Admin
 */

namespace ArabPsychology\NabooDatabase\Admin;

/**
 * Security_Center class - Admin interface for security settings.
 */
class Security_Center {

	/**
	 * Plugin name
	 * @var string
	 */
	private $plugin_name;

	/**
	 * Plugin version
	 * @var string
	 */
	private $version;

	/**
	 * Option name for security settings
	 * @var string
	 */
	private $option_name = 'naboodatabase_security_options';

	/**
	 * Constructor
	 *
	 * @param string $plugin_name Plugin name.
	 * @param string $version     Plugin version.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register submenu under NABOO Dashboard.
	 */
	public function register_menu() {
		add_submenu_page(
			'naboo-dashboard',
			__( 'Security', 'naboodatabase' ),
			__( '🛡️ Security', 'naboodatabase' ),
			'manage_options',
			'naboo-security',
			array( $this, 'render_page' ),
			6
		);
	}

	/**
	 * Register settings
	 */
	public function register_settings() {
		register_setting(
			'naboodatabase_security_group',
			$this->option_name,
			array( $this, 'sanitize_settings' )
		);
	}

	/**
	 * Sanitize security settings
	 */
	public function sanitize_settings( $input ) {
		$old_options = get_option( $this->option_name, array() );
		$sanitized   = is_array( $old_options ) ? $old_options : array();

		$input = is_array( $input ) ? $input : array();

		// Determine the active tab from the hidden referer field
		$active_tab = 'settings';
		if ( isset( $_POST['_wp_http_referer'] ) ) {
			$referer = sanitize_text_field( wp_unslash( $_POST['_wp_http_referer'] ) );
			wp_parse_str( (string) wp_parse_url( $referer, PHP_URL_QUERY ), $query_args );
			if ( ! empty( $query_args['tab'] ) ) {
				$active_tab = $query_args['tab'];
			}
		}

		// Define the fields present on each tab
		$tab_fields = array(
			'settings'      => array( 'limit_login_attempts', 'block_user_enumeration', 'hide_wp_version', 'disable_xmlrpc' ),
			'firewall'      => array( 'enable_waf', 'waf_whitelist', 'login_slug', 'server_hardening' ),
			'notifications' => array( 'enable_alerts', 'alert_email' ),
		);

		$current_fields = isset( $tab_fields[ $active_tab ] ) ? $tab_fields[ $active_tab ] : array();

		foreach ( $current_fields as $field ) {
			$value = isset( $input[ $field ] ) ? $input[ $field ] : '';

			if ( in_array( $field, array( 'login_slug', 'waf_whitelist', 'alert_email' ) ) ) {
				if ( $field === 'alert_email' ) {
					$sanitized[ $field ] = sanitize_email( $value );
				} elseif ( $field === 'waf_whitelist' ) {
					$sanitized[ $field ] = sanitize_textarea_field( $value );
				} else {
					$sanitized[ $field ] = sanitize_text_field( $value );
				}
			} else {
				$sanitized[ $field ] = ! empty( $value ) ? 1 : 0;
			}
		}

		// Log the update
		$logger = new \ArabPsychology\NabooDatabase\Core\Security_Logger();
		$logger->log( 'settings_update', __( 'Security settings were updated by an administrator.', 'naboodatabase' ), 'info' );

		return $sanitized;
	}
	/**
	 * Render the settings page
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'naboodatabase' ) );
		}

		$tab     = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'settings';
		$options = get_option( $this->option_name, array(
			'enable_nosniff'        => 1,
			'enable_xframe'         => 1,
			'enable_xss_protection' => 1,
			'disable_xmlrpc'        => 1,
			'secure_uploads'        => 1,
			'enable_honeypot'       => 1,
			'enable_rate_limit'     => 1,
			'limit_login_attempts'  => 1,
			'block_user_enumeration' => 1,
			'hide_wp_version'       => 1,
			'restrict_rest_api'     => 0,
			'enable_waf'            => 1,
			'waf_whitelist'         => '',
			'login_slug'            => '',
			'server_hardening'      => 0,
			'enable_alerts'         => 0,
			'alert_email'           => get_option( 'admin_email' ),
		) );

		?>
		// Enqueue Inter font
		echo '<link rel="preconnect" href="https://fonts.googleapis.com">
			  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
			  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">';

		?>
		<div class="wrap naboo-admin-page naboo-security-wrap" style="font-family: 'Inter', sans-serif;">
			
			<!-- Header -->
			<div class="naboo-admin-header" style="margin-bottom: 32px; padding: 40px; background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border-radius: 16px; color: white; box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1); position: relative; overflow: hidden;">
				<div style="position: absolute; top: -50px; right: -50px; width: 200px; height: 200px; background: rgba(239, 68, 68, 0.1); filter: blur(80px); border-radius: 50%;"></div>
				<h1 style="color: white !important; font-size: 36px !important; margin: 0 !important; font-weight: 800; letter-spacing: -0.025em; display: flex; align-items: center; gap: 20px;">
					<span style="background: rgba(255,255,255,0.1); width: 64px; height: 64px; display: flex; align-items: center; justify-content: center; border-radius: 16px; backdrop-filter: blur(4px); border: 1px solid rgba(255,255,255,0.1);">🛡️</span>
					<?php esc_html_e( 'Security Center', 'naboodatabase' ); ?>
				</h1>
				<p style="margin: 16px 0 0 84px !important; color: #94a3b8; font-size: 18px; max-width: 600px; line-height: 1.6;"><?php esc_html_e( 'Configure professional cyber security measures to protect your database and research infrastructure.', 'naboodatabase' ); ?></p>
			</div>

			<!-- Tabs -->
			<nav class="naboo-admin-tabs" style="margin-bottom: 32px; display: flex; gap: 8px; border-bottom: 1px solid #e2e8f0; padding-bottom: 1px;">
				<?php 
				$tabs = array(
					'settings'      => array( 'icon' => '⚙️', 'label' => __( 'Core Hardening', 'naboodatabase' ) ),
					'firewall'      => array( 'icon' => '🧱', 'label' => __( 'Firewall & Cloaking', 'naboodatabase' ) ),
					'notifications' => array( 'icon' => '🔔', 'label' => __( 'Alerts & Reports', 'naboodatabase' ) ),
					'logs'          => array( 'icon' => '📋', 'label' => __( 'Audit Log', 'naboodatabase' ) ),
				);
				foreach ( $tabs as $id => $info ) : 
					$is_active = ( $tab === $id ) || ( $id === 'logs' && strpos( $tab, 'logs' ) !== false );
				?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=naboo-security&tab=' . $id ) ); ?>" 
					   class="naboo-tab-link <?php echo $is_active ? 'active' : ''; ?>"
					   style="padding: 12px 20px; text-decoration: none; color: <?php echo $is_active ? '#4f46e5' : '#64748b'; ?>; font-weight: 600; border-bottom: 2px solid <?php echo $is_active ? '#4f46e5' : 'transparent'; ?>; transition: all 0.2s;">
						<span style="margin-right: 8px;"><?php echo $info['icon']; ?></span> <?php echo $info['label']; ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<?php if ( 'settings' === $tab ) : ?>
				<form method="post" action="options.php">
					<?php settings_fields( 'naboodatabase_security_group' ); ?>

					<div class="naboo-admin-grid">
						<!-- Brute Force & Login -->
						<div class="naboo-admin-card">
							<div class="naboo-admin-card-header">
								<span class="naboo-admin-card-icon red">🔨</span>
								<h3><?php esc_html_e( 'Brute Force Protection', 'naboodatabase' ); ?></h3>
							</div>
							
							<label class="naboo-toggle-row">
								<input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[limit_login_attempts]" value="1" <?php checked( 1, $options['limit_login_attempts'] ?? 1 ); ?> />
								<div class="toggle-info">
									<strong><?php esc_html_e( 'Limit Login Attempts', 'naboodatabase' ); ?></strong>
									<span><?php esc_html_e( 'Lock out IPs after 5 failed login attempts.', 'naboodatabase' ); ?></span>
								</div>
							</label>

							<label class="naboo-toggle-row">
								<input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[block_user_enumeration]" value="1" <?php checked( 1, $options['block_user_enumeration'] ?? 1 ); ?> />
								<div class="toggle-info">
									<strong><?php esc_html_e( 'Block User Enumeration', 'naboodatabase' ); ?></strong>
									<span><?php esc_html_e( 'Disable author-based username scans.', 'naboodatabase' ); ?></span>
								</div>
							</label>
						</div>

						<!-- Advanced Hardening -->
						<div class="naboo-admin-card">
							<div class="naboo-admin-card-header">
								<span class="naboo-admin-card-icon purple">💎</span>
								<h3><?php esc_html_e( 'Advanced Hardening', 'naboodatabase' ); ?></h3>
							</div>

							<label class="naboo-toggle-row">
								<input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[hide_wp_version]" value="1" <?php checked( 1, $options['hide_wp_version'] ?? 1 ); ?> />
								<div class="toggle-info">
									<strong><?php esc_html_e( 'Hide WP Version', 'naboodatabase' ); ?></strong>
									<span><?php esc_html_e( 'Hides version information from the public.', 'naboodatabase' ); ?></span>
								</div>
							</label>

							<label class="naboo-toggle-row">
								<input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[disable_xmlrpc]" value="1" <?php checked( 1, $options['disable_xmlrpc'] ?? 1 ); ?> />
								<div class="toggle-info">
									<strong><?php esc_html_e( 'Disable XML-RPC', 'naboodatabase' ); ?></strong>
									<span><?php esc_html_e( 'Block brute-force attacks via XML-RPC.', 'naboodatabase' ); ?></span>
								</div>
							</label>
						</div>

						<!-- Security Health dashboard -->
						<div class="naboo-admin-card">
							<div class="naboo-admin-card-header">
								<span class="naboo-admin-card-icon green">🚥</span>
								<h3><?php esc_html_e( 'Security Health', 'naboodatabase' ); ?></h3>
							</div>
							<?php
							$score = 0; $max = 10;
							if ( ! empty( $options['limit_login_attempts'] ) ) $score++;
							if ( ! empty( $options['block_user_enumeration'] ) ) $score++;
							if ( ! empty( $options['hide_wp_version'] ) ) $score++;
							if ( ! empty( $options['disable_xmlrpc'] ) ) $score++;
							if ( ! empty( $options['enable_waf'] ) ) $score++;
							if ( ! empty( $options['login_slug'] ) ) $score++;
							if ( ! empty( $options['server_hardening'] ) ) $score++;
							if ( ! empty( $options['secure_uploads'] ) ) $score++;
							if ( ! empty( $options['enable_honeypot'] ) ) $score++;
							if ( ! empty( $options['enable_alerts'] ) ) $score++;
							
							$percent = round( ($score / $max) * 100 );
							$color = $percent > 80 ? '#00a32a' : ($percent > 50 ? '#dba617' : '#d63638');
							?>
							<div class="security-score-container" style="text-align: center; margin: 20px 0;">
								<div class="score-circle" style="width: 100px; height: 100px; border-radius: 50%; border: 8px solid <?php echo $color; ?>; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
									<span style="font-size: 24px; font-weight: 800; color: <?php echo $color; ?>;"><?php echo $percent; ?>%</span>
								</div>
								<p style="margin-top: 10px; font-weight: 600; color: #666;"><?php esc_html_e( 'Cyber Posture', 'naboodatabase' ); ?></p>
							</div>

							<ul class="naboo-security-status-list">
								<li>
									<span><?php esc_html_e( 'Firewall (WAF):', 'naboodatabase' ); ?></span>
									<span class="status-badge <?php echo ! empty( $options['enable_waf'] ) ? 'success' : 'gray'; ?>">
										<?php echo ! empty( $options['enable_waf'] ) ? 'Active' : 'Bypassed'; ?>
									</span>
								</li>
								<li>
									<span><?php esc_html_e( 'Login URL:', 'naboodatabase' ); ?></span>
									<span class="status-badge <?php echo ! empty( $options['login_slug'] ) ? 'success' : 'warning'; ?>">
										<?php echo ! empty( $options['login_slug'] ) ? 'Hidden' : 'Standard'; ?>
									</span>
								</li>
							</ul>
						</div>
					</div>

					<div class="naboo-save-bar">
						<?php submit_button( __( 'Save Security Settings', 'naboodatabase' ), 'primary naboo-btn naboo-btn-primary', 'submit', false ); ?>
					</div>
				</form>

			<?php elseif ( 'firewall' === $tab ) : ?>
				<form method="post" action="options.php">
					<?php settings_fields( 'naboodatabase_security_group' ); ?>
					<div class="naboo-admin-grid">
						<div class="naboo-admin-card">
							<div class="naboo-admin-card-header">
								<span class="naboo-admin-card-icon blue">🧱</span>
								<h3><?php esc_html_e( 'Web Application Firewall', 'naboodatabase' ); ?></h3>
							</div>
							<label class="naboo-toggle-row">
								<input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[enable_waf]" value="1" <?php checked( 1, $options['enable_waf'] ?? 1 ); ?> />
								<div class="toggle-info">
									<strong><?php esc_html_e( 'Enable Proactive Firewall', 'naboodatabase' ); ?></strong>
									<span><?php esc_html_e( 'Automatically blocks common SQLi, XSS, and RCE attack patterns.', 'naboodatabase' ); ?></span>
								</div>
							</label>

							<div style="margin-top: 15px;">
								<label style="display: block; font-weight: 600; margin-bottom: 5px;"><?php esc_html_e( 'IP Whitelist (one per line)', 'naboodatabase' ); ?></label>
								<textarea name="<?php echo esc_attr( $this->option_name ); ?>[waf_whitelist]" style="width: 100%; height: 80px; font-family: monospace; font-size: 13px;"><?php echo esc_textarea( $options['waf_whitelist'] ?? '' ); ?></textarea>
							</div>
						</div>

						<div class="naboo-admin-card">
							<div class="naboo-admin-card-header">
								<span class="naboo-admin-card-icon purple">🕵️</span>
								<h3><?php esc_html_e( 'Login Cloaking', 'naboodatabase' ); ?></h3>
							</div>
							<div style="margin-bottom: 15px;">
								<label style="display: block; font-weight: 600; margin-bottom: 5px;"><?php esc_html_e( 'Custom Login URL', 'naboodatabase' ); ?></label>
								<input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[login_slug]" value="<?php echo esc_attr( $options['login_slug'] ?? '' ); ?>" placeholder="e.g. naboo-access" style="width: 100%;" />
								<p class="description"><?php esc_html_e( 'Renaming wp-login.php hides your login page from 99% of bots. Leave empty to disable.', 'naboodatabase' ); ?></p>
							</div>
						</div>

						<div class="naboo-admin-card">
							<div class="naboo-admin-card-header">
								<span class="naboo-admin-card-icon red">💿</span>
								<h3><?php esc_html_e( 'Server Hardening', 'naboodatabase' ); ?></h3>
							</div>
							<label class="naboo-toggle-row">
								<input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[server_hardening]" value="1" <?php checked( 1, $options['server_hardening'] ?? 0 ); ?> />
								<div class="toggle-info">
									<strong><?php esc_html_e( 'Write .htaccess Hardening Rules', 'naboodatabase' ); ?></strong>
									<span><?php esc_html_e( 'Protects wp-config.php and blocks access to readmes/licenses.', 'naboodatabase' ); ?></span>
								</div>
							</label>
						</div>
					</div>
					<div class="naboo-save-bar">
						<?php submit_button( __( 'Save Firewall Settings', 'naboodatabase' ), 'primary naboo-btn naboo-btn-primary', 'submit', false ); ?>
					</div>
				</form>

			<?php elseif ( 'notifications' === $tab ) : ?>
				<form method="post" action="options.php">
					<?php settings_fields( 'naboodatabase_security_group' ); ?>
					<div class="naboo-admin-grid">
						<div class="naboo-admin-card">
							<div class="naboo-admin-card-header">
								<span class="naboo-admin-card-icon amber">📧</span>
								<h3><?php esc_html_e( 'Security Email Alerts', 'naboodatabase' ); ?></h3>
							</div>
							<label class="naboo-toggle-row" style="margin-bottom: 15px;">
								<input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[enable_alerts]" value="1" <?php checked( 1, $options['enable_alerts'] ?? 0 ); ?> />
								<div class="toggle-info">
									<strong><?php esc_html_e( 'Enable Critical Alerts', 'naboodatabase' ); ?></strong>
									<span><?php esc_html_e( 'Receive an email when the WAF blocks a threat or an IP is locked out.', 'naboodatabase' ); ?></span>
								</div>
							</label>
							
							<label style="display: block; font-weight: 600; margin-bottom: 5px;"><?php esc_html_e( 'Alert Email Address', 'naboodatabase' ); ?></label>
							<input type="email" name="<?php echo esc_attr( $this->option_name ); ?>[alert_email]" value="<?php echo esc_attr( $options['alert_email'] ?? '' ); ?>" style="width: 100%;" />
						</div>
					</div>
					<div class="naboo-save-bar">
						<?php submit_button( __( 'Save Notification Settings', 'naboodatabase' ), 'primary naboo-btn naboo-btn-primary', 'submit', false ); ?>
					</div>
				</form>

			<?php elseif ( 'logs' === $tab ) : ?>
				<?php $this->render_logs_tab(); ?>
			<?php endif; ?>
		</div>

		<style>
			.naboo-admin-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 24px; }
			.naboo-admin-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); overflow: hidden; transition: transform 0.2s ease; }
			.naboo-admin-card:hover { transform: translateY(-2px); }
			
			.naboo-admin-card-header { padding: 20px; border-bottom: 1px solid #f1f5f9; background: #f8fafc; display: flex; align-items: center; gap: 12px; }
			.naboo-admin-card-header h3 { margin: 0; font-size: 16px; font-weight: 700; color: #1e293b; }
			.naboo-admin-card-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
			.naboo-admin-card-icon.red { background: #fee2e2; color: #dc2626; }
			.naboo-admin-card-icon.purple { background: #f3e8ff; color: #9333ea; }
			.naboo-admin-card-icon.blue { background: #dbeafe; color: #2563eb; }
			.naboo-admin-card-icon.green { background: #dcfce7; color: #16a34a; }
			.naboo-admin-card-icon.amber { background: #fef3c7; color: #d97706; }

			.naboo-toggle-row { display: flex; align-items: flex-start; gap: 12px; padding: 16px 20px; border-bottom: 1px solid #f1f5f9; transition: background 0.2s; cursor: pointer; }
			.naboo-toggle-row:hover { background: #f8fafc; }
			.naboo-toggle-row:last-child { border-bottom: none; }
			.toggle-info { display: flex; flex-direction: column; }
			.toggle-info strong { font-size: 14px; color: #1e293b; }
			.toggle-info span { font-size: 12px; color: #64748b; margin-top: 2px; }

			.naboo-save-bar { 
				position: sticky; bottom: 20px; z-index: 100; background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(12px); 
				padding: 20px 32px; border-radius: 16px; border: 1px solid rgba(226, 232, 240, 0.8); box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1); 
				display: flex; justify-content: flex-end; align-items: center; margin-top: 40px;
			}
			.naboo-btn { padding: 12px 28px; border-radius: 10px; font-weight: 700; font-size: 14px; cursor: pointer; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); border: none; }
			.naboo-btn-primary { background: #4f46e5; color: white; box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2); }
			.naboo-btn-primary:hover { background: #4338ca; transform: translateY(-1px); box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.3); }

			.security-score-container { padding: 24px; border-bottom: 1px solid #f1f5f9; }
			.naboo-security-status-list { list-style: none; padding: 0 20px 20px; margin: 0; }
			.naboo-security-status-list li { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #f1f5f9; }
			.naboo-security-status-list li:last-child { border-bottom: none; }
			
			.status-badge { font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px; }
			.status-badge.success { background: #dcfce7; color: #15803d; }
			.status-badge.info { background: #dbeafe; color: #1e40af; }
			.status-badge.warning { background: #fef3c7; color: #92400e; }
			.status-badge.danger { background: #fee2e2; color: #991b1b; }
			.status-badge.gray { background: #f1f5f9; color: #475569; }
			
			.naboo-log-table { width: 100%; border-collapse: collapse; background: #fff; }
			.naboo-log-table th, .naboo-log-table td { padding: 16px 20px; text-align: left; border-bottom: 1px solid #f1f5f9; }
			.naboo-log-table th { background: #f8fafc; font-weight: 700; font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
			.log-severity { width: 6px; padding: 0 !important; }
			.log-severity.info { background: #3b82f6; }
			.log-severity.warning { background: #f59e0b; }
			.log-severity.danger { background: #ef4444; }
		</style>
		<?php
	}

	/**
	 * Render the Security Audit Log tab
	 */
	private function render_logs_tab() {
		$logger = new \ArabPsychology\NabooDatabase\Core\Security_Logger();
		$logs   = $logger->get_logs( 50 );
		?>
		<div class="naboo-admin-card span-full" style="padding: 0;">
			<div class="naboo-admin-card-header" style="padding: 20px;">
				<span class="naboo-admin-card-icon blue">📋</span>
				<h3><?php esc_html_e( 'Security Audit Log', 'naboodatabase' ); ?></h3>
			</div>
			
			<table class="naboo-log-table">
				<thead>
					<tr>
						<th class="log-severity"></th>
						<th><?php esc_html_e( 'Timestamp', 'naboodatabase' ); ?></th>
						<th><?php esc_html_e( 'Event', 'naboodatabase' ); ?></th>
						<th><?php esc_html_e( 'User', 'naboodatabase' ); ?></th>
						<th><?php esc_html_e( 'IP Address', 'naboodatabase' ); ?></th>
						<th><?php esc_html_e( 'Description', 'naboodatabase' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $logs ) ) : ?>
						<tr>
							<td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8;">
								<?php esc_html_e( 'No security logs found.', 'naboodatabase' ); ?>
							</td>
						</tr>
					<?php else : ?>
						<?php foreach ( $logs as $log ) : ?>
							<tr>
								<td class="log-severity <?php echo esc_attr( $log->severity ); ?>"></td>
								<td style="font-size: 12px; color: #64748b;"><?php echo esc_html( $log->timestamp ); ?></td>
								<td><span class="status-badge <?php echo esc_attr( $log->severity ); ?>"><?php echo esc_html( str_replace('_', ' ', $log->event_type) ); ?></span></td>
								<td style="font-weight: 500;"><?php echo esc_html( $log->user_login ?: 'Guest' ); ?></td>
								<td><code><?php echo esc_html( $log->ip_address ); ?></code></td>
								<td style="font-size: 13px;"><?php echo esc_html( $log->description ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}
