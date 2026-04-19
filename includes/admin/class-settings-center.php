<?php
/**
 * Unified Settings & Control Center
 *
 * @package ArabPsychology\NabooDatabase\Admin
 */

namespace ArabPsychology\NabooDatabase\Admin;

use ArabPsychology\NabooDatabase\Admin\Settings\Tab_General;
use ArabPsychology\NabooDatabase\Admin\Settings\Tab_AI;
use ArabPsychology\NabooDatabase\Admin\Settings\Tab_Grokipedia;
use ArabPsychology\NabooDatabase\Admin\Settings\Tab_Roles;
use ArabPsychology\NabooDatabase\Admin\Settings\Section_System;

/**
 * Settings_Center class - Central plugin settings hub with tabbed UI.
 */
class Settings_Center {

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
	 * Option name for plugin settings
	 * @var string
	 */
	private $option_name = 'naboodatabase_plugin_settings';

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
	 * Runs at admin_menu priority 20 to override the placeholder set by Advanced_Admin_Dashboard.
	 */
	public function register_menu() {
		// Re-register the same slug with the real callback at a late priority so WordPress uses this one.
		add_submenu_page(
			'naboo-dashboard',
			__( 'NABOO Settings', 'naboodatabase' ),
			__( '⚙ Settings', 'naboodatabase' ),
			'manage_options',
			'naboo-dashboard-settings',
			array( $this, 'render_page' ),
			2
		);
	}

	/**
	 * Register settings
	 */
	public function register_settings() {
		register_setting(
			'naboodatabase_settings_group',
			$this->option_name,
			array( $this, 'sanitize_settings' )
		);
	}

	/**
	 * Enqueue assets (only on NABOO admin pages)
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( $hook ) {
		// Load global admin CSS on all NABOO pages
		if ( strpos( $hook, 'naboo-dashboard' ) !== false || strpos( $hook, 'naboodatabase' ) !== false ) {
			wp_enqueue_style(
				'naboo-admin-global',
				plugin_dir_url( __FILE__ ) . 'css/naboo-admin-global.css',
				array(),
				$this->version
			);
		}
	}

	/**
	 * Sanitize settings on save
	 *
	 * @param array $input Raw form input.
	 * @return array Sanitized settings.
	 */
	public function sanitize_settings( $input ) {
		$current  = get_option( $this->option_name, array() );
		$tab       = isset( $_POST['naboo_settings_tab'] ) ? sanitize_text_field( $_POST['naboo_settings_tab'] ) : '';
		$sanitized = $current;

		$tabs_to_process = ( $tab === 'all' ) ? array( 'general', 'ai' ) : array( $tab );

		foreach ( $tabs_to_process as $tab_to_process ) {
			switch ( $tab_to_process ) {
				case 'general':
					$gen_tab = new Tab_General();
					$sanitized = array_merge( $sanitized, $gen_tab->sanitize( $input ) );
					break;

				case 'ai':
					$ai_tab = new Tab_AI();
					$sanitized = array_merge( $sanitized, $ai_tab->sanitize( $input ) );
					break;
			}
		}

		return $sanitized;
	}

