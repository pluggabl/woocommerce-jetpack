# Booster Free: Top 10 Onboarding Priorities

## Scoring Methodology

**Score = (Impact * Confidence) / Effort**

### Impact Scale (blend of Activation + Installs + Upgrades)
- **High (3)**: Directly improves key metric; affects most users
- **Medium (2)**: Moderate improvement; affects subset of users
- **Low (1)**: Minor improvement; nice-to-have

### Confidence Scale
- **High (3)**: Proven pattern; low uncertainty
- **Medium (2)**: Reasonable hypothesis; some unknowns
- **Low (1)**: Experimental; significant unknowns

### Effort Scale
- **Small (1)**: <3 dev days
- **Medium (2)**: 3-7 dev days
- **Large (3)**: 8+ dev days

---

## Full Scoring Table

| Rank | ID | Idea | Impact | Conf | Effort | Score |
|------|----|----- |--------|------|--------|-------|
| 1 | A1 | Activation redirect | 3 | 3 | 1 | **9.0** |
| 2 | G1 | Re-open onboarding button | 2 | 3 | 1 | **6.0** |
| 3 | B4 | Search in modal | 2 | 3 | 1 | **6.0** |
| 4 | D4 | Success toast after save | 2 | 3 | 1 | **6.0** |
| 5 | E4 | Celebrate first win | 2 | 3 | 1 | **6.0** |
| 6 | C2 | Quick Start on 5 more modules | 3 | 3 | 2 | **4.5** |
| 7 | F1 | Upgrade blocks on 5 more modules | 3 | 3 | 2 | **4.5** |
| 8 | C1 | Help text on 20+ settings | 3 | 3 | 2 | **4.5** |
| 9 | A2 | Modal only on Booster pages | 2 | 3 | 1 | **6.0** |
| 10 | B1 | Store-type questionnaire | 3 | 2 | 2 | **3.0** |
| 11 | D1 | Preview: Sales Notifications | 3 | 2 | 2 | **3.0** |
| 12 | D2 | Test email: Cart Abandonment | 3 | 2 | 2 | **3.0** |
| 13 | D3 | Sample invoice preview | 3 | 2 | 2 | **3.0** |
| 14 | G3 | Getting Started redesign | 2 | 2 | 2 | **2.0** |
| 15 | F2 | Comparison page | 3 | 2 | 2 | **3.0** |

**Note**: When scores tie, items are ordered by: (1) Activation impact, (2) Effort, (3) Confidence.

---

## Top 10 Ranked

### #1: A1 - Activation Redirect to Getting Started
| Attribute | Value |
|-----------|-------|
| **Score** | 9.0 |
| **Why #1** | Fixes the single biggest drop-off point. Every new user is affected. Tiny effort (just a redirect hook). Proven pattern in countless WordPress plugins. |
| **Impact** | Activation: High (guarantees users see onboarding) |
| **Confidence** | High (standard WordPress pattern) |
| **Effort** | Small (~1 day) |
| **Code entry** | `woocommerce-jetpack.php`, `includes/core/wcj-loader.php` |

### #2: G1 - Re-Open Onboarding Button on Dashboard
| Attribute | Value |
|-----------|-------|
| **Score** | 6.0 |
| **Why #2** | Safety net for A1. Users who skip modal can return. Dead simple to implement. |
| **Impact** | Activation: Medium (helps second-chance users) |
| **Confidence** | High (obvious UX pattern) |
| **Effort** | Small (~0.5 day) |
| **Code entry** | `includes/admin/class-booster-onboarding.php:getting_started_page()` |

### #3: B4 - Search in Modal
| Attribute | Value |
|-----------|-------|
| **Score** | 6.0 |
| **Why #3** | Users who know what they want (e.g., "PDF invoice") shouldn't scroll. Client-side filter, no backend work. |
| **Impact** | Activation: Medium (speeds up goal selection) |
| **Confidence** | High (standard UX pattern) |
| **Effort** | Small (~1 day) |
| **Code entry** | `includes/admin/views/onboarding-modal.php`, `assets/js/admin/booster-onboarding.js` |

### #4: D4 - Success Toast After Save
| Attribute | Value |
|-----------|-------|
| **Score** | 6.0 |
| **Why #4** | Immediate feedback builds confidence. Applies to all 100+ modules. Minimal code. |
| **Impact** | Activation: Medium (reinforces saves) |
| **Confidence** | High (WooCommerce does this) |
| **Effort** | Small (~0.5 day) |
| **Code entry** | `includes/core/class-wcj-admin.php` |

### #5: E4 - Celebrate First Win
| Attribute | Value |
|-----------|-------|
| **Score** | 6.0 |
| **Why #5** | Psychological reinforcement at critical moment. One-time, not annoying. Easy conditional check. |
| **Impact** | Activation: Medium (builds habit) |
| **Confidence** | High (onboarding best practice) |
| **Effort** | Small (~0.5 day) |
| **Code entry** | `includes/admin/class-booster-onboarding.php` success screen |

### #6: C2 - Quick Start Presets on 5 More Modules
| Attribute | Value |
|-----------|-------|
| **Score** | 4.5 |
| **Why #6** | Extends proven pattern (v7.8.0 presets work). High-traffic modules get safe defaults. |
| **Impact** | Activation: High (reduces settings confusion) |
| **Confidence** | High (already shipped for 3 modules) |
| **Effort** | Medium (~5 days for 5 modules) |
| **Code entry** | `includes/wcj-quick-start-presets.php`, 5 settings files |

