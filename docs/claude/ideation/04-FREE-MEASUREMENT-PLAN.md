# Booster Free: Measurement Plan

## Overview

Practical, low-cost measurement approach. No fancy analytics dashboards required.

---

## What We Already Have

### 1. Onboarding Analytics (v7.4.0+)

**Location**: `includes/admin/class-booster-onboarding.php:962`

**Option key**: `wcj_onboarding_analytics`

**Events tracked**:
- `modal_shown` - Modal appeared
- `goal_selected` - User clicked a goal tile
- `goal_apply_success` - Goal was applied
- `goal_apply_fail` - Goal apply failed
- `goal_undo` - User clicked undo
- `modal_dismissed` - User closed modal
- `blueprint_selected` - User clicked a blueprint
- `blueprint_apply_success` - Blueprint was applied

**Limitations**:
- Stored locally (not sent anywhere)
- Capped at 500 events (oldest discarded)
- No user cohort tracking
- No time-series visualization

### 2. Upgrade Block Clicks (v7.9.0+)

**Location**: `includes/class-wcj-upgrade-blocks.php:138`

**Option key**: `wcj_upgrade_block_clicks`

**Events tracked**:
- `module_id` - Which module's block was clicked
- `button` - "compare" or "upgrade"
- `time` - Timestamp
- `user_id` - Which admin clicked

