# Booster Free: Current Onboarding Journey (December 2025)

## Overview

This document maps the step-by-step experience a new Free user has today, from first install through configuration. Each step notes what improved since October 2025.

---

## Step 1: Install + Activate Plugin

### User Experience
1. User installs "Booster for WooCommerce" from WordPress.org
2. Clicks "Activate"
3. Plugin activates successfully

### Admin Screen Route
- Plugins → Add New → Search "Booster" → Install → Activate
- Or: Upload via Plugins → Add New → Upload Plugin

### Code Entry Points
| File | Function | Purpose |
|------|----------|---------|
| `woocommerce-jetpack.php:1-50` | Main plugin header | Plugin registration |
| `includes/core/wcj-loader.php:1-80` | `wcj_loader()` | Bootstrap loading |

### What Improved Since October
- No changes to activation flow itself
- What happens AFTER activation improved significantly (see Step 2)

### What Still Feels Confusing
- No activation redirect to a welcome/setup page
- User lands on Plugins list, must find Booster in menu

---

## Step 2: First Screen They See

### User Experience
1. After activation, user stays on Plugins page
2. User navigates via sidebar: WooCommerce → Booster Settings (or sees "Settings" link)
3. **NEW (v7.4.0)**: Onboarding modal automatically appears on first admin page visit
4. Modal says "Get set up fast. Pick a goal."

### Admin Screen Route
- Any admin page triggers modal on first visit
- Direct access: `admin.php?page=wcj-getting-started`

### Code Entry Points
| File | Function | Purpose |
|------|----------|---------|
| `includes/admin/class-booster-onboarding.php:143-151` | `maybe_show_onboarding_modal()` | Shows modal on first run |
| `includes/admin/class-booster-onboarding.php:135-138` | `should_show_modal()` | Checks if modal should appear |
| `includes/admin/views/onboarding-modal.php` | N/A | Modal HTML |

### What Improved Since October
- **v7.4.0**: Modal now auto-appears on first visit (was no first-run experience before)
- **v7.4.0**: 3-step progress indicator (Choose → Review → Complete)
- **v7.5.0**: Quick Setup ↔ Blueprints tab switcher

### What Still Feels Confusing
- Modal shows on ANY admin page, not just Booster pages (could be jarring)
- No dedicated "Welcome" page after activation
- User can close modal and may never see it again

---

## Step 3: How They Discover Modules

### User Experience
1. From modal: User sees goal tiles representing module bundles
2. From menu: WooCommerce → Booster Settings → Dashboard shows all module categories
3. Categories expand to show individual modules
4. Each module has enable/disable toggle

### Admin Screen Route
- Onboarding modal: Goal tiles show related modules
- Dashboard: `admin.php?page=wcj-dashboard`
- Module browser: `admin.php?page=wcj-plugins&wcj-cat={category}`

### Code Entry Points
| File | Function | Purpose |
|------|----------|---------|
| `includes/admin/wcj-modules-cats.php` | N/A | Category definitions |
| `includes/admin/views/onboarding-modal.php:57-90` | N/A | Goal tiles with module tags |
| `includes/core/class-wcj-admin.php:70` | `booster_menu()` | Admin menu registration |

### What Improved Since October
- **v7.4.0**: Goal tiles show which modules will be enabled (module tags)
- **v7.5.0**: Blueprints show "Includes:" list with goals bundled
- **v7.6.0**: 13 goal packages now cover most use cases

### What Still Feels Confusing
- 100+ modules can be overwhelming
- No "recommended for your store" suggestions based on store type
- Categories are functional but not outcome-oriented

---

## Step 4: How They Enable a Module

### User Experience

**Path A: Via Onboarding Modal**
1. Click a goal tile (e.g., "Professional invoices")
2. Review screen shows "We will turn on:" and "We will set:"
3. Click "Apply changes"
4. Success screen with "Next steps" links

