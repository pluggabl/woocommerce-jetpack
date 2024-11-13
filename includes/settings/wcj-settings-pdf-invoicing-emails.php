<?php
/**
 * Booster for WooCommerce - Settings - PDF Invoicing - Email Options
 *
 * @version 7.2.4
 * @since   2.8.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$available_gateways = WC()->payment_gateways->payment_gateways();
foreach ( $available_gateways as $key => $gateway ) {
	$available_gateways_options_array[ $key ] = $gateway->title;
}
$available_emails = array();
$wc_emails        = WC()->mailer()->get_emails();
foreach ( $wc_emails as $wc_email ) {
	if ( isset( $wc_email->id ) && isset( $wc_email->title ) ) {
		$available_emails[ $wc_email->id ] = $wc_email->title;
	}
}

$invoice_types = ( 'yes' === wcj_get_option( 'wcj_invoicing_hide_disabled_docs_settings', 'no' ) ? wcj_get_enabled_invoice_types() : wcj_get_invoice_types() );
$tab_ids       = array();
foreach ( $invoice_types as $invoice_type ) {
	$tab_ids[ 'pdf_invoicing_emails_' . $invoice_type['id'] . '_tab' ] = $invoice_type['title'];
}

$settings = array(
	array(
		'type'              => 'module_head',
		'title'             => __( 'Email Options', 'woocommerce-jetpack' ),
		'desc'              => __( 'PDF Invoicing : Email Options Settings' ),
		'icon'              => 'pr-sm-icn.png',
		'module_reset_link' => '<a style="width:auto;" onclick="return confirm(\'' . __( 'Are you sure? This will reset module to default settings.', 'woocommerce-jetpack' ) . '\')" class="wcj_manage_settting_btn wcj_tab_end_save_btn" href="' . esc_url(
			add_query_arg(
				array(
					'wcj_reset_settings' => $this->id,
					'wcj_reset_settings-' . $this->id . '-nonce' => wp_create_nonce( 'wcj_reset_settings' ),
				)
			)
		) . '">' . __( 'Reset settings', 'woocommerce-jetpack' ) . '</a>',
	),
	array(
		'id'   => 'pdf_invoicing_emails_options',
		'type' => 'sectionend',
	),
	array(
		'id'      => 'pdf_invoicing_emails_options',
		'type'    => 'tab_ids',
		'tab_ids' => $tab_ids,
	),
);

foreach ( $invoice_types as $invoice_type ) {
	$settings = array_merge(
		$settings,
		array(
			array(
				'id'   => 'pdf_invoicing_emails_' . $invoice_type['id'] . '_tab',
				'type' => 'tab_start',
			),
			array(
				'title' => $invoice_type['title'],
				'type'  => 'title',
				'desc'  => '',
				'id'    => 'wcj_invoicing_' . $invoice_type['id'] . '_emails_options',
			),
			array(
				'title'             => __( 'Attach PDF to emails', 'woocommerce' ),
				'id'                => 'wcj_invoicing_' . $invoice_type['id'] . '_attach_to_emails',
				'type'              => 'multiselect',
				'class'             => 'chosen_select',
				'css'               => 'width: 450px;',
				'default'           => '',
				'options'           => $available_emails,
				'custom_attributes' => array( 'data-placeholder' => __( 'Select some emails', 'woocommerce' ) ),
			),
			array(
				'title'             => __( 'Payment gateways to include', 'woocommerce' ),
				'id'                => 'wcj_invoicing_' . $invoice_type['id'] . '_payment_gateways',
				'type'              => 'multiselect',
				'class'             => 'chosen_select',
				'css'               => 'width: 450px;',
				'default'           => '',
				'options'           => $available_gateways_options_array,
				'custom_attributes' => array( 'data-placeholder' => __( 'Select some gateways. Leave blank to include all.', 'woocommerce-jetpack' ) ),
			),
			array(
				'type' => 'sectionend',
				'id'   => 'wcj_invoicing_' . $invoice_type['id'] . '_emails_options',
			),
			array(
				'id'   => 'pdf_invoicing_emails_' . $invoice_type['id'] . '_tab',
				'type' => 'tab_end',
			),
		)
	);
}
return $settings;
