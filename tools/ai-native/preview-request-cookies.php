<?php
/**
 * Pure structural request/cookie regression; not native authorization proof.
 *
 * @package Booster_For_WooCommerce/tools
 */

define( 'ABSPATH', __DIR__ ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Guard only; no native runtime is loaded.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Hook registration only; no native authentication or permission fake.
/** Register nothing in this standalone structural test. */
function add_filter() {}
/** Register nothing in this standalone structural test. */
function add_action() {}
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
require dirname( __DIR__, 2 ) . '/includes/admin/class-wcj-ai-preview.php';

$wcj_ai_cases = array();
/**
 * Check the actual source method with explicit request and cookie channels.
 *
 * @param string $name Case name.
 * @param array  $input Selected GET/POST input.
 * @param array  $request PHP combined request.
 * @param array  $cookies Cookie-only input.
 * @param bool   $expected Expected structural decision.
 */
function wcj_ai_cookie_case( $name, $input, $request, $cookies, $expected ) {
	$_REQUEST                         = $request;
	$_COOKIE                          = $cookies;
	$wcj_ai_actual                    = WCJ_AI_Preview::request_matches( $input );
	$GLOBALS['wcj_ai_cases'][ $name ] = $wcj_ai_actual === $expected;
}
$wcj_ai_get  = array( 'page' => 'wcj-ai-preview' );
$wcj_ai_post = array(
	'action'   => 'wcj_ai_preview',
	'op'       => 'report_capture',
	'_wpnonce' => 'synthetic-nonce',
);
$wcj_ai_wish = array( 'wcj_guest_wishlist_str' => '10,20' );
wcj_ai_cookie_case( 'plain_get', $wcj_ai_get, $wcj_ai_get, array(), true );
wcj_ai_cookie_case( 'wishlist_cookie_get', $wcj_ai_get, $wcj_ai_get + $wcj_ai_wish, $wcj_ai_wish, true );
wcj_ai_cookie_case( 'wishlist_cookie_post', $wcj_ai_post, $wcj_ai_post + $wcj_ai_wish, $wcj_ai_wish, true );
wcj_ai_cookie_case( 'wishlist_not_cookie_refused', $wcj_ai_get, $wcj_ai_get + $wcj_ai_wish, array(), false );
wcj_ai_cookie_case( 'wishlist_cookie_mismatch_refused', $wcj_ai_get, $wcj_ai_get + $wcj_ai_wish, array( 'wcj_guest_wishlist_str' => '30' ), false );
foreach ( array( 'action', 'op', 'changes', 'wcj_delete_wishlist', 'unknown' ) as $wcj_ai_key ) {
	$wcj_ai_extra = array( $wcj_ai_key => 'synthetic-command' );
	wcj_ai_cookie_case( 'cookie_command_refused_' . $wcj_ai_key, $wcj_ai_get, $wcj_ai_get + $wcj_ai_extra, $wcj_ai_extra, false );
}
wcj_ai_cookie_case( 'selected_input_mismatch_refused', $wcj_ai_get, array( 'page' => 'other' ) + $wcj_ai_wish, $wcj_ai_wish, false );
wcj_ai_cookie_case( 'selected_input_missing_refused', $wcj_ai_get, $wcj_ai_wish, $wcj_ai_wish, false );
echo json_encode( // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Standalone test, no WordPress runtime.
	array(
		'scope'      => 'Pure request structure only; no authentication or early-guard execution proof.',
		'assertions' => count( $wcj_ai_cases ),
		'cases'      => $wcj_ai_cases,
	),
	JSON_PRETTY_PRINT
);
exit( in_array( false, $wcj_ai_cases, true ) ? 1 : 0 );
