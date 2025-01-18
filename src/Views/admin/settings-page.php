<?php
/**
 * Settings page for Access Defender plugin
 *
 * This file contains the markup for the settings page of the Access Defender plugin.
 *
 * @package AccessDefender
 * @subpackage Views
 * @since 1.0.1
 */

?>
<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<form method="post" action="options.php">
		<?php
		settings_fields( 'accessdefender_options' );
		do_settings_sections( 'access-defender' );
		submit_button();
		?>
	</form>
</div>

