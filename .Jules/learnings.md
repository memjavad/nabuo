## PHP Test Mocking
- When writing tests for WordPress plugins without a robust framework, you often need to mock functions in both the global namespace and the plugin's namespace if they're called globally from within a namespaced file.
- Using the `$GLOBALS` array is a robust way to share state between test assertions and mocked dependencies.
- When mocking `WP_REST_Request`, make sure to mock all parameter access methods like `get_json_params()` and `get_param()`.
