<?php
namespace ArabPsychology\NabooDatabase\Admin {
    if (!function_exists('ArabPsychology\NabooDatabase\Admin\error_log')) {
        $error_logs = [];
        function error_log($msg) {
            global $error_logs;
            $error_logs[] = $msg;
        }
    }
}

namespace {
    // Mock global WP functions
    $mock_post_meta = [];
    if (!function_exists('get_post_meta')) {
        function get_post_meta($post_id, $key, $single = false) {
            global $mock_post_meta;
            if (isset($mock_post_meta[$key])) {
                if ($mock_post_meta[$key] instanceof \Exception) {
                    throw $mock_post_meta[$key];
                }
                return $mock_post_meta[$key];
            }
            return '';
        }
    }

    $mock_post_terms = [];
    if (!function_exists('wp_get_post_terms')) {
        function wp_get_post_terms($post_id, $taxonomy) {
            global $mock_post_terms;
            if ($mock_post_terms instanceof \Exception) {
                throw $mock_post_terms;
            }
            return $mock_post_terms;
        }
    }

    if (!function_exists('is_wp_error')) {
        function is_wp_error($thing) {
            return false;
        }
    }

    // Require the class to test
    require_once __DIR__ . '/../includes/admin/class-scale-validation.php';

    use ArabPsychology\NabooDatabase\Admin\Scale_Validation;

    // Reset mocks and logs
    function reset_mocks() {
        global $mock_post_meta, $mock_post_terms;
        $mock_post_meta = [
            '_naboo_scale_items' => 10,
            '_naboo_scale_year' => 2020,
            '_naboo_scale_language' => 'English',
            '_naboo_scale_population' => 'Adults',
        ];
        $mock_post_terms = ['Category 1'];
        $GLOBALS['error_logs'] = [];
    }

    if (!class_exists('ArabPsychology\NabooDatabase\Admin\Scale_Validation')) {
        die("Class Scale_Validation not found.\n");
    }

    $validator = new Scale_Validation('naboodatabase', '1.0.0');
    $reflector = new \ReflectionClass($validator);
    $method = $reflector->getMethod('perform_validation');
    $method->setAccessible(true);

    $scale = new \stdClass();
    $scale->ID = 1;
    $scale->post_title = 'Valid Scale Title';
    $scale->post_content = str_repeat('This is a sufficiently long description for the scale content. ', 5);

    echo "Running Tests...\n";

    // Test 1: Happy path
    reset_mocks();
    $result = $method->invoke($validator, clone $scale);
    if ($result['is_valid'] !== true) {
        echo "❌ Test 1 Failed: Expected valid scale\n";
        print_r($result);
        exit(1);
    } else {
        echo "✅ Test 1 Passed: Happy path\n";
    }

    // Test 2: Validation failure
    reset_mocks();
    $invalid_scale = clone $scale;
    $invalid_scale->post_title = 'A';
    $result = $method->invoke($validator, $invalid_scale);
    if ($result['is_valid'] !== false || !in_array('Title is too short (minimum 3 characters)', $result['issues'])) {
        echo "❌ Test 2 Failed: Expected invalid scale due to short title\n";
        print_r($result);
        exit(1);
    } else {
        echo "✅ Test 2 Passed: Invalid title detected\n";
    }

    // Test 3: Exception path
    reset_mocks();
    $mock_post_meta['_naboo_scale_items'] = new \Exception("Database connection error");
    $result = $method->invoke($validator, clone $scale);

    if ($result['is_valid'] !== false) {
        echo "❌ Test 3 Failed: Expected invalid scale due to exception\n";
        print_r($result);
        exit(1);
    }

    $log_found = false;
    foreach ($GLOBALS['error_logs'] as $log) {
        if (strpos($log, 'Validation failed: Database connection error') !== false) {
            $log_found = true;
            break;
        }
    }

    if (!$log_found) {
        echo "❌ Test 3 Failed: error_log was not called with the expected message\n";
        print_r($GLOBALS['error_logs']);
        exit(1);
    } else {
        echo "✅ Test 3 Passed: Exception caught and logged\n";
    }

    echo "All tests passed successfully.\n";
}
