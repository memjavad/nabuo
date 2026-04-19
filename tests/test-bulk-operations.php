<?php
require_once __DIR__ . '/mock-wp-classes.php';
require_once dirname(__DIR__) . '/includes/admin/class-bulk-operations.php';

use ArabPsychology\NabooDatabase\Admin\Bulk_Operations;

echo "Running tests for Bulk_Operations...\n";

$bulk_ops = new Bulk_Operations( 'naboodatabase', '1.0.0' );

// Test change_status with non-array scale_ids
$request1 = new WP_REST_Request( array( 'scale_ids' => '123', 'status' => 'publish' ) );
$response1 = $bulk_ops->change_status( $request1 );
assert_status( $response1, 400 );

// Test add_taxonomy with non-array scale_ids
$request2 = new WP_REST_Request( array( 'scale_ids' => '123', 'term_ids' => array(1) ) );
$response2 = $bulk_ops->add_taxonomy( $request2 );
assert_status( $response2, 400 );

// Test add_taxonomy with non-array term_ids
$request3 = new WP_REST_Request( array( 'scale_ids' => array(123), 'term_ids' => '1' ) );
$response3 = $bulk_ops->add_taxonomy( $request3 );
assert_status( $response3, 400 );

// Test delete_scales with non-array scale_ids
$request4 = new WP_REST_Request( array( 'scale_ids' => '123' ) );
$response4 = $bulk_ops->delete_scales( $request4 );
assert_status( $response4, 400 );

// Test export_scales with non-array scale_ids
$request5 = new WP_REST_Request( array( 'scale_ids' => '123' ) );
$response5 = $bulk_ops->export_scales( $request5 );
assert_status( $response5, 400 );
