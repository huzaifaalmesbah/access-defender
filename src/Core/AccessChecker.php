<?php
/**
 * Access Checker Class File
 *
 * This file contains the AccessChecker class which handles VPN and proxy access
 * control functionality for the Access Defender plugin.
 *
 * @package AccessDefender
 * @subpackage Core
 */

namespace AccessDefender\Core;

use AccessDefender\Services\BotDetector;
use AccessDefender\Services\VpnDetector;

/**
 * Class AccessChecker
 *
 * Handles VPN and proxy access control functionality including blocking and warning messages.
 */
class AccessChecker {

	/**
	 * Bot detector service instance.
	 *
	 * @var BotDetector
	 */
	private $bot_detector;

	/**
	 * VPN detector service instance.
	 *
	 * @var VpnDetector
	 */
	private $vpn_detector;

	/**
	 * Constructor
	 *
	 * @param BotDetector $bot_detector Bot detection service instance.
	 * @param VpnDetector $vpn_detector VPN detection service instance.
	 */
	public function __construct( BotDetector $bot_detector, VpnDetector $vpn_detector ) {
		$this->bot_detector = $bot_detector;
		$this->vpn_detector = $vpn_detector;
	}

	/**
	 * Check access and handle VPN/proxy blocking.
	 *
	 * @return void
	 */
	public function check_access(): void {
		// Allow admin users to bypass the check.
		if ( ! is_admin() && current_user_can( 'manage_options' ) ) {
			return;
		}

		// Get options from database.
		$options = get_option( 'accessdefender_options', array() );

		// Check if VPN blocking is enabled and the request is from a VPN/proxy.
		if ( ! empty( $options['enable_vpn_blocking'] ) &&
			! $this->bot_detector->is_search_bot() &&
			$this->vpn_detector->is_vpn_or_proxy()
		) {
			// Get title from options or use default.
			$title = ! empty( $options['warning_title'] )
				? $options['warning_title']
				: esc_html__( 'Access Denied', 'access-defender' );

			// Get message from options or use default.
			$message = ! empty( $options['warning_message'] )
				? $options['warning_message']
				: esc_html__( 'We\'ve detected that you\'re using a VPN or proxy. For security reasons, access to this website is not allowed through VPNs or proxies. Please disable your VPN or proxy and try again.', 'access-defender' );

			// Format the complete message with HTML structure.
			$formatted_message = sprintf(
				'<h1>%s</h1><p>%s</p>',
				esc_html( $title ),
				wp_kses_post( $message )
			);

			wp_die(
				wp_kses_post( $formatted_message ),
				esc_html( $title ),
				array( 'response' => 403 )
			);
		}
	}
}
