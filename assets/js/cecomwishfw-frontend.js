/**
 * CECOM Wishlist for WooCommerce — Frontend JavaScript
 *
 * Handles all storefront wishlist interactions:
 *   - Add/Remove toggle on product pages and shop loop
 *   - Toast notification system
 *   - Header counter updates
 *   - Variation-aware button state
 *   - WooCommerce Blocks (Gutenberg) compatibility
 *   - Remove-from-wishlist-page handler
 *   - Redirect to checkout after add-to-cart (if setting enabled)
 *
 * Bootstrapped by wp_localize_script as window.cecomwishfwFrontend.
 * jQuery is available via WP core but used minimally (only for event delegation
 * on markup that already exists).
 *
 * @package Cecomwishfw
 */

( function ( $, cfg ) {
	'use strict';

	/* global jQuery */

	var WISHLIST_BTN    = '.cecomwishfw-btn';
	var REMOVE_BTN      = '.cecomwishfw-remove-item';
	var COUNTER_EL      = '.cecomwishfw-count';
	var ITEM_ROW        = '.cecomwishfw-item-row';
	var VARIATIONS_FORM = '.variations_form';

	// =========================================================================
	// Init
	// =========================================================================

	function init() {
		bindToggleClick();
		bindRemoveClick();
		bindVariationEvents();
		bindWcBlocksCompat();
		bindRedirectToCheckout();
		bindRemoveOnCart();
		bindOverlayPosition();
		bindCopyUrl();
		bindRegenerateToken();
		markRedirectCheckoutWrappers();
		hydrateCounterState();
		hydrateButtonStates();
	}

	// =========================================================================
	// Toggle (add / remove) click handler — ffr-7.2
	// =========================================================================

	function bindToggleClick() {
		$( document ).on( 'click', WISHLIST_BTN, function ( e ) {
			e.preventDefault();

			var $btn        = $( this );
			var productId   = parseInt( $btn.data( 'product-id' ), 10 ) || 0;
			var variationId = parseInt( $btn.data( 'variation-id' ), 10 ) || 0;

			if ( ! productId ) {
				return;
			}

			// Registered-only mode: guest buttons render normally but are blocked client-side.
			// data-login-required="1" is set by PHP when registered_only is enabled and the
			// visitor is not logged in. Show a toast and bail — no AJAX request is made.
			if ( $btn.data( 'login-required' ) ) {
				showToast( cfg.i18n && cfg.i18n.loginRequired ? cfg.i18n.loginRequired : 'Login required!', 'error' );
				return;
			}

			// Guard: nonce or ajaxUrl missing means wp_localize_script data was not
			// injected (e.g. page served from a full-page cache without PHP execution).
			if ( ! cfg.nonce || ! cfg.ajaxUrl ) {
				showToast( cfg.i18n && cfg.i18n.error ? cfg.i18n.error : 'Please refresh the page and try again.', 'error' );
				return;
			}

			// Disable button during request to prevent double-click.
			$btn.prop( 'disabled', true );

			$.ajax( {
				url:    cfg.ajaxUrl,
				method: 'POST',
				data:   {
					action:       'cecomwishfw_toggle_item',
					_ajax_nonce:  cfg.nonce,
					product_id:   productId,
					variation_id: variationId,
				},
				success: function ( res ) {
					if ( res.success ) {
						updateButtonState( $btn, res.data.in_wishlist );
						updateCounters( res.data.count );
						showToast( res.data.message || ( res.data.in_wishlist ? cfg.i18n.addedToWishlist : cfg.i18n.removedFromWishlist ) );
					} else {
						showToast( res.data && res.data.message ? res.data.message : cfg.i18n.error, 'error' );
					}
				},
				error: function () {
					showToast( cfg.i18n.error, 'error' );
				},
				complete: function () {
					$btn.prop( 'disabled', false );
				},
			} );
		} );
	}

	// =========================================================================
	// Button state update — ffr-7.2
	// =========================================================================

	function updateButtonState( $btn, inWishlist ) {
		$btn.toggleClass( 'active', inWishlist );
		$btn.attr( 'aria-pressed', inWishlist ? 'true' : 'false' );
		$btn.attr( 'aria-label', inWishlist ? cfg.i18n.removeFromWishlist : cfg.i18n.addToWishlist );

		if ( ! $btn.find( '.cecomwishfw-custom-icon' ).length ) {
			$btn.find( '.bi' )
				.removeClass( 'bi-heart bi-heart-fill' )
				.addClass( inWishlist ? 'bi-heart-fill' : 'bi-heart' );
		}

		var addLbl    = $btn.data( 'add-label' )    || cfg.i18n.addToWishlist;
		var removeLbl = $btn.data( 'remove-label' ) || cfg.i18n.removeFromWishlist;
		$btn.find( '.cecomwishfw-btn-label' ).text( inWishlist ? removeLbl : addLbl );
	}

	// =========================================================================
	// Counter update — ffr-7.4
	// =========================================================================

	function updateCounters( count ) {
		if ( cfg.settings && cfg.settings.showCounter === false ) {
			return;
		}
		var showZero = !! ( cfg.settings && cfg.settings.counterShowZero );
		$( COUNTER_EL ).text( count );
		$( COUNTER_EL ).toggleClass( 'cecomwishfw-count--empty', count === 0 && ! showZero );
	}

	// =========================================================================
	// Toast notification system — ffr-7.3
	// =========================================================================

	function showToast( message, type ) {
		type = type || 'success';

		var el = document.createElement( 'div' );
		el.className  = 'cecomwishfw-toast cecomwishfw-toast--' + type;
		el.setAttribute( 'role', 'status' );
		el.setAttribute( 'aria-live', 'polite' );
		el.textContent = message;
		document.body.appendChild( el );

		// Trigger transition on next frame.
		requestAnimationFrame( function () {
			el.classList.add( 'cecomwishfw-toast--show' );
		} );

		// Respect prefers-reduced-motion: no animation, dismiss immediately.
		var prefersReduced = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
		var delay = prefersReduced ? 100 : 3000;

		setTimeout( function () {
			el.classList.remove( 'cecomwishfw-toast--show' );
			el.addEventListener( 'transitionend', function () {
				if ( el.parentNode ) {
					el.parentNode.removeChild( el );
				}
			}, { once: true } );

			// Fallback removal if transitionend never fires.
			setTimeout( function () {
				if ( el.parentNode ) {
					el.parentNode.removeChild( el );
				}
			}, 600 );
		}, delay );
	}

	// =========================================================================
	// Variation-aware button state — ffr-7.5 (ffr-4.1)
	// =========================================================================

	function bindVariationEvents() {
		$( document ).on( 'found_variation', VARIATIONS_FORM, function ( e, variation ) {
			var variationId = variation && variation.variation_id ? parseInt( variation.variation_id, 10 ) : 0;
			updateButtonVariation( variationId );
		} );

		$( document ).on( 'reset_data', VARIATIONS_FORM, function () {
			updateButtonVariation( 0 );
		} );
	}

	function updateButtonVariation( variationId ) {
		// Find the wishlist button on the product page.
		$( WISHLIST_BTN ).each( function () {
			var $btn = $( this );
			$btn.attr( 'data-variation-id', variationId );

			// Update the active state to reflect whether this specific variation
			// (or any variation when resetting to 0) is already in the wishlist.
			// The server bakes the list of wishlisted variation IDs into the button
			// as data-wishlisted-variations so no extra AJAX call is needed.
			var raw = $btn.attr( 'data-wishlisted-variations' );
			if ( ! raw ) {
				return;
			}

			var wishlistedIds;
			try {
				wishlistedIds = JSON.parse( raw );
			} catch ( err ) {
				return;
			}

			if ( ! Array.isArray( wishlistedIds ) ) {
				return;
			}

			var inWishlist;
			if ( variationId > 0 ) {
				// A specific swatch was selected — show active only if that exact variation is saved.
				inWishlist = wishlistedIds.indexOf( variationId ) !== -1;
			} else {
				// Selections cleared — show active if any variation is saved (initial page state).
				inWishlist = wishlistedIds.length > 0;
			}

			updateButtonState( $btn, inWishlist );
		} );
	}

	// =========================================================================
	// Remove-from-wishlist-page handler — ffr-7.7
	// =========================================================================

	function bindRemoveClick() {
		$( document ).on( 'click', REMOVE_BTN, function ( e ) {
			e.preventDefault();

			var $btn        = $( this );
			var $row        = $btn.closest( ITEM_ROW );
			var productId   = parseInt( $btn.data( 'product-id' ), 10 ) || 0;
			var variationId = parseInt( $btn.data( 'variation-id' ), 10 ) || 0;
			var listId      = parseInt( $btn.data( 'list-id' ), 10 ) || 0;

			if ( ! productId ) {
				return;
			}

			if ( ! cfg.nonce || ! cfg.ajaxUrl ) {
				showToast( cfg.i18n && cfg.i18n.error ? cfg.i18n.error : 'Please refresh the page and try again.', 'error' );
				return;
			}

			$btn.prop( 'disabled', true );

			$.ajax( {
				url:    cfg.ajaxUrl,
				method: 'POST',
				data:   {
					action:       'cecomwishfw_remove_item',
					_ajax_nonce:  cfg.nonce,
					product_id:   productId,
					variation_id: variationId,
					list_id:      listId,
				},
				success: function ( res ) {
					if ( res.success ) {
						// Fade out and remove the row.
						$row.css( { transition: 'opacity 0.3s', opacity: 0 } );
						setTimeout( function () {
							$row.remove();
							// Show empty state if no rows remain.
							var $tbody = $( '.cecomwishfw-wishlist-table tbody' );
							if ( $tbody.length && $tbody.find( ITEM_ROW ).length === 0 ) {
								location.reload();
							}
						}, 320 );
						updateCounters( res.data.count );
						showToast( cfg.i18n.removedFromWishlist );
					} else {
						$btn.prop( 'disabled', false );
						showToast( res.data && res.data.message ? res.data.message : cfg.i18n.error, 'error' );
					}
				},
				error: function () {
					$btn.prop( 'disabled', false );
					showToast( cfg.i18n.error, 'error' );
				},
			} );
		} );
	}

	// =========================================================================
	// WooCommerce Blocks compatibility — ffr-7.6
	// =========================================================================

	function bindWcBlocksCompat() {
		// WC Blocks renders product cards via React. Use a MutationObserver to
		// detect when new product cards appear and inject the wishlist button.
		if ( ! ( 'MutationObserver' in window ) ) {
			return;
		}

		var observer = new MutationObserver( function ( mutations ) {
			mutations.forEach( function ( mutation ) {
				mutation.addedNodes.forEach( function ( node ) {
					if ( node.nodeType !== 1 ) {
						return;
					}
					// Look for WC blocks product cards that don't yet have a wishlist button.
					var cards = node.querySelectorAll
						? node.querySelectorAll( '.wc-block-grid__product:not(.cecomwishfw-block-wired)' )
						: [];

					Array.prototype.forEach.call( cards, function ( card ) {
						var productLink = card.querySelector( '.wc-block-grid__product-link' );
						if ( ! productLink ) {
							return;
						}

						// Extract product ID from the add-to-cart button data attribute.
						var addBtn = card.querySelector( '[data-product_id]' );
						if ( ! addBtn ) {
							return;
						}

						var productId = parseInt( addBtn.getAttribute( 'data-product_id' ), 10 );
						if ( ! productId ) {
							return;
						}

						// Create and inject a minimal wishlist button.
						var btn = document.createElement( 'button' );
						btn.className          = 'cecomwishfw-btn';
						btn.type               = 'button';
						btn.setAttribute( 'data-product-id', productId );
						btn.setAttribute( 'data-variation-id', '0' );
						btn.setAttribute( 'aria-label', cfg.i18n.addToWishlist );
						btn.setAttribute( 'aria-pressed', 'false' );
						btn.innerHTML          = '<i class="bi bi-heart" aria-hidden="true"></i>';

						card.appendChild( btn );
						card.classList.add( 'cecomwishfw-block-wired' );
					} );
				} );
			} );
		} );

		observer.observe( document.body, { childList: true, subtree: true } );
	}

	// =========================================================================
	// Redirect to checkout after Add to Cart — ffr-7.8
	// =========================================================================

	function bindRedirectToCheckout() {
		if ( ! cfg.settings || ! cfg.settings.redirectCheckout ) {
			return;
		}

		$( document ).on( 'added_to_cart', function () {
			var checkoutUrl = cfg.settings.checkoutUrl || cfg.settings.wishlistUrl;

			if ( checkoutUrl ) {
				window.location.href = checkoutUrl;
			}
		} );
	}

	// =========================================================================
	// Remove from wishlist when added to cart — ffr-7.9
	// =========================================================================

	function bindRemoveOnCart() {
		if ( ! cfg.settings || ! cfg.settings.removeOnCart ) {
			return;
		}

		$( document ).on( 'added_to_cart', function ( e, fragments, cartHash, $button ) {
			if ( ! $button || ! $button.data ) {
				return;
			}

			// Variation cart buttons pass the wishlist keys in dedicated attributes
			// (data-wl-product-id / data-wl-variation-id) because data-product_id is
			// set to the variation ID so WooCommerce can auto-resolve variation attributes.
			// Simple product buttons only have data-product_id.
			var wlProductId   = parseInt( $button.data( 'wl-product-id' ),   10 ) || 0;
			var wlVariationId = parseInt( $button.data( 'wl-variation-id' ), 10 ) || 0;
			var productId     = wlProductId  || parseInt( $button.data( 'product_id' ), 10 ) || 0;
			var variationId   = wlVariationId;

			if ( ! productId || ! cfg.nonce || ! cfg.ajaxUrl ) {
				return;
			}

			$.ajax( {
				url:    cfg.ajaxUrl,
				method: 'POST',
				data:   {
					action:       'cecomwishfw_remove_item',
					_ajax_nonce:  cfg.nonce,
					product_id:   productId,
					variation_id: variationId,
				},
				success: function ( res ) {
					if ( res.success ) {
						// productId is always the parent product ID here, matching the
						// data-product-id attribute on the .cecomwishfw-btn toggle buttons.
						$( WISHLIST_BTN + '[data-product-id="' + productId + '"]' ).each( function () {
							var $btn           = $( this );
							var btnVariationId = parseInt( $btn.data( 'variation-id' ), 10 ) || 0;
							// Only clear the active state on buttons that match the removed variation.
							if ( variationId === 0 || btnVariationId === variationId ) {
								updateButtonState( $btn, false );
								// Keep the wishlisted-variations list in sync so swatch changes
								// still reflect the correct state without a page reload.
								var raw = $btn.attr( 'data-wishlisted-variations' );
								if ( raw && variationId > 0 ) {
									try {
										var ids = JSON.parse( raw );
										ids     = ids.filter( function ( id ) { return id !== variationId; } );
										$btn.attr( 'data-wishlisted-variations', JSON.stringify( ids ) );
									} catch ( err ) { /* ignore parse errors */ }
								}
							}
						} );
						updateCounters( res.data.count );
					}
				},
			} );
		} );
	}

	// =========================================================================
	// Image overlay — single product DOM repositioning
	// =========================================================================

	/**
	 * Move the single-product overlay button into .woocommerce-product-gallery.
	 *
	 * The button is output via woocommerce_before_single_product_summary at
	 * priority 30 (after the gallery renders at priority 20). That places it
	 * correctly in the DOM order, but outside the gallery element. Moving it
	 * inside the gallery gives the CSS overlay anchor it needs, and works
	 * across all themes regardless of where woocommerce_product_thumbnails fires.
	 */
	function bindOverlayPosition() {
		var $btn = $( '.cecomwishfw-btn--overlay.cecomwishfw-btn--single' );
		if ( ! $btn.length ) {
			return;
		}
		var $gallery = $( '.woocommerce-product-gallery' ).first();
		if ( $gallery.length && ! $gallery.find( '.cecomwishfw-btn--overlay' ).length ) {
			$gallery.append( $btn );
		}
	}

	// =========================================================================
	// Copy share URL — .cecomwishfw-copy-url
	// =========================================================================

	/**
	 * Copy the wishlist share URL to the clipboard when the Copy button is clicked.
	 *
	 * Uses the Clipboard API (supported in all modern browsers) with a textarea
	 * fallback for older environments. Shows cfg.i18n.linkCopied as a toast and
	 * adds the .copied class to the button for a brief visual confirmation.
	 */
	function bindCopyUrl() {
		$( document ).on( 'click', '.cecomwishfw-copy-url', function () {
			var $btn    = $( this );
			var target  = $btn.data( 'clipboard-target' );
			var $input  = target ? $( target ) : $btn.prev( '.cecomwishfw-share-url-input' );
			var url     = $input.length ? $input.val() : '';

			if ( ! url ) {
				return;
			}

			var done = function () {
				showToast( ( cfg.i18n && cfg.i18n.linkCopied ) ? cfg.i18n.linkCopied : 'Link copied!', 'success' );
				$btn.addClass( 'copied' );
				setTimeout( function () { $btn.removeClass( 'copied' ); }, 2000 );
			};

			if ( navigator.clipboard && navigator.clipboard.writeText ) {
				navigator.clipboard.writeText( url ).then( done ).catch( function () {
					fallbackCopy( url );
					done();
				} );
			} else {
				fallbackCopy( url );
				done();
			}
		} );
	}

	// =========================================================================
	// Regenerate share token — .cecomwishfw-regenerate-token (fsh-5)
	// =========================================================================

	/**
	 * Click handler for the "Regenerate link" button on the wishlist page.
	 *
	 * Calls wp_ajax_cecomwishfw_regenerate_token. On success, updates the share
	 * URL input value so the user immediately sees the new link without reloading.
	 * Shows a toast for both success and error outcomes.
	 */
	function bindRegenerateToken() {
		$( document ).on( 'click', '.cecomwishfw-regenerate-token', function () {
			var $btn  = $( this );
			var nonce = $btn.data( 'nonce' );

			if ( ! cfg.ajaxUrl ) {
				return;
			}

			$btn.prop( 'disabled', true );

			$.post( cfg.ajaxUrl, {
				action:      'cecomwishfw_regenerate_token',
				_ajax_nonce: nonce,
			}, function ( res ) {
				if ( res.success ) {
					// Update every share URL input on the page (normally just one).
					$( '.cecomwishfw-share-url-input' ).val( res.data.share_url );
					showToast( ( cfg.i18n && cfg.i18n.linkRegenerated ) ? cfg.i18n.linkRegenerated : 'Link regenerated!', 'success' );
				} else {
					showToast( cfg.i18n && cfg.i18n.error ? cfg.i18n.error : 'Something went wrong.', 'error' );
				}
			} ).always( function () {
				$btn.prop( 'disabled', false );
			} );
		} );
	}

	// =========================================================================
	// Hide WooCommerce "View Cart" link when redirect-to-checkout is active
	// =========================================================================

	/**
	 * Mark the wishlist table/card wrappers when redirect-to-checkout is enabled.
	 *
	 * WooCommerce injects <a class="added_to_cart wc-forward"> after every
	 * successful AJAX add-to-cart. When our redirect-to-checkout setting is on
	 * the page navigates away immediately, so that link would flash on screen
	 * for a split second. Adding .cecomwishfw--redirect-checkout on DOM-ready
	 * (before any click) lets the CSS rule hide it pre-emptively.
	 */
	function markRedirectCheckoutWrappers() {
		if ( ! cfg.settings || ! cfg.settings.redirectCheckout ) {
			return;
		}
		$( '.cecomwishfw-table-wrap, .cecomwishfw-cards-wrap' ).addClass( 'cecomwishfw--redirect-checkout' );
	}

	// =========================================================================
	// Button-state hydration — restores real wishlist state on cached pages
	// =========================================================================

	/**
	 * Fetch the real wishlist item count via a single AJAX call and update all
	 * counter badges on the page. Allows the counter shortcode output to be
	 * cached safely (always rendered as 0) while showing the correct per-user
	 * count after page load. Silently no-ops when no counter element is present.
	 */
	function hydrateCounterState() {
		var $counters = $( COUNTER_EL );
		if ( ! $counters.length || ! cfg.nonce || ! cfg.ajaxUrl ) { return; }
		$.ajax( {
			url:    cfg.ajaxUrl,
			method: 'POST',
			data:   { action: 'cecomwishfw_get_count', _ajax_nonce: cfg.nonce },
			success: function ( res ) {
				if ( res.success && res.data && typeof res.data.count !== 'undefined' ) {
					updateCounters( res.data.count );
				}
			},
		} );
	}

	/**
	 * Fetch the wishlist status for every button on the current page and update
	 * the DOM to reflect the real per-user state.
	 *
	 * Product pages and shop loops are served from a full-page cache with all
	 * buttons in the default (inactive) state. This function runs once on
	 * DOMContentLoaded, collects the product IDs of every .cecomwishfw-btn on
	 * the page, and makes a single AJAX call to cecomwishfw_get_status. The
	 * response is then used to toggle the active state and repopulate
	 * data-wishlisted-variations so subsequent swatch changes still work
	 * without an extra round-trip (existing updateButtonVariation() logic).
	 *
	 * Silently no-ops when: no buttons are present, cfg.nonce is absent (e.g.
	 * a very aggressive cache stripped the wp_localize_script output), or the
	 * visitor has no items in any wishlist (server returns all false).
	 */
	function hydrateButtonStates() {
		var $btns = $( WISHLIST_BTN );
		if ( ! $btns.length || ! cfg.nonce || ! cfg.ajaxUrl ) {
			return;
		}

		var productIds = [];
		$btns.each( function () {
			var pid = parseInt( $( this ).data( 'product-id' ), 10 );
			if ( pid && productIds.indexOf( pid ) === -1 ) {
				productIds.push( pid );
			}
		} );

		if ( ! productIds.length ) {
			return;
		}

		$.ajax( {
			url:    cfg.ajaxUrl,
			method: 'POST',
			data:   {
				action:       'cecomwishfw_get_status',
				_ajax_nonce:  cfg.nonce,
				product_ids:  productIds,
			},
			success: function ( res ) {
				if ( ! res.success || ! res.data ) {
					return;
				}
				$( WISHLIST_BTN ).each( function () {
					var $btn   = $( this );
					var pid    = parseInt( $btn.data( 'product-id' ), 10 );
					var status = ( pid && res.data[ pid ] ) ? res.data[ pid ] : null;
					if ( ! status ) {
						return;
					}
					// Repopulate wishlisted-variations so swatch changes still
					// work correctly without an extra AJAX call.
					if ( Array.isArray( status.variation_ids ) ) {
						$btn.attr( 'data-wishlisted-variations', JSON.stringify( status.variation_ids ) );
					}
					var variationId = parseInt( $btn.data( 'variation-id' ), 10 ) || 0;
					var inWishlist  = variationId > 0
						? ( Array.isArray( status.variation_ids ) && status.variation_ids.indexOf( variationId ) !== -1 )
						: !! status.in_wishlist;
					if ( inWishlist ) {
						updateButtonState( $btn, true );
					}
				} );
			},
		} );
	}

	/**
	 * Textarea-based clipboard fallback for environments without Clipboard API.
	 *
	 * @param {string} text
	 */
	function fallbackCopy( text ) {
		var ta        = document.createElement( 'textarea' );
		ta.value      = text;
		ta.style.position = 'fixed';
		ta.style.opacity  = '0';
		document.body.appendChild( ta );
		ta.select();
		try { document.execCommand( 'copy' ); } catch ( e ) { /* silent */ }
		document.body.removeChild( ta );
	}

	// =========================================================================
	// Bootstrap
	// =========================================================================

	/* istanbul ignore next -- module export for Jest */
	if ( typeof module !== 'undefined' && module.exports ) {
		module.exports = {
			init,
			updateButtonState,
			updateCounters,
			showToast,
			bindToggleClick,
			bindVariationEvents,
			updateButtonVariation,
			bindRemoveClick,
			bindCopyUrl,
			fallbackCopy,
			markRedirectCheckoutWrappers,
			hydrateCounterState,
			hydrateButtonStates,
		};
	} else {
		$( function () {
			init();
		} );
	}

} )( jQuery, window.cecomwishfwFrontend || {} );
