<?php
/**
 * Booster for WooCommerce - Settings Meta Box - Product Images
 *
 * @version 5.6.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/meta-boxs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

return array(
	array(
		'name'    => 'wcj_product_images_meta_custom_on_single',
		'default' => '',
		'type'    => 'textarea',
		'title'   => __( 'Replace image with custom HTML on single product page', 'woocommerce-jetpack' ),
		'tooltip' => __( 'You can use shortcodes here.', 'woocommerce-jetpack' ),
		'css'     => 'width:100%;height:75px;',
	),
	array(
		'name'    => 'wcj_product_images_meta_custom_on_archives',
		'default' => '',
		'type'    => 'textarea',
		'title'   => __( 'Replace image with custom HTML on archives', 'woocommerce-jetpack' ),
		'tooltip' => __( 'You can use shortcodes here.', 'woocommerce-jetpack' ),
		'css'     => 'width:100%;height:75px;',
	),
	array(
		'name'    => 'wcj_product_images_hide_image_on_single',
		'default' => 'no',
		'type'    => 'select',
		'options' => array(
			'yes' => __( 'Yes', 'woocommerce-jetpack' ),
			'no'  => __( 'No', 'woocommerce-jetpack' ),
		),
		'title'   => __( 'Hide Image on Single', 'woocommerce-jetpack' ),
	),
	array(
		'name'    => 'wcj_product_images_hide_thumb_on_single',
		'default' => 'no',
		'type'    => 'select',
		'options' => array(
			'yes' => __( 'Yes', 'woocommerce-jetpack' ),
			'no'  => __( 'No', 'woocommerce-jetpack' ),
		),
		'title'   => __( 'Hide Thumbnails on Single', 'woocommerce-jetpack' ),
	),
	array(
		'name'    => 'wcj_product_images_hide_image_on_archives',
		'default' => 'no',
		'type'    => 'select',
		'options' => array(
			'yes' => __( 'Yes', 'woocommerce-jetpack' ),
			'no'  => __( 'No', 'woocommerce-jetpack' ),
		),
		'title'   => __( 'Hide Image on Archives', 'woocommerce-jetpack' ),
	),
);
