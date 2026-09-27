<?php
/** Two-instance shipping configuration regression. No runtime OR override. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'BOOSTER_INVOICE_SETUP_ISOLATED_QA' ) || ! in_array( wp_parse_url( home_url(), PHP_URL_HOST ), array( '127.0.0.1', 'localhost' ), true ) ) { exit( 1 ); }
WC()->initialize_session(); WC()->initialize_cart();
$old = array(); $products = array(); $coupons = array(); $cases = array();
$set = static function ( $k, $v ) use ( &$old ) { if ( ! array_key_exists( $k, $old ) ) { $old[ $k ] = get_option( $k, null ); } update_option( $k, $v ); };
$check = static function ( $name, $actual, $expected ) use ( &$cases ) { $cases[ $name ] = array( 'actual' => $actual, 'expected' => $expected, 'pass' => $actual === $expected ); if ( $actual !== $expected ) { throw new RuntimeException( $name . ':' . wp_json_encode( $cases[ $name ] ) ); } };
$term = wp_insert_term( 'Synthetic PAKKET ' . wp_generate_password( 8, false ), 'product_cat' );
$cat = $term['term_id'];
try {
	$set( 'woocommerce_calc_taxes', 'no' );
	foreach ( array( 'PAKKET', 'Ordinary' ) as $i => $name ) { $p = new WC_Product_Simple(); $p->set_name( $name ); $p->set_regular_price( '25' ); if ( 0 === $i ) { $p->set_category_ids( array( $cat ) ); } $p->save(); $products[] = $p; }
	foreach ( array( 'products', 'product_cats', 'product_tags', 'classes' ) as $condition ) {
		$set( 'wcj_shipping_by_' . $condition . '_section_enabled', 'yes' );
		$set( 'wcj_shipping_by_' . $condition . '_cart_not_package', 'no' );
		$set( 'wcj_shipping_by_' . $condition . '_validate_all_enabled', 'no' );
		foreach ( array( 9201, 9202 ) as $instance ) { foreach ( array( 'include', 'exclude' ) as $kind ) { $set( 'wcj_shipping_' . $condition . '_' . $kind . '_instance_' . $instance, array() ); } }
	}
	$set( 'wcj_shipping_product_cats_include_instance_9201', array( (string) $cat ) );
	$set( 'wcj_shipping_product_cats_exclude_instance_9202', array( (string) $cat ) );
	$module = new WCJ_Shipping_By_Products(); $module->use_shipping_instances = true;
	$package = static function () { return array( 'contents' => WC()->cart->get_cart(), 'destination' => array( 'country' => 'NL', 'state' => '', 'postcode' => '1000AA', 'city' => 'Synthetic', 'address' => '' ) ); };
	$rates = static function ( $pack, $require = 'min_amount', $ignore = 'yes' ) use ( $module ) {
		$r = array();
		foreach ( array( 9201, 9202 ) as $id ) { $m = new WC_Shipping_Free_Shipping( $id ); $m->requires = 9201 === $id ? '' : $require; $m->min_amount = 175; $m->ignore_discounts = $ignore; if ( $m->is_available( $pack ) ) { $r[ 'free_shipping:' . $id ] = new WC_Shipping_Rate( 'free_shipping:' . $id, 'Synthetic free', 0, array(), 'free_shipping', $id ); } }
		return array_keys( $module->available_shipping_methods( $r, $pack ) );
	};
	foreach ( array( 'ordinary-below' => array( 0, 6 ), 'ordinary-at' => array( 0, 7 ), 'ordinary-above' => array( 0, 8 ), 'pakket-below' => array( 1, 0 ), 'pakket-at' => array( 7, 0 ), 'pakket-above' => array( 8, 0 ), 'mixed-below' => array( 1, 1 ), 'mixed-at' => array( 1, 6 ), 'mixed-above' => array( 1, 7 ) ) as $name => $qty ) {
		WC()->cart->empty_cart(); foreach ( $qty as $i => $n ) { if ( $n ) { WC()->cart->add_to_cart( $products[ $i ]->get_id(), $n ); } } WC()->cart->calculate_totals();
		$check( $name, $rates( $package() ), $qty[0] ? array( 'free_shipping:9201' ) : ( $qty[1] >= 7 ? array( 'free_shipping:9202' ) : array() ) );
	}
	foreach ( array( 'discount', 'free' ) as $kind ) { $c = new WC_Coupon(); $c->set_code( 'codex-' . $kind . '-' . wp_generate_password( 6, false ) ); $c->set_discount_type( 'fixed_cart' ); $c->set_amount( 'discount' === $kind ? 25 : 0 ); $c->set_free_shipping( 'free' === $kind ); $c->save(); $coupons[ $kind ] = $c; }
	WC()->cart->empty_cart(); WC()->cart->add_to_cart( $products[1]->get_id(), 7 ); WC()->cart->apply_coupon( $coupons['discount']->get_code() ); WC()->cart->calculate_totals();
	$check( 'before-discount', $rates( $package(), 'min_amount', 'yes' ), array( 'free_shipping:9202' ) );
	$check( 'after-discount', $rates( $package(), 'min_amount', 'no' ), array() );
	WC()->cart->empty_cart(); WC()->cart->add_to_cart( $products[1]->get_id(), 1 ); WC()->cart->apply_coupon( $coupons['free']->get_code() ); WC()->cart->calculate_totals();
	$check( 'valid-free-coupon-either', $rates( $package(), 'either' ), array( 'free_shipping:9202' ) );
	$check( 'coupon-does-not-bypass-minimum-only', $rates( $package() ), array() );
	WC()->cart->remove_coupons(); WC()->cart->calculate_totals();
	$check( 'no-coupon-either-below', $rates( $package(), 'either' ), array() );
	WC()->cart->empty_cart(); WC()->cart->add_to_cart( $products[0]->get_id() ); WC()->cart->add_to_cart( $products[1]->get_id() ); WC()->cart->calculate_totals();
	$pack = $package(); $pack['contents'] = array_filter( $pack['contents'], static function ( $item ) use ( $products ) { return $item['product_id'] === $products[1]->get_id(); } );
	$check( 'split-ordinary-package', $rates( $pack ), array() );
	$set( 'wcj_shipping_by_product_cats_cart_not_package', 'yes' );
	$check( 'cart-scope-explicitly-differs', $rates( $pack ), array( 'free_shipping:9201' ) );
	$set( 'wcj_shipping_by_product_cats_cart_not_package', 'no' );
	$set( 'wcj_shipping_products_exclude_instance_9201', array( (string) $products[0]->get_id() ) );
	$check( 'explicit-product-exclusion-retained', $rates( $package() ), array() );
	echo wp_json_encode( array( 'pass' => true, 'cases' => $cases, 'uncovered' => array( 'merchant-zones', 'merchant-tax-basis', 'horeca-role-policies', 'merchant-SummerBox-exclusion' ), 'runtime_changed' => false ), JSON_PRETTY_PRINT );
} finally {
	WC()->cart->empty_cart(); foreach ( $coupons as $c ) { $c->delete( true ); } foreach ( $products as $p ) { $p->delete( true ); } wp_delete_term( $cat, 'product_cat' ); foreach ( $old as $k => $v ) { null === $v ? delete_option( $k ) : update_option( $k, $v ); }
}
