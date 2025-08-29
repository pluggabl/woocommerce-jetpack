# Pre-Orders Lite - Developer Notes

## Overview
Pre-Orders Lite provides basic pre-order functionality for WooCommerce stores using Booster for WooCommerce.

## Key Meta Keys (Reused from Elite)
- `_wcj_product_preorder_enabled` - Product-level enable/disable (yes/no)
- `_wcj_product_preorder_release_date` - Optional release date (YYYY-MM-DD)
- `_wcj_preorder` - Order-level flag (yes/no)
- `_wcj_preorder_release_date` - Order-level release date copy

## Limit Enforcement
- Uses `apply_filters('booster_option', 1, wcj_get_option('wcj_preorders_lite_limit', 1))`
- Default limit: 1 product
- Enforced on product save in meta box
- Shows upgrade CTA when limit exceeded

## Elite Coexistence
- Checks `class_exists('WCJ_Preorders')` and `wcj_is_module_enabled('preorders')`
- If Elite active, Lite shows admin notice and exits early
- Elite includes disable_lite_version() method to remove Lite hooks

## Frontend Hooks
- `woocommerce_product_single_add_to_cart_text` - Button text override
- `woocommerce_product_add_to_cart_text` - Loop button text
- `woocommerce_product_variable_add_to_cart_text` - Variable product text
- `woocommerce_before_add_to_cart_form` - Pre-order message
- `woocommerce_product_is_purchasable` - Allow out-of-stock purchase
- `woocommerce_variation_is_purchasable` - Allow out-of-stock variations
- `woocommerce_checkout_order_processed` - Set order meta

## Upgrade Path
Seamless - Elite uses same meta keys, so existing Lite pre-orders continue working.

## Files Created
- `includes/class-wcj-preorders-lite.php` - Main module class
- `includes/settings/wcj-settings-preorders-lite.php` - Settings configuration
- `includes/css/wcj-preorders-lite.css` - Minimal styling

## Files Modified
- `includes/admin/wcj-modules-cats.php` - Added module to shipping_and_orders category
- `readme.txt` - Added Pre-Orders Lite to feature list

## Elite Repository Changes
- `includes/class-wcj-preorders.php` - Added disable_lite_version() method for coexistence
