<?php
/**
 * Appearance settings tab.
 *
 * Renders the Appearance section fields. Included from settings.php inside a
 * <form> element — no form tag or submit button here.
 *
 * Settings are split per-button context:
 *   - Single Product Button: labels, color, icon, padding, font-size
 *   - Shop Loop Button:      labels, color, icon, padding, font-size
 *   - Shared: show_counter
 *
 * Uses the cecom-plugin-admin-ui-framework flat panel pattern:
 *   <div class="col-12 mb-3 shadow-sm rounded-4 p-3 p-sm-4 border border-light-subtle bg-white">
 *
 * @package Cecomwishfw
 */

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template file; variables are passed from the controller, not global

if ( ! function_exists( 'cecomwishfw_label_tooltip' ) ) {
	/**
	 * Print a Bootstrap-tooltip-capable question-mark icon next to a form
	 * label. Used to convert inline form-text descriptions into hoverable
	 * tooltips on the label itself, matching the framework's tooltip pattern
	 * (cecom-plugin-admin-ui-framework.html → Tooltips section).
	 *
	 * The icon is a `<button>` (not a bare `<i>`) so it's keyboard-focusable
	 * and works with screen readers via aria-label. Both `hover` and `focus`
	 * triggers are wired so users navigating with the keyboard see the same
	 * description as mouse users.
	 *
	 * @param string $text Help text to display in the tooltip. Empty = no-op.
	 * @return void
	 */
	function cecomwishfw_label_tooltip( string $text ): void {
		if ( '' === $text ) {
			return;
		}
		printf(
			'<button type="button" class="btn btn-link btn-sm p-0 ms-1 align-baseline cecomwishfw-tooltip-icon text-body-tertiary" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-trigger="hover focus" tabindex="0" title="%1$s" aria-label="%1$s"><i class="bi bi-question-circle" aria-hidden="true"></i></button>',
			esc_attr( $text )
		);
	}
}

if ( ! function_exists( 'cecomwishfw_render_alpha_picker' ) ) {
	/**
	 * Render a color picker that includes an opacity control.
	 *
	 * The markup is a Bootstrap dropdown whose toggle is a color swatch. Opening
	 * the dropdown reveals a native <input type="color"> plus a range slider for
	 * alpha, so a single picker control adjusts both color and opacity. The
	 * actual form-submitted values live in two hidden inputs, matching the
	 * existing split-storage schema (hex in settings[$id], float in
	 * settings[{$id}_opacity]).
	 *
	 * @param string $id         Color field ID — also the base for $alpha_id if null.
	 * @param string $color_val  Stored hex color (empty string = use default visually).
	 * @param string $alpha_val  Stored alpha 0..1 as string (empty = full opacity).
	 * @param string $default_color Fallback hex shown when $color_val is empty.
	 * @param string $title         Accessibility / tooltip text for the swatch button.
	 */
	function cecomwishfw_render_alpha_picker( string $id, string $color_val, string $alpha_val, string $default_color, string $title ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundBeforeLastUsed -- $default_color is used below
		$alpha_id      = $id . '_opacity';
		$display_color = '' !== $color_val ? $color_val : $default_color;
		$display_alpha = '' !== $alpha_val ? $alpha_val : '1';
		?>
		<div class="cecomwishfw-alpha-picker d-inline-flex align-items-center gap-2">
			<div class="dropdown">
				<button type="button"
						class="btn btn-sm p-0 border rounded cecomwishfw-alpha-swatch"
						data-bs-toggle="dropdown"
						data-bs-auto-close="outside"
						aria-expanded="false"
						aria-haspopup="true"
						aria-label="<?php echo esc_attr( $title ); ?>">
					<span class="cecomwishfw-alpha-swatch-fill"
							style="background-color: <?php echo esc_attr( $display_color ); ?>; opacity: <?php echo esc_attr( $display_alpha ); ?>;"></span>
				</button>
				<div class="dropdown-menu p-3 cecomwishfw-alpha-menu">
					<label class="form-label small fw-medium mb-1">
						<?php esc_html_e( 'Color', 'cecom-wishlist-for-woocommerce' ); ?>
					</label>
					<input type="color"
							class="form-control form-control-color form-control-sm cecomwishfw-alpha-color-input"
							value="<?php echo esc_attr( $display_color ); ?>"
							aria-label="<?php esc_attr_e( 'Choose color', 'cecom-wishlist-for-woocommerce' ); ?>">
					<label class="form-label small fw-medium mb-1 mt-3">
						<?php esc_html_e( 'Opacity', 'cecom-wishlist-for-woocommerce' ); ?>
					</label>
					<div class="d-flex align-items-center gap-2">
						<input type="range"
								class="form-range cecomwishfw-alpha-range-input"
								min="0" max="1" step="0.05"
								value="<?php echo esc_attr( $display_alpha ); ?>"
								aria-label="<?php esc_attr_e( 'Opacity (0–1)', 'cecom-wishlist-for-woocommerce' ); ?>">
						<span class="small text-body-secondary cecomwishfw-alpha-display" style="min-width:2.5rem; text-align:right;">
							<?php echo esc_html( $display_alpha ); ?>
						</span>
					</div>
				</div>
			</div>
			<input type="hidden"
					name="settings[<?php echo esc_attr( $id ); ?>]"
					id="<?php echo esc_attr( $id ); ?>"
					value="<?php echo esc_attr( $color_val ); ?>"
					class="cecomwishfw-alpha-color-store">
			<input type="hidden"
					name="settings[<?php echo esc_attr( $alpha_id ); ?>]"
					id="<?php echo esc_attr( $alpha_id ); ?>"
					value="<?php echo esc_attr( $alpha_val ); ?>"
					class="cecomwishfw-alpha-opacity-store">
			<button type="button"
					class="btn btn-sm btn-outline-secondary cecomwishfw-reset-color d-inline-flex align-items-center"
					data-target="<?php echo esc_attr( $id ); ?>"
					data-default="<?php echo esc_attr( $default_color ); ?>"
					aria-label="<?php esc_attr_e( 'Reset to default color', 'cecom-wishlist-for-woocommerce' ); ?>">
				<i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
			</button>
		</div>
		<?php
	}
}

