<?php
/**
 * Login prompt template — shown when registered_only mode is enabled.
 *
 * Displayed instead of the Add to Wishlist button for guest users
 * when the admin has enabled "Registered users only" in settings.
 *
 * Routes guests to the WooCommerce My Account page (the storefront login
 * form), never the wp-admin login screen — see
 * Cecomwishfw_Frontend_Controller::resolve_frontend_login_url() for the
 * WC My Account → wp_login_url() fallback chain.
 *
 * @package Cecomwishfw
 */

defined( 'ABSPATH' ) || exit;

$cecomwishfw_login_url = Cecomwishfw_Frontend_Controller::resolve_frontend_login_url( (string) get_permalink() );
?>
<a
	href="<?php echo esc_url( $cecomwishfw_login_url ); ?>"
	class="cecomwishfw-login-prompt"
	aria-label="<?php esc_attr_e( 'Log in to add to wishlist', 'cecom-wishlist-for-woocommerce' ); ?>">
	<i class="bi bi-heart" aria-hidden="true"></i>
	<span><?php esc_html_e( 'Log in to wishlist', 'cecom-wishlist-for-woocommerce' ); ?></span>
</a>
