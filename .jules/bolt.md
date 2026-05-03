# Performance Improvements

## N+1 Query in Collection Item Count

### What
Fixed an N+1 query issue in `includes/public/class-scale-collections.php` within the `get_collections` method. Replaced the loop calling `get_collection_item_count` for each collection with a single `LEFT JOIN`/`GROUP BY` equivalent query utilizing an `IN` clause.

### Why
When fetching collections, calling `COUNT(*)` for each individual collection iteratively leads to significant database overhead (N+1 queries). By retrieving all item counts in a single grouped query, we massively reduce the database load and the overall execution time.

### Measured Improvement
- **Baseline Queries:** 10100 (for 100 collections x 100 iterations)
- **Baseline Time:** ~7.05 ms
- **Optimized Queries:** 200 (for 100 collections x 100 iterations)
- **Optimized Time:** ~5.17 ms
- **Improvement:** Reduced database queries by 98% and improved execution time by ~26% in benchmark tests.
