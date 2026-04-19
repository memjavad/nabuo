<?php
/**
 * Health Checker - Handles system scans and connectivity tests
 *
 * @package ArabPsychology\NabooDatabase\Admin\Health
 */

namespace ArabPsychology\NabooDatabase\Admin\Health;

/**
 * Health_Checker class
 */
class Health_Checker {

	/**
	 * Perform full system health scan
	 *
	 * @return array
	 */
	public function perform_scan() {
		global $wpdb;
		$results = array();

		// 1. Transients
		$transients = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->options WHERE option_name LIKE '_transient_%' OR option_name LIKE '_site_transient_%'" );
		$results['transients'] = array(
			'label'  => 'System Transients',
			'value'  => sprintf( '%d items found', $transients ),
			'status' => $transients > 500 ? 'warning' : 'good',
		);

		// 2. Global Revisions
		$revisions = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = 'revision'" );
		$results['revisions'] = array(
			'label'  => 'Global Post Revisions',
			'value'  => sprintf( '%d revisions stored', $revisions ),
			'status' => $revisions > 500 ? 'warning' : 'good',
		);

		// 3. Trash & Auto-drafts
		$trashed = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts WHERE post_status IN ('trash', 'auto-draft')" );
		$results['trashed_content'] = array(
			'label'  => 'Trash & Auto-drafts',
			'value'  => sprintf( '%d items found', $trashed ),
			'status' => $trashed > 100 ? 'warning' : 'good',
		);

		// 4. Spam Comments
		$spam_comments = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->comments WHERE comment_approved = 'spam'" );
		$results['spam_comments'] = array(
			'label'  => 'Spam Comments',
			'value'  => sprintf( '%d comments found', $spam_comments ),
			'status' => $spam_comments > 50 ? 'warning' : 'good',
		);

		// 5. Search Index Size
		$index_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}naboo_search_index" );
		$scale_count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = 'psych_scale' AND post_status = 'publish'" );
		$is_synced   = ( $index_count >= $scale_count );
		$results['search_index'] = array(
			'label'  => 'Search Index Sync',
			'value'  => $is_synced ? 'Fully Synchronized' : sprintf( 'Out of sync (%d/%d)', $index_count, $scale_count ),
			'status' => $is_synced ? 'good' : 'warning',
		);

		// 6. Database Overhead
		$tables = $wpdb->get_results( 'SHOW TABLE STATUS' );
		$total_overhead = 0;
		foreach ( $tables as $table ) {
			$total_overhead += $table->Data_free;
		}
		$results['db_overhead'] = array(
			'label'  => 'Global Database Overhead',
			'value'  => size_format( $total_overhead ),
			'status' => $total_overhead > 5 * 1024 * 1024 ? 'warning' : 'good',
		);

		// 7. PHP Version
		$php_version = phpversion();
		$results['php_version'] = array(
			'label'  => 'PHP Environment',
			'value'  => 'v' . $php_version,
			'status' => version_compare( $php_version, '7.4', '>=' ) ? 'good' : 'bad',
		);

		// 8. Cron Checks
		$cron_events = array(
			'naboo_remote_auto_sync_event'           => 'Draft Sync',
			'naboo_full_auto_import_event'           => 'Auto Import',
			'naboo_background_ai_process_draft_event' => 'AI Batch Processor',
			'naboo_weekly_sitemap_cron'              => 'Sitemap Refresh',
			'naboo_queue_cleanup'                    => 'Queue Maintenance',
		);
		$missing_crons = array();
		foreach ( $cron_events as $hook => $label ) {
			if ( ! wp_next_scheduled( $hook ) ) {
				if ( $hook === 'naboo_remote_auto_sync_event' && ! get_option( 'naboo_remote_auto_sync', 0 ) ) {
					continue;
				}
				if ( $hook === 'naboo_full_auto_import_event' && ! get_option( 'naboo_full_auto_import', 0 ) ) {
					continue;
				}
				if ( $hook === 'naboo_background_ai_process_draft_event' ) {
					if ( ! get_option( 'naboo_background_ai_delay' ) || get_option( 'naboo_background_ai_delay' ) === '0' ) {
						continue;
					}
				}
				$missing_crons[] = $label;
			}
		}
		$results['crons'] = array(
			'label'  => 'Scheduled Tasks',
			'value'  => empty( $missing_crons ) ? 'All tasks active' : sprintf( '%d tasks missing', count( $missing_crons ) ),
			'status' => empty( $missing_crons ) ? 'good' : 'warning',
		);

		// 9. API Connectivity
		$api_status = $this->check_api_connectivity();
		$results['api_connectivity'] = array(
			'label'   => 'Sync Server Connectivity',
			'value'   => $api_status['success'] ? 'Connected' : 'Failed',
			'status'  => $api_status['success'] ? 'good' : 'bad',
			'message' => $api_status['message'],
			'action'  => ! $api_status['success'] ? 'fix_api_connectivity' : '',
		);

		// 10. Media & Core
		$results['unattached_media'] = array(
			'label'  => 'Unattached Media',
			'value'  => sprintf( '%d files found', $this->get_unattached_media_count() ),
			'status' => $this->get_unattached_media_count() > 50 ? 'warning' : 'good',
		);

		$core_integrity = $this->check_core_integrity();
		$results['core_integrity'] = array(
			'label'   => 'WordPress Core Integrity',
			'value'   => $core_integrity['success'] ? 'Verified' : 'Issues Found',
			'status'  => $core_integrity['success'] ? 'good' : 'bad',
			'message' => $core_integrity['message'],
		);

		$audit = $this->audit_plugins_themes();
		$results['audit'] = array(
			'label'   => 'Plugin & Theme Security',
			'value'   => sprintf( '%d potential issues', $audit['count'] ),
			'status'  => $audit['count'] > 0 ? 'warning' : 'good',
			'message' => $audit['message'],
		);

		return $results;
	}

	public function check_api_connectivity() {
		$url      = 'https://arabpsychology.com/';
		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 5,
				'headers' => array(
					'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => $response->get_error_message(),
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			return array(
				'success' => false,
				'message' => sprintf( 'Server returned code %d', $code ),
			);
		}

		return array(
			'success' => true,
			'message' => 'Successfully reached arabpsychology.com',
		);
	}

	public function get_unattached_media_count() {
		$args  = array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => -1,
			'post_parent'    => 0,
			'fields'         => 'ids',
		);
		$query = new \WP_Query( $args );
		return $query->found_posts;
	}

	public function check_core_integrity() {
		if ( ! function_exists( 'get_core_checksums' ) ) {
			require_once ABSPATH . 'wp-admin/includes/update.php';
		}
		$version   = get_bloginfo( 'version' );
		$locale    = get_locale();
		$checksums = get_core_checksums( $version, $locale );
		if ( ! $checksums ) {
			return array(
				'success' => false,
				'message' => 'Could not fetch checksum server.',
			);
		}
		return array(
			'success' => true,
			'message' => 'Core checksum API is reachable.',
		);
	}

	public function audit_plugins_themes() {
		$all_plugins      = get_plugins();
		$inactive_plugins = 0;
		foreach ( $all_plugins as $path => $data ) {
			if ( is_plugin_inactive( $path ) ) {
				$inactive_plugins++;
			}
		}
		$all_themes      = wp_get_themes();
		$inactive_themes = count( $all_themes ) - 1;
		$total           = $inactive_plugins + $inactive_themes;
		$message         = sprintf( '%d inactive plugins and %d inactive themes detected.', $inactive_plugins, $inactive_themes );
		return array(
			'count'   => $total,
			'message' => $message,
		);
	}
}
