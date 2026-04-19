<?php
/**
 * Import Processor - Handles file parsing and scale creation
 *
 * @package ArabPsychology\NabooDatabase\Admin\Import
 */

namespace ArabPsychology\NabooDatabase\Admin\Import;

/**
 * Import_Processor class
 */
class Import_Processor {

	/**
	 * Parse import file
	 *
	 * @param string $content   The file content.
	 * @param string $file_type The file type.
	 * @return array
	 */
	public function parse_file( $content, $file_type ) {
		if ( 'application/json' === $file_type ) {
			$data = json_decode( $content, true );
			return is_array( $data ) ? $data : array();
		} elseif ( 'text/csv' === $file_type ) {
			$lines  = explode( "\n", $content );
			$header_line = array_shift( $lines );
			if ( empty( $header_line ) ) {
				return array();
			}
			$header = str_getcsv( $header_line );
			$data   = array();

			foreach ( $lines as $line ) {
				if ( ! empty( trim( $line ) ) ) {
					$values = str_getcsv( $line );
					if ( count( $header ) === count( $values ) ) {
						$data[] = array_combine( $header, $values );
					}
				}
			}

			return $data;
		}

		return array();
	}

	/**
	 * Import single scale
	 *
	 * @param array $row The row data.
	 * @return array
	 */
	public function import_scale( $row ) {
		try {
			if ( empty( $_FILES['import_file'] ) ) {
				throw new \Exception( 'No file uploaded.' );
			}

			$file = $_FILES['import_file'];

			// Validate required fields
			if ( empty( $row['title'] ) ) {
				return array(
					'success' => false,
					'error'   => 'Missing title',
				);
			}

			// Create post
			$post_id = wp_insert_post(
				array(
					'post_type'      => 'psych_scale',
					'post_title'     => sanitize_text_field( $row['title'] ),
					'post_content'   => isset( $row['description'] ) ? wp_kses_post( $row['description'] ) : '',
					'post_excerpt'   => isset( $row['excerpt'] ) ? sanitize_textarea_field( $row['excerpt'] ) : '',
					'post_status'    => 'draft',
					'post_author'    => get_current_user_id(),
				)
			);

			if ( ! $post_id ) {
				return array(
					'success' => false,
					'error'   => 'Failed to create post',
				);
			}

			// Add metadata
			if ( isset( $row['items'] ) ) {
				update_post_meta( $post_id, '_naboo_scale_items', (int) $row['items'] );
			}

			if ( isset( $row['reliability'] ) ) {
				update_post_meta( $post_id, '_naboo_scale_reliability', sanitize_text_field( $row['reliability'] ) );
			}

			if ( isset( $row['validity'] ) ) {
				update_post_meta( $post_id, '_naboo_scale_validity', sanitize_text_field( $row['validity'] ) );
			}

			if ( isset( $row['year'] ) ) {
				update_post_meta( $post_id, '_naboo_scale_year', (int) $row['year'] );
			}

			if ( isset( $row['language'] ) ) {
				update_post_meta( $post_id, '_naboo_scale_language', sanitize_text_field( $row['language'] ) );
			}

			if ( isset( $row['population'] ) ) {
				update_post_meta( $post_id, '_naboo_scale_population', sanitize_text_field( $row['population'] ) );
			}

			// Set taxonomies if provided
			if ( isset( $row['category'] ) ) {
				$cat = get_term_by( 'name', $row['category'], 'scale_category' );
				if ( $cat ) {
					wp_set_post_terms( $post_id, $cat->term_id, 'scale_category' );
				}
			}

			if ( isset( $row['author'] ) ) {
				$author = get_term_by( 'name', $row['author'], 'scale_author' );
				if ( $author ) {
					wp_set_post_terms( $post_id, $author->term_id, 'scale_author' );
				}
			}

			return array(
				'success'  => true,
				'scale_id' => $post_id,
			);
		} catch ( \Exception $e ) {
			return array(
				'success' => false,
				'error'   => $e->getMessage(),
			);
		}
	}
}
