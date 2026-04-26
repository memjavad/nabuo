<?php
/**
 * Cloudflare Section View
 *
 * @package ArabPsychology\NabooDatabase\Admin\Performance\Views
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

$options = get_option( $this->option_name, array() );
?>
<!-- UI: CLOUDFLARE SECTION -->
<div class="naboo-admin-card">
	<h2><?php esc_html_e( 'Cloudflare Integration', 'naboodatabase' ); ?></h2>
	<div style="padding: 24px;">
		<div class="naboo-form-row">
			<label><input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[cf_enable_integration]" value="1" <?php checked( isset( $options['cf_enable_integration'] ) ? $options['cf_enable_integration'] : 0 ); ?> /> Enable CF API Access</label>
		</div>
		<div class="naboo-form-row"><label>Account ID</label><input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[cf_account_id]" value="<?php echo esc_attr( $options['cf_account_id'] ?? '' ); ?>" /></div>
		<div class="naboo-form-row"><label>Zone ID</label><input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[cf_zone_id]" value="<?php echo esc_attr( $options['cf_zone_id'] ?? '' ); ?>" /></div>
		<div class="naboo-form-row"><label>API Token</label><input type="password" name="<?php echo esc_attr( $this->option_name ); ?>[cf_api_token]" value="<?php echo esc_attr( $options['cf_api_token'] ?? '' ); ?>" /></div>

		<div style="display: flex; gap: 8px; margin-top: 20px;">
			<button type="button" id="naboo-whitelist-cf-ip" class="naboo-btn naboo-btn-secondary" style="font-size:12px;">Whitelist IP</button>
			<button type="button" id="naboo-purge-cf-all" class="naboo-btn naboo-btn-secondary" style="font-size:12px;">Purge All</button>
			<button type="button" id="naboo-deploy-cf-worker" class="naboo-btn" style="background:#f56e28; color:white; font-size:12px;">Deploy Worker</button>
		</div>
	</div>
</div>