if ( ! function_exists( 'cecomwishfw_render_padding_group' ) ) {
	/**
	 * Render any 4-side CSS shorthand field (padding, margin, …) as four
	 * side-by-side range sliders (top, right, bottom, left).
	 *
	 * Despite the historical "padding" in its name, this helper is generic —
	 * it takes an `$id` parameter and works for any property that uses the
	 * top/right/bottom/left CSS shorthand.
	 *
	 * The same Bootstrap `form-range` slider design used by the single-value
	 * dimension helper is preserved; the four sliders sit inline in a flex row
	 * and write a combined CSS shorthand value ("{T}px {R}px {B}px {L}px") to
	 * a single hidden input on every change. When all four sliders are at 0
	 * the hidden input collapses to an empty string, preserving the existing
	 * "leave blank to use the theme default" semantic and keeping the model's
	 * sanitize regex happy.
	 *
	 * Existing stored values that use CSS shorthand (1, 2, 3, or 4 components)
	 * are expanded on initial render so each slider lands on the correct side
	 * even on first paint. Non-px units in legacy stored values lose their
	 * decimal precision (sliders are integer-px only) but the value is only
	 * rewritten when the user actually touches a slider, so untouched fields
	 * stay verbatim until the next save.
	 *
	 * @param string $id     Form field ID (the base for settings[$id]).
	 * @param string $stored Stored padding shorthand string (e.g. "12px 8px").
	 * @param int    $min    Slider minimum (per side).
	 * @param int    $max    Slider maximum (per side).
	 * @param int    $step   Slider step.
	 * @param string $help   Form-text helper rendered below the row.
	 */
	function cecomwishfw_render_padding_group( string $id, string $stored, int $min, int $max, int $step, string $help ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- $help is used in the template output below
		// Expand the stored CSS shorthand into 4 integers, mirroring the CSS
		// shorthand rules: 1 value → all four sides, 2 → vertical/horizontal,
		// 3 → top/horizontal/bottom, 4 → top/right/bottom/left.
		$nums = array();
		if ( '' !== $stored ) {
			$split = preg_split( '/\s+/', trim( $stored ) );
			$parts = false !== $split ? $split : array();
			foreach ( $parts as $part ) {
				if ( preg_match( '/^(\d+)/', $part, $m ) ) {
					$nums[] = (int) $m[1];
				}
			}
		}
		switch ( count( $nums ) ) {
			case 0:
				$values = array( $min, $min, $min, $min );
				break;
			case 1:
				$values = array( $nums[0], $nums[0], $nums[0], $nums[0] );
				break;
			case 2:
				$values = array( $nums[0], $nums[1], $nums[0], $nums[1] );
				break;
			case 3:
				$values = array( $nums[0], $nums[1], $nums[2], $nums[1] );
				break;
			default:
				$values = array( $nums[0], $nums[1], $nums[2], $nums[3] );
				break;
		}
		// Clamp every value to the slider's range.
		foreach ( $values as $i => $v ) {
			if ( $v < $min ) {
				$values[ $i ] = $min;
			} elseif ( $v > $max ) {
				$values[ $i ] = $max;
			}
		}

		$sides     = array(
			'top'    => __( 'Top', 'cecom-wishlist-for-woocommerce' ),
			'right'  => __( 'Right', 'cecom-wishlist-for-woocommerce' ),
			'bottom' => __( 'Bottom', 'cecom-wishlist-for-woocommerce' ),
			'left'   => __( 'Left', 'cecom-wishlist-for-woocommerce' ),
		);
		$side_keys = array_keys( $sides );
		?>
		<div class="cecomwishfw-padding-group" data-target="<?php echo esc_attr( $id ); ?>">
			<div class="d-flex flex-wrap align-items-end gap-3 cecomwishfw-padding-row" style="max-width:560px;">
				<?php
				foreach ( $side_keys as $i => $side ) :
					$num     = (int) $values[ $i ];
					$display = ( 0 === $num ) ? __( 'Default', 'cecom-wishlist-for-woocommerce' ) : $num . 'px';
					?>
					<div class="cecomwishfw-padding-cell" style="flex:1 1 100px; min-width:100px;">
						<label class="form-label small fw-medium d-block mb-1">
							<?php echo esc_html( $sides[ $side ] ); ?>
						</label>
						<input type="range"
								class="form-range cecomwishfw-padding-slider"
								min="<?php echo esc_attr( (string) $min ); ?>"
								max="<?php echo esc_attr( (string) $max ); ?>"
								step="<?php echo esc_attr( (string) $step ); ?>"
								value="<?php echo esc_attr( (string) $num ); ?>"
								data-side="<?php echo esc_attr( $side ); ?>"
								aria-label="
								<?php
									/* translators: %s: side name (Top, Right, Bottom, Left) */
									echo esc_attr( sprintf( __( 'Padding — %s', 'cecom-wishlist-for-woocommerce' ), $sides[ $side ] ) );
								?>
									">
						<span class="small text-body-secondary cecomwishfw-padding-display d-block text-end">
							<?php echo esc_html( $display ); ?>
						</span>
					</div>
				<?php endforeach; ?>
			</div>
			<input type="hidden"
					name="settings[<?php echo esc_attr( $id ); ?>]"
					id="<?php echo esc_attr( $id ); ?>"
					value="<?php echo esc_attr( $stored ); ?>">
		</div>
		<?php
		// $help is intentionally NOT rendered as form-text here — the parent
		// row's <label> renders it as a Bootstrap tooltip via
		// cecomwishfw_label_tooltip(). The variable is still consumed above
		// (slider aria-label) for screen-reader accessibility.
	}
}

if ( ! function_exists( 'cecomwishfw_render_dim_range' ) ) {
	/**
	 * Render a CSS-dimension field as a range slider + live "Npx" display.
	 *
	 * The markup is a range slider paired with a hidden input that carries the
	 * actual stored value in "{N}px" format. Slider value 0 collapses to an
	 * empty stored value — preserving the existing "leave blank to use the
	 * theme default" semantics. The visible numeric display mirrors the slider
	 * position and switches to "Default" at 0 so users understand the
	 * fall-through behavior.
	 *
	 * @param string $id      Form field ID (the base for settings[$id]).
	 * @param string $stored  Stored string (e.g. "14px"). Leading integer is parsed for the initial slider position.
	 * @param int    $min     Slider minimum.
	 * @param int    $max     Slider maximum.
	 * @param int    $step    Slider step.
	 * @param string $help    Form-text helper below the slider.
	 */
	function cecomwishfw_render_dim_range( string $id, string $stored, int $min, int $max, int $step, string $help ): void {
		// Parse the leading integer from the stored value for the initial
		// slider position. Accepts "14px", "1rem" (→ 1, rough fallback),
		// or "" (→ min). The stored value is re-submitted verbatim if the
		// user never touches the slider, so non-px units are preserved until
		// the user commits a new value.
		$num = ( '' !== $stored && preg_match( '/^(\d+)/', $stored, $m ) ) ? (int) $m[1] : $min;
		if ( $num < $min ) {
			$num = $min;
		}
		if ( $num > $max ) {
			$num = $max;
		}
		$display = ( 0 === $num ) ? __( 'Default', 'cecom-wishlist-for-woocommerce' ) : $num . 'px';
		?>
		<div class="d-flex align-items-center gap-3 cecomwishfw-dim-range-wrap" style="max-width:420px;">
			<input type="range"
					class="form-range cecomwishfw-dim-range"
					min="<?php echo esc_attr( (string) $min ); ?>"
					max="<?php echo esc_attr( (string) $max ); ?>"
					step="<?php echo esc_attr( (string) $step ); ?>"
					value="<?php echo esc_attr( (string) $num ); ?>"
					data-target="<?php echo esc_attr( $id ); ?>"
					aria-label="<?php echo esc_attr( $help ); ?>">
			<span class="small text-body-secondary cecomwishfw-dim-display" style="min-width:4rem; text-align:right;">
				<?php echo esc_html( $display ); ?>
			</span>
			<input type="hidden"
					name="settings[<?php echo esc_attr( $id ); ?>]"
					id="<?php echo esc_attr( $id ); ?>"
					value="<?php echo esc_attr( $stored ); ?>">
		</div>
		<?php
		// $help is intentionally NOT rendered as form-text here — the parent
		// row's <label> renders it as a Bootstrap tooltip via
		// cecomwishfw_label_tooltip(). The variable is still consumed above
		// (slider aria-label) for screen-reader accessibility.
	}
}

