<?php
/**
 * Booster for WooCommerce Settings - Custom CSS
 *
 * @version 7.0.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

return array(
	array(
		'id'   => 'custom_css_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'custom_css_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'custom_css_general_options_tab' => __( 'General options', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'custom_css_general_options_tab',
		'type' => 'tab_start',
	),
	array(
		'title'   => __( 'Code Position', 'woocommerce-jetpack' ),
		'id'      => 'wcj_custom_css_hook',
		'default' => 'head',
		'type'    => 'select',
		'options' => array(
			'head'   => __( 'Header', 'woocommerce-jetpack' ),
			'footer' => __( 'Footer', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'   => __( 'Custom CSS - Front end (Customers)', 'woocommerce-jetpack' ),
		'id'      => 'wcj_general_custom_css',
		'default' => '',
		'type'    => 'textarea',
		'css'     => 'width:100%;min-height:300px;font-family:monospace;',
		/* translators: %s: translators Added */
		'desc'    => sprintf( __( 'Without the %s tag.', 'woocommerce-jetpack' ), '<code>' . esc_html( '<style></style>' ) . '</code>' ),
	),
	array(
		'title'   => __( 'Custom CSS - Back end (Admin)', 'woocommerce-jetpack' ),
		'id'      => 'wcj_general_custom_admin_css',
		'default' => '',
		'type'    => 'textarea',
		'css'     => 'width:100%;min-height:300px;font-family:monospace;',
		/* translators: %s: translators Added */
		'desc'    => sprintf( __( 'Without the %s tag.', 'woocommerce-jetpack' ), '<code>' . esc_html( '<style></style>' ) . '</code>' ),
	),
	array(
		'title'    => __( 'Custom CSS on per Product Basis', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Set product specific CSS to be loaded only on specific product\'s single page.', 'woocommerce-jetpack' ) .
			' ' . __( 'This will add meta box to each product\'s edit page.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_custom_css_per_product',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'   => __( 'Custom CSS on per Product Basis - Default Field Value', 'woocommerce-jetpack' ),
		'id'      => 'wcj_custom_css_per_product_default_value',
		'default' => '',
		'type'    => 'textarea',
		'css'     => 'width:100%;min-height:100px;font-family:monospace;',
	),
	array(
		'id'   => 'wcj_custom_css_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'custom_css_general_options_tab',
		'type' => 'tab_end',
	),
);
