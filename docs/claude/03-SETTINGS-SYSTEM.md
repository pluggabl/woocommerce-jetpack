# Settings System

**Key Takeaways:**
- Settings leverage WooCommerce's built-in settings API (`WC_Admin_Settings`)
- Each module's settings in `includes/settings/wcj-settings-{module-id}.php`
- All options stored with `wcj_` prefix in `wp_options` table
- Defaults handled via settings array `'default'` key
- Premium features gated via `apply_filters( 'booster_option', ... )`

---

## Admin Menu Structure

### Menu Location

Booster adds a top-level menu in wp-admin:

```
Booster (top-level menu)
├── Dashboard        (wcj-dashboard)
├── Plugins          (wcj-plugins)       <- Module management
├── General Settings (wcj-general-settings)
├── Tools            (wcj-tools)
└── Upgrade Clicks Log (wcj-upgrade-clicks-log)
```

### Menu Registration

**File:** `includes/core/class-wcj-admin.php:389-426`

```php
public function booster_menu() {
    add_menu_page(
        __( 'Booster', 'woocommerce-jetpack' ),
        __( 'Booster', 'woocommerce-jetpack' ),
        'manage_woocommerce',  // Capability (or 'manage_options' if admin-only)
        'wcj-dashboard',       // Menu slug
        '',
        wcj_plugin_url() . '/assets/images/wcj-booster-icon.svg',
        26                     // Position (after Products)
    );

    add_submenu_page( 'wcj-dashboard', ... );  // Dashboard
    add_submenu_page( 'wcj-dashboard', ... );  // Plugins
    add_submenu_page( 'wcj-dashboard', ... );  // General Settings
}
```

### Admin Pages

| Page | File | Purpose |
|------|------|---------|
| Dashboard | `includes/admin/wcj-settings-dashboard.php` | Overview & quick stats |
| Plugins | `includes/admin/wcj-settings-plugins.php` | Module list by category |
| General Settings | `includes/admin/wcj-settings-general.php` | Import/export/reset |

---

## Settings Definition Format

### Standard Field Types

| Type | Description |
|------|-------------|
| `title` | Section header |
| `sectionend` | Section closer |
| `tab_ids` | Tab navigation definition |
| `tab_start` | Tab content start |
| `tab_end` | Tab content end |
| `text` | Text input |
| `textarea` | Multi-line text |
| `number` | Numeric input |
| `checkbox` | Yes/No toggle |
| `select` | Dropdown |
| `multiselect` | Multi-select dropdown |
| `radio` | Radio buttons |
| `color` | Color picker |
| `custom_textarea` | Unescaped textarea |
| `custom_link` | Custom HTML link |
| `module_tools` | Tools list |
| `module_head` | Module header with toggle |

### Field Properties

```php
array(
    'title'             => __( 'Field Label', 'woocommerce-jetpack' ),
    'desc'              => __( 'Inline description', 'woocommerce-jetpack' ),
    'desc_tip'          => __( 'Tooltip text', 'woocommerce-jetpack' ),
    'id'                => 'wcj_module_field_name',
    'default'           => 'default_value',
    'type'              => 'text',
    'class'             => 'wc-enhanced-select',
    'css'               => 'width:100%;',
    'placeholder'       => __( 'Placeholder...', 'woocommerce-jetpack' ),
    'custom_attributes' => array( 'min' => '0', 'step' => '1' ),
    'options'           => array( 'opt1' => 'Label 1', 'opt2' => 'Label 2' ),
    'autoload'          => false,  // Don't autoload this option
    'wcj_raw'           => true,   // Don't sanitize
    'hide_on_free'      => true,   // Hide in free version
)
```

---

## Settings Storage

### Option Naming Convention

```
wcj_{module_id}_{setting_name}

Examples:
wcj_call_for_price_enabled        # Module enable/disable
wcj_call_for_price_text           # Call for price label text
wcj_multicurrency_enabled         # Module enable/disable
wcj_multicurrency_currency_1      # First additional currency
```

### Storage Location

All options stored in WordPress `wp_options` table:
- Option names prefixed with `wcj_`
- Most options autoload = 'yes' by default
- Set `'autoload' => false` for large/rarely used options

### Option Retrieval

```php
// Standard WordPress (bypasses Booster cache)
get_option( 'wcj_call_for_price_text', 'default' );

// Booster wrapper with caching (preferred)
wcj_get_option( 'wcj_call_for_price_text', 'default' );
```

The `wcj_get_option()` function caches values in `w_c_j()->options`:

```php
function wcj_get_option( $option_name, $default = null ) {
    if ( ! isset( w_c_j()->options[ $option_name ] ) ) {
        w_c_j()->options[ $option_name ] = get_option( $option_name, $default );
    }
    return apply_filters( $option_name, w_c_j()->options[ $option_name ] );
}
```

---

## Defaults Handling

### In Settings Array

Defaults are defined in the settings array:

```php
array(
    'id'      => 'wcj_call_for_price_text',
    'default' => '<strong>Call for price</strong>',
    'type'    => 'textarea',
)
```

### Reset to Defaults

The `WCJ_Module::reset_settings()` method handles reset:

```php
public function reset_settings() {
    // Triggered by ?wcj_reset_settings={module_id}
    foreach ( $this->get_settings() as $settings ) {
        $default_value = isset( $settings['default'] ) ? $settings['default'] : '';
        update_option( $settings['id'], $default_value );
    }
}
```

