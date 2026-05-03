<?php
// Mocks for WP classes
class WP_REST_Request {
    private $params = [];
    public function set_param($key, $value) {
        $this->params[$key] = $value;
    }
    public function get_param($key) {
        return isset($this->params[$key]) ? $this->params[$key] : null;
    }
}

class WP_REST_Response {
    public $data;
    public $status;
    public function __construct($data, $status = 200) {
        $this->data = $data;
        $this->status = $status;
    }
}

// Procedural mocks
function get_post($id) {
    if ($id == 999) return null; // Test 404

    $post = new stdClass();
    $post->ID = $id;
    $post->post_type = 'psych_scale';
    $post->post_status = 'publish';
    $post->post_title = 'Valid Title';
    $post->post_content = 'Valid Content';
    $post->guid = 'http://example.com/scale/valid-title';
    return $post;
}

// Ensure required variables/globals are defined for PDF generation (HTTP_HOST)
$_SERVER['HTTP_HOST'] = 'example.com';
function wp_get_post_terms($post_id, $taxonomy, $args = array()) { return []; }
function get_post_meta($post_id, $key = '', $single = false) { return ''; }

global $wpdb;
$wpdb = new class {
    public $prefix = 'wp_';
    public function prepare($query, ...$args) { return $query; }
    public function get_row($query) { return null; }
};

// Load class
require_once dirname(__DIR__) . '/includes/public/class-pdf-export.php';

use ArabPsychology\NabooDatabase\Public\PDF_Export;

// We override the generate_pdf method to throw an exception
class Test_PDF_Export extends PDF_Export {
    public function __construct() {
        parent::__construct('naboodatabase', '1.0.0');
    }

    // Override generate_pdf to throw an exception if post ID is 500
    protected function generate_pdf( $post ) {
        if ($post->ID == 500) {
            throw new \Exception('Simulated PDF generation failure');
        }
        return parent::generate_pdf($post);
    }
}

$exporter = new Test_PDF_Export();

// Test 1: Exception path
$req1 = new WP_REST_Request();
$req1->set_param('id', 500);

$resp1 = $exporter->export_scale_pdf($req1);

if ($resp1->status !== 500) {
    echo "Test 1 Failed: Expected status 500 for exception path, got {$resp1->status}\n";
    exit(1);
}

if (!isset($resp1->data['error']) || strpos($resp1->data['error'], 'Failed to generate PDF: Simulated PDF generation failure') === false) {
    echo "Test 1 Failed: Exception message not properly passed to response\n";
    exit(1);
}
echo "Test 1 Passed: Exception correctly caught and error returned.\n";

// Test 2: Scale not found
$req2 = new WP_REST_Request();
$req2->set_param('id', 999);

$resp2 = $exporter->export_scale_pdf($req2);

if ($resp2->status !== 404) {
    echo "Test 2 Failed: Expected status 404 for missing scale, got {$resp2->status}\n";
    exit(1);
}
echo "Test 2 Passed: 404 returned for missing scale.\n";

// Test 3: Happy path
$req3 = new WP_REST_Request();
$req3->set_param('id', 1);

$resp3 = $exporter->export_scale_pdf($req3);

if ($resp3->status !== 200) {
    echo "Test 3 Failed: Expected status 200 for happy path, got {$resp3->status}\n";
    exit(1);
}

if (!isset($resp3->data['success']) || !$resp3->data['success']) {
    echo "Test 3 Failed: Success not true\n";
    exit(1);
}
echo "Test 3 Passed: Happy path works.\n";
