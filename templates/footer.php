    </div><!-- #content -->

    <?php
    $options = get_option( 'naboodatabase_customizer_options', array() );
    $footer_columns = isset( $options['footer_columns'] ) ? intval( $options['footer_columns'] ) : 3;
    $show_back_to_top = ! empty( $options['back_to_top'] );

    // Social links
    $social_links = array();
    $social_platforms = array(
        'facebook'  => array( 'icon' => '&#xf09a;', 'label' => 'Facebook' ),
        'twitter'   => array( 'icon' => '&#xf099;', 'label' => 'Twitter' ),
        'instagram' => array( 'icon' => '&#xf16d;', 'label' => 'Instagram' ),
        'linkedin'  => array( 'icon' => '&#xf0e1;', 'label' => 'LinkedIn' ),
        'youtube'   => array( 'icon' => '&#xf167;', 'label' => 'YouTube' ),
    );
    foreach ( $social_platforms as $key => $data ) {
        if ( ! empty( $options[ 'social_' . $key ] ) ) {
            $social_links[ $key ] = $options[ 'social_' . $key ];
        }
    }
    ?>

    <footer id="colophon" class="naboo-site-footer">
        <div class="naboo-container">
            <?php if ( is_active_sidebar( 'footer-1' ) ) : ?>
                <div class="naboo-footer-widgets" style="--naboo-footer-columns: <?php echo intval( $footer_columns ); ?>;">
                    <?php dynamic_sidebar( 'footer-1' ); ?>
                </div>
            <?php endif; ?>

            <?php if ( ! empty( $social_links ) ) : ?>
                <div class="naboo-footer-social">
                    <?php foreach ( $social_links as $platform => $url ) : ?>
                        <a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( $social_platforms[ $platform ]['label'] ); ?>" title="<?php echo esc_attr( $social_platforms[ $platform ]['label'] ); ?>">
                            <?php echo esc_html( $social_platforms[ $platform ]['label'][0] ); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="naboo-site-info">
                <?php
                $footer_text = isset( $options['footer_text'] ) ? $options['footer_text'] : '';

                if ( $footer_text ) {
                    echo wp_kses_post( $footer_text );
                } else {
                    ?>
                    <p>&copy; <?php echo date( 'Y' ); ?> <?php bloginfo( 'name' ); ?>. <?php _e( 'All rights reserved.', 'naboodatabase' ); ?></p>
                    <?php
                }
                ?>
            </div>
        </div>
    </footer>

    <?php if ( $show_back_to_top ) : ?>
        <button class="naboo-back-to-top" aria-label="<?php esc_attr_e( 'Back to top', 'naboodatabase' ); ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;"><line x1="12" y1="19" x2="12" y2="5"></line><polyline points="5 12 12 5 19 12"></polyline></svg>
        </button>
    <?php endif; ?>

</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
