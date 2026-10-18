<?php
/**
 * Source-bound fixed-bracket predicate compatibility unit.
 *
 * CLI arguments: current tier root, original tier root, php72 or php82.
 * No WordPress, WooCommerce, admin, export or shortcode bootstrap is executed.
 *
 * @package Booster\Tests
 */
if ( 'cli' !== PHP_SAPI || 4 !== $argc || ! in_array( $argv[3], array( 'php72', 'php82' ), true ) ) {
	exit( 2 );
}
$rows44     = array();
$failures44 = 0;
$calls44    = array();
$value44    = null;

/**
 * Record one strict assertion.
 *
 * @param string $label Label.
 * @param mixed  $actual Actual value.
 * @param mixed  $expected Expected value.
 * @return void
 */
function wcj_fixed_check44( $label, $actual, $expected ) {
	global $rows44, $failures44;
	$passed   = $actual === $expected;
	$rows44[] = array( 'label' => $label, 'actual' => $actual, 'expected' => $expected, 'passed' => $passed );
	if ( ! $passed ) {
		++$failures44;
	}
}

/**
 * Inert option-read primitive with exact argument recording.
 *
 * @param string $key Key.
 * @param mixed  $default Default.
 * @return mixed
 */
function wcj_get_option( $key, $default ) {
	global $calls44, $value44;
	$calls44[] = array( 'wcj_get_option', array( $key, $default ) );
	return $value44;
}

/**
 * Inert option-read primitive with exact argument recording.
 *
 * @param string $key Key.
 * @return mixed
 */
function get_option( $key ) {
	global $calls44, $value44;
	$calls44[] = array( 'get_option', array( $key ) );
	return $value44;
}

/**
 * Evaluate only one exact source-extracted predicate.
 *
 * @param string $expression Predicate bytes.
 * @param mixed  $value Finite input.
 * @return bool
 */
