# Booster Free: Onboarding Building Blocks Inventory

## Overview

This document catalogs the reusable onboarding systems available in Booster Free as of December 2025. Use this to extend existing patterns rather than reinventing.

---

## 1. Onboarding Modal System (v7.4.0+)

### Purpose
First-run wizard with goal tiles, progress indicator, review/apply workflow.

### Location
```
includes/admin/class-booster-onboarding.php     # Controller
includes/admin/views/onboarding-modal.php        # HTML template
assets/css/admin/booster-onboarding.css          # Styles
assets/js/admin/booster-onboarding.js            # JavaScript
```

### Key Functions
| Function | File:Line | Purpose |
|----------|-----------|---------|
| `Booster_Onboarding::__construct()` | class-booster-onboarding.php:38 | Registers hooks |
| `maybe_show_onboarding_modal()` | class-booster-onboarding.php:143 | Conditional display |
| `ajax_apply_goal()` | class-booster-onboarding.php:287 | AJAX handler |
| `apply_goal()` | class-booster-onboarding.php:347 | Core application logic |
| `create_snapshot()` | class-booster-onboarding.php:414 | Undo state capture |
| `undo_goal()` | class-booster-onboarding.php:678 | Revert changes |

### How to Extend
```php
// Add a new goal to onboarding-map.php:
'my_custom_goal' => array(
    'title'           => __( 'My Goal Title', 'woocommerce-jetpack' ),
    'subtitle'        => __( 'Description of what this enables', 'woocommerce-jetpack' ),
    'icon'            => 'dashicons-star-filled',
    'svg_icon'        => '<svg>...</svg>',
    'modules'         => array(
        array(
            'id'       => 'module_id',
            'name'     => 'Module Name',
            'settings' => array(
                'wcj_module_enabled' => 'yes',
                // other settings...
            ),
        ),
    ),
    'first_win_check' => 'wcj_module_enabled',
    'next_step_text'  => __( 'Configure module', 'woocommerce-jetpack' ),
    'next_step_link'  => 'admin.php?page=wcj-plugins&section=module_id&wcj-cat-nonce=',
),
```

### Currently Using
- Getting Started page
- First-run modal (any admin page)

### Gaps
- No way to re-trigger modal after dismissal without URL parameter
- No "skip" or "do this later" explicit option
- Progress not persisted across sessions

---

## 2. Blueprints System (v7.5.0+)

### Purpose
Outcome-oriented presets that bundle multiple goals with guided next steps.

### Location
```
includes/admin/onboarding-blueprints.php         # Blueprint definitions
includes/admin/class-booster-onboarding.php      # Application logic
```

### Key Functions
| Function | File:Line | Purpose |
|----------|-----------|---------|
| Blueprint definitions | onboarding-blueprints.php:15-107 | Config array |
| `ajax_apply_blueprint()` | class-booster-onboarding.php:792 | AJAX apply |
| `create_blueprint_snapshot()` | class-booster-onboarding.php:880 | Snapshot |
| `ajax_undo_blueprint()` | class-booster-onboarding.php:908 | Undo |

### How to Extend
```php
// Add to onboarding-blueprints.php:
'my_blueprint' => array(
    'title'           => __( 'Blueprint Title', 'woocommerce-jetpack' ),
    'description'     => __( 'What this achieves', 'woocommerce-jetpack' ),
    'icon'            => 'dashicons-admin-site',
    'svg_icon'        => '<svg>...</svg>',
    'goal_keys'       => array( 'goal_1', 'goal_2' ),  // From onboarding-map.php
    'modules'         => array(),  // Can leave empty if using goal_keys
    'next_steps'      => array(
        array(
            'label' => __( 'Do this first', 'woocommerce-jetpack' ),
            'href'  => 'admin.php?page=...',
        ),
    ),
    'primary_cta'     => array(
        'label' => __( 'Main action', 'woocommerce-jetpack' ),
        'href'  => 'admin.php?page=...',
    ),
    'pro_note'        => array(
        'label' => __( 'Elite features...', 'woocommerce-jetpack' ),
        'href'  => 'https://booster.io/...',
    ),
    'success_message' => __( 'Success confirmation', 'woocommerce-jetpack' ),
),
```

### Currently Using
- Onboarding modal Blueprints tab
- 3 blueprints: Recover Lost Sales, Boost AOV, Sell Internationally

### Gaps
- No per-store customization of blueprints
- No "recommended for you" based on store data
- Limited to 3 blueprints currently

---

