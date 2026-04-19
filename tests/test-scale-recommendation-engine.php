<?php

require_once __DIR__ . '/../includes/public/class-scale-recommendation-engine.php';

// Mock WP Classes
class WP_REST_Request {
    private $params = [];
    public function set_param($key, $value) {
        $this->params[$key] = $value;
    }
    public function get_param($key) {
        return $this->params[$key] ?? null;
    }
}

class WP_REST_Response {
    private $data;
    private $status;
    public function __construct($data = null, $status = 200) {
        $this->data = $data;
        $this->status = $status;
    }
    public function get_data() { return $this->data; }
    public function get_status() { return $this->status; }
}

class WP_Post {
    public $ID;
    public $post_title;
    public $post_status = 'publish';
}

// Mock Global functions
if (!function_exists('get_post')) {
    function get_post($id) {
        if ($id <= 0) return null;
        $post = new WP_Post();
        $post->ID = $id;
        $post->post_title = "Mocked Scale $id";
        return $post;
    }
}

if (!function_exists('dbDelta')) {
    function dbDelta($sql) {
        // mock
    }
}

if (!function_exists('add_action')) {
    function add_action($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
        // mock
    }
}


// Mock $wpdb
class Mock_WPDB {
    public $prefix = 'wp_';
    public $last_query = '';
    public $mock_results = [];
    public $mock_var = null;

    public function prepare($query, ...$args) {
        // very simple prepare for tests
        $prepared = $query;
        foreach($args as $arg) {
            $prepared = preg_replace('/%d/', $arg, $prepared, 1);
        }
        $this->last_query = $prepared;
        return $this->last_query;
    }

    public function get_results($query) {
        return $this->mock_results;
    }

    public function get_var($query) {
        return $this->mock_var;
    }
}

global $wpdb;
$wpdb = new Mock_WPDB();

use ArabPsychology\NabooDatabase\Public\Scale_Recommendation_Engine;

function test_get_trending_recommendations() {
    global $wpdb;
    $wpdb->mock_var = 'wp_naboo_popularity_analytics'; // mock table exists
    $engine = new Scale_Recommendation_Engine('naboodatabase', '1.0.0');

    // Test 1: Default limit
    $request = new WP_REST_Request();

    // Set up mock DB results
    $item1 = new stdClass();
    $item1->scale_id = 1;
    $item1->views = 100;
    $item1->downloads = 50;
    $item1->favorites = 10;
    $item1->avg_rating = 4.5;

    $item2 = new stdClass();
    $item2->scale_id = 2;
    $item2->views = 80;
    $item2->downloads = 40;
    $item2->favorites = 5;
    $item2->avg_rating = 4.0;

    $wpdb->mock_results = [$item1, $item2];

    $response = $engine->get_trending_recommendations($request);
    $data = $response->get_data();

    if (!str_contains($wpdb->last_query, "LIMIT 5")) {
        echo "FAIL: Expected query to have LIMIT 5.\n";
        echo "Actual query: " . $wpdb->last_query . "\n";
        exit(1);
    }

    if (!isset($data['trending'])) {
        echo "FAIL: Expected 'trending' key in response.\n";
        exit(1);
    }

    if (count($data['trending']) !== 2) {
        echo "FAIL: Expected 2 items, got " . count($data['trending']) . ".\n";
        exit(1);
    }

    $first_item = $data['trending'][0];
    if ($first_item['id'] !== 1 || $first_item['score'] !== (100 + 50*2 + 10*3)) {
        echo "FAIL: First item has incorrect ID or score.\n";
        var_dump($first_item);
        exit(1);
    }

    // Test 2: Custom limit and invalid posts
    $request = new WP_REST_Request();
    $request->set_param('limit', 1);

    $item_invalid = new stdClass();
    $item_invalid->scale_id = -1; // This will return null from get_post
    $item_invalid->views = 500;
    $item_invalid->downloads = 100;
    $item_invalid->favorites = 50;
    $item_invalid->avg_rating = 5.0;

    $wpdb->mock_results = [$item_invalid, $item1];

    $response = $engine->get_trending_recommendations($request);
    $data = $response->get_data();

    if (!str_contains($wpdb->last_query, "LIMIT 1")) {
        echo "FAIL: Expected query to have LIMIT 1.\n";
        echo "Actual query: " . $wpdb->last_query . "\n";
        exit(1);
    }

    if (count($data['trending']) !== 1) {
        echo "FAIL: Expected 1 valid item (since the first was invalid), got " . count($data['trending']) . ".\n";
        exit(1);
    }

    if ($data['trending'][0]['id'] !== 1) {
        echo "FAIL: Expected valid post to be returned, skipping the invalid one.\n";
        exit(1);
    }

    // Test 3: Missing items handling
    $request = new WP_REST_Request();
    $wpdb->mock_results = [];
    $response = $engine->get_trending_recommendations($request);
    $data = $response->get_data();
    if (count($data['trending']) !== 0) {
        echo "FAIL: Expected 0 items when no DB results, got " . count($data['trending']) . ".\n";
        exit(1);
    }

    echo "PASS: get_trending_recommendations tests passed.\n";
}

test_get_trending_recommendations();
