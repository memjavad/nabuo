<?php
/**
 * Search Analytics & Trends
 *
 * @package ArabPsychology\NabooDatabase\Public
 */

namespace ArabPsychology\NabooDatabase\Public;

/**
 * Search_Analytics_Trends class - Track search queries and display trends.
 */
class Search_Analytics_Trends {

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
	 * Create search analytics table
	 */
	private function create_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_search_analytics';

		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === null ) {
			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE $table_name (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				user_id bigint(20),
				search_query varchar(255),
				results_count int DEFAULT 0,
				filters_applied varchar(255),
				clicked_scale_id bigint(20),
				session_id varchar(100),
				created_at datetime DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY user_id (user_id),
				KEY search_query (search_query),
				KEY created_at (created_at),
				KEY session_id (session_id)
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
			'/analytics/search-trends',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_search_trends' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'apa/v1',
			'/analytics/popular-searches',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_popular_searches' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'apa/v1',
			'/analytics/search-suggestions',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_search_suggestions' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'apa/v1',
			'/analytics/track-search',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'track_search' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'apa/v1',
			'/analytics/search-analytics-report',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_search_analytics_report' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}

	/**
	 * Get search trends
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_search_trends( $request ) {
		$period = $request->get_param( 'period' ) ?? 'week'; // week, month, year

		$interval = 'INTERVAL 7 DAY';
		if ( 'month' === $period ) {
			$interval = 'INTERVAL 30 DAY';
		} elseif ( 'year' === $period ) {
			$interval = 'INTERVAL 365 DAY';
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_search_analytics';

		$trends = $wpdb->get_results(
			"SELECT DATE(created_at) as date, COUNT(*) as count FROM $table_name 
			WHERE created_at >= DATE_SUB(NOW(), $interval) 
			GROUP BY DATE(created_at) 
			ORDER BY date ASC"
		);

		return new \WP_REST_Response( array( 'trends' => $trends ), 200 );
	}

	/**
	 * Get popular searches
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_popular_searches( $request ) {
		$limit = (int) $request->get_param( 'limit' ) ?? 10;

		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_search_analytics';

		$popular = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT search_query, COUNT(*) as count, AVG(results_count) as avg_results 
				FROM $table_name 
				WHERE search_query IS NOT NULL 
				GROUP BY search_query 
				ORDER BY count DESC 
				LIMIT %d",
				$limit
			)
		);

		return new \WP_REST_Response( array( 'popular_searches' => $popular ), 200 );
	}

	/**
	 * Get search suggestions based on analytics
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_search_suggestions( $request ) {
		$query = $request->get_param( 'query' );

		if ( ! $query || strlen( $query ) < 2 ) {
			return new \WP_REST_Response( array( 'suggestions' => array() ), 200 );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_search_analytics';

		$suggestions = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DISTINCT search_query FROM $table_name 
				WHERE search_query LIKE %s 
				GROUP BY search_query 
				ORDER BY COUNT(*) DESC 
				LIMIT 10",
				'%' . $wpdb->esc_like( $query ) . '%'
			)
		);

		return new \WP_REST_Response( array( 'suggestions' => wp_list_pluck( $suggestions, 'search_query' ) ), 200 );
	}

	/**
	 * Track search event
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function track_search( $request ) {
		$query = sanitize_text_field( $request->get_param( 'query' ) );
		$results_count = (int) $request->get_param( 'results_count' ) ?? 0;
		$filters = $request->get_param( 'filters' ) ? wp_json_encode( $request->get_param( 'filters' ) ) : null;
		$clicked_scale_id = (int) $request->get_param( 'clicked_scale_id' ) ?? null;

		$user_id = get_current_user_id();
		$session_id = $request->get_param( 'session_id' ) ?? wp_hash( time() );

		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_search_analytics';

		$result = $wpdb->insert(
			$table_name,
			array(
				'user_id'         => $user_id ? $user_id : null,
				'search_query'    => $query,
				'results_count'   => $results_count,
				'filters_applied' => $filters,
				'clicked_scale_id' => $clicked_scale_id,
				'session_id'      => $session_id,
			),
			array( '%d', '%s', '%d', '%s', '%d', '%s' )
		);

		if ( $result ) {
			return new \WP_REST_Response( array( 'message' => 'Search tracked' ), 200 );
		}

		return new \WP_REST_Response( array( 'error' => 'Failed to track search' ), 500 );
	}

	/**
	 * Get search analytics report
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_search_analytics_report( $request ) {
		$start_date = $request->get_param( 'start_date' ) ?? date( 'Y-m-d', strtotime( '-30 days' ) );
		$end_date = $request->get_param( 'end_date' ) ?? date( 'Y-m-d' );

		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_search_analytics';

		// Total searches
		$total_searches = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $table_name WHERE created_at BETWEEN %s AND %s",
				$start_date . ' 00:00:00',
				$end_date . ' 23:59:59'
			)
		);

		// Unique users
		$unique_users = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT user_id) FROM $table_name WHERE created_at BETWEEN %s AND %s",
				$start_date . ' 00:00:00',
				$end_date . ' 23:59:59'
			)
		);

		// Zero results searches
		$zero_results = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $table_name WHERE results_count = 0 AND created_at BETWEEN %s AND %s",
				$start_date . ' 00:00:00',
				$end_date . ' 23:59:59'
			)
		);

		// Click-through rate
		$clicks = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $table_name WHERE clicked_scale_id IS NOT NULL AND created_at BETWEEN %s AND %s",
				$start_date . ' 00:00:00',
				$end_date . ' 23:59:59'
			)
		);

		$ctr = $total_searches > 0 ? round( ( $clicks / $total_searches ) * 100, 2 ) : 0;

		// Top searches
		$top_searches = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT search_query, COUNT(*) as count FROM $table_name 
				WHERE created_at BETWEEN %s AND %s AND search_query IS NOT NULL
				GROUP BY search_query 
				ORDER BY count DESC 
				LIMIT 20",
				$start_date . ' 00:00:00',
				$end_date . ' 23:59:59'
			)
		);

		return new \WP_REST_Response(
			array(
				'total_searches'   => $total_searches,
				'unique_users'     => $unique_users,
				'zero_results'     => $zero_results,
				'click_through_rate' => $ctr,
				'top_searches'     => $top_searches,
				'period'           => array( 'start' => $start_date, 'end' => $end_date ),
			),
			200
		);
	}
}
