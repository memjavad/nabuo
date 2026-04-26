<?php
namespace {
    require_once __DIR__ . '/mock-wp-classes.php';
    require_once dirname(__DIR__) . '/includes/public/class-comments.php';

    $passed = 0;
    $failed = 0;

    // Mock missing global dependencies
    if (!function_exists('get_current_user_id')) {
        function get_current_user_id() {
            return $GLOBALS['mock_current_user_id'] ?? 1;
        }
    }

    if (!function_exists('wp_get_current_user')) {
        function wp_get_current_user() {
            $user = new stdClass();
            $user->display_name = 'Test User';
            $user->user_email = 'test@example.com';
            return $user;
        }
    }

    if (!function_exists('sanitize_textarea_field')) {
        function sanitize_textarea_field($str) {
            return strip_tags($str);
        }
    }

    if (!function_exists('get_transient')) {
        function get_transient($key) {
            return $GLOBALS['mock_transients'][$key] ?? false;
        }
    }

    if (!function_exists('set_transient')) {
        function set_transient($key, $value, $exp) {
            $GLOBALS['mock_transients'][$key] = $value;
            return true;
        }
    }

    if (!defined('HOUR_IN_SECONDS')) {
        define('HOUR_IN_SECONDS', 3600);
    }

    // Mock WPDB
    class Mock_WPDB_Test {
        public $prefix = 'wp_';
        public $insert_id = 123;
        public $mock_get_var_returns = 0;
        public $mock_get_row_returns = null;
        public $mock_insert_returns = true;

        public function prepare($query, ...$args) {
            return vsprintf(str_replace('%d', '%s', str_replace('%s', "'%s'", $query)), $args);
        }

        public function get_var($query) {
            return $this->mock_get_var_returns;
        }

        public function get_row($query) {
            return $this->mock_get_row_returns;
        }

        public function insert($table, $data, $format) {
            return $this->mock_insert_returns;
        }
    }

    $GLOBALS['wpdb'] = new Mock_WPDB_Test();

    if (!function_exists('rest_ensure_response')) {
        function rest_ensure_response($response, $status = 200) {
            if (is_array($response)) {
                return new WP_REST_Response($response, $status);
            }
            return $response;
        }
    }

    if (!function_exists('assert_status')) {
        function assert_status($response, $expected_status) {
            if ($response->status !== $expected_status) {
                echo "Expected status $expected_status but got {$response->status}\n";
                $GLOBALS['failed']++;
            } else {
                $GLOBALS['passed']++;
            }
        }
    }
}

// Map functions to the plugin's namespace so they override native WordPress if needed
namespace ArabPsychology\NabooDatabase\Public {
    if (!function_exists(__NAMESPACE__ . '\rest_ensure_response')) {
        function rest_ensure_response($response, $status = 200) {
            return \rest_ensure_response($response, $status);
        }
    }
    if (!function_exists(__NAMESPACE__ . '\get_post')) {
        function get_post($id) {
            return \get_post($id);
        }
    }
    if (!function_exists(__NAMESPACE__ . '\get_post_type')) {
        function get_post_type($id) {
            return \get_post_type($id);
        }
    }
    if (!function_exists(__NAMESPACE__ . '\get_current_user_id')) {
        function get_current_user_id() {
            return \get_current_user_id();
        }
    }
    if (!function_exists(__NAMESPACE__ . '\sanitize_textarea_field')) {
        function sanitize_textarea_field($str) {
            return \sanitize_textarea_field($str);
        }
    }
    if (!function_exists(__NAMESPACE__ . '\wp_get_current_user')) {
        function wp_get_current_user() {
            return \wp_get_current_user();
        }
    }
    if (!function_exists(__NAMESPACE__ . '\get_transient')) {
        function get_transient($k) {
            return \get_transient($k);
        }
    }
    if (!function_exists(__NAMESPACE__ . '\set_transient')) {
        function set_transient($k, $v, $e) {
            return \set_transient($k, $v, $e);
        }
    }
}

namespace {
    echo "Running tests for Comments::add_comment...\n";

    $comments = new ArabPsychology\NabooDatabase\Public\Comments('naboodatabase', '1.0.0');
    $wpdb = $GLOBALS['wpdb'];

    // Test 1: Missing scale ID and comment text -> 400
    echo "Test 1: Missing scale ID and comment text\n";
    $request1 = new WP_REST_Request(['scale_id' => 0, 'comment' => '']);
    $response1 = $comments->add_comment($request1);
    assert_status($response1, 400);

    // Test 2: Valid scale ID, missing comment text -> 400
    echo "Test 2: Valid scale ID, missing comment text\n";
    $request2 = new WP_REST_Request(['scale_id' => 1, 'comment' => '']);
    $response2 = $comments->add_comment($request2);
    assert_status($response2, 400);

    // Test 3: Invalid scale ID -> 404
    echo "Test 3: Invalid scale ID\n";
    $request3 = new WP_REST_Request(['scale_id' => 999, 'comment' => 'This is a comment']);
    $response3 = $comments->add_comment($request3);
    assert_status($response3, 404);

    // Test 4: Valid comment, parent not found -> 404
    echo "Test 4: Valid comment, parent not found\n";
    $wpdb->mock_get_row_returns = null;
    $request4 = new WP_REST_Request(['scale_id' => 1, 'comment' => 'This is a comment', 'parent_id' => 5]);
    $response4 = $comments->add_comment($request4);
    assert_status($response4, 404);

    // Test 5: Rate limit exceeded -> 429
    echo "Test 5: Rate limit exceeded\n";
    $GLOBALS['mock_current_user_id'] = 1;
    $wpdb->mock_get_var_returns = 10; // Max is 10 for logged in
    $request5 = new WP_REST_Request(['scale_id' => 1, 'comment' => 'This is a comment']);
    $response5 = $comments->add_comment($request5);
    assert_status($response5, 429);
    $wpdb->mock_get_var_returns = 0; // Reset

    // Test 6: Spam comment -> 400
    echo "Test 6: Spam comment\n";
    $request6 = new WP_REST_Request(['scale_id' => 1, 'comment' => 'TOO MUCH CAPS']);
    $response6 = $comments->add_comment($request6);
    assert_status($response6, 400);

    // Test 7: Successful comment -> 201
    echo "Test 7: Successful comment\n";
    $wpdb->mock_insert_returns = true;
    $request7 = new WP_REST_Request(['scale_id' => 1, 'comment' => 'This is a valid comment.']);
    $response7 = $comments->add_comment($request7);
    assert_status($response7, 201);
    if (!isset($response7->data['id']) || $response7->data['id'] !== 123) {
        echo "Expected insert_id 123, got " . ($response7->data['id'] ?? 'null') . "\n";
        global $failed;
        $failed++;
    }

    // Test 8: Failed insert -> 500
    echo "Test 8: Failed insert\n";
    $wpdb->mock_insert_returns = false;
    $request8 = new WP_REST_Request(['scale_id' => 1, 'comment' => 'This is a valid comment.']);
    $response8 = $comments->add_comment($request8);
    assert_status($response8, 500);

    echo "\nSummary: " . $GLOBALS['passed'] . " passed, " . $GLOBALS['failed'] . " failed.\n";
    if ($GLOBALS['failed'] > 0) {
        exit(1);
    }
}
