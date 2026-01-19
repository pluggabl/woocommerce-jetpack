# Booster Free: Onboarding Idea Backlog

## Overview

25+ ideas grouped into 7 buckets. Each idea is grounded in what the codebase can support today.

---

## Bucket A: First 10 Minutes After Install (Make "Aha" Happen Fast)

### A1: Activation Redirect to Getting Started
| Attribute | Detail |
|-----------|--------|
| **User problem** | After clicking "Activate," users land on Plugins page with no guidance. |
| **Screen/menu path** | Plugins page → redirect to `admin.php?page=wcj-getting-started&modal=onboarding` |
| **Building block** | WordPress `register_activation_hook` + existing modal system |
| **Effort** | S (Small) |
| **Impact** | Activation: High, Installs: Med, Upgrade: Low |
| **Non-annoying rule** | Redirect only on FIRST activation, not reactivations. Use transient to track. |

### A2: Modal Only on Booster Pages
| Attribute | Detail |
|-----------|--------|
| **User problem** | Modal appearing on random admin pages (Posts, Pages) is jarring. |
| **Screen/menu path** | Change trigger from "any admin page" to "Booster pages only" |
| **Building block** | `class-booster-onboarding.php:maybe_show_onboarding_modal()` |
| **Effort** | S |
| **Impact** | Activation: Med, Installs: Low, Upgrade: Low |
| **Non-annoying rule** | Auto-show only on Getting Started or Dashboard. Elsewhere, show subtle banner instead. |

### A3: "Skip for Now" Button in Modal
| Attribute | Detail |
|-----------|--------|
| **User problem** | User may want to explore before committing. No explicit "skip" option. |
| **Screen/menu path** | Onboarding modal footer |
| **Building block** | `views/onboarding-modal.php` |
| **Effort** | S |
| **Impact** | Activation: Med, Installs: Low, Upgrade: Low |
| **Non-annoying rule** | "Skip for now" sets reminder transient. Show subtle Dashboard notice after 3 days. |

### A4: Welcome Video/GIF in Modal
| Attribute | Detail |
|-----------|--------|
| **User problem** | Text-only modal may not convey value quickly. |
| **Screen/menu path** | Onboarding modal header or tab |
| **Building block** | `views/onboarding-modal.php` |
| **Effort** | M (Medium) |
| **Impact** | Activation: Med, Installs: Low, Upgrade: Low |
| **Non-annoying rule** | Video auto-plays muted with caption. User can dismiss permanently. |

### A5: "What Can Booster Do?" Quick Tour
| Attribute | Detail |
|-----------|--------|
| **User problem** | New users don't know the breadth of features. |
| **Screen/menu path** | New tab in modal: "See What's Possible" showing category highlights |
| **Building block** | Modal tab system (Quick Setup / Blueprints) |
| **Effort** | M |
| **Impact** | Activation: Med, Installs: Med, Upgrade: Low |
| **Non-annoying rule** | Optional tab, not default. User clicks to explore. |

---

## Bucket B: Module Discovery + Choosing the Right Modules

### B1: Store-Type Questionnaire
| Attribute | Detail |
|-----------|--------|
| **User problem** | 100+ modules is overwhelming without context. |
| **Screen/menu path** | First step in modal: "What do you sell?" (Physical, Digital, Services, B2B) |
| **Building block** | Add step to modal wizard; filter goals by type |
| **Effort** | M |
| **Impact** | Activation: High, Installs: Med, Upgrade: Med |
| **Non-annoying rule** | Can skip. Selection persists. Shows "Recommended for [store type]" badge. |

### B2: "Popular with Similar Stores" Badge
| Attribute | Detail |
|-----------|--------|
| **User problem** | Users don't know which modules others find useful. |
| **Screen/menu path** | Goal tiles in modal; Dashboard module list |
| **Building block** | Static badges (no live data needed) based on usage research |
| **Effort** | S |
| **Impact** | Activation: Med, Installs: Low, Upgrade: Low |
| **Non-annoying rule** | Subtle badge ("Popular"), not a nag. |

