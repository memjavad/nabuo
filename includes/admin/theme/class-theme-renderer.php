<?php
/**
 * Theme Renderer - Handles UI rendering for the theme customizer
 *
 * @package ArabPsychology\NabooDatabase\Admin\Theme
 */

namespace ArabPsychology\NabooDatabase\Admin\Theme;

/**
 * Theme_Renderer class
 */
class Theme_Renderer {

	/**
	 * Option name
	 * @var string
	 */
	private $option_name = 'naboodatabase_customizer_options';

	/**
	 * Render the main tab content based on active tab
	 *
	 * @param string $tab Active tab slug.
	 * @param array  $options Current theme options.
	 */
	public function render_tab_content( $tab, $options ) {
		switch ( $tab ) {
			case 'general':
				$this->render_general_tab( $options );
				break;
			case 'header':
				$this->render_header_tab( $options );
				break;
			case 'icons':
				$this->render_icons_tab( $options );
				break;
			case 'colors':
				$this->render_colors_tab( $options );
				break;
			case 'typography':
				$this->render_typography_tab( $options );
				break;
			case 'buttons':
				$this->render_buttons_tab( $options );
				break;
			case 'forms':
				$this->render_forms_tab( $options );
				break;
			case 'cards':
				$this->render_cards_tab( $options );
				break;
			case 'layout':
				$this->render_layout_tab( $options );
				break;
			case 'footer':
				$this->render_footer_tab( $options );
				break;
			case 'advanced':
				$this->render_advanced_tab( $options );
				break;
		}
	}

	public function render_general_tab( $options ) {
		?>
		<div class="customizer-section">
			<h2><?php _e( 'General Settings', 'naboodatabase' ); ?></h2>
			
			<div class="customize-control">
				<label><?php _e( 'Enable Naboo Theme Override', 'naboodatabase' ); ?></label>
				<p class="description"><?php _e( 'When enabled, the Naboo Database theme completely replaces the WordPress theme on frontend', 'naboodatabase' ); ?></p>
				<label class="checkbox-label">
					<input type="checkbox" name="<?php echo $this->option_name; ?>[enable_theme]" value="1" <?php checked( isset( $options['enable_theme'] ) ? $options['enable_theme'] : 1 ); ?> />
					<?php _e( 'Enable Theme Override', 'naboodatabase' ); ?>
				</label>
			</div>

			<div class="customize-control">
				<label><?php _e( 'Layout Mode', 'naboodatabase' ); ?></label>
				<select name="<?php echo $this->option_name; ?>[layout_mode]">
					<option value="wide" <?php selected( $options['layout_mode'] ?? 'wide', 'wide' ); ?>><?php _e( 'Wide (Full Width)', 'naboodatabase' ); ?></option>
					<option value="boxed" <?php selected( $options['layout_mode'] ?? 'wide', 'boxed' ); ?>><?php _e( 'Boxed (Contained)', 'naboodatabase' ); ?></option>
					<option value="fullscreen" <?php selected( $options['layout_mode'] ?? 'wide', 'fullscreen' ); ?>><?php _e( 'Fullscreen', 'naboodatabase' ); ?></option>
				</select>
			</div>

			<div class="customize-control">
				<label><?php _e( 'Container Max Width (px)', 'naboodatabase' ); ?></label>
				<input type="number" name="<?php echo $this->option_name; ?>[container_width]" min="800" max="2000" value="<?php echo isset( $options['container_width'] ) ? intval( $options['container_width'] ) : 1200; ?>" />
			</div>

			<div class="customize-control">
				<label class="checkbox-label">
					<input type="checkbox" name="<?php echo $this->option_name; ?>[hide_wp_admin_bar]" value="1" <?php checked( isset( $options['hide_wp_admin_bar'] ) ? $options['hide_wp_admin_bar'] : 0 ); ?> />
					<?php _e( 'Hide WordPress Admin Bar on Frontend', 'naboodatabase' ); ?>
				</label>
			</div>

			<div class="customize-control">
				<label><?php _e( 'Color Scheme', 'naboodatabase' ); ?></label>
				<select name="<?php echo $this->option_name; ?>[color_scheme]">
					<option value="light" <?php selected( $options['color_scheme'] ?? 'light', 'light' ); ?>><?php _e( 'Light', 'naboodatabase' ); ?></option>
					<option value="dark" <?php selected( $options['color_scheme'] ?? 'light', 'dark' ); ?>><?php _e( 'Dark', 'naboodatabase' ); ?></option>
				</select>
			</div>

			<h2><?php _e( 'Frontend Features', 'naboodatabase' ); ?></h2>

			<div class="customize-control">
				<label class="checkbox-label">
					<input type="checkbox" name="<?php echo $this->option_name; ?>[back_to_top]" value="1" <?php checked( isset( $options['back_to_top'] ) ? $options['back_to_top'] : 1 ); ?> />
					<?php _e( 'Show Back to Top Button', 'naboodatabase' ); ?>
				</label>
			</div>

			<div class="customize-control">
				<label class="checkbox-label">
					<input type="checkbox" name="<?php echo $this->option_name; ?>[progress_bar]" value="1" <?php checked( isset( $options['progress_bar'] ) ? $options['progress_bar'] : 0 ); ?> />
					<?php _e( 'Show Reading Progress Bar', 'naboodatabase' ); ?>
				</label>
			</div>

			<div class="customize-control">
				<label class="checkbox-label">
					<input type="checkbox" name="<?php echo $this->option_name; ?>[breadcrumbs]" value="1" <?php checked( isset( $options['breadcrumbs'] ) ? $options['breadcrumbs'] : 0 ); ?> />
					<?php _e( 'Show Breadcrumbs on Single Pages', 'naboodatabase' ); ?>
				</label>
			</div>
		</div>
		<?php
	}

