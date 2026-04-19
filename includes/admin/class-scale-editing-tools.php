<?php
/**
 * Scale Editing Tools
 *
 * @package ArabPsychology\NabooDatabase\Admin
 */

namespace ArabPsychology\NabooDatabase\Admin;

/**
 * Scale_Editing_Tools class - Advanced scale metadata editing.
 */
class Scale_Editing_Tools {

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
			'/scales/(?P<id>\d+)/edit',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_scale_for_editing' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/scales/(?P<id>\d+)/update',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'update_scale' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/scales/(?P<id>\d+)/duplicate',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'duplicate_scale' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/scales/bulk-update',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'bulk_update_scales' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/scales/metadata-templates',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_metadata_templates' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Get scale for editing
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_scale_for_editing( $request ) {
		$scale_id = $request->get_param( 'id' );
		$scale    = get_post( $scale_id );

		if ( ! $scale || $scale->post_type !== 'psych_scale' ) {
			return new \WP_REST_Response(
				array( 'error' => 'Scale not found' ),
				404
			);
		}

		$categories = wp_get_post_terms( $scale_id, 'scale_category', array( 'fields' => 'ids' ) );
		$authors    = wp_get_post_terms( $scale_id, 'scale_author', array( 'fields' => 'ids' ) );

		return new \WP_REST_Response(
			array(
				'id'            => $scale->ID,
				'title'         => $scale->post_title,
				'excerpt'       => $scale->post_excerpt,
				'content'       => $scale->post_content,
				'status'        => $scale->post_status,
				'items'         => (int) get_post_meta( $scale_id, '_naboo_scale_items', true ),
				'reliability'   => get_post_meta( $scale_id, '_naboo_scale_reliability', true ),
				'validity'      => get_post_meta( $scale_id, '_naboo_scale_validity', true ),
				'year'          => (int) get_post_meta( $scale_id, '_naboo_scale_year', true ),
				'language'      => get_post_meta( $scale_id, '_naboo_scale_language', true ),
				'population'    => get_post_meta( $scale_id, '_naboo_scale_population', true ),
				'categories'    => $categories,
				'authors'       => $authors,
			),
			200
		);
	}

