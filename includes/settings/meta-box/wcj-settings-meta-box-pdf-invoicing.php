<?php
/**
 * Booster for WooCommerce - Settings Meta Box - PDF Invoicing
 *
 * @version 3.5.0
 * @since   3.5.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$order_id = get_the_ID();
$_order = wc_get_order( $order_id );
if ( ! $_order ) {
	return array();
}
$options = array();
$invoice_types = wcj_get_enabled_invoice_types();
if ( ! empty( $invoice_types ) ) {
	foreach ( $invoice_types as $invoice_type ) {
		if ( wcj_is_invoice_created( $order_id, $invoice_type['id'] ) ) {
			$options = array_merge( $options, array(
				array(
					'title'    => sprintf( __( '%s number', 'woocommerce-jetpack' ), $invoice_type['title'] ),
					'name'     => 'wcj_invoicing_' . $invoice_type['id'] . '_number_id',
					'default'  => '',
					'type'     => 'number',
				),
			) );
		}
	}
}
return $options;