### Global Reset

In General Settings page, there's a "Reset all Booster's options" button that:
1. Queries all `wcj_%` options from database
2. Deletes each option
3. Deletes related transients and post meta

---

## Validation & Sanitization

### Built-in Sanitization

Booster uses WooCommerce's `WC_Admin_Settings::save_fields()` which provides:
- Text fields: `sanitize_text_field()`
- Textareas: `wp_kses_post()` or custom
- Numbers: validated as numeric
- Checkboxes: `'yes'` or `'no'`
- Selects: validated against options

### Custom Textarea (Raw)

For fields needing HTML/scripts:

```php
array(
    'type'    => 'custom_textarea',
    'wcj_raw' => true,  // Skip sanitization
)
```

The filter in `class-wcj-admin.php`:

```php
public function unclean_custom_textarea( $value, $option, $raw_value ) {
    return ( 'custom_textarea' === $option['type'] ) ? $raw_value : $value;
}

public function maybe_unclean_field( $value, $option, $raw_value ) {
    return ( isset( $option['wcj_raw'] ) && $option['wcj_raw'] ? $raw_value : $value );
}
```

### Autoload Control

For performance, disable autoload on large options:

```php
array(
    'id'       => 'wcj_products_xml_data',
    'autoload' => false,  // Won't load on every page
)
```

After save, these are updated via direct DB query:

```php
$wpdb->query( "UPDATE {$wpdb->options} SET autoload = 'no' WHERE option_name IN (...)" );
```

---

## Meta Box Settings

### Product Meta Box Options

**File:** `includes/settings/meta-box/wcj-settings-meta-box-{module-id}.php`

```php
return array(
    array(
        'type'    => 'title',
        'title'   => __( 'Section Title', 'woocommerce-jetpack' ),
    ),
    array(
        'name'    => 'wcj_product_custom_field',  // Saved as _wcj_product_custom_field
        'title'   => __( 'Custom Field', 'woocommerce-jetpack' ),
        'type'    => 'text',
        'default' => '',
        'tooltip' => __( 'Help text', 'woocommerce-jetpack' ),
    ),
);
```

### Meta Box Registration

In module constructor:

```php
if ( $this->is_enabled() ) {
    $this->meta_box_screen   = 'product';  // or 'shop_order', etc.
    $this->meta_box_context  = 'normal';   // or 'side'
    $this->meta_box_priority = 'high';

    add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
    add_action( 'save_post_product', array( $this, 'save_meta_box' ), 10, 2 );
}
```

### HPOS Compatibility

For WooCommerce High-Performance Order Storage:

```php
// In module constructor
if ( $this->is_enabled() ) {
    // For orders (HPOS compatible)
    add_action( 'woocommerce_process_shop_order_meta', array( $this, 'save_meta_box_hpos' ), 10, 2 );
}
```

---

## Settings Migration/Backward Compatibility

### Deprecated Options

Modules can migrate old option names:

```php
public function get_deprecated_options() {
    return array(
        'wcj_new_option_name' => array(
            'key1' => 'wcj_old_option_1',
            'key2' => 'wcj_old_option_2',
        ),
    );
}
```

The `handle_deprecated_options()` method:
1. Reads old option values
2. Merges into new option format
3. Deletes old options
4. Saves new option

---

## Recipe: Adding a New Setting

### Step 1: Add to Settings File

**File:** `includes/settings/wcj-settings-your-module.php`

```php
// Inside the settings array
array(
    'title'    => __( 'New Setting', 'woocommerce-jetpack' ),
    'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
    'desc_tip' => __( 'Description of what this does.', 'woocommerce-jetpack' ),
    'id'       => 'wcj_your_module_new_setting',
    'default'  => 'no',
    'type'     => 'checkbox',
),
```

### Step 2: Use in Module

```php
// In your module's method
$setting_value = wcj_get_option( 'wcj_your_module_new_setting', 'no' );

if ( 'yes' === $setting_value ) {
    // Do something
}
```

### Step 3: Gate for Premium (Optional)

```php
array(
    'title'             => __( 'Premium Setting', 'woocommerce-jetpack' ),
    'desc'              => apply_filters( 'booster_message', '', 'desc' ),
    'id'                => 'wcj_your_module_premium_setting',
    'default'           => 'default_value',
    'type'              => 'text',
    'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
),
```

---

## Common Pitfalls

### 1. Option Name Conflicts

Always use `wcj_{module_id}_` prefix to avoid conflicts:
```php
// Good
'id' => 'wcj_call_for_price_text'

// Bad (could conflict)
'id' => 'call_for_price_text'
```

### 2. Escaping Output

Always escape when outputting option values:
```php
// Good
echo esc_html( wcj_get_option( 'wcj_setting' ) );
echo wp_kses_post( wcj_get_option( 'wcj_html_setting' ) );

// Bad (XSS risk)
echo wcj_get_option( 'wcj_setting' );
```

### 3. Large Options Performance

Set `autoload => false` for:
- JSON/serialized arrays
- Large text content
- Options used only in admin
- Options used infrequently

### 4. Default vs Saved Value

The `default` in settings array only applies when:
- Option doesn't exist in database
- Module settings are reset

To change default for existing installs, need migration logic.

### 5. Select2 Class

WooCommerce 3.2+ requires explicit class for enhanced selects:
```php
// Handled automatically by maybe_fix_settings()
// But be aware when setting custom classes
'class' => 'wc-enhanced-select',
```
