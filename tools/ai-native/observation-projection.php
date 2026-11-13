<?php
/**
 * Pure synthetic projection contracts, not native provenance or client proof.
 *
 * @package Booster_For_WooCommerce/tools
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}
define( 'ABSPATH', __DIR__ ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Standalone pure test; no WordPress bootstrap.
require_once __DIR__ . '/class-wcj-ai-projection-poison.php';
require_once dirname( __DIR__, 2 ) . '/includes/class-wcj-ai-observation-projection.php';
$wcj_ai_results = array();

/**
 * Record a synthetic assertion.
 *
 * @param string $name Case.
 * @param bool   $ok Outcome.
 */
function wcj_ai_projection_check( $name, $ok ) {
	global $wcj_ai_results;
	$wcj_ai_results[ $name ] = true === $ok;
}

/**
 * Mutate one supported DTO and require the one finite refusal.
 *
 * @param string   $name Case.
 * @param string   $kind Projector.
 * @param array    $source Baseline.
 * @param callable $mutate Mutation.
 */
function wcj_ai_projection_bad( $name, $kind, $source, $mutate ) {
	$mutate( $source );
	try {
		WCJ_AI_Observation_Projection::$kind( $source );
		wcj_ai_projection_check( $name, false );
	} catch ( InvalidArgumentException $exception ) {
		wcj_ai_projection_check( $name, 'Unsupported observation projection.' === $exception->getMessage() );
	}
}

