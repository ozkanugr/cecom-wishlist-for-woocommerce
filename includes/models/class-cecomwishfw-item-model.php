<?php
/**
 * Item model — data access for wp_cecomwishfw_items.
 *
 * Encapsulates all reads and writes to the items table.
 * Controllers never touch $wpdb directly; they call methods on this class.
 *
 * FK semantics note (ADR-001): WordPress's $wpdb does not support FOREIGN KEY
 * constraints. Cascade deletes are enforced at the application layer:
 *   - Product deletion → fdb-4 hook calls delete_items_for_product()
 *   - User deletion   → fdb-5 hook calls delete_items_for_list() per list
 *
 * Schema creation  (fdb-2): create_table(), up_to_date(), table_name()
 * CRUD operations  (fmd-2): add(), remove(), exists(), get_for_list(), etc.
 *
 * @package Cecomwishfw
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Cecomwishfw_Item_Model
 */
class Cecomwishfw_Item_Model {

	// =========================================================================
	// Schema (fdb-2)
	// =========================================================================

	/**
	 * Create (or upgrade) the wp_cecomwishfw_items table via dbDelta.
	 *
	 * Safe to call multiple times — dbDelta is idempotent for CREATE TABLE.
	 *
	 * The UNIQUE KEY on (list_id, product_id, variation_id) prevents duplicate
	 * items at the DB level. The application layer must handle duplicate-key
	 * errors gracefully (errno 1062).
	 *
	 * No FOREIGN KEY on list_id — enforced by application-layer cascade hooks.
	 *
	 * dbDelta formatting rules:
	 *   - Two spaces after PRIMARY KEY
	 *   - KEY, not INDEX
	 *   - No trailing comma after last key definition
	 *
	 * @return void
	 */
	public static function create_table(): void {
		global $wpdb;

		$table           = $wpdb->prefix . 'cecomwishfw_items';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  list_id bigint(20) unsigned NOT NULL,
  product_id bigint(20) unsigned NOT NULL,
  variation_id bigint(20) unsigned NOT NULL DEFAULT 0,
  price_at_add decimal(10,4) NOT NULL DEFAULT 0.0000,
  sort_order int unsigned NOT NULL DEFAULT 0,
  quantity smallint unsigned NOT NULL DEFAULT 1,
  added_at datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY uniq_list_product_variation (list_id,product_id,variation_id),
  KEY idx_list_id (list_id),
  KEY idx_product_id (product_id),
  KEY idx_added_at (added_at)
) ENGINE=InnoDB {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Check whether the items table schema is current.
	 *
	 * Reuses the same 'cecomwishfw_schema_version' option as the list model
	 * because both tables are created in a single migration run.
	 *
	 * @return bool True when the stored version >= current DB version.
	 */
	public static function up_to_date(): bool {
		return version_compare(
			(string) get_option( 'cecomwishfw_schema_version', '0' ),
			CECOMWISHFW_DB_VERSION,
			'>='
		);
	}

	/**
	 * Return the full table name for this model.
	 *
	 * @return string e.g. 'wp_cecomwishfw_items'
	 */
	public static function table_name(): string {
		global $wpdb;
		return $wpdb->prefix . 'cecomwishfw_items';
	}

	// =========================================================================
	// CRUD operations (fmd-2)
	// =========================================================================

	/**
	 * Add a product to a wishlist.
	 *
	 * Captures price_at_add from the live WC product price at the moment of
	 * adding (stored for the price-change display feature).
	 *
	 * On duplicate (list, product, variation): returns false without a fatal
	 * error (MySQL errno 1062 detected via $wpdb->last_error).
	 *
	 * Actions fired:
	 *   cecomwishfw_before_add_item( $product_id, $variation_id, $list_id, $user_id )
	 *   cecomwishfw_after_add_item(  $item_id, $product_id, $variation_id, $list_id, $user_id )
	 *
	 * @param int $list_id      Target wishlist list ID.
	 * @param int $product_id   WooCommerce product ID.
	 * @param int $variation_id Variation ID; 0 for simple products.
	 * @return int|false Inserted item ID, or false on failure / duplicate.
	 */
	public static function add( int $list_id, int $product_id, int $variation_id = 0 ): int|false {
		global $wpdb;

		$user_id = get_current_user_id();

		// Capture price at add. Use variation price if a variation is selected.
		$price_product_id = $variation_id > 0 ? $variation_id : $product_id;
		$wc_product       = function_exists( 'wc_get_product' ) ? wc_get_product( $price_product_id ) : null;
		$price_at_add     = $wc_product ? (float) $wc_product->get_price() : 0.0;

		do_action( 'cecomwishfw_before_add_item', $product_id, $variation_id, $list_id, $user_id );

		$inserted = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prefix . 'cecomwishfw_items',
			array(
				'list_id'      => $list_id,
				'product_id'   => $product_id,
				'variation_id' => $variation_id,
				'price_at_add' => $price_at_add,
				'added_at'     => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%d', '%f', '%s' )
		);

		if ( ! $inserted ) {
			// Silently absorb duplicate-key errors (errno 1062).
			if ( false !== strpos( (string) $wpdb->last_error, '1062' ) ) {
				return false;
			}
			return false;
		}

		$item_id = (int) $wpdb->insert_id;

		do_action( 'cecomwishfw_after_add_item', $item_id, $product_id, $variation_id, $list_id, $user_id );

		return $item_id;
	}

	/**
	 * Remove a product from a wishlist.
	 *
	 * Idempotent — no error if the item does not exist (result = 0 rows).
	 * Fires cecomwishfw_after_remove_item only when a row was actually removed.
	 *
	 * @param int $list_id      Target wishlist list ID.
	 * @param int $product_id   WooCommerce product ID.
	 * @param int $variation_id Variation ID; 0 for simple products.
	 * @return bool True on success or no-op; false on DB error.
	 */
	public static function remove( int $list_id, int $product_id, int $variation_id = 0 ): bool {
		global $wpdb;

		$result = $wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prefix . 'cecomwishfw_items',
			array(
				'list_id'      => $list_id,
				'product_id'   => $product_id,
				'variation_id' => $variation_id,
			),
			array( '%d', '%d', '%d' )
		);

		if ( false === $result ) {
			return false; // DB error.
		}

		if ( $result > 0 ) {
			do_action( 'cecomwishfw_after_remove_item', $product_id, $variation_id, $list_id, get_current_user_id() );
		}

		return true; // true also for idempotent no-op (0 rows deleted).
	}

