<?php

namespace ArabPsychology\NabooDatabase\Public;

use WP_REST_Request;
use WP_REST_Response;

/**
 * PDF Export Feature
 *
 * Allows exporting psychological scales as PDF documents.
 */
class PDF_Export {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register REST API endpoints.
	 */
	public function register_endpoints() {
		register_rest_route(
			'apa/v1',
			'/scales/(?P<id>\d+)/export-pdf',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'export_scale_pdf' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Export a scale as PDF.
	 */
	public function export_scale_pdf( WP_REST_Request $request ) {
		$scale_id = (int) $request->get_param( 'id' );
		$post     = get_post( $scale_id );

		if ( ! $post || 'psych_scale' !== $post->post_type || 'publish' !== $post->post_status ) {
			return new WP_REST_Response( array( 'error' => 'Scale not found' ), 404 );
		}

		try {
			$pdf_content = $this->generate_pdf( $post );
			return new WP_REST_Response(
				array(
					'success' => true,
					'pdf_data' => base64_encode( $pdf_content ),
				)
			);
		} catch ( \Exception $e ) {
			return new WP_REST_Response(
				array( 'error' => 'Failed to generate PDF: ' . $e->getMessage() ),
				500
			);
		}
	}

	/**
	 * Generate PDF content for a scale.
	 */
	private function generate_pdf( $post ) {
		$html = $this->build_pdf_html( $post );
		return $this->html_to_pdf( $html, $post->post_title );
	}

	/**
	 * Build HTML content for PDF.
	 */
	private function build_pdf_html( $post ) {
		$scale_id = $post->ID;
		$categories = wp_get_post_terms( $scale_id, 'scale_category', array( 'fields' => 'names' ) );
		$authors    = wp_get_post_terms( $scale_id, 'scale_author', array( 'fields' => 'names' ) );

		// Get meta data
		$items        = get_post_meta( $scale_id, '_naboo_scale_items', true );
		$reliability  = get_post_meta( $scale_id, '_naboo_scale_reliability', true );
		$validity     = get_post_meta( $scale_id, '_naboo_scale_validity', true );
		$year         = get_post_meta( $scale_id, '_naboo_scale_year', true );
		$language     = get_post_meta( $scale_id, '_naboo_scale_language', true );
		$population   = get_post_meta( $scale_id, '_naboo_scale_population', true );

		// Get rating info
		$rating_info = $this->get_rating_summary( $scale_id );

		$categories_str = ! empty( $categories ) ? implode( ', ', $categories ) : 'Not specified';
		$authors_str    = ! empty( $authors ) ? implode( ', ', $authors ) : 'Not specified';

		ob_start();
		require plugin_dir_path( __FILE__ ) . 'partials/pdf-export-template.php';
		$html = ob_get_clean();

		return $html;
	}

	/**
	 * Get rating summary for display in PDF.
	 */
	private function get_rating_summary( $scale_id ) {
		global $wpdb;
		$ratings_table = $wpdb->prefix . 'naboo_ratings';

		$results = $wpdb->get_row( $wpdb->prepare(
			"SELECT
				COUNT(*) as total_reviews,
				ROUND(AVG(rating), 1) as average_rating,
				SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
				SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
				SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
				SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
				SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
			FROM $ratings_table
			WHERE scale_id = %d AND status = 'approved'",
			$scale_id
		) );

		if ( ! $results || 0 === $results->total_reviews ) {
			return '';
		}

		$stars = str_repeat( '★', intval( $results->average_rating ) ) . 
		         str_repeat( '☆', 5 - intval( $results->average_rating ) );

		ob_start();
		require plugin_dir_path( __FILE__ ) . 'partials/pdf-export-rating-template.php';
		return ob_get_clean();
	}

	/**
	 * Convert HTML to PDF using client-side JavaScript.
	 * Returns HTML that will be processed by client.
	 */
	private function html_to_pdf( $html, $filename ) {
		return $html;
	}

	/**
	 * Enqueue scripts and styles.
	 */
	public function enqueue_scripts() {
		if ( ! is_singular( 'psych_scale' ) ) {
			return;
		}

		// Load html2pdf library
		wp_enqueue_script(
			'html2pdf-lib',
			'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js',
			array(),
			'0.10.1',
			true
		);

		wp_enqueue_script(
			$this->plugin_name . '-pdf-export',
			plugins_url( 'js/pdf-export.js', __FILE__ ),
			array( 'jquery', 'html2pdf-lib' ),
			$this->version,
			true
		);

		wp_enqueue_style(
			$this->plugin_name . '-pdf-export',
			plugins_url( 'css/pdf-export.css', __FILE__ ),
			array(),
			$this->version
		);

		wp_localize_script(
			$this->plugin_name . '-pdf-export',
			'apaPDFExport',
			array(
				'api_url'   => rest_url( 'apa/v1/' ),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
				'scale_id'  => get_the_ID(),
				'post_title' => get_the_title(),
			)
		);
	}

	/**
	 * Inject Export to PDF button
	 */
	public function inject_export_button( $content ) {
		if ( ! is_singular( 'psych_scale' ) ) {
			return $content;
		}

		$scale_id = get_the_ID();
		
		ob_start();
		?>
		<div class="naboo-pdf-export-action">
			<button type="button" class="naboo-btn naboo-btn-outline naboo-export-pdf-btn" data-scale-id="<?php echo esc_attr( $scale_id ); ?>">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M12 18v-6"/><path d="M9 15l3 3 3-3"/></svg>
				<?php esc_html_e( 'Export as PDF', 'naboodatabase' ); ?>
			</button>
		</div>
		<?php
		$button_html = ob_get_clean();

		return $content . $button_html;
	}
}
