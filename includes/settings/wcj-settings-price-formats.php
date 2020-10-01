<?php
/**
 * Booster for WooCommerce - Settings - Price Formats
 *
 * @version 3.9.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @todo    (maybe) add `desc_tip` to `wcj_price_formats_general_trim_zeros`
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$settings = array(
	array(
		'title'    => __( 'General Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_price_formats_general_options',
	),
	array(
		'title'    => __( 'Trim Zeros in Prices', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'type'     => 'checkbox',
		'id'       => 'wcj_price_formats_general_trim_zeros',
		'default'  => 'no',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_price_formats_general_options',
	),
	array(
		'title'    => __( 'Price Formats by Currency (or WPML)', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_price_formats_options',
	),
	array(
		'title'    => __( 'Price Formats by Currency (or WPML)', 'woocommerce-jetpack' ),
		'desc'     => '<strong>' . __( 'Enable section', 'woocommerce-jetpack' ) . '</strong>',
		'type'     => 'checkbox',
		'id'       => 'wcj_price_formats_by_currency_enabled',
		'default'  => 'yes',
	),
	array(
		'title'    => __( 'Total Number', 'woocommerce-jetpack' ),
		'id'       => 'wcj_price_formats_total_number',
		'default'  => 1,
		'type'     => 'custom_number',
		'desc'     => apply_filters( 'booster_message', '', 'desc' ),
		'custom_attributes' => array_merge(
			is_array( apply_filters( 'booster_message', '', 'readonly' ) ) ? apply_filters( 'booster_message', '', 'readonly' ) : array(),
			array( 'step' => '1', 'min' => '0' )
		),
	),
);
for ( $i = 1; $i <= apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_price_formats_total_number', 1 ) ); $i++ ) {
	$currency_symbol = get_woocommerce_currency_symbol( wcj_get_option( 'wcj_price_formats_currency_' . $i, get_woocommerce_currency() ) );
	$settings = array_merge( $settings, array(
		array(
			'title'    => __( 'Format', 'woocommerce-jetpack' ) . ' #' . $i,
			'desc'     => __( 'Currency', 'woocommerce-jetpack' ),
			'id'       => 'wcj_price_formats_currency_' . $i,
			'default'  => get_woocommerce_currency(),
			'type'     => 'select',
			'options'  => wcj_get_woocommerce_currencies_and_symbols(),
			'css'      => 'width:300px;',
		),
		array(
			'desc'     => __( 'Currency Position', 'woocommerce-jetpack' ),
			'id'       => 'wcj_price_formats_currency_position_' . $i,
			'default'  => wcj_get_option( 'woocommerce_currency_pos' ),
			'type'     => 'select',
			'options'  => array(
				'left'        => __( 'Left', 'woocommerce' ) . ' (' . $currency_symbol . '99.99)',
				'right'       => __( 'Right', 'woocommerce' ) . ' (99.99' . $currency_symbol . ')',
				'left_space'  => __( 'Left with space', 'woocommerce' ) . ' (' . $currency_symbol . ' 99.99)',
				'right_space' => __( 'Right with space', 'woocommerce' ) . ' (99.99 ' . $currency_symbol . ')'
			),
			'css'      => 'width:300px;',
		),
		array(
			'desc'     => __( 'Additional Currency Code Position', 'woocommerce-jetpack' ),
			'id'       => 'wcj_price_formats_currency_code_position_' . $i,
			'default'  => 'none',
			'type'     => 'select',
			'options'  => array(
				'none'        => __( 'Do not add currency code', 'woocommerce-jetpack' ),
				'left'        => __( 'Left', 'woocommerce' ),
				'right'       => __( 'Right', 'woocommerce' ),
				'left_space'  => __( 'Left with space', 'woocommerce' ),
				'right_space' => __( 'Right with space', 'woocommerce' ),
			),
		),
		array(
			'desc'     => __( 'Thousand Separator', 'woocommerce-jetpack' ),
			'id'       => 'wcj_price_formats_thousand_separator_' . $i,
			'default'  => wc_get_price_thousand_separator(),
			'type'     => 'text',
			'css'      => 'width:300px;',
			'wcj_raw'  => true,
		),
		array(
			'desc'     => __( 'Decimal Separator', 'woocommerce-jetpack' ),
			'id'       => 'wcj_price_formats_decimal_separator_' . $i,
			'default'  => wc_get_price_decimal_separator(),
			'type'     => 'text',
			'css'      => 'width:300px;',
			'wcj_raw'  => true,
		),
		array(
			'desc'     => __( 'Number of Decimals', 'woocommerce-jetpack' ),
			'id'       => 'wcj_price_formats_number_of_decimals_' . $i,
			'default'  => wcj_get_option( 'woocommerce_price_num_decimals', 2 ),
			'type'     => 'number',
			'custom_attributes' => array( 'min'  => 0, 'step' => 1 ),
			'css'      => 'width:300px;',
		),
		array(
			'desc'     => __( 'WPML Language Code', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'Option to set different price formats for different WPML languages. Can be comma separated list. Leave empty to disable.', 'woocommerce-jetpack' ),
			'id'       => 'wcj_price_formats_wpml_language_' . $i,
			'default'  => '',
			'type'     => 'text',
			'css'      => 'width:300px;',
		),
	) );
}
$settings = array_merge( $settings, array(
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_price_formats_options',
	),
) );
return $settings;
