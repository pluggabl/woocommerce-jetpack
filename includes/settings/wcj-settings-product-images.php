<?php
/**
 * Booster for WooCommerce - Settings - Product Images
 *
 * @version 5.4.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
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
	array(
		'title'    => __( 'Placeholder Image', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_product_images_placeholder_options',
	),
	array(
		'title'    => __( 'Custom Placeholder Image URL', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Leave blank to use the default placeholder image.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_images_placeholder_src',
		'default'  => '',
		'type'     => 'text',
		'css'      => 'width:100%',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_product_images_placeholder_options',
	),
	array(
		'title'    => __( 'Callbacks', 'woocommerce-jetpack' ),
		'desc'     => __( 'Callback functions used by WooCommerce and the current theme in order to customize images and thumbnails', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_product_images_cb',
	),
	array(
		'title'    => __( 'Loop Thumbnail', 'woocommerce-jetpack' ),
		'desc'     => __( 'Used on hook <strong>woocommerce_before_shop_loop_item_title</strong>', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_images_cb_loop_product_thumbnail',
		'default'  => 'woocommerce_template_loop_product_thumbnail',
		'type'     => 'text',
	),
	array(
		'title'    => __( 'Loop Thumbnail Priority', 'woocommerce-jetpack' ),
		'desc'     => __( 'Priority for Loop Thumbnail. If you want to change the priority you can set Greater value for high priority & Lower value for low priority. Set to zero to use default priority.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_images_cb_loop_product_thumbnail_priority',
		'default'  => 10,
		'type'     => 'text',
	),
	array(
		'title'    => __( 'Show Images', 'woocommerce-jetpack' ),
		'desc'     => __( 'Used on hook <strong>woocommerce_before_single_product_summary</strong>', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_images_cb_show_product_images',
		'default'  => 'woocommerce_show_product_images',
		'type'     => 'text',
	),
	array(
		'title'    => __( 'Show Images Priority', 'woocommerce-jetpack' ),
		'desc'     => __( 'Priority for Show Images. If you want to change the priority you can set Greater value for high priority & Lower value for low priority. Set to zero to use default priority.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_images_cb_show_product_images_priority',
		'default'  => 20,
		'type'     => 'text',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_product_images_cb',
	),
);
