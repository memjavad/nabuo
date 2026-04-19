<?php
/**
 * Ratings Moderation Admin Page
 *
 * @package ArabPsychology\NabooDatabase\Admin
 */

namespace ArabPsychology\NabooDatabase\Admin;

/**
 * Ratings_Moderation class – admin page for reviewing NABOO scale ratings.
 */
class Ratings_Moderation {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/* ── Menu registration ───────────────────────────────────────────────── */

	public function register_menu() {
		add_submenu_page(
			'edit.php?post_type=psych_scale',
			__( 'Ratings Moderation', 'naboodatabase' ),
			__( '⭐ Ratings', 'naboodatabase' ),
			'manage_options',
			'naboo-ratings-moderation',
			array( $this, 'render_page' ),
			13
		);
	}

	/* ── Admin-post handler (approve / reject / delete) ─────────────────── */

	public function handle_action() {
		if ( ! isset( $_POST['naboo_rating_action_nonce'] ) ||
			! wp_verify_nonce( $_POST['naboo_rating_action_nonce'], 'naboo_rating_action' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'naboodatabase' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'naboodatabase' ) );
		}

		global $wpdb;
		$table     = $wpdb->prefix . 'naboo_ratings';
		$action    = sanitize_text_field( $_POST['naboo_action'] ?? '' );
		$rating_id = absint( $_POST['rating_id'] ?? 0 );
		$bulk_ids  = array_map( 'absint', (array) ( $_POST['bulk_ids'] ?? array() ) );
		$redirect  = admin_url( 'admin.php?page=naboo-ratings-moderation&tab=' . sanitize_text_field( $_POST['current_tab'] ?? 'pending' ) );

		if ( $rating_id ) {
			$bulk_ids = array( $rating_id );
		}

		if ( empty( $bulk_ids ) ) {
			wp_safe_redirect( add_query_arg( 'notice', 'none_selected', $redirect ) );
			exit;
		}

		$ids_placeholder = implode( ',', array_fill( 0, count( $bulk_ids ), '%d' ) );

