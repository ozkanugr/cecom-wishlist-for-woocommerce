<?php
/**
 * Frontend controller — storefront rendering and asset management.
 *
 * Responsibilities:
 *   enqueue_scripts()      — storefront CSS/JS + JS config (all pages)
 *   enqueue_admin_assets() — admin CSS/JS (gated to plugin settings page)
 *   render_button()        — Add to Wishlist button on product pages
 *   render_loop_button()   — button on shop loop / archive pages
 *   register_shortcode()   — [cecomwishfw_wishlist] shortcode
 *   register_block()       — cecomwishfw/wishlist Gutenberg block
 *
 * @package Cecomwishfw
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Cecomwishfw_Frontend_Controller
 */
class Cecomwishfw_Frontend_Controller {

	/**
	 * Settings model instance.
	 *
	 * @var Cecomwishfw_Settings
	 */
	private Cecomwishfw_Settings $settings;

	/**
	 * Item model instance.
	 *
	 * @var Cecomwishfw_Item_Model
	 */
	private Cecomwishfw_Item_Model $item_model;

	/**
	 * Session model instance.
	 *
	 * @var Cecomwishfw_Session_Model
	 */
	private Cecomwishfw_Session_Model $session;

	/**
	 * Constructor — inject model dependencies.
	 */
	public function __construct() {
		$this->settings   = new Cecomwishfw_Settings();
		$this->item_model = new Cecomwishfw_Item_Model();
		$this->session    = new Cecomwishfw_Session_Model();
	}

	// =========================================================================
	// Asset enqueuing (ffr-1.2, ffr-1.3, fax-6)
	// =========================================================================

