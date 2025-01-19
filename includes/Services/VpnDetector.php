<?php
/**
 * VpnDetector class
 *
 * This class is responsible for detecting if an IP address is using a VPN or proxy.
 * It uses the ip-api.com API to verify the IP address information and determine
 * if the IP belongs to a VPN or proxy service.
 *
 * @package AccessDefender\Services
 */

namespace AccessDefender\Services;

/**
 * Class VpnDetector
 *
 * @package AccessDefender\Services
 *
 * @property IpDetector $ip_detector IP detector service
 */
class VpnDetector {

	/**
	 * IP detector service instance.
	 *
	 * @var IpDetector
	 */
	private IpDetector $ip_detector;

	/**
	 * Constructor
	 *
	 * @param IpDetector $ip_detector IP detector service.
	 */
	public function __construct( IpDetector $ip_detector ) {
		$this->ip_detector = $ip_detector;
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

		$response = wp_remote_get(
			"http://ip-api.com/json/{$ip}?fields=proxy,hosting",
			array( 'timeout' => 5 )
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		return ( ! empty( $data['proxy'] ) || ! empty( $data['hosting'] ) );
	}
}
