<?php
/**
 * Cookie and privacy disclosure for CECOM Wishlist for WooCommerce.
 *
 * Makes plugin cookies discoverable by cookie consent plugins by:
 *
 * 1. Providing a structured `cecomwishfw_cookie_data` filter that returns
 *    every cookie's ID, domain, duration, script URL pattern, and description.
 *    Cookie plugins can apply this filter to read the registry programmatically:
 *
 *      $cookies = apply_filters( 'cecomwishfw_cookie_data', array() );
 *
 * 2. Contributing a formatted privacy policy section to the WordPress
 *    Privacy Policy builder (Tools → Privacy → Privacy Policy Guide).
 *
 * @package Cecomwishfw
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Cecomwishfw_Privacy
 */
class Cecomwishfw_Privacy {

	/**
	 * Return structured cookie metadata for this plugin.
	 *
	 * Each array entry contains the following keys:
	 *
	 *  - id               : Cookie name pattern. Wildcard * denotes the
	 *                        install-specific COOKIEHASH suffix.
	 *  - name             : Resolved cookie name on this install.
	 *  - domain           : Cookie domain (COOKIE_DOMAIN or site host).
	 *  - duration         : Human-readable lifetime.
	 *  - duration_seconds : Lifetime in seconds. 0 means browser/tab session.
	 *  - script_url       : Absolute URL of the JS file that writes the cookie.
	 *                        Empty string for server-set (PHP) cookies.
	 *  - type             : 'HTTP' | 'localStorage' | 'sessionStorage'.
	 *  - category         : 'functional' | 'preferences' | 'statistics' | 'marketing'.
	 *  - description      : Plain-text description of purpose.
	 *  - plugin           : Human-readable plugin name.
	 *  - httponly         : bool. true = HTTP-only flag set.
	 *  - secure           : bool. true = Secure flag set (HTTPS only).
	 *  - samesite         : 'Strict' | 'Lax' | 'None'. Empty for storage items.
	 *
	 * @param array $cookies Accumulated definitions from earlier filter callbacks.
	 * @return array
	 */
	public function get_cookies( array $cookies ): array {
		$domain = $this->cookie_domain();

		$cookies[] = array(
			'id'               => 'cecomwishfw_session_*',
			'name'             => 'cecomwishfw_session_' . COOKIEHASH,
			'domain'           => $domain,
			'duration'         => __( '30 days', 'cecom-wishlist-for-woocommerce' ),
			'duration_seconds' => 30 * DAY_IN_SECONDS,
			'script_url'       => CECOMWISHFW_PLUGIN_URL . 'assets/js/cecomwishfw-frontend.js',
			'type'             => 'HTTP',
			'category'         => 'functional',
			'description'      => __( 'Stores a temporary session token for guest (non-logged-in) wishlist access. Contains a HMAC-signed session identifier and expiry timestamp. No personally identifiable information is stored in the cookie itself. Deleted automatically when the session expires (30 days after creation).', 'cecom-wishlist-for-woocommerce' ),
			'plugin'           => 'CECOM Wishlist for WooCommerce',
			'httponly'         => true,
			'secure'           => true,
			'samesite'         => 'Lax',
		);

		return $cookies;
	}

	/**
	 * Register plugin cookies with Complianz (complianz-gdpr).
	 *
	 * Complianz reads its cookie registry from the `cmplz_cookies` filter.
	 * Each entry supplies the exact cookie name, category, script URL, and
	 * a language-keyed description array so that the Complianz scanner can
	 * surface the description and script URL pattern in its cookie overview.
	 *
	 * Safe to call when Complianz is not installed — WordPress filters are
	 * no-ops when nothing applies them.
	 *
	 * @param array $cookies Accumulated Complianz cookie definitions.
	 * @return array
	 */
	public function register_complianz_cookies( array $cookies ): array {
		$cookies[] = array(
			'ID'               => 'cecomwishfw_session_' . COOKIEHASH,
			'is_regex'         => false,
			'service'          => 'CECOM Wishlist for WooCommerce',
			'service_category' => 'Functional',
			'set_by_javascript' => false,
			'expires'          => 30 * DAY_IN_SECONDS,
			'domain'           => '',
			'httponly'         => true,
			'secure'           => false,
			'samesite'         => 'Lax',
			'script_url'       => CECOMWISHFW_PLUGIN_URL . 'assets/js/cecomwishfw-frontend.js',
			'en'               => array(
				'description' => 'Stores a temporary session token for guest (non-logged-in) wishlist access. Contains a HMAC-signed session identifier and expiry timestamp. No personally identifiable information is stored in the cookie itself.',
				'expiration'  => '30 days',
			),
		);
		return $cookies;
	}

