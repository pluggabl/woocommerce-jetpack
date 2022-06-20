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
			$this->short_desc = __( 'Gateways by Currency', 'woocommerce-jetpack' );
			$this->desc       = __( 'Set allowed currencies for payment gateways to show up.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-payment-gateways-by-currency';
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
			$current_currency = get_woocommerce_currency();
			foreach ( $_available_gateways as $key => $gateway ) {
				$allowed_currencies = wcj_get_option( 'wcj_gateways_by_currency_allowed_' . $key, '' );
				if ( ! empty( $allowed_currencies ) && ! in_array( $current_currency, $allowed_currencies, true ) ) {
					unset( $_available_gateways[ $key ] );
					continue;
				}
				$denied_currencies = wcj_get_option( 'wcj_gateways_by_currency_denied_' . $key, '' );
				if ( ! empty( $denied_currencies ) && in_array( $current_currency, $denied_currencies, true ) ) {
					unset( $_available_gateways[ $key ] );
					continue;
				}
			}
			return $_available_gateways;
		}

	}

endif;

return new WCJ_Payment_Gateways_By_Currency();
