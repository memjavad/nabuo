<?php
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/wp-admin/';
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/admin.php';

// Trigger the admin menu hook so that classes hook in
do_action('admin_menu');

global $submenu;
if (isset($submenu['naboo-dashboard'])) {
    foreach($submenu['naboo-dashboard'] as $k => $v) {
        echo $k . ' => ' . $v[0] . "\n";
    }
} else {
    echo "No naboo-dashboard found in submenu array.\n";
}