	public function render_header_tab( $options ) {
		?>
		<div class="customizer-section">
			<h2><?php _e( 'Header Settings', 'naboodatabase' ); ?></h2>
			
			<div class="customize-control">
				<label><?php _e( 'Custom Logo', 'naboodatabase' ); ?></label>
				<input type="text" name="<?php echo $this->option_name; ?>[logo_url]" value="<?php echo isset( $options['logo_url'] ) ? esc_attr( $options['logo_url'] ) : ''; ?>" class="logo-input" />
				<button type="button" class="button naboo-upload-btn"><?php _e( 'Upload Image', 'naboodatabase' ); ?></button>
				<?php if ( isset( $options['logo_url'] ) && $options['logo_url'] ): ?>
					<img src="<?php echo esc_url( $options['logo_url'] ); ?>" style="max-width: 100px; margin-top: 10px;" />
				<?php endif; ?>
			</div>

			<div class="customize-control">
				<label><?php _e( 'Logo Width (px)', 'naboodatabase' ); ?></label>
				<input type="number" name="<?php echo $this->option_name; ?>[logo_width]" min="20" max="200" value="<?php echo isset( $options['logo_width'] ) ? intval( $options['logo_width'] ) : 50; ?>" />
			</div>

			<div class="customize-control">
				<label><?php _e( 'Custom Header Text', 'naboodatabase' ); ?></label>
				<p class="description"><?php _e( 'Overrides the site name in the main header.', 'naboodatabase' ); ?></p>
				<input type="text" name="<?php echo $this->option_name; ?>[custom_header_text]" value="<?php echo isset( $options['custom_header_text'] ) ? esc_attr( $options['custom_header_text'] ) : ''; ?>" placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" />
			</div>

			<div class="customize-control">
				<label class="checkbox-label">
					<input type="checkbox" name="<?php echo $this->option_name; ?>[hide_mobile_menu]" value="1" <?php checked( isset( $options['hide_mobile_menu'] ) ? $options['hide_mobile_menu'] : 0 ); ?> />
					<?php _e( 'Hide Mobile Menu (3 lines)', 'naboodatabase' ); ?>
				</label>
			</div>

			<div class="customize-control">
				<label><?php _e( 'Header Background Color', 'naboodatabase' ); ?></label>
				<input type="text" name="<?php echo $this->option_name; ?>[header_bg_color]" class="naboo-color-field" value="<?php echo isset( $options['header_bg_color'] ) ? esc_attr( $options['header_bg_color'] ) : '#ffffff'; ?>" />
			</div>

			<div class="customize-control">
				<label><?php _e( 'Header Text Color', 'naboodatabase' ); ?></label>
				<input type="text" name="<?php echo $this->option_name; ?>[header_text_color]" class="naboo-color-field" value="<?php echo isset( $options['header_text_color'] ) ? esc_attr( $options['header_text_color'] ) : '#2c3e50'; ?>" />
			</div>

			<div class="customize-control">
				<label class="checkbox-label">
					<input type="checkbox" name="<?php echo $this->option_name; ?>[sticky_header]" value="1" <?php checked( isset( $options['sticky_header'] ) ? $options['sticky_header'] : 1 ); ?> />
					<?php _e( 'Sticky Header (stays visible while scrolling)', 'naboodatabase' ); ?>
				</label>
			</div>

			<div class="customize-control">
				<label class="checkbox-label">
					<input type="checkbox" name="<?php echo $this->option_name; ?>[show_tagline]" value="1" <?php checked( isset( $options['show_tagline'] ) ? $options['show_tagline'] : 0 ); ?> />
					<?php _e( 'Show Site Tagline Below Logo', 'naboodatabase' ); ?>
				</label>
			</div>

			<h2><?php _e( 'Header & Navigation Style', 'naboodatabase' ); ?></h2>

			<div class="customize-control">
				<label><?php _e( 'Header Style', 'naboodatabase' ); ?></label>
				<select name="<?php echo $this->option_name; ?>[header_style]">
					<option value="solid" <?php selected( $options['header_style'] ?? 'solid', 'solid' ); ?>><?php _e( 'Solid (Default)', 'naboodatabase' ); ?></option>
					<option value="gradient" <?php selected( $options['header_style'] ?? 'solid', 'gradient' ); ?>><?php _e( 'Gradient (Primary)', 'naboodatabase' ); ?></option>
					<option value="glass" <?php selected( $options['header_style'] ?? 'solid', 'glass' ); ?>><?php _e( 'Glass (Blur Effect)', 'naboodatabase' ); ?></option>
					<option value="transparent" <?php selected( $options['header_style'] ?? 'solid', 'transparent' ); ?>><?php _e( 'Transparent', 'naboodatabase' ); ?></option>
				</select>
			</div>

			<div class="customize-control">
				<label><?php _e( 'Navigation Style', 'naboodatabase' ); ?></label>
				<select name="<?php echo $this->option_name; ?>[nav_style]">
					<option value="underline" <?php selected( $options['nav_style'] ?? 'underline', 'underline' ); ?>><?php _e( 'Underline', 'naboodatabase' ); ?></option>
					<option value="pill" <?php selected( $options['nav_style'] ?? 'underline', 'pill' ); ?>><?php _e( 'Pill / Highlight Background', 'naboodatabase' ); ?></option>
					<option value="highlight" <?php selected( $options['nav_style'] ?? 'underline', 'highlight' ); ?>><?php _e( 'Solid Highlight', 'naboodatabase' ); ?></option>
				</select>
			</div>
		</div>
		<?php
	}

