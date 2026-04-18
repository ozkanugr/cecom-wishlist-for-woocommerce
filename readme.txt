=== CECOM Wishlist for WooCommerce ===

Contributors: ugurozkan
Tags: woocommerce add to wishlist, woocommerce, save for later, wishlist for woocommerce, share wishlist

Requires at least: 6.4
Tested up to: 6.9
Stable tag: 1.1.0
Requires PHP: 8.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

The easiest-to-use wishlist plugin for WooCommerce — save products, share lists, and convert warm leads with 1-click email campaigns.
WooCommerce 9.9.x compatible.

== Description ==

CECOM Wishlist for WooCommerce lets shoppers save products into organized, shareable wishlists — without creating an account — and gives store owners the tools to turn saved products into purchases.

Seven out of ten shoppers leave without buying. They're not gone — they're undecided. A wishlist keeps your products in their consideration set and tells you exactly what they want. The free edition covers everything a well-run store needs: persistent guest and logged-in wishlists, social sharing, a mobile-friendly wishlist page, and an admin dashboard showing your most-wanted products. The premium edition adds automated price-drop and back-in-stock emails, a manual campaign builder, multiple named lists, and a full analytics suite.

[Plugin page >](https://cecom.in/wishlist-for-woocommerce)
[Documentation >](https://cecom.in/docs-category/cecom-wishlist-for-woocommerce)

= Basic features =

* **Add to Wishlist button** on single product pages and the shop loop — configurable as icon only, text only, or icon + text
* **Guest wishlists** stored via session cookie (30-day TTL) and synced to the database — no account required
* **Logged-in user wishlists** with full DB persistence — survives logout, device switch, and browser change
* **Auto-merge on login** — guest items silently move into the user's default wishlist; duplicates are deduplicated against the DB row
* **Add/Remove toggle** without a full page reload (AJAX response with live counter update)
* **Auto-created wishlist page** on activation with shortcode and Gutenberg block — zero manual setup
* **Product table** showing image, name, price, selected variation, and date added — responsive (table on desktop, stacked cards on mobile)
* **Per-product Add to Cart** button inside the wishlist with optional "remove after add" and "redirect to checkout" toggles
* **Share wishlist** via WhatsApp deep link, Facebook, X, Pinterest, Telegram, Email, or Copy Link — token-based URL, no username exposed
* **Variation-aware save** — stores the selected size, color, or any variation attribute with each item
* **Popularity counter** — "X people have this on their wishlist" displayed on product pages
* **Toast notifications** on add/remove (auto-dismiss 3 s, respects `prefers-reduced-motion`)
* **Customizable button** — labels, colors, position (after cart, before cart, after summary, after price, image overlay, or shortcode), and Bootstrap Icons CSS class (no file upload required)
* **Mobile-responsive layout** with stacked card rendering on small screens
* **Out-of-stock display** — shows badge and disables the Add to Cart button for unavailable items
* **Free admin dashboard** — total wishlist and item counts plus the top 5 most-wished products
* **Deleted products cleanup** — items auto-removed from all wishlists on product trash/delete
* **HPOS compatible** (WooCommerce High-Performance Order Storage) from day one
* **i18n ready** — `.pot` file included, `load_plugin_textdomain`, RTL stylesheet
* **Uninstall cleanup** — optional "delete all data on uninstall" toggle (default: on)
* **WooCommerce Blocks checkout** compatible via DOM-based JS injection

= Premium features =

[Premium version live demo >](https://plugins.cecom.in/cecom-wishlist-for-woocommerce/)

The premium edition is a complete, standalone plugin (not an add-on) that includes every free feature plus powerful marketing tools for growing your store.

* **Unlimited multiple named wishlists** per user — inline create, rename, and delete
* **Wishlist dropdown on click** — users pick an existing list or create a new one inline
* **Per-list privacy controls** — Public, Private, Shared (token URL), or Collaborative (visitors can add to the owner's list)
* **Multiple wishlist content layouts** — table and cards views
* **Add all to Cart** bulk action
* **Move items between wishlists**
* **Mark as Purchased** — gift-givers can mark items on public/shared/collaborative wishlists to avoid duplicates
* **Price-change-since-added display** — current vs. original price, savings in green, sale badge
* **Automated price-drop email** — queued on WC `woocommerce_product_set_sale_price` hook + daily cron scan
* **Automated back-in-stock email** — triggered on WC stock transition to in-stock
* **Manual email campaign builder** — select a product, preview eligible recipient count, compose, schedule (Send Now or Schedule for later), and send; WP-Cron batches at 50 emails/min; edit or cancel scheduled campaigns before dispatch
* **Campaign history** — date, product, recipients, sent, opens, clicks, status (queued/scheduled/sending/completed/cancelled), and actions
* **Email analytics** — open rate, click rate, conversion rate, revenue, timeseries chart, and by-type breakdown
* **Customizable HTML email templates** — logo, brand colour, subject, greeting, and footer
* **Admin Popular Products dashboard** — filterable by date range, click-through to user list
* **Admin Customer Wishlists** — browse all wishlists by user
* **Wishlist mini-widget** — classic widget + Elementor widget with count badge
* **Elementor "Add to Wishlist" widget** with full style controls
* **Quote/estimate request form** — CPT-backed; admin manages, replies, converts to order
* **PDF wishlist export**
* **Public wishlist search**
* **Polylang PRO compatibility**
* **Premium licensing via DLM**

[GET THE PREMIUM VERSION HERE >](https://cecom.in/wishlist-for-woocommerce)

== Installation ==

1. Unzip the downloaded zip file.
2. Upload the plugin folder into the `wp-content/plugins/` directory of your WordPress site.
3. Activate **CECOM Wishlist for WooCommerce** from the Plugins screen.
4. WooCommerce 7.0 or newer must be installed and active.

CECOM Wishlist for WooCommerce adds a new submenu called **Wishlist** under the **CECOM** menu in your WordPress admin. A "My Wishlist" page is created automatically on first activation — no manual setup required.

== Frequently Asked Questions ==

= Does the plugin work without user registration? =

Yes. Guest wishlists are stored server-side against a random session token in a 30-day functional cookie. When the guest registers or logs in, their wishlist items are silently merged into their account.

= Is WooCommerce required? =

Yes, WooCommerce 7.0 or newer must be installed and active. The plugin declares a hard dependency and will not activate without it.

= Will this work with HPOS (High-Performance Order Storage)? =

Yes. HPOS compatibility is declared from version 1.0.0 and has been tested with WooCommerce 9.9.

= Does it work with variable products? =

Yes. The selected variation (size, colour, or any attribute) is saved with the wishlist item and displayed in the wishlist row.

= Can guests share their wishlist? =

Yes. Share URLs are token-based — no username or personal information is exposed in the URL. Guest wishlists are fully shareable.

= Where is the wishlist page? =

A page is created automatically when you activate the plugin. You can change which page is used in **CECOM → Wishlist → General** settings.

= Does the free version include email campaigns? =

No. Automated price-drop/back-in-stock emails and the manual campaign builder are premium-only features. The free admin dashboard shows your top 5 most-wished products and total counts.

= Is this plugin GDPR compliant? =

The plugin sets one functional cookie (guest session token). It relies on your site's cookie consent solution for GDPR compliance. Privacy-policy suggestion text is provided via the WordPress Privacy Policy API. No data is shared with third parties.

= How can I report security bugs? =

You can report security bugs through the CECOM security contact form. [Report a security vulnerability.](https://cecom.in/contact)

== Screenshots ==

1. Add to Wishlist button on the single product page
2. Wishlist page — desktop table view
3. Wishlist page — mobile card view
4. Share wishlist panel with social and copy-link options
5. Admin settings — General tab
6. Admin settings — Appearance tab
7. Admin dashboard — Popular Products

== External Services ==

This plugin uses five external social sharing platforms on the storefront. No data is sent server-side — connections are initiated by the visitor's browser only when they click a share button.

= WhatsApp (wa.me) =

**Purpose:** Render a "Share on WhatsApp" deep-link button on the wishlist page and the shared wishlist view. When clicked, the visitor's browser opens WhatsApp with a pre-composed message containing the token-based wishlist URL.

**When the connection is made:**

* Only when a visitor explicitly clicks the WhatsApp share button — no background requests are made by the plugin.

**What data is sent:**

* The token-based wishlist URL (e.g. `https://yourstore.com/wishlist/?cwfw_token=abc123`) is included in the link text. No username, email address, or personal data is transmitted.

**Service provider:** Meta Platforms, Inc.
* Terms of Service: https://www.whatsapp.com/legal/terms-of-service
* Privacy Policy: https://www.whatsapp.com/legal/privacy-policy

= Facebook (facebook.com) =

**Purpose:** Render a "Share on Facebook" button. When clicked, the visitor's browser navigates to `https://www.facebook.com/sharer/sharer.php` with the token-based wishlist URL as a query parameter.

**When the connection is made:**

* Only when a visitor explicitly clicks the Facebook share button.

**What data is sent:**

* The token-based wishlist URL is passed as a URL query parameter. No personal data, no user identifiers, and no private wishlist content is transmitted.

**Service provider:** Meta Platforms, Inc.
* Terms of Service: https://www.facebook.com/terms
* Privacy Policy: https://www.facebook.com/privacy/policy

= X — formerly Twitter (twitter.com) =

**Purpose:** Render a "Share on X" button. When clicked, the visitor's browser navigates to `https://twitter.com/intent/tweet` with the token-based wishlist URL and an optional share title as query parameters.

**When the connection is made:**

* Only when a visitor explicitly clicks the X share button.

**What data is sent:**

* The token-based wishlist URL and the page title (store name + "wishlist") are passed as URL query parameters. No personal data is transmitted.

**Service provider:** X Corp.
* Terms of Service: https://twitter.com/en/tos
* Privacy Policy: https://twitter.com/en/privacy

= Pinterest (pinterest.com) =

**Purpose:** Render a "Pin on Pinterest" button. When clicked, the visitor's browser navigates to `https://pinterest.com/pin/create/button/` with the wishlist URL, the first product image URL, and an optional description as query parameters.

**When the connection is made:**

* Only when a visitor explicitly clicks the Pinterest share button.

**What data is sent:**

* The token-based wishlist URL, the first product's image URL (a public URL served by your own server), and the page title are passed as URL query parameters. No personal data is transmitted.

**Service provider:** Pinterest, Inc.
* Terms of Service: https://policy.pinterest.com/en/terms-of-service
* Privacy Policy: https://policy.pinterest.com/en/privacy-policy

= Telegram (t.me) =

**Purpose:** Render a "Share on Telegram" button. When clicked, the visitor's browser navigates to `https://t.me/share/url` with the wishlist URL and an optional title as query parameters.

**When the connection is made:**

* Only when a visitor explicitly clicks the Telegram share button.

**What data is sent:**

* The token-based wishlist URL and the page title are passed as URL query parameters. No personal data is transmitted.

**Service provider:** Telegram Messenger Inc.
* Terms of Service: https://telegram.org/tos
* Privacy Policy: https://telegram.org/privacy

== Changelog ==

= 1.1.0 - Released on 18 April 2026 =

* New: CECOM Ecosystem page — cross-promotional admin page listing all CECOM plugins with install-state badges and purchase links.
* Fix: Minor bugs.

= 1.0.0 - Released on 17 April 2026 =

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
* New: Customizable button style, labels, colours, position, and Bootstrap Icons CSS class (no file upload)
* New: Toast notifications on add/remove with reduced-motion support
* New: Free admin dashboard with wishlist/item totals and top 5 most-wished products
* New: Deleted-product cleanup via `wp_trash_post` and `before_delete_post` hooks
* New: HPOS compatibility declared for WooCommerce High-Performance Order Storage
* New: WooCommerce Blocks checkout compatibility via DOM injection
* New: i18n ready with `.pot` file, `load_plugin_textdomain`, and RTL stylesheet
* New: Optional data-deletion on uninstall (removes tables, options, auto-created page, transients)
* Dev: AJAX API under `wp_ajax_cecomwishfw_*` / `wp_ajax_nopriv_cecomwishfw_*` with rate limiting and nonce verification
* Dev: Action hooks `cecomwishfw_before_add_item`, `cecomwishfw_after_add_item`, `cecomwishfw_after_remove_item`, `cecomwishfw_list_created`, `cecomwishfw_guest_merged_into_user`
* Dev: Filter hooks `cecomwishfw_button_html`, `cecomwishfw_wishlist_table_columns`, `cecomwishfw_share_url`, `cecomwishfw_wishlist_item_data`, `cecomwishfw_rate_limit` (returns array), `cecomwishfw_session_cookie`, `cecomwishfw_cookie_expiration`, `cecomwishfw_session_use_secure_cookie`, `cecomwishfw_popularity_count`

== Upgrade Notice ==

= 1.0.0 =
Initial release — no upgrade required.
