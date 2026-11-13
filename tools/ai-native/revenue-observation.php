<?php
/**
 * Native synthetic financial observations; not complete reporting/HPOS proof.
 *
 * @package Booster_For_WooCommerce/tools
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 ); }
require __DIR__ . '/bootstrap.php';
require_once WP_PLUGIN_DIR . '/woocommerce-jetpack/includes/class-wcj-ai-revenue-observation.php';
$wcj_ai_created = array();
/**
 * Create one synthetic order.
 *
 * @param int    $wcj_ai_stamp Paid timestamp.
 * @param string $wcj_ai_currency Currency.
 * @param string $wcj_ai_amount Order amount.
 * @param string $wcj_ai_status Status.
 * @return WC_Order Created order.
 */
function wcj_ai_order( $wcj_ai_stamp, $wcj_ai_currency, $wcj_ai_amount, $wcj_ai_status = 'completed' ) {
	global $wcj_ai_created;
	$wcj_ai_order = new WC_Order();
	$wcj_ai_order->set_currency( $wcj_ai_currency );
	$wcj_ai_order->set_status( $wcj_ai_status );
	$wcj_ai_order->set_date_created( $wcj_ai_stamp - 100 );
	$wcj_ai_order->set_date_paid( $wcj_ai_stamp );
	$wcj_ai_order->set_total( $wcj_ai_amount );
	$wcj_ai_order->save();
	$wcj_ai_created[] = $wcj_ai_order;
	return $wcj_ai_order;
}
/**
 * Find one labeled aggregate.
 *
 * @param array  $wcj_ai_result Observation.
 * @param string $wcj_ai_stream Stream.
 * @param string $wcj_ai_currency Currency.
 * @param int    $wcj_ai_period Period index.
 * @return array|null Aggregate.
 */
