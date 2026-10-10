=== QuickTools for WooCommerce ===
Contributors: hmbashar
Tags: total sold, ecommerce, woocommerce, orders, product sales
Requires at least: 5.0
Requires PHP: 7.4
Tested up to: 6.6.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

QuickTools for WooCommerce adds sales insights on products and previous-order badges on the orders list for faster store management.

== Description ==

QuickTools for WooCommerce enhances your WooCommerce admin and storefront with lightweight sales tools. No settings page is required — activate the plugin and the features are available immediately.

== Key Features ==

* **Sold column (Products):** Sortable units-sold column on the Products list for all product types.
* **Total Sold (Storefront):** Shows total units sold in shop/archive loops and on single product pages.
* **Previous Orders column (Orders):** On each order, shows prior order counts for the same billing phone — Total, Processing, On Hold, Pending, and Cancelled. Works with classic orders and HPOS.
* **Lightweight:** No JavaScript; CSS loads only on relevant product/order screens.

== Installation ==

1. Upload the `quicktools-for-woocommerce` directory to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Ensure that WooCommerce is installed and activated to use this plugin.

== Frequently Asked Questions ==

= What is the purpose of this plugin? =
It adds product sales visibility (admin + storefront) and previous-order status badges on the orders list, matched by billing phone.

= Is WooCommerce required? =
Yes. QuickTools for WooCommerce requires WooCommerce to be installed and activated.

= Does it support High-Performance Order Storage (HPOS)? =
Yes. The Previous Orders column registers on both the classic and HPOS orders screens and reads billing phone from HPOS address tables when enabled.

= How can I report a bug or request a feature? =
Open an issue on the plugin's [GitHub repository](https://github.com/hmbashar/QuickTools-for-WooCommerce).

== Changelog ==

= 1.0.0 =
* Initial release.
* Total Sold on products list, shop loop, and single product.
* Previous Orders column (billing phone) with legacy + HPOS support.
* Security and performance hardening: capability checks, escaped output, request-level query cache, conditional asset loading.

== Upgrade Notice ==

= 1.0.0 =
Initial release of QuickTools for WooCommerce.
