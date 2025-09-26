<?php
/**
 * Uninstall File
 *
 * This file runs when the plugin is uninstalled via the Plugins screen.
 *
 * @package AccessDefender
 */

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Load the ActivationHooks class.
require_once plugin_dir_path( __FILE__ ) . 'includes/Core/ActivationHooks.php';

// Run uninstall tasks.
AccessDefender\Core\ActivationHooks::uninstall();
