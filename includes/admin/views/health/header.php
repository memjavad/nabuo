<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="naboo-health-header">
<div class="naboo-header-info">
<h1><span>🏥</span> <?php esc_html_e( 'Naboo Health Center', 'naboodatabase' ); ?></h1>
<p style="margin: 12px 0 0 72px !important; color: #94a3b8; font-size: 16px;"><?php esc_html_e( 'Performance diagnostics and automated system optimization.', 'naboodatabase' ); ?></p>
</div>
<div class="naboo-header-badges">
<span class="naboo-badge-glass">v<?php echo esc_html( $this->version ); ?></span>
</div>
