<?php
/**
 * Search Results Template
 * Shows a results header bar and the result cards.
 */
$found = $query->found_posts;
$paged = max( 1, get_query_var( 'paged' ) );
$per_page = $query->query_vars['posts_per_page'];
$start = ( $paged - 1 ) * $per_page + 1;
$end   = min( $paged * $per_page, $found );
?>
<div class="naboo-results-section">
    <!-- Results Header Bar -->
    <div class="naboo-results-header">
        <div class="naboo-results-count">
            <?php
            printf(
                /* translators: %1$s: start, %2$s: end, %3$s: total */
                __( 'Showing <strong>%1$s–%2$s</strong> of <strong>%3$s</strong> scales', 'naboodatabase' ),
                number_format_i18n( $start ),
                number_format_i18n( $end ),
                number_format_i18n( $found )
            );
            ?>
        </div>
        <div class="naboo-results-view-toggle">
            <button type="button" class="naboo-view-btn active" data-view="list" title="<?php _e( 'List View', 'naboodatabase' ); ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
            </button>
            <button type="button" class="naboo-view-btn" data-view="grid" title="<?php _e( 'Grid View', 'naboodatabase' ); ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
            </button>
        </div>
    </div>

    <!-- Results List -->
    <div class="naboo-results-list" id="naboo-results-list">
        <?php while ( $query->have_posts() ) : $query->the_post(); ?>
            <?php include plugin_dir_path( __FILE__ ) . 'content-scale.php'; ?>
        <?php endwhile; ?>
    </div>

    <!-- Pagination -->
    <?php if ( $query->max_num_pages > 1 ) : ?>
    <div class="naboo-pagination">
        <?php
        echo paginate_links( array(
            'base'      => str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) ),
            'format'    => '?paged=%#%',
            'current'   => $paged,
            'total'     => $query->max_num_pages,
            'prev_text' => '← ' . __( 'Previous', 'naboodatabase' ),
            'next_text' => __( 'Next', 'naboodatabase' ) . ' →',
        ) );
        ?>
    </div>
    <?php endif; ?>
</div>
