# BOLT CRITICAL LEARNINGS

## Optimization Overview
Target: N+1 query pattern in `includes/admin/class-ratings-moderation.php`

**Before:**
The code iterated over an array of statuses (pending, approved, rejected, spam) and executed a separate `SELECT COUNT(*)` database query for each status using `$wpdb->get_var`.

**After:**
Implemented a single, batched `SELECT status, COUNT(*) FROM ... GROUP BY status` query using `$wpdb->get_results`. The PHP code then iterates over the result set to populate the count array.

**Performance Impact:**
* Database queries reduced from 4 per page load to 1 per page load.
* Based on a standalone mock benchmark simulating 10k iterations, optimized time improved processing by ~15% while reducing the number of queries by 75%.
