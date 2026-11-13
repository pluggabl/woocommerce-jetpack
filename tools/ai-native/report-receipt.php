<?php
/**
 * Pure synthetic receipt contracts, not consent, native execution or client proof.
 *
 * @package Booster_For_WooCommerce/tools
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}
define( 'ABSPATH', __DIR__ ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Standalone pure test only.
require_once dirname( __DIR__, 2 ) . '/includes/class-wcj-ai-report-receipt.php';
require_once __DIR__ . '/class-wcj-ai-revenue-fixture.php';
require_once __DIR__ . '/class-wcj-ai-projection-poison.php';
// phpcs:disable WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Standalone pure test has no WordPress bootstrap; exercise canonical native JSON bytes.
$wcj_ai_results = array();

/**
 * Record a strict assertion.
 *
 * @param string $name Case.
 * @param bool   $ok Outcome.
 */
function wcj_ai_receipt_check( $name, $ok ) {
	global $wcj_ai_results;
	$wcj_ai_results[ $name ] = true === $ok;
}

/**
 * Require one finite refusal without exposing input or producer exceptions.
 *
 * @param string   $name Case.
 * @param callable $call Test operation.
 */
function wcj_ai_receipt_bad( $name, $call ) {
	try {
		$call();
		wcj_ai_receipt_check( $name, false );
	} catch ( InvalidArgumentException $exception ) {
		wcj_ai_receipt_check( $name, 'Unsupported report receipt.' === $exception->getMessage() );
	}
}

$wcj_ai_source   = WCJ_AI_Revenue_Fixture::source();
$wcj_ai_original = $wcj_ai_source;
$wcj_ai_start    = 1794564000;
$wcj_ai_expiry   = $wcj_ai_start + 3600;
$wcj_ai_created  = $wcj_ai_start + 7;
$wcj_ai_bytes    = WCJ_AI_Report_Receipt::build( $wcj_ai_source, $wcj_ai_start, $wcj_ai_expiry, $wcj_ai_created );
$wcj_ai_decoded  = WCJ_AI_Report_Receipt::decode( $wcj_ai_bytes, $wcj_ai_start, $wcj_ai_expiry );
wcj_ai_receipt_check( 'source_unchanged', $wcj_ai_source === $wcj_ai_original );
wcj_ai_receipt_check( 'exact_canonical_roundtrip', json_encode( $wcj_ai_decoded ) === $wcj_ai_bytes );
wcj_ai_receipt_check( 'new_codec_version', 2 === $wcj_ai_decoded['schema_version'] );
wcj_ai_receipt_check( 'default_codec_escapes_timezone_slash', str_contains( $wcj_ai_bytes, 'America\/New_York' ) && json_encode( $wcj_ai_decoded, JSON_UNESCAPED_SLASHES ) !== $wcj_ai_bytes );
$wcj_ai_old                   = $wcj_ai_decoded;
$wcj_ai_old['schema_version'] = 1;
$wcj_ai_old_bytes             = json_encode( $wcj_ai_old, JSON_UNESCAPED_SLASHES );
wcj_ai_receipt_check( 'old_codec_retained_exactly', WCJ_AI_Report_Receipt::decode( $wcj_ai_old_bytes, $wcj_ai_start, $wcj_ai_expiry ) === $wcj_ai_old && str_contains( $wcj_ai_old_bytes, 'America/New_York' ) );
wcj_ai_receipt_bad( 'codec1_wrong_flags_refused', static fn() => WCJ_AI_Report_Receipt::decode( json_encode( $wcj_ai_old ), $wcj_ai_start, $wcj_ai_expiry ) );
wcj_ai_receipt_bad( 'codec2_wrong_flags_refused', static fn() => WCJ_AI_Report_Receipt::decode( json_encode( $wcj_ai_decoded, JSON_UNESCAPED_SLASHES ), $wcj_ai_start, $wcj_ai_expiry ) );
wcj_ai_receipt_check( 'closed_envelope', array_keys( $wcj_ai_decoded ) === array( 'schema_version', 'kind', 'captured_closed_at_utc', 'review_created_at_utc', 'expires_at_utc', 'source_schema', 'snapshot' ) );
wcj_ai_receipt_check( 'frozen_times', $wcj_ai_decoded['captured_closed_at_utc'] === $wcj_ai_source['observed_until_utc'] && gmdate( 'Y-m-d\TH:i:s\Z', $wcj_ai_created ) === $wcj_ai_decoded['review_created_at_utc'] && gmdate( 'Y-m-d\TH:i:s\Z', $wcj_ai_start + 600 ) === $wcj_ai_decoded['expires_at_utc'] );
wcj_ai_receipt_check( 'groups_and_decimal_strings_preserved', $wcj_ai_decoded['snapshot']['streams']['paid_cohort_order_totals']['groups'] === $wcj_ai_source['streams']['paid_cohort_order_totals']['groups'] );
wcj_ai_receipt_check( 'limitations_preserved', false === $wcj_ai_decoded['snapshot']['overall_complete'] && false === $wcj_ai_decoded['snapshot']['membership_complete'] && false === $wcj_ai_decoded['snapshot']['resumable'] && true === $wcj_ai_decoded['snapshot']['non_atomic'] && null === $wcj_ai_decoded['snapshot']['net_revenue'] );
wcj_ai_receipt_check( 'projection_revalidation', WCJ_AI_Observation_Projection::revenue_payload( $wcj_ai_decoded['snapshot'] ) === $wcj_ai_decoded['snapshot'] );
$wcj_ai_source['streams']['paid_cohort_order_totals']['groups'][0]['amount'] = '999';
wcj_ai_receipt_check( 'later_source_change_does_not_refresh_receipt', WCJ_AI_Report_Receipt::decode( $wcj_ai_bytes, $wcj_ai_start, $wcj_ai_expiry ) === $wcj_ai_decoded );
$wcj_ai_source = $wcj_ai_original;

