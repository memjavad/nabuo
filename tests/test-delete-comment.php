<?php
ini_set('assert.exception', 1);

define('ABSPATH', __DIR__ . '/');

$mock_current_user_id = 10;
if (!function_exists('get_current_user_id')) {
    function get_current_user_id() {
        global $mock_current_user_id;
        return $mock_current_user_id;
    }
}

if (!function_exists('rest_ensure_response')) {
    function rest_ensure_response($response, $status = 200) {
        $res = new stdClass();
        $res->data = $response;
        $res->status = $status;
        return $res;
    }
}

if (!class_exists('WP_REST_Request')) {
    class WP_REST_Request {
        private $params = [];
        public function set_param($key, $value) {
            $this->params[$key] = $value;
        }
        public function get_param($key) {
            return $this->params[$key] ?? null;
        }
    }
}

global $wpdb;
$wpdb = new class {
    public $prefix = 'wp_';
    public $mock_get_row_result = null;
    public $deleted_tables = [];

    public function prepare($query, ...$args) {
        return $query; // naive mock
    }
    public function get_row($query) {
        return $this->mock_get_row_result;
    }
    public function delete($table, $where, $where_format = null) {
        $this->deleted_tables[] = [
            'table' => $table,
            'where' => $where,
        ];
        return 1;
    }
};

require_once __DIR__ . '/../includes/public/class-comments.php';
use ArabPsychology\NabooDatabase\Public\Comments;

$comments = new Comments('naboodatabase', '1.0.0');

echo "Running tests for delete_comment API endpoint...\n";

// Test 1: Comment not found
$request = new WP_REST_Request();
$request->set_param('id', 99);
$wpdb->mock_get_row_result = null;

$response = $comments->delete_comment($request);
assert($response->status === 403, "Expected 403 when comment not found");
assert($response->data['success'] === false, "Expected success false");
echo "✅ Test 1 Passed: Comment not found returns 403\n";

// Test 2: Permission denied (different user)
$request = new WP_REST_Request();
$request->set_param('id', 1);
$wpdb->mock_get_row_result = (object)[ 'id' => 1, 'user_id' => 999 ]; // current user is 10
$response = $comments->delete_comment($request);
assert($response->status === 403, "Expected 403 when permission denied");
assert($response->data['success'] === false, "Expected success false");
echo "✅ Test 2 Passed: Permission denied returns 403\n";

// Test 3: Successful deletion
$request = new WP_REST_Request();
$request->set_param('id', 1);
$wpdb->mock_get_row_result = (object)[ 'id' => 1, 'user_id' => 10 ]; // current user is 10
$wpdb->deleted_tables = []; // reset
$response = $comments->delete_comment($request);

assert($response->status === 200, "Expected 200 on success");
assert($response->data['success'] === true, "Expected success true");
assert(count($wpdb->deleted_tables) === 2, "Expected 2 delete queries");
assert($wpdb->deleted_tables[0]['table'] === 'wp_naboo_comments', "Expected wp_naboo_comments table");
assert($wpdb->deleted_tables[0]['where']['id'] === 1, "Expected id to be 1 in first query");
assert($wpdb->deleted_tables[1]['where']['parent_id'] === 1, "Expected parent_id to be 1 in second query");
echo "✅ Test 3 Passed: Successful deletion\n";

echo "All tests passed successfully.\n";
