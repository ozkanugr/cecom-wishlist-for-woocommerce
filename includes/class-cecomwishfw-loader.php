<?php
/**
 * Registers all actions and filters for the plugin.
 *
 * Maintains a list of all hooks that have been registered throughout
 * the plugin, and registers them with the WordPress API.
 *
 * @package Cecomwishfw
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Cecomwishfw_Loader
 *
 * Collects actions and filters, then registers them in bulk via run().
 */
class Cecomwishfw_Loader {

	/**
	 * Registered actions.
	 *
	 * @var array<int, array{hook: string, component: object|string, callback: string, priority: int, args: int}>
	 */
	private array $actions = array();

	/**
	 * Registered filters.
	 *
	 * @var array<int, array{hook: string, component: object|string, callback: string, priority: int, args: int}>
	 */
	private array $filters = array();

	/**
	 * Initialize the collections of hooks.
	 *
	 * Requires and instantiates all controller classes that hook into
	 * WordPress, then delegates hook registration to their constructors.
	 */
	public function __construct() {
		$this->load_dependencies();
		$this->define_i18n_hooks();
		$this->define_admin_hooks();
		$this->define_frontend_hooks();
		$this->define_ajax_hooks();
		$this->define_share_hooks();
		$this->define_data_lifecycle_hooks();
		$this->define_cache_hooks();
	}

