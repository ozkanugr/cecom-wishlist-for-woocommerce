<?php
/**
 * Premium stub tabs — Multiple Lists, Email Campaigns, Upgrade.
 *
 * Frontend-only locked designs. Zero backend premium code anywhere in this
 * file (per ADR-006 — free plugin must never include premium logic).
 *
 * Locked stubs use the cecom-plugin-admin-ui-framework "Frosted Glass Overlay"
 * pattern (Design #2): blurred placeholder preview behind a semi-transparent
 * white frosted overlay with a shield-lock icon, heading, and warning-colored
 * upgrade CTA.
 *
 * The upgrade tab uses flat framework panels for the pricing summary and
 * free-vs-premium feature comparison.
 *
 * Variables from settings.php:
 *
 *   @var string $stub_tab  One of: 'multiple_lists' | 'email_campaigns' | 'upgrade'
 *
 * @package Cecomwishfw
 */

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template file; variables are passed from the controller, not global

$upgrade_url = 'https://cecom.in/wishlist-for-woocommerce-annual-premium/';
?>

<?php if ( 'multiple_lists' === $stub_tab ) : ?>

	<?php /* ── Multiple Lists: Frosted Glass Overlay ───────────────────── */ ?>
	<div class="col-12 mb-3 shadow-sm rounded-4 border border-light-subtle bg-white position-relative overflow-hidden" style="min-height:420px;">

		<?php /* Blurred preview: fake list-group showing multiple named wishlists */ ?>
		<div class="p-4 user-select-none" style="filter:blur(3px); pointer-events:none;">
			<div class="list-group list-group-flush">
				<div class="list-group-item d-flex align-items-center gap-3 py-3">
					<i class="bi bi-heart-fill text-primary fs-4" aria-hidden="true"></i>
					<div class="flex-fill">
						<div class="fw-semibold">Birthday Wishlist</div>
						<div class="small text-muted">12 items &middot; shared</div>
					</div>
					<span class="badge bg-primary-subtle text-primary-emphasis rounded-pill">Default</span>
				</div>
				<div class="list-group-item d-flex align-items-center gap-3 py-3">
					<i class="bi bi-gift-fill text-primary fs-4" aria-hidden="true"></i>
					<div class="flex-fill">
						<div class="fw-semibold">Holiday Gifts</div>
						<div class="small text-muted">8 items &middot; private</div>
					</div>
				</div>
				<div class="list-group-item d-flex align-items-center gap-3 py-3">
					<i class="bi bi-bag-heart-fill text-primary fs-4" aria-hidden="true"></i>
					<div class="flex-fill">
						<div class="fw-semibold">Save for Later</div>
						<div class="small text-muted">24 items &middot; private</div>
					</div>
				</div>
				<div class="list-group-item d-flex align-items-center gap-3 py-3">
					<i class="bi bi-house-heart-fill text-primary fs-4" aria-hidden="true"></i>
					<div class="flex-fill">
						<div class="fw-semibold">Home Renovation</div>
						<div class="small text-muted">6 items &middot; shared</div>
					</div>
				</div>
			</div>
		</div>

		<?php /* Frosted glass overlay */ ?>
		<div class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center text-center px-4 rounded-4"
			style="background:rgba(255,255,255,.72);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);">
			<i class="bi bi-shield-lock-fill text-primary mb-2" style="font-size:2.4rem;" aria-hidden="true"></i>
			<p class="fw-bold text-body-emphasis mb-1 fs-5">
				<?php esc_html_e( 'Multiple Wishlists — Pro', 'cecom-wishlist-for-woocommerce' ); ?>
			</p>
			<p class="text-secondary small mb-3" style="max-width:460px;">
				<?php esc_html_e( 'Let customers organise products into unlimited named wishlists, share them via unique links, and set a default for one-click saves.', 'cecom-wishlist-for-woocommerce' ); ?>
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

