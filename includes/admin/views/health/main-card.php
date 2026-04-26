<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>
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