	public function render_icons_tab( $options ) {
		?>
		<div class="customizer-section">
			<h2><?php _e( 'Global Icons', 'naboodatabase' ); ?></h2>
			
			<div class="customize-control">
				<label class="checkbox-label">
					<input type="checkbox" name="<?php echo $this->option_name; ?>[hide_main_search_logo]" value="1" <?php checked( isset( $options['hide_main_search_logo'] ) ? $options['hide_main_search_logo'] : 0 ); ?> />
					<?php _e( 'Hide Main Search Logo', 'naboodatabase' ); ?>
				</label>
			</div>

			<div class="customize-control">
				<label><?php _e( 'Main Search Logo Width (px)', 'naboodatabase' ); ?></label>
				<p class="description"><?php _e( 'Adjust the size of the logo. Default is 68px.', 'naboodatabase' ); ?></p>
				<input type="number" name="<?php echo $this->option_name; ?>[main_search_logo_width]" min="20" max="400" value="<?php echo isset( $options['main_search_logo_width'] ) ? intval( $options['main_search_logo_width'] ) : 68; ?>" />
			</div>

			<div class="customize-control">
				<label><?php _e( 'Search Page Title', 'naboodatabase' ); ?></label>
				<p class="description"><?php _e( 'Text displayed under the main search logo.', 'naboodatabase' ); ?></p>
				<input type="text" name="<?php echo $this->option_name; ?>[main_search_title]" value="<?php echo isset( $options['main_search_title'] ) ? esc_attr( $options['main_search_title'] ) : ''; ?>" placeholder="<?php esc_attr_e( 'e.g. Naboo Psychological Scales Database', 'naboodatabase' ); ?>" />
			</div>
			
			<div class="customize-control">
				<label><?php _e( 'Main Search Logo', 'naboodatabase' ); ?></label>
				<p class="description"><?php _e( 'Appears center-stage on the main search form. Replaces the SVG psychology logo.', 'naboodatabase' ); ?></p>
				<input type="text" name="<?php echo $this->option_name; ?>[main_search_logo_url]" value="<?php echo isset( $options['main_search_logo_url'] ) ? esc_attr( $options['main_search_logo_url'] ) : ''; ?>" class="logo-input" />
				<button type="button" class="button naboo-upload-btn"><?php _e( 'Upload Image', 'naboodatabase' ); ?></button>
				<?php if ( ! empty( $options['main_search_logo_url'] ) ): ?>
					<br><img src="<?php echo esc_url( $options['main_search_logo_url'] ); ?>" style="max-width: 150px; margin-top: 10px; background:#f0f0f0;" />
				<?php endif; ?>
			</div>

			<div class="customize-control">
				<label><?php _e( 'Favicon (Browser Tab Icon)', 'naboodatabase' ); ?></label>
				<p class="description"><?php _e( 'Recommended size: 512x512 pixels (PNG or ICO).', 'naboodatabase' ); ?></p>
				<input type="text" name="<?php echo $this->option_name; ?>[favicon_url]" value="<?php echo isset( $options['favicon_url'] ) ? esc_attr( $options['favicon_url'] ) : ''; ?>" class="logo-input" />
				<button type="button" class="button naboo-upload-btn"><?php _e( 'Upload Favicon', 'naboodatabase' ); ?></button>
				<?php if ( ! empty( $options['favicon_url'] ) ): ?>
					<br><img src="<?php echo esc_url( $options['favicon_url'] ); ?>" style="max-width: 64px; margin-top: 10px; background:#f0f0f0;" />
				<?php endif; ?>
			</div>

			<div class="customize-control">
				<label><?php _e( 'Mobile App Icon (Apple Touch Icon)', 'naboodatabase' ); ?></label>
				<p class="description"><?php _e( 'Used when a user adds your site to their iOS or Android home screen. Recommended: 180x180 pixels PNG.', 'naboodatabase' ); ?></p>
				<input type="text" name="<?php echo $this->option_name; ?>[mobile_icon_url]" value="<?php echo isset( $options['mobile_icon_url'] ) ? esc_attr( $options['mobile_icon_url'] ) : ''; ?>" class="logo-input" />
				<button type="button" class="button naboo-upload-btn"><?php _e( 'Upload Mobile Icon', 'naboodatabase' ); ?></button>
				<?php if ( ! empty( $options['mobile_icon_url'] ) ): ?>
					<br><img src="<?php echo esc_url( $options['mobile_icon_url'] ); ?>" style="max-width: 90px; margin-top: 10px; background:#f0f0f0;" />
				<?php endif; ?>
			</div>

		</div>
		<?php
	}

