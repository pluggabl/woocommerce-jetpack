# Search Cheatsheet

**Quick reference for finding things in this codebase.**

---

## Finding Modules

| To Find | Search Term | Location |
|---------|-------------|----------|
| Module registry | `$wcj_module_files` | `includes/core/wcj-modules.php` |
| Module class | `class-wcj-{module-id}.php` | `includes/` |
| Module by ID | `$this->id = '{module_id}'` | `includes/class-wcj-*.php` |
| Module categories | `wcj_modules` filter | `includes/admin/wcj-modules-cats.php` |
| Enabled check | `wcj_is_module_enabled` | `includes/functions/wcj-functions-booster-core.php` |

### Example Searches

```bash
# Find all module classes
grep -l "extends WCJ_Module" includes/class-wcj-*.php

# Find a specific module
grep -r "id.*=.*'multicurrency'" includes/

# Find where module is enabled/disabled
grep -r "wcj_multicurrency_enabled" includes/
```

---

## Finding Settings

| To Find | Search Term | Location |
|---------|-------------|----------|
| Settings file | `wcj-settings-{module-id}.php` | `includes/settings/` |
| Option by name | `'id' => 'wcj_...'` | `includes/settings/` |
| Option value | `wcj_get_option( 'wcj_...'` | Throughout |
| Meta box settings | `wcj-settings-meta-box-*.php` | `includes/settings/meta-box/` |
| Settings save | `wcj_save_module_settings` | `includes/core/class-wcj-admin.php` |

### Example Searches

```bash
# Find all settings for a module
grep -l "call_for_price" includes/settings/

# Find where an option is used
grep -rn "wcj_call_for_price_text" includes/

# Find all textarea settings
grep -rn "'type'.*=>.*'textarea'" includes/settings/
```

---

## Finding Premium Gating

| To Find | Search Term | Location |
|---------|-------------|----------|
| Gated settings | `apply_filters( 'booster_message'` | `includes/settings/` |
| Gated values | `apply_filters( 'booster_option'` | `includes/class-wcj-*.php` |
| Plus message | `wcj_get_plus_message` | `includes/functions/wcj-functions-admin.php` |
| Upgrade blocks | `wcj_render_upgrade_block` | `includes/class-wcj-upgrade-blocks.php` |
| Free version check | `woocommerce-jetpack.php.*basename` | Throughout |

### Example Searches

```bash
# Find all gated settings
grep -rn "booster_message" includes/settings/

# Find all gated values
grep -rn "booster_option" includes/class-wcj-*.php

# Find upgrade block configs
grep -A20 "wcj_get_upgrade_blocks_config" includes/class-wcj-upgrade-blocks.php
```

---

## Finding Hooks

| To Find | Search Term | Location |
|---------|-------------|----------|
| WooCommerce hooks | `add_filter( 'woocommerce_` | `includes/class-wcj-*.php` |
| WordPress hooks | `add_action( 'wp_` or `add_filter( 'wp_` | Throughout |
| Booster hooks | `do_action( 'wcj_` or `apply_filters( 'wcj_` | Throughout |
| Custom hook | `add_action( 'wcj_` | Throughout |

### Example Searches

```bash
# Find all WooCommerce hooks used
grep -rn "add_filter.*'woocommerce_" includes/

# Find where price is filtered
grep -rn "woocommerce_get_price\|WCJ_PRODUCT_GET_PRICE" includes/

# Find all custom Booster hooks
grep -rn "do_action.*'wcj_\|apply_filters.*'wcj_" includes/
```

---

## Finding Admin UI

| To Find | Search Term | Location |
|---------|-------------|----------|
| Admin menu | `add_menu_page\|add_submenu_page` | `includes/core/class-wcj-admin.php` |
| Dashboard | `wcj-settings-dashboard.php` | `includes/admin/` |
| Module list | `wcj-settings-plugins.php` | `includes/admin/` |
| Meta boxes | `add_meta_box` | `includes/class-wcj-*.php` |
| Admin scripts | `admin_enqueue_scripts` | Throughout |

### Example Searches

```bash
# Find all admin pages
grep -rn "add_submenu_page\|add_menu_page" includes/

# Find admin CSS/JS
grep -rn "admin_enqueue_scripts" includes/

# Find meta box registrations
grep -rn "add_meta_box" includes/class-wcj-*.php
```

---

## Finding Shortcodes

