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

echo "Running Glossary Admin Tests...
";
exec('php ' . __DIR__ . '/admin/test-glossary-admin.php', $output_glossary, $return_var_glossary);
echo implode("\n", $output_glossary) . "\n";
if ($return_var_glossary !== 0) {
    echo "FAIL: Glossary Admin Tests failed.\n";
    exit(1);
}

echo "All tests passed.\n";