	public function render_colors_tab( $options ) {
		?>
		<div class="customizer-section">
			<h2><?php _e( '🎨 Primary Colors', 'naboodatabase' ); ?></h2>
			
			<div class="color-grid">
				<div class="customize-control">
					<label><?php _e( 'Primary Color (Navy)', 'naboodatabase' ); ?></label>
					<input type="text" name="<?php echo $this->option_name; ?>[primary_color]" class="naboo-color-field" value="<?php echo isset( $options['primary_color'] ) ? esc_attr( $options['primary_color'] ) : '#1a3a52'; ?>" />
					<p class="description"><?php _e( 'Used for headings, primary text', 'naboodatabase' ); ?></p>
				</div>

				<div class="customize-control">
					<label><?php _e( 'Accent Color (Teal)', 'naboodatabase' ); ?></label>
					<input type="text" name="<?php echo $this->option_name; ?>[accent_color]" class="naboo-color-field" value="<?php echo isset( $options['accent_color'] ) ? esc_attr( $options['accent_color'] ) : '#00796b'; ?>" />
					<p class="description"><?php _e( 'Used for links, accents, hover states', 'naboodatabase' ); ?></p>
				</div>

				<div class="customize-control">
					<label><?php _e( 'Light Accent Color', 'naboodatabase' ); ?></label>
					<input type="text" name="<?php echo $this->option_name; ?>[accent_light_color]" class="naboo-color-field" value="<?php echo isset( $options['accent_light_color'] ) ? esc_attr( $options['accent_light_color'] ) : '#4db8a8'; ?>" />
					<p class="description"><?php _e( 'Lighter variant of accent color', 'naboodatabase' ); ?></p>
				</div>
			</div>

			<h2><?php _e( '📝 Text Colors', 'naboodatabase' ); ?></h2>
			
			<div class="color-grid">
				<div class="customize-control">
					<label><?php _e( 'Dark Text Color', 'naboodatabase' ); ?></label>
					<input type="text" name="<?php echo $this->option_name; ?>[text_dark_color]" class="naboo-color-field" value="<?php echo isset( $options['text_dark_color'] ) ? esc_attr( $options['text_dark_color'] ) : '#2c3e50'; ?>" />
				</div>

				<div class="customize-control">
					<label><?php _e( 'Light Text Color', 'naboodatabase' ); ?></label>
					<input type="text" name="<?php echo $this->option_name; ?>[text_light_color]" class="naboo-color-field" value="<?php echo isset( $options['text_light_color'] ) ? esc_attr( $options['text_light_color'] ) : '#555'; ?>" />
				</div>

				<div class="customize-control">
					<label><?php _e( 'Background Color', 'naboodatabase' ); ?></label>
					<input type="text" name="<?php echo $this->option_name; ?>[bg_color]" class="naboo-color-field" value="<?php echo isset( $options['bg_color'] ) ? esc_attr( $options['bg_color'] ) : '#f5f5f5'; ?>" />
				</div>
			</div>

			<h2><?php _e( '✨ Effects & Borders', 'naboodatabase' ); ?></h2>
			
			<div class="color-grid">
				<div class="customize-control">
					<label><?php _e( 'Border Color', 'naboodatabase' ); ?></label>
					<input type="text" name="<?php echo $this->option_name; ?>[border_color]" class="naboo-color-field" value="<?php echo isset( $options['border_color'] ) ? esc_attr( $options['border_color'] ) : '#d9d9d9'; ?>" />
				</div>

				<div class="customize-control">
					<label><?php _e( 'Shadow Color', 'naboodatabase' ); ?></label>
					<input type="text" name="<?php echo $this->option_name; ?>[shadow_color]" class="naboo-color-field" value="<?php echo isset( $options['shadow_color'] ) ? esc_attr( $options['shadow_color'] ) : '#000000'; ?>" />
				</div>

				<div class="customize-control">
					<label><?php _e( 'Shadow Opacity (%)', 'naboodatabase' ); ?></label>
					<input type="number" name="<?php echo $this->option_name; ?>[shadow_opacity]" min="1" max="30" value="<?php echo isset( $options['shadow_opacity'] ) ? intval( $options['shadow_opacity'] ) : 8; ?>" />
				</div>
			</div>
		</div>
		<?php
	}

