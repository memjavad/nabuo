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
}

$tester = new Test_Scale_Recommendation_Engine();
if ($tester->run()) {
    exit(0);
} else {
    exit(1);
}
