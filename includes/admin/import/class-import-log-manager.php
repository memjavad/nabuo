<?php
/**
 * Import Log Manager - Handles import tracking and database logs
 *
 * @package ArabPsychology\NabooDatabase\Admin\Import
 */

namespace ArabPsychology\NabooDatabase\Admin\Import;

/**
 * Import_Log_Manager class
 */
class Import_Log_Manager {

	/**
	 * Table name
	 * @var string
	 */
	private $table_name;

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'naboo_import_logs';
		$this->create_table();
	}

	/**
	 * Create imports tracking table
	 */
	public function create_table() {
		global $wpdb;
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $this->table_name ) ) === null ) {
			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE {$this->table_name} (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				import_file varchar(255),
				total_rows bigint(20),
				successful_imports bigint(20),
				failed_imports bigint(20),
				imported_by bigint(20),
				created_at datetime DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY imported_by (imported_by),
				KEY created_at (created_at)
			) $charset_collate;";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
		}
	}

	/**
	 * Log import results
	 *
	 * @param string $filename   Filename.
	 * @param int    $total      Total rows.
	 * @param int    $successful Successful imports.
	 * @param int    $failed      Failed imports.
	 */
	public function log_import( $filename, $total, $successful, $failed ) {
		global $wpdb;
		$wpdb->insert(
			$this->table_name,
			array(
				'import_file'         => $filename,
				'total_rows'          => $total,
				'successful_imports'  => $successful,
				'failed_imports'      => $failed,
				'imported_by'         => get_current_user_id(),
			),
			array( '%s', '%d', '%d', '%d', '%d' )
		);
	}

	/**
	 * Get recent import logs
	 *
	 * @param int $limit Max logs to retrieve.
	 * @return array
	 */
	public function get_logs( $limit = 20 ) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$this->table_name} ORDER BY created_at DESC LIMIT %d", $limit )
		);
	}
}
