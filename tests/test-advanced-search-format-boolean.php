<?php

require_once __DIR__ . '/../includes/public/class-advanced-search.php';

use ArabPsychology\NabooDatabase\Public\Advanced_Search;

class Test_Format_Boolean_Keyword {
    public function run() {
        $reflection = new ReflectionClass(Advanced_Search::class);
        $search = $reflection->newInstanceWithoutConstructor();

        // Make private method accessible via reflection
        $method = $reflection->getMethod('format_boolean_keyword');
        $method->setAccessible(true);

        $tests = [
            // Empty / Whitespace
            ['', false, ''],
            ['   ', false, ''],

            // Basic keywords (length > 2)
            ['test', false, '+test*'],
            ['apple', false, '+apple*'],

            // Short keywords (length <= 2 are ignored)
            ['a', false, ''],
            ['hi', false, ''],
            ['ok', false, ''],

            // Existing operators stripped
            ['+test', false, '+test*'],
            ['-test', false, '+test*'],
            ['"test"', false, '+test*'],
            ['(test)', false, '+test*'],
            ['>test<', false, '+test*'],
            ['~test', false, '+test*'],
            ['*test*', false, '+test*'],

            // Multiple words
            ['hello world', false, '+hello* +world*'],
            ['apple pie a', false, '+apple* +pie*'], // 'a' ignored

            // Force exclude
            ['test', true, '-test*'],
            ['hello world', true, '-hello* -world*'],

            // Mixed case (should convert to lowercase)
            ['HeLlO WoRlD', false, '+hello* +world*'],

            // Already complex
            ['+(hello world)', false, '+hello* +world*'],
            ['+"hello" -world', false, '+hello* +world*'],
        ];

        $passed = 0;
        $failed = 0;

        foreach ($tests as $test) {
            $keyword = $test[0];
            $force_exclude = $test[1];
            $expected = $test[2];

            $result = $method->invoke($search, $keyword, $force_exclude);

            if ($result === $expected) {
                $passed++;
            } else {
                echo "FAIL: format_boolean_keyword('$keyword', " . var_export($force_exclude, true) . ")\n";
                echo "  Expected: '$expected'\n";
                echo "  Got:      '$result'\n";
                $failed++;
            }
        }

        echo "\nTests run: " . count($tests) . ", Passed: $passed, Failed: $failed\n";

        if ($failed > 0) {
            exit(1);
        }
    }
}

$test = new Test_Format_Boolean_Keyword();
$test->run();
