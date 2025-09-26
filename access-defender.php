<?php
/**
 * Plugin Name: Access Defender
 * Description: Blocks users using VPN or proxy while allowing search engines and legitimate bots.
 * Version: 1.0.4
 * Author: Huzaifa Al Mesbah
 * Text Domain: access-defender
 * License: GPLv2 or later
 *
 * @package AccessDefender
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'ACCESS_DEFENDER_VERSION', '1.0.4' );
define( 'ACCESS_DEFENDER_FILE', __FILE__ );
define( 'ACCESS_DEFENDER_PATH', plugin_dir_path( __FILE__ ) );
define( 'ACCESS_DEFENDER_URL', plugin_dir_url( __FILE__ ) );

// Load Composer autoloader.
require_once ACCESS_DEFENDER_PATH . 'vendor/autoload.php';

// Load our custom autoloader for new v1.1.0 classes
spl_autoload_register( function ( $class ) {
	// Only handle AccessDefender namespace
	if ( strpos( $class, 'AccessDefender\\' ) !== 0 ) {
		return;
	}

	// Convert namespace to file path
	$class_file = str_replace( 'AccessDefender\\', '', $class );
	$class_file = str_replace( '\\', '/', $class_file );
	$file_path  = ACCESS_DEFENDER_PATH . 'includes/' . $class_file . '.php';

	if ( file_exists( $file_path ) ) {
		require_once $file_path;
	}
} );

// Register activation, deactivation, and uninstall hooks.
register_activation_hook( __FILE__, array( 'AccessDefender\Core\ActivationHooks', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'AccessDefender\Core\ActivationHooks', 'deactivate' ) );
register_uninstall_hook( __FILE__, array( 'AccessDefender\Core\ActivationHooks', 'uninstall' ) );

/**
 * Initializes the Access Defender plugin.
 *
 * This function creates a new instance of the Access Defender plugin
 * and calls its initialization method to set up the plugin functionality.
 *
 * @return void
 */
function initialize_access_defender_plugin() {
	$plugin = new AccessDefender\Core\Plugin();
	$plugin->init();
	
	// Initialize Appsero Tracker
	AccessDefender\Core\Tracker::init();
}

add_action( 'plugins_loaded', 'initialize_access_defender_plugin' );
