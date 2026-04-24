<?php
// A simple test runner since PHPUnit is missing
$tests = glob(__DIR__ . '/test-*.php');
$passed = 0;
$failed = 0;

foreach ($tests as $test) {
    require_once $test;
}

echo "Tests Passed: $passed\n";
echo "Tests Failed: $failed\n";

if ($failed > 0) {
    exit(1);
}
