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
	 * @updated 1.1.0 - Added API provider settings
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
			'provider_mode',
			'Provider Configuration',
			array( $this, 'render_provider_mode_field' ),
			'access-defender',
			'main_section',
			array( 'provider_mode' )
		);

		add_settings_field(
			'free_providers',
			'<span class="free-providers-header" style="display: none;">Free Providers (Auto-Rotation)</span>',
			array( $this, 'render_free_providers_field' ),
			'access-defender',
			'main_section',
			array( 'free_providers' )
		);

		add_settings_field(
			'paid_provider',
			'<span class="paid-providers-header" style="display: none;">Paid Provider Selection</span>',
			array( $this, 'render_paid_provider_field' ),
			'access-defender',
			'main_section',
			array( 'paid_provider' )
		);

		add_settings_field(
			'api_keys',
			'API Keys',
			array( $this, 'render_dynamic_api_keys_field' ),
			'access-defender',
			'main_section',
			array( 'api_keys' )
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
	 * Render provider mode selection field
	 *
	 * @param array $args Field arguments.
	 * @return void
	 * @since 1.1.0
	 */
	public function render_provider_mode_field( $args ): void {
		$field = $args[0];
		$value = $this->options[ $field ] ?? 'free';
		?>
		<div class="provider-mode-selection">
			<label style="display: block; margin-bottom: 15px;">
				<input type="radio" 
					   name="accessdefender_options[<?php echo esc_attr( $field ); ?>]" 
					   value="free" 
					   <?php checked( $value, 'free' ); ?>
					   class="provider-mode-radio">
				<strong>Use Free Providers</strong> - Multiple free APIs with automatic rotation when limits hit
			</label>
			<label style="display: block; margin-bottom: 15px;">
				<input type="radio" 
					   name="accessdefender_options[<?php echo esc_attr( $field ); ?>]" 
					   value="paid" 
					   <?php checked( $value, 'paid' ); ?>
					   class="provider-mode-radio">
				<strong>Use Paid Provider</strong> - Single reliable paid API service
			</label>
		</div>
		<p class="description">Choose your preferred detection method. Free providers rotate automatically when limits are reached.</p>
		<?php
	}

	/**
	 * Render free providers selection field
	 *
	 * @param array $args Field arguments.
	 * @return void
	 * @since 1.1.0
	 */
	public function render_free_providers_field( $args ): void {
		$field = $args[0];
		$value = $this->options[ $field ] ?? array( 'ip-api' );
		
		if ( ! is_array( $value ) ) {
			$value = array( $value );
		}
		
		// Get API provider manager to list providers
		$provider_manager = new \AccessDefender\Services\ApiProviderManager();
		$providers = $provider_manager->get_all_providers();
		
		$provider_mode = $this->options['provider_mode'] ?? 'free';
		?>
		<div class="free-providers-section" style="<?php echo $provider_mode !== 'free' ? 'display: none;' : ''; ?>">
			<div class="access-defender-providers-grid">
				<?php foreach ( $providers as $slug => $provider ) : ?>
					<?php if ( $provider->is_free() ) : ?>
						<div class="provider-card <?php echo in_array( $slug, $value, true ) ? 'selected' : ''; ?>">
							<label style="cursor: pointer; display: block;">
								<input type="checkbox" 
									   name="accessdefender_options[<?php echo esc_attr( $field ); ?>][]" 
									   value="<?php echo esc_attr( $slug ); ?>"
									   <?php checked( in_array( $slug, $value, true ) ); ?>
									   style="margin-right: 8px;">
								<h4><?php echo esc_html( $provider->get_name() ); ?>
									<span class="provider-badge free">Free</span>
								</h4>
								<?php
								$stats = $provider->get_usage_stats();
								?>
								<div class="provider-status provider-status-<?php echo esc_attr( $slug ); ?>">
									<span class="status-indicator <?php echo $stats['success_rate'] > 80 ? 'healthy' : 'degraded'; ?>"></span>
									<span class="provider-stats">
										Used: <span class="usage-count"><?php echo number_format( $stats['monthly_usage'] ); ?></span> | 
										Success: <span class="success-count"><?php echo number_format( $stats['total_success'] ); ?></span> | 
										Failed: <span class="failed-count"><?php echo number_format( $stats['total_failed'] ); ?></span>
									</span>
								</div>
							</label>
						</div>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
			<p class="description">
				<strong>Smart Rotation:</strong> When one provider hits its monthly limit, the system automatically switches to the next available provider. Select multiple providers for maximum reliability.
			</p>
		</div>
		<?php
	}

	/**
	 * Render paid provider selection field
	 *
	 * @param array $args Field arguments.
	 * @return void
	 * @since 1.1.0
	 */
	public function render_paid_provider_field( $args ): void {
		$field = $args[0];
		$value = $this->options[ $field ] ?? '';
		
		// Get API provider manager to list providers
		$provider_manager = new \AccessDefender\Services\ApiProviderManager();
		$providers = $provider_manager->get_all_providers();
		
		$provider_mode = $this->options['provider_mode'] ?? 'free';
		?>
		<div class="paid-providers-section" style="<?php echo $provider_mode !== 'paid' ? 'display: none;' : ''; ?>">
			<div class="access-defender-providers-grid">
				<?php foreach ( $providers as $slug => $provider ) : ?>
					<?php if ( ! $provider->is_free() ) : ?>
						<div class="provider-card <?php echo $value === $slug ? 'selected' : ''; ?>">
							<label style="cursor: pointer; display: block;">
								<input type="radio" 
									   name="accessdefender_options[<?php echo esc_attr( $field ); ?>]" 
									   value="<?php echo esc_attr( $slug ); ?>"
									   <?php checked( $value, $slug ); ?>
									   style="margin-right: 8px;">
								<h4><?php echo esc_html( $provider->get_name() ); ?>
									<span class="provider-badge paid">Paid</span>
								</h4>
								<?php
								$stats = $provider->get_usage_stats();
								?>
								<div class="provider-status provider-status-<?php echo esc_attr( $slug ); ?>">
									<span class="status-indicator <?php echo $stats['success_rate'] > 80 ? 'healthy' : 'degraded'; ?>"></span>
									<span class="provider-stats">
										Used: <span class="usage-count"><?php echo number_format( $stats['monthly_usage'] ); ?></span> | 
										Success: <span class="success-count"><?php echo number_format( $stats['total_success'] ); ?></span> | 
										Failed: <span class="failed-count"><?php echo number_format( $stats['total_failed'] ); ?></span>
									</span>
								</div>
							</label>
						</div>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
			<p class="description">
				<strong>Dedicated Service:</strong> Use a single reliable paid provider with higher rate limits and better accuracy.
			</p>
		</div>
		<?php
	}

	/**
	 * Render dynamic API keys field (shows only for selected providers)
	 *
	 * @param array $args Field arguments.
	 * @return void
	 * @since 1.1.0
	 */
	public function render_dynamic_api_keys_field( $args ): void {
		$field = $args[0];
		$value = $this->options[ $field ] ?? array();
		
		// Get API provider manager to list providers
		$provider_manager = new \AccessDefender\Services\ApiProviderManager();
		$providers = $provider_manager->get_all_providers();
		
		$provider_mode = $this->options['provider_mode'] ?? 'free';
		$paid_provider = $this->options['paid_provider'] ?? '';
		?>
		<div class="api-keys-section" id="dynamic-api-keys">
			<?php foreach ( $providers as $slug => $provider ) : ?>
				<?php if ( $provider->requires_api_key() ) : ?>
					<div class="api-key-field api-key-<?php echo esc_attr( $slug ); ?>" 
						 style="margin-bottom: 15px; padding: 15px; border: 1px solid #ddd; border-radius: 4px; display: none;"
						 data-provider="<?php echo esc_attr( $slug ); ?>">
						<label style="font-weight: 600; display: block; margin-bottom: 5px;">
							<?php echo esc_html( $provider->get_name() ); ?> API Key
						</label>
						<input type="password" 
							   name="accessdefender_options[<?php echo esc_attr( $field ); ?>][<?php echo esc_attr( $slug ); ?>]" 
							   value="<?php echo esc_attr( $value[ $slug ] ?? '' ); ?>" 
							   class="regular-text api-key-input" 
							   style="width: 100%;"
							   data-provider="<?php echo esc_attr( $slug ); ?>"
							   placeholder="Enter your <?php echo esc_attr( $provider->get_name() ); ?> API key">
						<?php
						$stats = $provider->get_usage_stats();
						?>
						<p class="description">
							Used: <?php echo number_format( $stats['monthly_usage'] ); ?> | Success: <?php echo number_format( $stats['total_success'] ); ?> | Failed: <?php echo number_format( $stats['total_failed'] ); ?>
							<?php if ( ! empty( $value[ $slug ] ) ) : ?>
								| <span id="status-<?php echo esc_attr( $slug ); ?>">Validating...</span>
							<?php endif; ?>
						</p>
					</div>
				<?php endif; ?>
			<?php endforeach; ?>
			<div id="no-api-key-needed" style="<?php echo $provider_mode !== 'free' ? 'display: none;' : ''; ?>">
				<p style="color: #46b450; font-weight: 600;">✓ No API keys required for free providers!</p>
			</div>
		</div>
		<?php
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
	 * @updated 1.1.0 - Added API provider options sanitization
	 */
	public function sanitize_options( $input ): array {
		$sanitized                        = array();
		$sanitized['enable_vpn_blocking'] = isset( $input['enable_vpn_blocking'] ) ? '1' : '0';
		$sanitized['provider_mode']       = sanitize_text_field( $input['provider_mode'] ?? 'free' );
		$sanitized['free_providers']      = isset( $input['free_providers'] ) ? array_map( 'sanitize_text_field', $input['free_providers'] ) : array( 'ip-api' );
		$sanitized['paid_provider']       = sanitize_text_field( $input['paid_provider'] ?? '' );
		$sanitized['warning_title']       = sanitize_text_field( $input['warning_title'] ?? '' );
		$sanitized['warning_message']     = wp_kses_post( $input['warning_message'] ?? '' );
		
		// Sanitize API keys
		$sanitized['api_keys'] = array();
		if ( isset( $input['api_keys'] ) && is_array( $input['api_keys'] ) ) {
			foreach ( $input['api_keys'] as $provider => $key ) {
				$sanitized['api_keys'][ sanitize_text_field( $provider ) ] = sanitize_text_field( $key );
			}
		}
		
		// Set backward compatibility fields based on mode
		if ( $sanitized['provider_mode'] === 'free' ) {
			$sanitized['primary_provider'] = 'ip-api';
			$sanitized['active_providers'] = $sanitized['free_providers'];
		} else {
			$sanitized['primary_provider'] = $sanitized['paid_provider'];
			$sanitized['active_providers'] = array( $sanitized['paid_provider'] );
		}
		
		return $sanitized;
	}
}
