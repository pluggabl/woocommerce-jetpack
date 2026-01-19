# Booster Free: Next Onboarding Roadmap

## Scoring: Shipped Improvements Since October

### Impact Scoring Table

| Improvement | Release | Reach | Impact | Effort | Confidence |
|-------------|---------|-------|--------|--------|------------|
| Onboarding modal with goals | v7.4.0 | High | High | High | High |
| 3-step progress indicator | v7.4.0 | High | Medium | Low | High |
| Snapshot/undo system | v7.4.0 | Medium | High | Medium | High |
| Success screen + next steps | v7.4.0 | High | High | Medium | High |
| Blueprints (outcome presets) | v7.5.0 | High | High | Medium | Medium |
| Quick Setup ↔ Blueprints switcher | v7.5.0 | High | Medium | Low | High |
| Expanded goal map (13 goals) | v7.6.0 | High | Medium | Medium | Medium |
| Help text framework | v7.7.0 | Low* | Medium | Low | High |
| Quick Start presets | v7.8.0 | Low* | High | Medium | High |
| Upgrade blocks | v7.9.0 | Low* | High | Medium | High |

*Low reach because only implemented on 3-5 modules currently

### Top 3 Highest-Impact Wins Already Shipped

1. **Onboarding Modal (v7.4.0)** - First-run experience that guides users to quick wins
2. **Blueprints (v7.5.0)** - Outcome-oriented setup that matches user intent
3. **Quick Start Presets (v7.8.0)** - In-context recommended settings

### Top 3 Areas Still Under-Served

1. **Help text coverage** - Only ~5% of settings have contextual help
2. **Module discovery** - 100+ modules with no recommendation engine
3. **Verification/feedback** - No "it worked!" confirmation or preview

---

## Prioritized Backlog

### Quick Wins (1-3 days each)

#### QW-1: Add Quick Start presets to 5 more high-traffic modules

**User Problem**: Users enable PDF Invoicing, Order Numbers, etc. but don't know optimal settings.

**Screens/Modules**:
- PDF Invoicing (`pdf_invoicing`)
- Order Numbers (`order_numbers`)
- Wishlist (`wishlist`)
- Checkout Core Fields (`checkout_core_fields`)
- Related Products (`related_products`)

**Implementation Entry Points**:
```
includes/wcj-quick-start-presets.php (add preset definitions)
includes/settings/wcj-settings-pdf-invoicing.php (render call)
includes/settings/wcj-settings-order-numbers.php (render call)
...
```

**Definition of Done**:
- [ ] 5 new preset definitions in `wcj_quick_start_get_all_presets()`
- [ ] `wcj_quick_start_render_box()` called in each settings file
- [ ] Each preset has descriptive `steps` array
- [ ] Manual test: applying preset fills form correctly

---

#### QW-2: Add upgrade blocks to 5 more Lite modules

**User Problem**: Users hit Elite-gated features without understanding what they're missing.

**Screens/Modules**:
- Multicurrency (`multicurrency`)
- PDF Invoicing (`pdf_invoicing`)
- Product Add-ons (`product_addons`)
- Pre-orders (`preorders`)
- Sales Notifications (`sales_notifications`)

**Implementation Entry Points**:
```
includes/class-wcj-upgrade-blocks.php (add config)
includes/settings/wcj-settings-multicurrency.php (render call)
...
```

**Definition of Done**:
- [ ] 5 new entries in `wcj_get_upgrade_blocks_config()`
- [ ] `wcj_render_upgrade_block()` called in each settings file
- [ ] Each block has 3-4 specific benefits
- [ ] Links go to relevant doc pages

---

#### QW-3: Add help text to top 20 most-used settings

**User Problem**: Settings like "wcj_sale_msg_duration" are cryptic without context.

**Screens/Modules**: All modules with commonly-used settings.

**Implementation Entry Points**:
```
includes/settings/wcj-settings-*.php (add help_text to array items)
```

