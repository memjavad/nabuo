<?php

namespace ArabPsychology\NabooDatabase\Public;

use WP_REST_Request;
use WP_REST_Response;

/**
 * File Download Features
 *
 * Manages scale file downloads, tracking, and statistics.
 */
class File_Download_Features {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Create download tracking table.
	 */
	public function create_table() {
		global $wpdb;
		$table_name      = $wpdb->prefix . 'naboo_file_downloads';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id BIGINT(20) NOT NULL AUTO_INCREMENT,
			file_id BIGINT(20) NOT NULL,
			scale_id BIGINT(20) NOT NULL,
			user_id BIGINT(20),
			user_ip VARCHAR(45),
			user_agent VARCHAR(255),
			download_count INT(11) DEFAULT 1,
			last_downloaded DATETIME,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY file_id (file_id),
			KEY scale_id (scale_id),
			KEY user_id (user_id),
			KEY last_downloaded (last_downloaded),
			UNIQUE KEY unique_download (file_id, user_ip, user_id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Register REST API endpoints.
	 */
	public function register_endpoints() {
		register_rest_route(
			'apa/v1',
			'/scales/(?P<id>\d+)/files',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_scale_files' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'apa/v1',
			'/files/(?P<file_id>\d+)/download',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'track_file_download' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'scale_id' => array(
						'type'     => 'integer',
						'required' => true,
					),
				),
			)
		);

		register_rest_route(
			'apa/v1',
			'/files/stats',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_download_stats' ),
				'permission_callback' => 'is_user_logged_in',
				'args'                => array(
					'period' => array(
						'type'              => 'string',
						'default'           => 'month',
						'enum'              => array( 'week', 'month', 'all' ),
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			'apa/v1',
			'/files/top',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_top_downloaded_files' ),
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
	 * Get files attached to a scale.
	 */
	public function get_scale_files( WP_REST_Request $request ) {
		$scale_id = (int) $request->get_param( 'id' );
		$post     = get_post( $scale_id );

		if ( ! $post || 'psych_scale' !== $post->post_type || 'publish' !== $post->post_status ) {
			return new WP_REST_Response( array( 'error' => 'Scale not found' ), 404 );
		}

		// Get the scale file from meta
		$file_id = get_post_meta( $scale_id, '_naboo_scale_file', true );

		if ( ! $file_id ) {
			return new WP_REST_Response( array( 'files' => array() ) );
		}

		$file = get_post( $file_id );
		if ( ! $file ) {
			return new WP_REST_Response( array( 'files' => array() ) );
		}

		$files = array( $this->format_file_data( $file, $scale_id ) );

		return new WP_REST_Response( array( 'files' => $files ) );
	}

	/**
	 * Format file data for response.
	 */
	private function format_file_data( $file, $scale_id ) {
		$file_url = wp_get_attachment_url( $file->ID );
		$download_count = $this->get_file_download_count( $file->ID );
		$file_size = $this->get_file_size( $file->ID );
		$mime_type = $file->post_mime_type;

		return array(
			'id'              => $file->ID,
			'title'           => $file->post_title ?: 'Scale Document',
			'filename'        => basename( $file_url ),
			'mime_type'       => $mime_type,
			'file_size'       => $file_size,
			'file_size_human' => size_format( $file_size ),
			'download_count'  => $download_count,
			'url'             => $file_url,
			'download_url'    => rest_url( "apa/v1/files/{$file->ID}/download" ),
		);
	}

	/**
	 * Get total downloads for a file.
	 */
	private function get_file_download_count( $file_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_file_downloads';

		$count = $wpdb->get_var( $wpdb->prepare(
			"SELECT SUM(download_count) FROM $table_name WHERE file_id = %d",
			$file_id
		) );

		return $count ? (int) $count : 0;
	}

	/**
	 * Get file size in bytes.
	 */
	private function get_file_size( $file_id ) {
		$file_path = get_attached_file( $file_id );
		if ( $file_path && file_exists( $file_path ) ) {
			return filesize( $file_path );
		}
		return 0;
	}

	/**
	 * Track file download and return download info.
	 */
	public function track_file_download( WP_REST_Request $request ) {
		$file_id  = (int) $request->get_param( 'file_id' );
		$scale_id = (int) $request->get_param( 'scale_id' );

		$file = get_post( $file_id );
		if ( ! $file || 'attachment' !== $file->post_type ) {
			return new WP_REST_Response( array( 'error' => 'File not found' ), 404 );
		}

		$scale = get_post( $scale_id );
		if ( ! $scale || 'psych_scale' !== $scale->post_type ) {
			return new WP_REST_Response( array( 'error' => 'Scale not found' ), 404 );
		}

		// Record download
		$this->record_download( $file_id, $scale_id );

		// Get file info
		$file_url = wp_get_attachment_url( $file_id );

		return new WP_REST_Response( array(
			'success'  => true,
			'file_url' => $file_url,
			'filename' => basename( $file_url ),
		) );
	}

	/**
	 * Record a file download in database.
	 */
	private function record_download( $file_id, $scale_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_file_downloads';
		$user_id    = get_current_user_id();
		$user_ip    = $this->get_client_ip();
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ), 0, 255 ) : '';

		$wpdb->query( $wpdb->prepare(
			"INSERT INTO $table_name (file_id, scale_id, user_id, user_ip, user_agent, download_count, last_downloaded)
			VALUES (%d, %d, %d, %s, %s, 1, NOW())
			ON DUPLICATE KEY UPDATE
			download_count = download_count + 1,
			last_downloaded = NOW()",
			$file_id,
			$scale_id,
			$user_id ?: 0,
			$user_ip,
			$user_agent
		) );
	}

	/**
	 * Get client IP address.
	 */
	private function get_client_ip() {
		if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
			$ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] )[0];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
		}

