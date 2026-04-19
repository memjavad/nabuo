<?php
/**
 * User Role Management
 *
 * @package ArabPsychology\NabooDatabase\Admin
 */

namespace ArabPsychology\NabooDatabase\Admin;

/**
 * User_Role_Management class - Advanced role and capability management.
 */
class User_Role_Management {

	/**
	 * Plugin name
	 *
	 * @var string
	 */
	private $plugin_name;

	/**
	 * Plugin version
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Constructor
	 *
	 * @param string $plugin_name The plugin name.
	 * @param string $version     The plugin version.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->create_custom_roles();
	}

	/**
	 * Create custom roles
	 */
	private function create_custom_roles() {
		// Scale Editor role
		if ( ! get_role( 'scale_editor' ) ) {
			add_role(
				'scale_editor',
				__( 'Scale Editor', 'naboodatabase' ),
				array(
					'read'                => true,
					'edit_psych_scales'   => true,
					'edit_others_psych_scales' => true,
					'publish_psych_scales' => true,
					'delete_psych_scales' => false,
					'manage_scale_categories' => true,
					'manage_scale_authors' => true,
				)
			);
		}

		// Scale Reviewer role
		if ( ! get_role( 'scale_reviewer' ) ) {
			add_role(
				'scale_reviewer',
				__( 'Scale Reviewer', 'naboodatabase' ),
				array(
					'read'                => true,
					'edit_psych_scales'   => true,
					'edit_others_psych_scales' => true,
					'publish_psych_scales' => true,
					'delete_psych_scales' => false,
					'moderate_comments'   => true,
				)
			);
		}

		// Scale Contributor role
		if ( ! get_role( 'scale_contributor' ) ) {
			add_role(
				'scale_contributor',
				__( 'Scale Contributor', 'naboodatabase' ),
				array(
					'read'                => true,
					'edit_psych_scales'   => true,
					'delete_psych_scales' => false,
					'publish_psych_scales' => false,
				)
			);
		}
	}

	/**
	 * Register REST API endpoints
	 */
	public function register_endpoints() {
		register_rest_route(
			'apa/v1',
			'/roles/list',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'list_roles' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/roles/assign',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'assign_role' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/roles/capabilities',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_capabilities' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'apa/v1',
			'/roles/grant-capability',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'grant_capability' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}

	/**
	 * List available roles
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function list_roles( $request ) {
		global $wp_roles;

		$roles = array();

		foreach ( $wp_roles->roles as $role_key => $role ) {
			$roles[] = array(
				'key'          => $role_key,
				'name'         => $role['name'],
				'capabilities' => array_keys( $role['capabilities'] ),
			);
		}

		return new \WP_REST_Response( array( 'roles' => $roles ), 200 );
	}

	/**
	 * Assign role to user
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function assign_role( $request ) {
		$user_id = $request->get_param( 'user_id' );
		$role    = $request->get_param( 'role' );

		$user = get_user_by( 'id', $user_id );

		if ( ! $user ) {
			return new \WP_REST_Response(
				array( 'error' => 'User not found' ),
				404
			);
		}

		// Remove all roles and assign new one
		foreach ( $user->roles as $old_role ) {
			$user->remove_role( $old_role );
		}

		$user->add_role( $role );

		return new \WP_REST_Response(
			array( 'message' => sprintf( 'User assigned role: %s', $role ) ),
			200
		);
	}

	/**
	 * Get capabilities for role
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_capabilities( $request ) {
		global $wp_roles;

		$role_key = $request->get_param( 'role' );

		if ( ! isset( $wp_roles->roles[ $role_key ] ) ) {
			return new \WP_REST_Response(
				array( 'error' => 'Role not found' ),
				404
			);
		}

		$role = $wp_roles->roles[ $role_key ];

		return new \WP_REST_Response(
			array(
				'role'         => $role_key,
				'capabilities' => $role['capabilities'],
			),
			200
		);
	}

	/**
	 * Grant capability to role
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function grant_capability( $request ) {
		$role_key  = $request->get_param( 'role' );
		$capability = $request->get_param( 'capability' );

		$role = get_role( $role_key );

		if ( ! $role ) {
			return new \WP_REST_Response(
				array( 'error' => 'Role not found' ),
				404
			);
		}

		$role->add_cap( $capability );

		return new \WP_REST_Response(
			array( 'message' => sprintf( 'Capability %s granted to role %s', $capability, $role_key ) ),
			200
		);
	}
}
