<?php
/**
 * Booster for WooCommerce - Settings Meta Box - Custom CSS
 *
 * @version 2.8.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

return array(
	array(
		'title'    => __( 'Enabled', 'woocommerce-jetpack' ),
		'name'     => 'wcj_product_open_price_enabled',
		'default'  => 'no',
		'type'     => 'select',
		'options'  => array(
			'yes' => __( 'Yes', 'woocommerce-jetpack' ),
			'no'  => __( 'No', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'    => __( 'Default Price', 'woocommerce-jetpack' ) . ' (' . get_woocommerce_currency_symbol() . ')',
		'name'     => 'wcj_product_open_price_default_price',
		'default'  => '',
		'type'     => 'price',
		'custom_attributes' => 'min="0"',
	),
	array(
		'title'    => __( 'Min Price', 'woocommerce-jetpack' ) . ' (' . get_woocommerce_currency_symbol() . ')',
		'name'     => 'wcj_product_open_price_min_price',
		'default'  => 1,
		'type'     => 'price',
		'custom_attributes' => 'min="0"',
	),
	array(
		'title'    => __( 'Max Price', 'woocommerce-jetpack' ) . ' (' . get_woocommerce_currency_symbol() . ')',
		'name'     => 'wcj_product_open_price_max_price',
		'default'  => '',
		'type'     => 'price',
		'custom_attributes' => 'min="0"',
	),
);
