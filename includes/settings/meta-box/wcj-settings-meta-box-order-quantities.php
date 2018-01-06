<?php
/**
 * Booster for WooCommerce - Settings Meta Box - Order Min/Max Quantities
 *
 * @version 3.2.2
 * @since   3.2.2
 * @author  Algoritmika Ltd.
 * @todo    test "Set 0 to use global settings. Set -1 to disable"
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
$quantities = array();
foreach ( $products as $product_id => $desc ) {
	if ( 'yes' === apply_filters( 'booster_option', 'no', get_option( 'wcj_order_quantities_min_per_item_quantity_per_product', 'no' ) ) ) {
		$quantities = array_merge( $quantities, array(
			array(
				'name'       => 'wcj_order_quantities_min' . '_' . $product_id,
				'default'    => '',
				'type'       => 'number',
				'title'      => __( 'Minimum Quantity', 'woocommerce-jetpack' ),
				'desc'       => $desc,
				'product_id' => $product_id,
				'meta_name'  => '_' . 'wcj_order_quantities_min',
				'custom_attributes' => 'min="-1"',
				'tooltip'    => __( 'Set 0 to use global settings. Set -1 to disable.', 'woocommerce-jetpack' ),
			),
		) );
	}
	if ( 'yes' === apply_filters( 'booster_option', 'no', get_option( 'wcj_order_quantities_max_per_item_quantity_per_product', 'no' ) ) ) {
		$quantities = array_merge( $quantities, array(
			array(
				'name'       => 'wcj_order_quantities_max' . '_' . $product_id,
				'default'    => '',
				'type'       => 'number',
				'title'      => __( 'Maximum Quantity', 'woocommerce-jetpack' ),
				'desc'       => $desc,
				'product_id' => $product_id,
				'meta_name'  => '_' . 'wcj_order_quantities_max',
				'custom_attributes' => 'min="-1"',
				'tooltip'    => __( 'Set 0 to use global settings. Set -1 to disable.', 'woocommerce-jetpack' ),
			),
		) );
	}
}
return $quantities;
