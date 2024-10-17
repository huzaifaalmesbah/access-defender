<?php
/**
 * Plugin Name: Access Defender
 * Description: Blocks users using VPN or proxy and shows a warning notice using IPapi.co free API with enhanced IP detection.
 * Version: 1.0.0
 * Author: Huzaifa Al Mesbah
 * Text Domain: access-defender
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package AccessDefender
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class.
 */
class Access_Defender {

	/**
	 * Plugin options.
	 *
	 * @var array
	 */
	private $options;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'load_plugin_options' ) );
		add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'page_init' ) );
		add_action( 'wp', array( $this, 'check_vpn_proxy' ) );
	}

	/**
	 * Load plugin options from database.
	 */
	public function load_plugin_options() {
		$this->options = get_option( 'accessdefender_options' );
	}

	/**
	 * Add options page under Settings menu.
	 */
	public function add_plugin_page() {
		add_options_page(
			'Access Defender Settings',
			'Access Defender',
			'manage_options',
			'access-defender',
			array( $this, 'create_admin_page' )
		);
	}

	/**
	 * Create the admin page form for plugin settings.
	 */
	public function create_admin_page() {
		?>
		<div class="wrap">
			<h1>Access Defender Settings</h1>
			<form method="post" action="options.php">
			<?php
				settings_fields( 'accessdefender_option_group' );
				do_settings_sections( 'access-defender-admin' );
				submit_button();
			?>
			</form>
		</div>
		<?php
	}

	/**
	 * Initialize plugin settings.
	 */
	public function page_init() {
		register_setting(
			'accessdefender_option_group',
			'accessdefender_options',
			array( $this, 'sanitize' )
		);

		add_settings_section(
			'accessdefender_setting_section',
			'General Settings',
			array( $this, 'section_info' ),
			'access-defender-admin'
		);

		// Enable VPN blocking field.
		add_settings_field(
			'enable_vpn_blocking',
			'Enable VPN Blocking',
			array( $this, 'enable_vpn_blocking_callback' ),
			'access-defender-admin',
			'accessdefender_setting_section'
		);

		// Add a new settings field for the custom warning title.
		add_settings_field(
			'warning_title',
			'Warning Title',
			array( $this, 'warning_title_callback' ),
			'access-defender-admin',
			'accessdefender_setting_section'
		);

		// Add a new settings field for the custom warning message.
		add_settings_field(
			'warning_message',
			'Warning Message',
			array( $this, 'warning_message_callback' ),
			'access-defender-admin',
			'accessdefender_setting_section'
		);
	}

	/**
	 * Sanitize the plugin settings.
	 *
	 * @param array $input The raw input array.
	 * @return array The sanitized input array.
	 */
	public function sanitize( $input ) {
		$sanitary_values = array();
		if ( isset( $input['enable_vpn_blocking'] ) ) {
			$sanitary_values['enable_vpn_blocking'] = $input['enable_vpn_blocking'];
		}
		if ( isset( $input['warning_title'] ) ) {
			$sanitary_values['warning_title'] = sanitize_text_field( $input['warning_title'] );
		}
		if ( isset( $input['warning_message'] ) ) {
			$sanitary_values['warning_message'] = wp_kses_post( $input['warning_message'] );
		}
		return $sanitary_values;
	}

	/**
	 * Section info for the plugin settings page.
	 */
	public function section_info() {
		echo esc_html__( 'Configure the settings for Access Defender below:', 'access-defender' );
	}

	/**
	 * Callback for Enable VPN Blocking checkbox.
	 */
	public function enable_vpn_blocking_callback() {
		printf(
			'<input type="checkbox" name="accessdefender_options[enable_vpn_blocking]" id="enable_vpn_blocking" value="1" %s>',
			( isset( $this->options['enable_vpn_blocking'] ) && '1' === $this->options['enable_vpn_blocking'] ) ? 'checked' : ''
		);
		echo '<label for="enable_vpn_blocking">' . esc_html__( 'Enable VPN and Proxy Blocking', 'access-defender' ) . '</label>';
	}

	/**
	 * Callback for the custom warning title input field.
	 */
	public function warning_title_callback() {
		$warning_title = isset( $this->options['warning_title'] ) ? esc_attr( $this->options['warning_title'] ) : '';
		printf(
			'<input type="text" name="accessdefender_options[warning_title]" value="%s" style="width: 100%%;">',
			esc_attr( $warning_title )
		);
		echo '<p class="description">' . esc_html__( 'Enter the custom warning title (e.g., "Access Denied").', 'access-defender' ) . '</p>';
	}

	/**
	 * Callback for custom warning message input field using WordPress editor.
	 */
	public function warning_message_callback() {
		$warning_message = isset( $this->options['warning_message'] ) ? wp_kses_post( $this->options['warning_message'] ) : '';
		wp_editor(
			$warning_message,
			'accessdefender_warning_message',
			array(
				'textarea_name' => 'accessdefender_options[warning_message]',
				'media_buttons' => false,
				'textarea_rows' => 10,
				'teeny'         => true,
				'quicktags'     => true,
			)
		);
	}

	/**
	 * Check if the user is using VPN or Proxy and show warning message if detected.
	 */
	public function check_vpn_proxy() {
		if ( ! isset( $this->options['enable_vpn_blocking'] ) || '1' !== $this->options['enable_vpn_blocking'] ) {
			return; // VPN blocking is disabled.
		}

		if ( $this->is_vpn_or_proxy() ) {
			wp_die( wp_kses_post( $this->get_warning_message() ), '', array( 'response' => 403 ) );
		}
	}

	/**
	 * Retrieve the client's IP address from the HTTP headers.
	 *
	 * First, it checks the HTTP_CLIENT_IP header, then the HTTP_X_FORWARDED_FOR
	 * header, followed by the HTTP_X_FORWARDED and HTTP_FORWARDED_FOR headers.
	 * If none of these headers are present, it defaults to the REMOTE_ADDR header.
	 *
	 * @return string The client's IP address or an empty string if it can't be determined.
	 */
	private function get_client_ip() {
		$ip_headers = array(
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR',
		);
		foreach ( $ip_headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				$ip_list   = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
				$ip_array  = explode( ',', $ip_list );
				$client_ip = trim( $ip_array[0] );
				if ( filter_var( $client_ip, FILTER_VALIDATE_IP ) ) {
					return $client_ip;
				}
			}
		}
		return '';
	}

	/**
	 * Check if the user is using VPN or Proxy by calling the external API.
	 *
	 * @return bool True if VPN or proxy is detected, false otherwise.
	 */
	private function is_vpn_or_proxy() {
		$ip_address = $this->get_client_ip();

		if ( empty( $ip_address ) ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'Access Defender: Unable to determine client IP address.' );
			return false; // If we can't determine the IP, don't block the user.
		}

		$api_url  = "http://ip-api.com/json/{$ip_address}?fields=proxy,hosting";
		$response = wp_remote_get( $api_url );

		if ( is_wp_error( $response ) ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'Access Defender API Error: ' . $response->get_error_message() );
			return false; // If there's an error, don't block the user.
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		// Check if the IP is identified as a proxy or hosting service.
		return ( isset( $data['proxy'] ) && true === $data['proxy'] ) || ( isset( $data['hosting'] ) && true === $data['hosting'] );
	}

	/**
	 * Get the warning message with the title and the message.
	 *
	 * @return string The formatted warning message.
	 */
	private function get_warning_message() {
		$title_html      = '<h1>' . esc_html( $this->get_warning_title() ) . '</h1>';
		$default_message = esc_html__(
			'We\'ve detected that you\'re using a VPN or proxy. 
            For security reasons, access to this website is not allowed through VPNs or proxies. 
            Please disable your VPN or proxy and try again.',
			'access-defender'
		);
		$message         = ! empty( $this->options['warning_message'] ) ? wp_kses_post( '<p>' . $this->options['warning_message'] . '</p>' ) : '<p>' . wp_kses_post( $default_message ) . '</p>';
		return $title_html . $message;
	}

	/**
	 * Get the warning title, either custom or default.
	 *
	 * @return string The warning title.
	 */
	private function get_warning_title() {
		$default_title = __( 'Access Denied', 'access-defender' );
		return ! empty( $this->options['warning_title'] ) ? esc_html( $this->options['warning_title'] ) : $default_title;
	}
}

new Access_Defender();