| To Find | Search Term | Location |
|---------|-------------|----------|
| Shortcode files | `class-wcj-*-shortcodes.php` | `includes/shortcodes/` |
| Shortcode registry | `$wcj_shortcodes_files` | `includes/core/wcj-shortcodes.php` |
| Shortcode tag | `add_shortcode( 'wcj_` | `includes/shortcodes/` |
| Shortcode class | `extends WCJ_Shortcodes` | `includes/shortcodes/` |

### Example Searches

```bash
# Find all shortcode tags
grep -rn "add_shortcode.*'wcj_" includes/shortcodes/

# Find shortcode implementation
grep -A20 "function wcj_product_price" includes/shortcodes/
```

---

## Finding Frontend Output

| To Find | Search Term | Location |
|---------|-------------|----------|
| Frontend scripts | `wp_enqueue_scripts` | `includes/class-wcj-*.php` |
| Frontend CSS | `wp_enqueue_style` | Throughout |
| Template output | `woocommerce_` + `_html` hooks | Throughout |
| Price filters | `woocommerce_get_price_html` | `includes/class-wcj-*.php` |

---

## Finding Functions

| To Find | Search Term | Location |
|---------|-------------|----------|
| Core functions | `function wcj_` | `includes/functions/` |
| Admin functions | `wcj-functions-admin.php` | `includes/functions/` |
| Product functions | `wcj-functions-products.php` | `includes/functions/` |
| Order functions | `wcj-functions-orders.php` | `includes/functions/` |

### Example Searches

```bash
# Find all helper functions
grep -rn "^function wcj_" includes/functions/

# Find function definition
grep -rn "function wcj_get_option" includes/

# Find function usage
grep -rn "wcj_get_option(" includes/class-wcj-*.php
```

---

## Key File Locations

| Component | File |
|-----------|------|
| Main plugin entry | `woocommerce-jetpack.php` |
| Loader | `includes/core/wcj-loader.php` |
| Module registry | `includes/core/wcj-modules.php` |
| Base module class | `includes/classes/class-wcj-module.php` |
| Admin core | `includes/core/class-wcj-admin.php` |
| Module categories | `includes/admin/wcj-modules-cats.php` |
| Constants | `includes/core/wcj-constants.php` |
| Scripts handler | `includes/core/class-wcj-scripts.php` |
| Core functions | `includes/core/wcj-functions.php` |
| Booster functions | `includes/functions/wcj-functions-booster-core.php` |
| Admin functions | `includes/functions/wcj-functions-admin.php` |
| Gating/upgrade | `includes/class-wcj-upgrade-blocks.php` |

---

## Regex Patterns

### Find Module Definition

```regex
class WCJ_([A-Za-z_]+) extends WCJ_Module
```

### Find Option Definition

```regex
'id'\s*=>\s*'wcj_[a-z_]+'
```

### Find Hook Registration

```regex
add_(action|filter)\s*\(\s*'[^']+'\s*,
```

### Find Translation Strings

```regex
__\(\s*'[^']+'\s*,\s*'woocommerce-jetpack'\s*\)
```

---

## VS Code / IDE Search Tips

### Search in Specific Folders

```
# Only in module classes
includes/class-wcj-*.php

# Only in settings
includes/settings/

# Only in functions
includes/functions/
```

### Useful Search Queries

```
# Find all enabled module hooks
is_enabled().*add_filter

# Find all premium gating points
apply_filters.*booster_

# Find all WC hooks
woocommerce_[a-z_]+
```

---

## CLI Commands

### Find Files

```bash
# All module files
find includes -name "class-wcj-*.php" -type f

# All settings files
find includes/settings -name "wcj-settings-*.php" -type f

# Files modified recently
find includes -name "*.php" -mtime -7
```

### Search Content

```bash
# Case-insensitive search
grep -ri "multicurrency" includes/

# Search with context
grep -B2 -A5 "function your_function" includes/

# Count occurrences
grep -c "wcj_get_option" includes/class-wcj-*.php
```

---

## Common Debugging Searches

| Issue | Search For |
|-------|------------|
| Settings not saving | `WC_Admin_Settings::save_fields` |
| Option not loading | `wcj_get_option`, `get_option` |
| Hook not firing | `add_filter`, `add_action` + hook name |
| Premium feature broken | `booster_option`, `booster_message` |
| Module not loading | `$wcj_module_files`, `include_once` |
| Admin page missing | `add_submenu_page`, `admin_menu` |
