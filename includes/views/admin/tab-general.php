<?php
/**
 * General settings tab.
 *
 * Renders all General section fields. Included from settings.php inside a
 * <form> element — no form tag or submit button here.
 *
 * Uses the cecom-plugin-admin-ui-framework flat panel pattern:
 *   <div class="col-12 mb-3 shadow-sm rounded-4 p-3 p-sm-4 border border-light-subtle bg-white">
 *
 * @package Cecomwishfw
 */

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template file; variables are passed from the controller, not global

if ( ! function_exists( 'cecomwishfw_render_shortcode_field' ) ) {
	/**
	 * Render a small read-only "copy this shortcode" widget — text input
	 * pre-filled with a shortcode literal next to a Copy button. Used by the
	 * General tab to expose the [cecomwishfw_button] shortcode when the user
	 * picks "Shortcode only" as the button position, and to always expose the
	 * [cecomwishfw_wishlist] shortcode in the Pages section.
	 *
	 * @param string $shortcode The literal shortcode text (e.g. "[cecomwishfw_button]").
	 * @param string $wrap_id   Optional wrapper id for JS targeting / [hidden] toggle.
	 * @param string $context   Optional data-context attribute on the wrapper, used by
	 *                          the JS visibility toggle to know which dropdown drives it.
	 * @param bool   $hidden    Initial hidden state (set when the wrapper should
	 *                          start collapsed because its driver dropdown is not yet
	 *                          on `shortcode_only`).
	 * @return void
	 */
	function cecomwishfw_render_shortcode_field( string $shortcode, string $wrap_id = '', string $context = '', bool $hidden = false ): void {
		?>
		<div class="cecomwishfw-shortcode-display mt-2"
			<?php
			if ( '' !== $wrap_id ) :
				?>
					id="<?php echo esc_attr( $wrap_id ); ?>"<?php endif; ?>
			<?php
			if ( '' !== $context ) :
				?>
					data-context="<?php echo esc_attr( $context ); ?>"<?php endif; ?>
			<?php
			if ( $hidden ) :
				?>
					hidden<?php endif; ?>>
			<div class="d-flex align-items-center gap-2" style="max-width:420px;">
				<input type="text"
						class="form-control form-control-sm font-monospace cecomwishfw-shortcode-input"
						value="<?php echo esc_attr( $shortcode ); ?>"
						readonly
						onfocus="this.select();"
						aria-label="<?php esc_attr_e( 'Shortcode (read-only)', 'cecom-wishlist-for-woocommerce' ); ?>">
				<button type="button"
						class="btn btn-sm btn-outline-secondary cecomwishfw-copy-shortcode d-inline-flex align-items-center gap-1"
						aria-label="<?php esc_attr_e( 'Copy shortcode', 'cecom-wishlist-for-woocommerce' ); ?>">
					<i class="bi bi-clipboard" aria-hidden="true"></i>
					<span><?php esc_html_e( 'Copy', 'cecom-wishlist-for-woocommerce' ); ?></span>
				</button>
			</div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'cecomwishfw_label_tooltip' ) ) {
	/**
	 * Print a Bootstrap-tooltip-capable question-mark icon next to a form
	 * label. Mirror of the same helper in tab-appearance.php (each tab template
	 * is loaded independently via include_once-style require, so both need the
	 * guarded definition — only the first one to load actually defines it).
	 *
	 * @param string $text Help text to display in the tooltip. Empty = no-op.
	 * @return void
	 */
	function cecomwishfw_label_tooltip( string $text ): void {
		if ( '' === $text ) {
			return;
		}
		printf(
			'<button type="button" class="btn btn-link btn-sm p-0 ms-1 align-baseline cecomwishfw-tooltip-icon text-body-tertiary" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-trigger="hover focus" tabindex="0" title="%1$s" aria-label="%1$s"><i class="bi bi-question-circle" aria-hidden="true"></i></button>',
			esc_attr( $text )
		);
	}
}

$g = Cecomwishfw_Settings::get_all( 'general' );

