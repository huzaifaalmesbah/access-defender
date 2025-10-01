<?php
/**
 * Bot Detection Service
 *
 * This file contains the BotDetector class which handles detection and verification
 * of search engine bots and crawlers.
 *
 * @package AccessDefender
 * @subpackage Services
 */

namespace AccessDefender\Services;

/**
 * BotDetector Class
 *
 * Handles the detection and verification of search engine bots and crawlers
 * by analyzing user agents and IP addresses.
 */
class BotDetector {

	/**
	 * IP Detector instance
	 *
	 * @var IpDetector
	 */
	private $ip_detector;

	/**
	 * List of allowed bot user agents
	 *
	 * @var array
	 */
	private $allowed_bots = array(
		'googlebot',
		'adsbot-google',
		'mediapartners-google',
		'google-read-aloud',
		'chrome-lighthouse',
		'google favicon',
		'google web preview',
		'google-inspectiontool',
		'bingbot',
		'bingpreview',
		'yandexbot',
		'baiduspider',
		'duckduckbot',
		'yahoo',
		'slurp',
		'facebookexternalhit',
		'twitterbot',
		'linkedinbot',
		'ahrefsbot',
		'semrushbot',
		'mj12bot',
	);

	/**
	 * List of Google IP ranges
	 *
	 * @var array
	 */
	private $google_ip_ranges = array(
		'66.249.',
		'64.233.',
		'72.14.',
		'74.125.',
		'216.239.',
		'209.85.',
		'35.',
		'34.',
	);

	/**
	 * Constructor
	 *
	 * @param IpDetector $ip_detector IP detector service instance.
	 */
	public function __construct( IpDetector $ip_detector ) {
		$this->ip_detector = $ip_detector;
	}

	/**
	 * Determines if the current request is from a search bot
	 *
	 * @return bool True if the request is from a verified search bot, false otherwise
	 */
	public function is_search_bot(): bool {
		if ( ! isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
			return false;
		}

		$user_agent = strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) );
		$ip         = $this->ip_detector->get_client_ip();

		if ( $this->is_google_ip( $ip ) && $this->verify_google_bot( $ip ) ) {
			return true;
		}

		foreach ( $this->allowed_bots as $bot ) {
			if ( strpos( $user_agent, $bot ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks if an IP address belongs to Google's IP ranges
	 *
	 * @param string $ip IP address to check.
	 * @return bool True if IP is in Google's range, false otherwise
	 */
	private function is_google_ip( string $ip ): bool {
		foreach ( $this->google_ip_ranges as $range ) {
			if ( strpos( $ip, $range ) === 0 ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Verifies if an IP address belongs to a genuine Google bot
	 *
	 * @param string $ip IP address to verify.
	 * @return bool True if verified as Google bot, false otherwise
	 */
	private function verify_google_bot( string $ip ): bool {
		$hostname = gethostbyaddr( $ip );
		if ( $hostname && (
			strpos( $hostname, '.googlebot.com' ) !== false ||
			strpos( $hostname, '.google.com' ) !== false
		) ) {
			return gethostbyname( $hostname ) === $ip;
		}
		return false;
	}
}
