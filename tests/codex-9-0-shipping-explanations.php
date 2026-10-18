<?php
/**
 * Finite offline shipping amount/quantity qualification. No native admission claim.
 * Paths are supplied explicitly, never embedded local diagnostics.
 */
if ( count( $argv ) !== 5 ) { throw new RuntimeException( 'usage: fixture tier base-root schema-json tuple-json' ); }
$tier = $argv[1];
if ( ! in_array( $tier, array( 'free', 'plus', 'elite' ), true ) ) { throw new RuntimeException( 'tier' ); }
$root = dirname( __DIR__ );
$baseline = $argv[2];
$schema = json_decode( file_get_contents( $argv[3] ), true, 512, JSON_THROW_ON_ERROR );
$tuple_document = json_decode( file_get_contents( $argv[4] ), true, 512, JSON_THROW_ON_ERROR );
$tuples = $tuple_document['tuples'];
define( 'ABSPATH', $root . '/' );
define( 'WPINC', 'wp-includes' );
define( 'WCJ_PLUGIN_FILE', $root . '/' . ( 'elite' === $tier ? 'booster-elite-for-woocommerce.php' : ( 'plus' === $tier ? 'booster-plus-for-woocommerce.php' : 'woocommerce-jetpack.php' ) ) );
$GLOBALS['cap'] = true;
$GLOBALS['d03'] = false;
$GLOBALS['trace'] = array();
$GLOBALS['options'] = array();
$GLOBALS['allowed'] = array();
$GLOBALS['wp_filter'] = array();
$GLOBALS['observations'] = array();
function __($s, $d = null) { return $s; }
function esc_html($s) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function esc_html__($s, $d = null) { return esc_html( $s ); }
function esc_attr($s) { return esc_html( $s ); }
function esc_url($s) { return esc_html( $s ); }
function current_user_can($c) { $GLOBALS['trace'][] = array( 'cap', $c ); return $GLOBALS['cap']; }
function is_user_logged_in() { return true; }
function is_admin() { return true; }
function wcj_is_rest_request() { return false; }
function wcj_is_rest() { return false; }
function wp_doing_ajax() { return false; }
function is_checkout() { return true; }
function is_wc_endpoint_url($s) { return false; }
function get_current_blog_id() { return 2; }
function get_current_user_id() { return 7; }
function wp_unslash($v) { return $v; }
function wp_create_nonce($action) { return substr( hash( 'sha256', $action ), 0, 12 ); }
function wp_verify_nonce($n, $a) { return is_string( $n ) && hash_equals( wp_create_nonce( $a ), $n ); }
function wp_nonce_field($a, $n) { echo '<input name="' . esc_attr( $n ) . '" value="' . wp_create_nonce( $a ) . '">'; }
function admin_url($s = '') { return 'https://fixture.invalid/wp-admin/' . $s; }
function wc_get_checkout_url() { return 'https://fixture.invalid/?page_id=7'; }
function add_query_arg($values, $url) { $p = parse_url( $url ); $q = array(); parse_str( isset( $p['query'] ) ? $p['query'] : '', $q ); return ( isset( $p['scheme'] ) ? $p['scheme'] . '://' . $p['host'] : '' ) . ( isset( $p['path'] ) ? $p['path'] : '' ) . '?' . http_build_query( array_merge( $q, $values ) ); }
function add_filter($h, $c, $p = 10, $a = 1) { if ( ! isset( $GLOBALS['wp_filter'][$h] ) ) { $GLOBALS['wp_filter'][$h] = (object) array( 'callbacks' => array() ); } $GLOBALS['wp_filter'][$h]->callbacks[$p][] = array( 'function' => $c, 'accepted_args' => $a ); return true; }
function add_action($h, $c, $p = 10, $a = 1) { return add_filter( $h, $c, $p, $a ); }
function has_filter($h, $c) { if ( ! isset( $GLOBALS['wp_filter'][$h] ) ) { return false; } foreach ( $GLOBALS['wp_filter'][$h]->callbacks as $p => $rows ) { foreach ( $rows as $row ) { if ( $c === $row['function'] ) { return $p; } } } return false; }
function remove_filter($h, $c, $p = 10) { if ( ! isset( $GLOBALS['wp_filter'][$h]->callbacks[$p] ) ) { return false; } foreach ( $GLOBALS['wp_filter'][$h]->callbacks[$p] as $i => $row ) { if ( $row['function'] === $c ) { unset( $GLOBALS['wp_filter'][$h]->callbacks[$p][$i] ); return true; } } return false; }
function wcj_get_option($key, $default = null) { $GLOBALS['trace'][] = array( 'wcj_option', $key ); return array_key_exists( $key, $GLOBALS['options'] ) ? $GLOBALS['options'][$key] : $default; }
function get_option($key, $default = null) { $GLOBALS['trace'][] = array( 'option', $key ); return array_key_exists( $key, $GLOBALS['options'] ) ? $GLOBALS['options'][$key] : $default; }
function wcj_is_module_enabled($id) { $GLOBALS['trace'][] = array( 'module', $id ); return 'modules_by_user_roles' === $id ? $GLOBALS['d03'] : false; }
function wcj_is_module_allowed_by_allowlist($id) { $GLOBALS['trace'][] = array( 'allowlist', $id ); return ! array_key_exists( $id, $GLOBALS['allowed'] ) || $GLOBALS['allowed'][$id]; }
function w_c_j() { return $GLOBALS['plugin']; }
function WC() { $GLOBALS['trace'][] = array( 'WC' ); return $GLOBALS['wc']; }
class WP_Error { private $code; private $message; public function __construct($c, $m = '') { $this->code = $c; $this->message = $m; } public function get_error_code() { return $this->code; } public function get_error_message() { return $this->message; } }
function is_wp_error($v) { return $v instanceof WP_Error; }
class Codex_Denied extends RuntimeException {}
function wp_die($s) { throw new Codex_Denied( (string) $s ); }
#[AllowDynamicProperties]
class WCJ_Module { public function __construct() {} public function is_enabled() { return 'yes' === wcj_get_option( 'wcj_' . $this->id . '_enabled', 'no' ); } }
class Codex_Cart_Facts { public $cart_contents_total = 20; public $quantity = 2; public $empty = false; public function is_empty() { $GLOBALS['trace'][] = array( 'is_empty' ); return $this->empty; } public function get_cart_contents_count() { $GLOBALS['trace'][] = array( 'count' ); return $this->quantity; } }
class Codex_Rate_Facts { private $method; private $instance; private $reads = 0; private $change; public function __construct($method = 'flat_rate', $instance = 9, $change = false) { $this->method = $method; $this->instance = $instance; $this->change = $change; } public function __get($name) { $GLOBALS['trace'][] = array( 'rate_get', $name ); ++$this->reads; return 'method_id' === $name ? ( $this->change && $this->reads > 1 ? 'pickup' : $this->method ) : $this->instance; } }
function require_check($value, $label) { if ( true !== $value ) { throw new RuntimeException( $label ); } }
function error_is($value, $code) { return is_wp_error( $value ) && $value->get_error_code() === $code; }
function trace_named($kind) { return array_values( array_filter( $GLOBALS['trace'], function($v) use ($kind) { return $v[0] === $kind; } ) ); }
function reset_trace() { $GLOBALS['trace'] = array(); }
function observe($name, $facts) { $GLOBALS['observations'][] = array( 'case' => $name, 'facts' => $facts ); }
function all_modules($mode = false) { $GLOBALS['wp_filter']['woocommerce_package_rates'] = (object) array( 'callbacks' => array() ); $GLOBALS['options']['wcj_shipping_by_order_amount_enabled'] = 'yes'; $GLOBALS['options']['wcj_shipping_by_order_qty_enabled'] = 'yes'; $GLOBALS['options']['wcj_shipping_by_order_amount_use_shipping_instance'] = $mode ? 'yes' : 'no'; $GLOBALS['options']['wcj_shipping_by_order_qty_use_shipping_instance'] = $mode ? 'yes' : 'no'; $a = new WCJ_Shipping_By_Order_Amount(); $q = new WCJ_Shipping_By_Order_Qty(); $GLOBALS['plugin'] = (object) array( 'all_modules' => array( 'shipping_by_order_amount' => $a, 'shipping_by_order_qty' => $q ) ); return array( $a, $q ); }
function scenario($mode = 'method') { return array( 'mode' => 'scenario', 'owners' => array( 'shipping_by_order_amount' => array( 'rule_key' => array( 'kind' => $mode, 'value' => 'instance' === $mode ? 9 : 'flat_rate' ) ), 'shipping_by_order_qty' => array( 'rule_key' => array( 'kind' => $mode, 'value' => 'instance' === $mode ? 9 : 'flat_rate' ) ) ), 'facts' => array( 'cart_present' => true, 'cart_empty' => false, 'contents_total' => 20, 'contents_count' => 2 ) ); }
function reasons($r) { require_check( is_array( $r ) && isset( $r['rows'] ), 'response_array' ); return array_column( $r['rows'], 'reason' ); }
require_once $root . '/includes/class-wcj-rule-explanations.php';
$GLOBALS['options']['wcj_shipping_by_order_amount_enabled'] = 'yes';
$GLOBALS['options']['wcj_shipping_by_order_qty_enabled'] = 'yes';
$GLOBALS['options']['wcj_shipping_by_order_qty_min'] = array();
$GLOBALS['options']['wcj_shipping_by_order_qty_max'] = array();
require_once $root . '/includes/class-wcj-shipping-by-order-amount.php';
require_once $root . '/includes/class-wcj-shipping-by-order-qty.php';
$service = new WCJ_Rule_Explanations();
list($amount, $qty) = all_modules();
$GLOBALS['wc'] = new stdClass();
$rates = array( 'flat_rate:9' => new Codex_Rate_Facts() );
foreach ( array( $amount, $qty ) as $module ) { reset_trace(); require_check( $rates === $module->available_shipping_methods( $rates, array() ), 'no_cart_return' ); require_check( $GLOBALS['trace'] === array( array( 'WC' ) ), 'no_cart_short_circuit' ); }
$GLOBALS['wc']->cart = new Codex_Cart_Facts(); $GLOBALS['wc']->cart->empty = true;
foreach ( array( $amount, $qty ) as $module ) { reset_trace(); require_check( $rates === $module->available_shipping_methods( $rates, array() ), 'empty_cart_return' ); require_check( $GLOBALS['trace'] === array( array( 'WC' ), array( 'WC' ), array( 'is_empty' ) ), 'empty_short_circuit' ); }
$GLOBALS['wc']->cart->empty = false;
observe( 'normal_early_paths', array( 'no_cart_WC' => 1, 'empty_WC' => 2, 'bounds_reads' => 0 ) );
foreach ( array( false, true ) as $instance ) {
 list($amount, $qty) = all_modules( $instance );
 $prefix = $instance ? 'wcj_shipping_by_order_amount_min_instance_' : 'wcj_shipping_by_order_amount_min_'; $suffix = $instance ? '9' : 'flat_rate';
 $GLOBALS['options'][$prefix . $suffix] = 21; $GLOBALS['options'][str_replace( '_min_', '_max_', $prefix ) . $suffix] = 30;
 reset_trace(); require_check( array() === $amount->available_shipping_methods( array( 'flat_rate:9' => new Codex_Rate_Facts() ), array() ), 'amount_min_removes' );
 $reads = array_merge( trace_named( 'wcj_option' ), trace_named( 'option' ) );
 require_check( $reads === array( array( $instance ? 'option' : 'wcj_option', $prefix . $suffix ), array( $instance ? 'option' : 'wcj_option', str_replace( '_min_', '_max_', $prefix ) . $suffix ) ), 'amount_lazy_min_max' );
 require_check( count( trace_named( 'rate_get' ) ) === 2, 'amount_two_key_reads' );
 $GLOBALS['options'][$prefix . $suffix] = 20; $GLOBALS['options'][str_replace( '_min_', '_max_', $prefix ) . $suffix] = 20;
 $r = array( 'flat_rate:9' => new Codex_Rate_Facts() ); require_check( $r === $amount->available_shipping_methods( $r, array() ), 'amount_equality_retains' );
 $GLOBALS['options'][$prefix . $suffix] = 0; $GLOBALS['options'][str_replace( '_min_', '_max_', $prefix ) . $suffix] = 19;
 require_check( array() === $amount->available_shipping_methods( array( 'flat_rate:9' => new Codex_Rate_Facts() ), array() ), 'amount_max_removes' );
 observe( $instance ? 'amount_instance' : 'amount_method', array( 'min_then_max' => true, 'two_actual_key_reads' => true, 'equality_retained' => true ) );
}
list($amount, $qty) = all_modules();
$qty->min_qty = array( 'flat_rate' => 3 ); $qty->max_qty = array( 'flat_rate' => 8 );
reset_trace(); require_check( array() === $qty->available_shipping_methods( array( 'flat_rate:9' => new Codex_Rate_Facts() ), array() ), 'qty_min_removes' );
require_check( array() === trace_named( 'option' ) && array() === trace_named( 'wcj_option' ) && count( trace_named( 'rate_get' ) ) === 2 && count( trace_named( 'count' ) ) === 1, 'qty_cache_no_refresh' );
$qty->min_qty = array( 'flat_rate' => null ); $qty->max_qty = array( 'flat_rate' => null );
require_check( 0 === $qty->get_min_max_qty( new Codex_Rate_Facts(), 'min' ), 'qty_null_fallback' );
foreach ( array( 0, '0', 0.0, '00', '0.0' ) as $zero ) { $qty->min_qty = array( 'flat_rate' => 0 ); $qty->max_qty = array( 'flat_rate' => $zero ); $r = array( 'flat_rate:9' => new Codex_Rate_Facts() ); $out = $qty->available_shipping_methods( $r, array() ); require_check( ( 0 === $zero || '0' === $zero ) ? $r === $out : array() === $out, 'qty_strict_sentinel' ); }
observe( 'quantity_cached_sentinels', array( 'no_refresh' => true, 'null_fallback_int0' => true, 'float_and_noncanonical_zero_active' => true ) );
foreach ( array( 'flat_rate:9', '-0', '00', '-05', '9223372036854775808', '-9223372036854775809' ) as $key ) { require_check( $service->shipping_typed_key_valid( array( 'kind' => 'string', 'value' => $key ) ), 'typed_string_accept' ); }
foreach ( array( '0', '123', '-5', '9223372036854775807', '-9223372036854775808', "flat\n", "flat\r", "flat\0", '' ) as $key ) { require_check( ! $service->shipping_typed_key_valid( array( 'kind' => 'string', 'value' => $key ) ), 'typed_string_reject' ); }
foreach ( array( true, 1.0, '1', -1, 2147483648 ) as $v ) { require_check( ! $service->shipping_typed_key_valid( array( 'kind' => 'int', 'value' => $v ) ), 'typed_int_reject' ); }
require_check( false === $service->shipping_transport_selector( 'int', '01' ) && false === $service->shipping_transport_selector( 'int', "1\n" ) && false === $service->shipping_transport_selector( 'string', '-5' ), 'selector_strict' );
require_check( array( 'kind' => 'int', 'value' => 0 ) === $service->shipping_transport_selector( 'int', '0' ), 'selector_int0' );
observe( 'typed_keys', array( 'signed_php_integer_strings_rejected' => true, 'overflow_and_noncanonical_strings_preserved' => true, 'full_end_guard' => true ) );
// Producer validation is exercised against each pinned joint tuple, not reconstructed labels.
require_check( count( $tuples ) === 54, 'tuple_inventory' );
$checked = 0;
foreach ( $tuples as $tuple ) {
 $row = $tuple; if ( is_array( $row['call_ordinal'] ) ) { $row['call_ordinal'] = 'shipping_by_order_amount' === $row['owner'] ? 1 : 2; }
 $other_owner = 'shipping_by_order_amount' === $row['owner'] ? 'shipping_by_order_qty' : 'shipping_by_order_amount'; $other = null;
 foreach ( $tuples as $candidate ) { if ( $candidate['owner'] === $other_owner && $candidate['context'] === $row['context'] && null === $candidate['call_ordinal'] ) { $other = $candidate; break; } }
 require_check( is_array( $other ), 'tuple_counterpart' );
 $rows = 'shipping_by_order_amount' === $row['owner'] ? array( $row, $other ) : array( $other, $row );
 require_check( $service->validate_shipping_rows( $rows, $row['context'] ), 'joint_tuple_' . $checked );
 $bad = $rows; $bad[0]['started'] = true; require_check( ! $service->validate_shipping_rows( $bad, $row['context'] ), 'bool_count_rejection' );
 $bad = $rows; $bad[0]['completed'] = 2; require_check( ! $service->validate_shipping_rows( $bad, $row['context'] ), 'completed_over_started' );
 require_check( ! $service->validate_shipping_rows( array_reverse( $rows ), $row['context'] ), 'owner_order' );
 $bad = $rows; $bad[0]['extra'] = null; require_check( ! $service->validate_shipping_rows( $bad, $row['context'] ), 'closed_row' );
 $bad = $rows; $bad[0]['reason'] = 'not_a_reason'; require_check( ! $service->validate_shipping_rows( $bad, $row['context'] ), 'unknown_reason' );
 ++$checked;
}
observe( 'closed_joint_tuples', array( 'pinned_tuples' => $checked, 'strict_counts_and_order' => true ) );
$GLOBALS['cap'] = false; reset_trace(); require_check( error_is( $service->explain_shipping_options( scenario() ), 'booster_shipping_capability_required' ), 'scenario_permission_first' ); require_check( count( $GLOBALS['trace'] ) === 1, 'permission_zero_private_reads' ); $GLOBALS['cap'] = true;
if ( 'elite' !== $tier ) {
 reset_trace(); require_check( error_is( $service->explain_shipping_options( scenario() ), 'booster_shipping_elite_required' ), 'scenario_tier' ); require_check( array() === trace_named( 'option' ) && array() === trace_named( 'wcj_option' ) && array() === trace_named( 'module' ), 'tier_refusal_lazy' );
 require_check( error_is( $service->compare_shipping_options( array( 'mode' => 'compare', 'scenarios' => array( scenario(), scenario() ) ) ), 'booster_shipping_elite_required' ), 'comparison_tier' );
 observe( 'scenario_entitlement', array( 'elite_required' => true, 'private_reads' => 0 ) );
} else {
 list($amount, $qty) = all_modules();
 $GLOBALS['options']['wcj_shipping_by_order_amount_min_flat_rate'] = 10; $GLOBALS['options']['wcj_shipping_by_order_amount_max_flat_rate'] = 30; $qty->min_qty = array( 'flat_rate' => 1 ); $qty->max_qty = array( 'flat_rate' => 3 );
 reset_trace(); $r = $service->explain_shipping_options( scenario() ); require_check( array( 'within_limits', 'within_limits' ) === reasons( $r ), 'scenario_two_within' ); require_check( $service->validate_shipping_output( $r ), 'scenario_output' );
 require_check( array( array( 'wcj_option', 'wcj_shipping_by_order_amount_enabled' ), array( 'wcj_option', 'wcj_shipping_by_order_amount_min_flat_rate' ), array( 'wcj_option', 'wcj_shipping_by_order_amount_max_flat_rate' ), array( 'wcj_option', 'wcj_shipping_by_order_qty_enabled' ) ) === trace_named( 'wcj_option' ), 'scenario_lazy_option_order' );
 $GLOBALS['d03'] = true; reset_trace(); require_check( error_is( $service->explain_shipping_options( scenario() ), 'booster_shipping_context_not_supported' ), 'd03_refusal' ); require_check( array() === trace_named( 'wcj_option' ), 'd03_zero_rule_reads' ); $GLOBALS['d03'] = false;
 $none = scenario(); $none['facts'] = array( 'cart_present' => false, 'cart_empty' => true, 'contents_total' => 0, 'contents_count' => 0 );
 $amount->use_shipping_instances = true; reset_trace(); $r = $service->explain_shipping_options( $none ); require_check( array( 'unsupported_rate_identity', 'no_cart' ) === reasons( $r ), 'mode_before_no_cart_independent' ); require_check( count( trace_named( 'wcj_option' ) ) === 2, 'no_rules_for_no_cart' ); $amount->use_shipping_instances = false;
 $empty = $none; $empty['facts']['cart_present'] = true; require_check( array( 'empty_cart', 'empty_cart' ) === reasons( $service->explain_shipping_options( $empty ) ), 'empty_independent' );
 foreach ( array( '1e2', ' 10', "10\n", null, true, new stdClass() ) as $bad_bound ) { $GLOBALS['options']['wcj_shipping_by_order_amount_min_flat_rate'] = $bad_bound; $r = $service->explain_shipping_options( scenario() ); require_check( 'unsupported_rule_values' === $r['rows'][0]['reason'], 'strict_bound_support' ); }
 $GLOBALS['options']['wcj_shipping_by_order_amount_min_flat_rate'] = 0; $GLOBALS['options']['wcj_shipping_by_order_amount_max_flat_rate'] = 0;
 foreach ( array( 0, '0', 0.0, '00', '0.0' ) as $zero ) { $qty->min_qty = array( 'flat_rate' => 0 ); $qty->max_qty = array( 'flat_rate' => $zero ); $r = $service->explain_shipping_options( scenario() ); require_check( $r['rows'][1]['reason'] === ( ( 0 === $zero || '0' === $zero ) ? 'no_limit' : 'above_max' ), 'scenario_qty_sentinel' ); }
 $qty->min_qty = array(); $qty->max_qty = array(); $GLOBALS['options']['wcj_shipping_by_order_amount_min_flat_rate'] = 21; $GLOBALS['options']['wcj_shipping_by_order_amount_max_flat_rate'] = 30;
 $r = $service->explain_shipping_options( scenario() ); require_check( array( 'below_min', 'no_limit' ) === reasons( $r ), 'independent_not_chain' );
 $GLOBALS['allowed']['shipping_by_order_amount'] = false; remove_filter( 'woocommerce_package_rates', array( $amount, 'available_shipping_methods' ), PHP_INT_MAX );
 $r = $service->explain_shipping_options( scenario() ); require_check( array( 'module_not_admitted', 'no_limit' ) === reasons( $r ), 'independent_absent_owner' );
 add_filter( 'woocommerce_package_rates', array( $amount, 'available_shipping_methods' ), PHP_INT_MAX, 2 ); require_check( 'module_admission_denied' === $service->explain_shipping_options( scenario() )['rows'][0]['reason'], 'contradictory_admission' ); $GLOBALS['allowed'] = array();
 foreach ( array( 'contents_total' => '20', 'contents_count' => 2.0, 'cart_present' => 1, 'cart_empty' => 0 ) as $key => $bad_fact ) { $s = scenario(); $s['facts'][$key] = $bad_fact; require_check( error_is( $service->explain_shipping_options( $s ), 'booster_shipping_invalid_input' ), 'strict_facts' ); }
 $s = scenario(); $s['extra'] = null; require_check( error_is( $service->explain_shipping_options( $s ), 'booster_shipping_invalid_input' ), 'closed_scenario' );
 $s = scenario(); $s['owners']['shipping_by_order_amount']['rule_key']['value'] = "flat_rate\n"; require_check( error_is( $service->explain_shipping_options( $s ), 'booster_shipping_invalid_input' ), 'method_full_end' );
 $compare = $service->compare_shipping_options( array( 'mode' => 'compare', 'scenarios' => array( scenario(), scenario() ) ) ); require_check( $service->validate_shipping_output( $compare, true ) && false === $compare['observations_atomic'] && null === $compare['final_availability'], 'comparison_nonatomic' );
 $bad = $compare; $bad['results'] = array( 1 => $compare['results'][0], 2 => $compare['results'][1] ); require_check( ! $service->validate_shipping_output( $bad, true ), 'dense_results' );
 observe( 'elite_independent_scenarios', array( 'admission_source_guard' => true, 'lazy_min_max' => true, 'D03_zero_rules' => true, 'mode_priority' => true, 'strict_bounds_facts' => true, 'quantity_sentinels' => true, 'non_atomic_comparison' => true ) );
}
// Prearm hooks are genuine unarmed no-ops. No reflection injection of armed/native state.
reset_trace(); $sentinel = array( 'opaque' => new stdClass() );
require_check( $service->open_shipping_generation( $sentinel ) === $sentinel, 'open_prearm' );
require_check( $service->begin_shipping_pass( $sentinel, array() ) === $sentinel, 'begin_prearm' );
require_check( $service->end_shipping_pass( $sentinel, array() ) === $sentinel, 'end_prearm' );
require_check( $service->close_shipping_generation( $sentinel ) === $sentinel, 'close_prearm' );
require_check( array() === $GLOBALS['trace'], 'prearm_zero_reads' );
require_check( error_is( $service->get_shipping_observation(), 'booster_shipping_observation_unavailable' ), 'no_fabricated_generation' );
ob_start(); $service->render_shipping_observer(); require_check( '' === ob_get_clean(), 'no_classic_hook_output_without_generation' );
$_GET = array(); WCJ_Rule_Explanations::initialize_shipping_transport(); $_GET['wcj_shipping_observe'] = '1';
require_check( array( 'X-Test' => 'kept' ) === WCJ_Rule_Explanations::shipping_transport_headers( array( 'X-Test' => 'kept' ), null ), 'late_key_not_recognized' ); $_GET = array();
observe( 'prearm_only', array( 'native_success' => 'UNQUALIFIED', 'unchanged_hooks' => true, 'late_injection_unrecognized' => true ) );
require_once $root . '/includes/class-wcj-rule-explanations-admin.php';
$admin = new WCJ_Rule_Explanations_Admin(); $_SERVER['REQUEST_METHOD'] = 'POST';
$collisions = array( 'wcj_payment_action', 'wcj_payment_form_nonce', 'wcj_payment_gateway_key', 'wcj_payment_json', 'wcj_rule_action', 'wcj_rule_comparison_nonce', '_wpnonce', 'gateway', 'subtotal', 'shipping', 'tax', 'shipping_tax', 'product_ids', 'role', 'scenarios' );
foreach ( $collisions as $key ) { foreach ( array( null, array(), 'value' ) as $value ) { $_POST = array( 'wcj_shipping_action' => null, $key => $value ); reset_trace(); ob_start(); $admin->render_page(); $html = ob_get_clean(); require_check( false !== strpos( $html, 'booster_shipping_invalid_input' ), 'exclusive_collision_' . $key ); require_check( array() === trace_named( 'wcj_option' ) && array() === trace_named( 'option' ) && array() === trace_named( 'module' ), 'collision_no_legacy_reads' ); } }
$_POST = array( 'wcj_shipping_action' => 'observe', 'wcj_shipping_form_nonce' => 'invalid' ); ob_start(); $admin->render_page(); $html = ob_get_clean(); require_check( false !== strpos( $html, 'booster_expired_rule_form' ), 'admin_nonce_expiry' );
$_POST = array( 'wcj_shipping_action' => 'observe', 'wcj_shipping_form_nonce' => wp_create_nonce( 'wcj_shipping_diagnostic' ), '_wp_http_referer' => '/wp-admin/', 'wcj_shipping_package_kind' => 'int', 'wcj_shipping_package_key' => '0', 'wcj_shipping_rate_kind' => 'string', 'wcj_shipping_rate_key' => 'flat_rate:9' );
ob_start(); $admin->render_page(); $html = html_entity_decode( ob_get_clean(), ENT_QUOTES, 'UTF-8' ); require_check( false !== strpos( $html, 'page_id=7' ) && false !== strpos( $html, 'wcj_shipping_nonce=' ) && false === strpos( $html, 'booster_shipping_invalid_input' ), 'shared_referer_and_manual_link' );
$GLOBALS['cap'] = false; $caught = false; try { $admin->render_page(); } catch ( Codex_Denied $e ) { $caught = true; } require_check( $caught, 'admin_capability_first' ); $GLOBALS['cap'] = true;
$_POST = array( 'wcj_shipping_action' => 'scenario', 'wcj_shipping_form_nonce' => wp_create_nonce( 'wcj_shipping_diagnostic' ), 'wcj_shipping_json' => str_repeat( 'x', 8193 ) ); reset_trace(); ob_start(); $admin->render_page(); $html = ob_get_clean(); require_check( false !== strpos( $html, 'elite' === $tier ? 'booster_shipping_invalid_input' : 'booster_shipping_elite_required' ), 'admin_tier_or_length_before_reads' ); require_check( array() === trace_named( 'wcj_option' ), 'invalid_admin_zero_rules' );
observe( 'admin_boundaries', array( 'collision_fields' => 15, 'null_array_scalar_presence' => true, 'nonce_expiry' => true, 'page_id_preserved' => true, 'shared_referer_allowed' => true ) );
function wp_register_ability($name, $args) { $GLOBALS['codex_ability_registry'][$name] = $args; }
require_once $root . '/includes/class-wcj-status-service.php';
require_once $root . '/includes/class-wcj-abilities.php';
$abilities = new WCJ_Abilities(); $abilities->register_abilities();
foreach ( array( 'booster/explain-shipping-options', 'booster/compare-shipping-options' ) as $name ) { require_check( isset( $GLOBALS['codex_ability_registry'][$name] ), 'ability_registered' ); require_check( $GLOBALS['codex_ability_registry'][$name]['input_schema'] === $schema['input_schemas'][$name] && $GLOBALS['codex_ability_registry'][$name]['output_schema'] === $schema['output_schemas'][$name], 'exact_closed_ability_schema' ); }
$GLOBALS['cap'] = false; require_check( error_is( $abilities->execute_shipping_explanation( scenario() ), 'booster_forbidden' ), 'ability_permission' ); $GLOBALS['cap'] = true;
$ability_result = $abilities->execute_shipping_explanation( scenario() ); require_check( 'elite' === $tier ? $service->validate_shipping_output( $ability_result ) : error_is( $ability_result, 'booster_shipping_elite_required' ), 'ability_runtime_boundary' );
observe( 'abilities', array( 'two_closed_schemas_exact' => true, 'permission_first' => true ) );
// Source-backed old dispatcher closure is outside this standalone controlled call scope.
echo json_encode( array( 'status' => 'PASS_OFFLINE_AFFECTED', 'tier' => $tier, 'observations' => $GLOBALS['observations'], 'source_sha256' => hash_file( 'sha256', $root . '/includes/class-wcj-rule-explanations.php' ), 'unqualified' => array( 'effective HTTP headers/SAPI/cache/referrer', 'native successful arm and actual eight-frame Woo caller chain', 'armed generation/pass/owner temporal edge cases', 'scripted positive traces are not native evidence', 'remaining four shipping owners and ShippingOptions', 'Blocks/browser/package/staging', 'old ordinary dispatcher behavior is preserved by source diff, not replayed old suite' ) ), JSON_THROW_ON_ERROR ) . "\n";