$button_style_options = array(
	'icon_text' => __( 'Icon + Text', 'cecom-wishlist-for-woocommerce' ),
	'icon_only' => __( 'Icon only', 'cecom-wishlist-for-woocommerce' ),
	'text_only' => __( 'Text only', 'cecom-wishlist-for-woocommerce' ),
);

$position_options = array(
	'after_cart'     => __( 'After Add to Cart button', 'cecom-wishlist-for-woocommerce' ),
	'before_cart'    => __( 'Before Add to Cart button', 'cecom-wishlist-for-woocommerce' ),
	'after_summary'  => __( 'After product summary', 'cecom-wishlist-for-woocommerce' ),
	'after_price'    => __( 'After product price', 'cecom-wishlist-for-woocommerce' ),
	'image_overlay'  => __( 'Image overlay (top-right)', 'cecom-wishlist-for-woocommerce' ),
	'shortcode_only' => __( 'Shortcode only [cecomwishfw_button]', 'cecom-wishlist-for-woocommerce' ),
);

$loop_position_options = array(
	'after_add_to_cart'  => __( 'After Add to Cart button', 'cecom-wishlist-for-woocommerce' ),
	'before_add_to_cart' => __( 'Before Add to Cart button', 'cecom-wishlist-for-woocommerce' ),
	'after_title'        => __( 'After product title', 'cecom-wishlist-for-woocommerce' ),
	'image_overlay'      => __( 'Image overlay (top-right)', 'cecom-wishlist-for-woocommerce' ),
	'shortcode_only'     => __( 'Shortcode only', 'cecom-wishlist-for-woocommerce' ),
);

$product_type_options = array(
	'simple'   => __( 'Simple', 'cecom-wishlist-for-woocommerce' ),
	'variable' => __( 'Variable', 'cecom-wishlist-for-woocommerce' ),
	'grouped'  => __( 'Grouped', 'cecom-wishlist-for-woocommerce' ),
	'external' => __( 'External / Affiliate', 'cecom-wishlist-for-woocommerce' ),
);
?>

