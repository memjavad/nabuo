<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>
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