## 3. Quick Start Presets (v7.8.0+)

### Purpose
Module-level preset buttons that apply recommended settings instantly.

### Location
```
includes/wcj-quick-start-presets.php             # Preset definitions
includes/admin/wcj-quick-start-admin.php         # UI renderer
includes/js/wcj-quick-start.js                   # JavaScript
```

### Key Functions
| Function | File:Line | Purpose |
|----------|-----------|---------|
| `wcj_quick_start_get_all_presets()` | wcj-quick-start-presets.php:62 | All presets |
| `wcj_quick_start_get_presets_for_module()` | wcj-quick-start-presets.php:177 | Per-module |
| `wcj_quick_start_render_box()` | wcj-quick-start-admin.php:32 | Render UI |
| Filter: `wcj_quick_start_presets` | wcj-quick-start-presets.php:163 | Extensibility |

### How to Extend
```php
// Add preset in wcj-quick-start-presets.php or via filter:
add_filter( 'wcj_quick_start_presets', function( $presets ) {
    $presets['my_module'] = array(
        'module_id'   => 'my_module',
        'module_name' => __( 'My Module', 'woocommerce-jetpack' ),
        'headline'    => __( 'What this module does', 'woocommerce-jetpack' ),
        'presets'     => array(
            'balanced' => array(
                'id'       => 'balanced',
                'label'    => __( 'Balanced (recommended)', 'woocommerce-jetpack' ),
                'tagline'  => __( 'Safe starting point', 'woocommerce-jetpack' ),
                'steps'    => array(
                    __( 'Step 1 description', 'woocommerce-jetpack' ),
                    __( 'Step 2 description', 'woocommerce-jetpack' ),
                ),
                'settings' => array(
                    'wcj_setting_1' => 'value',
                    'wcj_setting_2' => 'value',
                ),
            ),
        ),
    );
    return $presets;
} );

// Render in settings file:
if ( function_exists( 'wcj_quick_start_render_box' ) ) {
    wcj_quick_start_render_box( 'my_module' );
}
```

### Currently Using
- Cart Abandonment settings page
- Sales Notifications settings page
- Product Add-ons settings page

### Gaps
- Only 3 modules have presets
- No "aggressive" vs "conservative" preset variants
- No A/B testing of preset values
- Presets don't auto-apply on module enable

---

## 4. Upgrade Blocks (v7.9.0+)

### Purpose
Inline comparison panels showing Lite limitations and Elite benefits.

### Location
```
includes/class-wcj-upgrade-blocks.php            # Config + rendering
includes/css/wcj-admin.css                       # Styles (.wcj-upgrade-block)
```

### Key Functions
| Function | File:Line | Purpose |
|----------|-----------|---------|
| `wcj_get_upgrade_blocks_config()` | class-wcj-upgrade-blocks.php:28 | All configs |
| `wcj_get_upgrade_block_config()` | class-wcj-upgrade-blocks.php:99 | Single module |
| `wcj_has_upgrade_block()` | class-wcj-upgrade-blocks.php:120 | Check if exists |
| `wcj_render_upgrade_block()` | class-wcj-upgrade-blocks.php:339 | Render HTML |
| `wcj_log_upgrade_block_click()` | class-wcj-upgrade-blocks.php:138 | Analytics |
| Filter: `wcj_upgrade_blocks_config` | class-wcj-upgrade-blocks.php:83 | Extensibility |

### How to Extend
```php
// Add via filter:
add_filter( 'wcj_upgrade_blocks_config', function( $config ) {
    $config['my_module'] = array(
        'enabled'        => true,
        'module_id'      => 'my_module',
        'lite_label'     => __( 'My Module Lite', 'woocommerce-jetpack' ),
        'headline'       => __( 'Unlock the full power in Booster Elite', 'woocommerce-jetpack' ),
        'benefits'       => array(
            __( 'Benefit 1 (Lite: limited)', 'woocommerce-jetpack' ),
            __( 'Benefit 2', 'woocommerce-jetpack' ),
        ),
        'comparison_url' => 'https://booster.io/docs/...',
        'upgrade_url'    => 'https://booster.io/buy-booster/',
    );
    return $config;
} );

// Render in settings file:
if ( function_exists( 'wcj_render_upgrade_block' ) && wcj_has_upgrade_block( 'my_module' ) ) {
    wcj_render_upgrade_block( 'my_module' );
}
```

### Currently Using
- Wishlist settings page
- Cart Abandonment settings page
- Product Variation Swatches settings page

