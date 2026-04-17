<?php
/**
 * Wishlist page template — dispatcher.
 *
 * Variables passed from Cecomwishfw_Frontend_Controller::shortcode_callback():
 *
 * @var array                        $items           Enriched item objects from get_for_list().
 * @var object                       $list            Current wishlist list object.
 * @var Cecomwishfw_Settings         $settings        Settings model instance.
 * @var Cecomwishfw_Session_Model    $session         Session model instance.
 * @var bool                         $is_shared_view  True when viewing a shared wishlist via token.
 * @var string                       $owner_name      Display name of the wishlist owner (only set when $is_shared_view).
 *
 * @package Cecomwishfw
 */

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template file; variables are passed from the controller, not global

$is_shared_view = (bool) ( $is_shared_view ?? false );
$owner_name     = (string) ( $owner_name ?? '' );
$item_count     = count( $items );
?>
<div id="cecom-wishlist-for-woocommerceWrap" class="cecomwishfw-wishlist-wrap">

	<?php if ( $is_shared_view && '' !== $owner_name ) : ?>
		<?php /* ── Shared view banner ─────────────────────────────────────── */ ?>
		<div class="cecomwishfw-shared-notice" role="status">
			<i class="bi bi-info-circle" aria-hidden="true"></i>
			<span>
				<?php
				printf(
					/* translators: %s: wishlist owner's display name */
					esc_html__( "You are viewing %s's wishlist.", 'cecom-wishlist-for-woocommerce' ),
					esc_html( $owner_name )
				);
				?>
			</span>
		</div>
	<?php endif; ?>

	<?php /* ── Page header ────────────────────────────────────────────────── */ ?>
	<div class="cecomwishfw-page-header">
		<h2 class="cecomwishfw-page-title">
			<i class="bi bi-heart-fill" aria-hidden="true"></i>
			<?php
			if ( $is_shared_view && '' !== $owner_name ) {
				printf(
					/* translators: %s: wishlist owner's display name */
					esc_html__( "%s's Wishlist", 'cecom-wishlist-for-woocommerce' ),
					esc_html( $owner_name )
				);
			} else {
				esc_html_e( 'My Wishlist', 'cecom-wishlist-for-woocommerce' );
			}
			?>
		</h2>
		<?php if ( $item_count > 0 ) : ?>
			<span class="cecomwishfw-item-count-badge">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %d: number of items in the wishlist */
						_n( '%d item', '%d items', $item_count, 'cecom-wishlist-for-woocommerce' ),
						$item_count
					)
				);
				?>
			</span>
		<?php endif; ?>
	</div>

	<?php if ( empty( $items ) ) : ?>

		<?php /* ── Empty state ────────────────────────────────────────────── */ ?>
		<div class="cecomwishfw-empty">
			<i class="bi bi-heart cecomwishfw-empty__icon" aria-hidden="true"></i>
			<h3 class="cecomwishfw-empty__title">
				<?php
				if ( $is_shared_view ) {
					esc_html_e( 'This wishlist is empty', 'cecom-wishlist-for-woocommerce' );
				} else {
					esc_html_e( 'Your wishlist is empty', 'cecom-wishlist-for-woocommerce' );
				}
				?>
			</h3>
			<p class="cecomwishfw-empty__message">
				<?php
				if ( $is_shared_view ) {
					esc_html_e( 'There are no products in this shared wishlist yet.', 'cecom-wishlist-for-woocommerce' );
				} else {
					esc_html_e( 'Save products you love and come back to them any time.', 'cecom-wishlist-for-woocommerce' );
				}
				?>
			</p>
			<a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>"
				class="cecomwishfw-empty__action">
				<i class="bi bi-bag" aria-hidden="true"></i>
				<?php esc_html_e( 'Browse the shop', 'cecom-wishlist-for-woocommerce' ); ?>
			</a>
		</div>

	<?php else : ?>

		<?php include CECOMWISHFW_PLUGIN_DIR . 'includes/views/frontend/wishlist-table.php'; ?>

		<?php /* ── Share section ──────────────────────────────────────────── */ ?>
		<?php if ( ! $is_shared_view && $settings->get( 'general', 'share_enabled' ) ) : ?>
			<?php
			$page_id      = (int) $settings->get( 'general', 'wishlist_page_id', 0 );
			$wishlist_url = $page_id > 0
				? (string) get_permalink( $page_id )
				: home_url( '/wishlist/' );
			$share_token  = $list->share_token ?? '';
			$share_url    = '' !== $share_token
				? add_query_arg( 'cwfw_token', rawurlencode( $share_token ), $wishlist_url )
				: $wishlist_url;

			/**
			 * Filter the wishlist share URL before rendering share buttons.
			 *
			 * Allows third parties to append UTM parameters or rewrite the URL.
			 *
			 * @param string $share_url  The share URL containing cwfw_token.
			 * @param object $list       The current wishlist list object.
			 */
			$share_url = (string) apply_filters( 'cecomwishfw_share_url', $share_url, $list );

			$share_url_enc  = rawurlencode( $share_url );
			$share_title    = rawurlencode( get_bloginfo( 'name' ) . ' — ' . __( 'My Wishlist', 'cecom-wishlist-for-woocommerce' ) );
			$share_img_url  = rawurlencode( get_site_icon_url( 512, '', 0 ) );
			$share_wa_text  = rawurlencode( __( 'Check out my wishlist:', 'cecom-wishlist-for-woocommerce' ) . ' ' . $share_url );
			$share_mail_sub = rawurlencode( __( 'My Wishlist', 'cecom-wishlist-for-woocommerce' ) );
			$share_mail_bod = rawurlencode( __( 'Check out my wishlist:', 'cecom-wishlist-for-woocommerce' ) . "\n" . $share_url );
			?>
			<div class="cecomwishfw-share-section">

				<p class="cecomwishfw-share-label">
					<?php esc_html_e( 'Share your wishlist:', 'cecom-wishlist-for-woocommerce' ); ?>
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
						<label class="cecomwishfw-share-url-label" for="cecomwishfw-share-url-input">
							<?php esc_html_e( 'Share URL', 'cecom-wishlist-for-woocommerce' ); ?>
						</label>
						<div class="cecomwishfw-share-url-row">
							<input
								type="text"
								id="cecomwishfw-share-url-input"
								class="cecomwishfw-share-url-input"
								value="<?php echo esc_attr( $share_url ); ?>"
								readonly
								aria-label="<?php esc_attr_e( 'Wishlist share URL', 'cecom-wishlist-for-woocommerce' ); ?>">
							<button
								type="button"
								class="cecomwishfw-copy-url"
								data-clipboard-target="#cecomwishfw-share-url-input"
								aria-label="<?php esc_attr_e( 'Copy share URL', 'cecom-wishlist-for-woocommerce' ); ?>">
								<i class="bi bi-clipboard" aria-hidden="true"></i>
								<span><?php esc_html_e( 'Copy', 'cecom-wishlist-for-woocommerce' ); ?></span>
							</button>
							<?php if ( is_user_logged_in() ) : ?>
							<button
								type="button"
								class="cecomwishfw-regenerate-token"
								data-nonce="<?php echo esc_attr( wp_create_nonce( 'cecomwishfw_frontend' ) ); ?>"
								aria-label="<?php esc_attr_e( 'Regenerate share link (invalidates old link)', 'cecom-wishlist-for-woocommerce' ); ?>">
								<i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
								<span><?php esc_html_e( 'Regenerate link', 'cecom-wishlist-for-woocommerce' ); ?></span>
							</button>
						<?php endif; ?>
						</div>

					</div>
				<?php endif; ?>

			</div><!-- .cecomwishfw-share-section -->
		<?php endif; ?>

	<?php endif; ?>

</div><!-- #cecom-wishlist-for-woocommerceWrap -->
