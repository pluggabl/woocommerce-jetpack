<?php
/**
 * Booster for WooCommerce - Settings - Currencies
 *
 * @version 5.6.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$settings       = array(
	array(
		'title' => __( 'Currency Symbol Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'desc'  => sprintf(
			/* translators: %s: translators Added */
			__( 'You can use shortcodes in currency symbols, e.g.: %s.', 'woocommerce-jetpack' ),
			'<code>[wcj_wpml lang="EN"]$[/wcj_wpml][wcj_wpml not_lang="EN"]USD[/wcj_wpml]</code>'
		),
		'id'    => 'wcj_all_currencies_list_options',
	),
	array(
		'title'    => __( 'Hide Currency Symbol', 'woocommerce-jetpack' ),
		'desc'     => __( 'Hide', 'woocommerce-jetpack' ),
		'desc_tip' => __( 'Hides currency symbol completely.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_currency_hide_symbol',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
);
$currency_names = array_diff_key( array_merge( get_woocommerce_currencies(), $this->get_additional_currencies() ), $this->get_custom_currencies() );
$countries      = wcj_get_currency_countries();
remove_filter( 'woocommerce_currency_symbol', array( $this, 'change_currency_symbol' ), PHP_INT_MAX, 2 );
foreach ( $currency_names as $currency_code => $currency_name ) {
	$country_flag   = ( 'EUR' === $currency_code ?
		wcj_get_country_flag_by_code( 'EU' ) : ( isset( $countries[ $currency_code ] ) ? wcj_get_country_flag_by_code( $countries[ $currency_code ][0] ) : '' ) );
	$default_symbol = ( in_array( $currency_code, array_keys( $this->get_additional_currencies() ), true ) ?
		$this->get_additional_currency_symbol( $currency_code ) : get_woocommerce_currency_symbol( $currency_code ) );
	$settings       = array_merge(
		$settings,
		array(
			array(
				'title'             => $currency_name . ' [' . $currency_code . ']',
				'desc'              => $country_flag,
				'desc_tip'          => apply_filters( 'booster_message', '', 'desc_no_link' ),
				'id'                => 'wcj_currency_' . $currency_code,
				'default'           => $default_symbol,
				'type'              => 'text',
				'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
			),
		)
	);
}
add_filter( 'woocommerce_currency_symbol', array( $this, 'change_currency_symbol' ), PHP_INT_MAX, 2 );
$settings                     = array_merge(
	$settings,
	array(
		array(
			'type' => 'sectionend',
			'id'   => 'wcj_all_currencies_list_options',
		),
		array(
			'title' => __( 'Custom Currencies', 'woocommerce-jetpack' ),
			'type'  => 'title',
			'id'    => 'wcj_currency_custom_currency_options',
		),
		array(
			'title'             => __( 'Total Custom Currencies', 'woocommerce-jetpack' ),
			'id'                => 'wcj_currency_custom_currency_total_number',
			'default'           => 1,
			'type'              => 'custom_number',
			'desc'              => apply_filters( 'booster_message', '', 'desc' ),
			'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
		),
	)
);
$custom_currency_total_number = apply_filters( 'booster_option', 1, get_option( 'wcj_currency_custom_currency_total_number', 1 ) );
for ( $i = 1; $i <= $custom_currency_total_number; $i++ ) {
	$settings = array_merge(
		$settings,
		array(
			array(
				'title'   => __( 'Custom Currency', 'woocommerce-jetpack' ) . ' #' . $i,
				'desc'    => __( 'Currency Name (required)', 'woocommerce-jetpack' ),
				'id'      => 'wcj_currency_custom_currency_name_' . $i,
				'default' => '',
				'type'    => 'text',
			),
			array(
				'desc'    => __( 'Currency Code (required)', 'woocommerce-jetpack' ),
				'id'      => 'wcj_currency_custom_currency_code_' . $i,
				'default' => '',
				'type'    => 'text',
			),
			array(
				'desc'    => __( 'Currency Symbol', 'woocommerce-jetpack' ),
				'id'      => 'wcj_currency_custom_currency_symbol_' . $i,
				'default' => '',
				'type'    => 'text',
			),
		)
	);
}
$settings = array_merge(
	$settings,
	array(
		array(
			'type' => 'sectionend',
			'id'   => 'wcj_currency_custom_currency_options',
		),
	)
);
return $settings;
