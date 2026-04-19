<?php
/**
 * Database Indexer
 * Handles syncing Naboo Database scales into a flat indexed SQL table for fast searching.
 *
 * @package ArabPsychology\NabooDatabase\Admin
 */

namespace ArabPsychology\NabooDatabase\Admin;

/**
 * Database_Indexer class.
 */
class Database_Indexer {

	/**
	 * Name of the custom index table.
	 */
	const TABLE_NAME = 'naboo_search_index';

	/**
	 * Create or update the custom index table.
	 */
	public static function create_table() {
		global $wpdb;

		$table_name      = $wpdb->prefix . self::TABLE_NAME;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			post_id bigint(20) unsigned NOT NULL,
			title varchar(255) NOT NULL DEFAULT '',
			abstract longtext NOT NULL,
			purpose longtext NOT NULL,
			construct longtext NOT NULL,
			population varchar(255) NOT NULL DEFAULT '',
			keywords text NOT NULL,
			reliability text NOT NULL,
			validity text NOT NULL,
			source_reference text NOT NULL,
			year smallint(4) DEFAULT NULL,
			items smallint(5) DEFAULT NULL,
			language varchar(100) NOT NULL DEFAULT '',
			test_type varchar(100) NOT NULL DEFAULT '',
			format varchar(100) NOT NULL DEFAULT '',
			methodology varchar(100) NOT NULL DEFAULT '',
			author_ids varchar(255) NOT NULL DEFAULT '',
			category_ids varchar(255) NOT NULL DEFAULT '',
			age_group_ids varchar(255) NOT NULL DEFAULT '',
			has_file tinyint(1) NOT NULL DEFAULT 0,
			view_count bigint(20) UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY  (post_id),
			KEY year (year),
			KEY items (items),
			KEY language (language),
			KEY has_file (has_file),
			KEY view_count (view_count),
			FULLTEXT KEY search_index (title, abstract, purpose, construct, population, keywords, reliability, validity)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Sync a specific scale post into the flat index table.
	 *
	 * @param int $post_id The ID of the post to sync.
	 */
	public static function sync_post( $post_id ) {
		global $wpdb;

		// Fetch the post directly — use raw DB to avoid object-cache staleness.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$post = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->posts} WHERE ID = %d LIMIT 1",
			$post_id
		) );

		if ( ! $post ) {
			// Post gone entirely — remove from index.
			$wpdb->delete( $wpdb->prefix . self::TABLE_NAME, array( 'post_id' => $post_id ), array( '%d' ) );
			return;
		}

		// Remove from index if the post is no longer a published psych_scale.
		if ( $post->post_type !== 'psych_scale' || $post->post_status !== 'publish' ) {
			$wpdb->delete( $wpdb->prefix . self::TABLE_NAME, array( 'post_id' => $post_id ), array( '%d' ) );
			return;
		}

		// Gather taxonomy IDs
		$author_ids    = implode( ',', wp_list_pluck( wp_get_post_terms( $post_id, 'scale_author' ) ?: array(), 'term_id' ) );
		$category_ids  = implode( ',', wp_list_pluck( wp_get_post_terms( $post_id, 'scale_category' ) ?: array(), 'term_id' ) );
		$age_group_ids = implode( ',', wp_list_pluck( wp_get_post_terms( $post_id, 'scale_age_group' ) ?: array(), 'term_id' ) );

		// Gather meta values
		$abstract    = get_post_meta( $post_id, '_naboo_scale_abstract', true ) ?: $post->post_excerpt ?: wp_strip_all_tags( $post->post_content );
		$purpose     = get_post_meta( $post_id, '_naboo_scale_purpose', true ) ?: '';
		$construct   = get_post_meta( $post_id, '_naboo_scale_construct', true ) ?: '';
		$population  = get_post_meta( $post_id, '_naboo_scale_population', true ) ?: '';
		$keywords    = get_post_meta( $post_id, '_naboo_scale_keywords', true ) ?: '';
		$reliability = get_post_meta( $post_id, '_naboo_scale_reliability', true ) ?: '';
		$validity    = get_post_meta( $post_id, '_naboo_scale_validity', true ) ?: '';
		$source_ref  = get_post_meta( $post_id, '_naboo_scale_source_reference', true ) ?: '';
		$year        = intval( get_post_meta( $post_id, '_naboo_scale_year', true ) ) ?: null;
		$items       = intval( get_post_meta( $post_id, '_naboo_scale_items', true ) ) ?: null;
		$language    = get_post_meta( $post_id, '_naboo_scale_language', true ) ?: '';
		$test_type   = get_post_meta( $post_id, '_naboo_scale_test_type', true ) ?: '';
		$format      = get_post_meta( $post_id, '_naboo_scale_format', true ) ?: '';
		$methodology = get_post_meta( $post_id, '_naboo_scale_methodology', true ) ?: '';
		$has_file    = get_post_meta( $post_id, '_naboo_scale_file', true ) ? 1 : 0;
		$view_count  = (int) ( get_post_meta( $post_id, '_naboo_view_count', true ) ?: 0 );