foreach ( array( '0', '0.000001', '1800000000000', '9223372036854.775807' ) as $wcj_ai_amount ) {
	$wcj_ai_copy = $wcj_ai_source;
	$wcj_ai_copy['streams']['paid_cohort_order_totals']['groups'][0]['amount'] = $wcj_ai_amount;
	$wcj_ai_value = WCJ_AI_Report_Receipt::decode( WCJ_AI_Report_Receipt::build( $wcj_ai_copy, $wcj_ai_start, $wcj_ai_expiry, $wcj_ai_created ), $wcj_ai_start, $wcj_ai_expiry );
	wcj_ai_receipt_check( 'exact_amount_' . $wcj_ai_amount, $wcj_ai_value['snapshot']['streams']['paid_cohort_order_totals']['groups'][0]['amount'] === $wcj_ai_amount );
}
foreach ( array( null, 'legacy_native_uncached' ) as $wcj_ai_storage ) {
	$wcj_ai_copy            = $wcj_ai_source;
	$wcj_ai_copy['storage'] = $wcj_ai_storage;
	$wcj_ai_copy['periods'] = null;
	$wcj_ai_copy['streams'] = array();
	$wcj_ai_copy['reason']  = 'observation_unavailable';
	$wcj_ai_value           = WCJ_AI_Report_Receipt::decode( WCJ_AI_Report_Receipt::build( $wcj_ai_copy, $wcj_ai_start, $wcj_ai_expiry, $wcj_ai_created ), $wcj_ai_start, $wcj_ai_expiry );
	wcj_ai_receipt_check( 'root_unavailable_' . ( $wcj_ai_storage ?? 'null' ), null === $wcj_ai_value['snapshot']['streams']['paid_cohort_order_totals']['groups'] && null === $wcj_ai_value['snapshot']['streams']['recorded_refund_events']['cap_reached'] );
}
$wcj_ai_copy                                      = $wcj_ai_source;
$wcj_ai_copy['streams']['recorded_refund_events'] = array(
	'status'       => 'unavailable',
	'amount_basis' => 'recorded_refund_amount_not_return_requests',
	'groups'       => null,
	'reason'       => 'query_unavailable',
);
$wcj_ai_value                                     = WCJ_AI_Report_Receipt::decode( WCJ_AI_Report_Receipt::build( $wcj_ai_copy, $wcj_ai_start, $wcj_ai_expiry, $wcj_ai_created ), $wcj_ai_start, $wcj_ai_expiry );
wcj_ai_receipt_check( 'one_stream_unavailable', 'query_unavailable' === $wcj_ai_value['snapshot']['streams']['recorded_refund_events']['reason'] );
$wcj_ai_copy = $wcj_ai_source;
$wcj_ai_copy['streams']['recorded_refund_events']['groups'] = array();
$wcj_ai_value = WCJ_AI_Report_Receipt::decode( WCJ_AI_Report_Receipt::build( $wcj_ai_copy, $wcj_ai_start, $wcj_ai_expiry, $wcj_ai_created ), $wcj_ai_start, $wcj_ai_expiry );
wcj_ai_receipt_check( 'observed_empty_not_zero_or_unavailable', array() === $wcj_ai_value['snapshot']['streams']['recorded_refund_events']['groups'] && null === $wcj_ai_value['snapshot']['streams']['recorded_refund_events']['reason'] );

