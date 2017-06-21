<?php
/**
 * Booster for WooCommerce Settings - More Button Labels
 *
 * @version 2.9.0
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
		'title'    => __( 'Enable Section', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_images_and_thumbnails_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
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
		'css'      => 'width:66%; min-width:300px;',
	),
	array(
		'title'    => __( 'Replace Thumbnails on Single', 'woocommerce-jetpack' ),
		'desc'     => __( 'Replace thumbnails on single product page with custom HTML. Leave blank to disable.', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'You can use shortcodes here.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_images_thumbnails_custom_on_single',
		'default'  => '',
		'type'     => 'textarea',
		'css'      => 'width:66%; min-width:300px;',
	),
	array(
		'title'    => __( 'Replace Image on Archive', 'woocommerce-jetpack' ),
		'desc'     => __( 'Replace image on archive pages with custom HTML. Leave blank to disable.', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'You can use shortcodes here.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_images_custom_on_archives',
		'default'  => '',
		'type'     => 'textarea',
		'css'      => 'width:66%; min-width:300px;',
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
	array(
		'title'    => __( 'Product Images Sale Flash', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_product_images_sale_flash_options',
	),
	array(
		'title'    => __( 'Enable Section', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_images_sale_flash_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'HTML', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_images_sale_flash_html',
		'default'  => '<span class="onsale">' . __( 'Sale!', 'woocommerce' ) . '</span>',
		'type'     => 'textarea',
		'css'      => 'width:300px;height:100px;',
	),
	array(
		'title'    => __( 'Hide Everywhere', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_images_sale_flash_hide_everywhere',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Hide on Archives (Categories) Only', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_images_sale_flash_hide_on_archives',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Hide on Single Page Only', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_images_sale_flash_hide_on_single',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_product_images_sale_flash_options',
	),
);