**Definition of Done**:
- [ ] 20 settings have `help_text` field added
- [ ] Help text is plain English (no technical jargon)
- [ ] Tooltips appear on hover in settings UI
- [ ] Priority: Cart Abandonment, Sales Notifications, PDF Invoicing, Wishlist

---

#### QW-4: Add "Re-open onboarding" link to Dashboard

**User Problem**: Users who dismissed modal can't easily return to it.

**Screens/Modules**: Dashboard page

**Implementation Entry Points**:
```
includes/admin/class-booster-onboarding.php (add dashboard widget or notice)
Or: Update Getting Started page link visibility
```

**Definition of Done**:
- [ ] Link/button visible on main Dashboard
- [ ] Clicking opens onboarding modal
- [ ] Works even if modal was previously dismissed
- [ ] Consider: Add to admin toolbar?

---

### Medium (1-2 weeks each)

#### M-1: Activation redirect to Getting Started page

**User Problem**: After activating plugin, users land on generic Plugins page.

**Screens/Modules**: Plugin activation hook

**Implementation Entry Points**:
```
woocommerce-jetpack.php (add activation hook)
includes/core/wcj-loader.php (redirect logic)
```

**Definition of Done**:
- [ ] On first activation, redirect to `admin.php?page=wcj-getting-started&modal=onboarding`
- [ ] Use transient to ensure redirect only happens once
- [ ] Modal auto-opens on that page
- [ ] Test: fresh install triggers redirect

---

#### M-2: "Verify it worked" preview/test buttons

**User Problem**: Users enable modules but can't confirm they're working without visiting frontend.

**Screens/Modules**:
- Sales Notifications (show preview notification in admin)
- Cart Abandonment (send test email button)
- PDF Invoicing (generate sample invoice)

**Implementation Entry Points**:
```
includes/settings/wcj-settings-sales-notifications.php (add preview button)
includes/settings/wcj-settings-cart-abandonment.php (add test button)
includes/settings/wcj-settings-pdf-invoicing.php (add sample button)
```

**Definition of Done**:
- [ ] "Preview" or "Test" button on 3 module settings pages
- [ ] Button triggers visual confirmation (not just "sent")
- [ ] Non-destructive (preview mode, not real data)

---

#### M-3: Module recommendation engine (basic)

**User Problem**: 100+ modules is overwhelming; users don't know where to start.

**Screens/Modules**: Dashboard, Getting Started page

**Implementation Entry Points**:
```
includes/admin/class-booster-onboarding.php (add recommendation logic)
includes/admin/views/onboarding-modal.php (display recommendations)
```

**Definition of Done**:
- [ ] "Recommended for You" section with 3-5 modules
- [ ] Based on: which WooCommerce features are active (e.g., has variable products → suggest swatches)
- [ ] Fallback: show most popular if no signals
- [ ] Clicking recommendation leads to goal or module settings

---

#### M-4: Getting Started page redesign

**User Problem**: Current page is just "Open onboarding" button; not useful after initial setup.

**Screens/Modules**: `admin.php?page=wcj-getting-started`

**Implementation Entry Points**:
```
includes/admin/class-booster-onboarding.php (getting_started_page method)
```

**Definition of Done**:
- [ ] Show progress: "You've enabled X modules"
- [ ] List completed goals with timestamps
- [ ] "Suggested next steps" based on what's enabled
- [ ] Quick links to most-used modules
- [ ] Consider: embed module search

---

#### M-5: Expand Quick Start to "aggressive" vs "conservative" variants

**User Problem**: "Balanced" preset may not match user's risk tolerance.

**Screens/Modules**: Modules with Quick Start presets

**Implementation Entry Points**:
```
includes/wcj-quick-start-presets.php (add variant presets)
includes/admin/wcj-quick-start-admin.php (render multiple buttons)
```

**Definition of Done**:
- [ ] Each module with presets has 2-3 variants
- [ ] Labels: "Conservative", "Balanced (recommended)", "Aggressive"
- [ ] Clear taglines explaining difference
- [ ] Default selection is "Balanced"

