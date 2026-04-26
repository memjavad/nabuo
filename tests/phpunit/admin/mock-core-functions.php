<?php
// Mock WordPress Core Functions for PHPUnit

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action = -1) {
        return isset($GLOBALS['mock_verify_nonce_result']) ? $GLOBALS['mock_verify_nonce_result'] : true;
    }
}

if (!function_exists('wp_die')) {
    function wp_die($message = '', $title = '', $args = array()) {
        throw new \Exception("wp_die: " . $message);
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can($capability, ...$args) {
        return isset($GLOBALS['mock_current_user_can']) ? $GLOBALS['mock_current_user_can'] : true;
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        return $str;
    }
}

if (!function_exists('absint')) {
    function absint($maybeint) {
        return abs((int)$maybeint);
    }
}

if (!function_exists('admin_url')) {
    function admin_url($path = '', $scheme = 'admin') {
        return 'http://example.com/wp-admin/' . $path;
    }
}

if (!function_exists('add_query_arg')) {
    function add_query_arg() {
        $args = func_get_args();
        if (count($args) === 3) {
            return $args[2] . '&' . $args[0] . '=' . $args[1];
        }
        return '';
    }
}

if (!function_exists('wp_safe_redirect')) {
    function wp_safe_redirect($location, $status = 302, $x_redirect_by = 'WordPress') {
        throw new \Exception("wp_safe_redirect: " . $location);
    }
}

if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain = 'default') {
        return $text;
    }
}

if (!function_exists('esc_attr__')) {
    function esc_attr__($text, $domain = 'default') {
        return $text;
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text) {
        return $text;
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text) {
        return $text;
    }
}

if (!function_exists('esc_url')) {
    function esc_url($text) {
        return $text;
    }
}
