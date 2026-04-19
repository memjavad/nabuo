<?php
/**
 * Admin Reports Generator
 *
 * @package ArabPsychology\NabooDatabase\Admin
 */

namespace ArabPsychology\NabooDatabase\Admin;

/**
 * Admin_Reports_Generator class - Generate comprehensive admin reports.
 */
class Admin_Reports_Generator {

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
	 * Create reports table
	 */
	private function create_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_reports';

		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === null ) {
			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE $table_name (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				report_name varchar(255),
				report_type varchar(100),
				start_date date,
				end_date date,
				data longtext,
				generated_by bigint(20),
				generated_at datetime DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY report_type (report_type),
				KEY generated_at (generated_at)
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
			'/reports/overview',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_overview_report' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/reports/content',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_content_report' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/reports/engagement',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_engagement_report' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/reports/user-activity',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_user_activity_report' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/reports/saved-reports',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_saved_reports' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/reports/save-report',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'save_report' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}

	/**
	 * Get overview report
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_overview_report( $request ) {
		global $wpdb;

		$total_scales = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'psych_scale'"
		);

		$published_scales = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'psych_scale' AND post_status = 'publish'"
		);

		$pending_scales = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'psych_scale' AND post_status = 'pending'"
		);

		$total_users = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->users}"
		);

		$active_users = (int) $wpdb->get_var(
			"SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}naboo_user_analytics WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
		);

		$total_views = (int) $wpdb->get_var(
			"SELECT SUM(views) FROM {$wpdb->prefix}naboo_popularity_analytics"
		);

		$total_downloads = (int) $wpdb->get_var(
			"SELECT SUM(downloads) FROM {$wpdb->prefix}naboo_popularity_analytics"
		);

		return new \WP_REST_Response(
			array(
				'total_scales'      => $total_scales,
				'published_scales'  => $published_scales,
				'pending_scales'    => $pending_scales,
				'total_users'       => $total_users,
				'active_users'      => $active_users,
				'total_views'       => $total_views ?? 0,
				'total_downloads'   => $total_downloads ?? 0,
				'generated_at'      => current_time( 'mysql' ),
			),
			200
		);
	}

	/**
	 * Get content report
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_content_report( $request ) {
		$start_date = $request->get_param( 'start_date' ) ?? date( 'Y-m-d', strtotime( '-30 days' ) );
		$end_date = $request->get_param( 'end_date' ) ?? date( 'Y-m-d' );

		global $wpdb;

		// Scales added in period
		$scales_added = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'psych_scale' AND post_date BETWEEN %s AND %s",
				$start_date . ' 00:00:00',
				$end_date . ' 23:59:59'
			)
		);

		// Scales approved in period
		$scales_approved = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'psych_scale' AND post_status = 'publish' AND post_modified BETWEEN %s AND %s",
				$start_date . ' 00:00:00',
				$end_date . ' 23:59:59'
			)
		);

		// Top categories
		$top_categories = $wpdb->get_results(
			"SELECT t.name, COUNT(tr.object_id) as scale_count 
			FROM {$wpdb->terms} t
			JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
			JOIN {$wpdb->term_relationships} tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
			WHERE tt.taxonomy = 'scale_category'
			GROUP BY t.term_id
			ORDER BY scale_count DESC
			LIMIT 10"
		);

		// Top authors
		$top_authors = $wpdb->get_results(
			"SELECT p.post_author, u.display_name, COUNT(*) as scale_count 
			FROM {$wpdb->posts} p
			JOIN {$wpdb->users} u ON p.post_author = u.ID
			WHERE p.post_type = 'psych_scale' AND p.post_status = 'publish'
			GROUP BY p.post_author
			ORDER BY scale_count DESC
			LIMIT 10"
		);

		return new \WP_REST_Response(
			array(
				'scales_added'     => $scales_added,
				'scales_approved'  => $scales_approved,
				'top_categories'   => $top_categories,
				'top_authors'      => $top_authors,
				'period'           => array( 'start' => $start_date, 'end' => $end_date ),
			),
			200
		);
	}

	/**
	 * Get engagement report
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_engagement_report( $request ) {
		$start_date = $request->get_param( 'start_date' ) ?? date( 'Y-m-d', strtotime( '-30 days' ) );
		$end_date = $request->get_param( 'end_date' ) ?? date( 'Y-m-d' );

		global $wpdb;

		// Total ratings
		$total_ratings = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}naboo_ratings WHERE created_at BETWEEN %s AND %s",
				$start_date . ' 00:00:00',
				$end_date . ' 23:59:59'
			)
		);

		// Total comments
		$total_comments = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}naboo_comments WHERE created_at BETWEEN %s AND %s",
				$start_date . ' 00:00:00',
				$end_date . ' 23:59:59'
			)
		);

		// Total favorites
		$total_favorites = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}naboo_favorites WHERE created_at BETWEEN %s AND %s",
				$start_date . ' 00:00:00',
				$end_date . ' 23:59:59'
			)
		);

		// Average rating
		$avg_rating = (float) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT AVG(rating) FROM {$wpdb->prefix}naboo_ratings WHERE created_at BETWEEN %s AND %s",
				$start_date . ' 00:00:00',
				$end_date . ' 23:59:59'
			)
		);

		return new \WP_REST_Response(
			array(
				'total_ratings'    => $total_ratings,
				'total_comments'   => $total_comments,
				'total_favorites'  => $total_favorites,
				'avg_rating'       => round( $avg_rating ?? 0, 2 ),
				'period'           => array( 'start' => $start_date, 'end' => $end_date ),
			),
			200
		);
	}

	/**
	 * Get user activity report
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_user_activity_report( $request ) {
		$start_date = $request->get_param( 'start_date' ) ?? date( 'Y-m-d', strtotime( '-30 days' ) );
		$end_date = $request->get_param( 'end_date' ) ?? date( 'Y-m-d' );

		global $wpdb;

		// New users
		$new_users = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->users} WHERE user_registered BETWEEN %s AND %s",
				$start_date . ' 00:00:00',
				$end_date . ' 23:59:59'
			)
		);

		// Active users
		$active_users = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}naboo_search_analytics WHERE created_at BETWEEN %s AND %s",
				$start_date . ' 00:00:00',
				$end_date . ' 23:59:59'
			)
		);

		// Most active users
		$most_active = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT user_id, u.display_name, COUNT(*) as activity_count 
				FROM {$wpdb->prefix}naboo_search_analytics sa
				JOIN {$wpdb->users} u ON sa.user_id = u.ID
				WHERE sa.created_at BETWEEN %s AND %s
				GROUP BY user_id
				ORDER BY activity_count DESC
				LIMIT 10",
				$start_date . ' 00:00:00',
				$end_date . ' 23:59:59'
			)
		);

		return new \WP_REST_Response(
			array(
				'new_users'     => $new_users,
				'active_users'  => $active_users,
				'most_active'   => $most_active,
				'period'        => array( 'start' => $start_date, 'end' => $end_date ),
			),
			200
		);
	}

	/**
	 * Get saved reports
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_saved_reports( $request ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_reports';

		$reports = $wpdb->get_results(
			"SELECT id, report_name, report_type, start_date, end_date, generated_at FROM $table_name ORDER BY generated_at DESC LIMIT 50"
		);

		return new \WP_REST_Response( array( 'reports' => $reports ), 200 );
	}

	/**
	 * Save report
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function save_report( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error( 'unauthorized', 'Not authorized', array( 'status' => 403 ) );
		}

		$report_name = sanitize_text_field( $request->get_param( 'report_name' ) );
		$report_type = sanitize_text_field( $request->get_param( 'report_type' ) );
		$start_date = sanitize_text_field( $request->get_param( 'start_date' ) );
		$end_date = sanitize_text_field( $request->get_param( 'end_date' ) );
		$data = $request->get_param( 'data' );

		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_reports';

		$result = $wpdb->insert(
			$table_name,
			array(
				'report_name'  => $report_name,
				'report_type'  => $report_type,
				'start_date'   => $start_date,
				'end_date'     => $end_date,
				'data'         => wp_json_encode( $data ),
				'generated_by' => get_current_user_id(),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%d' )
		);

		if ( $result ) {
			return new \WP_REST_Response( array( 'message' => 'Report saved', 'id' => $wpdb->insert_id ), 200 );
		}

		return new \WP_REST_Response( array( 'error' => 'Failed to save report' ), 500 );
	}
}
