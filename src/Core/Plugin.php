<?php
/**
 * Plugin Core Class File
 *
 * This file contains the main Plugin class that handles initialization and core functionality
 * of the Access Defender plugin.
 *
 * @package AccessDefender
 * @subpackage Core
 */

namespace AccessDefender\Core;

use AccessDefender\Admin\AdminPage;
use AccessDefender\Services\BotDetector;
use AccessDefender\Services\IpDetector;
use AccessDefender\Services\VpnDetector;

/**
 * Plugin Class
 *
 * Handles the initialization and core functionality of the Access Defender plugin.
 * Implements the PluginInterface for standardized plugin structure.
 */
class Plugin implements PluginInterface {

	/**
	 * Bot detector service instance
	 *
	 * @var BotDetector
	 */
	private BotDetector $bot_detector;

	/**
	 * IP detector service instance
	 *
	 * @var IpDetector
	 */
	private IpDetector $ip_detector;

	/**
	 * VPN detector service instance
	 *
	 * @var VpnDetector
	 */
	private VpnDetector $vpn_detector;

	/**
	 * Admin page instance
	 *
	 * @var AdminPage
	 */
	private AdminPage $admin_page;

	/**
	 * Access checker instance
	 *
	 * @var AccessChecker
	 */
	private AccessChecker $access_checker;

	/**
	 * Constructor
	 *
	 * Initializes the plugin by setting up service instances and dependencies.
	 */
	public function __construct() {
		$this->ip_detector    = new IpDetector();
		$this->bot_detector   = new BotDetector( $this->ip_detector );
		$this->vpn_detector   = new VpnDetector( $this->ip_detector );
		$this->admin_page     = new AdminPage();
		$this->access_checker = new AccessChecker( $this->bot_detector, $this->vpn_detector );
	}

	/**
	 * Initialize the plugin
	 *
	 * Sets up WordPress hooks and initializes the admin page.
	 */
	public function init(): void {
		$this->init_hooks();
		$this->admin_page->init();
	}

	/**
	 * Initialize WordPress hooks
	 *
	 * Sets up the necessary WordPress action hooks for the plugin.
	 */
	private function init_hooks(): void {
		add_action( 'wp', array( $this, 'check_access' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Check user access
	 *
	 * Triggers the access checking functionality.
	 */
	public function check_access(): void {
		$this->access_checker->check_access();
	}

	/**
	 * Enqueue admin assets
	 *
	 * Loads the necessary CSS and JavaScript files for the admin interface.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_admin_assets( string $hook ): void {
		if ( 'settings_page_access-defender' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'access-defender-admin',
			ACCESS_DEFENDER_URL . 'assets/css/admin.css',
			array(),
			ACCESS_DEFENDER_VERSION
		);

		wp_enqueue_script(
			'access-defender-admin',
			ACCESS_DEFENDER_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			ACCESS_DEFENDER_VERSION,
			true
		);
	}
}
