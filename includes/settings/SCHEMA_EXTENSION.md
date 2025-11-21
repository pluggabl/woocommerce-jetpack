# Booster Settings Schema Extension - Help Text & Friendly Labels

## Overview

This document describes the optional metadata fields that can be added to any Booster setting definition to support help tooltips and friendly labels in the admin UI.

## New Optional Fields

### `help_text` (string, optional)

Short help text that provides contextual assistance about what a setting does or how to use it.

**Example:**
```php
array(
    'title'     => __( 'Enable Cart Custom Info', 'woocommerce-jetpack' ),
    'desc'      => __( 'Enable', 'woocommerce-jetpack' ),
    'id'        => 'wcj_cart_custom_info_enabled',
    'default'   => 'no',
    'type'      => 'checkbox',
    'help_text' => __( 'This allows you to add custom information blocks to your cart page using shortcodes.', 'woocommerce-jetpack' ),
),
```

### `friendly_label` (string, optional)

Alternative, more user-friendly name for settings that might have technical or abbreviated titles.

**Example:**
```php
array(
    'title'           => __( 'Session Expiring', 'woocommerce-jetpack' ),
    'desc'            => __( 'In seconds. Default: 47 hours (60 * 60 * 47)', 'woocommerce-jetpack' ),
    'id'              => 'wcj_session_expiring',
    'default'         => 47 * 60 * 60,
    'type'            => 'number',
    'friendly_label'  => __( 'How long before a session expires', 'woocommerce-jetpack' ),
),
```

## Backward Compatibility

- Both fields are **completely optional**
- Settings without these fields continue to work exactly as before
- No errors or warnings if fields are not defined
- No changes to existing database options or behavior

## Usage in UI Code (Future Units)

### Retrieving Help Text

```php
$help_text = wcj_get_setting_help_text( 'cart', 'wcj_cart_custom_info_enabled', '' );
if ( ! empty( $help_text ) ) {
    echo '<span class="help-tooltip">' . esc_html( $help_text ) . '</span>';
}
```

### Retrieving Friendly Label

```php
$friendly_label = wcj_get_setting_friendly_label( 'general', 'wcj_session_expiring', '' );
if ( ! empty( $friendly_label ) ) {
    echo '<span class="friendly-label">' . esc_html( $friendly_label ) . '</span>';
} else {
    // Fall back to the standard title
    echo esc_html( $setting['title'] );
}
```

## Helper Functions

### `wcj_get_setting_help_text( $module_id, $option_id, $default = '' )`

Retrieves help text for a specific setting.

**Parameters:**
- `$module_id` (string) - Module identifier (e.g., "cart", "general")
- `$option_id` (string) - Option/setting identifier (e.g., "wcj_cart_custom_info_enabled")
- `$default` (string) - Default value if help text is not defined (default: '')

**Returns:** (string) The help text if defined, otherwise the default value

### `wcj_get_setting_friendly_label( $module_id, $option_id, $default = '' )`

Retrieves friendly label for a specific setting.

**Parameters:**
- `$module_id` (string) - Module identifier (e.g., "cart", "general")
- `$option_id` (string) - Option/setting identifier (e.g., "wcj_session_expiring")
- `$default` (string) - Default value if friendly label is not defined (default: '')

**Returns:** (string) The friendly label if defined, otherwise the default value

## Implementation Notes

- Helper functions are defined in `/includes/functions/wcj-functions-settings.php`
- Settings are cached in memory to avoid repeated file reads
- All strings should be wrapped with `__()` for localization using the `'woocommerce-jetpack'` textdomain
- UI code should always use the helper functions rather than accessing array keys directly

## Next Steps (Future Units)

1. **Unit 1.2**: Implement UI rendering for tooltips and inline help in admin settings pages
2. **Unit 1.3**: Add actual help text content to priority modules
3. **Unit 1.4**: Extend to onboarding wizard and quick start boxes
