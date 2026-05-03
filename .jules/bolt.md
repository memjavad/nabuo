## 2024-05-24 - Missing no_found_rows in WP_Query
**Learning:** Found several `WP_Query` instances that fetch exactly ONE post using `posts_per_page => 1` but forget to include `no_found_rows => true`. This is a classic WordPress bottleneck as WP_Query defaults to executing `SQL_CALC_FOUND_ROWS` to calculate pagination, which is highly inefficient when only fetching 1 record and when pagination isn't needed.
**Action:** When creating or modifying single-post `WP_Query` lookups or unpaginated lists, aggressively add `'no_found_rows' => true`.
## 2024-05-24 - N+1 query loops with get_posts
**Learning:** When fetching WP posts per taxonomy term, looping over terms with get_posts inside is a huge N+1 performance bottleneck.
**Action:** Resolve by aggregating with $wpdb instead of looping with get_posts, ensuring to use LEFT JOIN for optional custom tables.
