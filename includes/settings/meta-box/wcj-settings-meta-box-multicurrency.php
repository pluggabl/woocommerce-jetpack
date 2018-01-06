<?php
/**
 * Booster for WooCommerce - Settings Meta Box - Multicurrency (Currency Switcher)
 *
 * @version 2.8.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$main_product_id = get_the_ID();
$_product = wc_get_product( $main_product_id );
if ( ! $_product ) {
	return array();
}
$products = array();
if ( $_product->is_type( 'variable' ) ) {
	$available_variations = $_product->get_available_variations();
	foreach ( $available_variations as $variation ) {
		$variation_product = wc_get_product( $variation['variation_id'] );
		$products[ $variation['variation_id'] ] = ' (' . wcj_get_product_formatted_variation( $variation_product, true ) . ')';
	}
} else {
	$products[ $main_product_id ] = '';
}
$currencies = array();
$total_number = apply_filters( 'booster_option', 2, get_option( 'wcj_multicurrency_total_number', 2 ) );
foreach ( $products as $product_id => $desc ) {
	for ( $i = 1; $i <= $total_number; $i++ ) {
		$currency_code = get_option( 'wcj_multicurrency_currency_' . $i );
		$currencies = array_merge( $currencies, array(
			array(
				'name'       => 'wcj_multicurrency_per_product_regular_price_' . $currency_code . '_' . $product_id,
				'default'    => '',
				'type'       => 'price',
				'title'      => '[' . $currency_code . '] ' . __( 'Regular Price', 'woocommerce-jetpack' ),
				'desc'       => $desc,
				'product_id' => $product_id,
				'meta_name'  => '_' . 'wcj_multicurrency_per_product_regular_price_' . $currency_code,
			),
			array(
				'name'       => 'wcj_multicurrency_per_product_sale_price_' . $currency_code . '_' . $product_id,
				'default'    => '',
				'type'       => 'price',
				'title'      => '[' . $currency_code . '] ' . __( 'Sale Price', 'woocommerce-jetpack' ),
				'desc'       => $desc,
				'product_id' => $product_id,
				'meta_name'  => '_' . 'wcj_multicurrency_per_product_sale_price_' . $currency_code,
			),
		) );
	}
}
return $currencies;