### Gaps
- Only 3 modules have upgrade blocks
- No "Compare all features" aggregate page
- Click analytics only stored locally (500 events max)

---

## 5. Help Text / Tooltip Framework (v7.7.0+)

### Purpose
Contextual help tooltips on individual settings fields.

### Location
```
includes/core/class-wcj-admin.php                # Enhancement function
includes/settings/wcj-settings-*.php             # Per-setting definitions
```

### Key Functions
| Function | File:Line | Purpose |
|----------|-----------|---------|
| `enhance_settings_for_module()` | class-wcj-admin.php:96 | Injects tooltips |

### How to Extend
```php
// In a wcj-settings-*.php file, add to setting array:
array(
    'id'             => 'wcj_my_setting',
    'title'          => __( 'Technical Name', 'woocommerce-jetpack' ),
    'type'           => 'text',
    'help_text'      => __( 'Plain English explanation of what this does', 'woocommerce-jetpack' ),
    'friendly_label' => __( 'User-Friendly Name', 'woocommerce-jetpack' ),  // Replaces title
),
```

### Currently Using
- Wishlist settings (~10 fields)
- Cart Abandonment settings
- Multicurrency settings
- Product Add-ons settings
- Sales Notifications settings

### Gaps
- ~95% of settings lack help text
- No automated help text generation
- No link to documentation from tooltips

---

## 6. Onboarding Analytics (v7.4.0+)

### Purpose
Local event logging for onboarding interactions.

### Location
```
includes/admin/class-booster-onboarding.php      # Event logging
includes/class-wcj-upgrade-blocks.php            # Click logging
```

### Key Functions
| Function | File:Line | Purpose |
|----------|-----------|---------|
| `log_onboarding_event()` | class-booster-onboarding.php:962 | General events |
| `ajax_log_onboarding_event()` | class-booster-onboarding.php:983 | AJAX logging |
| `wcj_log_upgrade_block_click()` | class-wcj-upgrade-blocks.php:138 | Click events |

### Data Stored
```php
// Onboarding events (option: wcj_onboarding_analytics)
array(
    'type'      => 'goal_apply_success',
    'data'      => array( 'goal_id' => 'professional_invoices' ),
    'timestamp' => '2025-12-14 10:30:00',
)

// Upgrade clicks (option: wcj_upgrade_block_clicks)
array(
    'time'      => '2025-12-14 10:30:00',
    'module_id' => 'wishlist',
    'button'    => 'upgrade',
    'user_id'   => 1,
)
```

### Currently Using
- Getting Started page shows analytics table
- Upgrade Clicks Log page (`admin.php?page=wcj-upgrade-clicks-log`)

### Gaps
- No export functionality
- No visualization (charts)
- Capped at 500 events (oldest discarded)
- No user-facing progress tracking

---

## 7. Admin Notices System (Existing)

### Purpose
Standard WordPress admin notices for alerts and messages.

### Location
```
Various files using add_action( 'admin_notices', ... )
```

### How to Extend
```php
// Standard WordPress pattern:
add_action( 'admin_notices', function() {
    if ( /* condition */ ) {
        ?>
        <div class="notice notice-info is-dismissible">
            <p><?php _e( 'Message here', 'woocommerce-jetpack' ); ?></p>
        </div>
        <?php
    }
} );
```

### Currently Using
- Onboarding modal (as overlay, not standard notice)
- Various module-specific warnings

### Gaps
- No persistent dismissal tracking
- No "getting started" banner on Dashboard
- Not used for progressive onboarding tips

---

## Reusability Matrix

| System | Can Add New Items | Has Filter Hook | Documentation |
|--------|------------------|-----------------|---------------|
| Onboarding Goals | Yes (map file) | No | This doc |
| Blueprints | Yes (blueprints file) | No | This doc |
| Quick Start Presets | Yes (filter) | `wcj_quick_start_presets` | This doc |
| Upgrade Blocks | Yes (filter) | `wcj_upgrade_blocks_config` | This doc |
| Help Text | Yes (settings arrays) | No | This doc |
| Analytics | Append-only | No | This doc |

---

## Recommended Extension Priority

1. **Help Text** - Lowest effort, highest coverage potential
2. **Quick Start Presets** - Medium effort, high impact on key modules
3. **Upgrade Blocks** - Medium effort, directly tied to revenue
4. **Onboarding Goals** - Higher effort, but reusable across modal and page
5. **Blueprints** - Higher effort, but great for complex use cases
