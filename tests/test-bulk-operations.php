<?php

// Mock WordPress functions and classes
if (!class_exists('WP_REST_Request')) {
    class WP_REST_Request {
        private $params = [];
        public function __construct($params = []) {
            $this->params = $params;
        }
        public function get_param($key) {
            return $this->params[$key] ?? null;
        }
    }
}

if (!class_exists('WP_REST_Response')) {
    class WP_REST_Response {
        public $data;
        public $status;
        public function __construct($data, $status) {
            $this->data = $data;
            $this->status = $status;
        }
    }
}

if (!function_exists('wp_update_post')) {
    function wp_update_post($args) { return true; }
    function wp_delete_post($id, $force) { return true; }
    function wp_set_post_terms($object_id, $terms, $taxonomy, $append = false) { return true; }
    function is_wp_error($thing) { return false; }
    function get_post($id) {
        $post = new stdClass();
        $post->ID = $id;
        $post->post_title = "Test Post";
        $post->post_content = "Test Content";
        $post->post_excerpt = "Test Excerpt";
        return $post;
    }
    function get_post_meta($post_id, $key, $single = false) { return "Test Meta"; }
    function wp_get_post_terms($post_id, $taxonomy, $args = []) { return ["Test Term"]; }
}

// Include the files
require_once __DIR__ . '/../includes/admin/class-bulk-operations.php';
require_once __DIR__ . '/../includes/admin/class-submission-management-queue.php';

class Test_Bulk_Operations {

    public function run() {
        $bulk_ops = new \ArabPsychology\NabooDatabase\Admin\Bulk_Operations('test', '1.0');
        $queue = new \ArabPsychology\NabooDatabase\Admin\Submission_Management_Queue('test', '1.0');

        $tests_passed = true;
        $tests = [];

        // 1. Test change_status
        try {
            $req = new WP_REST_Request(['scale_ids' => '1,2,3', 'status' => 'publish']);
            $res = $bulk_ops->change_status($req);
            $tests[] = ['name' => 'change_status missing array validation test', 'status' => $res->status === 400 ? 'PASS' : 'FAIL'];
            if ($res->status !== 400) $tests_passed = false;
        } catch (\Throwable $e) {
            $tests[] = ['name' => 'change_status', 'status' => 'FAIL', 'error' => $e->getMessage()];
            $tests_passed = false;
        }

        // 2. Test add_taxonomy
        try {
            $req = new WP_REST_Request(['scale_ids' => '1,2,3', 'taxonomy' => 'scale_category', 'term_ids' => [1]]);
            $res = $bulk_ops->add_taxonomy($req);
            $tests[] = ['name' => 'add_taxonomy missing array validation test', 'status' => $res->status === 400 ? 'PASS' : 'FAIL'];
            if ($res->status !== 400) $tests_passed = false;
        } catch (\Throwable $e) {
            $tests[] = ['name' => 'add_taxonomy', 'status' => 'FAIL', 'error' => $e->getMessage()];
            $tests_passed = false;
        }

        // 3. Test delete_scales
        try {
            $req = new WP_REST_Request(['scale_ids' => '1,2,3', 'permanent' => true]);
            $res = $bulk_ops->delete_scales($req);
            $tests[] = ['name' => 'delete_scales missing array validation test', 'status' => $res->status === 400 ? 'PASS' : 'FAIL'];
            if ($res->status !== 400) $tests_passed = false;
        } catch (\Throwable $e) {
            $tests[] = ['name' => 'delete_scales', 'status' => 'FAIL', 'error' => $e->getMessage()];
            $tests_passed = false;
        }

        // 4. Test export_scales
        try {
            $req = new WP_REST_Request(['scale_ids' => '1,2,3', 'format' => 'json']);
            $res = $bulk_ops->export_scales($req);
            $tests[] = ['name' => 'export_scales missing array validation test', 'status' => $res->status === 400 ? 'PASS' : 'FAIL'];
            if ($res->status !== 400) $tests_passed = false;
        } catch (\Throwable $e) {
            $tests[] = ['name' => 'export_scales', 'status' => 'FAIL', 'error' => $e->getMessage()];
            $tests_passed = false;
        }

        // 5. Test bulk_action
        try {
            $req = new WP_REST_Request(['post_ids' => '1,2,3', 'action' => 'approve']);
            $res = $queue->bulk_action($req);
            $tests[] = ['name' => 'bulk_action missing array validation test', 'status' => $res->status === 400 ? 'PASS' : 'FAIL'];
            if ($res->status !== 400) $tests_passed = false;
        } catch (\Throwable $e) {
            $tests[] = ['name' => 'bulk_action', 'status' => 'FAIL', 'error' => $e->getMessage()];
            $tests_passed = false;
        }

        // Print results
        echo "Test Results:\n";
        foreach ($tests as $test) {
            echo "- {$test['name']}: {$test['status']}\n";
            if (isset($test['error'])) {
                echo "  Error: {$test['error']}\n";
            }
        }

        if (!$tests_passed) {
            echo "\nTests Failed!\n";
            exit(1);
        } else {
            echo "\nAll Tests Passed!\n";
            exit(0);
        }
    }
}

// Run if executed directly
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    $tester = new Test_Bulk_Operations();
    $tester->run();
}
