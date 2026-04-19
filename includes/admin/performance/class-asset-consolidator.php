<?php
/**
 * Asset Consolidator - Handles CSS/JS minification and consolidation
 *
 * @package ArabPsychology\NabooDatabase\Admin\Performance
 */

namespace ArabPsychology\NabooDatabase\Admin\Performance;

/**
 * Asset_Consolidator class
 */
class Asset_Consolidator {

	/**
	 * Dequeue specific asset handles if requested
	 */
	public function dequeue_theme_assets_action() {
		$theme_uri       = get_template_directory_uri();
		$child_theme_uri = get_stylesheet_directory_uri();

		global $wp_styles, $wp_scripts;

		if ( is_object( $wp_styles ) && isset( $wp_styles->queue ) ) {
			foreach ( $wp_styles->queue as $handle ) {
				if ( isset( $wp_styles->registered[ $handle ] ) ) {
					$src = $wp_styles->registered[ $handle ]->src;
					if ( strpos( $src, $theme_uri ) !== false || strpos( $src, $child_theme_uri ) !== false ) {
						wp_dequeue_style( $handle );
						wp_deregister_style( $handle );
					}
				}
			}
		}

		if ( is_object( $wp_scripts ) && isset( $wp_scripts->queue ) ) {
			foreach ( $wp_scripts->queue as $handle ) {
				if ( isset( $wp_scripts->registered[ $handle ] ) ) {
					$src = $wp_scripts->registered[ $handle ]->src;
					if ( strpos( $src, $theme_uri ) !== false || strpos( $src, $child_theme_uri ) !== false ) {
						wp_dequeue_script( $handle );
						wp_deregister_script( $handle );
					}
				}
			}
		}
	}

	/**
	 * Main asset consolidation engine
	 */
	public function enqueue_consolidated_assets() {
		if ( is_admin() ) {
			return;
		}

		global $wp_styles, $wp_scripts;

		$upload_dir = wp_upload_dir();
		$cache_dir  = $upload_dir['basedir'] . '/naboo_optimizer_cache';
		$cache_url  = $upload_dir['baseurl'] . '/naboo_optimizer_cache';

		if ( ! file_exists( $cache_dir ) ) {
			wp_mkdir_p( $cache_dir );
		}

		$css_files         = array();
		$js_files          = array();
		$localized_scripts = '';

		foreach ( $wp_styles->queue as $handle ) {
			if ( strpos( $handle, 'naboo' ) !== false || strpos( $handle, 'apa' ) !== false ) {
				if ( isset( $wp_styles->registered[ $handle ] ) ) {
					$src = $wp_styles->registered[ $handle ]->src;
					if ( strpos( $src, 'http' ) === false && strpos( $src, '//' ) !== 0 ) {
						$src = site_url( $src );
					}
					if ( strpos( $src, site_url() ) !== false ) {
						$path = str_replace( site_url(), ABSPATH, $src );
						$path = strtok( $path, '?' );
						if ( file_exists( $path ) ) {
							$css_files[] = $path;
							wp_dequeue_style( $handle );
						}
					}
				}
			}
		}

		foreach ( $wp_scripts->queue as $handle ) {
			if ( strpos( $handle, 'naboo' ) !== false || strpos( $handle, 'apa' ) !== false ) {
				if ( isset( $wp_scripts->registered[ $handle ] ) ) {
					$src = $wp_scripts->registered[ $handle ]->src;

					$data = $wp_scripts->get_data( $handle, 'data' );
					if ( $data ) {
						$localized_scripts .= "/* Data for $handle */\n" . $data . "\n";
					}

					$before = $wp_scripts->get_data( $handle, 'before' );
					if ( ! empty( $before ) && is_array( $before ) ) {
						$localized_scripts .= "/* Before $handle */\n" . implode( "\n", $before ) . "\n";
					}

					$after = $wp_scripts->get_data( $handle, 'after' );
					if ( ! empty( $after ) && is_array( $after ) ) {
						$localized_scripts .= "/* After $handle */\n" . implode( "\n", $after ) . "\n";
					}

					if ( strpos( $src, 'http' ) === false && strpos( $src, '//' ) !== 0 ) {
						$src = site_url( $src );
					}

					if ( strpos( $src, site_url() ) !== false ) {
						$path = str_replace( site_url(), ABSPATH, $src );
						$path = strtok( $path, '?' );
						if ( file_exists( $path ) ) {
							$js_files[] = $path;
							wp_dequeue_script( $handle );
						}
					}
				}
			}
		}

		if ( ! empty( $css_files ) ) {
			sort( $css_files );
			$css_mtimes     = array_map( 'filemtime', $css_files );
			$css_hash       = md5( implode( ',', $css_files ) . implode( ',', $css_mtimes ) . NABOODATABASE_VERSION );
			$css_cache_file = "consolidated-$css_hash.css";
			$css_cache_path = "$cache_dir/$css_cache_file";

			if ( ! file_exists( $css_cache_path ) ) {
				$this->clean_asset_cache_directory( $cache_dir );
				$combined_css = '';
				foreach ( $css_files as $file ) {
					$combined_css .= "/* Source: " . basename( $file ) . " */\n";
					$combined_css .= file_get_contents( $file ) . "\n";
				}
				$combined_css = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $combined_css );
				$combined_css = str_replace( array( "\r\n", "\r", "\n", "\t", '  ', '    ' ), '', $combined_css );
				file_put_contents( $css_cache_path, $combined_css );
			}
			wp_enqueue_style( 'naboo-consolidated-css', "$cache_url/$css_cache_file", array(), NABOODATABASE_VERSION );
		}

