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
	 * Register cache-exclusion rules, rebuild cache-plugin rule files, and
	 * purge stored pages immediately after activation.
	 *
	 * register_with_active_caches() manually registers filter_reject_wishlist_uri
	 * on the WP Rocket and W3 Total Cache filter hooks (plugins_loaded has not
	 * fired for this plugin yet), then calls rocket_generate_config_file() and
	 * flush_rocket_htaccess() so WP Rocket's server-level rule files contain the
	 * wishlist URI exclusion from the very first request. The flush_rewrite_rules()
	 * call below causes W3 Total Cache to regenerate its .htaccess with the same
	 * exclusion. Finally, all stored page-cache files are purged.
	 *
	 * The activator runs in a minimal bootstrap (register_activation_hook
	 * fires before the normal plugin load path), so we require the class
	 * file defensively here instead of relying on the loader.
	 *
	 * Silently no-ops when no supported cache plugin is active.
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

		$installed  = get_option( 'cecomwishfw_db_version', '0' );
		$is_current = version_compare( $installed, CECOMWISHFW_DB_VERSION, '>=' );

		if ( $is_current ) {
			// Use a transient to cache the table-existence result for 1 hour so
			// we avoid SHOW TABLES queries on every single request.
			$cache_key = 'cecomwishfw_tables_ok';
			$tables_ok = get_transient( $cache_key );

			if ( 'yes' !== $tables_ok ) {
				if ( self::all_tables_present() ) {
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
		self::migrate_items_schema();

		// Verify creation actually succeeded (dbDelta is silent on failure).
		// Only mark schema as current when both tables physically exist, so
		// a failed dbDelta retries on the next request rather than silently
		// leaving the plugin broken.
		if ( self::all_tables_present() ) {
			update_option( 'cecomwishfw_db_version', CECOMWISHFW_DB_VERSION );
			set_transient( 'cecomwishfw_tables_ok', 'yes', HOUR_IN_SECONDS );
		}
		// If tables still do not exist (persistent DB permission issue), leave
		// the version option unchanged so the next request retries creation.
	}

	/**
	 * Idempotently add price_meta and quantity_meta columns + composite index.
	 *
	 * Uses SHOW COLUMNS guard so safe to call on every upgrade.
	 *
	 * @return void
	 */
	private static function migrate_items_schema(): void {
		global $wpdb;
		$table = $wpdb->prefix . 'cecomwishfw_items';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$cols = array_column( (array) $wpdb->get_results( $wpdb->prepare( 'SHOW COLUMNS FROM %i', $table ) ), 'Field' );
		if ( ! in_array( 'price_meta', $cols, true ) ) {
			$wpdb->query( $wpdb->prepare( 'ALTER TABLE %i ADD COLUMN price_meta mediumtext DEFAULT NULL AFTER price_at_add', $table ) );
		}
		if ( ! in_array( 'quantity_meta', $cols, true ) ) {
			$wpdb->query( $wpdb->prepare( 'ALTER TABLE %i ADD COLUMN quantity_meta mediumtext DEFAULT NULL AFTER price_meta', $table ) );
		}
		$idx = array_column( (array) $wpdb->get_results( $wpdb->prepare( 'SHOW INDEX FROM %i', $table ) ), 'Key_name' );
		if ( ! in_array( 'idx_product_id_added_at', $idx, true ) ) {
			$wpdb->query( $wpdb->prepare( 'ALTER TABLE %i ADD INDEX idx_product_id_added_at (product_id, added_at)', $table ) );
		}
		// phpcs:enable
	}

	/**
	 * Check whether both custom tables physically exist in the database.
	 *
	 * @return bool True when both tables are present; false on first missing table.
	 */
	private static function all_tables_present(): bool {
		global $wpdb;

		foreach ( array( 'lists', 'items' ) as $suffix ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			if ( ! $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . 'cecomwishfw_' . $suffix ) ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Create the auto-generated wishlist page if it does not yet exist.
	 *
	 * Stores the page ID in the standalone 'cecomwishfw_wishlist_page_id'
	 * bookkeeping option (used by the re-activation guard and uninstall
	 * cleanup) and returns it so set_default_options() can wire it into the
	 * user-facing `cecomwishfw_settings.general.wishlist_page_id` setting.
	 *
	 * Before inserting a new page, the method checks whether a page with the
	 * canonical slug 'my-wishlist' already exists. Reusing the existing page
	 * prevents WordPress from appending a suffix (e.g. 'my-wishlist-2') when
	 * wp_insert_post() encounters a slug collision.
	 *
	 * @return int The wishlist page ID, or 0 if creation failed.
	 */
	private static function create_wishlist_page(): int {
		$page_id = (int) get_option( 'cecomwishfw_wishlist_page_id', 0 );

		if ( $page_id > 0 && 'publish' === get_post_status( $page_id ) ) {
			self::maybe_fix_wishlist_shortcode( $page_id );
			return $page_id;
		}

		// Stored ID is stale or missing — look for an existing page with the
		// canonical slug before inserting a new one. Without this check,
		// wp_insert_post() would create 'my-wishlist-2' if 'my-wishlist' is taken.
		$existing = get_page_by_path( 'my-wishlist', OBJECT, 'page' );
		if ( $existing instanceof WP_Post ) {
			$existing_id = (int) $existing->ID;
			if ( 'publish' !== $existing->post_status ) {
				wp_update_post( array( 'ID' => $existing_id, 'post_status' => 'publish' ) );
			}
			update_option( 'cecomwishfw_wishlist_page_id', $existing_id );
			self::maybe_fix_wishlist_shortcode( $existing_id );
			return $existing_id;
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
	 * Ensure the wishlist page contains exactly one [cecomwishfw_wishlist] shortcode.
	 *
	 * When the shortcode is absent, the page content is replaced with it.
	 * When duplicates are present, the content is normalized to a single
	 * shortcode. No database write is performed when the count is already 1.
	 *
	 * @param int $page_id WordPress page ID.
	 * @return void
	 */
	private static function maybe_fix_wishlist_shortcode( int $page_id ): void {
		$post = get_post( $page_id );
		if ( ! $post instanceof WP_Post ) {
			return;
		}

		$shortcode = '[cecomwishfw_wishlist]';
		$count     = substr_count( $post->post_content, $shortcode );

		if ( 1 === $count ) {
			return;
		}

		wp_update_post(
			array(
				'ID'           => $page_id,
				'post_content' => $shortcode,
			)
		);
	}

	/**
	 * Populate default plugin settings (does not overwrite existing values).
	 *
	 * On a fresh install, seeds `cecomwishfw_settings` with the schema defaults
	 * from Cecomwishfw_Settings, including the just-created wishlist page ID so
	 * the "Pages → Wishlist page" dropdown in the General tab is pre-selected.
	 * On re-activation of an already-configured site, the existing settings are
	 * preserved — except we self-heal `general.wishlist_page_id` when it is
	 * still 0 but a real wishlist page exists.
	 *
	 * Delegates to Cecomwishfw_Settings::get_defaults() so the seeded values
	 * are always identical to what reset_all() would write, eliminating the risk
	 * of the activator and the model drifting out of sync.
	 *
	 * @param int $wishlist_page_id Page ID returned by create_wishlist_page(). 0 = no page available.
	 * @return void
	 */
	private static function set_default_options( int $wishlist_page_id = 0 ): void {
		require_once CECOMWISHFW_PLUGIN_DIR . 'includes/models/class-cecomwishfw-settings.php';

		$defaults = Cecomwishfw_Settings::get_defaults();

		// Override the schema default (0) with the page created during activation.
		if ( $wishlist_page_id > 0 ) {
			$defaults['general']['wishlist_page_id'] = $wishlist_page_id;
		}

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
