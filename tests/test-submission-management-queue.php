<?php
require_once __DIR__ . '/mock-wp-classes.php';
require_once dirname(__DIR__) . '/includes/admin/class-submission-management-queue.php';

use ArabPsychology\NabooDatabase\Admin\Submission_Management_Queue;

echo "Running tests for Submission_Management_Queue...\n";

// Mocking function that might be called in the constructor
if (!function_exists('add_action')) {
    function add_action() {}
}

$queue = new Submission_Management_Queue( 'naboodatabase', '1.0.0' );

// Test bulk_action with non-array post_ids
$request1 = new WP_REST_Request( array( 'post_ids' => '123', 'action' => 'approve' ) );
$response1 = $queue->bulk_action( $request1 );
assert_status( $response1, 400 );
