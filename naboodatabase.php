<?php
/**
 * Plugin Name:       Naboo Database
 * Plugin URI:        https://arabpsychology.com/
 * Description:       A database for psychological scales. «نابو» كإله للكتابة والعقلانية المنظمة. لقد مثل نابو مراحل متقدمة من التطور المعرفي البشري وتدوين السلوكيات الاجتماعية.
 * Version:           1.55.4
 * Author:            Arab Psychology
 * Text Domain:       naboodatabase
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Current plugin version.
 */
define( 'NABOODATABASE_VERSION', '1.55.4' );
define( 'NABOODATABASE_PATH', plugin_dir_path( __FILE__ ) );
define( 'NABOODATABASE_URL', plugin_dir_url( __FILE__ ) );

/**
 * Autoloader for ArabPsychology\NabooDatabase namespace.
 *
 * @param string $class_name The fully-qualified class name.
 */
spl_autoload_register( function ( $class_name ) {
	// Project-specific namespace prefix
	$prefix = 'ArabPsychology\\NabooDatabase\\';

	// Base directory for the namespace prefix
	$base_dir = plugin_dir_path( __FILE__ ) . 'includes/';

	// Does the class use the namespace prefix?
	$len = strlen( $prefix );
	if ( strncmp( $prefix, $class_name, $len ) !== 0 ) {
		// no, move to the next registered autoloader
		return;
	}

	// Get the relative class name
	$relative_class = substr( $class_name, $len );

	// Replace the namespace prefix with the base directory, replace namespace
	// separators with directory separators in the relative class name, append
	// with .php
    $parts = explode( '\\', $relative_class );
    $class_filename = 'class-' . str_replace( '_', '-', strtolower( array_pop( $parts ) ) ) . '.php';
    
    $sub_path = '';
    if ( ! empty( $parts ) ) {
        $sub_path = str_replace( '_', '-', strtolower( implode( '/', $parts ) ) ) . '/';
    }
    
    // Special case for root classes in 'includes' which are currently namespaced as just NabooDatabase in my mind, 
    // but files are in includes/.
    
    $file = $base_dir . $sub_path . $class_filename;

	// If the file exists, require it
	if ( file_exists( $file ) ) {
		require $file;
	}
} );

/**
 * The code that runs during plugin activation.
 */
function activate_naboodatabase() {
	ArabPsychology\NabooDatabase\Activator::activate();
    // Ensure our custom DB tables exist
    ArabPsychology\NabooDatabase\Core\Installer::create_tables();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_naboodatabase() {
	ArabPsychology\NabooDatabase\Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_naboodatabase' );
register_deactivation_hook( __FILE__, 'deactivate_naboodatabase' );

/**
 * Begins execution of the plugin.
 */
function run_naboodatabase() {
	$plugin = new ArabPsychology\NabooDatabase\Core();
	$plugin->run();
}

run_naboodatabase();

/**
 * Register WP-CLI commands if running in CLI context.
 */
if ( defined( 'WP_CLI' ) && WP_CLI ) {
    WP_CLI::add_command( 'naboo', 'ArabPsychology\NabooDatabase\CLI\Naboo_CLI' );
}
