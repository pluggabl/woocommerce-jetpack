<?php
/** Focused early classifier unit test. Native HTTP is tested separately. */
define( 'ABSPATH', __DIR__ );
define( 'WCJ_AI_PREVIEW', false );
function add_action() {}
function add_filter() {}
function get_user_by() { return false; }
function is_email() { return false; }
function home_url() { return $GLOBALS['test_home']; }
function wp_parse_url( $url ) { return parse_url( $url ); }
function rest_get_url_prefix() { return 'wp-json'; }
function wp_get_environment_type() { return 'local'; }
function wp_die() { throw new RuntimeException( 'denied' ); }
require dirname( __DIR__, 2 ) . '/includes/class-wcj-ai-connection.php';
$cases = array();
foreach ( array( '/', '/shop/', '/shop%20one/' ) as $base ) {
	$GLOBALS['test_home'] = 'http://127.0.0.1:61646' . $base;
	foreach ( array(
		array( '?s=booster-ai', false ),
		array( 'product/booster-ai/', false ),
		array( '?name=booster-ai', false ),
		array( 'wp-json/unrelated/v1', false ),
		array( 'wp-json/booster-ai-lookalike/v1', false ),
		array( 'wp-json/booster-ai/v1/mcp', true ),
		array( '/wp-json/booster-ai/v1/mcp', true ),
		array( '//wp-json/booster-ai/v1/mcp', true ),
		array( 'index.php/wp-json/booster-ai/v1/mcp', true ),
		array( '%77p-json/booster-ai/v1/mcp', true ),
	) as $case ) {
		$_GET = $_POST = $_COOKIE = $_FILES = $_REQUEST = array();
		$_SERVER = array( 'REQUEST_URI' => $base . $case[0], 'REQUEST_METHOD' => 'GET', 'HTTP_HOST' => '127.0.0.1:61646' );
		$denied = false;
		try { WCJ_AI_Connection::early_guard(); } catch ( RuntimeException $e ) { $denied = true; }
		$ok = $case[1] === $denied;
		$cases[] = array( 'base' => $base, 'path' => $case[0], 'pass' => $ok );
		if ( ! $ok ) { echo json_encode( $cases ); exit( 1 ); }
	}
	foreach ( array( 'PATH_INFO', 'PHP_SELF' ) as $key ) {
		$_SERVER = array( 'REQUEST_URI' => $base . 'index.php', 'REQUEST_METHOD' => 'GET', 'HTTP_HOST' => '127.0.0.1:61646', $key => $base . '/wp-json/booster-ai/v1/mcp' );
		$denied = false;
		try { WCJ_AI_Connection::early_guard(); } catch ( RuntimeException $e ) { $denied = true; }
		$cases[] = array( 'base' => $base, 'server_key' => $key, 'pass' => $denied );
		if ( ! $denied ) { echo json_encode( $cases ); exit( 1 ); }
	}
}
echo json_encode( array( 'kind' => 'Stubbed classifier tests; not native routing proof', 'cases' => $cases ) );
