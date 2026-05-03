<?php

namespace {
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

    function assert_true($condition, $message = '') {
        assert_equals(true, $condition, $message);
    }

    // Global test state for mocks
    $GLOBALS['mock_state'] = [
        'submenu_pages' => [],
        'current_user_can' => true,
        'wp_die_called' => false,
        'post_meta' => [],
        'term_lists' => []
    ];

    // Mock WP functions
    function add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function = '', $position = null) {
        $GLOBALS['mock_state']['submenu_pages'][] = compact('parent_slug', 'page_title', 'menu_title', 'capability', 'menu_slug', 'function', 'position');
    }

    function __($text, $domain = 'default') { return $text; }
    function _x($text, $context, $domain = 'default') { return $text; }
    function esc_html($text) { return $text; }
    function esc_html__($text, $domain = "default") { return $text; }

    function current_user_can($capability) {
        return $GLOBALS['mock_state']['current_user_can'];
    }

    function wp_die($message = '', $title = '', $args = []) {
        $GLOBALS['mock_state']['wp_die_called'] = true;
        throw new \Exception("wp_die called with message: " . (is_string($message) ? $message : ''));
    }

    function get_post_meta($post_id, $key = '', $single = false) {
        if (isset($GLOBALS['mock_state']['post_meta'][$post_id][$key])) {
            return $GLOBALS['mock_state']['post_meta'][$post_id][$key];
        }
        return '';
    }

    function get_the_term_list($post_id, $taxonomy, $before = '', $sep = '', $after = '') {
        if (isset($GLOBALS['mock_state']['term_lists'][$post_id][$taxonomy])) {
            return $before . implode($sep, $GLOBALS['mock_state']['term_lists'][$post_id][$taxonomy]) . $after;
        }
        return '';
    }
}

namespace ArabPsychology\NabooDatabase\Admin\Glossary {
    class Glossary_Metabox_Handler {
        public $registered_metaboxes = [];
        public $saved_data = [];

        public function register_metaboxes($screen) {
            $this->registered_metaboxes[] = $screen;
        }

        public function save_data($post_id) {
            $this->saved_data[] = $post_id;
        }
    }

    class Glossary_Renderer {
        public $rendered_instructions = 0;
        public $rendered_settings = 0;

        public function render_instructions_page() {
            $this->rendered_instructions++;
        }

        public function render_settings_page() {
            $this->rendered_settings++;
        }
    }
}

namespace ArabPsychology\NabooDatabase\Admin {
    require_once dirname(__DIR__, 2) . '/includes/admin/class-glossary-admin.php';

    function run_test() {
        global $tests_passed, $tests_failed, $mock_state;

        echo "Running tests for Glossary_Admin...\n\n";

        // Test 1: __construct and object creation
        $admin = new Glossary_Admin('test-plugin', '1.0.0');
        \assert_true($admin instanceof Glossary_Admin, "Instantiates successfully");

        // Use reflection to access private properties
        $reflector = new \ReflectionClass($admin);

        // Test 2: add_admin_menu
        $admin->add_admin_menu();
        \assert_equals(1, count($mock_state['submenu_pages']), "add_submenu_page called once");
        \assert_equals('naboo-dashboard', $mock_state['submenu_pages'][0]['parent_slug'], "Parent slug is correct");
        \assert_equals('naboo-glossary-settings', $mock_state['submenu_pages'][0]['menu_slug'], "Menu slug is correct");

        // Test 3: render_instructions_page
        $renderer_prop = $reflector->getProperty('renderer');
        $renderer_prop->setAccessible(true);
        $renderer = $renderer_prop->getValue($admin);

        $admin->render_instructions_page();
        \assert_equals(1, $renderer->rendered_instructions, "renderer->render_instructions_page called");

        // Test 4: render_settings_page (authorized)
        $mock_state['current_user_can'] = true;
        $mock_state['wp_die_called'] = false;
        $admin->render_settings_page();
        \assert_equals(1, $renderer->rendered_settings, "renderer->render_settings_page called when authorized");
        \assert_equals(false, $mock_state['wp_die_called'], "wp_die not called when authorized");

        // Test 5: render_settings_page (unauthorized)
        $mock_state['current_user_can'] = false;
        $mock_state['wp_die_called'] = false;
        try {
            $admin->render_settings_page();
            \assert_equals(true, false, "Should have thrown exception from wp_die");
        } catch (\Exception $e) {
            \assert_equals(true, $mock_state['wp_die_called'], "wp_die called when unauthorized");
        }

        // Test 6: manage_columns
        $initial_columns = ['cb' => 'Checkbox', 'date' => 'Date'];
        $new_columns = $admin->manage_columns($initial_columns);
        \assert_equals('Checkbox', $new_columns['cb'], "Preserves cb column");
        \assert_equals('Date', $new_columns['date'], "Preserves date column");
        \assert_equals('Term (English)', $new_columns['title'], "Adds title column");
        \assert_equals('Arabic Term', $new_columns['arabic_term'], "Adds arabic_term column");
        \assert_equals('Category', $new_columns['glossary_category'], "Adds glossary_category column");

        // Test 7: manage_custom_column
        $mock_state['post_meta'][123]['_naboo_glossary_arabic'] = 'Test Arabic';
        $mock_state['term_lists'][123]['glossary_category'] = ['Cat1', 'Cat2'];

        ob_start();
        $admin->manage_custom_column('arabic_term', 123);
        $output = ob_get_clean();
        \assert_equals('Test Arabic', $output, "Outputs arabic term correctly");

        ob_start();
        $admin->manage_custom_column('glossary_category', 123);
        $output = ob_get_clean();
        \assert_equals('Cat1, Cat2', $output, "Outputs categories correctly");

        ob_start();
        $admin->manage_custom_column('other_column', 123);
        $output = ob_get_clean();
        \assert_equals('', $output, "Outputs nothing for unknown column");

        // Test 8: add_meta_boxes and save_meta_box_data
        $metabox_prop = $reflector->getProperty('metabox_handler');
        $metabox_prop->setAccessible(true);
        $metabox_handler = $metabox_prop->getValue($admin);

        $admin->add_meta_boxes();
        \assert_equals(['naboo_glossary'], $metabox_handler->registered_metaboxes, "register_metaboxes called with correct screen");

        $admin->save_meta_box_data(456);
        \assert_equals([456], $metabox_handler->saved_data, "save_data called with correct post ID");

        echo "\nTests completed: $tests_passed passed, $tests_failed failed.\n";
        if ($tests_failed > 0) {
            exit(1);
        }
    }

    run_test();
}
