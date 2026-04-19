<?php
/**
 * Batch AI Processor - Handles extraction and background processing
 *
 * @package ArabPsychology\NabooDatabase\Admin\Batch_AI
 */

namespace ArabPsychology\NabooDatabase\Admin\Batch_AI;

use ArabPsychology\NabooDatabase\Core\AI_Extractor;
use ArabPsychology\NabooDatabase\Core\Installer;

/**
 * Batch_AI_Processor class
 */
class Batch_AI_Processor {

	/**
	 * AJAX: Toggle background AI processing
	 */
	public function ajax_toggle_background_ai() {
		check_ajax_referer( 'naboo_batch_ai_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'No permission.' ) );
		}

		$delay = isset( $_POST['delay'] ) ? sanitize_text_field( wp_unslash( $_POST['delay'] ) ) : '0';
		$rand_min = isset( $_POST['random_min'] ) ? absint( $_POST['random_min'] ) : 45;
		$rand_max = isset( $_POST['random_max'] ) ? absint( $_POST['random_max'] ) : 90;
		$keep_active = isset( $_POST['keep_active'] ) && $_POST['keep_active'] === '1' ? 1 : 0;
		$daily_limit = isset( $_POST['daily_limit'] ) ? absint( $_POST['daily_limit'] ) : 0;
		
		if ( $delay !== '0' && ( $delay === 'random' || absint( $delay ) > 0 ) ) {
			update_option( 'naboo_background_ai_delay', $delay );
			update_option( 'naboo_bg_ai_random_min', $rand_min );
			update_option( 'naboo_bg_ai_random_max', $rand_max );
			update_option( 'naboo_bg_ai_keep_active', $keep_active );
			update_option( 'naboo_bg_ai_daily_limit', $daily_limit );
			
			if ( ! wp_next_scheduled( 'naboo_background_ai_process_draft_event' ) ) {
				wp_schedule_single_event( time(), 'naboo_background_ai_process_draft_event' );
			}
			wp_send_json_success( array( 'message' => 'Background processing started.', 'active' => true ) );
		} else {
			update_option( 'naboo_background_ai_delay', 0 );
			$timestamp = wp_next_scheduled( 'naboo_background_ai_process_draft_event' );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, 'naboo_background_ai_process_draft_event' );
			}
			wp_send_json_success( array( 'message' => 'Background processing stopped.', 'active' => false ) );
		}
	}

	/**
	 * AJAX: Process a single draft immediately (manual trigger)
	 */
	public function ajax_process_single_draft() {
		check_ajax_referer( 'naboo_batch_ai_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to do this.', 'naboodatabase' ) ) );
		}

		$draft_id = isset( $_POST['draft_id'] ) ? absint( $_POST['draft_id'] ) : 0;
		if ( ! $draft_id ) {
			wp_send_json_error( array( 'message' => __( 'No Draft ID provided.', 'naboodatabase' ) ) );
		}

		$draft = get_post( $draft_id );
		if ( ! $draft || $draft->post_type !== 'naboo_raw_draft' ) {
			wp_send_json_error( array( 'message' => __( 'Invalid Draft.', 'naboodatabase' ) ) );
		}

		// 1. Get raw text
		$raw_text = $draft->post_content;
		if ( empty( $raw_text ) ) {
			wp_update_post( array( 'ID' => $draft_id, 'post_status' => 'trash' ) );
			wp_send_json_error( array( 'message' => __( 'Draft content is empty. Trashed.', 'naboodatabase' ) ) );
		}

		// 2. Extract Data using AI
		$extractor      = new AI_Extractor();
		$extracted_data = $extractor->extract_from_text( $raw_text );

		if ( is_wp_error( $extracted_data ) ) {
			wp_send_json_error( array( 'message' => $extracted_data->get_error_message() ) );
		}

		// 3. Assemble and Create the Scale
		if ( empty( $extracted_data['title'] ) ) {
			$extracted_data['title'] = $draft->post_title . ' (Processed)';
		}

		$post_data = array(
			'post_title'   => sanitize_text_field( $extracted_data['title'] ),
			'post_content' => '',
			'post_status'  => get_option( 'naboo_auto_publish', 0 ) ? 'publish' : get_option( 'naboo_default_submission_status', 'pending' ),
			'post_type'    => 'psych_scale',
			'post_author'  => get_current_user_id()
		);

		$new_scale_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $new_scale_id ) ) {
			wp_send_json_error( array( 'message' => $new_scale_id->get_error_message() ) );
		}

		// Update Meta
		$this->save_meta_fields( $new_scale_id, $extracted_data );

		if ( isset( $extracted_data['items'] ) && ! empty( $extracted_data['items'] ) ) {
			update_post_meta( $new_scale_id, '_naboo_scale_items', absint( $extracted_data['items'] ) );
		}
		if ( isset( $extracted_data['year'] ) && ! empty( $extracted_data['year'] ) ) {
			update_post_meta( $new_scale_id, '_naboo_scale_year', absint( $extracted_data['year'] ) );
		}

		// 4. Mark draft as processed
		global $wpdb;
		$wpdb->update(
			$wpdb->posts,
			array( 'post_status' => 'naboo_processed' ),
			array( 'ID' => $draft_id ),
			array( '%s' ),
			array( '%d' )
		);
		clean_post_cache( $draft_id );

		wp_send_json_success( array(
			'message'      => sprintf( __( 'Processed Draft ID %1$d -> Created Scale "%2$s" (ID: %3$d).', 'naboodatabase' ), $draft_id, esc_html( $extracted_data['title'] ), $new_scale_id ),
			'new_scale_id' => $new_scale_id
		) );
	}

	/**
	 * Background AI processor — imports one draft, refines it, and schedules the next.
	 */
	public function background_ai_process_draft() {
		$delay_setting = get_option( 'naboo_background_ai_delay', '0' );

		if ( $delay_setting === '0' || ( $delay_setting !== 'random' && (int) $delay_setting <= 0 ) ) {
			return; // Background processing disabled
		}

		// 1. Get next pending item from queue
		$queue_item = Installer::dequeue_next();

		if ( ! $queue_item ) {
			// Queue is empty. Check if there are un-queued drafts we can load.
			global $wpdb;
			$table = $wpdb->prefix . 'naboo_process_queue';
			$now = current_time( 'mysql' );

			$refilled = $wpdb->query( $wpdb->prepare( "
                INSERT IGNORE INTO {$table} (draft_id, status, queued_at)
                SELECT p.ID, 'pending', %s
                FROM {$wpdb->posts} p
                LEFT JOIN {$table} q ON p.ID = q.draft_id
                WHERE p.post_type = 'naboo_raw_draft'
                AND p.post_status IN ('publish', 'draft', 'pending')
                AND q.draft_id IS NULL
                LIMIT 500
            ", $now ) );

			if ( $refilled ) {
				$queue_item = Installer::dequeue_next();
			}
		}

		if ( ! $queue_item ) {
			// Nothing left to process anywhere.
			if ( get_option( 'naboo_batch_ai_is_processing_run', 0 ) == 1 ) {
				// We just finished a batch. Send an email to the admin.
				$admin_email = get_option( 'admin_email' );
				$subject     = __( 'Naboo Database: AI Batch Processing Complete', 'naboodatabase' );
				$message     = __( 'The background AI Batch Processor has finished processing all pending raw drafts.', 'naboodatabase' ) . '<br><br>';
				$message    .= '<a href="' . admin_url( 'admin.php?page=naboo-raw-drafts' ) . '">' . __( 'View Processed Scales', 'naboodatabase' ) . '</a>';

				if ( class_exists( '\ArabPsychology\NabooDatabase\Admin\Email_Notifications_System' ) ) {
					$mailer = new \ArabPsychology\NabooDatabase\Admin\Email_Notifications_System();
					$mailer->send_notification( 'batch_complete', $subject, $message, $admin_email );
				} else {
					wp_mail( $admin_email, $subject, strip_tags( str_replace( '<br>', "\n", $message ) ) );
				}

				delete_option( 'naboo_batch_ai_is_processing_run' );
			}

			$keep_active = (bool) get_option( 'naboo_bg_ai_keep_active', false );
			if ( ! $keep_active ) {
				update_option( 'naboo_background_ai_delay', 0 );
				return; // Stop background job completely
			}
		} else {
			// Handle daily limits
			$daily_limit = (int) get_option( 'naboo_bg_ai_daily_limit', 0 );
			$daily_count = (int) get_option( 'naboo_bg_ai_daily_count', 0 );
			$last_date   = get_option( 'naboo_bg_ai_last_date', '' );

			$current_date = current_time( 'Y-m-d' );
			if ( $last_date !== $current_date ) {
				$daily_count = 0;
				update_option( 'naboo_bg_ai_daily_count', 0 );
				update_option( 'naboo_bg_ai_last_date', $current_date );
			}

			if ( $daily_limit > 0 && $daily_count >= $daily_limit ) {
				update_option( 'naboo_batch_ai_limit_triggered', 1 );
			} else {
				update_option( 'naboo_batch_ai_limit_triggered', 0 );
				update_option( 'naboo_batch_ai_is_processing_run', 1 );
				$draft_id = absint( $queue_item->draft_id );
				$draft_title = get_the_title( $draft_id );

				set_transient( 'naboo_ai_current_bg_draft', array(
					'id'    => $draft_id,
					'title' => $draft_title,
					'start' => time()
				), 1 * HOUR_IN_SECONDS );

				$process_result = $this->do_process_draft( $draft_id );

				if ( ! is_wp_error( $process_result ) && ! empty( $process_result['new_scale_id'] ) ) {
					$post_id = $process_result['new_scale_id'];

					set_transient( 'naboo_ai_current_bg_draft', array(
						'id'      => $draft_id,
						'post_id' => $post_id,
						'title'   => get_the_title( $post_id ),
						'refining'=> true,
						'start'   => time()
					), 1 * HOUR_IN_SECONDS );

					$daily_count++;
					update_option( 'naboo_bg_ai_daily_count', $daily_count );

					$this->perform_inline_refinements( $post_id, $draft_id );
				}

				Installer::mark_done( $draft_id );
				delete_transient( 'naboo_ai_current_bg_draft' );
				delete_transient( 'naboo_processing_lock_' . $draft_id );
			}
		}

		$this->reschedule_next_event( $delay_setting );
	}

	/**
	 * Core draft-to-scale transformation engine.
	 */
	public function do_process_draft( $draft_id ) {
		$draft_id = absint( $draft_id );
		$draft    = get_post( $draft_id );

		if ( ! $draft || $draft->post_type !== 'naboo_raw_draft' ) {
			$err = 'Invalid draft ID: ' . $draft_id;
			Installer::mark_failed( $draft_id, $err );
			return new \WP_Error( 'invalid_draft', $err );
		}

		$lock_key = 'naboo_processing_lock_' . $draft_id;
		if ( get_transient( $lock_key ) ) {
			return new \WP_Error( 'already_processing', 'Draft ' . $draft_id . ' is already being processed. Retry in 120 seconds.' );
		}
		set_transient( $lock_key, 1, 120 );

		$raw_text = $draft->post_content;
		if ( empty( $raw_text ) ) {
			global $wpdb;
			$wpdb->update(
				$wpdb->posts,
				array( 'post_status' => 'naboo_processed' ),
				array( 'ID' => $draft_id ),
				array( '%s' ),
				array( '%d' )
			);
			clean_post_cache( $draft_id );
			Installer::mark_done( $draft_id );
			return array( 'success' => true, 'message' => 'Draft content is empty — marked as processed.' );
		}

		$extractor      = new AI_Extractor();
		$extracted_data = $extractor->extract_from_text( $raw_text );

		if ( is_wp_error( $extracted_data ) ) {
			Installer::mark_failed( $draft_id, $extracted_data->get_error_message() );
			return $extracted_data;
		}

		if ( empty( $extracted_data['title'] ) ) {
			$extracted_data['title'] = $draft->post_title . ' (Processed)';
		}

		$key_fields     = array( 'title', 'abstract', 'items', 'year', 'authors', 'reliability', 'validity', 'scoring_rules' );
		$filled         = 0;
		foreach ( $key_fields as $f ) {
			if ( ! empty( $extracted_data[ $f ] ) ) {
				$filled++;
			}
		}
		$quality_score  = (int) round( ( $filled / count( $key_fields ) ) * 100 );

		$auto_publish   = get_option( 'naboo_auto_publish', 0 );
		$default_status = get_option( 'naboo_default_submission_status', 'pending' );
		$post_status    = $auto_publish && $quality_score >= 60 ? 'publish' : $default_status;

		$new_scale_id = wp_insert_post( array(
			'post_title'   => sanitize_text_field( $extracted_data['title'] ),
			'post_content' => '',
			'post_status'  => $post_status,
			'post_type'    => 'psych_scale',
			'post_author'  => get_current_user_id(),
		), true );

		if ( is_wp_error( $new_scale_id ) ) {
			Installer::mark_failed( $draft_id, $new_scale_id->get_error_message() );
			return $new_scale_id;
		}

		$this->save_meta_fields( $new_scale_id, $extracted_data );
		
		if ( ! empty( $extracted_data['items'] ) ) {
			update_post_meta( $new_scale_id, '_naboo_scale_items', absint( $extracted_data['items'] ) );
		}
		if ( ! empty( $extracted_data['year'] ) ) {
			update_post_meta( $new_scale_id, '_naboo_scale_year', absint( $extracted_data['year'] ) );
		}

		update_post_meta( $new_scale_id, '_naboo_ai_quality_score', $quality_score );
		update_post_meta( $new_scale_id, '_naboo_ai_fields_filled', $filled . '/' . count( $key_fields ) );

		$this->assign_taxonomies( $new_scale_id, $extracted_data );

		global $wpdb;
		$wpdb->update(
			$wpdb->posts,
			array( 'post_status' => 'naboo_processed' ),
			array( 'ID' => $draft_id ),
			array( '%s' ),
			array( '%d' )
		);
		clean_post_cache( $draft_id );
		Installer::mark_done( $draft_id );

		$quality_label = $quality_score >= 80 ? 'high' : ( $quality_score >= 50 ? 'medium' : 'low' );

		return array(
			'draft_id'      => $draft_id,
			'new_scale_id'  => $new_scale_id,
			'title'         => $extracted_data['title'],
			'quality_score' => $quality_score,
			'quality_label' => $quality_label,
			'fields_filled' => $filled . '/' . count( $key_fields ),
			'post_status'   => $post_status,
			'message'       => sprintf(
				'Draft %d → Scale "%s" (ID: %d) | Quality: %d%% (%s)',
				$draft_id,
				esc_html( $extracted_data['title'] ),
				$new_scale_id,
				$quality_score,
				$quality_label
			),
		);
	}

	/**
	 * REST Callback: Process a single draft
	 */
	public function rest_process_draft( $request ) {
		$draft_id = $request->get_param( 'draft_id' );
		$result   = $this->do_process_draft( $draft_id );
		if ( is_wp_error( $result ) ) {
			return new \WP_REST_Response(
				array( 'success' => false, 'message' => $result->get_error_message() ),
				422
			);
		}
		return rest_ensure_response( array_merge( array( 'success' => true ), $result ) );
	}

	/**
	 * AJAX: Get current background status
	 */
	public function ajax_get_bg_status() {
		check_ajax_referer( 'naboo_batch_ai_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();

		$current = get_transient( 'naboo_ai_current_bg_draft' );
		$stats = Installer::get_queue_stats();

		$total = $stats['pending'] + $stats['processing'] + $stats['done'] + $stats['failed'];
		$processed = $stats['done'] + $stats['failed'];

		$next_title = 'None';
		global $wpdb;
		$q_table = $wpdb->prefix . 'naboo_process_queue';
		$next_id = $wpdb->get_var( $wpdb->prepare( "SELECT draft_id FROM {$q_table} WHERE status = %s ORDER BY id ASC LIMIT 1", 'pending' ) );
		if ( $next_id ) {
			$next_title = get_the_title( $next_id );
		}

		$is_limit_reached = false;
		$daily_limit = (int) get_option( 'naboo_bg_ai_daily_limit', 0 );
		$daily_count = (int) get_option( 'naboo_bg_ai_daily_count', 0 );
		if ( $daily_limit > 0 && $daily_count >= $daily_limit ) {
			$is_limit_reached = true;
		}

		wp_send_json_success( array(
			'current'    => $current,
			'next_title' => $next_title,
			'stats'      => $stats,
			'daily_count'=> $daily_count,
			'daily_limit'=> $daily_limit,
			'summary'    => sprintf( __( 'Processed: %d / %d drafts', 'naboodatabase' ), $processed, $total ),
			'is_active'  => (bool) wp_next_scheduled( 'naboo_background_ai_process_draft_event' ),
			'limit_reached' => $is_limit_reached
		) );
	}

	/**
	 * AJAX: Reset daily progress
	 */
	public function ajax_reset_daily_progress() {
		check_ajax_referer( 'naboo_batch_ai_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();

		update_option( 'naboo_bg_ai_daily_count', 0 );
		update_option( 'naboo_batch_ai_limit_triggered', 0 );
		wp_send_json_success( __( 'Daily progress reset. Processing should resume on the next cycle.', 'naboodatabase' ) );
	}

	/**
	 * AJAX: Skip current background draft
	 */
	public function ajax_skip_bg_draft() {
		check_ajax_referer( 'naboo_batch_ai_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();

		$current = get_transient( 'naboo_ai_current_bg_draft' );
		if ( $current && isset( $current['id'] ) ) {
			Installer::mark_failed( $current['id'], 'Skipped by user' );
			delete_transient( 'naboo_ai_current_bg_draft' );
			wp_send_json_success( __( 'Draft marked for skipping. The background process will move to the next item shortly.', 'naboodatabase' ) );
		}

		wp_send_json_error( __( 'No draft currently being processed in background.', 'naboodatabase' ) );
	}

	/* ─────────────────────── Helpers ─────────────────────── */

	private function save_meta_fields( $post_id, $data ) {
		$meta_mapping = array(
			'construct'             => '_naboo_scale_construct',
			'keywords'              => '_naboo_scale_keywords',
			'purpose'               => '_naboo_scale_purpose',
			'abstract'              => '_naboo_scale_abstract',
			'items_list'            => '_naboo_scale_items_list',
			'scoring_rules'         => '_naboo_scale_scoring_rules',
			'r_code'                => '_naboo_scale_r_code',
			'language'              => '_naboo_scale_language',
			'test_type'             => '_naboo_scale_test_type',
			'format'                => '_naboo_scale_format',
			'methodology'           => '_naboo_scale_methodology',
			'reliability'           => '_naboo_scale_reliability',
			'validity'              => '_naboo_scale_validity',
			'factor_analysis'       => '_naboo_scale_factor_analysis',
			'population'            => '_naboo_scale_population',
			'age_group'             => '_naboo_scale_age_group',
			'author_details'        => '_naboo_scale_author_details',
			'author_email'          => '_naboo_scale_author_email',
			'author_orcid'          => '_naboo_scale_author_orcid',
			'administration_method' => '_naboo_scale_administration_method',
			'instrument_type'       => '_naboo_scale_instrument_type',
			'source_reference'      => '_naboo_scale_source_reference',
		);

		foreach ( $meta_mapping as $json_key => $meta_key ) {
			if ( isset( $data[ $json_key ] ) && ! empty( $data[ $json_key ] ) ) {
				update_post_meta( $post_id, $meta_key, sanitize_textarea_field( $data[ $json_key ] ) );
			}
		}
	}

	private function assign_taxonomies( $post_id, array $extracted ) {
		if ( ! empty( $extracted['authors'] ) ) {
			$names = array_map( 'trim', explode( ',', $extracted['authors'] ) );
			$names = array_filter( $names );
			if ( ! empty( $names ) ) {
				wp_set_object_terms( $post_id, $names, 'scale_author', false );
			}
		}

		if ( ! empty( $extracted['construct'] ) ) {
			$constructs = array_map( 'trim', explode( ',', $extracted['construct'] ) );
			$constructs = array_filter( $constructs );
			if ( ! empty( $constructs ) ) {
				wp_set_object_terms( $post_id, $constructs, 'scale_category', false );
			}
		}
	}

	private function perform_inline_refinements( $post_id, $draft_id ) {
		$fields_to_refine = array( 'abstract', 'category', 'year', 'authors', 'language', 'test_type', 'format', 'age_group', 'author_details', 'permissions' );
		$extractor = new AI_Extractor();

		foreach ( $fields_to_refine as $field_name ) {
			global $wpdb;
			$q_table = $wpdb->prefix . 'naboo_process_queue';
			$q_status = $wpdb->get_var( $wpdb->prepare( "SELECT status FROM $q_table WHERE draft_id = %d", $draft_id ) );
			if ( $q_status === 'failed' || $q_status === 'done' ) break;

			sleep( 5 );
			if ( function_exists( 'set_time_limit' ) ) set_time_limit( 300 );

			$current_value = $this->get_field_current_value( $post_id, $field_name );
			$result = $extractor->refine_published_field( $post_id, $field_name, $current_value );

			if ( is_wp_error( $result ) ) continue;

			$this->save_refined_value( $post_id, $field_name, $result );
		}
	}

	private function get_field_current_value( $post_id, $field_name ) {
		if ( $field_name === 'author_details' ) {
			return get_post_meta( $post_id, '_naboo_scale_author_details', true );
		}
		if ( $field_name === 'title' ) {
			return get_the_title( $post_id );
		}
		$tax_map = array(
			'year'      => 'scale_year',
			'language'   => 'scale_language',
			'test_type'  => 'scale_test_type',
			'format'    => 'scale_format',
			'age_group'  => 'scale_age_group',
		);
		if ( array_key_exists( $field_name, $tax_map ) ) {
			return implode( ', ', wp_get_object_terms( $post_id, $tax_map[ $field_name ], array( 'fields' => 'names' ) ) );
		}
		return get_post_meta( $post_id, '_naboo_scale_' . $field_name, true );
	}

	private function save_refined_value( $post_id, $field_name, $result ) {
		if ( $field_name === 'title' ) {
			wp_update_post( array( 'ID' => $post_id, 'post_title' => $result ) );
		} elseif ( $field_name === 'author_details' ) {
			update_post_meta( $post_id, '_naboo_scale_author_details', $result );
		} elseif ( $field_name === 'author_orcid' ) {
			update_post_meta( $post_id, '_naboo_scale_author_orcid', $result );
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
				update_post_meta( $post_id, '_naboo_scale_' . $field_name, $result );
			} else {
				update_post_meta( $post_id, '_naboo_scale_' . $field_name, $result );
			}
		}
	}

	private function reschedule_next_event( $delay_setting ) {
		if ( ! wp_next_scheduled( 'naboo_background_ai_process_draft_event' ) ) {
			if ( $delay_setting === 'random' ) {
				$r_min = (int) get_option( 'naboo_bg_ai_random_min', 45 );
				$r_max = (int) get_option( 'naboo_bg_ai_random_max', 90 );
				if ( $r_min >= $r_max ) {
					$r_min = 45;
					$r_max = 90;
				}
				$actual_delay = wp_rand( $r_min * 60, $r_max * 60 );
			} else {
				$actual_delay = (int) $delay_setting;
			}
			wp_schedule_single_event( time() + $actual_delay, 'naboo_background_ai_process_draft_event' );
		}
	}
}
