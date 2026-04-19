<?php if ( isset( $message ) ) echo $message; ?>

<div class="naboo-submission-form-wrapper">
    <div class="naboo-submission-form-header">
        <h2><?php _e( 'Contribute a Scale', 'naboodatabase' ); ?></h2>
        <p><?php _e( 'Help us build a comprehensive database by sharing your psychological scales', 'naboodatabase' ); ?></p>
    </div>

    <form id="naboo-submission-form" method="post" action="" enctype="multipart/form-data" class="naboo-submission-form">
        <?php wp_nonce_field( 'naboo_submit_scale', 'naboo_submit_scale_nonce' ); ?>
        
        <!-- Security Honeypot -->
        <input type="text" name="naboo_website_url" id="naboo_website_url" value="" style="display:none !important;" autocomplete="off" tabindex="-1">
        
        <!-- Basic Information Section -->
        <fieldset class="naboo-form-section">
            <legend class="naboo-form-section-title">
                <span class="naboo-section-icon">📋</span>
                <?php _e( 'Basic Information', 'naboodatabase' ); ?>
            </legend>
            
            <div class="naboo-form-row">
                <label for="scale_title">
                    <?php _e( 'Scale Title', 'naboodatabase' ); ?>
                    <span class="naboo-required">*</span>
                </label>
                <input type="text" name="scale_title" id="scale_title" required placeholder="<?php _e( 'Enter the full name of the scale', 'naboodatabase' ); ?>">
                <p class="naboo-form-help"><?php _e( 'The official or commonly used name of the psychological scale', 'naboodatabase' ); ?></p>
            </div>

            <div class="naboo-form-row">
                <label for="scale_description">
                    <?php _e( 'Description / Abstract', 'naboodatabase' ); ?>
                    <span class="naboo-required">*</span>
                </label>
                <textarea name="scale_description" id="scale_description" rows="6" required placeholder="<?php _e( 'Provide a brief summary of the scale...', 'naboodatabase' ); ?>"></textarea>
                <p class="naboo-form-help"><?php _e( 'Include the purpose, theoretical framework, and key features of the scale', 'naboodatabase' ); ?></p>
            </div>

            <div class="naboo-form-row">
                <label for="scale_category"><?php _e( 'Category', 'naboodatabase' ); ?></label>
                <?php
                wp_dropdown_categories( array(
                    'show_option_none' => __( '— Select Category —', 'naboodatabase' ),
                    'taxonomy'         => 'scale_category',
                    'name'             => 'scale_category',
                    'id'               => 'scale_category',
                    'value_field'      => 'term_id',
                    'hide_empty'       => 0,
                    'class'            => 'naboo-select-field'
                ) );
                ?>
                <p class="naboo-form-help"><?php _e( 'Select the primary category that best fits this scale', 'naboodatabase' ); ?></p>
            </div>
        </fieldset>

        <!-- Scale Properties Section -->
        <fieldset class="naboo-form-section">
            <legend class="naboo-form-section-title">
                <span class="naboo-section-icon">⚙️</span>
                <?php _e( 'Scale Properties', 'naboodatabase' ); ?>
            </legend>

            <div class="naboo-form-row-group">
                <div class="naboo-form-col">
                    <label for="scale_items"><?php _e( 'Number of Items', 'naboodatabase' ); ?></label>
                    <input type="number" name="scale_items" id="scale_items" placeholder="<?php _e( 'e.g., 20', 'naboodatabase' ); ?>" min="1">
                    <p class="naboo-form-help"><?php _e( 'Total number of items in the scale', 'naboodatabase' ); ?></p>
                </div>

                <div class="naboo-form-col">
                    <label for="scale_year"><?php _e( 'Year of Publication', 'naboodatabase' ); ?></label>
                    <input type="number" name="scale_year" id="scale_year" placeholder="<?php _e( 'e.g., 2020', 'naboodatabase' ); ?>" min="1900" max="<?php echo date( 'Y' ); ?>">
                    <p class="naboo-form-help"><?php _e( 'When was the scale originally published?', 'naboodatabase' ); ?></p>
                </div>
            </div>

            <div class="naboo-form-row">
                <label for="scale_language"><?php _e( 'Original Language', 'naboodatabase' ); ?></label>
                <input type="text" name="scale_language" id="scale_language" placeholder="<?php _e( 'e.g., English, Arabic', 'naboodatabase' ); ?>">
                <p class="naboo-form-help"><?php _e( 'The language in which the scale was originally developed', 'naboodatabase' ); ?></p>
            </div>

            <div class="naboo-form-row">
                <label for="scale_population"><?php _e( 'Target Population', 'naboodatabase' ); ?></label>
                <input type="text" name="scale_population" id="scale_population" placeholder="<?php _e( 'e.g., Adolescents, Clinical samples', 'naboodatabase' ); ?>">
                <p class="naboo-form-help"><?php _e( 'The population group this scale is designed for', 'naboodatabase' ); ?></p>
            </div>
        </fieldset>

        <!-- Psychometric Properties Section -->
        <fieldset class="naboo-form-section">
            <legend class="naboo-form-section-title">
                <span class="naboo-section-icon">📊</span>
                <?php _e( 'Psychometric Properties', 'naboodatabase' ); ?>
            </legend>

            <div class="naboo-form-row">
                <label for="scale_reliability"><?php _e( 'Reliability (Cronbach\'s Alpha / ICC)', 'naboodatabase' ); ?></label>
                <input type="text" name="scale_reliability" id="scale_reliability" placeholder="<?php _e( 'e.g., α = 0.82', 'naboodatabase' ); ?>">
                <p class="naboo-form-help"><?php _e( 'Internal consistency or test-retest reliability values', 'naboodatabase' ); ?></p>
            </div>

            <div class="naboo-form-row">
                <label for="scale_validity"><?php _e( 'Validity Evidence', 'naboodatabase' ); ?></label>
                <textarea name="scale_validity" id="scale_validity" rows="4" placeholder="<?php _e( 'Describe convergent, discriminant, and construct validity...', 'naboodatabase' ); ?>"></textarea>
                <p class="naboo-form-help"><?php _e( 'Include information about construct, convergent, and discriminant validity', 'naboodatabase' ); ?></p>
            </div>
        </fieldset>

        <!-- Documentation Section -->
        <fieldset class="naboo-form-section">
            <legend class="naboo-form-section-title">
                <span class="naboo-section-icon">📎</span>
                <?php _e( 'Documentation', 'naboodatabase' ); ?>
            </legend>

            <div class="naboo-form-row">
                <label for="scale_file"><?php _e( 'Upload Scale Document (PDF/Doc)', 'naboodatabase' ); ?></label>
                <div class="naboo-file-upload">
                    <input type="file" name="scale_file" id="scale_file" accept=".pdf,.doc,.docx">
                    <p class="naboo-form-help"><?php _e( 'Upload a PDF or Word document containing the scale. Maximum file size: 2MB.', 'naboodatabase' ); ?></p>
                </div>
            </div>
        </fieldset>

        <div class="naboo-form-submit">
            <button type="submit" class="naboo-btn-primary">
                <span class="naboo-btn-icon">✓</span>
                <?php _e( 'Submit Scale for Review', 'naboodatabase' ); ?>
            </button>
            <p class="naboo-form-notice"><?php _e( 'Your submission will be reviewed by our team before being published to the database.', 'naboodatabase' ); ?></p>
        </div>
    </form>
</div>
