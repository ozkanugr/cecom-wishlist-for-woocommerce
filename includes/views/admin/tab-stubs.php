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
 *   @var string $stub_tab  One of: 'multiple_lists' | 'email' | 'email_template' | 'email_campaigns' | 'customer_wishlists' | 'quotes' | 'upgrade'
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

<?php elseif ( 'email' === $stub_tab ) : ?>

	<?php /* ── Email Settings: Frosted Glass Overlay ────────────────────── */ ?>
	<div class="col-12 mb-3 shadow-sm rounded-4 border border-light-subtle bg-white position-relative overflow-hidden" style="min-height:420px;">

		<?php /* Blurred preview: fake notification toggles + subject line inputs */ ?>
		<div class="p-4 user-select-none" style="filter:blur(3px); pointer-events:none;">
			<div class="mb-4">
				<div class="fw-semibold text-body-emphasis mb-3"><?php esc_html_e( 'Notification Triggers', 'cecom-wishlist-for-woocommerce' ); ?></div>
				<div class="d-flex align-items-center gap-3 mb-3">
					<div class="form-check form-switch mb-0">
						<input class="form-check-input" type="checkbox" checked disabled>
						<label class="form-check-label"><?php esc_html_e( 'Price drop notifications', 'cecom-wishlist-for-woocommerce' ); ?></label>
					</div>
				</div>
				<div class="d-flex align-items-center gap-3">
					<div class="form-check form-switch mb-0">
						<input class="form-check-input" type="checkbox" checked disabled>
						<label class="form-check-label"><?php esc_html_e( 'Back in stock notifications', 'cecom-wishlist-for-woocommerce' ); ?></label>
					</div>
				</div>
			</div>
			<div>
				<div class="fw-semibold text-body-emphasis mb-3"><?php esc_html_e( 'Subject Lines', 'cecom-wishlist-for-woocommerce' ); ?></div>
				<div class="mb-2">
					<label class="form-label small text-muted"><?php esc_html_e( 'Price Drop Subject', 'cecom-wishlist-for-woocommerce' ); ?></label>
					<input type="text" class="form-control form-control-sm" value="Price drop on {product_name} in your wishlist!" disabled style="max-width:380px;">
				</div>
				<div>
					<label class="form-label small text-muted"><?php esc_html_e( 'Back in Stock Subject', 'cecom-wishlist-for-woocommerce' ); ?></label>
					<input type="text" class="form-control form-control-sm" value="{product_name} is back in stock!" disabled style="max-width:380px;">
				</div>
			</div>
		</div>

		<?php /* Frosted glass overlay */ ?>
		<div class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center text-center px-4 rounded-4"
			style="background:rgba(255,255,255,.72);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);">
			<i class="bi bi-shield-lock-fill text-primary mb-2" style="font-size:2.4rem;" aria-hidden="true"></i>
			<p class="fw-bold text-body-emphasis mb-1 fs-5">
				<?php esc_html_e( 'Email Settings — Pro', 'cecom-wishlist-for-woocommerce' ); ?>
			</p>
			<p class="text-secondary small mb-3" style="max-width:460px;">
				<?php esc_html_e( 'Configure automated notification triggers — price-drop and back-in-stock alerts sent directly to customers who wishlisted a product.', 'cecom-wishlist-for-woocommerce' ); ?>
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

