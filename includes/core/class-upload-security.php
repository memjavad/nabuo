<?php
/**
 * Naboo Database Upload Security
 * Secures the WordPress uploads directory against malicious executable files.
 *
 * @package ArabPsychology\NabooDatabase\Core
 */

namespace ArabPsychology\NabooDatabase\Core;

/**
 * Class Upload_Security
 */
class Upload_Security {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Initialization
	}

	/**
	 * Hooked to admin_init to secure the main WP upload directory.
	 */
	public function init_secure_upload_dir() {
		$upload_dir_info = wp_get_upload_dir();
		if ( empty( $upload_dir_info['error'] ) && ! empty( $upload_dir_info['basedir'] ) ) {
			$this->secure_upload_dir( $upload_dir_info['basedir'] );
		}
	}

	/**
	 * Creates an .htaccess file in the given upload directory to prevent execution of PHP
	 *
	 * @param string $upload_dir Path to the upload directory.
	 */
	public function secure_upload_dir( $upload_dir ) {
		if ( ! is_dir( $upload_dir ) ) {
			return; // Not a directory
		}

		$htaccess_file = trailingslashit( $upload_dir ) . '.htaccess';
		if ( ! file_exists( $htaccess_file ) ) {
			$htaccess_content = "# Naboo Database - Upload Directory Security\n" .
								"<Files \"*.*\ভারত\">\n" . // Using a zero-width character hack or simply *.php. Actually let's do a standard block.
								"</Files>\n"; // Will rewrite properly below
			
			// Let's write a standard secure .htaccess ruleset
			$rules = array(
				"# Naboo Database - Upload Directory Security",
				"# Disable script execution",
				"<FilesMatch \"\.(php|php4|php5|php7|php8|phtml|pl|py|jsp|asp|sh|cgi)$\">",
				"    Require all denied",
				"</FilesMatch>",
				"# Prevent directory browsing",
				"Options -Indexes"
			);

			$content = implode( "\n", $rules ) . "\n";
			file_put_contents( $htaccess_file, $content );
		}
	}

	/**
	 * Hooked to 'wp_handle_upload_prefilter'
	 * Checks MIME type of incoming files specifically for Naboo submissions to ensure they are PDFs.
	 * Also randomizes the file name to prevent guessing and path traversal.
	 *
	 * @param array $file The uploaded file data array.
	 * @return array
	 */
	public function validate_scale_upload( $file ) {
		// Only run this check if we know it's a Naboo Scale submission.
		// We'll check for a nonce or specific action identifier in the POST request.
		if ( isset( $_POST['action'] ) && $_POST['action'] === 'naboo_submit_scale' ) {
			
			// Require PDF
			$allowed_mimes = array( 'pdf' => 'application/pdf' );
			$file_info     = wp_check_filetype( $file['name'], $allowed_mimes );
			$ext           = empty( $file_info['ext'] ) ? '' : $file_info['ext'];
			$type          = empty( $file_info['type'] ) ? '' : $file_info['type'];

			if ( ! $ext || ! $type || 'pdf' !== $ext ) {
				$file['error'] = __( 'Directly uploaded files for Scales must be in PDF format for security reasons.', 'naboodatabase' );
				return $file; // Reject the file
			}

			// Randomize the filename for security
			$file_ext = pathinfo( $file['name'], PATHINFO_EXTENSION );
			$random_name = md5( time() . wp_rand() . $file['name'] ) . '.' . $file_ext;
			$file['name'] = $random_name;
		}

		return $file;
	}
}
