<?php
/**
 * Contributor Management
 *
 * @package ArabPsychology\NabooDatabase\Admin
 */

namespace ArabPsychology\NabooDatabase\Admin;

/**
 * Contributor_Management class - User contribution tracking and management.
 */
class Contributor_Management {

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
	 * @param string $version	 The plugin version.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version	 = $version;
		$this->create_table();
	}

	/**
	 * Create contributor tracking table
	 */
	private function create_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_contributor_stats';

		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === null ) {
			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE $table_name (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				user_id bigint(20) NOT NULL,
				total_scales bigint(20) DEFAULT 0,
				approved_scales bigint(20) DEFAULT 0,
				rejected_scales bigint(20) DEFAULT 0,
				total_comments bigint(20) DEFAULT 0,
				total_ratings bigint(20) DEFAULT 0,
				avg_rating float DEFAULT 0,
				first_contribution datetime,
				last_contribution datetime,
				PRIMARY KEY (id),
				UNIQUE KEY user_id (user_id),
				KEY total_scales (total_scales)
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
			'/contributors/leaderboard',
			array(
				'methods'			 => 'GET',
				'callback'			=> array( $this, 'get_leaderboard' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'apa/v1',
			'/contributors/(?P<user_id>\d+)/stats',
			array(
				'methods'			 => 'GET',
				'callback'			=> array( $this, 'get_contributor_stats' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'apa/v1',
			'/contributors/(?P<user_id>\d+)/scales',
			array(
				'methods'			 => 'GET',
				'callback'			=> array( $this, 'get_contributor_scales' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'apa/v1',
			'/contributors/update-stats',
			array(
				'methods'			 => 'POST',
				'callback'			=> array( $this, 'update_all_contributor_stats' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}

	/**
	 * Get leaderboard
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_leaderboard( $request ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_contributor_stats';

		$leaderboard = $wpdb->get_results(
			"SELECT * FROM $table_name ORDER BY total_scales DESC, approved_scales DESC LIMIT 50"
		);

		// Enrich with user info
		foreach ( $leaderboard as &$row ) {
			$user = get_user_by( 'id', $row->user_id );
			$row->display_name = $user ? $user->display_name : 'Unknown';
			$row->user_url	 = $user ? get_author_posts_url( $user->ID ) : '';
		}

		return new \WP_REST_Response( array( 'leaderboard' => $leaderboard ), 200 );
	}

	/**
	 * Get contributor statistics
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_contributor_stats( $request ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_contributor_stats';
		$user_id	= $request->get_param( 'user_id' );

		$stats = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM $table_name WHERE user_id = %d", $user_id )
		);

		if ( ! $stats ) {
			$user = get_user_by( 'id', $user_id );
			if ( ! $user ) {
				return new \WP_REST_Response(
					array( 'error' => 'User not found' ),
					404
				);
			}

			$stats = $this->calculate_contributor_stats( $user_id );
		}

		$user = get_user_by( 'id', $user_id );
		$stats->display_name = $user->display_name;
		$stats->user_email = $user->user_email;
		$stats->user_url = get_author_posts_url( $user->ID );

		return new \WP_REST_Response( $stats, 200 );
	}

	/**
	 * Get contributor's scales
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_contributor_scales( $request ) {
		$user_id = $request->get_param( 'user_id' );
		$status  = $request->get_param( 'status' ) ?? 'publish';

		$args = array(
			'post_type'	  => 'psych_scale',
			'post_status'	=> $status,
			'author'		 => $user_id,
			'posts_per_page' => 20,
		);

		$query = new \WP_Query( $args );

		$post_ids = wp_list_pluck( $query->posts, 'ID' );

		if ( ! empty( $post_ids ) ) {
			update_meta_cache( 'post', $post_ids );
		}

		$scales = array();
		foreach ( $query->posts as $scale ) {
			$views = get_post_meta( $scale->ID, '_naboo_view_count', true );
			$scales[] = array(
				'id'	   => $scale->ID,
				'title'	=> $scale->post_title,
				'date'	 => $scale->post_date,
				'status'   => $scale->post_status,
				'views'	=> $views ? (int) $views : 0,
			);
		}

		wp_reset_postdata();

		return new \WP_REST_Response(
			array(
				'scales'	  => $scales,
				'total'	   => $query->found_posts,
				'total_pages' => $query->max_num_pages,
			),
			200
		);
	}

	/**
	 * Calculate contributor statistics
	 *
	 * @param int $user_id The user ID.
	 * @return object
	 */
	private function calculate_contributor_stats( $user_id ) {
		global $wpdb;

		$total_scales = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'psych_scale' AND post_author = %d",
				$user_id
			)
		);

		$approved_scales = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'psych_scale' AND post_status = 'publish' AND post_author = %d",
				$user_id
			)
		);

		$rejected_scales = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'psych_scale' AND post_status = 'trash' AND post_author = %d",
				$user_id
			)
		);

		$total_comments = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}naboo_comments WHERE user_id = %d",
				$user_id
			)
		);

		$total_ratings = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}naboo_ratings WHERE user_id = %d",
				$user_id
			)
		);

		$avg_rating = (float) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT AVG(rating) FROM {$wpdb->prefix}naboo_ratings WHERE user_id = %d",
				$user_id
			)
		);

		$first_scale = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_date FROM {$wpdb->posts} WHERE post_type = 'psych_scale' AND post_author = %d ORDER BY post_date ASC LIMIT 1",
				$user_id
			)
		);

		$last_scale = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_date FROM {$wpdb->posts} WHERE post_type = 'psych_scale' AND post_author = %d ORDER BY post_date DESC LIMIT 1",
				$user_id
			)
		);

		return (object) array(
			'user_id'			  => $user_id,
			'total_scales'		 => $total_scales,
			'approved_scales'	  => $approved_scales,
			'rejected_scales'	  => $rejected_scales,
			'total_comments'	   => $total_comments,
			'total_ratings'		=> $total_ratings,
			'avg_rating'		   => round( $avg_rating, 2 ),
			'first_contribution'   => $first_scale,
			'last_contribution'	=> $last_scale,
		);
	}

	/**
	 * Update all contributor statistics
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function update_all_contributor_stats( $request ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_contributor_stats';

		$users = get_users(
			array(
				'has_published_posts' => array( 'psych_scale' ),
			)
		);

		$updated = 0;
		$values  = array();

		$values	   = array();
		$placeholders = array();

		foreach ( $users as $user ) {
			$stats = $this->calculate_contributor_stats( $user->ID );

			$values[] = $wpdb->prepare(
				"(%d, %d, %d, %d, %d, %d, %f, %s, %s)",
				$user->ID,
				$stats->total_scales,
				$stats->approved_scales,
				$stats->rejected_scales,
				$stats->total_comments,
				$stats->total_ratings,
				$stats->avg_rating,
				$stats->first_contribution,
				$stats->last_contribution
			);

			$updated++;
		}

		if ( ! empty( $values ) ) {
			$query = "INSERT INTO $table_name
				(user_id, total_scales, approved_scales, rejected_scales, total_comments, total_ratings, avg_rating, first_contribution, last_contribution)
				VALUES " . implode( ', ', $values ) . "
				ON DUPLICATE KEY UPDATE
				total_scales = VALUES(total_scales),
				approved_scales = VALUES(approved_scales),
				rejected_scales = VALUES(rejected_scales),
				total_comments = VALUES(total_comments),
				total_ratings = VALUES(total_ratings),
				avg_rating = VALUES(avg_rating),
				last_contribution = VALUES(last_contribution)";

			$wpdb->query( $query );
		}

		return new \WP_REST_Response(
			array( 'message' => sprintf( '%d contributor stats updated', $updated ) ),
			200
		);
	}
}