<?php elseif ( 'email_template' === $stub_tab ) : ?>

	<?php /* ── Email Template: Frosted Glass Overlay ────────────────────── */ ?>
	<div class="col-12 mb-3 shadow-sm rounded-4 border border-light-subtle bg-white position-relative overflow-hidden" style="min-height:420px;">

		<?php /* Blurred preview: fake template builder fields */ ?>
		<div class="p-4 user-select-none" style="filter:blur(3px); pointer-events:none;">
			<div class="mb-4">
				<div class="fw-semibold text-body-emphasis mb-3"><?php esc_html_e( 'Sender Configuration', 'cecom-wishlist-for-woocommerce' ); ?></div>
				<div class="d-flex align-items-center gap-3 mb-3">
					<div class="rounded-3 border border-light-subtle bg-body-tertiary d-flex align-items-center justify-content-center" style="width:72px;height:72px;">
						<i class="bi bi-image text-muted fs-3" aria-hidden="true"></i>
					</div>
					<div>
						<div class="small text-muted mb-1"><?php esc_html_e( 'Logo', 'cecom-wishlist-for-woocommerce' ); ?></div>
						<div class="btn btn-sm btn-outline-secondary disabled"><?php esc_html_e( 'Upload', 'cecom-wishlist-for-woocommerce' ); ?></div>
					</div>
				</div>
				<div class="d-flex align-items-center gap-2 mb-2">
					<label class="form-label small text-muted mb-0" style="width:140px;"><?php esc_html_e( 'Brand Colour', 'cecom-wishlist-for-woocommerce' ); ?></label>
					<span class="rounded-2 border" style="display:inline-block;width:28px;height:28px;background:#e74c3c;"></span>
					<span class="small text-muted">#e74c3c</span>
				</div>
				<div class="mb-2">
					<label class="form-label small text-muted"><?php esc_html_e( 'From Name', 'cecom-wishlist-for-woocommerce' ); ?></label>
					<input type="text" class="form-control form-control-sm" value="My Store" disabled style="max-width:280px;">
				</div>
				<div>
					<label class="form-label small text-muted"><?php esc_html_e( 'From Email', 'cecom-wishlist-for-woocommerce' ); ?></label>
					<input type="text" class="form-control form-control-sm" value="hello@mystore.com" disabled style="max-width:280px;">
				</div>
			</div>
		</div>

		<?php /* Frosted glass overlay */ ?>
		<div class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center text-center px-4 rounded-4"
			style="background:rgba(255,255,255,.72);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);">
			<i class="bi bi-shield-lock-fill text-primary mb-2" style="font-size:2.4rem;" aria-hidden="true"></i>
			<p class="fw-bold text-body-emphasis mb-1 fs-5">
				<?php esc_html_e( 'Email Template — Pro', 'cecom-wishlist-for-woocommerce' ); ?>
			</p>
			<p class="text-secondary small mb-3" style="max-width:460px;">
				<?php esc_html_e( 'Design your notification emails: upload a logo, set your brand colour, customise the greeting and footer text, and preview before sending.', 'cecom-wishlist-for-woocommerce' ); ?>
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

