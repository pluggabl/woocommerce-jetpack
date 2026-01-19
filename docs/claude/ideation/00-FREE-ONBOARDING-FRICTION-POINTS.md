# Booster Free: Current Onboarding Reality + Friction Points

## Part 1: Current Onboarding Flow (10 Bullets)

### First Install Moment
1. **Activation lands on Plugins page** - No redirect, no welcome. User must find "Booster Settings" in the WooCommerce submenu.
2. **Onboarding modal auto-appears on first admin visit** (v7.4.0+) - Shows on ANY admin page, not just Booster pages.

### Module Discovery
3. **Goal tiles in modal** - User picks outcomes like "Professional invoices" or "Recover abandoned carts" instead of hunting through 100+ modules.
4. **Blueprints tab** (v7.5.0+) - Bundles multiple goals for outcomes like "Recover Lost Sales" or "Sell Internationally."
5. **Dashboard shows all 100+ modules by category** - Functional grouping, not outcome-oriented. Overwhelming for first-time users.

### Enable Module + Settings
6. **One-click apply from modal** - Review screen shows what will change, then applies settings. Snapshot created for undo.
7. **Quick Start presets** (v7.8.0+) - Only on 3 modules currently (Cart Abandonment, Sales Notifications, Product Add-ons).

### Verify Success
8. **Success screen with "Next Steps"** - Links to configure the module, but no "preview" or "test it now" button.

### Settings Comprehension
9. **Help tooltips** (v7.7.0+) - Only ~5% of settings have `help_text`. Most settings show technical option names.

### Upgrade Prompts
10. **Upgrade blocks** (v7.9.0+) - Inline Lite vs Elite comparison on 3 modules (Wishlist, Cart Abandonment, Swatches). Contextual, not spammy.

---

## Part 2: Top 10 Friction Points

### F1: No Activation Redirect
| Attribute | Detail |
|-----------|--------|
| **Screen** | Plugins page (`plugins.php`) |
| **What happens** | User clicks "Activate", stays on Plugins list. Must find Booster manually. |
| **Evidence** | `02-FREE-ONBOARDING-JOURNEY-NOW.md` Step 1: "No activation redirect to a welcome/setup page" |
| **File path** | `woocommerce-jetpack.php` (no `register_activation_hook` redirect) |
| **Impact** | Users get lost immediately; bounce before seeing value |

### F2: Modal Shows on Any Admin Page
| Attribute | Detail |
|-----------|--------|
| **Screen** | Any WordPress admin page (Posts, Pages, Dashboard, etc.) |
| **What happens** | User activates Booster, goes to write a blog post, modal pops up unexpectedly. |
| **Evidence** | `02-FREE-ONBOARDING-JOURNEY-NOW.md` Step 2: "Modal shows on ANY admin page, not just Booster pages" |
| **File path** | `includes/admin/class-booster-onboarding.php:143` `maybe_show_onboarding_modal()` |
| **Impact** | Jarring UX; user may dismiss modal to get back to what they were doing |

### F3: Can't Re-Open Modal After Dismissal
| Attribute | Detail |
|-----------|--------|
| **Screen** | Dashboard, Getting Started page |
| **What happens** | User closes modal, later wants to use it, no obvious way back. |
| **Evidence** | `03-FREE-ONBOARDING-BUILDING-BLOCKS.md` Onboarding Modal Gaps: "No way to re-trigger modal after dismissal without URL parameter" |
| **File path** | `includes/admin/class-booster-onboarding.php:135` `should_show_modal()` checks transient |
| **Impact** | Second-chance users abandon setup |

### F4: 100+ Modules With No Recommendations
| Attribute | Detail |
|-----------|--------|
| **Screen** | Dashboard (`admin.php?page=wcj-dashboard`) |
| **What happens** | User sees all categories expanded. No "recommended for your store" suggestions. |
| **Evidence** | `02-FREE-ONBOARDING-JOURNEY-NOW.md` Step 3: "No 'recommended for your store' suggestions based on store type" |
| **File path** | `includes/admin/wcj-modules-cats.php` (static category definitions) |
| **Impact** | Analysis paralysis; users don't know where to start |

### F5: Only 3 Modules Have Quick Start Presets
| Attribute | Detail |
|-----------|--------|
| **Screen** | Module settings pages |
| **What happens** | User enables PDF Invoicing, sees complex settings, no "recommended setup" option. |
| **Evidence** | `03-FREE-ONBOARDING-BUILDING-BLOCKS.md` Quick Start Gaps: "Only 3 modules have presets" |
| **File path** | `includes/wcj-quick-start-presets.php` (only `cart_abandonment`, `sales_notifications`, `product_addons`) |
| **Impact** | Users guess at settings or give up |

