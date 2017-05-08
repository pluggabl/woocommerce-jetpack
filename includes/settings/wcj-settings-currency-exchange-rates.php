<?php
/**
 * Booster for WooCommerce - Settings - Currency Exchange Rates
 *
 * @version 2.8.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$desc = '';
if ( $this->is_enabled() ) {
	if ( '' != get_option( 'wcj_currency_exchange_rate_cron_time', '' ) ) {
		$scheduled_time_diff = get_option( 'wcj_currency_exchange_rate_cron_time', '' ) - time();
		if ( $scheduled_time_diff > 60 ) {
			$desc = '<br><em>' . sprintf( __( '%s till next update.', 'woocommerce-jetpack' ), human_time_diff( 0, $scheduled_time_diff ) ) . '</em>';
		} elseif ( $scheduled_time_diff > 0 ) {
			$desc = '<br><em>' . sprintf( __( '%s seconds till next update.', 'woocommerce-jetpack' ), $scheduled_time_diff ) . '</em>';
		}
	}
}
$settings = array(
	array(
		'title'    => __( 'Exchange Rates', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'desc'     => __( 'All currencies from all <strong>enabled</strong> modules (with "Exchange Rates Updates" set to "Automatically via Currency Exchange Rates module") will be automatically added to the list.', 'woocommerce-jetpack' ) . $desc,
		'id'       => 'wcj_currency_exchange_rates_options',
	),
	array(
		'title'    => __( 'Exchange Rates Updates', 'woocommerce-jetpack' ),
		'id'       => 'wcj_currency_exchange_rates_auto',
		'default'  => 'daily',
		'type'     => 'select',
		'options'  => array(
//			'manual'     => __( 'Enter Rates Manually', 'woocommerce-jetpack' ),
			'minutely'   => __( 'Update Every Minute', 'woocommerce-jetpack' ),
			'hourly'     => __( 'Update Hourly', 'woocommerce-jetpack' ),
			'twicedaily' => __( 'Update Twice Daily', 'woocommerce-jetpack' ),
			'daily'      => __( 'Update Daily', 'woocommerce-jetpack' ),
			'weekly'     => __( 'Update Weekly', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'    => __( 'Exchange Rates Server', 'woocommerce-jetpack' ),
		'id'       => 'wcj_currency_exchange_rates_server',
		'default'  => 'yahoo',
		'type'     => 'select',
		'options'  => wcj_get_currency_exchange_rate_servers(),
	),
	array(
		'title'    => __( 'Exchange Rates Offset - Percent', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'If both percent and fixed offsets are set - percent offset is applied first and fixed offset after that.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_currency_exchange_rates_offset_percent',
		'default'  => 0,
		'type'     => 'number',
		'custom_attributes' => array( 'step' => '0.001' ),
	),
	array(
		'title'    => __( 'Exchange Rates Offset - Fixed', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'If both percent and fixed offsets are set - percent offset is applied first and fixed offset after that.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_currency_exchange_rates_offset_fixed',
		'default'  => 0,
		'type'     => 'number',
		'custom_attributes' => array( 'step' => '0.000001' ),
	),
	array(
		'title'    => __( 'Calculate with Inversion', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'If your currency pair have very small exchange rate, you may want to invert currencies before calculating the rate.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_currency_exchange_rates_calculate_by_invert',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Always Use cURL', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'If for some reason currency exchange rates are not updating, try enabling this option.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_currency_exchange_rates_always_curl',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
);
$currency_from = get_option( 'woocommerce_currency' );
if ( wcj_is_module_enabled( 'price_by_country' ) ) {
	// Currency Pairs - Price by Country
	if ( 'manual' != apply_filters( 'booster_get_option', 'manual', get_option( 'wcj_price_by_country_auto_exchange_rates', 'manual' ) ) ) {
		for ( $i = 1; $i <= apply_filters( 'booster_get_option', 1, get_option( 'wcj_price_by_country_total_groups_number', 1 ) ); $i++ ) {
			$currency_to = get_option( 'wcj_price_by_country_exchange_rate_currency_group_' . $i );
			$settings = $this->add_currency_pair_setting( $currency_from, $currency_to, $settings );
		}
	}
}
if ( wcj_is_module_enabled( 'multicurrency' ) ) {
	// Currency Pairs - Multicurrency
	if ( 'manual' != apply_filters( 'booster_get_option', 'manual', get_option( 'wcj_multicurrency_exchange_rate_update_auto', 'manual' ) ) ) {
		for ( $i = 1; $i <= apply_filters( 'booster_get_option', 2, get_option( 'wcj_multicurrency_total_number', 2 ) ); $i++ ) {
			$currency_to = get_option( 'wcj_multicurrency_currency_' . $i );
			$settings = $this->add_currency_pair_setting( $currency_from, $currency_to, $settings );
		}
	}
}
if ( wcj_is_module_enabled( 'multicurrency_base_price' ) ) {
	// Currency Pairs - Multicurrency Product Base Price
	if ( 'manual' != apply_filters( 'booster_get_option', 'manual', get_option( 'wcj_multicurrency_base_price_exchange_rate_update', 'manual' ) ) ) {
		for ( $i = 1; $i <= apply_filters( 'booster_get_option', 1, get_option( 'wcj_multicurrency_base_price_total_number', 1 ) ); $i++ ) {
			$currency_to = get_option( 'wcj_multicurrency_base_price_currency_' . $i );
			$settings = $this->add_currency_pair_setting( $currency_from, $currency_to, $settings );
		}
	}
}
if ( wcj_is_module_enabled( 'currency_per_product' ) ) {
	// Currency Pairs - Currency per Product
	if ( 'manual' != apply_filters( 'booster_get_option', 'manual', get_option( 'wcj_currency_per_product_exchange_rate_update', 'manual' ) ) ) {
		for ( $i = 1; $i <= apply_filters( 'booster_get_option', 1, get_option( 'wcj_currency_per_product_total_number', 1 ) ); $i++ ) {
			$currency_to = get_option( 'wcj_currency_per_product_currency_' . $i );
			$settings = $this->add_currency_pair_setting( $currency_from, $currency_to, $settings );
		}
	}
}
if ( wcj_is_module_enabled( 'payment_gateways_currency' ) ) {
	if ( 'manual' != apply_filters( 'booster_get_option', 'manual', get_option( 'wcj_gateways_currency_exchange_rate_update_auto', 'manual' ) ) ) {
		// Currency Pairs - Gateway Currency
		global $woocommerce;
		$available_gateways = $woocommerce->payment_gateways->payment_gateways();
		foreach ( $available_gateways as $key => $gateway ) {
			$currency_to = get_option( 'wcj_gateways_currency_' . $key );
			if ( 'no_changes' != $currency_to ) {
				$settings = $this->add_currency_pair_setting( $currency_from, $currency_to, $settings );
			}
		}
	}
}
$settings = array_merge( $settings, array(
	/*
	array(
		'title'    => __( 'Logging', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_currency_exchange_logging_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	*/
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_currency_exchange_rates_options',
	),
) );
return $settings;
