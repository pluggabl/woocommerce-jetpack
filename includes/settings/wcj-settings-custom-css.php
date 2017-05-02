<?php
/**
 * WooCommerce Jetpack Settings - Custom CSS
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
		'type'     => 'sectionend',
		'id'       => 'wcj_custom_css_options',
	),
);
