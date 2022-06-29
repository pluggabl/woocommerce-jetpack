<?php
/**
 * Booster for WooCommerce - Settings Meta Box - Product Price by Formula
 *
 * @version 5.6.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/meta-boxs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$options      = array(
	array(
		'name'    => 'wcj_product_price_by_formula_enabled',
		'default' => 'no',
		'type'    => 'select',
		'options' => array(
			'yes' => __( 'Yes', 'woocommerce-jetpack' ),
			'no'  => __( 'No', 'woocommerce-jetpack' ),
		),
		'title'   => __( 'Enabled', 'woocommerce-jetpack' ),
		'tooltip' => __( '\'Enabled\' option is ignored if \'Enable Price Calculation By Formula For All Products\' option is checked in module\'s settings.', 'woocommerce-jetpack' ),
	),
	array(
		'name'    => 'wcj_product_price_by_formula_calculation',
		'default' => 'per_product',
		'type'    => 'select',
		'options' => array(
			'per_product' => __( 'Use values below', 'woocommerce-jetpack' ),
			'global'      => __( 'Use default values', 'woocommerce-jetpack' ),
		),
		'title'   => __( 'Calculation', 'woocommerce-jetpack' ),
	),
	array(
		'name'    => 'wcj_product_price_by_formula_eval',
		'default' => wcj_get_option( 'wcj_product_price_by_formula_eval', '' ),
		'type'    => 'text',
		'title'   => __( 'Formula', 'woocommerce-jetpack' ),
	),
	array(
		'name'    => 'wcj_product_price_by_formula_total_params',
		'default' => wcj_get_option( 'wcj_product_price_by_formula_total_params', 1 ),
		'type'    => 'number',
		'title'   => __( 'Number of Parameters', 'woocommerce-jetpack' ),
	),
);
$total_params = get_post_meta( get_the_ID(), '_wcj_product_price_by_formula_total_params', false );
if ( empty( $total_params ) ) {
	$total_params = wcj_get_option( 'wcj_product_price_by_formula_total_params', 1 );
} else {
	$total_params = $total_params[0];
}
for ( $i = 1; $i <= $total_params; $i++ ) {
	$options[] = array(
		'name'    => 'wcj_product_price_by_formula_param_' . $i,
		'default' => wcj_get_option( 'wcj_product_price_by_formula_param_' . $i, '' ),
		'type'    => 'text',
		'title'   => 'p' . $i,
	);
}
return $options;
