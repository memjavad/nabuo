<?php
/**
 * Bulk Operations
 *
 * @package ArabPsychology\NabooDatabase\Admin
 */

namespace ArabPsychology\NabooDatabase\Admin;

/**
 * Bulk_Operations class - Advanced bulk scale operations.
 */
class Bulk_Operations {

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
			'/bulk/change-status',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'change_status' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/bulk/add-taxonomy',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'add_taxonomy' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/bulk/delete',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'delete_scales' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/bulk/export',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'export_scales' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}

	/**
	 * Change status for multiple scales
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function change_status( $request ) {
		$scale_ids = $request->get_param( 'scale_ids' ) ?? array();
		$status    = $request->get_param( 'status' ) ?? 'draft';

		if ( empty( $scale_ids ) || ! in_array( $status, array( 'publish', 'draft', 'pending', 'trash' ), true ) ) {
			return new \WP_REST_Response(
				array( 'error' => 'Invalid parameters' ),
				400
			);
		}

		$updated = 0;

		foreach ( $scale_ids as $scale_id ) {
			$result = wp_update_post(
				array(
					'ID'          => $scale_id,
					'post_status' => $status,
				)
			);

			if ( $result ) {
				$updated++;
			}
		}

		return new \WP_REST_Response(
			array( 'message' => sprintf( '%d scales updated', $updated ) ),
			200
		);
	}

	/**
	 * Add taxonomy to multiple scales
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function add_taxonomy( $request ) {
		$scale_ids = $request->get_param( 'scale_ids' ) ?? array();
		$taxonomy  = $request->get_param( 'taxonomy' ) ?? 'scale_category';
		$term_ids  = $request->get_param( 'term_ids' ) ?? array();

		if ( empty( $scale_ids ) || empty( $term_ids ) ) {
			return new \WP_REST_Response(
				array( 'error' => 'Missing parameters' ),
				400
			);
		}

		$updated = 0;

		foreach ( $scale_ids as $scale_id ) {
			$result = wp_set_post_terms( $scale_id, $term_ids, $taxonomy, true );

			if ( ! is_wp_error( $result ) ) {
				$updated++;
			}
		}

		return new \WP_REST_Response(
			array( 'message' => sprintf( '%d scales updated', $updated ) ),
			200
		);
	}

	/**
	 * Delete multiple scales
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function delete_scales( $request ) {
		$scale_ids = $request->get_param( 'scale_ids' ) ?? array();
		$permanent = $request->get_param( 'permanent' ) ?? false;

		if ( empty( $scale_ids ) ) {
			return new \WP_REST_Response(
				array( 'error' => 'No scales provided' ),
				400
			);
		}

		$deleted = 0;

		foreach ( $scale_ids as $scale_id ) {
			$result = wp_delete_post( $scale_id, $permanent );

			if ( $result ) {
				$deleted++;
			}
		}

		return new \WP_REST_Response(
			array( 'message' => sprintf( '%d scales deleted', $deleted ) ),
			200
		);
	}

	/**
	 * Export multiple scales
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function export_scales( $request ) {
		$scale_ids = $request->get_param( 'scale_ids' ) ?? array();
		$format    = $request->get_param( 'format' ) ?? 'json';

		if ( empty( $scale_ids ) ) {
			return new \WP_REST_Response(
				array( 'error' => 'No scales provided' ),
				400
			);
		}

		$scales_data = array();

		foreach ( $scale_ids as $scale_id ) {
			$scale = get_post( $scale_id );

			if ( ! $scale ) {
				continue;
			}

			$scales_data[] = array(
				'id'            => $scale->ID,
				'title'         => $scale->post_title,
				'description'   => $scale->post_content,
				'excerpt'       => $scale->post_excerpt,
				'items'         => get_post_meta( $scale_id, '_naboo_scale_items', true ),
				'reliability'   => get_post_meta( $scale_id, '_naboo_scale_reliability', true ),
				'validity'      => get_post_meta( $scale_id, '_naboo_scale_validity', true ),
				'year'          => get_post_meta( $scale_id, '_naboo_scale_year', true ),
				'language'      => get_post_meta( $scale_id, '_naboo_scale_language', true ),
				'population'    => get_post_meta( $scale_id, '_naboo_scale_population', true ),
				'categories'    => wp_get_post_terms( $scale_id, 'scale_category', array( 'fields' => 'names' ) ),
				'authors'       => wp_get_post_terms( $scale_id, 'scale_author', array( 'fields' => 'names' ) ),
			);
		}

		if ( 'csv' === $format ) {
			$csv_data = $this->array_to_csv( $scales_data );
			return new \WP_REST_Response(
				array( 'data' => $csv_data ),
				200
			);
		}

		return new \WP_REST_Response(
			array( 'data' => $scales_data ),
			200
		);
	}

	/**
	 * Convert array to CSV
	 *
	 * @param array $data The data array.
	 * @return string
	 */
	private function array_to_csv( $data ) {
		if ( empty( $data ) ) {
			return '';
		}

		$output = '';
		$header = array_keys( $data[0] );

		$output .= implode( ',', array_map( array( $this, 'escape_csv' ), $header ) ) . "\n";

		foreach ( $data as $row ) {
			$values = array();
			foreach ( $header as $key ) {
				$value = isset( $row[ $key ] ) ? $row[ $key ] : '';
				if ( is_array( $value ) ) {
					$value = implode( ';', $value );
				}
				$values[] = $this->escape_csv( $value );
			}
			$output .= implode( ',', $values ) . "\n";
		}

		return $output;
	}

