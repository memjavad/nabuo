<?php
if ( function_exists( 'wpa_get_header' ) ) {
    wpa_get_header();
} else {
    include 'header.php';
}
?>

<?php
$options = get_option( 'naboodatabase_customizer_options', array() );
$show_breadcrumbs = ! empty( $options['breadcrumbs'] );
?>

<div class="naboo-container">

    <?php if ( $show_breadcrumbs ) : ?>
        <div class="naboo-breadcrumbs">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php _e( 'Home', 'naboodatabase' ); ?></a>
            <span class="separator">/</span>
            <a href="<?php echo esc_url( get_post_type_archive_link( 'psych_scale' ) ); ?>"><?php _e( 'Scales', 'naboodatabase' ); ?></a>
            <span class="separator">/</span>
            <span><?php the_title(); ?></span>
        </div>
    <?php endif; ?>

    <main id="primary" class="site-main">

        <?php
        while ( have_posts() ) :
            the_post();
            ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class( 'naboo-single-scale' ); ?>>
                <header class="entry-header naboo-scholarly-header">
                    <?php the_title( '<h1 class="entry-title naboo-scholarly-title">', '</h1>' ); ?>
                    
                    <div class="naboo-scholarly-byline">
                        <?php
                        $authors = get_the_terms( get_the_ID(), 'scale_author' );
                        $author_details = get_post_meta( get_the_ID(), '_naboo_scale_author_details', true );
                        
                        if ( $authors && ! is_wp_error( $authors ) ) {
                            $author_names = wp_list_pluck( $authors, 'name' );
                            echo '<div class="naboo-scholarly-authors"><strong>' . esc_html( implode( ', ', $author_names ) ) . '</strong></div>';
                        } elseif ( empty( $author_details ) ) {
                            echo '<div class="naboo-scholarly-authors"><strong>' . esc_html( get_the_author() ) . '</strong></div>';
                        }
                        ?>
                    </div>

                </header>

                <div class="entry-content">
                    <?php
                    // Note: We rely on the 'the_content' filter in Frontend class to inject the meta box and related scales
                    the_content();
                    ?>
                </div>

            </article>
            <?php
        endwhile;
        ?>

    </main>

    <?php
    if ( isset( $options['sidebar_pos'] ) && $options['sidebar_pos'] !== 'none' ) {
        include plugin_dir_path( __FILE__ ) . 'sidebar.php';
    }
    ?>
</div>

<?php
if ( function_exists( 'wpa_get_footer' ) ) {
    wpa_get_footer();
} else {
    include 'footer.php';
}
?>
