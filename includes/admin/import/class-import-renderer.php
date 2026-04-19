<?php
/**
 * Import Renderer - Handles UI rendering for the bulk import tool
 *
 * @package ArabPsychology\NabooDatabase\Admin\Import
 */

namespace ArabPsychology\NabooDatabase\Admin\Import;

/**
 * Import_Renderer class
 */
class Import_Renderer {

	/**
	 * Plugin name
	 * @var string
	 */
	private $plugin_name;

	/**
	 * Plugin version
	 * @var string
	 */
	private $version;

	/**
	 * Constructor
	 *
	 * @param string $plugin_name Plugin name.
	 * @param string $version     Plugin version.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Enqueue Admin Scripts
	 *
	 * @param string $hook Admin page hook.
	 */
	public function enqueue_scripts( $hook ) {
		if ( false === strpos( $hook, 'naboo-bulk-import' ) ) {
			return;
		}

		wp_enqueue_script(
			$this->plugin_name . '-bulk-import',
			plugin_dir_url( dirname( __FILE__ ) ) . 'js/bulk-import-admin.js',
			array( 'jquery', 'wp-api-fetch' ),
			$this->version,
			true
		);

		wp_localize_script(
			$this->plugin_name . '-bulk-import',
			'apaBulkImport',
			array(
				'apiUrl' => rest_url( 'apa/v1/import' ),
				'nonce'  => wp_create_nonce( 'wp_rest' ),
			)
		);

		wp_enqueue_style(
			$this->plugin_name . '-bulk-import',
			plugin_dir_url( dirname( __FILE__ ) ) . 'css/bulk-import-admin.css',
			array(),
			$this->version
		);
	}

	/**
	 * Render Admin Page UI
	 */
	public function render_page() {
		?>
		<div class="wrap naboo-admin-wrap">
			<h1><?php esc_html_e( 'Bulk Import Scales', 'naboodatabase' ); ?></h1>
			
			<div class="naboo-bulk-import-container">
				
				<div class="naboo-import-card">
					<h2><?php esc_html_e( 'Upload CSV or JSON', 'naboodatabase' ); ?></h2>
					<p><?php esc_html_e( 'Upload a data file to automatically import new scales. Ensure formatting matches the required template.', 'naboodatabase' ); ?></p>
					
					<form id="naboo-bulk-import-form">
						<div class="naboo-file-dropzone" id="naboo-dropzone">
							<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="naboo-dropzone-icon"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
							<p><?php esc_html_e( 'Drag and drop file here or click to browse', 'naboodatabase' ); ?></p>
							<input type="file" id="naboo-import-file" name="file" accept=".csv,.json" style="display:none;">
							<span class="naboo-selected-file"></span>
						</div>

						<div class="naboo-import-actions">
							<button type="button" id="naboo-validate-btn" class="button button-secondary" disabled><?php esc_html_e( 'Validate Content', 'naboodatabase' ); ?></button>
							<button type="submit" id="naboo-import-btn" class="button button-primary" disabled><?php esc_html_e( 'Start Import Process', 'naboodatabase' ); ?></button>
						</div>
					</form>
					
					<div id="naboo-import-progress" style="display: none;">
						<p class="naboo-import-status-text"><?php esc_html_e( 'Processing...', 'naboodatabase' ); ?></p>
						<div class="naboo-progress-bar-container">
							<div class="naboo-progress-bar" style="width: 0%"></div>
						</div>
					</div>

					<div id="naboo-import-results" style="display: none;">
						<!-- Result log dumped here -->
					</div>
				</div>

				<div class="naboo-import-card">
					<h2><?php esc_html_e( 'Format Rules', 'naboodatabase' ); ?></h2>
					<p><?php esc_html_e( 'Ensure your CSV headers map precisely to these keys:', 'naboodatabase' ); ?></p>
					<ul class="naboo-rules-list">
						<li><code>title</code> - <b><?php esc_html_e( 'Required', 'naboodatabase' ); ?></b>. The name of the scale.</li>
						<li><code>description</code> - The content text.</li>
						<li><code>items</code> - integer</li>
						<li><code>reliability</code> - string</li>
						<li><code>validity</code> - string</li>
						<li><code>year</code> - integer</li>
						<li><code>language</code> - string</li>
						<li><code>population</code> - string</li>
						<li><code>category</code> - Must exactly match an existing Category term name.</li>
						<li><code>author</code> - Must exactly match an existing Author term name.</li>
					</ul>
				</div>

			</div>
		</div>
		<?php
	}
}
