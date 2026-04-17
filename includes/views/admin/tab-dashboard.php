<?php
/**
 * Dashboard tab — analytics overview.
 *
 * Displays free-edition stats: total wishlists, total items, top 5 popular
 * products (by wishlist count). Read-only — no form fields.
 *
 * Uses the cecom-plugin-admin-ui-framework flat panel pattern and the
 * framework stat-card pattern (bg-light shadow-sm rounded-3). The locked
 * "Full Analytics" block uses the Frosted Glass Overlay pattern (Design #2).
 *
 * @package Cecomwishfw
 */

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template file; variables are passed from the controller, not global

// ── Fetch aggregate data ─────────────────────────────────────────────────────
$total_lists = Cecomwishfw_List_Model::count_all();
$total_items = Cecomwishfw_Item_Model::count_all();
$popular     = Cecomwishfw_Item_Model::get_popular_products( 5 );
$top_product = ! empty( $popular ) ? $popular[0] : null;
$top_name    = $top_product ? get_the_title( (int) $top_product->product_id ) : '—';

$upgrade_url = 'https://cecom.in/wishlist-for-woocommerce-annual-premium/';
?>

<?php /* ── Stats panel (framework stat-card grid) ─────────────────────── */ ?>
<div class="col-12 mb-3 shadow-sm rounded-4 p-3 p-sm-4 border border-light-subtle bg-white">
	<h2 class="h6 fw-semibold text-body-emphasis mb-3">
		<i class="bi bi-graph-up me-1" aria-hidden="true"></i>
		<?php esc_html_e( 'Wishlist Overview', 'cecom-wishlist-for-woocommerce' ); ?>
	</h2>

	<div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3">

		<div class="col">
			<div class="card mt-0 h-100 text-center shadow-sm rounded-3 p-3 gap-2 bg-light border border-light-subtle">
				<div class="stat-value text-body-emphasis fs-2 fw-bold">
					<?php echo esc_html( number_format_i18n( $total_lists ) ); ?>
				</div>
				<div class="small text-muted text-uppercase">
					<i class="bi bi-heart me-1" aria-hidden="true"></i>
					<?php esc_html_e( 'Active Wishlists', 'cecom-wishlist-for-woocommerce' ); ?>
				</div>
			</div>
		</div>

		<div class="col">
			<div class="card mt-0 h-100 text-center shadow-sm rounded-3 p-3 gap-2 bg-light border border-light-subtle">
				<div class="stat-value text-body-emphasis fs-2 fw-bold">
					<?php echo esc_html( number_format_i18n( $total_items ) ); ?>
				</div>
				<div class="small text-muted text-uppercase">
					<i class="bi bi-box-seam me-1" aria-hidden="true"></i>
					<?php esc_html_e( 'Total Items', 'cecom-wishlist-for-woocommerce' ); ?>
				</div>
			</div>
		</div>

		<div class="col">
			<div class="card mt-0 h-100 text-center shadow-sm rounded-3 p-3 gap-2 bg-light border border-light-subtle">
				<div class="stat-value text-body-emphasis fs-6 fw-bold text-truncate" aria-label="<?php echo esc_attr( $top_name ); ?>">
					<?php echo esc_html( $top_name ); ?>
				</div>
				<div class="small text-muted text-uppercase">
					<i class="bi bi-trophy me-1" aria-hidden="true"></i>
					<?php esc_html_e( 'Most Wishlisted', 'cecom-wishlist-for-woocommerce' ); ?>
				</div>
			</div>
		</div>

	</div>
</div>

