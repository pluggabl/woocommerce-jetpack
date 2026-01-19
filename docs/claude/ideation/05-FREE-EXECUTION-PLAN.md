# Booster Free: Onboarding Improvements Execution Plan

## Overview

**Branch**: `onboarding-phase8`
**Release**: v7.10.0
**Sessions**: 4

This document is the single source of truth for implementation. All session prompts reference this file.

---

## Session Summary

| Session | Theme | Items | Files | Creates Branch? | Creates PR? |
|---------|-------|-------|-------|-----------------|-------------|
| 1 | Core Activation Flow | A1, A2, G1, E4 | 3-4 | Yes | No |
| 2 | Modal Polish | B4, D4 | 4 | No (pulls) | No |
| 3 | Settings Enhancements | C2, C1 | 6-8 | No (pulls) | No |
| 4 | Upgrade + Release | F1, readme, version | 6-7 | No (pulls) | Yes |

---

## Session 1: Core Activation Flow

### Items to Implement (4 items)

| ID | Name | Description |
|----|------|-------------|
| A1 | Activation Redirect | Redirect to Getting Started page after first activation |
| A2 | Modal on Booster Pages Only | Modal only shows on `page=wcj-*` URLs |
| G1 | Re-open Onboarding Button | Button on Getting Started page to re-open modal |
| E4 | First Win Celebration | Special message on first-ever goal apply |

### Primary Files (3-4 files)

```
woocommerce-jetpack.php                          # Activation hook (A1)
includes/core/wcj-loader.php                     # Redirect logic (A1)
includes/admin/class-booster-onboarding.php      # Modal trigger, first-win, re-open (A2, G1, E4)
```

### Definition of Done

**A1: Activation Redirect**
- [ ] `register_activation_hook` sets transient `wcj_activation_redirect`
- [ ] `wcj-loader.php` checks transient on `admin_init`, redirects to `wcj-getting-started`
- [ ] Transient deleted after redirect (one-time only)
- [ ] Reactivation (disable/enable) does NOT trigger redirect
- [ ] Redirect includes `?modal=onboarding` param to auto-open modal

**A2: Modal on Booster Pages Only**
- [ ] `maybe_show_onboarding_modal()` checks `$_GET['page']` starts with `wcj-`
- [ ] Modal does NOT appear on Posts, Pages, Dashboard, etc.
- [ ] Modal still appears on Getting Started, Booster Dashboard, all module pages

**G1: Re-open Onboarding Button**
- [ ] Button visible on Getting Started page
- [ ] Button text: "Open Setup Guide" or similar
- [ ] Clicking button opens modal (sets URL param or uses JS trigger)
- [ ] Works even if modal was previously dismissed

**E4: First Win Celebration**
- [ ] On first-ever goal apply, success screen shows special message
- [ ] Message example: "You did it! Your first Booster feature is now active."
- [ ] Uses user meta or option to track "first goal applied"
- [ ] Does NOT show on subsequent goal applies

### Commit Messages
```
git commit -m "A1: Add activation redirect to Getting Started page"
git commit -m "A2: Restrict onboarding modal to Booster admin pages"
git commit -m "G1: Add re-open onboarding button to Getting Started"
git commit -m "E4: Add first-win celebration message"
```

### Test Checkpoint
Before pushing, verify:
- [ ] Fresh install → Activate → redirects to Getting Started with modal
- [ ] Disable/re-enable plugin does NOT redirect
- [ ] Go to Posts page → no modal appears
- [ ] Go to Booster Dashboard → modal can appear
- [ ] Re-open button works on Getting Started page
- [ ] First goal apply shows celebration; second does not

---

## Session 2: Modal Polish

### Items to Implement (2 items)

| ID | Name | Description |
|----|------|-------------|
| B4 | Modal Search | Search box to filter goal tiles in real-time |
| D4 | Success Toast | Toast notification after saving module settings |

### Primary Files (4 files)

```
includes/admin/views/onboarding-modal.php        # Search box HTML (B4)
assets/js/admin/booster-onboarding.js            # Search filter JS (B4)
assets/css/admin/booster-onboarding.css          # Search styling (B4)
includes/core/class-wcj-admin.php                # Settings save toast (D4)
```

### Definition of Done

