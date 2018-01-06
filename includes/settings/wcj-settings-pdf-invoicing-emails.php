<?php
/**
 * Booster for WooCommerce - Settings - PDF Invoicing - Email Options
 *
 * @version 3.2.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$available_gateways = WC()->payment_gateways->payment_gateways();
foreach ( $available_gateways as $key => $gateway ) {
	$available_gateways_options_array[ $key ] = $gateway->title;
}
/* $available_emails = array(
	'new_order'                         => __( 'Admin - New Order', 'woocommerce-jetpack' ),
	'cancelled_order'                   => __( 'Admin - Cancelled Order', 'woocommerce-jetpack' ),
	'failed_order'                      => __( 'Admin - Failed Order', 'woocommerce-jetpack' ),
	'customer_processing_order'         => __( 'Customer - Processing Order', 'woocommerce-jetpack' ),
	'customer_on_hold_order'            => __( 'Customer - On-Hold Order', 'woocommerce-jetpack' ),
	'customer_completed_order'          => __( 'Customer - Completed Order', 'woocommerce-jetpack' ),
	'customer_invoice'                  => __( 'Customer - Invoice', 'woocommerce-jetpack' ),
	'customer_refunded_order'           => __( 'Customer - Refunded Order', 'woocommerce-jetpack' ),
	'customer_partially_refunded_order' => __( 'Customer - Partially Refunded Order', 'woocommerce-jetpack' ),
	'customer_note'                     => __( 'Customer - Note', 'woocommerce-jetpack' ),
	'customer_new_account'              => __( 'Customer - New Account', 'woocommerce-jetpack' ),
	'customer_reset_password'           => __( 'Customer - Reset Password', 'woocommerce-jetpack' ),
);
if ( wcj_is_module_enabled( 'emails' ) ) {
	for ( $i = 1; $i <= apply_filters( 'booster_option', 1, get_option( 'wcj_emails_custom_emails_total_number', 1 ) ); $i++ ) {
		$available_emails[ 'wcj_custom' . '_' . $i ] = __( 'Custom', 'woocommerce-jetpack' ) . ' #' . $i;
	}
} */
$available_emails = array();
$wc_emails = WC()->mailer()->get_emails();
foreach ( $wc_emails as $wc_email ) {
	if ( isset( $wc_email->id ) && isset( $wc_email->title ) ) {
		$available_emails[ $wc_email->id ] = $wc_email->title;
	}
}
$settings = array();
$invoice_types = ( 'yes' === get_option( 'wcj_invoicing_hide_disabled_docs_settings', 'no' ) ) ? wcj_get_enabled_invoice_types() : wcj_get_invoice_types();
foreach ( $invoice_types as $invoice_type ) {
	$settings = array_merge( $settings, array(
		array(
			'title'    => $invoice_type['title'],
			'type'     => 'title',
			'desc'     => '',
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_emails_options',
		),
		array(
			'title'    => __( 'Attach PDF to emails', 'woocommerce' ),
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_attach_to_emails',
			'type'     => 'multiselect',
			'class'    => 'chosen_select',
			'css'      => 'width: 450px;',
			'default'  => '',
			'options'  => $available_emails,
			'custom_attributes' => array( 'data-placeholder' => __( 'Select some emails', 'woocommerce' ) ),
		),
		array(
			'title'    => __( 'Payment gateways to include', 'woocommerce' ),
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_payment_gateways',
			'type'     => 'multiselect',
			'class'    => 'chosen_select',
			'css'      => 'width: 450px;',
			'default'  => '',
			'options'  => $available_gateways_options_array,
			'custom_attributes' => array( 'data-placeholder' => __( 'Select some gateways. Leave blank to include all.', 'woocommerce-jetpack' ) ),
		),
		array(
			'type'     => 'sectionend',
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_emails_options',
		),
	) );
}
return $settings;
