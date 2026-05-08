## 2024-05-08 - SSRF via Raw cURL

**Vulnerability:** The `Batch_AI_Remote_Sync::make_raw_curl_request()` method used raw `curl_init()` and `curl_exec()` to fetch data from remote URLs provided by user input (e.g., via `$_POST['remote_url']` or `$_POST['zip_url']`). This allowed attackers to supply internal IP addresses (like `127.0.0.1` or `169.254.169.254`), bypassing network segmentation and leading to Server-Side Request Forgery (SSRF).

**Learning:** Raw `curl` implementations in PHP do not natively restrict requests to loopback or private IP ranges. When relying on user-supplied URLs, developers must either implement complex IP validation logic or leverage secure wrapper functions provided by the framework.

**Prevention:** Always use WordPress HTTP API functions, specifically `wp_safe_remote_get()` and `wp_safe_remote_post()`, which include built-in protections against SSRF by rejecting requests to local loopbacks and internal IPs. When converting from `curl`, ensure headers are properly formatted as an associative array.
