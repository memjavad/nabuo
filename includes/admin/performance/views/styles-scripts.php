<?php
/**
 * Styles and Scripts for Performance Optimizer Admin Page
 *
 * @package ArabPsychology\NabooDatabase\Admin\Performance\Views
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}
?>
<style>
	.naboo-admin-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 24px; }
	.naboo-admin-card { background: white; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); overflow: hidden; }
	.naboo-admin-card h2 { margin: 0; padding: 20px 24px; font-size: 18px; font-weight: 800; color: #1e293b; border-bottom: 1px solid #f1f5f9; background: #f8fafc; }
	.form-table th { width: 260px !important; padding: 16px 24px !important; font-size: 13px !important; }
	.form-table td { padding: 12px 24px !important; }
	.naboo-form-row { margin-bottom: 12px; }
	.naboo-form-row label { display: block; font-weight: 600; font-size: 13px; color: #64748b; margin-bottom: 4px; }
	.naboo-form-row input { width: 100%; padding: 8px 12px; border-radius: 8px; border: 1px solid #e2e8f0; }
	.naboo-save-bar { position: sticky; bottom: 20px; z-index: 100; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(8px); padding: 20px 40px; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); display: flex; justify-content: flex-end; margin-top: 40px; }
	.naboo-btn { padding: 10px 24px; border-radius: 12px; font-weight: 700; cursor: pointer; transition: all 0.2s; border: 1px solid transparent; }
	.naboo-btn-primary { background: #4f46e5; color: white; }
	.naboo-btn-secondary { background: white; border-color: #e2e8f0; color: #475569; }
	.naboo-btn:hover { opacity: 0.9; transform: translateY(-1px); }
</style>

<script>
jQuery(document).ready(function($) {
	const nonce = '<?php echo wp_create_nonce( "naboo_performance_action" ); ?>';
	const cfNonce = '<?php echo wp_create_nonce( "naboo_cf_action" ); ?>';

	$('#naboo-clear-cache').click(function() {
		$.post(ajaxurl, { action: 'naboo_clear_asset_cache', nonce: '<?php echo wp_create_nonce( "naboo_clear_cache" ); ?>' }, function(r) { alert(r.data); });
	});
	$('#naboo-whitelist-cf-ip').click(function() {
		$.post(ajaxurl, { action: 'naboo_cf_whitelist_ip', _wpnonce: cfNonce }, function(r) { alert(r.data); });
	});
	$('#naboo-purge-cf-all').click(function() {
		$.post(ajaxurl, { action: 'naboo_purge_cloudflare_all', _wpnonce: cfNonce }, function(r) { alert(r.data); });
	});
	$('#naboo-deploy-cf-worker').click(function() {
		$.post(ajaxurl, { action: 'naboo_deploy_cloudflare_worker', _wpnonce: cfNonce }, function(r) { alert(r.data); });
	});
	$('#naboo-install-cache, #naboo-uninstall-cache').click(function() {
		const act = $(this).attr('id') === 'naboo-install-cache' ? 'naboo_install_object_cache' : 'naboo_uninstall_object_cache';
		$.post(ajaxurl, { action: act, nonce: nonce }, function(r) { alert(r.data); location.reload(); });
	});
	$('#naboo-install-page-cache, #naboo-uninstall-page-cache').click(function() {
		const act = $(this).attr('id') === 'naboo-install-page-cache' ? 'naboo_install_page_cache' : 'naboo_uninstall_page_cache';
		$.post(ajaxurl, { action: act, nonce: nonce }, function(r) { alert(r.data); location.reload(); });
	});
	$('#naboo-clear-page-cache').click(function() {
		$.post(ajaxurl, { action: 'naboo_clear_page_cache', nonce: nonce }, function(r) { alert(r.data); });
	});
});
</script>
