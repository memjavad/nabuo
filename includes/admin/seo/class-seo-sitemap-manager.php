<?php
/**
 * SEO Sitemap Manager - Handles XML sitemap generation and management
 *
 * @package ArabPsychology\NabooDatabase\Admin\SEO
 */

namespace ArabPsychology\NabooDatabase\Admin\SEO;

/**
 * SEO_Sitemap_Manager class
 */
class SEO_Sitemap_Manager {

	/**
	 * Build XML for a chunk of URLs
	 *
	 * @param array $urls Array of URL data.
	 * @return string XML content.
	 */
	public function build_chunk_xml( $urls ) {
		$xml = new \XMLWriter();
		$xml->openMemory();
		$xml->setIndent( true );
		$xml->setIndentString( "\t" );
		
		$xml->startDocument( '1.0', 'UTF-8' );
		$xml->startElement( 'urlset' );
		$xml->writeAttribute( 'xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9' );
		$xml->writeAttribute( 'xmlns:image', 'http://www.google.com/schemas/sitemap-image/1.1' );

		foreach ( $urls as $url_data ) {
			if ( empty( $url_data['loc'] ) ) {
				continue;
			}
			$xml->startElement( 'url' );
			$xml->writeElement( 'loc', esc_url( $url_data['loc'] ) );
			
			if ( ! empty( $url_data['lastmod'] ) ) {
				$xml->writeElement( 'lastmod', esc_html( $url_data['lastmod'] ) );
			}
			if ( ! empty( $url_data['changefreq'] ) ) {
				$xml->writeElement( 'changefreq', esc_html( $url_data['changefreq'] ) );
			}
			if ( ! empty( $url_data['priority'] ) ) {
				$xml->writeElement( 'priority', esc_html( $url_data['priority'] ) );
			}
			
			// Images
			if ( ! empty( $url_data['images'] ) && is_array( $url_data['images'] ) ) {
				foreach ( $url_data['images'] as $img ) {
					if ( empty( $img['loc'] ) ) continue;
					$xml->startElement( 'image:image' );
					$xml->writeElement( 'image:loc', esc_url( $img['loc'] ) );
					if ( ! empty( $img['title'] ) ) {
						$xml->writeElement( 'image:title', esc_html( $img['title'] ) );
					}
					if ( ! empty( $img['caption'] ) ) {
						$xml->writeElement( 'image:caption', esc_html( $img['caption'] ) );
					}
					$xml->endElement(); // end image:image
				}
			}
			
			$xml->endElement(); // end url
		}

		$xml->endElement(); // end urlset
		$xml->endDocument();

		return $xml->outputMemory();
	}

