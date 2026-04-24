<?php
// Mock WordPress functions and classes

function get_role( $role ) {
    return false;
}

function add_role( $role, $display_name, $capabilities ) {
    return true;
}

function __( $text, $domain ) {
    return $text;
}

class WP_REST_Response {
    public $data;
    public $status;
    public function __construct( $data = null, $status = 200 ) {
        $this->data = $data;
        $this->status = $status;
    }
}

// Load the class
require_once __DIR__ . '/../includes/admin/class-user-role-management.php';
