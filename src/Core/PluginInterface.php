<?php
/**
 * Plugin Interface File
 *
 * This file contains the PluginInterface which defines the contract
 * for the main plugin functionality.
 *
 * @package AccessDefender
 * @subpackage Core
 */

namespace AccessDefender\Core;

/**
 * Plugin Interface
 *
 * Defines the contract that plugin implementations must follow.
 */
interface PluginInterface {

	/**
	 * Initialize the plugin
	 *
	 * Sets up all hooks, filters, and other functionality.
	 *
	 * @return void
	 */
	public function init(): void;

	/**
	 * Check user access
	 *
	 * Performs access control checks for VPN and proxy usage.
	 *
	 * @return void
	 */
	public function check_access(): void;

	/**
	 * Enqueue admin assets
	 *
	 * Loads necessary CSS and JavaScript files for the admin interface.
	 *
	 * @param string $hook The current admin page hook.
	 * @return void
	 */
	public function enqueue_admin_assets( string $hook ): void;
}
