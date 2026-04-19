<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php
$options = get_option( 'naboodatabase_customizer_options', array() );

// Header style class
$header_style = isset( $options['header_style'] ) ? $options['header_style'] : 'solid';
$header_class = 'header-' . sanitize_html_class( $header_style );

// Nav style class
$nav_style = isset( $options['nav_style'] ) ? $options['nav_style'] : 'underline';
$nav_class = 'nav-style-' . sanitize_html_class( $nav_style );

// Card style class
$card_style = isset( $options['card_style'] ) ? $options['card_style'] : 'classic';
$card_class = 'card-style-' . sanitize_html_class( $card_style );

// Footer style class
$footer_style = isset( $options['footer_style'] ) ? $options['footer_style'] : 'default';
$footer_class = 'footer-style-' . sanitize_html_class( $footer_style );

// Dark mode
$color_scheme = isset( $options['color_scheme'] ) ? $options['color_scheme'] : 'light';

// Logo settings
$logo_url   = isset( $options['logo_url'] ) ? $options['logo_url'] : '';
$logo_width = isset( $options['logo_width'] ) ? intval( $options['logo_width'] ) : 44;

// Feature flags
$show_progress_bar = ! empty( $options['progress_bar'] );
$show_breadcrumbs  = ! empty( $options['breadcrumbs'] );
$show_tagline      = ! empty( $options['show_tagline'] );
$hide_mobile_menu  = ! empty( $options['hide_mobile_menu'] );
$custom_header_text = isset( $options['custom_header_text'] ) ? $options['custom_header_text'] : '';
?>

<?php if ( $show_progress_bar ) : ?>
    <div class="naboo-progress-bar" role="progressbar"></div>
<?php endif; ?>

<div id="page" class="site <?php echo esc_attr( $nav_class . ' ' . $card_class . ' ' . $footer_class ); ?>">
    <header id="masthead" class="naboo-site-header <?php echo esc_attr( $header_class ); ?>">
        <div class="naboo-container">
            <div class="naboo-site-branding">
                <?php
                if ( $logo_url ) {
                    echo '<a href="' . esc_url( home_url( '/' ) ) . '" rel="home"><img src="' . esc_url( $logo_url ) . '" alt="' . esc_attr( get_bloginfo( 'name' ) ) . '" class="naboo-custom-logo" style="max-width: ' . intval( $logo_width ) . 'px;"></a>';
                } elseif ( has_custom_logo() ) {
                    the_custom_logo();
                }
                ?>
                <div>
                    <p class="site-title">
                        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
                            <?php echo $custom_header_text ? esc_html( $custom_header_text ) : get_bloginfo( 'name' ); ?>
                        </a>
                    </p>
                    <?php if ( $show_tagline ) : ?>
                        <?php $description = get_bloginfo( 'description', 'display' ); ?>
                        <?php if ( $description ) : ?>
                            <p class="site-description"><?php echo $description; ?></p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ( ! $hide_mobile_menu ) : ?>
            <button class="naboo-mobile-toggle" aria-label="<?php esc_attr_e( 'Toggle Menu', 'naboodatabase' ); ?>" aria-expanded="false">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <?php endif; ?>

            <nav id="site-navigation" class="naboo-main-navigation" role="navigation">
                <?php
                wp_nav_menu( array(
                    'theme_location' => 'primary',
                    'menu_id'        => 'primary-menu',
                    'container'      => false,
                    'fallback_cb'    => false,
                ) );
                ?>
                <a href="<?php echo esc_url( home_url( '/?s=' ) ); ?>" class="naboo-header-search-toggle" aria-label="<?php esc_attr_e( 'Search', 'naboodatabase' ); ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </a>
            </nav>
        </div>
    </header>

    <?php
    $sidebar_pos = isset( $options['sidebar_pos'] ) && $options['sidebar_pos'] !== 'none' ? $options['sidebar_pos'] : '';
    $layout_class = $sidebar_pos ? 'has-sidebar sidebar-' . $sidebar_pos : 'no-sidebar';
    ?>
    <div id="content" class="naboo-site-content <?php echo esc_attr( $layout_class ); ?>">
