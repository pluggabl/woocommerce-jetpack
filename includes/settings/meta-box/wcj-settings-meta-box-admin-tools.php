<?php
/**
 * Booster for WooCommerce - Settings Meta Box - Admin Tools
 *
 * @version 3.4.6
 * @since   3.3.0
 * @author  Algoritmika Ltd.
 * @todo    finish "Editable meta" (for products and orders)
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$products = wcj_get_product_ids_for_meta_box_options( get_the_ID(), true );
$settings = array();
foreach ( $products as $product_id => $desc ) {
	$settings = array_merge( $settings, array(
		array(
			'type'       => 'title',
			'title'      => sprintf( __( 'Product ID: %s', 'woocommerce-jetpack' ), $product_id ) . $desc,
			'css'        => 'background-color:#cddc39;color:black;',
		),
		array(
			'name'       => '_regular_price' . '_' . $product_id,
			'default'    => '',
			'type'       => 'price',
			'title'      => __( 'Regular price', 'woocommerce' ) . ' (' . get_woocommerce_currency_symbol() . ')',
			'product_id' => $product_id,
			'meta_name'  => '_regular_price',
		),
		array(
			'name'       => '_sale_price' . '_' . $product_id,
			'default'    => '',
			'type'       => 'price',
			'title'      => __( 'Sale price', 'woocommerce' ) . ' (' . get_woocommerce_currency_symbol() . ')',
			'product_id' => $product_id,
			'meta_name'  => '_sale_price',
		),
	) );
	/*
	// Editable meta
	if ( true ) { // todo
		$all_meta = get_post_meta( $product_id );
		foreach ( $all_meta as $meta_key => $meta_value ) {
			if ( is_array( $meta_value[0] ) || is_serialized( $meta_value[0] ) || is_object( $meta_value[0] ) ) {
				continue;
			}
			$settings = array_merge( $settings, array(
				array(
					'name'       => $meta_key . '_' . $product_id,
					'default'    => '',
					'type'       => 'textarea',
					'title'      => $meta_key,
					'product_id' => $product_id,
					'meta_name'  => $meta_key,
					'css'        => 'width:100%;',
				),
			) );
		}
	}
	*/
}
return $settings;
