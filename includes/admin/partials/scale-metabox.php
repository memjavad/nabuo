<?php
/**
 * Scale Details Meta Box Template
 * Organized into tabbed sections for clarity.
 */
?>
<div class="naboo-metabox-wrapper">

    <!-- Tab Navigation -->
    <div class="naboo-metabox-tabs">
        <button type="button" class="naboo-metabox-tab active" data-tab="basic">
            <span class="dashicons dashicons-info-outline"></span>
            <?php _e( 'Basic Info', 'naboodatabase' ); ?>
        </button>
        <button type="button" class="naboo-metabox-tab" data-tab="instrument">
            <span class="dashicons dashicons-clipboard"></span>
            <?php _e( 'Instrument', 'naboodatabase' ); ?>
        </button>
        <button type="button" class="naboo-metabox-tab" data-tab="psychometrics">
            <span class="dashicons dashicons-chart-bar"></span>
            <?php _e( 'Psychometrics', 'naboodatabase' ); ?>
        </button>
        <button type="button" class="naboo-metabox-tab" data-tab="population">
            <span class="dashicons dashicons-groups"></span>
            <?php _e( 'Population', 'naboodatabase' ); ?>
        </button>
        <button type="button" class="naboo-metabox-tab" data-tab="additional">
            <span class="dashicons dashicons-admin-page"></span>
            <?php _e( 'Additional', 'naboodatabase' ); ?>
        </button>
        <button type="button" class="naboo-metabox-tab" data-tab="versions">
            <span class="dashicons dashicons-admin-links"></span>
            <?php _e( 'Versions', 'naboodatabase' ); ?>
        </button>
    </div>

    <!-- ══════════ Tab 1: Basic Information ══════════ -->
    <div class="naboo-metabox-panel active" data-panel="basic">
        <div class="naboo-panel-header">
            <h3><span class="dashicons dashicons-info-outline"></span> <?php _e( 'Basic Information', 'naboodatabase' ); ?></h3>
            <p class="naboo-panel-desc"><?php _e( 'Core information about the psychological scale.', 'naboodatabase' ); ?></p>
        </div>

        <div class="naboo-field">
            <label for="_naboo_scale_construct"><?php _e( 'Construct Measured', 'naboodatabase' ); ?></label>
            <input type="text" name="_naboo_scale_construct" id="_naboo_scale_construct" value="<?php echo esc_attr( $meta['construct'] ); ?>" class="widefat" placeholder="<?php _e( 'e.g., Self-Esteem, Depression, Anxiety', 'naboodatabase' ); ?>">
            <p class="naboo-field-help"><?php _e( 'The psychological construct this scale is designed to measure.', 'naboodatabase' ); ?></p>
        </div>

        <div class="naboo-field">
            <label for="_naboo_scale_keywords"><?php _e( 'Keywords', 'naboodatabase' ); ?></label>
            <input type="text" name="_naboo_scale_keywords" id="_naboo_scale_keywords" value="<?php echo esc_attr( $meta['keywords'] ?? '' ); ?>" class="widefat" placeholder="<?php _e( 'e.g., clinical, self-report, depression', 'naboodatabase' ); ?>">
            <p class="naboo-field-help"><?php _e( 'A comma-separated list of keywords related to the scale.', 'naboodatabase' ); ?></p>
        </div>

        <div class="naboo-field">
            <label for="_naboo_scale_purpose"><?php _e( 'Purpose', 'naboodatabase' ); ?></label>
            <textarea name="_naboo_scale_purpose" id="_naboo_scale_purpose" class="widefat" rows="3" placeholder="<?php _e( 'Describe the intended use and purpose of this scale...', 'naboodatabase' ); ?>"><?php echo esc_textarea( $meta['purpose'] ); ?></textarea>
            <p class="naboo-field-help"><?php _e( 'Brief description of the scale\'s intended use case in clinical or research settings.', 'naboodatabase' ); ?></p>
        </div>

        <div class="naboo-field">
            <label for="_naboo_scale_abstract"><?php _e( 'Abstract', 'naboodatabase' ); ?></label>
            <textarea name="_naboo_scale_abstract" id="_naboo_scale_abstract" class="widefat" rows="5" placeholder="<?php _e( 'Provide a summary of the scale development and its key features...', 'naboodatabase' ); ?>"><?php echo esc_textarea( $meta['abstract'] ); ?></textarea>
            <p class="naboo-field-help"><?php _e( 'A comprehensive summary including theoretical background, development process, and key characteristics.', 'naboodatabase' ); ?></p>
        </div>
    </div>

    <!-- ══════════ Tab 2: Instrument Details ══════════ -->
    <div class="naboo-metabox-panel" data-panel="instrument">
        <div class="naboo-panel-header">
            <h3><span class="dashicons dashicons-clipboard"></span> <?php _e( 'Instrument Details', 'naboodatabase' ); ?></h3>
            <p class="naboo-panel-desc"><?php _e( 'Technical specifications of the measurement instrument.', 'naboodatabase' ); ?></p>
        </div>

        <div class="naboo-field-row two-col">
            <div class="naboo-field">
                <label for="_naboo_scale_items"><?php _e( 'Number of Items', 'naboodatabase' ); ?></label>
                <input type="number" name="_naboo_scale_items" id="_naboo_scale_items" value="<?php echo esc_attr( $meta['items'] ); ?>" min="1" placeholder="<?php _e( 'e.g., 20', 'naboodatabase' ); ?>">
                <p class="naboo-field-help"><?php _e( 'Total number of items/questions in the scale.', 'naboodatabase' ); ?></p>
            </div>

            <div class="naboo-field">
                <label for="_naboo_scale_year"><?php _e( 'Publication Year', 'naboodatabase' ); ?></label>
                <input type="number" name="_naboo_scale_year" id="_naboo_scale_year" value="<?php echo esc_attr( $meta['year'] ); ?>" min="1900" max="<?php echo date( 'Y' ); ?>" placeholder="<?php _e( 'e.g., 2020', 'naboodatabase' ); ?>">
                <p class="naboo-field-help"><?php _e( 'The year this scale was originally published.', 'naboodatabase' ); ?></p>
            </div>
        </div>

        <div class="naboo-field-row two-col">
            <div class="naboo-field">
                <label for="_naboo_scale_language"><?php _e( 'Language', 'naboodatabase' ); ?></label>
                <input type="text" name="_naboo_scale_language" id="_naboo_scale_language" value="<?php echo esc_attr( $meta['language'] ); ?>" placeholder="<?php _e( 'e.g., Arabic, English', 'naboodatabase' ); ?>">
                <p class="naboo-field-help"><?php _e( 'The language(s) the scale is available in.', 'naboodatabase' ); ?></p>
            </div>

            <div class="naboo-field">
                <label for="_naboo_scale_test_type"><?php _e( 'Test Type', 'naboodatabase' ); ?></label>
                <input type="text" name="_naboo_scale_test_type" id="_naboo_scale_test_type" value="<?php echo esc_attr( $meta['test_type'] ); ?>" placeholder="<?php _e( 'e.g., Self-report, Observer-rating', 'naboodatabase' ); ?>">
                <p class="naboo-field-help"><?php _e( 'The category of the test (saved as tags).', 'naboodatabase' ); ?></p>
            </div>
        </div>
        
        <div class="naboo-field-row two-col">
            <div class="naboo-field">
                <label for="_naboo_scale_administration_method"><?php _e( 'Administration Method', 'naboodatabase' ); ?></label>
                <input type="text" name="_naboo_scale_administration_method" id="_naboo_scale_administration_method" value="<?php echo esc_attr( $meta['administration_method'] ?? '' ); ?>" placeholder="<?php _e( 'e.g., Paper-and-pencil, Computerized, Online', 'naboodatabase' ); ?>">
                <p class="naboo-field-help"><?php _e( 'How the scale is generally administered to participants.', 'naboodatabase' ); ?></p>
            </div>

            <div class="naboo-field">
                <label for="_naboo_scale_instrument_type"><?php _e( 'Instrument Type', 'naboodatabase' ); ?></label>
                <input type="text" name="_naboo_scale_instrument_type" id="_naboo_scale_instrument_type" value="<?php echo esc_attr( $meta['instrument_type'] ?? '' ); ?>" placeholder="<?php _e( 'e.g., Personality Inventory, Cognitive Test', 'naboodatabase' ); ?>">
                <p class="naboo-field-help"><?php _e( 'The modern category defining this instrument.', 'naboodatabase' ); ?></p>
            </div>
        </div>

        <div class="naboo-field-row two-col">
            <div class="naboo-field">
                <label for="_naboo_scale_format"><?php _e( 'Response Format', 'naboodatabase' ); ?></label>
                <input type="text" name="_naboo_scale_format" id="_naboo_scale_format" value="<?php echo esc_attr( $meta['format'] ); ?>" placeholder="<?php _e( 'e.g., 5-point Likert, Yes/No, Visual Analog', 'naboodatabase' ); ?>">
                <p class="naboo-field-help"><?php _e( 'The response format or rating scale used.', 'naboodatabase' ); ?></p>
            </div>

            <div class="naboo-field">
                <label for="_naboo_scale_methodology"><?php _e( 'Development Methodology', 'naboodatabase' ); ?></label>
                <input type="text" name="_naboo_scale_methodology" id="_naboo_scale_methodology" value="<?php echo esc_attr( $meta['methodology'] ); ?>" placeholder="<?php _e( 'e.g., Classical Test Theory, IRT', 'naboodatabase' ); ?>">
                <p class="naboo-field-help"><?php _e( 'The methodology used to develop or validate this scale.', 'naboodatabase' ); ?></p>
            </div>

            <div class="naboo-field">
                <label for="_naboo_scale_age_group"><?php _e( 'Age Group', 'naboodatabase' ); ?></label>
                <input type="text" name="_naboo_scale_age_group" id="_naboo_scale_age_group" value="<?php echo esc_attr( $meta['age_group'] ); ?>" placeholder="<?php _e( 'e.g., Children (0-12), Adolescents (13-17), Adults (18-64)', 'naboodatabase' ); ?>">
                <p class="naboo-field-help"><?php _e( 'The intended age group(s) for the scale (saved as tags).', 'naboodatabase' ); ?></p>
            </div>
        </div>

        <div class="naboo-field">
            <label for="_naboo_scale_items_list"><?php _e( 'Items List', 'naboodatabase' ); ?></label>
            <textarea name="_naboo_scale_items_list" id="_naboo_scale_items_list" class="widefat" rows="5" placeholder="<?php _e( 'List the items or questions of the scale here...', 'naboodatabase' ); ?>"><?php echo esc_textarea( $meta['items_list'] ?? '' ); ?></textarea>
            <p class="naboo-field-help"><?php _e( 'The full list of questions or items that make up the instrument.', 'naboodatabase' ); ?></p>
        </div>

        <div class="naboo-field">
            <label for="_naboo_scale_scoring_rules"><?php _e( 'Scoring Rules', 'naboodatabase' ); ?></label>
            <textarea name="_naboo_scale_scoring_rules" id="_naboo_scale_scoring_rules" class="widefat" rows="3" placeholder="<?php _e( 'e.g., Reverse scoring items, subscales sums...', 'naboodatabase' ); ?>"><?php echo esc_textarea( $meta['scoring_rules'] ?? '' ); ?></textarea>
            <p class="naboo-field-help"><?php _e( 'Instructions for scoring the scale, including Likert values or reverse-scored items.', 'naboodatabase' ); ?></p>
        </div>

        <div class="naboo-field">
            <label for="_naboo_scale_r_code"><?php _e( 'R Code for Auto-Scoring', 'naboodatabase' ); ?></label>
            <textarea name="_naboo_scale_r_code" id="_naboo_scale_r_code" class="widefat" rows="4" style="font-family: monospace;" placeholder="<?php _e( 'R code snippet for calculating totals...', 'naboodatabase' ); ?>"><?php echo esc_textarea( $meta['r_code'] ?? '' ); ?></textarea>
            <p class="naboo-field-help"><?php _e( 'An R code snippet that automates the scoring process.', 'naboodatabase' ); ?></p>
        </div>
    </div>

    <!-- ══════════ Tab 3: Psychometric Properties ══════════ -->
    <div class="naboo-metabox-panel" data-panel="psychometrics">
        <div class="naboo-panel-header">
            <h3><span class="dashicons dashicons-chart-bar"></span> <?php _e( 'Psychometric Properties', 'naboodatabase' ); ?></h3>
            <p class="naboo-panel-desc"><?php _e( 'Reliability, validity, and factor structure information.', 'naboodatabase' ); ?></p>
        </div>

        <div class="naboo-field">
            <label for="_naboo_scale_reliability"><?php _e( 'Reliability Coefficient', 'naboodatabase' ); ?></label>
            <textarea name="_naboo_scale_reliability" id="_naboo_scale_reliability" class="widefat" rows="3" placeholder="<?php _e( 'e.g., Cronbach\'s α = 0.89; Test-retest r = 0.85 (2-week interval)', 'naboodatabase' ); ?>"><?php echo esc_textarea( $meta['reliability'] ); ?></textarea>
            <p class="naboo-field-help"><?php _e( 'Internal consistency (Cronbach\'s alpha), test-retest, split-half, or inter-rater reliability values.', 'naboodatabase' ); ?></p>
        </div>

        <div class="naboo-field">
            <label for="_naboo_scale_validity"><?php _e( 'Validity Coefficient', 'naboodatabase' ); ?></label>
            <textarea name="_naboo_scale_validity" id="_naboo_scale_validity" class="widefat" rows="3" placeholder="<?php _e( 'Describe content, construct, convergent, and discriminant validity evidence...', 'naboodatabase' ); ?>"><?php echo esc_textarea( $meta['validity'] ); ?></textarea>
            <p class="naboo-field-help"><?php _e( 'Evidence for construct, convergent, discriminant, and criterion-related validity.', 'naboodatabase' ); ?></p>
        </div>

        <div class="naboo-field">
            <label for="_naboo_scale_factor_analysis"><?php _e( 'Factor Analysis', 'naboodatabase' ); ?></label>
            <textarea name="_naboo_scale_factor_analysis" id="_naboo_scale_factor_analysis" class="widefat" rows="3" placeholder="<?php _e( 'e.g., EFA revealed 3 factors accounting for 62% of variance; CFA: CFI = 0.95, RMSEA = 0.04', 'naboodatabase' ); ?>"><?php echo esc_textarea( $meta['factor_analysis'] ); ?></textarea>
            <p class="naboo-field-help"><?php _e( 'Results from exploratory or confirmatory factor analysis, including fit indices.', 'naboodatabase' ); ?></p>
        </div>
    </div>

    <!-- ══════════ Tab 4: Population & Administration ══════════ -->
    <div class="naboo-metabox-panel" data-panel="population">
        <div class="naboo-panel-header">
            <h3><span class="dashicons dashicons-groups"></span> <?php _e( 'Population & Administration', 'naboodatabase' ); ?></h3>
            <p class="naboo-panel-desc"><?php _e( 'Target population details and administration information.', 'naboodatabase' ); ?></p>
        </div>

        <div class="naboo-field-row">
            <div class="naboo-field">
                <label for="_naboo_scale_population"><?php _e( 'Target Population', 'naboodatabase' ); ?></label>
                <input type="text" name="_naboo_scale_population" id="_naboo_scale_population" value="<?php echo esc_attr( $meta['population'] ); ?>" placeholder="<?php _e( 'e.g., Adults, Adolescents, Clinical patients', 'naboodatabase' ); ?>" class="widefat">
                <p class="naboo-field-help"><?php _e( 'The intended population group for this scale.', 'naboodatabase' ); ?></p>
            </div>
        </div>
    </div>

    <!-- ══════════ Tab 5: Additional Information ══════════ -->
    <div class="naboo-metabox-panel" data-panel="additional">
        <div class="naboo-panel-header">
            <h3><span class="dashicons dashicons-admin-page"></span> <?php _e( 'Additional Information', 'naboodatabase' ); ?></h3>
            <p class="naboo-panel-desc"><?php _e( 'Author details, permissions, references, and file attachment.', 'naboodatabase' ); ?></p>
        </div>

        <div class="naboo-field">
            <label for="_naboo_scale_source_reference"><?php _e( 'Source Reference (APA Citation)', 'naboodatabase' ); ?></label>
            <textarea name="_naboo_scale_source_reference" id="_naboo_scale_source_reference" class="widefat" rows="2" placeholder="<?php _e( 'Smith, J. (2020). The Scale of Things. Journal of... ', 'naboodatabase' ); ?>"><?php echo esc_textarea( $meta['source_reference'] ?? '' ); ?></textarea>
            <p class="naboo-field-help"><?php _e( 'The formal APA citation of the original source paper.', 'naboodatabase' ); ?></p>
        </div>

        <div class="naboo-field">
            <label for="_naboo_scale_author_details"><?php _e( 'Author Information', 'naboodatabase' ); ?></label>
            <textarea name="_naboo_scale_author_details" id="_naboo_scale_author_details" class="widefat" rows="3" placeholder="<?php _e( 'Full name, affiliation, email, and contact details of the scale author(s)...', 'naboodatabase' ); ?>"><?php echo esc_textarea( $meta['author_details'] ); ?></textarea>
            <p class="naboo-field-help"><?php _e( 'Contact details and institutional affiliation of the scale developer(s).', 'naboodatabase' ); ?></p>
        </div>

        <div class="naboo-field-row two-col">
            <div class="naboo-field">
                <label for="_naboo_scale_author_email"><?php _e( 'Author Email', 'naboodatabase' ); ?></label>
                <input type="email" name="_naboo_scale_author_email" id="_naboo_scale_author_email" class="widefat" value="<?php echo esc_attr( $meta['author_email'] ?? '' ); ?>" placeholder="<?php _e( 'e.g., researcher@university.edu', 'naboodatabase' ); ?>">
            </div>

            <div class="naboo-field">
                <label for="_naboo_scale_author_orcid"><?php _e( 'Author ORCID', 'naboodatabase' ); ?></label>
                <input type="text" name="_naboo_scale_author_orcid" id="_naboo_scale_author_orcid" class="widefat" value="<?php echo esc_attr( $meta['author_orcid'] ?? '' ); ?>" placeholder="<?php _e( 'e.g., 0000-0000-0000-0000', 'naboodatabase' ); ?>">
            </div>
        </div>

        <div class="naboo-field">
            <label for="_naboo_scale_permissions"><?php _e( 'Permissions & Fee', 'naboodatabase' ); ?></label>
            <input type="text" name="_naboo_scale_permissions" id="_naboo_scale_permissions" value="<?php echo esc_attr( $meta['permissions'] ); ?>" placeholder="<?php _e( 'e.g., Free for research use, Contact author for clinical use', 'naboodatabase' ); ?>">
            <p class="naboo-field-help"><?php _e( 'Licensing, copyright, and fee information for using this scale.', 'naboodatabase' ); ?></p>
        </div>

        <div class="naboo-field">
            <label for="_naboo_scale_references"><?php _e( 'Key References', 'naboodatabase' ); ?></label>
            <textarea name="_naboo_scale_references" id="_naboo_scale_references" class="widefat" rows="4" placeholder="<?php _e( 'List key publications using NABOO citation format, one per line...', 'naboodatabase' ); ?>"><?php echo esc_textarea( $meta['references'] ); ?></textarea>
            <p class="naboo-field-help"><?php _e( 'Primary references for this scale in NABOO format.', 'naboodatabase' ); ?></p>
        </div>

        <div class="naboo-field naboo-file-field">
            <label for="_naboo_scale_file_btn"><?php _e( 'Scale Document (PDF/DOC)', 'naboodatabase' ); ?></label>
            <div class="naboo-file-upload-wrap">
                <input type="hidden" name="_naboo_scale_file" id="_naboo_scale_file" value="<?php echo esc_attr( $meta['file'] ); ?>">
                <button type="button" class="button naboo-upload-file-btn" id="_naboo_scale_file_btn">
                    <span class="dashicons dashicons-upload"></span>
                    <?php _e( 'Upload / Select File', 'naboodatabase' ); ?>
                </button>
                <button type="button" class="button naboo-remove-file-btn" id="_naboo_scale_file_remove" style="<?php echo empty( $meta['file'] ) ? 'display:none;' : ''; ?>">
                    <span class="dashicons dashicons-no-alt"></span>
                    <?php _e( 'Remove', 'naboodatabase' ); ?>
                </button>
                <?php if ( ! empty( $meta['file'] ) ) : ?>
                    <span class="naboo-file-name" id="_naboo_scale_file_name">
                        <span class="dashicons dashicons-media-document"></span>
                        <?php echo esc_html( basename( get_attached_file( $meta['file'] ) ) ); ?>
                    </span>
                <?php else : ?>
                    <span class="naboo-file-name" id="_naboo_scale_file_name" style="display:none;"></span>
                <?php endif; ?>
            </div>
            <p class="naboo-field-help"><?php _e( 'Upload the scale instrument, manual, or related documentation.', 'naboodatabase' ); ?></p>
        </div>
    </div>

    <!-- ══════════ Tab 6: Linked Versions ══════════ -->
    <div class="naboo-metabox-panel" data-panel="versions">
        <div class="naboo-panel-header">
            <h3><span class="dashicons dashicons-admin-links"></span> <?php _e( 'Linked Versions', 'naboodatabase' ); ?></h3>
            <p class="naboo-panel-desc"><?php _e( 'Link different versions of this scale (e.g., Short Form, Revised Edition, Translated Version).', 'naboodatabase' ); ?></p>
        </div>
        
        <div class="naboo-versions-wrapper" id="naboo-versions-wrapper">
            <?php
            $linked_versions = is_array( $meta['linked_versions'] ) ? $meta['linked_versions'] : array();
            
            // Fetch all scales for the dropdown
            $all_scales = get_posts( array(
                'post_type'      => 'psych_scale',
                'posts_per_page' => -1,
                'post_status'    => array('publish', 'draft', 'pending', 'private'),
                'orderby'        => 'title',
                'order'          => 'ASC'
            ) );
            
            if ( empty( $linked_versions ) ) {
                $linked_versions[] = array( 'id' => '', 'type' => '' );
            }
            
            foreach ( $linked_versions as $index => $version ) :
                $v_id = isset( $version['id'] ) ? $version['id'] : '';
                $v_type = isset( $version['type'] ) ? $version['type'] : '';
            ?>
                <div class="naboo-version-row" style="display:flex; gap:10px; margin-bottom:10px; align-items:center;">
                    <select name="_naboo_scale_linked_versions[<?php echo $index; ?>][id]" style="flex:2;">
                        <option value=""><?php _e( '— Select Scale —', 'naboodatabase' ); ?></option>
                        <?php foreach ( $all_scales as $scale_post ) : 
                            if ( $scale_post->ID == $post->ID ) continue;
                        ?>
                            <option value="<?php echo esc_attr( $scale_post->ID ); ?>" <?php selected( $v_id, $scale_post->ID ); ?>>
                                <?php echo esc_html( $scale_post->post_title ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <input type="text" name="_naboo_scale_linked_versions[<?php echo $index; ?>][type]" value="<?php echo esc_attr( $v_type ); ?>" placeholder="<?php _e( 'e.g., Short Form, Arabic Translation', 'naboodatabase' ); ?>" style="flex:2;">
                    
                    <button type="button" class="button naboo-remove-version-btn" style="flex:none;"><span class="dashicons dashicons-trash" style="color:#a00;"></span></button>
                </div>
            <?php endforeach; ?>
        </div>
        
        <button type="button" class="button button-primary" id="naboo-add-version-btn" style="margin-top:10px;">
            <span class="dashicons dashicons-plus" style="margin-top:4px;"></span> <?php _e( 'Add Another Version', 'naboodatabase' ); ?>
        </button>
        
        <!-- Template for new row -->
        <script type="text/template" id="naboo-version-row-template">
            <div class="naboo-version-row" style="display:flex; gap:10px; margin-bottom:10px; align-items:center;">
                <select name="_naboo_scale_linked_versions[{{INDEX}}][id]" style="flex:2;">
                    <option value=""><?php _e( '— Select Scale —', 'naboodatabase' ); ?></option>
                    <?php foreach ( $all_scales as $scale_post ) : 
                        if ( $scale_post->ID == $post->ID ) continue;
                    ?>
                        <option value="<?php echo esc_attr( $scale_post->ID ); ?>">
                            <?php echo esc_html( $scale_post->post_title ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <input type="text" name="_naboo_scale_linked_versions[{{INDEX}}][type]" value="" placeholder="<?php _e( 'e.g., Short Form, Arabic Translation', 'naboodatabase' ); ?>" style="flex:2;">
                
                <button type="button" class="button naboo-remove-version-btn" style="flex:none;"><span class="dashicons dashicons-trash" style="color:#a00;"></span></button>
            </div>
        </script>
    </div>

</div><!-- .naboo-metabox-wrapper -->
