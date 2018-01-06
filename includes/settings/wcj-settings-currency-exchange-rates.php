<?php
/**
 * Booster for WooCommerce - Settings - Currency Exchange Rates
 *
 * @version 3.2.4
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 * @todo    add "rounding" and "offset" options for each pair separately
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
		'title'    => __( 'General Options', 'woocommerce-jetpack' ),
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
		'default'  => 'ecb',
		'type'     => 'select',
		'options'  => wcj_get_currency_exchange_rate_servers(),
	),
	array(
		'title'    => __( 'Exchange Rates Rounding', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'id'       => 'wcj_currency_exchange_rates_rounding_enabled',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'desc'     => __( 'Rounding Precision', 'woocommerce-jetpack' ),
		'id'       => 'wcj_currency_exchange_rates_rounding_precision',
		'default'  => 0,
		'type'     => 'number',
		'custom_attributes' => array( 'min' => '0' ),
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
	array(
		'title'    => __( 'Custom Currencies Options', 'woocommerce-jetpack' ),
		'desc'     => sprintf(
			__( 'You can add more currencies in this section. E.g. this can be used to display exchange rates with %s shortcodes.', 'woocommerce-jetpack' ),
			'<code>[wcj_currency_exchange_rate]</code>, <code>[wcj_currency_exchange_rates_table]</code>'
		),
		'type'     => 'title',
		'id'       => 'wcj_currency_exchange_custom_currencies_options',
	),
);
// Additional (custom) currencies
$all_currencies = wcj_get_currencies_names_and_symbols();
$settings = array_merge( $settings, array(
	array(
		'title'    => __( 'Total Custom Currencies', 'woocommerce-jetpack' ),
		'id'       => 'wcj_currency_exchange_custom_currencies_total_number',
		'default'  => 1,
		'type'     => 'custom_number',
		'desc'     => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
	),
) );
$total_number = apply_filters( 'booster_option', 1, get_option( 'wcj_currency_exchange_custom_currencies_total_number', 1 ) );
for ( $i = 1; $i <= $total_number; $i++ ) {
	$settings = array_merge( $settings, array(
		array(
			'title'    => __( 'Custom Currency', 'woocommerce-jetpack' ) . ' #' . $i,
			'id'       => 'wcj_currency_exchange_custom_currencies_' . $i,
			'default'  => 'disabled',
			'type'     => 'select',
			'options'  => array_merge( array( 'disabled' => __( 'Disabled', 'woocommerce-jetpack' ) ), $all_currencies ),
		),
	) );
}
$settings = array_merge( $settings, array(
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_currency_exchange_custom_currencies_options',
	),
) );
// Exchange rates
$exchange_rate_settings = $this->get_all_currencies_exchange_rates_settings( true );
if ( ! empty( $exchange_rate_settings ) ) {
	$settings = array_merge( $settings, array(
		array(
			'title'    => __( 'Exchange Rates', 'woocommerce-jetpack' ),
			'type'     => 'title',
			'id'       => 'wcj_currency_exchange_rates_rates',
		),
	) );
	$settings = array_merge( $settings, $exchange_rate_settings );
	$settings = array_merge( $settings, array(
		array(
			'type'     => 'sectionend',
			'id'       => 'wcj_currency_exchange_rates_rates',
		),
	) );
}
return $settings;
