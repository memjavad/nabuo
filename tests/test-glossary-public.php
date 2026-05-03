<?php

require_once __DIR__ . '/mock-wp-core.php';

function wp_enqueue_style($handle, $src = '', $deps = array(), $ver = false, $media = 'all') {
    global $enqueued_styles;
    if (!isset($enqueued_styles)) {
        $enqueued_styles = [];
    }
    $enqueued_styles[] = $handle;
}

function wp_enqueue_script($handle, $src = '', $deps = array(), $ver = false, $in_footer = false) {
    global $enqueued_scripts;
    if (!isset($enqueued_scripts)) {
        $enqueued_scripts = [];
    }
    $enqueued_scripts[] = $handle;
}

function plugin_dir_url($file) {
    return 'http://example.com/wp-content/plugins/naboodatabase/includes/public/';
}

function has_shortcode($content, $tag) {
    return strpos($content, '[' . $tag) !== false;
}

function is_post_type_archive($post_types) {
    global $mock_is_post_type_archive;
    return $mock_is_post_type_archive ?? false;
}

function is_singular($post_types = '') {
    global $mock_is_singular;
    return $mock_is_singular ?? false;
}

function get_option($option, $default = false) {
    return $default;
}

function sanitize_hex_color($color) {
    return $color;
}

function absint($maybeint) {
    return abs(intval($maybeint));
}

function esc_html__($text, $domain = 'default') {
    return $text;
}

function esc_attr__($text, $domain = 'default') {
    return $text;
}

function esc_attr_e($text, $domain = 'default') {
    echo $text;
}

function esc_html_e($text, $domain = 'default') {
    echo $text;
}

function esc_attr($text) {
    return $text;
}

function esc_html($text) {
    return $text;
}

function wp_localize_script($handle, $object_name, $l10n) {
    global $localized_scripts;
    if (!isset($localized_scripts)) {
        $localized_scripts = [];
    }
    $localized_scripts[$handle] = $l10n;
}

function shortcode_atts($pairs, $atts, $shortcode = '') {
    $out = array();
    foreach ($pairs as $name => $default) {
        if (array_key_exists($name, $atts)) {
            $out[$name] = $atts[$name];
        } else {
            $out[$name] = $default;
        }
    }
    return $out;
}

function sanitize_text_field($str) {
    return $str;
}

if (!function_exists('esc_url_raw')) {
    function esc_url_raw($url) {
        return $url;
    }
}

if (!function_exists('rest_url')) {
    function rest_url($path = '') {
        return 'http://example.com/wp-json/' . $path;
    }
}

if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action = -1) {
        return 'mocked_nonce';
    }
}


if (!class_exists('WP_Post')) {
    class WP_Post {}
}


require_once __DIR__ . '/../includes/public/class-glossary-public.php';

use ArabPsychology\NabooDatabase\Public\Glossary_Public;

echo "Testing Glossary_Public...\n";

$failures = 0;

function assert_test($condition, $message) {
    global $failures;
    if ($condition) {
        echo "PASS: $message\n";
    } else {
        echo "FAIL: $message\n";
        $failures++;
    }
}

// 1. Test enqueue_assets (in this file named enqueue_assets instead of enqueue_styles)
function test_enqueue_assets() {
    global $enqueued_styles, $enqueued_scripts, $localized_scripts, $post, $mock_is_post_type_archive, $mock_is_singular;

    $glossary = new Glossary_Public('naboodatabase', '1.0.0');

    // Reset global state
    $enqueued_styles = [];
    $enqueued_scripts = [];
    $localized_scripts = [];
    $mock_is_post_type_archive = false;
    $mock_is_singular = false;

    // Case 1: Wrong post type, no shortcode, not archive/singular
    $post = new stdClass(); // Not WP_Post
    $glossary->enqueue_assets();
    assert_test(empty($enqueued_styles), "enqueue_assets: returns early if not WP_Post and not archive/singular");

    // Case 2: WP_Post without shortcode, not archive/singular
    $post = new WP_Post();
    $post->post_content = 'Some content without shortcode';
    $glossary->enqueue_assets();
    assert_test(empty($enqueued_styles), "enqueue_assets: returns early if no shortcode and not archive/singular");

    // Case 3: WP_Post with shortcode
    $post->post_content = 'Some content [naboo_glossary] here';
    $glossary->enqueue_assets();
    assert_test(in_array('naboodatabase-glossary', $enqueued_styles), "enqueue_assets: enqueues style when shortcode is present");
    assert_test(in_array('naboodatabase-glossary', $enqueued_scripts), "enqueue_assets: enqueues script when shortcode is present");
    assert_test(isset($localized_scripts['naboodatabase-glossary']), "enqueue_assets: localizes script when shortcode is present");

    // Case 4: Not WP_Post but is_post_type_archive
    $enqueued_styles = [];
    $post = null;
    $mock_is_post_type_archive = true;
    $glossary->enqueue_assets();
    assert_test(in_array('naboodatabase-glossary', $enqueued_styles), "enqueue_assets: enqueues style when on archive page");
}

function test_render_shortcode() {
    $glossary = new Glossary_Public('naboodatabase', '1.0.0');

    // Empty atts
    $atts = [];
    $output = $glossary->render_shortcode($atts);

    assert_test(strpos($output, 'id="naboo-glossary-app"') !== false, "render_shortcode: renders the main wrapper");
    assert_test(strpos($output, 'layout-grid') !== false, "render_shortcode: uses default layout (grid)");
    assert_test(strpos($output, 'data-post-type="naboo_glossary"') !== false, "render_shortcode: uses default post type");

    // Custom atts
    $atts = [
        'layout' => 'list',
        'post_type' => 'custom_type',
        'per_page' => 20
    ];
    $output = $glossary->render_shortcode($atts);
    assert_test(strpos($output, 'layout-list') !== false, "render_shortcode: respects layout attribute");
    assert_test(strpos($output, 'data-post-type="custom_type"') !== false, "render_shortcode: respects post_type attribute");
    assert_test(strpos($output, 'data-per-page="20"') !== false, "render_shortcode: respects per_page attribute");
}

test_enqueue_assets();
test_render_shortcode();

if ($failures > 0) {
    exit(1);
} else {
    echo "All tests passed.\n";
}
