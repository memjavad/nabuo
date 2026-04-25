<?php
/**
 * Settings AJAX Handlers
 *
 * @package ArabPsychology\NabooDatabase\Admin
 */

namespace ArabPsychology\NabooDatabase\Admin;

use ArabPsychology\NabooDatabase\Admin\Settings\Tab_General;
use ArabPsychology\NabooDatabase\Admin\Settings\Tab_AI;

class Settings_Ajax {

	private $option_name = 'naboodatabase_plugin_settings';

	public function register_hooks() {
		add_action( 'wp_ajax_naboo_test_api_key', array( $this, 'ajax_test_api_key' ) );
		add_action( 'wp_ajax_naboo_export_settings', array( $this, 'ajax_export_settings' ) );
		add_action( 'wp_ajax_naboo_import_settings', array( $this, 'ajax_import_settings' ) );
	}

	/**
	 * AJAX Handler for testing a Gemini API Key using gemma-3-4b-it
	 */
	public function ajax_test_api_key() {
		// Verify nonce and capability
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'naboo_test_key_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', 'naboodatabase' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'naboodatabase' ) );
		}

		$api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( trim( $_POST['api_key'] ) ) : '';

		if ( empty( $api_key ) ) {
			wp_send_json_error( __( 'API key is missing.', 'naboodatabase' ) );
		}

		// Prepare a lightweight test payload using the requested gemma-3-4b-it model
		$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemma-4-4b-it:generateContent?key=' . $api_key;
		
		$body = array(
			'contents' => array(
				array(
					'parts' => array(
						array( 'text' => 'Reply with "ok" exactly.' )
					)
				)
			)
		);

		$args = array(
			'body'        => wp_json_encode( $body ),
			'headers'     => array(
				'Content-Type' => 'application/json',
			),
			'timeout'     => 15,
			'data_format' => 'body',
		);

		$response = wp_remote_post( $url, $args );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( sprintf( __( 'Connection error: %s', 'naboodatabase' ), $response->get_error_message() ) );
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body_json   = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 === $status_code ) {
			wp_send_json_success( __( 'Key Valid (Gemma 3 Responded!)', 'naboodatabase' ) );
		} elseif ( 400 === $status_code ) {
			wp_send_json_error( __( 'Invalid Key (400 Bad Request)', 'naboodatabase' ) );
		} elseif ( 429 === $status_code ) {
			wp_send_json_error( __( 'Rate Limit (429)', 'naboodatabase' ) );
		} elseif ( 403 === $status_code ) {
			wp_send_json_error( __( 'Forbidden (403)', 'naboodatabase' ) );
		} else {
			$error_msg = isset( $body_json['error']['message'] ) ? $body_json['error']['message'] : __( 'Unknown Error', 'naboodatabase' );
			wp_send_json_error( sprintf( __( 'Error %d: %s', 'naboodatabase' ), $status_code, esc_html( $error_msg ) ) );
		}
	}

	/**
	 * AJAX Handler: Export settings as JSON string.
	 */
	public function ajax_export_settings() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'naboo_settings_import_export_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed.' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized.' ) );
		}
		$options = get_option( $this->option_name, array() );
		wp_send_json_success( array( 'json' => wp_json_encode( $options ) ) );
	}

	/**
	 * AJAX Handler: Import settings from JSON string.
	 */
	public function ajax_import_settings() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'naboo_settings_import_export_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed.' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized.' ) );
		}
		
		$json_str = isset( $_POST['import_json'] ) ? wp_unslash( $_POST['import_json'] ) : '';
		$parsed   = json_decode( $json_str, true );
		
		if ( ! is_array( $parsed ) ) {
			wp_send_json_error( array( 'message' => 'Invalid JSON string provided.' ) );
		}

		$sanitized = $parsed;
		$current   = get_option( $this->option_name, array() );

		$gen_tab = new Tab_General();
		$ai_tab  = new Tab_AI();

		$current = array_merge( $current, $gen_tab->sanitize( $sanitized ) );
		$current = array_merge( $current, $ai_tab->sanitize( $sanitized ) );

		update_option( $this->option_name, $current );

		wp_send_json_success( array( 'message' => 'Settings securely imported successfully.' ) );
	}
}
