<?php
if ( function_exists( 'wpa_get_header' ) ) {
    wpa_get_header();
} else {
    include 'header.php';
}
?>

<div class="naboo-container naboo-404-container">
    <main id="primary" class="site-main">

        <section class="error-404 not-found">
            
            <div class="naboo-404-visual">
                <div class="naboo-404-glitch" data-text="404">404</div>
            </div>

            <header class="page-header">
                <h1 class="page-title"><?php esc_html_e( 'Scale Not Found', 'naboodatabase' ); ?></h1>
            </header>

            <div class="page-content">
                <p><?php esc_html_e( 'The psychological scale, tool, or document you are looking for has been moved, deleted, or does not exist.', 'naboodatabase' ); ?></p>
                <p><?php esc_html_e( 'Try using the database search to find what you need.', 'naboodatabase' ); ?></p>

                <div class="naboo-404-search">
                    <form role="search" method="get" class="naboo-search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                        <div class="naboo-search-wrapper">
                            <span class="naboo-search-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            </span>
                            <input type="search" class="naboo-search-field" placeholder="<?php echo esc_attr_x( 'Search scales, authors, or constructs...', 'placeholder', 'naboodatabase' ); ?>" value="<?php echo get_search_query(); ?>" name="s" autocomplete="off" />
                            <input type="hidden" name="post_type" value="psych_scale" />
                            <button type="submit" class="naboo-search-submit naboo-btn naboo-btn-primary">
                                <?php echo esc_attr_x( 'Search', 'submit button', 'naboodatabase' ); ?>
                            </button>
                        </div>
                    </form>
                </div>

                <div class="naboo-404-actions">
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="naboo-btn naboo-btn-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                        <?php esc_html_e( 'Back to Database', 'naboodatabase' ); ?>
                    </a>
                    <a href="<?php echo esc_url( get_post_type_archive_link( 'psych_scale' ) ); ?>" class="naboo-btn naboo-btn-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><line x1="3" x2="21" y1="9" y2="9"/><path d="m9 16 3-3 3 3"/></svg>
                        <?php esc_html_e( 'Browse Categories', 'naboodatabase' ); ?>
                    </a>
                </div>

                <?php 
                // Display some popular scales as suggestions
                $args = array(
                    'post_type'      => 'psych_scale',
                    'posts_per_page' => 3,
                    'meta_key'       => '_naboo_view_count',
                    'orderby'        => 'meta_value_num',
                    'order'          => 'DESC',
                );
                
                $popular = new WP_Query( $args );
                
                if ( $popular->have_posts() ) : ?>
                    <div class="naboo-404-suggestions">
                        <h3><?php esc_html_e( 'Most Popular Scales', 'naboodatabase' ); ?></h3>
                        <div class="naboo-cards-grid">
                            <?php 
                            while ( $popular->have_posts() ) : $popular->the_post();
                                $scale_items = get_post_meta( get_the_ID(), '_naboo_scale_items', true );
                                $scale_year = get_post_meta( get_the_ID(), '_naboo_scale_year', true );
                                ?>
                                <article class="naboo-card">
                                    <h4 class="naboo-card-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
                                    <div class="naboo-card-meta">
                                        <?php if ( $scale_items ) echo '<span><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg> ' . esc_html( $scale_items ) . ' Items</span>'; ?>
                                        <?php if ( $scale_year ) echo '<span><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> ' . esc_html( $scale_year ) . '</span>'; ?>
                                    </div>
                                    <?php
                                    $abstract = get_post_meta( get_the_ID(), '_naboo_scale_abstract', true );
                                    if ( ! empty( $abstract ) ) {
                                        echo '<p class="naboo-card-excerpt">' . esc_html( wp_trim_words( wp_strip_all_tags( $abstract ), 12 ) ) . '</p>';
                                    }
                                    ?>
                                </article>
                            <?php endwhile; wp_reset_postdata(); ?>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </section>

    </main>
</div>

<style>
/* 404 Specific Stunning Styles */
.naboo-404-container {
    padding: 100px 20px 150px;
    text-align: center;
    max-width: 900px !important;
}

.naboo-404-visual {
    margin-bottom: 40px;
}