	/**
	 * Load all required class files.
	 *
	 * @return void
	 */
	private function load_dependencies(): void {
		// Controllers.
		require_once CECOMWISHFW_PLUGIN_DIR . 'includes/controllers/class-cecomwishfw-admin-controller.php';
		require_once CECOMWISHFW_PLUGIN_DIR . 'includes/controllers/class-cecomwishfw-ajax-controller.php';
		require_once CECOMWISHFW_PLUGIN_DIR . 'includes/controllers/class-cecomwishfw-frontend-controller.php';
		require_once CECOMWISHFW_PLUGIN_DIR . 'includes/controllers/class-cecomwishfw-share-controller.php';

		// Models.
		require_once CECOMWISHFW_PLUGIN_DIR . 'includes/models/class-cecomwishfw-settings.php';
		require_once CECOMWISHFW_PLUGIN_DIR . 'includes/models/class-cecomwishfw-list-model.php';
		require_once CECOMWISHFW_PLUGIN_DIR . 'includes/models/class-cecomwishfw-item-model.php';
		require_once CECOMWISHFW_PLUGIN_DIR . 'includes/models/class-cecomwishfw-session-model.php';

		// Cache plugin compatibility (runtime filters for WP Rocket / LiteSpeed /
		// W3TC / Cache Enabler / WP Super Cache / WP Fastest Cache / WP-Optimize).
		require_once CECOMWISHFW_PLUGIN_DIR . 'includes/class-cecomwishfw-cache-compatibility.php';
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * @return void
	 */
	private function define_i18n_hooks(): void {
		$i18n = new Cecomwishfw_I18n();
		$this->add_action( 'plugins_loaded', $i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all admin-facing hooks.
	 *
	 * @return void
	 */
	private function define_admin_hooks(): void {
		$admin = new Cecomwishfw_Admin_Controller();
		$this->add_action( 'admin_menu', $admin, 'register_menu' );
		$this->add_action( 'admin_init', $admin, 'handle_form_submission' );
		$this->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_assets' );
		$this->add_action( 'wp_ajax_cecomwishfw_save_settings', $admin, 'ajax_save_settings' );
		$this->add_action( 'wp_ajax_cecomwishfw_reset_settings', $admin, 'ajax_reset_settings' );
	}

	/**
	 * Register storefront (frontend) hooks.
	 *
	 * @return void
	 */
	private function define_frontend_hooks(): void {
		$frontend = new Cecomwishfw_Frontend_Controller();

		// Storefront scripts + JS config.
		$this->add_action( 'wp_enqueue_scripts', $frontend, 'enqueue_scripts' );

		// Shortcode [cecomwishfw_wishlist] and Gutenberg block.
		$this->add_action( 'init', $frontend, 'register_shortcode' );
		$this->add_action( 'init', $frontend, 'register_block' );

		// Shortcode [cecomwishfw_count] and Gutenberg block (cecomwishfw/count).
		$this->add_action( 'init', $frontend, 'register_count_shortcode' );
		$this->add_action( 'init', $frontend, 'register_count_block' );

		// Shortcode [cecomwishfw_button] — used when button_position /
		// loop_button_position is set to 'shortcode_only' so the user can
		// embed the wishlist button manually inside product page / loop content.
		$this->add_action( 'init', $frontend, 'register_button_shortcode' );

		// WooCommerce My Account integration — wishlist tab for logged-in users.
		// The endpoint is always registered; the menu item only appears when
		// registered_only is enabled (controlled inside add_my_account_tab()).
		$this->add_action( 'init', $frontend, 'register_my_account_endpoint' );
		$this->add_filter( 'woocommerce_account_menu_items', $frontend, 'add_my_account_tab' );
		$this->add_action( 'woocommerce_account_' . Cecomwishfw_Frontend_Controller::MYACCOUNT_ENDPOINT . '_endpoint', $frontend, 'render_my_account_tab' );

		// Bypass full-page cache on the wishlist page only. Priority 1 ensures
		// DONOTCACHEPAGE is set before caching plugins inspect it. Visitors
		// carrying wishlist state are bypassed automatically via WooCommerce's
		// session cookie, which every major cache plugin already excludes.
		$this->add_action( 'template_redirect', $frontend, 'set_nocache_headers', 1 );

		// Redirect guests away from the standalone wishlist page when registered_only is on.
		// Runs on template_redirect (before output) so headers are not yet sent.
		$this->add_action( 'template_redirect', $frontend, 'maybe_redirect_guests' );

		// Add to Wishlist button on single product pages.
		// Only registered when the single product button is enabled.
		// Position is resolved from settings at hook registration time.
		if ( Cecomwishfw_Settings::get( 'general', 'show_on_single', true ) ) {
			$position       = Cecomwishfw_Settings::get( 'general', 'button_position', 'after_cart' );
			$position_hooks = array(
				'after_cart'    => array( 'woocommerce_after_add_to_cart_button', 10 ),
				'before_cart'   => array( 'woocommerce_before_add_to_cart_button', 10 ),
				'after_summary' => array( 'woocommerce_after_single_product_summary', 10 ),
				'after_price'   => array( 'woocommerce_single_product_summary', 25 ),
				// Priority 30 fires after woocommerce_template_single_media (priority 20)
				// so the button is output after the gallery in the DOM. cecomwishfw-frontend.js
				// then moves it inside .woocommerce-product-gallery for cross-theme reliability.
				'image_overlay' => array( 'woocommerce_before_single_product_summary', 30 ),
			);

			if ( isset( $position_hooks[ $position ] ) ) {
				[ $hook, $priority ] = $position_hooks[ $position ];
				// accepted_args = 0: WC hooks may pass a string/object as their first arg;
				// render_button() reads the product from get_the_ID() and must not receive
				// hook args (PHP 8 strict int type would throw TypeError on a non-int arg).
				$this->add_action( $hook, $frontend, 'render_button', $priority, 0 );
			}
			// 'shortcode_only' = no automatic hook; user places [cecomwishfw_button] manually.
		}

		// Add to Wishlist button on shop loop / archive pages.
		// Position is resolved from settings at hook registration time.
		$loop_position       = Cecomwishfw_Settings::get( 'general', 'loop_button_position', 'after_add_to_cart' );
		$loop_position_hooks = array(
			'after_add_to_cart'  => array( 'woocommerce_after_shop_loop_item', 15 ),
			'before_add_to_cart' => array( 'woocommerce_after_shop_loop_item', 5 ),
			'after_title'        => array( 'woocommerce_after_shop_loop_item_title', 10 ),
			// Priority 5 fires before woocommerce_template_loop_product_link_open
			// (priority 10 on the same hook), so the button is outside the <a> tag
			// but inside li.product (position: relative) for CSS overlay placement.
			'image_overlay'      => array( 'woocommerce_before_shop_loop_item', 5 ),
		);

		if ( isset( $loop_position_hooks[ $loop_position ] ) ) {
			[ $hook, $priority ] = $loop_position_hooks[ $loop_position ];
			$this->add_action( $hook, $frontend, 'render_loop_button', $priority, 0 );
		}
		// 'shortcode_only' = no automatic hook for loop either.
	}

	/**
	 * Register storefront AJAX endpoints (logged-in + guest).
	 *
	 * Eight actions: toggle/add/remove/count × (priv + nopriv).
	 *
	 * @return void
	 */
	private function define_ajax_hooks(): void {
		$ajax = new Cecomwishfw_Ajax_Controller();

		$map = array(
			'toggle_item' => 'handle_toggle',
			'add_item'    => 'handle_add',
			'remove_item' => 'handle_remove',
			'get_count'   => 'handle_get_count',
		);

		foreach ( $map as $action => $method ) {
			$this->add_action( "wp_ajax_cecomwishfw_{$action}", $ajax, $method );
			$this->add_action( "wp_ajax_nopriv_cecomwishfw_{$action}", $ajax, $method );
		}
	}

	/**
	 * Register shared wishlist hooks — token URL handling, title filter,
	 * and the regenerate-token AJAX endpoint.
	 *
	 * @return void
	 */
	private function define_share_hooks(): void {
		$share = new Cecomwishfw_Share_Controller();

		// Handle ?cwfw_token=<token> requests before WP renders any template.
		$this->add_action( 'template_redirect', $share, 'handle_shared_view' );

		// Override the browser/tab title for shared wishlist pages.
		$this->add_filter( 'document_title_parts', $share, 'filter_document_title' );

		// Regenerate token — logged-in users only (no nopriv).
		$this->add_action( 'wp_ajax_cecomwishfw_regenerate_token', $share, 'ajax_regenerate_token' );
	}

	/**
	 * Register cache-plugin compatibility hooks.
	 *
	 * Delegates to Cecomwishfw_Cache_Compatibility::register_runtime_filters(),
	 * which attaches callbacks to the filters exposed by WP Rocket, LiteSpeed
	 * Cache, W3 Total Cache, Cache Enabler, and WP-Optimize so the wishlist
	 * session cookie is excluded from full-page cache storage. Also wires a
	 * DONOTCACHEPAGE fallback on `init` priority 0 for WP Super Cache and WP
	 * Fastest Cache, which do not expose equivalent runtime cookie filters.
	 *
	 * Priority 20 on plugins_loaded ensures cache plugins have finished
	 * bootstrapping before we attach.
	 *
	 * @return void
	 */
	private function define_cache_hooks(): void {
		$this->add_action( 'plugins_loaded', 'Cecomwishfw_Cache_Compatibility', 'register_runtime_filters', 20 );
	}

	/**
	 * Register data-lifecycle hooks — keeps DB tables clean when WP/WC
	 * objects are trashed or permanently deleted.
	 *
	 * @return void
	 */
	private function define_data_lifecycle_hooks(): void {
		// Remove wishlist items when a product is trashed.
		$this->add_action( 'wp_trash_post', 'Cecomwishfw_Item_Model', 'delete_items_for_product' );

		// Remove wishlist items when a product is permanently deleted.
		// before_delete_post fires before the post row is removed, so
		// get_post_type() still resolves the correct type.
		$this->add_action( 'before_delete_post', 'Cecomwishfw_Item_Model', 'delete_items_for_product' );

		// Remove wishlists and items when a WP user is permanently deleted.
		$this->add_action( 'delete_user', 'Cecomwishfw_List_Model', 'delete_lists_for_user' );

		// Silently merge guest wishlist into user account on login (ADR-009).
		// Accepted args = 2: ($user_login, $user).
		$this->add_action( 'wp_login', 'Cecomwishfw_Session_Model', 'on_login', 10, 2 );

		// Purge stale guest sessions daily (cookie TTL + 1-day grace).
		$this->add_action( 'cecomwishfw_gc_guests', 'Cecomwishfw_List_Model', 'gc_guest_sessions' );
	}

	/**
	 * Add a new action to the collection to be registered with WordPress.
	 *
	 * @param string        $hook      The name of the WordPress action.
	 * @param object|string $component The object instance or class.
	 * @param string        $callback  The name of the function definition on the component.
	 * @param int           $priority  Optional. The priority for the action. Default 10.
	 * @param int           $accepted_args Optional. The number of accepted arguments. Default 1.
	 * @return void
	 */
	public function add_action( string $hook, object|string $component, string $callback, int $priority = 10, int $accepted_args = 1 ): void {
		$this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Add a new filter to the collection to be registered with WordPress.
	 *
	 * @param string        $hook      The name of the WordPress filter.
	 * @param object|string $component The object instance or class.
	 * @param string        $callback  The name of the function definition on the component.
	 * @param int           $priority  Optional. Default 10.
	 * @param int           $accepted_args Optional. Default 1.
	 * @return void
	 */
	public function add_filter( string $hook, object|string $component, string $callback, int $priority = 10, int $accepted_args = 1 ): void {
		$this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * A utility function to register the actions and hooks into a single collection.
	 *
	 * @param array         $hooks     The collection of hooks being registered.
	 * @param string        $hook      The name of the hook.
	 * @param object|string $component The instance or class.
	 * @param string        $callback  The callback method name.
	 * @param int           $priority  Priority.
	 * @param int           $accepted_args Number of args.
	 * @return array
	 */
	private function add( array $hooks, string $hook, object|string $component, string $callback, int $priority, int $accepted_args ): array {
		$hooks[] = array(
			'hook'      => $hook,
			'component' => $component,
			'callback'  => $callback,
			'priority'  => $priority,
			'args'      => $accepted_args,
		);
		return $hooks;
	}

	/**
	 * Register all actions and filters with WordPress.
	 *
	 * @return void
	 */
	public function run(): void {
		foreach ( $this->filters as $hook ) {
			add_filter( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['args'] );
		}
		foreach ( $this->actions as $hook ) {
			add_action( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['args'] );
		}
	}
}
