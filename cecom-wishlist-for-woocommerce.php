<?php
/**
 * Plugin Name:       CECOM Wishlist for WooCommerce
 * Plugin URI:        https://cecom.in/wishlist-for-woocommerce-annual/
 * Description:       The easiest-to-use wishlist plugin for WooCommerce — save products, share lists, and convert warm leads with 1-click email campaigns.
 * Version:           1.3.9
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Requires Plugins:  woocommerce
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
define( 'CECOMWISHFW_VERSION', '1.3.9' );
define( 'CECOMWISHFW_DB_VERSION', '1.1.0' );
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

// ── Plugin action links ────────────────────────────────────────────────────────
add_filter(
	'plugin_action_links_' . CECOMWISHFW_PLUGIN_BASENAME,
	static function ( array $links ): array {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=cecomwishfw-settings' ) ),
			esc_html__( 'Settings', 'cecom-wishlist-for-woocommerce' )
		);

		$docs_link = sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_url( 'https://cecom.in/docs-category/cecom-wishlist-for-woocommerce' ),
			esc_html__( 'Docs', 'cecom-wishlist-for-woocommerce' )
		);

		// Prepend Settings and Docs so they appear before Deactivate.
		array_unshift( $links, $docs_link );
		array_unshift( $links, $settings_link );

		$upgrade_link = sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer" style="color:#00a32a;font-weight:600;">%s</a>',
			esc_url( 'https://cecom.in/wishlist-for-woocommerce-annual-premium/' ),
			esc_html__( 'Upgrade', 'cecom-wishlist-for-woocommerce' )
		);

		// Append Upgrade after Settings and Docs but before Deactivate.
		array_splice( $links, 2, 0, array( $upgrade_link ) );

		return $links;
	}
);

if ( is_admin() ) {

	/**
	 * Plugin row meta — appends star rating (fetched from the WordPress.org API,
	 * cached 12 h) and an "Add Review" link to the plugin-version-author-uri row
	 * on the Plugins admin page.
	 */
	add_filter(
		'plugin_row_meta',
		static function ( array $links, string $plugin_file ): array {
			if ( CECOMWISHFW_PLUGIN_BASENAME !== $plugin_file ) {
				return $links;
			}

			$transient_key = 'cecomwishfw_wporg_rating';
			$data          = get_transient( $transient_key );

			// Discard any cached value that is not an array (e.g. stale string from a prior run).
			if ( false !== $data && ! is_array( $data ) ) {
				delete_transient( $transient_key );
				$data = false;
			}

			if ( false === $data ) {
				$response = wp_remote_get(
					'https://api.wordpress.org/plugins/info/1.2/?action=plugin_information'
					. '&request[slug]=cecom-wishlist-for-woocommerce'
					. '&request[fields][rating]=1'
					. '&request[fields][num_ratings]=1',
					array( 'timeout' => 5 )
				);

				if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
					$body = json_decode( wp_remote_retrieve_body( $response ), true );
					if ( isset( $body['rating'], $body['num_ratings'] ) ) {
						$data = array(
							'rating'      => (float) $body['rating'],
							'num_ratings' => (int) $body['num_ratings'],
						);
					} else {
						$data = array();
					}
				} else {
					$data = array();
				}

				set_transient( $transient_key, $data, 12 * HOUR_IN_SECONDS );
			}

			if ( is_array( $data ) && ! empty( $data ) ) {
				$rating      = (float) $data['rating'];
				$full_stars  = (int) floor( $rating / 20 );
				$half_star   = ( ( $rating / 20 ) - $full_stars ) >= 0.5 ? 1 : 0;
				$empty_stars = 5 - $full_stars - $half_star;
				$stars_html  = '';
				$review_url  = 'https://wordpress.org/support/plugin/cecom-wishlist-for-woocommerce/reviews/';
				for ( $i = 0; $i < $full_stars; $i++ ) {
					$stars_html .= '<span class="dashicons dashicons-star-filled" style="color:#ffb900;font-size:16px;width:16px;height:16px;" aria-hidden="true"></span>';
				}
				if ( $half_star ) {
					$stars_html .= '<span class="dashicons dashicons-star-half" style="color:#ffb900;font-size:16px;width:16px;height:16px;" aria-hidden="true"></span>';
				}
				for ( $i = 0; $i < $empty_stars; $i++ ) {
					$stars_html .= '<span class="dashicons dashicons-star-empty" style="color:#ffb900;font-size:16px;width:16px;height:16px;" aria-hidden="true"></span>';
				}

				$links[] = '<a href="' . esc_url( $review_url . '#new-post' ) . '" target="_blank" rel="noopener noreferrer">'
					. esc_html__( 'Add Review', 'cecom-wishlist-for-woocommerce' ) . '</a>';

				$links[] = wp_kses(
					'<a href="' . esc_url( $review_url ) . '" target="_blank" rel="noopener noreferrer" style="text-decoration:none;">'
						. $stars_html . '</a>',
					array(
						'a'    => array(
							'href'   => true,
							'target' => true,
							'rel'    => true,
							'style'  => true,
						),
						'span' => array(
							'class'       => true,
							'style'       => true,
							'aria-hidden' => true,
						),
					)
				);
			}

			return $links;
		},
		10,
		2
	);

} // end is_admin()
