<?php
/**
 * Activation Hooks File
 *
 * Handles plugin activation and deactivation functionality.
 *
 * @package AccessDefender
 * @subpackage Core
 */

namespace AccessDefender\Core;

/**
 * Class ActivationHooks
 *
 * Manages plugin activation and deactivation procedures.
 */
class ActivationHooks {

	/**
	 * Handle plugin activation
	 *
	 * Sets up default options and performs any necessary initialization tasks
	 * when the plugin is activated.
	 *
	 * @return void
	 */
	public static function activate(): void {
		// Only set default options if they don't already exist.
		if ( ! get_option( 'accessdefender_options' ) ) {
			$default_options = array(
				'enable_vpn_blocking' => '1',
				'warning_title'       => esc_html__( 'Access Denied', 'access-defender' ),
				'warning_message'     => wp_kses_post(
					esc_html__(
						'We\'ve detected that you\'re using a VPN or proxy. For security reasons, access to this website is not allowed through VPNs or proxies. Please disable your VPN or proxy and try again.',
						'access-defender'
					)
				),
			);

			// Save default options to database.
			update_option( 'accessdefender_options', $default_options );
		}

		// Flush rewrite rules for clean activation.
		flush_rewrite_rules();
	}

	/**
	 * Handle plugin deactivation
	 *
	 * Performs cleanup tasks when the plugin is deactivated.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		// Flush rewrite rules for clean deactivation.
		flush_rewrite_rules();
	}

	/**
	 * Handle plugin uninstall
	 *
	 * Performs cleanup tasks when the plugin is uninstalled.
	 *
	 * @return void
	 */
	public static function uninstall(): void {
		// Remove all plugin options from database.
		delete_option( 'accessdefender_options' );
	}
}
