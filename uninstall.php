<?php
/**
 * Plugin uninstall handler.
 *
 * Fires when the plugin is deleted from the WordPress Plugins screen.
 * Removes all plugin data only when the admin has opted in
 * via the "Delete all data on uninstall" setting.
 *
 * @package Cecomwishfw
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$cecomwishfw_settings      = get_option( 'cecomwishfw_settings', array() );
$cecomwishfw_should_delete = (bool) ( $cecomwishfw_settings['general']['delete_on_uninstall'] ?? true );

if ( ! $cecomwishfw_should_delete ) {
	return;
}

global $wpdb;

// Drop custom tables (order matters — items first, then lists).
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}cecomwishfw_items" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}cecomwishfw_lists" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

// Delete options.
delete_option( 'cecomwishfw_settings' );
delete_option( 'cecomwishfw_wishlist_page_id' );
delete_option( 'cecomwishfw_db_version' );
delete_option( 'cecomwishfw_schema_version' );

// Delete transients.
delete_transient( 'cecomwishfw_popular_cache' );

// Unpublish and trash the auto-created wishlist page.
$cecomwishfw_page_id = (int) get_option( 'cecomwishfw_wishlist_page_id' );
if ( $cecomwishfw_page_id > 0 ) {
	wp_delete_post( $cecomwishfw_page_id, true );
}
