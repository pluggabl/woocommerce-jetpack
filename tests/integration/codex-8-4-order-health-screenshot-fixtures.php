<?php
/**
 * Explicit isolated WP-CLI Order Health screenshot fixtures.
 *
 * Requires CODEX_QA_ISOLATED=1, WCJ_ORDER_HEALTH_SCREENSHOT_FIXTURES=1,
 * a local/development WordPress environment, and one explicit create/delete arg.
 * Never run on a merchant database.
 */

if (
	'cli' !== PHP_SAPI ||
	! defined( 'ABSPATH' ) ||
	! defined( 'WP_CLI' ) || true !== WP_CLI ||
	'1' !== getenv( 'CODEX_QA_ISOLATED' ) ||
	'1' !== getenv( 'WCJ_ORDER_HEALTH_SCREENSHOT_FIXTURES' ) ||
	! function_exists( 'wp_get_environment_type' ) ||
	! in_array( wp_get_environment_type(), array( 'local', 'development' ), true )
) {
	throw new RuntimeException( 'SCREENSHOT_ISOLATED_CLI_OPT_IN_REQUIRED' );
}

if ( ! isset( $args ) || ! is_array( $args ) || 1 !== count( $args ) || ! in_array( $args[0], array( 'create', 'delete' ), true ) ) {
	throw new RuntimeException( 'SCREENSHOT_EXPLICIT_ACTION_REQUIRED' );
}

$mode = $args[0];
$ids  = get_option( 'wcj_order_health_screenshot_fixture_ids', array() );
if ( ! is_array( $ids ) || array_values( $ids ) !== $ids || count( array_unique( $ids, SORT_REGULAR ) ) !== count( $ids ) ) {
	throw new RuntimeException( 'SCREENSHOT_INVALID_OWNED_IDS' );
}

// Read and validate the entire closure before any hook or mutation.
$owned = array();
foreach ( $ids as $id ) {
	if ( ! is_int( $id ) || $id <= 0 ) {
		throw new RuntimeException( 'SCREENSHOT_INVALID_OWNED_ID' );
	}
	$order = wc_get_order( $id );
	if ( ! $order || $order->get_id() !== $id || ! in_array( $order->get_type(), array( 'shop_order', 'shop_order_refund' ), true ) ) {
		throw new RuntimeException( 'SCREENSHOT_MISSING_OWNED_RECORD' );
	}
	$owned[ $id ] = $order;
}
foreach ( $owned as $order ) {
	if ( 'shop_order_refund' === $order->get_type() ) {
		$parent_id = $order->get_parent_id();
		// Supports existing legacy refunds, which were not directly marked.
		if ( ! isset( $owned[ $parent_id ] ) || 'shop_order' !== $owned[ $parent_id ]->get_type() || '8.4' !== $owned[ $parent_id ]->get_meta( '_wcj_order_health_screenshot_fixture', true, 'edit' ) ) {
			throw new RuntimeException( 'SCREENSHOT_REFUND_PARENT_NOT_OWNED' );
		}
		$marker = $order->get_meta( '_wcj_order_health_screenshot_fixture', true, 'edit' );
		if ( '' !== $marker && '8.4' !== $marker ) {
			throw new RuntimeException( 'SCREENSHOT_REFUND_MARKER_MISMATCH' );
		}
	} elseif ( '8.4' !== $order->get_meta( '_wcj_order_health_screenshot_fixture', true, 'edit' ) ) {
		throw new RuntimeException( 'SCREENSHOT_ORDER_NOT_OWNED' );
	}
	// Woo may cascade parent deletion to refunds. No unselected child is waived.
	if ( 'shop_order' === $order->get_type() ) {
		foreach ( $order->get_refunds() as $refund ) {
			$refund_id = $refund->get_id();
			if ( ! isset( $owned[ $refund_id ] ) || 'shop_order_refund' !== $owned[ $refund_id ]->get_type() || $owned[ $refund_id ]->get_parent_id() !== $order->get_id() ) {
				throw new RuntimeException( 'SCREENSHOT_UNSELECTED_REFUND' );
			}
		}
	}
}

add_filter( 'pre_wp_mail', '__return_false' );
// Delete positively joined children first, independently of option-list order.
foreach ( array( 'shop_order_refund', 'shop_order' ) as $type ) {
	foreach ( array_reverse( $owned, true ) as $order ) {
		if ( $type === $order->get_type() ) {
			$order->delete( true );
		}
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
	$refund->update_meta_data( '_wcj_order_health_screenshot_fixture', '8.4' );
	$refund->save();
	$created[] = $refund->get_id();
}
update_option( 'wcj_order_health_screenshot_fixture_ids', $created, false );
WP_CLI::success( sprintf( 'Created %d Order Health screenshot records.', count( $created ) ) );
