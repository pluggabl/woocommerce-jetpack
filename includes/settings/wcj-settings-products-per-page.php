<?php
/**
 * Booster for WooCommerce - Settings - Products per Page
 *
 * @version 2.9.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

return array(
	array(
		'title'    => __( 'Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_products_per_page_options',
	),
	array(
		'title'    => __( 'Select Options', 'woocommerce-jetpack' ),
		'desc'     => __( 'Name|Number; one per line; -1 for all products', 'woocommerce-jetpack' ),
		'id'       => 'wcj_products_per_page_select_options',
		'default'  => '10|10' . PHP_EOL . '25|25' . PHP_EOL . '50|50' . PHP_EOL . '100|100' . PHP_EOL . 'All|-1',
		'type'     => 'textarea',
		'css'      => 'height:200px;',
		'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
		'desc_tip' => apply_filters( 'booster_message', '', 'desc_no_link' ),
	),
	array(
		'title'    => __( 'Default', 'woocommerce-jetpack' ),
		'id'       => 'wcj_products_per_page_default',
		'default'  => 10,
		'type'     => 'number',
		'custom_attributes' => array( 'min' => -1 ),
	),
	array(
		'title'    => __( 'Position', 'woocommerce-jetpack' ),
		'id'       => 'wcj_products_per_page_position',
		'default'  => array( 'woocommerce_before_shop_loop' ),
		'type'     => 'multiselect',
		'class'    => 'chosen_select',
		'options'  => array(
			'woocommerce_before_shop_loop' => __( 'Before shop loop', 'woocommerce-jetpack' ),
			'woocommerce_after_shop_loop'  => __( 'After shop loop', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'    => __( 'Position Priority', 'woocommerce-jetpack' ),
		'id'       => 'wcj_products_per_page_position_priority',
		'default'  => 40,
		'type'     => 'number',
		'custom_attributes' => array( 'min' => 0 ),
	),
	array(
		'title'    => __( 'Template - Before Form', 'woocommerce-jetpack' ),
		'id'       => 'wcj_products_per_page_text_before',
		'default'  => '<div class="clearfix"></div><div>',
		'type'     => 'custom_textarea',
		'css'      => 'width:66%;min-width:300px;',
	),
	array(
		'title'    => __( 'Template - Form', 'woocommerce-jetpack' ),
		'id'       => 'wcj_products_per_page_text',
		'default'  => __( 'Products <strong>%from% - %to%</strong> from <strong>%total%</strong>. Products on page %select_form%', 'woocommerce-jetpack' ),
		'type'     => 'custom_textarea',
		'css'      => 'width:66%;min-width:300px;',
	),
	array(
		'title'    => __( 'Template - After Form', 'woocommerce-jetpack' ),
		'id'       => 'wcj_products_per_page_text_after',
		'default'  => '</div>',
		'type'     => 'custom_textarea',
		'css'      => 'width:66%;min-width:300px;',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_products_per_page_options',
	),
);
