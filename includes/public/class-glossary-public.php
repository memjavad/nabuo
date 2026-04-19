<?php
/**
 * Glossary Frontend Functionality
 *
 * @package ArabPsychology\NabooDatabase\Public
 */

namespace ArabPsychology\NabooDatabase\Public;

/**
 * Glossary_Public class - Handles public-facing glossary features.
 */
class Glossary_Public {

	/** @var string */
	private $plugin_name;

	/** @var string */
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/* ─────────────────────── Assets ─────────────────────── */

	public function enqueue_assets() {
		global $post;
		if ( ! is_a( $post, 'WP_Post' ) || ! has_shortcode( $post->post_content, 'naboo_glossary' ) ) {
			if ( ! is_post_type_archive( 'naboo_glossary' ) && ! is_singular( 'naboo_glossary' ) ) {
				return;
			}
		}

		wp_enqueue_style(
			$this->plugin_name . '-glossary',
			plugin_dir_url( __FILE__ ) . 'css/glossary.css',
			array(),
			$this->version
		);

		wp_enqueue_script(
			$this->plugin_name . '-glossary',
			plugin_dir_url( __FILE__ ) . 'js/glossary.js',
			array(),
			$this->version,
			true
		);

		// Gather all settings for frontend consumption
		$settings = array(
			'restUrl'        => esc_url_raw( rest_url( 'naboo/v1/glossary' ) ),
			'nonce'          => wp_create_nonce( 'wp_rest' ),
			'perPage'        => (int) get_option( 'naboo_glossary_per_page', 50 ),
			'pagination'     => get_option( 'naboo_glossary_pagination', 'infinite' ),
			'showExcerpt'    => (bool) get_option( 'naboo_glossary_show_excerpt', true ),
			'showSecondary'  => (bool) get_option( 'naboo_glossary_show_secondary', true ),
			'showLetterIndex' => (bool) get_option( 'naboo_glossary_show_letter_index', true ),
			'accentColor'    => sanitize_hex_color( get_option( 'naboo_glossary_accent_color', '#6366f1' ) ),
			'cardRadius'     => absint( get_option( 'naboo_glossary_card_radius', 16 ) ),
			'i18n'           => array(
				'search'      => esc_html__( 'Search content...', 'naboodatabase' ),
				'all'         => esc_html__( 'All', 'naboodatabase' ),
				'noResults'   => esc_html__( 'No matching results found.', 'naboodatabase' ),
				'loading'     => esc_html__( 'Loading...', 'naboodatabase' ),
				'viewDetails' => esc_html__( 'View Details', 'naboodatabase' ),
				'page'        => esc_html__( 'Page', 'naboodatabase' ),
				'of'          => esc_html__( 'of', 'naboodatabase' ),
				'prev'        => esc_html__( '← Prev', 'naboodatabase' ),
				'next'        => esc_html__( 'Next →', 'naboodatabase' ),
				'items'       => esc_html__( 'items', 'naboodatabase' ),
			),
		);

		wp_localize_script( $this->plugin_name . '-glossary', 'nabooGlossaryConfig', $settings );
	}

	/* ─────────────────────── REST Endpoint ─────────────────────── */

