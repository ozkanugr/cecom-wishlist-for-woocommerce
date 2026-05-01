<?php
/**
 * Settings model — typed wrapper for the cecomwishfw_settings option.
 *
 * Provides get/get_all/save methods with schema-backed defaults so callers
 * never need to handle a missing option or raw array access.
 *
 * Sanitization (fmd-6) lives here as sanitize_settings() and is called
 * inside save() before writing to the DB.
 *
 * Option key: 'cecomwishfw_settings'
 * Structure:  [ 'general' => [...], 'appearance' => [...], 'dashboard' => [...] ]
 *
 * @package Cecomwishfw
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Cecomwishfw_Settings
 */
class Cecomwishfw_Settings {

	// =========================================================================
	// Schema & defaults (fmd-5.1)
	// =========================================================================

	/**
	 * Default values for every settings key.
	 *
	 * Used as the fallback when a key has never been saved, or when the stored
	 * option is missing/corrupted. Typed to match their intended PHP types.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private static array $defaults = array(
		'general'    => array(
			'show_on_single'          => true,            // Enable or disable the single product button.
			'button_style'            => 'icon_text',     // Allowed values: icon_only, text_only, icon_text.
			'button_position'         => 'after_cart',    // See BUTTON_POSITIONS constant.
			'loop_button_style'       => 'icon_only',     // Allowed values: icon_only, text_only, icon_text.
			'loop_button_position'    => 'after_add_to_cart', // See LOOP_BUTTON_POSITIONS constant.
			'show_on_loop'            => true,
			'remove_on_cart'          => true,
			'redirect_checkout'       => true,
			'product_types'           => array( 'simple', 'variable', 'grouped', 'external' ),
			'show_out_of_stock'       => true,
			'registered_only'         => false,
			'delete_on_uninstall'     => false,
			'wishlist_page_id'        => 0,               // WP page ID; 0 = not configured.
			// Wishlist detail page — column / element visibility.
			'table_show_variations'   => true,  // Variation attributes (size, color, etc.).
			'table_show_price'        => true,  // Price column.
			'table_show_stock'        => true,  // In-stock / out-of-stock badge.
			'table_show_date'         => true,  // Date added column.
			'table_show_add_to_cart'  => true,  // Add to Cart button.
			'table_show_remove_left'  => true,  // Trash icon on the left of the row.
			'table_show_remove_right' => true,  // Remove button on the right of the row.
			// Sharing.
			'share_enabled'           => true,
			'share_facebook'          => true,
			'share_twitter'           => true,
			'share_pinterest'         => true,
			'share_email'             => true,
			'share_whatsapp'          => true,
			'share_telegram'          => true,
			'share_url'               => true,
			// Social proof.
			'show_popularity_counter' => true,
			// Wishlist table columns.
			'table_show_quantity'     => true,
		),
		'appearance' => array(
			// Single Product Button.
			'single_add_label'                   => '',  // Empty means default translated string at render time.
			'single_remove_label'                => '',
			'single_button_color'                => '',  // Empty means CSS default (#4f46e5 via framework CSS).
			'single_icon_class'                  => '',  // Empty means default Bootstrap heart icon (bi-heart).
			'single_padding'                     => '',  // Empty means CSS default (e.g. 0.5rem 1rem).
			'single_margin'                      => '',  // Empty means CSS default.
			'single_font_size'                   => '',  // Empty means CSS default (e.g. 14px).
			// Single Product Button — additional appearance (empty = default plugin styling).
			'single_appearance_type'             => '',  // Allowed values: textual, theme, custom, or empty.
			'single_custom_bg'                   => '',  // Hex color.
			'single_custom_bg_opacity'           => '',  // Decimal 0..1, or empty for full opacity.
			'single_custom_text'                 => '',  // Hex color.
			'single_custom_text_opacity'         => '',
			'single_custom_border'               => '',  // Hex color.
			'single_custom_border_opacity'       => '',
			'single_custom_bg_hover'             => '',  // Hex color.
			'single_custom_bg_hover_opacity'     => '',
			'single_custom_text_hover'           => '',  // Hex color.
			'single_custom_text_hover_opacity'   => '',
			'single_custom_border_hover'         => '',  // Hex color.
			'single_custom_border_hover_opacity' => '',
			'single_custom_radius'               => '',  // CSS length.
			'single_custom_border_width'         => '',  // CSS length.
			// Shop Loop Button.
			'loop_add_label'                     => '',
			'loop_remove_label'                  => '',
			'loop_button_color'                  => '',
			'loop_icon_class'                    => '',  // Empty means default Bootstrap heart icon (bi-heart).
			'loop_padding'                       => '',
			'loop_margin'                        => '',
			'loop_font_size'                     => '',
			// Shop Loop Button — additional appearance (empty = default plugin styling).
			'loop_appearance_type'               => '',
			'loop_custom_bg'                     => '',
			'loop_custom_bg_opacity'             => '',
			'loop_custom_text'                   => '',
			'loop_custom_text_opacity'           => '',
			'loop_custom_border'                 => '',
			'loop_custom_border_opacity'         => '',
			'loop_custom_bg_hover'               => '',
			'loop_custom_bg_hover_opacity'       => '',
			'loop_custom_text_hover'             => '',
			'loop_custom_text_hover_opacity'     => '',
			'loop_custom_border_hover'           => '',
			'loop_custom_border_hover_opacity'   => '',
			'loop_custom_radius'                 => '',
			'loop_custom_border_width'           => '',
			// Counter badge.
			'show_counter'                       => true,   // Enable JS updates of .cecomwishfw-count elements.
			'counter_show_icon'                  => true,   // Show icon next to badge in [cecomwishfw_count].
			'counter_link'                       => true,   // Wrap badge in a link to the wishlist page.
			'counter_icon_class'                 => '',     // Empty means bi-heart.
			'counter_show_zero'                  => true,   // Show badge when count is 0.
			// Plugin-scoped CSS appended after the computed per-context rules.
			'custom_css'                         => '',
		),
		'dashboard'  => array(),
	);

	// Allowlists for select fields.
	private const BUTTON_STYLES         = array( 'icon_only', 'text_only', 'icon_text' );
	private const BUTTON_POSITIONS      = array( 'after_cart', 'before_cart', 'after_summary', 'after_price', 'image_overlay', 'shortcode_only' );
	private const LOOP_BUTTON_POSITIONS = array( 'after_add_to_cart', 'before_add_to_cart', 'after_title', 'image_overlay', 'shortcode_only' );
	private const PRODUCT_TYPES         = array( 'simple', 'variable', 'grouped', 'external' );
	private const APPEARANCE_TYPES      = array( 'textual', 'theme', 'custom' );

	// =========================================================================
	// Getters (fmd-5.2, fmd-5.3)
	// =========================================================================

	/**
	 * Get a single setting value.
	 *
	 * Resolution order: stored → $fallback → schema default → null.
	 *
	 * @param string     $section  Settings section: 'general' | 'appearance' | 'dashboard'.
	 * @param string     $key      Setting key within the section.
	 * @param mixed|null $fallback Optional caller-supplied fallback.
	 * @return mixed
	 */
	public static function get( string $section, string $key, mixed $fallback = null ): mixed {
		$stored = get_option( 'cecomwishfw_settings', array() );

		if ( isset( $stored[ $section ][ $key ] ) ) {
			return $stored[ $section ][ $key ];
		}

		if ( null !== $fallback ) {
			return $fallback;
		}

		return self::$defaults[ $section ][ $key ] ?? null;
	}

