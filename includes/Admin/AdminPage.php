<?php
/**
 * Admin Page class for Access Defender plugin
 *
 * Handles rendering of the settings page and saving of options
 *
 * @package AccessDefender\Admin
 * @since 1.0.1
 */

namespace AccessDefender\Admin;

use AccessDefender\Services\ApiProviderManager;

/**
 * Class AdminPage
 *
 * Handles rendering of the settings page and saving of options
 *
 * @package AccessDefender\Admin
 * @since 1.0.1
 */
class AdminPage {
	/**
	 * Validate provider configuration before saving
	 *
	 * @param array $provider_input Provider settings input
	 * @return array Array of validation errors
	 * @since 1.1.0
	 */
	private function validate_provider_configuration( array $provider_input ): array {
		$errors = array();
		
		// Check if paid mode is selected
		if ( isset( $provider_input['provider_mode'] ) && $provider_input['provider_mode'] === 'paid' ) {
			
			// Check if paid provider is selected
			if ( empty( $provider_input['paid_provider'] ) ) {
				$errors[] = array(
					'code' => 'no_paid_provider',
					'message' => 'Please select a paid provider when using paid mode.'
				);
				return $errors;
			}
			
			$selected_provider = $provider_input['paid_provider'];
			
			// Check if API key is provided
			if ( empty( $provider_input['api_keys'][ $selected_provider ] ) ) {
				$errors[] = array(
					'code' => 'missing_api_key',
					'message' => 'Please provide a valid API key for the selected paid provider.'
				);
				return $errors;
			}
			
			// Validate API key
			$api_key = trim( $provider_input['api_keys'][ $selected_provider ] );
			if ( ! $this->validate_api_key_for_provider( $selected_provider, $api_key ) ) {
				$errors[] = array(
					'code' => 'invalid_api_key',
					'message' => 'The provided API key is invalid. Please check your API key and try again.'
				);
			}
		}
		
		return $errors;
	}

