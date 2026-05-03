<?php

namespace ArabPsychology\NabooDatabase\Public;

class Related_Scales {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	public function enqueue_scripts() {
		wp_enqueue_style(
			$this->plugin_name . '-related-scales',
			plugins_url( 'css/related-scales.css', __FILE__ ),
			array(),
			$this->version
		);

		wp_enqueue_script(
			$this->plugin_name . '-related-scales',
			plugins_url( 'js/related-scales.js', __FILE__ ),
			array( 'jquery' ),
			$this->version,
			true
		);

		wp_localize_script( $this->plugin_name . '-related-scales', 'apaRelated', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'naboo_related_nonce' ),
		) );
	}

	public function inject_related_scales( $content ) {
		if ( ! is_singular( 'psych_scale' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$scale_id = get_the_ID();
		$related_scales = $this->get_related_scales( $scale_id );

		if ( empty( $related_scales ) ) {
			return $content;
		}

		$is_rtl = is_rtl();
		$title = $is_rtl ? 'مقاييس ذات صلة' : 'Related Scales';

		$html = '<div class="naboo-related-scales-section">';
		$html .= '<div class="naboo-related-header">';
		$html .= '<h3 class="naboo-related-title">' . esc_html( $title ) . '</h3>';
		
		// Slider Navigation
		$html .= '<div class="naboo-slider-nav">';
		$html .= '<button class="naboo-slider-prev" aria-label="Previous">❮</button>';
		$html .= '<button class="naboo-slider-next" aria-label="Next">❯</button>';
		$html .= '</div>';
		$html .= '</div>'; // .naboo-related-header

		$html .= '<div class="naboo-related-slider-viewport">';
		$html .= '<div class="naboo-related-scales-container" data-scale-id="' . esc_attr( $scale_id ) . '">';

		foreach ( $related_scales as $scale ) {
			ob_start();
			$this->render_related_scale_card( $scale );
			$html .= ob_get_clean();
		}

		$html .= '</div>'; // .naboo-related-scales-container
		$html .= '</div>'; // .naboo-related-slider-viewport
		$html .= '</div>'; // .naboo-related-scales-section

		return $content . $html;
	}

	public function get_related_scales( $scale_id, $limit = 6 ) {
		$cache_key = "related_scales_{$scale_id}_{$limit}";
		$cache_group = 'naboo_scales';
		$cached_scales = wp_cache_get( $cache_key, $cache_group );

		if ( false !== $cached_scales ) {
			return $cached_scales;
		}

		$scale = get_post( $scale_id );

		if ( ! $scale || 'psych_scale' !== $scale->post_type ) {
			return array();
		}

		$related = array();

		// Get related scales by category
		$related['by_category'] = $this->get_scales_by_category( $scale_id, $limit );

		// Get related scales by author
		$related['by_author'] = $this->get_scales_by_author( $scale_id, $limit );

		// Get related scales by keyword similarity
		$related['by_keyword'] = $this->get_scales_by_keyword( $scale_id, $limit );

		// Get popular recent scales
		$related['popular'] = $this->get_popular_scales( $scale_id, $limit );

		// Merge and deduplicate
		$merged = $this->merge_and_deduplicate( $related, $scale_id );

		// Sort by relevance score and limit
		usort( $merged, function( $a, $b ) {
			return $b['score'] <=> $a['score'];
		});

		$final_scales = array_slice( $merged, 0, $limit );
		wp_cache_set( $cache_key, $final_scales, $cache_group, 12 * 3600 ); // 12 hours

		return $final_scales;
	}

	private function get_scales_by_category( $scale_id, $limit = 6 ) {
		$scale_categories = wp_get_post_terms( $scale_id, 'scale_category', array( 'fields' => 'ids' ) );

		if ( empty( $scale_categories ) ) {
			return array();
		}

		$args = array(
			'post_type' => 'psych_scale',
			'posts_per_page' => $limit,
			'post__not_in' => array( $scale_id ),
			'tax_query' => array(
				array(
					'taxonomy' => 'scale_category',
					'field' => 'id',
					'terms' => $scale_categories,
					'operator' => 'IN',
				),
			),
			'orderby' => 'date',
			'order' => 'DESC',
		);

		$query = new \WP_Query( $args );
		$scales = array();

		foreach ( $query->posts as $post ) {
			$scales[] = array(
				'id' => $post->ID,
				'post' => $post,
				'score' => 3,
				'reason' => 'Same Category',
			);
		}

		wp_reset_postdata();
		return $scales;
	}

	private function get_scales_by_author( $scale_id, $limit = 6 ) {
		$scale_authors = wp_get_post_terms( $scale_id, 'scale_author', array( 'fields' => 'ids' ) );

		if ( empty( $scale_authors ) ) {
			return array();
		}

		$args = array(
			'post_type' => 'psych_scale',
			'posts_per_page' => $limit,
			'post__not_in' => array( $scale_id ),
			'tax_query' => array(
				array(
					'taxonomy' => 'scale_author',
					'field' => 'id',
					'terms' => $scale_authors,
					'operator' => 'IN',
				),
			),
			'orderby' => 'date',
			'order' => 'DESC',
		);

		$query = new \WP_Query( $args );
		$scales = array();

		foreach ( $query->posts as $post ) {
			$scales[] = array(
				'id' => $post->ID,
				'post' => $post,
				'score' => 2.5,
				'reason' => 'By Same Author',
			);
		}

		wp_reset_postdata();
		return $scales;
	}

	private function get_scales_by_keyword( $scale_id, $limit = 6 ) {
		$scale = get_post( $scale_id );

		if ( ! $scale ) {
			return array();
		}

		// Extract keywords from title and content
		$keywords = $this->extract_keywords( $scale->post_title . ' ' . $scale->post_content );

		if ( empty( $keywords ) ) {
			return array();
		}

		// Search for scales with similar keywords
		$args = array(
			'post_type' => 'psych_scale',
			'posts_per_page' => $limit,
			'post__not_in' => array( $scale_id ),
			's' => implode( ' ', array_slice( $keywords, 0, 3 ) ),
			'orderby' => 'relevance',
			'order' => 'DESC',
		);

		$query = new \WP_Query( $args );
		$scales = array();

		foreach ( $query->posts as $post ) {
			$scales[] = array(
				'id' => $post->ID,
				'post' => $post,
				'score' => 2,
				'reason' => 'Similar Keywords',
			);
		}

		wp_reset_postdata();
		return $scales;
	}

	private function get_popular_scales( $scale_id, $limit = 6 ) {
		global $wpdb;

		// Get most viewed scales in the last 90 days
		$ninety_days_ago = gmdate( 'Y-m-d H:i:s', strtotime( '-90 days' ) );

		$scale_categories = wp_get_post_terms( $scale_id, 'scale_category', array( 'fields' => 'ids' ) );

		if ( empty( $scale_categories ) ) {
			return array();
		}

		$category_ids = implode( ',', array_map( 'intval', $scale_categories ) );

		$query = "
			SELECT p.ID, p.post_title, p.post_content, p.post_date,
				   COUNT(pm.meta_id) as view_count
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_naboo_view_count'
			INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
			INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
			WHERE p.post_type = 'psych_scale'
			  AND p.post_status = 'publish'
			  AND p.ID != %d
			  AND tt.taxonomy = 'scale_category'
			  AND tt.term_id IN ($category_ids)
			GROUP BY p.ID
			ORDER BY view_count DESC, p.post_date DESC
			LIMIT %d
		";

		$results = $wpdb->get_results( $wpdb->prepare( $query, $scale_id, $limit ) );

		$scales = array();
		foreach ( $results as $row ) {
			$post = get_post( $row->ID );
			if ( $post ) {
				$scales[] = array(
					'id' => $post->ID,
					'post' => $post,
					'score' => 1.5,
					'reason' => 'Popular Recent',
					'views' => intval( $row->view_count ),
				);
			}
		}

		return $scales;
	}

	private function extract_keywords( $text, $max_keywords = 5 ) {
		// Convert to lowercase and extract words
		$text = strtolower( $text );

		// Remove common stop words
		$stop_words = array(
			'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for',
			'of', 'with', 'by', 'from', 'as', 'is', 'was', 'are', 'be', 'been',
			'being', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would',
			'should', 'could', 'may', 'might', 'can', 'this', 'that', 'these',
			'those', 'i', 'you', 'he', 'she', 'it', 'we', 'they',
		);

		// Extract words (alphanumeric only)
		preg_match_all( '/\b[a-z]{4,}\b/', $text, $matches );

		if ( empty( $matches[0] ) ) {
			return array();
		}

		// Count word frequency
		$word_freq = array_count_values( $matches[0] );

		// Remove stop words and sort by frequency
		foreach ( $stop_words as $stop_word ) {
			unset( $word_freq[ $stop_word ] );
		}

		arsort( $word_freq );

		return array_slice( array_keys( $word_freq ), 0, $max_keywords );
	}

	private function merge_and_deduplicate( $related, $scale_id ) {
		$merged = array();
		$ids_seen = array( $scale_id );

		// Merge all sources
		foreach ( $related as $source => $scales ) {
			foreach ( $scales as $scale ) {
				$id = $scale['id'];

				if ( in_array( $id, $ids_seen, true ) ) {
					continue;
				}

				$ids_seen[] = $id;

				if ( isset( $merged[ $id ] ) ) {
					// Increase score if scale appears in multiple sources
					$merged[ $id ]['score'] += $scale['score'];
					$merged[ $id ]['sources'][] = $source;
				} else {
					$scale['sources'] = array( $source );
					$merged[ $id ] = $scale;
				}
			}
		}

		return array_values( $merged );
	}

	private function render_related_scale_card( $scale ) {
		$post = $scale['post'];
		$categories = wp_get_post_terms( $post->ID, 'scale_category', array( 'fields' => 'all' ) );
		$view_count = get_post_meta( $post->ID, '_naboo_view_count', true );
		$items_count = get_post_meta( $post->ID, '_naboo_scale_items', true );

		?>
		<div class="naboo-related-scale-card">
			<div class="naboo-related-card-header">
				<h4 class="naboo-related-scale-title">
					<a href="<?php echo esc_url( get_permalink( $post->ID ) ); ?>">
						<?php echo esc_html( $post->post_title ); ?>
					</a>
				</h4>
				<span class="naboo-related-reason"><?php echo esc_html( $scale['reason'] ); ?></span>
			</div>

			<?php if ( ! empty( $categories ) ) : ?>
				<div class="naboo-related-categories">
					<?php foreach ( $categories as $category ) : ?>
						<a href="<?php echo esc_url( get_term_link( $category->term_id, 'scale_category' ) ); ?>" class="naboo-related-category-tag">
							<?php echo esc_html( $category->name ); ?>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( $post->post_excerpt ) : ?>
				<p class="naboo-related-excerpt">
					<?php echo esc_html( wp_trim_words( $post->post_excerpt, 15 ) ); ?>
				</p>
			<?php elseif ( $post->post_content ) : ?>
				<p class="naboo-related-excerpt">
					<?php echo esc_html( wp_trim_words( wp_strip_all_tags( $post->post_content ), 15 ) ); ?>
				</p>
			<?php endif; ?>

			<div class="naboo-related-meta">
				<?php if ( $items_count ) : ?>
					<span class="naboo-related-meta-item">
						<i class="naboo-icon">📊</i> <?php echo intval( $items_count ); ?> items
					</span>
				<?php endif; ?>
				<?php if ( $view_count ) : ?>
					<span class="naboo-related-meta-item">
						<i class="naboo-icon">👁️</i> <?php echo intval( $view_count ); ?> views
					</span>
				<?php endif; ?>
				<span class="naboo-related-meta-date">
					<?php echo esc_html( $this->time_ago( $post->post_date ) ); ?>
				</span>
			</div>
		</div>
		<?php
	}

	private function time_ago( $date ) {
		$time = strtotime( $date );
		$diff = time() - $time;

		if ( $diff < 60 ) {
			return 'Just now';
		} elseif ( $diff < 3600 ) {
			return intval( $diff / 60 ) . ' minutes ago';
		} elseif ( $diff < 86400 ) {
			return intval( $diff / 3600 ) . ' hours ago';
		} elseif ( $diff < 604800 ) {
			return intval( $diff / 86400 ) . ' days ago';
		} elseif ( $diff < 2592000 ) {
			return intval( $diff / 604800 ) . ' weeks ago';
		} elseif ( $diff < 31536000 ) {
			return intval( $diff / 2592000 ) . ' months ago';
		} else {
			return intval( $diff / 31536000 ) . ' years ago';
		}
	}

	public function ajax_get_related_scales() {
		check_ajax_referer( 'naboo_related_nonce' );

		$scale_id = intval( $_POST['scale_id'] ?? 0 );

		if ( ! $scale_id ) {
			wp_send_json_error( array( 'message' => 'Invalid scale ID' ) );
		}

		$related = $this->get_related_scales( $scale_id, 9 );

		if ( empty( $related ) ) {
			wp_send_json_error( array( 'message' => 'No related scales found' ) );
		}

		ob_start();
		foreach ( $related as $scale ) {
			$this->render_related_scale_card( $scale );
		}
		$html = ob_get_clean();

		wp_send_json_success( array(
			'html' => $html,
			'count' => count( $related ),
		) );
	}
}
