<?php
namespace ArabPsychology\NabooDatabase\Public;

use PHPUnit\Framework\TestCase;

class TestScaleRecommendationEngine extends TestCase {

    protected function setUp(): void {
        global $mock_current_user_id;
        $mock_current_user_id = null; // Reset before each test
    }

    public function testGetPersonalizedRecommendationsForAnonymousUser() {
        // Set up the environment to simulate an anonymous user
        global $mock_current_user_id;
        $mock_current_user_id = 0; // Anonymous user

        // Create a mock for Scale_Recommendation_Engine that overrides get_trending_recommendations
        $engine = $this->getMockBuilder(Scale_Recommendation_Engine::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get_trending_recommendations'])
            ->getMock();

        $request = new \WP_REST_Request();
        $request->set_param('limit', 5);

        // Expect get_trending_recommendations to be called exactly once
        $expectedResponse = new \WP_REST_Response(['trending' => []], 200);
        $engine->expects($this->once())
            ->method('get_trending_recommendations')
            ->with($request)
            ->willReturn($expectedResponse);

        // Execute the method
        $response = $engine->get_personalized_recommendations($request);

        // Verify the response is what get_trending_recommendations returned
        $this->assertSame($expectedResponse, $response);
    }

    public function testCalculateSimilarityScore() {
        // We use Reflection to test the private method calculate_similarity_score
        $engine = new Scale_Recommendation_Engine('naboodatabase', '1.0.0');

        $reflection = new \ReflectionClass(Scale_Recommendation_Engine::class);
        $method = $reflection->getMethod('calculate_similarity_score');
        $method->setAccessible(true);

        // Ensure mock functions have wp_set_mock_post_terms and wp_set_mock_post_meta available
        if (function_exists('wp_set_mock_post_terms')) {
            wp_set_mock_post_terms([
                1 => [1, 2, 3], // Scale 1 categories
                2 => [2, 3, 4], // Scale 2 categories (2 common out of 3 = 2/3 * 60 = 40)
            ]);
        }

        if (function_exists('wp_set_mock_post_meta')) {
            wp_set_mock_post_meta([
                1 => ['_naboo_view_count' => 100],
                2 => ['_naboo_view_count' => 80], // diff = 20, max = 100 -> diff/max = 0.2. score += (1 - 0.2) * 20 = 16
            ]);
        }

        $scale1 = (object) ['ID' => 1, 'post_author' => 1];
        $scale2 = (object) ['ID' => 2, 'post_author' => 1]; // same author = 20 score

        // Expected score: 40 (category) + 20 (author) + 16 (popularity) = 76
        $score = $method->invokeArgs($engine, [$scale1, $scale2]);

        $this->assertEquals(76.0, round($score));
    }

    public function testCalculateSimilarityScoreDivisionByZero() {
        $engine = new Scale_Recommendation_Engine('naboodatabase', '1.0.0');

        $reflection = new \ReflectionClass(Scale_Recommendation_Engine::class);
        $method = $reflection->getMethod('calculate_similarity_score');
        $method->setAccessible(true);

        if (function_exists('wp_set_mock_post_terms')) {
            wp_set_mock_post_terms([
                3 => [], // No categories
                4 => [], // No categories
            ]);
        }

        if (function_exists('wp_set_mock_post_meta')) {
            wp_set_mock_post_meta([
                3 => ['_naboo_view_count' => 0],
                4 => ['_naboo_view_count' => 0],
            ]);
        }

        $scale1 = (object) ['ID' => 3, 'post_author' => 1];
        $scale2 = (object) ['ID' => 4, 'post_author' => 2]; // different author = 0 score

        // The division by zero bug should be fixed, so it doesn't throw Error
        $score = $method->invokeArgs($engine, [$scale1, $scale2]);
        $this->assertEquals(0.0, round($score));
    }
}