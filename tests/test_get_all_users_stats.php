<?php

require_once __DIR__ . '/../includes/public/class-user-analytics-dashboard.php';
require_once __DIR__ . '/mock-wp-classes.php';

// Mock WP_Error
class WP_Error {
    public $code;
    public $message;
    public $data;

    public function __construct( $code = '', $message = '', $data = '' ) {
        $this->code = $code;
        $this->message = $message;
        $this->data = $data;
    }
}

// Mock wpdb
class wpdb {
    public $prefix = 'wp_';
    public $users = 'wp_users';
    private $results = [];

    public function set_results($results) {
        $this->results = $results;
    }

    public function get_results($query) {
        return $this->results;
    }

    public function get_var($query) {
        return 'wp_naboo_user_analytics';
    }

    public function query($query) {
        return true;
    }

    public function prepare($query, ...$args) {
        // Simple mock implementation of prepare
        $query = str_replace('%s', "'%s'", $query);
        return vsprintf($query, $args);
    }
}

function dbDelta($sql) {
    return [];
}

global $wpdb;
$wpdb = new wpdb();

$current_user_can_result = true;
function current_user_can($capability) {
    global $current_user_can_result;
    return $current_user_can_result;
}

function esc_html__($text, $domain) {
    return $text;
}

// Test cases
$dashboard = new \ArabPsychology\NabooDatabase\Public\User_Analytics_Dashboard('naboodatabase', '1.0.0');

echo "Running tests for get_all_users_stats...\n\n";

// Test 1: Permission denied
$current_user_can_result = false;
$request = new WP_REST_Request();
$response = $dashboard->get_all_users_stats($request);

if ($response instanceof WP_Error && $response->code === 'rest_forbidden') {
    echo "✅ Test 1 Passed: Permission denied handled correctly.\n";
} else {
    echo "❌ Test 1 Failed: Expected WP_Error with rest_forbidden.\n";
    exit(1);
}

// Test 2: Success
$current_user_can_result = true;
$mock_results = [
    (object)['ID' => 1, 'user_login' => 'admin', 'display_name' => 'Admin User', 'total_searches' => 10, 'total_views' => 20, 'total_downloads' => 5, 'total_ratings' => 2, 'last_activity' => '2023-01-01 12:00:00'],
];
$wpdb->set_results($mock_results);
$request = new WP_REST_Request();
$response = $dashboard->get_all_users_stats($request);
$data = $response->data;

if ($response instanceof WP_REST_Response && $response->status === 200 && count($data['users']) === 1 && $data['users'][0]->ID === 1) {
    echo "✅ Test 2 Passed: Success handled correctly.\n";
} else {
    echo "❌ Test 2 Failed: Expected WP_REST_Response with user data.\n";
    exit(1);
}

echo "\nAll tests passed.\n";
