<?php
/**
 * Fired during plugin deactivation.
 *
 * Clears scheduled events and flushes rewrite rules.
 * Does NOT delete any data — that is handled by uninstall.php.
 *
 * @package Cecomwishfw
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Cecomwishfw_Deactivator
 */
class Cecomwishfw_Deactivator {

	/**
	 * Plugin deactivation entry point.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		self::clear_cron();
		self::unregister_cache_compatibility();
		delete_transient( 'cecomwishfw_tables_ok' );
		flush_rewrite_rules();
	}

	/**
	 * Purge all installed cache plugins on deactivation and remove runtime
	 * URI-exclusion filter callbacks before flush_rewrite_rules() fires.
	 *
	 * deregister_uri_filters() is called first so that WP Rocket and W3 Total
	 * Cache — which hook into the flush_rules action and regenerate their
	 * .htaccess rules when flush_rewrite_rules() runs — cannot invoke
	 * get_wishlist_page_uri() and persist the wishlist page URI during
	 * deactivation. Silently no-ops when no cache plugin is installed.
	 *
	 * @return void
	 */
	private static function unregister_cache_compatibility(): void {
		require_once CECOMWISHFW_PLUGIN_DIR . 'includes/class-cecomwishfw-cache-compatibility.php';
		if ( class_exists( 'Cecomwishfw_Cache_Compatibility' ) ) {
			Cecomwishfw_Cache_Compatibility::deregister_uri_filters();
			Cecomwishfw_Cache_Compatibility::unregister_from_active_caches();
		}
	}

	/**
	 * Unschedule all plugin cron events.
	 *
	 * @return void
	 */
	private static function clear_cron(): void {
		$timestamp = wp_next_scheduled( 'cecomwishfw_gc_guests' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'cecomwishfw_gc_guests' );
		}
	}
}
