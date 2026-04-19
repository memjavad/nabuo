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
}
