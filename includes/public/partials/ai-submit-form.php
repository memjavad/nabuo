<?php
/**
 * AI Submit Form Partial
 * 
 * Loaded by the [naboo_ai_submit] shortcode.
 */
?>
<div class="naboo-ai-submit-container">
	<!-- 1. PDF Upload Zone -->
	<div id="naboo-ai-upload-zone" class="naboo-ai-upload-zone">
		<div class="naboo-ai-upload-inner">
			<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="naboo-ai-icon"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
			<h3><?php _e( 'Upload Scale Study (PDF)', 'naboodatabase' ); ?></h3>
			<p><?php _e( 'Drag and drop a PDF file here, or click to select.', 'naboodatabase' ); ?></p>
			<input type="file" id="naboo-ai-file-input" accept="application/pdf" style="display:none;">
			<button type="button" class="naboo-btn naboo-btn-primary" id="naboo-ai-select-btn">
				<?php _e( 'Select PDF File', 'naboodatabase' ); ?>
			</button>
		</div>
		<div id="naboo-ai-loading" class="naboo-ai-loading" style="display:none;">
			<div class="naboo-spinner"></div>
			<p><?php _e( 'Extracting scale data using Google AI... Please wait, this may take a moment.', 'naboodatabase' ); ?></p>
		</div>
	</div>

	<!-- 2. Form (Hidden Initially) -->
	<div id="naboo-ai-form-wrapper" class="naboo-ai-form-wrapper" style="display:none;">
		<div class="naboo-notice info" style="margin-bottom: 20px;">
			<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; color: #16a34a;"><polyline points="20 6 9 17 4 12"></polyline></svg>
			<?php _e( 'Data extracted successfully! Please review the details below. Some fields may need manual correction.', 'naboodatabase' ); ?>
		</div>

		<style>
		.naboo-ai-refine-btn {
			background: linear-gradient(135deg, #0d9488, #0f766e);
			color: #fff;
			border: none;
			border-radius: 4px;
			padding: 2px 6px;
			font-size: 11px;
			cursor: pointer;
			margin-left: 8px;
			display: inline-flex;
			align-items: center;
			gap: 4px;
			box-shadow: 0 1px 3px rgba(0,0,0,0.1);
			transition: all 0.2s ease;
			vertical-align: middle;
			line-height: 1;
		}
		.naboo-ai-refine-btn:hover {
			transform: translateY(-1px);
			box-shadow: 0 2px 4px rgba(0,0,0,0.15);
		}
		.naboo-ai-refine-btn:disabled {
			opacity: 0.7;
			cursor: not-allowed;
		}
		.naboo-ai-refine-btn svg {
			width: 12px;
			height: 12px;
		}
		.naboo-spin {
			animation: spin 1s linear infinite;
		}
		@keyframes spin { 100% { transform: rotate(360deg); } }
		</style>

		<form id="naboo-ai-submit-form" class="naboo-submission-form">
			<input type="hidden" id="ai_attachment_id" name="attachment_id" value="">
			
			<!-- Security Honeypot -->
			<input type="text" name="naboo_website_url" id="naboo_website_url" value="" style="display:none !important;" autocomplete="off" tabindex="-1">
			
			<div class="naboo-form-section">
				<h3><?php _e( 'Basic Information', 'naboodatabase' ); ?></h3>
				
				<div class="naboo-form-row">
					<label for="ai_scale_title"><?php _e( 'Scale Title *', 'naboodatabase' ); ?> <button type="button" class="naboo-ai-refine-btn" data-field="title" title="<?php esc_attr_e( 'Refine with AI', 'naboodatabase' ); ?>"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg> AI</button></label>
					<input type="text" id="ai_scale_title" name="scale_title" required class="widefat">
				</div>

				<div class="naboo-form-row two-col">
					<div>
						<label for="ai_scale_construct"><?php _e( 'Construct Measured', 'naboodatabase' ); ?> <button type="button" class="naboo-ai-refine-btn" data-field="construct"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg> AI</button></label>
						<input type="text" id="ai_scale_construct" name="scale_construct" class="widefat">
					</div>
					<div>
						<label for="ai_scale_year"><?php _e( 'Publication Year', 'naboodatabase' ); ?> <button type="button" class="naboo-ai-refine-btn" data-field="year"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg> AI</button></label>
						<input type="number" id="ai_scale_year" name="scale_year" class="widefat">
					</div>
				</div>

				<div class="naboo-form-row">
					<label for="ai_scale_purpose"><?php _e( 'Purpose', 'naboodatabase' ); ?> <button type="button" class="naboo-ai-refine-btn" data-field="purpose"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg> AI</button></label>
					<textarea id="ai_scale_purpose" name="scale_purpose" rows="2" class="widefat"></textarea>
				</div>
				
				<div class="naboo-form-row">
					<label for="ai_scale_keywords"><?php _e( 'Keywords', 'naboodatabase' ); ?> <button type="button" class="naboo-ai-refine-btn" data-field="keywords"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg> AI</button></label>
					<input type="text" id="ai_scale_keywords" name="scale_keywords" class="widefat" placeholder="<?php esc_attr_e( 'e.g., depression, anxiety, self-report...', 'naboodatabase' ); ?>">
				</div>
				
				<div class="naboo-form-row">
					<label for="ai_scale_abstract"><?php _e( 'Abstract / Description', 'naboodatabase' ); ?> <button type="button" class="naboo-ai-refine-btn" data-field="abstract"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg> AI</button></label>
					<textarea id="ai_scale_abstract" name="scale_abstract" rows="4" class="widefat"></textarea>
				</div>
			</div>

			<div class="naboo-form-section">
				<h3><?php _e( 'Instrument Details', 'naboodatabase' ); ?></h3>

				<div class="naboo-form-row three-col">
					<div>
						<label for="ai_scale_items"><?php _e( 'Number of Items', 'naboodatabase' ); ?> <button type="button" class="naboo-ai-refine-btn" data-field="items"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg> AI</button></label>
						<input type="number" id="ai_scale_items" name="scale_items" class="widefat">
					</div>
					<div>
						<label for="ai_scale_language"><?php _e( 'Language', 'naboodatabase' ); ?> <button type="button" class="naboo-ai-refine-btn" data-field="language"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg> AI</button></label>
						<input type="text" id="ai_scale_language" name="scale_language" class="widefat">
					</div>
					<div>
						<label for="ai_scale_test_type"><?php _e( 'Test Type (Legacy)', 'naboodatabase' ); ?> <button type="button" class="naboo-ai-refine-btn" data-field="test_type"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg> AI</button></label>
						<input type="text" id="ai_scale_test_type" name="scale_test_type" class="widefat">
					</div>
				</div>

				<div class="naboo-form-row two-col">
					<div>
						<label for="ai_scale_administration_method"><?php _e( 'Administration Method', 'naboodatabase' ); ?> <button type="button" class="naboo-ai-refine-btn" data-field="administration_method"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg> AI</button></label>
						<input type="text" id="ai_scale_administration_method" name="scale_administration_method" class="widefat">
					</div>
					<div>
						<label for="ai_scale_instrument_type"><?php _e( 'Instrument Type', 'naboodatabase' ); ?> <button type="button" class="naboo-ai-refine-btn" data-field="instrument_type"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg> AI</button></label>
						<input type="text" id="ai_scale_instrument_type" name="scale_instrument_type" class="widefat">
					</div>
				</div>

				<div class="naboo-form-row two-col">
					<div>
						<label for="ai_scale_format"><?php _e( 'Response Format', 'naboodatabase' ); ?> <button type="button" class="naboo-ai-refine-btn" data-field="format"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg> AI</button></label>
						<input type="text" id="ai_scale_format" name="scale_format" class="widefat">
					</div>
					<div>
						<label for="ai_scale_methodology"><?php _e( 'Methodology', 'naboodatabase' ); ?> <button type="button" class="naboo-ai-refine-btn" data-field="methodology"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg> AI</button></label>
						<input type="text" id="ai_scale_methodology" name="scale_methodology" class="widefat">
					</div>
				</div>

				<div class="naboo-form-row">
					<label for="ai_scale_items_list"><?php _e( 'Items List', 'naboodatabase' ); ?> <button type="button" class="naboo-ai-refine-btn" data-field="items_list"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg> AI</button></label>
					<textarea id="ai_scale_items_list" name="scale_items_list" rows="5" class="widefat" placeholder="<?php esc_attr_e( 'Review the extracted scale items here...', 'naboodatabase' ); ?>"></textarea>
				</div>
			</div>

			<div class="naboo-form-section">
				<h3><?php _e( 'Scoring Details', 'naboodatabase' ); ?></h3>
				
				<div class="naboo-form-row">
					<label for="ai_scale_scoring_rules"><?php _e( 'Scoring Rules', 'naboodatabase' ); ?> <button type="button" class="naboo-ai-refine-btn" data-field="scoring_rules"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg> AI</button></label>
					<textarea id="ai_scale_scoring_rules" name="scale_scoring_rules" rows="3" class="widefat" placeholder="<?php esc_attr_e( 'e.g., Reverse scoring items, subscales sums...', 'naboodatabase' ); ?>"></textarea>
				</div>

				<div class="naboo-form-row">
					<label for="ai_scale_r_code"><?php _e( 'R Code for Auto-Scoring', 'naboodatabase' ); ?> <button type="button" class="naboo-ai-refine-btn" data-field="r_code"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg> AI</button></label>
					<textarea id="ai_scale_r_code" name="scale_r_code" rows="4" class="widefat" style="font-family: monospace;" placeholder="<?php esc_attr_e( 'R code snippet for calculating totals...', 'naboodatabase' ); ?>"></textarea>
				</div>
			</div>

			<div class="naboo-form-section">
				<h3><?php _e( 'Psychometrics', 'naboodatabase' ); ?></h3>
				
				<div class="naboo-form-row">
					<label for="ai_scale_reliability"><?php _e( 'Reliability', 'naboodatabase' ); ?> <button type="button" class="naboo-ai-refine-btn" data-field="reliability"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg> AI</button></label>
					<textarea id="ai_scale_reliability" name="scale_reliability" rows="3" class="widefat"></textarea>
				</div>

				<div class="naboo-form-row">
					<label for="ai_scale_validity"><?php _e( 'Validity', 'naboodatabase' ); ?> <button type="button" class="naboo-ai-refine-btn" data-field="validity"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg> AI</button></label>
					<textarea id="ai_scale_validity" name="scale_validity" rows="3" class="widefat"></textarea>
				</div>

				<div class="naboo-form-row">
					<label for="ai_scale_factor_analysis"><?php _e( 'Factor Analysis', 'naboodatabase' ); ?> <button type="button" class="naboo-ai-refine-btn" data-field="factor_analysis"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg> AI</button></label>
					<textarea id="ai_scale_factor_analysis" name="scale_factor_analysis" rows="3" class="widefat"></textarea>
				</div>
			</div>

			<div class="naboo-form-section">
				<h3><?php _e( 'Population & Authors', 'naboodatabase' ); ?></h3>
				
				<div class="naboo-form-row two-col">
					<div>
						<label for="ai_scale_population"><?php _e( 'Target Population', 'naboodatabase' ); ?> <button type="button" class="naboo-ai-refine-btn" data-field="population"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg> AI</button></label>
						<input type="text" id="ai_scale_population" name="scale_population" class="widefat">
					</div>
					<div>
						<label for="ai_scale_age_group"><?php _e( 'Age Group', 'naboodatabase' ); ?> <button type="button" class="naboo-ai-refine-btn" data-field="age_group"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg> AI</button></label>
						<input type="text" id="ai_scale_age_group" name="scale_age_group" class="widefat">
					</div>
				</div>

				<div class="naboo-form-row">
					<label for="ai_scale_authors"><?php _e( 'Scale Authors (Names)', 'naboodatabase' ); ?> <button type="button" class="naboo-ai-refine-btn" data-field="authors"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg> AI</button></label>
					<input type="text" id="ai_scale_authors" name="scale_authors" class="widefat" placeholder="<?php esc_attr_e( 'e.g., John Doe, Jane Smith', 'naboodatabase' ); ?>">
				</div>

				<div class="naboo-form-row">
					<label for="ai_scale_author_details"><?php _e( 'Author Details & Affiliations', 'naboodatabase' ); ?> <button type="button" class="naboo-ai-refine-btn" data-field="author_details"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg> AI</button></label>
					<textarea id="ai_scale_author_details" name="scale_author_details" rows="3" class="widefat"></textarea>
				</div>
                
                <div class="naboo-form-row two-col">
					<div>
						<label for="ai_scale_author_email"><?php _e( 'Author Email', 'naboodatabase' ); ?> <button type="button" class="naboo-ai-refine-btn" data-field="author_email"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg> AI</button></label>
						<input type="email" id="ai_scale_author_email" name="scale_author_email" class="widefat">
					</div>
					<div>
						<label for="ai_scale_author_orcid"><?php _e( 'Author ORCID', 'naboodatabase' ); ?> <button type="button" class="naboo-ai-refine-btn" data-field="author_orcid"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg> AI</button></label>
						<input type="text" id="ai_scale_author_orcid" name="scale_author_orcid" class="widefat" placeholder="e.g., 0000-0000-0000-0000">
					</div>
				</div>
			</div>

			<div class="naboo-form-actions" style="margin-top: 20px; text-align: right;">
				<button type="button" class="naboo-btn naboo-btn-secondary" id="naboo-ai-restart-btn"><?php _e( 'Start Over', 'naboodatabase' ); ?></button>
				<button type="submit" class="naboo-btn naboo-btn-primary" id="naboo-ai-final-submit-btn">
					<?php _e( 'Submit Scale to Database', 'naboodatabase' ); ?>
				</button>
			</div>
		</form>
	</div>
</div>
