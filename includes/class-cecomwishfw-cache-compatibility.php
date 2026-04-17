<?php
/**
 * Cache plugin compatibility — registers the wishlist session cookie as an
 * exclusion/vary cookie across the popular full-page cache plugins.
 *
 * Problem:
 *   The wishlist sets `cecomwishfw_session_<COOKIEHASH>` (see
 *   Cecomwishfw_Session_Model::get_session_cookie_name()). Unlike WooCommerce's
 *   own `wp_woocommerce_session_*` cookie, this one is NOT on any cache plugin's
 *   default exclusion list, so anonymous visitors with an active wishlist get
 *   served stale cached pages.
 *
 * Strategy (dual):
 *   1. RUNTIME FILTERS — registered on plugins_loaded. These hook into each
 *      cache plugin's own filter and work whenever the cache plugin is active,
 *      regardless of install order. Covers: WP Rocket, LiteSpeed, W3 Total
 *      Cache, Cache Enabler, WP-Optimize, Super Page Cache for Cloudflare.
 *
 *   2. ACTIVATION WRITES — called from Cecomwishfw_Activator::activate(). For
 *      cache plugins whose persistent config is read once (and whose runtime
 *      filters are either unreliable or nonexistent), append the cookie to the
 *      plugin's stored options. Covers: WP Rocket, Cache Enabler, WP Fastest
 *      Cache, WP-Optimize, Super Page Cache for Cloudflare. Each branch is
 *      guarded — silently no-ops when the cache plugin isn't installed, so
 *      activation can never error out.
 *
 *   3. RUNTIME FALLBACK — WP Super Cache and WP Fastest Cache do not expose a
 *      clean cookie-exclusion filter. For visitors carrying the wishlist
 *      cookie, we define DONOTCACHEPAGE early on `init`, which both plugins
 *      honor. This prevents storing a cached variant for that visitor without
 *      touching wp-cache-config.php or any other cache plugin config file.
 *      Super Page Cache for Cloudflare also honors DONOTCACHEPAGE for its
 *      local fallback layer; the Cloudflare EDGE layer is governed by CDN
 *      Page Rules and must be configured at Cloudflare itself.
 *
 * What this class does NOT do:
 *   - Change the wishlist cookie name, format, or lifetime — those remain in
 *     Cecomwishfw_Session_Model and are unaffected.
 *   - Add admin UI, settings, options, or DB tables.
 *   - Alter the existing set_nocache_headers() integration in the frontend
 *     controller (which bypasses cache on the wishlist page itself).
 *
 * @package Cecomwishfw
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Cecomwishfw_Cache_Compatibility
 */
class Cecomwishfw_Cache_Compatibility {

	/**
	 * Prefix of the wishlist session cookie. Must match the prefix used in
	 * Cecomwishfw_Session_Model::get_session_cookie_name() — that method
	 * appends COOKIEHASH, but cache plugins match on a prefix, so we keep
	 * the bare prefix here.
	 *
	 * @var string
	 */
	const COOKIE_PREFIX = 'cecomwishfw_session_';

	// =========================================================================
	// Runtime filters — registered on plugins_loaded via the loader.
	// =========================================================================

