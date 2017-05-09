<?php
/**
 * Booster for WooCommerce - Functions - Invoicing
 *
 * @version 2.7.0
 * @author  Algoritmika Ltd.
 */

if ( ! function_exists( 'wcj_get_invoice_types' ) ) {
	/*
	 * wcj_get_invoice_types.
	 *
	 * @version 2.7.0
	 */
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

			/* array(
				'id'    => 'custom_doc',
				'title' => __( 'Custom Document', 'woocommerce-jetpack' ),
				'desc'  => __( 'Custom Documents', 'woocommerce-jetpack' ),
				'defaults'  => array( 'init' => 'disabled', ),
//				'icon'  => '\e019',
			), */
		);

		$total_custom_docs = get_option( 'wcj_invoicing_custom_doc_total_number', 1 );
		for ( $i = 1; $i <= $total_custom_docs; $i++ ) {
			$invoice_types[] = array(
				'id'    => ( 1 == $i ? 'custom_doc' : 'custom_doc' . '_' . $i ),
				'title' => __( 'Custom Document', 'woocommerce-jetpack' ) . ' #' . $i,
				'desc'  => __( 'Custom Documents', 'woocommerce-jetpack' ) . ' #' . $i,
				'defaults'  => array( 'init' => 'disabled', ),
			);
		}

		return $invoice_types;
	}
}

if ( ! function_exists( 'wcj_get_enabled_invoice_types' ) ) {
	/*
	 * wcj_get_enabled_invoice_types.
	 */
	function wcj_get_enabled_invoice_types() {
		$invoice_types = wcj_get_invoice_types();
		$enabled_invoice_types = array();
		foreach ( $invoice_types as $k => $invoice_type ) {
			$z = ( 0 === $k ) ? get_option( 'wcj_invoicing_' . $invoice_type['id'] . '_create_on' ) : apply_filters( 'booster_get_option', 'disabled', get_option( 'wcj_invoicing_' . $invoice_type['id'] . '_create_on' ) );
			if ( 'disabled' === $z )
				continue;
			$enabled_invoice_types[] = $invoice_type;
		}
		return $enabled_invoice_types;
	}
}

if ( ! function_exists( 'wcj_get_enabled_invoice_types_ids' ) ) {
	/*
	 * wcj_get_enabled_invoice_types_ids.
	 */
	function wcj_get_enabled_invoice_types_ids() {
		$invoice_types = wcj_get_enabled_invoice_types();
		$invoice_types_ids = array();
		foreach( $invoice_types as $invoice_type ) {
			$invoice_types_ids[] = $invoice_type['id'];
		}
		return $invoice_types_ids;
	}
}

if ( ! function_exists( 'wcj_get_pdf_invoice' ) ) {
	/*
	 * wcj_get_pdf_invoice.
	 */
	function wcj_get_pdf_invoice( $order_id, $invoice_type_id ) {
		$the_invoice = new WCJ_PDF_Invoice( $order_id, $invoice_type_id );
		return $the_invoice;
	}
}

if ( ! function_exists( 'wcj_get_invoice' ) ) {
	/*
	 * wcj_get_invoice.
	 */
	function wcj_get_invoice( $order_id, $invoice_type_id ) {
		$the_invoice = new WCJ_Invoice( $order_id, $invoice_type_id );
		return $the_invoice;
	}
}

if ( ! function_exists( 'wcj_get_invoice_date' ) ) {
	/*
	 * wcj_get_invoice_date.
	 */
	function wcj_get_invoice_date( $order_id, $invoice_type_id, $extra_days, $date_format ) {
		$the_invoice = wcj_get_invoice( $order_id, $invoice_type_id );
		$extra_days_in_sec = $extra_days  * 24 * 60 * 60;
		return date_i18n( $date_format, date( $the_invoice->get_invoice_date() ) + $extra_days_in_sec );
	}
}

if ( ! function_exists( 'wcj_get_invoice_number' ) ) {
	/*
	 * wcj_get_invoice_number.
	 */
	function wcj_get_invoice_number( $order_id, $invoice_type_id ) {
		$the_invoice = wcj_get_invoice( $order_id, $invoice_type_id );
		return $the_invoice->get_invoice_number();
	}
}

if ( ! function_exists( 'wcj_delete_invoice' ) ) {
	/*
	 * wcj_delete_invoice.
	 */
	function wcj_delete_invoice( $order_id, $invoice_type_id ) {
		$the_invoice = wcj_get_invoice( $order_id, $invoice_type_id );
		$the_invoice->delete();
	}
}

if ( ! function_exists( 'wcj_create_invoice' ) ) {
	/*
	 * wcj_create_invoice.
	 */
	function wcj_create_invoice( $order_id, $invoice_type_id, $date = '' ) {
		$the_invoice = wcj_get_invoice( $order_id, $invoice_type_id );
		$the_invoice->create( $date );
	}
}

if ( ! function_exists( 'wcj_is_invoice_created' ) ) {
	/*
	 * wcj_is_invoice_created.
	 */
	function wcj_is_invoice_created( $order_id, $invoice_type_id ) {
		$the_invoice = wcj_get_invoice( $order_id, $invoice_type_id );
		return $the_invoice->is_created();
	}
}
