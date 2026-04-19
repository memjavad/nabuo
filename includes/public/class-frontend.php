<?php
/**
 * Unified Frontend Orchestrator
 *
 * @package ArabPsychology\NabooDatabase\Public
 */

namespace ArabPsychology\NabooDatabase\Public;

/**
 * Frontend class - Minimal orchestrator for frontend assets and shortcodes.
 */
class Frontend {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/naboodatabase-public.css', array(), $this->version, 'all' );
	}

	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/naboodatabase-public.js', array( 'jquery' ), $this->version, false );
		
		wp_localize_script( $this->plugin_name, 'naboo_ajax_obj', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'naboo_search_nonce' )
		));

		wp_enqueue_script(
			$this->plugin_name . '-advanced-search',
			plugin_dir_url( __FILE__ ) . 'js/advanced-search.js',
			array( 'jquery' ),
			$this->version,
			true
		);
		wp_enqueue_style(
			$this->plugin_name . '-advanced-search',
			plugin_dir_url( __FILE__ ) . 'css/advanced-search.css',
			array(),
			$this->version
		);
		wp_localize_script( $this->plugin_name . '-advanced-search', 'nabooAdvancedSearch', array(
			'api_url' => rest_url( 'apa/v1' ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
		) );

		if ( is_post_type_archive( 'psych_scale' ) || is_tax( array( 'scale_category', 'scale_author', 'scale_year', 'scale_language', 'scale_test_type', 'scale_format', 'scale_age_group' ) ) ) {
			wp_enqueue_script(
				$this->plugin_name . '-archive-filter',
				plugin_dir_url( __FILE__ ) . 'js/archive-filter.js',
				array( 'jquery' ),
				$this->version,
				true
			);

			wp_localize_script( $this->plugin_name . '-archive-filter', 'nabooArchiveFilter', array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'naboo_filter_nonce' ),
			) );
		}
	}

	/**
	 * Shortcode: [naboo_search]
	 */
	public function render_search_shortcode( $atts ) {
		ob_start();
		require plugin_dir_path( __FILE__ ) . 'partials/search-form.php';
		return ob_get_clean();
	}

	/**
	 * Shortcode: [naboo_submit]
	 */
	public function render_submission_shortcode( $atts ) {
		$message = '';
		if ( isset( $_POST['naboo_submit_scale_nonce'] ) ) {
			$submission = new Submission();
			$message = $submission->handle_submission();
		}

		ob_start();
		require plugin_dir_path( __FILE__ ) . 'partials/submission-form.php';
		return ob_get_clean();
	}
}
