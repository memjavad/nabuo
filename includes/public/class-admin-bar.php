<?php
/**
 * Admin Bar & Inline Editing Manager for Frontend
 *
 * @package ArabPsychology\NabooDatabase\Public
 */

namespace ArabPsychology\NabooDatabase\Public;

class Admin_Bar {

	/**
	 * Renders a fixed admin bar in the footer for quick publishing and navigation.
	 */
	public function render_admin_review_bar() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$post = get_post();
		$is_unpublished_scale = ( is_singular( 'psych_scale' ) && $post && $post->post_status !== 'publish' );

		$cache_key = 'naboo_admin_bar_next_' . ( $is_unpublished_scale && $post ? $post->ID : '0' );
		$next_url  = get_transient( $cache_key );

		if ( false === $next_url ) {
			$next_query_args = array(
				'post_type'      => 'psych_scale',
				'post_status'    => array( 'draft', 'pending', 'naboo_raw_draft' ),
				'posts_per_page' => 1,
				'no_found_rows'  => true, // Performance: skips SQL_CALC_FOUND_ROWS since we only need 1 post
				'orderby'        => 'date',
				'order'          => 'ASC',
			);

			if ( $is_unpublished_scale ) {
				$next_query_args['post__not_in'] = array( $post->ID );
			}

			$next_scale = new \WP_Query( $next_query_args );
			$next_url   = '';
			if ( $next_scale->have_posts() ) {
				$next_url = get_permalink( $next_scale->posts[0]->ID );
			}
			wp_reset_postdata();

			set_transient( $cache_key, $next_url ?: '__none__', 5 * MINUTE_IN_SECONDS );
		}

		if ( $next_url === '__none__' ) {
			$next_url = '';
		}

		if ( ! $next_url && ! $is_unpublished_scale ) {
			return;
		}

		$nonce = wp_create_nonce( 'naboo_publish_nonce' );
		?>
		<div id="naboo-admin-publish-wrap" style="position: fixed; bottom: 20px; right: 20px; z-index: 99999; display: flex; flex-direction: column; gap: 12px; align-items: flex-end;">
			<a href="<?php echo esc_url( admin_url() ); ?>" id="naboo-dashboard-btn" 
			   style="background: linear-gradient(135deg, #6366f1, #4f46e5); color: #fff; border: none; border-radius: 50px; padding: 10px 20px; font-weight: 600; font-size: 14px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; gap: 8px; text-decoration: none;">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
				<?php _e( 'Dashboard', 'naboodatabase' ); ?>
			</a>

			<?php if ( $next_url ) : ?>
				<a href="<?php echo esc_url( $next_url ); ?>" id="naboo-next-publish-btn" 
				   style="background: linear-gradient(135deg, #0ea5e9, #0284c7); color: #fff; border: none; border-radius: 50px; padding: 10px 20px; font-weight: 600; font-size: 14px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; gap: 8px; text-decoration: none;">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
					<?php _e( 'Next to Publish', 'naboodatabase' ); ?>
				</a>
			<?php endif; ?>

