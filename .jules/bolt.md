## 2024-05-24 - Missing no_found_rows in WP_Query
**Learning:** Found several `WP_Query` instances that fetch exactly ONE post using `posts_per_page => 1` but forget to include `no_found_rows => true`. This is a classic WordPress bottleneck as WP_Query defaults to executing `SQL_CALC_FOUND_ROWS` to calculate pagination, which is highly inefficient when only fetching 1 record and when pagination isn't needed.
**Action:** When creating or modifying single-post `WP_Query` lookups or unpaginated lists, aggressively add `'no_found_rows' => true`.

## 2024-05-24 - N+1 Queries in WP_Query Loops
**Learning:** Found an N+1 query issue in a `WP_Query` loop fetching post metadata (`get_post_meta`) and terms (`wp_get_post_terms`) inside a `foreach` loop for the retrieved posts. WordPress caching can be highly inefficient when data is lazily loaded individually within a loop because it executes separate database queries.
**Action:** When iterating over a list of posts from `WP_Query` to get metadata or terms, preemptively collect all the post IDs using `wp_list_pluck` and explicitly warm the cache using `update_meta_cache( 'post', $post_ids )` and `update_object_term_cache( $post_ids, 'post_type' )`. Include all necessary IDs (including related items like the base post being compared against) in the array using `array_merge`.
