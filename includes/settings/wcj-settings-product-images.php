<?php
/**
 * Booster for WooCommerce - Settings - Product Images
 *
 * @version 3.2.4
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

return array(
	array(
		'title'    => __( 'Product Image and Thumbnails', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_product_images_and_thumbnails_options',
	),
	array(
		'title'    => __( 'Image and Thumbnails on Single', 'woocommerce-jetpack' ),
		'desc'     => __( 'Hide', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_images_and_thumbnails_hide_on_single',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Image on Single', 'woocommerce-jetpack' ),
		'desc'     => __( 'Hide', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_images_hide_on_single',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Thumbnails on Single', 'woocommerce-jetpack' ),
		'desc'     => __( 'Hide', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_images_thumbnails_hide_on_single',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Image on Archives', 'woocommerce-jetpack' ),
		'desc'     => __( 'Hide', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_images_hide_on_archive',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Replace Image on Single', 'woocommerce-jetpack' ),
		'desc'     => __( 'Replace image on single product page with custom HTML. Leave blank to disable.', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'You can use shortcodes here.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_images_custom_on_single',
		'default'  => '',
		'type'     => 'textarea',
		'css'      => 'width:100%;',
	),
	array(
		'title'    => __( 'Replace Thumbnails on Single', 'woocommerce-jetpack' ),
		'desc'     => __( 'Replace thumbnails on single product page with custom HTML. Leave blank to disable.', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'You can use shortcodes here.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_images_thumbnails_custom_on_single',
		'default'  => '',
		'type'     => 'textarea',
		'css'      => 'width:100%;',
	),
	array(
		'title'    => __( 'Replace Image on Archive', 'woocommerce-jetpack' ),
		'desc'     => __( 'Replace image on archive pages with custom HTML. Leave blank to disable.', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'You can use shortcodes here.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_images_custom_on_archives',
		'default'  => '',
		'type'     => 'textarea',
		'css'      => 'width:100%;',
	),
	array(
		'title'    => __( 'Single Product Thumbnails Columns', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_images_thumbnails_columns',
		'default'  => 3,
		'type'     => 'number',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_product_images_and_thumbnails_options',
	),
);
