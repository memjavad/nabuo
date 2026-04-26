<?php
/**
 * Bloat Section View
 *
 * @package ArabPsychology\NabooDatabase\Admin\Performance\Views
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

$options = get_option( $this->option_name, array() );
?>
<!-- UI: BLOAT SECTION -->
<div class="naboo-admin-card">
	<h2><?php esc_html_e( 'Disable WordPress Bloat', 'naboodatabase' ); ?></h2>
	<table class="form-table">
		<?php
		$clean_options = array(
			'disable_emojis'           => __( 'Disable Emojis', 'naboodatabase' ),
			'disable_embeds'           => __( 'Disable Embeds', 'naboodatabase' ),
			'disable_xmlrpc'           => __( 'Disable XML-RPC', 'naboodatabase' ),
			'disable_heartbeat'        => __( 'Disable Heartbeat API', 'naboodatabase' ),
			'disable_author_pages'     => __( 'Disable Author Archives', 'naboodatabase' ),
			'disable_attachment_pages' => __( 'Disable Attachment Pages', 'naboodatabase' ),
			'disable_update_emails'    => __( 'Disable Auto-Update Emails', 'naboodatabase' ),
			'disable_pingbacks'        => __( 'Disable All Pingbacks', 'naboodatabase' ),
			'disable_native_comments'  => __( 'Disable Native Comments', 'naboodatabase' ),
			'disable_theme_assets'     => __( 'Disable Active Theme Assets', 'naboodatabase' ),
		);
		foreach ( $clean_options as $key => $label ) :
		?>
		<tr>
			<th scope="row"><?php echo esc_html( $label ); ?></th>
			<td>
				<label class="naboo-switch">
					<input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( isset( $options[ $key ] ) ? $options[ $key ] : 0 ); ?> />
					<span class="slider round"></span>
				</label>
			</td>
		</tr>
		<?php endforeach; ?>
	</table>
</div>
