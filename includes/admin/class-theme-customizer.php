<?php
/**
 * Advanced Theme Customizer with Frontend Override
 *
 * @package ArabPsychology\NabooDatabase\Admin
 */

namespace ArabPsychology\NabooDatabase\Admin;

use ArabPsychology\NabooDatabase\Admin\Theme\Theme_Settings_Manager;
use ArabPsychology\NabooDatabase\Admin\Theme\Theme_Renderer;

/**
 * Theme_Customizer class - Admin interface orchestrator for theme customization
 */
class Theme_Customizer {

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
	 * Option name
	 * @var string
	 */
	private $option_name = 'naboodatabase_customizer_options';

	/**
	 * Settings manager
	 * @var Theme_Settings_Manager
	 */
	private $settings_manager;

	/**
	 * UI renderer
	 * @var Theme_Renderer
	 */
	private $renderer;

	/**
	 * Constructor
	 *
	 * @param string $plugin_name Plugin name.
	 * @param string $version     Plugin version.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		// Initialize sub-managers
		$this->settings_manager = new Theme_Settings_Manager();
		$this->renderer         = new Theme_Renderer();
	}

	/**
	 * Enqueue scripts and styles
	 */
	public function enqueue_scripts() {
		wp_enqueue_style( 'naboodatabase-customizer', plugin_dir_url( __FILE__ ) . 'css/naboodatabase-customizer.css', array(), $this->version, 'all' );
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_media();
		wp_enqueue_script( 'wp-util' );
		
		wp_enqueue_script( 'naboodatabase-customizer-js', plugin_dir_url( __FILE__ ) . 'js/naboodatabase-customizer.js', array( 'jquery', 'wp-color-picker' ), $this->version, true );
	}

	/**
	 * Register admin menu
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'naboo-dashboard',
			__( 'Naboo Theme Customizer', 'naboodatabase' ),
			'🎨 Theme',
			'manage_options',
			'naboodatabase-customizer',
			array( $this, 'render_customizer_page' ),
			3
		);
	}

	/**
	 * Register settings with WordPress. Delegates to Settings Manager.
	 */
	public function register_settings() {
		$this->settings_manager->register_settings();
	}

	/**
	 * Render the main customizer page.
	 */
	public function render_customizer_page() {
		$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'general';
		$options    = get_option( $this->option_name, array() );
		?>
		<div class="wrap naboodatabase-customizer-wrap">
			<div class="customizer-header">
				<h1>
					<span class="dashicon dashicons-art"></span>
					<?php _e( 'Naboo Theme Customizer', 'naboodatabase' ); ?>
				</h1>
				<p class="subtitle"><?php _e( 'Customize every aspect of your theme frontend appearance', 'naboodatabase' ); ?></p>
			</div>

			<div class="customizer-container">
				<!-- Tabs Navigation -->
				<div class="customizer-tabs">
					<?php 
					$tabs = array(
						'general'    => array( '⚙️', 'General' ),
						'header'     => array( '🏠', 'Header' ),
						'icons'      => array( '🖼️', 'Icons' ),
						'colors'     => array( '🎨', 'Colors' ),
						'typography' => array( '📝', 'Typography' ),
						'buttons'    => array( '🔘', 'Buttons' ),
						'forms'      => array( '📋', 'Forms' ),
						'cards'      => array( '🎴', 'Cards' ),
						'layout'     => array( '📐', 'Layout' ),
						'footer'     => array( '🏁', 'Footer' ),
						'advanced'   => array( '⚡', 'Advanced' ),
					);

					foreach ( $tabs as $slug => $data ) : ?>
						<a href="?page=naboodatabase-customizer&tab=<?php echo esc_attr( $slug ); ?>" class="tab-link <?php echo $active_tab === $slug ? 'active' : ''; ?>">
							<span class="tab-icon"><?php echo esc_html( $data[0] ); ?></span> <?php echo esc_html( $data[1] ); ?>
						</a>
					<?php endforeach; ?>
				</div>

				<!-- Tab Content -->
				<form method="post" action="options.php" class="customizer-form">
					<input type="hidden" name="naboo_active_tab" value="<?php echo esc_attr( $active_tab ); ?>" />
					<?php
					settings_fields( 'naboodatabase_customizer_group' );
					$this->renderer->render_tab_content( $active_tab, $options );
					submit_button( __( 'Save Changes', 'naboodatabase' ), 'primary large' );
					?>
				</form>

				<!-- Live Preview Panel -->
				<div class="customizer-preview">
					<div class="preview-header">
						<h3><?php _e( 'Live Preview', 'naboodatabase' ); ?></h3>
						<p><?php _e( 'Changes appear instantly here', 'naboodatabase' ); ?></p>
					</div>
					<div class="preview-content" id="customizer-preview">
						<div class="preview-card" style="
							background: <?php echo isset( $options['card_bg_color'] ) ? esc_attr( $options['card_bg_color'] ) : '#ffffff'; ?>;
							border-left: <?php echo isset( $options['card_border_width'] ) ? intval( $options['card_border_width'] ) : 5; ?>px solid <?php echo isset( $options['accent_color'] ) ? esc_attr( $options['accent_color'] ) : '#00796b'; ?>;
							border-radius: <?php echo isset( $options['card_radius'] ) ? intval( $options['card_radius'] ) : 10; ?>px;
							padding: <?php echo isset( $options['card_padding'] ) ? intval( $options['card_padding'] ) : 32; ?>px;
						">
							<h3 style="color: <?php echo isset( $options['primary_color'] ) ? esc_attr( $options['primary_color'] ) : '#1a3a52'; ?>; margin-top: 0;">
								<?php _e( 'Preview Card', 'naboodatabase' ); ?>
							</h3>
							<p style="color: <?php echo isset( $options['text_light_color'] ) ? esc_attr( $options['text_light_color'] ) : '#555'; ?>;">
								<?php _e( 'This is how your content cards will look with the selected styles.', 'naboodatabase' ); ?>
							</p>
							<button class="preview-button" style="
								background: <?php echo isset( $options['button_primary_color'] ) ? esc_attr( $options['button_primary_color'] ) : '#00796b'; ?>;
								color: <?php echo isset( $options['button_text_color'] ) ? esc_attr( $options['button_text_color'] ) : '#ffffff'; ?>;
								padding: <?php echo isset( $options['button_padding_v'] ) ? intval( $options['button_padding_v'] ) : 12; ?>px <?php echo isset( $options['button_padding_h'] ) ? intval( $options['button_padding_h'] ) : 24; ?>px;
								border-radius: <?php echo isset( $options['button_radius'] ) ? intval( $options['button_radius'] ) : 6; ?>px;
							">
								<?php _e( 'Click Me', 'naboodatabase' ); ?>
							</button>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Proxy for sanitization (if needed for hooks)
	 */
	public function sanitize_options( $input ) {
		return $this->settings_manager->sanitize_options( $input );
	}

	/**
	 * Render reset button (utility)
	 */
	public function render_reset_button( $args ) {
		echo '<button type="button" class="button button-danger" id="reset-theme-btn">';
		_e( 'Reset to Defaults', 'naboodatabase' );
		echo '</button>';
	}
}
