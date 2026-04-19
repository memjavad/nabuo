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
                'post_type'      => 'psych_scale',
                'posts_per_page' => -1,
                'post_status'    => 'any',
            );

            $query = new \WP_Query( $args );

            if ( $query->have_posts() ) {
                while ( $query->have_posts() ) {
                    $query->the_post();
                    
                    $pid = get_the_ID();
                    $terms = get_the_terms( $pid, 'scale_category' );
                    $cats = array();
                    if ( $terms && ! is_wp_error( $terms ) ) {
                        foreach ( $terms as $t ) $cats[] = $t->name;
                    }

                    $year = get_post_meta( $pid, '_naboo_scale_year', true );
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
