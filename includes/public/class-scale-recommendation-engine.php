<?php
/**
 * Scale Recommendation Engine
 *
 * @package ArabPsychology\NabooDatabase\Public
 */

namespace ArabPsychology\NabooDatabase\Public;

/**
 * Scale_Recommendation_Engine class - ML-based scale recommendations.
 */
class Scale_Recommendation_Engine {

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
		$this->create_table();
	}

	/**
	 * Create recommendations table
	 */
	private function create_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_recommendations';

		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === null ) {
			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE $table_name (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				user_id bigint(20),
				scale_id bigint(20),
				recommendation_score float,
				recommendation_reason varchar(255),
				recommendation_type varchar(100),
				generated_at datetime DEFAULT CURRENT_TIMESTAMP,
				clicked int DEFAULT 0,
				PRIMARY KEY (id),
				KEY user_id (user_id),
				KEY scale_id (scale_id),
				KEY recommendation_score (recommendation_score),
				UNIQUE KEY user_scale (user_id, scale_id)
			) $charset_collate;";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
		}
	}

	/**
	 * Register REST API endpoints
	 */
	public function register_endpoints() {
		register_rest_route(
			'apa/v1',
			'/recommendations/personalized',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_personalized_recommendations' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'apa/v1',
			'/recommendations/similar-scales',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_similar_scales' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'apa/v1',
			'/recommendations/trending',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_trending_recommendations' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'apa/v1',
			'/recommendations/click',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'track_recommendation_click' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'apa/v1',
			'/recommendations/generate-batch',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'generate_batch_recommendations' ),
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}

	/**
	 * Get personalized recommendations for user
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_personalized_recommendations( $request ) {
		$user_id = get_current_user_id();
		$limit = $request->get_param( 'limit' ) ? (int) $request->get_param( 'limit' ) : 5;

		if ( ! $user_id ) {
			// Return trending for anonymous users
			return $this->get_trending_recommendations( $request );
		}

		$recommendations = $this->calculate_personalized_recommendations( $user_id, $limit );

		return new \WP_REST_Response( array( 'recommendations' => $recommendations ), 200 );
	}

	/**
	 * Get similar scales
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_similar_scales( $request ) {
		$scale_id = $request->get_param( 'scale_id' );
		$limit = $request->get_param( 'limit' ) ? (int) $request->get_param( 'limit' ) : 5;

		if ( ! $scale_id ) {
			return new \WP_REST_Response(
				array( 'error' => 'Scale ID is required' ),
				400
			);
		}

		$scale = get_post( $scale_id );
		if ( ! $scale ) {
			return new \WP_REST_Response(
				array( 'error' => 'Scale not found' ),
				404
			);
		}

		// Get scale categories
		$scale_categories = wp_get_post_terms( $scale_id, 'scale_category', array( 'fields' => 'ids' ) );

		// Find similar scales
		$args = array(
			'post_type'      => 'psych_scale',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'post__not_in'   => array( $scale_id ),
			'tax_query'      => array(
				array(
					'taxonomy' => 'scale_category',
					'field'    => 'term_id',
					'terms'    => $scale_categories,
				),
			),
		);

		$similar = new \WP_Query( $args );
		$results = array();

		foreach ( $similar->posts as $post ) {
			$similarity_score = $this->calculate_similarity_score( $scale, $post );
			$results[] = array(
				'id'                 => $post->ID,
				'title'              => $post->post_title,
				'similarity_score'   => $similarity_score,
				'reason'             => 'Similar category and popularity',
			);
		}

		usort( $results, function( $a, $b ) {
			return $b['similarity_score'] <=> $a['similarity_score'];
		} );

		wp_reset_postdata();

		return new \WP_REST_Response( array( 'similar_scales' => array_slice( $results, 0, $limit ) ), 200 );
	}

	/**
	 * Get trending recommendations
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_trending_recommendations( $request ) {
		$limit = $request->get_param( 'limit' ) ? (int) $request->get_param( 'limit' ) : 5;

		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_popularity_analytics';

		$trending = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT scale_id, views, downloads, favorites, avg_rating 
				FROM $table_name 
				WHERE views > 0 
				ORDER BY (views + downloads * 2 + favorites * 3) DESC 
				LIMIT %d",
				$limit
			)
		);

		$results = array();
		foreach ( $trending as $item ) {
			$scale = get_post( $item->scale_id );
			if ( $scale ) {
				$results[] = array(
					'id'     => $scale->ID,
					'title'  => $scale->post_title,
					'score'  => $item->views + $item->downloads * 2 + $item->favorites * 3,
					'reason' => 'Trending with high engagement',
				);
			}
		}

		return new \WP_REST_Response( array( 'trending' => $results ), 200 );
	}

	/**
	 * Track recommendation click
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function track_recommendation_click( $request ) {
		$recommendation_id = (int) $request->get_param( 'recommendation_id' );

		global $wpdb;
		$table_name = $wpdb->prefix . 'naboo_recommendations';

		$wpdb->update(
			$table_name,
			array( 'clicked' => true ),
			array( 'id' => $recommendation_id ),
			array( '%d' ),
			array( '%d' )
		);

		return new \WP_REST_Response( array( 'message' => 'Click tracked' ), 200 );
	}

	/**
	 * Generate batch recommendations
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function generate_batch_recommendations( $request ) {
		$users = get_users( array( 'number' => 100 ) );
		$generated = 0;

		foreach ( $users as $user ) {
			$recommendations = $this->calculate_personalized_recommendations( $user->ID, 10 );
			$generated += count( $recommendations );
		}

		return new \WP_REST_Response(
			array( 'message' => sprintf( '%d recommendations generated', $generated ) ),
			200
		);
	}

	/**
	 * Calculate personalized recommendations
	 *
	 * @param int $user_id The user ID.
	 * @param int $limit   Maximum recommendations to return.
	 * @return array
	 */
	private function calculate_personalized_recommendations( $user_id, $limit ) {
		global $wpdb;

		// Get user's favorite categories
		$favorite_categories = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT tc.term_id, tc.name, COUNT(f.id) as count 
				FROM {$wpdb->prefix}naboo_favorites f
				JOIN {$wpdb->posts} p ON f.scale_id = p.ID
				JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
				JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
				JOIN {$wpdb->terms} tc ON tt.term_id = tc.term_id
				WHERE f.user_id = %d AND tt.taxonomy = 'scale_category'
				GROUP BY tc.term_id
				ORDER BY count DESC
				LIMIT 5",
				$user_id
			)
		);

		if ( empty( $favorite_categories ) ) {
			// Return trending if no favorites
			$trending = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT scale_id FROM {$wpdb->prefix}naboo_popularity_analytics 
					ORDER BY (views + downloads * 2) DESC 
					LIMIT %d",
					$limit
				)
			);

			$results = array();
			foreach ( $trending as $item ) {
				$scale = get_post( $item->scale_id );
				if ( $scale && $scale->post_status === 'publish' ) {
					$results[] = array(
						'id'     => $scale->ID,
						'title'  => $scale->post_title,
						'score'  => 0.5,
						'reason' => 'Popular in your category',
					);
				}
			}

			return array_slice( $results, 0, $limit );
		}

		$category_ids = wp_list_pluck( $favorite_categories, 'term_id' );
		$placeholders = implode( ',', array_fill( 0, count( $category_ids ), '%d' ) );

		// Get high-rated scales in favorite categories
		$recommended = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DISTINCT p.ID, p.post_title, pa.avg_rating, pa.views 
				FROM {$wpdb->posts} p
				JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
				JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
				JOIN {$wpdb->prefix}naboo_popularity_analytics pa ON p.ID = pa.scale_id
				WHERE p.post_type = 'psych_scale' 
				AND p.post_status = 'publish' 
				AND tt.taxonomy = 'scale_category'
				AND tt.term_id IN ($placeholders)
				AND p.ID NOT IN (SELECT scale_id FROM {$wpdb->prefix}naboo_favorites WHERE user_id = %d)
				ORDER BY (pa.avg_rating * 0.6 + LEAST(pa.views / 100, 10) * 0.4) DESC 
				LIMIT %d",
				array_merge( $category_ids, array( $user_id, $limit ) )
			)
		);

		$results = array();
		foreach ( $recommended as $rec ) {
			$score = ( $rec->avg_rating ?? 0 ) * 0.6 + min( ( $rec->views / 100 ), 10 ) * 0.4;
			$results[] = array(
				'id'     => $rec->ID,
				'title'  => $rec->post_title,
				'score'  => round( $score, 2 ),
				'reason' => 'Based on your interests and ratings',
			);
		}

		return array_slice( $results, 0, $limit );
	}

	/**
	 * Calculate similarity score between two scales
	 *
	 * @param \WP_Post $scale1 First scale.
	 * @param \WP_Post $scale2 Second scale.
	 * @return float
	 */
	private function calculate_similarity_score( $scale1, $scale2 ) {
		$score = 0;

		// Category similarity
		$categories1 = wp_get_post_terms( $scale1->ID, 'scale_category', array( 'fields' => 'ids' ) );
		$categories2 = wp_get_post_terms( $scale2->ID, 'scale_category', array( 'fields' => 'ids' ) );
		$common_cats = count( array_intersect( $categories1, $categories2 ) );
		$max_cats = max( count( $categories1 ), count( $categories2 ) );
		if ( $max_cats > 0 ) {
			$score += min( $common_cats / $max_cats, 1 ) * 60;
		}

		// Author similarity
		if ( $scale1->post_author === $scale2->post_author ) {
			$score += 20;
		}

		// Popularity similarity
		$views1 = (int) get_post_meta( $scale1->ID, '_naboo_view_count', true ) ?? 0;
		$views2 = (int) get_post_meta( $scale2->ID, '_naboo_view_count', true ) ?? 0;
		$view_diff = abs( $views1 - $views2 ) / max( $views1, $views2, 1 );
		$score += ( 1 - min( $view_diff, 1 ) ) * 20;

		return round( $score, 2 );
	}

	/**
	 * Enqueue styles
	 */
	public function enqueue_scripts() {
		if ( ! is_singular( 'psych_scale' ) ) {
			return;
		}

		wp_enqueue_style(
			$this->plugin_name . '-recommendations',
			plugin_dir_url( __FILE__ ) . 'css/scale-recommendations.css',
			array(),
			$this->version
		);
	}

	/**
	 * Inject recommendations at the bottom of single scale pages
	 */
	public function inject_recommendations_section( $content ) {
		if ( ! is_singular( 'psych_scale' ) ) {
			return $content;
		}

		$scale_id = get_the_ID();
		
		// Create a mock request to reuse our existing API logic
		$request = new \WP_REST_Request();
		$request->set_param( 'scale_id', $scale_id );
		$request->set_param( 'limit', 3 );
		
		$similar_response = $this->get_similar_scales( $request );
		$recommended_scales = array();

		if ( $similar_response instanceof \WP_REST_Response && $similar_response->get_status() === 200 ) {
			$data = $similar_response->get_data();
			if ( ! empty( $data['similar_scales'] ) ) {
				$recommended_scales = $data['similar_scales'];
			}
		}

		// Fallback to trending if no similar scales found
		if ( empty( $recommended_scales ) ) {
			$trending_response = $this->get_trending_recommendations( $request );
			if ( $trending_response instanceof \WP_REST_Response && $trending_response->get_status() === 200 ) {
				$data = $trending_response->get_data();
				if ( ! empty( $data['trending'] ) ) {
					$recommended_scales = $data['trending'];
				}
			}
		}

		if ( empty( $recommended_scales ) ) {
			return $content; // Nothing to recommend
		}

		ob_start();
		?>
		<div class="naboo-recommendations-wrapper">
			<h3 class="naboo-recommendations-title">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
				<?php esc_html_e( 'You May Also Like', 'naboodatabase' ); ?>
			</h3>
			
			<div class="naboo-recommendations-grid">
				<?php foreach ( $recommended_scales as $scale_data ) : 
					$rec_post = get_post( $scale_data['id'] );
					if ( ! $rec_post || $rec_post->ID == $scale_id ) continue;
					
					$reliability = get_post_meta( $rec_post->ID, '_naboo_scale_reliability', true );
					$items = get_post_meta( $rec_post->ID, '_naboo_scale_items', true );
					?>
					<a href="<?php echo esc_url( get_permalink( $rec_post->ID ) ); ?>" class="naboo-recommendation-card">
						<div class="naboo-rec-content">
							<h4><?php echo esc_html( $rec_post->post_title ); ?></h4>
							<p class="naboo-rec-reason"><?php echo esc_html( $scale_data['reason'] ?? '' ); ?></p>
						</div>
						
						<div class="naboo-rec-meta">
							<?php /* Validation indicator (reliability) removed per request */ ?>

							
							<?php if ( $items ) : ?>
								<span class="naboo-rec-meta-badge naboo-rec-meta-items">
									<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h7"/></svg>
									<?php echo esc_html( $items ); ?> items
								</span>
							<?php endif; ?>
						</div>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		$html = ob_get_clean();

		return $content . $html;
	}
}

