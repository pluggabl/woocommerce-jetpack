<?php
/**
 * Booster for WooCommerce - Settings Meta Box - Product Add To Cart
 *
 * @version 3.3.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$options = array();
if ( 'yes' === apply_filters( 'booster_option', 'no', wcj_get_option( 'wcj_add_to_cart_redirect_per_product_enabled', 'no' ) ) ) {
	$options = array_merge( $options, array(
		array(
			'name'       => 'wcj_add_to_cart_redirect_enabled',
			'default'    => 'no',
			'type'       => 'select',
			'options'    => array(
				'yes' => __( 'Yes', 'woocommerce-jetpack' ),
				'no'  => __( 'No', 'woocommerce-jetpack' ),
			),
			'title'      => __( 'Add to Cart Local Redirect', 'woocommerce-jetpack' ),
		),
		array(
			'name'       => 'wcj_add_to_cart_redirect_url',
			'tooltip'    => __( 'Redirect URL. Leave empty to redirect to checkout page (skipping the cart page).', 'woocommerce-jetpack' ),
			'default'    => '',
			'type'       => 'text',
			'title'      => __( 'Add to Cart Local Redirect URL', 'woocommerce-jetpack' ),
			'css'        => 'width:100%;',
		),
	) );
}
if ( 'per_product' === wcj_get_option( 'wcj_add_to_cart_on_visit_enabled', 'no' ) ) {
	$options = array_merge( $options, array(
		array(
			'name'       => 'wcj_add_to_cart_on_visit_enabled',
			'default'    => 'no',
			'type'       => 'select',
			'options'    => array(
				'yes' => __( 'Yes', 'woocommerce-jetpack' ),
				'no'  => __( 'No', 'woocommerce-jetpack' ),
			),
			'title'      => __( 'Add to Cart on Visit', 'woocommerce-jetpack' ),
		),
	) );
}
if ( 'yes' === wcj_get_option( 'wcj_add_to_cart_button_custom_loop_url_per_product_enabled', 'no' ) ) {
	$options = array_merge( $options, array(
		array(
			'name'       => 'wcj_add_to_cart_button_loop_custom_url',
			'default'    => '',
			'type'       => 'text',
			'title'      => __( 'Custom Add to Cart Button URL (Category/Archives)', 'woocommerce-jetpack' ),
		),
	) );
}
if ( 'yes' === wcj_get_option( 'wcj_add_to_cart_button_ajax_per_product_enabled', 'no' ) ) {
	$options = array_merge( $options, array(
		array(
			'name'       => 'wcj_add_to_cart_button_ajax_disable',
			'default'    => 'as_shop_default',
			'type'       => 'select',
			'options'    => array(
				'as_shop_default' => __( 'As shop default (no changes)', 'woocommerce-jetpack' ),
				'yes'             => __( 'Disable', 'woocommerce-jetpack' ),
				'no'              => __( 'Enable', 'woocommerce-jetpack' ),
			),
			'title'      => __( 'Disable Add to Cart Button AJAX', 'woocommerce-jetpack' ),
		),
	) );
}
return $options;
