<?php
namespace ArabPsychology\NabooDatabase\Public {
    // We override WP functions inside the namespace to shadow the global ones safely.
    $mock_options = [];
    function get_option($key, $default = false) {
        global $mock_options;
        return isset($mock_options[$key]) ? $mock_options[$key] : $default;
    }
    function sanitize_text_field($str) { return $str; }
    function absint($val) { return abs((int)$val); }
    function sanitize_hex_color($color) { return $color; }
    function get_bloginfo($show) { return 'Test Blog'; }
    function __($text, $domain) { return $text; }
    function plugin_dir_url($file) { return 'https://example.com/plugin/'; }
    function rest_url($path) { return 'https://example.com/wp-json/' . $path; }
    function esc_url_raw($url) { return $url; }
    function wp_create_nonce($action) { return 'testnonce'; }
    function get_locale() { return 'en_US'; }
    function esc_html($text) { return htmlspecialchars((string)$text); }
    function esc_attr($text) { return htmlspecialchars((string)$text); }
    function esc_url($url) { return htmlspecialchars((string)$url); }
    function esc_js($text) { return htmlspecialchars((string)$text); }
    function home_url($path) { return 'https://example.com' . $path; }
    function wp_json_encode($data) { return json_encode($data); }
    function esc_attr_e($text, $domain) { echo htmlspecialchars((string)$text); }
    function esc_html_e($text, $domain) { echo htmlspecialchars((string)$text); }

    $mock_query_vars = [];
    function get_query_var($var, $default = '') {
        global $mock_query_vars;
        return isset($mock_query_vars[$var]) ? $mock_query_vars[$var] : $default;
    }

    // Since header() cannot be suppressed inside ob_start easily if it throws warning,
    // we override header() in this namespace.
    function header($string, $replace = true, $http_response_code = 0) {}

    // Mock functions used but not previously defined
    function add_filter($tag, $function_to_add, $priority = 10, $accepted_args = 1) {}
    function add_action($tag, $function_to_add, $priority = 10, $accepted_args = 1) {}
    function delete_option($option) { return true; }

    require_once __DIR__ . '/mock-wp-classes.php';
    require_once __DIR__ . '/../includes/public/class-scale-index.php';
}

namespace {
    use ArabPsychology\NabooDatabase\Public\Scale_Index;

    class Test_Scale_Index {
        private $instance;
        private $default_options;
        private $passed = true;

        public function __construct() {
            $this->instance = new Scale_Index('test-plugin', '1.0.0');
            $this->default_options = [
                'naboo_scale_index_post_type' => 'psych_scale',
                'naboo_scale_index_meta_label' => '',
                'naboo_glossary_layout' => 'grid',
                'naboo_glossary_per_page' => 50,
                'naboo_glossary_pagination' => 'infinite',
                'naboo_glossary_accent_color' => '#5b5ef4',
                'naboo_glossary_card_radius' => 12,
                'naboo_scale_index_title' => 'Test Scale Index',
                'naboo_custom_logo_url' => 'https://example.com/logo.png',
                'naboo_scale_index_subtitle' => 'Test Subtitle',
                'naboo_glossary_show_excerpt' => 1,
                'naboo_glossary_show_secondary' => 1,
                'naboo_glossary_show_letter_index' => 1,
            ];
        }

        public function run() {
            echo "Running Scale_Index tests...\n";
            $this->test_fallback_meta_label();
            $this->test_custom_meta_label();
            $this->test_no_fallback_for_other_post_type();
            $this->test_layout_config();
            $this->test_maybe_render_index_skips_when_no_query_var();
            $this->test_maybe_render_index_skips_when_disabled();

            return $this->passed;
        }

        private function get_method($name) {
            $reflection = new \ReflectionClass($this->instance);
            $method = $reflection->getMethod($name);
            $method->setAccessible(true);
            return $method;
        }

        private function test_fallback_meta_label() {
            global $mock_options;
            $mock_options = $this->default_options;
            $mock_options['naboo_scale_index_meta_label'] = '';
            $mock_options['naboo_scale_index_post_type'] = 'psych_scale';

            $method = $this->get_method('render_index_page');
            ob_start();
            $method->invoke($this->instance);
            $output = ob_get_clean();

            if (strpos($output, 'data-meta-label="Author"') !== false) {
                echo "PASS: Fallback meta_label 'Author' used for 'psych_scale'.\n";
            } else {
                echo "FAIL: Expected fallback meta_label 'Author' for 'psych_scale'.\n";
                $this->passed = false;
            }
        }

        private function test_custom_meta_label() {
            global $mock_options;
            $mock_options = $this->default_options;
            $mock_options['naboo_scale_index_meta_label'] = 'Custom Label';

            $method = $this->get_method('render_index_page');
            ob_start();
            $method->invoke($this->instance);
            $output = ob_get_clean();

            if (strpos($output, 'data-meta-label="Custom Label"') !== false) {
                echo "PASS: Custom meta_label used correctly.\n";
            } else {
                echo "FAIL: Expected custom meta_label 'Custom Label'.\n";
                $this->passed = false;
            }
        }

        private function test_no_fallback_for_other_post_type() {
            global $mock_options;
            $mock_options = $this->default_options;
            $mock_options['naboo_scale_index_meta_label'] = '';
            $mock_options['naboo_scale_index_post_type'] = 'other_type';

            $method = $this->get_method('render_index_page');
            ob_start();
            $method->invoke($this->instance);
            $output = ob_get_clean();

            if (strpos($output, 'data-meta-label=""') !== false) {
                echo "PASS: No fallback meta_label for 'other_type'.\n";
            } else {
                echo "FAIL: Expected empty meta_label for 'other_type'.\n";
                $this->passed = false;
            }
        }

        private function test_layout_config() {
            global $mock_options;
            $mock_options = $this->default_options;
            $mock_options['naboo_glossary_layout'] = 'list';

            $method = $this->get_method('render_index_page');
            ob_start();
            $method->invoke($this->instance);
            $output = ob_get_clean();

            if (strpos($output, 'layout-list') !== false && strpos($output, 'window.nabooGlossaryConfig =') !== false) {
                echo "PASS: Layout class 'layout-list' and JS config rendered.\n";
            } else {
                echo "FAIL: Expected layout class 'layout-list' and JS config.\n";
                $this->passed = false;
            }
        }

        private function test_maybe_render_index_skips_when_no_query_var() {
            global $mock_query_vars;
            $mock_query_vars['naboo_scale_index'] = false;

            $method = $this->get_method('maybe_render_index');
            ob_start();
            $method->invoke($this->instance);
            $output = ob_get_clean();

            if ($output === '') {
                echo "PASS: maybe_render_index skips when query var is missing.\n";
            } else {
                echo "FAIL: Expected no output when query var missing.\n";
                $this->passed = false;
            }
        }

        private function test_maybe_render_index_skips_when_disabled() {
            global $mock_options, $mock_query_vars;
            $mock_query_vars['naboo_scale_index'] = true;
            $mock_options['naboo_scale_index_enabled'] = 0;

            $method = $this->get_method('maybe_render_index');
            ob_start();
            $method->invoke($this->instance);
            $output = ob_get_clean();

            if ($output === '') {
                echo "PASS: maybe_render_index skips when index feature is disabled.\n";
            } else {
                echo "FAIL: Expected no output when disabled.\n";
                $this->passed = false;
            }
        }
    }

    $tester = new Test_Scale_Index();
    if ($tester->run()) {
        exit(0);
    } else {
        exit(1);
    }
}
