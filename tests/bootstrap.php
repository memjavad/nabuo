<?php

// Define WordPress functions we need to mock
if (!function_exists('get_current_user_id')) {
    function get_current_user_id() {
        global $mock_current_user_id;
        return $mock_current_user_id !== null ? $mock_current_user_id : 0;
    }
}

// We also need WP_REST_Request and WP_REST_Response mocks since the classes might not be loaded
if (!class_exists('WP_REST_Request')) {
    class WP_REST_Request {
        private $params = [];

        public function set_param($key, $value) {
            $this->params[$key] = $value;
        }

        public function get_param($key) {
            return isset($this->params[$key]) ? $this->params[$key] : null;
        }
    }
}

if (!class_exists('WP_REST_Response')) {
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
}

// Require the file we're testing
require_once __DIR__ . '/../includes/public/class-scale-recommendation-engine.php';
