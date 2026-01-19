# Booster Free: Onboarding Release Packages

## Overview

Three cohesive packages, each shippable in one release. Ordered by recommended implementation sequence.

---

## Package 1: Activation Pack

**Theme**: Get users to their first "aha" moment within 5 minutes of activation.

**Goal**: Reduce drop-off between "Activate" and "First Goal Applied."

### Included Items (7 items)

| ID | Idea | Why Included |
|----|------|--------------|
| A1 | Activation redirect to Getting Started | Eliminates the #1 friction point |
| A2 | Modal only on Booster pages | Reduces jarring experience |
| A3 | "Skip for Now" button | Gives users control, reduces modal dismissals |
| G1 | Re-open onboarding button | Safety net for users who skipped |
| B4 | Search in modal | Helps users who know what they want |
| E4 | Celebrate first win | Reinforces completion, builds habit |
| D4 | Success toast after save | Immediate feedback loop |

### Definition of Done

- [ ] **A1**: Fresh install → Activate → redirects to `wcj-getting-started&modal=onboarding`
- [ ] **A1**: Reactivation (plugin disabled then re-enabled) does NOT redirect
- [ ] **A2**: Modal auto-shows only on `page=wcj-*` URLs, not Posts/Pages/etc.
- [ ] **A2**: First visit to ANY Booster page after activation shows modal
- [ ] **A3**: "Skip for now, I'll explore first" link visible in modal footer
- [ ] **A3**: Clicking skip sets transient, shows Dashboard reminder after 3 days
- [ ] **G1**: Dashboard has visible "Open Setup Guide" or "Re-open Onboarding" button
- [ ] **G1**: Button works even after modal was previously dismissed
- [ ] **B4**: Search box in modal filters goal tiles in real-time
- [ ] **B4**: Search matches title, subtitle, and module names
- [ ] **E4**: First-ever goal apply shows "You did it!" message (not on subsequent goals)
- [ ] **D4**: After "Save changes" on any module, brief success toast appears
- [ ] **D4**: Toast auto-dismisses after 5 seconds

### Key Code Entry Points

| File | Changes |
|------|---------|
| `woocommerce-jetpack.php` | Add `register_activation_hook` with redirect transient |
| `includes/core/wcj-loader.php` | Check transient, redirect if set, delete transient |
| `includes/admin/class-booster-onboarding.php:143` | Modify `maybe_show_onboarding_modal()` page check |
| `includes/admin/class-booster-onboarding.php:135` | Update `should_show_modal()` for skip tracking |
| `includes/admin/views/onboarding-modal.php` | Add skip link, search box |
| `assets/js/admin/booster-onboarding.js` | Search filtering logic |
| `includes/admin/class-booster-onboarding.php:getting_started_page()` | Add re-open button |
| `includes/core/class-wcj-admin.php` | Add success toast after settings save |

### Risks + Mitigations

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Redirect conflicts with other plugins | Low | Med | Use unique transient name; check for existing redirects |
| Modal page check too restrictive | Med | Low | Test all Booster page URLs; use `strpos` on `page=wcj` |
| Search slows modal on slow hosts | Low | Low | Debounce search input; filter client-side only |
| First-win check unreliable | Low | Low | Store flag in user meta, not transient |

### Success Metrics

- First goal applied within 5 minutes of activation (baseline: unknown, target: 40%+)
- Modal completion rate (baseline: unknown, target: 50%+)
- Reduced support tickets about "where to start"

---

## Package 2: Clarity Pack

**Theme**: Make settings understandable and safe. Reduce "I don't know what this does" moments.

**Goal**: Increase settings engagement; reduce support tickets about configuration.

### Included Items (8 items)

| ID | Idea | Why Included |
|----|------|--------------|
| C1 | Help text on 20+ settings | Directly addresses 95% missing help text |
| C2 | Quick Start on 5 more modules | Extends proven pattern |
| C4 | Friendly labels | Replaces technical jargon |
| D1 | Preview: Sales Notifications | Verify-it-worked for popular module |
| D2 | Test email: Cart Abandonment | Verify-it-worked for revenue module |
| D3 | Sample invoice preview | Verify-it-worked for PDF module |
| B3 | "You might also like" suggestions | Guides next action |
| G3 | Getting Started page redesign | Makes page useful beyond day 1 |

### Definition of Done