$a = Cecomwishfw_Settings::get_all( 'appearance' );

// Default color for the color picker when no custom color is saved.
$single_color_value = '' !== (string) $a['single_button_color'] ? (string) $a['single_button_color'] : '#4f46e5';
$loop_color_value   = '' !== (string) $a['loop_button_color'] ? (string) $a['loop_button_color'] : '#4f46e5';

// Additional-appearance type for each context (empty = default plugin styling).
$single_appearance_type = (string) $a['single_appearance_type'];
$loop_appearance_type   = (string) $a['loop_appearance_type'];

?>

<?php /* ── Single Product Button panel ──────────────────────────────── */ ?>
<div class="col-12 mb-3 shadow-sm rounded-4 p-3 p-sm-4 border border-light-subtle bg-white">
	<h2 class="h6 fw-semibold text-body-emphasis mb-3">
		<?php esc_html_e( 'Single Product Button', 'cecom-wishlist-for-woocommerce' ); ?>
	</h2>

	<?php /* ── Labels ──────────────────────────────────────────────── */ ?>
	<div class="row mb-4">
		<label for="single_add_label" class="col-sm-3 col-form-label fw-medium pt-0">
			<?php esc_html_e( '"Add to Wishlist" label', 'cecom-wishlist-for-woocommerce' ); ?>
			<?php cecomwishfw_label_tooltip( __( 'Leave blank to use the default translated string.', 'cecom-wishlist-for-woocommerce' ) ); ?>
		</label>
		<div class="col-sm-9">
			<input type="text"
					class="form-control form-control-sm"
					style="max-width:320px;"
					name="settings[single_add_label]"
					id="single_add_label"
					value="<?php echo esc_attr( $a['single_add_label'] ); ?>"
					placeholder="<?php esc_attr_e( 'Add to wishlist', 'cecom-wishlist-for-woocommerce' ); ?>">
		</div>
	</div>

	<div class="row mb-4">
		<label for="single_remove_label" class="col-sm-3 col-form-label fw-medium pt-0">
			<?php esc_html_e( '"Remove" label', 'cecom-wishlist-for-woocommerce' ); ?>
			<?php cecomwishfw_label_tooltip( __( 'Leave blank to use the default translated string.', 'cecom-wishlist-for-woocommerce' ) ); ?>
		</label>
		<div class="col-sm-9">
			<input type="text"
					class="form-control form-control-sm"
					style="max-width:320px;"
					name="settings[single_remove_label]"
					id="single_remove_label"
					value="<?php echo esc_attr( $a['single_remove_label'] ); ?>"
					placeholder="<?php esc_attr_e( 'Remove from wishlist', 'cecom-wishlist-for-woocommerce' ); ?>">
		</div>
	</div>

	<?php /* ── Color ────────────────────────────────────────────────── */ ?>
	<div class="row mb-4">
		<label for="single_button_color" class="col-sm-3 col-form-label fw-medium pt-0">
			<?php esc_html_e( 'Button color', 'cecom-wishlist-for-woocommerce' ); ?>
			<?php cecomwishfw_label_tooltip( __( 'Applied as the primary color on single product pages.', 'cecom-wishlist-for-woocommerce' ) ); ?>
		</label>
		<div class="col-sm-9">
			<div class="d-flex align-items-center gap-2">
				<input type="color"
						class="form-control form-control-color form-control-sm"
						name="settings[single_button_color]"
						id="single_button_color"
						value="<?php echo esc_attr( $single_color_value ); ?>"
						aria-label="<?php esc_attr_e( 'Choose button color', 'cecom-wishlist-for-woocommerce' ); ?>">
				<button type="button"
						class="btn btn-sm btn-outline-secondary cecomwishfw-reset-color d-inline-flex align-items-center"
						data-target="single_button_color"
						data-default="#4f46e5"
						aria-label="<?php esc_attr_e( 'Reset to default color', 'cecom-wishlist-for-woocommerce' ); ?>">
					<i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
				</button>
			</div>
		</div>
	</div>

	<?php /* ── Custom icon ──────────────────────────────────────────── */ ?>
	<div class="row mb-4">
		<label class="col-sm-3 col-form-label fw-medium pt-0">
			<?php esc_html_e( 'Custom icon', 'cecom-wishlist-for-woocommerce' ); ?>
			<?php cecomwishfw_label_tooltip( __( 'Leave blank to use the default Bootstrap heart icon.', 'cecom-wishlist-for-woocommerce' ) ); ?>
		</label>
		<div class="col-sm-9">
			<div class="d-flex align-items-center gap-2">
				<?php $single_icon_cls = (string) $a['single_icon_class']; ?>
				<input type="hidden"
						name="settings[single_icon_class]"
						id="single-icon-class"
						value="<?php echo esc_attr( $single_icon_cls ); ?>">
				<span class="cecomwishfw-icon-preview-bi" id="single-icon-preview" aria-hidden="true">
					<i class="bi <?php echo esc_attr( '' !== $single_icon_cls ? $single_icon_cls : 'bi-heart' ); ?>"></i>
				</span>
				<button type="button"
						class="btn btn-outline-secondary btn-sm cecomwishfw-icon-picker-btn d-inline-flex align-items-center gap-1"
						data-target="single-icon-class"
						data-preview="single-icon-preview">
					<i class="bi bi-grid-3x3-gap" aria-hidden="true"></i>
					<?php esc_html_e( 'Choose', 'cecom-wishlist-for-woocommerce' ); ?>
				</button>
				<button type="button"
						class="btn btn-outline-danger btn-sm cecomwishfw-icon-clear-btn d-inline-flex align-items-center gap-1"
						data-target="single-icon-class"
						data-preview="single-icon-preview"
						style="<?php echo esc_attr( '' === $single_icon_cls ? 'display:none;' : '' ); ?>">
					<i class="bi bi-x-lg" aria-hidden="true"></i>
					<?php esc_html_e( 'Clear', 'cecom-wishlist-for-woocommerce' ); ?>
				</button>
			</div>
		</div>
	</div>

	<?php /* ── Padding ──────────────────────────────────────────────── */ ?>
	<div class="row mb-4">
		<label class="col-sm-3 col-form-label fw-medium pt-0">
			<?php esc_html_e( 'Padding', 'cecom-wishlist-for-woocommerce' ); ?>
			<?php cecomwishfw_label_tooltip( __( 'Set top, right, bottom and left independently. Drag every slider to 0 to use the theme default.', 'cecom-wishlist-for-woocommerce' ) ); ?>
		</label>
		<div class="col-sm-9">
			<?php cecomwishfw_render_padding_group( 'single_padding', (string) $a['single_padding'], 0, 40, 1, __( 'Set top, right, bottom and left independently. Drag every slider to 0 to use the theme default.', 'cecom-wishlist-for-woocommerce' ) ); ?>
		</div>
	</div>

	<?php /* ── Margin ───────────────────────────────────────────────── */ ?>
	<div class="row mb-4">
		<label class="col-sm-3 col-form-label fw-medium pt-0">
			<?php esc_html_e( 'Margin', 'cecom-wishlist-for-woocommerce' ); ?>
			<?php cecomwishfw_label_tooltip( __( 'Set top, right, bottom and left independently. Drag every slider to 0 to use the theme default.', 'cecom-wishlist-for-woocommerce' ) ); ?>
		</label>
		<div class="col-sm-9">
			<?php cecomwishfw_render_padding_group( 'single_margin', (string) $a['single_margin'], 0, 40, 1, __( 'Set top, right, bottom and left independently. Drag every slider to 0 to use the theme default.', 'cecom-wishlist-for-woocommerce' ) ); ?>
		</div>
	</div>

	<?php /* ── Font size ────────────────────────────────────────────── */ ?>
	<div class="row mb-0">
		<label class="col-sm-3 col-form-label fw-medium pt-0">
			<?php esc_html_e( 'Font size', 'cecom-wishlist-for-woocommerce' ); ?>
			<?php cecomwishfw_label_tooltip( __( 'Drag to 0 to use the theme default.', 'cecom-wishlist-for-woocommerce' ) ); ?>
		</label>
		<div class="col-sm-9">
			<?php cecomwishfw_render_dim_range( 'single_font_size', (string) $a['single_font_size'], 0, 32, 1, __( 'Drag to 0 to use the theme default.', 'cecom-wishlist-for-woocommerce' ) ); ?>
		</div>
	</div>

	<?php /* ── Additional appearance — type selector ──────────────── */ ?>
	<div class="row mt-4 pt-4 border-top">
		<label for="single_appearance_type" class="col-sm-3 col-form-label fw-medium pt-0">
			<?php esc_html_e( 'Appearance type', 'cecom-wishlist-for-woocommerce' ); ?>
			<?php cecomwishfw_label_tooltip( __( 'Choose how the single product wishlist button should look. "Custom style" unlocks full control over colors, border, and opacity.', 'cecom-wishlist-for-woocommerce' ) ); ?>
		</label>
		<div class="col-sm-9">
			<select class="form-select form-select-sm cecomwishfw-appearance-type"
					style="max-width:320px;"
					name="settings[single_appearance_type]"
					id="single_appearance_type"
					data-context="single">
				<option value="" <?php selected( '', $single_appearance_type ); ?>><?php esc_html_e( 'Default (plugin styling)', 'cecom-wishlist-for-woocommerce' ); ?></option>
				<option value="textual" <?php selected( 'textual', $single_appearance_type ); ?>><?php esc_html_e( 'Textual (anchor)', 'cecom-wishlist-for-woocommerce' ); ?></option>
				<option value="theme" <?php selected( 'theme', $single_appearance_type ); ?>><?php esc_html_e( 'Button with theme style', 'cecom-wishlist-for-woocommerce' ); ?></option>
				<option value="custom" <?php selected( 'custom', $single_appearance_type ); ?>><?php esc_html_e( 'Button with custom style', 'cecom-wishlist-for-woocommerce' ); ?></option>
			</select>
		</div>
	</div>

	<?php /* ── Custom style fields — revealed when "custom" is selected ── */ ?>
	<div class="cecomwishfw-custom-style mt-3" data-context="single"<?php if ( 'custom' !== $single_appearance_type ) : ?> hidden<?php endif; ?>>
		<div class="row mb-4">
			<label for="single_custom_bg" class="col-sm-3 col-form-label fw-medium pt-0">
				<?php esc_html_e( 'Background', 'cecom-wishlist-for-woocommerce' ); ?>
			</label>
			<div class="col-sm-9">
				<?php cecomwishfw_render_alpha_picker( 'single_custom_bg', (string) $a['single_custom_bg'], (string) $a['single_custom_bg_opacity'], '#ffffff', esc_attr__( 'Background color', 'cecom-wishlist-for-woocommerce' ) ); ?>
			</div>
		</div>
		<div class="row mb-4">
			<label for="single_custom_text" class="col-sm-3 col-form-label fw-medium pt-0">
				<?php esc_html_e( 'Text color', 'cecom-wishlist-for-woocommerce' ); ?>
			</label>
			<div class="col-sm-9">
				<?php cecomwishfw_render_alpha_picker( 'single_custom_text', (string) $a['single_custom_text'], (string) $a['single_custom_text_opacity'], '#111111', esc_attr__( 'Text color', 'cecom-wishlist-for-woocommerce' ) ); ?>
			</div>
		</div>
		<div class="row mb-4">
			<label for="single_custom_border" class="col-sm-3 col-form-label fw-medium pt-0">
				<?php esc_html_e( 'Border color', 'cecom-wishlist-for-woocommerce' ); ?>
			</label>
			<div class="col-sm-9">
				<?php cecomwishfw_render_alpha_picker( 'single_custom_border', (string) $a['single_custom_border'], (string) $a['single_custom_border_opacity'], '#4f46e5', esc_attr__( 'Border color', 'cecom-wishlist-for-woocommerce' ) ); ?>
			</div>
		</div>
		<div class="row mb-4">
			<label for="single_custom_bg_hover" class="col-sm-3 col-form-label fw-medium pt-0">
				<?php esc_html_e( 'Background (hover)', 'cecom-wishlist-for-woocommerce' ); ?>
			</label>
			<div class="col-sm-9">
				<?php cecomwishfw_render_alpha_picker( 'single_custom_bg_hover', (string) $a['single_custom_bg_hover'], (string) $a['single_custom_bg_hover_opacity'], '#4f46e5', esc_attr__( 'Hover background color', 'cecom-wishlist-for-woocommerce' ) ); ?>
			</div>
		</div>
		<div class="row mb-4">
			<label for="single_custom_text_hover" class="col-sm-3 col-form-label fw-medium pt-0">
				<?php esc_html_e( 'Text color (hover)', 'cecom-wishlist-for-woocommerce' ); ?>
			</label>
			<div class="col-sm-9">
				<?php cecomwishfw_render_alpha_picker( 'single_custom_text_hover', (string) $a['single_custom_text_hover'], (string) $a['single_custom_text_hover_opacity'], '#ffffff', esc_attr__( 'Hover text color', 'cecom-wishlist-for-woocommerce' ) ); ?>
			</div>
		</div>
		<div class="row mb-4">
			<label for="single_custom_border_hover" class="col-sm-3 col-form-label fw-medium pt-0">
				<?php esc_html_e( 'Border color (hover)', 'cecom-wishlist-for-woocommerce' ); ?>
			</label>
			<div class="col-sm-9">
				<?php cecomwishfw_render_alpha_picker( 'single_custom_border_hover', (string) $a['single_custom_border_hover'], (string) $a['single_custom_border_hover_opacity'], '#4f46e5', esc_attr__( 'Hover border color', 'cecom-wishlist-for-woocommerce' ) ); ?>
			</div>
		</div>
		<div class="row mb-4">
			<label class="col-sm-3 col-form-label fw-medium pt-0">
				<?php esc_html_e( 'Border radius', 'cecom-wishlist-for-woocommerce' ); ?>
				<?php cecomwishfw_label_tooltip( __( 'Drag to 0 to use the plugin default.', 'cecom-wishlist-for-woocommerce' ) ); ?>
			</label>
			<div class="col-sm-9">
				<?php cecomwishfw_render_dim_range( 'single_custom_radius', (string) $a['single_custom_radius'], 0, 40, 1, __( 'Drag to 0 to use the plugin default.', 'cecom-wishlist-for-woocommerce' ) ); ?>
			</div>
		</div>
		<div class="row mb-0">
			<label class="col-sm-3 col-form-label fw-medium pt-0">
				<?php esc_html_e( 'Border thickness', 'cecom-wishlist-for-woocommerce' ); ?>
				<?php cecomwishfw_label_tooltip( __( 'Drag to 0 to use the plugin default.', 'cecom-wishlist-for-woocommerce' ) ); ?>
			</label>
			<div class="col-sm-9">
				<?php cecomwishfw_render_dim_range( 'single_custom_border_width', (string) $a['single_custom_border_width'], 0, 10, 1, __( 'Drag to 0 to use the plugin default.', 'cecom-wishlist-for-woocommerce' ) ); ?>
			</div>
		</div>
	</div>
