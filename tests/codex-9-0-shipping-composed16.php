<?php
/**
 * Booster composed shipping source interaction checks.
 *
 * Development-only CLI fixture. Real service and Abilities code, primitive WP boundaries.
 * No observer arming, Woo session, native request, database or performance measurement.
 *
 * @package Booster_For_WooCommerce/tests
 */
if ( PHP_SAPI !== 'cli' ) {
	exit( 2 );
}
if ( 4 !== $argc ) {
	fwrite( STDERR, "Usage: php fixture.php tier v1-json v2-json\n" );
	exit( 2 );
}
$tier = $argv[1];
define( 'ABSPATH', __DIR__ . '/' );
define( 'WCJ_PLUGIN_FILE', array( 'free' => 'woocommerce-jetpack.php', 'plus' => 'booster-plus-for-woocommerce.php', 'elite' => 'booster-elite-for-woocommerce.php' )[ $tier ] );
$GLOBALS['c16_capable'] = true;
$GLOBALS['c16_options'] = array();
$GLOBALS['c16_modules'] = array();
$GLOBALS['c16_registered'] = array();
$GLOBALS['c16_clock_calls'] = 0;
$GLOBALS['wp_filter'] = array();
$_GET = array();
/** Primitive error boundary. */
class WP_Error {
	public $code;
	public function __construct( $code, $message = '' ) { $this->code = $code; }
	public function get_error_code() { return $this->code; }
}
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function __( $value, $domain = '' ) { return $value; }
function current_user_can( $capability ) { return 'manage_woocommerce' === $capability && $GLOBALS['c16_capable']; }
function add_action( $name, $callback, $priority = 10, $accepted_args = 1 ) { return true; }
function add_filter( $name, $callback, $priority = 10, $accepted_args = 1 ) {
	if ( ! isset( $GLOBALS['wp_filter'][ $name ] ) ) { $GLOBALS['wp_filter'][ $name ] = (object) array( 'callbacks' => array() ); }
	$GLOBALS['wp_filter'][ $name ]->callbacks[ $priority ][] = array( 'function' => $callback, 'accepted_args' => $accepted_args );
	return true;
}
function wcj_get_option( $key, $default = false ) { return array_key_exists( $key, $GLOBALS['c16_options'] ) ? $GLOBALS['c16_options'][ $key ] : $default; }
function get_option( $key, $default = false ) { return wcj_get_option( $key, $default ); }
function wcj_is_module_enabled( $id ) { return false; }
function wcj_is_module_allowed_by_allowlist( $id ) { return true; }
function wcj_get_woocommerce_package_rates_module_filter_priority( $id ) { return 10; }
function current_time( $format ) { ++$GLOBALS['c16_clock_calls']; return '2026-10-14 12:00:00'; }
function w_c_j() { return (object) array( 'all_modules' => $GLOBALS['c16_modules'] ); }
function wp_register_ability( $name, $definition ) { $GLOBALS['c16_registered'][ $name ] = $definition; }
/** Primitive module substrate, not a replacement for either shipping owner. */
#[AllowDynamicProperties]
class WCJ_Module {
	public function __construct() {}
	public function is_enabled() { return 'yes' === wcj_get_option( 'wcj_' . $this->id . '_enabled', 'no' ); }
}
$include = dirname( __DIR__ ) . '/includes/';
foreach ( array( 'class-wcj-status-service.php', 'class-wcj-order-health-service.php', 'class-wcj-returns-summary.php', 'class-wcj-rule-explanations.php', 'class-wcj-abilities.php' ) as $file ) {
	require $include . $file;
}
$expected = json_decode( <<<'C16_EXPECTED'
{"free":{"unarmed_constructor_metadata_absent":false,"execute_shipping_explanation_capability_before_version":"booster_forbidden","execute_shipping_explanation_edition_before_version":"booster_shipping_elite_required","execute_shipping_comparison_capability_before_version":"booster_forbidden","execute_shipping_comparison_edition_before_version":"booster_shipping_elite_required","denial_does_not_load_metadata":false,"real_registration_loads_metadata":true,"explain_complete_v1_input_parity":true,"explain_complete_v2_input_parity":true,"explain_complete_v1_output_parity":true,"explain_complete_v2_output_parity":true,"explain_cache_copy_invariance":true,"explain_repeated_output_invariance":true,"explain_accurate_description":"Version 1 explains amount and quantity predicates; version 2 explains nine bounded Booster shipping conditions and options. Explicit merchant scenarios only, not final shipping availability. Comparisons are independent and not atomic.","explain_actual_execute_callback":true,"explain_actual_permission_callback":true,"explain_registered_input_parity":true,"explain_registered_output_parity":true,"compare_complete_v1_input_parity":true,"compare_complete_v2_input_parity":true,"compare_complete_v1_output_parity":true,"compare_complete_v2_output_parity":true,"compare_cache_copy_invariance":true,"compare_repeated_output_invariance":true,"compare_accurate_description":"Version 1 explains amount and quantity predicates; version 2 explains nine bounded Booster shipping conditions and options. Explicit merchant scenarios only, not final shipping availability. Comparisons are independent and not atomic.","compare_actual_execute_callback":true,"compare_actual_permission_callback":true,"compare_registered_input_parity":true,"compare_registered_output_parity":true,"reversed_int_key_public_result":"booster_shipping_elite_required","reversed_string_key_public_result":"booster_shipping_elite_required","reversed_keys_public_comparison":"booster_shipping_elite_required","int_bool_explanation_joint_type_denied":"booster_shipping_elite_required","int_bool_comparison_joint_type_denied":"booster_shipping_elite_required","string_int_explanation_joint_type_denied":"booster_shipping_elite_required","string_int_comparison_joint_type_denied":"booster_shipping_elite_required","coercible_numeric_string_explanation_joint_type_denied":"booster_shipping_elite_required","coercible_numeric_string_comparison_joint_type_denied":"booster_shipping_elite_required","all_declared_cases_reached":39},"plus":{"unarmed_constructor_metadata_absent":false,"execute_shipping_explanation_capability_before_version":"booster_forbidden","execute_shipping_explanation_edition_before_version":"booster_shipping_elite_required","execute_shipping_comparison_capability_before_version":"booster_forbidden","execute_shipping_comparison_edition_before_version":"booster_shipping_elite_required","denial_does_not_load_metadata":false,"real_registration_loads_metadata":true,"explain_complete_v1_input_parity":true,"explain_complete_v2_input_parity":true,"explain_complete_v1_output_parity":true,"explain_complete_v2_output_parity":true,"explain_cache_copy_invariance":true,"explain_repeated_output_invariance":true,"explain_accurate_description":"Version 1 explains amount and quantity predicates; version 2 explains nine bounded Booster shipping conditions and options. Explicit merchant scenarios only, not final shipping availability. Comparisons are independent and not atomic.","explain_actual_execute_callback":true,"explain_actual_permission_callback":true,"explain_registered_input_parity":true,"explain_registered_output_parity":true,"compare_complete_v1_input_parity":true,"compare_complete_v2_input_parity":true,"compare_complete_v1_output_parity":true,"compare_complete_v2_output_parity":true,"compare_cache_copy_invariance":true,"compare_repeated_output_invariance":true,"compare_accurate_description":"Version 1 explains amount and quantity predicates; version 2 explains nine bounded Booster shipping conditions and options. Explicit merchant scenarios only, not final shipping availability. Comparisons are independent and not atomic.","compare_actual_execute_callback":true,"compare_actual_permission_callback":true,"compare_registered_input_parity":true,"compare_registered_output_parity":true,"reversed_int_key_public_result":"booster_shipping_elite_required","reversed_string_key_public_result":"booster_shipping_elite_required","reversed_keys_public_comparison":"booster_shipping_elite_required","int_bool_explanation_joint_type_denied":"booster_shipping_elite_required","int_bool_comparison_joint_type_denied":"booster_shipping_elite_required","string_int_explanation_joint_type_denied":"booster_shipping_elite_required","string_int_comparison_joint_type_denied":"booster_shipping_elite_required","coercible_numeric_string_explanation_joint_type_denied":"booster_shipping_elite_required","coercible_numeric_string_comparison_joint_type_denied":"booster_shipping_elite_required","all_declared_cases_reached":39},"elite":{"unarmed_constructor_metadata_absent":false,"execute_shipping_explanation_capability_before_version":"booster_forbidden","execute_shipping_explanation_edition_before_version":"booster_shipping_invalid_input","execute_shipping_comparison_capability_before_version":"booster_forbidden","execute_shipping_comparison_edition_before_version":"booster_shipping_invalid_input","denial_does_not_load_metadata":false,"real_registration_loads_metadata":true,"explain_complete_v1_input_parity":true,"explain_complete_v2_input_parity":true,"explain_complete_v1_output_parity":true,"explain_complete_v2_output_parity":true,"explain_cache_copy_invariance":true,"explain_repeated_output_invariance":true,"explain_accurate_description":"Version 1 explains amount and quantity predicates; version 2 explains nine bounded Booster shipping conditions and options. Explicit merchant scenarios only, not final shipping availability. Comparisons are independent and not atomic.","explain_actual_execute_callback":true,"explain_actual_permission_callback":true,"explain_registered_input_parity":true,"explain_registered_output_parity":true,"compare_complete_v1_input_parity":true,"compare_complete_v2_input_parity":true,"compare_complete_v1_output_parity":true,"compare_complete_v2_output_parity":true,"compare_cache_copy_invariance":true,"compare_repeated_output_invariance":true,"compare_accurate_description":"Version 1 explains amount and quantity predicates; version 2 explains nine bounded Booster shipping conditions and options. Explicit merchant scenarios only, not final shipping availability. Comparisons are independent and not atomic.","compare_actual_execute_callback":true,"compare_actual_permission_callback":true,"compare_registered_input_parity":true,"compare_registered_output_parity":true,"reversed_int_key_public_result":"result","reversed_int_nine_rows":9,"reversed_int_schema_valid":true,"reversed_int_not_final_availability":null,"reversed_int_all_modules_absent":["module_not_admitted"],"reversed_string_key_public_result":"result","reversed_string_nine_rows":9,"reversed_string_schema_valid":true,"reversed_string_not_final_availability":null,"reversed_string_all_modules_absent":["module_not_admitted"],"reversed_keys_public_comparison":"result","comparison_two_results":2,"comparison_schema_valid":true,"int_bool_explanation_joint_type_denied":"booster_shipping_invalid_input","int_bool_comparison_joint_type_denied":"booster_shipping_invalid_input","string_int_explanation_joint_type_denied":"booster_shipping_invalid_input","string_int_comparison_joint_type_denied":"booster_shipping_invalid_input","coercible_numeric_string_explanation_joint_type_denied":"booster_shipping_invalid_input","coercible_numeric_string_comparison_joint_type_denied":"booster_shipping_invalid_input","time_129_public_result":"result","time_129_literal_reason":"unsupported_rule_shape","time_129_no_real_clock_read":0,"time_129_full_output_valid":true,"time_128_public_result":"result","time_128_literal_reason":"invalid_time_bypass","time_128_no_real_clock_read":0,"time_128_full_output_valid":true,"all_declared_cases_reached":57}}
C16_EXPECTED
, true );
$observed = array();
$failed = 0;
function c16_check( $label, $actual ) {
	global $expected, $observed, $failed, $tier;
	if ( ! array_key_exists( $label, $expected[ $tier ] ) || array_key_exists( $label, $observed ) ) { throw new RuntimeException( 'Undeclared or repeated case: ' . $label ); }
	$want = $expected[ $tier ][ $label ];
	$pass = $actual === $want;
	$observed[ $label ] = $actual;
	$failed += $pass ? 0 : 1;
	echo json_encode( array( 'case' => $label, 'actual' => $actual, 'actual_type' => get_debug_type( $actual ), 'expected' => $want, 'expected_type' => get_debug_type( $want ), 'pass' => $pass ), JSON_UNESCAPED_SLASHES ) . "\n";
	if ( ! $pass ) { throw new RuntimeException( 'Assertion failed: ' . $label ); }
}
function c16_result( $value ) { return is_wp_error( $value ) ? $value->get_error_code() : 'result'; }
function c16_metadata_loaded() {
	foreach ( get_included_files() as $path ) {
		if ( 'wcj-shipping-v2-metadata.php' === basename( $path ) ) { return true; }
	}
	return false;
}
$service = new WCJ_Rule_Explanations();
$ability = new WCJ_Abilities();
c16_check( 'unarmed_constructor_metadata_absent', c16_metadata_loaded() );
foreach ( array( 'execute_shipping_explanation', 'execute_shipping_comparison' ) as $method ) {
	$GLOBALS['c16_capable'] = false;
	c16_check( $method . '_capability_before_version', c16_result( $ability->$method( array( 'contract_version' => 'unsupported' ) ) ) );
	$GLOBALS['c16_capable'] = true;
	c16_check( $method . '_edition_before_version', c16_result( $ability->$method( array( 'contract_version' => 'unsupported' ) ) ) );
}
c16_check( 'denial_does_not_load_metadata', c16_metadata_loaded() );
$ability->register_abilities();
c16_check( 'real_registration_loads_metadata', c16_metadata_loaded() );
$v1 = json_decode( file_get_contents( $argv[2] ), true );
$v2 = json_decode( file_get_contents( $argv[3] ), true );
foreach ( array( false, true ) as $compare ) {
	$key = $compare ? 'compare' : 'explain';
	$input = $service->get_shipping_dispatch_input_schema( $compare );
	$output = $service->get_shipping_dispatch_output_schema( $compare );
	c16_check( $key . '_complete_v1_input_parity', $input['oneOf'][0] === $v1['input_schemas'][ 'booster/' . $key . '-shipping-options' ] );
	c16_check( $key . '_complete_v2_input_parity', $input['oneOf'][1] === $v2['schemas']['input_schemas'][ $key . '_v2' ] );
	c16_check( $key . '_complete_v1_output_parity', $output['oneOf'][0] === $v1['output_schemas'][ 'booster/' . $key . '-shipping-options' ] );
	c16_check( $key . '_complete_v2_output_parity', $output['oneOf'][1] === $v2['schemas']['output_schemas'][ $compare ? 'comparison_v2' : 'single_v2' ] );
	$original = $input;
	$input['oneOf'][1]['type'] = 'mutation-of-returned-copy';
	c16_check( $key . '_cache_copy_invariance', $service->get_shipping_dispatch_input_schema( $compare ) === $original );
	c16_check( $key . '_repeated_output_invariance', $service->get_shipping_dispatch_output_schema( $compare ) === $output );
	$registration = $GLOBALS['c16_registered'][ 'booster/' . $key . '-shipping-options' ];
	c16_check( $key . '_accurate_description', $registration['description'] );
	c16_check( $key . '_actual_execute_callback', $registration['execute_callback'] === array( $ability, $compare ? 'execute_shipping_comparison' : 'execute_shipping_explanation' ) );
	c16_check( $key . '_actual_permission_callback', $registration['permission_callback'] === array( $ability, 'check_permission' ) );
	c16_check( $key . '_registered_input_parity', $registration['input_schema'] === $original );
	c16_check( $key . '_registered_output_parity', $registration['output_schema'] === $output );
}
$scenario = array( 'contract_version' => 2, 'mode' => 'scenario', 'owners' => array(), 'options' => array(), 'facts' => array() );
foreach ( array( 'shipping_by_order_amount', 'shipping_by_order_qty', 'shipping_by_user_role', 'shipping_by_products', 'shipping_by_cities', 'shipping_by_time' ) as $owner ) {
	$scenario['owners'][ $owner ] = array( 'rule_key' => array( 'kind' => 'method', 'value' => 'flat_rate' ) );
}
foreach ( array( 'shipping_options_most_expensive', 'shipping_options_hide_free', 'shipping_options_free_by_product' ) as $owner ) { $scenario['options'][ $owner ] = array( 'evaluate' => true ); }
foreach ( array( 'amount_quantity', 'products', 'actor', 'address', 'time', 'ordered_rates', 'free_grant' ) as $family ) { $scenario['facts'][ $family ] = array( 'status' => 'unavailable' ); }
$int = $scenario;
$int['facts']['ordered_rates'] = array( 'status' => 'supplied', 'selected_key' => array( 'value' => 5, 'kind' => 'int' ), 'rates' => array( array( 'key' => array( 'kind' => 'int', 'value' => 5 ), 'id' => array( 'value' => 'flat_rate:1', 'kind' => 'string' ), 'method_id' => 'flat_rate', 'cost' => 10 ) ) );
$string = $int;
$string['facts']['ordered_rates']['selected_key'] = array( 'value' => '05', 'kind' => 'string' );
$string['facts']['ordered_rates']['rates'][0]['key'] = array( 'kind' => 'string', 'value' => '05' );
foreach ( array( 'int' => $int, 'string' => $string ) as $name => $input ) {
	$result = $ability->execute_shipping_explanation( $input );
	c16_check( 'reversed_' . $name . '_key_public_result', c16_result( $result ) );
	if ( 'elite' === $tier ) {
		c16_check( 'reversed_' . $name . '_nine_rows', count( $result['rows'] ) );
		c16_check( 'reversed_' . $name . '_schema_valid', $service->validate_shipping_output( $result, false ) );
		c16_check( 'reversed_' . $name . '_not_final_availability', $result['final_availability'] );
		c16_check( 'reversed_' . $name . '_all_modules_absent', array_values( array_unique( array_column( $result['rows'], 'reason' ) ) ) );
	}
}
$comparison = array( 'contract_version' => 2, 'mode' => 'compare', 'scenarios' => array( $int, $string ) );
$result = $ability->execute_shipping_comparison( $comparison );
c16_check( 'reversed_keys_public_comparison', c16_result( $result ) );
if ( 'elite' === $tier ) {
	c16_check( 'comparison_two_results', count( $result['results'] ) );
	c16_check( 'comparison_schema_valid', $service->validate_shipping_output( $result, true ) );
}
foreach ( array( 'int_bool' => array( 'value' => true, 'kind' => 'int' ), 'string_int' => array( 'value' => 5, 'kind' => 'string' ), 'coercible_numeric_string' => array( 'value' => '5', 'kind' => 'string' ) ) as $name => $key ) {
	$bad = $int;
	$bad['facts']['ordered_rates']['selected_key'] = $key;
	c16_check( $name . '_explanation_joint_type_denied', c16_result( $ability->execute_shipping_explanation( $bad ) ) );
	$bad_compare = $comparison;
	$bad_compare['scenarios'][1] = $bad;
	c16_check( $name . '_comparison_joint_type_denied', c16_result( $ability->execute_shipping_comparison( $bad_compare ) ) );
}
if ( 'elite' === $tier ) {
	$GLOBALS['c16_options']['wcj_shipping_by_time_enabled'] = 'yes';
	require $include . 'classes/class-wcj-module-shipping-by-condition.php';
	$time = require $include . 'class-wcj-shipping-by-time.php';
	$GLOBALS['c16_modules']['shipping_by_time'] = $time;
	$time_input = $scenario;
	$time_input['facts']['time'] = array( 'status' => 'supplied', 'real_clock_consent' => true );
	foreach ( array( 129, 128 ) as $length ) {
		$GLOBALS['c16_options']['wcj_shipping_time_include_flat_rate'] = str_repeat( 'x', $length );
		$GLOBALS['c16_clock_calls'] = 0;
		$result = $ability->execute_shipping_explanation( $time_input );
		c16_check( 'time_' . $length . '_public_result', c16_result( $result ) );
		$row = null;
		foreach ( $result['rows'] as $item ) { if ( 'shipping_by_time' === $item['owner'] ) { $row = $item; } }
		c16_check( 'time_' . $length . '_literal_reason', $row['reason'] );
		c16_check( 'time_' . $length . '_no_real_clock_read', $GLOBALS['c16_clock_calls'] );
		c16_check( 'time_' . $length . '_full_output_valid', $service->validate_shipping_output( $result, false ) );
	}
}
c16_check( 'all_declared_cases_reached', count( $observed ) + 1 );
echo json_encode( array( 'status' => 'PASS', 'tier' => $tier, 'assertions' => count( $observed ), 'failures' => $failed, 'native' => false, 'included_files_membership_only' => get_included_files() ), JSON_UNESCAPED_SLASHES ) . "\n";
exit( 0 );
