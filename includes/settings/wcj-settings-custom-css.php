<?php
/**
 * Booster for WooCommerce Settings - Custom CSS
 *
 * @version 2.8.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

return array(
	array(
		'title'    => __( 'Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_custom_css_options',
	),
	array(
		'title'    => __( 'Custom CSS - Front end (Customers)', 'woocommerce-jetpack' ),
		'id'       => 'wcj_general_custom_css',
		'default'  => '',
		'type'     => 'custom_textarea',
		'css'      => 'width:66%;min-width:300px;min-height:300px;',
	),
	array(
		'title'    => __( 'Custom CSS - Back end (Admin)', 'woocommerce-jetpack' ),
		'id'       => 'wcj_general_custom_admin_css',
		'default'  => '',
		'type'     => 'custom_textarea',
		'css'      => 'width:66%;min-width:300px;min-height:300px;',
	),
	array(
		'title'    => __( 'Custom CSS on per Product Basis', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Set product specific CSS to be loaded only on specific product\'s single page.', 'woocommerce-jetpack' ) .
			' ' . __( 'This will add meta box to each product\'s edit page', 'woocommerce-jetpack' ),
		'id'       => 'wcj_custom_css_per_product',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Custom CSS on per Product Basis - Default Field Value', 'woocommerce-jetpack' ),
		'id'       => 'wcj_custom_css_per_product_default_value',
		'default'  => '',
		'type'     => 'custom_textarea',
		'css'      => 'width:66%;min-width:300px;min-height:100px;',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_custom_css_options',
	),
);
