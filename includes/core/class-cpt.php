<?php

namespace ArabPsychology\NabooDatabase\Core;

class CPT {

	public function register() {
        $this->register_custom_statuses();
        add_filter( 'views_edit-naboo_raw_draft', array( $this, 'add_processed_tab' ) );
        add_filter( 'views_edit-psych_scale', array( $this, 'add_manual_tab' ) );
        
		$labels = array(
			'name'                  => _x( 'Scales', 'Post Type General Name', 'naboodatabase' ),
			'singular_name'         => _x( 'Scale', 'Post Type Singular Name', 'naboodatabase' ),
			'menu_name'             => __( 'Psychological Scales', 'naboodatabase' ),
			'name_admin_bar'        => __( 'Scale', 'naboodatabase' ),
			'archives'              => __( 'Scale Archives', 'naboodatabase' ),
			'attributes'            => __( 'Scale Attributes', 'naboodatabase' ),
			'parent_item_colon'     => __( 'Parent Scale:', 'naboodatabase' ),
			'all_items'             => __( 'All Scales', 'naboodatabase' ),
			'add_new_item'          => __( 'Add New Scale', 'naboodatabase' ),
			'add_new'               => __( 'Add New', 'naboodatabase' ),
			'new_item'              => __( 'New Scale', 'naboodatabase' ),
			'edit_item'             => __( 'Edit Scale', 'naboodatabase' ),
			'update_item'           => __( 'Update Scale', 'naboodatabase' ),
			'view_item'             => __( 'View Scale', 'naboodatabase' ),
			'view_items'            => __( 'View Scales', 'naboodatabase' ),
			'search_items'          => __( 'Search Scale', 'naboodatabase' ),
			'not_found'             => __( 'Not found', 'naboodatabase' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'naboodatabase' ),
			'featured_image'        => __( 'Featured Image', 'naboodatabase' ),
			'set_featured_image'    => __( 'Set featured image', 'naboodatabase' ),
			'remove_featured_image' => __( 'Remove featured image', 'naboodatabase' ),
			'use_featured_image'    => __( 'Use as featured image', 'naboodatabase' ),
			'insert_into_item'      => __( 'Insert into scale', 'naboodatabase' ),
			'uploaded_to_this_item' => __( 'Uploaded to this scale', 'naboodatabase' ),
			'items_list'            => __( 'Scales list', 'naboodatabase' ),
			'items_list_navigation' => __( 'Scales list navigation', 'naboodatabase' ),
			'filter_items_list'     => __( 'Filter scales list', 'naboodatabase' ),
		);
		$args = array(
			'label'                 => __( 'Scale', 'naboodatabase' ),
			'description'           => __( 'Psychological Scales Database', 'naboodatabase' ),
			'labels'                => $labels,
			'supports'              => array( 'title', 'editor', 'author', 'thumbnail', 'custom-fields' ),
			'hierarchical'          => false,
			'public'                => true,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'menu_position'         => 5,
			'menu_icon'             => 'dashicons-clipboard',
			'show_in_admin_bar'     => true,
			'show_in_nav_menus'     => true,
			'show_in_rest'          => true,
			'can_export'            => true,
			'has_archive'           => true,
			'exclude_from_search'   => false,
			'publicly_queryable'    => true,
			'capability_type'       => 'post',
			'map_meta_cap'          => true,
		);
		register_post_type( 'psych_scale', $args );

        // Register Drafts for AI Batch Processing
        $this->register_raw_draft_cpt();

        // Register Taxonomies
        $this->register_taxonomies();

        // Register Glossary CPT
        $this->register_glossary_cpt();
	}

    public function register_custom_statuses() {
        register_post_status( 'naboo_processed', array(
            'label'                     => _x( 'Processed', 'post status label', 'naboodatabase' ),
            'public'                    => true,
            'exclude_from_search'       => true,
            'show_in_admin_all_list'    => false,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop( 'Processed <span class="count">(%s)</span>', 'Processed <span class="count">(%s)</span>', 'naboodatabase' ),
        ) );

        register_post_status( 'naboo_manual', array(
            'label'                     => _x( 'Needs Manual', 'post status label', 'naboodatabase' ),
            'public'                    => true,
            'exclude_from_search'       => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop( 'Needs Manual <span class="count">(%s)</span>', 'Needs Manual <span class="count">(%s)</span>', 'naboodatabase' ),
        ) );
    }

