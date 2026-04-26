<?php
namespace ArabPsychology\NabooDatabase\Admin\Database_Indexer {
    if (!class_exists('ArabPsychology\NabooDatabase\Admin\Database_Indexer')) {
        class Database_Indexer {
            public static function sync_all_scales() {}
        }
    }
}
namespace ArabPsychology\NabooDatabase\Core {
    if (!class_exists('ArabPsychology\NabooDatabase\Core\Security_Logger')) {
        class Security_Logger {
            public function log() {}
        }
    }
}

namespace {
    // Mocking the required global arrays and functions
    $GLOBALS['mock_crons'] = [];
    $GLOBALS['mock_options'] = [];
    $GLOBALS['mock_scheduled'] = [];
    $GLOBALS['mock_actions_called'] = [];
    $GLOBALS['mock_cron_array_saved'] = null;

    if (!function_exists('_get_cron_array')) {
        function _get_cron_array() {
            return $GLOBALS['mock_crons'];
        }
    }
    if (!function_exists('_set_cron_array')) {
        function _set_cron_array($crons) {
            $GLOBALS['mock_cron_array_saved'] = $crons;
        }
    }
    if (!function_exists('wp_next_scheduled')) {
        function wp_next_scheduled($hook) {
            return isset($GLOBALS['mock_scheduled'][$hook]) && $GLOBALS['mock_scheduled'][$hook] !== null;
        }
    }
    if (!function_exists('wp_schedule_event')) {
        function wp_schedule_event($timestamp, $recurrence, $hook) {
            $GLOBALS['mock_scheduled'][$hook] = $recurrence;
        }
    }
    if (!function_exists('get_option')) {
        function get_option($option, $default = false) {
            return isset($GLOBALS['mock_options'][$option]) ? $GLOBALS['mock_options'][$option] : $default;
        }
    }
    if (!function_exists('do_action')) {
        function do_action($tag, ...$args) {
            $GLOBALS['mock_actions_called'][] = ['tag' => $tag, 'args' => $args];
        }
    }
    if (!function_exists('__')) {
        function __($text, $domain = 'default') {
            return $text;
        }
    }
}