---

### Larger (Multi-week)

#### L-1: Progressive onboarding tips system

**User Problem**: After initial setup, there's no guidance on advanced features.

**Screens/Modules**: All admin screens

**Implementation Entry Points**:
```
New file: includes/admin/class-booster-tips.php
includes/core/class-wcj-admin.php (instantiate)
```

**Definition of Done**:
- [ ] Tip system that shows contextual suggestions
- [ ] Dismissable with "Don't show again"
- [ ] Triggers: first visit to module, after X days, after enabling related module
- [ ] 10+ tips covering advanced use cases
- [ ] Analytics: which tips are dismissed vs clicked

---

#### L-2: Interactive setup wizard (multi-step)

**User Problem**: Current modal is good for quick wins but not for comprehensive setup.

**Screens/Modules**: New dedicated wizard page

**Implementation Entry Points**:
```
New file: includes/admin/class-booster-setup-wizard.php
New page: admin.php?page=wcj-setup-wizard
```

**Definition of Done**:
- [ ] Multi-step wizard: Store Type → Goals → Review → Complete
- [ ] Store type selection: Retail, B2B, Digital, Services
- [ ] Auto-recommends modules based on type
- [ ] Progress saved across sessions
- [ ] Can skip and return later

---

#### L-3: "What you're missing" Elite comparison page

**User Problem**: Users don't have a central view of all Elite benefits.

**Screens/Modules**: New comparison page

**Implementation Entry Points**:
```
New file: includes/admin/class-booster-comparison.php
Link from Dashboard and upgrade blocks
```

**Definition of Done**:
- [ ] Table showing all Lite vs Elite differences
- [ ] Grouped by category
- [ ] "Upgrade" CTA prominent
- [ ] Shows which Lite modules user is actively using
- [ ] Highlights "unlock" potential based on usage

---

## Required Improvements Summary

### First 10 Minutes After Install (M-1)
**Activation redirect to Getting Started** ensures users don't get lost after clicking "Activate".

### Module Discovery and Selection (M-3)
**Module recommendation engine** surfaces relevant modules based on store signals.

### Settings Clarity (QW-3, QW-1)
**Help text expansion** and **Quick Start presets** make settings understandable.

### Verify It Worked (M-2)
**Preview/test buttons** give confidence that configuration is correct.

### Upgrade Path (QW-2)
**Upgrade blocks on more modules** show Elite value without being obnoxious (info-first, not sales-first).

---

## Implementation Order (Recommended)

| Priority | Item | Rationale |
|----------|------|-----------|
| 1 | QW-1: Quick Start presets (5 more) | Extends proven pattern, high ROI |
| 2 | QW-2: Upgrade blocks (5 more) | Revenue impact, low effort |
| 3 | M-1: Activation redirect | Fixes first impression |
| 4 | QW-3: Help text (20 settings) | Low effort, immediate clarity |
| 5 | QW-4: Re-open onboarding link | Removes friction |
| 6 | M-4: Getting Started redesign | Makes page useful long-term |
| 7 | M-2: Verify it worked buttons | Confidence builder |
| 8 | M-3: Recommendation engine | Discovery improvement |
| 9 | M-5: Preset variants | Power user value |
| 10 | L-1: Progressive tips | Engagement over time |
| 11 | L-2: Setup wizard | Premium onboarding |
| 12 | L-3: Comparison page | Conversion optimization |

---

## Acceptance Criteria Checklist Template

For each item, verify:

- [ ] Code follows existing patterns (see Building Blocks doc)
- [ ] Uses existing CSS classes where possible
- [ ] Strings are translatable (`__()` or `esc_html__()`)
- [ ] No console errors
- [ ] Works on mobile viewport
- [ ] Accessible (keyboard nav, ARIA labels)
- [ ] Analytics event logged (if applicable)
- [ ] Manual test on fresh install
- [ ] Manual test on existing install with data
