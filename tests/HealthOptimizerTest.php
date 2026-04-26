<?php
namespace ArabPsychology\NabooDatabase\Admin\Health {
    if (!class_exists('ArabPsychology\NabooDatabase\Admin\Health\Health_Checker')) {
        class Health_Checker {
            public function perform_scan() { return []; }
        }
    }
    if (!class_exists('ArabPsychology\NabooDatabase\Admin\Health\Maintenance_Manager')) {
        class Maintenance_Manager {}
    }
    if (!class_exists('ArabPsychology\NabooDatabase\Admin\Health\System_Info_Renderer')) {
        class System_Info_Renderer {}
    }
}

namespace ArabPsychology\NabooDatabase\Admin {

    class Mock_Maintenance_Manager {
        public $should_throw = false;
        public $error_message = 'Test error';
        public $success_message = 'Test success';

        public function execute_action($action) {
            if ($this->should_throw) {
                throw new \Exception($this->error_message);
            }
            return $this->success_message;
        }
    }

    class Mock_Health_Checker {
        public $results_to_return = [];
        public function perform_scan() {
            return $this->results_to_return;
        }
    }
}

namespace {
    // Mock necessary WordPress functions
    if (!function_exists('check_ajax_referer')) {
        function check_ajax_referer($action = -1, $query_arg = false, $die = true) { return true; }
    }

    $mock_current_user_can = true;
    if (!function_exists('current_user_can')) {
        function current_user_can($capability, ...$args) {
            global $mock_current_user_can;
            return $mock_current_user_can;
        }
    }

    if (!function_exists('wp_send_json_success')) {
        function wp_send_json_success($data = null, $status_code = null, $options = 0) {
            throw new \Exception(json_encode($data));
        }
    }
    if (!function_exists('wp_send_json_error')) {
        function wp_send_json_error($data = null, $status_code = null, $options = 0) {
            throw new \Exception(json_encode($data));
        }
    }

    if (!function_exists('sanitize_text_field')) {
        function sanitize_text_field($str) { return strip_tags(trim($str)); }
    }

    if (!function_exists('esc_attr')) {
        function esc_attr($text) { return $text; }
    }
    if (!function_exists('esc_html')) {
        function esc_html($text) { return $text; }
    }
    if (!function_exists('esc_html_e')) {
        function esc_html_e($text, $domain = 'default') { echo $text; }
    }
    if (!function_exists('add_submenu_page')) {
        function add_submenu_page() {}
    }
    if (!function_exists('add_action')) {
        function add_action() {}
    }
    if (!function_exists('wp_create_nonce')) {
        function wp_create_nonce() { return 'nonce'; }
    }

    // Ensure the class we are testing is loaded
    require_once __DIR__ . '/../includes/admin/class-health-optimizer.php';
}

namespace Tests {

    use PHPUnit\Framework\TestCase;
    use ArabPsychology\NabooDatabase\Admin\Health_Optimizer;
    use ArabPsychology\NabooDatabase\Admin\Mock_Health_Checker;
    use ArabPsychology\NabooDatabase\Admin\Mock_Maintenance_Manager;

    class HealthOptimizerTest extends TestCase {

        private $optimizer;
        private $mock_checker;
        private $mock_maintenance;

        protected function setUp(): void {
            parent::setUp();

            $this->optimizer = new Health_Optimizer('test-plugin', '1.0.0');
            $this->mock_checker = new Mock_Health_Checker();
            $this->mock_maintenance = new Mock_Maintenance_Manager();

            // Use reflection to set the private checker property
            $reflector = new \ReflectionClass($this->optimizer);
            $property = $reflector->getProperty('checker');
            $property->setAccessible(true);
            $property->setValue($this->optimizer, $this->mock_checker);

            $prop_maintenance = $reflector->getProperty('maintenance');
            $prop_maintenance->setAccessible(true);
            $prop_maintenance->setValue($this->optimizer, $this->mock_maintenance);
        }



        protected function tearDown(): void {
            unset($_POST['maintenance_action']);
            parent::tearDown();
        }

