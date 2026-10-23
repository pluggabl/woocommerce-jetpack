<?php
/**
 * Source-bound semantic49 fixture. Unchanged prepared43 cases, new exact source identities.
 * Actual class declaration is imported without include-time hooks or WP bootstrap.
 * No fake armed headers/trace, no product class doubles, no cart/order mutation.
 * @package Booster_For_WooCommerce/tests
 */
if ( 'cli' !== PHP_SAPI || ! isset( $argv[1] ) || isset( $argv[2] ) ) {
 exit( 2 );
}
$semantic43_tier = 'free';
$semantic43_allowed = array( '98a7282ddc0f63c2c58b27e0b82de7f0b46af624ab95fabcebe55045acf412e1', '8bdd02c939bcb5452967a682c27a025f2454fd8f2065bc0581f2f44c4893750d' );
$semantic43_path = rtrim( $argv[1], '/\\' ) . '/includes/class-wcj-rule-explanations.php';
$semantic43_source = file_get_contents( $semantic43_path );
$semantic43_digest = hash( 'sha256', $semantic43_source );
if ( ! in_array( $semantic43_digest, $semantic43_allowed, true ) ) { exit( 3 ); }
$semantic43_rows = array();
set_error_handler( function ( $level, $message, $file, $line ) { throw new ErrorException( $message, 0, $level, $file, $line ); } );
function semantic43_assert( $id, $actual, $expected ) {
 global $semantic43_rows;
 $semantic43_rows[] = array( 'id' => $id, 'pass' => $actual === $expected, 'actual' => $actual, 'expected' => $expected );
}
function semantic43_invoke( $instance, $name, $arguments ) {
 $method = new ReflectionMethod( 'WCJ_Rule_Explanations', $name );
 $method->setAccessible( true );
 return $method->invokeArgs( $instance, $arguments );
}
function wcj_get_option( $key, $default = '' ) {
 $GLOBALS['semantic43_option_reads'][] = $key;
 return $default;
}
function apply_filters( $hook, $value ) {
 $GLOBALS['semantic43_filter_reads'][] = $hook;
 if ( 'booster_option' !== $hook || array() === $GLOBALS['semantic43_script'] ) {
  throw new RuntimeException( 'Unexpected callback boundary.' );
 }
 return array_shift( $GLOBALS['semantic43_script'] );
}
try {
 // Exact production class text, not copied or reimplemented methods.
 $start = strpos( $semantic43_source, "\tclass WCJ_Rule_Explanations {" );
 if ( false === $start ) { throw new RuntimeException( 'Class anchor missing.' ); }
 $tokens = token_get_all( '<?php ' . substr( $semantic43_source, $start ) );
 $class = ''; $level = 0; $opened = false;
 foreach ( $tokens as $token ) {
  if ( is_array( $token ) && T_OPEN_TAG === $token[0] ) { continue; }
  $text = is_array( $token ) ? $token[1] : $token;
  $class .= $text;
  if ( ( ! is_array( $token ) && '{' === $token ) || ( is_array( $token ) && in_array( $token[0], array( T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES ), true ) ) ) { ++$level; $opened = true; }
  if ( ! is_array( $token ) && '}' === $token ) { --$level; }
  if ( $opened && 0 === $level ) { break; }
 }
 if ( ! $opened || 0 !== $level ) { throw new RuntimeException( 'Class token boundary missing.' ); }
 eval( $class ); // Future test-only actual class import, not a runtime change.
 $instance = ( new ReflectionClass( 'WCJ_Rule_Explanations' ) )->newInstanceWithoutConstructor();
 $zero_cases = array(
  array( 'int-zero', 0, true ), array( 'float-zero', 0.0, true ), array( 'negative-float-zero', -0.0, true ),
  array( 'positive-int', 1, false ), array( 'positive-float', 0.1, false ), array( 'negative', -1, false ),
  array( 'numeric-string', '0', false ), array( 'boolean', false, false ), array( 'null', null, false ),
  array( 'nan', NAN, false ), array( 'infinity', INF, false ), array( 'over-bound', 1000000001, false ),
 );
 foreach ( $zero_cases as $case ) {
  $facts = array( 'cart_present' => false, 'cart_empty' => true, 'contents_total' => $case[1], 'contents_count' => 0 );
  semantic43_assert( 'strict-zero/' . $case[0], semantic43_invoke( $instance, 'shipping_facts_valid', array( $facts ) ), $case[2] );
 }
 $facts = array( 'cart_present' => true, 'cart_empty' => false, 'contents_total' => 14.25, 'contents_count' => 2 );
 semantic43_assert( 'strict-zero/nonempty-finite', semantic43_invoke( $instance, 'shipping_facts_valid', array( $facts ) ), true );
 $tags = array(
  array( 'unknown-default', array( 'state' => 'unknown' ), 'country', null, true ),
  array( 'empty-default', array( 'state' => 'known_empty' ), 'country', null, false ),
  array( 'empty-false', array( 'state' => 'known_empty' ), 'country', false, false ),
  array( 'empty-true', array( 'state' => 'known_empty' ), 'country', true, true ),
  array( 'known-country', array( 'state' => 'known', 'value' => 'US' ), 'country', false, true ),
  array( 'country-malformed', array( 'state' => 'known', 'value' => 'us' ), 'country', true, false ),
  array( 'boolean-false', array( 'state' => 'known', 'value' => false ), 'boolean', false, true ),
  array( 'unknown-extra', array( 'state' => 'unknown', 'value' => '' ), 'country', true, false ),
 );
 foreach ( $tags as $case ) {
  $args = array( $case[1], $case[2] );
  if ( null !== $case[3] ) { $args[] = $case[3]; }
  semantic43_assert( 'private-tag/' . $case[0], semantic43_invoke( $instance, 'payment_tag', $args ), $case[4] );
 }
 $parsers = array(
  array( 'empty', '', false ), array( 'object', '{"alpha":[]}', true ), array( 'array', '[true,false,null,-0.5e2]', true ),
  array( 'duplicate-key', '{"a":1,"a":2}', false ), array( 'unicode-duplicate-key', '{"a":1,"\u0061":2}', false ),
  array( 'numeric-looking-distinct', '{"1":1,"01":2}', true ), array( 'nested-scopes', '{"a":{"a":1},"b":{"a":2}}', true ),
  array( 'unterminated-string', '["abc]', false ), array( 'trailing-backslash', '["abc\\', false ),
  array( 'escaped-quote', '["a' . chr( 92 ) . '"b"]', true ), array( 'trailing', '{}x', false ),
  array( 'depth12-direct-scan', str_repeat( '[', 12 ) . '0' . str_repeat( ']', 12 ), true ),
  array( 'depth13-direct-scan', str_repeat( '[', 13 ) . '0' . str_repeat( ']', 13 ), false ),
  array( '8192-direct-scan-only', '"' . str_repeat( 'a', 8190 ) . '"', true ),
  array( '8193-direct-scan-only-not-transport', '"' . str_repeat( 'a', 8191 ) . '"', true ),
 );
 foreach ( $parsers as $case ) {
  $index = 0;
  $args = array( $case[1], &$index, 0 );
  $ok = semantic43_invoke( $instance, 'shipping_v2_scan', $args );
  $index += strspn( $case[1], " \t\r\n", $index );
  semantic43_assert( 'parser/' . $case[0], $ok && strlen( $case[1] ) === $index, $case[2] );
 }
 $option_cases = array(
  array( 'free-only', 'free_shipping:1', array(), 'kept_free_key', 0 ),
  array( 'local-first-true', 'local_pickup:2', array( 'except_local_pickup' ), 'kept_local_key', 1 ),
  array( 'nonflat-second-true', 'custom:2', array( 'except_local_pickup', 'flat_rate_only' ), 'kept_nonflat_key', 2 ),
  array( 'flat-second-true', 'flat_rate:2', array( 'hide_all', 'flat_rate_only' ), 'hidden_by_free', 2 ),
  array( 'changing-results-not-cached', 'local_pickup:2', array( 'flat_rate_only', 'hide_all' ), 'hidden_by_free', 2 ),
 );
 foreach ( $option_cases as $case ) {
  $GLOBALS['semantic43_option_reads'] = array(); $GLOBALS['semantic43_filter_reads'] = array(); $GLOBALS['semantic43_script'] = $case[2];
  $keys = 'free_shipping:1' === $case[1] ? array( 'free_shipping:1' ) : array( 'free_shipping:1', $case[1] );
  $facts = array( 'rates' => array(), 'selected_key' => array( 'kind' => 'string', 'value' => $case[1] ) );
  foreach ( $keys as $key ) { $facts['rates'][] = array( 'key' => array( 'kind' => 'string', 'value' => $key ), 'id' => array( 'value' => $key ), 'method_id' => explode( ':', $key )[0], 'cost' => 0 ); }
  $result = semantic43_invoke( $instance, 'shipping_v2_options_scenario', array( 'shipping_options_hide_free', $facts ) );
  semantic43_assert( 'option-reason/' . $case[0], $result, $case[3] );
  semantic43_assert( 'option-order/' . $case[0], $GLOBALS['semantic43_option_reads'], array_fill( 0, $case[4], 'wcj_shipping_hide_if_free_available_type' ) );
  semantic43_assert( 'filter-order/' . $case[0], $GLOBALS['semantic43_filter_reads'], array_fill( 0, $case[4], 'booster_option' ) );
  semantic43_assert( 'filter-script-consumed/' . $case[0], $GLOBALS['semantic43_script'], array() );
 }
 // No positive armed/provenance state is manufactured. Natural default only.
 $packages = array( 'literal43' => array( 'marker' => 'unchanged' ) );
 semantic43_assert( 'guarded-assignment/naturally-unarmed-pass-through', $instance->open_shipping_generation( $packages ), $packages );
 $start = strpos( $semantic43_source, 'public function open_shipping_generation(' );
 $end = strpos( $semantic43_source, "\n\t\t}", $start );
 $body = substr( $semantic43_source, $start, $end - $start );
 if ( '8bdd02c939bcb5452967a682c27a025f2454fd8f2065bc0581f2f44c4893750d' === $semantic43_digest ) {
  $position = strpos( $body, '$id = $this->shipping_packages_valid( $packages, true );' );
  semantic43_assert( 'guarded-assignment/source-only-single-call', substr_count( $body, 'shipping_packages_valid(' ), 1 );
  semantic43_assert( 'guarded-assignment/source-only-preceding-return', substr_count( substr( $body, 0, $position ), 'return $packages;' ) >= 2, true );
 }
 $failures = array_filter( $semantic43_rows, function ( $row ) { return ! $row['pass']; } );
 echo json_encode( array( 'tier' => $semantic43_tier, 'source_sha256' => $semantic43_digest, 'cases' => $semantic43_rows, 'status' => array() === $failures ? 'PASS' : 'FAIL', 'limitations' => array( 'Synthetic option/filter adapters only; not live WordPress.', 'Positive armed package validation call counts and all native transport remain UNQUALIFIED.', 'Two proposed passive Yoda edits have static source proof only; eight dynamic comparisons remain withheld.', 'Private scanner8193 acceptance is not public8192 transport acceptance.' ) ), JSON_PRETTY_PRINT ) . PHP_EOL;
 exit( array() === $failures ? 0 : 1 );
} catch ( Throwable $error ) {
 echo json_encode( array( 'tier' => $semantic43_tier, 'status' => 'FIXTURE_ERROR', 'cases' => $semantic43_rows, 'exception_class' => get_class( $error ) ) ) . PHP_EOL;
 exit( 2 );
}