	/**
	 * Escape CSV value
	 *
	 * @param string $value The value to escape.
	 * @return string
	 */
	private function escape_csv( $value ) {
		if ( strpos( $value, ',' ) !== false || strpos( $value, '"' ) !== false || strpos( $value, "\n" ) !== false ) {
			return '"' . str_replace( '"', '""', $value ) . '"';
		}
		return $value;
	}

	/**
	 * Register Admin Menu
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'edit.php?post_type=psych_scale',
			__( 'Bulk Operations', 'naboodatabase' ),
			__( 'Bulk Ops', 'naboodatabase' ),
			'manage_options',
			'naboo-bulk-ops',
			array( $this, 'render_admin_page' ),
			15
		);
	}

	/**
	 * Enqueue Admin Scripts
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( 'psych_scale_page_naboo-bulk-ops' !== $hook ) {
			return;
		}

		wp_enqueue_script(
			$this->plugin_name . '-bulk-ops',
			plugin_dir_url( __FILE__ ) . 'js/bulk-ops-admin.js',
			array( 'jquery', 'wp-api-fetch' ),
			$this->version,
			true
		);

		wp_localize_script(
			$this->plugin_name . '-bulk-ops',
			'apaBulkOps',
			array(
				'apiUrl' => rest_url( 'apa/v1/bulk' ),
				'nonce'  => wp_create_nonce( 'wp_rest' ),
			)
		);

		wp_enqueue_style(
			$this->plugin_name . '-bulk-ops',
			plugin_dir_url( __FILE__ ) . 'css/bulk-ops-admin.css',
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
		
		$categories = get_terms( array( 'taxonomy' => 'scale_category', 'hide_empty' => false ) );
		?>
		<div class="wrap naboo-admin-wrap">
			<h1><?php esc_html_e( 'Advanced Bulk Operations', 'naboodatabase' ); ?></h1>
			<p><?php esc_html_e( 'Perform batch updates on multiple scales simultaneously.', 'naboodatabase' ); ?></p>

			<div class="naboo-bulk-ops-layout">
				<div class="naboo-bulk-selection-panel">
					<h2>1. <?php esc_html_e( 'Select Scales', 'naboodatabase' ); ?></h2>
					<div class="naboo-selection-filters">
						<input type="text" id="naboo-bulk-search" placeholder="<?php esc_attr_e( 'Search scales...', 'naboodatabase' ); ?>">
						<select id="naboo-bulk-cat-filter">
							<option value=""><?php esc_html_e( 'All Categories', 'naboodatabase' ); ?></option>
							<?php foreach ( $categories as $cat ) : ?>
								<option value="<?php echo esc_attr( $cat->term_id ); ?>"><?php echo esc_html( $cat->name ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="naboo-bulk-list-container">
						<table class="wp-list-table widefat fixed striped">
							<thead>
								<tr>
									<td class="check-column"><input type="checkbox" id="naboo-bulk-select-all"></td>
									<th><?php esc_html_e( 'Title', 'naboodatabase' ); ?></th>
									<th><?php esc_html_e( 'Status', 'naboodatabase' ); ?></th>
								</tr>
							</thead>
							<tbody id="naboo-bulk-items-list">
								<!-- AJAX results -->
								<tr><td colspan="3"><?php esc_html_e( 'Use the search to find scales.', 'naboodatabase' ); ?></td></tr>
							</tbody>
						</table>
					</div>
				</div>

				<div class="naboo-bulk-action-panel">
					<h2>2. <?php esc_html_e( 'Choose Action', 'naboodatabase' ); ?></h2>
					
					<div class="naboo-bulk-card">
						<h3><?php esc_html_e( 'Change Status', 'naboodatabase' ); ?></h3>
						<div class="naboo-action-row">
							<select id="naboo-bulk-status-val">
								<option value="publish"><?php esc_html_e( 'Publish', 'naboodatabase' ); ?></option>
								<option value="draft"><?php esc_html_e( 'Draft', 'naboodatabase' ); ?></option>
								<option value="pending"><?php esc_html_e( 'Pending Review', 'naboodatabase' ); ?></option>
								<option value="trash"><?php esc_html_e( 'Move to Trash', 'naboodatabase' ); ?></option>
							</select>
							<button type="button" class="button button-secondary naboo-bulk-exec" data-action="change-status"><?php esc_html_e( 'Apply Status', 'naboodatabase' ); ?></button>
						</div>
					</div>

					<div class="naboo-bulk-card">
						<h3><?php esc_html_e( 'Add Category', 'naboodatabase' ); ?></h3>
						<div class="naboo-action-row">
							<select id="naboo-bulk-tax-val">
								<?php foreach ( $categories as $cat ) : ?>
									<option value="<?php echo esc_attr( $cat->term_id ); ?>"><?php echo esc_html( $cat->name ); ?></option>
								<?php endforeach; ?>
							</select>
							<button type="button" class="button button-secondary naboo-bulk-exec" data-action="add-taxonomy"><?php esc_html_e( 'Assign Category', 'naboodatabase' ); ?></button>
						</div>
					</div>

					<div class="naboo-bulk-card">
						<h3><?php esc_html_e( 'Data Export', 'naboodatabase' ); ?></h3>
						<div class="naboo-action-row">
							<select id="naboo-bulk-export-format">
								<option value="json">JSON</option>
								<option value="csv">CSV</option>
							</select>
							<button type="button" class="button button-secondary naboo-bulk-exec" data-action="export"><?php esc_html_e( 'Export Selected', 'naboodatabase' ); ?></button>
						</div>
					</div>

				</div>
			</div>

			<div id="naboo-bulk-progress" class="naboo-modal" style="display:none;">
				<div class="naboo-modal-content">
					<h3><?php esc_html_e( 'Processing Bulk Action...', 'naboodatabase' ); ?></h3>
					<div class="naboo-progress-bar-container">
						<div class="naboo-progress-bar" style="width:0%"></div>
					</div>
					<p id="naboo-bulk-status-text"></p>
				</div>
			</div>

		</div>
		<?php
	}
}
