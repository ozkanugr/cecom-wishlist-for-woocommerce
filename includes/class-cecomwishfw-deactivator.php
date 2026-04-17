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
	 * Reverse of Cecomwishfw_Activator::register_cache_compatibility() —
	 * remove the wishlist session cookie from every installed caching
	 * plugin's persistent exclusion list, so deactivation leaves each cache
	 * plugin in its prior state. Silently no-ops when no cache plugin is
	 * installed.
	 *
	 * @return void
	 */
	private static function unregister_cache_compatibility(): void {
		require_once CECOMWISHFW_PLUGIN_DIR . 'includes/class-cecomwishfw-cache-compatibility.php';
		if ( class_exists( 'Cecomwishfw_Cache_Compatibility' ) ) {
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
