<?php
/**
 * Booster for WooCommerce Invoice
 *
 * @version 8.1.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Invoice' ) ) :

		/**
		 * WCJ_Invoice.
		 *
		 * @version 8.1.0
		 */
	class WCJ_Invoice {
		/**
		 * Order_id.
		 *
		 * @var $order_id.
		 */
		public $order_id;
		/**
		 * Invoice_type.
		 *
		 * @var $invoice_type
		 */
		public $invoice_type;
		/**
		 * Wcj_invoice_type.
		 *
		 * @var $wcj_invoice_type
		 */
		public $wcj_invoice_type;

		/**
		 * Request-scoped order object.
		 *
		 * @var WC_Order|false|null
		 */
		private $order = null;

		/**
		 * Constructor.
		 *
		 * @param int    $order_id Get order id.
		 * @param string $invoice_type  get invoice type.
		 */
		public function __construct( $order_id, $invoice_type ) {
			$this->order_id     = absint( $order_id );
			$this->invoice_type = $invoice_type;
		}

		/**
		 * Get the invoice order once per request.
		 *
		 * @return WC_Order|false
		 */
		private function get_order() {
			if ( null === $this->order ) {
				$this->order = wc_get_order( $this->order_id );
			}
			return $this->order;
		}

		/**
		 * Is_created.
		 */
		public function is_created() {
			$order = $this->get_order();
			return $order ? '' !== $order->get_meta( '_wcj_invoicing_' . $this->invoice_type . '_date' ) : false;
		}

		/**
		 * Delete.
		 *
		 * @version 8.1.0
		 * @todo    removed update_option( $option_name, ( $the_invoice_counter - 1 ) ); It was causing the issue on the count.
		 */
		public function delete() {
			$order = $this->get_order();
			if ( $order ) {
				$order->update_meta_data( '_wcj_invoicing_' . $this->invoice_type . '_number_id', 0 );
				$order->update_meta_data( '_wcj_invoicing_' . $this->invoice_type . '_date', '' );
				$order->save();
			}
			if ( 'yes' === wcj_get_option( 'wcj_invoicing_' . $this->invoice_type . '_sequential_enabled', 'no' ) ) {
				$option_name         = 'wcj_invoicing_' . $this->invoice_type . '_numbering_counter';
				$the_invoice_counter = wcj_get_option( $option_name, 1 );
			}
		}

		/**
		 * Create.
		 *
		 * @version 8.1.0
		 * @todo    use mysql transaction enabled (as in "wcj_order_number_use_mysql_transaction_enabled")
		 * @todo    used get_option instead wcj_get_option to get current numbering_counter.
		 * @param number $date get date.
		 */
		public function create( $date = '' ) {

			$order_id     = $this->order_id;
			$invoice_type = $this->invoice_type;
			$order = $this->get_order();
			if ( ! $order ) {
				return;
			}
			if ( 'yes' === wcj_get_option( 'wcj_invoicing_' . $invoice_type . '_skip_zero_total', 'no' ) ) {
				if ( 0 === (int) $order->get_total() ) {
					return;
				}
			}
			if ( 'yes' === wcj_get_option( 'wcj_invoicing_' . $invoice_type . '_sequential_enabled', 'no' ) ) {
				$the_invoice_number = get_option( 'wcj_invoicing_' . $invoice_type . '_numbering_counter', 1 );
				update_option( 'wcj_invoicing_' . $invoice_type . '_numbering_counter', ( $the_invoice_number + 1 ) );
			} else {
				$the_invoice_number = $order_id;
			}
			$the_date = ( '' === $date ) ? (string) wcj_get_timestamp_date_from_gmt() : $date;
			$order->update_meta_data( '_wcj_invoicing_' . $invoice_type . '_number_id', $the_invoice_number );
			$order->update_meta_data( '_wcj_invoicing_' . $invoice_type . '_date', $the_date );
			$order->save();
		}

		/**
		 * Get_file_name.
		 *
		 * @version 3.5.0
		 */
		public function get_file_name() {
			$_file_name = sanitize_file_name( do_shortcode( wcj_get_option( 'wcj_invoicing_' . $this->invoice_type . '_file_name', '' ) ) );
			if ( '' === $_file_name ) {
				$_file_name = $this->invoice_type . '-' . $this->order_id;
			}
			return apply_filters( 'wcj_get_' . $this->invoice_type . '_file_name', $_file_name . '.pdf', $this->order_id );
		}

		/**
		 * Get_invoice_date.
		 */
		public function get_invoice_date() {
			$order    = $this->get_order();
			$the_date = $order ? $order->get_meta( '_wcj_invoicing_' . $this->invoice_type . '_date' ) : '';
			return apply_filters( 'wcj_get_' . $this->invoice_type . '_date', $the_date, $this->order_id );
		}

		/**
		 * Get_invoice_number.
		 *
		 * @version 8.1.0
		 */
		public function get_invoice_number() {
			$order             = $this->get_order();
			$invoice_number_id = $order ? $order->get_meta( '_wcj_invoicing_' . $this->invoice_type . '_number_id' ) : 0;
			$replaced_values   = array(
				'%prefix%'  => wcj_get_option( 'wcj_invoicing_' . $this->invoice_type . '_numbering_prefix', '' ),
				'%counter%' => sprintf(
					'%0' . wcj_get_option( 'wcj_invoicing_' . $this->invoice_type . '_numbering_counter_width', 0 ) . 'd',
					$invoice_number_id
				),
				'%suffix%'  => wcj_get_option( 'wcj_invoicing_' . $this->invoice_type . '_numbering_suffix', '' ),
			);
			return apply_filters(
				'wcj_get_' . $this->invoice_type . '_number',
				do_shortcode(
					str_replace(
						array_keys( $replaced_values ),
						$replaced_values,
						get_option( 'wcj_invoicing_' . $this->invoice_type . '_numbering_template', '%prefix%%counter%%suffix%' )
					)
				),
				$this->order_id
			);
		}

	}

endif;