	/**
	 * Render the settings page (Unified Single Page View)
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'naboodatabase' ) );
		}

		$options = get_option( $this->option_name, array() );

		// Enqueue Inter font
		echo '<link rel="preconnect" href="https://fonts.googleapis.com">
			  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
			  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">';

		?>
		<div class="wrap naboo-admin-page naboo-settings-wrap" style="font-family: 'Inter', sans-serif;">

			<!-- Header -->
			<div class="naboo-admin-header" style="margin-bottom: 32px; padding: 40px; background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border-radius: 16px; color: white; box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1); position: relative; overflow: hidden;">
				<div style="position: absolute; top: -50px; right: -50px; width: 200px; height: 200px; background: rgba(79, 70, 229, 0.1); filter: blur(80px); border-radius: 50%;"></div>
				<h1 style="color: white !important; font-size: 36px !important; margin: 0 !important; font-weight: 800; letter-spacing: -0.025em; display: flex; align-items: center; gap: 16px;">
					<span style="background: rgba(255,255,255,0.1); width: 64px; height: 64px; display: flex; align-items: center; justify-content: center; border-radius: 16px; backdrop-filter: blur(4px); border: 1px solid rgba(255,255,255,0.1);">⚙️</span>
					<?php esc_html_e( 'Settings & Control Center', 'naboodatabase' ); ?>
				</h1>
				<p style="margin: 16px 0 0 80px !important; color: #94a3b8; font-size: 18px; max-width: 600px; line-height: 1.6;"><?php esc_html_e( 'Manage plugin features, AI integrations, and system access with professional-grade controls.', 'naboodatabase' ); ?></p>
			</div>

			<?php settings_errors( 'naboodatabase_settings_group' ); ?>

			<style>
				.naboo-settings-section { margin-bottom: 56px; }
				.section-title { font-size: 22px; font-weight: 700; color: #1e293b; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; padding-left: 4px; border-left: 4px solid #4f46e5; }
				
				.naboo-admin-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 24px; }
				.naboo-admin-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); overflow: hidden; transition: transform 0.2s ease; }
				.naboo-admin-card:hover { transform: translateY(-2px); }
				.naboo-admin-card.span-full { grid-column: 1 / -1; }
				
				.naboo-admin-card-header { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; background: #f8fafc; display: flex; align-items: center; gap: 12px; }
				.naboo-admin-card-header h3 { margin: 0; font-size: 16px; font-weight: 700; color: #1e293b; }
				.naboo-admin-card-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 16px; }
				
				.naboo-form-section { padding: 24px; }
				.naboo-form-row { margin-bottom: 20px; }
				.naboo-form-row:last-child { margin-bottom: 0; }
				.naboo-form-row label { display: block; font-weight: 600; font-size: 14px; color: #334155; margin-bottom: 8px; }
				.naboo-form-row input[type="text"], .naboo-form-row input[type="number"], .naboo-form-row input[type="email"], .naboo-form-row select, .naboo-form-row input[type="password"] { 
					width: 100%; border-radius: 8px; border: 1px solid #d1d5db; padding: 10px 12px; font-size: 14px; transition: border-color 0.2s; 
				}
				.naboo-form-row input:focus { border-color: #4f46e5; outline: none; box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
				
				.naboo-toggle-row { display: flex; align-items: flex-start; gap: 12px; padding: 12px; border-radius: 8px; transition: background 0.2s; cursor: pointer; }
				.naboo-toggle-row:hover { background: #f8fafc; }
				.toggle-info { display: flex; flex-direction: column; }
				.toggle-info strong { font-size: 14px; color: #1e293b; }
				.toggle-info span { font-size: 12px; color: #64748b; }

				.naboo-save-bar { 
					position: sticky; bottom: 20px; z-index: 100; background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(12px); 
					padding: 20px 32px; border-radius: 16px; border: 1px solid rgba(226, 232, 240, 0.8); box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1); 
					display: flex; justify-content: flex-end; align-items: center; gap: 16px; margin-top: 60px;
				}
				.naboo-btn { padding: 12px 28px; border-radius: 10px; font-weight: 700; font-size: 14px; cursor: pointer; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); border: none; display: inline-flex; align-items: center; gap: 8px; }
				.naboo-btn-primary { background: #4f46e5; color: white; box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2); }
				.naboo-btn-primary:hover { background: #4338ca; transform: translateY(-1px); box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.3); }
				.naboo-btn-secondary { background: #f8fafc; color: #475569; border: 1px solid #e2e8f0; }
				.naboo-btn-secondary:hover { background: #f1f5f9; color: #1e293b; border-color: #cbd5e1; }

				.cols-3 { grid-template-columns: repeat(3, 1fr); }
				.cols-1 { grid-template-columns: 1fr; }

				/* Tab Styling */
				.naboo-tabs-nav {
					display: flex;
					gap: 8px;
					margin-bottom: 32px;
					padding: 6px;
					background: #f1f5f9;
					border-radius: 12px;
					width: fit-content;
				}
				.naboo-tab-btn {
					padding: 10px 20px;
					border-radius: 8px;
					font-weight: 600;
					font-size: 14px;
					color: #64748b;
					cursor: pointer;
					transition: all 0.2s;
					border: none;
					background: transparent;
				}
				.naboo-tab-btn:hover {
					color: #1e293b;
					background: rgba(255,255,255,0.5);
				}
				.naboo-tab-btn.active {
					background: white;
					color: #4f46e5;
					box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
				}
				.naboo-tab-content {
					display: none;
					animation: fadeIn 0.3s ease-out;
				}
				.naboo-tab-content.active {
					display: block;
				}
				@keyframes fadeIn {
					from { opacity: 0; transform: translateY(10px); }
					to { opacity: 1; transform: translateY(0); }
				}
			</style>

