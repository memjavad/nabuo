<?php
/**
 * SEO Renderer - Handles UI rendering for SEO settings
 *
 * @package ArabPsychology\NabooDatabase\Admin\SEO
 */

namespace ArabPsychology\NabooDatabase\Admin\SEO;

/**
 * SEO_Renderer class
 */
class SEO_Renderer {

	/**
	 * Render the SEO settings page
	 *
	 * @param string $option_name Option name.
	 */
	public function render_admin_page( $option_name ) {
		$options = get_option( $option_name, array() );

		// Enqueue media uploader
		wp_enqueue_media();

		// Enqueue Inter font
		echo '<link rel="preconnect" href="https://fonts.googleapis.com">
			  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
			  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">';

		?>
		<div class="wrap naboo-admin-page naboodatabase-seo-wrap" style="font-family: 'Inter', sans-serif;">
			
			<?php $this->render_header(); ?>

			<form method="post" action="options.php">
				<?php settings_fields( 'naboodatabase_seo_group' ); ?>

				<div class="naboo-admin-grid">
					<?php
					$this->render_global_toggles( $options, $option_name );
					$this->render_organization_info( $options, $option_name );
					$this->render_academic_defaults( $options, $option_name );
					$this->render_sharing_fallbacks( $options, $option_name );
					$this->render_sitemap_generation( $options, $option_name );
					?>
				</div>

				<div class="naboo-save-bar">
					<?php submit_button( __( 'Save SEO Settings', 'naboodatabase' ), 'primary naboo-btn naboo-btn-primary', 'submit', false ); ?>
				</div>
			</form>
		</div>

		<?php
		$this->render_scripts();
		$this->render_styles();
	}

