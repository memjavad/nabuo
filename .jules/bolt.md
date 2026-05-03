# Bolt: Performance Improvements

## CRITICAL LEARNINGS
- **N+1 Queries in Bulk Operations:** Using `wp_update_post` in a `foreach` loop for bulk operations triggers an excessive amount of queries and hooks per post, causing significant performance degradation (e.g., ~60ms for 50 posts).
- **Direct `$wpdb` UPDATE:** Replacing the loop with a single `$wpdb->query( $wpdb->prepare(...) )` using an `IN` clause drastically improves performance (e.g., dropping to ~0.03ms for 50 posts).
- **Cache Management:** When bypassing standard WordPress functions like `wp_update_post` in favor of direct SQL queries, it's crucial to manually invalidate the post cache for the affected IDs using `clean_post_cache( $post_id )` to ensure subsequent data retrievals are accurate.
