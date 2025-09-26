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
				'provider_mode'       => 'free',
				'free_providers'      => array( 'ip-api', 'freeipapi' ),
				'paid_provider'       => '',
				'primary_provider'    => 'ip-api',
				'active_providers'    => array( 'ip-api' ),
				'api_keys'            => array(),
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
		} else {
			// Update existing options with new defaults for v1.1.0
			$existing_options = get_option( 'accessdefender_options', array() );
			$updated = false;
			
			if ( ! isset( $existing_options['provider_mode'] ) ) {
				$existing_options['provider_mode'] = 'free';
				$updated = true;
			}
			
			if ( ! isset( $existing_options['free_providers'] ) ) {
				$existing_options['free_providers'] = array( 'ip-api', 'freeipapi' );
				$updated = true;
			}
			
			if ( ! isset( $existing_options['paid_provider'] ) ) {
				$existing_options['paid_provider'] = '';
				$updated = true;
			}
			
			if ( ! isset( $existing_options['primary_provider'] ) ) {
				$existing_options['primary_provider'] = 'ip-api';
				$updated = true;
			}
			
			if ( ! isset( $existing_options['active_providers'] ) ) {
				$existing_options['active_providers'] = array( 'ip-api' );
				$updated = true;
			}
			
			if ( ! isset( $existing_options['api_keys'] ) ) {
				$existing_options['api_keys'] = array();
				$updated = true;
			}
			
			if ( $updated ) {
				update_option( 'accessdefender_options', $existing_options );
			}
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
