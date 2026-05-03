<?php
/**
 * Batch AI Remote Sync - Handles origin connection and data importing
 *
 * @package ArabPsychology\NabooDatabase\Admin\Batch_AI
 */

namespace ArabPsychology\NabooDatabase\Admin\Batch_AI;

use ArabPsychology\NabooDatabase\Core\Installer;

/**
 * Batch_AI_Remote_Sync class
 */
class Batch_AI_Remote_Sync {

	/**
	 * AJAX: Connect to origin site and fetch supported post types/statuses
	 */
	public function ajax_connect_remote_drafts() {
		check_ajax_referer( 'naboo_batch_ai_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to do this.', 'naboodatabase' ) ) );
		}

		$url   = esc_url_raw( $_POST['remote_url'] ?? '' );
		$token = sanitize_text_field( $_POST['remote_token'] ?? '' );

		if ( empty( $url ) || empty( $token ) ) {
			wp_send_json_error( array( 'message' => 'Both URL and Token are required.' ) );
		}

		$api_endpoint = rtrim( $url, '/' ) . '/?naboo_export=info&token=' . urlencode( $token );
		$response     = $this->make_raw_curl_request( $api_endpoint, $token, 30 );

		if ( $response['error'] ) {
			wp_send_json_error( array( 'message' => 'Connection failed: ' . $response['error'] ) );
		}

		if ( $response['status'] !== 200 ) {
			$err         = json_decode( $response['body'], true );
			$msg         = $err['message'] ?? 'Status ' . $response['status'];
			$raw_preview = substr( strip_tags( $response['body'] ), 0, 300 );
			wp_send_json_error( array(
				'message' => 'HTTP ' . $response['status'] . ' — ' . $msg . '. Origin says: ' . $raw_preview,
			) );
		}

		$data = json_decode( $response['body'], true );
		if ( empty( $data['success'] ) ) {
			wp_send_json_error( array( 'message' => 'Invalid response from origin site.' ) );
		}

		update_option( 'naboo_remote_url', $url );
		update_option( 'naboo_remote_token', $token );

		wp_send_json_success( array(
			'message'  => 'Connected successfully.',
			'types'    => $data['types'] ?? array(),
			'statuses' => $data['statuses'] ?? array()
		) );
	}

	/**
	 * AJAX: Fetch a list of drafts from the remote origin
	 */
	public function ajax_fetch_remote_list() {
		check_ajax_referer( 'naboo_batch_ai_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to do this.', 'naboodatabase' ) ) );
		}

		$url         = esc_url_raw( $_POST['remote_url'] ?? '' );
		$token       = sanitize_text_field( $_POST['remote_token'] ?? '' );
		$post_type   = sanitize_text_field( $_POST['post_type'] ?? 'post' );
		$post_status = sanitize_text_field( $_POST['post_status'] ?? 'draft' );
		$page        = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;

		if ( empty( $url ) || empty( $token ) ) {
			wp_send_json_error( array( 'message' => 'Both URL and Token are required.' ) );
		}

		update_option( 'naboo_remote_post_type', $post_type );
		update_option( 'naboo_remote_post_status', $post_status );

		$api_endpoint = rtrim( $url, '/' ) . '/?naboo_export=drafts&token=' . urlencode( $token );
		$api_endpoint = add_query_arg( array(
			'post_type'   => $post_type,
			'post_status' => $post_status,
			'page'        => $page,
			'per_page'    => 150,
		), $api_endpoint );

		$response = $this->make_raw_curl_request( $api_endpoint, $token, 120 );

		if ( $response['error'] ) {
			wp_send_json_error( array( 'message' => 'Connection failed: ' . $response['error'] ) );
		}

		if ( $response['status'] !== 200 ) {
			$err = json_decode( $response['body'], true );
			$msg = $err['message'] ?? 'Status ' . $response['status'];
			wp_send_json_error( array( 'message' => 'API Error: ' . $msg ) );
		}

		$data = json_decode( $response['body'], true );
		if ( empty( $data['success'] ) || empty( $data['data'] ) ) {
			wp_send_json_error( array( 'message' => 'No drafts returned from origin.' ) );
		}

		wp_send_json_success( array(
			'message'      => sprintf( 'Found %d posts on Page %d. Starting import...', count( $data['data'] ), $data['current_page'] ),
			'posts'        => $data['data'],
			'total_pages'  => 'Unknown',
			'current_page' => $data['current_page'],
		) );
	}

	/**
	 * AJAX: Import a single post from the remote origin
	 */
	public function ajax_import_remote_single() {
		check_ajax_referer( 'naboo_batch_ai_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to do this.', 'naboodatabase' ) ) );
		}

		$post = isset( $_POST['post_data'] ) ? $_POST['post_data'] : array();
		if ( empty( $post['title'] ) ) {
			wp_send_json_error( array( 'message' => 'Invalid post data.' ) );
		}

		$origin_id = isset( $post['id'] ) ? absint( $post['id'] ) : 0;
		if ( $origin_id && Installer::is_imported( $origin_id ) ) {
			wp_send_json_success( array( 'message' => 'Skipped (ID#' . $origin_id . '): Already in import log.', 'status' => 'skipped' ) );
		}

		$post_id = wp_insert_post( array(
			'post_type'    => 'naboo_raw_draft',
			'post_title'   => sanitize_text_field( wp_unslash( $post['title'] ) ),
			'post_content' => wp_kses_post( wp_unslash( $post['content'] ) ),
			'post_excerpt' => wp_kses_post( wp_unslash( $post['excerpt'] ?? '' ) ),
			'post_status'  => 'publish',
			'post_author'  => get_current_user_id()
		) );

		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error( array( 'message' => $post_id->get_error_message() ) );
		}

		if ( $origin_id ) {
			update_post_meta( $post_id, '_naboo_remote_origin_id', $origin_id );
			Installer::log_import( $origin_id );
			update_option( 'naboo_remote_last_import_time', time(), false );
		}

		wp_send_json_success( array(
			'message'   => 'Imported: ' . sanitize_text_field( wp_unslash( $post['title'] ) ),
			'status'    => 'imported',
			'log_count' => Installer::get_log_count(),
		) );
	}

	/**
	 * AJAX: Save remote sync settings
	 */
	public function ajax_save_remote_settings() {
		check_ajax_referer( 'naboo_batch_ai_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to do this.', 'naboodatabase' ) ) );
		}

		$auto_sync = isset( $_POST['auto_sync'] ) ? absint( $_POST['auto_sync'] ) : 0;
		update_option( 'naboo_remote_auto_sync', $auto_sync );

		if ( $auto_sync ) {
			if ( ! wp_next_scheduled( 'naboo_remote_auto_sync_event' ) ) {
				wp_schedule_event( time(), 'hourly', 'naboo_remote_auto_sync_event' );
			}
		} else {
			$timestamp = wp_next_scheduled( 'naboo_remote_auto_sync_event' );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, 'naboo_remote_auto_sync_event' );
			}
		}

		wp_send_json_success();
	}

	/**
	 * AJAX: Clear import log and reset cursor
	 */
	public function ajax_clear_import_log() {
		check_ajax_referer( 'naboo_batch_ai_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'No permission.' ) );
		}
		Installer::clear_log();
		delete_option( 'naboo_remote_last_page' );
		delete_option( 'naboo_remote_last_import_time' );
		wp_send_json_success( array( 'message' => 'Import log and cursor cleared.', 'log_count' => 0 ) );
	}

	/**
	 * AJAX: Save pagination cursor
	 */
	public function ajax_save_cursor() {
		check_ajax_referer( 'naboo_batch_ai_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'No permission.' ) );
		}
		$page = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 0;
		if ( $page > 0 ) {
			update_option( 'naboo_remote_last_page', $page, false );
		} else {
			delete_option( 'naboo_remote_last_page' );
		}
		wp_send_json_success();
	}

	/**
	 * AJAX: Import posts from an uploaded JSON file
	 */
	public function ajax_import_from_file() {
		check_ajax_referer( 'naboo_batch_ai_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied.' ) );
		}

		if ( empty( $_FILES['import_file'] ) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK ) {
			$err_code = $_FILES['import_file']['error'] ?? -1;
			wp_send_json_error( array( 'message' => 'File upload error (code ' . $err_code . ').' ) );
		}

		$file = $_FILES['import_file'];
		$ext  = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
		if ( $ext !== 'json' ) {
			wp_send_json_error( array( 'message' => 'Only .json files are supported.' ) );
		}

		$raw = file_get_contents( $file['tmp_name'] );
		$posts = json_decode( $raw, true );
		if ( ! is_array( $posts ) ) {
			wp_send_json_error( array( 'message' => 'Invalid JSON format.' ) );
		}

		$current_user = get_current_user_id() ?: 1;
		$imported     = 0;
		$skipped      = 0;
		$log_entries  = array();

		foreach ( $posts as $post ) {
			$origin_id = isset( $post['id'] ) ? absint( $post['id'] ) : 0;
			$title     = sanitize_text_field( $post['title'] ?? '' );
			$content   = wp_kses_post( $post['content'] ?? '' );

			if ( empty( $title ) && empty( $content ) ) { $skipped++; continue; }
			if ( $origin_id && Installer::is_imported( $origin_id ) ) { $skipped++; continue; }

			$new_id = wp_insert_post( array(
				'post_type'    => 'naboo_raw_draft',
				'post_title'   => $title ?: '(Untitled)',
				'post_content' => $content,
				'post_status'  => 'publish',
				'post_author'  => $current_user,
			) );

			if ( is_wp_error( $new_id ) ) {
				$log_entries[] = 'ERROR: ' . $title . ' — ' . $new_id->get_error_message();
				continue;
			}

			if ( $origin_id ) {
				update_post_meta( $new_id, '_naboo_remote_origin_id', $origin_id );
				Installer::log_import( $origin_id );
			}

			$imported++;
			$log_entries[] = '✅ Imported: ' . $title;
		}

		wp_send_json_success( array(
			'imported' => $imported,
			'skipped'  => $skipped,
			'total'    => count( $posts ),
			'log'      => $log_entries,
			'message'  => 'Done! Imported ' . $imported . ' posts.',
		) );
	}

	/**
	 * AJAX: Import from a given Remote ZIP/JSON URL directly
	 */
	public function ajax_import_from_url() {
		check_ajax_referer( 'naboo_batch_ai_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied.' ) );
		}

		$url = isset( $_POST['zip_url'] ) ? esc_url_raw( $_POST['zip_url'] ) : '';
		if ( empty( $url ) ) {
			wp_send_json_error( array( 'message' => 'Please provide a valid URL.' ) );
		}

		@set_time_limit( 300 );
		$token    = get_option( 'naboo_remote_token', '' );
		$response = $this->make_raw_curl_request( $url, $token, 180 );

		if ( $response['error'] ) {
			wp_send_json_error( array( 'message' => 'Network error fetching URL.' ) );
		}
		if ( $response['status'] !== 200 ) {
			wp_send_json_error( array( 'message' => 'HTTP Error ' . $response['status'] ) );
		}

		$raw_data = $response['body'];
		$posts    = json_decode( $raw_data, true );

		if ( ! is_array( $posts ) ) {
			if ( ! class_exists( 'ZipArchive' ) ) {
				wp_send_json_error( array( 'message' => 'ZipArchive extension missing.' ) );
			}

			$upload_dir = wp_upload_dir();
			$temp_zip   = $upload_dir['basedir'] . '/temp_naboo_import_' . time() . '.zip';
			file_put_contents( $temp_zip, $raw_data );

			$zip = new \ZipArchive();
			if ( $zip->open( $temp_zip ) === true ) {
				$json_content = $zip->getFromName( 'data.json' );
				$zip->close();
				@unlink( $temp_zip );
				if ( $json_content === false ) {
					wp_send_json_error( array( 'message' => 'ZIP missing data.json.' ) );
				}
				$posts = json_decode( $json_content, true );
			} else {
				@unlink( $temp_zip );
				wp_send_json_error( array( 'message' => 'Failed to open ZIP.' ) );
			}
		}

		$current_user = get_current_user_id() ?: 1;
		$imported     = 0;
		$skipped      = 0;
		$log_entries  = array();

		foreach ( $posts as $post ) {
			$origin_id = isset( $post['id'] ) ? absint( $post['id'] ) : 0;
			$title     = sanitize_text_field( $post['title'] ?? '' );
			$content   = wp_kses_post( $post['content'] ?? '' );

			if ( empty( $title ) && empty( $content ) ) { $skipped++; continue; }
			if ( $origin_id && Installer::is_imported( $origin_id ) ) { $skipped++; continue; }

			$new_id = wp_insert_post( array(
				'post_type'    => 'naboo_raw_draft',
				'post_title'   => $title ?: '(Untitled)',
				'post_content' => $content,
				'post_status'  => 'publish',
				'post_author'  => $current_user,
			) );

			if ( is_wp_error( $new_id ) ) {
				$log_entries[] = 'ERROR: ' . $title . ' — ' . $new_id->get_error_message();
				continue;
			}

			if ( $origin_id ) {
				update_post_meta( $new_id, '_naboo_remote_origin_id', $origin_id );
				Installer::log_import( $origin_id );
			}

			$imported++;
			$log_entries[] = '✅ Imported: ' . $title;
		}

		wp_send_json_success( array(
			'imported' => $imported,
			'skipped'  => $skipped,
			'total'    => count( $posts ),
			'log'      => $log_entries,
			'message'  => 'Done! Downloaded ZIP & Imported ' . $imported . ' posts.',
		) );
	}

	/**
	 * Core shared import logic — used by REST, cron, and manual fetch
	 */
	public function do_import_page( $page ) {
		$url         = get_option( 'naboo_remote_url' );
		$token       = get_option( 'naboo_remote_token' );
		$post_type   = get_option( 'naboo_remote_post_type', 'post' );
		$post_status = get_option( 'naboo_remote_post_status', 'draft' );

		if ( empty( $url ) || empty( $token ) ) {
			return new \WP_Error( 'not_configured', 'Origin URL and Token are not configured.' );
		}

		$api_endpoint = rtrim( $url, '/' ) . '/?naboo_export=drafts&token=' . urlencode( $token );
		$api_endpoint = add_query_arg( array(
			'post_type'   => $post_type,
			'post_status' => $post_status,
			'page'        => $page,
			'per_page'    => 150,
		), $api_endpoint );

		$response = $this->make_raw_curl_request( $api_endpoint, $token, 120 );

		if ( $response['error'] ) {
			return new \WP_Error( 'fetch_failed', 'Connection failed: ' . $response['error'] );
		}

		if ( $response['status'] !== 200 ) {
			return new \WP_Error( 'bad_status', 'Origin returned HTTP ' . $response['status'] );
		}

		$data = json_decode( $response['body'], true );
		if ( empty( $data['success'] ) ) {
			return new \WP_Error( 'no_data', 'Origin returned no data.' );
		}

		$posts         = $data['data'] ?? array();
		$imported      = 0;
		$skipped       = 0;
		$errors        = array();
		$current_user  = get_current_user_id() ?: 1;

		foreach ( $posts as $post ) {
			$origin_id = isset( $post['id'] ) ? absint( $post['id'] ) : 0;

			if ( $origin_id && Installer::is_imported( $origin_id ) ) {
				$skipped++;
				continue;
			}

			$post_id = wp_insert_post( array(
				'post_type'    => 'naboo_raw_draft',
				'post_title'   => sanitize_text_field( $post['title'] ?? '' ),
				'post_content' => wp_kses_post( $post['content'] ?? '' ),
				'post_excerpt' => wp_kses_post( $post['excerpt'] ?? '' ),
				'post_status'  => 'publish',
				'post_author'  => $current_user,
			) );

			if ( is_wp_error( $post_id ) ) {
				$errors[] = $post['title'] ?? 'Unknown';
				continue;
			}

			if ( $origin_id ) {
				update_post_meta( $post_id, '_naboo_remote_origin_id', $origin_id );
				Installer::log_import( $origin_id );
			}

			$imported++;
		}

		$next_page = $page + 1;
		$has_more  = count( $posts ) >= 150;

		if ( $has_more ) {
			update_option( 'naboo_remote_last_page', $next_page, false );
		} else {
			delete_option( 'naboo_remote_last_page' );
		}

		if ( $imported > 0 ) {
			update_option( 'naboo_remote_last_import_time', time(), false );
		}

		return array(
			'page'       => $page,
			'imported'   => $imported,
			'skipped'    => $skipped,
			'errors'     => $errors,
			'has_more'   => $has_more,
			'next_page'  => $has_more ? $next_page : null,
			'log_count'  => Installer::get_log_count(),
			'batch_size' => count( $posts ),
		);
	}

	/**
	 * Helper to perform a raw cURL request
	 */
	public function make_raw_curl_request( $url, $token, $timeout = 120 ) {
		$args = array(
			'timeout'     => $timeout,
			'redirection' => 5,
			'user-agent'  => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
			'sslverify'   => false, // Legacy behavior kept
			'headers'     => array(
				'X-Naboo-Token'             => $token,
				'Accept'                    => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
				'Accept-Language'           => 'en-US,en;q=0.5',
				'Connection'                => 'keep-alive',
				'Upgrade-Insecure-Requests' => '1',
				'Sec-Fetch-Dest'            => 'document',
				'Sec-Fetch-Mode'            => 'navigate',
				'Sec-Fetch-Site'            => 'none',
				'Sec-Fetch-User'            => '?1',
				'Cache-Control'             => 'max-age=0',
			),
		);

		$response = wp_safe_remote_get( $url, $args );

		if ( is_wp_error( $response ) ) {
			return array(
				'status' => 0,
				'body'   => '',
				'error'  => $response->get_error_message(),
			);
		}

		return array(
			'status' => wp_remote_retrieve_response_code( $response ),
			'body'   => wp_remote_retrieve_body( $response ),
			'error'  => '',
		);
	}
}
