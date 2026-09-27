<?php
/**
 * Two-instance shipping configuration regression. No runtime OR override.
 *
 * @package Booster_For_WooCommerce/tests
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI || '1' !== getenv( 'BOOSTER_INVOICE_SETUP_ISOLATED_QA' ) || ! in_array( wp_parse_url( home_url(), PHP_URL_HOST ), array( '127.0.0.1', 'localhost' ), true ) ) {
	exit( 1 ); }
WC()->initialize_session();
WC()->initialize_cart();
$wcj_test_old      = array();
$wcj_test_products = array();
$wcj_test_coupons  = array();
$wcj_test_cases    = array();
$wcj_test_set      = static function ( $wcj_test_k, $wcj_test_v ) use ( &$wcj_test_old ) {
	if ( ! array_key_exists( $wcj_test_k, $wcj_test_old ) ) {
		$wcj_test_old[ $wcj_test_k ] = get_option( $wcj_test_k, null );
	} update_option( $wcj_test_k, $wcj_test_v );
	$wcj_test_booster = function_exists( 'WCJ' ) ? WCJ() : w_c_j();
	unset( $wcj_test_booster->options[ $wcj_test_k ] );
};
$wcj_test_check    = static function ( $wcj_test_name, $wcj_test_actual, $wcj_test_expected ) use ( &$wcj_test_cases ) {
	$wcj_test_cases[ $wcj_test_name ] = array(
		'actual'   => $wcj_test_actual,
		'expected' => $wcj_test_expected,
		'pass'     => $wcj_test_actual === $wcj_test_expected,
	);
	if ( $wcj_test_actual !== $wcj_test_expected ) {
		throw new RuntimeException( esc_html( $wcj_test_name . ':' . wp_json_encode( $wcj_test_cases[ $wcj_test_name ] ) ) );
	} };
$wcj_test_term     = wp_insert_term( 'Synthetic PAKKET ' . wp_generate_password( 8, false ), 'product_cat' );
$wcj_test_cat      = $wcj_test_term['term_id'];
try {
	$wcj_test_set( 'woocommerce_calc_taxes', 'no' );
	foreach ( array( 'PAKKET', 'Ordinary' ) as $wcj_test_i => $wcj_test_name ) {
		$wcj_test_p = new WC_Product_Simple();
		$wcj_test_p->set_name( $wcj_test_name );
		$wcj_test_p->set_regular_price( '25' );
		if ( 0 === $wcj_test_i ) {
			$wcj_test_p->set_category_ids( array( $wcj_test_cat ) );
		} $wcj_test_p->save();
		$wcj_test_products[] = $wcj_test_p; }
	foreach ( array( 'products', 'product_cats', 'product_tags', 'classes' ) as $wcj_test_condition ) {
		$wcj_test_set( 'wcj_shipping_by_' . $wcj_test_condition . '_section_enabled', 'yes' );
		$wcj_test_set( 'wcj_shipping_by_' . $wcj_test_condition . '_cart_not_package', 'no' );
		$wcj_test_set( 'wcj_shipping_by_' . $wcj_test_condition . '_validate_all_enabled', 'no' );
		foreach ( array( 9201, 9202 ) as $wcj_test_instance ) {
			foreach ( array( 'include', 'exclude' ) as $wcj_test_kind ) {
				$wcj_test_set( 'wcj_shipping_' . $wcj_test_condition . '_' . $wcj_test_kind . '_instance_' . $wcj_test_instance, array() ); }
		}
	}
	$wcj_test_set( 'wcj_shipping_product_cats_include_instance_9201', array( (string) $wcj_test_cat ) );
	$wcj_test_set( 'wcj_shipping_product_cats_exclude_instance_9202', array( (string) $wcj_test_cat ) );
	$wcj_test_module                         = new WCJ_Shipping_By_Products();
	$wcj_test_module->use_shipping_instances = true;
	$wcj_test_package                        = static function () {
		return array(
			'contents'    => WC()->cart->get_cart(),
			'destination' => array(
				'country'  => 'NL',
				'state'    => '',
				'postcode' => '1000AA',
				'city'     => 'Synthetic',
				'address'  => '',
			),
		);
	};
	$wcj_test_rates                          = static function ( $wcj_test_pack, $wcj_test_require = 'min_amount', $wcj_test_ignore = 'yes' ) use ( $wcj_test_module ) {
		$wcj_test_r = array();
		foreach ( array( 9201, 9202 ) as $wcj_test_id ) {
			$wcj_test_m                   = new WC_Shipping_Free_Shipping( $wcj_test_id );
			$wcj_test_m->requires         = 9201 === $wcj_test_id ? '' : $wcj_test_require;
			$wcj_test_m->min_amount       = 175;
			$wcj_test_m->ignore_discounts = $wcj_test_ignore;
			if ( $wcj_test_m->is_available( $wcj_test_pack ) ) {
				$wcj_test_r[ 'free_shipping:' . $wcj_test_id ] = new WC_Shipping_Rate( 'free_shipping:' . $wcj_test_id, 'Synthetic free', 0, array(), 'free_shipping', $wcj_test_id ); }
		}
		return array_keys( $wcj_test_module->available_shipping_methods( $wcj_test_r, $wcj_test_pack ) );
	};
	foreach ( array(
		'ordinary-below' => array( 0, 6 ),
		'ordinary-at'    => array( 0, 7 ),
		'ordinary-above' => array( 0, 8 ),
		'pakket-below'   => array( 1, 0 ),
		'pakket-at'      => array( 7, 0 ),
		'pakket-above'   => array( 8, 0 ),
		'mixed-below'    => array( 1, 1 ),
		'mixed-at'       => array( 1, 6 ),
		'mixed-above'    => array( 1, 7 ),
	) as $wcj_test_name => $wcj_test_qty ) {
		WC()->cart->empty_cart();
		foreach ( $wcj_test_qty as $wcj_test_i => $wcj_test_n ) {
			if ( $wcj_test_n ) {
				WC()->cart->add_to_cart( $wcj_test_products[ $wcj_test_i ]->get_id(), $wcj_test_n );
			}
		} WC()->cart->calculate_totals();
		$wcj_test_check( $wcj_test_name, $wcj_test_rates( $wcj_test_package() ), $wcj_test_qty[0] ? array( 'free_shipping:9201' ) : ( $wcj_test_qty[1] >= 7 ? array( 'free_shipping:9202' ) : array() ) );
	}
	foreach ( array( 'discount', 'free' ) as $wcj_test_kind ) {
		$wcj_test_c = new WC_Coupon();
		$wcj_test_c->set_code( 'codex-' . $wcj_test_kind . '-' . wp_generate_password( 6, false ) );
		$wcj_test_c->set_discount_type( 'fixed_cart' );
		$wcj_test_c->set_amount( 'discount' === $wcj_test_kind ? 25 : 0 );
		$wcj_test_c->set_free_shipping( 'free' === $wcj_test_kind );
		$wcj_test_c->save();
		$wcj_test_coupons[ $wcj_test_kind ] = $wcj_test_c; }
	WC()->cart->empty_cart();
	WC()->cart->add_to_cart( $wcj_test_products[1]->get_id(), 7 );
	WC()->cart->apply_coupon( $wcj_test_coupons['discount']->get_code() );
	WC()->cart->calculate_totals();
	$wcj_test_check( 'before-discount', $wcj_test_rates( $wcj_test_package(), 'min_amount', 'yes' ), array( 'free_shipping:9202' ) );
	$wcj_test_check( 'after-discount', $wcj_test_rates( $wcj_test_package(), 'min_amount', 'no' ), array() );
	WC()->cart->empty_cart();
	WC()->cart->add_to_cart( $wcj_test_products[1]->get_id(), 1 );
	WC()->cart->apply_coupon( $wcj_test_coupons['free']->get_code() );
	WC()->cart->calculate_totals();
	$wcj_test_check( 'valid-free-coupon-either', $wcj_test_rates( $wcj_test_package(), 'either' ), array( 'free_shipping:9202' ) );
	$wcj_test_check( 'coupon-does-not-bypass-minimum-only', $wcj_test_rates( $wcj_test_package() ), array() );
	WC()->cart->remove_coupons();
	WC()->cart->calculate_totals();
	$wcj_test_check( 'no-coupon-either-below', $wcj_test_rates( $wcj_test_package(), 'either' ), array() );
	WC()->cart->empty_cart();
	WC()->cart->add_to_cart( $wcj_test_products[0]->get_id() );
	WC()->cart->add_to_cart( $wcj_test_products[1]->get_id() );
	WC()->cart->calculate_totals();
	$wcj_test_pack             = $wcj_test_package();
	$wcj_test_pack['contents'] = array_filter(
		$wcj_test_pack['contents'],
		static function ( $wcj_test_item ) use ( $wcj_test_products ) {
			return $wcj_test_item['product_id'] === $wcj_test_products[1]->get_id();
		}
	);
	$wcj_test_check( 'split-ordinary-package', $wcj_test_rates( $wcj_test_pack ), array() );
	$wcj_test_set( 'wcj_shipping_by_product_cats_cart_not_package', 'yes' );
	$wcj_test_check( 'cart-scope-explicitly-differs', $wcj_test_rates( $wcj_test_pack ), array( 'free_shipping:9201' ) );
	$wcj_test_set( 'wcj_shipping_by_product_cats_cart_not_package', 'no' );
	$wcj_test_set( 'wcj_shipping_products_exclude_instance_9201', array( (string) $wcj_test_products[0]->get_id() ) );
	$wcj_test_check( 'explicit-product-exclusion-retained', $wcj_test_rates( $wcj_test_package() ), array() );
	$wcj_test_set( 'wcj_shipping_products_exclude_instance_9201', array() );
	$wcj_test_set( 'wcj_shipping_by_product_cats_validate_all_enabled', 'yes' );
	$wcj_test_check( 'all-items-is-not-mixed-any', $wcj_test_rates( $wcj_test_package() ), array() );
	$wcj_test_set( 'wcj_shipping_by_product_cats_validate_all_enabled', 'no' );
	WC()->cart->empty_cart();
	$wcj_test_check( 'empty-cart', $wcj_test_rates( $wcj_test_package() ), array() );
	$wcj_test_parent = new WC_Product_Variable();
	$wcj_test_parent->set_name( 'Synthetic category variation parent' );
	$wcj_test_parent->set_category_ids( array( $wcj_test_cat ) );
	$wcj_test_parent->save();
	$wcj_test_products[] = $wcj_test_parent;
	$wcj_test_variation  = new WC_Product_Variation();
	$wcj_test_variation->set_parent_id( $wcj_test_parent->get_id() );
	$wcj_test_variation->set_regular_price( '25' );
	$wcj_test_variation->set_status( 'publish' );
	$wcj_test_variation->save();
	$wcj_test_products[] = $wcj_test_variation;
	WC_Product_Variable::sync( $wcj_test_parent->get_id() );
	WC()->cart->add_to_cart( $wcj_test_parent->get_id(), 1, $wcj_test_variation->get_id() );
	WC()->cart->calculate_totals();
	$wcj_test_check( 'variation-parent-category', $wcj_test_rates( $wcj_test_package() ), array( 'free_shipping:9201' ) );
	WC()->cart->empty_cart();
	WC()->cart->add_to_cart( $wcj_test_products[1]->get_id() );
	$wcj_test_check( 'invalid-coupon-rejected', WC()->cart->apply_coupon( 'codex-no-such-coupon' ), false );
	WC()->cart->calculate_totals();
	$wcj_test_check( 'invalid-coupon-does-not-grant-free', $wcj_test_rates( $wcj_test_package(), 'either' ), array() );
	WC()->cart->empty_cart();
	$wcj_test_zone = new WC_Shipping_Zone();
	$wcj_test_zone->set_zone_name( 'Synthetic FR isolated test' );
	$wcj_test_zone->add_location( 'FR', 'country' );
	$wcj_test_zone->save();
	$wcj_test_pack_fr                           = $wcj_test_package();
	$wcj_test_pack_fr['destination']['country'] = 'FR';
	$wcj_test_check( 'zone-country-match', WC_Shipping_Zones::get_zone_matching_package( $wcj_test_pack_fr )->get_id(), $wcj_test_zone->get_id() );
	$wcj_test_check( 'zone-does-not-leak-to-NL', WC_Shipping_Zones::get_zone_matching_package( $wcj_test_package() )->get_id() === $wcj_test_zone->get_id(), false );
	$wcj_test_zone->delete();
	$wcj_test_zone = null;
	// Tax basis follows native WooCommerce display/subtotal rules, not an OR override.
	$wcj_test_set( 'woocommerce_calc_taxes', 'yes' );
	$wcj_test_set( 'woocommerce_prices_include_tax', 'no' );
	$wcj_test_set( 'woocommerce_tax_based_on', 'shipping' );
	$wcj_test_set( 'woocommerce_tax_display_cart', 'incl' );
	WC()->customer->set_shipping_country( 'NL' );
	WC()->customer->set_shipping_postcode( '1000AA' );
	WC()->customer->set_is_vat_exempt( false );
	$wcj_test_tax = WC_Tax::_insert_tax_rate(
		array(
			'tax_rate_country'  => 'NL',
			'tax_rate_state'    => '',
			'tax_rate'          => '21',
			'tax_rate_name'     => 'Synthetic VAT',
			'tax_rate_priority' => 1,
			'tax_rate_compound' => 0,
			'tax_rate_shipping' => 1,
			'tax_rate_order'    => 0,
			'tax_rate_class'    => '',
		)
	);
	WC_Cache_Helper::invalidate_cache_group( 'taxes' );
	WC()->cart->add_to_cart( $wcj_test_products[1]->get_id(), 6 );
	WC()->cart->calculate_totals();
	$wcj_test_check( 'tax-inclusive-subtotal-crosses-threshold', $wcj_test_rates( $wcj_test_package() ), array( 'free_shipping:9202' ) );
	$wcj_test_set( 'woocommerce_tax_display_cart', 'excl' );
	WC()->cart->calculate_totals();
	$wcj_test_check( 'tax-exclusive-subtotal-below', $wcj_test_rates( $wcj_test_package() ), array() );
	WC_Tax::_delete_tax_rate( $wcj_test_tax );
	$wcj_test_tax = null;
	WC_Cache_Helper::invalidate_cache_group( 'taxes' );
	$wcj_test_set( 'woocommerce_calc_taxes', 'no' );
	// Role restrictions remain a separate filtering step, not permission to grant a rate.
	$wcj_test_roles                         = new WCJ_Shipping_By_User_Role();
	$wcj_test_roles->use_shipping_instances = true;
	$wcj_test_set( 'wcj_shipping_user_roles_include_instance_9201', array( 'customer' ) );
	$wcj_test_role_rates            = array( 'free_shipping:9201' => new WC_Shipping_Rate( 'free_shipping:9201', 'Synthetic', 0, array(), 'free_shipping', 9201 ) );
	$wcj_test_roles->customer_roles = array( 'guest' );
	$wcj_test_check( 'guest-role-restriction', array_keys( $wcj_test_roles->available_shipping_methods( $wcj_test_role_rates, $wcj_test_package() ) ), array() );
	$wcj_test_roles->customer_roles = array( 'customer' );
	$wcj_test_check( 'customer-role-allowed', array_keys( $wcj_test_roles->available_shipping_methods( $wcj_test_role_rates, $wcj_test_package() ) ), array( 'free_shipping:9201' ) );
	$wcj_test_shipping_options = new WCJ_Shipping_Options();
	$wcj_test_set( 'wcj_shipping_hide_if_free_available_type', 'hide_all' );
	$wcj_test_role_rates['flat_rate:9203'] = new WC_Shipping_Rate( 'flat_rate:9203', 'Synthetic paid', 6, array(), 'flat_rate', 9203 );
	$wcj_test_check( 'hide-paid-when-free-configured', array_keys( $wcj_test_shipping_options->hide_shipping_when_free_is_available( $wcj_test_role_rates, $wcj_test_package() ) ), array( 'free_shipping:9201' ) );
	$wcj_test_set( 'wcj_shipping_free_shipping_by_product_products', array() );
	$wcj_test_check( 'no-extra-product-grant', $wcj_test_shipping_options->free_shipping_by_product( false, $wcj_test_package() ), false );
	echo wp_json_encode(
		array(
			'pass'            => true,
			'cases'           => $wcj_test_cases,
			'uncovered'       => array( 'merchant-zones', 'customer-confirmed-tax-policy', 'horeca-role-policies', 'merchant-SummerBox-exclusion' ),
			'runtime_changed' => false,
		),
		JSON_PRETTY_PRINT
	);
} finally {
	WC()->cart->empty_cart();
	if ( ! empty( $wcj_test_zone ) ) {
		$wcj_test_zone->delete();
	} if ( ! empty( $wcj_test_tax ) ) {
		WC_Tax::_delete_tax_rate( $wcj_test_tax );
		WC_Cache_Helper::invalidate_cache_group( 'taxes' );
	} foreach ( $wcj_test_coupons as $wcj_test_c ) {
		$wcj_test_c->delete( true );
	} foreach ( $wcj_test_products as $wcj_test_p ) {
		$wcj_test_p->delete( true );
	} wp_delete_term( $wcj_test_cat, 'product_cat' );
	foreach ( $wcj_test_old as $wcj_test_k => $wcj_test_v ) {
		null === $wcj_test_v ? delete_option( $wcj_test_k ) : update_option( $wcj_test_k, $wcj_test_v ); }
}
