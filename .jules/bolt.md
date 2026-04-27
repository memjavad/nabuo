## 2024-05-24 - Missing no_found_rows in WP_Query
**Learning:** Found several `WP_Query` instances that fetch exactly ONE post using `posts_per_page => 1` but forget to include `no_found_rows => true`. This is a classic WordPress bottleneck as WP_Query defaults to executing `SQL_CALC_FOUND_ROWS` to calculate pagination, which is highly inefficient when only fetching 1 record and when pagination isn't needed.
**Action:** When creating or modifying single-post `WP_Query` lookups or unpaginated lists, aggressively add `'no_found_rows' => true`.
## 2024-05-27 - N+1 Query in Popularity Analytics
**Learning:** The codebase had a massive N+1 query bottleneck in `class-scale-popularity-analytics.php` where it looped through all taxonomy categories, then executed a `get_posts` and `$wpdb` aggregation query inside the loop. This can grow exponentially slow as the number of categories increases.
**Action:** Always refactor iterative database calls (N+1 queries) inside loops into a single unified SQL query using `JOIN`s (e.g., joining `wp_terms`, `wp_term_taxonomy`, `wp_posts`, and custom tables) and aggregations (`SUM`, `COUNT`) with `GROUP BY term_id`.