<?php /* ── Top 5 popular products panel ────────────────────────────────── */ ?>
<div class="col-12 mb-3 shadow-sm rounded-4 p-3 p-sm-4 border border-light-subtle bg-white">
	<h2 class="h6 fw-semibold text-body-emphasis mb-3">
		<i class="bi bi-bar-chart-line me-1" aria-hidden="true"></i>
		<?php esc_html_e( 'Top 5 Popular Products', 'cecom-wishlist-for-woocommerce' ); ?>
	</h2>

	<?php if ( empty( $popular ) ) : ?>
		<div class="p-4 text-muted text-center">
			<i class="bi bi-inbox fs-3 d-block mb-2 opacity-50" aria-hidden="true"></i>
			<?php esc_html_e( 'No wishlist data yet.', 'cecom-wishlist-for-woocommerce' ); ?>
		</div>
	<?php else : ?>
		<div class="table-responsive">
			<table class="table table-hover mb-0 align-middle">
				<thead class="table-light">
					<tr>
						<th scope="col" style="width:60px;">#</th>
						<th scope="col"><?php esc_html_e( 'Product', 'cecom-wishlist-for-woocommerce' ); ?></th>
						<th scope="col" style="width:160px;"><?php esc_html_e( 'Wishlist Count', 'cecom-wishlist-for-woocommerce' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $popular as $rank => $row ) : ?>
						<?php
						$product_id   = (int) $row->product_id;
						$wish_count   = (int) $row->wish_count;
						$product_name = get_the_title( $product_id );
						$edit_url     = get_edit_post_link( $product_id );
						?>
						<tr>
							<td class="text-muted"><?php echo esc_html( $rank + 1 ); ?></td>
							<td>
								<?php if ( $edit_url ) : ?>
									<a href="<?php echo esc_url( $edit_url ); ?>">
										<?php echo esc_html( $product_name ); ?>
									</a>
								<?php else : ?>
									<?php echo esc_html( $product_name ); ?>
								<?php endif; ?>
							</td>
							<td>
								<span class="badge bg-primary-subtle border border-primary-subtle text-primary-emphasis rounded-pill">
									<?php echo esc_html( number_format_i18n( $wish_count ) ); ?>
								</span>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</div>

<?php /* ── Full Analytics (Frosted Glass Overlay — Design #2) ───────────── */ ?>
<div class="col-12 mb-3 shadow-sm rounded-4 border border-light-subtle bg-white position-relative overflow-hidden" style="min-height:320px;">

	<?php /* Blurred placeholder chart preview */ ?>
	<div class="p-4 user-select-none" style="filter:blur(3px); pointer-events:none;">
		<div class="d-flex align-items-end gap-2" style="height:180px;">
			<div class="bg-primary-subtle rounded-top" style="width:40px;height:60%;"></div>
			<div class="bg-primary-subtle rounded-top" style="width:40px;height:85%;"></div>
			<div class="bg-primary-subtle rounded-top" style="width:40px;height:45%;"></div>
			<div class="bg-primary-subtle rounded-top" style="width:40px;height:92%;"></div>
			<div class="bg-primary-subtle rounded-top" style="width:40px;height:70%;"></div>
			<div class="bg-primary-subtle rounded-top" style="width:40px;height:55%;"></div>
			<div class="bg-primary-subtle rounded-top" style="width:40px;height:78%;"></div>
			<div class="bg-primary-subtle rounded-top" style="width:40px;height:40%;"></div>
			<div class="bg-primary-subtle rounded-top" style="width:40px;height:65%;"></div>
			<div class="bg-primary-subtle rounded-top" style="width:40px;height:88%;"></div>
		</div>
		<div class="d-flex justify-content-between mt-2 small text-muted">
			<span>Jan</span><span>Feb</span><span>Mar</span><span>Apr</span><span>May</span>
			<span>Jun</span><span>Jul</span><span>Aug</span><span>Sep</span><span>Oct</span>
		</div>
	</div>

	<?php /* Frosted glass overlay */ ?>
	<div class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center text-center px-4 rounded-4"
		style="background:rgba(255,255,255,.72);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);">
		<i class="bi bi-shield-lock-fill text-primary mb-2" style="font-size:2.4rem;" aria-hidden="true"></i>
		<p class="fw-bold text-body-emphasis mb-1">
			<?php esc_html_e( 'Full Analytics — Pro', 'cecom-wishlist-for-woocommerce' ); ?>
		</p>
		<p class="text-secondary small mb-3" style="max-width:440px;">
			<?php esc_html_e( 'All wishlisted products, customer lists, date-range filters, and email campaign history in one place.', 'cecom-wishlist-for-woocommerce' ); ?>
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
