<?php
/**
 * WooCommerce Jetpack PDF Invoicing Emails
 *
 * The WooCommerce Jetpack PDF Invoicing Emails class.
 *
 * @version 2.4.8
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_PDF_Invoicing_Emails' ) ) :

class WCJ_PDF_Invoicing_Emails extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.4.8
	 */
	function __construct() {

		$this->id         = 'pdf_invoicing_emails';
		$this->parent_id  = 'pdf_invoicing';
		$this->short_desc = __( 'Email Options', 'woocommerce-jetpack' );
		$this->desc       = '';
		parent::__construct( 'submodule' );

		add_filter( 'init',  array( $this, 'add_hooks' ) );

		if ( $this->is_enabled() ) {
			if ( ! wcj_is_module_enabled( 'general' ) || 'no' === get_option( 'wcj_general_advanced_disable_save_sys_temp_dir', 'no' ) ) {
				add_filter( 'woocommerce_email_attachments', array( $this, 'add_pdf_invoice_email_attachment' ), PHP_INT_MAX, 3 );
			}
		}
	}

	/**
	 * add_hooks.
	 */
	function add_hooks() {
		add_filter( 'wcj_pdf_invoicing_emails_settings', array( $this, 'add_payment_gateways_pdf_invoicing_emails_settings' ), PHP_INT_MAX, 2 );
	}

	/**
	 * do_attach_for_payment_method.
	 */
	function do_attach_for_payment_method( $invoice_type_id, $payment_method ) {
		$included_gateways = get_option( 'wcj_invoicing_' . $invoice_type_id . '_payment_gateways', array() );
		if ( empty ( $included_gateways ) ) return true; // include all
		return ( in_array( $payment_method, $included_gateways ) ) ? true : false;
	}

	/**
	 * add_pdf_invoice_email_attachment.
	 */
	function add_pdf_invoice_email_attachment( $attachments, $status, $order ) {
		$invoice_types_ids = wcj_get_enabled_invoice_types_ids();
		foreach ( $invoice_types_ids as $invoice_type_id ) {
			if ( false === $this->do_attach_for_payment_method( $invoice_type_id, $order->payment_method ) ) {
				continue;
			}
			$send_on_statuses = get_option( 'wcj_invoicing_' . $invoice_type_id . '_attach_to_emails', array() );
			if ( '' == $send_on_statuses ) $send_on_statuses = array();
			if ( in_array( $status, $send_on_statuses ) ) {
				$the_invoice = wcj_get_pdf_invoice( $order->id, $invoice_type_id );
				$file_name = $the_invoice->get_pdf( 'F' );
				if ( '' != $file_name ) {
					$attachments[] = $file_name;
				}
			}
		}
		return $attachments;
	}

	/**
	 * add_payment_gateways_pdf_invoicing_emails_settings.
	 */
	function add_payment_gateways_pdf_invoicing_emails_settings( $settings, $invoice_type_id ) {
		global $woocommerce;
		$available_gateways = $woocommerce->payment_gateways->payment_gateways();
		foreach ( $available_gateways as $key => $gateway ) {
			$available_gateways_options_array[ $key ] = $gateway->title;
		}
		$settings[] = array(
			'title'             => __( 'Payment gateways to include', 'woocommerce' ),
			'id'                => 'wcj_invoicing_' . $invoice_type_id . '_payment_gateways',
			'type'              => 'multiselect',
			'class'             => 'chosen_select',
			'css'               => 'width: 450px;',
			'default'           => '',
			'options'           => $available_gateways_options_array,
			'custom_attributes' => array( 'data-placeholder' => __( 'Select some gateways. Leave blank to include all.', 'woocommerce-jetpack' ) ),
		);
		return $settings;
	}

	/**
	 * get_settings.
	 *
	 * @version 2.4.7
	 */
	function get_settings() {

		$settings = array();
		$invoice_types = ( 'yes' === get_option( 'wcj_invoicing_hide_disabled_docs_settings', 'no' ) ) ? wcj_get_enabled_invoice_types() : wcj_get_invoice_types();
		foreach ( $invoice_types as $invoice_type ) {

			$settings[] = array(
				'title' => strtoupper( $invoice_type['desc'] ),
				'type'  => 'title',
				'desc'  => '',
				'id'    => 'wcj_invoicing_' . $invoice_type['id'] . '_emails_options',
			);

			$available_emails = array(
				'new_order'                 => __( 'Admin - New Order', 'woocommerce' ),
				'cancelled_order'           => __( 'Admin - Cancelled Order', 'woocommerce' ),
				'customer_processing_order' => __( 'Customer - Processing Order', 'woocommerce' ),
				'customer_completed_order'  => __( 'Customer - Completed Order', 'woocommerce' ),
				'customer_invoice'          => __( 'Customer - Invoice', 'woocommerce' ),
				'customer_refunded_order'   => __( 'Customer - Refunded Order', 'woocommerce' ),
			);
			if ( 'yes' === get_option( 'wcj_emails_enabled', 'no' ) ) {
				for ( $i = 1; $i <= apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_emails_custom_emails_total_number', 1 ) ); $i++ ) {
					$available_emails[ 'wcj_custom' . '_' . $i ] = __( 'Custom', 'woocommerce-jetpack' ) . ' #' . $i;
				}
			}
			$settings[] = array(
				'title'         => __( 'Attach PDF to emails', 'woocommerce' ),
				'id'            => 'wcj_invoicing_' . $invoice_type['id'] . '_attach_to_emails',
				'type'          => 'multiselect',
				'class'         => 'chosen_select',
				'css'           => 'width: 450px;',
				'default'       => '',
				'options'       => $available_emails,
				'custom_attributes' => array( 'data-placeholder' => __( 'Select some emails', 'woocommerce' ) ),
			);

			$settings = apply_filters( 'wcj_pdf_invoicing_emails_settings', $settings, $invoice_type['id'] );

			$settings[] = array(
				'type' => 'sectionend',
				'id'   => 'wcj_invoicing_' . $invoice_type['id'] . '_emails_options',
			);
		}

		return $this->add_standard_settings( $settings );
	}
}

endif;

return new WCJ_PDF_Invoicing_Emails();
