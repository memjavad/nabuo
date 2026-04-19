<?php

namespace ArabPsychology\NabooDatabase\Public;

class Ratings {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	public function create_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_ratings';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) NOT NULL,
			scale_id bigint(20) NOT NULL,
			rating int(1) NOT NULL,
			review longtext,
			status varchar(20) DEFAULT 'pending',
			helpful_count int(11) DEFAULT 0,
			unhelpful_count int(11) DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY user_scale (user_id, scale_id),
			KEY scale_id (scale_id),
			KEY status (status),
			KEY rating (rating)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	public function register_endpoints() {
		register_rest_route( 'apa/v1', '/ratings', array(
			'methods' => 'GET',
			'callback' => array( $this, 'get_ratings' ),
			'permission_callback' => '__return_true',
		) );

		register_rest_route( 'apa/v1', '/ratings/(?P<scale_id>\d+)', array(
			'methods' => 'GET',
			'callback' => array( $this, 'get_scale_ratings' ),
			'permission_callback' => '__return_true',
		) );

		register_rest_route( 'apa/v1', '/ratings', array(
			'methods' => 'POST',
			'callback' => array( $this, 'add_rating' ),
			'permission_callback' => array( $this, 'check_user_logged_in' ),
		) );

		register_rest_route( 'apa/v1', '/ratings/(?P<id>\d+)', array(
			'methods' => 'PUT',
			'callback' => array( $this, 'update_rating' ),
			'permission_callback' => array( $this, 'check_user_logged_in' ),
		) );

		register_rest_route( 'apa/v1', '/ratings/(?P<id>\d+)', array(
			'methods' => 'DELETE',
			'callback' => array( $this, 'delete_rating' ),
			'permission_callback' => array( $this, 'check_user_logged_in' ),
		) );

		register_rest_route( 'apa/v1', '/ratings/(?P<id>\d+)/helpful', array(
			'methods' => 'POST',
			'callback' => array( $this, 'mark_helpful' ),
			'permission_callback' => array( $this, 'check_user_logged_in' ),
		) );

		register_rest_route( 'apa/v1', '/ratings/stats/(?P<scale_id>\d+)', array(
			'methods' => 'GET',
			'callback' => array( $this, 'get_rating_stats' ),
			'permission_callback' => '__return_true',
		) );
	}

	public function get_ratings( $request ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_ratings';
		$scale_id = $request->get_param( 'scale_id' );
		$user_id = get_current_user_id();

		$query = $wpdb->prepare(
			"SELECT * FROM $table_name WHERE status = 'approved' ORDER BY created_at DESC LIMIT 10"
		);

		if ( $scale_id ) {
			$query = $wpdb->prepare(
				"SELECT * FROM $table_name WHERE scale_id = %d AND status = 'approved' ORDER BY created_at DESC LIMIT 10",
				intval( $scale_id )
			);
		}

		$results = $wpdb->get_results( $query );

		return rest_ensure_response( array(
			'success' => true,
			'data' => $results,
			'count' => count( $results ),
		) );
	}

