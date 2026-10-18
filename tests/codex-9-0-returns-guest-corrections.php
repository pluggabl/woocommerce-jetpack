<?php
/**
 * Finite F2/N1 regression on actual controller and private-store methods.
 * No native SQL, HTTP, scheduler or email execution.
 *
 * @package Booster_For_WooCommerce
 */

if ( 'cli' !== PHP_SAPI || defined( 'ABSPATH' ) || extension_loaded( 'mysqli' ) ) {
	fwrite( STDERR, "Offline CLI only, use php -n.\n" );
	exit( 2 );
}
define( 'WCJ_RETURNS_GUEST_LIBRARY_ONLY', true );
$wcj_correction_root = $argv[1] ?? '';
require $wcj_correction_root . '/tests/codex-9-0-returns-guest.php';
require $wcj_correction_root . '/tests/fixtures/codex-returns-guest-sql.php';
$GLOBALS['wcj_guest_count']  = 0;
$GLOBALS['wcj_guest_fail']   = 0;
$GLOBALS['wcj_guest_output'] = array();
foreach ( array( '192.0.2.1', '198.51.100.9', '0.0.0.0', '255.255.255.255' ) as $wcj_correction_address ) {
	wcj_guest_check( 'mapped IPv4 equals whole native address ' . $wcj_correction_address, WCJ_Returns_Guest::peer( '::ffff:' . $wcj_correction_address ), WCJ_Returns_Guest::peer( $wcj_correction_address ) );
}
wcj_guest_check( 'mapped distinct IPv4 clients have distinct buckets', WCJ_Returns_Guest::peer( '::ffff:192.0.2.1' ) !== WCJ_Returns_Guest::peer( '::ffff:192.0.2.2' ), true );
wcj_guest_check( 'hex spelling of mapped IPv4 normalized exactly', WCJ_Returns_Guest::peer( '::ffff:c000:201' ), 'v4|c0000201' );
wcj_guest_check( 'ordinary IPv6 same /64 unchanged', WCJ_Returns_Guest::peer( '2001:db8:abcd:1234::1' ), 'v6|20010db8abcd1234' );
wcj_guest_check( 'IPv4-compatible nonmapped IPv6 remains IPv6', WCJ_Returns_Guest::peer( '::192.0.2.1' ), 'v6|0000000000000000' );
wcj_guest_check(
	'invalid peer still denied',
	wcj_returns_test_thrown(
		function () {
			WCJ_Returns_Guest::peer( '::ffff:999.0.0.1' );
		}
	),
	'auth_unavailable'
);
$wcj_correction_deadline = new ReflectionMethod( WCJ_Returns_Guest::class, 'deadline' );
$wcj_correction_phase    = array(
	'now'   => 1700000000,
	'start' => 2000000000,
);
foreach ( array( 2, 600, 900 ) as $wcj_correction_remaining ) {
	$wcj_correction_row = array( 'expires_gmt' => gmdate( 'Y-m-d H:i:s', 1700000000 + $wcj_correction_remaining ) );
	wcj_guest_check( 'deadline subtracts full uncertainty second ' . $wcj_correction_remaining, $wcj_correction_deadline->invoke( null, $wcj_correction_row, $wcj_correction_phase ), 2000000000 + ( $wcj_correction_remaining - 1 ) * 1000000000 );
}
foreach ( array( -1, 0, 1, 901 ) as $wcj_correction_remaining ) {
	$wcj_correction_row = array( 'expires_gmt' => gmdate( 'Y-m-d H:i:s', 1700000000 + $wcj_correction_remaining ) );
	wcj_guest_check(
		'no positive safe interval or excess TTL denied ' . $wcj_correction_remaining,
		wcj_returns_test_thrown(
			function () use ( $wcj_correction_deadline, $wcj_correction_row, $wcj_correction_phase ) {
				$wcj_correction_deadline->invoke( null, $wcj_correction_row, $wcj_correction_phase );
			}
		),
		'auth_unavailable'
	);
}
foreach ( array( 0, 1, 500000000, 999999999 ) as $wcj_correction_fraction ) {
	$wcj_correction_row   = array( 'expires_gmt' => gmdate( 'Y-m-d H:i:s', 1700000002 ) );
	$wcj_correction_bound = $wcj_correction_deadline->invoke( null, $wcj_correction_row, $wcj_correction_phase );
	// Actual wall epoch is captured floor + fraction. The bound must not be later.
	$wcj_correction_true = 2000000000 + 2000000000 - $wcj_correction_fraction;
	wcj_guest_check( 'deterministic subsecond model never adds expiry grace ' . $wcj_correction_fraction, $wcj_correction_bound <= $wcj_correction_true, true );
}
$wcj_correction_row   = array( 'expires_gmt' => gmdate( 'Y-m-d H:i:s', 1700000002 ) );
$wcj_correction_phase = array(
	'now'   => 1700000000,
	'start' => PHP_INT_MAX - 1000000000 + 1,
);
wcj_guest_check(
	'monotonic deadline addition overflow denied',
	wcj_returns_test_thrown(
		function () use ( $wcj_correction_deadline, $wcj_correction_row, $wcj_correction_phase ) {
			$wcj_correction_deadline->invoke( null, $wcj_correction_row, $wcj_correction_phase );
		}
	),
	'auth_unavailable'
);
// Actual store checks the corrected bound through a real monotonic wait.
foreach ( array( 'read', 'commit' ) as $wcj_correction_wait ) {
	list( $wcj_correction_memory )    = wcj_guest_scenario();
	$wcj_correction_db                = new WCJ_Returns_Test_DB();
	$wcj_correction_sql               = new WCJ_Guest_SQL_Connection( $wcj_correction_db );
	$wcj_correction_sql->auth         = $wcj_correction_memory->auth;
	$wcj_correction_store             = new WCJ_Returns_Store( $wcj_correction_db, $wcj_correction_sql );
	$wcj_correction_phase             = array(
		'now'   => 1700000000,
		'start' => hrtime( true ) - 990000000,
	);
	$wcj_correction_bound             = $wcj_correction_deadline->invoke( null, $wcj_correction_row, $wcj_correction_phase );
	$wcj_correction_sql->delay_read   = 'read' === $wcj_correction_wait;
	$wcj_correction_sql->delay_commit = 'commit' === $wcj_correction_wait;
	wcj_guest_check(
		'actual store tightened expiry through ' . $wcj_correction_wait,
		wcj_returns_test_thrown(
			function () use ( $wcj_correction_store, $wcj_correction_phase, $wcj_correction_bound, $wcj_correction_wait ) {
				$wcj_correction_store->transaction(
					function () use ( $wcj_correction_store, $wcj_correction_wait ) {
						if ( 'read' === $wcj_correction_wait ) {
							$wcj_correction_store->auth_get( WCJ_Returns_Store::auth_anchor_key(), true );
						}
					},
					$wcj_correction_phase['start'],
					$wcj_correction_bound
				);
			}
		),
		'read' === $wcj_correction_wait ? 'auth_expired' : 'commit_unknown'
	);
	wcj_guest_check( 'tightened ' . $wcj_correction_wait . ' wait ends with cleanup', end( $wcj_correction_sql->trace ), 'ROLLBACK' );
	wcj_guest_check(
		'tightened ' . $wcj_correction_wait . ' expiry clears for ordinary account',
		$wcj_correction_store->transaction(
			function () {
				return 'ordinary';
			}
		),
		'ordinary'
	);
}
/** Offline-only gap after a known sending commit, before the mail call. */
class WCJ_Guest_Correction_Gap_Store extends WCJ_Guest_Test_Store {
	/**
	 * Inject only after the synthetic sending transaction.
	 *
	 * @var bool Synthetic scheduling delay enabled.
	 */
	public $gap = false;
	/**
	 * Preserve the real shared engine transaction and model caller scheduling delay.
	 *
	 * @param callable $operation Private operation.
	 * @param int|null $started Captured monotonic start.
	 * @param int|null $expiry Captured auth deadline.
	 * @return mixed Original operation result.
	 */
	public function transaction( $operation, $started = null, $expiry = null ) {
		$result = parent::transaction( $operation, $started, $expiry );
		if ( $this->gap ) {
			$this->gap = false;
			usleep( 1100000 );
		}
		return $result;
	}
}
list( $wcj_correction_memory, $wcj_correction_facts ) = wcj_guest_scenario();
$wcj_correction_store                                 = new WCJ_Guest_Correction_Gap_Store();
$wcj_correction_store->auth                           = $wcj_correction_memory->auth;
$wcj_correction_guest                                 = new WCJ_Returns_Guest(
	$wcj_correction_store,
	$wcj_correction_facts,
	function () {
		return $GLOBALS['wcj_returns_test_now'];
	}
);
$wcj_correction_result                                = $wcj_correction_guest->challenge( '10', 'guest@example.invalid', wcj_returns_test_nonce( 'wcj_returns_guest_challenge' ) );
$GLOBALS['wcj_returns_test_now']                     += 598;
$wcj_correction_store->gap                            = true;
$wcj_correction_guest->send_challenge( hash( 'sha256', $wcj_correction_result['cookie'] ) );
wcj_guest_check( 'proof mail never starts after captured deadline outside TX', count( $GLOBALS['wcj_returns_test_messages'] ), 0 );
wcj_guest_check( 'expired after sending commit remains nonresendable', $wcj_correction_store->auth_get( hash( 'sha256', $wcj_correction_result['cookie'] ) )['data']['delivery'], 'sending' );
$wcj_correction_guest->send_challenge( hash( 'sha256', $wcj_correction_result['cookie'] ) );
wcj_guest_check( 'late proof mail has no automatic replay', count( $GLOBALS['wcj_returns_test_messages'] ), 0 );
echo json_encode(
	array(
		'assertions' => $GLOBALS['wcj_guest_count'],
		'failures'   => $GLOBALS['wcj_guest_fail'],
		'cases'      => $GLOBALS['wcj_guest_output'],
	),
	JSON_PRETTY_PRINT
) . PHP_EOL;
exit( $GLOBALS['wcj_guest_fail'] ? 1 : 0 );
