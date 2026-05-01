<?php
/**
 * AJAX controller — storefront endpoints.
 *
 * Handles all wp_ajax_cecomwishfw_* and wp_ajax_nopriv_cecomwishfw_* actions.
 *
 * Security layering (applied at the top of every handler):
 *   1. verify_nonce()     — CSRF protection
 *   2. rate_limit()       — per-IP throttling
 *   3. sanitize_*()       — input sanitization
 *   4. validate_product() — existence / type check
 *   5. resolve_list()     — auth-aware list resolution
 *
 * Response contract: wp_send_json_success( [...] ) / wp_send_json_error( [...], $status )
 * All success payloads include at minimum: count (int), in_wishlist (bool).
 *
 * Reference: docs/api/ajax-endpoints.md | docs/adr/adr-004-ajax-only-api-v1.md
 *
 * @package Cecomwishfw
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Cecomwishfw_Ajax_Controller
 */
class Cecomwishfw_Ajax_Controller {

	/**
	 * Session model instance.
	 *
	 * @var Cecomwishfw_Session_Model
	 */
	private Cecomwishfw_Session_Model $session;

	/**
	 * Item model instance.
	 *
	 * @var Cecomwishfw_Item_Model
	 */
	private Cecomwishfw_Item_Model $item_model;

	/**
	 * Settings model instance.
	 *
	 * @var Cecomwishfw_Settings
	 */
	private Cecomwishfw_Settings $settings;

	/**
	 * Constructor — instantiate model dependencies.
	 */
	public function __construct() {
		$this->session    = new Cecomwishfw_Session_Model();
		$this->item_model = new Cecomwishfw_Item_Model();
		$this->settings   = new Cecomwishfw_Settings();
	}

	// =========================================================================
	// Security middleware (fax-2)
	// =========================================================================

	/**
	 * Verify the frontend nonce. Sends a 403 JSON error and exits on failure.
	 *
	 * @param string $action Nonce action. Default: 'cecomwishfw_frontend'.
	 * @return void
	 */
	private function verify_nonce( string $action = 'cecomwishfw_frontend' ): void {
		if ( ! check_ajax_referer( $action, '_ajax_nonce', false ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Security check failed.', 'cecom-wishlist-for-woocommerce' ) ),
				403
			);
		}
	}

	/**
	 * Enforce a per-IP rate limit using transients.
	 *
	 * Sends a 429 JSON error and exits when the limit is exceeded.
	 * Developers can override via the 'cecomwishfw_rate_limit' filter.
	 *
	 * @param string $action Identifies the rate-limit bucket.
	 * @param int    $max    Maximum requests per window. Default 20.
	 * @param int    $window Window in seconds. Default 60.
	 * @return void
	 */
	private function rate_limit( string $action, int $max = 20, int $window = 60 ): void {
		$limits = apply_filters(
			'cecomwishfw_rate_limit',
			array(
				'max_requests' => $max,
				'window'       => $window,
			),
			$action
		);

		$max    = (int) $limits['max_requests'];
		$window = (int) $limits['window'];

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		$ip    = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
		$key   = 'cecomwishfw_rl_' . md5( $action . $ip );
		$count = (int) get_transient( $key );

		if ( $count >= $max ) {
			wp_send_json_error(
				array( 'message' => __( 'Too many requests. Please wait.', 'cecom-wishlist-for-woocommerce' ) ),
				429
			);
		}

		set_transient( $key, $count + 1, $window );
	}

