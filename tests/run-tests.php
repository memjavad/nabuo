<?php
// Autoloader/mock setup for testing without framework
require_once __DIR__ . '/../includes/admin/import/class-import-processor.php';
use ArabPsychology\NabooDatabase\Admin\Import\Import_Processor;

echo "Running Test Suite...\n";

function test_empty_file_upload() {
    $processor = new Import_Processor();
    $_FILES = []; // Simulate empty file upload

    try {
        $processor->validate_file_upload();
        echo "FAIL: test_empty_file_upload - Expected Exception was not thrown.\n";
        exit(1);
    } catch (\Exception $e) {
        if ($e->getMessage() === 'No file uploaded.') {
            echo "PASS: test_empty_file_upload\n";
        } else {
            echo "FAIL: test_empty_file_upload - Unexpected exception message: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
}

test_empty_file_upload();

require_once __DIR__ . '/../includes/class-loader.php';

function test_loader_add_methods() {
    $loader = new \ArabPsychology\NabooDatabase\Loader();
    $loader->add_action('init', 'my_component', 'my_callback', 10, 1);
    $loader->add_filter('the_content', 'my_component', 'my_callback', 20, 2);
    $loader->add_shortcode('my_tag', 'my_component', 'my_callback');

    $reflection = new \ReflectionClass($loader);

    $property = $reflection->getProperty('actions');
    $property->setAccessible(true);
    $actions = $property->getValue($loader);

    if (count($actions) !== 1 || $actions[0]['hook'] !== 'init') {
        echo "FAIL: test_loader_add_methods - action not added correctly.\n";
        exit(1);
    }

    $property = $reflection->getProperty('filters');
    $property->setAccessible(true);
    $filters = $property->getValue($loader);

    if (count($filters) !== 1 || $filters[0]['hook'] !== 'the_content') {
        echo "FAIL: test_loader_add_methods - filter not added correctly.\n";
        exit(1);
    }

    $property = $reflection->getProperty('shortcodes');
    $property->setAccessible(true);
    $shortcodes = $property->getValue($loader);

    if (count($shortcodes) !== 1 || $shortcodes[0]['tag'] !== 'my_tag') {
        echo "FAIL: test_loader_add_methods - shortcode not added correctly.\n";
        exit(1);
    }

    echo "PASS: test_loader_add_methods\n";
}

test_loader_add_methods();
echo "All tests passed.\n";
