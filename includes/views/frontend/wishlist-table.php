<?php
/**
 * Wishlist table / cards template.
 *
 * Renders the full wishlist in two layouts:
 *   Desktop (≥ 768px): custom table with self-contained CSS (no Bootstrap dependency)
 *   Mobile  (< 768px): card stack (CSS hides table, shows cards)
 *
 * Column and element visibility is controlled by the "Wishlist Detail Page"
 * settings in the General tab. $is_shared_view additionally hides all remove
 * controls regardless of settings (visitors must not modify the owner's list).
 *
 * Variables inherited from wishlist-page.php / shared-wishlist.php:
 *
 * @var array                    $items           Enriched item objects from get_for_list().
 * @var object                   $list            Current wishlist list object.
 * @var Cecomwishfw_Settings     $settings        Settings model instance.
 * @var bool                     $is_shared_view  True when viewing another user's shared wishlist.
 *
 * @package Cecomwishfw
 */

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template file; variables are passed from the controller, not global

$list_id        = (int) ( $list->id ?? 0 );
$is_shared_view = (bool) ( $is_shared_view ?? false );

// ── Wishlist detail page visibility settings ─────────────────────────────
$show_variations  = (bool) $settings->get( 'general', 'table_show_variations', true );
$show_price       = (bool) $settings->get( 'general', 'table_show_price', true );
$show_stock       = (bool) $settings->get( 'general', 'table_show_stock', true );
$show_date        = (bool) $settings->get( 'general', 'table_show_date', true );
$show_add_to_cart = (bool) $settings->get( 'general', 'table_show_add_to_cart', true );
$show_quantity    = (bool) $settings->get( 'general', 'table_show_quantity', true );
// Remove controls are always hidden in shared view — settings only apply to the owner's own view.
$show_remove_left  = ! $is_shared_view && (bool) $settings->get( 'general', 'table_show_remove_left', true );
$show_remove_right = ! $is_shared_view && (bool) $settings->get( 'general', 'table_show_remove_right', true );

// ── Build columns array based on settings ───────────────────────────────
// 'remove_left' has an empty label (no header text — icon-only narrow column).
$base_columns = array();
if ( $show_remove_left ) {
	$base_columns['remove_left'] = '';
}
$base_columns['image'] = __( 'Product', 'cecom-wishlist-for-woocommerce' );
if ( $show_price ) {
	$base_columns['price'] = __( 'Price', 'cecom-wishlist-for-woocommerce' );
}
if ( $show_quantity ) {
	$base_columns['quantity'] = __( 'Quantity', 'cecom-wishlist-for-woocommerce' );
}
if ( $show_date ) {
	$base_columns['added_at'] = __( 'Date Added', 'cecom-wishlist-for-woocommerce' );
}
// Actions column is only included when at least one action is enabled.
if ( $show_add_to_cart || $show_remove_right ) {
	$base_columns['actions'] = __( 'Actions', 'cecom-wishlist-for-woocommerce' );
}

$columns = apply_filters( 'cecomwishfw_wishlist_table_columns', $base_columns );
?>

