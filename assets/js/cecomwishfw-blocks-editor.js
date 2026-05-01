/**
 * Block editor scripts for CECOM Wishlist for WooCommerce.
 *
 * Registers edit components for all free-edition Gutenberg blocks:
 *   cecomwishfw/wishlist  — static editor placeholder
 *   cecomwishfw/count     — InspectorControls: icon, link, zero, icon-class
 *   cecomwishfw/button    — InspectorControls: productId, context
 *
 * Plain ES5 — no build step required.
 * Enqueued via Cecomwishfw_Frontend_Controller::enqueue_block_editor_assets()
 * on the enqueue_block_editor_assets hook.
 *
 * @package Cecomwishfw
 */
( function ( blocks, blockEditor, components, element, i18n ) {
	'use strict';

	var el               = element.createElement;
	var Fragment         = element.Fragment;
	var useBlockProps    = blockEditor.useBlockProps;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelBody        = components.PanelBody;
	var ToggleControl    = components.ToggleControl;
	var TextControl      = components.TextControl;
	var SelectControl    = components.SelectControl;
	var __               = i18n.__;
	var td               = 'cecom-wishlist-for-woocommerce';

	var previewStyle = {
		padding:     '12px 16px',
		background:  '#fafafa',
		border:      '1px dashed #c8c8c8',
		borderRadius: '4px',
		color:       '#555',
		fontSize:    '13px',
		fontFamily:  'sans-serif',
		display:     'flex',
		alignItems:  'center',
		gap:         '8px',
	};

	// ── cecomwishfw/wishlist ───────────────────────────────────────────────────

	blocks.registerBlockType( 'cecomwishfw/wishlist', {
		edit: function () {
			var blockProps = useBlockProps( { style: previewStyle } );
			return el(
				'div', blockProps,
				el( 'span', { className: 'dashicons dashicons-heart', style: { color: '#e65a5a' } } ),
				__( 'Wishlist — renders for the current user on the frontend.', td )
			);
		},
		save: function () { return null; },
	} );

	// ── cecomwishfw/count ─────────────────────────────────────────────────────

	blocks.registerBlockType( 'cecomwishfw/count', {
		edit: function ( props ) {
			var attributes    = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps    = useBlockProps( { style: previewStyle } );

			return el(
				Fragment, null,
				el( InspectorControls, null,
					el( PanelBody, { title: __( 'Counter Settings', td ), initialOpen: true },
						el( ToggleControl, {
							label:    __( 'Show Icon', td ),
							checked:  attributes.showIcon,
							onChange: function ( v ) { setAttributes( { showIcon: v } ); },
						} ),
						el( ToggleControl, {
							label:    __( 'Link to Wishlist Page', td ),
							checked:  attributes.link,
							onChange: function ( v ) { setAttributes( { link: v } ); },
						} ),
						el( ToggleControl, {
							label:    __( 'Show When Count Is Zero', td ),
							checked:  attributes.showZero,
							onChange: function ( v ) { setAttributes( { showZero: v } ); },
						} ),
						el( TextControl, {
							label:    __( 'Custom Icon Class', td ),
							value:    attributes.iconClass,
							onChange: function ( v ) { setAttributes( { iconClass: v } ); },
							help:     __( 'Bootstrap Icons class, e.g. bi-heart-fill', td ),
						} )
					)
				),
				el(
					'div', blockProps,
					attributes.showIcon
						? el( 'span', { className: 'dashicons dashicons-heart', style: { color: '#e65a5a' } } )
						: null,
					el( 'span', null, __( 'Wishlist Counter', td ) ),
					el( 'span', {
						style: {
							background:   '#e65a5a',
							color:        '#fff',
							borderRadius: '10px',
							padding:      '0 6px',
							marginLeft:   '6px',
							fontSize:     '11px',
						},
					}, '3' )
				)
			);
		},
		save: function () { return null; },
	} );

	// ── cecomwishfw/button ────────────────────────────────────────────────────

	blocks.registerBlockType( 'cecomwishfw/button', {
		edit: function ( props ) {
			var attributes    = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps    = useBlockProps( { style: previewStyle } );

			return el(
				Fragment, null,
				el( InspectorControls, null,
					el( PanelBody, { title: __( 'Button Settings', td ), initialOpen: true },
						el( TextControl, {
							type:     'number',
							label:    __( 'Product ID', td ),
							value:    attributes.productId ? String( attributes.productId ) : '',
							onChange: function ( v ) {
								setAttributes( { productId: v ? parseInt( v, 10 ) : 0 } );
							},
							help: __( 'Leave at 0 to use the current product page\'s product automatically.', td ),
						} ),
						el( SelectControl, {
							label:    __( 'Button Context', td ),
							value:    attributes.context,
							options: [
								{ label: __( 'Single product', td ),       value: 'single' },
								{ label: __( 'Shop loop / archive', td ), value: 'loop'   },
							],
							onChange: function ( v ) { setAttributes( { context: v } ); },
						} )
					)
				),
				el(
					'div', blockProps,
					el( 'span', { className: 'dashicons dashicons-heart', style: { color: '#e65a5a' } } ),
					__( 'Add to Wishlist', td ),
					attributes.productId
						? el( 'small', { style: { marginLeft: '8px', color: '#888' } },
							'(Product #' + attributes.productId + ')'
						  )
						: el( 'small', { style: { marginLeft: '8px', color: '#888' } },
							__( '(current product page)', td )
						  )
				)
			);
		},
		save: function () { return null; },
	} );

}(
	wp.blocks,
	wp.blockEditor,
	wp.components,
	wp.element,
	wp.i18n
) );
