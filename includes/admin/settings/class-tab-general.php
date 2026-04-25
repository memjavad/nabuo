<?php
/**
 * Settings Tab: General
 *
 * @package ArabPsychology\NabooDatabase\Admin\Settings
 */

namespace ArabPsychology\NabooDatabase\Admin\Settings;

class Tab_General {

	public function render( $options, $option_name ) {
		?>
		<div class="naboo-admin-grid cols-1">
			<?php $this->render_general_settings_card( $options, $option_name ); ?>
			<?php $this->render_submission_settings_card( $options, $option_name ); ?>
		</div>
		<?php
	}

	private function render_general_settings_card( $options, $option_name ) {
		?>
		<div class="naboo-admin-card">
			<div class="naboo-admin-card-header">
				<span class="naboo-admin-card-icon green">⚙️</span>
				<h3><?php esc_html_e( 'General Settings', 'naboodatabase' ); ?></h3>
			</div>
			<?php $this->render_display_section( $options, $option_name ); ?>
			<?php $this->render_access_control_section( $options, $option_name ); ?>
		</div>
		<?php
	}

	private function render_display_section( $options, $option_name ) {
		?>
		<div class="naboo-form-section">
			<p class="naboo-form-section-title"><?php esc_html_e( 'Display', 'naboodatabase' ); ?></p>

			<div class="naboo-form-row">
				<label for="scales_per_page"><?php esc_html_e( 'Scales Per Page', 'naboodatabase' ); ?></label>
				<input type="number" id="scales_per_page" name="<?php echo esc_attr( $option_name ); ?>[scales_per_page]"
				       min="3" max="100" value="<?php echo absint( $options['scales_per_page'] ?? get_option( 'naboo_scales_per_page', 12 ) ); ?>" style="max-width:100px;" />
				<p class="description"><?php esc_html_e( 'How many scales to show per search results page.', 'naboodatabase' ); ?></p>
			</div>

			<div class="naboo-form-row">
				<label for="search_placeholder"><?php esc_html_e( 'Search Box Placeholder Text', 'naboodatabase' ); ?></label>
				<input type="text" id="search_placeholder" name="<?php echo esc_attr( $option_name ); ?>[search_placeholder]"
				       value="<?php echo esc_attr( $options['search_placeholder'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Search psychological scales…', 'naboodatabase' ); ?>" />
			</div>
		</div>
		<?php
	}

	private function render_access_control_section( $options, $option_name ) {
		?>
		<div class="naboo-form-section">
			<p class="naboo-form-section-title"><?php esc_html_e( 'Access Control', 'naboodatabase' ); ?></p>

			<label class="naboo-toggle-row">
				<input type="checkbox" name="<?php echo esc_attr( $option_name ); ?>[require_login_to_download]" value="1"
				       <?php checked( 1, $options['require_login_to_download'] ?? get_option( 'naboo_require_login_to_download', 0 ) ); ?> />
				<div class="toggle-info">
					<strong><?php esc_html_e( 'Require Login to Download Files', 'naboodatabase' ); ?></strong>
					<span><?php esc_html_e( 'Only logged-in users can download scale files.', 'naboodatabase' ); ?></span>
				</div>
			</label>

			<label class="naboo-toggle-row">
				<input type="checkbox" name="<?php echo esc_attr( $option_name ); ?>[enable_guest_search]" value="1"
				       <?php checked( 1, $options['enable_guest_search'] ?? get_option( 'naboo_enable_guest_search', 1 ) ); ?> />
				<div class="toggle-info">
					<strong><?php esc_html_e( 'Allow Guest Search', 'naboodatabase' ); ?></strong>
					<span><?php esc_html_e( 'Non-logged-in visitors can search the database.', 'naboodatabase' ); ?></span>
				</div>
			</label>

			<label class="naboo-toggle-row">
				<input type="checkbox" name="<?php echo esc_attr( $option_name ); ?>[enable_view_count]" value="1"
				       <?php checked( 1, $options['enable_view_count'] ?? 1 ); ?> />
				<div class="toggle-info">
					<strong><?php esc_html_e( 'Track View Counts', 'naboodatabase' ); ?></strong>
					<span><?php esc_html_e( 'Increment view count each time a scale page is loaded.', 'naboodatabase' ); ?></span>
				</div>
			</label>

			<label class="naboo-toggle-row">
				<input type="checkbox" name="<?php echo esc_attr( $option_name ); ?>[enable_ratings]" value="1"
				       <?php checked( 1, $options['enable_ratings'] ?? get_option( 'naboo_enable_ratings', 1 ) ); ?> />
				<div class="toggle-info">
					<strong><?php esc_html_e( 'Enable Ratings & Reviews', 'naboodatabase' ); ?></strong>
					<span><?php esc_html_e( 'Allow users to rate and review scales on their individual pages.', 'naboodatabase' ); ?></span>
				</div>
			</label>

			<label class="naboo-toggle-row">
				<input type="checkbox" name="<?php echo esc_attr( $option_name ); ?>[require_rating_approval]" value="1"
				       <?php checked( 1, $options['require_rating_approval'] ?? get_option( 'naboo_require_rating_approval', 1 ) ); ?> />
				<div class="toggle-info">
					<strong><?php esc_html_e( 'Require Approval for Ratings', 'naboodatabase' ); ?></strong>
					<span><?php esc_html_e( 'New ratings will be set to "Pending" and must be manually approved.', 'naboodatabase' ); ?></span>
				</div>
			</label>
		</div>
		<?php
	}

