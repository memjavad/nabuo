<?php

namespace ArabPsychology\NabooDatabase\Admin\Batch_AI {
    if (!class_exists('ArabPsychology\NabooDatabase\Admin\Batch_AI\Batch_AI_Remote_Sync')) {
        require_once __DIR__ . '/../includes/admin/batch-ai/class-batch-ai-remote-sync.php';
    }

    $mock_url = '';
    $mock_args = [];
    $mock_return_error = false;

    function wp_safe_remote_get($url, $args) {
        global $mock_url, $mock_args, $mock_return_error;
        $mock_url = $url;
        $mock_args = $args;

        if ($mock_return_error) {
            return new \WP_Error('test_error', 'Test error message');
        }

        return [
            'response' => ['code' => 200],
            'body' => 'Success body'
        ];
    }
}

namespace {
    if (!class_exists('WP_Error')) {
        class WP_Error {
            public $code;
            public $message;
            public function __construct($code, $message) {
                $this->code = $code;
                $this->message = $message;
            }
            public function get_error_message() {
                return $this->message;
            }
        }
    }

    if (!function_exists('is_wp_error')) {
        function is_wp_error($thing) {
            return ($thing instanceof WP_Error);
        }
    }

    if (!function_exists('wp_remote_retrieve_response_code')) {
        function wp_remote_retrieve_response_code($response) {
            if (is_array($response) && isset($response['response']['code'])) {
                return $response['response']['code'];
            }
            return '';
        }
    }

    if (!function_exists('wp_remote_retrieve_body')) {
        function wp_remote_retrieve_body($response) {
            if (is_array($response) && isset($response['body'])) {
                return $response['body'];
            }
            return '';
        }
    }

    echo "Running Batch_AI_Remote_Sync Tests...\n";

    $sync = new \ArabPsychology\NabooDatabase\Admin\Batch_AI\Batch_AI_Remote_Sync();

    // Test 1: Successful response
    global $mock_url, $mock_args, $mock_return_error;
    $mock_return_error = false;
    $res = $sync->make_raw_curl_request('http://example.com', 'test-token', 123);

    if ($res['status'] === 200 && $res['body'] === 'Success body' && $res['error'] === '') {
        echo "PASS: Successful wp_safe_remote_get call\n";
    } else {
        echo "FAIL: Successful wp_safe_remote_get call\n";
        print_r($res);
        exit(1);
    }

    if ($mock_url === 'http://example.com' && $mock_args['timeout'] === 123 && $mock_args['headers']['X-Naboo-Token'] === 'test-token') {
        echo "PASS: Arguments passed correctly\n";
    } else {
        echo "FAIL: Arguments passed incorrectly\n";
        print_r($mock_args);
        exit(1);
    }

    // Test 2: Error response
    $mock_return_error = true;
    $res = $sync->make_raw_curl_request('http://example.com', 'test-token');

    if ($res['status'] === 0 && $res['body'] === '' && $res['error'] === 'Test error message') {
        echo "PASS: Error wp_safe_remote_get call\n";
    } else {
        echo "FAIL: Error wp_safe_remote_get call\n";
        print_r($res);
        exit(1);
    }

    echo "All tests passed.\n";
}
