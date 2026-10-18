<?php
/**
 * Finite actual-controller PHP header lifecycle regression, not native HTTP QA.
 * Invoke separately for each case with php -n -d output_buffering=0.
 * WordPress, order facts and private storage remain the existing offline doubles.
 *
 * @package Booster_For_WooCommerce
 */

if ( 'cli' !== PHP_SAPI || defined( 'ABSPATH' ) || extension_loaded( 'mysqli' ) ) {
	fwrite( STDERR, "Offline CLI only, use php -n.\n" );
	exit( 2 );
}
define( 'WCJ_RETURNS_GUEST_LIBRARY_ONLY', true );
$wcj_headers_root = $argv[1] ?? '';
$wcj_headers_case = $argv[2] ?? '';
if ( ! in_array( $wcj_headers_case, array( 'early-view', 'public', 'private', 'no-receipt', 'wrong-page', 'wrong-blog', 'http', 'late-uncache', 'disabled', 'revoked-consent', 'preview', 'clear-stale', 'cache-disabled', 'writes' ), true ) ) {
	exit( 2 );
}
require $wcj_headers_root . '/tests/codex-9-0-returns-guest.php';
$GLOBALS['wcj_guest_count']  = 0;
$GLOBALS['wcj_guest_fail']   = 0;
$GLOBALS['wcj_guest_output'] = array();
list( $wcj_headers_store, $wcj_headers_facts, $wcj_headers_guest, $wcj_headers_service ) = wcj_guest_scenario();
$wcj_headers_cookie = wcj_guest_grant( $wcj_headers_store );
$wcj_headers_row    = $wcj_headers_service->guest_submit( $wcj_headers_cookie, array( 11 => '1' ), 'Private header history', wcj_returns_test_token( 1 ), wcj_guest_nonce( 'submit', $wcj_headers_cookie, 10 ) );
wcj_guest_check( 'header fixture seeded exact pending request before output', $wcj_headers_row['status'] ?? null, 'pending' );
wcj_guest_check( 'PHP output buffering actually disabled', ob_get_level(), 0 );
wcj_guest_check( 'before theme output headers are actually unsent', headers_sent(), false );
$_COOKIE = array( '__Host-wcj_return_gg_1' => $wcj_headers_cookie );
if ( 'public' === $wcj_headers_case ) {
	$_COOKIE = array();
}
if ( 'cache-disabled' === $wcj_headers_case ) {
	define( 'DONOTCACHEPAGE', false );
}
if ( ! in_array( $wcj_headers_case, array( 'no-receipt', 'late-uncache', 'early-view' ), true ) ) {
	$wcj_headers_guest->uncache();
}
if ( 'no-receipt' === $wcj_headers_case ) {
	define( 'DONOTCACHEPAGE', true );
}
if ( 'clear-stale' === $wcj_headers_case ) {
	$GLOBALS['wcj_guest_page_context'] = false;
	$wcj_headers_guest->uncache();
	$GLOBALS['wcj_guest_page_context'] = true;
}
if ( 'wrong-page' === $wcj_headers_case ) {
	$GLOBALS['wcj_guest_page_context'] = false;
}
if ( 'wrong-blog' === $wcj_headers_case ) {
	$GLOBALS['wcj_guest_blog'] = 2;
}
if ( 'http' === $wcj_headers_case ) {
	$GLOBALS['wcj_guest_ssl'] = false;
}
if ( 'disabled' === $wcj_headers_case ) {
	$GLOBALS['wcj_returns_test_options']['wcj_returns_enabled'] = 'no';
}
if ( 'revoked-consent' === $wcj_headers_case ) {
	$GLOBALS['wcj_returns_test_options']['wcj_returns_guest'] = 'no';
}
if ( 'preview' === $wcj_headers_case ) {
	$GLOBALS['wcj_guest_preview'] = true;
}
if ( 'early-view' !== $wcj_headers_case ) {
	// Real unbuffered output, never a headers_sent stub or output buffer.
	echo "Synthetic theme head already emitted.\n";
	flush();
	wcj_guest_check( 'after theme output PHP headers are actually sent', headers_sent(), true );
}
$wcj_headers_warnings = array();
set_error_handler(
	function ( $number, $message ) use ( &$wcj_headers_warnings ) {
		$wcj_headers_warnings[] = array( $number, $message );
		return true;
	}
);
if ( 'late-uncache' === $wcj_headers_case ) {
	$wcj_headers_guest->uncache();
}
$wcj_headers_allowed = in_array( $wcj_headers_case, array( 'early-view', 'public', 'private', 'writes' ), true );
$wcj_headers_html    = $wcj_headers_guest->render();
wcj_guest_check( 'public challenge form matches exact page receipt policy', false !== strpos( $wcj_headers_html, 'value="wcj_returns_guest_challenge"' ), $wcj_headers_allowed );
wcj_guest_check( 'private history requires both receipt and proven grant', false !== strpos( $wcj_headers_html, 'Private header history' ), $wcj_headers_allowed && 'public' !== $wcj_headers_case );
wcj_guest_check( 'render never exposes cookie or stored email', false === strpos( $wcj_headers_html, $wcj_headers_cookie ) && false === strpos( $wcj_headers_html, 'guest@example.invalid' ), true );
$wcj_headers_fresh   = new WCJ_Returns_Guest(
	$wcj_headers_store,
	$wcj_headers_facts,
	function () {
		return $GLOBALS['wcj_returns_test_now'];
	}
);
$wcj_headers_context = wcj_returns_test_thrown(
	function () use ( $wcj_headers_fresh, $wcj_headers_cookie ) {
		$wcj_headers_fresh->context( $wcj_headers_cookie );
	}
);
wcj_guest_check( 'new controller view shares receipt only on current allowed page', $wcj_headers_context, $wcj_headers_allowed ? 'none' : 'auth_unavailable' );
if ( 'writes' === $wcj_headers_case ) {
	$wcj_headers_before_auth = $wcj_headers_store->auth;
	$wcj_headers_before_rows = $wcj_headers_store->rows;
	wcj_guest_check( 'receipt never admits challenge after output', $wcj_headers_guest->challenge( '10', 'guest@example.invalid', wcj_returns_test_nonce( 'wcj_returns_guest_challenge' ) ), array() );
	wcj_guest_check( 'receipt never admits verification after output', $wcj_headers_guest->verify( $wcj_headers_cookie, 'wrong', wcj_guest_nonce( 'verify', $wcj_headers_cookie ) ), array() );
	foreach ( array( 'submit', 'cancel' ) as $wcj_headers_operation ) {
		wcj_guest_check(
			'receipt never admits write context ' . $wcj_headers_operation,
			wcj_returns_test_thrown(
				function () use ( $wcj_headers_fresh, $wcj_headers_cookie, $wcj_headers_operation ) {
					$wcj_headers_fresh->context( $wcj_headers_cookie, $wcj_headers_operation, wcj_guest_nonce( $wcj_headers_operation, $wcj_headers_cookie, 10 ) );
				}
			),
			'auth_unavailable'
		);
	}
	wcj_guest_check( 'service submit stays denied after theme output', is_wp_error( $wcj_headers_service->guest_submit( $wcj_headers_cookie, array( 12 => '1' ), '', wcj_returns_test_token( 2 ), wcj_guest_nonce( 'submit', $wcj_headers_cookie, 10 ) ) ), true );
	wcj_guest_check( 'service cancel stays denied after theme output', is_wp_error( $wcj_headers_service->guest_cancel( $wcj_headers_cookie, 1, 1, wcj_guest_nonce( 'cancel', $wcj_headers_cookie, 10 ) ) ), true );
	wcj_guest_check( 'denied writes make no auth mutation', $wcj_headers_store->auth, $wcj_headers_before_auth );
	wcj_guest_check( 'denied writes make no business-row mutation', $wcj_headers_store->rows, $wcj_headers_before_rows );
	$wcj_headers_finish = new ReflectionMethod( WCJ_Returns_Guest::class, 'finish' );
	wcj_guest_check(
		'cookie finish retains fixed redirect without late cookie header',
		wcj_returns_test_thrown(
			function () use ( $wcj_headers_finish, $wcj_headers_guest, $wcj_headers_cookie ) {
				$wcj_headers_finish->invoke(
					$wcj_headers_guest,
					array(
						'kind'    => 'gg',
						'blog'    => 1,
						'seconds' => 900,
						'cookie'  => $wcj_headers_cookie,
					)
				);
			}
		),
		'wcj_returns_test_redirect_stop'
	);
	wcj_guest_check( 'explicit storage handler remains denied after output', wcj_returns_test_redirect_call( array( $wcj_headers_guest, 'storage_handler' ) ), 'https://store.invalid/wp-admin/admin.php?page=wcj-returns&wcj_returns_result=unavailable' );
}
restore_error_handler();
wcj_guest_check( 'no late PHP header or cookie warnings', $wcj_headers_warnings, array() );
fwrite(
	STDERR,
	json_encode(
		array(
			'case'       => $wcj_headers_case,
			'assertions' => $GLOBALS['wcj_guest_count'],
			'failures'   => $GLOBALS['wcj_guest_fail'],
			'cases'      => $GLOBALS['wcj_guest_output'],
		),
		JSON_PRETTY_PRINT
	) . PHP_EOL
);
exit( $GLOBALS['wcj_guest_fail'] ? 1 : 0 );
