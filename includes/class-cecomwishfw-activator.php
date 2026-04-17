<?php
/**
 * Fired during plugin activation.
 *
 * Creates database tables, sets default options, auto-creates the wishlist
 * page, and schedules WP-Cron events.
 *
 * @package Cecomwishfw
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Cecomwishfw_Activator
 */
class Cecomwishfw_Activator {

	/**
	 * Plugin activation entry point.
	 *
	 * Called via register_activation_hook(). Runs database setup,
	 * creates the auto-page, sets defaults, and schedules cron.
	 *
	 * @return void
	 */
	public static function activate(): void {
		self::maybe_upgrade_schema();
		$wishlist_page_id = self::create_wishlist_page();
		self::set_default_options( $wishlist_page_id );
		self::schedule_cron();
		self::register_cache_compatibility();
		flush_rewrite_rules();
	}

	/**
	 * Register the wishlist session cookie with every installed caching
	 * plugin so full-page caches don't serve stale pages to visitors carrying
	 * a wishlist. Silently no-ops when no cache plugin is installed.
	 *
	 * The activator runs in a minimal bootstrap (register_activation_hook
	 * fires before the normal plugin load path), so we require the class
	 * file defensively here instead of relying on the loader.
	 *
	 * @return void
	 */
	private static function register_cache_compatibility(): void {
		require_once CECOMWISHFW_PLUGIN_DIR . 'includes/class-cecomwishfw-cache-compatibility.php';
		if ( class_exists( 'Cecomwishfw_Cache_Compatibility' ) ) {
			Cecomwishfw_Cache_Compatibility::register_with_active_caches();
		}
	}

	/**
	 * Run DB schema creation/upgrades if needed.
	 *
	 * Guards on TWO conditions — both must be true to skip:
	 *   1. The stored DB version is current.
	 *   2. Both custom tables physically exist in the database.
	 *
	 * Checking actual table existence (via a transient-cached SHOW TABLES query)
	 * self-heals the common staging-server scenario where a DB snapshot is
	 * restored that contains the version option but not the custom tables.
	 * The transient caps the SHOW TABLES overhead to one check per hour.
	 *
	 * @return void
	 */
	public static function maybe_upgrade_schema(): void {
		global $wpdb;

		$installed  = get_option( 'cecomwishfw_db_version', '0' );
		$is_current = version_compare( $installed, CECOMWISHFW_DB_VERSION, '>=' );

		if ( $is_current ) {
			// Use a transient to cache the table-existence result for 1 hour so
			// we avoid a SHOW TABLES pair on every single request.
			$cache_key = 'cecomwishfw_tables_ok';
			$tables_ok = get_transient( $cache_key );

			if ( 'yes' !== $tables_ok ) {
				// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$lists_exist = (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . 'cecomwishfw_lists' ) );
				$items_exist = (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . 'cecomwishfw_items' ) );
				// phpcs:enable

				if ( $lists_exist && $items_exist ) {
					set_transient( $cache_key, 'yes', HOUR_IN_SECONDS );
					return;
				}

				// Tables are missing despite the version option being current.
				// Fall through to recreate them and reset the transient below.
				delete_transient( $cache_key );
			} else {
				return; // Version current + tables confirmed present — nothing to do.
			}
		}

		require_once CECOMWISHFW_PLUGIN_DIR . 'includes/models/class-cecomwishfw-list-model.php';
		Cecomwishfw_List_Model::create_table();

		require_once CECOMWISHFW_PLUGIN_DIR . 'includes/models/class-cecomwishfw-item-model.php';
		Cecomwishfw_Item_Model::create_table();