<?php /* ── Desktop table (CSS hides on < 768px) ─────────────────────── */ ?>
<div class="cecomwishfw-table-wrap">
	<table class="cecomwishfw-wishlist-table">
		<thead>
			<tr>
				<?php foreach ( $columns as $key => $label ) : ?>
					<th scope="col" class="cecomwishfw-col-<?php echo esc_attr( $key ); ?>">
						<?php echo esc_html( $label ); ?>
					</th>
				<?php endforeach; ?>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $items as $item ) : ?>
				<?php
				$product = $item->product ?? null; // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.FoundInControlStructure -- simple assignment in loop for readability
				if ( ! $product instanceof \WC_Product ) {
					continue;
				}
				$product_id   = (int) $item->product_id;
				$variation_id = (int) $item->variation_id;
				$item_id      = (int) $item->id;
				$in_stock     = $product->is_in_stock();
				$image        = $product->get_image( 'woocommerce_thumbnail', array( 'class' => 'cecomwishfw-product-img' ) );
				$name         = $product->get_name();

				// Variation attributes (e.g. "Color: Blue, Size: M").
				$variation_text = '';
				if ( $show_variations && $variation_id > 0 && $product instanceof \WC_Product_Variation ) {
					$attrs = $product->get_variation_attributes();
					$pairs = array();
					foreach ( $attrs as $attr_name => $attr_value ) {
						$pairs[] = wc_attribute_label( str_replace( 'attribute_', '', $attr_name ) ) . ': ' . esc_html( $attr_value );
					}
					$variation_text = implode( ', ', $pairs );
				}

				$add_to_cart_url = $product->add_to_cart_url();
				$add_to_cart_txt = esc_html( $product->add_to_cart_text() );
				// Simple products and already-resolved variations both support AJAX add-to-cart.
				$is_ajax_cart = $product->is_type( 'simple' ) || ( $product->is_type( 'variation' ) && $variation_id > 0 );
				// Grouped/variable product — main row shows no price; children expand in accordion.
				$is_grouped       = $product instanceof \WC_Product_Grouped;
				$is_variable      = $product instanceof \WC_Product_Variable;
				$grouped_children = array();
				if ( $is_grouped ) {
					foreach ( $product->get_children() as $child_id ) {
						$gc = wc_get_product( (int) $child_id );
						if ( $gc instanceof \WC_Product && $gc->is_visible() ) {
							$grouped_children[] = $gc;
						}
					}
				} elseif ( $is_variable ) {
					foreach ( $product->get_children() as $child_id ) {
						$gc = wc_get_product( (int) $child_id );
						if ( $gc instanceof \WC_Product_Variation && $gc->variation_is_visible() ) {
							$grouped_children[] = $gc;
						}
					}
				}
				// Decode per-child price snapshot (set at wishlist-add time).
				$cwfw_price_meta_map = array();
				if ( ! empty( $item->price_meta ) ) {
					$cwfw_decoded = json_decode( $item->price_meta, true );
					if ( is_array( $cwfw_decoded ) ) {
						$cwfw_price_meta_map = $cwfw_decoded;
					}
				}
				// Decode per-child quantity (set via cecomwishfw_update_child_quantities).
				$cwfw_qty_meta_map = array();
				if ( ! empty( $item->quantity_meta ) ) {
					$cwfw_decoded_qty = json_decode( $item->quantity_meta, true );
					if ( is_array( $cwfw_decoded_qty ) ) {
						$cwfw_qty_meta_map = $cwfw_decoded_qty;
					}
				}
				$cwfw_item_qty = max( 1, (int) ( $item->quantity ?? 1 ) );
				?>
				<tr class="cecomwishfw-item-row" data-item-id="<?php echo esc_attr( $item_id ); ?>">

					<?php if ( isset( $columns['remove_left'] ) ) : ?>
						<td class="cecomwishfw-col-remove-left">
							<button
								class="cecomwishfw-remove-icon cecomwishfw-remove-item"
								data-product-id="<?php echo esc_attr( $product_id ); ?>"
								data-variation-id="<?php echo esc_attr( $variation_id ); ?>"
								data-list-id="<?php echo esc_attr( $list_id ); ?>"
								aria-label="<?php esc_attr_e( 'Remove from wishlist', 'cecom-wishlist-for-woocommerce' ); ?>"
								type="button">
								<i class="bi bi-trash" aria-hidden="true"></i>
							</button>
						</td>
					<?php endif; ?>

					<?php if ( isset( $columns['image'] ) ) : ?>
						<td class="cecomwishfw-col-image">
							<div class="cecomwishfw-product-cell">
								<a href="<?php echo esc_url( get_permalink( $product_id ) ); ?>" class="cecomwishfw-product-thumb">
									<?php echo wp_kses_post( $image ); ?>
								</a>
								<div class="cecomwishfw-product-meta">
									<a href="<?php echo esc_url( get_permalink( $product_id ) ); ?>" class="cecomwishfw-product-name-link">
										<?php echo esc_html( $name ); ?>
									</a>
									<?php if ( $variation_text ) : ?>
										<span class="cecomwishfw-variation-text"><?php echo esc_html( $variation_text ); ?></span>
									<?php endif; ?>
									<?php
									if ( $show_stock ) {
										// Delegate to WC's own stock-display function so the wishlist
										// table reflects the global Inventory settings — Stock display
										// format, Low stock threshold, Out of stock threshold, and the
										// "Available on backorder" / "Only N left" variants. Returns
										// an empty string when WC is configured to suppress the
										// stock label entirely.
										$cwfw_stock_html = trim( (string) wc_get_stock_html( $product ) );
										if ( '' !== $cwfw_stock_html ) {
											echo '<span class="cecomwishfw-stock-cell">' . wp_kses_post( $cwfw_stock_html ) . '</span>';
										}
									}
									?>
									<?php if ( ( $is_grouped || $is_variable ) && ! empty( $grouped_children ) ) : ?>
										<button type="button"
												class="cecomwishfw-grouped-toggle"
												aria-expanded="false"
												aria-controls="cwfw-group-<?php echo esc_attr( $item_id ); ?>-children"
												data-target="cwfw-group-<?php echo esc_attr( $item_id ); ?>-children">
											<i class="bi bi-chevron-down" aria-hidden="true"></i>
											<?php
											printf(
												/* translators: %d: number of child products in the group */
												esc_html( _n( '%d item', '%d items', count( $grouped_children ), 'cecom-wishlist-for-woocommerce' ) ),
												count( $grouped_children )
											);
											?>
										</button>
									<?php endif; ?>
								</div>
							</div>
						</td>
					<?php endif; ?>

					<?php if ( isset( $columns['price'] ) ) : ?>
						<td class="cecomwishfw-col-price">
							<?php echo wp_kses_post( $product->get_price_html() ); ?>
						</td>
					<?php endif; ?>

					<?php if ( isset( $columns['quantity'] ) ) : ?>
						<td class="cecomwishfw-col-quantity">
							<?php $cwfw_main_qty = max( 1, (int) ( $item->quantity ?? 1 ) ); ?>
							<?php if ( ! $is_shared_view ) : ?>
								<input type="number" min="1" step="1"
									class="cecomwishfw-item-qty"
									value="<?php echo esc_attr( (string) $cwfw_main_qty ); ?>"
									data-item-id="<?php echo esc_attr( (string) $item_id ); ?>"
									aria-label="<?php esc_attr_e( 'Item quantity', 'cecom-wishlist-for-woocommerce' ); ?>">
							<?php else : ?>
								<span class="cecomwishfw-item-qty-readonly">
									<?php echo esc_html( (string) $cwfw_main_qty ); ?>
								</span>
							<?php endif; ?>
						</td>
					<?php endif; ?>

					<?php if ( isset( $columns['added_at'] ) ) : ?>
						<td class="cecomwishfw-col-date">
							<span class="cecomwishfw-date-text">
								<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $item->added_at ?? '' ) ) ); ?>
							</span>
						</td>
					<?php endif; ?>

					<?php if ( isset( $columns['actions'] ) ) : ?>
						<td class="cecomwishfw-col-actions">
							<div class="cecomwishfw-actions-wrap">

								<?php if ( $show_add_to_cart && $in_stock ) : ?>
									<?php
									// For variation items, pass variation_id as data-product_id so WooCommerce's
									// AJAX handler detects type "variation" and auto-resolves all required
									// variation attributes — no manual attribute array needed.
									// data-wl-product-id / data-wl-variation-id carry the wishlist item keys
									// used by bindRemoveOnCart() without interfering with the WC cart handler.
									$cart_product_id = ( $is_ajax_cart && $variation_id > 0 ) ? $variation_id : $product_id;
									?>
									<a href="<?php echo esc_url( add_query_arg( 'quantity', $cwfw_main_qty, $add_to_cart_url ) ); ?>"
										class="cecomwishfw-btn-cart<?php echo esc_attr( $is_ajax_cart ? ' add_to_cart_button ajax_add_to_cart' : '' ); ?>"
										data-product_id="<?php echo esc_attr( $cart_product_id ); ?>"
										<?php if ( $variation_id > 0 ) : ?>
										data-wl-product-id="<?php echo esc_attr( $product_id ); ?>"
										data-wl-variation-id="<?php echo esc_attr( $variation_id ); ?>"
										<?php endif; ?>
										data-quantity="<?php echo esc_attr( (string) $cwfw_main_qty ); ?>"
										aria-label="<?php echo esc_attr( $product->add_to_cart_text() ); ?>">
										<?php echo esc_html( $product->add_to_cart_text() ); ?>
									</a>
								<?php elseif ( $show_add_to_cart && ! $in_stock ) : ?>
									<button class="cecomwishfw-btn-oos" disabled aria-disabled="true">
										<?php esc_html_e( 'Out of stock', 'cecom-wishlist-for-woocommerce' ); ?>
									</button>
								<?php endif; ?>

								<?php if ( $show_remove_right ) : ?>
									<button
										class="cecomwishfw-btn-remove cecomwishfw-remove-item"
										data-product-id="<?php echo esc_attr( $product_id ); ?>"
										data-variation-id="<?php echo esc_attr( $variation_id ); ?>"
										data-list-id="<?php echo esc_attr( $list_id ); ?>"
										aria-label="<?php esc_attr_e( 'Remove from wishlist', 'cecom-wishlist-for-woocommerce' ); ?>">
										<i class="bi bi-trash" aria-hidden="true"></i>
										<?php esc_html_e( 'Remove', 'cecom-wishlist-for-woocommerce' ); ?>
									</button>
								<?php endif; ?>

							</div>
						</td>
					<?php endif; ?>

				</tr>
				<?php if ( ( $is_grouped || $is_variable ) && ! empty( $grouped_children ) ) : ?>
					<?php $cwfw_col_count = count( $columns ); ?>
					<tr class="cecomwishfw-grouped-children-row"
						id="cwfw-group-<?php echo esc_attr( $item_id ); ?>-children"
						hidden>
						<td colspan="<?php echo esc_attr( (string) $cwfw_col_count ); ?>"
							class="cecomwishfw-grouped-children-cell">
							<table class="cecomwishfw-grouped-children-table">
								<tbody>
									<?php foreach ( $grouped_children as $gc ) : ?>
										<?php
										$gc_id       = $gc->get_id();
										$gc_in_stock = $gc->is_in_stock();
										?>
										<tr class="cecomwishfw-grouped-child-row">
											<?php if ( $show_remove_left ) : ?>
											<td class="cecomwishfw-grouped-child-remove-left">
												<button
													class="cecomwishfw-remove-icon cecomwishfw-remove-item"
													data-product-id="<?php echo esc_attr( $is_variable ? (string) $product_id : (string) $gc_id ); ?>"
													data-variation-id="<?php echo esc_attr( $is_variable ? (string) $gc_id : (string) $variation_id ); ?>"
													<?php if ( ! $is_variable ) : ?>data-parent-product-id="<?php echo esc_attr( (string) $product_id ); ?>"<?php endif; ?>
													<?php echo $is_variable ? 'data-child-variation="1"' : 'data-child-grouped="1"'; ?>
													data-list-id="<?php echo esc_attr( $list_id ); ?>"
													aria-label="<?php esc_attr_e( 'Remove from wishlist', 'cecom-wishlist-for-woocommerce' ); ?>"
													type="button">
													<i class="bi bi-trash" aria-hidden="true"></i>
												</button>
											</td>
											<?php endif; ?>
											<td class="cecomwishfw-grouped-child-img">
												<a href="<?php echo esc_url( get_permalink( $gc_id ) ); ?>">
													<?php echo wp_kses_post( $gc->get_image( 'woocommerce_thumbnail', array( 'class' => 'cecomwishfw-product-img' ) ) ); ?>
												</a>
											</td>
											<td class="cecomwishfw-grouped-child-name">
												<a href="<?php echo esc_url( get_permalink( $gc_id ) ); ?>" class="cecomwishfw-product-name-link">
													<?php echo esc_html( $gc->get_name() ); ?>
												</a>
												<?php if ( $show_stock ) : ?>
													<?php
													$gc_stock_html = trim( (string) wc_get_stock_html( $gc ) );
													if ( '' !== $gc_stock_html ) {
														echo '<span class="cecomwishfw-stock-cell">' . wp_kses_post( $gc_stock_html ) . '</span>';
													}
													?>
												<?php endif; ?>
											</td>
											<?php if ( $show_price ) : ?>
												<td class="cecomwishfw-grouped-child-price">
													<?php
													$gc_current      = (float) $gc->get_price();
													$gc_price_at_add = isset( $cwfw_price_meta_map[ $gc_id ] ) ? (float) $cwfw_price_meta_map[ $gc_id ] : 0.0;
													if ( $gc_price_at_add > 0.001 ) :
														$gc_diff = $gc_current - $gc_price_at_add;
														if ( $gc_diff < -0.001 ) :
															$gc_drop_pct = round( abs( $gc_diff ) / $gc_price_at_add * 100, 1 );
															?>
															<span class="cecomwishfw-price-drop">
																&darr; <?php echo esc_html( (string) $gc_drop_pct ); ?>% &mdash; <?php echo wp_kses_post( wc_price( $gc_current ) ); ?>
															</span>
														<?php elseif ( $gc_diff > 0.001 ) : ?>
															<del class="cecomwishfw-price-original"><?php echo wp_kses_post( wc_price( $gc_price_at_add ) ); ?></del>
															<?php echo wp_kses_post( wc_price( $gc_current ) ); ?>
														<?php else : ?>
															<?php echo wp_kses_post( wc_price( $gc_current ) ); ?>
														<?php endif; ?>
													<?php else : ?>
														<?php echo wp_kses_post( wc_price( $gc_current ) ); ?>
													<?php endif; ?>
												</td>
											<?php endif; ?>
											<?php $cwfw_gc_qty = max( 1, (int) ( $cwfw_qty_meta_map[ $gc_id ] ?? 1 ) ); ?>
											<?php if ( $show_quantity ) : ?>
												<td class="cecomwishfw-grouped-child-qty">
													<input type="number" min="1" step="1"
														class="cecomwishfw-child-qty"
														value="<?php echo esc_attr( (string) $cwfw_gc_qty ); ?>"
														data-item-id="<?php echo esc_attr( (string) $item_id ); ?>"
														data-child-id="<?php echo esc_attr( (string) $gc_id ); ?>"
														aria-label="<?php esc_attr_e( 'Item quantity', 'cecom-wishlist-for-woocommerce' ); ?>">
												</td>
											<?php endif; ?>
											<?php if ( $show_add_to_cart || $show_remove_right ) : ?>
												<td class="cecomwishfw-grouped-child-actions">
													<?php if ( $show_add_to_cart ) : ?>
														<?php if ( $gc_in_stock && $gc->is_purchasable() ) : ?>
															<a href="<?php echo esc_url( $gc->add_to_cart_url() ); ?>"
																class="cecomwishfw-btn-cart add_to_cart_button ajax_add_to_cart"
																data-product_id="<?php echo esc_attr( $gc_id ); ?>"
																data-quantity="<?php echo esc_attr( (string) ( $cwfw_item_qty * $cwfw_gc_qty ) ); ?>"
																aria-label="<?php echo esc_attr( $gc->add_to_cart_text() ); ?>">
																<?php echo esc_html( $gc->add_to_cart_text() ); ?>
															</a>
														<?php else : ?>
															<button class="cecomwishfw-btn-oos" disabled aria-disabled="true">
																<?php esc_html_e( 'Out of stock', 'cecom-wishlist-for-woocommerce' ); ?>
															</button>
														<?php endif; ?>
													<?php endif; ?>
													<?php if ( $show_remove_right ) : ?>
														<button
															class="cecomwishfw-btn-remove cecomwishfw-remove-item"
															data-product-id="<?php echo esc_attr( $is_variable ? (string) $product_id : (string) $gc_id ); ?>"
															data-variation-id="<?php echo esc_attr( $is_variable ? (string) $gc_id : (string) $variation_id ); ?>"
															<?php if ( ! $is_variable ) : ?>data-parent-product-id="<?php echo esc_attr( (string) $product_id ); ?>"<?php endif; ?>
															<?php echo $is_variable ? 'data-child-variation="1"' : 'data-child-grouped="1"'; ?>
															data-list-id="<?php echo esc_attr( $list_id ); ?>"
															type="button">
															<?php esc_html_e( 'Remove', 'cecom-wishlist-for-woocommerce' ); ?>
														</button>
													<?php endif; ?>
												</td>
											<?php endif; ?>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</td>
					</tr>
				<?php endif; ?>
			<?php endforeach; ?>
		</tbody>
	</table>
