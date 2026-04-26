<?php

namespace ArabPsychology\NabooDatabase\Admin;

class Dashboard {

    public function __construct() {
        add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widgets' ) );
        add_action( 'admin_init', array( $this, 'handle_csv_export' ) );
    }

    public function add_dashboard_widgets() {
        wp_add_dashboard_widget(
            'naboo_database_summary',
            __( 'Naboo Database Overview', 'naboodatabase' ),
            array( $this, 'render_dashboard_widget' )
        );
    }

    public function render_dashboard_widget() {
        $count_posts = wp_count_posts( 'psych_scale' );
        $published = $count_posts->publish;
        $pending = $count_posts->pending;

        echo '<div class="main">';
        echo '<p><span style="font-size: 2em; font-weight: bold;">' . esc_html( $published ) . '</span> ' . __( 'Published Scales', 'naboodatabase' ) . '</p>';
        echo '<p><span style="color: #d63638; font-weight: bold;">' . esc_html( $pending ) . '</span> ' . __( 'Pending Review', 'naboodatabase' ) . '</p>';
        echo '</div>';
        
        echo '<hr>';
        
        echo '<p><a href="' . admin_url( 'edit.php?post_type=psych_scale' ) . '" class="button button-primary">' . __( 'Manage Scales', 'naboodatabase' ) . '</a> ';
        echo '<a href="' . wp_nonce_url( admin_url( 'admin.php?action=naboo_export_scales' ), 'naboo_export_csv' ) . '" class="button">' . __( 'Export to CSV', 'naboodatabase' ) . '</a></p>';
    }

    public function handle_csv_export() {
        if ( isset( $_GET['action'] ) && $_GET['action'] == 'naboo_export_scales' ) {
            // Check nonce
            check_admin_referer( 'naboo_export_csv' );

            if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }

            // Headers
            header( 'Content-Type: text/csv; charset=utf-8' );
            header( 'Content-Disposition: attachment; filename=naboo-scales-' . date('Y-m-d') . '.csv' );

            $output = fopen( 'php://output', 'w' );

            // CSV Column Headers
            fputcsv( $output, array( 'ID', 'Title', 'Category', 'Year', 'Author', 'Status' ) );

            $args = array(
                'post_type'              => 'psych_scale',
                'posts_per_page'         => -1,
                'post_status'            => 'any',
                'no_found_rows'          => true,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
            );

            $query = new \WP_Query( $args );

            if ( $query->have_posts() ) {
                global $wpdb;

                // OPTIMIZATION: Instead of querying post meta and terms inside the while loop (N+1 query problem),
                // we collect all post IDs and execute a single batch query for meta and terms, grouping the results by post ID.
                // This reduces database round-trips from O(N) to O(1) and significantly speeds up the export process.
                $post_ids = wp_list_pluck( $query->posts, 'ID' );

                $meta_map = array();
                $term_map = array();

                if ( ! empty( $post_ids ) ) {
                    $placeholders = implode( ',', array_fill( 0, count( $post_ids ), '%d' ) );

                    $meta_query = $wpdb->prepare( "
                        SELECT post_id, meta_value
                        FROM {$wpdb->postmeta}
                        WHERE meta_key = '_naboo_scale_year' AND post_id IN ($placeholders)
                    ", $post_ids );

                    $meta_results = $wpdb->get_results( $meta_query );
                    if ( $meta_results ) {
                        foreach ( $meta_results as $m ) {
                            $meta_map[ $m->post_id ] = $m->meta_value;
                        }
                    }

                    $term_query = $wpdb->prepare( "
                        SELECT tr.object_id, t.name
                        FROM {$wpdb->term_relationships} tr
                        INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                        INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
                        WHERE tt.taxonomy = 'scale_category' AND tr.object_id IN ($placeholders)
                    ", $post_ids );

                    $term_results = $wpdb->get_results( $term_query );
                    if ( $term_results ) {
                        foreach ( $term_results as $tr ) {
                            if ( ! isset( $term_map[ $tr->object_id ] ) ) {
                                $term_map[ $tr->object_id ] = array();
                            }
                            $term_map[ $tr->object_id ][] = $tr->name;
                        }
                    }
                }

                while ( $query->have_posts() ) {
                    $query->the_post();
                    
                    $pid = get_the_ID();
                    $cats = isset( $term_map[ $pid ] ) ? $term_map[ $pid ] : array();
                    $year = isset( $meta_map[ $pid ] ) ? $meta_map[ $pid ] : '';
                    $status = get_post_status( $pid );

                    fputcsv( $output, array(
                        $pid,
                        get_the_title(),
                        implode( ', ', $cats ),
                        $year,
                        get_the_author(),
                        $status
                    ) );
                }
            }
            
            fclose( $output );
            exit;
        }
    }
}
