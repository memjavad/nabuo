<?php

namespace ArabPsychology\NabooDatabase\Public;

use WP_REST_Request;
use WP_REST_Response;

/**
 * Data Export Features
 *
 * Exports scale data in CSV and JSON formats.
 */
class Data_Export_Features {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register REST API endpoints.
	 */
	public function register_endpoints() {
		register_rest_route(
			'apa/v1',
			'/export/scales',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'export_scales' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'format' => array(
						'type'              => 'string',
						'default'           => 'json',
						'enum'              => array( 'json', 'csv' ),
						'sanitize_callback' => 'sanitize_text_field',
					),
					'category' => array(
						'type'              => 'integer',
						'default'           => 0,
						'sanitize_callback' => 'absint',
					),
					'author' => array(
						'type'              => 'integer',
						'default'           => 0,
						'sanitize_callback' => 'absint',
					),
					'limit' => array(
						'type'              => 'integer',
						'default'           => -1,
						'sanitize_callback' => 'intval',
					),
					'offset' => array(
						'type'              => 'integer',
						'default'           => 0,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			'apa/v1',
			'/export/my-favorites',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'export_user_favorites' ),
				'permission_callback' => 'is_user_logged_in',
				'args'                => array(
					'format' => array(
						'type'              => 'string',
						'default'           => 'json',
						'enum'              => array( 'json', 'csv' ),
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * Export scales data.
	 */
	public function export_scales( WP_REST_Request $request ) {
		$format   = $request->get_param( 'format' );
		$category = $request->get_param( 'category' );
		$author   = $request->get_param( 'author' );
		$limit    = $request->get_param( 'limit' );
		$offset   = $request->get_param( 'offset' );

		$args = array(
			'post_type'      => 'psych_scale',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'offset'         => $offset,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		if ( $category > 0 ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'scale_category',
					'terms'    => $category,
					'field'    => 'term_id',
				),
			);
		}

		if ( $author > 0 ) {
			if ( isset( $args['tax_query'] ) ) {
				$args['tax_query']['relation'] = 'AND';
				$args['tax_query'][] = array(
					'taxonomy' => 'scale_author',
					'terms'    => $author,
					'field'    => 'term_id',
				);
			} else {
				$args['tax_query'] = array(
					array(
						'taxonomy' => 'scale_author',
						'terms'    => $author,
						'field'    => 'term_id',
					),
				);
			}
		}

		$query = new \WP_Query( $args );
		$scales = $this->format_scales_for_export( $query->posts );

		if ( 'csv' === $format ) {
			$csv_data = $this->convert_to_csv( $scales );
			return new WP_REST_Response(
				array(
					'success'  => true,
					'format'   => 'csv',
					'data'     => $csv_data,
					'filename' => 'scales-export-' . date( 'Y-m-d-His' ) . '.csv',
				)
			);
		}

		return new WP_REST_Response(
			array(
				'success'  => true,
				'format'   => 'json',
				'data'     => $scales,
				'count'    => count( $scales ),
				'filename' => 'scales-export-' . date( 'Y-m-d-His' ) . '.json',
			)
		);
	}

	/**
	 * Export user's favorite scales.
	 */
	public function export_user_favorites( WP_REST_Request $request ) {
		$user_id = get_current_user_id();
		$format  = $request->get_param( 'format' );

		global $wpdb;
		$favorites_table = $wpdb->prefix . 'naboo_favorites';

		$favorite_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT scale_id FROM $favorites_table WHERE user_id = %d AND status = 'active' ORDER BY created_at DESC",
			$user_id
		) );

		if ( empty( $favorite_ids ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'No favorites to export',
				),
				404
			);
		}

		$args = array(
			'post_type'   => 'psych_scale',
			'post_status' => 'publish',
			'post__in'    => $favorite_ids,
			'orderby'     => 'post__in',
		);

		$query = new \WP_Query( $args );
		$scales = $this->format_scales_for_export( $query->posts );

		if ( 'csv' === $format ) {
			$csv_data = $this->convert_to_csv( $scales );
			return new WP_REST_Response(
				array(
					'success'  => true,
					'format'   => 'csv',
					'data'     => $csv_data,
					'filename' => 'my-favorites-' . date( 'Y-m-d-His' ) . '.csv',
				)
			);
		}

		return new WP_REST_Response(
			array(
				'success'  => true,
				'format'   => 'json',
				'data'     => $scales,
				'count'    => count( $scales ),
				'filename' => 'my-favorites-' . date( 'Y-m-d-His' ) . '.json',
			)
		);
	}

