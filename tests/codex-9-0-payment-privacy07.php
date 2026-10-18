<?php
/**
 * Affected privacy07 production guard fixture. CLI is NOT native HTTP evidence.
 * No WordPress bootstrap, private actor/settings reads or original fixture replay.
 * @package Booster_For_WooCommerce/tests
 */
error_reporting( E_ALL );
set_error_handler( function ( $level, $message, $file, $line ) { throw new ErrorException( $message, 0, $level, $file, $line ); } );
$cases = array( 'noop', 'late_final', 'filter_noop', 'recognize_observe_null', 'recognize_gateway_array', 'recognize_nonce_bad', 'recognized_removed', 'filter_recognized', 'repeat_recognized', 'bootstrap_cachefalse', 'bootstrap_output', 'admin_get', 'admin_post', 'admin_ajax', 'rest', 'login', 'xmlrpc', 'cron', 'themes_false', 'template_missing_observer', 'template_head', 'template_duplicate', 'template_cachefalse', 'template_headers_sent', 'arm_observer_mismatch', 'template_cli_headers', 'arm_cli_headers' );
function privacy_require( $condition, $code ) { if ( ! $condition ) { throw new RuntimeException( $code ); } }
if ( isset( $argv[1] ) && '--case' === $argv[1] ) {
	privacy_require( isset( $argv[2] ) && in_array( $argv[2], $cases, true ), 'unknown_case' );
	$case = $argv[2];
	privacy_require( 'cli' === PHP_SAPI, 'CLI_only_no_native_claim' );
	define( 'ABSPATH', __DIR__ . '/' );
	$GLOBALS['privacy_hooks'] = array();
	$GLOBALS['privacy_reads'] = 0;
	function add_action( $tag, $callback, $priority = 10, $args = 1 ) { $GLOBALS['privacy_hooks'][] = array( 'action', $tag, $callback, $priority, $args ); }
	function add_filter( $tag, $callback, $priority = 10, $args = 1 ) { $GLOBALS['privacy_hooks'][] = array( 'filter', $tag, $callback, $priority, $args ); }
	function privacy_forbidden_read() { ++$GLOBALS['privacy_reads']; throw new RuntimeException( 'unexpected_actor_context_private_read' ); }
	function current_user_can( $cap ) { return privacy_forbidden_read(); }
	function is_user_logged_in() { return privacy_forbidden_read(); }
	function get_current_user_id() { return privacy_forbidden_read(); }
	function get_current_blog_id() { return privacy_forbidden_read(); }
	function is_admin() { return privacy_forbidden_read(); }
	function wp_doing_ajax() { return privacy_forbidden_read(); }
	function is_checkout() { return privacy_forbidden_read(); }
	function is_wc_endpoint_url( $name ) { return privacy_forbidden_read(); }
	function wp_verify_nonce( $nonce, $action ) { return privacy_forbidden_read(); }
	function wcj_is_module_enabled( $name ) { return privacy_forbidden_read(); }
	function wcj_get_option( $name, $default = null ) { return privacy_forbidden_read(); }
	$_SERVER['REQUEST_METHOD'] = 'template_head' === $case ? 'HEAD' : 'GET';
	$_GET = in_array( $case, array( 'noop', 'late_final', 'filter_noop' ), true ) ? array() : array( 'wcj_payment_nonce' => 'FICTITIOUS_REJECTED_INPUT' );
	if ( 'recognize_observe_null' === $case ) { $_GET = array( 'wcj_payment_observe' => null ); }
	if ( 'recognize_gateway_array' === $case ) { $_GET = array( 'wcj_payment_gateway' => array( 'FICTITIOUS_REJECTED_INPUT' ) ); }
	$non_template = in_array( $case, array( 'admin_get', 'admin_post', 'admin_ajax', 'rest', 'login', 'xmlrpc', 'cron', 'themes_false' ), true );
	if ( in_array( $case, array( 'bootstrap_cachefalse', 'template_cachefalse' ), true ) || $non_template ) { define( 'DONOTCACHEPAGE', false ); }
	if ( in_array( $case, array( 'admin_get', 'admin_post' ), true ) ) { define( 'WP_ADMIN', true ); }
	if ( 'admin_post' === $case ) { $_SERVER['REQUEST_METHOD'] = 'POST'; }
	if ( 'admin_ajax' === $case ) { define( 'DOING_AJAX', true ); }
	if ( 'rest' === $case ) { define( 'REST_REQUEST', true ); }
	if ( 'xmlrpc' === $case ) { define( 'XMLRPC_REQUEST', true ); }
	if ( 'cron' === $case ) { define( 'DOING_CRON', true ); }
	if ( 'themes_false' === $case ) { define( 'WP_USE_THEMES', false ); }
	if ( in_array( $case, array( 'bootstrap_output', 'template_headers_sent' ), true ) ) { echo "Prior test output.\n"; }
	$root = dirname( __DIR__ );
	require $root . '/includes/class-wcj-rule-explanations.php';
	$rc = new ReflectionClass( 'WCJ_Rule_Explanations' );
	privacy_require( $rc->getFileName() === realpath( $root . '/includes/class-wcj-rule-explanations.php' ), 'actual_service_source' );
	$transport = $rc->getProperty( 'payment_transport' );
	$window = $rc->getProperty( 'payment_window' );
	$observer = $rc->getProperty( 'payment_observer' );
	$state = $transport->getValue();
	privacy_require( true === $state['initialized'] && 0 === $GLOBALS['privacy_reads'], 'bootstrap_zero_reads' );
	$input = array( 'rEfErReR-pOlIcY' => 'public-old', 'Referrer-Policy' => 'public-other', 'cache-control' => 'public', 'CACHE-CONTROL' => 'old', 'Expires' => 'old', 'eTAG' => 'old', 'LAST-MODIFIED' => 'old', 'X-Public' => 'preserved', 'X-Array' => array( 1, 2 ) );
	if ( in_array( $case, array( 'noop', 'late_final', 'filter_noop' ), true ) ) {
		privacy_require( false === $state['recognized'] && false === $state['failed'] && ! defined( 'DONOTCACHEPAGE' ) && 1 === count( $GLOBALS['privacy_hooks'] ), 'noattempt_no_sideeffects' );
		privacy_require( WCJ_Rule_Explanations::payment_transport_headers( $input, new stdClass() ) === $input, 'noattempt_header_identity' );
		if ( 'late_final' === $case ) { $_GET = array( 'wcj_payment_nonce' => 'FICTITIOUS_REJECTED_INPUT' ); WCJ_Rule_Explanations::initialize_payment_transport(); privacy_require( $transport->getValue() === $state, 'first_false_final' ); }
		unset( $GLOBALS['_GET'] );
		WCJ_Rule_Explanations::initialize_payment_transport();
		$service = new WCJ_Rule_Explanations();
		$service->arm_payment_observer();
		WCJ_Rule_Explanations::check_payment_transport();
		$service->begin_payment_window(); $service->end_payment_window(); $service->render_payment_observer();
		privacy_require( null === WCJ_Rule_Explanations::payment_start( new stdClass(), array() ), 'unarmed_ordinary_start' );
		WCJ_Rule_Explanations::payment_finish( null, null, null, false );
		privacy_require( $state === $transport->getValue() && array( 'state' => 'unarmed' ) === $window->getValue() && 0 === $GLOBALS['privacy_reads'] && 1 === count( $GLOBALS['privacy_hooks'] ), 'noattempt_first_arm_before_mismatch_and_GET' );
	} else {
		privacy_require( true === $state['recognized'] && 3 === count( $GLOBALS['privacy_hooks'] ), 'recognized_hooks_only' );
		$filter = $GLOBALS['privacy_hooks'][0]; $precheck = $GLOBALS['privacy_hooks'][1];
		privacy_require( array( 'filter', 'wp_headers', array( 'WCJ_Rule_Explanations', 'payment_transport_headers' ), PHP_INT_MAX, 2 ) === $filter && array( 'action', 'template_redirect', array( 'WCJ_Rule_Explanations', 'check_payment_transport' ), -1, 0 ) === $precheck, 'exact_hook_order_priority_args' );
		if ( 'filter_recognized' === $case ) {
			$expected = array( 'X-Public' => 'preserved', 'X-Array' => array( 1, 2 ), 'Referrer-Policy' => 'no-referrer', 'Cache-Control' => 'private, no-store, no-cache, must-revalidate', 'Expires' => 'Wed, 11 Jan 1984 05:00:00 GMT', 'Last-Modified' => false );
			privacy_require( $expected === WCJ_Rule_Explanations::payment_transport_headers( $input, new stdClass() ), 'mixedcase_exact_header_array' );
		}
		if ( 'repeat_recognized' === $case || 'template_duplicate' === $case ) { unset( $GLOBALS['_GET'] ); WCJ_Rule_Explanations::initialize_payment_transport(); privacy_require( true === $transport->getValue()['failed'] && 3 === count( $GLOBALS['privacy_hooks'] ), 'duplicate_failed_no_reread_no_rehook' ); }
		if ( 'recognized_removed' === $case ) { unset( $GLOBALS['_GET'] ); privacy_require( true === $transport->getValue()['recognized'], 'removed_keys_recognition_stays' ); }
		if ( in_array( $case, array( 'bootstrap_cachefalse', 'template_cachefalse' ), true ) || $non_template ) { privacy_require( false === DONOTCACHEPAGE && true === $transport->getValue()['failed'], 'cachefalse_preserved_failure_continue' ); }
		if ( in_array( $case, array( 'bootstrap_output', 'template_headers_sent' ), true ) ) { privacy_require( true === $transport->getValue()['failed'], 'prior_output_flag_continue' ); }
		if ( 'template_missing_observer' === $case || 'template_head' === $case ) { privacy_require( null === $observer->getValue(), 'missing_observer_actual' ); WCJ_Rule_Explanations::check_payment_transport(); throw new RuntimeException( 'production_denial_returned' ); }
		if ( 0 === strpos( $case, 'template_' ) || 'arm_cli_headers' === $case ) {
			WCJ_Rule_Explanations::bind_payment_observer();
			privacy_require( $observer->getValue() instanceof WCJ_Rule_Explanations && array( 'state' => 'unarmed' ) === $window->getValue(), 'real_observer_bound' );
			if ( in_array( $case, array( 'template_cli_headers', 'arm_cli_headers' ), true ) ) { privacy_require( array() === headers_list() && false === $transport->getValue()['failed'], 'actual_CLI_effective_headers_unavailable' ); }
			if ( 'arm_cli_headers' === $case ) { $observer->getValue()->arm_payment_observer(); } else { WCJ_Rule_Explanations::check_payment_transport(); }
			throw new RuntimeException( 'production_denial_returned' );
		}
		if ( 'arm_observer_mismatch' === $case ) { WCJ_Rule_Explanations::bind_payment_observer(); ( new WCJ_Rule_Explanations() )->arm_payment_observer(); throw new RuntimeException( 'production_denial_returned' ); }
	}
	privacy_require( 0 === $GLOBALS['privacy_reads'], 'no_early_reads' );
	echo json_encode( array( 'case' => $case, 'completed' => true, 'zero_early_actor_context_private_reads' => true, 'real_service' => true, 'native_HTTP_proof' => false ) ) . "\n";
	exit;
}
privacy_require( 'cli' === PHP_SAPI && 1 === count( $argv ), 'suite_CLI_only' );
$results = array();
foreach ( $cases as $case ) {
	$argv_child = array( PHP_BINARY, '-n', __FILE__, '--case', $case );
	$p = proc_open( $argv_child, array( 0 => array( 'pipe', 'r' ), 1 => array( 'pipe', 'w' ), 2 => array( 'pipe', 'w' ) ), $pipes, dirname( __FILE__ ) );
	privacy_require( is_resource( $p ), 'child_launch' ); fclose( $pipes[0] ); stream_set_blocking( $pipes[1], false ); stream_set_blocking( $pipes[2], false );
	$out = ''; $err = ''; $until = microtime( true ) + 15;
	do {
		$out .= stream_get_contents( $pipes[1] ); $err .= stream_get_contents( $pipes[2] );
		privacy_require( strlen( $out ) <= 4096 && strlen( $err ) <= 4096, 'bounded_child_streams' );
		$state = proc_get_status( $p );
		privacy_require( microtime( true ) < $until, 'child_deadline_no_retry' );
		if ( $state['running'] ) { usleep( 10000 ); }
	} while ( $state['running'] );
	$out .= stream_get_contents( $pipes[1] ); $err .= stream_get_contents( $pipes[2] ); fclose( $pipes[1] ); fclose( $pipes[2] );
	$exit = $state['exitcode']; $closed = proc_close( $p );
	privacy_require( 0 === $exit && '' === $err && false === strpos( $out, 'FICTITIOUS_REJECTED_INPUT' ), 'unexpected_child_failure_or_rejected_input_output' );
	$exit_case = 0 === strpos( $case, 'template_' ) || in_array( $case, array( 'arm_observer_mismatch', 'arm_cli_headers' ), true );
	if ( $exit_case ) {
		$expected = 'template_head' === $case ? '' : ( 'template_headers_sent' === $case ? "Prior test output.\n" : "Diagnostic observation unavailable.\n" );
		privacy_require( $expected === $out, 'actual_production_exit_body' );
	} else {
		$body = 'bootstrap_output' === $case ? substr( $out, strlen( "Prior test output.\n" ) ) : $out;
		$row = json_decode( $body, true ); privacy_require( is_array( $row ) && $row['case'] === $case && true === $row['completed'] && true === $row['zero_early_actor_context_private_reads'], 'actual_case_observation' );
	}
	$results[] = array( 'case' => $case, 'exit' => $exit, 'proc_close' => $closed, 'stdout_bytes' => strlen( $out ), 'stdout_sha256' => hash( 'sha256', $out ), 'stderr_bytes' => strlen( $err ), 'stderr_sha256' => hash( 'sha256', $err ), 'actual_production_exit' => $exit_case, 'scope' => in_array( $case, array( 'template_cli_headers', 'arm_cli_headers' ), true ) ? 'CLI_EFFECTIVE_HEADER_REFUSAL_ONLY_NOT_ADMISSION_BRANCH' : 'ACTUAL_OFFLINE_GUARD_OR_BOOTSTRAP', 'native_HTTP_headers_status_referrer_cache' => 'UNQUALIFIED' );
}
echo json_encode( array( 'status' => 'PASS_AFFECTED_OFFLINE_PRIVACY_GUARDS_ONLY', 'cases' => $results, 'unqualified' => array( 'effective HTTP status/headers/referrer/cache and successful native arm', 'wrong-method/context/unauthorized/nonce-identity/D03 branches behind real effective-header gate: NOT_REACHED_IN_CLI', 'recognized removed keys later admission behind real effective-header gate', 'wc-ajax preemption, unhooked arm, shutdown/output buffers, early redirects, 304/error exits, finalized headers, CDN/page cache', 'all independent QA/R1/native/browser/package/human gates' ), 'no_old_fixture_or_gateway_suite_replay' => true ) ) . "\n";
