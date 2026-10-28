<?php
/** Source-only actual-method regression. No WordPress/WooCommerce installation is bootstrapped. */
if ( PHP_SAPI !== 'cli' || empty( $argv[1] ) ) { exit( 2 ); }
$root = realpath( $argv[1] );
$tier = isset( $argv[2] ) ? $argv[2] : 'free';
$mode = isset( $argv[3] ) ? $argv[3] : 'verify';
if ( ! $root || ! in_array( $tier, array( 'free', 'plus', 'elite' ), true ) ) { exit( 2 ); }
define( 'ABSPATH', __DIR__ . '/' );
if ( 'free' === $tier ) { define( 'WCJ_FREE_PLUGIN_FILE', 'woocommerce-jetpack.php' ); }
else { define( 'WCJ_PLUGIN_FILE', 'booster-' . $tier . '-for-woocommerce.php' ); }
$GLOBALS['options'] = array();
$GLOBALS['allowed'] = true;
$GLOBALS['reads'] = 0;
$GLOBALS['writes'] = 0;
$GLOBALS['shortcodes'] = 0;
$GLOBALS['shortcode_callbacks'] = 0;
$GLOBALS['user_reads'] = 0;
$GLOBALS['wc_reads'] = 0;
$GLOBALS['fee_api_calls'] = 0;
$GLOBALS['registered'] = array();
$GLOBALS['hooks'] = array();
$GLOBALS['role'] = 'guest';
class WP_Error { public $code; public $message; public function __construct( $code, $message = '' ) { $this->code = $code; $this->message = $message; } public function get_error_code() { return $this->code; } public function get_error_message() { return $this->message; } }
function is_wp_error( $v ) { return $v instanceof WP_Error; }
function __( $s, $d = '' ) { return $s; }
function esc_html__( $s, $d = '' ) { return htmlspecialchars( $s, ENT_QUOTES ); }
function esc_html( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function esc_attr( $s ) { return esc_html( $s ); }
function esc_url( $s ) { return esc_html( $s ); }
function sanitize_key( $s ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $s ) ); }
function sanitize_text_field( $s ) { return strip_tags( $s ); }
function wp_unslash( $v ) { return $v; }
function wp_json_encode( $v ) { return json_encode( $v ); }
function add_action( $h, $cb, $priority = 10, $args = 1 ) { $GLOBALS['hooks'][$h][] = $cb; }
function current_filter() { return ''; }
function wcj_get_option( $k, $d = false ) { ++$GLOBALS['reads']; if ( 'wcj_gateways_fees_text' === $k && ! empty( $GLOBALS['alternating_titles'] ) ) { ++$GLOBALS['title_reads']; return array( 'cod' => 1 === $GLOBALS['title_reads'] ? 'Literal cached title' : '[title]' ); } if ( 'wcj_gateways_fees_guest' === $k && ! empty( $GLOBALS['alternating_role_values'] ) ) { ++$GLOBALS['role_reads']; return array( 'cod' => 1 === $GLOBALS['role_reads'] ? 4 : 'not numeric' ); } return array_key_exists( $k, $GLOBALS['options'] ) ? $GLOBALS['options'][$k] : $d; }
function apply_filters( $h, $v, ...$args ) { return 'booster_option' === $h && 'free' !== $GLOBALS['tier'] ? $args[0] : $v; }
if ( 'native-shortcode' !== $mode ) {
 function do_shortcode( $s ) { ++$GLOBALS['shortcodes']; if ( false !== strpos( $s, '[title]' ) || false !== strpos( $s, '[empty]' ) ) { ++$GLOBALS['shortcode_callbacks']; } return '[empty]' === $s ? '' : str_replace( '[title]', 'Resolved title', $s ); }
} else {
 if ( empty( $argv[5] ) || ! is_file( $argv[5] ) ) { exit( 2 ); }
 function has_filter( ...$a ) { return false; }
 function add_filter( ...$a ) { return true; }
 function remove_filter( ...$a ) { return true; }
 function wp_html_split( $s ) { return array( $s ); }
 require $argv[5];
 add_shortcode( 'title', function () { ++$GLOBALS['shortcode_callbacks']; return 'Resolved title'; } );
 add_shortcode( 'empty', function () { ++$GLOBALS['shortcode_callbacks']; return ''; } );
}
function is_user_logged_in() { return 'guest' !== $GLOBALS['role']; }
function wp_get_current_user() { ++$GLOBALS['user_reads']; return (object) array( 'roles' => 'roleless' === $GLOBALS['role'] ? array() : array( $GLOBALS['role'] ) ); }
function current_user_can( $cap ) { return $GLOBALS['allowed']; }
function wp_roles() { return (object) array( 'roles' => array( 'customer' => array( 'name' => 'Customer' ), 'shop_manager' => array( 'name' => 'Shop manager' ) ) ); }
function get_woocommerce_currency() { return 'USD'; }
function is_cart() { return false; }
function is_checkout() { return false; }
function add_submenu_page( ...$a ) { $GLOBALS['menu'] = $a; }
function admin_url( $s = '' ) { return 'https://example.invalid/wp-admin/' . $s; }
function wp_nonce_field( ...$a ) { echo '<input name="_wpnonce" value="synthetic">'; }
function wp_verify_nonce( $nonce, $action ) { return 'synthetic' === $nonce; }
function wp_die( $s ) { throw new RuntimeException( 'denied' ); }
function selected( $a, $b, $echo = true ) { $s = $a === $b ? 'selected' : ''; if ( $echo ) { echo $s; } return $s; }
function update_option( ...$a ) { ++$GLOBALS['writes']; throw new RuntimeException( 'business write' ); }
function delete_option( ...$a ) { ++$GLOBALS['writes']; throw new RuntimeException( 'business write' ); }
function set_transient( ...$a ) { ++$GLOBALS['writes']; throw new RuntimeException( 'business write' ); }
if ( 'no-api' !== $mode ) {
 function wp_register_ability( $name, $args ) { $GLOBALS['registered'][$name] = $args; }
}
class WCJ_Module { public $id; public $short_desc; public $desc; public $desc_pro; public $link_slug; public $options = array(); public function __construct() {} public function is_enabled() { return ! empty( $GLOBALS['module_enabled'] ); } }
class WC_Tax { public static function get_tax_classes() { return array( 'Reduced rate', 'Zero rate' ); } }
class TestCurrency { public $enabled = false; public $calls = 0; public $checks = 0; public $flip_enabled = false; public function is_enabled() { ++$this->checks; return $this->flip_enabled ? $this->checks > 1 : $this->enabled; } public function change_price( $v, $p ) { ++$this->calls; return $v * 2; } }
class TestSession { public $gateway = 'cod'; public function get( $k ) { return $this->gateway; } public function set( ...$a ) { ++$GLOBALS['writes']; throw new RuntimeException( 'session write' ); } }
class WC_Cart { public $subtotal = 100; public $shipping = 10; public $tax = 5; public $shipping_tax = 1; public $items = array(); public $fees = array(); public $duplicate = false; public function get_cart_contents_total() { return $this->subtotal; } public function get_shipping_total() { return $this->shipping; } public function get_subtotal_tax() { return $this->tax; } public function get_shipping_tax() { return $this->shipping_tax; } public function get_cart() { return $this->items; } public function fees_api() { ++$GLOBALS['fee_api_calls']; return $this; } public function add_fee( $v ) { $this->fees[] = $v; return $this->duplicate ? new WP_Error( 'duplicate_fee' ) : (object) $v; } }
class ThrowingFeeCart extends WC_Cart { public function get_cart_contents_total() { throw new RuntimeException( 'synthetic incomplete callback' ); } }

