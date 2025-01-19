<?php
/**
 * Admin page class for Access Defender plugin
 *
 * Handles rendering of the settings page and saving of options
 *
 * @package AccessDefender
 * @since 1.0.1
 */

namespace AccessDefender\Admin;

/**
 * Class AdminPage
 *
 * Handles rendering of the settings page and saving of options
 *
 * @package AccessDefender
 * @since 1.0.1
 */
class AdminPage {
	/**
	 * The options for the Access Defender plugin
	 *
	 * @since 1.0.1
	 * @var array
	 *  @access private
	 */
	private $options;

	/**
	 * Initializes the admin page.
	 *
	 * Adds the menu item for the settings page and initializes the settings.
	 *
	 * @since 1.0.1
	 */
	public function init(): void {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'init_settings' ) );
	}

	/**
	 * Adds the settings page menu item
	 *
	 * @since 1.0.1
	 */
	public function add_admin_menu(): void {
		add_options_page(
			'Access Defender',
			'Access Defender',
			'manage_options',
			'access-defender',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Renders the settings page for Access Defender
	 *
	 * @since 1.0.1
	 */
	public function render_admin_page(): void {
		$this->options = get_option( 'accessdefender_options', array() );
		require_once ACCESS_DEFENDER_PATH . 'includes/Views/admin/settings-page.php';
	}

	/**
	 * Initializes the settings for the Access Defender plugin.
	 *
	 * Registers the settings and adds the main settings section and fields
	 * for configuring the plugin options. The settings are sanitized using
	 * the specified callback method.
	 *
	 * @since 1.0.1
	 */
	public function init_settings(): void {
		register_setting(
			'accessdefender_options',
			'accessdefender_options',
			array( $this, 'sanitize_options' )
		);

		add_settings_section(
			'main_section',
			'General Settings',
			null,
			'access-defender'
		);

		$this->add_settings_fields();
	}

	/**
	 * Adds settings fields to the Access Defender settings page.
	 *
	 * This method defines and adds the necessary settings fields for
	 * configuring the Access Defender plugin options, including enabling
	 * VPN blocking, setting a custom warning title, and setting a custom
	 * warning message.
	 *
	 * The settings fields are registered under the 'main_section' of the
	 * 'access-defender' settings page.
	 *
	 * @since 1.0.1
	 */
	private function add_settings_fields(): void {
		add_settings_field(
			'enable_vpn_blocking',
			'Enable VPN Blocking',
			array( $this, 'render_switch_field' ),
			'access-defender',
			'main_section',
			array( 'enable_vpn_blocking' )
		);

		add_settings_field(
			'warning_title',
			'Warning Title',
			array( $this, 'render_text_field' ),
			'access-defender',
			'main_section',
			array( 'warning_title' )
		);

		add_settings_field(
			'warning_message',
			'Warning Message',
			array( $this, 'render_textarea_field' ),
			'access-defender',
			'main_section',
			array( 'warning_message' )
		);
	}

	/**
	 * Render a switch field for the settings page
	 *
	 * @param array $args Field arguments.
	 * @return void
	 */
	public function render_switch_field( $args ): void {
		$field   = $args[0];
		$checked = ( isset( $this->options[ $field ] ) && '1' === $this->options[ $field ] ) ? 'checked' : '';
		?>
	<label class="switch">
		<input type="checkbox" 
				name="accessdefender_options[<?php echo esc_attr( $field ); ?>]" 
				value="1" 
				<?php echo esc_attr( $checked ); ?>>
		<span class="slider round"></span>
	</label>
		<?php
	}

	/**
	 * Render a text input field for the settings page.
	 *
	 * @param array $args Field arguments, including the field name.
	 * @return void
	 */
	public function render_text_field( $args ): void {
		$field = $args[0];
		$value = $this->options[ $field ] ?? '';
		?>
		<input type="text" name="accessdefender_options[<?php echo esc_attr( $field ); ?>]" 
				value="<?php echo esc_attr( $value ); ?>" class="regular-text" style="width: 100%;">
		<?php
	}

	/**
	 * Render a textarea field for the settings page using WordPress editor.
	 *
	 * @param array $args Field arguments, including the field name.
	 * @return void
	 */
	public function render_textarea_field( $args ): void {
		$field = $args[0];
		$value = $this->options[ $field ] ?? '';

		wp_editor(
			$value,
			'accessdefender_' . $field,
			array(
				'textarea_name' => 'accessdefender_options[' . $field . ']',
				'textarea_rows' => 2,
				'editor_class'  => 'regular-text',
				'media_buttons' => false,
				'teeny'         => true,
				'quicktags'     => true,
			)
		);
	}

	/**
	 * Sanitizes the Access Defender plugin options.
	 *
	 * The function filters the input options array to ensure that only the
	 * expected options are processed. It also sanitizes the values of the
	 * options using the proper WordPress sanitization functions.
	 *
	 * @param array $input The options input array.
	 *
	 * @return array The sanitized options array.
	 */
	public function sanitize_options( $input ): array {
		$sanitized                        = array();
		$sanitized['enable_vpn_blocking'] = isset( $input['enable_vpn_blocking'] ) ? '1' : '0';
		$sanitized['warning_title']       = sanitize_text_field( $input['warning_title'] ?? '' );
		$sanitized['warning_message']     = wp_kses_post( $input['warning_message'] ?? '' );
		return $sanitized;
	}
}
