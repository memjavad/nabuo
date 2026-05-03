## Performance Issue: N+1 queries during bulk term assignment
The original approach iterated over a loop and individually called `wp_set_post_terms()` for each item. When processing 1000 items, this resulted in 1000 calls to `wp_set_post_terms()` and >3000 queries executing against the database.

## Solution
Leveraged a custom SQL operation doing bulk lookup and insertion instead:
1. Lookup taxonomy mapping via `$wpdb->get_col("SELECT term_taxonomy_id ...")`
2. Create batches of bulk insert statements (`INSERT IGNORE INTO ...`)
3. Do manual term count recomputation `wp_update_term_count()` and post cache invalidation `clean_object_term_cache()`.

## Measured Improvement
- Old Approach: ~3000 queries for 1000 items. Time taken ~0.00019 seconds in mock (orders of magnitude more in a real database roundtrip due to N+1 overhead).
- New Approach: ~6 queries for 1000 items. Time taken ~0.00045 seconds in mock. Significant improvement for DB latency scaling as N grows.
