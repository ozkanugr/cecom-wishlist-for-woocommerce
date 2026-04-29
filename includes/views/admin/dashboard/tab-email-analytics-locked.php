<?php
/**
 * Email Analytics — locked stub (FREE edition).
 *
 * @package Cecomwishfw
 */

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template partial.

$upgrade_url = 'https://cecom.in/wishlist-for-woocommerce-annual-premium/';
?>

<div class="col-12 mb-3 shadow-sm rounded-4 border border-light-subtle bg-white position-relative overflow-hidden" style="min-height:420px;">

	<div class="p-4 user-select-none" style="filter:blur(3px); pointer-events:none;">
		<div class="row row-cols-2 row-cols-md-5 g-2 mb-3">
			<?php
			$funnel = array(
				array( 'bi-send', 'Sent', '1,248' ),
				array( 'bi-envelope-open', 'Opened', '612' ),
				array( 'bi-cursor', 'Clicked', '184' ),
				array( 'bi-bag-check', 'Converted', '42' ),
				array( 'bi-cash-coin', 'Revenue', '$3,860' ),
			);
			foreach ( $funnel as $f ) : ?>
				<div class="col">
					<div class="card text-center p-3 bg-light border-light-subtle">
						<div class="small text-muted text-uppercase">
							<i class="bi <?php echo esc_attr( $f[0] ); ?>" aria-hidden="true"></i>
							<?php echo esc_html( $f[1] ); ?>
						</div>
						<div class="fs-3 fw-bold"><?php echo esc_html( $f[2] ); ?></div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<div class="d-flex align-items-end gap-2" style="height:110px;">
			<?php $bars = array( 70, 55, 80, 45, 90, 60, 75, 50 ); foreach ( $bars as $h ) : ?>
				<div class="bg-primary-subtle rounded-top flex-fill" style="height:<?php echo esc_attr( (string) $h ); ?>%;"></div>
			<?php endforeach; ?>
		</div>
	</div>

	<div class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center text-center px-4 rounded-4"
		style="background:rgba(255,255,255,.72);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);">
		<i class="bi bi-shield-lock-fill text-primary mb-2" style="font-size:2.4rem;" aria-hidden="true"></i>
		<p class="fw-bold text-body-emphasis mb-1 fs-5">
			<?php esc_html_e( 'Email Analytics — Pro', 'cecom-wishlist-for-woocommerce' ); ?>
		</p>
		<p class="text-secondary small mb-3" style="max-width:460px;">
			<?php esc_html_e( 'Full email funnel: sent, opened, clicked, converted, revenue — per user, per campaign, with attribution.', 'cecom-wishlist-for-woocommerce' ); ?>
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
