<?php
/**
 * Settings Tab: AI Integrations
 *
 * @package ArabPsychology\NabooDatabase\Admin\Settings
 */

namespace ArabPsychology\NabooDatabase\Admin\Settings;

class Tab_AI {

	public function render( $options, $option_name ) {
		?>
		<div class="naboo-admin-grid cols-1">
			<div class="naboo-admin-card">
				<div class="naboo-admin-card-header">
					<span class="naboo-admin-card-icon purple">🤖</span>
					<h3><?php esc_html_e( 'AI Integrations', 'naboodatabase' ); ?></h3>
				</div>
				<div class="naboo-notice info" style="margin-bottom:20px;">
					<span>ℹ️</span>
					<span><?php esc_html_e( 'The AI Scale Extractor allows users to upload PDF studies. The Google Gemini API parses the PDF and autofills the scale submission form.', 'naboodatabase' ); ?></span>
				</div>
				<div class="naboo-form-row">
					<label><?php esc_html_e( 'Google Gemini API Keys', 'naboodatabase' ); ?></label>
					<p class="description" style="margin-bottom: 10px;">
						<?php esc_html_e( 'Provide up to 10 API keys. The plugin will rotate through them to avoid rate limits.', 'naboodatabase' ); ?>
						<a href="https://aistudio.google.com/app/apikey" target="_blank"><?php esc_html_e( 'Get API Key', 'naboodatabase' ); ?></a>
					</p>
					<?php
					$saved_keys = get_option( 'naboo_gemini_api_key', array() );
					if ( ! is_array( $saved_keys ) ) {
						// Backward compatibility: convert single string to array
						$saved_keys = ! empty( $saved_keys ) ? array( $saved_keys ) : array();
					}
					// Always render 10 inputs
					for ( $i = 0; $i < 10; $i++ ) {
						$val = isset( $saved_keys[ $i ] ) ? $saved_keys[ $i ] : '';
						?>
						<div style="margin-bottom: 8px; display:flex; align-items:center; gap:8px;">
							<span style="color:#666; font-size:12px; width:20px;"><?php echo esc_html( $i + 1 ); ?>.</span>
							<input type="password" name="<?php echo esc_attr( $option_name ); ?>[gemini_api_key][]" 
							       value="<?php echo esc_attr( $val ); ?>" 
							       placeholder="<?php esc_attr_e( 'Key ' . ($i + 1), 'naboodatabase' ); ?>"
							       style="width:100%; max-width: 400px;" />
							<button type="button" class="button button-secondary naboo-test-key-btn"><?php esc_html_e( 'Test', 'naboodatabase' ); ?></button>
							<span class="naboo-test-key-status" style="font-size:13px; font-weight:500;"></span>
						</div>
						<?php
					}
					?>
				</div>
				<div class="naboo-form-row">
					<label for="gemini_model"><?php esc_html_e( 'AI Model', 'naboodatabase' ); ?></label>
					<select id="gemini_model" name="<?php echo esc_attr( $option_name ); ?>[gemini_model]" style="width:100%; max-width: 400px;">
						<?php
						$current_model = get_option( 'naboo_gemini_model', 'gemini-2.5-flash' );
						$models = array(
							'gemini-1.5-flash' => 'Gemini 1.5 Flash',
							'gemini-1.5-pro'   => 'Gemini 1.5 Pro',
							'gemini-2.0-flash' => 'Gemini 2.0 Flash',
							'gemini-2.0-flash-lite-preview-02-05' => 'Gemini 2.0 Flash-Lite',
							'gemini-2.5-flash' => 'Gemini 2.5 Flash',
							'gemini-3.0-flash' => 'Gemini 3.0 Flash (Latest)',
							'gemma-3-4b-it'    => 'Gemma 3 (4B IT)',
							'gemma-3-12b-it'   => 'Gemma 3 (12B IT)',
							'gemma-3-27b-it'   => 'Gemma 3 (27B IT)',
						);
						foreach ( $models as $val => $label ) {
							printf(
								'<option value="%s" %s>%s</option>',
								esc_attr( $val ),
								selected( $current_model, $val, false ),
								esc_html( $label )
							);
						}
						?>
					</select>
					<p class="description"><?php esc_html_e( 'Select the Google AI (Gemini or Gemma) model to use for extraction.', 'naboodatabase' ); ?></p>
				</div>
			</div>
		</div>

		<script>
		jQuery(document).ready(function($) {
			$('.naboo-test-key-btn').on('click', function(e) {
				e.preventDefault();
				var $btn = $(this);
				var $container = $btn.closest('div');
				var $input = $container.find('input[type="password"]');
				var $status = $container.find('.naboo-test-key-status');
				var apiKey = $input.val().trim();

				if (!apiKey) {
					$status.html('<span style="color:#d63638;">❌ <?php esc_html_e( 'Key is empty!', 'naboodatabase' ); ?></span>');
					return;
				}

				$btn.prop('disabled', true).text('<?php esc_html_e( 'Testing...', 'naboodatabase' ); ?>');
				$status.html('<span class="spinner is-active" style="float:none; margin:0 5px;"></span>');

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'naboo_test_api_key',
						api_key: apiKey,
						nonce: '<?php echo wp_create_nonce("naboo_test_key_nonce"); ?>'
					},
					success: function(response) {
						if (response.success) {
							$status.html('<span style="color:#00a32a;">✅ ' + response.data + '</span>');
						} else {
							$status.html('<span style="color:#d63638;">❌ ' + response.data + '</span>');
						}
					},
					error: function() {
						$status.html('<span style="color:#d63638;">❌ <?php esc_html_e( 'AJAX Error', 'naboodatabase' ); ?></span>');
					},
					complete: function() {
						$btn.prop('disabled', false).text('<?php esc_html_e( 'Test', 'naboodatabase' ); ?>');
					}
				});
			});
		});
		</script>
		<?php
	}

	public function sanitize( $input ) {
		$sanitized = array();
		$api_keys = isset( $input['gemini_api_key'] ) && is_array( $input['gemini_api_key'] ) ? $input['gemini_api_key'] : array();
		$sanitized_keys = array();
		foreach ( $api_keys as $key ) {
			$clean_key = sanitize_text_field( trim( $key ) );
			if ( ! empty( $clean_key ) ) {
				$sanitized_keys[] = $clean_key;
			}
		}
		$sanitized['gemini_api_key'] = array_slice( $sanitized_keys, 0, 10 );
		$sanitized['gemini_model']   = sanitize_text_field( $input['gemini_model'] ?? 'gemini-2.5-flash' );
		update_option( 'naboo_gemini_api_key', $sanitized['gemini_api_key'] );
		update_option( 'naboo_gemini_model', $sanitized['gemini_model'] );
		$current_index = (int) get_option( 'naboo_gemini_api_key_index', 0 );
		if ( $current_index >= count( $sanitized['gemini_api_key'] ) ) {
			update_option( 'naboo_gemini_api_key_index', 0 );
		}
		return $sanitized;
	}
}