    public function add_processed_tab( $views ) {
        if ( isset( $views['naboo_processed'] ) ) {
            return $views; // WP already added it
        }

        $count = wp_count_posts( 'naboo_raw_draft' );
        $num   = isset( $count->naboo_processed ) ? $count->naboo_processed : 0;
        
        if ( $num > 0 ) {
            $class = ( isset( $_GET['post_status'] ) && $_GET['post_status'] === 'naboo_processed' ) ? 'current' : '';
            $url   = admin_url( 'edit.php?post_status=naboo_processed&post_type=naboo_raw_draft' );
            $label = sprintf( _n( 'Processed <span class="count">(%s)</span>', 'Processed <span class="count">(%s)</span>', $num, 'naboodatabase' ), number_format_i18n( $num ) );
            $views['naboo_processed'] = "<a href='{$url}' class='{$class}'>{$label}</a>";
        }
        
        return $views;
    }

    public function add_manual_tab( $views ) {
        if ( isset( $views['naboo_manual'] ) ) {
            return $views; // WP already added it
        }

        $count = wp_count_posts( 'psych_scale' );
        $num   = isset( $count->naboo_manual ) ? $count->naboo_manual : 0;
        
        if ( $num > 0 ) {
            $class = ( isset( $_GET['post_status'] ) && $_GET['post_status'] === 'naboo_manual' ) ? 'current' : '';
            $url   = admin_url( 'edit.php?post_status=naboo_manual&post_type=psych_scale' );
            $label = sprintf( _n( 'Needs Manual <span class="count">(%s)</span>', 'Needs Manual <span class="count">(%s)</span>', $num, 'naboodatabase' ), number_format_i18n( $num ) );
            $views['naboo_manual'] = "<a href='{$url}' class='{$class}'>{$label}</a>";
        }
        
        return $views;
    }

    private function register_raw_draft_cpt() {
        $labels = array(
            'name'                  => _x( 'Raw Drafts', 'Post Type General Name', 'naboodatabase' ),
            'singular_name'         => _x( 'Raw Draft', 'Post Type Singular Name', 'naboodatabase' ),
            'menu_name'             => __( 'Raw Drafts', 'naboodatabase' ),
            'all_items'             => __( 'Raw Drafts', 'naboodatabase' ),
            'add_new_item'          => __( 'Add New Raw Draft', 'naboodatabase' ),
            'add_new'               => __( 'Add New', 'naboodatabase' ),
            'new_item'              => __( 'New Raw Draft', 'naboodatabase' ),
            'edit_item'             => __( 'Edit Raw Draft', 'naboodatabase' ),
            'update_item'           => __( 'Update Raw Draft', 'naboodatabase' ),
            'view_item'             => __( 'View Raw Draft', 'naboodatabase' ),
            'view_items'            => __( 'View Raw Drafts', 'naboodatabase' ),
            'search_items'          => __( 'Search Raw Drafts', 'naboodatabase' ),
            'not_found'             => __( 'Not found', 'naboodatabase' ),
            'not_found_in_trash'    => __( 'Not found in Trash', 'naboodatabase' ),
        );
        
        $args = array(
            'label'                 => __( 'Raw Draft', 'naboodatabase' ),
            'description'           => __( 'Raw text drafts for AI processing into scales.', 'naboodatabase' ),
            'labels'                => $labels,
            'supports'              => array( 'title', 'editor', 'author' ),
            'hierarchical'          => false,
            'public'                => false,
            'show_ui'               => true,
            'show_in_menu'          => 'edit.php?post_type=psych_scale',
            'menu_position'         => 10,
            'show_in_admin_bar'     => false,
            'show_in_nav_menus'     => false,
            'show_in_rest'          => false,
            'can_export'            => false,
            'has_archive'           => false,
            'exclude_from_search'   => true,
            'publicly_queryable'    => false,
            'capability_type'       => 'post',
            'rewrite'               => false,
        );
        
        register_post_type( 'naboo_raw_draft', $args );

    }

