<?php
if ( function_exists( 'wpa_get_header' ) ) {
    wpa_get_header();
} else {
    include 'header.php';
}
?>

<?php
/**
 * Hide the main site header ONLY on the search landing page.
 * It will reappear once results are active (based on body class).
 */
?>
<style>
    body.naboo-search-page:not(.naboo-results-view) header#masthead, 
    body.naboo-search-page:not(.naboo-results-view) header.site-header, 
    body.naboo-search-page:not(.naboo-results-view) .wpa-header, 
    body.naboo-search-page:not(.naboo-results-view) #wpa-header, 
    body.naboo-search-page:not(.naboo-results-view) .ast-main-header-wrap,
    body.naboo-search-page:not(.naboo-results-view) .et_header_outer,
    body.naboo-search-page:not(.naboo-results-view) #main-header { 
        display: none !important; 
    }
    body.naboo-search-page:not(.naboo-results-view), 
    body.naboo-search-page:not(.naboo-results-view) .site-content { 
        padding-top: 0 !important; 
        margin-top: 0 !important; 
    }
</style>


<div class="naboo-container">
    <main id="primary" class="site-main">

        <?php
        while ( have_posts() ) :
            the_post();
            ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <?php if ( ! has_shortcode( get_the_content(), 'naboo_search' ) && ! has_shortcode( get_the_content(), 'naboo_submit' ) && ! has_shortcode( get_the_content(), 'naboo_dashboard' ) ) : ?>
                    <header class="entry-header">
                        <?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
                    </header>
                <?php endif; ?>

                <div class="entry-content">
                    <!-- DEBUG: NABOO PAGE TEMPLATE LOADED -->
                    <?php
                    the_content();

                    wp_link_pages( array(
                        'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'naboodatabase' ),
                        'after'  => '</div>',
                    ) );
                    ?>
                </div>
            </article>
            <?php
        endwhile;
        ?>

    </main>

    <?php
    $options = get_option( 'naboodatabase_customizer_options', array() );
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