	/**
	 * Register runtime filter callbacks for every supported cache plugin.
	 *
	 * Called once, from Cecomwishfw_Loader on plugins_loaded priority 20 (so
	 * cache plugins have already declared their own filters). The add_filter()
	 * calls themselves are unconditional — filters for uninstalled cache
	 * plugins are simply never fired, so there is no harm in registering them.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function register_runtime_filters(): void {
		// WP Rocket reject-cookies filter.
		add_filter( 'rocket_cache_reject_cookies', array( __CLASS__, 'filter_append_cookie_prefix' ) );

		// LiteSpeed Cache vary-cookies filter.
		add_filter( 'litespeed_vary_cookies', array( __CLASS__, 'filter_append_cookie_prefix' ) );

		// W3 Total Cache — both Apache and Nginx page-cache reject lists.
		add_filter( 'w3tc_pgcache_rules_apache_reject_cookies', array( __CLASS__, 'filter_append_cookie_prefix' ) );
		add_filter( 'w3tc_pgcache_rules_nginx_reject_cookies', array( __CLASS__, 'filter_append_cookie_prefix' ) );

		// Cache Enabler — bypass_cache takes a boolean; return true when the
		// wishlist cookie is present on the request.
		add_filter( 'cache_enabler_bypass_cache', array( __CLASS__, 'filter_bypass_when_cookie_present' ) );

		// Super Page Cache for Cloudflare — `swcfpc_cache_bypass` takes a
		// boolean (return true to skip caching this request). The plugin has
		// no runtime filter for the cookie list, so we route through the
		// shared bypass-when-cookie-present callback.
		add_filter( 'swcfpc_cache_bypass', array( __CLASS__, 'filter_bypass_when_cookie_present' ) );

		// WP-Optimize — cookie-exceptions filter.
		add_filter( 'wpo_cache_cookie_exceptions', array( __CLASS__, 'filter_append_cookie_prefix' ) );

		// WP Super Cache + WP Fastest Cache — no usable runtime cookie filter,
		// so define DONOTCACHEPAGE early when the cookie is present. Both
		// plugins honor that constant. Priority 0 on 'init' runs before any
		// cache plugin's own init hook inspects the page.
		add_action( 'init', array( __CLASS__, 'maybe_bypass_for_cookie_carriers' ), 0 );
	}

	/**
	 * Append the wishlist cookie prefix to any cache plugin's reject-cookies
	 * filter that takes an array. Idempotent — never appends twice.
	 *
	 * @since 1.0.0
	 * @param mixed $cookies The existing cookie list from the cache plugin.
	 *                       Usually an array of strings, occasionally a
	 *                       newline-separated string on older W3TC.
	 * @return array<int, string>|string Updated list, same type as input when
	 *                                    practical.
	 */
	public static function filter_append_cookie_prefix( $cookies ) {
		// Newline-separated string form (older W3TC config rules).
		if ( is_string( $cookies ) ) {
			if ( false !== strpos( $cookies, self::COOKIE_PREFIX ) ) {
				return $cookies;
			}
			return '' === $cookies
				? self::COOKIE_PREFIX
				: $cookies . "\n" . self::COOKIE_PREFIX;
		}

		if ( ! is_array( $cookies ) ) {
			$cookies = array();
		}

		if ( ! in_array( self::COOKIE_PREFIX, $cookies, true ) ) {
			$cookies[] = self::COOKIE_PREFIX;
		}

		return $cookies;
	}

	/**
	 * Cache Enabler bypass callback — returns true (bypass cache) when the
	 * current request carries a wishlist cookie.
	 *
	 * @since 1.0.0
	 * @param bool $bypass Existing bypass decision from Cache Enabler.
	 * @return bool
	 */
	public static function filter_bypass_when_cookie_present( $bypass ): bool {
		return (bool) $bypass || self::request_has_wishlist_cookie();
	}

