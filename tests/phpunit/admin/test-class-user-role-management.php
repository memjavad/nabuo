<?php
/**
 * Test User Role Management.
 *
 * @package ArabPsychology\NabooDatabase\Tests
 */

namespace ArabPsychology\NabooDatabase\Tests;

use WP_UnitTestCase;
use ArabPsychology\NabooDatabase\Admin\User_Role_Management;
use WP_REST_Request;

class Test_User_Role_Management extends WP_UnitTestCase {

	/**
	 * Role manager instance.
	 *
	 * @var User_Role_Management
	 */
	public $role_manager;

	public function setUp(): void {
		parent::setUp();
		$this->role_manager = new User_Role_Management( 'naboodatabase', '1.0.0' );
	}

	public function test_list_roles() {
		$request = new WP_REST_Request( 'GET', '/apa/v1/roles/list' );

		$response = $this->role_manager->list_roles( $request );

		$this->assertInstanceOf( 'WP_REST_Response', $response );
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'roles', $data );
		$this->assertIsArray( $data['roles'] );

		// Make sure our custom roles are there
		$role_keys = array_column( $data['roles'], 'key' );
		$this->assertContains( 'administrator', $role_keys );
		$this->assertContains( 'scale_editor', $role_keys );
		$this->assertContains( 'scale_reviewer', $role_keys );
		$this->assertContains( 'scale_contributor', $role_keys );

		// Check structure of first role
		$first_role = $data['roles'][0];
		$this->assertArrayHasKey( 'key', $first_role );
		$this->assertArrayHasKey( 'name', $first_role );
		$this->assertArrayHasKey( 'capabilities', $first_role );
		$this->assertIsArray( $first_role['capabilities'] );
	}
}
