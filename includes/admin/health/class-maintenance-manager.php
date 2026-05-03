<?php
/**
 * Maintenance Manager - Handles database optimizations, media scrubbing, and cron management
 *
 * @package ArabPsychology\NabooDatabase\Admin\Health
 */

namespace ArabPsychology\NabooDatabase\Admin\Health;

use ArabPsychology\NabooDatabase\Admin\Database_Indexer;
use ArabPsychology\NabooDatabase\Core\Security_Logger;

/**
 * Maintenance_Manager class
 */
class Maintenance_Manager {

	/**
	 * Execute a maintenance action
	 *
	 * @param string $maintenance_action Action to execute.
	 * @return string Success message.
	 * @throws \Exception On failure.
	 */
	public function execute_action( $maintenance_action ) {
		global $wpdb;
		$message = '';

		switch ( $maintenance_action ) {
			case 'clean_transients':
				$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_%' OR option_name LIKE '_site_transient_%'" );
				$message = __( 'All transients have been cleared.', 'naboodatabase' );
				break;

			case 'optimize_tables':
				$tables = $wpdb->get_col( "SHOW TABLES LIKE '{$wpdb->prefix}naboo_%'" );
				if ( ! empty( $tables ) ) {
					$tables_list = '`' . implode( '`, `', $tables ) . '`';
					$wpdb->query( "OPTIMIZE TABLE $tables_list" );
				}
				Database_Indexer::sync_all_scales();
				$message = __( 'Plugin database tables have been optimized and re-indexed.', 'naboodatabase' );
				break;

			case 'flush_rewrites':
				flush_rewrite_rules( true );
				$message = __( 'Rewrite rules have been flushed successfully.', 'naboodatabase' );
				break;

			case 'purge_revisions':
				$wpdb->query( "DELETE FROM $wpdb->posts WHERE post_type = 'revision'" );
				$message = __( 'Global post revisions have been purged.', 'naboodatabase' );
				break;

			case 'clean_global_content':
				$wpdb->query( "DELETE FROM $wpdb->posts WHERE post_status IN ('trash', 'auto-draft')" );
				$wpdb->query( "DELETE FROM $wpdb->comments WHERE comment_approved = 'spam'" );
				$message = __( 'Trash, auto-drafts, and spam comments have been cleared.', 'naboodatabase' );
				break;

			case 'optimize_all_tables':
				$tables = $wpdb->get_col( 'SHOW TABLES' );
				if ( ! empty( $tables ) ) {
					$tables_list = '`' . implode( '`, `', $tables ) . '`';
					$wpdb->query( "OPTIMIZE TABLE $tables_list" );
				}
				$message = __( 'All database tables have been optimized.', 'naboodatabase' );
				break;

			case 'clear_debug_log':
				$log_file = WP_CONTENT_DIR . '/debug.log';
				if ( file_exists( $log_file ) ) {
					file_put_contents( $log_file, '' );
					$message = __( 'The debug.log file has been emptied.', 'naboodatabase' );
				} else {
					$message = __( 'No debug.log file found.', 'naboodatabase' );
				}
				break;

			case 'fix_cron':
				$this->fix_plugin_crons();
				$message = __( 'Plugin cron events verified and re-scheduled.', 'naboodatabase' );
				break;

			case 'fix_api_connectivity':
				$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_naboo_sync_%'" );
				$checker = new Health_Checker();
				$status  = $checker->check_api_connectivity();
				if ( $status['success'] ) {
					$message = __( 'Connectivity restored and verified.', 'naboodatabase' );
				} else {
					throw new \Exception( sprintf( __( 'Repair attempted, but still failing: %s', 'naboodatabase' ), $status['message'] ) );
				}
				break;

			case 'scrub_media':
				$count   = $this->scrub_media_library();
				$message = sprintf( __( '%d unattached media files removed.', 'naboodatabase' ), $count );
				break;

			case 'test_email':
				if ( $this->test_outbound_email() ) {
					$message = __( 'Test email sent successfully. Check your admin inbox.', 'naboodatabase' );
				} else {
					throw new \Exception( __( 'Failed to send test email. Check your server mail logs.', 'naboodatabase' ) );
				}
				break;

			case 'all':
				$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_%' OR option_name LIKE '_site_transient_%'" );
				$wpdb->query( "DELETE FROM $wpdb->posts WHERE post_status IN ('trash', 'auto-draft')" );
				$wpdb->query( "DELETE FROM $wpdb->comments WHERE comment_approved = 'spam'" );
				$wpdb->query( "DELETE FROM $wpdb->posts WHERE post_type = 'revision'" );
				$tables = $wpdb->get_col( 'SHOW TABLES' );
				if ( ! empty( $tables ) ) {
					$tables_list = '`' . implode( '`, `', $tables ) . '`';
					$wpdb->query( "OPTIMIZE TABLE $tables_list" );
				}
				Database_Indexer::sync_all_scales();
				flush_rewrite_rules( true );
				$this->fix_plugin_crons();
				$message = __( 'Full system optimization complete.', 'naboodatabase' );
				break;
		}

		$logger = new Security_Logger();
		$logger->log( 'system_maintenance', sprintf( __( 'Maintenance action "%s" was executed.', 'naboodatabase' ), $maintenance_action ), 'info' );

		return $message;
	}

