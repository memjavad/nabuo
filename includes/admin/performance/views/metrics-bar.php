<?php
/**
 * Metrics Bar View
 *
 * @package ArabPsychology\NabooDatabase\Admin\Performance\Views
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

global $wpdb;
$total_scales   = wp_count_posts('psych_scale')->publish ?? 0;
$indexed_scales = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}naboo_search_index") ?? 0;

$cf_status = 'Disabled';
if ( class_exists( 'ArabPsychology\NabooDatabase\Admin\Cloudflare_Integration' ) ) {
	$cf = new \ArabPsychology\NabooDatabase\Admin\Cloudflare_Integration();
	if ( $cf->is_active() ) {
		$cf_status = 'Active (' . ( $cf->get_zone_name() ?: 'Unknown Zone' ) . ')';
	}
}

$cache_status      = wp_using_ext_object_cache() ? '<span style="color:green;font-weight:bold;">Active (RAM)</span>' : '<span style="color:#d63638;font-weight:bold;">Inactive</span>';
$page_cache_status = defined('WP_CACHE') && WP_CACHE && file_exists( WP_CONTENT_DIR . '/advanced-cache.php' ) ? '<span style="color:green;font-weight:bold;">Active (Disk)</span>' : '<span style="color:#d63638;font-weight:bold;">Inactive</span>';
?>

<div class="naboo-performance-metrics-bar" style="background: white; padding: 32px; border-radius: 20px; border: 1px solid #e2e8f0; margin-bottom: 40px; display: flex; gap: 40px; align-items: center; box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);">
	<div class="metric-item" style="flex: 1;">
		<div style="display: flex; flex-direction: column; gap: 12px;">
			<div style="display: flex; justify-content: space-between; align-items: center;">
				<span style="font-size: 14px; font-weight: 700; color: #64748b; text-transform: uppercase;">RAM Cache</span>
				<?php echo wp_kses_post( $cache_status ); ?>
			</div>
			<div style="display: flex; gap: 8px;">
				<?php if ( wp_using_ext_object_cache() ) : ?>
					<button type="button" class="naboo-btn naboo-btn-secondary" id="naboo-uninstall-cache" style="color:#dc2626; font-size:12px;"><?php esc_html_e( 'Remove', 'naboodatabase' ); ?></button>
				<?php else : ?>
					<button type="button" class="naboo-btn naboo-btn-primary" id="naboo-install-cache" style="font-size:12px;"><?php esc_html_e( 'Install', 'naboodatabase' ); ?></button>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<div style="width: 1px; height: 60px; background: #e2e8f0;"></div>
	<div class="metric-item" style="flex: 1;">
		<div style="display: flex; flex-direction: column; gap: 12px;">
			<div style="display: flex; justify-content: space-between; align-items: center;">
				<span style="font-size: 14px; font-weight: 700; color: #64748b; text-transform: uppercase;">Page Cache</span>
				<?php echo wp_kses_post( $page_cache_status ); ?>
			</div>
			<div style="display: flex; gap: 8px;">
				<?php if ( defined('WP_CACHE') && WP_CACHE && file_exists( WP_CONTENT_DIR . '/advanced-cache.php' ) ) : ?>
					<button type="button" class="naboo-btn naboo-btn-secondary" id="naboo-uninstall-page-cache" style="color:#dc2626; font-size:12px;"><?php esc_html_e( 'Remove', 'naboodatabase' ); ?></button>
					<button type="button" class="naboo-btn naboo-btn-secondary" id="naboo-clear-page-cache" style="color:#047857; border-color: #10b981; font-size:12px;"><?php esc_html_e( 'Purge', 'naboodatabase' ); ?></button>
				<?php else : ?>
					<button type="button" class="naboo-btn naboo-btn-primary" id="naboo-install-page-cache" style="font-size:12px;"><?php esc_html_e( 'Install', 'naboodatabase' ); ?></button>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<div style="width: 1px; height: 60px; background: #e2e8f0;"></div>
	<div class="metric-item" style="flex: 1;">
		<div style="display: flex; flex-direction: column; gap: 8px;">
			<span style="font-size: 14px; font-weight: 700; color: #64748b; text-transform: uppercase;">Indexing</span>
			<div style="font-size: 24px; font-weight: 800; color: #1e293b;">
				<?php echo number_format( (int)$indexed_scales ); ?> / <?php echo number_format( (int)$total_scales ); ?>
			</div>
			<div style="height: 6px; background: #f1f5f9; border-radius: 3px; overflow: hidden;">
				<div style="width: <?php echo esc_attr( $total_scales > 0 ? round( ( $indexed_scales / $total_scales ) * 100 ) : 0 ); ?>%; height: 100%; background: #10b981;"></div>
			</div>
		</div>
	</div>
	<div style="width: 1px; height: 60px; background: #e2e8f0;"></div>
	<div class="metric-item" style="flex: 1;">
		<div style="display: flex; flex-direction: column; gap: 8px;">
			<span style="font-size: 14px; font-weight: 700; color: #64748b; text-transform: uppercase;">Cloudflare</span>
			<div style="display: flex; align-items: center; gap: 10px;">
				<?php if ( strpos( $cf_status, 'Active' ) !== false ) : ?>
					<span style="width: 12px; height: 12px; background: #10b981; border-radius: 50%;"></span>
					<span style="color:#10b981; font-weight: 700; font-size: 18px;"><?php esc_html_e( 'Active', 'naboodatabase' ); ?></span>
				<?php else : ?>
					<span style="width: 12px; height: 12px; background: #94a3b8; border-radius: 50%;"></span>
					<span style="color:#64748b; font-weight: 700; font-size: 18px;"><?php esc_html_e( 'Inactive', 'naboodatabase' ); ?></span>
				<?php endif; ?>
			</div>
			<span style="font-size: 12px; color: #94a3b8;"><?php echo esc_html( $cf_status ); ?></span>
		</div>
	</div>
</div>