        public function test_ajax_health_optimize_success() {
            global $mock_current_user_can;
            $mock_current_user_can = true;
            $_POST['maintenance_action'] = 'some_action';
            $this->mock_maintenance->should_throw = false;
            $this->mock_maintenance->success_message = 'Optimization complete.';

            try {
                $this->optimizer->ajax_health_optimize();
                $this->fail('Expected wp_send_json_success exception.');
            } catch (\Exception $e) {
                $this->assertStringContainsString('Optimization complete.', $e->getMessage());
            }
        }

        public function test_ajax_health_optimize_error_handling() {
            global $mock_current_user_can;
            $mock_current_user_can = true;
            $_POST['maintenance_action'] = 'some_action';
            $this->mock_maintenance->should_throw = true;
            $this->mock_maintenance->error_message = 'Optimization failed.';

            try {
                $this->optimizer->ajax_health_optimize();
                $this->fail('Expected wp_send_json_error exception.');
            } catch (\Exception $e) {
                $this->assertStringContainsString('Optimization failed.', $e->getMessage());
            }
        }

        public function test_ajax_health_optimize_unauthorized() {
            global $mock_current_user_can;
            $mock_current_user_can = false;

            try {
                $this->optimizer->ajax_health_optimize();
                $this->fail('Expected wp_send_json_error exception.');
            } catch (\Exception $e) {
                $this->assertStringContainsString('Unauthorized', $e->getMessage());
            }

            // Restore for other tests
            $mock_current_user_can = true;
        }

        public function test_score_calculation_empty_results() {
            $this->mock_checker->results_to_return = [];

            try {
                $this->optimizer->ajax_health_check();
                $this->fail('Expected wp_send_json_success exception.');
            } catch (\Exception $e) {
                $response = json_decode($e->getMessage(), true);
                $this->assertArrayHasKey('html', $response);
                // Score should be 100% when no results
                $this->assertStringContainsString('100%', $response['html']);
            }
        }

        public function test_score_calculation_all_good() {
            $this->mock_checker->results_to_return = [
                ['status' => 'good', 'label' => 'Test 1', 'value' => 'OK'],
                ['status' => 'good', 'label' => 'Test 2', 'value' => 'OK'],
            ];

            try {
                $this->optimizer->ajax_health_check();
                $this->fail('Expected wp_send_json_success exception.');
            } catch (\Exception $e) {
                $response = json_decode($e->getMessage(), true);
                $this->assertArrayHasKey('html', $response);
                // Score should be 100% when all are good
                $this->assertStringContainsString('100%', $response['html']);
                $this->assertStringNotContainsString('Optimize Everything Now', $response['html']);
            }
        }

        public function test_score_calculation_mixed_results() {
            $this->mock_checker->results_to_return = [
                ['status' => 'good', 'label' => 'Test 1', 'value' => 'OK'], // 2 points
                ['status' => 'warning', 'label' => 'Test 2', 'value' => 'Warn'], // 1 point
                // Total max points = 4. Earned = 3. Score = 3/4 = 75%.
            ];

            try {
                $this->optimizer->ajax_health_check();
                $this->fail('Expected wp_send_json_success exception.');
            } catch (\Exception $e) {
                $response = json_decode($e->getMessage(), true);
                $this->assertArrayHasKey('html', $response);
                $this->assertStringContainsString('75%', $response['html']);
                $this->assertStringContainsString('Optimize Everything Now', $response['html']);
            }
        }

        public function test_score_calculation_all_danger() {
            $this->mock_checker->results_to_return = [
                ['status' => 'danger', 'label' => 'Test 1', 'value' => 'Bad'], // 0 points
                ['status' => 'danger', 'label' => 'Test 2', 'value' => 'Bad'], // 0 points
                // Total max points = 4. Earned = 0. Score = 0/4 = 0%.
            ];

            try {
                $this->optimizer->ajax_health_check();
                $this->fail('Expected wp_send_json_success exception.');
            } catch (\Exception $e) {
                $response = json_decode($e->getMessage(), true);
                $this->assertArrayHasKey('html', $response);
                $this->assertStringContainsString('0%', $response['html']);
                $this->assertStringContainsString('Optimize Everything Now', $response['html']);
            }
        }
    }
}
