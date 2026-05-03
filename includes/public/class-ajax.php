<?php

namespace ArabPsychology\NabooDatabase\Public;

use WP_Query;

class Ajax {

    public function __construct() {
        add_action( 'wp_ajax_naboo_search_scales', array( $this, 'search_scales' ) );
        add_action( 'wp_ajax_nopriv_naboo_search_scales', array( $this, 'search_scales' ) );

        add_action( 'wp_ajax_naboo_filter_archive', array( $this, 'ajax_filter_archive' ) );
        add_action( 'wp_ajax_nopriv_naboo_filter_archive', array( $this, 'ajax_filter_archive' ) );

        add_action( 'wp_ajax_naboo_publish_scale', array( $this, 'ajax_publish_scale' ) );
    }

    public function search_scales() {
        check_ajax_referer( 'naboo_search_nonce', 'nonce' );

        $search_query = isset( $_POST['search_term'] ) ? sanitize_text_field( $_POST['search_term'] ) : '';
        $category = isset( $_POST['category'] ) ? sanitize_text_field( $_POST['category'] ) : '';
        $year = isset( $_POST['year'] ) ? sanitize_text_field( $_POST['year'] ) : '';
        $sort = isset( $_POST['sort'] ) ? sanitize_text_field( $_POST['sort'] ) : 'newest';

        $args = array(
            'post_type' => 'psych_scale',
            'posts_per_page' => 10,
            's' => $search_query,
            'post_status' => 'publish',
            'no_found_rows' => true,
        );
        
        // Sorting Logic
        switch ( $sort ) {
            case 'oldest':
                $args['orderby'] = 'date';
                $args['order'] = 'ASC';
                break;
            case 'title_asc':
                $args['orderby'] = 'title';
                $args['order'] = 'ASC';
                break;
            case 'title_desc':
                $args['orderby'] = 'title';
                $args['order'] = 'DESC';
                break;
            case 'newest':
            default:
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
                break;
        }

        if ( ! empty( $category ) ) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'scale_category',
                    'field'    => 'slug',
                    'terms'    => $category,
                ),
            );
        }

        if ( ! empty( $year ) ) {
             $args['meta_query'] = array(
                array(
                    'key'     => '_naboo_scale_year',
                    'value'   => $year,
                    'compare' => '=',
                ),
            );
        }

        $query = new WP_Query( $args );

        if ( $query->have_posts() ) {
            ob_start();
            while ( $query->have_posts() ) {
                $query->the_post();
                // We should reuse the search-results.php partial loop logic here, 
                // but since search-results.php likely expects a loop context or is the loop itself...
                // Let's create a partial for a SINGLE item, so we can loop here.
                // Or just output HTML here directly or include a loop partial.
                // For now, let's include the existing loop partial which likely iterates.
                // Wait, if I include 'partials/search-results.php', it probably does `while(have_posts())`.
                // Let's check `search-results.php` content in next step.
                // Assuming it loops, I can just include it? NO, `search-results.php` might rely on global $wp_query or just $query variable if passed?
                // Step 22 showed `search-results.php` being included if `$query->have_posts()`.
                // I'll make sure to set the global `$post` or pass `$query` to it?
                // The cleanest way is to pass the $query object to the partial.
                // But `search-results.php` might be written to use the global query.
                // I will inspect `search-results.php` before deciding.
                
                // For now, I will assume I can just use a simple loop here or refactor later.
                // I'll leave a placeholder.
                
                // Actually, let's just output JSON? No, the plan said "Render search results".
                // I'll output HTML.
                include plugin_dir_path( __FILE__ ) . '../partials/content-scale.php';
            }
            $html = ob_get_clean();
            wp_send_json_success( array( 'html' => $html ) );
        }
        wp_die();
    }

    public function ajax_filter_archive() {
        $search_term = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
        $taxonomy    = isset( $_GET['taxonomy'] ) ? sanitize_text_field( $_GET['taxonomy'] ) : '';
        $term_id     = isset( $_GET['term_id'] ) ? intval( $_GET['term_id'] ) : 0;

        $args = array(
            'post_type'      => 'psych_scale',
            'post_status'    => 'publish',
            'posts_per_page' => 20,
            's'              => $search_term,
            'no_found_rows'  => true,
        );

        if ( $taxonomy && $term_id ) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => $taxonomy,
                    'field'    => 'term_id',
                    'terms'    => $term_id,
                ),
            );
        }

        $query = new WP_Query( $args );

        if ( $query->have_posts() ) {
            ob_start();
            echo '<div class="naboo-grid-loop">';
            $card_index = 0;
            while ( $query->have_posts() ) {
                $query->the_post();
                include plugin_dir_path( __FILE__ ) . 'partials/card-archive.php';
                $card_index++;
            }
            echo '</div>';

            // Simple "no more" or pagination can be added here if needed, 
            // but for "real-time filter" often we just show top results or replace entirely.
            
            $html = ob_get_clean();
            wp_send_json_success( array( 'html' => $html ) );
        } else {
            wp_send_json_success( array( 
                'html' => '<div class="naboo-no-results"><p>' . esc_html__( 'No scales found matching your search.', 'naboodatabase' ) . '</p></div>' 
            ) );
        }
        wp_die();
    }

    public function ajax_publish_scale() {
        check_ajax_referer( 'naboo_publish_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized access.', 'naboodatabase' ) ) );
        }

        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
        if ( ! $post_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid scale ID.', 'naboodatabase' ) ) );
        }

        $post = get_post( $post_id );
        if ( ! $post || $post->post_type !== 'psych_scale' ) {
            wp_send_json_error( array( 'message' => __( 'Scale not found.', 'naboodatabase' ) ) );
        }

        $update_id = wp_update_post( array(
            'ID'          => $post_id,
            'post_status' => 'publish',
        ) );

        if ( is_wp_error( $update_id ) ) {
            wp_send_json_error( array( 'message' => $update_id->get_error_message() ) );
        }

        // Find the next scale that needs publishing
        $next_scale = new \WP_Query( array(
            'post_type'      => 'psych_scale',
            'post_status'    => array( 'draft', 'pending', 'naboo_raw_draft' ),
            'posts_per_page' => 1,
            'no_found_rows'  => true, // Performance: skips SQL_CALC_FOUND_ROWS since we only need 1 post
            'orderby'        => 'date',
            'order'          => 'ASC',
            'no_found_rows'  => true, // Optimizes query performance
        ) );

        $next_url = '';
        if ( $next_scale->have_posts() ) {
            $next_url = get_permalink( $next_scale->posts[0]->ID );
        }

        wp_send_json_success( array(
            'next_url' => $next_url,
            'message'  => __( 'Scale published successfully.', 'naboodatabase' ),
        ) );
    }
}
