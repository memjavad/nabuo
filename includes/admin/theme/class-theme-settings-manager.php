<?php
/**
 * Theme Settings Manager - Handles registration, migration, and sanitization of theme options
 *
 * @package ArabPsychology\NabooDatabase\Admin\Theme
 */

namespace ArabPsychology\NabooDatabase\Admin\Theme;

/**
 * Theme_Settings_Manager class
 */
class Theme_Settings_Manager {

	/**
	 * Option name
	 * @var string
	 */
	private $option_name = 'naboodatabase_customizer_options';

	/**
	 * Register settings with WordPress
	 */
	public function register_settings() {
		register_setting(
			'naboodatabase_customizer_group',
			$this->option_name,
			array( $this, 'sanitize_options' )
		);

		// Migrate old settings if they exist
		if ( get_option( 'naboodatabase_theme_options' ) ) {
			$this->migrate_old_settings();
		}
	}

	/**
	 * Migrate legacy theme options to the new customizer format
	 */
	public function migrate_old_settings() {
		$old_options     = get_option( 'naboodatabase_theme_options', array() );
		$current_options = get_option( $this->option_name, array() );

		if ( empty( $old_options ) ) {
			return;
		}

		$migrated = false;

		// Map old keys to new keys
		$mapping = array(
			'enable_theme'    => 'enable_theme',
			'layout_mode'     => 'layout_mode',
			'container_width' => 'container_width',
			'logo_url'        => 'logo_url',
			'sticky_header'   => 'sticky_header',
			'font_family'     => 'body_font',
			'heading_font'    => 'heading_font',
			'primary_color'   => 'primary_color',
			'bg_color'        => 'bg_color',
			'sidebar_pos'     => 'sidebar_pos',
			'footer_text'     => 'footer_text',
			'custom_css'      => 'custom_css',
			'secondary_color' => 'accent_color',
		);

		foreach ( $mapping as $old_key => $new_key ) {
			if ( isset( $old_options[ $old_key ] ) && ! isset( $current_options[ $new_key ] ) ) {
				$current_options[ $new_key ] = $old_options[ $old_key ];
				$migrated                    = true;
			}
		}

		// Handle border_radius specially as it's new in Customizer
		if ( isset( $old_options['border_radius'] ) && ! isset( $current_options['border_radius'] ) ) {
			$current_options['border_radius'] = $old_options['border_radius'];
			$migrated                        = true;
		}

		if ( $migrated ) {
			update_option( $this->option_name, $current_options );
		}

		// Delete old option to prevent re-migration
		delete_option( 'naboodatabase_theme_options' );
	}

