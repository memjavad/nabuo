<?php
namespace {
    /**
     * Test Health Optimizer scoring.
     */

    // Basic test harness
    $tests_passed = 0;
    $tests_failed = 0;

    function assert_equals($expected, $actual, $message = '') {
        global $tests_passed, $tests_failed;
        if ($expected === $actual) {
            $tests_passed++;
            echo "✅ PASS: $message\n";
        } else {
            $tests_failed++;
            echo "❌ FAIL: $message\n";
            echo "   Expected: " . print_r($expected, true) . "\n";
            echo "   Actual:   " . print_r($actual, true) . "\n";
        }
    }

    // Mock WordPress functions
    function check_ajax_referer($action, $query_arg = false, $die = true) { return true; }
    function current_user_can($capability) { return true; }
    function wp_send_json_error($data = null, $status_code = null) { throw new Exception("JSON Error: " . json_encode($data)); }
    function wp_send_json_success($data = null, $status_code = null) {
        echo json_encode($data);
        return true;
    }
    function __($text, $domain = 'default') { return $text; }
    function _e($text, $domain = 'default') { echo $text; }
    function esc_html($text) { return $text; }
    function esc_html_e($text, $domain = 'default') { echo $text; }
    function esc_attr($text) { return $text; }
}

namespace ArabPsychology\NabooDatabase\Admin\Health {
    class Health_Checker {
        public $mock_results = [];
        public function perform_scan() {
            return $this->mock_results;
        }
    }

    class Maintenance_Manager {
        public function __construct() {}
    }

    class System_Info_Renderer {
        public function __construct() {}
    }
}

namespace ArabPsychology\NabooDatabase\Admin {
    use ArabPsychology\NabooDatabase\Admin\Health\Health_Checker;

    require_once dirname(__DIR__, 2) . '/includes/admin/class-health-optimizer.php';

    function run_test() {
        global $tests_passed, $tests_failed;

        $optimizer = new Health_Optimizer('test_plugin', '1.0.0');

        $reflector = new \ReflectionClass($optimizer);
        $checkerProperty = $reflector->getProperty('checker');
        $checkerProperty->setAccessible(true);

        // Helper function to test score
        $get_score_for_results = function($results) use ($optimizer, $checkerProperty) {
            $mockChecker = new Health_Checker();
            $mockChecker->mock_results = $results;
            $checkerProperty->setValue($optimizer, $mockChecker);

            ob_start();
            try {
                $optimizer->ajax_health_check();
                $output = ob_get_clean();

                $data = json_decode($output, true);
                if (isset($data['html'])) {
                    $html = $data['html'];
                    // Extract score using regex from the JSON string
                    if (preg_match('/<div class="score-circle"[^>]*>\s*(\d+(?:\.\d+)?)\s*%/i', $html, $matches)) {
                        return (float)$matches[1];
                    }
                }

                return "Could not extract score from output";
            } catch (\Exception $e) {
                ob_end_clean();
                throw $e;
            } catch (\DivisionByZeroError $e) {
                ob_end_clean();
                throw $e;
            }
        };

        echo "Running tests for Health Optimizer Scoring...\n\n";

        // Test 1: All good
        $results = [
            ['status' => 'good', 'id' => 't1', 'label' => 't1', 'description' => 't1', 'value' => 't1'],
            ['status' => 'good', 'id' => 't2', 'label' => 't2', 'description' => 't2', 'value' => 't2'],
            ['status' => 'good', 'id' => 't3', 'label' => 't3', 'description' => 't3', 'value' => 't3']
        ];
        $score = $get_score_for_results($results);
        \assert_equals(100.0, $score, "All good statuses should return 100 score");

        // Test 2: Mixed (good, warning, critical)
        $results = [
            ['status' => 'good', 'id' => 't1', 'label' => 't1', 'description' => 't1', 'value' => 't1'],    // 2 points
            ['status' => 'warning', 'id' => 't2', 'label' => 't2', 'description' => 't2', 'value' => 't2'], // 1 point
            ['status' => 'critical', 'id' => 't3', 'label' => 't3', 'description' => 't3', 'value' => 't3'] // 0 points
        ];
        // Total points: 3. Max points: 3 * 2 = 6. Score: (3/6)*100 = 50.
        $score = $get_score_for_results($results);
        \assert_equals(50.0, $score, "Mixed statuses should calculate correct score");

        // Test 3: All critical
        $results = [
            ['status' => 'critical', 'id' => 't1', 'label' => 't1', 'description' => 't1', 'value' => 't1'],
            ['status' => 'critical', 'id' => 't2', 'label' => 't2', 'description' => 't2', 'value' => 't2']
        ];
        $score = $get_score_for_results($results);
        \assert_equals(0.0, $score, "All critical statuses should return 0 score");

        // Test 4: Empty array (Boundary condition)
        try {
            $score = $get_score_for_results([]);
            \assert_equals(100.0, $score, "Empty results should default to 100 score (or handle without crash)");
        } catch (\DivisionByZeroError $e) {
            global $tests_failed;
            $tests_failed++;
            echo "❌ FAIL: Empty results threw DivisionByZeroError\n";
        }

        echo "\nTests completed: $tests_passed passed, $tests_failed failed.\n";
        if ($tests_failed > 0) {
            exit(1);
        }
    }

    run_test();
}
