# Gating & Licensing

**Key Takeaways:**
- **No licensing system** in this free repo - it's the open WordPress.org version
- Premium features gated via `apply_filters( 'booster_option', $free_value, $premium_value )`
- Free version returns `$free_value`, Premium hooks return `$premium_value`
- Some features are "soft gated" (UI visible but disabled)
- "Upgrade Blocks" system shows Lite -> Elite comparisons

---

## Gating Architecture

### The Core Pattern

```php
apply_filters( 'booster_option', $free_value, $premium_value )
```

In the **free version**:
- Filter returns `$free_value` (first argument)
- Usually an empty string, 'readonly', or default fallback

In **Premium versions** (Plus/Elite):
- A filter hooks into `booster_option`
- Returns `$premium_value` (second argument)
- Unlocks the full feature

### Example: Settings Field Gating

```php
// In settings file
array(
    'title'             => __( 'Label to Show on Single', 'woocommerce-jetpack' ),
    'desc'              => apply_filters( 'booster_message', '', 'desc' ),
    'id'                => 'wcj_call_for_price_text',
    'default'           => '<strong>Call for price</strong>',
    'type'              => 'textarea',
    'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
)
```

**In free version:**
- `desc` = '' (empty)
- `custom_attributes` = '' (empty, so field is editable? Actually this returns nothing)

Wait, let me re-check. Looking at the code:

```php
// In free version, apply_filters returns first arg
apply_filters( 'booster_message', '', 'desc' )  // Returns ''
apply_filters( 'booster_message', '', 'readonly' )  // Returns ''
```

So in free, description shows "Upgrade Booster to change value" and field is readonly.

Actually, looking at `wcj_get_plus_message()`:

```php
function wcj_get_plus_message( $value, $message_type, $args = array() ) {
    switch ( $message_type ) {
        case 'desc':
            return sprintf( 'Upgrade <a href="%s">Booster</a> to change value.', ... );
        case 'readonly':
            return array( 'readonly' => 'readonly' );
        // ...
    }
    return $value;  // Default: return first arg
}
```

And in `class-wcj-admin.php`:
```php
add_filter( 'booster_message', 'wcj_get_plus_message', 100, 3 );
```

So the filter **hooks to `booster_message`**, not `booster_option`. Let me revise:

---

## Two Gating Filters

### 1. `booster_message` - UI Messages & Attributes

Used for settings UI:

```php
apply_filters( 'booster_message', $default_value, $message_type, $args )
```

| Message Type | Free Returns |
|--------------|--------------|
| `'desc'` | "Upgrade Booster to change value." link |
| `'readonly'` | `array( 'readonly' => 'readonly' )` |
| `'disabled'` | `array( 'disabled' => 'disabled' )` |
| `'readonly_string'` | `'readonly'` |
| `'disabled_string'` | `'disabled'` |
| `'global'` | Upgrade notice HTML block |
| `'desc_below'` | "Upgrade to change values below." |
| `'desc_above'` | "Upgrade to change values above." |

**Implementation:** `includes/functions/wcj-functions-admin.php:342-405`

### 2. `booster_option` - Feature Values

Used for actual feature values:

```php
apply_filters( 'booster_option', $free_value, $premium_value )
```

**In free version:**
- Always returns `$free_value` (first argument)
- Usually a limited default

**In premium:**
- Filter added to return `$premium_value`

**Example in code:**

```php
// In class-wcj-call-for-price.php:180
return do_shortcode(
    apply_filters( 'booster_option',
        '<strong>Call for price</strong>',  // Free: returns this
        wcj_get_option( 'wcj_call_for_price_text' )  // Premium: returns this
    )
);
```

---

## Gating Styles

### Style 1: Soft Gated (UI Present, Disabled)

Setting visible but readonly/disabled:

```php
array(
    'title'             => __( 'Premium Feature', 'woocommerce-jetpack' ),
    'desc'              => apply_filters( 'booster_message', '', 'desc' ),
    'id'                => 'wcj_module_premium_setting',
    'default'           => 'default',
    'type'              => 'text',
    'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
)
```

### Style 2: Hidden on Free

Setting completely hidden in free version:

```php
array(
    'title'        => __( 'Premium Only', 'woocommerce-jetpack' ),
    'id'           => 'wcj_module_premium_only',
    'type'         => 'text',
    'hide_on_free' => true,  // Filtered out in free version
)
```

**Implementation in WCJ_Module:**

```php
public function handle_hide_on_free_parameter( $settings ) {
    if ( 'woocommerce-jetpack.php' !== basename( WCJ_FREE_PLUGIN_FILE ) ) {
        return $settings;  // Not free, keep all
    }
    // Remove settings with hide_on_free = true
    $settings = wp_list_filter( $settings, array( 'hide_on_free' => true ), 'NOT' );
    return $settings;
}
```

### Style 3: Value Limited

Feature works but with limited value:

```php
// In module code
$max_items = apply_filters( 'booster_option', 3, PHP_INT_MAX );
// Free: max 3 items
// Premium: unlimited
```

### Style 4: Per-Product Limit

Limited number of products with per-product settings:

```php
// In WCJ_Module::save_meta_box_value()
if ( true === apply_filters( 'booster_option', false, true ) ) {
    return $option_value;  // Premium: allow save
}
// Free: Check product count limit
$args = array( 'post_type' => 'product', 'meta_key' => '_wcj_...', ... );
$loop = new WP_Query( $args );
if ( $loop->found_posts >= 3 ) {
    // Show upgrade notice, don't save
    return 'no';
}
```

---

