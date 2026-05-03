<?php
namespace ArabPsychology\NabooDatabase\Admin {

    require_once __DIR__ . '/../includes/admin/class-bulk-operations.php';

    // Define WordPress mock functions in the specific namespace to shadow globals
    function wp_update_term_count($terms, $taxonomy) {
        global $wp_update_term_count_calls;
        $wp_update_term_count_calls++;
    }

    function clean_object_term_cache($object_ids, $object_type) {
        global $clean_cache_calls;
        $clean_cache_calls++;
    }
}

namespace {
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

    global $wpdb;
    $wpdb = new class {
        public $prefix = 'wp_';
        public $term_relationships = 'wp_term_relationships';
        public $term_taxonomy = 'wp_term_taxonomy';
        public $queries = [];

        public function prepare($query, ...$args) {
            if (!empty($args) && is_array($args[0])) {
                $args = $args[0];
            }
            return ['query' => $query, 'args' => $args];
        }

        public function query($query_data) {
            $this->queries[] = $query_data;
            return true;
        }

        public function get_col($query_data) {
            return [101, 102];
        }
    };

    global $wp_update_term_count_calls, $clean_cache_calls;
    $wp_update_term_count_calls = 0;
    $clean_cache_calls = 0;

    $bulk_ops = new \ArabPsychology\NabooDatabase\Admin\Bulk_Operations('naboodatabase', '1.55.10');

    $request = new WP_REST_Request([
        'scale_ids' => [1, 2, 3],
        'taxonomy' => 'scale_category',
        'term_ids' => [10, 20]
    ]);

    $response = $bulk_ops->add_taxonomy($request);

    // Assertions
    if ($response->status !== 200) {
        echo "FAIL: Expected status 200, got " . $response->status . "\n";
        exit(1);
    }

    if ($wp_update_term_count_calls !== 1) {
        echo "FAIL: wp_update_term_count was not called exactly once.\n";
        exit(1);
    }

    if ($clean_cache_calls !== 1) {
        echo "FAIL: clean_object_term_cache was not called exactly once.\n";
        exit(1);
    }

    if (empty($wpdb->queries)) {
        echo "FAIL: No insert queries executed.\n";
        exit(1);
    }

    echo "PASS: verify_bulk_assignment.php\n";
    exit(0);
}