### B3: "You Might Also Like" Suggestions Post-Apply
| Attribute | Detail |
|-----------|--------|
| **User problem** | After applying one goal, user doesn't know logical next step. |
| **Screen/menu path** | Success screen after goal/blueprint apply |
| **Building block** | `class-booster-onboarding.php` success screen |
| **Effort** | S |
| **Impact** | Activation: Med, Installs: Low, Upgrade: Low |
| **Non-annoying rule** | Show 1-2 related goals, not a wall of options. |

### B4: Search in Modal
| Attribute | Detail |
|-----------|--------|
| **User problem** | User knows what they want ("PDF invoice") but must scroll through tiles. |
| **Screen/menu path** | Search box at top of modal |
| **Building block** | JS filter on existing goal tiles |
| **Effort** | S |
| **Impact** | Activation: Med, Installs: Low, Upgrade: Low |
| **Non-annoying rule** | Search filters tiles in real-time. No new page loads. |

### B5: Category Quick-Jump in Dashboard
| Attribute | Detail |
|-----------|--------|
| **User problem** | Dashboard shows all categories expanded; scrolling is tedious. |
| **Screen/menu path** | Dashboard page - add sticky category nav or accordion collapse |
| **Building block** | Existing category structure in `wcj-modules-cats.php` |
| **Effort** | S |
| **Impact** | Activation: Low, Installs: Low, Upgrade: Low |
| **Non-annoying rule** | Collapse all by default; let user expand what they need. |

---

## Bucket C: Settings Clarity + Safe Defaults

### C1: Help Text Expansion (20+ Settings)
| Attribute | Detail |
|-----------|--------|
| **User problem** | Technical setting names like `wcj_sale_msg_duration` are cryptic. |
| **Screen/menu path** | All module settings pages |
| **Building block** | `help_text` field in settings arrays |
| **Effort** | M (content writing) |
| **Impact** | Activation: High, Installs: Low, Upgrade: Low |
| **Non-annoying rule** | Tooltip on hover, not inline text bloat. Plain English, no jargon. |

### C2: Quick Start Presets on 5 More Modules
| Attribute | Detail |
|-----------|--------|
| **User problem** | Users on PDF Invoicing, Order Numbers, etc. don't know optimal settings. |
| **Screen/menu path** | Module settings pages for PDF Invoicing, Order Numbers, Wishlist, Checkout Fields, Related Products |
| **Building block** | `wcj-quick-start-presets.php` + `wcj_quick_start_render_box()` |
| **Effort** | M |
| **Impact** | Activation: High, Installs: Med, Upgrade: Low |
| **Non-annoying rule** | Optional box at top of settings. User can ignore and configure manually. |

### C3: "Reset to Default" Button per Section
| Attribute | Detail |
|-----------|--------|
| **User problem** | User messes up settings, wants to start over, but undo only covers goals. |
| **Screen/menu path** | Module settings sections |
| **Building block** | Extend undo/snapshot system to per-module |
| **Effort** | M |
| **Impact** | Activation: Med, Installs: Low, Upgrade: Low |
| **Non-annoying rule** | Confirmation dialog before reset. |

### C4: Friendly Labels Replace Technical IDs
| Attribute | Detail |
|-----------|--------|
| **User problem** | Users see "wcj_call_for_price_text" instead of "Button Text." |
| **Screen/menu path** | All settings pages |
| **Building block** | `friendly_label` field in settings arrays (already supported) |
| **Effort** | M (content work) |
| **Impact** | Activation: Med, Installs: Low, Upgrade: Low |
| **Non-annoying rule** | Only replace labels, not option keys. Technical IDs still work. |

### C5: Contextual "Learn More" Links
| Attribute | Detail |
|-----------|--------|
| **User problem** | User wants deeper explanation than tooltip provides. |
| **Screen/menu path** | Tooltip or setting description |
| **Building block** | Add `doc_url` field to settings arrays |
| **Effort** | M |
| **Impact** | Activation: Low, Installs: Low, Upgrade: Low |
| **Non-annoying rule** | Link opens in new tab. Only on complex settings, not every field. |

---

## Bucket D: Verify-It-Worked Loops