	/**
	 * Update scale
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function update_scale( $request ) {
		$scale_id = $request->get_param( 'id' );
		$data     = $request->get_json_params();

		$scale = get_post( $scale_id );

		if ( ! $scale || $scale->post_type !== 'psych_scale' ) {
			return new \WP_REST_Response(
				array( 'error' => 'Scale not found' ),
				404
			);
		}

		// Update post
		$update_args = array(
			'ID' => $scale_id,
		);

		if ( isset( $data['title'] ) ) {
			$update_args['post_title'] = sanitize_text_field( $data['title'] );
		}

		if ( isset( $data['excerpt'] ) ) {
			$update_args['post_excerpt'] = sanitize_textarea_field( $data['excerpt'] );
		}

		if ( isset( $data['content'] ) ) {
			$update_args['post_content'] = wp_kses_post( $data['content'] );
		}

		if ( isset( $data['status'] ) && in_array( $data['status'], array( 'publish', 'draft', 'pending' ), true ) ) {
			$update_args['post_status'] = $data['status'];
		}

		wp_update_post( $update_args );

		// Update metadata
		$metadata_fields = array(
			'_naboo_scale_items'      => 'items',
			'_naboo_scale_reliability' => 'reliability',
			'_naboo_scale_validity'    => 'validity',
			'_naboo_scale_year'        => 'year',
			'_naboo_scale_language'    => 'language',
			'_naboo_scale_population'  => 'population',
		);

		foreach ( $metadata_fields as $meta_key => $param_key ) {
			if ( isset( $data[ $param_key ] ) ) {
				update_post_meta( $scale_id, $meta_key, sanitize_text_field( $data[ $param_key ] ) );
			}
		}

		// Update taxonomies
		if ( isset( $data['categories'] ) && is_array( $data['categories'] ) ) {
			wp_set_post_terms( $scale_id, $data['categories'], 'scale_category' );
		}

		if ( isset( $data['authors'] ) && is_array( $data['authors'] ) ) {
			wp_set_post_terms( $scale_id, $data['authors'], 'scale_author' );
		}

		// Log edit
		update_post_meta( $scale_id, '_naboo_last_edited_by', get_current_user_id() );
		update_post_meta( $scale_id, '_naboo_last_edited_at', current_time( 'mysql' ) );

		return new \WP_REST_Response(
			array( 'message' => 'Scale updated successfully' ),
			200
		);
	}

	/**
	 * Duplicate scale
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function duplicate_scale( $request ) {
		$scale_id = $request->get_param( 'id' );
		$scale    = get_post( $scale_id );

		if ( ! $scale || $scale->post_type !== 'psych_scale' ) {
			return new \WP_REST_Response(
				array( 'error' => 'Scale not found' ),
				404
			);
		}

		// Create duplicate
		$new_post = array(
			'post_type'      => 'psych_scale',
			'post_title'     => $scale->post_title . ' (Copy)',
			'post_content'   => $scale->post_content,
			'post_excerpt'   => $scale->post_excerpt,
			'post_status'    => 'draft',
			'post_author'    => get_current_user_id(),
		);

		$new_post_id = wp_insert_post( $new_post );

		if ( ! $new_post_id ) {
			return new \WP_REST_Response(
				array( 'error' => 'Failed to duplicate scale' ),
				500
			);
		}

		// Copy metadata
		$metadata_fields = array(
			'_naboo_scale_items',
			'_naboo_scale_reliability',
			'_naboo_scale_validity',
			'_naboo_scale_year',
			'_naboo_scale_language',
			'_naboo_scale_population',
		);
		$all_meta = get_post_meta( $scale_id );
		foreach ( $metadata_fields as $meta_key ) {
			if ( isset( $all_meta[ $meta_key ] ) && isset( $all_meta[ $meta_key ][0] ) ) {
				$meta_value = maybe_unserialize( $all_meta[ $meta_key ][0] );
				if ( $meta_value ) {
					update_post_meta( $new_post_id, $meta_key, $meta_value );
				}
			}
		}

		// Copy taxonomies
		$categories = wp_get_post_terms( $scale_id, 'scale_category', array( 'fields' => 'ids' ) );
		$authors    = wp_get_post_terms( $scale_id, 'scale_author', array( 'fields' => 'ids' ) );

		if ( $categories ) {
			wp_set_post_terms( $new_post_id, $categories, 'scale_category' );
		}

		if ( $authors ) {
			wp_set_post_terms( $new_post_id, $authors, 'scale_author' );
		}

		return new \WP_REST_Response(
			array(
				'message'     => 'Scale duplicated successfully',
				'new_post_id' => $new_post_id,
			),
			201
		);
	}

	/**
	 * Bulk update scales
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function bulk_update_scales( $request ) {
		$scale_ids = $request->get_param( 'scale_ids' ) ?? array();
		$updates   = $request->get_param( 'updates' ) ?? array();

		if ( empty( $scale_ids ) || empty( $updates ) ) {
			return new \WP_REST_Response(
				array( 'error' => 'Missing scale IDs or updates' ),
				400
			);
		}

		$updated = 0;

		foreach ( $scale_ids as $scale_id ) {
			// Update metadata
			foreach ( $updates as $meta_key => $value ) {
				update_post_meta( $scale_id, $meta_key, sanitize_text_field( $value ) );
				$updated++;
			}
		}

		return new \WP_REST_Response(
			array( 'message' => sprintf( '%d scales updated', count( $scale_ids ) ) ),
			200
		);
	}

	/**
	 * Get metadata templates
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_metadata_templates( $request ) {
		return new \WP_REST_Response(
			array(
				'templates' => array(
					array(
						'id'      => 'clinical',
						'label'   => 'Clinical Assessment',
						'language' => 'English',
						'population' => 'Clinical populations',
					),
					array(
						'id'      => 'research',
						'label'   => 'Research Instrument',
						'language' => 'English',
						'population' => 'Research samples',
					),
					array(
						'id'      => 'screening',
						'label'   => 'Screening Tool',
						'language' => 'English',
						'population' => 'General population',
					),
					array(
						'id'      => 'educational',
						'label'   => 'Educational Assessment',
						'language' => 'English',
						'population' => 'Students',
					),
				),
			),
			200
		);
	}
}
