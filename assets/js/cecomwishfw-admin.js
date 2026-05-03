/**
 * CECOM Wishlist for WooCommerce — Admin JavaScript
 *
 * Handles all admin settings page interactions:
 *   - Client-side tab switching (no page reload, history.pushState for URL sync)
 *   - AJAX settings save (no page reload)
 *   - Bootstrap Icons picker for custom icon
 *   - Toast notifications (success and error)
 *
 * Bootstrapped by wp_localize_script as window.cecomwishfwAdmin.
 * jQuery and Bootstrap are available globally via WP core.
 *
 * @package Cecomwishfw
 */

( function ( $, data ) {
	'use strict';

	/* global wp */

	var CecomwishfwAdmin = {

		/**
		 * Initialize the admin UI.
		 *
		 * @return {void}
		 */
		init: function () {
			this.bindTabSwitching();
			this.bindAjaxSave();
			this.bindIconPicker();
			this.bindAppearanceToggle();
			this.bindAlphaPicker();
			this.bindDimRanges();
			this.bindPaddingGroup();
			this.bindColorReset();
			this.bindSettingsReset();
			this.bindTooltips();
			this.bindShortcodeVisibility();
			this.bindCopyShortcode();
			// Show a toast when the page was reached via a traditional (non-AJAX)
			// form save redirect — PHP injects data.initialNotice in that case.
			if ( data.initialNotice && data.initialNotice.message ) {
				this.showToast( data.initialNotice.message, data.initialNotice.type || 'success' );
			}
		},

		// =====================================================================
		// Tab switching — client-side, no page reload
		// =====================================================================

		/**
		 * Intercept sidebar tab clicks and swap panels client-side.
		 *
		 * All panels are pre-rendered by settings.php; inactive ones carry .d-none.
		 * This handler hides the current panel, shows the target, updates .active
		 * state on the sidebar, pushes a new history entry so the URL stays in
		 * sync (bookmarkable), and closes the mobile offcanvas drawer if open.
		 *
		 * The anchor hrefs remain valid (?tab=X) so the page still works without
		 * JS — clicks fall through to a normal navigation.
		 *
		 * @return {void}
		 */
		bindTabSwitching: function () {
			$( document ).on( 'click', '.admin-tab[data-panel]', function ( e ) {
				var $tab     = $( this );
				var panelKey = $tab.attr( 'data-panel' );

				if ( ! panelKey ) {
					return;
				}

				// Allow modifier-click / middle-click / ctrl-click to open in a new tab
				// — same UX people expect from any nav link.
				if ( e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || 1 !== e.which ) {
					return;
				}

				e.preventDefault();

				var $targetPanel = $( '.tab-panel[data-panel="' + panelKey + '"]' );
				if ( ! $targetPanel.length ) {
					return;
				}

				// Swap active state on tabs.
				$( '.admin-tab[data-panel]' )
					.removeClass( 'active' )
					.attr( 'aria-selected', 'false' );
				$tab.addClass( 'active' ).attr( 'aria-selected', 'true' );

				// Swap visible panel.
				$( '.tab-panel' ).addClass( 'd-none' );
				$targetPanel.removeClass( 'd-none' );

				// Keep the URL in sync so refresh/bookmark lands on the same tab.
				if ( window.history && window.history.pushState ) {
					try {
						var url = new URL( window.location.href );
						url.searchParams.set( 'tab', panelKey );
						// Strip ?updated=1 so the "Settings saved" notice doesn't
						// keep resurfacing after the user navigates away.
						url.searchParams.delete( 'updated' );
						window.history.pushState( { tab: panelKey }, '', url.toString() );
					} catch ( err ) {
						// Older browsers: ignore — the visual state is already correct.
					}
				}

				// Close the mobile offcanvas drawer if it was open.
				var offcanvasEl = document.getElementById( 'cecomwishfwSidebar' );
				if ( offcanvasEl && typeof window.bootstrap !== 'undefined' && window.bootstrap.Offcanvas ) {
					var oc = window.bootstrap.Offcanvas.getInstance( offcanvasEl );
					if ( oc ) {
						oc.hide();
					}
				}
			} );

			// Handle browser back/forward so the panel follows the URL.
			$( window ).on( 'popstate', function () {
				var params   = new URLSearchParams( window.location.search );
				var panelKey = params.get( 'tab' ) || 'general';
				var $tab     = $( '.admin-tab[data-panel="' + panelKey + '"]' );
				if ( $tab.length ) {
					// Fire a synthetic click that will run through bindTabSwitching above.
					// preventDefault is already called inside the handler.
					$tab.trigger( 'click' );
				}
			} );
		},

		// =====================================================================
		// AJAX settings save — fad-7.2
		// =====================================================================

		/**
		 * Intercept settings form submit and save via AJAX instead of page reload.
		 *
		 * Uses a class selector (.cecomwishfw-settings-form) because multiple tab
		 * forms are pre-rendered on the same page (one per form-tab). The submit
		 * event matches whichever form the user actually clicked Save on.
		 *
		 * @return {void}
		 */
		bindAjaxSave: function () {
			$( document ).on( 'submit', '.cecomwishfw-settings-form', function ( e ) {
				e.preventDefault();

				var $form       = $( this );
				var $btn        = $form.find( '.cecomwishfw-save-btn' );
				var savingLabel = data.i18n && data.i18n.saving      ? data.i18n.saving      : 'Saving…';
				var savedLabel  = data.i18n && data.i18n.saveSettings ? data.i18n.saveSettings : 'Save Settings';

				// Disable button + show saving state.
				$btn.prop( 'disabled', true ).html(
					'<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>' +
					savingLabel
				);

				var formData = new FormData( this );
				formData.append( 'action',      'cecomwishfw_save_settings' );
				formData.append( '_ajax_nonce', ( data && data.nonce ) ? data.nonce : '' );

				fetch( data.ajaxUrl, {
					method:      'POST',
					credentials: 'same-origin',
					body:        formData,
				} )
					.then( function ( response ) {
						return response.json();
					} )
					.then( function ( res ) {
						if ( res && res.success ) {
							CecomwishfwAdmin.showToast(
								res.data.message || ( data.i18n && data.i18n.saved ) || 'Settings saved.',
								'success'
							);
						} else {
							var msg = ( res && res.data && res.data.message )
								? res.data.message
								: ( data.i18n && data.i18n.error ? data.i18n.error : 'An error occurred.' );
							CecomwishfwAdmin.showToast( msg, 'error' );
						}
					} )
					.catch( function () {
						CecomwishfwAdmin.showToast( ( data.i18n && data.i18n.error ) || 'An error occurred.', 'error' );
					} )
					.finally( function () {
						$btn.prop( 'disabled', false ).html(
							'<i class="bi bi-floppy me-1" aria-hidden="true"></i>' + savedLabel
						);
					} );
			} );
		},

		/**
		 * Show a Bootstrap Toast notification matching the cecom-plugin-admin-ui-framework.
		 *
		 * Structure mirrors the framework reference (cecom-plugin-admin-ui-framework.html):
		 *   .toast-header — colored square indicator + plugin name + close button
		 *   .toast-body   — notification message
		 *
		 * Uses Bootstrap's Toast JS API (bootstrap.bundle.min.js is loaded as a
		 * dependency of cecomwishfw-admin). The toast container is created once and
		 * reused; each toast is removed from the DOM after it hides.
		 *
		 * @param {string} message Notification text.
		 * @param {string} type    'success' (default) | 'error'
		 * @return {void}
		 */
		showToast: function ( message, type ) {
			type = type || 'success';

			// Create or reuse the positioning container (bottom-center, above WP chrome).
			var containerId = 'cecomwishfw-toast-container';
			var container   = document.getElementById( containerId );
			if ( ! container ) {
				container           = document.createElement( 'div' );
				container.id        = containerId;
				container.className = 'toast-container position-fixed top-0 end-0 mt-4 p-3';
				container.style.zIndex = '99999';
				document.body.appendChild( container );
			}

			// Colored square: bg-primary for success, bg-danger for error — matches framework.
			var colorClass = 'error' === type ? 'bg-danger' : 'bg-primary';

			// Safely escape message and static strings for HTML insertion.
			var safeMessage = $( '<span>' ).text( message ).html();

			var toastEl       = document.createElement( 'div' );
			toastEl.className = 'toast';
			toastEl.setAttribute( 'role', 'alert' );
			toastEl.setAttribute( 'aria-live', 'assertive' );
			toastEl.setAttribute( 'aria-atomic', 'true' );
			toastEl.innerHTML =
				'<div class="toast-header">' +
					'<span class="rounded me-2 d-inline-block flex-shrink-0 ' + colorClass + '"' +
					'      style="width:20px;height:20px;" aria-hidden="true"></span>' +
					'<strong class="me-auto">CECOM Wishlist</strong>' +
					'<button type="button" class="btn-close" data-bs-dismiss="toast"' +
					'        aria-label="Close"></button>' +
				'</div>' +
				'<div class="toast-body">' + safeMessage + '</div>';

			container.appendChild( toastEl );

			if ( typeof window.bootstrap !== 'undefined' && window.bootstrap.Toast ) {
				var bsToast = new window.bootstrap.Toast( toastEl, { delay: 3000 } );
				bsToast.show();
				// Remove from DOM once hidden so the container stays clean.
				toastEl.addEventListener( 'hidden.bs.toast', function () {
					toastEl.remove();
				} );
			}
		},

		// =====================================================================
		// Bootstrap Icons picker — replaces WP media picker
		// =====================================================================

		/**
		 * Bootstrap Icons picker for the Appearance tab.
		 *
		 * Opens a Bootstrap modal with a searchable grid of all bundled icons.
		 * Selecting an icon stores its class name (e.g. 'bi-heart-fill') in a
		 * hidden input and updates the adjacent preview element.
		 *
		 * Icons are supplied via window.cecomwishfwAdmin.icons (injected by PHP
		 * from bootstrap-icons.json). The modal is created lazily on first open.
		 *
		 * @return {void}
		 */
		bindIconPicker: function () {
			var $modal       = null;
			var activeTarget  = '';
			var activePreview = '';
			var allIcons      = Array.isArray( data.icons ) ? data.icons : [];
			var MAX_SHOWN     = 240;

			/**
			 * Build the picker modal HTML and append it to <body> once.
			 *
			 * @return {jQuery}
			 */
			function getOrCreateModal() {
				if ( $modal ) {
					return $modal;
				}

				var title       = ( data.i18n && data.i18n.chooseIcon )  ? data.i18n.chooseIcon  : 'Choose Icon';
				var placeholder = ( data.i18n && data.i18n.searchIcons ) ? data.i18n.searchIcons : 'Search icons…';

				$modal = $( [
					'<div class="modal fade" id="cecomwishfwIconModal" tabindex="-1"',
					'     aria-labelledby="cecomwishfwIconModalLabel" aria-hidden="true">',
					'  <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">',
					'    <div class="modal-content">',
					'      <div class="modal-header py-2 border-bottom">',
					'        <h5 class="modal-title fs-6 fw-semibold" id="cecomwishfwIconModalLabel">' + title + '</h5>',
					'        <button type="button" class="btn-close" data-bs-dismiss="modal"',
					'                aria-label="Close"></button>',
					'      </div>',
					'      <div class="modal-body p-3">',
					'        <input type="search"',
					'               class="form-control form-control-sm mb-3 cecomwishfw-icon-search"',
					'               placeholder="' + placeholder + '"',
					'               autocomplete="off">',
					'        <div class="cecomwishfw-icon-grid" role="listbox"',
					'             aria-label="' + title + '"></div>',
					'        <p class="cecomwishfw-icon-count text-secondary small mt-2 mb-0"></p>',
					'      </div>',
					'    </div>',
					'  </div>',
					'</div>',
				].join( '\n' ) );

				$( 'body' ).append( $modal );

				// Filter icons as the user types.
				$modal.on( 'input', '.cecomwishfw-icon-search', function () {
					renderIcons( $( this ).val().trim().toLowerCase() );
				} );

				// Select an icon from the grid.
				$modal.on( 'click', '.cecomwishfw-icon-item', function () {
					var iconClass = String( $( this ).data( 'icon' ) );

					// Update the hidden input.
					$( '#' + activeTarget ).val( iconClass );

					// Update the preview <i> inside the preview wrapper.
					$( '#' + activePreview ).find( 'i' ).attr( 'class', 'bi ' + iconClass );

					// Reveal the Clear button for this picker row.
					$( '.cecomwishfw-icon-picker-btn[data-target="' + activeTarget + '"]' )
						.closest( '.d-flex' )
						.find( '.cecomwishfw-icon-clear-btn' )
						.show();

					// Close the modal.
					if ( typeof window.bootstrap !== 'undefined' && window.bootstrap.Modal ) {
						var bsModal = window.bootstrap.Modal.getInstance( $modal[ 0 ] );
						if ( bsModal ) {
							bsModal.hide();
						}
					}
				} );

				return $modal;
			}

			/**
			 * Render up to MAX_SHOWN icons matching the filter string into the grid.
			 *
			 * @param {string} filter Lowercase search string.
			 * @return {void}
			 */
			function renderIcons( filter ) {
				var filtered = filter
					? allIcons.filter( function ( ic ) { return ic.indexOf( filter ) !== -1; } )
					: allIcons;

				var shown = filtered.slice( 0, MAX_SHOWN );
				var html  = '';

				shown.forEach( function ( iconName ) {
					html += '<button type="button" class="cecomwishfw-icon-item"' +
						' data-icon="' + iconName + '"' +
						' title="' + iconName + '"' +
						' role="option"' +
						' aria-label="' + iconName + '">' +
						'<i class="bi ' + iconName + '" aria-hidden="true"></i>' +
						'</button>';
				} );

				var $m = getOrCreateModal();
				$m.find( '.cecomwishfw-icon-grid' ).html( html );
				$m.find( '.cecomwishfw-icon-count' ).text(
					filtered.length > MAX_SHOWN
						? ( data.i18n.iconPickerCount || 'Showing %1$d of %2$d — refine your search' )
							.replace( '%1$d', MAX_SHOWN ).replace( '%2$d', filtered.length )
						: filtered.length + ' ' + ( 1 === filtered.length
							? ( data.i18n.iconSingular || 'icon' )
							: ( data.i18n.iconPlural   || 'icons' ) )
				);
			}

			// Open the picker modal.
			$( document ).on( 'click', '.cecomwishfw-icon-picker-btn', function () {
				activeTarget  = String( $( this ).data( 'target' ) );
				activePreview = String( $( this ).data( 'preview' ) );

				var $m = getOrCreateModal();
				$m.find( '.cecomwishfw-icon-search' ).val( '' );
				renderIcons( '' );

				if ( typeof window.bootstrap !== 'undefined' && window.bootstrap.Modal ) {
					window.bootstrap.Modal.getOrCreateInstance( $m[ 0 ] ).show();
				}
			} );

			// Clear the selected icon and revert preview to the default heart.
			$( document ).on( 'click', '.cecomwishfw-icon-clear-btn', function () {
				var targetId  = String( $( this ).data( 'target' ) );
				var previewId = String( $( this ).data( 'preview' ) );
				$( '#' + targetId ).val( '' );
				$( '#' + previewId ).find( 'i' ).attr( 'class', 'bi bi-heart' );
				$( this ).hide();
			} );
		},

		// =====================================================================
		// Appearance type toggle — show/hide custom-style fields per context
		// =====================================================================

		/**
		 * Toggle the visibility of the custom-style field wrapper based on the
		 * current value of the appearance-type <select> for each context.
		 *
		 * @return {void}
		 */
		bindAppearanceToggle: function () {
			var sync = function ( $select ) {
				var ctx     = String( $select.data( 'context' ) || '' );
				var isCust  = 'custom' === String( $select.val() || '' );
				var $target = $( '.cecomwishfw-custom-style[data-context="' + ctx + '"]' );
				if ( ! $target.length ) {
					return;
				}
				if ( isCust ) {
					$target.removeAttr( 'hidden' );
				} else {
					$target.attr( 'hidden', 'hidden' );
				}
			};

			// Initial pass: sync current state for every appearance-type select.
			$( '.cecomwishfw-appearance-type' ).each( function () {
				sync( $( this ) );
			} );

			// Delegated change handler — re-syncs whenever the user picks a new type.
			$( document ).on( 'change', '.cecomwishfw-appearance-type', function () {
				sync( $( this ) );
			} );
		},

		// =====================================================================
		// Alpha picker — color + opacity in a single Bootstrap-dropdown picker
		// =====================================================================

		/**
		 * Refresh the visual state of a .cecomwishfw-alpha-picker wrapper so
		 * the swatch, color input, and range slider all match the current
		 * hidden-store values. Used after programmatic updates (e.g. the reset
		 * button fires this path) and on initial DOM ready.
		 *
		 * @param {jQuery} $wrap The .cecomwishfw-alpha-picker element.
		 * @return {void}
		 */
		refreshAlphaPicker: function ( $wrap ) {
			if ( ! $wrap || ! $wrap.length ) {
				return;
			}
			var color      = String( $wrap.find( '.cecomwishfw-alpha-color-store' ).val() || '' );
			var alpha      = String( $wrap.find( '.cecomwishfw-alpha-opacity-store' ).val() || '' );
			var defaultHex = String( $wrap.find( '.cecomwishfw-reset-color' ).data( 'default' ) || '#000000' );
			var dispColor  = '' !== color ? color : defaultHex;
			var dispAlpha  = '' !== alpha ? alpha : '1';
			$wrap.find( '.cecomwishfw-alpha-swatch-fill' ).css( {
				'background-color': dispColor,
				'opacity':          dispAlpha,
			} );
			$wrap.find( '.cecomwishfw-alpha-color-input' ).val( dispColor );
			$wrap.find( '.cecomwishfw-alpha-range-input' ).val( dispAlpha );
			$wrap.find( '.cecomwishfw-alpha-display' ).text( dispAlpha );
		},

		/**
		 * Wire the alpha-picker widgets: color change, alpha change, and the
		 * back-channel refresh when the hidden color store is updated from the
		 * outside (e.g. by the reset-color button). The Bootstrap dropdown
		 * already handles open/close via data-bs-auto-close="outside".
		 *
		 * @return {void}
		 */
		bindAlphaPicker: function () {
			var self = this;

			// Color picker change → update hidden color store + swatch.
			$( document ).on( 'input change', '.cecomwishfw-alpha-color-input', function () {
				var $wrap = $( this ).closest( '.cecomwishfw-alpha-picker' );
				var val   = String( $( this ).val() || '' );
				$wrap.find( '.cecomwishfw-alpha-color-store' ).val( val );
				$wrap.find( '.cecomwishfw-alpha-swatch-fill' ).css( 'background-color', val );
			} );

			// Range slider change → update hidden opacity store + swatch + numeric display.
			$( document ).on( 'input change', '.cecomwishfw-alpha-range-input', function () {
				var $wrap = $( this ).closest( '.cecomwishfw-alpha-picker' );
				var val   = String( $( this ).val() || '1' );
				$wrap.find( '.cecomwishfw-alpha-opacity-store' ).val( val );
				$wrap.find( '.cecomwishfw-alpha-swatch-fill' ).css( 'opacity', val );
				$wrap.find( '.cecomwishfw-alpha-display' ).text( val );
			} );

			// Resync when the hidden color store is updated externally — e.g. reset button.
			$( document ).on( 'change', '.cecomwishfw-alpha-color-store', function () {
				self.refreshAlphaPicker( $( this ).closest( '.cecomwishfw-alpha-picker' ) );
			} );

			// Initial sync for every picker on the page.
			$( '.cecomwishfw-alpha-picker' ).each( function () {
				self.refreshAlphaPicker( $( this ) );
			} );
		},

		// =====================================================================
		// CSS dimension range sliders (padding, font-size, border radius, width)
		// =====================================================================

		/**
		 * Sync each dimension range slider with its hidden {id}=settings[$id]
		 * store. Slider value 0 collapses to an empty stored value, preserving
		 * the existing "leave blank to use theme/plugin default" semantics and
		 * the sanitize regex. Slider value > 0 is committed as "{N}px".
		 *
		 * @return {void}
		 */
		bindDimRanges: function () {
			var refresh = function ( $range ) {
				var val        = String( $range.val() || '0' );
				var targetId   = String( $range.data( 'target' ) || '' );
				var $display   = $range.siblings( '.cecomwishfw-dim-display' );
				var $store     = $( '#' + targetId );
				var numeric    = parseInt( val, 10 );
				if ( isNaN( numeric ) || numeric <= 0 ) {
					$store.val( '' );
					$display.text(
						( data.i18n && data.i18n.dimDefault ) ? data.i18n.dimDefault : 'Default'
					);
				} else {
					$store.val( numeric + 'px' );
					$display.text( numeric + 'px' );
				}
			};

			$( document ).on( 'input change', '.cecomwishfw-dim-range', function () {
				refresh( $( this ) );
			} );
		},

		// =====================================================================
		// Padding group — four side-by-side sliders combined into one shorthand
		// =====================================================================

		/**
		 * Combine the four padding sliders inside a .cecomwishfw-padding-group
		 * wrapper into a single "T R B L" CSS shorthand value, store it in the
		 * group's hidden input, and refresh each slider's "Npx / Default"
		 * label. When all four sliders are at 0 the hidden input collapses to
		 * an empty string — same fall-through-to-theme-default semantic the
		 * single-value dim-range helper already uses.
		 *
		 * @return {void}
		 */
		bindPaddingGroup: function () {
			var defaultLabel = ( data.i18n && data.i18n.dimDefault ) ? data.i18n.dimDefault : 'Default';

			var combine = function ( $group ) {
				var sides = [ 'top', 'right', 'bottom', 'left' ];
				var nums  = [];
				var allZero = true;
				for ( var i = 0; i < sides.length; i++ ) {
					var $slider = $group.find( '.cecomwishfw-padding-slider[data-side="' + sides[ i ] + '"]' );
					var n       = parseInt( $slider.val(), 10 );
					if ( isNaN( n ) || n < 0 ) {
						n = 0;
					}
					nums.push( n );
					if ( n !== 0 ) {
						allZero = false;
					}
				}
				var $store = $group.find( '> input[type="hidden"]' ).first();
				if ( allZero ) {
					$store.val( '' );
				} else {
					$store.val( nums[ 0 ] + 'px ' + nums[ 1 ] + 'px ' + nums[ 2 ] + 'px ' + nums[ 3 ] + 'px' );
				}
			};

			$( document ).on( 'input change', '.cecomwishfw-padding-slider', function () {
				var $slider = $( this );
				var $cell   = $slider.closest( '.cecomwishfw-padding-cell' );
				var n       = parseInt( $slider.val(), 10 );
				if ( isNaN( n ) || n < 0 ) {
					n = 0;
				}
				$cell.find( '.cecomwishfw-padding-display' ).text( 0 === n ? defaultLabel : n + 'px' );
				combine( $slider.closest( '.cecomwishfw-padding-group' ) );
			} );
		},

		// =====================================================================
		// Per-color "reset to default" icon button
		// =====================================================================

		/**
		 * Reset an individual color picker to the default hex carried in the
		 * sibling button's data-default attribute. Dispatches a change event on
		 * the input so any listeners (e.g. live preview) stay in sync.
		 *
		 * @return {void}
		 */
		bindColorReset: function () {
			$( document ).on( 'click', '.cecomwishfw-reset-color', function () {
				var $btn      = $( this );
				var targetId  = String( $btn.data( 'target' ) || '' );
				var def       = String( $btn.data( 'default' ) || '' );
				if ( ! targetId || ! def ) {
					return;
				}
				var el = document.getElementById( targetId );
				if ( ! el ) {
					return;
				}
				el.value = def;
				el.dispatchEvent( new Event( 'change', { bubbles: true } ) );
			} );
		},

		// =====================================================================
		// "Reset Defaults" — wipe stored settings via AJAX
		// =====================================================================

		/**
		 * Handle the global reset button: open a Bootstrap confirmation modal,
		 * then on confirm POST to the reset AJAX action, show a toast, and
		 * reload the page so every form field shows the re-seeded default values.
		 *
		 * The modal follows the "Modal Choice" pattern from the plugin design
		 * system. It explicitly states that no wishlist data will be deleted —
		 * only plugin settings are restored to their defaults.
		 *
		 * @return {void}
		 */
		bindSettingsReset: function () {
			// Build the confirmation modal once and append it to the body.
			if ( ! $( '#cecomwishfwResetModal' ).length ) {
				var title      = ( data.i18n && data.i18n.resetModalTitle )  ? data.i18n.resetModalTitle  : 'Reset All Settings?';
				var body       = ( data.i18n && data.i18n.resetModalBody )   ? data.i18n.resetModalBody   : 'Your wishlist data will not be affected \u2014 only plugin settings will be restored to their defaults.';
				var confirmTxt = ( data.i18n && data.i18n.resetConfirmBtn )  ? data.i18n.resetConfirmBtn  : 'Yes, Reset Settings';
				var cancelTxt  = ( data.i18n && data.i18n.resetCancelBtn )   ? data.i18n.resetCancelBtn   : 'Cancel';

				$( 'body' ).append( [
					'<div class="modal fade" id="cecomwishfwResetModal" tabindex="-1" role="dialog"',
					'     aria-labelledby="cecomwishfwResetModalLabel" aria-hidden="true">',
					'  <div class="modal-dialog modal-dialog-centered">',
					'    <div class="modal-content rounded-3 shadow">',
					'      <div class="modal-body p-4 text-center">',
					'        <i class="bi bi-arrow-counterclockwise fs-1 mb-2 d-block" aria-hidden="true"></i>',
					'        <h5 class="fw-semibold mb-2" id="cecomwishfwResetModalLabel">' + title + '</h5>',
					'        <p class="text-secondary small mb-0">' + body + '</p>',
					'      </div>',
					'      <div class="modal-footer flex-nowrap p-0">',
					'        <button type="button"',
					'                class="btn btn-lg btn-link fs-6 text-decoration-none text-danger col-6 py-3 m-0 rounded-0 border-end cecomwishfw-reset-confirm-btn">',
					'          <strong>' + confirmTxt + '</strong>',
					'        </button>',
					'        <button type="button"',
					'                class="btn btn-lg btn-link fs-6 text-decoration-none col-6 py-3 m-0 rounded-0"',
					'                data-bs-dismiss="modal">' + cancelTxt + '</button>',
					'      </div>',
					'    </div>',
					'  </div>',
					'</div>',
				].join( '\n' ) );
			}

			var $modal      = $( '#cecomwishfwResetModal' );
			var $pendingBtn = null;

			// Open modal when the reset button is clicked, remembering which button triggered it.
			$( document ).on( 'click', '.cecomwishfw-reset-all-btn', function () {
				$pendingBtn = $( this );
				window.bootstrap.Modal.getOrCreateInstance( $modal[ 0 ] ).show();
			} );

			// On modal confirm: close the modal, then run the AJAX reset.
			$( document ).on( 'click', '#cecomwishfwResetModal .cecomwishfw-reset-confirm-btn', function () {
				window.bootstrap.Modal.getOrCreateInstance( $modal[ 0 ] ).hide();

				if ( ! $pendingBtn ) {
					return;
				}

				var $btn         = $pendingBtn;
				$pendingBtn      = null;
				var originalHtml = $btn.html();
				var resettingTxt = ( data.i18n && data.i18n.resetting ) ? data.i18n.resetting : 'Resetting...';

				$btn.prop( 'disabled', true ).html(
					'<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>' +
					resettingTxt
				);

				var formData = new FormData();
				formData.append( 'action',      'cecomwishfw_reset_settings' );
				formData.append( '_ajax_nonce', ( data && data.nonce ) ? data.nonce : '' );

				fetch( data.ajaxUrl, {
					method:      'POST',
					credentials: 'same-origin',
					body:        formData,
				} )
					.then( function ( response ) {
						return response.json();
					} )
					.then( function ( res ) {
						if ( res && res.success ) {
							CecomwishfwAdmin.showToast(
								res.data.message || ( data.i18n && data.i18n.resetDone ) || 'Settings restored to defaults.',
								'success'
							);
							// Reload shortly after the toast so the user sees the confirmation.
							window.setTimeout( function () {
								window.location.reload();
							}, 900 );
						} else {
							var msg = ( res && res.data && res.data.message )
								? res.data.message
								: ( data.i18n && data.i18n.error ? data.i18n.error : 'An error occurred.' );
							CecomwishfwAdmin.showToast( msg, 'error' );
							$btn.prop( 'disabled', false ).html( originalHtml );
						}
					} )
					.catch( function () {
						CecomwishfwAdmin.showToast( ( data.i18n && data.i18n.error ) || 'An error occurred.', 'error' );
						$btn.prop( 'disabled', false ).html( originalHtml );
					} );
			} );
		},

		// =====================================================================
		// Bootstrap tooltips — initialize every [data-bs-toggle="tooltip"] icon
		// =====================================================================

		/**
		 * Walk every element flagged with `data-bs-toggle="tooltip"` and
		 * instantiate a Bootstrap Tooltip on it. The PHP helper
		 * cecomwishfw_label_tooltip() emits these elements next to every
		 * form label whose description was previously a `<div class="form-text">`.
		 *
		 * Bootstrap is bundled with the plugin and exposed on `window.bootstrap`
		 * (the same global the icon picker modal uses). If it's missing for any
		 * reason — defer / no-bundle — the call is a clean no-op so the admin
		 * page never breaks.
		 *
		 * @return {void}
		 */
		bindTooltips: function () {
			if ( typeof window.bootstrap === 'undefined' || ! window.bootstrap.Tooltip ) {
				return;
			}
			// Restrict to OUR question-mark icons (`.cecomwishfw-tooltip-icon`)
			// instead of the broader `[data-bs-toggle="tooltip"]` selector so we
			// never accidentally hijack a future Bootstrap demo / theme widget
			// that also uses the same data attribute.
			document.querySelectorAll( '.cecomwishfw-tooltip-icon[data-bs-toggle="tooltip"]' ).forEach( function ( el ) {
				// Skip if Bootstrap already attached an instance (idempotent re-init).
				if ( window.bootstrap.Tooltip.getInstance( el ) ) {
					return;
				}
				new window.bootstrap.Tooltip( el, {
					trigger:   'hover focus',
					placement: 'top',
					container: 'body',
				} );
			} );
		},

		// =====================================================================
		// Shortcode copy widget — General tab "Copy this shortcode" fields
		// =====================================================================

		/**
		 * Show / hide the [cecomwishfw_button] copy widget based on the
		 * Button position dropdown. The widget is only relevant when the
		 * user has chosen "Shortcode only" — otherwise the button auto-places
		 * itself via a WC hook and the shortcode field is just clutter.
		 *
		 * Driver dropdowns:
		 *   #button_position       → .cecomwishfw-shortcode-display[data-context="single"]
		 *   #loop_button_position  → .cecomwishfw-shortcode-display[data-context="loop"]
		 *
		 * @return {void}
		 */
		bindShortcodeVisibility: function () {
			var sync = function ( $select, context ) {
				var $target = $( '.cecomwishfw-shortcode-display[data-context="' + context + '"]' );
				if ( ! $target.length ) {
					return;
				}
				if ( 'shortcode_only' === String( $select.val() ) ) {
					$target.removeAttr( 'hidden' );
				} else {
					$target.attr( 'hidden', 'hidden' );
				}
			};

			// Initial pass + delegated change handler for both dropdowns.
			$( '#button_position' ).each( function () {
				sync( $( this ), 'single' );
			} );
			$( '#loop_button_position' ).each( function () {
				sync( $( this ), 'loop' );
			} );
			$( document ).on( 'change', '#button_position', function () {
				sync( $( this ), 'single' );
			} );
			$( document ).on( 'change', '#loop_button_position', function () {
				sync( $( this ), 'loop' );
			} );
		},

		/**
		 * Copy the shortcode from the sibling read-only input to the clipboard
		 * when the user clicks the "Copy" button next to it. Mirrors the
		 * frontend share-URL copy pattern (`navigator.clipboard.writeText`
		 * with a hidden-textarea fallback for older environments) and shows
		 * a toast confirmation via the existing showToast() helper.
		 *
		 * @return {void}
		 */
		bindCopyShortcode: function () {
			$( document ).on( 'click', '.cecomwishfw-copy-shortcode', function () {
				var $btn   = $( this );
				var $input = $btn.siblings( '.cecomwishfw-shortcode-input' ).first();
				var value  = $input.length ? String( $input.val() || '' ) : '';
				if ( '' === value ) {
					return;
				}

				var done = function () {
					CecomwishfwAdmin.showToast(
						( data.i18n && data.i18n.shortcodeCopied ) ? data.i18n.shortcodeCopied : 'Shortcode copied!',
						'success'
					);
					$btn.addClass( 'copied' );
					window.setTimeout( function () { $btn.removeClass( 'copied' ); }, 1500 );
				};

				if ( navigator.clipboard && navigator.clipboard.writeText ) {
					navigator.clipboard.writeText( value ).then( done ).catch( function () {
						// Fallback — select the input and use the deprecated execCommand.
						$input.trigger( 'focus' ).trigger( 'select' );
						try {
							document.execCommand( 'copy' );
							done();
						} catch ( err ) {
							CecomwishfwAdmin.showToast(
								( data.i18n && data.i18n.error ) ? data.i18n.error : 'Copy failed.',
								'error'
							);
						}
					} );
				} else {
					$input.trigger( 'focus' ).trigger( 'select' );
					try {
						document.execCommand( 'copy' );
						done();
					} catch ( err ) {
						CecomwishfwAdmin.showToast(
							( data.i18n && data.i18n.error ) ? data.i18n.error : 'Copy failed.',
							'error'
						);
					}
				}
			} );
		},
	};

	/* istanbul ignore next -- module export for Jest */
	if ( typeof module !== 'undefined' && module.exports ) {
		module.exports = CecomwishfwAdmin;
	} else {
		$( function () {
			CecomwishfwAdmin.init();
		} );
	}

} )( jQuery, window.cecomwishfwAdmin || {} );
