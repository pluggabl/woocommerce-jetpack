<?php
/**
 * Explicit decoder defaults, source-bound expression fixture.
 *
 * CLI development asset only. The six actual exporter statements are evaluated,
 * with inert metadata/shortcode providers and a bounded strip_tags stand-in.
 * Native html_entity_decode performs every decode. This is not a full export.
 *
 * @package Booster_For_WooCommerce
 */
namespace WCJ_Decode78;

if ( 'cli' !== PHP_SAPI ) {
	exit;
}

set_error_handler(
	static function ( $severity, $message, $file, $line ) {
		throw new \ErrorException( $message, 0, $severity, $file, $line );
	}
);

/**
 * Record arguments, then call the real native decoder.
 *
 * @return string
 */
function html_entity_decode() {
	$args78                       = func_get_args();
	$GLOBALS['decode78_args'][]   = array( bin2hex( $args78[0] ), array_slice( $args78, 1 ) );
	$value78                      = \html_entity_decode( ...$args78 );
	$GLOBALS['decode78_values'][] = bin2hex( $value78 );
	return $value78;
}

/**
 * Record input and perform bounded tag stripping.
 *
 * @param string $value Value to strip.
 * @return string
 */
function wp_strip_all_tags( $value ) {
	$GLOBALS['strip78'][] = bin2hex( $value );
	return strip_tags( $value );
}

/**
 * Inert shortcode provider.
 *
 * @param string $value Source expression argument.
 * @return string
 */
function do_shortcode( $value ) {
	$GLOBALS['provider78'][] = array( 'shortcode', $value );
	return $GLOBALS['input78'];
}

/**
 * Inert metadata provider. No WooCommerce object or database is used.
 */
class Provider78 {
	/**
	 * Record legacy meta expression.
	 *
	 * @param int    $id Order identity.
	 * @param string $key Metadata key.
	 * @return string
	 */
	public function safely_get_post_meta( $id, $key ) {
		$GLOBALS['provider78'][] = array( 'legacy_meta', $id, $key );
		return $GLOBALS['input78'];
	}

	/**
	 * Record CRUD meta expression.
	 *
	 * @param string $key Metadata key.
	 * @return string
	 */
	public function get_meta( $key ) {
		$GLOBALS['provider78'][] = array( 'order_meta', $key );
		return $GLOBALS['input78'];
	}
}

$checks78 = array();

/**
 * Retain typed literal expectations.
 *
 * @param string $name Assertion name.
 * @param mixed  $actual Actual value.
 * @param mixed  $expected Expected value.
 */
function check78( $name, $actual, $expected ) {
	$GLOBALS['checks78'][] = array(
		'name'     => $name,
		'actual'   => $actual,
		'expected' => $expected,
		'pass'     => $actual === $expected,
	);
}

