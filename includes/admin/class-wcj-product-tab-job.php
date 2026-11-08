<?php
/** One plain global product tab. Native visibility, exact scoped writes and guarded recovery. @package Booster_For_WooCommerce/admin */
if ( ! defined( 'ABSPATH' ) ) { exit; }
require_once __DIR__ . '/class-wcj-job-store.php';
if ( ! class_exists( 'WCJ_Product_Tab_Job' ) ) :
class WCJ_Product_Tab_Job {
 const MODULE = 'wcj_product_tabs_enabled';
 const GLOBAL_ON = 'wcj_custom_product_tabs_global_enabled';
 const TOTAL = 'wcj_custom_product_tabs_global_total_number';
 const RECEIPT = 'booster_product_tab_job_receipt';
 const LOCK = 'booster_product_tab_job_lock';
 const OPS = array( 'load', 'preview', 'apply', 'recover', 'resolve' );
 const RULES = array( 'global_hide_in_products_', 'title_global_hide_in_product_ids_', 'global_hide_in_cats_', 'title_global_hide_in_cats_ids_', 'global_hide_in_tags_', 'global_show_in_products_', 'title_global_show_in_product_ids_', 'global_show_in_cats_', 'title_global_show_in_cats_ids_', 'global_show_in_tags_' );
 public function __construct() {
  add_action( 'admin_menu', array( $this, 'register' ), 99 );
  add_filter( 'wcj_settings_product_tabs', array( $this, 'card_setting' ), 9999 );
  add_action( 'woocommerce_admin_field_wcj_product_tab_job', array( $this, 'render' ) );
  foreach ( self::OPS as $op ) { add_action( 'wp_ajax_wcj_product_tab_job_' . $op, array( $this, 'handle' ) ); }
 }
 public function register() {
		if ( ! WCJ_Task_Finder::allowed( 'product_tabs' ) ) { return; } add_submenu_page( 'woocommerce', 'Add a shared product tab', 'Product information tab', 'manage_woocommerce', 'wcj-product-tab-job', array( $this, 'render' ) ); }
 public function card_setting( $settings ) { if ( ! WCJ_Task_Finder::allowed( 'product_tabs' ) ) { return $settings; } array_unshift( $settings, array( 'type' => 'wcj_product_tab_job', 'id' => 'booster_product_tab_guided_card' ) ); return $settings; }
 public static function native_url() { return admin_url( wcj_admin_tab_url() . '&wcj-cat=products&section=product_tabs&active_tab=wcj_product_tab_for_all_product_tab#wcj_product_tab_options' ); }
 private static function authorize() { if ( ! WCJ_Task_Finder::allowed( 'product_tabs' ) ) { throw new RuntimeException( 'You need permission to manage WooCommerce.' ); } }
 private static function free() { return defined( 'WCJ_FREE_PLUGIN_PATH' ); }
 private static function edition() { return self::elite() ? 'elite' : ( self::free() ? 'free' : 'plus' ); }
 private static function compatible( $r ) { return is_array( $r ) && isset( $r['slot'], $r['elite'] ) && ( self::elite() || ! $r['elite'] ) && ( ! self::free() || ( (int) $r['slot'] <= 1 && ( ! isset( $r['edition'] ) || 'free' === $r['edition'] ) ) ); }
 private static function elite() { return defined( 'WCJ_PLUGIN_FILE' ) && 'booster-elite-for-woocommerce.php' === basename( WCJ_PLUGIN_FILE ); }
 private static function key( $part, $slot ) { return 'wcj_custom_product_tabs_' . $part . $slot; }
 private static function snapshot() {
  global $wpdb;
  $rows = $wpdb->get_results( $wpdb->prepare( "SELECT option_name,option_value FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s OR option_name IN (%s,%s) ORDER BY option_name", $wpdb->esc_like( 'wcj_custom_product_tabs_' ) . '%', $wpdb->esc_like( 'wcj_product_info_product_tabs_' ) . '%', self::MODULE, 'wcj_modules_by_user_roles_enabled' ), ARRAY_A );
  if ( ! is_array( $rows ) || $wpdb->last_error ) { throw new RuntimeException( 'The saved tab settings could not be read. Nothing was changed.' ); }
  $s = array(); foreach ( $rows as $r ) { $s[ $r['option_name'] ] = (string) $r['option_value']; } return $s;
 }
 private static function value( $s, $k, $default = '' ) { return array_key_exists( $k, $s ) ? maybe_unserialize( $s[ $k ] ) : $default; }
 private static function state( $s, $k ) { return array( 'exists' => array_key_exists( $k, $s ), 'raw' => isset( $s[ $k ] ) ? $s[ $k ] : '' ); }
 private static function total( $s ) {
  $n = self::value( $s, self::TOTAL, 1 );
  if ( ! is_scalar( $n ) || ! preg_match( '/^[0-9]{1,2}$/D', (string) $n ) || (int) $n > 50 ) { throw new RuntimeException( 'This tab count needs the full editor. Nothing was changed.' ); }
  $effective = apply_filters( 'booster_option', 1, (int) $n );
  if ( ! is_numeric( $effective ) || (int) $effective < 0 || (int) $effective > 50 ) { throw new RuntimeException( 'The native tab limit needs the full editor.' ); }
  return (int) $effective;
 }
 private static function slot( $s ) {
  $v = isset( $_POST['slot'] ) && is_string( $_POST['slot'] ) ? wp_unslash( $_POST['slot'] ) : 'auto';
  if ( 'auto' === $v ) { $v = (string) self::preferred( $s ); }
  if ( ! preg_match( '/^[1-9][0-9]?$/D', $v ) || (int) $v > 50 || ( ! self::elite() && self::preferred( $s ) !== (int) $v ) ) { throw new RuntimeException( 'Choose a tab supported by this guided edition.' ); }
  $i = (int) $v; $total = self::total( $s );
  if ( $i > max( 1, $total + 1 ) || (int) apply_filters( 'booster_option', 1, max( $i, $total ) ) < $i ) { throw new RuntimeException( 'This tab is outside the native slot limit.' ); }
  return $i;
 }
 private static function preferred( $s ) {
  $total = self::total( $s ); $r = get_option( self::RECEIPT, null );
  if ( is_array( $r ) && isset( $r['slot'], $r['elite'] ) && ( self::elite() || ! $r['elite'] ) && $r['slot'] <= max( 1, $total ) && ! self::lock_reason( $s, $r['slot'] ) ) { return $r['slot']; }
  $first = null;
  for ( $i = 1; $i <= max( 1, $total ); ++$i ) { if ( self::lock_reason( $s, $i ) ) { continue; } if ( null === $first ) { $first = $i; } if ( '' === self::value( $s, self::key( 'title_global_', $i ) ) && '' === self::value( $s, self::key( 'content_global_', $i ) ) ) { return $i; } }
  if ( null !== $first ) { return $first; }
  if ( self::elite() && $total < 50 && (int) apply_filters( 'booster_option', 1, $total + 1 ) >= $total + 1 ) { return $total + 1; }
  return 1;
 }
 private static function rules( $s, $i ) { $r = array(); foreach ( self::RULES as $p ) { $r[ $p ] = self::value( $s, self::key( $p, $i ), null ); } return $r; }
 private static function cats() {
  $terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'number' => 501 ) );
  if ( is_wp_error( $terms ) || count( $terms ) > 500 ) { throw new RuntimeException( 'The category list needs the full editor (up to 500 categories here).' ); }
  $out = array(); foreach ( $terms as $t ) { $out[ (string) $t->term_id ] = array( 'name' => html_entity_decode( $t->name, ENT_QUOTES | ENT_HTML5, 'UTF-8' ), 'count' => (int) $t->count, 'parent' => (int) $t->parent ); } return $out;
 }
 private static function catalog() {
  global $wpdb;
  $p = $wpdb->get_results( "SELECT ID,post_title FROM {$wpdb->posts} WHERE post_type='product' AND post_status='publish' ORDER BY ID LIMIT 2001", ARRAY_A );
  if ( ! is_array( $p ) || $wpdb->last_error ) { throw new RuntimeException( 'Published product reach could not be read.' ); }
  $ids = array_map( 'intval', array_column( $p, 'ID' ) ); $terms = array();
  if ( $ids ) { $terms = $wpdb->get_results( "SELECT tr.object_id,tt.taxonomy,tt.term_id FROM {$wpdb->term_relationships} tr INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id=tt.term_taxonomy_id WHERE tr.object_id IN (" . implode( ',', $ids ) . ") AND tt.taxonomy IN ('product_cat','product_tag') ORDER BY tr.object_id,tt.taxonomy,tt.term_id", ARRAY_A ); if ( ! is_array( $terms ) || $wpdb->last_error ) { throw new RuntimeException( 'Product category assignments could not be read. Nothing was changed.' ); } }
  return array( 'products' => $p, 'terms' => $terms, 'categories' => self::cats() );
 }
 private static function fingerprint( $s, $omit = array() ) {
  foreach ( $omit as $k ) { unset( $s[ $k ] ); } ksort( $s );
  return hash( 'sha256', serialize( array( $s, self::total( $s ), self::catalog(), function_exists( 'wcj_get_enabled_module_allowlist' ) ? wcj_get_enabled_module_allowlist() : null ) ) );
 }
 private static function configuration_fingerprint( $s, $omit = array() ) {
  foreach ( $omit as $k ) { unset( $s[ $k ] ); } ksort( $s );
  return hash( 'sha256', serialize( array( $s, apply_filters( 'booster_option', 1, 50 ), function_exists( 'wcj_get_enabled_module_allowlist' ) ? wcj_get_enabled_module_allowlist() : null ) ) );
 }
 private static function lock_reason( $s, $i ) {
  if ( ! class_exists( 'WCJ_Product_Tabs' ) || ! method_exists( 'WCJ_Product_Tabs', 'global_tab_visible_with_rules' ) ) { return 'The native tab preview is unavailable. Open the full editor.'; }
  if ( function_exists( 'wcj_is_module_allowed_by_allowlist' ) && ! wcj_is_module_allowed_by_allowlist( 'product_tabs' ) ) { return 'Product Tabs is outside the module allowlist.'; }
  if ( 'yes' === self::value( $s, 'wcj_modules_by_user_roles_enabled' ) ) { return 'Modules by User Roles needs the full editor.'; }
  foreach ( $s as $k => $raw ) { if ( preg_match( '/^wcj_custom_product_tabs_key_global_([0-9]+)$/D', $k, $m ) && (int) $m[1] !== $i && maybe_unserialize( $raw ) === 'global_' . $i ) { return 'Another native tab uses this tab key. Open the full editor; no tab will be displaced.'; } }
  global $wpdb;
  $collision = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key LIKE %s AND meta_value=%s LIMIT 1", $wpdb->esc_like( '_wcj_custom_product_tabs_key_local_' ) . '%', 'global_' . $i ) );
  if ( $wpdb->last_error ) { throw new RuntimeException( 'Native tab key collisions could not be checked. Nothing was changed.' ); }
  if ( $collision ) { return 'A per-product native tab uses this tab key. Open the full editor; no tab will be displaced.'; }
  $keys = array_merge( array_keys( $s ), array( self::MODULE, self::GLOBAL_ON, self::TOTAL, self::key( 'title_global_', $i ), self::key( 'content_global_', $i ), self::key( 'priority_global_', $i ), self::key( 'key_global_', $i ), self::key( 'link_global_', $i ), 'wcj_custom_product_tabs_general_content_processing' ) );
  foreach ( self::RULES as $p ) { $keys[] = self::key( $p, $i ); }
  foreach ( $keys as $k ) { if ( has_filter( $k ) || has_filter( 'option_' . $k ) || has_filter( 'pre_option_' . $k ) ) { return 'An option filter customizes tab settings. Open the full editor.'; } }
  foreach ( self::RULES as $p ) {
   if ( in_array( $p, array( 'global_show_in_cats_', 'global_hide_in_cats_' ), true ) ) { continue; }
   if ( ! empty( self::value( $s, self::key( $p, $i ) ) ) ) { return 'This tab uses advanced rules. Open the full editor; its values are preserved.'; }
  }
  if ( '' !== self::value( $s, self::key( 'link_global_', $i ) ) || ! in_array( self::value( $s, self::key( 'key_global_', $i ), 'global_' . $i ), array( '', 'global_' . $i ), true ) ) { return 'This tab uses an advanced link or key. Open the full editor.'; }
  $all = self::cats(); $in = self::value( $s, self::key( 'global_show_in_cats_', $i ) ); $ex = self::value( $s, self::key( 'global_hide_in_cats_', $i ) );
  foreach ( array( $in, $ex ) as $v ) { if ( '' === $v ) { continue; } if ( ! is_array( $v ) || array_values( $v ) !== $v || count( $v ) !== count( array_unique( $v, SORT_REGULAR ) ) ) { return 'A saved category rule needs the full editor.'; } foreach ( $v as $c ) { if ( ! is_string( $c ) || ! isset( $all[ $c ] ) ) { return 'A saved category rule needs the full editor.'; } } }
  if ( ( $in && $ex ) || ( ! self::elite() && $ex ) ) { return 'This tab uses advanced rules for this guided edition. Open the full editor.'; }
  foreach ( array( 'title_global_', 'content_global_' ) as $p ) { $v = self::value( $s, self::key( $p, $i ) ); $title = 'title_global_' === $p; if ( ! is_string( $v ) || ( $title ? sanitize_text_field( $v ) : sanitize_textarea_field( $v ) ) !== $v || preg_match( '/[\[\]]/', $v ) ) { return 'This tab has rich text or shortcodes. Open the full editor; nothing is executed here.'; } if ( ( $title && ( function_exists( 'mb_strlen' ) ? mb_strlen( $v ) : strlen( $v ) ) > 50 ) || ( ! $title && strlen( $v ) > 6000 ) ) { return 'Saved tab text exceeds the guided limits. Open the full editor; its exact values are preserved.'; } }
  $priority = self::value( $s, self::key( 'priority_global_', $i ), '40' );
  if ( ! is_scalar( $priority ) || ! preg_match( '/^(0|[1-9][0-9]{0,2})$/D', (string) $priority ) ) { return 'This saved position needs the full editor. Its exact value is preserved, not replaced with a default.'; }
  return '';
 }
 private static function saved( $s, $i ) { $in = self::value( $s, self::key( 'global_show_in_cats_', $i ) ); $ex = self::value( $s, self::key( 'global_hide_in_cats_', $i ) ); return array( 'title' => self::value( $s, self::key( 'title_global_', $i ) ), 'text' => self::value( $s, self::key( 'content_global_', $i ) ), 'priority' => self::value( $s, self::key( 'priority_global_', $i ), 40 ), 'mode' => $ex ? 'except' : ( $in ? 'categories' : 'all' ), 'categories' => is_array( $ex ?: $in ) ? ( $ex ?: $in ) : array() ); }
 private static function activate_safe( $s, $i ) {
  global $wpdb;
  foreach ( $s as $k => $raw ) {
   if ( preg_match( '/_(global_|in_products_|in_cats_|in_tags_|ids_)([0-9]+)$/', $k, $m ) && (int) $m[2] !== $i && ! empty( maybe_unserialize( $raw ) ) ) { return false; }
   if ( 0 === strpos( $k, 'wcj_product_info_product_tabs_' ) ) { $v = maybe_unserialize( $raw ); $default = false !== strpos( $k, '_priority' ) ? ( false !== strpos( $k, '_description_' ) ? 10 : ( false !== strpos( $k, '_additional_information_' ) ? 20 : 30 ) ) : ( false !== strpos( $k, '_disable' ) ? 'no' : '' ); if ( (string) $v !== (string) $default ) { return false; } }
   if ( preg_match( '/_(title|content|link)_local_default_/', $k ) && ! empty( maybe_unserialize( $raw ) ) ) { return false; }
  }
  $found = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key LIKE %s AND meta_value NOT IN ('','0') LIMIT 1", $wpdb->esc_like( '_wcj_custom_product_tabs_' ) . '%' ) );
  if ( $wpdb->last_error ) { throw new RuntimeException( 'Dormant per-product tabs could not be checked. Shared controls were not enabled.' ); }
  return ! $found;
 }
 private static function plan( $s, $i, $d ) {
  $why = self::lock_reason( $s, $i ); if ( $why ) { throw new RuntimeException( $why ); }
  if ( array_diff( array_keys( $d ), array( 'title', 'text', 'priority', 'mode', 'categories', 'activate' ) ) || ! isset( $d['title'], $d['text'], $d['priority'], $d['mode'], $d['categories'], $d['activate'] ) || ! is_string( $d['title'] ) || ! is_string( $d['text'] ) || ! is_int( $d['priority'] ) || ! is_bool( $d['activate'] ) || ! in_array( $d['mode'], self::elite() ? array( 'all', 'categories', 'except' ) : array( 'all', 'categories' ), true ) || ! is_array( $d['categories'] ) || array_values( $d['categories'] ) !== $d['categories'] ) { throw new RuntimeException( 'Choose plain tab text, a title and a supported audience.' ); }
  if ( '' === trim( $d['title'] ) || '' === trim( $d['text'] ) || ( function_exists( 'mb_strlen' ) ? mb_strlen( $d['title'] ) : strlen( $d['title'] ) ) > 50 || strlen( $d['text'] ) > 6000 || $d['priority'] < 0 || $d['priority'] > 999 ) { throw new RuntimeException( 'Use a title up to 50 characters, text up to 6,000 bytes and position 0 to 999.' ); }
  if ( preg_match( '/[\[\]]/', $d['title'] . $d['text'] ) ) { throw new RuntimeException( 'Looks like a shortcode. Use the full editor.' ); }
  if ( sanitize_text_field( $d['title'] ) !== $d['title'] || sanitize_textarea_field( $d['text'] ) !== $d['text'] ) { throw new RuntimeException( 'Use plain text without HTML. The full editor keeps rich content available.' ); }
  $cats = self::cats(); if ( count( $d['categories'] ) !== count( array_unique( $d['categories'], SORT_REGULAR ) ) ) { throw new RuntimeException( 'Choose each category once.' ); }
  foreach ( $d['categories'] as $c ) { if ( ! is_string( $c ) || ! isset( $cats[ $c ] ) ) { throw new RuntimeException( 'Choose existing categories.' ); } }
  if ( ( 'all' === $d['mode'] && $d['categories'] ) || ( 'all' !== $d['mode'] && ! $d['categories'] ) ) { throw new RuntimeException( 'Choose categories for this audience, or choose All products.' ); }
  $total = self::total( $s ); $needs = 'yes' !== self::value( $s, self::MODULE ) || 'yes' !== self::value( $s, self::GLOBAL_ON, 'yes' ) || $i > $total;
  if ( $needs && ! $d['activate'] ) { throw new RuntimeException( 'Confirm turning on the tab controls listed below the draft.' ); }
  if ( $needs && ( 'yes' !== self::value( $s, self::MODULE ) || 'yes' !== self::value( $s, self::GLOBAL_ON, 'yes' ) ) && ! self::activate_safe( $s, $i ) ) { throw new RuntimeException( 'Turning on shared tab controls would affect other saved tabs or default-tab controls. Open the full editor.' ); }
  $show = 'categories' === $d['mode'] ? $d['categories'] : ''; $hide = 'except' === $d['mode'] ? $d['categories'] : '';
  $values = array( self::key( 'title_global_', $i ) => sanitize_text_field( $d['title'] ), self::key( 'content_global_', $i ) => sanitize_textarea_field( $d['text'] ), self::key( 'priority_global_', $i ) => $d['priority'] );
  $scopes = 'except' === $d['mode'] ? array( 'global_hide_in_cats_' => $hide, 'global_show_in_cats_' => $show ) : array( 'global_show_in_cats_' => $show, 'global_hide_in_cats_' => $hide ); foreach ( $scopes as $p => $v ) { $values[ self::key( $p, $i ) ] = $v; }
  if ( $needs ) { if ( $i > $total ) { $values[ self::TOTAL ] = $i; } $values[ self::GLOBAL_ON ] = 'yes'; $values[ self::MODULE ] = 'yes'; }
  $entries = array(); foreach ( $values as $k => $v ) { $before = self::state( $s, $k ); $default = self::GLOBAL_ON === $k ? 'yes' : ( self::TOTAL === $k ? 1 : ( self::key( 'priority_global_', $i ) === $k ? 40 : '' ) ); if ( ! $before['exists'] && $v === $default ) { continue; } $after = WCJ_Job_Store::value_state( $v ); if ( $before !== $after ) { $entries[ $k ] = array( 'before' => $before, 'after' => $after ); } }
  $r = self::rules( $s, $i ); $r['global_show_in_cats_'] = $show; $r['global_hide_in_cats_'] = $hide;
  $visible = 'yes' === self::value( $s, self::MODULE ) && 'yes' === self::value( $s, self::GLOBAL_ON, 'yes' ) && $i <= $total && '' !== self::value( $s, self::key( 'title_global_', $i ) ) && '' !== self::value( $s, self::key( 'content_global_', $i ) );
  return array( 'entries' => $entries, 'rules' => $r, 'multi' => $visible || $needs, 'needs' => $needs );
 }
 private static function reach( $rules ) {
  $catalog = self::catalog(); $products = $catalog['products']; $limited = count( $products ) > 2000; $products = array_slice( $products, 0, 2000 ); $n = 0; $match = null; $miss = null;
  foreach ( $products as $p ) { $yes = WCJ_Product_Tabs::global_tab_visible_with_rules( $rules, (int) $p['ID'] ); $example = array( 'id' => (int) $p['ID'], 'title' => $p['post_title'], 'url' => get_permalink( (int) $p['ID'] ) ); if ( $yes ) { ++$n; if ( ! $match ) { $match = $example; } } elseif ( ! $miss ) { $miss = $example; } }
  return array( 'matched' => $n, 'checked' => count( $products ), 'limited' => $limited, 'text' => $limited ? 'Native assigned-category reach: checked first 2,000 of more than 2,000 published products; ' . $n . ' matched in this sample. Full reach not counted.' : 'Native assigned-category reach: ' . $n . ' of ' . count( $products ) . ' published products. Theme and other plugin overrides are not counted.', 'match' => $match, 'miss' => $miss );
 }
 private static function display( $k, $st ) { $v = WCJ_Job_Store::state_value( $st ); if ( self::MODULE === $k ) { return 'yes' === $v ? 'On' : 'Off'; } if ( self::GLOBAL_ON === $k ) { return ! $st['exists'] || 'yes' === $v ? 'On' : 'Off'; } if ( self::TOTAL === $k ) { return $st['exists'] ? (string) $v : '1'; } if ( false !== strpos( $k, '_priority_global_' ) ) { return $st['exists'] ? (string) $v : '40'; } if ( false !== strpos( $k, '_in_cats_' ) ) { $c = self::cats(); return is_array( $v ) && $v ? implode( ', ', array_map( static function( $id ) use ( $c ) { return isset( $c[ $id ] ) ? $c[ $id ]['name'] : (string) $id; }, $v ) ) : 'No category restriction'; } return is_scalar( $v ) && '' !== (string) $v ? (string) $v : '(empty)'; }
 private static function label( $k ) { if ( self::MODULE === $k ) { return 'Product Tabs module'; } if ( self::GLOBAL_ON === $k ) { return 'Shared tabs section'; } if ( self::TOTAL === $k ) { return 'Native tab count'; } foreach ( array( 'title_global_' => 'Tab title', 'content_global_' => 'Tab text', 'priority_global_' => 'Position', 'global_show_in_cats_' => 'Only categories', 'global_hide_in_cats_' => 'Except categories' ) as $p => $label ) { if ( false !== strpos( $k, $p ) ) { return $label; } } return 'Tab setting'; }
 private static function rows( $entries, $s = null ) { $rows = array(); foreach ( $entries as $k => $e ) { $row = array( 'label' => self::label( $k ), 'before' => self::display( $k, $e['before'] ), 'after' => self::display( $k, $e['after'] ) ); if ( null !== $s ) { $current = self::state( $s, $k ); $row['current'] = self::display( $k, $current ); $row['current_status'] = $current === $e['after'] ? 'applied' : ( $current === $e['before'] ? 'unchanged' : 'changed_elsewhere' ); } $rows[] = $row; } return $rows; }
 private static function receipt( $s, $i ) {
  $r = get_option( self::RECEIPT, null ); if ( ! is_array( $r ) || ! isset( $r['slot'], $r['entries'], $r['status'], $r['elite'] ) ) { return null; }
  $active = in_array( $r['status'], array( 'applied', 'pending' ), true ); $compatible = self::compatible( $r );
  if ( ! $active && ( $i !== $r['slot'] || ! $compatible ) ) { return null; }
  return array( 'token' => hash( 'sha256', serialize( $r ) ), 'status' => $r['status'], 'pending' => 'pending' === $r['status'], 'restricted' => ! $compatible, 'close' => $active && ( ! $compatible || 'pending' === $r['status'] ), 'recover' => $compatible && $active, 'message' => $compatible ? $r['message'] : 'An open change record belongs to another edition. Its paid settings are not shown or editable here. Keep current settings and close only this record to continue.', 'rows' => $compatible ? self::rows( $r['entries'], $s ) : array(), 'reach' => $compatible && isset( $r['reach'] ) ? $r['reach'] : null );
 }
 private static function view( $s, $i ) {
  $total = self::total( $s ); $slots = array(); $max = self::elite() ? min( 50, max( 1, $total + 1 ) ) : 1;
  if ( ! self::elite() ) { $max = self::preferred( $s ); }
  for ( $n = 1; $n <= $max; ++$n ) { if ( ( ! self::elite() && $n !== $max ) || (int) apply_filters( 'booster_option', 1, max( $n, $total ) ) < $n ) { continue; } $title = self::value( $s, self::key( 'title_global_', $n ) ); $slots[ $n ] = 'Tab ' . $n . ': ' . ( is_string( $title ) && '' !== $title ? $title : 'Add a tab' ); }
  $r = get_option( self::RECEIPT, null ); return array( 'slot' => $i, 'slots' => $slots, 'elite' => self::elite(), 'saved' => self::saved( $s, $i ), 'categories' => self::cats(), 'lock' => self::lock_reason( $s, $i ), 'needs' => 'yes' !== self::value( $s, self::MODULE ) || 'yes' !== self::value( $s, self::GLOBAL_ON, 'yes' ) || $i > $total, 'base' => self::fingerprint( $s ), 'recovery_base' => self::configuration_fingerprint( $s ), 'receipt' => self::receipt( $s, $i ), 'pending_elsewhere' => is_array( $r ) && isset( $r['status'], $r['slot'] ) && in_array( $r['status'], array( 'pending', 'applied' ), true ) && ( ! self::compatible( $r ) || ( 'pending' === $r['status'] && $i !== $r['slot'] ) ) );
 }
 public static function load() { self::authorize(); $s = self::snapshot(); return self::view( $s, self::slot( $s ) ); }
 private static function draft() { $raw = isset( $_POST['draft'] ) && is_string( $_POST['draft'] ) ? wp_unslash( $_POST['draft'] ) : ''; if ( strlen( $raw ) > 15000 ) { throw new RuntimeException( 'Draft too large.' ); } $d = json_decode( $raw, true, 4 ); if ( ! is_array( $d ) ) { throw new RuntimeException( 'The draft could not be read.' ); } return $d; }
 public static function preview() { self::authorize(); $s = self::snapshot(); $i = self::slot( $s ); $d = self::draft(); $p = self::plan( $s, $i, $d ); return array( 'base' => self::fingerprint( $s ), 'changes' => self::rows( $p['entries'] ), 'multi' => $p['multi'], 'reach' => self::reach( $p['rules'] ), 'title' => $d['title'], 'plain_html' => WCJ_Product_Tabs::plain_tab_preview( $d['text'] ) ); }
 private static function save_receipt( $r ) { update_option( self::RECEIPT, $r, false ); if ( get_option( self::RECEIPT ) !== $r ) { throw new RuntimeException( 'The change journal could not be saved. Reload; no success is claimed.' ); } }
 public static function apply() {
  self::authorize(); $lock = WCJ_Job_Store::lock( self::LOCK, 'Another tab change is running.' );
  try {
   $s = self::snapshot(); $i = self::slot( $s ); $p = self::plan( $s, $i, self::draft() ); $base = isset( $_POST['base'] ) && is_string( $_POST['base'] ) ? wp_unslash( $_POST['base'] ) : '';
   if ( ! hash_equals( self::fingerprint( $s ), $base ) ) { throw new RuntimeException( 'Settings or product reach changed. Reload and review again.' ); }
   if ( $p['multi'] && ( ! isset( $_POST['confirm'] ) || '1' !== $_POST['confirm'] ) ) { throw new RuntimeException( 'Confirm the listed changes before applying.' ); }
   $old = get_option( self::RECEIPT, null ); if ( is_array( $old ) && isset( $old['status'] ) && ( 'pending' === $old['status'] || ( 'applied' === $old['status'] && ! self::compatible( $old ) ) ) ) { throw new RuntimeException( 'Recover or close the pending tab change first.' ); }
   if ( ! $p['entries'] ) { return array( 'message' => 'These values are already saved.', 'fresh' => self::view( $s, $i ) ); }
   $r = array( 'v' => 2, 'edition' => self::edition(), 'id' => bin2hex( random_bytes( 12 ) ), 'slot' => $i, 'elite' => self::elite(), 'entries' => $p['entries'], 'guard' => self::configuration_fingerprint( $s, array_keys( $p['entries'] ) ), 'status' => 'pending', 'message' => 'Change started; read current rows before claiming success.', 'reach' => self::reach( $p['rules'] ) ); self::save_receipt( $r ); $expected = $s;
   foreach ( $p['entries'] as $k => $e ) { if ( self::fingerprint( self::snapshot() ) !== self::fingerprint( $expected ) || ! WCJ_Job_Store::compare_exchange( $k, $e['before'], $e['after'] ) ) { $r['message'] = 'A concurrent save interrupted this change. Newer values are kept. Recover the group or check the full editor.'; self::save_receipt( $r ); return array( 'message' => $r['message'], 'fresh' => self::view( self::snapshot(), $i ) ); } $expected[ $k ] = $e['after']['raw']; }
   $done = self::snapshot(); if ( self::fingerprint( $done ) === self::fingerprint( $expected ) ) { $r['status'] = 'applied'; $r['message'] = 'Saved and read back. ' . $r['reach']['text']; } self::save_receipt( $r ); return array( 'message' => $r['message'], 'fresh' => self::view( $done, $i ) );
  } finally { WCJ_Job_Store::unlock( self::LOCK, $lock ); }
 }
 private static function journal( $allow_restricted = false ) {
  $r = get_option( self::RECEIPT, null ); if ( ! is_array( $r ) || ! isset( $r['v'], $r['slot'], $r['entries'], $r['guard'], $r['status'], $r['elite'] ) || ! in_array( $r['v'], array( 1, 2 ), true ) || ! is_int( $r['slot'] ) || $r['slot'] < 1 || $r['slot'] > 50 || ! in_array( $r['status'], array( 'pending', 'applied' ), true ) ) { throw new RuntimeException( 'No current recoverable tab change.' ); }
  $token = isset( $_POST['receipt'] ) && is_string( $_POST['receipt'] ) ? wp_unslash( $_POST['receipt'] ) : ''; if ( ! hash_equals( hash( 'sha256', serialize( $r ) ), $token ) ) { throw new RuntimeException( 'This receipt changed. Reload before acting.' ); }
  if ( ! $allow_restricted && ! self::compatible( $r ) ) { throw new RuntimeException( 'This edition cannot recover paid settings. You may explicitly keep current settings and close only the record.' ); } return $r;
 }
 public static function recover() {
  self::authorize(); $lock = WCJ_Job_Store::lock( self::LOCK, 'Another tab change is running.' );
  try { $s = self::snapshot(); $r = self::journal(); $i = self::preferred( $s ); $conflict = 2 !== $r['v'] || self::configuration_fingerprint( $s, array_keys( $r['entries'] ) ) !== $r['guard']; foreach ( $r['entries'] as $k => $e ) { $c = self::state( $s, $k ); if ( $c !== $e['before'] && $c !== $e['after'] ) { $conflict = true; } }
   if ( $conflict ) { $r['status'] = 'pending'; $r['message'] = 'Changed since. No row was overwritten. Open the full editor, then keep current settings or recover the group.'; self::save_receipt( $r ); return array( 'message' => $r['message'], 'fresh' => self::view( $s, $i ) ); }
   $expected = $s; foreach ( array_reverse( $r['entries'], true ) as $k => $e ) { if ( self::configuration_fingerprint( self::snapshot() ) !== self::configuration_fingerprint( $expected ) || ( self::state( $expected, $k ) !== $e['before'] && ! WCJ_Job_Store::compare_exchange( $k, $e['after'], $e['before'] ) ) ) { $r['status'] = 'pending'; $r['message'] = 'A newer save interrupted recovery. No complete Undo is claimed.'; self::save_receipt( $r ); return array( 'message' => $r['message'], 'fresh' => self::view( self::snapshot(), $i ) ); } if ( $e['before']['exists'] ) { $expected[ $k ] = $e['before']['raw']; } else { unset( $expected[ $k ] ); } }
   $done = self::snapshot(); $r['status'] = self::configuration_fingerprint( $done ) === self::configuration_fingerprint( $expected ) ? 'recovered' : 'pending'; $r['message'] = 'recovered' === $r['status'] ? 'Undone. Previous raw tab settings restored and read back.' : 'A newer save arrived at readback; check current rows.'; self::save_receipt( $r ); return array( 'message' => $r['message'], 'fresh' => self::view( $done, $i ) );
  } finally { WCJ_Job_Store::unlock( self::LOCK, $lock ); }
 }
 public static function resolve() { self::authorize(); $lock = WCJ_Job_Store::lock( self::LOCK, 'Another tab change is running.' ); try { $s = self::snapshot(); $r = self::journal( true ); $base = isset( $_POST['base'] ) && is_string( $_POST['base'] ) ? wp_unslash( $_POST['base'] ) : ''; if ( ( 'pending' !== $r['status'] && self::compatible( $r ) ) || ! isset( $_POST['confirm'] ) || '1' !== $_POST['confirm'] || ! hash_equals( self::configuration_fingerprint( $s ), $base ) ) { throw new RuntimeException( 'Reload and confirm the current saved settings before closing.' ); } $r['status'] = 'checked'; $r['message'] = 'Current settings kept. This change record is closed; a new draft can proceed.'; self::save_receipt( $r ); return array( 'message' => $r['message'], 'fresh' => self::view( $s, self::preferred( $s ) ) ); } finally { WCJ_Job_Store::unlock( self::LOCK, $lock ); } }
 public function handle() {
		if ( ! WCJ_Task_Finder::allowed( 'product_tabs' ) ) { wp_send_json_error( array( 'error' => esc_html__( 'Ask an administrator for access.', 'woocommerce-jetpack' ) ), 403 ); } try { if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) { throw new RuntimeException( 'Use a POST request.' ); } $action = isset( $_POST['action'] ) ? sanitize_key( $_POST['action'] ) : ''; $op = substr( $action, strlen( 'wcj_product_tab_job_' ) ); if ( ! in_array( $op, self::OPS, true ) ) { throw new RuntimeException( 'Unknown operation.' ); } check_ajax_referer( 'wcj_product_tab_job_' . $op, 'nonce' ); nocache_headers(); wp_send_json_success( call_user_func( array( __CLASS__, $op ) ) ); } catch ( Throwable $e ) { nocache_headers(); wp_send_json_error( array( 'error' => $e instanceof RuntimeException ? $e->getMessage() : 'The tab change could not complete. Reload saved state; no success is claimed.' ), 409 ); } }
 public function render() { if ( ! WCJ_Task_Finder::allowed( 'product_tabs' ) ) { wp_die( esc_html__( 'Ask an administrator for access.', 'woocommerce-jetpack' ), '', array( 'response' => 403 ) ); } wp_enqueue_style( 'wcj-product-tab-job', wcj_plugin_url() . '/assets/css/admin/wcj-product-tab-job.css', array(), '1.2' ); wp_enqueue_script( 'wcj-product-tab-job', wcj_plugin_url() . '/assets/js/admin/wcj-product-tab-job.js', array(), '1.5', true ); $nonces = array(); foreach ( self::OPS as $op ) { $nonces[ $op ] = wp_create_nonce( 'wcj_product_tab_job_' . $op ); } wp_localize_script( 'wcj-product-tab-job', 'wcjProductTabJob', array( 'url' => admin_url( 'admin-ajax.php' ), 'nonces' => $nonces ) ); include __DIR__ . '/views/product-tab-job.php'; }
}
endif;
new WCJ_Product_Tab_Job();
