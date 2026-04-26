<?php
// Define minimal WordPress core mocks directly to avoid conflicts
if (!function_exists('get_bloginfo')) {
    function get_bloginfo($show = '') {
        return '6.0';
    }
}
if (!defined('NABOODATABASE_VERSION')) {
    define('NABOODATABASE_VERSION', '1.0.0');
}

if (!class_exists('WP_REST_Request')) {
    class WP_REST_Request {
        public $method;
        public $route;
        public function __construct($method = '', $route = '') {
            $this->method = $method;
            $this->route = $route;
        }
    }
}

if (!class_exists('WP_REST_Response')) {
    class WP_REST_Response {
        public $data;
        public $status;
        public function __construct($data = null, $status = 200) {
            $this->data = $data;
            $this->status = $status;
        }
        public function get_data() {
            return $this->data;
        }
    }
}

if (!function_exists('add_action')) {
    function add_action($hook_name, $callback, $priority = 10, $accepted_args = 1) {}
}

if (!function_exists('register_rest_route')) {
    function register_rest_route($namespace, $route, $args = array(), $override = false) {}
}

if (!function_exists('dbDelta')) {
    function dbDelta($sql) { return []; }
}

require_once __DIR__ . '/../includes/admin/class-performance-metrics-dashboard.php';
use ArabPsychology\NabooDatabase\Admin\Performance_Metrics_Dashboard;

echo "Running Performance Metrics Dashboard Health Test...\n";

function test_get_system_health_db_error() {
    global $wpdb;

    // Create a mock WPDB that throws an exception on query()
    $wpdb = new class {
        public $prefix = 'wp_';
        public function query($query) {
            if ($query === 'SELECT 1') {
                throw new \Exception('Database connection failed');
            }
            return false;
        }
        public function get_col($query) {
            return [];
        }
        public function get_var($query) {
            return 'wp_naboo_performance_metrics';
        }
        public function prepare($query, ...$args) {
            return $query;
        }
        public function esc_like($text) {
            return $text;
        }
        public function get_charset_collate() {
            return '';
        }
    };

    $dashboard = new Performance_Metrics_Dashboard('naboodatabase', '1.0.0');

    // Create a mock request
    $request = new \WP_REST_Request('GET', '/performance/system-health');

    $response = $dashboard->get_system_health($request);
    $data = $response->get_data();

    ini_set('assert.exception', 1);

    try {
        assert($data['database_status'] === 'error', 'Expected database_status to be "error"');
        assert($data['database_error'] === 'Database connection failed', 'Expected database_error to contain the exception message');
        echo "PASS: test_get_system_health_db_error\n";
    } catch (\Throwable $e) {
        echo "FAIL: test_get_system_health_db_error - " . $e->getMessage() . "\n";
        exit(1);
    }
}

function test_get_system_health_db_success() {
    global $wpdb;

    // Create a mock WPDB that returns success on query()
    $wpdb = new class {
        public $prefix = 'wp_';
        public function query($query) {
            if ($query === 'SELECT 1') {
                return 1;
            }
            return false;
        }
        public function get_col($query) {
            return [];
        }
        public function get_var($query) {
            return 'wp_naboo_performance_metrics';
        }
        public function prepare($query, ...$args) {
            return $query;
        }
        public function esc_like($text) {
            return $text;
        }
        public function get_charset_collate() {
            return '';
        }
    };

    $dashboard = new Performance_Metrics_Dashboard('naboodatabase', '1.0.0');

    // Create a mock request
    $request = new \WP_REST_Request('GET', '/performance/system-health');

    $response = $dashboard->get_system_health($request);
    $data = $response->get_data();

    ini_set('assert.exception', 1);

    try {
        assert($data['database_status'] === 'healthy', 'Expected database_status to be "healthy"');
        echo "PASS: test_get_system_health_db_success\n";
    } catch (\Throwable $e) {
        echo "FAIL: test_get_system_health_db_success - " . $e->getMessage() . "\n";
        exit(1);
    }
}

test_get_system_health_db_error();
test_get_system_health_db_success();