		return sanitize_text_field( trim( $ip ) );
	}

	/**
	 * Get download statistics (admin only).
	 */
	public function get_download_stats( WP_REST_Request $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_REST_Response( array( 'error' => 'Unauthorized' ), 403 );
		}

		$period = $request->get_param( 'period' );

		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_file_downloads';

		$where = '';
		switch ( $period ) {
			case 'week':
				$where = "WHERE last_downloaded >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
				break;
			case 'month':
				$where = "WHERE last_downloaded >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
				break;
		}

		$stats = $wpdb->get_row( $wpdb->prepare(
			"SELECT
				COUNT(DISTINCT file_id) as total_files,
				COUNT(DISTINCT user_ip) as unique_downloads,
				SUM(download_count) as total_downloads,
				COUNT(*) as total_records
			FROM $table_name
			$where"
		) );

		return new WP_REST_Response( array(
			'total_files'       => (int) $stats->total_files,
			'unique_downloads'  => (int) $stats->unique_downloads,
			'total_downloads'   => (int) $stats->total_downloads,
			'period'            => $period,
		) );
	}

	/**
	 * Get top downloaded files.
	 */
	public function get_top_downloaded_files( WP_REST_Request $request ) {
		$limit = $request->get_param( 'limit' );

		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_file_downloads';

		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT
				p.ID,
				p.post_title,
				ps.post_title as scale_title,
				ps.ID as scale_id,
				SUM(fd.download_count) as total_downloads,
				MAX(fd.last_downloaded) as last_downloaded
			FROM $table_name fd
			JOIN {$wpdb->posts} p ON fd.file_id = p.ID
			JOIN {$wpdb->posts} ps ON fd.scale_id = ps.ID
			WHERE ps.post_status = 'publish'
			GROUP BY fd.file_id
			ORDER BY total_downloads DESC, fd.last_downloaded DESC
			LIMIT %d",
			$limit
		) );

		$files = array_map( function( $file ) {
			return array(
				'file_id'        => (int) $file->ID,
				'file_title'     => $file->post_title,
				'scale_id'       => (int) $file->scale_id,
				'scale_title'    => $file->scale_title,
				'download_count' => (int) $file->total_downloads,
				'last_downloaded' => $file->last_downloaded,
			);
		}, $results );

		return new WP_REST_Response( array( 'top_files' => $files ) );
	}

	/**
	 * Enqueue scripts and styles.
	 */
	public function enqueue_scripts() {
		if ( ! is_singular( 'psych_scale' ) ) {
			return;
		}

		wp_enqueue_script(
			$this->plugin_name . '-file-downloads',
			plugins_url( 'js/file-download-features.js', __FILE__ ),
			array( 'jquery' ),
			$this->version,
			true
		);

		wp_enqueue_style(
			$this->plugin_name . '-file-downloads',
			plugins_url( 'css/file-download-features.css', __FILE__ ),
			array(),
			$this->version
		);

		wp_localize_script(
			$this->plugin_name . '-file-downloads',
			'apaFileDownloads',
			array(
				'api_url'   => rest_url( 'apa/v1' ),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
				'scale_id'  => get_the_ID(),
			)
		);
	}
}
