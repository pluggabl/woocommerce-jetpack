<?php
/**
 * Booster for WooCommerce - Settings - Currencies
 *
 * @version 2.9.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 * @todo    add virtual currencies to "All Currencies for WC" plugin
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$settings = array(
	array(
		'title'    => __( 'Currency Symbol Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_all_currencies_list_options',
	),
	array(
		'title'    => __( 'Hide Currency Symbol', 'woocommerce-jetpack' ),
		'desc'     => __( 'Hide', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Default: no.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_currency_hide_symbol',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
);
$currency_names   = wcj_get_currencies_names_and_symbols( 'names',   'no_custom' );
$currency_symbols = wcj_get_currencies_names_and_symbols( 'symbols', 'no_custom' );
$countries        = wcj_get_currency_countries();
foreach ( $currency_names as $currency_code => $currency_name ) {
	$country_flag = ( 'EUR' === $currency_code ?
		wcj_get_country_flag_by_code( 'EU' ) :
		( isset( $countries[ $currency_code ] ) ? wcj_get_country_flag_by_code( $countries[ $currency_code ][0] ) : '' )
	);
	$settings = array_merge( $settings, array(
		array(
			'title'    => $currency_name . ' [' . $currency_code . ']',
			'desc'     => $country_flag,
			'desc_tip' => apply_filters( 'booster_message', '', 'desc_no_link' ),
			'id'       => 'wcj_currency_' . $currency_code,
			'default'  => $currency_symbols[ $currency_code ],
			'type'     => 'text',
			'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
		),
	) );
}
$settings = array_merge( $settings, array(
	array(
			'type'     => 'sectionend',
			'id'       => 'wcj_all_currencies_list_options',
	),
	array(
			'title'    => __( 'Custom Currencies', 'woocommerce-jetpack' ),
			'type'     => 'title',
			'id'       => 'wcj_currency_custom_currency_options',
	),
	array(
			'title'    => __( 'Total Custom Currencies', 'woocommerce-jetpack' ),
			'id'       => 'wcj_currency_custom_currency_total_number',
			'default'  => 1,
			'type'     => 'custom_number',
			'desc'     => apply_filters( 'booster_message', '', 'desc' ),
			'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
	),
) );
$custom_currency_total_number = apply_filters( 'booster_option', 1, get_option( 'wcj_currency_custom_currency_total_number', 1 ) );
for ( $i = 1; $i <= $custom_currency_total_number; $i++ ) {
	$settings = array_merge( $settings, array(
		array(
			'title'    => __( 'Custom Currency', 'woocommerce-jetpack' ) . ' #' . $i,
			'desc'     => __( 'Currency Name (required)', 'woocommerce-jetpack' ),
			'id'       => 'wcj_currency_custom_currency_name_' . $i,
			'default'  => '',
			'type'     => 'text',
		),
		array(
			'title'    => '',
			'desc'     => __( 'Currency Code (required)', 'woocommerce-jetpack' ),
			'id'       => 'wcj_currency_custom_currency_code_' . $i,
			'default'  => '',
			'type'     => 'text',
		),
		array(
			'title'    => '',
			'desc'     => __( 'Currency Symbol', 'woocommerce-jetpack' ),
			'id'       => 'wcj_currency_custom_currency_symbol_' . $i,
			'default'  => '',
			'type'     => 'text',
		),
	) );
}
$settings = array_merge( $settings, array(
	array(
			'type'     => 'sectionend',
			'id'       => 'wcj_currency_custom_currency_options',
	),
) );
return $settings;