### #7: F1 - Upgrade Blocks on 5 More Modules
| Attribute | Value |
|-----------|-------|
| **Score** | 4.5 |
| **Why #7** | Direct revenue impact. Extends proven pattern (v7.9.0 blocks work). High-value modules (Multicurrency, PDF). |
| **Impact** | Upgrade: High (shows Elite value) |
| **Confidence** | High (already shipped for 3 modules) |
| **Effort** | Medium (~4 days for 5 modules) |
| **Code entry** | `includes/class-wcj-upgrade-blocks.php`, 5 settings files |

### #8: C1 - Help Text on 20+ Settings
| Attribute | Value |
|-----------|-------|
| **Score** | 4.5 |
| **Why #8** | Addresses 95% help text gap. Reduces support tickets. Content work, not complex code. |
| **Impact** | Activation: High (reduces confusion) |
| **Confidence** | High (already have framework) |
| **Effort** | Medium (~5 days for 20 settings + review) |
| **Code entry** | Various `includes/settings/wcj-settings-*.php` files |

### #9: A2 - Modal Only on Booster Pages
| Attribute | Value |
|-----------|-------|
| **Score** | 6.0 |
| **Why #9** | Fixes jarring UX. Tiny change (add page check). Lower than #2-5 because it's less impactful than missing modal entirely. |
| **Impact** | Activation: Medium (smoother experience) |
| **Confidence** | High (simple conditional) |
| **Effort** | Small (~0.5 day) |
| **Code entry** | `includes/admin/class-booster-onboarding.php:143` |

### #10: B1 - Store-Type Questionnaire
| Attribute | Value |
|-----------|-------|
| **Score** | 3.0 |
| **Why #10** | High potential impact but medium confidence (needs testing). Addresses 100+ modules overwhelm. |
| **Impact** | Activation: High (personalized recommendations) |
| **Confidence** | Medium (new pattern, needs validation) |
| **Effort** | Medium (~5 days) |
| **Code entry** | `includes/admin/views/onboarding-modal.php`, new step in wizard |

---

## Why Top 3 Are Top 3

### #1 A1 - Activation Redirect
**The highest-leverage change possible.**
- Every new user hits this moment
- Current state: 100% drop-off to Plugins page
- After: 100% land on Getting Started with modal open
- Effort is trivial (WordPress hook)
- Zero downside risk

### #2 G1 - Re-Open Onboarding Button
**Essential companion to #1.**
- Users who skip modal need a path back
- Without this, A1's value is partially lost
- Takes 30 minutes to implement
- Makes modal feel "available" not "forced"

### #3 B4 - Search in Modal
**Removes friction for power users.**
- Users who installed Booster for "PDF invoices" shouldn't hunt
- Client-side filter, no backend complexity
- Makes 13 goal tiles instantly navigable
- Scales as more goals are added

---

## Implementation Sequence

Based on dependencies and scores:

```
Week 1: A1 + A2 + G1 (Activation basics)
        ↓
Week 2: B4 + D4 + E4 (Modal enhancements + feedback)
        ↓
Week 3-4: C2 + C1 (Quick Start + Help Text)
        ↓
Week 5-6: F1 (Upgrade blocks)
        ↓
Week 7+: B1 (Store-type questionnaire - test and iterate)
```

---

## Items Just Outside Top 10

| Rank | ID | Idea | Score | Why Not Top 10 |
|------|----|----- |-------|----------------|
| 11 | D1 | Preview: Sales Notifications | 3.0 | Module-specific; affects subset of users |
| 12 | D2 | Test email: Cart Abandonment | 3.0 | Module-specific; SMTP dependency risk |
| 13 | D3 | Sample invoice preview | 3.0 | Module-specific; PDF generation complexity |
| 14 | F2 | Comparison page | 3.0 | Needs upgrade blocks first (F1) |
| 15 | G3 | Getting Started redesign | 2.0 | Lower urgency; page is rarely visited |

---

## Score Sensitivity Analysis

**If we weight Upgrade impact higher:**
- F1 (Upgrade blocks) moves to #5
- F2 (Comparison page) enters Top 10

**If we weight Activation impact higher:**
- B1 (Store-type questionnaire) moves to #6
- C1 (Help text) moves to #5

**If we use stricter Confidence:**
- B1 drops out (experimental)
- D1/D2/D3 stay out (module-specific risk)

Current scoring balances all three goals: activation, installs, upgrades.

---

## Quick Reference: Top 10 with One-Line Rationale

| Rank | ID | Idea | One-Line Rationale |
|------|----|----- |-------------------|
| 1 | A1 | Activation redirect | Every user, zero effort, proven pattern |
| 2 | G1 | Re-open button | Safety net for skipped modal |
| 3 | B4 | Search in modal | Power users find goals instantly |
| 4 | D4 | Success toast | Immediate feedback on all saves |
| 5 | E4 | Celebrate first win | Psychological reinforcement |
| 6 | C2 | Quick Start (5 more) | Extends working pattern |
| 7 | F1 | Upgrade blocks (5 more) | Direct revenue impact |
| 8 | C1 | Help text (20+) | Addresses 95% coverage gap |
| 9 | A2 | Modal on Booster pages only | Removes jarring experience |
| 10 | B1 | Store-type questionnaire | Personalized discovery |
