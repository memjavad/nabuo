<?php
/**
 * System Info Renderer - Handles rendering of system details and environment information
 *
 * @package ArabPsychology\NabooDatabase\Admin\Health
 */

namespace ArabPsychology\NabooDatabase\Admin\Health;

/**
 * System_Info_Renderer class
 */
class System_Info_Renderer {

	/**
	 * Render system environment and database table details
	 */
	public function render_system_info() {
		global $wpdb;
		$tables = array(
			'naboo_dashboard_metrics',
			'naboo_email_logs',
			'naboo_file_downloads',
			'naboo_ratings',
			'naboo_favorites',
			'naboo_search_suggestions',
		);
		?>
		<div class="naboo-health-grid">
			<div class="naboo-glass-card">
				<div class="card-header">
					<span style="font-size: 20px;">🖥️</span>
					<h3><?php esc_html_e( 'Environment', 'naboodatabase' ); ?></h3>
				</div>
				<div class="card-body">
					<table class="naboo-sysinfo-table" style="width: 100%; border-collapse: collapse;">
						<style>
							.naboo-sysinfo-table td { padding: 12px 16px; border-bottom: 1px solid var(--naboo-slate-100); font-size: 14px; }
							.naboo-sysinfo-table tr:last-child td { border-bottom: none; }
							.naboo-sysinfo-table td:first-child { font-weight: 600; color: var(--naboo-slate-500); width: 40%; }
						</style>
						<tr><td><?php esc_html_e( 'Plugin Version', 'naboodatabase' ); ?></td><td><?php echo esc_html( NABOODATABASE_VERSION ); ?></td></tr>
						<tr><td><?php esc_html_e( 'WordPress Version', 'naboodatabase' ); ?></td><td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td></tr>
						<tr><td><?php esc_html_e( 'PHP Version', 'naboodatabase' ); ?></td><td><?php echo esc_html( PHP_VERSION ); ?></td></tr>
						<tr><td><?php esc_html_e( 'MySQL Version', 'naboodatabase' ); ?></td><td><?php echo esc_html( $wpdb->db_version() ); ?></td></tr>
						<tr><td><?php esc_html_e( 'Memory Limit', 'naboodatabase' ); ?></td><td><?php echo esc_html( WP_MEMORY_LIMIT ); ?></td></tr>
						<tr><td><?php esc_html_e( 'Debug Mode', 'naboodatabase' ); ?></td><td><?php echo WP_DEBUG ? '<span class="status-dot warning" style="display:inline-block;margin-right:8px;"></span>ON' : '<span class="status-dot good" style="display:inline-block;margin-right:8px;"></span>OFF'; ?></td></tr>
						<tr><td><?php esc_html_e( 'Active Theme', 'naboodatabase' ); ?></td><td><?php echo esc_html( wp_get_theme()->get( 'Name' ) ); ?></td></tr>
					</table>
				</div>
			</div>

			<div class="naboo-glass-card">
				<div class="card-header">
					<span style="font-size: 20px;">🗃️</span>
					<h3><?php esc_html_e( 'Database Tables', 'naboodatabase' ); ?></h3>
				</div>
				<div class="card-body" style="padding: 0;">
					<table class="naboo-admin-table" style="width: 100%; border-collapse: collapse;">
						<style>
							.naboo-admin-table th { background: var(--naboo-slate-50); padding: 12px 16px; text-align: left; font-size: 12px; text-transform: uppercase; color: var(--naboo-slate-500); }
							.naboo-admin-table td { padding: 12px 16px; border-bottom: 1px solid var(--naboo-slate-100); font-size: 14px; }
						</style>
						<thead>
							<tr>
								<th><?php esc_html_e( 'Table', 'naboodatabase' ); ?></th>
								<th><?php esc_html_e( 'Rows', 'naboodatabase' ); ?></th>
								<th><?php esc_html_e( 'Status', 'naboodatabase' ); ?></th>
							</tr>
						</thead>
						<tbody>
						<?php foreach ( $tables as $table ) :
							$full   = $wpdb->prefix . $table;
							$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $full ) ) === $full;
							$count  = $exists ? (int) $wpdb->get_var( "SELECT COUNT(*) FROM `$full`" ) : 0;
						?>
						<tr>
							<td><code style="font-size:12px;"><?php echo esc_html( $table ); ?></code></td>
							<td><?php echo absint( $count ); ?></td>
							<td>
								<?php if ( $exists ) : ?>
									<span class="status-dot good"></span>
								<?php else : ?>
									<span class="status-dot bad"></span>
								<?php endif; ?>
							</td>
						</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<?php
	}
}
