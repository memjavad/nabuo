## Testing Improvements

**Learning:** When mocking PHP functions that internally throw exceptions encoded with JSON (such as `wp_send_json_success` which `throw new \Exception(json_encode($data));`), the thrown exception's `$e->getMessage()` will output stringified JSON. Thus, assertions should check for string occurrences using `assertStringContainsString` instead of direct equality if the JSON payload structure is nested or complex (e.g. `$response['message']`).
**Action:** Use `assertStringContainsString` for exception message assertions. Also ensure test global variables like `$_POST` are reset using `tearDown()` and test properties are correctly managed via Reflection. Clean up patch script files before final commit.
