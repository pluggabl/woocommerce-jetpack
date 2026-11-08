<?php
/** One existing percentage-discount coefficient. No product/cart or expression evaluation.
 * @package Booster_For_WooCommerce/admin
 */
defined( 'ABSPATH' ) || exit;
require_once __DIR__ . '/class-wcj-job-store.php';
require_once __DIR__ . '/pricing-discount-groups.php';
final class WCJ_Global_Discount_Job {
 const ID = 'global_discount';
 const MODULE = 'wcj_global_discount_enabled';
 const RECEIPT = 'booster_global_discount_job_receipt';
 const OPS = array( 'load', 'review', 'save', 'undo' );
 const FIELDS = array( 'enabled', 'coefficient_type', 'coefficient', 'final_correction_func', 'final_correction_coef', 'product_scope', 'products_incl', 'products_excl', 'categories_incl', 'categories_excl', 'tags_incl', 'tags_excl' );
 public function __construct() {
  add_action( 'admin_menu', array( $this, 'register' ), 99 );
  foreach ( self::OPS as $op ) { add_action( 'wp_ajax_wcj_global_discount_job_' . $op, array( $this, 'handle' ) ); }
 }
 public static function url() { return admin_url( 'admin.php?page=wcj-global-discount-job' ); }
 public static function native_url() { return admin_url( wcj_admin_tab_url() . '&wcj-cat=prices_and_currencies&section=global_discount&active_tab=global_discount_general_options_tab' ); }
 public static function edition() { return defined( 'WCJ_PLUGIN_FILE' ) && 'booster-elite-for-woocommerce.php' === basename( WCJ_PLUGIN_FILE ) ? 'elite' : ( defined( 'WCJ_PLUGIN_FILE' ) && 'booster-plus-for-woocommerce.php' === basename( WCJ_PLUGIN_FILE ) ? 'plus' : 'free' ); }
 private static function fail( $message, $code = 422 ) { throw new RuntimeException( $message, $code ); }
 private static function authorize() { if ( ! current_user_can( 'manage_woocommerce' ) || ! WCJ_Task_Finder::allowed( self::ID ) ) { self::fail( 'Ask an administrator for access to Global Discount settings.', 403 ); } }
 public function register() { if ( current_user_can( 'manage_woocommerce' ) && WCJ_Task_Finder::allowed( self::ID ) ) { add_submenu_page( 'woocommerce', 'Adjust an existing percentage discount', 'Percentage discount', 'manage_woocommerce', 'wcj-global-discount-job', array( $this, 'render' ) ); } }
 /** Scaled integers only. Identical ASCII grammar in the browser; no implicit conversion. */
 public static function decimal( $v, $example = false ) {
  $pattern = $example ? '/^(0|[1-9][0-9]{0,3})(?:\.([0-9]{1,4}))?$/D' : '/^(0|[1-9][0-9]?)(?:\.([0-9]{1,4}))?$/D';
  if ( ! is_string( $v ) || ! preg_match( $pattern, $v, $m ) ) { return null; }
  $n = (int) $m[1] * 10000 + (int) str_pad( $m[2] ?? '', 4, '0' );
  return $n > 0 && $n <= ( $example ? 99999999 : 999999 ) ? $n : null;
 }
 public static function negative( $v ) { return is_string( $v ) && isset( $v[0] ) && '-' === $v[0] ? self::decimal( substr( $v, 1 ) ) : null; }
 public static function arithmetic( $amount, $percent ) {
  $a = self::decimal( $amount, true ); $p = self::decimal( $percent );
  if ( null === $a || null === $p || PHP_INT_SIZE < 8 ) { return null; }
  $scaled = $a * ( 1000000 - $p );
  $digits = str_pad( (string) $scaled, 11, '0', STR_PAD_LEFT );
  $fraction = rtrim( substr( $digits, -10 ), '0' );
  return substr( $digits, 0, -10 ) . ( '' === $fraction ? '' : '.' . $fraction );
 }
 private static function key( $group, $field = 'coefficient' ) {
  if ( ! is_string( $group ) || ! preg_match( '/^(?:[1-9][0-9]?|100)$/D', $group ) || ! in_array( $field, self::FIELDS, true ) ) { self::fail( 'Select a supported existing group.', 409 ); }
  return 'wcj_global_discount_sale_' . $field . '_' . $group;
 }
 private static function raw( $c, $key, $default = null ) { return isset( $c['rows'][ $key ] ) ? $c['rows'][ $key ]['raw'] : $default; }
 private static function state( $c, $key ) { return $c['rows'][ $key ] ?? null; }
 private static function hash( $group, $c, $receipt = true ) { if ( ! $receipt ) { unset( $c['rows'][ self::RECEIPT ] ); } return hash_hmac( 'sha256', serialize( array( get_current_blog_id(), get_current_user_id(), self::edition(), $group, $c ) ), wp_salt( 'nonce' ) ); }
 private static function count_groups( $rows ) {
  $raw = isset( $rows['wcj_global_discount_groups_total_number'] ) ? $rows['wcj_global_discount_groups_total_number']['raw'] : '1';
  if ( ! preg_match( '/^(?:[1-9][0-9]?|100)$/D', $raw ) ) { return null; }
  return 'free' === self::edition() ? 1 : (int) $raw;
 }
 /** Safe display of saved ID restrictions, without products/terms queries or normalization. */
 public static function selector( $raw, $products = false ) {
  if ( null === $raw || '' === $raw ) { return array( 'supported' => true, 'text' => 'None saved' ); }
  if ( ! is_string( $raw ) || strlen( $raw ) > 16000 ) { return array( 'supported' => false, 'text' => 'Custom or oversized saved restriction: use full settings' ); }
  if ( is_serialized( $raw ) ) {
   $values = @unserialize( $raw, array( 'allowed_classes' => false ) );
   if ( ! is_array( $values ) || count( $values ) > 500 || array_keys( $values ) !== ( empty( $values ) ? array() : range( 0, count( $values ) - 1 ) ) ) { return array( 'supported' => false, 'text' => 'Custom saved restriction: use full settings' ); }
   foreach ( $values as $v ) { if ( ! is_string( $v ) || ! preg_match( '/^[1-9][0-9]{0,9}$/D', $v ) ) { return array( 'supported' => false, 'text' => 'Nonstandard saved ID values: use full settings' ); } }
   return array( 'supported' => true, 'text' => $values ? 'Saved IDs: ' . implode( ', ', $values ) : 'None saved (empty list)' );
  }
  if ( $products && preg_match( '/^[1-9][0-9]{0,9}(?:\s*,\s*[1-9][0-9]{0,9})*$/D', $raw ) ) { return array( 'supported' => true, 'text' => 'Saved ID text: ' . $raw ); }
  return array( 'supported' => false, 'text' => 'Custom saved restriction: use full settings' );
 }
 private static function scope( $group, $c ) {
  $scope = self::raw( $c, self::key( $group, 'product_scope' ), 'all' );
  $labels = array( 'all' => 'Native sale scope: all products (other saved selectors still apply)', 'only_on_sale' => 'Native sale scope: only products treated as on sale by this module', 'only_not_on_sale' => 'Native sale scope: only products treated as not on sale by this module' );
  $lines = array( $labels[ $scope ] ?? 'Unknown saved sale scope: use full settings' );
  foreach ( array( 'products_incl' => 'Include products', 'products_excl' => 'Exclude products', 'categories_incl' => 'Include categories', 'categories_excl' => 'Exclude categories', 'tags_incl' => 'Include tags', 'tags_excl' => 'Exclude tags' ) as $field => $label ) { $v = self::selector( self::raw( $c, self::key( $group, $field ) ), 0 === strpos( $field, 'products_' ) ); $lines[] = $label . ': ' . $v['text']; }
  return $lines;
 }
 private static function status( $group, $c ) {
  $m = self::raw( $c, self::MODULE, 'no' ); $g = self::raw( $c, self::key( $group, 'enabled' ), 'yes' );
  return 'Global Discount module: ' . ( 'yes' === $m ? 'on' : ( 'no' === $m ? 'off' : 'unknown' ) ) . '. Group ' . $group . ': ' . ( 'yes' === $g ? 'enabled' : ( 'no' === $g ? 'disabled' : 'unknown' ) ) . '. This task never changes activation.';
 }
 private static function relevant_keys( $count, $roles ) {
  global $wpdb;
  $keys = array( self::MODULE, self::RECEIPT, 'wcj_global_discount_groups_total_number', 'wcj_global_discount_enabled_in_admin', 'wcj_global_discount_products_shortcode_compatibility', 'wcj_global_discount_advanced_price_hooks_priority', 'wcj_global_discount_wpml_get_terms_all_lang', 'wcj_global_discount_wpml_get_products_all_lang', 'wcj_list_for_products', 'wcj_modules_by_user_roles_enabled', $wpdb->prefix . 'user_roles', 'active_plugins' );
  for ( $i = 1; $i <= ( $count ?? 0 ); ++$i ) { foreach ( self::FIELDS as $field ) { $keys[] = self::key( (string) $i, $field ); } }
  foreach ( $roles as $role ) { $keys[] = 'wcj_modules_by_user_roles_incl_' . $role; $keys[] = 'wcj_modules_by_user_roles_excl_' . $role; }
  sort( $keys ); return array_values( array_unique( $keys ) );
 }
 /** Narrow pricing context, not every unrelated Woo option. Absences remain distinct. */
 private static function context( $group, $lock = false ) {
  global $wpdb; $user = wp_get_current_user();
  $base = self::relevant_keys( 0, $user->roles );
  $prefixes = array( 'wcj_global_discount_', 'wcj_wholesale_price_', 'wcj_price_by_user_role_', 'wcj_product_price_by_formula_', 'wcj_product_open_price_', 'wcj_call_for_price_', 'wcj_product_fees_', 'wcj_multicurrency_', 'wcj_price_by_country_', 'wcj_checkout_fees_' );
  $clauses = array(); $args = array(); foreach ( $prefixes as $prefix ) { $clauses[] = 'option_name LIKE %s'; $args[] = $wpdb->esc_like( $prefix ) . '%'; }
  $base = array_merge( $base, array( 'woocommerce_currency', 'woocommerce_price_num_decimals', 'woocommerce_prices_include_tax', 'woocommerce_tax_display_shop', 'woocommerce_tax_display_cart', 'woocommerce_calc_taxes' ) );
  $clauses[] = 'option_name IN (' . implode( ',', array_fill( 0, count( $base ), '%s' ) ) . ')'; $args = array_merge( $args, $base );
  $sql = $wpdb->prepare( "SELECT option_name,option_value,autoload FROM {$wpdb->options} WHERE " . implode( ' OR ', $clauses ) . ' ORDER BY option_name LIMIT 5001' . ( $lock ? ' FOR UPDATE' : '' ), $args );
  $rows = $wpdb->get_results( $sql, ARRAY_A );
  if ( $wpdb->last_error || ! is_array( $rows ) || count( $rows ) > 5000 ) { self::fail( 'Relevant pricing settings could not be safely read. Use full settings.', 409 ); }
  $out = array(); $bytes = 0; foreach ( $rows as $row ) { $bytes += strlen( $row['option_value'] ); $out[ $row['option_name'] ] = array( 'raw' => (string) $row['option_value'], 'autoload' => (string) $row['autoload'] ); }
  if ( $bytes > 2000000 ) { self::fail( 'The retained pricing context is too large for this task. Use full settings.' ); } ksort( $out );
  $role_rows = $wpdb->get_results( $wpdb->prepare( "SELECT umeta_id,meta_key,meta_value FROM {$wpdb->usermeta} WHERE user_id=%d AND meta_key IN (%s,%s) ORDER BY umeta_id" . ( $lock ? ' FOR UPDATE' : '' ), get_current_user_id(), $wpdb->prefix . 'capabilities', $wpdb->prefix . 'user_level' ), ARRAY_A );
  if ( $wpdb->last_error || ! is_array( $role_rows ) ) { self::fail( 'Current role storage could not be qualified.', 409 ); }
  $count = self::count_groups( $out ); $keys = self::relevant_keys( $count, $user->roles );
  return array( 'rows' => $out, 'count' => $count, 'keys' => $keys, 'hooks' => self::hooks( $group, $keys ), 'observers' => self::known_observers( $group ), 'paid' => self::paid_filter(), 'global_update' => self::global_update_filter( $group ), 'platform' => self::platform(), 'role_rows' => $role_rows, 'roles' => $user->roles, 'caps' => $user->allcaps, 'allowlist' => function_exists( 'wcj_get_enabled_module_allowlist' ) ? wcj_get_enabled_module_allowlist() : null, 'edition' => self::edition(), 'blog' => get_current_blog_id() );
 }
 /** Presence of unknown relevant observers refuses writes; none are probed or replayed. */
 private static function hooks( $group, $keys ) {
  $out = array(); $known = self::known_observers( $group );
  foreach ( $keys as $key ) { foreach ( array( '', 'option_', 'pre_option_', 'default_option_', 'pre_update_option_', 'sanitize_option_', 'woocommerce_admin_settings_sanitize_option_' ) as $prefix ) { if ( has_filter( $prefix . $key ) ) { $out[] = $prefix . $key; } } }
  foreach ( array( self::key( $group ), self::RECEIPT ) as $key ) { foreach ( array( 'update_option_', 'add_option_', 'delete_option_' ) as $prefix ) { if ( has_filter( $prefix . $key ) ) { $out[] = $prefix . $key; } } }
  foreach ( array( 'pre_option', 'pre_wp_load_alloptions', 'pre_cache_alloptions', 'alloptions', 'woocommerce_admin_settings_sanitize_option', 'woocommerce_update_option_number', 'update_option', 'updated_option', 'add_option', 'added_option', 'delete_option', 'deleted_option', 'sanitize_text_field' ) as $hook ) { if ( has_filter( $hook ) && ( ! isset( $known[ $hook ] ) || ! $known[ $hook ]['safe'] ) ) { $out[] = $hook; } }
  sort( $out ); return $out;
 }
 /** Exact known no-op observers. No callback, settings builder or lazy initializer is called. */
 private static function observer_specs() { return array(
array( 'hook' => 'added_option', 'priority' => 999, 'accepted_args' => 2, 'class' => 'Automattic\\WooCommerce\\Internal\\DataStores\\Orders\\DataSynchronizer', 'method' => 'process_added_option', 'file_sha256' => 'a431e293f882f6ed0517de243856bff419454888d65d1c0e5779ad7959abc594', 'body_sha256' => 'c4e3e8507654fbd1e9ef66f3be358443e8f60900b7d4cb2ed3826224bb097580', 'relative' => 'src/Internal/DataStores/Orders/DataSynchronizer.php', 'static' => false, 'parameters' => 2, 'declaring' => 'Automattic\\WooCommerce\\Internal\\DataStores\\Orders\\DataSynchronizer' ),
array( 'hook' => 'added_option', 'priority' => 999, 'accepted_args' => 3, 'class' => 'Automattic\\WooCommerce\\Internal\\Features\\FeaturesController', 'method' => 'process_added_option', 'file_sha256' => '667bd335a443c93e9746d8be05309b98877cc4498888fdb91fe93a13a8b30348', 'body_sha256' => 'dcbe8f8b65af325a59eb4bbad5f45239b9e2bd20a72a05e5f29c1f1c37cb4681', 'relative' => 'src/Internal/Features/FeaturesController.php', 'static' => false, 'parameters' => 2, 'declaring' => 'Automattic\\WooCommerce\\Internal\\Features\\FeaturesController' ),
array( 'hook' => 'delete_option', 'priority' => 10, 'accepted_args' => 1, 'class' => 'Automattic\\WooCommerce\\Internal\\Admin\\Schedulers\\OrdersScheduler', 'method' => 'handle_scheduled_import_option_before_delete', 'file_sha256' => '349ed8a1a046baba4d3419335b089dbb96115b7f7de0b80718d7ca734fb0b6e6', 'body_sha256' => 'f967929b70dc4e5af314cc83769a8281c64b409ca781fb9e89c19aa108353a19', 'relative' => 'src/Internal/Admin/Schedulers/OrdersScheduler.php', 'static' => true, 'parameters' => 1, 'declaring' => 'Automattic\\WooCommerce\\Internal\\Admin\\Schedulers\\OrdersScheduler' ),
array( 'hook' => 'deleted_option', 'priority' => 999, 'accepted_args' => 1, 'class' => 'Automattic\\WooCommerce\\Internal\\DataStores\\Orders\\DataSynchronizer', 'method' => 'process_deleted_option', 'file_sha256' => 'a431e293f882f6ed0517de243856bff419454888d65d1c0e5779ad7959abc594', 'body_sha256' => '7adc1cc398895b312649c78d625a7cbc18cb2c2da7ea0a095e3e4423681425dd', 'relative' => 'src/Internal/DataStores/Orders/DataSynchronizer.php', 'static' => false, 'parameters' => 1, 'declaring' => 'Automattic\\WooCommerce\\Internal\\DataStores\\Orders\\DataSynchronizer' ),
array( 'hook' => 'updated_option', 'priority' => 999, 'accepted_args' => 3, 'class' => 'Automattic\\WooCommerce\\Internal\\DataStores\\Orders\\CustomOrdersTableController', 'method' => 'process_updated_option', 'file_sha256' => 'b4d1a6772b064de9be6a80750074b0a9e371514f58131a1701cad6cd52ccb8bf', 'body_sha256' => 'a3bf19beace7a327b75aaea6f5da138ccf3b5fea29d3b752d9daca38734e761e', 'relative' => 'src/Internal/DataStores/Orders/CustomOrdersTableController.php', 'static' => false, 'parameters' => 3, 'declaring' => 'Automattic\\WooCommerce\\Internal\\DataStores\\Orders\\CustomOrdersTableController' ),
array( 'hook' => 'updated_option', 'priority' => 999, 'accepted_args' => 3, 'class' => 'Automattic\\WooCommerce\\Internal\\DataStores\\Orders\\CustomOrdersTableController', 'method' => 'process_updated_option_fts_index', 'file_sha256' => 'b4d1a6772b064de9be6a80750074b0a9e371514f58131a1701cad6cd52ccb8bf', 'body_sha256' => 'c8c1773ad069b3195c036ed8ff4c6fa6707fb92f27f1b324accf4677cb42585b', 'relative' => 'src/Internal/DataStores/Orders/CustomOrdersTableController.php', 'static' => false, 'parameters' => 3, 'declaring' => 'Automattic\\WooCommerce\\Internal\\DataStores\\Orders\\CustomOrdersTableController' ),
array( 'hook' => 'updated_option', 'priority' => 999, 'accepted_args' => 3, 'class' => 'Automattic\\WooCommerce\\Internal\\DataStores\\Orders\\DataSynchronizer', 'method' => 'process_updated_option', 'file_sha256' => 'a431e293f882f6ed0517de243856bff419454888d65d1c0e5779ad7959abc594', 'body_sha256' => 'db93170a25602ba9593d933c5d84c7fa047261521da4df584d55401afe631737', 'relative' => 'src/Internal/DataStores/Orders/DataSynchronizer.php', 'static' => false, 'parameters' => 3, 'declaring' => 'Automattic\\WooCommerce\\Internal\\DataStores\\Orders\\DataSynchronizer' ),
array( 'hook' => 'updated_option', 'priority' => 999, 'accepted_args' => 3, 'class' => 'Automattic\\WooCommerce\\Internal\\Features\\FeaturesController', 'method' => 'process_updated_option', 'file_sha256' => '667bd335a443c93e9746d8be05309b98877cc4498888fdb91fe93a13a8b30348', 'body_sha256' => 'e24cce741c42df41d9a0b1ba6919532101adfc7b6d6e6658210b804f0f7b4f74', 'relative' => 'src/Internal/Features/FeaturesController.php', 'static' => false, 'parameters' => 3, 'declaring' => 'Automattic\\WooCommerce\\Internal\\Features\\FeaturesController' ),
array( 'hook' => 'woocommerce_admin_settings_sanitize_option', 'priority' => 9223372036854775807, 'accepted_args' => 3, 'class' => 'WCJ_Admin', 'method' => 'unclean_custom_textarea', 'file_sha256' => array( 'free' => 'aa10daca2e5e97a3ce75a001f59a0883d2a90c7a0f7a6f6827a18c3d3da55bfa', 'plus' => 'c60626022c4b66367ddf9f39b41122b5aa5bca4692fb36484faaeea10d6cf8e6', 'elite' => '85d3bdcb59405e1c8b8342c5efeb3bef88305aedb30d9675de0edbff010297c6' ), 'body_sha256' => 'ed73b9570822d3aa5a04b131184c37c45d7e8ca84df9cfa09d7a2231c47f7772', 'relative' => 'native:includes/core/class-wcj-admin.php', 'static' => false, 'parameters' => 3, 'declaring' => 'WCJ_Admin' ),
array( 'hook' => 'woocommerce_admin_settings_sanitize_option', 'priority' => 9223372036854775807, 'accepted_args' => 3, 'class' => 'WCJ_Admin', 'method' => 'maybe_unclean_field', 'file_sha256' => array( 'free' => 'aa10daca2e5e97a3ce75a001f59a0883d2a90c7a0f7a6f6827a18c3d3da55bfa', 'plus' => 'c60626022c4b66367ddf9f39b41122b5aa5bca4692fb36484faaeea10d6cf8e6', 'elite' => '85d3bdcb59405e1c8b8342c5efeb3bef88305aedb30d9675de0edbff010297c6' ), 'body_sha256' => '761ec28ed524a23788bcbdfb60d319072f090ef5230dd0121d9680bf41829b5b', 'relative' => 'native:includes/core/class-wcj-admin.php', 'static' => false, 'parameters' => 3, 'declaring' => 'WCJ_Admin' ),
array( 'hook' => 'wcj_global_discount_settings', 'priority' => 10, 'accepted_args' => 1, 'class' => 'WCJ_Global_Discount', 'declaring' => 'WCJ_Module', 'method' => 'add_settings_from_file', 'relative' => 'native:includes/classes/class-wcj-module.php', 'file_sha256' => array( 'free' => '31bc1cec496acd7453abf585435c4eacff9362ad7978097b1df60ffe0f834232', 'plus' => '2c306011e2d7c8984231f03c2e9e2795640a18fda1239a42fd5f89e2c34a6022', 'elite' => '290d825a26026a725dd43130bd28fa53222dd9cba24a856bf36d91652f1444d6' ), 'body_sha256' => array( 'free' => '23a89666372bf336121b647d306f810a560ef985c7ddeb9426e9b9e4dd3bbf75', 'plus' => 'e07bca7bb3f7f733d9f8baef023310e979a578e58dd1b2aadb6282ac67814937', 'elite' => 'e07bca7bb3f7f733d9f8baef023310e979a578e58dd1b2aadb6282ac67814937' ), 'static' => false, 'parameters' => 1 ),
array( 'hook' => 'wcj_settings_global_discount', 'priority' => 100, 'accepted_args' => 1, 'class' => 'WCJ_Global_Discount', 'declaring' => 'WCJ_Module', 'method' => 'get_settings', 'relative' => 'native:includes/classes/class-wcj-module.php', 'file_sha256' => array( 'free' => '31bc1cec496acd7453abf585435c4eacff9362ad7978097b1df60ffe0f834232', 'plus' => '2c306011e2d7c8984231f03c2e9e2795640a18fda1239a42fd5f89e2c34a6022', 'elite' => '290d825a26026a725dd43130bd28fa53222dd9cba24a856bf36d91652f1444d6' ), 'body_sha256' => array( 'free' => 'ffd59e72735f9426b74de8cdc14c1bdbeca8f710439376ca7e9e65de2e88a2dd', 'plus' => 'ffd59e72735f9426b74de8cdc14c1bdbeca8f710439376ca7e9e65de2e88a2dd', 'elite' => '22821a332f06e2ab607f2b31f14084cc0510f9bff5d5bae7dfdcd6b278eff933' ), 'static' => false, 'parameters' => 0 )
 ); }
 private static function observer_identity( $entry, $priority, $spec ) {
  $f = $entry['function'] ?? null;
  if ( $spec['priority'] !== (int) $priority || $spec['accepted_args'] !== ( $entry['accepted_args'] ?? null ) || ! is_array( $f ) || array_keys( $f ) !== array( 0, 1 ) || $spec['method'] !== $f[1] ) { return false; }
  if ( $spec['static'] ? ( ! is_string( $f[0] ) || $spec['class'] !== $f[0] || ! class_exists( $f[0], false ) ) : ( ! is_object( $f[0] ) || $spec['class'] !== get_class( $f[0] ) ) ) { return false; }
  $native = 0 === strpos( $spec['relative'], 'native:' );
  if ( ! $native && ! defined( 'WC_ABSPATH' ) ) { return false; }
  $file = realpath( $native ? dirname( __DIR__, 2 ) . '/' . substr( $spec['relative'], 7 ) : WC_ABSPATH . $spec['relative'] );
  $sha = is_array( $spec['file_sha256'] ) ? $spec['file_sha256'][ self::edition() ] : $spec['file_sha256'];
  if ( ! $file || ! is_readable( $file ) || hash_file( 'sha256', $file ) !== $sha ) { return false; }
  $m = new ReflectionMethod( $f[0], $f[1] );
  if ( realpath( $m->getFileName() ) !== $file || $m->getDeclaringClass()->getName() !== $spec['declaring'] || ! $m->isPublic() || $m->isStatic() !== $spec['static'] || $m->getNumberOfParameters() !== $spec['parameters'] ) { return false; }
  $body_sha = is_array( $spec['body_sha256'] ) ? $spec['body_sha256'][ self::edition() ] : $spec['body_sha256'];
  $lines = file( $file, FILE_IGNORE_NEW_LINES );
  if ( ! is_array( $lines ) || hash( 'sha256', implode( "\n", array_slice( $lines, $m->getStartLine() - 1, $m->getEndLine() - $m->getStartLine() + 1 ) ) ) !== $body_sha ) { return false; }
  return array( $spec['class'], $spec['method'], $spec['priority'], $spec['accepted_args'], $spec['relative'], $sha, $body_sha );
 }
 /** Constants are checked as loaded, not assumed from a class-name match. */
 private static function observer_constants( $class, $values, $write_keys ) {
  if ( ! class_exists( $class, false ) ) { return false; }
  $out = array();
  foreach ( $values as $name => $expected ) {
   if ( ! defined( $class . '::' . $name ) ) { return false; }
   $c = new ReflectionClassConstant( $class, $name ); $actual = $c->getValue();
   if ( $c->getDeclaringClass()->getName() !== $class || $actual !== $expected || in_array( $actual, $write_keys, true ) ) { return false; }
   $out[ $name ] = $actual;
  }
  return $out;
 }
 /** Read only the already-initialized fields used before the observer's early return. */
 private static function feature_observer_context( $object, $write_keys ) {
  $class = 'Automattic\WooCommerce\Internal\Features\FeaturesController';
  if ( ! is_object( $object ) || get_class( $object ) !== $class || has_filter( 'woocommerce_register_feature_definitions' ) ) { return false; }
  $state = array();
  foreach ( array( 'features', 'registered_additional_features_via_class_calls', 'registered_additional_features_via_action' ) as $name ) {
   $p = new ReflectionProperty( $class, $name );
   if ( $p->getDeclaringClass()->getName() !== $class || ! $p->isPrivate() || ! $p->isInitialized( $object ) ) { return false; }
   $state[ $name ] = $p->getValue( $object );
  }
  if ( true !== $state['registered_additional_features_via_class_calls'] || true !== $state['registered_additional_features_via_action'] || ! is_array( $state['features'] ) || ! $state['features'] || count( $state['features'] ) > 256 ) { return false; }
  $keys = array();
  foreach ( $write_keys as $key ) { if ( preg_match( '/^woocommerce_feature_([a-zA-Z0-9_]+)_enabled$/D', $key ) ) { return false; } }
  foreach ( $state['features'] as $id => $feature ) {
   if ( ! is_string( $id ) || ! preg_match( '/^[a-zA-Z0-9_-]{1,100}$/D', $id ) || ! is_array( $feature ) ) { return false; }
   $present = array_key_exists( 'option_key', $feature ); $key = $present ? $feature['option_key'] : null;
   if ( null !== $key && ( ! is_string( $key ) || strlen( $key ) > 191 || ( '' !== $key && ! preg_match( '/^[a-zA-Z0-9_-]+$/D', $key ) ) ) ) { return false; }
   if ( in_array( $key, $write_keys, true ) ) { return false; }
   $keys[ $id ] = array( 'present' => $present, 'option_key' => $key );
  }
  ksort( $keys );
  // Only this projection influences the pinned callback before it returns for our two keys.
  return array( 'class_calls_initialized' => true, 'action_initialized' => true, 'registration_hook' => 'absent', 'keys' => $keys );
 }
 private static function observer_hook( $hook, $group ) {
  global $wp_filter;
  $unknown = array( 'safe' => false, 'binding' => 'unqualified' );
  if ( ! has_filter( $hook ) ) { return array( 'safe' => true, 'binding' => 'absent' ); }
  if ( ! isset( $wp_filter[ $hook ] ) || ! $wp_filter[ $hook ] instanceof WP_Hook || ! is_array( $wp_filter[ $hook ]->callbacks ) ) { return $unknown; }
  $write_keys = array( self::key( $group ), self::RECEIPT ); $bindings = array(); $seen = array();
  try {
   foreach ( $wp_filter[ $hook ]->callbacks as $priority => $entries ) {
    if ( ! is_array( $entries ) ) { return $unknown; }
    foreach ( $entries as $entry ) {
     if ( ! is_array( $entry ) ) { return $unknown; } $matched = false;
     foreach ( self::observer_specs() as $spec ) {
      if ( $spec['hook'] !== $hook ) { continue; } $identity = self::observer_identity( $entry, $priority, $spec );
      if ( false === $identity ) { continue; }
      $id = $spec['class'] . '::' . $spec['method']; if ( isset( $seen[ $id ] ) ) { return $unknown; } $seen[ $id ] = true;
      $extra = array(); $class = $spec['class']; $method = $spec['method'];
      if ( 'Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer' === $class ) {
       $extra = self::observer_constants( $class, array( 'ORDERS_DATA_SYNC_ENABLED_OPTION' => 'woocommerce_custom_orders_table_data_sync_enabled', 'BACKGROUND_SYNC_MODE_OPTION' => 'woocommerce_custom_orders_table_background_sync_mode' ), $write_keys );
      } elseif ( 'Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController' === $class ) {
       $extra = self::observer_constants( $class, array( 'HPOS_DATASTORE_CACHING_ENABLED_OPTION' => 'woocommerce_hpos_datastore_caching_enabled', 'HPOS_FTS_INDEX_OPTION' => 'woocommerce_hpos_fts_index_enabled' ), $write_keys );
       $sync = self::observer_constants( 'Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer', array( 'ORDERS_DATA_SYNC_ENABLED_OPTION' => 'woocommerce_custom_orders_table_data_sync_enabled' ), $write_keys );
       if ( false === $sync || false === $extra ) { return $unknown; } $extra['sync'] = $sync;
      } elseif ( 'Automattic\WooCommerce\Internal\Admin\Schedulers\OrdersScheduler' === $class ) {
       $extra = self::observer_constants( $class, array( 'SCHEDULED_IMPORT_OPTION' => 'woocommerce_analytics_scheduled_import' ), $write_keys );
      } elseif ( 'Automattic\WooCommerce\Internal\Features\FeaturesController' === $class ) {
       $extra = self::feature_observer_context( $entry['function'][0], $write_keys );
      } elseif ( 'WCJ_Admin' === $class ) {
       // Pinned native field is type number with no wcj_raw; strict ASCII draft survives wc_clean unchanged.
       // The signed receipt is not a Woo settings field and never enters this sanitizer.
       $inner = self::observer_hook( 'wcj_global_discount_settings', $group ); $outer = self::observer_hook( 'wcj_settings_global_discount', $group );
       if ( ! $inner['safe'] || 'absent' === $inner['binding'] || ! $outer['safe'] || 'absent' === $outer['binding'] ) { return $unknown; }
       $extra = array( 'field' => array( 'id' => $write_keys[0], 'type' => 'number', 'wcj_raw' => 'absent' ), 'receipt' => 'not-a-Woo-field', 'inner' => $inner['binding'], 'outer' => $outer['binding'] );
      } elseif ( 'WCJ_Global_Discount' === $class ) {
       $p = new ReflectionProperty( $class, 'id' );
       if ( ! $p->isInitialized( $entry['function'][0] ) || self::ID !== $p->getValue( $entry['function'][0] ) ) { return $unknown; }
       $extra = array( 'module_id' => self::ID );
      } else { return $unknown; }
      if ( false === $extra ) { return $unknown; }
      $bindings[] = array( 'identity' => $identity, 'context' => $extra, 'write_keys' => $write_keys ); $matched = true; break;
     }
     if ( ! $matched ) { return $unknown; }
    }
   }
  } catch ( Throwable $e ) { return $unknown; }
  return $bindings ? array( 'safe' => true, 'binding' => $bindings ) : $unknown;
 }
 private static function known_observers( $group ) {
  $out = array(); foreach ( array( 'added_option', 'delete_option', 'deleted_option', 'updated_option', 'woocommerce_admin_settings_sanitize_option' ) as $hook ) { $out[ $hook ] = self::observer_hook( $hook, $group ); } return $out;
 }

