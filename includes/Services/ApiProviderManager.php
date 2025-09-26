<?php
/**
 * API Provider Manager
 *
 * Manages multiple API providers with fallback and load balancing
 *
 * @package AccessDefender
 * @subpackage Services
 * @since 1.1.0
 */

namespace AccessDefender\Services;

use AccessDefender\Interfaces\ApiProviderInterface;
use AccessDefender\Services\ApiProviders\Free\IpApiProvider;
use AccessDefender\Services\ApiProviders\Paid\IpApiPaidProvider;
use AccessDefender\Services\ApiProviders\Paid\ProxyCheckProvider;
use AccessDefender\Services\ApiProviders\Paid\IpGeolocationProvider;

/**
 * ApiProviderManager Class
 *
 * Manages API providers and handles failover logic
 */
class ApiProviderManager {

	/**
	 * Available providers
	 *
	 * @var array
	 */
	private $providers = array();

	/**
	 * Plugin options
	 *
	 * @var array
	 */
	private $options = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		// Load new options structure with backward compatibility
		$core_settings     = get_option( 'accessdefender_core_settings', array() );
		$provider_settings = get_option( 'accessdefender_provider_settings', array() );
		$legacy_options    = get_option( 'accessdefender_options', array() );
		
		// Merge all options with priority: provider_settings > core_settings > legacy_options
		$this->options = array_merge( $legacy_options, $core_settings, $provider_settings );
		
