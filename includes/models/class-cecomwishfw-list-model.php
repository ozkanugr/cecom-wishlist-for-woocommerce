<?php
/**
 * List model — data access for wp_cecomwishfw_lists.
 *
 * Encapsulates all reads and writes to the lists table.
 * Controllers never touch $wpdb directly; they call methods on this class.
 *
 * Schema creation (fdb-1):  create_table(), up_to_date()
 * CRUD operations  (fmd-1):  create(), get(), update(), delete(), find_by_*()
 *
 * @package Cecomwishfw
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Cecomwishfw_List_Model
 */
class Cecomwishfw_List_Model {

	// =========================================================================
	// Schema (fdb-1)
	// =========================================================================

	/**
	 * Create (or upgrade) the wp_cecomwishfw_lists table via dbDelta.
	 *
	 * Safe to call multiple times — dbDelta is idempotent for CREATE TABLE.
	 * Updates the 'cecomwishfw_schema_version' option on success.
	 *
	 * dbDelta formatting rules applied:
	 *   - Two spaces after PRIMARY KEY
	 *   - KEY, not INDEX
	 *   - No trailing comma after last key definition
	 *
	 * @return void
	 */
	public static function create_table(): void {
		global $wpdb;

		$table           = $wpdb->prefix . 'cecomwishfw_lists';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  user_id bigint(20) unsigned NULL DEFAULT NULL,
  session_id varchar(64) NULL DEFAULT NULL,
  name varchar(100) NOT NULL DEFAULT 'My Wishlist',
  privacy enum('public','private','shared') NOT NULL DEFAULT 'private',
  share_token varchar(64) NOT NULL DEFAULT '',
  is_default tinyint(1) NOT NULL DEFAULT 0,
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY uniq_share_token (share_token),
  KEY idx_user_id (user_id),
  KEY idx_session_id (session_id),
  KEY idx_user_default (user_id,is_default)
) ENGINE=InnoDB {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( 'cecomwishfw_schema_version', CECOMWISHFW_DB_VERSION );
	}

	/**
	 * Check whether the lists table schema is current.
	 *
	 * Compares the stored schema version against the plugin constant.
	 * If false, call create_table() to upgrade.
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
	 * @return string e.g. 'wp_cecomwishfw_lists'
	 */
	public static function table_name(): string {
		global $wpdb;
		return $wpdb->prefix . 'cecomwishfw_lists';
	}

	// =========================================================================
	// CRUD operations (fmd-1)
	// =========================================================================

	/**
	 * Validate a privacy string against the allowed enum values.
	 *
	 * @param string $value Privacy string to validate.
	 * @return bool
	 */
	private static function valid_privacy( string $value ): bool {
		return in_array( $value, array( 'public', 'private', 'shared' ), true );
	}

	/**
	 * Create a new wishlist row.
	 *
	 * Auto-sets share_token, created_at, updated_at.
	 * Sets is_default = 1 when this is the user's first list.
	 * For guest lists (session_id only), always is_default = 1.
	 *
	 * @param array $data Creation data (user_id, session_id, name, privacy keys).
	 * @return int|false Inserted row ID, or false on failure.
	 */
	public static function create( array $data ): int|false {
		global $wpdb;

		$user_id    = isset( $data['user_id'] ) ? (int) $data['user_id'] : 0;
		$session_id = isset( $data['session_id'] ) ? sanitize_text_field( $data['session_id'] ) : '';

		if ( 0 === $user_id && '' === $session_id ) {
			return false;
		}

		$name    = sanitize_text_field( $data['name'] ?? __( 'My Wishlist', 'cecom-wishlist-for-woocommerce' ) );
		$privacy = ( isset( $data['privacy'] ) && self::valid_privacy( $data['privacy'] ) )
			? $data['privacy']
			: 'private';

		// Determine is_default: first list for a user, or any guest list.
		$is_default = 0;
		if ( $user_id > 0 ) {
			$has_default = self::get_default_for_user( $user_id );
			$is_default  = ( null === $has_default ) ? 1 : 0;
		} else {
			$is_default = 1; // Guests always have one list.
		}

		$now         = current_time( 'mysql' );
		$share_token = bin2hex( random_bytes( 32 ) );

		$row = array(
			'name'        => $name,
			'privacy'     => $privacy,
			'share_token' => $share_token,
			'is_default'  => $is_default,
			'created_at'  => $now,
			'updated_at'  => $now,
		);
		$fmt = array( '%s', '%s', '%s', '%d', '%s', '%s' );

		if ( $user_id > 0 ) {
			$row['user_id'] = $user_id;
			$fmt[]          = '%d';
		} else {
			$row['session_id'] = $session_id;
			$fmt[]             = '%s';
		}

		$inserted = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prefix . 'cecomwishfw_lists',
			$row,
			$fmt
		);

