<?php
/**
 * Booster for WooCommerce - Module - Gateways by Currency
 *
 * @version 3.0.0
 * @since   3.0.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Payment_Gateways_By_Currency' ) ) :
	/**
	 * WCJ_Payment_Gateways_By_Currency.
	 */
	class WCJ_Payment_Gateways_By_Currency extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 3.0.0
		 * @since   3.0.0
		 */
		public function __construct() {

			$this->id         = 'payment_gateways_by_currency';
			$this->short_desc = 'Gateways by Currency';
			wcj_init_presentation(
				function () {
					$this->short_desc = __( 'Gateways by Currency', 'woocommerce-jetpack' );
				}
			);
			$this->desc = 'Set allowed currencies for payment gateways to show up.';
			wcj_init_presentation(
				function () {
					$this->desc = __( 'Set allowed currencies for payment gateways to show up.', 'woocommerce-jetpack' );
				}
			);
			$this->link_slug = 'woocommerce-payment-gateways-by-currency';
			parent::__construct();

			if ( $this->is_enabled() ) {
				add_filter( 'woocommerce_available_payment_gateways', array( $this, 'available_payment_gateways' ), PHP_INT_MAX, 1 );
			}
		}

		/**
		 * Available_payment_gateways.
		 *
		 * @version 3.0.0
		 * @since   3.0.0
		 * @param array $_available_gateways defines the _available_gateways.
		 */
		public function available_payment_gateways( $_available_gateways ) {
			$ticket = WCJ_Rule_Explanations::payment_start( $this, $_available_gateways );
			$row = null;
			$whole = false;
			$result = $this->payment_filter_body( $_available_gateways, $ticket, $row, $whole );
			if ( null !== $ticket ) { WCJ_Rule_Explanations::payment_finish( $this, $ticket, $row, $whole ); }
			return $result;
		}

		/** Original lazy collector, shared with bounded per-gateway decisions. */
		private function payment_filter_body( $_available_gateways, $ticket, &$row, &$whole ) {
			$current_currency = get_woocommerce_currency();
			foreach ( $_available_gateways as $key => $gateway ) {
				$decision = $this->payment_decision( $key, $current_currency );
				if ( $ticket === (string) $key ) { $row = $decision; }
				if ( 'removed' === $decision[0] ) { unset( $_available_gateways[ $key ] ); }
			} 
			return $_available_gateways;
		
		}

		/** Shared original include/exclude short-circuit decision. */
		private function payment_decision( $key, $current_currency ) {
				$allowed_currencies = wcj_get_option( 'wcj_gateways_by_currency_allowed_' . $key, '' );
				if ( ! empty( $allowed_currencies ) && ! in_array( $current_currency, $allowed_currencies, true ) ) {
					return array( 'removed', 'include_miss', 'currency' );
				}
				$denied_currencies = wcj_get_option( 'wcj_gateways_by_currency_denied_' . $key, '' );
				if ( ! empty( $denied_currencies ) && in_array( $current_currency, $denied_currencies, true ) ) {
					return array( 'removed', 'exclude_match', 'currency' );
				}
			
			return array( empty( $allowed_currencies ) && empty( $denied_currencies ) ? 'not_configured' : 'retained', empty( $allowed_currencies ) && empty( $denied_currencies ) ? 'no_rules' : 'rules_pass', 'currency' );
		}

		public function explain_payment_gateway( $key, $facts ) {
			if ( 'unknown' === $facts['currency']['state'] ) { return array( 'not_checked', 'missing_fact', 'currency' ); }
			return $this->payment_decision( $key, $facts['currency']['value'] );
		}

	}

endif;

return new WCJ_Payment_Gateways_By_Currency();
