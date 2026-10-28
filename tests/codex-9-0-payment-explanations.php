<?php
/** Affected04 support only. Consumed03 complete assertions are not executed. */
define( 'ABSPATH', __DIR__ . '/' );
define( 'WCJ_PLUGIN_FILE', 'woocommerce-jetpack.php' );
$root = dirname( __DIR__ );
$baseline = isset( $argv[1] ) ? realpath( $argv[1] ) : false;
if ( false === $baseline ) { throw new RuntimeException( 'missing_explicit_baseline' ); }
$tuples = json_decode( file_get_contents( $argv[2] ), true );
$trace = array(); $errors = array(); $records = array(); $opts = array(); $hooks = array();
$cap = true; $admin = false; $logged = true; $checkout = true; $ajax = false; $terms = array();
$plugin = (object) array( 'options' => array(), 'all_modules' => array() );
#[AllowDynamicProperties]
class WCJ_Module {
 public function __construct() {}
 public function is_enabled() { return 'yes' === wcj_get_option( 'wcj_' . $this->id . '_enabled', 'no' ); }
}
class WP_Error { public $code; private $message; public function __construct( $c, $m = '' ) { $this->code = $c; $this->message = $m; } public function get_error_code() { return $this->code; } public function get_error_message() { return $this->message; } }
function is_wp_error( $v ) { return $v instanceof WP_Error; }
function __( $s, $d = null ) { return $s; }
function esc_html__( $s, $d = null ) { return htmlspecialchars( $s, ENT_QUOTES ); }
function esc_html( $s ) { return htmlspecialchars( $s, ENT_QUOTES ); }
function esc_url( $s ) { return htmlspecialchars( $s, ENT_QUOTES ); }
function get_option( $k, $d = null ) { $GLOBALS['trace'][] = array( 'get_option', $k, $d ); return array_key_exists( $k, $GLOBALS['opts'] ) ? $GLOBALS['opts'][$k] : $d; }
function add_action( $tag, $cb, $p = 10, $n = 1 ) { return add_filter( $tag, $cb, $p, $n ); }
function add_filter( $tag, $cb, $p = 10, $n = 1 ) { $GLOBALS['hooks'][$tag][] = array( $cb, $p, $n ); return true; }
function has_filter( $tag, $cb ) { $GLOBALS['trace'][] = array( 'has_filter', $tag, is_array( $cb ) ? $cb[1] : $cb ); foreach ( $GLOBALS['hooks'][$tag] ?? array() as $h ) { if ( $h[0] === $cb ) { return $h[1]; } } return false; }
function apply_filters( $tag, $v, ...$args ) { $GLOBALS['trace'][] = array( 'filter', $tag, $v, $args ); if ( 'booster_option' === $tag && 'elite' === 'free' && isset( $args[0] ) ) { return $args[0]; } if ( 'wcj_use_product_terms_request_cache' === $tag ) { return false; } return $v; }
function did_action( $tag ) { return 0; }
function WCJ() { return $GLOBALS['plugin']; }
function w_c_j() { return $GLOBALS['plugin']; }
function current_user_can( $c ) { return $GLOBALS['cap']; }
function is_admin() { return $GLOBALS['admin']; }
function is_user_logged_in() { return $GLOBALS['logged']; }
function get_current_user_id() { return 7; }
function get_current_blog_id() { return 3; }
function wp_get_current_user() { $GLOBALS['trace'][] = array( 'current_roles' ); return (object) array( 'roles' => array( 'customer' ) ); }
function wp_roles() { return (object) array( 'roles' => array( 'customer' => array(), 'administrator' => array(), 'shop_manager' => array() ) ); }
function get_user_meta( $id, $key, $single = false ) { $GLOBALS['trace'][] = array( 'user_meta', $id, $key ); return ''; }
function wp_doing_ajax() { return $GLOBALS['ajax']; }
function is_checkout() { return $GLOBALS['checkout']; }
function is_checkout_pay_page() { return false; }
function is_wc_endpoint_url( $s ) { return false; }
function wp_is_rest_endpoint() { return false; }
function is_page( $id ) { return false; }
function get_query_var( $k ) { return 0; }
function wc_get_page_id( $s ) { return 9; }
function wc_get_order( $id ) { throw new RuntimeException( 'unexpected_order_read' ); }
function get_woocommerce_currency() { $GLOBALS['trace'][] = array( 'currency' ); return 'USD'; }
function wc_price( $v ) { $GLOBALS['trace'][] = array( 'price', $v ); return '$' . $v; }
function wc_clean( $v ) { $GLOBALS['trace'][] = array( 'clean', $v ); return trim( $v ); }
function sanitize_key( $v ) { return strtolower( preg_replace( '/[^a-zA-Z0-9_-]/', '', $v ) ); }
function sanitize_text_field( $v ) { return trim( strip_tags( $v ) ); }
function wp_unslash( $v ) { return $v; }
function absint( $v ) { return abs( (int) $v ); }
function rest_get_url_prefix() { return 'wp-json'; }
function site_url( $p = '' ) { return 'https://fixture.invalid/' . $p; }
function wp_parse_url( $v ) { return parse_url( $v ); }
function add_query_arg( $a, $url = 'https://fixture.invalid/checkout/' ) { return $url . ( $a ? '?' . http_build_query( $a ) : '' ); }
function admin_url( $p ) { return 'https://fixture.invalid/admin/' . $p; }
function wc_get_checkout_url() { return 'https://fixture.invalid/checkout/'; }
function wp_create_nonce( $a ) { return substr( hash( 'sha256', $a ), 0, 12 ); }
function wp_verify_nonce( $n, $a ) { $GLOBALS['nonce_checks'][] = $a; return wp_create_nonce( $a ) === $n; }
function nocache_headers() {}
function wp_register_ability( $name, $args ) { $GLOBALS['abilities'][$name] = $args; }
function get_the_terms( $id, $tax ) { $GLOBALS['trace'][] = array( 'terms', $id, $tax ); return $GLOBALS['terms']; }
function wcj_customer_get_country() { $GLOBALS['trace'][] = array( 'billing_country' ); return 'US'; }
function wcj_customer_get_country_state() { $GLOBALS['trace'][] = array( 'billing_state' ); return 'NC'; }
function wcj_get_country_by_ip() { throw new RuntimeException( 'unexpected_geoip' ); }
function wcj_get_european_union_countries() { return array( 'FR', 'DE' ); }
function wcj_range_match( $v, $r ) { $a = explode( '...', $r ); return count( $a ) === 2 && $v >= $a[0] && $v <= $a[1]; }
class Fixture_Cart {
 public $cart_contents_total = 20; public $shipping_total = 3; public $items = array(); public $shipping = true;
 public function get_cart() { $GLOBALS['trace'][] = array( 'cart' ); return $this->items; }
 public function needs_shipping() { $GLOBALS['trace'][] = array( 'needs_shipping' ); return $this->shipping; }
 public function get_totals() { $GLOBALS['trace'][] = array( 'totals' ); return array( 'total_tax' => 2 ); }
}
class Fixture_Session { public $chosen = array( 'flat_rate:2' ); public function get( $k ) { $GLOBALS['trace'][] = array( 'session', $k ); return $this->chosen; } }
class Fixture_Customer { public function get_billing_postcode() { $GLOBALS['trace'][] = array( 'billing_postcode' ); return '28277'; } }
$wc = (object) array( 'cart' => new Fixture_Cart(), 'session' => new Fixture_Session(), 'customer' => new Fixture_Customer() );
function WC() { return $GLOBALS['wc']; }
/** Import exact production function bodies, retaining helper cache/filter call order. */
function import_function( $file, $name ) {
 $tokens = token_get_all( file_get_contents( $file ) ); $n = count( $tokens );
 for ( $i = 0; $i < $n; $i++ ) {
  if ( ! is_array( $tokens[$i] ) || T_FUNCTION !== $tokens[$i][0] ) { continue; }
  $j = $i + 1; while ( is_array( $tokens[$j] ) && T_WHITESPACE === $tokens[$j][0] ) { $j++; }
  if ( ! is_array( $tokens[$j] ) || $name !== $tokens[$j][1] ) { continue; }
  $code = ''; $depth = 0; $opened = false;
  for ( ; $i < $n; $i++ ) { $t = $tokens[$i]; $code .= is_array( $t ) ? $t[1] : $t; if ( '{' === $t ) { $depth++; $opened = true; } if ( '}' === $t && --$depth === 0 && $opened ) { eval( $code ); return; } }
 }
 throw new RuntimeException( 'missing_import:' . $name );
}
$imports = array(
 'core' => array( 'wcj_get_option' ),
 'booster-core' => array( 'wcj_is_rest', 'wcj_check_modules_by_user_roles', 'wcj_is_module_enabled', 'wcj_is_store_api_request' ),
 'users' => array( 'wcj_is_user_role', 'wcj_get_current_user_all_roles', 'wcj_is_user_logged_in', 'wcj_is_booster_role_changer_enabled', 'wcj_get_current_user_first_role' ),
 'admin' => array( 'wcj_maybe_convert_string_to_array', 'wcj_convert_string_to_array' ),
 'products' => array( 'wcj_get_product_terms' ),
 'general' => array( 'wcj_check_postcode' ),
 'country' => array( 'wcj_maybe_add_european_union_countries' ),
);
foreach ( $imports as $file => $names ) { foreach ( $names as $name ) { import_function( $root . '/includes/functions/wcj-functions-' . $file . '.php', $name ); } }
if ( 'elite' === 'free' ) { foreach ( array( 'wcj_get_enabled_module_allowlist', 'wcj_is_module_allowed_by_allowlist' ) as $name ) { import_function( $root . '/includes/functions/wcj-functions-booster-core.php', $name ); } }
set_error_handler( function( $level, $message ) { $GLOBALS['errors'][] = array( $level, $message ); return true; } );
require $root . '/includes/class-wcj-rule-explanations.php';
$owners = WCJ_Rule_Explanations::payment_owners();
foreach ( $owners as $id => $spec ) { $opts[ 'wcj_' . $id . '_enabled' ] = 'yes'; }
$legacy = array();
foreach ( $owners as $id => $spec ) {
 $file = 'class-wcj-' . str_replace( '_', '-', $id ) . '.php';
 $code = file_get_contents( $baseline . '/includes/' . $file );
 $code = str_replace( $spec[0], 'Legacy_' . $spec[0], $code );
 $legacy[$id] = eval( '?>' . $code );
 $plugin->all_modules[$id] = require $root . '/includes/' . $file;
}
$service = new WCJ_Rule_Explanations();
require $root . '/includes/class-wcj-status-service.php';
require $root . '/includes/class-wcj-abilities.php';
$window = new ReflectionProperty( 'WCJ_Rule_Explanations', 'payment_window' ); $window->setAccessible( true );
function eq( $name, $actual, $expected ) { if ( $actual !== $expected ) { throw new RuntimeException( 'mismatch:' . $name . ':' . json_encode( array( 'actual' => $actual, 'expected' => $expected ) ) ); } $GLOBALS['records'][] = array( 'case' => $name, 'actual' => $actual ); }
function fresh( $extra = array() ) { $GLOBALS['opts'] = $extra; foreach ( $GLOBALS['owners'] as $id => $s ) { $GLOBALS['opts']['wcj_' . $id . '_enabled'] = 'yes'; } $GLOBALS['plugin']->options = array(); $GLOBALS['trace'] = array(); $GLOBALS['errors'] = array(); }
function open_window( $selected = 'cod' ) { $GLOBALS['window']->setValue( null, array( 'state' => 'open', 'actor' => 7, 'blog' => 3, 'gateway' => $selected, 'nonce' => wp_create_nonce( 'wcj_payment_observe:3:7:' . $selected ), 'counts' => array(), 'rows' => array(), 'entry' => array(), 'renders' => 0, 'modules' => $GLOBALS['plugin']->all_modules ) ); }
function observe( $id, $gateways ) { open_window(); $spec = $GLOBALS['owners'][$id]; $m = $GLOBALS['plugin']->all_modules[$id]; $m->{$spec[1]}( $gateways ); $w = $GLOBALS['window']->getValue(); $w['state'] = 'closed'; $GLOBALS['window']->setValue( null, $w ); return $GLOBALS['service']->get_payment_observation()['rows'][array_search( $id, array_keys( $GLOBALS['owners'] ), true )]['reason']; }
$gateway = (object) array( 'title' => 'Cash', 'id' => 'cod' ); $gateways = array( 'cod' => $gateway );
// Rendering boundary stubs are limited to actual admin entry-point exercise.
function esc_attr( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function selected( $a, $b, $echo = true ) { $s = (string) $a === (string) $b ? 'selected="selected"' : ''; if ( $echo ) { echo $s; } return $s; }
function wp_nonce_field( $a, $name = '_wpnonce' ) { echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( wp_create_nonce( $a ) ) . '">'; }
function wp_die( $s ) { throw new RuntimeException( 'wp_die:' . $s ); }