function wcj_fixed_eval44( $expression, $value ) {
	global $calls44, $value44;
	$calls44               = array();
	$value44               = $value;
	$additional_field_value = $value;
	$i                     = 3;
	$get_payment_notes     = 'test-payment-notes';
	return eval( 'return ' . $expression . ';' );
}
$binding_json44 = <<<'WCJ_FIXED_BINDINGS44'
[{"relative":"includes/class-wcj-admin-orders-list.php","original_sha":"dc31f6beccb02cc1e58ea1270a7d29f3e112043fd6514bb1508c2e7d9277449f","current_sha":"65b1fc377816361c5f94352ac141cb4937afd725a6ed8402db0fc3c3dfb25e19","expressions":[{"label":"admin_option","old":"str_contains( wcj_get_option( 'wcj_orders_list_custom_columns_value_' . $i, '' ), ']' )","new":"false !== strpos( wcj_get_option( 'wcj_orders_list_custom_columns_value_' . $i, '' ), ']' )","old_offset":30150,"calls":[["wcj_get_option",["wcj_orders_list_custom_columns_value_3",""]]],"new_offset":30150}]},{"relative":"includes/export/class-wcj-exporter-orders.php","original_sha":"5b6ee0c3fb762d76c74bbf49f353334c757befca1f93fea55fece936c35e00a6","current_sha":"87e21562dfd1bffd24f7ef20998166d0d1f50ae17fb4b200b9cb9a61518bb9e2","expressions":[{"label":"export_1","old":"str_contains( $additional_field_value, ']' )","new":"false !== strpos( $additional_field_value, ']' )","old_offset":18054,"calls":[],"new_offset":18054},{"label":"export_2","old":"str_contains( $additional_field_value, ']' )","new":"false !== strpos( $additional_field_value, ']' )","old_offset":25871,"calls":[],"new_offset":25875}]},{"relative":"includes/shortcodes/class-wcj-orders-shortcodes.php","original_sha":"07c327a13b6a5cee2be3e07e30566d63c1205f276389f22ff50de2b55dc0ff3a","current_sha":"bd76155a4a7a1fefc80725d0570ddd2387f13b934455650d80f511330b981e1b","expressions":[{"label":"payment_notes","old":"str_contains( get_option( $get_payment_notes ), ']' )","new":"false !== strpos( get_option( $get_payment_notes ), ']' )","old_offset":66760,"calls":[["get_option",["test-payment-notes"]]],"new_offset":66760}]}]
WCJ_FIXED_BINDINGS44;
$case_json44 = <<<'WCJ_FIXED_CASES44'
[{"label":"beginning","value":"]alpha","expected":true},{"label":"middle","value":"alpha]beta","expected":true},{"label":"end","value":"alpha]","expected":true},{"label":"missing","value":"alpha","expected":false},{"label":"empty","value":"","expected":false},{"label":"unicode","value":"caf\u00e9]\u65e5\u672c","expected":true},{"label":"nul","value":"\u0000]\u0000","expected":true},{"label":"multiple","value":"]a]b]","expected":true},{"label":"newline","value":"before\n]after","expected":true},{"label":"scalar_false","value":false,"expected":false},{"label":"scalar_zero","value":0,"expected":false},{"label":"scalar_integer","value":123,"expected":false},{"label":"scalar_true","value":true,"expected":false},{"label":"scalar_float","value":0.5,"expected":false}]
WCJ_FIXED_CASES44;
$bindings44 = json_decode( $binding_json44, true );
$cases44    = json_decode( $case_json44, true );
if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $bindings44 ) || ! is_array( $cases44 ) ) {
	throw new RuntimeException( 'Literal bindings unavailable.' );
}
$expressions44 = array();
foreach ( $bindings44 as $binding44 ) {
	$original44 = file_get_contents( $argv[2] . '/' . $binding44['relative'] );
	$current44  = file_get_contents( $argv[1] . '/' . $binding44['relative'] );
	if ( false === $original44 || false === $current44 ) {
		throw new RuntimeException( 'Bound source unavailable.' );
	}
	wcj_fixed_check44( $binding44['relative'] . ':original_sha', hash( 'sha256', $original44 ), $binding44['original_sha'] );
	wcj_fixed_check44( $binding44['relative'] . ':current_sha', hash( 'sha256', $current44 ), $binding44['current_sha'] );
	$inverse44 = $current44;
	foreach ( array_reverse( $binding44['expressions'] ) as $entry44 ) {
		$old44 = substr( $original44, $entry44['old_offset'], strlen( $entry44['old'] ) );
		$new44 = substr( $current44, $entry44['new_offset'], strlen( $entry44['new'] ) );
		wcj_fixed_check44( $entry44['label'] . ':old_expression', $old44, $entry44['old'] );
		wcj_fixed_check44( $entry44['label'] . ':new_expression', $new44, $entry44['new'] );
		$inverse44 = substr_replace( $inverse44, $old44, $entry44['new_offset'], strlen( $new44 ) );
		$expressions44[] = array( 'entry' => $entry44, 'old' => $old44, 'new' => $new44 );
	}
	wcj_fixed_check44( $binding44['relative'] . ':exact_inverse', $inverse44 === $original44, true );
}
foreach ( $expressions44 as $expression44 ) {
	$entry44 = $expression44['entry'];
	foreach ( $cases44 as $case44 ) {
		$label44  = $entry44['label'] . ':' . $case44['label'];
		$result44 = wcj_fixed_eval44( $expression44['new'], $case44['value'] );
		wcj_fixed_check44( $label44 . ':new', $result44, $case44['expected'] );
		wcj_fixed_check44( $label44 . ':new_calls', $calls44, $entry44['calls'] );
		if ( 'php82' === $argv[3] ) {
			$original_result44 = wcj_fixed_eval44( $expression44['old'], $case44['value'] );
			wcj_fixed_check44( $label44 . ':original_equivalence', $original_result44, $result44 );
			wcj_fixed_check44( $label44 . ':original_calls', $calls44, $entry44['calls'] );
		}
	}
}
$output44 = array(
	'fixture' => 'fixed-bracket-compat',
	'php_version' => PHP_VERSION,
	'mode' => $argv[3],
	'assertions' => count( $rows44 ),
	'failed' => $failures44,
	'rows' => $rows44,
	'product_loaded' => false,
	'limits' => 'Only finite string/scalar inputs and isolated exact predicates; null, arrays, objects, resources and native rendering are unqualified.',
);
$encoded44 = json_encode( $output44, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
if ( false === $encoded44 ) {
	throw new RuntimeException( 'Fixture encoding failed.' );
}
echo $encoded44, PHP_EOL;
exit( 0 === $failures44 ? 0 : 1 );
