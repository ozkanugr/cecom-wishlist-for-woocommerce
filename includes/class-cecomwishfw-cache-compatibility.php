<?php
/**
 * Cache plugin compatibility — excludes the wishlist page from full-page caches.
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
	 * Register runtime filter callbacks for every supported cache plugin.
	 *
	 * @return void
	 */
	public static function register_runtime_filters(): void {
		add_filter( 'rocket_cache_reject_uri', array( __CLASS__, 'filter_reject_wishlist_uri' ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- third-party WP Rocket filter
		add_filter( 'w3tc_pgcache_rules_apache_reject_uri', array( __CLASS__, 'filter_reject_wishlist_uri' ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- third-party W3 Total Cache filter
		add_filter( 'w3tc_pgcache_rules_nginx_reject_uri', array( __CLASS__, 'filter_reject_wishlist_uri' ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- third-party W3 Total Cache filter
		add_filter( 'cache_enabler_bypass_cache', array( __CLASS__, 'filter_bypass_for_wishlist_page' ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- third-party Cache Enabler filter
		add_filter( 'swcfpc_cache_bypass', array( __CLASS__, 'filter_bypass_for_wishlist_page' ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- third-party Super Page Cache for Cloudflare filter
		add_filter( 'litespeed_is_uri_excluded', array( __CLASS__, 'filter_bypass_for_wishlist_page' ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- third-party LiteSpeed Cache filter
		add_filter( 'wpo_page_cache_exclude', array( __CLASS__, 'filter_bypass_for_wishlist_page' ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- third-party WP Optimize filter
		add_filter( 'wphb_cache_bypass', array( __CLASS__, 'filter_bypass_for_wishlist_page' ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- third-party Hummingbird filter

		// Priority 0 fires before any cache plugin's own init hook.
		add_action( 'init', array( __CLASS__, 'maybe_set_donotcache' ), 0 );

		// WP Fastest Cache — must be called during page rendering, after WordPress knows what page is served.
		add_action( 'template_redirect', array( __CLASS__, 'maybe_exclude_from_wpfc' ), 1 );
	}

	/**
	 * Return the relative URI path for the configured wishlist page, or '' if none.
	 *
	 * @return string
	 */
	private static function get_wishlist_page_uri(): string {
		$options = get_option( 'cecomwishfw_settings', array() );
		$page_id = (int) ( $options['general']['wishlist_page_id'] ?? 0 );
		if ( $page_id <= 0 ) {
			return '';
		}
		$uri = get_page_uri( $page_id );
		return $uri ? trailingslashit( $uri ) : '';
	}

	/**
	 * Whether the current server request is for the wishlist page.
	 *
	 * @return bool
	 */
	private static function is_current_request_wishlist_page(): bool {
		$wishlist_uri = self::get_wishlist_page_uri();
		if ( '' === $wishlist_uri ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- path comparison only, not output
		$request_path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';

		$pos = strpos( $request_path, '?' );
		if ( false !== $pos ) {
			$request_path = substr( $request_path, 0, $pos );
		}
		$request_path = trailingslashit( $request_path );

		$home_path = (string) wp_parse_url( home_url(), PHP_URL_PATH );
		$home_path = $home_path ? trailingslashit( $home_path ) : '/';
		$expected  = $home_path . ltrim( $wishlist_uri, '/' );

		return $request_path === $expected;
	}

	/**
	 * Append the wishlist page URI to a cache plugin's reject-URI list.
	 *
	 * @param mixed $uris Existing URI list from the cache plugin.
	 * @return array<int, string>|string
	 */
	public static function filter_reject_wishlist_uri( $uris ) {
		$wishlist_uri = self::get_wishlist_page_uri();
		if ( '' === $wishlist_uri ) {
			return $uris;
		}

		if ( is_string( $uris ) ) {
			if ( false !== strpos( $uris, $wishlist_uri ) ) {
				return $uris;
			}
			return '' === $uris
				? $wishlist_uri
				: $uris . "\n" . $wishlist_uri;
		}

		if ( ! is_array( $uris ) ) {
			$uris = array();
		}

		if ( ! in_array( $wishlist_uri, $uris, true ) ) {
			$uris[] = $wishlist_uri;
		}

		return $uris;
	}

	/**
	 * Bypass cache when the current request is for the wishlist page.
	 *
	 * @param mixed $bypass Existing bypass decision from the cache plugin.
	 * @return bool
	 */
	public static function filter_bypass_for_wishlist_page( $bypass ): bool {
		return (bool) $bypass || self::is_current_request_wishlist_page();
	}

	/**
	 * Define DONOTCACHE* constants and send nocache headers on wishlist page requests.
	 *
	 * @return void
	 */
	public static function maybe_set_donotcache(): void {
		if ( ! self::is_current_request_wishlist_page() ) {
			return;
		}
		defined( 'DONOTCACHEPAGE' ) || define( 'DONOTCACHEPAGE', true );    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- WP cache-compat constant
		defined( 'DONOTCACHEOBJECT' ) || define( 'DONOTCACHEOBJECT', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- WP cache-compat constant
		defined( 'DONOTCACHEDB' ) || define( 'DONOTCACHEDB', true );         // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- WP cache-compat constant
		nocache_headers();
	}

	/**
	 * Exclude the wishlist page from WP Fastest Cache via its documented function.
	 *
	 * Must run on template_redirect so WordPress knows what page is being served.
	 *
	 * @return void
	 */
	public static function maybe_exclude_from_wpfc(): void {
		if ( ! function_exists( 'wpfc_exclude_current_page' ) ) {
			return;
		}
		$options = get_option( 'cecomwishfw_settings', array() );
		$page_id = (int) ( $options['general']['wishlist_page_id'] ?? 0 );
		if ( $page_id > 0 && is_page( $page_id ) ) {
			wpfc_exclude_current_page(); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- WP Fastest Cache documented exclusion function
		}
	}

	/**
	 * Register URI-exclusion filters, rebuild cache-plugin rule files, and purge pages on activation.
	 *
	 * Manual registration is required because plugins_loaded does not fire again for the
	 * plugin being activated in the same request.
	 *
	 * @return void
	 */
	public static function register_with_active_caches(): void {
		add_filter( 'rocket_cache_reject_uri', array( __CLASS__, 'filter_reject_wishlist_uri' ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- third-party WP Rocket filter
		add_filter( 'w3tc_pgcache_rules_apache_reject_uri', array( __CLASS__, 'filter_reject_wishlist_uri' ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- third-party W3 Total Cache filter
		add_filter( 'w3tc_pgcache_rules_nginx_reject_uri', array( __CLASS__, 'filter_reject_wishlist_uri' ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- third-party W3 Total Cache filter

		if ( function_exists( 'rocket_generate_config_file' ) ) {
			rocket_generate_config_file();
		}
		if ( function_exists( 'flush_rocket_htaccess' ) ) {
			flush_rocket_htaccess();
		}

		self::purge_active_caches();
	}

	/**
	 * Rebuild cache-plugin rule files without the wishlist URI and purge pages on deactivation.
	 *
	 * @return void
	 */
	public static function unregister_from_active_caches(): void {
		if ( function_exists( 'rocket_generate_config_file' ) ) {
			rocket_generate_config_file();
		}
		if ( function_exists( 'flush_rocket_htaccess' ) ) {
			flush_rocket_htaccess();
		}

		self::purge_active_caches();
	}

	/**
	 * Remove the URI-exclusion filter callbacks. Idempotent.
	 *
	 * @return void
	 */
	public static function deregister_uri_filters(): void {
		remove_filter( 'rocket_cache_reject_uri', array( __CLASS__, 'filter_reject_wishlist_uri' ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- third-party WP Rocket filter
		remove_filter( 'w3tc_pgcache_rules_apache_reject_uri', array( __CLASS__, 'filter_reject_wishlist_uri' ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- third-party W3 Total Cache filter
		remove_filter( 'w3tc_pgcache_rules_nginx_reject_uri', array( __CLASS__, 'filter_reject_wishlist_uri' ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- third-party W3 Total Cache filter
	}

	/**
	 * Purge any installed cache plugin's stored pages. Absent plugins are silently skipped.
	 *
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
		// 'swcfpc_purge_cache_programmatically' is the documented action defined by
		// the "Super Page Cache for Cloudflare" (SWCFPC) plugin. We are CALLING it,
		// not declaring it — the swcfpc_ prefix belongs to that plugin, not to ours.
		if ( has_action( 'swcfpc_purge_cache_programmatically' ) ) {
			do_action( 'swcfpc_purge_cache_programmatically' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- third-party Super Page Cache for Cloudflare hook; hook name is not ours to change
		}
		if ( function_exists( 'sg_cachepress_purge_cache' ) ) {
			sg_cachepress_purge_cache(); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- third-party SiteGround Optimizer function
		}
		if ( has_action( 'wphb_clear_page_cache' ) ) {
			do_action( 'wphb_clear_page_cache' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- third-party Hummingbird action
		}
	}
}
