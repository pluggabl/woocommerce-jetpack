<?php
/** Creates or removes deterministic Order Health screenshot fixtures. */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

add_filter( 'pre_wp_mail', '__return_false' );

$mode = isset( $args[0] ) ? sanitize_key( $args[0] ) : 'create';
$ids  = array_map( 'absint', (array) get_option( 'wcj_order_health_screenshot_fixture_ids', array() ) );
foreach ( array_reverse( $ids ) as $id ) {
	$order = wc_get_order( $id );
	if ( $order ) {
		$order->delete( true );
	}
}
delete_option( 'wcj_order_health_screenshot_fixture_ids' );

if ( 'delete' === $mode ) {
	WP_CLI::success( 'Order Health screenshot fixtures removed.' );
	return;
}

$created   = array();
$make_date = function ( $seconds_ago ) {
	$date = new WC_DateTime( '@' . ( time() - $seconds_ago ) );
	$date->setTimezone( new DateTimeZone( 'UTC' ) );
	return $date;
};
$make_order = function ( $status, $seconds_ago, $paid = false ) use ( &$created, $make_date ) {
	$order = wc_create_order();
	$order->set_status( $status );
	$order->set_total( 100 );
	$order->set_date_created( $make_date( $seconds_ago ) );
	if ( $paid ) {
		$order->set_date_paid( $make_date( $seconds_ago ) );
	}
	$order->update_meta_data( '_wcj_order_health_screenshot_fixture', '8.4' );
	$order->save();
	$created[] = $order->get_id();
	return $order;
};

$make_order( 'pending', 5 * HOUR_IN_SECONDS );
$make_order( 'failed', 2 * HOUR_IN_SECONDS );
$make_order( 'on-hold', 2 * DAY_IN_SECONDS );
$make_order( 'processing', 4 * DAY_IN_SECONDS, true );
$partial = $make_order( 'processing', 6 * HOUR_IN_SECONDS, true );
$refund  = wc_create_refund(
	array(
		'order_id'      => $partial->get_id(),
		'amount'        => 25,
		'reason'        => 'Booster 8.4 screenshot fixture',
		'restock_items' => false,
	)
);
if ( ! is_wp_error( $refund ) ) {
	$created[] = $refund->get_id();
}

update_option( 'wcj_order_health_screenshot_fixture_ids', $created, false );
WP_CLI::success( sprintf( 'Created %d Order Health screenshot records.', count( $created ) ) );