		if ( ! $inserted ) {
			return false;
		}

		$id = (int) $wpdb->insert_id;

		do_action( 'cecomwishfw_list_created', $id, ( 0 !== $user_id ? $user_id : null ), $name );

		return $id;
	}

	/**
	 * Get a single list row by ID.
	 *
	 * @param int $list_id List row ID.
	 * @return object|null
	 */
	public static function get( int $list_id ): ?object {
		global $wpdb;

		return $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}cecomwishfw_lists WHERE id = %d LIMIT 1",
				$list_id
			)
		) ?? null;
	}

	/**
	 * Get all lists for a logged-in user.
	 *
	 * Ordered by is_default DESC (default list first), then created_at ASC.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return array<object>
	 */
	public static function get_for_user( int $user_id ): array {
		global $wpdb;

		return $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}cecomwishfw_lists
				 WHERE user_id = %d
				 ORDER BY is_default DESC, created_at ASC",
				$user_id
			)
		) ?? array();
	}

	/**
	 * Get the default list for a user, or null if none exists.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return object|null
	 */
	public static function get_default_for_user( int $user_id ): ?object {
		global $wpdb;

		return $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}cecomwishfw_lists
				 WHERE user_id = %d AND is_default = 1
				 LIMIT 1",
				$user_id
			)
		) ?? null;
	}

	/**
	 * Get the default list for a user, creating it if it does not exist.
	 *
	 * Never returns null — creates the list on first call if missing.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return object
	 */
	public static function get_or_create_default_for_user( int $user_id ): object {
		$list = self::get_default_for_user( $user_id );

		if ( null !== $list ) {
			return $list;
		}

		$id   = self::create( array( 'user_id' => $user_id ) );
		$list = $id ? self::get( $id ) : null;

		// Extreme edge case: DB insert failed. Return a minimal in-memory
		// object so callers never null-dereference (will fail silently on next write).
		return $list ?? (object) array(
			'id'          => 0,
			'user_id'     => $user_id,
			'session_id'  => null,
			'name'        => '',
			'privacy'     => 'private',
			'share_token' => '',
			'is_default'  => 1,
			'created_at'  => '',
			'updated_at'  => '',
		);
	}

	/**
	 * Find a guest list by session cookie token.
	 *
	 * @param string $session_id 64-char hex token from the guest cookie.
	 * @return object|null
	 */
	public static function find_by_session( string $session_id ): ?object {
		if ( '' === $session_id ) {
			return null;
		}

		global $wpdb;

		return $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}cecomwishfw_lists WHERE session_id = %s LIMIT 1",
				$session_id
			)
		) ?? null;
	}

	/**
	 * Find a list by its public share token.
	 *
	 * @param string $token 64-char hex share token.
	 * @return object|null
	 */
	public static function find_by_token( string $token ): ?object {
		if ( '' === $token ) {
			return null;
		}

		global $wpdb;

		return $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}cecomwishfw_lists WHERE share_token = %s LIMIT 1",
				$token
			)
		) ?? null;
	}

	/**
	 * Update allowed fields on a list row.
	 *
	 * Permitted:  name, privacy, is_default.
	 * Blocked:    user_id, session_id, share_token (silently stripped).
	 * Auto-added: updated_at is always set to current time.
	 *
	 * @param int   $list_id List row ID.
	 * @param array $data    Key-value pairs of fields to update.
	 * @return bool True on success (rows matched), false on failure or no-op.
	 */
	public static function update( int $list_id, array $data ): bool {
		global $wpdb;

		// Remove protected fields.
		unset( $data['user_id'], $data['session_id'], $data['share_token'], $data['id'], $data['created_at'] );

		$clean = array();
		$fmt   = array();

		if ( isset( $data['name'] ) ) {
			$clean['name'] = sanitize_text_field( $data['name'] );
			$fmt[]         = '%s';
		}

		if ( isset( $data['privacy'] ) && self::valid_privacy( $data['privacy'] ) ) {
			$clean['privacy'] = $data['privacy'];
			$fmt[]            = '%s';
		}

		if ( isset( $data['is_default'] ) ) {
			$clean['is_default'] = (int) (bool) $data['is_default'];
			$fmt[]               = '%d';
		}

		if ( empty( $clean ) ) {
			return false; // Nothing to update.
		}

		$clean['updated_at'] = current_time( 'mysql' );
		$fmt[]               = '%s';

		$result = $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prefix . 'cecomwishfw_lists',
			$clean,
			array( 'id' => $list_id ),
			$fmt,
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Delete a list row by ID.
	 *
	 * Caller is responsible for cascade-deleting items first
	 * (no FK constraint — app-layer enforcement).
	 *
	 * @param int $list_id List row ID to delete.
	 * @return bool
	 */
	public static function delete( int $list_id ): bool {
		global $wpdb;

		$result = $wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prefix . 'cecomwishfw_lists',
			array( 'id' => $list_id ),
			array( '%d' )
		);

		return false !== $result;
	}

	// =========================================================================
	// Lifecycle hooks — implemented in fdb-5
	// =========================================================================

	/**
	 * Delete all wishlists (and their items) belonging to a WP user.
	 *
	 * Called by the 'delete_user' hook before the user row is removed.
	 * Guest lists (user_id IS NULL) are never affected — the WHERE clause
	 * only matches exact user_id values.
	 *
	 * Implementation note: $wpdb->delete() supports only equality conditions,
	 * so items are removed per-list rather than via a batch IN() query.
	 * For typical user data volumes this is safe and clear.
	 *
	 * @param int $user_id WP user ID being deleted.
	 * @return void
	 */
	public static function delete_lists_for_user( int $user_id ): void {
		global $wpdb;

		// Fetch all list IDs owned by this user.
		$list_ids = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}cecomwishfw_lists WHERE user_id = %d",
				$user_id
			)
		);

		if ( empty( $list_ids ) ) {
			return;
		}

		// Cascade-delete all items in each list first (no FK constraints in WP).
		foreach ( $list_ids as $list_id ) {
			Cecomwishfw_Item_Model::delete_items_for_list( (int) $list_id );
		}

		// Remove all the user's list rows.
		$wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prefix . 'cecomwishfw_lists',
			array( 'user_id' => $user_id ),
			array( '%d' )
		);
	}

	/**
	 * Count all wishlists across all users (for Dashboard stats).
	 *
	 * @return int
	 */
	public static function count_all(): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			$wpdb->prepare( 'SELECT COUNT(*) FROM %i', $wpdb->prefix . 'cecomwishfw_lists' )
		);
	}

	/**
	 * Purge expired guest sessions (WP-Cron callback).
	 *
	 * Called by the 'cecomwishfw_gc_guests' daily cron event.
	 * Removes guest lists (user_id IS NULL) whose updated_at is older than
	 * 31 days (cookie TTL + 1-day grace period per ADR-005).
	 *
	 * Batch-safe: processes one list row at a time rather than issuing a
	 * bulk DELETE, so no single query can lock a huge table.
	 *
	 * @return void
	 */
	public static function gc_guest_sessions(): void {
		global $wpdb;

		// gmdate() is WPCS-safe; strtotime('-31 days') is timezone-neutral.
		$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( '-31 days' ) );

		$stale_ids = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}cecomwishfw_lists WHERE user_id IS NULL AND updated_at < %s",
				$cutoff
			)
		);

		if ( empty( $stale_ids ) ) {
			return;
		}

		foreach ( $stale_ids as $list_id ) {
			$list_id = (int) $list_id;

			// Cascade-delete items first (no FK constraints).
			Cecomwishfw_Item_Model::delete_items_for_list( $list_id );

			// Then remove the list row.
			$wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prefix . 'cecomwishfw_lists',
				array( 'id' => $list_id ),
				array( '%d' )
			);
		}
	}
}
