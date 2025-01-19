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
		<div class="access-defender-main">
			<div class="access-defender-card">
				<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
				<form method="post" action="options.php">
					<?php
					settings_fields( 'accessdefender_options' );
					do_settings_sections( 'access-defender' );
					submit_button( 'Save Changes', 'primary access-defender-submit', 'submit', true );
					?>
				</form>
			</div>
		</div>
		
		<div class="access-defender-sidebar">
			<?php require_once ACCESS_DEFENDER_PATH . 'includes/Views/admin/settings-sidebar.php'; ?>
		</div>
	</div>
</div>