**Path B: Via Dashboard**
1. Navigate to Dashboard
2. Find category (e.g., "PDF Invoicing")
3. Click module name
4. Toggle "Enable" checkbox
5. Click "Save changes"

### Admin Screen Route
- Modal apply: AJAX call to `admin-ajax.php?action=booster_apply_goal`
- Manual enable: `admin.php?page=wcj-plugins&section={module_id}`

### Code Entry Points
| File | Function | Purpose |
|------|----------|---------|
| `includes/admin/class-booster-onboarding.php:287-309` | `ajax_apply_goal()` | Modal goal application |
| `includes/admin/class-booster-onboarding.php:347-405` | `apply_goal()` | Applies settings |
| `includes/core/class-wcj-admin.php:137-166` | `wcj_save_module_settings()` | Manual save handler |

### What Improved Since October
- **v7.4.0**: One-click enable via goals (was manual toggle only)
- **v7.4.0**: Review screen before applying (transparency)
- **v7.4.0**: Undo snapshot created automatically
- **v7.8.0**: Quick Start presets on individual module pages

### What Still Feels Confusing
- Two paths (modal vs manual) might confuse users
- No confirmation of what enabling a module actually does to their store
- Quick Start only on 3 modules currently

---

## Step 5: How They Understand What a Setting Does

### User Experience
1. Navigate to module settings page
2. See setting label and description
3. **NEW (v7.7.0)**: Hover over (i) icon for help tooltip
4. **NEW (v7.8.0)**: Quick Start box explains recommended settings
5. **NEW (v7.9.0)**: Upgrade block explains Lite limitations

### Admin Screen Route
- Module settings: `admin.php?page=wcj-plugins&section={module_id}`

### Code Entry Points
| File | Function | Purpose |
|------|----------|---------|
| `includes/core/class-wcj-admin.php:96-130` | `enhance_settings_for_module()` | Injects help text |
| `includes/settings/wcj-settings-{module}.php` | `help_text` field | Setting-level help |
| `includes/admin/wcj-quick-start-admin.php:32-126` | `wcj_quick_start_render_box()` | Quick Start UI |
| `includes/class-wcj-upgrade-blocks.php:339-432` | `wcj_render_upgrade_block()` | Lite vs Elite info |

### What Improved Since October
- **v7.7.0**: `help_text` and `friendly_label` fields now supported
- **v7.7.0**: Wishlist, Cart Abandonment, Product Add-ons have contextual help
- **v7.8.0**: Quick Start shows "what this preset does" in plain English
- **v7.9.0**: Upgrade blocks explain what's available in Elite

### What Still Feels Confusing
- Help text only on ~5% of settings currently
- No "Learn more" links to documentation
- Technical option names (e.g., `wcj_sale_msg_duration`) still visible

---

## Step 6: How They Verify It Worked

### User Experience
1. User enables a module (e.g., Sales Notifications)
2. User goes to frontend store
3. User waits/looks for expected behavior
4. No explicit "test" or "preview" button in admin

### Admin Screen Route
- Success screen from modal points to "next steps"
- No dedicated "test your settings" page

### Code Entry Points
| File | Function | Purpose |
|------|----------|---------|
| `includes/admin/class-booster-onboarding.php:394-404` | N/A | Returns `next_step_link` |
| Individual module classes | Various | Actual functionality |

### What Improved Since October
- **v7.4.0**: Success screen shows "Next steps" with deep links
- **v7.5.0**: Blueprints have specific verification steps ("Send a test email")
- **v7.8.0**: Quick Start steps describe expected behavior

### What Still Feels Confusing
- No "preview" or "test mode" for most modules
- Must visit frontend to verify (context switch)
- No success indicators in admin after save

---

## Step 7: Where They Hit "What Now?" Moments

### Common Confusion Points

