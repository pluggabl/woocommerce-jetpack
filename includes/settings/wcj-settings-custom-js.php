<?php
/**
 * Booster for WooCommerce Settings - Custom JS
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
		'id'   => 'custom_js_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'custom_js_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'custom_js_general_options_tab' => __( 'General Options', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'custom_js_general_options_tab',
		'type' => 'tab_start',
	),
	array(
		'title'   => __( 'Code Position', 'woocommerce-jetpack' ),
		'id'      => 'wcj_custom_js_hook',
		'default' => 'head',
		'type'    => 'select',
		'options' => array(
			'head'   => __( 'Header', 'woocommerce-jetpack' ),
			'footer' => __( 'Footer', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'   => __( 'Custom JS - Front end (Customers)', 'woocommerce-jetpack' ),
		'id'      => 'wcj_custom_js_frontend',
		'default' => '',
		'type'    => 'textarea',
		'css'     => 'width:100%;min-height:300px;font-family:monospace;',
		/* translators: %s: translators Added */
		'desc'    => sprintf( __( 'Without the %s tag.', 'woocommerce-jetpack' ), '<code>' . esc_html( '<script></script>' ) . '</code>' ),
	),
	array(
		'title'   => __( 'Custom JS - Back end (Admin)', 'woocommerce-jetpack' ),
		'id'      => 'wcj_custom_js_backend',
		'default' => '',
		'type'    => 'textarea',
		'css'     => 'width:100%;min-height:300px;font-family:monospace;',
		/* translators: %s: translators Added */
		'desc'    => sprintf( __( 'Without the %s tag.', 'woocommerce-jetpack' ), '<code>' . esc_html( '<script></script>' ) . '</code>' ),
	),
	array(
		'id'   => 'wcj_custom_js_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'custom_js_general_options_tab',
		'type' => 'tab_end',
	),
);
