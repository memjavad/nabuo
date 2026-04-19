<?php
/**
 * Performance Metrics Dashboard
 *
 * @package ArabPsychology\NabooDatabase\Admin
 */

namespace ArabPsychology\NabooDatabase\Admin;

/**
 * Performance_Metrics_Dashboard class - Track system performance metrics.
 */
class Performance_Metrics_Dashboard {

	/**
	 * Plugin name
	 *
	 * @var string
	 */
	private $plugin_name;

	/**
	 * Plugin version
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Constructor
	 *
	 * @param string $plugin_name The plugin name.
	 * @param string $version     The plugin version.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->create_table();
	}

	/**
	 * Create performance metrics table
	 */
	private function create_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_performance_metrics';

		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === null ) {
			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE $table_name (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				metric_type varchar(100),
				metric_value float,
				metric_unit varchar(50),
				endpoint varchar(255),
				response_time int,
				memory_usage bigint(20),
				database_queries int,
				recorded_at datetime DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY metric_type (metric_type),
				KEY endpoint (endpoint),
				KEY recorded_at (recorded_at)
			) $charset_collate;";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
		}
	}

	/**
	 * Register REST API endpoints
	 */
	public function register_endpoints() {
		register_rest_route(
			'apa/v1',
			'/performance/system-health',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_system_health' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/performance/endpoint-metrics',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_endpoint_metrics' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/performance/database-stats',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_database_stats' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/performance/resource-usage',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_resource_usage' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/performance/record-metric',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'record_metric' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Get system health
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_system_health( $request ) {
		$health = array(
			'php_version'           => PHP_VERSION,
			'wp_version'            => get_bloginfo( 'version' ),
			'plugin_version'        => NABOODATABASE_VERSION,
			'memory_limit'          => ini_get( 'memory_limit' ),
			'max_execution_time'    => ini_get( 'max_execution_time' ),
			'upload_max_filesize'   => ini_get( 'upload_max_filesize' ),
			'post_max_size'         => ini_get( 'post_max_size' ),
		);

		// Check database connection
		global $wpdb;
		try {
			$wpdb->query( 'SELECT 1' );
			$health['database_status'] = 'healthy';
		} catch ( \Exception $e ) {
			$health['database_status'] = 'error';
			$health['database_error'] = $e->getMessage();
		}

		// Check plugin tables
		$tables = array(
			'naboo_favorites',
			'naboo_ratings',
			'naboo_comments',
			'naboo_collections',
			'naboo_popularity_analytics',
			'naboo_user_analytics',
			'naboo_search_analytics',
			'naboo_performance_metrics',
		);

		$missing_tables = array();
		$existing_tables = $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $wpdb->prefix ) . '%' ) );

		if ( is_array( $existing_tables ) ) {
			$existing_tables_flipped = array_flip( $existing_tables );
			foreach ( $tables as $table ) {
				if ( ! isset( $existing_tables_flipped[ $wpdb->prefix . $table ] ) ) {
					$missing_tables[] = $table;
				}
			}
		} else {
			$missing_tables = $tables;
		}

		$health['tables_status'] = empty( $missing_tables ) ? 'healthy' : 'warning';
		if ( ! empty( $missing_tables ) ) {
			$health['missing_tables'] = $missing_tables;
		}

		return new \WP_REST_Response( $health, 200 );
	}

	/**
	 * Get endpoint metrics
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_endpoint_metrics( $request ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_performance_metrics';

		$metrics = $wpdb->get_results(
			"SELECT endpoint, 
					COUNT(*) as request_count,
					AVG(response_time) as avg_response_time,
					MAX(response_time) as max_response_time,
					MIN(response_time) as min_response_time,
					AVG(memory_usage) as avg_memory,
					AVG(database_queries) as avg_queries
			FROM $table_name 
			WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
			GROUP BY endpoint
			ORDER BY request_count DESC
			LIMIT 20"
		);

		return new \WP_REST_Response( array( 'endpoints' => $metrics ), 200 );
	}

	/**
	 * Get database statistics
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_database_stats( $request ) {
		global $wpdb;

		// Get table sizes
		$tables = $wpdb->get_results(
			"SELECT table_name, 
					(data_length + index_length) / 1024 / 1024 as size_mb,
					table_rows as row_count
			FROM information_schema.TABLES 
			WHERE table_schema = DATABASE() 
			AND table_name LIKE '" . $wpdb->prefix . "apa%'
			ORDER BY (data_length + index_length) DESC"
		);

		$total_size = 0;
		$total_rows = 0;

		foreach ( $tables as $table ) {
			$total_size += $table->size_mb;
			$total_rows += $table->row_count;
		}

		return new \WP_REST_Response(
			array(
				'tables'     => $tables,
				'total_size' => round( $total_size, 2 ),
				'total_rows' => $total_rows,
			),
			200
		);
	}

	/**
	 * Get resource usage
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_resource_usage( $request ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_performance_metrics';

		// Get average metrics from last 24 hours
		$metrics = $wpdb->get_row(
			"SELECT 
					AVG(response_time) as avg_response_time,
					MAX(response_time) as max_response_time,
					AVG(memory_usage) / 1024 / 1024 as avg_memory_mb,
					MAX(memory_usage) / 1024 / 1024 as max_memory_mb,
					AVG(database_queries) as avg_queries,
					COUNT(*) as total_requests
			FROM $table_name 
			WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
		);

		// Get current memory usage
		$current_memory = memory_get_peak_usage( true ) / 1024 / 1024;

		return new \WP_REST_Response(
			array(
				'avg_response_time'  => round( $metrics->avg_response_time ?? 0, 2 ),
				'max_response_time'  => round( $metrics->max_response_time ?? 0, 2 ),
				'avg_memory_mb'      => round( $metrics->avg_memory_mb ?? 0, 2 ),
				'max_memory_mb'      => round( $metrics->max_memory_mb ?? 0, 2 ),
				'current_memory_mb'  => round( $current_memory, 2 ),
				'avg_queries'        => round( $metrics->avg_queries ?? 0, 2 ),
				'total_requests'     => $metrics->total_requests ?? 0,
			),
			200
		);
	}

	/**
	 * Record performance metric
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function record_metric( $request ) {
		$metric_type = sanitize_text_field( $request->get_param( 'metric_type' ) );
		$metric_value = (float) $request->get_param( 'metric_value' );
		$metric_unit = sanitize_text_field( $request->get_param( 'metric_unit' ) );
		$endpoint = sanitize_text_field( $request->get_param( 'endpoint' ) );
		$response_time = (int) $request->get_param( 'response_time' ) ?? 0;
		$memory_usage = (int) $request->get_param( 'memory_usage' ) ?? 0;
		$database_queries = (int) $request->get_param( 'database_queries' ) ?? 0;

		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_performance_metrics';

		$result = $wpdb->insert(
			$table_name,
			array(
				'metric_type'     => $metric_type,
				'metric_value'    => $metric_value,
				'metric_unit'     => $metric_unit,
				'endpoint'        => $endpoint,
				'response_time'   => $response_time,
				'memory_usage'    => $memory_usage,
				'database_queries' => $database_queries,
			),
			array( '%s', '%f', '%s', '%s', '%d', '%d', '%d' )
		);

		if ( $result ) {
			return new \WP_REST_Response( array( 'message' => 'Metric recorded' ), 200 );
		}

		return new \WP_REST_Response( array( 'error' => 'Failed to record metric' ), 500 );
	}
}
