<?php
/**
 * CECOM Ecosystem promotional admin page controller.
 *
 * Registers a cross-promotional submenu at the bottom of the shared "CECOM"
 * top-level menu. When multiple CECOM plugins are active, only one copy of
 * the page appears — the first plugin's controller to fire `admin_menu`
 * defines the sentinel constant; later copies bail out.
 *
 * @package Cecomwishfw
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Cecomwishfw_Ecosystem_Controller
 *
 * Read-only page — lists the CECOM plugin catalog with install-state badges
 * and purchase links pointing to cecom.in. Contains no license checks and no
 * premium backend logic, so the same controller is safe in both the free and
 * premium editions.
 */
class Cecomwishfw_Ecosystem_Controller {

	/**
	 * Submenu page slug (shared across every CECOM plugin that ships this
	 * controller — the dedup guard relies on it being identical everywhere).
	 *
	 * @var string
	 */
	private const PAGE_SLUG = 'cecomplgns-ecosystem';

	/**
	 * Capability required to view the page.
	 *
	 * @var string
	 */
	private const CAPABILITY = 'manage_options';

	/**
	 * Process-global sentinel used to dedupe cross-plugin registrations.
	 *
	 * @var string
	 */
	private const SENTINEL = 'CECOMPLGNS_ECOSYSTEM_REGISTERED';

	/**
	 * The hookname returned by add_submenu_page() — stored for reliable
	 * hook-suffix matching in enqueue_assets().
	 *
	 * @var string
	 */
	private string $page_hook = '';

	/**
	 * Constructor — registers late on admin_menu (priority 999) so sibling
	 * plugins using the default priority have already registered the
	 * `cecomplgns` parent. Assets enqueue on the standard admin hook.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ), 999 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register the ecosystem submenu under the CECOM parent.
	 *
	 * Two-layer dedup: a defined() sentinel for the fast path, and a
	 * defensive scan of $submenu['cecomplgns'] for cross-priority races.
	 *
	 * @return void
	 */
	public function register_menu(): void {

		// Layer 1 — fast-path sentinel.
		if ( defined( self::SENTINEL ) ) {
			return;
		}

		// Layer 2 — defensive scan of already-registered submenu entries.
		global $submenu;
		if ( isset( $submenu['cecomplgns'] ) && is_array( $submenu['cecomplgns'] ) ) {
			foreach ( $submenu['cecomplgns'] as $item ) {
				if ( isset( $item[2] ) && self::PAGE_SLUG === $item[2] ) {
					define( self::SENTINEL, true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.VariableConstantNameFound -- intentional cross-plugin sentinel, must not carry a plugin prefix
					return;
				}
			}
		}

		define( self::SENTINEL, true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.VariableConstantNameFound -- intentional cross-plugin sentinel, must not carry a plugin prefix

		$hook = add_submenu_page(
			'cecomplgns',
			esc_html__( 'CECOM Ecosystem', 'cecom-wishlist-for-woocommerce' ),
			esc_html__( 'CECOM Ecosystem', 'cecom-wishlist-for-woocommerce' ),
			self::CAPABILITY,
			self::PAGE_SLUG,
			array( $this, 'render_page' ),
			9999
		);

		$this->page_hook = is_string( $hook ) ? $hook : '';
	}

	/**
	 * Enqueue Bootstrap, Bootstrap Icons, and the shared CECOM framework CSS
	 * on the ecosystem page only. Handles match the admin controller's
	 * conventions so WordPress treats them as idempotent registrations.
	 *
	 * @param string $hook_suffix The current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		if ( '' === $this->page_hook || $this->page_hook !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'cecomwishfw-bootstrap',
			CECOMWISHFW_PLUGIN_URL . 'assets/dist/css/bootstrap.min.css',
			array(),
			null // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- bundled asset
		);

		wp_enqueue_style(
			'cecomwishfw-bs-icons',
			CECOMWISHFW_PLUGIN_URL . 'assets/icons/font/bootstrap-icons.css',
			array(),
			null // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- bundled asset
		);

		wp_enqueue_style(
			'cecomwishfw-framework',
			CECOMWISHFW_PLUGIN_URL . 'assets/css/cecom-plugin-admin-ui-framework.css',
			array( 'cecomwishfw-bootstrap' ),
			null // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- bundled asset
		);
	}

	/**
	 * Render the ecosystem page view.
	 *
	 * @return void
	 */
	public function render_page(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'cecom-wishlist-for-woocommerce' ) );
		}

