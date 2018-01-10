<?php
/**
 * Booster for WooCommerce - Settings - Add to Cart Button Visibility
 *
 * @version 3.3.0
 * @since   3.3.0
 * @author  Algoritmika Ltd.
 * @todo    "Per Tag"
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$products_cats = wcj_get_terms( 'product_cat' );

return array(
	array(
		'title'    => __( 'All Products', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_add_to_cart_button_visibility_global_options',
	),
	array(
		'title'    => __( 'All Products', 'woocommerce-jetpack' ),
		'desc'     => '<strong>' . __( 'Enable section', 'woocommerce-jetpack' ) . '</strong>',
		'id'       => 'wcj_add_to_cart_button_global_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Disable Buttons on Category/Archives Pages', 'woocommerce-jetpack' ),
		'desc'     => __( 'Disable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_add_to_cart_button_disable_archives',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Disable Buttons on Single Product Pages', 'woocommerce-jetpack' ),
		'desc'     => __( 'Disable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_add_to_cart_button_disable_single',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_add_to_cart_button_visibility_global_options',
	),
	array(
		'title'    => __( 'Per Product', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_add_to_cart_button_visibility_per_product_options',
	),
	array(
		'title'    => __( 'Per Product', 'woocommerce-jetpack' ),
		'desc'     => '<strong>' . __( 'Enable section', 'woocommerce-jetpack' ) . '</strong>',
		'desc_tip' => __( 'This will add meta box to each product\'s edit page', 'woocommerce-jetpack' ),
		'id'       => 'wcj_add_to_cart_button_per_product_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_add_to_cart_button_visibility_per_product_options',
	),
	array(
		'title'    => __( 'Per Category', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_add_to_cart_button_visibility_per_category_options',
	),
	array(
		'title'    => __( 'Per Category', 'woocommerce-jetpack' ),
		'desc'     => '<strong>' . __( 'Enable section', 'woocommerce-jetpack' ) . '</strong>',
		'id'       => 'wcj_add_to_cart_button_per_category_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Disable Buttons on Category/Archives Pages', 'woocommerce-jetpack' ),
		'id'       => 'wcj_add_to_cart_button_per_category_disable_loop',
		'default'  => '',
		'type'     => 'multiselect',
		'class'    => 'chosen_select',
		'options'  => $products_cats,
	),
	array(
		'desc'     => __( 'Content', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Content to replace with on archives (can be empty). You can use HTML and/or shortcodes here.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_add_to_cart_button_per_category_content_loop',
		'default'  => '',
		'type'     => 'custom_textarea',
		'css'      => 'width:100%',
	),
	array(
		'title'    => __( 'Disable Buttons on Single Product Pages', 'woocommerce-jetpack' ),
		'id'       => 'wcj_add_to_cart_button_per_category_disable_single',
		'default'  => '',
		'type'     => 'multiselect',
		'class'    => 'chosen_select',
		'options'  => $products_cats,
	),
	array(
		'desc'     => __( 'Content', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Content to replace with on single product pages (can be empty). You can use HTML and/or shortcodes here.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_add_to_cart_button_per_category_content_single',
		'default'  => '',
		'type'     => 'custom_textarea',
		'css'      => 'width:100%',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_add_to_cart_button_visibility_per_category_options',
	),
);
