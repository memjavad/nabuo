<?php

namespace ArabPsychology\NabooDatabase\Public;

class Submission {

    public function handle_submission() {
        if ( ! isset( $_POST['naboo_submit_scale_nonce'] ) || ! wp_verify_nonce( $_POST['naboo_submit_scale_nonce'], 'naboo_submit_scale' ) ) {
            return '';
        }

        // Honeypot check
        if ( ! empty( $_POST['naboo_website_url'] ) ) {
            return '<div class="naboo-error">' . __( 'Spam detected. Submission rejected.', 'naboodatabase' ) . '</div>';
        }
        
        // Rate Limiting (5 per hour per IP)
        $ip = $_SERVER['REMOTE_ADDR'];
        $transient_key = 'naboo_submit_ratelimit_' . md5( $ip );
        $attempts = get_transient( $transient_key );
        if ( false === $attempts ) {
            set_transient( $transient_key, 1, HOUR_IN_SECONDS );
        } else {
            if ( $attempts >= 5 ) {
                return '<div class="naboo-error">' . __( 'You have reached the maximum number of submissions allowed per hour. Please try again later.', 'naboodatabase' ) . '</div>';
            }
            set_transient( $transient_key, $attempts + 1, HOUR_IN_SECONDS );
        }

        // Required fields
        $title = isset( $_POST['scale_title'] ) ? sanitize_text_field( $_POST['scale_title'] ) : '';
        $content = isset( $_POST['scale_description'] ) ? wp_kses_post( $_POST['scale_description'] ) : '';
        
        if ( empty( $title ) || empty( $content ) ) {
            return '<div class="naboo-error">' . __( 'Please fill in all required fields.', 'naboodatabase' ) . '</div>';
        }

        // File Upload Handling
        $attachment_id = 0;
        if ( ! empty( $_FILES['scale_file']['name'] ) ) {
            require_once( ABSPATH . 'wp-admin/includes/image.php' );
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
            require_once( ABSPATH . 'wp-admin/includes/media.php' );

            $attachment_id = media_handle_upload( 'scale_file', 0 );

            if ( is_wp_error( $attachment_id ) ) {
                return '<div class="naboo-error">' . __( 'Error uploading file: ', 'naboodatabase' ) . $attachment_id->get_error_message() . '</div>';
            }
        }

        $post_data = array(
            'post_title'    => $title,
            'post_content'  => $content,
            'post_status'   => 'pending',
            'post_type'     => 'psych_scale',
        );

        $post_id = wp_insert_post( $post_data );

        if ( is_wp_error( $post_id ) ) {
            return '<div class="naboo-error">' . __( 'Error submitting scale.', 'naboodatabase' ) . '</div>';
        }

        // Save Meta
        $fields = array(
            'scale_items'       => '_naboo_scale_items',
            'scale_reliability' => '_naboo_scale_reliability',
            'scale_validity'    => '_naboo_scale_validity',
            'scale_year'        => '_naboo_scale_year',
            'scale_language'    => '_naboo_scale_language',
            'scale_population'  => '_naboo_scale_population'
        );

        foreach ( $fields as $post_key => $meta_key ) {
            if ( isset( $_POST[ $post_key ] ) ) {
                update_post_meta( $post_id, $meta_key, sanitize_text_field( $_POST[ $post_key ] ) );
            }
        }

        // Save Taxonomy
        if ( isset( $_POST['scale_category'] ) && intval( $_POST['scale_category'] ) > 0 ) {
            wp_set_object_terms( $post_id, intval( $_POST['scale_category'] ), 'scale_category' );
        }

        // Attach file if uploaded
        if ( $attachment_id ) {
            update_post_meta( $post_id, '_naboo_scale_file', $attachment_id );
        }

        return '<div class="naboo-success">' . __( 'Scale submitted successfully for review!', 'naboodatabase' ) . '</div>';
    }
}
