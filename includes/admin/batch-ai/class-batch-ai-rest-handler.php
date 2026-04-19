<?php
/**
 * Batch AI REST Handler - Handles REST API for batch processor
 *
 * @package ArabPsychology\NabooDatabase\Admin\Batch_AI
 */

namespace ArabPsychology\NabooDatabase\Admin\Batch_AI;

use ArabPsychology\NabooDatabase\Core\Installer;

/**
 * Batch_AI_REST_Handler class
 */
class Batch_AI_REST_Handler {

	/**
	 * Processor instance
	 *
	 * @var Batch_AI_Processor
	 */
	private $processor;

	/**
	 * Remote Sync instance
	 *
	 * @var Batch_AI_Remote_Sync
	 */
	private $remote_sync;

	/**
	 * Constructor
	 */
	public function __construct( Batch_AI_Processor $processor, Batch_AI_Remote_Sync $remote_sync ) {
		$this->processor   = $processor;
		$this->remote_sync = $remote_sync;
	}

	/**
	 * Register REST API routes
	 */
	public function register_rest_routes() {
		$ns = 'naboo-db/v1';

		register_rest_route( $ns, '/import-page', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'rest_import_page' ),
			'permission_callback' => function() { return current_user_can( 'manage_options' ); },
			'args'                => array(
				'page' => array( 'default' => 1, 'sanitize_callback' => 'absint' ),
			),
		) );

		register_rest_route( $ns, '/import-status', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'rest_import_status' ),
			'permission_callback' => function() { return current_user_can( 'manage_options' ); },
		) );

		register_rest_route( $ns, '/toggle-auto-import', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'rest_toggle_auto_import' ),
			'permission_callback' => function() { return current_user_can( 'manage_options' ); },
		) );

		register_rest_route( $ns, '/process-draft', array(
			'methods'             => 'POST',
			'callback'            => array( $this->processor, 'rest_process_draft' ),
			'permission_callback' => function() { return current_user_can( 'manage_options' ); },
			'args'                => array(
				'draft_id' => array( 'required' => true, 'sanitize_callback' => 'absint' ),
			),
		) );

		register_rest_route( $ns, '/enqueue-batch', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'rest_enqueue_batch' ),
			'permission_callback' => function() { return current_user_can( 'manage_options' ); },
		) );

		register_rest_route( $ns, '/queue-stats', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'rest_queue_stats' ),
			'permission_callback' => function() { return current_user_can( 'manage_options' ); },
		) );

		register_rest_route( $ns, '/retry-draft', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'rest_retry_draft' ),
			'permission_callback' => function() { return current_user_can( 'manage_options' ); },
			'args'                => array(
				'draft_id' => array( 'required' => true, 'sanitize_callback' => 'absint' ),
			),
		) );
	}

	public function rest_import_page( $request ) {
		$page   = max( 1, (int) $request->get_param( 'page' ) );
		$result = $this->remote_sync->do_import_page( $page );
		if ( is_wp_error( $result ) ) {
			return new \WP_REST_Response( array( 'message' => $result->get_error_message() ), 500 );
		}
		return rest_ensure_response( $result );
	}

	public function rest_import_status( $request ) {
		return rest_ensure_response( array(
			'auto_import' => (bool) get_option( 'naboo_full_auto_import', 0 ),
			'last_page'   => (int) get_option( 'naboo_remote_last_page', 0 ),
			'log_count'   => Installer::get_log_count(),
		) );
	}

	public function rest_toggle_auto_import( $request ) {
		$params = json_decode( $request->get_body(), true );
		$enable = isset( $params['enable'] ) ? (bool) $params['enable'] : false;

		if ( $enable ) {
			update_option( 'naboo_full_auto_import', 1 );
			if ( ! wp_next_scheduled( 'naboo_full_auto_import_event' ) ) {
				wp_schedule_event( time(), 'naboo_5min', 'naboo_full_auto_import_event' );
			}
		} else {
			update_option( 'naboo_full_auto_import', 0 );
			$ts = wp_next_scheduled( 'naboo_full_auto_import_event' );
			if ( $ts ) {
				wp_unschedule_event( $ts, 'naboo_full_auto_import_event' );
			}
		}

		return rest_ensure_response( array( 'auto_import' => $enable ) );
	}

	public function rest_enqueue_batch( $request ) {
		$draft_ids = get_posts( array(
			'post_type'      => 'naboo_raw_draft',
			'post_status'    => array( 'publish', 'draft', 'pending' ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
		) );

		$inserted = Installer::enqueue_drafts( $draft_ids );
		$stats    = Installer::get_queue_stats();

		return rest_ensure_response( array(
			'enqueued' => $inserted,
			'total'    => count( $draft_ids ),
			'stats'    => $stats,
		) );
	}

	public function rest_queue_stats( $request ) {
		$stats  = Installer::get_queue_stats();
		$failed = Installer::get_failed_items();
		return rest_ensure_response( array(
			'stats'  => $stats,
			'failed' => $failed,
		) );
	}

	public function rest_retry_draft( $request ) {
		$draft_id = $request->get_param( 'draft_id' );
		Installer::retry_draft( $draft_id );
		return $this->processor->rest_process_draft( $request );
	}
}
