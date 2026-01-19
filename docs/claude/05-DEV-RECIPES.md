# Developer Recipes

**Quick reference for common development tasks in this codebase.**

---

## How to Add a New Module

### 1. Create Module Class

**File:** `includes/class-wcj-your-module.php`

```php
<?php
/**
 * Booster for WooCommerce - Module - Your Module
 *
 * @version 7.9.0
 * @since   7.9.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WCJ_Your_Module' ) ) :

class WCJ_Your_Module extends WCJ_Module {

    /**
     * Constructor.
     *
     * @version 7.9.0
     * @since   7.9.0
     */
    public function __construct() {
        $this->id         = 'your_module';
        $this->short_desc = __( 'Your Module Name', 'woocommerce-jetpack' );
        $this->desc       = __( 'Free version description.', 'woocommerce-jetpack' );
        $this->desc_pro   = __( 'Premium version description.', 'woocommerce-jetpack' );
        $this->link_slug  = 'woocommerce-your-module';

        parent::__construct();

        if ( $this->is_enabled() ) {
            // Add your hooks here
            add_filter( 'woocommerce_some_hook', array( $this, 'your_method' ) );
        }
    }

    /**
     * Your method description.
     *
     * @version 7.9.0
     * @since   7.9.0
     * @param mixed $value The value to filter.
     * @return mixed
     */
    public function your_method( $value ) {
        // Your logic
        return $value;
    }

}

endif;

return new WCJ_Your_Module();
```

### 2. Create Settings File

**File:** `includes/settings/wcj-settings-your-module.php`

```php
<?php
/**
 * Booster for WooCommerce - Settings - Your Module
 *
 * @version 7.9.0
 * @since   7.9.0
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
            'your_module_general_tab' => __( 'General', 'woocommerce-jetpack' ),
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
        'title'   => __( 'Enable Feature', 'woocommerce-jetpack' ),
        'desc'    => __( 'Enable', 'woocommerce-jetpack' ),
        'id'      => 'wcj_your_module_feature_enabled',
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

### 3. Register in Module List

**File:** `includes/core/wcj-modules.php`

Add to the `$wcj_module_files` array:
```php
$wcj_module_files = array(
    // ... existing modules ...
    'class-wcj-your-module.php',
);
```

### 4. Add to Category

**File:** `includes/admin/wcj-modules-cats.php`

```php
'emails_and_misc' => array(
    // ... existing properties ...
    'all_cat_ids' => array(
        // ... existing modules ...
        'your_module',
    ),
),
```

---

## How to Add a New Settings Field

### Basic Field

```php
array(
    'title'   => __( 'Field Title', 'woocommerce-jetpack' ),
    'desc'    => __( 'Description text', 'woocommerce-jetpack' ),
    'id'      => 'wcj_module_field_name',
    'default' => 'default_value',
    'type'    => 'text',
),
```

### Premium-Gated Field

```php
array(
    'title'             => __( 'Premium Field', 'woocommerce-jetpack' ),
    'desc'              => apply_filters( 'booster_message', '', 'desc' ),
    'id'                => 'wcj_module_premium_field',
    'default'           => '',
    'type'              => 'text',
    'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
),
```

### Select Field

```php
array(
    'title'   => __( 'Select Option', 'woocommerce-jetpack' ),
    'id'      => 'wcj_module_select',
    'default' => 'option1',
    'type'    => 'select',
    'class'   => 'wc-enhanced-select',
    'options' => array(
        'option1' => __( 'Option 1', 'woocommerce-jetpack' ),
        'option2' => __( 'Option 2', 'woocommerce-jetpack' ),
    ),
),
```

### Number Field with Validation

```php
array(
    'title'             => __( 'Number Field', 'woocommerce-jetpack' ),
    'id'                => 'wcj_module_number',
    'default'           => 10,
    'type'              => 'number',
    'custom_attributes' => array( 'min' => '0', 'max' => '100', 'step' => '1' ),
),
```

### Multi-Select Field

```php
array(
    'title'   => __( 'Multi-Select', 'woocommerce-jetpack' ),
    'id'      => 'wcj_module_multiselect',
    'default' => array(),
    'type'    => 'multiselect',
    'class'   => 'wc-enhanced-select',
    'options' => array(
        'opt1' => 'Option 1',
        'opt2' => 'Option 2',
    ),
),
```

---

## How to Add a New Admin Page/Section

### Add Submenu Page

**In your module or custom code:**

```php
add_action( 'admin_menu', function() {
    add_submenu_page(
        'wcj-dashboard',                        // Parent slug
        __( 'Page Title', 'woocommerce-jetpack' ),
        __( 'Menu Title', 'woocommerce-jetpack' ),
        'manage_woocommerce',                   // Capability
        'wcj-your-page',                        // Menu slug
        'your_page_callback'                    // Callback function
    );
}, 100 );