<?php /* ── Single Product Button panel ──────────────────────────────── */ ?>
<div class="col-12 mb-3 shadow-sm rounded-4 p-3 p-sm-4 border border-light-subtle bg-white">
	<h2 class="h6 fw-semibold text-body-emphasis mb-3">
		<?php esc_html_e( 'Single Product Button', 'cecom-wishlist-for-woocommerce' ); ?>
	</h2>

	<?php /* ── Enable ───────────────────────────────────────────────── */ ?>
	<div class="row mb-3 align-items-center">
		<div class="col-sm-3 col-form-label fw-medium">
			<?php esc_html_e( 'Enable', 'cecom-wishlist-for-woocommerce' ); ?>
		</div>
		<div class="col-sm-9">
			<div class="form-check form-switch">
				<input class="form-check-input"
						type="checkbox"
						role="switch"
						name="settings[show_on_single]"
						id="show_on_single"
						value="1"
						<?php checked( (bool) $g['show_on_single'] ); ?>>
				<label class="form-check-label" for="show_on_single">
					<?php esc_html_e( 'Show button on single product pages', 'cecom-wishlist-for-woocommerce' ); ?>
				</label>
			</div>
		</div>
	</div>

	<?php /* ── Button style ─────────────────────────────────────────── */ ?>
	<div class="row mb-3 align-items-center">
		<label class="col-sm-3 col-form-label fw-medium">
			<?php esc_html_e( 'Button style', 'cecom-wishlist-for-woocommerce' ); ?>
		</label>
		<div class="col-sm-9">
			<div class="btn-group" role="group" aria-label="<?php esc_attr_e( 'Single product button style', 'cecom-wishlist-for-woocommerce' ); ?>">
				<?php foreach ( $button_style_options as $value => $label ) : ?>
					<input type="radio"
							class="btn-check"
							name="settings[button_style]"
							id="button_style_<?php echo esc_attr( $value ); ?>"
							value="<?php echo esc_attr( $value ); ?>"
							autocomplete="off"
							<?php checked( $g['button_style'], $value ); ?>>
					<label class="btn btn-outline-secondary btn-sm"
							for="button_style_<?php echo esc_attr( $value ); ?>">
						<?php echo esc_html( $label ); ?>
					</label>
				<?php endforeach; ?>
			</div>
		</div>
	</div>

	<?php /* ── Button position ──────────────────────────────────────── */ ?>
	<div class="row mb-0 align-items-center">
		<label for="button_position" class="col-sm-3 col-form-label fw-medium">
			<?php esc_html_e( 'Button position', 'cecom-wishlist-for-woocommerce' ); ?>
			<?php cecomwishfw_label_tooltip( __( 'Where the "Add to Wishlist" button appears on single product pages.', 'cecom-wishlist-for-woocommerce' ) ); ?>
		</label>
		<div class="col-sm-9">
			<select name="settings[button_position]" id="button_position" class="form-select form-select-sm" style="max-width:320px;">
				<?php foreach ( $position_options as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $g['button_position'], $value ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<?php
			cecomwishfw_render_shortcode_field(
				'[cecomwishfw_button]',
				'cecomwishfw_button_shortcode_single',
				'single',
				'shortcode_only' !== ( $g['button_position'] ?? '' )
			);
			?>
		</div>
	</div>
</div>

<?php /* ── Shop Loop Button panel ──────────────────────────────────────── */ ?>
<div class="col-12 mb-3 shadow-sm rounded-4 p-3 p-sm-4 border border-light-subtle bg-white">
	<h2 class="h6 fw-semibold text-body-emphasis mb-3">
		<?php esc_html_e( 'Shop Loop Button', 'cecom-wishlist-for-woocommerce' ); ?>
	</h2>

	<?php /* ── Show on shop loop ────────────────────────────────────── */ ?>
	<div class="row mb-3 align-items-center">
		<div class="col-sm-3 col-form-label fw-medium">
			<?php esc_html_e( 'Enable', 'cecom-wishlist-for-woocommerce' ); ?>
		</div>
		<div class="col-sm-9">
			<div class="form-check form-switch">
				<input class="form-check-input"
						type="checkbox"
						role="switch"
						name="settings[show_on_loop]"
						id="show_on_loop"
						value="1"
						<?php checked( (bool) $g['show_on_loop'] ); ?>>
				<label class="form-check-label" for="show_on_loop">
					<?php esc_html_e( 'Show button on shop/archive pages', 'cecom-wishlist-for-woocommerce' ); ?>
				</label>
			</div>
		</div>
	</div>

	<?php /* ── Loop button position ────────────────────────────────────── */ ?>
	<div class="row mb-3 align-items-center">
		<label for="loop_button_position" class="col-sm-3 col-form-label fw-medium">
			<?php esc_html_e( 'Button position', 'cecom-wishlist-for-woocommerce' ); ?>
			<?php cecomwishfw_label_tooltip( __( 'Where the button appears on shop and archive pages.', 'cecom-wishlist-for-woocommerce' ) ); ?>
		</label>
		<div class="col-sm-9">
			<select name="settings[loop_button_position]" id="loop_button_position" class="form-select form-select-sm" style="max-width:320px;">
				<?php foreach ( $loop_position_options as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $g['loop_button_position'], $value ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<?php
			cecomwishfw_render_shortcode_field(
				'[cecomwishfw_button context="loop"]',
				'cecomwishfw_button_shortcode_loop',
				'loop',
				'shortcode_only' !== ( $g['loop_button_position'] ?? '' )
			);
			?>
		</div>
	</div>

	<?php /* ── Loop button style ──────────────────────────────────────── */ ?>
	<div class="row mb-0 align-items-center">
		<label class="col-sm-3 col-form-label fw-medium">
			<?php esc_html_e( 'Button style', 'cecom-wishlist-for-woocommerce' ); ?>
			<?php cecomwishfw_label_tooltip( __( 'Applies to the button shown on shop and archive pages.', 'cecom-wishlist-for-woocommerce' ) ); ?>
		</label>
		<div class="col-sm-9">
			<div class="btn-group" role="group" aria-label="<?php esc_attr_e( 'Loop button style', 'cecom-wishlist-for-woocommerce' ); ?>">
				<?php foreach ( $button_style_options as $value => $label ) : ?>
					<input type="radio"
							class="btn-check"
							name="settings[loop_button_style]"
							id="loop_button_style_<?php echo esc_attr( $value ); ?>"
							value="<?php echo esc_attr( $value ); ?>"
							autocomplete="off"
							<?php checked( $g['loop_button_style'], $value ); ?>>
					<label class="btn btn-outline-secondary btn-sm"
							for="loop_button_style_<?php echo esc_attr( $value ); ?>">
						<?php echo esc_html( $label ); ?>
					</label>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</div>

<?php /* ── Behavior panel ──────────────────────────────────────────────── */ ?>
<div class="col-12 mb-3 shadow-sm rounded-4 p-3 p-sm-4 border border-light-subtle bg-white">
	<h2 class="h6 fw-semibold text-body-emphasis mb-3">
		<?php esc_html_e( 'Behavior', 'cecom-wishlist-for-woocommerce' ); ?>
	</h2>

	<?php /* ── Remove on cart ──────────────────────────────────────── */ ?>
	<div class="row mb-3 align-items-center">
		<div class="col-sm-3 col-form-label fw-medium">
			<?php esc_html_e( 'Remove after cart', 'cecom-wishlist-for-woocommerce' ); ?>
		</div>
		<div class="col-sm-9">
			<div class="form-check form-switch">
				<input class="form-check-input"
						type="checkbox"
						role="switch"
						name="settings[remove_on_cart]"
						id="remove_on_cart"
						value="1"
						<?php checked( (bool) $g['remove_on_cart'] ); ?>>
				<label class="form-check-label" for="remove_on_cart">
					<?php esc_html_e( 'Remove item from wishlist when added to cart', 'cecom-wishlist-for-woocommerce' ); ?>
				</label>
			</div>
		</div>
	</div>

	<?php /* ── Redirect to checkout ──────────────────────────────── */ ?>
	<div class="row mb-3 align-items-center">
		<div class="col-sm-3 col-form-label fw-medium">
			<?php esc_html_e( 'Redirect to checkout', 'cecom-wishlist-for-woocommerce' ); ?>
		</div>
		<div class="col-sm-9">
			<div class="form-check form-switch">
				<input class="form-check-input"
						type="checkbox"
						role="switch"
						name="settings[redirect_checkout]"
						id="redirect_checkout"
						value="1"
						<?php checked( (bool) $g['redirect_checkout'] ); ?>>
				<label class="form-check-label" for="redirect_checkout">
					<?php esc_html_e( 'Redirect to checkout after adding product to cart from wishlist', 'cecom-wishlist-for-woocommerce' ); ?>
				</label>
			</div>
		</div>
	</div>

	<?php /* ── Show out of stock ────────────────────────────────────── */ ?>
	<div class="row mb-3 align-items-center">
		<div class="col-sm-3 col-form-label fw-medium">
			<?php esc_html_e( 'Out-of-stock items', 'cecom-wishlist-for-woocommerce' ); ?>
		</div>
		<div class="col-sm-9">
			<div class="form-check form-switch">
				<input class="form-check-input"
						type="checkbox"
						role="switch"
						name="settings[show_out_of_stock]"
						id="show_out_of_stock"
						value="1"
						<?php checked( (bool) $g['show_out_of_stock'] ); ?>>
				<label class="form-check-label" for="show_out_of_stock">
					<?php esc_html_e( 'Show out-of-stock items in the wishlist', 'cecom-wishlist-for-woocommerce' ); ?>
				</label>
			</div>
		</div>
	</div>

	<?php /* ── Registered only ─────────────────────────────────────── */ ?>
	<div class="row mb-3 align-items-start">
		<div class="col-sm-3 col-form-label fw-medium pt-0">
			<?php esc_html_e( 'Registered users only', 'cecom-wishlist-for-woocommerce' ); ?>
		</div>
		<div class="col-sm-9">
			<div class="form-check form-switch mb-2">
				<input class="form-check-input"
						type="checkbox"
						role="switch"
						name="settings[registered_only]"
						id="registered_only"
						value="1"
						<?php checked( (bool) $g['registered_only'] ); ?>>
				<label class="form-check-label" for="registered_only">
					<?php esc_html_e( 'Disable guest wishlists — require login', 'cecom-wishlist-for-woocommerce' ); ?>
				</label>
			</div>
		</div>
	</div>

	<?php /* ── Popularity counter ──────────────────────────────────── */ ?>
	<div class="row mb-0 align-items-start">
		<div class="col-sm-3 col-form-label fw-medium pt-0">
			<?php esc_html_e( 'Popularity counter', 'cecom-wishlist-for-woocommerce' ); ?>
			<?php cecomwishfw_label_tooltip( __( 'Shows how many wishlists contain this product below the Add to Wishlist icon — on both single product pages and shop loop cards. Counts are cached for 1 hour.', 'cecom-wishlist-for-woocommerce' ) ); ?>
		</div>
		<div class="col-sm-9">
			<div class="form-check form-switch mb-2">
				<input class="form-check-input"
						type="checkbox"
						role="switch"
						name="settings[show_popularity_counter]"
						id="show_popularity_counter"
						value="1"
						<?php checked( (bool) ( $g['show_popularity_counter'] ?? true ) ); ?>>
				<label class="form-check-label" for="show_popularity_counter">
					<?php esc_html_e( 'Show popularity counter below the wishlist button', 'cecom-wishlist-for-woocommerce' ); ?>
				</label>
			</div>
		</div>
	</div>
</div>

<?php /* ── Product Types panel ─────────────────────────────────────────── */ ?>
<div class="col-12 mb-3 shadow-sm rounded-4 p-3 p-sm-4 border border-light-subtle bg-white">
	<h2 class="h6 fw-semibold text-body-emphasis mb-3">
		<?php esc_html_e( 'Product Types', 'cecom-wishlist-for-woocommerce' ); ?>
	</h2>

	<div class="row mb-0">
		<div class="col-sm-3 col-form-label fw-medium">
			<?php esc_html_e( 'Enable for', 'cecom-wishlist-for-woocommerce' ); ?>
			<?php cecomwishfw_label_tooltip( __( 'The "Add to Wishlist" button only appears on selected product types.', 'cecom-wishlist-for-woocommerce' ) ); ?>
		</div>
		<div class="col-sm-9">
			<div class="d-flex flex-wrap gap-3">
				<?php foreach ( $product_type_options as $value => $label ) : ?>
					<div class="form-check">
						<input class="form-check-input"
								type="checkbox"
								name="settings[product_types][]"
								id="product_type_<?php echo esc_attr( $value ); ?>"
								value="<?php echo esc_attr( $value ); ?>"
								<?php checked( in_array( $value, (array) $g['product_types'], true ) ); ?>>
						<label class="form-check-label" for="product_type_<?php echo esc_attr( $value ); ?>">
							<?php echo esc_html( $label ); ?>
						</label>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</div>

<?php /* ── Pages panel ─────────────────────────────────────────────────── */ ?>
<div class="col-12 mb-3 shadow-sm rounded-4 p-3 p-sm-4 border border-light-subtle bg-white">
	<h2 class="h6 fw-semibold text-body-emphasis mb-3">
		<?php esc_html_e( 'Pages', 'cecom-wishlist-for-woocommerce' ); ?>
	</h2>

	<div class="row mb-0">
		<label for="wishlist_page_id" class="col-sm-3 col-form-label fw-medium">
			<?php esc_html_e( 'Wishlist page', 'cecom-wishlist-for-woocommerce' ); ?>
			<?php cecomwishfw_label_tooltip( __( 'Select the page where your wishlist should appear. The [cecomwishfw_wishlist] shortcode will be added to that page automatically if not already present.', 'cecom-wishlist-for-woocommerce' ) ); ?>
		</label>
		<div class="col-sm-9">
			<?php
			wp_dropdown_pages(
				array(
					'name'              => 'settings[wishlist_page_id]',
					'id'                => 'wishlist_page_id',
					'selected'          => absint( $g['wishlist_page_id'] ?? 0 ),
					'show_option_none'  => esc_html__( '— Select a page —', 'cecom-wishlist-for-woocommerce' ),
					'option_none_value' => '0',
					'class'             => 'form-select form-select-sm',
				)
			);
			cecomwishfw_render_shortcode_field( '[cecomwishfw_wishlist]' );
			?>
		</div>
	</div>
</div>

<?php /* ── Wishlist Page panel ──────────────────────────────────────────── */ ?>
<div class="col-12 mb-3 shadow-sm rounded-4 p-3 p-sm-4 border border-light-subtle bg-white">
	<h2 class="h6 fw-semibold text-body-emphasis mb-1">
		<?php esc_html_e( 'Wishlist Detail Page', 'cecom-wishlist-for-woocommerce' ); ?>
	</h2>
	<p class="text-muted small mb-3">
		<?php esc_html_e( 'Choose which columns and actions are visible on the wishlist page table.', 'cecom-wishlist-for-woocommerce' ); ?>
	</p>

	<div class="row mb-2 align-items-start">
		<label class="col-sm-3 col-form-label fw-medium pt-0">
			<?php esc_html_e( 'Visible columns', 'cecom-wishlist-for-woocommerce' ); ?>
		</label>
		<div class="col-sm-9 d-flex flex-column gap-2">

			<div class="form-check">
				<input class="form-check-input"
						type="checkbox"
						name="settings[table_show_variations]"
						id="table_show_variations"
						value="1"
						<?php checked( (bool) ( $g['table_show_variations'] ?? true ) ); ?>>
				<label class="form-check-label" for="table_show_variations">
					<?php esc_html_e( 'Product variations (size, color, …)', 'cecom-wishlist-for-woocommerce' ); ?>
				</label>
			</div>

			<div class="form-check">
				<input class="form-check-input"
						type="checkbox"
						name="settings[table_show_price]"
						id="table_show_price"
						value="1"
						<?php checked( (bool) ( $g['table_show_price'] ?? true ) ); ?>>
				<label class="form-check-label" for="table_show_price">
					<?php esc_html_e( 'Product price', 'cecom-wishlist-for-woocommerce' ); ?>
				</label>
			</div>

			<div class="form-check">
				<input class="form-check-input"
						type="checkbox"
						name="settings[table_show_stock]"
						id="table_show_stock"
						value="1"
						<?php checked( (bool) ( $g['table_show_stock'] ?? true ) ); ?>>
				<label class="form-check-label" for="table_show_stock">
					<?php esc_html_e( 'Stock status (in stock / out of stock)', 'cecom-wishlist-for-woocommerce' ); ?>
				</label>
			</div>

			<div class="form-check">
				<input class="form-check-input"
						type="checkbox"
						name="settings[table_show_date]"
						id="table_show_date"
						value="1"
						<?php checked( (bool) ( $g['table_show_date'] ?? true ) ); ?>>
				<label class="form-check-label" for="table_show_date">
					<?php esc_html_e( 'Date added to wishlist', 'cecom-wishlist-for-woocommerce' ); ?>
				</label>
			</div>

			<div class="form-check">
				<input class="form-check-input"
						type="checkbox"
						name="settings[table_show_quantity]"
						id="table_show_quantity"
						value="1"
					<?php checked( (bool) ( $g['table_show_quantity'] ?? true ) ); ?>>
				<label class="form-check-label" for="table_show_quantity">
					<?php esc_html_e( 'Quantity', 'cecom-wishlist-for-woocommerce' ); ?>
				</label>
			</div>

		</div>
	</div>

	<div class="row mb-0 align-items-start">
		<label class="col-sm-3 col-form-label fw-medium pt-0">
			<?php esc_html_e( 'Actions', 'cecom-wishlist-for-woocommerce' ); ?>
		</label>
		<div class="col-sm-9 d-flex flex-column gap-2">

			<div class="form-check">
				<input class="form-check-input"
						type="checkbox"
						name="settings[table_show_add_to_cart]"
						id="table_show_add_to_cart"
						value="1"
						<?php checked( (bool) ( $g['table_show_add_to_cart'] ?? true ) ); ?>>
				<label class="form-check-label" for="table_show_add_to_cart">
					<?php esc_html_e( 'Add to Cart button', 'cecom-wishlist-for-woocommerce' ); ?>
				</label>
			</div>

			<div class="form-check">
				<input class="form-check-input"
						type="checkbox"
						name="settings[table_show_remove_left]"
						id="table_show_remove_left"
						value="1"
						<?php checked( (bool) ( $g['table_show_remove_left'] ?? true ) ); ?>>
				<label class="form-check-label" for="table_show_remove_left">
					<?php esc_html_e( 'Remove icon — left of product row', 'cecom-wishlist-for-woocommerce' ); ?>
				</label>
			</div>

			<div class="form-check">
				<input class="form-check-input"
						type="checkbox"
						name="settings[table_show_remove_right]"
						id="table_show_remove_right"
						value="1"
						<?php checked( (bool) ( $g['table_show_remove_right'] ?? true ) ); ?>>
				<label class="form-check-label" for="table_show_remove_right">
					<?php esc_html_e( 'Remove button — right of product row (in Actions column)', 'cecom-wishlist-for-woocommerce' ); ?>
				</label>
			</div>

		</div>
	</div>
</div>

<?php /* ── Share Wishlist panel ──────────────────────────────────────────── */ ?>
<div class="col-12 mb-3 shadow-sm rounded-4 p-3 p-sm-4 border border-light-subtle bg-white">
	<h2 class="h6 fw-semibold text-body-emphasis mb-3">
		<?php esc_html_e( 'Share Wishlist', 'cecom-wishlist-for-woocommerce' ); ?>
	</h2>

	<?php /* Enable sharing */ ?>
	<div class="row mb-3">
		<div class="col-sm-3"></div>
		<div class="col-sm-9">
			<div class="form-check form-switch">
				<input class="form-check-input"
						type="checkbox"
						role="switch"
						name="settings[share_enabled]"
						id="share_enabled"
						value="1"
						<?php checked( (bool) ( $g['share_enabled'] ?? true ) ); ?>>
				<label class="form-check-label" for="share_enabled">
					<?php esc_html_e( 'Enable sharing — let users share their wishlist on social media', 'cecom-wishlist-for-woocommerce' ); ?>
				</label>
			</div>
		</div>
	</div>

	<?php /* Social media channels */ ?>
	<div class="row mb-3">
		<label class="col-sm-3 col-form-label fw-medium">
			<?php esc_html_e( 'Share on social media', 'cecom-wishlist-for-woocommerce' ); ?>
		</label>
		<div class="col-sm-9">
			<div class="d-flex flex-column gap-2">

				<div class="form-check">
					<input class="form-check-input"
							type="checkbox"
							name="settings[share_facebook]"
							id="share_facebook"
							value="1"
							<?php checked( (bool) ( $g['share_facebook'] ?? true ) ); ?>>
					<label class="form-check-label" for="share_facebook">
						<?php esc_html_e( 'Share on Facebook', 'cecom-wishlist-for-woocommerce' ); ?>
					</label>
				</div>

				<div class="form-check">
					<input class="form-check-input"
							type="checkbox"
							name="settings[share_twitter]"
							id="share_twitter"
							value="1"
							<?php checked( (bool) ( $g['share_twitter'] ?? true ) ); ?>>
					<label class="form-check-label" for="share_twitter">
						<?php esc_html_e( 'Tweet on Twitter (X)', 'cecom-wishlist-for-woocommerce' ); ?>
					</label>
				</div>

				<div class="form-check">
					<input class="form-check-input"
							type="checkbox"
							name="settings[share_pinterest]"
							id="share_pinterest"
							value="1"
							<?php checked( (bool) ( $g['share_pinterest'] ?? true ) ); ?>>
					<label class="form-check-label" for="share_pinterest">
						<?php esc_html_e( 'Pin on Pinterest', 'cecom-wishlist-for-woocommerce' ); ?>
					</label>
				</div>

				<div class="form-check">
					<input class="form-check-input"
							type="checkbox"
							name="settings[share_email]"
							id="share_email"
							value="1"
							<?php checked( (bool) ( $g['share_email'] ?? true ) ); ?>>
					<label class="form-check-label" for="share_email">
						<?php esc_html_e( 'Share by email', 'cecom-wishlist-for-woocommerce' ); ?>
					</label>
				</div>

				<div class="form-check">
					<input class="form-check-input"
							type="checkbox"
							name="settings[share_whatsapp]"
							id="share_whatsapp"
							value="1"
							<?php checked( (bool) ( $g['share_whatsapp'] ?? true ) ); ?>>
					<label class="form-check-label" for="share_whatsapp">
						<?php esc_html_e( 'Share on WhatsApp', 'cecom-wishlist-for-woocommerce' ); ?>
					</label>
				</div>

				<div class="form-check">
					<input class="form-check-input"
							type="checkbox"
							name="settings[share_telegram]"
							id="share_telegram"
							value="1"
							<?php checked( (bool) ( $g['share_telegram'] ?? true ) ); ?>>
					<label class="form-check-label" for="share_telegram">
						<?php esc_html_e( 'Share on Telegram', 'cecom-wishlist-for-woocommerce' ); ?>
					</label>
				</div>

			</div>
		</div>
	</div>

	<?php /* Show Share URL field */ ?>
	<div class="row mb-0">
		<div class="col-sm-3"></div>
		<div class="col-sm-9">
			<div class="form-check form-switch">
				<input class="form-check-input"
						type="checkbox"
						role="switch"
						name="settings[share_url]"
						id="share_url"
						value="1"
						<?php checked( (bool) ( $g['share_url'] ?? true ) ); ?>>
				<label class="form-check-label" for="share_url">
					<?php esc_html_e( 'Show "Share URL" field on wishlist page', 'cecom-wishlist-for-woocommerce' ); ?>
				</label>
			</div>
		</div>
	</div>
</div>

<?php /* ── Advanced panel (danger accent) ──────────────────────────────── */ ?>
<div class="col-12 mb-3 shadow-sm rounded-4 p-3 p-sm-4 border border-danger-subtle bg-white">
	<h2 class="d-flex gap-1 h6 fw-semibold text-danger-emphasis mb-3">
		<i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>
		<?php esc_html_e( 'Advanced', 'cecom-wishlist-for-woocommerce' ); ?>
	</h2>

	<div class="row mb-0 align-items-center">
		<div class="col-sm-3 col-form-label fw-medium">
			<?php esc_html_e( 'Uninstall data', 'cecom-wishlist-for-woocommerce' ); ?>
		</div>
		<div class="col-sm-9">
			<div class="form-check form-switch">
				<input class="form-check-input"
						type="checkbox"
						role="switch"
						name="settings[delete_on_uninstall]"
						id="delete_on_uninstall"
						value="1"
						<?php checked( (bool) $g['delete_on_uninstall'] ); ?>>
				<label class="form-check-label" for="delete_on_uninstall">
					<?php esc_html_e( 'Delete all wishlist data when the plugin is uninstalled', 'cecom-wishlist-for-woocommerce' ); ?>
				</label>
			</div>
			<small class="text-danger d-block mt-1">
				<?php esc_html_e( 'Warning: this cannot be undone. All wishlists, items, and settings will be permanently deleted.', 'cecom-wishlist-for-woocommerce' ); ?>
			</small>
		</div>
	</div>
</div>
