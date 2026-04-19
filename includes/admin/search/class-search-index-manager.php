<?php
/**
 * Search Index Manager - Handles bulk synchronization and cache management
 *
 * @package ArabPsychology\NabooDatabase\Admin\Search
 */

namespace ArabPsychology\NabooDatabase\Admin\Search;

use ArabPsychology\NabooDatabase\Admin\Database_Indexer;

/**
 * Search_Index_Manager class
 */
class Search_Index_Manager {

	/**
	 * Bulk sync all published scales to the index using direct SQL to
	 * bypass any WordPress query filters that limit get_posts() results.
	 *
	 * @return int Number of synced posts.
	 */
	public function do_bulk_sync() {
		global $wpdb;
		$total  = 0;
		$batch  = 200;
		$offset = 0;
		do {
			// Direct SQL — immune to suppress_filters, WPML, and other hooks
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$ids = $wpdb->get_col( $wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'psych_scale' AND post_status = 'publish' ORDER BY ID ASC LIMIT %d OFFSET %d",
				$batch,
				$offset
			) );
			foreach ( $ids as $pid ) {
				Database_Indexer::sync_post( (int) $pid );
				$total++;
			}
			$offset += $batch;
		} while ( count( $ids ) === $batch );

		$this->clear_cache();

		return $total;
	}

	/**
	 * Clear search filter cache
	 */
	public function clear_cache() {
		delete_transient( 'naboo_search_filters_cache' );
	}
}