	/**
	 * Get all settings for a section, with defaults applied for any missing keys.
	 *
	 * Stored values win over defaults; keys not yet saved are filled from the schema.
	 *
	 * @param string $section Settings section key ('general', 'appearance', or 'dashboard').
	 * @return array<string, mixed>
	 */
	public static function get_all( string $section ): array {
		$stored          = get_option( 'cecomwishfw_settings', array() );
		$section_stored  = is_array( $stored[ $section ] ?? null ) ? $stored[ $section ] : array();
		$section_default = self::$defaults[ $section ] ?? array();

		return array_merge( $section_default, $section_stored );
	}

	/**
	 * Get the full defaults schema.
	 *
	 * Returns either the complete three-section defaults array, or a single
	 * section when `$section` is provided. Used by the reset-to-defaults flow.
	 *
	 * @param string|null $section Optional section key ('general' | 'appearance' | 'dashboard').
	 * @return array<string, mixed>
	 */
	public static function get_defaults( ?string $section = null ): array {
		if ( null === $section ) {
			return self::$defaults;
		}
		return self::$defaults[ $section ] ?? array();
	}

	/**
	 * Reset every plugin setting back to its schema default.
	 *
	 * Overwrites the `cecomwishfw_settings` option with the full defaults array.
	 * Returns true when the option was updated or already matches defaults.
	 *
	 * @return bool
	 */
	public static function reset_all(): bool {
		return (bool) update_option( 'cecomwishfw_settings', self::$defaults );
	}