	/**
	 * Check whether a (list, product, variation) combination exists.
	 *
	 * Uses a lightweight SELECT id with LIMIT 1 — no JOIN, hits idx_list_id.
	 *
	 * @param int $list_id      Target wishlist list ID.
	 * @param int $product_id   WooCommerce product ID.
	 * @param int $variation_id Variation ID; 0 for simple products.
	 * @return bool
	 */
	public static function exists( int $list_id, int $product_id, int $variation_id = 0 ): bool {
		global $wpdb;

		return (bool) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}cecomwishfw_items
				 WHERE list_id = %d AND product_id = %d AND variation_id = %d
				 LIMIT 1",
				$list_id,
				$product_id,
				$variation_id
			)
		);
	}

	/**
	 * Get all items for a wishlist with WC product data attached.
	 *
	 * Batch-safe: primes the WP object cache with a single get_posts() call
	 * before iterating, so subsequent wc_get_product() calls are cache hits.
	 *
	 * Items where the WC product no longer exists (not yet GC'd after product
	 * deletion) are silently skipped.
	 *
	 * Applies 'cecomwishfw_wishlist_item_data' filter per row.
	 *
	 * @param int $list_id Wishlist list ID.
	 * @return array<object> Enriched item objects with a 'product' property.
	 */
	public static function get_for_list( int $list_id ): array {
		global $wpdb;

		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}cecomwishfw_items
				 WHERE list_id = %d
				 ORDER BY sort_order ASC, added_at ASC",
				$list_id
			)
		);

		if ( empty( $rows ) ) {
			return array();
		}

		// Collect all product / variation IDs.
		$all_ids = array();
		foreach ( $rows as $row ) {
			$all_ids[] = (int) $row->product_id;
			if ( (int) $row->variation_id > 0 ) {
				$all_ids[] = (int) $row->variation_id;
			}
		}
		$all_ids = array_unique( array_filter( $all_ids ) );

		// Batch-prime the WP object cache (one SQL query for all products).
		if ( ! empty( $all_ids ) ) {
			get_posts(
				array(
					'post__in'       => $all_ids,
					'post_type'      => array( 'product', 'product_variation' ),
					'posts_per_page' => -1,
					'post_status'    => 'any',
					'fields'         => 'ids',
				)
			);
		}

		$result = array();

		foreach ( $rows as $row ) {
			// Resolve the WC product (variation if set, otherwise parent).
			$resolve_id = ( (int) $row->variation_id > 0 ) ? (int) $row->variation_id : (int) $row->product_id;
			$product    = function_exists( 'wc_get_product' ) ? wc_get_product( $resolve_id ) : null;

			if ( ! $product ) {
				continue; // Product deleted but not yet GC'd — skip silently.
			}

			$row->product = $product;

			$result[] = apply_filters( 'cecomwishfw_wishlist_item_data', $row, $row, $product );
		}

		return $result;
	}

	/**
	 * Get a single item row by item ID.
	 *
	 * @param int $item_id Wishlist item row ID.
	 * @return object|null
	 */
	public static function get_item( int $item_id ): ?object {
		global $wpdb;

		return $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}cecomwishfw_items WHERE id = %d LIMIT 1",
				$item_id
			)
		) ?? null;
	}

	/**
	 * Get lightweight item data for a list (IDs + product_ids, no WC join).
	 *
	 * Used for merge-on-login and cascade-delete operations where WC product
	 * data is not needed.
	 *
	 * @param int $list_id Wishlist list ID.
	 * @return array<object>
	 */
	public static function find_by_list( int $list_id ): array {
		global $wpdb;

		return $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT id, list_id, product_id, variation_id
				 FROM {$wpdb->prefix}cecomwishfw_items
				 WHERE list_id = %d
				 ORDER BY sort_order ASC, added_at ASC",
				$list_id
			)
		) ?? array();
	}

	/**
	 * Count total items across all lists belonging to a user.
	 *
	 * Used for the header badge counter on logged-in user pages.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return int
	 */
	public static function count_for_user( int $user_id ): int {
		global $wpdb;

		return (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT COUNT(i.id)
				 FROM {$wpdb->prefix}cecomwishfw_items i
				 INNER JOIN {$wpdb->prefix}cecomwishfw_lists l ON i.list_id = l.id
				 WHERE l.user_id = %d",
				$user_id
			)
		);
	}

	/**
	 * Count items in a specific list.
	 *
	 * Used for the guest counter (no user_id available) and list-card display.
	 *
	 * @param int $list_id Wishlist list ID.
	 * @return int
	 */
	public static function count_for_list( int $list_id ): int {
		global $wpdb;

		return (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT COUNT(id) FROM {$wpdb->prefix}cecomwishfw_items WHERE list_id = %d",
				$list_id
			)
		);
	}

	/**
	 * Return all variation IDs that a user has wishlisted for a given product.
	 *
	 * Used by the button renderer to pre-load variation state server-side so no
	 * extra AJAX round-trip is needed when the user selects a swatch on the
	 * product page. Returns an empty array for guests or when nothing is saved.
	 *
	 * @param int $user_id    Logged-in user ID.
	 * @param int $product_id Parent product ID.
	 * @return int[] List of variation_id values (may include 0 for un-varied entries).
	 */
	public static function get_wishlisted_variation_ids_for_user( int $user_id, int $product_id ): array {
		global $wpdb;

		$rows = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT i.variation_id
				 FROM {$wpdb->prefix}cecomwishfw_items i
				 INNER JOIN {$wpdb->prefix}cecomwishfw_lists l ON i.list_id = l.id
				 WHERE l.user_id = %d AND i.product_id = %d",
				$user_id,
				$product_id
			)
		);

		return array_map( 'intval', $rows ?? array() );
	}

	/**
	 * Return all variation IDs wishlisted for a given product inside a specific list.
	 *
	 * Guest-session equivalent of get_wishlisted_variation_ids_for_user().
	 *
	 * @param int $list_id    Wishlist list ID.
	 * @param int $product_id Parent product ID.
	 * @return int[]
	 */
	public static function get_wishlisted_variation_ids_for_list( int $list_id, int $product_id ): array {
		global $wpdb;

		$rows = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT variation_id
				 FROM {$wpdb->prefix}cecomwishfw_items
				 WHERE list_id = %d AND product_id = %d",
				$list_id,
				$product_id
			)
		);

		return array_map( 'intval', $rows ?? array() );
	}

	/**
	 * Check whether a product is in any of a user's wishlists.
	 *
	 * Used by the Add-to-Wishlist toggle button to determine the "in wishlist"
	 * state across all of the user's lists (not just the default).
	 *
	 * Uses LIMIT 1 — hits the composite UNIQUE index.
	 *
	 * @param int $user_id      WordPress user ID.
	 * @param int $product_id   WooCommerce product ID.
	 * @param int $variation_id Variation ID; 0 for simple products.
	 * @return bool
	 */
	public static function is_product_in_any_user_list( int $user_id, int $product_id, int $variation_id = 0 ): bool {
		global $wpdb;

		return (bool) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT i.id
				 FROM {$wpdb->prefix}cecomwishfw_items i
				 INNER JOIN {$wpdb->prefix}cecomwishfw_lists l ON i.list_id = l.id
				 WHERE l.user_id = %d AND i.product_id = %d AND i.variation_id = %d
				 LIMIT 1",
				$user_id,
				$product_id,
				$variation_id
			)
		);
	}

	/**
	 * Count all items across every wishlist (for Dashboard stats).
	 *
	 * @return int
	 */
	public static function count_all(): int {
		global $wpdb;

		return (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			"SELECT COUNT(*) FROM {$wpdb->prefix}cecomwishfw_items"
		);
	}

	/**
	 * Get the most-wishlisted products, ordered by number of distinct lists.
	 *
	 * Used for the admin Dashboard "Top Popular Products" table (fad-5).
	 *
	 * @param int $limit Maximum number of results. Minimum 1.
	 * @return array<int, object{product_id: string, wish_count: string}>
	 */
	public static function get_popular_products( int $limit = 5 ): array {
		global $wpdb;

		return (array) $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT product_id, COUNT(DISTINCT list_id) AS wish_count
				 FROM {$wpdb->prefix}cecomwishfw_items
				 GROUP BY product_id
				 ORDER BY wish_count DESC
				 LIMIT %d",
				max( 1, $limit )
			)
		);
	}

	/**
	 * Remove all items belonging to a specific product from every list.
	 *
	 * Called by the product-deletion hook (fdb-4).
	 *
	 * @todo fmd-2.10 (also referenced as fdb-4)
	 *
	 * @param int $product_id WooCommerce product ID.
	 * @return void
	 */
	public static function delete_items_for_product( int $product_id ): void {
		// Guard: only process WooCommerce product post types.
		// wp_trash_post and before_delete_post fire for all post types,
		// so this check prevents unnecessary DB writes on pages, posts, etc.
		if ( 'product' !== get_post_type( $product_id ) ) {
			return;
		}

		global $wpdb;

		$wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prefix . 'cecomwishfw_items',
			array( 'product_id' => $product_id ),
			array( '%d' )
		);
	}

	/**
	 * Remove all items belonging to a specific list.
	 *
	 * Called before deleting the parent list row (e.g., user deletion fdb-5,
	 * or premium list-delete endpoint pml-1.4).
	 *
	 * @todo fmd-2.11
	 *
	 * @param int $list_id Wishlist list ID.
	 * @return void
	 */
	public static function delete_items_for_list( int $list_id ): void {
		global $wpdb;

		$wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prefix . 'cecomwishfw_items',
			array( 'list_id' => $list_id ),
			array( '%d' )
		);
	}
}