.naboo-404-glitch {
    font-size: 15vw;
    font-weight: 800;
    line-height: 1;
    color: var(--naboo-primary, #2b6cb0);
    position: relative;
    display: inline-block;
    letter-spacing: -5px;
    z-index: 1;
}

.naboo-404-glitch::before,
.naboo-404-glitch::after {
    content: attr(data-text);
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: -1;
}

.naboo-404-glitch::before {
    color: var(--naboo-accent, #ed8936);
    animation: glitch-anim-1 2.5s infinite linear alternate-reverse;
}

.naboo-404-glitch::after {
    color: rgba(43, 108, 176, 0.5); /* fallback hex */
    animation: glitch-anim-2 3s infinite linear alternate-reverse;
}

@keyframes glitch-anim-1 {
    0% { clip-path: inset(20% 0 80% 0); transform: translate(-2px, 1px); }
    20% { clip-path: inset(60% 0 10% 0); transform: translate(2px, -1px); }
    40% { clip-path: inset(40% 0 50% 0); transform: translate(-2px, 2px); }
    60% { clip-path: inset(80% 0 5% 0); transform: translate(2px, -2px); }
    80% { clip-path: inset(10% 0 70% 0); transform: translate(-1px, 1px); }
    100% { clip-path: inset(30% 0 50% 0); transform: translate(1px, -1px); }
}

@keyframes glitch-anim-2 {
    0% { clip-path: inset(10% 0 60% 0); transform: translate(2px, -1px); }
    20% { clip-path: inset(30% 0 20% 0); transform: translate(-2px, 1px); }
    40% { clip-path: inset(70% 0 10% 0); transform: translate(2px, -2px); }
    60% { clip-path: inset(20% 0 50% 0); transform: translate(-2px, 2px); }
    80% { clip-path: inset(50% 0 30% 0); transform: translate(1px, -1px); }
    100% { clip-path: inset(5% 0 80% 0); transform: translate(-1px, 1px); }
}

.error-404 .page-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 20px;
    color: var(--naboo-text-primary, #1a202c);
}

.error-404 .page-content > p {
    font-size: 1.15rem;
    color: var(--naboo-text-secondary, #4a5568);
    margin-bottom: 30px;
    line-height: 1.6;
}

.naboo-404-search {
    max-width: 600px;
    margin: 40px auto;
    background: var(--naboo-surface, #ffffff);
    padding: 10px;
    border-radius: 50px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.05);
    border: 1px solid var(--naboo-border, #e2e8f0);
}

.naboo-404-search .naboo-search-wrapper {
    display: flex;
    align-items: center;
}

.naboo-404-search .naboo-search-icon {
    padding: 0 15px 0 20px;
    color: var(--naboo-text-secondary, #a0aec0);
    display: flex;
}

.naboo-404-search .naboo-search-field {
    flex-grow: 1;
    border: none !important;
    background: transparent !important;
    padding: 15px 10px;
    font-size: 1.1rem;
    outline: none !important;
    box-shadow: none !important;
    color: var(--naboo-text-primary, #1a202c);
}

.naboo-404-search .naboo-search-submit {
    border-radius: 40px !important;
    padding: 12px 30px !important;
    font-size: 1rem !important;
}

.naboo-404-actions {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-bottom: 60px;
}

.naboo-404-actions .naboo-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.2s ease;
    text-decoration: none;
}

.naboo-404-actions .naboo-btn-secondary {
    background: var(--naboo-surface, #f7fafc);
    color: var(--naboo-text-primary, #2d3748);
    border: 1px solid var(--naboo-border, #e2e8f0);
}

.naboo-404-actions .naboo-btn-secondary:hover {
    background: var(--naboo-border, #e2e8f0);
    transform: translateY(-2px);
}

.naboo-404-suggestions {
    margin-top: 80px;
    text-align: left;
    border-top: 1px solid var(--naboo-border, #e2e8f0);
    padding-top: 40px;
}

.naboo-404-suggestions h3 {
    margin-bottom: 30px;
    font-size: 1.5rem;
    text-align: center;
    color: var(--naboo-text-primary);
}

.naboo-404-suggestions .naboo-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
}

.naboo-404-suggestions .naboo-card {
    background: var(--naboo-surface, #ffffff);
    border: 1px solid var(--naboo-border, #e2e8f0);
    border-radius: 12px;
    padding: 20px;
    transition: transform 0.2s, box-shadow 0.2s;
    text-decoration: none;
    display: block;
}

.naboo-404-suggestions .naboo-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 15px rgba(0,0,0,0.05);
}

.naboo-404-suggestions .naboo-card-title {
    font-size: 1.2rem;
    margin-bottom: 10px;
}

.naboo-404-suggestions .naboo-card-title a {
    text-decoration: none;
    color: var(--naboo-primary, #2b6cb0);
}

.naboo-404-suggestions .naboo-card-meta {
    font-size: 0.85rem;
    color: var(--naboo-text-secondary, #718096);
    margin-bottom: 15px;
    display: flex;
    gap: 15px;
}

.naboo-404-suggestions .naboo-card-meta span {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.naboo-404-suggestions .naboo-card-excerpt {
    font-size: 0.95rem;
    color: var(--naboo-text-secondary, #4a5568);
    margin: 0;
}

@media (max-width: 768px) {
    .naboo-404-glitch { font-size: 25vw; }
    .naboo-404-actions { flex-direction: column; }
    .naboo-404-search .naboo-search-wrapper { flex-direction: column; }
    .naboo-404-search .naboo-search-icon { display: none; }
    .naboo-404-search .naboo-search-field { text-align: center; margin-bottom: 10px; }
    .naboo-404-search .naboo-search-submit { width: 100%; }
}
</style>

<?php
if ( function_exists( 'wpa_get_footer' ) ) {
    wpa_get_footer();
} else {
    include 'footer.php';
}
?>
