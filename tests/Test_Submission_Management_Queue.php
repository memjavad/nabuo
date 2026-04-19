<?php
require_once 'tests/WP_Mock_Classes.php';
require_once 'includes/admin/class-submission-management-queue.php';

use PHPUnit\Framework\TestCase;

if (!function_exists('wp_update_post')) {
    function wp_update_post() {
        return true;
    }
}

if (!class_exists('Mock_WP_REST_Request')) {
    class Mock_WP_REST_Request {
        private $params = [];
        public function __construct($params = []) {
            $this->params = $params;
        }
        public function get_param($key) {
            return isset($this->params[$key]) ? $this->params[$key] : null;
        }
    }
}

class Test_Submission_Management_Queue extends TestCase {
    public function test_bulk_action_with_non_array_post_ids() {
        $queue = new \ArabPsychology\NabooDatabase\Admin\Submission_Management_Queue('naboodatabase', '1.0.0');
        $request = new Mock_WP_REST_Request([
            'post_ids' => 'not_an_array',
            'action' => 'approve'
        ]);

        $response = $queue->bulk_action($request);
        $this->assertEquals(400, $response->status);
        $this->assertArrayHasKey('error', $response->data);
    }
}
