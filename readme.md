<p align="center"><a href="https://cecom.in/"><img src="https://cecom.in/logo.png" alt="cecom.in"></a></p>

<p align="center">
<img src="https://img.shields.io/github/v/release/cecom/cecom-wishlist-for-woocommerce?label=stable" alt="Latest release">
<img src="https://img.shields.io/wordpress/plugin/v/cecom-wishlist-for-woocommerce" alt="WordPress.org version">
<img src="https://img.shields.io/wordpress/plugin/installs/cecom-wishlist-for-woocommerce" alt="Active installs">
<img src="https://img.shields.io/wordpress/plugin/stars/cecom-wishlist-for-woocommerce" alt="Rating">
<img src="https://img.shields.io/github/license/cecom/cecom-wishlist-for-woocommerce" alt="License">
</p>

Welcome to the CECOM Wishlist for WooCommerce repository on GitHub. Here you can browse the source, look at open issues, and keep track of development.

If you are not a developer, please use the [CECOM Wishlist for WooCommerce plugin page](https://wordpress.org/plugins/cecom-wishlist-for-woocommerce/) on WordPress.org.

## About plugin

CECOM Wishlist for WooCommerce lets shoppers save products into organized, shareable wishlists — without creating an account — and gives store owners the tools to turn saved products into purchases.

Seven out of ten shoppers leave without buying. They're not gone — they're undecided. A wishlist keeps your products in their consideration set and tells you exactly what they want. The free edition covers everything a well-run store needs: persistent guest and logged-in wishlists, social sharing, a mobile-friendly wishlist page, and an admin dashboard showing your most-wanted products. The premium edition adds automated price-drop and back-in-stock emails, a manual campaign builder, multiple named lists, and a full analytics suite.

[Plugin page >](https://cecom.in/wishlist-for-woocommerce)
[Documentation >](https://cecom.in/docs-category/cecom-wishlist-for-woocommerce)

### Basic features

* **Add to Wishlist button** on single product pages and the shop loop — configurable as icon only, text only, or icon + text
* **Guest wishlists** stored via session cookie (30-day TTL) and synced to the database — no account required
* **Logged-in user wishlists** with full DB persistence — survives logout, device switch, and browser change
* **Auto-merge on login** — guest items silently move into the user's default wishlist; duplicates are deduplicated against the DB row
* **Add/Remove toggle** without a full page reload (AJAX response with live counter update)
* **Auto-created wishlist page** on activation with shortcode and Gutenberg block — zero manual setup
* **Product table** showing image, name, price, selected variation, and date added — responsive (table on desktop, stacked cards on mobile)
* **Per-product Add to Cart** button inside the wishlist with optional "remove after add" and "redirect to checkout" toggles
* **Share wishlist** via WhatsApp deep link, Facebook, X, Pinterest, Telegram, Email, or Copy Link — token-based URL, no username exposed
* **Variation-aware save** — stores the selected size, colour, or any variation attribute with each item
* **Popularity counter** — "X people have this on their wishlist" displayed on product pages
* **Toast notifications** on add/remove (auto-dismiss 3 s, respects `prefers-reduced-motion`)
* **Customizable button** — labels, colours, position, and custom icon upload
* **Mobile-responsive layout** with stacked card rendering on small screens
* **Out-of-stock display** — badge and disabled Add to Cart button for unavailable items
* **Free admin dashboard** — total wishlist and item counts plus the top 5 most-wished products
* **Deleted products cleanup** — items auto-removed from all wishlists on product trash/delete
* **HPOS compatible** (WooCommerce High-Performance Order Storage) from day one
* **i18n ready** — `.pot` file included, RTL stylesheet
* **WooCommerce Blocks checkout** compatible

### Premium features

[Premium version live demo >](https://cecom.in/wishlist-for-woocommerce)

The premium edition is a complete, standalone plugin — not an add-on — that includes every free feature plus powerful marketing tools for growing your store.

* **Unlimited multiple named wishlists** per user — rename, delete, reorder items
* **Wishlist dropdown on click** — users pick the list or create a new one inline
* **Per-list privacy controls** — Public, Private, or Shared (link-only access via token URL)
* **Multiple wishlist content layouts** — grid, list, and compact views
* **Add all to Cart** bulk action
* **Move items between wishlists**
* **Price-change-since-added display** — current vs. original price, savings in green, sale badge
* **Automated price-drop email** — queued on WC price change hook + daily cron scan
* **Automated back-in-stock email** — triggered on WC stock transition to in-stock
* **Manual email campaign builder** — select a product, see eligible user count, compose, and send; WP-Cron batches at 50 emails/min
* **Campaign history** — date, product, recipients, and open count
* **Customizable HTML email templates** — logo, brand colour, subject, greeting, and footer
* **Admin Popular Products dashboard** — filterable by date range, click-through to user list
* **Admin Customer Wishlists** — browse all wishlists by user
* **Wishlist mini-widget** — classic widget + Elementor widget with count badge
* **Elementor "Add to Wishlist" widget** with full style controls
* **Quote/estimate request form** — CPT-backed; admin manages, replies, converts to order
* **PDF wishlist export**
* **Public wishlist search**
* **Polylang PRO compatibility**

[GET THE PREMIUM VERSION HERE >](https://cecom.in/wishlist-for-woocommerce-annual/)

## Getting started

* [Installation Guide](#installation-guide)
* [Languages](#available-languages)
* [Documentation](#documentation)
* [FAQ](#faq)
* [External Services](#external-services)
* [Changelog](#changelog)
* [Support](#support)
* [Reporting Security Issues](#reporting-security-issues)

## Installation guide

Clone the plugin directly into the `wp-content/plugins/` directory of your WordPress site:

```bash
git clone https://github.com/ozkanugr/cecom-wishlist-for-woocommerce.git wp-content/plugins/cecom-wishlist-for-woocommerce
```

Otherwise, you can:

1. Download the repository `.zip` file.
2. Unzip the downloaded package.
3. Upload the plugin folder into the `wp-content/plugins/` directory of your WordPress site.

Finally, activate **CECOM Wishlist for WooCommerce** from the Plugins screen.

WooCommerce 7.0 or newer must be installed and active. A "My Wishlist" page is created automatically on first activation — no manual page setup required.

## Available Languages

* English — United Kingdom (default)
* Turkish — `tr_TR`

## Documentation

You can find the official documentation of the plugin [here](https://cecom.in/docs-category/cecom-wishlist-for-woocommerce).

## FAQ

**Does the plugin work without user registration?**

Yes. Guest wishlists are stored server-side against a random session token in a 30-day functional cookie. When the guest registers or logs in, their wishlist items are silently merged into their account.

**Is WooCommerce required?**

Yes, WooCommerce 7.0 or newer must be installed and active. The plugin declares a hard dependency and will not activate without it.

**Will this work with HPOS (High-Performance Order Storage)?**

Yes. HPOS compatibility is declared from version 1.0.0 and has been tested with WooCommerce 9.9.

**Does it work with variable products?**

Yes. The selected variation (size, colour, or any attribute) is saved with the wishlist item and displayed in the wishlist row.

**Can guests share their wishlist?**

Yes. Share URLs are token-based — no username or personal information is exposed in the URL. Guest wishlists are fully shareable.

**Where is the wishlist page?**

A page is created automatically on activation. You can change which page is used in **CECOM → Wishlist → General** settings.

**Does the free version include email campaigns?**

No. Automated price-drop/back-in-stock emails and the manual campaign builder are premium-only features. The free admin dashboard shows your top 5 most-wished products and total counts.

**Is this plugin GDPR compliant?**

The plugin sets one functional cookie (guest session token) and relies on your site's cookie consent solution for GDPR compliance. Privacy-policy suggestion text is provided via the WordPress Privacy Policy API. No data is shared with third parties.

## External Services

This plugin renders social share buttons on the wishlist page. All connections are initiated by the visitor's browser only when they click a share button — no data is ever sent server-side by the plugin itself.

| Service | URL pattern rendered | Data passed | Provider |
|---------|----------------------|-------------|----------|
| WhatsApp | `https://wa.me/?text={url}` | Token-based wishlist URL | Meta Platforms, Inc. |
| Facebook | `https://www.facebook.com/sharer/sharer.php?u={url}` | Token-based wishlist URL | Meta Platforms, Inc. |
| X (Twitter) | `https://twitter.com/intent/tweet?url={url}&text={title}` | Token-based wishlist URL, page title | X Corp. |
| Pinterest | `https://pinterest.com/pin/create/button/?url={url}&media={img}&description={title}` | Token-based wishlist URL, first product image URL, page title | Pinterest, Inc. |
| Telegram | `https://t.me/share/url?url={url}&text={title}` | Token-based wishlist URL, page title | Telegram Messenger Inc. |

Share URLs are **token-based** — they contain a random token (e.g. `?cwfw_token=abc123`) and never expose the owner's username or account details.

**Service provider links:**

* WhatsApp — [Terms](https://www.whatsapp.com/legal/terms-of-service) · [Privacy Policy](https://www.whatsapp.com/legal/privacy-policy)
* Facebook — [Terms](https://www.facebook.com/terms) · [Privacy Policy](https://www.facebook.com/privacy/policy)
* X — [Terms](https://twitter.com/en/tos) · [Privacy Policy](https://twitter.com/en/privacy)
* Pinterest — [Terms](https://policy.pinterest.com/en/terms-of-service) · [Privacy Policy](https://policy.pinterest.com/en/privacy-policy)
* Telegram — [Terms](https://telegram.org/tos) · [Privacy Policy](https://telegram.org/privacy)

## Changelog

### 1.0.0 - Released on 17 April 2026

* New: Add to Wishlist button for single product pages and shop loop (icon, text, icon+text modes)
* New: Guest wishlists via 30-day session cookie with server-side DB storage
* New: Logged-in user wishlists with full DB persistence across devices
* New: Auto-merge of guest items into user account on login with deduplication
* New: Token-based wishlist sharing via WhatsApp, Facebook, X, Pinterest, Telegram, Email, and Copy Link
* New: Variation-aware item storage — saves selected product attributes
* New: Popularity counter ("X people have this on their wishlist")
* New: Auto-created wishlist page on activation with shortcode and Gutenberg block
* New: Mobile-responsive wishlist page (table on desktop, cards on mobile)
* New: Per-product Add to Cart button inside wishlist with post-add behavior toggles
* New: Customizable button style, labels, colours, position, and custom icon upload
* New: Toast notifications on add/remove with reduced-motion support
* New: Free admin dashboard with wishlist/item totals and top 5 most-wished products
* New: Deleted-product cleanup via `wp_trash_post` and `before_delete_post` hooks
* New: HPOS compatibility declared for WooCommerce High-Performance Order Storage
* New: WooCommerce Blocks checkout compatibility via DOM injection
* New: i18n ready with `.pot` file, `load_plugin_textdomain`, and RTL stylesheet
* New: Optional data-deletion on uninstall (removes tables, options, auto-created page, transients)
* Dev: AJAX API under `wp_ajax_cecomwishfw_*` / `wp_ajax_nopriv_cecomwishfw_*` with rate limiting and nonce verification
* Dev: Action hooks — `cecomwishfw_before_add_item`, `cecomwishfw_after_add_item`, `cecomwishfw_after_remove_item`, `cecomwishfw_list_created`, `cecomwishfw_guest_merged_into_user`
* Dev: Filter hooks — `cecomwishfw_button_html`, `cecomwishfw_button_label`, `cecomwishfw_wishlist_table_columns`, `cecomwishfw_share_url`, `cecomwishfw_share_channels`, `cecomwishfw_rate_limit`, `cecomwishfw_session_cookie`, `cecomwishfw_cookie_expiration`

## Support

This repository is a development tool. Please post any support request about the free version on the [WordPress.org support forum](https://wordpress.org/support/plugin/cecom-wishlist-for-woocommerce/).

If you have purchased the premium version and need support, please refer to our [support desk](https://cecom.in/support/).

## Reporting Security Issues

To disclose a security issue to our team, please contact us via our [security contact form](https://cecom.in/security/).
