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
	protected function generate_pdf( $post ) {
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

		$html = <<<HTML
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<style>
		* {
			margin: 0;
			padding: 0;
			box-sizing: border-box;
		}
		body {
			font-family: Arial, sans-serif;
			line-height: 1.6;
			color: #333;
			background: #fff;
		}
		.pdf-container {
			max-width: 8.5in;
			height: 11in;
			padding: 0.5in;
			margin: 0 auto;
			background: #fff;
		}
		.pdf-header {
			border-bottom: 3px solid #00796b;
			padding-bottom: 12px;
			margin-bottom: 24px;
		}
		.pdf-title {
			font-size: 24px;
			font-weight: bold;
			color: #1a3a52;
			margin-bottom: 8px;
		}
		.pdf-meta {
			font-size: 11px;
			color: #666;
		}
		.pdf-meta span {
			margin-right: 16px;
		}
		.pdf-section {
			margin-bottom: 20px;
			page-break-inside: avoid;
		}
		.pdf-section-title {
			font-size: 14px;
			font-weight: bold;
			color: #1a3a52;
			background: #f5f5f5;
			padding: 8px 12px;
			margin-bottom: 12px;
			border-left: 4px solid #00796b;
		}
		.pdf-section-content {
			padding: 0 12px;
		}
		.pdf-description {
			font-size: 11px;
			line-height: 1.5;
			margin-bottom: 12px;
			text-align: justify;
		}
		.pdf-grid {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 16px;
			font-size: 11px;
			margin-bottom: 12px;
		}
		.pdf-grid-item {
			padding: 8px;
			background: #f9fafb;
			border-radius: 4px;
		}
		.pdf-grid-label {
			font-weight: bold;
			color: #00796b;
			margin-bottom: 4px;
		}
		.pdf-grid-value {
			color: #333;
		}
		.pdf-rating-box {
			background: #e8f5e9;
			border: 1px solid #00796b;
			padding: 8px;
			margin-bottom: 12px;
			border-radius: 4px;
			font-size: 11px;
		}
		.pdf-rating-title {
			font-weight: bold;
			color: #00796b;
			margin-bottom: 6px;
		}
		.pdf-rating-item {
			display: flex;
			justify-content: space-between;
			margin-bottom: 4px;
		}
		.pdf-footer {
			position: absolute;
			bottom: 0.5in;
			width: 7.5in;
			border-top: 1px solid #ddd;
			padding-top: 8px;
			font-size: 9px;
			color: #999;
			text-align: center;
		}
		.pdf-stars {
			color: #ffc107;
			letter-spacing: 2px;
		}
	</style>
</head>
<body>
	<div class="pdf-container">
		<div class="pdf-header">
			<div class="pdf-title">{$post->post_title}</div>
			<div class="pdf-meta">
				<span>Published: {$year}</span>
				<span>Language: {$language}</span>
			</div>
		</div>

		<div class="pdf-section">
			<div class="pdf-section-title">Description</div>
			<div class="pdf-section-content">
				<div class="pdf-description">{$post->post_content}</div>
			</div>
		</div>

		<div class="pdf-section">
			<div class="pdf-section-title">Classification</div>
			<div class="pdf-section-content">
				<div class="pdf-grid">
					<div class="pdf-grid-item">
						<div class="pdf-grid-label">Category</div>
						<div class="pdf-grid-value">{$categories_str}</div>
					</div>
					<div class="pdf-grid-item">
						<div class="pdf-grid-label">Author(s)</div>
						<div class="pdf-grid-value">{$authors_str}</div>
					</div>
					<div class="pdf-grid-item">
						<div class="pdf-grid-label">Target Population</div>
						<div class="pdf-grid-value">{$population}</div>
					</div>
					<div class="pdf-grid-item">
						<div class="pdf-grid-label">Number of Items</div>
						<div class="pdf-grid-value">{$items}</div>
					</div>
				</div>
			</div>
		</div>

		<div class="pdf-section">
			<div class="pdf-section-title">Psychometric Properties</div>
			<div class="pdf-section-content">
				<div class="pdf-grid">
					<div class="pdf-grid-item">
						<div class="pdf-grid-label">Reliability</div>
						<div class="pdf-grid-value">{$reliability}</div>
					</div>
					<div class="pdf-grid-item">
						<div class="pdf-grid-label">Validity</div>
						<div class="pdf-grid-value">{$validity}</div>
					</div>
				</div>
			</div>
		</div>

		{$rating_info}

		<div class="pdf-section">
			<div class="pdf-section-title">Additional Information</div>
			<div class="pdf-section-content">
				<div class="pdf-description">
					For more information about this scale, visit: {$_SERVER['HTTP_HOST']}{$post->guid}
				</div>
			</div>
		</div>

		<div class="pdf-footer">
			Generated on {date( 'F j, Y g:i A' )} | Naboo Database
		</div>
	</div>
</body>
</html>
HTML;

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

		return <<<HTML
		<div class="pdf-section">
			<div class="pdf-section-title">Community Ratings</div>
			<div class="pdf-section-content">
				<div class="pdf-rating-box">
					<div class="pdf-rating-title">Average Rating: <span class="pdf-stars">{$stars}</span> {$results->average_rating}/5</div>
					<div class="pdf-rating-item">
						<span>★★★★★ 5 stars</span>
						<span>{$results->five_star} review(s)</span>
					</div>
					<div class="pdf-rating-item">
						<span>★★★★☆ 4 stars</span>
						<span>{$results->four_star} review(s)</span>
					</div>
					<div class="pdf-rating-item">
						<span>★★★☆☆ 3 stars</span>
						<span>{$results->three_star} review(s)</span>
					</div>
					<div class="pdf-rating-item">
						<span>★★☆☆☆ 2 stars</span>
						<span>{$results->two_star} review(s)</span>
					</div>
					<div class="pdf-rating-item">
						<span>★☆☆☆☆ 1 star</span>
						<span>{$results->one_star} review(s)</span>
					</div>
				</div>
			</div>
		</div>
HTML;
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
