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
		// Set up core plugin settings (persistent data)
		self::setup_core_settings();
		
		// Set up provider settings (persistent data)
		self::setup_provider_settings();
		
		// Migrate from old options structure if needed
		self::migrate_legacy_options();
		
		// Clear any existing temporary data
		self::clear_temporary_data();

		// Flush rewrite rules for clean activation.
		flush_rewrite_rules();
	}

	/**
	 * Setup core plugin settings
	 *
	 * @return void
	 */
	private static function setup_core_settings(): void {
		if ( ! get_option( 'accessdefender_core_settings' ) ) {
			$core_settings = array(
				'enable_vpn_blocking' => '1',
				'warning_title'       => esc_html__( 'Access Denied', 'access-defender' ),
				'warning_message'     => wp_kses_post(
					esc_html__(
						'We\'ve detected that you\'re using a VPN or proxy. For security reasons, access to this website is not allowed through VPNs or proxies. Please disable your VPN or proxy and try again.',
						'access-defender'
					)
				),
				'version'             => ACCESS_DEFENDER_VERSION,
				'installed_date'      => current_time( 'mysql' ),
			);

			update_option( 'accessdefender_core_settings', $core_settings );
		}
	}

	/**
	 * Setup provider settings
	 *
	 * @return void
	 */
	private static function setup_provider_settings(): void {
		if ( ! get_option( 'accessdefender_provider_settings' ) ) {
			$provider_settings = array(
				'provider_mode'    => 'free',
				'free_providers'   => array( 'ip-api' ),
				'paid_provider'    => '',
				'primary_provider' => 'ip-api',
				'active_providers' => array( 'ip-api' ),
				'api_keys'         => array(),
			);

			update_option( 'accessdefender_provider_settings', $provider_settings );
		}
	}

	/**
	 * Migrate from legacy options structure
	 *
	 * @return void
	 */
	private static function migrate_legacy_options(): void {
		$legacy_options = get_option( 'accessdefender_options' );
		
		if ( $legacy_options && is_array( $legacy_options ) ) {
			// Migrate core settings
			$core_settings = get_option( 'accessdefender_core_settings', array() );
			if ( isset( $legacy_options['enable_vpn_blocking'] ) ) {
				$core_settings['enable_vpn_blocking'] = $legacy_options['enable_vpn_blocking'];
			}
			if ( isset( $legacy_options['warning_title'] ) ) {
				$core_settings['warning_title'] = $legacy_options['warning_title'];
			}
			if ( isset( $legacy_options['warning_message'] ) ) {
				$core_settings['warning_message'] = $legacy_options['warning_message'];
			}
			update_option( 'accessdefender_core_settings', $core_settings );

			// Migrate provider settings
			$provider_settings = get_option( 'accessdefender_provider_settings', array() );
			if ( isset( $legacy_options['provider_mode'] ) ) {
				$provider_settings['provider_mode'] = $legacy_options['provider_mode'];
			}
			if ( isset( $legacy_options['free_providers'] ) ) {
				$provider_settings['free_providers'] = $legacy_options['free_providers'];
			}
			if ( isset( $legacy_options['paid_provider'] ) ) {
				$provider_settings['paid_provider'] = $legacy_options['paid_provider'];
			}
			if ( isset( $legacy_options['primary_provider'] ) ) {
				$provider_settings['primary_provider'] = $legacy_options['primary_provider'];
			}
			if ( isset( $legacy_options['active_providers'] ) ) {
				$provider_settings['active_providers'] = $legacy_options['active_providers'];
			}
			if ( isset( $legacy_options['api_keys'] ) ) {
				$provider_settings['api_keys'] = $legacy_options['api_keys'];
			}
			update_option( 'accessdefender_provider_settings', $provider_settings );

			// Keep legacy option for backward compatibility, but mark as migrated
			$legacy_options['_migrated_to_v1_1'] = true;
			update_option( 'accessdefender_options', $legacy_options );
		}
	}

	/**
	 * Clear temporary data on activation
	 *
	 * @return void
	 */
	private static function clear_temporary_data(): void {
		// Clear all provider usage transients
		$providers = array( 'ip-api', 'ip-api-paid', 'proxycheck', 'ipgeolocation' );
		
		foreach ( $providers as $provider ) {
			// Clear monthly usage stats
			delete_option( "accessdefender_usage_{$provider}_" . date( 'Y-m' ) );
			delete_option( "accessdefender_stats_{$provider}" );
			
			// Clear per-minute rate limiting
			delete_option( "accessdefender_minute_{$provider}_" . date( 'Y-m-d-H-i' ) );
		}
		
		// Clear cached IP data
		delete_transient( 'accessdefender_ip_cache' );
		delete_transient( 'accessdefender_provider_status' );
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
		// Remove all persistent plugin options from database
		delete_option( 'accessdefender_core_settings' );
		delete_option( 'accessdefender_provider_settings' );
		delete_option( 'accessdefender_options' ); // Legacy option
		
		// Clear all temporary data and transients
		self::clear_all_temporary_data();
		
		// Clear any scheduled events
		wp_clear_scheduled_hook( 'accessdefender_cleanup_transients' );
	}

	/**
	 * Clear all temporary data and transients
	 *
	 * @return void
	 */
	private static function clear_all_temporary_data(): void {
		global $wpdb;
		
		// Clear all provider usage stats (monthly and per-minute)
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s",
				'accessdefender_usage_%',
				'accessdefender_stats_%',
				'accessdefender_minute_%'
			)
		);
		
		// Clear all plugin transients
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				'_transient_accessdefender_%',
				'_transient_timeout_accessdefender_%'
			)
		);
		
		// Clear specific transients
		$transients = array(
			'accessdefender_ip_cache',
			'accessdefender_provider_status',
			'accessdefender_api_validation',
		);
		
		foreach ( $transients as $transient ) {
			delete_transient( $transient );
		}
	}
}
