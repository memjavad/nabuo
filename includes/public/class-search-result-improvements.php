<?php

namespace ArabPsychology\NabooDatabase\Public;

use WP_REST_Request;
use WP_REST_Response;

/**
 * Search Result Improvements Feature
 *
 * Provides faceted search, filter sidebar, and saved search functionality.
 */
class Search_Result_Improvements {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Create the saved searches table.
	 */
	public function create_table() {
		global $wpdb;
		$table_name      = $wpdb->prefix . 'naboo_saved_searches';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id BIGINT(20) NOT NULL AUTO_INCREMENT,
			user_id BIGINT(20) NOT NULL,
			search_name VARCHAR(255) NOT NULL,
			search_query VARCHAR(500),
			filters LONGTEXT,
			result_count INT(11) DEFAULT 0,
			is_public TINYINT(1) DEFAULT 0,
			view_count INT(11) DEFAULT 0,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY is_public (is_public),
			KEY created_at (created_at),
			UNIQUE KEY user_search (user_id, search_name)
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
			'/search/facets',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_facets' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'query' => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			'apa/v1',
			'/saved-searches',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_saved_searches' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
			)
		);

		register_rest_route(
			'apa/v1',
			'/saved-searches',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'create_saved_search' ),
				'permission_callback' => array( $this, 'check_write_permission' ),
				'args'                => array(
					'search_name' => array(
						'type'     => 'string',
						'required' => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'search_query' => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'filters' => array(
						'type'              => 'object',
						'default'           => array(),
						'sanitize_callback' => array( $this, 'sanitize_filters' ),
					),
					'is_public' => array(
						'type'              => 'boolean',
						'default'           => false,
						'sanitize_callback' => 'rest_sanitize_boolean',
					),
				),
			)
		);

		register_rest_route(
			'apa/v1',
			'/saved-searches/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_saved_search' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
			)
		);

		register_rest_route(
			'apa/v1',
			'/saved-searches/(?P<id>\d+)',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'update_saved_search' ),
				'permission_callback' => array( $this, 'check_write_permission' ),
				'args'                => array(
					'search_name' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'is_public' => array(
						'type'              => 'boolean',
						'sanitize_callback' => 'rest_sanitize_boolean',
					),
				),
			)
		);

		register_rest_route(
			'apa/v1',
			'/saved-searches/(?P<id>\d+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'delete_saved_search' ),
				'permission_callback' => array( $this, 'check_write_permission' ),
			)
		);

		register_rest_route(
			'apa/v1',
			'/saved-searches/public',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_public_saved_searches' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'limit' => array(
						'type'              => 'integer',
						'default'           => 10,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	/**
	 * Get faceted search data for current results.
	 */
	public function get_facets( WP_REST_Request $request ) {
		$query = $request->get_param( 'query' );

		$args = array(
			'post_type'      => 'psych_scale',
			'post_status'    => 'publish',
			's'              => $query,
			'posts_per_page' => -1,
			'fields'         => 'ids',
		);

		$query_obj = new \WP_Query( $args );

		$categories = $this->count_taxonomy_facets( 'scale_category', $query_obj->posts );
		$authors    = $this->count_taxonomy_facets( 'scale_author', $query_obj->posts );
		$years      = $this->count_year_facets( $query_obj->posts );
		$languages  = $this->count_language_facets( $query_obj->posts );

		return new WP_REST_Response(
			array(
				'total_results' => $query_obj->found_posts,
				'categories'    => $categories,
				'authors'       => $authors,
				'years'         => $years,
				'languages'     => $languages,
			)
		);
	}

	/**
	 * Count occurrences of taxonomy terms in post list.
	 */
	private function count_taxonomy_facets( $taxonomy, $post_ids ) {
		if ( empty( $post_ids ) ) {
			return array();
		}

		$terms = wp_get_object_terms( $post_ids, $taxonomy, array( 'fields' => 'all' ) );

		$facets = array();
		foreach ( $terms as $term ) {
			if ( ! isset( $facets[ $term->term_id ] ) ) {
				$facets[ $term->term_id ] = array(
					'id'    => $term->term_id,
					'name'  => $term->name,
					'count' => 0,
				);
			}
			$facets[ $term->term_id ]['count']++;
		}

		usort( $facets, function( $a, $b ) {
			return $b['count'] - $a['count'];
		} );

		return array_slice( $facets, 0, 10 );
	}

	/**
	 * Count year facets.
	 */
	private function count_year_facets( $post_ids ) {
		if ( empty( $post_ids ) ) {
			return array();
		}

		global $wpdb;
		$post_list = implode( ',', array_map( 'intval', $post_ids ) );

		$years = $wpdb->get_results(
			"SELECT CAST(meta_value AS UNSIGNED) as year, COUNT(*) as count
			FROM {$wpdb->postmeta}
			WHERE post_id IN ($post_list)
			AND meta_key = '_naboo_scale_year'
			AND meta_value REGEXP '^[0-9]{4}$'
			GROUP BY year
			ORDER BY year DESC
			LIMIT 10"
		);

		return array_map(
			function( $item ) {
				return array(
					'year'  => (int) $item->year,
					'count' => (int) $item->count,
				);
			},
			$years
		);
	}

	/**
	 * Count language facets.
	 */
	private function count_language_facets( $post_ids ) {
		if ( empty( $post_ids ) ) {
			return array();
		}

		global $wpdb;
		$post_list = implode( ',', array_map( 'intval', $post_ids ) );

		$languages = $wpdb->get_results(
			"SELECT meta_value as language, COUNT(*) as count
			FROM {$wpdb->postmeta}
			WHERE post_id IN ($post_list)
			AND meta_key = '_naboo_scale_language'
			GROUP BY language
			ORDER BY count DESC
			LIMIT 10"
		);

		return array_map(
			function( $item ) {
				return array(
					'language' => sanitize_text_field( $item->language ),
					'count'    => (int) $item->count,
				);
			},
			$languages
		);
	}

	/**
	 * Get saved searches for current user.
	 */
	public function get_saved_searches( WP_REST_Request $request ) {
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return new WP_REST_Response( array( 'error' => 'Unauthorized' ), 401 );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_saved_searches';

		$searches = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM $table_name
			WHERE user_id = %d
			ORDER BY created_at DESC",
			$user_id
		) );

		foreach ( $searches as $search ) {
			$search->filters = json_decode( $search->filters );
		}

		return new WP_REST_Response( array( 'saved_searches' => $searches ) );
	}

	/**
	 * Create a new saved search.
	 */
	public function create_saved_search( WP_REST_Request $request ) {
		$user_id       = get_current_user_id();
		$search_name   = $request->get_param( 'search_name' );
		$search_query  = $request->get_param( 'search_query' );
		$filters       = $request->get_param( 'filters' );
		$is_public     = $request->get_param( 'is_public' ) ? 1 : 0;

		if ( ! $user_id ) {
			return new WP_REST_Response( array( 'error' => 'Unauthorized' ), 401 );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_saved_searches';

		$wpdb->insert(
			$table_name,
			array(
				'user_id'      => $user_id,
				'search_name'  => $search_name,
				'search_query' => $search_query,
				'filters'      => json_encode( $filters ),
				'is_public'    => $is_public,
			),
			array( '%d', '%s', '%s', '%s', '%d' )
		);

		if ( $wpdb->last_error ) {
			return new WP_REST_Response( array( 'error' => 'Database error' ), 500 );
		}

		return new WP_REST_Response( array( 'id' => $wpdb->insert_id, 'success' => true ), 201 );
	}

	/**
	 * Get a single saved search.
	 */
	public function get_saved_search( WP_REST_Request $request ) {
		$id      = $request->get_param( 'id' );
		$user_id = get_current_user_id();

		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_saved_searches';

		$search = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $table_name WHERE id = %d",
			$id
		) );

		if ( ! $search ) {
			return new WP_REST_Response( array( 'error' => 'Not found' ), 404 );
		}

		if ( ! $search->is_public && $search->user_id !== $user_id && ! current_user_can( 'manage_options' ) ) {
			return new WP_REST_Response( array( 'error' => 'Unauthorized' ), 401 );
		}

		$search->filters = json_decode( $search->filters );

		// Increment view count
		$wpdb->query( $wpdb->prepare(
			"UPDATE $table_name SET view_count = view_count + 1 WHERE id = %d",
			$id
		) );

		return new WP_REST_Response( $search );
	}

	/**
	 * Update a saved search.
	 */
	public function update_saved_search( WP_REST_Request $request ) {
		$id       = $request->get_param( 'id' );
		$user_id  = get_current_user_id();
		$name     = $request->get_param( 'search_name' );
		$is_public = $request->get_param( 'is_public' );

		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_saved_searches';

		$search = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $table_name WHERE id = %d",
			$id
		) );

		if ( ! $search || $search->user_id !== $user_id ) {
			return new WP_REST_Response( array( 'error' => 'Unauthorized' ), 401 );
		}

		$update = array();
		if ( $name ) {
			$update['search_name'] = $name;
		}
		if ( $is_public !== null ) {
			$update['is_public'] = $is_public ? 1 : 0;
		}

		if ( ! empty( $update ) ) {
			$wpdb->update( $table_name, $update, array( 'id' => $id ) );
		}

		return new WP_REST_Response( array( 'success' => true ) );
	}

	/**
	 * Delete a saved search.
	 */
	public function delete_saved_search( WP_REST_Request $request ) {
		$id       = $request->get_param( 'id' );
		$user_id  = get_current_user_id();

		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_saved_searches';

		$search = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $table_name WHERE id = %d",
			$id
		) );

		if ( ! $search || $search->user_id !== $user_id ) {
			return new WP_REST_Response( array( 'error' => 'Unauthorized' ), 401 );
		}

		$wpdb->delete( $table_name, array( 'id' => $id ) );

		return new WP_REST_Response( array( 'success' => true ) );
	}

	/**
	 * Get public saved searches.
	 */
	public function get_public_saved_searches( WP_REST_Request $request ) {
		$limit = $request->get_param( 'limit' );

		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_saved_searches';

		$searches = $wpdb->get_results( $wpdb->prepare(
			"SELECT id, user_id, search_name, search_query, result_count, view_count, created_at
			FROM $table_name
			WHERE is_public = 1
			ORDER BY view_count DESC, created_at DESC
			LIMIT %d",
			$limit
		) );

		return new WP_REST_Response( array( 'public_searches' => $searches ) );
	}

	/**
	 * Permission check for read operations.
	 */
	public function check_read_permission() {
		return is_user_logged_in();
	}

	/**
	 * Permission check for write operations.
	 */
	public function check_write_permission() {
		return is_user_logged_in();
	}

	/**
	 * Sanitize filters object.
	 */
	public function sanitize_filters( $filters ) {
		if ( ! is_array( $filters ) ) {
			return array();
		}

		return array_map( 'sanitize_text_field', $filters );
	}

	/**
	 * Enqueue scripts and styles.
	 */
	public function enqueue_scripts() {
		if ( ! is_singular( 'psych_scale' ) && ! is_page() ) {
			return;
		}

		wp_enqueue_script(
			$this->plugin_name . '-search-improvements',
			plugins_url( 'js/search-result-improvements.js', __FILE__ ),
			array( 'jquery' ),
			$this->version,
			true
		);

		wp_enqueue_style(
			$this->plugin_name . '-search-improvements',
			plugins_url( 'css/search-result-improvements.css', __FILE__ ),
			array(),
			$this->version
		);

		wp_localize_script(
			$this->plugin_name . '-search-improvements',
			'apaSearchImprovements',
			array(
				'api_url'   => rest_url( 'apa/v1' ),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
				'is_logged_in' => is_user_logged_in(),
			)
		);
	}
}
