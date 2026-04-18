/**
 * CECOM Wishlist for WooCommerce — Deactivation Feedback Modal.
 *
 * Intercepts the Deactivate link on the Plugins page and shows a quick-feedback
 * dialog. The chosen reason is sent (fire-and-forget) to the plugin's server
 * before WordPress proceeds with the deactivation redirect.
 *
 * @global cecomwishfwFeedback  Localized PHP object with ajaxUrl, nonce, pluginBasename, iconUrl, i18n.
 * @package Cecomwishfw
 */

/* global cecomwishfwFeedback */

( function () {
	'use strict';

	var cfg          = window.cecomwishfwFeedback || {};
	var i18n         = cfg.i18n || {};
	var deactivateUrl = '';
	var _overlay     = null;

	/* ── Deactivation reasons ─────────────────────────────────────────────── */

	var REASONS = [
		{ value: 'temporary',       label: i18n.temporary       || 'This is a temporary deactivation for testing.' },
		{ value: 'not_working',     label: i18n.not_working     || "The plugin isn't working properly." },
		{ value: 'found_better',    label: i18n.found_better    || 'I found a better alternative plugin.' },
		{ value: 'missing_feature', label: i18n.missing_feature || "It's missing a specific feature." },
		{ value: 'other',           label: i18n.other           || 'Other' },
	];

	/* ── Helpers ──────────────────────────────────────────────────────────── */

	/**
	 * Escape a string for safe injection into innerHTML.
	 *
	 * @param {string} str
	 * @return {string}
	 */
	function escHtml( str ) {
		var div = document.createElement( 'div' );
		div.appendChild( document.createTextNode( str ) );
		return div.innerHTML;
	}

	/* ── Modal construction ───────────────────────────────────────────────── */

	function buildModal() {
		var overlay = document.createElement( 'div' );
		overlay.id  = 'cecomwishfw-dfm-overlay';

		var box = document.createElement( 'div' );
		box.id  = 'cecomwishfw-dfm-box';
		box.setAttribute( 'role', 'dialog' );
		box.setAttribute( 'aria-modal', 'true' );
		box.setAttribute( 'aria-labelledby', 'cecomwishfw-dfm-title' );

		/* Header */
		var header =
			'<div id="cecomwishfw-dfm-header">' +
				'<img src="' + escHtml( cfg.iconUrl || '' ) + '" alt="" id="cecomwishfw-dfm-icon" />' +
				'<span id="cecomwishfw-dfm-title">' + escHtml( i18n.title || 'Quick Feedback' ) + '</span>' +
				'<button type="button" id="cecomwishfw-dfm-close" aria-label="' + escHtml( i18n.close || 'Close' ) + '">' +
					'<svg width="18" height="18" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">' +
						'<path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-2.72 2.72a.75.75 0 1 0 1.06 1.06L10 11.06l2.72 2.72a.75.75 0 1 0 1.06-1.06L11.06 10l2.72-2.72a.75.75 0 0 0-1.06-1.06L10 8.94 7.28 6.22 6.28 5.22Z"/>' +
					'</svg>' +
				'</button>' +
			'</div>';

		/* Reasons list */
		var radioList = '';
		REASONS.forEach( function ( r, idx ) {
			radioList +=
				'<li>' +
					'<label>' +
						'<input type="radio" name="cecomwishfw_dfm_reason" value="' + escHtml( r.value ) + '"' + ( idx === 0 ? ' checked' : '' ) + ' /> ' +
						escHtml( r.label ) +
					'</label>' +
				'</li>';
		} );

		/* Body */
		var body =
			'<div id="cecomwishfw-dfm-body">' +
				'<p id="cecomwishfw-dfm-question">' +
					escHtml( i18n.question || 'If you have a moment, please share why you are deactivating CECOM Wishlist for WooCommerce:' ) +
				'</p>' +
				'<ul id="cecomwishfw-dfm-reasons">' + radioList + '</ul>' +
				'<div id="cecomwishfw-dfm-details-wrap" hidden>' +
					'<textarea id="cecomwishfw-dfm-details" rows="4" placeholder="' + escHtml( i18n.details_placeholder || 'Please tell us more details.' ) + '"></textarea>' +
				'</div>' +
			'</div>';

		/* Footer */
		var footer =
			'<div id="cecomwishfw-dfm-footer">' +
				'<button type="button" id="cecomwishfw-dfm-submit" class="button button-primary">' + escHtml( i18n.submit || 'Submit & Deactivate' ) + '</button>' +
				'<button type="button" id="cecomwishfw-dfm-skip"   class="button">' + escHtml( i18n.skip || 'Skip & Deactivate' ) + '</button>' +
			'</div>';

		box.innerHTML = header + body + footer;
		overlay.appendChild( box );
		document.body.appendChild( overlay );

		/* ── Events ── */

		/* Click outside box → close (cancel deactivation). */
		overlay.addEventListener( 'click', function ( e ) {
			if ( e.target === overlay ) {
				closeModal();
			}
		} );

		box.querySelector( '#cecomwishfw-dfm-close' ).addEventListener( 'click', closeModal );
		box.querySelector( '#cecomwishfw-dfm-skip'  ).addEventListener( 'click', doDeactivate );
		box.querySelector( '#cecomwishfw-dfm-submit' ).addEventListener( 'click', onSubmit );

		/* Show/hide textarea when "Other" is selected. */
		box.querySelectorAll( 'input[name="cecomwishfw_dfm_reason"]' ).forEach( function ( radio ) {
			radio.addEventListener( 'change', function () {
				var detailsWrap = box.querySelector( '#cecomwishfw-dfm-details-wrap' );
				detailsWrap.hidden = ( this.value !== 'other' );
				if ( ! detailsWrap.hidden ) {
					box.querySelector( '#cecomwishfw-dfm-details' ).focus();
				}
			} );
		} );

		/* Keyboard: Escape → close (cancel deactivation). */
		document.addEventListener( 'keydown', onKeydown );

		return overlay;
	}

	/* ── Modal lifecycle ──────────────────────────────────────────────────── */

	function showModal() {
		if ( _overlay ) {
			return;
		}
		_overlay = buildModal();
		document.body.style.overflow = 'hidden';

		/* Focus first radio for accessibility. */
		var firstRadio = _overlay.querySelector( 'input[type="radio"]' );
		if ( firstRadio ) {
			firstRadio.focus();
		}
	}

	function closeModal() {
		if ( ! _overlay ) {
			return;
		}
		document.body.removeChild( _overlay );
		_overlay = null;
		document.body.style.overflow = '';
		document.removeEventListener( 'keydown', onKeydown );
	}

	function onKeydown( e ) {
		if ( e.key === 'Escape' ) {
			closeModal();
		}
	}

	/* ── Deactivation ─────────────────────────────────────────────────────── */

	function doDeactivate() {
		window.location.href = deactivateUrl;
	}

	function onSubmit() {
		if ( ! _overlay ) {
			return;
		}

		var selectedRadio = _overlay.querySelector( 'input[name="cecomwishfw_dfm_reason"]:checked' );
		var reason        = selectedRadio ? selectedRadio.value : 'other';
		var detailsEl     = _overlay.querySelector( '#cecomwishfw-dfm-details' );
		var details       = ( detailsEl && ! detailsEl.closest( '[hidden]' ) ) ? detailsEl.value : '';

		var submitBtn     = _overlay.querySelector( '#cecomwishfw-dfm-submit' );
		submitBtn.disabled    = true;
		submitBtn.textContent = i18n.sending || 'Sending...';

		var data = new FormData();
		data.append( 'action',   'cecomwishfw_submit_deactivation_feedback' );
		data.append( '_wpnonce', cfg.nonce || '' );
		data.append( 'reason',   reason );
		data.append( 'details',  details );

		/* Fire-and-forget: proceed to deactivation regardless of network outcome. */
		fetch( cfg.ajaxUrl || '', { method: 'POST', body: data } )
			.catch( function () { /* ignore network errors */ } )
			.finally( doDeactivate );
	}

	/* ── Boot ─────────────────────────────────────────────────────────────── */

	function init() {
		var basename = cfg.pluginBasename || 'cecom-wishlist-for-woocommerce/cecom-wishlist-for-woocommerce.php';
		var row      = document.querySelector( 'tr[data-plugin="' + basename + '"]' );

		if ( ! row ) {
			return;
		}

		var deactivateLink = row.querySelector( '.deactivate a' );
		if ( ! deactivateLink ) {
			return; /* Plugin is already inactive or link is missing. */
		}

		deactivateUrl = deactivateLink.href;

		deactivateLink.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			showModal();
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
