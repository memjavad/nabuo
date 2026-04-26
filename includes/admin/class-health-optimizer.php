<?php
/**
 * Naboo Health Optimizer
 *
 * @package ArabPsychology\NabooDatabase\Admin
 */

namespace ArabPsychology\NabooDatabase\Admin;

use ArabPsychology\NabooDatabase\Admin\Health\Health_Checker;
use ArabPsychology\NabooDatabase\Admin\Health\Maintenance_Manager;
use ArabPsychology\NabooDatabase\Admin\Health\System_Info_Renderer;

/**
 * Health_Optimizer class - Admin interface orchestrator for maintenance and health checks.
 */
class Health_Optimizer {

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
	 * Health Checker manager
	 * @var Health_Checker
	 */
	private $checker;

	/**
	 * Maintenance manager
	 * @var Maintenance_Manager
	 */
	private $maintenance;

	/**
	 * System Info renderer
	 * @var System_Info_Renderer
	 */
	private $renderer;

	/**
	 * Constructor
	 *
	 * @param string $plugin_name Plugin name.
	 * @param string $version     Plugin version.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		// Initialize sub-managers
		$this->checker     = new Health_Checker();
		$this->maintenance = new Maintenance_Manager();
		$this->renderer    = new System_Info_Renderer();
	}

	/**
	 * Register submenu under NABOO Dashboard.
	 */
	public function register_menu() {
		add_submenu_page(
			'naboo-dashboard',
			__( 'Health Check', 'naboodatabase' ),
			__( '🏥 Health Check', 'naboodatabase' ),
			'manage_options',
			'naboo-health',
			array( $this, 'render_page' ),
			9
		);
	}

	/**
	 * Register Ajax hooks
	 */
	public function register_ajax() {
		add_action( 'wp_ajax_naboo_health_check', array( $this, 'ajax_health_check' ) );
		add_action( 'wp_ajax_naboo_health_optimize', array( $this, 'ajax_health_optimize' ) );
		add_filter( 'cron_schedules', array( $this, 'add_cron_intervals' ) );
		add_action( 'naboo_auto_optimize_event', array( $this, 'run_automated_maintenance' ) );
	}

	/**
	 * Add custom cron intervals.
	 */
	public function add_cron_intervals( $schedules ) {
		if ( ! isset( $schedules['naboo_5min'] ) ) {
			$schedules['naboo_5min'] = array(
				'interval' => 300,
				'display'  => __( 'Every 5 Minutes' ),
			);
		}
		if ( ! isset( $schedules['naboo_10min'] ) ) {
			$schedules['naboo_10min'] = array(
				'interval' => 600,
				'display'  => __( 'Every 10 Minutes' ),
			);
		}
		return $schedules;
	}

	/**
	 * Render the health check page
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'naboodatabase' ) );
		}

		// Enqueue modern font
		echo '<link rel="preconnect" href="https://fonts.googleapis.com">
			  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
			  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">';

		?>
		<?php include dirname( __FILE__ ) . '/views/health/styles.php'; ?>

		<div class="naboo-health-wrapper wrap">
			<?php include dirname( __FILE__ ) . '/views/health/header.php'; ?>

			<div class="naboo-health-grid">
				<!-- Left Column: Status -->
				<?php include dirname( __FILE__ ) . '/views/health/main-card.php'; ?>

				<!-- Right Column: Actions & Settings -->
				<?php include dirname( __FILE__ ) . '/views/health/side-column.php'; ?>
			</div>

			<!-- System Information Section -->
			<div class="naboo-health-grid cols-1" style="margin-top: 40px;">
				<?php $this->renderer->render_system_info(); ?>
			</div>
		</div>

		<?php include dirname( __FILE__ ) . '/views/health/scripts.php'; ?>
		<?php
	}

	/**
	 * Ajax: Run health check scan
	 */
	public function ajax_health_check() {
		check_ajax_referer( 'naboo_health_check', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$results = $this->checker->perform_scan();

		// Calculate Score
		$points = 0;
		foreach ( $results as $r ) {
			if ( $r['status'] === 'good' ) $points += 2;
			elseif ( $r['status'] === 'warning' ) $points += 1;
		}
		if ( empty( $results ) ) {
			$score = 100;
		} else {
			$score = ( $points / ( count( $results ) * 2 ) ) * 100;
		}

		// Generate HTML
		ob_start();
		?>
		<div id="health-results-wrapper">
			<div class="health-score-ring">
				<div class="score-circle" style="color: <?php echo $score > 80 ? 'var(--naboo-success)' : ($score > 60 ? 'var(--naboo-warning)' : 'var(--naboo-danger)'); ?>">
					<?php echo round($score); ?>%
				</div>
			</div>
			
			<div class="health-items-grid">
				<?php foreach ( $results as $id => $data ) : ?>
					<div class="health-item">
						<div class="health-item-label">
							<span class="status-dot <?php echo esc_attr( $data['status'] ); ?>"></span>
							<?php echo esc_html( $data['label'] ); ?>
						</div>
						<div class="health-item-value">
							<?php echo esc_html( $data['value'] ); ?>
							<?php if ( isset( $data['message'] ) && $data['status'] !== 'good' ) : ?>
								<span style="display: block; font-size: 11px; font-weight: 400;"><?php echo esc_html( $data['message'] ); ?></span>
							<?php endif; ?>
							<?php if ( ! empty( $data['action'] ) ) : ?>
								<button type="button" class="naboo-btn-elegant run-maintenance-action" data-action="<?php echo esc_attr( $data['action'] ); ?>" style="margin-top: 8px; font-size: 11px; padding: 2px 10px !important;">
									<?php esc_html_e( 'Try Fix', 'naboodatabase' ); ?>
								</button>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<?php if ( $score < 100 ) : ?>
				<div style="margin-top: 30px; text-align: center; padding-top: 20px; border-top: 1px solid var(--naboo-slate-100);">
					<button type="button" class="naboo-btn-primary" id="optimize-all-btn" data-action="all">
						🚀 <?php esc_html_e( 'Optimize Everything Now', 'naboodatabase' ); ?>
					</button>
				</div>
			<?php endif; ?>
		</div>
		<?php
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * Ajax: Run maintenance action
	 */
	public function ajax_health_optimize() {
		check_ajax_referer( 'naboo_health_optimize', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$maintenance_action = isset( $_POST['maintenance_action'] ) ? sanitize_text_field( $_POST['maintenance_action'] ) : 'all';

		try {
			// Special case for saving settings
			if ( 'save_settings' === $maintenance_action ) {
				$auto_optimize = isset( $_POST['auto_optimize'] ) ? (int) $_POST['auto_optimize'] : 0;
				update_option( 'naboo_auto_optimize', $auto_optimize );
				if ( $auto_optimize ) {
					if ( ! wp_next_scheduled( 'naboo_auto_optimize_event' ) ) {
						wp_schedule_event( strtotime( 'next sunday 3am' ), 'weekly', 'naboo_auto_optimize_event' );
					}
				} else {
					wp_clear_scheduled_hook( 'naboo_auto_optimize_event' );
				}
				$message = __( 'Health settings saved.', 'naboodatabase' );
			} else {
				$message = $this->maintenance->execute_action( $maintenance_action );
			}

			wp_send_json_success( array( 'message' => $message ) );

		} catch ( \Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * Run all automated maintenance tasks. Delegate to maintenance manager.
	 */
	public function run_automated_maintenance() {
		$this->maintenance->run_automated_maintenance();
	}
}