	private function render_submission_settings_card( $options, $option_name ) {
		?>
		<div class="naboo-admin-card">
			<div class="naboo-admin-card-header">
				<span class="naboo-admin-card-icon blue">📥</span>
				<h3><?php esc_html_e( 'Submission Settings', 'naboodatabase' ); ?></h3>
			</div>

			<div class="naboo-form-section">
				<div class="naboo-form-row">
					<label for="default_submission_status"><?php esc_html_e( 'Default Submission Status', 'naboodatabase' ); ?></label>
					<select id="default_submission_status" name="<?php echo esc_attr( $option_name ); ?>[default_submission_status]">
						<option value="pending" <?php selected( $options['default_submission_status'] ?? get_option( 'naboo_default_submission_status', 'pending' ), 'pending' ); ?>>
							<?php esc_html_e( 'Pending Review', 'naboodatabase' ); ?>
						</option>
						<option value="draft" <?php selected( $options['default_submission_status'] ?? 'pending', 'draft' ); ?>>
							<?php esc_html_e( 'Draft', 'naboodatabase' ); ?>
						</option>
					</select>
					<p class="description"><?php esc_html_e( 'Status of newly submitted scales before admin review.', 'naboodatabase' ); ?></p>
				</div>

				<label class="naboo-toggle-row">
					<input type="checkbox" name="<?php echo esc_attr( $option_name ); ?>[auto_publish]" value="1"
					       <?php checked( 1, $options['auto_publish'] ?? get_option( 'naboo_auto_publish', 0 ) ); ?> />
					<div class="toggle-info">
						<strong><?php esc_html_e( 'Auto-Publish Submissions', 'naboodatabase' ); ?></strong>
						<span><?php esc_html_e( 'Automatically publish new submissions without manual review. Use with caution.', 'naboodatabase' ); ?></span>
					</div>
				</label>

				<label class="naboo-toggle-row">
					<input type="checkbox" name="<?php echo esc_attr( $option_name ); ?>[submission_notify_admin]" value="1"
					       <?php checked( 1, $options['submission_notify_admin'] ?? get_option( 'naboo_submission_notify_admin', 1 ) ); ?> />
					<div class="toggle-info">
						<strong><?php esc_html_e( 'Notify Admin on New Submission', 'naboodatabase' ); ?></strong>
						<span><?php esc_html_e( 'Send an email to the site admin whenever a new scale is submitted.', 'naboodatabase' ); ?></span>
					</div>
				</label>
			</div>
		<?php
	}

