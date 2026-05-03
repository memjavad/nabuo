<?php
/**
 * Batch AI Draft Processor - Orchestrator
 *
 * @package ArabPsychology\NabooDatabase\Admin
 */

namespace ArabPsychology\NabooDatabase\Admin;

use ArabPsychology\NabooDatabase\Admin\Batch_AI\Batch_AI_Processor;
use ArabPsychology\NabooDatabase\Admin\Batch_AI\Batch_AI_Remote_Sync;
use ArabPsychology\NabooDatabase\Admin\Batch_AI\Batch_AI_Cron_Manager;
use ArabPsychology\NabooDatabase\Admin\Batch_AI\Batch_AI_REST_Handler;
use ArabPsychology\NabooDatabase\Core\Installer;

/**
 * Batch_AI class
 */
class Batch_AI {

	/** @var Batch_AI_Processor */
	private $processor;

	/** @var Batch_AI_Remote_Sync */
	private $remote_sync;

	/** @var Batch_AI_Cron_Manager */
	private $cron_manager;

	/** @var Batch_AI_REST_Handler */
	private $rest_handler;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->processor    = new Batch_AI_Processor();
		$this->remote_sync  = new Batch_AI_Remote_Sync();
		$this->cron_manager = new Batch_AI_Cron_Manager( $this->remote_sync );
		$this->rest_handler = new Batch_AI_REST_Handler( $this->processor, $this->remote_sync );
	}

	/**
	 * Register hooks
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// AJAX Processor hooks
		add_action( 'wp_ajax_naboo_process_single_draft', array( $this->processor, 'ajax_process_single_draft' ) );
		add_action( 'wp_ajax_naboo_toggle_background_ai', array( $this->processor, 'ajax_toggle_background_ai' ) );
		add_action( 'wp_ajax_naboo_get_bg_status', array( $this->processor, 'ajax_get_bg_status' ) );
		add_action( 'wp_ajax_naboo_skip_bg_draft', array( $this->processor, 'ajax_skip_bg_draft' ) );
		add_action( 'wp_ajax_naboo_reset_daily_progress', array( $this->processor, 'ajax_reset_daily_progress' ) );

		// AJAX Remote Sync hooks
		add_action( 'wp_ajax_naboo_connect_remote_drafts', array( $this->remote_sync, 'ajax_connect_remote_drafts' ) );
		add_action( 'wp_ajax_naboo_fetch_remote_list', array( $this->remote_sync, 'ajax_fetch_remote_list' ) );
		add_action( 'wp_ajax_naboo_import_remote_single', array( $this->remote_sync, 'ajax_import_remote_single' ) );
		add_action( 'wp_ajax_naboo_save_remote_settings', array( $this->remote_sync, 'ajax_save_remote_settings' ) );
		add_action( 'wp_ajax_naboo_clear_import_log', array( $this->remote_sync, 'ajax_clear_import_log' ) );
		add_action( 'wp_ajax_naboo_save_cursor', array( $this->remote_sync, 'ajax_save_cursor' ) );
		add_action( 'wp_ajax_naboo_import_from_file', array( $this->remote_sync, 'ajax_import_from_file' ) );
		add_action( 'wp_ajax_naboo_import_from_url', array( $this->remote_sync, 'ajax_import_from_url' ) );

		// REST API
		add_action( 'rest_api_init', array( $this->rest_handler, 'register_rest_routes' ) );

		// Cron
		add_filter( 'cron_schedules', array( $this->cron_manager, 'add_cron_intervals' ) );
		add_action( 'naboo_remote_auto_sync_event', array( $this->cron_manager, 'auto_sync_remote_drafts' ) );
		add_action( 'naboo_full_auto_import_event', array( $this->cron_manager, 'full_auto_import_tick' ) );
		add_action( 'naboo_background_ai_process_draft_event', array( $this->processor, 'background_ai_process_draft' ) );
		add_action( 'naboo_ai_watchdog_event', array( $this->cron_manager, 'watchdog_check' ) );

		if ( ! wp_next_scheduled( 'naboo_ai_watchdog_event' ) ) {
			wp_schedule_event( time(), 'naboo_5min', 'naboo_ai_watchdog_event' );
		}
	}

	public function add_plugin_admin_menu() {
		add_submenu_page(
			'naboo-dashboard',
			__( 'Batch AI processor', 'naboodatabase' ),
			__( '🤖 AI Batch Processing', 'naboodatabase' ),
			'manage_options',
			'naboo-batch-ai',
			array( $this, 'display_plugin_admin_page' ),
			11
		);
	}

	public function enqueue_scripts( $hook ) {
		if ( false === strpos( $hook, 'naboo-batch-ai' ) ) {
			return;
		}

		wp_enqueue_style( 'naboo-admin-global', plugin_dir_url( dirname( __FILE__ ) ) . 'admin/css/naboo-admin-global.css', array(), NABOODATABASE_VERSION, 'all' );

		wp_enqueue_script(
			'naboo-batch-ai-script',
			plugin_dir_url( dirname( __FILE__ ) ) . 'admin/js/batch-ai-processor.js',
			array( 'jquery' ),
			NABOODATABASE_VERSION,
			true
		);

		wp_localize_script(
			'naboo-batch-ai-script',
			'nabooBatchAI',
			array(
				'ajax_url'     => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'naboo_batch_ai_nonce' ),
				'search_nonce' => wp_create_nonce( 'naboo_search_nonce' ),
				'last_page'    => (int) get_option( 'naboo_remote_last_page', 0 ),
				'rest_url'     => rest_url( 'naboo-db/v1/' ),
				'rest_nonce'   => wp_create_nonce( 'wp_rest' ),
				'auto_import'  => (int) get_option( 'naboo_full_auto_import', 0 ),
				'parallelism'  => (int) get_option( 'naboo_import_parallelism', 2 ),
				'is_bg_active' => (bool) wp_next_scheduled( 'naboo_background_ai_process_draft_event' ),
				'bg_delay'     => get_option( 'naboo_background_ai_delay', '0' ),
				'bg_rand_min'  => (int) get_option( 'naboo_bg_ai_random_min', 45 ),
				'bg_rand_max'  => (int) get_option( 'naboo_bg_ai_random_max', 90 ),
				'bg_keep'      => (bool) get_option( 'naboo_bg_ai_keep_active', false ),
				'daily_limit'  => (int) get_option( 'naboo_bg_ai_daily_limit', 0 ),
				'daily_count'  => (int) get_option( 'naboo_bg_ai_daily_count', 0 ),
				'daily_date'   => get_option( 'naboo_bg_ai_last_date', '' ),
			)
		);
	}

	public function display_plugin_admin_page() {
		global $wpdb;
		$raw_drafts = $wpdb->get_results(
			$wpdb->prepare(
				"
            SELECT ID, post_title 
            FROM %i
            WHERE post_type = 'naboo_raw_draft' 
            AND post_status IN ('publish', 'draft', 'pending')
            ORDER BY post_date DESC
        ",
				$wpdb->posts
			)
		);

		$pending_drafts = array();
		foreach ( $raw_drafts as $rd ) {
			$pending_drafts[] = array(
				'id'    => $rd->ID,
				'title' => $rd->post_title,
			);
		}
		$count        = count( $pending_drafts );
		$saved_url    = get_option( 'naboo_remote_url', '' );
		$saved_token  = get_option( 'naboo_remote_token', '' );
		$saved_type   = get_option( 'naboo_remote_post_type', 'post' );
		$saved_status = get_option( 'naboo_remote_post_status', 'draft' );
		$auto_sync    = get_option( 'naboo_remote_auto_sync', 0 );
		$log_count    = Installer::get_log_count();
		$last_import  = get_option( 'naboo_remote_last_import_time', 0 );
		$last_page    = (int) get_option( 'naboo_remote_last_page', 0 );
		$is_connected = ( $saved_url && $saved_token );

		// Eagerly pre-fill queue if empty
		$stats = Installer::get_queue_stats();
		if ( $stats['pending'] == 0 && $stats['processing'] == 0 && $count > 0 ) {
			$table = $wpdb->prefix . 'naboo_process_queue';
			$now   = current_time( 'mysql' );
			$wpdb->query( $wpdb->prepare( "
                INSERT IGNORE INTO {$table} (draft_id, status, queued_at)
                SELECT p.ID, 'pending', %s
                FROM {$wpdb->posts} p
                LEFT JOIN {$table} q ON p.ID = q.draft_id
                WHERE p.post_type = 'naboo_raw_draft'
                AND p.post_status IN ('publish', 'draft', 'pending')
                AND q.draft_id IS NULL
                LIMIT 500
            ", $now ) );
		}
		?>
		<div class="naboo-admin-page">
			<div class="naboo-admin-header">
				<div class="naboo-admin-header-left">
					<h1 class="naboo-admin-title">
						<span class="title-icon">🤖</span>
						Batch AI Processor
					</h1>
					<p class="naboo-admin-subtitle">Import, process, and publish psychological scales at scale.</p>
				</div>
				<div class="naboo-admin-header-right">
					<span class="naboo-badge naboo-badge-green">v<?php echo esc_html( NABOODATABASE_VERSION ); ?></span>
				</div>
			</div>

			<div class="naboo-stat-row" style="margin-bottom:24px;">
				<div class="naboo-stat-item">
					<span class="naboo-stat-label">📥 Raw Drafts Pending</span>
					<span class="naboo-stat-value<?php echo $count > 0 ? ' info' : ''; ?>" id="naboo-draft-count"><?php echo esc_html( number_format( $count ) ); ?></span>
				</div>
				<div class="naboo-stat-item">
					<span class="naboo-stat-label">✅ Total Imported</span>
					<span class="naboo-stat-value success" id="naboo-log-count"><?php echo esc_html( number_format( $log_count ) ); ?></span>
				</div>
				<div class="naboo-stat-item">
					<span class="naboo-stat-label">🕒 Last Import</span>
					<span class="naboo-stat-value" style="font-size:20px;">
						<?php echo $last_import ? esc_html( human_time_diff( (int) $last_import, time() ) . ' ago' ) : '—'; ?>
					</span>
				</div>
				<div class="naboo-stat-item">
					<span class="naboo-stat-label">🌐 Origin</span>
					<span class="naboo-stat-value" style="font-size:16px; word-break:break-all;">
						<?php echo $saved_url ? '<span class="naboo-badge naboo-badge-green">Connected</span>' : '<span class="naboo-badge naboo-badge-gray">Not set</span>'; ?>
					</span>
				</div>
			</div>

			<?php if ( $last_page > 0 ) : ?>
			<div class="naboo-notice warning" style="margin-bottom:20px;">
				<span style="font-size:20px;">▶</span>
				<div>
					<strong>Auto-resume active</strong> — Last run stopped at page <strong><?php echo esc_html( $last_page ); ?></strong>.
					The "Fetch All Pages" and "Start Auto-Import" buttons will continue from that page.
					<a href="#" id="naboo-reset-cursor" style="color:var(--naboo-red); margin-left:10px; font-weight:600;">✕ Reset cursor</a>
				</div>
			</div>
			<?php endif; ?>

			<div class="naboo-admin-grid cols-2" style="align-items:start;">
				<div style="display:flex; flex-direction:column; gap:20px;">
					<div class="naboo-admin-card">
						<div class="naboo-admin-card-header">
							<div class="naboo-admin-card-icon green">🤖</div>
							<h3>AI Batch Processor</h3>
							<?php if ( $count > 0 ) : ?>
							<span class="naboo-badge naboo-badge-blue" style="margin-left:auto;"><?php echo esc_html( number_format( $count ) ); ?> drafts</span>
							<?php endif; ?>
						</div>
						<?php if ( $count > 0 ) : ?>
						<p style="color:var(--naboo-text-muted); margin:0 0 16px; font-size:14px;">
							<strong style="color:var(--naboo-primary);"><?php echo esc_html( number_format( $count ) ); ?></strong> raw drafts waiting.
						</p>
						<div style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:16px;">
							<div class="naboo-form-row" style="margin:0; min-width:200px;">
								<select id="naboo-batch-delay">
									<option value="0">None (Browser immediately)</option>
									<option value="60">1 minute background</option>
									<option value="300">5 minutes background</option>
									<option value="600">10 minutes background</option>
									<option value="900">15 minutes background</option>
									<option value="3600">1 hour background</option>
									<option value="random">Random background</option>
								</select>
							</div>
							<div id="naboo-random-inputs" style="display:none; gap:6px; align-items:center;">
								<input type="number" id="naboo-random-min" min="1" max="1440" placeholder="Min (m)" style="width:80px;"> 
								<span>-</span>
								<input type="number" id="naboo-random-max" min="1" max="1440" placeholder="Max (m)" style="width:80px;">
							</div>
							<div style="display:flex; align-items:center; gap:8px;">
								<label for="naboo-daily-limit" style="font-size:13px; color:var(--naboo-text-secondary); font-weight:600;">Daily Limit:</label>
								<input type="number" id="naboo-daily-limit" min="0" value="0" style="width:80px; padding:6px;">
							</div>
							<button class="naboo-btn naboo-btn-primary" id="naboo-start-batch-ai" style="font-size:15px; padding:12px 28px;">▶ Start Batch Processing</button>
							<button class="naboo-btn naboo-btn-danger" id="naboo-stop-batch-ai" style="display:none; font-size:15px; padding:12px 28px;">⏹ Stop Background</button>
						</div>

						<div id="naboo-batch-progress-wrapper" style="display:none; margin-top:4px;">
							<div id="naboo-live-processing-info" style="margin-top:15px; margin-bottom:15px; padding:15px; border:1px solid #c3c4c7; background:#fff; border-left:4px solid #2271b1; border-radius:3px;">
								<div style="margin-bottom:8px; font-size:14px;"><strong>▶ Currently Processing:</strong> <span id="naboo-current-processing" style="color:#007cba; font-weight:600;">...</span></div>
								<div style="margin-bottom:12px; font-size:13px;"><strong>⏭ Next in Queue:</strong> <span id="naboo-next-processing" style="color:#646970;">...</span></div>
								<button type="button" class="naboo-btn naboo-btn-secondary" id="naboo-skip-current-draft" style="font-size:12px; padding:4px 10px;">⏭ Skip Current Draft</button>
							</div>
							<div id="naboo-batch-stats" style="font-size:13px; color:var(--naboo-text-muted); margin-bottom:10px;"></div>
							<div class="naboo-form-section-title">Processing Log</div>
							<div id="naboo-batch-log" style="height:300px; overflow-y:scroll; background:var(--naboo-bg); border:1px solid var(--naboo-border); border-radius:var(--naboo-radius-sm); padding:14px; font-family:monospace; font-size:12px; line-height:1.7;"></div>
							<progress id="naboo-batch-progress" value="0" max="<?php echo esc_attr( $count ); ?>" style="width:100%; height:8px; margin-top:12px; border-radius:4px; accent-color:var(--naboo-accent);"></progress>
						</div>
						<script>window.nabooPendingDrafts = <?php echo wp_json_encode( $pending_drafts ); ?>;</script>
						<?php else : ?>
						<div class="naboo-notice success" style="margin:0;">🎉 <span>All caught up! No local raw drafts found.</span></div>
						<?php endif; ?>
					</div>

					<div class="naboo-admin-card">
						<div class="naboo-admin-card-header">
							<div class="naboo-admin-card-icon blue">📋</div>
							<h3>Import Log</h3>
							<div style="margin-left:auto;">
								<button type="button" id="naboo-clear-import-log" class="naboo-btn naboo-btn-danger" style="padding:7px 14px; font-size:13px;">🗑 Clear</button>
							</div>
						</div>
						<div class="naboo-stat-row">
							<div class="naboo-stat-item">
								<span class="naboo-stat-label">All-Time Imported</span>
								<span class="naboo-stat-value success" id="naboo-log-count-live"><?php echo esc_html( number_format( $log_count ) ); ?></span>
							</div>
							<div class="naboo-stat-item">
								<span class="naboo-stat-label">This Session</span>
								<span class="naboo-stat-value info" id="naboo-session-count">0</span>
							</div>
						</div>
					</div>
				</div>

				<div style="display:flex; flex-direction:column; gap:20px;">
					<div class="naboo-admin-card">
						<div class="naboo-admin-card-header">
							<div class="naboo-admin-card-icon purple">🔌</div>
							<h3>Origin Connection</h3>
							<?php if ( $is_connected ) : ?>
							<span class="naboo-badge naboo-badge-green" style="margin-left:auto;">● Connected</span>
							<?php endif; ?>
						</div>
						<div class="naboo-form-row">
							<label for="naboo_remote_url">Origin Site URL</label>
							<input type="url" id="naboo_remote_url" placeholder="https://example.com" value="<?php echo esc_attr( $saved_url ); ?>" />
						</div>
						<div class="naboo-form-row">
							<label for="naboo_remote_token">API Token</label>
							<input type="password" id="naboo_remote_token" value="<?php echo esc_attr( $saved_token ); ?>" />
						</div>
						<div style="display:flex; gap:10px; flex-wrap:wrap;">
							<button type="button" id="naboo-connect-remote" class="naboo-btn naboo-btn-secondary">🔗 Connect &amp; Get Options</button>
						</div>
					</div>

					<div class="naboo-admin-card" id="naboo-remote-options-wrap" style="<?php echo $is_connected ? '' : 'display:none;'; ?>">
						<div class="naboo-admin-card-header">
							<div class="naboo-admin-card-icon amber">⚙️</div>
							<h3>Import Options</h3>
						</div>
						<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:16px;">
							<div class="naboo-form-row" style="margin:0;">
								<label for="naboo_remote_post_type">Post Type</label>
								<select id="naboo_remote_post_type" data-saved="<?php echo esc_attr( $saved_type ); ?>">
									<option value="<?php echo esc_attr( $saved_type ); ?>"><?php echo esc_html( $saved_type ); ?></option>
								</select>
							</div>
							<div class="naboo-form-row" style="margin:0;">
								<label for="naboo_remote_post_status">Status</label>
								<select id="naboo_remote_post_status" data-saved="<?php echo esc_attr( $saved_status ); ?>">
									<option value="<?php echo esc_attr( $saved_status ); ?>"><?php echo esc_html( $saved_status ); ?></option>
								</select>
							</div>
						</div>
						<div class="naboo-form-row" style="max-width:200px;">
							<label for="naboo_remote_page">Start Page</label>
							<input type="number" id="naboo_remote_page" value="1" min="1" step="1" />
						</div>
						<div class="naboo-toggle-row">
							<input type="checkbox" id="naboo_remote_auto_sync" value="1" <?php checked( $auto_sync, 1 ); ?> />
							<div class="toggle-info">
								<strong>Hourly Background Sync</strong>
								<span>Automatically imports one page hourly.</span>
							</div>
						</div>
						<div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:16px;">
							<button type="button" id="naboo-fetch-remote" class="naboo-btn naboo-btn-secondary">📄 Fetch Current Page</button>
							<button type="button" id="naboo-fetch-all-remote" class="naboo-btn naboo-btn-secondary">📚 Fetch All Pages</button>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}
