<?php
/**
 * Docker/WP-CLI integration checks for Booster 8.4 Order Health.
 *
 * Run with: wp eval-file tests/integration/codex-8-4-order-health.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$failures    = array();
$fixture_ids = array();

add_filter( 'pre_wp_mail', '__return_false' );

$assert = function ( $condition, $message ) use ( &$failures ) {
	if ( ! $condition ) {
		$failures[] = $message;
	}
};

$make_date = function ( $seconds_ago ) {
	$date = new WC_DateTime( '@' . ( time() - $seconds_ago ) );
	$date->setTimezone( new DateTimeZone( 'UTC' ) );
	return $date;
};

$create_order = function ( $status, $seconds_ago, $total = 100, $paid = false ) use ( &$fixture_ids, $make_date ) {
	$order = wc_create_order();
	$order->set_status( $status );
	$order->set_total( $total );
	$order->set_date_created( $make_date( $seconds_ago ) );
	if ( $paid ) {
		$order->set_date_paid( $make_date( $seconds_ago ) );
	}
	$order->update_meta_data( '_wcj_order_health_fixture', '8.4' );
	$order->save();
	if ( method_exists( $order, 'set_date_modified' ) ) {
		$order->set_date_modified( $make_date( $seconds_ago ) );
		$order->save();
	}
	$fixture_ids[] = $order->get_id();
	return wc_get_order( $order->get_id() );
};

$delete_fixtures = function () use ( &$fixture_ids ) {
	foreach ( array_reverse( $fixture_ids ) as $fixture_id ) {
		$order = wc_get_order( $fixture_id );
		if ( $order ) {
			$order->delete( true );
		}
	}
};

try {
	$assert( class_exists( 'WCJ_Order_Health_Service' ), 'Order Health service was not loaded.' );
	$assert( class_exists( 'WCJ_Abilities' ), 'Booster Abilities class was not loaded.' );
	if ( ! class_exists( 'WCJ_Order_Health_Service' ) ) {
		throw new Exception( 'Cannot continue without WCJ_Order_Health_Service.' );
	}

	$service = new WCJ_Order_Health_Service();

	$pending           = $create_order( 'pending', 3 * HOUR_IN_SECONDS );
	$failed            = $create_order( 'failed', HOUR_IN_SECONDS );
	$processing        = $create_order( 'processing', 3 * DAY_IN_SECONDS, 100, true );
	$on_hold           = $create_order( 'on-hold', 2 * DAY_IN_SECONDS );
	$recent_processing = $create_order( 'processing', HOUR_IN_SECONDS, 100, true );
	$completed         = $create_order( 'completed', 10 * DAY_IN_SECONDS, 100, true );

	$pending_health    = $service->classify_order( $pending );
	$failed_health     = $service->classify_order( $failed );
	$processing_health = $service->classify_order( $processing );
	$hold_health       = $service->classify_order( $on_hold );
	$recent_health     = $service->classify_order( $recent_processing );
	$completed_health  = $service->classify_order( $completed );

	$assert( isset( $pending_health['reason_codes'] ) && in_array( 'payment_pending', $pending_health['reason_codes'], true ), 'Old pending order was not classified as payment_pending.' );
	$assert( isset( $failed_health['reason_codes'] ) && in_array( 'payment_failed', $failed_health['reason_codes'], true ), 'Failed order was not classified as payment_failed.' );
	$assert( isset( $processing_health['reason_codes'] ) && in_array( 'fulfillment_delayed', $processing_health['reason_codes'], true ), 'Old processing order was not classified as fulfillment_delayed.' );
	$assert( isset( $hold_health['reason_codes'] ) && in_array( 'workflow_incomplete', $hold_health['reason_codes'], true ), 'Old on-hold order was not classified as workflow_incomplete.' );
	$assert( empty( $recent_health ), 'Recent processing order should not be flagged.' );
	$assert( empty( $completed_health ), 'Completed order should not be flagged.' );

	$partial        = $create_order( 'processing', HOUR_IN_SECONDS, 100, true );
	$partial_refund = wc_create_refund(
		array(
			'order_id'     => $partial->get_id(),
			'amount'       => 20,
			'reason'       => 'Booster 8.4 partial-refund fixture',
			'restock_items' => false,
		)
	);
	if ( ! is_wp_error( $partial_refund ) ) {
		$fixture_ids[] = $partial_refund->get_id();
	}
	$partial        = wc_get_order( $partial->get_id() );
	$partial_health = $service->classify_order( $partial );
	$assert( isset( $partial_health['reason_codes'] ) && in_array( 'partial_refund_open', $partial_health['reason_codes'], true ), 'Open partial refund was not flagged.' );
	$assert( isset( $partial_health['refund_state'] ) && 'partial' === $partial_health['refund_state'], 'Partial refund state was not preserved.' );

	$full        = $create_order( 'processing', 4 * DAY_IN_SECONDS, 100, true );
	$full_refund = wc_create_refund(
		array(
			'order_id'     => $full->get_id(),
			'amount'       => 100,
			'reason'       => 'Booster 8.4 full-refund fixture',
			'restock_items' => false,
		)
	);
	if ( ! is_wp_error( $full_refund ) ) {
		$fixture_ids[] = $full_refund->get_id();
	}
	$full = wc_get_order( $full->get_id() );
	$assert( empty( $service->classify_order( $full ) ), 'Fully refunded order should not be flagged even if its stored status is active.' );

	$transition = $create_order( 'pending', 4 * HOUR_IN_SECONDS );
	$assert( ! empty( $service->classify_order( $transition ) ), 'Pre-transition pending order should be flagged.' );
	$transition->set_status( 'completed' );
	$transition->save();
	$transition = wc_get_order( $transition->get_id() );
	$assert( empty( $service->classify_order( $transition ) ), 'Current completed status was not honored after a status transition.' );

	register_post_status( 'wc-needs-config', array( 'label' => 'Needs config', 'public' => true ) );
	add_filter(
		'wc_order_statuses',
		function ( $statuses ) {
			$statuses['wc-needs-config'] = 'Needs config';
			return $statuses;
		}
	);
	$custom        = $create_order( 'needs-config', 4 * DAY_IN_SECONDS );
	$custom_health = $service->classify_order( $custom );
	$assert( isset( $custom_health['reason_codes'] ) && in_array( 'configuration_review', $custom_health['reason_codes'], true ), 'Stale custom status was not classified as configuration_review.' );

	$data = $service->get_dashboard_data();
	$assert( WCJ_Order_Health_Service::MAX_QUERY_ORDERS === $data['query']['query_limit'], 'Dashboard did not report its fixed query ceiling.' );
	$assert( $data['query']['candidate_count'] <= WCJ_Order_Health_Service::MAX_QUERY_ORDERS, 'Candidate query exceeded its fixed ceiling.' );
	$assert( $data['summary']['reason_counts']['payment_pending'] >= 1, 'Dashboard summary omitted payment_pending.' );
	$assert( $data['summary']['reason_counts']['partial_refund_open'] >= 1, 'Dashboard summary omitted partial_refund_open.' );
	$assert( $data['summary']['reason_counts']['configuration_review'] >= 1, 'Dashboard bounded query omitted the registered custom status.' );

	$filtered = $service->get_dashboard_data( array( 'cause' => 'payment', 'age' => 'under_1_day' ) );
	foreach ( $filtered['orders'] as $row ) {
		$assert( 'payment' === $row['cause'] && 'under_1_day' === $row['age_bucket'], 'Dashboard filters returned a non-matching row.' );
	}

	$summary = $service->get_order_health_summary();
	$encoded = wp_json_encode( $summary );
	$assert( 'aggregate-only' === $summary['morning_briefing']['privacy'], 'Morning briefing privacy contract is missing.' );
	$assert( false === strpos( $encoded, 'order_id' ) && false === strpos( $encoded, 'order_number' ), 'Ability summary exposed order identifiers.' );
	$assert( false === strpos( $encoded, 'billing_' ) && false === strpos( $encoded, 'shipping_' ) && false === strpos( $encoded, 'customer_' ), 'Ability summary exposed customer field names.' );

	$abilities = new WCJ_Abilities();
	wp_set_current_user( 0 );
	$assert( is_wp_error( $abilities->execute_order_health_summary() ), 'Guest execution was not denied.' );
	$administrators = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
	if ( ! empty( $administrators ) ) {
		wp_set_current_user( (int) $administrators[0] );
		$ability_result = $abilities->execute_order_health_summary();
		$assert( ! is_wp_error( $ability_result ) && isset( $ability_result['bounded_query'] ), 'Administrator could not execute Order Health summary.' );
	} else {
		$failures[] = 'No administrator was available for the authorized Ability check.';
	}

	if ( function_exists( 'wp_get_ability' ) ) {
		$assert( null !== wp_get_ability( 'booster/order-health-summary' ), 'booster/order-health-summary was not registered.' );
	}
} catch ( Throwable $error ) {
	$failures[] = 'Unexpected exception: ' . $error->getMessage();
}

$delete_fixtures();

$result = array(
	'passed'       => empty( $failures ),
	'storage_mode' => isset( $service ) ? $service->get_dashboard_data()['storage_mode'] : 'unknown',
	'failures'     => $failures,
);

if ( ! empty( $failures ) ) {
	if ( class_exists( 'WP_CLI' ) ) {
		WP_CLI::error( wp_json_encode( $result, JSON_PRETTY_PRINT ) );
	}
	echo wp_json_encode( $result, JSON_PRETTY_PRINT ) . PHP_EOL;
	exit( 1 );
}

if ( class_exists( 'WP_CLI' ) ) {
	WP_CLI::success( wp_json_encode( $result, JSON_PRETTY_PRINT ) );
} else {
	echo wp_json_encode( $result, JSON_PRETTY_PRINT ) . PHP_EOL;
}
