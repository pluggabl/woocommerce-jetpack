<?php
/**
 * Booster for WooCommerce - Settings Meta Box - Add to Cart Button Visibility
 *
 * @version 3.3.0
 * @since   3.3.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

return array(
	array(
		'name'       => 'wcj_add_to_cart_button_disable',
		'default'    => 'no',
		'type'       => 'select',
		'options'    => array(
			'yes' => __( 'Hide', 'woocommerce-jetpack' ),
			'no'  => __( 'Show', 'woocommerce-jetpack' ),
		),
		'title'      => __( 'Single Product Page', 'woocommerce-jetpack' ),
	),
	array(
		'name'       => 'wcj_add_to_cart_button_disable_content',
		'default'    => '',
		'type'       => 'textarea',
		'title'      => '',
		'css'        => 'width:100%;',
		'tooltip'    => __( 'Content to replace add to cart button on single product page.', 'woocommerce-jetpack' ) . ' ' .
			__( 'You can use HTML and/or shortcodes here.', 'woocommerce-jetpack' ),
	),
	array(
		'name'       => 'wcj_add_to_cart_button_loop_disable',
		'default'    => 'no',
		'type'       => 'select',
		'options'    => array(
			'yes' => __( 'Hide', 'woocommerce-jetpack' ),
			'no'  => __( 'Show', 'woocommerce-jetpack' ),
		),
		'title'      => __( 'Category/Archives', 'woocommerce-jetpack' ),
	),
	array(
		'name'       => 'wcj_add_to_cart_button_loop_disable_content',
		'default'    => '',
		'type'       => 'textarea',
		'title'      => '',
		'css'        => 'width:100%;',
		'tooltip'    => __( 'Content to replace add to cart button on category/archives.', 'woocommerce-jetpack' ) . ' ' .
			__( 'You can use HTML and/or shortcodes here.', 'woocommerce-jetpack' ),
	),
);
