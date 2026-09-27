# 8.5 Wishlist and shipping amendment

## Merchant choices

Removal after a successful Wishlist cart addition remains enabled by default, including for existing stores with no saved value. In Wishlist > General, disable **Remove from wishlist after adding to cart** to retain saved items. This affects guests and signed-in customers. Explicit Remove remains visible and keyboard reachable without external icon fonts; failed additions must not delete saved products. Upgrading alone does not enable preservation.

Wishlist archive controls remain Elite-only. In Elite, enable Archives and place `[wcj_wishlist_button]` in a Shortcode widget within a custom loop-item template, outside product links. The current product must resolve separately for every card. Do not hard-code one ID into a reusable card. Back up the template, then check separate widgets, pagination/load-more, keyboard and mobile. Installation never edits merchant templates. Synthetic renderer tests do not certify Elementor Pro or any merchant template.

## Category OR minimum free shipping

Back up settings and record the applicable zone/method IDs before an authorized change. Use two native Free shipping instances in that zone:

1. Category route: no native requirement; Booster category Include on this instance; require any matching item, not every item.
2. Minimum route: confirmed native threshold and coupon/discount policy; no category Include; category Exclude for the first route's category prevents duplicate free options.
3. Preserve product exclusions, roles, other methods and zones. Review Shipping Options hide-if-free and free-shipping-by-product interactions. Choose whole-cart versus package scope deliberately. Native minimum eligibility normally uses the cart, even for split packages.
4. Invalidate shipping sessions/rate caches after approved setting changes. Test below, at and above the threshold, mixed carts, coupons, taxes, roles, zones and split packages. No global override of WooCommerce eligibility is installed.

## Regressions and boundaries

On an isolated disposable site only, run the tracked `tests/wishlist-preservation-integration.php` and `tests/shipping-or-integration.php` with `BOOSTER_INVOICE_SETUP_ISOLATED_QA=1 wp eval-file`. Elite also has `tests/wishlist-loop-integration.php`. Browser cookies, reloads, actual theme/builder behavior and merchant policy acceptance are separate gates.

Restore the prior exact ZIP and backed-up settings/templates for rollback after validating data compatibility; do not delete saved Wishlist metadata. Public 8.5 is not released by this amendment. Paid shipped-baseline upgrades, customer staging permission, human acceptance and release gates remain separate from synthetic tests.