		$this->init_providers();
	}

	/**
	 * Initialize available providers
	 *
	 * @return void
	 */
	private function init_providers(): void {
		$this->providers = array(
			// Free providers (no API key required) with VPN/Proxy detection
			'ip-api'         => new IpApiProvider(),        // Has proxy/hosting fields
			
			// Paid providers with advanced VPN/Proxy detection (ordered by preference)
			'proxycheck'     => new ProxyCheckProvider(),   // Proxy/VPN detection via proxycheck.io
			'ip-api-paid'    => new IpApiPaidProvider(),    // IP-API Pro - Premium with higher limits
			'ipgeolocation'  => new IpGeolocationProvider(), // Security data with VPN detection
		);
	}

	/**
	 * Get all available providers
	 *
	 * @return array Array of provider instances
	 */
	public function get_all_providers(): array {
		return $this->providers;
	}

	/**
	 * Get active providers based on settings with smart rotation
	 *
	 * @return array Array of active provider instances
	 */
	public function get_active_providers(): array {
		$provider_mode = $this->options['provider_mode'] ?? 'free';
		
		if ( $provider_mode === 'free' ) {
			return $this->get_available_free_providers();
		} else {
			return $this->get_paid_provider_array();
		}
	}

	/**
	 * Get available free providers with smart rotation logic
	 *
	 * @return array Array of available free provider instances
	 */
	private function get_available_free_providers(): array {
		$free_providers = $this->options['free_providers'] ?? array( 'ip-api' );
		$available_providers = array();

		// Check providers in order (sequential rotation, not random)
		foreach ( $free_providers as $slug ) {
			if ( isset( $this->providers[ $slug ] ) && $this->providers[ $slug ]->is_free() ) {
				$provider = $this->providers[ $slug ];
				
				// Skip if provider requires API key but none provided
				if ( $provider->requires_api_key() ) {
					$api_keys = $this->options['api_keys'] ?? array();
					if ( empty( $api_keys[ $slug ] ) ) {
						continue;
					}
				}
				
				// Check per-minute and monthly limits
				if ( ! $provider->is_minute_rate_limited() && ! $this->is_provider_rate_limited( $slug ) ) {
					$available_providers[ $slug ] = $provider;
				}
			}
		}

		// Return providers in original order (sequential rotation)
		// First available provider will be used until it hits limit, then next one
		if ( ! empty( $available_providers ) ) {
			return $available_providers;
		}

		// Fallback: if all providers are rate limited, try to find one with least usage
		$fallback_providers = $this->get_fallback_providers( $free_providers );
		if ( ! empty( $fallback_providers ) ) {
			return $fallback_providers;
		}

		// Ultimate fallback to ip-api
		return array( 'ip-api' => $this->providers['ip-api'] );
	}

	/**
	 * Get fallback providers when all are rate limited
	 *
	 * @param array $provider_slugs Provider slugs to check
	 * @return array Fallback providers
	 */
	private function get_fallback_providers( array $provider_slugs ): array {
		$fallback_providers = array();

		// Sort by usage and try to find providers that are not severely rate limited
		$sorted_providers = $this->sort_providers_by_usage( $provider_slugs );

		foreach ( $sorted_providers as $slug ) {
			if ( isset( $this->providers[ $slug ] ) ) {
				$provider = $this->providers[ $slug ];
				
				// Skip API key check for fallback (emergency use)
				// Only check if not severely over monthly limit (allow some buffer)
				if ( ! $this->is_provider_severely_limited( $slug ) ) {
					$fallback_providers[ $slug ] = $provider;
					break; // Take the first available as fallback
				}
			}
		}

		return $fallback_providers;
	}

	/**
	 * Check if provider is severely over its limits
	 *
	 * @param string $provider_slug Provider slug
	 * @return bool True if severely limited
	 */
	private function is_provider_severely_limited( string $provider_slug ): bool {
		if ( ! isset( $this->providers[ $provider_slug ] ) ) {
			return true;
		}

		$provider = $this->providers[ $provider_slug ];
		$rate_limit = $provider->get_rate_limit();

		if ( $rate_limit === 0 ) {
			return false; // Unlimited
		}

		$usage_key = 'accessdefender_usage_' . $provider_slug . '_' . date( 'Y-m' );
		$current_usage = get_option( $usage_key, 0 );

		// Consider severely limited if over 150% of rate limit
		return $current_usage >= ( $rate_limit * 1.5 );
	}

	/**
	 * Get paid provider as array
	 *
	 * @return array Array with single paid provider
	 */
	private function get_paid_provider_array(): array {
		$paid_provider = $this->options['paid_provider'] ?? '';
		
		if ( ! empty( $paid_provider ) && isset( $this->providers[ $paid_provider ] ) ) {
			return array( $paid_provider => $this->providers[ $paid_provider ] );
		}

		// Fallback to first paid provider if available
		foreach ( $this->providers as $slug => $provider ) {
			if ( ! $provider->is_free() ) {
				return array( $slug => $provider );
			}
		}

		// Ultimate fallback to free provider
		return array( 'ip-api' => $this->providers['ip-api'] );
	}

	/**
	 * Sort providers by usage (least used first for rotation)
	 *
	 * @param array $provider_slugs Provider slugs to sort
	 * @return array Sorted provider slugs
	 */
	private function sort_providers_by_usage( array $provider_slugs ): array {
		$usage_data = array();

		foreach ( $provider_slugs as $slug ) {
			$usage_key = 'accessdefender_usage_' . $slug . '_' . date( 'Y-m' );
			$usage_data[ $slug ] = get_option( $usage_key, 0 );
		}

		// Sort by usage (ascending - least used first)
		asort( $usage_data );

		return array_keys( $usage_data );
	}

	/**
	 * Check if provider has reached its rate limit
	 *
	 * @param string $provider_slug Provider slug
	 * @return bool True if rate limited
	 */
	private function is_provider_rate_limited( string $provider_slug ): bool {
		if ( ! isset( $this->providers[ $provider_slug ] ) ) {
			return true;
		}

		$provider = $this->providers[ $provider_slug ];
		$rate_limit = $provider->get_rate_limit();

		// If rate limit is 0, it means unlimited
		if ( $rate_limit === 0 ) {
			return false;
		}

		$usage_key = 'accessdefender_usage_' . $provider_slug . '_' . date( 'Y-m' );
		$current_usage = get_option( $usage_key, 0 );

		// Allow 10% buffer before hard limit
		$soft_limit = $rate_limit * 0.9;

		return $current_usage >= $soft_limit;
	}

	/**
	 * Get primary provider
	 *
	 * @return ApiProviderInterface Primary provider instance
	 */
	public function get_primary_provider(): ApiProviderInterface {
		$primary_slug = $this->options['primary_provider'] ?? 'ip-api';
		
		if ( isset( $this->providers[ $primary_slug ] ) ) {
			return $this->providers[ $primary_slug ];
		}

		return $this->providers['ip-api']; // Fallback
	}

	/**
	 * Get IP information with smart fallback and rotation
	 *
	 * @param string $ip IP address to check
	 * @return array|false IP information or false on failure
	 */
	public function get_ip_info( string $ip ) {
		$active_providers = $this->get_active_providers();
		$api_keys         = $this->options['api_keys'] ?? array();

		// Try each provider in sequential order (first available until limit hit, then next)
		foreach ( $active_providers as $slug => $provider ) {
			$api_key = $api_keys[ $slug ] ?? '';

			// Skip if provider requires API key but none provided
			if ( $provider->requires_api_key() && empty( $api_key ) ) {
				continue;
			}

			// Skip if provider is currently rate limited per minute
			if ( $provider->is_minute_rate_limited() ) {
				continue;
			}

			$result = $provider->get_ip_info( $ip, $api_key );

			if ( $result !== false ) {
				// Add debug info about which provider was used
				$result['_provider_used'] = $slug;
				$result['_provider_name'] = $provider->get_name();
				return $result;
			}
		}

		return false;
	}

	/**
	 * Check if IP is VPN/Proxy using primary provider
	 *
	 * @param string $ip IP address to check
	 * @return bool True if VPN/Proxy detected
	 */
	public function is_vpn_proxy( string $ip ): bool {
		$ip_info = $this->get_ip_info( $ip );

		if ( $ip_info === false ) {
			return false;
		}

		$provider_slug = $ip_info['provider'] ?? 'ip-api';
		$provider      = $this->providers[ $provider_slug ] ?? $this->providers['ip-api'];

		return $provider->is_vpn_proxy( $ip_info );
	}

	/**
	 * Get country information
	 *
	 * @param string $ip IP address to check
	 * @return array Country information
	 */
	public function get_country_info( string $ip ): array {
		$ip_info = $this->get_ip_info( $ip );

		if ( $ip_info === false ) {
			return array(
				'code' => '',
				'name' => '',
			);
		}

		$provider_slug = $ip_info['provider'] ?? 'ip-api';
		$provider      = $this->providers[ $provider_slug ] ?? $this->providers['ip-api'];

		return $provider->get_country_info( $ip_info );
	}

	/**
	 * Get provider by slug
	 *
	 * @param string $slug Provider slug
	 * @return ApiProviderInterface|null Provider instance or null
	 */
	public function get_provider( string $slug ): ?ApiProviderInterface {
		return $this->providers[ $slug ] ?? null;
	}

	/**
	 * Validate API key for provider
	 *
	 * @param string $provider_slug Provider slug
	 * @param string $api_key API key to validate
	 * @return bool True if valid
	 */
	public function validate_api_key( string $provider_slug, string $api_key ): bool {
		$provider = $this->get_provider( $provider_slug );

		if ( ! $provider ) {
			return false;
		}

		return $provider->validate_api_key( $api_key );
	}

	/**
	 * Get status of all providers
	 *
	 * @return array Provider status information
	 */
	public function get_providers_status(): array {
		$status = array();

		foreach ( $this->providers as $slug => $provider ) {
			$status[ $slug ] = $provider->get_status();
		}

		return $status;
	}

	/**
	 * Test connection to all active providers
	 *
	 * @return array Test results
	 */
	public function test_providers(): array {
		$results  = array();
		$test_ip  = '8.8.8.8'; // Google DNS for testing
		$api_keys = $this->options['api_keys'] ?? array();

		foreach ( $this->get_active_providers() as $slug => $provider ) {
			$start_time = microtime( true );
			$api_key    = $api_keys[ $slug ] ?? '';

			try {
				$result = $provider->get_ip_info( $test_ip, $api_key );
				$response_time = round( ( microtime( true ) - $start_time ) * 1000, 2 );

				$results[ $slug ] = array(
					'success'       => $result !== false,
					'response_time' => $response_time,
					'error'         => $result === false ? 'Failed to get IP info' : null,
				);
			} catch ( Exception $e ) {
				$results[ $slug ] = array(
					'success'       => false,
					'response_time' => 0,
					'error'         => $e->getMessage(),
				);
			}
		}

		return $results;
	}

	/**
	 * Get usage statistics for all providers
	 *
	 * @return array Usage statistics
	 */
	public function get_usage_statistics(): array {
		$statistics = array();

		foreach ( $this->providers as $slug => $provider ) {
			$statistics[ $slug ] = $provider->get_usage_stats();
		}

		return $statistics;
	}
}
