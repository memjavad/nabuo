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
    public function __construct($data, $status) {
        $this->data = $data;
        $this->status = $status;
    }
}

// Procedural mocks
function get_post($id) {
    $post = new stdClass();
    $post->ID = $id;
    $post->post_type = 'psych_scale';
    $post->post_title = 'Valid Title';
    $post->post_content = str_repeat('Valid Content ', 10);
    return $post;
}

function get_post_meta($post_id, $key, $single) {
    if ($post_id == 999 && $key === '_naboo_scale_items') {
        throw new \Exception('Simulated database error');
    }
    if ($key === '_naboo_scale_items') return 10;
    if ($key === '_naboo_scale_year') return 2020;
    if ($key === '_naboo_scale_language') return 'English';
    if ($key === '_naboo_scale_population') return 'Adults';
    return '';
}

if (!function_exists('gmdate')) {
    function gmdate($format) {
        return date($format);
    }
}

function wp_get_post_terms($post_id, $taxonomy) {
    return array('mock_term');
}

function is_wp_error($thing) {
    return false;
}

// Load class
require_once dirname(__DIR__) . '/includes/admin/class-scale-validation.php';

use ArabPsychology\NabooDatabase\Admin\Scale_Validation;

$validator = new Scale_Validation('naboodatabase', '1.0.0');

// Test 1: Happy path
$req1 = new WP_REST_Request();
$req1->set_param('id', 1);
$resp1 = $validator->validate_scale($req1);

if ($resp1->status !== 200 || !$resp1->data['is_valid']) {
    echo "Test 1 Failed: Expected valid scale\n";
    exit(1);
}
echo "Test 1 Passed: Happy path works\n";

// Test 2: Exception path
$req2 = new WP_REST_Request();
$req2->set_param('id', 999);

try {
    // Start output buffering to capture stderr if error_log writes there
    ob_start();
    $resp2 = $validator->validate_scale($req2);
    $output = ob_get_clean();

    // Check if $resp2 shows invalid properly due to the exception caught
    if ($resp2->status === 200 && $resp2->data['is_valid'] === false) {

        $has_exception_message = false;
        foreach ($resp2->data['issues'] as $issue) {
            if (strpos($issue, 'An unexpected error occurred during validation.') !== false) {
                $has_exception_message = true;
                break;
            }
        }

        if ($has_exception_message) {
            echo "Test 2 Passed: Exception correctly caught and scale marked as invalid.\n";
            exit(0);
        } else {
            echo "Test 2 Failed: is_valid is false, but the exception message was not found in issues.\n";
            exit(1);
        }

    } else {
        echo "Test 2 Failed: Did not return status 200 or is_valid is not false. Status: {$resp2->status}\n";
        exit(1);
    }
} catch (\Exception $e) {
    echo "Test 2 Failed: Exception bubbled up: " . $e->getMessage() . "\n";
    exit(1);
}
