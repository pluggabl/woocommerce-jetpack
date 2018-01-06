<?php
/**
 * Booster for WooCommerce - Settings Meta Box - Currency per Product
 *
 * @version 2.8.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$currency_codes = array();
$currency_codes[ get_option('woocommerce_currency') ] = get_option('woocommerce_currency');
$currency_codes[ get_woocommerce_currency() ] = get_woocommerce_currency();
$total_number = apply_filters( 'booster_option', 1, get_option( 'wcj_currency_per_product_total_number', 1 ) );
for ( $i = 1; $i <= $total_number; $i++ ) {
	$currency_codes[ get_option( 'wcj_currency_per_product_currency_' . $i ) ] = get_option( 'wcj_currency_per_product_currency_' . $i );
}
$options = array(
	array(
		'name'       => 'wcj_currency_per_product_currency',
		'default'    => get_woocommerce_currency(),
		'type'       => 'select',
		'title'      => __( 'Product Currency', 'woocommerce-jetpack' ),
		'options'    => $currency_codes,
		'tooltip'    => __( 'Update product after you change this field\'s value.', 'woocommerce-jetpack' ),
	),
);
return $options;
