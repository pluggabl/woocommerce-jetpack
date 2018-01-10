<?php
/**
 * Booster for WooCommerce - Module - Payment Gateways by Country
 *
 * @version 3.3.0
 * @since   2.4.1
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Payment_Gateways_By_Country' ) ) :

class WCJ_Payment_Gateways_By_Country extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.8.0
	 */
	function __construct() {

		$this->id         = 'payment_gateways_by_country';
		$this->short_desc = __( 'Gateways by Country or State', 'woocommerce-jetpack' );
		$this->desc       = __( 'Set countries or states to include/exclude for WooCommerce payment gateways to show up.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-payment-gateways-by-country-or-state';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_filter( 'woocommerce_available_payment_gateways', array( $this, 'available_payment_gateways' ), PHP_INT_MAX, 1 );
		}
	}

	/**
	 * available_payment_gateways.
	 *
	 * @version 3.3.0
	 * @todo    (maybe) add option to detect customer's country by IP (instead of `wcj_customer_get_country()`); probably won't work with states though...
	 */
	function available_payment_gateways( $_available_gateways ) {
		if ( isset( WC()->customer ) ) {
			$customer_country = wcj_customer_get_country();
			$customer_state   = ( WCJ_IS_WC_VERSION_BELOW_3 ) ? WC()->customer->get_state() : WC()->customer->get_billing_state();
			foreach ( $_available_gateways as $key => $gateway ) {
				$include_countries = get_option( 'wcj_gateways_countries_include_' . $key, '' );
				if ( ! empty( $include_countries ) && ! in_array( $customer_country, $include_countries ) ) {
					unset( $_available_gateways[ $key ] );
					continue;
				}
				$exclude_countries = get_option( 'wcj_gateways_countries_exclude_' . $key, '' );
				if ( ! empty( $exclude_countries ) && in_array( $customer_country, $exclude_countries ) ) {
					unset( $_available_gateways[ $key ] );
					continue;
				}
				$include_states = get_option( 'wcj_gateways_states_include_' . $key, '' );
				if ( ! empty( $include_states ) && ! in_array( $customer_state, $include_states ) ) {
					unset( $_available_gateways[ $key ] );
					continue;
				}
				$exclude_states = get_option( 'wcj_gateways_states_exclude_' . $key, '' );
				if ( ! empty( $exclude_states ) && in_array( $customer_state, $exclude_states ) ) {
					unset( $_available_gateways[ $key ] );
					continue;
				}
			}
		}
		return $_available_gateways;
	}

}

endif;

return new WCJ_Payment_Gateways_By_Country();
