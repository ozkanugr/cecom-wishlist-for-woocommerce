<?php
/**
 * Share Controller — public shared-wishlist view and token management.
 *
 * Responsibilities:
 *   handle_shared_view()    — template_redirect handler for ?cwfw_token=<token>
 *   filter_document_title() — sets page title to "{Owner}'s Wishlist"
 *   ajax_regenerate_token() — AJAX: regenerate share_token for logged-in user's default list
 *
 * URL scheme: add_query_arg( 'cwfw_token', rawurlencode( $token ), $wishlist_page_url )
 * Security order: nonce → capability → sanitize → validate → escape
 *
 * @package Cecomwishfw
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Cecomwishfw_Share_Controller
 */
class Cecomwishfw_Share_Controller {

	// =========================================================================
	// Shared view — template_redirect (fsh-1)
	// =========================================================================

	/**
	 * Validate ?cwfw_token=<token> requests on template_redirect.
	 *
	 * Behavior split:
	 *   • No token in the URL          → return immediately (no-op).
	 *   • Token present, list exists   → return immediately so WP renders the
	 *                                    active theme's template normally. The
	 *                                    wishlist page contains the
	 *                                    [cecomwishfw_wishlist] shortcode, which
	 *                                    detects ?cwfw_token=… inside
	 *                                    Cecomwishfw_Frontend_Controller::shortcode_callback()
	 *                                    and renders the read-only shared view
	 *                                    inside the theme's container / header /
	 *                                    footer — same chrome as a logged-in
	 *                                    user viewing their own My Wishlist.
	 *   • Token present, list missing  → wp_die(404). Visitor sees the theme's
	 *                                    404 chrome, never a raw PHP error.
	 *
	 * Hooked on 'template_redirect'.
	 *
	 * @return void
	 */
	public function handle_shared_view(): void {
		$token = sanitize_text_field( wp_unslash( filter_input( INPUT_GET, 'cwfw_token', FILTER_DEFAULT ) ?? '' ) );
		if ( '' === $token ) {
			return;
		}

		$list = Cecomwishfw_List_Model::find_by_token( $token );

		if ( ! $list ) {
			wp_die(
				esc_html__( 'This wishlist link is no longer available.', 'cecom-wishlist-for-woocommerce' ),
				esc_html__( 'Wishlist Not Found', 'cecom-wishlist-for-woocommerce' ),
				array( 'response' => 404 )
			);
		}

		// Valid token — let the request continue. WordPress will render the
		// active theme's template; the wishlist page's [cecomwishfw_wishlist]
		// shortcode handles the shared-view rendering from inside the page
		// content, so the output is wrapped in the theme's normal chrome.
	}

	// =========================================================================
	// Document title — document_title_parts filter (fsh-1.4)
	// =========================================================================

	/**
	 * Override the browser/tab title on shared wishlist pages.
	 *
	 * Output: "{Owner}'s Wishlist — {Site Name}"
	 * Hooked on 'document_title_parts' (WP 4.4+).
	 *
	 * @param array<string, string> $parts Title parts provided by WP.
	 * @return array<string, string>
	 */
	public function filter_document_title( array $parts ): array {
		$token = sanitize_text_field( wp_unslash( filter_input( INPUT_GET, 'cwfw_token', FILTER_DEFAULT ) ?? '' ) );
		if ( '' === $token ) {
			return $parts;
		}

		$list = Cecomwishfw_List_Model::find_by_token( $token );
		if ( ! $list ) {
			return $parts;
		}

		$owner_name = __( 'Someone', 'cecom-wishlist-for-woocommerce' );
		if ( ! empty( $list->user_id ) ) {
			$owner_data = get_userdata( (int) $list->user_id );
			if ( $owner_data instanceof \WP_User ) {
				$owner_name = $owner_data->display_name;
			}
		}

		/* translators: %s: wishlist owner's display name */
		$parts['title'] = sprintf( __( "%s's Wishlist", 'cecom-wishlist-for-woocommerce' ), $owner_name );

		return $parts;
	}

	// =========================================================================
	// Regenerate share token AJAX (fsh-5)
	// =========================================================================

	/**
	 * AJAX handler: regenerate the share token for the current user's default list.
	 *
	 * Security: nonce → login check → IDOR guard (query scoped to user_id).
	 * The old token is immediately invalidated — any existing shared links stop working.
	 * Returns the new share URL so JS can update the UI without a page reload.
	 *
	 * Registered for: wp_ajax_cecomwishfw_regenerate_token (logged-in only; no nopriv).
	 *
	 * @return void
	 */
	public function ajax_regenerate_token(): void {
		check_ajax_referer( 'cecomwishfw_frontend', '_ajax_nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Login required.', 'cecom-wishlist-for-woocommerce' ) ) );
		}

		global $wpdb;
		$user_id = get_current_user_id();

		// Fetch the user's default list, scoped by user_id (IDOR prevention).
		$list = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}cecomwishfw_lists WHERE user_id = %d AND is_default = 1 LIMIT 1",
				$user_id
			)
		);

		if ( ! $list ) {
			wp_send_json_error( array( 'message' => __( 'Wishlist not found.', 'cecom-wishlist-for-woocommerce' ) ) );
		}

		// Belt-and-suspenders IDOR guard.
		if ( (int) $list->user_id !== $user_id ) {
			wp_send_json_error( array( 'message' => __( 'Access denied.', 'cecom-wishlist-for-woocommerce' ) ) );
		}

		$new_token = bin2hex( random_bytes( 32 ) );

		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prefix . 'cecomwishfw_lists',
			array( 'share_token' => $new_token ),
			array( 'id' => (int) $list->id ),
			array( '%s' ),
			array( '%d' )
		);

		// Build the new share URL.
		$settings  = new Cecomwishfw_Settings();
		$page_id   = (int) $settings->get( 'general', 'wishlist_page_id', 0 );
		$base_url  = $page_id > 0 ? (string) get_permalink( $page_id ) : home_url( '/wishlist/' );
		$share_url = add_query_arg( 'cwfw_token', rawurlencode( $new_token ), $base_url );

		/**
		 * Filter the regenerated share URL.
		 *
		 * @param string $share_url  New share URL (contains cwfw_token).
		 * @param object $list       List row object from the DB.
		 */
		$share_url = (string) apply_filters( 'cecomwishfw_share_url', $share_url, $list );

		wp_send_json_success( array( 'share_url' => esc_url_raw( $share_url ) ) );
	}
}
