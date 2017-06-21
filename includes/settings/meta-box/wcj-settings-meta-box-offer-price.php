<?php
/**
 * Booster for WooCommerce - Settings Meta Box - Offer Price
 *
 * @version 2.9.0
 * @since   2.9.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

return array(
	array(
		'title'    => __( 'Enable Offer Price', 'woocommerce-jetpack' ),
		'name'     => 'wcj_offer_price_enabled',
		'default'  => 'no',
		'type'     => 'select',
		'options'  => array(
			'yes' => __( 'Yes', 'woocommerce-jetpack' ),
			'no'  => __( 'No', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'    => __( 'Price Step', 'woocommerce-jetpack' ),
		'tooltip'  => __( 'Leave blank to use global value.', 'woocommerce-jetpack' ),
		'name'     => 'wcj_offer_price_price_step',
		'default'  => '',
		'type'     => 'number',
		'placeholder' => get_option( 'wcj_offer_price_price_step', get_option( 'woocommerce_price_num_decimals' ) ),
	),
);