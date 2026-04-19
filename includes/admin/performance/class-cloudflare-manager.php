<?php
/**
 * Cloudflare Manager - Handles Cloudflare API integration
 *
 * @package ArabPsychology\NabooDatabase\Admin\Performance
 */

namespace ArabPsychology\NabooDatabase\Admin\Performance;

use ArabPsychology\NabooDatabase\Admin\Cloudflare_Integration;

/**
 * Cloudflare_Manager class
 */
class Cloudflare_Manager {

	/**
	 * AJAX: Whitelist server IP in Cloudflare
	 */
	public function ajax_cf_whitelist_ip() {
		check_ajax_referer( 'naboo_cf_action' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized.', 'naboodatabase' ) );
		}

		if ( ! class_exists( 'ArabPsychology\NabooDatabase\Admin\Cloudflare_Integration' ) ) {
			wp_send_json_error( __( 'Cloudflare integration module missing.', 'naboodatabase' ) );
		}

		$cf     = new Cloudflare_Integration();
		$result = $cf->whitelist_server_ip();

		if ( $result['success'] ) {
			wp_send_json_success( $result['message'] );
		} else {
			wp_send_json_error( $result['message'] );
		}
	}

	/**
	 * AJAX: Purge all Cloudflare cache
	 */
	public function ajax_purge_cloudflare_all() {
		check_ajax_referer( 'naboo_cf_action' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized.', 'naboodatabase' ) );
		}

		if ( ! class_exists( 'ArabPsychology\NabooDatabase\Admin\Cloudflare_Integration' ) ) {
			wp_send_json_error( __( 'Cloudflare integration module missing.', 'naboodatabase' ) );
		}

		$cf     = new Cloudflare_Integration();
		$result = $cf->purge_everything();

		if ( $result['success'] ) {
			wp_send_json_success( $result['message'] );
		} else {
			wp_send_json_error( $result['message'] );
		}
	}

	/**
	 * AJAX: Deploy Cloudflare Worker
	 */
	public function ajax_deploy_cloudflare_worker() {
		check_ajax_referer( 'naboo_cf_action' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized.', 'naboodatabase' ) );
		}

		if ( ! class_exists( 'ArabPsychology\NabooDatabase\Admin\Cloudflare_Integration' ) ) {
			wp_send_json_error( __( 'Cloudflare integration module missing.', 'naboodatabase' ) );
		}

		$cf     = new Cloudflare_Integration();
		$result = $cf->deploy_edge_worker();

		if ( $result['success'] ) {
			wp_send_json_success( $result['message'] );
		} else {
			wp_send_json_error( $result['message'] );
		}
	}

	/**
	 * AJAX: Create Cloudflare Cache Rule
	 */
	public function ajax_cf_create_cache_rule() {
		check_ajax_referer( 'naboo_cf_action' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized.', 'naboodatabase' ) );
		}

		if ( ! class_exists( 'ArabPsychology\NabooDatabase\Admin\Cloudflare_Integration' ) ) {
			wp_send_json_error( __( 'Cloudflare integration module missing.', 'naboodatabase' ) );
		}

		$cf     = new Cloudflare_Integration();
		$result = $cf->create_page_rule_cache_everything();

		if ( $result['success'] ) {
			wp_send_json_success( $result['message'] );
		} else {
			wp_send_json_error( $result['message'] );
		}
	}

	/**
	 * Sync Cloudflare settings on option save
	 */
	public function sync_cloudflare_settings_on_save( $old_value, $new_value, $option_name ) {
		if ( ! class_exists( 'ArabPsychology\NabooDatabase\Admin\Cloudflare_Integration' ) ) {
			return;
		}

		$cf = new Cloudflare_Integration();
		if ( ! $cf->is_active() ) {
			return;
		}

		if ( isset( $new_value['cf_brotli'] ) && ( ! isset( $old_value['cf_brotli'] ) || $old_value['cf_brotli'] !== $new_value['cf_brotli'] ) ) {
			$cf->update_zone_setting( 'brotli', $new_value['cf_brotli'] ? 'on' : 'off' );
		}

		if ( isset( $new_value['cf_early_hints'] ) && ( ! isset( $old_value['cf_early_hints'] ) || $old_value['cf_early_hints'] !== $new_value['cf_early_hints'] ) ) {
			$cf->update_zone_setting( 'early_hints', $new_value['cf_early_hints'] ? 'on' : 'off' );
		}

		if ( isset( $new_value['cf_auto_minify'] ) && ( ! isset( $old_value['cf_auto_minify'] ) || $old_value['cf_auto_minify'] !== $new_value['cf_auto_minify'] ) ) {
			$state = $new_value['cf_auto_minify'] ? 'on' : 'off';
			$cf->update_zone_setting( 'minify', array( 'css' => $state, 'html' => $state, 'js' => $state ) );
		}

		if ( isset( $new_value['cf_tiered_cache'] ) && ( ! isset( $old_value['cf_tiered_cache'] ) || $old_value['cf_tiered_cache'] !== $new_value['cf_tiered_cache'] ) ) {
			$cf->update_zone_setting( 'tiered_cache', $new_value['cf_tiered_cache'] ? 'on' : 'off' );
		}
	}
}
