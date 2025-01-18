<?php
/**
 * Main Plugin Class
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
 * Main plugin class that handles initialization and core functionality.
 */
class Plugin implements PluginInterface {

	/**
	 * Bot detector service instance.
	 *
	 * @var BotDetector
	 */
	private BotDetector $bot_detector;

	/**
	 * IP detector service instance.
	 *
	 * @var IpDetector
	 */
	private IpDetector $ip_detector;

	/**
	 * VPN detector service instance.
	 *
	 * @var VpnDetector
	 */
	private VpnDetector $vpn_detector;

	/**
	 * Admin page instance.
	 *
	 * @var AdminPage
	 */
	private AdminPage $admin_page;

	/**
	 * Access checker instance.
	 *
	 * @var AccessChecker
	 */
	private AccessChecker $access_checker;

	/**
	 * Constructor
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
	 * This method loads and initializes the necessary classes for the plugin.
	 *
	 * @return void
	 */
	public function init(): void {
		$this->init_hooks();
		$this->admin_page->init();

		// Add plugin action and meta links.
		add_filter( 'plugin_action_links_' . plugin_basename( ACCESS_DEFENDER_FILE ), array( $this, 'add_plugin_settings_link' ) );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
	}

	/**
	 * Initialize WordPress hooks
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		add_action( 'wp', array( $this, 'check_access' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Add settings link to the plugin action links
	 *
	 * @param array $links Existing plugin action links.
	 * @return array Modified plugin action links.
	 */
	public function add_plugin_settings_link( array $links ): array {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			admin_url( 'options-general.php?page=access-defender' ),
			esc_html__( 'Settings', 'access-defender' )
		);

		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Add custom row meta links for the plugin
	 *
	 * @param array  $links Existing row meta links.
	 * @param string $file  Current plugin file.
	 * @return array Modified row meta links.
	 */
	public function plugin_row_meta( array $links, string $file ): array {
		if ( plugin_basename( ACCESS_DEFENDER_FILE ) === $file ) {
			$row_meta = array(
				'docs'    => sprintf(
					'<a href="%s" target="_blank">%s</a>',
					'https://wordpress.org/plugins/access-defender/#description',
					esc_html__( 'Documentation', 'access-defender' )
				),
				'support' => sprintf(
					'<a href="%s" target="_blank">%s</a>',
					'https://wordpress.org/support/plugin/access-defender/',
					esc_html__( 'Support', 'access-defender' )
				),
			);

			return array_merge( $links, $row_meta );
		}

		return $links;
	}

	/**
	 * Check user access
	 *
	 * @return void
	 */
	public function check_access(): void {
		$this->access_checker->check_access();
	}

	/**
	 * Enqueue admin assets
	 *
	 * @param string $hook The current admin page hook.
	 * @return void
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
