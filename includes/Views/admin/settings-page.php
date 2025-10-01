<?php
/**
 * Settings page for Access Defender plugin
 *
 * @package AccessDefender
 * @subpackage Views
 * @since 1.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<div class="access-defender-container">
		<div class="access-defender-card">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			
			<!-- Tab Navigation -->
			<div class="access-defender-tabs">
				<nav class="nav-tab-wrapper">
					<a href="#settings" class="nav-tab nav-tab-active" data-tab="settings">
						<span class="dashicons dashicons-admin-settings"></span>
						<?php esc_html_e( 'Settings', 'access-defender' ); ?>
					</a>
					<a href="#about" class="nav-tab" data-tab="about">
						<span class="dashicons dashicons-info"></span>
						<?php esc_html_e( 'About', 'access-defender' ); ?>
					</a>
				</nav>
			</div>
			
			<!-- Tab Content -->
			<div class="tab-content">
				<!-- Settings Tab -->
				<div id="settings-tab" class="tab-pane active">
					<?php settings_errors(); ?>
					
					<!-- Combined Settings Form -->
					<form method="post" action="">
						<?php wp_nonce_field( 'accessdefender_save_settings', 'accessdefender_nonce' ); ?>
						<input type="hidden" name="action" value="accessdefender_save_settings" />
						
						<?php do_settings_sections( 'access-defender' ); ?>
						
						<h3>Provider Settings</h3>
						<?php do_settings_sections( 'access-defender-providers' ); ?>
						
						<?php submit_button( 'Save Settings', 'primary access-defender-submit', 'submit', true ); ?>
					</form>
				</div>
				
				<!-- About Tab -->
				<div id="about-tab" class="tab-pane">
					<?php require_once ACCESS_DEFENDER_PATH . 'includes/Views/admin/settings-sidebar.php'; ?>
				</div>
			</div>
		</div>
	</div>
</div>
