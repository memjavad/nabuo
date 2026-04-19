<?php

require_once __DIR__ . '/../includes/public/class-scale-recommendation-engine.php';

// Mock WP_REST_Request
class WP_REST_Request {
    private $params = [];
    public function __construct() {}
    public function set_param($key, $value) {
        $this->params[$key] = $value;
    }
    public function get_param($key) {
        return isset($this->params[$key]) ? $this->params[$key] : null;
    }
}

// Mock WP_REST_Response
class WP_REST_Response {
    private $data;
    private $status;
    public function __construct($data, $status) {
        $this->data = $data;
        $this->status = $status;
    }
    public function get_data() {
        return $this->data;
    }
    public function get_status() {
        return $this->status;
    }
}

// Mock wpdb
class wpdb {
    public $prefix = 'wp_';
    private $results = [];

    public function set_results($results) {
        $this->results = $results;
    }

    public function get_results($query) {
        return $this->results;
    }

    public function prepare($query, ...$args) {
        // Simple mock implementation of prepare
        $query = str_replace('%d', '%s', $query);
        return vsprintf($query, $args);
    }

    public function get_var($query) {
        return $this->prefix . 'naboo_recommendations';
    }
}

function dbDelta($sql) {
    // mock dbDelta
}

global $wpdb;
$wpdb = new wpdb();

// Mock get_post
function get_post($id) {
    if (!$id) return null;
    $post = new stdClass();
    $post->ID = $id;
    $post->post_title = "Test Scale $id";
    return $post;
}

// Test cases
$engine = new \ArabPsychology\NabooDatabase\Public\Scale_Recommendation_Engine('naboodatabase', '1.0.0');

echo "Running tests for get_trending_recommendations...\n\n";

// Test 1: Empty results
$wpdb->set_results([]);
$request = new WP_REST_Request();
$response = $engine->get_trending_recommendations($request);
$data = $response->get_data();

if (empty($data['trending']) && $response->get_status() === 200) {
    echo "✅ Test 1 Passed: Empty results handled correctly.\n";
} else {
    echo "❌ Test 1 Failed: Expected empty trending array.\n";
}

// Test 2: Standard results
$mock_results = [
    (object)['scale_id' => 1, 'views' => 10, 'downloads' => 5, 'favorites' => 2, 'avg_rating' => 4.5],
    (object)['scale_id' => 2, 'views' => 20, 'downloads' => 2, 'favorites' => 1, 'avg_rating' => 4.0]
];
$wpdb->set_results($mock_results);
$request = new WP_REST_Request();
$response = $engine->get_trending_recommendations($request);
$data = $response->get_data();

if (count($data['trending']) === 2 && $data['trending'][0]['id'] === 1 && $data['trending'][0]['score'] == (10 + 5*2 + 2*3)) {
    echo "✅ Test 2 Passed: Standard results calculated correctly.\n";
} else {
    echo "❌ Test 2 Failed: Incorrect processing of standard results.\n";
}

// Test 3: Custom Limit
$request = new WP_REST_Request();
$request->set_param('limit', 1);
$response = $engine->get_trending_recommendations($request);
if ($response->get_status() === 200) {
     echo "✅ Test 3 Passed: Custom limit parameter handled without crashing.\n";
} else {
     echo "❌ Test 3 Failed: Custom limit caused an error.\n";
}

// Test 4: Handling missing post
$mock_results_with_missing = [
    (object)['scale_id' => 1, 'views' => 10, 'downloads' => 5, 'favorites' => 2, 'avg_rating' => 4.5],
    (object)['scale_id' => 0, 'views' => 20, 'downloads' => 2, 'favorites' => 1, 'avg_rating' => 4.0] // 0 will mock a missing post
];
$wpdb->set_results($mock_results_with_missing);
$request = new WP_REST_Request();
$response = $engine->get_trending_recommendations($request);
$data = $response->get_data();

if (count($data['trending']) === 1 && $data['trending'][0]['id'] === 1) {
    echo "✅ Test 4 Passed: Missing post handled correctly (skipped).\n";
} else {
    echo "❌ Test 4 Failed: Incorrect handling of missing post.\n";
}


echo "\nAll tests completed.\n";
