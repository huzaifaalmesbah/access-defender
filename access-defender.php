<?php
/**
 * Plugin Name: Access Defender
 * Description: Blocks users using VPN or proxy while allowing search engines and legitimate bots.
 * Version: 1.1.2
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
define( 'ACCESS_DEFENDER_VERSION', '1.1.2' );
define( 'ACCESS_DEFENDER_FILE', __FILE__ );
define( 'ACCESS_DEFENDER_PATH', plugin_dir_path( __FILE__ ) );
define( 'ACCESS_DEFENDER_URL', plugin_dir_url( __FILE__ ) );

// Load Composer autoloader.
require_once ACCESS_DEFENDER_PATH . 'vendor/autoload.php';

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