	public function fix_plugin_crons() {
		$crons = _get_cron_array();
		if ( is_array( $crons ) ) {
			$modified = false;
			$now      = time();
			foreach ( $crons as $timestamp => $cron_hooks ) {
				if ( $timestamp < $now - 600 ) {
					unset( $crons[ $timestamp ] );
					$modified = true;
				}
			}
			if ( $modified ) {
				_set_cron_array( $crons );
			}
		}

		if ( get_option( 'naboo_remote_auto_sync', 0 ) && ! wp_next_scheduled( 'naboo_remote_auto_sync_event' ) ) {
			wp_schedule_event( time(), 'hourly', 'naboo_remote_auto_sync_event' );
		}
		if ( ! wp_next_scheduled( 'naboo_weekly_sitemap_cron' ) ) {
			wp_schedule_event( time(), 'weekly', 'naboo_weekly_sitemap_cron' );
		}
		if ( ! wp_next_scheduled( 'naboo_queue_cleanup' ) ) {
			wp_schedule_event( time(), 'daily', 'naboo_queue_cleanup' );
		}
		if ( get_option( 'naboo_full_auto_import', 0 ) && ! wp_next_scheduled( 'naboo_full_auto_import_event' ) ) {
			wp_schedule_event( time(), 'naboo_5min', 'naboo_full_auto_import_event' );
		}

		$ai_delay = get_option( 'naboo_background_ai_delay', '0' );
		if ( '0' !== $ai_delay && ! wp_next_scheduled( 'naboo_background_ai_process_draft_event' ) ) {
			$interval = 'hourly';
			if ( strpos( $ai_delay, 'min' ) !== false || (int) $ai_delay < 3600 ) {
				if ( (int) $ai_delay <= 300 ) {
					$interval = 'naboo_5min';
				} elseif ( (int) $ai_delay <= 600 ) {
					$interval = 'naboo_10min';
				}
			}
			wp_schedule_event( time(), $interval, 'naboo_background_ai_process_draft_event' );
		}

		do_action( 'naboo_security_log_event', 'Health Optimizer', 'Fixed plugin cron schedules.', 'medium' );
	}

	public function scrub_media_library() {
		$args        = array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => -1,
			'post_parent'    => 0,
			'fields'         => 'ids',
		);
		$attachments = get_posts( $args );
		$count       = 0;
		foreach ( $attachments as $post_id ) {
			if ( wp_delete_attachment( $post_id, true ) ) {
				$count++;
			}
		}
		return $count;
	}

	public function test_outbound_email() {
		$admin_email = get_option( 'admin_email' );
		$subject     = '[NABOO] Outbound Email Health Test';
		$message     = 'This is a test email sent from the Naboo Health Optimizer to verify your server\'s mail delivery configuration.';
		return wp_mail( $admin_email, $subject, $message );
	}

	public function run_automated_maintenance() {
		global $wpdb;
		$maintenance_actions = array(
			'clean_transients',
			'clean_global_content',
			'optimize_all_tables',
			'fix_cron',
		);
		foreach ( $maintenance_actions as $action ) {
			$this->execute_action( $action );
		}
		Database_Indexer::sync_all_scales();
		flush_rewrite_rules( true );

		$logger = new Security_Logger();
		$logger->log( 'system_maintenance', 'Weekly automated system-wide optimization completed.', 'info' );
	}
}
