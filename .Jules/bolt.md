## 2024-04-26 - Fix N+1 queries in data export formatting
**Learning:** We can utilize WordPress's `update_meta_cache` and `update_object_term_cache` to efficiently batch load metadata and term data for multiple posts before iterating them. When custom SQL is used (like average ratings and download counts), we can use `IN` clauses with dynamically generated `%d` placeholders for `$wpdb->prepare` to batch query data.
**Action:** When finding loops querying the database, collect the IDs first, and use caching/batching before the loop.
