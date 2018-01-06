<?php
/**
 * Booster for WooCommerce - Settings - Gateways Currency
 *
 * @version 3.2.4
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$settings = array(
	array(
		'title'    => __( 'Payment Gateways Currency Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'desc'     => __( 'This section lets you set different currency for each payment gateway.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_payment_gateways_currency_options',
	),
);
$currency_from = get_woocommerce_currency();
global $woocommerce;
$available_gateways = $woocommerce->payment_gateways->payment_gateways();
foreach ( $available_gateways as $key => $gateway ) {
	$currency_to = get_option( 'wcj_gateways_currency_' . $key, get_woocommerce_currency() );
	$custom_attributes = array(
		'currency_from'        => $currency_from,
		'currency_to'          => $currency_to,
		'multiply_by_field_id' => 'wcj_gateways_currency_exchange_rate_' . $key,
	);
	if ( $currency_from == $currency_to ) {
		$custom_attributes['disabled'] = 'disabled';
	}
	if ( 'no_changes' == $currency_to ) {
		$custom_attributes['disabled'] = 'disabled';
		$currency_to = $currency_from;
	}
	$settings = array_merge( $settings, array(
		array(
			'title'     => $gateway->title,
//			'desc'      => __( 'currency', 'woocommerce-jetpack' ),
			'id'        => 'wcj_gateways_currency_' . $key,
			'default'   => 'no_changes', // get_woocommerce_currency(),
			'type'      => 'select',
			'options'   => array_merge( array( 'no_changes' => __( 'No changes', 'woocommerce-jetpack' ) ), wcj_get_currencies_names_and_symbols() ),
		),
		array(
			'title'                    => '',
//			'desc'                     => __( 'exchange rate', 'woocommerce-jetpack' ) . ' ' . $currency_from . ' / ' . $currency_to,
			'id'                       => 'wcj_gateways_currency_exchange_rate_' . $key,
			'default'                  => 1,
			'type'                     => 'exchange_rate',
			'custom_attributes_button' => $custom_attributes,
			'value'                    => $currency_from . '/' . $currency_to,
		),
	) );
}
$settings = array_merge( $settings, array(
	array(
		'title'    => __( 'Exchange Rates Updates', 'woocommerce-jetpack' ),
		'id'       => 'wcj_gateways_currency_exchange_rate_update_auto',
		'default'  => 'manual',
		'type'     => 'select',
		'options'  => array(
			'manual' => __( 'Enter Rates Manually', 'woocommerce-jetpack' ),
			'auto'   => __( 'Automatically via Currency Exchange Rates module', 'woocommerce-jetpack' ),
		),
		'desc'     => ( '' == apply_filters( 'booster_message', '', 'desc' ) ) ?
			__( 'Visit', 'woocommerce-jetpack' ) . ' <a href="' . admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=prices_and_currencies&section=currency_exchange_rates' ) . '">' . __( 'Currency Exchange Rates module', 'woocommerce-jetpack' ) . '</a>'
			: apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_payment_gateways_currency_options',
	),
) );
return $settings;
