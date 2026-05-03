<?php
/**
 * Search Admin View
 *
 * @package ArabPsychology\NabooDatabase\Admin\Search
 */

namespace ArabPsychology\NabooDatabase\Admin\Search;

/**
 * Search_Admin_View class - Renders the search admin dashboard.
 */
class Search_Admin_View {

	/**
	 * Render the admin page.
	 *
	 * @param string $tab      Current tab.
	 * @param array  $stats    Search index statistics.
	 * @param array  $settings Search settings.
	 * @param bool   $cache_active Cache status.
	 * @param array  $status_counts Post status diagnostics.
	 */
	public function render_page( $tab, $stats, $settings, $cache_active, $status_counts ) {
		settings_errors( 'naboo_search_notices' );
		?>

		?>
		<div class="wrap naboo-admin-page">

			<!-- Header -->
			<div class="naboo-admin-header" style="margin-bottom: 32px; padding: 40px; background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border-radius: 16px; color: white; box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1); position: relative; overflow: hidden;">
				<div style="position: absolute; top: -50px; right: -50px; width: 200px; height: 200px; background: rgba(99, 102, 241, 0.1); filter: blur(80px); border-radius: 50%;"></div>
				<div style="display: flex; justify-content: space-between; align-items: center; position: relative; z-index: 1;">
					<div class="naboo-admin-header-left">
						<h1 style="color: white !important; font-size: 36px !important; margin: 0 !important; font-weight: 800; letter-spacing: -0.025em; display: flex; align-items: center; gap: 20px;">
							<span style="background: rgba(255,255,255,0.1); width: 64px; height: 64px; display: flex; align-items: center; justify-content: center; border-radius: 16px; backdrop-filter: blur(4px); border: 1px solid rgba(255,255,255,0.1);">🔍</span>
							<?php esc_html_e( 'Search Engine Manager', 'naboodatabase' ); ?>
						</h1>
						<p style="margin: 16px 0 0 84px !important; color: #94a3b8; font-size: 18px; max-width: 600px; line-height: 1.6;"><?php esc_html_e( 'Manage the optimized search index, monitor performance, and tune discovery algorithms for psychological scales.', 'naboodatabase' ); ?></p>
					</div>
					<div class="naboo-admin-header-right">
						<div style="background: rgba(255,255,255,0.05); padding: 20px 24px; border-radius: 16px; border: 1px solid rgba(255,255,255,0.1); backdrop-filter: blur(4px); text-align: center;">
							<div style="font-size: 24px; font-weight: 800; color: white;"><?php echo esc_html( $stats['coverage'] ); ?>%</div>
							<div style="font-size: 12px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; margin-top: 4px;"><?php esc_html_e( 'Index Coverage', 'naboodatabase' ); ?></div>
						</div>
					</div>
				</div>
			</div>

				<!-- Diagnostic: real counts by post_status -->
				<?php
				$status_counts = $status_counts;
				$total_all     = array_sum( array_column( $status_counts, 'cnt' ) );
				?>
				<div class="naboo-admin-grid" style="margin-top:40px;">
					<div class="naboo-admin-card span-full" style="background: white; border-radius: 20px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);">
						<div class="naboo-admin-card-header" style="padding: 24px 32px; border-bottom: 1px solid #f1f5f9; background: #f8fafc; display: flex; align-items: center; gap: 16px;">
							<span style="background: #fff7ed; color: #ea580c; width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px;">🔬</span>
							<h3 style="margin: 0; font-size: 20px; font-weight: 800; color: #1e293b; letter-spacing: -0.01em;"><?php esc_html_e( 'Real-time Diagnostic', 'naboodatabase' ); ?></h3>
						</div>
						<div style="padding: 32px;">
							<p style="color:#64748b;font-size:14px;margin:0 0 24px; line-height: 1.6;">
								<?php esc_html_e( 'Direct analysis of psych_scale posts stored in the database. Only "Published" scales are synchronized with the optimized search index.', 'naboodatabase' ); ?>
							</p>
						<table class="naboo-log-table">
							<thead><tr>
								<th><?php esc_html_e( 'Post Status', 'naboodatabase' ); ?></th>
								<th><?php esc_html_e( 'Count', 'naboodatabase' ); ?></th>
								<th><?php esc_html_e( 'Indexed?', 'naboodatabase' ); ?></th>
							</tr></thead>
							<tbody>
							<?php foreach ( $status_counts as $row ) : ?>
								<tr>
									<td><code><?php echo esc_html( $row->post_status ); ?></code></td>
									<td><strong><?php echo esc_html( number_format( $row->cnt ) ); ?></strong></td>
									<td>
										<?php if ( $row->post_status === 'publish' ) : ?>
											<span class="status-badge success">✅ Yes — included in rebuild</span>
										<?php else : ?>
											<span class="status-badge gray">❌ No — only publish is indexed</span>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
								<tr style="background:#f8fafc;font-weight:700;">
									<td><?php esc_html_e( 'TOTAL', 'naboodatabase' ); ?></td>
									<td><?php echo esc_html( number_format( $total_all ) ); ?></td>
									<td></td>
								</tr>
							</tbody>
						</table>
						<p class="description" style="margin-top:10px;">
							<?php esc_html_e( 'If most scales are in "draft", "pending", or a custom status — they will not appear in search. Publish them first, then rebuild the index.', 'naboodatabase' ); ?>
						</p>
					</div>
				</div>
			</div>

			<!-- Tabs -->
			<div class="naboo-tabs-container" style="margin-bottom: 32px; display: flex; gap: 4px; background: #f1f5f9; padding: 6px; border-radius: 14px; width: fit-content; border: 1px solid #e2e8f0; margin-top: 40px;">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=naboo-search-admin&tab=overview' ) ); ?>" class="naboo-tab-btn <?php echo $tab === 'overview' ? 'active' : ''; ?>" style="text-decoration:none; display:flex; align-items:center; gap:8px; padding: 12px 24px; border-radius: 10px; font-weight: 700; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); background: <?php echo $tab === 'overview' ? 'white' : 'transparent'; ?>; color: <?php echo $tab === 'overview' ? '#1e293b' : '#64748b'; ?>; <?php echo $tab === 'overview' ? 'box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1); border: 1px solid #e2e8f0;' : ''; ?>">
					<span>📊</span> <?php esc_html_e( 'Overview', 'naboodatabase' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=naboo-search-admin&tab=index' ) ); ?>" class="naboo-tab-btn <?php echo $tab === 'index' ? 'active' : ''; ?>" style="text-decoration:none; display:flex; align-items:center; gap:8px; padding: 12px 24px; border-radius: 10px; font-weight: 700; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); background: <?php echo $tab === 'index' ? 'white' : 'transparent'; ?>; color: <?php echo $tab === 'index' ? '#1e293b' : '#64748b'; ?>; <?php echo $tab === 'index' ? 'box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1); border: 1px solid #e2e8f0;' : ''; ?>">
					<span>🗄️</span> <?php esc_html_e( 'Index Management', 'naboodatabase' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=naboo-search-admin&tab=settings' ) ); ?>" class="naboo-tab-btn <?php echo $tab === 'settings' ? 'active' : ''; ?>" style="text-decoration:none; display:flex; align-items:center; gap:8px; padding: 12px 24px; border-radius: 10px; font-weight: 700; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); background: <?php echo $tab === 'settings' ? 'white' : 'transparent'; ?>; color: <?php echo $tab === 'settings' ? '#1e293b' : '#64748b'; ?>; <?php echo $tab === 'settings' ? 'box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1); border: 1px solid #e2e8f0;' : ''; ?>">
					<span>⚙️</span> <?php esc_html_e( 'Settings', 'naboodatabase' ); ?>
				</a>
			</div>

			<?php if ( 'overview' === $tab ) : ?>

				<!-- Stats row -->
				<div class="naboo-search-stats-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px;">
					<div class="naboo-search-stat-card" style="background: white; border-radius: 20px; padding: 32px; box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1); border: 1px solid #e2e8f0; position: relative; overflow: hidden;">
						<div style="position: absolute; top: 0; right: 0; width: 64px; height: 64px; background: rgba(99, 102, 241, 0.03); border-radius: 0 0 0 64px;"></div>
						<div class="stat-icon" style="background: #eef2ff; color: #4f46e5; width: 56px; height: 56px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px; margin-bottom: 20px;">📦</div>
						<div class="stat-value" style="font-size: 32px; font-weight: 800; color: #1e293b; letter-spacing: -0.02em;"><?php echo esc_html( number_format( $stats['total'] ) ); ?></div>
						<div class="stat-label" style="font-size: 14px; font-weight: 600; color: #64748b; margin-top: 4px;"><?php esc_html_e( 'Indexed Scales', 'naboodatabase' ); ?></div>
					</div>
					<div class="naboo-search-stat-card" style="background: white; border-radius: 20px; padding: 32px; box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1); border: 1px solid #e2e8f0; position: relative; overflow: hidden;">
						<div style="position: absolute; top: 0; right: 0; width: 64px; height: 64px; background: rgba(14, 165, 233, 0.03); border-radius: 0 0 0 64px;"></div>
						<div class="stat-icon" style="background: #f0f9ff; color: #0ea5e9; width: 56px; height: 56px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px; margin-bottom: 20px;">📰</div>
						<div class="stat-value" style="font-size: 32px; font-weight: 800; color: #1e293b; letter-spacing: -0.02em;"><?php echo esc_html( number_format( $stats['published'] ) ); ?></div>
						<div class="stat-label" style="font-size: 14px; font-weight: 600; color: #64748b; margin-top: 4px;"><?php esc_html_e( 'Live Submissions', 'naboodatabase' ); ?></div>
					</div>
					<div class="naboo-search-stat-card" style="background: white; border-radius: 20px; padding: 32px; box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1); border: 1px solid #e2e8f0; position: relative; overflow: hidden;">
						<div style="position: absolute; top: 0; right: 0; width: 64px; height: 64px; background: rgba(16, 185, 129, 0.03); border-radius: 0 0 0 64px;"></div>
						<div class="stat-icon" style="background: #ecfdf5; color: #10b981; width: 56px; height: 56px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px; margin-bottom: 20px;">📎</div>
						<div class="stat-value" style="font-size: 32px; font-weight: 800; color: #1e293b; letter-spacing: -0.02em;"><?php echo esc_html( number_format( $stats['with_file'] ) ); ?></div>
						<div class="stat-label" style="font-size: 14px; font-weight: 600; color: #64748b; margin-top: 4px;"><?php esc_html_e( 'With Files', 'naboodatabase' ); ?></div>
					</div>
					<div class="naboo-search-stat-card" style="background: white; border-radius: 20px; padding: 32px; box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1); border: 1px solid #e2e8f0; position: relative; overflow: hidden;">
						<div style="position: absolute; top: 0; right: 0; width: 64px; height: 64px; background: rgba(245, 158, 11, 0.03); border-radius: 0 0 0 64px;"></div>
						<div class="stat-icon" style="background: #fffbeb; color: #f59e0b; width: 56px; height: 56px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px; margin-bottom: 20px;">🌐</div>
						<div class="stat-value" style="font-size: 32px; font-weight: 800; color: #1e293b; letter-spacing: -0.02em;"><?php echo esc_html( $stats['languages'] ); ?></div>
						<div class="stat-label" style="font-size: 14px; font-weight: 600; color: #64748b; margin-top: 4px;"><?php esc_html_e( 'Languages', 'naboodatabase' ); ?></div>
					</div>
				</div>

				<!-- Health Cards -->
				<div class="naboo-admin-grid" style="margin-top:40px;">
					<div class="naboo-admin-card" style="background: white; border-radius: 20px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);">
						<div class="naboo-admin-card-header" style="padding: 24px 32px; border-bottom: 1px solid #f1f5f9; background: #f8fafc; display: flex; align-items: center; gap: 16px;">
							<span style="background: #dcfce7; color: #15803d; width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px;">🏥</span>
							<h3 style="margin: 0; font-size: 20px; font-weight: 800; color: #1e293b; letter-spacing: -0.01em;"><?php esc_html_e( 'Engine Health', 'naboodatabase' ); ?></h3>
						</div>
						<div style="padding: 32px;">
						<ul class="naboo-security-status-list">
							<li>
								<span><?php esc_html_e( 'Table Exists', 'naboodatabase' ); ?></span>
								<span class="status-badge <?php echo $stats['exists'] ? 'success' : 'danger'; ?>">
									<?php echo $stats['exists'] ? 'Yes' : 'No'; ?>
								</span>
							</li>
							<li>
								<span><?php esc_html_e( 'Coverage', 'naboodatabase' ); ?></span>
								<span class="status-badge <?php echo $stats['coverage'] >= 90 ? 'success' : ( $stats['coverage'] >= 50 ? 'warning' : 'danger' ); ?>">
									<?php echo esc_html( $stats['coverage'] ); ?>%
								</span>
							</li>
							<li>
								<span><?php esc_html_e( 'Filter Cache', 'naboodatabase' ); ?></span>
								<span class="status-badge <?php echo $cache_active ? 'success' : 'gray'; ?>">
									<?php echo $cache_active ? 'Active' : 'Empty'; ?>
								</span>
							</li>
							<li>
								<span><?php esc_html_e( 'FULLTEXT Search', 'naboodatabase' ); ?></span>
								<span class="status-badge <?php echo ! empty( $settings['enable_fulltext'] ) ? 'success' : 'warning'; ?>">
									<?php echo ! empty( $settings['enable_fulltext'] ) ? 'Enabled' : 'Disabled'; ?>
								</span>
							</li>
							<li>
								<span><?php esc_html_e( 'Auto-Sync on Save', 'naboodatabase' ); ?></span>
								<span class="status-badge <?php echo ! empty( $settings['auto_sync'] ) ? 'success' : 'warning'; ?>">
									<?php echo ! empty( $settings['auto_sync'] ) ? 'Active' : 'Off'; ?>
								</span>
							</li>
						</ul>
					</div>
				</div>

				<div class="naboo-admin-card" style="background: white; border-radius: 20px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);">
						<div class="naboo-admin-card-header" style="padding: 24px 32px; border-bottom: 1px solid #f1f5f9; background: #f8fafc; display: flex; align-items: center; gap: 16px;">
							<span style="background: #e0f2fe; color: #0284c7; width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px;">📊</span>
							<h3 style="margin: 0; font-size: 20px; font-weight: 800; color: #1e293b; letter-spacing: -0.01em;"><?php esc_html_e( 'Index Coverage', 'naboodatabase' ); ?></h3>
						</div>
						<div style="padding: 32px;">

						<div style="text-align:center; padding: 20px 0;">
							<?php
							$pct = $stats['coverage'];
							$color = $pct >= 90 ? '#10b981' : ( $pct >= 50 ? '#f59e0b' : '#ef4444' );
							$not_indexed = max( 0, $stats['published'] - $stats['total'] );
							?>
							<div style="width:120px;height:120px;border-radius:50%;border:10px solid <?php echo esc_attr( $color ); ?>;display:flex;align-items:center;justify-content:center;margin:0 auto;">
								<span style="font-size:24px;font-weight:800;color:<?php echo esc_attr( $color ); ?>;"><?php echo esc_html( $pct ); ?>%</span>
							</div>
							<p style="margin-top:12px;color:#64748b;font-size:13px;">
								<?php echo esc_html( number_format( $not_indexed ) ); ?> <?php esc_html_e( 'scales not yet indexed', 'naboodatabase' ); ?>
							</p>
							<?php if ( $stats['min_year'] && $stats['max_year'] ) : ?>
							<p style="color:#94a3b8;font-size:12px;"><?php esc_html_e( 'Years', 'naboodatabase' ); ?>: <?php echo esc_html( $stats['min_year'] . ' – ' . $stats['max_year'] ); ?></p>
							<?php endif; ?>
						</div>
					</div>
				</div>

				<div class="naboo-admin-card" style="background: white; border-radius: 20px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);">
						<div class="naboo-admin-card-header" style="padding: 24px 32px; border-bottom: 1px solid #f1f5f9; background: #f8fafc; display: flex; align-items: center; gap: 16px;">
							<span style="background: #f3f4f6; color: #4b5563; width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px;">⚡</span>
							<h3 style="margin: 0; font-size: 20px; font-weight: 800; color: #1e293b; letter-spacing: -0.01em;"><?php esc_html_e( 'Engine Control', 'naboodatabase' ); ?></h3>
						</div>
						<div style="padding: 32px;">
						<form method="post" style="padding:5px 0;">
							<?php wp_nonce_field( 'naboo_search_action' ); ?>
							<div style="display:flex;flex-direction:column;gap:10px;">
								<button type="submit" name="naboo_rebuild_index" value="1" class="naboo-action-btn naboo-btn-primary" onclick="return confirm('<?php esc_attr_e( 'This will re-index all published scales. Continue?', 'naboodatabase' ); ?>')">
									🔄 <?php esc_html_e( 'Rebuild Full Index', 'naboodatabase' ); ?>
								</button>
								<button type="submit" name="naboo_clear_cache" value="1" class="naboo-action-btn naboo-btn-secondary">
									🗑️ <?php esc_html_e( 'Clear Filter Cache', 'naboodatabase' ); ?>
								</button>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=naboo-search-admin&tab=settings' ) ); ?>" class="naboo-action-btn naboo-btn-outline">
									⚙️ <?php esc_html_e( 'Search Settings', 'naboodatabase' ); ?>
								</a>
							</div>
						</form>
					</div>
				</div>
			</div>

			<?php elseif ( 'index' === $tab ) : ?>

				<div class="naboo-admin-grid">
					<div class="naboo-admin-card span-full">
						<div class="naboo-admin-card-header">
							<span class="naboo-admin-card-icon blue">🗄️</span>
							<h3><?php esc_html_e( 'Search Index Table', 'naboodatabase' ); ?></h3>
						</div>

						<?php if ( ! $stats['exists'] ) : ?>
							<div class="naboo-notice error">
								<p><?php esc_html_e( '⚠️ The search index table does not exist yet. Click "Create & Rebuild" below to initialize it.', 'naboodatabase' ); ?></p>
							</div>
						<?php endif; ?>

						<table class="naboo-log-table" style="margin-top:15px;">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Property', 'naboodatabase' ); ?></th>
									<th><?php esc_html_e( 'Value', 'naboodatabase' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<tr><td><?php esc_html_e( 'Table Name', 'naboodatabase' ); ?></td><td><code>wp_<?php echo esc_html( \ArabPsychology\NabooDatabase\Admin\Database_Indexer::TABLE_NAME ); ?></code></td></tr>
								<tr><td><?php esc_html_e( 'Table Status', 'naboodatabase' ); ?></td><td><?php echo $stats['exists'] ? '<span class="status-badge success">Exists</span>' : '<span class="status-badge danger">Missing</span>'; ?></td></tr>
								<tr><td><?php esc_html_e( 'Indexed Rows', 'naboodatabase' ); ?></td><td><strong><?php echo esc_html( number_format( $stats['total'] ) ); ?></strong></td></tr>
								<tr><td><?php esc_html_e( 'Published Scales', 'naboodatabase' ); ?></td><td><?php echo esc_html( number_format( $stats['published'] ) ); ?></td></tr>
								<tr><td><?php esc_html_e( 'Coverage', 'naboodatabase' ); ?></td><td><?php echo esc_html( $stats['coverage'] ); ?>%</td></tr>
								<tr><td><?php esc_html_e( 'Scales with File', 'naboodatabase' ); ?></td><td><?php echo esc_html( number_format( $stats['with_file'] ) ); ?></td></tr>
								<tr><td><?php esc_html_e( 'Year Range', 'naboodatabase' ); ?></td><td><?php echo $stats['min_year'] ? esc_html( $stats['min_year'] . ' – ' . $stats['max_year'] ) : '—'; ?></td></tr>
								<tr><td><?php esc_html_e( 'Language Variants', 'naboodatabase' ); ?></td><td><?php echo esc_html( $stats['languages'] ); ?></td></tr>
								<tr><td><?php esc_html_e( 'FULLTEXT Index', 'naboodatabase' ); ?></td><td><span class="status-badge success">title, abstract, purpose, construct, population</span></td></tr>
								<tr><td><?php esc_html_e( 'Filter Cache', 'naboodatabase' ); ?></td><td><?php echo $cache_active ? '<span class="status-badge success">Active (24h TTL)</span>' : '<span class="status-badge gray">Not cached</span>'; ?></td></tr>
							</tbody>
						</table>

						<form method="post" style="margin-top:20px;">
							<?php wp_nonce_field( 'naboo_search_action' ); ?>
							<div style="display:flex;gap:10px;flex-wrap:wrap;">
								<button type="submit" name="naboo_rebuild_index" value="1" class="button button-primary" onclick="return confirm('<?php esc_attr_e( 'Re-index all published scales?', 'naboodatabase' ); ?>')">
									🔄 <?php esc_html_e( 'Rebuild Index', 'naboodatabase' ); ?>
								</button>
								<button type="submit" name="naboo_clear_cache" value="1" class="button">
									🗑️ <?php esc_html_e( 'Clear Filter Cache', 'naboodatabase' ); ?>
								</button>
							</div>
							<p class="description" style="margin-top:10px;">
								<?php esc_html_e( 'Rebuilding syncs all published scales into the flat index. Newly saved scales are auto-synced. Use this after bulk imports only.', 'naboodatabase' ); ?>
							</p>
						</form>
					</div>
				</div>

			<?php elseif ( 'settings' === $tab ) : ?>

				<form method="post">
					<?php wp_nonce_field( 'naboo_search_action' ); ?>
					<div class="naboo-admin-grid">
						<div class="naboo-admin-card">
							<div class="naboo-admin-card-header">
								<span class="naboo-admin-card-icon purple">🔍</span>
								<h3><?php esc_html_e( 'Search Behavior', 'naboodatabase' ); ?></h3>
							</div>

							<table class="form-table">
								<tr>
									<th><?php esc_html_e( 'Results Per Page', 'naboodatabase' ); ?></th>
									<td>
										<input type="number" name="results_per_page" value="<?php echo esc_attr( $settings['results_per_page'] ); ?>" min="5" max="50" style="width:80px;" />
										<p class="description"><?php esc_html_e( 'Default number of results returned per page (5–50).', 'naboodatabase' ); ?></p>
									</td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'FULLTEXT Search', 'naboodatabase' ); ?></th>
									<td>
										<label class="naboo-toggle-row">
											<input type="checkbox" name="enable_fulltext" value="1" <?php checked( 1, $settings['enable_fulltext'] ); ?> />
											<div class="toggle-info">
												<strong><?php esc_html_e( 'Enable MySQL FULLTEXT', 'naboodatabase' ); ?></strong>
												<span><?php esc_html_e( 'Uses MATCH...AGAINST for relevance-ranked keyword search.', 'naboodatabase' ); ?></span>
											</div>
										</label>
									</td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Auto-Sync on Save', 'naboodatabase' ); ?></th>
									<td>
										<label class="naboo-toggle-row">
											<input type="checkbox" name="auto_sync" value="1" <?php checked( 1, $settings['auto_sync'] ); ?> />
											<div class="toggle-info">
												<strong><?php esc_html_e( 'Auto-sync index on scale save', 'naboodatabase' ); ?></strong>
												<span><?php esc_html_e( 'Automatically updates the index when a scale is published, updated, or deleted.', 'naboodatabase' ); ?></span>
											</div>
										</label>
									</td>
								</tr>
								<tr>
									<th><?php esc_html_e( 'Min. Word Length', 'naboodatabase' ); ?></th>
									<td>
										<input type="number" name="min_word_length" value="<?php echo esc_attr( $settings['min_word_length'] ); ?>" min="2" max="6" style="width:60px;" />
										<p class="description"><?php esc_html_e( 'Words shorter than this are ignored in FULLTEXT searches (must match MySQL ft_min_word_len).', 'naboodatabase' ); ?></p>
									</td>
								</tr>
							</table>
						</div>

						<div class="naboo-admin-card">
							<div class="naboo-admin-card-header">
								<span class="naboo-admin-card-icon green">💡</span>
								<h3><?php esc_html_e( 'How It Works', 'naboodatabase' ); ?></h3>
							</div>
							<div style="font-size:13px;color:#475569;line-height:1.7;">
								<p><strong>🗄️ Flat Index Table:</strong> <?php esc_html_e( 'Scales are mirrored into a dedicated flat SQL table (wp_naboo_search_index) with FULLTEXT indexes, bypassing slow wp_postmeta JOINs.', 'naboodatabase' ); ?></p>
								<p><strong>⚡ FULLTEXT Search:</strong> <?php esc_html_e( 'Keyword queries use MySQL MATCH...AGAINST in BOOLEAN MODE for instant relevance scoring across title, abstract, purpose, construct, and population fields.', 'naboodatabase' ); ?></p>
								<p><strong>🔢 Filter Queries:</strong> <?php esc_html_e( 'Year, items, language, and other filters use indexed B-tree columns for O(log n) lookups.', 'naboodatabase' ); ?></p>
								<p><strong>🏎️ Cache:</strong> <?php esc_html_e( 'Filter options (languages, categories, year ranges) are cached for 24 hours and auto-invalidated on scale save/delete.', 'naboodatabase' ); ?></p>
								<p><strong>🔄 WP-CLI Sync:</strong> <code>wp naboo-search sync</code> <?php esc_html_e( 'for initial population or after a bulk import.', 'naboodatabase' ); ?></p>
							</div>
						</div>
					</div>

					<div class="naboo-save-bar" style="position: sticky; bottom: 24px; z-index: 100; background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(12px); padding: 24px 40px; border-radius: 20px; border: 1px solid rgba(226, 232, 240, 0.8); box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1); display: flex; justify-content: flex-end; align-items: center; margin-top: 60px;">
						<?php submit_button( __( 'Save Search Configuration', 'naboodatabase' ), 'primary naboo-btn', 'naboo_save_search_settings', false, array( 'style' => 'background: #4f46e5; color: white; border: none; padding: 14px 32px; border-radius: 12px; font-weight: 700; font-size: 15px; cursor: pointer; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2);' ) ); ?>
					</div>
				</form>

			<?php endif; ?>

		</div>

		<style>
			.naboo-admin-page { font-family: 'Inter', sans-serif !important; }
			.naboo-search-stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 24px; }
			.naboo-admin-card { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
			.naboo-admin-card:hover { transform: translateY(-2px); box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1); }

			.stat-icon { transition: transform 0.3s ease; }
			.naboo-search-stat-card:hover .stat-icon { transform: scale(1.1) rotate(5deg); }

			.naboo-action-btn { display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%; padding: 14px; border-radius: 12px; border: none; cursor: pointer; font-weight: 700; font-size: 14px; text-decoration: none; transition: all 0.3s; }
			.naboo-btn-primary { background: #4f46e5; color: #fff; box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2); }
			.naboo-btn-primary:hover { transform: translateY(-1px); filter: brightness(1.1); box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.3); }
			.naboo-btn-secondary { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
			.naboo-btn-secondary:hover { background: #e2e8f0; color: #1e293b; }
			.naboo-btn-outline { background: transparent; color: #4f46e5; border: 2px solid #4f46e5; }
			.naboo-btn-outline:hover { background: #f5f3ff; transform: translateY(-1px); }

			.status-badge { font-size: 11px; font-weight: 800; padding: 4px 12px; border-radius: 999px; text-transform: uppercase; letter-spacing: 0.05em; border: 1px solid transparent; }
			.status-badge.success { background: #ecfdf5; color: #065f46; border-color: #d1fae5; }
			.status-badge.danger  { background: #fef2f2; color: #991b1b; border-color: #fee2e2; }
			.status-badge.warning { background: #fffbeb; color: #92400e; border-color: #fef3c7; }
			.status-badge.gray    { background: #f8fafc; color: #64748b; border-color: #e2e8f0; }

			.naboo-log-table { width: 100%; border-collapse: separate; border-spacing: 0; background: #fff; border-radius: 12px; overflow: hidden; }
			.naboo-log-table th { background: #f8fafc; padding: 16px 24px; font-weight: 800; color: #475569; text-transform: uppercase; font-size: 12px; letter-spacing: 0.05em; border-bottom: 2px solid #f1f5f9; text-align: left; }
			.naboo-log-table td { padding: 18px 24px; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #334155; vertical-align: middle; }
			.naboo-log-table tr:hover td { background: #fbfcfe; }
			.naboo-log-table tr:last-child td { border-bottom: none; }

			.naboo-tab-btn:hover { background: rgba(255,255,255,0.8); }
			.naboo-tab-btn.active:hover { background: white; }

			.form-table th { font-weight: 700; color: #1e293b; padding: 20px 10px 20px 0; width: 240px; }
			.form-table td { padding: 15px 10px; }
			input[type="number"] { padding: 10px 14px; border-radius: 10px; border: 1px solid #cbd5e1; font-size: 14px; transition: all 0.2s; }
			input[type="number"]:focus { border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); outline: none; }

			.toggle-info strong { display: block; font-size: 14px; color: #1e293b; }
			.toggle-info span { font-size: 12px; color: #64748b; }
			.naboo-toggle-row { display: flex; align-items: flex-start; gap: 12px; cursor: pointer; padding: 12px; border-radius: 10px; transition: background 0.2s; }
			.naboo-toggle-row:hover { background: #f8fafc; }
			.naboo-toggle-row input[type="checkbox"] { width: 20px; height: 20px; margin-top: 2px; cursor: pointer; accent-color: #4f46e5; }

			.naboo-admin-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 24px; }
			.naboo-admin-card.span-full { grid-column: 1 / -1; }
		</style>
		<?php
		}
}
