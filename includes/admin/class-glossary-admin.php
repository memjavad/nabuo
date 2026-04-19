<?php
/**
 * Glossary Admin Functionality
 *
 * @package ArabPsychology\NabooDatabase\Admin
 */

namespace ArabPsychology\NabooDatabase\Admin;

use ArabPsychology\NabooDatabase\Admin\Glossary\Glossary_Metabox_Handler;
use ArabPsychology\NabooDatabase\Admin\Glossary\Glossary_Renderer;

/**
 * Glossary_Admin class - Admin interface orchestrator for glossary management.
 */
class Glossary_Admin {

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
	 * Metabox handler
	 * @var Glossary_Metabox_Handler
	 */
	private $metabox_handler;

	/**
	 * UI Renderer
	 * @var Glossary_Renderer
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
		$this->metabox_handler = new Glossary_Metabox_Handler();
		$this->renderer        = new Glossary_Renderer();
	}

	/**
	 * Add admin menu items.
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'naboo-dashboard',
			__( 'Glossary & Index', 'naboodatabase' ),
			__( '📚 Glossary & Index', 'naboodatabase' ),
			'manage_options',
			'naboo-glossary-settings',
			array( $this, 'render_settings_page' ),
			4
		);
	}

	/**
	 * Render the instructions page.
	 */
	public function render_instructions_page() {
		$this->renderer->render_instructions_page();
	}

	/**
	 * Render Glossary & Index Settings Page
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'naboodatabase' ) );
		}
		$this->renderer->render_settings_page();
	}

	/**
	 * Register admin columns for glossary terms.
	 */
	public function manage_columns( $columns ) {
		$new_columns = array();
		$new_columns['cb']                = $columns['cb'];
		$new_columns['title']             = _x( 'Term (English)', 'column name', 'naboodatabase' );
		$new_columns['arabic_term']       = __( 'Arabic Term', 'naboodatabase' );
		$new_columns['glossary_category'] = __( 'Category', 'naboodatabase' );
		$new_columns['date']              = $columns['date'];

		return $new_columns;
	}

	/**
	 * Render custom column content.
	 */
	public function manage_custom_column( $column, $post_id ) {
		switch ( $column ) {
			case 'arabic_term':
				echo esc_html( get_post_meta( $post_id, '_naboo_glossary_arabic', true ) );
				break;
			case 'glossary_category':
				echo get_the_term_list( $post_id, 'glossary_category', '', ', ' );
				break;
		}
	}

	/**
	 * Add metaboxes for glossary terms.
	 */
	public function add_meta_boxes() {
		$this->metabox_handler->register_metaboxes( 'naboo_glossary' );
	}

	/**
	 * Save metabox data.
	 */
	public function save_meta_box_data( $post_id ) {
		$this->metabox_handler->save_data( $post_id );
	}
}