### D1: Preview Button for Sales Notifications
| Attribute | Detail |
|-----------|--------|
| **User problem** | User enables notifications, doesn't know if they'll appear. |
| **Screen/menu path** | Sales Notifications settings page |
| **Building block** | Add "Preview" button that shows sample notification inline |
| **Effort** | M |
| **Impact** | Activation: High, Installs: Med, Upgrade: Low |
| **Non-annoying rule** | Preview is clearly labeled "Sample - not real data." |

### D2: Test Email Button for Cart Abandonment
| Attribute | Detail |
|-----------|--------|
| **User problem** | User configures abandonment emails, no way to see what they look like. |
| **Screen/menu path** | Cart Abandonment settings page |
| **Building block** | Add "Send Test Email" button (similar to WooCommerce email test) |
| **Effort** | M |
| **Impact** | Activation: High, Installs: Med, Upgrade: Low |
| **Non-annoying rule** | Sends to admin email only. Button says "Send Test to [email]." |

### D3: Sample Invoice Preview for PDF Invoicing
| Attribute | Detail |
|-----------|--------|
| **User problem** | User configures invoice settings, must create fake order to see result. |
| **Screen/menu path** | PDF Invoicing settings page |
| **Building block** | Add "Generate Sample Invoice" button |
| **Effort** | M |
| **Impact** | Activation: High, Installs: Med, Upgrade: Low |
| **Non-annoying rule** | Uses placeholder data. Watermarked "SAMPLE." |

### D4: Success Toast After Save
| Attribute | Detail |
|-----------|--------|
| **User problem** | After clicking "Save changes," only WooCommerce banner appears. No Booster-specific feedback. |
| **Screen/menu path** | All module settings pages |
| **Building block** | Add inline success message with module-specific tip |
| **Effort** | S |
| **Impact** | Activation: Med, Installs: Low, Upgrade: Low |
| **Non-annoying rule** | Brief toast, auto-dismiss after 5 seconds. No modal. |

### D5: Checklist After Goal Apply
| Attribute | Detail |
|-----------|--------|
| **User problem** | Success screen shows "Next Steps" as links, but no tracking of completion. |
| **Screen/menu path** | Success screen in modal |
| **Building block** | Extend success screen with checkboxes |
| **Effort** | M |
| **Impact** | Activation: Med, Installs: Low, Upgrade: Low |
| **Non-annoying rule** | Checkboxes are optional. User can close modal without checking. |

---

## Bucket E: "Lite Value" Improvements (Make Free Feel Alive)

### E1: Dashboard Quick Stats
| Attribute | Detail |
|-----------|--------|
| **User problem** | Dashboard shows modules but no indication that Booster is "doing something." |
| **Screen/menu path** | Dashboard page header |
| **Building block** | New widget showing: Modules enabled, Emails sent (if cart abandonment), etc. |
| **Effort** | M |
| **Impact** | Activation: Med, Installs: Med, Upgrade: Low |
| **Non-annoying rule** | Stats are informational, not promotional. |

### E2: "Booster is Working" Indicator
| Attribute | Detail |
|-----------|--------|
| **User problem** | User enables modules but never sees Booster "in action." |
| **Screen/menu path** | Admin toolbar or Dashboard |
| **Building block** | Simple count of active modules + recent activity |
| **Effort** | S |
| **Impact** | Activation: Low, Installs: Low, Upgrade: Low |
| **Non-annoying rule** | Subtle indicator, not a badge that demands attention. |

### E3: Monthly "Your Store's Booster Activity" Summary
| Attribute | Detail |
|-----------|--------|
| **User problem** | User forgets Booster is installed and providing value. |
| **Screen/menu path** | Admin notice once per month OR email (if opted in) |
| **Building block** | Admin notices system + optional email digest |
| **Effort** | L (Large) |
| **Impact** | Activation: Med, Installs: Med, Upgrade: Med |
| **Non-annoying rule** | Max once per month. Dismissable. Shows value delivered, not sales pitch. |

### E4: Celebrate First Win
| Attribute | Detail |
|-----------|--------|
| **User problem** | User completes first goal, no celebration or reinforcement. |
| **Screen/menu path** | Success screen after first goal ever applied |
| **Building block** | Conditional in success screen |
| **Effort** | S |
| **Impact** | Activation: Med, Installs: Low, Upgrade: Low |
| **Non-annoying rule** | One-time confetti or "You did it!" message. Not repeated. |

