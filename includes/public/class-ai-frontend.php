<?php
/**
 * AI Frontend & AJAX Handlers
 *
 * @package ArabPsychology\NabooDatabase\Public
 */

namespace ArabPsychology\NabooDatabase\Public;

use ArabPsychology\NabooDatabase\Core\AI_Extractor;

class AI_Frontend {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Shortcode for AI PDF Upload Form
	 */
	public function render_ai_submit_shortcode( $atts ) {
		// Only display form if user is logged in
		if ( ! is_user_logged_in() ) {
			return '<div class="naboo-notice error">' . __( 'You must be logged in to upload and extract scale PDFs.', 'naboodatabase' ) . '</div>';
		}

		// Enqueue necessary scripts
		wp_enqueue_script(
			'pdf-js-lib',
			'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js',
			array(),
			'2.16.105',
			true
		);

		wp_enqueue_script(
			'naboo-ai-extractor-script',
			plugin_dir_url( __FILE__ ) . 'js/ai-extractor.js',
			array( 'jquery', 'pdf-js-lib' ),
			$this->version,
			true
		);

		wp_localize_script(
			'naboo-ai-extractor-script',
			'nabooAIExtractor',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'naboo_ai_submit_nonce' ),
			)
		);

		ob_start();
		include plugin_dir_path( __FILE__ ) . 'partials/ai-submit-form.php';
		return ob_get_clean();
	}

	/**
	 * AJAX: Process PDF Upload and Extraction via Gemini
	 */
	public function ajax_process_pdf_extraction() {
		check_ajax_referer( 'naboo_ai_submit_nonce', 'nonce' );

		// Rate Limiting (5 PDF extracts per hour per IP)
		$ip = $_SERVER['REMOTE_ADDR'];
		$transient_key = 'naboo_ai_extract_ratelimit_' . md5( $ip );
		$attempts = get_transient( $transient_key );
		if ( false === $attempts ) {
			set_transient( $transient_key, 1, HOUR_IN_SECONDS );
		} else {
			if ( $attempts >= 5 ) {
				wp_send_json_error( array( 'message' => __( 'You have reached the maximum number of PDF extractions allowed per hour (5). Please try again later.', 'naboodatabase' ) ) );
			}
			set_transient( $transient_key, $attempts + 1, HOUR_IN_SECONDS );
		}

		if ( ! is_user_logged_in() ) wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'naboodatabase' ) ) );
		if ( empty( $_FILES['scale_pdf'] ) ) wp_send_json_error( array( 'message' => __( 'No file uploaded.', 'naboodatabase' ) ) );

		$extracted_text = isset( $_POST['extracted_text'] ) ? wp_unslash( $_POST['extracted_text'] ) : '';

		if ( empty( $extracted_text ) ) {
			wp_send_json_error( array( 'message' => __( 'Could not extract text from the PDF.', 'naboodatabase' ) ) );
		}

		$file = $_FILES['scale_pdf'];
		if ( $file['type'] !== 'application/pdf' ) wp_send_json_error( array( 'message' => __( 'Only PDF files are supported.', 'naboodatabase' ) ) );

		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$attachment_id = media_handle_upload( 'scale_pdf', 0 );

		if ( is_wp_error( $attachment_id ) ) {
			wp_send_json_error( array( 'message' => $attachment_id->get_error_message() ) );
		}

		// Pass extracted text to AI_Extractor
		$extractor = new AI_Extractor();
		$result    = $extractor->extract_from_text( $extracted_text );

		if ( is_wp_error( $result ) ) {
			wp_delete_attachment( $attachment_id, true ); // clean up
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'attachment_id' => $attachment_id,
			'extracted'     => $result,
		) );
	}

	/**
	 * AJAX: Handle Final Submission of Extracted + Reviewed Data
	 */
	public function ajax_submit_ai_scale() {
		check_ajax_referer( 'naboo_ai_submit_nonce', 'nonce' );

		// Honeypot Challenge
		if ( ! empty( $_POST['naboo_website_url'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Spam detected. Submission rejected.', 'naboodatabase' ) ) );
		}

		// Rate Limiting (5 Scale Submissions per hour per IP)
		$ip = $_SERVER['REMOTE_ADDR'];
		$transient_key = 'naboo_ai_submit_ratelimit_' . md5( $ip );
		$attempts = get_transient( $transient_key );
		if ( false === $attempts ) {
			set_transient( $transient_key, 1, HOUR_IN_SECONDS );
		} else {
			if ( $attempts >= 5 ) {
				wp_send_json_error( array( 'message' => __( 'You have reached the maximum number of submissions allowed per hour. Please try again later.', 'naboodatabase' ) ) );
			}
			set_transient( $transient_key, $attempts + 1, HOUR_IN_SECONDS );
		}

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'naboodatabase' ) ) );
		}

		// Sanitize standard fields. We will reuse Frontend logic if we can, or write simple insert for now.
		$title       = sanitize_text_field( wp_unslash( $_POST['scale_title'] ?? '' ) ); // Title shouldn't have HTML usually, but keep simple
		$construct   = wp_kses_post( wp_unslash( $_POST['scale_construct'] ?? '' ) );
		$keywords    = wp_kses_post( wp_unslash( $_POST['scale_keywords'] ?? '' ) );
		$purpose     = wp_kses_post( wp_unslash( $_POST['scale_purpose'] ?? '' ) );
		$abstract    = wp_kses_post( wp_unslash( $_POST['scale_abstract'] ?? '' ) );
		$items       = wp_kses_post( wp_unslash( $_POST['scale_items'] ?? '' ) );
		$items_list  = wp_kses_post( wp_unslash( $_POST['scale_items_list'] ?? '' ) );
		$scoring_rules = wp_kses_post( wp_unslash( $_POST['scale_scoring_rules'] ?? '' ) );
		$r_code      = sanitize_textarea_field( wp_unslash( $_POST['scale_r_code'] ?? '' ) );
		$administration_method = wp_kses_post( wp_unslash( $_POST['scale_administration_method'] ?? '' ) );
		$instrument_type = wp_kses_post( wp_unslash( $_POST['scale_instrument_type'] ?? '' ) );
		$source_reference = wp_kses_post( wp_unslash( $_POST['scale_source_reference'] ?? '' ) );
		$year        = wp_kses_post( wp_unslash( $_POST['scale_year'] ?? '' ) );
		$language    = wp_kses_post( wp_unslash( $_POST['scale_language'] ?? '' ) );
		$test_type   = wp_kses_post( wp_unslash( $_POST['scale_test_type'] ?? '' ) );
		$format      = wp_kses_post( wp_unslash( $_POST['scale_format'] ?? '' ) );
		$methodology = wp_kses_post( wp_unslash( $_POST['scale_methodology'] ?? '' ) );
		$reliability = wp_kses_post( wp_unslash( $_POST['scale_reliability'] ?? '' ) );
		$validity    = wp_kses_post( wp_unslash( $_POST['scale_validity'] ?? '' ) );
		$factor_analysis = wp_kses_post( wp_unslash( $_POST['scale_factor_analysis'] ?? '' ) );
		$population  = wp_kses_post( wp_unslash( $_POST['scale_population'] ?? '' ) );
		$age_group   = sanitize_text_field( wp_unslash( $_POST['scale_age_group'] ?? '' ) );
		$author_details = wp_kses_post( wp_unslash( $_POST['scale_author_details'] ?? '' ) );
		$author_email   = sanitize_email( wp_unslash( $_POST['scale_author_email'] ?? '' ) );
		$author_orcid   = sanitize_text_field( wp_unslash( $_POST['scale_author_orcid'] ?? '' ) );
		$authors        = sanitize_text_field( wp_unslash( $_POST['scale_authors'] ?? '' ) );
		$attachment_id = absint( $_POST['attachment_id'] ?? 0 );

		if ( empty( $title ) ) {
			wp_send_json_error( array( 'message' => __( 'Title is required.', 'naboodatabase' ) ) );
		}

		$status = get_option( 'naboo_auto_publish', 0 ) ? 'publish' : get_option( 'naboo_default_submission_status', 'pending' );

		$post_data = array(
			'post_title'   => $title,
			'post_content' => '',
			'post_status'  => $status,
			'post_type'    => 'psych_scale',
			'post_author'  => get_current_user_id()
		);

		$post_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error( array( 'message' => $post_id->get_error_message() ) );
		}

		// Update Meta
		update_post_meta( $post_id, '_naboo_scale_construct', $construct );
		update_post_meta( $post_id, '_naboo_scale_keywords', $keywords );
		update_post_meta( $post_id, '_naboo_scale_purpose', $purpose );
		update_post_meta( $post_id, '_naboo_scale_abstract', $abstract );
		update_post_meta( $post_id, '_naboo_scale_items', $items );
		update_post_meta( $post_id, '_naboo_scale_items_list', $items_list );
		update_post_meta( $post_id, '_naboo_scale_scoring_rules', $scoring_rules );
		update_post_meta( $post_id, '_naboo_scale_r_code', $r_code );
		update_post_meta( $post_id, '_naboo_scale_administration_method', $administration_method );
		update_post_meta( $post_id, '_naboo_scale_instrument_type', $instrument_type );
		update_post_meta( $post_id, '_naboo_scale_source_reference', $source_reference );
		update_post_meta( $post_id, '_naboo_scale_author_orcid', $author_orcid );

		// Save Taxonomies
		$tax_map = array(
			'scale_author'    => $authors,
			'scale_year'      => $year,
			'scale_language'   => $language,
			'scale_test_type'  => $test_type,
			'scale_format'    => $format,
			'scale_age_group' => $age_group,
		);
		foreach ( $tax_map as $tax => $val ) {
			if ( ! empty( $val ) ) {
				$terms = array_map( 'trim', explode( ',', $val ) );
				$terms = array_filter( $terms );
				wp_set_object_terms( $post_id, $terms, $tax );
			}
		}

		// Check if we attach the PDF
		if ( $attachment_id ) {
			wp_update_post( array(
				'ID' => $attachment_id,
				'post_parent' => $post_id
			) );
			update_post_meta( $post_id, '_naboo_scale_file', $attachment_id );
		}

		wp_send_json_success( array(
			'message' => __( 'Scale submitted successfully.', 'naboodatabase' ),
		) );
	}

	/**
	 * AJAX: Refine a single field utilizing the Gemini API
	 */
	public function ajax_refine_single_field() {
		check_ajax_referer( 'naboo_ai_submit_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'naboodatabase' ) ) );

		$field_name     = sanitize_text_field( $_POST['field_name'] ?? '' );
		$current_value  = sanitize_text_field( $_POST['current_value'] ?? '' );
		$extracted_text = isset( $_POST['extracted_text'] ) ? wp_unslash( $_POST['extracted_text'] ) : '';
		$extra_context  = isset( $_POST['extra_context'] ) ? sanitize_text_field( $_POST['extra_context'] ) : '';

		if ( empty( $field_name ) || empty( $extracted_text ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing required data for refinement.', 'naboodatabase' ) ) );
		}

		$extractor = new AI_Extractor();
		$result = $extractor->refine_single_field( $extracted_text, $field_name, $current_value, $extra_context );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'refined_text' => $result,
		) );
	}

	/**
	 * AJAX: Refine an already published field directly on the frontend (Admin only)
	 */
	public function ajax_inline_ai_refine() {
		check_ajax_referer( 'naboo_search_nonce', 'nonce' );

		$post_id    = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$field_name = sanitize_text_field( $_POST['field_name'] ?? '' );

		if ( ! $post_id || empty( $field_name ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing required data.', 'naboodatabase' ) ) );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to edit this scale.', 'naboodatabase' ) ) );
		}

		// Get current value
		if ( $field_name === 'author_details' ) {
            $current_value = get_post_meta( $post_id, '_naboo_scale_author_details', true );
        } elseif ( $field_name === 'title' ) {
            $current_value = get_the_title( $post_id );
        } else {
		    // Check if it's a taxonomy field
			$tax_map = array(
				'year'      => 'scale_year',
				'language'   => 'scale_language',
				'test_type'  => 'scale_test_type',
				'format'    => 'scale_format',
				'age_group'  => 'scale_age_group',
			);

			if ( array_key_exists( $field_name, $tax_map ) ) {
				$current_value = implode( ', ', wp_get_object_terms( $post_id, $tax_map[ $field_name ], array( 'fields' => 'names' ) ) );
			} else {
				$current_value = get_post_meta( $post_id, '_naboo_scale_' . $field_name, true );
			}
        }

		$extractor = new AI_Extractor();
		$result = $extractor->refine_published_field( $post_id, $field_name, $current_value );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		// Save new value
		if ( $field_name === 'title' ) {
			wp_update_post( array(
				'ID'         => $post_id,
				'post_title' => $result,
			) );
			$formatted = esc_html( $result );
		} else {
            if ( $field_name === 'author_details' ) {
                update_post_meta( $post_id, '_naboo_scale_author_details', $result );
                $formatted = nl2br( make_clickable( esc_html( $result ) ) );
            } elseif ( $field_name === 'author_orcid' ) {
                update_post_meta( $post_id, '_naboo_scale_author_orcid', $result );
                
                $orcids = preg_split('/[\s,]+/', trim($result));
                $orcid_links = array();
                foreach ($orcids as $orcid) {
                    if ( ! empty( $orcid ) ) {
                        $clean_orcid = preg_replace('/^https?:\/\/(www\.)?orcid\.org\//i', '', $orcid);
                        $orcid_links[] = '<a href="https://orcid.org/' . esc_attr( $clean_orcid ) . '" target="_blank">' . esc_html( $clean_orcid ) . '</a>';
                    }
                }
                $formatted = implode(', ', $orcid_links);
            } else {
				$tax_map = array(
					'year'      => 'scale_year',
					'language'   => 'scale_language',
					'test_type'  => 'scale_test_type',
					'format'    => 'scale_format',
					'age_group' => 'scale_age_group',
					'authors'   => 'scale_author',
					'category'  => 'scale_category',
				);

				if ( array_key_exists( $field_name, $tax_map ) ) {
					$terms = array_map( 'trim', explode( ',', $result ) );
					$terms = array_filter( $terms );
					wp_set_object_terms( $post_id, $terms, $tax_map[ $field_name ] );
					update_post_meta( $post_id, '_naboo_scale_' . $field_name, $result ); // Sync meta
					$formatted = get_the_term_list( $post_id, $tax_map[ $field_name ], '', ', ' );
				} else {
					update_post_meta( $post_id, '_naboo_scale_' . $field_name, $result );
					if ( in_array( $field_name, array('abstract', 'purpose', 'construct', 'items_list', 'reliability', 'validity', 'factor_analysis', 'source_reference', 'scoring_rules', 'permissions', 'methodology') ) ) {
						$formatted = nl2br( esc_html( $result ) );
					} elseif ( $field_name === 'r_code' ) {
						// Return raw text for the code block — the JS will set it via innerText
						// which preserves whitespace exactly. nl2br or HTML encoding would break it.
						$formatted = trim( $result );
					} else {
						$formatted = esc_html( $result );
					}
				}
            }
		}

		wp_send_json_success( array(
			'formatted_text' => $formatted,
		) );
	}
}