function wcj_ai_group( $wcj_ai_result, $wcj_ai_stream, $wcj_ai_currency, $wcj_ai_period ) {
	foreach ( $wcj_ai_result['streams'][ $wcj_ai_stream ]['groups'] ?? array() as $wcj_ai_group ) {
		if ( $wcj_ai_group['currency'] === $wcj_ai_currency && $wcj_ai_group['period'] === $wcj_ai_period ) {
			return $wcj_ai_group; }
	}
	return null;
}
try {
	$wcj_ai_periods    = WCJ_AI_Report_Values::completed_week_pair( time(), wp_timezone_string() );
	$wcj_ai_older      = strtotime( $wcj_ai_periods['periods'][0]['utc_start'] ) + 3600;
	$wcj_ai_newer      = strtotime( $wcj_ai_periods['periods'][1]['utc_start'] ) + 3600;
	$wcj_ai_usd        = wcj_ai_order( $wcj_ai_older, 'USD', '100.25' );
	$wcj_ai_eur        = wcj_ai_order( $wcj_ai_newer, 'EUR', '70.50' );
	$wcj_ai_excluded   = wcj_ai_order( $wcj_ai_newer, 'USD', '999.99', 'cancelled' );
	$wcj_ai_old_parent = wcj_ai_order( $wcj_ai_older - 86400 * 30, 'USD', '20' );
	$wcj_ai_refund     = new WC_Order_Refund();
	$wcj_ai_refund->set_parent_id( $wcj_ai_old_parent->get_id() );
	$wcj_ai_refund->set_currency( 'USD' );
	$wcj_ai_refund->set_amount( '5.25' );
	$wcj_ai_refund->set_date_created( $wcj_ai_newer );
	$wcj_ai_refund->save();
	$wcj_ai_created[] = $wcj_ai_refund;
	$wcj_ai_result    = WCJ_AI_Revenue_Observation::observe();
	wcj_ai_check( 'separate_usd_older', '100.25' === ( wcj_ai_group( $wcj_ai_result, 'paid_cohort_order_totals', 'USD', 0 )['amount'] ?? null ) );
	wcj_ai_check( 'separate_eur_newer', '70.5' === ( wcj_ai_group( $wcj_ai_result, 'paid_cohort_order_totals', 'EUR', 1 )['amount'] ?? null ) );
	wcj_ai_check( 'cancelled_excluded', null === wcj_ai_group( $wcj_ai_result, 'paid_cohort_order_totals', 'USD', 1 ) );
	wcj_ai_check( 'independent_old_parent_refund_event', '5.25' === ( wcj_ai_group( $wcj_ai_result, 'recorded_refund_events', 'USD', 1 )['amount'] ?? null ) );
	wcj_ai_check( 'no_invented_net_or_completeness', false === $wcj_ai_result['overall_complete'] && false === $wcj_ai_result['membership_complete'] && null === $wcj_ai_result['net_revenue'] && null === $wcj_ai_result['return_requests'] );
	wcj_ai_check( 'period_currency_context_retained', $wcj_ai_periods['periods'] === $wcj_ai_result['periods']['periods'] && $wcj_ai_periods['timezone'] === $wcj_ai_result['periods']['timezone'] );
	wp_using_ext_object_cache( true );
	$wcj_ai_unqualified = WCJ_AI_Revenue_Observation::observe();
	wp_using_ext_object_cache( false );
	wcj_ai_check( 'persistent_cache_fail_closed', 'freshness_unverified' === ( $wcj_ai_unqualified['reason'] ?? null ) && array() === $wcj_ai_unqualified['streams'] );
	delete_post_meta( $wcj_ai_usd->get_id(), '_order_currency' );
	$wcj_ai_missing = WCJ_AI_Revenue_Observation::observe();
	wcj_ai_check( 'missing_currency_not_store_default', null === $wcj_ai_missing['streams']['paid_cohort_order_totals']['groups'] );
	update_post_meta( $wcj_ai_usd->get_id(), '_order_currency', 'USD' );
	update_post_meta( $wcj_ai_usd->get_id(), '_order_total', '100.251234' );
	$wcj_ai_rounded = WCJ_AI_Revenue_Observation::observe();
	wcj_ai_check( 'rounded_getter_not_exact_source', null === $wcj_ai_rounded['streams']['paid_cohort_order_totals']['groups'] );
	update_post_meta( $wcj_ai_usd->get_id(), '_order_total', '100.25' );
	$wcj_ai_empty_filter = static fn() => array();
	add_filter( 'woocommerce_order_query', $wcj_ai_empty_filter, 999 );
	$wcj_ai_filtered     = WCJ_AI_Revenue_Observation::observe();
	remove_filter( 'woocommerce_order_query', $wcj_ai_empty_filter, 999 );
	wcj_ai_check( 'filtered_empty_never_complete', false === $wcj_ai_filtered['membership_complete'] && 'observed_subset' === $wcj_ai_filtered['streams']['paid_cohort_order_totals']['status'] );
	$wcj_ai_count   = 0;
	$wcj_ai_mutator = static function ( $wcj_ai_query ) use ( &$wcj_ai_count, $wcj_ai_usd ) {
		if ( str_starts_with( $wcj_ai_query, 'SELECT post_id, meta_key, meta_value' ) &&
			preg_match( '/post_id IN \(\s*' . $wcj_ai_usd->get_id() . '\s*\)/', $wcj_ai_query ) && 2 === ++$wcj_ai_count ) {
			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Exact synthetic entity fault injection before verification hydration.
			$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value = %s WHERE post_id = %d AND meta_key = %s", '101.25', $wcj_ai_usd->get_id(), '_order_total' ) );
		}
		return $wcj_ai_query;
	};
	add_filter( 'query', $wcj_ai_mutator, 999 );
	$wcj_ai_changed = WCJ_AI_Revenue_Observation::observe();
	remove_filter( 'query', $wcj_ai_mutator, 999 );
	wcj_ai_check( 'changed_during_verification_unavailable', $wcj_ai_count >= 2 && null === $wcj_ai_changed['streams']['paid_cohort_order_totals']['groups'] );
	update_post_meta( $wcj_ai_usd->get_id(), '_order_total', '100.25' );
	delete_post_meta( $wcj_ai_usd->get_id(), '_order_total' );
	$wcj_ai_missing_total = WCJ_AI_Revenue_Observation::observe();
	wcj_ai_check( 'missing_total_not_zero', null === $wcj_ai_missing_total['streams']['paid_cohort_order_totals']['groups'] );
	update_post_meta( $wcj_ai_usd->get_id(), '_order_total', '100.25' );
	wp_set_current_user( 0 );
	wcj_ai_check( 'unauthenticated_read_denied', wcj_ai_denied( static fn() => WCJ_AI_Revenue_Observation::observe() ) );
	wp_set_current_user( $wcj_ai_owner->ID );
	for ( $wcj_ai_i = 0; $wcj_ai_i < 26; ++$wcj_ai_i ) {
		wcj_ai_order( $wcj_ai_newer, 'USD', '1' ); }
	$wcj_ai_capped = WCJ_AI_Revenue_Observation::observe();
	wcj_ai_check( 'cap_is_incomplete_not_resumable', false === $wcj_ai_capped['resumable'] && false === $wcj_ai_capped['overall_complete'] && ( $wcj_ai_capped['streams']['paid_cohort_order_totals']['cap_reached'] ?? false ) );
} finally {
	wp_using_ext_object_cache( false );
	foreach ( array_reverse( $wcj_ai_created ) as $wcj_ai_entity ) {
		$wcj_ai_entity->delete( true ); }
}
wcj_ai_done( 'native legacy synthetic subset observations only; no full totals, product allocation, Returns or HPOS claim' );
