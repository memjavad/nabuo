<?php
/**
 * Naboo Search Admin
 * Admin dashboard page for search index management, statistics, and settings.
 *
 * @package ArabPsychology\NabooDatabase\Admin
 */

namespace ArabPsychology\NabooDatabase\Admin;

use ArabPsychology\NabooDatabase\Admin\Search\Search_Index_Manager;
use ArabPsychology\NabooDatabase\Admin\Search\Search_Stats_Calculator;
use ArabPsychology\NabooDatabase\Admin\Search\Search_Admin_View;

/**
 * Search_Admin class - Admin interface orchestrator for search engine management
 */
class Search_Admin {

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
	 * Index manager
	 * @var Search_Index_Manager
	 */
	private $index_manager;

	/**
	 * Stats calculator
	 * @var Search_Stats_Calculator
	 */
	private $stats_calculator;

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
		$this->index_manager    = new Search_Index_Manager();
		$this->stats_calculator = new Search_Stats_Calculator();
	}

	/**
	 * Register submenu under NABOO Dashboard.
	 */
	public function register_menu() {
		add_submenu_page(
			'naboo-dashboard',
			__( 'Search Engine', 'naboodatabase' ),
			__( '🔍 Search Engine', 'naboodatabase' ),
			'manage_options',
			'naboo-search-admin',
			array( $this, 'render_page' ),
			4
		);
	}

	/**
	 * Handle AJAX/form actions on this page.
	 */
	public function handle_actions() {
		if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'naboo-search-admin' ) {
			return;
		}

		// Rebuild index action
		if ( isset( $_POST['naboo_rebuild_index'] ) && check_admin_referer( 'naboo_search_action' ) ) {
			if ( current_user_can( 'manage_options' ) ) {
				$count = $this->index_manager->do_bulk_sync();
				add_settings_error(
					'naboo_search_notices',
					'index_rebuilt',
					sprintf( __( '✅ Index rebuilt successfully. %d scales synced.', 'naboodatabase' ), $count ),
					'success'
				);
			}
		}

		// Clear cache action
		if ( isset( $_POST['naboo_clear_cache'] ) && check_admin_referer( 'naboo_search_action' ) ) {
			if ( current_user_can( 'manage_options' ) ) {
				$this->index_manager->clear_cache();
				add_settings_error(
					'naboo_search_notices',
					'cache_cleared',
					__( '✅ Search filter cache cleared.', 'naboodatabase' ),
					'success'
				);
			}
		}

		// Save settings action
		if ( isset( $_POST['naboo_save_search_settings'] ) && check_admin_referer( 'naboo_search_action' ) ) {
			if ( current_user_can( 'manage_options' ) ) {
				$settings = array(
					'results_per_page' => absint( $_POST['results_per_page'] ?? 20 ),
					'enable_fulltext'  => ! empty( $_POST['enable_fulltext'] ) ? 1 : 0,
					'auto_sync'        => ! empty( $_POST['auto_sync'] ) ? 1 : 0,
					'min_word_length'  => absint( $_POST['min_word_length'] ?? 3 ),
				);
				update_option( 'naboo_search_settings', $settings );
				add_settings_error(
					'naboo_search_notices',
					'settings_saved',
					__( '✅ Search settings saved.', 'naboodatabase' ),
					'success'
				);
			}
		}
	}

	/**
	 * Render the admin page.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'naboodatabase' ) );
		}

		$this->handle_actions();

		$tab      = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'overview';
		$stats    = $this->stats_calculator->get_index_stats();
		$settings = get_option( 'naboo_search_settings', array(
			'results_per_page' => 20,
			'enable_fulltext'  => 1,
			'auto_sync'        => 1,
			'min_word_length'  => 3,
		) );
		$cache_active = (bool) get_transient( 'naboo_search_filters_cache' );
		$status_counts = $this->stats_calculator->get_post_status_diagnostics();

		$view = new Search_Admin_View();
		$view->render_page( $tab, $stats, $settings, $cache_active, $status_counts );
	}
}