	/**
	 * Contribute cookie disclosure to the WordPress Privacy Policy builder.
	 *
	 * Called on `admin_init`. Site administrators find the generated text at
	 * Tools → Privacy → Privacy Policy Guide and can copy it into their policy.
	 *
	 * @return void
	 */
	public function register_privacy_policy_content(): void {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}
		wp_add_privacy_policy_content(
			'CECOM Wishlist for WooCommerce',
			wp_kses_post( $this->policy_html() )
		);
	}

	/**
	 * Build the privacy policy disclosure HTML.
	 *
	 * @return string
	 */
	protected function policy_html(): string {
		$cookie_name = 'cecomwishfw_session_' . COOKIEHASH;
		$domain      = $this->cookie_domain();
		$js_url      = CECOMWISHFW_PLUGIN_URL . 'assets/js/cecomwishfw-frontend.js';

		$rows  = '<tr>';
		$rows .= '<td><code>' . esc_html( $cookie_name ) . '</code></td>';
		$rows .= '<td>' . esc_html( $domain ) . '</td>';
		$rows .= '<td>' . esc_html__( '30 days', 'cecom-wishlist-for-woocommerce' ) . '</td>';
		$rows .= '<td><code>' . esc_url( $js_url ) . '</code></td>';
		$rows .= '<td>' . esc_html__( 'Stores a temporary session token for guest (non-logged-in) wishlist access. Contains a HMAC-signed session identifier and expiry timestamp. No personally identifiable information is stored in the cookie itself. Deleted automatically when the session expires (30 days after creation).', 'cecom-wishlist-for-woocommerce' ) . '</td>';
		$rows .= '</tr>';

		return '<h2>' . esc_html__( 'Wishlist — Cookies', 'cecom-wishlist-for-woocommerce' ) . '</h2>'
			. '<p>' . esc_html__( 'This plugin uses one HTTP cookie to provide guest wishlist functionality. No data is shared with external services.', 'cecom-wishlist-for-woocommerce' ) . '</p>'
			. '<table>'
				. '<thead><tr>'
					. '<th>' . esc_html__( 'Cookie ID', 'cecom-wishlist-for-woocommerce' ) . '</th>'
					. '<th>' . esc_html__( 'Domain', 'cecom-wishlist-for-woocommerce' ) . '</th>'
					. '<th>' . esc_html__( 'Duration', 'cecom-wishlist-for-woocommerce' ) . '</th>'
					. '<th>' . esc_html__( 'Script URL Pattern', 'cecom-wishlist-for-woocommerce' ) . '</th>'
					. '<th>' . esc_html__( 'Description', 'cecom-wishlist-for-woocommerce' ) . '</th>'
				. '</tr></thead>'
				. '<tbody>' . $rows . '</tbody>'
			. '</table>';
	}

	/**
	 * Resolve the effective cookie domain for this install.
	 *
	 * Returns COOKIE_DOMAIN when defined and non-empty; otherwise falls back
	 * to the hostname from home_url().
	 *
	 * @return string
	 */
	protected function cookie_domain(): string {
		if ( defined( 'COOKIE_DOMAIN' ) && is_string( COOKIE_DOMAIN ) && '' !== COOKIE_DOMAIN ) {
			return COOKIE_DOMAIN;
		}
		return (string) wp_parse_url( home_url(), PHP_URL_HOST );
	}
}
