<?php
namespace {
    ini_set('assert.exception', 1);

    // Mock wp_remote_get responses
    $mock_wp_remote_get_response = null;

    if (!function_exists('wp_remote_get')) {
        function wp_remote_get($url, $args) {
            global $mock_wp_remote_get_response;
            return $mock_wp_remote_get_response;
        }
    }

    if (!class_exists('WP_Error')) {
        class WP_Error {
            private $message;
            public function __construct($message) {
                $this->message = $message;
            }
            public function get_error_message() {
                return $this->message;
            }
        }
    }

    if (!function_exists('is_wp_error')) {
        function is_wp_error($thing) {
            return $thing instanceof WP_Error;
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
}

namespace ArabPsychology\NabooDatabase\Admin\Health {
    require_once dirname(__DIR__, 2) . '/includes/admin/health/class-health-checker.php';

    function run_test() {
        global $mock_wp_remote_get_response;

        $checker = new Health_Checker();

        echo "Running tests for check_api_connectivity...\n\n";

        // Test 1: Successful connection (HTTP 200)
        $mock_wp_remote_get_response = array(
            'response' => array('code' => 200)
        );
        $result = $checker->check_api_connectivity();
        assert($result['success'] === true, "Successful connection should return success => true");
        assert($result['message'] === 'Successfully reached arabpsychology.com', "Successful connection message");
        echo "✅ PASS: Successful connection\n";

        // Test 2: Failed connection (WP_Error)
        $mock_wp_remote_get_response = new \WP_Error('Connection timed out');
        $result = $checker->check_api_connectivity();
        assert($result['success'] === false, "WP_Error should return success => false");
        assert($result['message'] === 'Connection timed out', "WP_Error message should match");
        echo "✅ PASS: Failed connection (WP_Error)\n";

        // Test 3: Non-200 HTTP code
        $mock_wp_remote_get_response = array(
            'response' => array('code' => 500)
        );
        $result = $checker->check_api_connectivity();
        assert($result['success'] === false, "Non-200 status should return success => false");
        assert($result['message'] === 'Server returned code 500', "Non-200 message should include status code");
        echo "✅ PASS: Non-200 HTTP code\n";

        echo "\nTests completed successfully.\n";
    }

    run_test();
}