	/**
	 * Runtime fallback for WP Super Cache / WP Fastest Cache: on requests
	 * that carry a wishlist cookie, define DONOTCACHEPAGE so the current
	 * response is not stored as a cached variant.
	 *
	 * This prevents a cached copy from being *created* for the cookie-carrying
	 * visitor. It does NOT prevent serving an existing cached page that was
	 * stored before this runs — users should purge the cache once after
	 * plugin activation (which activation_writes() helps with).
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function maybe_bypass_for_cookie_carriers(): void {
		if ( ! self::request_has_wishlist_cookie() ) {
			return;
		}
		defined( 'DONOTCACHEPAGE' ) || define( 'DONOTCACHEPAGE', true );   // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- WP cache-compat constant
		defined( 'DONOTCACHEOBJECT' ) || define( 'DONOTCACHEOBJECT', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- WP cache-compat constant
		defined( 'DONOTCACHEDB' ) || define( 'DONOTCACHEDB', true );     // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- WP cache-compat constant
	}

	/**
	 * Whether the current request carries a wishlist session cookie.
	 *
	 * Matches any `$_COOKIE` key that begins with the COOKIE_PREFIX, which
	 * lets us cover the COOKIEHASH-suffixed real cookie name without having
	 * to reproduce the hashing logic here.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public static function request_has_wishlist_cookie(): bool {
		if ( empty( $_COOKIE ) || ! is_array( $_COOKIE ) ) {
			return false;
		}
		foreach ( array_keys( $_COOKIE ) as $name ) {
			if ( is_string( $name ) && 0 === strpos( $name, self::COOKIE_PREFIX ) ) {
				return true;
			}
		}
		return false;
	}

	// =========================================================================
	// Activation: write cookie exclusion into installed cache plugins' options.
	// =========================================================================

	/**
	 * Write the wishlist cookie into each installed cache plugin's persistent
	 * options at activation time. Silently skips any plugin that is not
	 * installed.
	 *
	 * Intentionally limited to cache plugins whose option schema is stable
	 * and where a direct write is safer than editing on-disk config files:
	 *   - WP Rocket
	 *   - Cache Enabler
	 *   - WP Fastest Cache
	 *   - WP-Optimize
	 *   - Super Page Cache for Cloudflare (local fallback layer only;
	 *     Cloudflare edge cache must be configured at the CDN level)
	 *
	 * LiteSpeed, W3TC, and WP Super Cache are handled by the runtime filters
	 * / DONOTCACHEPAGE fallback above, which is sufficient and safer than
	 * poking at their on-disk config.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function register_with_active_caches(): void {
		self::write_wp_rocket( true );
		self::write_cache_enabler( true );
		self::write_wp_fastest_cache( true );
		self::write_wp_optimize( true );
		self::write_super_page_cache( true );

		self::purge_active_caches();
	}

	/**
	 * Remove the wishlist cookie from each cache plugin's persistent options.
	 * Mirror of register_with_active_caches(), called on deactivation.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function unregister_from_active_caches(): void {
		self::write_wp_rocket( false );
		self::write_cache_enabler( false );
		self::write_wp_fastest_cache( false );
		self::write_wp_optimize( false );
		self::write_super_page_cache( false );

		self::purge_active_caches();
	}

	/**
	 * WP Rocket — append/remove the wishlist cookie prefix in
	 * wp_rocket_settings['cache_reject_cookies']. That field is stored as a
	 * newline-separated string of cookie-name patterns.
	 *
	 * @since 1.0.0
	 * @param bool $add True to add, false to remove.
	 * @return void
	 */
	private static function write_wp_rocket( bool $add ): void {
		if ( ! defined( 'WP_ROCKET_VERSION' ) && ! function_exists( 'rocket_clean_domain' ) ) {
			return;
		}

		$settings = get_option( 'wp_rocket_settings' );
		if ( ! is_array( $settings ) ) {
			return;
		}

		$raw     = isset( $settings['cache_reject_cookies'] ) ? (string) $settings['cache_reject_cookies'] : '';
		$split   = preg_split( '/\r\n|\r|\n/', $raw );
		$entries = array_filter( array_map( 'trim', false !== $split ? $split : array() ) );

		$changed = false;
		$present = in_array( self::COOKIE_PREFIX, $entries, true );

		if ( $add && ! $present ) {
			$entries[] = self::COOKIE_PREFIX;
			$changed   = true;
		} elseif ( ! $add && $present ) {
			$entries = array_values( array_diff( $entries, array( self::COOKIE_PREFIX ) ) );
			$changed = true;
		}

		if ( $changed ) {
			$settings['cache_reject_cookies'] = implode( "\n", $entries );
			update_option( 'wp_rocket_settings', $settings );
		}
	}

