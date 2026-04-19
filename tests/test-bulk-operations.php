<?php

require_once __DIR__ . '/../includes/admin/class-bulk-operations.php';

class Mock_WP_REST_Request {
    private $params = [];

    public function __construct( $params = [] ) {
        $this->params = $params;
    }

    public function get_param( $key ) {
        return isset( $this->params[$key] ) ? $this->params[$key] : null;
    }
}

class Mock_WP_REST_Response {
    public $data;
    public $status;

    public function __construct( $data = null, $status = 200, $headers = [] ) {
        $this->data = $data;
        $this->status = $status;
    }
}

// Mock WordPress functions
function wp_update_post( $post ) {
    return true; // Simulate success
}

function wp_set_post_terms( $post_id, $tags = '', $taxonomy = 'post_tag', $append = false ) {
    return true; // Simulate success
}

function wp_delete_post( $postid = 0, $force_delete = false ) {
    return true; // Simulate success
}

function get_post( $post = null, $output = 'OBJECT', $filter = 'raw' ) {
    $p = new stdClass();
    $p->ID = $post;
    $p->post_title = "Test Scale";
    $p->post_content = "Content";
    $p->post_excerpt = "Excerpt";
    return $p;
}

function get_post_meta( $post_id, $key = '', $single = false ) {
    return "meta_value";
}

function wp_get_post_terms( $post_id, $taxonomy = 'post_tag', $args = [] ) {
    return ["term1", "term2"];
}

// Map WP class to mock
class WP_REST_Response extends Mock_WP_REST_Response {}

function assert_response_400($response, $method_name) {
    if ( ! is_a( $response, '\WP_REST_Response' ) ) {
        echo "FAIL [$method_name]: Expected \WP_REST_Response, got " . gettype($response) . "\n";
        return false;
    }

    if ( $response->status !== 400 ) {
        echo "FAIL [$method_name]: Expected status 400, got " . $response->status . "\n";
        return false;
    }

    if ( ! isset( $response->data['error'] ) ) {
        echo "FAIL [$method_name]: Expected error message in response data.\n";
        return false;
    }

    echo "PASS [$method_name]: Correctly returned 400 \WP_REST_Response with error message.\n";
    return true;
}

function test_validation() {
    $bulk_ops = new \ArabPsychology\NabooDatabase\Admin\Bulk_Operations( 'test', '1.0' );

    $methods = [
        'change_status' => ['status' => 'publish'],
        'add_taxonomy' => ['taxonomy' => 'scale_category', 'term_ids' => [1, 2]],
        'delete_scales' => ['permanent' => false],
        'export_scales' => ['format' => 'json']
    ];

    foreach ($methods as $method => $extra_params) {
        $params = array_merge( ['scale_ids' => '1,2,3'], $extra_params ); // String instead of array
        $request = new Mock_WP_REST_Request( $params );

        try {
            $response = $bulk_ops->$method( $request );
            assert_response_400( $response, $method );
        } catch (Exception $e) {
            echo "FAIL [$method]: Exception caught: " . $e->getMessage() . "\n";
        } catch (Error $e) {
            echo "FAIL [$method]: Error caught: " . $e->getMessage() . "\n";
        }
    }
}

test_validation();
