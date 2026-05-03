<?php
namespace {
    // Mocks for globals
    $wp_defer_term_counting_calls = [];
    $wp_defer_comment_counting_calls = [];
    $wp_delete_post_calls = [];

    function wp_defer_term_counting($defer) {
        global $wp_defer_term_counting_calls;
        $wp_defer_term_counting_calls[] = $defer;
    }

    function wp_defer_comment_counting($defer) {
        global $wp_defer_comment_counting_calls;
        $wp_defer_comment_counting_calls[] = $defer;
    }

    function wp_delete_post($post_id, $force_delete = false) {
        global $wp_delete_post_calls;
        $wp_delete_post_calls[] = [$post_id, $force_delete];
        return true;
    }

    if (!class_exists('\WP_REST_Response')) {
        class WP_REST_Response {
            public $data;
            public $status;
            public function __construct($data = null, $status = 200) {
                $this->data = $data;
                $this->status = $status;
            }
        }
    }
}

namespace ArabPsychology\NabooDatabase\Admin {
    require_once __DIR__ . '/../includes/admin/class-bulk-operations.php';

    global $wp_defer_term_counting_calls, $wp_defer_comment_counting_calls, $wp_delete_post_calls;

    class WP_REST_Request_Mock {
        private $params = [];
        public function __construct($params = []) {
            $this->params = $params;
        }
        public function get_param($key) {
            return isset($this->params[$key]) ? $this->params[$key] : null;
        }
    }


    $bulk_ops = new Bulk_Operations('test', '1.0.0');

    $request = new WP_REST_Request_Mock([
        'scale_ids' => [1, 2, 3],
        'permanent' => true
    ]);

    $response = $bulk_ops->delete_scales($request);

    if ($response->status !== 200) {
        echo "FAIL: Expected status 200, got {$response->status}\n";
        exit(1);
    }

    if (count($wp_delete_post_calls) !== 3) {
        echo "FAIL: Expected 3 calls to wp_delete_post, got " . count($wp_delete_post_calls) . "\n";
        exit(1);
    }

    if (count($wp_defer_term_counting_calls) !== 2) {
        echo "FAIL: Expected 2 calls to wp_defer_term_counting, got " . count($wp_defer_term_counting_calls) . "\n";
        exit(1);
    }

    if ($wp_defer_term_counting_calls[0] !== true) {
        echo "FAIL: Expected wp_defer_term_counting(true) first\n";
        exit(1);
    }
    if ($wp_defer_term_counting_calls[1] !== false) {
        echo "FAIL: Expected wp_defer_term_counting(false) second\n";
        exit(1);
    }


    if (count($wp_defer_comment_counting_calls) !== 2) {
        echo "FAIL: Expected 2 calls to wp_defer_comment_counting, got " . count($wp_defer_comment_counting_calls) . "\n";
        exit(1);
    }

    if ($wp_defer_comment_counting_calls[0] !== true) {
        echo "FAIL: Expected wp_defer_comment_counting(true) first\n";
        exit(1);
    }
    if ($wp_defer_comment_counting_calls[1] !== false) {
        echo "FAIL: Expected wp_defer_comment_counting(false) second\n";
        exit(1);
    }

    echo "PASS: Term and comment counting are deferred during bulk deletion.\n";
    exit(0);
}
