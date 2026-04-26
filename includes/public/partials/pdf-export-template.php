<?php
/**
 * PDF Export Template
 *
 * @package NabooDatabase
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}
?>
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
			<div class="pdf-title"><?php echo esc_html( $post->post_title ); ?></div>
			<div class="pdf-meta">
				<span>Published: <?php echo esc_html( $year ); ?></span>
				<span>Language: <?php echo esc_html( $language ); ?></span>
			</div>
		</div>

		<div class="pdf-section">
			<div class="pdf-section-title">Description</div>
			<div class="pdf-section-content">
				<div class="pdf-description"><?php echo wp_kses_post( $post->post_content ); ?></div>
			</div>
		</div>

		<div class="pdf-section">
			<div class="pdf-section-title">Classification</div>
			<div class="pdf-section-content">
				<div class="pdf-grid">
					<div class="pdf-grid-item">
						<div class="pdf-grid-label">Category</div>
						<div class="pdf-grid-value"><?php echo esc_html( $categories_str ); ?></div>
					</div>
					<div class="pdf-grid-item">
						<div class="pdf-grid-label">Author(s)</div>
						<div class="pdf-grid-value"><?php echo esc_html( $authors_str ); ?></div>
					</div>
					<div class="pdf-grid-item">
						<div class="pdf-grid-label">Target Population</div>
						<div class="pdf-grid-value"><?php echo esc_html( $population ); ?></div>
					</div>
					<div class="pdf-grid-item">
						<div class="pdf-grid-label">Number of Items</div>
						<div class="pdf-grid-value"><?php echo esc_html( $items ); ?></div>
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
						<div class="pdf-grid-value"><?php echo esc_html( $reliability ); ?></div>
					</div>
					<div class="pdf-grid-item">
						<div class="pdf-grid-label">Validity</div>
						<div class="pdf-grid-value"><?php echo esc_html( $validity ); ?></div>
					</div>
				</div>
			</div>
		</div>

		<?php echo $rating_info; ?>

		<div class="pdf-section">
			<div class="pdf-section-title">Additional Information</div>
			<div class="pdf-section-content">
				<div class="pdf-description">
					For more information about this scale, visit: <?php echo esc_html( $_SERVER['HTTP_HOST'] . $post->guid ); ?>
				</div>
			</div>
		</div>

		<div class="pdf-footer">
			Generated on <?php echo esc_html( date( 'F j, Y g:i A' ) ); ?> | Naboo Database
		</div>
	</div>
</body>
</html>
