<?php
/** One literal shipping description. Never quotes, enables or edits a rate.
 * @package Booster_For_WooCommerce/admin
 */
defined( 'ABSPATH' ) || exit;
require_once __DIR__ . '/class-wcj-job-store.php';
require_once __DIR__ . '/shipping-delivery-groups.php';
final class WCJ_Shipping_Description_Job {
 const ID = 'shipping_description';
 const MODULE = 'wcj_shipping_description_enabled';
 const MODE = 'wcj_shipping_descriptions_use_shipping_instance';
 const POSITION = 'wcj_shipping_descriptions_position';
 const VISIBILITY = 'wcj_shipping_descriptions_visibility';
 const RECEIPT = 'booster_shipping_description_job_receipt';
 const OPS = array( 'load', 'save', 'undo' );
 const METHODS = array( 'flat_rate' => 'Flat rate', 'free_shipping' => 'Free shipping', 'local_pickup' => 'Local pickup' );
 public function __construct() {
  add_action( 'admin_menu', array( $this, 'register' ), 99 );
  foreach ( self::OPS as $op ) { add_action( 'wp_ajax_wcj_shipping_description_' . $op, array( $this, 'handle' ) ); }
 }
 public static function url() { return admin_url( 'admin.php?page=wcj-shipping-description-job' ); }
 public static function native_url() { return admin_url( wcj_admin_tab_url() . '&wcj-cat=shipping_and_orders&section=shipping_description&active_tab=shipping_description_shipping_method_descriptions_tab' ); }
 private static function fail( $text, $code = 422 ) { throw new RuntimeException( $text, $code ); }
 private static function authorize() { if ( ! WCJ_Task_Finder::allowed( self::ID ) ) { self::fail( 'Ask an administrator for access to shipping descriptions.', 403 ); } }
 public static function edition() { return defined( 'WCJ_PLUGIN_FILE' ) && 'booster-elite-for-woocommerce.php' === basename( WCJ_PLUGIN_FILE ) ? 'elite' : ( defined( 'WCJ_PLUGIN_FILE' ) && 'booster-plus-for-woocommerce.php' === basename( WCJ_PLUGIN_FILE ) ? 'plus' : 'free' ); }
 public function register() { if ( WCJ_Task_Finder::allowed( self::ID ) ) { add_submenu_page( 'woocommerce', 'Edit a shipping description', 'Shipping description', 'manage_woocommerce', 'wcj-shipping-description-job', array( $this, 'render' ) ); } }
 /** Identical exact-byte predicate for drafts, existing values and Undo. Empty is allowed. */
 public static function plain( $v ) {
  return is_string( $v ) && strlen( $v ) <= 500 && 1 === preg_match( '//u', $v )
   && ! preg_match( '/[<>\[\]\p{C}\p{Zl}\p{Zp}]/u', $v )
   && ! preg_match( '/&(?:#[0-9]+;?|#x[0-9a-f]+;?|[a-z][a-z0-9]+;)/i', $v )
   && ! preg_match( '/&(?:Aacute|Agrave|Atilde|Ccedil|Eacute|Egrave|Iacute|Igrave|Ntilde|Oacute|Ograve|Oslash|Otilde|Uacute|Ugrave|Yacute|aacute|agrave|atilde|brvbar|ccedil|curren|divide|eacute|egrave|frac12|frac14|frac34|iacute|igrave|iquest|middot|ntilde|oacute|ograve|oslash|otilde|plusmn|uacute|ugrave|yacute|AElig|Acirc|Aring|Ecirc|Icirc|Ocirc|THORN|Ucirc|acirc|acute|aelig|aring|cedil|ecirc|icirc|iexcl|laquo|micro|ocirc|pound|raquo|szlig|thorn|times|ucirc|Auml|COPY|Euml|Iuml|Ouml|QUOT|Uuml|auml|cent|copy|euml|iuml|macr|nbsp|ordf|ordm|ouml|para|quot|sect|sup1|sup2|sup3|uuml|yuml|AMP|ETH|REG|amp|deg|eth|not|reg|shy|uml|yen|GT|LT|gt|lt)/', $v )
   && ! is_serialized( $v ) && ( '' === $v || ! preg_match( '/^[\s\p{Z}]+$/uD', $v ) );
 }
 private static function key( $target ) {
  if ( preg_match( '/^method:(flat_rate|free_shipping|local_pickup)$/D', $target, $m ) ) { return 'wcj_shipping_description_' . $m[1]; }
  if ( preg_match( '/^instance:([1-9][0-9]{0,9})$/D', $target, $m ) ) { return 'wcj_shipping_description_instance_' . $m[1]; }
  self::fail( 'Select an existing supported method or instance.', 409 );
 }
 private static function tables() { global $wpdb; return array( 'zones' => $wpdb->prefix . 'woocommerce_shipping_zones', 'methods' => $wpdb->prefix . 'woocommerce_shipping_zone_methods', 'locations' => $wpdb->prefix . 'woocommerce_shipping_zone_locations' ); }
 /** Read real configured topology, never instantiate methods or request rates. */
 private static function topology( $lock ) {
  global $wpdb; $out = array();
  $columns = array( 'zones' => 'zone_id,zone_name,zone_order', 'methods' => 'instance_id,zone_id,method_id,method_order,is_enabled', 'locations' => 'location_id,zone_id,location_code,location_type' );
  foreach ( self::tables() as $name => $table ) {
   $rows = $wpdb->get_results( "SELECT {$columns[$name]} FROM {$table} ORDER BY " . ( 'zones' === $name ? 'zone_id' : ( 'methods' === $name ? 'instance_id' : 'location_id' ) ) . ' LIMIT 10001' . ( $lock ? ' FOR UPDATE' : '' ), ARRAY_A );
   if ( $wpdb->last_error || ! is_array( $rows ) || count( $rows ) > 10000 ) { self::fail( 'The actual shipping topology could not be safely read. Use full settings.', 409 ); }
   $out[ $name ] = $rows;
  }
  return $out;
 }
 private static function option_keys( $target, $topology, $roles ) {
  global $wpdb;
  $keys = array( self::MODULE, self::MODE, self::POSITION, self::VISIBILITY, self::RECEIPT, 'wcj_modules_by_user_roles_enabled', $wpdb->prefix . 'user_roles', 'active_plugins', 'woocommerce_ship_to_countries' );
  if ( '' !== $target ) { $keys[] = self::key( $target ); }
  foreach ( self::METHODS as $id => $label ) { $keys[] = 'woocommerce_' . $id . '_settings'; }
  foreach ( $topology['methods'] as $row ) { if ( isset( self::METHODS[ $row['method_id'] ] ) ) { $keys[] = 'woocommerce_' . $row['method_id'] . '_' . $row['instance_id'] . '_settings'; } }
  foreach ( $roles as $role ) { $keys[] = 'wcj_modules_by_user_roles_incl_' . $role; $keys[] = 'wcj_modules_by_user_roles_excl_' . $role; }
  sort( $keys ); return array_values( array_unique( $keys ) );
 }
 /** Installed source qualification, not a version-string assumption. */
 private static function platform() {
  $bindings = array();
  if ( ! defined( 'WC_ABSPATH' ) ) { return array( 'safe' => false ); }
  $files = array( 'includes/class-wc-shipping.php' => 'c274d473d3292208c4f9355643938e4dc419dd1d5647062bfb229ac0c0a57e24', 'includes/class-wc-shipping-zone.php' => '2df31b48ca297396e7bda1e8fbcb7cec53a4efcde32a8a83d33ba2be0ced25d5', 'includes/class-wc-shipping-zones.php' => 'b0d3cec14e8425fd73b5802d0daa0660e46694668bb94b36fab1ea36b7235558', 'includes/data-stores/class-wc-shipping-zone-data-store.php' => '248d0f0bd139914fe035571c4998edf220c2e0fc87f2f46ba026eec1f7cf8f12', 'includes/admin/class-wc-admin-settings.php' => '34c63bd66fa729ed1e09bca21a15061183bb12aef4455d0514817399b606eb3d', 'includes/shipping/flat-rate/class-wc-shipping-flat-rate.php' => 'cfbdbc7dcbc005ea54f870dd7808d6ca3b52cb5e4d4b490434d3d5889a1bd0a2', 'includes/shipping/free-shipping/class-wc-shipping-free-shipping.php' => '8371a7f5a4d9f460f1243e64c6ed7a88df2c7b02825114161bf7b95e5c632712', 'includes/shipping/local-pickup/class-wc-shipping-local-pickup.php' => '7f35634e0c90425d1a3aab0dc669617361da8bb61f46a0afd46c7d8586fc4e22', 'wp:wp-includes/option.php' => 'fe3f0b7e115b2a50c31162ed6428abe4088c080b0f71b9678d84806168fe90d0' );
  foreach ( $files as $rel => $expected ) {
   $file = ( 0 === strpos( $rel, 'wp:' ) ? ABSPATH . substr( $rel, 3 ) : WC_ABSPATH . $rel );
   if ( ! is_readable( $file ) || hash_file( 'sha256', $file ) !== $expected ) { return array( 'safe' => false ); }
   $bindings[ $rel ] = $expected;
  }
  foreach ( array( 'flat_rate' => 'WC_Shipping_Flat_Rate', 'free_shipping' => 'WC_Shipping_Free_Shipping', 'local_pickup' => 'WC_Shipping_Local_Pickup' ) as $id => $class ) {
   if ( ! class_exists( $class ) ) { return array( 'safe' => false ); }
   $reflection = new ReflectionClass( $class );
   $file = realpath( WC_ABSPATH . 'includes/shipping/' . str_replace( '_', '-', $id ) . '/class-wc-shipping-' . str_replace( '_', '-', $id ) . '.php' );
   if ( $reflection->getName() !== $class || ! $file || realpath( $reflection->getFileName() ) !== $file ) { return array( 'safe' => false ); }
  }
  return array( 'safe' => true, 'binding' => $bindings );
 }
 /** The exact paid callback returns the raw second argument. No callback is probed. */
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

