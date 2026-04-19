<?php
/**
 * Bulk Import Tool orchestrator
 *
 * @package ArabPsychology\NabooDatabase\Admin
 */

namespace ArabPsychology\NabooDatabase\Admin;

use ArabPsychology\NabooDatabase\Admin\Import\Import_Log_Manager;
use ArabPsychology\NabooDatabase\Admin\Import\Import_Processor;
use ArabPsychology\NabooDatabase\Admin\Import\Import_REST_Handler;
use ArabPsychology\NabooDatabase\Admin\Import\Import_Renderer;

/**
 * Bulk_Import_Tool class
 */
class Bulk_Import_Tool {

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
	 * Log manager
	 * @var Import_Log_Manager
	 */
	private $log_manager;

	/**
	 * Processor
	 * @var Import_Processor
	 */
	private $processor;

	/**
	 * REST Handler
	 * @var Import_REST_Handler
	 */
	private $rest_handler;

	/**
	 * UI Renderer
	 * @var Import_Renderer
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

		$this->log_manager  = new Import_Log_Manager();
		$this->processor    = new Import_Processor();
		$this->rest_handler = new Import_REST_Handler( $this->log_manager, $this->processor );
		$this->renderer     = new Import_Renderer( $plugin_name, $version );
	}

	/**
	 * Register REST API endpoints
	 */
	public function register_endpoints() {
		$this->rest_handler->register_endpoints();
	}

	/**
	 * Register Admin Menu
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'naboo-dashboard',
			__( 'Bulk Import tool', 'naboodatabase' ),
			__( '⬇️ Bulk Import', 'naboodatabase' ),
			'manage_options',
			'naboo-bulk-import',
			array( $this, 'render_admin_page' ),
			10
		);
	}

	/**
	 * Enqueue Admin Scripts
	 *
	 * @param string $hook Admin page hook.
	 */
	public function enqueue_admin_scripts( $hook ) {
		$this->renderer->enqueue_scripts( $hook );
	}

	/**
	 * Render Admin Page UI
	 */
	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'naboodatabase' ) );
		}
		$this->renderer->render_page();
	}
}
