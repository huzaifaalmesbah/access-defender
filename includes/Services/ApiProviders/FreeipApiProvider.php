<?php
/**
 * freeipapi.com Provider
 *
 * Completely free IP geolocation API with no limits
 *
 * @package AccessDefender
 * @subpackage Services\ApiProviders
 * @since 1.1.0
 */

namespace AccessDefender\Services\ApiProviders;

/**
 * FreeipApiProvider Class
 *
 * Handles IP detection using freeipapi.com service
 * Free tier: 60 requests/minute (requires domain/IP whitelisting)
 */
class FreeipApiProvider extends BaseProvider {

	/**
	 * API endpoint URL
	 *
	 * @var string
	 */
	private $api_url = 'https://freeipapi.com/api/json/';

	/**
	 * Get provider name
	 *
	 * @return string Provider name
	 */
	public function get_name(): string {
		return 'FreeipAPI.com';
	}

	/**
	 * Get provider slug
	 *
	 * @return string Provider slug
	 */
	public function get_slug(): string {
		return 'freeipapi';
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
	 * @return int Rate limit (60 requests per minute = ~2.6M per month)
	 */
	public function get_rate_limit(): int {
		return 2592000; // 60 requests/minute * 60 minutes * 24 hours * 30 days = ~2.6M/month
	}

	/**
	 * Get per-minute rate limit
	 *
	 * @return int Per-minute rate limit
	 */
	public function get_minute_rate_limit(): int {
		return 60; // 60 requests per minute for free
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

		$url = $this->api_url . $ip;

		$response = $this->make_request( $url );

		if ( ! $response['success'] ) {
			$this->log_usage( false );
			return false;
		}

		$data = $response['data'];

		// Check for error in response
		if ( isset( $data['error'] ) ) {
			$this->log_usage( false );
			return false;
		}

		// Detect VPN/Proxy based on ISP and organization
		$is_proxy = $this->detect_vpn_proxy_from_data( $data );

		$result = array(
			'ip'           => $data['ipAddress'] ?? $ip,
			'country'      => $data['countryName'] ?? '',
			'country_code' => $data['countryCode'] ?? '',
			'region'       => $data['regionName'] ?? '',
			'city'         => $data['cityName'] ?? '',
			'latitude'     => isset( $data['latitude'] ) ? (float) $data['latitude'] : null,
			'longitude'    => isset( $data['longitude'] ) ? (float) $data['longitude'] : null,
			'timezone'     => $data['timeZone'] ?? '',
			'isp'          => $data['ispName'] ?? '',
			'organization' => $data['ispName'] ?? '',
			'as_number'    => '',
			'is_proxy'     => $is_proxy,
			'is_hosting'   => $this->is_hosting_provider( $data ),
			'provider'     => $this->get_slug(),
		);

		$this->cache_result( $ip, $result );
		$this->log_usage( true );

		return $result;
	}

	/**
	 * Detect VPN/Proxy from available data
	 *
	 * @param array $data API response data
	 * @return bool True if VPN/Proxy detected
	 */
	private function detect_vpn_proxy_from_data( array $data ): bool {
		$isp = strtolower( $data['ispName'] ?? '' );
		
		// Common VPN/Proxy keywords
		$vpn_keywords = array(
			'vpn', 'proxy', 'tor', 'hosting', 'server', 'datacenter',
			'cloud', 'digital ocean', 'amazon', 'google cloud',
			'microsoft azure', 'ovh', 'hetzner', 'linode',
			'expressvpn', 'nordvpn', 'surfshark', 'cyberghost',
		);

		foreach ( $vpn_keywords as $keyword ) {
			if ( strpos( $isp, $keyword ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if IP belongs to hosting provider
	 *
	 * @param array $data API response data
	 * @return bool True if hosting provider
	 */
	private function is_hosting_provider( array $data ): bool {
		$isp = strtolower( $data['ispName'] ?? '' );
		
		$hosting_keywords = array(
			'hosting', 'server', 'datacenter', 'cloud',
			'digital ocean', 'amazon', 'google cloud',
			'microsoft azure', 'ovh', 'hetzner', 'linode',
		);

		foreach ( $hosting_keywords as $keyword ) {
			if ( strpos( $isp, $keyword ) !== false ) {
				return true;
			}
		}

		return false;
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
