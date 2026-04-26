<?php
require_once __DIR__ . '/mock-wp-core.php';

function rest_ensure_response($response) {
    if ($response instanceof WP_REST_Response) {
        return $response;
    }
    return new WP_REST_Response($response, 200);
}

// Ensure the Comments class is loaded
require_once __DIR__ . '/../includes/public/class-comments.php';
use ArabPsychology\NabooDatabase\Public\Comments;

ini_set('assert.exception', 1);

class Test_Class_Comments {
    private $original_wpdb;

    public function setUp() {
        global $wpdb;
        $this->original_wpdb = $wpdb;

        $wpdb = new class {
            public $prefix = 'wp_';
            public $prepare_calls = [];
            public $mock_comments = [];
            public $mock_replies = [];
            public $mock_count = 0;

            public function prepare($query, ...$args) {
                $this->prepare_calls[] = [
                    'query' => $query,
                    'args' => $args
                ];
                $prepared = $query;
                foreach($args as $arg) {
                    $prepared = preg_replace('/%[dsf]/', "'$arg'", $prepared, 1);
                }
                return $prepared;
            }

            public function get_results($query) {
                if (strpos($query, 'parent_id = 0') !== false) {
                    return $this->mock_comments;
                }

                if (strpos($query, 'parent_id =') !== false && strpos($query, 'parent_id = 0') === false) {
                    return $this->mock_replies;
                }

                return [];
            }

            public function get_var($query) {
                if (strpos($query, 'COUNT(*)') !== false) {
                    return $this->mock_count;
                }
                return null;
            }

            public function get_charset_collate() { return ""; }
            public function query($query) { return true; }
        };
    }

    public function tearDown() {
        global $wpdb;
        $wpdb = $this->original_wpdb;
    }

    public function test_get_comments_success() {
        global $wpdb;
        $this->setUp();

        $comment = new stdClass();
        $comment->id = 1;
        $comment->comment_text = 'Test top level comment';
        $wpdb->mock_comments = [$comment];

        $reply = new stdClass();
        $reply->id = 2;
        $reply->comment_text = 'Test reply';
        $wpdb->mock_replies = [$reply];

        $wpdb->mock_count = 1;

        $comments_handler = new Comments('naboodatabase', '1.0.0');

        $request = new WP_REST_Request();
        $request->set_param('scale_id', '123');
        $request->set_param('page', '2');
        $request->set_param('per_page', '10');

        $response = $comments_handler->get_comments($request);

        assert($response instanceof WP_REST_Response, "Expected WP_REST_Response");
        $data = $response->get_data();

        assert($data['success'] === true, "Expected success");
        assert($data['total'] === 1, "Expected total 1");
        assert($data['page'] === 2, "Expected page 2");
        assert($data['per_page'] === 10, "Expected per_page 10");

        assert(count($data['data']) === 1, "Expected 1 comment");
        $top_comment = $data['data'][0];
        assert($top_comment->id === 1, "Expected top comment ID 1");

        assert(isset($top_comment->replies), "Expected replies");
        assert(count($top_comment->replies) === 1, "Expected 1 reply");
        assert($top_comment->replies[0]->id === 2, "Expected reply ID 2");

        $top_level_query = $wpdb->prepare_calls[0];
        assert(strpos($top_level_query['query'], 'LIMIT %d OFFSET %d') !== false, "Expected LIMIT and OFFSET");
        assert($top_level_query['args'][0] === 123, "Expected scale_id");
        assert($top_level_query['args'][1] === 10, "Expected per_page for LIMIT");
        assert($top_level_query['args'][2] === 10, "Expected offset for page 2");

        $this->tearDown();
        echo "PASS: test_get_comments_success\n";
    }

    public function test_get_comments_empty() {
        global $wpdb;
        $this->setUp();

        $wpdb->mock_comments = [];
        $wpdb->mock_replies = [];
        $wpdb->mock_count = 0;

        $comments_handler = new Comments('naboodatabase', '1.0.0');

        $request = new WP_REST_Request();
        $request->set_param('scale_id', '123');

        $response = $comments_handler->get_comments($request);

        $data = $response->get_data();

        assert($data['success'] === true, "Expected success");
        assert($data['total'] === 0, "Expected total 0");
        assert(count($data['data']) === 0, "Expected 0 comments");

        $this->tearDown();
        echo "PASS: test_get_comments_empty\n";
    }

    public function test_get_comments_default_pagination() {
        global $wpdb;
        $this->setUp();

        $wpdb->mock_comments = [];
        $wpdb->mock_replies = [];
        $wpdb->mock_count = 0;

        $comments_handler = new Comments('naboodatabase', '1.0.0');

        $request = new WP_REST_Request();
        $request->set_param('scale_id', '123');

        // No page or per_page params set
        $response = $comments_handler->get_comments($request);

        $top_level_query = $wpdb->prepare_calls[0];
        assert($top_level_query['args'][1] === 20, "Expected default per_page to be 20");
        assert($top_level_query['args'][2] === 0, "Expected default offset to be 0");

        $this->tearDown();
        echo "PASS: test_get_comments_default_pagination\n";
    }
}

echo "Running Class Comments Tests...\n";
$tester = new Test_Class_Comments();

try {
    $tester->test_get_comments_success();
    $tester->test_get_comments_empty();
    $tester->test_get_comments_default_pagination();
    echo "All class comments tests passed.\n";
} catch (Throwable $e) {
    echo "FAIL: " . $e->getMessage() . "\n";
    exit(1);
}
