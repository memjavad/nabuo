## 2024-05-24 - Missing no_found_rows in WP_Query
**Learning:** Found several `WP_Query` instances that fetch exactly ONE post using `posts_per_page => 1` but forget to include `no_found_rows => true`. This is a classic WordPress bottleneck as WP_Query defaults to executing `SQL_CALC_FOUND_ROWS` to calculate pagination, which is highly inefficient when only fetching 1 record and when pagination isn't needed.
**Action:** When creating or modifying single-post `WP_Query` lookups or unpaginated lists, aggressively add `'no_found_rows' => true`.

## 2024-06-25 - N+1 Queries in REST API Category/Term Fetching
**Learning:** Found N+1 queries when fetching terms for categories via `get_the_terms` in REST API endpoints. Although `get_the_terms` uses the object cache, fetching individual terms per post in an API loop over 100+ items is incredibly inefficient and triggers significant object cache/database overhead when missing. Bulk pre-fetching terms via a single aggregated `$wpdb` query for all `$post_ids` and storing them in memory array before formatting significantly increases performance.
**Action:** When working with collections and REST API arrays, always identify iterative term fetching, gather the base post IDs via `wp_list_pluck`, and replace N+1 queries with a single batch `IN` SQL statement mapped to arrays before mapping back to the response output.