			<div class="naboo-tabs-nav">
				<button type="button" class="naboo-tab-btn active" data-tab="general">⚙️ <?php esc_html_e( 'General', 'naboodatabase' ); ?></button>
				<button type="button" class="naboo-tab-btn" data-tab="ai">🤖 <?php esc_html_e( 'AI Integration', 'naboodatabase' ); ?></button>
				<button type="button" class="naboo-tab-btn" data-tab="grokipedia">🌐 <?php esc_html_e( 'Grokipedia Sync', 'naboodatabase' ); ?></button>
				<button type="button" class="naboo-tab-btn" data-tab="system">🛠️ <?php esc_html_e( 'System Tools', 'naboodatabase' ); ?></button>
				<button type="button" class="naboo-tab-btn" data-tab="roles">👥 <?php esc_html_e( 'Roles & Access', 'naboodatabase' ); ?></button>
			</div>

			<form method="post" action="options.php" id="naboo-settings-form">
				<?php settings_fields( 'naboodatabase_settings_group' ); ?>
				<input type="hidden" name="naboo_settings_tab" id="naboo_active_tab_input" value="general">

				<!-- Tab: General -->
				<div id="tab-general" class="naboo-tab-content active">
					<div class="naboo-settings-section">
						<?php (new Tab_General())->render( $options, $this->option_name ); ?>
					</div>
				</div>

				<!-- Tab: AI -->
				<div id="tab-ai" class="naboo-tab-content">
					<div class="naboo-settings-section">
						<?php (new Tab_AI())->render( $options, $this->option_name ); ?>
					</div>
				</div>

				<!-- Tab: Grokipedia Sync -->
				<div id="tab-grokipedia" class="naboo-tab-content">
					<div class="naboo-settings-section">
						<?php (new Tab_Grokipedia())->render(); ?>
					</div>
				</div>

				<!-- Tab: System Tools -->
				<div id="tab-system" class="naboo-tab-content">
					<div class="naboo-settings-section">
						<?php (new Section_System())->render(); ?>
					</div>
				</div>

				<!-- Tab: Roles & Access -->
				<div id="tab-roles" class="naboo-tab-content">
					<div class="naboo-settings-section">
						<?php (new Tab_Roles())->render(); ?>
					</div>
				</div>

				<div class="naboo-save-bar">
					<button type="reset" class="naboo-btn naboo-btn-secondary"><?php esc_html_e( 'Discard', 'naboodatabase' ); ?></button>
					<?php submit_button( __( 'Save All Settings', 'naboodatabase' ), 'primary naboo-btn naboo-btn-primary', 'submit', false ); ?>
				</div>
			</form>

			<script>
				jQuery(document).ready(function($) {
					// Tab switching logic
					$('.naboo-tab-btn').on('click', function() {
						var tabId = $(this).data('tab');
						
						// Update buttons
						$('.naboo-tab-btn').removeClass('active');
						$(this).addClass('active');
						
						// Update content
						$('.naboo-tab-content').removeClass('active');
						$('#tab-' + tabId).addClass('active');
						
						// Update hidden input
						$('#naboo_active_tab_input').val(tabId);

						// Update URL (optional but good for reload/bookmark)
						var url = new URL(window.location.href);
						url.searchParams.set('tab', tabId);
						window.history.replaceState({}, '', url);
					});

					// Load active tab from URL if present
					var urlParams = new URLSearchParams(window.location.search);
					var activeTab = urlParams.get('tab');
					if (activeTab && $('.naboo-tab-btn[data-tab="' + activeTab + '"]').length) {
						$('.naboo-tab-btn[data-tab="' + activeTab + '"]').trigger('click');
					}
				});
			</script>

		</div>

		<?php
	}
}