---

## Bucket F: Upgrade Path Improvements

### F1: Upgrade Blocks on 5 More Modules
| Attribute | Detail |
|-----------|--------|
| **User problem** | Users on Multicurrency, PDF Invoicing don't see Elite benefits. |
| **Screen/menu path** | Module settings pages for Multicurrency, PDF Invoicing, Product Add-ons, Pre-orders, Sales Notifications |
| **Building block** | `class-wcj-upgrade-blocks.php` + `wcj_render_upgrade_block()` |
| **Effort** | M |
| **Impact** | Activation: Low, Installs: Low, Upgrade: High |
| **Non-annoying rule** | Info box at top, not blocking settings. User can collapse/ignore. |

### F2: "What You're Missing" Comparison Page
| Attribute | Detail |
|-----------|--------|
| **User problem** | No central view of all Lite vs Elite differences. |
| **Screen/menu path** | New page: `admin.php?page=wcj-compare-elite` linked from Dashboard |
| **Building block** | New page using `wcj_get_upgrade_blocks_config()` data |
| **Effort** | M |
| **Impact** | Activation: Low, Installs: Low, Upgrade: High |
| **Non-annoying rule** | User navigates here voluntarily. No auto-popups. |

### F3: Usage-Based Upgrade Suggestions
| Attribute | Detail |
|-----------|--------|
| **User problem** | User gets generic upgrade prompts, not relevant to their usage. |
| **Screen/menu path** | Upgrade blocks, comparison page |
| **Building block** | Check which modules user has enabled; highlight relevant Elite features |
| **Effort** | M |
| **Impact** | Activation: Low, Installs: Low, Upgrade: High |
| **Non-annoying rule** | "Based on your active modules, you'd unlock: [list]" - informative, not pushy. |

### F4: Time-Limited Trial Prompt (After 30 Days)
| Attribute | Detail |
|-----------|--------|
| **User problem** | User has been using Lite for a month, never considered upgrade. |
| **Screen/menu path** | Dashboard banner after 30 days |
| **Building block** | Admin notices system + date tracking |
| **Effort** | M |
| **Impact** | Activation: Low, Installs: Low, Upgrade: Med |
| **Non-annoying rule** | Once only. Dismissable with "Don't show again." Offers trial, not hard sell. |

### F5: Inline "Elite Feature" Indicators
| Attribute | Detail |
|-----------|--------|
| **User problem** | Disabled fields have "Elite only" but easy to miss. |
| **Screen/menu path** | All settings pages with gated features |
| **Building block** | `booster_option` filter styling |
| **Effort** | S |
| **Impact** | Activation: Low, Installs: Low, Upgrade: Med |
| **Non-annoying rule** | Subtle icon + tooltip. Not a flashing banner. |

---

## Bucket G: Reactivation Loops (Bring Users Back)

### G1: "Re-Open Onboarding" Button on Dashboard
| Attribute | Detail |
|-----------|--------|
| **User problem** | User dismissed modal, wants to use it again, can't find it. |
| **Screen/menu path** | Dashboard page |
| **Building block** | Link that sets `wcj_onboarding_modal=1` and redirects |
| **Effort** | S |
| **Impact** | Activation: Med, Installs: Low, Upgrade: Low |
| **Non-annoying rule** | Persistent button, not a nag. User clicks when ready. |

### G2: "Continue Setup" Reminder After 3 Days
| Attribute | Detail |
|-----------|--------|
| **User problem** | User applied one goal, never came back to configure more. |
| **Screen/menu path** | Admin notice on Booster pages |
| **Building block** | Admin notices + transient tracking |
| **Effort** | S |
| **Impact** | Activation: Med, Installs: Low, Upgrade: Low |
| **Non-annoying rule** | Shows only on Booster pages, not site-wide. Dismissable permanently. |

### G3: Getting Started Page Redesign
| Attribute | Detail |
|-----------|--------|
| **User problem** | Page is nearly empty after initial setup. |
| **Screen/menu path** | `admin.php?page=wcj-getting-started` |
| **Building block** | Extend `getting_started_page()` method |
| **Effort** | M |
| **Impact** | Activation: Med, Installs: Low, Upgrade: Low |
| **Non-annoying rule** | Shows progress, completed goals, suggested next steps. Not a sales page. |