<?php elseif ( 'email_campaigns' === $stub_tab ) : ?>

	<?php /* ── Email Campaigns: Frosted Glass Overlay ───────────────────── */ ?>
	<div class="col-12 mb-3 shadow-sm rounded-4 border border-light-subtle bg-white position-relative overflow-hidden" style="min-height:420px;">

		<?php /* Blurred preview: fake email campaign list */ ?>
		<div class="p-4 user-select-none" style="filter:blur(3px); pointer-events:none;">
			<div class="list-group list-group-flush">
				<div class="list-group-item d-flex align-items-start gap-3 py-3">
					<i class="bi bi-envelope-check-fill text-success fs-4" aria-hidden="true"></i>
					<div class="flex-fill">
						<div class="fw-semibold">Back in stock: Vintage Leather Bag</div>
						<div class="small text-muted">Sent to 142 customers &middot; 36% open rate</div>
					</div>
					<span class="badge bg-success-subtle text-success-emphasis rounded-pill">Delivered</span>
				</div>
				<div class="list-group-item d-flex align-items-start gap-3 py-3">
					<i class="bi bi-tag-fill text-warning fs-4" aria-hidden="true"></i>
					<div class="flex-fill">
						<div class="fw-semibold">Price drop: Summer Collection (-20%)</div>
						<div class="small text-muted">Sent to 287 customers &middot; 52% open rate</div>
					</div>
					<span class="badge bg-success-subtle text-success-emphasis rounded-pill">Delivered</span>
				</div>
				<div class="list-group-item d-flex align-items-start gap-3 py-3">
					<i class="bi bi-clock-history text-primary fs-4" aria-hidden="true"></i>
					<div class="flex-fill">
						<div class="fw-semibold">Wishlist reminder (7-day drip)</div>
						<div class="small text-muted">Scheduled &middot; queue: 58 customers</div>
					</div>
					<span class="badge bg-primary-subtle text-primary-emphasis rounded-pill">Scheduled</span>
				</div>
				<div class="list-group-item d-flex align-items-start gap-3 py-3">
					<i class="bi bi-envelope-paper-heart-fill text-primary fs-4" aria-hidden="true"></i>
					<div class="flex-fill">
						<div class="fw-semibold">Abandoned wishlist nudge</div>
						<div class="small text-muted">Automated &middot; ongoing</div>
					</div>
					<span class="badge bg-primary-subtle text-primary-emphasis rounded-pill">Active</span>
				</div>
			</div>
		</div>

		<?php /* Frosted glass overlay */ ?>
		<div class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center text-center px-4 rounded-4"
			style="background:rgba(255,255,255,.72);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);">
			<i class="bi bi-shield-lock-fill text-primary mb-2" style="font-size:2.4rem;" aria-hidden="true"></i>
			<p class="fw-bold text-body-emphasis mb-1 fs-5">
				<?php esc_html_e( 'Email Campaigns — Pro', 'cecom-wishlist-for-woocommerce' ); ?>
			</p>
			<p class="text-secondary small mb-3" style="max-width:460px;">
				<?php esc_html_e( 'Automated back-in-stock alerts, price-drop notifications, and drip reminder campaigns that convert warm leads into sales.', 'cecom-wishlist-for-woocommerce' ); ?>
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

