<?php

namespace ArabPsychology\NabooDatabase\Public;

/**
 * Favorites/Bookmarking System
 * 
 * Allows users to save and organize favorite scales
 */
class Favorites {

	private $plugin_name;
	private $version;
	private $table_name;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'naboo_favorites';
	}

	/**
	 * Create favorites table
	 */
	public function create_table() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) NOT NULL,
			scale_id bigint(20) NOT NULL,
			folder varchar(255) DEFAULT 'default',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY user_scale (user_id, scale_id),
			KEY user_id (user_id),
			KEY scale_id (scale_id),
			KEY folder (folder)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Register REST API endpoints
	 */
	public function register_endpoints() {
		register_rest_route(
			'apa/v1',
			'/favorites',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_user_favorites' ),
				'permission_callback' => array( $this, 'check_user_permission' ),
			)
		);

		register_rest_route(
			'apa/v1',
			'/favorites',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'add_favorite' ),
				'permission_callback' => array( $this, 'check_user_permission' ),
			)
		);

		register_rest_route(
			'apa/v1',
			'/favorites/(?P<id>\d+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'remove_favorite' ),
				'permission_callback' => array( $this, 'check_user_permission' ),
			)
		);

		register_rest_route(
			'apa/v1',
			'/favorites/check/(?P<scale_id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'is_favorite' ),
				'permission_callback' => array( $this, 'check_user_permission' ),
			)
		);

		register_rest_route(
			'apa/v1',
			'/favorites/folder',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'create_folder' ),
				'permission_callback' => array( $this, 'check_user_permission' ),
			)
		);

		register_rest_route(
			'apa/v1',
			'/export/my-favorites',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'export_favorites_csv' ),
				'permission_callback' => array( $this, 'check_user_permission' ),
			)
		);
	}

	/**
	 * Export favorites to CSV
	 */
	public function export_favorites_csv( $request ) {
		$user_id = get_current_user_id();
		
		if ( ! $user_id ) {
			return new \WP_Error( 'not_logged_in', 'User must be logged in', array( 'status' => 401 ) );
		}

		global $wpdb;
		$favorites = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT f.folder, f.created_at, p.post_title, p.ID as scale_id
				 FROM {$this->table_name} f
				 LEFT JOIN {$wpdb->posts} p ON f.scale_id = p.ID
				 WHERE f.user_id = %d
				 ORDER BY f.created_at DESC",
				$user_id
			)
		);

		$csv_data = array();
		$csv_data[] = array( 'Scale ID', 'Title', 'URL', 'Folder', 'Favorited Date' );

		foreach ( $favorites as $fav ) {
			$csv_data[] = array(
				$fav->scale_id,
				$fav->post_title,
				get_permalink( $fav->scale_id ),
				$fav->folder,
				$fav->created_at
			);
		}

		$output = fopen( 'php://temp', 'r+' );
		fputs( $output, $bom = ( chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) ) );
		foreach ( $csv_data as $row ) {
			fputcsv( $output, $row );
		}
		rewind( $output );
		$csv_string = stream_get_contents( $output );
		fclose( $output );

		$response = new \WP_REST_Response( $csv_string );
		$response->header( 'Content-Type', 'text/csv; charset=UTF-8' );
		$response->header( 'Content-Disposition', 'attachment; filename="my-favorites.csv"' );

		return $response;
	}

	/**
	 * Get user's favorites
	 */
	public function get_user_favorites( $request ) {
		$user_id = get_current_user_id();
		
		if ( ! $user_id ) {
			return new \WP_Error( 'not_logged_in', 'User must be logged in', array( 'status' => 401 ) );
		}

		global $wpdb;
		$folder = $request->get_param( 'folder' ) ?: 'default';

		$query = "SELECT f.id, f.scale_id, f.folder, f.created_at, p.post_title 
		          FROM {$this->table_name} f
		          LEFT JOIN {$wpdb->posts} p ON f.scale_id = p.ID
		          WHERE f.user_id = %d";
		
		$params = array( $user_id );

		if ( $folder !== 'all' ) {
			$query .= " AND f.folder = %s";
			$params[] = $folder;
		}

		$query .= " ORDER BY f.created_at DESC";

		$favorites = $wpdb->get_results( $wpdb->prepare( $query, $params ) );

		return rest_ensure_response( $favorites );
	}

	/**
	 * Add a favorite
	 */
	public function add_favorite( $request ) {
		$user_id = get_current_user_id();
		
		if ( ! $user_id ) {
			return new \WP_Error( 'not_logged_in', 'User must be logged in', array( 'status' => 401 ) );
		}

		$scale_id = intval( $request->get_json_params()['scale_id'] ?? 0 );
		$folder = sanitize_text_field( $request->get_json_params()['folder'] ?? 'default' );

		if ( ! $scale_id ) {
			return new \WP_Error( 'invalid_scale', 'Invalid scale ID', array( 'status' => 400 ) );
		}

		global $wpdb;
		$result = $wpdb->insert(
			$this->table_name,
			array(
				'user_id'  => $user_id,
				'scale_id' => $scale_id,
				'folder'   => $folder,
			),
			array( '%d', '%d', '%s' )
		);

		if ( $result ) {
			return rest_ensure_response( array( 'success' => true, 'id' => $wpdb->insert_id ) );
		}

		return new \WP_Error( 'insert_failed', 'Failed to add favorite', array( 'status' => 500 ) );
	}

	/**
	 * Remove a favorite
	 */
	public function remove_favorite( $request ) {
		$user_id = get_current_user_id();
		
		if ( ! $user_id ) {
			return new \WP_Error( 'not_logged_in', 'User must be logged in', array( 'status' => 401 ) );
		}

		$id = intval( $request['id'] );

		global $wpdb;
		$wpdb->delete(
			$this->table_name,
			array( 'id' => $id, 'user_id' => $user_id ),
			array( '%d', '%d' )
		);

		return rest_ensure_response( array( 'success' => true ) );
	}

	/**
	 * Check if scale is favorite
	 */
	public function is_favorite( $request ) {
		$user_id = get_current_user_id();
		$scale_id = intval( $request['scale_id'] );

		if ( ! $user_id ) {
			return rest_ensure_response( array( 'is_favorite' => false ) );
		}

		global $wpdb;
		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$this->table_name} WHERE user_id = %d AND scale_id = %d",
				$user_id,
				$scale_id
			)
		);

		return rest_ensure_response( array( 'is_favorite' => ! empty( $result ) ) );
	}

	/**
	 * Create a favorites folder
	 */
	public function create_folder( $request ) {
		$user_id = get_current_user_id();
		
		if ( ! $user_id ) {
			return new \WP_Error( 'not_logged_in', 'User must be logged in', array( 'status' => 401 ) );
		}

		$folder_name = sanitize_text_field( $request->get_json_params()['folder_name'] ?? '' );

		if ( empty( $folder_name ) ) {
			return new \WP_Error( 'empty_folder', 'Folder name cannot be empty', array( 'status' => 400 ) );
		}

		// Store folder in user meta
		$folders = get_user_meta( $user_id, 'naboo_favorite_folders', true ) ?: array();
		$folders[] = $folder_name;
		update_user_meta( $user_id, 'naboo_favorite_folders', $folders );

		return rest_ensure_response( array( 'success' => true, 'folder' => $folder_name ) );
	}

	/**
	 * Check user permission
	 */
	public function check_user_permission() {
		return is_user_logged_in();
	}

	/**
	 * Add favorite button to scale page
	 */
	public function add_favorite_button() {
		if ( ! is_singular( 'psych_scale' ) ) {
			return;
		}

		$scale_id = get_the_ID();
		$user_id = get_current_user_id();
		?>
		<div class="naboo-favorite-button" data-scale-id="<?php echo esc_attr( $scale_id ); ?>">
			<button class="btn btn-favorite" id="add-to-favorites">
				<span class="icon">♡</span>
				<span class="text">Add to Favorites</span>
			</button>
		</div>
		<?php
	}

	/**
	 * Enqueue frontend scripts
	 */
	public function enqueue_scripts() {
		wp_enqueue_script(
			'naboo-favorites',
			plugin_dir_url( __FILE__ ) . 'js/favorites.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		wp_enqueue_style(
			'naboo-favorites',
			plugin_dir_url( __FILE__ ) . 'css/favorites.css',
			array(),
			$this->version
		);

		wp_localize_script(
			'naboo-favorites',
			'apaFavorites',
			array(
				'ajax_url'   => rest_url( 'apa/v1/favorites' ),
				'nonce'      => wp_create_nonce( 'wp_rest' ),
				'is_user'    => is_user_logged_in(),
				'current_user' => get_current_user_id(),
			)
		);
	}

	/**
	 * Add favorites to user dashboard
	 */
	public function add_favorites_dashboard_section() {
		if ( ! is_user_logged_in() ) {
			return;
		}

		$user_id = get_current_user_id();
		global $wpdb;

		$favorites = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT f.id, f.scale_id, f.folder, p.post_title, p.post_excerpt
				 FROM {$this->table_name} f
				 LEFT JOIN {$wpdb->posts} p ON f.scale_id = p.ID
				 WHERE f.user_id = %d
				 ORDER BY f.created_at DESC
				 LIMIT 10",
				$user_id
			)
		);

		$export_url = add_query_arg( '_wpnonce', wp_create_nonce( 'wp_rest' ), rest_url( 'apa/v1/export/my-favorites' ) );
		?>
		<div class="naboo-dashboard-section naboo-favorites-section">
			<div style="display: flex; justify-content: space-between; align-items: center;">
				<h3>My Favorites</h3>
				<a href="<?php echo esc_url( $export_url ); ?>" class="button button-small" style="background: #10b981; color: white; border: none; border-radius: 4px; padding: 6px 12px; font-size: 13px; text-decoration: none;">⬇ Export CSV</a>
			</div>
			<?php if ( $favorites ) : ?>
				<ul class="naboo-favorites-list">
					<?php foreach ( $favorites as $favorite ) : ?>
						<li>
							<a href="<?php echo esc_url( get_permalink( $favorite->scale_id ) ); ?>">
								<?php echo esc_html( $favorite->post_title ); ?>
							</a>
							<span class="folder-badge"><?php echo esc_html( $favorite->folder ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
				<a href="#my-favorites" class="view-all-link">View All Favorites →</a>
			<?php else : ?>
				<p>You haven't saved any favorites yet. <a href="<?php echo esc_url( home_url( '/scales' ) ); ?>">Browse scales</a></p>
			<?php endif; ?>
		</div>
		<?php
	}
}
