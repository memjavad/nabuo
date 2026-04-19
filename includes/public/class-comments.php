<?php

namespace ArabPsychology\NabooDatabase\Public;

class Comments {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	public function create_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_comments';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			scale_id bigint(20) NOT NULL,
			parent_id bigint(20) DEFAULT 0,
			user_id bigint(20) NOT NULL,
			user_name varchar(255),
			user_email varchar(255),
			comment_text longtext NOT NULL,
			status varchar(20) DEFAULT 'pending',
			helpful_count int(11) DEFAULT 0,
			unhelpful_count int(11) DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY scale_id (scale_id),
			KEY parent_id (parent_id),
			KEY user_id (user_id),
			KEY status (status),
			KEY created_at (created_at)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	public function register_endpoints() {
		register_rest_route( 'apa/v1', '/comments/(?P<scale_id>\d+)', array(
			'methods' => 'GET',
			'callback' => array( $this, 'get_comments' ),
			'permission_callback' => '__return_true',
		) );

		register_rest_route( 'apa/v1', '/comments', array(
			'methods' => 'POST',
			'callback' => array( $this, 'add_comment' ),
			'permission_callback' => array( $this, 'check_user_logged_in' ),
		) );

		register_rest_route( 'apa/v1', '/comments/(?P<id>\d+)', array(
			'methods' => 'PUT',
			'callback' => array( $this, 'update_comment' ),
			'permission_callback' => array( $this, 'check_user_logged_in' ),
		) );

		register_rest_route( 'apa/v1', '/comments/(?P<id>\d+)', array(
			'methods' => 'DELETE',
			'callback' => array( $this, 'delete_comment' ),
			'permission_callback' => array( $this, 'check_user_logged_in' ),
		) );

		register_rest_route( 'apa/v1', '/comments/(?P<id>\d+)/helpful', array(
			'methods' => 'POST',
			'callback' => array( $this, 'mark_helpful' ),
			'permission_callback' => array( $this, 'check_user_logged_in' ),
		) );

		register_rest_route( 'apa/v1', '/comments/count/(?P<scale_id>\d+)', array(
			'methods' => 'GET',
			'callback' => array( $this, 'get_comment_count' ),
			'permission_callback' => '__return_true',
		) );
	}

