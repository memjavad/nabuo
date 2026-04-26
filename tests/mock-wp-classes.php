<?php

class WP_REST_Request {
    private $params;

    public function __construct( $params = array() ) {
        $this->params = $params;
    }

    public function get_param( $key ) {
        return isset( $this->params[ $key ] ) ? $this->params[ $key ] : null;
    }

    public function get_json_params() {
        return $this->params;
    }
}

class WP_REST_Response {
    public $data;
    public $status;

    public function __construct( $data = null, $status = 200 ) {
        $this->data = $data;
        $this->status = $status;
    }
}

// Simple mocking of WordPress functions needed by the tested methods (to prevent fatal errors)
if (!function_exists('get_post')) {
    function get_post( $id ) {
        $post_id = is_object($id) ? $id->ID : (is_array($id) ? $id['ID'] : $id);
        if ($post_id == 999) return null;
        if ($post_id == 1) {
            $p = new stdClass();
            $p->ID = 1;
            return $p;
        }
        return false;
    }
}

function wp_update_post( $args ) { return true; }
function wp_set_post_terms( $object_id, $terms, $taxonomy, $append = false ) { return true; }
function is_wp_error( $thing ) { return false; }
function wp_delete_post( $postid, $force_delete = false ) { return true; }

if (!function_exists('get_post_type')) {
    function get_post_type( $id ) {
        $post_id = is_object($id) ? $id->ID : (is_array($id) ? $id['ID'] : $id);
        if ($post_id == 999) return false;
        return 'psych_scale';
    }
}
function get_post_meta( $post_id, $key = '', $single = false ) { return ''; }
function wp_get_post_terms( $post_id, $taxonomy, $args = array() ) { return array(); }

// Simple assertion function
function assert_status( $response, $expected_status ) {
    global $failed;
    if ( $response->status !== $expected_status ) {
        echo "Expected status $expected_status but got {$response->status}\n";
        $failed++;
    } else {
        global $passed;
        $passed++;
    }
}
