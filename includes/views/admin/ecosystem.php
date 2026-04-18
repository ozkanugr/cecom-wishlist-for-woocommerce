<?php
/**
 * CECOM Ecosystem promotional page view.
 *
 * @package Cecomwishfw
 *
 * @var array<int, array<string, mixed>> $plugins  Catalog from Cecomwishfw_Ecosystem_Controller::get_catalog().
 * @var array<string, string>            $statuses Map of plugin key => 'premium'|'free'|'none'.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap" id="cecomplgns-ecosystem">
	<div class="col-12 mb-3 shadow-sm rounded-4 p-3 p-sm-4 border border-light-subtle bg-white">

		<!-- Page header -->
		<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4 pb-3 border-bottom">
			<div>
				<h1 class="h4 fw-bold text-body-emphasis mb-1">
					<i class="bi bi-grid-1x2-fill text-primary me-2" aria-hidden="true"></i>
					<?php esc_html_e( 'CECOM Ecosystem', 'cecom-wishlist-for-woocommerce' ); ?>
				</h1>
				<p class="text-muted small mb-0">
					<?php esc_html_e( 'Discover the full range of CECOM plugins — tools built to work well together.', 'cecom-wishlist-for-woocommerce' ); ?>
				</p>
			</div>
			<a
				href="<?php echo esc_url( 'https://cecom.in/' ); ?>"
				target="_blank"
				rel="noopener noreferrer"
				class="btn btn-outline-secondary rounded-pill px-3 d-inline-flex align-items-center gap-2"
			>
				<i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
				<?php esc_html_e( 'Browse all at cecom.in', 'cecom-wishlist-for-woocommerce' ); ?>
			</a>
		</div>

		<!-- Plugin cards grid -->
		<div class="row g-4">
			<?php foreach ( $plugins as $plugin ) : ?>
				<?php
				$status      = $statuses[ $plugin['key'] ] ?? 'none';
				$has_premium = ! empty( $plugin['premium_basename'] ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
				$image_url   = ! empty( $plugin['image_url'] ) ? $plugin['image_url'] : ''; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
				?>
				<div class="d-flex justify-content-center col-12 col-lg-6">
					<div class="card h-100 border border-light-subtle rounded-4 shadow-sm overflow-hidden">

						<!-- Product image -->
						<?php if ( $image_url ) : ?>
							<div style="height:200px;overflow:hidden;background:#f8f9fa;">
								<img
									src="<?php echo esc_url( $image_url ); ?>"
									alt="<?php echo esc_attr( $plugin['name'] ); ?>"
									class="w-100 h-100"
									style="object-fit:cover;"
									loading="lazy"
								/>
							</div>
						<?php endif; ?>

						<div class="card-body d-flex flex-column p-4">

							<!-- Name + status badge -->
							<div class="d-flex align-items-start justify-content-between gap-2 mb-2">
								<h2 class="h5 fw-bold text-body-emphasis mb-0">
									<?php echo esc_html( $plugin['name'] ); ?>
								</h2>

								<?php if ( 'premium' === $status ) : ?>
									<span class="badge bg-success-subtle border border-success-subtle text-success-emphasis rounded-pill flex-shrink-0">
										<i class="bi bi-check-circle-fill me-1" aria-hidden="true"></i>
										<?php esc_html_e( 'Installed (Premium)', 'cecom-wishlist-for-woocommerce' ); ?>
									</span>
								<?php elseif ( 'free' === $status && $has_premium ) : ?>
									<span class="badge bg-primary-subtle border border-primary-subtle text-primary-emphasis rounded-pill flex-shrink-0">
										<i class="bi bi-check-circle-fill me-1" aria-hidden="true"></i>
										<?php esc_html_e( 'Installed (Free)', 'cecom-wishlist-for-woocommerce' ); ?>
									</span>
								<?php elseif ( 'free' === $status ) : ?>
									<span class="badge bg-success-subtle border border-success-subtle text-success-emphasis rounded-pill flex-shrink-0">
										<i class="bi bi-check-circle-fill me-1" aria-hidden="true"></i>
										<?php esc_html_e( 'Installed', 'cecom-wishlist-for-woocommerce' ); ?>
									</span>
								<?php else : ?>
									<span class="badge bg-secondary-subtle border border-secondary-subtle text-secondary-emphasis rounded-pill flex-shrink-0">
										<?php esc_html_e( 'Available', 'cecom-wishlist-for-woocommerce' ); ?>
									</span>
								<?php endif; ?>
							</div>

							<!-- Tagline -->
							<p class="text-muted small mb-3"><?php echo esc_html( $plugin['tagline'] ); ?></p>

							<!-- Feature items — bg-body-tertiary cards, framework pattern -->
							<?php if ( ! empty( $plugin['features'] ) ) : ?>
								<div class="row row-cols-1 g-2 mb-3">
									<?php foreach ( $plugin['features'] as $feature ) : // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
										<div class="col">
											<div class="d-flex align-items-center gap-2 px-3 py-2 rounded-3 bg-body-tertiary small">
												<i class="bi bi-check2 text-primary flex-shrink-0" aria-hidden="true"></i>
												<?php echo esc_html( $feature ); ?>
											</div>
										</div>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>

							<!-- Tag badges -->
							<?php if ( ! empty( $plugin['badges'] ) ) : ?>
								<div class="mb-3 d-flex flex-wrap gap-1">
									<?php foreach ( $plugin['badges'] as $badge ) : // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
										<span class="badge bg-body-tertiary border text-muted rounded-pill px-2">
											<?php echo esc_html( $badge ); ?>
										</span>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>

							<!-- CTA footer -->
							<div class="mt-auto pt-3 border-top d-flex align-items-center justify-content-between">
								<?php if ( 'premium' === $status ) : ?>
									<span class="small text-muted">
										<i class="bi bi-stars me-1 text-warning" aria-hidden="true"></i>
										<?php esc_html_e( 'Premium edition active.', 'cecom-wishlist-for-woocommerce' ); ?>
									</span>
									<a
										href="<?php echo esc_url( $plugin['purchase_url'] ); ?>"
										target="_blank"
										rel="noopener noreferrer"
										class="btn btn-light rounded-pill px-3 btn-sm d-inline-flex align-items-center gap-2"
									>
										<i class="bi bi-book" aria-hidden="true"></i>
										<?php esc_html_e( 'View details', 'cecom-wishlist-for-woocommerce' ); ?>
									</a>
								<?php elseif ( 'free' === $status && $has_premium ) : ?>
									<span class="small text-muted">
										<i class="bi bi-check-circle me-1 text-success" aria-hidden="true"></i>
										<?php esc_html_e( 'Free edition active.', 'cecom-wishlist-for-woocommerce' ); ?>
									</span>
									<a
										href="<?php echo esc_url( $plugin['purchase_url'] ); ?>"
										target="_blank"
										rel="noopener noreferrer"
										class="btn btn-warning rounded-pill px-4 btn-sm fw-semibold d-inline-flex align-items-center gap-2"
									>
										<i class="bi bi-stars" aria-hidden="true"></i>
										<?php esc_html_e( 'Upgrade to Premium', 'cecom-wishlist-for-woocommerce' ); ?>
									</a>
								<?php elseif ( 'free' === $status ) : ?>
									<span class="small text-muted">
										<i class="bi bi-check-circle me-1 text-success" aria-hidden="true"></i>
										<?php esc_html_e( 'Active.', 'cecom-wishlist-for-woocommerce' ); ?>
									</span>
									<a
										href="<?php echo esc_url( $plugin['purchase_url'] ); ?>"
										target="_blank"
										rel="noopener noreferrer"
										class="btn btn-light rounded-pill px-3 btn-sm d-inline-flex align-items-center gap-2"
									>
										<i class="bi bi-book" aria-hidden="true"></i>
										<?php esc_html_e( 'View details', 'cecom-wishlist-for-woocommerce' ); ?>
									</a>
								<?php else : ?>
									<span class="small text-muted">
										<?php esc_html_e( 'Not installed.', 'cecom-wishlist-for-woocommerce' ); ?>
									</span>
									<a
										href="<?php echo esc_url( $plugin['purchase_url'] ); ?>"
										target="_blank"
										rel="noopener noreferrer"
										class="btn btn-primary rounded-pill px-4 btn-sm fw-semibold d-inline-flex align-items-center gap-2"
									>
										<i class="bi bi-arrow-up-right" aria-hidden="true"></i>
										<?php esc_html_e( 'Get it', 'cecom-wishlist-for-woocommerce' ); ?>
									</a>
								<?php endif; ?>
							</div>

						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<!-- Soft footer -->
		<p class="text-muted small text-center mt-4 mb-0">
			<?php
			echo wp_kses(
				sprintf(
					/* translators: %s: cecom.in link */
					__( 'Have an idea for the next CECOM plugin? Let us know at %s.', 'cecom-wishlist-for-woocommerce' ),
					'<a href="' . esc_url( 'https://cecom.in/contact/' ) . '" target="_blank" rel="noopener noreferrer">cecom.in</a>'
				),
				array(
					'a' => array(
						'href'   => true,
						'target' => true,
						'rel'    => true,
					),
				)
			);
			?>
		</p>

	</div>
</div>
