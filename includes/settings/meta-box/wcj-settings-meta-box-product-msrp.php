<?php
/**
 * Booster for WooCommerce - Settings Meta Box - Product MSRP
 *
 * @version 4.9.0
 * @since   3.6.0
 * @author  Pluggabl LLC.
 * @todo    (maybe) `wcj_product_msrp_variable_as_simple_enabled`
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$main_product_id = get_the_ID();
$_product = wc_get_product( $main_product_id );
if ( ! $_product ) {
	return array();
}
$products = array();
$options = array();

if ( $_product->is_type( 'variable' ) && 'no' === wcj_get_option( 'wcj_product_msrp_variable_as_simple_enabled', 'no' ) ) {
	$available_variations = $_product->get_available_variations();
	foreach ( $available_variations as $variation ) {
		$variation_product = wc_get_product( $variation['variation_id'] );
		$products[ $variation['variation_id'] ] = ' (' . wcj_get_product_formatted_variation( $variation_product, true ) . ')';
	}
} else {
	$products[ $main_product_id ] = '';
}

// Archive Page Field
if ( 'yes' === wcj_get_option( 'wcj_product_msrp_archive_page_field', 'no' ) ) {
	$options[] = array(
		'name'       => 'wcj_msrp_archive_' . $main_product_id,
		'default'    => 0,
		'type'       => 'price',
		'title'      => __( 'MSRP - Archive', 'woocommerce-jetpack' ) . ' (' . get_woocommerce_currency_symbol() . ')',
		'product_id' => $main_product_id,
		'meta_name'  => '_' . 'wcj_msrp_archive',
	);
}

foreach ( $products as $product_id => $desc ) {
	$options[] = array(
		'name'       => 'wcj_msrp_' . $product_id,
		'default'    => 0,
		'type'       => 'price',
		'title'      => __( 'MSRP', 'woocommerce-jetpack' ) . ' (' . get_woocommerce_currency_symbol() . ')',
		'desc'       => $desc,
		'product_id' => $product_id,
		'meta_name'  => '_' . 'wcj_msrp',
	);
}
return $options;