	public function get_scale_ratings( $request ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_ratings';
		$scale_id = intval( $request->get_param( 'scale_id' ) );

		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM $table_name WHERE scale_id = %d AND status = 'approved' ORDER BY helpful_count DESC, created_at DESC LIMIT 20",
			$scale_id
		) );

		return rest_ensure_response( array(
			'success' => true,
			'data' => $results,
			'count' => count( $results ),
		) );
	}

	public function add_rating( $request ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_ratings';
		$user_id = get_current_user_id();

		$params = $request->get_json_params();
		$scale_id = intval( $params['scale_id'] ?? 0 );
		$rating = intval( $params['rating'] ?? 0 );
		$review = sanitize_textarea_field( $params['review'] ?? '' );

		if ( ! $scale_id || $rating < 1 || $rating > 5 ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => 'Invalid rating data',
			), 400 );
		}

		// Check if user already rated this scale (by user_id for logged-in; by IP for guests).
		if ( $user_id > 0 ) {
			$existing = $wpdb->get_row( $wpdb->prepare(
				"SELECT id FROM $table_name WHERE user_id = %d AND scale_id = %d",
				$user_id,
				$scale_id
			) );
		} else {
			// Guest: IP-based dedup with a 24-hour transient.
			$ip     = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
			$ip_key = 'naboo_guest_rating_' . md5( $ip . '_' . $scale_id );
			$existing = get_transient( $ip_key ) ? (object) array( 'id' => 0 ) : null;
			if ( ! $existing ) {
				set_transient( $ip_key, 1, DAY_IN_SECONDS );
			}
		}

		if ( $existing ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => 'You have already rated this scale',
			), 400 );
		}

		$result = $wpdb->insert(
			$table_name,
			array(
				'user_id' => $user_id,
				'scale_id' => $scale_id,
				'rating' => $rating,
				'review' => $review,
				'status' => get_option( 'naboo_require_rating_approval', 1 ) ? 'pending' : 'approved',
			),
			array( '%d', '%d', '%d', '%s', '%s' )
		);

		if ( ! $result ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => 'Failed to save rating',
			), 500 );
		}

		return rest_ensure_response( array(
			'success' => true,
			'message' => 'Rating submitted for moderation',
			'id' => $wpdb->insert_id,
		), 201 );
	}

	public function update_rating( $request ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_ratings';
		$id = intval( $request->get_param( 'id' ) );
		$user_id = get_current_user_id();

		$rating = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $table_name WHERE id = %d",
			$id
		) );

		if ( ! $rating || $rating->user_id != $user_id ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => 'Rating not found or permission denied',
			), 403 );
		}

		$params = $request->get_json_params();
		$new_rating = intval( $params['rating'] ?? $rating->rating );
		$review = sanitize_textarea_field( $params['review'] ?? $rating->review );

		if ( $new_rating < 1 || $new_rating > 5 ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => 'Invalid rating value',
			), 400 );
		}

		$result = $wpdb->update(
			$table_name,
			array(
				'rating' => $new_rating,
				'review' => $review,
				'status' => 'pending',
			),
			array( 'id' => $id ),
			array( '%d', '%s', '%s' ),
			array( '%d' )
		);

		if ( $result === false ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => 'Failed to update rating',
			), 500 );
		}

		return rest_ensure_response( array(
			'success' => true,
			'message' => 'Rating updated',
		) );
	}

	public function delete_rating( $request ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_ratings';
		$id = intval( $request->get_param( 'id' ) );
		$user_id = get_current_user_id();

		$rating = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $table_name WHERE id = %d",
			$id
		) );

		if ( ! $rating || $rating->user_id != $user_id ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => 'Rating not found or permission denied',
			), 403 );
		}

		$result = $wpdb->delete(
			$table_name,
			array( 'id' => $id ),
			array( '%d' )
		);

		if ( ! $result ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => 'Failed to delete rating',
			), 500 );
		}

		return rest_ensure_response( array(
			'success' => true,
			'message' => 'Rating deleted',
		) );
	}

	public function mark_helpful( $request ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_ratings';
		$id = intval( $request->get_param( 'id' ) );
		$params  = $request->get_json_params();
		$helpful = isset( $params['helpful'] ) && ( true === $params['helpful'] || 'true' === $params['helpful'] || 1 === $params['helpful'] || '1' === $params['helpful'] );

		// Rate-limit: max 1 helpful vote per IP per rating per hour.
		$ip     = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
		$rl_key = 'naboo_helpful_rl_' . md5( $ip . '_' . $id );
		if ( get_transient( $rl_key ) ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => 'You have already voted on this rating recently.',
			), 429 );
		}
		set_transient( $rl_key, 1, HOUR_IN_SECONDS );

		$rating = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $table_name WHERE id = %d",
			$id
		) );

		if ( ! $rating ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => 'Rating not found',
			), 404 );
		}

		if ( $helpful ) {
			$new_count = $rating->helpful_count + 1;
			$wpdb->update(
				$table_name,
				array( 'helpful_count' => $new_count ),
				array( 'id' => $id ),
				array( '%d' ),
				array( '%d' )
			);
		} else {
			$new_count = max( 0, $rating->unhelpful_count + 1 );
			$wpdb->update(
				$table_name,
				array( 'unhelpful_count' => $new_count ),
				array( 'id' => $id ),
				array( '%d' ),
				array( '%d' )
			);
		}

		return rest_ensure_response( array(
			'success' => true,
			'message' => 'Feedback recorded',
		) );
	}

	public function get_rating_stats( $request ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_ratings';
		$scale_id = intval( $request->get_param( 'scale_id' ) );

		$stats = $wpdb->get_row( $wpdb->prepare(
			"SELECT 
				COUNT(*) as total_ratings,
				AVG(rating) as average_rating,
				COUNT(CASE WHEN rating = 5 THEN 1 END) as five_star,
				COUNT(CASE WHEN rating = 4 THEN 1 END) as four_star,
				COUNT(CASE WHEN rating = 3 THEN 1 END) as three_star,
				COUNT(CASE WHEN rating = 2 THEN 1 END) as two_star,
				COUNT(CASE WHEN rating = 1 THEN 1 END) as one_star
			FROM $table_name 
			WHERE scale_id = %d AND status = 'approved'",
			$scale_id
		) );

		if ( ! $stats || ! $stats->total_ratings ) {
			return rest_ensure_response( array(
				'success' => true,
				'data' => array(
					'total_ratings' => 0,
					'average_rating' => 0,
					'distribution' => array(),
				),
			) );
		}

		return rest_ensure_response( array(
			'success' => true,
			'data' => array(
				'total_ratings' => intval( $stats->total_ratings ),
				'average_rating' => floatval( round( $stats->average_rating, 1 ) ),
				'distribution' => array(
					5 => intval( $stats->five_star ),
					4 => intval( $stats->four_star ),
					3 => intval( $stats->three_star ),
					2 => intval( $stats->two_star ),
					1 => intval( $stats->one_star ),
				),
			),
		) );
	}

	public function enqueue_scripts() {
		if ( ! get_option( 'naboo_enable_ratings', 1 ) ) {
			return;
		}

		wp_enqueue_script(
			$this->plugin_name . '-ratings',
			plugins_url( 'js/ratings.js', __FILE__ ),
			array( 'jquery', 'wp-api' ),
			$this->version,
			true
		);

		wp_enqueue_style(
			$this->plugin_name . '-ratings',
			plugins_url( 'css/ratings.css', __FILE__ ),
			array(),
			$this->version
		);

		wp_localize_script( $this->plugin_name . '-ratings', 'apaRatings', array(
			'api_url' => rest_url( 'apa/v1' ),
			'nonce' => wp_create_nonce( 'wp_rest' ),
			'user_id' => get_current_user_id(),
			'user_logged_in' => is_user_logged_in(),
		) );
	}

	public function inject_rating_section( $content ) {
		if ( ! get_option( 'naboo_enable_ratings', 1 ) ) {
			return $content;
		}

		if ( ! is_singular( 'psych_scale' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$scale_id = get_the_ID();
		$html  = '<div class="naboo-ratings-section">';
		$html .= '<h3 class="naboo-ratings-title">' . __( 'User Ratings & Reviews', 'naboodatabase' ) . '</h3>';
		$html .= '<div id="naboo-ratings-container" data-scale-id="' . esc_attr( $scale_id ) . '"></div>';
		$html .= '</div>';

		return $content . $html;
	}

	public function add_rating_section() {
		if ( ! get_option( 'naboo_enable_ratings', 1 ) ) {
			return;
		}

		if ( ! is_singular( 'psych_scale' ) ) {
			return;
		}

		$scale_id = get_the_ID();
		echo '<div class="naboo-ratings-section">';
		echo '<h3 class="naboo-ratings-title">User Ratings & Reviews</h3>';
		echo '<div id="naboo-ratings-container" data-scale-id="' . esc_attr( $scale_id ) . '"></div>';
		echo '</div>';
	}

	public function check_user_logged_in() {
		return is_user_logged_in();
	}

	public function add_default_rating_on_publish( $post_id, $post, $update ) {
		// Only run for authentic psych_scale saves
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		// Use a meta key to ensure we only add the default rating exactly once per scale.
		if ( get_post_meta( $post_id, '_naboo_default_rating_added', true ) ) {
			return;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_ratings';

		// Insert a default 5-star rating
		$wpdb->insert(
			$table_name,
			array(
				'user_id' => $post->post_author ?: 1, // Attribute to post author or fallback to admin
				'scale_id' => $post_id,
				'rating' => 5,
				'review' => '', // Empty review
				'status' => 'approved',
			),
			array( '%d', '%d', '%d', '%s', '%s' )
		);

		update_post_meta( $post_id, '_naboo_default_rating_added', 1 );
	}
}
