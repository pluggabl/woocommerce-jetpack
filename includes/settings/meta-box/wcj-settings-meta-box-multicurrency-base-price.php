<?php
/**
 * Booster for WooCommerce - Settings Meta Box - Multicurrency Product Base Price
 *
 * @version 2.8.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/*
$main_product_id = get_the_ID();
$_product = wc_get_product( $main_product_id );
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
$options = array();
$total_number = apply_filters( 'booster_option', 1, get_option( 'wcj_multicurrency_base_price_total_number', 1 ) );
foreach ( $products as $product_id => $desc ) {
	$currency_codes = array();
	$currency_codes[ get_woocommerce_currency() ] = get_woocommerce_currency();
	for ( $i = 1; $i <= $total_number; $i++ ) {
		$currency_codes[ get_option( 'wcj_multicurrency_base_price_currency_' . $i ) ] = get_option( 'wcj_multicurrency_base_price_currency_' . $i );
	}
	$options[] = array(
		'name'       => 'wcj_multicurrency_base_price_currency_' . $product_id,
		'default'    => '',
		'type'       => 'select',
		'title'      => __( 'Product Currency', 'woocommerce-jetpack' ),
		'desc'       => $desc,
		'product_id' => $product_id,
		'meta_name'  => '_' . 'wcj_multicurrency_base_price_currency',
		'options'    => $currency_codes,
	);
}
return $options;
*/
$currency_codes = array();
$currency_codes[ get_woocommerce_currency() ] = get_woocommerce_currency();
$total_number = apply_filters( 'booster_option', 1, get_option( 'wcj_multicurrency_base_price_total_number', 1 ) );
for ( $i = 1; $i <= $total_number; $i++ ) {
	$currency_codes[ get_option( 'wcj_multicurrency_base_price_currency_' . $i ) ] = get_option( 'wcj_multicurrency_base_price_currency_' . $i );
}
$options = array(
	array(
		'name'       => 'wcj_multicurrency_base_price_currency',
		'default'    => get_woocommerce_currency(),
		'type'       => 'select',
		'title'      => __( 'Product Currency', 'woocommerce-jetpack' ),
		'options'    => $currency_codes,
	),
);
return $options;
