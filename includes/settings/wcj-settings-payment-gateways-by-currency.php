<?php
/**
 * Booster for WooCommerce - Settings - Gateways by Currency
 *
 * @version 7.0.0
 * @since   3.0.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$settings                            = array(
	array(
		'id'   => 'wcj_payment_gateways_by_currency_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'wcj_payment_gateways_by_currency_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'wcj_payment_gateways_by_currency_payment_gatways_tab'   => __( 'Payment Gateways', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'wcj_payment_gateways_by_currency_payment_gatways_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Payment Gateways', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'desc'  => __( 'Leave empty to disable.', 'woocommerce-jetpack' ),
		'id'    => 'wcj_payment_gateways_by_currency_gateways_options',
	),
);
$currencies                          = wcj_get_woocommerce_currencies_and_symbols();
$gateways                            = WC()->payment_gateways->payment_gateways();
$paypal_allowed_currencies           = array( 'AUD', 'BRL', 'CAD', 'MXN', 'NZD', 'HKD', 'SGD', 'USD', 'EUR', 'JPY', 'TRY', 'NOK', 'CZK', 'DKK', 'HUF', 'ILS', 'MYR', 'PHP', 'PLN', 'SEK', 'CHF', 'TWD', 'THB', 'GBP', 'RMB', 'RUB' );
$paypal_allowed_currencies_and_names = array();
foreach ( $paypal_allowed_currencies as $paypal_allowed_currency ) {
	if ( isset( $currencies[ $paypal_allowed_currency ] ) ) {
		$paypal_allowed_currencies_and_names[] = $currencies[ $paypal_allowed_currency ];
	}
}
/* translators: %s: translators Added */
$paypal_tip = sprintf( __( 'PayPal allows only these currencies: %s.', 'woocommerce-jetpack' ), '<br>' . implode( '<br>', $paypal_allowed_currencies_and_names ) );
foreach ( $gateways as $key => $gateway ) {
	$default_gateways = array( 'bacs' );
	if ( ! empty( $default_gateways ) && ! in_array( $key, $default_gateways, true ) ) {
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
				'desc_tip'          => $desc_tip . ( 'paypal' === $key ? ' ' . $paypal_tip : '' ),
				'desc'              => __( 'Allowed Currencies', 'woocommerce-jetpack' ),
				'id'                => 'wcj_gateways_by_currency_allowed_' . $key,
				'default'           => '',
				'type'              => 'multiselect',
				'class'             => 'chosen_select',
				'css'               => 'width: 450px;',
				'options'           => $currencies,
				'custom_attributes' => $custom_attributes,
			),
			array(
				'desc_tip'          => $desc_tip,
				'desc'              => __( 'Denied Currencies', 'woocommerce-jetpack' ),
				'id'                => 'wcj_gateways_by_currency_denied_' . $key,
				'default'           => '',
				'type'              => 'multiselect',
				'class'             => 'chosen_select',
				'css'               => 'width: 450px;',
				'options'           => $currencies,
				'custom_attributes' => $custom_attributes,
			),
		)
	);
}
$settings = array_merge(
	$settings,
	array(
		array(
			'id'   => 'wcj_payment_gateways_by_currency_gateways_options',
			'type' => 'sectionend',
		),
		array(
			'id'   => 'wcj_payment_gateways_by_currency_payment_gatways_tab',
			'type' => 'tab_end',
		),
	)
);
return $settings;