- [ ] **C1**: 20 high-traffic settings have `help_text` added
- [ ] **C1**: Help text uses plain English (no `wcj_` references in user-facing text)
- [ ] **C1**: Tooltips appear on (i) icon hover
- [ ] **C2**: Quick Start presets added to: PDF Invoicing, Order Numbers, Wishlist, Checkout Fields, Related Products
- [ ] **C2**: Each preset has 2-3 step descriptions
- [ ] **C4**: 20+ settings have `friendly_label` replacing technical titles
- [ ] **D1**: Sales Notifications settings page has "Preview Notification" button
- [ ] **D1**: Preview shows sample notification inline (not frontend redirect)
- [ ] **D2**: Cart Abandonment has "Send Test Email" button
- [ ] **D2**: Test email sends to logged-in admin's email
- [ ] **D3**: PDF Invoicing has "Generate Sample Invoice" button
- [ ] **D3**: Sample uses placeholder data, marked "SAMPLE - NOT REAL ORDER"
- [ ] **B3**: Success screen shows 1-2 related goals based on current goal
- [ ] **G3**: Getting Started shows: modules enabled count, completed goals, suggested next steps

### Key Code Entry Points

| File | Changes |
|------|---------|
| `includes/settings/wcj-settings-sales-notifications.php` | Add `help_text`, `friendly_label`, preview button |
| `includes/settings/wcj-settings-cart-abandonment.php` | Add `help_text`, test email button |
| `includes/settings/wcj-settings-pdf-invoicing.php` | Add `help_text`, sample invoice button, Quick Start |
| `includes/settings/wcj-settings-order-numbers.php` | Add Quick Start |
| `includes/settings/wcj-settings-wishlist.php` | Add Quick Start |
| `includes/settings/wcj-settings-checkout-core-fields.php` | Add Quick Start |
| `includes/settings/wcj-settings-related-products.php` | Add Quick Start |
| `includes/wcj-quick-start-presets.php` | Add 5 new preset definitions |
| `includes/admin/class-booster-onboarding.php` | Add related goals logic, redesign Getting Started |
| `includes/class-wcj-sales-notifications.php` | Add preview AJAX handler |
| `includes/class-wcj-cart-abandonment.php` | Add test email handler |
| `includes/class-wcj-pdf-invoicing.php` | Add sample invoice generator |

### Risks + Mitigations

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Help text incorrect/misleading | Med | Med | Review by someone who knows the module |
| Preview shows broken layout | Med | Med | Use same rendering as frontend |
| Test email fails (SMTP issues) | Med | Low | Use `wp_mail()` with fallback message |
| Sample invoice exposes real data | Low | High | Hardcode placeholder data; never use real DB |

### Success Metrics

- Settings page time-on-page (baseline: unknown, target: longer = exploring)
- Preview/test button click rate (target: 20%+ of users who enable module)
- Support tickets about "what does this setting do" (target: decrease)

---

## Package 3: Upgrade-When-Ready Pack

**Theme**: Show Elite value contextually, when users are ready, not before.

**Goal**: Increase upgrade clicks without being annoying. Convert users who hit Lite limits.

### Included Items (6 items)

| ID | Idea | Why Included |
|----|------|--------------|
| F1 | Upgrade blocks on 5 more modules | Extends proven pattern to high-value modules |
| F2 | "What you're missing" comparison page | Central upgrade destination |
| F3 | Usage-based upgrade suggestions | Personalized, relevant prompts |
| F5 | Inline Elite indicators | Subtle visibility of gated features |
| G5 | Dormant user re-engagement | Brings back users who never started |
| E1 | Dashboard quick stats | Shows value delivered (primes for upgrade) |

### Definition of Done

- [ ] **F1**: Upgrade blocks added to: Multicurrency, PDF Invoicing, Product Add-ons, Pre-orders, Sales Notifications
- [ ] **F1**: Each block has 3-4 specific benefits with checkmarks
- [ ] **F1**: "Compare" and "Upgrade" links tracked via existing click logging
- [ ] **F2**: New page at `admin.php?page=wcj-compare-elite`
- [ ] **F2**: Page shows all Lite vs Elite differences in table format
- [ ] **F2**: Grouped by module category
- [ ] **F2**: Link visible in Dashboard and Getting Started
- [ ] **F3**: Comparison page highlights Elite features for user's enabled modules
- [ ] **F3**: "Based on your active modules" section shows relevant unlocks
- [ ] **F5**: Gated settings have small "Elite" badge next to disabled field
- [ ] **F5**: Badge has tooltip explaining what Elite unlocks
- [ ] **G5**: After 14 days with 0 modules enabled, show Dashboard notice
- [ ] **G5**: Notice says "Still exploring? Here's a quick way to get started"
- [ ] **G5**: Dismissable with "Don't show again"
- [ ] **E1**: Dashboard header shows: "X modules active" + optional "Y emails sent" if cart abandonment enabled