### F6: ~95% of Settings Lack Help Text
| Attribute | Detail |
|-----------|--------|
| **Screen** | All module settings pages |
| **What happens** | User sees `wcj_sale_msg_duration` with no explanation of what it does. |
| **Evidence** | `03-FREE-ONBOARDING-BUILDING-BLOCKS.md` Help Text Gaps: "~95% of settings lack help text" |
| **File path** | `includes/settings/wcj-settings-*.php` (most settings arrays lack `help_text` key) |
| **Impact** | Confusion, support tickets, trial-and-error |

### F7: No "Verify It Worked" Confirmation
| Attribute | Detail |
|-----------|--------|
| **Screen** | Module settings pages, Success screen |
| **What happens** | User enables Sales Notifications, saves. No preview, must visit frontend to check. |
| **Evidence** | `02-FREE-ONBOARDING-JOURNEY-NOW.md` Step 6: "No explicit 'test' or 'preview' button in admin" |
| **File path** | `includes/settings/wcj-settings-sales-notifications.php` (no preview button) |
| **Impact** | User unsure if it worked; may disable out of uncertainty |

### F8: Getting Started Page Is Nearly Empty
| Attribute | Detail |
|-----------|--------|
| **Screen** | Getting Started (`admin.php?page=wcj-getting-started`) |
| **What happens** | Page just shows "Open onboarding" button and analytics table. No progress, no tips. |
| **Evidence** | `04-FREE-NEXT-ONBOARDING-PLAN.md` M-4: "Current page is just 'Open onboarding' button; not useful after initial setup" |
| **File path** | `includes/admin/class-booster-onboarding.php:getting_started_page()` |
| **Impact** | Wasted real estate; no reason to return |

### F9: Only 3 Modules Have Upgrade Blocks
| Attribute | Detail |
|-----------|--------|
| **Screen** | Module settings (Wishlist, Cart Abandonment, Swatches) |
| **What happens** | User on Multicurrency page doesn't see what Elite unlocks. |
| **Evidence** | `03-FREE-ONBOARDING-BUILDING-BLOCKS.md` Upgrade Blocks Gaps: "Only 3 modules have upgrade blocks" |
| **File path** | `includes/class-wcj-upgrade-blocks.php:28` `wcj_get_upgrade_blocks_config()` |
| **Impact** | Missed upgrade opportunities; users don't know what they're missing |

### F10: No Progress Tracking Across Sessions
| Attribute | Detail |
|-----------|--------|
| **Screen** | Dashboard, Getting Started |
| **What happens** | User sets up 3 modules, leaves, returns next week. No "You've configured 3 modules" indicator. |
| **Evidence** | `03-FREE-ONBOARDING-BUILDING-BLOCKS.md` Onboarding Modal Gaps: "Progress not persisted across sessions" |
| **File path** | `includes/admin/class-booster-onboarding.php` (no persistent progress option) |
| **Impact** | No sense of accomplishment; users don't know what's left to do |

---

## Friction Points by Severity

| Rank | Friction | Severity | User Dropout Risk |
|------|----------|----------|-------------------|
| 1 | F1: No activation redirect | Critical | High - lose users immediately |
| 2 | F4: 100+ modules, no recs | High | High - overwhelm causes abandonment |
| 3 | F6: 95% settings lack help | High | Medium - causes confusion/support |
| 4 | F7: No verify-it-worked | High | Medium - uncertainty leads to disable |
| 5 | F5: Only 3 Quick Start modules | Medium | Medium - missed quick wins |
| 6 | F3: Can't re-open modal | Medium | Medium - second-chance users lost |
| 7 | F2: Modal on any page | Medium | Low - jarring but recoverable |
| 8 | F9: Only 3 upgrade blocks | Medium | Low (revenue impact, not dropout) |
| 9 | F8: Empty Getting Started | Low | Low - users don't return there anyway |
| 10 | F10: No progress tracking | Low | Low - nice-to-have motivation |

---

## What's Working Well

Before fixing friction, acknowledge what's already good:

1. **Onboarding modal exists** - First-run experience is huge improvement vs. nothing (pre-v7.4.0)
2. **Goal tiles are outcome-oriented** - Users pick "Professional invoices" not "PDF Invoicing module"
3. **Blueprints bundle complexity** - "Recover Lost Sales" is easier than picking 3 separate goals
4. **Undo/snapshot system** - Users can reverse mistakes safely
5. **Quick Start presets (where they exist)** - One-click sensible defaults
6. **Upgrade blocks (where they exist)** - Contextual, not spammy
7. **Success screens with next steps** - User isn't abandoned after applying a goal
