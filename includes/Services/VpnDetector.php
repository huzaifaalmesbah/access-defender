<?php
/**
 * VpnDetector class
 *
 * This class is responsible for detecting if an IP address is using a VPN or proxy.
 * It now uses multiple API providers with fallback support for improved reliability.
 *
 * @package AccessDefender\Services
 * @since 1.0.0
 * @updated 1.1.0 - Added multiple API provider support
 */

namespace AccessDefender\Services;

/**
 * Class VpnDetector
 *
 * @package AccessDefender\Services
 */
class VpnDetector {

	/**
	 * IP detector service instance.
	 *
	 * @var IpDetector
	 */
	private IpDetector $ip_detector;

	/**
	 * API provider manager instance.
	 *
	 * @var ApiProviderManager
	 */
	private ApiProviderManager $api_manager;

	/**
	 * Constructor
	 *
	 * @param IpDetector $ip_detector IP detector service.
	 */
	public function __construct( IpDetector $ip_detector ) {
		$this->ip_detector = $ip_detector;
		$this->api_manager = new ApiProviderManager();
	}

	/**
	 * Check if the given IP address is a VPN or proxy
	 *
	 * @return bool True if the IP is a VPN or proxy, false otherwise
	 */
	public function is_vpn_or_proxy(): bool {
		$ip = $this->ip_detector->get_client_ip();

		if ( empty( $ip ) || $this->ip_detector->is_private_ip( $ip ) ) {
			return false;
		}

		$result = $this->api_manager->is_vpn_proxy( $ip );
		
		return $result;
	}

	/**
	 * Get country information for the current IP
	 *
	 * @return array Country information (code, name)
	 * @since 1.1.0
	 */
	public function get_country_info(): array {
		$ip = $this->ip_detector->get_client_ip();

		if ( empty( $ip ) || $this->ip_detector->is_private_ip( $ip ) ) {
			return array(
				'code' => '',
				'name' => '',
			);
		}

		return $this->api_manager->get_country_info( $ip );
	}

	/**
	 * Get full IP information for the current IP
	 *
	 * @return array|false Full IP information or false on failure
	 * @since 1.1.0
	 */
	public function get_ip_info() {
		$ip = $this->ip_detector->get_client_ip();

		if ( empty( $ip ) || $this->ip_detector->is_private_ip( $ip ) ) {
			return false;
		}

		return $this->api_manager->get_ip_info( $ip );
	}

	/**
	 * Get API provider manager instance
	 *
	 * @return ApiProviderManager API provider manager
	 * @since 1.1.0
	 */
	public function get_api_manager(): ApiProviderManager {
		return $this->api_manager;
	}
}
