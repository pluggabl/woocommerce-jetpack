<?php
/**
 * Booster for WooCommerce - Shortcodes - Invoices
 *
 * @version 5.6.8
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Invoices_Shortcodes' ) ) :

		/**
		 * WCJ_Invoices_Shortcodes.
		 *
		 * @version 5.5.0
		 */
	class WCJ_Invoices_Shortcodes extends WCJ_Shortcodes {

		/**
		 * Constructor.
		 *
		 * @version 5.5.0
		 */
		public function __construct() {

			$this->the_shortcodes = array(

				'wcj_invoice_number',
				'wcj_proforma_invoice_number',
				'wcj_packing_slip_number',
				'wcj_credit_note_number',
				'wcj_custom_doc_number',
				'wcj_encode_img',
				'wcj_invoice_date',
				'wcj_proforma_invoice_date',
				'wcj_packing_slip_date',
				'wcj_credit_note_date',
				'wcj_custom_doc_date',

			);

			$this->the_atts = array(
				'order_id'     => 0,
				'date_format'  => wcj_get_option( 'date_format' ),
				'days'         => 0,
				'invoice_type' => 'invoice',
				'doc_nr'       => 1,
			);

			parent::__construct();
		}

		/**
		 * Init_atts.
		 *
		 * @version 5.6.8
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function init_atts( $atts ) {
			// Atts.
			// phpcs:disable WordPress.Security.NonceVerification
			if ( 0 === $atts['order_id'] ) {
				$atts['order_id'] = ( isset( $_GET['order_id'] ) ) ? sanitize_text_field(
					wp_unslash(
						$_GET['order_id']
					)
				) : get_the_ID();
				if ( 0 === $atts['order_id'] ) {
					return false;
				}
			}
			if ( 'shop_order' !== get_post_type( $atts['order_id'] ) ) {
				return false;
			}
			// phpcs:enable WordPress.Security.NonceVerification

			return $atts;
		}

		/**
		 * Wcj_encode_img.
		 *
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_encode_img( $atts ) {

			if ( isset( $atts['srcs'] ) ) {
				$img_base64_encoded = $atts['srcs'];
				$img                = '<img  src="@' . preg_replace( '#^data:image/[^;]+;base64,#', '', $img_base64_encoded ) . '">';

				return $img;

			}

		}

		/**
		 * Wcj_invoice_date.
		 *
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_invoice_date( $atts ) {
			return wcj_get_invoice_date( $atts['order_id'], $atts['invoice_type'], $atts['days'], $atts['date_format'] );
		}

		/**
		 * Wcj_proforma_invoice_date.
		 *
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_proforma_invoice_date( $atts ) {
			return wcj_get_invoice_date( $atts['order_id'], 'proforma_invoice', $atts['days'], $atts['date_format'] );
		}

		/**
		 * Wcj_packing_slip_date.
		 *
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_packing_slip_date( $atts ) {
			return wcj_get_invoice_date( $atts['order_id'], 'packing_slip', $atts['days'], $atts['date_format'] );
		}

		/**
		 * Wcj_credit_note_date.
		 *
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_credit_note_date( $atts ) {
			return wcj_get_invoice_date( $atts['order_id'], 'credit_note', $atts['days'], $atts['date_format'] );
		}

		/**
		 * Wcj_custom_doc_date.
		 *
		 * @version 2.7.0
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_custom_doc_date( $atts ) {
			$invoice_type_id = ( 1 === $atts['doc_nr'] ) ? 'custom_doc' : 'custom_doc_' . $atts['doc_nr'];
			return wcj_get_invoice_date( $atts['order_id'], $invoice_type_id, $atts['days'], $atts['date_format'] );
		}

		/**
		 * Wcj_invoice_number.
		 *
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_invoice_number( $atts ) {
			return wcj_get_invoice_number( $atts['order_id'], $atts['invoice_type'] );
		}

		/**
		 * Wcj_proforma_invoice_number.
		 *
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_proforma_invoice_number( $atts ) {
			return wcj_get_invoice_number( $atts['order_id'], 'proforma_invoice' );
		}

		/**
		 * Wcj_packing_slip_number.
		 *
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_packing_slip_number( $atts ) {
			return wcj_get_invoice_number( $atts['order_id'], 'packing_slip' );
		}

		/**
		 * Wcj_credit_note_number.
		 *
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_credit_note_number( $atts ) {
			return wcj_get_invoice_number( $atts['order_id'], 'credit_note' );
		}

		/**
		 * Wcj_custom_doc_number.
		 *
		 * @version 2.7.0
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_custom_doc_number( $atts ) {
			$invoice_type_id = ( 1 === $atts['doc_nr'] ) ? 'custom_doc' : 'custom_doc_' . $atts['doc_nr'];
			return wcj_get_invoice_number( $atts['order_id'], $invoice_type_id );
		}

	}

endif;

return new WCJ_Invoices_Shortcodes();