	/**
	 * Sanitize options on save
	 */
	public function sanitize_options( $input ) {
		$sanitized  = get_option( $this->option_name, array() );
		$active_tab = isset( $_POST['naboo_active_tab'] ) ? sanitize_text_field( $_POST['naboo_active_tab'] ) : '';

		if ( empty( $active_tab ) ) {
			return $sanitized;
		}

		// Define which fields belong to which tab
		$tab_fields = array(
			'general'    => array(
				'checkboxes' => array( 'enable_theme', 'hide_wp_admin_bar', 'back_to_top', 'progress_bar', 'breadcrumbs' ),
				'text'       => array( 'layout_mode', 'color_scheme' ),
				'numbers'    => array( 'container_width' ),
			),
			'header'     => array(
				'checkboxes' => array( 'sticky_header', 'show_tagline' ),
				'text'       => array( 'logo_url', 'header_style', 'nav_style', 'custom_header_text', 'hide_mobile_menu' ),
				'numbers'    => array( 'logo_width' ),
				'colors'     => array( 'header_bg_color', 'header_text_color' ),
			),
			'icons'      => array(
				'checkboxes' => array( 'hide_main_search_logo' ),
				'numbers'    => array( 'main_search_logo_width' ),
				'text'       => array( 'main_search_logo_url', 'favicon_url', 'mobile_icon_url', 'main_search_title' ),
			),
			'colors'     => array(
				'colors'  => array( 'primary_color', 'accent_color', 'accent_light_color', 'text_dark_color', 'text_light_color', 'bg_color', 'border_color', 'shadow_color' ),
				'numbers' => array( 'shadow_opacity' ),
			),
			'typography' => array(
				'text'    => array( 'body_font', 'heading_font' ),
				'numbers' => array( 'base_font_size', 'heading_size_mult', 'line_height' ),
			),
			'buttons'    => array(
				'colors'  => array( 'button_primary_color', 'button_text_color' ),
				'numbers' => array( 'button_radius', 'button_padding_h', 'button_padding_v' ),
			),
			'forms'      => array(
				'colors'  => array( 'input_bg_color', 'input_border_color', 'input_focus_color' ),
				'numbers' => array( 'input_radius' ),
				'text'    => array( 'form_label_weight' ),
			),
			'cards'      => array(
				'colors'  => array( 'card_bg_color' ),
				'numbers' => array( 'card_border_width', 'card_radius', 'card_padding', 'card_shadow_opacity', 'card_hover_lift', 'card_image_height' ),
				'text'    => array( 'card_style' ),
			),
			'layout'     => array(
				'numbers' => array( 'spacing_unit', 'page_margin', 'section_gap', 'sidebar_width', 'border_radius' ),
				'text'    => array( 'sidebar_pos' ),
			),
			'footer'     => array(
				'colors'     => array( 'footer_bg_color', 'footer_text_color' ),
				'textarea'   => array( 'footer_text' ),
				'checkboxes' => array( 'show_footer_widgets' ),
				'text'       => array( 'footer_style', 'footer_columns' ),
				'urls'       => array( 'social_facebook', 'social_twitter', 'social_instagram', 'social_linkedin', 'social_youtube' ),
			),
			'advanced'   => array(
				'checkboxes' => array( 'enable_animations', 'scroll_animations' ),
				'numbers'    => array( 'animation_speed' ),
				'textarea'   => array( 'custom_css' ),
			),
		);

		if ( isset( $tab_fields[ $active_tab ] ) ) {
			$fields = $tab_fields[ $active_tab ];

			// Checkboxes
			if ( isset( $fields['checkboxes'] ) ) {
				foreach ( $fields['checkboxes'] as $checkbox ) {
					$sanitized[ $checkbox ] = isset( $input[ $checkbox ] ) ? 1 : 0;
				}
			}

			// Colors
			if ( isset( $fields['colors'] ) ) {
				foreach ( $fields['colors'] as $color ) {
					if ( isset( $input[ $color ] ) ) {
						$sanitized[ $color ] = sanitize_hex_color( $input[ $color ] );
					}
				}
			}

			// Text
			if ( isset( $fields['text'] ) ) {
				foreach ( $fields['text'] as $text ) {
					if ( isset( $input[ $text ] ) ) {
						$sanitized[ $text ] = sanitize_text_field( $input[ $text ] );
					}
				}
			}

			// Numbers
			if ( isset( $fields['numbers'] ) ) {
				foreach ( $fields['numbers'] as $number ) {
					if ( isset( $input[ $number ] ) ) {
						$sanitized[ $number ] = floatval( $input[ $number ] );
					}
				}
			}

			// Textareas (Specific handling)
			if ( isset( $fields['textarea'] ) ) {
				if ( in_array( 'custom_css', $fields['textarea'], true ) && isset( $input['custom_css'] ) ) {
					$sanitized['custom_css'] = wp_strip_all_tags( $input['custom_css'] );
				}
				if ( in_array( 'footer_text', $fields['textarea'], true ) && isset( $input['footer_text'] ) ) {
					$sanitized['footer_text'] = wp_kses_post( $input['footer_text'] );
				}
			}

			// URLs
			if ( isset( $fields['urls'] ) ) {
				foreach ( $fields['urls'] as $url_field ) {
					if ( isset( $input[ $url_field ] ) ) {
						$sanitized[ $url_field ] = esc_url_raw( $input[ $url_field ] );
					}
				}
			}
		}

		return $sanitized;
	}
}
