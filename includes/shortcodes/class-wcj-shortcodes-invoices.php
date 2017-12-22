<?php
/**
 * Booster for WooCommerce - Shortcodes - Invoices
 *
 * @version 2.7.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Invoices_Shortcodes' ) ) :

class WCJ_Invoices_Shortcodes extends WCJ_Shortcodes {

	/**
	 * Constructor.
	 *
	 * @version 2.7.0
	 */
	function __construct() {

		$this->the_shortcodes = array(

			'wcj_invoice_number',
			'wcj_proforma_invoice_number',
			'wcj_packing_slip_number',
			'wcj_credit_note_number',
			'wcj_custom_doc_number',

			'wcj_invoice_date',
			'wcj_proforma_invoice_date',
			'wcj_packing_slip_date',
			'wcj_credit_note_date',
			'wcj_custom_doc_date',

		);

		$this->the_atts = array(
			'order_id'     => 0,
			'date_format'  => get_option( 'date_format' ),
			'days'         => 0,
			'invoice_type' => 'invoice',
			'doc_nr'       => 1,
		);

		parent::__construct();
    }

	/**
	 * init_atts.
	 */
	function init_atts( $atts ) {

		// Atts
		if ( 0 == $atts['order_id'] ) {
			$atts['order_id'] = ( isset( $_GET['order_id'] ) ) ? $_GET['order_id'] : get_the_ID();
			if ( 0 == $atts['order_id'] ) return false;
		}
		if ( 'shop_order' !== get_post_type( $atts['order_id'] ) ) return false;

		// Class properties
		/*if ( ! in_array( $atts['invoice_type'], wcj_enabled_invoice_types_ids() ) ) return false;
		$this->the_invoice = wc_get_invoice( $atts['order_id'], $atts['invoice_type'] );
		if ( ! $this->the_invoice ) return false;*/

		return $atts;
	}

	/**
	 * wcj_invoice_date.
	 */
    function wcj_invoice_date( $atts ) {
		return wcj_get_invoice_date( $atts['order_id'], $atts['invoice_type'], $atts['days'], $atts['date_format'] );
	}

	/**
	 * wcj_proforma_invoice_date.
	 */
	function wcj_proforma_invoice_date( $atts ) {
		return wcj_get_invoice_date( $atts['order_id'], 'proforma_invoice', $atts['days'], $atts['date_format'] );
	}

	/**
	 * wcj_packing_slip_date.
	 */
	function wcj_packing_slip_date( $atts ) {
		return wcj_get_invoice_date( $atts['order_id'], 'packing_slip', $atts['days'], $atts['date_format'] );
	}

	/**
	 * wcj_credit_note_date.
	 */
	function wcj_credit_note_date( $atts ) {
		return wcj_get_invoice_date( $atts['order_id'], 'credit_note', $atts['days'], $atts['date_format'] );
	}

	/**
	 * wcj_custom_doc_date.
	 *
	 * @version 2.7.0
	 */
	function wcj_custom_doc_date( $atts ) {
		$invoice_type_id = ( 1 == $atts['doc_nr'] ) ? 'custom_doc' :  'custom_doc' . '_' . $atts['doc_nr'];
		return wcj_get_invoice_date( $atts['order_id'], $invoice_type_id, $atts['days'], $atts['date_format'] );
	}

	/**
	 * wcj_invoice_number.
	 */
	function wcj_invoice_number( $atts ) {
		return wcj_get_invoice_number( $atts['order_id'], $atts['invoice_type'] );
	}

	/**
	 * wcj_proforma_invoice_number.
	 */
	function wcj_proforma_invoice_number( $atts ) {
		return wcj_get_invoice_number( $atts['order_id'], 'proforma_invoice' );
	}

	/**
	 * wcj_packing_slip_number.
	 */
	function wcj_packing_slip_number( $atts ) {
		return wcj_get_invoice_number( $atts['order_id'], 'packing_slip' );
	}

	/**
	 * wcj_credit_note_number.
	 */
	function wcj_credit_note_number( $atts ) {
		return wcj_get_invoice_number( $atts['order_id'], 'credit_note' );
	}

	/**
	 * wcj_custom_doc_number.
	 *
	 * @version 2.7.0
	 */
	function wcj_custom_doc_number( $atts ) {
		$invoice_type_id = ( 1 == $atts['doc_nr'] ) ? 'custom_doc' :  'custom_doc' . '_' . $atts['doc_nr'];
		return wcj_get_invoice_number( $atts['order_id'], $invoice_type_id );
	}

}

endif;

return new WCJ_Invoices_Shortcodes();
