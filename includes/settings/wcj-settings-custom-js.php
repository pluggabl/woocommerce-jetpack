<?php
/**
 * WooCommerce Jetpack Settings - Custom JS
 *
 * @version 2.7.2
 * @since   2.7.2
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

return array(
	array(
		'title'    => __( 'Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_custom_js_options',
	),
	array(
		'title'    => __( 'Custom JS - Front end (Customers)', 'woocommerce-jetpack' ),
		'id'       => 'wcj_custom_js_frontend',
		'default'  => '',
		'type'     => 'custom_textarea',
		'css'      => 'width:66%;min-width:300px;min-height:300px;font-family:monospace;',
	),
	array(
		'title'    => __( 'Custom JS - Back end (Admin)', 'woocommerce-jetpack' ),
		'id'       => 'wcj_custom_js_backend',
		'default'  => '',
		'type'     => 'custom_textarea',
		'css'      => 'width:66%;min-width:300px;min-height:300px;font-family:monospace;',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_custom_js_options',
	),
);