    private function register_taxonomies() {
        $taxonomies = array(
            'scale_category' => array(
                'object_type' => array( 'psych_scale' ),
                'args'        => array(
                    'hierarchical'      => true,
                    'labels'            => array(
                        'name'              => _x( 'Scale Categories', 'taxonomy general name', 'naboodatabase' ),
                        'singular_name'     => _x( 'Scale Category', 'taxonomy singular name', 'naboodatabase' ),
                        'search_items'      => __( 'Search Categories', 'naboodatabase' ),
                        'all_items'         => __( 'All Categories', 'naboodatabase' ),
                        'parent_item'       => __( 'Parent Category', 'naboodatabase' ),
                        'parent_item_colon' => __( 'Parent Category:', 'naboodatabase' ),
                        'edit_item'         => __( 'Edit Category', 'naboodatabase' ),
                        'update_item'       => __( 'Update Category', 'naboodatabase' ),
                        'add_new_item'      => __( 'Add New Category', 'naboodatabase' ),
                        'new_item_name'     => __( 'New Category Name', 'naboodatabase' ),
                        'menu_name'         => __( 'Categories', 'naboodatabase' ),
                    ),
                    'show_in_rest'      => true,
                    'rewrite'           => array( 'slug' => 'scale-category' ),
                )
            ),
            'scale_author' => array(
                'object_type' => array( 'psych_scale' ),
                'args'        => array(
                    'hierarchical'      => false,
                    'labels'            => array(
                        'name'              => _x( 'Scale Authors', 'taxonomy general name', 'naboodatabase' ),
                        'singular_name'     => _x( 'Scale Author', 'taxonomy singular name', 'naboodatabase' ),
                        'search_items'      => __( 'Search Authors', 'naboodatabase' ),
                        'all_items'         => __( 'All Authors', 'naboodatabase' ),
                        'edit_item'         => __( 'Edit Author', 'naboodatabase' ),
                        'update_item'       => __( 'Update Author', 'naboodatabase' ),
                        'add_new_item'      => __( 'Add New Author', 'naboodatabase' ),
                        'new_item_name'     => __( 'New Author Name', 'naboodatabase' ),
                        'menu_name'         => __( 'Authors', 'naboodatabase' ),
                    ),
                    'show_in_rest'      => true,
                    'rewrite'           => array( 'slug' => 'scale-author' ),
                )
            ),
            'scale_year' => array(
                'object_type' => array( 'psych_scale' ),
                'args'        => array(
                    'hierarchical'      => false,
                    'labels'            => array(
                        'name'              => _x( 'Years', 'taxonomy general name', 'naboodatabase' ),
                        'singular_name'     => _x( 'Year', 'taxonomy singular name', 'naboodatabase' ),
                        'search_items'      => __( 'Search Years', 'naboodatabase' ),
                        'all_items'         => __( 'All Years', 'naboodatabase' ),
                        'edit_item'         => __( 'Edit Year', 'naboodatabase' ),
                        'update_item'       => __( 'Update Year', 'naboodatabase' ),
                        'add_new_item'      => __( 'Add New Year', 'naboodatabase' ),
                        'new_item_name'     => __( 'New Year Name', 'naboodatabase' ),
                        'menu_name'         => __( 'Years', 'naboodatabase' ),
                    ),
                    'rewrite'           => array( 'slug' => 'scale-year' ),
                )
            ),
            'scale_language' => array(
                'object_type' => array( 'psych_scale' ),
                'args'        => array(
                    'hierarchical'      => false,
                    'labels'            => array(
                        'name'              => _x( 'Languages', 'taxonomy general name', 'naboodatabase' ),
                        'singular_name'     => _x( 'Language', 'taxonomy singular name', 'naboodatabase' ),
                        'search_items'      => __( 'Search Languages', 'naboodatabase' ),
                        'all_items'         => __( 'All Languages', 'naboodatabase' ),
                        'edit_item'         => __( 'Edit Language', 'naboodatabase' ),
                        'update_item'       => __( 'Update Language', 'naboodatabase' ),
                        'add_new_item'      => __( 'Add New Language', 'naboodatabase' ),
                        'new_item_name'     => __( 'New Language Name', 'naboodatabase' ),
                        'menu_name'         => __( 'Languages', 'naboodatabase' ),
                    ),
                    'rewrite'           => array( 'slug' => 'scale-language' ),
                )
            ),
            'scale_test_type' => array(
                'object_type' => array( 'psych_scale' ),
                'args'        => array(
                    'hierarchical'      => false,
                    'labels'            => array(
                        'name'              => _x( 'Test Types', 'taxonomy general name', 'naboodatabase' ),
                        'singular_name'     => _x( 'Test Type', 'taxonomy singular name', 'naboodatabase' ),
                        'search_items'      => __( 'Search Test Types', 'naboodatabase' ),
                        'all_items'         => __( 'All Test Types', 'naboodatabase' ),
                        'edit_item'         => __( 'Edit Test Type', 'naboodatabase' ),
                        'update_item'       => __( 'Update Test Type', 'naboodatabase' ),
                        'add_new_item'      => __( 'Add New Test Type', 'naboodatabase' ),
                        'new_item_name'     => __( 'New Test Type Name', 'naboodatabase' ),
                        'menu_name'         => __( 'Test Types', 'naboodatabase' ),
                    ),
                    'rewrite'           => array( 'slug' => 'scale-test-type' ),
                )
            ),
            'scale_format' => array(
                'object_type' => array( 'psych_scale' ),
                'args'        => array(
                    'hierarchical'      => false,
                    'labels'            => array(
                        'name'              => _x( 'Formats', 'taxonomy general name', 'naboodatabase' ),
                        'singular_name'     => _x( 'Format', 'taxonomy singular name', 'naboodatabase' ),
                        'search_items'      => __( 'Search Formats', 'naboodatabase' ),
                        'all_items'         => __( 'All Formats', 'naboodatabase' ),
                        'edit_item'         => __( 'Edit Format', 'naboodatabase' ),
                        'update_item'       => __( 'Update Format', 'naboodatabase' ),
                        'add_new_item'      => __( 'Add New Format', 'naboodatabase' ),
                        'new_item_name'     => __( 'New Format Name', 'naboodatabase' ),
                        'menu_name'         => __( 'Formats', 'naboodatabase' ),
                    ),
                    'rewrite'           => array( 'slug' => 'scale-format' ),
                )
            ),
            'scale_age_group' => array(
                'object_type' => array( 'psych_scale' ),
                'args'        => array(
                    'hierarchical'      => false,
                    'labels'            => array(
                        'name'              => _x( 'Age Groups', 'taxonomy general name', 'naboodatabase' ),
                        'singular_name'     => _x( 'Age Group', 'taxonomy singular name', 'naboo' ),
                        'search_items'      => __( 'Search Age Groups', 'naboodatabase' ),
                        'all_items'         => __( 'All Age Groups', 'naboodatabase' ),
                        'edit_item'         => __( 'Edit Age Group', 'naboodatabase' ),
                        'update_item'       => __( 'Update Age Group', 'naboodatabase' ),
                        'add_new_item'      => __( 'Add New Age Group', 'naboodatabase' ),
                        'new_item_name'     => __( 'New Age Group Name', 'naboodatabase' ),
                        'menu_name'         => __( 'Age Groups', 'naboodatabase' ),
                    ),
                    'show_in_rest'      => true,
                    'rewrite'           => array( 'slug' => 'scale-age-group' ),
                )
            ),
            'glossary_category' => array(
                'object_type' => array( 'naboo_glossary' ),
                'args'        => array(
                    'hierarchical'      => true,
                    'labels'            => array(
                        'name'              => _x( 'Glossary Categories', 'taxonomy general name', 'naboodatabase' ),
                        'singular_name'     => _x( 'Glossary Category', 'taxonomy singular name', 'naboodatabase' ),
                        'search_items'      => __( 'Search Categories', 'naboodatabase' ),
                        'all_items'         => __( 'All Categories', 'naboodatabase' ),
                        'parent_item'       => __( 'Parent Category', 'naboodatabase' ),
                        'parent_item_colon' => __( 'Parent Category:', 'naboodatabase' ),
                        'edit_item'         => __( 'Edit Category', 'naboodatabase' ),
                        'update_item'       => __( 'Update Category', 'naboodatabase' ),
                        'add_new_item'      => __( 'Add New Category', 'naboodatabase' ),
                        'new_item_name'     => __( 'New Category Name', 'naboodatabase' ),
                        'menu_name'         => __( 'Categories', 'naboodatabase' ),
                    ),
                    'show_in_rest'      => true,
                    'rewrite'           => array( 'slug' => 'glossary-category' ),
                )
            ),
        );

        $default_args = array(
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
        );

        foreach ( $taxonomies as $taxonomy => $data ) {
            $args = array_merge( $default_args, $data['args'] );
            register_taxonomy( $taxonomy, $data['object_type'], $args );
        }
    }

