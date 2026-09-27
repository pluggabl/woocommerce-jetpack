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
class Codex_Wishlist_Json_End extends RuntimeException {}
$stop = static function () {
	return static function () { throw new Codex_Wishlist_Json_End(); };
};
add_filter( 'wp_die_handler', $stop );
add_filter( 'wp_die_ajax_handler', $stop );
WC()->initialize_session();
WC()->initialize_cart();
$key      = 'wcj_wishlist_remove_on_add_to_cart';
$previous = get_option( $key, null );
$old_uid  = get_current_user_id();
$products = array();
$cases    = array();
$uid      = wp_insert_user( array( 'user_login' => 'codex-wishlist-' . wp_generate_password( 10, false ), 'user_pass' => wp_generate_password(), 'role' => 'customer' ) );
$module   = new WCJ_Wishlist();
$check    = static function ( $name, $condition ) use ( &$cases ) {
	$cases[ $name ] = (bool) $condition;
	if ( ! $condition ) { throw new RuntimeException( $name ); }
};
$request = static function ( $id, $nonce = null ) use ( $module ) {
	$_POST['product_id']           = $id;
	$_REQUEST['wishlist_wpnonce'] = null === $nonce ? wp_create_nonce( 'wcj-wishlist' ) : $nonce;
	ob_start();
	try { $module->wcj_ajax_add_to_cart_wishlist_pro(); } catch ( Codex_Wishlist_Json_End $e ) { /* Expected JSON termination. */ }
	$output = ob_get_clean();
	$value  = json_decode( $output, true );
	if ( ! is_array( $value ) ) { throw new RuntimeException( 'Invalid JSON: ' . $output ); }
	return $value;
};
try {
	foreach ( array( 'first', 'second', 'outofstock', 'unpriced', 'variable', 'limited' ) as $type ) {
		$p = 'variable' === $type ? new WC_Product_Variable() : new WC_Product_Simple();
		$p->set_name( 'Synthetic Wishlist ' . $type );
		if ( 'unpriced' !== $type ) { $p->set_regular_price( '25' ); }
		if ( 'outofstock' === $type ) { $p->set_stock_status( 'outofstock' ); }
		if ( 'limited' === $type ) { $p->set_manage_stock( true ); $p->set_stock_quantity( 1 ); }
		$p->save();
		$products[ $type ] = $p;
	}
	$a = (string) $products['first']->get_id();
	$b = (string) $products['second']->get_id();
	wp_set_current_user( $uid );
	foreach ( array( null, 'yes', 'no' ) as $policy ) {
		null === $policy ? delete_option( $key ) : update_option( $key, $policy );
		foreach ( array( $a, $a . ',' . $b ) as $saved ) {
			update_user_meta( $uid, 'wcj_wishlist', $saved );
			WC()->cart->empty_cart();
			$r = $request( $a );
			$remove = 'no' !== $policy;
			$expected = $remove ? ( $a === $saved ? '' : $b ) : $saved;
			$name = ( null === $policy ? 'upgrade-default' : $policy ) . '-' . ( $a === $saved ? 'single' : 'multiple' );
			$check( $name, 1 === $r['success'] && $remove === $r['remove_from_wishlist'] && $expected === get_user_meta( $uid, 'wcj_wishlist', true ) && 1 === WC()->cart->get_cart_contents_count() );
			$r = $request( $a );
			$check( $name . '-repeated', 1 === $r['success'] && $expected === get_user_meta( $uid, 'wcj_wishlist', true ) );
		}
	}
	foreach ( array( 'yes', 'no' ) as $policy ) {
		update_option( $key, $policy );
		update_user_meta( $uid, 'wcj_wishlist', $a . ',' . $b );
		$invalid = array( 'zero' => '0', 'missing' => '999999999', 'negative' => '-1', 'array' => array( $a ), 'text' => 'broken', 'outofstock' => (string) $products['outofstock']->get_id(), 'unpriced' => (string) $products['unpriced']->get_id(), 'variable-selection' => (string) $products['variable']->get_id() );
		foreach ( $invalid as $name => $id ) {
			$r = $request( $id );
			$check( $policy . '-failed-' . $name, 0 === $r['success'] && false === $r['remove_from_wishlist'] && $a . ',' . $b === get_user_meta( $uid, 'wcj_wishlist', true ) );
		}
		foreach ( array( 'invalid', array( 'invalid' ) ) as $i => $nonce ) {
			$r = $request( $a, $nonce );
			$check( $policy . '-nonce-' . $i, 0 === $r['success'] && false === $r['remove_from_wishlist'] && $a . ',' . $b === get_user_meta( $uid, 'wcj_wishlist', true ) );
		}
		WC()->cart->empty_cart();
		$limited = (string) $products['limited']->get_id();
		WC()->cart->add_to_cart( $limited );
		update_user_meta( $uid, 'wcj_wishlist', $limited . ',' . $b );
		$r = $request( $limited );
		$check( $policy . '-stock-limit', 0 === $r['success'] && $limited . ',' . $b === get_user_meta( $uid, 'wcj_wishlist', true ) );
	}
	update_option( $key, 'no' );
	update_user_meta( $uid, 'wcj_wishlist', $a . ',' . $b );
	$check( 'explicit-remove-in-preservation', 1 === $module->wcj_remove_from_wishlist( $a ) && $b === get_user_meta( $uid, 'wcj_wishlist', true ) );
	wp_set_current_user( 0 );
	foreach ( array( 'yes', 'no' ) as $policy ) {
		update_option( $key, $policy );
		WC()->cart->empty_cart();
		$r = $request( $a );
		$check( 'guest-policy-' . $policy, 1 === $r['success'] && ( 'yes' === $policy ) === $r['remove_from_wishlist'] && 0 === $r['removed'] );
	}
	echo wp_json_encode( array( 'pass' => true, 'cases' => $cases, 'class_source' => ( new ReflectionClass( 'WCJ_Wishlist' ) )->getFileName(), 'browser_cookie_and_reload' => 'separate_browser_gate' ), JSON_PRETTY_PRINT );
} finally {
	WC()->cart->empty_cart();
	foreach ( $products as $p ) { $p->delete( true ); }
	null === $previous ? delete_option( $key ) : update_option( $key, $previous );
	wp_set_current_user( $old_uid );
	require_once ABSPATH . 'wp-admin/includes/user.php';
	wp_delete_user( $uid );
	remove_filter( 'wp_die_handler', $stop );
	remove_filter( 'wp_die_ajax_handler', $stop );
}