	/**
	 * Render the header section.
	 */
	private function render_header() {
		?>
		<!-- Header -->
		<div class="naboo-admin-header" style="margin-bottom: 32px; padding: 40px; background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border-radius: 16px; color: white; box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1); position: relative; overflow: hidden;">
			<div style="position: absolute; top: -50px; right: -50px; width: 200px; height: 200px; background: rgba(59, 130, 246, 0.1); filter: blur(80px); border-radius: 50%;"></div>
			<h1 style="color: white !important; font-size: 36px !important; margin: 0 !important; font-weight: 800; letter-spacing: -0.025em; display: flex; align-items: center; gap: 16px;">
				<span style="background: rgba(255,255,255,0.1); width: 64px; height: 64px; display: flex; align-items: center; justify-content: center; border-radius: 16px; backdrop-filter: blur(4px); border: 1px solid rgba(255,255,255,0.1);">🔍</span>
				<?php esc_html_e( 'SEO & Schema Settings', 'naboodatabase' ); ?>
			</h1>
			<p style="margin: 16px 0 0 80px !important; color: #94a3b8; font-size: 18px; max-width: 600px; line-height: 1.6;"><?php esc_html_e( 'Control global SEO configurations, Schema.org output, and social sharing fallbacks for the academic scales database.', 'naboodatabase' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Render the Global SEO Toggles section.
	 *
	 * @param array  $options     Current options.
	 * @param string $option_name Option name.
	 */
	private function render_global_toggles( $options, $option_name ) {
		?>
		<!-- General Toggles -->
		<div class="naboo-admin-card">
			<h2><?php _e( 'Global SEO Toggles', 'naboodatabase' ); ?></h2>
			<div class="naboo-settings-rows">
				<div class="naboo-setting-row">
					<div class="naboo-setting-label"><?php _e( 'JSON-LD Schema (Dataset)', 'naboodatabase' ); ?></div>
					<div class="naboo-setting-content">
						<label>
							<input type="checkbox" name="<?php echo $option_name; ?>[enable_schema]" value="1" <?php checked( isset( $options['enable_schema'] ) ? $options['enable_schema'] : 1 ); ?> />
							<?php _e( 'Enable Schema.org Dataset output on single scales', 'naboodatabase' ); ?>
						</label>
					</div>
				</div>
				<div class="naboo-setting-row">
					<div class="naboo-setting-label"><?php _e( 'OpenGraph (Facebook/LinkedIn)', 'naboodatabase' ); ?></div>
					<div class="naboo-setting-content">
						<label>
							<input type="checkbox" name="<?php echo $option_name; ?>[enable_opengraph]" value="1" <?php checked( isset( $options['enable_opengraph'] ) ? $options['enable_opengraph'] : 1 ); ?> />
							<?php _e( 'Enable OpenGraph meta tags', 'naboodatabase' ); ?>
						</label>
					</div>
				</div>
				<div class="naboo-setting-row">
					<div class="naboo-setting-label"><?php _e( 'Twitter Cards', 'naboodatabase' ); ?></div>
					<div class="naboo-setting-content">
						<label>
							<input type="checkbox" name="<?php echo $option_name; ?>[enable_twitter]" value="1" <?php checked( isset( $options['enable_twitter'] ) ? $options['enable_twitter'] : 1 ); ?> />
							<?php _e( 'Enable Twitter summary card meta tags', 'naboodatabase' ); ?>
						</label>
					</div>
				</div>
				<div class="naboo-setting-row">
					<div class="naboo-setting-label"><?php _e( 'Google Scholar (Highwire Press)', 'naboodatabase' ); ?></div>
					<div class="naboo-setting-content">
						<label>
							<input type="checkbox" name="<?php echo $option_name; ?>[enable_scholar]" value="1" <?php checked( isset( $options['enable_scholar'] ) ? $options['enable_scholar'] : 1 ); ?> />
							<?php _e( 'Enable academic citation meta tags for Google Scholar', 'naboodatabase' ); ?>
						</label>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the Organization & Publisher Info section.
	 *
	 * @param array  $options     Current options.
	 * @param string $option_name Option name.
	 */
	private function render_organization_info( $options, $option_name ) {
		?>
		<!-- Organization Info -->
		<div class="naboo-admin-card">
			<h2><?php _e( 'Organization & Publisher Info', 'naboodatabase' ); ?></h2>
			<div class="naboo-settings-rows">
				<div class="naboo-setting-row">
					<div class="naboo-setting-label"><?php _e( 'Publisher Name', 'naboodatabase' ); ?></div>
					<div class="naboo-setting-content">
						<input type="text" class="regular-text" name="<?php echo $option_name; ?>[publisher_name]" value="<?php echo esc_attr( isset( $options['publisher_name'] ) ? $options['publisher_name'] : get_bloginfo( 'name' ) ); ?>" />
					</div>
				</div>
				<div class="naboo-setting-row">
					<div class="naboo-setting-label"><?php _e( 'Publisher Logo URL', 'naboodatabase' ); ?></div>
					<div class="naboo-setting-content">
						<div style="display: flex; gap: 8px; align-items: center; margin-bottom: 8px;">
							<input type="text" class="regular-text" id="publisher_logo_url" name="<?php echo $option_name; ?>[publisher_logo_url]" value="<?php echo esc_attr( isset( $options['publisher_logo_url'] ) ? $options['publisher_logo_url'] : '' ); ?>" style="flex: 1;" />
							<button type="button" class="naboo-btn naboo-btn-secondary js-naboo-media-upload" data-target="publisher_logo_url" style="padding: 10px; min-width: 44px; flex-shrink: 0;" title="<?php _e('Upload Logo', 'naboodatabase'); ?>">
								<span>📸</span>
							</button>
						</div>
						<p class="description"><?php _e( 'Used in structured data. Minimum 112x112px.', 'naboodatabase' ); ?></p>
					</div>
				</div>
				<div class="naboo-setting-row">
					<div class="naboo-setting-label"><?php _e( 'Default Author', 'naboodatabase' ); ?></div>
					<div class="naboo-setting-content">
						<input type="text" class="regular-text" name="<?php echo $option_name; ?>[default_author]" value="<?php echo esc_attr( isset( $options['default_author'] ) ? $options['default_author'] : '' ); ?>" />
						<p class="description"><?php _e( 'Fallback if a scale has no authors set.', 'naboodatabase' ); ?></p>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the Academic Default Settings section.
	 *
	 * @param array  $options     Current options.
	 * @param string $option_name Option name.
	 */
	private function render_academic_defaults( $options, $option_name ) {
		?>
		<!-- Academic Defaults -->
		<div class="naboo-admin-card">
			<h2><?php _e( 'Academic Default Settings', 'naboodatabase' ); ?></h2>
			<div class="naboo-settings-rows">
				<div class="naboo-setting-row">
					<div class="naboo-setting-label"><?php _e( 'Default License URL', 'naboodatabase' ); ?></div>
					<div class="naboo-setting-content">
						<input type="text" class="regular-text" name="<?php echo $option_name; ?>[default_license]" value="<?php echo esc_attr( isset( $options['default_license'] ) ? $options['default_license'] : 'https://creativecommons.org/licenses/by-nc/4.0/' ); ?>" />
					</div>
				</div>
				<div class="naboo-setting-row">
					<div class="naboo-setting-label"><?php _e( 'Accessible For Free', 'naboodatabase' ); ?></div>
					<div class="naboo-setting-content">
						<label>
							<input type="checkbox" name="<?php echo $option_name; ?>[is_accessible_for_free]" value="1" <?php checked( isset( $options['is_accessible_for_free'] ) ? $options['is_accessible_for_free'] : 1 ); ?> />
							<?php _e( 'Are these datasets open-access by default?', 'naboodatabase' ); ?>
						</label>
					</div>
				</div>
				<div class="naboo-setting-row">
					<div class="naboo-setting-label"><?php _e( 'Default Language', 'naboodatabase' ); ?></div>
					<div class="naboo-setting-content">
						<input type="text" class="regular-text" name="<?php echo $option_name; ?>[default_language]" value="<?php echo esc_attr( isset( $options['default_language'] ) ? $options['default_language'] : 'ar' ); ?>" />
						<p class="description"><?php _e( 'e.g., "ar", "en". Fallback if empty.', 'naboodatabase' ); ?></p>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the Sharing Fallbacks section.
	 *
	 * @param array  $options     Current options.
	 * @param string $option_name Option name.
	 */
	private function render_sharing_fallbacks( $options, $option_name ) {
		?>
		<!-- Social Fallbacks -->
		<div class="naboo-admin-card">
			<h2><?php _e( 'Sharing Fallbacks', 'naboodatabase' ); ?></h2>
			<div class="naboo-settings-rows">
				<div class="naboo-setting-row">
					<div class="naboo-setting-label"><?php _e( 'Global Social Image', 'naboodatabase' ); ?></div>
					<div class="naboo-setting-content">
						<div style="display: flex; gap: 8px; align-items: center; margin-bottom: 8px;">
							<input type="text" class="regular-text" id="social_image_url" name="<?php echo $option_name; ?>[social_image_url]" value="<?php echo esc_attr( isset( $options['social_image_url'] ) ? $options['social_image_url'] : '' ); ?>" style="flex: 1;" />
							<button type="button" class="naboo-btn naboo-btn-secondary js-naboo-media-upload" data-target="social_image_url" style="padding: 10px; min-width: 44px; flex-shrink: 0;" title="<?php _e('Upload Image', 'naboodatabase'); ?>">
								<span>📸</span>
							</button>
						</div>
						<p class="description"><?php _e( 'Used when a scale lacks a featured image.', 'naboodatabase' ); ?></p>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the Sitemap Generation section.
	 *
	 * @param array  $options     Current options.
	 * @param string $option_name Option name.
	 */
	private function render_sitemap_generation( $options, $option_name ) {
		?>
		<!-- Sitemap Generation -->
		<div class="naboo-admin-card">
			<h2><?php _e( 'XML Sitemap generation', 'naboodatabase' ); ?></h2>
			<div class="naboo-settings-rows">
				<div class="naboo-setting-row">
					<div class="naboo-setting-label"><?php _e( 'Enable Static XML Sitemap', 'naboodatabase' ); ?></div>
					<div class="naboo-setting-content">
						<label>
							<input type="checkbox" name="<?php echo $option_name; ?>[enable_sitemap]" value="1" <?php checked( isset( $options['enable_sitemap'] ) ? $options['enable_sitemap'] : 0 ); ?> />
							<?php _e( 'Enables automatic and manual XML Sitemap generation.', 'naboodatabase' ); ?>
						</label>
					</div>
				</div>
				<div class="naboo-setting-row">
					<div class="naboo-setting-label"><?php _e( 'Generate Sitemap Builder', 'naboodatabase' ); ?></div>
					<div class="naboo-setting-content">
						<div style="display: flex; align-items: center; gap: 16px; margin-bottom: 12px;">
							<button type="button" class="naboo-btn naboo-btn-primary" id="naboo-generate-sitemap">
								<span>🚀</span> <?php _e( 'Build Now', 'naboodatabase' ); ?>
							</button>
							<span class="spinner" id="naboo-sitemap-spinner"></span>
							<span id="naboo-sitemap-msg" style="font-weight: bold; font-size: 13px;"></span>
						</div>
						<?php
						$sitemap_url = site_url( '/naboo-sitemap.xml' );
						if ( file_exists( ABSPATH . 'naboo-sitemap.xml' ) ) {
							echo '<p class="description" style="color:#059669; font-weight: 600;">' . sprintf( __( '✅ Sitemap live at <a href="%s" target="_blank" style="color:inherit; text-decoration:underline;">%s</a>', 'naboodatabase' ), esc_url( $sitemap_url ), esc_url( $sitemap_url ) ) . '</p>';
						}
						?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render javascript.
	 */
	private function render_scripts() {
		?>
		<script>
		jQuery(document).ready(function($){
			var custom_uploader;
			$('.js-naboo-media-upload').click(function(e) {
				e.preventDefault();
				var target_id = $(this).data('target');
				if (custom_uploader) {
					custom_uploader.open();
					return;
				}
				custom_uploader = wp.media.frames.file_frame = wp.media({
					title: 'Choose Image',
					button: { text: 'Choose Image' },
					multiple: false
				});
				custom_uploader.on('select', function() {
					var attachment = custom_uploader.state().get('selection').first().toJSON();
					$('#' + target_id).val(attachment.url);
				});
				custom_uploader.open();
			});

			$('#naboo-generate-sitemap').click(function(e) {
				e.preventDefault();
				var $btn = $(this);
				var $spinner = $('#naboo-sitemap-spinner');
				var $msg = $('#naboo-sitemap-msg');

				$btn.prop('disabled', true);
				$spinner.addClass('is-active');
				$msg.text('').css('color', 'inherit');

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'naboo_generate_sitemap',
						_wpnonce: '<?php echo wp_create_nonce( 'naboo_generate_sitemap' ); ?>'
					},
					success: function(response) {
						$spinner.removeClass('is-active');
						$btn.prop('disabled', false);
						if (response.success) {
							$msg.text(response.data).css('color', 'green');
							setTimeout(function() { window.location.reload(); }, 1500);
						} else {
							$msg.text(response.data || 'Build failed').css('color', 'red');
						}
					},
					error: function() {
						$spinner.removeClass('is-active');
						$btn.prop('disabled', false);
						$msg.text('<?php echo esc_js( __( 'An error occurred.', 'naboodatabase' ) ); ?>').css('color', 'red');
					}
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * Render styles.
	 */
	private function render_styles() {
		?>
		<style>
			.naboo-admin-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 24px; }
			.naboo-admin-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); overflow: hidden; height: 100%; transition: transform 0.2s ease; }
			.naboo-admin-card:hover { transform: translateY(-2px); }
			.naboo-admin-card h2 { margin: 0; padding: 24px; font-size: 16px; font-weight: 800; color: #1e293b; border-bottom: 1px solid #f1f5f9; background: #f8fafc; text-transform: uppercase; letter-spacing: 0.05em; }
			.naboo-settings-rows { padding: 24px; display: flex; flex-direction: column; gap: 24px; }
			.naboo-setting-row { display: flex; flex-direction: column; gap: 8px; }
			.naboo-setting-label { font-size: 13px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.025em; }
			.naboo-setting-content { position: relative; }
			.naboo-setting-content input[type="text"] { width: 100%; border-radius: 10px; border: 1px solid #e2e8f0; padding: 10px 14px; font-size: 14px; transition: all 0.2s; }
			.naboo-setting-content input[type="text"]:focus { border-color: #4f46e5; box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1); outline: none; }
			.naboo-setting-content label { display: flex; align-items: flex-start; gap: 12px; cursor: pointer; color: #334155; font-size: 14px; line-height: 1.5; font-weight: 500; }
			.naboo-setting-content input[type="checkbox"] { width: 18px; height: 18px; margin-top: 2px; cursor: pointer; accent-color: #4f46e5; }
			.naboo-setting-content .description { margin: 8px 0 0 0; font-size: 12px; color: #94a3b8; line-height: 1.6; }
			
			.naboo-save-bar { 
				position: sticky; bottom: 24px; z-index: 100; background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(12px); 
				padding: 24px 40px; border-radius: 20px; border: 1px solid rgba(226, 232, 240, 0.8); box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1); 
				display: flex; justify-content: flex-end; align-items: center; margin-top: 60px;
			}
			.naboo-btn { padding: 10px 24px; border-radius: 10px; font-weight: 700; font-size: 14px; cursor: pointer; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); border: 1px solid transparent; display: inline-flex; align-items: center; justify-content: center; gap: 8px; white-space: nowrap; }
			.naboo-btn-primary { background: #4f46e5; color: white; border-color: #4f46e5; box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2); }
			.naboo-btn-primary:hover { background: #4338ca; transform: translateY(-1px); box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.3); }
			.naboo-btn-secondary { background: #f8fafc; color: #475569; border-color: #e2e8f0; }
			.naboo-btn-secondary:hover { background: #f1f5f9; color: #1e293b; border-color: #cbd5e1; }

			@media (max-width: 1200px) { .naboo-admin-grid { grid-template-columns: 1fr; } }
		</style>
		<?php
	}
}
