<?php
/**
 * Add to Wishlist button template.
 *
 * Variables passed from Cecomwishfw_Frontend_Controller::render_button():
 *
 * @var int    $product_id     WC product ID.
 * @var int    $variation_id   Selected variation ID (0 if none).
 * @var bool   $in_wishlist    Whether the product is currently in the wishlist.
 * @var bool   $show_icon      Show the Bootstrap Icons heart icon.
 * @var bool   $show_text      Show the text label.
 * @var string $add_label      "Add to wishlist" label text.
 * @var string $remove_label   "Remove from wishlist" label text.
 * @var string $icon_class     Custom Bootstrap Icons class e.g. 'bi-star-fill' (empty = use default bi-heart).
 * @var string $context        'single' (product page) or 'loop' (shop/archive loop).
 * @var bool   $overlay        True when the button position is 'image_overlay'; adds cecomwishfw-btn--overlay class.
 * @var bool   $login_required    True when registered_only is enabled and the visitor is a guest; JS intercepts the click.
 * @var bool   $show_popularity   Whether to render the popularity counter span.
 * @var int    $popularity_count  Number of wishlists this product appears in.
 * @var string $popularity_position Position of the counter: 'left', 'right', or 'below'.
 *
 * @package Cecomwishfw
 */

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template file; variables are passed from the controller, not global

$pop_modifier = ! empty( $show_popularity ) && 'below' === ( $popularity_position ?? 'right' )
	? ' cecomwishfw-btn--pop-below'
	: '';
?>
<button
	class="cecomwishfw-btn cecomwishfw-btn--<?php echo esc_attr( $context ?? 'single' ); ?><?php echo esc_attr( ! empty( $overlay ) ? ' cecomwishfw-btn--overlay' : '' ); ?><?php echo esc_attr( $pop_modifier ); ?>"
	data-product-id="<?php echo esc_attr( $product_id ); ?>"
	data-variation-id="<?php echo esc_attr( $variation_id ); ?>"
	data-add-label="<?php echo esc_attr( $add_label ); ?>"
	data-remove-label="<?php echo esc_attr( $remove_label ); ?>"
	data-wishlisted-variations="[]"
	<?php
	if ( ! empty( $login_required ) ) :
		?>
		data-login-required="1"<?php endif; ?>
	aria-label="<?php echo esc_attr( $add_label ); ?>"
	aria-pressed="false"
	type="button">
	<?php if ( ! empty( $show_popularity ) && 'left' === ( $popularity_position ?? 'right' ) ) : ?>
		<span class="cecomwishfw-popularity__count<?php echo ( ( $popularity_count ?? 0 ) < 1 ) ? ' cecomwishfw-popularity__count--zero' : ''; ?>" data-product-id="<?php echo esc_attr( $product_id ); ?>"><?php echo esc_html( $popularity_count ?? 0 ); ?></span>
	<?php endif; ?>
	<?php if ( $show_icon ) : ?>
		<?php if ( ! empty( $icon_class ) ) : ?>
			<i class="bi <?php echo esc_attr( $icon_class ); ?> cecomwishfw-custom-icon" aria-hidden="true"></i>
		<?php else : ?>
			<i class="bi bi-heart" aria-hidden="true"></i>
		<?php endif; ?>
	<?php endif; ?>
	<?php if ( ! empty( $show_popularity ) && 'left' !== ( $popularity_position ?? 'right' ) ) : ?>
		<span class="cecomwishfw-popularity__count<?php echo ( ( $popularity_count ?? 0 ) < 1 ) ? ' cecomwishfw-popularity__count--zero' : ''; ?>" data-product-id="<?php echo esc_attr( $product_id ); ?>"><?php echo esc_html( $popularity_count ?? 0 ); ?></span>
	<?php endif; ?>
	<?php if ( $show_text ) : ?>
		<span class="cecomwishfw-btn-label">
			<?php echo esc_html( $add_label ); ?>
		</span>
	<?php endif; ?>
</button>
