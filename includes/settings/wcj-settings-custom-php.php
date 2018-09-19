<?php
/**
 * Booster for WooCommerce Settings - Custom PHP
 *
 * @version 3.9.2
 * @since   3.9.2
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

return array(
	array(
		'title'    => __( 'Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_custom_php_options',
	),
	array(
		'title'    => __( 'Custom PHP', 'woocommerce-jetpack' ),
		'id'       => 'wcj_custom_php',
		'default'  => '',
		'type'     => 'textarea',
		'css'      => 'width:100%;height:500px;font-family:monospace;',
		'wcj_raw'  => true,
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_custom_php_options',
	),
);
