<?php

namespace ArabPsychology\NabooDatabase\Public;

use WP_REST_Request;
use WP_REST_Response;

/**
 * Smart Search Suggestions Feature
 *
 * Provides autocomplete suggestions, trending searches, and search analytics.
 */
class Smart_Search_Suggestions {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Create the search suggestions analytics table.
	 */
	public function create_table() {
		global $wpdb;
		$table_name      = $wpdb->prefix . 'naboo_search_suggestions';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id BIGINT(20) NOT NULL AUTO_INCREMENT,
			search_term VARCHAR(255) NOT NULL,
			search_count INT(11) DEFAULT 1,
			last_searched DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY unique_term (search_term),
			KEY search_count (search_count),
			KEY last_searched (last_searched)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Register REST API endpoints.
	 */
	public function register_endpoints() {
		register_rest_route(
			'apa/v1',
			'/search/suggestions',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_suggestions' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'query' => array(
						'type'     => 'string',
						'required' => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'limit' => array(
						'type'              => 'integer',
						'default'           => 8,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			'apa/v1',
			'/search/trending',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_trending_searches' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'limit' => array(
						'type'              => 'integer',
						'default'           => 10,
						'sanitize_callback' => 'absint',
					),
					'period' => array(
						'type'              => 'string',
						'default'           => 'week',
						'enum'              => array( 'day', 'week', 'month', 'all' ),
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			'apa/v1',
			'/search/record',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'record_search' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'query' => array(
						'type'     => 'string',
						'required' => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			'apa/v1',
			'/search/scales',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'search_scales' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'query' => array(
						'type'     => 'string',
						'required' => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'limit' => array(
						'type'              => 'integer',
						'default'           => 5,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	/**
	 * Get search suggestions based on query.
	 */
	public function get_suggestions( WP_REST_Request $request ) {
		$query = $request->get_param( 'query' );
		$limit = $request->get_param( 'limit' );

		if ( strlen( $query ) < 2 ) {
			return new WP_REST_Response( array( 'suggestions' => array() ) );
		}

		global $wpdb;
		$table_name  = $wpdb->prefix . 'naboo_search_suggestions';
		$like_query  = '%' . $wpdb->esc_like( $query ) . '%';

		$results = $wpdb->get_col( $wpdb->prepare(
			"SELECT search_term FROM $table_name
			WHERE search_term LIKE %s
			ORDER BY search_count DESC, last_searched DESC
			LIMIT %d",
			$like_query,
			$limit
		) );

		$suggestions = array_map( 'strtolower', array_unique( $results ) );

		return new WP_REST_Response( array( 'suggestions' => $suggestions ) );
	}

	/**
	 * Get trending searches within a time period.
	 */
	public function get_trending_searches( WP_REST_Request $request ) {
		$limit  = $request->get_param( 'limit' );
		$period = $request->get_param( 'period' );

		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_search_suggestions';

		$where = '';
		switch ( $period ) {
			case 'day':
				$where = "WHERE last_searched >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
				break;
			case 'week':
				$where = "WHERE last_searched >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
				break;
			case 'month':
				$where = "WHERE last_searched >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
				break;
		}

		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT search_term, search_count, last_searched FROM $table_name
			$where
			ORDER BY search_count DESC, last_searched DESC
			LIMIT %d",
			$limit
		) );

		return new WP_REST_Response( array( 'trending' => $results ) );
	}

	/**
	 * Record a search query for analytics.
	 */
	public function record_search( WP_REST_Request $request ) {
		$query = $request->get_param( 'query' );

		if ( strlen( $query ) < 2 ) {
			return new WP_REST_Response( array( 'success' => false, 'message' => 'Query too short' ), 400 );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_search_suggestions';

		$wpdb->query( $wpdb->prepare(
			"INSERT INTO $table_name (search_term, search_count, last_searched)
			VALUES (%s, 1, NOW())
			ON DUPLICATE KEY UPDATE
			search_count = search_count + 1,
			last_searched = NOW()",
			strtolower( $query )
		) );

		return new WP_REST_Response( array( 'success' => true ) );
	}

	/**
	 * Search for scales matching the query.
	 */
	public function search_scales( WP_REST_Request $request ) {
		$query = $request->get_param( 'query' );
		$limit = $request->get_param( 'limit' );

		$args = array(
			'post_type'      => 'psych_scale',
			'post_status'    => 'publish',
			's'              => $query,
			'posts_per_page' => $limit,
			'fields'         => 'ids',
		);

		$query_obj = new \WP_Query( $args );
		$results   = array();

		foreach ( $query_obj->posts as $post_id ) {
			$post   = get_post( $post_id );
			$thumb  = get_the_post_thumbnail_url( $post_id, 'thumbnail' );
			$rating = $this->get_average_rating( $post_id );

			$results[] = array(
				'id'         => $post_id,
				'title'      => $post->post_title,
				'excerpt'    => wp_trim_words( $post->post_excerpt ?: $post->post_content, 15 ),
				'thumbnail'  => $thumb ?: '',
				'rating'     => $rating,
				'url'        => get_permalink( $post_id ),
			);
		}

		return new WP_REST_Response( array( 'scales' => $results ) );
	}

	/**
	 * Get average rating for a scale.
	 */
	private function get_average_rating( $scale_id ) {
		global $wpdb;
		$ratings_table = $wpdb->prefix . 'naboo_ratings';

		$avg = $wpdb->get_var( $wpdb->prepare(
			"SELECT AVG(rating) FROM $ratings_table
			WHERE scale_id = %d AND status = 'approved'",
			$scale_id
		) );

		return $avg ? round( $avg, 1 ) : 0;
	}

	/**
	 * Enqueue scripts and styles.
	 */
	public function enqueue_scripts() {
		if ( ! is_singular( 'psych_scale' ) && ! is_page() ) {
			return;
		}

		wp_enqueue_script(
			$this->plugin_name . '-smart-suggestions',
			plugins_url( 'js/smart-search-suggestions.js', __FILE__ ),
			array( 'jquery' ),
			$this->version,
			true
		);

		wp_enqueue_style(
			$this->plugin_name . '-smart-suggestions',
			plugins_url( 'css/smart-search-suggestions.css', __FILE__ ),
			array(),
			$this->version
		);

		wp_localize_script(
			$this->plugin_name . '-smart-suggestions',
			'apaSmartSearch',
			array(
				'api_url'   => rest_url( 'apa/v1' ),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
				'min_chars' => 2,
			)
		);
	}
}
