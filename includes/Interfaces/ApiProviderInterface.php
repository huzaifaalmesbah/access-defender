<?php
/**
 * API Provider Interface
 *
 * Defines the contract that all API providers must implement
 *
 * @package AccessDefender
 * @subpackage Interfaces
 * @since 1.1.0
 */

namespace AccessDefender\Interfaces;

/**
 * Interface ApiProviderInterface
 *
 * Contract for all IP detection API providers
 */
interface ApiProviderInterface {

	/**
	 * Get provider name
	 *
	 * @return string Provider name
	 */
	public function get_name(): string;

	/**
	 * Get provider slug
	 *
	 * @return string Provider slug
	 */
	public function get_slug(): string;

	/**
	 * Check if provider is free
	 *
	 * @return bool True if free, false if paid
	 */
	public function is_free(): bool;

	/**
	 * Get rate limit per month
	 *
	 * @return int Rate limit (0 for unlimited)
	 */
	public function get_rate_limit(): int;

	/**
	 * Check if API key is required
	 *
	 * @return bool True if API key required
	 */
	public function requires_api_key(): bool;

	/**
	 * Validate API key
	 *
	 * @param string $api_key API key to validate
	 * @return bool True if valid
	 */
	public function validate_api_key( string $api_key ): bool;

	/**
	 * Get IP information
	 *
	 * @param string $ip IP address to check
	 * @param string $api_key Optional API key
	 * @return array|false IP information or false on failure
	 */
	public function get_ip_info( string $ip, string $api_key = '' );

	/**
	 * Check if IP is VPN/Proxy
	 *
	 * @param array $ip_info IP information from get_ip_info
	 * @return bool True if VPN/Proxy detected
	 */
	public function is_vpn_proxy( array $ip_info ): bool;

	/**
	 * Get country information
	 *
	 * @param array $ip_info IP information from get_ip_info
	 * @return array Country info (code, name)
	 */
	public function get_country_info( array $ip_info ): array;

	/**
	 * Get provider status
	 *
	 * @return array Status information
	 */
	public function get_status(): array;
}
