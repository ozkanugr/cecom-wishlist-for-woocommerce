<?php
/**
 * Dashboard tab shell (FREE edition).
 *
 * Sub-tab pill nav + four sub-panels:
 *   - overview            → dashboard/tab-overview.php             (functional, free)
 *   - wishlist-analytics  → dashboard/tab-wishlist-analytics-locked.php
 *   - products-analytics  → dashboard/tab-products-analytics-locked.php
 *   - email-analytics     → dashboard/tab-email-analytics-locked.php
 *
 * The three analytics sub-tabs show a Frosted Glass Overlay locked stub
 * (cecom-plugin-admin-ui-framework Design #2) — purely frontend UI, no
 * premium backend code. Per ADR-006.
 *
 * @package Cecomwishfw
 */

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template file.

$active_sub_tab = sanitize_key( wp_unslash( $_GET['sub'] ?? 'overview' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$allowed_subs   = array( 'overview', 'wishlist-analytics', 'products-analytics', 'email-analytics' );
if ( ! in_array( $active_sub_tab, $allowed_subs, true ) ) {
	$active_sub_tab = 'overview';
}

$sub_tabs = array(
	'overview'           => array(
		'label'   => __( 'Overview', 'cecom-wishlist-for-woocommerce' ),
		'icon'    => 'bi-speedometer2',
		'partial' => 'tab-overview.php',
		'locked'  => false,
	),
	'wishlist-analytics' => array(
		'label'   => __( 'Wishlist Analytics', 'cecom-wishlist-for-woocommerce' ),
		'icon'    => 'bi-list-ul',
		'partial' => 'tab-wishlist-analytics-locked.php',
		'locked'  => true,
	),
	'products-analytics' => array(
		'label'   => __( 'Products Analytics', 'cecom-wishlist-for-woocommerce' ),
		'icon'    => 'bi-bar-chart-line',
		'partial' => 'tab-products-analytics-locked.php',
		'locked'  => true,
	),
	'email-analytics'    => array(
		'label'   => __( 'Email Analytics', 'cecom-wishlist-for-woocommerce' ),
		'icon'    => 'bi-envelope-at',
		'partial' => 'tab-email-analytics-locked.php',
		'locked'  => true,
	),
);

$base_url = admin_url( 'admin.php?page=cecomwishfw-settings&tab=dashboard' );
?>

<div class="cwfw-dashboard">

	<?php /* ── Header strip ─────────────────────────────────────────────── */ ?>
	<div class="col-12 mb-3 shadow-sm rounded-4 p-3 p-sm-4 border border-light-subtle bg-white">
		<h2 class="h5 fw-semibold text-body-emphasis mb-1">
			<i class="bi bi-speedometer2 me-1" aria-hidden="true"></i>
			<?php esc_html_e( 'Dashboard', 'cecom-wishlist-for-woocommerce' ); ?>
		</h2>
		<div class="small text-muted">
			<?php esc_html_e( 'Wishlist overview and aggregated metrics.', 'cecom-wishlist-for-woocommerce' ); ?>
		</div>
	</div>

	<?php /* ── Sub-tab pill nav ─────────────────────────────────────────── */ ?>
	<ul class="nav nav-pills cwfw-subtabs mb-3 gap-1" role="tablist">
		<?php foreach ( $sub_tabs as $sub_key => $sub_cfg ) : ?>
			<?php $is_active = ( $sub_key === $active_sub_tab ); ?>
			<li class="nav-item" role="presentation">
				<a href="<?php echo esc_url( add_query_arg( 'sub', $sub_key, $base_url ) ); ?>"
					class="nav-link d-inline-flex align-items-center gap-2<?php echo $is_active ? ' active' : ''; ?>"
					data-cwfw-subtab="<?php echo esc_attr( $sub_key ); ?>"
					role="tab"
					aria-controls="cwfw-subpanel-<?php echo esc_attr( $sub_key ); ?>"
					aria-selected="<?php echo esc_attr( $is_active ? 'true' : 'false' ); ?>">
					<i class="bi <?php echo esc_attr( $sub_cfg['icon'] ); ?>" aria-hidden="true"></i>
					<span><?php echo esc_html( $sub_cfg['label'] ); ?></span>
					<?php if ( $sub_cfg['locked'] ) : ?>
						<i class="bi bi-lock-fill text-warning small" aria-hidden="true" title="<?php esc_attr_e( 'Premium feature', 'cecom-wishlist-for-woocommerce' ); ?>"></i>
					<?php endif; ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>

	<?php /* ── Sub-panels — all rendered, inactive ones .d-none ─────────── */ ?>
	<?php foreach ( $sub_tabs as $sub_key => $sub_cfg ) : ?>
		<?php $is_active = ( $sub_key === $active_sub_tab ); ?>
		<div class="cwfw-subpanel<?php echo $is_active ? '' : ' d-none'; ?>"
			id="cwfw-subpanel-<?php echo esc_attr( $sub_key ); ?>"
			data-cwfw-subpanel="<?php echo esc_attr( $sub_key ); ?>"
			role="tabpanel">
			<?php include __DIR__ . '/dashboard/' . $sub_cfg['partial']; ?>
		</div>
	<?php endforeach; ?>

</div>

<?php /* ── Sub-tab switcher (free plugin ships no dedicated dashboard JS) ── */ ?>
<script>
( function () {
	'use strict';
	var wrap = document.querySelector( '.cwfw-dashboard' );
	if ( ! wrap ) { return; }
	var links  = wrap.querySelectorAll( '[data-cwfw-subtab]' );
	var panels = wrap.querySelectorAll( '.cwfw-subpanel' );
	function activate( key, updateUrl ) {
		for ( var i = 0; i < panels.length; i++ ) {
			var p = panels[ i ];
			if ( p.getAttribute( 'data-cwfw-subpanel' ) === key ) {
				p.classList.remove( 'd-none' );
			} else {
				p.classList.add( 'd-none' );
			}
		}
		for ( var j = 0; j < links.length; j++ ) {
			var a  = links[ j ];
			var on = ( a.getAttribute( 'data-cwfw-subtab' ) === key );
			a.classList.toggle( 'active', on );
			a.setAttribute( 'aria-selected', on ? 'true' : 'false' );
		}
		if ( updateUrl ) {
			var u = new URL( window.location.href );
			u.searchParams.set( 'sub', key );
			window.history.pushState( {}, '', u.toString() );
		}
	}
	for ( var i = 0; i < links.length; i++ ) {
		links[ i ].addEventListener( 'click', function ( e ) {
			if ( e.ctrlKey || e.metaKey || e.shiftKey ) { return; }
			e.preventDefault();
			activate( this.getAttribute( 'data-cwfw-subtab' ), true );
		} );
	}
} )();
</script>
<?php
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