	public function render_typography_tab( $options ) {
		$fonts = array(
			'system' => 'System UI (Default)',
			'"Segoe UI", Tahoma, Geneva' => 'Segoe UI',
			'"Roboto", sans-serif' => 'Roboto',
			'"Inter", sans-serif' => 'Inter',
			'"Open Sans", sans-serif' => 'Open Sans',
			'"Lato", sans-serif' => 'Lato'
		);

		$heading_fonts = array(
			'"Georgia", serif' => 'Georgia (Default)',
			'"Times New Roman", serif' => 'Times New Roman',
			'"Playfair Display", serif' => 'Playfair Display',
			'"Merriweather", serif' => 'Merriweather',
			'"Montserrat", sans-serif' => 'Montserrat',
			'"Poppins", sans-serif' => 'Poppins'
		);
		?>
		<div class="customizer-section">
			<h2><?php _e( 'Typography Settings', 'naboodatabase' ); ?></h2>
			
			<div class="customize-control">
				<label><?php _e( 'Body Font', 'naboodatabase' ); ?></label>
				<select name="<?php echo $this->option_name; ?>[body_font]">
					<?php foreach ( $fonts as $value => $label ): ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $options['body_font'] ?? 'system', $value ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="customize-control">
				<label><?php _e( 'Heading Font', 'naboodatabase' ); ?></label>
				<select name="<?php echo $this->option_name; ?>[heading_font]">
					<?php foreach ( $heading_fonts as $value => $label ): ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $options['heading_font'] ?? '"Georgia", serif', $value ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="customize-control">
				<label><?php _e( 'Base Font Size (px)', 'naboodatabase' ); ?></label>
				<input type="number" name="<?php echo $this->option_name; ?>[base_font_size]" min="12" max="20" value="<?php echo isset( $options['base_font_size'] ) ? intval( $options['base_font_size'] ) : 16; ?>" />
			</div>

			<div class="customize-control">
				<label><?php _e( 'Heading Size Multiplier', 'naboodatabase' ); ?></label>
				<input type="number" name="<?php echo $this->option_name; ?>[heading_size_mult]" min="0.5" max="2" step="0.1" value="<?php echo isset( $options['heading_size_mult'] ) ? floatval( $options['heading_size_mult'] ) : 1; ?>" />
				<p class="description"><?php _e( '1.0 = default, 1.5 = 50% larger', 'naboodatabase' ); ?></p>
			</div>

			<div class="customize-control">
				<label><?php _e( 'Body Line Height', 'naboodatabase' ); ?></label>
				<input type="number" name="<?php echo $this->option_name; ?>[line_height]" min="1" max="2.5" step="0.1" value="<?php echo isset( $options['line_height'] ) ? floatval( $options['line_height'] ) : 1.7; ?>" />
				<p class="description"><?php _e( '1.5 - 1.8 recommended for readability', 'naboodatabase' ); ?></p>
			</div>
		</div>
		<?php
	}

	public function render_buttons_tab( $options ) {
		?>
		<div class="customizer-section">
			<h2><?php _e( 'Button Styling', 'naboodatabase' ); ?></h2>
			
			<div class="customize-control">
				<label><?php _e( 'Primary Button Background Color', 'naboodatabase' ); ?></label>
				<input type="text" name="<?php echo $this->option_name; ?>[button_primary_color]" class="naboo-color-field" value="<?php echo isset( $options['button_primary_color'] ) ? esc_attr( $options['button_primary_color'] ) : '#00796b'; ?>" />
			</div>

			<div class="customize-control">
				<label><?php _e( 'Button Text Color', 'naboodatabase' ); ?></label>
				<input type="text" name="<?php echo $this->option_name; ?>[button_text_color]" class="naboo-color-field" value="<?php echo isset( $options['button_text_color'] ) ? esc_attr( $options['button_text_color'] ) : '#ffffff'; ?>" />
			</div>

			<div class="customize-control">
				<label><?php _e( 'Button Border Radius (px)', 'naboodatabase' ); ?></label>
				<input type="number" name="<?php echo $this->option_name; ?>[button_radius]" min="0" max="50" value="<?php echo isset( $options['button_radius'] ) ? intval( $options['button_radius'] ) : 6; ?>" />
			</div>

			<div class="customize-control">
				<label><?php _e( 'Button Horizontal Padding (px)', 'naboodatabase' ); ?></label>
				<input type="number" name="<?php echo $this->option_name; ?>[button_padding_h]" min="10" max="50" value="<?php echo isset( $options['button_padding_h'] ) ? intval( $options['button_padding_h'] ) : 24; ?>" />
			</div>

			<div class="customize-control">
				<label><?php _e( 'Button Vertical Padding (px)', 'naboodatabase' ); ?></label>
				<input type="number" name="<?php echo $this->option_name; ?>[button_padding_v]" min="8" max="30" value="<?php echo isset( $options['button_padding_v'] ) ? intval( $options['button_padding_v'] ) : 12; ?>" />
			</div>
		</div>
		<?php
	}

