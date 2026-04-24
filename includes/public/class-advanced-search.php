<?php
/**
 * Advanced Academic Search
 *
 * @package ArabPsychology\NabooDatabase\Public
 */

namespace ArabPsychology\NabooDatabase\Public;

/**
 * Advanced_Search class – REST API endpoints for the academic search engine.
 */
class Advanced_Search {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/* ── REST endpoint registration ─────────────────────────────────────── */

	public function register_endpoints() {
		register_rest_route( 'apa/v1', '/search/advanced', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'advanced_search' ),
			'permission_callback' => '__return_true',
		) );

		register_rest_route( 'apa/v1', '/search/filters', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_search_filters' ),
			'permission_callback' => '__return_true',
		) );

		register_rest_route( 'apa/v1', '/search/suggestions', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_search_suggestions' ),
			'permission_callback' => '__return_true',
		) );

		register_rest_route( 'apa/v1', '/search/rebuild-index', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'rebuild_index_endpoint' ),
			'permission_callback' => function() { return current_user_can( 'manage_options' ); },
		) );

		register_rest_route( 'apa/v1', '/scales/(?P<id>\d+)/abstract', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_scale_abstract' ),
			'permission_callback' => '__return_true',
		) );
	}

	/* ── Main search endpoint ────────────────────────────────────────────── */

	/**
	 * GET /apa/v1/search/advanced
	 *
	 * Query params:
	 *   rows[]       – JSON encoded array of {term, field, operator}
	 *   keyword      – fallback simple keyword (used if no rows)
	 *   categories[] – term IDs
	 *   authors[]    – term IDs
	 *   year_from    – int
	 *   year_to      – int
	 *   language     – string
	 *   test_type    – string
	 *   format       – string
	 *   age_group    – string
	 *   population   – string (LIKE match)
	 *   items_min    – int
	 *   items_max    – int
	 *   reliability_min – float 0-1
	 *   validity_min    – float 0-1
	 *   has_file     – '1' | ''
	 *   sort         – relevance|date|year|views|items|title_asc|title_desc
	 *   page         – int
	 *   per_page     – int
	 */
	public function advanced_search( $request ) {
		global $wpdb;

		/* ── Sanitize simple params ── */
		$keyword         = sanitize_text_field( $request->get_param( 'keyword' ) ?? '' );
		$categories      = array_map( 'intval', (array) ( $request->get_param( 'categories' ) ?? array() ) );
		$authors         = array_map( 'intval', (array) ( $request->get_param( 'authors' ) ?? array() ) );
		$year_from       = intval( $request->get_param( 'year_from' ) ?? 0 );
		$year_to         = intval( $request->get_param( 'year_to' ) ?? 0 );
		$language        = sanitize_text_field( $request->get_param( 'language' ) ?? '' );
		$test_type       = sanitize_text_field( $request->get_param( 'test_type' ) ?? '' );
		$format          = sanitize_text_field( $request->get_param( 'format' ) ?? '' );
		$age_group       = sanitize_text_field( $request->get_param( 'age_group' ) ?? '' );
		$methodology     = sanitize_text_field( $request->get_param( 'methodology' ) ?? '' );
		$population      = sanitize_text_field( $request->get_param( 'population' ) ?? '' );
		$items_min       = intval( $request->get_param( 'items_min' ) ?? 0 );
		$items_max       = intval( $request->get_param( 'items_max' ) ?? 0 );
		$has_file        = $request->get_param( 'has_file' ) === '1';
		$sort            = sanitize_text_field( $request->get_param( 'sort' ) ?? 'date' );
		$page            = max( 1, intval( $request->get_param( 'page' ) ?? 1 ) );
		$per_page        = min( 50, max( 5, intval( $request->get_param( 'per_page' ) ?? 20 ) ) );

		/* ── Boolean rows ── */
		$rows_raw = $request->get_param( 'rows' );
		$rows     = array();
		if ( is_array( $rows_raw ) ) {
			foreach ( $rows_raw as $row ) {
				if ( ! is_array( $row ) ) {
					$row = json_decode( $row, true );
				}
				$term     = sanitize_text_field( $row['term'] ?? '' );
				$field    = sanitize_key( $row['field'] ?? 'any' );
				$operator = in_array( strtoupper( $row['operator'] ?? 'AND' ), array( 'AND', 'OR', 'NOT' ), true )
					? strtoupper( $row['operator'] )
					: 'AND';
				if ( $term !== '' ) {
					$rows[] = compact( 'term', 'field', 'operator' );
				}
			}
		}

		$table_name = $wpdb->prefix . \ArabPsychology\NabooDatabase\Admin\Database_Indexer::TABLE_NAME;

		/* ── Auto-populate index if empty (first-run scenario) ── */
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$index_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
		if ( $index_count === 0 ) {
			$this->bulk_sync_index();
		}

		/* ── Query Construction ── */

		$where_clauses = array();
		$sql_args      = array();

		/* ── Keyword / Boolean Search ── */
		// We'll map the allowed 'field' properties to columns in our flat index.
		// Fallback for 'any' will hit the FULLTEXT index.
		$allowed_fields = array(
			'title'      => 'title',
			'construct'  => 'construct',
			'purpose'    => 'purpose',
			'abstract'   => 'abstract',
			'population' => 'population',
		);

		// Handle simple keyword search (no rows, just the main search bar)
		if ( empty( $rows ) && ! empty( $keyword ) ) {
			// Boolean mode FULLTEXT search across primary fields
			$where_clauses[] = "MATCH (title, abstract, purpose, construct, population) AGAINST (%s IN BOOLEAN MODE)";
			$sql_args[]      = $this->format_boolean_keyword( $keyword );
		}
		
		// Handle boolean rows from Advanced Search
		if ( ! empty( $rows ) ) {
			$boolean_expressions = array();
			
			// Map the UI rows into SQL grouping
			foreach ( $rows as $i => $row ) {
				$term = trim( $row['term'] );
				if ( empty( $term ) ) continue;

				$db_field = $allowed_fields[ $row['field'] ] ?? 'any';
				$operator = ( $i === 0 ) ? '' : ( $row['operator'] === 'OR' ? 'OR' : 'AND' ); // 'NOT' is handled within boolean fulltext or via NOT LIKE
				$is_not   = ( $row['operator'] === 'NOT' );

				if ( $db_field === 'any' ) {
					$match_sql = "MATCH (title, abstract, purpose, construct, population) AGAINST (%s IN BOOLEAN MODE)";
					$match_arg = $this->format_boolean_keyword( $term, $is_not );
				} else {
					// Specific column
					$match_sql = "{$db_field} " . ( $is_not ? "NOT " : "" ) . "LIKE %s";
					$match_arg = '%' . $wpdb->esc_like( $term ) . '%';
				}

				if ( $operator ) {
					$boolean_expressions[] = "{$operator} {$match_sql}";
				} else {
					$boolean_expressions[] = $match_sql;
				}
				$sql_args[] = $match_arg;
			}
			
			if ( ! empty( $boolean_expressions ) ) {
				$where_clauses[] = "( " . implode( " ", $boolean_expressions ) . " )";
			}
		}

		/* ── Taxonomy Filters (Stored as comma-separated JSON-like strings in index) ── */
		// To match efficiently without JSON functions (for older MySQL), we use FIND_IN_SET mapped with ANDs
		if ( ! empty( $categories ) ) {
			$cat_clauses = array();
			foreach ( $categories as $cat_id ) {
				$cat_clauses[] = "FIND_IN_SET(%d, category_ids) > 0";
				$sql_args[]    = $cat_id;
			}
			$where_clauses[] = "( " . implode( " OR ", $cat_clauses ) . " )";
		}

		if ( ! empty( $authors ) ) {
			$auth_clauses = array();
			foreach ( $authors as $auth_id ) {
				$auth_clauses[] = "FIND_IN_SET(%d, author_ids) > 0";
				$sql_args[]    = $auth_id;
			}
			$where_clauses[] = "( " . implode( " OR ", $auth_clauses ) . " )";
		}

		if ( ! empty( $age_group ) ) {
			$age_clauses = array();
			foreach ( (array) $age_group as $age_id ) {
				$age_clauses[] = "FIND_IN_SET(%d, age_group_ids) > 0";
				$sql_args[]    = $age_id;
			}
			$where_clauses[] = "( " . implode( " OR ", $age_clauses ) . " )";
		}

		/* ── Meta Column Filters ── */
		if ( $year_from && $year_to ) {
			$where_clauses[] = "year BETWEEN %d AND %d";
			$sql_args[] = $year_from;
			$sql_args[] = $year_to;
		} elseif ( $year_from ) {
			$where_clauses[] = "year >= %d";
			$sql_args[] = $year_from;
		} elseif ( $year_to ) {
			$where_clauses[] = "year <= %d";
			$sql_args[] = $year_to;
		}

		if ( $items_min && $items_max ) {
			$where_clauses[] = "items BETWEEN %d AND %d";
			$sql_args[] = $items_min;
			$sql_args[] = $items_max;
		} elseif ( $items_min ) {
			$where_clauses[] = "items >= %d";
			$sql_args[] = $items_min;
		} elseif ( $items_max ) {
			$where_clauses[] = "items <= %d";
			$sql_args[] = $items_max;
		}

		$exact_meta_fields = array(
			'language'    => $language,
			'test_type'   => $test_type,
			'format'      => $format,
			'methodology' => $methodology,
		);
		foreach ( $exact_meta_fields as $col => $val ) {
			if ( $val !== '' ) {
				$where_clauses[] = "{$col} = %s";
				$sql_args[] = $val;
			}
		}

		if ( $population !== '' ) {
			$where_clauses[] = "population LIKE %s";
			$sql_args[] = '%' . $wpdb->esc_like( $population ) . '%';
		}

		if ( $has_file ) {
			$where_clauses[] = "has_file = 1";
		}

		/* ── Pagination & Sorting ── */
		$order_by_sql = "post_id DESC"; // Default
		$relevance_col = "";

		if ( $sort === 'relevance' && ! empty( $keyword ) && empty( $rows ) ) {
			$relevance_col = ", MATCH (title, abstract, purpose, construct, population) AGAINST ('" . esc_sql( $this->format_boolean_keyword($keyword) ) . "' IN BOOLEAN MODE) AS score";
			$order_by_sql = "score DESC, post_id DESC";
		} else {
			switch ( $sort ) {
				case 'views':
					// Since views change constantly, we left it out of the index table. We join postmeta for sorting.
					$join_views = "LEFT JOIN {$wpdb->postmeta} pm_views ON i.post_id = pm_views.post_id AND pm_views.meta_key = '_naboo_view_count'";
					$order_by_sql = "CAST(IFNULL(pm_views.meta_value, 0) AS UNSIGNED) DESC, i.post_id DESC";
					break;
				case 'reliability_desc':
					$order_by_sql = "reliability DESC, post_id DESC";
					break;
				case 'validity_desc':
					$order_by_sql = "validity DESC, post_id DESC";
					break;
				case 'year_desc':
					$order_by_sql = "year DESC, post_id DESC";
					break;
				case 'year_asc':
					$order_by_sql = "year ASC, post_id ASC";
					break;
				case 'items':
					$order_by_sql = "items ASC, post_id ASC";
					break;
				case 'title_asc':
					$order_by_sql = "title ASC";
					break;
				case 'title_desc':
					$order_by_sql = "title DESC";
					break;
				case 'oldest':
				case 'date':
				default:
					// Instead of joining posts table just for date, we use post_id as a proxy for date since they are sequential.
					$dir = ( $sort === 'oldest' ) ? 'ASC' : 'DESC';
					$order_by_sql = "post_id {$dir}";
					break;
			}
		}

		// Build WHERE String
		$where_sql = "";
		if ( ! empty( $where_clauses ) ) {
			$where_sql = "WHERE " . implode( " AND ", $where_clauses );
		}

		// Count Query
		$count_sql = "SELECT COUNT(i.post_id) FROM {$table_name} i {$where_sql}";
		if ( ! empty( $sql_args ) ) {
			$count_sql = $wpdb->prepare( $count_sql, ...$sql_args );
		}
		$total_posts = (int) $wpdb->get_var( $count_sql );
		$total_pages = ceil( $total_posts / $per_page );

		// Data Query
		$offset = ( $page - 1 ) * $per_page;
		$join_views = $join_views ?? ""; 
		
		$data_sql = "SELECT i.post_id {$relevance_col} FROM {$table_name} i {$join_views} {$where_sql} ORDER BY {$order_by_sql} LIMIT %d OFFSET %d";
		
		// Add LIMIT/OFFSET args
		$sql_args[] = $per_page;
		$sql_args[] = $offset;

		$data_sql = $wpdb->prepare( $data_sql, ...$sql_args );
		
		$post_ids = $wpdb->get_col( $data_sql );

		/* ── Formatting Results ── */
		$results = array();
		if ( ! empty( $post_ids ) ) {
			// Now we hit the standard WP cache to construct the UI
			// This is fast because we're only querying exactly 20 posts max
			foreach ( $post_ids as $pid ) {
				$post = get_post( $pid );
				if ( $post ) {
					$results[] = $this->format_scale_result( $post, $keyword );
				}
			}
		}

		return rest_ensure_response( array(
			'success'     => true,
			'data'        => $results,
			'total'       => $total_posts,
			'page'        => $page,
			'per_page'    => $per_page,
			'total_pages' => $total_pages,
		) );
	}

	/**
	 * Helper: formats a standard search string for MySQL BOOLEAN MODE.
	 */
	private function format_boolean_keyword( $keyword, $force_exclude = false ) {
		$words = array_filter( explode( ' ', mb_strtolower( trim( $keyword ) ) ) );
		$formatted = array();
		foreach ( $words as $word ) {
			// Strip existing operators
			$clean = str_replace( array('+', '-', '*', '>', '<', '(', ')', '~', '"'), '', $word );
			if ( strlen( $clean ) > 2 ) {
				// Append wildcard for partial matches
				$prefix = $force_exclude ? '-' : '+';
				$formatted[] = "{$prefix}{$clean}*";
			}
		}
		return implode( ' ', $formatted );
	}

	/* ── Bulk index sync helper ─────────────────────────────────────────── */

	/**
	 * Sync all published psych_scale posts into the flat search index.
	 * Uses batching to stay within PHP memory limits.
	 *
	 * @param int $batch_size Posts per batch.
	 * @return int Total synced.
	 */
	private function bulk_sync_index( $batch_size = 200 ) {
		global $wpdb;
		$total  = 0;
		$offset = 0;
		do {
			// Direct SQL — bypasses get_posts() filters that can cap results
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$ids = $wpdb->get_col( $wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'psych_scale' AND post_status = 'publish' ORDER BY ID ASC LIMIT %d OFFSET %d",
				$batch_size,
				$offset
			) );
			foreach ( $ids as $pid ) {
				\ArabPsychology\NabooDatabase\Admin\Database_Indexer::sync_post( (int) $pid );
				$total++;
			}
			$offset += $batch_size;
		} while ( count( $ids ) === $batch_size );
		return $total;
	}

	/**
	 * POST /apa/v1/search/rebuild-index  (admin only)
	 */
	public function rebuild_index_endpoint( $request ) {
		$synced = $this->bulk_sync_index();
		delete_transient( 'naboo_search_filters_cache' );
		return rest_ensure_response( array( 'success' => true, 'synced' => $synced ) );
	}

	/* ── Filter options endpoint ─────────────────────────────────────────── */

	public function get_search_filters( $request ) {
		global $wpdb;

		// 1. Check Transients Cache First
		$cached = get_transient( 'naboo_search_filters_cache' );
		if ( $cached !== false ) {
			return rest_ensure_response( $cached );
		}

		$table_name = $wpdb->prefix . \ArabPsychology\NabooDatabase\Admin\Database_Indexer::TABLE_NAME;

		// Taxonomies still come from standard terms because they have counts globally,
		// but ideally we only show terms actually in the index. For simplicity, we stick to WP get_terms here.
		$categories = get_terms( array( 'taxonomy' => 'scale_category', 'hide_empty' => true ) );
		$authors    = get_terms( array( 'taxonomy' => 'scale_author',   'hide_empty' => true ) );
		$age_groups = get_terms( array( 'taxonomy' => 'scale_age_group', 'hide_empty' => true ) );

		// Aggregates hit the new flat table 
		$stats = $wpdb->get_row( "
			SELECT 
				MIN(year) as min_year, MAX(year) as max_year,
				MIN(items) as min_items, MAX(items) as max_items
			FROM {$table_name}
		" );

		// Distinct enumerated values for dropdown filters with counts
		$get_distinct = function( $column ) use ( $wpdb, $table_name ) {
			// Using flat table avoids joining posts to postmeta
			$results = $wpdb->get_results( "
				SELECT {$column} as value, COUNT(*) as cc
				FROM {$table_name}
				WHERE {$column} != '' AND {$column} IS NOT NULL
				GROUP BY {$column}
			" );

			// These columns might still have comma-separated values internally (e.g. Test Types)
			$counts = array();
			foreach ( $results as $row ) {
				$parts = array_map( 'trim', explode( ',', $row->value ) );
				$parts = array_unique( array_filter( $parts ) );
				foreach ( $parts as $part ) {
					if ( ! isset( $counts[ $part ] ) ) {
						$counts[ $part ] = 0;
					}
					$counts[ $part ] += (int) $row->cc;
				}
			}

			ksort( $counts );

			$formatted = array();
			foreach ( $counts as $val => $count ) {
				$formatted[] = array(
					'id'    => $val,
					'name'  => $val,
					'count' => (int) $count,
				);
			}
			return $formatted;
		};

		$response = array(
			'success' => true,
			'filters' => array(
				'categories'  => array_values( array_map( fn( $c ) => array( 'id' => $c->term_id, 'name' => $c->name, 'count' => $c->count ), is_wp_error( $categories ) ? array() : $categories ) ),
				'authors'     => array_values( array_map( fn( $a ) => array( 'id' => $a->term_id, 'name' => $a->name, 'count' => $a->count ), is_wp_error( $authors ) ? array() : $authors ) ),
				'year'        => array( 'min' => intval( $stats->min_year ?? 1970 ), 'max' => intval( $stats->max_year ?? (int) gmdate( 'Y' ) ) ),
				'items'       => array( 'min' => intval( $stats->min_items ?? 1 ),  'max' => intval( $stats->max_items ?? 200 ) ),
				'languages'   => array_values( $get_distinct( 'language' ) ),
				'test_types'  => array_values( $get_distinct( 'test_type' ) ),
				'formats'     => array_values( $get_distinct( 'format' ) ),
				'age_groups'  => array_values( array_map( fn( $g ) => array( 'id' => $g->term_id, 'name' => $g->name, 'count' => $g->count ), is_wp_error( $age_groups ) ? array() : $age_groups ) ),
				'methodologies' => array_values( $get_distinct( 'methodology' ) ),
			),
		);

		// Cache for 24 hours (cleared automatically by Database_Indexer on post save/delete)
		set_transient( 'naboo_search_filters_cache', $response, DAY_IN_SECONDS );

		return rest_ensure_response( $response );
	}

	/* ── Autocomplete suggestions ────────────────────────────────────────── */

	public function get_search_suggestions( $request ) {
		global $wpdb;

		$search = sanitize_text_field( $request->get_param( 'search' ) ?? '' );

		if ( strlen( $search ) < 2 ) {
			return rest_ensure_response( array( 'success' => true, 'suggestions' => array() ) );
		}

		$table_name = $wpdb->prefix . \ArabPsychology\NabooDatabase\Admin\Database_Indexer::TABLE_NAME;
		$like = '%' . $wpdb->esc_like( $search ) . '%';

		// We can get titles, constructs, and populations straight from the flat index
		$suggestions = $wpdb->get_col( $wpdb->prepare( "
			(SELECT title as val FROM {$table_name} WHERE title LIKE %s LIMIT 5)
			UNION
			(SELECT construct as val FROM {$table_name} WHERE construct LIKE %s LIMIT 5)
			UNION
			(SELECT population as val FROM {$table_name} WHERE population LIKE %s LIMIT 5)
		", $like, $like, $like ) );

		// Term names are still in taxonomy, but fast enough
		$term_names = $wpdb->get_col( $wpdb->prepare(
			"SELECT DISTINCT t.name FROM {$wpdb->terms} t
			 INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = t.term_id
			 WHERE tt.taxonomy IN ('scale_category','scale_author') AND t.name LIKE %s
			 LIMIT 5",
			$like
		) );

		$all = array_unique( array_merge( $suggestions, $term_names ) );

		return rest_ensure_response( array( 'success' => true, 'suggestions' => array_slice( array_values( $all ), 0, 15 ) ) );
	}

	/* ── Result formatter ────────────────────────────────────────────────── */

	private function format_scale_result( $post, $keyword = '' ) {
		$id          = $post->ID;
		$categories  = wp_get_post_terms( $id, 'scale_category', array( 'fields' => 'all' ) );
		$auth_terms  = wp_get_post_terms( $id, 'scale_author',   array( 'fields' => 'names' ) );
		$file_id     = get_post_meta( $id, '_naboo_scale_file',        true );

		return array(
			'id'          => $id,
			'title'       => $post->post_title,
			'url'         => get_permalink( $id ),
			'excerpt'     => wp_trim_words( $post->post_excerpt ?: wp_strip_all_tags( $post->post_content ), 30 ),
			'categories'  => array_map( fn( $c ) => array( 'id' => $c->term_id, 'name' => $c->name ), is_wp_error( $categories ) ? array() : $categories ),
			'authors'     => is_wp_error( $auth_terms ) ? array() : $auth_terms,
			'year'        => intval( get_post_meta( $id, '_naboo_scale_year',        true ) ),
			'items'       => intval( get_post_meta( $id, '_naboo_scale_items',       true ) ),
			'language'    => get_post_meta( $id, '_naboo_scale_language',    true ),
			'reliability' => get_post_meta( $id, '_naboo_scale_reliability', true ),
			'validity'    => get_post_meta( $id, '_naboo_scale_validity',    true ),
			'population'  => get_post_meta( $id, '_naboo_scale_population',  true ),
			'age_groups'  => is_wp_error( wp_get_post_terms( $id, 'scale_age_group', array( 'fields' => 'names' ) ) ) ? array() : wp_get_post_terms( $id, 'scale_age_group', array( 'fields' => 'names' ) ),
			'test_type'   => get_post_meta( $id, '_naboo_scale_test_type',   true ),
			'construct'   => get_post_meta( $id, '_naboo_scale_construct',   true ),
			'views'       => intval( get_post_meta( $id, '_naboo_view_count',        true ) ),
			'has_file'    => ! empty( $file_id ),
			'date'        => $post->post_date,
		);
	}

	/* ── Asset enqueue ───────────────────────────────────────────────────── */

	public function enqueue_scripts() {
		wp_enqueue_script(
			$this->plugin_name . '-advanced-search',
			plugins_url( 'js/advanced-search.js', __FILE__ ),
			array( 'jquery' ),
			$this->version,
			true
		);

		wp_enqueue_style(
			$this->plugin_name . '-advanced-search',
			plugins_url( 'css/advanced-search.css', __FILE__ ),
			array(),
			$this->version
		);

		// Note: nabooAdvancedSearch localization is handled in class-frontend.php::enqueue_scripts()
		// to ensure it is visible to the Asset Consolidator at wp_enqueue_scripts priority 999.
	}


	/**
	 * GET /apa/v1/scales/{id}/abstract
	 */
	public function get_scale_abstract( $request ) {
		$id = intval( $request['id'] );
		$abstract = get_post_meta( $id, '_naboo_scale_abstract', true );
		
		if ( empty( $abstract ) ) {
			$post = get_post( $id );
			if ( $post ) {
				$abstract = wp_trim_words( $post->post_content, 100 );
			}
		}

		return rest_ensure_response( array( 
			'success'  => true, 
			'abstract' => wpautop( $abstract ) 
		) );
	}
}
