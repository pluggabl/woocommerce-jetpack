<?php
/**
 * Booster for WooCommerce - Settings Meta Box - Related Products
 *
 * @version 5.6.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/meta-boxs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$product_id       = get_the_ID();
$products         = wcj_get_products( array(), 'publish' );
$is_chosen_select = ( 'chosen_select' === wcj_get_option( 'wcj_product_info_related_products_per_product_box_type', 'chosen_select' ) );
$default_value    = wcj_get_option( 'wcj_product_info_related_products_per_product_cmb_default', 'no' );
unset( $products[ $product_id ] );
$options = array(
	array(
		'title'   => __( 'Enable', 'woocommerce-jetpack' ),
		'tooltip' => __( 'If enabled and no products selected - will hide related products section on frontend for current product.', 'woocommerce-jetpack' ),
		'name'    => 'wcj_product_info_related_products_enabled',
		'default' => $default_value,
		'type'    => 'select',
		'options' => array(
			'no'  => __( 'No', 'woocommerce-jetpack' ),
			'yes' => __( 'Yes', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'    => __( 'Related Products', 'woocommerce-jetpack' ),
		'tooltip'  => ( $is_chosen_select ? '' : __( 'Hold Control (Ctrl) key to select multiple products. Ctrl and "A" to select all products.', 'woocommerce-jetpack' ) ),
		'name'     => 'wcj_product_info_related_products_ids',
		'default'  => '',
		'type'     => 'select',
		'options'  => $products,
		'multiple' => true,
		'css'      => ( $is_chosen_select ? 'width:100%' : 'height:300px' ),
		'class'    => ( $is_chosen_select ? 'chosen_select' : '' ),
	),
);
return $options;
