<?php
/**
 * Booster for WooCommerce - Settings Meta Box - Orders
 *
 * @version 7.1.4
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/meta-boxs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
if ( true === wcj_is_hpos_enabled() ) {
	$order_id           = isset( $_REQUEST['id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['id'] ) ) : ''; //phpcs:ignore WordPress.Security.NonceVerification
	$woo_order_currency = 'currency';
} else {
	$order_id           = get_the_ID();
	$woo_order_currency = 'order_currency';
}
$_order = wcj_get_order( $order_id );
if ( ! $_order || false === $_order ) {
	return array();
}
$options = array(
	array(
		'title'   => __( 'Order Currency', 'woocommerce-jetpack' ),
		'tooltip' => __( 'Save order after you change this field.', 'woocommerce-jetpack' ),
		'name'    => ( 'filter' === wcj_get_option( 'wcj_order_admin_currency_method', 'filter' ) ? 'wcj_order_currency' : $woo_order_currency ),
		'default' => wcj_get_order_currency( $_order ),
		'type'    => 'select',
		'options' => wcj_get_woocommerce_currencies_and_symbols(),
	),
);
return $options;
