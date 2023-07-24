<?php
/**
 * Booster for WooCommerce - Settings - Payment Gateways by Shipping
 *
 * @version 7.0.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @todo    (maybe) remove COD, Custom Booster Payment Gateways (and maybe other payment gateways) that already have `enable_for_methods` option
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$use_shipping_instance = ( 'yes' === wcj_get_option( 'wcj_payment_gateways_by_shipping_use_shipping_instance', 'no' ) );
$shipping_methods      = ( $use_shipping_instance ? wcj_get_shipping_methods_instances() : wcj_get_shipping_methods() );
$settings              = array(
	array(
		'id'   => 'wcj_payment_gateways_by_shipping_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'wcj_payment_gateways_by_shipping_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'wcj_payment_gateways_by_shipping_general_options_tab' => __( 'General Options', 'woocommerce-jetpack' ),
			'wcj_payment_gateways_by_shipping_payment_gatways_tab'   => __( 'Payment Gateways', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'wcj_payment_gateways_by_shipping_general_options_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'General Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_payment_gateways_by_shipping_general_options',
	),
	array(
		'title'    => __( 'Use Shipping Instances', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Enable this if you want to use shipping methods instances instead of shipping methods.', 'woocommerce-jetpack' ) . ' ' .
			__( 'Save changes after enabling this option.', 'woocommerce-jetpack' ),
		'type'     => 'checkbox',
		'id'       => 'wcj_payment_gateways_by_shipping_use_shipping_instance',
		'default'  => 'no',
	),
	array(
		'id'   => 'wcj_payment_gateways_by_shipping_general_options',
		'type' => 'sectionend',
	),
	array(
		'id'   => 'wcj_payment_gateways_by_shipping_general_options_tab',
		'type' => 'tab_end',
	),
	array(
		'id'   => 'wcj_payment_gateways_by_shipping_payment_gatways_tab',
		'type' => 'tab_start',
	),
);
$settings              = array_merge(
	$settings,
	array(
		array(
			'title' => __( 'Payment Gateways', 'woocommerce-jetpack' ),
			'type'  => 'title',
			'desc'  => __( 'If payment gateway is only available for certain methods, set it up here. Leave blank to enable for all methods.', 'woocommerce-jetpack' ),
			'id'    => 'wcj_payment_gateways_by_shipping_options',
		),
	)
);
$gateways              = WC()->payment_gateways->payment_gateways();
foreach ( $gateways as $key => $gateway ) {
	if ( ! in_array( $key, array( 'bacs', 'cod' ), true ) ) {
		$custom_attributes = apply_filters( 'booster_message', '', 'disabled' );
		if ( '' === $custom_attributes ) {
			$custom_attributes = array();
		}
		$desc_tip = apply_filters( 'booster_message', '', 'desc_no_link' );
	} else {
		$custom_attributes = array();
		$desc_tip          = '';
	}
	$settings = array_merge(
		$settings,
		array(
			array(
				'title'             => $gateway->title,
				'desc_tip'          => $desc_tip,
				'desc'              => __( 'Enable for shipping methods', 'woocommerce' ),
				'id'                => ( $use_shipping_instance ? 'wcj_gateways_by_shipping_enable_instance_' . $key : 'wcj_gateways_by_shipping_enable_' . $key ),
				'default'           => '',
				'type'              => 'multiselect',
				'class'             => 'chosen_select',
				'css'               => 'width: 450px;',
				'options'           => $shipping_methods,
				'custom_attributes' => array_merge( array( 'data-placeholder' => __( 'Select shipping methods', 'woocommerce' ) ), $custom_attributes ),
			),
		)
	);
}
$settings = array_merge(
	$settings,
	array(
		array(
			'id'   => 'wcj_payment_gateways_by_shipping_options',
			'type' => 'sectionend',
		),
		array(
			'id'   => 'wcj_payment_gateways_by_shipping_payment_gatways_tab',
			'type' => 'tab_end',
		),
	)
);
return $settings;