function your_page_callback() {
    echo '<div class="wrap">';
    echo '<h1>' . esc_html__( 'Page Title', 'woocommerce-jetpack' ) . '</h1>';
    // Your page content
    echo '</div>';
}
```

---

## How to Add a Shortcode

### 1. Create Shortcode Class

**File:** `includes/shortcodes/class-wcj-your-shortcodes.php`

```php
<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WCJ_Your_Shortcodes' ) ) :

class WCJ_Your_Shortcodes extends WCJ_Shortcodes {

    public function __construct() {
        $this->the_shortcodes = array(
            'wcj_your_shortcode',
        );
        parent::__construct();
    }

    /**
     * [wcj_your_shortcode] shortcode.
     */
    public function wcj_your_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'param1' => 'default',
        ), $atts, 'wcj_your_shortcode' );

        return 'Your shortcode output: ' . esc_html( $atts['param1'] );
    }

}

endif;

return new WCJ_Your_Shortcodes();
```

### 2. Register in Shortcodes Loader

**File:** `includes/core/wcj-shortcodes.php`

```php
$wcj_shortcodes_files = array(
    // ... existing shortcodes ...
    'your'  => 'class-wcj-your-shortcodes.php',
);
```

---

## How to Enqueue Admin JS/CSS

### In Module Constructor

```php
if ( $this->is_enabled() ) {
    add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
}
```

### Enqueue Method

```php
public function enqueue_admin_scripts( $hook ) {
    // Only on specific pages
    if ( 'woocommerce_page_wc-settings' !== $hook ) {
        return;
    }

    wp_enqueue_style(
        'wcj-your-module-admin',
        wcj_plugin_url() . '/includes/css/your-module-admin.css',
        array(),
        w_c_j()->version
    );

    wp_enqueue_script(
        'wcj-your-module-admin',
        wcj_plugin_url() . '/includes/js/your-module-admin.js',
        array( 'jquery' ),
        w_c_j()->version,
        true
    );

    wp_localize_script( 'wcj-your-module-admin', 'wcj_your_module', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'wcj-your-module' ),
    ) );
}
```

---

## How to Add Frontend JS/CSS

```php
if ( $this->is_enabled() ) {
    add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
}

public function enqueue_scripts() {
    if ( ! is_product() ) {
        return;  // Only on product pages
    }

    wp_enqueue_style(
        'wcj-your-module',
        wcj_plugin_url() . '/includes/css/your-module.css',
        array(),
        w_c_j()->version
    );

    wp_enqueue_script(
        'wcj-your-module',
        wcj_plugin_url() . '/includes/js/your-module.js',
        array( 'jquery' ),
        w_c_j()->version,
        true
    );
}
```

---

## How to Add Translations/i18n

### In PHP

```php
// Simple string
__( 'Your text', 'woocommerce-jetpack' )

// With escaping
esc_html__( 'Your text', 'woocommerce-jetpack' )

// Pluralization
sprintf(
    _n( '%d item', '%d items', $count, 'woocommerce-jetpack' ),
    $count
)

// With placeholder
sprintf(
    /* translators: %s: product name */
    __( 'Added %s to cart', 'woocommerce-jetpack' ),
    $product_name
)
```

### In JavaScript

Use wp_localize_script() to pass translated strings:

```php
wp_localize_script( 'your-script', 'wcj_i18n', array(
    'confirm' => __( 'Are you sure?', 'woocommerce-jetpack' ),
    'success' => __( 'Success!', 'woocommerce-jetpack' ),
) );
```

### Translation Files

Location: `langs/woocommerce-jetpack.pot`

Generate with WP-CLI:
```bash
wp i18n make-pot . langs/woocommerce-jetpack.pot --domain=woocommerce-jetpack
```

---

## How to Add a Widget

### 1. Create Widget Class

**File:** `includes/widgets/class-wcj-widget-your-widget.php`

```php
<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WCJ_Widget_Your_Widget' ) ) :

class WCJ_Widget_Your_Widget extends WCJ_Widget {