	private function render_display_settings( $options, $option_name ) {
		?>
		<div class="naboo-form-section">
			<p class="naboo-form-section-title"><?php esc_html_e( 'Display', 'naboodatabase' ); ?></p>

			<div class="naboo-form-row">
				<label for="scales_per_page"><?php esc_html_e( 'Scales Per Page', 'naboodatabase' ); ?></label>
				<input type="number" id="scales_per_page" name="<?php echo esc_attr( $option_name ); ?>[scales_per_page]"
				       min="3" max="100" value="<?php echo absint( $options['scales_per_page'] ?? get_option( 'naboo_scales_per_page', 12 ) ); ?>" style="max-width:100px;" />
				<p class="description"><?php esc_html_e( 'How many scales to show per search results page.', 'naboodatabase' ); ?></p>
			</div>

			<div class="naboo-form-row">
				<label for="search_placeholder"><?php esc_html_e( 'Search Box Placeholder Text', 'naboodatabase' ); ?></label>
				<input type="text" id="search_placeholder" name="<?php echo esc_attr( $option_name ); ?>[search_placeholder]"
				       value="<?php echo esc_attr( $options['search_placeholder'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Search psychological scales…', 'naboodatabase' ); ?>" />
			</div>
		</div>
		<?php
	}

	private function render_access_control_settings( $options, $option_name ) {
		?>
		<div class="naboo-form-section">
			<p class="naboo-form-section-title"><?php esc_html_e( 'Access Control', 'naboodatabase' ); ?></p>

			<label class="naboo-toggle-row">
				<input type="checkbox" name="<?php echo esc_attr( $option_name ); ?>[require_login_to_download]" value="1"
				       <?php checked( 1, $options['require_login_to_download'] ?? get_option( 'naboo_require_login_to_download', 0 ) ); ?> />
				<div class="toggle-info">
					<strong><?php esc_html_e( 'Require Login to Download Files', 'naboodatabase' ); ?></strong>
					<span><?php esc_html_e( 'Only logged-in users can download scale files.', 'naboodatabase' ); ?></span>
				</div>
			</label>

			<label class="naboo-toggle-row">
				<input type="checkbox" name="<?php echo esc_attr( $option_name ); ?>[enable_guest_search]" value="1"
				       <?php checked( 1, $options['enable_guest_search'] ?? get_option( 'naboo_enable_guest_search', 1 ) ); ?> />
				<div class="toggle-info">
					<strong><?php esc_html_e( 'Allow Guest Search', 'naboodatabase' ); ?></strong>
					<span><?php esc_html_e( 'Non-logged-in visitors can search the database.', 'naboodatabase' ); ?></span>
				</div>
			</label>

			<label class="naboo-toggle-row">
				<input type="checkbox" name="<?php echo esc_attr( $option_name ); ?>[enable_view_count]" value="1"
				       <?php checked( 1, $options['enable_view_count'] ?? 1 ); ?> />
				<div class="toggle-info">
					<strong><?php esc_html_e( 'Track View Counts', 'naboodatabase' ); ?></strong>
					<span><?php esc_html_e( 'Increment view count each time a scale page is loaded.', 'naboodatabase' ); ?></span>
				</div>
			</label>

			<label class="naboo-toggle-row">
				<input type="checkbox" name="<?php echo esc_attr( $option_name ); ?>[enable_ratings]" value="1"
				       <?php checked( 1, $options['enable_ratings'] ?? get_option( 'naboo_enable_ratings', 1 ) ); ?> />
				<div class="toggle-info">
					<strong><?php esc_html_e( 'Enable Ratings & Reviews', 'naboodatabase' ); ?></strong>
					<span><?php esc_html_e( 'Allow users to rate and review scales on their individual pages.', 'naboodatabase' ); ?></span>
				</div>
			</label>

			<label class="naboo-toggle-row">
				<input type="checkbox" name="<?php echo esc_attr( $option_name ); ?>[require_rating_approval]" value="1"
				       <?php checked( 1, $options['require_rating_approval'] ?? get_option( 'naboo_require_rating_approval', 1 ) ); ?> />
				<div class="toggle-info">
					<strong><?php esc_html_e( 'Require Approval for Ratings', 'naboodatabase' ); ?></strong>
					<span><?php esc_html_e( 'New ratings will be set to "Pending" and must be manually approved.', 'naboodatabase' ); ?></span>
				</div>
			</label>
		</div>
		<?php
	}

