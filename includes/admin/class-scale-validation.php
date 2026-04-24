<?php
/**
 * Scale Validation
 *
 * @package ArabPsychology\NabooDatabase\Admin
 */

namespace ArabPsychology\NabooDatabase\Admin;

/**
 * Scale_Validation class - Scale data quality and compliance checking.
 */
class Scale_Validation {

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
			'/validation/validate/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'validate_scale' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/validation/validate-all',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'validate_all_scales' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/validation/report',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_validation_report' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}

	/**
	 * Validate single scale
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function validate_scale( $request ) {
		$scale_id = $request->get_param( 'id' );
		$scale    = get_post( $scale_id );

		if ( ! $scale || $scale->post_type !== 'psych_scale' ) {
			return new \WP_REST_Response(
				array( 'error' => 'Scale not found' ),
				404
			);
		}

		$validation_result = $this->perform_validation( $scale );

		return new \WP_REST_Response( $validation_result, 200 );
	}

	/**
	 * Validate all scales
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function validate_all_scales( $request ) {
		$status = $request->get_param( 'status' ) ?? 'publish';

		$args = array(
			'post_type'      => 'psych_scale',
			'post_status'    => $status,
			'posts_per_page' => -1,
		);

		$query = new \WP_Query( $args );

		$results = array(
			'total_scales' => $query->found_posts,
			'valid_scales' => 0,
			'invalid_scales' => 0,
			'issues' => array(),
		);

		foreach ( $query->posts as $scale ) {
			$validation = $this->perform_validation( $scale );

			if ( $validation['is_valid'] ) {
				$results['valid_scales']++;
			} else {
				$results['invalid_scales']++;
				$results['issues'][ $scale->ID ] = $validation['issues'];
			}
		}

		wp_reset_postdata();

		return new \WP_REST_Response( $results, 200 );
	}

	/**
	 * Perform validation on scale
	 *
	 * @param \WP_Post $scale The scale post.
	 * @return array
	 */
	private function perform_validation( $scale ) {
		$issues = array();
		$is_valid = true;

		try {
			// Check title
			if ( empty( $scale->post_title ) || strlen( $scale->post_title ) < 3 ) {
				$issues[] = 'Title is too short (minimum 3 characters)';
			}

			// Check content
			if ( empty( $scale->post_content ) || strlen( $scale->post_content ) < 50 ) {
				$issues[] = 'Description is too short (minimum 50 characters)';
			}

			// Check metadata
			$items = get_post_meta( $scale->ID, '_naboo_scale_items', true );
			if ( empty( $items ) || ! is_numeric( $items ) || $items < 1 ) {
				$issues[] = 'Missing or invalid item count';
			}

			$year = get_post_meta( $scale->ID, '_naboo_scale_year', true );
			if ( empty( $year ) || ! is_numeric( $year ) || $year < 1900 || $year > gmdate( 'Y' ) ) {
				$issues[] = 'Missing or invalid year';
			}

			$language = get_post_meta( $scale->ID, '_naboo_scale_language', true );
			if ( empty( $language ) ) {
				$issues[] = 'Missing language information';
			}

			$population = get_post_meta( $scale->ID, '_naboo_scale_population', true );
			if ( empty( $population ) ) {
				$issues[] = 'Missing population information';
			}

			// Check taxonomy
			$categories = wp_get_post_terms( $scale->ID, 'scale_category' );
			if ( is_wp_error( $categories ) || count( $categories ) === 0 ) {
				$issues[] = 'Scale must be assigned to at least one category';
			}

			$is_valid = count( $issues ) === 0;

		} catch ( \Exception $e ) {
			$is_valid = false;
			$issues[] = 'Validation exception: ' . $e->getMessage();
			error_log( 'Validation failed: ' . $e->getMessage() );
		}

		return array(
			'scale_id'  => $scale->ID,
			'is_valid'  => $is_valid,
			'issues'    => $issues,
			'issue_count' => count( $issues ),
		);
	}

	/**
	 * Get validation report
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_validation_report( $request ) {
		$args = array(
			'post_type'      => 'psych_scale',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		);

		$query = new \WP_Query( $args );

		$report = array(
			'total_scales'     => $query->found_posts,
			'valid_scales'     => 0,
			'invalid_scales'   => 0,
			'common_issues'    => array(),
			'issue_breakdown'  => array(),
		);

		$issue_counts = array();

		foreach ( $query->posts as $scale ) {
			$validation = $this->perform_validation( $scale );

			if ( $validation['is_valid'] ) {
				$report['valid_scales']++;
			} else {
				$report['invalid_scales']++;

				foreach ( $validation['issues'] as $issue ) {
					$issue_counts[ $issue ] = isset( $issue_counts[ $issue ] ) ? $issue_counts[ $issue ] + 1 : 1;
				}
			}
		}

		arsort( $issue_counts );
		$report['common_issues'] = array_slice( $issue_counts, 0, 5, true );
		$report['compliance_percentage'] = $query->found_posts > 0 ? round( ( $report['valid_scales'] / $query->found_posts ) * 100, 2 ) : 0;

		wp_reset_postdata();

		return new \WP_REST_Response( $report, 200 );
	}

	/**
	 * Register Admin Menu
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'edit.php?post_type=psych_scale',
			__( 'Scale Validator', 'naboodatabase' ),
			__( 'Scale Validator', 'naboodatabase' ),
			'manage_options',
			'naboo-validator',
			array( $this, 'render_admin_page' ),
			14
		);
	}

	/**
	 * Enqueue Admin Scripts
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( 'psych_scale_page_naboo-validator' !== $hook ) {
			return;
		}

		wp_enqueue_script(
			$this->plugin_name . '-validator',
			plugin_dir_url( __FILE__ ) . 'js/validator-admin.js',
			array( 'jquery', 'wp-api-fetch' ),
			$this->version,
			true
		);

		wp_localize_script(
			$this->plugin_name . '-validator',
			'apaValidator',
			array(
				'apiUrl' => rest_url( 'apa/v1/validation' ),
				'nonce'  => wp_create_nonce( 'wp_rest' ),
			)
		);

		wp_enqueue_style(
			$this->plugin_name . '-validator',
			plugin_dir_url( __FILE__ ) . 'css/validator-admin.css',
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
			<h1><?php esc_html_e( 'Scale Quality Validator', 'naboodatabase' ); ?></h1>
			<p><?php esc_html_e( 'Run automated checks to ensure scale data quality and compliance.', 'naboodatabase' ); ?></p>

			<div class="naboo-validator-controls">
				<button type="button" id="naboo-run-validation" class="button button-primary"><?php esc_html_e( 'Run Full Validation Scan', 'naboodatabase' ); ?></button>
				<span id="naboo-validator-status" class="naboo-status-text"></span>
			</div>

			<div id="naboo-validation-results" style="display:none;">
				<div class="naboo-validator-stats">
					<div class="naboo-vstat-card">
						<span class="naboo-vstat-label"><?php esc_html_e( 'Compliance', 'naboodatabase' ); ?></span>
						<span class="naboo-vstat-value" id="naboo-vstat-percent">0%</span>
					</div>
					<div class="naboo-vstat-card">
						<span class="naboo-vstat-label"><?php esc_html_e( 'Total Issues', 'naboodatabase' ); ?></span>
						<span class="naboo-vstat-value" id="naboo-vstat-issues">0</span>
					</div>
				</div>

				<div class="naboo-validator-grid">
					<div class="naboo-validator-main">
						<h2><?php esc_html_e( 'Detailed Issues by Scale', 'naboodatabase' ); ?></h2>
						<div id="naboo-v-issues-list" class="naboo-v-list">
							<!-- Populated via AJAX -->
						</div>
					</div>
					<div class="naboo-validator-side">
						<h2><?php esc_html_e( 'Common Quality Issues', 'naboodatabase' ); ?></h2>
						<div id="naboo-v-summary" class="naboo-v-summary-box">
							<!-- Populated via AJAX -->
						</div>
					</div>
				</div>
			</div>

			<div id="naboo-v-progress" class="naboo-v-progress-overlay" style="display:none;">
				<div class="naboo-v-progress-box">
					<h3><?php esc_html_e( 'Scanning Database...', 'naboodatabase' ); ?></h3>
					<div class="naboo-v-progress-bar-bg">
						<div class="naboo-v-progress-bar-fill"></div>
					</div>
					<p id="naboo-v-progress-text"><?php esc_html_e( 'Analyzing scales...', 'naboodatabase' ); ?></p>
				</div>
			</div>

		</div>
		<?php
	}
}
