<?php
/**
 * Scale Comparison Tool
 *
 * @package ArabPsychology\NabooDatabase\Public
 */

namespace ArabPsychology\NabooDatabase\Public;

/**
 * Scale_Comparison class - Side-by-side scale comparison with metrics.
 */
class Scale_Comparison {

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
	 * Create comparison history table
	 */
	private function create_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_comparisons';

		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === null ) {
			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE $table_name (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				user_id bigint(20) NOT NULL,
				scale_ids text NOT NULL,
				comparison_data longtext,
				view_count bigint(20) DEFAULT 0,
				created_at datetime DEFAULT CURRENT_TIMESTAMP,
				updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY user_id (user_id),
				KEY created_at (created_at)
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
			'/comparison/create',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'create_comparison' ),
				'permission_callback' => function() {
					return is_user_logged_in();
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/comparison/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_comparison' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'apa/v1',
			'/comparison/my-comparisons',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_my_comparisons' ),
				'permission_callback' => function() {
					return is_user_logged_in();
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/comparison/delete/(?P<id>\d+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'delete_comparison' ),
				'permission_callback' => function() {
					return is_user_logged_in();
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/comparison/save-shared',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'save_shared_comparison' ),
				'permission_callback' => function() {
					return is_user_logged_in();
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/comparison/scales-data',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'get_scales_data' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Create a new comparison
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function create_comparison( $request ) {
		$scale_ids = $request->get_param( 'scale_ids' );

		if ( ! is_array( $scale_ids ) || count( $scale_ids ) < 2 ) {
			return new \WP_REST_Response(
				array( 'error' => 'At least 2 scales are required for comparison' ),
				400
			);
		}

		// Verify scales exist
		foreach ( $scale_ids as $scale_id ) {
			if ( get_post_type( $scale_id ) !== 'psych_scale' ) {
				return new \WP_REST_Response(
					array( 'error' => 'Invalid scale ID: ' . $scale_id ),
					404
				);
			}
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_comparisons';
		$user_id    = get_current_user_id();

		// Build comparison data
		$comparison_data = $this->build_comparison_data( $scale_ids );

		$result = $wpdb->insert(
			$table_name,
			array(
				'user_id'           => $user_id,
				'scale_ids'         => implode( ',', $scale_ids ),
				'comparison_data'   => wp_json_encode( $comparison_data ),
				'view_count'        => 0,
			),
			array( '%d', '%s', '%s', '%d' )
		);

		if ( ! $result ) {
			return new \WP_REST_Response(
				array( 'error' => 'Failed to create comparison' ),
				500
			);
		}

		$comparison_id = $wpdb->insert_id;

		return new \WP_REST_Response(
			array(
				'id'               => $comparison_id,
				'scale_ids'        => $scale_ids,
				'comparison_data'  => $comparison_data,
				'message'          => 'Comparison created successfully',
			),
			201
		);
	}

	/**
	 * Build comparison data for scales
	 *
	 * @param array $scale_ids Array of scale IDs.
	 * @return array
	 */
	private function build_comparison_data( $scale_ids ) {
		$data = array();

		foreach ( $scale_ids as $scale_id ) {
			$post = get_post( $scale_id );

			if ( ! $post ) {
				continue;
			}

			$data[] = array(
				'id'            => $scale_id,
				'title'         => $post->post_title,
				'excerpt'       => $post->post_excerpt,
				'content'       => wp_strip_all_tags( $post->post_content ),
				'items'         => (int) get_post_meta( $scale_id, '_naboo_scale_items', true ),
				'reliability'   => get_post_meta( $scale_id, '_naboo_scale_reliability', true ),
				'validity'      => get_post_meta( $scale_id, '_naboo_scale_validity', true ),
				'year'          => (int) get_post_meta( $scale_id, '_naboo_scale_year', true ),
				'language'      => get_post_meta( $scale_id, '_naboo_scale_language', true ),
				'population'    => get_post_meta( $scale_id, '_naboo_scale_population', true ),
				'categories'    => wp_get_post_terms( $scale_id, 'scale_category', array( 'fields' => 'names' ) ),
				'authors'       => wp_get_post_terms( $scale_id, 'scale_author', array( 'fields' => 'names' ) ),
				'permalink'     => get_permalink( $scale_id ),
				'thumbnail'     => get_the_post_thumbnail_url( $scale_id ),
			);
		}

		return $data;
	}

	/**
	 * Get a specific comparison
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_comparison( $request ) {
		global $wpdb;
		$table_name   = $wpdb->prefix . 'naboo_comparisons';
		$comparison_id = $request->get_param( 'id' );

		$comparison = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $comparison_id )
		);

		if ( ! $comparison ) {
			return new \WP_REST_Response(
				array( 'error' => 'Comparison not found' ),
				404
			);
		}

		// Increment view count
		$wpdb->update(
			$table_name,
			array( 'view_count' => $comparison->view_count + 1 ),
			array( 'id' => $comparison_id ),
			array( '%d' ),
			array( '%d' )
		);

		$comparison->comparison_data = json_decode( $comparison->comparison_data, true );
		$comparison->view_count++;

		return new \WP_REST_Response( $comparison, 200 );
	}

	/**
	 * Get user's comparisons
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_my_comparisons( $request ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_comparisons';
		$user_id    = get_current_user_id();

		$comparisons = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, scale_ids, view_count, created_at FROM $table_name WHERE user_id = %d ORDER BY created_at DESC LIMIT 50",
				$user_id
			)
		);

		return new \WP_REST_Response( $comparisons, 200 );
	}

	/**
	 * Delete a comparison
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function delete_comparison( $request ) {
		global $wpdb;
		$table_name    = $wpdb->prefix . 'naboo_comparisons';
		$comparison_id = $request->get_param( 'id' );
		$user_id       = get_current_user_id();

		$comparison = $wpdb->get_row(
			$wpdb->prepare( "SELECT user_id FROM $table_name WHERE id = %d", $comparison_id )
		);

		if ( ! $comparison ) {
			return new \WP_REST_Response(
				array( 'error' => 'Comparison not found' ),
				404
			);
		}

		if ( $comparison->user_id !== $user_id ) {
			return new \WP_REST_Response(
				array( 'error' => 'Unauthorized' ),
				403
			);
		}

		$result = $wpdb->delete(
			$table_name,
			array( 'id' => $comparison_id ),
			array( '%d' )
		);

		if ( ! $result ) {
			return new \WP_REST_Response(
				array( 'error' => 'Failed to delete comparison' ),
				500
			);
		}

		return new \WP_REST_Response(
			array( 'message' => 'Comparison deleted successfully' ),
			200
		);
	}

	/**
	 * Save shared comparison
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function save_shared_comparison( $request ) {
		$scale_ids = $request->get_param( 'scale_ids' );

		if ( ! is_array( $scale_ids ) || count( $scale_ids ) < 2 ) {
			return new \WP_REST_Response(
				array( 'error' => 'At least 2 scales are required' ),
				400
			);
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_comparisons';
		$user_id    = get_current_user_id();

		$comparison_data = $this->build_comparison_data( $scale_ids );

		$result = $wpdb->insert(
			$table_name,
			array(
				'user_id'         => $user_id,
				'scale_ids'       => implode( ',', $scale_ids ),
				'comparison_data' => wp_json_encode( $comparison_data ),
			),
			array( '%d', '%s', '%s' )
		);

		if ( ! $result ) {
			return new \WP_REST_Response(
				array( 'error' => 'Failed to save comparison' ),
				500
			);
		}

		$comparison_id = $wpdb->insert_id;
		$share_url     = add_query_arg( 'comparison', $comparison_id, get_home_url() );

		return new \WP_REST_Response(
			array(
				'id'        => $comparison_id,
				'share_url' => $share_url,
				'message'   => 'Comparison saved and shared',
			),
			201
		);
	}

	/**
	 * Get scales data for comparison
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_scales_data( $request ) {
		$scale_ids = $request->get_param( 'scale_ids' );

		if ( ! is_array( $scale_ids ) ) {
			return new \WP_REST_Response(
				array( 'error' => 'Invalid scale IDs' ),
				400
			);
		}

		$comparison_data = $this->build_comparison_data( $scale_ids );

		return new \WP_REST_Response( $comparison_data, 200 );
	}

	/**
	 * Enqueue scripts and styles
	 */
	public function enqueue_scripts() {
		wp_enqueue_script(
			$this->plugin_name . '-comparison',
			plugin_dir_url( __FILE__ ) . 'js/scale-comparison.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		wp_localize_script(
			$this->plugin_name . '-comparison',
			'apaComparison',
			array(
				'ajaxUrl' => rest_url( 'apa/v1/' ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
			)
		);

		wp_enqueue_style(
			$this->plugin_name . '-comparison',
			plugin_dir_url( __FILE__ ) . 'css/scale-comparison.css',
			array(),
			$this->version
		);
	}

	/**
	 * Inject Add to Compare button on single scale pages
	 */
	public function inject_add_to_compare_button( $content ) {
		if ( ! is_singular( 'psych_scale' ) ) {
			return $content;
		}

		$scale_id = get_the_ID();
		
		ob_start();
		?>
		<div class="naboo-comparison-action">
			<button type="button" class="naboo-btn naboo-btn-outline naboo-add-compare-btn" data-scale-id="<?php echo esc_attr( $scale_id ); ?>" data-scale-title="<?php echo esc_attr( get_the_title() ); ?>">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 3v4a2 2 0 002 2h4"/><path d="M8 21v-4a2 2 0 00-2-2H2"/><path d="M21 8l-5-5-5 5"/><path d="M3 16l5 5 5-5"/></svg>
				<?php esc_html_e( 'Compare Scale', 'naboodatabase' ); ?>
			</button>
		</div>
		<?php
		$button_html = ob_get_clean();

		return $content . $button_html;
	}

	/**
	 * Render the fixed comparison bar and modal
	 */
	public function render_comparison_bar() {
		// Render on all pages so user can browse and keep scales in their "compare cart"
		?>
		<!-- Floating Comparison Bar -->
		<div id="naboo-compare-bar" class="naboo-compare-bar" style="display: none;">
			<div class="naboo-compare-bar-inner">
				<div class="naboo-compare-bar-header">
					<h4><?php esc_html_e( 'Compare Scales', 'naboodatabase' ); ?> <span class="naboo-compare-count">(0)</span></h4>
					<button class="naboo-compare-bar-toggle" aria-label="<?php esc_attr_e( 'Toggle comparison bar', 'naboodatabase' ); ?>">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 15l-6-6-6 6"/></svg>
					</button>
				</div>
				<div class="naboo-compare-items-container">
					<div class="naboo-compare-items">
						<!-- Populated via JS -->
					</div>
					<div class="naboo-compare-bar-actions">
						<button class="naboo-btn naboo-btn-primary naboo-run-compare-btn" disabled><?php esc_html_e( 'Compare Now', 'naboodatabase' ); ?></button>
						<button class="naboo-btn naboo-btn-outline naboo-clear-compare-btn"><?php esc_html_e( 'Clear All', 'naboodatabase' ); ?></button>
					</div>
				</div>
			</div>
		</div>

		<!-- Comparison Results Modal -->
		<div id="naboo-compare-modal" class="naboo-compare-modal-overlay" style="display: none;">
			<div class="naboo-compare-modal-content">
				<div class="naboo-compare-modal-header">
					<h2><?php esc_html_e( 'Scale Comparison', 'naboodatabase' ); ?></h2>
					<div class="naboo-compare-modal-actions">
						<button class="naboo-btn naboo-btn-primary naboo-save-compare-btn" style="display: none;">
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
							<?php esc_html_e( 'Save & Share', 'naboodatabase' ); ?>
						</button>
						<button class="naboo-compare-modal-close" title="<?php esc_attr_e( 'Close', 'naboodatabase' ); ?>" aria-label="<?php esc_attr_e( 'Close comparison modal', 'naboodatabase' ); ?>">&times;</button>
					</div>
				</div>
				<div class="naboo-compare-modal-body">
					<!-- Table generated via JS -->
					<div class="naboo-compare-spinner" style="display: none;"></div>
					<div class="naboo-compare-table-container"></div>
				</div>
			</div>
		</div>
		<?php
	}
}

