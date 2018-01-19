<?php
/**
 * Booster for WooCommerce - Module - Payment Gateways by Country
 *
 * @version 3.3.1
 * @since   2.4.1
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Payment_Gateways_By_Country' ) ) :

class WCJ_Payment_Gateways_By_Country extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.3.1
	 */
	function __construct() {

		$this->id         = 'payment_gateways_by_country';
		$this->short_desc = __( 'Gateways by Country or State', 'woocommerce-jetpack' );
		$this->desc       = __( 'Set countries, states or postcodes to include/exclude for WooCommerce payment gateways to show up.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-payment-gateways-by-country-or-state';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_filter( 'woocommerce_available_payment_gateways', array( $this, 'available_payment_gateways' ), PHP_INT_MAX, 1 );
		}
	}

	/**
	 * get_location.
	 *
	 * @version 3.3.1
	 * @since   3.3.1
	 */
	function get_location( $type ) {
		switch ( $type ) {
			case 'country':
				return ( isset( WC()->customer ) ? wcj_customer_get_country() : '' );
			case 'state':
				return ( isset( WC()->customer ) ? ( WCJ_IS_WC_VERSION_BELOW_3 ? WC()->customer->get_state() : WC()->customer->get_billing_state() ) : '' );
			case 'postcode':
				$postcode = '';
				if ( isset( $_REQUEST['postcode'] ) && 'billing' === get_option( 'wcj_gateways_by_location_postcodes_type', 'billing' ) ) {
					$postcode = $_REQUEST['postcode'];
				} elseif ( isset( $_REQUEST['s_postcode'] ) && 'shipping' === get_option( 'wcj_gateways_postcodes_type', 'billing' ) ) {
					$postcode = $_REQUEST['s_postcode'];
				}
				if ( '' == $postcode ) {
					$postcode = WC()->countries->get_base_postcode();
				}
				return strtoupper( $postcode );
		}
	}

	/**
	 * available_payment_gateways.
	 *
	 * @version 3.3.1
	 * @todo    ! ranges and wildcards in postcodes
	 * @todo    ! rename module to "Payment Gateways by (Customer's) Location" or "Gateways by Country, State or Postcode"
	 * @todo    check naming, should be `wcj_gateways_by_location_` (however it's too long...)
	 * @todo    code refactoring
	 * @todo    add more locations options (e.g. "... by city")
	 * @todo    (maybe) add option to detect customer's country and state by current `$_REQUEST` (as it is now done with postcodes)
	 * @todo    (maybe) add option to detect customer's country by IP (instead of `wcj_customer_get_country()`); probably won't work with states though...
	 */
	function available_payment_gateways( $_available_gateways ) {
		$customer_country = $this->get_location( 'country' );
		$customer_state   = $this->get_location( 'state' );
		$postcode         = $this->get_location( 'postcode' );
		foreach ( $_available_gateways as $key => $gateway ) {
			if ( '' != $customer_country ) {
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
			}
			if ( '' != $customer_state ) {
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
			if ( '' != $postcode ) {
				$include_postcodes = get_option( 'wcj_gateways_postcodes_include_' . $key, '' );
				if ( ! empty( $include_postcodes ) ) {
					$include_postcodes = array_filter( array_map( 'strtoupper', array_map( 'wc_clean', explode( "\n", $include_postcodes ) ) ) );
					if ( ! in_array( $postcode, $include_postcodes ) ) {
						unset( $_available_gateways[ $key ] );
						continue;
					}
				}
				$exclude_postcodes = get_option( 'wcj_gateways_postcodes_exclude_' . $key, '' );
				if ( ! empty( $exclude_postcodes ) ) {
					$exclude_postcodes = array_filter( array_map( 'strtoupper', array_map( 'wc_clean', explode( "\n", $exclude_postcodes ) ) ) );
					if ( in_array( $postcode, $exclude_postcodes ) ) {
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
