# Booster for WooCommerce - Repository Overview

**Key Takeaways:**
- This is the **FREE** version of Booster for WooCommerce
- Plugin slug: `woocommerce-jetpack` (historical name, branded as "Booster")
- Over 100 modules organized into 9 categories
- Modular architecture where each module is a PHP class extending `WCJ_Module`
- Settings stored in WordPress options table with `wcj_` prefix

---

## Repo Identity

| Property | Value |
|----------|-------|
| Plugin Name | Booster for WooCommerce |
| Main Plugin File | `woocommerce-jetpack.php` |
| Version | 7.9.0 |
| Author | Pluggabl LLC |
| Text Domain | `woocommerce-jetpack` |
| Current Branch | `master` |
| Last Commit | `53d1c3a04508cb3240913e42eef8fdbe2d074d9f` |
| License | GPL v3.0 |

### Plugin Type: FREE
This repository is the **free/lite** version distributed via WordPress.org. Evidence:
- Filename check: `basename( WCJ_FREE_PLUGIN_FILE ) === 'woocommerce-jetpack.php'`
- Constant: `WCJ_FREE_PLUGIN_FILE` defined
- Premium features gated via `apply_filters( 'booster_option', ... )`
- Upgrade prompts to "Booster Elite" throughout

### Related Products
The Booster ecosystem includes:
1. **Booster for WooCommerce** (Free) - This repo
2. **Booster Plus for WooCommerce** (`booster-plus-for-woocommerce.php`)
3. **Booster Elite for WooCommerce** (`booster-elite-for-woocommerce.php`)
4. **Booster Basic for WooCommerce** (`booster-basic-for-woocommerce.php`)
5. **Booster Pro for WooCommerce** (`booster-pro-for-woocommerce.php`) - deprecated

---

## Directory Structure Overview

```
woocommerce-jetpack/
├── woocommerce-jetpack.php     # Main plugin file (WP plugin header)
├── readme.txt                   # WordPress.org readme
├── changelog.txt                # Version history
├── version-details.json         # Current version info
├── wpml-config.xml             # WPML compatibility config
├── assets/                      # Frontend & admin assets (images, icons)
├── langs/                       # Translation files (.pot, .po, .mo)
├── tracking/                    # Plugin usage tracking (Wisdom)
└── includes/                    # Core PHP code
    ├── core/                    # Boot sequence, loader, admin core
    ├── classes/                 # Base classes (WCJ_Module, etc.)
    ├── functions/               # Helper functions (17 files)
    ├── settings/                # Settings arrays for each module
    │   ├── meta-box/           # Product/order meta box settings
    │   └── pdf-invoicing/      # PDF invoicing sub-settings
    ├── shortcodes/             # Shortcode classes
    ├── admin/                   # Admin UI, dashboard, onboarding
    │   └── views/              # Admin view templates
    ├── templates/              # Frontend templates
    ├── widgets/                # WordPress widgets
    ├── lib/                    # Third-party libraries
    │   ├── FPDI/              # PDF library
    │   ├── tcpdf/             # PDF generation
    │   ├── select2/           # Select2 UI library
    │   ├── hashids/           # Hashids library
    │   ├── PHPMathParser/     # Math expression parser
    │   ├── timepicker/        # Time picker JS
    │   └── wSelect/           # Select enhancement
    ├── css/                    # Plugin CSS
    ├── js/                     # Plugin JavaScript
    ├── gateways/               # Custom payment gateways
    ├── shipping/               # Custom shipping methods
    ├── emails/                 # Custom WooCommerce emails
    ├── exchange-rates/         # Currency exchange rate servers
    ├── export/                 # Export functionality
    ├── reports/                # Custom reports
    ├── tools/                  # Admin tools
    ├── input-fields/           # Product input field types
    ├── add-to-cart/            # Add to cart customizations
    ├── pdf-invoices/           # PDF invoicing submodules
    ├── price-by-country/       # Price by country logic
    ├── background-process/     # Background processing
    ├── cart-abandonment/       # Cart abandonment feature
    ├── mini-plugin/            # Mini plugin system
    └── class-wcj-*.php         # Module classes (100+)
```

---

## Key Paths Quick Reference

