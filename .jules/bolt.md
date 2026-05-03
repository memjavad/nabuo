CRITICAL LEARNINGS - PERFORMANCE

*   **N+1 Queries:** When looping through results from a query (like `WP_Query`), avoid making database calls inside the loop. Fetch all required data in a single bulk query beforehand and map it in memory.
*   **Prepared Statements with IN():** To execute an `IN` query safely in WordPress with `$wpdb->prepare()`, build a string of placeholders (`%d`) equal to the array length, construct the SQL with the placeholders, and pass the array of values directly as the second argument.
*   **`no_found_rows`:** When using `WP_Query` where pagination isn't needed (like returning a fixed number of search results), explicitly set `'no_found_rows' => true` to bypass the slow `SQL_CALC_FOUND_ROWS` query.
*   **Pre-warming Caches:** Always proactively call `update_meta_cache('post', $post_ids)` and `update_object_term_cache($post_ids, $post_type)` when iterating over a custom array of posts. This stops WordPress from making N+1 queries when accessing things like the post thumbnail URL.
