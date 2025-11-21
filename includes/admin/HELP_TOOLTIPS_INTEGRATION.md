# Help Tooltips Integration - Unit 1.2

## Overview

This document explains the admin UI rendering layer for help tooltips in Booster for WooCommerce, implemented in Unit 1.2. This builds on the data foundation from Unit 1.1.

## How It Works

The help tooltips integration automatically enhances WooCommerce settings fields with help icons and tooltips when `help_text` is defined in the settings schema.

### Architecture

1. **Data Layer (Unit 1.1)**: Helper functions retrieve `help_text` and `friendly_label` from settings definitions
   - `wcj_get_setting_help_text( $module_id, $option_id, $default )`
   - `wcj_get_setting_friendly_label( $module_id, $option_id, $default )`

2. **UI Layer (Unit 1.2)**: Integration class enhances settings arrays before rendering
   - `WCJ_Settings_Help_Tooltips` class filters settings via `woocommerce_get_settings_jetpack` hook
   - Adds `desc_tip` to fields that have `help_text` defined
   - Replaces `title` with `friendly_label` when defined

3. **Rendering**: WooCommerce's existing tooltip system displays the help icons
   - Uses `woocommerce-help-tip` class with `data-tip` attribute
   - No additional CSS/JS required - reuses WooCommerce's existing tooltip functionality

## Files

### Core Integration Files

- **`includes/admin/class-wcj-settings-help-tooltips.php`**: Main integration class that filters settings arrays
- **`includes/functions/wcj-functions-admin-ui.php`**: Helper functions for rendering help icons (available for future use)
- **`includes/admin/class-wc-settings-jetpack.php`**: Modified to load the help tooltips integration class
- **`includes/core/wcj-functions.php`**: Modified to load the admin UI helper functions

## How to Use

### Adding Help Text to Settings

To add help text and friendly labels to a setting, simply add the optional fields to the setting definition:

```php
array(
    'title'          => __( 'Total Blocks', 'woocommerce-jetpack' ),
    'id'             => 'wcj_cart_custom_info_total_number',
    'default'        => 1,
    'type'           => 'custom_number',
    'help_text'      => __( 'Set the number of custom info blocks to display on the cart page.', 'woocommerce-jetpack' ),
    'friendly_label' => __( 'Number of Info Blocks', 'woocommerce-jetpack' ),
),
```

### What Happens Automatically

When you add `help_text` and/or `friendly_label` to a setting:

1. **Help Text**: Automatically displayed as a tooltip icon next to the field label
   - Uses WooCommerce's existing `woocommerce-help-tip` styling
   - Appears on hover with the help text content
   - If the field already has a `desc_tip`, the help text is appended to it

2. **Friendly Label**: Automatically replaces the default `title` in the UI
   - Makes labels more user-friendly without changing the underlying setting ID
   - Only affects display, not functionality

3. **Backward Compatibility**: Settings without `help_text` or `friendly_label` render exactly as before
   - No extra HTML markup
   - No spacing or layout changes
   - Zero visual impact

## Technical Details

### Filter Hook

The integration uses the `woocommerce_get_settings_jetpack` filter with priority 999 to ensure it runs after all modules have defined their settings:

```php
add_filter( 'woocommerce_get_settings_jetpack', array( $this, 'enhance_settings_array' ), 999, 2 );
```

### Processing Logic

For each setting in the array:
1. Check if it's a Booster setting (ID starts with `wcj_`)
2. Retrieve `help_text` and `friendly_label` using Unit 1.1 helpers
3. If `friendly_label` exists, replace the `title`
4. If `help_text` exists, add or append to `desc_tip`

### Tooltip Rendering

WooCommerce's `WC_Admin_Settings::get_field_description()` method automatically converts `desc_tip` into a tooltip icon:

```php
// WooCommerce automatically generates:
<span class="woocommerce-help-tip" data-tip="Your help text here"></span>
```

## Future Enhancements

Potential improvements for future units (not implemented in Unit 1.2):

1. **Inline Help Option**: Support rendering help text as description below field instead of tooltip
2. **Per-Field Control**: Allow settings to specify tooltip vs inline display preference
3. **Help Text Grouping**: Support for related help text across multiple fields
4. **Rich Content**: Support for HTML formatting in help text (currently plain text only)

## Testing

To test the help tooltips integration:

1. Add `help_text` and/or `friendly_label` to a setting definition
2. Navigate to the module's settings page in WP Admin
3. Verify the help icon appears next to the label
4. Hover over the icon to see the tooltip
5. Verify settings without help_text render unchanged

## Next Steps

**Unit 1.3** will add actual help_text content to priority modules, using this UI rendering system.
