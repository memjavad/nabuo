# False Positive: Missing Nonce Check in AJAX Settings Export

The security vulnerability reported in `includes/admin/class-settings-ajax.php:90` regarding a missing `check_ajax_referer` call in `ajax_export_settings()` is a **false positive**.

## Investigation

The current state of the `main` branch includes the `check_ajax_referer` call explicitly:

```php
	/**
	 * AJAX Handler: Export settings as JSON string.
	 */
	public function ajax_export_settings() {
		check_ajax_referer( 'naboo_settings_import_export_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized.' ) );
		}
		$options = get_option( $this->option_name, array() );
		wp_send_json_success( array( 'json' => wp_json_encode( $options ) ) );
	}
```

The issue description indicated the code looked like this:
```php
public function ajax_export_settings() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( __( 'Unauthorized.', 'naboodatabase' ) );
    }
    $options = get_option( 'naboo_security_options', array() );
```

Since the code correctly validates the `naboo_settings_import_export_nonce` nonce, the CSRF vulnerability does not exist in the current version of the plugin. No code changes were needed. Other AJAX handlers in the application were also investigated to rule out misidentified locations for this vulnerability.
