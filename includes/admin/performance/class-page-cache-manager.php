<?php
/**
 * Page Cache Manager - Handles page caching and object caching drops-ins
 *
 * @package ArabPsychology\NabooDatabase\Admin\Performance
 */

namespace ArabPsychology\NabooDatabase\Admin\Performance;

/**
 * Page_Cache_Manager class
 */
class Page_Cache_Manager {

	/**
	 * AJAX: Install object cache drop-in
	 */
	public function ajax_install_object_cache() {
		check_ajax_referer( 'naboo_performance_action', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied.' );
		}

		$source = dirname( dirname( dirname( __FILE__ ) ) ) . '/object-cache.php';
		$dest   = WP_CONTENT_DIR . '/object-cache.php';

		if ( ! file_exists( $source ) ) {
			wp_send_json_error( 'Source drop-in file missing.' );
		}

		if ( file_exists( $dest ) ) {
			$existing = file_get_contents( $dest );
			if ( strpos( $existing, 'LiteSpeed' ) !== false || strpos( $existing, 'W3 Total Cache' ) !== false ) {
				wp_send_json_error( 'Another object-cache.php is already installed. Please remove it first.' );
			}
		}

		if ( copy( $source, $dest ) ) {
			wp_send_json_success( 'Memcached Object Cache drop-in installed successfully!' );
		} else {
			wp_send_json_error( 'Failed to copy file to wp-content/object-cache.php. Check folder permissions.' );
		}
	}

	/**
	 * AJAX: Uninstall object cache drop-in
	 */
	public function ajax_uninstall_object_cache() {
		check_ajax_referer( 'naboo_performance_action', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied.' );
		}

		$dest = WP_CONTENT_DIR . '/object-cache.php';
		if ( ! file_exists( $dest ) ) {
			wp_send_json_success( 'No object-cache.php found to remove.' );
		}

		$content = file_get_contents( $dest );
		if ( strpos( $content, 'Naboo Memcached Object Cache' ) === false ) {
			wp_send_json_error( 'The existing object-cache.php was not created by Naboo Database.' );
		}

		if ( unlink( $dest ) ) {
			wp_send_json_success( 'Memcached Object Cache drop-in removed successfully.' );
		} else {
			wp_send_json_error( 'Failed to delete wp-content/object-cache.php.' );
		}
	}

	/**
	 * AJAX: Install page cache drop-in
	 */
	public function ajax_install_page_cache() {
		check_ajax_referer( 'naboo_performance_action', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied.' );
		}

		$source = dirname( dirname( dirname( __FILE__ ) ) ) . '/advanced-cache.php';
		$dest   = WP_CONTENT_DIR . '/advanced-cache.php';

		if ( ! file_exists( $source ) ) {
			wp_send_json_error( 'Source advanced-cache.php missing.' );
		}

		if ( copy( $source, $dest ) ) {
			// Update wp-config.php to enable WP_CACHE
			$wp_config_path = ABSPATH . 'wp-config.php';
			if ( file_exists( $wp_config_path ) ) {
				$config = file_get_contents( $wp_config_path );
				if ( strpos( $config, "define( 'WP_CACHE', true )" ) === false ) {
					$config = preg_replace( "/(<\?php)/", "$1\ndefine( 'WP_CACHE', true );", $config, 1 );
					file_put_contents( $wp_config_path, $config );
				}
			}
			wp_send_json_success( 'Page Cache drop-in installed and WP_CACHE enabled!' );
		} else {
			wp_send_json_error( 'Failed to copy advanced-cache.php.' );
		}
	}

	/**
	 * AJAX: Uninstall page cache drop-in
	 */
	public function ajax_uninstall_page_cache() {
		check_ajax_referer( 'naboo_performance_action', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied.' );
		}

		$dest = WP_CONTENT_DIR . '/advanced-cache.php';
		if ( file_exists( $dest ) ) {
			unlink( $dest );
		}

		$wp_config_path = ABSPATH . 'wp-config.php';
		if ( file_exists( $wp_config_path ) ) {
			$config = file_get_contents( $wp_config_path );
			$config = preg_replace( "/define\( 'WP_CACHE', true \);\s*/", "", $config );
			file_put_contents( $wp_config_path, $config );
		}

		wp_send_json_success( 'Page Cache removed and WP_CACHE disabled.' );
	}

	/**
	 * AJAX: Clear page cache
	 */
	public function ajax_clear_page_cache() {
		check_ajax_referer( 'naboo_performance_action', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied.' );
		}

		$cache_dir = WP_CONTENT_DIR . '/naboo-page-cache';
		if ( is_dir( $cache_dir ) ) {
			$this->recursive_rmdir( $cache_dir );
		}
		wp_send_json_success( 'Page cache cleared successfully.' );
	}

	/**
	 * Write page cache TTL config file
	 */
	public function write_page_cache_config( $old_value, $new_value ) {
		$ttl         = absint( $new_value['page_cache_ttl'] ?? 3600 );
		$config_file = WP_CONTENT_DIR . '/naboo-page-cache-config.php';
		$content     = "<?php\n// Naboo Page Cache Configuration\ndefine( 'NABOO_PAGE_CACHE_TTL', {$ttl} );\n";
		@file_put_contents( $config_file, $content );
	}

	private function recursive_rmdir( $dir ) {
		if ( ! is_dir( $dir ) ) {
			return;
		}
		$files = array_diff( scandir( $dir ), array( '.', '..' ) );
		foreach ( $files as $file ) {
			( is_dir( "$dir/$file" ) ) ? $this->recursive_rmdir( "$dir/$file" ) : unlink( "$dir/$file" );
		}
		return rmdir( $dir );
	}
}
