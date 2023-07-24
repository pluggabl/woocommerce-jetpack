<?php
/**
 * Booster for WooCommerce - Settings - Gateways Icons
 *
 * @version 7.0.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$settings           = array(
	array(
		'id'   => 'wcj_payment_gateways_icons_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'wcj_payment_gateways_icons_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'wcj_payment_gateways_icons_general_options_tab' => __( 'General Options', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'wcj_payment_gateways_icons_general_options_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'desc'  => __( 'If you want to show an image next to the gateway\'s name on the frontend, enter a URL to an image.', 'woocommerce-jetpack' ),
		'id'    => 'wcj_payment_gateways_icons_options',
	),
);
$available_gateways = WC()->payment_gateways->payment_gateways();
foreach ( $available_gateways as $key => $gateway ) {
	$default_gateways = array( 'cod', 'cheque', 'bacs', 'mijireh_checkout', 'paypal' );
	if ( ! empty( $default_gateways ) && ! in_array( $key, $default_gateways, true ) ) {
		$custom_attributes = apply_filters( 'booster_message', '', 'disabled' );
		$desc_tip          = apply_filters( 'booster_message', '', 'desc' );
	} else {
		$custom_attributes = array();
		$desc_tip          = '';
	}
	$current_icon_url = wcj_get_option( 'wcj_gateways_icons_' . $key . '_icon', '' );
	$desc             = ( '' !== $current_icon_url ) ? '<img width="16" src="' . $current_icon_url . '" alt="' . $gateway->title . '" title="' . $gateway->title . '" />' : '';
	$settings         = array_merge(
		$settings,
		array(
			array(
				'title'             => $gateway->title,
				'desc_tip'          => __( 'Leave blank to set WooCommerce default value', 'woocommerce-jetpack' ),
				'desc'              => ( '' !== $desc_tip ) ? $desc_tip : $desc,
				'id'                => 'wcj_gateways_icons_' . $key . '_icon',
				'default'           => '',
				'type'              => 'text',
				'css'               => 'min-width:300px;width:50%;',
				'custom_attributes' => $custom_attributes,
			),
			array(
				'title'             => '',
				'desc_tip'          => $desc_tip,
				'desc'              => __( 'Remove Icon', 'woocommerce-jetpack' ),
				'id'                => 'wcj_gateways_icons_' . $key . '_icon_remove',
				'default'           => 'no',
				'type'              => 'checkbox',
				'custom_attributes' => $custom_attributes,
			),
		)
	);
}
$settings = array_merge(
	$settings,
	array(
		array(
			'id'   => 'wcj_payment_gateways_icons_options',
			'type' => 'sectionend',
		),
		array(
			'id'   => 'wcj_payment_gateways_icons_general_options_tab',
			'type' => 'tab_end',
		),
	)
);
return $settings;