		$plugins  = self::get_catalog();
		$statuses = array();
		foreach ( $plugins as $plugin ) {
			$statuses[ $plugin['key'] ] = self::get_plugin_status( $plugin );
		}

		include CECOMWISHFW_PLUGIN_DIR . 'includes/views/admin/ecosystem.php';
	}

	/**
	 * Static catalog of CECOM plugins displayed on the ecosystem page.
	 *
	 * Append a new entry here to promote a third plugin — no other edits
	 * required. All user-facing strings are translation-ready; URLs and
	 * basenames are code, not translated.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function get_catalog(): array {
		return array(
			array(
				'key'              => 'smarttr-address',
				'name'             => __( 'SmartTR Address', 'cecom-wishlist-for-woocommerce' ),
				'tagline'          => __( 'Guided Turkish address entry for WooCommerce checkout — cascading Province, District, Neighborhood & Postal Code.', 'cecom-wishlist-for-woocommerce' ),
				'icon'             => 'bi-geo-alt-fill',
				'image_url'        => CECOMWISHFW_PLUGIN_URL . 'assets/img/smarttr-address.jpg',
				'features'         => array(
					__( 'Province → District cascade', 'cecom-wishlist-for-woocommerce' ),
					__( 'Neighborhood AJAX dropdown (Premium)', 'cecom-wishlist-for-woocommerce' ),
					__( 'Postal code auto-fill (Premium)', 'cecom-wishlist-for-woocommerce' ),
				),
				'badges'           => array(
					__( 'WooCommerce', 'cecom-wishlist-for-woocommerce' ),
					__( 'Turkish', 'cecom-wishlist-for-woocommerce' ),
				),
				'free_basename'    => 'smarttr-address/smarttr-address.php',
				'premium_basename' => 'smarttr-address-premium/smarttr-address-premium.php',
				'purchase_url'     => 'https://cecom.in/smarttr-address-turkish-address',
			),
			array(
				'key'              => 'cecom-wishlist-for-woocommerce',
				'name'             => __( 'Wishlist for WooCommerce', 'cecom-wishlist-for-woocommerce' ),
				'tagline'          => __( 'Let customers save favorite products, share lists, and return to purchase with automated email campaigns.', 'cecom-wishlist-for-woocommerce' ),
				'icon'             => 'bi-heart-fill',
				'image_url'        => CECOMWISHFW_PLUGIN_URL . 'assets/img/cecom-wishlist-for-woocommerce.png',
				'features'         => array(
					__( 'Guest and logged-in wishlists', 'cecom-wishlist-for-woocommerce' ),
					__( 'Shortcode and block support', 'cecom-wishlist-for-woocommerce' ),
					__( 'Collaborative lists and email campaigns (Premium)', 'cecom-wishlist-for-woocommerce' ),
				),
				'badges'           => array(
					__( 'WooCommerce', 'cecom-wishlist-for-woocommerce' ),
				),
				'free_basename'    => 'cecom-wishlist-for-woocommerce/cecom-wishlist-for-woocommerce.php',
				'premium_basename' => 'cecom-wishlist-for-woocommerce-premium/cecom-wishlist-for-woocommerce-premium.php',
				'purchase_url'     => 'https://cecom.in/wishlist-for-woocommerce-annual/',
			),
		);
	}

	/**
	 * Determine install status for a catalog entry.
	 *
	 * @param array<string, mixed> $plugin Catalog row.
	 * @return string 'premium' | 'free' | 'none'.
	 */
	private static function get_plugin_status( array $plugin ): string {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( ! empty( $plugin['premium_basename'] ) && is_plugin_active( $plugin['premium_basename'] ) ) {
			return 'premium';
		}

		if ( ! empty( $plugin['free_basename'] ) && is_plugin_active( $plugin['free_basename'] ) ) {
			return 'free';
		}

		return 'none';
	}
}
