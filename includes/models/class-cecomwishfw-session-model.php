<?php
/**
 * Session model — guest cookie lifecycle and merge-on-login.
 *
 * Mirrors the cookie practice used by YITH WooCommerce Wishlist:
 *   • Cookie name:  cecomwishfw_session_<COOKIEHASH>  (filterable)
 *   • Transport:    wc_setcookie()  (WooCommerce cookie wrapper)
 *   • Payload:      JSON-encoded { session_id, session_expiration,
 *                                   session_expiring, cookie_hash }
 *   • Integrity:    HMAC-MD5 over session_id|session_expiration,
 *                   keyed with wp_hash() of the same string.
 *   • Expiration:   30 days, filterable via `cecomwishfw_cookie_expiration`.
 *   • Security:     httponly + secure-on-HTTPS, filterable.
 *
 * Caching caveat: unlike WooCommerce's own `wp_woocommerce_session_*` cookie,
 * this plugin's session cookie is NOT on any caching plugin's default exclusion
 * list. Users running WP Rocket / W3TC / WP Super Cache / Cache Enabler / WP
 * Fastest Cache must add `cecomwishfw_session_` to their cookie-exclusion list
 * manually — this is the same situation YITH users face.
 *
 * On user login, silently moves guest items into the authenticated user's
 * default wishlist (ADR-009 — DB row wins on conflict).
 *
 * Also provides the unified resolve_list() helper used by AJAX handlers and
 * frontend rendering to get the right list regardless of auth state (fmd-4).
 *
 * @package Cecomwishfw
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Cecomwishfw_Session_Model
 */
class Cecomwishfw_Session_Model {

	/**
	 * In-request cache of the current session token, to avoid re-reading and
	 * re-hashing the cookie on every call within a single request.
	 *
	 * @var string|null
	 */
	private static $cached_token = null;

	// =========================================================================
	// Cookie helpers (YITH-style: name / expiration / security are all filtered)
	// =========================================================================

	/**
	 * Get the session cookie name, suffixed with COOKIEHASH so installs on
	 * the same domain (multisite, dev/stage) don't collide.
	 *
	 * @return string
	 */
	private function get_session_cookie_name(): string {
		/**
		 * Filter the session cookie name.
		 *
		 * @param string $cookie_name Default: cecomwishfw_session_{COOKIEHASH}.
		 */
		return (string) apply_filters( 'cecomwishfw_session_cookie', 'cecomwishfw_session_' . COOKIEHASH );
	}

	/**
	 * Cookie expiration (seconds). 30 days by default.
	 *
	 * @return int
	 */
	private function get_cookie_expiration_seconds(): int {
		/**
		 * Filter the session cookie lifetime in seconds.
		 *
		 * @param int $seconds Default 30 days.
		 */
		return (int) apply_filters( 'cecomwishfw_cookie_expiration', 30 * DAY_IN_SECONDS );
	}

	/**
	 * Whether the session cookie must be set with the Secure flag.
	 *
	 * @return bool
	 */
	private function use_secure_cookie(): bool {
		/**
		 * Filter whether to mark the cookie Secure (HTTPS-only).
		 *
		 * @param bool $secure Default: site is HTTPS and current request is SSL.
		 */
		return (bool) apply_filters(
			'cecomwishfw_session_use_secure_cookie',
			function_exists( 'wc_site_is_https' ) ? ( wc_site_is_https() && is_ssl() ) : is_ssl()
		);
	}

	/**
	 * Build the HMAC hash that ties session_id and session_expiration together
	 * and prevents client-side tampering of the cookie payload. Mirrors YITH.
	 *
	 * @param string $session_id         64-char hex token.
	 * @param int    $session_expiration UNIX timestamp.
	 * @return string
	 */
	private function build_cookie_hash( string $session_id, int $session_expiration ): string {
		$to_hash = $session_id . '|' . $session_expiration;
		return hash_hmac( 'md5', $to_hash, wp_hash( $to_hash ) );
	}

	// =========================================================================
	// Guest cookie lifecycle (fmd-3.1 – 3.3)
	// =========================================================================

