<?php
/**
 * Admin settings page controller.
 *
 * Registers the plugin under the shared CECOM admin menu,
 * handles form submissions, and enqueues admin-only assets.
 *
 * @package Cecomwishfw
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Cecomwishfw_Admin_Controller
 */
class Cecomwishfw_Admin_Controller {

	/** Settings page slug. */
	private const PAGE_SLUG = 'cecomwishfw-settings';

	/** Required capability. */
	private const CAPABILITY = 'manage_woocommerce';

	/** Nonce action for settings save (form + AJAX). */
	private const NONCE_ACTION = 'cecomwishfw_admin_save';

	/** Nonce field name (traditional form). */
	private const NONCE_FIELD = '_cecomwishfw_nonce';

	/**
	 * Valid sections that accept user input.
	 *
	 * @var string[]
	 */
	private const SAVE_SECTIONS = array( 'general', 'appearance' );

	/**
	 * The hookname returned by add_submenu_page().
	 *
	 * WordPress derives the hookname from the sanitized MENU TITLE, not the slug,
	 * so it cannot be predicted at compile time (e.g. 'CECOM' → 'cecom', giving
	 * 'cecom_page_cecomwishfw-settings'). Storing the return value of
	 * add_submenu_page() is the only reliable way to match it in enqueue_assets().
	 *
	 * @var string
	 */
	private string $page_hook = '';

	// =========================================================================
	// Constructor
	// =========================================================================

	/**
	 * Constructor — no dependencies needed; Settings/Item/List models use static methods.
	 * Properties kept for testability and template access.
	 */
	public function __construct() {}

	// =========================================================================
	// Hook registration
	// =========================================================================

