<?php

namespace ArabPsychology\NabooDatabase\Admin;

class Admin {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/naboodatabase-admin.css', array(), $this->version, 'all' );
	}

	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/naboodatabase-admin.js', array( 'jquery' ), $this->version, false );

		// Enqueue media uploader on psych_scale edit screen.
		$screen = get_current_screen();
		if ( $screen && 'psych_scale' === $screen->post_type ) {
			wp_enqueue_media();
		}
	}

    public function add_meta_boxes() {
        add_meta_box(
            'naboo_scale_details',
            __( 'Scale Details', 'naboodatabase' ),
            array( $this, 'render_scale_metabox' ),
            'psych_scale',
            'normal',
            'high'
        );
    }

    public function render_scale_metabox( $post ) {
        // Add a nonce field so we can check for it later.
        wp_nonce_field( 'naboo_save_scale_details', 'naboo_scale_details_nonce' );

        // Retrieve all meta values.
        $meta = array(
            'construct'       => get_post_meta( $post->ID, '_naboo_scale_construct', true ),
            'purpose'         => get_post_meta( $post->ID, '_naboo_scale_purpose', true ),
            'abstract'        => get_post_meta( $post->ID, '_naboo_scale_abstract', true ),
            'items'           => get_post_meta( $post->ID, '_naboo_scale_items', true ),
            'items_list'      => get_post_meta( $post->ID, '_naboo_scale_items_list', true ),
            'year'            => implode( ', ', wp_get_object_terms( $post->ID, 'scale_year', array( 'fields' => 'names' ) ) ),
            'language'        => implode( ', ', wp_get_object_terms( $post->ID, 'scale_language', array( 'fields' => 'names' ) ) ),
            'test_type'       => implode( ', ', wp_get_object_terms( $post->ID, 'scale_test_type', array( 'fields' => 'names' ) ) ),
            'format'          => implode( ', ', wp_get_object_terms( $post->ID, 'scale_format', array( 'fields' => 'names' ) ) ),
            'methodology'     => get_post_meta( $post->ID, '_naboo_scale_methodology', true ),
            'reliability'     => get_post_meta( $post->ID, '_naboo_scale_reliability', true ),
            'validity'        => get_post_meta( $post->ID, '_naboo_scale_validity', true ),
            'factor_analysis' => get_post_meta( $post->ID, '_naboo_scale_factor_analysis', true ),
            'population'      => get_post_meta( $post->ID, '_naboo_scale_population', true ),
            'age_group'       => implode( ', ', wp_get_object_terms( $post->ID, 'scale_age_group', array( 'fields' => 'names' ) ) ),
            'author_details'  => get_post_meta( $post->ID, '_naboo_scale_author_details', true ),
            'author_email'    => get_post_meta( $post->ID, '_naboo_scale_author_email', true ),
            'author_orcid'    => get_post_meta( $post->ID, '_naboo_scale_author_orcid', true ),
            'administration_method'    => get_post_meta( $post->ID, '_naboo_scale_administration_method', true ),
            'instrument_type' => get_post_meta( $post->ID, '_naboo_scale_instrument_type', true ),
            'source_reference'=> get_post_meta( $post->ID, '_naboo_scale_source_reference', true ),
            'permissions'     => get_post_meta( $post->ID, '_naboo_scale_permissions', true ),
            'references'      => get_post_meta( $post->ID, '_naboo_scale_references', true ),
            'file'            => get_post_meta( $post->ID, '_naboo_scale_file', true ),
            'linked_versions' => get_post_meta( $post->ID, '_naboo_scale_linked_versions', true ),
        );

        require plugin_dir_path( __FILE__ ) . 'partials/scale-metabox.php';
    }

    public function save_meta_box_data( $post_id ) {
        // Check if our nonce is set.
        if ( ! isset( $_POST['naboo_scale_details_nonce'] ) ) {
            return;
        }

        // Verify that the nonce is valid.
        if ( ! wp_verify_nonce( $_POST['naboo_scale_details_nonce'], 'naboo_save_scale_details' ) ) {
            return;
        }

        // If this is an autosave, our form has not been submitted, so we don't want to do anything.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Check the user's permissions.
        if ( isset( $_POST['post_type'] ) && 'psych_scale' == $_POST['post_type'] ) {
            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                return;
            }
        } else {
            return;
        }

        // Separate email for specialized sanitization
        if ( isset( $_POST['_naboo_scale_author_email'] ) ) {
            update_post_meta( $post_id, '_naboo_scale_author_email', sanitize_email( wp_unslash( $_POST['_naboo_scale_author_email'] ) ) );
        }

        // Separate orcid for strict text sanitization
        if ( isset( $_POST['_naboo_scale_author_orcid'] ) ) {
            update_post_meta( $post_id, '_naboo_scale_author_orcid', sanitize_text_field( wp_unslash( $_POST['_naboo_scale_author_orcid'] ) ) );
        }

        // Strict text fields (sanitize_text_field).
        $strict_text_fields = array(
            '_naboo_scale_items',
            '_naboo_scale_year', // Even though it's a taxonomy, meta is sometimes saved. Let's keep strict text out of the main array if we separate it. Wait, year is handled below in taxonomies.
            '_naboo_scale_administration_method',
            '_naboo_scale_instrument_type',
            '_naboo_scale_permissions',
            '_naboo_scale_file',
            '_naboo_scale_construct',
            '_naboo_scale_keywords',
            '_naboo_scale_methodology',
            '_naboo_scale_population',
            '_naboo_scale_age_group',
        );

        foreach ( $strict_text_fields as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                update_post_meta( $post_id, $field, sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) );
            }
        }

        // Textarea fields (allows safe HTML using wp_kses_post).
        $textarea_fields = array(
            '_naboo_scale_purpose',
            '_naboo_scale_abstract',
            '_naboo_scale_items_list',
            '_naboo_scale_scoring_rules',
            '_naboo_scale_r_code',
            '_naboo_scale_reliability',
            '_naboo_scale_validity',
            '_naboo_scale_factor_analysis',
            '_naboo_scale_author_details',
            '_naboo_scale_source_reference',
            '_naboo_scale_references',
        );

        foreach ( $textarea_fields as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                update_post_meta( $post_id, $field, wp_kses_post( wp_unslash( $_POST[ $field ] ) ) );
            }
        }

        // Save Taxonomies
        $taxonomies = array(
            'scale_year'      => '_naboo_scale_year',
            'scale_language'   => '_naboo_scale_language',
            'scale_test_type'  => '_naboo_scale_test_type',
            'scale_format'    => '_naboo_scale_format',
            'scale_age_group' => '_naboo_scale_age_group',
        );

        foreach ( $taxonomies as $taxonomy => $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                $terms = array_map( 'trim', explode( ',', wp_unslash( $_POST[ $field ] ) ) );
                $terms = array_filter( $terms ); // remove empty
                wp_set_object_terms( $post_id, $terms, $taxonomy );
                
                // Keep the meta for total legacy fallback/compatibility if needed, 
                // but primarily we use taxonomies now. 
                // Update: Let's keep meta updated too for now so we don't break code I haven't seen yet.
                update_post_meta( $post_id, $field, sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) );
            }
        }

        // Linked Versions Array
        if ( isset( $_POST['_naboo_scale_linked_versions'] ) && is_array( $_POST['_naboo_scale_linked_versions'] ) ) {
            $linked_versions = array();
            foreach ( $_POST['_naboo_scale_linked_versions'] as $version ) {
                if ( ! empty( $version['id'] ) || ! empty( $version['type'] ) ) {
                    $linked_versions[] = array(
                        'id'   => sanitize_text_field( $version['id'] ),
                        'type' => sanitize_text_field( $version['type'] ),
                    );
                }
            }
            // Filter out empty rows where both are somehow empty after sanitization
            $linked_versions = array_filter( $linked_versions, function( $v ) {
                return ! empty( $v['id'] ) || ! empty( $v['type'] );
            });
            update_post_meta( $post_id, '_naboo_scale_linked_versions', array_values( $linked_versions ) );
        } else {
            delete_post_meta( $post_id, '_naboo_scale_linked_versions' );
        }
    }

    /**
     * Define the columns for the scale list table.
     */
    public function manage_columns( $columns ) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['author_taxonomy'] = __( 'Scale Author', 'naboodatabase' );
        $new_columns['scale_category'] = __( 'Category', 'naboodatabase' );
        $new_columns['scale_year'] = __( 'Year', 'naboodatabase' );
        $new_columns['items'] = __( 'Items', 'naboodatabase' );
        $new_columns['grokipedia_sync'] = __( 'G', 'naboodatabase' );
        $new_columns['date'] = $columns['date'];
        
        return $new_columns;
    }

    /**
     * Render the content for the custom columns.
     */
    public function manage_custom_column( $column, $post_id ) {
        switch ( $column ) {
            case 'author_taxonomy':
                echo get_the_term_list( $post_id, 'scale_author', '', ', ' );
                break;
            case 'scale_category':
                echo get_the_term_list( $post_id, 'scale_category', '', ', ' );
                break;
            case 'scale_year':
                echo get_the_term_list( $post_id, 'scale_year', '', ', ' );
                break;
            case 'items':
                echo esc_html( get_post_meta( $post_id, '_naboo_scale_items', true ) );
                break;
            case 'grokipedia_sync':
                $synced = get_post_meta( $post_id, '_naboo_synced_grokipedia', true );
                if ( '1' === $synced ) {
                    echo '<span class="dashicons dashicons-yes-alt" style="color: #10b981;" title="' . esc_attr__( 'Synced to Grokipedia', 'naboodatabase' ) . '"></span>';
                } else {
                    echo '<span class="dashicons dashicons-minus" style="color: #94a3b8; opacity: 0.3;"></span>';
                }
                break;
        }
    }

    /**
     * Register sortable columns for the scale list table.
     * Hooked to: manage_edit-psych_scale_sortable_columns
     */
    public function sortable_columns( $columns ) {
        $columns['scale_year'] = 'scale_year_order';
        $columns['items']      = 'items_order';
        return $columns;
    }

    /**
     * Modify the main query to handle custom sort orders.
     * Hooked to: pre_get_posts
     */
    public function handle_custom_sort( $query ) {
        if ( ! is_admin() || ! $query->is_main_query() ) {
            return;
        }
        if ( $query->get( 'post_type' ) !== 'psych_scale' ) {
            return;
        }

        $orderby = $query->get( 'orderby' );

        if ( 'scale_year_order' === $orderby ) {
            $query->set( 'orderby', 'meta_value_num' );
            $query->set( 'meta_key', '_naboo_scale_year' );
        }

        if ( 'items_order' === $orderby ) {
            $query->set( 'orderby', 'meta_value_num' );
            $query->set( 'meta_key', '_naboo_scale_items' );
        }
    }

}
