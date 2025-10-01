<?php
/**
 * Main Plugin Class
 *
 * @package AccessDefender
 * @subpackage Core
 */

namespace AccessDefender\Core;

use AccessDefender\Admin\AdminPage;
use AccessDefender\Services\ApiProviderManager;
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
		// Check for version upgrade and run migration if needed
		$this->check_version_upgrade();
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
		// Hook on 'wp' to ensure WordPress is fully loaded and user is authenticated
		add_action( 'wp', array( $this, 'check_access' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		
		// AJAX handlers for v1.1.0
		add_action( 'wp_ajax_accessdefender_validate_api_key', array( $this, 'ajax_validate_api_key' ) );
		add_action( 'wp_ajax_accessdefender_provider_status', array( $this, 'ajax_provider_status' ) );
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

		// Localize script with AJAX nonce for v1.1.0
		wp_localize_script(
			'access-defender-admin',
			'accessdefender_admin',
			array(
				'nonce' => wp_create_nonce( 'accessdefender_admin_nonce' ),
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
			)
		);
	}

	/**
	 * AJAX handler for API key validation
	 *
	 * @return void
	 * @since 1.1.0
	 */
	public function ajax_validate_api_key(): void {
		// Verify nonce
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'accessdefender_admin_nonce' ) ) {
			wp_send_json_error( 'Invalid nonce' );
			return;
		}

		// Check user capability
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
			return;
		}

		$provider = sanitize_text_field( wp_unslash( $_POST['provider'] ?? '' ) );
		$api_key  = sanitize_text_field( wp_unslash( $_POST['api_key'] ?? '' ) );

		$api_manager = new ApiProviderManager();
		$is_valid    = $api_manager->validate_api_key( $provider, $api_key );

		wp_send_json_success( $is_valid );
	}

	/**
	 * AJAX handler for provider status
	 *
	 * @return void
	 * @since 1.1.0
	 */
	public function ajax_provider_status(): void {
		// Verify nonce
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'accessdefender_admin_nonce' ) ) {
			wp_send_json_error( 'Invalid nonce' );
			return;
		}

		// Check user capability
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
			return;
		}

		$api_manager = new ApiProviderManager();
		$status      = $api_manager->get_providers_status();

		wp_send_json_success( $status );
	}

	/**
	 * Check for version upgrade and run migration if needed
	 *
	 * @return void
	 * @since 1.1.0
	 */
	private function check_version_upgrade(): void {
		$core_settings = get_option( 'accessdefender_core_settings', array() );
		$stored_version = $core_settings['version'] ?? '';
		
		// If no version is stored or version is different, check for migration needs
		if ( empty( $stored_version ) || version_compare( $stored_version, ACCESS_DEFENDER_VERSION, '<' ) ) {
			// Check if legacy options exist and need migration
			$legacy_options = get_option( 'accessdefender_options' );
			
			if ( $legacy_options && is_array( $legacy_options ) ) {
				// Run migration
				ActivationHooks::migrate_legacy_options();
			}
			
			// Update version in core settings
			if ( empty( $core_settings ) ) {
				// If core settings don't exist, create them
				ActivationHooks::setup_core_settings();
			} else {
				// Update version in existing core settings
				$core_settings['version'] = ACCESS_DEFENDER_VERSION;
				update_option( 'accessdefender_core_settings', $core_settings );
			}
			
			// Ensure provider settings exist
			if ( ! get_option( 'accessdefender_provider_settings' ) ) {
				ActivationHooks::setup_provider_settings();
			}
		}
	}
}
