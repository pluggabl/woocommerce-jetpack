<?php
/**
 * Booster for WooCommerce - Settings - Add to Cart Button Visibility
 *
 * @version 7.0.0
 * @since   3.3.0
 * @author  Pluggabl LLC.
 * @todo    "Per Tag"
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$products_cats = wcj_get_terms( 'product_cat' );

return array(
	array(
		'id'   => 'wcj_cart_button_visibility_general_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'wcj_cart_button_visibility_general_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'wcj_cart_button_visibility_all_product_tab'  => __( 'All Product', 'woocommerce-jetpack' ),
			'wcj_cart_button_visibility_per_product_tab'  => __( 'Per Product', 'woocommerce-jetpack' ),
			'wcj_cart_button_visibility_per_category_tab' => __( 'Per Category', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'wcj_cart_button_visibility_all_product_tab',
		'type' => 'tab_start',
	),
	array(
		'title'   => __( 'All Products', 'woocommerce-jetpack' ),
		'desc'    => '<strong>' . __( 'Enable section', 'woocommerce-jetpack' ) . '</strong>',
		'id'      => 'wcj_add_to_cart_button_global_enabled',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'title'   => __( 'Disable Buttons on Category/Archives Pages', 'woocommerce-jetpack' ),
		'desc'    => __( 'Disable', 'woocommerce-jetpack' ),
		'id'      => 'wcj_add_to_cart_button_disable_archives',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'desc'     => __( 'Advanced', 'woocommerce-jetpack' ) . ': ' . __( 'Method', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Method for disabling the buttons. Try changing if buttons are not being disabled (may happen with some themes).', 'woocommerce-jetpack' ),
		'id'       => 'wcj_add_to_cart_button_disable_archives_method',
		'default'  => 'remove_action',
		'type'     => 'select',
		'options'  => array(
			'remove_action' => __( 'Remove action', 'woocommerce-jetpack' ),
			'add_filter'    => __( 'Add filter', 'woocommerce-jetpack' ),
		),
	),
	array(
		'desc'     => __( 'Content', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Content to replace with on archives (can be empty). You can use HTML and/or shortcodes here.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_add_to_cart_button_archives_content',
		'default'  => '',
		'type'     => 'textarea',
		'css'      => 'width:100%',
	),
	array(
		'title'   => __( 'Disable Buttons on Single Product Pages', 'woocommerce-jetpack' ),
		'desc'    => __( 'Disable', 'woocommerce-jetpack' ),
		'id'      => 'wcj_add_to_cart_button_disable_single',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'desc'     => __( 'Advanced', 'woocommerce-jetpack' ) . ': ' . __( 'Method', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Method for disabling the buttons. Try changing if buttons are not being disabled (may happen with some themes).', 'woocommerce-jetpack' ),
		'id'       => 'wcj_add_to_cart_button_disable_single_method',
		'default'  => 'remove_action',
		'type'     => 'select',
		'options'  => array(
			'remove_action' => __( 'Remove action', 'woocommerce-jetpack' ),
			'add_action'    => __( 'Add action', 'woocommerce-jetpack' ),
		),
	),
	array(
		'desc'     => __( 'Content', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Content to replace with on single product pages (can be empty). You can use HTML and/or shortcodes here.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_add_to_cart_button_single_content',
		'default'  => '',
		'type'     => 'textarea',
		'css'      => 'width:100%',
	),

	array(
		'id'   => 'wcj_add_to_cart_button_visibility_global_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'wcj_cart_button_visibility_all_product_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'wcj_cart_button_visibility_per_product_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Per Product', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_add_to_cart_button_visibility_per_product_options',
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
		'id'   => 'wcj_add_to_cart_button_visibility_per_product_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'wcj_cart_button_visibility_per_product_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'wcj_cart_button_visibility_per_category_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Per Category', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_add_to_cart_button_visibility_per_category_options',
	),
	array(
		'title'   => __( 'Per Category', 'woocommerce-jetpack' ),
		'desc'    => '<strong>' . __( 'Enable section', 'woocommerce-jetpack' ) . '</strong>',
		'id'      => 'wcj_add_to_cart_button_per_category_enabled',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'title'   => __( 'Disable Buttons on Category/Archives Pages', 'woocommerce-jetpack' ),
		'id'      => 'wcj_add_to_cart_button_per_category_disable_loop',
		'default' => '',
		'type'    => 'multiselect',
		'class'   => 'chosen_select',
		'options' => $products_cats,
	),
	array(
		'desc'     => __( 'Content', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Content to replace with on archives (can be empty). You can use HTML and/or shortcodes here.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_add_to_cart_button_per_category_content_loop',
		'default'  => '',
		'type'     => 'textarea',
		'css'      => 'width:100%',
	),
	array(
		'title'   => __( 'Disable Buttons on Single Product Pages', 'woocommerce-jetpack' ),
		'id'      => 'wcj_add_to_cart_button_per_category_disable_single',
		'default' => '',
		'type'    => 'multiselect',
		'class'   => 'chosen_select',
		'options' => $products_cats,
	),
	array(
		'desc'     => __( 'Content', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Content to replace with on single product pages (can be empty). You can use HTML and/or shortcodes here.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_add_to_cart_button_per_category_content_single',
		'default'  => '',
		'type'     => 'textarea',
		'css'      => 'width:100%',
	),
	array(
		'id'   => 'wcj_add_to_cart_button_visibility_per_category_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'wcj_cart_button_visibility_per_category_tab',
		'type' => 'tab_end',
	),
);