	/**
	 * Internal function to construct the XML data and cache it or write to file
	 *
	 * @param array $options SEO Options.
	 * @return int|\WP_Error Number of links on success, or WP_Error on failure.
	 */
	public function generate_sitemap_xml( $options ) {
		// If explicitly set to 0, we abort.
		if ( isset( $options['enable_sitemap'] ) && empty( $options['enable_sitemap'] ) ) {
			return new \WP_Error( 'disabled', __( 'Sitemap generation is disabled in settings.', 'naboodatabase' ) );
		}

		// Base Setup Data
		$urls             = array();
		$sitemap_max_size = 1000; // Chunk size
		$total_links      = 0;

		// 1. Home URL
		$home_url_data = array(
			'loc'        => home_url( '/' ),
			'lastmod'    => gmdate( 'c' ),
			'changefreq' => 'daily',
			'priority'   => '1.0',
		);
		if ( ! empty( $options['publisher_logo_url'] ) ) {
			$home_url_data['images'] = array(
				array(
					'loc'   => $options['publisher_logo_url'],
					'title' => get_bloginfo( 'name' ),
				)
			);
		}
		$urls[] = $home_url_data;
		$total_links++;

		// 2. Scale Posts
		$args = array(
			'post_type'              => 'psych_scale',
			'post_status'            => 'publish',
			'posts_per_page'         => -1,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		);
		$query = new \WP_Query( $args );

		if ( ! empty( $query->posts ) ) {
			foreach ( $query->posts as $post_id ) {
				$url_data = array(
					'loc'        => get_permalink( $post_id ),
					'lastmod'    => get_the_modified_date( 'c', $post_id ),
					'changefreq' => 'weekly',
					'priority'   => '0.8',
				);

				// Add Image Data
				$image = false;
				if ( has_post_thumbnail( $post_id ) ) {
					$img_id = get_post_thumbnail_id( $post_id );
					$image = array(
						'loc'     => wp_get_attachment_image_url( $img_id, 'full' ),
						'title'   => get_the_title( $img_id ),
						'caption' => wp_get_attachment_caption( $img_id ),
					);
				} elseif ( ! empty( $options['social_image_url'] ) ) {
					$image = array(
						'loc'   => $options['social_image_url'],
						'title' => get_the_title( $post_id ) . ' - Logo',
					);
				}
				
				if ( $image ) {
					$url_data['images'] = array( $image );
				}

				$urls[] = $url_data;
				$total_links++;
			}
		}

		// 3. Taxonomies (Categories and Authors)
		$taxonomies = array( 'scale_category', 'scale_author' );
		foreach ( $taxonomies as $taxonomy ) {
			$terms = get_terms( array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => true,
			) );

			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
				foreach ( $terms as $term ) {
					// Get the last-modified date from the most recently modified post in this term.
					$latest_in_term = get_posts( array(
						'post_type'      => 'psych_scale',
						'post_status'    => 'publish',
						'posts_per_page' => 1,
						'orderby'        => 'modified',
						'order'          => 'DESC',
						'fields'         => 'ids',
						'tax_query'      => array(
							array(
								'taxonomy' => $taxonomy,
								'field'    => 'term_id',
								'terms'    => $term->term_id,
							),
						),
						'no_found_rows'          => true,
						'update_post_meta_cache' => false,
						'update_post_term_cache' => false,
					) );
					$term_lastmod = ! empty( $latest_in_term )
						? get_the_modified_date( 'c', $latest_in_term[0] )
						: gmdate( 'c' );

					$urls[] = array(
						'loc'        => get_term_link( $term ),
						'lastmod'    => $term_lastmod,
						'changefreq' => 'weekly',
						'priority'   => '0.6',
					);
					$total_links++;
				}
			}
		}

		// 4. Filter to allow extensions to the sitemap
		$urls = apply_filters( 'naboo_sitemap_urls', $urls );
		$total_links = count( $urls );

		if ( empty( $urls ) ) {
			return new \WP_Error( 'no_urls', __( 'No valid links found to generate sitemap.', 'naboodatabase' ) );
		}

		// 5. Chunk the URLs
		$chunks = array_chunk( $urls, $sitemap_max_size );
		$is_index = count( $chunks ) > 1;

		// Clear old transients
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_naboo_dynamic_sitemap%'" );

		// Clean up stale physical chunk files
		$chunk_index = 1;
		while ( file_exists( ABSPATH . "naboo-sitemap-{$chunk_index}.xml" ) ) {
			@unlink( ABSPATH . "naboo-sitemap-{$chunk_index}.xml" );
			$chunk_index++;
		}

		if ( $is_index ) {
			// GENERATE INDEX SITEMAP
			$index_xml = new \XMLWriter();
			$index_xml->openMemory();
			$index_xml->setIndent( true );
			$index_xml->setIndentString( "\t" );
			$index_xml->startDocument( '1.0', 'UTF-8' );
			$index_xml->startElement( 'sitemapindex' );
			$index_xml->writeAttribute( 'xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9' );

			foreach ( $chunks as $index => $chunk_urls ) {
				$chunk_num = $index + 1;
				
				// Calculate child XML
				$chunk_xml_content = $this->build_chunk_xml( $chunk_urls );
				
				// Write physically if possible
				$chunk_path = ABSPATH . "naboo-sitemap-{$chunk_num}.xml";
				file_put_contents( $chunk_path, $chunk_xml_content );
				
				// Cache dynamically
				set_transient( "naboo_dynamic_sitemap_{$chunk_num}", $chunk_xml_content, 12 * HOUR_IN_SECONDS );

				// Add to index
				$index_xml->startElement( 'sitemap' );
				$index_xml->writeElement( 'loc', home_url( "/naboo-sitemap-{$chunk_num}.xml" ) );
				$index_xml->writeElement( 'lastmod', gmdate('c') );
				$index_xml->endElement(); // end sitemap
			}

			$index_xml->endElement(); // end sitemapindex
			$index_xml->endDocument();

			$final_xml_content = $index_xml->outputMemory();

		} else {
			// GENERATE SINGLE SITEMAP
			$final_xml_content = $this->build_chunk_xml( $chunks[0] );
		}

		// 6. Save natively & set transient for dynamic serving
		$sitemap_path = ABSPATH . 'naboo-sitemap.xml';
		$written      = file_put_contents( $sitemap_path, $final_xml_content );
		
		// Set base transient
		set_transient( 'naboo_dynamic_sitemap', $final_xml_content, 12 * HOUR_IN_SECONDS );

		if ( $written !== false || get_transient( 'naboo_dynamic_sitemap' ) ) {
			return $total_links;
		} else {
			return new \WP_Error( 'write_failed', __( 'Failed to generate sitemap. Check permissions.', 'naboodatabase' ) );
		}
	}

	/**
	 * Serve sitemap dynamically if physical file is missing or intercepted by WP
	 *
	 * @param array $options SEO Options.
	 */
	public function handle_dynamic_request( $options ) {
		$request_uri = $_SERVER['REQUEST_URI'] ?? '';
		
		if ( preg_match('/\/naboo-sitemap(-[0-9]+)?\.xml$/', $request_uri, $matches) ) {
			
			if ( isset( $options['enable_sitemap'] ) && empty( $options['enable_sitemap'] ) ) {
				return;
			}
			
			$transient_key = 'naboo_dynamic_sitemap';
			if ( ! empty( $matches[1] ) ) {
				$chunk_num = str_replace( '-', '', $matches[1] );
				$transient_key = "naboo_dynamic_sitemap_{$chunk_num}";
			}

			$cached_xml = get_transient( $transient_key );
			if ( false === $cached_xml ) {
				$this->generate_sitemap_xml( $options );
				$cached_xml = get_transient( $transient_key );
			}

			if ( $cached_xml ) {
				header( 'Content-Type: application/xml; charset=utf-8' );
				header( 'X-Robots-Tag: noindex, follow', true );
				echo $cached_xml;
				exit;
			}
		}
	}

	/**
	 * Ping search engines
	 */
	public function ping_search_engines() {
		$sitemap_url = home_url( '/naboo-sitemap.xml' );

		$ping_urls = array(
			'google' => 'https://www.google.com/ping?sitemap=' . rawurlencode( $sitemap_url ),
			'bing'   => 'https://www.bing.com/ping?sitemap=' . rawurlencode( $sitemap_url ),
		);

		foreach ( $ping_urls as $engine => $url ) {
			wp_safe_remote_get( $url, array(
				'timeout'  => 5,
				'blocking' => false,
			) );
		}
	}
}
