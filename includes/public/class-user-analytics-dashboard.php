<?php
/**
 * User Analytics Dashboard
 *
 * @package ArabPsychology\NabooDatabase\Public
 */

namespace ArabPsychology\NabooDatabase\Public;

/**
 * User_Analytics_Dashboard class - Track and display user behavior analytics.
 */
class User_Analytics_Dashboard {

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
	 * Create user analytics table
	 */
	private function create_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_user_analytics';

		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === null ) {
			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE $table_name (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				user_id bigint(20) NOT NULL,
				total_searches bigint(20) DEFAULT 0,
				total_views bigint(20) DEFAULT 0,
				total_downloads bigint(20) DEFAULT 0,
				total_favorites bigint(20) DEFAULT 0,
				total_ratings bigint(20) DEFAULT 0,
				total_comments bigint(20) DEFAULT 0,
				total_submissions bigint(20) DEFAULT 0,
				approved_submissions bigint(20) DEFAULT 0,
				avg_session_duration int DEFAULT 0,
				last_activity datetime,
				created_at datetime DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY user_id (user_id),
				KEY last_activity (last_activity)
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
			'/analytics/user-stats',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_user_stats' ),
				'permission_callback' => function() {
					return is_user_logged_in();
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/analytics/user-dashboard',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_dashboard_data' ),
				'permission_callback' => function() {
					return is_user_logged_in();
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/analytics/user-activity',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_user_activity' ),
				'permission_callback' => function() {
					return is_user_logged_in();
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/analytics/user-preferences',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_user_preferences' ),
				'permission_callback' => function() {
					return is_user_logged_in();
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/analytics/all-users-stats',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_all_users_stats' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}

	/**
	 * Get user statistics
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_user_stats( $request ) {
		$user_id = get_current_user_id();

		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_user_analytics';

		$stats = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM $table_name WHERE user_id = %d", $user_id )
		);

		if ( ! $stats ) {
			$stats = $this->calculate_user_stats( $user_id );
		}

		return new \WP_REST_Response( $stats, 200 );
	}

	/**
	 * Get dashboard data
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_dashboard_data( $request ) {
		$user_id = get_current_user_id();
		$stats = $this->calculate_user_stats( $user_id );

		global $wpdb;

		// Get recent activity
		$recent_searches = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT search_query, COUNT(*) as count FROM {$wpdb->prefix}naboo_search_analytics WHERE user_id = %d GROUP BY search_query ORDER BY count DESC LIMIT 5",
				$user_id
			)
		);

		// Get favorite categories
		$favorite_categories = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT tc.name, COUNT(*) as count 
				FROM {$wpdb->prefix}naboo_favorites f
				JOIN {$wpdb->posts} p ON f.scale_id = p.ID
				JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
				JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
				JOIN {$wpdb->terms} tc ON tt.term_id = tc.term_id
				WHERE f.user_id = %d AND tt.taxonomy = 'scale_category'
				GROUP BY tc.name ORDER BY count DESC LIMIT 5",
				$user_id
			)
		);

		return new \WP_REST_Response(
			array(
				'stats'                => $stats,
				'recent_searches'      => $recent_searches,
				'favorite_categories'  => $favorite_categories,
			),
			200
		);
	}

	/**
	 * Get user activity
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_user_activity( $request ) {
		$user_id = get_current_user_id();

		global $wpdb;

		// Get last 30 days of activity
		$activities = array(
			'favorites'    => $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}naboo_favorites WHERE user_id = %d AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
					$user_id
				)
			),
			'ratings'      => $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}naboo_ratings WHERE user_id = %d AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
					$user_id
				)
			),
			'comments'     => $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}naboo_comments WHERE user_id = %d AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
					$user_id
				)
			),
			'submissions'  => $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_author = %d AND post_type = 'psych_scale' AND post_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
					$user_id
				)
			),
		);

		return new \WP_REST_Response( array( 'activities' => $activities ), 200 );
	}

	/**
	 * Get user preferences
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_user_preferences( $request ) {
		$user_id = get_current_user_id();

		$preferences = array(
			'email_notifications'  => get_user_meta( $user_id, 'naboo_email_notifications', true ) ?? true,
			'digest_frequency'     => get_user_meta( $user_id, 'naboo_digest_frequency', true ) ?? 'weekly',
			'favorite_language'    => get_user_meta( $user_id, 'naboo_favorite_language', true ) ?? 'en',
		);

		return new \WP_REST_Response( $preferences, 200 );
	}

	/**
	 * Get all users statistics (admin only)
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_all_users_stats( $request ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_user_analytics';

		$stats = $wpdb->get_results(
			"SELECT u.ID, u.user_login, u.display_name, ua.total_searches, ua.total_views, ua.total_downloads, ua.total_ratings, ua.last_activity
			FROM {$wpdb->users} u
			LEFT JOIN $table_name ua ON u.ID = ua.user_id
			ORDER BY ua.total_searches DESC
			LIMIT 50"
		);

		return new \WP_REST_Response( array( 'users' => $stats ), 200 );
	}

	/**
	 * Calculate user statistics
	 *
	 * @param int $user_id The user ID.
	 * @return object
	 */
	private function calculate_user_stats( $user_id ) {
		global $wpdb;

		$total_searches = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}naboo_search_analytics WHERE user_id = %d",
				$user_id
			)
		);

		$total_views = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'psych_scale' AND post_author = %d",
				$user_id
			)
		);

		$total_downloads = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}naboo_file_downloads WHERE user_id = %d",
				$user_id
			)
		);

		$total_favorites = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}naboo_favorites WHERE user_id = %d",
				$user_id
			)
		);

		$total_ratings = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}naboo_ratings WHERE user_id = %d",
				$user_id
			)
		);

		$total_comments = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}naboo_comments WHERE user_id = %d",
				$user_id
			)
		);

		$total_submissions = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_author = %d AND post_type = 'psych_scale'",
				$user_id
			)
		);

		$approved_submissions = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_author = %d AND post_type = 'psych_scale' AND post_status = 'publish'",
				$user_id
			)
		);

		return (object) array(
			'user_id'              => $user_id,
			'total_searches'       => $total_searches,
			'total_views'          => $total_views,
			'total_downloads'      => $total_downloads,
			'total_favorites'      => $total_favorites,
			'total_ratings'        => $total_ratings,
			'total_comments'       => $total_comments,
			'total_submissions'    => $total_submissions,
			'approved_submissions' => $approved_submissions,
			'last_activity'        => current_time( 'mysql' ),
		);
	}

	/**
	 * Enqueue frontend scripts and styles
	 */
	public function enqueue_scripts() {
		// Only load inside the dashboard where the shortcode was executed
		global $post;
		if ( ! is_a( $post, 'WP_Post' ) || ! has_shortcode( $post->post_content, 'naboo_dashboard' ) ) {
			return;
		}

		// Chart.js for data visualization
		wp_enqueue_script(
			'chartjs',
			'https://cdn.jsdelivr.net/npm/chart.js',
			array(),
			'4.4.0',
			true
		);

		wp_enqueue_script(
			$this->plugin_name . '-user-analytics',
			plugin_dir_url( __FILE__ ) . 'js/user-analytics.js',
			array( 'jquery', 'chartjs' ),
			$this->version,
			true
		);

		wp_localize_script(
			$this->plugin_name . '-user-analytics',
			'apaAnalytics',
			array(
				'ajaxUrl' => rest_url( 'apa/v1/' ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
			)
		);

		wp_enqueue_style(
			$this->plugin_name . '-user-analytics',
			plugin_dir_url( __FILE__ ) . 'css/user-analytics.css',
			array(),
			$this->version
		);
	}

	/**
	 * Inject Analytics tab into the User Dashboard
	 */
	public function inject_dashboard_tab( $content ) {
		// Just output our container div inside the dashboard if hooked properly
		ob_start();
		?>
		<div id="naboo-dashboard-analytics" class="naboo-dashboard-section" style="display: none;">
			<div class="naboo-dashboard-header">
				<h3><?php esc_html_e( 'My Analytics', 'naboodatabase' ); ?></h3>
				<p><?php esc_html_e( 'Track your engagement and scale performance history.', 'naboodatabase' ); ?></p>
			</div>
			
			<div class="naboo-analytics-spinner" style="display: none;">
				<svg class="naboo-spinner" viewBox="0 0 50 50" width="24" height="24"><circle class="path" cx="25" cy="25" r="20" fill="none" stroke-width="5"></circle></svg> 
				<?php esc_html_e( 'Loading your data...', 'naboodatabase' ); ?>
			</div>

			<div class="naboo-analytics-data-wrapper" style="display: none;">
				<!-- Top KPI Cards -->
				<div class="naboo-analytics-kpi-grid">
					<div class="naboo-kpi-card">
						<div class="naboo-kpi-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg></div>
						<div class="naboo-kpi-info">
							<span class="naboo-kpi-label"><?php esc_html_e( 'Total Downloads', 'naboodatabase' ); ?></span>
							<span class="naboo-kpi-value" id="kpi-downloads">0</span>
						</div>
					</div>
					<div class="naboo-kpi-card">
						<div class="naboo-kpi-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></div>
						<div class="naboo-kpi-info">
							<span class="naboo-kpi-label"><?php esc_html_e( 'Scales Viewed', 'naboodatabase' ); ?></span>
							<span class="naboo-kpi-value" id="kpi-views">0</span>
						</div>
					</div>
					<div class="naboo-kpi-card">
						<div class="naboo-kpi-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg></div>
						<div class="naboo-kpi-info">
							<span class="naboo-kpi-label"><?php esc_html_e( 'My Submitted Scales', 'naboodatabase' ); ?></span>
							<span class="naboo-kpi-value" id="kpi-submissions">0</span>
						</div>
					</div>
					<div class="naboo-kpi-card">
						<div class="naboo-kpi-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg></div>
						<div class="naboo-kpi-info">
							<span class="naboo-kpi-label"><?php esc_html_e( 'Favorites Saved', 'naboodatabase' ); ?></span>
							<span class="naboo-kpi-value" id="kpi-favorites">0</span>
						</div>
					</div>
				</div>

				<div class="naboo-analytics-charts">
					<div class="naboo-chart-container">
						<h4><?php esc_html_e( '30-Day Activity Trend', 'naboodatabase' ); ?></h4>
						<canvas id="naboo-activity-chart"></canvas>
					</div>

					<div class="naboo-chart-container">
						<h4><?php esc_html_e( 'Favorite Subject Areas', 'naboodatabase' ); ?></h4>
						<div class="naboo-analytics-cats-list">
							<!-- Populated via JS -->
						</div>
					</div>
				</div>

				<div class="naboo-analytics-recent-searches">
					<h4><?php esc_html_e( 'Recent Search History', 'naboodatabase' ); ?></h4>
					<div class="naboo-analytics-search-list">
						<!-- Populated via JS -->
					</div>
				</div>

			</div>
		</div>
		<?php
		return $content . ob_get_clean();
	}
}