	public function register_rest_routes() {
		register_rest_route( 'naboo/v1', '/glossary', array(
			'methods'             => \WP_REST_Server::READABLE,
			'callback'            => array( $this, 'rest_get_glossary_items' ),
			'permission_callback' => '__return_true',
			'args'                => array(
				'post_type'  => array(
					'default'           => 'naboo_glossary',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'meta_key'   => array(
					'default'           => '',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'letter'     => array(
					'default'           => 'all',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'search'     => array(
					'default'           => '',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'page'       => array(
					'default'           => 1,
					'sanitize_callback' => 'absint',
				),
				'per_page'   => array(
					'default'           => 50,
					'sanitize_callback' => 'absint',
				),
			),
		) );
	}

	public function rest_get_glossary_items( \WP_REST_Request $request ) {
		$post_type = $request->get_param( 'post_type' );
		$meta_key  = $request->get_param( 'meta_key' );
		$letter    = $request->get_param( 'letter' );
		$search    = $request->get_param( 'search' );
		$page      = max( 1, $request->get_param( 'page' ) );
		$per_page  = min( 200, max( 1, $request->get_param( 'per_page' ) ) );

		// Auto meta-key for known types
		if ( empty( $meta_key ) ) {
			if ( 'naboo_glossary' === $post_type ) {
				$meta_key = '_naboo_glossary_arabic';
			} elseif ( 'psych_scale' === $post_type ) {
				$meta_key = '_naboo_scale_author';
			}
		}

		// Build query args — no suppress_filters so our WHERE hooks work
		$args = array(
			'post_type'      => $post_type,
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'post_status'    => 'publish',
			'no_found_rows'  => false,
		);

		// ── Letter filter ──────────────────────────────────────────────
		$letter_filter = null;
		if ( 'all' !== $letter && '' !== $letter ) {
			if ( '#' === $letter ) {
				$letter_filter = function( $where ) {
					global $wpdb;
					$where .= " AND {$wpdb->posts}.post_title NOT REGEXP '^[a-zA-Z]'";
					return $where;
				};
			} else {
				$safe_letter  = esc_sql( $letter );
				$letter_filter = function( $where ) use ( $safe_letter ) {
					global $wpdb;
					$where .= $wpdb->prepare(
						" AND {$wpdb->posts}.post_title LIKE %s",
						$wpdb->esc_like( $safe_letter ) . '%'
					);
					return $where;
				};
			}
			add_filter( 'posts_where', $letter_filter, 10 );
		}

		// ── Search filter (via native s param for title search) ────────
		if ( ! empty( $search ) ) {
			// Use native WP search — fast & reliable
			$args['s']                    = $search;
			$args['orderby']              = 'relevance';
			// Force title-only search by overriding the search SQL
			add_filter( 'posts_search', function( $sql, $query ) use ( $search ) {
				if ( ! $query->is_main_query() ) {
					global $wpdb;
					$like = '%' . $wpdb->esc_like( $search ) . '%';
					return $wpdb->prepare(
						" AND ({$wpdb->posts}.post_title LIKE %s)",
						$like
					);
				}
				return $sql;
			}, 10, 2 );
		}

		$query = new \WP_Query( $args );

		// Clean up filters after query
		if ( $letter_filter ) {
			remove_filter( 'posts_where', $letter_filter, 10 );
		}
		remove_all_filters( 'posts_search' );

		$posts = $query->posts;

		$items = array();
		foreach ( $posts as $post ) {
			$first_char   = mb_substr( $post->post_title, 0, 1 );
			$first_letter = preg_match( '/[A-Za-z]/', $first_char ) ? strtoupper( $first_char ) : '#';

			$secondary_val = '';
			if ( ! empty( $meta_key ) ) {
				$secondary_val = get_post_meta( $post->ID, $meta_key, true );
				if ( is_array( $secondary_val ) ) {
					$secondary_val = implode( ', ', $secondary_val );
				}
			}

			$excerpt = '';
			if ( has_excerpt( $post->ID ) ) {
				$excerpt = wp_strip_all_tags( get_the_excerpt( $post->ID ) );
			} else {
				$excerpt = wp_trim_words( wp_strip_all_tags( $post->post_content ), 20 );
			}

			$items[] = array(
				'id'          => $post->ID,
				'title'       => $post->post_title,
				'letter'      => $first_letter,
				'secondary'   => (string) $secondary_val,
				'excerpt'     => $excerpt,
				'url'         => get_permalink( $post->ID ),
			);
		}

		return rest_ensure_response( array(
			'items'       => $items,
			'total'       => (int) $query->found_posts,
			'total_pages' => (int) $query->max_num_pages,
			'page'        => $page,
			'per_page'    => $per_page,
		) );
	}

	/* ─────────────────────── Shortcode ─────────────────────── */

	public function render_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'layout'         => get_option( 'naboo_glossary_layout', 'grid' ),
			'post_type'      => 'naboo_glossary',
			'type'           => '',
			'meta_key'       => '',
			'meta_label'     => '',
			'per_page'       => get_option( 'naboo_glossary_per_page', 50 ),
			'fullscreen'     => get_option( 'naboo_glossary_fullscreen', '0' ),
		), $atts, 'naboo_glossary' );

		$target_type = ! empty( $atts['type'] ) ? $atts['type'] : $atts['post_type'];
		$target_type = sanitize_text_field( $target_type );

		// Smart defaults for meta_label
		if ( empty( $atts['meta_label'] ) && 'psych_scale' === $target_type ) {
			$atts['meta_label'] = esc_html__( 'Author', 'naboodatabase' );
		}

		$fullscreen_class = ( '1' === (string) $atts['fullscreen'] ) ? ' naboo-glossary-fullscreen' : '';

		$alphabet = array_merge( range( 'A', 'Z' ), array( '#' ) );

		ob_start();
		?>
		<div id="naboo-glossary-app"
		     class="naboo-glossary-app layout-<?php echo esc_attr( $atts['layout'] ); ?><?php echo esc_attr( $fullscreen_class ); ?>"
		     data-post-type="<?php echo esc_attr( $target_type ); ?>"
		     data-meta-key="<?php echo esc_attr( $atts['meta_key'] ); ?>"
		     data-meta-label="<?php echo esc_attr( $atts['meta_label'] ); ?>"
		     data-per-page="<?php echo absint( $atts['per_page'] ); ?>">

			<!-- Top Controls -->
			<div class="ngg-controls">
				<div class="ngg-search-wrap">
					<svg class="ngg-search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
					<input type="search" id="ngg-search" class="ngg-search-input" 
					       placeholder="<?php esc_attr_e( 'Search...', 'naboodatabase' ); ?>"
					       autocomplete="off" />
					<button class="ngg-search-clear" id="ngg-search-clear" aria-label="Clear search">✕</button>
				</div>
				<div class="ngg-stats" id="ngg-stats">
					<span id="ngg-count"></span>
				</div>
			</div>

			<!-- Alphabet Navigation -->
			<div class="ngg-alpha-nav" id="ngg-alpha-nav">
				<button class="ngg-alpha-btn active" data-letter="all"><?php esc_html_e( 'All', 'naboodatabase' ); ?></button>
				<?php foreach ( $alphabet as $letter ) : ?>
					<button class="ngg-alpha-btn" data-letter="<?php echo esc_attr( $letter ); ?>"><?php echo esc_html( $letter ); ?></button>
				<?php endforeach; ?>
			</div>

			<!-- Items Container -->
			<div class="ngg-items-wrap">
				<div class="ngg-items" id="ngg-items">
					<!-- Skeleton loaders -->
					<?php for ( $i = 0; $i < 6; $i++ ) : ?>
						<div class="ngg-skeleton">
							<div class="ngg-skeleton-title"></div>
							<div class="ngg-skeleton-sub"></div>
							<div class="ngg-skeleton-text"></div>
							<div class="ngg-skeleton-text short"></div>
						</div>
					<?php endfor; ?>
				</div>

				<!-- Loader -->
				<div class="ngg-loader" id="ngg-loader" style="display:none;">
					<div class="ngg-spinner"></div>
					<span><?php esc_html_e( 'Loading...', 'naboodatabase' ); ?></span>
				</div>

				<!-- No Results -->
				<div class="ngg-empty" id="ngg-empty" style="display:none;">
					<div class="ngg-empty-icon">🔍</div>
					<p><?php esc_html_e( 'No matching results found.', 'naboodatabase' ); ?></p>
				</div>
			</div>

			<!-- Pagination Bar (shown for 'pagination' mode) -->
			<div class="ngg-pagination" id="ngg-pagination" style="display:none;">
				<button class="ngg-page-btn" id="ngg-prev" disabled><?php esc_html_e( '← Prev', 'naboodatabase' ); ?></button>
				<span class="ngg-page-info" id="ngg-page-info"></span>
				<button class="ngg-page-btn" id="ngg-next"><?php esc_html_e( 'Next →', 'naboodatabase' ); ?></button>
			</div>

			<!-- Infinite scroll sentinel -->
			<div id="ngg-sentinel" style="height:10px;"></div>
		</div>
		<?php
		return ob_get_clean();
	}
}