$wcj_ai_calendar = WCJ_AI_Report_Values::completed_week_pair( 1794564000, 'America/New_York' );
$wcj_ai_revenue  = array(
	'schema'                 => 1,
	'overall_complete'       => false,
	'non_atomic'             => true,
	'membership_complete'    => false,
	'query_basis'            => 'native_filtered_query_subset',
	'resumable'              => false,
	'periods'                => $wcj_ai_calendar,
	'storage'                => 'legacy_native_uncached',
	'streams'                => array(
		'paid_cohort_order_totals' => array(
			'status'       => 'observed_subset',
			'amount_basis' => 'recorded_order_total_including_tax_shipping_fees_before_refunds',
			'cap_reached'  => false,
			'groups'       => array(
				array(
					'period'   => 0,
					'currency' => 'USD',
					'count'    => 2,
					'amount'   => '100.35',
				),
				array(
					'period'   => 1,
					'currency' => 'EUR',
					'count'    => 1,
					'amount'   => '70.5',
				),
				array(
					'period'   => 1,
					'currency' => 'USD',
					'count'    => 1,
					'amount'   => '0',
				),
			),
		),
		'recorded_refund_events'   => array(
			'status'       => 'observed_subset',
			'amount_basis' => 'recorded_refund_amount_not_return_requests',
			'cap_reached'  => false,
			'groups'       => array(
				array(
					'period'   => 1,
					'currency' => 'USD',
					'count'    => 1,
					'amount'   => '5.25',
				),
			),
		),
	),
	'cohort_refunds'         => null,
	'product_breakdown'      => null,
	'return_requests'        => null,
	'net_revenue'            => null,
	'unavailable_components' => array( 'cohort_refund_allocation', 'product_variation_breakdown', 'return_requests', 'net_revenue' ),
	'limits'                 => array(
		'ids_per_stream'                    => 25,
		'queries'                           => 4,
		'hydrations'                        => 100,
		'soft_seconds'                      => 2,
		'native_operation_duration_bounded' => false,
	),
	'observed_until_utc'     => gmdate( 'Y-m-d\TH:i:s\Z', 1794564000 + 5 ),
);
$wcj_ai_original = $wcj_ai_revenue;
$wcj_ai_out      = WCJ_AI_Observation_Projection::revenue( $wcj_ai_revenue );
wcj_ai_projection_check( 'report_input_unchanged', $wcj_ai_original === $wcj_ai_revenue );
wcj_ai_projection_check( 'separate_exact_groups', $wcj_ai_out['streams']['paid_cohort_order_totals']['groups'] === $wcj_ai_revenue['streams']['paid_cohort_order_totals']['groups'] && $wcj_ai_out['streams']['recorded_refund_events']['groups'] === $wcj_ai_revenue['streams']['recorded_refund_events']['groups'] );
wcj_ai_projection_check( 'no_inferred_completeness_or_net', false === $wcj_ai_out['overall_complete'] && false === $wcj_ai_out['membership_complete'] && null === $wcj_ai_out['net_revenue'] && null === $wcj_ai_out['return_requests'] && false === $wcj_ai_out['resumable'] );
wcj_ai_projection_check( 'frozen_calendar', $wcj_ai_out['periods'] === $wcj_ai_calendar );
wcj_ai_projection_check( 'api_call_not_sql_limit', 4 === $wcj_ai_out['limits']['wc_get_orders_calls_max'] && ! isset( $wcj_ai_out['limits']['queries'] ) && false === $wcj_ai_out['limits']['native_operation_duration_bounded'] );
$wcj_ai_copy                       = $wcj_ai_revenue;
$wcj_ai_copy['observed_until_utc'] = gmdate( 'Y-m-d\TH:i:s\Z', 1794564000 + 86400 );
wcj_ai_projection_check( 'no_new_day_recollection_or_hard_duration_inference', WCJ_AI_Observation_Projection::revenue( $wcj_ai_copy )['periods'] === $wcj_ai_calendar );
foreach ( array( null, 'legacy_native_uncached' ) as $wcj_ai_storage ) {
	$wcj_ai_copy            = $wcj_ai_revenue;
	$wcj_ai_copy['storage'] = $wcj_ai_storage;
	$wcj_ai_copy['periods'] = null;
	$wcj_ai_copy['streams'] = array();
	$wcj_ai_copy['reason']  = 'observation_unavailable';
	$wcj_ai_out             = WCJ_AI_Observation_Projection::revenue( $wcj_ai_copy );
	wcj_ai_projection_check( 'root_failure_' . ( $wcj_ai_storage ?? 'null' ), 2 === count( $wcj_ai_out['streams'] ) && null === $wcj_ai_out['streams']['paid_cohort_order_totals']['groups'] && null === $wcj_ai_out['streams']['paid_cohort_order_totals']['cap_reached'] && 'unavailable' === $wcj_ai_out['streams']['recorded_refund_events']['status'] );
}
foreach ( array( 'freshness_unverified', 'query_unavailable', 'stale_or_missing_entity', 'unsupported_currency', 'unsupported_persisted_facts', 'unsupported_amount', 'soft_time_limit', 'stale_membership_or_context', 'observation_unavailable' ) as $wcj_ai_reason ) {
	$wcj_ai_copy                                      = $wcj_ai_revenue;
	$wcj_ai_copy['streams']['recorded_refund_events'] = array(
		'status'       => 'unavailable',
		'amount_basis' => 'recorded_refund_amount_not_return_requests',
		'groups'       => null,
		'reason'       => $wcj_ai_reason,
	);
	$wcj_ai_out                                       = WCJ_AI_Observation_Projection::revenue( $wcj_ai_copy );
	wcj_ai_projection_check( 'unavailable_' . $wcj_ai_reason, $wcj_ai_reason === $wcj_ai_out['streams']['recorded_refund_events']['reason'] && null === $wcj_ai_out['streams']['recorded_refund_events']['groups'] );
}
foreach ( array( '0', '0.000001', '1800000000000', '9223372036854.775807' ) as $wcj_ai_amount ) {
	$wcj_ai_copy = $wcj_ai_revenue;
	$wcj_ai_copy['streams']['paid_cohort_order_totals']['groups'][0]['amount'] = $wcj_ai_amount;
	wcj_ai_projection_check( 'exact_aggregate_' . $wcj_ai_amount, WCJ_AI_Observation_Projection::revenue( $wcj_ai_copy )['streams']['paid_cohort_order_totals']['groups'][0]['amount'] === $wcj_ai_amount );
}
foreach ( array( '9223372036854.775808', '9999999999999', '-1', '-0', '01', '1.0', '0.0000001', '1e2', '1,25', 'NaN', '1\n', 1, 1.5, new WCJ_AI_Projection_Poison() ) as $wcj_ai_i => $wcj_ai_amount ) {
	wcj_ai_projection_bad(
		'bad_aggregate_' . $wcj_ai_i,
		'revenue',
		$wcj_ai_revenue,
		static function ( &$v ) use ( $wcj_ai_amount ) {
			$v['streams']['paid_cohort_order_totals']['groups'][0]['amount'] = $wcj_ai_amount;
		}
	);
}
$wcj_ai_report_mutations = array(
	'root_private_extra'              => static function ( &$v ) {
		$v['customer'] = new WCJ_AI_Projection_Poison(); },
	'missing_stream'                  => static function ( &$v ) {
		unset( $v['streams']['recorded_refund_events'] ); },
	'stream_extra'                    => static function ( &$v ) {
		$v['streams']['recorded_refund_events']['orders'] = array( 17 ); },
	'group_extra'                     => static function ( &$v ) {
		$v['streams']['paid_cohort_order_totals']['groups'][0]['order_id'] = 17; },
	'calendar_extra'                  => static function ( &$v ) {
		$v['periods']['periods'][0]['private'] = 'CUSTOMER'; },
	'calendar_changed'                => static function ( &$v ) {
		++$v['periods']['periods'][0]['elapsed_seconds']; },
	'calendar_coercion'               => static function ( &$v ) {
		$v['periods']['periods'][0]['local_days'] = '7'; },
	'timezone_object'                 => static function ( &$v ) {
		$v['periods']['timezone'] = new WCJ_AI_Projection_Poison(); },
	'impossible_finish_date'          => static function ( &$v ) {
		$v['observed_until_utc'] = '2026-02-30T00:00:00Z'; },
	'backward_finish'                 => static function ( &$v ) {
		$v['observed_until_utc'] = '1970-01-01T00:00:00Z'; },
	'unknown_storage'                 => static function ( &$v ) {
		$v['storage'] = 'hpos_native_uncached'; },
	'unsupported_limits'              => static function ( &$v ) {
		$v['limits']['queries'] = 5; },
	'pretend_complete'                => static function ( &$v ) {
		$v['overall_complete'] = true; },
	'currency_private'                => static function ( &$v ) {
		$v['streams']['paid_cohort_order_totals']['groups'][0]['currency'] = 'CUSTOMER'; },
	'duplicate_group'                 => static function ( &$v ) {
		$v['streams']['paid_cohort_order_totals']['groups'][] = $v['streams']['paid_cohort_order_totals']['groups'][0]; },
	'count_sum'                       => static function ( &$v ) {
		$v['streams']['paid_cohort_order_totals']['groups'][0]['count'] = 25; },
	'count_type'                      => static function ( &$v ) {
		$v['streams']['paid_cohort_order_totals']['groups'][0]['count'] = '2'; },
	'wrong_period'                    => static function ( &$v ) {
		$v['streams']['paid_cohort_order_totals']['groups'][0]['period'] = 2; },
	'bad_list'                        => static function ( &$v ) {
		unset( $v['streams']['paid_cohort_order_totals']['groups'][0] ); },
	'cap_without_25'                  => static function ( &$v ) {
		$v['streams']['paid_cohort_order_totals']['cap_reached'] = true; },
	'empty_failure_not_empty_success' => static function ( &$v ) {
		$v['reason'] = 'query_unavailable';
		$v['streams'] = array();
		$v['reason'] = 'PRIVATE_CUSTOMER'; },
);
foreach ( $wcj_ai_report_mutations as $wcj_ai_name => $wcj_ai_mutation ) {
	wcj_ai_projection_bad( $wcj_ai_name, 'revenue', $wcj_ai_revenue, $wcj_ai_mutation );
}
$wcj_ai_copy = $wcj_ai_revenue;
$wcj_ai_copy['streams']['paid_cohort_order_totals']['groups'] = array();
wcj_ai_projection_check( 'empty_is_incomplete_observed_subset', array() === WCJ_AI_Observation_Projection::revenue( $wcj_ai_copy )['streams']['paid_cohort_order_totals']['groups'] );
$wcj_ai_copy['streams']['paid_cohort_order_totals']['groups']      = array(
	array(
		'period'   => 0,
		'currency' => 'USD',
		'count'    => 25,
		'amount'   => '0',
	),
);
$wcj_ai_copy['streams']['paid_cohort_order_totals']['cap_reached'] = true;
wcj_ai_projection_check( 'cap_preserved', true === WCJ_AI_Observation_Projection::revenue( $wcj_ai_copy )['streams']['paid_cohort_order_totals']['cap_reached'] );

