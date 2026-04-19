<?php
/**
 * Settings Tab: Grokipedia Sync
 *
 * @package ArabPsychology\NabooDatabase\Admin\Settings
 */

namespace ArabPsychology\NabooDatabase\Admin\Settings;

class Tab_Grokipedia {

	public function render() {
		$history = \ArabPsychology\NabooDatabase\Core\Installer::get_sync_history( 100 );
        $sync_key = get_option( 'naboo_grokipedia_sync_key' );
        if ( empty( $sync_key ) ) {
            $sync_key = wp_generate_password( 32, false );
            update_option( 'naboo_grokipedia_sync_key', $sync_key );
        }
		?>
		<div class="naboo-admin-grid cols-1">
            <div class="naboo-admin-card span-full" style="margin-bottom: 24px;">
                <div class="naboo-admin-card-header">
                    <span class="naboo-admin-card-icon purple">🔑</span>
                    <h3><?php esc_html_e( 'Sync Authentication Key', 'naboodatabase' ); ?></h3>
                </div>
                <div class="naboo-form-section">
                    <p class="description" style="margin-bottom:12px;"><?php esc_html_e( 'Copy this key and paste it into the Chrome Extension settings to authenticate synchronization requests.', 'naboodatabase' ); ?></p>
                    <div style="display:flex;gap:10px;align-items:center;">
                        <input type="text" readonly value="<?php echo esc_attr( $sync_key ); ?>" style="width: 100%; max-width: 400px; font-family: monospace; background: #f8fafc; cursor: text;" id="naboo_grokipedia_sync_key_display" />
                        <button type="button" class="naboo-btn naboo-btn-secondary" onclick="var copyText = document.getElementById('naboo_grokipedia_sync_key_display'); copyText.select(); document.execCommand('copy'); alert('Copied to clipboard!');">📋 <?php esc_html_e( 'Copy', 'naboodatabase' ); ?></button>
                    </div>
                </div>
            </div>

			<div class="naboo-admin-card span-full">
				<div class="naboo-admin-card-header">
					<span class="naboo-admin-card-icon green">📋</span>
					<h3><?php esc_html_e( 'Submission Log', 'naboodatabase' ); ?></h3>
				</div>
				<div class="naboo-notice info" style="margin-bottom:0; border-radius:0; border-bottom:1px solid #e2e8f0;">
					<span>ℹ️</span>
					<span><?php esc_html_e( 'This log shows all scales successfully suggested to Grokipedia via the Chrome Extension.', 'naboodatabase' ); ?></span>
				</div>
				<table class="naboo-admin-table" style="margin-top:0;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Scale', 'naboodatabase' ); ?></th>
							<th><?php esc_html_e( 'Status', 'naboodatabase' ); ?></th>
							<th><?php esc_html_e( 'Synced At', 'naboodatabase' ); ?></th>
							<th><?php esc_html_e( 'Details', 'naboodatabase' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'naboodatabase' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $history ) ) : ?>
							<tr>
								<td colspan="5" style="text-align:center; padding:40px; color:#64748b;">
									<?php esc_html_e( 'No synchronization history found yet.', 'naboodatabase' ); ?>
								</td>
							</tr>
						<?php else : ?>
							<?php foreach ( $history as $row ) : ?>
								<tr>
									<td>
										<div style="font-weight:700; color:#1e293b;"><?php echo esc_html( $row->scale_title ); ?></div>
										<div style="font-size:11px; color:#94a3b8;">ID: <?php echo absint( $row->scale_id ); ?></div>
									</td>
									<td>
										<?php if ( 'success' === $row->status ) : ?>
											<span class="naboo-badge naboo-badge-green"><?php esc_html_e( 'SUCCESS', 'naboodatabase' ); ?></span>
										<?php else : ?>
											<span class="naboo-badge naboo-badge-gray"><?php echo esc_html( strtoupper( $row->status ) ); ?></span>
										<?php endif; ?>
									</td>
									<td>
										<div style="font-size:13px; color:#475569;"><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $row->synced_at ) ) ); ?></div>
									</td>
									<td style="max-width:300px;">
										<div style="font-size:12px; color:#64748b; font-family:monospace;"><?php echo esc_html( $row->details ); ?></div>
									</td>
									<td>
										<a href="<?php echo esc_url( get_edit_post_link( $row->scale_id ) ); ?>" class="naboo-btn naboo-btn-secondary" style="padding:6px 12px; font-size:12px;">
											<?php esc_html_e( 'View Scale', 'naboodatabase' ); ?>
										</a>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}
}
