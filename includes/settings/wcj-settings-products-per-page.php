<?php
/**
 * Booster for WooCommerce - Settings - Products per Page
 *
 * @version 5.6.7
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

return array(
	array(
		'title' => __( 'Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_products_per_page_options',
	),
	array(
		'title'             => __( 'Select Options', 'woocommerce-jetpack' ),
		'desc'              => __( '<code>Name|Number</code>; one per line; <code>-1</code> for all products;', 'woocommerce-jetpack' ),
		'id'                => 'wcj_products_per_page_select_options',
		'default'           => implode( PHP_EOL, array( '10|10', '25|25', '50|50', '100|100', 'All|-1' ) ),
		'type'              => 'textarea',
		'css'               => 'height:200px;',
		'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
		'desc_tip'          => apply_filters( 'booster_message', '', 'desc_no_link' ),
	),
	array(
		'title'             => __( 'Default', 'woocommerce-jetpack' ),
		'id'                => 'wcj_products_per_page_default',
		'default'           => 10,
		'type'              => 'number',
		'custom_attributes' => array( 'min' => -1 ),
	),
	array(
		'title'   => __( 'Position', 'woocommerce-jetpack' ),
		'id'      => 'wcj_products_per_page_position',
		'default' => array( 'woocommerce_before_shop_loop' ),
		'type'    => 'multiselect',
		'class'   => 'chosen_select',
		'options' => array(
			'woocommerce_before_shop_loop' => __( 'Before shop loop', 'woocommerce-jetpack' ),
			'woocommerce_after_shop_loop'  => __( 'After shop loop', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'             => __( 'Position Priority', 'woocommerce-jetpack' ),
		'id'                => 'wcj_products_per_page_position_priority',
		'default'           => 40,
		'type'              => 'number',
		'custom_attributes' => array( 'min' => 0 ),
	),
	array(
		'title'   => __( 'Template - Before Form', 'woocommerce-jetpack' ),
		'id'      => 'wcj_products_per_page_text_before',
		'default' => '<div class="clearfix"></div><div>',
		'type'    => 'custom_textarea',
		'css'     => 'width:100%',
	),
	array(
		'title'   => __( 'Template - Form', 'woocommerce-jetpack' ),
		'id'      => 'wcj_products_per_page_text',
		/* translators: %s: translators Added */
		'default' => sprintf( __( 'Products <strong>%1$s - %2$s</strong> from <strong>%3$s</strong>. Products on page %4$s', 'woocommerce-jetpack' ), '%from%', '%to%', '%total%', '%select_form%' ) . '<br>',
		'type'    => 'custom_textarea',
		'css'     => 'width:100%',
	),
	array(
		'title'   => __( 'Template - After Form', 'woocommerce-jetpack' ),
		'id'      => 'wcj_products_per_page_text_after',
		'default' => '</div>',
		'type'    => 'custom_textarea',
		'css'     => 'width:100%',
	),
	array(
		'title'   => __( 'Form Method', 'woocommerce-jetpack' ),
		'id'      => 'wcj_products_per_page_form_method',
		'default' => 'post',
		'type'    => 'select',
		'options' => array(
			'post' => __( 'POST', 'woocommerce-jetpack' ),
			'get'  => __( 'GET', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'   => __( 'Saving Method', 'woocommerce-jetpack' ),
		'id'      => 'wcj_products_per_page_saving_method',
		'default' => 'cookie',
		'type'    => 'select',
		'options' => array(
			'cookie'  => __( 'Cookie', 'woocommerce-jetpack' ),
			'session' => __( 'Session', 'woocommerce-jetpack' ),
		),
	),
	array(
		'type' => 'sectionend',
		'id'   => 'wcj_products_per_page_options',
	),
);
