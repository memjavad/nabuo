## 2024-04-24 - Bypass SQL_CALC_FOUND_ROWS in unpaginated queries
**Learning:** In WordPress, `WP_Query` calculates the total number of found rows (`SQL_CALC_FOUND_ROWS`) by default when `posts_per_page` is set to any number (even 1 or 5), which causes unnecessary performance overhead when we don't need pagination counts (such as single lookups, ajax lists, or top items).
**Action:** Always include `'no_found_rows' => true` in `WP_Query` arguments when fetching single posts or building unpaginated lists.
