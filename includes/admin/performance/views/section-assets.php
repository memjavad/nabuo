<?php
/**
 * Assets Section View
 *
 * @package ArabPsychology\NabooDatabase\Admin\Performance\Views
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

$options = get_option( $this->option_name, array() );
?>
<!-- UI: ASSET SECTION -->
<div class="naboo-admin-card">
	<h2><?php esc_html_e( 'Optimization & Assets', 'naboodatabase' ); ?></h2>
	<table class="form-table">
		<?php
		$asset_options = array(
			'remove_query_strings'  => __( 'Remove Query Strings', 'naboodatabase' ),
			'disable_block_css'     => __( 'Remove Gutenberg CSS', 'naboodatabase' ),
			'consolidate_assets'    => __( 'Consolidate Naboo Assets', 'naboodatabase' ),
			'defer_js'              => __( 'Defer JavaScript', 'naboodatabase' ),
			'minify_html'           => __( 'Minify HTML Output', 'naboodatabase' ),
			'clean_head'            => __( 'Clean Head Garbage', 'naboodatabase' ),
			'preload_assets'        => __( 'Preload Stylesheets', 'naboodatabase' ),
			'disable_global_styles' => __( 'Disable Global Scripts', 'naboodatabase' ),
		);
		foreach ( $asset_options as $key => $label ) :
		?>
		<tr>
			<th scope="row"><?php echo esc_html( $label ); ?></th>
			<td>
				<label class="naboo-switch">
					<input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( isset( $options[ $key ] ) ? $options[ $key ] : 0 ); ?> />
					<span class="slider round"></span>
				</label>
				<?php if ( $key === 'consolidate_assets' ) : ?>
					<button type="button" class="button button-link" id="naboo-clear-cache" style="font-size:11px;"><?php esc_html_e( 'Clear Cache', 'naboodatabase' ); ?></button>
				<?php endif; ?>
			</td>
		</tr>
		<?php endforeach; ?>
	</table>
</div>
