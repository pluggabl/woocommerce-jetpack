<?php
/**
 * Booster for WooCommerce - Settings Meta Box - Order Min/Max Quantities
 *
 * @version 5.6.0
 * @since   3.2.2
 * @author  Pluggabl LLC.
 * @todo    test "Set 0 to use global settings. Set -1 to disable"
 * @package Booster_For_WooCommerce/meta-boxs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$main_product_id = get_the_ID();
$_product        = wc_get_product( $main_product_id );
if ( ! $_product ) {
	return array();
}
$products = array();
if ( $_product->is_type( 'variable' ) ) {
	$available_variations = $_product->get_available_variations();
	foreach ( $available_variations as $variation ) {
		$variation_product                      = wc_get_product( $variation['variation_id'] );
		$products[ $variation['variation_id'] ] = ' (' . wcj_get_product_formatted_variation( $variation_product, true ) . ')';
	}
} else {
	$products[ $main_product_id ] = '';
}
$qty_step_settings = ( 'yes' === wcj_get_option( 'wcj_order_quantities_decimal_qty_enabled', 'no' ) ? '0.000001' : '1' );
$quantities        = array();
foreach ( $products as $product_id => $desc ) {
	if ( $this->is_min_per_product_enabled ) {
		$quantities = array_merge(
			$quantities,
			array(
				array(
					'name'              => 'wcj_order_quantities_min_' . $product_id,
					'default'           => '',
					'type'              => 'number',
					'title'             => __( 'Minimum Quantity', 'woocommerce-jetpack' ),
					'desc'              => $desc,
					'product_id'        => $product_id,
					'meta_name'         => '_wcj_order_quantities_min',
					'custom_attributes' => 'min="-1" step="' . $qty_step_settings . '"',
					'tooltip'           => __( 'Set 0 to use global settings. Set -1 to disable.', 'woocommerce-jetpack' ),
				),
			)
		);
	}
	if ( $this->is_max_per_product_enabled ) {
		$quantities = array_merge(
			$quantities,
			array(
				array(
					'name'              => 'wcj_order_quantities_max_' . $product_id,
					'default'           => '',
					'type'              => 'number',
					'title'             => __( 'Maximum Quantity', 'woocommerce-jetpack' ),
					'desc'              => $desc,
					'product_id'        => $product_id,
					'meta_name'         => '_wcj_order_quantities_max',
					'custom_attributes' => 'min="-1" step="' . $qty_step_settings . '"',
					'tooltip'           => __( 'Set 0 to use global settings. Set -1 to disable.', 'woocommerce-jetpack' ),
				),
			)
		);
	}
}
if ( $this->is_step_per_product_enabled ) {
	$quantities = array_merge(
		$quantities,
		array(
			array(
				'name'              => 'wcj_order_quantities_step_' . $main_product_id,
				'default'           => '',
				'type'              => 'number',
				'title'             => __( 'Quantity Step', 'woocommerce-jetpack' ),
				'desc'              => ( $_product->is_type( 'variable' ) ? __( 'All variations', 'woocommerce-jetpack' ) : '' ),
				'product_id'        => $main_product_id,
				'meta_name'         => '_wcj_order_quantities_step',
				'custom_attributes' => 'min="0" step="' . $qty_step_settings . '"',
				'tooltip'           => __( 'Set 0 to use global settings.', 'woocommerce-jetpack' ),
			),
		)
	);
}
return $quantities;
