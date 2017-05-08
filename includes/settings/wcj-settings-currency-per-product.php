<?php
/**
 * Booster for WooCommerce - Settings - Currency per Product
 *
 * @version 2.8.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$currency_from = get_woocommerce_currency();
$all_currencies = wcj_get_currencies_names_and_symbols();
/*
foreach ( $all_currencies as $currency_key => $currency_name ) {
	if ( $currency_from == $currency_key ) {
		unset( $all_currencies[ $currency_key ] );
	}
}
*/
$settings = array(
	array(
		'title'    => __( 'Cart and Checkout Behaviour Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_currency_per_product_cart_options',
	),
	array(
		'title'    => __( 'Cart and Checkout Behaviour', 'woocommerce-jetpack' ),
		'id'       => 'wcj_currency_per_product_cart_checkout',
		'default'  => 'convert_shop_default',
		'type'     => 'select',
		'options'  => array(
			'convert_shop_default'  => __( 'Convert to shop default currency', 'woocommerce-jetpack' ),
			'leave_one_product'     => __( 'Leave product currency (allow only one product to be added to cart)', 'woocommerce-jetpack' ),
			'leave_same_currency'   => __( 'Leave product currency (allow only same currency products to be added to cart)', 'woocommerce-jetpack' ),
			'convert_last_product'  => __( 'Convert to currency of last product in cart', 'woocommerce-jetpack' ),
			'convert_first_product' => __( 'Convert to currency of first product in cart', 'woocommerce-jetpack' ),
		),
	),
	array(
		'title'    => __( 'Message', 'woocommerce-jetpack' ) . ': ' . __( 'Leave product currency (allow only one product to be added to cart)', 'woocommerce-jetpack' ),
		'id'       => 'wcj_currency_per_product_cart_checkout_leave_one_product',
		'default'  => __( 'Only one product can be added to the cart. Clear the cart or finish the order, before adding another product to the cart.', 'woocommerce-jetpack' ),
		'type'     => 'textarea',
		'css'      => 'min-width:300px;width:66%',
	),
	array(
		'title'    => __( 'Message', 'woocommerce-jetpack' ) . ': ' . __( 'Leave product currency (allow only same currency products to be added to cart)', 'woocommerce-jetpack' ),
		'id'       => 'wcj_currency_per_product_cart_checkout_leave_same_currency',
		'default'  => __( 'Only products with same currency can be added to the cart. Clear the cart or finish the order, before adding products with another currency to the cart.', 'woocommerce-jetpack' ),
		'type'     => 'textarea',
		'css'      => 'min-width:300px;width:66%',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_currency_per_product_cart_options',
	),
	array(
		'title'    => __( 'Exchange Rates Updates Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_currency_per_product_exchange_rate_update_options',
	),
	array(
		'title'    => __( 'Exchange Rates Updates', 'woocommerce-jetpack' ),
		'id'       => 'wcj_currency_per_product_exchange_rate_update',
		'default'  => 'manual',
		'type'     => 'select',
		'options'  => array(
			'manual' => __( 'Enter Rates Manually', 'woocommerce-jetpack' ),
			'auto'   => __( 'Automatically via Currency Exchange Rates module', 'woocommerce-jetpack' ),
		),
		'desc'     => ( '' == apply_filters( 'booster_get_message', '', 'desc' ) ) ?
			__( 'Visit', 'woocommerce-jetpack' ) . ' <a href="' . admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=prices_and_currencies&section=currency_exchange_rates' ) . '">' . __( 'Currency Exchange Rates module', 'woocommerce-jetpack' ) . '</a>'
			:
			apply_filters( 'booster_get_message', '', 'desc' ),
		'custom_attributes' => apply_filters( 'booster_get_message', '', 'disabled' ),
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_currency_per_product_exchange_rate_update_options',
	),
	array(
		'title'    => __( 'Currencies Options', 'woocommerce-jetpack' ),
		'desc'     => __( 'Exchange rates for currencies won\'t be used if "Cart and Checkout Behaviour" is set to one of "Leave product currency ..." options.', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_currency_per_product_currencies_options',
	),
	array(
		'title'    => __( 'Total Currencies', 'woocommerce-jetpack' ),
		'id'       => 'wcj_currency_per_product_total_number',
		'default'  => 1,
		'type'     => 'custom_number',
		'desc'     => apply_filters( 'booster_get_message', '', 'desc' ),
		'custom_attributes' => array_merge(
			is_array( apply_filters( 'booster_get_message', '', 'readonly' ) ) ? apply_filters( 'booster_get_message', '', 'readonly' ) : array(),
			array( 'step' => '1', 'min'  => '1', )
		),
	),
);
$total_number = apply_filters( 'booster_get_option', 1, get_option( 'wcj_currency_per_product_total_number', 1 ) );
for ( $i = 1; $i <= $total_number; $i++ ) {
	$currency_to = get_option( 'wcj_currency_per_product_currency_' . $i, $currency_from );
	$custom_attributes = array(
		'currency_from'        => $currency_from,
		'currency_to'          => $currency_to,
		'multiply_by_field_id' => 'wcj_currency_per_product_exchange_rate_' . $i,
	);
	if ( $currency_from == $currency_to ) {
		$custom_attributes['disabled'] = 'disabled';
	}
	$settings = array_merge( $settings, array(
		array(
			'title'    => __( 'Currency', 'woocommerce-jetpack' ) . ' #' . $i,
			'id'       => 'wcj_currency_per_product_currency_' . $i,
			'default'  => $currency_from,
			'type'     => 'select',
			'options'  => $all_currencies,
			'css'      => 'width:250px;',
		),
		array(
			'title'                    => '',
			'id'                       => 'wcj_currency_per_product_exchange_rate_' . $i,
			'default'                  => 1,
			'type'                     => 'exchange_rate',
			'custom_attributes'        => array( 'step' => '0.000001', 'min'  => '0', ),
			'custom_attributes_button' => $custom_attributes,
			'css'                      => 'width:100px;',
			'value'                    => $currency_from . '/' . $currency_to,
		),
	) );
}
$settings = array_merge( $settings, array(
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_currency_per_product_currencies_options',
	),
) );
return $settings;
