## 2024-05-24 - Optimizing WP_Query Unpaginated Queries
**Learning:** `WP_Query` defaults to calculating the total found rows for pagination via `SQL_CALC_FOUND_ROWS` or a secondary `COUNT` query. This happens even when fetching a single post (`posts_per_page => 1`) or a small unpaginated list, leading to unnecessary full-table scans.
**Action:** Always include `'no_found_rows' => true` in the arguments for `WP_Query` or `get_posts` when fetching a fixed number of records where pagination is not required (e.g., getting the 'next' item, or top 5 recent items).
