<?php
// Added to core for debugging briefly
add_action('admin_notices', function() {
    global $submenu;
    if (isset($submenu['naboo-dashboard'])) {
        echo '<div class="notice notice-info"><pre>';
        echo esc_html(wp_json_encode($submenu['naboo-dashboard'], JSON_PRETTY_PRINT));
        echo '</pre></div>';
    }
});
