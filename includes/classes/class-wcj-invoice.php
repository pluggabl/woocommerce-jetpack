<?php
/**
 * Booster for WooCommerce Invoice
 *
 * @version 7.1.6
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
		 * @version 7.1.6
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
		 * Constructor.
		 *
		 * @param int    $order_id Get order id.
		 * @param string $invoice_type  get invoice type.
		 */
		public function __construct( $order_id, $invoice_type ) {
			$this->order_id     = $order_id;
			$this->invoice_type = $invoice_type;
		}

		/**
		 * Is_created.
		 */
		public function is_created() {
			if ( true === wcj_is_hpos_enabled() ) {
				$order = wcj_get_order( $this->order_id );
				if ( $order && false !== $order ) {
					return ( '' !== $order->get_meta( '_wcj_invoicing_' . $this->invoice_type . '_date' ) );
				}
			} else {
				return ( '' !== get_post_meta( $this->order_id, '_wcj_invoicing_' . $this->invoice_type . '_date', true ) );
			}
		}

		/**
		 * Delete.
		 *
		 * @version 7.1.4
		 * @todo    removed update_option( $option_name, ( $the_invoice_counter - 1 ) ); It was causing the issue on the count.
		 */
		public function delete() {
			if ( true === wcj_is_hpos_enabled() ) {
				$order = wcj_get_order( $this->order_id );
				if ( $order && false !== $order ) {
					$order->update_meta_data( '_wcj_invoicing_' . $this->invoice_type . '_number_id', 0 );
					$order->update_meta_data( '_wcj_invoicing_' . $this->invoice_type . '_date', '' );
					$order->save();
				}
			} else {
				update_post_meta( $this->order_id, '_wcj_invoicing_' . $this->invoice_type . '_number_id', 0 );
				update_post_meta( $this->order_id, '_wcj_invoicing_' . $this->invoice_type . '_date', '' );
			}
			if ( 'yes' === wcj_get_option( 'wcj_invoicing_' . $this->invoice_type . '_sequential_enabled', 'no' ) ) {
				$option_name         = 'wcj_invoicing_' . $this->invoice_type . '_numbering_counter';
				$the_invoice_counter = wcj_get_option( $option_name, 1 );
			}
		}

		/**
		 * Create.
		 *
		 * @version 7.1.4
		 * @todo    use mysql transaction enabled (as in "wcj_order_number_use_mysql_transaction_enabled")
		 * @todo    used get_option instead wcj_get_option to get current numbering_counter.
		 * @param number $date get date.
		 */
		public function create( $date = '' ) {

			$order_id     = $this->order_id;
			$invoice_type = $this->invoice_type;
			if ( 'yes' === wcj_get_option( 'wcj_invoicing_' . $invoice_type . '_skip_zero_total', 'no' ) ) {
				$_order = wc_get_order( $order_id );
				if ( 0 === (int) $_order->get_total() ) {
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
			if ( true === wcj_is_hpos_enabled() ) {
				$order = wcj_get_order( $order_id );
				if ( $order && false !== $order ) {
					$order->update_meta_data( '_wcj_invoicing_' . $invoice_type . '_number_id', $the_invoice_number );
					$order->update_meta_data( '_wcj_invoicing_' . $invoice_type . '_date', $the_date );
					$order->save();
				}
			} else {
				update_post_meta( $order_id, '_wcj_invoicing_' . $invoice_type . '_number_id', $the_invoice_number );
				update_post_meta( $order_id, '_wcj_invoicing_' . $invoice_type . '_date', $the_date );
			}
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
			if ( true === wcj_is_hpos_enabled() ) {
				$order = wcj_get_order( $this->order_id );
				if ( $order && false !== $order ) {
					$the_date = $order->get_meta( '_wcj_invoicing_' . $this->invoice_type . '_date' );
				}
			} else {
				$the_date = get_post_meta( $this->order_id, '_wcj_invoicing_' . $this->invoice_type . '_date', true );
			}
			return apply_filters( 'wcj_get_' . $this->invoice_type . '_date', $the_date, $this->order_id );
		}

		/**
		 * Get_invoice_number.
		 *
		 * @version 7.1.4
		 */
		public function get_invoice_number() {
			if ( true === wcj_is_hpos_enabled() ) {
				$order = wcj_get_order( $this->order_id );
				if ( $order && false !== $order ) {
					$replaced_values = array(
						'%prefix%'  => wcj_get_option( 'wcj_invoicing_' . $this->invoice_type . '_numbering_prefix', '' ),
						'%counter%' => sprintf(
							'%0' . wcj_get_option( 'wcj_invoicing_' . $this->invoice_type . '_numbering_counter_width', 0 ) . 'd',
							$order->get_meta( '_wcj_invoicing_' . $this->invoice_type . '_number_id' )
						),
						'%suffix%'  => wcj_get_option( 'wcj_invoicing_' . $this->invoice_type . '_numbering_suffix', '' ),
					);
				}
			} else {
				$replaced_values = array(
					'%prefix%'  => wcj_get_option( 'wcj_invoicing_' . $this->invoice_type . '_numbering_prefix', '' ),
					'%counter%' => sprintf(
						'%0' . wcj_get_option( 'wcj_invoicing_' . $this->invoice_type . '_numbering_counter_width', 0 ) . 'd',
						get_post_meta( $this->order_id, '_wcj_invoicing_' . $this->invoice_type . '_number_id', true )
					),
					'%suffix%'  => wcj_get_option( 'wcj_invoicing_' . $this->invoice_type . '_numbering_suffix', '' ),
				);
			}
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
