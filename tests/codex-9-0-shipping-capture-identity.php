<?php
/** Source/token/inverse-only selected-rate capture regression. No product loading or state injection. */
function require_capture( $value, $code ) {
	if ( ! $value ) { throw new RuntimeException( $code ); }
}
function capture_method_tokens( $source ) {
	$tokens = token_get_all( $source );
	$methods = array();
	$n = count( $tokens );
	for ( $i = 0; $i < $n; ++$i ) {
		if ( ! is_array( $tokens[ $i ] ) || T_FUNCTION !== $tokens[ $i ][0] ) { continue; }
		$j = $i + 1;
		while ( $j < $n && is_array( $tokens[ $j ] ) && T_WHITESPACE === $tokens[ $j ][0] ) { ++$j; }
		if ( $j >= $n || ! is_array( $tokens[ $j ] ) || T_STRING !== $tokens[ $j ][0] ) { continue; }
		$name = $tokens[ $j ][1];
		$out = array(); $depth = 0; $body = false;
		for ( ; $i < $n; ++$i ) {
			$t = $tokens[ $i ];
			if ( is_array( $t ) && in_array( $t[0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) { continue; }
			$out[] = is_array( $t ) ? array( $t[0], $t[1] ) : $t;
			if ( '{' === $t ) { ++$depth; $body = true; }
			if ( '}' === $t ) { --$depth; if ( $body && 0 === $depth ) { break; } }
		}
		require_capture( ! isset( $methods[ $name ] ) && $body && 0 === $depth, 'unique_complete_method:' . $name );
		$methods[ $name ] = $out;
	}
	return $methods;
}
try {
	require_capture( 7 === $argc && in_array( $argv[1], array( 'free', 'plus', 'elite' ), true ), 'exact_arguments' );
	$actual = dirname( __DIR__ ) . '/includes/class-wcj-rule-explanations.php';
	require_capture( realpath( $argv[2] ) === realpath( $actual ), 'actual_candidate' );
	$source = file_get_contents( $actual ); $base = file_get_contents( $argv[4] );
	require_capture( false !== $source && false !== $base, 'source_reads' );
	require_capture( hash( 'sha256', $source ) === $argv[3] && hash( 'sha256', $base ) === $argv[5] && hash_file( 'sha256', __FILE__ ) === $argv[6], 'external_complete_byte_pins' );
	$match = "if ( self::\$shipping_generation['modules'][ \$owner ] === \$module && \$capture['object'] === \$rate ) { ";
	$key_write = <<<'KEY_WRITE'
$capture['keys'][ $phase ] = $key; $capture['modes'][ $phase ] = $mode; break; }
KEY_WRITE;
	$bounds_write = <<<'BOUNDS_WRITE'
$capture['bounds'] = array( $min, $max ); $capture['value'] = $value; $capture['branch'] = $branch; break; }
BOUNDS_WRITE;
	$key_guard = <<<'KEY_GUARD'
if ( array_key_exists( $phase, $capture['keys'] ) ) { self::$shipping_generation['state'] = 'invalid'; unset( $capture ); return $key; }
KEY_GUARD;
	$bounds_guard = <<<'BOUNDS_GUARD'
if ( ! empty( $capture['bounds'] ) ) { self::$shipping_generation['state'] = 'invalid'; unset( $capture ); return; }
BOUNDS_GUARD;
	$key_guard .= ' '; $bounds_guard .= ' ';
	$old = array( $match . $key_write, $match . $bounds_write );
	$new = array( $match . $key_guard . $key_write, $match . $bounds_guard . $bounds_write );
	$expected = $base; $inverse = $source;
	foreach ( $old as $i => $part ) {
		require_capture( 1 === substr_count( $base, $part ) && 1 === substr_count( $source, $new[ $i ] ), 'unique_matching_branch_before_assignment:' . $i );
		$expected = str_replace( $part, $new[ $i ], $expected );
		$inverse = str_replace( $new[ $i ], $part, $inverse );
	}
	require_capture( $expected === $source && $inverse === $base, 'complete_two_guard_forward_inverse_only' );
	$a = capture_method_tokens( $base ); $b = capture_method_tokens( $source ); $expected_tokens = capture_method_tokens( $expected );
	require_capture( array_keys( $a ) === array_keys( $b ) && $b === $expected_tokens, 'complete_method_namespace_and_expected_tokens' );
	$changed = array(); $unchanged = array();
	foreach ( $a as $name => $tokens ) {
		if ( $tokens !== $b[ $name ] ) { $changed[] = $name; } else { $unchanged[ $name ] = hash( 'sha256', json_encode( $tokens ) ); }
	}
	require_capture( array( 'shipping_capture_rule_key', 'shipping_capture_bounds' ) === $changed, 'only_two_changed_method_token_bodies' );
	foreach ( array( 'shipping_start', 'shipping_finish', 'get_shipping_observation', 'arm_shipping_observer', 'initialize_shipping_transport', 'payment_start', 'payment_finish' ) as $name ) {
		require_capture( isset( $unchanged[ $name ] ), 'unchanged_normal_or_prearm_method:' . $name );
	}
	$original_first = array( $key_write, $bounds_write );
	foreach ( $original_first as $part ) { require_capture( 1 === substr_count( $source, $part ), 'first_capture_assignments_and_returns_preserved' ); }
	echo json_encode( array( 'status' => 'PASS_SOURCE_TOKEN_INVERSE_CAPTURE_GUARDS_ONLY', 'tier' => $argv[1], 'candidate_sha256' => $argv[3], 'base_sha256' => $argv[5], 'fixture_sha256' => $argv[6], 'changed_methods' => $changed, 'unchanged_method_token_hashes' => $unchanged, 'first_capture_bytes_preserved' => true, 'duplicate_rule_phase_source_guard' => 'array_key_exists before keys/modes assignment; mark invalid, release reference, return unchanged key', 'duplicate_bounds_source_guard' => 'nonempty bounds before bounds/value/branch assignment; mark invalid, release reference, return void', 'product_loaded' => false, 'armed_state_injected' => false, 'native_alias_generation_HTTP_outcomes' => 'UNQUALIFIED', 'prior_tests_replayed' => false ) ) . "\n";
} catch ( Throwable $e ) {
	echo json_encode( array( 'status' => 'STOP', 'type' => get_class( $e ), 'code' => $e->getMessage(), 'product_loaded' => false ) ) . "\n";
	exit( 1 );
}
