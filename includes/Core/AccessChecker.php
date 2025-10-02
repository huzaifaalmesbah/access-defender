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
use AccessDefender\Core\OptionsManager;

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
		// Allow admin users and admin area to bypass the check.
		if ( is_admin() || current_user_can( 'manage_options' ) ) {
			return;
		}

		// Get options from OptionsManager (supports new structure with backward compatibility)
		$enable_vpn_blocking = OptionsManager::get_option( 'enable_vpn_blocking', '' );
		$vpn_blocking_mode = OptionsManager::get_option( 'vpn_blocking_mode', 'full_site' );
		$excluded_pages = OptionsManager::get_option( 'excluded_pages', array() );
		$excluded_posts = OptionsManager::get_option( 'excluded_posts', array() );
		$selected_pages = OptionsManager::get_option( 'selected_pages', array() );
		$selected_posts = OptionsManager::get_option( 'selected_posts', array() );
		$warning_title = OptionsManager::get_option( 'warning_title', '' );
		$warning_message = OptionsManager::get_option( 'warning_message', '' );

		// Check if VPN blocking is enabled and the request is from a VPN/proxy.
		$is_bot = $this->bot_detector->is_search_bot();
		$is_vpn = $this->vpn_detector->is_vpn_or_proxy();
		
		// Skip if VPN blocking is disabled, user is a bot, or not using VPN
		if ( empty( $enable_vpn_blocking ) || $is_bot || ! $is_vpn ) {
			return;
		}

		// Check if VPN blocking should be applied based on the mode and current page
		$should_block = $this->should_block_current_page( $vpn_blocking_mode, $excluded_pages, $excluded_posts, $selected_pages, $selected_posts );

		if ( $should_block ) {
			// Get title from options or use default.
			$title = ! empty( $warning_title )
				? $warning_title
				: esc_html__( 'Access Denied', 'access-defender' );

			// Get message from options or use default.
			$message = ! empty( $warning_message )
				? stripslashes( $warning_message )
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

	/**
	 * Determine if VPN blocking should be applied to the current page.
	 *
	 * @param string $mode The VPN blocking mode ('full_site' or 'selective').
	 * @param array  $excluded_pages Array of excluded page IDs for full site mode.
	 * @param array  $excluded_posts Array of excluded post IDs for full site mode.
	 * @param array  $selected_pages Array of selected page IDs for selective mode.
	 * @param array  $selected_posts Array of selected post IDs for selective mode.
	 * @return bool True if VPN should be blocked, false otherwise.
	 */
	private function should_block_current_page( $mode, $excluded_pages, $excluded_posts, $selected_pages, $selected_posts ): bool {
		$current_page_id = get_queried_object_id();
		$current_post_type = get_post_type( $current_page_id );

		// Handle different blocking modes
		switch ( $mode ) {
			case 'selective':
				// Selective mode: only block on selected pages/posts
				if ( is_page() && in_array( $current_page_id, $selected_pages, true ) ) {
					return true;
				}
				if ( is_single() && in_array( $current_page_id, $selected_posts, true ) ) {
					return true;
				}
				return false;

			case 'full_site':
			default:
				// Full site mode: block everywhere except excluded pages/posts
				if ( is_page() && in_array( $current_page_id, $excluded_pages, true ) ) {
					return false;
				}
				if ( is_single() && in_array( $current_page_id, $excluded_posts, true ) ) {
					return false;
				}
				// Block on home page, archives, and all other pages/posts not excluded
				return true;
		}
	}
}
