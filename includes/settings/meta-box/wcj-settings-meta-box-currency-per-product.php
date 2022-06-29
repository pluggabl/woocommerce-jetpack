<?php
/**
 * Booster for WooCommerce - Settings Meta Box - Currency per Product
 *
 * @version 5.6.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/meta-boxs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$currency_codes     = array();
$currency_codes[''] = __( 'Default', 'woocommerce-jetpack' );
$currency_codes[ wcj_get_option( 'woocommerce_currency' ) ] = wcj_get_option( 'woocommerce_currency' );
$currency_codes[ get_woocommerce_currency() ]               = get_woocommerce_currency();
$total_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_currency_per_product_total_number', 1 ) );
for ( $i = 1; $i <= $total_number; $i++ ) {
	$currency_codes[ wcj_get_option( 'wcj_currency_per_product_currency_' . $i ) ] = wcj_get_option( 'wcj_currency_per_product_currency_' . $i );
}
$options = array(
	array(
		'name'    => 'wcj_currency_per_product_currency',
		'default' => '',
		'type'    => 'select',
		'title'   => __( 'Product Currency', 'woocommerce-jetpack' ),
		'options' => $currency_codes,
		'tooltip' => __( 'Update product after you change this field\'s value.', 'woocommerce-jetpack' ),
	),
);
return $options;