    public function __construct() {
        $this->widget_cssclass    = 'wcj_widget_your_widget';
        $this->widget_description = __( 'Description', 'woocommerce-jetpack' );
        $this->widget_id          = 'wcj_widget_your_widget';
        $this->widget_name        = __( 'Booster - Your Widget', 'woocommerce-jetpack' );
        $this->settings           = array(
            'title' => array(
                'type'  => 'text',
                'std'   => __( 'Widget Title', 'woocommerce-jetpack' ),
                'label' => __( 'Title', 'woocommerce-jetpack' ),
            ),
        );
        parent::__construct();
    }

    public function widget( $args, $instance ) {
        echo $args['before_widget'];
        if ( ! empty( $instance['title'] ) ) {
            echo $args['before_title'] . esc_html( $instance['title'] ) . $args['after_title'];
        }
        // Widget content
        echo '<p>Your widget content</p>';
        echo $args['after_widget'];
    }

}

endif;
```

### 2. Register in Loader

**File:** `includes/core/wcj-loader.php`

```php
require_once WCJ_FREE_PLUGIN_PATH . '/includes/widgets/class-wcj-widget-your-widget.php';
```

---

## How to Add AJAX Handler

### PHP Side

```php
// In module constructor
add_action( 'wp_ajax_wcj_your_action', array( $this, 'ajax_handler' ) );
add_action( 'wp_ajax_nopriv_wcj_your_action', array( $this, 'ajax_handler' ) );

public function ajax_handler() {
    check_ajax_referer( 'wcj-your-action', 'nonce' );

    $result = array( 'success' => true );

    // Your logic here

    wp_send_json( $result );
}
```

### JavaScript Side

```javascript
jQuery.ajax({
    url: wcj_your_module.ajax_url,
    type: 'POST',
    data: {
        action: 'wcj_your_action',
        nonce: wcj_your_module.nonce,
        // your data
    },
    success: function( response ) {
        if ( response.success ) {
            // Handle success
        }
    }
});
```

---

## Where to Put Shared Helpers

### Existing Function Files

| File | Purpose |
|------|---------|
| `includes/functions/wcj-functions-core.php` | Core WP utilities |
| `includes/functions/wcj-functions-booster-core.php` | Booster-specific core |
| `includes/functions/wcj-functions-admin.php` | Admin-only helpers |
| `includes/functions/wcj-functions-products.php` | Product helpers |
| `includes/functions/wcj-functions-orders.php` | Order helpers |
| `includes/functions/wcj-functions-price-currency.php` | Price/currency |
| `includes/functions/wcj-functions-html.php` | HTML generation |
| `includes/functions/wcj-functions-general.php` | General utilities |

### Adding New Helper

```php
// In appropriate function file
if ( ! function_exists( 'wcj_your_helper' ) ) {
    /**
     * Your helper description.
     *
     * @version 7.9.0
     * @since   7.9.0
     * @param mixed $param Description.
     * @return mixed
     */
    function wcj_your_helper( $param ) {
        // Your code
        return $result;
    }
}
```

---

## How to Add Product Meta Box

### 1. Create Meta Box Settings

**File:** `includes/settings/meta-box/wcj-settings-meta-box-your-module.php`

```php
<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return array(
    array(
        'type'  => 'title',
        'title' => __( 'Your Module Settings', 'woocommerce-jetpack' ),
    ),
    array(
        'name'    => 'wcj_your_module_per_product',
        'default' => '',
        'type'    => 'text',
        'title'   => __( 'Per-Product Setting', 'woocommerce-jetpack' ),
    ),
);
```

### 2. Register in Module

```php
public function __construct() {
    // ... other setup ...

    if ( $this->is_enabled() ) {
        $this->meta_box_screen   = 'product';
        $this->meta_box_context  = 'normal';
        $this->meta_box_priority = 'high';

        add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
        add_action( 'save_post_product', array( $this, 'save_meta_box' ), 10, 2 );
    }
}

public function get_meta_box_options() {
    return require WCJ_FREE_PLUGIN_PATH . '/includes/settings/meta-box/wcj-settings-meta-box-your-module.php';
}
```

---

## Running Tests/Linting

### PHP Linting

The repo doesn't include automated tests, but you can use:

```bash
# PHP syntax check
php -l includes/class-wcj-your-module.php

# PHPCS (if configured)
phpcs --standard=WordPress includes/class-wcj-your-module.php
```

### JavaScript

```bash
# If ESLint is configured
eslint includes/js/your-module.js
```

### Manual Testing

1. Enable WP_DEBUG in wp-config.php
2. Enable WP_DEBUG_LOG
3. Check `/wp-content/debug.log` for errors
4. Test module enable/disable
5. Test all settings save correctly
6. Test frontend output
7. Test with caching plugins
8. Test with WPML if relevant
