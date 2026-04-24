<?php
/**
 * Submission Management Queue
 *
 * @package ArabPsychology\NabooDatabase\Admin
 */

namespace ArabPsychology\NabooDatabase\Admin;

/**
 * Submission_Management_Queue class - Advanced submission review and management.
 */
class Submission_Management_Queue {

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
	}

	/**
	 * Register REST API endpoints
	 */
	public function register_endpoints() {
		register_rest_route(
			'apa/v1',
			'/submissions/queue',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_submission_queue' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/submissions/(?P<id>\d+)/approve',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'approve_submission' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/submissions/(?P<id>\d+)/reject',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'reject_submission' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/submissions/(?P<id>\d+)/request-changes',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'request_changes' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/submissions/bulk-action',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'bulk_action' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/submissions/(?P<id>\d+)/details',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_submission_details' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}

	/**
	 * Get submission queue
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_submission_queue( $request ) {
		$per_page = $request->get_param( 'per_page' ) ?? 20;
		$page     = $request->get_param( 'page' ) ?? 1;
		$status   = $request->get_param( 'status' ) ?? 'pending';

		$args = array(
			'post_type'      => 'psych_scale',
			'post_status'    => $status,
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		$query = new \WP_Query( $args );

		$submissions = array();
		foreach ( $query->posts as $post ) {
			$submissions[] = $this->format_submission( $post );
		}

		return new \WP_REST_Response(
			array(
				'submissions'  => $submissions,
				'total'        => $query->found_posts,
				'total_pages'  => $query->max_num_pages,
				'current_page' => $page,
			),
			200
		);
	}

	/**
	 * Format submission data
	 *
	 * @param \WP_Post $post The post object.
	 * @return array
	 */
	private function format_submission( $post ) {
		$author = get_user_by( 'id', $post->post_author );

		return array(
			'id'              => $post->ID,
			'title'           => $post->post_title,
			'excerpt'         => $post->post_excerpt,
			'status'          => $post->post_status,
			'author'          => $author ? $author->display_name : 'Unknown',
			'author_email'    => $author ? $author->user_email : '',
			'author_id'       => $post->post_author,
			'submitted_date'  => $post->post_date,
			'modified_date'   => $post->post_modified,
			'items'           => get_post_meta( $post->ID, '_naboo_scale_items', true ),
			'language'        => get_post_meta( $post->ID, '_naboo_scale_language', true ),
			'population'      => get_post_meta( $post->ID, '_naboo_scale_population', true ),
			'categories'      => wp_get_post_terms( $post->ID, 'scale_category', array( 'fields' => 'names' ) ),
		);
	}

	/**
	 * Approve submission
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function approve_submission( $request ) {
		$post_id = $request->get_param( 'id' );
		$message = $request->get_param( 'message' ) ?? '';

		$post = get_post( $post_id );

		if ( ! $post || $post->post_type !== 'psych_scale' ) {
			return new \WP_REST_Response(
				array( 'error' => 'Submission not found' ),
				404
			);
		}

		// Update post status
		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'publish',
			)
		);

		// Send notification email
		$this->send_approval_email( $post, $message );

		// Log action
		update_post_meta( $post_id, '_naboo_submission_approved_by', get_current_user_id() );
		update_post_meta( $post_id, '_naboo_submission_approved_at', current_time( 'mysql' ) );

		return new \WP_REST_Response(
			array( 'message' => 'Submission approved successfully' ),
			200
		);
	}

	/**
	 * Reject submission
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function reject_submission( $request ) {
		$post_id = $request->get_param( 'id' );
		$reason  = $request->get_param( 'reason' ) ?? 'Does not meet our requirements';

		$post = get_post( $post_id );

		if ( ! $post || $post->post_type !== 'psych_scale' ) {
			return new \WP_REST_Response(
				array( 'error' => 'Submission not found' ),
				404
			);
		}

		// Update post status
		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'trash',
			)
		);

		// Send rejection email
		$this->send_rejection_email( $post, $reason );

		// Log action
		update_post_meta( $post_id, '_naboo_submission_rejected_by', get_current_user_id() );
		update_post_meta( $post_id, '_naboo_submission_rejected_at', current_time( 'mysql' ) );
		update_post_meta( $post_id, '_naboo_rejection_reason', $reason );

		return new \WP_REST_Response(
			array( 'message' => 'Submission rejected' ),
			200
		);
	}

	/**
	 * Request changes
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function request_changes( $request ) {
		$post_id  = $request->get_param( 'id' );
		$feedback = $request->get_param( 'feedback' ) ?? '';

		$post = get_post( $post_id );

		if ( ! $post || $post->post_type !== 'psych_scale' ) {
			return new \WP_REST_Response(
				array( 'error' => 'Submission not found' ),
				404
			);
		}

		// Update post status
		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'draft',
			)
		);

		// Send feedback email
		$this->send_feedback_email( $post, $feedback );

		// Log action
		update_post_meta( $post_id, '_naboo_feedback_requested_by', get_current_user_id() );
		update_post_meta( $post_id, '_naboo_feedback_requested_at', current_time( 'mysql' ) );
		update_post_meta( $post_id, '_naboo_feedback_message', $feedback );

		return new \WP_REST_Response(
			array( 'message' => 'Feedback request sent' ),
			200
		);
	}

	/**
	 * Bulk action
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function bulk_action( $request ) {
		$post_ids = $request->get_param( 'post_ids' ) ?? array();
		$action   = $request->get_param( 'action' ) ?? '';

		if ( empty( $post_ids ) || ! is_array( $post_ids ) || ! in_array( $action, array( 'approve', 'reject' ), true ) ) {
			return new \WP_REST_Response(
				array( 'error' => 'Invalid action or empty post IDs' ),
				400
			);
		}

		$updated = 0;

		foreach ( $post_ids as $post_id ) {
			if ( 'approve' === $action ) {
				wp_update_post(
					array(
						'ID'          => $post_id,
						'post_status' => 'publish',
					)
				);
				$updated++;
			} elseif ( 'reject' === $action ) {
				wp_update_post(
					array(
						'ID'          => $post_id,
						'post_status' => 'trash',
					)
				);
				$updated++;
			}
		}

		return new \WP_REST_Response(
			array( 'message' => sprintf( '%d submissions updated', $updated ) ),
			200
		);
	}

	/**
	 * Get submission details
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_submission_details( $request ) {
		$post_id = $request->get_param( 'id' );
		$post    = get_post( $post_id );

		if ( ! $post || $post->post_type !== 'psych_scale' ) {
			return new \WP_REST_Response(
				array( 'error' => 'Submission not found' ),
				404
			);
		}

		$submission = $this->format_submission( $post );
		$submission['content'] = $post->post_content;
		$submission['reliability'] = get_post_meta( $post_id, '_naboo_scale_reliability', true );
		$submission['validity'] = get_post_meta( $post_id, '_naboo_scale_validity', true );
		$submission['year'] = get_post_meta( $post_id, '_naboo_scale_year', true );
		$submission['approval_history'] = array(
			'approved_by' => get_post_meta( $post_id, '_naboo_submission_approved_by', true ),
			'approved_at' => get_post_meta( $post_id, '_naboo_submission_approved_at', true ),
			'rejected_by' => get_post_meta( $post_id, '_naboo_submission_rejected_by', true ),
			'rejected_at' => get_post_meta( $post_id, '_naboo_submission_rejected_at', true ),
			'rejection_reason' => get_post_meta( $post_id, '_naboo_rejection_reason', true ),
		);

		return new \WP_REST_Response( $submission, 200 );
	}

	/**
	 * Send approval email
	 *
	 * @param \WP_Post $post The post object.
	 * @param string   $message Custom message.
	 */
	private function send_approval_email( $post, $message = '' ) {
		$author = get_user_by( 'id', $post->post_author );

		if ( ! $author ) {
			return;
		}

		$subject = sprintf(
			__( 'Your Scale "%s" Has Been Approved', 'naboodatabase' ),
			$post->post_title
		);

		$body = sprintf(
			__( 'Dear %s,

Your submitted scale "%s" has been approved and is now published in our database.

%s

You can view it here: %s

Best regards,
%s',
			'naboodatabase'
		),
			$author->display_name,
			$post->post_title,
			$message ? sprintf( __( 'Message from reviewer: %s', 'naboodatabase' ), $message ) : '',
			get_permalink( $post->ID ),
			get_bloginfo( 'name' )
		);

		wp_mail( $author->user_email, $subject, $body );
	}

	/**
	 * Send rejection email
	 *
	 * @param \WP_Post $post The post object.
	 * @param string   $reason Reason for rejection.
	 */
	private function send_rejection_email( $post, $reason ) {
		$author = get_user_by( 'id', $post->post_author );

		if ( ! $author ) {
			return;
		}

		$subject = sprintf(
			__( 'Your Scale "%s" Was Not Approved', 'naboodatabase' ),
			$post->post_title
		);

		$body = sprintf(
			__( 'Dear %s,

Your submitted scale "%s" was not approved for publication.

Reason: %s

If you have any questions, please contact us.

Best regards,
%s',
			'naboodatabase'
		),
			$author->display_name,
			$post->post_title,
			$reason,
			get_bloginfo( 'name' )
		);

		wp_mail( $author->user_email, $subject, $body );
	}

	/**
	 * Send feedback email
	 *
	 * @param \WP_Post $post The post object.
	 * @param string   $feedback Feedback message.
	 */
	private function send_feedback_email( $post, $feedback ) {
		$author = get_user_by( 'id', $post->post_author );

		if ( ! $author ) {
			return;
		}

		$subject = sprintf(
			__( 'Changes Requested for "%s"', 'naboodatabase' ),
			$post->post_title
		);

		$body = sprintf(
			__( 'Dear %s,

We need some changes before we can approve your scale "%s".

Feedback:
%s

Please make the requested changes and resubmit.

Best regards,
%s',
			'naboodatabase'
		),
			$author->display_name,
			$post->post_title,
			$feedback,
			get_bloginfo( 'name' )
		);

		wp_mail( $author->user_email, $subject, $body );
		wp_mail( $author->user_email, $subject, $body );
	}

	/**
	 * Register Admin Menu
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'edit.php?post_type=psych_scale',
			__( 'Submission Queue', 'naboodatabase' ),
			__( 'Submission Queue', 'naboodatabase' ),
			'manage_options',
			'naboo-submission-queue',
			array( $this, 'render_admin_page' ),
			10
		);
	}

	/**
	 * Enqueue Admin Scripts
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( 'psych_scale_page_naboo-submission-queue' !== $hook ) {
			return;
		}

		wp_enqueue_script(
			$this->plugin_name . '-submission-queue',
			plugin_dir_url( __FILE__ ) . 'js/submission-queue-admin.js',
			array( 'jquery', 'wp-api-fetch' ),
			$this->version,
			true
		);

		wp_localize_script(
			$this->plugin_name . '-submission-queue',
			'apaSubmissions',
			array(
				'apiUrl' => rest_url( 'apa/v1/submissions' ),
				'nonce'  => wp_create_nonce( 'wp_rest' ),
			)
		);

		wp_enqueue_style(
			$this->plugin_name . '-submission-queue',
			plugin_dir_url( __FILE__ ) . 'css/submission-queue-admin.css',
			array(),
			$this->version
		);
	}

	/**
	 * Render Admin Page UI
	 */
	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'naboodatabase' ) );
		}
		?>
		<div class="wrap naboo-admin-wrap">
			
			<!-- Header -->
			<div class="naboo-admin-header" style="margin-bottom: 32px; padding: 40px; background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border-radius: 16px; color: white; box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1); position: relative; overflow: hidden;">
				<div style="position: absolute; top: -50px; right: -50px; width: 200px; height: 200px; background: rgba(99, 102, 241, 0.1); filter: blur(80px); border-radius: 50%;"></div>
				<div style="display: flex; justify-content: space-between; align-items: center; position: relative; z-index: 1;">
					<div class="naboo-admin-header-left">
						<h1 style="color: white !important; font-size: 36px !important; margin: 0 !important; font-weight: 800; letter-spacing: -0.025em; display: flex; align-items: center; gap: 20px;">
							<span style="background: rgba(255,255,255,0.1); width: 64px; height: 64px; display: flex; align-items: center; justify-content: center; border-radius: 16px; backdrop-filter: blur(4px); border: 1px solid rgba(255,255,255,0.1);">📥</span>
							<?php esc_html_e( 'Submission Queue', 'naboodatabase' ); ?>
						</h1>
						<p style="margin: 16px 0 0 84px !important; color: #94a3b8; font-size: 18px; max-width: 600px; line-height: 1.6;"><?php esc_html_e( 'Review and moderate new scale submissions. Maintain the quality and accuracy of the psychological database.', 'naboodatabase' ); ?></p>
					</div>
				</div>
			</div>

			<!-- Tabs -->
			<div class="naboo-queue-tabs">
				<button class="naboo-tab-btn active" data-status="pending">
					<span style="margin-right:8px;">🕐</span><?php esc_html_e( 'Pending Review', 'naboodatabase' ); ?>
				</button>
				<button class="naboo-tab-btn" data-status="publish">
					<span style="margin-right:8px;">✅</span><?php esc_html_e( 'Published', 'naboodatabase' ); ?>
				</button>
				<button class="naboo-tab-btn" data-status="trash">
					<span style="margin-right:8px;">🚫</span><?php esc_html_e( 'Rejected', 'naboodatabase' ); ?>
				</button>
			</div>

			<div class="naboo-queue-container">
				<div id="naboo-queue-list-wrapper">
					<!-- Bulk Actions Bar -->
					<div style="display:flex; align-items:center; justify-content: space-between; padding: 20px 24px; background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
						<div style="display:flex; align-items:center; gap:12px;">
							<select id="naboo-bulk-action" style="padding:10px 16px; border:1px solid #cbd5e1; border-radius:10px; font-size:14px; background: white; font-weight: 600; cursor: pointer; min-width: 180px;">
								<option value=""><?php esc_html_e( 'Batch Operations…', 'naboodatabase' ); ?></option>
								<option value="approve"><?php esc_html_e( 'Approve Selected', 'naboodatabase' ); ?></option>
								<option value="reject"><?php esc_html_e( 'Reject Selected', 'naboodatabase' ); ?></option>
							</select>
							<button type="button" class="naboo-btn" id="naboo-apply-bulk" style="background: #1e293b; color: white; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 700; cursor: pointer; transition: all 0.2s;">
								<?php esc_html_e( 'Apply to Selected', 'naboodatabase' ); ?>
							</button>
						</div>
						<div id="naboo-queue-stats" style="font-size: 14px; font-weight: 700; color: #64748b; background: #fff; padding: 8px 16px; border-radius: 999px; border: 1px solid #e2e8f0;">
							<!-- Total items count will be here -->
						</div>
					</div>

					<table class="wp-list-table widefat fixed striped posts" style="border:none !important; box-shadow: none !important;">
						<thead style="background: #f8fafc;">
							<tr>
								<td id="cb" class="manage-column column-cb check-column" style="padding: 20px 10px !important;"><input id="cb-select-all-1" type="checkbox" style="width:18px; height:18px; accent-color:#4f46e5;"></td>
								<th scope="col" class="manage-column column-title column-primary" style="padding: 20px 10px !important; font-weight:800; text-transform:uppercase; font-size:11px; color:#64748b; letter-spacing:0.05em;"><?php esc_html_e( 'Scale Title', 'naboodatabase' ); ?></th>
								<th scope="col" class="manage-column column-author" style="padding: 20px 10px !important; font-weight:800; text-transform:uppercase; font-size:11px; color:#64748b; letter-spacing:0.05em;"><?php esc_html_e( 'Contributor', 'naboodatabase' ); ?></th>
								<th scope="col" class="manage-column column-categories" style="padding: 20px 10px !important; font-weight:800; text-transform:uppercase; font-size:11px; color:#64748b; letter-spacing:0.05em;"><?php esc_html_e( 'Categories', 'naboodatabase' ); ?></th>
								<th scope="col" class="manage-column column-date" style="padding: 20px 10px !important; font-weight:800; text-transform:uppercase; font-size:11px; color:#64748b; letter-spacing:0.05em;"><?php esc_html_e( 'Submitted', 'naboodatabase' ); ?></th>
								<th scope="col" class="manage-column column-actions" style="padding: 20px 10px !important; font-weight:800; text-transform:uppercase; font-size:11px; color:#64748b; letter-spacing:0.05em; text-align:right; padding-right:24px !important;"><?php esc_html_e( 'Actions', 'naboodatabase' ); ?></th>
							</tr>
						</thead>
						<tbody id="naboo-queue-list">
							<!-- Populated via AJAX -->
						</tbody>
					</table>
					
					<div class="naboo-queue-pagination" style="padding: 20px 24px; border-top: 1px solid #e2e8f0; background: #f8fafc;">
						<!-- Populated via AJAX -->
					</div>
				</div>

				<div id="naboo-submission-details-panel" class="naboo-side-panel" style="display:none;">
					<div class="naboo-panel-header">
						<h2><?php esc_html_e( 'Submission Details', 'naboodatabase' ); ?></h2>
						<button type="button" class="naboo-close-panel" aria-label="<?php esc_attr_e( 'Close panel', 'naboodatabase' ); ?>">&times;</button>
					</div>
					<div class="naboo-panel-content">
						<!-- Populated via AJAX -->
					</div>
					<div class="naboo-panel-footer">
						<button type="button" class="button button-primary naboo-approve-btn" style="flex: 1;"><?php esc_html_e( 'Approve', 'naboodatabase' ); ?></button>
						<button type="button" class="button button-secondary naboo-request-changes-btn" style="flex: 1;"><?php esc_html_e( 'Request Changes', 'naboodatabase' ); ?></button>
						<div style="width: 100%; text-align: center; margin-top: 8px;">
							<button type="button" class="button button-link-delete naboo-reject-btn" style="font-weight:700 !important;"><?php esc_html_e( 'Direct Reject', 'naboodatabase' ); ?></button>
						</div>
					</div>
				</div>
			</div>
			
			<!-- Modal for Request Changes / Rejection message -->
			<div id="naboo-action-modal" class="naboo-modal" style="display:none;">
				<div class="naboo-modal-content">
					<h3 id="naboo-modal-title"></h3>
					<div style="margin-bottom: 20px;">
						<textarea id="naboo-modal-textarea" rows="6" style="width:100%; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0; font-family: inherit; font-size: 14px; background: #f8fafc;" placeholder="<?php esc_attr_e( 'Enter message or reason...', 'naboodatabase' ); ?>"></textarea>
					</div>
					<div class="naboo-modal-actions">
						<button type="button" class="naboo-btn" id="naboo-modal-cancel" style="background: white; border: 1px solid #e2e8f0; color: #64748b; padding: 12px 24px; border-radius: 10px; font-weight: 700; cursor: pointer;"><?php esc_html_e( 'Go Back', 'naboodatabase' ); ?></button>
						<button type="button" class="naboo-btn" id="naboo-modal-submit" style="background: #4f46e5; color: white; border: none; padding: 12px 24px; border-radius: 10px; font-weight: 700; cursor: pointer;"><?php esc_html_e( 'Send Message', 'naboodatabase' ); ?></button>
					</div>
				</div>
			</div>

		</div>
		<?php
	}
}
