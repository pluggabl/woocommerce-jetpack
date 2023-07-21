<?php
/**
 * Booster for WooCommerce - Settings - Shipping Methods by Min/Max Order Quantity
 *
 * @version 7.0.0-dev
 * @since   4.3.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$use_shipping_instances = ( 'yes' === wcj_get_option( 'wcj_shipping_by_order_qty_use_shipping_instance', 'no' ) );
$settings               = array(
	array(
		'id'   => 'shipping_by_order_qty_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'shipping_by_order_qty_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'shipping_by_order_qty_general_options_tab' => __( 'General Options', 'woocommerce-jetpack' ),
			'shipping_by_order_qty_order_quantity_tab'  => __( 'Order Quantity', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'shipping_by_order_qty_general_options_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'General Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_shipping_by_order_qty_general_options',
	),
	array(
		'title'    => __( 'Use Shipping Instances', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Enable this if you want to use shipping methods instances instead of shipping methods.', 'woocommerce-jetpack' ) . ' ' .
			__( 'Save changes after enabling this option.', 'woocommerce-jetpack' ),
		'type'     => 'checkbox',
		'id'       => 'wcj_shipping_by_order_qty_use_shipping_instance',
		'default'  => 'no',
	),
	array(
		'id'   => 'wcj_shipping_by_order_qty_general_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'shipping_by_order_qty_general_options_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'shipping_by_order_qty_order_quantity_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Shipping Methods by Min/Max Order Quantity', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'desc'  => __( 'Set to zero to disable.', 'woocommerce-jetpack' ),
		'id'    => 'wcj_shipping_by_order_qty_options',
	),
);
$shipping_methods       = ( $use_shipping_instances ? wcj_get_shipping_methods_instances( true ) : WC()->shipping()->load_shipping_methods() );
foreach ( $shipping_methods as $method ) {
	$method_id = ( $use_shipping_instances ? $method['shipping_method_id'] : $method->id );
	if ( ! in_array( $method_id, array( 'flat_rate', 'free_shipping' ), true ) ) {
		$custom_attributes = apply_filters( 'booster_message', '', 'disabled' );
		if ( '' === $custom_attributes ) {
			$custom_attributes = array();
		}
		$desc_tip = apply_filters( 'booster_message', '', 'desc_no_link' );
	} else {
		$custom_attributes = array();
		$desc_tip          = '';
	}
	$custom_attributes = array_merge( $custom_attributes, array( 'min' => 0 ) );
	$settings          = array_merge(
		$settings,
		array(
			array(
				'title'             => ( $use_shipping_instances ? $method['zone_name'] . ': ' . $method['shipping_method_title'] : $method->get_method_title() ),
				'desc_tip'          => $desc_tip,
				'desc'              => '<br>' . __( 'Minimum order quantity', 'woocommerce-jetpack' ),
				'id'                => 'wcj_shipping_by_order_qty_min' . ( $use_shipping_instances ? '_instance[' . $method['shipping_method_instance_id'] . ']' : '[' . $method->id . ']' ),
				'default'           => 0,
				'type'              => 'number',
				'custom_attributes' => $custom_attributes,
			),
			array(
				'desc_tip'          => $desc_tip,
				'desc'              => '<br>' . __( 'Maximum order quantity', 'woocommerce-jetpack' ),
				'id'                => 'wcj_shipping_by_order_qty_max' . ( $use_shipping_instances ? '_instance[' . $method['shipping_method_instance_id'] . ']' : '[' . $method->id . ']' ),
				'default'           => 0,
				'type'              => 'number',
				'custom_attributes' => $custom_attributes,
			),
		)
	);
}
$settings = array_merge(
	$settings,
	array(
		array(
			'id'   => 'wcj_shipping_by_order_qty_options',
			'type' => 'sectionend',
		),
		array(
			'id'   => 'shipping_by_order_qty_order_quantity_tab',
			'type' => 'tab_end',
		),
	)
);
return $settings;
