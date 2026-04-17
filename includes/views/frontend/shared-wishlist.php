<?php
/**
 * Shared wishlist view — DEPRECATED, no longer rendered.
 *
 * Historical: this template was rendered by
 * Cecomwishfw_Share_Controller::handle_shared_view() via include + exit, with
 * a bare get_header() / get_footer() wrapper. That bypassed the active theme's
 * page-template chrome (no `<main>`, no `.entry-content`, no the_content()
 * filter chain), so the shared view looked visibly different from a logged-in
 * user's My Wishlist page and broke any theme that renders sidebars / page
 * containers around its content.
 *
 * Current: the share controller no longer hijacks template_redirect for valid
 * tokens. The request now flows through normal WP routing → the wishlist page
 * is rendered inside the active theme's page template → its
 * [cecomwishfw_wishlist] shortcode detects ?cwfw_token=… and delegates to
 * `wishlist-page.php` with $is_shared_view = true and $owner_name resolved.
 * `wishlist-page.php` now renders the same heading + notice that this file
 * used to provide. See:
 *   - Cecomwishfw_Share_Controller::handle_shared_view()
 *   - Cecomwishfw_Frontend_Controller::shortcode_callback()
 *   - includes/views/frontend/wishlist-page.php
 *
 * This file is kept on disk for backwards compatibility with any external
 * code that might be including it directly, but it is no longer reachable
 * from any plugin code path.
 *
 * Variables injected by the (legacy) controller:
 *
 * @var array                     $items           Enriched item objects from get_for_list().
 * @var object                    $list            List row object (includes share_token).
 * @var Cecomwishfw_Settings      $settings        Settings model instance.
 * @var string                    $owner_name      Display name of the wishlist owner.
 * @var bool                      $is_shared_view  Always true — hides remove buttons in wishlist-table.php.
 *
 * @deprecated 1.0.0 Replaced by wishlist-page.php's shared-view branch.
 * @package    Cecomwishfw
 */

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template file; variables are passed from the controller, not global

get_header();
?>

