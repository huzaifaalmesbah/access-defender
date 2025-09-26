<?php
/**
 * IP-API.com Provider
 *
 * Free IP geolocation API provider
 *
 * @package AccessDefender
 * @subpackage Services\ApiProviders
 * @since 1.1.0
 */

namespace AccessDefender\Services\ApiProviders;

/**
 * IpApiProvider Class
 *
 * Handles IP detection using ip-api.com service
 */
class IpApiProvider extends BaseProvider {

	/**
	 * API endpoint URL
	 *
	 * @var string
	 */
	private $api_url = 'http://ip-api.com/json/';

	/**
	 * Get provider name
	 *
	 * @return string Provider name
	 */
	public function get_name(): string {
		return 'IP-API.com';
	}

	/**
	 * Get provider slug
	 *
	 * @return string Provider slug
	 */
	public function get_slug(): string {
		return 'ip-api';
	}

	/**
	 * Check if provider is free
	 *
	 * @return bool True if free
	 */
	public function is_free(): bool {
		return true;
	}

	/**
	 * Get rate limit per month
	 *
	 * @return int Rate limit
	 */
	public function get_rate_limit(): int {
		return 1000; // 1000 requests per month for free
	}

	/**
	 * Get per-minute rate limit
	 *
	 * @return int Per-minute rate limit
	 */
	public function get_minute_rate_limit(): int {
		return 45; // 45 requests per minute for free
	}

	/**
	 * Check if API key is required
	 *
	 * @return bool False - no API key required
	 */
	public function requires_api_key(): bool {
		return false;
	}

	/**
	 * Validate API key
	 *
	 * @param string $api_key API key to validate
	 * @return bool Always true - no API key required
	 */
	public function validate_api_key( string $api_key ): bool {
		return true; // No API key required
	}

	/**
	 * Get IP information
	 *
	 * @param string $ip IP address to check
	 * @param string $api_key Optional API key (not used)
	 * @return array|false IP information or false on failure
	 */
	public function get_ip_info( string $ip, string $api_key = '' ) {
		// Check cache first
		$cached = $this->get_cached_result( $ip );
		if ( $cached !== false ) {
			return $cached;
		}

		$url = $this->api_url . $ip . '?fields=status,country,countryCode,region,regionName,city,lat,lon,timezone,isp,org,as,proxy,hosting,query';

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

		$result = array(
			'ip'           => $data['query'] ?? $ip,
			'country'      => $data['country'] ?? '',
			'country_code' => $data['countryCode'] ?? '',
			'region'       => $data['regionName'] ?? '',
			'city'         => $data['city'] ?? '',
			'latitude'     => $data['lat'] ?? null,
			'longitude'    => $data['lon'] ?? null,
			'timezone'     => $data['timezone'] ?? '',
			'isp'          => $data['isp'] ?? '',
			'organization' => $data['org'] ?? '',
			'as_number'    => $data['as'] ?? '',
			'is_proxy'     => $data['proxy'] ?? false,
			'is_hosting'   => $data['hosting'] ?? false,
			'provider'     => $this->get_slug(),
		);

		$this->cache_result( $ip, $result );
		$this->log_usage( true );

		return $result;
	}

	/**
	 * Check if IP is VPN/Proxy
	 *
	 * @param array $ip_info IP information from get_ip_info
	 * @return bool True if VPN/Proxy detected
	 */
	public function is_vpn_proxy( array $ip_info ): bool {
		return ! empty( $ip_info['is_proxy'] ) || ! empty( $ip_info['is_hosting'] );
	}

	/**
	 * Get country information
	 *
	 * @param array $ip_info IP information from get_ip_info
	 * @return array Country info
	 */
	public function get_country_info( array $ip_info ): array {
		return array(
			'code' => $ip_info['country_code'] ?? '',
			'name' => $ip_info['country'] ?? '',
		);
	}
}
