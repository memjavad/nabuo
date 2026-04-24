<?php
/**
 * Advanced Admin Dashboard
 *
 * @package ArabPsychology\NabooDatabase\Admin
 */

namespace ArabPsychology\NabooDatabase\Admin;

/**
 * Advanced_Admin_Dashboard class - Comprehensive admin insights and metrics.
 */
class Advanced_Admin_Dashboard {

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
	 * Create dashboard metrics table
	 */
	private function create_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_dashboard_metrics';

		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === null ) {
			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE $table_name (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				metric_type varchar(100) NOT NULL,
				metric_value longtext,
				date_recorded datetime DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY metric_type (metric_type),
				KEY date_recorded (date_recorded)
			) $charset_collate;";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
		}
	}

	/**
	 * Add admin menu
	 */
	public function add_admin_menu() {
		add_menu_page(
			'NABOO Insights',
			'NABOO Insights',
			'manage_options',
			'naboo-dashboard',
			array( $this, 'render_analytics_page' ),
			'dashicons-chart-bar',
			2
		);

		add_submenu_page(
			'naboo-dashboard',
			__( 'Insights & Analytics', 'naboodatabase' ),
			__( '📈 Insights & Analytics', 'naboodatabase' ),
			'manage_options',
			'naboo-dashboard',
			array( $this, 'render_analytics_page' ),
			1.1
		);
	}

	/**
	 * Explicitly reorder the submenus of the NABOO Dashboard to bypass WordPress $position sorting bugs.
	 */
	public function reorder_submenus() {
		global $submenu;

		if ( ! isset( $submenu['naboo-dashboard'] ) || empty( $submenu['naboo-dashboard'] ) ) {
			return;
		}

		$slug_order = array(
			'naboo-dashboard',
			'naboo-dashboard-settings',
			'naboodatabase-customizer',
			'naboo-search-admin',
			'naboodatabase-seo',
			'naboo-security',
			'naboo-batch-ai',
			'naboo-pending-processor',
		);

		$new_submenu = array();
		$current_submenu = $submenu['naboo-dashboard'];
		$index = 1;

		// 1. Add items matching the defined exact order
		foreach ( $slug_order as $slug ) {
			foreach ( $current_submenu as $key => $item ) {
				// Submenu slug is at index 2
				if ( $item[2] === $slug ) {
					$new_submenu[ $index ] = $item;
					unset( $current_submenu[ $key ] );
					$index++;
					break;
				}
			}
		}

		// 2. Add any leftover items (e.g. from future extensions) to the bottom
		foreach ( $current_submenu as $item ) {
			$new_submenu[ $index ] = $item;
			$index++;
		}

		// Save back to WP global
		$submenu['naboo-dashboard'] = $new_submenu;

		// --- Reorder Psych Scale CPT submenus ---
		if ( isset( $submenu['edit.php?post_type=psych_scale'] ) && ! empty( $submenu['edit.php?post_type=psych_scale'] ) ) {
			$cpt_slug_order = array(
				'naboo-submission-queue',
				'naboo-comments-moderation',
				'naboo-ratings-moderation',
				'naboo-validator',
				'naboo-bulk-ops',
				'edit.php?post_type=naboo_glossary',
				'naboo-glossary-instructions',
			);

			$cpt_submenu = $submenu['edit.php?post_type=psych_scale'];
			$wp_core_items = array();
			$naboo_items   = array();

			// Separate our custom tool items from WP core items
			foreach ( $cpt_submenu as $key => $item ) {
				$slug = $item[2];
				if ( in_array( $slug, $cpt_slug_order, true ) ) {
					$naboo_items[ $slug ] = $item;
				} else {
					$wp_core_items[] = $item;
				}
			}

			$new_cpt_submenu = array();
			$index = 1;

			// 1. WP Core items first
			foreach ( $wp_core_items as $item ) {
				$new_cpt_submenu[ $index ] = $item;
				$index++;
			}

			// 2. Our items in explicit order
			foreach ( $cpt_slug_order as $slug ) {
				if ( isset( $naboo_items[ $slug ] ) ) {
					$new_cpt_submenu[ $index ] = $naboo_items[ $slug ];
					$index++;
				}
			}

			$submenu['edit.php?post_type=psych_scale'] = $new_cpt_submenu;
		}
	}


	/**
	 * Get dashboard metrics
	 */
	private function get_dashboard_metrics() {
		global $wpdb;

		$total_scales = wp_count_posts( 'psych_scale' )->publish ?? 0;
		$published = wp_count_posts( 'psych_scale' )->publish ?? 0;
		$pending = wp_count_posts( 'psych_scale' )->pending ?? 0;
		$total_users = count_users();

		// Downloads in last 30 days
		$downloads_month = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}naboo_file_downloads WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
			)
		);

		// Average rating
		$avg_rating = $wpdb->get_var(
			"SELECT AVG(rating) FROM {$wpdb->prefix}naboo_ratings WHERE status = 'approved'"
		) ?? 0;

		return array(
			'total_scales'      => $total_scales,
			'published_scales'  => $published,
			'pending_scales'    => $pending,
			'total_users'       => $total_users['total_users'] ?? 0,
			'downloads_month'   => $downloads_month ?? 0,
			'avg_rating'        => $avg_rating,
		);
	}

	/**
	 * Render recent submissions
	 */
	private function render_recent_submissions() {
		$args = array(
			'post_type'      => 'psych_scale',
			'posts_per_page' => 5,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'no_found_rows'  => true,
		);

		$query = new \WP_Query( $args );

		if ( $query->have_posts() ) {
			echo '<ul class="naboo-submission-list">';
			while ( $query->have_posts() ) {
				$query->the_post();
				$author = get_the_author();
				$date   = get_the_date( 'M d, Y' );
				echo '<li>';
				echo '<strong>' . wp_kses_post( get_the_title() ) . '</strong><br>';
				echo '<small>' . esc_html( $author ) . ' — ' . esc_html( $date ) . '</small>';
				echo '</li>';
			}
			echo '</ul>';
			wp_reset_postdata();
		} else {
			echo '<p>' . esc_html__( 'No submissions yet', 'naboodatabase' ) . '</p>';
		}
	}

	/**
	 * Render top scales
	 */
	private function render_top_scales() {
		global $wpdb;

		$top_scales = $wpdb->get_results(
			"SELECT ID, post_title, post_name, pm.meta_value as view_count
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_naboo_view_count'
			WHERE p.post_type = 'psych_scale' AND p.post_status = 'publish'
			ORDER BY CAST(pm.meta_value AS UNSIGNED) DESC
			LIMIT 5"
		);

		if ( $top_scales ) {
			echo '<ol class="naboo-top-scales-list">';
			foreach ( $top_scales as $scale ) {
				$views = $scale->view_count ?? 0;
				echo '<li>';
				echo '<a href="' . esc_url( get_edit_post_link( $scale->ID ) ) . '">';
				echo esc_html( $scale->post_title );
				echo '</a>';
				echo '<span class="naboo-views-badge">' . absint( $views ) . ' views</span>';
				echo '</li>';
			}
			echo '</ol>';
		} else {
			echo '<p>' . esc_html__( 'No scales yet', 'naboodatabase' ) . '</p>';
		}
	}

	/**
	 * Render user activity
	 */
	private function render_user_activity() {
		global $wpdb;

		$user_activity = $wpdb->get_results(
			"SELECT DATE(created_at) as date, COUNT(*) as count 
			FROM {$wpdb->prefix}naboo_favorites 
			WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
			GROUP BY DATE(created_at)
			ORDER BY date DESC
			LIMIT 7"
		);

		if ( $user_activity ) {
			echo '<div class="naboo-activity-chart">';
			foreach ( $user_activity as $activity ) {
				$date = wp_date( 'M d', strtotime( $activity->date ) );
				echo '<div class="naboo-activity-row">';
				echo '<span class="naboo-date">' . esc_html( $date ) . '</span>';
				echo '<div class="naboo-bar" style="width: ' . esc_attr( min( 100, $activity->count * 5 ) ) . '%"></div>';
				echo '<span class="naboo-count">' . absint( $activity->count ) . '</span>';
				echo '</div>';
			}
			echo '</div>';
		} else {
			echo '<p>' . esc_html__( 'No activity in the last 7 days', 'naboodatabase' ) . '</p>';
		}
	}

	/**
	 * Render categories breakdown
	 */
	private function render_categories_breakdown() {
		$categories = get_terms(
			array(
				'taxonomy'   => 'scale_category',
				'hide_empty' => false,
			)
		);

		if ( ! is_wp_error( $categories ) && count( $categories ) > 0 ) {
			echo '<div class="naboo-categories-list">';
			foreach ( $categories as $category ) {
				echo '<div class="naboo-category-row">';
				echo '<span class="naboo-category-name">' . esc_html( $category->name ) . '</span>';
				echo '<span class="naboo-category-count">' . absint( $category->count ) . '</span>';
				echo '</div>';
			}
			echo '</div>';
		} else {
			echo '<p>' . esc_html__( 'No categories yet', 'naboodatabase' ) . '</p>';
		}
	}

	/**
	 * Render unified analytics page (Single Page View)
	 */
	public function render_analytics_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'naboodatabase' ) );
		}

		// Enqueue Inter font
		echo '<link rel="preconnect" href="https://fonts.googleapis.com">
			  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
			  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">';

		?>
		<div class="wrap naboo-admin-page naboo-analytics-wrap" style="font-family: 'Inter', sans-serif;">
			<div class="naboo-admin-header" style="margin-bottom: 32px; padding: 32px; background: #0f172a; border-radius: 16px; color: white; box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1);">
				<h1 style="color: white !important; font-size: 32px !important; margin: 0 !important; font-weight: 800; letter-spacing: -0.025em;">📈 <?php esc_html_e( 'Insights & Analytics', 'naboodatabase' ); ?></h1>
				<p style="margin: 8px 0 0 0 !important; color: #94a3b8; font-size: 16px; opacity: 0.9;"><?php esc_html_e( 'Real-time data visualization, contribution reports, and system security audit logs.', 'naboodatabase' ); ?></p>
			</div>

			<style>
				.naboo-analytics-summary-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 32px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); }
				.naboo-analytics-summary-body { display: flex; justify-content: space-between; align-items: center; padding: 24px 40px; gap: 20px; flex-wrap: wrap; }
				.naboo-summary-item { text-align: center; flex: 1; min-width: 120px; }
				.naboo-summary-label { display: block; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; }
				.naboo-summary-value { display: block; font-size: 24px; font-weight: 800; color: #1e293b; }
				.naboo-summary-divider { width: 1px; height: 40px; background: #e2e8f0; }
				@media (max-width: 768px) { .naboo-summary-divider { display: none; } .naboo-summary-item { border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; } .naboo-summary-item:last-child { border-bottom: none; } }
				
				.naboo-analytics-section { margin-bottom: 48px; }
				.section-title { font-size: 20px; font-weight: 700; color: #1e293b; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; padding-left: 4px; border-left: 4px solid #4f46e5; }
				
				.naboo-analytics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; }
				.naboo-analytics-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); overflow: hidden; transition: transform 0.2s ease, box-shadow 0.2s ease; }
				.naboo-analytics-card:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1); }
				
				.card-header { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; background: #f8fafc; font-weight: 700; font-size: 14px; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
				.card-body { padding: 24px; }
				
				.stats-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px dashed #e2e8f0; }
				.stats-row:last-child { border-bottom: none; }
				.stats-label { color: #64748b; font-weight: 500; font-size: 14px; }
				.stats-value { font-weight: 700; color: #1e293b; font-size: 15px; }

				.naboo-log-table { width: 100%; border-collapse: collapse; }
				.naboo-log-table th { text-align: left; padding: 14px 20px; background: #f8fafc; font-size: 12px; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
				.naboo-log-table td { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #334155; }
				.severity-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 8px; }
				.severity-info { background: #3b82f6; }
				.severity-warning { background: #f59e0b; }
				.severity-error { background: #ef4444; }
				
				.naboo-contributions-table { width: 100%; border-collapse: collapse; }
				.naboo-contributions-table th { text-align: left; font-size: 12px; color: #64748b; text-transform: uppercase; padding-bottom: 12px; border-bottom: 1px solid #e2e8f0; }
				.naboo-contributions-table td { padding: 12px 0; border-bottom: 1px solid #f1f5f9; font-weight: 500; }
			</style>

			<div class="naboo-analytics-summary-card">
				<?php $metrics = $this->get_dashboard_metrics(); ?>
				<div class="naboo-analytics-summary-body">
					<div class="naboo-summary-item">
						<span class="naboo-summary-label"><?php esc_html_e( 'Total Scales', 'naboodatabase' ); ?></span>
						<span class="naboo-summary-value"><?php echo absint( $metrics['total_scales'] ); ?></span>
					</div>
					<div class="naboo-summary-divider"></div>
					<div class="naboo-summary-item">
						<span class="naboo-summary-label"><?php esc_html_e( 'Pending Review', 'naboodatabase' ); ?></span>
						<span class="naboo-summary-value" style="color: #4f46e5;"><?php echo absint( $metrics['pending_scales'] ); ?></span>
					</div>
					<div class="naboo-summary-divider"></div>
					<div class="naboo-summary-item">
						<span class="naboo-summary-label"><?php esc_html_e( 'Total Users', 'naboodatabase' ); ?></span>
						<span class="naboo-summary-value"><?php echo absint( $metrics['total_users'] ); ?></span>
					</div>
					<div class="naboo-summary-divider"></div>
					<div class="naboo-summary-item">
						<span class="naboo-summary-label"><?php esc_html_e( 'Downloads (30d)', 'naboodatabase' ); ?></span>
						<span class="naboo-summary-value"><?php echo absint( $metrics['downloads_month'] ); ?></span>
					</div>
					<div class="naboo-summary-divider"></div>
					<div class="naboo-summary-item">
						<span class="naboo-summary-label"><?php esc_html_e( 'Avg Rating', 'naboodatabase' ); ?></span>
						<span class="naboo-summary-value" style="color: #f59e0b;"><?php echo esc_html( number_format( (float) $metrics['avg_rating'], 1 ) ); ?></span>
					</div>
				</div>
			</div>

			<!-- Section: Statistics -->
			<div class="naboo-analytics-section">
				<h2 class="section-title">📊 <?php esc_html_e( 'System Statistics', 'naboodatabase' ); ?></h2>
				<div class="naboo-analytics-grid">
					<div class="naboo-analytics-card">
						<div class="card-header"><?php esc_html_e( 'Scales by Status', 'naboodatabase' ); ?></div>
						<div class="card-body">
							<?php 
							$statuses = array( 'publish', 'pending', 'draft' );
							foreach ( $statuses as $status ) : 
								$count = wp_count_posts( 'psych_scale' )->{$status} ?? 0;
							?>
								<div class="stats-row">
									<span class="stats-label"><?php echo esc_html( ucfirst( $status ) ); ?></span>
									<span class="stats-value"><?php echo absint( $count ); ?></span>
								</div>
							<?php endforeach; ?>
						</div>
					</div>

					<div class="naboo-analytics-card">
						<div class="card-header"><?php esc_html_e( 'Ratings Distribution', 'naboodatabase' ); ?></div>
						<div class="card-body">
							<?php $this->render_ratings_stats(); ?>
						</div>
					</div>

					<div class="naboo-analytics-card">
						<div class="card-header"><?php esc_html_e( 'Downloads Analytics', 'naboodatabase' ); ?></div>
						<div class="card-body">
							<?php $this->render_downloads_stats(); ?>
						</div>
					</div>

					<div class="naboo-analytics-card">
						<div class="card-header"><?php esc_html_e( 'Comments Activity', 'naboodatabase' ); ?></div>
						<div class="card-body">
							<?php $this->render_comments_stats(); ?>
						</div>
					</div>
				</div>
			</div>

			<!-- Section: Reports -->
			<div class="naboo-analytics-section">
				<h2 class="section-title">📄 <?php esc_html_e( 'Performance Reports', 'naboodatabase' ); ?></h2>
				<div class="naboo-analytics-grid">
					<div class="naboo-analytics-card">
						<div class="card-header"><?php esc_html_e( 'Database Health Summary', 'naboodatabase' ); ?></div>
						<div class="card-body">
							<?php $this->render_health_report(); ?>
						</div>
					</div>

					<div class="naboo-analytics-card">
						<div class="card-header"><?php esc_html_e( 'Recent Submissions', 'naboodatabase' ); ?></div>
						<div class="card-body">
							<?php $this->render_recent_submissions(); ?>
						</div>
					</div>

					<div class="naboo-analytics-card">
						<div class="card-header"><?php esc_html_e( 'Top Scales', 'naboodatabase' ); ?></div>
						<div class="card-body">
							<?php $this->render_top_scales(); ?>
						</div>
					</div>

					<div class="naboo-analytics-card" style="grid-column: span 3;">
						<div class="card-header"><?php esc_html_e( 'Top Content Contributors', 'naboodatabase' ); ?></div>
						<div class="card-body">
							<?php $this->render_contributions_report(); ?>
						</div>
					</div>
				</div>
			</div>

			<!-- Section: Audit Logs -->
			<div class="naboo-analytics-section">
				<h2 class="section-title">📋 <?php esc_html_e( 'Security Audit Logs', 'naboodatabase' ); ?></h2>
				<div class="naboo-analytics-card">
					<div class="card-body" style="padding: 0;">
						<?php 
						$logger = new \ArabPsychology\NabooDatabase\Core\Security_Logger();
						$logs   = $logger->get_logs( 50 );
						?>
						<table class="naboo-log-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Time', 'naboodatabase' ); ?></th>
									<th><?php esc_html_e( 'Event', 'naboodatabase' ); ?></th>
									<th><?php esc_html_e( 'User', 'naboodatabase' ); ?></th>
									<th><?php esc_html_e( 'Description', 'naboodatabase' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php if ( empty( $logs ) ) : ?>
									<tr><td colspan="4" style="text-align: center; padding: 60px; color: #94a3b8; font-weight: 500;"><?php esc_html_e( 'No security logs available.', 'naboodatabase' ); ?></td></tr>
								<?php else : ?>
									<?php foreach ( $logs as $log ) : ?>
										<tr>
											<td style="color: #64748b; font-family: monospace;"><?php echo esc_html( $log->timestamp ); ?></td>
											<td>
												<span class="severity-dot severity-<?php echo esc_attr( $log->severity ); ?>"></span>
												<span style="font-weight: 600;"><?php echo esc_html( str_replace('_', ' ', $log->event_type) ); ?></span>
											</td>
											<td style="font-weight: 500; color: #1e293b;"><?php echo esc_html( $log->user_login ?: 'Guest' ); ?></td>
											<td style="color: #475569;"><?php echo esc_html( $log->description ); ?></td>
										</tr>
									<?php endforeach; ?>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render status statistics
	 */
	private function render_status_stats() {
		$statuses = array( 'publish', 'pending', 'draft' );
		echo '<table class="naboo-stats-table">';
		foreach ( $statuses as $status ) {
			$count = wp_count_posts( 'psych_scale' )->{$status} ?? 0;
			echo '<tr>';
			echo '<td>' . esc_html( ucfirst( $status ) ) . '</td>';
			echo '<td class="naboo-stat-value">' . absint( $count ) . '</td>';
			echo '</tr>';
		}
		echo '</table>';
	}

	/**
	 * Render ratings statistics
	 */
	private function render_ratings_stats() {
		global $wpdb;

		$table = $wpdb->prefix . 'naboo_ratings';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			echo '<p style="color:#64748b;font-size:13px;">' . esc_html__( 'Ratings table not yet created. Visit a scale page to initialise.', 'naboodatabase' ) . '</p>';
			return;
		}

		$ratings = $wpdb->get_results(
			"SELECT rating, COUNT(*) as count FROM {$table}
			WHERE status = 'approved'
			GROUP BY rating
			ORDER BY rating DESC"
		);

		if ( empty( $ratings ) ) {
			echo '<p style="color:#64748b;font-size:13px;">' . esc_html__( 'No ratings yet.', 'naboodatabase' ) . '</p>';
			return;
		}

		echo '<table class="naboo-stats-table">';
		foreach ( $ratings as $rating ) {
			echo '<tr>';
			echo '<td>' . esc_html( $rating->rating . ' stars' ) . '</td>';
			echo '<td class="naboo-stat-value">' . absint( $rating->count ) . '</td>';
			echo '</tr>';
		}
		echo '</table>';
	}

	/**
	 * Render comments statistics
	 */
	private function render_comments_stats() {
		global $wpdb;

		$table = $wpdb->prefix . 'naboo_comments';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			echo '<p style="color:#64748b;font-size:13px;">' . esc_html__( 'Comments table not yet created. Visit a scale page to initialise.', 'naboodatabase' ) . '</p>';
			return;
		}

		$pending  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'pending'" );
		$approved = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'approved'" );

		echo '<table class="naboo-stats-table">';
		echo '<tr><td>' . esc_html__( 'Pending', 'naboodatabase' ) . '</td><td class="naboo-stat-value">' . $pending . '</td></tr>';
		echo '<tr><td>' . esc_html__( 'Approved', 'naboodatabase' ) . '</td><td class="naboo-stat-value">' . $approved . '</td></tr>';
		echo '</table>';
		echo '<a href="' . esc_url( admin_url( 'admin.php?page=naboo-comments-moderation&tab=pending' ) ) . '" class="naboo-btn naboo-btn-primary" style="margin-top:12px;display:inline-flex;">💬 ' . esc_html__( 'Moderate Comments', 'naboodatabase' ) . '</a>';
	}

	/**
	 * Render downloads statistics
	 */
	private function render_downloads_stats() {
		global $wpdb;

		$table = $wpdb->prefix . 'naboo_file_downloads';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			echo '<p style="color:#64748b;font-size:13px;">' . esc_html__( 'Downloads table not yet created. Files must be downloaded first.', 'naboodatabase' ) . '</p>';
			return;
		}

		$total_downloads = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		$month_downloads = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)" );

		echo '<table class="naboo-stats-table">';
		echo '<tr><td>' . esc_html__( 'Total Downloads', 'naboodatabase' ) . '</td><td class="naboo-stat-value">' . $total_downloads . '</td></tr>';
		echo '<tr><td>' . esc_html__( 'Last 30 Days', 'naboodatabase' ) . '</td><td class="naboo-stat-value">' . $month_downloads . '</td></tr>';
		echo '</table>';
	}

	/**
	 * Render health report
	 */
	private function render_health_report() {
		global $wpdb;

		$scales = wp_count_posts( 'psych_scale' )->publish ?? 0;
		$users = count_users();
		$total_users = $users['total_users'] ?? 0;

		echo '<div class="naboo-health-report">';
		echo '<p><strong>' . esc_html__( 'Database Status:', 'naboodatabase' ) . '</strong> ' . esc_html__( 'Healthy', 'naboodatabase' ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Total Scales:', 'naboodatabase' ) . '</strong> ' . absint( $scales ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Active Users:', 'naboodatabase' ) . '</strong> ' . absint( $total_users ) . '</p>';
		echo '</div>';
	}

	/**
	 * Render contributions report
	 */
	private function render_contributions_report() {
		global $wpdb;

		$contributors = $wpdb->get_results(
			"SELECT post_author, COUNT(*) as count FROM {$wpdb->posts} 
			WHERE post_type = 'psych_scale' AND post_status = 'publish'
			GROUP BY post_author 
			ORDER BY count DESC 
			LIMIT 10"
		);

		echo '<table class="naboo-contributions-table">';
		echo '<thead><tr><th>' . esc_html__( 'Author', 'naboodatabase' ) . '</th><th>' . esc_html__( 'Scales', 'naboodatabase' ) . '</th></tr></thead>';
		echo '<tbody>';

		foreach ( $contributors as $contributor ) {
			$author = get_user_by( 'id', $contributor->post_author );
			echo '<tr>';
			echo '<td>' . esc_html( $author->display_name ?? 'Unknown' ) . '</td>';
			echo '<td>' . absint( $contributor->count ) . '</td>';
			echo '</tr>';
		}

		echo '</tbody></table>';
	}

	/**
	 * Enqueue admin styles
	 */
	public function enqueue_styles() {
		// Global admin design system (shared across all NABOO pages)
		wp_enqueue_style(
			'naboo-admin-global',
			plugin_dir_url( __FILE__ ) . 'css/naboo-admin-global.css',
			array(),
			$this->version
		);

		// Dashboard-specific overrides
		wp_enqueue_style(
			$this->plugin_name . '-admin-dashboard',
			plugin_dir_url( __FILE__ ) . 'css/admin-dashboard.css',
			array( 'naboo-admin-global' ),
			$this->version
		);
	}
}