	/**
	 * Read the session cookie, validate its HMAC, and return the payload.
	 *
	 * @return array|null Decoded payload (session_id, session_expiration,
	 *                    session_expiring, cookie_hash) or null if missing /
	 *                    malformed / tampered / expired.
	 */
	private function read_session_cookie(): ?array {
		$name = $this->get_session_cookie_name();

		if ( ! isset( $_COOKIE[ $name ] ) ) {
			return null;
		}

		$raw     = sanitize_text_field( wp_unslash( $_COOKIE[ $name ] ) );
		$payload = json_decode( $raw, true );

		if ( ! is_array( $payload )
			|| empty( $payload['session_id'] )
			|| empty( $payload['session_expiration'] )
			|| empty( $payload['cookie_hash'] ) ) {
			return null;
		}

		// HMAC check — reject tampered cookies.
		$expected = $this->build_cookie_hash(
			(string) $payload['session_id'],
			(int) $payload['session_expiration']
		);
		if ( ! hash_equals( $expected, (string) $payload['cookie_hash'] ) ) {
			return null;
		}

		// Expired cookie — treat as absent.
		if ( time() > (int) $payload['session_expiration'] ) {
			return null;
		}

		return $payload;
	}

	/**
	 * Mint a new session payload and write it as a cookie via wc_setcookie().
	 *
	 * The cookie value is a JSON-encoded array matching YITH's structure:
	 *   session_id, session_expiration, session_expiring, cookie_hash.
	 *
	 * @return string The newly-minted session_id (64-char hex).
	 */
	private function set_session_cookie(): string {
		$session_id         = bin2hex( random_bytes( 32 ) );
		$session_expiration = time() + $this->get_cookie_expiration_seconds();
		$session_expiring   = $session_expiration - HOUR_IN_SECONDS;

		$payload = array(
			'session_id'         => $session_id,
			'session_expiration' => $session_expiration,
			'session_expiring'   => $session_expiring,
			'cookie_hash'        => $this->build_cookie_hash( $session_id, $session_expiration ),
		);

		$name  = $this->get_session_cookie_name();
		$value = wp_json_encode( $payload );

		// Write the cookie via WooCommerce's wrapper (respects COOKIEPATH,
		// COOKIE_DOMAIN, and the woocommerce_set_cookie_options filter).
		// If headers are already sent (e.g. during output buffering), wc_setcookie
		// is a silent no-op — the $_COOKIE assignment below still lets this
		// request see the token so the guest list is not lost.
		if ( function_exists( 'wc_setcookie' ) ) {
			wc_setcookie( $name, $value, $session_expiration, $this->use_secure_cookie(), true );
		} elseif ( ! headers_sent() ) {
			setcookie( // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.cookies_setcookie
				$name,
				$value,
				array(
					'expires'  => $session_expiration,
					'path'     => COOKIEPATH ? COOKIEPATH : '/',
					'domain'   => COOKIE_DOMAIN,
					'secure'   => $this->use_secure_cookie(),
					'httponly' => true,
					'samesite' => 'Lax',
				)
			);
		}

		// Make the cookie usable within the current request without a reload.
		$_COOKIE[ $name ]   = $value; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		self::$cached_token = $session_id;

		return $session_id;
	}

