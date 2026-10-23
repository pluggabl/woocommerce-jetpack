<?php
/**
 * Source-only HPOS renumeration query contract regression.
 *
 * This fixture executes the actual extracted production method under explicit
 * offline option/query/downstream recorder doubles. It does not boot WordPress,
 * use a database, execute the historical baseline or prove native persistence.
 *
 * @package Booster_For_WooCommerce/tests
 */

if ( 'cli' !== PHP_SAPI || 3 !== $argc ) {
	exit( 2 );
}

error_reporting( E_ALL );
set_error_handler(
	static function ( $severity, $message, $file, $line ) {
		throw new ErrorException( $message, 0, $severity, $file, $line );
	}
);

/** Extract one actual production method without plugin bootstrap. */
function wcj_probe_method( $source ) {
	$offset = strpos( $source, 'public function renumerate_orders()' );
	if ( false === $offset ) {
		throw new RuntimeException( 'Production method not found.' );
	}
	$tokens = token_get_all( '<?php ' . substr( $source, $offset ) );
	$method = '';
	$depth  = 0;
	$opened = false;
	foreach ( array_slice( $tokens, 1 ) as $token ) {
		$method .= is_array( $token ) ? $token[1] : $token;
		if ( ! is_array( $token ) && '{' === $token ) {
			++$depth;
			$opened = true;
		} elseif ( ! is_array( $token ) && '}' === $token ) {
			--$depth;
			if ( $opened && 0 === $depth ) {
				return $method;
			}
		}
	}
	throw new RuntimeException( 'Production method is incomplete.' );
}

/** Exact assertion stream, including literal expected and observed values. */
function wcj_probe_check( $name, $expected, $actual ) {
	$GLOBALS['wcj_probe_assertions'][] = array(
		'name'     => $name,
		'expected' => $expected,
		'actual'   => $actual,
		'pass'     => $expected === $actual,
	);
	if ( $expected !== $actual ) {
		throw new RuntimeException( 'Assertion failed: ' . $name );
	}
}

/** Offline option boundary, no WordPress options or filters. */
function wcj_get_option( $name, $default ) {
	$GLOBALS['wcj_probe_reads'][] = array( $name, $default );
	return array_key_exists( $name, $GLOBALS['wcj_probe_options'] ) ? $GLOBALS['wcj_probe_options'][ $name ] : $default;
}

/** Offline write recorder, never a database write. */
function update_option( $name, $value ) {
	$GLOBALS['wcj_probe_writes'][] = array( $name, $value );
	return true;
}

/** Offline query boundary explicitly models the public return selector. */
function wc_get_orders( $args ) {
	$GLOBALS['wcj_probe_queries'][] = $args;
	$index = count( $GLOBALS['wcj_probe_queries'] ) - 1;
	if ( $index >= count( $GLOBALS['wcj_probe_pages'] ) ) {
		throw new RuntimeException( 'Unexpected extra query.' );
	}
	$page = $GLOBALS['wcj_probe_pages'][ $index ];
	if ( isset( $args['return'] ) && 'ids' === $args['return'] ) {
		return $page;
	}
	return array_map(
		static function ( $id ) {
			return (object) array( 'synthetic_order_id' => $id );
		},
		$page
	);
}

