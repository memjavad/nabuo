<?php
/**
 * Import REST Handler - Handles REST API endpoints for bulk import
 *
 * @package ArabPsychology\NabooDatabase\Admin\Import
 */

namespace ArabPsychology\NabooDatabase\Admin\Import;

/**
 * Import_REST_Handler class
 */
class Import_REST_Handler {

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
	 * Constructor
	 *
	 * @param Import_Log_Manager $log_manager Log manager instance.
	 * @param Import_Processor   $processor   Processor instance.
	 */
	public function __construct( $log_manager, $processor ) {
		$this->log_manager = $log_manager;
		$this->processor   = $processor;
	}

	/**
	 * Register REST API endpoints
	 */
	public function register_endpoints() {
		register_rest_route(
			'apa/v1',
			'/import/validate',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'validate_import_file' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/import/preview',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'preview_import' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/import/process',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'process_import' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/import/logs',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_import_logs' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}

	/**
	 * Validate import file
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function validate_import_file( $request ) {
		$params = $request->get_file_params();

		if ( empty( $params['file'] ) ) {
			return new \WP_REST_Response( array( 'error' => 'No file provided' ), 400 );
		}

		$file = $params['file'];

		if ( ! in_array( $file['type'], array( 'text/csv', 'application/json' ), true ) ) {
			return new \WP_REST_Response( array( 'error' => 'Invalid file type. Only CSV and JSON supported.' ), 400 );
		}

		if ( $file['size'] > 10 * 1024 * 1024 ) {
			return new \WP_REST_Response( array( 'error' => 'File too large. Maximum 10MB allowed.' ), 400 );
		}

		$content = file_get_contents( $file['tmp_name'] );
		$rows    = $this->processor->parse_file( $content, $file['type'] );

		if ( ! $rows ) {
			return new \WP_REST_Response( array( 'error' => 'Invalid file format' ), 400 );
		}

		return new \WP_REST_Response(
			array(
				'valid'       => true,
				'row_count'   => count( $rows ),
				'sample_rows' => array_slice( $rows, 0, 2 ),
			),
			200
		);
	}

	/**
	 * Preview import
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function preview_import( $request ) {
		$params = $request->get_file_params();

		if ( empty( $params['file'] ) ) {
			return new \WP_REST_Response( array( 'error' => 'No file provided' ), 400 );
		}

		$file    = $params['file'];
		$content = file_get_contents( $file['tmp_name'] );
		$rows    = $this->processor->parse_file( $content, $file['type'] );

		return new \WP_REST_Response(
			array(
				'preview_rows' => array_slice( $rows, 0, 5 ),
				'total_rows'   => count( $rows ),
			),
			200
		);
	}

	/**
	 * Process import
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function process_import( $request ) {
		$params = $request->get_file_params();

		if ( empty( $params['file'] ) ) {
			return new \WP_REST_Response( array( 'error' => 'No file provided' ), 400 );
		}

		$file           = $params['file'];
		$content        = file_get_contents( $file['tmp_name'] );
		$rows           = $this->processor->parse_file( $content, $file['type'] );
		$successful     = 0;
		$failed         = 0;
		$import_results = array();

		foreach ( $rows as $row ) {
			$result = $this->processor->import_scale( $row );

			if ( $result['success'] ) {
				$successful++;
				$import_results[] = array(
					'row'      => $row,
					'success'  => true,
					'scale_id' => $result['scale_id'],
				);
			} else {
				$failed++;
				$import_results[] = array(
					'row'    => $row,
					'success' => false,
					'error'  => $result['error'],
				);
			}
		}

		$this->log_manager->log_import(
			basename( $file['name'] ),
			count( $rows ),
			$successful,
			$failed
		);

		return new \WP_REST_Response(
			array(
				'total'       => count( $rows ),
				'successful'  => $successful,
				'failed'      => $failed,
				'results'     => $import_results,
			),
			200
		);
	}

	/**
	 * Get import logs
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_import_logs( $request ) {
		$logs = $this->log_manager->get_logs();
		return new \WP_REST_Response( array( 'logs' => $logs ), 200 );
	}
}
