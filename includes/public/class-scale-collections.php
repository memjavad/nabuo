<?php

namespace ArabPsychology\NabooDatabase\Public;

use WP_REST_Request;
use WP_REST_Response;

/**
 * Scale Collections Feature
 *
 * Allows users to create and manage custom collections of scales.
 */
class Scale_Collections {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Create collections and items tables.
	 */
	public function create_table() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		// Collections table
		$collections_table = $wpdb->prefix . 'naboo_collections';
		$sql_collections = "CREATE TABLE IF NOT EXISTS $collections_table (
			id BIGINT(20) NOT NULL AUTO_INCREMENT,
			user_id BIGINT(20) NOT NULL,
			collection_name VARCHAR(255) NOT NULL,
			description LONGTEXT,
			color_code VARCHAR(7) DEFAULT '#00796b',
			is_public TINYINT(1) DEFAULT 0,
			item_count INT(11) DEFAULT 0,
			view_count INT(11) DEFAULT 0,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY is_public (is_public),
			KEY created_at (created_at),
			UNIQUE KEY user_collection (user_id, collection_name)
		) $charset_collate;";

		// Collection items table
		$items_table = $wpdb->prefix . 'naboo_collection_items';
		$sql_items = "CREATE TABLE IF NOT EXISTS $items_table (
			id BIGINT(20) NOT NULL AUTO_INCREMENT,
			collection_id BIGINT(20) NOT NULL,
			scale_id BIGINT(20) NOT NULL,
			note TEXT,
			sort_order INT(11) DEFAULT 0,
			added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY collection_id (collection_id),
			KEY scale_id (scale_id),
			UNIQUE KEY collection_scale (collection_id, scale_id),
			KEY sort_order (sort_order)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_collections );
		dbDelta( $sql_items );
	}

	/**
	 * Register REST API endpoints.
	 */
	public function register_endpoints() {
		$this->register_base_routes();
		$this->register_single_routes();
		$this->register_item_routes();
		$this->register_public_routes();
	}

	/**
	 * Register base collection routes.
	 */
	private function register_base_routes() {
		// Get user's collections
		register_rest_route(
			'apa/v1',
			'/collections',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_collections' ),
				'permission_callback' => 'is_user_logged_in',
			)
		);

		// Create collection
		register_rest_route(
			'apa/v1',
			'/collections',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'create_collection' ),
				'permission_callback' => 'is_user_logged_in',
				'args'                => array(
					'collection_name' => array(
						'type'     => 'string',
						'required' => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'description' => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_textarea_field',
					),
					'color_code' => array(
						'type'              => 'string',
						'default'           => '#00796b',
						'sanitize_callback' => 'sanitize_hex_color',
					),
					'is_public' => array(
						'type'              => 'boolean',
						'default'           => false,
						'sanitize_callback' => 'rest_sanitize_boolean',
					),
				),
			)
		);
	}

	/**
	 * Register single collection routes.
	 */
	private function register_single_routes() {
		// Get single collection
		register_rest_route(
			'apa/v1',
			'/collections/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_collection' ),
				'permission_callback' => '__return_true',
			)
		);

		// Update collection
		register_rest_route(
			'apa/v1',
			'/collections/(?P<id>\d+)',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'update_collection' ),
				'permission_callback' => 'is_user_logged_in',
				'args'                => array(
					'collection_name' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'description' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_textarea_field',
					),
					'color_code' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_hex_color',
					),
					'is_public' => array(
						'type'              => 'boolean',
						'sanitize_callback' => 'rest_sanitize_boolean',
					),
				),
			)
		);

		// Delete collection
		register_rest_route(
			'apa/v1',
			'/collections/(?P<id>\d+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'delete_collection' ),
				'permission_callback' => 'is_user_logged_in',
			)
		);
	}

	/**
	 * Register collection item routes.
	 */
	private function register_item_routes() {
		// Add scale to collection
		register_rest_route(
			'apa/v1',
			'/collections/(?P<id>\d+)/items',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'add_item_to_collection' ),
				'permission_callback' => 'is_user_logged_in',
				'args'                => array(
					'scale_id' => array(
						'type'     => 'integer',
						'required' => true,
					),
					'note' => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_textarea_field',
					),
				),
			)
		);

		// Remove scale from collection
		register_rest_route(
			'apa/v1',
			'/collections/(?P<id>\d+)/items/(?P<item_id>\d+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'remove_item_from_collection' ),
				'permission_callback' => 'is_user_logged_in',
			)
		);
	}

	/**
	 * Register public collection routes.
	 */
	private function register_public_routes() {
		// Get public collections
		register_rest_route(
			'apa/v1',
			'/collections/public/list',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_public_collections' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'limit' => array(
						'type'              => 'integer',
						'default'           => 10,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	/**
	 * Get user's collections.
	 */
	public function get_collections( WP_REST_Request $request ) {
		$user_id = get_current_user_id();

		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_collections';

		$collections = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM $table_name WHERE user_id = %d ORDER BY updated_at DESC",
			$user_id
		) );

		if ( empty( $collections ) ) {
			return new WP_REST_Response( array( 'collections' => $collections ) );
		}

		$collection_ids = wp_list_pluck( $collections, 'id' );

		$items_table = $wpdb->prefix . 'naboo_collection_items';

		// Use a single query to fetch item counts for all retrieved collections
		$placeholders = implode( ',', array_fill( 0, count( $collection_ids ), '%d' ) );
		$counts = $wpdb->get_results( $wpdb->prepare(
			"SELECT collection_id, COUNT(*) as count FROM $items_table WHERE collection_id IN ($placeholders) GROUP BY collection_id",
			$collection_ids
		), OBJECT_K );

		foreach ( $collections as $collection ) {
			$collection->item_count = isset( $counts[ $collection->id ] ) ? (int) $counts[ $collection->id ]->count : 0;
		}

		return new WP_REST_Response( array( 'collections' => $collections ) );
	}

	/**
	 * Create a new collection.
	 */
	public function create_collection( WP_REST_Request $request ) {
		$user_id            = get_current_user_id();
		$collection_name    = $request->get_param( 'collection_name' );
		$description        = $request->get_param( 'description' );
		$color_code         = $request->get_param( 'color_code' );
		$is_public          = $request->get_param( 'is_public' ) ? 1 : 0;

		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_collections';

		$wpdb->insert(
			$table_name,
			array(
				'user_id'           => $user_id,
				'collection_name'   => $collection_name,
				'description'       => $description,
				'color_code'        => $color_code,
				'is_public'         => $is_public,
			),
			array( '%d', '%s', '%s', '%s', '%d' )
		);

		if ( $wpdb->last_error ) {
			return new WP_REST_Response( array( 'error' => 'Database error' ), 500 );
		}

		return new WP_REST_Response( array( 'id' => $wpdb->insert_id, 'success' => true ), 201 );
	}

	/**
	 * Get a single collection with items.
	 */
	public function get_collection( WP_REST_Request $request ) {
		$id = (int) $request->get_param( 'id' );

		global $wpdb;
		$collections_table = $wpdb->prefix . 'naboo_collections';
		$items_table       = $wpdb->prefix . 'naboo_collection_items';

		$collection = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $collections_table WHERE id = %d",
			$id
		) );

		if ( ! $collection ) {
			return new WP_REST_Response( array( 'error' => 'Collection not found' ), 404 );
		}

		// Check if user has permission to view
		if ( ! $collection->is_public && $collection->user_id !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
			return new WP_REST_Response( array( 'error' => 'Unauthorized' ), 401 );
		}

		// Increment view count
		$wpdb->query( $wpdb->prepare(
			"UPDATE $collections_table SET view_count = view_count + 1 WHERE id = %d",
			$id
		) );

		// Get items
		$items = $wpdb->get_results( $wpdb->prepare(
			"SELECT ci.*, p.post_title, p.post_content FROM $items_table ci
			JOIN {$wpdb->posts} p ON ci.scale_id = p.ID
			WHERE ci.collection_id = %d
			ORDER BY ci.sort_order ASC",
			$id
		) );

		$collection->item_count = count( $items );
		$collection->items      = $items;

		return new WP_REST_Response( $collection );
	}

	/**
	 * Update a collection.
	 */
	public function update_collection( WP_REST_Request $request ) {
		$id       = (int) $request->get_param( 'id' );
		$user_id  = get_current_user_id();

		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_collections';

		$collection = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $table_name WHERE id = %d",
			$id
		) );

		if ( ! $collection || $collection->user_id !== $user_id ) {
			return new WP_REST_Response( array( 'error' => 'Unauthorized' ), 401 );
		}

		$update = array();
		if ( $request->has_param( 'collection_name' ) ) {
			$update['collection_name'] = $request->get_param( 'collection_name' );
		}
		if ( $request->has_param( 'description' ) ) {
			$update['description'] = $request->get_param( 'description' );
		}
		if ( $request->has_param( 'color_code' ) ) {
			$update['color_code'] = $request->get_param( 'color_code' );
		}
		if ( $request->has_param( 'is_public' ) ) {
			$update['is_public'] = $request->get_param( 'is_public' ) ? 1 : 0;
		}

		if ( ! empty( $update ) ) {
			$wpdb->update( $table_name, $update, array( 'id' => $id ) );
		}

		return new WP_REST_Response( array( 'success' => true ) );
	}

	/**
	 * Delete a collection.
	 */
	public function delete_collection( WP_REST_Request $request ) {
		$id       = (int) $request->get_param( 'id' );
		$user_id  = get_current_user_id();

		global $wpdb;
		$collections_table = $wpdb->prefix . 'naboo_collections';
		$items_table       = $wpdb->prefix . 'naboo_collection_items';

		$collection = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $collections_table WHERE id = %d",
			$id
		) );

		if ( ! $collection || $collection->user_id !== $user_id ) {
			return new WP_REST_Response( array( 'error' => 'Unauthorized' ), 401 );
		}

		// Delete items
		$wpdb->delete( $items_table, array( 'collection_id' => $id ) );

		// Delete collection
		$wpdb->delete( $collections_table, array( 'id' => $id ) );

		return new WP_REST_Response( array( 'success' => true ) );
	}

	/**
	 * Add scale to collection.
	 */
	public function add_item_to_collection( WP_REST_Request $request ) {
		$collection_id = (int) $request->get_param( 'id' );
		$scale_id      = (int) $request->get_param( 'scale_id' );
		$note          = $request->get_param( 'note' );
		$user_id       = get_current_user_id();

		global $wpdb;
		$collections_table = $wpdb->prefix . 'naboo_collections';
		$items_table       = $wpdb->prefix . 'naboo_collection_items';

		// Verify collection belongs to user
		$collection = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $collections_table WHERE id = %d",
			$collection_id
		) );

		if ( ! $collection || $collection->user_id !== $user_id ) {
			return new WP_REST_Response( array( 'error' => 'Unauthorized' ), 401 );
		}

		// Verify scale exists
		$scale = get_post( $scale_id );
		if ( ! $scale || 'psych_scale' !== $scale->post_type ) {
			return new WP_REST_Response( array( 'error' => 'Scale not found' ), 404 );
		}

		// Add item
		$wpdb->insert(
			$items_table,
			array(
				'collection_id' => $collection_id,
				'scale_id'      => $scale_id,
				'note'          => $note,
				'sort_order'    => 0,
			),
			array( '%d', '%d', '%s', '%d' )
		);

		if ( $wpdb->last_error ) {
			return new WP_REST_Response( array( 'error' => 'Item already exists in collection' ), 400 );
		}

		// Update item count
		$wpdb->query( $wpdb->prepare(
			"UPDATE $collections_table SET item_count = item_count + 1 WHERE id = %d",
			$collection_id
		) );

		return new WP_REST_Response( array( 'id' => $wpdb->insert_id, 'success' => true ), 201 );
	}

	/**
	 * Remove scale from collection.
	 */
	public function remove_item_from_collection( WP_REST_Request $request ) {
		$collection_id = (int) $request->get_param( 'id' );
		$item_id       = (int) $request->get_param( 'item_id' );
		$user_id       = get_current_user_id();

		global $wpdb;
		$collections_table = $wpdb->prefix . 'naboo_collections';
		$items_table       = $wpdb->prefix . 'naboo_collection_items';

		// Verify collection belongs to user
		$collection = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $collections_table WHERE id = %d",
			$collection_id
		) );

		if ( ! $collection || $collection->user_id !== $user_id ) {
			return new WP_REST_Response( array( 'error' => 'Unauthorized' ), 401 );
		}

		// Delete item
		$wpdb->delete( $items_table, array( 'id' => $item_id, 'collection_id' => $collection_id ) );

		// Update item count
		$wpdb->query( $wpdb->prepare(
			"UPDATE $collections_table SET item_count = GREATEST(item_count - 1, 0) WHERE id = %d",
			$collection_id
		) );

		return new WP_REST_Response( array( 'success' => true ) );
	}

	/**
	 * Get public collections.
	 */
	public function get_public_collections( WP_REST_Request $request ) {
		$limit = $request->get_param( 'limit' );

		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_collections';

		$collections = $wpdb->get_results( $wpdb->prepare(
			"SELECT id, collection_name, description, color_code, item_count, view_count, created_at
			FROM $table_name
			WHERE is_public = 1
			ORDER BY view_count DESC, created_at DESC
			LIMIT %d",
			$limit
		) );

		return new WP_REST_Response( array( 'public_collections' => $collections ) );
	}

	/**
	 * Get collection item count.
	 */
	private function get_collection_item_count( $collection_id ) {
		global $wpdb;
		$items_table = $wpdb->prefix . 'naboo_collection_items';

		$count = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM $items_table WHERE collection_id = %d",
			$collection_id
		) );

		return (int) $count;
	}

	/**
	 * Enqueue scripts and styles.
	 */
	public function enqueue_scripts() {
		if ( ! is_user_logged_in() && ! ( is_page() || is_archive() || is_singular( 'psych_scale' ) ) ) {
			return;
		}

		wp_enqueue_script(
			$this->plugin_name . '-collections',
			plugins_url( 'js/scale-collections.js', __FILE__ ),
			array( 'jquery' ),
			$this->version,
			true
		);

		wp_enqueue_style(
			$this->plugin_name . '-collections',
			plugins_url( 'css/scale-collections.css', __FILE__ ),
			array(),
			$this->version
		);

		wp_localize_script(
			$this->plugin_name . '-collections',
			'apaCollections',
			array(
				'api_url'      => rest_url( 'apa/v1' ),
				'nonce'        => wp_create_nonce( 'wp_rest' ),
				'is_logged_in' => is_user_logged_in(),
			)
		);
	}

	/**
	 * Inject Add to Collection button on single scale pages.
	 */
	public function inject_add_to_collection_button( $content ) {
		if ( ! is_singular( 'psych_scale' ) ) {
			return $content;
		}

		$scale_id = get_the_ID();
		
		ob_start();
		?>
		<div class="naboo-collections-wrapper">
			<?php if ( is_user_logged_in() ) : ?>
				<button type="button" class="naboo-btn naboo-btn-outline naboo-add-collection-btn" data-scale-id="<?php echo esc_attr( $scale_id ); ?>">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 11v9h-5.328l-2.828 2.828-2.828-2.828H3v-9h16z"/><path d="M7 7h14v9h-2"/><path d="M12 11h2"/><path d="M11 14h4"/></svg>
					<?php esc_html_e( 'Add to Collection', 'naboodatabase' ); ?>
				</button>
				
				<!-- Collection Modal (Hidden by Default) -->
				<div class="naboo-collection-modal" id="naboo-collection-modal-<?php echo esc_attr( $scale_id ); ?>" style="display: none;">
					<div class="naboo-collection-modal-content">
						<span class="naboo-collection-close">&times;</span>
						<h3><?php esc_html_e( 'Save to Collection', 'naboodatabase' ); ?></h3>
						
						<div class="naboo-collection-list-container">
							<!-- Populated via AJAX -->
							<div class="naboo-collection-spinner"></div>
						</div>
						
						<div class="naboo-collection-create-new">
							<h4><?php esc_html_e( 'Create New Collection', 'naboodatabase' ); ?></h4>
							<input type="text" class="naboo-new-collection-name" placeholder="<?php esc_attr_e( 'Collection Name', 'naboodatabase' ); ?>">
							<input type="color" class="naboo-new-collection-color" value="#00796b">
							<button type="button" class="naboo-btn naboo-btn-primary naboo-create-collection-btn"><?php esc_html_e( 'Create & Save', 'naboodatabase' ); ?></button>
						</div>
					</div>
				</div>
			<?php else : ?>
				<button type="button" class="naboo-btn naboo-btn-outline" onclick="window.location.href='<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>'">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 11v9h-5.328l-2.828 2.828-2.828-2.828H3v-9h16z"/><path d="M7 7h14v9h-2"/><path d="M12 11h2"/><path d="M11 14h4"/></svg>
					<?php esc_html_e( 'Log in to Save to Collection', 'naboodatabase' ); ?>
				</button>
			<?php endif; ?>
		</div>
		<?php
		$button_html = ob_get_clean();

		return $content . $button_html;
	}
}
