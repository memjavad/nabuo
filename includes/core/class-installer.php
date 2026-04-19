<?php
/**
 * Plugin Installer — handles custom database table creation.
 *
 * @package ArabPsychology\NabooDatabase\Core
 */

namespace ArabPsychology\NabooDatabase\Core;

class Installer {

    /**
     * The DB schema version. Increment whenever the table structure changes.
     */
    const DB_VERSION = '1.4';

    /**
     * Create (or upgrade) the custom plugin tables.
     * Safe to run multiple times — uses dbDelta internally.
     */
    public static function create_tables() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        // ── Table 1: Import Log ─────────────────────────────────
        $import_table = $wpdb->prefix . 'naboo_import_log';
        $sql1 = "CREATE TABLE {$import_table} (
            id        BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            origin_id BIGINT(20) UNSIGNED NOT NULL,
            imported_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY origin_id (origin_id)
        ) {$charset_collate};";
        dbDelta( $sql1 );

        // ── Table 2: AI Batch Process Queue ─────────────────────
        $queue_table = $wpdb->prefix . 'naboo_process_queue';
        $sql2 = "CREATE TABLE {$queue_table} (
            id           BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            draft_id     BIGINT(20) UNSIGNED NOT NULL,
            status       VARCHAR(20) NOT NULL DEFAULT 'pending',
            retries      TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
            error        TEXT,
            queued_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            processed_at DATETIME,
            PRIMARY KEY  (id),
            UNIQUE KEY draft_id (draft_id),
            KEY status (status)
        ) {$charset_collate};";
        dbDelta( $sql2 );

        // ── Table 3: Security Audit Logs ────────────────────────
        $security_logs_table = $wpdb->prefix . 'naboo_security_logs';
        $sql3 = "CREATE TABLE {$security_logs_table} (
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
        ) {$charset_collate};";
        dbDelta( $sql3 );

        // ── Table 4: Advanced Search Index ──────────────────────
        // Called directly (not via dbDelta version) so it is safe to run on
        // existing installs without interfering with other feature tables.
        \ArabPsychology\NabooDatabase\Admin\Database_Indexer::create_table();

        // ── Table 5: Grokipedia Sync History ─────────────────────
        $sync_history_table = $wpdb->prefix . 'naboo_sync_history';
        $sql5 = "CREATE TABLE {$sync_history_table} (
            id           BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            scale_id     BIGINT(20) UNSIGNED NOT NULL,
            scale_title  VARCHAR(255),
            synced_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            status       VARCHAR(20) DEFAULT 'success',
            details      TEXT,
            PRIMARY KEY  (id),
            KEY scale_id (scale_id),
            KEY status (status)
        ) {$charset_collate};";
        dbDelta( $sql5 );

        update_option( 'naboo_db_version', self::DB_VERSION );
    }

    /**
     * Lazily ensure tables exist. Compares stored version to trigger upgrades.
     */
    public static function maybe_create_tables() {
        if ( get_option( 'naboo_db_version' ) !== self::DB_VERSION ) {
            self::create_tables();
        }
    }

    // ═══════════════════════════════════════════════════════════
    // IMPORT LOG METHODS
    // ═══════════════════════════════════════════════════════════

    public static function get_log_count() {
        global $wpdb;
        self::maybe_create_tables();
        $table = $wpdb->prefix . 'naboo_import_log';
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    public static function is_imported( $origin_id ) {
        global $wpdb;
        self::maybe_create_tables();
        $table = $wpdb->prefix . 'naboo_import_log';
        $found = $wpdb->get_var( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            "SELECT origin_id FROM {$table} WHERE origin_id = %d LIMIT 1",
            $origin_id
        ) );
        return ! is_null( $found );
    }

    public static function log_import( $origin_id ) {
        global $wpdb;
        self::maybe_create_tables();
        $table  = $wpdb->prefix . 'naboo_import_log';
        $result = $wpdb->query( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            "INSERT IGNORE INTO {$table} (origin_id, imported_at) VALUES (%d, %s)",
            $origin_id,
            current_time( 'mysql' )
        ) );
        return $result !== false;
    }

    public static function clear_log() {
        global $wpdb;
        self::maybe_create_tables();
        $table = $wpdb->prefix . 'naboo_import_log';
        return $wpdb->query( "TRUNCATE TABLE {$table}" ) !== false; // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    // ═══════════════════════════════════════════════════════════
    // PROCESS QUEUE METHODS
    // ═══════════════════════════════════════════════════════════

    /**
     * Bulk-insert draft IDs as pending queue items.
     * Uses INSERT IGNORE so already-queued drafts are not duplicated.
     *
     * @param int[] $draft_ids
     * @return int Number of rows inserted.
     */
    public static function enqueue_drafts( array $draft_ids ) {
        global $wpdb;
        self::maybe_create_tables();
        if ( empty( $draft_ids ) ) {
            return 0;
        }
        $table       = $wpdb->prefix . 'naboo_process_queue';
        $now         = current_time( 'mysql' );
        $placeholders = implode( ', ', array_fill( 0, count( $draft_ids ), "(%d, 'pending', %s)" ) );
        $values       = [];
        foreach ( $draft_ids as $id ) {
            $values[] = absint( $id );
            $values[] = $now;
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
        return (int) $wpdb->query( $wpdb->prepare(
            "INSERT IGNORE INTO {$table} (draft_id, status, queued_at) VALUES {$placeholders}",
            $values
        ) );
    }

    /**
     * Fetch and atomically claim the next pending draft for processing.
     * Returns the queue row or null if nothing is left.
     *
     * @return object|null
     */
    public static function dequeue_next() {
        global $wpdb;
        self::maybe_create_tables();
        $table = $wpdb->prefix . 'naboo_process_queue';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $row = $wpdb->get_row(
            "SELECT * FROM {$table} WHERE status = 'pending' ORDER BY id ASC LIMIT 1"
        );
        if ( ! $row ) {
            return null;
        }
        // Claim it to avoid race conditions
        $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $table,
            [ 'status' => 'processing' ],
            [ 'id' => $row->id, 'status' => 'pending' ]
        );
        $row->status = 'processing';
        return $row;
    }

    /**
     * Mark a queue item as successfully done.
     *
     * @param int $draft_id
     */
    public static function mark_done( $draft_id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'naboo_process_queue';
        $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $table,
            [ 'status' => 'done', 'processed_at' => current_time( 'mysql' ) ],
            [ 'draft_id' => absint( $draft_id ) ]
        );
    }

    /**
     * Mark a queue item as failed, increment retry count.
     * If retries >= 3, set status to 'failed' permanently.
     *
     * @param int    $draft_id
     * @param string $error_message
     * @return bool True if the item will be retried, false if permanently failed.
     */
    public static function mark_failed( $draft_id, $error_message = '' ) {
        global $wpdb;
        $table   = $wpdb->prefix . 'naboo_process_queue';
        $draft_id = absint( $draft_id );

        $row = $wpdb->get_row( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            "SELECT retries FROM {$table} WHERE draft_id = %d LIMIT 1",
            $draft_id
        ) );
        $retries   = $row ? (int) $row->retries + 1 : 1;
        $new_status = $retries >= 3 ? 'failed' : 'pending'; // retry up to 3 times

        $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $table,
            [
                'status'       => $new_status,
                'retries'      => $retries,
                'error'        => sanitize_textarea_field( $error_message ),
                'processed_at' => current_time( 'mysql' ),
            ],
            [ 'draft_id' => $draft_id ]
        );

        if ( $new_status === 'failed' ) {
            // Take the draft out of circulation so the background query doesn't constantly fetch it
            $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                $wpdb->posts,
                [ 'post_status' => 'naboo_manual' ],
                [ 'ID' => $draft_id, 'post_type' => 'naboo_raw_draft' ]
            );
            clean_post_cache( $draft_id );
        }

        return $new_status === 'pending';
    }

    /**
     * Reset a failed item back to pending (manual retry).
     *
     * @param int $draft_id
     */
    public static function retry_draft( $draft_id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'naboo_process_queue';
        $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $table,
            [ 'status' => 'pending', 'retries' => 0, 'error' => '' ],
            [ 'draft_id' => absint( $draft_id ) ]
        );
    }

    /**
     * Get queue stats: pending, processing, done, failed counts.
     *
     * @return array
     */
    public static function get_queue_stats() {
        global $wpdb;
        self::maybe_create_tables();
        $table = $wpdb->prefix . 'naboo_process_queue';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $rows = $wpdb->get_results(
            "SELECT status, COUNT(*) as cnt FROM {$table} GROUP BY status",
            ARRAY_A
        );
        $stats = [ 'pending' => 0, 'processing' => 0, 'done' => 0, 'failed' => 0 ];
        foreach ( $rows as $r ) {
            $stats[ $r['status'] ] = (int) $r['cnt'];
        }
        return $stats;
    }

    /**
     * Get all failed queue items with their error messages.
     *
     * @return array
     */
    public static function get_failed_items() {
        global $wpdb;
        $table = $wpdb->prefix . 'naboo_process_queue';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        return $wpdb->get_results(
            "SELECT draft_id, retries, error, processed_at FROM {$table} WHERE status = 'failed' ORDER BY processed_at DESC"
        );
    }

    /**
     * Clear the process queue entirely.
     */
    public static function clear_queue() {
        global $wpdb;
        $table = $wpdb->prefix . 'naboo_process_queue';
        $wpdb->query( "TRUNCATE TABLE {$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    /**
     * Reset any queue items stuck in 'processing' for more than 15 minutes.
     * Handles cron jobs that crash mid-run, which would otherwise leave items stuck forever.
     * Scheduled via 'naboo_queue_cleanup' cron hook.
     */
    public static function reset_stuck_processing_items() {
        global $wpdb;
        $table = $wpdb->prefix . 'naboo_process_queue';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->query(
            "UPDATE {$table}
             SET status = 'pending'
             WHERE status = 'processing'
               AND queued_at < DATE_SUB( NOW(), INTERVAL 15 MINUTE )"
        );
    }

    /**
     * Purge queue rows that have been 'done' for more than 7 days.
     * Prevents the queue table from growing indefinitely.
     * Scheduled via 'naboo_queue_cleanup' cron hook.
     */
    public static function purge_old_done_items() {
        global $wpdb;
        $table = $wpdb->prefix . 'naboo_process_queue';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->query(
            "DELETE FROM {$table}
             WHERE status = 'done'
               AND processed_at < DATE_SUB( NOW(), INTERVAL 7 DAY )"
        );
    }

    /**
     * Log a Grokipedia sync event.
     */
    public static function log_sync_submission( $scale_id, $status = 'success', $details = '' ) {
        global $wpdb;
        self::maybe_create_tables();
        $table = $wpdb->prefix . 'naboo_sync_history';
        
        $post = get_post( $scale_id );
        $title = $post ? $post->post_title : 'Unknown Scale';

        return $wpdb->insert(
            $table,
            array(
                'scale_id'    => absint( $scale_id ),
                'scale_title' => sanitize_text_field( $title ),
                'synced_at'   => current_time( 'mysql' ),
                'status'      => sanitize_text_field( $status ),
                'details'     => sanitize_textarea_field( $details ),
            ),
            array( '%d', '%s', '%s', '%s', '%s' )
        );
    }

    /**
     * Get sync history.
     */
    public static function get_sync_history( $limit = 50 ) {
        global $wpdb;
        self::maybe_create_tables();
        $table = $wpdb->prefix . 'naboo_sync_history';
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$table} ORDER BY synced_at DESC LIMIT %d",
            $limit
        ) );
    }
}