try {
	$relative = '/includes/class-wcj-order-numbers.php';
	$current  = file_get_contents( rtrim( $argv[1], '/\\' ) . $relative );
	$baseline = file_get_contents( rtrim( $argv[2], '/\\' ) . $relative );
	$method   = wcj_probe_method( $current );
	$old      = wcj_probe_method( $baseline );
	$GLOBALS['wcj_probe_assertions'] = array();
	$position = strpos( $baseline, "'fields'", strpos( $baseline, 'public function renumerate_orders()' ) );
	wcj_probe_check( 'baseline target exists', true, false !== $position );
	$expected_source = substr_replace( $baseline, "'return'", $position, strlen( "'fields'" ) );
	wcj_probe_check( 'exact one-key whole-file inverse', $expected_source, $current );
	$legacy_start = strpos( $old, '} else {' );
	wcj_probe_check( 'legacy branch exists', true, false !== $legacy_start );
	wcj_probe_check( 'legacy WP_Query tail byte equality', substr( $old, $legacy_start ), substr( $method, strpos( $method, '} else {' ) ) );
	wcj_probe_check( 'legacy fields selector retained', true, false !== strpos( substr( $method, strpos( $method, '} else {' ) ), "'fields'" ) );
	// Only this actual source method is evaluated; downstream is a recorder.
	eval( 'class WCJ_Renumeration_Source_Probe { public $seen = array(); ' . $method . ' public function add_order_number_meta_hpos( $id, $force ) { $this->seen[] = array( $id, $force ); } }' );
	$cases = array(
		'empty defaults' => array( array(), array( array() ) ),
		'one page'       => array( array(), array( array( 7, 513, 9001 ), array() ) ),
		'two full pages' => array( array(), array( range( 1, 512 ), range( 513, 1024 ), array() ) ),
		'reset custom'   => array(
			array( 'wcj_order_number_counter_reset_enabled' => 'monthly', 'wcj_order_numbers_renumerate_tool_orderby' => 'ID', 'wcj_order_numbers_renumerate_tool_order' => 'DESC' ),
			array( array( 71, 3 ), array() ),
		),
		'hash no reset'  => array(
			array( 'wcj_order_number_sequential_enabled' => 'hash_crc32', 'wcj_order_number_counter_reset_enabled' => 'monthly' ),
			array( array( 42 ), array() ),
		),
	);
	foreach ( $cases as $name => $case ) {
		$GLOBALS['wcj_probe_options'] = $case[0];
		$GLOBALS['wcj_probe_pages']   = $case[1];
		$GLOBALS['wcj_probe_queries'] = array();
		$GLOBALS['wcj_probe_reads']   = array();
		$GLOBALS['wcj_probe_writes']  = array();
		$probe = new WCJ_Renumeration_Source_Probe();
		$returned = $probe->renumerate_orders();
		$expected_queries = array();
		$expected_seen    = array();
		$expected_reads   = array( array( 'wcj_order_number_sequential_enabled', 'yes' ) );
		$sequential = $case[0]['wcj_order_number_sequential_enabled'] ?? 'yes';
		if ( 'yes' === $sequential ) {
			$expected_reads[] = array( 'wcj_order_number_counter_reset_enabled', 'no' );
		}
		foreach ( $case[1] as $index => $page ) {
			$expected_queries[] = array(
				'type' => array( 'shop_order' ), 'status' => 'any', 'limit' => 512,
				'orderby' => $case[0]['wcj_order_numbers_renumerate_tool_orderby'] ?? 'date',
				'order' => $case[0]['wcj_order_numbers_renumerate_tool_order'] ?? 'ASC',
				'offset' => $index * 512, 'return' => 'ids',
			);
			$expected_reads[] = array( 'wcj_order_numbers_renumerate_tool_orderby', 'date' );
			$expected_reads[] = array( 'wcj_order_numbers_renumerate_tool_order', 'ASC' );
			foreach ( $page as $id ) {
				$expected_seen[] = array( $id, true );
			}
		}
		$expected_writes = 'reset custom' === $name ? array( array( 'wcj_order_number_counter_previous_order_date', 0 ) ) : array();
		wcj_probe_check( $name . ': exact query tuples', $expected_queries, $GLOBALS['wcj_probe_queries'] );
		wcj_probe_check( $name . ': exact scalar downstream IDs and overwrite', $expected_seen, $probe->seen );
		wcj_probe_check( $name . ': exact option reads', $expected_reads, $GLOBALS['wcj_probe_reads'] );
		wcj_probe_check( $name . ': exact reset writes', $expected_writes, $GLOBALS['wcj_probe_writes'] );
		wcj_probe_check( $name . ': empty page terminates', count( $case[1] ), count( $GLOBALS['wcj_probe_queries'] ) );
		wcj_probe_check( $name . ': implicit return unchanged', null, $returned );
	}
	$result = array( 'status' => 'PASS', 'assertions' => count( $GLOBALS['wcj_probe_assertions'] ), 'cases' => count( $cases ), 'runtime_sha256' => hash( 'sha256', $current ), 'fixture_sha256' => hash_file( 'sha256', __FILE__ ), 'checks' => $GLOBALS['wcj_probe_assertions'], 'limitations' => 'Extracted actual method with explicit offline doubles, no WordPress/bootstrap/datastore/DB/native/actual numbering mutation proof. Baseline read only, not executed.' );
	echo json_encode( $result, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR ) . PHP_EOL;
} catch ( Throwable $error ) {
	fwrite( STDERR, get_class( $error ) . ': ' . $error->getMessage() . PHP_EOL );
	echo json_encode( array( 'status' => 'FAIL', 'checks' => $GLOBALS['wcj_probe_assertions'] ?? array() ), JSON_UNESCAPED_SLASHES ) . PHP_EOL;
	exit( 1 );
}
