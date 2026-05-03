## Security Vulnerability Learnings

**Vulnerability:** SQL Injection in Advanced Search Statistics (`includes/public/class-advanced-search.php`)
**Impact:** HIGH. Direct user input interpolation into a SQL query could lead to unauthenticated SQL injection attacks, exposing the database or allowing arbitrary code execution.
**Fix:** Removed direct variable interpolation and used `$wpdb->prepare` with the `%i` placeholder to properly escape identifiers (both table names and column names).
**Important Context:** The `%i` placeholder for identifiers was introduced in WordPress 6.2. Therefore, when utilizing this feature to resolve SQL injection, the plugin's "Requires at least" header in both the main plugin file and `README.txt` must be bumped to at least 6.2.
