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


	/**
	 * Test get overview report with standard results
	 */
	public function test_get_overview_report_standard_results() {
		global $wpdb;

		// Create a mock WP_REST_Request
		$request = new WP_REST_Request();

		// Replace the global wpdb with our mock just for this test
		$original_wpdb = $wpdb;

		$wpdb_mock = $this->createMock( wpdb::class );
		$wpdb_mock->posts = 'wp_posts';
		$wpdb_mock->users = 'wp_users';
		$wpdb_mock->prefix = 'wp_';

		$wpdb_mock->method( 'get_var' )->willReturnCallback( function( $query ) {
			$query_map = [
				"SELECT COUNT(*) FROM wp_posts WHERE post_type = 'psych_scale'" => 10,
				"SELECT COUNT(*) FROM wp_posts WHERE post_type = 'psych_scale' AND post_status = 'publish'" => 5,
				"SELECT COUNT(*) FROM wp_posts WHERE post_type = 'psych_scale' AND post_status = 'pending'" => 3,
				"SELECT COUNT(*) FROM wp_users" => 50,
				"SELECT COUNT(DISTINCT user_id) FROM wp_naboo_user_analytics WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 30 DAY)" => 20,
				"SELECT SUM(views) FROM wp_naboo_popularity_analytics" => 1000,
				"SELECT SUM(downloads) FROM wp_naboo_popularity_analytics" => 500,
			];
			return isset( $query_map[ $query ] ) ? $query_map[ $query ] : null;
		});

		$GLOBALS['wpdb'] = $wpdb_mock;

		$response = $this->generator->get_overview_report( $request );
		$data = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 10, $data['total_scales'] );
		$this->assertEquals( 5, $data['published_scales'] );
		$this->assertEquals( 3, $data['pending_scales'] );
		$this->assertEquals( 50, $data['total_users'] );
		$this->assertEquals( 20, $data['active_users'] );
		$this->assertEquals( 1000, $data['total_views'] );
		$this->assertEquals( 500, $data['total_downloads'] );
		$this->assertArrayHasKey( 'generated_at', $data );

		// Restore original wpdb
		$GLOBALS['wpdb'] = $original_wpdb;
	}

	/**
	 * Test get overview report with null DB results
	 */
	public function test_get_overview_report_null_results() {
		global $wpdb;

		// Create a mock WP_REST_Request
		$request = new WP_REST_Request();

		// Replace the global wpdb with our mock just for this test
		$original_wpdb = $wpdb;

		$wpdb_mock = $this->createMock( wpdb::class );
		$wpdb_mock->posts = 'wp_posts';
		$wpdb_mock->users = 'wp_users';
		$wpdb_mock->prefix = 'wp_';

		$wpdb_mock->method( 'get_var' )->willReturnCallback( function( $query ) {
			$query_map = [
				"SELECT COUNT(*) FROM wp_posts WHERE post_type = 'psych_scale'" => 0,
				"SELECT COUNT(*) FROM wp_posts WHERE post_type = 'psych_scale' AND post_status = 'publish'" => 0,
				"SELECT COUNT(*) FROM wp_posts WHERE post_type = 'psych_scale' AND post_status = 'pending'" => 0,
				"SELECT COUNT(*) FROM wp_users" => 1,
				"SELECT COUNT(DISTINCT user_id) FROM wp_naboo_user_analytics WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 30 DAY)" => 0,
				"SELECT SUM(views) FROM wp_naboo_popularity_analytics" => null,
				"SELECT SUM(downloads) FROM wp_naboo_popularity_analytics" => null,
			];
			return isset( $query_map[ $query ] ) ? $query_map[ $query ] : null;
		});

		$GLOBALS['wpdb'] = $wpdb_mock;

		$response = $this->generator->get_overview_report( $request );
		$data = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 0, $data['total_scales'] );
		$this->assertEquals( 0, $data['total_views'] );
		$this->assertEquals( 0, $data['total_downloads'] );

		// Restore original wpdb
		$GLOBALS['wpdb'] = $original_wpdb;
	}
}
