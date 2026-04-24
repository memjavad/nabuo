<?php
/**
 * Glossary Renderer - Handles UI rendering for the glossary admin
 *
 * @package ArabPsychology\NabooDatabase\Admin\Glossary
 */

namespace ArabPsychology\NabooDatabase\Admin\Glossary;

/**
 * Glossary_Renderer class
 */
class Glossary_Renderer {

	/**
	 * Render the instructions page
	 */
	public function render_instructions_page() {
		?>
		<div class="wrap naboo-admin-page">
			<div class="naboo-admin-header" style="margin-bottom: 32px; padding: 40px; background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border-radius: 16px; color: white; box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1); position: relative; overflow: hidden;">
				<div style="position: absolute; top: -50px; right: -50px; width: 200px; height: 200px; background: rgba(99, 102, 241, 0.1); filter: blur(80px); border-radius: 50%;"></div>
				<h1 style="color: white !important; font-size: 36px !important; margin: 0 !important; font-weight: 800; letter-spacing: -0.025em; display: flex; align-items: center; gap: 20px;">
					<span style="background: rgba(255,255,255,0.1); width: 64px; height: 64px; display: flex; align-items: center; justify-content: center; border-radius: 16px; backdrop-filter: blur(4px); border: 1px solid rgba(255,255,255,0.1);">📚</span>
					<?php esc_html_e( 'Glossary & Index Guide', 'naboodatabase' ); ?>
				</h1>
				<p style="margin: 16px 0 0 84px !important; color: #94a3b8; font-size: 18px; max-width: 600px; line-height: 1.6;"><?php esc_html_e( 'Learn how to manage your bilingual mental health terminology and automated scale index.', 'naboodatabase' ); ?></p>
			</div>

			<div class="naboo-admin-grid cols-1">
				<div class="naboo-admin-card" style="background: white; border-radius: 20px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);">
					<div class="naboo-admin-card-header" style="padding: 24px 32px; border-bottom: 1px solid #f1f5f9; background: #f8fafc; display: flex; align-items: center; gap: 16px;">
						<span class="naboo-admin-card-icon" style="background: #eef2ff; width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px;">📖</span>
						<h3 style="margin: 0; font-size: 20px; font-weight: 800; color: #1e293b; letter-spacing: -0.01em;"><?php esc_html_e( 'Mastering Your Glossary', 'naboodatabase' ); ?></h3>
					</div>
					<div class="naboo-instructions-content" style="padding: 32px 40px; max-width: 900px; line-height: 1.8; color: #334155; font-size: 15px;">
						<p><?php esc_html_e( 'The Glossary module allows you to build a bilingual (English/Arabic) database of psychological terms with an elegant, searchable frontend interface.', 'naboodatabase' ); ?></p>
						
						<h4 style="margin-top:20px;"><?php esc_html_e( '1. Adding Terms', 'naboodatabase' ); ?></h4>
						<p><?php printf( esc_html__( 'Go to %s and click "Add New". Enter the English term as the title, and the definition in the main editor.', 'naboodatabase' ), '<strong>Glossary</strong>' ); ?></p>
						
						<h4 style="margin-top:20px;"><?php esc_html_e( '2. Bilingual Support', 'naboodatabase' ); ?></h4>
						<p><?php esc_html_e( 'In the "Term Details" box below the editor, you can provide the Arabic translation. The frontend will display both side-by-side.', 'naboodatabase' ); ?></p>
						
						<h4 style="margin-top:20px;"><?php esc_html_e( '3. Displaying the Glossary', 'naboodatabase' ); ?></h4>
						<p><?php esc_html_e( 'Simply paste the following shortcode onto any page:', 'naboodatabase' ); ?></p>
						<code style="display:block; background:#f0f0f1; padding:15px; border-radius:8px; font-weight:bold; margin:10px 0;">[naboo_glossary]</code>
						
						<h4 style="margin-top:20px;"><?php esc_html_e( '4. Customizing Layouts & Content', 'naboodatabase' ); ?></h4>
						<p><?php esc_html_e( 'The glossary is highly customizable via shortcode attributes:', 'naboodatabase' ); ?></p>
						<ul style="list-style:disc; margin-left:20px;">
							<li><code>[naboo_glossary layout="list"]</code> <?php esc_html_e( 'Clean vertical list view.', 'naboodatabase' ); ?></li>
							<li><code>[naboo_glossary post_type="psych_scale"]</code> <?php esc_html_e( 'Show your Scales as an alphabetical list.', 'naboodatabase' ); ?></li>
							<li><code>[naboo_glossary post_type="psych_scale" meta_key="_naboo_scale_author" meta_label="Author"]</code> <?php esc_html_e( 'Show Scales with their Author as a secondary label.', 'naboodatabase' ); ?></li>
						</ul>

						<h4 style="margin-top:20px;"><?php esc_html_e( '5. Advanced usage', 'naboodatabase' ); ?></h4>
						<p><?php esc_html_e( 'You can display any content type by specifying its slug. The system will automatically use the post title and excerpt.', 'naboodatabase' ); ?></p>
						<p><?php printf( esc_html__( 'Visit the %s → Glossary tab to enable/disable the feature and set default layouts.', 'naboodatabase' ), '<strong>Naboo Settings</strong>' ); ?></p>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the settings page
	 */
	public function render_settings_page() {
		$option_name = 'naboodatabase_plugin_settings';
		$options     = get_option( $option_name, array() );

		// Enqueue Inter font
		echo '<link rel="preconnect" href="https://fonts.googleapis.com">
			  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
			  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">';

		?>
		<div class="wrap naboo-admin-page naboo-glossary-settings-wrap" style="font-family: 'Inter', sans-serif;">
			<?php $this->render_settings_header(); ?>

			<form method="post" action="options.php">
				<?php settings_fields( 'naboodatabase_settings_group' ); ?>
				<input type="hidden" name="naboo_settings_tab" value="glossary">

				<?php $this->render_glossary_configuration_card( $options, $option_name ); ?>
				<?php $this->render_scale_index_engine_card( $option_name ); ?>
				<?php $this->render_save_button(); ?>
			</form>
		</div>

		<?php
		$this->render_settings_styles();
	}


	/**
	 * Render the settings header
	 */
	private function render_settings_header() {
		?>
		<!-- Header -->
		<div class="naboo-admin-header" style="margin-bottom: 32px; padding: 40px; background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border-radius: 16px; color: white; box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1); position: relative; overflow: hidden;">
			<div style="position: absolute; top: -50px; right: -50px; width: 200px; height: 200px; background: rgba(99, 102, 241, 0.1); filter: blur(80px); border-radius: 50%;"></div>
			<h1 style="color: white !important; font-size: 36px !important; margin: 0 !important; font-weight: 800; letter-spacing: -0.025em; display: flex; align-items: center; gap: 20px;">
				<span style="background: rgba(255,255,255,0.1); width: 64px; height: 64px; display: flex; align-items: center; justify-content: center; border-radius: 16px; backdrop-filter: blur(4px); border: 1px solid rgba(255,255,255,0.1);">📚</span>
				<?php esc_html_e( 'Glossary & Index', 'naboodatabase' ); ?>
			</h1>
			<p style="margin: 16px 0 0 84px !important; color: #94a3b8; font-size: 18px; max-width: 600px; line-height: 1.6;"><?php esc_html_e( 'Manage how your term collection and automated scale indexing appear to users. Enable bilingual support and customize layouts.', 'naboodatabase' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Render glossary configuration card
	 *
	 * @param array  $options Plugin options.
	 * @param string $option_name Option name.
	 */
	private function render_glossary_configuration_card( $options, $option_name ) {
		?>
		<!-- Glossary Section -->
		<div class="naboo-admin-card" style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; margin-bottom: 32px; overflow: hidden; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);">
			<div class="naboo-admin-card-header" style="padding: 20px 24px; border-bottom: 1px solid #f1f5f9; background: #f8fafc; display: flex; align-items: center; gap: 12px;">
				<span style="background: #e0f2fe; color: #0284c7; width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px;">📖</span>
				<h2 style="margin: 0 !important; font-size: 18px !important; font-weight: 800 !important; color: #1e293b;"><?php esc_html_e( 'Glossary Configuration', 'naboodatabase' ); ?></h2>
			</div>
			<div class="section-body">
				<div class="field-row">
					<label class="field-label"><?php esc_html_e( 'Enable Glossary Module', 'naboodatabase' ); ?></label>
					<?php $this->render_toggle( $option_name . '[enable_glossary]', $options['enable_glossary'] ?? 0 ); ?>
				</div>

				<div class="field-row">
					<label class="field-label"><?php esc_html_e( 'Glossary Main Page', 'naboodatabase' ); ?></label>
					<?php wp_dropdown_pages( array( 'name' => $option_name . '[glossary_page]', 'selected' => $options['glossary_page'] ?? 0, 'show_option_none' => __( '-- Select Page --', 'naboodatabase' ) ) ); ?>
				</div>

				<div class="field-row">
					<label class="field-label"><?php esc_html_e( 'Default Layout', 'naboodatabase' ); ?></label>
					<select name="<?php echo esc_attr( $option_name ); ?>[glossary_layout]">
						<option value="grid" <?php selected( $options['glossary_layout'] ?? '', 'grid' ); ?>>Grid Cards</option>
						<option value="list" <?php selected( $options['glossary_layout'] ?? '', 'list' ); ?>>Clean List</option>
						<option value="compact" <?php selected( $options['glossary_layout'] ?? '', 'compact' ); ?>>Compact Rows</option>
					</select>
				</div>

				<div class="field-row">
					<label class="field-label"><?php esc_html_e( 'Terms Per Page', 'naboodatabase' ); ?></label>
					<input type="number" name="<?php echo esc_attr( $option_name ); ?>[glossary_per_page]" value="<?php echo esc_attr( $options['glossary_per_page'] ?? 50 ); ?>" min="5" max="200">
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render scale index engine card
	 *
	 * @param string $option_name Option name.
	 */
	private function render_scale_index_engine_card( $option_name ) {
		?>
		<!-- Scale Index Section -->
		<div class="naboo-admin-card" style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; margin-bottom: 32px; overflow: hidden; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);">
			<div class="naboo-admin-card-header" style="padding: 20px 24px; border-bottom: 1px solid #f1f5f9; background: #f8fafc; display: flex; align-items: center; gap: 12px;">
				<span style="background: #fef2f2; color: #dc2626; width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px;">📑</span>
				<h2 style="margin: 0 !important; font-size: 18px !important; font-weight: 800 !important; color: #1e293b;"><?php esc_html_e( 'Scale Index Engine', 'naboodatabase' ); ?></h2>
			</div>
			<div class="section-body">
				<div class="field-row">
					<label class="field-label"><?php esc_html_e( 'Enable Automated Scale Index', 'naboodatabase' ); ?></label>
					<?php $this->render_toggle( $option_name . '[scale_index_enabled]', get_option( 'naboo_scale_index_enabled', 0 ) ); ?>
				</div>

				<div class="field-row">
					<label class="field-label"><?php esc_html_e( 'Index URL Slug', 'naboodatabase' ); ?></label>
					<input type="text" name="<?php echo esc_attr( $option_name ); ?>[scale_index_slug]" value="<?php echo esc_attr( get_option( 'naboo_scale_index_slug', 'scales-index' ) ); ?>">
					<p class="field-description"><?php printf( esc_html__( 'Index will be accessible at %s', 'naboodatabase' ), '<code>' . home_url( '/' ) . '<strong>' . get_option( 'naboo_scale_index_slug', 'scales-index' ) . '</strong></code>' ); ?></p>
				</div>

				<div class="field-row">
					<label class="field-label"><?php esc_html_e( 'Page Title', 'naboodatabase' ); ?></label>
					<input type="text" name="<?php echo esc_attr( $option_name ); ?>[scale_index_title]" value="<?php echo esc_attr( get_option( 'naboo_scale_index_title', 'Scale Index' ) ); ?>">
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render save button
	 */
	private function render_save_button() {
		?>
		<div class="naboo-save-bar" style="position: sticky; bottom: 24px; z-index: 100; background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(12px); padding: 24px 40px; border-radius: 20px; border: 1px solid rgba(226, 232, 240, 0.8); box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1); display: flex; justify-content: flex-end; align-items: center; margin-top: 60px;">
			<button type="submit" class="naboo-btn" style="background: #4f46e5; color: white; border: none; padding: 14px 32px; border-radius: 12px; font-weight: 700; font-size: 15px; cursor: pointer; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2);">
				<?php esc_html_e( 'Save Glossary Engine Settings', 'naboodatabase' ); ?>
			</button>
		</div>
		<?php
	}

	/**
	 * Render settings styles
	 */
	private function render_settings_styles() {
		?>
		<style>
			.field-row { margin-bottom: 24px; }
			.field-label { display: block; font-weight: 700; font-size: 14px; color: #1e293b; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.05em; }
			.field-description { font-size: 13px; color: #64748b; margin-top: 8px; line-height: 1.5; }
			input[type="text"], input[type="number"], select { width: 100%; padding: 12px 16px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 15px; transition: all 0.2s; }
			input[type="text"]:focus, select:focus { border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); outline: none; }
			
			.naboo-btn { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
			.naboo-btn:hover { transform: translateY(-1px); filter: brightness(1.1); }

			.naboo-toggle { position: relative; display: inline-block; width: 48px; height: 26px; }
			.naboo-toggle input { opacity: 0; width: 0; height: 0; }
			.naboo-toggle-slider { position: absolute; cursor: pointer; inset: 0; background-color: #e2e8f0; transition: .4s; border-radius: 24px; border: 1px solid #cbd5e1; }
			.naboo-toggle-slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
			input:checked + .naboo-toggle-slider { background-color: #4f46e5; border-color: #4f46e5; }
			input:checked + .naboo-toggle-slider:before { transform: translateX(22px); }
		</style>
		<?php
	}

	/**
	 * Helper: Render elegant toggle
	 *
	 * @param string $name Toggle name.
	 * @param mixed  $value Toggle value.
	 */
	private function render_toggle( $name, $value ) {
		?>
		<label class="naboo-toggle">
			<input type="checkbox" name="<?php echo esc_attr( $name ); ?>" value="1" <?php checked( $value, 1 ); ?>>
			<span class="naboo-toggle-slider"></span>
		</label>
		<?php
	}
}
