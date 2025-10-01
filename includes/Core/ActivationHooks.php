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
					__( 'We\'ve detected that you\'re using a VPN or proxy. For security reasons, access to this website is not allowed through VPNs or proxies. Please disable your VPN or proxy and try again.', 'access-defender' )
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
	 * Legacy version only had 3 core settings:
	 * - enable_vpn_blocking
	 * - warning_title  
	 * - warning_message
	 *
	 * All provider settings are new in v1.1.0, so we only migrate these 3 fields.
	 *
	 * @return void
	 */
	private static function migrate_legacy_options(): void {
		$legacy_options = get_option( 'accessdefender_options' );
		
		// Only migrate if legacy options exist and contain the expected core fields
		if ( $legacy_options && is_array( $legacy_options ) ) {
			$core_settings = get_option( 'accessdefender_core_settings', array() );
			
			// Migrate only the 3 core fields that existed in legacy version
			if ( isset( $legacy_options['enable_vpn_blocking'] ) ) {
				$core_settings['enable_vpn_blocking'] = $legacy_options['enable_vpn_blocking'];
			}
			if ( isset( $legacy_options['warning_title'] ) ) {
				$core_settings['warning_title'] = wp_kses_post( $legacy_options['warning_title'] );
			}
			if ( isset( $legacy_options['warning_message'] ) ) {
				// Handle HTML entities from legacy data
				$message = wp_kses_post( html_entity_decode( $legacy_options['warning_message'] ) );
				$core_settings['warning_message'] = $message;
			}
			
			// Update core settings with migrated data
			update_option( 'accessdefender_core_settings', $core_settings );

			// Migration complete - remove legacy option
			delete_option( 'accessdefender_options' );
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
			delete_option( "accessdefender_usage_{$provider}_" . gmdate( 'Y-m' ) );
			delete_option( "accessdefender_stats_{$provider}" );
			
			// Clear per-minute rate limiting
			delete_option( "accessdefender_minute_{$provider}_" . gmdate( 'Y-m-d-H-i' ) );
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
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s",
				'accessdefender_usage_%',
				'accessdefender_stats_%',
				'accessdefender_minute_%'
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		
		// Clear all plugin transients
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				'_transient_accessdefender_%',
				'_transient_timeout_accessdefender_%'
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		
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
