<?php
/**
 * Wishlist Analytics — locked stub (FREE edition).
 *
 * Frosted Glass Overlay (Design #2) behind a blurred list-analytics preview.
 * Free plugin has no premium backend code; this file is purely frontend.
 *
 * @package Cecomwishfw
 */

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template partial.

$upgrade_url = 'https://cecom.in/wishlist-for-woocommerce-annual-premium/';
?>

<div class="col-12 mb-3 shadow-sm rounded-4 border border-light-subtle bg-white position-relative overflow-hidden" style="min-height:420px;">

	<?php /* Blurred preview: fake analytics panel */ ?>
	<div class="p-4 user-select-none" style="filter:blur(3px); pointer-events:none;">
		<div class="row row-cols-2 row-cols-md-4 g-3 mb-3">
			<?php for ( $i = 0; $i < 4; $i++ ) : ?>
				<div class="col">
					<div class="card p-3 bg-light border-light-subtle">
						<div class="fs-3 fw-bold">—</div>
						<div class="small text-muted text-uppercase">Metric <?php echo esc_html( (string) ( $i + 1 ) ); ?></div>
					</div>
				</div>
			<?php endfor; ?>
		</div>
		<div class="d-flex align-items-end gap-2" style="height:120px;">
			<?php $bars = array( 60, 85, 45, 92, 70, 55, 78, 40, 65, 88 ); foreach ( $bars as $h ) : ?>
				<div class="bg-primary-subtle rounded-top" style="width:36px;height:<?php echo esc_attr( (string) $h ); ?>%;"></div>
			<?php endforeach; ?>
		</div>
	</div>

	<?php /* Frosted glass overlay */ ?>
	<div class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center text-center px-4 rounded-4"
		style="background:rgba(255,255,255,.72);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);">
		<i class="bi bi-shield-lock-fill text-primary mb-2" style="font-size:2.4rem;" aria-hidden="true"></i>
		<p class="fw-bold text-body-emphasis mb-1 fs-5">
			<?php esc_html_e( 'Wishlist Analytics — Pro', 'cecom-wishlist-for-woocommerce' ); ?>
		</p>
		<p class="text-secondary small mb-3" style="max-width:460px;">
			<?php esc_html_e( 'Privacy breakdown, views, shares, CTR, abandonment, and a customer browser with per-user wishlists.', 'cecom-wishlist-for-woocommerce' ); ?>
		</p>
		<a href="<?php echo esc_url( $upgrade_url ); ?>"
			target="_blank"
			rel="noopener noreferrer"
			class="btn btn-warning rounded-pill px-4 d-inline-flex align-items-center gap-2">
			<i class="bi bi-stars" aria-hidden="true"></i>
			<?php esc_html_e( 'Upgrade to Pro', 'cecom-wishlist-for-woocommerce' ); ?>
		</a>
	</div>
</div>
<?php
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