## Upgrade Blocks System

**File:** `includes/class-wcj-upgrade-blocks.php`

### Purpose

Shows contextual "Upgrade to Elite" panels in specific modules.

### Configuration

```php
function wcj_get_upgrade_blocks_config() {
    return array(
        'cart_abandonment' => array(
            'enabled'        => true,
            'module_id'      => 'cart_abandonment',
            'lite_label'     => __( 'Cart Abandoned Lite', 'woocommerce-jetpack' ),
            'headline'       => __( 'Unlock the full power in Booster Elite', 'woocommerce-jetpack' ),
            'benefits'       => array(
                __( 'Send up to 3 recovery emails (Lite: 1)', 'woocommerce-jetpack' ),
                __( 'Add automatic discount coupons', 'woocommerce-jetpack' ),
                // ...
            ),
            'comparison_url' => 'https://booster.io/docs/woocommerce-cart-abandonment/',
            'upgrade_url'    => 'https://booster.io/buy-booster/',
        ),
        'wishlist' => array( ... ),
        'product_variation_swatches' => array( ... ),
    );
}
```

### Rendering

```php
wcj_render_upgrade_block( 'cart_abandonment' );
// Outputs HTML notice with benefits and CTA buttons
```

### Click Tracking

Clicks are logged to `wcj_upgrade_block_clicks` option (max 500 events).

---

## Premium Plugin Detection

### In Main Plugin File

**File:** `woocommerce-jetpack.php:44-51`

```php
// Exit if premium version active (premium takes over)
if ( 'woocommerce-jetpack.php' === basename( __FILE__ ) &&
    ( wcj_is_plugin_activated( 'booster-plus-for-woocommerce', 'booster-plus-for-woocommerce.php' ) ||
      wcj_is_plugin_activated( 'booster-elite-for-woocommerce', 'booster-elite-for-woocommerce.php' ) ||
      wcj_is_plugin_activated( 'booster-basic-for-woocommerce', 'booster-basic-for-woocommerce.php' ) ||
      wcj_is_plugin_activated( 'booster-pro-for-woocommerce', 'booster-pro-for-woocommerce.php' ) )
) {
    return;  // Don't load free if premium active
}
```

### Version Option

Different option name based on plugin type:

```php
// In wcj-constants.php:114
define( 'WCJ_VERSION_OPTION',
    ( 'woocommerce-jetpack.php' === basename( WCJ_FREE_PLUGIN_FILE )
        ? 'booster_for_woocommerce_version'
        : 'booster_plus_for_woocommerce_version' )
);
```

---

## Free Version Checks

### Check if Free Version

```php
// Common pattern throughout codebase
if ( 'woocommerce-jetpack.php' === basename( WCJ_FREE_PLUGIN_FILE ) ) {
    // Free version specific code
}
```

### Plus Class Check

```php
// Check if Plus class exists (indicates premium)
if ( class_exists( 'WCJ_Plus' ) ) {
    // Premium features available
}
```

---

## Site Key (Premium Only)

Premium versions use a site key for license validation:

```php
// In General Settings
array(
    'title'   => __( 'Site Key', 'woocommerce-jetpack' ),
    'type'    => 'text',
    'id'      => 'wcj_site_key',
    'default' => '',
)
```

In free version, this setting exists but has no effect.

---

## Adding Elite-Only Features

### Step 1: Gate the Setting

```php
// In settings file
array(
    'title'             => __( 'Elite Feature', 'woocommerce-jetpack' ),
    'desc'              => apply_filters( 'booster_message', '', 'desc' ),
    'id'                => 'wcj_module_elite_feature',
    'default'           => 'no',
    'type'              => 'checkbox',
    'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
),
```

### Step 2: Gate the Functionality

```php
// In module code
public function elite_feature_method() {
    // Only runs if premium
    if ( true !== apply_filters( 'booster_option', false, true ) ) {
        return;  // Free version: exit early
    }

    // Elite functionality here
}
```

### Step 3: Gate the Value

```php
// Return limited value in free
$value = apply_filters( 'booster_option',
    'free_default',           // Free gets this
    wcj_get_option( '...' )   // Premium gets saved value
);
```

---

## Security Considerations

### Don't Trust booster_option Alone

The filter can be bypassed by adding a custom filter. For truly secure premium features:

1. **Code not present** - Best security, but requires separate codebases
2. **Server-side validation** - Validate license on critical operations
3. **Obfuscation** - Makes bypass harder but not impossible

### Current Approach (This Repo)

This free repo uses "soft gating" - premium code IS present but gated. Technically, someone could:
1. Add `add_filter( 'booster_option', fn($a, $b) => $b, 10, 2 );`
2. Unlock premium features without purchase

This is acceptable for:
- Open source / GPL software
- Features that don't pose security risks
- Encouraging upgrade rather than preventing use

---

## Plugin Updates

### Free Version

Updates via WordPress.org repository - standard WP update mechanism.

### Premium Versions

Premium plugins likely use:
- Custom update server
- License key validation
- Separate download endpoint

(Not implemented in this free repo)

---

## Summary: Where to Hook for Premium

| Purpose | Hook/Filter | Notes |
|---------|-------------|-------|
| Unlock settings UI | `booster_message` | Make fields editable |
| Unlock feature values | `booster_option` | Return premium values |
| Add premium-only settings | Check `basename( WCJ_FREE_PLUGIN_FILE )` | Conditionally add |
| Remove free limitations | Override limit checks | Use `booster_option` |
| Add premium classes | Check `class_exists( 'WCJ_Plus' )` | For conditional loading |