		switch ( $action ) {
			case 'approve':
				$wpdb->query(
					$wpdb->prepare(
						"UPDATE `{$table}` SET status = 'approved' WHERE id IN ({$ids_placeholder})",
						...$bulk_ids
					)
				);
				$notice = 'approved';
				break;

			case 'reject':
				$wpdb->query(
					$wpdb->prepare(
						"UPDATE `{$table}` SET status = 'rejected' WHERE id IN ({$ids_placeholder})",
						...$bulk_ids
					)
				);
				$notice = 'rejected';
				break;

			case 'spam':
				$wpdb->query(
					$wpdb->prepare(
						"UPDATE `{$table}` SET status = 'spam' WHERE id IN ({$ids_placeholder})",
						...$bulk_ids
					)
				);
				$notice = 'spam';
				break;

			case 'delete':
				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM `{$table}` WHERE id IN ({$ids_placeholder})",
						...$bulk_ids
					)
				);
				$notice = 'deleted';
				break;

			default:
				$notice = 'unknown';
		}

		wp_safe_redirect( add_query_arg( 'notice', $notice, $redirect ) );
		exit;
	}

	/* ── Page renderer ───────────────────────────────────────────────────── */

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'naboodatabase' ) );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'naboo_ratings';

		// Table existence guard
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			echo '<div class="wrap naboo-admin-page">';
			echo '<div class="naboo-notice info" style="margin-top:24px;">ℹ️ ' . esc_html__( 'The ratings table has not been created yet.', 'naboodatabase' ) . '</div>';
			echo '</div>';
			return;
		}

		$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'pending';
		$search     = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
		$paged      = max( 1, absint( $_GET['paged'] ?? 1 ) );
		$per_page   = 20;
		$offset     = ( $paged - 1 ) * $per_page;

		// Count per status
		$counts = array();
		foreach ( array( 'pending', 'approved', 'rejected', 'spam' ) as $s ) {
			$counts[ $s ] = (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(*) FROM `{$table}` WHERE status = %s", $s )
			);
		}
		$counts['all'] = array_sum( $counts );

		// Build WHERE
		$where = $active_tab !== 'all'
			? $wpdb->prepare( 'WHERE r.status = %s', $active_tab )
			: 'WHERE 1=1';

		if ( $search ) {
			$like   = '%' . $wpdb->esc_like( $search ) . '%';
			$where .= $wpdb->prepare( ' AND (r.review LIKE %s OR u.display_name LIKE %s)', $like, $like );
		}

		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}` r LEFT JOIN {$wpdb->users} u ON u.ID = r.user_id {$where}" );

		$ratings = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT r.*, p.post_title AS scale_title, u.display_name, u.user_email
				 FROM `{$table}` r
				 LEFT JOIN {$wpdb->posts} p ON p.ID = r.scale_id
				 LEFT JOIN {$wpdb->users} u ON u.ID = r.user_id
				 {$where}
				 ORDER BY r.created_at DESC
				 LIMIT %d OFFSET %d",
				$per_page,
				$offset
			)
		);

		$notice = isset( $_GET['notice'] ) ? sanitize_text_field( $_GET['notice'] ) : '';

		$tab_labels = array(
			'all'      => array( 'label' => __( 'All', 'naboodatabase' ),      'icon' => '📋' ),
			'pending'  => array( 'label' => __( 'Pending', 'naboodatabase' ),   'icon' => '🕐' ),
			'approved' => array( 'label' => __( 'Approved', 'naboodatabase' ),  'icon' => '✅' ),
			'rejected' => array( 'label' => __( 'Rejected', 'naboodatabase' ),  'icon' => '🚫' ),
			'spam'     => array( 'label' => __( 'Spam', 'naboodatabase' ),       'icon' => '⚠️' ),
		);

		$badge_class = array(
			'pending'  => 'naboo-badge-amber',
			'approved' => 'naboo-badge-green',
			'rejected' => 'naboo-badge-red',
			'spam'     => 'naboo-badge-red',
		);
		?>
		<div class="wrap naboo-admin-page">

			<!-- Header -->
			<div class="naboo-admin-header" style="margin-bottom: 32px; padding: 40px; background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border-radius: 16px; color: white; box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1); position: relative; overflow: hidden;">
				<div style="position: absolute; top: -50px; right: -50px; width: 200px; height: 200px; background: rgba(99, 102, 241, 0.1); filter: blur(80px); border-radius: 50%;"></div>
				<div style="display: flex; justify-content: space-between; align-items: center; position: relative; z-index: 1;">
					<div class="naboo-admin-header-left">
						<h1 style="color: white !important; font-size: 36px !important; margin: 0 !important; font-weight: 800; letter-spacing: -0.025em; display: flex; align-items: center; gap: 20px;">
							<span style="background: rgba(255,255,255,0.1); width: 64px; height: 64px; display: flex; align-items: center; justify-content: center; border-radius: 16px; backdrop-filter: blur(4px); border: 1px solid rgba(255,255,255,0.1);">⭐</span>
							<?php esc_html_e( 'Ratings Moderation', 'naboodatabase' ); ?>
						</h1>
						<p style="margin: 16px 0 0 84px !important; color: #94a3b8; font-size: 18px; max-width: 600px; line-height: 1.6;"><?php esc_html_e( 'Monitor and manage user reviews and star ratings. Ensure community feedback is authentic and helpful.', 'naboodatabase' ); ?></p>
					</div>
					<div class="naboo-admin-header-right">
						<?php if ( $counts['pending'] > 0 ) : ?>
							<div style="background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.2); padding: 12px 20px; border-radius: 12px; backdrop-filter: blur(4px); display: flex; align-items: center; gap: 12px;">
								<span style="width: 10px; height: 10px; background: #f59e0b; border-radius: 50%; box-shadow: 0 0 10px #f59e0b;"></span>
								<span style="color: #fbd38d; font-weight: 700; font-size: 14px;"><?php echo absint( $counts['pending'] ); ?> <?php esc_html_e( 'Pending Action', 'naboodatabase' ); ?></span>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<!-- Notice banner -->
			<?php if ( $notice ) : ?>
			<div class="naboo-notice <?php echo $notice === 'approved' ? 'success' : ( $notice === 'deleted' ? 'warning' : 'info' ); ?>" style="margin-bottom:16px;">
				<span>
				<?php
				switch ( $notice ) {
					case 'approved': esc_html_e( '✅ Rating(s) approved successfully.', 'naboodatabase' ); break;
					case 'rejected': esc_html_e( '🚫 Rating(s) rejected.', 'naboodatabase' ); break;
					case 'spam':     esc_html_e( '⚠️ Rating(s) marked as spam.', 'naboodatabase' ); break;
					case 'deleted':  esc_html_e( '🗑️ Rating(s) deleted.', 'naboodatabase' ); break;
					case 'none_selected': esc_html_e( 'No ratings selected.', 'naboodatabase' ); break;
				}
				?>
				</span>
			</div>
			<?php endif; ?>

			<!-- Stat Cards -->
			<div class="naboo-stat-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; margin-bottom: 40px;">
				<div class="naboo-stat-card" style="background: white; border-radius: 20px; padding: 24px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 20px;">
					<div style="background: #fffbeb; color: #d97706; width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px;">🕐</div>
					<div>
						<div style="font-size: 24px; font-weight: 800; color: #1e293b; line-height: 1;"><?php echo absint( $counts['pending'] ); ?></div>
						<div style="font-size: 13px; font-weight: 600; color: #64748b; margin-top: 4px;"><?php esc_html_e( 'Awaiting Approval', 'naboodatabase' ); ?></div>
					</div>
				</div>
				<div class="naboo-stat-card" style="background: white; border-radius: 20px; padding: 24px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 20px;">
					<div style="background: #ecfdf5; color: #10b981; width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px;">✅</div>
					<div>
						<div style="font-size: 24px; font-weight: 800; color: #1e293b; line-height: 1;"><?php echo absint( $counts['approved'] ); ?></div>
						<div style="font-size: 13px; font-weight: 600; color: #64748b; margin-top: 4px;"><?php esc_html_e( 'Total Approved', 'naboodatabase' ); ?></div>
					</div>
				</div>
				<div class="naboo-stat-card" style="background: white; border-radius: 20px; padding: 24px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 20px;">
					<div style="background: #fef2f2; color: #ef4444; width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px;">🚫</div>
					<div>
						<div style="font-size: 24px; font-weight: 800; color: #1e293b; line-height: 1;"><?php echo absint( $counts['rejected'] ); ?></div>
						<div style="font-size: 13px; font-weight: 600; color: #64748b; margin-top: 4px;"><?php esc_html_e( 'Rejected Ratings', 'naboodatabase' ); ?></div>
					</div>
				</div>
				<div class="naboo-stat-card" style="background: white; border-radius: 20px; padding: 24px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 20px;">
					<div style="background: #f8fafc; color: #475569; width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px;">⚠️</div>
					<div>
						<div style="font-size: 24px; font-weight: 800; color: #1e293b; line-height: 1;"><?php echo absint( $counts['spam'] ); ?></div>
						<div style="font-size: 13px; font-weight: 600; color: #64748b; margin-top: 4px;"><?php esc_html_e( 'Spam/Bots', 'naboodatabase' ); ?></div>
					</div>
				</div>
			</div>

			<!-- Tab nav -->
			<!-- Tabs -->
			<div class="naboo-tabs-container" style="margin-bottom: 32px; display: flex; gap: 4px; background: #f1f5f9; padding: 6px; border-radius: 14px; width: fit-content; border: 1px solid #e2e8f0;">
				<?php foreach ( $tab_labels as $tab_key => $info ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=naboo-ratings-moderation&tab=' . $tab_key . ( $search ? '&s=' . urlencode( $search ) : '' ) ) ); ?>"
					   class="naboo-tab-btn <?php echo $active_tab === $tab_key ? 'active' : ''; ?>"
					   style="text-decoration:none; display:flex; align-items:center; gap:8px; padding: 12px 24px; border-radius: 10px; font-weight: 700; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); background: <?php echo $active_tab === $tab_key ? 'white' : 'transparent'; ?>; color: <?php echo $active_tab === $tab_key ? '#1e293b' : '#64748b'; ?>; <?php echo $active_tab === $tab_key ? 'box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1); border: 1px solid #e2e8f0;' : ''; ?>">
						<span><?php echo esc_html( $info['icon'] ); ?></span>
						<?php echo esc_html( $info['label'] ); ?>
						<?php if ( isset( $counts[ $tab_key ] ) && $counts[ $tab_key ] > 0 ) : ?>
							<span style="background: <?php echo $active_tab === $tab_key ? '#f1f5f9' : 'rgba(0,0,0,0.05)'; ?>; color: #475569; border-radius: 10px; padding: 2px 8px; font-size: 11px; font-weight: 800; border: 1px solid rgba(0,0,0,0.05);"><?php echo absint( $counts[ $tab_key ] ); ?></span>
						<?php endif; ?>
					</a>
				<?php endforeach; ?>
			</div>

			<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; background: white; padding: 20px 24px; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);">
				<form method="get" action="" style="display:flex;gap:12px;align-items:center; flex: 1;">
					<input type="hidden" name="page" value="naboo-ratings-moderation">
					<input type="hidden" name="tab" value="<?php echo esc_attr( $active_tab ); ?>">
					<div style="position: relative; flex: 1; max-width: 400px;">
						<span style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 16px;">🔍</span>
						<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>"
							   placeholder="<?php esc_attr_e( 'Search content, author ID or email…', 'naboodatabase' ); ?>"
							   style="width:100%; padding:12px 16px 12px 44px; border:1px solid #cbd5e1; border-radius:12px; font-size:14px; transition: all 0.2s; background: #fff;" />
					</div>
					<button type="submit" class="naboo-btn" style="background: #4f46e5; color: white; border: none; padding: 12px 24px; border-radius: 10px; font-weight: 700; cursor: pointer; transition: all 0.2s;"><?php esc_html_e( 'Search Filter', 'naboodatabase' ); ?></button>
					<?php if ( $search ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=naboo-ratings-moderation&tab=' . $active_tab ) ); ?>" style="color: #64748b; font-size: 14px; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 4px;"><?php esc_html_e( '✕ Clear Search', 'naboodatabase' ); ?></a>
					<?php endif; ?>
				</form>
				<div style="font-size: 14px; font-weight: 700; color: #64748b; background: #f1f5f9; padding: 8px 16px; border-radius: 999px;">
					<?php printf( _n( '%s Rating Found', '%s Ratings Found', $total, 'naboodatabase' ), number_format_i18n( $total ) ); ?>
				</div>
			</div>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'naboo_rating_action', 'naboo_rating_action_nonce' ); ?>
				<input type="hidden" name="action" value="naboo_rating_action">
				<input type="hidden" name="current_tab" value="<?php echo esc_attr( $active_tab ); ?>">

				<!-- Batch Actions -->
				<div style="display:flex;align-items:center;gap:12px;margin-bottom:20px; padding: 0 8px;">
					<select name="naboo_action" id="naboo-bulk-action" style="padding:10px 16px; border:1px solid #cbd5e1; border-radius:10px; font-size:14px; background: white; font-weight: 600; cursor: pointer; min-width: 180px;">
						<option value=""><?php esc_html_e( 'Batch Operations…', 'naboodatabase' ); ?></option>
						<option value="approve"><?php esc_html_e( 'Approve Selected', 'naboodatabase' ); ?></option>
						<option value="reject"><?php esc_html_e( 'Reject Selected', 'naboodatabase' ); ?></option>
						<option value="spam"><?php esc_html_e( 'Mark as Spam', 'naboodatabase' ); ?></option>
						<option value="delete"><?php esc_html_e( 'Permanent Delete', 'naboodatabase' ); ?></option>
					</select>
					<button type="submit" class="naboo-btn" style="background: #1e293b; color: white; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 700; cursor: pointer; transition: all 0.2s;"><?php esc_html_e( 'Execute Batch', 'naboodatabase' ); ?></button>
				</div>

				<!-- Ratings table -->
				<div class="naboo-admin-card" style="padding:0;overflow:hidden; background: white; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);">
					<?php if ( empty( $ratings ) ) : ?>
						<div style="padding:80px 48px; text-align:center; color:#94a3b8;">
							<div style="font-size:64px; margin-bottom:24px; opacity: 0.5;">⭐</div>
							<h3 style="font-size:20px; font-weight:800; color:#1e293b; margin:0;"><?php esc_html_e( 'No Ratings Found', 'naboodatabase' ); ?></h3>
							<p style="font-size:14px; margin-top:8px;"><?php esc_html_e( 'Moderation queue is empty.', 'naboodatabase' ); ?></p>
						</div>
					<?php else : ?>
					<table class="naboo-log-table" style="width: 100%; border-collapse: separate; border-spacing: 0;">
						<thead>
							<tr>
								<th style="width:48px; padding: 20px 24px; text-align: center;"><input type="checkbox" id="naboo-select-all" style="width: 18px; height: 18px; cursor: pointer; accent-color: #4f46e5;"></th>
								<th><?php esc_html_e( 'User Profile', 'naboodatabase' ); ?></th>
								<th><?php esc_html_e( 'Review Content', 'naboodatabase' ); ?></th>
								<th><?php esc_html_e( 'Scale Source', 'naboodatabase' ); ?></th>
								<th><?php esc_html_e( 'Status', 'naboodatabase' ); ?></th>
								<th><?php esc_html_e( 'Submission Date', 'naboodatabase' ); ?></th>
								<th style="text-align: right; padding-right: 32px;"><?php esc_html_e( 'Moderation Tools', 'naboodatabase' ); ?></th>
							</tr>
						</thead>
						<tbody>
						<?php foreach ( $ratings as $r ) :
							$bc = $badge_class[ $r->status ] ?? 'naboo-badge-gray';
						?>
						<tr id="rating-row-<?php echo absint( $r->id ); ?>" style="transition: all 0.2s;">
							<td style="text-align: center; vertical-align: middle;"><input type="checkbox" name="bulk_ids[]" value="<?php echo absint( $r->id ); ?>" style="width: 18px; height: 18px; cursor: pointer; accent-color: #4f46e5;"></td>
							<td style="vertical-align: middle;">
								<div style="display: flex; align-items: center; gap: 12px;">
									<div style="background: #f1f5f9; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; color: #475569; font-size: 14px; border: 1px solid #e2e8f0;">
										<?php echo strtoupper(substr($r->display_name ?: 'G', 0, 1)); ?>
									</div>
									<div>
										<strong style="display:block; font-size:15px; color: #1e293b; font-weight: 700;"><?php echo esc_html( $r->display_name ?: __( 'Guest Reviewer', 'naboodatabase' ) ); ?></strong>
										<span style="font-size:12px; color:#64748b; font-weight: 500;"><?php echo esc_html( $r->user_email ); ?></span>
									</div>
								</div>
							</td>
							<td>
								<div style="max-width:340px; padding: 12px 0;">
									<div class="naboo-stars" style="color:#f59e0b; margin-bottom:10px; font-size: 18px; letter-spacing: 2px;">
										<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
											<?php echo $i <= $r->rating ? '★' : '☆'; ?>
										<?php endfor; ?>
									</div>
									<div style="font-size:14px; line-height:1.6; color:#334155; font-weight: 500;"><?php echo esc_html( mb_substr( $r->review, 0, 180 ) . ( mb_strlen( $r->review ) > 180 ? '…' : '' ) ); ?></div>
									<?php if ( $r->helpful_count > 0 || $r->unhelpful_count > 0 ) : ?>
										<div style="margin-top: 10px; display: flex; gap: 16px;">
											<span style="font-size:11px; color:#10b981; font-weight: 800; background: #ecfdf5; padding: 2px 10px; border-radius: 999px; border: 1px solid #d1fae5;">👍 <?php echo absint( $r->helpful_count ); ?></span>
											<span style="font-size:11px; color:#ef4444; font-weight: 800; background: #fef2f2; padding: 2px 10px; border-radius: 999px; border: 1px solid #fee2e2;">👎 <?php echo absint( $r->unhelpful_count ); ?></span>
										</div>
									<?php endif; ?>
								</div>
							</td>
							<td style="vertical-align: middle;">
								<?php if ( $r->scale_title ) : ?>
									<a href="<?php echo esc_url( get_permalink( $r->scale_id ) ); ?>" target="_blank" style="font-size:14px; color:#4f46e5; font-weight: 700; text-decoration:none; display: flex; align-items: center; gap: 6px;">
										<span style="background: #eef2ff; padding: 4px; border-radius: 6px;">📑</span>
										<?php echo esc_html( mb_substr( $r->scale_title, 0, 35 ) . ( mb_strlen( $r->scale_title ) > 35 ? '…' : '' ) ); ?>
									</a>
								<?php else : ?>
									<span style="color:#94a3b8; font-size:13px; font-style: italic;"><?php esc_html_e( 'Target scale deleted', 'naboodatabase' ); ?></span>
								<?php endif; ?>
							</td>
							<td style="vertical-align: middle;">
								<span class="status-badge <?php echo esc_attr( str_replace('naboo-badge-', '', $bc) ); ?>"><?php echo esc_html( $r->status ); ?></span>
							</td>
							<td style="vertical-align: middle; white-space: nowrap;">
								<div style="font-size:13px; color:#1e293b; font-weight: 700;"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $r->created_at ) ) ); ?></div>
								<div style="font-size:11px; color:#94a3b8; font-weight: 600;"><?php echo esc_html( date_i18n( 'H:i', strtotime( $r->created_at ) ) ); ?></div>
							</td>
							<td style="vertical-align: middle; text-align: right; padding-right: 32px;">
								<div style="display:flex; gap:8px; justify-content: flex-end;">
									<?php if ( $r->status !== 'approved' ) : ?>
									<button type="submit" name="naboo_action" value="approve" onclick="document.querySelector('#rating-row-<?php echo absint( $r->id ); ?> input[type=checkbox]').checked=true;"
									        class="naboo-tool-btn" style="background:#ecfdf5; color:#059669; border:1px solid #d1fae5; width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; cursor:pointer; transition:all 0.2s;" title="Approve">
										<strong>✓</strong>
									</button>
									<?php endif; ?>
									<?php if ( $r->status !== 'rejected' ) : ?>
									<button type="submit" name="naboo_action" value="reject" onclick="document.querySelector('#rating-row-<?php echo absint( $r->id ); ?> input[type=checkbox]').checked=true;"
									        class="naboo-tool-btn" style="background:#fef2f2; color:#ef4444; border:1px solid #fee2e2; width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; cursor:pointer; transition:all 0.2s;" title="Reject">
										<strong>✕</strong>
									</button>
									<?php endif; ?>
									<button type="submit" name="naboo_action" value="delete"
									        onclick="if(!confirm('<?php esc_attr_e( 'Permanently delete this rating?', 'naboodatabase' ); ?>'))return false;document.querySelector('#rating-row-<?php echo absint( $r->id ); ?> input[type=checkbox]').checked=true;"
									        class="naboo-tool-btn" style="background:#f1f5f9; color:#64748b; border:1px solid #e2e8f0; width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; cursor:pointer; transition:all 0.2s;" title="Delete">
										<strong>🗑️</strong>
									</button>
								</div>
								<input type="hidden" name="rating_id" value="" id="single-id-<?php echo absint( $r->id ); ?>">
							</td>
						</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
					<?php endif; ?>
				</div>

				<!-- Pagination -->
				<?php if ( $total > $per_page ) :
					$total_pages = ceil( $total / $per_page );
				?>
				<div style="display:flex; align-items:center; justify-content:space-between; margin-top:32px; background: white; padding: 20px 24px; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);">
					<span style="font-size:14px; font-weight: 700; color: #64748b;">
						<?php printf( esc_html__( 'Page %1$s of %2$s', 'naboodatabase' ), '<strong>' . $paged . '</strong>', $total_pages ); ?>
					</span>
					<div style="display:flex; gap:8px;">
						<?php 
						$pag_args = array( 'page' => 'naboo-ratings-moderation', 'tab' => $active_tab, 's' => $search );
						if ( $paged > 1 ) : ?>
							<a href="<?php echo esc_url( add_query_arg( array_merge( $pag_args, array( 'paged' => $paged - 1 ) ), admin_url( 'admin.php' ) ) ); ?>" class="naboo-btn" style="background: white; border: 1px solid #cbd5e1; color: #1e293b; padding: 10px 16px; border-radius: 10px; font-weight: 700; text-decoration: none;">&larr; Previous</a>
						<?php endif; ?>
						
						<?php if ( $paged < $total_pages ) : ?>
							<a href="<?php echo esc_url( add_query_arg( array_merge( $pag_args, array( 'paged' => $paged + 1 ) ), admin_url( 'admin.php' ) ) ); ?>" class="naboo-btn" style="background: white; border: 1px solid #cbd5e1; color: #1e293b; padding: 10px 16px; border-radius: 10px; font-weight: 700; text-decoration: none;">Next &rarr;</a>
						<?php endif; ?>
					</div>
				</div>
				<?php endif; ?>

			</form>

			<style>
				.naboo-admin-page { font-family: 'Inter', sans-serif !important; }
				.naboo-stat-card { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
				.naboo-stat-card:hover { transform: translateY(-4px); box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1); }
				
				.naboo-log-table th { background: #f8fafc; padding: 16px 24px; font-weight: 800; color: #475569; text-transform: uppercase; font-size: 11px; letter-spacing: 0.05em; border-bottom: 2px solid #f1f5f9; text-align: left; }
				.naboo-log-table td { padding: 16px 24px; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #334155; }
				.naboo-log-table tr:hover td { background: #fbfcfe; }
				.naboo-log-table tr:last-child td { border-bottom: none; }

				.status-badge { font-size: 11px; font-weight: 800; padding: 4px 12px; border-radius: 999px; text-transform: uppercase; letter-spacing: 0.05em; border: 1px solid transparent; }
				.status-badge.amber { background: #fffbeb; color: #92400e; border-color: #fef3c7; }
				.status-badge.green { background: #ecfdf5; color: #065f46; border-color: #d1fae5; }
				.status-badge.red   { background: #fef2f2; color: #991b1b; border-color: #fee2e2; }
				.status-badge.gray  { background: #f8fafc; color: #64748b; border-color: #e2e8f0; }

				.naboo-tool-btn:hover { transform: translateY(-2px); filter: brightness(1.05); }
				.naboo-btn:hover { transform: translateY(-1px); filter: brightness(1.1); box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.3); }
			</style>

			</form><!-- /bulk form -->

		</div><!-- /.naboo-admin-page -->

		<script>
		(function(){
			var all = document.getElementById('naboo-select-all');
			if(!all) return;
			all.addEventListener('change', function(){
				document.querySelectorAll('input[name="bulk_ids[]"]').forEach(function(cb){ cb.checked = all.checked; });
			});
		})();
		</script>
		<?php
	}
}
