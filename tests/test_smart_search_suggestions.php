<?php

require_once __DIR__ . '/../includes/public/class-smart-search-suggestions.php';

// Mock WP_REST_Request
if (!class_exists('WP_REST_Request')) {
    class WP_REST_Request {
        private $params = [];
        public function __construct( $params = [] ) {
            $this->params = $params;
        }
        public function get_param( $key ) {
            return isset( $this->params[$key] ) ? $this->params[$key] : null;
        }
    }
}

// Mock WP_REST_Response
if (!class_exists('WP_REST_Response')) {
    class WP_REST_Response {
        public $data;
        public $status;
        public function __construct( $data = null, $status = 200 ) {
            $this->data = $data;
            $this->status = $status;
        }
    }
}

// Mock WP_Query
if (!class_exists('WP_Query')) {
    class WP_Query {
        public $posts = [];
        public $args = [];
        public function __construct( $args ) {
            $this->args = $args;
            if ( isset($args['s']) && $args['s'] === 'depression' ) {
                $this->posts = [1, 2];
            }
        }
    }
}

// Mock standard WP functions
if (!function_exists('get_post')) {
    function get_post( $id ) {
        $post = new stdClass();
        $post->post_title = "Scale $id";
        $post->post_excerpt = "Excerpt $id";
        $post->post_content = "Content $id";
        return $post;
    }
}

if (!function_exists('get_the_post_thumbnail_url')) {
    function get_the_post_thumbnail_url( $id, $size ) {
        return "http://example.com/thumb-$id.jpg";
    }
}

if (!function_exists('wp_trim_words')) {
    function wp_trim_words( $text, $num_words ) {
        return $text; // Return as is for test simplicity
    }
}

if (!function_exists('get_permalink')) {
    function get_permalink( $id ) {
        return "http://example.com/scale-$id";
    }
}

// Mock WPDB
if (!class_exists('Mock_WPDB')) {
    class Mock_WPDB {
        public $prefix = 'wp_';
        public function prepare( $query, ...$args ) {
            return $query; // Not doing real prep in mock
        }
        public function get_var( $query ) {
            if ( strpos( $query, 'AVG(rating)' ) !== false ) {
                return 4.5;
            }
            return null;
        }
    }
}

if (!isset($GLOBALS['wpdb'])) {
    $GLOBALS['wpdb'] = new Mock_WPDB();
}
$wpdb = $GLOBALS['wpdb'];

$plugin = new \ArabPsychology\NabooDatabase\Public\Smart_Search_Suggestions('naboo', '1.0');

$tests_passed = 0;
$tests_total = 0;

// Test 1: Happy path
$tests_total++;
// Using both 'q' and 'query' to handle potential discrepancies between prompt and codebase
$request = new WP_REST_Request( ['query' => 'depression', 'q' => 'depression', 'limit' => 5] );
$response = $plugin->search_scales( $request );

if ( isset($response->data['scales']) && count($response->data['scales']) === 2 ) {
    $first_scale = $response->data['scales'][0];
    if ($first_scale['id'] === 1 && $first_scale['title'] === 'Scale 1' && $first_scale['rating'] === 4.5) {
        echo "✅ Test 1 (Happy path) passed.\n";
        $tests_passed++;
    } else {
        echo "❌ Test 1 (Happy path) failed: Data mapping incorrect.\n";
    }
} else {
    echo "❌ Test 1 (Happy path) failed: Incorrect results count.\n";
}

// Test 2: Zero results
$tests_total++;
$request2 = new WP_REST_Request( ['query' => 'nonexistent', 'q' => 'nonexistent', 'limit' => 5] );
$response2 = $plugin->search_scales( $request2 );
if ( isset($response2->data['scales']) && count($response2->data['scales']) === 0 ) {
    echo "✅ Test 2 (Zero results) passed.\n";
    $tests_passed++;
} else {
    echo "❌ Test 2 (Zero results) failed.\n";
}

// Test 3: Missing parameters (limit missing)
$tests_total++;
$request3 = new WP_REST_Request( ['query' => 'depression', 'q' => 'depression'] );
$response3 = $plugin->search_scales( $request3 );
if ( isset($response3->data['scales']) && count($response3->data['scales']) === 2 ) {
    echo "✅ Test 3 (Missing limit parameter) passed.\n";
    $tests_passed++;
} else {
    echo "❌ Test 3 (Missing limit parameter) failed.\n";
}

echo "\nTests completed: $tests_passed/$tests_total passed.\n";
if ($tests_passed !== $tests_total) {
    exit(1);
}