<?php elseif ( 'customer_wishlists' === $stub_tab ) : ?>

	<?php /* ── Customer Wishlists: Frosted Glass Overlay ─────────────────── */ ?>
	<div class="col-12 mb-3 shadow-sm rounded-4 border border-light-subtle bg-white position-relative overflow-hidden" style="min-height:420px;">

		<?php /* Blurred preview: fake customer table */ ?>
		<div class="p-4 user-select-none" style="filter:blur(3px); pointer-events:none;">
			<div class="mb-3 d-flex align-items-center gap-2">
				<input type="search" class="form-control form-control-sm" placeholder="Search by name or email…" disabled style="max-width:260px;">
			</div>
			<div class="table-responsive">
				<table class="table table-sm align-middle mb-0">
					<thead class="table-light">
						<tr>
							<th><?php esc_html_e( 'Customer', 'cecom-wishlist-for-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Email', 'cecom-wishlist-for-woocommerce' ); ?></th>
							<th class="text-center"><?php esc_html_e( 'Lists', 'cecom-wishlist-for-woocommerce' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						$fake_customers = array(
							array( 'S', 'Sarah Johnson', 'sarah@example.com', 3 ),
							array( 'M', 'Mike Thompson', 'mike@example.com', 1 ),
							array( 'E', 'Emma Williams', 'emma@example.com', 5 ),
							array( 'J', 'James Brown', 'james@example.com', 2 ),
						);
						foreach ( $fake_customers as $c ) :
							?>
							<tr>
								<td>
									<div class="d-flex align-items-center gap-2">
										<span class="rounded-circle bg-primary-subtle text-primary-emphasis d-inline-flex align-items-center justify-content-center fw-semibold" style="width:32px;height:32px;font-size:.8rem;">
											<?php echo esc_html( $c[0] ); ?>
										</span>
										<span class="fw-medium"><?php echo esc_html( $c[1] ); ?></span>
									</div>
								</td>
								<td class="text-muted small"><?php echo esc_html( $c[2] ); ?></td>
								<td class="text-center">
									<span class="badge bg-primary-subtle text-primary-emphasis rounded-pill"><?php echo esc_html( $c[3] ); ?></span>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>

		<?php /* Frosted glass overlay */ ?>
		<div class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center text-center px-4 rounded-4"
			style="background:rgba(255,255,255,.72);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);">
			<i class="bi bi-shield-lock-fill text-primary mb-2" style="font-size:2.4rem;" aria-hidden="true"></i>
			<p class="fw-bold text-body-emphasis mb-1 fs-5">
				<?php esc_html_e( 'Customer Wishlists — Pro', 'cecom-wishlist-for-woocommerce' ); ?>
			</p>
			<p class="text-secondary small mb-3" style="max-width:460px;">
				<?php esc_html_e( 'Browse every customer\'s wishlists from one dashboard — search by name or email, then drill down to inspect individual lists and items.', 'cecom-wishlist-for-woocommerce' ); ?>
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

<?php elseif ( 'quotes' === $stub_tab ) : ?>

	<?php /* ── Quote Requests: Frosted Glass Overlay ────────────────────── */ ?>
	<div class="col-12 mb-3 shadow-sm rounded-4 border border-light-subtle bg-white position-relative overflow-hidden" style="min-height:420px;">

		<?php /* Blurred preview: status pills + fake quote table */ ?>
		<div class="p-4 user-select-none" style="filter:blur(3px); pointer-events:none;">
			<div class="d-flex gap-2 mb-3">
				<button type="button" class="btn btn-sm btn-primary rounded-pill disabled"><?php esc_html_e( 'All', 'cecom-wishlist-for-woocommerce' ); ?> <span class="badge bg-white text-primary ms-1">9</span></button>
				<button type="button" class="btn btn-sm btn-outline-secondary rounded-pill disabled"><?php esc_html_e( 'New', 'cecom-wishlist-for-woocommerce' ); ?> <span class="badge bg-warning-subtle text-warning-emphasis ms-1">4</span></button>
				<button type="button" class="btn btn-sm btn-outline-secondary rounded-pill disabled"><?php esc_html_e( 'In Progress', 'cecom-wishlist-for-woocommerce' ); ?> <span class="badge bg-primary-subtle text-primary-emphasis ms-1">3</span></button>
				<button type="button" class="btn btn-sm btn-outline-secondary rounded-pill disabled"><?php esc_html_e( 'Resolved', 'cecom-wishlist-for-woocommerce' ); ?> <span class="badge bg-success-subtle text-success-emphasis ms-1">2</span></button>
			</div>
			<div class="table-responsive">
				<table class="table table-sm align-middle mb-0">
					<thead class="table-light">
						<tr>
							<th><?php esc_html_e( 'Date', 'cecom-wishlist-for-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Customer', 'cecom-wishlist-for-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Wishlist', 'cecom-wishlist-for-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Status', 'cecom-wishlist-for-woocommerce' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td class="text-muted small">Apr 15</td>
							<td>Sarah Johnson</td>
							<td class="text-muted small">Birthday Wishlist</td>
							<td><span class="badge bg-warning-subtle text-warning-emphasis rounded-pill"><?php esc_html_e( 'New', 'cecom-wishlist-for-woocommerce' ); ?></span></td>
						</tr>
						<tr>
							<td class="text-muted small">Apr 14</td>
							<td>Mike Thompson</td>
							<td class="text-muted small">Home Renovation</td>
							<td><span class="badge bg-primary-subtle text-primary-emphasis rounded-pill"><?php esc_html_e( 'In Progress', 'cecom-wishlist-for-woocommerce' ); ?></span></td>
						</tr>
						<tr>
							<td class="text-muted small">Apr 12</td>
							<td>Emma Williams</td>
							<td class="text-muted small">Holiday Gifts</td>
							<td><span class="badge bg-warning-subtle text-warning-emphasis rounded-pill"><?php esc_html_e( 'New', 'cecom-wishlist-for-woocommerce' ); ?></span></td>
						</tr>
						<tr>
							<td class="text-muted small">Apr 10</td>
							<td>James Brown</td>
							<td class="text-muted small">Save for Later</td>
							<td><span class="badge bg-success-subtle text-success-emphasis rounded-pill"><?php esc_html_e( 'Resolved', 'cecom-wishlist-for-woocommerce' ); ?></span></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>

		<?php /* Frosted glass overlay */ ?>
		<div class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center text-center px-4 rounded-4"
			style="background:rgba(255,255,255,.72);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);">
			<i class="bi bi-shield-lock-fill text-primary mb-2" style="font-size:2.4rem;" aria-hidden="true"></i>
			<p class="fw-bold text-body-emphasis mb-1 fs-5">
				<?php esc_html_e( 'Quote Requests — Pro', 'cecom-wishlist-for-woocommerce' ); ?>
			</p>
			<p class="text-secondary small mb-3" style="max-width:460px;">
				<?php esc_html_e( 'Manage estimate requests submitted from the wishlist page — review, reply, update status, and convert accepted quotes to WooCommerce orders.', 'cecom-wishlist-for-woocommerce' ); ?>
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
						<h6 class="fw-semibold mb-1"><?php esc_html_e( 'Automated Email Notifications', 'cecom-wishlist-for-woocommerce' ); ?></h6>
						<p class="small text-muted mb-0"><?php esc_html_e( 'Price-drop and back-in-stock alerts sent automatically to every customer who wishlisted the product.', 'cecom-wishlist-for-woocommerce' ); ?></p>
					</div>
				</div>
			</div>

			<div class="col">
				<div class="d-flex align-items-start gap-3 p-3 rounded-3 bg-body-tertiary h-100">
					<i class="bi bi-megaphone-fill text-primary fs-4 flex-shrink-0" aria-hidden="true"></i>
					<div>
						<h6 class="fw-semibold mb-1"><?php esc_html_e( 'Email Campaign Builder', 'cecom-wishlist-for-woocommerce' ); ?></h6>
						<p class="small text-muted mb-0"><?php esc_html_e( 'Select a product, see how many customers wishlisted it, compose a message, and send — WP-Cron handles delivery in batches.', 'cecom-wishlist-for-woocommerce' ); ?></p>
					</div>
				</div>
			</div>

			<div class="col">
				<div class="d-flex align-items-start gap-3 p-3 rounded-3 bg-body-tertiary h-100">
					<i class="bi bi-bar-chart-line-fill text-primary fs-4 flex-shrink-0" aria-hidden="true"></i>
					<div>
						<h6 class="fw-semibold mb-1"><?php esc_html_e( 'Full Analytics Dashboard', 'cecom-wishlist-for-woocommerce' ); ?></h6>
						<p class="small text-muted mb-0"><?php esc_html_e( 'Email funnel (sent → opened → clicked → converted), timeseries chart, by-type breakdown, and top revenue emails — all filterable by date range.', 'cecom-wishlist-for-woocommerce' ); ?></p>
					</div>
				</div>
			</div>

			<div class="col">
				<div class="d-flex align-items-start gap-3 p-3 rounded-3 bg-body-tertiary h-100">
					<i class="bi bi-people-fill text-primary fs-4 flex-shrink-0" aria-hidden="true"></i>
					<div>
						<h6 class="fw-semibold mb-1"><?php esc_html_e( 'Customer Wishlists Browser', 'cecom-wishlist-for-woocommerce' ); ?></h6>
						<p class="small text-muted mb-0"><?php esc_html_e( 'Browse every customer\'s wishlists from the admin — search by name or email and drill down to individual lists and items.', 'cecom-wishlist-for-woocommerce' ); ?></p>
					</div>
				</div>
			</div>

			<div class="col">
				<div class="d-flex align-items-start gap-3 p-3 rounded-3 bg-body-tertiary h-100">
					<i class="bi bi-chat-text-fill text-primary fs-4 flex-shrink-0" aria-hidden="true"></i>
					<div>
						<h6 class="fw-semibold mb-1"><?php esc_html_e( 'Quote Request Form', 'cecom-wishlist-for-woocommerce' ); ?></h6>
						<p class="small text-muted mb-0"><?php esc_html_e( 'Customers submit estimate requests from the wishlist page; you review, reply, set status, and convert to a WooCommerce order.', 'cecom-wishlist-for-woocommerce' ); ?></p>
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
						// ── Wishlist core (both editions) ──────────────────────────
						array( __( 'Add to Wishlist button (product page + shop loop)', 'cecom-wishlist-for-woocommerce' ), true, true ),
						array( __( 'Guest wishlists — no account required', 'cecom-wishlist-for-woocommerce' ), true, true ),
						array( __( 'Logged-in user wishlists with DB persistence', 'cecom-wishlist-for-woocommerce' ), true, true ),
						array( __( 'Auto-merge guest items on login', 'cecom-wishlist-for-woocommerce' ), true, true ),
						array( __( 'Variation-aware item storage', 'cecom-wishlist-for-woocommerce' ), true, true ),
						array( __( 'Customisable button (style, labels, position, icon)', 'cecom-wishlist-for-woocommerce' ), true, true ),
						array( __( 'Social sharing (WhatsApp, Facebook, X, Pinterest, Telegram)', 'cecom-wishlist-for-woocommerce' ), true, true ),
						array( __( 'Popularity counter on product pages', 'cecom-wishlist-for-woocommerce' ), true, true ),
						array( __( 'Out-of-stock badge + disabled Add to Cart', 'cecom-wishlist-for-woocommerce' ), true, true ),
						array( __( 'Mobile-responsive wishlist page', 'cecom-wishlist-for-woocommerce' ), true, true ),
						array( __( 'Auto-created wishlist page (shortcode + Gutenberg block)', 'cecom-wishlist-for-woocommerce' ), true, true ),
						array( __( 'HPOS + WooCommerce Blocks compatible', 'cecom-wishlist-for-woocommerce' ), true, true ),
						array( __( 'Basic admin dashboard (top 5 most-wished products)', 'cecom-wishlist-for-woocommerce' ), true, true ),
						// ── Premium wishlist management ────────────────────────────
						array( __( 'Unlimited multiple named wishlists per user', 'cecom-wishlist-for-woocommerce' ), false, true ),
						array( __( 'Wishlist dropdown on add — pick list or create inline', 'cecom-wishlist-for-woocommerce' ), false, true ),
						array( __( 'Per-list privacy (Public / Private / Shared link / Collaborative)', 'cecom-wishlist-for-woocommerce' ), false, true ),
						array( __( 'Multiple content layouts (grid, list, compact)', 'cecom-wishlist-for-woocommerce' ), false, true ),
						array( __( 'Add all to Cart, move items, drag & drop reorder', 'cecom-wishlist-for-woocommerce' ), false, true ),
						array( __( 'Manage item quantity inside the wishlist', 'cecom-wishlist-for-woocommerce' ), false, true ),
						array( __( 'Price-change-since-added display (savings in green, sale badge)', 'cecom-wishlist-for-woocommerce' ), false, true ),
						array( __( 'PDF wishlist export', 'cecom-wishlist-for-woocommerce' ), false, true ),
						array( __( 'Public wishlist search', 'cecom-wishlist-for-woocommerce' ), false, true ),
						// ── Email campaigns ────────────────────────────────────────
						array( __( 'Automated price-drop email notifications', 'cecom-wishlist-for-woocommerce' ), false, true ),
						array( __( 'Automated back-in-stock email notifications', 'cecom-wishlist-for-woocommerce' ), false, true ),
						array( __( 'Manual email campaign builder (WP-Cron batched delivery)', 'cecom-wishlist-for-woocommerce' ), false, true ),
						array( __( 'Customisable HTML email templates (logo, brand colour, footer)', 'cecom-wishlist-for-woocommerce' ), false, true ),
						array( __( 'Campaign history with open & click tracking', 'cecom-wishlist-for-woocommerce' ), false, true ),
						array( __( 'Full email analytics — funnel, KPIs, timeseries chart', 'cecom-wishlist-for-woocommerce' ), false, true ),
						// ── Admin & integrations ───────────────────────────────────
						array( __( 'Popular Products dashboard with date-range filter', 'cecom-wishlist-for-woocommerce' ), false, true ),
						array( __( 'Admin Customer Wishlists browser', 'cecom-wishlist-for-woocommerce' ), false, true ),
						array( __( 'Wishlist mini-widget (classic + Elementor with count badge)', 'cecom-wishlist-for-woocommerce' ), false, true ),
						array( __( 'Elementor "Add to Wishlist" widget with full style controls', 'cecom-wishlist-for-woocommerce' ), false, true ),
						array( __( 'Quote / estimate request form (admin manages, converts to order)', 'cecom-wishlist-for-woocommerce' ), false, true ),
						array( __( 'Polylang PRO compatibility', 'cecom-wishlist-for-woocommerce' ), false, true ),
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