| Purpose | Path |
|---------|------|
| **Plugin Bootstrap** | `woocommerce-jetpack.php` |
| **Core Loader** | `includes/core/wcj-loader.php` |
| **Module Registry** | `includes/core/wcj-modules.php` |
| **Base Module Class** | `includes/classes/class-wcj-module.php` |
| **Admin Core** | `includes/core/class-wcj-admin.php` |
| **Module Categories** | `includes/admin/wcj-modules-cats.php` |
| **Settings Files** | `includes/settings/wcj-settings-*.php` |
| **Meta Box Settings** | `includes/settings/meta-box/wcj-settings-meta-box-*.php` |
| **Functions (Core)** | `includes/functions/wcj-functions-core.php` |
| **Functions (Booster)** | `includes/functions/wcj-functions-booster-core.php` |
| **Functions (Admin)** | `includes/functions/wcj-functions-admin.php` |
| **Shortcodes** | `includes/shortcodes/class-wcj-*-shortcodes.php` |
| **Widgets** | `includes/widgets/class-wcj-widget-*.php` |
| **Gating Logic** | `apply_filters( 'booster_option', ... )` in settings |
| **Upgrade Blocks** | `includes/class-wcj-upgrade-blocks.php` |
| **JS (Admin)** | `includes/js/admin-script.js`, `includes/js/wcj-admin.js` |
| **CSS (Admin)** | `includes/css/admin-style.css` |
| **Languages** | `langs/woocommerce-jetpack.pot` |
| **Templates** | `includes/templates/` |

---

## Third-Party Libraries

| Library | Location | Purpose |
|---------|----------|---------|
| TCPDF | `includes/lib/tcpdf/` | PDF generation |
| FPDI | `includes/lib/FPDI/` | PDF import/manipulation |
| Select2 | `includes/lib/select2/` | Enhanced select dropdowns |
| Hashids | `includes/lib/hashids/` | Short unique IDs |
| PHPMathParser | `includes/lib/PHPMathParser/` | Formula evaluation |
| jQuery Timepicker | `includes/lib/timepicker/` | Time picker UI |
| wSelect | `includes/lib/wSelect/` | Select enhancement |

---

## Module Categories (9 Total)

From `includes/admin/wcj-modules-cats.php`:

1. **Prices & Currencies** - Multicurrency, price conversion, wholesale pricing
2. **Button & Price Labels** - Add to cart labels, call for price, custom labels
3. **Products** - Listings, tabs, input fields, bookings, addons, swatches
4. **Cart & Checkout** - Cart customization, checkout fields, coupons, wishlist
5. **Payment Gateways** - Custom gateways, fees, restrictions
6. **Shipping & Orders** - Custom shipping, order numbers, statuses, preorders
7. **Marketing** - Sales notifications
8. **PDF Invoicing & Packing Slips** - Invoice generation and customization
9. **Emails & Misc.** - Email customization, reports, tools, admin bar

---

## Constants Defined

| Constant | Purpose | Defined In |
|----------|---------|------------|
| `WCJ_FREE_PLUGIN_FILE` | Path to main plugin file | `woocommerce-jetpack.php:59` |
| `WCJ_FREE_PLUGIN_PATH` | Plugin directory path | `includes/core/wcj-loader.php:27` |
| `WCJ_WC_VERSION` | WooCommerce version | `includes/core/wcj-constants.php:22` |
| `WCJ_IS_WC_VERSION_BELOW_3` | WC version check | `includes/core/wcj-constants.php:32` |
| `WCJ_PRODUCT_GET_PRICE_FILTER` | Price filter hook name | `includes/core/wcj-constants.php:72` |
| `WCJ_SESSION_TYPE` | Session handling type | `includes/core/wcj-constants.php:103` |
| `WCJ_VERSION_OPTION` | Version option name in DB | `includes/core/wcj-constants.php:114` |

---

## Notes for Future Claude Sessions

1. **This is a read-only analysis** - No production code was modified
2. **100+ modules** - Each module is a class in `includes/class-wcj-*.php`
3. **Settings are convention-based** - Each module's settings in `includes/settings/wcj-settings-{module-id}.php`
4. **Option prefix is `wcj_`** - All options start with this prefix
5. **Elite/Plus gating** - Look for `apply_filters( 'booster_option', ... )` pattern
