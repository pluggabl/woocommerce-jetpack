<?php
/**
 * Fixed source-bound Additional Fields loop regression.
 *
 * Development-only CLI fixture. It does not load WordPress or export orders.
 * Shortcode rendering and tag stripping below are inert stand-ins. Native
 * html_entity_decode, strpos and str_replace run unchanged from the loop.
 *
 * @package Booster_For_WooCommerce
 */

namespace WCJ_Order_Id69;

if ( 'cli' !== PHP_SAPI ) {
	exit;
}

set_error_handler(
	static function ( $severity, $message, $file, $line ) {
		throw new \ErrorException( $message, 0, $severity, $file, $line );
	}
);

/**
 * Record the exact shortcode argument without executing a real shortcode.
 *
 * @param string $value Shortcode argument.
 * @return string
 */
function do_shortcode( $value ) {
	$GLOBALS['wcj69_calls'][] = $value;
	return '<b>' . $value . '</b>';
}

/**
 * Bounded stand-in, sufficient only for this fixture's literal HTML inputs.
 *
 * @param string $value Rendered text.
 * @return string
 */
function wp_strip_all_tags( $value ) {
	return strip_tags( $value );
}

/**
 * Synthetic order metadata object, not a WooCommerce object.
 */
class Order69 {
	private $id;

	/**
	 * Store a literal synthetic identity.
	 *
	 * @param int $id Order ID.
	 */
	public function __construct( $id ) {
		$this->id = $id;
	}

	/**
	 * Return only the two declared metadata values.
	 *
	 * @param string $key Metadata key.
	 * @return string
	 */
	public function get_meta( $key ) {
		$GLOBALS['wcj69_meta'][] = array( $key, $this->id );
		return 'note' === $key ? '<i>Meta &amp; value</i>' : '';
	}
}

$results69 = array();

/**
 * Preserve the exact typed actual and expected value.
 *
 * @param string $name Assertion name.
 * @param mixed  $actual Actual value.
 * @param mixed  $expected Literal expected value.
 */
function check69( $name, $actual, $expected ) {
	$GLOBALS['results69'][] = array(
		'name'     => $name,
		'actual'   => $actual,
		'expected' => $expected,
		'pass'     => $actual === $expected,
	);
}