**B4: Modal Search**
- [ ] Search input at top of Quick Setup tab in modal
- [ ] Placeholder text: "Search goals..." or similar
- [ ] Typing filters goal tiles in real-time (client-side JS)
- [ ] Matches against: title, subtitle, module names in tags
- [ ] Empty search shows all tiles
- [ ] Search is case-insensitive
- [ ] No AJAX required (filter existing DOM elements)

**D4: Success Toast**
- [ ] After settings save completes, show toast notification
- [ ] Toast appears at top of content area or fixed position
- [ ] Message: "Settings saved successfully" or similar
- [ ] Auto-dismisses after 5 seconds
- [ ] Styled to match WordPress admin or Booster brand
- [ ] Does not block user interaction

### Commit Messages
```
git commit -m "B4: Add search box to onboarding modal"
git commit -m "D4: Add success toast after settings save"
```

### Test Checkpoint
Before pushing, verify:
- [ ] Modal has search box visible at top of goal tiles
- [ ] Typing "invoice" shows only invoice-related goals
- [ ] Typing "cart" shows cart-related goals
- [ ] Clearing search shows all goals again
- [ ] Save any module settings → toast appears
- [ ] Toast disappears after ~5 seconds

---

## Session 3: Settings Enhancements

### Items to Implement (2 items)

| ID | Name | Description |
|----|------|-------------|
| C2 | Quick Start Presets | Add presets to 5 modules |
| C1 | Help Text | Add help_text to 20+ settings |

### Primary Files

**C2: Quick Start Presets (6 files)**
```
includes/wcj-quick-start-presets.php                    # Preset definitions
includes/settings/wcj-settings-pdf-invoicing.php        # Render call
includes/settings/wcj-settings-order-numbers.php        # Render call
includes/settings/wcj-settings-wishlist.php             # Render call
includes/settings/wcj-settings-checkout-core-fields.php # Render call
includes/settings/wcj-settings-related-products.php     # Render call
```

**C1: Help Text (8 files)**
```
includes/settings/wcj-settings-cart-abandonment.php
includes/settings/wcj-settings-sales-notifications.php
includes/settings/wcj-settings-pdf-invoicing.php
includes/settings/wcj-settings-wishlist.php
includes/settings/wcj-settings-multicurrency.php
includes/settings/wcj-settings-product-addons.php
includes/settings/wcj-settings-order-numbers.php
includes/settings/wcj-settings-checkout-core-fields.php
```

### Definition of Done

**C2: Quick Start Presets**
- [ ] 5 new entries in `wcj_quick_start_get_all_presets()`:
  - `pdf_invoicing`
  - `order_numbers`
  - `wishlist`
  - `checkout_core_fields`
  - `related_products`
- [ ] Each preset has: module_id, module_name, headline, presets array
- [ ] Each preset option has: id, label, tagline, steps (2-3 items), settings array
- [ ] `wcj_quick_start_render_box()` called at top of each settings file
- [ ] Clicking preset button applies settings correctly
- [ ] Form fields update to show applied values

**C1: Help Text**
- [ ] At least 20 settings have `help_text` field added
- [ ] Priority modules and settings:
  - Cart Abandonment: timing, email template settings
  - Sales Notifications: duration, position, styling
  - PDF Invoicing: template, numbering format
  - Multicurrency: exchange rates, rounding
  - Product Add-ons: pricing, display options
  - Order Numbers: format, prefix/suffix
  - Wishlist: button text, display options
  - Checkout Core Fields: field visibility, required
- [ ] Help text is plain English (no `wcj_` option names visible to user)
- [ ] Help text is 1-2 sentences max
- [ ] Tooltip appears on (i) icon hover
- [ ] No PHP errors on any settings page

### Commit Messages
```
git commit -m "C2: Add Quick Start presets for PDF Invoicing, Order Numbers, Wishlist, Checkout Fields, Related Products"
git commit -m "C1: Add help text to 20+ settings across key modules"
```

### Test Checkpoint
Before pushing, verify:
- [ ] PDF Invoicing settings page shows Quick Start box
- [ ] Order Numbers settings page shows Quick Start box
- [ ] Wishlist settings page shows Quick Start box
- [ ] Checkout Core Fields settings page shows Quick Start box
- [ ] Related Products settings page shows Quick Start box
- [ ] Each preset applies settings correctly when clicked
- [ ] At least 20 settings show (i) tooltip with help text
- [ ] Help text is readable and helpful
- [ ] No PHP errors or warnings

---

## Session 4: Upgrade Path + Release

### Items to Implement (3 items)