	/**
	 * Cache Enabler — append/remove the wishlist cookie prefix from the
	 * `excluded_cookies` field on the `cache_enabler` option, which stores
	 * cookies as a newline-separated string (matching the plugin's settings
	 * screen format).
	 *
	 * @since 1.0.0
	 * @param bool $add True to add, false to remove.
	 * @return void
	 */
	private static function write_cache_enabler( bool $add ): void {
		if ( ! class_exists( 'Cache_Enabler' ) ) {
			return;
		}

		$settings = get_option( 'cache_enabler' );
		if ( ! is_array( $settings ) ) {
			return;
		}

		$raw     = isset( $settings['excluded_cookies'] ) ? (string) $settings['excluded_cookies'] : '';
		$split   = preg_split( '/\r\n|\r|\n/', $raw );
		$entries = array_filter( array_map( 'trim', false !== $split ? $split : array() ) );

		$changed = false;
		$present = in_array( self::COOKIE_PREFIX, $entries, true );

		if ( $add && ! $present ) {
			$entries[] = self::COOKIE_PREFIX;
			$changed   = true;
		} elseif ( ! $add && $present ) {
			$entries = array_values( array_diff( $entries, array( self::COOKIE_PREFIX ) ) );
			$changed = true;
		}

		if ( $changed ) {
			$settings['excluded_cookies'] = implode( "\n", $entries );
			update_option( 'cache_enabler', $settings );
		}
	}

	/**
	 * WP Fastest Cache — append/remove the wishlist cookie prefix in the
	 * `WpFastestCacheExclude` option, which is a JSON-encoded array of
	 * exclusion rule objects (per the plugin's exclude-cookie rule format).
	 *
	 * @since 1.0.0
	 * @param bool $add True to add, false to remove.
	 * @return void
	 */
	private static function write_wp_fastest_cache( bool $add ): void {
		if ( ! class_exists( 'WpFastestCache' ) ) {
			return;
		}

		$raw   = get_option( 'WpFastestCacheExclude' );
		$rules = array();
		if ( is_string( $raw ) && '' !== $raw ) {
			$decoded = json_decode( $raw );
			if ( is_array( $decoded ) ) {
				$rules = $decoded;
			}
		}

		$changed     = false;
		$already_idx = null;
		foreach ( $rules as $idx => $rule ) {
			if ( is_object( $rule )
				&& isset( $rule->type, $rule->content )
				&& 'cookie' === $rule->type
				&& self::COOKIE_PREFIX === $rule->content
			) {
				$already_idx = $idx;
				break;
			}
		}

		if ( $add && null === $already_idx ) {
			$rules[] = (object) array(
				'prefix'  => 'startwith',
				'content' => self::COOKIE_PREFIX,
				'type'    => 'cookie',
			);
			$changed = true;
		} elseif ( ! $add && null !== $already_idx ) {
			unset( $rules[ $already_idx ] );
			$rules   = array_values( $rules );
			$changed = true;
		}

		if ( $changed ) {
			update_option( 'WpFastestCacheExclude', wp_json_encode( $rules ) );
		}
	}

	/**
	 * WP-Optimize — append/remove the wishlist cookie prefix in the
	 * `wpo_cache_config` option's `cache_exception_cookies` array. WP-Optimize
	 * stores its page-cache config as a serialized option; we update it in
	 * place and let the plugin read the new value on its next request.
	 *
	 * @since 1.0.0
	 * @param bool $add True to add, false to remove.
	 * @return void
	 */
	private static function write_wp_optimize( bool $add ): void {
		if ( ! class_exists( 'WP_Optimize' ) && ! class_exists( 'WPO_Page_Cache' ) ) {
			return;
		}

		$config = get_option( 'wpo_cache_config' );
		if ( ! is_array( $config ) ) {
			return;
		}

		$entries = isset( $config['cache_exception_cookies'] ) && is_array( $config['cache_exception_cookies'] )
			? $config['cache_exception_cookies']
			: array();

		$changed = false;
		$present = in_array( self::COOKIE_PREFIX, $entries, true );

		if ( $add && ! $present ) {
			$entries[] = self::COOKIE_PREFIX;
			$changed   = true;
		} elseif ( ! $add && $present ) {
			$entries = array_values( array_diff( $entries, array( self::COOKIE_PREFIX ) ) );
			$changed = true;
		}

		if ( $changed ) {
			$config['cache_exception_cookies'] = $entries;
			update_option( 'wpo_cache_config', $config );
		}
	}

