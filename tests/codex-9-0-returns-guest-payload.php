<?php
/**
 * Offline failing-first guest payload boundaries against actual Returns classes.
 *
 * No WordPress bootstrap, native database, transport, scheduler or HTTP.
 * Usage: php -n codex-guest-failing-first-payload-01.php ABSOLUTE_PLUGIN_FOLDER
 *
 * @package Booster_For_WooCommerce
 */

if ( 'cli' !== PHP_SAPI || defined( 'ABSPATH' ) || extension_loaded( 'mysqli' ) ) {
	exit( 2 );
}

$wcj_guest_root = $argv[1] ?? '';
if ( ! is_string( $wcj_guest_root ) || ! is_dir( $wcj_guest_root ) ) {
	fwrite( STDERR, "Supply the explicitly admitted isolated plugin folder.\n" );
	exit( 2 );
}

define( 'ABSPATH', __DIR__ );
require_once $wcj_guest_root . '/includes/returns/class-wcj-returns-facts.php';
require_once $wcj_guest_root . '/includes/returns/class-wcj-returns-store.php';

$wcj_guest_database         = new stdClass();
$wcj_guest_database->prefix = 'offline_guest_';
$wcj_guest_store            = new WCJ_Returns_Store( $wcj_guest_database );
$wcj_guest_owner            = str_repeat( 'a', 64 );
$wcj_guest_payload          = array(
	'items'   => array( 11 => 1000000 ),
	'reason'  => 'Synthetic guest reason',
	'policy'  => array(
		'days'    => 30,
		'notify'  => false,
		'version' => 1,
	),
	'history' => array(
		array(
			'status' => 'pending',
			'actor'  => 7,
			'time'   => 1791547200,
		),
	),
	'outbox'  => array(),
);
$wcj_guest_cases            = array();

$wcj_guest_cases[]            = array( 'account row remains valid', 7, $wcj_guest_payload, 'valid' );
$wcj_guest_data               = $wcj_guest_payload;
$wcj_guest_data['unexpected'] = true;
$wcj_guest_cases[]            = array( 'unknown account top-level key denied', 7, $wcj_guest_data, 'payload_invalid' );

$wcj_guest_data                                   = $wcj_guest_payload;
$wcj_guest_data['guest']                          = array(
	'v'     => 1,
	'owner' => $wcj_guest_owner,
);
$wcj_guest_data['history'][0]['actor']            = 0;
$wcj_guest_cases[]                                = array( 'guest row actor zero with strict owner valid', 0, $wcj_guest_data, 'valid' );
$wcj_guest_cases[]                                = array( 'guest payload forbidden on account row', 7, $wcj_guest_data, 'payload_invalid' );
$wcj_guest_account_binding                        = $wcj_guest_data;
$wcj_guest_account_binding['history'][0]['actor'] = 7;
$wcj_guest_cases[]                                = array( 'guest payload forbidden even with account history actor', 7, $wcj_guest_account_binding, 'payload_invalid' );
$wcj_guest_cases[]                                = array( 'guest actor without guest payload denied', 0, $wcj_guest_payload, 'payload_invalid' );
foreach ( array( null, false, -1, 7.0, 'missing' ) as $wcj_guest_position => $wcj_guest_bad_actor ) {
	$wcj_guest_cases[] = array( 'invalid or missing row actor context ' . $wcj_guest_position, $wcj_guest_bad_actor, $wcj_guest_payload, 'payload_invalid' );
}

foreach ( array( 'approved', 'declined', 'cancelled' ) as $wcj_guest_status ) {
	$wcj_guest_terminal                        = $wcj_guest_data;
	$wcj_guest_terminal['history'][]           = array(
		'status' => $wcj_guest_status,
		'actor'  => 0,
		'time'   => 1791547201,
	);
	$wcj_guest_cases[]                         = array( 'guest zero terminal actor ' . $wcj_guest_status, 0, $wcj_guest_terminal, 'cancelled' === $wcj_guest_status ? 'valid' : 'payload_invalid' );
	$wcj_guest_terminal['history'][1]['actor'] = 9;
	$wcj_guest_cases[]                         = array( 'staff terminal actor on guest ' . $wcj_guest_status, 0, $wcj_guest_terminal, 'valid' );
}

foreach ( array( '', str_repeat( 'a', 63 ), str_repeat( 'A', 64 ), str_repeat( 'g', 64 ), 0, null ) as $wcj_guest_position => $wcj_guest_bad_owner ) {
	$wcj_guest_bad                   = $wcj_guest_data;
	$wcj_guest_bad['guest']['owner'] = $wcj_guest_bad_owner;
	$wcj_guest_cases[]               = array( 'strict guest owner shape ' . $wcj_guest_position, 0, $wcj_guest_bad, 'payload_invalid' );
}

foreach ( array(
	array(
		'v'     => '1',
		'owner' => $wcj_guest_owner,
	),
	array(
		'v'     => 2,
		'owner' => $wcj_guest_owner,
	),
	array(
		'v'     => 1,
		'owner' => $wcj_guest_owner,
		'extra' => true,
	),
) as $wcj_guest_position => $wcj_guest_bad_binding ) {
	$wcj_guest_bad          = $wcj_guest_data;
	$wcj_guest_bad['guest'] = $wcj_guest_bad_binding;
	$wcj_guest_cases[]      = array( 'strict guest payload keys/version ' . $wcj_guest_position, 0, $wcj_guest_bad, 'payload_invalid' );
}

$wcj_guest_results  = array();
$wcj_guest_failures = 0;
foreach ( $wcj_guest_cases as $wcj_guest_case ) {
	try {
		$wcj_guest_row = array(
			'actor_id' => $wcj_guest_case[1],
			'payload'  => json_encode( $wcj_guest_case[2] ),
		);
		if ( 'missing' === $wcj_guest_case[1] ) {
			unset( $wcj_guest_row['actor_id'] );
		}
		$wcj_guest_store->payload( $wcj_guest_row );
		$wcj_guest_actual = 'valid';
	} catch ( RuntimeException $wcj_guest_error ) {
		$wcj_guest_actual = $wcj_guest_error->getMessage();
	}
	$wcj_guest_pass      = $wcj_guest_actual === $wcj_guest_case[3];
	$wcj_guest_failures += $wcj_guest_pass ? 0 : 1;
	$wcj_guest_results[] = array(
		'name'     => $wcj_guest_case[0],
		'expected' => $wcj_guest_case[3],
		'actual'   => $wcj_guest_actual,
		'pass'     => $wcj_guest_pass,
	);
}

echo json_encode(
	array(
		'scope'      => 'actual-class offline payload only',
		'assertions' => count( $wcj_guest_results ),
		'failures'   => $wcj_guest_failures,
		'results'    => $wcj_guest_results,
	),
	JSON_PRETTY_PRINT
) . "\n";
exit( $wcj_guest_failures ? 1 : 0 );
