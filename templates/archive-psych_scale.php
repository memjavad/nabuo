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

        <?php if ( have_posts() ) : ?>

            <?php if ( ! is_front_page() ) : ?>
            <header class="page-header">
                <h1 class="page-title"><?php the_archive_title(); ?></h1>
                <?php the_archive_description( '<div class="archive-description">', '</div>' ); ?>

                <?php
                if ( true ) : // Always show search on archives
                    $current_term = get_queried_object();
                ?>
                <div class="naboo-archive-search-container" style="text-align: center; margin: 30px auto; max-width: 600px;">
                    <div class="naboo-archive-search-wrapper" style="position: relative; display: inline-block; width: 100%;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #64748b;"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        <input type="text" 
                               id="naboo-archive-filter-input"
                               placeholder="<?php esc_attr_e( 'Search within this list...', 'naboodatabase' ); ?>" 
                               data-taxonomy="<?php echo esc_attr( $current_term->taxonomy ?? '' ); ?>"
                               data-term-id="<?php echo esc_attr( $current_term->term_id ?? '' ); ?>"
                               style="width: 100%; padding: 15px 20px 15px 45px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 16px; outline: none; transition: border-color 0.2s; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05);">
                        <div id="naboo-archive-spinner" style="display: none; position: absolute; right: 15px; top: 50%; transform: translateY(-50%);">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="spin" style="color: #2563eb;"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                        </div>
                    </div>
                </div>
                <style>
                    .spin { animation: naboo-spin 1s linear infinite; }
                    @keyframes naboo-spin { from { transform: translateY(-50%) rotate(0deg); } to { transform: translateY(-50%) rotate(360deg); } }
                    #naboo-archive-filter-input:focus { border-color: #2563eb; }
                </style>
                <?php endif; ?>
            </header>
            <?php endif; ?>

            <div id="naboo-archive-results">
                <?php if ( have_posts() ) : ?>
                    <div class="naboo-grid-loop">
                        <?php
                        $card_index = 0;
                        while ( have_posts() ) :
                            the_post();
                            include plugin_dir_path( __FILE__ ) . '../includes/public/partials/card-archive.php';
                            $card_index++;
                        endwhile;
                        ?>
                    </div>

                    <?php
                    the_posts_pagination( array(
                        'mid_size'  => 2,
                        'prev_text' => sprintf( '%s <span class="nav-prev-text">%s</span>', is_rtl() ? '→' : '←', __( 'Previous', 'naboodatabase' ) ),
                        'next_text' => sprintf( '<span class="nav-next-text">%s</span> %s', __( 'Next', 'naboodatabase' ), is_rtl() ? '←' : '→' ),
                    ) );
                    ?>
                <?php else : ?>
                    <div class="naboo-no-results">
                        <p><?php esc_html_e( 'No scales found in this category.', 'naboodatabase' ); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

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