		if ( ! empty( $js_files ) ) {
			sort( $js_files );
			$js_mtimes     = array_map( 'filemtime', $js_files );
			$js_hash       = md5( implode( ',', $js_files ) . implode( ',', $js_mtimes ) . NABOODATABASE_VERSION );
			$js_cache_file = "consolidated-$js_hash.js";
			$js_cache_path = "$cache_dir/$js_cache_file";

			if ( ! file_exists( $js_cache_path ) ) {
				$this->clean_asset_cache_directory( $cache_dir );
				$combined_js = '';
				foreach ( $js_files as $file ) {
					$combined_js .= "/* Source: " . basename( $file ) . " */\n";
					$combined_js .= file_get_contents( $file ) . ";\n";
				}
				file_put_contents( $js_cache_path, $combined_js );
			}
			wp_enqueue_script( 'naboo-consolidated-js', "$cache_url/$js_cache_file", array( 'jquery' ), NABOODATABASE_VERSION, true );
			if ( ! empty( $localized_scripts ) ) {
				wp_add_inline_script( 'naboo-consolidated-js', $localized_scripts, 'before' );
			}
		}
	}

	public function ajax_clear_cache() {
		check_ajax_referer( 'naboo_clear_cache', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		$upload_dir = wp_upload_dir();
		$cache_dir  = $upload_dir['basedir'] . '/naboo_optimizer_cache';

		if ( is_dir( $cache_dir ) ) {
			$files = glob( $cache_dir . '/*.*' );
			foreach ( $files as $file ) {
				if ( is_file( $file ) ) {
					unlink( $file );
				}
			}
		}
		wp_send_json_success( __( 'Asset cache cleared.', 'naboodatabase' ) );
	}

	private function clean_asset_cache_directory( $cache_dir ) {
		if ( ! is_dir( $cache_dir ) ) {
			return;
		}
		$files = glob( $cache_dir . '/consolidated-*.*' );
		if ( empty( $files ) ) {
			return;
		}
		$now     = time();
		$max_age = 48 * 3600;
		foreach ( $files as $file ) {
			if ( is_file( $file ) && ( $now - filemtime( $file ) >= $max_age ) ) {
				unlink( $file );
			}
		}
	}
}
