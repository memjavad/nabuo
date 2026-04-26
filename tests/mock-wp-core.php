<?php
// Mock WP core functions and classes for testing
define('ABSPATH', __DIR__ . '/');

$mock_current_user_id = 0;

function get_current_user_id() {
    global $mock_current_user_id;
    return $mock_current_user_id;
}

$mock_posts = [
    1 => (object)[
        'ID' => 1,
        'post_title' => 'Mock Scale',
    ]
];

function get_post($id) {
    global $mock_posts;
    return $mock_posts[$id] ?? null;
}

function dbDelta($queries) {
    return [];
}

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

    public function get_data() {
        return $this->data;
    }

    public function get_status() {
        return $this->status;
    }
}

global $wpdb;
$wpdb = new class {
    public $prefix = 'wp_';
    public function prepare($query, ...$args) {
        return "PREPARED";
    }
    public function get_results($query) {
        $item = new stdClass();
        $item->scale_id = 1;
        $item->views = 100;
        $item->downloads = 50;
        $item->favorites = 10;
        return [$item];
    }
    public function get_charset_collate() {
        return "";
    }
    public function get_var($query) {
        return null;
    }
    public function query($query) {
        return true;
    }
};

function wp_reset_postdata() {}
if (!function_exists("wp_get_post_terms")) {
    $mock_post_terms = [];
    function wp_set_mock_post_terms($terms) {
        global $mock_post_terms;
        $mock_post_terms = $terms;
    }
    function wp_get_post_terms($post_id, $taxonomy = "", $args = []) {
        global $mock_post_terms;
        return $mock_post_terms[$post_id] ?? [];
    }
}
if (!function_exists("get_post_meta")) {
    $mock_post_meta = [];
    function wp_set_mock_post_meta($meta) {
        global $mock_post_meta;
        $mock_post_meta = $meta;
    }
    function get_post_meta($post_id, $key = "", $single = false) {
        global $mock_post_meta;
        return $mock_post_meta[$post_id][$key] ?? 0;
    }
}
