<?php
/**
 * Booster for WooCommerce - Settings - Admin Tools
 *
 * @version 4.0.0
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
		'title'    => __( 'Show Booster Menus Only to Admin', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_admin_tools_show_menus_to_admin_only',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Suppress Admin Connect Notice', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => sprintf( __( 'Will remove "%s" admin notice.', 'woocommerce-jetpack' ),
			__( 'Connect your store to WooCommerce.com to receive extensions updates and support.', 'woocommerce-jetpack' ) ),
		'id'       => 'wcj_admin_tools_suppress_connect_notice',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Suppress Admin Notices', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Will remove admin notices (including the Connect notice).', 'woocommerce-jetpack' ),
		'id'       => 'wcj_admin_tools_suppress_admin_notices',
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
	array(
		'title'    => __( 'Debug Tools Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_debug_tools_options',
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
		'title'    => __( 'System Info', 'woocommerce-jetpack' ),
		'id'       => 'wcj_admin_tools_system_info',
		'default'  => '',
		'type'     => 'custom_link',
		'link'     => '<pre>' . wcj_get_table_html( $this->get_system_info_table_array(),
			array( 'columns_styles' => array( 'padding:0;', 'padding:0;' ), 'table_heading_type' => 'vertical' ) ) . '</pre>',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_debug_tools_options',
	),
);
