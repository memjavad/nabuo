<?php
require_once __DIR__ . '/mock-wp-core.php';
require_once __DIR__ . '/../includes/public/class-scale-recommendation-engine.php';

use ArabPsychology\NabooDatabase\Public\Scale_Recommendation_Engine;

class Test_Scale_Recommendation_Engine {
    private $engine;

    public function __construct() {
        $this->engine = new Scale_Recommendation_Engine('naboodatabase', '1.0.0');
    }

    public function run() {
        $passed = true;
        $passed = $this->test_get_personalized_recommendations_anonymous() && $passed;
        $passed = $this->test_calculate_similarity_score() && $passed;

        return $passed;
    }

    private function test_get_personalized_recommendations_anonymous() {
        echo "Running test_get_personalized_recommendations_anonymous...\n";

        // Set up anonymous user
        global $mock_current_user_id;
        $mock_current_user_id = 0;

        $request = new WP_REST_Request();
        $request->set_param('limit', 5);

        // Let's spy on the get_trending_recommendations method instead of hardcoding expected output
        // We can do this by using a test subclass that overrides get_trending_recommendations

        $test_engine = new class('naboodatabase', '1.0.0') extends Scale_Recommendation_Engine {
            public $trending_called = false;
            public function get_trending_recommendations($request) {
                $this->trending_called = true;
                return new WP_REST_Response(['trending' => 'mocked'], 200);
            }
        };

        $response = $test_engine->get_personalized_recommendations($request);

        if ($test_engine->trending_called) {
            echo "PASS: Anonymous user gets trending recommendations (method was called).\n";
            return true;
        }

        echo "FAIL: Anonymous user did not get expected trending recommendations (method was NOT called).\n";
        return false;
    }

    private function test_calculate_similarity_score() {
        echo "Running test_calculate_similarity_score...
";

        $engine = new Scale_Recommendation_Engine('naboodatabase', '1.0.0');

        // Use Reflection to access private method
        $reflection = new ReflectionClass(Scale_Recommendation_Engine::class);
        $method = $reflection->getMethod('calculate_similarity_score');
        $method->setAccessible(true);

        // Setup mock data
        if (function_exists('wp_set_mock_post_terms')) {
            wp_set_mock_post_terms([
                1 => [1, 2, 3],
                2 => [2, 3, 4],
                3 => [], // For zero division test
                4 => []
            ]);
        }

        if (function_exists('wp_set_mock_post_meta')) {
            wp_set_mock_post_meta([
                1 => ['_naboo_view_count' => 100],
                2 => ['_naboo_view_count' => 80],
                3 => ['_naboo_view_count' => 0],
                4 => ['_naboo_view_count' => 0]
            ]);
        }

        $scale1 = (object) ['ID' => 1, 'post_author' => 1];
        $scale2 = (object) ['ID' => 2, 'post_author' => 1];

        $score = $method->invokeArgs($engine, [$scale1, $scale2]);

        if (round($score) == 76) {
            echo "PASS: calculate_similarity_score calculates correctly.
";
        } else {
            echo "FAIL: calculate_similarity_score returned $score, expected 76.
";
            return false;
        }

        // Test division by zero fix
        $scale3 = (object) ['ID' => 3, 'post_author' => 1];
        $scale4 = (object) ['ID' => 4, 'post_author' => 2];

        try {
            $score_zero = $method->invokeArgs($engine, [$scale3, $scale4]);
            echo "PASS: calculate_similarity_score handles zero categories safely.
";
            return true;
        } catch (\Exception $e) {
            echo "FAIL: calculate_similarity_score threw Exception with zero categories: " . $e->getMessage() . "
";
            return false;
        } catch (\Error $e) {
            echo "FAIL: calculate_similarity_score threw Error with zero categories: " . $e->getMessage() . "
";
            return false;
        }
    }
}

$tester = new Test_Scale_Recommendation_Engine();
if ($tester->run()) {
    exit(0);
} else {
    exit(1);
}
