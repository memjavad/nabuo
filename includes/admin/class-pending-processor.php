<?php
/**
 * Pending Scale Processor
 *
 * @package ArabPsychology\NabooDatabase\Admin
 */

namespace ArabPsychology\NabooDatabase\Admin;

use ArabPsychology\NabooDatabase\Core\AI_Extractor;

/**
 * Class Pending_Processor
 */
class Pending_Processor {

    /**
     * Register the administration menu.
     */
    public function add_plugin_admin_menu() {
        add_submenu_page(
            'naboo-dashboard',
            __( 'Pending Processor', 'naboodatabase' ),
            __( '⏳ Pending Processor', 'naboodatabase' ),
            'manage_options',
            'naboo-pending-processor',
            array( $this, 'display_plugin_admin_page' ),
            12
        );
    }

    /**
     * Enqueue admin scripts.
     *
     * @param string $hook The current admin page hook.
     */
    public function enqueue_scripts( $hook ) {
        if ( strpos( $hook, 'naboo-pending-processor' ) === false ) {
            return;
        }

        wp_enqueue_style( 'naboo-admin-css' );
        wp_enqueue_script(
            'naboo-pending-processor-js',
            plugin_dir_url( dirname( __DIR__ ) ) . 'includes/admin/js/pending-processor.js',
            array( 'jquery' ),
            NABOODATABASE_VERSION,
            true
        );

        wp_localize_script(
            'naboo-pending-processor-js',
            'NabooPendingProcessor',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'naboo_pending_processor_nonce' ),
            )
        );
    }

    /**
     * Display the admin page.
     */
    public function display_plugin_admin_page() {
        // Get pending/draft scales
        $pending_scales = get_posts( array(
            'post_type'      => 'psych_scale',
            'post_status'    => array('pending', 'draft'),
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ) );
        $count = count( $pending_scales );
        ?>
        <div class="naboo-admin-page">
            <div class="naboo-admin-header">
                <div class="naboo-admin-header-left">
                    <h1 class="naboo-admin-title">
                        <span class="title-icon">⚙️</span>
                        Pending Scale Processor
                    </h1>
                    <p class="naboo-admin-subtitle">Process pending scales, apply AI refinements to missing fields, publish complete ones, and mark incomplete ones for manual review.</p>
                </div>
            </div>

            <div class="naboo-admin-card" style="margin-top:20px;">
                <div class="naboo-admin-card-header">
                    <h3>Processor Details</h3>
                    <span class="naboo-badge naboo-badge-blue"><?php echo esc_html( number_format($count) ); ?> scales waiting</span>
                </div>
                
                <?php if ( $count > 0 ) : ?>
                    <p>There are currently <strong><?php echo esc_html( number_format($count) ); ?></strong> scales in Draft or Pending status. Clicking the button below will begin processing them.</p>

                    <button class="naboo-admin-btn naboo-admin-btn-primary" id="naboo-start-pending-processor">
                        <span class="dashicons dashicons-controls-play" style="line-height:1.2;"></span>
                        Start Processing Pending Scales
                    </button>
                    <button class="naboo-admin-btn naboo-admin-btn-secondary" id="naboo-stop-pending-processor" style="display:none;">
                        <span class="dashicons dashicons-controls-pause" style="line-height:1.2;"></span>
                        Stop Processing
                    </button>

                    <div id="naboo-pending-progress-container" style="display:none; margin-top:20px; background:#f0f0f1; border-radius:4px; overflow:hidden; position:relative; height:24px;">
                        <div id="naboo-pending-progress-bar" style="width:0%; height:100%; background:var(--naboo-primary); transition:width 0.3s; position:absolute; top:0; left:0;"></div>
                        <div id="naboo-pending-progress-text" style="position:absolute; width:100%; text-align:center; color:#fff; font-size:12px; font-weight:bold; line-height:24px; text-shadow:0 1px 2px rgba(0,0,0,0.3);">0%</div>
                    </div>

                    <div style="margin-top:20px;">
                        <h4>Log Output</h4>
                        <div id="naboo-pending-log" class="naboo-admin-log-window"></div>
                    </div>

                    <script>
                        window.nabooPendingScaleIds = <?php echo json_encode( $pending_scales ); ?>;
                    </script>
                <?php else : ?>
                    <div class="naboo-notice info" style="margin-top:20px;">
                        <p>🎉 Excellent! There are no pending scales to process right now.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Process a single pending scale with AI refinements.
     */
    public function ajax_process_pending_scale() {
        check_ajax_referer( 'naboo_pending_processor_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized access.' ) );
        }

        $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
        if ( ! $post_id ) {
            wp_send_json_error( array( 'message' => 'No scale ID provided.' ) );
        }

        $post = get_post( $post_id );
        if ( ! $post || $post->post_type !== 'psych_scale' ) {
            wp_send_json_error( array( 'message' => 'Invalid scale ID.' ) );
        }

        // 1. Identify missing fields
        $fields_to_check = array(
            'abstract'         => get_post_meta( $post_id, '_naboo_scale_abstract', true ),
            'items'            => get_post_meta( $post_id, '_naboo_scale_items', true ),
            'author_details'   => get_post_meta( $post_id, '_naboo_scale_author_details', true ),
            'validity'         => get_post_meta( $post_id, '_naboo_scale_validity', true ),
            'reliability'      => get_post_meta( $post_id, '_naboo_scale_reliability', true ),
        );

        $taxonomies_to_check = array(
            'scale_category' => wp_get_object_terms( $post_id, 'scale_category', array('fields' => 'ids') ),
            'scale_author'   => wp_get_object_terms( $post_id, 'scale_author', array('fields' => 'ids') ),
            'scale_year'     => wp_get_object_terms( $post_id, 'scale_year', array('fields' => 'ids') ),
        );

        $missing_fields = array();
        
        foreach ( $fields_to_check as $field_key => $value ) {
            if ( empty( trim( $value ) ) || stripos($value, 'information not available') !== false || stripos($value, 'not mentioned') !== false) {
                $missing_fields[] = $field_key;
            }
        }

        foreach ( $taxonomies_to_check as $tax_name => $terms ) {
            if ( empty( $terms ) || is_wp_error( $terms ) ) {
                if ($tax_name === 'scale_category') {
                     $missing_fields[] = 'category';
                } elseif ($tax_name === 'scale_author') {
                     $missing_fields[] = 'authors';
                } elseif ($tax_name === 'scale_year') {
                     $missing_fields[] = 'year';
                }
            }
        }

        $extractor = new AI_Extractor();
        $refine_count = 0;
        $failed_refine = false;

        // 2. Refine empty fields via AI
        if ( ! empty( $missing_fields ) ) {
            
            foreach ( $missing_fields as $field_name ) {
                $result = $extractor->refine_published_field( $post_id, $field_name, '' );
                
                if ( is_wp_error( $result ) || empty(trim($result)) || stripos($result, 'information not available') !== false ) {
                    // AI could not refine this field
                    $failed_refine = true;
                    continue;
                }
                
                // Save successful refinement
                $refine_count++;
                
                // Map to proper meta/tax
                $tax_map = array(
                    'year'      => 'scale_year',
                    'language'   => 'scale_language',
                    'test_type'  => 'scale_test_type',
                    'format'    => 'scale_format',
                    'age_group' => 'scale_age_group',
                    'authors'   => 'scale_author',
                    'category'  => 'scale_category',
                );

                if ( array_key_exists( $field_name, $tax_map ) ) {
                    $terms = array_map( 'trim', explode( ',', $result ) );
                    $terms = array_filter( $terms );
                    wp_set_object_terms( $post_id, $terms, $tax_map[ $field_name ] );
                    update_post_meta( $post_id, '_naboo_scale_' . $field_name, $result ); // Sync meta
                } else {
                    update_post_meta( $post_id, '_naboo_scale_' . $field_name, $result );
                }
            }
        }

        // 3. Final validation to determine status
        $is_complete = true;
        
        // Re-check essential fields after possible refinement
        $final_check = array(
            'abstract'         => get_post_meta( $post_id, '_naboo_scale_abstract', true ),
            'items'            => get_post_meta( $post_id, '_naboo_scale_items', true ),
            'author_details'   => get_post_meta( $post_id, '_naboo_scale_author_details', true ),
        );
        foreach ( $final_check as $value ) {
           if ( empty( trim( $value ) ) || stripos($value, 'information not available') !== false) {
               $is_complete = false;
               break;
           }
        }
        
        if ( $is_complete ) {
            $tax_final_check = array('scale_category', 'scale_author', 'scale_year');
            foreach ( $tax_final_check as $tax_name ) {
               $terms = wp_get_object_terms( $post_id, $tax_name, array('fields' => 'ids') );
               if ( empty( $terms ) || is_wp_error( $terms ) ) {
                   $is_complete = false;
                   break;
               }
            }
        }

        // If 'failed_refine' is true from above because Gemini returned empty or "information not available"
        if ( $failed_refine ) {
           $is_complete = false;
        }

        // 4. Update status based on completeness
        if ( $is_complete ) {
            wp_update_post( array(
                'ID'          => $post_id,
                'post_status' => 'publish',
            ) );
            $status_msg = 'Published';
            $status_class = 'success';
        } else {
            wp_update_post( array(
                'ID'          => $post_id,
                'post_status' => 'naboo_manual',
            ) );
            $status_msg = 'Needs Manual';
            $status_class = 'warning';
            
            // Log missing pieces so the user knows what to fix
            $status_msg .= ' (Missing: ' . implode(', ', $missing_fields) . ')';
        }

        $title = get_the_title( $post_id );
        $edit_url = admin_url( 'post.php?post=' . $post_id . '&action=edit' );
        
        $message_html = sprintf(
            '<li><a href="%s" target="_blank">%s</a> - Refined %d fields. <span class="naboo-badge naboo-badge-%s">%s</span></li>',
            esc_url( $edit_url ),
            esc_html( $title ),
            $refine_count,
            $status_class,
            esc_html( $status_msg )
        );

        wp_send_json_success( array(
            'message' => $message_html,
            'status'  => $is_complete ? 'published' : 'manual'
        ) );
    }
}