	public function render_forms_tab( $options ) {
		?>
		<div class="customizer-section">
			<h2><?php _e( 'Form Styling', 'naboodatabase' ); ?></h2>
			
			<div class="customize-control">
				<label><?php _e( 'Input Background Color', 'naboodatabase' ); ?></label>
				<input type="text" name="<?php echo $this->option_name; ?>[input_bg_color]" class="naboo-color-field" value="<?php echo isset( $options['input_bg_color'] ) ? esc_attr( $options['input_bg_color'] ) : '#ffffff'; ?>" />
			</div>

			<div class="customize-control">
				<label><?php _e( 'Input Border Color', 'naboodatabase' ); ?></label>
				<input type="text" name="<?php echo $this->option_name; ?>[input_border_color]" class="naboo-color-field" value="<?php echo isset( $options['input_border_color'] ) ? esc_attr( $options['input_border_color'] ) : '#d9d9d9'; ?>" />
			</div>

			<div class="customize-control">
				<label><?php _e( 'Input Focus Color', 'naboodatabase' ); ?></label>
				<input type="text" name="<?php echo $this->option_name; ?>[input_focus_color]" class="naboo-color-field" value="<?php echo isset( $options['input_focus_color'] ) ? esc_attr( $options['input_focus_color'] ) : '#00796b'; ?>" />
			</div>

			<div class="customize-control">
				<label><?php _e( 'Input Border Radius (px)', 'naboodatabase' ); ?></label>
				<input type="number" name="<?php echo $this->option_name; ?>[input_radius]" min="0" max="30" value="<?php echo isset( $options['input_radius'] ) ? intval( $options['input_radius'] ) : 6; ?>" />
			</div>

			<div class="customize-control">
				<label><?php _e( 'Form Label Font Weight', 'naboodatabase' ); ?></label>
				<select name="<?php echo $this->option_name; ?>[form_label_weight]">
					<option value="400" <?php selected( $options['form_label_weight'] ?? '600', '400' ); ?>>Normal</option>
					<option value="500" <?php selected( $options['form_label_weight'] ?? '600', '500' ); ?>>Medium</option>
					<option value="600" <?php selected( $options['form_label_weight'] ?? '600', '600' ); ?>>Bold</option>
					<option value="700" <?php selected( $options['form_label_weight'] ?? '600', '700' ); ?>>Very Bold</option>
				</select>
			</div>
		</div>
		<?php
	}

	public function render_cards_tab( $options ) {
		?>
		<div class="customizer-section">
			<h2><?php _e( 'Card Styling', 'naboodatabase' ); ?></h2>

			<div class="customize-control">
				<label><?php _e( 'Card Style', 'naboodatabase' ); ?></label>
				<select name="<?php echo $this->option_name; ?>[card_style]">
					<option value="classic" <?php selected( $options['card_style'] ?? 'classic', 'classic' ); ?>><?php _e( 'Classic (Border)', 'naboodatabase' ); ?></option>
					<option value="modern" <?php selected( $options['card_style'] ?? 'classic', 'modern' ); ?>><?php _e( 'Modern (Left Accent)', 'naboodatabase' ); ?></option>
					<option value="minimal" <?php selected( $options['card_style'] ?? 'classic', 'minimal' ); ?>><?php _e( 'Minimal (Flat)', 'naboodatabase' ); ?></option>
					<option value="glass" <?php selected( $options['card_style'] ?? 'classic', 'glass' ); ?>><?php _e( 'Glass (Blur Effect)', 'naboodatabase' ); ?></option>
				</select>
			</div>

			<div class="customize-control">
				<label><?php _e( 'Card Image Height (px)', 'naboodatabase' ); ?></label>
				<input type="number" name="<?php echo $this->option_name; ?>[card_image_height]" min="100" max="400" value="<?php echo isset( $options['card_image_height'] ) ? intval( $options['card_image_height'] ) : 220; ?>" />
			</div>

			<div class="customize-control">
				<label><?php _e( 'Card Background Color', 'naboodatabase' ); ?></label>
				<input type="text" name="<?php echo $this->option_name; ?>[card_bg_color]" class="naboo-color-field" value="<?php echo isset( $options['card_bg_color'] ) ? esc_attr( $options['card_bg_color'] ) : '#ffffff'; ?>" />
			</div>

			<div class="customize-control">
				<label><?php _e( 'Card Left Border Width (px)', 'naboodatabase' ); ?></label>
				<input type="number" name="<?php echo $this->option_name; ?>[card_border_width]" min="0" max="10" value="<?php echo isset( $options['card_border_width'] ) ? intval( $options['card_border_width'] ) : 5; ?>" />
			</div>

			<div class="customize-control">
				<label><?php _e( 'Card Border Radius (px)', 'naboodatabase' ); ?></label>
				<input type="number" name="<?php echo $this->option_name; ?>[card_radius]" min="0" max="30" value="<?php echo isset( $options['card_radius'] ) ? intval( $options['card_radius'] ) : 10; ?>" />
			</div>

			<div class="customize-control">
				<label><?php _e( 'Card Padding (px)', 'naboodatabase' ); ?></label>
				<input type="number" name="<?php echo $this->option_name; ?>[card_padding]" min="10" max="60" value="<?php echo isset( $options['card_padding'] ) ? intval( $options['card_padding'] ) : 32; ?>" />
			</div>

			<div class="customize-control">
				<label><?php _e( 'Card Shadow Opacity (%)', 'naboodatabase' ); ?></label>
				<input type="number" name="<?php echo $this->option_name; ?>[card_shadow_opacity]" min="0" max="30" value="<?php echo isset( $options['card_shadow_opacity'] ) ? intval( $options['card_shadow_opacity'] ) : 8; ?>" />
			</div>

			<div class="customize-control">
				<label><?php _e( 'Card Hover Lift (px)', 'naboodatabase' ); ?></label>
				<input type="number" name="<?php echo $this->option_name; ?>[card_hover_lift]" min="0" max="20" value="<?php echo isset( $options['card_hover_lift'] ) ? intval( $options['card_hover_lift'] ) : 6; ?>" />
			</div>
		</div>
		<?php
	}

