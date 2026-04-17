<?php
/**
 * Wishlist counter template.
 *
 * Renders a badge that shows the current wishlist item count.
 * JavaScript (updateCounters) keeps the .cecomwishfw-count element in sync
 * whenever items are added or removed without a page reload.
 *
 * Variables passed from Cecomwishfw_Frontend_Controller::shortcode_count_callback():
 *
 * @var int    $count        Current wishlist item count (server-side resolved).
 * @var bool   $show_icon    Show the Bootstrap Icons heart icon.
 * @var bool   $link         Wrap in an anchor to the wishlist page.
 * @var string $icon_class   Bootstrap Icons class (empty = default bi-heart).
 * @var bool   $show_zero    Show the badge even when count is 0.
 * @var string $wishlist_url Wishlist page URL.
 *
 * @package Cecomwishfw
 */

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template file; variables are passed from the controller, not global

$badge_class   = 'cecomwishfw-count' . ( ( 0 === $count && ! $show_zero ) ? ' cecomwishfw-count--empty' : '' );
$resolved_icon = '' !== $icon_class ? $icon_class : 'bi-heart';

/* translators: %d: number of wishlist items */
$aria_label = sprintf( __( 'Wishlist (%d items)', 'cecom-wishlist-for-woocommerce' ), $count );

if ( $link ) : ?>
<a href="<?php echo esc_url( $wishlist_url ); ?>" class="cecomwishfw-counter-wrap" aria-label="<?php echo esc_attr( $aria_label ); ?>">
<?php else : ?>
<span class="cecomwishfw-counter-wrap">
<?php endif; ?>
	<?php if ( $show_icon ) : ?>
		<i class="bi <?php echo esc_attr( $resolved_icon ); ?> cecomwishfw-counter-icon" aria-hidden="true"></i>
	<?php endif; ?>
	<span class="<?php echo esc_attr( $badge_class ); ?>"><?php echo esc_html( $count ); ?></span>
<?php if ( $link ) : ?>
</a>
<?php else : ?>
</span>
<?php endif; ?>
