<?php

global $wpdb;

// exit if uninstall constant is not defined
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	
	exit;
	
}


// delete the plugin options

	delete_option( 'wploti_maintenance_redirect_version' );
	delete_option( 'wploti_activation_notice' );
	delete_option( 'wploti_animation' );
	delete_option( 'wploti_status');
	delete_option( 'wploti_notes_notice');
	delete_option( 'wploti_message');
	delete_option( 'wploti_header_type');



// delete tables from database

	$wpdb->query( " DROP TABLE wp_wploti_mr_unrestricted_ips " );
	$wpdb->query( " DROP TABLE wp_wploti_mr_access_keys " );
	