	private function render_submission_settings( $options, $option_name ) {
		?>
		<div class="naboo-form-section">
			<div class="naboo-form-row">
				<label for="default_submission_status"><?php esc_html_e( 'Default Submission Status', 'naboodatabase' ); ?></label>
				<select id="default_submission_status" name="<?php echo esc_attr( $option_name ); ?>[default_submission_status]">
					<option value="pending" <?php selected( $options['default_submission_status'] ?? get_option( 'naboo_default_submission_status', 'pending' ), 'pending' ); ?>>
						<?php esc_html_e( 'Pending Review', 'naboodatabase' ); ?>
					</option>
					<option value="draft" <?php selected( $options['default_submission_status'] ?? 'pending', 'draft' ); ?>>
						<?php esc_html_e( 'Draft', 'naboodatabase' ); ?>
					</option>
				</select>
				<p class="description"><?php esc_html_e( 'Status of newly submitted scales before admin review.', 'naboodatabase' ); ?></p>
			</div>

			<label class="naboo-toggle-row">
				<input type="checkbox" name="<?php echo esc_attr( $option_name ); ?>[auto_publish]" value="1"
				       <?php checked( 1, $options['auto_publish'] ?? get_option( 'naboo_auto_publish', 0 ) ); ?> />
				<div class="toggle-info">
					<strong><?php esc_html_e( 'Auto-Publish Submissions', 'naboodatabase' ); ?></strong>
					<span><?php esc_html_e( 'Automatically publish new submissions without manual review. Use with caution.', 'naboodatabase' ); ?></span>
				</div>
			</label>

			<label class="naboo-toggle-row">
				<input type="checkbox" name="<?php echo esc_attr( $option_name ); ?>[submission_notify_admin]" value="1"
				       <?php checked( 1, $options['submission_notify_admin'] ?? get_option( 'naboo_submission_notify_admin', 1 ) ); ?> />
				<div class="toggle-info">
					<strong><?php esc_html_e( 'Notify Admin on New Submission', 'naboodatabase' ); ?></strong>
					<span><?php esc_html_e( 'Send an email to the site admin whenever a new scale is submitted.', 'naboodatabase' ); ?></span>
				</div>
			</label>
		</div>
		<?php
	}

	public function sanitize( $input ) {
		$sanitized = array();
		$sanitized['scales_per_page']           = absint( $input['scales_per_page'] ?? 12 );
		$sanitized['require_login_to_download'] = ! empty( $input['require_login_to_download'] ) ? 1 : 0;
		$sanitized['enable_guest_search']       = ! empty( $input['enable_guest_search'] ) ? 1 : 0;
		$sanitized['enable_view_count']         = ! empty( $input['enable_view_count'] ) ? 1 : 0;
		$sanitized['enable_ratings']            = ! empty( $input['enable_ratings'] ) ? 1 : 0;
		$sanitized['require_rating_approval']   = ! empty( $input['require_rating_approval'] ) ? 1 : 0;
		$sanitized['search_placeholder']        = sanitize_text_field( $input['search_placeholder'] ?? '' );
		
		$sanitized['auto_publish']              = ! empty( $input['auto_publish'] ) ? 1 : 0;
		$sanitized['submission_notify_admin']   = ! empty( $input['submission_notify_admin'] ) ? 1 : 0;
		$sanitized['default_submission_status'] = in_array( $input['default_submission_status'] ?? '', array( 'pending', 'draft' ), true )
			? $input['default_submission_status'] : 'pending';

		update_option( 'naboo_scales_per_page', $sanitized['scales_per_page'] );
		update_option( 'naboo_require_login_to_download', $sanitized['require_login_to_download'] );
		update_option( 'naboo_enable_guest_search', $sanitized['enable_guest_search'] );
		update_option( 'naboo_enable_ratings', $sanitized['enable_ratings'] );
		update_option( 'naboo_require_rating_approval', $sanitized['require_rating_approval'] );
		update_option( 'naboo_auto_publish', $sanitized['auto_publish'] );
		update_option( 'naboo_submission_notify_admin', $sanitized['submission_notify_admin'] );
		update_option( 'naboo_default_submission_status', $sanitized['default_submission_status'] );

		return $sanitized;
	}
}
