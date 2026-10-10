# QuickTools for WooCommerce

Lightweight WooCommerce tools for product sales visibility and previous-order insights.

## Features

- **Sold column (Products)** — Sortable units-sold column on the Products list (all product types via `get_total_sales()`).
- **Total Sold (Storefront)** — Shows total units sold in shop/archive loops and on single product pages.
- **Previous Orders (Orders)** — Status count badges for prior orders sharing the same billing phone (Total, Processing, On Hold, Pending, Cancelled). Supports classic `shop_order` lists and HPOS.
- **No settings required** — Activate with WooCommerce; features load automatically.
- **Lean assets** — CSS only, enqueued on relevant product/order screens.

## Requirements

- WordPress 5.0+
- PHP 7.4+
- WooCommerce 6.0+

## Installation

1. Upload `quicktools-for-woocommerce` to `/wp-content/plugins/`.
2. Activate the plugin in WordPress.
3. Ensure WooCommerce is active.

## Security & performance notes

- Admin column output is gated with `edit_products` / `edit_shop_orders`.
- All displayed values are escaped; phone lookups use prepared SQL.
- Previous-order counts are cached per request (by phone + excluded order ID) to reduce repeat queries on the orders list.
- Debug `error_log` of phone numbers / SQL was removed.
- Frontend/admin CSS loads only on WooCommerce product views and product/order list screens.

## Roadmap

Planned improvements (not implemented yet):

1. **Settings page** — Toggle Sold / Total Sold / Previous Orders independently.
2. **Matching options** — Match previous orders by email, phone, or both; optional phone normalization.
3. **More status badges** — Completed, refunded, failed (configurable).
4. **Batch prefetch** — Prefetch counts for all phones on the current orders page in one query.
5. **Variable product breakdown** — Optional per-variation sold counts in admin.

## Changelog

### 1.0.0

- Initial release with Total Sold (admin + storefront) and Previous Orders column.
- HPOS-compatible Previous Orders (address-table phone lookup + HPOS list hooks).
- Security and performance hardening.

## License

GPLv2 or later. See [LICENSE](LICENSE).

---

[WordPress Plugin Directory](https://wordpress.org/plugins/quicktools-for-woocommerce/) · [GitHub](https://github.com/hmbashar/QuickTools-for-WooCommerce)