	/**
	 * Validate API key for a specific provider
	 *
	 * @param string $provider_slug Provider slug
	 * @param string $api_key API key to validate
	 * @return bool True if valid, false otherwise
	 * @since 1.1.0
	 */
	private function validate_api_key_for_provider( string $provider_slug, string $api_key ): bool {
		try {
			$api_manager = new ApiProviderManager();
			return $api_manager->validate_api_key( $provider_slug, $api_key );
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * The options for the Access Defender plugin
	 *
	 * @since 1.0.1
	 * @var array
	 * @access private
	 */
	private $options;

	/**
	 * Core plugin settings
	 *
	 * @since 1.1.0
	 * @var array
	 * @access private
	 */
	private $core_settings;

	/**
	 * Provider settings
	 *
	 * @since 1.1.0
	 * @var array
	 * @access private
	 */
	private $provider_settings;

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
		add_action( 'admin_init', array( $this, 'handle_form_submission' ) );
	}
	


	/**
	 * Handle custom form submission for both option groups
	 *
	 * @since 1.1.0
	 */
	public function handle_form_submission(): void {
		if ( ! isset( $_POST['submit'] ) ) {
			return;
		}

		if ( ! check_admin_referer( 'accessdefender_save_settings', 'accessdefender_nonce' ) ) {
			add_settings_error( 'accessdefender_messages', 'security_check_failed', 'Security check failed', 'error' );
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			add_settings_error( 'accessdefender_messages', 'insufficient_permissions', 'Insufficient permissions', 'error' );
			return;
		}

		// Process core settings
		$core_input = array();
		if ( isset( $_POST['accessdefender_core_settings'] ) ) {
			$core_input = map_deep( wp_unslash( $_POST['accessdefender_core_settings'] ), 'sanitize_text_field' );
		}
		
		// Handle individual core fields that might not be in the array
		if ( isset( $_POST['enable_vpn_blocking'] ) ) {
			$core_input['enable_vpn_blocking'] = sanitize_text_field( wp_unslash( $_POST['enable_vpn_blocking'] ) );
		}
		if ( isset( $_POST['warning_title'] ) ) {
			$core_input['warning_title'] = sanitize_text_field( wp_unslash( $_POST['warning_title'] ) );
		}
		if ( isset( $_POST['warning_message'] ) ) {
			$core_input['warning_message'] = sanitize_textarea_field( wp_unslash( $_POST['warning_message'] ) );
		}

		// Process provider settings
		$provider_input = array();
		if ( isset( $_POST['accessdefender_provider_settings'] ) ) {
			$provider_input = map_deep( wp_unslash( $_POST['accessdefender_provider_settings'] ), 'sanitize_text_field' );
		}

		// Handle individual provider fields
		$provider_fields = array( 'provider_mode', 'free_providers', 'paid_provider', 'api_keys' );
		foreach ( $provider_fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				$provider_input[ $field ] = map_deep( wp_unslash( $_POST[ $field ] ), 'sanitize_text_field' );
			}
		}

		// Validate paid provider settings before saving
		$validation_errors = $this->validate_provider_configuration( $provider_input );
		
		if ( ! empty( $validation_errors ) ) {
			// Add validation error messages
			foreach ( $validation_errors as $error ) {
				add_settings_error( 'accessdefender_messages', $error['code'], $error['message'], 'error' );
			}
			
			// Force fallback to free mode if paid provider validation fails
			if ( isset( $provider_input['provider_mode'] ) && $provider_input['provider_mode'] === 'paid' ) {
				$provider_input['provider_mode'] = 'free';
				$provider_input['free_providers'] = array( 'ip-api' ); // Default free provider
			}
		}

		// Sanitize and save core settings
		if ( ! empty( $core_input ) ) {
			$sanitized_core = $this->sanitize_core_settings( $core_input );
			update_option( 'accessdefender_core_settings', $sanitized_core );
		}

		// Sanitize and save provider settings  
		if ( ! empty( $provider_input ) ) {
			$sanitized_provider = $this->sanitize_provider_settings( $provider_input );
			update_option( 'accessdefender_provider_settings', $sanitized_provider );
		}

		// Store messages in transients to survive redirect
		if ( ! empty( $validation_errors ) ) {
			// Store error messages
			$error_messages = array();
			foreach ( $validation_errors as $error ) {
				$error_messages[] = array(
					'message' => $error['message'],
					'type' => 'error'
				);
			}
			
			// Add fallback message if switched to free mode
			if ( isset( $provider_input['provider_mode'] ) && $provider_input['provider_mode'] === 'free' ) {
				$error_messages[] = array(
					'message' => 'Switched to free mode due to validation errors.',
					'type' => 'notice-warning'
				);
			}
			
			set_transient( 'accessdefender_admin_messages', $error_messages, 30 );
		} else {
			// Store success message
			$success_messages = array(
				array(
					'message' => 'Settings saved successfully!',
					'type' => 'updated'
				)
			);
			set_transient( 'accessdefender_admin_messages', $success_messages, 30 );
		}

		// Redirect to prevent resubmission
		$redirect_url = add_query_arg( 'settings-updated', 'true', wp_get_referer() );
		wp_redirect( $redirect_url );
		exit;
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
	 * Render the admin page
	 *
	 * @since 1.0.1
	 */
	public function render_admin_page(): void {
		$this->load_options();
		$this->display_admin_messages();
		require_once ACCESS_DEFENDER_PATH . 'includes/Views/admin/settings-page.php';
	}

	/**
	 * Display admin messages from transients
	 *
	 * @since 1.1.0
	 */
	private function display_admin_messages(): void {
		$messages = get_transient( 'accessdefender_admin_messages' );
		
		if ( $messages && is_array( $messages ) ) {
			foreach ( $messages as $message ) {
				add_settings_error(
					'accessdefender_messages',
					'accessdefender_message_' . uniqid(),
					$message['message'],
					$message['type']
				);
			}
			
			// Clear the transient after displaying
			delete_transient( 'accessdefender_admin_messages' );
		}
	}

	/**
	 * Load plugin options
	 *
	 * @since 1.1.0
	 */
	private function load_options(): void {
		$this->core_settings = get_option( 'accessdefender_core_settings', array() );
		$this->provider_settings = get_option( 'accessdefender_provider_settings', array() );
		$this->options = array_merge( $this->core_settings, $this->provider_settings );
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
		// Register option groups for the new structure
		register_setting(
			'accessdefender_core_settings',
			'accessdefender_core_settings',
			array( $this, 'validate_core_settings' )
		);
		
		register_setting(
			'accessdefender_provider_settings', 
			'accessdefender_provider_settings',
			array( $this, 'validate_provider_settings' )
		);
		
		// Add settings sections
		add_settings_section(
			'main_section',
			'General Settings',
			null,
			'access-defender'
		);

		add_settings_section(
			'provider_section',
			'Provider Settings', 
			null,
			'access-defender-providers'
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
		// Core settings fields
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

		// Provider settings fields
		add_settings_field(
			'provider_mode',
			'Provider Configuration',
			array( $this, 'render_provider_mode_field' ),
			'access-defender-providers',
			'provider_section',
			array( 'provider_mode' )
		);

		add_settings_field(
			'free_providers',
			'<span class="free-providers-header" style="display: none;">Free Providers (Auto-Rotation)</span>',
			array( $this, 'render_free_providers_field' ),
			'access-defender-providers',
			'provider_section',
			array( 'free_providers' )
		);

		add_settings_field(
			'paid_provider',
			'<span class="paid-providers-header" style="display: none;">Paid Provider Selection</span>',
			array( $this, 'render_paid_provider_field' ),
			'access-defender-providers',
			'provider_section',
			array( 'paid_provider' )
		);

		add_settings_field(
			'api_keys',
			'API Keys',
			array( $this, 'render_dynamic_api_keys_field' ),
			'access-defender-providers',
			'provider_section',
			array( 'api_keys' )
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
				name="<?php echo esc_attr( $field ); ?>" 
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
		<input type="text" name="<?php echo esc_attr( $field ); ?>" 
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
		
		// Remove extra backslashes when displaying the value
		$value = stripslashes( $value );

		wp_editor(
			$value,
			'accessdefender_' . $field,
			array(
				'textarea_name' => $field,
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
					   name="<?php echo esc_attr( $field ); ?>" 
					   value="free" 
					   <?php checked( $value, 'free' ); ?>
					   class="provider-mode-radio">
				<strong>Use Free Providers</strong> - Multiple free APIs with sequential rotation when limits hit
			</label>
			<label style="display: block; margin-bottom: 15px;">
				<input type="radio" 
					   name="<?php echo esc_attr( $field ); ?>" 
					   value="paid" 
					   <?php checked( $value, 'paid' ); ?>
					   class="provider-mode-radio">
				<strong>Use Paid Provider</strong> - Single reliable paid API service
			</label>
		</div>
		<p class="description">Choose your preferred detection method. The free endpoint is limited to 45 unique requests per minute. For higher usage, consider upgrading to a paid provider.</p>
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
		$provider_manager = new ApiProviderManager();
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
									   name="<?php echo esc_attr( $field ); ?>[]" 
									   value="<?php echo esc_attr( $slug ); ?>"
									   <?php checked( in_array( $slug, $value, true ) ); ?>
									   style="margin-right: 8px;">
				<h4><?php echo esc_html( $provider->get_name() ); ?>
					<span class="provider-badge free">Free</span>
				</h4>
				<p class="provider-limit-info">
					<strong>Rate Limit:</strong> 45 unique requests per minute
				</p>
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
				
				<!-- Coming Soon Free Provider -->
				<div class="provider-card disabled coming-soon">
					<label style="cursor: not-allowed; display: block; opacity: 0.6;">
						<input type="checkbox" 
							   disabled
							   style="margin-right: 8px;">
						<h4>Additional Free Provider
							<span class="provider-badge free">Free</span>
							<span class="provider-badge coming-soon">Coming Soon</span>
						</h4>
						<p class="provider-limit-info">
							<strong>Enhanced rotation capabilities coming soon</strong>
						</p>
						<div class="provider-status">
							<span class="status-indicator coming-soon"></span>
							<span class="provider-stats">
								More free providers for better reliability
							</span>
						</div>
					</label>
				</div>
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
		$provider_manager = new ApiProviderManager();
		$providers = $provider_manager->get_all_providers();
		
		$provider_mode = $this->options['provider_mode'] ?? 'free';
		?>
		<div class="paid-providers-section" style="<?php echo $provider_mode !== 'paid' ? 'display: none;' : ''; ?>">
			<div class="access-defender-providers-grid">
		<?php foreach ( $providers as $slug => $provider ) : ?>
					<?php if ( ! $provider->is_free() ) : ?>
				<?php 
					$enabled_paid_slugs = array( 'proxycheck', 'ipgeolocation' );
					$is_enabled = in_array( $slug, $enabled_paid_slugs, true );
				?>
				<div class="provider-card <?php echo $value === $slug ? 'selected' : ''; ?> <?php echo $is_enabled ? '' : 'disabled'; ?>">
					<label style="cursor: <?php echo $is_enabled ? 'pointer' : 'not-allowed'; ?>; display: block; opacity: <?php echo $is_enabled ? '1' : '0.6'; ?>;">
								<input type="radio" 
									   name="<?php echo esc_attr( $field ); ?>" 
									   value="<?php echo esc_attr( $slug ); ?>"
									   <?php checked( $value, $slug ); ?>
							   <?php echo $is_enabled ? '' : 'disabled'; ?>
									   style="margin-right: 8px;">
				<h4><?php echo esc_html( $provider->get_name() ); ?>
					<span class="provider-badge paid">Paid</span>
				</h4>
						<?php if ( method_exists( $provider, 'get_signup_url' ) ) : ?>
					<p class="provider-signup-link">
						<a href="<?php echo esc_url( $provider->get_signup_url() ); ?>" target="_blank" class="button button-secondary button-small">
							Get API Key
						</a>
							<?php if ( $is_enabled ) : ?>
							<?php if ( $slug === 'ipgeolocation' ) : ?>
								<span style="margin-left:8px; font-size:12px; color:#555;">Requires API key from the IPGeolocation.io Security (Security API) package for proxy/VPN detection.</span>
							<?php elseif ( $slug === 'proxycheck' ) : ?>
								<span style="margin-left:8px; font-size:12px; color:#555;">Free signup includes 1,000 requests/day. Upgrade anytime.</span>
							<?php endif; ?>
							<?php else : ?>
							<span style="margin-left:8px; font-size:12px; color:#a00;">Coming soon</span>
						<?php endif; ?>
					</p>
				<?php endif; ?>
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
				<strong>Dedicated Service:</strong> Use a single reliable paid provider with higher rate limits and better accuracy. ProxyCheck.io offers 1,000 requests/day on the free tier; upgrade for higher limits.
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
		$provider_manager = new ApiProviderManager();
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
							<?php if ( method_exists( $provider, 'get_signup_url' ) ) : ?>
								<a href="<?php echo esc_url( $provider->get_signup_url() ); ?>" target="_blank" class="button-link" style="margin-left: 10px; font-weight: normal;">
									Get API Key
								</a>
							<?php endif; ?>
						</label>
						<input type="password" 
							   name="<?php echo esc_attr( $field ); ?>[<?php echo esc_attr( $slug ); ?>]" 
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
							| <a href="#" class="api-key-validate-link" data-provider="<?php echo esc_attr( $slug ); ?>">Validate</a>
							<span id="status-<?php echo esc_attr( $slug ); ?>" style="margin-left:8px;"></span>
						</p>
					</div>
				<?php endif; ?>
			<?php endforeach; ?>
			<div id="no-api-key-needed" style="<?php echo $provider_mode !== 'free' ? 'display: none;' : ''; ?>">
				<p style="color: #46b450; font-weight: 600;">✓ No API keys required for free providers!</p>
			<p style="color: #0073aa; font-size: 13px; margin-top: 10px;">
				<strong>Note:</strong> The free endpoint is limited to 45 unique requests per minute. This is suitable for most small to medium websites. 
				For high-traffic sites, consider using a paid provider for unlimited requests.
			</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Validate core settings
	 *
	 * @param array $input Input values to validate.
	 * @return array Validated values.
	 * @since 1.1.0
	 */
	public function validate_core_settings( $input ): array {
		$sanitized = $this->sanitize_core_settings( $input );
		
		// Add admin notice to confirm saving
		add_settings_error(
			'accessdefender_core_settings',
			'settings_saved',
			'Core settings saved successfully!',
			'success'
		);
		
		return $sanitized;
	}

	/**
	 * Validate provider settings
	 *
	 * @param array $input Input values to validate.
	 * @return array Validated values.
	 * @since 1.1.0
	 */
	public function validate_provider_settings( $input ): array {
		return $this->sanitize_provider_settings( $input );
	}

	/**
	 * Sanitize core settings
	 *
	 * @param array $input Input values to sanitize.
	 * @return array Sanitized values.
	 * @since 1.1.0
	 */
	public function sanitize_core_settings( $input ): array {
		// Get existing settings to preserve values not in current form submission
		$existing = get_option( 'accessdefender_core_settings', array() );
		$sanitized = $existing;

		// Handle checkbox fields - if not present in input, it means unchecked
		$sanitized['enable_vpn_blocking'] = isset( $input['enable_vpn_blocking'] ) ? '1' : '0';

		if ( isset( $input['warning_title'] ) ) {
			$sanitized['warning_title'] = sanitize_text_field( $input['warning_title'] );
		}

		if ( isset( $input['warning_message'] ) ) {
			// Use stripslashes to remove any extra backslashes before sanitizing
			$sanitized['warning_message'] = wp_kses_post( stripslashes( $input['warning_message'] ) );
		}

		if ( isset( $input['version'] ) ) {
			$sanitized['version'] = sanitize_text_field( $input['version'] );
		}

		if ( isset( $input['installed_date'] ) ) {
			$sanitized['installed_date'] = sanitize_text_field( $input['installed_date'] );
		}

		return $sanitized;
	}

	/**
	 * Sanitize provider settings
	 *
	 * @param array $input Input values to sanitize.
	 * @return array Sanitized values.
	 * @since 1.1.0
	 */
	public function sanitize_provider_settings( $input ): array {
		$sanitized = array();

		if ( isset( $input['provider_mode'] ) ) {
			$sanitized['provider_mode'] = in_array( $input['provider_mode'], array( 'free', 'paid' ), true )
				? $input['provider_mode'] : 'free';
		}

		if ( isset( $input['free_providers'] ) && is_array( $input['free_providers'] ) ) {
			$valid_providers = array( 'ip-api' );
			$sanitized['free_providers'] = array_filter(
				array_map( 'sanitize_text_field', $input['free_providers'] ),
				function( $provider ) use ( $valid_providers ) {
					return in_array( $provider, $valid_providers, true );
				}
			);
		}

		if ( isset( $input['paid_provider'] ) ) {
			// Allow only active paid providers; others are coming soon
			$valid_paid_providers = array( 'proxycheck', 'ipgeolocation' );
			$sanitized['paid_provider'] = in_array( $input['paid_provider'], $valid_paid_providers, true )
				? $input['paid_provider'] : 'proxycheck';
		}

		if ( isset( $input['primary_provider'] ) ) {
			$sanitized['primary_provider'] = sanitize_text_field( $input['primary_provider'] );
		}

		if ( isset( $input['active_providers'] ) && is_array( $input['active_providers'] ) ) {
			$sanitized['active_providers'] = array_map( 'sanitize_text_field', $input['active_providers'] );
		}

		if ( isset( $input['api_keys'] ) && is_array( $input['api_keys'] ) ) {
			$sanitized['api_keys'] = array();
			foreach ( $input['api_keys'] as $provider => $key ) {
				$sanitized['api_keys'][ sanitize_text_field( $provider ) ] = sanitize_text_field( $key );
			}
		}

		return $sanitized;
	}

}
