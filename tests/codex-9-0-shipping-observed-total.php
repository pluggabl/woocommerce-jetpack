<?php
/**
 * Exact-source observed amount predicate proof. No product class is loaded.
 *
 * @package Booster_For_WooCommerce/tests
 */
error_reporting( E_ALL );
set_error_handler( static function ( $severity, $message, $file, $line ) { throw new ErrorException( $message, 0, $severity, $file, $line ); } );
function codex_check( $condition, $label ) {
	if ( true !== $condition ) { throw new RuntimeException( $label ); }
}
$source_path = dirname( __DIR__ ) . '/includes/class-wcj-rule-explanations.php';
$base_path   = isset( $argv[1] ) ? $argv[1] : '';
$source      = file_get_contents( $source_path );
$base        = file_get_contents( $base_path );
codex_check( 'eadb18e6886e00d8bca73d7ada86e9e9462114397b01dd793d1a720cfd462785' === hash( 'sha256', $source ), 'candidate_source_hash' );
codex_check( '2e0a98b1db27046255ae48246a2acfdb07888928ea3142d5333820e5f9674038' === hash( 'sha256', $base ), 'base_source_hash' );
$old_line = base64_decode( 'CQkJZWxzZWlmICggISBpc3NldCggJGNbJ3ZhbHVlJ10gKSB8fCAoICdzaGlwcGluZ19ieV9vcmRlcl9hbW91bnQnID09PSAkdGlja2V0ID8gKCAhIGlzX2ludCggJGNbJ3ZhbHVlJ10gKSAmJiAhIGlzX2Zsb2F0KCAkY1sndmFsdWUnXSApICkgOiAhIGlzX2ludCggJGNbJ3ZhbHVlJ10gKSApIHx8ICEgaXNfZmluaXRlKCAoZmxvYXQpICRjWyd2YWx1ZSddICkgfHwgJGNbJ3ZhbHVlJ10gPCAwIHx8ICRjWyd2YWx1ZSddID4gKCAnc2hpcHBpbmdfYnlfb3JkZXJfYW1vdW50JyA9PT0gJHRpY2tldCA/IDEwMDAwMDAwMDAgOiAxMDAwMDAwICkgKSB7ICRyZWFzb24gPSAndW5zdXBwb3J0ZWRfb2JzZXJ2ZWRfZmFjdHMnOyB9', true );
$new_line = base64_decode( 'CQkJZWxzZWlmICggISBpc3NldCggJGNbJ3ZhbHVlJ10gKSB8fCAoICdzaGlwcGluZ19ieV9vcmRlcl9hbW91bnQnID09PSAkdGlja2V0ID8gISAkcy0+c2hpcHBpbmdfYm91bmRfdmFsaWQoICRjWyd2YWx1ZSddICkgOiAhIGlzX2ludCggJGNbJ3ZhbHVlJ10gKSApIHx8ICEgaXNfZmluaXRlKCAoZmxvYXQpICRjWyd2YWx1ZSddICkgfHwgJGNbJ3ZhbHVlJ10gPCAwIHx8ICRjWyd2YWx1ZSddID4gKCAnc2hpcHBpbmdfYnlfb3JkZXJfYW1vdW50JyA9PT0gJHRpY2tldCA/IDEwMDAwMDAwMDAgOiAxMDAwMDAwICkgKSB7ICRyZWFzb24gPSAndW5zdXBwb3J0ZWRfb2JzZXJ2ZWRfZmFjdHMnOyB9', true );
codex_check( 1 === substr_count( $source, $new_line ) && 0 === substr_count( $source, $old_line ), 'exact_one_replacement' );
codex_check( str_replace( $new_line, $old_line, $source ) === $base, 'full_service_byte_inverse' );
function codex_tokens( $text ) {
	$out = array();
	foreach ( token_get_all( $text ) as $token ) {
		if ( is_array( $token ) ) {
			if ( in_array( $token[0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) { continue; }
			$out[] = array( token_name( $token[0] ), $token[1] );
		} else { $out[] = $token; }
	}
	return $out;
}
codex_check( codex_tokens( str_replace( $new_line, $old_line, $source ) ) === codex_tokens( $base ), 'full_service_inverse_tokens' );
function codex_method( $text, $name ) {
	$count = preg_match_all( '/\t\tprivate function ' . preg_quote( $name, '/' ) . '\(.*?\n\t\t\}/s', $text, $matches );
	codex_check( 1 === $count, 'single_exact_method_' . $name );
	return $matches[0][0];
}
$methods = array();
foreach ( array( 'shipping_exact', 'shipping_bound_valid', 'shipping_facts_valid' ) as $name ) {
	$methods[ $name ] = codex_method( $source, $name );
	codex_check( $methods[ $name ] === codex_method( $base, $name ), 'unchanged_actual_helper_' . $name );
}
// Only these exact extracted pure private methods and the exact predicate line execute.
// No WCJ object, native state, header override, transport arm, or product callback is loaded.
$body = 'class Codex_Observed_Total_Predicate {' . implode( "\n", $methods );
foreach ( array( 'current' => $new_line, 'previous' => $old_line ) as $name => $line ) {
	$body .= 'public function ' . $name . '( $v, $owner ) { $s = $this; $c = array( "value" => $v ); $ticket = $owner; $reason = null; if ( false ) { $reason = "unreachable"; } ' . $line . ' else { $reason = "supported"; } return "supported" === $reason; }';
}
$body .= 'public function facts( $f ) { return $this->shipping_facts_valid( $f ); } }';
eval( $body ); // Explicit lower-strength exact-source extraction, never a native observer proof.
$predicate = new Codex_Observed_Total_Predicate();
$string_calls = 0;
$stringable = new class( $string_calls ) {
	private $calls;
	public function __construct( &$calls ) { $this->calls =& $calls; }
	public function __toString() { ++$this->calls; return '20'; }
};
$resource = fopen( 'php://memory', 'r+' );
$cases = array(
	array( 'int20', 20, true ), array( 'float20', 20.0, true ),
	array( 'woo_trimmed20', '20', true ), array( 'woo_trimmed20_5', '20.5', true ), array( 'woo_trimmed0', '0', true ),
	array( 'filtered20_00', '20.00', true ), array( 'positive_sign', '+20', true ), array( 'leading_decimal', '.5', true ),
	array( 'trailing_decimal', '20.', true ), array( 'leading_zeroes', '00', true ), array( 'minus_zero', '-0', true ),
	array( 'minus_zero_decimal', '-0.0', true ), array( 'float_minus_zero', -0.0, true ),
	array( 'zero', 0, true ), array( 'upper_int', 1000000000, true ), array( 'upper_string', '1000000000', true ),
	array( 'decimal_rounds_to_upper', '1000000000.0000000001', true ),
	array( 'valid64bytes', str_repeat( '0', 62 ) . '.0', true ),
	array( 'negative_string', '-0.1', false ), array( 'negative_int', -1, false ), array( 'negative_float', -0.1, false ),
	array( 'upper_decimal_exceeded', '1000000000.01', false ), array( 'upper_int_exceeded', 1000000001, false ),
	array( 'php_int_max', PHP_INT_MAX, false ), array( 'php_int_min', PHP_INT_MIN, false ),
	array( 'scientific', '1e2', false ), array( 'leading_space', ' 20', false ), array( 'trailing_space', '20 ', false ),
	array( 'trailing_lf', "20\n", false ), array( 'trailing_cr', "20\r", false ), array( 'tab', "20\t", false ),
	array( 'empty', '', false ), array( 'bool_true', true, false ), array( 'bool_false', false, false ), array( 'null', null, false ),
	array( 'array_empty', array(), false ), array( 'array_value', array( 20 ), false ), array( 'object', new stdClass(), false ),
	array( 'stringable_object', $stringable, false ), array( 'resource', $resource, false ),
	array( 'infinity', INF, false ), array( 'minus_infinity', -INF, false ), array( 'nan', NAN, false ),
	array( 'text_inf', 'INF', false ), array( 'text_nan', 'NAN', false ),
	array( 'invalid65bytes', str_repeat( '0', 63 ) . '.0', false ), array( '64digit_overflow', str_repeat( '9', 64 ), false ),
	array( 'lone_plus', '+', false ), array( 'lone_minus', '-', false ), array( 'lone_dot', '.', false ),
	array( 'hex', '0x14', false ), array( 'comma', '20,00', false ), array( 'nul', "20\0", false ),
	array( 'arabic_digits', "\xD9\xA2\xD9\xA0", false )
);
$observations = array();
foreach ( $cases as $case ) {
	list( $name, $value, $expected ) = $case;
	$actual = $predicate->current( $value, 'shipping_by_order_amount' );
	$old    = $predicate->previous( $value, 'shipping_by_order_amount' );
	codex_check( $actual === $expected, 'amount_' . $name );
	codex_check( is_string( $value ) ? false === $old : $actual === $old, 'old_nonstring_equivalence_' . $name );
	codex_check( $predicate->current( $value, 'shipping_by_order_qty' ) === $predicate->previous( $value, 'shipping_by_order_qty' ), 'quantity_inverse_' . $name );
	$observations[] = array( 'case' => $name, 'supported' => $actual, 'old_supported' => $old, 'type' => gettype( $value ) );
}
foreach ( array( array( 'qty0', 0, true ), array( 'qty_upper', 1000000, true ), array( 'qty_over', 1000001, false ), array( 'qty_float', 2.0, false ), array( 'qty_string', '2', false ) ) as $case ) {
	codex_check( $case[2] === $predicate->current( $case[1], 'shipping_by_order_qty' ), $case[0] );
}
foreach ( array( array( 'scenario_int', 20, true ), array( 'scenario_float', 20.5, true ), array( 'scenario_string', '20', false ), array( 'scenario_string_zero', '0', false ), array( 'scenario_bool', true, false ), array( 'scenario_negative', -1, false ), array( 'scenario_nan', NAN, false ) ) as $case ) {
	$facts = array( 'cart_present' => true, 'cart_empty' => false, 'contents_total' => $case[1], 'contents_count' => 2 );
	codex_check( $case[2] === $predicate->facts( $facts ), $case[0] );
}
codex_check( 0 === $string_calls, 'no_stringable_cast' );
fclose( $resource );
$method_hashes = array();
foreach ( $methods as $name => $bytes ) { $method_hashes[ $name ] = hash( 'sha256', $bytes ); }
echo json_encode( array( 'pass' => true, 'proof' => 'LOWER_STRENGTH_EXACT_SOURCE_PREDICATE_EXTRACTION_NO_NATIVE_STATE', 'candidate_sha256' => hash( 'sha256', $source ), 'base_sha256' => hash( 'sha256', $base ), 'amount_cases' => $observations, 'quantity_extra_cases' => 5, 'scenario_cases' => 7, 'method_hashes' => $method_hashes, 'full_byte_inverse' => true, 'full_inverse_tokens' => true, 'ordinary_callbacks_executed' => false, 'native_or_arm_proof' => false ), JSON_THROW_ON_ERROR ) . "\n";
