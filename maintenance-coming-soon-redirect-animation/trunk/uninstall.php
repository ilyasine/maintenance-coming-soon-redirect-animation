<?php
// Exit if uninstall constant is not defined
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete the plugin options
delete_option('wploti_maintenance_redirect_version');
delete_option('wploti_activation_notice');
delete_option('wploti_animation');
delete_option('wploti_status');
delete_option('wploti_notes_notice');
delete_option('wploti_message');
delete_option('wploti_header_type');
delete_option('wploti_whitelisted_roles');
delete_option('wploti_whitelisted_users');

// Delete tables using proper WordPress methods
global $wpdb;

// Use the proper table names with prefixes
$table_ips = $wpdb->prefix . 'wploti_mr_unrestricted_ips';
$table_keys = $wpdb->prefix . 'wploti_mr_access_keys';

// Check if the tables exist before attempting to drop them
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_ips))) {
    $wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %s", $table_ips));
}

if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_keys))) {
    $wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS %s", $table_keys));
}
// phpcs:enable

// Clear any related cache
wp_cache_flush();