<?php
/**
 * Booster for WooCommerce - PDF Invoicing - Email Options
 *
 * @version 4.5.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_PDF_Invoicing_Emails' ) ) :
		/**
		 * WCJ_PDF_Invoicing_Emails.
		 *
		 * @version 3.8.0
		 */
	class WCJ_PDF_Invoicing_Emails extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 3.8.0
		 */
		public function __construct() {

			$this->id         = 'pdf_invoicing_emails';
			$this->parent_id  = 'pdf_invoicing';
			$this->short_desc = __( 'Email Options', 'woocommerce-jetpack' );
			$this->desc       = '';
			parent::__construct( 'submodule' );

			if ( $this->is_enabled() ) {
				if ( 'no' === wcj_get_option( 'wcj_general_advanced_disable_save_sys_temp_dir', 'no' ) ) {
					add_filter( 'woocommerce_email_attachments', array( $this, 'add_pdf_invoice_email_attachment' ), PHP_INT_MAX, 3 );
				}
			}
		}

		/**
		 * Do_attach_for_payment_method.
		 *
		 * @version 2.8.0
		 * @param int    $invoice_type_id Get invoice ID.
		 * @param string $payment_method Get payment methods.
		 */
		public function do_attach_for_payment_method( $invoice_type_id, $payment_method ) {
			$included_gateways = wcj_get_option( 'wcj_invoicing_' . $invoice_type_id . '_payment_gateways', array() );
			if ( empty( $included_gateways ) ) {
				return true; // include all.
			}
			return ( in_array( $payment_method, $included_gateways, true ) );
		}

		/**
		 * Add_pdf_invoice_email_attachment.
		 *
		 * @version 4.5.0
		 * @param array  $attachments Get attachments.
		 * @param string $status Get status.
		 * @param array  $order Get order.
		 */
		public function add_pdf_invoice_email_attachment( $attachments, $status, $order ) {
			if ( ! $order || ! is_object( $order ) ) {
				return $attachments;
			}
			if ( 'WC_Vendor_Stores_Order' === get_class( $order ) ) {
				$order = $order->get_parent_order( wcj_get_order_id( $order ) );
			}
			if ( ! is_a( $order, 'WC_Order' ) ) {
				return $attachments;
			}
			$invoice_types_ids = wcj_get_enabled_invoice_types_ids();
			$order_id          = wcj_get_order_id( $order );
			foreach ( $invoice_types_ids as $invoice_type_id ) {
				if ( false === $this->do_attach_for_payment_method( $invoice_type_id, wcj_order_get_payment_method( $order ) ) ) {
					continue;
				}
				if ( ! wcj_is_invoice_created( $order_id, $invoice_type_id ) ) {
					continue;
				}
				$send_on_statuses = wcj_get_option( 'wcj_invoicing_' . $invoice_type_id . '_attach_to_emails', array() );
				if ( '' === $send_on_statuses ) {
					$send_on_statuses = array();
				}
				if ( in_array( $status, $send_on_statuses, true ) ) {
					$the_invoice = wcj_get_pdf_invoice( $order_id, $invoice_type_id );
					$file_name   = $the_invoice->get_pdf( 'F' );
					if ( '' !== $file_name ) {
						$attachments[] = $file_name;
					}
				}
			}
			return $attachments;
		}

	}

endif;

return new WCJ_PDF_Invoicing_Emails();