$wcj_ai_scalar   = WCJ_AI_Price_Value::project( '100.00' );
$wcj_ai_event    = array(
	'sequence'     => 0,
	'parent'       => null,
	'hook'         => 'woocommerce_product_get_price',
	'priority'     => PHP_INT_MAX,
	'invocation'   => 'method_observed_dispatch_unqualified',
	'input'        => $wcj_ai_scalar,
	'output'       => WCJ_AI_Price_Value::project( 120.0 ),
	'decisions'    => array(
		array(
			'reason'         => 'global_multiplier',
			'evaluator_role' => 'role_2',
			'role_source'    => 'booster_effective_role',
			'factor'         => WCJ_AI_Price_Value::project( 1.2 ),
			'result'         => WCJ_AI_Price_Value::project( 120.0 ),
		),
	),
	'cache'        => 'miss',
	'cache_origin' => null,
	'cache_role'   => 'role_1',
);
$wcj_ai_role     = array(
	'schema'      => 1,
	'phase'       => 'product_render',
	'persistence' => 'none',
	'non_atomic'  => true,
	'unobserved'  => array( 'earlier_phases', 'other_role_module_instances', 'wholesale', 'multicurrency', 'addons', 'fees', 'payment', 'shipping' ),
	'truncated'   => false,
	'events'      => array( $wcj_ai_event ),
);
$wcj_ai_original = $wcj_ai_role;
$wcj_ai_out      = WCJ_AI_Observation_Projection::role( $wcj_ai_role );
wcj_ai_projection_check( 'role_unchanged', $wcj_ai_original === $wcj_ai_role );
wcj_ai_projection_check( 'priority_exact_text', (string) PHP_INT_MAX === $wcj_ai_out['events'][0]['priority_text'] );
wcj_ai_projection_check( 'separate_role_aliases', 'role_1' === $wcj_ai_out['events'][0]['cache_role'] && 'role_2' === $wcj_ai_out['events'][0]['decisions'][0]['evaluator_role'] );
wcj_ai_projection_check( 'no_inferred_price_currency_dispatch', null === $wcj_ai_out['effective_storefront_price'] && 'unqualified' === $wcj_ai_out['currency_context'] && 'method_observed_dispatch_unqualified' === $wcj_ai_out['dispatch'] );
foreach ( array( '', '0', '-0.00', 0, -1000000000, 1000000000, 0.1, -0.0, 1.0e-300, 'PRIVATE_CUSTOMER', new WCJ_AI_Projection_Poison() ) as $wcj_ai_i => $wcj_ai_value ) {
	$wcj_ai_copy                       = $wcj_ai_role;
	$wcj_ai_copy['events'][0]['input'] = WCJ_AI_Price_Value::project( $wcj_ai_value );
	wcj_ai_projection_check( 'scalar_preserved_' . $wcj_ai_i, WCJ_AI_Observation_Projection::role( $wcj_ai_copy )['events'][0]['input'] === $wcj_ai_copy['events'][0]['input'] );
}
foreach ( array( 'extension_veto', 'changed_product', 'addon_guard', 'product_empty', 'regular_skip', 'product_multiplier', 'display_delegate', 'product_fixed_extension', 'product_regular', 'product_sale', 'on_sale_skip', 'category_empty', 'category_multiplier', 'tag_empty', 'tag_multiplier', 'global_empty', 'global_multiplier', 'unchanged' ) as $wcj_ai_reason ) {
	$wcj_ai_copy                                        = $wcj_ai_role;
	$wcj_ai_copy['events'][0]['decisions'][0]['reason'] = $wcj_ai_reason;
	wcj_ai_projection_check( 'native_reason_' . $wcj_ai_reason, WCJ_AI_Observation_Projection::role( $wcj_ai_copy )['events'][0]['decisions'][0]['reason'] === $wcj_ai_reason );
}
$wcj_ai_copy                              = $wcj_ai_role;
$wcj_ai_copy['events'][0]['cache']        = 'hit';
$wcj_ai_copy['events'][0]['cache_origin'] = 1;
$wcj_ai_copy['events'][]                  = $wcj_ai_event;
$wcj_ai_copy['events'][1]['sequence']     = 1;
$wcj_ai_copy['events'][1]['parent']       = 0;
$wcj_ai_copy['events'][1]['input']        = WCJ_AI_Price_Value::project( 100 );
$wcj_ai_out                               = WCJ_AI_Observation_Projection::role( $wcj_ai_copy );
wcj_ai_projection_check( 'nested_later_sequence_cache_origin_preserved', 1 === $wcj_ai_out['events'][0]['cache_origin'] && 'integer' === $wcj_ai_out['events'][1]['input']['native_type'] && 'string' === $wcj_ai_out['events'][0]['input']['native_type'] );
$wcj_ai_copy           = $wcj_ai_role;
$wcj_ai_copy['events'] = array();
wcj_ai_projection_check( 'empty_trace_not_diagnosis', array() === WCJ_AI_Observation_Projection::role( $wcj_ai_copy )['events'] );
$wcj_ai_copy['truncated'] = true;
wcj_ai_projection_check( 'empty_truncated_preserved', true === WCJ_AI_Observation_Projection::role( $wcj_ai_copy )['truncated'] );
$wcj_ai_copy                            = $wcj_ai_role;
$wcj_ai_copy['events'][0]['output']     = null;
$wcj_ai_copy['events'][0]['cache']      = 'unobserved';
$wcj_ai_copy['events'][0]['cache_role'] = null;
$wcj_ai_copy['truncated']               = true;
wcj_ai_projection_check( 'unfinished_preserved', null === WCJ_AI_Observation_Projection::role( $wcj_ai_copy )['events'][0]['output'] );
$wcj_ai_role_mutations = array(
	'role_root_extra'     => static function ( &$v ) {
		$v['product_id'] = 10; },
	'event_extra'         => static function ( &$v ) {
		$v['events'][0]['cache_key'] = 'PRIVATE'; },
	'scalar_extra'        => static function ( &$v ) {
		$v['events'][0]['input']['raw'] = new WCJ_AI_Projection_Poison(); },
	'decision_extra'      => static function ( &$v ) {
		$v['events'][0]['decisions'][0]['role_name'] = 'administrator'; },
	'role_slug'           => static function ( &$v ) {
		$v['events'][0]['cache_role'] = 'administrator'; },
	'alias_bound'         => static function ( &$v ) {
		$v['events'][0]['cache_role'] = 'role_33'; },
	'reason_private'      => static function ( &$v ) {
		$v['events'][0]['decisions'][0]['reason'] = 'CUSTOMER'; },
	'sequence_coercion'   => static function ( &$v ) {
		$v['events'][0]['sequence'] = '0'; },
	'parent_self'         => static function ( &$v ) {
		$v['events'][0]['parent'] = 0; },
	'priority_coercion'   => static function ( &$v ) {
		$v['events'][0]['priority'] = '10'; },
	'hook_private'        => static function ( &$v ) {
		$v['events'][0]['hook'] = 'private_hook'; },
	'origin_self'         => static function ( &$v ) {
		$v['events'][0]['cache'] = 'hit';
		$v['events'][0]['cache_origin'] = 0; },
	'origin_missing'      => static function ( &$v ) {
		$v['events'][0]['cache'] = 'hit';
		$v['events'][0]['cache_origin'] = 1; },
	'false_complete'      => static function ( &$v ) {
		$v['events'][0]['output'] = null; },
	'truncation_coercion' => static function ( &$v ) {
		$v['truncated'] = 0; },
	'event_limit'         => static function ( &$v ) {
		$v['events'] = array_fill( 0, 65, $v['events'][0] ); },
	'decision_limit'      => static function ( &$v ) {
		$v['events'][0]['decisions'] = array_fill( 0, 9, $v['events'][0]['decisions'][0] ); },
	'float_noncanonical'  => static function ( &$v ) {
		$v['events'][0]['input'] = array(
			'kind'        => 'numeric',
			'native_type' => 'float',
			'encoding'    => 'binary64_g17_v1',
			'value'       => '1.0',
		); },
	'float_overflow'      => static function ( &$v ) {
		$v['events'][0]['input'] = array(
			'kind'        => 'numeric',
			'native_type' => 'float',
			'encoding'    => 'binary64_g17_v1',
			'value'       => '1e999',
		); },
	'string_exponent'     => static function ( &$v ) {
		$v['events'][0]['input']['value'] = '1e2'; },
	'unsupported_private' => static function ( &$v ) {
		$v['events'][0]['input'] = array(
			'kind'   => 'unsupported',
			'reason' => 'PRIVATE_CUSTOMER',
		); },
	'object_scalar'       => static function ( &$v ) {
		$v['events'][0]['input'] = new WCJ_AI_Projection_Poison(); },
	'object_value'        => static function ( &$v ) {
		$v['events'][0]['input']['value'] = new WCJ_AI_Projection_Poison(); },
	'too_deep'            => static function ( &$v ) {
		$event = $v['events'][0]; for ( $i = 1; $i < 17; ++$i ) {
			$v['events'][] = $event;
			$v['events'][ $i ]['sequence'] = $i;
			$v['events'][ $i ]['parent'] = $i - 1; } },
	'payload_cap'         => static function ( &$v ) {
		$event = $v['events'][0];
		$event['decisions'] = array_fill( 0, 8, $event['decisions'][0] ); for ( $i = 0; $i < 64; ++$i ) {
			$v['events'][ $i ] = $event;
			$v['events'][ $i ]['sequence'] = $i; } },
);
foreach ( $wcj_ai_role_mutations as $wcj_ai_name => $wcj_ai_mutation ) {
	wcj_ai_projection_bad( $wcj_ai_name, 'role', $wcj_ai_role, $wcj_ai_mutation );
}
foreach ( array( array( 0, 'UTC' ), array( 1794564000, '+05:30' ) ) as $wcj_ai_i => $wcj_ai_context ) {
	$wcj_ai_copy                       = $wcj_ai_revenue;
	$wcj_ai_copy['periods']            = WCJ_AI_Report_Values::completed_week_pair( $wcj_ai_context[0], $wcj_ai_context[1] );
	$wcj_ai_copy['observed_until_utc'] = gmdate( 'Y-m-d\TH:i:s\Z', $wcj_ai_context[0] );
	wcj_ai_projection_check( 'explicit_calendar_' . $wcj_ai_i, WCJ_AI_Observation_Projection::revenue( $wcj_ai_copy )['periods'] === $wcj_ai_copy['periods'] );
}
wcj_ai_projection_bad(
	'unsorted_groups',
	'revenue',
	$wcj_ai_revenue,
	static function ( &$v ) {
		$v['streams']['paid_cohort_order_totals']['groups'] = array_reverse( $v['streams']['paid_cohort_order_totals']['groups'] );
	}
);
wcj_ai_projection_bad(
	'group_cardinality',
	'revenue',
	$wcj_ai_revenue,
	static function ( &$v ) {
		$v['streams']['paid_cohort_order_totals']['groups'] = array_fill( 0, 26, $v['streams']['paid_cohort_order_totals']['groups'][0] );
	}
);
$wcj_ai_copy = $wcj_ai_role;
for ( $wcj_ai_i = 0; $wcj_ai_i < 64; ++$wcj_ai_i ) {
	$wcj_ai_copy['events'][ $wcj_ai_i ]             = $wcj_ai_event;
	$wcj_ai_copy['events'][ $wcj_ai_i ]['sequence'] = $wcj_ai_i;
}
// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Standalone test; this fixture contains only synthetic scalars.
$wcj_ai_bytes = strlen( json_encode( $wcj_ai_copy ) );
wcj_ai_projection_check( 'local_trace_not_limited_by_future_share_cap', $wcj_ai_bytes > 32768 && $wcj_ai_bytes <= 65536 && 64 === count( WCJ_AI_Observation_Projection::role( $wcj_ai_copy )['events'] ) );
$wcj_ai_copy                       = $wcj_ai_revenue;
$wcj_ai_copy['periods']            = WCJ_AI_Report_Values::completed_week_pair( 4102444800, 'UTC' );
$wcj_ai_copy['observed_until_utc'] = '2100-01-01T00:00:01Z';
wcj_ai_projection_check( 'finish_above_calendar_ceiling', '2100-01-01T00:00:01Z' === WCJ_AI_Observation_Projection::revenue( $wcj_ai_copy )['observed_until_utc'] );
$wcj_ai_copy['periods'] = null;
$wcj_ai_copy['streams'] = array();
$wcj_ai_copy['reason']  = 'observation_unavailable';
wcj_ai_projection_check( 'root_failure_above_calendar_ceiling', '2100-01-01T00:00:01Z' === WCJ_AI_Observation_Projection::revenue( $wcj_ai_copy )['observed_until_utc'] );
wcj_ai_projection_bad(
	'closed_parent_revived',
	'role',
	$wcj_ai_role,
	static function ( &$v ) {
		$v['events']                = array_fill( 0, 3, $v['events'][0] );
		$v['events'][1]['sequence'] = 1;
		$v['events'][2]['sequence'] = 2;
		$v['events'][2]['parent']   = 0;
	}
);
wcj_ai_projection_bad(
	'origin_later_unrelated',
	'role',
	$wcj_ai_role,
	static function ( &$v ) {
		$v['events'][]                  = $v['events'][0];
		$v['events'][1]['sequence']     = 1;
		$v['events'][0]['cache']        = 'hit';
		$v['events'][0]['cache_origin'] = 1;
	}
);
wcj_ai_projection_bad(
	'origin_earlier_open_ancestor',
	'role',
	$wcj_ai_role,
	static function ( &$v ) {
		$v['events'][]                  = $v['events'][0];
		$v['events'][1]['sequence']     = 1;
		$v['events'][1]['parent']       = 0;
		$v['events'][1]['cache']        = 'hit';
		$v['events'][1]['cache_origin'] = 0;
	}
);
$wcj_ai_copy = $wcj_ai_role;
for ( $wcj_ai_i = 0; $wcj_ai_i < 64; ++$wcj_ai_i ) {
	$wcj_ai_copy['events'][ $wcj_ai_i ]             = $wcj_ai_event;
	$wcj_ai_copy['events'][ $wcj_ai_i ]['sequence'] = $wcj_ai_i;
}
for ( $wcj_ai_i = 0; $wcj_ai_i < 64; ++$wcj_ai_i ) {
	for ( $wcj_ai_j = 1; $wcj_ai_j < 8; ++$wcj_ai_j ) {
		$wcj_ai_copy['events'][ $wcj_ai_i ]['decisions'][] = $wcj_ai_event['decisions'][0];
        // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Synthetic scalar-only boundary fixture.
		if ( strlen( json_encode( $wcj_ai_copy ) ) > 65480 ) {
			array_pop( $wcj_ai_copy['events'][ $wcj_ai_i ]['decisions'] );
			break;
		}
	}
}
// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Synthetic scalar-only boundary fixture.
$wcj_ai_gap = 65520 - strlen( json_encode( $wcj_ai_copy ) );
for ( $wcj_ai_i = 0; $wcj_ai_i < 64 && $wcj_ai_gap > 0; ++$wcj_ai_i ) {
	$wcj_ai_pad = min( 58, $wcj_ai_gap );
	$wcj_ai_copy['events'][ $wcj_ai_i ]['input']['value'] .= str_repeat( '0', $wcj_ai_pad );
	$wcj_ai_gap -= $wcj_ai_pad;
}
// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Synthetic scalar-only boundary fixture.
$wcj_ai_size = strlen( json_encode( $wcj_ai_copy ) );
$wcj_ai_out  = WCJ_AI_Observation_Projection::role( $wcj_ai_copy );
// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Synthetic scalar-only boundary fixture.
$wcj_ai_size_out = strlen( json_encode( $wcj_ai_out ) );
wcj_ai_projection_check( 'near_native_64k_boundary_preserved', 65520 === $wcj_ai_size && $wcj_ai_size_out > 66048 && $wcj_ai_size_out <= 66560 && 573 === $wcj_ai_size_out - $wcj_ai_size );
wcj_ai_projection_check( 'no_observation_stays_null', null === WCJ_AI_Observation_Projection::role( null ) );
wcj_ai_projection_check( 'no_object_conversion_or_serialization', 0 === WCJ_AI_Projection_Poison::$calls );
// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Standalone CLI without WordPress.
echo json_encode(
	array(
		'scope' => 'pure synthetic projection contracts only; not provenance, consent, receipt, native integration or client evidence',
		'php'   => PHP_VERSION,
		'cases' => $wcj_ai_results,
	),
	JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR
); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Standalone CLI without WordPress.
exit( in_array( false, $wcj_ai_results, true ) ? 1 : 0 );