	// =========================================================================
	// Save (fmd-5.4)
	// =========================================================================

	/**
	 * Sanitize and save a settings section.
	 *
	 * Reads the current option, merges only the provided section with the
	 * sanitized input (all other sections are preserved), then persists.
	 *
	 * @param string               $section Settings section key.
	 * @param array<string, mixed> $data    Raw input (e.g. from $_POST).
	 * @return bool True on successful update, false if unchanged or on failure.
	 */
	public static function save( string $section, array $data ): bool {
		$clean    = self::sanitize_settings( $section, $data );
		$existing = get_option( 'cecomwishfw_settings', array() );

		// Merge only the target section — all other sections are preserved.
		$existing[ $section ] = array_merge(
			is_array( $existing[ $section ] ?? null ) ? $existing[ $section ] : array(),
			$clean
		);

		return update_option( 'cecomwishfw_settings', $existing );
	}

	// =========================================================================
	// Sanitization (fmd-6.1)
	// =========================================================================

	/**
	 * Sanitize a raw input array for a given settings section.
	 *
	 * Only keys present in $raw are processed — unsubmitted keys are not
	 * touched, preserving their current stored values.
	 *
	 * @param string               $section Settings section key.
	 * @param array<string, mixed> $raw     Raw, unsanitized input.
	 * @return array<string, mixed> Sanitized values keyed the same as $raw.
	 */
	public static function sanitize_settings( string $section, array $raw ): array {
		return match ( $section ) {
			'general'    => self::sanitize_general( $raw ),
			'appearance' => self::sanitize_appearance( $raw ),
			'dashboard'  => array(), // Dashboard has no user-editable inputs.
			default      => array(),
		};
	}

	// ── General section ──────────────────────────────────────────────────────

	/**
	 * Sanitize raw input for the general settings section.
	 *
	 * @param array<string, mixed> $raw Raw, unsanitized input.
	 * @return array<string, mixed>
	 */
	private static function sanitize_general( array $raw ): array {
		$clean = array();

		// Select: button_style (single product page).
		if ( array_key_exists( 'button_style', $raw ) ) {
			$v                     = (string) ( $raw['button_style'] ?? '' );
			$clean['button_style'] = in_array( $v, self::BUTTON_STYLES, true )
				? $v
				: self::$defaults['general']['button_style'];
		}

		// Select: loop_button_style (shop loop / archive pages).
		if ( array_key_exists( 'loop_button_style', $raw ) ) {
			$v                          = (string) ( $raw['loop_button_style'] ?? '' );
			$clean['loop_button_style'] = in_array( $v, self::BUTTON_STYLES, true )
				? $v
				: self::$defaults['general']['loop_button_style'];
		}

		// Select: button_position (single product page).
		if ( array_key_exists( 'button_position', $raw ) ) {
			$v                        = (string) ( $raw['button_position'] ?? '' );
			$clean['button_position'] = in_array( $v, self::BUTTON_POSITIONS, true )
				? $v
				: self::$defaults['general']['button_position'];
		}

		// Select: loop_button_position (shop loop / archive pages).
		if ( array_key_exists( 'loop_button_position', $raw ) ) {
			$v                             = (string) ( $raw['loop_button_position'] ?? '' );
			$clean['loop_button_position'] = in_array( $v, self::LOOP_BUTTON_POSITIONS, true )
				? $v
				: self::$defaults['general']['loop_button_position'];
		}

		// Array: product_types (allowlist intersection)
		// Always save — absent key means all checkboxes unchecked → empty array.
		$v                      = isset( $raw['product_types'] ) && is_array( $raw['product_types'] ) ? $raw['product_types'] : array();
		$clean['product_types'] = array_values( array_intersect( $v, self::PRODUCT_TYPES ) );

		// Boolean fields — absent key means unchecked checkbox = false.
		foreach (
			array(
				'show_on_single',
				'remove_on_cart',
				'redirect_checkout',
				'show_on_loop',
				'show_out_of_stock',
				'registered_only',
				'delete_on_uninstall',
				'table_show_variations',
				'table_show_price',
				'table_show_stock',
				'table_show_date',
				'table_show_add_to_cart',
				'table_show_remove_left',
				'table_show_remove_right',
				'share_enabled',
				'share_facebook',
				'share_twitter',
				'share_pinterest',
				'share_email',
				'share_whatsapp',
				'share_telegram',
				'share_url',
				'show_popularity_counter',
				'table_show_quantity',
			) as $bool_key
		) {
			$clean[ $bool_key ] = isset( $raw[ $bool_key ] ) && (bool) $raw[ $bool_key ];
		}

		// Integer: wishlist page ID.
		if ( array_key_exists( 'wishlist_page_id', $raw ) ) {
			$clean['wishlist_page_id'] = absint( $raw['wishlist_page_id'] );
		}

		return $clean;
	}

