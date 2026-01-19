# Modules System

**Key Takeaways:**
- A "module" is a PHP class extending `WCJ_Module`
- Module registry is in `includes/core/wcj-modules.php` (array of filenames)
- Enable/disable state stored as `wcj_{module_id}_enabled` option ('yes'/'no')
- Settings for each module in `includes/settings/wcj-settings-{module-id}.php`
- Module class file returns `new WCJ_ModuleName()` at the end

---

## What is a Module?

A module in Booster is:
1. **A PHP class** extending `WCJ_Module` (or a subclass like `WCJ_Module_Product_By_Condition`)
2. **Located in** `includes/class-wcj-{module-id}.php`
3. **Registered in** `includes/core/wcj-modules.php`
4. **Has settings in** `includes/settings/wcj-settings-{module-id}.php`

Each module:
- Has a unique `id` (e.g., `call_for_price`, `multicurrency`)
- Has a `short_desc` (display name)
- Has a `desc` (description)
- Can be enabled/disabled independently
- Registers its own hooks when enabled
- Defines its own settings fields

---

## Module Registry

**File:** `includes/core/wcj-modules.php`

The registry is a simple PHP array of filenames:

```php
$wcj_module_files = array(
    'class-wcj-debug-tools.php',
    'class-wcj-admin-tools.php',
    'class-wcj-price-labels.php',
    'class-wcj-call-for-price.php',
    // ... 140+ more modules
    'class-wcj-preorders.php',
);
```

**Loading logic:**
```php
foreach ( $wcj_module_files as $wcj_module_file ) {
    $module = include_once $wcj_modules_dir . $wcj_module_file;
    $this->modules[ $module->id ] = $module;
}
```

Each module file **must return** a new instance of the module class:
```php
// At the end of class-wcj-call-for-price.php
return new WCJ_Call_For_Price();
```

---

## Module Enable/Disable System

### How is state stored?

Option name format: `wcj_{module_id}_enabled`
- Value: `'yes'` or `'no'` (string)
- Storage: WordPress `wp_options` table
- Default: `'no'` (disabled by default)

### Checking if enabled

```php
// Function in includes/functions/wcj-functions-booster-core.php
function wcj_is_module_enabled( $module_id ) {
    return ( 'yes' === wcj_get_option( 'wcj_' . $module_id . '_enabled', 'no' ) );
}
```

### In module class

```php
// WCJ_Module::is_enabled() method
public function is_enabled() {
    return wcj_is_module_enabled( $this->id );
}
```

### Usage in constructor

```php
public function __construct() {
    $this->id = 'call_for_price';
    $this->short_desc = __( 'Call for Price', 'woocommerce-jetpack' );
    // ... setup

    parent::__construct(); // Registers settings hooks

    if ( $this->is_enabled() ) {
        // Only register functional hooks if enabled
        add_filter( 'woocommerce_empty_price_html', ... );
    }
}
```

---

## Module Categories

**File:** `includes/admin/wcj-modules-cats.php`

Modules are organized into 9 categories for the admin UI:

```php
return array(
    'dashboard' => array(
        'label' => 'Dashboard',
        'all_cat_ids' => array( 'by_category', 'all_module', 'active', 'manager' ),
    ),
    'prices_and_currencies' => array(
        'icon' => 'side-menu-icn5.png',
        'label' => 'Prices & Currencies',
        'all_cat_ids' => array(
            'price_by_country', 'multicurrency', 'multicurrency_base_price',
            'currency_per_product', 'currency', 'bulk_price_converter', ...
        ),
    ),
    // ... 7 more categories
);
```

---

## Base Module Class

**File:** `includes/classes/class-wcj-module.php`

### Key Properties

| Property | Type | Purpose |
|----------|------|---------|
| `$id` | string | Module identifier (e.g., `'call_for_price'`) |
| `$short_desc` | string | Display name |
| `$desc` | string | Description (free version) |
| `$desc_pro` | string | Description (premium version) |
| `$extra_desc` | string | Additional description |
| `$parent_id` | string | Parent module ID (for submodules) |
| `$type` | string | `'module'` or `'submodule'` |
| `$link` | string | Documentation URL |
| `$link_slug` | string | Docs page slug |
| `$options` | array | Cached options |
| `$tools_array` | array | Module tools configuration |

### Key Methods

| Method | Purpose |
|--------|---------|
| `__construct( $type )` | Initialize module, register settings hooks |
| `is_enabled()` | Check if module is enabled |
| `get_settings()` | Get all settings for this module |
| `add_settings_from_file( $settings )` | Load settings from file |
| `get_meta_box_options()` | Get product/order meta box options |
| `save_meta_box( $post_id, $post )` | Save meta box values |
| `add_meta_box()` | Register meta box in admin |
| `settings_section( $sections )` | Add settings section |
| `reset_settings()` | Reset module to defaults |
| `add_tools( $tools_array )` | Register module tools |

### Constructor Flow

