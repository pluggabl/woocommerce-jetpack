<?php
/**
 * Booster for WooCommerce - Settings - PDF Invoicing - General
 *
 * @version 2.8.0
 * @since   2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$settings = array(
	array(
		'title'    => __( 'PDF Invoicing General Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_pdf_invoicing_options',
	),
);
// Hooks Array
$create_on_array = array();
$create_on_array['disabled'] = __( 'Disabled', 'woocommerce-jetpack' );
$create_on_array['woocommerce_new_order'] = __( 'Create on New Order', 'woocommerce-jetpack' );
$order_statuses = wcj_get_order_statuses( true );
foreach ( $order_statuses as $status => $desc ) {
	$create_on_array[ 'woocommerce_order_status_' . $status ] = __( 'Create on Order Status', 'woocommerce-jetpack' ) . ' ' . $desc;
}
$create_on_array['manual'] = __( 'Manual Only', 'woocommerce-jetpack' );
// Settings
$invoice_types = wcj_get_invoice_types();
foreach ( $invoice_types as $k => $invoice_type ) {
	if ( 'custom_doc' === $invoice_type['id'] ) {
		$settings = array_merge( $settings, array(
			array(
				'title'    => __( 'Number of Custom Documents', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'Save changes after setting this number.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_invoicing_custom_doc_total_number',
				'default'  => 1,
				'type'     => 'custom_number',
				'custom_attributes' => array( 'min' => '1' ),
			),
		) );
	}
	$settings = array_merge( $settings, array(
		array(
			'title'    => $invoice_type['title'],
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_create_on',
			'default'  => 'disabled',
			'type'     => 'select',
			'class'    => 'chosen_select',
			'options'  => $create_on_array,
			'desc'     => ( 0 === $k ) ? '' : apply_filters( 'booster_get_message', '', 'desc' ),
			'custom_attributes' => ( 0 === $k ) ? '' : apply_filters( 'booster_get_message', '', 'disabled' ),
		),
		array(
			'id'       => 'wcj_invoicing_' . $invoice_type['id'] . '_skip_zero_total',
			'default'  => 'no',
			'type'     => 'checkbox',
			'desc'     => __( 'Do not create if order total equals zero', 'woocommerce-jetpack' ),
			'custom_attributes' => ( 0 === $k ) ? '' : apply_filters( 'booster_get_message', '', 'disabled' ),
		),
	) );
}
$settings = array_merge( $settings, array(
	array(
		'title'    => __( 'Hide Disabled Docs Settings', 'woocommerce-jetpack' ),
		'desc'     => __( 'Hide', 'woocommerce-jetpack' ),
		'id'       => 'wcj_invoicing_hide_disabled_docs_settings',
		'default'  => 'no',
		'type'     => 'checkbox',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_pdf_invoicing_options',
	),
) );
return $settings;
