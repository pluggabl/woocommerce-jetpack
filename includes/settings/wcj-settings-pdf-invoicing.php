<?php
/**
 * Booster for WooCommerce - Settings - PDF Invoicing - General
 *
 * @version 7.0.0
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$settings = array(
	array(
		'id'   => 'pdf_invoicing_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'pdf_invoicing_options',
		'type'    => 'tab_ids',
		'tab_ids' => array(
			'pdf_invoicing_document_options_tab' => __( 'Documents Options', 'woocommerce-jetpack' ),
			'pdf_invoicing_general_options_tab'  => __( 'General options', 'woocommerce-jetpack' ),
			'pdf_invoicing_tools_options_tab'    => __( 'Tools', 'woocommerce-jetpack' ),
		),
	),
	array(
		'id'   => 'pdf_invoicing_document_options_tab',
		'type' => 'tab_start',
	),
	array(
		'title' => __( 'Documents Options', 'woocommerce-jetpack' ),
		'type'  => 'title',
		'id'    => 'wcj_pdf_invoicing_options',
	),
);
// Hooks Array.
$status_change_hooks = array();
$order_statuses      = wcj_get_order_statuses();
foreach ( $order_statuses as $status_key => $desc ) {
	/* translators: %s: translators Added */
	$status_change_hooks[ 'woocommerce_order_status_' . $status_key ] = sprintf( __( 'Create on Order Status %s', 'woocommerce-jetpack' ), $desc );
}
$create_on_array = array_merge(
	array(
		'woocommerce_new_order' => __( 'Create on New Order', 'woocommerce-jetpack' ),
	),
	$status_change_hooks,
	array(
		'woocommerce_order_partially_refunded_notification' => __( 'Create on Order Partially Refunded', 'woocommerce-jetpack' ),
		'manual' => __( 'Manually', 'woocommerce-jetpack' ),
	)
);
// Settings.
$invoice_types = wcj_get_invoice_types();
foreach ( $invoice_types as $k => $invoice_type ) {
	if ( 'custom_doc' === $invoice_type['id'] ) {
		$settings = array_merge(
			$settings,
			array(
				array(
					'id'   => 'pdf_invoicing_options',
					'type' => 'sectionend',
				),
				array(
					'id'   => 'pdf_invoicing_document_options_tab',
					'type' => 'tab_end',
				),
				array(
					'id'   => 'pdf_invoicing_general_options_tab',
					'type' => 'tab_start',
				),
				array(
					'title'             => __( 'Number of Custom Documents', 'woocommerce-jetpack' ),
					'desc_tip'          => __( 'Save changes after setting this number.', 'woocommerce-jetpack' ),
					'id'                => 'wcj_invoicing_custom_doc_total_number',
					'default'           => 1,
					'type'              => 'custom_number',
					'custom_attributes' => array(
						'min' => '1',
						'max' => '100',
					),
				),
			)
		);
	}
	$create_on_value = wcj_get_invoice_create_on( $invoice_type['id'] ); // for conversion (i.e. backward compatibility with Booster version <= 3.1.3).
	$settings        = array_merge(
		$settings,
		array(
			array(
				'title'             => $invoice_type['title'],
				'id'                => 'wcj_invoicing_' . $invoice_type['id'] . '_create_on',
				'default'           => '',
				'type'              => 'multiselect',
				'class'             => 'chosen_select',
				'options'           => $create_on_array,
				'desc'              => ( 0 === $k ) ? '' : apply_filters( 'booster_message', '', 'desc' ),
				'custom_attributes' => ( 0 === $k ) ? '' : apply_filters( 'booster_message', '', 'disabled' ),
			),
			array(
				'id'                => 'wcj_invoicing_' . $invoice_type['id'] . '_skip_zero_total',
				'default'           => 'no',
				'type'              => 'checkbox',
				'desc'              => __( 'Do not create if order total equals zero', 'woocommerce-jetpack' ),
				'custom_attributes' => ( 0 === $k ) ? '' : apply_filters( 'booster_message', '', 'disabled' ),
			),
		)
	);
}
$settings = array_merge(
	$settings,
	array(
		array(
			'id'   => 'wcj_pdf_invoicing_options',
			'type' => 'sectionend',
		),
		array(
			'id'   => 'pdf_invoicing_general_options_tab',
			'type' => 'tab_end',
		),
		array(
			'id'   => 'pdf_invoicing_tools_options_tab',
			'type' => 'tab_start',
		),
		array(
			'title'    => __( 'Module Tools', 'woocommerce-jetpack' ),
			'desc_tip' => __( 'To use tools, module must be enabled.', 'woocommerce-jetpack' ),
			'id'       => 'wcj_' . $this->id . '_module_tools',
			'type'     => 'custom_link',
			'link'     => ( $this->is_enabled() ) ?
			'<p><code><a href="' . esc_url( admin_url( 'admin.php?page=wcj-tools&tab=renumerate_invoices&wcj_tools_nonce=' . wp_create_nonce( 'wcj_tools' ) . '' ) ) . '">' . __( 'Renumerate Invoices', 'woocommerce-jetpack' ) . '</a></code>
			<p>
			<p><code><a href="' . esc_url( admin_url( 'admin.php?page=wcj-tools&tab=invoices_report&wcj_tools_nonce=' . wp_create_nonce( 'wcj_tools' ) . '' ) ) . '">' . __( 'Invoices Report', 'woocommerce-jetpack' ) . '</a></code>
			<p>
			' :
				'<p><code>' . __( 'Renumerate Invoices', 'woocommerce-jetpack' ) . '</code></p>
				<p><code>' . __( 'Invoices Report', 'woocommerce-jetpack' ) . '</code></p>
			',
		),
		array(
			'id'   => 'pdf_invoicing_tools_options_tab',
			'type' => 'tab_end',
		),
	)
);
return $settings;