**Limitations**:
- Same local storage limitations
- No conversion tracking (can't see if click led to purchase)

### 3. WordPress/WooCommerce Native Data

| Data Point | Source | What It Tells Us |
|------------|--------|------------------|
| Plugin activation date | `get_option('wcj_first_activation_time')` (if we add it) | Install cohort |
| Enabled modules count | Loop through `wcj_{module}_enabled` options | Engagement depth |
| WooCommerce orders | `wc_get_orders()` | Store activity (proxy for value) |
| Admin user count | `count_users()` | Store size |
| Active theme | `wp_get_theme()` | Compatibility context |
| PHP version | `phpversion()` | Technical context |

---

## What We Can Measure Today (No Code Changes)

### A. From Existing Analytics Options

**Monthly check (manual or simple script)**:

```php
// Paste in wp-admin > Tools > Console or create a simple admin page
$analytics = get_option( 'wcj_onboarding_analytics', array() );
$clicks = get_option( 'wcj_upgrade_block_clicks', array() );

// Modal engagement
$modal_shown = count( array_filter( $analytics, fn($e) => $e['type'] === 'modal_shown' ) );
$goals_applied = count( array_filter( $analytics, fn($e) => $e['type'] === 'goal_apply_success' ) );
$modal_completion_rate = $modal_shown > 0 ? round( $goals_applied / $modal_shown * 100 ) : 0;

// Goal popularity
$goal_counts = array();
foreach ( $analytics as $event ) {
    if ( $event['type'] === 'goal_apply_success' && isset( $event['data']['goal_id'] ) ) {
        $goal_id = $event['data']['goal_id'];
        $goal_counts[ $goal_id ] = ( $goal_counts[ $goal_id ] ?? 0 ) + 1;
    }
}
arsort( $goal_counts );

// Upgrade interest
$upgrade_clicks_by_module = array();
foreach ( $clicks as $click ) {
    if ( $click['button'] === 'upgrade' ) {
        $module = $click['module_id'];
        $upgrade_clicks_by_module[ $module ] = ( $upgrade_clicks_by_module[ $module ] ?? 0 ) + 1;
    }
}

echo "Modal completion rate: {$modal_completion_rate}%\n";
echo "Top goals: " . implode( ', ', array_keys( array_slice( $goal_counts, 0, 5 ) ) ) . "\n";
echo "Top upgrade interest: " . implode( ', ', array_keys( $upgrade_clicks_by_module ) ) . "\n";
```

### B. From Support Tickets

**Track these patterns**:

| Ticket Type | What It Indicates | Target |
|-------------|-------------------|--------|
| "How do I start?" | Activation redirect needed | Reduce after A1 ships |
| "What does [setting] do?" | Help text needed | Reduce after C1 ships |
| "Did it work?" | Preview buttons needed | Reduce after D1/D2/D3 ship |
| "Which module for [use case]?" | Discovery problem | Reduce after B1 ships |
| "How do I get back to wizard?" | Re-open button needed | Reduce after G1 ships |

**Simple tracking**: Tag tickets in help desk (Zendesk, Help Scout, etc.) with "onboarding" tag. Monthly count.

### C. From WordPress.org Data

| Metric | Source | What It Tells Us |
|--------|--------|------------------|
| Active installs | WordPress.org plugin page | Growth trend |
| 1-star reviews mentioning "confusing" | Review text | Onboarding failures |
| Support forum "getting started" threads | Support forum | Discovery issues |
| Download velocity after release | WordPress.org stats | Release impact |

---

## What We Should Add (Minimal Instrumentation)

### Event 1: First Activation Timestamp

**Why**: Enables cohort analysis (users who activated in January vs February).

**Implementation**:
```php
// In woocommerce-jetpack.php activation hook:
register_activation_hook( __FILE__, function() {
    if ( ! get_option( 'wcj_first_activation_time' ) ) {
        update_option( 'wcj_first_activation_time', time() );
    }
} );
```

**Effort**: 5 minutes

### Event 2: Module Enable Counts

**Why**: Know which modules users actually enable (not just which goals they apply).

**Implementation**:
```php
// In class-wcj-admin.php save handler:
function wcj_track_module_enable( $module_id, $enabled ) {
    if ( $enabled === 'yes' ) {
        $enables = get_option( 'wcj_module_enables', array() );
        $enables[ $module_id ] = ( $enables[ $module_id ] ?? 0 ) + 1;
        update_option( 'wcj_module_enables', $enables );
    }
}
```

**Effort**: 15 minutes

### Event 3: Time to First Goal

**Why**: Measure if activation redirect improves speed to first action.

**Implementation**:
```php
// In ajax_apply_goal():
if ( ! get_option( 'wcj_first_goal_time' ) ) {
    $activation_time = get_option( 'wcj_first_activation_time', 0 );
    $time_to_first_goal = time() - $activation_time;
    update_option( 'wcj_first_goal_time', $time_to_first_goal );
}
```

**Effort**: 10 minutes

### Event 4: Modal Skip Rate

**Why**: If we add "Skip for now" button (A3), track usage.

**Implementation**:
```php
// In modal JS when skip clicked:
// AJAX: booster_log_onboarding_event( 'modal_skipped' )
```

**Effort**: 10 minutes

---

## Measurement Dashboard (Low-Cost)

### Option A: Admin Page

Add a simple "Booster Stats" page under Getting Started showing:

```
┌─────────────────────────────────────────────────────────────┐
│  Booster Onboarding Stats                                   │
├─────────────────────────────────────────────────────────────┤
│  Installed: January 15, 2025                                │
│  First goal applied: 3 minutes after install                │
│  Active modules: 5                                          │
│                                                             │
│  Goals Applied:                                             │
│  ├─ Professional Invoices (Jan 15)                          │
│  ├─ Recover Abandoned Carts (Jan 15)                        │
│  └─ Sales Notifications (Jan 18)                            │
│                                                             │
│  Upgrade Block Interactions:                                │
│  ├─ Wishlist: 2 compare clicks, 1 upgrade click             │
│  └─ Cart Abandonment: 1 compare click                       │
└─────────────────────────────────────────────────────────────┘
```

**Effort**: 2-3 hours (uses existing data)

### Option B: Export to CSV

Add "Export Analytics" button to Getting Started page:

```php
// On button click:
$data = array(
    'onboarding_events' => get_option( 'wcj_onboarding_analytics', array() ),
    'upgrade_clicks' => get_option( 'wcj_upgrade_block_clicks', array() ),
    'module_enables' => get_option( 'wcj_module_enables', array() ),
    'first_activation' => get_option( 'wcj_first_activation_time' ),
    'first_goal_time' => get_option( 'wcj_first_goal_time' ),
);
// Output as downloadable JSON or CSV
```

This lets store owners share data with support if needed.

**Effort**: 1 hour

---

## Key Metrics by Package

### Activation Pack Metrics

| Metric | Baseline | Target | How to Measure |
|--------|----------|--------|----------------|
| Time to first goal | Unknown | <5 min | `wcj_first_goal_time` option |
| Modal completion rate | Unknown | 50%+ | `goal_apply_success / modal_shown` |
| Modal skip rate | N/A | <30% | `modal_skipped / modal_shown` |
| Re-open button clicks | N/A | Track | New event if added |

### Clarity Pack Metrics

| Metric | Baseline | Target | How to Measure |
|--------|----------|--------|----------------|
| Preview button usage | N/A | 20%+ of module users | New event if added |
| Test email sends | N/A | 30%+ of cart abandonment users | New event if added |
| Help text hover rate | N/A | Can't measure without JS | Skip for now |
| Quick Start apply rate | Unknown | 40%+ of preset module users | Extend existing analytics |

### Upgrade Pack Metrics

| Metric | Baseline | Target | How to Measure |
|--------|----------|--------|----------------|
| Upgrade block CTR | From v7.9.0 data | +20% | `wcj_upgrade_block_clicks` |
| Comparison page visits | N/A | Track | New event if added |
| Upgrade conversions | External | Track externally | Can't measure in-plugin |

---

## What We Can't Measure (And That's OK)

| Metric | Why We Can't | Alternative |
|--------|--------------|-------------|
| Actual upgrade purchases | Happens on booster.io | Use UTM params in upgrade links |
| User satisfaction | No survey system | Monitor reviews + support tone |
| Churn (uninstalls) | WP doesn't notify | Compare monthly active installs |
| Long-term retention | No ping-back | Cohort analysis via forums |

---

## Quarterly Review Process

**Every 3 months**:

1. **Pull data**: Run analytics script from "What We Can Measure Today" section
2. **Review support**: Count "onboarding" tagged tickets
3. **Check WordPress.org**: Note active install count and review trends
4. **Compare to targets**: Are metrics moving in right direction?
5. **Adjust priorities**: If a package isn't moving metrics, investigate

**Simple spreadsheet**:

| Quarter | Modal Completion | Time to First Goal | Upgrade Clicks | Active Installs |
|---------|------------------|-------------------|----------------|-----------------|
| Q1 2025 | (baseline) | (baseline) | (baseline) | (baseline) |
| Q2 2025 | | | | |
| Q3 2025 | | | | |

---

## Implementation Priority for Measurement

| Priority | What to Add | Effort | Value |
|----------|-------------|--------|-------|
| 1 | First activation timestamp | 5 min | High (enables cohort) |
| 2 | Time to first goal | 10 min | High (measures A1 impact) |
| 3 | Admin stats page | 2-3 hrs | Medium (visibility) |
| 4 | Module enable counts | 15 min | Medium (usage insights) |
| 5 | Export to CSV | 1 hr | Low (support tool) |

**Total effort**: ~4 hours for meaningful measurement capability.

---

## Summary

**We have**: Local analytics for onboarding events and upgrade clicks.

**We need**: First activation timestamp, time to first goal, simple stats page.

**We can't have**: External analytics, conversion tracking, uninstall data.

**Our approach**: Low-cost instrumentation + support ticket tagging + WordPress.org monitoring.

**Review cadence**: Quarterly data pull + target comparison.
