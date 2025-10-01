<?php
/**
 * IP Detector Service File
 *
 * This file contains the IpDetector class which provides functionality
 * for detecting and validating IP addresses from various HTTP headers.
 *
 * @package AccessDefender
 * @subpackage Services
 */

namespace AccessDefender\Services;

/**
 * IP Detector Class
 *
 * Handles detection and validation of client IP addresses by checking
 * various HTTP headers and providing methods to validate IP types.
 */
class IpDetector {

	/**
	 * List of HTTP headers that might contain client IP addresses.
	 *
	 * Headers are checked in order of precedence, from most to least reliable.
	 *
	 * @var array
	 */
	private array $headers = array(
		'HTTP_CLIENT_IP',
		'HTTP_X_FORWARDED_FOR',
		'HTTP_X_FORWARDED',
		'HTTP_X_CLUSTER_CLIENT_IP',
		'HTTP_FORWARDED_FOR',
		'HTTP_FORWARDED',
		'REMOTE_ADDR',
	);

	/**
	 * Get the client's IP address
	 *
	 * Attempts to get the client's real IP address by checking various HTTP headers.
	 * Returns the first valid IP address found or an empty string if none is found.
	 *
	 * @return string The client's IP address or empty string if not found
	 */
	public function get_client_ip(): string {
		foreach ( $this->headers as $header ) {
			if ( ! isset( $_SERVER[ $header ] ) ) {
				continue;
			}

			$ip = trim( sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) ) );
			if ( empty( $ip ) ) {
				continue;
			}

			// If contains multiple IPs, get the first one.
			$ips = explode( ',', $ip );
			$ip  = trim( $ips[0] );

			if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
				return $ip;
			}
		}

		return '';
	}

	/**
	 * Check if an IP address is private
	 *
	 * Determines whether the given IP address is in a private range
	 * (including RFC1918 private ranges and reserved ranges).
	 *
	 * @param string $ip The IP address to check.
	 * @return bool True if the IP is private, false otherwise
	 */
	public function is_private_ip( string $ip ): bool {
		return ! filter_var(
			$ip,
			FILTER_VALIDATE_IP,
			FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
		);
	}
}
