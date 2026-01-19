# Booster Free: Onboarding Search Cheatsheet

Quick reference for finding onboarding-related code and extending the systems.

---

## Onboarding Modal System

### Find the modal controller
```bash
# Main class
grep -r "class Booster_Onboarding" includes/

# Where it's instantiated
grep -r "new Booster_Onboarding" includes/
```

### Find goal definitions
```bash
# Goals array
cat includes/admin/onboarding-map.php

# Search for specific goal
grep -r "professional_invoices" includes/admin/
```

### Find AJAX handlers
```bash
grep -r "booster_apply_goal" includes/
grep -r "booster_undo_goal" includes/
grep -r "booster_apply_blueprint" includes/
```

### Find modal HTML
```bash
cat includes/admin/views/onboarding-modal.php
```

### Find modal styles
```bash
cat assets/css/admin/booster-onboarding.css
```

---

## Blueprints System

### Find blueprint definitions
```bash
cat includes/admin/onboarding-blueprints.php
```

### Search for blueprint-related code
```bash
grep -r "blueprint" includes/admin/
```

---

## Quick Start Presets

### Find all presets
```bash
cat includes/wcj-quick-start-presets.php
```

### Find where presets are rendered
```bash
grep -r "wcj_quick_start_render_box" includes/
```

### Find preset JavaScript
```bash
cat includes/js/wcj-quick-start.js
```

### Add preset to new module
```bash
# 1. Add definition
vim includes/wcj-quick-start-presets.php

# 2. Add render call to settings file
grep -l "wcj_quick_start_render_box" includes/settings/
# Then add similar call to your target settings file
```

---

## Upgrade Blocks

### Find all upgrade block configs
```bash
cat includes/class-wcj-upgrade-blocks.php
grep -r "wcj_get_upgrade_blocks_config" includes/
```

### Find where blocks are rendered
```bash
grep -r "wcj_render_upgrade_block" includes/
```

### Find click tracking
```bash
grep -r "wcj_log_upgrade_block_click" includes/
grep -r "wcj_upgrade_block_clicks" includes/
```

### Add upgrade block to new module
```bash
# 1. Add config to class-wcj-upgrade-blocks.php
# 2. Add render call to settings file:
grep -r "wcj_has_upgrade_block" includes/settings/
```

---

## Help Text / Tooltips

### Find settings with help text
```bash
grep -r "help_text" includes/settings/
```

### Find the enhancement function
```bash
grep -r "enhance_settings_for_module" includes/
```

### Find friendly_label usage
```bash
grep -r "friendly_label" includes/settings/
```

---

## Analytics / Logging

### Find onboarding analytics
```bash
grep -r "wcj_onboarding_analytics" includes/
grep -r "log_onboarding_event" includes/
```

### Find upgrade click analytics
```bash
grep -r "wcj_upgrade_block_clicks" includes/
```

### View analytics in database
```sql
-- WordPress options table
SELECT * FROM wp_options WHERE option_name LIKE 'wcj_onboarding%';
SELECT * FROM wp_options WHERE option_name LIKE 'wcj_upgrade_block%';
```

---

## Admin Menu / Pages

### Find Getting Started menu
```bash
grep -r "wcj-getting-started" includes/
```

### Find Upgrade Clicks Log page
```bash
grep -r "wcj-upgrade-clicks-log" includes/
```

### Find all Booster admin pages
```bash
grep -r "add_submenu_page.*wcj-" includes/
```

---

## Settings Files

### List all settings files
```bash
ls includes/settings/wcj-settings-*.php
```

### Find settings for specific module
```bash
# Example: cart abandonment
cat includes/settings/wcj-settings-cart-abandonment.php
```

### Find where Quick Start or Upgrade Block is called
```bash
grep -l "wcj_quick_start_render_box\|wcj_render_upgrade_block" includes/settings/
```

---

## CSS Classes

### Onboarding modal classes
```
.booster-modal           - Modal container
.booster-modal-overlay   - Dark overlay
.booster-modal-content   - White modal box
.booster-modal-header    - Header with title
.booster-goals-grid      - Goals tile grid
.booster-tile            - Individual tile
.booster-btn-primary     - Orange CTA button
.booster-btn-secondary   - White secondary button
.applied-badge           - "Applied" chip
.booster-progress-indicator - Progress steps
```

### Upgrade block classes
```
.wcj-upgrade-block       - Block container
.wcj-upgrade-block__intro     - "You're using" line
.wcj-upgrade-block__headline  - Main headline
.wcj-upgrade-block__benefits  - Benefits list
.wcj-upgrade-block__actions   - Button row
```

### Quick Start classes
```
.wcj-quick-start-box     - Box container
.wcj-quick-start-buttons - Button row
.wcj-quick-start-apply   - Apply button
.wcj-quick-start-steps   - Steps checklist
.wcj-quick-start-message - Success message
```

---

## Filter Hooks

### Quick Start presets
```php
// Filter: wcj_quick_start_presets
add_filter( 'wcj_quick_start_presets', function( $presets ) {
    // Add or modify presets
    return $presets;
} );
```

### Upgrade blocks config
```php
// Filter: wcj_upgrade_blocks_config
add_filter( 'wcj_upgrade_blocks_config', function( $config ) {
    // Add or modify configs
    return $config;
} );
```

---

## Action Hooks

### After goal applied
```php
// Action: booster_onboarding_goal_applied
add_action( 'booster_onboarding_goal_applied', function( $goal_id ) {
    // Do something after goal applied
} );
```

### After goal undone
```php
// Action: booster_onboarding_undo
add_action( 'booster_onboarding_undo', function( $goal_id ) {
    // Do something after undo
} );
```

---

## Option Keys

| Option Name | Purpose |
|-------------|---------|
| `booster_free_onboarding` | Onboarding state (completed goals, snapshots) |
| `wcj_onboarding_analytics` | Event log (max 500) |
| `wcj_upgrade_block_clicks` | Click tracking (max 500) |

---

## Quick Debugging

### Check if onboarding ran
```php
$state = get_option( 'booster_free_onboarding', array() );
var_dump( $state );
```

### Reset onboarding state (for testing)
```php
delete_option( 'booster_free_onboarding' );
```

### Force show modal
```
Navigate to: admin.php?page=wcj-getting-started&modal=onboarding
```

### Check upgrade block clicks
```php
$clicks = get_option( 'wcj_upgrade_block_clicks', array() );
var_dump( $clicks );
```

---

## File Quick Reference

| What | File |
|------|------|
| Onboarding controller | `includes/admin/class-booster-onboarding.php` |
| Goal definitions | `includes/admin/onboarding-map.php` |
| Blueprint definitions | `includes/admin/onboarding-blueprints.php` |
| Modal HTML | `includes/admin/views/onboarding-modal.php` |
| Modal CSS | `assets/css/admin/booster-onboarding.css` |
| Modal JS | `assets/js/admin/booster-onboarding.js` |
| Quick Start presets | `includes/wcj-quick-start-presets.php` |
| Quick Start admin UI | `includes/admin/wcj-quick-start-admin.php` |
| Quick Start JS | `includes/js/wcj-quick-start.js` |
| Upgrade blocks | `includes/class-wcj-upgrade-blocks.php` |
| Admin class (help text) | `includes/core/class-wcj-admin.php` |
| Settings files | `includes/settings/wcj-settings-*.php` |
