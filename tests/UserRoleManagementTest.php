<?php
use PHPUnit\Framework\TestCase;
use ArabPsychology\NabooDatabase\Admin\User_Role_Management;

class UserRoleManagementTest extends TestCase {
    public function test_list_roles() {
        global $wp_roles;

        // Mock $wp_roles
        $wp_roles = new stdClass();
        $wp_roles->roles = [
            'administrator' => [
                'name' => 'Administrator',
                'capabilities' => ['manage_options' => true, 'edit_posts' => true]
            ],
            'editor' => [
                'name' => 'Editor',
                'capabilities' => ['edit_posts' => true]
            ]
        ];

        $plugin_name = 'naboodatabase';
        $version = '1.0.0';
        $role_manager = new User_Role_Management( $plugin_name, $version );

        // Mock request
        $request = new stdClass();

        $response = $role_manager->list_roles( $request );

        $this->assertInstanceOf( 'WP_REST_Response', $response );
        $this->assertEquals( 200, $response->status );

        $expected_data = [
            'roles' => [
                [
                    'key' => 'administrator',
                    'name' => 'Administrator',
                    'capabilities' => ['manage_options', 'edit_posts']
                ],
                [
                    'key' => 'editor',
                    'name' => 'Editor',
                    'capabilities' => ['edit_posts']
                ]
            ]
        ];

        $this->assertEquals( $expected_data, $response->data );
    }
}
