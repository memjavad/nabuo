<?php
/**
 * WP-CLI Commands for the Naboo Database Plugin.
 *
 * Usage:
 *   wp naboo process [--limit=<n>] [--concurrency=<n>]
 *   wp naboo queue-stats
 *   wp naboo clear-queue
 *   wp naboo sync [--batch-size=<size>]
 *
 * @package ArabPsychology\NabooDatabase\CLI
 */

namespace ArabPsychology\NabooDatabase\CLI;

use ArabPsychology\NabooDatabase\Core\Installer;
use ArabPsychology\NabooDatabase\Admin\Batch_AI;
use ArabPsychology\NabooDatabase\Admin\Database_Indexer;
use WP_CLI;

/**
 * Naboo Database WP-CLI commands.
 */
class Naboo_CLI {

	/**
	 * Process AI drafts from the command line — no browser needed.
	 *
	 * ## OPTIONS
	 *
	 * [--limit=<n>]
	 * : Maximum number of drafts to process. Default: all.
	 *
	 * [--concurrency=<n>]
	 * : Number of concurrent AI calls (1–5). Default: 1 (CLI is sequential).
	 *
	 * ## EXAMPLES
	 *
	 *     wp naboo process
	 *     wp naboo process --limit=50
	 *     wp naboo process --limit=100 --concurrency=1
	 *
	 * @when after_wp_load
	 */
	public function process( $args, $assoc_args ) {
		$limit       = isset( $assoc_args['limit'] ) ? (int) $assoc_args['limit'] : -1;
		$concurrency = isset( $assoc_args['concurrency'] ) ? max( 1, min( 5, (int) $assoc_args['concurrency'] ) ) : 1;

		// 1. Fetch draft IDs
		$query_args = array(
			'post_type'      => 'naboo_raw_draft',
			'post_status'    => array( 'publish', 'draft', 'pending' ),
			'posts_per_page' => $limit > 0 ? $limit : -1,
			'fields'         => 'ids',
		);
		$draft_ids = get_posts( $query_args );
		$total     = count( $draft_ids );

		if ( $total === 0 ) {
			WP_CLI::success( 'No raw drafts found. Nothing to do.' );
			return;
		}

		// 2. Enqueue into persistent DB queue
		Installer::enqueue_drafts( $draft_ids );
		WP_CLI::log( "Enqueued {$total} drafts into wp_naboo_process_queue." );

		// 3. Process sequentially with progress bar
		$processor = new Batch_AI();
		$progress  = \WP_CLI\Utils\make_progress_bar( "Processing {$total} drafts", $total );
		$done      = 0;
		$failed    = 0;
		$high      = 0;
		$medium    = 0;
		$low       = 0;

		foreach ( $draft_ids as $draft_id ) {
			$result = $processor->do_process_draft( (int) $draft_id );
			if ( is_wp_error( $result ) ) {
				WP_CLI::warning( "Draft {$draft_id}: " . $result->get_error_message() );
				$failed++;
			} else {
				$done++;
				$label = $result['quality_label'] ?? 'low';
				if ( $label === 'high' )   $high++;
				elseif ( $label === 'medium' ) $medium++;
				else $low++;
				WP_CLI::debug( "Draft {$draft_id} → Scale {$result['new_scale_id']} | Quality: {$result['quality_score']}%" );
			}
			$progress->tick();
		}

		$progress->finish();

		// 4. Summary table
		\WP_CLI\Utils\format_items( 'table',
			array(
				array( 'Metric' => 'Total Processed', 'Value' => $done ),
				array( 'Metric' => 'Failed',          'Value' => $failed ),
				array( 'Metric' => 'High Quality (≥80%)', 'Value' => $high ),
				array( 'Metric' => 'Medium Quality (50-79%)', 'Value' => $medium ),
				array( 'Metric' => 'Low Quality (<50%)', 'Value' => $low ),
			),
			array( 'Metric', 'Value' )
		);

		WP_CLI::success( "Done! Processed {$done} drafts ({$failed} failed)." );
	}

	/**
	 * Show the current processing queue statistics.
	 *
	 * ## EXAMPLES
	 *
	 *     wp naboo queue-stats
	 *
	 * @when after_wp_load
	 */
	public function queue_stats( $args, $assoc_args ) {
		$stats = Installer::get_queue_stats();
		\WP_CLI\Utils\format_items( 'table',
			array(
				array( 'Status' => 'Pending',    'Count' => $stats['pending'] ),
				array( 'Status' => 'Processing', 'Count' => $stats['processing'] ),
				array( 'Status' => 'Done',       'Count' => $stats['done'] ),
				array( 'Status' => 'Failed',     'Count' => $stats['failed'] ),
			),
			array( 'Status', 'Count' )
		);

		$failed_items = Installer::get_failed_items();
		if ( ! empty( $failed_items ) ) {
			WP_CLI::warning( count( $failed_items ) . ' items permanently failed. Run `wp naboo process` to retry (after `wp naboo clear-queue`).' );
			foreach ( $failed_items as $item ) {
				WP_CLI::log( "  Draft {$item->draft_id} ({$item->retries} retries): " . substr( $item->error, 0, 100 ) );
			}
		}
	}

	/**
	 * Clear the processing queue entirely.
	 *
	 * ## EXAMPLES
	 *
	 *     wp naboo clear-queue
	 *
	 * @when after_wp_load
	 */
	public function clear_queue( $args, $assoc_args ) {
		Installer::clear_queue();
		WP_CLI::success( 'Processing queue cleared.' );
	}

	/**
	 * Syncs all scales into the flat search index table.
	 *
	 * ## OPTIONS
	 *
	 * [--batch-size=<size>]
	 * : Number of posts to process per batch. Default: 100.
	 *
	 * ## EXAMPLES
	 *
	 *     wp naboo sync
	 *     wp naboo sync --batch-size=500
	 *
	 * @when after_wp_load
	 */
	public function sync( $args, $assoc_args ) {
		global $wpdb;

		// Make sure table exists
		Database_Indexer::create_table();

		$batch_size = (int) ( $assoc_args['batch-size'] ?? 100 );
		$paged      = 1;
		$synced     = 0;

		WP_CLI::log( "Starting search index sync..." );

		do {
			$posts = get_posts( array(
				'post_type'      => 'psych_scale',
				'post_status'    => 'publish',
				'posts_per_page' => $batch_size,
				'paged'          => $paged,
				'fields'         => 'ids',
			) );

			if ( empty( $posts ) ) {
				break;
			}

			foreach ( $posts as $post_id ) {
				Database_Indexer::sync_post( $post_id );
				$synced++;
			}

			WP_CLI::log( "Processed batch {$paged}..." );
			
			if ( function_exists( 'wp_cache_flush' ) ) {
				wp_cache_flush();
			}

			$paged++;
		} while ( count( $posts ) === $batch_size );

		WP_CLI::success( "Successfully synced {$synced} scales to the search index." );
	}

	/**
	 * Creates or rebuilds the search index table.
	 *
	 * ## EXAMPLES
	 *
	 *     wp naboo recreate-index
	 *
	 * @when after_wp_load
	 */
	public function recreate_index() {
		Database_Indexer::create_table();
		WP_CLI::success( "Search index table created or verified." );
	}
}