	/**
	 * Register the CECOM parent menu (singleton) and the Wishlist submenu entry.
	 *
	 * All CECOM plugins share the 'cecomplgns' parent slug. The parent is registered
	 * only once — whichever plugin's admin_menu hook fires first wins.
	 *
	 * @return void
	 */
	public function register_menu(): void {

		// -- CECOM parent menu (singleton) ------------------------------------
		global $menu;
		$cecomplgns_registered = false;
		if ( is_array( $menu ) ) {
			foreach ( $menu as $item ) {
				if ( isset( $item[2] ) && 'cecomplgns' === $item[2] ) {
					$cecomplgns_registered = true;
					break;
				}
			}
		}

		if ( ! $cecomplgns_registered ) {
			$icon_path = CECOMWISHFW_PLUGIN_DIR . 'assets/img/cecomplgns-menu-icon.svg';
			$icon_data = file_exists( $icon_path )
				? 'data:image/svg+xml;base64,' . base64_encode( // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
					(string) file_get_contents( $icon_path ) // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				)
				: 'dashicons-admin-plugins';

			add_menu_page(
				esc_html__( 'CECOM', 'cecom-wishlist-for-woocommerce' ),
				esc_html__( 'CECOM', 'cecom-wishlist-for-woocommerce' ),
				'manage_options',
				'cecomplgns',
				'__return_null',
				$icon_data,
				58
			);

			// Remove the WP separator that appears directly below the CECOM menu item.
			remove_menu_page( 'separator2' );
		}

		// -- Plugin submenu ---------------------------------------------------
		// Capture the hookname returned by add_submenu_page(). WordPress builds
		// the hookname from the sanitized PARENT MENU TITLE (not the slug), so
		// it cannot be predicted statically — e.g. 'CECOM' → 'cecom', giving
		// 'cecom_page_cecomwishfw-settings'. Using the stored value in
		// enqueue_assets() avoids the hook-suffix mismatch bug.
		$hook = add_submenu_page(
			'cecomplgns',
			esc_html__( 'Wishlist Settings', 'cecom-wishlist-for-woocommerce' ),
			esc_html__( 'Wishlist', 'cecom-wishlist-for-woocommerce' ),
			self::CAPABILITY,
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);

		$this->page_hook = is_string( $hook ) ? $hook : '';

		// WordPress auto-inserts the parent page as the first submenu entry.
		// Since the parent uses __return_null, remove the duplicate link.
		global $submenu;
		if ( isset( $submenu['cecomplgns'][0] ) && 'cecomplgns' === ( $submenu['cecomplgns'][0][2] ?? '' ) ) {
			unset( $submenu['cecomplgns'][0] );
		}
	}

	/**
	 * Enqueue admin assets — only on this plugin's settings page.
	 *
	 * Compares against $this->page_hook (set in register_menu()) rather than a
	 * hardcoded string because WordPress derives the hookname from the sanitized
	 * menu TITLE, not the slug. 'CECOM' sanitizes to 'cecom', so the actual
	 * hookname is 'cecom_page_cecomwishfw-settings', not
	 * 'cecomplgns_page_cecomwishfw-settings'.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		if ( '' === $this->page_hook || $this->page_hook !== $hook_suffix ) {
			return;
		}

		// Bootstrap CSS (bundled — no CDN).
		wp_enqueue_style(
			'cecomwishfw-bootstrap',
			CECOMWISHFW_PLUGIN_URL . 'assets/dist/css/bootstrap.min.css',
			array(),
			null // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- bundled asset, version omitted intentionally
		);

		// Bootstrap Icons (bundled).
		wp_enqueue_style(
			'cecomwishfw-bs-icons',
			CECOMWISHFW_PLUGIN_URL . 'assets/icons/font/bootstrap-icons.css',
			array(),
			null // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- bundled asset, version omitted intentionally
		);

		// Framework CSS — brand color overrides (shared, do NOT modify per-plugin).
		wp_enqueue_style(
			'cecomwishfw-framework',
			CECOMWISHFW_PLUGIN_URL . 'assets/css/cecom-plugin-admin-ui-framework.css',
			array( 'cecomwishfw-bootstrap' ),
			null // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- bundled asset, version omitted intentionally
		);

		// Plugin-specific admin CSS.
		$css_file = CECOMWISHFW_PLUGIN_DIR . 'assets/css/cecomwishfw-admin.css';
		wp_enqueue_style(
			'cecomwishfw-admin',
			CECOMWISHFW_PLUGIN_URL . 'assets/css/cecomwishfw-admin.css',
			array( 'cecomwishfw-framework' ),
			file_exists( $css_file ) ? (string) filemtime( $css_file ) : CECOMWISHFW_VERSION
		);

		// Bootstrap JS + Popper (bundled).
		wp_enqueue_script(
			'cecomwishfw-bootstrap',
			CECOMWISHFW_PLUGIN_URL . 'assets/dist/js/bootstrap.bundle.min.js',
			array(),
			null, // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- bundled asset, version omitted intentionally
			true
		);

		// Plugin admin JS.
		$js_file = CECOMWISHFW_PLUGIN_DIR . 'assets/js/cecomwishfw-admin.js';
		wp_enqueue_script(
			'cecomwishfw-admin',
			CECOMWISHFW_PLUGIN_URL . 'assets/js/cecomwishfw-admin.js',
			array( 'cecomwishfw-bootstrap' ),
			file_exists( $js_file ) ? (string) filemtime( $js_file ) : CECOMWISHFW_VERSION,
			true
		);

		// Build the Bootstrap Icons name list for the admin icon picker.
		// Reads bootstrap-icons.json (bundled) and extracts icon names prefixed with 'bi-'.
		// Only heart and bookmark icons are included — these are the meaningful choices
		// for a wishlist button; the full 2000+ icon set would overwhelm the picker.
		$icon_names      = array();
		$icons_json_path = CECOMWISHFW_PLUGIN_DIR . 'assets/icons/font/bootstrap-icons.json';
		if ( file_exists( $icons_json_path ) ) {
			$json = file_get_contents( $icons_json_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			if ( false !== $json ) {
				$decoded = json_decode( $json, true );
				if ( is_array( $decoded ) ) {
					$all_names  = array_map(
						static fn( string $name ) => 'bi-' . $name,
						array_keys( $decoded )
					);
					$icon_names = array_values(
						array_filter(
							$all_names,
							static fn( string $name ) => str_contains( $name, 'heart' ) || str_contains( $name, 'bookmark' )
						)
					);
				}
			}
		}

		wp_localize_script(
			'cecomwishfw-admin',
			'cecomwishfwAdmin',
			array(
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( self::NONCE_ACTION ),
				'icons'         => $icon_names,
				// Populated when the page is reached via the traditional (non-AJAX)
				// form save redirect (?updated=1). Admin JS reads this on init and
				// fires showToast() so the user sees a toast instead of a WP notice.
				'initialNotice' => ( isset( $_GET['updated'] ) && '1' === sanitize_key( $_GET['updated'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					? array(
						'message' => __( 'Settings saved.', 'cecom-wishlist-for-woocommerce' ),
						'type'    => 'success',
					)
					: null,
				'i18n'          => array(
					'saved'           => __( 'Settings saved.', 'cecom-wishlist-for-woocommerce' ),
					'saving'          => __( 'Saving…', 'cecom-wishlist-for-woocommerce' ),
					'error'           => __( 'An error occurred. Please retry.', 'cecom-wishlist-for-woocommerce' ),
					'saveSettings'    => __( 'Save Settings', 'cecom-wishlist-for-woocommerce' ),
					'chooseIcon'      => __( 'Choose Icon', 'cecom-wishlist-for-woocommerce' ),
					'searchIcons'     => __( 'Search icons…', 'cecom-wishlist-for-woocommerce' ),
					'resetConfirm'    => __( 'Reset all plugin settings to their defaults? This cannot be undone.', 'cecom-wishlist-for-woocommerce' ),
					'resetting'       => __( 'Resetting…', 'cecom-wishlist-for-woocommerce' ),
					'resetDone'       => __( 'Settings restored to defaults.', 'cecom-wishlist-for-woocommerce' ),
					'dimDefault'      => __( 'Default', 'cecom-wishlist-for-woocommerce' ),
					'shortcodeCopied' => __( 'Shortcode copied!', 'cecom-wishlist-for-woocommerce' ),
				),
			)
		);
	}

	/**
	 * Handle traditional (non-AJAX) form submission.
	 *
	 * Security order: nonce → capability → sanitize → save → redirect.
	 *
	 * @return void
	 */
	public function handle_form_submission(): void {
		// AJAX saves are handled by ajax_save_settings(); bail here to prevent
		// handle_form_submission from intercepting the request on admin_init,
		// redirecting, and returning HTML instead of JSON.
		if ( wp_doing_ajax() ) {
			return;
		}

		if ( ! isset( $_POST[ self::NONCE_FIELD ] ) ) {
			return;
		}

		if ( ! wp_verify_nonce(
			sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ),
			self::NONCE_ACTION
		) ) {
			wp_die( esc_html__( 'Security check failed.', 'cecom-wishlist-for-woocommerce' ) );
		}

		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'cecom-wishlist-for-woocommerce' ) );
		}

		$section = sanitize_key( wp_unslash( $_POST['tab'] ?? 'general' ) );
		if ( ! in_array( $section, self::SAVE_SECTIONS, true ) ) {
			$section = 'general';
		}

		$raw = isset( $_POST['settings'] ) ? map_deep( wp_unslash( (array) $_POST['settings'] ), 'sanitize_text_field' ) : array();
		Cecomwishfw_Settings::save( $section, $raw );

		if ( 'general' === $section && isset( $raw['wishlist_page_id'] ) ) {
			$this->maybe_insert_wishlist_shortcode( absint( $raw['wishlist_page_id'] ) );
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => self::PAGE_SLUG,
					'tab'     => $section,
					'updated' => '1',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * AJAX endpoint — save settings without page reload (fad-7).
	 *
	 * Security order: capability → nonce → sanitize → save → JSON response.
	 *
	 * @return void
	 */
	public function ajax_save_settings(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Permission denied.', 'cecom-wishlist-for-woocommerce' ) ),
				403
			);
		}

		check_ajax_referer( self::NONCE_ACTION, '_ajax_nonce' );

		$section = sanitize_key( wp_unslash( $_POST['tab'] ?? 'general' ) );
		if ( ! in_array( $section, self::SAVE_SECTIONS, true ) ) {
			$section = 'general';
		}

		$raw = isset( $_POST['settings'] ) ? map_deep( wp_unslash( (array) $_POST['settings'] ), 'sanitize_text_field' ) : array();
		Cecomwishfw_Settings::save( $section, $raw );

		if ( 'general' === $section && isset( $raw['wishlist_page_id'] ) ) {
			$this->maybe_insert_wishlist_shortcode( absint( $raw['wishlist_page_id'] ) );
		}

		wp_send_json_success(
			array( 'message' => __( 'Settings saved.', 'cecom-wishlist-for-woocommerce' ) )
		);
	}

	/**
	 * AJAX endpoint — reset every plugin setting back to its schema default.
	 *
	 * Security order: capability → nonce → reset → JSON response. Reuses the
	 * same nonce action as the save endpoint because both actions are gated
	 * behind the same capability.
	 *
	 * @return void
	 */
	public function ajax_reset_settings(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Permission denied.', 'cecom-wishlist-for-woocommerce' ) ),
				403
			);
		}

		check_ajax_referer( self::NONCE_ACTION, '_ajax_nonce' );

		Cecomwishfw_Settings::reset_all();

		wp_send_json_success(
			array( 'message' => __( 'Settings restored to defaults.', 'cecom-wishlist-for-woocommerce' ) )
		);
	}

	/**
	 * Insert [cecomwishfw_wishlist] into the selected page if not already present.
	 *
	 * Called after settings save when wishlist_page_id is submitted. Skips silently
	 * when the page ID is 0, the post does not exist, the post type is not 'page',
	 * or the shortcode is already in the post content.
	 *
	 * @param int $page_id WP page ID selected as the wishlist page.
	 * @return void
	 */
	private function maybe_insert_wishlist_shortcode( int $page_id ): void {
		if ( $page_id <= 0 ) {
			return;
		}

		$page = get_post( $page_id );
		if ( ! $page instanceof \WP_Post || 'page' !== $page->post_type ) {
			return;
		}

		if ( has_shortcode( $page->post_content, 'cecomwishfw_wishlist' ) ) {
			return;
		}

		$content = trim( $page->post_content );
		wp_update_post(
			array(
				'ID'           => $page_id,
				'post_content' => '' !== $content
					? $content . "\n\n[cecomwishfw_wishlist]"
					: '[cecomwishfw_wishlist]',
			)
		);
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render_page(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'cecom-wishlist-for-woocommerce' ) );
		}

		include CECOMWISHFW_PLUGIN_DIR . 'includes/views/admin/settings.php';
	}
}
