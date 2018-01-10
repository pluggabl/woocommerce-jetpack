<?php
/**
 * Booster for WooCommerce - Settings Meta Box - Admin Tools
 *
 * @version 3.3.0
 * @since   3.3.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$products = wcj_get_product_ids_for_meta_box_options( get_the_ID() );
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
}
return $settings;
