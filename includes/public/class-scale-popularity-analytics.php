<?php
/**
 * Scale Popularity Analytics
 *
 * @package ArabPsychology\NabooDatabase\Public
 */

namespace ArabPsychology\NabooDatabase\Public;

/**
 * Scale_Popularity_Analytics class - Track and display scale popularity metrics.
 */
class Scale_Popularity_Analytics {

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
	 * Create popularity analytics table
	 */
	private function create_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_popularity_analytics';

		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === null ) {
			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE $table_name (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				scale_id bigint(20) NOT NULL,
				views bigint(20) DEFAULT 0,
				downloads bigint(20) DEFAULT 0,
				shares bigint(20) DEFAULT 0,
				favorites bigint(20) DEFAULT 0,
				comments bigint(20) DEFAULT 0,
				ratings bigint(20) DEFAULT 0,
				avg_rating float DEFAULT 0,
				collection_additions bigint(20) DEFAULT 0,
				last_viewed datetime,
				updated_at datetime DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY scale_id (scale_id),
				KEY views (views),
				KEY downloads (downloads)
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
			'/analytics/popularity',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_popularity' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'apa/v1',
			'/analytics/top-scales',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_top_scales' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'apa/v1',
			'/analytics/scale-stats',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_scale_stats' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'apa/v1',
			'/analytics/trending-scales',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_trending_scales' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'apa/v1',
			'/analytics/popularity-by-category',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_popularity_by_category' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Enqueue scripts and styles
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'naboo-popularity-analytics', plugin_dir_url( __FILE__ ) . 'js/scale-popularity-analytics.js', array( 'jquery' ), $this->version, true );
		wp_enqueue_style( 'naboo-popularity-analytics', plugin_dir_url( __FILE__ ) . 'css/scale-popularity-analytics.css', array(), $this->version );
	}

	/**
	 * Get popularity metrics for a scale
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_popularity( $request ) {
		$scale_id = $request->get_param( 'scale_id' );

		if ( ! $scale_id ) {
			return new \WP_REST_Response(
				array( 'error' => 'Scale ID is required' ),
				400
			);
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_popularity_analytics';

		$stats = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM $table_name WHERE scale_id = %d", $scale_id )
		);

		if ( ! $stats ) {
			$stats = $this->calculate_popularity( $scale_id );
		}

		return new \WP_REST_Response( $stats, 200 );
	}

	/**
	 * Get top scales by popularity
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_top_scales( $request ) {
		$limit = (int) $request->get_param( 'limit' ) ?? 10;
		$metric = $request->get_param( 'metric' ) ?? 'views';

		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_popularity_analytics';

		$top_scales = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT scale_id, views, downloads, shares, favorites, ratings, avg_rating FROM $table_name ORDER BY $metric DESC LIMIT %d",
				$limit
			)
		);

		// Enrich with scale info
		foreach ( $top_scales as &$row ) {
			$scale = get_post( $row->scale_id );
			$row->title = $scale ? $scale->post_title : 'Unknown';
		}

		return new \WP_REST_Response( array( 'scales' => $top_scales ), 200 );
	}

	/**
	 * Get scale statistics
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_scale_stats( $request ) {
		$scale_id = $request->get_param( 'scale_id' );

		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_popularity_analytics';

		$stats = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM $table_name WHERE scale_id = %d", $scale_id )
		);

		if ( ! $stats ) {
			$stats = $this->calculate_popularity( $scale_id );
			$this->save_popularity( $stats );
		}

		// Calculate engagement rate
		$total_interactions = (int) $stats->views + (int) $stats->downloads + (int) $stats->shares + (int) $stats->comments;
		$engagement_rate = $stats->views > 0 ? round( ( $total_interactions / $stats->views ) * 100, 2 ) : 0;

		$stats->engagement_rate = $engagement_rate;
		$stats->total_interactions = $total_interactions;

		return new \WP_REST_Response( $stats, 200 );
	}

	/**
	 * Get trending scales (based on recent activity)
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_trending_scales( $request ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_popularity_analytics';

		// Get scales viewed in last 7 days
		$trending_scales = $wpdb->get_results(
			"SELECT scale_id, views, downloads, shares, last_viewed FROM $table_name 
			WHERE last_viewed >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
			ORDER BY views DESC LIMIT 10"
		);

		// Enrich with scale info
		foreach ( $trending_scales as &$row ) {
			$scale = get_post( $row->scale_id );
			$row->title = $scale ? $scale->post_title : 'Unknown';
		}

		return new \WP_REST_Response( array( 'trending' => $trending_scales ), 200 );
	}

	/**
	 * Get popularity by category
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_popularity_by_category( $request ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_popularity_analytics';

		// ⚡ Bolt Performance: Replaced N+1 query pattern (looping through categories to run get_posts and db queries)
		// with a single unified SQL query using joins and aggregations.
		$query = "
			SELECT
				t.name AS category,
				COUNT(DISTINCT p.ID) AS scale_count,
				SUM(COALESCE(pa.views, 0)) AS total_views,
				SUM(COALESCE(pa.downloads, 0)) AS total_downloads
			FROM {$wpdb->terms} t
			INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id AND tt.taxonomy = 'scale_category'
			INNER JOIN {$wpdb->term_relationships} tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
			INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID AND p.post_type = 'psych_scale' AND p.post_status = 'publish'
			LEFT JOIN {$table_name} pa ON p.ID = pa.scale_id
			GROUP BY t.term_id, t.name
		";

		$results = $wpdb->get_results( $query, ARRAY_A );
		if ( ! is_array( $results ) ) {
			$results = array();
		}

		// Format back to associative array with correct types
		$category_stats = array_map( function( $row ) {
			return array(
				'category'        => $row['category'],
				'scale_count'     => (int) $row['scale_count'],
				'total_views'     => (int) $row['total_views'],
				'total_downloads' => (int) $row['total_downloads'],
			);
		}, $results );

		return new \WP_REST_Response( array( 'categories' => $category_stats ), 200 );
	}

	/**
	 * Calculate popularity metrics
	 *
	 * @param int $scale_id The scale ID.
	 * @return object
	 */
	private function calculate_popularity( $scale_id ) {
		$views = (int) get_post_meta( $scale_id, '_naboo_view_count', true ) ?? 0;

		global $wpdb;

		$downloads = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}naboo_file_downloads WHERE scale_id = %d AND status = 'completed'",
				$scale_id
			)
		);

		$shares = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}naboo_comparison_shares WHERE scale_id = %d",
				$scale_id
			)
		);

		$favorites = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}naboo_favorites WHERE scale_id = %d",
				$scale_id
			)
		);

		$comments = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}naboo_comments WHERE scale_id = %d AND status = 'approved'",
				$scale_id
			)
		);

		$ratings = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}naboo_ratings WHERE scale_id = %d AND status = 'approved'",
				$scale_id
			)
		);

		$avg_rating = (float) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT AVG(rating) FROM {$wpdb->prefix}naboo_ratings WHERE scale_id = %d AND status = 'approved'",
				$scale_id
			)
		);

		$collection_additions = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}naboo_collection_items WHERE scale_id = %d",
				$scale_id
			)
		);

		return (object) array(
			'scale_id'               => $scale_id,
			'views'                  => $views,
			'downloads'              => $downloads,
			'shares'                 => $shares,
			'favorites'              => $favorites,
			'comments'               => $comments,
			'ratings'                => $ratings,
			'avg_rating'             => round( $avg_rating ?? 0, 2 ),
			'collection_additions'   => $collection_additions,
			'last_viewed'            => current_time( 'mysql' ),
		);
	}

	/**
	 * Save popularity metrics
	 *
	 * @param object $stats The stats object.
	 */
	private function save_popularity( $stats ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_popularity_analytics';

		$existing = $wpdb->get_var(
			$wpdb->prepare( "SELECT id FROM $table_name WHERE scale_id = %d", $stats->scale_id )
		);

		if ( $existing ) {
			$wpdb->update(
				$table_name,
				array(
					'views'                => $stats->views,
					'downloads'            => $stats->downloads,
					'shares'               => $stats->shares,
					'favorites'            => $stats->favorites,
					'comments'             => $stats->comments,
					'ratings'              => $stats->ratings,
					'avg_rating'           => $stats->avg_rating,
					'collection_additions' => $stats->collection_additions,
					'last_viewed'          => $stats->last_viewed,
				),
				array( 'scale_id' => $stats->scale_id ),
				array( '%d', '%d', '%d', '%d', '%d', '%d', '%f', '%d', '%s' ),
				array( '%d' )
			);
		} else {
			$wpdb->insert(
				$table_name,
				(array) $stats,
				array( '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s' )
			);
		}
	}

	/**
	 * Track view event
	 *
	 * @param int $scale_id The scale ID.
	 */
	public function track_view( $scale_id ) {
		$current_count = (int) get_post_meta( $scale_id, '_naboo_view_count', true );
		update_post_meta( $scale_id, '_naboo_view_count', $current_count + 1 );
		$this->update_analytics( $scale_id );
	}

	/**
	 * Update analytics record
	 *
	 * @param int $scale_id The scale ID.
	 */
	private function update_analytics( $scale_id ) {
		$stats = $this->calculate_popularity( $scale_id );
		$this->save_popularity( $stats );
	}
}
