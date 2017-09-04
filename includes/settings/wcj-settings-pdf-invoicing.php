<?php
/**
 * Booster for WooCommerce - Settings - PDF Invoicing - General
 *
 * @version 3.1.0
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
$status_change_hooks = array();
$order_statuses      = wcj_get_order_statuses( true );
foreach ( $order_statuses as $status => $desc ) {
	$status_change_hooks[ 'woocommerce_order_status_' . $status ] = sprintf( __( 'Create on Order Status %s', 'woocommerce-jetpack' ), $desc );
}
$create_on_array = array_merge(
	array(
		'disabled'                                          => __( 'Disabled', 'woocommerce-jetpack' ),
		'woocommerce_new_order'                             => __( 'Create on New Order', 'woocommerce-jetpack' ),
	),
	$status_change_hooks,
	array(
		'wcj_pdf_invoicing_create_on_any_refund'            => __( 'Create on Order Status Refunded and/or Order Partially Refunded', 'woocommerce-jetpack' ),
		'woocommerce_order_partially_refunded_notification' => __( 'Create on Order Partially Refunded', 'woocommerce-jetpack' ),
		'manual'                                            => __( 'Manual Only', 'woocommerce-jetpack' ),
	)
);
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
	array(
		'title'    => __( 'Report Tool Options', 'woocommerce-jetpack' ),
		'type'     => 'title',
		'id'       => 'wcj_pdf_invoicing_report_tool_options',
	),
	array(
		'title'    => __( 'Reports Filename', 'woocommerce-jetpack' ),
		'desc'     => wcj_message_replaced_values( array( '%site%', '%invoice_type%', '%year%', '%month%' ) ),
		'id'       => 'wcj_pdf_invoicing_report_tool_filename',
		'default'  => '%site%-%invoice_type%-%year%_%month%',
		'type'     => 'text',
		'class'    => 'widefat',
	),
	array(
		'title'    => __( 'CSV Separator', 'woocommerce-jetpack' ),
		'id'       => 'wcj_pdf_invoicing_report_tool_csv_separator',
		'default'  => ';',
		'type'     => 'text',
	),
	array(
		'title'    => __( 'CSV UTF-8 BOM', 'woocommerce-jetpack' ),
		'desc'     => __( 'Add', 'woocommerce-jetpack' ),
		'id'       => 'wcj_pdf_invoicing_report_tool_csv_add_utf_8_bom',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'title'    => __( 'Replace Periods with Commas in CSV Data', 'woocommerce-jetpack' ),
		'desc'     => __( 'Replace', 'woocommerce-jetpack' ),
		'id'       => 'wcj_pdf_invoicing_report_tool_csv_replace_periods_w_commas',
		'default'  => 'yes',
		'type'     => 'checkbox',
	),
	array(
		'type'     => 'sectionend',
		'id'       => 'wcj_pdf_invoicing_report_tool_options',
	),
) );
return $settings;
