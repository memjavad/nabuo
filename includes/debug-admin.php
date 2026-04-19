<?php
// Added to core for debugging briefly
add_action('admin_notices', function() {
    global $submenu;
    if (isset($submenu['naboo-dashboard'])) {
        echo '<div class="notice notice-info"><pre>';
        echo esc_html(print_r($submenu['naboo-dashboard'], true));
        echo '</pre></div>';
    }
});
