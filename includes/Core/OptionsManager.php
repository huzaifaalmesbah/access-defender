<?php
/**
 * Options Manager Class
 *
 * Centralizes option management for better data structure and backward compatibility.
 *
 * @package AccessDefender
 * @subpackage Core
 * @since 1.1.0
 */

namespace AccessDefender\Core;

/**
 * Class OptionsManager
 *
 * Manages plugin options with proper separation between core settings,
 * provider settings, and temporary data using transients.
 */
class OptionsManager {

	/**
	 * Core settings option name
	 */
	const CORE_SETTINGS_OPTION = 'accessdefender_core_settings';

	/**
	 * Provider settings option name
	 */
	const PROVIDER_SETTINGS_OPTION = 'accessdefender_provider_settings';

	/**
	 * Legacy options name (for backward compatibility)
	 */
	const LEGACY_OPTIONS = 'accessdefender_options';

	/**
	 * Get all options merged together
	 *
	 * @return array Merged options array
	 */
	public static function get_all_options(): array {
		$core_settings     = get_option( self::CORE_SETTINGS_OPTION, array() );
		$provider_settings = get_option( self::PROVIDER_SETTINGS_OPTION, array() );
		$legacy_options    = get_option( self::LEGACY_OPTIONS, array() );

		// Merge with priority: provider_settings > core_settings > legacy_options
		return array_merge( $legacy_options, $core_settings, $provider_settings );
	}

	/**
	 * Get core settings
	 *
	 * @return array Core settings
	 */
	public static function get_core_settings(): array {
		return get_option( self::CORE_SETTINGS_OPTION, array() );
	}

	/**
	 * Get provider settings
	 *
	 * @return array Provider settings
	 */
	public static function get_provider_settings(): array {
		return get_option( self::PROVIDER_SETTINGS_OPTION, array() );
	}

	/**
	 * Update core settings
	 *
	 * @param array $settings Core settings to update
	 * @return bool True on success, false on failure
	 */
	public static function update_core_settings( array $settings ): bool {
		return update_option( self::CORE_SETTINGS_OPTION, $settings );
	}

	/**
	 * Update provider settings
	 *
	 * @param array $settings Provider settings to update
	 * @return bool True on success, false on failure
	 */
	public static function update_provider_settings( array $settings ): bool {
		return update_option( self::PROVIDER_SETTINGS_OPTION, $settings );
	}

	/**
	 * Get specific option with fallback to legacy
	 *
	 * @param string $key     Option key
	 * @param mixed  $default Default value if option doesn't exist
	 * @return mixed Option value
	 */
	public static function get_option( string $key, $default = null ) {
		$all_options = self::get_all_options();
		return $all_options[ $key ] ?? $default;
	}

	/**
	 * Set temporary data using transients
	 *
	 * @param string $key        Transient key (will be prefixed with 'accessdefender_')
	 * @param mixed  $value      Data to store
	 * @param int    $expiration Expiration time in seconds (default: 1 hour)
	 * @return bool True on success, false on failure
	 */
	public static function set_temporary_data( string $key, $value, int $expiration = HOUR_IN_SECONDS ): bool {
		return set_transient( 'accessdefender_' . $key, $value, $expiration );
	}

	/**
	 * Get temporary data from transients
	 *
	 * @param string $key     Transient key (will be prefixed with 'accessdefender_')
	 * @param mixed  $default Default value if transient doesn't exist
	 * @return mixed Transient value
	 */
	public static function get_temporary_data( string $key, $default = false ) {
		return get_transient( 'accessdefender_' . $key ) ?: $default;
	}

	/**
	 * Delete temporary data
	 *
	 * @param string $key Transient key (will be prefixed with 'accessdefender_')
	 * @return bool True on success, false on failure
	 */
	public static function delete_temporary_data( string $key ): bool {
		return delete_transient( 'accessdefender_' . $key );
	}

	/**
	 * Check if plugin is migrated to new options structure
	 *
	 * @return bool True if migrated, false otherwise
	 */
	public static function is_migrated(): bool {
		$legacy_options = get_option( self::LEGACY_OPTIONS, array() );
		return isset( $legacy_options['_migrated_to_v1_1'] ) && $legacy_options['_migrated_to_v1_1'];
	}

	/**
	 * Clean up all plugin data (for uninstall)
	 *
	 * @return void
	 */
	public static function cleanup_all_data(): void {
		// Delete options
		delete_option( self::CORE_SETTINGS_OPTION );
		delete_option( self::PROVIDER_SETTINGS_OPTION );
		delete_option( self::LEGACY_OPTIONS );

		// Clear temporary data
		global $wpdb;

		// Clear all provider usage stats
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
	}
}
