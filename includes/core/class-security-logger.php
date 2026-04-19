<?php
/**
 * Naboo Security Logger
 * Handles recording and retrieving security-related events.
 *
 * @package ArabPsychology\NabooDatabase\Core
 */

namespace ArabPsychology\NabooDatabase\Core;

/**
 * Class Security_Logger
 */
class Security_Logger {

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
		$this->table_name = $wpdb->prefix . 'naboo_security_logs';
	}

	/**
	 * Create the logs table if it doesn't exist.
	 */
	public function create_table() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $this->table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			event_type varchar(50) NOT NULL,
			user_id bigint(20) DEFAULT 0,
			user_login varchar(60) DEFAULT '',
			ip_address varchar(45) DEFAULT '',
			user_agent text,
			severity varchar(20) DEFAULT 'info',
			description text NOT NULL,
			metadata text,
			PRIMARY KEY  (id),
			KEY event_type (event_type),
			KEY timestamp (timestamp),
			KEY ip_address (ip_address)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Log a security event.
	 *
	 * @param string $event_type Type of event (e.g., 'failed_login', 'settings_update').
	 * @param string $description Human-readable description.
	 * @param string $severity Event severity (info, warning, danger).
	 * @param array  $metadata Additional structured data.
	 */
	public function log( $event_type, $description, $severity = 'info', $metadata = array() ) {
		global $wpdb;

		$user_id    = 0;
		$user_login = '';

		if ( \function_exists( 'wp_get_current_user' ) ) {
			$user       = \wp_get_current_user();
			if ( isset( $user->ID ) ) {
				$user_id    = $user->ID;
				$user_login = $user->user_login;
			}
		}

		$ip = $this->get_ip();

		$wpdb->insert(
			$this->table_name,
			array(
				'timestamp'   => \current_time( 'mysql' ),
				'event_type'  => \sanitize_text_field( $event_type ),
				'user_id'     => $user_id,
				'user_login'  => $user_login,
				'ip_address'  => $ip,
				'user_agent'  => \sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ?? '' ),
				'severity'    => \sanitize_text_field( $severity ),
				'description' => \sanitize_textarea_field( $description ),
				'metadata'    => ! empty( $metadata ) ? \wp_json_encode( $metadata ) : '',
			)
		);
	}

	/**
	 * Get logs with pagination and filtering.
	 */
	public function get_logs( $limit = 50, $offset = 0, $type = '' ) {
		global $wpdb;
		
		$query = "SELECT * FROM $this->table_name";
		$where = array();
		
		if ( ! empty( $type ) ) {
			$where[] = $wpdb->prepare( "event_type = %s", $type );
		}
		
		if ( ! empty( $where ) ) {
			$query .= " WHERE " . implode( " AND ", $where );
		}
		
		$query .= " ORDER BY timestamp DESC LIMIT %d OFFSET %d";
		
		return $wpdb->get_results( $wpdb->prepare( $query, $limit, $offset ) );
	}

	/**
	 * Count total logs for pagination.
	 */
	public function get_total_count( $type = '' ) {
		global $wpdb;
		$query = "SELECT COUNT(*) FROM $this->table_name";
		if ( ! empty( $type ) ) {
			$query .= $wpdb->prepare( " WHERE event_type = %s", $type );
		}
		return (int) $wpdb->get_var( $query );
	}

	/**
	 * Purge old logs.
	 */
	public function purge_old_logs( $days = 30 ) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare(
			"DELETE FROM $this->table_name WHERE timestamp < DATE_SUB(NOW(), INTERVAL %d DAY)",
			$days
		) );
	}

	/**
	 * Helper to get user IP.
	 */
	private function get_ip() {
		$headers = array( 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR' );
		foreach ( $headers as $key ) {
			if ( array_key_exists( $key, $_SERVER ) === true ) {
				foreach ( explode( ',', $_SERVER[ $key ] ) as $ip ) {
					$ip = trim( $ip );
					if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) !== false ) {
						return $ip;
					}
				}
			}
		}
		return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
	}
}
