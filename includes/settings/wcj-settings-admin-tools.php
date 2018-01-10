<?php
/**
 * Booster for WooCommerce - Settings - Admin Tools
 *
 * @version 3.3.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

return array(
	array(
		'title'    => __( 'Admin Tools Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_admin_tools_module_options',
	),
	array(
		'title'    => __( 'Log', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_logging_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'WooCommerce Log', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_wc_logging_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Debug', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_debuging_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'PHP Memory Limit', 'woocommerce-jetpack' ),
		'desc'     => __( 'megabytes.', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Set zero to disable.', 'woocommerce-jetpack' ) . $this->current_php_memory_limit,
		'id'       => 'wcj_admin_tools_php_memory_limit',
		'default'  => 0,
		'type'     => 'number',
		'custom_attributes' => array( 'min' => 0 ),
	),
	array(
		'title'    => __( 'PHP Time Limit', 'woocommerce-jetpack' ),
		'desc'     => __( 'seconds.', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Set zero to disable.', 'woocommerce-jetpack' ) . $this->current_php_time_limit,
		'id'       => 'wcj_admin_tools_php_time_limit',
		'default'  => 0,
		'type'     => 'number',
		'custom_attributes' => array( 'min' => 0 ),
	),
	/*
	array(
		'title'    => __( 'Custom Shortcode', 'woocommerce-jetpack' ),
		'id'       => 'wcj_custom_shortcode_1',
		'default'  => '',
		'type'     => 'textarea',
	),
	*/
	array(
		'title'    => __( 'System Info', 'woocommerce-jetpack' ),
		'id'       => 'wcj_admin_tools_system_info',
		'default'  => '',
		'type'     => 'custom_link',
		'link'     => '<pre>' . wcj_get_table_html( $this->get_system_info_table_array(), array( 'columns_styles' => array( 'padding:0;', 'padding:0;' ), 'table_heading_type' => 'vertical' ) ) . '</pre>',
	),
	array(
		'title'    => __( 'Show Order Meta', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_admin_tools_show_order_meta_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Show Product Meta', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_admin_tools_show_product_meta_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Show Variable Product Pricing Table', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_admin_tools_variable_product_pricing_table_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_admin_tools_module_options',
	),
);