	/**
	 * Sanitize and return the product_id from POST.
	 *
	 * @return int 0 if missing or non-numeric.
	 */
	private function sanitize_product_id(): int {
		return absint( wp_unslash( $_POST['product_id'] ?? 0 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in the calling AJAX handler
	}

	/**
	 * Sanitize and return the variation_id from POST.
	 *
	 * @return int 0 if missing or non-numeric.
	 */
	private function sanitize_variation_id(): int {
		return absint( wp_unslash( $_POST['variation_id'] ?? 0 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in the calling AJAX handler
	}

	/**
	 * Sanitize and return the product_ids array from POST.
	 *
	 * @return int[] Positive integer product IDs; empty array if none provided.
	 */
	private function sanitize_product_ids(): array {
		$raw = isset( $_POST['product_ids'] ) ? (array) wp_unslash( $_POST['product_ids'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nonce verified in the calling AJAX handler; each element cast to absint below
		return array_values( array_filter( array_map( 'absint', $raw ) ) );
	}

	/**
	 * Validate that a product_id corresponds to an existing WC product.
	 *
	 * Sends a 400 JSON error and exits on failure.
	 *
	 * @param int $product_id WooCommerce product ID to validate.
	 * @return \WC_Product (or never — exits on invalid)
	 */
	private function validate_product( int $product_id ): \WC_Product {
		if ( $product_id <= 0 ) {
			wp_send_json_error(
				array( 'message' => __( 'Invalid product.', 'cecom-wishlist-for-woocommerce' ) ),
				400
			);
		}

		$product = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;

		if ( ! $product instanceof \WC_Product ) {
			wp_send_json_error(
				array( 'message' => __( 'Product not found.', 'cecom-wishlist-for-woocommerce' ) ),
				400
			);
		}

		return $product; // @phpstan-ignore-line (return after wp_send_json_error exit)
	}

	/**
	 * Resolve the current list and guard against IDOR / DB errors.
	 *
	 * Sends a 403 JSON error on IDOR (ownership mismatch) and a 500 JSON error
	 * when resolve_list() returns id=0 due to a DB failure — distinguishing an
	 * authorization failure from a server-side error.
	 *
	 * @param int|null $list_id Optional specific list ID (for logged-in users).
	 * @return object Valid list object with id > 0.
	 */
	private function get_list( ?int $list_id = null ): object {
		$list = $this->session->resolve_list( $list_id );

		if ( (int) ( $list->id ?? 0 ) === 0 ) {
			wp_send_json_error(
				array( 'message' => __( 'Unable to resolve wishlist. Please refresh the page.', 'cecom-wishlist-for-woocommerce' ) ),
				500
			);
		}

		return $list; // @phpstan-ignore-line
	}

	/**
	 * Return the current item count for the visitor.
	 *
	 * @return int
	 */
	private function get_current_count(): int {
		if ( is_user_logged_in() ) {
			return $this->item_model->count_for_user( get_current_user_id() );
		}

		$guest_list = $this->session->get_guest_list();
		return $this->item_model->count_for_list( $guest_list ? (int) $guest_list->id : 0 );
	}

	// =========================================================================
	// Storefront endpoints (fax-3 → fax-5)
	// =========================================================================

	/**
	 * Toggle a product in the wishlist (add if absent, remove if present).
	 *
	 * Action: wp_ajax[_nopriv]_cecomwishfw_toggle_item
	 *
	 * @return void
	 */
	public function handle_toggle(): void {
		$this->verify_nonce();
		$this->rate_limit( 'toggle' );

		if ( $this->settings->get( 'general', 'registered_only' ) && ! is_user_logged_in() ) {
			wp_send_json_error(
				array( 'message' => __( 'Please log in to use the wishlist.', 'cecom-wishlist-for-woocommerce' ) ),
				403
			);
		}

		$product_id   = $this->sanitize_product_id();
		$variation_id = $this->sanitize_variation_id();

		$product = $this->validate_product( $product_id );

		$allowed_types = (array) $this->settings->get( 'general', 'product_types' );
		if ( ! empty( $allowed_types ) && ! in_array( $product->get_type(), $allowed_types, true ) ) {
			wp_send_json_error(
				array( 'message' => __( 'This product type cannot be wishlisted.', 'cecom-wishlist-for-woocommerce' ) ),
				400
			);
		}

		// For guests: peek at the existing list without creating a row first.
		// An absent list means the item cannot be wishlisted, so we only
		// create the row when we actually need to add the item.
		if ( is_user_logged_in() ) {
			$list = $this->get_list();
		} else {
			$list = $this->session->get_guest_list();
		}

		$in_wishlist = $list && (int) $list->id > 0
			? $this->item_model->exists( (int) $list->id, $product_id, $variation_id )
			: false;

		if ( $in_wishlist ) {
			$this->item_model->remove( (int) $list->id, $product_id, $variation_id );
			$action = 'removed';
			// Remove the guest list row when it becomes empty so it is not
			// counted as an Active Wishlist.
			if ( ! is_user_logged_in() && 0 === $this->item_model->count_for_list( (int) $list->id ) ) {
				Cecomwishfw_List_Model::delete( (int) $list->id );
			}
		} else {
			// Adding: create the guest list row now (only at this point).
			if ( ! $list || 0 === (int) $list->id ) {
				$list = $this->get_list();
			}
			$this->item_model->add( (int) $list->id, $product_id, $variation_id );
			$action = 'added';
		}

		wp_send_json_success(
			array(
				'in_wishlist' => ! $in_wishlist,
				'action'      => $action,
				'count'       => $this->get_current_count(),
				'message'     => 'added' === $action
					? __( 'Added to wishlist', 'cecom-wishlist-for-woocommerce' )
					: __( 'Removed from wishlist', 'cecom-wishlist-for-woocommerce' ),
			)
		);
	}

	/**
	 * Explicitly add a product to the wishlist.
	 *
	 * Idempotent: if the product is already wishlisted, returns its current state.
	 * Action: wp_ajax[_nopriv]_cecomwishfw_add_item
	 *
	 * @return void
	 */
	public function handle_add(): void {
		$this->verify_nonce();
		$this->rate_limit( 'add' );

		if ( $this->settings->get( 'general', 'registered_only' ) && ! is_user_logged_in() ) {
			wp_send_json_error(
				array( 'message' => __( 'Please log in to use the wishlist.', 'cecom-wishlist-for-woocommerce' ) ),
				403
			);
		}

		$product_id   = $this->sanitize_product_id();
		$variation_id = $this->sanitize_variation_id();

		$product = $this->validate_product( $product_id );

		$allowed_types = (array) $this->settings->get( 'general', 'product_types' );
		if ( ! empty( $allowed_types ) && ! in_array( $product->get_type(), $allowed_types, true ) ) {
			wp_send_json_error(
				array( 'message' => __( 'This product type cannot be wishlisted.', 'cecom-wishlist-for-woocommerce' ) ),
				400
			);
		}

		$list        = $this->get_list();
		$in_wishlist = $this->item_model->exists( (int) $list->id, $product_id, $variation_id );

		if ( ! $in_wishlist ) {
			$this->item_model->add( (int) $list->id, $product_id, $variation_id );
			$in_wishlist = true;
		}

		wp_send_json_success(
			array(
				'in_wishlist' => $in_wishlist,
				'action'      => 'added',
				'count'       => $this->get_current_count(),
				'message'     => __( 'Added to wishlist', 'cecom-wishlist-for-woocommerce' ),
			)
		);
	}

	/**
	 * Explicitly remove a product from the wishlist.
	 *
	 * Idempotent: no error if the product was not in the list.
	 * Action: wp_ajax[_nopriv]_cecomwishfw_remove_item
	 *
	 * @return void
	 */
	public function handle_remove(): void {
		$this->verify_nonce();
		$this->rate_limit( 'remove' );

		if ( $this->settings->get( 'general', 'registered_only' ) && ! is_user_logged_in() ) {
			wp_send_json_error(
				array( 'message' => __( 'Please log in to use the wishlist.', 'cecom-wishlist-for-woocommerce' ) ),
				403
			);
		}

		$product_id   = $this->sanitize_product_id();
		$variation_id = $this->sanitize_variation_id();

		$this->validate_product( $product_id );

		// For guests: don't create a list row just to remove from it.
		if ( is_user_logged_in() ) {
			$list = $this->get_list();
		} else {
			$list = $this->session->get_guest_list();
			if ( null === $list ) {
				// No list exists — nothing to remove; return success immediately.
				wp_send_json_success(
					array(
						'in_wishlist' => false,
						'action'      => 'removed',
						'count'       => 0,
						'message'     => __( 'Removed from wishlist', 'cecom-wishlist-for-woocommerce' ),
					)
				);
			}
		}

		$this->item_model->remove( (int) $list->id, $product_id, $variation_id );

		// Delete the guest list row when it becomes empty.
		if ( ! is_user_logged_in() && 0 === $this->item_model->count_for_list( (int) $list->id ) ) {
			Cecomwishfw_List_Model::delete( (int) $list->id );
		}

		wp_send_json_success(
			array(
				'in_wishlist' => false,
				'action'      => 'removed',
				'count'       => $this->get_current_count(),
				'message'     => __( 'Removed from wishlist', 'cecom-wishlist-for-woocommerce' ),
			)
		);
	}

	/**
	 * Return the current wishlist item count for the header badge.
	 *
	 * Action: wp_ajax[_nopriv]_cecomwishfw_get_count
	 *
	 * @return void
	 */
	public function handle_get_count(): void {
		$this->verify_nonce();

		wp_send_json_success(
			array(
				'count' => $this->get_current_count(),
			)
		);
	}

	/**
	 * Return the wishlist status for a batch of product IDs.
	 *
	 * Called on every page load by the JS hydrateButtonStates() function so
	 * buttons on cached pages can display the correct per-user state without
	 * re-rendering the page. Read-only — no state mutation, so no rate limit.
	 *
	 * Action: wp_ajax[_nopriv]_cecomwishfw_get_status
	 *
	 * Request (POST):
	 *   product_ids  array<int>  IDs of the products whose status is requested.
	 *   _ajax_nonce  string      Frontend nonce.
	 *
	 * Response (success):
	 *   {
	 *     "<product_id>": {
	 *       "in_wishlist":   bool,
	 *       "variation_ids": int[]   // wishlisted variation IDs (may be empty)
	 *     },
	 *     ...
	 *   }
	 *
	 * @return void
	 */
	public function handle_get_status(): void {
		$this->verify_nonce();

		$product_ids = $this->sanitize_product_ids();

		if ( empty( $product_ids ) ) {
			wp_send_json_success( array() );
		}

		$result = array();

		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
			foreach ( $product_ids as $pid ) {
				$var_ids     = $this->item_model->get_wishlisted_variation_ids_for_user( $user_id, $pid );
				$in_wishlist = ! empty( $var_ids ) || $this->item_model->is_product_in_any_user_list( $user_id, $pid, 0 );
				$result[ $pid ] = array(
					'in_wishlist'   => $in_wishlist,
					'variation_ids' => $var_ids,
				);
			}
		} else {
			$guest_list = $this->session->get_guest_list();
			foreach ( $product_ids as $pid ) {
				if ( $guest_list ) {
					$var_ids     = $this->item_model->get_wishlisted_variation_ids_for_list( (int) $guest_list->id, $pid );
					$in_wishlist = ! empty( $var_ids ) || $this->item_model->exists( (int) $guest_list->id, $pid, 0 );
				} else {
					$var_ids     = array();
					$in_wishlist = false;
				}
				$result[ $pid ] = array(
					'in_wishlist'   => $in_wishlist,
					'variation_ids' => $var_ids,
				);
			}
		}

		wp_send_json_success( $result );
	}

	/**
	 * Return wishlist popularity counts for a batch of product IDs.
	 *
	 * Read-only — no rate limit. Batch size capped at 100 (filterable).
	 * Action: wp_ajax[_nopriv]_cecomwishfw_get_popularity
	 *
	 * @return void
	 */
	public function handle_get_popularity(): void {
		$this->verify_nonce();

		$product_ids = array_values( array_filter( array_map( 'absint', (array) wp_unslash( $_POST['product_ids'] ?? array() ) ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified above
		$product_ids = array_slice( $product_ids, 0, (int) apply_filters( 'cecomwishfw_batch_product_ids_limit', 100 ) );

		if ( empty( $product_ids ) ) {
			wp_send_json_success( array() );
		}

		$result = array();
		foreach ( $product_ids as $pid ) {
			$result[ $pid ] = Cecomwishfw_Item_Model::get_popularity_count( $pid );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Update the main quantity for a wishlist item (simple / parent product).
	 *
	 * Owner-only — not registered for nopriv. Requires login.
	 * Action: wp_ajax_cecomwishfw_update_item_quantity
	 *
	 * @return void
	 */
	public function handle_update_item_quantity(): void {
		$this->verify_nonce();

		if ( ! is_user_logged_in() ) {
			wp_send_json_error(
				array(
					'code'    => 'not_logged_in',
					'message' => __( 'Please log in first.', 'cecom-wishlist-for-woocommerce' ),
				),
				403
			);
		}

		$item_id  = absint( wp_unslash( $_POST['item_id'] ?? 0 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified above
		$quantity = max( 1, absint( wp_unslash( $_POST['quantity'] ?? 1 ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( $item_id <= 0 ) {
			wp_send_json_error(
				array(
					'code'    => 'invalid_request',
					'message' => __( 'Invalid request.', 'cecom-wishlist-for-woocommerce' ),
				),
				400
			);
		}

		$ok = Cecomwishfw_Item_Model::update_quantity( $item_id, $quantity, get_current_user_id() );
		if ( ! $ok ) {
			wp_send_json_error(
				array(
					'code'    => 'server_error',
					'message' => __( 'Could not update quantity.', 'cecom-wishlist-for-woocommerce' ),
				),
				400
			);
		}

		wp_send_json_success(
			array(
				'item_id'  => $item_id,
				'quantity' => $quantity,
			)
		);
	}

	/**
	 * Merge per-child quantities for a grouped or variable wishlist item.
	 *
	 * Owner-only — not registered for nopriv. Requires login.
	 * Action: wp_ajax_cecomwishfw_update_child_quantities
	 *
	 * @return void
	 */
	public function handle_update_child_quantities(): void {
		$this->verify_nonce();

		if ( ! is_user_logged_in() ) {
			wp_send_json_error(
				array(
					'code'    => 'not_logged_in',
					'message' => __( 'Please log in first.', 'cecom-wishlist-for-woocommerce' ),
				),
				403
			);
		}

		$item_id    = absint( wp_unslash( $_POST['item_id'] ?? 0 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified above
		$raw_qtys   = (array) wp_unslash( $_POST['quantities'] ?? array() ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$quantities = array();
		foreach ( $raw_qtys as $child_id => $qty ) {
			$child_id = absint( $child_id );
			if ( $child_id > 0 ) {
				$quantities[ $child_id ] = max( 1, absint( $qty ) );
			}
		}

		if ( $item_id <= 0 || empty( $quantities ) ) {
			wp_send_json_error(
				array(
					'code'    => 'invalid_request',
					'message' => __( 'Invalid request.', 'cecom-wishlist-for-woocommerce' ),
				),
				400
			);
		}

		$ok = Cecomwishfw_Item_Model::update_child_quantities( $item_id, $quantities, get_current_user_id() );
		if ( ! $ok ) {
			wp_send_json_error(
				array(
					'code'    => 'server_error',
					'message' => __( 'Could not update quantities.', 'cecom-wishlist-for-woocommerce' ),
				),
				400
			);
		}

		wp_send_json_success(
			array(
				'item_id'    => $item_id,
				'quantities' => $quantities,
			)
		);
	}
}
