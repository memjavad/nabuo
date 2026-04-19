<?php
/**
 * Archive Card Template
 * Used in archive loops and AJAX filtering.
 *
 * @var int    $id         The post ID.
 * @var int    $card_index The index in the loop (for staggering animations).
 * @var string $animate_class Animation class if enabled.
 */

$id = get_the_ID();
$card_index = isset( $args['index'] ) ? $args['index'] : 0;
$animate_class = isset( $args['animate_class'] ) ? $args['animate_class'] : '';
?>
<article id="post-<?php echo $id; ?>" <?php post_class( 'naboo-card ' . $animate_class ); ?> style="--naboo-stagger: <?php echo intval( $card_index ); ?>;">
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
                $years = get_the_terms($id, 'scale_year');
                if ($years && !is_wp_error($years)) {
                    echo esc_html($years[0]->name);
                } else {
                    echo get_the_date(); 
                }
                ?>
            </span>
            <span>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: text-bottom;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                <?php 
                $authors = get_the_terms($id, 'scale_author');
                if ($authors && !is_wp_error($authors)) {
                    $author_names = wp_list_pluck($authors, 'name');
                    echo esc_html(implode(', ', $author_names));
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