	/**
	 * Enqueue frontend scripts and styles on storefront pages.
	 *
	 * Hooked on 'wp_enqueue_scripts'. Assets are bundled locally — no CDN.
	 *
	 * @return void
	 */
	public function enqueue_scripts(): void {
		if ( is_admin() ) {
			return;
		}

		$url     = CECOMWISHFW_PLUGIN_URL;
		$dir     = CECOMWISHFW_PLUGIN_DIR;
		$version = CECOMWISHFW_VERSION;

		// Bootstrap Icons (bundled).
		wp_enqueue_style( 'cecomwishfw-bs-icons', $url . 'assets/icons/font/bootstrap-icons.css', array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- bundled asset, version omitted intentionally

		// Frontend CSS.
		$css = $dir . 'assets/css/cecomwishfw-frontend.css';
		wp_enqueue_style(
			'cecomwishfw-frontend',
			$url . 'assets/css/cecomwishfw-frontend.css',
			array( 'cecomwishfw-bs-icons' ),
			file_exists( $css ) ? (string) filemtime( $css ) : $version
		);

		// Per-button CSS overrides. Four appearance modes are supported.
		// Padding, margin, and font-size are honored in every mode so the
		// Appearance tab controls work consistently regardless of the selected mode.
		// '' (default)  — --cwfw-primary color + padding + margin + font-size. Legacy behavior.
		// 'textual'     — link-like reset (transparent bg, no border, underline);
		// honors color, font-size, padding (falls back to 0), and margin.
		// 'theme'       — revert plugin styling so the active WP theme's <button>
		// styles take over. User padding, margin, and font-size
		// override the revert when set, else revert wins.
		// 'custom'      — full control: bg, text, border, radius, border-width,
		// opacity + hover variants. Active state mirrors hover
		// colors so the "in wishlist" indicator stays coherent.
		// Padding, margin, and font-size are emitted in the idle
		// block and inherit naturally to hover/active states.
		//
		// Values read from the settings model are already validated (hex regex,
		// CSS-dimension regex, enum allowlist, opacity clamp) — that validation
		// IS the escape for inline CSS because no `;`, `}`, or `<` can survive it.
		$inline_css = '';
		foreach ( array( 'single', 'loop' ) as $ctx ) {
			$type      = (string) $this->settings->get( 'appearance', $ctx . '_appearance_type', '' );
			$color     = (string) $this->settings->get( 'appearance', $ctx . '_button_color', '' );
			$padding   = (string) $this->settings->get( 'appearance', $ctx . '_padding', '' );
			$margin    = (string) $this->settings->get( 'appearance', $ctx . '_margin', '' );
			$font_size = (string) $this->settings->get( 'appearance', $ctx . '_font_size', '' );
			$base      = '.cecomwishfw-btn--' . $ctx;
			$all       = $base . ',' . $base . ':hover,' . $base . '.active,' . $base . '.active:hover';

			if ( 'textual' === $type ) {
				// Link-like reset across every state: transparent bg, no border/radius,
				// underlined text that inherits color. Padding falls back to 0 for a
				// tight link look when the user hasn't set a value — but user-provided
				// padding, margin, font-size, and color all win and apply across all
				// states so hover/active stay visually consistent.
				$rules  = 'background:transparent;border:0;border-radius:0;text-decoration:underline;color:inherit;';
				$rules .= 'padding:' . ( '' !== $padding ? $padding : '0' ) . ';';
				if ( '' !== $margin ) {
					$rules .= 'margin:' . $margin . ';';
				}
				if ( '' !== $color && preg_match( '/^#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $color ) ) {
					$rules .= 'color:' . $color . ';';
				}
				if ( '' !== $font_size ) {
					$rules .= 'font-size:' . $font_size . ';';
				}
				$inline_css .= $all . '{' . $rules . '}';
				continue;
			}

			if ( 'theme' === $type ) {
				// Hands-off: revert plugin styling so theme CSS wins. Padding,
				// margin, and font-size fall through to `revert` when unset, but
				// user-provided values override the revert so Appearance-tab
				// sliders still work.
				$padding_rule   = '' !== $padding ? ( 'padding:' . $padding . ';' ) : 'padding:revert;';
				$margin_rule    = '' !== $margin ? ( 'margin:' . $margin . ';' ) : 'margin:revert;';
				$font_size_rule = '' !== $font_size ? ( 'font-size:' . $font_size . ';' ) : 'font-size:revert;';
				$inline_css    .= $all . '{background:revert;border:revert;border-radius:revert;color:revert;' . $padding_rule . $margin_rule . $font_size_rule . '}';
				continue;
			}

			if ( 'custom' === $type ) {
				// Per-color merge: combine each hex with its own opacity into an
				// rgba() value. When opacity is empty or 1, the original hex is
				// returned unchanged so saved-only-color configurations still
				// emit compact hex rules. Pure client-side validation already
				// guarantees valid hex + float 0..1 from the model.
				$merge = static function ( string $hex, string $alpha ): string {
					if ( '' === $hex ) {
						return '';
					}
					if ( '' === $alpha || '1' === $alpha ) {
						return $hex;
					}
					$clean = ltrim( $hex, '#' );
					if ( 3 === strlen( $clean ) ) {
						$clean = $clean[0] . $clean[0] . $clean[1] . $clean[1] . $clean[2] . $clean[2];
					}
					if ( 6 !== strlen( $clean ) ) {
						return $hex;
					}
					$r = hexdec( substr( $clean, 0, 2 ) );
					$g = hexdec( substr( $clean, 2, 2 ) );
					$b = hexdec( substr( $clean, 4, 2 ) );
					return 'rgba(' . $r . ',' . $g . ',' . $b . ',' . $alpha . ')';
				};

				$bg           = $merge(
					(string) $this->settings->get( 'appearance', $ctx . '_custom_bg', '' ),
					(string) $this->settings->get( 'appearance', $ctx . '_custom_bg_opacity', '' )
				);
				$text         = $merge(
					(string) $this->settings->get( 'appearance', $ctx . '_custom_text', '' ),
					(string) $this->settings->get( 'appearance', $ctx . '_custom_text_opacity', '' )
				);
				$border       = $merge(
					(string) $this->settings->get( 'appearance', $ctx . '_custom_border', '' ),
					(string) $this->settings->get( 'appearance', $ctx . '_custom_border_opacity', '' )
				);
				$bg_hover     = $merge(
					(string) $this->settings->get( 'appearance', $ctx . '_custom_bg_hover', '' ),
					(string) $this->settings->get( 'appearance', $ctx . '_custom_bg_hover_opacity', '' )
				);
				$text_hover   = $merge(
					(string) $this->settings->get( 'appearance', $ctx . '_custom_text_hover', '' ),
					(string) $this->settings->get( 'appearance', $ctx . '_custom_text_hover_opacity', '' )
				);
				$border_hover = $merge(
					(string) $this->settings->get( 'appearance', $ctx . '_custom_border_hover', '' ),
					(string) $this->settings->get( 'appearance', $ctx . '_custom_border_hover_opacity', '' )
				);

				// Hover fallback: when a *_hover variant is empty, reuse the
				// idle value so the user's custom colors persist across :hover
				// and .active states. Without this, the base plugin CSS
				// (.cecomwishfw-btn:hover / .active rules) has higher
				// specificity than the single-class .cecomwishfw-btn--{ctx}
				// idle selector and silently clobbers the user's bg / text /
				// border the moment the button is hovered or added to the
				// wishlist. Applied after $merge so rgba conversion is
				// preserved for fields that had alpha configured.
				if ( '' === $bg_hover ) {
					$bg_hover = $bg;
				}
				if ( '' === $text_hover ) {
					$text_hover = $text;
				}
				if ( '' === $border_hover ) {
					$border_hover = $border;
				}

				$radius       = (string) $this->settings->get( 'appearance', $ctx . '_custom_radius', '' );
				$border_width = (string) $this->settings->get( 'appearance', $ctx . '_custom_border_width', '' );

				// Base (idle) rules — each property only emitted when present.
				$base_rules = '';
				if ( '' !== $bg ) {
					$base_rules .= 'background:' . $bg . ';';
				}
				if ( '' !== $text ) {
					$base_rules .= 'color:' . $text . ';';
				}
				if ( '' !== $border ) {
					$base_rules .= 'border-color:' . $border . ';';
				}
				if ( '' !== $border_width ) {
					$base_rules .= 'border-width:' . $border_width . ';border-style:solid;';
				}
				if ( '' !== $radius ) {
					$base_rules .= 'border-radius:' . $radius . ';';
				}
				// Padding, margin, and font-size: emitted in the idle block and
				// inherit to hover/active states naturally through the CSS
				// cascade, matching how bg / text / border properties are
				// handled above.
				if ( '' !== $padding ) {
					$base_rules .= 'padding:' . $padding . ';';
				}
				if ( '' !== $margin ) {
					$base_rules .= 'margin:' . $margin . ';';
				}
				if ( '' !== $font_size ) {
					$base_rules .= 'font-size:' . $font_size . ';';
				}
				if ( '' !== $base_rules ) {
					$inline_css .= $base . '{' . $base_rules . '}';
				}

				// Hover rules — active state mirrors hover so the "added" indicator stays coherent.
				$hover_rules = '';
				if ( '' !== $bg_hover ) {
					$hover_rules .= 'background:' . $bg_hover . ';';
				}
				if ( '' !== $text_hover ) {
					$hover_rules .= 'color:' . $text_hover . ';';
				}
				if ( '' !== $border_hover ) {
					$hover_rules .= 'border-color:' . $border_hover . ';';
				}
				if ( '' !== $hover_rules ) {
					$inline_css .= $base . ':hover,' . $base . '.active,' . $base . '.active:hover{' . $hover_rules . '}';
				}
				continue;
			}

			// Default branch — legacy behavior: --cwfw-primary + padding + margin + font-size.
			$rules = '';
			if ( '' !== $color && preg_match( '/^#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $color ) ) {
				$rules .= '--cwfw-primary:' . $color . ';--cwfw-primary-hover:' . $color . ';';
			}
			if ( '' !== $padding ) {
				$rules .= 'padding:' . $padding . ';';
			}
			if ( '' !== $margin ) {
				$rules .= 'margin:' . $margin . ';';
			}
			if ( '' !== $font_size ) {
				$rules .= 'font-size:' . $font_size . ';';
			}
			if ( '' !== $rules ) {
				$inline_css .= $base . '{' . $rules . '}';
			}
		}

		// Free-form Custom CSS — appended LAST so user rules cascade after
		// the computed per-context block. wp_strip_all_tags() is applied as
		// defense-in-depth even though the model already sanitized on save;
		// invalid CSS syntax is harmlessly ignored by the browser parser.
		$custom_css = (string) $this->settings->get( 'appearance', 'custom_css', '' );
		if ( '' !== $custom_css ) {
			$inline_css .= wp_strip_all_tags( $custom_css );
		}

		if ( '' !== $inline_css ) {
			wp_add_inline_style( 'cecomwishfw-frontend', $inline_css );
		}

		// Frontend JS (IIFE, jQuery dep).
		$js = $dir . 'assets/js/cecomwishfw-frontend.js';
		wp_enqueue_script(
			'cecomwishfw-frontend',
			$url . 'assets/js/cecomwishfw-frontend.js',
			array( 'jquery' ),
			file_exists( $js ) ? (string) filemtime( $js ) : $version,
			true
		);

		// JS config injected via wp_localize_script — NOT on admin pages.
		wp_localize_script(
			'cecomwishfw-frontend',
			'cecomwishfwFrontend',
			array(
				'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'cecomwishfw_frontend' ),
				'i18n'     => array(
					'addedToWishlist'     => __( 'Added to wishlist', 'cecom-wishlist-for-woocommerce' ),
					'removedFromWishlist' => __( 'Removed from wishlist', 'cecom-wishlist-for-woocommerce' ),
					'addToWishlist'       => __( 'Add to wishlist', 'cecom-wishlist-for-woocommerce' ),
					'removeFromWishlist'  => __( 'Remove from wishlist', 'cecom-wishlist-for-woocommerce' ),
					'error'               => __( 'Something went wrong. Please try again.', 'cecom-wishlist-for-woocommerce' ),
					'linkCopied'          => __( 'Link copied!', 'cecom-wishlist-for-woocommerce' ),
					'linkRegenerated'     => __( 'Link regenerated!', 'cecom-wishlist-for-woocommerce' ),
					'loginRequired'       => __( 'Login required!', 'cecom-wishlist-for-woocommerce' ),
				),
				'settings' => array(
					'removeOnCart'     => $this->settings->get( 'general', 'remove_on_cart' ),
					'redirectCheckout' => $this->settings->get( 'general', 'redirect_checkout' ),
					'wishlistUrl'      => $this->resolve_wishlist_url(),
					'checkoutUrl'      => function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : '',
					'showCounter'      => (bool) $this->settings->get( 'appearance', 'show_counter', true ),
					'counterShowZero'  => (bool) $this->settings->get( 'appearance', 'counter_show_zero', false ),
				),
			)
		);
	}

	// =========================================================================
	// Button rendering (ffr-2, ffr-3, ffr-8)
	// =========================================================================

	/**
	 * Render the Add to Wishlist button on a single product page.
	 *
	 * Auto-resolves the current product from the WC global if $product_id = 0.
	 * Uses the `button_style` setting from the General section.
	 *
	 * @param int $product_id   WC product ID. 0 = auto-detect from global.
	 * @param int $variation_id Selected variation ID. 0 = none.
	 * @return void
	 */
	public function render_button( int $product_id = 0, int $variation_id = 0 ): void {
		if ( ! $this->settings->get( 'general', 'show_on_single', true ) ) {
			return;
		}
		$style   = (string) $this->settings->get( 'general', 'button_style', 'icon_text' );
		$overlay = 'image_overlay' === $this->settings->get( 'general', 'button_position', 'after_cart' );
		$this->output_button( $product_id, $variation_id, $style, 'single', $overlay );
	}

	/**
	 * Render the single product button only when the product is out of stock.
	 *
	 * Fallback for after_cart/before_cart positions: woocommerce_after/before_add_to_cart_button
	 * do not fire when WooCommerce loads the out-of-stock template instead of the cart form.
	 * This hook fires on woocommerce_single_product_summary (priority 29 or 31) and guards
	 * against in-stock products to prevent double-rendering alongside the primary hook.
	 *
	 * @return void
	 */
	public function render_button_oos_fallback(): void {
		$product_id = (int) get_the_ID();
		if ( $product_id <= 0 ) {
			return;
		}
		$product = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;
		if ( ! $product instanceof \WC_Product || $product->is_in_stock() ) {
			return;
		}
		$this->render_button( $product_id );
	}

	/**
	 * Render the button on shop loop / archive cards.
	 *
	 * Variation ID defaults to 0 on loop (no variation selected yet).
	 * Only fires when the `show_on_loop` setting is enabled.
	 * Uses the `loop_button_style` setting from the General section.
	 *
	 * @return void
	 */
	public function render_loop_button(): void {
		if ( ! $this->settings->get( 'general', 'show_on_loop', true ) ) {
			return;
		}
		$style   = (string) $this->settings->get( 'general', 'loop_button_style', 'icon_only' );
		$overlay = 'image_overlay' === $this->settings->get( 'general', 'loop_button_position', 'after_add_to_cart' );
		$this->output_button( (int) get_the_ID(), 0, $style, 'loop', $overlay );
	}

	/**
	 * Core button rendering logic — shared by render_button() and render_loop_button().
	 *
	 * Resolves the product, enforces type and auth guards, determines wishlist
	 * state, then outputs the button template using the supplied style and context.
	 *
	 * @param int    $product_id   WC product ID. 0 = auto-detect from global.
	 * @param int    $variation_id Selected variation ID.
	 * @param string $style        One of 'icon_text' | 'icon_only' | 'text_only'.
	 * @param string $context      'single' (product page) or 'loop' (shop/archive loop).
	 * @param bool   $overlay      True when the button position is 'image_overlay'.
	 * @return void
	 */
	private function output_button( int $product_id, int $variation_id, string $style, string $context = 'single', bool $overlay = false ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- $overlay is passed to button.php template via include variable scope
		// Resolve product.
		if ( 0 === $product_id ) {
			$product_id = (int) get_the_ID();
		}

		if ( $product_id <= 0 ) {
			return;
		}

		$product = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;
		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		// Product type guard (ffr-8.2).
		$allowed_types = (array) $this->settings->get( 'general', 'product_types' );
		if ( ! empty( $allowed_types ) && ! in_array( $product->get_type(), $allowed_types, true ) ) {
			return;
		}

		// Registered-only mode (ffr-8.3).
		// Guests still see the button (same appearance), but clicking it shows a
		// "Login required!" toast instead of making an AJAX request. The flag is
		// passed to the template as a data-attribute so JS can intercept the click
		// without needing global state. Logged-in users are unaffected.
		$login_required = $this->settings->get( 'general', 'registered_only' ) && ! is_user_logged_in();

		// Button state is always inactive in the server-rendered HTML so the page
		// is safe to store in a full-page cache for all visitors. The real state
		// is fetched client-side by hydrateButtonStates() via cecomwishfw_get_status.
		$in_wishlist              = false;
		$wishlisted_variation_ids = array();

		// Resolve display flags from style.
		$show_icon = in_array( $style, array( 'icon_only', 'icon_text' ), true );
		$show_text = in_array( $style, array( 'text_only', 'icon_text' ), true );

		// Per-context appearance settings.
		$prefix           = 'loop' === $context ? 'loop' : 'single';
		$add_label_raw    = (string) $this->settings->get( 'appearance', $prefix . '_add_label', '' );
		$add_label        = '' !== $add_label_raw ? $add_label_raw : __( 'Add to wishlist', 'cecom-wishlist-for-woocommerce' );
		$remove_label_raw = (string) $this->settings->get( 'appearance', $prefix . '_remove_label', '' );
		$remove_label     = '' !== $remove_label_raw ? $remove_label_raw : __( 'Remove from wishlist', 'cecom-wishlist-for-woocommerce' );
		$icon_class       = (string) $this->settings->get( 'appearance', $prefix . '_icon_class', '' );

		ob_start();
		include CECOMWISHFW_PLUGIN_DIR . 'includes/views/frontend/button.php';
		$html = (string) ob_get_clean();

		// Allow third parties to override the button markup (ffr-2.3).
		$html = (string) apply_filters( 'cecomwishfw_button_html', $html, $product_id, $in_wishlist );

		echo wp_kses_post( $html );
	}

	// =========================================================================
	// Wishlist page (ffr-5, ffr-6)
	// =========================================================================

	/**
	 * Register the [cecomwishfw_wishlist] shortcode.
	 *
	 * Hooked on 'init'.
	 *
	 * @return void
	 */
	public function register_shortcode(): void {
		add_shortcode( 'cecomwishfw_wishlist', array( $this, 'shortcode_callback' ) );
	}

	/**
	 * Register the [cecomwishfw_button] shortcode.
	 *
	 * Lets users place the Add to Wishlist button anywhere on the storefront —
	 * required when General → Button position is set to "Shortcode only", in
	 * which case the loader skips the auto-position hook and the user is
	 * expected to embed the shortcode manually inside a product page or loop.
	 *
	 * Supported attributes:
	 *   id      — int    Product ID. 0 (default) auto-resolves from the loop / queried object.
	 *   context — string 'single' (default) or 'loop'. Controls which button-style /
	 *                    button-position settings the rendered button uses.
	 *
	 * Hooked on 'init'.
	 *
	 * @return void
	 */
	public function register_button_shortcode(): void {
		add_shortcode( 'cecomwishfw_button', array( $this, 'shortcode_button_callback' ) );
	}

	/**
	 * [cecomwishfw_button] shortcode callback.
	 *
	 * Captures the existing render_button() / render_loop_button() output via
	 * an output buffer and returns it as a string (shortcodes must return, not
	 * echo). Auto-resolves the product from the WC loop globals when the `id`
	 * attribute is not supplied — the typical placement is inside a product
	 * page or product loop, where the loop's current product is available.
	 *
	 * @param array<string, string>|string $atts Raw shortcode attributes.
	 * @return string Rendered HTML, or empty string when no product can be resolved.
	 */
	public function shortcode_button_callback( $atts ): string {
		$atts = shortcode_atts(
			array(
				'id'      => '0',
				'context' => 'single',
			),
			$atts,
			'cecomwishfw_button'
		);

		$product_id = (int) $atts['id'];
		if ( $product_id <= 0 ) {
			global $product;
			if ( $product instanceof \WC_Product ) {
				$product_id = (int) $product->get_id();
			} else {
				$product_id = (int) get_the_ID();
			}
		}

		if ( $product_id <= 0 ) {
			return '';
		}

		$context = ( 'loop' === (string) $atts['context'] ) ? 'loop' : 'single';

		ob_start();
		if ( 'loop' === $context ) {
			// render_loop_button() reads get_the_ID() internally; if the caller
			// passed an explicit `id`, set up the loop globals so it picks up
			// the right product.
			if ( (int) get_the_ID() !== $product_id ) {
				$post_obj = get_post( $product_id );
				if ( $post_obj instanceof \WP_Post ) {
					$GLOBALS['post'] = $post_obj; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- temporary loop scoping for shortcode rendering
					setup_postdata( $GLOBALS['post'] );
					$this->render_loop_button();
					wp_reset_postdata();
				}
			} else {
				$this->render_loop_button();
			}
		} else {
			$this->render_button( $product_id );
		}
		return (string) ob_get_clean();
	}

	/**
	 * Register the [cecomwishfw_count] shortcode.
	 *
	 * Renders the wishlist counter badge. Shortcode attributes override the
	 * corresponding Appearance settings on a per-placement basis.
	 *
	 * Supported attributes:
	 *   show_icon  — '1'|'0'  Show an icon next to the badge.
	 *   link       — '1'|'0'  Wrap in a link to the wishlist page.
	 *   icon_class — string   Bootstrap Icons class (e.g. 'bi-star'). Default: 'bi-heart'.
	 *   show_zero  — '1'|'0'  Keep badge visible when count is 0.
	 *
	 * Hooked on 'init'.
	 *
	 * @return void
	 */
	public function register_count_shortcode(): void {
		add_shortcode( 'cecomwishfw_count', array( $this, 'shortcode_count_callback' ) );
	}

	/**
	 * [cecomwishfw_count] shortcode callback.
	 *
	 * @param array<string, string>|string $atts Raw shortcode attributes.
	 * @return string Rendered HTML.
	 */
	public function shortcode_count_callback( $atts ): string {
		$atts = shortcode_atts(
			array(
				'show_icon'  => (bool) $this->settings->get( 'appearance', 'counter_show_icon', true ) ? '1' : '0',
				'link'       => (bool) $this->settings->get( 'appearance', 'counter_link', true ) ? '1' : '0',
				'icon_class' => (string) $this->settings->get( 'appearance', 'counter_icon_class', '' ),
				'show_zero'  => (bool) $this->settings->get( 'appearance', 'counter_show_zero', false ) ? '1' : '0',
			),
			$atts,
			'cecomwishfw_count'
		);

		// Count is always 0 in server-rendered HTML so the page is safe for
		// full-page caching. The real per-user count is fetched client-side by
		// hydrateCounterState() via cecomwishfw_get_count.
		$count        = 0;
		$show_icon    = filter_var( $atts['show_icon'], FILTER_VALIDATE_BOOLEAN );
		$link         = filter_var( $atts['link'], FILTER_VALIDATE_BOOLEAN );
		$icon_class   = sanitize_text_field( (string) $atts['icon_class'] );
		$show_zero    = filter_var( $atts['show_zero'], FILTER_VALIDATE_BOOLEAN );
		$wishlist_url = $this->resolve_wishlist_url();

		ob_start();
		include CECOMWISHFW_PLUGIN_DIR . 'includes/views/frontend/count.php';
		return (string) ob_get_clean();
	}

	/**
	 * Shortcode callback — render the wishlist page.
	 *
	 * When a share token is present in ?cwfw_token=TOKEN the shared list is
	 * loaded in read-only mode ($is_shared_view = true): add-to-cart remains
	 * active but remove buttons are hidden so visitors cannot modify the
	 * owner's wishlist. The shared view renders inside the active theme's
	 * page template (header / footer / container) because the share controller
	 * no longer hijacks template_redirect for valid tokens — see
	 * Cecomwishfw_Share_Controller::handle_shared_view().
	 *
	 * @return string Rendered HTML.
	 */
	public function shortcode_callback(): string {
		$is_shared_view = false;
		$owner_name     = '';

		$raw_token = sanitize_text_field( wp_unslash( filter_input( INPUT_GET, 'cwfw_token', FILTER_DEFAULT ) ?? '' ) );

		if ( '' !== $raw_token ) {
			$shared_list = Cecomwishfw_List_Model::find_by_token( $raw_token );
			if ( $shared_list ) {
				$list           = $shared_list;
				$is_shared_view = true;

				// Resolve owner display name — never expose username or email.
				$owner_name = __( 'Someone', 'cecom-wishlist-for-woocommerce' );
				if ( ! empty( $list->user_id ) ) {
					$owner_data = get_userdata( (int) $list->user_id );
					if ( $owner_data instanceof \WP_User ) {
						$owner_name = $owner_data->display_name;
					}
				}
			} else {
				// Token not found — fall back to the visitor's own list.
				// For guests: read the existing list without creating an empty row.
				$list = is_user_logged_in()
					? $this->session->resolve_list()
					: ( $this->session->get_guest_list() ?? (object) array( 'id' => 0 ) );
			}
		} else {
			// No token — show the visitor's own list.
			// For guests: read the existing list without creating an empty row.
			$list = is_user_logged_in()
				? $this->session->resolve_list()
				: ( $this->session->get_guest_list() ?? (object) array( 'id' => 0 ) );
		}

		$items = ( (int) ( $list->id ?? 0 ) > 0 )
			? $this->item_model->get_for_list( (int) $list->id )
			: array();

		if ( ! $this->settings->get( 'general', 'show_out_of_stock', true ) ) {
			$items = array_filter(
				$items,
				static function ( $item ) {
					return ( $item->product instanceof \WC_Product ) && $item->product->is_in_stock();
				}
			);
		}

		$session  = $this->session;
		$settings = $this->settings;

		ob_start();
		include CECOMWISHFW_PLUGIN_DIR . 'includes/views/frontend/wishlist-page.php';
		return (string) ob_get_clean();
	}

	/**
	 * Register the cecomwishfw/wishlist Gutenberg block.
	 *
	 * Server-side rendered — delegates to the shortcode callback.
	 * Hooked on 'init'.
	 *
	 * @return void
	 */
	public function register_block(): void {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type(
			CECOMWISHFW_PLUGIN_DIR . 'assets/blocks/wishlist/block.json',
			array(
				'render_callback' => function () {
					return do_shortcode( '[cecomwishfw_wishlist]' );
				},
			)
		);
	}

	/**
	 * Register the cecomwishfw/count Gutenberg block.
	 *
	 * Server-side rendered. Block attributes override the corresponding
	 * Appearance settings, mirroring the [cecomwishfw_count] shortcode.
	 * Hooked on 'init'.
	 *
	 * @return void
	 */
	public function register_count_block(): void {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type(
			CECOMWISHFW_PLUGIN_DIR . 'assets/blocks/count/block.json',
			array(
				'render_callback' => function ( array $attrs ): string {
					$show_icon  = isset( $attrs['showIcon'] ) ? ( $attrs['showIcon'] ? '1' : '0' ) : '1';
					$link       = isset( $attrs['link'] ) ? ( $attrs['link'] ? '1' : '0' ) : '1';
					$icon_class = isset( $attrs['iconClass'] ) ? sanitize_text_field( (string) $attrs['iconClass'] ) : '';
					$show_zero  = isset( $attrs['showZero'] ) ? ( $attrs['showZero'] ? '1' : '0' ) : '0';

					return do_shortcode(
						sprintf(
							'[cecomwishfw_count show_icon="%s" link="%s" icon_class="%s" show_zero="%s"]',
							esc_attr( $show_icon ),
							esc_attr( $link ),
							esc_attr( $icon_class ),
							esc_attr( $show_zero )
						)
					);
				},
			)
		);
	}

	// =========================================================================
	// WooCommerce My Account integration
	// =========================================================================

	/** Endpoint slug used for the My Account wishlist tab. */
	public const MYACCOUNT_ENDPOINT = 'cecomwishfw-wishlist';

	/**
	 * Register the WooCommerce My Account rewrite endpoint.
	 *
	 * Must run on 'init' before rewrite rules are compiled. After adding the
	 * endpoint for the first time, a one-time flush is triggered via the
	 * 'cecomwishfw_flush_rewrite_rules' option so the URL becomes active
	 * without requiring a manual visit to Settings > Permalinks.
	 *
	 * Hooked on 'init'.
	 *
	 * @return void
	 */
	public function register_my_account_endpoint(): void {
		add_rewrite_endpoint( self::MYACCOUNT_ENDPOINT, EP_ROOT | EP_PAGES );

		// One-time flush after the endpoint is first introduced (or after any
		// plugin update that changes CECOMWISHFW_VERSION). Runs only once per
		// version — subsequent requests skip the option check entirely.
		if ( get_option( 'cecomwishfw_endpoint_version' ) !== CECOMWISHFW_VERSION ) {
			flush_rewrite_rules( false );
			update_option( 'cecomwishfw_endpoint_version', CECOMWISHFW_VERSION );
		}
	}

	/**
	 * Add "Wishlist" to the WooCommerce My Account navigation.
	 *
	 * Only shown when "Registered users only" is enabled — the tab is the
	 * primary way logged-in users reach their wishlist in that mode.
	 * The endpoint URL (/my-account/cecomwishfw-wishlist/) is always active
	 * regardless of this setting.
	 *
	 * Hooked on 'woocommerce_account_menu_items' filter.
	 *
	 * @param array<string, string> $items Existing My Account menu items.
	 * @return array<string, string>
	 */
	public function add_my_account_tab( array $items ): array {
		if ( ! $this->settings->get( 'general', 'registered_only' ) ) {
			return $items;
		}

		// Insert "Wishlist" before the "Logout" item so it appears at the end
		// of the functional tab list, not after the logout link.
		$logout = array();
		if ( isset( $items['customer-logout'] ) ) {
			$logout = array( 'customer-logout' => $items['customer-logout'] );
			unset( $items['customer-logout'] );
		}

		$items[ self::MYACCOUNT_ENDPOINT ] = __( 'Wishlist', 'cecom-wishlist-for-woocommerce' );

		return array_merge( $items, $logout );
	}

	/**
	 * Render the wishlist inside the WooCommerce My Account tab.
	 *
	 * Delegates to shortcode_callback() so the output is identical to the
	 * standalone wishlist page — same items, same share section, same empty state.
	 *
	 * Hooked on 'woocommerce_account_cecomwishfw-wishlist_endpoint'.
	 *
	 * @return void
	 */
	public function render_my_account_tab(): void {
		echo wp_kses_post( $this->shortcode_callback() );
	}

	/**
	 * Send no-cache headers on the wishlist page only.
	 *
	 * The wishlist page is server-rendered with per-visitor content and must
	 * never be stored in a full-page cache. Every other surface (product pages,
	 * shop archives) is safe to cache because the add-to-wishlist button
	 * hydrates its state via AJAX after page load.
	 *
	 * Note: this constant-based bypass only prevents the CURRENT response from
	 * being stored. It does NOT block a cache plugin from serving a page that
	 * was cached before this handler ran. For full coverage, users running a
	 * caching plugin must add `cecomwishfw_session_` to their cookie-exclusion
	 * list (see readme "Caching plugins" section).
	 *
	 * Hooked on 'template_redirect' at priority 1 — fires before output so
	 * headers are still sendable and cache plugins inspect DONOTCACHEPAGE
	 * before deciding to store a response.
	 *
	 * @return void
	 */
	public function set_nocache_headers(): void {
		if ( ! $this->should_bypass_cache() ) {
			return;
		}
		nocache_headers();
		defined( 'DONOTCACHEPAGE' ) || define( 'DONOTCACHEPAGE', true );   // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- WP cache-compat constant
		defined( 'DONOTCACHEOBJECT' ) || define( 'DONOTCACHEOBJECT', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- WP cache-compat constant
		defined( 'DONOTCACHEDB' ) || define( 'DONOTCACHEDB', true );     // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- WP cache-compat constant
		do_action( 'litespeed_control_set_nocache', 'cecomwishfw' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- third-party LiteSpeed hook
	}

	/**
	 * Decide whether the current request must bypass the full-page cache.
	 *
	 * Only the standalone wishlist page is server-rendered with per-visitor
	 * content. All other pages stay cacheable and rely on WC's session cookie
	 * for cache-plugin-level bypass of wishlist-carrying visitors.
	 *
	 * @return bool
	 */
	private function should_bypass_cache(): bool {
		$page_id = (int) $this->settings->get( 'general', 'wishlist_page_id', 0 );
		return $page_id > 0 && is_page( $page_id );
	}

	/**
	 * Redirect guests away from the standalone wishlist page when registered-only
	 * mode is active.
	 *
	 * The redirect sends guests to the WooCommerce My Account page (the
	 * storefront login form) with the wishlist URL passed as `redirect_to`
	 * so they land on the wishlist page after logging in. Visitors are
	 * NEVER sent to the wp-admin login screen — see resolve_frontend_login_url()
	 * for the WC My Account → wp_login_url() fallback chain.
	 *
	 * Hooked on 'template_redirect' (fires before output, so headers are not sent).
	 *
	 * @return void
	 */
	public function maybe_redirect_guests(): void {
		if ( ! $this->settings->get( 'general', 'registered_only' ) ) {
			return;
		}

		if ( is_user_logged_in() ) {
			return;
		}

		$page_id = (int) $this->settings->get( 'general', 'wishlist_page_id', 0 );
		if ( $page_id > 0 && is_page( $page_id ) ) {
			wp_safe_redirect( self::resolve_frontend_login_url( (string) get_permalink( $page_id ) ) );
			exit;
		}
	}

	// =========================================================================
	// Helpers
	// =========================================================================

	/**
	 * Return the current wishlist item count for the visiting user or guest.
	 *
	 * @return int
	 */
	private function get_current_wishlist_count(): int {
		if ( is_user_logged_in() ) {
			return Cecomwishfw_Item_Model::count_for_user( get_current_user_id() );
		}
		$guest_list = $this->session->get_guest_list();
		return $guest_list ? Cecomwishfw_Item_Model::count_for_list( (int) $guest_list->id ) : 0;
	}

	/**
	 * Resolve a frontend login URL — never the wp-admin login screen.
	 *
	 * Sends visitors to the WooCommerce My Account page (which IS the
	 * storefront login form for guests), with a `redirect_to` query parameter
	 * so any plugin or theme that honors it can return the user to the
	 * supplied URL after authentication. Falls back to wp_login_url() only
	 * when WooCommerce is unavailable or its My Account page has not been
	 * configured / has been trashed — defensive but unlikely on a WC-required
	 * plugin.
	 *
	 * Public + static so both this controller (template_redirect handler) and
	 * the views/frontend/login-prompt.php template can call the same logic
	 * without instantiating the controller.
	 *
	 * @param string $return_to Absolute URL to send the visitor back to after login.
	 *                          Pass an empty string to skip the redirect_to hint.
	 * @return string Absolute login URL (always non-empty).
	 */
	public static function resolve_frontend_login_url( string $return_to = '' ): string {
		if ( function_exists( 'wc_get_page_id' ) ) {
			$myaccount_id = (int) wc_get_page_id( 'myaccount' );
			if ( $myaccount_id > 0 && 'publish' === get_post_status( $myaccount_id ) ) {
				$myaccount_url = get_permalink( $myaccount_id );
				if ( is_string( $myaccount_url ) && '' !== $myaccount_url ) {
					return ( '' !== $return_to )
						? add_query_arg( 'redirect_to', rawurlencode( $return_to ), $myaccount_url )
						: $myaccount_url;
				}
			}
		}
		return wp_login_url( $return_to );
	}

	/**
	 * Resolve a safe URL for the configured wishlist page.
	 *
	 * Never calls get_permalink( 0 ) directly: that would return the permalink
	 * of $GLOBALS['post'] (the current page being rendered) because
	 * get_post( 0 ) falls through to the loop global. Guard on a positive page
	 * ID AND a published status; fall back to home_url( '/wishlist/' )
	 * otherwise so the counter anchor still points somewhere sensible when the
	 * site owner has not chosen a wishlist page (or has trashed it).
	 *
	 * @return string Absolute URL (always non-empty).
	 */
	private function resolve_wishlist_url(): string {
		$page_id = (int) $this->settings->get( 'general', 'wishlist_page_id', 0 );
		if ( $page_id > 0 && 'publish' === get_post_status( $page_id ) ) {
			$permalink = get_permalink( $page_id );
			if ( is_string( $permalink ) && '' !== $permalink ) {
				return $permalink;
			}
		}
		return home_url( '/wishlist/' );
	}
}