	public function get_comments( $request ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_comments';
		$scale_id = intval( $request->get_param( 'scale_id' ) );
		$page = intval( $request->get_param( 'page' ) ?? 1 );
		$per_page = intval( $request->get_param( 'per_page' ) ?? 20 );
		$offset = ( $page - 1 ) * $per_page;

		// Get top-level comments and their replies
		$comments = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM $table_name WHERE scale_id = %d AND status = 'approved' AND parent_id = 0 
			 ORDER BY helpful_count DESC, created_at DESC LIMIT %d OFFSET %d",
			$scale_id,
			$per_page,
			$offset
		) );

		// Get replies for each comment
		foreach ( $comments as $comment ) {
			$replies = $wpdb->get_results( $wpdb->prepare(
				"SELECT * FROM $table_name WHERE scale_id = %d AND parent_id = %d AND status = 'approved' 
				 ORDER BY created_at ASC",
				$scale_id,
				$comment->id
			) );
			$comment->replies = $replies;
		}

		$total = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM $table_name WHERE scale_id = %d AND status = 'approved' AND parent_id = 0",
			$scale_id
		) );

		return rest_ensure_response( array(
			'success' => true,
			'data' => $comments,
			'total' => intval( $total ),
			'page' => $page,
			'per_page' => $per_page,
		) );
	}

	public function add_comment( $request ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_comments';
		$user_id = get_current_user_id();

		$params = $request->get_json_params();
		$scale_id = intval( $params['scale_id'] ?? 0 );
		$parent_id = intval( $params['parent_id'] ?? 0 );
		$comment_text = sanitize_textarea_field( $params['comment'] ?? '' );

		if ( ! $scale_id || empty( $comment_text ) ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => 'Invalid comment data',
			), 400 );
		}

		// Verify scale exists
		if ( ! get_post( $scale_id ) || 'psych_scale' !== get_post_type( $scale_id ) ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => 'Scale not found',
			), 404 );
		}

		// Verify parent comment exists if replying
		if ( $parent_id > 0 ) {
			$parent = $wpdb->get_row( $wpdb->prepare(
				"SELECT id FROM $table_name WHERE id = %d AND scale_id = %d",
				$parent_id,
				$scale_id
			) );
			if ( ! $parent ) {
				return rest_ensure_response( array(
					'success' => false,
					'message' => 'Parent comment not found',
				), 404 );
			}
		}

		// Check rate limiting (max 10 comments per hour per logged-in user; 5 per hour for guests via IP).
		if ( $user_id > 0 ) {
			$recent_count = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
				$user_id
			) );
			$max_comments = 10;
		} else {
			$ip           = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
			$rl_key       = 'naboo_guest_comment_rl_' . md5( $ip );
			$recent_count = (int) get_transient( $rl_key );
			set_transient( $rl_key, $recent_count + 1, HOUR_IN_SECONDS );
			$max_comments = 5;
		}

		if ( $recent_count >= $max_comments ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => 'Too many comments. Please try again later.',
			), 429 );
		}

		// Check for spam patterns
		if ( $this->is_spam( $comment_text ) ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => 'Your comment looks like spam. Please try again.',
			), 400 );
		}

		$user = wp_get_current_user();

		$result = $wpdb->insert(
			$table_name,
			array(
				'scale_id' => $scale_id,
				'parent_id' => $parent_id,
				'user_id' => $user_id,
				'user_name' => $user->display_name,
				'user_email' => $user->user_email,
				'comment_text' => $comment_text,
				'status' => 'pending',
			),
			array( '%d', '%d', '%d', '%s', '%s', '%s', '%s' )
		);

		if ( ! $result ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => 'Failed to save comment',
			), 500 );
		}

		return rest_ensure_response( array(
			'success' => true,
			'message' => 'Comment submitted for moderation',
			'id' => $wpdb->insert_id,
		), 201 );
	}

	public function update_comment( $request ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_comments';
		$id = intval( $request->get_param( 'id' ) );
		$user_id = get_current_user_id();

		$comment = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $table_name WHERE id = %d",
			$id
		) );

		if ( ! $comment || $comment->user_id != $user_id ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => 'Comment not found or permission denied',
			), 403 );
		}

		// Can only edit pending or approved comments within 24 hours
		$time_diff = strtotime( 'now' ) - strtotime( $comment->created_at );
		if ( $time_diff > 86400 ) { // 24 hours
			return rest_ensure_response( array(
				'success' => false,
				'message' => 'Can only edit comments within 24 hours of posting',
			), 403 );
		}

		$params = $request->get_json_params();
		$comment_text = sanitize_textarea_field( $params['comment'] ?? $comment->comment_text );

		if ( empty( $comment_text ) ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => 'Comment text cannot be empty',
			), 400 );
		}

		$result = $wpdb->update(
			$table_name,
			array(
				'comment_text' => $comment_text,
				'status' => 'pending',
			),
			array( 'id' => $id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		if ( $result === false ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => 'Failed to update comment',
			), 500 );
		}

		return rest_ensure_response( array(
			'success' => true,
			'message' => 'Comment updated and sent for re-moderation',
		) );
	}

	public function delete_comment( $request ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_comments';
		$id = intval( $request->get_param( 'id' ) );
		$user_id = get_current_user_id();

		$comment = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $table_name WHERE id = %d",
			$id
		) );

		if ( ! $comment || $comment->user_id != $user_id ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => 'Comment not found or permission denied',
			), 403 );
		}

		// Delete the comment and its replies
		$wpdb->delete(
			$table_name,
			array( 'id' => $id ),
			array( '%d' )
		);

		$wpdb->delete(
			$table_name,
			array( 'parent_id' => $id ),
			array( '%d' )
		);

		return rest_ensure_response( array(
			'success' => true,
			'message' => 'Comment deleted',
		) );
	}

	public function mark_helpful( $request ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_comments';
		$id = intval( $request->get_param( 'id' ) );
		$params  = $request->get_json_params();
		$helpful = isset( $params['helpful'] ) && ( true === $params['helpful'] || 'true' === $params['helpful'] || 1 === $params['helpful'] || '1' === $params['helpful'] );

		$comment = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $table_name WHERE id = %d",
			$id
		) );

		if ( ! $comment ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => 'Comment not found',
			), 404 );
		}

		if ( $helpful ) {
			$new_count = $comment->helpful_count + 1;
			$wpdb->update(
				$table_name,
				array( 'helpful_count' => $new_count ),
				array( 'id' => $id ),
				array( '%d' ),
				array( '%d' )
			);
		} else {
			$new_count = max( 0, $comment->unhelpful_count + 1 );
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

	public function get_comment_count( $request ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_comments';
		$scale_id = intval( $request->get_param( 'scale_id' ) );

		$count = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM $table_name WHERE scale_id = %d AND status = 'approved'",
			$scale_id
		) );

		return rest_ensure_response( array(
			'success' => true,
			'count' => intval( $count ),
		) );
	}

	public function enqueue_scripts() {
		wp_enqueue_script(
			$this->plugin_name . '-comments',
			plugins_url( 'js/comments.js', __FILE__ ),
			array( 'jquery', 'wp-api' ),
			$this->version,
			true
		);

		wp_enqueue_style(
			$this->plugin_name . '-comments',
			plugins_url( 'css/comments.css', __FILE__ ),
			array(),
			$this->version
		);

		wp_localize_script( $this->plugin_name . '-comments', 'apaComments', array(
			'api_url' => rest_url( 'apa/v1' ),
			'nonce' => wp_create_nonce( 'wp_rest' ),
			'user_id' => get_current_user_id(),
			'user_logged_in' => is_user_logged_in(),
			'login_url' => wp_login_url( get_the_permalink() ),
		) );
	}

	public function inject_comments_section( $content ) {
		if ( ! is_singular( 'psych_scale' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$scale_id = get_the_ID();
		$html  = '<div class="naboo-comments-section">';
		$html .= '<h3 class="naboo-comments-title">' . __( 'Discussion & Comments', 'naboodatabase' ) . '</h3>';

		if ( is_user_logged_in() ) {
			$html .= '<div class="naboo-comment-form-wrapper">';
			$html .= $this->render_comment_form( $scale_id );
			$html .= '</div>';
		} else {
			$html .= '<div class="naboo-comments-login-prompt">';
			$html .= '<p><i class="naboo-icon">💬</i> ' . sprintf( __( 'Please %slog in%s to leave a comment.', 'naboodatabase' ), '<a href="' . esc_url( wp_login_url( get_the_permalink() ) ) . '">', '</a>' ) . '</p>';
			$html .= '</div>';
		}

		$html .= '<div id="naboo-comments-container" data-scale-id="' . esc_attr( $scale_id ) . '" class="naboo-comments-list"></div>';
		$html .= '</div>';

		return $content . $html;
	}

	public function display_comments_section() {
		if ( ! is_singular( 'psych_scale' ) ) {
			return;
		}

		$scale_id = get_the_ID();
		echo '<div class="naboo-comments-section">';
		echo '<h3 class="naboo-comments-title">Discussion & Comments</h3>';
		
		if ( is_user_logged_in() ) {
			echo '<div class="naboo-comment-form-wrapper">';
			echo $this->render_comment_form( $scale_id );
			echo '</div>';
		} else {
			echo '<div class="naboo-comments-login-prompt">';
			echo '<p><i class="naboo-icon">💬</i> Please <a href="' . esc_url( wp_login_url( get_the_permalink() ) ) . '">log in</a> to leave a comment.</p>';
			echo '</div>';
		}

		echo '<div id="naboo-comments-container" data-scale-id="' . esc_attr( $scale_id ) . '" class="naboo-comments-list"></div>';
		echo '</div>';
	}

	private function render_comment_form( $scale_id ) {
		$html = '<div class="naboo-comment-form">';
		$html .= '<h4>Leave a Comment</h4>';
		$html .= '<textarea id="naboo-comment-text" class="naboo-comment-textarea" placeholder="Share your thoughts about this scale..." maxlength="5000"></textarea>';
		$html .= '<div class="naboo-comment-form-footer">';
		$html .= '<span class="naboo-comment-char-count"><span id="naboo-char-count">0</span>/5000</span>';
		$html .= '<button type="button" class="naboo-comment-submit-btn" data-scale-id="' . esc_attr( $scale_id ) . '">Post Comment</button>';
		$html .= '</div>';
		$html .= '</div>';
		return $html;
	}

	private function is_spam( $text ) {
		// Check for excessive links
		$links = preg_match_all( '/https?:\/\//', $text );
		if ( $links > 3 ) {
			return true;
		}

		// Check for excessive capitalization
		$caps = preg_match_all( '/[A-Z]/', $text );
		if ( $caps > strlen( $text ) * 0.7 ) {
			return true;
		}

		// Check for repeated characters
		if ( preg_match( '/(.)\1{4,}/', $text ) ) {
			return true;
		}

		return false;
	}

	public function check_user_logged_in() {
		return is_user_logged_in();
	}
}
