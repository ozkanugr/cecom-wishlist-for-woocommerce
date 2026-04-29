<?php
/**
 * Dashboard → Overview sub-tab (FREE edition).
 *
 * Preserves the free edition's original Dashboard content: total-wishlists
 * stat cards + top-5 popular products table + the locked "Full Analytics"
 * preview (Frosted Glass Overlay pattern). Identical content to the previous
 * monolithic tab-dashboard.php, just relocated into a sub-tab.
 *
 * @package Cecomwishfw
 */

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template partial; variables passed from parent.

// ── Fetch aggregate data ────────────────────────────────────────────────────
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
<?php
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
