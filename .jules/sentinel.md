## 🔒 Security Fix Log: SQL Injection in Ratings Extraction

**File**: `includes/public/class-ratings.php`
**Vulnerability Type**: SQL Injection

### Description
The plugin directly interpolated `$table_name` into `$wpdb->prepare()` calls for several queries in `includes/public/class-ratings.php`. For example:
`$wpdb->prepare( "SELECT * FROM $table_name WHERE status = 'approved' ORDER BY created_at DESC LIMIT 10" );`
Directly using variables like `$table_name` instead of properly using placeholders allows potential SQL injection if the prefix is ever controllable or poisoned, but it also violates proper WordPress parameterization conventions which causes warnings.

### Fix
Refactored the SQL queries to use the `%i` placeholder (introduced in WP 6.2 for identifiers). The code now looks like:
`$wpdb->prepare( "SELECT * FROM %i WHERE status = 'approved' ORDER BY created_at DESC LIMIT 10", $table_name );`
Updated the `Requires at least` version to 6.2 in both `naboodatabase.php` and `README.txt` to safely support `%i`.
