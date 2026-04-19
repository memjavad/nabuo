<?php
/**
 * Settings Tab: Section System Tools
 *
 * @package ArabPsychology\NabooDatabase\Admin\Settings
 */

namespace ArabPsychology\NabooDatabase\Admin\Settings;

class Section_System {

	public function render() {
		?>
		<div class="naboo-admin-grid cols-1">
			<div class="naboo-admin-card span-full">
				<div class="naboo-admin-card-header">
					<span class="naboo-admin-card-icon blue">💾</span>
					<h3><?php esc_html_e( 'Backup & Restore', 'naboodatabase' ); ?></h3>
				</div>
				<div class="naboo-form-section" style="margin-top:0;">
					<p class="description" style="margin-bottom:12px;"><?php esc_html_e( 'Export your plugin settings as a JSON file, or import an existing configuration.', 'naboodatabase' ); ?></p>
					<div style="display:flex;gap:10px;align-items:center;">
						<button type="button" class="naboo-btn naboo-btn-primary" id="naboo-export-settings-btn">
							⬇️ <?php esc_html_e( 'Export Settings', 'naboodatabase' ); ?>
						</button>
						<button type="button" class="naboo-btn naboo-btn-secondary" id="naboo-import-settings-btn">
							⬆️ <?php esc_html_e( 'Import Settings', 'naboodatabase' ); ?>
						</button>
						<input type="file" id="naboo-import-file-input" accept=".json" style="display:none;">
						<span id="naboo-import-export-status" style="font-size:13px; font-weight:500;"></span>
					</div>
				</div>
			</div>
		</div>

		<script>
		jQuery(document).ready(function($) {
			$('#naboo-export-settings-btn').on('click', function(e) {
				e.preventDefault();
				var $btn = $(this);
				var $status = $('#naboo-import-export-status');
				$btn.prop('disabled', true);
				$status.html('<span class="spinner is-active" style="float:none;margin:0 5px;"></span>');

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'naboo_export_settings',
						nonce: '<?php echo wp_create_nonce("naboo_settings_import_export_nonce"); ?>'
					},
					success: function(response) {
						if (response.success && response.data.json) {
							var blob = new Blob([response.data.json], { type: 'application/json' });
							var url = window.URL.createObjectURL(blob);
							var a = document.createElement('a');
							a.href = url;
							a.download = 'naboodatabase-settings-' + new Date().toISOString().slice(0,10) + '.json';
							document.body.appendChild(a);
							a.click();
							window.URL.revokeObjectURL(url);
							document.body.removeChild(a);
							$status.html('<span style="color:#00a32a;">✅ <?php esc_html_e( 'Export complete.', 'naboodatabase' ); ?></span>');
							setTimeout(function() { $status.empty(); }, 3000);
						} else {
							$status.html('<span style="color:#d63638;">❌ <?php esc_html_e( 'Export failed.', 'naboodatabase' ); ?></span>');
						}
					},
					error: function() {
						$status.html('<span style="color:#d63638;">❌ <?php esc_html_e( 'Server error.', 'naboodatabase' ); ?></span>');
					},
					complete: function() {
						$btn.prop('disabled', false);
					}
				});
			});

			$('#naboo-import-settings-btn').on('click', function(e) {
				e.preventDefault();
				$('#naboo-import-file-input').click();
			});

			$('#naboo-import-file-input').on('change', function(e) {
				var file = e.target.files[0];
				if (!file) return;

				var reader = new FileReader();
				reader.onload = function(event) {
					var json_str = event.target.result;
					var $status = $('#naboo-import-export-status');
					$status.html('<span class="spinner is-active" style="float:none;margin:0 5px;"></span> <?php esc_html_e( 'Importing...', 'naboodatabase' ); ?>');
					
					$.ajax({
						url: ajaxurl,
						type: 'POST',
						dataType: 'json',
						data: {
							action: 'naboo_import_settings',
							import_json: json_str,
							nonce: '<?php echo wp_create_nonce("naboo_settings_import_export_nonce"); ?>'
						},
						success: function(response) {
							if (response.success) {
								$status.html('<span style="color:#00a32a;">✅ ' + response.data.message + '</span>');
								setTimeout(function(){ window.location.reload(); }, 1500);
							} else {
								$status.html('<span style="color:#d63638;">❌ ' + (response.data.message || '<?php esc_html_e( 'Import failed.', 'naboodatabase' ); ?>') + '</span>');
							}
						},
						error: function() {
							$status.html('<span style="color:#d63638;">❌ <?php esc_html_e( 'Server error.', 'naboodatabase' ); ?></span>');
						}
					});
				};
				reader.readAsText(file);
				$(this).val(''); // Reset input
			});
		});
		</script>
		<?php
	}
}