</div>

<?php /* ── Shop Loop Button panel ──────────────────────────────────── */ ?>
<div class="col-12 mb-3 shadow-sm rounded-4 p-3 p-sm-4 border border-light-subtle bg-white">
	<h2 class="h6 fw-semibold text-body-emphasis mb-3">
		<?php esc_html_e( 'Shop Loop Button', 'cecom-wishlist-for-woocommerce' ); ?>
	</h2>

	<?php /* ── Labels ──────────────────────────────────────────────── */ ?>
	<div class="row mb-4">
		<label for="loop_add_label" class="col-sm-3 col-form-label fw-medium pt-0">
			<?php esc_html_e( '"Add to Wishlist" label', 'cecom-wishlist-for-woocommerce' ); ?>
			<?php cecomwishfw_label_tooltip( __( 'Leave blank to use the default translated string.', 'cecom-wishlist-for-woocommerce' ) ); ?>
		</label>
		<div class="col-sm-9">
			<input type="text"
					class="form-control form-control-sm"
					style="max-width:320px;"
					name="settings[loop_add_label]"
					id="loop_add_label"
					value="<?php echo esc_attr( $a['loop_add_label'] ); ?>"
					placeholder="<?php esc_attr_e( 'Add to wishlist', 'cecom-wishlist-for-woocommerce' ); ?>">
		</div>
	</div>

	<div class="row mb-4">
		<label for="loop_remove_label" class="col-sm-3 col-form-label fw-medium pt-0">
			<?php esc_html_e( '"Remove" label', 'cecom-wishlist-for-woocommerce' ); ?>
			<?php cecomwishfw_label_tooltip( __( 'Leave blank to use the default translated string.', 'cecom-wishlist-for-woocommerce' ) ); ?>
		</label>
		<div class="col-sm-9">
			<input type="text"
					class="form-control form-control-sm"
					style="max-width:320px;"
					name="settings[loop_remove_label]"
					id="loop_remove_label"
					value="<?php echo esc_attr( $a['loop_remove_label'] ); ?>"
					placeholder="<?php esc_attr_e( 'Remove from wishlist', 'cecom-wishlist-for-woocommerce' ); ?>">
		</div>
	</div>

	<?php /* ── Color ────────────────────────────────────────────────── */ ?>
	<div class="row mb-4">
		<label for="loop_button_color" class="col-sm-3 col-form-label fw-medium pt-0">
			<?php esc_html_e( 'Button color', 'cecom-wishlist-for-woocommerce' ); ?>
			<?php cecomwishfw_label_tooltip( __( 'Applied as the primary color on shop and archive pages.', 'cecom-wishlist-for-woocommerce' ) ); ?>
		</label>
		<div class="col-sm-9">
			<div class="d-flex align-items-center gap-2">
				<input type="color"
						class="form-control form-control-color form-control-sm"
						name="settings[loop_button_color]"
						id="loop_button_color"
						value="<?php echo esc_attr( $loop_color_value ); ?>"
						aria-label="<?php esc_attr_e( 'Choose button color', 'cecom-wishlist-for-woocommerce' ); ?>">
				<button type="button"
						class="btn btn-sm btn-outline-secondary cecomwishfw-reset-color d-inline-flex align-items-center"
						data-target="loop_button_color"
						data-default="#4f46e5"
						aria-label="<?php esc_attr_e( 'Reset to default color', 'cecom-wishlist-for-woocommerce' ); ?>">
					<i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
				</button>
			</div>
		</div>
	</div>

	<?php /* ── Custom icon ──────────────────────────────────────────── */ ?>
	<div class="row mb-4">
		<label class="col-sm-3 col-form-label fw-medium pt-0">
			<?php esc_html_e( 'Custom icon', 'cecom-wishlist-for-woocommerce' ); ?>
			<?php cecomwishfw_label_tooltip( __( 'Leave blank to use the default Bootstrap heart icon.', 'cecom-wishlist-for-woocommerce' ) ); ?>
		</label>
		<div class="col-sm-9">
			<div class="d-flex align-items-center gap-2">
				<?php $loop_icon_cls = (string) $a['loop_icon_class']; ?>
				<input type="hidden"
						name="settings[loop_icon_class]"
						id="loop-icon-class"
						value="<?php echo esc_attr( $loop_icon_cls ); ?>">
				<span class="cecomwishfw-icon-preview-bi" id="loop-icon-preview" aria-hidden="true">
					<i class="bi <?php echo esc_attr( '' !== $loop_icon_cls ? $loop_icon_cls : 'bi-heart' ); ?>"></i>
				</span>
				<button type="button"
						class="btn btn-outline-secondary btn-sm cecomwishfw-icon-picker-btn d-inline-flex align-items-center gap-1"
						data-target="loop-icon-class"
						data-preview="loop-icon-preview">
					<i class="bi bi-grid-3x3-gap" aria-hidden="true"></i>
					<?php esc_html_e( 'Choose', 'cecom-wishlist-for-woocommerce' ); ?>
				</button>
				<button type="button"
						class="btn btn-outline-danger btn-sm cecomwishfw-icon-clear-btn d-inline-flex align-items-center gap-1"
						data-target="loop-icon-class"
						data-preview="loop-icon-preview"
						style="<?php echo esc_attr( '' === $loop_icon_cls ? 'display:none;' : '' ); ?>">
					<i class="bi bi-x-lg" aria-hidden="true"></i>
					<?php esc_html_e( 'Clear', 'cecom-wishlist-for-woocommerce' ); ?>
				</button>
			</div>
		</div>
	</div>

	<?php /* ── Padding ──────────────────────────────────────────────── */ ?>
	<div class="row mb-4">
		<label class="col-sm-3 col-form-label fw-medium pt-0">
			<?php esc_html_e( 'Padding', 'cecom-wishlist-for-woocommerce' ); ?>
			<?php cecomwishfw_label_tooltip( __( 'Set top, right, bottom and left independently. Drag every slider to 0 to use the theme default.', 'cecom-wishlist-for-woocommerce' ) ); ?>
		</label>
		<div class="col-sm-9">
			<?php cecomwishfw_render_padding_group( 'loop_padding', (string) $a['loop_padding'], 0, 40, 1, __( 'Set top, right, bottom and left independently. Drag every slider to 0 to use the theme default.', 'cecom-wishlist-for-woocommerce' ) ); ?>
		</div>
	</div>

	<?php /* ── Margin ───────────────────────────────────────────────── */ ?>
	<div class="row mb-4">
		<label class="col-sm-3 col-form-label fw-medium pt-0">
			<?php esc_html_e( 'Margin', 'cecom-wishlist-for-woocommerce' ); ?>
			<?php cecomwishfw_label_tooltip( __( 'Set top, right, bottom and left independently. Drag every slider to 0 to use the theme default.', 'cecom-wishlist-for-woocommerce' ) ); ?>
		</label>
		<div class="col-sm-9">
			<?php cecomwishfw_render_padding_group( 'loop_margin', (string) $a['loop_margin'], 0, 40, 1, __( 'Set top, right, bottom and left independently. Drag every slider to 0 to use the theme default.', 'cecom-wishlist-for-woocommerce' ) ); ?>
		</div>
	</div>

	<?php /* ── Font size ────────────────────────────────────────────── */ ?>
	<div class="row mb-0">
		<label class="col-sm-3 col-form-label fw-medium pt-0">
			<?php esc_html_e( 'Font size', 'cecom-wishlist-for-woocommerce' ); ?>
			<?php cecomwishfw_label_tooltip( __( 'Drag to 0 to use the theme default.', 'cecom-wishlist-for-woocommerce' ) ); ?>
		</label>
		<div class="col-sm-9">
			<?php cecomwishfw_render_dim_range( 'loop_font_size', (string) $a['loop_font_size'], 0, 32, 1, __( 'Drag to 0 to use the theme default.', 'cecom-wishlist-for-woocommerce' ) ); ?>
		</div>
	</div>

	<?php /* ── Additional appearance — type selector ──────────────── */ ?>
	<div class="row mt-4 pt-4 border-top">
		<label for="loop_appearance_type" class="col-sm-3 col-form-label fw-medium pt-0">
			<?php esc_html_e( 'Appearance type', 'cecom-wishlist-for-woocommerce' ); ?>
			<?php cecomwishfw_label_tooltip( __( 'Choose how the shop/archive wishlist button should look. "Custom style" unlocks full control over colors, border, and opacity.', 'cecom-wishlist-for-woocommerce' ) ); ?>
		</label>
		<div class="col-sm-9">
			<select class="form-select form-select-sm cecomwishfw-appearance-type"
					style="max-width:320px;"
					name="settings[loop_appearance_type]"
					id="loop_appearance_type"
					data-context="loop">
				<option value="" <?php selected( '', $loop_appearance_type ); ?>><?php esc_html_e( 'Default (plugin styling)', 'cecom-wishlist-for-woocommerce' ); ?></option>
				<option value="textual" <?php selected( 'textual', $loop_appearance_type ); ?>><?php esc_html_e( 'Textual (anchor)', 'cecom-wishlist-for-woocommerce' ); ?></option>
				<option value="theme" <?php selected( 'theme', $loop_appearance_type ); ?>><?php esc_html_e( 'Button with theme style', 'cecom-wishlist-for-woocommerce' ); ?></option>
				<option value="custom" <?php selected( 'custom', $loop_appearance_type ); ?>><?php esc_html_e( 'Button with custom style', 'cecom-wishlist-for-woocommerce' ); ?></option>
			</select>
		</div>
	</div>

	<?php /* ── Custom style fields — revealed when "custom" is selected ── */ ?>
	<div class="cecomwishfw-custom-style mt-3" data-context="loop"<?php if ( 'custom' !== $loop_appearance_type ) : ?> hidden<?php endif; ?>>
		<div class="row mb-4">
			<label for="loop_custom_bg" class="col-sm-3 col-form-label fw-medium pt-0">
				<?php esc_html_e( 'Background', 'cecom-wishlist-for-woocommerce' ); ?>
			</label>
			<div class="col-sm-9">
				<?php cecomwishfw_render_alpha_picker( 'loop_custom_bg', (string) $a['loop_custom_bg'], (string) $a['loop_custom_bg_opacity'], '#ffffff', esc_attr__( 'Background color', 'cecom-wishlist-for-woocommerce' ) ); ?>
			</div>
		</div>
		<div class="row mb-4">
			<label for="loop_custom_text" class="col-sm-3 col-form-label fw-medium pt-0">
				<?php esc_html_e( 'Text color', 'cecom-wishlist-for-woocommerce' ); ?>
			</label>
			<div class="col-sm-9">
				<?php cecomwishfw_render_alpha_picker( 'loop_custom_text', (string) $a['loop_custom_text'], (string) $a['loop_custom_text_opacity'], '#111111', esc_attr__( 'Text color', 'cecom-wishlist-for-woocommerce' ) ); ?>
			</div>
		</div>
		<div class="row mb-4">
			<label for="loop_custom_border" class="col-sm-3 col-form-label fw-medium pt-0">
				<?php esc_html_e( 'Border color', 'cecom-wishlist-for-woocommerce' ); ?>
			</label>
			<div class="col-sm-9">
				<?php cecomwishfw_render_alpha_picker( 'loop_custom_border', (string) $a['loop_custom_border'], (string) $a['loop_custom_border_opacity'], '#4f46e5', esc_attr__( 'Border color', 'cecom-wishlist-for-woocommerce' ) ); ?>
			</div>
		</div>
		<div class="row mb-4">
			<label for="loop_custom_bg_hover" class="col-sm-3 col-form-label fw-medium pt-0">
				<?php esc_html_e( 'Background (hover)', 'cecom-wishlist-for-woocommerce' ); ?>
			</label>
			<div class="col-sm-9">
				<?php cecomwishfw_render_alpha_picker( 'loop_custom_bg_hover', (string) $a['loop_custom_bg_hover'], (string) $a['loop_custom_bg_hover_opacity'], '#4f46e5', esc_attr__( 'Hover background color', 'cecom-wishlist-for-woocommerce' ) ); ?>
			</div>
		</div>
		<div class="row mb-4">
			<label for="loop_custom_text_hover" class="col-sm-3 col-form-label fw-medium pt-0">
				<?php esc_html_e( 'Text color (hover)', 'cecom-wishlist-for-woocommerce' ); ?>
			</label>
			<div class="col-sm-9">
				<?php cecomwishfw_render_alpha_picker( 'loop_custom_text_hover', (string) $a['loop_custom_text_hover'], (string) $a['loop_custom_text_hover_opacity'], '#ffffff', esc_attr__( 'Hover text color', 'cecom-wishlist-for-woocommerce' ) ); ?>
			</div>
		</div>
		<div class="row mb-4">
			<label for="loop_custom_border_hover" class="col-sm-3 col-form-label fw-medium pt-0">
				<?php esc_html_e( 'Border color (hover)', 'cecom-wishlist-for-woocommerce' ); ?>
			</label>
			<div class="col-sm-9">
				<?php cecomwishfw_render_alpha_picker( 'loop_custom_border_hover', (string) $a['loop_custom_border_hover'], (string) $a['loop_custom_border_hover_opacity'], '#4f46e5', esc_attr__( 'Hover border color', 'cecom-wishlist-for-woocommerce' ) ); ?>
			</div>
		</div>
		<div class="row mb-4">
			<label class="col-sm-3 col-form-label fw-medium pt-0">
				<?php esc_html_e( 'Border radius', 'cecom-wishlist-for-woocommerce' ); ?>
				<?php cecomwishfw_label_tooltip( __( 'Drag to 0 to use the plugin default.', 'cecom-wishlist-for-woocommerce' ) ); ?>
			</label>
			<div class="col-sm-9">
				<?php cecomwishfw_render_dim_range( 'loop_custom_radius', (string) $a['loop_custom_radius'], 0, 40, 1, __( 'Drag to 0 to use the plugin default.', 'cecom-wishlist-for-woocommerce' ) ); ?>
			</div>
		</div>
		<div class="row mb-0">
			<label class="col-sm-3 col-form-label fw-medium pt-0">
				<?php esc_html_e( 'Border thickness', 'cecom-wishlist-for-woocommerce' ); ?>
				<?php cecomwishfw_label_tooltip( __( 'Drag to 0 to use the plugin default.', 'cecom-wishlist-for-woocommerce' ) ); ?>
			</label>
			<div class="col-sm-9">
				<?php cecomwishfw_render_dim_range( 'loop_custom_border_width', (string) $a['loop_custom_border_width'], 0, 10, 1, __( 'Drag to 0 to use the plugin default.', 'cecom-wishlist-for-woocommerce' ) ); ?>
			</div>
		</div>
	</div>