	public function render_layout_tab( $options ) {
		?>
		<div class="customizer-section">
			<h2><?php _e( 'Spacing', 'naboodatabase' ); ?></h2>
			
			<div class="customize-control">
				<label><?php _e( 'Base Spacing Unit (px)', 'naboodatabase' ); ?></label>
				<input type="number" name="<?php echo $this->option_name; ?>[spacing_unit]" min="4" max="16" value="<?php echo isset( $options['spacing_unit'] ) ? intval( $options['spacing_unit'] ) : 8; ?>" />
				<p class="description"><?php _e( 'Base unit for all spacing (multiplies by 1, 2, 3, etc)', 'naboodatabase' ); ?></p>
			</div>

			<div class="customize-control">
				<label><?php _e( 'Page Top/Bottom Margin (px)', 'naboodatabase' ); ?></label>
				<input type="number" name="<?php echo $this->option_name; ?>[page_margin]" min="10" max="100" value="<?php echo isset( $options['page_margin'] ) ? intval( $options['page_margin'] ) : 48; ?>" />
			</div>

			<div class="customize-control">
				<label><?php _e( 'Section Gap (px)', 'naboodatabase' ); ?></label>
				<input type="number" name="<?php echo $this->option_name; ?>[section_gap]" min="10" max="100" value="<?php echo isset( $options['section_gap'] ) ? intval( $options['section_gap'] ) : 32; ?>" />
			</div>

            <div class="customize-control">
				<label><?php _e( 'Global Border Radius (px)', 'naboodatabase' ); ?></label>
				<input type="number" name="<?php echo $this->option_name; ?>[border_radius]" min="0" max="50" value="<?php echo isset( $options['border_radius'] ) ? intval( $options['border_radius'] ) : 8; ?>" />
			</div>

			<h2><?php _e( 'Layout Controls', 'naboodatabase' ); ?></h2>
			
			<div class="customize-control">
				<label><?php _e( 'Sidebar Position', 'naboodatabase' ); ?></label>
				<select name="<?php echo $this->option_name; ?>[sidebar_pos]">
					<option value="none" <?php selected( $options['sidebar_pos'] ?? 'none', 'none' ); ?>><?php _e( 'No Sidebar', 'naboodatabase' ); ?></option>
					<option value="right" <?php selected( $options['sidebar_pos'] ?? 'none', 'right' ); ?>><?php _e( 'Right', 'naboodatabase' ); ?></option>
					<option value="left" <?php selected( $options['sidebar_pos'] ?? 'none', 'left' ); ?>><?php _e( 'Left', 'naboodatabase' ); ?></option>
				</select>
			</div>

			<div class="customize-control">
				<label><?php _e( 'Sidebar Width (%)', 'naboodatabase' ); ?></label>
				<input type="number" name="<?php echo $this->option_name; ?>[sidebar_width]" min="15" max="40" value="<?php echo isset( $options['sidebar_width'] ) ? intval( $options['sidebar_width'] ) : 25; ?>" />
			</div>
		</div>
		<?php
	}