	/**
	 * Destroy the session cookie (expire immediately).
	 *
	 * @return void
	 */
	private function forget_session_cookie(): void {
		$name = $this->get_session_cookie_name();

		if ( function_exists( 'wc_setcookie' ) ) {
			wc_setcookie( $name, '', time() - HOUR_IN_SECONDS, $this->use_secure_cookie(), true );
		} elseif ( ! headers_sent() ) {
			setcookie( // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.cookies_setcookie
				$name,
				'',
				array(
					'expires'  => time() - HOUR_IN_SECONDS,
					'path'     => COOKIEPATH ? COOKIEPATH : '/',
					'domain'   => COOKIE_DOMAIN,
					'secure'   => $this->use_secure_cookie(),
					'httponly' => true,
					'samesite' => 'Lax',
				)
			);
		}

		unset( $_COOKIE[ $name ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		self::$cached_token = null;
	}

	/**
	 * Get the current session token, minting and setting a new cookie if absent.
	 *
	 * @return string 64-char hex token.
	 */
	public function get_or_create_session_token(): string {
		if ( null !== self::$cached_token && '' !== self::$cached_token ) {
			return self::$cached_token;
		}

		$payload = $this->read_session_cookie();
		if ( null !== $payload ) {
			self::$cached_token = (string) $payload['session_id'];
			return self::$cached_token;
		}

		return $this->set_session_cookie();
	}

	/**
	 * Get the guest list associated with the current session token, or null.
	 *
	 * @return object|null
	 */
	public function get_guest_list(): ?object {
		return Cecomwishfw_List_Model::find_by_session(
			$this->get_or_create_session_token()
		);
	}

	/**
	 * Get the guest list for the current session, creating it if it does not exist.
	 *
	 * @return object
	 */
	public function get_or_create_guest_list(): object {
		$list = $this->get_guest_list();

		if ( null !== $list ) {
			return $list;
		}

		$token = $this->get_or_create_session_token();
		$id    = Cecomwishfw_List_Model::create( array( 'session_id' => $token ) );
		$list  = $id ? Cecomwishfw_List_Model::get( $id ) : null;

		// Fallback in the extremely unlikely event of a DB insert failure.
		return $list ?? (object) array(
			'id'          => 0,
			'user_id'     => null,
			'session_id'  => $token,
			'name'        => '',
			'privacy'     => 'private',
			'share_token' => '',
			'is_default'  => 1,
			'created_at'  => '',
			'updated_at'  => '',
		);
	}

	// =========================================================================
	// Merge-on-login (fmd-3.4, ADR-009)
	// =========================================================================

	/**
	 * Silently merge the current guest wishlist into a user's default list.
	 *
	 * Called on `wp_login` (via on_login() static proxy). Strategy:
	 *   - Non-duplicate items: their list_id is updated directly to the user list
	 *     (no add() call to avoid firing cecomwishfw_after_add_item side effects).
	 *   - Duplicate items: guest copy is deleted; DB row (user list) wins.
	 *   - Guest list row and cookie cleared after merge.
	 *
	 * Idempotent: a second call finds no guest list and returns 0.
	 *
	 * @param int $user_id WP user ID that just logged in.
	 * @return int Number of items actually moved into the user list.
	 */
	public function merge_into_user( int $user_id ): int {
		global $wpdb;

		$token      = $this->get_or_create_session_token();
		$guest_list = $this->get_guest_list();

		if ( null === $guest_list || 0 === (int) $guest_list->id ) {
			return 0; // No guest list — nothing to merge.
		}

		$user_list   = Cecomwishfw_List_Model::get_or_create_default_for_user( $user_id );
		$guest_items = Cecomwishfw_Item_Model::find_by_list( (int) $guest_list->id );
		$merged      = 0;

		foreach ( $guest_items as $item ) {
			$product_id   = (int) $item->product_id;
			$variation_id = (int) $item->variation_id;
			$item_id      = (int) $item->id;

			if ( ! Cecomwishfw_Item_Model::exists( (int) $user_list->id, $product_id, $variation_id ) ) {
				// Move: silently update list_id without firing add() hooks.
				$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prefix . 'cecomwishfw_items',
					array( 'list_id' => (int) $user_list->id ),
					array( 'id' => $item_id ),
					array( '%d' ),
					array( '%d' )
				);
				++$merged;
			} else {
				// Dedupe: DB row (user list) wins — delete the guest copy.
				$wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prefix . 'cecomwishfw_items',
					array( 'id' => $item_id ),
					array( '%d' )
				);
			}
		}

		// Remove the now-empty guest list row.
		Cecomwishfw_List_Model::delete( (int) $guest_list->id );

		// Expire the session cookie so subsequent requests on the same device
		// mint a fresh token rather than resurrecting the merged guest list.
		$this->forget_session_cookie();

		do_action( 'cecomwishfw_guest_merged_into_user', $user_id, $merged, $token );

		return $merged;
	}

	/**
	 * Static proxy for the wp_login hook (2-arg callback).
	 *
	 * @param string   $user_login Username (unused — user object is sufficient).
	 * @param \WP_User $user       The logged-in user object.
	 * @return void
	 */
	public static function on_login( string $user_login, \WP_User $user ): void {
		( new self() )->merge_into_user( $user->ID );
	}

	// =========================================================================
	// Unified list resolver (fmd-4)
	// =========================================================================

	/**
	 * Resolve the correct wishlist regardless of auth state.
	 *
	 * Used by AJAX handlers and frontend rendering to get the active list
	 * for the current visitor.
	 *
	 * Logged-in users:
	 *   - No list_id: returns the user's default list (creates if missing).
	 *   - With list_id: validates ownership; returns object with id=0 on IDOR.
	 *
	 * Guests: returns the session-backed guest list (creates if missing).
	 *
	 * Callers must check $list->id > 0 to detect IDOR / DB-error conditions.
	 *
	 * @param int|null $list_id Optional. Specific list to return for logged-in users.
	 * @return object
	 */
	public function resolve_list( ?int $list_id = null ): object {
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();

			if ( null !== $list_id ) {
				$list = Cecomwishfw_List_Model::get( $list_id );

				// IDOR check: list must belong to the current user.
				if ( ! $list || (int) $list->user_id !== $user_id ) {
					return (object) array( 'id' => 0 );
				}

				return $list;
			}

			return Cecomwishfw_List_Model::get_or_create_default_for_user( $user_id );
		}

		return $this->get_or_create_guest_list();
	}
}