| Moment | What Happens | Why It's Confusing |
|--------|--------------|-------------------|
| After closing modal | User is on random admin page | No clear "continue setup" path |
| After enabling first module | Settings saved successfully | No guidance on what to do next |
| Seeing 100+ modules | Dashboard shows everything | No prioritization or recommendations |
| Hitting an Elite-only setting | Gray/disabled field | Not always clear WHY it's disabled |
| After applying a goal | Success screen appears | "Pick Another Goal" vs "Go configure" unclear |

### Admin Screen Route
- Dashboard: `admin.php?page=wcj-dashboard`
- Getting Started: `admin.php?page=wcj-getting-started`

### What Improved Since October
- **v7.4.0**: Success screen with "Pick Another Goal" option
- **v7.5.0**: Blueprint "Next Steps" lists guide user
- **v7.9.0**: Upgrade blocks explain Elite benefits inline

### What Still Feels Confusing
- No "recommended next module" suggestions
- No progress tracker ("You've set up 3 of 10 recommended modules")
- Getting Started page is basic (just "Open onboarding" button)

---

## Step 8: Where Upgrade Prompts Appear

### User Experience
1. **Module settings (v7.9.0)**: Blue info box at top of Wishlist, Cart Abandonment, Swatches
2. **Settings fields**: Disabled fields with "Available in Elite" tooltip
3. **Success screen (v7.5.0)**: Pro note with "Compare →" link after blueprints
4. **Readme/docs**: Feature comparison tables

### Admin Screen Route
- Module settings: `admin.php?page=wcj-plugins&section={module_id}`

### Code Entry Points
| File | Function | Purpose |
|------|----------|---------|
| `includes/class-wcj-upgrade-blocks.php:339-432` | `wcj_render_upgrade_block()` | Inline upgrade block |
| `includes/functions/wcj-functions-general.php` | `wcj_get_plus_message()` | Field-level messages |
| `includes/admin/onboarding-blueprints.php:42-44` | `pro_note` | Blueprint pro notes |

### What Improved Since October
- **v7.9.0**: Dedicated upgrade blocks with benefits list
- **v7.9.0**: Click tracking for upgrade interactions
- **v7.9.0**: Comparison and upgrade links properly tracked

### What Still Feels Confusing
- Upgrade prompts only on 3 modules currently
- No "what you're missing" summary page
- Field-level "Elite only" messages are small and easy to miss

---

## Journey Summary Diagram

```
Install
   │
   ▼
Activate ──────────────────────────────────┐
   │                                        │
   ▼                                        │
[Any Admin Page]                            │
   │                                        │
   ▼                                        │
┌─────────────────────────┐                 │
│   ONBOARDING MODAL      │ ◄───────────────┘
│   (auto-shows first run)│      v7.4.0+
│                         │
│  ┌─────────┬──────────┐ │
│  │Quick    │Blueprints│ │  v7.5.0+
│  │Setup    │          │ │
│  └─────────┴──────────┘ │
│                         │
│  [Goal Tiles Grid]      │
│  [Blueprint Tiles Grid] │
└────────────┬────────────┘
             │
             ▼
      ┌──────────────┐
      │ Review Screen│
      │ "We will...  │
      └──────┬───────┘
             │
             ▼
      ┌──────────────┐
      │Success Screen│
      │ + Next Steps │  v7.4.0+
      │ + Pro Note   │  v7.5.0+
      └──────┬───────┘
             │
             ├──────────────────────┐
             ▼                      ▼
   ┌─────────────────┐    ┌─────────────────┐
   │Pick Another Goal│    │Configure Module │
   └────────┬────────┘    └────────┬────────┘
            │                      │
            │                      ▼
            │             ┌─────────────────┐
            │             │ Module Settings │
            │             │ + Quick Start   │  v7.8.0+
            │             │ + Upgrade Block │  v7.9.0+
            │             │ + Help Tooltips │  v7.7.0+
            │             └────────┬────────┘
            │                      │
            └──────────────────────┘
```
