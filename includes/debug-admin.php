<?php
// Added to core for debugging briefly
add_action('admin_notices', function() {
    global $submenu;
    if (isset($submenu['naboo-dashboard'])) {
        error_log(print_r($submenu['naboo-dashboard'], true));
    }
});
