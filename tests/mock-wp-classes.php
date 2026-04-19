<?php

class WP_REST_Request {
    private $params;

    public function __construct( $params = array() ) {
        $this->params = $params;
    }

    public function get_param( $key ) {
        return isset( $this->params[ $key ] ) ? $this->params[ $key ] : null;
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
function get_post( $id ) { return false; }
function wp_update_post( $args ) { return true; }
function wp_set_post_terms( $object_id, $terms, $taxonomy, $append = false ) { return true; }
function is_wp_error( $thing ) { return false; }
function wp_delete_post( $postid, $force_delete = false ) { return true; }
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
