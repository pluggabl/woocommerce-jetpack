<?php
/**
 * Activation redirect regression for isolated WP-CLI AJAX emulation.
 *
 * @package Booster_For_WooCommerce/tests
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'BOOSTER_INVOICE_SETUP_ISOLATED_QA' ) ) {
	exit( 1 ); }
if ( ! in_array( wp_parse_url( home_url(), PHP_URL_HOST ), array( '127.0.0.1', 'localhost' ), true ) ) {
	exit( 1 ); }
if ( ! defined( 'DOING_AJAX' ) ) {
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Isolated fixture emulates the core AJAX flag.
	define( 'DOING_AJAX', true );
}
$wcj_test_free     = function_exists( 'wcj_redirect_after_first_activation_free' );
$wcj_test_callback = $wcj_test_free ? 'wcj_redirect_after_first_activation_free' : ( function_exists( 'wcj_redirect_after_activation_or_update_plus' ) ? 'wcj_redirect_after_activation_or_update_plus' : 'wcj_redirect_after_activation_or_update' );
$wcj_test_before   = $wcj_test_free ? get_transient( 'wcj_activation_redirect' ) : get_option( 'wcj_do_activation_redirect', null );
$wcj_test_uid      = get_current_user_id();
$wcj_test_cases    = array();
try {
	foreach ( array( 0, 1 ) as $wcj_test_user ) {
		wp_set_current_user( $wcj_test_user );
		$wcj_test_free ? set_transient( 'wcj_activation_redirect', true, 60 ) : update_option( 'wcj_do_activation_redirect', true );
		call_user_func( $wcj_test_callback );
		$wcj_test_pending = $wcj_test_free ? get_transient( 'wcj_activation_redirect' ) : get_option( 'wcj_do_activation_redirect' );
		if ( ! $wcj_test_pending ) {
			throw new RuntimeException( 'AJAX consumed activation redirect flag' ); }
		$wcj_test_cases[ 'ajax-user-' . $wcj_test_user ] = true;
	}
	echo wp_json_encode(
		array(
			'pass'               => true,
			'cases'              => $wcj_test_cases,
			'separate_http_gate' => 'First guest AJAX must be JSON 200 with no redirect; manager page must retain onboarding',
		),
		JSON_PRETTY_PRINT
	);
} finally {
	wp_set_current_user( $wcj_test_uid );
	if ( $wcj_test_free ) {
		$wcj_test_before ? set_transient( 'wcj_activation_redirect', $wcj_test_before, 60 ) : delete_transient( 'wcj_activation_redirect' );
	} else {
		null === $wcj_test_before ? delete_option( 'wcj_do_activation_redirect' ) : update_option( 'wcj_do_activation_redirect', $wcj_test_before ); }
}
