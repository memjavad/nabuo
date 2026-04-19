<?php
/**
 * Batch AI Cron Manager - Handles scheduled tasks and watchdog
 *
 * @package ArabPsychology\NabooDatabase\Admin\Batch_AI
 */

namespace ArabPsychology\NabooDatabase\Admin\Batch_AI;

/**
 * Batch_AI_Cron_Manager class
 */
class Batch_AI_Cron_Manager {

	/**
	 * Remote Sync instance
	 *
	 * @var Batch_AI_Remote_Sync
	 */
	private $remote_sync;

	/**
	 * Constructor
	 */
	public function __construct( Batch_AI_Remote_Sync $remote_sync ) {
		$this->remote_sync = $remote_sync;
	}

	/**
	 * Add custom cron intervals
	 */
	public function add_cron_intervals( $schedules ) {
		if ( ! isset( $schedules['naboo_5min'] ) ) {
			$schedules['naboo_5min'] = array(
				'interval' => 300,
				'display'  => __( 'Every 5 Minutes' ),
			);
		}
		if ( ! isset( $schedules['naboo_10min'] ) ) {
			$schedules['naboo_10min'] = array(
				'interval' => 600,
				'display'  => __( 'Every 10 Minutes' ),
			);
		}
		return $schedules;
	}

	/**
	 * Hourly auto-sync — imports latest page only.
	 */
	public function auto_sync_remote_drafts() {
		$page = max( 1, (int) get_option( 'naboo_remote_last_page', 1 ) );
		$this->remote_sync->do_import_page( $page );
	}

	/**
	 * Full auto-import tick — imports one page and self-reschedules until done.
	 */
	public function full_auto_import_tick() {
		if ( ! get_option( 'naboo_full_auto_import', 0 ) ) {
			return;
		}

		$page   = max( 1, (int) get_option( 'naboo_remote_last_page', 1 ) );
		$result = $this->remote_sync->do_import_page( $page );

		if ( is_wp_error( $result ) ) {
			return;
		}

		if ( $result['has_more'] ) {
			if ( ! wp_next_scheduled( 'naboo_full_auto_import_event' ) ) {
				wp_schedule_single_event( time() + 30, 'naboo_full_auto_import_event' );
			}
		} else {
			update_option( 'naboo_full_auto_import', 0 );
			$ts = wp_next_scheduled( 'naboo_full_auto_import_event' );
			if ( $ts ) {
				wp_unschedule_event( $ts, 'naboo_full_auto_import_event' );
			}
		}
	}

	/**
	 * Watchdog Cron: Ensures the background processor hasn't crashed.
	 */
	public function watchdog_check() {
		$delay_setting = get_option( 'naboo_background_ai_delay', '0' );
		if ( $delay_setting === '0' || ( $delay_setting !== 'random' && (int) $delay_setting <= 0 ) ) {
			return;
		}

		if ( wp_next_scheduled( 'naboo_background_ai_process_draft_event' ) ) {
			return;
		}

		$current = get_transient( 'naboo_ai_current_bg_draft' );
		if ( $current ) {
			$start_time = isset( $current['start'] ) ? (int) $current['start'] : 0;
			if ( time() - $start_time < 1200 ) {
				return;
			}
			delete_transient( 'naboo_ai_current_bg_draft' );
			if ( isset( $current['id'] ) ) {
				delete_transient( 'naboo_processing_lock_' . $current['id'] );
			}
		}

		wp_schedule_single_event( time(), 'naboo_background_ai_process_draft_event' );
	}
}
