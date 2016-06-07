<?php
/**
 * WooCommerce Jetpack Invoicing Functions
 *
 * @version 2.5.2
 * @author  Algoritmika Ltd.
 */

/*
 * wcj_get_invoice_types.
 *
 * @version 2.5.2
 */
if ( ! function_exists( 'wcj_get_invoice_types' ) ) {
	function wcj_get_invoice_types() {
		$invoice_types = array(
			array(
				'id'    => 'invoice',
				'title' => __( 'Invoice', 'woocommerce-jetpack' ),
				'desc'  => __( 'Invoices', 'woocommerce-jetpack' ),
				'defaults'  => array( 'init' => 'disabled', ),
//				'icon'  => '\e028',
			),

			array(
				'id'    => 'proforma_invoice',
				'title' => __( 'Proforma Invoice', 'woocommerce-jetpack' ),
				'desc'  => __( 'Proforma Invoices', 'woocommerce-jetpack' ),
				'defaults'  => array( 'init' => 'disabled', ),
//				'icon'  => '\e030',
			),

			array(
				'id'    => 'packing_slip',
				'title' => __( 'Packing Slip', 'woocommerce-jetpack' ),
				'desc'  => __( 'Packing Slips', 'woocommerce-jetpack' ),
				'defaults'  => array( 'init' => 'disabled', ),
//				'icon'  => '\e019',
			),

			array(
				'id'    => 'credit_note',
				'title' => __( 'Credit Note', 'woocommerce-jetpack' ),
				'desc'  => __( 'Credit Notes', 'woocommerce-jetpack' ),
				'defaults'  => array( 'init' => 'disabled', ),
//				'icon'  => '\e019',
			),

			array(
				'id'    => 'custom_doc',
				'title' => __( 'Custom Document', 'woocommerce-jetpack' ),
				'desc'  => __( 'Custom Documents', 'woocommerce-jetpack' ),
				'defaults'  => array( 'init' => 'disabled', ),
//				'icon'  => '\e019',
			),
		);
		return $invoice_types;
	}
}

/*
 * wcj_get_enabled_invoice_types.
 */
if ( ! function_exists( 'wcj_get_enabled_invoice_types' ) ) {
	function wcj_get_enabled_invoice_types() {
		$invoice_types = wcj_get_invoice_types();
		$enabled_invoice_types = array();
		foreach ( $invoice_types as $k => $invoice_type ) {
			$z = ( 0 === $k ) ? get_option( 'wcj_invoicing_' . $invoice_type['id'] . '_create_on' ) : apply_filters( 'wcj_get_option_filter', 'disabled', get_option( 'wcj_invoicing_' . $invoice_type['id'] . '_create_on' ) );
			if ( 'disabled' === $z )
				continue;
			$enabled_invoice_types[] = $invoice_type;
		}
		return $enabled_invoice_types;
	}
}

/*
 * wcj_get_enabled_invoice_types_ids.
 */
 if ( ! function_exists( 'wcj_get_enabled_invoice_types_ids' ) ) {
	function wcj_get_enabled_invoice_types_ids() {
		$invoice_types = wcj_get_enabled_invoice_types();
		$invoice_types_ids = array();
		foreach( $invoice_types as $invoice_type ) {
			$invoice_types_ids[] = $invoice_type['id'];
		}
		return $invoice_types_ids;
	}
}

/*
 * wcj_get_pdf_invoice.
 */
if ( ! function_exists( 'wcj_get_pdf_invoice' ) ) {
	function wcj_get_pdf_invoice( $order_id, $invoice_type_id ) {
		$the_invoice = new WCJ_PDF_Invoice( $order_id, $invoice_type_id );
		return $the_invoice;
	}
}

/*
 * wcj_get_invoice.
 */
if ( ! function_exists( 'wcj_get_invoice' ) ) {
	function wcj_get_invoice( $order_id, $invoice_type_id ) {
		$the_invoice = new WCJ_Invoice( $order_id, $invoice_type_id );
		return $the_invoice;
	}
}

/*
 * wcj_get_invoice_date.
 */
if ( ! function_exists( 'wcj_get_invoice_date' ) ) {
	function wcj_get_invoice_date( $order_id, $invoice_type_id, $extra_days, $date_format ) {
		$the_invoice = wcj_get_invoice( $order_id, $invoice_type_id );
		$extra_days_in_sec = $extra_days  * 24 * 60 * 60;
		return date_i18n( $date_format, date( $the_invoice->get_invoice_date() ) + $extra_days_in_sec );
	}
}

/*
 * wcj_get_invoice_number.
 */
if ( ! function_exists( 'wcj_get_invoice_number' ) ) {
	function wcj_get_invoice_number( $order_id, $invoice_type_id ) {
		$the_invoice = wcj_get_invoice( $order_id, $invoice_type_id );
		return $the_invoice->get_invoice_number();
	}
}

/*
 * wcj_delete_invoice.
 */
if ( ! function_exists( 'wcj_delete_invoice' ) ) {
	function wcj_delete_invoice( $order_id, $invoice_type_id ) {
		$the_invoice = wcj_get_invoice( $order_id, $invoice_type_id );
		$the_invoice->delete();
	}
}

/*
 * wcj_create_invoice.
 */
if ( ! function_exists( 'wcj_create_invoice' ) ) {
	function wcj_create_invoice( $order_id, $invoice_type_id, $date = '' ) {
		$the_invoice = wcj_get_invoice( $order_id, $invoice_type_id );
		$the_invoice->create( $date );
	}
}

/*
 * wcj_is_invoice_created.
 */
if ( ! function_exists( 'wcj_is_invoice_created' ) ) {
	function wcj_is_invoice_created( $order_id, $invoice_type_id ) {
		$the_invoice = wcj_get_invoice( $order_id, $invoice_type_id );
		return $the_invoice->is_created();
	}
}