 private static function paid_filter() {
  global $wp_filter;
  $unknown = array( 'safe' => false, 'binding' => 'unqualified' );
  if ( 'free' === self::edition() ) { return has_filter( 'booster_option' ) ? $unknown : array( 'safe' => true, 'binding' => 'free-defaults' ); }
  if ( ! isset( $wp_filter['booster_option'] ) || ! $wp_filter['booster_option'] instanceof WP_Hook ) { return $unknown; }
  $bindings = array();
  foreach ( $wp_filter['booster_option']->callbacks as $priority => $entries ) {
   foreach ( $entries as $entry ) {
    $f = $entry['function'] ?? null;
    if ( 101 !== (int) $priority || 2 !== ( $entry['accepted_args'] ?? null ) || ! is_array( $f ) || count( $f ) !== 2 || ! is_object( $f[0] ) || 'WCJ_Plus_Filters' !== get_class( $f[0] ) || 'booster_get_option' !== $f[1] ) { return $unknown; }
    $m = new ReflectionMethod( $f[0], $f[1] ); $file = realpath( dirname( __DIR__ ) . '/plus/class-wcj-plus-filters.php' );
    if ( ! $file || realpath( $m->getFileName() ) !== $file || ! $m->isPublic() || $m->isStatic() || $m->getNumberOfParameters() !== 2 || $m->getDeclaringClass()->getName() !== 'WCJ_Plus_Filters' ) { return $unknown; }
    $lines = file( $file, FILE_IGNORE_NEW_LINES ); $hash = hash( 'sha256', implode( "\n", array_slice( $lines, $m->getStartLine() - 1, $m->getEndLine() - $m->getStartLine() + 1 ) ) );
    if ( '544741187c4efed7ceb7bac060003631d6f2cc45a202a02d240c540404becc9f' !== $hash ) { return $unknown; }
    $bindings[] = array( $hash, 101, 2 );
   }
  }
  return count( $bindings ) === 1 ? array( 'safe' => true, 'binding' => $bindings ) : $unknown;
 }
	private static function global_update_filter( $g ) {
		global $wp_filter;
		if ( ! has_filter( 'pre_update_option' ) ) { return array( 'safe' => true, 'binding' => 'absent' ); }
		$unknown = array( 'safe' => false, 'binding' => 'unqualified' );
		if ( ! isset( $wp_filter['pre_update_option'] ) || ! $wp_filter['pre_update_option'] instanceof WP_Hook || ! is_array( $wp_filter['pre_update_option']->callbacks ) || ! defined( 'WC_ABSPATH' ) ) { return $unknown; }
		$class = 'Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController';
		$sync = 'Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer';
		$bindings = array();
		try {
			foreach ( $wp_filter['pre_update_option']->callbacks as $priority => $callbacks ) {
				foreach ( $callbacks as $entry ) {
					$f = $entry['function'] ?? null;
					if ( 999 !== (int) $priority || 3 !== ( $entry['accepted_args'] ?? null ) || ! is_array( $f ) || 2 !== count( $f ) || ! isset( $f[0], $f[1] ) || ! is_object( $f[0] ) || get_class( $f[0] ) !== $class || 'process_pre_update_option' !== $f[1] || ! class_exists( $sync, false ) ) { return $unknown; }
					$method = new ReflectionMethod( $f[0], $f[1] );
					$file = realpath( WC_ABSPATH . 'src/Internal/DataStores/Orders/CustomOrdersTableController.php' );
					if ( false === $file || realpath( $method->getFileName() ) !== $file || $method->getDeclaringClass()->getName() !== $class || ! $method->isPublic() || $method->isStatic() || 3 !== $method->getNumberOfParameters() || ! is_readable( $file ) ) { return $unknown; }
					$lines = file( $file, FILE_IGNORE_NEW_LINES );
					if ( ! is_array( $lines ) ) { return $unknown; }
					$body = implode( "\n", array_slice( $lines, $method->getStartLine() - 1, $method->getEndLine() - $method->getStartLine() + 1 ) );
					$fingerprint = hash( 'sha256', $body );
					if ( '45c08141fc7a2f7977c41f668cd71923bff54bce974f4793c71fe67c4ea4235a' !== $fingerprint || ! defined( $class . '::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION' ) || ! defined( $sync . '::ORDERS_DATA_SYNC_ENABLED_OPTION' ) ) { return $unknown; }
					$usage = constant( $class . '::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION' ); $sync_option = constant( $sync . '::ORDERS_DATA_SYNC_ENABLED_OPTION' );
					if ( 'woocommerce_custom_orders_table_enabled' !== $usage || 'woocommerce_custom_orders_table_data_sync_enabled' !== $sync_option || array_intersect( array( $usage, $sync_option ), array( '' === $g ? '' : self::key( $g ), self::RECEIPT ) ) ) { return $unknown; }
					$bindings[] = array( $class, $f[1], 999, 3, $fingerprint, $usage, $sync_option );
				}
			}
		} catch ( Throwable $e ) { return $unknown; }
		return $bindings ? array( 'safe' => true, 'binding' => hash( 'sha256', serialize( $bindings ) ) ) : $unknown;
	}
 private static function platform() {
  if ( ! defined( 'WC_ABSPATH' ) || PHP_INT_SIZE < 8 ) { return array( 'safe' => false ); }
  $files = array( 'includes/data-stores/class-wc-product-variable-data-store-cpt.php' => '528134ffa07060865239ad325f007703414c85e4976f31023262253bfbcd23f7', 'includes/wc-formatting-functions.php' => 'a4188d2d9b6786eebed14e4a3e07fc37a2c2eec48cfb6a32199a2605c3415361', 'includes/class-wc-cache-helper.php' => 'cd825e2f2f80bde5b57fd7ede5e8fe0770874e8605c583d2bbd8fbcc33af40b7', 'wp:wp-includes/formatting.php' => '350f6b32303915df970bb0e3ea360fcd54bb0b8a7ecc3d504c3d860ab9468d32', 'wp:wp-includes/option.php' => 'fe3f0b7e115b2a50c31162ed6428abe4088c080b0f71b9678d84806168fe90d0', 'includes/admin/class-wc-admin-settings.php' => '34c63bd66fa729ed1e09bca21a15061183bb12aef4455d0514817399b606eb3d', 'src/Internal/DataStores/Orders/CustomOrdersTableController.php' => 'b4d1a6772b064de9be6a80750074b0a9e371514f58131a1701cad6cd52ccb8bf', 'src/Internal/DataStores/Orders/DataSynchronizer.php' => 'a431e293f882f6ed0517de243856bff419454888d65d1c0e5779ad7959abc594' ); $bindings = array();
  foreach ( $files as $rel => $sha ) { $p = 0 === strpos( $rel, 'wp:' ) ? ABSPATH . substr( $rel, 3 ) : WC_ABSPATH . $rel; if ( ! is_readable( $p ) || hash_file( 'sha256', $p ) !== $sha ) { return array( 'safe' => false ); } $bindings[ $rel ] = $sha; }
  $native = array( 'free' => array( 'includes/class-wcj-global-discount.php' => '7980f02be2da693adccc61ca673df817c107d754141cbab38e8078e7f9e0377b', 'includes/settings/wcj-settings-global-discount.php' => '74b1433b3d7b2e210adc9a3ed0fb2a7d9a3ca7fbb6a2f5bc3d41f1b8dd3cc38a', 'includes/functions/wcj-functions-core.php' => 'b09c635f440ed758a18d7e48960904dbcc85f6c12dfd2672bebdb8e9a2344e0f', 'includes/functions/wcj-functions-price-currency.php' => 'aa29854dad5563d6cde69dd98b0109b0caa081e8b1fb82db438fd5b9c4279e2b', 'includes/functions/wcj-functions-products.php' => '303a5278481602b3137469d16b321ccaee8c4d4817553582571cddff3965aa70', 'includes/classes/class-wcj-module.php' => '31bc1cec496acd7453abf585435c4eacff9362ad7978097b1df60ffe0f834232', 'includes/functions/wcj-functions-booster-core.php' => '407642a150cd219f8d7d17a12a0c2b169cfad1f08fea268d9cbc2f4f2c168199' ), 'plus' => array( 'includes/class-wcj-global-discount.php' => 'cd42dcab776118287c761fd2fa8c6866685e6cfc87a22ddfe772a7c01d52811c', 'includes/settings/wcj-settings-global-discount.php' => '61cbdef74ed186837ee36c22d249a014097c68543bd6e0cf8df1cbf92a3fef00', 'includes/functions/wcj-functions-core.php' => '269204584ef29993971f9ca02fca42e896d12564dae847cece4c1b27f3420ed1', 'includes/functions/wcj-functions-price-currency.php' => '02f8d12c33829b6e69a8b21882f843f404650b7991991b5123affa602603454e', 'includes/functions/wcj-functions-products.php' => '18b66576b38289660027e1e4d5f153619e6642dd6188f8881af36a1fe72471c3', 'includes/classes/class-wcj-module.php' => '2c306011e2d7c8984231f03c2e9e2795640a18fda1239a42fd5f89e2c34a6022', 'includes/functions/wcj-functions-booster-core.php' => '213d6298553459656c3343b44e1d10bdca5ed2883e584b485daa2fcd27b1d37c' ), 'elite' => array( 'includes/class-wcj-global-discount.php' => 'c9a4c6ab5a61e868d27df8e8c6a3e134f1a28c754ada0098e5f9f976f7a22f90', 'includes/settings/wcj-settings-global-discount.php' => '212ebcb8aee08a704e317434556ae8d0ac84d7f5e73b7c214b266ad08a782f93', 'includes/functions/wcj-functions-core.php' => '097f31d34847837cc8a2d9336c515c021de84b247f419eee84b7a041e1f73f99', 'includes/functions/wcj-functions-price-currency.php' => '8e5725ff3c9dd1f37dc7ef2f09347a23cdda68377e296f0746a773db46c0b40e', 'includes/functions/wcj-functions-products.php' => '625f09533619e157ef7d228ab40ca521fbcc46175b928b4398009ff3e09f8bf3', 'includes/classes/class-wcj-module.php' => '290d825a26026a725dd43130bd28fa53222dd9cba24a856bf36d91652f1444d6', 'includes/functions/wcj-functions-booster-core.php' => '8c334071eae93a8f18b406e3e999d9586a50e12bddee0cb37e97077f9613fa6a' ) );
  foreach ( $native[ self::edition() ] as $rel => $sha ) { $p = dirname( __DIR__, 2 ) . '/' . $rel; if ( ! is_readable( $p ) || hash_file( 'sha256', $p ) !== $sha ) { return array( 'safe' => false ); } $bindings[ $rel ] = $sha; }
  return array( 'safe' => true, 'binding' => $bindings );
 }
 private static function storage_reason() {
  global $wpdb;
  if ( PHP_INT_SIZE < 8 || wp_using_ext_object_cache() ) { return 'This task needs 64-bit arithmetic and nonpersistent option caches. Use full settings.'; }
  if ( is_multisite() ) { return 'Network-wide plugin and role context is outside this shortcut. Use full settings.'; }
  $schema = array( $wpdb->options => array( 'option_id' => 'bigint(20) unsigned', 'option_name' => 'varchar(191)', 'option_value' => 'longtext', 'autoload' => 'varchar(20)' ), $wpdb->usermeta => array( 'umeta_id' => 'bigint(20) unsigned', 'user_id' => 'bigint(20) unsigned', 'meta_key' => 'varchar(255)', 'meta_value' => 'longtext' ) );
  foreach ( $schema as $table => $expected ) {
   $status = $wpdb->get_row( $wpdb->prepare( 'SHOW TABLE STATUS WHERE Name=%s', $table ), ARRAY_A );
   if ( $wpdb->last_error || ! $status || 'InnoDB' !== $status['Engine'] ) { return 'Transactional option and role storage could not be qualified. Use full settings.'; }
   $columns = $wpdb->get_results( "SHOW FULL COLUMNS FROM {$table}", ARRAY_A ); $actual = array();
   if ( $wpdb->last_error || ! is_array( $columns ) ) { return 'The native storage schema could not be qualified. Use full settings.'; }
   foreach ( $columns as $col ) { $actual[ $col['Field'] ] = strtolower( $col['Type'] ); if ( $wpdb->options === $table && ( 'NO' !== $col['Null'] || ( in_array( $col['Field'], array( 'option_name', 'option_value', 'autoload' ), true ) && 'utf8mb4_unicode_520_ci' !== $col['Collation'] ) ) ) { return 'Custom native option storage needs full settings.'; } }
   if ( $actual !== $expected ) { return 'This storage schema differs from the qualified native schema. Use full settings.'; }
   foreach ( $columns as $col ) {
    $id = in_array( $col['Field'], array( 'option_id', 'umeta_id' ), true );
    $text = in_array( $col['Field'], array( 'option_name', 'option_value', 'autoload', 'meta_key', 'meta_value' ), true );
    $nullable = in_array( $col['Field'], array( 'meta_key', 'meta_value' ), true );
    $default = 'user_id' === $col['Field'] ? '0' : ( 'option_name' === $col['Field'] ? '' : ( 'autoload' === $col['Field'] ? 'yes' : null ) );
    if ( ( $nullable ? 'YES' : 'NO' ) !== $col['Null'] || ( $id ? 'auto_increment' : '' ) !== $col['Extra'] || $col['Default'] !== $default || ( $text && 'utf8mb4_unicode_520_ci' !== $col['Collation'] ) ) { return 'Custom native storage definitions need full settings.'; }
   }
   $triggers = $wpdb->get_results( $wpdb->prepare( 'SHOW TRIGGERS WHERE `Table`=%s', $table ), ARRAY_A );
   if ( $wpdb->last_error || ! is_array( $triggers ) || $triggers ) { return 'Storage triggers could not be safely excluded. Use full settings.'; }

   $rows = $wpdb->get_results( "SHOW INDEX FROM {$table}", ARRAY_A ); $parts = array();
   if ( $wpdb->last_error || ! is_array( $rows ) ) { return 'Storage indexes could not be verified.'; }
   foreach ( $rows as $row ) { $parts[ $row['Key_name'] ][] = $row; }
   $required = $wpdb->options === $table ? array( 'PRIMARY' => array( 'option_id', 0 ), 'option_name' => array( 'option_name', 0 ) ) : array( 'PRIMARY' => array( 'umeta_id', 0 ), 'user_id' => array( 'user_id', 1 ) );
   foreach ( $required as $name => $want ) { $r = $parts[ $name ] ?? array(); if ( 1 !== count( $r ) || $want[0] !== $r[0]['Column_name'] || $want[1] !== (int) $r[0]['Non_unique'] || 1 !== (int) $r[0]['Seq_in_index'] || ! empty( $r[0]['Sub_part'] ) ) { return 'Required native exact-key indexes are missing or custom. Use full settings.'; } }
  }
  return '';
 }
 private static function base_reason( $c ) {
  if ( ! $c['platform']['safe'] || ! $c['paid']['safe'] || ! $c['global_update']['safe'] || $c['hooks'] ) { return 'A relevant settings integration or installed source could not be safely qualified. Use full settings. No callback was invoked or removed to probe it.'; }
  if ( empty( $c['roles'] ) || empty( $c['role_rows'] ) ) { return 'Current native role storage could not be qualified. Use full settings.'; }
  if ( null === $c['count'] ) { return 'The saved group count is custom or exceeds this task limit of 100. Use full settings; its count is unchanged.'; }
  $core = WCJ_Job_Store::core(); if ( ! $core || ! isset( $core->all_modules[ self::ID ] ) ) { return 'The native Global Discount module is unavailable. Use full settings.'; }
  if ( 'yes' !== self::raw( $c, self::MODULE, 'no' ) ) { return 'Global Discount is off or its state is unknown. Review activation in full settings. This task never enables it.'; }
  if ( 'no' !== self::raw( $c, 'wcj_modules_by_user_roles_enabled', 'no' ) ) { return 'Dynamic module-by-role configuration needs full settings; this task does not change activation or access.'; }
  foreach ( array( 'wcj_global_discount_enabled_in_admin', 'wcj_global_discount_products_shortcode_compatibility', 'wcj_global_discount_wpml_get_terms_all_lang', 'wcj_global_discount_wpml_get_products_all_lang' ) as $key ) { if ( ! in_array( self::raw( $c, $key, 'no' ), array( 'yes', 'no' ), true ) ) { return 'A native pricing mode is custom. Use full settings without simplifying it.'; } }
  foreach ( $c['rows'] as $state ) { if ( ! in_array( $state['autoload'], array( 'yes', 'no', 'on', 'off', 'auto', 'auto-on', 'auto-off' ), true ) ) { return 'A stored autoload value is custom. Use full settings.'; } }
  for ( $i = 1; $i <= $c['count']; ++$i ) {
   $g = (string) $i;
   if ( ! in_array( self::raw( $c, self::key( $g, 'enabled' ), 'yes' ), array( 'yes', 'no' ), true ) || ! in_array( self::raw( $c, self::key( $g, 'product_scope' ), 'all' ), array( 'all', 'only_on_sale', 'only_not_on_sale' ), true ) ) { return 'A saved group has an unknown activation or sale scope. Use full settings.'; }
   foreach ( array( 'products_incl', 'products_excl', 'categories_incl', 'categories_excl', 'tags_incl', 'tags_excl' ) as $field ) { if ( ! self::selector( self::raw( $c, self::key( $g, $field ) ), 0 === strpos( $field, 'products_' ) )['supported'] ) { return 'A saved group has custom restriction data. It is preserved; use full settings.'; } }
  }
  if ( null !== self::raw( $c, self::RECEIPT ) && ! self::record( $c ) ) { return 'The previous task receipt cannot be verified. Use full settings; it has not been overwritten.'; }
  return self::storage_reason();
 }
 private static function group_reason( $g, $c ) {
  if ( (int) $g > ( $c['count'] ?? 0 ) ) { return 'This group is outside the current native group count.'; }
  if ( 'elite' !== self::edition() && '1' !== $g ) { return 'This new Free/Plus shortcut edits group 1 only. Plus keeps all its existing groups in full settings.'; }
  if ( 'yes' !== self::raw( $c, self::key( $g, 'enabled' ), 'yes' ) ) { return 'This group is disabled or unknown. Use its native Enable setting; the task never enables it.'; }
  if ( null === self::negative( self::raw( $c, self::key( $g ) ) ) ) { return 'This group needs an existing negative percentage strictly between -100 and 0 with at most four decimals. Blank, absent, zero or other values stay unchanged in full settings.'; }
  if ( 'percent' !== self::raw( $c, self::key( $g, 'coefficient_type' ), 'percent' ) ) { return 'This group is not a supported percentage rule. Use full settings.'; }
  if ( 'none' !== self::raw( $c, self::key( $g, 'final_correction_func' ), 'none' ) ) { return 'This group uses a correction function. It is preserved and never evaluated here. Use full settings.'; }
  return '';
 }
 private static function refusal( $g, $c ) { return self::base_reason( $c ) ?: self::group_reason( $g, $c ); }
 private static function record( $c ) {
  $raw = self::raw( $c, self::RECEIPT );
  $r = is_string( $raw ) && strlen( $raw ) < 16000 && is_serialized( $raw ) ? @unserialize( $raw, array( 'allowed_classes' => false ) ) : null;
  if ( ! is_array( $r ) || serialize( $r ) !== $raw || ! is_string( $r['signature'] ?? null ) ) { return null; }
  $sig = $r['signature']; unset( $r['signature'] );
  return hash_equals( hash_hmac( 'sha256', serialize( $r ), wp_salt( 'nonce' ) ), $sig ) ? $r : null;
 }
 private static function valid_state( $s ) { return is_array( $s ) && count( $s ) === 2 && null !== self::negative( $s['raw'] ?? null ) && in_array( $s['autoload'] ?? null, array( 'yes', 'no', 'on', 'off', 'auto', 'auto-on', 'auto-off' ), true ); }
 private static function own_record( $g, $r ) { return is_array( $r ) && 1 === ( $r['version'] ?? null ) && get_current_blog_id() === ( $r['blog'] ?? null ) && get_current_user_id() === ( $r['user'] ?? null ) && self::edition() === ( $r['edition'] ?? null ) && $g === ( $r['group'] ?? null ) && self::valid_state( $r['before'] ?? null ) && self::valid_state( $r['after'] ?? null ) && is_string( $r['after_guard'] ?? null ) && is_string( $r['summary'] ?? null ) && in_array( $r['status'] ?? null, array( 'applied', 'undone' ), true ); }
 private static function target( $g, $c, $percent, $undo ) {
  $reason = self::refusal( $g, $c ); if ( $reason ) { self::fail( $reason ); }
  $before = self::state( $c, self::key( $g ) );
  if ( ! self::valid_state( $before ) ) { self::fail( 'This existing raw value is unsupported. Use full settings.' ); }
  if ( $undo ) {
   $r = self::record( $c );
   if ( ! self::own_record( $g, $r ) || 'applied' !== $r['status'] || $before !== $r['after'] || ! hash_equals( self::hash( $g, $c, false ), $r['after_guard'] ) ) { self::fail( 'Restore no longer matches your saved change and current context. It will not overwrite newer settings. Discard and load current values.', 409 ); }
   if ( '' !== $percent ) { self::fail( 'Restore must use the exact signed previous value, not a submitted discount.' ); }
   return $r['before'];
  }
  if ( null === self::decimal( $percent ) ) { self::fail( 'Enter a positive percentage below 100 with at most four decimals, without signs, spaces, commas or percent characters. Blank or 0 does not turn this off.' ); }
  if ( self::negative( $before['raw'] ) === self::decimal( $percent ) ) { self::fail( 'The percentage is unchanged. Nothing is saved or normalized.' ); }
  return array( 'raw' => '-' . $percent, 'autoload' => $before['autoload'] );
 }
 private static function consequence( $g, $c, $after, $example ) {
  $percent = substr( $after['raw'], 1 ); $result = self::arithmetic( $example, $percent );
  return 'If group ' . $g . ' is the first applicable group and its incoming price is your supplied example ' . $example . ', this group alone gives ' . $result . ' with a ' . $percent . '% discount (stored as ' . $after['raw'] . '). Unrounded arithmetic only, not a product or checkout quote. Tax, coupons, fees, currency conversion and other price rules can change final charges. Scope, order, correction and activation stay unchanged. ' . self::status( $g, $c );
 }
 private static function signature( $g, $guard, $before, $after, $example, $undo, $expires ) { return hash_hmac( 'sha256', serialize( array( get_current_blog_id(), get_current_user_id(), self::edition(), $g, $guard, $before, $after, $example, self::arithmetic( $example, substr( $after['raw'], 1 ) ), $undo, $expires ) ), wp_salt( 'nonce' ) ); }
 public static function review( $g, $guard, $percent, $example, $undo = false ) {
  self::authorize(); self::key( $g ); $c = self::context( $g );
  if ( ! hash_equals( self::hash( $g, $c ), $guard ) ) { self::fail( 'Settings changed. Your draft is parked. Discard and load current values.', 409 ); }
  if ( null === self::decimal( $example, true ) ) { self::fail( 'Use a positive example amount up to 9999.9999 with at most four decimals.' ); }
  $after = self::target( $g, $c, $percent, $undo ); $before = self::state( $c, self::key( $g ) ); $expires = time() + 300;
  return array( 'operation' => $undo ? 'undo' : 'save', 'group' => $g, 'before' => $before['raw'], 'after' => $after['raw'], 'example' => $example, 'result' => self::arithmetic( $example, substr( $after['raw'], 1 ) ), 'scope' => self::scope( $g, $c ), 'summary' => self::consequence( $g, $c, $after, $example ), 'expires' => $expires, 'token' => self::signature( $g, $guard, $before, $after, $example, $undo, $expires ) );
 }
 public static function load( $g = '' ) {
  self::authorize(); if ( '' !== $g ) { self::key( $g ); }
  $c = self::context( '' === $g ? '1' : $g );
  if ( '' === $g ) { $g = '1'; for ( $i = 1; $i <= ( $c['count'] ?? 0 ); ++$i ) { if ( ! self::group_reason( (string) $i, $c ) ) { $g = (string) $i; break; } } if ( '1' !== $g ) { $c = self::context( $g ); } }
  return self::view( $g, $c );
 }
 private static function view( $g, $c ) {
  $base = self::base_reason( $c ); $reason = $base ?: self::group_reason( $g, $c ); $inventory = array(); $others = array(); $order = array();
  for ( $i = 1; $i <= ( $c['count'] ?? 0 ); ++$i ) {
   $id = (string) $i; $why = $base ?: self::group_reason( $id, $c ); $item = array( 'group' => $id, 'label' => 'Discount group ' . $id, 'reason' => $why, 'native' => self::native_url() );
   if ( $why ) { $others[] = $item; } else { $inventory[] = $item; }
   $order[] = 'Group ' . $id . ': ' . ( 'yes' === self::raw( $c, self::key( $id, 'enabled' ), 'yes' ) ? 'enabled' : 'disabled' ) . '; ' . ( 'percent' === self::raw( $c, self::key( $id, 'coefficient_type' ), 'percent' ) ? 'percentage' : 'native type' ) . '. Applicability is not evaluated here.';
  }
  $r = self::record( $c ); $receipt = null;
  if ( self::own_record( $g, $r ) ) { $receipt = array( 'status' => $r['status'], 'before' => $r['before']['raw'], 'after' => $r['after']['raw'], 'summary' => $r['summary'], 'current' => self::status( $g, $c ), 'undo' => ! $reason && 'applied' === $r['status'] && self::state( $c, self::key( $g ) ) === $r['after'] && hash_equals( self::hash( $g, $c, false ), $r['after_guard'] ) ); }
  $raw = self::raw( $c, self::key( $g ) );
  return array( 'group' => $g, 'count' => $c['count'], 'inventory' => $inventory, 'other_groups' => $others, 'order' => $order, 'raw' => is_string( $raw ) && strlen( $raw ) <= 30 ? $raw : null, 'percent' => null !== self::negative( $raw ) ? substr( $raw, 1 ) : '', 'guard' => $reason ? '' : self::hash( $g, $c ), 'reason' => $reason, 'status' => self::status( $g, $c ), 'scope' => self::scope( $g, $c ), 'position' => 'Group ' . $g . ' of ' . ( $c['count'] ?? 'an unsupported count' ) . '. ' . ( (int) $g > 1 ? 'Earlier applicable groups can prevent this group from running. ' : '' ) . 'Only the first applicable group in this module is used. No winning group is predicted here.', 'receipt' => $receipt, 'native' => self::native_url(), 'edition' => self::edition() );
 }
 /** Only these two exact keys may be changed. Business row must already exist. */
 private static function write( $c, $key, $after, $g ) {
  global $wpdb; if ( ! in_array( $key, array( self::key( $g ), self::RECEIPT ), true ) ) { self::fail( 'Unexpected write target.', 500 ); }
  $before = self::state( $c, $key ); if ( $before === $after ) { return; }
  if ( null === $before ) {
   if ( self::RECEIPT !== $key || 'off' !== $after['autoload'] ) { self::fail( 'This task cannot create a native discount setting.', 409 ); }
   $n = $wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->options} (option_name,option_value,autoload) VALUES (%s,%s,%s)", $key, $after['raw'], $after['autoload'] ) );
  } else { $n = $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->options} SET option_value=%s,autoload=%s WHERE option_name=%s AND BINARY option_value=%s AND BINARY autoload=%s", $after['raw'], $after['autoload'], $key, $before['raw'], $before['autoload'] ) ); }
  if ( 1 !== $n ) { self::fail( 'A concurrent change or storage error stopped this transaction. No successful save is claimed.', 409 ); }
 }
 private static function clear_cache( $g ) {
  $core = WCJ_Job_Store::core(); foreach ( array( self::key( $g ), self::RECEIPT ) as $key ) { wp_cache_delete( $key, 'options' ); if ( $core ) { unset( $core->options[ $key ] ); } } wp_cache_delete( 'alloptions', 'options' ); wp_cache_delete( 'notoptions', 'options' );
 }
 public static function mutate( $g, $guard, $percent, $example, $undo, $review ) {
  global $wpdb; self::authorize(); $key = self::key( $g ); $reason = self::storage_reason(); if ( $reason ) { self::fail( $reason ); }
  $lock = 'wcj_discount_' . substr( hash( 'sha256', $wpdb->prefix . DB_NAME ), 0, 40 );
  if ( '1' !== (string) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s,5)', $lock ) ) ) { self::fail( 'Another discount change is running. Your draft is retained.', 409 ); }
  $begun = false;
  try {
   if ( false === $wpdb->query( 'SET TRANSACTION ISOLATION LEVEL SERIALIZABLE' ) || false === $wpdb->query( 'START TRANSACTION' ) ) { self::fail( 'A safe transaction could not begin.', 500 ); } $begun = true;
   $c = self::context( $g, true ); self::authorize();
   if ( ! hash_equals( self::hash( $g, $c ), $guard ) ) { self::fail( 'Settings changed. Your draft is parked. Discard and load current values.', 409 ); }
   if ( null === self::decimal( $example, true ) ) { self::fail( 'Use a positive example amount up to 9999.9999 with at most four decimals.' ); }
   $after = self::target( $g, $c, $percent, $undo ); $before = self::state( $c, $key ); $expires = $review['expires'] ?? 0;
   if ( ! is_int( $expires ) || $expires <= time() || $expires > time() + 300 || 'yes' !== ( $review['confirm'] ?? null ) || ! is_string( $review['token'] ?? null ) || ! hash_equals( self::signature( $g, $guard, $before, $after, $example, $undo, $expires ), $review['token'] ) ) { self::fail( 'Review and explicitly confirm this exact discount change before saving or restoring.' ); }
   self::write( $c, $key, $after, $g ); $expected = $c; $expected['rows'][ $key ] = $after; ksort( $expected['rows'] ); $check = self::context( $g, true );
   if ( ! hash_equals( self::hash( $g, $expected ), self::hash( $g, $check ) ) ) { self::fail( 'Readback or context changed. The transaction was not accepted.', 409 ); }
   $record = array( 'version' => 1, 'blog' => get_current_blog_id(), 'user' => get_current_user_id(), 'edition' => self::edition(), 'group' => $g, 'before' => $before, 'after' => $after, 'status' => $undo ? 'undone' : 'applied', 'time' => time(), 'after_guard' => self::hash( $g, $check, false ), 'summary' => ( $undo ? 'Restored your exact previous discount. ' : 'Saved the selected discount only. ' ) . self::status( $g, $check ) . ' Scope, order, correction and other prices were not edited. This receipt is configuration, not a product or checkout quote.' );
   $record['signature'] = hash_hmac( 'sha256', serialize( $record ), wp_salt( 'nonce' ) );
   $record_state = array( 'raw' => serialize( $record ), 'autoload' => isset( $c['rows'][ self::RECEIPT ] ) ? $c['rows'][ self::RECEIPT ]['autoload'] : 'off' ); self::write( $check, self::RECEIPT, $record_state, $g );
   $expected['rows'][ self::RECEIPT ] = $record_state; ksort( $expected['rows'] ); $verified = self::context( $g, true ); self::authorize();
   if ( ! hash_equals( self::hash( $g, $expected ), self::hash( $g, $verified ) ) || ! self::own_record( $g, self::record( $verified ) ) ) { self::fail( 'Receipt or context readback failed. The transaction was not accepted.', 409 ); }
   $commit = $wpdb->query( 'COMMIT' ); $begun = false;
   if ( false === $commit ) { self::clear_cache( $g ); self::fail( 'Commit outcome is unknown. Discard and load current values before another change.', 500 ); }
   self::clear_cache( $g ); self::authorize(); $fresh = self::context( $g );
   if ( ! hash_equals( self::hash( $g, $verified ), self::hash( $g, $fresh ) ) ) { self::fail( 'The transaction committed, but its context changed before final readback. No current saved-state success is claimed. Discard and load current values.', 409 ); }
   return self::view( $g, $fresh );
  } finally { if ( $begun ) { $wpdb->query( 'ROLLBACK' ); self::clear_cache( $g ); } $wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) ); }
 }
 public function handle() {
  try {
   if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! empty( $_FILES ) ) { self::fail( 'Use the task form with a POST request.', 403 ); }
   self::authorize(); $action = $_POST['action'] ?? ''; $op = is_string( $action ) ? substr( $action, strlen( 'wcj_global_discount_job_' ) ) : '';
   if ( ! in_array( $op, self::OPS, true ) || 'wcj_global_discount_job_' . $op !== $action ) { self::fail( 'Unknown task operation.', 403 ); }
   $allowed = 'load' === $op ? array( 'action', 'nonce', 'group' ) : array( 'action', 'nonce', 'group', 'guard', 'percent', 'example', 'undo', 'review' ); $actual = array_keys( $_POST ); sort( $actual ); sort( $allowed );
   if ( $actual !== $allowed ) { self::fail( 'Unexpected or missing task fields.', 403 ); }
   foreach ( $_POST as $value ) { if ( ! is_string( $value ) || strlen( $value ) > 2000 ) { self::fail( 'Task fields must be bounded strings.', 403 ); } }
   if ( ! wp_verify_nonce( wp_unslash( $_POST['nonce'] ), 'wcj_global_discount_job_' . $op ) ) { self::fail( 'This task token expired. Your draft is parked; reload deliberately.', 403 ); }
   $g = wp_unslash( $_POST['group'] );
   if ( 'load' === $op ) { $result = self::load( $g ); }
   else {
    $guard = wp_unslash( $_POST['guard'] ); if ( ! preg_match( '/^[a-f0-9]{64}$/D', $guard ) ) { self::fail( 'Load the current group before changing it.', 409 ); }
    $undo = $_POST['undo']; if ( ! in_array( $undo, array( 'yes', 'no' ), true ) || ( 'save' === $op && 'no' !== $undo ) || ( 'undo' === $op && 'yes' !== $undo ) ) { self::fail( 'Invalid operation binding.', 403 ); }
    $review = json_decode( wp_unslash( $_POST['review'] ), true, 4 );
    if ( ! is_array( $review ) || array_diff( array_keys( $review ), array( 'expires', 'token', 'confirm' ) ) || ( 'review' === $op && $review ) ) { self::fail( 'Invalid review fields.', 403 ); }
    $p = wp_unslash( $_POST['percent'] ); $example = wp_unslash( $_POST['example'] );
    $result = 'review' === $op ? self::review( $g, $guard, $p, $example, 'yes' === $undo ) : self::mutate( $g, $guard, $p, $example, 'yes' === $undo, $review );
   }
   nocache_headers(); wp_send_json_success( $result );
  } catch ( Throwable $e ) {
   $code = in_array( $e->getCode(), array( 403, 409, 422 ), true ) ? $e->getCode() : 500;
   nocache_headers(); wp_send_json_error( array( 'message' => 500 === $code ? 'The outcome could not be verified. Your draft is parked. Discard and load current values before another change.' : $e->getMessage(), 'kind' => 'parked' ), $code );
  }
 }
 public function render() {
  self::authorize();
  wp_enqueue_style( 'wcj-global-discount-job', wcj_plugin_url() . '/assets/css/admin/wcj-global-discount-job.css', array(), '1.0' );
  wp_enqueue_script( 'wcj-global-discount-job', wcj_plugin_url() . '/assets/js/admin/wcj-global-discount-job.js', array(), '1.0', true );
  $nonces = array(); foreach ( self::OPS as $op ) { $nonces[ $op ] = wp_create_nonce( 'wcj_global_discount_job_' . $op ); }
  wp_localize_script( 'wcj-global-discount-job', 'wcjGlobalDiscountJob', array( 'url' => admin_url( 'admin-ajax.php' ), 'nonces' => $nonces, 'storage' => 'wcj-discount-' . get_current_blog_id() . '-' . get_current_user_id() . '-' . self::edition() ) );
  include __DIR__ . '/views/global-discount-job.php';
 }
}
new WCJ_Global_Discount_Job();