		$table_name = $wpdb->prefix . self::TABLE_NAME;

		// Truncate oversized text to avoid exceeding max_allowed_packet / MEDIUMTEXT limits
		$max_text = 60000; // ~60 KB per field, safe for default 1MB packet size
		$abstract  = mb_substr( wp_strip_all_tags( $abstract ),  0, $max_text );
		$purpose   = mb_substr( wp_strip_all_tags( $purpose ),   0, $max_text );
		$construct = mb_substr( wp_strip_all_tags( $construct ),  0, $max_text );
		$population = mb_substr( wp_strip_all_tags( $population ), 0, 255 );
		$keywords   = mb_substr( wp_strip_all_tags( $keywords ),  0, 5000 );
		$reliability = mb_substr( wp_strip_all_tags( $reliability ), 0, $max_text );
		$validity   = mb_substr( wp_strip_all_tags( $validity ),  0, $max_text );
		$source_ref = mb_substr( wp_strip_all_tags( $source_ref ), 0, 5000 );

		// Insert or update on duplicate key
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->replace(
			$table_name,
			array(
				'post_id'        => $post_id,
				'title'          => mb_substr( $post->post_title, 0, 255 ),
				'abstract'       => $abstract,
				'purpose'        => $purpose,
				'construct'      => $construct,
				'population'     => $population,
				'keywords'       => $keywords,
				'reliability'    => $reliability,
				'validity'       => $validity,
				'source_reference' => $source_ref,
				'year'           => $year,
				'items'          => $items,
				'language'       => mb_substr( $language, 0, 100 ),
				'test_type'      => mb_substr( $test_type, 0, 100 ),
				'format'         => mb_substr( $format, 0, 100 ),
				'methodology'    => mb_substr( $methodology, 0, 100 ),
				'author_ids'     => mb_substr( $author_ids, 0, 255 ),
				'category_ids'   => mb_substr( $category_ids, 0, 255 ),
				'age_group_ids'  => mb_substr( $age_group_ids, 0, 255 ),
				'has_file'       => $has_file,
				'view_count'     => $view_count,
			),
			array(
				'%d', // post_id
				'%s', // title
				'%s', // abstract
				'%s', // purpose
				'%s', // construct
				'%s', // population
				'%s', // keywords
				'%s', // reliability
				'%s', // validity
				'%s', // source_reference
				'%d', // year
				'%d', // items
				'%s', // language
				'%s', // test_type
				'%s', // format
				'%s', // methodology
				'%s', // author_ids
				'%s', // category_ids
				'%s', // age_group_ids
				'%d', // has_file
				'%d', // view_count
			)
		);

		if ( $result === false && $wpdb->last_error ) {
			error_log( 'NabooDatabase: sync_post failed for ID ' . $post_id . ': ' . $wpdb->last_error );
		}
	}

	/**
	 * Hooked to save_post to automatically keep the index fresh.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @param bool     $update  Whether this is an existing post being updated.
	 */
	public static function trigger_sync_on_save( $post_id, $post, $update ) {
		// Prevent infinite loops or autosaves
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Schedule a single hook event to run asynchronously soon, 
		// removing the heavy indexer from the user's direct save request.
		wp_schedule_single_event( time(), 'naboo_async_sync_post', array( $post_id ) );
	}

	/**
	 * Setup hooks for asynchronous background indexing.
	 * Must be called during plugin initialization (e.g. in class-core.php).
	 */
	public static function init_async_hooks() {
		add_action( 'naboo_async_sync_post', function( $post_id ) {
			self::sync_post( $post_id );
			
			// Clear transient search filters when a scale is updated
			delete_transient( 'naboo_search_filters_cache' );
		});
	}

	/**
	 * Synchronize all published scales into the index table.
	 */
	public static function sync_all_scales() {
		global $wpdb;

		// Ensure table exists
		self::create_table();

		$batch_size = 100;
		$paged      = 1;

		do {
			$posts = get_posts( array(
				'post_type'      => 'psych_scale',
				'post_status'    => 'publish',
				'posts_per_page' => $batch_size,
				'paged'          => $paged,
				'fields'         => 'ids',
				'no_found_rows'  => true, // Performance optimization
			) );

			if ( empty( $posts ) ) {
				break;
			}

			foreach ( $posts as $post_id ) {
				self::sync_post( $post_id );
			}

			if ( function_exists( 'wp_cache_flush' ) ) {
				wp_cache_flush();
			}

			$paged++;
		} while ( count( $posts ) === $batch_size );

		// Clear cache
		delete_transient( 'naboo_search_filters_cache' );
	}
    
    /**
     * Hooked to deleted_post to remove from index
     */
    public static function trigger_sync_on_delete( $post_id ) {
        if ( get_post_type( $post_id ) === 'psych_scale' ) {
            global $wpdb;
            $wpdb->delete( $wpdb->prefix . self::TABLE_NAME, array( 'post_id' => $post_id ), array( '%d' ) );
            delete_transient( 'naboo_search_filters_cache' );
        }
    }
}