<?php elseif ( 'upgrade' === $stub_tab ) : ?>

	<?php /* ── Upgrade: hero + feature cards + CTA (framework pattern) ────── */ ?>
	<div class="col-12 mb-3 shadow-sm rounded-4 p-3 p-sm-4 border border-light-subtle bg-white">

		<?php /* Hero */ ?>
		<div class="text-center py-4 py-md-5">
			<div class="mb-3">
				<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-3 py-2 fs-6">
					<i class="bi bi-stars me-1" aria-hidden="true"></i>
					<?php esc_html_e( 'Premium', 'cecom-wishlist-for-woocommerce' ); ?>
				</span>
			</div>
			<h2 class="display-6 fw-bold text-body-emphasis mb-2">
				<?php esc_html_e( 'Unlock the Full Power of Wishlists', 'cecom-wishlist-for-woocommerce' ); ?>
			</h2>
			<p class="lead text-muted col-12 col-sm-10 col-md-8 mx-auto mb-4">
				<?php esc_html_e( 'Get multiple wishlists, automated email campaigns, full analytics, and priority support with a single upgrade.', 'cecom-wishlist-for-woocommerce' ); ?>
			</p>
		</div>

		<?php /* Feature cards grid */ ?>
		<div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3 mb-4">

			<div class="col">
				<div class="d-flex align-items-start gap-3 p-3 rounded-3 bg-body-tertiary h-100">
					<i class="bi bi-collection-fill text-primary fs-4 flex-shrink-0" aria-hidden="true"></i>
					<div>
						<h6 class="fw-semibold mb-1"><?php esc_html_e( 'Multiple Named Wishlists', 'cecom-wishlist-for-woocommerce' ); ?></h6>
						<p class="small text-muted mb-0"><?php esc_html_e( 'Let customers create unlimited named wishlists, share them via unique links, and set a default for one-click saves.', 'cecom-wishlist-for-woocommerce' ); ?></p>
					</div>
				</div>
			</div>

			<div class="col">
				<div class="d-flex align-items-start gap-3 p-3 rounded-3 bg-body-tertiary h-100">
					<i class="bi bi-bell-fill text-primary fs-4 flex-shrink-0" aria-hidden="true"></i>
					<div>
						<h6 class="fw-semibold mb-1"><?php esc_html_e( 'Back-in-Stock Alerts', 'cecom-wishlist-for-woocommerce' ); ?></h6>
						<p class="small text-muted mb-0"><?php esc_html_e( 'Automatically email customers the moment a wishlisted product comes back in stock.', 'cecom-wishlist-for-woocommerce' ); ?></p>
					</div>
				</div>
			</div>

			<div class="col">
				<div class="d-flex align-items-start gap-3 p-3 rounded-3 bg-body-tertiary h-100">
					<i class="bi bi-tags-fill text-primary fs-4 flex-shrink-0" aria-hidden="true"></i>
					<div>
						<h6 class="fw-semibold mb-1"><?php esc_html_e( 'Price-Drop Notifications', 'cecom-wishlist-for-woocommerce' ); ?></h6>
						<p class="small text-muted mb-0"><?php esc_html_e( 'Notify shoppers instantly when a wishlisted item drops in price, driving conversions at the right moment.', 'cecom-wishlist-for-woocommerce' ); ?></p>
					</div>
				</div>
			</div>

			<div class="col">
				<div class="d-flex align-items-start gap-3 p-3 rounded-3 bg-body-tertiary h-100">
					<i class="bi bi-envelope-at-fill text-primary fs-4 flex-shrink-0" aria-hidden="true"></i>
					<div>
						<h6 class="fw-semibold mb-1"><?php esc_html_e( 'Drip Email Campaigns', 'cecom-wishlist-for-woocommerce' ); ?></h6>
						<p class="small text-muted mb-0"><?php esc_html_e( 'Automated wishlist-reminder sequences that convert warm leads into sales without manual effort.', 'cecom-wishlist-for-woocommerce' ); ?></p>
					</div>
				</div>
			</div>

			<div class="col">
				<div class="d-flex align-items-start gap-3 p-3 rounded-3 bg-body-tertiary h-100">
					<i class="bi bi-bar-chart-line-fill text-primary fs-4 flex-shrink-0" aria-hidden="true"></i>
					<div>
						<h6 class="fw-semibold mb-1"><?php esc_html_e( 'Full Analytics Dashboard', 'cecom-wishlist-for-woocommerce' ); ?></h6>
						<p class="small text-muted mb-0"><?php esc_html_e( 'Date-range filters, customer-level wishlist data, and campaign history all in one place.', 'cecom-wishlist-for-woocommerce' ); ?></p>
					</div>
				</div>
			</div>

			<div class="col">
				<div class="d-flex align-items-start gap-3 p-3 rounded-3 bg-body-tertiary h-100">
					<i class="bi bi-headset text-primary fs-4 flex-shrink-0" aria-hidden="true"></i>
					<div>
						<h6 class="fw-semibold mb-1"><?php esc_html_e( 'Priority Support', 'cecom-wishlist-for-woocommerce' ); ?></h6>
						<p class="small text-muted mb-0"><?php esc_html_e( 'Dedicated support channel with faster response times — get help when you need it.', 'cecom-wishlist-for-woocommerce' ); ?></p>
					</div>
				</div>
			</div>

		</div>

		<?php /* CTA */ ?>
		<div class="text-center py-4 border-top">
			<p class="text-muted small mb-3">
				<?php esc_html_e( 'One-time purchase. Lifetime updates. 14-day money-back guarantee.', 'cecom-wishlist-for-woocommerce' ); ?>
			</p>
			<a href="<?php echo esc_url( $upgrade_url ); ?>"
				target="_blank"
				rel="noopener noreferrer"
				class="btn btn-warning btn-lg rounded-pill px-5 fw-semibold">
				<i class="bi bi-stars me-2" aria-hidden="true"></i>
				<?php esc_html_e( 'Get Premium', 'cecom-wishlist-for-woocommerce' ); ?>
			</a>
		</div>
	</div>

	<?php /* ── Free vs Premium comparison panel ─────────────────────────── */ ?>
	<div class="col-12 mb-3 shadow-sm rounded-4 p-3 p-sm-4 border border-light-subtle bg-white">
		<h2 class="h6 fw-semibold text-body-emphasis mb-3">
			<?php esc_html_e( 'Free vs Premium', 'cecom-wishlist-for-woocommerce' ); ?>
		</h2>

		<div class="table-responsive">
			<table class="table mb-0 align-middle">
				<thead class="table-light">
					<tr>
						<th><?php esc_html_e( 'Feature', 'cecom-wishlist-for-woocommerce' ); ?></th>
						<th class="text-center" style="width:120px;"><?php esc_html_e( 'Free', 'cecom-wishlist-for-woocommerce' ); ?></th>
						<th class="text-center" style="width:120px;"><?php esc_html_e( 'Premium', 'cecom-wishlist-for-woocommerce' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					$features = array(
						array( __( 'Add to Wishlist button', 'cecom-wishlist-for-woocommerce' ), true, true ),
						array( __( 'Guest wishlists (no login needed)', 'cecom-wishlist-for-woocommerce' ), true, true ),
						array( __( 'Shop loop button', 'cecom-wishlist-for-woocommerce' ), true, true ),
						array( __( 'Gutenberg block', 'cecom-wishlist-for-woocommerce' ), true, true ),
						array( __( 'Social sharing', 'cecom-wishlist-for-woocommerce' ), true, true ),
						array( __( 'Basic analytics (top 5)', 'cecom-wishlist-for-woocommerce' ), true, true ),
						array( __( 'Multiple named wishlists', 'cecom-wishlist-for-woocommerce' ), false, true ),
						array( __( 'Back-in-stock email alerts', 'cecom-wishlist-for-woocommerce' ), false, true ),
						array( __( 'Price-drop notifications', 'cecom-wishlist-for-woocommerce' ), false, true ),
						array( __( 'Drip campaigns', 'cecom-wishlist-for-woocommerce' ), false, true ),
						array( __( 'Full analytics dashboard', 'cecom-wishlist-for-woocommerce' ), false, true ),
						array( __( 'Priority support', 'cecom-wishlist-for-woocommerce' ), false, true ),
					);
					foreach ( $features as $feature ) :
						[ $label, $in_free, $in_premium ] = $feature;
						?>
						<tr>
							<td><?php echo esc_html( $label ); ?></td>
							<td class="text-center">
								<?php if ( $in_free ) : ?>
									<i class="bi bi-check-circle-fill text-success" aria-label="<?php esc_attr_e( 'Included', 'cecom-wishlist-for-woocommerce' ); ?>"></i>
								<?php else : ?>
									<i class="bi bi-x-circle text-secondary opacity-50" aria-label="<?php esc_attr_e( 'Not included', 'cecom-wishlist-for-woocommerce' ); ?>"></i>
								<?php endif; ?>
							</td>
							<td class="text-center">
								<i class="bi bi-check-circle-fill text-success" aria-label="<?php esc_attr_e( 'Included', 'cecom-wishlist-for-woocommerce' ); ?>"></i>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>

<?php endif; ?>