### Key Code Entry Points

| File | Changes |
|------|---------|
| `includes/class-wcj-upgrade-blocks.php` | Add 5 new config entries |
| `includes/settings/wcj-settings-multicurrency.php` | Render upgrade block |
| `includes/settings/wcj-settings-pdf-invoicing.php` | Render upgrade block |
| `includes/settings/wcj-settings-product-addons.php` | Render upgrade block |
| `includes/settings/wcj-settings-preorders.php` | Render upgrade block |
| `includes/settings/wcj-settings-sales-notifications.php` | Render upgrade block |
| NEW: `includes/admin/class-booster-comparison.php` | Comparison page controller |
| `includes/core/class-wcj-admin.php` | Register comparison page, add menu link |
| `includes/functions/wcj-functions-general.php` | Enhance `wcj_get_plus_message()` with badge |
| `includes/admin/class-booster-onboarding.php` | Add dormant user notice logic |
| `includes/admin/views/wcj-dashboard.php` | Add quick stats widget |

### Risks + Mitigations

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Comparison page feels like sales page | Med | Med | Lead with "what you have" then "what you could have" |
| Dormant notice is annoying | Med | Med | One-time only; dismissable; on Booster pages only |
| Usage tracking feels invasive | Low | Med | Only track enabled modules, no content data |
| Too many upgrade prompts | Med | High | Cap at 1 upgrade block per page load |

### Success Metrics

- Upgrade block click-through rate (baseline from v7.9.0 data)
- Comparison page visits (new metric)
- Upgrade conversions from Free (if tracked externally)
- Dormant user reactivation (goals applied after notice shown)

---

## Package Comparison

| Attribute | Activation Pack | Clarity Pack | Upgrade-When-Ready Pack |
|-----------|-----------------|--------------|-------------------------|
| **Focus** | First 5 minutes | Settings comprehension | Conversion |
| **Primary metric** | First goal applied | Settings engagement | Upgrade clicks |
| **Risk level** | Low | Medium | Medium |
| **User sentiment** | Positive | Positive | Neutral (if done right) |
| **Effort** | ~1 release | ~1-2 releases | ~1 release |
| **Dependencies** | None | A1 from Pack 1 helpful | E1 from Pack 3 |

---

## Recommended Order

### Ship First: Activation Pack

**Why**:
- Addresses the #1 drop-off point (activation → lost)
- All items are small or medium effort
- Low risk; extends existing patterns
- Creates foundation for subsequent packs (users reach settings pages)

### Ship Second: Clarity Pack

**Why**:
- Users who activate need clear settings
- Preview/test buttons build confidence
- Help text reduces support burden
- Getting Started redesign makes page valuable long-term

### Ship Third: Upgrade-When-Ready Pack

**Why**:
- Only makes sense after users are engaged
- Comparison page needs users who've explored
- Usage-based suggestions need module usage data
- Risk of being perceived as pushy if shipped too early

---

## Implementation Notes

### Cross-Package Dependencies

```
Activation Pack
      │
      ├─→ G1 (Re-open button) helps users who skip, needed for Clarity Pack
      │
      └─→ D4 (Success toast) creates pattern for D1/D2/D3 preview feedback
              │
              ▼
        Clarity Pack
              │
              ├─→ G3 (Getting Started redesign) creates home for F2 comparison link
              │
              └─→ C2 (Quick Start) modules are good candidates for F1 upgrade blocks
                      │
                      ▼
                Upgrade-When-Ready Pack
```

### Shared Infrastructure

All three packs benefit from:
1. **Transient-based tracking** - Activation redirect, skip reminder, dormant user
2. **AJAX handlers** - Preview, test email, apply goal
3. **Settings enhancement** - Help text, friendly labels, Quick Start, upgrade blocks

### Testing Strategy

| Pack | Test Focus |
|------|------------|
| Activation | Fresh install flow; reactivation flow; modal on correct pages |
| Clarity | Settings display; preview renders correctly; test email sends |
| Upgrade | Click tracking works; comparison page accurate; notices dismissable |

---

## Effort Estimates

| Pack | Items | Total Effort | Estimated Dev Time |
|------|-------|--------------|-------------------|
| Activation | 7 | 5S + 2M = ~7-10 dev days | 1 release |
| Clarity | 8 | 2S + 6M = ~12-18 dev days | 1-2 releases |
| Upgrade | 6 | 1S + 5M = ~10-14 dev days | 1 release |

**Note**: Effort assumes one developer. Actual time depends on testing, reviews, and content writing for help text.
