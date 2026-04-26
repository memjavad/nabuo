<?php
/**
 * Admin Page View for Performance Optimizer
 *
 * @package ArabPsychology\NabooDatabase\Admin\Performance\Views
 */

// If accessed directly, terminate.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<div class="wrap naboo-admin-page naboo-performance-wrap" style="font-family: 'Inter', sans-serif;">

	<div class="naboo-admin-header" style="margin-bottom: 32px; padding: 40px; background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border-radius: 16px; color: white; box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1); position: relative; overflow: hidden;">
		<div style="position: absolute; top: -50px; right: -50px; width: 200px; height: 200px; background: rgba(245, 158, 11, 0.1); filter: blur(80px); border-radius: 50%;"></div>
		<h1 style="color: white !important; font-size: 36px !important; margin: 0 !important; font-weight: 800; letter-spacing: -0.025em; display: flex; align-items: center; gap: 20px;">
			<span style="background: rgba(255,255,255,0.1); width: 64px; height: 64px; display: flex; align-items: center; justify-content: center; border-radius: 16px; backdrop-filter: blur(4px); border: 1px solid rgba(255,255,255,0.1);">⚡</span>
			<?php esc_html_e( 'Performance Optimizer', 'naboodatabase' ); ?>
		</h1>
		<p style="margin: 16px 0 0 84px !important; color: #94a3b8; font-size: 18px; max-width: 600px; line-height: 1.6;"><?php esc_html_e( 'Fine-tune your database performance, optimize asset delivery, and monitor system bottlenecks.', 'naboodatabase' ); ?></p>
	</div>

	<?php require __DIR__ . '/metrics-bar.php'; ?>

	<form method="post" action="options.php">
		<?php settings_fields( 'naboodatabase_performance_group' ); ?>

		<div class="naboo-admin-grid">
			<?php
			require __DIR__ . '/section-bloat.php';
			require __DIR__ . '/section-assets.php';
			require __DIR__ . '/section-cloudflare.php';
			require __DIR__ . '/section-caching.php';
			?>
		</div>

		<div class="naboo-save-bar">
			<button type="submit" class="naboo-btn naboo-btn-primary"><?php esc_html_e( 'Save Optimization Settings', 'naboodatabase' ); ?></button>
		</div>
	</form>
</div>

<?php require __DIR__ . '/styles-scripts.php'; ?>