```php
public function __construct( $type = 'module' ) {
    // 1. Register settings section filter
    add_filter( 'wcj_settings_sections', array( $this, 'settings_section' ) );

    // 2. Register settings filter
    add_filter( 'wcj_settings_' . $this->id, array( $this, 'get_settings' ), 100 );

    // 3. Set module type
    $this->type = $type;

    // 4. Register settings loading
    add_action( 'init', array( $this, 'add_settings' ) );
    add_action( 'init', array( $this, 'reset_settings' ), PHP_INT_MAX );

    // 5. If enabled, register WPML and price hooks
    if ( $this->is_enabled() ) {
        // WPML compatibility hooks
        // Price handling hooks
    }
}
```

---

## Settings Pattern

### Settings File Structure

**File:** `includes/settings/wcj-settings-{module-id}.php`

```php
<?php
// Return an array of settings fields
return array(
    // Tab structure (optional)
    array(
        'id'   => 'module_options',
        'type' => 'sectionend',
    ),
    array(
        'id'      => 'module_options',
        'type'    => 'tab_ids',
        'tab_ids' => array(
            'module_tab_1' => __( 'Tab 1 Label', 'woocommerce-jetpack' ),
        ),
    ),
    array(
        'id'   => 'module_tab_1',
        'type' => 'tab_start',
    ),

    // Section title
    array(
        'title' => __( 'Options', 'woocommerce-jetpack' ),
        'type'  => 'title',
        'desc'  => __( 'Description here.', 'woocommerce-jetpack' ),
        'id'    => 'wcj_module_options',
    ),

    // Settings fields
    array(
        'title'    => __( 'Field Label', 'woocommerce-jetpack' ),
        'desc_tip' => __( 'Tooltip text.', 'woocommerce-jetpack' ),
        'desc'     => apply_filters( 'booster_message', '', 'desc' ), // Premium gating
        'id'       => 'wcj_module_field_name',
        'default'  => 'default_value',
        'type'     => 'text', // text, textarea, checkbox, select, multiselect, etc.
        'css'      => 'width:100%',
        'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
    ),

    // Section end
    array(
        'id'   => 'wcj_module_options',
        'type' => 'sectionend',
    ),

    // Tab end
    array(
        'id'   => 'module_tab_1',
        'type' => 'tab_end',
    ),
);
```

### Loading Settings

The `WCJ_Module::add_settings_from_file()` method loads settings:

```php
public function add_settings_from_file( $settings ) {
    $filename = wcj_free_plugin_path() . '/includes/settings/wcj-settings-'
        . str_replace( '_', '-', $this->id ) . '.php';
    $settings = ( file_exists( $filename ) ? require $filename : $settings );
    return $this->maybe_fix_settings( $settings );
}
```

---

## Module Deep Dives

### Example 1: Simple Module - Call for Price

**Files:**
- `includes/class-wcj-call-for-price.php` (193 lines)
- `includes/settings/wcj-settings-call-for-price.php`

**Purpose:** Show custom text for products with empty prices

**Constructor:**
```php
public function __construct() {
    $this->id         = 'call_for_price';
    $this->short_desc = __( 'Call for Price', 'woocommerce-jetpack' );
    $this->desc       = __( 'Create any custom price label...', 'woocommerce-jetpack' );
    $this->link_slug  = 'woocommerce-call-for-price';
    parent::__construct();

    if ( $this->is_enabled() ) {
        add_filter( 'woocommerce_get_variation_prices_hash', ... );
        add_action( 'init', array( $this, 'add_empty_price_hooks' ), PHP_INT_MAX );
        add_filter( 'woocommerce_sale_flash', ... );
        // ... more hooks
    }
}
```

**Main hooks:**
- `woocommerce_empty_price_html` - Replace empty price with custom text
- `woocommerce_sale_flash` - Hide sale badge for empty price products
- `woocommerce_variation_is_visible` - Make variations visible even with empty price

**Settings:**
- Label for single product page
- Label for archives
- Label for homepage
- Label for related products
- Label for variations
- Hide sale tag option
- Make all products empty price option

---

### Example 2: Complex Module - Multicurrency

**Files:**
- `includes/class-wcj-multicurrency.php` (1,448 lines)
- `includes/settings/wcj-settings-multicurrency.php` (620 lines)

**Purpose:** Allow customers to view/purchase in different currencies

**Constructor excerpt:**
```php
public function __construct() {
    $this->id         = 'multicurrency';
    $this->short_desc = __( 'Multicurrency (Currency Switcher)', 'woocommerce-jetpack' );
    // ...
    parent::__construct();

    if ( $this->is_enabled() ) {
        // Price filters
        add_filter( WCJ_PRODUCT_GET_PRICE_FILTER, ... );
        add_filter( WCJ_PRODUCT_GET_REGULAR_PRICE_FILTER, ... );
        add_filter( WCJ_PRODUCT_GET_SALE_PRICE_FILTER, ... );

        // Currency filters
        add_filter( 'woocommerce_currency', ... );

        // Shortcode
        add_shortcode( 'wcj_currency_select_drop_down_list', ... );
        add_shortcode( 'wcj_currency_select_link_list', ... );

        // AJAX handlers
        add_action( 'wp_ajax_wcj_multicurrency_ajax', ... );
        add_action( 'wp_ajax_nopriv_wcj_multicurrency_ajax', ... );

        // Enqueue scripts
        add_action( 'wp_enqueue_scripts', ... );

        // 30+ more hooks...
    }
}
```

