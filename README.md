# QuickTools for WooCommerce

Lightweight WooCommerce tools for product sales visibility and previous-order insights.

## Features

- **Control Center** — WooCommerce → QuickTools admin panel with per-feature toggles.
- **Sold column (Products)** — Sortable units-sold badge column on the Products list.
- **Total Sold (Storefront)** — Premium chip/card UI on shop loops and single product pages (separately toggleable).
- **Previous Orders (Orders)** — Status badges for prior orders sharing the same billing phone (with/without country code). Supports classic lists and HPOS.
- **Lean by default** — Disabled features are not loaded; CSS/JS enqueue only on relevant screens.

## Requirements

- WordPress 5.0+
- PHP 7.4+
- WooCommerce 6.0+

## Installation

1. Upload `quicktools-for-woocommerce` to `/wp-content/plugins/`.
2. Activate the plugin in WordPress.
3. Ensure WooCommerce is active.

## Security & performance notes

- Settings require `manage_woocommerce`; saved through the Settings API with sanitization.
- Admin column output is gated with `edit_products` / `edit_shop_orders`.
- All displayed values are escaped; phone lookups use prepared SQL.
- Previous-order counts are cached per request (by phone core + excluded order ID).
- Feature classes and assets load only when that feature is enabled and the screen needs them.

## Roadmap

Planned improvements (not implemented yet):

1. **Matching options** — Match previous orders by email, phone, or both.
2. **More status badges** — Completed, refunded, failed (configurable).
3. **Batch prefetch** — Prefetch counts for all phones on the current orders page in one query.
4. **Variable product breakdown** — Optional per-variation sold counts in admin.

## Changelog

### 1.1.0

- Control Center admin panel with enable/disable toggles for each feature.
- Premium Total Sold UI (admin + storefront).
- Load/enqueue only what is enabled.

### 1.0.0

- Initial release with Total Sold (admin + storefront) and Previous Orders column.
- HPOS-compatible Previous Orders (address-table phone lookup + HPOS list hooks).
- Security and performance hardening.

## License

GPLv2 or later. See [LICENSE](LICENSE).

---

[WordPress Plugin Directory](https://wordpress.org/plugins/quicktools-for-woocommerce/) · [GitHub](https://github.com/hmbashar/QuickTools-for-WooCommerce)
