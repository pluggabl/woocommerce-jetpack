<?php
/**
 * Booster for WooCommerce - Module - Gateways Min/Max Amounts
 *
 * @version 7.2.2
 * @since   2.4.1
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Payment_Gateways_Min_Max' ) ) :
	/**
	 * WCJ_Payment_Gateways_Min_Max.
	 */
	class WCJ_Payment_Gateways_Min_Max extends WCJ_Module {
		/**
		 * Payment receipt.
		 *
		 * @var mixed
		 */
		private $payment_receipt = null;
		/**
		 * Payment collect.
		 *
		 * @var mixed
		 */
		private $payment_collect = false;
		/**
		 * Payment selected.
		 *
		 * @var mixed
		 */
		private $payment_selected = null;
		/**
		 * Payment whole.
		 *
		 * @var mixed
		 */
		private $payment_whole = false;

		/**
		 * Notices
		 *
		 * @version 4.7.0
		 * @var array $notice defines array..
		 */
		/**
		 * Notices
		 *
		 * @var array $notice.
		 */
		private $notices = array();

		/**
		 * Constructor.
		 *
		 * @version 5.2.0
		 * @since   2.4.1
		 */
		public function __construct() {

			$this->id         = 'payment_gateways_min_max';
			$this->short_desc = 'Gateways Min/Max Amounts';
			wcj_init_presentation(
				function () {
					$this->short_desc = __( 'Gateways Min/Max Amounts', 'woocommerce-jetpack' );
				}
			);
			$this->desc = 'Add min/max amounts for payment gateways to show up (Only Direct bank transfer allowed in free version).';
			wcj_init_presentation(
				function () {
					$this->desc = __( 'Add min/max amounts for payment gateways to show up (Only Direct bank transfer allowed in free version).', 'woocommerce-jetpack' );
				}
			);
			$this->desc_pro = 'Add min/max amounts for payment gateways to show up.';
			wcj_init_presentation(
				function () {
					$this->desc_pro = __( 'Add min/max amounts for payment gateways to show up.', 'woocommerce-jetpack' );
				}
			);
			$this->link_slug = 'woocommerce-payment-gateways-min-max-amounts';
			parent::__construct();

			if ( $this->is_enabled() ) {
				add_filter( 'woocommerce_available_payment_gateways', array( $this, 'available_payment_gateways' ), PHP_INT_MAX, 1 );
				add_filter( 'woocommerce_before_checkout_form', array( $this, 'add_notices' ), 1 );
			}
		}

		/**
		 * Add_notices.
		 *
		 * @version 4.7.0
		 * @since   4.7.0
		 */
		public function add_notices() {
			if ( ! function_exists( 'WC' ) ) {
				return;
			}
			WC()->payment_gateways->get_available_payment_gateways();
			$notices = $this->notices;
			if ( function_exists( 'is_checkout' ) && is_checkout() && 'yes' === wcj_get_option( 'wcj_payment_gateways_min_max_notices_enable', 'yes' ) && ! empty( $notices ) ) {
				$notice_type = wcj_get_option( 'wcj_payment_gateways_min_max_notices_type', 'notice' );
				foreach ( $notices as $notice ) {
					if ( ! wc_has_notice( $notice, $notice_type ) ) {
						wc_add_notice( $notice, $notice_type );
					}
				}
			}
		}

		/**
		 * Remove_payment_gateways.
		 *
		 * @version 7.2.2
		 * @since   4.7.0
		 *
		 * @param array $_available_gateways defines the _available_gateways.
		 *
		 * @return array
		 */
		public function remove_payment_gateways( $_available_gateways ) {
			$row    = null;
			$whole  = false;
			$ticket = $this->payment_collect ? $this->payment_selected : null;
			$result = $this->payment_remove_body( $_available_gateways, $ticket, $row, $whole );
			if ( $this->payment_collect ) {
				$this->payment_receipt = $row;
				$this->payment_whole   = $whole; }
			return $result;
		}
		/**
		 * Remove unavailable gateways while recording their bounded decisions.
		 *
		 * @param mixed $_available_gateways Available gateways.
		 * @param mixed $ticket Ticket.
		 * @param mixed $row Row.
		 * @param mixed $whole Whole.
		 * @return mixed Existing gateway decision or gateway map.
		 */
		private function payment_remove_body( $_available_gateways, $ticket, &$row, &$whole ) {
			if ( ! function_exists( 'WC' ) || ! isset( WC()->cart ) ) {
				$whole = true;
				$row   = array( 'not_checked', 'missing_cart', 'total' );
				return array(
					'gateways' => $_available_gateways,
					'notices'  => null,
				);
			}
			$total_in_cart = WC()->cart->cart_contents_total;

			if ( 'yes' === wcj_get_option( 'wcj_payment_gateways_min_max_include_tax', 'no' ) ) {

				// Get the total tax amount.
				$cart_totals = WC()->cart->get_totals();

				// Extract the total tax.
				$total_tax = isset( $cart_totals['total_tax'] ) ? $cart_totals['total_tax'] : 0;

				$total_in_cart += $total_tax;
			}
			if ( 'no' === wcj_get_option( 'wcj_payment_gateways_min_max_exclude_shipping', 'no' ) ) {
				$total_in_cart += WC()->cart->shipping_total;
			}
			$notices = array();
			/* translators: %s: translation added */
			$notices_template_min = wcj_get_option( 'wcj_payment_gateways_min_max_notices_template_min', __( 'Minimum amount for %gateway_title% is %min_amount%', 'woocommerce-jetpack' ) );
			/* translators: %s: translation added */
			$notices_template_max = wcj_get_option( 'wcj_payment_gateways_min_max_notices_template_max', __( 'Maximum amount for %gateway_title% is %max_amount%', 'woocommerce-jetpack' ) );
			foreach ( $_available_gateways as $key => $gateway ) {
				$min = wcj_get_option( 'wcj_payment_gateways_min_' . $key, 0 );
				$max = wcj_get_option( 'wcj_payment_gateways_max_' . $key, 0 );

				// Compatibility with other modules.
				if ( 'yes' === wcj_get_option( 'wcj_payment_gateways_min_max_comp_mc', 'no' ) ) {
					if ( wcj_is_module_enabled( 'multicurrency' ) ) {
						$min = w_c_j()->all_modules['multicurrency']->change_price( $min, null );
						$max = w_c_j()->all_modules['multicurrency']->change_price( $max, null );
					}
				}

				$decision = $this->payment_threshold_decision( $total_in_cart, $min, $max );
				if ( $ticket === (string) $key ) {
					$row = $decision; }
				if ( 'below_min' === $decision[1] ) {
					$notices[] = str_replace( array( '%gateway_title%', '%min_amount%' ), array( $gateway->title, wc_price( $min ) ), $notices_template_min );
					unset( $_available_gateways[ $key ] );
					continue;
				}

				if ( 'above_max' === $decision[1] ) {
					$notices[] = str_replace( array( '%gateway_title%', '%max_amount%' ), array( $gateway->title, wc_price( $max ) ), $notices_template_max );
					unset( $_available_gateways[ $key ] );
					continue;
				}
			}
			return array(
				'gateways' => $_available_gateways,
				'notices'  => $notices,
			);
		}

		/**
		 * Available_payment_gateways.
		 *
		 * @version 4.7.0
		 * @since   2.4.1
		 * @todo    (maybe) `wc_clear_notices()`
		 * @param array $_available_gateways defines the _available_gateways.
		 */
		public function available_payment_gateways( $_available_gateways ) {
			$ticket                = WCJ_Rule_Explanations::payment_start( $this, $_available_gateways );
			$this->payment_collect = null !== $ticket;
			if ( $this->payment_collect ) {
				$this->payment_selected = $ticket;
				$this->payment_whole    = false; }
			if ( $this->payment_collect ) {
				$this->payment_receipt = null; }
			$row                   = null;
			$whole                 = false;
			$result                = $this->payment_filter_body( $_available_gateways, $ticket, $row, $whole );
			$this->payment_collect = false;
			if ( null !== $ticket ) {
				WCJ_Rule_Explanations::payment_finish( $this, $ticket, $row, $whole ); }
			return $result;
		}

		/**
		 * Original lazy collector, shared with bounded per-gateway decisions.
		 *
		 * @param mixed $_available_gateways Available gateway map.
		 * @param mixed $ticket Request-local observation ticket.
		 * @param mixed $row Captured decision row.
		 * @param mixed $whole Whether the complete decision is requested.
		 */
		private function payment_filter_body( $_available_gateways, $ticket, &$row, &$whole ) {
			$remove_response     = $this->remove_payment_gateways( $_available_gateways );
			$_available_gateways = $remove_response['gateways'];
			$this->notices       = $remove_response['notices'];
			if ( $this->payment_collect ) {
				$row   = $this->payment_receipt;
				$whole = $this->payment_whole; }
			return $_available_gateways;
		}

		/**
		 * Evaluate the configured payment amount threshold.
		 *
		 * @param mixed $total_in_cart Total in cart.
		 * @param mixed $min Min.
		 * @param mixed $max Max.
		 * @return mixed Existing gateway decision or gateway map.
		 */
		private function payment_threshold_decision( $total_in_cart, $min, $max ) {
			$min_zero = '0' === (string) $min;
			if ( ! $min_zero && $total_in_cart < $min ) {
				return array( 'removed', 'below_min', 'total' ); }
			$max_zero = '0' === (string) $max;
			if ( ! $max_zero && $total_in_cart > $max ) {
				return array( 'removed', 'above_max', 'total' ); }
			return array( $min_zero && $max_zero ? 'not_configured' : 'retained', $min_zero && $max_zero ? 'no_limits' : 'limits_pass', 'total' );
		}
		/**
		 * Return a bounded explanation of this gateway rule without applying it.
		 *
		 * @param mixed $key Key.
		 * @param mixed $facts Facts.
		 * @return mixed Existing gateway decision or gateway map.
		 */
		public function explain_payment_gateway( $key, $facts ) {
			if ( 'unknown' === $facts['contents_total']['state'] ) {
				return array( 'not_checked', 'missing_fact', 'total' ); }
			$total = $facts['contents_total']['value'];
			if ( 'yes' === wcj_get_option( 'wcj_payment_gateways_min_max_include_tax', 'no' ) ) {
				if ( 'unknown' === $facts['total_tax']['state'] ) {
					return array( 'not_checked', 'missing_fact', 'total' ); }
				$total += $facts['total_tax']['value'];
			}
			if ( 'no' === wcj_get_option( 'wcj_payment_gateways_min_max_exclude_shipping', 'no' ) ) {
				if ( 'unknown' === $facts['shipping_total']['state'] ) {
					return array( 'not_checked', 'missing_fact', 'total' ); }
				$total += $facts['shipping_total']['value'];
			}
			$min = wcj_get_option( 'wcj_payment_gateways_min_' . $key, 0 );
			$max = wcj_get_option( 'wcj_payment_gateways_max_' . $key, 0 );
			if ( 'yes' === wcj_get_option( 'wcj_payment_gateways_min_max_comp_mc', 'no' ) ) {
				return array( 'unsupported', 'live_conversion', 'total' ); }
			return $this->payment_threshold_decision( $total, $min, $max );
		}
	}

endif;

return new WCJ_Payment_Gateways_Min_Max();
