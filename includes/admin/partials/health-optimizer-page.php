<?php
/**
 * Health Optimizer Admin Page Template
 *
 * @package ArabPsychology\NabooDatabase\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
		<link rel="preconnect" href="https://fonts.googleapis.com">
		<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
		<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
		<style>
			:root {
				--naboo-primary: #4f46e5;
				--naboo-primary-dark: #4338ca;
				--naboo-success: #10b981;
				--naboo-warning: #f59e0b;
				--naboo-danger: #ef4444;
				--naboo-slate-50: #f8fafc;
				--naboo-slate-100: #f1f5f9;
				--naboo-slate-200: #e2e8f0;
				--naboo-slate-500: #64748b;
				--naboo-slate-800: #1e293b;
				--naboo-slate-900: #0f172a;
				--naboo-radius: 12px;
				--naboo-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
				--naboo-shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
			}

			.naboo-health-wrapper {
				font-family: 'Inter', sans-serif;
				color: var(--naboo-slate-800);
				max-width: 1200px;
				margin: 20px auto;
				padding: 0 20px;
			}

			.naboo-health-header {
				display: flex;
				justify-content: space-between;
				align-items: center;
				margin-bottom: 40px;
				padding: 40px;
				background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
				border-radius: 16px;
				color: white;
				box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
				position: relative;
				overflow: hidden;
			}

			.naboo-health-header::after {
				content: '';
				position: absolute;
				top: -50px;
				right: -50px;
				width: 200px;
				height: 200px;
				background: rgba(16, 185, 129, 0.1);
				filter: blur(80px);
				border-radius: 50%;
			}

			.naboo-header-info h1 {
				color: white !important;
				font-size: 32px !important;
				font-weight: 800 !important;
				margin: 0 !important;
				display: flex;
				align-items: center;
				gap: 16px;
				letter-spacing: -0.025em;
			}

			.naboo-header-info h1 span {
				background: rgba(255,255,255,0.1);
				width: 56px;
				height: 56px;
				display: flex;
				align-items: center;
				justify-content: center;
				border-radius: 14px;
				backdrop-filter: blur(4px);
				border: 1px solid rgba(255,255,255,0.1);
			}

			.naboo-header-info p {
				color: var(--naboo-slate-200) !important;
				margin: 0 !important;
				font-size: 14px;
				opacity: 0.8;
			}

			.naboo-health-grid {
				display: grid;
				grid-template-columns: 1.5fr 1fr;
				gap: 24px;
			}

			@media (max-width: 1024px) {
				.naboo-health-grid { grid-template-columns: 1fr; }
			}

			.naboo-glass-card {
				background: white;
				border-radius: var(--naboo-radius);
				border: 1px solid var(--naboo-slate-200);
				box-shadow: var(--naboo-shadow);
				overflow: hidden;
				transition: all 0.3s ease;
			}

			.naboo-glass-card:hover {
				box-shadow: var(--naboo-shadow-lg);
				transform: translateY(-2px);
			}

			.card-header {
				padding: 20px 24px;
				border-bottom: 1px solid var(--naboo-slate-100);
				display: flex;
				align-items: center;
				gap: 12px;
				background: var(--naboo-slate-50);
			}

			.card-header h3 {
				margin: 0 !important;
				font-size: 18px !important;
				font-weight: 700 !important;
				color: var(--naboo-slate-900);
			}

			.card-body {
				padding: 24px;
			}

			/* Status Indicators */
			.health-score-ring {
				width: 120px;
				height: 120px;
				margin: 0 auto 20px;
				position: relative;
				display: flex;
				align-items: center;
				justify-content: center;
			}

			.score-circle {
				font-size: 36px;
				font-weight: 800;
				color: var(--naboo-primary);
			}

			.health-item {
				display: flex;
				justify-content: space-between;
				align-items: center;
				padding: 14px 16px;
				background: var(--naboo-slate-50);
				border-radius: 8px;
				margin-bottom: 12px;
				border: 1px solid transparent;
				transition: all 0.2s ease;
			}

			.health-item:hover {
				border-color: var(--naboo-slate-200);
				background: white;
			}

			.health-item-label {
				display: flex;
				align-items: center;
				gap: 12px;
				font-weight: 600;
				font-size: 14px;
			}

			.status-dot {
				width: 8px;
				height: 8px;
				border-radius: 50%;
				position: relative;
			}

			.status-dot::after {
				content: '';
				position: absolute;
				inset: -4px;
				border-radius: 50%;
				background: inherit;
				opacity: 0.2;
				animation: pulse 2s infinite;
			}

			@keyframes pulse {
				0% { transform: scale(1); opacity: 0.3; }
				70% { transform: scale(2.5); opacity: 0; }
				100% { transform: scale(1); opacity: 0; }
			}

			.status-dot.good { background: var(--naboo-success); }
			.status-dot.warning { background: var(--naboo-warning); }
			.status-dot.bad { background: var(--naboo-danger); }

			.health-item-value {
				font-size: 13px;
				color: var(--naboo-slate-500);
				font-weight: 500;
			}

			/* Maintenance Actions */
			.maintenance-btn-grid {
				display: grid;
				grid-template-columns: repeat(auto-fill, minmax(100%, 1fr));
				gap: 12px;
			}

			.maintenance-btn-item {
				display: flex;
				justify-content: space-between;
				align-items: center;
				padding: 16px;
				border: 1px solid var(--naboo-slate-200);
				border-radius: 10px;
				transition: all 0.2s ease;
			}

			.maintenance-btn-item:hover {
				background: var(--naboo-slate-50);
				border-color: var(--naboo-primary);
			}

			.btn-info h4 { margin: 0 0 2px 0 !important; font-size: 14px !important; color: var(--naboo-slate-900); }
			.btn-info p { margin: 0 !important; font-size: 12px; color: var(--naboo-slate-500); }

			.naboo-btn-elegant {
				background: white !important;
				border: 1px solid var(--naboo-slate-200) !important;
				color: var(--naboo-slate-900) !important;
				border-radius: 6px !important;
				font-weight: 600 !important;
				padding: 6px 16px !important;
				transition: all 0.2s ease !important;
				cursor: pointer;
			}

			.naboo-btn-elegant:hover {
				background: var(--naboo-primary) !important;
				color: white !important;
				border-color: var(--naboo-primary) !important;
			}

			.naboo-btn-primary {
				background: var(--naboo-primary) !important;
				border: none !important;
				color: white !important;
				padding: 10px 24px !important;
				border-radius: 8px !important;
				font-weight: 700 !important;
				box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.4);
			}

			.naboo-btn-primary:hover {
				background: var(--naboo-primary-dark) !important;
				transform: translateY(-1px);
			}

			/* Settings */
			.settings-row {
				padding: 16px;
				background: var(--naboo-slate-50);
				border-radius: 10px;
				border: 1px dashed var(--naboo-slate-200);
			}

			#health-results-wrapper {
				animation: fadeIn 0.4s ease-out;
			}

			@keyframes fadeIn {
				from { opacity: 0; transform: translateY(10px); }
				to { opacity: 1; transform: translateY(0); }
			}
		</style>

		<div class="naboo-health-wrapper wrap">
			<div class="naboo-health-header">
				<div class="naboo-header-info">
					<h1><span>🏥</span> <?php esc_html_e( 'Naboo Health Center', 'naboodatabase' ); ?></h1>
					<p style="margin: 12px 0 0 72px !important; color: #94a3b8; font-size: 16px;"><?php esc_html_e( 'Performance diagnostics and automated system optimization.', 'naboodatabase' ); ?></p>
				</div>
				<div class="naboo-header-badges">
					<span class="naboo-badge-glass">v<?php echo esc_html( $this->version ); ?></span>
				</div>
			</div>

			<div class="naboo-health-grid">
				<!-- Left Column: Status -->
				<div class="naboo-glass-card" id="naboo-health-status-card">
					<div class="card-header">
						<span style="font-size: 20px;">🔍</span>
						<h3><?php esc_html_e( 'System Diagnostics', 'naboodatabase' ); ?></h3>
					</div>
					<div class="card-body">
						<div id="health-idle-message" style="text-align: center; padding: 40px 0;">
							<div style="font-size: 40px; margin-bottom: 20px; opacity: 0.3;">🚀</div>
							<p style="color: var(--naboo-slate-500); margin-bottom: 24px;"><?php esc_html_e( 'Ready to perform a deep analysis of your environment.', 'naboodatabase' ); ?></p>
							<button type="button" class="naboo-btn-primary" id="run-health-scan">
								<?php esc_html_e( 'Begin Full System Scan', 'naboodatabase' ); ?>
							</button>
						</div>

						<div id="health-loading" style="display: none; text-align: center; padding: 60px 0;">
							<span class="spinner is-active" style="float: none; margin-bottom: 16px; width: 30px; height: 30px;"></span>
							<p style="font-weight: 600; color: var(--naboo-primary);"><?php esc_html_e( 'Analyzing system components...', 'naboodatabase' ); ?></p>
						</div>

						<div id="health-results" style="display: none;">
							<!-- Results injected here -->
						</div>
					</div>
				</div>

				<!-- Right Column: Actions & Settings -->
				<div class="naboo-side-column" style="display: flex; flex-direction: column; gap: 24px;">
					<!-- Quick Actions -->
					<div class="naboo-glass-card">
						<div class="card-header">
							<span style="font-size: 20px;">⚡</span>
							<h3><?php esc_html_e( 'System Tools', 'naboodatabase' ); ?></h3>
						</div>
						<div class="card-body" style="padding: 16px;">
							<div class="maintenance-btn-grid">
								<?php
								$actions = array(
									'clean_transients'     => array( 'Clean Transients', 'Clear expired temp data.' ),
									'optimize_tables'      => array( 'Optimize DB', 'Re-index plugin tables.' ),
									'flush_rewrites'       => array( 'Flush Permalinks', 'Reset URL structure.' ),
									'purge_revisions'      => array( 'Purge Revisions', 'Clear old post history.' ),
									'clean_global_content' => array( 'Clean Content', 'Clear trash and spam.' ),
									'optimize_all_tables'  => array( 'Global Optimize', 'Optimize entire database.' ),
									'scrub_media'          => array( 'Scrub Media', 'Remove unattached files.' ),
									'test_email'           => array( 'Test Email', 'Verify delivery health.' ),
									'clear_debug_log'      => array( 'Clear Logs', 'Empty debug.log file.' ),
									'fix_cron'             => array( 'Clear Failed Crons', 'Purge stuck WP-Cron events.' ),
								);

								foreach ( $actions as $action => $info ) : ?>
									<div class="maintenance-btn-item">
										<div class="btn-info">
											<h4><?php echo esc_html( $info[0] ); ?></h4>
											<p><?php echo esc_html( $info[1] ); ?></p>
										</div>
										<button type="button" class="naboo-btn-elegant run-maintenance-action" data-action="<?php echo esc_attr( $action ); ?>">
											<?php esc_html_e( 'Run', 'naboodatabase' ); ?>
										</button>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					</div>

					<!-- Settings -->
					<div class="naboo-glass-card">
						<div class="card-header">
							<span style="font-size: 20px;">⚙️</span>
							<h3><?php esc_html_e( 'Automation', 'naboodatabase' ); ?></h3>
						</div>
						<div class="card-body">
							<div class="settings-row">
								<label style="display: flex; align-items: flex-start; gap: 12px; cursor: pointer;">
									<input type="checkbox" id="naboo-auto-optimize" style="margin-top: 4px;" <?php checked( get_option( 'naboo_auto_optimize', 0 ), 1 ); ?>>
									<div>
										<span style="font-weight: 700; font-size: 14px;"><?php esc_html_e( 'Weekly Auto-Pilot', 'naboodatabase' ); ?></span>
										<p style="margin: 4px 0 0 0; font-size: 12px; color: var(--naboo-slate-500); line-height: 1.4;">
											<?php esc_html_e( 'Runs full optimization every Sunday at 3 AM.', 'naboodatabase' ); ?>
										</p>
									</div>
								</label>
							</div>
							<div style="margin-top: 20px;">
								<button type="button" class="naboo-btn-elegant" id="save-health-settings" style="width: 100%;">
									<?php esc_html_e( 'Update Automation Settings', 'naboodatabase' ); ?>
								</button>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- System Information Section -->
			<div class="naboo-health-grid cols-1" style="margin-top: 40px;">
				<?php $this->renderer->render_system_info(); ?>
			</div>
		</div>

		<script>
		jQuery(document).ready(function($) {
			$('#save-health-settings').on('click', function() {
				var btn = $(this);
				var autoOptimize = $('#naboo-auto-optimize').is(':checked') ? 1 : 0;

				btn.prop('disabled', true).text('Saving...');

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'naboo_health_optimize',
						maintenance_action: 'save_settings',
						auto_optimize: autoOptimize,
						nonce: '<?php echo wp_create_nonce( 'naboo_health_optimize' ); ?>'
					},
					success: function(response) {
						btn.prop('disabled', false).text('Update Automation Settings');
						if (response.success) {
						}
					}
				});
			});

			$('#run-health-scan').on('click', function() {
				$('#health-idle-message').hide();
				$('#health-loading').show();

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'naboo_health_check',
						nonce: '<?php echo wp_create_nonce( 'naboo_health_check' ); ?>'
					},
					success: function(response) {
						$('#health-loading').hide();
						if (response.success) {
							$('#health-results').html(response.data.html).show();
						}
					}
				});
			});

			$(document).on('click', '.run-maintenance-action, #optimize-all-btn', function() {
				var btn = $(this);
				var action = btn.data('action');
				var originalText = btn.text();

				btn.prop('disabled', true).text('...');

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'naboo_health_optimize',
						maintenance_action: action,
						nonce: '<?php echo wp_create_nonce( 'naboo_health_optimize' ); ?>'
					},
					success: function(response) {
						btn.prop('disabled', false).text(originalText);
						if (response.success) {
							if (action === 'all') {
								$('#run-health-scan').trigger('click');
							} else {
								btn.css('background', 'var(--naboo-success)').css('color', 'white');
								setTimeout(function(){
									btn.css('background', '').css('color', '');
								}, 2000);
							}
						}
					}
				});
			});
		});
		</script>
		<?php