try {
	$bindings78 = json_decode( '{"base_sha256":"208c4da8b2b4cc974aa4a978daf1c0034bec9b34a8c6a6940c436eacd35034e5","current_sha256":"714b0f7c2d5dd6ffee06b4f02be64185d15a836c6d41605d04ba570bf447a141","sites":[{"name":"legacy_order_meta","line":372,"base_start":13537,"base_bytes":118,"base_sha256":"992a549c16dc9ac03fc9a843daa6c26071731c1ee023d9fb2642207f35922e2e","current_start":13537,"current_bytes":218,"current_sha256":"b17ecbf0926f5fe9776d7d2f2cc58b43ec0c5369179b0a718d30b649b457aa64","provider":["legacy_meta",901,"field-token"],"first_argument_hex":"24746869732d3e736166656c795f6765745f706f73745f6d6574612820246f726465725f69642c20246164646974696f6e616c5f6669656c645f76616c75652029","first_argument_unchanged":true},{"name":"legacy_order_shortcode","line":377,"base_start":13843,"base_bytes":92,"base_sha256":"0b7c4b57bc478fcd2349c50689e3bb9fbbb259fb859c7f22125c0c77de302405","current_start":13943,"current_bytes":192,"current_sha256":"03d7672c9094bacf3481358c30effb8395b127eafbdb1d441972314bd44b0017","provider":["shortcode","field-token"],"first_argument_hex":"646f5f73686f7274636f64652820246164646974696f6e616c5f6669656c645f76616c75652029","first_argument_unchanged":true},{"name":"hpos_order_meta","line":490,"base_start":17926,"base_bytes":96,"base_sha256":"3a780a9f45f5fc9b873c8cdaedec00a4982a1fa787c913b1bbf76f4a812a547c","current_start":18126,"current_bytes":196,"current_sha256":"e106b337a37ed9b312973517a206a55a3b8fb9bd8e211b0908324f21e990a639","provider":["order_meta","field-token"],"first_argument_hex":"246f726465722d3e6765745f6d6574612820246164646974696f6e616c5f6669656c645f76616c75652029","first_argument_unchanged":true},{"name":"hpos_order_shortcode","line":499,"base_start":18406,"base_bytes":86,"base_sha256":"c36d15896e4648db38326ab042444cd875d95472d55c70d7849ed1a6b4c55651","current_start":18699,"current_bytes":186,"current_sha256":"ca823ce8477fead36d0e8afdc1ffbb08cb5ef6b20722fff4b22e76d071eafe32","provider":["shortcode","custom-token"],"first_argument_hex":"646f5f73686f7274636f6465282024637573746f6d5f73686f7274636f64652029","first_argument_unchanged":true},{"name":"legacy_item_shortcode","line":595,"base_start":22032,"base_bytes":92,"base_sha256":"0b7c4b57bc478fcd2349c50689e3bb9fbbb259fb859c7f22125c0c77de302405","current_start":22425,"current_bytes":192,"current_sha256":"03d7672c9094bacf3481358c30effb8395b127eafbdb1d441972314bd44b0017","provider":["shortcode","field-token"],"first_argument_hex":"646f5f73686f7274636f64652820246164646974696f6e616c5f6669656c645f76616c75652029","first_argument_unchanged":true},{"name":"hpos_item_shortcode","line":706,"base_start":26255,"base_bytes":86,"base_sha256":"c36d15896e4648db38326ab042444cd875d95472d55c70d7849ed1a6b4c55651","current_start":26748,"current_bytes":186,"current_sha256":"ca823ce8477fead36d0e8afdc1ffbb08cb5ef6b20722fff4b22e76d071eafe32","provider":["shortcode","custom-token"],"first_argument_hex":"646f5f73686f7274636f6465282024637573746f6d5f73686f7274636f64652029","first_argument_unchanged":true}],"alignment_before":"\t\t\t\t\t\t\t\t\t$order_id_custom         = \u0027 order_id=\"\u0027 . $order_id . \u0027\"]\u0027;\r\n","alignment_after":"\t\t\t\t\t\t\t\t\t$order_id_custom  = \u0027 order_id=\"\u0027 . $order_id . \u0027\"]\u0027;\r\n"}', true );
	$cases78    = json_decode( '[{"name":"named_quotes_amp","charset":"UTF-8","input_hex":"2671756f743b202661706f733b2026616d703b","php72":{"decoded_hex":"22202661706f733b2026","row_hex":"22202661706f733b2026"},"php82":{"decoded_hex":"22202661706f733b2026","row_hex":"22202661706f733b2026"}},{"name":"decimal_quotes","charset":"UTF-8","input_hex":"262333343b7c262333393b7c262333383b","php72":{"decoded_hex":"227c262333393b7c26","row_hex":"227c262333393b7c26"},"php82":{"decoded_hex":"227c277c26","row_hex":"227c277c26"}},{"name":"hexadecimal_quotes","charset":"UTF-8","input_hex":"26237832323b7c26237832373b7c26237832363b","php72":{"decoded_hex":"227c26237832373b7c26","row_hex":"227c26237832373b7c26"},"php82":{"decoded_hex":"227c277c26","row_hex":"227c277c26"}},{"name":"amp_and_encoded_tags","charset":"UTF-8","input_hex":"26616d703b20266c743b622667743b746167266c743b2f622667743b","php72":{"decoded_hex":"26203c623e7461673c2f623e","row_hex":"2620746167"},"php82":{"decoded_hex":"26203c623e7461673c2f623e","row_hex":"2620746167"}},{"name":"known_html4_names","charset":"UTF-8","input_hex":"266575726f3b2026636f70793b20266e6273703b","php72":{"decoded_hex":"e282ac20c2a920c2a0","row_hex":"e282ac20c2a920c2a0"},"php82":{"decoded_hex":"e282ac20c2a920c2a0","row_hex":"e282ac20c2a920c2a0"}},{"name":"unknown_html5_names","charset":"UTF-8","input_hex":"264e6f74457175616c54696c64653b7c2661706f733b7c26436f756e746572436c6f636b77697365436f6e746f7572496e74656772616c3b","php72":{"decoded_hex":"264e6f74457175616c54696c64653b7c2661706f733b7c26436f756e746572436c6f636b77697365436f6e746f7572496e74656772616c3b","row_hex":"264e6f74457175616c54696c64653b7c2661706f733b7c26436f756e746572436c6f636b77697365436f6e746f7572496e74656772616c3b"},"php82":{"decoded_hex":"264e6f74457175616c54696c64653b7c2661706f733b7c26436f756e746572436c6f636b77697365436f6e746f7572496e74656772616c3b","row_hex":"264e6f74457175616c54696c64653b7c2661706f733b7c26436f756e746572436c6f636b77697365436f6e746f7572496e74656772616c3b"}},{"name":"ordinary_unicode","charset":"UTF-8","input_hex":"436166c3a920e4b8ad20f09f9880","php72":{"decoded_hex":"436166c3a920e4b8ad20f09f9880","row_hex":"436166c3a920e4b8ad20f09f9880"},"php82":{"decoded_hex":"436166c3a920e4b8ad20f09f9880","row_hex":"436166c3a920e4b8ad20f09f9880"}},{"name":"invalid_utf8_bytes_no_entity","charset":"UTF-8","input_hex":"41c32842","php72":{"decoded_hex":"41c32842","row_hex":"41c32842"},"php82":{"decoded_hex":"41c32842","row_hex":"41c32842"}},{"name":"empty_string","charset":"UTF-8","input_hex":"","php72":{"decoded_hex":"","row_hex":""},"php82":{"decoded_hex":"","row_hex":""}},{"name":"plain_string","charset":"UTF-8","input_hex":"506c61696e2030","php72":{"decoded_hex":"506c61696e2030","row_hex":"506c61696e2030"},"php82":{"decoded_hex":"506c61696e2030","row_hex":"506c61696e2030"}},{"name":"literal_tags","charset":"UTF-8","input_hex":"3c693e546578743c2f693e","php72":{"decoded_hex":"3c693e546578743c2f693e","row_hex":"54657874"},"php82":{"decoded_hex":"3c693e546578743c2f693e","row_hex":"54657874"}},{"name":"omitted_encoding_latin1","charset":"ISO-8859-1","input_hex":"266561637574653b","php72":{"decoded_hex":"e9","row_hex":"e9"},"php82":{"decoded_hex":"e9","row_hex":"e9"}}]', true );
	if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $bindings78 ) || ! is_array( $cases78 ) ) {
		throw new \RuntimeException( 'fixture_literal_decode78' );
	}
	$current78 = file_get_contents( dirname( __DIR__ ) . '/includes/export/class-wcj-exporter-orders.php' );
	$base78    = file_get_contents( $argv[1] );
	check78( 'base_whole_hash', hash( 'sha256', $base78 ), $bindings78['base_sha256'] );
	check78( 'current_whole_hash', hash( 'sha256', $current78 ), $bindings78['current_sha256'] );
	$insertion78 = ', ( PHP_VERSION_ID >= 80100 ? ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 : ENT_COMPAT | ENT_HTML401 )';
	$inverse78   = str_replace( $insertion78, '', $current78, $flags_count78 );
	$inverse78   = str_replace( '$order_id_custom  = ', '$order_id_custom         = ', $inverse78, $alignment_count78 );
	// The already-correct counterpart also has two spaces. Restore only bound line494.
	$inverse78 = $current78;
	$inverse78 = str_replace( $insertion78, '', $inverse78, $flags_count78 );
	$alignment_line78 = $bindings78['alignment_after'];
	$alignment_old78  = $bindings78['alignment_before'];
	$inverse78 = str_replace( "\n" . $alignment_line78, "\n" . $alignment_old78, $inverse78, $alignment_count78 );
	check78( 'whole_file_inverse', $inverse78, $base78 );
	check78( 'six_explicit_flags', $flags_count78, 6 );
	check78( 'one_alignment_line', $alignment_count78, 1 );
	check78( 'six_source_statements', count( $bindings78['sites'] ), 6 );
	$flags78 = PHP_VERSION_ID >= 80100 ? 11 : 2;
	check78( 'native_version_branch_literal', PHP_VERSION_ID >= 80100 ? ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 : ENT_COMPAT | ENT_HTML401, $flags78 );
	$executors78 = array();
	$provider78  = new Provider78();
	foreach ( $bindings78['sites'] as $site78 ) {
		$base_statement78 = substr( $base78, $site78['base_start'], $site78['base_bytes'] );
		$new_statement78  = substr( $current78, $site78['current_start'], $site78['current_bytes'] );
		check78( $site78['name'] . '/base_statement_hash', hash( 'sha256', $base_statement78 ), $site78['base_sha256'] );
		check78( $site78['name'] . '/current_statement_hash', hash( 'sha256', $new_statement78 ), $site78['current_sha256'] );
		foreach ( array( 'baseline' => $base_statement78, 'candidate' => $new_statement78 ) as $mode78 => $statement78 ) {
			$function78 = eval(
				'namespace WCJ_Decode78; return function () {'
				. '$order_id = 901; $additional_field_value = "field-token"; $custom_shortcode = "custom-token";'
				. '$order = $this; $row = array();'
				. $statement78
				. 'return $row; };'
			);
			$executors78[] = array( $site78['name'], $mode78, $function78->bindTo( $provider78, Provider78::class ), $site78['provider'] );
		}
	}
	foreach ( $checks78 as $binding78 ) {
		if ( ! $binding78['pass'] ) {
			throw new \RuntimeException( 'source_binding78' );
		}
	}
	$original_charset78 = ini_get( 'default_charset' );
	try {
		foreach ( $cases78 as $case78 ) {
			ini_set( 'default_charset', $case78['charset'] );
			if ( ini_get( 'default_charset' ) !== $case78['charset'] ) {
				throw new \RuntimeException( 'charset_control78' );
			}
			$input78 = hex2bin( $case78['input_hex'] );
			$gold78  = $case78[ PHP_VERSION_ID >= 80100 ? 'php82' : 'php72' ];
			foreach ( $executors78 as $executor78 ) {
				$GLOBALS['input78']         = $input78;
				$GLOBALS['provider78']      = array();
				$GLOBALS['decode78_args']   = array();
				$GLOBALS['decode78_values'] = array();
				$GLOBALS['strip78']         = array();
				$row78                     = $executor78[2]();
				$prefix78                  = $executor78[0] . '/' . $executor78[1] . '/' . $case78['name'] . '/';
				$row_hex78                 = array_map( 'bin2hex', $row78 );
				check78( $prefix78 . 'literal_row_hex', $row_hex78, array( $gold78['row_hex'] ) );
				check78( $prefix78 . 'single_input_evaluation', $GLOBALS['provider78'], array( $executor78[3] ) );
				$expected_args78 = array( $case78['input_hex'], 'baseline' === $executor78[1] ? array() : array( $flags78 ) );
				check78( $prefix78 . 'decoder_arguments_encoding_omitted', $GLOBALS['decode78_args'], array( $expected_args78 ) );
				check78( $prefix78 . 'wrapper_input', $GLOBALS['strip78'], array( $gold78['decoded_hex'] ) );
				check78( $prefix78 . 'literal_native_decode_hex', $GLOBALS['decode78_values'], array( $gold78['decoded_hex'] ) );
				check78( $prefix78 . 'secondary_native_default_parity', $row_hex78, array( bin2hex( \strip_tags( \html_entity_decode( $input78 ) ) ) ) );
			}
		}
	} finally {
		ini_set( 'default_charset', $original_charset78 );
	}
	check78( 'process_charset_restored', ini_get( 'default_charset' ), $original_charset78 );
	$failed78 = array_filter(
		$checks78,
		static function ( $check78 ) {
			return ! $check78['pass'];
		}
	);
	$json78 = json_encode(
		array(
			'status'      => empty( $failed78 ) ? 'PASS' : 'FAIL',
			'assertions'  => count( $checks78 ),
			'failed'      => count( $failed78 ),
			'php_version' => PHP_VERSION,
			'results'     => $checks78,
			'scope'       => 'Actual six bound statements; native decoding with inert metadata/shortcode and bounded strip_tags stand-ins. No full WordPress/WooCommerce export, invalid nonstrings, PHP8.4 or cross-version byte-equality qualification.',
		),
		JSON_UNESCAPED_SLASHES
	);
	if ( false === $json78 || JSON_ERROR_NONE !== json_last_error() ) {
		throw new \RuntimeException( 'fixture_output78' );
	}
	echo $json78 . "\n";
	exit( empty( $failed78 ) ? 0 : 1 );
} catch ( \Throwable $error78 ) {
	fwrite( STDERR, 'FIXTURE_ERROR78: ' . $error78->getMessage() . "\n" );
	exit( 2 );
}
