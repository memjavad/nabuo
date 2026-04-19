<?php

namespace ArabPsychology\NabooDatabase\Public;

use WP_REST_Request;
use WP_REST_Response;
use WP_Query;

class API {

    private $namespace = 'naboodatabase/v1';

    public function register_routes() {
        register_rest_route( $this->namespace, '/scales', array(
            'methods'  => 'GET',
            'callback' => array( $this, 'get_scales' ),
            'permission_callback' => '__return_true',
            'args' => array(
                'posts_per_page' => array(
                    'default'           => 100,
                    'sanitize_callback' => 'absint',
                ),
                'page' => array(
                    'default'           => 1,
                    'sanitize_callback' => 'absint',
                ),
                'search' => array(
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'category' => array(
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ) );

        register_rest_route( $this->namespace, '/scales/(?P<id>\d+)', array(
            'methods'  => 'GET',
            'callback' => array( $this, 'get_scale_by_id' ),
            'permission_callback' => '__return_true',
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param, $request, $key) {
                        return is_numeric($param);
                    }
                ),
            ),
        ) );

        register_rest_route( $this->namespace, '/scales/(?P<id>\d+)/sync-status', array(
            'methods'  => 'POST',
            'callback' => array( $this, 'update_sync_status' ),
            'permission_callback' => array( $this, 'check_sync_permission' ),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param, $request, $key) {
                        return is_numeric($param);
                    }
                ),
                'synced' => array(
                    'required' => true,
                    'type'     => 'boolean',
                ),
            ),
        ) );

        // Add filter to allow CORS for the extension
        add_filter( 'rest_pre_serve_request', array( $this, 'add_cors_headers' ), 10, 4 );
    }

    /**
     * Add stricter CORS headers to the REST response.
     */
    public function add_cors_headers( $served, $result, $request, $server ) {
        if ( strpos( ltrim( $request->get_route(), '/' ), $this->namespace ) === 0 ) {
            $origin = get_http_origin();
            if ( $origin ) {
                if ( preg_match( '/^chrome-extension:\/\//', $origin ) || strpos( $origin, 'https://grokipedia.com' ) === 0 || strpos( $origin, 'https://www.grokipedia.com' ) === 0 ) {
                    header( 'Access-Control-Allow-Origin: ' . esc_url_raw( $origin ) );
                    header( 'Access-Control-Allow-Methods: GET, POST, OPTIONS' );
                    header( 'Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce, X-Naboo-Sync-Key' );
                    header( 'Access-Control-Allow-Credentials: true' );
                }
            }
        }
        return $served;
    }

    /**
     * Permission callback for syncing scale status to Grokipedia.
     */
    public function check_sync_permission( WP_REST_Request $request ) {
        if ( current_user_can( 'manage_options' ) ) {
            return true;
        }

        $configured_key = get_option( 'naboo_grokipedia_sync_key' );
        $provided_key   = $request->get_header( 'x_naboo_sync_key' );

        if ( ! empty( $configured_key ) && ! empty( $provided_key ) && hash_equals( $configured_key, $provided_key ) ) {
            return true;
        }

        return new \WP_Error( 'rest_forbidden', __( 'Unauthorized to update sync status.', 'naboodatabase' ), array( 'status' => 401 ) );
    }

    public function get_scales( WP_REST_Request $request ) {
        $paged = $request->get_param( 'page' );
        $paged = isset( $paged ) ? (int) $paged : 1;

        $search = $request->get_param( 'search' );
        $category = $request->get_param( 'category' );

        $posts_per_page = $request->get_param( 'posts_per_page' );
        $posts_per_page = ! empty( $posts_per_page ) ? min( absint( $posts_per_page ), 100 ) : 100;

        $args = array(
            'post_type'      => 'psych_scale',
            'post_status'    => 'publish',
            'posts_per_page' => $posts_per_page,
            'paged'          => $paged,
            'no_found_rows'  => true, // Optimizes query performance
        );

        if ( ! empty( $search ) ) {
            $args['s'] = sanitize_text_field( $search );
        }

        if ( ! empty( $category ) ) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'scale_category',
                    'field'    => 'slug',
                    'terms'    => sanitize_text_field( $category ),
                ),
            );
        }

        $query = new WP_Query( $args );
        $scales = array();

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $scales[] = $this->prepare_scale_for_response( get_post() );
            }
        }

        return new WP_REST_Response( $scales, 200 );
    }

    public function get_scale_by_id( WP_REST_Request $request ) {
        $id = (int) $request['id'];
        $post = get_post( $id );

        if ( ! $post || $post->post_type !== 'psych_scale' ) {
            return new WP_REST_Response( array( 'message' => 'Scale not found' ), 404 );
        }

        $data = $this->prepare_scale_for_response( $post );
        return new WP_REST_Response( $data, 200 );
    }

    public function update_sync_status( WP_REST_Request $request ) {
        $id = (int) $request['id'];
        $synced = (bool) $request['synced'];

        $post = get_post( $id );
        if ( ! $post || $post->post_type !== 'psych_scale' ) {
            return new WP_REST_Response( array( 'message' => 'Scale not found' ), 404 );
        }

        update_post_meta( $id, '_naboo_synced_grokipedia', $synced ? '1' : '0' );

        // Log the event
        \ArabPsychology\NabooDatabase\Core\Installer::log_sync_submission( $id, $synced ? 'success' : 'removed', 'Updated via Chrome Extension API' );

        return new WP_REST_Response( array( 'success' => true, 'synced' => $synced ), 200 );
    }

    private function prepare_scale_for_response( $post ) {
        $terms = get_the_terms( $post->ID, 'scale_category' );
        $categories = array();
        if ( $terms && ! is_wp_error( $terms ) ) {
            foreach ( $terms as $term ) {
                $categories[] = array(
                    'id'   => $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug,
                );
            }
        }

        return array(
            'id'          => $post->ID,
            'title'       => $post->post_title,
            'content'     => $post->post_content,
            'excerpt'     => get_the_excerpt( $post->ID ),
            'date'        => $post->post_date,
            'categories'  => $categories,
            'meta'        => array(
                'items'       => get_post_meta( $post->ID, '_naboo_scale_items', true ),
                'reliability' => get_post_meta( $post->ID, '_naboo_scale_reliability', true ),
                'validity'    => get_post_meta( $post->ID, '_naboo_scale_validity', true ),
                'year'        => get_post_meta( $post->ID, '_naboo_scale_year', true ),
                'views'       => get_post_meta( $post->ID, '_naboo_view_count', true ),
                'synced'      => get_post_meta( $post->ID, '_naboo_synced_grokipedia', true ) === '1',
            ),
            'link'        => get_permalink( $post->ID ),
        );
    }

}
