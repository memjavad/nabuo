CRITICAL LEARNINGS:
- In WordPress database maintenance code (`$wpdb`), `OPTIMIZE TABLE` commands can be batched directly by comma-separating table names in a single query (e.g. `OPTIMIZE TABLE table1, table2`) rather than looping `OPTIMIZE TABLE table1` query execution inside a loop.
- It provides a significant query reduction and improves time performance.
- When incrementing the plugin version during a production code modification, do not forget to increment the version in both the main plugin file (`naboodatabase.php`) and the `README.txt` file (Stable tag & Changelog).
- Use `php -l` and a standalone test/benchmark script to ensure no syntax errors and accurate measurement.
- If unable to run a PHP Unit framework natively, download `phpunit.phar` and make sure it has executable privileges. However, test setups might fail if dependencies like `mariadb-client`/`mariadb-server` are missing.