| ID | Name | Description |
|----|------|-------------|
| F1 | Upgrade Blocks | Add upgrade comparison blocks to 5 modules |
| -- | readme.txt | Changelog entry for v7.10.0 |
| -- | Version Bump | Update version in plugin header and readme.txt |

### Primary Files

**F1: Upgrade Blocks (6 files)**
```
includes/class-wcj-upgrade-blocks.php                   # Config entries
includes/settings/wcj-settings-multicurrency.php        # Render call
includes/settings/wcj-settings-pdf-invoicing.php        # Render call
includes/settings/wcj-settings-product-addons.php       # Render call
includes/settings/wcj-settings-preorders.php            # Render call
includes/settings/wcj-settings-sales-notifications.php  # Render call
```

**Release Files (2 files)**
```
readme.txt                                              # Changelog + stable tag
woocommerce-jetpack.php                                 # Plugin header version
```

### Definition of Done

**F1: Upgrade Blocks**
- [ ] 5 new entries in `wcj_get_upgrade_blocks_config()`:
  - `multicurrency`
  - `pdf_invoicing`
  - `product_addons`
  - `preorders`
  - `sales_notifications`
- [ ] Each config has: module_id, enabled (true), lite_label, headline, benefits (3-4 items), comparison_url, upgrade_url
- [ ] Benefits are specific and compelling:
  - Multicurrency: "Unlimited currencies", "Automatic exchange rates", etc.
  - PDF Invoicing: "Custom templates", "Bulk PDF generation", etc.
  - Product Add-ons: "Unlimited add-on groups", "Conditional logic", etc.
  - Pre-orders: "Automatic notifications", "Partial payments", etc.
  - Sales Notifications: "Custom styling", "Advanced targeting", etc.
- [ ] `wcj_render_upgrade_block()` called at top of each settings file
- [ ] Block appears at top of settings page with blue info styling
- [ ] "Compare" and "Upgrade" links work correctly
- [ ] Click tracking works via existing `wcj_log_upgrade_block_click()`

**readme.txt Changelog**
- [ ] New entry at TOP of `== Changelog ==` section:
```
= 7.10.0 - DD/MM/YYYY =
* Added - Activation redirect to Getting Started page for new installs
* Added - Search box in onboarding modal for quick goal discovery
* Added - "Re-open Onboarding" button on Getting Started page
* Added - First-win celebration message after applying first goal
* Added - Success toast notification after saving module settings
* Added - Quick Start presets for PDF Invoicing, Order Numbers, Wishlist, Checkout Fields, Related Products
* Added - Help text tooltips for 20+ settings across key modules
* Added - Upgrade comparison blocks for Multicurrency, PDF Invoicing, Product Add-ons, Pre-orders, Sales Notifications
* Improved - Onboarding modal now only appears on Booster admin pages
```
- [ ] Replace `DD/MM/YYYY` with actual release date

**Version Bump**
- [ ] `readme.txt` → Update `Stable tag: 7.10.0`
- [ ] `woocommerce-jetpack.php` → Update `Version: 7.10.0` in plugin header comment
- [ ] Search for any other version constants (e.g., `WCJ_VERSION`) and update if found

### Commit Messages
```
git commit -m "F1: Add upgrade blocks for Multicurrency, PDF Invoicing, Product Add-ons, Pre-orders, Sales Notifications"
git commit -m "Release: Update readme.txt changelog and bump version to 7.10.0"
```

### Test Checkpoint (Full End-to-End)
Before creating PR, test ALL features from Sessions 1-4:

**Session 1 features:**
- [ ] Fresh install activation redirects to Getting Started
- [ ] Modal only appears on Booster pages
- [ ] Re-open button works
- [ ] First-win celebration shows once

**Session 2 features:**
- [ ] Modal search filters goals
- [ ] Success toast shows after save

**Session 3 features:**
- [ ] Quick Start presets work on 5 modules
- [ ] Help text tooltips appear on 20+ settings

**Session 4 features:**
- [ ] Upgrade blocks appear on 5 modules
- [ ] Compare and Upgrade links work
- [ ] readme.txt has correct changelog
- [ ] Version is 7.10.0 everywhere

---

## PR Template

After Session 4 completes all tests, create PR:

```bash
gh pr create --title "v7.10.0: Onboarding Phase 8 - Activation Flow, Quick Start, Help Text, Upgrade Blocks" --body "$(cat <<'EOF'
## Summary

Implements onboarding improvements from `docs/claude/ideation/03-FREE-ONBOARDING-TOP-10-PRIORITIES.md`

Release version: **7.10.0**

### Session 1: Core Activation Flow
- **A1**: Activation redirect to Getting Started page
- **A2**: Modal only on Booster pages (less intrusive)
- **G1**: Re-open onboarding button on Getting Started
- **E4**: First-win celebration message

### Session 2: Modal Polish
- **B4**: Search box in onboarding modal
- **D4**: Success toast after settings save

### Session 3: Settings Enhancements
- **C2**: Quick Start presets for 5 modules (PDF Invoicing, Order Numbers, Wishlist, Checkout Fields, Related Products)
- **C1**: Help text for 20+ settings across 8 modules

### Session 4: Upgrade Path + Release
- **F1**: Upgrade blocks for 5 modules (Multicurrency, PDF Invoicing, Product Add-ons, Pre-orders, Sales Notifications)
- readme.txt changelog
- Version bump to 7.10.0

## Test Plan

### Fresh Install Flow
- [ ] Install plugin → Activate → redirects to Getting Started
- [ ] Modal auto-opens on Getting Started page
- [ ] Disable/re-enable plugin does NOT redirect

### Modal Behavior
- [ ] Modal appears on Booster pages (wcj-*)
- [ ] Modal does NOT appear on Posts, Pages, WP Dashboard
- [ ] Search box filters goal tiles correctly
- [ ] Re-open button works after dismissing modal

### First Win + Feedback
- [ ] First goal apply shows celebration message
- [ ] Second goal apply does NOT show celebration
- [ ] Settings save shows success toast
- [ ] Toast auto-dismisses after ~5 seconds

### Quick Start Presets
- [ ] PDF Invoicing has Quick Start box
- [ ] Order Numbers has Quick Start box
- [ ] Wishlist has Quick Start box
- [ ] Checkout Core Fields has Quick Start box
- [ ] Related Products has Quick Start box
- [ ] Clicking preset applies settings correctly

### Help Text
- [ ] 20+ settings show (i) tooltip icon
- [ ] Hovering shows help text
- [ ] No PHP errors on settings pages

### Upgrade Blocks
- [ ] Multicurrency shows upgrade block
- [ ] PDF Invoicing shows upgrade block
- [ ] Product Add-ons shows upgrade block
- [ ] Pre-orders shows upgrade block
- [ ] Sales Notifications shows upgrade block
- [ ] Compare and Upgrade links work

### Release Prep
- [ ] readme.txt changelog is accurate
- [ ] Version is 7.10.0 in readme.txt and plugin header

## Documentation
- `docs/claude/ideation/03-FREE-ONBOARDING-TOP-10-PRIORITIES.md` - Prioritization
- `docs/claude/ideation/05-FREE-EXECUTION-PLAN.md` - Execution plan

🤖 Generated with [Claude Code](https://claude.com/claude-code)
EOF
)"
```

---

## Reference Docs

| Doc | Purpose |
|-----|---------|
| `docs/claude/ideation/00-FREE-ONBOARDING-FRICTION-POINTS.md` | Problems we're solving |
| `docs/claude/ideation/01-FREE-ONBOARDING-IDEA-BACKLOG.md` | Full idea list with specs |
| `docs/claude/ideation/03-FREE-ONBOARDING-TOP-10-PRIORITIES.md` | Why these items ranked highest |
| `docs/claude/releases/03-FREE-ONBOARDING-BUILDING-BLOCKS.md` | Existing patterns to follow |

---

## Risk Mitigation

| Risk | Session | Mitigation |
|------|---------|------------|
| Activation redirect conflicts with other plugins | 1 | Use unique transient name; delete immediately after use |
| Modal page check too restrictive | 1 | Test all `page=wcj-*` URLs; use `strpos()` not exact match |
| Search JS breaks on old browsers | 2 | Use vanilla JS; test on common browsers |
| Toast blocks interaction | 2 | Use fixed position that doesn't overlay content |
| Quick Start presets apply wrong values | 3 | Test each preset individually; verify form fields update |
| Help text missing on some tooltips | 3 | Manual check of all 20+ settings after adding |
| Upgrade block styling inconsistent | 4 | Compare to existing blocks (Wishlist, Cart Abandonment, Swatches) |
| Version mismatch | 4 | Search entire codebase for version strings before committing |
