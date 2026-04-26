<?php
/**
 * Caching Config Section View
 *
 * @package ArabPsychology\NabooDatabase\Admin\Performance\Views
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

$options = get_option( $this->option_name, array() );
?>
<!-- UI: CACHING CONFIG SECTION -->
<div class="naboo-admin-card">
	<h2><?php esc_html_e( 'Caching TTL & Security', 'naboodatabase' ); ?></h2>
	<table class="form-table">
		<tr>
			<th scope="row">Object Cache TTL (s)</th>
			<td><input type="number" name="<?php echo esc_attr( $this->option_name ); ?>[cache_ttl]" value="<?php echo esc_attr( $options['cache_ttl'] ?? 3600 ); ?>" /></td>
		</tr>
		<tr>
			<th scope="row">Page Cache TTL (s)</th>
			<td><input type="number" name="<?php echo esc_attr( $this->option_name ); ?>[page_cache_ttl]" value="<?php echo esc_attr( $options['page_cache_ttl'] ?? 3600 ); ?>" /></td>
		</tr>
		<tr>
			<th scope="row">Revisions Limit</th>
			<td>
				<select name="<?php echo esc_attr( $this->option_name ); ?>[post_revisions_limit]">
					<option value="" <?php selected( $options['post_revisions_limit'] ?? '', '' ); ?>>Unlimited</option>
					<option value="0" <?php selected( $options['post_revisions_limit'] ?? '', '0' ); ?>>Disabled</option>
					<option value="5" <?php selected( $options['post_revisions_limit'] ?? '', '5' ); ?>>5 Versions</option>
				</select>
			</td>
		</tr>
	</table>
</div>
