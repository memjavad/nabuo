<?php
/**
 * Settings Tab: Roles API
 *
 * @package ArabPsychology\NabooDatabase\Admin\Settings
 */

namespace ArabPsychology\NabooDatabase\Admin\Settings;

class Tab_Roles {

	public function render() {
		global $wp_roles;
		$naboo_roles = array(
			'scale_editor'      => array( 'color' => 'blue',   'icon' => '✏️' ),
			'scale_reviewer'    => array( 'color' => 'amber',  'icon' => '🔍' ),
			'scale_contributor' => array( 'color' => 'green',  'icon' => '📤' ),
		);
		?>
		<div class="naboo-admin-grid">

			<div class="naboo-admin-card span-full">
				<div class="naboo-admin-card-header">
					<span class="naboo-admin-card-icon purple">👥</span>
					<h3><?php esc_html_e( 'Custom Roles', 'naboodatabase' ); ?></h3>
				</div>
				<div class="naboo-notice info">
					<span>ℹ️</span>
					<span><?php esc_html_e( 'These roles are created automatically by the plugin. Assign them to users from WordPress Users → Edit User.', 'naboodatabase' ); ?></span>
				</div>
				<div class="naboo-admin-grid cols-3" style="margin-top:16px;">
					<?php foreach ( $naboo_roles as $role_key => $meta ) :
						$role = $wp_roles->roles[ $role_key ] ?? null;
						if ( ! $role ) continue;
						$user_count = get_users( array( 'role' => $role_key, 'count_total' => true, 'fields' => 'ID' ) );
					?>
					<div class="naboo-admin-card" style="margin:0;">
						<div class="naboo-admin-card-header">
							<span class="naboo-admin-card-icon <?php echo esc_attr( $meta['color'] ); ?>"><?php echo esc_html( $meta['icon'] ); ?></span>
							<h3><?php echo esc_html( translate_user_role( $role['name'] ) ); ?></h3>
						</div>
						<p class="naboo-admin-subtitle" style="margin-bottom:14px;font-size:13px;color:var(--naboo-text-muted);">
							<strong><?php echo absint( count( $user_count ) ); ?></strong> <?php esc_html_e( 'assigned users', 'naboodatabase' ); ?>
						</p>
						<p style="font-size:12px;color:var(--naboo-text-muted);font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px;"><?php esc_html_e( 'Capabilities', 'naboodatabase' ); ?></p>
						<ul style="margin:0;padding:0;list-style:none;display:flex;flex-direction:column;gap:4px;">
							<?php foreach ( array_keys( $role['capabilities'] ) as $cap ) : ?>
								<li style="font-size:12px;background:var(--naboo-bg);padding:4px 8px;border-radius:4px;font-family:monospace;color:var(--naboo-text-muted);"><?php echo esc_html( $cap ); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="naboo-admin-card span-full">
				<div class="naboo-admin-card-header">
					<span class="naboo-admin-card-icon amber">👤</span>
					<h3><?php esc_html_e( 'Users by Role', 'naboodatabase' ); ?></h3>
				</div>
				<table class="naboo-admin-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Role', 'naboodatabase' ); ?></th>
							<th><?php esc_html_e( 'Slug', 'naboodatabase' ); ?></th>
							<th><?php esc_html_e( 'User Count', 'naboodatabase' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'naboodatabase' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $wp_roles->roles as $role_key => $role ) :
							$users = get_users( array( 'role' => $role_key, 'count_total' => true, 'fields' => 'ID' ) );
						?>
						<tr>
							<td><strong><?php echo esc_html( translate_user_role( $role['name'] ) ); ?></strong></td>
							<td><code style="font-size:12px;background:var(--naboo-bg);padding:2px 6px;border-radius:4px;"><?php echo esc_html( $role_key ); ?></code></td>
							<td><span class="naboo-badge <?php echo absint( count( $users ) ) > 0 ? 'naboo-badge-green' : 'naboo-badge-gray'; ?>"><?php echo absint( count( $users ) ); ?></span></td>
							<td>
								<a href="<?php echo esc_url( admin_url( 'users.php?role=' . $role_key ) ); ?>" class="naboo-btn naboo-btn-secondary" style="padding:6px 12px;font-size:12px;">
									<?php esc_html_e( 'Manage', 'naboodatabase' ); ?> →
								</a>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}
}
