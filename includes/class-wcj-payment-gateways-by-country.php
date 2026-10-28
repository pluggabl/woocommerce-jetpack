<?php
/**
 * Booster for WooCommerce - Module - Payment Gateways by Country
 *
 * @version 5.6.8
 * @since   2.4.1
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Payment_Gateways_By_Country' ) ) :
	/**
	 * WCJ_Payment_Gateways_By_Country.
	 */
	class WCJ_Payment_Gateways_By_Country extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 3.4.0
		 */
		public function __construct() {

			$this->id         = 'payment_gateways_by_country';
			$this->short_desc = 'Gateways by Country, State or Postcode';
			wcj_init_presentation(
				function () {
					$this->short_desc = __( 'Gateways by Country, State or Postcode', 'woocommerce-jetpack' );
				}
			);
			$this->desc = 'Set countries, states or postcodes to include/exclude for payment gateways to show up.';
			wcj_init_presentation(
				function () {
					$this->desc = __( 'Set countries, states or postcodes to include/exclude for payment gateways to show up.', 'woocommerce-jetpack' );
				}
			);
			$this->link_slug = 'woocommerce-payment-gateways-by-country-or-state';
			parent::__construct();

			if ( $this->is_enabled() ) {
				add_filter( 'woocommerce_available_payment_gateways', array( $this, 'available_payment_gateways' ), PHP_INT_MAX, 1 );
			}
		}

		/**
		 * Get_location.
		 *
		 * @version 5.6.8
		 * @since   3.4.0
		 * @todo    on `WCJ_IS_WC_VERSION_BELOW_3` recheck if `get_shipping_country()` and `get_shipping_state()` work correctly
		 * @param string $type defines the type.
		 */
		public function get_location( $type ) {
			// phpcs:disable WordPress.Security.NonceVerification
			switch ( $type ) {
				case 'country':
					$country_type = wcj_get_option( 'wcj_gateways_by_location_country_type', 'billing' );

					switch ( $country_type ) {
						case 'by_ip':
							return wcj_get_country_by_ip();
						case 'shipping':
							return ( ( ! empty( $_REQUEST['s_country'] ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['s_country'] ) ) : ( isset( WC()->customer ) ? WC()->customer->get_shipping_country() : '' ) );
						default: // 'billing'
							return ( ! empty( $_REQUEST['country'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['country'] ) ) : ( isset( WC()->customer ) ? wcj_customer_get_country() : '' ) );
					}
				case 'state':
					$state_type = wcj_get_option( 'wcj_gateways_by_location_state_type', 'billing' );
					switch ( $state_type ) {
						case 'shipping':
							return ( ! empty( $_REQUEST['s_state'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s_state'] ) ) : ( isset( WC()->customer ) ? WC()->customer->get_shipping_state() : '' ) );
						default: // 'billing'
							return ( ! empty( $_REQUEST['state'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['state'] ) ) : ( isset( WC()->customer ) ? wcj_customer_get_country_state() : '' ) );
					}
				case 'postcode':
					$postcodes_type = wcj_get_option( 'wcj_gateways_by_location_postcodes_type', 'billing' );
					// REST checkout uses the current customer, not Classic request fields.
					$allow_classic_request = function_exists( 'wp_is_rest_endpoint' ) ? ! wp_is_rest_endpoint() : ! ( defined( 'REST_REQUEST' ) && REST_REQUEST );
					switch ( $postcodes_type ) {
						case 'shipping':
							return ( $allow_classic_request && ! empty( $_REQUEST['s_postcode'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_REQUEST['s_postcode'] ) ) ) : ( $allow_classic_request && ! empty( $_REQUEST['shipping_postcode'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_REQUEST['shipping_postcode'] ) ) ) : strtoupper( isset( WC()->customer ) && ( ! $allow_classic_request || ! is_checkout_pay_page() ) ? WC()->customer->get_shipping_postcode() : WC()->countries->get_base_postcode() ) ) );
						default: // 'billing'
							return ( $allow_classic_request && ! empty( $_REQUEST['postcode'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_REQUEST['postcode'] ) ) ) : ( $allow_classic_request && ! empty( $_REQUEST['billing_postcode'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_REQUEST['billing_postcode'] ) ) ) : strtoupper( isset( WC()->customer ) && ( ! $allow_classic_request || ! is_checkout_pay_page() ) ? WC()->customer->get_billing_postcode() : WC()->countries->get_base_postcode() ) ) );
					}
			}
			// phpcs:enable WordPress.Security.NonceVerification
		}

		/**
		 * Available_payment_gateways.
		 *
		 * @version 5.6.2
		 * @todo    (maybe) rename module to "Payment Gateways by (Customer's) Location"
		 * @todo    (maybe) check naming, should be `wcj_gateways_by_location_` (however it's too long...)
		 * @todo    (maybe) code refactoring
		 * @todo    (maybe) add more locations options (e.g. "... by city")
		 * @todo    (maybe) add option to detect customer's country and state by current `$_REQUEST` (as it is now done with postcodes)
		 * @param array $_available_gateways defines the _available_gateways.
		 */
		public function available_payment_gateways( $_available_gateways ) {
			$ticket = WCJ_Rule_Explanations::payment_start( $this, $_available_gateways );
			$row    = null;
			$whole  = false;
			$result = $this->payment_filter_body( $_available_gateways, $ticket, $row, $whole );
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
			$customer_country = $this->get_location( 'country' );
			$customer_state   = $this->get_location( 'state' );
			$postcode         = $this->get_location( 'postcode' );
			foreach ( $_available_gateways as $key => $gateway ) {
				$decision = $this->payment_decision( $key, $customer_country, $customer_state, $postcode );
				if ( $ticket === (string) $key ) {
					$row = $decision; }
				if ( 'removed' === $decision[0] ) {
					unset( $_available_gateways[ $key ] ); }
			}
			return $_available_gateways;
		}

		/**
		 * Evaluate the configured country restriction for the captured payment context.
		 *
		 * @param mixed $key Key.
		 * @param mixed $customer_country Customer country.
		 * @param mixed $customer_state Customer state.
		 * @param mixed $postcode Postcode.
		 * @return mixed Existing gateway decision or gateway map.
		 */
		private function payment_decision( $key, $customer_country, $customer_state, $postcode ) {
			$configured = false;
			if ( '' !== $customer_country ) {
				$include_countries = wcj_maybe_add_european_union_countries( wcj_get_option( 'wcj_gateways_countries_include_' . $key, '' ) );
				$configured        = $configured || ! empty( $include_countries );
				if ( ! empty( $include_countries ) && ! in_array( $customer_country, $include_countries, true ) ) {
					return array( 'removed', 'include_miss', 'country' );
				}
				$exclude_countries = wcj_get_option( 'wcj_gateways_countries_exclude_' . $key, '' );
				$configured        = $configured || ! empty( $exclude_countries );
				if ( ! empty( $exclude_countries ) && in_array( $customer_country, $exclude_countries, true ) ) {
					return array( 'removed', 'exclude_match', 'country' );
				}
			}
			if ( '' !== $customer_state ) {
				$include_states = wcj_get_option( 'wcj_gateways_states_include_' . $key, '' );
				$configured     = $configured || ! empty( $include_states );
				if ( ! empty( $include_states ) && ! in_array( $customer_state, $include_states, true ) ) {
					return array( 'removed', 'include_miss', 'state' );
				}
				$exclude_states = wcj_get_option( 'wcj_gateways_states_exclude_' . $key, '' );
				$configured     = $configured || ! empty( $exclude_states );
				if ( ! empty( $exclude_states ) && in_array( $customer_state, $exclude_states, true ) ) {
					return array( 'removed', 'exclude_match', 'state' );
				}
			}
			if ( '' !== $postcode ) {
				$include_postcodes = wcj_get_option( 'wcj_gateways_postcodes_include_' . $key, '' );
				$configured        = $configured || ! empty( $include_postcodes );
				if ( ! empty( $include_postcodes ) ) {
					$include_postcodes = array_filter( array_map( 'strtoupper', array_map( 'wc_clean', explode( "\n", $include_postcodes ) ) ) );
					$configured        = $configured || ! empty( $include_postcodes );
					if ( ! wcj_check_postcode( $postcode, $include_postcodes ) ) {
						return array( 'removed', 'include_miss', 'postcode' );
					}
				}
				$exclude_postcodes = wcj_get_option( 'wcj_gateways_postcodes_exclude_' . $key, '' );
				$configured        = $configured || ! empty( $exclude_postcodes );
				if ( ! empty( $exclude_postcodes ) ) {
					$exclude_postcodes = array_filter( array_map( 'strtoupper', array_map( 'wc_clean', explode( "\n", $exclude_postcodes ) ) ) );
					$configured        = $configured || ! empty( $exclude_postcodes );
					if ( wcj_check_postcode( $postcode, $exclude_postcodes ) ) {
						return array( 'removed', 'exclude_match', 'postcode' );
					}
				}
			}

			if ( '' === $customer_country && '' === $customer_state && '' === $postcode ) {
				return array( 'not_checked', 'empty_location_facts', null ); }
			return array( ! $configured && '' !== $customer_country && '' !== $customer_state && '' !== $postcode ? 'not_configured' : 'retained', ! $configured && '' !== $customer_country && '' !== $customer_state && '' !== $postcode ? 'no_rules' : 'reached_location_rules_pass', null );
		}

		/**
		 * Return a bounded explanation of this gateway rule without applying it.
		 *
		 * @param mixed $key Key.
		 * @param mixed $facts Facts.
		 * @return mixed Existing gateway decision or gateway map.
		 */
		public function explain_payment_gateway( $key, $facts ) {
			$location = array();
			foreach ( array( 'country', 'state', 'postcode' ) as $type ) {
				$option   = 'postcode' === $type ? 'postcodes' : $type;
				$selector = wcj_get_option( 'wcj_gateways_by_location_' . $option . '_type', 'billing' );
				if ( 'country' === $type && 'by_ip' === $selector ) {
					return array( 'unsupported', 'by_ip_location', 'country' ); }
				$tag = $facts[ 'shipping' === $selector ? 'shipping' : 'billing' ][ $type ];
				if ( 'unknown' === $tag['state'] ) {
					return array( 'not_checked', 'missing_fact', $type ); }
				$location[ $type ] = 'known_empty' === $tag['state'] ? '' : ( 'postcode' === $type ? strtoupper( $tag['value'] ) : $tag['value'] );
			}
			return $this->payment_decision( $key, $location['country'], $location['state'], $location['postcode'] );
		}
	}

endif;

return new WCJ_Payment_Gateways_By_Country();