	// ── Appearance section ───────────────────────────────────────────────────

	/**
	 * Sanitize raw input for the appearance settings section.
	 *
	 * @param array<string, mixed> $raw Raw, unsanitized input.
	 * @return array<string, mixed>
	 */
	private static function sanitize_appearance( array $raw ): array {
		$clean = array();

		// Text labels (per-button) — strip tags, unslash.
		foreach (
			array(
				'single_add_label',
				'single_remove_label',
				'loop_add_label',
				'loop_remove_label',
			) as $label_key
		) {
			if ( array_key_exists( $label_key, $raw ) ) {
				$clean[ $label_key ] = sanitize_text_field( wp_unslash( (string) $raw[ $label_key ] ) );
			}
		}

		// Colors (per-button) — must be a valid #rrggbb or #rgb hex string; empty otherwise.
		// Includes the legacy *_button_color fields plus the custom-style color palette.
		foreach (
			array(
				'single_button_color',
				'loop_button_color',
				'single_custom_bg',
				'single_custom_text',
				'single_custom_border',
				'single_custom_bg_hover',
				'single_custom_text_hover',
				'single_custom_border_hover',
				'loop_custom_bg',
				'loop_custom_text',
				'loop_custom_border',
				'loop_custom_bg_hover',
				'loop_custom_text_hover',
				'loop_custom_border_hover',
			) as $color_key
		) {
			if ( array_key_exists( $color_key, $raw ) ) {
				$v                   = sanitize_text_field( wp_unslash( (string) $raw[ $color_key ] ) );
				$clean[ $color_key ] = ( '' === $v || preg_match( '/^#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $v ) )
					? $v
					: '';
			}
		}

		// Icon class names (per-button) — must be a Bootstrap Icons class like 'bi-heart-fill'.
		// Accepts empty string (= use default heart) or a value matching 'bi-' followed by
		// lowercase letters, digits, and hyphens only. Any other value is rejected to empty.
		foreach ( array( 'single_icon_class', 'loop_icon_class' ) as $icon_key ) {
			if ( array_key_exists( $icon_key, $raw ) ) {
				$v                  = sanitize_text_field( wp_unslash( (string) $raw[ $icon_key ] ) );
				$clean[ $icon_key ] = ( '' === $v || preg_match( '/^bi-[a-z0-9][a-z0-9-]*$/', $v ) )
					? $v
					: '';
			}
		}

		// CSS dimension values (padding, margin, font-size, per-button) + custom border radius / width.
		// Accepted pattern: one to four space-separated values of digits+unit (px|rem|em|%|vw|vh).
		foreach (
			array(
				'single_padding',
				'single_margin',
				'single_font_size',
				'loop_padding',
				'loop_margin',
				'loop_font_size',
				'single_custom_radius',
				'single_custom_border_width',
				'loop_custom_radius',
				'loop_custom_border_width',
			) as $css_key
		) {
			if ( array_key_exists( $css_key, $raw ) ) {
				$v                 = sanitize_text_field( wp_unslash( (string) $raw[ $css_key ] ) );
				$clean[ $css_key ] = ( '' === $v || preg_match( '/^[\d.]+(px|rem|em|%|vw|vh)(\s+[\d.]+(px|rem|em|%|vw|vh)){0,3}$/', $v ) )
					? $v
					: '';
			}
		}

		// Additional appearance type (per-button) — enum allowlist; empty = default plugin styling.
		foreach ( array( 'single_appearance_type', 'loop_appearance_type' ) as $type_key ) {
			if ( array_key_exists( $type_key, $raw ) ) {
				$v                  = sanitize_text_field( wp_unslash( (string) $raw[ $type_key ] ) );
				$clean[ $type_key ] = ( '' === $v || in_array( $v, self::APPEARANCE_TYPES, true ) ) ? $v : '';
			}
		}

		// Per-color opacity (one alpha channel per custom color field) — float
		// clamped 0..1, stored as a compact string. Empty stays empty so the
		// frontend CSS emitter falls back to the raw hex value.
		$opacity_keys = array(
			'single_custom_bg_opacity',
			'single_custom_text_opacity',
			'single_custom_border_opacity',
			'single_custom_bg_hover_opacity',
			'single_custom_text_hover_opacity',
			'single_custom_border_hover_opacity',
			'loop_custom_bg_opacity',
			'loop_custom_text_opacity',
			'loop_custom_border_opacity',
			'loop_custom_bg_hover_opacity',
			'loop_custom_text_hover_opacity',
			'loop_custom_border_hover_opacity',
		);
		foreach ( $opacity_keys as $opacity_key ) {
			if ( array_key_exists( $opacity_key, $raw ) ) {
				$v = sanitize_text_field( wp_unslash( (string) $raw[ $opacity_key ] ) );
				if ( '' === $v ) {
					$clean[ $opacity_key ] = '';
				} elseif ( is_numeric( $v ) ) {
					$f = (float) $v;
					$f = max( 0.0, min( 1.0, $f ) );
					// Trim trailing zeros for a compact storage representation.
					$clean[ $opacity_key ] = rtrim( rtrim( sprintf( '%.2f', $f ), '0' ), '.' );
					if ( '' === $clean[ $opacity_key ] ) {
						$clean[ $opacity_key ] = '0';
					}
				} else {
					$clean[ $opacity_key ] = '';
				}
			}
		}

		// Icon class for counter badge — same validation as per-button icon classes.
		if ( array_key_exists( 'counter_icon_class', $raw ) ) {
			$v                           = sanitize_text_field( wp_unslash( (string) $raw['counter_icon_class'] ) );
			$clean['counter_icon_class'] = ( '' === $v || preg_match( '/^bi-[a-z0-9][a-z0-9-]*$/', $v ) )
				? $v : '';
		}

		// Boolean: counter settings (shared, not per-button) — absent key = unchecked = false.
		foreach (
			array( 'show_counter', 'counter_show_icon', 'counter_link', 'counter_show_zero' ) as $bool_key
		) {
			$clean[ $bool_key ] = isset( $raw[ $bool_key ] ) && (bool) $raw[ $bool_key ];
		}

		// Plugin-scoped custom CSS — sanitized with the dedicated helper.
		if ( array_key_exists( 'custom_css', $raw ) ) {
			$clean['custom_css'] = self::_sanitize_css( (string) wp_unslash( $raw['custom_css'] ) );
		}

		return $clean;
	}

	/**
	 * Sanitize a freeform CSS string for safe inline embedding.
	 *
	 * Strips HTML tags and removes CSS-specific injection vectors: IE
	 * expression(), javascript: pseudo-protocol, @import rules, url() with
	 * data: or javascript: schemes, and the legacy IE behavior and
	 * -moz-binding properties. Valid style declarations are preserved.
	 *
	 * @param string $css Raw CSS input.
	 * @return string Sanitized CSS ready for wp_add_inline_style().
	 */
	private static function _sanitize_css( string $css ): string {
		$css = wp_strip_all_tags( $css );
		$css = preg_replace( '/\bexpression\s*\(/i', '', $css );
		$css = preg_replace( '/\bjavascript\s*:/i', '', $css );
		$css = preg_replace( '/@import\b/i', '', $css );
		$css = preg_replace( '/url\s*\(\s*["\']?\s*(data|javascript)\s*:/i', 'url(#', $css );
		$css = preg_replace( '/\bbehavior\s*:/i', '', $css );
		$css = preg_replace( '/-moz-binding\s*:/i', '', $css );
		return trim( $css );
	}
}
