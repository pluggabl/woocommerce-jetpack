<?php
/**
 * Booster for WooCommerce - Settings Meta Box - Multicurrency Product Base Price
 *
 * @version 5.6.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/meta-boxs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$currency_codes                               = array();
$currency_codes[ get_woocommerce_currency() ] = get_woocommerce_currency();
$total_number                                 = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_multicurrency_base_price_total_number', 1 ) );
for ( $i = 1; $i <= $total_number; $i++ ) {
	$currency_codes[ wcj_get_option( 'wcj_multicurrency_base_price_currency_' . $i ) ] = wcj_get_option( 'wcj_multicurrency_base_price_currency_' . $i );
}
$options = array(
	array(
		'name'    => 'wcj_multicurrency_base_price_currency',
		'default' => get_woocommerce_currency(),
		'type'    => 'select',
		'title'   => __( 'Product Currency', 'woocommerce-jetpack' ),
		'options' => $currency_codes,
	),
);
return $options;