// Core-source replay primitives are synthetic; no REST server/bootstrap is run.
function wp_parse_args( $args, $defaults = array() ) { return array_merge( $defaults, (array) $args ); }
function wp_is_numeric_array( $value ) { return is_array( $value ) && ( array() === $value || array_keys( $value ) === range( 0, count( $value ) - 1 ) ); }
function do_action( ...$args ) {}
function _doing_it_wrong( ...$args ) {}
if ( ! function_exists( 'mb_strlen' ) ) { function mb_strlen( $value, $encoding = null ) { return strlen( $value ); } }
class WP_REST_Controller {}
class WCJ_Rule_Test_Query { private $query; public function __construct( $query ) { $this->query = $query; } public function get_method() { return 'GET'; } public function get_query_params() { return $this->query; } }
$GLOBALS['tier'] = $tier;
$GLOBALS['plugin'] = (object) array( 'all_modules' => array( 'multicurrency' => new TestCurrency() ) );
$GLOBALS['wc'] = (object) array( 'cart' => null, 'session' => new TestSession() );
function WCJ() { return $GLOBALS['plugin']; }
function w_c_j() { return $GLOBALS['plugin']; }
function WC() { ++$GLOBALS['wc_reads']; return $GLOBALS['wc']; }
if ( 'old-producer' === $mode ) {
 class WCJ_Payment_Gateways_Fees extends WCJ_Module {}
 $GLOBALS['plugin']->all_modules['payment_gateways_fees'] = new WCJ_Payment_Gateways_Fees();
 require $root . '/includes/class-wcj-rule-explanations.php';
 $r = ( new WCJ_Rule_Explanations() )->explain( array( 'mode' => 'scenario', 'gateway' => 'cod', 'subtotal' => 1, 'shipping' => 0, 'tax' => 0, 'shipping_tax' => 0 ) );
 $ok = 'unavailable' === $r['state'] && 'producer_unavailable' === $r['reason'];
 echo json_encode( array( 'tier' => $tier, 'mode' => $mode, 'pass' => $ok ? 1 : 0, 'failures' => $ok ? array() : array( 'old-producer-method-guard' ) ) ) . "\n"; exit( $ok ? 0 : 1 );
}
if ( 'lazy-producer' === $mode ) {
 require $root . '/includes/class-wcj-rule-explanations.php';
 $r = ( new WCJ_Rule_Explanations() )->explain( array( 'mode' => 'request_local' ) );
 if ( 'producer_unavailable' !== $r['reason'] ) { exit( 1 ); }
}
$module = require $root . '/includes/class-wcj-payment-gateways-fees.php';
class FlipTitleFeeModule extends WCJ_Payment_Gateways_Fees { public $title_reads = 0; public function wcj_get_option( $option, $key, $default = false ) { if ( 'text' === $option ) { ++$this->title_reads; return 1 === $this->title_reads ? 'Literal title' : '[title]'; } return parent::wcj_get_option( $option, $key, $default ); } }
class ThrowingScenarioModule extends WCJ_Payment_Gateways_Fees { public function evaluate_gateway_fee( $gateway, $cart, $role = null, $scenario = false ) { throw new TypeError( 'synthetic-secret-option-value' ); } }
$GLOBALS['plugin']->all_modules['payment_gateways_fees'] = $module;
function setup_case( $c, $module ) {
 $GLOBALS['options'] = array( 'wcj_gateways_fees_text' => array( 'cod' => 'Gateway fee' ), 'wcj_gateways_fees_value' => array( 'cod' => 5 ) );
 foreach ( isset( $c['options'] ) ? $c['options'] : array() as $k => $v ) { $GLOBALS['options'][$k] = $v; }
 $cart = new WC_Cart();
 $cart->items = array( 'line' => array( 'product_id' => 12, 'variation_id' => 13, 'quantity' => 2 ) );
 foreach ( isset( $c['cart'] ) ? $c['cart'] : array() as $k => $v ) { $cart->$k = $v; }
 $GLOBALS['wc']->cart = $cart;
 $GLOBALS['wc']->session = ! empty( $c['missing_session'] ) ? null : new TestSession();
 if ( $GLOBALS['wc']->session ) { $GLOBALS['wc']->session->gateway = isset( $c['gateway'] ) ? $c['gateway'] : 'cod'; }
 $GLOBALS['role'] = isset( $c['role'] ) ? $c['role'] : 'guest';
 $GLOBALS['plugin']->all_modules['multicurrency']->enabled = ! empty( $c['currency'] );
 $module->init_options();
 return $cart;
}
$cases = array(
 'fixed' => array(), 'missing_session' => array( 'missing_session' => true ),
 'no_gateway' => array( 'gateway' => '' ), 'unknown_gateway' => array( 'gateway' => 'unknown' ),
 'empty_title' => array( 'options' => array( 'wcj_gateways_fees_text' => array( 'cod' => '' ) ) ),
 'shortcode_empty' => array( 'options' => array( 'wcj_gateways_fees_text' => array( 'cod' => '[empty]' ) ) ),
 'shortcode_title' => array( 'options' => array( 'wcj_gateways_fees_text' => array( 'cod' => '[title]' ) ) ),
 'duplicate' => array( 'cart' => array( 'duplicate' => true ) ),
 'role_override' => array( 'role' => 'customer', 'options' => array( 'wcj_enable_payment_gateway_charge_discount_userwise' => array( 'cod' => 'yes' ), 'wcj_gateways_fees_customer' => array( 'cod' => -7 ) ) ),
 'role_zero_fallback' => array( 'role' => 'customer', 'options' => array( 'wcj_enable_payment_gateway_charge_discount_userwise' => array( 'cod' => 'yes' ), 'wcj_gateways_fees_customer' => array( 'cod' => '0' ) ) ),
 'roleless' => array( 'role' => 'roleless', 'options' => array( 'wcj_enable_payment_gateway_charge_discount_userwise' => array( 'cod' => 'yes' ), 'wcj_gateways_fees_guest' => array( 'cod' => 4 ) ) ),
 'include_match' => array( 'options' => array( 'wcj_gateways_fees_include_products' => array( 'cod' => array( 13 ) ) ) ),
 'include_miss' => array( 'options' => array( 'wcj_gateways_fees_include_products' => array( 'cod' => array( 99 ) ) ) ),
 'exclude_match' => array( 'options' => array( 'wcj_gateways_fees_exclude_products' => array( 'cod' => array( 12 ) ) ) ),
 'exclude_miss' => array( 'options' => array( 'wcj_gateways_fees_exclude_products' => array( 'cod' => array( 99 ) ) ) ),
 'invalid_type' => array( 'options' => array( 'wcj_gateways_fees_type' => array( 'cod' => 'invalid' ) ) ),
 'klarna_alias' => array( 'gateway' => 'klarna_x', 'options' => array( 'wcj_enable_payment_gateway_charge_discount' => 'yes', 'wcj_gateways_fees_text' => array( 'klarna_payments' => 'Klarna fee' ), 'wcj_gateways_fees_value' => array( 'klarna_payments' => 3 ) ) ),
);
foreach ( array( 'fixed', 'percent' ) as $type ) { foreach ( array( -12.345, 0, 7.891 ) as $value ) { foreach ( array( false, true ) as $currency ) { foreach ( array( 'no', 'yes' ) as $round ) {
 $cases["calc-$type-$value-$currency-$round"] = array( 'currency' => $currency, 'options' => array( 'wcj_gateways_fees_type' => array( 'cod' => $type ), 'wcj_gateways_fees_value' => array( 'cod' => $value ), 'wcj_gateways_fees_round' => array( 'cod' => $round ), 'wcj_gateways_fees_round_precision' => array( 'cod' => 1 ), 'wcj_gateways_fees_is_taxable' => array( 'cod' => 'yes' ), 'wcj_gateways_fees_tax_class_id' => array( 'cod' => 1 ) ) );
} } } }
foreach ( array( 'no', 'yes' ) as $shipping ) { foreach ( array( 'no', 'yes' ) as $tax ) { foreach ( array( 0, 100, 110, 116, 117 ) as $min ) { foreach ( array( 0, 105, 116 ) as $max ) {
 $cases["bounds-$shipping-$tax-$min-$max"] = array( 'options' => array( 'wcj_gateways_fees_type' => array( 'cod' => 'percent' ), 'wcj_gateways_fees_exclude_shipping' => array( 'cod' => $shipping ), 'wcj_gateways_fees_include_taxes' => array( 'cod' => $tax ), 'wcj_gateways_fees_min_cart_amount' => array( 'cod' => $min ), 'wcj_gateways_fees_max_cart_amount' => array( 'cod' => $max ) ) );
} } } }
$outputs = array();
foreach ( $cases as $name => $case ) {
 $cart = setup_case( $case, $module );
 $module->gateways_fees( $cart );
 $d = $module->get_last_fee_decision();
 $outputs[$name] = array( 'fees' => $cart->fees, 'gateway' => $d['gateway'], 'reason' => $d['reason'] );
}
if ( 'capture' === $mode ) { echo json_encode( array( 'tier' => $tier, 'outputs' => $outputs ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION ) . "\n"; exit( 0 ); }
$pass = 0; $fail = array(); $assertions = array();
function check( $ok, $name ) { $GLOBALS['assertions'][] = array( 'name' => $name, 'pass' => (bool) $ok ); if ( $ok ) { ++$GLOBALS['pass']; } else { $GLOBALS['fail'][] = $name; } }
$golden_file = isset( $argv[4] ) ? $argv[4] : $root . '/tests/fixtures/codex-9-0-rule-explanations-' . $tier . '.json';
$golden = is_file( $golden_file ) ? json_decode( file_get_contents( $golden_file ), true ) : array();
foreach ( $outputs as $name => $out ) { check( isset( $golden['outputs'][$name] ) && $out === $golden['outputs'][$name], 'legacy-payload-' . $name ); }
$feature = $root . '/includes/class-wcj-rule-explanations.php';
check( is_file( $feature ), 'feature-service-exists' );
if ( is_file( $feature ) ) {
 require $feature;
 require $root . '/includes/class-wcj-rule-explanations-admin.php';
 require $root . '/includes/class-wcj-status-service.php';
 require $root . '/includes/class-wcj-abilities.php';
 $GLOBALS['module_enabled'] = true;
 $service = new WCJ_Rule_Explanations();
 $input = array( 'mode' => 'scenario', 'gateway' => 'cod', 'subtotal' => 100, 'shipping' => 10, 'tax' => 5, 'shipping_tax' => 1 );
 setup_case( array(), $module );
 $before = serialize( array( $GLOBALS['options'], $GLOBALS['wc'], $module->get_last_fee_decision() ) );
 $spies = array( $GLOBALS['user_reads'], $GLOBALS['wc_reads'], $GLOBALS['fee_api_calls'], $GLOBALS['shortcode_callbacks'], $module->get_performance_counters() );
 $response = $service->explain( $input );
 check( ! is_wp_error( $response ) && 'evaluated' === $response['state'] && 5.0 === $response['amount'] && 'eligible' === $response['reason'], 'scenario-actual-fixed' );
 check( $before === serialize( array( $GLOBALS['options'], $GLOBALS['wc'], $module->get_last_fee_decision() ) ), 'scenario-does-not-mutate-business-or-live-decision' );
 check( $spies === array( $GLOBALS['user_reads'], $GLOBALS['wc_reads'], $GLOBALS['fee_api_calls'], $GLOBALS['shortcode_callbacks'], $module->get_performance_counters() ), 'scenario-no-shopper-user-cart-fee-api-shortcode-callback-or-counter-mutation' );
 check( 'elite' === $tier ? count( $response['conditions'] ) > 0 : array() === $response['conditions'], 'detail-entitlement' );
 // Compare all supported scenarios to actual live evaluations, not copied arithmetic.
 foreach ( $cases as $name => $case ) {
  if ( ! empty( $case['missing_session'] ) || ( isset( $case['gateway'] ) && 'cod' !== $case['gateway'] && false === strpos( $case['gateway'], 'klarna' ) ) || ( 'elite' !== $tier && isset( $case['role'] ) && ! in_array( $case['role'], array( 'guest', 'roleless' ), true ) ) ) { continue; }
  $cart = setup_case( $case, $module ); $module->gateways_fees( $cart ); $live = $module->get_last_fee_decision();
  $scenario = array( 'mode' => 'scenario', 'gateway' => isset( $case['gateway'] ) ? $case['gateway'] : 'cod', 'subtotal' => $cart->subtotal, 'shipping' => $cart->shipping, 'tax' => $cart->tax, 'shipping_tax' => $cart->shipping_tax );
  if ( 'elite' === $tier ) { $scenario['product_ids'] = array( 12, 13 ); $scenario['role'] = 'roleless' === $GLOBALS['role'] ? 'guest' : $GLOBALS['role']; }
  $identity = serialize( array( $GLOBALS['wc'], $GLOBALS['options'], $module ) );
  $r = $service->explain( $scenario );
  if ( ! empty( $case['currency'] ) || false !== strpos( $module->wcj_get_option( 'text', $live['gateway'] ), '[' ) || 'invalid' === $module->wcj_get_option( 'type', $live['gateway'] ) || ( 'elite' !== $tier && ( $module->wcj_get_option( 'include_products', $live['gateway'] ) || $module->wcj_get_option( 'exclude_products', $live['gateway'] ) ) ) ) {
   check( ! is_wp_error( $r ) && 'unsupported' === $r['state'], 'scenario-unsupported-' . $name );
  } else {
   $reason = in_array( $live['reason'], array( 'applied', 'duplicate_suppressed' ), true ) ? 'eligible' : $live['reason'];
   check( ! is_wp_error( $r ) && $reason === $r['reason'] && ( null === $live['amount'] ? null === $r['amount'] : (float) $live['amount'] === $r['amount'] ), 'scenario-live-parity-' . $name );
  }
  check( $identity === serialize( array( $GLOBALS['wc'], $GLOBALS['options'], $module ) ), 'scenario-clone-business-cache-isolation-' . $name );
 }

 // R1 A-1: same shared Klarna resolution before configuration lookup.
 foreach ( array( false, true ) as $alias_enabled ) {
  $cart = setup_case( array( 'gateway' => 'klarna_x', 'options' => array( 'wcj_enable_payment_gateway_charge_discount' => $alias_enabled ? 'yes' : 'no', 'wcj_gateways_fees_text' => array( 'klarna_x' => 'Ignored alias fee', 'klarna_payments' => 'Resolved fee' ), 'wcj_gateways_fees_value' => array( 'klarna_x' => 91, 'klarna_payments' => 3 ) ) ), $module );
  $module->gateways_fees( $cart ); $live = $module->get_last_fee_decision();
  $identity = serialize( array( $GLOBALS['wc'], $GLOBALS['options'], $module ) );
  $r = $service->explain( array_merge( $input, array( 'gateway' => 'klarna_x' ) ) );
  check( ! is_wp_error( $r ) && $r['gateway'] === $live['gateway'] && (float) $live['amount'] === $r['amount'] && ( $alias_enabled ? 3.0 : 91.0 ) === $r['amount'], 'klarna-conflicting-alias-configuration-parity-' . (int) $alias_enabled );
  check( $identity === serialize( array( $GLOBALS['wc'], $GLOBALS['options'], $module ) ), 'klarna-resolution-no-business-mutation-' . (int) $alias_enabled );
 }
 // R1 A-2: global activation cannot be hidden by request-local role gating.
 setup_case( array( 'options' => array( 'wcj_multicurrency_enabled' => 'yes' ) ), $module );
 $currency = $GLOBALS['plugin']->all_modules['multicurrency']; $currency->enabled = false;
 $calls = $currency->calls; $snapshot = serialize( array( $GLOBALS['wc'], $module ) );
 $r = $service->explain( $input );
 check( 'unsupported' === $r['state'] && 'multicurrency_context' === $r['reason'] && $calls === $currency->calls, 'global-multicurrency-refused-when-role-gated-module-disabled' );
 check( $snapshot === serialize( array( $GLOBALS['wc'], $module ) ), 'global-multicurrency-refusal-no-live-cart-or-decision-mutation' );
 $clone = clone $module; $clone->init_options();
 $r = $clone->evaluate_gateway_fee( 'cod', new WCJ_Rule_Explanation_Context( $input ), 'guest', true );
 check( 'multicurrency_context' === $r['reason'] && ! isset( $r['fee'] ) && $calls === $currency->calls, 'evaluator-rechecks-global-multicurrency-without-conversion' );
 unset( $GLOBALS['plugin']->all_modules['multicurrency'] );
 $r = $service->explain( $input );
 check( 'multicurrency_context' === $r['reason'], 'globally-enabled-multicurrency-refused-even-without-runtime-instance' );
 $GLOBALS['plugin']->all_modules['multicurrency'] = $currency;
 // R1 A-3: validate surviving GET strings before converting, after authorization.
 setup_case( array(), $module );
 foreach ( array( '100', '000100.25', '0', '1000000000', '0000000000000000000000001' ) as $value ) {
  $numeric = $service->explain( array_merge( $input, array( 'subtotal' => (float) $value ) ) );
  $string = $service->explain( array_merge( $input, array( 'subtotal' => $value ) ) );
  check( ! is_wp_error( $string ) && $numeric === $string, 'strict-decimal-string-parity-' . $value );
 }
 foreach ( array( '1e3', ' 100', '100 ', '0x10', '+100', '-1', '.5', '5.', '', 'NaN', 'INF', "1\n", str_repeat( '0', 26 ), '1000000001', true, array( 100 ), NAN ) as $i => $value ) {
  $reads = $GLOBALS['reads']; $r = $service->explain( array_merge( $input, array( 'subtotal' => $value ) ) );
  check( is_wp_error( $r ) && $reads === $GLOBALS['reads'], 'malformed-decimal-before-option-read-' . $i );
 }
 foreach ( array( 'subtotal', 'shipping', 'tax', 'shipping_tax' ) as $key ) {
  $numeric = $service->explain( array_merge( $input, array( $key => 0.0 ) ) );
  check( $numeric === $service->explain( array_merge( $input, array( $key => '0' ) ) ), 'all-monetary-getters-normalize-zero-' . $key );
 }
 if ( 'elite' === $tier ) {
  $numeric = $service->explain( array_merge( $input, array( 'role' => 'guest', 'product_ids' => array( 12, 13 ) ) ) );
  check( $numeric === $service->explain( array_merge( $input, array( 'role' => 'guest', 'product_ids' => array( '12', '00013' ) ) ) ), 'strict-product-id-string-parity' );
  foreach ( array( '1e1', ' 12', '12 ', '0x10', '+12', '12.0', '0', '2147483648', str_repeat( '0', 26 ), true, 12.0 ) as $i => $id ) {
   $reads = $GLOBALS['reads']; $r = $service->explain( array_merge( $input, array( 'product_ids' => array( $id ) ) ) );
   check( is_wp_error( $r ) && $reads === $GLOBALS['reads'], 'malformed-product-id-before-option-read-' . $i );
  }
 }
 $reads = $GLOBALS['reads']; $GLOBALS['allowed'] = false;
 check( is_wp_error( $service->explain( array_merge( $input, array( 'subtotal' => '100' ) ) ) ) && $reads === $GLOBALS['reads'], 'decimal-string-denied-before-option-reads' );
 $GLOBALS['allowed'] = true;
 setup_case( array(), $module );
 setup_case( array(), $module );
 $GLOBALS['title_reads'] = 0; $GLOBALS['alternating_titles'] = true;
 $callbacks = $GLOBALS['shortcode_callbacks']; $r = $service->explain( $input );
 check( 'eligible' === $r['reason'] && 1 === $GLOBALS['title_reads'] && $callbacks === $GLOBALS['shortcode_callbacks'], 'literal-title-option-filter-canary-no-shortcode-callback' );
 $GLOBALS['alternating_titles'] = false;
 $flip = new FlipTitleFeeModule(); $GLOBALS['plugin']->all_modules['payment_gateways_fees'] = $flip;
 $callbacks = $GLOBALS['shortcode_callbacks']; $r = $service->explain( $input );
 check( 'unsupported' === $r['state'] && 'dynamic_title' === $r['reason'] && $callbacks === $GLOBALS['shortcode_callbacks'] && 0 === $flip->title_reads, 'dynamic-getter-refused-after-preflight-clone-isolated' );
 $GLOBALS['plugin']->all_modules['payment_gateways_fees'] = $module;
 $currency = $GLOBALS['plugin']->all_modules['multicurrency']; $currency->checks = 0; $currency->flip_enabled = true;
 $conversions = $currency->calls; $r = $service->explain( $input );
 check( 'unsupported' === $r['state'] && 'multicurrency_context' === $r['reason'] && $conversions === $currency->calls, 'dynamic-currency-enablement-no-conversion-after-preflight' );
 $currency->flip_enabled = false;
 setup_case( array( 'options' => array( 'wcj_enable_payment_gateway_charge_discount_userwise' => array( 'cod' => 'yes' ) ) ), $module );
 $GLOBALS['role_reads'] = 0; $GLOBALS['alternating_role_values'] = true;
 $r = $service->explain( 'elite' === $tier ? array_merge( $input, array( 'role' => 'guest' ) ) : $input );
 check( 'unsupported' === $r['state'] && 'invalid_configuration' === $r['reason'] && 2 === $GLOBALS['role_reads'], 'dynamic-role-value-invalid-after-preflight-no-arithmetic' );
 $GLOBALS['alternating_role_values'] = false;
 setup_case( array(), $module );
 $read_before = $GLOBALS['reads']; $GLOBALS['allowed'] = false;
 check( is_wp_error( $service->explain( $input ) ) && $read_before === $GLOBALS['reads'], 'denied-before-option-read' );
 check( is_wp_error( $service->get_scenario_choices() ) && $read_before === $GLOBALS['reads'], 'denied-choice-list-before-option-read' );
 $ability = new WCJ_Abilities();
 check( is_wp_error( $ability->execute_rule_explanation( $input ) ), 'ability-direct-denial' );
 $GLOBALS['allowed'] = true;
 $ability->register_abilities();
 check( 'no-api' === $mode ? ! isset( $GLOBALS['registered']['booster/explain-gateway-fee'] ) : isset( $GLOBALS['registered']['booster/explain-gateway-fee'] ), 'ability-api-presence' );
 check( ! is_wp_error( $ability->execute_rule_explanation( $input ) ), 'ability-direct-allowed' );
 if ( 'no-api' !== $mode ) { $registered = $GLOBALS['registered']['booster/explain-gateway-fee']; check( true === $registered['meta']['annotations']['readonly'] && false === $registered['meta']['annotations']['destructive'] && false === $registered['input_schema']['additionalProperties'] && ( 'elite' === $tier ) === isset( $registered['input_schema']['properties']['role'] ), 'ability-strict-readonly-tier-schema' ); }
 $ability->check_permission(); $GLOBALS['allowed'] = false;
 check( is_wp_error( $ability->execute_rule_explanation( $input ) ), 'ability-demotion-rechecked' );
 $GLOBALS['allowed'] = true;
 foreach ( array( array( 'subtotal' => -1 ), array( 'subtotal' => INF ), array( 'subtotal' => '1e3' ), array( 'gateway' => '../secret' ), array( 'unknown' => true ), array( 'mode' => 'wrong' ), array( 'subtotal' => 1000000001 ), array( 'tax' => null ) ) as $bad ) {
  check( is_wp_error( $service->explain( array_merge( $input, $bad ) ) ), 'invalid-input-' . json_encode( array_keys( $bad ) ) );
 }
 setup_case( array( 'options' => array( 'wcj_gateways_fees_text' => array( 'cod' => '[title]' ) ) ), $module );
 $calls = $GLOBALS['shortcodes']; $r = $service->explain( $input );
 check( 'unsupported' === $r['state'] && 'dynamic_title' === $r['reason'] && $calls === $GLOBALS['shortcodes'], 'unsupported-shortcode-before-evaluator' );
 setup_case( array( 'currency' => true ), $module );
 $calls = $GLOBALS['plugin']->all_modules['multicurrency']->calls; $r = $service->explain( $input );
 check( 'unsupported' === $r['state'] && 'multicurrency_context' === $r['reason'] && $calls === $GLOBALS['plugin']->all_modules['multicurrency']->calls, 'unsupported-currency-no-conversion' );
 setup_case( array(), $module );
 $advanced = array_merge( $input, array( 'product_ids' => array( 12, 13 ), 'role' => 'customer' ) );
 check( 'elite' === $tier ? ! is_wp_error( $service->explain( $advanced ) ) : is_wp_error( $service->explain( $advanced ) ), 'advanced-input-entitlement' );
 foreach ( array( 'text', 'value' ) as $bad_role ) { check( is_wp_error( $service->explain( array_merge( $input, array( 'role' => $bad_role ) ) ) ), 'unregistered-role-name-denied-' . $bad_role ); }
 setup_case( array( 'options' => array( 'wcj_enable_payment_gateway_charge_discount_userwise' => array( 'cod' => 'yes' ), 'wcj_gateways_fees_guest' => array( 'cod' => -4 ) ) ), $module );
 $r = $service->explain( $input );
 check( 'elite' === $tier ? 'role_fact_required' === $r['reason'] : -4.0 === $r['amount'], 'conditional-role-omission-versus-explicit-basic-guest' );
 setup_case( array( 'options' => array( 'wcj_gateways_fees_include_products' => array( 'cod' => array( 12 ) ) ) ), $module );
 $r = $service->explain( $input );
 check( 'elite' === $tier ? 'product_fact_required' === $r['reason'] : ( 'plus' === $tier ? 'advanced_product_context' === $r['reason'] : 'eligible' === $r['reason'] ), 'conditional-product-omission-tier-rights' );
 if ( 'elite' === $tier ) { $r = $service->explain( array_merge( $input, array( 'product_ids' => array() ) ) ); check( 'product_condition_not_met' === $r['reason'], 'explicit-empty-products-is-known-not-omitted' ); }
 // Exactly-empty GET array compatibility does not enable advanced facts in basic editions.
 $empty_product_input = array_merge( $input, array( 'product_ids' => '' ) );
 $reads = $GLOBALS['reads']; $identity = serialize( array( $GLOBALS['wc'], $GLOBALS['options'], $module ) );
 $r = $service->explain( $empty_product_input );
 check( 'elite' === $tier ? ! is_wp_error( $r ) && 'product_condition_not_met' === $r['reason'] : is_wp_error( $r ) && $reads === $GLOBALS['reads'], 'explicit-empty-product-string-tier-boundary' );
 if ( 'elite' === $tier ) { check( $r === $service->explain( array_merge( $input, array( 'product_ids' => array() ) ) ), 'explicit-empty-product-string-array-parity' ); }
 check( $identity === serialize( array( $GLOBALS['wc'], $GLOBALS['options'], $module ) ), 'explicit-empty-product-string-business-cache-isolation' );
 foreach ( array( ' ', '12,13', '0', false, null, 12 ) as $i => $value ) {
  $reads = $GLOBALS['reads']; $r = $service->explain( array_merge( $input, array( 'product_ids' => $value ) ) );
  check( is_wp_error( $r ) && $reads === $GLOBALS['reads'], 'nonempty-or-nonstring-product-list-denied-before-options-' . $i );
 }
 check( is_wp_error( $service->explain( array( 'mode' => 'request_local', 'product_ids' => '' ) ) ), 'empty-product-string-does-not-expand-request-local' );
 $GLOBALS['allowed'] = false; $reads = $GLOBALS['reads']; $r = $service->explain( $empty_product_input );
 check( is_wp_error( $r ) && 'booster_forbidden' === $r->get_error_code() && $reads === $GLOBALS['reads'], 'empty-product-string-permission-first-denial' );
 $GLOBALS['allowed'] = true;
 foreach ( array( array( 'wcj_gateways_fees_value' => array( 'cod' => 'malformed' ) ), array( 'wcj_gateways_fees_round_precision' => array( 'cod' => array( 'bad' ) ) ), array( 'wcj_gateways_fees_text' => array( 'cod' => '[[x]]' ) ) ) as $bad_options ) { setup_case( array( 'options' => $bad_options ), $module ); check( 'unsupported' === $service->explain( $input )['state'], 'malformed-or-escaped-title-refusal-' . implode( ',', array_keys( $bad_options ) ) ); }
 setup_case( array(), $module ); $thrower = new ThrowingScenarioModule(); $GLOBALS['plugin']->all_modules['payment_gateways_fees'] = $thrower;
 $r = $service->explain( $input );
 check( 'not_evaluated' === $r['state'] && 'evaluation_failed' === $r['reason'] && false === strpos( json_encode( $r ), 'synthetic-secret' ), 'scenario-Throwable-caught-without-raw-error-detail' );
 $GLOBALS['plugin']->all_modules['payment_gateways_fees'] = $module;
 check( isset( $service->get_output_schema()['properties']['reason']['enum'] ) && in_array( $r['reason'], $service->get_output_schema()['properties']['reason']['enum'], true ), 'closed-output-reason-enum' );
 setup_case( array(), $module );
 $GLOBALS['module_enabled'] = false; $r = $service->explain( $input );
 check( 'not_evaluated' === $r['state'] && 'module_disabled' === $r['reason'], 'disabled-not-evaluated' );
 $GLOBALS['module_enabled'] = true;
 $new = new WCJ_Payment_Gateways_Fees(); $old = $GLOBALS['plugin']->all_modules['payment_gateways_fees'];
 $GLOBALS['plugin']->all_modules['payment_gateways_fees'] = $new;
 $r = $service->explain( array( 'mode' => 'request_local' ) );
 check( 'unavailable' === $r['state'], 'new-object-no-fabricated-evaluation' );
 $cart = setup_case( array(), $new ); $new->gateways_fees( $cart ); $r = $service->explain( array( 'mode' => 'request_local' ) );
 check( 'applied' === $r['reason'] && 'last_callback_not_current_cart' === $r['context'], 'actual-producer-snapshot-not-current-cart' );
 $safe_snapshot = $new->get_last_fee_decision();
 $generation = $r['generation'];
 try { $new->gateways_fees( new ThrowingFeeCart() ); } catch ( RuntimeException $e ) {}
 $r = $service->explain( array( 'mode' => 'request_local' ) );
 check( 'unavailable' === $r['state'] && 'evaluation_incomplete' === $r['reason'] && $r['generation'] > $generation && null === $r['amount'], 'interrupted-callback-cannot-return-stale-success' );
 $generation = $r['generation'];
 setup_case( array( 'missing_session' => true ), $new ); $new->gateways_fees();
 $r = $service->explain( array( 'mode' => 'request_local' ) );
 check( 'missing_cart_or_session' === $r['reason'] && $r['generation'] > $generation && null === $r['amount'], 'reason-lifecycle-replaces-stale-success' );
 $prop = new ReflectionProperty( $new, 'last_fee_decision' ); $prop->setAccessible( true );
 $prop->setValue( $new, array( 'gateway' => 'cod', 'reason' => 'future_unknown', 'version' => 1, 'generation' => 99 ) );
 check( 'unknown_decision' === $service->explain( array( 'mode' => 'request_local' ) )['reason'], 'unknown-producer-fail-closed' );
 $prop->setValue( $new, array( 'gateway' => 'cod', 'reason' => 'applied', 'version' => 99, 'generation' => 99, 'conditions' => array(), 'amount' => 5 ) );
 check( 'unknown_decision' === $service->explain( array( 'mode' => 'request_local' ) )['reason'], 'unknown-version-fail-closed' );
 $prop->setValue( $new, array( 'gateway' => 'cod', 'reason' => 'applied', 'version' => 1, 'generation' => 99, 'conditions' => array( 'secret' => 'matched' ), 'amount' => 5 ) );
 check( 'unknown_decision' === $service->explain( array( 'mode' => 'request_local' ) )['reason'], 'unknown-condition-fail-closed' );
 $prop->setValue( $new, array( 'gateway' => 'cod', 'reason' => 'applied', 'version' => 1, 'generation' => 99, 'conditions' => array(), 'amount' => INF, 'customer_email' => 'synthetic-secret@example.invalid' ) );
 check( 'unknown_decision' === $service->explain( array( 'mode' => 'request_local' ) )['reason'], 'nonfinite-unknown-facts-fail-closed' );
 $prop->setValue( $new, array_merge( $safe_snapshot, array( 'conditions' => array(), 'amount' => 5 ) ) );
 check( 'unknown_decision' === $service->explain( array( 'mode' => 'request_local' ) )['reason'], 'incomplete-applied-facts-fail-closed' );
 $prop->setValue( $new, array_merge( $safe_snapshot, array( 'customer_email' => 'synthetic-secret@example.invalid', 'fee' => array( 'name' => 'synthetic-secret' ), 'raw_option' => 'synthetic-secret' ) ) );
 $r = $service->explain( array( 'mode' => 'request_local' ) );
 check( 'applied' === $r['reason'] && false === strpos( json_encode( $r ), 'synthetic-secret' ) && array_keys( $service->get_output_schema()['properties'] ) === array_keys( $r ), 'allowlisted-producer-output-no-secret-spill' );
 $prop->setValue( $new, array_merge( $safe_snapshot, array( 'amount' => INF ) ) );
 check( 'unknown_decision' === $service->explain( array( 'mode' => 'request_local' ) )['reason'], 'nonfinite-complete-facts-fail-closed' );
 unset( $GLOBALS['plugin']->all_modules['payment_gateways_fees'] );
 check( 'producer_unavailable' === $service->explain( $input )['reason'], 'missing-producer-unavailable' );
 $GLOBALS['plugin']->all_modules['payment_gateways_fees'] = $old;
 setup_case( array(), $module );
 $admin = new WCJ_Rule_Explanations_Admin();
 $_SERVER['REQUEST_METHOD'] = 'GET'; $_POST = array(); ob_start(); $admin->render_page(); $html = ob_get_clean();
 check( false !== strpos( $html, 'Rule explanations' ) && false !== strpos( $html, 'merchant-supplied' ), 'admin-context-visible' );
 $_SERVER['REQUEST_METHOD'] = 'POST'; $_POST = array( '_wpnonce' => 'bad', 'gateway' => 'cod', 'subtotal' => '100', 'shipping' => '10', 'tax' => '5', 'shipping_tax' => '1' );
 ob_start(); $admin->render_page(); $html = ob_get_clean();
 check( false !== strpos( $html, 'expired' ), 'admin-invalid-nonce-no-evaluation' );
 $_POST['_wpnonce'] = 'synthetic'; ob_start(); $admin->render_page(); $html = ob_get_clean();
 check( false !== strpos( $html, 'eligible' ), 'admin-connects-actual-evaluation' );
 $_POST['subtotal'] = array( 'bad' ); ob_start(); $admin->render_page(); $html = ob_get_clean();
 check( false !== strpos( $html, 'valid numeric' ), 'admin-array-input-fails-before-conversion' );
 $GLOBALS['allowed'] = false; ob_start(); try { $admin->render_page(); check( false, 'admin-denial' ); } catch ( RuntimeException $e ) { check( true, 'admin-denial' ); } ob_end_clean();
 $GLOBALS['allowed'] = true;

 if ( 'older-ability' === $mode ) {
  // Full official older core classes/functions, with explicit surrounding doubles.
  if ( empty( $argv[5] ) ) { exit( 2 ); }
  // Import this one complete primitive from the exact same official core version.
  function wcj_rule_test_core_function( $file, $name ) {
   $tokens = token_get_all( file_get_contents( $file ) ); $count = count( $tokens );
   for ( $i = 0; $i < $count; ++$i ) {
    if ( ! is_array( $tokens[$i] ) || T_FUNCTION !== $tokens[$i][0] ) { continue; }
    $j = $i + 1;
    while ( is_array( $tokens[$j] ) && T_WHITESPACE === $tokens[$j][0] ) { ++$j; }
    if ( ! is_array( $tokens[$j] ) || $name !== $tokens[$j][1] ) { continue; }
    $body = ''; $depth = 0; $started = false;
    for ( ; $i < $count; ++$i ) {
     $token = $tokens[$i]; $body .= is_array( $token ) ? $token[1] : $token;
     if ( '{' === $token ) { ++$depth; $started = true; }
     elseif ( '}' === $token ) { --$depth; if ( $started && 0 === $depth ) { return $body; } }
    }
   }
   throw new RuntimeException( 'Missing complete pinned core primitive' );
  }
  $parse_list_body = wcj_rule_test_core_function( $argv[5] . '/wp-includes/functions.php', 'wp_parse_list' );
  eval( $parse_list_body );
  $GLOBALS['older_parse_list_body_sha256'] = hash( 'sha256', $parse_list_body );
  require $argv[5] . '/wp-includes/rest-api.php';
  require $argv[5] . '/wp-includes/abilities-api/class-wp-ability.php';
  require $argv[5] . '/wp-includes/rest-api/endpoints/class-wp-rest-abilities-v1-run-controller.php';
  setup_case( array(), $module );
  $core = new WP_Ability( 'booster/explain-gateway-fee', $registered );
  $query_string = 'input[mode]=scenario&input[gateway]=cod&input[subtotal]=100.25&input[shipping]=0&input[tax]=0&input[shipping_tax]=0';
  if ( 'elite' === $tier ) { $query_string .= '&input[role]=guest&input[product_ids][0]=12&input[product_ids][1]=13'; }
  parse_str( $query_string, $query );
  $request = new WCJ_Rule_Test_Query( $query ); $controller = new WP_REST_Abilities_V1_Run_Controller();
  $extract = new ReflectionMethod( $controller, 'get_input_from_request' ); $extract->setAccessible( true );
  $transport_input = $extract->invoke( $controller, $request );
  check( '100.25' === $transport_input['subtotal'], 'older-core-controller-retains-query-numeric-string' );
  check( $transport_input === $core->normalize_input( $transport_input ), 'older-core-normalizer-does-not-cast' );
  check( true === $core->validate_input( $transport_input ), 'older-core-number-schema-accepts-query-numeric-strings' );
  check( true === $controller->validate_request_method( 'GET', array( 'readonly' => true ) ) && is_wp_error( $controller->validate_request_method( 'POST', array( 'readonly' => true ) ) ), 'older-core-readonly-GET-method-boundary' );
  $identity = serialize( array( $GLOBALS['wc'], $GLOBALS['options'], $module ) );
  $r = $core->execute( $transport_input );
  check( ! is_wp_error( $r ) && 'eligible' === $r['reason'] && 5.0 === $r['amount'], 'older-core-actual-execute-query-scenario' );
  check( $identity === serialize( array( $GLOBALS['wc'], $GLOBALS['options'], $module ) ), 'older-core-execution-live-business-cache-isolation' );
  foreach ( array( '1e3', ' 100', '0x10', '1000000001' ) as $value ) {
   check( is_wp_error( $core->execute( array_merge( $transport_input, array( 'subtotal' => $value ) ) ) ), 'older-core-malformed-decimal-denial-' . $value );
  }
  if ( 'elite' === $tier ) { check( is_wp_error( $core->execute( array_merge( $transport_input, array( 'product_ids' => array( '1e1' ) ) ) ) ), 'older-core-malformed-product-id-denial' ); }
  setup_case( array( 'options' => array( 'wcj_gateways_fees_include_products' => array( 'cod' => array( 12 ) ) ) ), $module );
  $empty_query_string = 'input[mode]=scenario&input[gateway]=cod&input[subtotal]=100.25&input[shipping]=0&input[tax]=0&input[shipping_tax]=0&input[product_ids]=';
  if ( 'elite' === $tier ) { $empty_query_string .= '&input[role]=guest'; }
  parse_str( $empty_query_string, $empty_query );
  $empty_transport_input = $extract->invoke( $controller, new WCJ_Rule_Test_Query( $empty_query ) );
  check( '' === $empty_transport_input['product_ids'], 'older-core-controller-retains-explicit-empty-query-array-string' );
  check( true === rest_is_array( '' ) && array() === wp_parse_list( '' ), 'older-core-actual-empty-array-primitive-validation' );
  check( $empty_transport_input === $core->normalize_input( $empty_transport_input ), 'older-core-empty-array-normalizer-retains-original-input' );
  $validation = $core->validate_input( $empty_transport_input );
  check( 'elite' === $tier ? true === $validation : is_wp_error( $validation ), 'older-core-empty-array-schema-tier-boundary' );
  $identity = serialize( array( $GLOBALS['wc'], $GLOBALS['options'], $module ) ); $reads = $GLOBALS['reads'];
  $empty_result = $core->execute( $empty_transport_input );
  check( 'elite' === $tier ? ! is_wp_error( $empty_result ) && 'product_condition_not_met' === $empty_result['reason'] : is_wp_error( $empty_result ) && $reads === $GLOBALS['reads'], 'older-core-execute-explicit-empty-product-query' );
  check( $identity === serialize( array( $GLOBALS['wc'], $GLOBALS['options'], $module ) ), 'older-core-empty-query-business-cache-isolation' );
  $GLOBALS['allowed'] = false; $reads = $GLOBALS['reads'];
  check( is_wp_error( $core->execute( $empty_transport_input ) ) && $reads === $GLOBALS['reads'], 'older-core-empty-query-denial-no-Booster-option-reads' );
  $GLOBALS['allowed'] = true;
  $reads = $GLOBALS['reads']; $GLOBALS['allowed'] = false;
  check( is_wp_error( $core->execute( $transport_input ) ) && $reads === $GLOBALS['reads'], 'older-core-denial-no-Booster-option-reads' );
  $GLOBALS['allowed'] = true;
 }
 check( 0 === $GLOBALS['writes'], 'zero-business-persistence-writes' );
}
echo json_encode( array( 'tier' => $tier, 'mode' => $mode, 'pass' => $pass, 'failures' => $fail, 'legacy_cases' => count( $outputs ), 'older_parse_list_body_sha256' => $GLOBALS['older_parse_list_body_sha256'] ?? null, 'assertions' => $assertions, 'limitations' => 'Synthetic WordPress/WooCommerce doubles; actual production methods. native-shortcode mode additionally uses the pinned upstream shortcode source with synthetic callback/filter/HTML primitives. Not native UI, authentication, Ability REST, Store API, gateway/provider or tax settlement evidence.' ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";
exit( empty( $fail ) ? 0 : 1 );
