<?php
/**
 * Booster for WooCommerce - Module - Payment Gateways by Country
 *
 * @version 4.5.0
 * @since   2.4.1
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Payment_Gateways_By_Country' ) ) :

class WCJ_Payment_Gateways_By_Country extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.4.0
	 */
	function __construct() {

		$this->id         = 'payment_gateways_by_country';
		$this->short_desc = __( 'Gateways by Country, State or Postcode', 'woocommerce-jetpack' );
		$this->desc       = __( 'Set countries, states or postcodes to include/exclude for payment gateways to show up.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-payment-gateways-by-country-or-state';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_filter( 'woocommerce_available_payment_gateways', array( $this, 'available_payment_gateways' ), PHP_INT_MAX, 1 );
		}
	}

	/**
	 * get_location.
	 *
	 * @version 4.5.0
	 * @since   3.4.0
	 * @todo    on `WCJ_IS_WC_VERSION_BELOW_3` recheck if `get_shipping_country()` and `get_shipping_state()` work correctly
	 */
	function get_location( $type ) {
		switch ( $type ) {
			case 'country':
				$country_type = wcj_get_option( 'wcj_gateways_by_location_country_type', 'billing' );
				switch( $country_type ) {
					case 'by_ip':
						return wcj_get_country_by_ip();
					case 'shipping':
						return ( ! empty( $_REQUEST['s_country'] )  ? $_REQUEST['s_country']                : ( isset( WC()->customer ) ? WC()->customer->get_shipping_country() : '' ) );
					default: // 'billing'
						return ( ! empty( $_REQUEST['country'] )    ? $_REQUEST['country']                  : ( isset( WC()->customer ) ? wcj_customer_get_country()             : '' ) );
				}
			case 'state':
				$state_type = wcj_get_option( 'wcj_gateways_by_location_state_type', 'billing' );
				switch( $state_type ) {
					case 'shipping':
						return ( ! empty( $_REQUEST['s_state'] )    ? $_REQUEST['s_state']                  : ( isset( WC()->customer ) ? WC()->customer->get_shipping_state()   : '' ) );
					default: // 'billing'
						return ( ! empty( $_REQUEST['state'] )      ? $_REQUEST['state']                    : ( isset( WC()->customer ) ? wcj_customer_get_country_state()       : '' ) );
				}
			case 'postcode':
				$postcodes_type = wcj_get_option( 'wcj_gateways_by_location_postcodes_type', 'billing' );
				switch ( $postcodes_type ) {
					case 'shipping':
						return ( ! empty( $_REQUEST['s_postcode'] ) ? strtoupper( $_REQUEST['s_postcode'] ) : ( ! empty( $_REQUEST['shipping_postcode'] ) ? strtoupper( $_REQUEST['shipping_postcode'] ) : strtoupper( WC()->countries->get_base_postcode() ) ) );
					default: // 'billing'
						return ( ! empty( $_REQUEST['postcode'] ) ? strtoupper( $_REQUEST['postcode'] ) : ( ! empty( $_REQUEST['billing_postcode'] ) ? strtoupper( $_REQUEST['billing_postcode'] ) : strtoupper( WC()->countries->get_base_postcode() ) ) );
				}
		}
	}

	/**
	 * available_payment_gateways.
	 *
	 * @version 4.0.0
	 * @todo    (maybe) rename module to "Payment Gateways by (Customer's) Location"
	 * @todo    (maybe) check naming, should be `wcj_gateways_by_location_` (however it's too long...)
	 * @todo    (maybe) code refactoring
	 * @todo    (maybe) add more locations options (e.g. "... by city")
	 * @todo    (maybe) add option to detect customer's country and state by current `$_REQUEST` (as it is now done with postcodes)
	 */
	function available_payment_gateways( $_available_gateways ) {
		$customer_country = $this->get_location( 'country' );
		$customer_state   = $this->get_location( 'state' );
		$postcode         = $this->get_location( 'postcode' );
		foreach ( $_available_gateways as $key => $gateway ) {
			if ( '' != $customer_country ) {
				$include_countries = wcj_maybe_add_european_union_countries( wcj_get_option( 'wcj_gateways_countries_include_' . $key, '' ) );
				if ( ! empty( $include_countries ) && ! in_array( $customer_country, $include_countries ) ) {
					unset( $_available_gateways[ $key ] );
					continue;
				}
				$exclude_countries = wcj_get_option( 'wcj_gateways_countries_exclude_' . $key, '' );
				if ( ! empty( $exclude_countries ) && in_array( $customer_country, $exclude_countries ) ) {
					unset( $_available_gateways[ $key ] );
					continue;
				}
			}
			if ( '' != $customer_state ) {
				$include_states = wcj_get_option( 'wcj_gateways_states_include_' . $key, '' );
				if ( ! empty( $include_states ) && ! in_array( $customer_state, $include_states ) ) {
					unset( $_available_gateways[ $key ] );
					continue;
				}
				$exclude_states = wcj_get_option( 'wcj_gateways_states_exclude_' . $key, '' );
				if ( ! empty( $exclude_states ) && in_array( $customer_state, $exclude_states ) ) {
					unset( $_available_gateways[ $key ] );
					continue;
				}
			}
			if ( '' != $postcode ) {
				$include_postcodes = wcj_get_option( 'wcj_gateways_postcodes_include_' . $key, '' );
				if ( ! empty( $include_postcodes ) ) {
					$include_postcodes = array_filter( array_map( 'strtoupper', array_map( 'wc_clean', explode( "\n", $include_postcodes ) ) ) );
					if ( ! wcj_check_postcode( $postcode, $include_postcodes ) ) {
						unset( $_available_gateways[ $key ] );
						continue;
					}
				}
				$exclude_postcodes = wcj_get_option( 'wcj_gateways_postcodes_exclude_' . $key, '' );
				if ( ! empty( $exclude_postcodes ) ) {
					$exclude_postcodes = array_filter( array_map( 'strtoupper', array_map( 'wc_clean', explode( "\n", $exclude_postcodes ) ) ) );
					if ( wcj_check_postcode( $postcode, $exclude_postcodes ) ) {
						unset( $_available_gateways[ $key ] );
						continue;
					}
				}
			}
		}
		return $_available_gateways;
	}

}

endif;

return new WCJ_Payment_Gateways_By_Country();
