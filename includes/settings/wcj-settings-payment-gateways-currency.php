<?php
/**
 * Booster for WooCommerce - Settings - Gateways Currency Converter
 *
 * @version 7.0.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @todo    [dev] maybe make "Advanced: Fix Chosen Payment Method" option enabled by default (or even remove option completely and always perform `$this->fix_chosen_payment_method()`)
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$settings           = array(
	array(
		'id'   => 'wcj_gateways_currency_converter_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'wcj_gateways_currency_converter_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'wcj_gateways_currency_converter_payment_gatways_tab'   => __( 'Payment Gateways', 'woocommerce-jetpack' ),
			'wcj_gateways_currency_converter_general_options_tab' => __( 'General Options', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'wcj_gateways_currency_converter_payment_gatways_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Payment Gateways', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'desc'  => __( 'This section lets you set different currency for each payment gateway.', 'woocommerce-jetpack' ),
		'id'    => 'wcj_payment_gateways_currency_options',
	),
);
$currency_from      = get_woocommerce_currency();
$available_gateways = WC()->payment_gateways->payment_gateways();
foreach ( $available_gateways as $key => $gateway ) {
	$currency_to       = wcj_get_option( 'wcj_gateways_currency_' . $key, get_woocommerce_currency() );
	$custom_attributes = array(
		'currency_from'        => $currency_from,
		'currency_to'          => $currency_to,
		'multiply_by_field_id' => 'wcj_gateways_currency_exchange_rate_' . $key,
	);
	if ( $currency_from === $currency_to ) {
		$custom_attributes['disabled'] = 'disabled';
	}
	if ( 'no_changes' === $currency_to ) {
		$custom_attributes['disabled'] = 'disabled';
		$currency_to                   = $currency_from;
	}
	$settings = array_merge(
		$settings,
		array(
			array(
				'title'   => $gateway->get_method_title() . ( $gateway->get_title() !== $gateway->get_method_title() ? ' (' . $gateway->get_title() . ')' : '' ),
				'id'      => 'wcj_gateways_currency_' . $key,
				'default' => 'no_changes',
				'type'    => 'select',
				'class'   => 'wcj_select_search_input',
				'options' => array_merge( array( 'no_changes' => __( 'No changes', 'woocommerce-jetpack' ) ), wcj_get_woocommerce_currencies_and_symbols() ),
				'desc'    => '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $key ) . '"' .
					' style="font-style:normal;text-decoration:none;" title="' . __( 'Go to payment gateway\'s settings', 'woocommerce-jetpack' ) . '">&#8505;</a>',
			),
			array(
				'title'                    => '',
				'id'                       => 'wcj_gateways_currency_exchange_rate_' . $key,
				'default'                  => 1,
				'type'                     => 'exchange_rate',
				'custom_attributes_button' => $custom_attributes,
				'value'                    => $currency_from . '/' . $currency_to,
			),
		)
	);
}
$settings = array_merge(
	$settings,
	array(
		array(
			'id'   => 'wcj_payment_gateways_currency_options',
			'type' => 'sectionend',
		),
		array(
			'id'   => 'wcj_gateways_currency_converter_payment_gatways_tab',
			'type' => 'tab_end',
		),
		array(
			'id'   => 'wcj_gateways_currency_converter_general_options_tab',
			'type' => 'tab_start',
		),
		array(
			'title' => __( 'Options', 'woocommerce-jetpack' ),
			'type'  => 'title',
			'id'    => 'wcj_payment_gateways_currency_general_options',
		),
		array(
			'title'             => __( 'Exchange Rates Updates', 'woocommerce-jetpack' ),
			'id'                => 'wcj_gateways_currency_exchange_rate_update_auto',
			'default'           => 'manual',
			'type'              => 'select',
			'options'           => array(
				'manual' => __( 'Enter Rates Manually', 'woocommerce-jetpack' ),
				'auto'   => __( 'Automatically via Currency Exchange Rates module', 'woocommerce-jetpack' ),
			),
			'desc'              => ( '' === apply_filters( 'booster_message', '', 'desc' ) ) ?
				__( 'Visit', 'woocommerce-jetpack' ) .
					' <a href="' . admin_url( wcj_admin_tab_url() . '&wcj-cat=prices_and_currencies&section=currency_exchange_rates' ) . '">' .
						__( 'Currency Exchange Rates module', 'woocommerce-jetpack' ) . '</a>'
				: apply_filters( 'booster_message', '', 'desc' ),
			'custom_attributes' => apply_filters( 'booster_message', '', 'disabled' ),
		),
		array(
			'title'   => __( 'Show Converted Prices', 'woocommerce-jetpack' ),
			'id'      => 'wcj_gateways_currency_page_scope',
			'default' => 'cart_and_checkout',
			'type'    => 'select',
			'options' => array(
				'cart_and_checkout' => __( 'On both cart and checkout pages', 'woocommerce-jetpack' ),
				'checkout_only'     => __( 'On checkout page only', 'woocommerce-jetpack' ),
			),
		),
		array(
			'title'    => __( 'Advanced: Fix "Chosen Payment Method"', 'woocommerce-jetpack' ),
			'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'Enable this if you are having compatibility issues with some other plugins or modules.', 'woocommerce-jetpack' ),
			'id'       => 'wcj_gateways_currency_fix_chosen_payment_method',
			'default'  => 'no',
			'type'     => 'checkbox',
		),
		array(
			'id'   => 'wcj_payment_gateways_currency_general_options',
			'type' => 'sectionend',
		),
		array(
			'id'   => 'wcj_gateways_currency_converter_general_options_tab',
			'type' => 'tab_end',
		),
	)
);
return $settings;
