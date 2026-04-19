<?php
/**
 * SEO Settings orchestrator
 *
 * @package ArabPsychology\NabooDatabase\Admin
 */

namespace ArabPsychology\NabooDatabase\Admin;

use ArabPsychology\NabooDatabase\Admin\SEO\SEO_Sitemap_Manager;
use ArabPsychology\NabooDatabase\Admin\SEO\SEO_Renderer;

/**
 * SEO_Settings class
 */
class SEO_Settings {

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
	private $option_name = 'naboodatabase_seo_options';

	/**
	 * Sitemap manager
	 * @var SEO_Sitemap_Manager
	 */
	private $sitemap_manager;

	/**
	 * UI Renderer
	 * @var SEO_Renderer
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

		$this->sitemap_manager = new SEO_Sitemap_Manager();
		$this->renderer        = new SEO_Renderer();
	}

	/**
	 * Register admin menu items.
	 */
	public function register_menu() {
		add_submenu_page(
			'naboo-dashboard',
			__( 'SEO & Schema', 'naboodatabase' ),
			__( 'SEO & Schema', 'naboodatabase' ),
			'manage_options',
			'naboodatabase-seo',
			array( $this, 'render_admin_page' ),
			5
		);
	}

	/**
	 * Register settings.
	 */
	public function register_settings() {
		register_setting( 'naboodatabase_seo_group', $this->option_name, array( $this, 'sanitize_options' ) );
	}

	/**
	 * Render the admin page.
	 */
	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'naboodatabase' ) );
		}
		$this->renderer->render_admin_page( $this->option_name );
	}

	/**
	 * Sanitize options.
	 *
	 * @param array $input Input data.
	 * @return array Sanitized data.
	 */
	public function sanitize_options( $input ) {
		$sanitized = array();
		
		// Checkboxes
		$checkboxes = array( 'enable_schema', 'enable_opengraph', 'enable_twitter', 'enable_scholar', 'is_accessible_for_free', 'enable_sitemap' );
		foreach ( $checkboxes as $cb ) {
			$sanitized[$cb] = isset( $input[$cb] ) ? 1 : 0;
		}

		// Text fields
		$texts = array( 'publisher_name', 'default_author', 'default_language' );
		foreach ( $texts as $txt ) {
			$sanitized[$txt] = isset( $input[$txt] ) ? sanitize_text_field( $input[$txt] ) : '';
		}

		// URL fields
		$urls = array( 'publisher_logo_url', 'default_license', 'social_image_url' );
		foreach ( $urls as $url ) {
			$sanitized[$url] = isset( $input[$url] ) ? esc_url_raw( $input[$url] ) : '';
		}

		return $sanitized;
	}

	/**
	 * AJAX Callback: Generate sitemap.
	 */
	public function ajax_generate_sitemap() {
		check_ajax_referer( 'naboo_generate_sitemap' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized.', 'naboodatabase' ) );
		}

		$options = get_option( $this->option_name, array() );
		if ( empty( $options['enable_sitemap'] ) ) {
			wp_send_json_error( __( 'Sitemap generation is disabled in settings.', 'naboodatabase' ) );
		}

		$result = $this->sitemap_manager->generate_sitemap_xml( $options );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		} else {
			wp_send_json_success( sprintf( __( 'Sitemap generated successfully (%d links). Resolvable at /naboo-sitemap.xml', 'naboodatabase' ), $result ) );
		}
	}

	/**
	 * Trigger sitemap update on post save.
	 */
	public function trigger_sitemap_update( $post_id, $post, $update ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( ! isset( $post->post_status ) || $post->post_status !== 'publish' ) return;

		$options = get_option( $this->option_name, array() );
		if ( empty( $options['enable_sitemap'] ) ) return;

		$this->sitemap_manager->generate_sitemap_xml( $options );
		$this->sitemap_manager->ping_search_engines();
	}

	/**
	 * Trigger sitemap update on delete.
	 */
	public function trigger_sitemap_update_on_delete( $post_id ) {
		if ( get_post_type( $post_id ) !== 'psych_scale' ) return;

		$options = get_option( $this->option_name, array() );
		if ( empty( $options['enable_sitemap'] ) ) return;

		$this->sitemap_manager->generate_sitemap_xml( $options );
	}

	/**
	 * Trigger sitemap update on taxonomy edit.
	 */
	public function trigger_sitemap_update_taxonomy( $term_id, $tt_id, $taxonomy ) {
		if ( ! in_array( $taxonomy, array( 'scale_category', 'scale_author' ) ) ) return;

		$options = get_option( $this->option_name, array() );
		if ( empty( $options['enable_sitemap'] ) ) return;

		$this->sitemap_manager->generate_sitemap_xml( $options );
		$this->sitemap_manager->ping_search_engines();
	}

	/**
	 * Cron callback.
	 */
	public function cron_regenerate_sitemap() {
		$options = get_option( $this->option_name, array() );
		if ( empty( $options['enable_sitemap'] ) ) return;

		$this->sitemap_manager->generate_sitemap_xml( $options );
		$this->sitemap_manager->ping_search_engines();
	}

	/**
	 * Inject sitemap into robots.txt.
	 */
	public function inject_sitemap_in_robots( $output ) {
		$options = get_option( $this->option_name, array() );
		if ( empty( $options['enable_sitemap'] ) ) {
			return $output;
		}

		$sitemap_url = home_url( '/naboo-sitemap.xml' );
		if ( strpos( $output, 'naboo-sitemap' ) === false ) {
			$output .= "\nSitemap: " . esc_url( $sitemap_url ) . "\n";
		}

		return $output;
	}

	/**
	 * Handle dynamic sitemap requests.
	 */
	public function dynamic_sitemap_endpoint() {
		$options = get_option( $this->option_name, array() );
		$this->sitemap_manager->handle_dynamic_request( $options );
	}

	/**
	 * Inject canonical and hreflang into head.
	 */
	public function inject_canonical_and_hreflang() {
		if ( ! is_singular( 'psych_scale' ) ) {
			return;
		}

		$options = get_option( $this->option_name, array() );

		// Canonical tag
		if ( empty( $options['disable_canonical'] ) ) {
			$canonical = get_permalink( get_the_ID() );
			echo '<link rel="canonical" href="' . esc_url( $canonical ) . '" />' . "\n";
		}

		// hreflang tag
		if ( ! empty( $options['enable_hreflang'] ) ) {
			$languages = wp_get_object_terms( get_the_ID(), 'scale_language', array( 'fields' => 'names' ) );
			if ( ! is_wp_error( $languages ) ) {
				foreach ( $languages as $lang ) {
					$lang_lower = strtolower( trim( $lang ) );
					if ( in_array( $lang_lower, array( 'arabic', 'ar', 'عربي', 'العربية' ), true ) ) {
						$url = get_permalink( get_the_ID() );
						echo '<link rel="alternate" hreflang="ar" href="' . esc_url( $url ) . '" />' . "\n";
						break;
					}
				}
			}
		}
	}
}
