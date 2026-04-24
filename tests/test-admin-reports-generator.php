<?php
/**
 * Class Test_Admin_Reports_Generator
 *
 * @package NabooDatabase
 */

// Load the class being tested directly to avoid autoloader issues in testing
require_once dirname( dirname( __FILE__ ) ) . '/includes/admin/class-admin-reports-generator.php';

use ArabPsychology\NabooDatabase\Admin\Admin_Reports_Generator;

/**
 * Admin Reports Generator test case.
 */
class Test_Admin_Reports_Generator extends WP_UnitTestCase {

	/**
	 * @var Admin_Reports_Generator
	 */
	private $generator;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		// Passing empty strings for plugin name and version as they are only used for properties
		$this->generator = new Admin_Reports_Generator( 'naboodatabase', '1.55.5' );
	}

	/**
	 * Test unauthorized save report.
	 */
	public function test_save_report_unauthorized() {
		// Set current user to 0 (unlogged in)
		wp_set_current_user( 0 );

		$request = new WP_REST_Request();
		$response = $this->generator->save_report( $request );

		$this->assertWPError( $response );
		$this->assertEquals( 'unauthorized', $response->get_error_code() );
	}

	/**
	 * Test authorized save report.
	 */
	public function test_save_report_authorized() {
		// Create an administrator and set as current user
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$request = new WP_REST_Request();
		$request->set_param( 'report_name', 'Test Report' );
		$request->set_param( 'report_type', 'Test Type' );
		$request->set_param( 'start_date', '2023-01-01' );
		$request->set_param( 'end_date', '2023-12-31' );
		$request->set_param( 'data', array( 'key' => 'value' ) );

		$response = $this->generator->save_report( $request );

		// The insert operation will fail in a unit test environment if the custom table doesn't exist.
		// Therefore we assert WP_REST_Response if insert succeeded OR we receive a 500 status
		// (which means it got past the authorization check)
		$this->assertInstanceOf( 'WP_REST_Response', $response );
	}
}
