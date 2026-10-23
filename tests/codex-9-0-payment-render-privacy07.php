<?php
/** Source-bound M1 regression only. No product loading or native-header simulation. */
function checked( $condition, $label ) {
	if ( ! $condition ) { throw new RuntimeException( $label ); }
}
function method_tokens( $source, $name ) {
	$tokens = token_get_all( $source );
	$count = count( $tokens );
	for ( $i = 0; $i < $count; ++$i ) {
		if ( ! is_array( $tokens[ $i ] ) || T_FUNCTION !== $tokens[ $i ][0] ) { continue; }
		$j = $i + 1;
		while ( $j < $count && is_array( $tokens[ $j ] ) && T_WHITESPACE === $tokens[ $j ][0] ) { ++$j; }
		if ( ! is_array( $tokens[ $j ] ) || T_STRING !== $tokens[ $j ][0] || $name !== $tokens[ $j ][1] ) { continue; }
		$out = array();
		$depth = 0;
		$body = false;
		for ( ; $i < $count; ++$i ) {
			$t = $tokens[ $i ];
			if ( is_array( $t ) && in_array( $t[0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) { continue; }
			$out[] = is_array( $t ) ? array( $t[0], $t[1] ) : $t;
			if ( '{' === $t ) { ++$depth; $body = true; }
			if ( '}' === $t && 0 === --$depth && $body ) { return $out; }
		}
	}
	throw new RuntimeException( 'method_absent:' . $name );
}
try {
	checked( 6 === $argc, 'exact_arguments' );
	$actual_path = dirname( __DIR__ ) . '/includes/class-wcj-rule-explanations.php';
	checked( realpath( $argv[1] ) === realpath( $actual_path ), 'actual_candidate_file' );
	$source = file_get_contents( $actual_path );
	$base = file_get_contents( $argv[3] );
	checked( false !== $source && false !== $base, 'source_read' );
	checked( hash( 'sha256', $source ) === $argv[2] && hash( 'sha256', $base ) === $argv[4], 'external_source_hashes' );
	checked( hash_file( 'sha256', __FILE__ ) === $argv[5], 'fixture_hash' );
	$old = array( 'private static function payment_transport_headers_valid() {', "self::\$payment_transport['failed'] || headers_sent() ||", 'return self::payment_transport_headers_valid();' );
	$new = array( 'private static function payment_transport_headers_valid( $require_writable = true ) {', "self::\$payment_transport['failed'] || ( \$require_writable && headers_sent() ) ||", 'return self::payment_transport_headers_valid( false );' );
	$expected = $base;
	foreach ( $old as $i => $part ) {
		checked( 1 === substr_count( $expected, $part ), 'unique_old_region:' . $i );
		$expected = str_replace( $part, $new[ $i ], $expected );
	}
	checked( $source === $expected, 'complete_source_only_M1_delta' );
	$inverse = $source;
	foreach ( $new as $i => $part ) {
		checked( 1 === substr_count( $inverse, $part ), 'unique_new_region:' . $i );
		$inverse = str_replace( $part, $old[ $i ], $inverse );
	}
	checked( $inverse === $base, 'complete_inverse_source_closure' );
	checked( 3 === substr_count( $source, 'self::payment_transport_headers_valid()' ), 'three_admission_default_true_calls' );
	$header = method_tokens( $source, 'payment_transport_headers_valid' );
	checked( in_array( array( T_VARIABLE, '$require_writable' ), $header, true ), 'typed_source_parameter_token' );
	checked( in_array( array( T_BOOLEAN_AND, '&&' ), $header, true ), 'writable_short_circuit_token' );
	checked( false !== strpos( $source, '$require_writable = true' ), 'admission_default_true' );
	checked( false !== strpos( $source, '( $require_writable && headers_sent() )' ), 'only_writability_gated' );
	$wrapper = method_tokens( $source, 'payment_headers_valid' );
	checked( in_array( array( T_STRING, 'false' ), $wrapper, true ), 'render_confirmation_false_call_token' );
	$unchanged = array( 'check_payment_transport', 'arm_payment_observer', 'render_payment_observer', 'write_payment_transport_headers', 'deny_payment_transport' );
	$proofs = array();
	foreach ( $unchanged as $name ) {
		$a = method_tokens( $base, $name );
		$b = method_tokens( $source, $name );
		checked( $a === $b, 'unchanged_method_tokens:' . $name );
		$proofs[ $name ] = hash( 'sha256', json_encode( $b ) );
	}
	echo json_encode( array( 'pass' => true, 'category' => 'OFFLINE_SOURCE_BOUND_M1_ONLY', 'candidate_sha256' => $argv[2], 'base_sha256' => $argv[4], 'fixture_sha256' => $argv[5], 'default_true_admission_calls' => 3, 'render_requires_writable' => false, 'unchanged_method_token_hashes' => $proofs, 'native_success_proved' => false, 'product_loaded' => false, 'old_suite_replayed' => false ) ) . "\n";
} catch ( Throwable $e ) {
	echo json_encode( array( 'pass' => false, 'error_type' => get_class( $e ), 'code' => $e->getMessage() ) ) . "\n";
	exit( 1 );
}