    private function register_glossary_cpt() {
        $labels = array(
            'name'                  => _x( 'Glossary Terms', 'Post Type General Name', 'naboodatabase' ),
            'singular_name'         => _x( 'Glossary Term', 'Post Type Singular Name', 'naboodatabase' ),
            'menu_name'             => __( 'Glossary', 'naboodatabase' ),
            'all_items'             => __( 'All Terms', 'naboodatabase' ),
            'add_new_item'          => __( 'Add New Term', 'naboodatabase' ),
            'add_new'               => __( 'Add New', 'naboodatabase' ),
            'new_item'              => __( 'New Term', 'naboodatabase' ),
            'edit_item'             => __( 'Edit Term', 'naboodatabase' ),
            'update_item'           => __( 'Update Term', 'naboodatabase' ),
            'view_item'             => __( 'View Term', 'naboodatabase' ),
            'view_items'            => __( 'View Terms', 'naboodatabase' ),
            'search_items'          => __( 'Search Terms', 'naboodatabase' ),
            'not_found'             => __( 'Not found', 'naboodatabase' ),
            'not_found_in_trash'    => __( 'Not found in Trash', 'naboodatabase' ),
        );

        $args = array(
            'label'                 => __( 'Glossary Term', 'naboodatabase' ),
            'description'           => __( 'Psychological terms and definitions.', 'naboodatabase' ),
            'labels'                => $labels,
            'supports'              => array( 'title', 'editor', 'excerpt', 'thumbnail' ),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => 'edit.php?post_type=psych_scale',
            'menu_position'         => 16,
            'menu_icon'             => 'dashicons-book-alt',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'show_in_rest'          => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'rewrite'               => array( 'slug' => 'glossary' ),
        );

        register_post_type( 'naboo_glossary', $args );
    }
}
