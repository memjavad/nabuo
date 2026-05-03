<?php
/**
 * Test Scale Collections.
 *
 * @package ArabPsychology\NabooDatabase\Tests
 */

namespace ArabPsychology\NabooDatabase\Tests;

use WP_UnitTestCase;
use ArabPsychology\NabooDatabase\Public\Scale_Collections;
use WP_REST_Request;
use WP_Error;

class Test_Scale_Collections extends WP_UnitTestCase {

	/**
	 * Scale collections instance.
	 *
	 * @var Scale_Collections
	 */
	public $scale_collections;

	public function setUp(): void {
		parent::setUp();
		$this->scale_collections = new Scale_Collections( 'naboodatabase', '1.0.0' );

		// Ensure tables exist for tests
		$this->scale_collections->create_table();
	}

	public function test_create_collection_not_logged_in() {
		// Ensure user is not logged in
		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'POST', '/apa/v1/collections' );
		$request->set_param( 'collection_name', 'My New Collection' );

		$response = $this->scale_collections->create_collection( $request );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertEquals( 'not_logged_in', $response->get_error_code() );
		$this->assertEquals( 401, $response->get_error_data()['status'] );
	}

	public function test_create_collection_missing_title() {
		// Log in a user
		$user_id = $this->factory->user->create();
		wp_set_current_user( $user_id );

		$request = new WP_REST_Request( 'POST', '/apa/v1/collections' );
		// Do not set collection_name parameter

		$response = $this->scale_collections->create_collection( $request );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertEquals( 'missing_title', $response->get_error_code() );
		$this->assertEquals( 400, $response->get_error_data()['status'] );
	}

	public function test_create_collection_empty_title() {
		// Log in a user
		$user_id = $this->factory->user->create();
		wp_set_current_user( $user_id );

		$request = new WP_REST_Request( 'POST', '/apa/v1/collections' );
		$request->set_param( 'collection_name', '   ' ); // Only spaces

		$response = $this->scale_collections->create_collection( $request );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertEquals( 'missing_title', $response->get_error_code() );
		$this->assertEquals( 400, $response->get_error_data()['status'] );
	}
}
