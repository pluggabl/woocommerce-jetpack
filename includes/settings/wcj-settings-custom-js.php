<?php
/**
 * Booster for WooCommerce Settings - Custom JS
 *
 * @version 5.4.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

return array(
	array(
		'title'    => __( 'Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_custom_js_options',
	),
	array(
		'title'    => __( 'Code Position', 'woocommerce-jetpack' ),
		'id'       => 'wcj_custom_js_hook',
		'default'  => 'head',
		'type'     => 'select',
		'options'  => array(
			'head'   => __( 'Header', 'woocommerce-jetpack' ),
			'footer' => __( 'Footer', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'    => __( 'Custom JS - Front end (Customers)', 'woocommerce-jetpack' ),
		'id'       => 'wcj_custom_js_frontend',
		'default'  => '',
		'type'     => 'custom_textarea',
		'css'      => 'width:100%;min-height:300px;font-family:monospace;',
		'desc'     => sprintf( __( 'Without the %s tag.', 'woocommerce-jetpack' ), '<code>' . esc_html( '<script></script>' ) . '</code>' )
	),
	array(
		'title'    => __( 'Custom JS - Back end (Admin)', 'woocommerce-jetpack' ),
		'id'       => 'wcj_custom_js_backend',
		'default'  => '',
		'type'     => 'custom_textarea',
		'css'      => 'width:100%;min-height:300px;font-family:monospace;',
		'desc'     => sprintf( __( 'Without the %s tag.', 'woocommerce-jetpack' ), '<code>' . esc_html( '<script></script>' ) . '</code>' )
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_custom_js_options',
	),
);
