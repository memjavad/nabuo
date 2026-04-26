<?php
/**
 * Search Stats Calculator - Handles search index statistics and diagnostics
 *
 * @package ArabPsychology\NabooDatabase\Admin\Search
 */

namespace ArabPsychology\NabooDatabase\Admin\Search;

use ArabPsychology\NabooDatabase\Admin\Database_Indexer;

/**
 * Search_Stats_Calculator class
 */
class Search_Stats_Calculator {

	/**
	 * Get index statistics
	 *
	 * @return array
	 */
	public function get_index_stats() {
		global $wpdb;
		$table = $wpdb->prefix . Database_Indexer::TABLE_NAME;

		// Check table exists
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$table_exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(1) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
			DB_NAME,
			$table
		) );

		if ( ! $table_exists ) {
			return array(
				'exists'    => false,
				'total'     => 0,
				'with_file' => 0,
				'min_year'  => null,
				'max_year'  => null,
				'languages' => 0,
				'published' => 0,
				'coverage'  => 0,
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$stats = $wpdb->get_row( "SELECT COUNT(*) as total, SUM(has_file) as with_file, MIN(year) as min_year, MAX(year) as max_year, COUNT(DISTINCT language) as languages FROM {$table}" );

		$published = (int) wp_count_posts( 'psych_scale' )->publish;
		$indexed   = (int) ( $stats->total ?? 0 );
		$coverage  = $published > 0 ? round( ( $indexed / $published ) * 100 ) : 0;

		return array(
			'exists'    => true,
			'total'     => $indexed,
			'with_file' => (int) ( $stats->with_file ?? 0 ),
			'min_year'  => $stats->min_year ?? null,
			'max_year'  => $stats->max_year ?? null,
			'languages' => (int) ( $stats->languages ?? 0 ),
			'published' => $published,
			'coverage'  => $coverage,
		);
	}

	/**
	 * Get real post status counts for diagnostics
	 *
	 * @return array
	 */
	public function get_post_status_diagnostics() {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return $wpdb->get_results(
			"SELECT post_status, COUNT(*) as cnt
			 FROM {$wpdb->posts}
			 WHERE post_type = 'psych_scale'
			 GROUP BY post_status
			 ORDER BY cnt DESC"
		);
	}
}
