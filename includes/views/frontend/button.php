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
 * @var bool   $login_required True when registered_only is enabled and the visitor is a guest; JS intercepts the click.
 *
 * @package Cecomwishfw
 */

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template file; variables are passed from the controller, not global

$aria_label = $in_wishlist
	? esc_attr( $remove_label )
	: esc_attr( $add_label );
?>
<button
	class="cecomwishfw-btn cecomwishfw-btn--<?php echo esc_attr( $context ?? 'single' ); ?><?php echo esc_attr( ! empty( $overlay ) ? ' cecomwishfw-btn--overlay' : '' ); ?><?php echo esc_attr( $in_wishlist ? ' active' : '' ); ?>"
	data-product-id="<?php echo esc_attr( $product_id ); ?>"
	data-variation-id="<?php echo esc_attr( $variation_id ); ?>"
	data-add-label="<?php echo esc_attr( $add_label ); ?>"
	data-remove-label="<?php echo esc_attr( $remove_label ); ?>"
	data-wishlisted-variations="<?php echo esc_attr( wp_json_encode( $wishlisted_variation_ids ?? array() ) ); ?>"
	<?php
	if ( ! empty( $login_required ) ) :
		?>
		data-login-required="1"<?php endif; ?>
	aria-label="<?php echo esc_attr( $aria_label ); ?>"
	aria-pressed="<?php echo esc_attr( $in_wishlist ? 'true' : 'false' ); ?>"
	type="button">
	<?php if ( $show_icon ) : ?>
		<?php if ( ! empty( $icon_class ) ) : ?>
			<i class="bi <?php echo esc_attr( $icon_class ); ?> cecomwishfw-custom-icon" aria-hidden="true"></i>
		<?php else : ?>
			<i class="bi <?php echo esc_attr( $in_wishlist ? 'bi-heart-fill' : 'bi-heart' ); ?>" aria-hidden="true"></i>
		<?php endif; ?>
	<?php endif; ?>
	<?php if ( $show_text ) : ?>
		<span class="cecomwishfw-btn-label">
			<?php echo esc_html( $in_wishlist ? $remove_label : $add_label ); ?>
		</span>
	<?php endif; ?>
</button>
