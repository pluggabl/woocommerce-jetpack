<?php
/** Read-only family workspace and contextual guidance. */
defined( 'ABSPATH' ) || exit;

final class WCJ_Diagnose_Customize_Workspace {
 const MODULES = array( 'admin_tools', 'custom_css', 'custom_js', 'custom_php', 'debug_tools', 'old_slugs', 'template_editor', 'wpml' );

 public static function edition() {
  $file = defined( 'WCJ_PLUGIN_FILE' ) ? basename( WCJ_PLUGIN_FILE ) : '';
  return 'booster-elite-for-woocommerce.php' === $file ? 'elite' : ( 'booster-plus-for-woocommerce.php' === $file ? 'plus' : 'free' );
 }

 public static function capability() {
  return 'yes' === wcj_get_option( 'wcj_admin_tools_enabled', 'no' ) &&
   'yes' === wcj_get_option( 'wcj_admin_tools_show_menus_to_admin_only', 'no' ) ? 'manage_options' : 'manage_woocommerce';
 }

 public static function admitted() {
  return current_user_can( 'manage_woocommerce' ) && current_user_can( self::capability() ) &&
   (bool) apply_filters( 'wcj_can_create_admin_interface', true );
 }

 public static function allowed( $module ) {
  $core = WCJ_Job_Store::core();
  return in_array( $module, self::MODULES, true ) && self::admitted() && $core &&
   isset( $core->all_modules[ $module ] ) &&
   ( ! function_exists( 'wcj_is_module_allowed_by_allowlist' ) || wcj_is_module_allowed_by_allowlist( $module ) ) &&
   ( ! wcj_is_module_enabled( 'modules_by_user_roles' ) || wcj_check_modules_by_user_roles( $module ) );
 }

 public static function groups() { return require __DIR__ . '/diagnose-customize-groups.php'; }
 public static function url() { return admin_url( 'admin.php?page=wcj-diagnose-customize' ); }

 private static function link( $url, $label ) {
  return '<a target="_blank" rel="noopener noreferrer" href="' . esc_url( $url ) . '">' . esc_html( $label . ' (new tab)' ) . '</a>';
 }

 public static function native_url( $group ) {
  return self::allowed( $group['module'] ) ? admin_url( wcj_admin_tab_url() . '&wcj-cat=emails_and_misc&section=' .
   $group['module'] . '&active_tab=' . $group['native_tab'] ) : '';
 }

 private static function tool_url( $module ) {
  $tabs = array( 'admin_tools' => 'products_atts', 'old_slugs' => 'old_slugs', 'debug_tools' => 'debug_tools' );
  return isset( $tabs[ $module ] ) && self::allowed( $module ) && wcj_is_module_enabled( $module ) ?
   admin_url( 'admin.php?page=wcj-tools&tab=' . $tabs[ $module ] . '&wcj_tools_nonce=' . wp_create_nonce( 'wcj_tools' ) ) : '';
 }

