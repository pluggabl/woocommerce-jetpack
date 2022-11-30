<?php
/**
 * Booster for WooCommerce - Settings - Currency Exchange Rates
 *
 * @version 6.0.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @todo    add "rounding" and "fixed offset" options for each pair separately (and option to enable/disable these per pair extra settings)
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$desc = '';
if ( $this->is_enabled() ) {
	if ( '' !== wcj_get_option( 'wcj_currency_exchange_rate_cron_time', '' ) ) {
		$scheduled_time_diff = wcj_get_option( 'wcj_currency_exchange_rate_cron_time', '' ) - time();
		if ( $scheduled_time_diff > 60 ) {
			/* translators: %s: translators Added */
			$desc = '<br><em>' . sprintf( __( '%s till next update.', 'woocommerce-jetpack' ), human_time_diff( 0, $scheduled_time_diff ) ) . '</em>';
		} elseif ( $scheduled_time_diff > 0 ) {
			/* translators: %s: translators Added */
			$desc = '<br><em>' . sprintf( __( '%s seconds till next update.', 'woocommerce-jetpack' ), $scheduled_time_diff ) . '</em>';
		}
	}
}
$settings = array(
	array(
		'title' => __( 'General Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_currency_exchange_rates_options',
	),
	array(
		'title'    => __( 'Exchange Rates Updates', 'woocommerce-jetpack' ),
		'id'       => 'wcj_currency_exchange_rates_auto',
		'default'  => 'daily',
		'desc_tip' => __( 'How frequently do you want to update currency rates. ', 'woocommerce-jetpack' ),
		'type'     => 'select',
		'options'  => array(
			'minutely'   => __( 'Update Every Minute', 'woocommerce-jetpack' ),
			'hourly'     => __( 'Update Hourly', 'woocommerce-jetpack' ),
			'twicedaily' => __( 'Update Twice Daily', 'woocommerce-jetpack' ),
			'daily'      => __( 'Update Daily', 'woocommerce-jetpack' ),
			'weekly'     => __( 'Update Weekly', 'woocommerce-jetpack' ),
		),
		'desc'     => ( $this->is_enabled() ?
			$desc . '<a href="' . esc_url( add_query_arg( 'wcj_currency_exchange_rates_update_now', '1' ) ) . '">' . __( 'Update all rates now', 'woocommerce-jetpack' ) . '</a>' : '' ),
	),
	array(
		'title'    => __( 'Exchange Rates Server', 'woocommerce-jetpack' ),
		'id'       => 'wcj_currency_exchange_rates_server',
		'default'  => 'ecb',
		'desc_tip' => __( 'If rates are not updated then re-enable the cron system open your wp-config.php file located in the base root of your WordPress directory and look for a PHP Constant named define("ALTERNATE_WP_CRON", true);and set it’s value to true..', 'woocommerce-jetpack' ),
		'type'     => 'select',
		'options'  => wcj_get_currency_exchange_rate_servers(),
	),
	array(
		'title'   => __( 'Exchange Rates Rounding', 'woocommerce-jetpack' ),
		'desc'    => __( 'Enable', 'woocommerce-jetpack' ),
		'id'      => 'wcj_currency_exchange_rates_rounding_enabled',
		'default' => 'no',
		'type'    => 'checkbox',
	),
	array(
		'desc'              => __( 'Number of decimals', 'woocommerce' ) . ' (' . __( 'i.e. rounding precision', 'woocommerce-jetpack' ) . ')',
		'desc_tip'          => __( 'Rounding precision sets number of decimal digits to round to.', 'woocommerce-jetpack' ),
		'id'                => 'wcj_currency_exchange_rates_rounding_precision',
		'default'           => 0,
		'type'              => 'number',
		'custom_attributes' => array( 'min' => '0' ),
	),
	array(
		'title'             => __( 'Exchange Rates Offset - Percent', 'woocommerce-jetpack' ),
		'desc_tip'          => __( 'If both percent and fixed offsets are set - percent offset is applied first and fixed offset after that.', 'woocommerce-jetpack' ),
		'id'                => 'wcj_currency_exchange_rates_offset_percent',
		'default'           => 0,
		'type'              => 'number',
		'custom_attributes' => array( 'step' => '0.001' ),
	),
	array(
		'title'             => __( 'Exchange Rates Offset - Fixed', 'woocommerce-jetpack' ),
		'desc_tip'          => __( 'If both percent and fixed offsets are set - percent offset is applied first and fixed offset after that.', 'woocommerce-jetpack' ),
		'id'                => 'wcj_currency_exchange_rates_offset_fixed',
		'default'           => 0,
		'type'              => 'number',
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
		'title'    => __( 'Force Point as Decimal Separator', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Force "." as decimal separator for exchange rates.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_currency_exchange_rates_point_decimal_separator',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'type' => 'sectionend',
		'id'   => 'wcj_currency_exchange_rates_options',
	),
	array(
		'title' => __( 'API Keys', 'woocommerce-jetpack' ),
		'desc'  => __( 'API keys provided by the Exchange Rates Servers', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_currency_exchange_api_key',
	),
	array(
		'title' => __( 'Free Currency Converter', 'woocommerce-jetpack' ),
		/* translators: %s: translators Added */
		'desc'  => sprintf( __( 'More information at %s', 'woocommerce-jetpack' ), '<a target="_blank" href="https://free.currencyconverterapi.com/free-api-key">https://free.currencyconverterapi.com/free-api-key</a>' ),
		'type'  => 'text',
		'id'    => 'wcj_currency_exchange_api_key_fccapi',
	),
	array(
		'type' => 'sectionend',
		'id'   => 'wcj_currency_exchange_api_key',
	),
	array(
		'title' => __( 'Custom Currencies Options', 'woocommerce-jetpack' ),
		'desc'  => sprintf(
			/* translators: %s: translators Added */
			__( 'You can add more currencies in this section. E.g. this can be used to display exchange rates with %s shortcodes.', 'woocommerce-jetpack' ),
			'<code>[wcj_currency_exchange_rate]</code>, <code>[wcj_currency_exchange_rates_table]</code>'
		),
		'type'  => 'title',
		'id'    => 'wcj_currency_exchange_custom_currencies_options',
	),
);
// Additional (custom) currencies.
$all_currencies = wcj_get_woocommerce_currencies_and_symbols();
$settings       = array_merge(
	$settings,
	array(
		array(
			'title'             => __( 'Total Custom Currencies', 'woocommerce-jetpack' ),
			'id'                => 'wcj_currency_exchange_custom_currencies_total_number',
			'default'           => 1,
			'type'              => 'custom_number',
			'desc'              => apply_filters( 'booster_message', '', 'desc' ),
			'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
		),
	)
);
$total_number   = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_currency_exchange_custom_currencies_total_number', 1 ) );
for ( $i = 1; $i <= $total_number; $i++ ) {
	$settings = array_merge(
		$settings,
		array(
			array(
				'title'   => __( 'Custom Currency', 'woocommerce-jetpack' ) . ' #' . $i,
				'id'      => 'wcj_currency_exchange_custom_currencies_' . $i,
				'default' => 'disabled',
				'type'    => 'select',
				'options' => array_merge( array( 'disabled' => __( 'Disabled', 'woocommerce-jetpack' ) ), $all_currencies ),
			),
		)
	);
}
$settings = array_merge(
	$settings,
	array(
		array(
			'type' => 'sectionend',
			'id'   => 'wcj_currency_exchange_custom_currencies_options',
		),
	)
);
// Exchange rates.
$exchange_rate_settings = $this->get_all_currencies_exchange_rates_settings( true );
if ( ! empty( $exchange_rate_settings ) ) {
	$settings = array_merge(
		$settings,
		array(
			array(
				'title' => __( 'Exchange Rates', 'woocommerce-jetpack' ),
				'type'  => 'title',
				'desc'  => __( 'All currencies from all <strong>enabled</strong> modules (with "Exchange Rates Updates" set to "Automatically via Currency Exchange Rates module") will be automatically added to the list.', 'woocommerce-jetpack' ),
				'id'    => 'wcj_currency_exchange_rates_rates',
			),
		)
	);
	$settings = array_merge( $settings, $exchange_rate_settings );
	$settings = array_merge(
		$settings,
		array(
			array(
				'type' => 'sectionend',
				'id'   => 'wcj_currency_exchange_rates_rates',
			),
		)
	);
}
return $settings;
