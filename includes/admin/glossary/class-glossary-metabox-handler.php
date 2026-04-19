<?php
/**
 * Glossary Metabox Handler - Handles glossary term metadata
 *
 * @package ArabPsychology\NabooDatabase\Admin\Glossary
 */

namespace ArabPsychology\NabooDatabase\Admin\Glossary;

/**
 * Glossary_Metabox_Handler class
 */
class Glossary_Metabox_Handler {

	/**
	 * Register metaboxes
	 *
	 * @param string $post_type Post type slug.
	 */
	public function register_metaboxes( $post_type ) {
		add_meta_box(
			'naboo_glossary_details',
			__( 'Term Details', 'naboodatabase' ),
			array( $this, 'render_metabox' ),
			$post_type,
			'normal',
			'high'
		);
	}

	/**
	 * Render the glossary details metabox
	 *
	 * @param \WP_Post $post Post object.
	 */
	public function render_metabox( $post ) {
		wp_nonce_field( 'naboo_save_glossary_details', 'naboo_glossary_details_nonce' );

		$arabic_term   = get_post_meta( $post->ID, '_naboo_glossary_arabic', true );
		$related_links = get_post_meta( $post->ID, '_naboo_glossary_related_links', true );

		?>
		<div class="naboo-metabox-field">
			<label for="naboo_glossary_arabic" style="display:block; font-weight:bold; margin-bottom:5px;">
				<?php esc_html_e( 'Arabic Translation', 'naboodatabase' ); ?>
			</label>
			<input type="text" id="naboo_glossary_arabic" name="naboo_glossary_arabic" 
			       value="<?php echo esc_attr( $arabic_term ); ?>" 
			       class="widefat" placeholder="<?php esc_attr_e( 'e.g., علم النفس', 'naboodatabase' ); ?>" 
			       style="text-align:right; direction:rtl; font-size:16px;" />
		</div>
		
		<div class="naboo-metabox-field" style="margin-top:20px;">
			<label for="naboo_glossary_related_links" style="display:block; font-weight:bold; margin-bottom:5px;">
				<?php esc_html_e( 'Related Resources (Links)', 'naboodatabase' ); ?>
			</label>
			<textarea id="naboo_glossary_related_links" name="naboo_glossary_related_links" 
			          class="widefat" rows="4" 
			          placeholder="<?php esc_attr_e( 'Enter one URL per line', 'naboodatabase' ); ?>"><?php echo esc_textarea( $related_links ); ?></textarea>
			<p class="description"><?php esc_html_e( 'Helpful links related to this term.', 'naboodatabase' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Save metabox data
	 *
	 * @param int $post_id Post ID.
	 */
	public function save_data( $post_id ) {
		if ( ! isset( $_POST['naboo_glossary_details_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['naboo_glossary_details_nonce'], 'naboo_save_glossary_details' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( isset( $_POST['post_type'] ) && 'naboo_glossary' === $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}
		} else {
			return;
		}

		if ( isset( $_POST['naboo_glossary_arabic'] ) ) {
			update_post_meta( $post_id, '_naboo_glossary_arabic', sanitize_text_field( $_POST['naboo_glossary_arabic'] ) );
		}

		if ( isset( $_POST['naboo_glossary_related_links'] ) ) {
			update_post_meta( $post_id, '_naboo_glossary_related_links', sanitize_textarea_field( $_POST['naboo_glossary_related_links'] ) );
		}
	}
}
