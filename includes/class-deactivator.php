<?php

namespace ArabPsychology\NabooDatabase;

class Deactivator {

	/**
	 * Runs on plugin deactivation.
	 * Clears all plugin-registered WP-Cron events and transients.
	 */
	public static function deactivate() {
		// Clear all known plugin cron hooks.
		$cron_hooks = array(
			'naboo_weekly_sitemap_cron',
			'naboo_full_auto_import_event',
			'naboo_background_ai_process_draft_event',
			'naboo_remote_auto_sync_event',
			'naboo_daily_cron',
			'naboo_queue_cleanup',
		);
		foreach ( $cron_hooks as $hook ) {
			$timestamp = wp_next_scheduled( $hook );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, $hook );
			}
			wp_clear_scheduled_hook( $hook );
		}

		// Clear plugin transients.
		$transient_keys = array(
			'naboo_search_filters_cache',
			'naboo_dynamic_sitemap',
		);
		foreach ( $transient_keys as $key ) {
			delete_transient( $key );
		}

		// Delete dynamic chunk sitemap transients from DB.
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_naboo_%'" );
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_naboo_%'" );

		// Flush rewrite rules last.
		flush_rewrite_rules();
	}

}
