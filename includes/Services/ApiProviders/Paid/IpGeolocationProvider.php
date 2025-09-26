<?php
/**
 * IPGeolocation.io Provider
 *
 * Paid IP geolocation API provider with security features
 *
 * @package AccessDefender
 * @subpackage Services\ApiProviders
 * @since 1.1.0
 */

namespace AccessDefender\Services\ApiProviders\Paid;

use AccessDefender\Interfaces\ApiProviderInterface;
use AccessDefender\Services\ApiProviders\BaseProvider;

/**
 * IpGeolocationProvider Class
 *
 * Handles IP detection using ipgeolocation.io service
 */
class IpGeolocationProvider extends BaseProvider {

	/**
	 * API endpoint URL
	 *
	 * @var string
	 */
	private $api_url = 'https://api.ipgeolocation.io/ipgeo';

	/**
	 * Get provider name
	 *
	 * @return string Provider name
	 */
	public function get_name(): string {
		return 'IPGeolocation.io';
	}

	/**
	 * Get provider slug
	 *
	 * @return string Provider slug
	 */
	public function get_slug(): string {
		return 'ipgeolocation';
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
		return 30000; // 30k requests per month for basic paid plan
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

		$test_response = $this->make_request( $this->api_url . '?apiKey=' . $api_key . '&ip=8.8.8.8' );
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

		$url = $this->api_url . '?apiKey=' . $api_key . '&ip=' . $ip . '&fields=geo,security';

		$response = $this->make_request( $url );

		if ( ! $response['success'] ) {
			$this->log_usage( false );
			return false;
		}

		$data = $response['data'];

		// Check for error in response
		if ( isset( $data['message'] ) && strpos( $data['message'], 'Invalid' ) !== false ) {
			$this->log_usage( false );
			return false;
		}

		// Parse security information
		$is_proxy = false;
		$is_vpn   = false;
		if ( isset( $data['security'] ) ) {
			$is_proxy = ! empty( $data['security']['is_proxy'] );
			$is_vpn   = ! empty( $data['security']['is_vpn'] );
		}

		$result = array(
			'ip'           => $data['ip'] ?? $ip,
			'country'      => $data['country_name'] ?? '',
			'country_code' => $data['country_code2'] ?? '',
			'region'       => $data['state_prov'] ?? '',
			'city'         => $data['city'] ?? '',
			'latitude'     => isset( $data['latitude'] ) ? (float) $data['latitude'] : null,
			'longitude'    => isset( $data['longitude'] ) ? (float) $data['longitude'] : null,
			'timezone'     => $data['time_zone']['name'] ?? '',
			'isp'          => $data['isp'] ?? '',
			'organization' => $data['organization'] ?? '',
			'as_number'    => '',
			'is_proxy'     => $is_proxy || $is_vpn,
			'is_hosting'   => false, // Not provided by this API
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
		return ! empty( $ip_info['is_proxy'] );
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

	/**
	 * Get signup URL for API key
	 *
	 * @return string Signup URL
	 */
	public function get_signup_url(): string {
		return 'https://ipgeolocation.io/signup.html';
	}

}