**Key features:**
- Currency switcher widget/shortcode
- Price conversion with exchange rates
- Per-product currency settings
- Session/cookie-based currency storage
- Compatibility with caching plugins
- AJAX currency switching

---

### Example 3: Checkout Module - Checkout Custom Fields

**Files:**
- `includes/class-wcj-checkout-custom-fields.php` (1,180 lines)
- `includes/settings/wcj-settings-checkout-custom-fields.php` (570 lines)

**Purpose:** Add custom fields to WooCommerce checkout

**Main hooks:**
```php
// Add fields to checkout
add_filter( 'woocommerce_checkout_fields', array( $this, 'add_custom_checkout_fields' ) );

// Validate fields
add_action( 'woocommerce_checkout_process', array( $this, 'process_checkout_fields' ) );

// Save to order
add_action( 'woocommerce_checkout_order_processed', array( $this, 'save_fields' ) );

// Display in admin
add_action( 'woocommerce_admin_order_data_after_billing_address', ... );

// Display in emails
add_filter( 'woocommerce_email_order_meta_fields', ... );
```

**Field types supported:**
- Text, Textarea, Number
- Select, Multiselect
- Radio, Checkbox
- Datepicker, Timepicker
- File upload
- Country, State selects
- Color picker

---

## How to Add a New Module

### Step 1: Create Module Class File

**File:** `includes/class-wcj-your-module.php`

```php
<?php
/**
 * Booster for WooCommerce - Module - Your Module
 *
 * @version X.X.X
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WCJ_Your_Module' ) ) :

    class WCJ_Your_Module extends WCJ_Module {

        public function __construct() {
            $this->id         = 'your_module';  // Must match filename
            $this->short_desc = __( 'Your Module', 'woocommerce-jetpack' );
            $this->desc       = __( 'Description of your module.', 'woocommerce-jetpack' );
            $this->desc_pro   = __( 'Premium description.', 'woocommerce-jetpack' );
            $this->link_slug  = 'woocommerce-your-module';

            parent::__construct();

            if ( $this->is_enabled() ) {
                // Register your hooks here
                add_filter( 'some_hook', array( $this, 'your_method' ) );
            }
        }

        public function your_method( $value ) {
            // Your functionality
            return $value;
        }

    }

endif;

return new WCJ_Your_Module();
```

### Step 2: Create Settings File

**File:** `includes/settings/wcj-settings-your-module.php`

```php
<?php
/**
 * Booster for WooCommerce - Settings - Your Module
 *
 * @version X.X.X
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return array(
    array(
        'id'   => 'your_module_options',
        'type' => 'sectionend',
    ),
    array(
        'id'      => 'your_module_options',
        'type'    => 'tab_ids',
        'tab_ids' => array(
            'your_module_general_tab' => __( 'General Options', 'woocommerce-jetpack' ),
        ),
    ),
    array(
        'id'   => 'your_module_general_tab',
        'type' => 'tab_start',
    ),
    array(
        'title' => __( 'General Options', 'woocommerce-jetpack' ),
        'type'  => 'title',
        'id'    => 'wcj_your_module_general_options',
    ),
    array(
        'title'   => __( 'Your Setting', 'woocommerce-jetpack' ),
        'desc'    => __( 'Enable', 'woocommerce-jetpack' ),
        'id'      => 'wcj_your_module_setting',
        'default' => 'no',
        'type'    => 'checkbox',
    ),
    array(
        'id'   => 'wcj_your_module_general_options',
        'type' => 'sectionend',
    ),
    array(
        'id'   => 'your_module_general_tab',
        'type' => 'tab_end',
    ),
);
```

### Step 3: Register in Module Registry

**File:** `includes/core/wcj-modules.php`

Add your module to the array:
```php
$wcj_module_files = array(
    // ... existing modules
    'class-wcj-your-module.php',  // Add this line
);
```

### Step 4: Add to Category

**File:** `includes/admin/wcj-modules-cats.php`

Add module ID to appropriate category:
```php
'emails_and_misc' => array(
    'all_cat_ids' => array(
        // ... existing modules
        'your_module',  // Add this
    ),
),
```

---

## Submodules

Some modules have submodules (e.g., PDF Invoicing):

```php
// Parent module
class WCJ_PDF_Invoicing extends WCJ_Module {
    // type = 'module', parent_id = ''
}

// Submodule
class WCJ_PDF_Invoicing_Numbering extends WCJ_Module {
    public function __construct() {
        $this->type = 'submodule';
        $this->parent_id = 'pdf_invoicing';
        // ...
    }
}
```

Submodules:
- Are enabled when parent module is enabled
- Share the parent's category
- Have their own settings sections
- Listed in `wcj-modules.php` with path prefix: `'pdf-invoices/submodules/class-wcj-pdf-invoicing-numbering.php'`
