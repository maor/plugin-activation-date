<?php
// If uninstall was not called from WordPress, exit!
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit;

// Cleaning up
delete_option( 'pad_activated_plugins' );
delete_option( 'pad_display_relative_date' );