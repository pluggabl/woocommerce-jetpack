<?php
/**
 * Wishlist policy regression for an isolated, disposable WordPress test site.
 *
 * Run: BOOSTER_INVOICE_SETUP_ISOLATED_QA=1 wp eval-file this-file.php
 * Never run on a merchant installation. Products, user and settings are restored.
 *
 * @package Booster_For_WooCommerce/tests
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'BOOSTER_INVOICE_SETUP_ISOLATED_QA' ) || ! in_array( wp_parse_url( home_url(), PHP_URL_HOST ), array( '127.0.0.1', 'localhost' ), true ) ) {
	exit( 1 );
}
if ( ! defined( 'DOING_AJAX' ) ) {
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Isolated fixture emulates the core AJAX flag.
	define( 'DOING_AJAX', true );
}
$wcj_test_stop = static function () {
	return static function () {
		throw new RuntimeException( 'wcj-json-end' );
	};
};
add_filter( 'wp_die_handler', $wcj_test_stop );
add_filter( 'wp_die_ajax_handler', $wcj_test_stop );
WC()->initialize_session();
WC()->initialize_cart();
$wcj_test_key      = 'wcj_wishlist_remove_on_add_to_cart';
$wcj_test_previous = get_option( $wcj_test_key, null );
$wcj_test_old_uid  = get_current_user_id();
$wcj_test_products = array();
$wcj_test_cases    = array();
$wcj_test_uid      = wp_insert_user(
	array(
		'user_login' => 'codex-wishlist-' . wp_generate_password( 10, false ),
		'user_pass'  => wp_generate_password(),
		'role'       => 'customer',
	)
);
$wcj_test_module   = new WCJ_Wishlist();
$wcj_test_check    = static function ( $wcj_test_name, $wcj_test_condition ) use ( &$wcj_test_cases ) {
	$wcj_test_cases[ $wcj_test_name ] = (bool) $wcj_test_condition;
	if ( ! $wcj_test_condition ) {
		throw new RuntimeException( esc_html( $wcj_test_name ) ); }
};
$wcj_test_request  = static function ( $wcj_test_id, $wcj_test_nonce = null ) use ( $wcj_test_module ) {
	$wcj_test_booster = function_exists( 'WCJ' ) ? WCJ() : w_c_j();
	unset( $wcj_test_booster->options['wcj_wishlist_remove_on_add_to_cart'] );
	$_POST['product_id']          = $wcj_test_id;
	$_REQUEST['wishlist_wpnonce'] = null === $wcj_test_nonce ? wp_create_nonce( 'wcj-wishlist' ) : $wcj_test_nonce;
	ob_start();
	try {
		$wcj_test_module->wcj_ajax_add_to_cart_wishlist_pro();
	} catch ( RuntimeException $wcj_test_e ) {
		if ( 'wcj-json-end' !== $wcj_test_e->getMessage() ) {
			throw $wcj_test_e; }
	}
	$wcj_test_output = ob_get_clean();
	$wcj_test_value  = json_decode( $wcj_test_output, true );
	if ( ! is_array( $wcj_test_value ) ) {
		throw new RuntimeException( esc_html( 'Invalid JSON: ' . $wcj_test_output ) ); }
	return $wcj_test_value;
};
try {
	foreach ( array( 'first', 'second', 'outofstock', 'unpriced', 'variable', 'limited' ) as $wcj_test_type ) {
		$wcj_test_p = 'variable' === $wcj_test_type ? new WC_Product_Variable() : new WC_Product_Simple();
		$wcj_test_p->set_name( 'Synthetic Wishlist ' . $wcj_test_type );
		if ( 'unpriced' !== $wcj_test_type ) {
			$wcj_test_p->set_regular_price( '25' ); }
		if ( 'outofstock' === $wcj_test_type ) {
			$wcj_test_p->set_stock_status( 'outofstock' ); }
		if ( 'limited' === $wcj_test_type ) {
			$wcj_test_p->set_manage_stock( true );
			$wcj_test_p->set_stock_quantity( 1 ); }
		$wcj_test_p->save();
		$wcj_test_products[ $wcj_test_type ] = $wcj_test_p;
	}
	$wcj_test_a = (string) $wcj_test_products['first']->get_id();
	$wcj_test_b = (string) $wcj_test_products['second']->get_id();
	wp_set_current_user( $wcj_test_uid );
	foreach ( array( null, 'yes', 'no' ) as $wcj_test_policy ) {
		null === $wcj_test_policy ? delete_option( $wcj_test_key ) : update_option( $wcj_test_key, $wcj_test_policy );
		foreach ( array( $wcj_test_a, $wcj_test_a . ',' . $wcj_test_b ) as $wcj_test_saved ) {
			update_user_meta( $wcj_test_uid, 'wcj_wishlist', $wcj_test_saved );
			WC()->cart->empty_cart();
			$wcj_test_r        = $wcj_test_request( $wcj_test_a );
			$wcj_test_remove   = 'no' !== $wcj_test_policy;
			$wcj_test_expected = $wcj_test_remove ? ( $wcj_test_a === $wcj_test_saved ? '' : $wcj_test_b ) : $wcj_test_saved;
			$wcj_test_name     = ( null === $wcj_test_policy ? 'upgrade-default' : $wcj_test_policy ) . '-' . ( $wcj_test_a === $wcj_test_saved ? 'single' : 'multiple' );
			$wcj_test_check( $wcj_test_name, 1 === $wcj_test_r['success'] && $wcj_test_remove === $wcj_test_r['remove_from_wishlist'] && get_user_meta( $wcj_test_uid, 'wcj_wishlist', true ) === $wcj_test_expected && 1 === WC()->cart->get_cart_contents_count() );
			$wcj_test_r = $wcj_test_request( $wcj_test_a );
			$wcj_test_check( $wcj_test_name . '-repeated', 1 === $wcj_test_r['success'] && get_user_meta( $wcj_test_uid, 'wcj_wishlist', true ) === $wcj_test_expected );
		}
	}
	foreach ( array( 'yes', 'no' ) as $wcj_test_policy ) {
		update_option( $wcj_test_key, $wcj_test_policy );
		update_user_meta( $wcj_test_uid, 'wcj_wishlist', $wcj_test_a . ',' . $wcj_test_b );
		$wcj_test_invalid = array(
			'zero'               => '0',
			'missing'            => '999999999',
			'negative'           => '-1',
			'array'              => array( $wcj_test_a ),
			'text'               => 'broken',
			'outofstock'         => (string) $wcj_test_products['outofstock']->get_id(),
			'unpriced'           => (string) $wcj_test_products['unpriced']->get_id(),
			'variable-selection' => (string) $wcj_test_products['variable']->get_id(),
		);
		foreach ( $wcj_test_invalid as $wcj_test_name => $wcj_test_id ) {
			$wcj_test_r = $wcj_test_request( $wcj_test_id );
			$wcj_test_check( $wcj_test_policy . '-failed-' . $wcj_test_name, 0 === $wcj_test_r['success'] && false === $wcj_test_r['remove_from_wishlist'] && get_user_meta( $wcj_test_uid, 'wcj_wishlist', true ) === $wcj_test_a . ',' . $wcj_test_b );
		}
		foreach ( array( 'invalid', array( 'invalid' ) ) as $wcj_test_i => $wcj_test_nonce ) {
			$wcj_test_r = $wcj_test_request( $wcj_test_a, $wcj_test_nonce );
			$wcj_test_check( $wcj_test_policy . '-nonce-' . $wcj_test_i, 0 === $wcj_test_r['success'] && false === $wcj_test_r['remove_from_wishlist'] && get_user_meta( $wcj_test_uid, 'wcj_wishlist', true ) === $wcj_test_a . ',' . $wcj_test_b );
		}
		WC()->cart->empty_cart();
		$wcj_test_limited = (string) $wcj_test_products['limited']->get_id();
		WC()->cart->add_to_cart( $wcj_test_limited );
		update_user_meta( $wcj_test_uid, 'wcj_wishlist', $wcj_test_limited . ',' . $wcj_test_b );
		$wcj_test_r = $wcj_test_request( $wcj_test_limited );
		$wcj_test_check( $wcj_test_policy . '-stock-limit', 0 === $wcj_test_r['success'] && get_user_meta( $wcj_test_uid, 'wcj_wishlist', true ) === $wcj_test_limited . ',' . $wcj_test_b );
	}
	update_option( $wcj_test_key, 'no' );
	update_user_meta( $wcj_test_uid, 'wcj_wishlist', $wcj_test_a . ',' . $wcj_test_b );
	$wcj_test_check( 'explicit-remove-in-preservation', 1 === $wcj_test_module->wcj_remove_from_wishlist( $wcj_test_a ) && get_user_meta( $wcj_test_uid, 'wcj_wishlist', true ) === $wcj_test_b );
	wp_set_current_user( 0 );
	foreach ( array( 'yes', 'no' ) as $wcj_test_policy ) {
		update_option( $wcj_test_key, $wcj_test_policy );
		WC()->cart->empty_cart();
		$wcj_test_r = $wcj_test_request( $wcj_test_a );
		$wcj_test_check( 'guest-policy-' . $wcj_test_policy, 1 === $wcj_test_r['success'] && ( 'yes' === $wcj_test_policy ) === $wcj_test_r['remove_from_wishlist'] && 0 === $wcj_test_r['removed'] );
	}
	echo wp_json_encode(
		array(
			'pass'                      => true,
			'cases'                     => $wcj_test_cases,
			'class_source'              => ( new ReflectionClass( 'WCJ_Wishlist' ) )->getFileName(),
			'browser_cookie_and_reload' => 'separate_browser_gate',
		),
		JSON_PRETTY_PRINT
	);
} finally {
	WC()->cart->empty_cart();
	foreach ( $wcj_test_products as $wcj_test_p ) {
		$wcj_test_p->delete( true ); }
	null === $wcj_test_previous ? delete_option( $wcj_test_key ) : update_option( $wcj_test_key, $wcj_test_previous );
	wp_set_current_user( $wcj_test_old_uid );
	require_once ABSPATH . 'wp-admin/includes/user.php';
	wp_delete_user( $wcj_test_uid );
	remove_filter( 'wp_die_handler', $wcj_test_stop );
	remove_filter( 'wp_die_ajax_handler', $wcj_test_stop );
}
