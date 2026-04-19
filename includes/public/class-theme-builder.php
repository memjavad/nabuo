<?php

namespace ArabPsychology\NabooDatabase\Public;

class Theme_Builder {

    private $plugin_path;

    public function __construct() {
        $this->plugin_path = plugin_dir_path( dirname( __DIR__ ) ) . 'templates/'; 
    }

    public function init() {
        add_filter( 'template_include', array( $this, 'template_loader' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_theme_styles' ), 9999 );
        add_action( 'wp_enqueue_scripts', array( $this, 'dequeue_theme_styles' ), 100 );
        add_action( 'after_setup_theme', array( $this, 'handle_admin_bar' ) );
        add_action( 'body_class', array( $this, 'add_body_classes' ) );
    }

    public function handle_admin_bar() {
        $options = $this->get_combined_options();
        if ( ! empty( $options['hide_wp_admin_bar'] ) && ! is_admin() ) {
            add_filter( 'show_admin_bar', '__return_false' );
        }
    }

    public function add_body_classes( $classes ) {
        $options = $this->get_combined_options();
        $enable_theme = isset( $options['enable_theme'] ) ? $options['enable_theme'] : 1;

        if ( ! $enable_theme ) {
            return $classes;
        }

        // Dark mode
        $color_scheme = isset( $options['color_scheme'] ) ? $options['color_scheme'] : 'light';
        if ( $color_scheme === 'dark' ) {
            $classes[] = 'naboo-dark-mode';
        }

        return $classes;
    }

    private function get_combined_options() {
        return get_option( 'naboodatabase_customizer_options', array() );
    }

    public function dequeue_theme_styles() {
        $options = $this->get_combined_options();
        $enable_theme = isset( $options['enable_theme'] ) ? $options['enable_theme'] : 1;
        
        if ( ! $enable_theme ) {
            return;
        }

        // Remove ALL styles from the active theme directory
        $theme_dir_uri = get_stylesheet_directory_uri();
        $parent_dir_uri = get_template_directory_uri();
        $wp_styles = wp_styles();

        foreach ( $wp_styles->registered as $handle => $style ) {
            if ( ! empty( $style->src ) ) {
                $src = $style->src;
                if ( strpos( $src, $theme_dir_uri ) !== false || strpos( $src, $parent_dir_uri ) !== false ) {
                    wp_dequeue_style( $handle );
                    wp_deregister_style( $handle );
                }
            }
        }

        $common_handles = array(
            'style', 'main', 'theme-style', 'global-styles',
            'wp-block-library-theme', 'classic-theme-styles',
        );
        $theme = wp_get_theme();
        $common_handles[] = $theme->get_stylesheet();
        $common_handles[] = $theme->get_template();
        $common_handles[] = $theme->get_stylesheet() . '-style';
        $common_handles[] = $theme->get_template() . '-style';

        foreach ( $common_handles as $handle ) {
            wp_dequeue_style( $handle );
        }
    }

    public function enqueue_theme_styles() {
        $options = $this->get_combined_options();
        $enable_theme = isset( $options['enable_theme'] ) ? $options['enable_theme'] : 1;

        if ( ! $enable_theme ) {
            return;
        }

        // Enqueue Google Fonts (Inter + Outfit)
        $body_font = isset( $options['body_font'] ) ? $options['body_font'] : 'system';
        $heading_font = isset( $options['heading_font'] ) ? $options['heading_font'] : '';

        $google_fonts = array();
        $google_font_families = array(
            'Roboto'           => 'Roboto:wght@400;500;600;700',
            'Inter'            => 'Inter:wght@400;500;600;700;800',
            'Open Sans'        => 'Open+Sans:wght@400;500;600;700',
            'Lato'             => 'Lato:wght@400;700',
            'Playfair Display' => 'Playfair+Display:wght@400;600;700',
            'Merriweather'     => 'Merriweather:wght@400;700',
            'Montserrat'       => 'Montserrat:wght@400;500;600;700;800',
            'Poppins'          => 'Poppins:wght@400;500;600;700;800',
            'Outfit'           => 'Outfit:wght@400;500;600;700;800',
        );

        // Always load Inter + Outfit for default
        $google_fonts[] = 'Inter:wght@400;500;600;700;800';
        $google_fonts[] = 'Outfit:wght@400;500;600;700;800';

        // Add selected fonts
        foreach ( $google_font_families as $name => $family ) {
            if ( strpos( $body_font, $name ) !== false || strpos( $heading_font, $name ) !== false ) {
                if ( ! in_array( $family, $google_fonts ) ) {
                    $google_fonts[] = $family;
                }
            }
        }

        $fonts_url = 'https://fonts.googleapis.com/css2?' . implode( '&', array_map( function( $f ) {
            return 'family=' . $f;
        }, $google_fonts ) ) . '&display=swap';

        wp_enqueue_style( 'naboodatabase-google-fonts', $fonts_url, array(), null );

        // Enqueue Dashicons (for template icons) - Removed for performance

        // Enqueue the theme CSS
        wp_enqueue_style( 'naboodatabase-theme', plugin_dir_url( dirname( __DIR__ ) ) . 'includes/public/css/naboodatabase-theme.css', array( 'naboodatabase-google-fonts' ), NABOODATABASE_VERSION, 'all' );

        // Enqueue the theme JS
        wp_enqueue_script( 'naboodatabase-theme-js', plugin_dir_url( dirname( __DIR__ ) ) . 'includes/public/js/naboodatabase-theme.js', array(), NABOODATABASE_VERSION, true );

        // Build Dynamic CSS
        $css = $this->generate_dynamic_css( $options );

        wp_add_inline_style( 'naboodatabase-theme', $css );
    }

    private function generate_dynamic_css( $options ) {
        $css = ":root {\n";

        // ---------- Colors ----------
        if ( ! empty( $options['primary_color'] ) ) {
            $css .= "    --naboo-primary: " . $options['primary_color'] . ";\n";
            $css .= "    --naboo-primary-light: " . $options['primary_color'] . ";\n";
        }
        if ( ! empty( $options['accent_color'] ) )       $css .= "    --naboo-accent: " . $options['accent_color'] . ";\n";
        if ( ! empty( $options['accent_light_color'] ) )  $css .= "    --naboo-accent-light: " . $options['accent_light_color'] . ";\n";
        if ( ! empty( $options['text_dark_color'] ) )     $css .= "    --naboo-text-primary: " . $options['text_dark_color'] . ";\n";
        if ( ! empty( $options['text_light_color'] ) )    $css .= "    --naboo-text-secondary: " . $options['text_light_color'] . ";\n";
        if ( ! empty( $options['bg_color'] ) )            $css .= "    --naboo-bg: " . $options['bg_color'] . ";\n";
        if ( ! empty( $options['border_color'] ) )        $css .= "    --naboo-border: " . $options['border_color'] . ";\n";

        // ---------- Typography ----------
        if ( ! empty( $options['body_font'] ) ) {
            $font = $options['body_font'] === 'system' ? 'system-ui, -apple-system, sans-serif' : $options['body_font'];
            $css .= "    --naboo-font-body: " . $font . ";\n";
        }
        if ( ! empty( $options['heading_font'] ) ) {
            $css .= "    --naboo-font-heading: " . $options['heading_font'] . ";\n";
        }
        if ( ! empty( $options['base_font_size'] ) ) {
            $css .= "    font-size: " . intval( $options['base_font_size'] ) . "px;\n";
        }
        if ( ! empty( $options['line_height'] ) ) {
            $css .= "    --naboo-line-height: " . floatval( $options['line_height'] ) . ";\n";
        }

        // ---------- Layout ----------
        $container_width = ! empty( $options['container_width'] ) ? intval( $options['container_width'] ) : 1200;
        $css .= "    --naboo-container-width: " . $container_width . "px;\n";

        if ( ! empty( $options['border_radius'] ) ) {
            $r = intval( $options['border_radius'] );
            $css .= "    --naboo-radius-sm: " . $r . "px;\n";
            $css .= "    --naboo-radius-md: " . ( $r + 4 ) . "px;\n";
            $css .= "    --naboo-radius-lg: " . ( $r + 8 ) . "px;\n";
        }

        // ---------- Buttons ----------
        if ( ! empty( $options['button_primary_color'] ) ) {
            $css .= "    --naboo-btn-bg: " . $options['button_primary_color'] . ";\n";
        }
        if ( ! empty( $options['button_text_color'] ) ) {
            $css .= "    --naboo-btn-text: " . $options['button_text_color'] . ";\n";
        }
        if ( ! empty( $options['button_radius'] ) ) {
            $css .= "    --naboo-btn-radius: " . intval( $options['button_radius'] ) . "px;\n";
        }

        // ---------- Cards ----------
        if ( ! empty( $options['card_bg_color'] ) ) {
            $css .= "    --naboo-surface: " . $options['card_bg_color'] . ";\n";
        }
        if ( ! empty( $options['card_image_height'] ) ) {
            $css .= "    --naboo-card-image-height: " . intval( $options['card_image_height'] ) . "px;\n";
        }

        // ---------- Forms ----------
        if ( ! empty( $options['input_bg_color'] ) )     $css .= "    --naboo-input-bg: " . $options['input_bg_color'] . ";\n";
        if ( ! empty( $options['input_border_color'] ) ) $css .= "    --naboo-input-border: " . $options['input_border_color'] . ";\n";
        if ( ! empty( $options['input_focus_color'] ) )  $css .= "    --naboo-input-focus: " . $options['input_focus_color'] . ";\n";

        // ---------- Footer ----------
        if ( ! empty( $options['footer_bg_color'] ) )   $css .= "    --naboo-footer-bg: " . $options['footer_bg_color'] . ";\n";
        if ( ! empty( $options['footer_text_color'] ) ) $css .= "    --naboo-footer-text: " . $options['footer_text_color'] . ";\n";

        $css .= "}\n\n";

        // ===== Global Resets (prevent theme from constraining width) =====
        $css .= "html { max-width: none !important; width: 100% !important; padding: 0 !important; margin: 0 !important; }\n";
        $css .= "body { max-width: none !important; width: 100% !important; }\n";
        $css .= "#page, .site { max-width: none !important; width: 100% !important; }\n";

        // ===== Container =====
        $css .= ".naboo-container { max-width: var(--naboo-container-width, 1200px) !important; width: 100% !important; margin-inline: auto !important; }\n";

        // ===== Card Image Height =====
        if ( ! empty( $options['card_image_height'] ) ) {
            $css .= ".naboo-card-image { height: " . intval( $options['card_image_height'] ) . "px; }\n";
        }

        // ===== Buttons =====
        if ( ! empty( $options['button_primary_color'] ) ) {
            $css .= ".naboo-btn, .naboo-btn-primary { background: linear-gradient(135deg, " . $options['button_primary_color'] . ", " . ( $options['button_primary_color'] ) . ") !important; }\n";
        }
        if ( ! empty( $options['button_padding_v'] ) && ! empty( $options['button_padding_h'] ) ) {
            $css .= ".naboo-btn, .naboo-btn-primary, .naboo-btn-secondary { padding: " . intval( $options['button_padding_v'] ) . "px " . intval( $options['button_padding_h'] ) . "px !important; }\n";
        }

        // ===== Forms =====
        if ( ! empty( $options['input_bg_color'] ) ) {
            $css .= "input[type='text'], input[type='email'], input[type='search'], input[type='number'], textarea, select { background: " . $options['input_bg_color'] . " !important; }\n";
        }
        if ( ! empty( $options['input_border_color'] ) ) {
            $css .= "input[type='text'], input[type='email'], input[type='search'], input[type='number'], textarea, select { border-color: " . $options['input_border_color'] . " !important; }\n";
        }
        if ( ! empty( $options['input_radius'] ) ) {
            $css .= "input[type='text'], input[type='email'], input[type='search'], input[type='number'], textarea, select { border-radius: " . intval( $options['input_radius'] ) . "px !important; }\n";
        }

        // ===== Boxed Layout =====
        if ( isset( $options['layout_mode'] ) && $options['layout_mode'] === 'boxed' ) {
            $css .= "body { background: #e0e0e0 !important; }\n";
            $css .= "#page { max-width: var(--naboo-container-width, 1200px) !important; margin: 0 auto; background: var(--naboo-bg); box-shadow: var(--naboo-shadow-xl); }\n";
        }

        // ===== Sidebar Width =====
        if ( ! empty( $options['sidebar_width'] ) ) {
            $sw = intval( $options['sidebar_width'] );
            $css .= ".widget-area { flex: 0 0 " . $sw . "% !important; max-width: " . $sw . "% !important; }\n";
        }

        // ===== Header =====
        $header_style = isset( $options['header_style'] ) ? $options['header_style'] : 'solid';
        if ( $header_style === 'solid' && ! empty( $options['header_bg_color'] ) ) {
            $css .= ".naboo-site-header.header-solid { background: " . $options['header_bg_color'] . " !important; }\n";
        }
        if ( ! empty( $options['header_text_color'] ) ) {
            $css .= ".naboo-site-header .site-title a { -webkit-text-fill-color: " . $options['header_text_color'] . " !important; background: none !important; color: " . $options['header_text_color'] . " !important; }\n";
            $css .= ".naboo-site-header .naboo-main-navigation a { color: " . $options['header_text_color'] . " !important; }\n";
        }
        if ( ! empty( $options['sticky_header'] ) && empty( $options['sticky_header'] ) ) {
            $css .= ".naboo-site-header { position: relative !important; }\n";
        }

        // ===== Footer =====
        if ( ! empty( $options['footer_bg_color'] ) ) {
            $css .= ".naboo-site-footer { background: " . $options['footer_bg_color'] . " !important; }\n";
        }
        if ( ! empty( $options['footer_text_color'] ) ) {
            $css .= ".naboo-site-footer, .naboo-site-footer .naboo-site-info, .naboo-site-footer p { color: " . $options['footer_text_color'] . " !important; }\n";
        }

        // ===== Animations Toggle =====
        if ( isset( $options['enable_animations'] ) && empty( $options['enable_animations'] ) ) {
            $css .= "*, *::before, *::after { animation: none !important; transition: none !important; }\n";
        }
        if ( ! empty( $options['animation_speed'] ) ) {
            $css .= ":root { --naboo-duration: " . intval( $options['animation_speed'] ) . "ms; }\n";
        }

        // ===== Custom CSS =====
        if ( ! empty( $options['custom_css'] ) ) {
            $css .= "\n/* Custom CSS */\n" . $options['custom_css'] . "\n";
        }

        return $css;
    }

    public function template_loader( $template ) {
        
        $options = $this->get_combined_options();
        $enable_theme = isset( $options['enable_theme'] ) ? $options['enable_theme'] : 1;

        if ( ! $enable_theme ) {
            return $template;
        }
        
        $new_template = '';

        if ( is_embed() ) {
            return $template;
        }

        if ( is_singular( 'psych_scale' ) ) {
            $new_template = $this->plugin_path . 'single-psych_scale.php';
        } elseif ( is_post_type_archive( 'psych_scale' ) || is_tax( array( 'scale_category', 'scale_author', 'scale_year', 'scale_language', 'scale_test_type', 'scale_format', 'scale_age_group' ) ) ) {
             $new_template = $this->plugin_path . 'archive-psych_scale.php';
        } elseif ( is_page() ) {
            $new_template = $this->plugin_path . 'page.php';
        } elseif ( is_single() ) {
            $new_template = $this->plugin_path . 'single-psych_scale.php';
        } elseif ( is_404() ) {
            $new_template = $this->plugin_path . '404.php';
        } elseif ( is_home() || is_front_page() || is_archive() || is_search() ) {
             $new_template = $this->plugin_path . 'index.php';
        }

        if ( ! empty( $new_template ) && file_exists( $new_template ) ) {
            return $new_template;
        }

        return $template;
    }

}
