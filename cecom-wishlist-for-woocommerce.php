<?php
/**
 * Plugin Name:       CECOM Wishlist for WooCommerce
 * Plugin URI:        https://cecom.in/wishlist-for-woocommerce-annual/
 * Description:       The easiest-to-use wishlist plugin for WooCommerce — save products, share lists, and convert warm leads with 1-click email campaigns.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            ugurozkan
 * Author URI:        https://cecom.in
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       cecom-wishlist-for-woocommerce
 * Domain Path:       /languages
 * WC requires at least: 7.0
 * WC tested up to:   9.9
 *
 * @package Cecomwishfw
 */

defined( 'ABSPATH' ) || exit;

// ── Constants ──────────────────────────────────────────────────────────────────
define( 'CECOMWISHFW_VERSION', '1.0.0' );
define( 'CECOMWISHFW_DB_VERSION', '1.0.0' );
define( 'CECOMWISHFW_PLUGIN_FILE', __FILE__ );
define( 'CECOMWISHFW_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CECOMWISHFW_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CECOMWISHFW_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'CECOMWISHFW_SLUG', 'cecom-wishlist-for-woocommerce' );
define( 'CECOMWISHFW_TEXT_DOMAIN', 'cecom-wishlist-for-woocommerce' );

// ── HPOS compatibility (ADR-007) ───────────────────────────────────────────────
add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				CECOMWISHFW_PLUGIN_FILE,
				true
			);
		}
	}
);

// ── Core class files ───────────────────────────────────────────────────────────
require_once CECOMWISHFW_PLUGIN_DIR . 'includes/class-cecomwishfw-activator.php';
require_once CECOMWISHFW_PLUGIN_DIR . 'includes/class-cecomwishfw-deactivator.php';

register_activation_hook( __FILE__, array( 'Cecomwishfw_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Cecomwishfw_Deactivator', 'deactivate' ) );

// ── Auto-upgrade schema on every load when DB version is behind (fdb-3) ───────
// Fires at priority 5 — before the main loader (priority 10) — so tables are
// guaranteed to exist before any model call on the first load after an update.
// maybe_upgrade_schema() is idempotent: returns early when schema is current.
add_action( 'plugins_loaded', array( 'Cecomwishfw_Activator', 'maybe_upgrade_schema' ), 5 );

// ── Bootstrap on plugins_loaded (after WooCommerce) ───────────────────────────
add_action( 'plugins_loaded', 'cecomwishfw_run' );

/**
 * Bootstrap the plugin after all plugins are loaded.
 *
 * Checks for WooCommerce; shows an admin notice if missing.
 *
 * @return void
 */
function cecomwishfw_run(): void {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'cecomwishfw_missing_wc_notice' );
		return;
	}

	require_once CECOMWISHFW_PLUGIN_DIR . 'includes/class-cecomwishfw-i18n.php';
	require_once CECOMWISHFW_PLUGIN_DIR . 'includes/class-cecomwishfw-loader.php';

	$loader = new Cecomwishfw_Loader();
	$loader->run();
}

/**
 * Admin notice: WooCommerce is required.
 *
 * @return void
 */
function cecomwishfw_missing_wc_notice(): void {
	echo '<div class="notice notice-error"><p>' .
		esc_html__(
			'CECOM Wishlist for WooCommerce requires WooCommerce 7.0 or newer to be installed and activated.',
			'cecom-wishlist-for-woocommerce'
		) .
		'</p></div>';
}
