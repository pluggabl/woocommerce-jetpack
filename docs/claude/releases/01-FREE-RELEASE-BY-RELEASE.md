# Booster Free: Release-by-Release Onboarding Changes

## v7.3.2 (October 25, 2025)

**Category**: Foundation / Setup

### Onboarding Changes
- Initial foundation for onboarding infrastructure
- `Booster_Onboarding` class instantiated from `class-wcj-admin.php`

### Key Files
| File | Changes |
|------|---------|
| `includes/core/class-wcj-admin.php:60-63` | Instantiates `Booster_Onboarding` class |

### Screens Affected
- Admin initialization (backend only)

---

## v7.4.0 (October 30, 2025)

**Category**: First-run / Setup Flow, Navigation, Presets

### Onboarding Changes
- **NEW**: Guided onboarding modal with 4 one-click goals
- **NEW**: 3-step progress bar (Choose → Review → Complete)
- **NEW**: "Getting Started" submenu under Booster Dashboard
- **NEW**: Snapshot/undo system for safe rollback
- **NEW**: Goal tiles with icons and module tags
- **NEW**: Success screen with "next steps" deep-links
- **NEW**: Keyboard accessible, mobile-friendly modal

### Goals Introduced (Free tier)
| Goal ID | Title | Modules Enabled |
|---------|-------|-----------------|
| `grow_sales` | Grow sales now | Sales Notifications |
| `work_smarter` | Work smarter (backend) | Order Numbers, Admin Orders List |
| `go_global` | Go global (starter) | Currencies |
| `professional_invoices` | Professional invoices | PDF Invoicing |
| `boost_conversions_free` | Boost conversions | Product Add-ons, Related Products |
| `better_checkout_basics` | Better checkout (basics) | Checkout Core Fields, Button Labels |
| `store_essentials_quick` | Store essentials | Order Numbers, Product Tabs |

### Key Files
| File | Function/Class | Purpose |
|------|----------------|---------|
| `includes/admin/class-booster-onboarding.php` | `Booster_Onboarding` | Main controller |
| `includes/admin/class-booster-onboarding.php:38-50` | `__construct()` | Hooks registration |
| `includes/admin/class-booster-onboarding.php:287-309` | `ajax_apply_goal()` | Goal application |
| `includes/admin/class-booster-onboarding.php:678-715` | `undo_goal()` | Snapshot restoration |
| `includes/admin/onboarding-map.php` | N/A | Goal definitions array |
| `includes/admin/views/onboarding-modal.php` | N/A | Modal HTML template |
| `assets/css/admin/booster-onboarding.css` | N/A | Modal styling |
| `assets/js/admin/booster-onboarding.js` | N/A | Modal interactions |

### Screens Affected
- Any admin screen (modal appears on first run)
- Booster Dashboard → Getting Started page
- Module settings pages (via next-step links)

---

## v7.5.0 (November 5, 2025)

**Category**: Presets / Blueprints, Navigation

### Onboarding Changes
- **NEW**: Blueprints feature - outcome-oriented presets that bundle multiple goals
- **NEW**: Quick Setup ↔ Blueprints switcher (segmented control)
- **NEW**: "Next Steps" guidance after blueprint application
- **NEW**: "Applied" badges for completed blueprints
- **NEW**: Dedicated Undo for blueprints
- **NEW**: Pro note with Elite comparison link

### Blueprints Introduced
| Blueprint ID | Title | Goals Bundled | Outcome |
|--------------|-------|---------------|---------|
| `recover_lost_sales` | Recover Lost Sales | Cart Abandonment | Set up recovery emails |
| `boost_aov` | Boost Average Order Value | Product Add-ons, Related Products | Increase cart size |
| `sell_internationally` | Sell Internationally | Store Essentials, Checkout Basics, Go Global | International readiness |

### Key Files
| File | Function/Class | Purpose |
|------|----------------|---------|
| `includes/admin/onboarding-blueprints.php` | N/A | Blueprint definitions |
| `includes/admin/class-booster-onboarding.php:792-844` | `ajax_apply_blueprint()` | Blueprint application |
| `includes/admin/class-booster-onboarding.php:908-954` | `ajax_undo_blueprint()` | Blueprint undo |
| `includes/admin/views/onboarding-modal.php:23-29` | N/A | Segmented control tabs |
| `includes/admin/views/onboarding-modal.php:93-122` | N/A | Blueprints grid |

### Screens Affected
- Onboarding modal (new Blueprints tab)
- Getting Started page (analytics for blueprints)

---

## v7.6.0 (November 17, 2025)

**Category**: Navigation, Presets

### Onboarding Changes
- **EXPANDED**: Onboarding map now includes 13 goals (was 7)
- **NEW**: B2B Store goal package (11 modules)
- **NEW**: INTL Store goal package (3 modules)
- **NEW**: Merchant Getting Started package (5 modules)
- **NEW**: Merchant AOV Increase package (3 modules)
- **NEW**: Merchant Run Store Efficiently package (7 modules)
- **NEW**: Recover Lost Sales goal (standalone cart abandonment)

### New Goals in v7.6.0
| Goal ID | Modules Count | Use Case |
|---------|--------------|----------|
| `recover_lost_sales_goal` | 1 | Cart abandonment setup |
| `b2b_store` | 11 | Wholesale, role-based pricing, EU VAT |
| `intl_Store` | 3 | Currency exchange, country pricing |
| `merchant_getting_started` | 5 | Input fields, swatches, checkout files |
| `merchant_aov_increase` | 3 | Coupons, URL coupons, sale flash |
| `merchant_run_their_store_efficiently` | 7 | Export, admin tools, XML feeds |