</div><!-- .cecomwishfw-table-wrap -->

<?php /* ── Mobile cards (CSS hides on ≥ 768px) ────────────────────────── */ ?>
<div class="cecomwishfw-cards-wrap">
	<?php foreach ( $items as $item ) : ?>
		<?php
		$product = $item->product ?? null;
		if ( ! $product instanceof \WC_Product ) {
			continue;
		}
		$product_id   = (int) $item->product_id;
		$variation_id = (int) $item->variation_id;
		$item_id      = (int) $item->id;
		$in_stock     = $product->is_in_stock();
		// Simple products and already-resolved variations both support AJAX add-to-cart.
		$is_ajax_cart = $product->is_type( 'simple' ) || ( $product->is_type( 'variation' ) && $variation_id > 0 );
		// Grouped/variable product — main card shows no price; children expand in panel.
		$is_grouped       = $product instanceof \WC_Product_Grouped;
		$is_variable      = $product instanceof \WC_Product_Variable;
		$grouped_children = array();
		if ( $is_grouped ) {
			foreach ( $product->get_children() as $child_id ) {
				$gc = wc_get_product( (int) $child_id );
				if ( $gc instanceof \WC_Product && $gc->is_visible() ) {
					$grouped_children[] = $gc;
				}
			}
		} elseif ( $is_variable ) {
			foreach ( $product->get_children() as $child_id ) {
				$gc = wc_get_product( (int) $child_id );
				if ( $gc instanceof \WC_Product_Variation && $gc->variation_is_visible() ) {
					$grouped_children[] = $gc;
				}
			}
		}
		$cwfw_price_meta_map = array();
		if ( ! empty( $item->price_meta ) ) {
			$cwfw_decoded = json_decode( $item->price_meta, true );
			if ( is_array( $cwfw_decoded ) ) {
				$cwfw_price_meta_map = $cwfw_decoded;
			}
		}
		$cwfw_qty_meta_map = array();
		if ( ! empty( $item->quantity_meta ) ) {
			$cwfw_decoded_qty = json_decode( $item->quantity_meta, true );
			if ( is_array( $cwfw_decoded_qty ) ) {
				$cwfw_qty_meta_map = $cwfw_decoded_qty;
			}
		}
		$cwfw_item_qty = max( 1, (int) ( $item->quantity ?? 1 ) );

		// Variation attributes for mobile card.
		$variation_text = '';
		if ( $show_variations && $variation_id > 0 && $product instanceof \WC_Product_Variation ) {
			$attrs = $product->get_variation_attributes();
			$pairs = array();
			foreach ( $attrs as $attr_name => $attr_value ) {
				$pairs[] = wc_attribute_label( str_replace( 'attribute_', '', $attr_name ) ) . ': ' . esc_html( $attr_value );
			}
			$variation_text = implode( ', ', $pairs );
		}
		?>
		<div class="cecomwishfw-product-card cecomwishfw-item-row" data-item-id="<?php echo esc_attr( $item_id ); ?>">

			<?php if ( $show_remove_left ) : ?>
				<div class="cecomwishfw-card-remove-col">
					<button
						class="cecomwishfw-remove-icon cecomwishfw-remove-item"
						data-product-id="<?php echo esc_attr( $product_id ); ?>"
						data-variation-id="<?php echo esc_attr( $variation_id ); ?>"
						data-list-id="<?php echo esc_attr( $list_id ); ?>"
						aria-label="<?php esc_attr_e( 'Remove from wishlist', 'cecom-wishlist-for-woocommerce' ); ?>"
						type="button">
						<i class="bi bi-trash" aria-hidden="true"></i>
					</button>
				</div>
			<?php endif; ?>

			<a href="<?php echo esc_url( get_permalink( $product_id ) ); ?>" class="cecomwishfw-card-thumb">
				<?php echo wp_kses_post( $product->get_image( 'woocommerce_thumbnail', array( 'class' => 'cecomwishfw-product-img' ) ) ); ?>
			</a>

			<div class="cecomwishfw-card-body">

				<a href="<?php echo esc_url( get_permalink( $product_id ) ); ?>" class="cecomwishfw-card-name">
					<?php echo esc_html( $product->get_name() ); ?>
				</a>

				<?php if ( $variation_text ) : ?>
					<span class="cecomwishfw-variation-text"><?php echo esc_html( $variation_text ); ?></span>
				<?php endif; ?>

				<?php if ( $show_price && ! $is_grouped && ! $is_variable ) : ?>
					<div class="cecomwishfw-card-price">
						<?php echo wp_kses_post( $product->get_price_html() ); ?>
					</div>
				<?php endif; ?>

				<?php
				if ( $show_stock ) {
					// Same WC delegation as the desktop table — see the comment block above.
					$cwfw_stock_html = trim( (string) wc_get_stock_html( $product ) );
					if ( '' !== $cwfw_stock_html ) {
						echo '<span class="cecomwishfw-stock-cell">' . wp_kses_post( $cwfw_stock_html ) . '</span>';
					}
				}
				?>

				<?php if ( $show_date ) : ?>
					<span class="cecomwishfw-card-date">
						<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $item->added_at ?? '' ) ) ); ?>
					</span>
				<?php endif; ?>

				<?php if ( $show_quantity ) : ?>
					<?php $cwfw_card_qty = max( 1, (int) ( $item->quantity ?? 1 ) ); ?>
					<?php if ( ! $is_shared_view ) : ?>
						<input type="number" min="1" step="1"
							class="cecomwishfw-item-qty"
							value="<?php echo esc_attr( (string) $cwfw_card_qty ); ?>"
							data-item-id="<?php echo esc_attr( (string) $item_id ); ?>"
							aria-label="<?php esc_attr_e( 'Item quantity', 'cecom-wishlist-for-woocommerce' ); ?>">
					<?php else : ?>
						<span class="cecomwishfw-item-qty-readonly">
							<?php echo esc_html( (string) $cwfw_card_qty ); ?>
						</span>
					<?php endif; ?>
				<?php endif; ?>

				<?php if ( ( $is_grouped || $is_variable ) && ! empty( $grouped_children ) ) : ?>
					<button type="button"
							class="cecomwishfw-grouped-toggle"
							aria-expanded="false"
							aria-controls="cwfw-group-card-<?php echo esc_attr( $item_id ); ?>-children"
							data-target="cwfw-group-card-<?php echo esc_attr( $item_id ); ?>-children">
						<i class="bi bi-chevron-down" aria-hidden="true"></i>
						<?php
						printf(
							/* translators: %d: number of child products in the group */
							esc_html( _n( '%d item', '%d items', count( $grouped_children ), 'cecom-wishlist-for-woocommerce' ) ),
							count( $grouped_children )
						);
						?>
					</button>
					<div class="cecomwishfw-grouped-children-panel"
						id="cwfw-group-card-<?php echo esc_attr( $item_id ); ?>-children"
						hidden>
						<?php foreach ( $grouped_children as $gc ) : ?>
							<?php
							$gc_id       = $gc->get_id();
							$gc_in_stock = $gc->is_in_stock();
							$cwfw_gc_qty = max( 1, (int) ( $cwfw_qty_meta_map[ $gc_id ] ?? 1 ) );
							?>
							<div class="cecomwishfw-grouped-child-card">
								<a href="<?php echo esc_url( get_permalink( $gc_id ) ); ?>"
									class="cecomwishfw-grouped-child-card-thumb">
									<?php echo wp_kses_post( $gc->get_image( 'woocommerce_thumbnail', array( 'class' => 'cecomwishfw-product-img' ) ) ); ?>
								</a>
								<div class="cecomwishfw-grouped-child-card-body">
									<a href="<?php echo esc_url( get_permalink( $gc_id ) ); ?>" class="cecomwishfw-product-name-link">
										<?php echo esc_html( $gc->get_name() ); ?>
									</a>
									<?php if ( $show_price ) : ?>
										<div class="cecomwishfw-grouped-child-price">
											<?php
											$gc_current      = (float) $gc->get_price();
											$gc_price_at_add = isset( $cwfw_price_meta_map[ $gc_id ] ) ? (float) $cwfw_price_meta_map[ $gc_id ] : 0.0;
											if ( $gc_price_at_add > 0.001 ) :
												$gc_diff = $gc_current - $gc_price_at_add;
												if ( $gc_diff < -0.001 ) :
													$gc_drop_pct = round( abs( $gc_diff ) / $gc_price_at_add * 100, 1 );
													?>
													<span class="cecomwishfw-price-drop">
														&darr; <?php echo esc_html( (string) $gc_drop_pct ); ?>% &mdash; <?php echo wp_kses_post( wc_price( $gc_current ) ); ?>
													</span>
												<?php elseif ( $gc_diff > 0.001 ) : ?>
													<del class="cecomwishfw-price-original"><?php echo wp_kses_post( wc_price( $gc_price_at_add ) ); ?></del>
													<?php echo wp_kses_post( wc_price( $gc_current ) ); ?>
												<?php else : ?>
													<?php echo wp_kses_post( wc_price( $gc_current ) ); ?>
												<?php endif; ?>
											<?php else : ?>
												<?php echo wp_kses_post( wc_price( $gc_current ) ); ?>
											<?php endif; ?>
										</div>
									<?php endif; ?>
									<?php if ( $show_stock ) : ?>
										<?php
										$gc_stock_html = trim( (string) wc_get_stock_html( $gc ) );
										if ( '' !== $gc_stock_html ) {
											echo '<span class="cecomwishfw-stock-cell">' . wp_kses_post( $gc_stock_html ) . '</span>';
										}
										?>
									<?php endif; ?>
									<?php if ( $show_quantity ) : ?>
										<input type="number" min="1" step="1"
											class="cecomwishfw-child-qty"
											value="<?php echo esc_attr( (string) $cwfw_gc_qty ); ?>"
											data-item-id="<?php echo esc_attr( (string) $item_id ); ?>"
											data-child-id="<?php echo esc_attr( (string) $gc_id ); ?>"
											aria-label="<?php esc_attr_e( 'Item quantity', 'cecom-wishlist-for-woocommerce' ); ?>">
									<?php endif; ?>
									<?php if ( $show_add_to_cart ) : ?>
										<?php if ( $gc_in_stock && $gc->is_purchasable() ) : ?>
											<a href="<?php echo esc_url( $gc->add_to_cart_url() ); ?>"
												class="cecomwishfw-btn-cart add_to_cart_button ajax_add_to_cart"
												data-product_id="<?php echo esc_attr( $gc_id ); ?>"
												data-quantity="<?php echo esc_attr( (string) ( $cwfw_item_qty * $cwfw_gc_qty ) ); ?>"
												aria-label="<?php echo esc_attr( $gc->add_to_cart_text() ); ?>">
												<?php echo esc_html( $gc->add_to_cart_text() ); ?>
											</a>
										<?php else : ?>
											<button class="cecomwishfw-btn-oos" disabled aria-disabled="true">
												<?php esc_html_e( 'Out of stock', 'cecom-wishlist-for-woocommerce' ); ?>
											</button>
										<?php endif; ?>
									<?php endif; ?>
									<?php if ( $show_remove_left ) : ?>
										<button
											class="cecomwishfw-remove-icon cecomwishfw-remove-item"
											data-product-id="<?php echo esc_attr( $is_variable ? (string) $product_id : (string) $gc_id ); ?>"
											data-variation-id="<?php echo esc_attr( $is_variable ? (string) $gc_id : (string) $variation_id ); ?>"
											<?php if ( ! $is_variable ) : ?>data-parent-product-id="<?php echo esc_attr( (string) $product_id ); ?>"<?php endif; ?>
											<?php echo $is_variable ? 'data-child-variation="1"' : 'data-child-grouped="1"'; ?>
											data-list-id="<?php echo esc_attr( $list_id ); ?>"
											aria-label="<?php esc_attr_e( 'Remove from wishlist', 'cecom-wishlist-for-woocommerce' ); ?>"
											type="button">
											<i class="bi bi-trash" aria-hidden="true"></i>
										</button>
									<?php endif; ?>
									<?php if ( $show_remove_right ) : ?>
										<button
											class="cecomwishfw-btn-remove cecomwishfw-remove-item"
											data-product-id="<?php echo esc_attr( $is_variable ? (string) $product_id : (string) $gc_id ); ?>"
											data-variation-id="<?php echo esc_attr( $is_variable ? (string) $gc_id : (string) $variation_id ); ?>"
											<?php if ( ! $is_variable ) : ?>data-parent-product-id="<?php echo esc_attr( (string) $product_id ); ?>"<?php endif; ?>
											<?php echo $is_variable ? 'data-child-variation="1"' : 'data-child-grouped="1"'; ?>
											data-list-id="<?php echo esc_attr( $list_id ); ?>"
											type="button">
											<?php esc_html_e( 'Remove', 'cecom-wishlist-for-woocommerce' ); ?>
										</button>
									<?php endif; ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<?php if ( $show_add_to_cart ) : ?>
					<div class="cecomwishfw-card-actions">
						<?php if ( $in_stock ) : ?>
							<?php $cart_product_id = ( $is_ajax_cart && $variation_id > 0 ) ? $variation_id : $product_id; ?>
							<a href="<?php echo esc_url( add_query_arg( 'quantity', $cwfw_card_qty, $product->add_to_cart_url() ) ); ?>"
								class="cecomwishfw-btn-cart<?php echo esc_attr( $is_ajax_cart ? ' add_to_cart_button ajax_add_to_cart' : '' ); ?>"
								data-product_id="<?php echo esc_attr( $cart_product_id ); ?>"
								<?php if ( $variation_id > 0 ) : ?>
								data-wl-product-id="<?php echo esc_attr( $product_id ); ?>"
								data-wl-variation-id="<?php echo esc_attr( $variation_id ); ?>"
								<?php endif; ?>
								data-quantity="<?php echo esc_attr( (string) $cwfw_card_qty ); ?>"
								aria-label="<?php echo esc_attr( $product->add_to_cart_text() ); ?>">
								<?php echo esc_html( $product->add_to_cart_text() ); ?>
							</a>
						<?php else : ?>
							<button class="cecomwishfw-btn-oos" disabled aria-disabled="true">
								<?php esc_html_e( 'Out of stock', 'cecom-wishlist-for-woocommerce' ); ?>
							</button>
						<?php endif; ?>
					</div>
				<?php endif; ?>

			</div><!-- .cecomwishfw-card-body -->

		</div><!-- .cecomwishfw-product-card -->
	<?php endforeach; ?>
</div><!-- .cecomwishfw-cards-wrap -->
