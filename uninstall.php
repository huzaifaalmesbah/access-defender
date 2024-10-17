<?php
/**
 * Uninstall script for Access Defender plugin.
 *
 * This file is called when the plugin is uninstalled.
 *
 * @package AccessDefender
 */

// If uninstall is not called from WordPress, exit.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Delete the options stored in the database.
delete_option( 'accessdefender_options' );