$wcj_ai_mutations = array(
	'unknown_top'             => static function ( &$x ) {
		$x['customer'] = 'PRIVATE_CUSTOMER'; },
	'wrong_schema'            => static function ( &$x ) {
		$x['schema_version'] = 3; },
	'wrong_kind'              => static function ( &$x ) {
		$x['kind'] = 'role_price_observation'; },
	'wrong_source_schema'     => static function ( &$x ) {
		$x['source_schema'] = 'other'; },
	'changed_closed_time'     => static function ( &$x ) {
		$x['captured_closed_at_utc'] = '2026-11-13T00:00:00Z'; },
	'changed_expiry'          => static function ( &$x ) {
		$x['expires_at_utc'] = '2026-11-13T00:00:00Z'; },
	'shared_time_added'       => static function ( &$x ) {
		$x['shared_at_utc'] = '2026-11-13T00:00:00Z'; },
	'nested_id'               => static function ( &$x ) {
		$x['snapshot']['order_id'] = 1; },
	'nested_raw_text'         => static function ( &$x ) {
		$x['snapshot']['streams']['recorded_refund_events']['reason'] = 'SELECT PRIVATE_CUSTOMER'; },
	'numeric_amount'          => static function ( &$x ) {
		$x['snapshot']['streams']['paid_cohort_order_totals']['groups'][0]['amount'] = 100.35; },
	'oversize_aggregate'      => static function ( &$x ) {
		$x['snapshot']['streams']['paid_cohort_order_totals']['groups'][0]['amount'] = '9223372036854.775808'; },
	'false_complete'          => static function ( &$x ) {
		$x['snapshot']['overall_complete'] = true; },
	'wrong_limit'             => static function ( &$x ) {
		$x['snapshot']['limits']['wc_get_orders_calls_max'] = 5; },
	'observed_reason'         => static function ( &$x ) {
		$x['snapshot']['streams']['paid_cohort_order_totals']['reason'] = 'query_unavailable'; },
	'root_reason_with_groups' => static function ( &$x ) {
		$x['snapshot']['reason'] = 'query_unavailable'; },
	'invalid_timestamp'       => static function ( &$x ) {
		$x['review_created_at_utc'] = '2026-02-30T00:00:00Z'; },
);
foreach ( $wcj_ai_mutations as $wcj_ai_name => $wcj_ai_mutate ) {
	$wcj_ai_copy = $wcj_ai_decoded;
	$wcj_ai_mutate( $wcj_ai_copy );
	wcj_ai_receipt_bad( $wcj_ai_name, static fn() => WCJ_AI_Report_Receipt::decode( json_encode( $wcj_ai_copy ), $wcj_ai_start, $wcj_ai_expiry ) );
}
foreach ( array(
	'pretty'       => json_encode( $wcj_ai_decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ),
	'duplicate'    => str_replace( '{"schema_version":2,', '{"schema_version":2,"schema_version":2,', $wcj_ai_bytes ),
	'reordered'    => json_encode( array_reverse( $wcj_ai_decoded, true ), JSON_UNESCAPED_SLASHES ),
	'trailing'     => $wcj_ai_bytes . ' ',
	'invalid_utf8' => $wcj_ai_bytes . "\xc0",
	'depth'        => str_repeat( '[', 17 ) . '0' . str_repeat( ']', 17 ),
	'oversize'     => str_repeat( ' ', 32769 ),
	'not_json'     => 'not_json',
) as $wcj_ai_name => $wcj_ai_bad ) {
	wcj_ai_receipt_bad( 'encoding_' . $wcj_ai_name, static fn() => WCJ_AI_Report_Receipt::decode( $wcj_ai_bad, $wcj_ai_start, $wcj_ai_expiry ) );
}
foreach ( array(
	'reserved_string' => array( (string) $wcj_ai_start, $wcj_ai_expiry, $wcj_ai_created ),
	'expiry_string'   => array( $wcj_ai_start, (string) $wcj_ai_expiry, $wcj_ai_created ),
	'created_string'  => array( $wcj_ai_start, $wcj_ai_expiry, (string) $wcj_ai_created ),
	'created_before'  => array( $wcj_ai_start, $wcj_ai_expiry, $wcj_ai_start + 4 ),
	'expired'         => array( $wcj_ai_start, $wcj_ai_start, $wcj_ai_created ),
	'excess_grant'    => array( $wcj_ai_start, $wcj_ai_start + 3601, $wcj_ai_created ),
	'review_deadline' => array( $wcj_ai_start, $wcj_ai_expiry, $wcj_ai_start + 300 ),
	'reserved_after'  => array( $wcj_ai_start + 6, $wcj_ai_expiry, $wcj_ai_created ),
	'grant_deadline'  => array( $wcj_ai_start, $wcj_ai_start + 7, $wcj_ai_created ),
) as $wcj_ai_name => $wcj_ai_times ) {
	wcj_ai_receipt_bad( 'time_' . $wcj_ai_name, static fn() => WCJ_AI_Report_Receipt::build( $wcj_ai_source, ...$wcj_ai_times ) );
}
$wcj_ai_short = WCJ_AI_Report_Receipt::decode( WCJ_AI_Report_Receipt::build( $wcj_ai_source, $wcj_ai_start, $wcj_ai_start + 8, $wcj_ai_created ), $wcj_ai_start, $wcj_ai_start + 8 );
wcj_ai_receipt_check( 'short_grant_clips_frozen_expiry', gmdate( 'Y-m-d\TH:i:s\Z', $wcj_ai_start + 8 ) === $wcj_ai_short['expires_at_utc'] );
wcj_ai_receipt_check( 'last_review_second_accepted', is_string( WCJ_AI_Report_Receipt::build( $wcj_ai_source, $wcj_ai_start, $wcj_ai_expiry, $wcj_ai_start + 299 ) ) );
$wcj_ai_copy = $wcj_ai_source;
$wcj_ai_copy['streams']['paid_cohort_order_totals']['groups'][0]['amount'] = new WCJ_AI_Projection_Poison();
wcj_ai_receipt_bad( 'source_object_rejected', static fn() => WCJ_AI_Report_Receipt::build( $wcj_ai_copy, $wcj_ai_start, $wcj_ai_expiry, $wcj_ai_created ) );
wcj_ai_receipt_check( 'object_methods_not_called', 0 === WCJ_AI_Projection_Poison::$calls );

// Test the encoder's size layer only; this synthetic padded array is NOT a valid receipt.
$wcj_ai_encoder = new ReflectionMethod( WCJ_AI_Report_Receipt::class, 'encode' );
wcj_ai_receipt_check(
	'encoder_32768_byte_boundary_only',
	32768 === strlen(
		$wcj_ai_encoder->invoke(
			null,
			array(
				'schema_version' => 2,
				'x'              => str_repeat( 'a', 32741 ),
			)
		)
	)
);
wcj_ai_receipt_bad(
	'encoder_32769_byte_boundary_only',
	static fn() => $wcj_ai_encoder->invoke(
		null,
		array(
			'schema_version' => 2,
			'x'              => str_repeat( 'a', 32742 ),
		)
	)
);

echo json_encode(
	array(
		'scope'      => 'Pure synthetic envelope/projection tests only. Encoder byte-limit cases are not valid full receipt examples. No consent, grants, store queries, persistence or client proof.',
		'passed'     => ! in_array( false, $wcj_ai_results, true ),
		'assertions' => count( $wcj_ai_results ),
		'cases'      => $wcj_ai_results,
	),
	JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
) . "\n";
exit( in_array( false, $wcj_ai_results, true ) ? 1 : 0 );