<div id="cecom-wishlist-for-woocommerceWrap" class="cecomwishfw-wishlist-wrap">

	<?php /* ── Page heading ─────────────────────────────────────────────── */ ?>
	<h2 class="cecomwishfw-shared-heading">
		<?php
		/* translators: %s: wishlist owner's display name */
		printf( esc_html__( "%s's Wishlist", 'cecom-wishlist-for-woocommerce' ), esc_html( $owner_name ) );
		?>
	</h2>

	<?php /* ── Shared-view notice ──────────────────────────────────────── */ ?>
	<div class="cecomwishfw-shared-notice" role="status">
		<i class="bi bi-eye" aria-hidden="true"></i>
		<span>
			<?php
			/* translators: %s: wishlist owner's display name */
			printf( esc_html__( "You are viewing %s's wishlist.", 'cecom-wishlist-for-woocommerce' ), esc_html( $owner_name ) );
			?>
		</span>
	</div>

	<?php if ( empty( $items ) ) : ?>

		<?php /* ── Empty state ────────────────────────────────────────────── */ ?>
		<div class="cecomwishfw-empty">
			<i class="bi bi-heart cecomwishfw-empty__icon" aria-hidden="true"></i>
			<h3 class="cecomwishfw-empty__title">
				<?php esc_html_e( 'This wishlist is empty', 'cecom-wishlist-for-woocommerce' ); ?>
			</h3>
			<p class="cecomwishfw-empty__message">
				<?php esc_html_e( 'No products have been added to this wishlist yet.', 'cecom-wishlist-for-woocommerce' ); ?>
			</p>
		</div>

	<?php else : ?>

		<?php include CECOMWISHFW_PLUGIN_DIR . 'includes/views/frontend/wishlist-table.php'; ?>

		<?php /* ── Share bar — recipient can re-share the same token URL ── */ ?>
		<?php if ( $settings->get( 'general', 'share_enabled' ) ) : ?>
			<?php
			$page_id      = (int) $settings->get( 'general', 'wishlist_page_id', 0 );
			$wishlist_url = $page_id > 0
				? (string) get_permalink( $page_id )
				: home_url( '/wishlist/' );
			$share_token  = $list->share_token ?? '';
			$share_url    = '' !== $share_token
				? add_query_arg( 'cwfw_token', rawurlencode( $share_token ), $wishlist_url )
				: $wishlist_url;

			/** This filter is documented in includes/views/frontend/wishlist-page.php */
			$share_url = (string) apply_filters( 'cecomwishfw_share_url', $share_url, $list );

			$share_url_enc = rawurlencode( $share_url );
			$share_title   = rawurlencode(
				get_bloginfo( 'name' ) . ' — ' . sprintf(
				/* translators: %s: wishlist owner's display name */
					__( "%s's Wishlist", 'cecom-wishlist-for-woocommerce' ),
					$owner_name
				)
			);
			$share_img_url  = rawurlencode( get_site_icon_url( 512, '', 0 ) );
			$share_wa_text  = rawurlencode( __( 'Check out this wishlist:', 'cecom-wishlist-for-woocommerce' ) . ' ' . $share_url );
			$share_mail_sub = rawurlencode(
				sprintf(
				/* translators: %s: wishlist owner's display name */
					__( "%s's Wishlist", 'cecom-wishlist-for-woocommerce' ),
					$owner_name
				)
			);
			$share_mail_bod = rawurlencode( __( 'Check out this wishlist:', 'cecom-wishlist-for-woocommerce' ) . "\n" . $share_url );
			?>
			<div class="cecomwishfw-share-section">

				<p class="cecomwishfw-share-label">
					<?php esc_html_e( 'Share this wishlist:', 'cecom-wishlist-for-woocommerce' ); ?>
				</p>

				<div class="cecomwishfw-share-buttons">

					<?php if ( $settings->get( 'general', 'share_facebook' ) ) : ?>
						<a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo esc_attr( $share_url_enc ); ?>"
							class="cecomwishfw-share-btn cecomwishfw-share-facebook"
							target="_blank"
							rel="noopener noreferrer"
							aria-label="<?php esc_attr_e( 'Share on Facebook', 'cecom-wishlist-for-woocommerce' ); ?>">
							<i class="bi bi-facebook" aria-hidden="true"></i>
							<span><?php esc_html_e( 'Facebook', 'cecom-wishlist-for-woocommerce' ); ?></span>
						</a>
					<?php endif; ?>

					<?php if ( $settings->get( 'general', 'share_twitter' ) ) : ?>
						<a href="https://twitter.com/intent/tweet?url=<?php echo esc_attr( $share_url_enc ); ?>&text=<?php echo esc_attr( $share_title ); ?>"
							class="cecomwishfw-share-btn cecomwishfw-share-twitter"
							target="_blank"
							rel="noopener noreferrer"
							aria-label="<?php esc_attr_e( 'Tweet on Twitter (X)', 'cecom-wishlist-for-woocommerce' ); ?>">
							<i class="bi bi-twitter-x" aria-hidden="true"></i>
							<span><?php esc_html_e( 'Twitter (X)', 'cecom-wishlist-for-woocommerce' ); ?></span>
						</a>
					<?php endif; ?>

					<?php if ( $settings->get( 'general', 'share_pinterest' ) ) : ?>
						<a href="https://pinterest.com/pin/create/button/?url=<?php echo esc_attr( $share_url_enc ); ?>&media=<?php echo esc_attr( $share_img_url ); ?>&description=<?php echo esc_attr( $share_title ); ?>"
							class="cecomwishfw-share-btn cecomwishfw-share-pinterest"
							target="_blank"
							rel="noopener noreferrer"
							aria-label="<?php esc_attr_e( 'Pin on Pinterest', 'cecom-wishlist-for-woocommerce' ); ?>">
							<i class="bi bi-pinterest" aria-hidden="true"></i>
							<span><?php esc_html_e( 'Pinterest', 'cecom-wishlist-for-woocommerce' ); ?></span>
						</a>
					<?php endif; ?>

					<?php if ( $settings->get( 'general', 'share_telegram' ) ) : ?>
						<a href="https://t.me/share/url?url=<?php echo esc_attr( $share_url_enc ); ?>&text=<?php echo esc_attr( $share_title ); ?>"
							class="cecomwishfw-share-btn cecomwishfw-share-telegram"
							target="_blank"
							rel="noopener noreferrer"
							aria-label="<?php esc_attr_e( 'Share on Telegram', 'cecom-wishlist-for-woocommerce' ); ?>">
							<i class="bi bi-telegram" aria-hidden="true"></i>
							<span><?php esc_html_e( 'Telegram', 'cecom-wishlist-for-woocommerce' ); ?></span>
						</a>
					<?php endif; ?>

					<?php if ( $settings->get( 'general', 'share_email' ) ) : ?>
						<a href="mailto:?subject=<?php echo esc_attr( $share_mail_sub ); ?>&body=<?php echo esc_attr( $share_mail_bod ); ?>"
							class="cecomwishfw-share-btn cecomwishfw-share-email"
							target="_blank"
							rel="noopener noreferrer"
							aria-label="<?php esc_attr_e( 'Share by email', 'cecom-wishlist-for-woocommerce' ); ?>">
							<i class="bi bi-envelope" aria-hidden="true"></i>
							<span><?php esc_html_e( 'Email', 'cecom-wishlist-for-woocommerce' ); ?></span>
						</a>
					<?php endif; ?>

					<?php if ( $settings->get( 'general', 'share_whatsapp' ) ) : ?>
						<a href="https://wa.me/?text=<?php echo esc_attr( $share_wa_text ); ?>"
							class="cecomwishfw-share-btn cecomwishfw-share-whatsapp"
							target="_blank"
							rel="noopener noreferrer"
							aria-label="<?php esc_attr_e( 'Share on WhatsApp', 'cecom-wishlist-for-woocommerce' ); ?>">
							<i class="bi bi-whatsapp" aria-hidden="true"></i>
							<span><?php esc_html_e( 'WhatsApp', 'cecom-wishlist-for-woocommerce' ); ?></span>
						</a>
					<?php endif; ?>

				</div><!-- .cecomwishfw-share-buttons -->

				<?php if ( $settings->get( 'general', 'share_url' ) ) : ?>
					<div class="cecomwishfw-share-url-wrap">
						<label class="cecomwishfw-share-url-label" for="cecomwishfw-share-url-input-shared">
							<?php esc_html_e( 'Share URL', 'cecom-wishlist-for-woocommerce' ); ?>
						</label>
						<div class="cecomwishfw-share-url-row">
							<input
								type="text"
								id="cecomwishfw-share-url-input-shared"
								class="cecomwishfw-share-url-input"
								value="<?php echo esc_attr( $share_url ); ?>"
								readonly
								aria-label="<?php esc_attr_e( 'Wishlist share URL', 'cecom-wishlist-for-woocommerce' ); ?>">
							<button
								type="button"
								class="cecomwishfw-copy-url"
								data-clipboard-target="#cecomwishfw-share-url-input-shared"
								aria-label="<?php esc_attr_e( 'Copy share URL', 'cecom-wishlist-for-woocommerce' ); ?>">
								<i class="bi bi-clipboard" aria-hidden="true"></i>
								<span><?php esc_html_e( 'Copy', 'cecom-wishlist-for-woocommerce' ); ?></span>
							</button>
						</div>
					</div>
				<?php endif; ?>

			</div><!-- .cecomwishfw-share-section -->
		<?php endif; ?>

	<?php endif; ?>

</div><!-- #cecom-wishlist-for-woocommerceWrap -->

<?php
get_footer();
