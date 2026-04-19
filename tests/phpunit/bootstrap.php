<?php
// PHPUnit bootstrap for Naboo Database
define( 'ABSPATH', dirname( __DIR__ ) . '/../' );

// Mock WordPress functions
function wp_insert_post( $post ) {
    if ( empty( $post['post_title'] ) ) return 0;
    return rand( 1, 1000 );
}

function sanitize_text_field( $str ) { return $str; }
function wp_kses_post( $str ) { return $str; }
function sanitize_textarea_field( $str ) { return $str; }
function get_current_user_id() { return 1; }
function update_post_meta( $id, $key, $val ) { return true; }
function get_term_by( $field, $value, $taxonomy ) {
    $term = new stdClass();
    $term->term_id = rand( 1, 100 );
    return $term;
}
function wp_set_post_terms( $id, $term, $taxonomy ) { return true; }

require_once __DIR__ . '/../../includes/admin/import/class-import-processor.php';
