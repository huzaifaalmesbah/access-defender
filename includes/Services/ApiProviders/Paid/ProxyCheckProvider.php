<?php
/**
 * proxycheck.io Provider
 *
 * Paid IP detection provider with VPN/Proxy detection
 *
 * @package AccessDefender
 * @subpackage Services\ApiProviders
 * @since 1.1.1
 */

namespace AccessDefender\Services\ApiProviders\Paid;

use AccessDefender\Interfaces\ApiProviderInterface;
use AccessDefender\Services\ApiProviders\BaseProvider;

/**
 * ProxyCheckProvider Class
 *
 * Handles IP detection using proxycheck.io service
 */
class ProxyCheckProvider extends BaseProvider {

	/**
	 * API endpoint base URL
	 *
	 * @var string
	 */
	private $api_url = 'https://proxycheck.io/v3/';

	/**
	 * Get provider name
	 *
	 * @return string Provider name
	 */
	public function get_name(): string {
		return 'ProxyCheck.io';
	}

	/**
	 * Get provider slug
	 *
	 * @return string Provider slug
	 */
	public function get_slug(): string {
		return 'proxycheck';
	}

	/**
	 * Check if provider is free
	 *
	 * @return bool False - primarily paid service (free tier available)
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
		// Free plan allows 1,000 daily, but treat as paid provider selection
		return 30000;
	}

	/**
	 * Check if API key is required
	 *
	 * @return bool True - API key recommended/required for production
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

		$test_response = $this->make_request( $this->api_url . '8.8.8.8?key=' . rawurlencode( $api_key ) . '&asn=1&risk=1&vpn=1' );
		if ( ! $test_response['success'] ) {
			return false;
		}
		$data = $test_response['data'];
		// proxycheck.io should return status ok for valid queries with a valid key
		if ( isset( $data['status'] ) && strtolower( (string) $data['status'] ) !== 'ok' ) {
			return false;
		}
		// Ensure we got an entry for the test IP and that it is an object/array
		return isset( $data['8.8.8.8'] ) && is_array( $data['8.8.8.8'] );
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

		$url = $this->api_url . rawurlencode( $ip ) . '?key=' . rawurlencode( $api_key ) . '&asn=1&risk=1&vpn=1&tag=AccessDefender';

		$response = $this->make_request( $url );

		if ( ! $response['success'] ) {
			$this->log_usage( false );
			return false;
		}

		$data = $response['data'];

		// The API returns keyed by IP
		$entry = $data[ $ip ] ?? null;
		if ( ! is_array( $entry ) ) {
			$this->log_usage( false );
			return false;
		}

		// New structured fields: network, location, detections
		$network    = isset( $entry['network'] ) && is_array( $entry['network'] ) ? $entry['network'] : array();
		$location   = isset( $entry['location'] ) && is_array( $entry['location'] ) ? $entry['location'] : array();
		$detections = isset( $entry['detections'] ) && is_array( $entry['detections'] ) ? $entry['detections'] : array();

		$country_code = $location['country_code'] ?? '';
		$country_name = $location['country_name'] ?? '';
		$region_name  = $location['region_name'] ?? '';
		$city_name    = $location['city_name'] ?? '';
		$latitude     = isset( $location['latitude'] ) ? (float) $location['latitude'] : null;
		$longitude    = isset( $location['longitude'] ) ? (float) $location['longitude'] : null;
		$timezone     = $location['timezone'] ?? '';

		$asn      = isset( $network['asn'] ) ? (string) $network['asn'] : '';
		$prov     = isset( $network['provider'] ) ? (string) $network['provider'] : '';
		$org      = isset( $network['organisation'] ) ? (string) $network['organisation'] : '';

		// Determine flags: block if any of these are true
		$flag_keys = array( 'proxy', 'vpn', 'compromised', 'scraper', 'tor', 'hosting', 'anonymous' );
		$is_any_true = false;
		foreach ( $flag_keys as $key ) {
			if ( isset( $detections[ $key ] ) && $detections[ $key ] === true ) {
				$is_any_true = true;
				break;
			}
		}
		$is_hosting = isset( $detections['hosting'] ) ? (bool) $detections['hosting'] : false;

		$result = array(
			'ip'           => $ip,
			'country'      => $country_name,
			'country_code' => $country_code,
			'region'       => $region_name,
			'city'         => $city_name,
			'latitude'     => $latitude,
			'longitude'    => $longitude,
			'timezone'     => $timezone,
			'isp'          => $org ?: $prov,
			'organization' => $org ?: $prov,
			'as_number'    => $asn,
			'is_proxy'     => $is_any_true,
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

	/**
	 * Get signup URL for API key
	 *
	 * @return string Signup URL
	 */
	public function get_signup_url(): string {
		return 'https://proxycheck.io/';
	}
}