	/**
	 * Format scales for export.
	 */
	private function format_scales_for_export( $posts ) {
		$scales = array();

		$post_ids = wp_list_pluck( $posts, 'ID' );

		if ( ! empty( $post_ids ) ) {
			update_meta_cache( 'post', $post_ids );
			update_object_term_cache( $post_ids, 'psych_scale' );
		}

		$ratings_map   = $this->get_batch_average_ratings( $post_ids );
		$downloads_map = $this->get_batch_download_counts( $post_ids );

		foreach ( $posts as $post ) {
			$categories = wp_get_post_terms( $post->ID, 'scale_category', array( 'fields' => 'names' ) );
			$authors    = wp_get_post_terms( $post->ID, 'scale_author', array( 'fields' => 'names' ) );

			$items        = get_post_meta( $post->ID, '_naboo_scale_items', true );
			$reliability  = get_post_meta( $post->ID, '_naboo_scale_reliability', true );
			$validity     = get_post_meta( $post->ID, '_naboo_scale_validity', true );
			$year         = get_post_meta( $post->ID, '_naboo_scale_year', true );
			$language     = get_post_meta( $post->ID, '_naboo_scale_language', true );
			$population   = get_post_meta( $post->ID, '_naboo_scale_population', true );

			// Get rating
			$rating = isset( $ratings_map[ $post->ID ] ) ? $ratings_map[ $post->ID ] : '';

			// Get download count
			$download_count = isset( $downloads_map[ $post->ID ] ) ? $downloads_map[ $post->ID ] : 0;

			$scales[] = array(
				'id'           => $post->ID,
				'title'        => $post->post_title,
				'description'  => wp_strip_all_tags( $post->post_content ),
				'categories'   => implode( '; ', $categories ),
				'authors'      => implode( '; ', $authors ),
				'items'        => $items ?: '',
				'reliability'  => $reliability ?: '',
				'validity'     => $validity ?: '',
				'year'         => $year ?: '',
				'language'     => $language ?: '',
				'population'   => $population ?: '',
				'rating'       => $rating,
				'downloads'    => $download_count,
				'url'          => get_permalink( $post->ID ),
				'published'    => $post->post_date,
			);
		}

		return $scales;
	}

	/**
	 * Get average rating for a scale.
	 */
	private function get_average_rating( $scale_id ) {
		global $wpdb;
		$ratings_table = $wpdb->prefix . 'naboo_ratings';

		$avg = $wpdb->get_var( $wpdb->prepare(
			"SELECT AVG(rating) FROM $ratings_table WHERE scale_id = %d AND status = 'approved'",
			$scale_id
		) );

		return $avg ? round( $avg, 2 ) : '';
	}

	/**
	 * Get download count for a scale.
	 */
	private function get_download_count( $scale_id ) {
		global $wpdb;
		$downloads_table = $wpdb->prefix . 'naboo_file_downloads';

		$count = $wpdb->get_var( $wpdb->prepare(
			"SELECT SUM(download_count) FROM $downloads_table WHERE scale_id = %d",
			$scale_id
		) );

		return $count ? (int) $count : 0;
	}

	/**
	 * Get average ratings for multiple scales.
	 */
	private function get_batch_average_ratings( $scale_ids ) {
		if ( empty( $scale_ids ) ) {
			return array();
		}

		global $wpdb;
		$ratings_table = $wpdb->prefix . 'naboo_ratings';

		$placeholders = implode( ',', array_fill( 0, count( $scale_ids ), '%d' ) );

		$query = $wpdb->prepare(
			"SELECT scale_id, AVG(rating) as avg_rating FROM {$ratings_table} WHERE scale_id IN ($placeholders) AND status = 'approved' GROUP BY scale_id",
			$scale_ids
		);

		$results = $wpdb->get_results( $query );

		$map = array();
		foreach ( $results as $row ) {
			$map[ $row->scale_id ] = round( $row->avg_rating, 2 );
		}

		return $map;
	}

	/**
	 * Get download counts for multiple scales.
	 */
	private function get_batch_download_counts( $scale_ids ) {
		if ( empty( $scale_ids ) ) {
			return array();
		}

		global $wpdb;
		$downloads_table = $wpdb->prefix . 'naboo_file_downloads';

		$placeholders = implode( ',', array_fill( 0, count( $scale_ids ), '%d' ) );

		$query = $wpdb->prepare(
			"SELECT scale_id, SUM(download_count) as total_downloads FROM {$downloads_table} WHERE scale_id IN ($placeholders) GROUP BY scale_id",
			$scale_ids
		);

		$results = $wpdb->get_results( $query );

		$map = array();
		foreach ( $results as $row ) {
			$map[ $row->scale_id ] = (int) $row->total_downloads;
		}

		return $map;
	}

	/**
	 * Convert array data to CSV format.
	 */
	private function convert_to_csv( $data ) {
		if ( empty( $data ) ) {
			return '';
		}

		$output = fopen( 'php://memory', 'r+' );

		// Write header
		$headers = array_keys( $data[0] );
		fputcsv( $output, $headers );

		// Write rows
		foreach ( $data as $row ) {
			fputcsv( $output, $row );
		}

		rewind( $output );
		$csv = stream_get_contents( $output );
		fclose( $output );

		return $csv;
	}

	/**
	 * Enqueue scripts and styles.
	 */
	public function enqueue_scripts() {
		if ( ! is_page() && ! is_archive() && ! is_singular( 'psych_scale' ) ) {
			return;
		}

		wp_enqueue_script(
			$this->plugin_name . '-data-export',
			plugins_url( 'js/data-export-features.js', __FILE__ ),
			array( 'jquery' ),
			$this->version,
			true
		);

		wp_enqueue_style(
			$this->plugin_name . '-data-export',
			plugins_url( 'css/data-export-features.css', __FILE__ ),
			array(),
			$this->version
		);

		wp_localize_script(
			$this->plugin_name . '-data-export',
			'apaDataExport',
			array(
				'api_url'      => rest_url( 'apa/v1' ),
				'nonce'        => wp_create_nonce( 'wp_rest' ),
				'is_logged_in' => is_user_logged_in(),
			)
		);
	}
}
