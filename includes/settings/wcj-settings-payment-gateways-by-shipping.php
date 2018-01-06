<?php
/**
 * Booster for WooCommerce Settings - Payment Gateways by Shipping
 *
 * @version 2.8.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 * @todo    (maybe) remove COD, Custom Booster Payment Gateways (and maybe other payment gateways) that already have `enable_for_methods` option
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$shipping_methods = array();
if ( is_admin() ) {
	foreach ( WC()->shipping()->load_shipping_methods() as $method ) {
		$shipping_methods[ $method->id ] = $method->get_method_title();
	}
}
$settings = array(
	array(
		'title' => __( 'Payment Gateways', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'desc'  => __( 'If payment gateway is only available for certain methods, set it up here. Leave blank to enable for all methods.', 'woocommerce-jetpack' ),
		'id'    => 'wcj_payment_gateways_by_shipping_options',
	),
);
$gateways = WC()->payment_gateways->payment_gateways();
foreach ( $gateways as $key => $gateway ) {
	if ( ! in_array( $key, array( 'bacs', 'cod' ) ) ) {
		$custom_attributes = apply_filters( 'booster_message', '', 'disabled' );
		if ( '' == $custom_attributes ) {
			$custom_attributes = array();
		}
		$desc_tip = apply_filters( 'booster_message', '', 'desc_no_link' );
	} else {
		$custom_attributes = array();
		$desc_tip = '';
	}
	$settings = array_merge( $settings, array(
		array(
			'title'             => $gateway->title,
			'desc_tip'          => $desc_tip,
			'desc'              => __( 'Enable for shipping methods', 'woocommerce' ),
			'id'                => 'wcj_gateways_by_shipping_enable_' . $key,
			'default'           => '',
			'type'              => 'multiselect',
			'class'             => 'chosen_select',
			'css'               => 'width: 450px;',
			'options'           => $shipping_methods,
			'custom_attributes' => array_merge( array( 'data-placeholder' => __( 'Select shipping methods', 'woocommerce' ) ), $custom_attributes ),
		),
	) );
}
$settings = array_merge( $settings, array(
	array(
		'type'  => 'sectionend',
		'id'    => 'wcj_payment_gateways_by_shipping_options',
	),
) );
return $settings;