### G4: Progress Tracker Widget
| Attribute | Detail |
|-----------|--------|
| **User problem** | User doesn't know how much they've configured or what's left. |
| **Screen/menu path** | Dashboard sidebar or Getting Started page |
| **Building block** | New widget using goals/blueprints applied data |
| **Effort** | M |
| **Impact** | Activation: Med, Installs: Low, Upgrade: Low |
| **Non-annoying rule** | "You've set up 3 of 13 goals" - informational, not gamified pressure. |

### G5: Dormant User Re-Engagement Notice
| Attribute | Detail |
|-----------|--------|
| **User problem** | User installed Booster, enabled nothing, forgot about it. |
| **Screen/menu path** | Dashboard notice after 14 days of no module enabled |
| **Building block** | Admin notices + activity tracking |
| **Effort** | M |
| **Impact** | Activation: High, Installs: Med, Upgrade: Low |
| **Non-annoying rule** | "Still exploring? Here's a quick way to get started." One-time, dismissable. |

---

## Summary Table

| ID | Idea | Bucket | Effort | Activation | Installs | Upgrade |
|----|------|--------|--------|------------|----------|---------|
| A1 | Activation redirect | A | S | High | Med | Low |
| A2 | Modal only on Booster pages | A | S | Med | Low | Low |
| A3 | "Skip for Now" button | A | S | Med | Low | Low |
| A4 | Welcome video/GIF | A | M | Med | Low | Low |
| A5 | Quick tour tab | A | M | Med | Med | Low |
| B1 | Store-type questionnaire | B | M | High | Med | Med |
| B2 | "Popular" badge | B | S | Med | Low | Low |
| B3 | "You might also like" suggestions | B | S | Med | Low | Low |
| B4 | Search in modal | B | S | Med | Low | Low |
| B5 | Category quick-jump | B | S | Low | Low | Low |
| C1 | Help text expansion | C | M | High | Low | Low |
| C2 | Quick Start on 5 more modules | C | M | High | Med | Low |
| C3 | Reset to default button | C | M | Med | Low | Low |
| C4 | Friendly labels | C | M | Med | Low | Low |
| C5 | Learn more links | C | M | Low | Low | Low |
| D1 | Preview: Sales Notifications | D | M | High | Med | Low |
| D2 | Test email: Cart Abandonment | D | M | High | Med | Low |
| D3 | Sample invoice preview | D | M | High | Med | Low |
| D4 | Success toast after save | D | S | Med | Low | Low |
| D5 | Checklist after goal apply | D | M | Med | Low | Low |
| E1 | Dashboard quick stats | E | M | Med | Med | Low |
| E2 | "Booster is working" indicator | E | S | Low | Low | Low |
| E3 | Monthly activity summary | E | L | Med | Med | Med |
| E4 | Celebrate first win | E | S | Med | Low | Low |
| F1 | Upgrade blocks on 5 more | F | M | Low | Low | High |
| F2 | "What you're missing" page | F | M | Low | Low | High |
| F3 | Usage-based upgrade suggestions | F | M | Low | Low | High |
| F4 | Time-limited trial prompt | F | M | Low | Low | Med |
| F5 | Inline Elite indicators | F | S | Low | Low | Med |
| G1 | Re-open onboarding button | G | S | Med | Low | Low |
| G2 | Continue setup reminder | G | S | Med | Low | Low |
| G3 | Getting Started redesign | G | M | Med | Low | Low |
| G4 | Progress tracker widget | G | M | Med | Low | Low |
| G5 | Dormant user notice | G | M | High | Med | Low |

---

## Building Block Usage Summary

| Building Block | Ideas Using It |
|----------------|----------------|
| Onboarding Modal | A1, A2, A3, A4, A5, B1, B4, D5 |
| Blueprints | B3 |
| Quick Start Presets | C2 |
| Upgrade Blocks | F1, F2, F3 |
| Help Text Framework | C1, C4 |
| Admin Notices | A3, G2, G5, F4 |
| Analytics | E1, E3, G4 |
| WordPress Hooks | A1 |
