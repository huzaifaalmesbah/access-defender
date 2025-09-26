<?php
/**
 * IP-API.com Paid Provider Class
 *
 * Handles paid IP-API.com API integration with premium features.
 *
 * @package AccessDefender
 * @subpackage Services\ApiProviders
 */

namespace AccessDefender\Services\ApiProviders;

use AccessDefender\Interfaces\ApiProviderInterface;

/**
 * Class IpApiPaidProvider
 *
 * Paid version of IP-API.com with higher limits and premium features
 */
class IpApiPaidProvider extends BaseProvider implements ApiProviderInterface {

	/**
	 * Get provider slug
	 *
	 * @return string Provider slug
	 */
	public function get_slug(): string {
		return 'ip-api-paid';
	}

	/**
	 * Get provider name
	 *
	 * @return string Provider name
	 */
	public function get_name(): string {
		return 'IP-API.com (Pro)';
	}

	/**
	 * Check if provider is free
	 *
	 * @return bool False for paid provider
	 */
	public function is_free(): bool {
		return false;
	}

	/**
	 * Check if provider requires API key
	 *
	 * @return bool True for paid provider
	 */
	public function requires_api_key(): bool {
		return true;
	}

	/**
	 * Get rate limit (paid version has higher limits)
	 *
	 * @return int Rate limit per month (unlimited for paid)
	 */
	public function get_rate_limit(): int {
		return 0; // Unlimited for paid plans
	}

	/**
	 * Get per-minute rate limit
	 *
	 * @return int Per-minute rate limit (much higher for paid)
	 */
	public function get_minute_rate_limit(): int {
		return 1000; // 1000 requests per minute for paid
	}

	/**
	 * Get IP information from IP-API.com paid endpoint
	 *
	 * @param string $ip      IP address to check
	 * @param string $api_key API key for paid access
	 * @return array|false IP information or false on failure
	 */
	public function get_ip_info( string $ip, string $api_key = '' ) {
		if ( empty( $api_key ) ) {
			$this->log_usage( false );
			return false;
		}

		// Check cache first
		$cached = $this->get_cached_result( $ip );
		if ( $cached !== false ) {
			return $cached;
		}

		// Paid endpoint with API key
		$url = sprintf( 
			'https://pro.ip-api.com/json/%s?key=%s&fields=status,message,country,countryCode,region,regionName,city,lat,lon,timezone,isp,org,as,proxy,hosting', 
			$ip, 
			$api_key 
		);

		$response = $this->make_request( $url );

		if ( ! $response['success'] ) {
			$this->log_usage( false );
			return false;
		}

		$data = $response['data'];

		// Check if API returned success
		if ( isset( $data['status'] ) && $data['status'] !== 'success' ) {
			$this->log_usage( false );
			return false;
		}

		// Extract VPN/Proxy information (paid version has better detection)
		$result = array(
			'ip'          => $ip,
			'country'     => $data['country'] ?? '',
			'countryCode' => $data['countryCode'] ?? '',
			'region'      => $data['regionName'] ?? '',
			'city'        => $data['city'] ?? '',
			'isp'         => $data['isp'] ?? '',
			'org'         => $data['org'] ?? '',
			'as'          => $data['as'] ?? '',
			'proxy'       => isset( $data['proxy'] ) ? (bool) $data['proxy'] : false,
			'hosting'     => isset( $data['hosting'] ) ? (bool) $data['hosting'] : false,
			'vpn'         => isset( $data['proxy'] ) ? (bool) $data['proxy'] : false, // Proxy detection covers VPN
		);

		$this->cache_result( $ip, $result );
		$this->log_usage( true );

		return $result;
	}

	/**
	 * Check if IP is VPN or proxy
	 *
	 * @param array $ip_info IP information from get_ip_info
	 * @return bool True if VPN/proxy detected
	 */
	public function is_vpn_proxy( array $ip_info ): bool {
		// Paid version has more accurate detection
		return ! empty( $ip_info['proxy'] ) || ! empty( $ip_info['hosting'] ) || ! empty( $ip_info['vpn'] );
	}

	/**
	 * Validate API key
	 *
	 * @param string $api_key API key to validate
	 * @return bool True if valid
	 */
	public function validate_api_key( string $api_key ): bool {
		if ( empty( $api_key ) ) {
			return false;
		}

		// Test with a known IP
		$test_result = $this->get_ip_info( '8.8.8.8', $api_key );
		return $test_result !== false;
	}

	/**
	 * Get signup URL for API key
	 *
	 * @return string Signup URL
	 */
	public function get_signup_url(): string {
		return 'https://signup.ip-api.com/';
	}

	/**
	 * Get documentation URL
	 *
	 * @return string Documentation URL
	 */
	public function get_docs_url(): string {
		return 'https://ip-api.com/docs';
	}

	/**
	 * Get country information
	 *
	 * @param array $ip_info IP information from get_ip_info
	 * @return array Country info
	 */
	public function get_country_info( array $ip_info ): array {
		return array(
			'code' => $ip_info['countryCode'] ?? '',
			'name' => $ip_info['country'] ?? '',
		);
	}
}
