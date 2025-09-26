<?php
/**
 * IPInfo.io Provider
 *
 * Paid IP geolocation API provider with VPN/Proxy detection
 *
 * @package AccessDefender
 * @subpackage Services\ApiProviders
 * @since 1.1.0
 */

namespace AccessDefender\Services\ApiProviders;

/**
 * IpInfoProvider Class
 *
 * Handles IP detection using ipinfo.io service
 */
class IpInfoProvider extends BaseProvider {

	/**
	 * API endpoint URL
	 *
	 * @var string
	 */
	private $api_url = 'https://ipinfo.io/';

	/**
	 * Get provider name
	 *
	 * @return string Provider name
	 */
	public function get_name(): string {
		return 'IPInfo.io';
	}

	/**
	 * Get provider slug
	 *
	 * @return string Provider slug
	 */
	public function get_slug(): string {
		return 'ipinfo';
	}

	/**
	 * Check if provider is free
	 *
	 * @return bool False - paid service
	 */
	public function is_free(): bool {
		return false;
	}

	/**
	 * Get rate limit per month
	 *
	 * @return int Rate limit
	 */
	public function get_rate_limit(): int {
		return 50000; // 50k requests per month for basic paid plan
	}

	/**
	 * Check if API key is required
	 *
	 * @return bool True - API key required
	 */
	public function requires_api_key(): bool {
		return true;
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

		$test_response = $this->make_request( $this->api_url . '8.8.8.8/json?token=' . $api_key );
		return $test_response['success'] && isset( $test_response['data']['ip'] );
	}

	/**
	 * Get IP information
	 *
	 * @param string $ip IP address to check
	 * @param string $api_key API key
	 * @return array|false IP information or false on failure
	 */
	public function get_ip_info( string $ip, string $api_key = '' ) {
		if ( empty( $api_key ) ) {
			return false;
		}

		// Check cache first
		$cached = $this->get_cached_result( $ip );
		if ( $cached !== false ) {
			return $cached;
		}

		$url = $this->api_url . $ip . '/json?token=' . $api_key;

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

		// Parse location
		$location_parts = explode( ',', $data['loc'] ?? '' );
		$latitude       = isset( $location_parts[0] ) ? (float) trim( $location_parts[0] ) : null;
		$longitude      = isset( $location_parts[1] ) ? (float) trim( $location_parts[1] ) : null;

		// Check for VPN/Proxy indicators
		$is_proxy   = isset( $data['privacy']['proxy'] ) ? $data['privacy']['proxy'] : false;
		$is_hosting = isset( $data['privacy']['hosting'] ) ? $data['privacy']['hosting'] : false;
		$is_vpn     = isset( $data['privacy']['vpn'] ) ? $data['privacy']['vpn'] : false;

		$result = array(
			'ip'           => $data['ip'] ?? $ip,
			'country'      => $data['country_name'] ?? ( $data['country'] ?? '' ),
			'country_code' => $data['country'] ?? '',
			'region'       => $data['region'] ?? '',
			'city'         => $data['city'] ?? '',
			'latitude'     => $latitude,
			'longitude'    => $longitude,
			'timezone'     => $data['timezone'] ?? '',
			'isp'          => $data['org'] ?? '',
			'organization' => $data['org'] ?? '',
			'as_number'    => '',
			'is_proxy'     => $is_proxy || $is_vpn,
			'is_hosting'   => $is_hosting,
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
