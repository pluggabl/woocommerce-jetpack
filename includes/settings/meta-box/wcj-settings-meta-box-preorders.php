<?php
/**
 * Booster for WooCommerce - Settings Meta Box - Pre Orders
 *
 * @version 7.3.1
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/meta-boxs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$product_id = get_the_ID();
$products   = wcj_get_products( array(), 'publish', 256, true, true );
unset( $products[ $product_id ] );
$format = wcj_get_option( 'wcj_product_preorder_release_date_format', 'm/d/Y' );

$options = array(
	array(
		'title'   => __( 'Enable Pre-order', 'woocommerce-jetpack' ),
		'name'    => 'wcj_product_preorder_enabled',
		'default' => 'no',
		'type'    => 'select',
		'options' => array(
			'no'  => __( 'No', 'woocommerce-jetpack' ),
			'yes' => __( 'Yes', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'             => __( 'Release Date', 'woocommerce-jetpack' ),
		'name'              => 'wcj_product_preorder_release_date',
		'type'              => 'date',
		'default'           => '',
		'custom_attributes' => ( '' !== ( $format ) ? 'dateformat="' . wcj_date_format_php_to_js( $format ) . '"' : '' ),
	),
	array(
		'title'   => __( 'Pricing Type', 'woocommerce-jetpack' ),
		'name'    => 'wcj_product_preorder_price_type',
		'type'    => 'select',
		'default' => 'default',
		'options' => array(
			'default'  => __( 'Default Product Price', 'woocommerce-jetpack' ),
			'fixed'    => __( 'Fixed Pre-order Price', 'woocommerce-jetpack' ),
			'discount' => __( 'Discount on Default Product Price', 'woocommerce-jetpack' ),
			'increase' => __( 'Increase on Default Product Price', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'             => __( 'Fixed Pre-order Price', 'woocommerce-jetpack' ),
		'name'              => 'wcj_product_preorder_fixed_price',
		'type'              => 'number',
		'default'           => '',
		'custom_attributes' => 'step="0.01"',
		'tooltip'           => __( 'Leave empty if you do not use the Fixed Pre-order Price Pricing Type.', 'woocommerce-jetpack' ),
	),
	array(
		'title'             => __( 'Price Adjustment Discount/Increase (%)', 'woocommerce-jetpack' ),
		'name'              => 'wcj_product_preorder_price_adjustment',
		'type'              => 'number',
		'default'           => '',
		'custom_attributes' => 'step="0.01"',
		'tooltip'           => __( 'Leave empty if you do not use the Discount/Increase Pricing Type.', 'woocommerce-jetpack' ),
	),
	array(
		'title'   => __( 'Maximum Quantity at Time', 'woocommerce-jetpack' ),
		'name'    => 'wcj_product_preorder_max_qty',
		'type'    => 'number',
		'default' => '',
	),
	array(
		'title'   => __( 'Custom Message', 'woocommerce-jetpack' ),
		'name'    => 'wcj_product_preorder_message',
		'type'    => 'textarea',
		'default' => '',
	),
);

return $options;
