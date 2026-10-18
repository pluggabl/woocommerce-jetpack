<?php
/**
 * Exact shipping review correction12 affected source checks only.
 *
 * @package Booster_For_WooCommerce/tests
 */
if ( 'cli' !== PHP_SAPI ) { exit( 1 ); }
error_reporting( E_ALL );
set_error_handler( static function ( $severity, $message, $file, $line ) { throw new ErrorException( $message, 0, $severity, $file, $line ); } );
$plan = json_decode( file_get_contents( $argv[1] ), true ); $tier = $argv[2];
foreach ( $plan['imports'][ $tier ] as $r ) { if ( filesize( $r['path'] ) !== $r['bytes'] || hash_file( 'sha256', $r['path'] ) !== $r['sha256'] ) { throw new RuntimeException( 'Import identity mismatch.' ); } }
$root = dirname( __DIR__ );
define( 'ABSPATH', $root . '/' );
define( 'WCJ_PLUGIN_FILE', array( 'free' => 'woocommerce-jetpack.php', 'plus' => 'booster-plus-for-woocommerce.php', 'elite' => 'booster-elite-for-woocommerce.php' )[ $tier ] );
$_GET = array(); $_POST = array(); $_REQUEST = array();
$GLOBALS['c12_rows'] = array(); $GLOBALS['c12_reads'] = array(); $GLOBALS['c12_cap'] = true; $GLOBALS['c12_auth'] = array( 0, 0 ); $GLOBALS['c12_options'] = array(); $GLOBALS['c12_clock'] = 0; $GLOBALS['c12_parse'] = 0; $GLOBALS['c12_abilities'] = array();
function __( $v, $domain = '' ) { return $v; }
function add_action( $hook, $callback, $priority = 10, $accepted = 1 ) {}
function current_user_can( $cap ) { ++$GLOBALS['c12_auth'][0]; return 'manage_woocommerce' === $cap && $GLOBALS['c12_cap']; }
function wcj_get_option( $key, $default = false ) { $GLOBALS['c12_reads'][] = $key; return array_key_exists( $key, $GLOBALS['c12_options'] ) ? $GLOBALS['c12_options'][ $key ] : $default; }
function get_option( $key, $default = false ) { return wcj_get_option( $key, $default ); }
function current_time( $format ) { return '2000-01-01 00:00:00'; }
function wcj_get_timestamp_date_from_gmt() { ++$GLOBALS['c12_clock']; return 946684800; }
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function wp_register_ability( $name, $metadata ) { $GLOBALS['c12_abilities'][ $name ] = $metadata; }
class WP_Error { private $code; public function __construct( $code, $message = '' ) { $this->code = $code; } public function get_error_code() { return $this->code; } }
#[AllowDynamicProperties]
class WCJ_Module { public function __construct( $type = 'module' ) {} public function is_enabled() { return false; } }
require $root . '/includes/class-wcj-rule-explanations.php';
require $root . '/includes/classes/class-wcj-module-shipping-by-condition.php';
require $root . '/includes/class-wcj-shipping-by-time.php';
require $root . '/includes/class-wcj-abilities.php';
class Codex_C12_Service extends WCJ_Rule_Explanations { public function is_full_experience() { ++$GLOBALS['c12_auth'][1]; return parent::is_full_experience(); } }
class Codex_C12_Time extends WCJ_Shipping_By_Time { public function parse_time( $value ) { ++$GLOBALS['c12_parse']; return parent::parse_time( $value ); } }
$service = new Codex_C12_Service(); $time = new Codex_C12_Time();
function c12_check( $actual, $expected, $label ) { $row = array( 'label' => $label, 'actual' => $actual, 'expected' => $expected, 'pass' => $actual === $expected ); $GLOBALS['c12_rows'][] = $row; if ( ! $row['pass'] ) { echo json_encode( array( 'status' => 'FAIL', 'rows' => $GLOBALS['c12_rows'] ), JSON_PRETTY_PRINT ); exit( 1 ); } }
function c12_pure( $name, array $args ) { $method = new ReflectionMethod( 'WCJ_Rule_Explanations', $name ); return $method->invokeArgs( $GLOBALS['c12_service'], $args ); }
$GLOBALS['c12_service'] = $service;
foreach ( $plan['positive'] as $case ) {
 $in = $plan['empty_input']; $facts = array( 'status' => 'supplied', 'selected_key' => $case['selected_key'], 'rates' => $case['rates'] ); $in['facts']['ordered_rates'] = $facts;
 $GLOBALS['c12_reads'] = array(); c12_check( c12_pure( 'shipping_v2_input_valid', array( $in ) ), true, $case['id'] . ':valid' );
 unset( $facts['status'] ); $GLOBALS['c12_options'] = $case['options'];
 c12_check( c12_pure( 'shipping_v2_options_scenario', array( 'shipping_options_most_expensive', $facts ) ), $case['expected_most_expensive_reason'], $case['id'] . ':object_identity' );
 c12_check( $GLOBALS['c12_reads'], array( 'wcj_shipping_most_expensive_enabled', 'wcj_shipping_most_expensive_ignored_methods' ), $case['id'] . ':reads' );
 c12_check( $in['facts']['ordered_rates']['selected_key'], $case['selected_key'], $case['id'] . ':no_key_normalization' );
}
foreach ( $plan['negative'] as $case ) { $in = $plan['empty_input']; $in['facts']['ordered_rates'] = $case['facts']; $GLOBALS['c12_reads'] = array(); c12_check( c12_pure( 'shipping_v2_input_valid', array( $in ) ), false, $case['id'] . ':denied' ); c12_check( $GLOBALS['c12_reads'], array(), $case['id'] . ':zero_reads' ); }
foreach ( $plan['auth_cases'][ $tier ] as $case ) {
 $GLOBALS['c12_cap'] = $case['capability']; $GLOBALS['c12_auth'] = array( 0, 0 ); $GLOBALS['c12_reads'] = array();
 $result = $service->{$case['method']}( $case['input'] );
 c12_check( is_wp_error( $result ) ? $result->get_error_code() : null, $case['expected_code'], $case['id'] . ':code' );
 c12_check( $GLOBALS['c12_auth'], $case['expected_auth_calls'], $case['id'] . ':new_auth_counts' );
 c12_check( $GLOBALS['c12_reads'], array(), $case['id'] . ':zero_private_reads' );
}
$GLOBALS['c12_cap'] = true;
foreach ( array( 128, 129, 8192, 8193 ) as $length ) { foreach ( array( 'include', 'exclude' ) as $phase ) {
 $GLOBALS['c12_reads'] = array(); $GLOBALS['c12_clock'] = 0; $GLOBALS['c12_parse'] = 0; $cache = array();
 $value = str_repeat( 'x', $length ); $actual = c12_pure( 'shipping_v2_check', array( $time, 'shipping_by_time', 'time', $value, $phase, array(), &$cache ) );
 c12_check( $actual, 128 === $length ? array( 'include' === $phase, 'invalid_time_bypass', 'invalid_range' ) : 'unsupported_rule_shape', 'time' . $length . ':' . $phase );
 c12_check( $GLOBALS['c12_reads'], array(), 'time' . $length . ':' . $phase . ':zero_options' );
 c12_check( $GLOBALS['c12_clock'], 0, 'time' . $length . ':' . $phase . ':zero_clock' );
 c12_check( $GLOBALS['c12_parse'], 128 === $length ? 1 : 0, 'time' . $length . ':' . $phase . ':actual_parse_count' );
} }
$text = file_get_contents( $root . '/includes/class-wcj-rule-explanations.php' );
c12_check( substr_count( $text, $plan['observer_anchor'] ), 1, 'observer_time128_city8192_source_only' );
$GLOBALS['c12_reads'] = array(); WCJ_Rule_Explanations::shipping_v2_phase( $time, 'flat_rate', 'time', 'include', str_repeat( 'x', 129 ) );
c12_check( $GLOBALS['c12_reads'], array(), 'unarmed_observer_zero_reads' );
c12_check( $GLOBALS['c12_clock'], 0, 'unarmed_observer_no_clock' );
$GLOBALS['c12_reads'] = array(); $abilities = new WCJ_Abilities(); $abilities->register_abilities();
foreach ( array( false, true ) as $compare ) {
 $id = $compare ? 'booster/compare-shipping-options' : 'booster/explain-shipping-options'; $registered = $GLOBALS['c12_abilities'][ $id ];
 c12_check( $registered['description'], $plan['description'], $id . ':description' );
 c12_check( $registered['label'], $compare ? 'Compare shipping predicates' : 'Explain shipping predicates', $id . ':label' );
 c12_check( $registered['input_schema'], $service->get_shipping_dispatch_input_schema( $compare ), $id . ':input_schema' );
 c12_check( $registered['output_schema'], $service->get_shipping_dispatch_output_schema( $compare ), $id . ':output_schema' );
 c12_check( array( $registered['execute_callback'], $registered['permission_callback'] ), array( array( $abilities, $compare ? 'execute_shipping_comparison' : 'execute_shipping_explanation' ), array( $abilities, 'check_permission' ) ), $id . ':callbacks_not_executed' );
}
c12_check( $GLOBALS['c12_reads'], array(), 'registration_zero_private_reads' );
if ( count( $GLOBALS['c12_rows'] ) !== $plan['checks_per_tier'] ) { throw new RuntimeException( 'Declared count mismatch.' ); }
echo json_encode( array( 'status' => 'PASS', 'tier' => $tier, 'checks' => count( $GLOBALS['c12_rows'] ), 'rows' => $GLOBALS['c12_rows'], 'limitations' => 'Synthetic primitive source checks, counting overrides call actual parent methods. Observer threshold source-only; no private state writes/native arming or transport.' ), JSON_PRETTY_PRINT ) . "\n";