</div>

<?php /* ── Wishlist Counter panel ──────────────────────────────────────── */ ?>
<?php $counter_icon_cls = (string) $a['counter_icon_class']; ?>
<div class="col-12 mb-3 shadow-sm rounded-4 p-3 p-sm-4 border border-light-subtle bg-white">
	<h2 class="h6 fw-semibold text-body-emphasis mb-3">
		<?php esc_html_e( 'Wishlist Counter', 'cecom-wishlist-for-woocommerce' ); ?>
	</h2>

	<?php /* ── Enable ──────────────────────────────────────────────────── */ ?>
	<div class="row mb-4">
		<div class="col-sm-3 col-form-label fw-medium pt-0">
			<?php esc_html_e( 'Enable counter', 'cecom-wishlist-for-woocommerce' ); ?>
		</div>
		<div class="col-sm-9">
			<div class="form-check form-switch">
				<input class="form-check-input"
						type="checkbox"
						role="switch"
						name="settings[show_counter]"
						id="show_counter"
						value="1"
						<?php checked( (bool) $a['show_counter'] ); ?>>
				<label class="form-check-label" for="show_counter">
					<?php esc_html_e( 'Enable JavaScript counter updates on .cecomwishfw-count elements', 'cecom-wishlist-for-woocommerce' ); ?>
				</label>
			</div>
		</div>
	</div>

	<?php /* ── Show icon ───────────────────────────────────────────────── */ ?>
	<div class="row mb-4">
		<div class="col-sm-3 col-form-label fw-medium pt-0">
			<?php esc_html_e( 'Show icon', 'cecom-wishlist-for-woocommerce' ); ?>
		</div>
		<div class="col-sm-9">
			<div class="form-check form-switch">
				<input class="form-check-input"
						type="checkbox"
						role="switch"
						name="settings[counter_show_icon]"
						id="counter_show_icon"
						value="1"
						<?php checked( (bool) $a['counter_show_icon'] ); ?>>
				<label class="form-check-label" for="counter_show_icon">
					<?php esc_html_e( 'Display an icon next to the count badge', 'cecom-wishlist-for-woocommerce' ); ?>
				</label>
			</div>
		</div>
	</div>

	<?php /* ── Link to wishlist ─────────────────────────────────────────── */ ?>
	<div class="row mb-4">
		<div class="col-sm-3 col-form-label fw-medium pt-0">
			<?php esc_html_e( 'Link to wishlist', 'cecom-wishlist-for-woocommerce' ); ?>
		</div>
		<div class="col-sm-9">
			<div class="form-check form-switch">
				<input class="form-check-input"
						type="checkbox"
						role="switch"
						name="settings[counter_link]"
						id="counter_link"
						value="1"
						<?php checked( (bool) $a['counter_link'] ); ?>>
				<label class="form-check-label" for="counter_link">
					<?php esc_html_e( 'Wrap the counter in a link to the wishlist page', 'cecom-wishlist-for-woocommerce' ); ?>
				</label>
			</div>
		</div>
	</div>

	<?php /* ── Show when empty ─────────────────────────────────────────── */ ?>
	<div class="row mb-4">
		<div class="col-sm-3 col-form-label fw-medium pt-0">
			<?php esc_html_e( 'Show when empty', 'cecom-wishlist-for-woocommerce' ); ?>
		</div>
		<div class="col-sm-9">
			<div class="form-check form-switch">
				<input class="form-check-input"
						type="checkbox"
						role="switch"
						name="settings[counter_show_zero]"
						id="counter_show_zero"
						value="1"
						<?php checked( (bool) $a['counter_show_zero'] ); ?>>
				<label class="form-check-label" for="counter_show_zero">
					<?php esc_html_e( 'Keep the badge visible when the wishlist is empty (count 0)', 'cecom-wishlist-for-woocommerce' ); ?>
				</label>
			</div>
		</div>
	</div>

	<?php /* ── Custom icon ─────────────────────────────────────────────── */ ?>
	<div class="row mb-4">
		<label class="col-sm-3 col-form-label fw-medium pt-0">
			<?php esc_html_e( 'Icon', 'cecom-wishlist-for-woocommerce' ); ?>
			<?php cecomwishfw_label_tooltip( __( 'Icon displayed next to the count badge. Default: bi-heart.', 'cecom-wishlist-for-woocommerce' ) ); ?>
		</label>
		<div class="col-sm-9">
			<div class="d-flex align-items-center gap-2 flex-wrap">
				<input type="hidden"
						name="settings[counter_icon_class]"
						id="counter-icon-class"
						value="<?php echo esc_attr( $counter_icon_cls ); ?>">
				<span class="cecomwishfw-icon-preview-bi" id="counter-icon-preview" aria-hidden="true">
					<i class="bi <?php echo esc_attr( '' !== $counter_icon_cls ? $counter_icon_cls : 'bi-heart' ); ?>"></i>
				</span>
				<button type="button"
						class="btn btn-outline-secondary btn-sm cecomwishfw-icon-picker-btn d-inline-flex align-items-center gap-1"
						data-target="counter-icon-class"
						data-preview="counter-icon-preview">
					<i class="bi bi-grid-3x3-gap" aria-hidden="true"></i>
					<?php esc_html_e( 'Choose', 'cecom-wishlist-for-woocommerce' ); ?>
				</button>
				<button type="button"
						class="btn btn-outline-danger btn-sm cecomwishfw-icon-clear-btn d-inline-flex align-items-center gap-1"
						data-target="counter-icon-class"
						data-preview="counter-icon-preview"
						style="<?php echo esc_attr( '' === $counter_icon_cls ? 'display:none;' : '' ); ?>">
					<i class="bi bi-x-lg" aria-hidden="true"></i>
					<?php esc_html_e( 'Clear', 'cecom-wishlist-for-woocommerce' ); ?>
				</button>
			</div>
		</div>
	</div>

	<?php /* ── Usage ───────────────────────────────────────────────────── */ ?>
	<div class="row mb-0">
		<div class="col-sm-3 col-form-label fw-medium pt-0">
			<?php esc_html_e( 'Usage', 'cecom-wishlist-for-woocommerce' ); ?>
		</div>
		<div class="col-sm-9">
			<p class="mb-2 text-body-secondary small">
				<?php esc_html_e( 'Place the counter anywhere using the shortcode or the "Wishlist Counter" block in the block editor:', 'cecom-wishlist-for-woocommerce' ); ?>
			</p>
			<code class="d-block mb-3 p-2 rounded bg-light small">[cecomwishfw_count]</code>
			<p class="mb-2 text-body-secondary small">
				<?php esc_html_e( 'Shortcode attributes override the settings above per placement:', 'cecom-wishlist-for-woocommerce' ); ?>
			</p>
			<code class="d-block mb-3 p-2 rounded bg-light small">[cecomwishfw_count show_icon="1" link="1" icon_class="bi-heart" show_zero="0"]</code>
			<p class="mb-0 text-body-secondary small">
				<?php esc_html_e( 'Any element with the class .cecomwishfw-count is also updated automatically by JavaScript when items are added or removed.', 'cecom-wishlist-for-woocommerce' ); ?>
			</p>
		</div>
	</div>
