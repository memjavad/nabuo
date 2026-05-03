# CRITICAL learnings for Bolt:

* **Caching complex computations:** When optimizing heavy operations that produce results which do not change frequently (like related scales based on term taxonomy queries or similar text matches), wrapping the logic with `wp_cache_get` and `wp_cache_set` is a simple yet powerful performance improvement.
* **Testing Global Constants in Namespaces:** When working in a namespaced PHP file in WordPress, using global constants like `HOUR_IN_SECONDS` may fail with `Undefined constant "Namespace\HOUR_IN_SECONDS"`. Use their raw integer counterparts (e.g., `3600`) or reference them globally `\HOUR_IN_SECONDS` if the environment guarantees they are defined.
* **Managing Sandbox Artifacts:** Ensure test scripts and patch scripts are properly deleted before committing to avoid polluting the PR with unrelated, temporary files.
* **Testing Missing Dependencies:** When `phpunit` is not installed or the downloaded `.phar` is broken, rely on the project's custom test runner (e.g., `php tests/run-tests.php`) to ensure CI compatibility.

# CRITICAL learnings for Bolt:

* **Caching complex computations:** When optimizing heavy operations that produce results which do not change frequently (like related scales based on term taxonomy queries or similar text matches), wrapping the logic with `wp_cache_get` and `wp_cache_set` is a simple yet powerful performance improvement.
* **Testing Global Constants in Namespaces:** When working in a namespaced PHP file in WordPress, using global constants like `HOUR_IN_SECONDS` may fail with `Undefined constant "Namespace\HOUR_IN_SECONDS"`. Use their raw integer counterparts (e.g., `3600`) or reference them globally `\HOUR_IN_SECONDS` if the environment guarantees they are defined.
* **Managing Sandbox Artifacts:** Ensure test scripts and patch scripts are properly deleted before committing to avoid polluting the PR with unrelated, temporary files.
* **Testing Missing Dependencies:** When `phpunit` is not installed or the downloaded `.phar` is broken, rely on the project's custom test runner (e.g., `php tests/run-tests.php`) to ensure CI compatibility.