 /** Only length and a short scalar projection, never code, paths or serialized option contents. */
 public static function option_shape( $key, $with_scalar = true ) {
  global $wpdb;
  $scalar = $with_scalar ? 'CASE WHEN OCTET_LENGTH(option_value)<=16 THEN option_value ELSE NULL END' : 'NULL';
  $rows = $wpdb->get_results( $wpdb->prepare(
   "SELECT OCTET_LENGTH(option_value) AS bytes, {$scalar} AS scalar
    FROM {$wpdb->options} WHERE option_name=%s LIMIT 2", $key ), ARRAY_A );
  if ( $wpdb->last_error || ! is_array( $rows ) || count( $rows ) > 1 ) { throw new RuntimeException( 'Summary unavailable.' ); }
  return $rows ? $rows[0] : null;
 }

 public static function flag( $key, $default = 'no' ) {
  $row = self::option_shape( $key );
  if ( null === $row ) { return 'No saved value; default ' . ( 'yes' === $default ? 'On' : 'Off' ); }
  return 'yes' === $row['scalar'] ? 'Saved On' : ( 'no' === $row['scalar'] ? 'Saved Off' : 'Saved choice needs full settings' );
 }

 public static function presence( $key ) {
  $row = self::option_shape( $key, false );
  return null === $row ? 'No saved value' : ( 0 === (int) $row['bytes'] ? 'Saved empty' : 'Saved content present; not loaded or inspected here' );
 }

 public static function summary( $module ) {
  $rows = array( 'Module' => self::flag( 'wcj_' . $module . '_enabled' ) );
  $keys = array(
   'admin_tools' => array( 'Admin-only menus' => 'wcj_admin_tools_show_menus_to_admin_only', 'Order metadata panel' => 'wcj_admin_tools_show_order_meta_enabled', 'Product metadata panel' => 'wcj_admin_tools_show_product_meta_enabled', 'Variable pricing panel' => 'wcj_admin_tools_variable_product_pricing_table_enabled' ),
   'custom_css' => array( 'Per-product CSS controls' => 'wcj_custom_css_per_product' ),
   'debug_tools' => array( 'Booster logging' => 'wcj_logging_enabled', 'WooCommerce logging' => 'wcj_wc_logging_enabled', 'Debugging' => 'wcj_debuging_enabled' ),
   'wpml' => array( 'Currency switching' => 'wcj_wpml_switch_booster_currency', 'Automatic configuration regeneration' => 'wcj_wpml_config_xml_auto_regenerate' ),
  );
  foreach ( $keys[ $module ] ?? array() as $label => $key ) { $rows[ $label ] = self::flag( $key ); }
  $content = array(
   'custom_css' => array( 'Customer CSS' => 'wcj_general_custom_css', 'Admin CSS' => 'wcj_general_custom_admin_css' ),
   'custom_js' => array( 'Customer JavaScript' => 'wcj_custom_js_frontend', 'Admin JavaScript' => 'wcj_custom_js_backend' ),
   'custom_php' => array( 'PHP setting' => 'wcj_custom_php' ),
   'template_editor' => array( 'Path selection' => 'wcj_template_editor_paths', 'Template selection' => 'wcj_template_editor_templates_to_edit' ),
  );
  foreach ( $content[ $module ] ?? array() as $label => $key ) { $rows[ $label ] = self::presence( $key ); }
  if ( 'old_slugs' === $module ) { $rows['History check'] = 'Available without enabling cleanup; no product read until you submit its ID'; }
  if ( 'template_editor' === $module ) { $rows['Discovery'] = 'No paths scanned and no template files opened here'; }
  if ( 'wpml' === $module ) { $rows['Dependencies'] = 'Translation and currency extensions are not tested here'; }
  return $rows;
 }

 public static function help( $pick = array() ) {
  $edition = self::edition();
  foreach ( self::groups() as $group ) {
   if ( ( $pick && ! in_array( $group['id'], $pick, true ) ) || ! self::allowed( $group['module'] ) ) { continue; }
   echo '<article id="' . esc_attr( $group['anchor'] ) . '" data-diagnose-group="' . esc_attr( $group['id'] ) . '"><h3>' . esc_html( $group['label'] ) . '</h3><p>' . esc_html( $group['context'] ) . '</p><p>' .
    self::link( self::native_url( $group ), 'Full settings' ) . ' | ' . self::link( $group['guide'], 'Guide: ' . $group['label'] ) . '</p>';
   if ( in_array( $group['id'], array( 'admin-attributes', 'old-slugs-cleanup', 'debug-inspect' ), true ) ) {
    $tool = self::tool_url( $group['module'] );
    if ( $tool ) { echo '<p>' . self::link( $tool, 'Open existing tool deliberately' ) . '</p>'; }
   }
   if ( 'php-code' === $group['id'] ) {
    echo '<p>' . self::link( self::native_url( $group ) . '&wcj_disable_custom_php=1&wcj_disable_custom_php_nonce=' . wp_create_nonce( 'wcj-disable-custom-php' ), 'Existing one-request PHP recovery link' ) . '</p>';
   }
   echo '<details><summary>Fields and actions covered</summary><ul>';
   foreach ( $group['members'][ $edition ] as $member ) {
    echo '<li>' . esc_html( $member['label'] . ( $member['locked'] ? ' (locked in Free)' : '' ) ) . '</li>';
   }
   foreach ( array_merge( $group['actions'], $group['common_controls'] ?? array() ) as $label ) { echo '<li>' . esc_html( $label ) . '</li>'; }
   echo '</ul></details></article>';
  }
 }

 public static function screen_help() {
  if ( ! self::admitted() ) { return; }
  $page = $_GET['page'] ?? ''; $section = $_GET['section'] ?? ''; $tab = $_GET['tab'] ?? ''; $active = $_GET['active_tab'] ?? '';
  foreach ( array( $page, $section, $tab, $active ) as $value ) { if ( ! is_string( $value ) ) { return; } }
  $pick = array();
  foreach ( self::groups() as $group ) {
   if ( ! self::allowed( $group['module'] ) ) { continue; }
   if ( 'wcj-plugins' === $page && $section === $group['module'] && ( ! $active || $active === $group['native_tab'] ||
    ( 'debug-inspect' === $group['id'] && 'debug_tools_general_options_tab' === $active ) ) ) { $pick[] = $group['id']; }
  }
  $tools = array( 'products_atts' => array( 'admin_tools', 'admin-attributes' ), 'old_slugs' => array( 'old_slugs', 'old-slugs-cleanup' ), 'debug_tools' => array( 'debug_tools', 'debug-inspect' ) );
  if ( 'wcj-tools' === $page && isset( $tools[ $tab ] ) && self::allowed( $tools[ $tab ][0] ) && wcj_is_module_enabled( $tools[ $tab ][0] ) ) { $pick[] = $tools[ $tab ][1]; }
  $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
  if ( $screen && 'product' === $screen->id ) {
   if ( self::allowed( 'custom_css' ) && wcj_is_module_enabled( 'custom_css' ) && 'yes' === wcj_get_option( 'wcj_custom_css_per_product', 'no' ) ) { $pick[] = 'css-product'; }
   if ( self::allowed( 'admin_tools' ) && wcj_is_module_enabled( 'admin_tools' ) &&
    ( 'yes' === wcj_get_option( 'wcj_admin_tools_show_product_meta_enabled', 'no' ) || 'yes' === wcj_get_option( 'wcj_admin_tools_variable_product_pricing_table_enabled', 'no' ) ) ) { $pick[] = 'admin-products'; }
  }
  $order_edit = $screen && ( 'shop_order' === $screen->id || ( 'woocommerce_page_wc-orders' === $screen->id && in_array( $_GET['action'] ?? '', array( 'edit', 'new' ), true ) ) );
  if ( $order_edit && self::allowed( 'admin_tools' ) && wcj_is_module_enabled( 'admin_tools' ) && 'yes' === wcj_get_option( 'wcj_admin_tools_show_order_meta_enabled', 'no' ) ) { $pick[] = 'admin-orders'; }
  if ( ! $pick ) { return; }
  echo '<div class="notice notice-info"><p><a href="' . esc_url( self::url() ) . '">Diagnose and customize safely</a></p><details><summary>These controls: effects, checks and recovery</summary>';
  self::help( array_unique( $pick ) );
  echo '</details></div>';
 }

 public static function entry() {
  foreach ( self::MODULES as $module ) {
   if ( self::allowed( $module ) ) { echo '<p><a href="' . esc_url( self::url() ) . '">Diagnose and customize: check one product\'s saved old addresses</a></p>'; return; }
  }
 }

 public static function register() {
  if ( self::admitted() ) { add_submenu_page( 'woocommerce', 'Diagnose and customize', 'Diagnose and customize', self::capability(), 'wcj-diagnose-customize', array( __CLASS__, 'render' ) ); }
 }

 private static function form() {
  echo '<section class="wcj-history-task" id="booster-v10-s26-product-history"><p class="description">Old Slugs / Links and translation</p><h2>Check a product\'s old addresses</h2><p>See the old address text (slugs) WordPress has saved for one product.</p><form><label for="wcj-history-id">Product or variation ID</label> <input id="wcj-history-id" name="id" type="text" inputmode="numeric" maxlength="19" autocomplete="off" aria-describedby="wcj-history-boundary"> <button class="button button-primary" type="submit">Show saved history</button></form><p id="wcj-history-boundary">This check only reads saved records. It does not test redirects, open links, delete history or change your product. The cleanup module can remain off.</p><p class="wcj-history-status" role="status" aria-live="polite">No history has been read. Enter one ID and choose Show saved history.</p><div class="wcj-history-output" tabindex="-1"></div><details><summary>Product scope and display limits</summary><p>Products may be published, draft, pending or private if you can edit them. Variations also require access to their parent product; inherited status is checked through that parent. Trash and other statuses are not supported.</p><p>Up to 25 entries are displayed. At most 100 are checked, plus one extra record to detect more than 100. Text is shown up to 200 characters and clearly marked if shortened. Unsupported formats or values over 4,096 bytes are refused, not interpreted. Stored order is not a history of dates, and duplicate entries are not merged.</p><p>Saved records do not prove a browser redirect works or a page loads. There is no Undo because this check does not change the records. The separate existing cleanup tool has irreversible remove-all actions.</p></details></section>';
 }

 public static function render() {
  if ( ! self::admitted() ) { wp_die( 'Ask an administrator for access.' ); }
  wp_enqueue_style( 'wcj-diagnose-customize', wcj_plugin_url() . '/assets/css/admin/wcj-diagnose-customize.css', array(), '1.0' );
  wp_enqueue_script( 'wcj-diagnose-customize', wcj_plugin_url() . '/assets/js/admin/wcj-diagnose-customize.js', array(), '1.0', true );
  wp_localize_script( 'wcj-diagnose-customize', 'wcjDiagnoseCustomize', array( 'ajax' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'wcj_product_slug_history' ) ) );
  echo '<div class="wrap wcj-diagnose-customize"><h1>Diagnose and customize safely</h1><p>Check one product\'s saved history, then use focused guidance for expert settings. This workspace does not run or change your saved custom code. Already-enabled site code can still run during ordinary WordPress startup.</p>';
  if ( self::allowed( 'old_slugs' ) ) { self::form(); }
  $sections = array( "Find out what's happening", 'Customize with code', 'Admin workspace', 'Links and translation' );
  foreach ( $sections as $section ) {
   $pick = array(); $modules = array();
   foreach ( self::groups() as $group ) {
    if ( $section === $group['section'] && self::allowed( $group['module'] ) ) { $pick[] = $group['id']; $modules[ $group['module'] ] = true; }
   }
   if ( ! $pick && "Find out what's happening" !== $section ) { continue; }
   echo '<details class="wcj-diagnose-section"><summary>' . esc_html( $section ) . '</summary>';
   if ( "Find out what's happening" === $section ) {
    echo '<p>Existing diagnostics keep their own limits. Order Health is a bounded sample, Rule Explanations gives estimates rather than final totals, and status checks do not prove credentials or integrations work.</p><p>' .
     self::link( admin_url( 'admin.php?page=wcj-order-health' ), 'Order Health' ) . ' | ' .
     self::link( admin_url( 'admin.php?page=wcj-rule-explanations' ), 'Rule Explanations' ) . ' | ' .
     self::link( admin_url( 'admin.php?page=wcj-compatibility-status' ), 'Compatibility and readiness status' ) . '</p>';
   }
   try {
    foreach ( array_keys( $modules ) as $module ) {
     echo '<details class="wcj-diagnose-summary"><summary>' . esc_html( ( array(
				'custom_css' => 'Custom CSS: settings overview',
				'custom_js'  => 'Custom JavaScript: settings overview',
				'custom_php' => 'Custom PHP: settings overview',
				'template_editor' => 'Template Editor: settings overview',
			)[ $module ] ?? ucwords( str_replace( '_', ' ', $module ) ) . ': saved configuration' ) ) . '</summary><dl>';
     foreach ( self::summary( $module ) as $label => $value ) { echo '<div><dt>' . esc_html( $label ) . '</dt><dd>' . esc_html( $value ) . '</dd></div>'; }
     echo '</dl></details>';
    }
   } catch ( Throwable $e ) { echo '<p>Saved configuration could not be summarized safely. Use full settings.</p>'; }
   self::help( $pick );
   echo '</details>';
  }
  echo '</div>';
 }
}
new WCJ_Product_Slug_History();
add_action( 'admin_menu', array( 'WCJ_Diagnose_Customize_Workspace', 'register' ), 99 );
add_action( 'admin_notices', array( 'WCJ_Diagnose_Customize_Workspace', 'screen_help' ), 45 );
