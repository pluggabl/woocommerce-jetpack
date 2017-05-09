<?php
/**
 * Booster for WooCommerce - Settings - Product Price by Formula
 *
 * @version 2.8.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$settings = array(
	array(
		'title'    => __( 'Default Settings', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'desc'     => __( 'You can set default settings here. All settings can later be changed in individual product\'s edit page.', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_price_by_formula_options',
	),
	array(
		'title'    => __( 'Formula', 'woocommerce-jetpack' ),
		'desc'     => __( 'Use "x" variable for product\'s base price. For example: x+p1*p2', 'woocommerce-jetpack' ),
		'type'     => 'text',
		'id'       => 'wcj_product_price_by_formula_eval',
		'default'  => '',
	),
	array(
		'title'    => __( 'Enable Price Calculation By Formula For All Products', 'woocommerce-jetpack' ),
		'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
		'type'     => 'checkbox',
		'id'       => 'wcj_product_price_by_formula_enable_for_all_products',
		'default'  => 'no',
		'desc_tip' => apply_filters( 'booster_get_message', '', 'desc_no_link' ),
		'custom_attributes' => apply_filters( 'booster_get_message', '', 'readonly' ),
	),
	array(
		'title'    => __( 'Total Params', 'woocommerce-jetpack' ),
		'id'       => 'wcj_product_price_by_formula_total_params',
		'default'  => 1,
		'type'     => 'custom_number',
		/* 'desc'     => apply_filters( 'booster_get_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_get_message', '', 'readonly' ), */
	),
);
$total_number = get_option( 'wcj_product_price_by_formula_total_params', 1 );
for ( $i = 1; $i <= $total_number; $i++ ) {
	$settings[] = array(
		'title'    => 'p' . $i,
		'id'       => 'wcj_product_price_by_formula_param_' . $i,
		'default'  => '',
		'type'     => 'text',
	);
}
$settings[] = array(
	'type'         => 'sectionend',
	'id'           => 'wcj_product_price_by_formula_options',
);
return $settings;