			<?php if ( $is_unpublished_scale ) : ?>
				<button id="naboo-publish-scale-btn" 
						data-post-id="<?php echo esc_attr( $post->ID ); ?>" 
						data-nonce="<?php echo esc_attr( $nonce ); ?>"
						style="background: linear-gradient(135deg, #059669, #047857); color: #fff; border: none; border-radius: 50px; padding: 12px 24px; font-weight: 700; font-size: 15px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; gap: 10px;">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
					<?php _e( 'Publish Scale', 'naboodatabase' ); ?>
				</button>
			<?php endif; ?>
		</div>
		<style>
			#naboo-dashboard-btn:hover, #naboo-publish-scale-btn:hover, #naboo-next-publish-btn:hover { transform: translateY(-2px); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); }
			#naboo-dashboard-btn:hover { background: linear-gradient(135deg, #4f46e5, #4338ca); }
			#naboo-next-publish-btn:hover { background: linear-gradient(135deg, #0284c7, #0369a1); }
			#naboo-publish-scale-btn:hover { background: linear-gradient(135deg, #047857, #065f46); }
			#naboo-dashboard-btn:active, #naboo-publish-scale-btn:active, #naboo-next-publish-btn:active { transform: translateY(0); }
			#naboo-publish-scale-btn:disabled { background: #9ca3af; cursor: not-allowed; transform: none; }
		</style>
		<?php
	}

	public function ajax_get_raw_field_value() {
		check_ajax_referer( 'naboo_search_nonce', 'nonce' );
		$post_id    = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$field_name = sanitize_text_field( $_POST['field_name'] ?? '' );
		if ( ! $post_id || empty( $field_name ) ) { wp_send_json_error( array( 'message' => __( 'Missing required data.', 'naboodatabase' ) ) ); }
		if ( ! current_user_can( 'edit_post', $post_id ) ) { wp_send_json_error( array( 'message' => __( 'You do not have permission to edit this scale.', 'naboodatabase' ) ) ); }

		if ( $field_name === 'author_details' ) { $current_value = get_post_meta( $post_id, '_naboo_scale_author_details', true ); }
		elseif ( $field_name === 'title' ) { $current_value = get_the_title( $post_id ); }
		else {
			$tax_map = array( 'year' => 'scale_year', 'language' => 'scale_language', 'test_type' => 'scale_test_type', 'format' => 'scale_format', 'age_group' => 'scale_age_group', 'authors' => 'scale_author', 'category' => 'scale_category' );
			if ( array_key_exists( $field_name, $tax_map ) ) { $current_value = implode( ', ', wp_get_object_terms( $post_id, $tax_map[ $field_name ], array( 'fields' => 'names' ) ) ); }
			else { $current_value = get_post_meta( $post_id, '_naboo_scale_' . $field_name, true ); }
		}
		wp_send_json_success( array( 'raw_text' => $current_value ) );
	}

	public function ajax_inline_manual_edit() {
		check_ajax_referer( 'naboo_search_nonce', 'nonce' );
		$post_id    = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$field_name = sanitize_text_field( $_POST['field_name'] ?? '' );
		$new_value  = isset( $_POST['new_value'] ) ? wp_unslash( $_POST['new_value'] ) : '';

		if ( ! $post_id || empty( $field_name ) ) { wp_send_json_error( array( 'message' => __( 'Missing required data.', 'naboodatabase' ) ) ); }
		if ( ! current_user_can( 'edit_post', $post_id ) ) { wp_send_json_error( array( 'message' => __( 'You do not have permission to edit this scale.', 'naboodatabase' ) ) ); }

		$sanitized_value = ( $field_name === 'r_code' ) ? $new_value : wp_kses_post( $new_value );

		if ( $field_name === 'title' ) {
			wp_update_post( array( 'ID' => $post_id, 'post_title' => sanitize_text_field( $sanitized_value ) ) );
			$formatted = esc_html( $sanitized_value );
		} else {
			if ( $field_name === 'author_details' ) {
				update_post_meta( $post_id, '_naboo_scale_author_details', $sanitized_value );
				$formatted = nl2br( make_clickable( esc_html( $sanitized_value ) ) );
			} elseif ( $field_name === 'author_orcid' ) {
				update_post_meta( $post_id, '_naboo_scale_author_orcid', sanitize_text_field( $sanitized_value ) );
				$orcids = preg_split('/[\s,]+/', trim( $sanitized_value ) );
				$orcid_links = array();
				foreach ($orcids as $orcid) {
					if ( ! empty( $orcid ) ) {
						$clean_orcid = preg_replace('/^https?:\/\/(www\.)?orcid\.org\//i', '', $orcid);
						$orcid_links[] = '<a href="https://orcid.org/' . esc_attr( $clean_orcid ) . '" target="_blank">' . esc_html( $clean_orcid ) . '</a>';
					}
				}
				$formatted = implode(', ', $orcid_links);
			} else {
				$tax_map = array( 'year' => 'scale_year', 'language' => 'scale_language', 'test_type' => 'scale_test_type', 'format' => 'scale_format', 'age_group' => 'scale_age_group', 'authors' => 'scale_author', 'category' => 'scale_category' );
				if ( array_key_exists( $field_name, $tax_map ) ) {
					$terms = array_filter( array_map( 'trim', explode( ',', $sanitized_value ) ) );
					wp_set_object_terms( $post_id, $terms, $tax_map[ $field_name ] );
					update_post_meta( $post_id, '_naboo_scale_' . $field_name, $sanitized_value ); 
					$formatted = get_the_term_list( $post_id, $tax_map[ $field_name ], '', ', ' );
				} else {
					update_post_meta( $post_id, '_naboo_scale_' . $field_name, $sanitized_value );
					if ( in_array( $field_name, array('abstract', 'purpose', 'construct', 'items_list', 'reliability', 'validity', 'factor_analysis', 'source_reference', 'scoring_rules', 'permissions', 'methodology') ) ) { $formatted = nl2br( wp_kses_post( $sanitized_value ) ); }
					elseif ( $field_name === 'r_code' ) { $formatted = trim( $sanitized_value ); }
					else { $formatted = esc_html( $sanitized_value ); }
				}
			}
		}

		// Re-sync index
		\ArabPsychology\NabooDatabase\Admin\Database_Indexer::sync_post( $post_id );

		wp_send_json_success( array( 'formatted_text' => $formatted ) );
	}

	public function get_admin_action_buttons( $field_key ) {
		if ( current_user_can( 'edit_post', get_the_ID() ) ) {
			$post_id = get_the_ID();
			$ai_btn = '<button type="button" class="naboo-inline-ai-refine-btn" data-post-id="' . $post_id . '" data-field="' . esc_attr( $field_key ) . '" title="' . esc_attr__( 'Refine directly with AI', 'naboodatabase' ) . '" style="background: linear-gradient(135deg, #0d9488, #0f766e); color: #fff; border: none; border-radius: 4px; padding: 2px 6px; font-size: 11px; cursor: pointer; margin-left: 8px; display: inline-flex; align-items: center; gap: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); vertical-align: middle;"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg> AI</button>';
			$edit_btn = '<button type="button" class="naboo-inline-manual-edit-btn" data-post-id="' . $post_id . '" data-field="' . esc_attr( $field_key ) . '" title="' . esc_attr__( 'Edit manually', 'naboodatabase' ) . '" style="background: var(--sc-surface); color: var(--sc-text-muted); border: 1px solid var(--sc-border); border-radius: 4px; padding: 2px 6px; font-size: 11px; cursor: pointer; margin-left: 4px; display: inline-flex; align-items: center; gap: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); vertical-align: middle; transition: all 0.2s;"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg> Edit</button>';
			return ' <span class="naboo-admin-actions">' . $ai_btn . $edit_btn . '</span>';
		}
		return '';
	}
}