try {
	$path69     = dirname( __DIR__ ) . '/includes/export/class-wcj-exporter-orders.php';
	$current69  = file_get_contents( $path69 );
	$baseline69 = file_get_contents( $argv[1] );
	$bindings69 = json_decode( '{"base_sha256":"87e21562dfd1bffd24f7ef20998166d0d1f50ae17fb4b200b9cb9a61518bb9e2","current_sha256":"208c4da8b2b4cc974aa4a978daf1c0034bec9b34a8c6a6940c436eacd35034e5","loop_start":17664,"base_loop_bytes":916,"current_loop_bytes":930,"base_loop_sha256":"b31310df65f2f9297117303ae6269d75c578b9203b17a67b8743ade37c2ef10d","current_loop_sha256":"4f8ffd601ab7c8f5d4b665c96be418a7ff2f521152913f4bb655659b9d86a57d","counterpart_base_start":25039,"counterpart_current_start":25053,"counterpart_bytes":1805,"counterpart_sha256":"afa54b9c4322e3eb898527a03b3d622f79b1bd1b2d3e29cca53e9c0e17c3420e"}', true );
	$cases69    = json_decode( '[{"name":"two_bracketed_fields","id":101,"row":["seed:101"],"fields":[{"type":"shortcode","value":"[alpha]"},{"type":"shortcode","value":"[beta]"}],"meta_calls":[],"baseline":{"order_id":" order_id=\" order_id=\"101\"]\"]","data":[["seed:101","[alpha order_id=\"101\"]","[beta order_id=\" order_id=\"101\"]\"]"]],"calls":["[alpha order_id=\"101\"]","[beta order_id=\" order_id=\"101\"]\"]"]},"candidate":{"order_id":101,"data":[["seed:101","[alpha order_id=\"101\"]","[beta order_id=\"101\"]"]],"calls":["[alpha order_id=\"101\"]","[beta order_id=\"101\"]"]}},{"name":"mixed_meta_plain_empty_multiple_closes","id":202,"row":["seed:202"],"fields":[{"type":"meta","value":"note"},{"type":"shortcode","value":"plain text"},{"type":"shortcode","value":""},{"type":"shortcode","value":"[one][two]"},{"type":"meta","value":"missing"}],"meta_calls":[["note",202],["missing",202]],"baseline":{"order_id":" order_id=\"202\"]","data":[["seed:202","Meta & value","plain text","","[one order_id=\"202\"][two order_id=\"202\"]",""]],"calls":["plain text","[one order_id=\"202\"][two order_id=\"202\"]"]},"candidate":{"order_id":202,"data":[["seed:202","Meta & value","plain text","","[one order_id=\"202\"][two order_id=\"202\"]",""]],"calls":["plain text","[one order_id=\"202\"][two order_id=\"202\"]"]}},{"name":"second_order_new_identity","id":303,"row":["seed:303"],"fields":[{"type":"shortcode","value":"[later]"},{"type":"shortcode","value":"[last]"}],"meta_calls":[],"baseline":{"order_id":" order_id=\" order_id=\"303\"]\"]","data":[["seed:303","[later order_id=\"303\"]","[last order_id=\" order_id=\"303\"]\"]"]],"calls":["[later order_id=\"303\"]","[last order_id=\" order_id=\"303\"]\"]"]},"candidate":{"order_id":303,"data":[["seed:303","[later order_id=\"303\"]","[last order_id=\"303\"]"]],"calls":["[later order_id=\"303\"]","[last order_id=\"303\"]"]}},{"name":"closing_bracket_at_zero","id":404,"row":["seed:404"],"fields":[{"type":"shortcode","value":"]tail"},{"type":"shortcode","value":"again]"}],"meta_calls":[],"baseline":{"order_id":" order_id=\" order_id=\"404\"]\"]","data":[["seed:404"," order_id=\"404\"]tail","again order_id=\" order_id=\"404\"]\"]"]],"calls":[" order_id=\"404\"]tail","again order_id=\" order_id=\"404\"]\"]"]},"candidate":{"order_id":404,"data":[["seed:404"," order_id=\"404\"]tail","again order_id=\"404\"]"]],"calls":[" order_id=\"404\"]tail","again order_id=\"404\"]"]}},{"name":"no_bracket_preserves_id","id":505,"row":["seed:505"],"fields":[{"type":"shortcode","value":"literal"},{"type":"shortcode","value":""},{"type":"meta","value":"note"}],"meta_calls":[["note",505]],"baseline":{"order_id":505,"data":[["seed:505","literal","","Meta & value"]],"calls":["literal"]},"candidate":{"order_id":505,"data":[["seed:505","literal","","Meta & value"]],"calls":["literal"]}},{"name":"no_additional_fields","id":606,"row":["seed:606"],"fields":[],"meta_calls":[],"baseline":{"order_id":606,"data":[["seed:606"]],"calls":[]},"candidate":{"order_id":606,"data":[["seed:606"]],"calls":[]}}]', true );
	if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $bindings69 ) || ! is_array( $cases69 ) ) {
		throw new \RuntimeException( 'fixture_literal_decode' );
	}
	check69( 'baseline_whole_hash', hash( 'sha256', $baseline69 ), $bindings69['base_sha256'] );
	check69( 'candidate_whole_hash', hash( 'sha256', $current69 ), $bindings69['current_sha256'] );
	$old69 = array(
		'$order_id         = ',
		"str_replace( ']', \$order_id, \$get_value )",
	);
	$new69 = array(
		'$order_id_custom         = ',
		"str_replace( ']', \$order_id_custom, \$get_value )",
	);
	$inverse69 = str_replace( $new69, $old69, $current69, $count69 );
	check69( 'whole_file_inverse', $inverse69, $baseline69 );
	check69( 'exact_two_identifier_changes', $count69, 2 );
	$base_loop69 = substr( $baseline69, $bindings69['loop_start'], $bindings69['base_loop_bytes'] );
	$new_loop69  = substr( $current69, $bindings69['loop_start'], $bindings69['current_loop_bytes'] );
	check69( 'baseline_loop_hash', hash( 'sha256', $base_loop69 ), $bindings69['base_loop_sha256'] );
	check69( 'candidate_loop_hash', hash( 'sha256', $new_loop69 ), $bindings69['current_loop_sha256'] );
	$base_other69 = substr( $baseline69, $bindings69['counterpart_base_start'], $bindings69['counterpart_bytes'] );
	$new_other69  = substr( $current69, $bindings69['counterpart_current_start'], $bindings69['counterpart_bytes'] );
	check69( 'correct_counterpart_unchanged', $new_other69, $base_other69 );
	check69( 'correct_counterpart_hash', hash( 'sha256', $new_other69 ), $bindings69['counterpart_sha256'] );
	foreach ( $results69 as $binding69 ) {
		if ( ! $binding69['pass'] ) {
			throw new \RuntimeException( 'source_binding_failed' );
		}
	}
	$loops69 = array();
	foreach ( array( 'baseline' => $base_loop69, 'candidate' => $new_loop69 ) as $mode69 => $loop69 ) {
		$loops69[ $mode69 ] = eval(
			'namespace WCJ_Order_Id69; return function ( $order_id, $additional_fields, $order, $row ) {'
			. '$data = array(); $i = 0;'
			. $loop69
			. 'return array( "order_id" => $order_id, "data" => $data, "i" => $i ); };'
		);
	}
	foreach ( $cases69 as $case69 ) {
		foreach ( $loops69 as $mode69 => $run69 ) {
			$GLOBALS['wcj69_calls'] = array();
			$GLOBALS['wcj69_meta']  = array();
			$order69               = new Order69( $case69['id'] );
			$out69                 = $run69( $case69['id'], $case69['fields'], $order69, $case69['row'] );
			$expected69            = $case69[ $mode69 ];
			$label69               = $mode69 . '/' . $case69['name'] . '/';
			check69( $label69 . 'order_id', $out69['order_id'], $expected69['order_id'] );
			check69( $label69 . 'rows', $out69['data'], $expected69['data'] );
			check69( $label69 . 'increment', $out69['i'], 1 );
			check69( $label69 . 'shortcode_args', $GLOBALS['wcj69_calls'], $expected69['calls'] );
			check69( $label69 . 'meta_calls', $GLOBALS['wcj69_meta'], $case69['meta_calls'] );
		}
	}
	$failed69 = array_filter(
		$results69,
		static function ( $row69 ) {
			return ! $row69['pass'];
		}
	);
	$encoded69 = json_encode(
		array(
			'status'           => empty( $failed69 ) ? 'PASS' : 'FAIL',
			'assertions'       => count( $results69 ),
			'failed'           => count( $failed69 ),
			'php_version'      => PHP_VERSION,
			'bindings'         => $bindings69,
			'results'          => $results69,
			'scope'            => 'Actual bound Additional Fields loop slice only; synthetic order, shortcode and strip-tags stand-ins; no WordPress/WooCommerce/full-export/native qualification.',
			'baseline_defect'  => 'Baseline expectations intentionally record corrupted repeated bracketed-field IDs; candidate expectations require original typed ID and clean arguments.',
		),
		JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
	);
	if ( false === $encoded69 || JSON_ERROR_NONE !== json_last_error() ) {
		throw new \RuntimeException( 'fixture_output_encode' );
	}
	echo $encoded69 . "\n";
	exit( empty( $failed69 ) ? 0 : 1 );
} catch ( \Throwable $error69 ) {
	fwrite( STDERR, 'FIXTURE_ERROR: ' . $error69->getMessage() . "\n" );
	exit( 2 );
}
