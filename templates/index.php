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
                <h1 class="page-title"><?php single_post_title(); ?></h1>
            </header>
            <?php endif; ?>

            <div class="naboo-grid-loop">
                <?php
                $card_index = 0;
                $enable_animations = isset( $options['scroll_animations'] ) ? $options['scroll_animations'] : 1;
                
                while ( have_posts() ) :
                    the_post();
                    $animate_class = $enable_animations ? 'naboo-animate' : '';
                    ?>
                    <article id="post-<?php the_ID(); ?>" <?php post_class( 'naboo-card ' . $animate_class ); ?> style="--naboo-stagger: <?php echo intval( $card_index ); ?>;">
                        <?php if ( has_post_thumbnail() ) : ?>
                            <div class="naboo-card-image">
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_post_thumbnail( 'medium_large' ); ?>
                                </a>
                                <div class="naboo-card-overlay"></div>
                                <?php
                                $categories = get_the_category();
                                if ( ! empty( $categories ) ) :
                                    ?>
                                    <span class="naboo-card-badge"><?php echo esc_html( $categories[0]->name ); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="naboo-card-content">
                            <header class="entry-header">
                                <?php the_title( '<h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h2>' ); ?>
                            </header>

                            <div class="naboo-card-meta">
                                <span>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: text-bottom;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                    <?php 
                                    if ( get_post_type() === 'psych_scale' ) {
                                        $years = get_the_terms( get_the_ID(), 'scale_year' );
                                        if ( $years && ! is_wp_error( $years ) ) {
                                            echo esc_html( $years[0]->name );
                                        } else {
                                            echo get_the_date();
                                        }
                                    } else {
                                        echo get_the_date();
                                    }
                                    ?>
                                </span>
                                <span>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: text-bottom;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                    <?php 
                                    if ( get_post_type() === 'psych_scale' ) {
                                        $authors = get_the_terms( get_the_ID(), 'scale_author' );
                                        if ( $authors && ! is_wp_error( $authors ) ) {
                                            $author_names = wp_list_pluck( $authors, 'name' );
                                            echo esc_html( implode( ', ', $author_names ) );
                                        } else {
                                            the_author();
                                        }
                                    } else {
                                        the_author();
                                    }
                                    ?>
                                </span>
                            </div>

                            <div class="entry-excerpt">
                                <?php the_excerpt(); ?>
                            </div>

                        </div>
                    </article>
                    <?php
                    $card_index++;
                endwhile;
                ?>
            </div>

            <?php
            the_posts_pagination( array(
                'prev_text' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>',
                'next_text' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>',
            ) );

        else :

            echo '<div class="naboo-text-center naboo-mt-4"><p>' . __( 'Nothing found here.', 'naboodatabase' ) . '</p></div>';

        endif;
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
