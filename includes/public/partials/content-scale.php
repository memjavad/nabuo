<?php
/**
 * Scale Result Card Template
 * Used in search results to display each scale as a rich card.
 */
$id          = get_the_ID();
$year        = get_post_meta( $id, '_naboo_scale_year', true );
$items       = get_post_meta( $id, '_naboo_scale_items', true );
$language    = get_post_meta( $id, '_naboo_scale_language', true );
$population  = get_post_meta( $id, '_naboo_scale_population', true );
$reliability = get_post_meta( $id, '_naboo_scale_reliability', true );
$construct   = get_post_meta( $id, '_naboo_scale_construct', true );
$test_type   = get_post_meta( $id, '_naboo_scale_test_type', true );
$views       = get_post_meta( $id, '_naboo_view_count', true );
$categories  = get_the_terms( $id, 'scale_category' );
$authors     = get_the_terms( $id, 'scale_author' );
$age_groups  = get_the_terms( $id, 'scale_age_group' );
$file_id     = get_post_meta( $id, '_naboo_scale_file', true );
?>
<article id="post-<?php echo $id; ?>" <?php post_class( 'naboo-scale-card' ); ?>>
    
    <!-- Card Left: Accent Strip -->
    <div class="naboo-card-accent"></div>

    <!-- Card Main Content -->
    <div class="naboo-card-body">

        <!-- Top Row: Title + Badges -->
        <div class="naboo-card-top">
            <h3 class="naboo-card-title">
                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
            </h3>
            <div class="naboo-card-badges">
                <?php if ( $categories && ! is_wp_error( $categories ) ) : ?>
                    <?php foreach ( $categories as $cat ) : ?>
                        <span class="naboo-badge naboo-badge-category"><?php echo esc_html( $cat->name ); ?></span>
                    <?php endforeach; ?>
                <?php endif; ?>
                <?php if ( $file_id ) : ?>
                    <span class="naboo-badge naboo-badge-file" title="<?php _e( 'Document available', 'naboodatabase' ); ?>">📄 PDF</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Construct / Author Line -->
        <div class="naboo-card-subtitle">
            <?php if ( $construct ) : ?>
                <span class="naboo-card-construct"><?php echo esc_html( $construct ); ?></span>
            <?php endif; ?>
            <?php if ( $authors && ! is_wp_error( $authors ) ) : ?>
                <span class="naboo-card-authors">
                    <?php echo esc_html( implode( ', ', wp_list_pluck( $authors, 'name' ) ) ); ?>
                    <?php if ( $year ) : ?>
                        <span class="naboo-card-year">(<?php echo esc_html( $year ); ?>)</span>
                    <?php endif; ?>
                </span>
            <?php elseif ( $year ) : ?>
                <span class="naboo-card-year">(<?php echo esc_html( $year ); ?>)</span>
            <?php endif; ?>
        </div>

        <!-- Excerpt -->
        <div class="naboo-card-excerpt">
            <?php echo wp_trim_words( get_the_excerpt(), 25, '...' ); ?>
        </div>

        <!-- Meta Chips Row -->
        <div class="naboo-card-meta-chips">
            <?php if ( $items ) : ?>
                <span class="naboo-chip">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
                    <?php printf( __( '%s items', 'naboodatabase' ), intval( $items ) ); ?>
                </span>
            <?php endif; ?>
            <?php if ( $language ) : ?>
                <span class="naboo-chip">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                    <?php echo esc_html( $language ); ?>
                </span>
            <?php endif; ?>
            <?php if ( $population ) : ?>
                <span class="naboo-chip">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    <?php echo esc_html( wp_trim_words( $population, 4 ) ); ?>
                </span>
            <?php endif; ?>
            <?php if ( $age_groups && ! is_wp_error( $age_groups ) ) : ?>
                <span class="naboo-chip">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><circle cx="19" cy="8" r="3"/></svg>
                    <?php echo esc_html( $age_groups[0]->name ); ?>
                </span>
            <?php endif; ?>
            <?php if ( $test_type ) : ?>
                <span class="naboo-chip">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    <?php echo esc_html( ucfirst( $test_type ) ); ?>
                </span>
            <?php endif; ?>
        </div>

        <!-- Footer: Actions + Views -->
        <div class="naboo-card-footer">
            <a href="<?php the_permalink(); ?>" class="naboo-card-view-btn">
                <?php _e( 'View Details', 'naboodatabase' ); ?>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </a>
            <?php if ( $views ) : ?>
                <span class="naboo-card-views">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    <?php echo number_format_i18n( intval( $views ) ); ?>
                </span>
            <?php endif; ?>
        </div>
    </div>
</article>