 private static function context( $target, $lock = false ) {
  global $wpdb; $user = wp_get_current_user(); $topology = self::topology( $lock );
  $keys = self::option_keys( $target, $topology, $user->roles );
  $sql = $wpdb->prepare( "SELECT option_name,option_value,autoload FROM {$wpdb->options} WHERE option_name IN (" . implode( ',', array_fill( 0, count( $keys ), '%s' ) ) . ') ORDER BY option_name' . ( $lock ? ' FOR UPDATE' : '' ), $keys );
  $rows = $wpdb->get_results( $sql, ARRAY_A );
  if ( $wpdb->last_error || ! is_array( $rows ) ) { self::fail( 'Relevant settings could not be read safely.', 409 ); }
  $out = array(); foreach ( $rows as $row ) { $out[ $row['option_name'] ] = array( 'raw' => (string) $row['option_value'], 'autoload' => (string) $row['autoload'] ); }
  $role_rows = $wpdb->get_results( $wpdb->prepare( "SELECT umeta_id,meta_key,meta_value FROM {$wpdb->usermeta} WHERE user_id=%d AND meta_key IN (%s,%s) ORDER BY umeta_id" . ( $lock ? ' FOR UPDATE' : '' ), get_current_user_id(), $wpdb->prefix . 'capabilities', $wpdb->prefix . 'user_level' ), ARRAY_A );
  if ( $wpdb->last_error || ! is_array( $role_rows ) ) { self::fail( 'Current role storage could not be verified.', 409 ); }
  $filters = array();
  foreach ( $keys as $key ) { foreach ( array( '', 'option_', 'pre_option_', 'default_option_', 'pre_update_option_', 'sanitize_option_' ) as $prefix ) { if ( has_filter( $prefix . $key ) ) { $filters[] = $prefix . $key; } } }
  foreach ( array( 'pre_option', 'pre_wp_load_alloptions', 'pre_cache_alloptions', 'alloptions', 'woocommerce_shipping_methods', 'woocommerce_shipping_zone_shipping_methods' ) as $hook ) { if ( has_filter( $hook ) ) { $filters[] = $hook; } }
  sort( $filters );
  return array( 'rows' => $out, 'topology' => $topology, 'keys' => $keys, 'filters' => $filters, 'paid' => self::paid_filter(), 'global_update' => self::global_update_filter( $target ), 'platform' => self::platform(), 'role_rows' => $role_rows, 'roles' => $user->roles, 'caps' => $user->allcaps, 'allowlist' => function_exists( 'wcj_get_enabled_module_allowlist' ) ? wcj_get_enabled_module_allowlist() : null, 'edition' => self::edition(), 'blog' => get_current_blog_id() );
 }
 private static function raw( $c, $key, $default = null ) { return isset( $c['rows'][ $key ] ) ? $c['rows'][ $key ]['raw'] : $default; }
 private static function state( $c, $key ) { return $c['rows'][ $key ] ?? null; }
 private static function hash( $target, $c, $receipt = true ) { if ( ! $receipt ) { unset( $c['rows'][ self::RECEIPT ] ); } return hash_hmac( 'sha256', serialize( array( get_current_user_id(), $target, $c ) ), wp_salt( 'nonce' ) ); }
 private static function inventory( $c ) {
  $out = array(); $zones = array( '0' => 'Locations not covered by other zones' );
  foreach ( $c['topology']['zones'] as $z ) { $zones[ $z['zone_id'] ] = $z['zone_name']; }
  if ( 'yes' === self::raw( $c, self::MODE, 'no' ) ) {
   foreach ( $c['topology']['methods'] as $row ) {
    if ( isset( self::METHODS[ $row['method_id'] ], $zones[ $row['zone_id'] ] ) && preg_match( '/^[1-9][0-9]{0,9}$/D', $row['instance_id'] ) ) {
     $out[ 'instance:' . $row['instance_id'] ] = array( 'label' => $zones[ $row['zone_id'] ] . ': ' . self::METHODS[ $row['method_id'] ] . ' #' . $row['instance_id'], 'method' => $row['method_id'], 'instance' => $row['instance_id'], 'zone' => $row['zone_id'], 'enabled' => $row['is_enabled'], 'matches' => array( $row ), 'woo' => admin_url( 'admin.php?page=wc-settings&tab=shipping&instance_id=' . $row['instance_id'] ) );
    }
   }
  } else {
   foreach ( self::METHODS as $id => $label ) {
    $matches = array(); foreach ( $c['topology']['methods'] as $row ) { if ( $row['method_id'] === $id ) { $row['zone_name'] = $zones[ $row['zone_id'] ] ?? 'Unknown zone'; $matches[] = $row; } }
    $out[ 'method:' . $id ] = array( 'label' => $label . ' (all matching instances/rates)', 'method' => $id, 'instance' => null, 'enabled' => null, 'matches' => $matches, 'woo' => admin_url( 'admin.php?page=wc-settings&tab=shipping' ) );
   }
  }
  return $out;
 }
 private static function storage_reason() {
  global $wpdb;
  if ( wp_using_ext_object_cache() ) { return 'Persistent object caching needs full settings; this task cannot safely save here.'; }
  foreach ( array_merge( array( $wpdb->options, $wpdb->usermeta ), array_values( self::tables() ) ) as $table ) {
   $row = $wpdb->get_row( $wpdb->prepare( 'SHOW TABLE STATUS WHERE Name = %s', $table ), ARRAY_A );
   if ( ! $row || 'InnoDB' !== $row['Engine'] ) { return 'This task requires transactional option, role and shipping topology storage. Use full settings.'; }
  }
  $tables = self::tables();
  $indexes = array( $wpdb->options => array( 'option_name', true ), $wpdb->usermeta => array( 'user_id', false ), $tables['zones'] => array( 'zone_id', true ), $tables['methods'] => array( 'instance_id', true ), $tables['locations'] => array( 'location_id', true ) );
  foreach ( $indexes as $table => $required ) {
   $rows = $wpdb->get_results( "SHOW INDEX FROM {$table}", ARRAY_A ); $found = false; $parts = array();
   if ( $wpdb->last_error || ! is_array( $rows ) ) { return 'The native storage indexes could not be qualified. Use full settings.'; }
   foreach ( $rows as $row ) { $parts[ $row['Key_name'] ][] = $row; }
   foreach ( $parts as $index ) { if ( 1 === count( $index ) && $required[0] === $index[0]['Column_name'] && 1 === (int) $index[0]['Seq_in_index'] && ( ! $required[1] || 0 === (int) $index[0]['Non_unique'] ) && empty( $index[0]['Sub_part'] ) ) { $found = true; } }
   if ( ! $found ) { return 'The required native exact-key storage index is missing or custom. Use full settings.'; }
  }
  return '';
 }
 private static function refusal( $target, $c ) {
  if ( ! $c['platform']['safe'] ) { return 'The installed shipping registry, core methods or persistence source could not be qualified. Use full settings.'; }
  if ( ! $c['paid']['safe'] || ! $c['global_update']['safe'] || $c['filters'] ) { return 'An unqualified relevant settings or shipping integration needs full settings. No callback was invoked or removed to probe it.'; }
  if ( empty( $c['roles'] ) || empty( $c['role_rows'] ) ) { return 'Current native rights could not be qualified. Use full settings.'; }
  foreach ( array( self::MODULE, self::MODE, 'wcj_modules_by_user_roles_enabled' ) as $key ) { if ( ! in_array( self::raw( $c, $key, 'no' ), array( 'yes', 'no' ), true ) ) { return 'A native mode or activation value is custom. It is preserved; use full settings.'; } }
  if ( 'yes' === self::raw( $c, 'wcj_modules_by_user_roles_enabled', 'no' ) ) { return 'Dynamic module-by-role configuration needs its native settings. No wording or activation changed.'; }
  $position = self::raw( $c, self::POSITION, 'after' );
  if ( 'instead' === $position ) { return 'Saved position is Instead: when honored it replaces the shipping label, including its displayed price. This task does not change or simplify that configuration. Use full settings.'; }
  if ( ! in_array( $position, array( 'before', 'after' ), true ) ) { return 'Saved placement is unknown or custom. Its effect cannot be interpreted by this task. Use full settings.'; }
  if ( ! in_array( self::raw( $c, self::VISIBILITY, 'both' ), array( 'both', 'cart_only', 'checkout_only' ), true ) ) { return 'Saved visibility is custom. Use full settings; its raw value is preserved.'; }
  $inventory = self::inventory( $c );
  if ( ! isset( $inventory[ $target ] ) ) { return 'This target no longer exists in the current method/instance mode. Keep your draft and use full settings.'; }
  foreach ( $inventory[ $target ]['matches'] as $row ) { if ( ! in_array( $row['is_enabled'], array( '0', '1' ), true ) ) { return 'A shipping instance enabled flag is unsupported. Use its WooCommerce settings.'; } }
  $value = self::raw( $c, self::key( $target ), '' );
  if ( ! self::plain( $value ) ) { return 'Existing wording contains unsupported HTML, entities, shortcodes, structure, whitespace or more than 500 UTF-8 bytes. It is preserved unchanged; use full settings.'; }
  return self::storage_reason();
 }
 private static function status( $target, $c ) {
  $inventory = self::inventory( $c ); $item = $inventory[ $target ] ?? null;
  $status = 'yes' === self::raw( $c, self::MODULE, 'no' ) ? 'Shipping descriptions module on.' : 'Shipping descriptions module off: saved wording is inactive through this module.';
  if ( $item && null !== $item['enabled'] ) { $status .= '1' === $item['enabled'] ? ' Selected instance enabled.' : ' Selected instance off: saving wording does not enable it.'; }
  else { $status .= ' Method-wide configuration: no single enabled state or offered rate is implied.'; if ( $item ) { $off = count( array_filter( $item['matches'], static function ( $row ) { return '0' === $row['is_enabled']; } ) ); if ( $off ) { $status .= ' ' . $off . ' matching configured instance(s) off; saving wording does not enable them.'; } if ( ! $item['matches'] ) { $status .= ' No matching configured zone instance was observed.'; } } }
  return $status;
 }
 private static function record( $c ) {
  $raw = self::raw( $c, self::RECEIPT );
  $r = is_string( $raw ) && strlen( $raw ) < 12000 && is_serialized( $raw ) ? unserialize( $raw, array( 'allowed_classes' => false ) ) : null;
  if ( ! is_array( $r ) || ! is_string( $r['signature'] ?? null ) ) { return null; }
  $signature = $r['signature']; unset( $r['signature'] );
  return hash_equals( hash_hmac( 'sha256', serialize( $r ), wp_salt( 'nonce' ) ), $signature ) ? $r : null;
 }
 private static function valid_state( $s ) { return null === $s || is_array( $s ) && count( $s ) === 2 && self::plain( $s['raw'] ?? null ) && is_string( $s['autoload'] ?? null ) && in_array( $s['autoload'], array( 'yes', 'no', 'on', 'off', 'auto', 'auto-on', 'auto-off' ), true ); }
 private static function own_record( $target, $r ) { return is_array( $r ) && 1 === ( $r['version'] ?? 0 ) && get_current_blog_id() === ( $r['blog'] ?? 0 ) && get_current_user_id() === ( $r['user'] ?? 0 ) && self::edition() === ( $r['edition'] ?? '' ) && $target === ( $r['target'] ?? '' ) && array_key_exists( 'before', $r ) && array_key_exists( 'after', $r ) && self::valid_state( $r['before'] ) && self::valid_state( $r['after'] ) && is_string( $r['after_guard'] ?? null ); }
 private static function view( $target, $c ) {
  $inventory = self::inventory( $c ); $item = $inventory[ $target ] ?? null; $r = self::record( $c ); $receipt = null;
  $reason = self::refusal( $target, $c );
  if ( self::own_record( $target, $r ) ) { $receipt = array( 'status' => $r['status'], 'before' => $r['before'], 'after' => $r['after'], 'summary' => $r['summary'], 'current' => self::status( $target, $c ), 'undo' => ! $reason && 'applied' === $r['status'] && hash_equals( self::hash( $target, $c, false ), $r['after_guard'] ) ); }
  $mode = self::raw( $c, self::MODE, 'no' );
  $active = 'yes' === self::raw( $c, self::MODULE, 'no' ) && $item && ( null === $item['enabled'] ? count( array_filter( $item['matches'], static function ( $row ) { return '1' === $row['is_enabled']; } ) ) > 0 : '1' === $item['enabled'] );
  return array( 'target' => $target, 'inventory' => $inventory, 'item' => $item, 'guard' => $reason ? '' : self::hash( $target, $c ), 'value' => self::raw( $c, self::key( $target ), '' ), 'exists' => null !== self::state( $c, self::key( $target ) ), 'reason' => $reason, 'status' => self::status( $target, $c ), 'mode' => $mode, 'scope' => 'yes' === $mode ? 'One actual numeric instance under the existing native instance map.' : 'All matching method instances/rates across zones and packages, not just one choice. This does not enable or offer any rate.', 'position' => 'free' === self::edition() ? 'after' : self::raw( $c, self::POSITION, 'after' ), 'visibility' => 'free' === self::edition() ? 'both' : self::raw( $c, self::VISIBILITY, 'both' ), 'raw_position' => self::raw( $c, self::POSITION, 'after' ), 'raw_visibility' => self::raw( $c, self::VISIBILITY, 'both' ), 'receipt' => $receipt, 'native' => self::native_url(), 'next' => $active ? 'Next check: use a private test cart reaching the relevant zone and read cart/checkout wording. No order is needed. Themes and Blocks may differ; this is not output or availability proof.' : 'Next check needs the module on and a matching enabled instance. The module/selected instance is off, or no enabled matching zone instance was observed. Review full settings; this save never activates or creates either.' );
 }
 public static function load( $target = '' ) {
  self::authorize(); if ( '' !== $target ) { self::key( $target ); }
  $c = self::context( $target );
  if ( '' === $target ) { $inventory = self::inventory( $c ); if ( ! $inventory ) { self::fail( 'There is no supported existing instance in the current mode. Use full settings; no instance was created.' ); } $target = array_key_first( $inventory ); $c = self::context( $target ); }
  return self::view( $target, $c );
 }
 /** Exact state CAS, including absence and autoload. */
 private static function write( $c, $key, $after ) {
  global $wpdb; $before = self::state( $c, $key ); if ( $before === $after ) { return; }
  if ( null === $before ) { $n = $wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->options} (option_name,option_value,autoload) VALUES (%s,%s,%s)", $key, $after['raw'], $after['autoload'] ) ); }
  elseif ( null === $after ) { $n = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name=%s AND BINARY option_value=%s AND BINARY autoload=%s", $key, $before['raw'], $before['autoload'] ) ); }
  else { $n = $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->options} SET option_value=%s,autoload=%s WHERE option_name=%s AND BINARY option_value=%s AND BINARY autoload=%s", $after['raw'], $after['autoload'], $key, $before['raw'], $before['autoload'] ) ); }
  if ( 1 !== $n ) { self::fail( 'A concurrent change or storage error stopped this transaction. No successful save is claimed.', 409 ); }
 }
 private static function clear_cache( $target ) {
  $core = WCJ_Job_Store::core(); foreach ( array( self::key( $target ), self::RECEIPT ) as $key ) { wp_cache_delete( $key, 'options' ); if ( $core ) { unset( $core->options[ $key ] ); } } wp_cache_delete( 'alloptions', 'options' ); wp_cache_delete( 'notoptions', 'options' );
 }
 public static function mutate( $target, $guard, $draft, $undo = false ) {
  global $wpdb; self::authorize(); $key = self::key( $target ); $reason = self::storage_reason(); if ( $reason ) { self::fail( $reason ); }
  $lock = 'wcj_ship_text_' . substr( hash( 'sha256', $wpdb->prefix . DB_NAME ), 0, 40 );
  if ( '1' !== (string) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s,5)', $lock ) ) ) { self::fail( 'Another description save is running. Keep your draft and try again.', 409 ); }
  $begun = false;
  try {
   if ( false === $wpdb->query( 'SET TRANSACTION ISOLATION LEVEL SERIALIZABLE' ) || false === $wpdb->query( 'START TRANSACTION' ) ) { self::fail( 'A safe transaction could not begin.', 500 ); } $begun = true;
   $c = self::context( $target, true ); self::authorize();
   if ( ! hash_equals( self::hash( $target, $c ), $guard ) ) { self::fail( 'Settings changed. Your draft is parked. Discard and load current values.', 409 ); }
   $reason = self::refusal( $target, $c ); if ( $reason ) { self::fail( $reason ); }
   $before = self::state( $c, $key );
   if ( ! self::valid_state( $before ) ) { self::fail( 'The current raw state is unsupported. Use full settings.' ); }
   if ( $undo ) {
    $r = self::record( $c );
    if ( ! self::own_record( $target, $r ) || 'applied' !== $r['status'] || $before !== $r['after'] || ! hash_equals( self::hash( $target, $c, false ), $r['after_guard'] ) ) { self::fail( 'Undo no longer matches your change. It will not overwrite newer settings. Discard and load current values.', 409 ); }
    $after = $r['before'];
   } else {
    if ( ! self::plain( $draft ) ) { self::fail( 'Use literal single-line text up to 500 UTF-8 bytes: no HTML, entities, shortcodes, structure, controls or whitespace-only value. Nothing was converted.' ); }
    $after = null === $before && '' === $draft ? null : array( 'raw' => $draft, 'autoload' => null === $before ? 'on' : $before['autoload'] );
   }
   if ( $before === $after ) { self::fail( 'No description changed. Nothing to save.' ); }
   self::write( $c, $key, $after );
   $expected = $c; if ( null === $after ) { unset( $expected['rows'][ $key ] ); } else { $expected['rows'][ $key ] = $after; ksort( $expected['rows'] ); }
   $check = self::context( $target, true );
   if ( ! hash_equals( self::hash( $target, $expected ), self::hash( $target, $check ) ) ) { self::fail( 'Readback or context changed; this transaction is not accepted.', 409 ); }
   $r = array( 'version' => 1, 'blog' => get_current_blog_id(), 'user' => get_current_user_id(), 'edition' => self::edition(), 'target' => $target, 'before' => $before, 'after' => $after, 'status' => $undo ? 'undone' : 'applied', 'time' => time(), 'after_guard' => self::hash( $target, $check, false ), 'summary' => ( $undo ? 'Restored exact prior wording/absence and autoload. ' : 'Saved exact wording only. ' ) . self::status( $target, $check ) . ' No activation, price, availability or zone configuration changed.' );
   $r['signature'] = hash_hmac( 'sha256', serialize( $r ), wp_salt( 'nonce' ) );
   $record_state = array( 'raw' => serialize( $r ), 'autoload' => isset( $c['rows'][ self::RECEIPT ] ) ? $c['rows'][ self::RECEIPT ]['autoload'] : 'off' );
   self::write( $check, self::RECEIPT, $record_state );
   $expected['rows'][ self::RECEIPT ] = $record_state; ksort( $expected['rows'] ); $verified = self::context( $target, true ); self::authorize();
   if ( ! hash_equals( self::hash( $target, $expected ), self::hash( $target, $verified ) ) || ! self::record( $verified ) ) { self::fail( 'Receipt or context readback failed; the transaction was rolled back.', 409 ); }
   if ( false === $wpdb->query( 'COMMIT' ) ) { self::fail( 'Commit outcome is unknown. Discard and load current values before another change.', 500 ); } $begun = false;
   self::clear_cache( $target ); self::authorize(); $fresh = self::context( $target );
   if ( ! hash_equals( self::hash( $target, $verified ), self::hash( $target, $fresh ) ) ) { self::fail( 'The transaction committed, but configuration changed before the final readback. No current saved-state claim is made. Your draft is parked; discard and load current values.', 409 ); }
   return self::view( $target, $fresh );
  } finally { if ( $begun ) { $wpdb->query( 'ROLLBACK' ); } $wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) ); }
 }
 public function handle() {
  try {
   if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! empty( $_FILES ) ) { self::fail( 'Use the task form with a POST request.', 403 ); }
   self::authorize(); $action = $_POST['action'] ?? ''; $op = is_string( $action ) ? substr( $action, strlen( 'wcj_shipping_description_' ) ) : '';
   if ( ! in_array( $op, self::OPS, true ) || 'wcj_shipping_description_' . $op !== $action ) { self::fail( 'Unknown task operation.', 403 ); }
   $allowed = 'load' === $op ? array( 'action', 'nonce', 'target' ) : ( 'save' === $op ? array( 'action', 'nonce', 'target', 'guard', 'text' ) : array( 'action', 'nonce', 'target', 'guard' ) );
   $actual = array_keys( $_POST ); sort( $actual ); sort( $allowed ); if ( $actual !== $allowed ) { self::fail( 'Unexpected or missing task fields.', 403 ); }
   foreach ( $_POST as $value ) { if ( ! is_string( $value ) ) { self::fail( 'Task fields must be strings.', 403 ); } }
   if ( ! wp_verify_nonce( wp_unslash( $_POST['nonce'] ), 'wcj_shipping_description_' . $op ) ) { self::fail( 'The task token expired. Your draft is parked; reload deliberately.', 403 ); }
   $target = wp_unslash( $_POST['target'] );
   if ( 'load' === $op ) { $result = self::load( $target ); }
   else { $guard = wp_unslash( $_POST['guard'] ); if ( ! preg_match( '/^[a-f0-9]{64}$/D', $guard ) ) { self::fail( 'Load the current target before saving.', 409 ); } $result = self::mutate( $target, $guard, 'save' === $op ? wp_unslash( $_POST['text'] ) : null, 'undo' === $op ); }
   nocache_headers(); wp_send_json_success( $result );
  } catch ( Throwable $e ) {
   $code = in_array( $e->getCode(), array( 403, 409, 422 ), true ) ? $e->getCode() : 500;
   nocache_headers(); wp_send_json_error( array( 'message' => 500 === $code ? 'The outcome could not be verified. Your draft is parked. Discard and load current values before another change.' : $e->getMessage(), 'kind' => 'parked' ), $code );
  }
 }
 public function render() {
  self::authorize(); wp_enqueue_style( 'wcj-shipping-description-job', wcj_plugin_url() . '/assets/css/admin/wcj-shipping-description-job.css', array(), '1.0' );
  wp_enqueue_script( 'wcj-shipping-description-job', wcj_plugin_url() . '/assets/js/admin/wcj-shipping-description-job.js', array(), '1.0', true );
  $nonces = array(); foreach ( self::OPS as $op ) { $nonces[ $op ] = wp_create_nonce( 'wcj_shipping_description_' . $op ); }
  wp_localize_script( 'wcj-shipping-description-job', 'wcjShippingDescription', array( 'url' => admin_url( 'admin-ajax.php' ), 'nonces' => $nonces, 'storage' => 'wcj-shipping-description-' . get_current_blog_id() . '-' . get_current_user_id() ) );
  include __DIR__ . '/views/shipping-description-job.php';
 }
}
new WCJ_Shipping_Description_Job();