	public function render_footer_tab( $options ) {
		?>
		<div class="customizer-section">
			<h2><?php _e( 'Footer Settings', 'naboodatabase' ); ?></h2>

			<div class="customize-control">
				<label><?php _e( 'Footer Style', 'naboodatabase' ); ?></label>
				<select name="<?php echo $this->option_name; ?>[footer_style]">
					<option value="default" <?php selected( $options['footer_style'] ?? 'default', 'default' ); ?>><?php _e( 'Default (Dark)', 'naboodatabase' ); ?></option>
					<option value="minimal" <?php selected( $options['footer_style'] ?? 'default', 'minimal' ); ?>><?php _e( 'Minimal (Light)', 'naboodatabase' ); ?></option>
				</select>
			</div>

			<div class="customize-control">
				<label><?php _e( 'Footer Columns', 'naboodatabase' ); ?></label>
				<select name="<?php echo $this->option_name; ?>[footer_columns]">
					<option value="2" <?php selected( $options['footer_columns'] ?? '3', '2' ); ?>>2</option>
					<option value="3" <?php selected( $options['footer_columns'] ?? '3', '3' ); ?>>3</option>
					<option value="4" <?php selected( $options['footer_columns'] ?? '3', '4' ); ?>>4</option>
				</select>
			</div>
			
			<div class="customize-control">
				<label><?php _e( 'Footer Background Color', 'naboodatabase' ); ?></label>
				<input type="text" name="<?php echo $this->option_name; ?>[footer_bg_color]" class="naboo-color-field" value="<?php echo isset( $options['footer_bg_color'] ) ? esc_attr( $options['footer_bg_color'] ) : '#0f2b46'; ?>" />
			</div>

			<div class="customize-control">
				<label><?php _e( 'Footer Text Color', 'naboodatabase' ); ?></label>
				<input type="text" name="<?php echo $this->option_name; ?>[footer_text_color]" class="naboo-color-field" value="<?php echo isset( $options['footer_text_color'] ) ? esc_attr( $options['footer_text_color'] ) : '#ffffff'; ?>" />
			</div>

			<div class="customize-control">
				<label><?php _e( 'Copyright Text', 'naboodatabase' ); ?></label>
				<textarea name="<?php echo $this->option_name; ?>[footer_text]" rows="4" class="large-text"><?php echo isset( $options['footer_text'] ) ? esc_textarea( $options['footer_text'] ) : ''; ?></textarea>
				<p class="description"><?php _e( 'HTML allowed (a, br, strong, em tags)', 'naboodatabase' ); ?></p>
			</div>

			<div class="customize-control">
				<label class="checkbox-label">
					<input type="checkbox" name="<?php echo $this->option_name; ?>[show_footer_widgets]" value="1" <?php checked( isset( $options['show_footer_widgets'] ) ? $options['show_footer_widgets'] : 1 ); ?> />
					<?php _e( 'Show Footer Widget Area', 'naboodatabase' ); ?>
				</label>
			</div>

			<h2><?php _e( '🔗 Social Media Links', 'naboodatabase' ); ?></h2>
			<p class="description"><?php _e( 'Add your social media URLs. Leave blank to hide.', 'naboodatabase' ); ?></p>

			<div class="customize-control">
				<label><?php _e( 'Facebook URL', 'naboodatabase' ); ?></label>
				<input type="url" name="<?php echo $this->option_name; ?>[social_facebook]" value="<?php echo isset( $options['social_facebook'] ) ? esc_url( $options['social_facebook'] ) : ''; ?>" />
			</div>

			<div class="customize-control">
				<label><?php _e( 'Twitter / X URL', 'naboodatabase' ); ?></label>
				<input type="url" name="<?php echo $this->option_name; ?>[social_twitter]" value="<?php echo isset( $options['social_twitter'] ) ? esc_url( $options['social_twitter'] ) : ''; ?>" />
			</div>

			<div class="customize-control">
				<label><?php _e( 'Instagram URL', 'naboodatabase' ); ?></label>
				<input type="url" name="<?php echo $this->option_name; ?>[social_instagram]" value="<?php echo isset( $options['social_instagram'] ) ? esc_url( $options['social_instagram'] ) : ''; ?>" />
			</div>

			<div class="customize-control">
				<label><?php _e( 'LinkedIn URL', 'naboodatabase' ); ?></label>
				<input type="url" name="<?php echo $this->option_name; ?>[social_linkedin]" value="<?php echo isset( $options['social_linkedin'] ) ? esc_url( $options['social_linkedin'] ) : ''; ?>" />
			</div>

			<div class="customize-control">
				<label><?php _e( 'YouTube URL', 'naboodatabase' ); ?></label>
				<input type="url" name="<?php echo $this->option_name; ?>[social_youtube]" value="<?php echo isset( $options['social_youtube'] ) ? esc_url( $options['social_youtube'] ) : ''; ?>" />
			</div>
		</div>
		<?php
	}

	public function render_advanced_tab( $options ) {
		?>
		<div class="customizer-section">
			<h2><?php _e( 'Advanced Settings', 'naboodatabase' ); ?></h2>
			
			<div class="customize-control">
				<label class="checkbox-label">
					<input type="checkbox" name="<?php echo $this->option_name; ?>[enable_animations]" value="1" <?php checked( isset( $options['enable_animations'] ) ? $options['enable_animations'] : 1 ); ?> />
					<?php _e( 'Enable Animations & Transitions', 'naboodatabase' ); ?>
				</label>
			</div>

			<div class="customize-control">
				<label class="checkbox-label">
					<input type="checkbox" name="<?php echo $this->option_name; ?>[scroll_animations]" value="1" <?php checked( isset( $options['scroll_animations'] ) ? $options['scroll_animations'] : 1 ); ?> />
					<?php _e( 'Enable Scroll-triggered Fade-in Animations', 'naboodatabase' ); ?>
				</label>
			</div>

			<div class="customize-control">
				<label><?php _e( 'Animation Speed (ms)', 'naboodatabase' ); ?></label>
				<input type="number" name="<?php echo $this->option_name; ?>[animation_speed]" min="100" max="1000" step="50" value="<?php echo isset( $options['animation_speed'] ) ? intval( $options['animation_speed'] ) : 300; ?>" />
			</div>

			<h2><?php _e( 'Custom CSS', 'naboodatabase' ); ?></h2>
			
			<div class="customize-control">
				<label><?php _e( 'Add Custom CSS Rules', 'naboodatabase' ); ?></label>
				<p class="description"><?php _e( 'Enter additional CSS rules that will be appended to the theme styles', 'naboodatabase' ); ?></p>
				<textarea name="<?php echo $this->option_name; ?>[custom_css]" rows="10" class="large-text code"><?php echo isset( $options['custom_css'] ) ? esc_textarea( $options['custom_css'] ) : ''; ?></textarea>
			</div>

			<h2><?php _e( 'Reset Theme', 'naboodatabase' ); ?></h2>
			
			<div class="customize-control reset-control">
				<p class="description"><?php _e( 'Click the button below to reset all theme settings to default values', 'naboodatabase' ); ?></p>
				<button type="button" class="button button-danger" id="reset-theme-btn">
					<?php _e( 'Reset to Defaults', 'naboodatabase' ); ?>
				</button>
			</div>
		</div>
		<?php
	}
}
