<?php
/**
 * Admin settings page shell.
 *
 * Renders the CECOM Wishlist settings page following the cecom-plugin-admin-ui-framework:
 *   #pluginWrap
 *     > rounded-4 header (logo, plugin title, version badge, Docs/Support/Upgrade CTAs)
 *     > flex layout
 *         > offcanvas-lg sidebar (.admin-tab links with data-panel attribute)
 *         > main.container-fluid — ALL tab panels pre-rendered, inactive ones d-none
 *
 * JS switches panels client-side (no reload) via cecomwishfw-admin.js bindTabSwitching().
 * Server still handles the URL on initial load so links like ?tab=appearance and
 * bookmarks continue to render the correct active tab without JS.
 *
 * Security: this template is only reachable via render_page(), which already
 * guards with current_user_can('manage_woocommerce'). No additional cap check
 * needed here.
 *
 * @package Cecomwishfw
 */

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template file; variables are passed from the controller, not global

// ── Active tab ──────────────────────────────────────────────────────────────
$active_tab   = sanitize_key( wp_unslash( $_GET['tab'] ?? 'general' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$free_tabs    = array( 'general', 'appearance', 'dashboard' );
$stub_tabs    = array( 'multiple_lists', 'email_campaigns', 'upgrade' );
$allowed_tabs = array_merge( $free_tabs, $stub_tabs );

if ( ! in_array( $active_tab, $allowed_tabs, true ) ) {
	$active_tab = 'general';
}

// ── External URLs (header + upgrade CTAs) ───────────────────────────────────
$docs_url    = 'https://cecom.in/docs/cecom-wishlist-for-woocommerce/';
$support_url = 'https://cecom.in/support/';
$upgrade_url = 'https://cecom.in/wishlist-for-woocommerce-annual-premium/';

// ── Tabs configuration ──────────────────────────────────────────────────────
$tabs = array( // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- local template variable, not modifying the WP global
	'general'         => array(
		'label'    => __( 'General', 'cecom-wishlist-for-woocommerce' ),
		'icon'     => 'bi-gear',
		'locked'   => false,
		'has_form' => true,
	),
	'appearance'      => array(
		'label'    => __( 'Appearance', 'cecom-wishlist-for-woocommerce' ),
		'icon'     => 'bi-palette',
		'locked'   => false,
		'has_form' => true,
	),
	'dashboard'       => array(
		'label'    => __( 'Dashboard', 'cecom-wishlist-for-woocommerce' ),
		'icon'     => 'bi-bar-chart-line',
		'locked'   => false,
		'has_form' => false,
	),
	'multiple_lists'  => array(
		'label'    => __( 'Multiple Lists', 'cecom-wishlist-for-woocommerce' ),
		'icon'     => 'bi-collection',
		'locked'   => true,
		'has_form' => false,
	),
	'email_campaigns' => array(
		'label'    => __( 'Email Campaigns', 'cecom-wishlist-for-woocommerce' ),
		'icon'     => 'bi-envelope-paper',
		'locked'   => true,
		'has_form' => false,
	),
	'upgrade'         => array(
		'label'    => __( 'Upgrade', 'cecom-wishlist-for-woocommerce' ),
		'icon'     => 'bi-stars',
		'locked'   => false,
		'has_form' => false,
	),
);

$base_url = admin_url( 'admin.php?page=cecomwishfw-settings' );
$logo_url = CECOMWISHFW_PLUGIN_URL . 'assets/img/cecomwishfw-icon.svg';
?>
<div id="pluginWrap" class="cecomwishfw-plugin-wrap">

	<?php /* ── Header ────────────────────────────────────────────────────── */ ?>
	<header class="d-flex flex-wrap align-items-center justify-content-between bg-white shadow-sm rounded-4 border border-light-subtle p-3 p-sm-4 mb-3 gap-2">

		<div class="d-flex align-items-center gap-2">
			<button type="button"
					class="d-lg-none btn btn-outline-secondary border-0 p-1"
					data-bs-toggle="offcanvas"
					data-bs-target="#cecomwishfwSidebar"
					aria-controls="cecomwishfwSidebar"
					aria-label="<?php esc_attr_e( 'Open navigation', 'cecom-wishlist-for-woocommerce' ); ?>">
				<i class="bi bi-list fs-4" aria-hidden="true"></i>
			</button>

			<img src="<?php echo esc_url( $logo_url ); ?>"
				height="45"
				alt=""
				aria-hidden="true">

			<div class="d-flex align-items-center gap-1 flex-wrap">
				<h1 class="plugin-title text-primary fw-light fs-5 mb-0">
					<span class="fw-bold"><?php esc_html_e( 'Wishlist', 'cecom-wishlist-for-woocommerce' ); ?></span>
					<?php esc_html_e( 'for WooCommerce', 'cecom-wishlist-for-woocommerce' ); ?>
				</h1>
				<span class="badge bg-primary-subtle border border-primary-subtle text-primary-emphasis rounded-pill">
					v<?php echo esc_html( CECOMWISHFW_VERSION ); ?>
				</span>
			</div>
		</div>

		<div class="d-flex align-items-center gap-2">
			<a href="<?php echo esc_url( $docs_url ); ?>"
				target="_blank"
				rel="noopener noreferrer"
				class="btn btn-light bg-white rounded-pill px-3 d-none d-md-inline-flex align-items-center gap-2">
				<i class="bi bi-book fs-5" aria-hidden="true"></i>
				<?php esc_html_e( 'Docs', 'cecom-wishlist-for-woocommerce' ); ?>
			</a>
			<a href="<?php echo esc_url( $support_url ); ?>"
				target="_blank"
				rel="noopener noreferrer"
				class="btn btn-light bg-white rounded-pill px-3 d-none d-md-inline-flex align-items-center gap-2">
				<i class="bi bi-question-circle fs-5" aria-hidden="true"></i>
				<?php esc_html_e( 'Support', 'cecom-wishlist-for-woocommerce' ); ?>
			</a>
			<a href="<?php echo esc_url( $upgrade_url ); ?>"
				target="_blank"
				rel="noopener noreferrer"
				class="btn btn-warning rounded-pill px-3 d-inline-flex align-items-center gap-2">
				<i class="bi bi-stars fs-5" aria-hidden="true"></i>
				<?php esc_html_e( 'Upgrade to Pro', 'cecom-wishlist-for-woocommerce' ); ?>
			</a>
		</div>
	</header>

	<?php /* ── Layout: sidebar + main ────────────────────────────────────── */ ?>
	<div class="d-flex align-items-start gap-3">

		<?php /* ── Sidebar (offcanvas-lg) ─────────────────────────────── */ ?>
		<div class="col-lg-3 offcanvas-lg offcanvas-start flex-shrink-0 bg-white shadow-sm rounded-4 border border-light-subtle"
			id="cecomwishfwSidebar"
			tabindex="-1"
			aria-labelledby="cecomwishfwSidebarLabel">

			<div class="offcanvas-header border-bottom d-lg-none">
				<span class="fw-semibold" id="cecomwishfwSidebarLabel">
					<?php esc_html_e( 'Navigation', 'cecom-wishlist-for-woocommerce' ); ?>
				</span>
				<button type="button"
						class="btn-close"
						data-bs-dismiss="offcanvas"
						data-bs-target="#cecomwishfwSidebar"
						aria-label="<?php esc_attr_e( 'Close', 'cecom-wishlist-for-woocommerce' ); ?>"></button>
			</div>

			<nav class="offcanvas-body p-3 d-flex flex-column gap-1" role="tablist">
				<?php foreach ( $tabs as $tab_key => $tab_cfg ) : ?>
					<?php
					$is_active  = ( $tab_key === $active_tab );
					$is_locked  = (bool) ( $tab_cfg['locked'] ?? false );
					$is_upgrade = ( 'upgrade' === $tab_key );
					$tab_url    = add_query_arg( 'tab', $tab_key, $base_url );

					$classes = 'admin-tab d-flex align-items-center gap-2 w-100 py-3 px-3 fs-6 fw-medium border-0 rounded-2 text-start text-decoration-none';
					if ( $is_active ) {
						$classes .= ' active';
					}
					if ( $is_locked ) {
						$classes .= ' bg-light opacity-75 text-secondary';
					} elseif ( $is_upgrade ) {
						$classes .= ' bg-transparent text-warning-emphasis fw-semibold';
					} else {
						$classes .= ' bg-transparent text-secondary';
					}
					?>
					<a href="<?php echo esc_url( $tab_url ); ?>"
						class="<?php echo esc_attr( $classes ); ?>"
						role="tab"
						data-panel="<?php echo esc_attr( $tab_key ); ?>"
						aria-controls="cecomwishfw-panel-<?php echo esc_attr( $tab_key ); ?>"
						aria-selected="<?php echo esc_attr( $is_active ? 'true' : 'false' ); ?>">
						<i class="bi <?php echo esc_attr( $tab_cfg['icon'] ); ?> fs-5" aria-hidden="true"></i>
						<span><?php echo esc_html( $tab_cfg['label'] ); ?></span>
						<?php if ( $is_locked ) : ?>
							<i class="bi bi-lock ms-auto" aria-hidden="true"></i>
						<?php endif; ?>
					</a>
				<?php endforeach; ?>
			</nav>
		</div>

		<?php /* ── Main content area — ALL panels rendered, inactive ones d-none ── */ ?>
		<main class="container-fluid m-0 p-0 flex-fill">

			<?php foreach ( $tabs as $tab_key => $tab_cfg ) : ?>
				<?php
				$is_active_panel = ( $tab_key === $active_tab );
				$panel_classes   = 'tab-panel';
				if ( ! $is_active_panel ) {
					$panel_classes .= ' d-none';
				}
				$is_stub  = in_array( $tab_key, $stub_tabs, true );
				$has_form = (bool) ( $tab_cfg['has_form'] ?? false );
				?>
				<div class="<?php echo esc_attr( $panel_classes ); ?>"
					id="cecomwishfw-panel-<?php echo esc_attr( $tab_key ); ?>"
					data-panel="<?php echo esc_attr( $tab_key ); ?>"
					role="tabpanel"
					aria-labelledby="cecomwishfw-tab-<?php echo esc_attr( $tab_key ); ?>">

					<?php if ( $is_stub ) : ?>
						<?php
						$stub_tab = $tab_key;
						include __DIR__ . '/tab-stubs.php';
						?>

					<?php elseif ( $has_form ) : ?>
						<form method="post"
								action=""
								class="cecomwishfw-settings-form"
								id="cecomwishfw-form-<?php echo esc_attr( $tab_key ); ?>"
								novalidate>
							<?php wp_nonce_field( 'cecomwishfw_admin_save', '_cecomwishfw_nonce' ); ?>
							<input type="hidden" name="tab" value="<?php echo esc_attr( $tab_key ); ?>">

							<?php include __DIR__ . '/tab-' . $tab_key . '.php'; ?>

							<div class="sticky-bottom col-12 mb-3 shadow-sm rounded-4 p-3 p-sm-4 border border-light-subtle bg-white d-flex flex-wrap align-items-center gap-2">
								<button type="submit" class="btn btn-primary rounded-pill px-4 d-inline-flex align-items-center gap-2 cecomwishfw-save-btn">
									<i class="bi bi-floppy" aria-hidden="true"></i>
									<?php esc_html_e( 'Save Settings', 'cecom-wishlist-for-woocommerce' ); ?>
								</button>
								<button type="button" class="btn btn-outline-danger rounded-pill px-4 d-inline-flex align-items-center gap-2 cecomwishfw-reset-all-btn">
									<i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
									<?php esc_html_e( 'Reset Defaults', 'cecom-wishlist-for-woocommerce' ); ?>
								</button>
							</div>
						</form>

					<?php else : ?>
						<?php include __DIR__ . '/tab-' . $tab_key . '.php'; ?>
					<?php endif; ?>

				</div><!-- /.tab-panel -->
			<?php endforeach; ?>

		</main>

	</div><!-- .d-flex layout -->

</div><!-- #pluginWrap -->
