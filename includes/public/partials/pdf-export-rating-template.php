<?php
/**
 * PDF Export Rating Summary Template
 *
 * @package NabooDatabase
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}
?>
<div class="pdf-section">
	<div class="pdf-section-title">Community Ratings</div>
	<div class="pdf-section-content">
		<div class="pdf-rating-box">
			<div class="pdf-rating-title">Average Rating: <span class="pdf-stars"><?php echo esc_html( $stars ); ?></span> <?php echo esc_html( $results->average_rating ); ?>/5</div>
			<div class="pdf-rating-item">
				<span>★★★★★ 5 stars</span>
				<span><?php echo esc_html( $results->five_star ); ?> review(s)</span>
			</div>
			<div class="pdf-rating-item">
				<span>★★★★☆ 4 stars</span>
				<span><?php echo esc_html( $results->four_star ); ?> review(s)</span>
			</div>
			<div class="pdf-rating-item">
				<span>★★★☆☆ 3 stars</span>
				<span><?php echo esc_html( $results->three_star ); ?> review(s)</span>
			</div>
			<div class="pdf-rating-item">
				<span>★★☆☆☆ 2 stars</span>
				<span><?php echo esc_html( $results->two_star ); ?> review(s)</span>
			</div>
			<div class="pdf-rating-item">
				<span>★☆☆☆☆ 1 star</span>
				<span><?php echo esc_html( $results->one_star ); ?> review(s)</span>
			</div>
		</div>
	</div>
</div>