### Key Files
| File | Lines Changed | Purpose |
|------|---------------|---------|
| `includes/admin/onboarding-map.php:193-507` | +314 lines | New goal definitions |
| `includes/admin/class-booster-onboarding.php:443-461` | N/A | Module ID handlers |

### Screens Affected
- Onboarding modal (more goal tiles)
- Getting Started page (more completed goals tracking)

---

## v7.7.0 (November 26, 2025)

**Category**: Help Text / Tooltips, Settings Clarity

### Onboarding Changes
- **NEW**: `enhance_settings_for_module()` function for help text injection
- **NEW**: `help_text` and `friendly_label` fields supported in settings arrays
- **IMPROVED**: Wishlist settings now have contextual help text
- **IMPROVED**: Cart Abandonment settings with clearer descriptions
- **IMPROVED**: Multicurrency settings enhanced
- **IMPROVED**: Product Add-ons settings enhanced
- **IMPROVED**: Sales Notifications settings enhanced

### Help Text Pattern
```php
array(
    'id'             => 'wcj_wishlist_enabled_single',
    'title'          => __( 'Enable/Disable', 'woocommerce-jetpack' ),
    'help_text'      => __( 'Show wishlist buttons on individual product pages...', 'woocommerce-jetpack' ),
    'friendly_label' => __( 'Button Text', 'woocommerce-jetpack' ),
    // ...
)
```

### Key Files
| File | Function | Purpose |
|------|----------|---------|
| `includes/core/class-wcj-admin.php:96-130` | `enhance_settings_for_module()` | Injects help tooltips |
| `includes/settings/wcj-settings-wishlist.php:73-84` | N/A | Help text examples |
| `includes/settings/wcj-settings-cart-abandonment.php` | N/A | Enhanced descriptions |
| `includes/settings/wcj-settings-multicurrency.php` | N/A | Enhanced descriptions |
| `includes/settings/wcj-settings-product-addons.php` | N/A | Enhanced descriptions |

### Screens Affected
- All module settings pages that use `help_text` fields
- Specifically: Wishlist, Cart Abandonment, Multicurrency, Product Add-ons

---

## v7.8.0 (December 3, 2025)

**Category**: Presets, First-run Experience

### Onboarding Changes
- **NEW**: Quick Start preset system on module settings pages
- **NEW**: One-click preset buttons above settings forms
- **NEW**: Step-by-step checklist showing what preset will do
- **NEW**: "See advanced options" link for power users
- **NEW**: Toast confirmation after preset applied
- **NEW**: Filterable preset system via `wcj_quick_start_presets` filter

### Quick Start Presets Introduced
| Module | Preset | What It Does |
|--------|--------|--------------|
| `cart_abandonment` | Balanced | 1 email after 1 hour, no coupon |
| `sales_notifications` | Balanced | 6s display, 30s gap, bottom-right |
| `product_addons` | Balanced | Gift wrapping checkbox for all products |

### Key Files
| File | Function | Purpose |
|------|----------|---------|
| `includes/wcj-quick-start-presets.php:62-164` | `wcj_quick_start_get_all_presets()` | Preset definitions |
| `includes/wcj-quick-start-presets.php:177-194` | `wcj_quick_start_get_presets_for_module()` | Get presets for module |
| `includes/admin/wcj-quick-start-admin.php:32-126` | `wcj_quick_start_render_box()` | Render preset UI |
| `includes/admin/wcj-quick-start-admin.php:138-161` | `wcj_quick_start_enqueue_admin_scripts()` | Enqueue JS |
| `includes/js/wcj-quick-start.js` | N/A | Preset application JS |

### Screens Affected
- Cart Abandonment settings page
- Sales Notifications settings page
- Product Add-ons settings page

---

## v7.9.0 (December 11, 2025)

**Category**: Lite vs Elite Clarity, Upgrade Prompts

### Onboarding Changes
- **NEW**: Upgrade Blocks for Lite modules with Elite comparison
- **NEW**: Benefits list showing what Elite unlocks
- **NEW**: "See full comparison" and "Upgrade to Elite" CTAs
- **NEW**: Click tracking for upgrade block interactions
- **NEW**: Upgrade Clicks Log admin page for analytics
- **NEW**: Filterable config via `wcj_upgrade_blocks_config` filter

### Upgrade Blocks Introduced
| Module | Lite Label | Key Elite Benefits |
|--------|------------|-------------------|
| `cart_abandonment` | Cart Abandoned Lite | 3 emails, auto coupons, role exclusions |
| `wishlist` | Wishlist Lite | Multiple lists, email reminders, styling |
| `product_variation_swatches` | Variation Swatches Lite | Image swatches, per-product, tooltips |

### Key Files
| File | Function | Purpose |
|------|----------|---------|
| `includes/class-wcj-upgrade-blocks.php:28-84` | `wcj_get_upgrade_blocks_config()` | Block configurations |
| `includes/class-wcj-upgrade-blocks.php:339-432` | `wcj_render_upgrade_block()` | Render upgrade block |
| `includes/class-wcj-upgrade-blocks.php:125-167` | `wcj_log_upgrade_block_click()` | Click analytics |
| `includes/class-wcj-upgrade-blocks.php:271-325` | `wcj_render_upgrade_clicks_log_page()` | Admin log page |
| `includes/settings/wcj-settings-wishlist.php:19-22` | N/A | Upgrade block call |
| `includes/settings/wcj-settings-cart-abandonment.php` | N/A | Upgrade block call |

### Screens Affected
- Wishlist module settings page
- Cart Abandonment module settings page
- Variation Swatches module settings page
- Booster Dashboard → Upgrade Clicks Log page