</div>

<?php /* ── Custom CSS panel ──────────────────────────────────────────── */ ?>
<div class="col-12 mb-3 shadow-sm rounded-4 p-3 p-sm-4 border border-light-subtle bg-white">
	<h2 class="h6 fw-semibold text-body-emphasis mb-3">
		<?php esc_html_e( 'Custom CSS', 'cecom-wishlist-for-woocommerce' ); ?>
	</h2>
	<p class="text-body-secondary small mb-3">
		<?php esc_html_e( 'Catch-all for any style rules the settings above do not cover. Rules written here are appended after the computed per-button CSS, so they take precedence when specificity is equal.', 'cecom-wishlist-for-woocommerce' ); ?>
	</p>

	<div class="mb-3">
		<label for="cecomwishfw_custom_css" class="form-label fw-medium mb-2">
			<?php esc_html_e( 'Your CSS', 'cecom-wishlist-for-woocommerce' ); ?>
			<?php cecomwishfw_label_tooltip( __( 'HTML tags are stripped on save. Invalid CSS is silently ignored by the browser and cannot break other rules on the page.', 'cecom-wishlist-for-woocommerce' ) ); ?>
		</label>
		<textarea class="form-control form-control-sm font-monospace"
					name="settings[custom_css]"
					id="cecomwishfw_custom_css"
					rows="10"
					spellcheck="false"
					placeholder="<?php esc_attr_e( '/* Your rules here */', 'cecom-wishlist-for-woocommerce' ); ?>"><?php echo esc_textarea( (string) $a['custom_css'] ); ?></textarea>
	</div>

	<div>
		<p class="small fw-medium mb-2">
			<?php esc_html_e( 'Example selectors', 'cecom-wishlist-for-woocommerce' ); ?>
		</p>
		<pre class="mb-0 p-3 rounded bg-light small font-monospace" style="white-space:pre-wrap;"><code>/* Both wishlist buttons */
.cecomwishfw-btn { letter-spacing: 0.05em; }

/* Single product button only */
.cecomwishfw-btn--single { text-transform: uppercase; }

/* Shop loop button only */
.cecomwishfw-btn--loop { transition: transform 0.2s ease; }

/* Hover state */
.cecomwishfw-btn:hover { transform: translateY(-1px); }

/* "In wishlist" (active) state */
.cecomwishfw-btn.active { font-weight: 700; }

/* Wishlist counter badge */
.cecomwishfw-count { color: inherit; }</code></pre>
	</div>
</div>