	/**
	 * Super Page Cache for Cloudflare — append/remove the wishlist cookie
	 * prefix in `swcfpc_config['cf_fallback_cache_excluded_cookies']`. The
	 * plugin treats each entry as a regex pattern that is matched against
	 * cookie names via preg_grep(), so the bare prefix produces a substring
	 * match against the COOKIEHASH-suffixed real cookie name.
	 *
	 * Detection covers both the legacy global class (`SWCFPC_Backend` /
	 * `SWCFPC_PLUGIN_PATH`) and the namespaced rewrite (`SPC\Services\Settings_Store`)
	 * shipped from v5.x onwards.
	 *
	 * Note: this only affects the plugin's local fallback cache. The
	 * Cloudflare EDGE cache must be configured separately at the CDN level
	 * (Cloudflare Page Rules / Cache Rules); no WordPress plugin can write
	 * Cloudflare-side rules without an API token, which is outside scope.
	 *
	 * @since 1.0.0
	 * @param bool $add True to add, false to remove.
	 * @return void
	 */
	private static function write_super_page_cache( bool $add ): void {
		if ( ! defined( 'SWCFPC_PLUGIN_PATH' )
			&& ! class_exists( 'SWCFPC_Backend' )
			&& ! class_exists( 'SPC\\Services\\Settings_Store' )
		) {
			return;
		}

		$config = get_option( 'swcfpc_config' );
		if ( ! is_array( $config ) ) {
			return;
		}

		$entries = isset( $config['cf_fallback_cache_excluded_cookies'] ) && is_array( $config['cf_fallback_cache_excluded_cookies'] )
			? $config['cf_fallback_cache_excluded_cookies']
			: array();

		$changed = false;
		$present = in_array( self::COOKIE_PREFIX, $entries, true );

		if ( $add && ! $present ) {
			$entries[] = self::COOKIE_PREFIX;
			$changed   = true;
		} elseif ( ! $add && $present ) {
			$entries = array_values( array_diff( $entries, array( self::COOKIE_PREFIX ) ) );
			$changed = true;
		}

		if ( $changed ) {
			$config['cf_fallback_cache_excluded_cookies'] = $entries;
			update_option( 'swcfpc_config', $config );
		}
	}

	/**
	 * Purge any installed cache plugin's stored pages so the newly-excluded
	 * cookie takes effect immediately after activation / deactivation.
	 *
	 * Each branch is guarded — absent plugins are silently skipped.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function purge_active_caches(): void {
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}
		if ( is_callable( array( 'LiteSpeed\Purge', 'purge_all' ) ) ) {
			call_user_func( array( 'LiteSpeed\Purge', 'purge_all' ) );
		}
		if ( function_exists( 'w3tc_pgcache_flush' ) ) {
			w3tc_pgcache_flush();
		}
		if ( is_callable( array( 'Cache_Enabler', 'clear_complete_cache' ) ) ) {
			call_user_func( array( 'Cache_Enabler', 'clear_complete_cache' ) );
		}
		if ( function_exists( 'wp_cache_clear_cache' ) ) {
			wp_cache_clear_cache();
		}
		if ( function_exists( 'wpfc_clear_all_cache' ) ) {
			wpfc_clear_all_cache();
		}
		if ( is_callable( array( 'WPO_Page_Cache', 'instance' ) ) ) {
			$instance = call_user_func( array( 'WPO_Page_Cache', 'instance' ) );
			if ( is_object( $instance ) && method_exists( $instance, 'purge' ) ) {
				$instance->purge();
			}
		}
		// Super Page Cache for Cloudflare — fires the plugin's own programmatic
		// purge action if it has been registered on `init` already, otherwise
		// silently no-ops. The action is the plugin's documented external entry
		// point for triggering a full purge.
		if ( has_action( 'swcfpc_purge_cache_programmatically' ) ) {
			do_action( 'swcfpc_purge_cache_programmatically' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- third-party Super Page Cache for Cloudflare hook
		}
	}
}
