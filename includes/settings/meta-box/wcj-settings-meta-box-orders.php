<?php
/**
 * Booster for WooCommerce - Settings Meta Box - Orders
 *
 * @version 3.9.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$order_id = get_the_ID();
$_order = wc_get_order( $order_id );
if ( ! $_order ) {
	return array();
}
$options = array(
	array(
		'title'    => __( 'Order Currency', 'woocommerce-jetpack' ),
		'tooltip'  => __( 'Save order after you change this field.', 'woocommerce-jetpack' ),
		'name'     => ( 'filter' === wcj_get_option( 'wcj_order_admin_currency_method', 'filter' ) ? 'wcj_order_currency' : 'order_currency' ),
		'default'  => wcj_get_order_currency( $_order ),
		'type'     => 'select',
		'options'  => wcj_get_woocommerce_currencies_and_symbols(),
	),
);
return $options;