		// Verify creation actually succeeded (dbDelta is silent on failure).
		// Only mark schema as current when both tables physically exist, so
		// a failed dbDelta retries on the next request rather than silently
		// leaving the plugin broken.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$lists_ok = (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . 'cecomwishfw_lists' ) );
		$items_ok = (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . 'cecomwishfw_items' ) );
		// phpcs:enable

		if ( $lists_ok && $items_ok ) {
			update_option( 'cecomwishfw_db_version', CECOMWISHFW_DB_VERSION );
			set_transient( 'cecomwishfw_tables_ok', 'yes', HOUR_IN_SECONDS );
		}
		// If tables still do not exist (persistent DB permission issue), leave
		// the version option unchanged so the next request retries creation.
	}

	/**
	 * Create the auto-generated wishlist page if it does not yet exist.
	 *
	 * Stores the page ID in the standalone 'cecomwishfw_wishlist_page_id'
	 * bookkeeping option (used by the re-activation guard and uninstall
	 * cleanup) and returns it so set_default_options() can wire it into the
	 * user-facing `cecomwishfw_settings.general.wishlist_page_id` setting.
	 *
	 * @return int The wishlist page ID, or 0 if creation failed.
	 */
	private static function create_wishlist_page(): int {
		$page_id = (int) get_option( 'cecomwishfw_wishlist_page_id', 0 );

		if ( $page_id > 0 && 'publish' === get_post_status( $page_id ) ) {
			return $page_id;
		}

		$new_id = wp_insert_post(
			array(
				'post_title'   => __( 'My Wishlist', 'cecom-wishlist-for-woocommerce' ),
				'post_content' => '[cecomwishfw_wishlist]',
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_author'  => get_current_user_id() ? get_current_user_id() : 1,
			)
		);

		if ( $new_id && ! is_wp_error( $new_id ) ) {
			update_option( 'cecomwishfw_wishlist_page_id', (int) $new_id );
			return (int) $new_id;
		}

		return 0;
	}

	/**
	 * Populate default plugin settings (does not overwrite existing values).
	 *
	 * On a fresh install, seeds `cecomwishfw_settings` with the defaults array,
	 * including the just-created wishlist page ID so the "Pages → Wishlist page"
	 * dropdown in the General tab is pre-selected. On re-activation of an
	 * already-configured site, the existing settings are preserved — except we
	 * self-heal `general.wishlist_page_id` when it is still 0 but a real
	 * wishlist page exists (fixes sites that were activated before this wiring
	 * was in place).
	 *
	 * @param int $wishlist_page_id Page ID returned by create_wishlist_page(). 0 = no page available.
	 * @return void
	 */
	private static function set_default_options( int $wishlist_page_id = 0 ): void {
		$defaults = array(
			'general'    => array(
				'show_on_single'          => true,
				'button_style'            => 'icon_text',
				'button_position'         => 'after_cart',
				'loop_button_style'       => 'icon_only',
				'loop_button_position'    => 'after_add_to_cart',
				'show_on_loop'            => true,
				'remove_on_cart'          => true,
				'redirect_checkout'       => true,
				'product_types'           => array( 'simple', 'variable', 'grouped', 'external' ),
				'show_out_of_stock'       => true,
				'registered_only'         => false,
				'delete_on_uninstall'     => true,
				'wishlist_page_id'        => $wishlist_page_id,
				'table_show_variations'   => true,
				'table_show_price'        => true,
				'table_show_stock'        => true,
				'table_show_date'         => true,
				'table_show_add_to_cart'  => true,
				'table_show_remove_left'  => true,
				'table_show_remove_right' => true,
				'share_enabled'           => true,
				'share_facebook'          => true,
				'share_twitter'           => true,
				'share_pinterest'         => true,
				'share_email'             => true,
				'share_whatsapp'          => true,
				'share_telegram'          => true,
				'share_url'               => true,
			),
			'appearance' => array(
				'single_add_label'                   => '',
				'single_remove_label'                => '',
				'single_button_color'                => '',
				'single_icon_class'                  => '',
				'single_padding'                     => '',
				'single_margin'                      => '',
				'single_font_size'                   => '',
				// Single — additional appearance (empty = default plugin styling).
				'single_appearance_type'             => '',
				'single_custom_bg'                   => '',
				'single_custom_bg_opacity'           => '',
				'single_custom_text'                 => '',
				'single_custom_text_opacity'         => '',
				'single_custom_border'               => '',
				'single_custom_border_opacity'       => '',
				'single_custom_bg_hover'             => '',
				'single_custom_bg_hover_opacity'     => '',
				'single_custom_text_hover'           => '',
				'single_custom_text_hover_opacity'   => '',
				'single_custom_border_hover'         => '',
				'single_custom_border_hover_opacity' => '',
				'single_custom_radius'               => '',
				'single_custom_border_width'         => '',
				'loop_add_label'                     => '',
				'loop_remove_label'                  => '',
				'loop_button_color'                  => '',
				'loop_icon_class'                    => '',
				'loop_padding'                       => '',
				'loop_margin'                        => '',
				'loop_font_size'                     => '',
				// Loop — additional appearance (empty = default plugin styling).
				'loop_appearance_type'               => '',
				'loop_custom_bg'                     => '',
				'loop_custom_bg_opacity'             => '',
				'loop_custom_text'                   => '',
				'loop_custom_text_opacity'           => '',
				'loop_custom_border'                 => '',
				'loop_custom_border_opacity'         => '',
				'loop_custom_bg_hover'               => '',
				'loop_custom_bg_hover_opacity'       => '',
				'loop_custom_text_hover'             => '',
				'loop_custom_text_hover_opacity'     => '',
				'loop_custom_border_hover'           => '',
				'loop_custom_border_hover_opacity'   => '',
				'loop_custom_radius'                 => '',
				'loop_custom_border_width'           => '',
				'show_counter'                       => true,
				'counter_show_icon'                  => true,
				'counter_link'                       => true,
				'counter_icon_class'                 => '',
				'counter_show_zero'                  => true,
				'custom_css'                         => '',
			),
			'dashboard'  => array(),
		);

		if ( false === get_option( 'cecomwishfw_settings' ) ) {
			add_option( 'cecomwishfw_settings', $defaults );
			return;
		}

		// Self-heal: existing install was seeded by an earlier plugin version
		// that forgot to wire the created page ID into this setting. If the
		// setting is still 0 (unset) and we have a real page, update just that
		// key — never overwrite a page ID the user explicitly chose.
		if ( $wishlist_page_id > 0 ) {
			$existing = get_option( 'cecomwishfw_settings', array() );
			if ( is_array( $existing ) ) {
				$general = isset( $existing['general'] ) && is_array( $existing['general'] )
					? $existing['general']
					: array();
				if ( empty( $general['wishlist_page_id'] ) ) {
					$general['wishlist_page_id'] = $wishlist_page_id;
					$existing['general']         = $general;
					update_option( 'cecomwishfw_settings', $existing );
				}
			}
		}
	}

	/**
	 * Schedule WP-Cron events used by the plugin.
	 *
	 * @return void
	 */
	private static function schedule_cron(): void {
		if ( ! wp_next_scheduled( 'cecomwishfw_gc_guests' ) ) {
			wp_schedule_event( time(), 'daily', 'cecomwishfw_gc_guests' );
		}
	}
}
