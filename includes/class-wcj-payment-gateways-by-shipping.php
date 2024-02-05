<?php
/**
 * Booster for WooCommerce - Module - Payment Gateways by Shipping
 *
 * @version 7.1.6
 * @since   2.7.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Payment_Gateways_By_Shipping' ) ) :
	/**
	 * WCJ_Payment_Gateways_By_Shipping.
	 */
	class WCJ_Payment_Gateways_By_Shipping extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 7.1.6
		 * @since   2.7.0
		 * @todo    re-check in which more modules `use_shipping_instance` can be used
		 */
		public function __construct() {

			$this->id         = 'payment_gateways_by_shipping';
			$this->short_desc = __( 'Gateways by Shipping', 'woocommerce-jetpack' );
			$this->desc       = __( 'Set "enable for shipping methods" for payment gateways.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-payment-gateways-by-shipping';
			parent::__construct();

			if ( $this->is_enabled() ) {
				$this->use_shipping_instances = ( 'yes' === wcj_get_option( 'wcj_payment_gateways_by_shipping_use_shipping_instance', 'no' ) );
				add_filter( 'woocommerce_available_payment_gateways', array( $this, 'available_payment_gateways' ), PHP_INT_MAX, 1 );
			}
		}

		/**
		 * Check_if_enabled_for_methods.
		 *
		 * @version 7.1.6
		 * @since   2.7.0
		 * @see     `is_available()` function in WooCommerce `WC_Gateway_COD` class
		 * @todo    (maybe) virtual orders (`enable_for_virtual`)
		 * @param string        $gateway_key defines the gateway_key.
		 * @param string | bool $enable_for_methods defines the enable_for_methods.
		 */
		public function check_if_enabled_for_methods( $gateway_key, $enable_for_methods ) {

			$order          = null;
			$needs_shipping = false;

			// Test if shipping is needed first.
			if ( WC()->cart && WC()->cart->needs_shipping() ) {
				$needs_shipping = true;
			} elseif ( is_page( wc_get_page_id( 'checkout' ) ) && 0 < get_query_var( 'order-pay' ) ) {
				$order_id = absint( get_query_var( 'order-pay' ) );
				$order    = wc_get_order( $order_id );

				// Test if order needs shipping.
				if ( 0 < count( $order->get_items() ) ) {
					foreach ( $order->get_items() as $item ) {
						$_product = $item->get_product();
						if ( $_product && $_product->needs_shipping() ) {
							$needs_shipping = true;
							break;
						}
					}
				}
			}

			$needs_shipping = apply_filters( 'woocommerce_cart_needs_shipping', $needs_shipping );

			// Virtual order, with virtual disabled.

			// Check methods.
			if ( ! empty( $enable_for_methods ) && $needs_shipping ) {

				// Only apply if all packages are being shipped via chosen methods, or order is virtual.
				$chosen_shipping_methods_session = WC()->session->get( 'chosen_shipping_methods' );

				if ( isset( $chosen_shipping_methods_session ) ) {
					$chosen_shipping_methods = array_unique( $chosen_shipping_methods_session );
				} else {
					$chosen_shipping_methods = array();
				}

				$check_method = false;

				if ( is_object( $order ) ) {
					if ( $order->shipping_method ) {
						$check_method = $order->shipping_method;
					}
				} elseif ( empty( $chosen_shipping_methods ) || count( $chosen_shipping_methods ) > 1 ) {
					$check_method = false;
				} elseif ( count( $chosen_shipping_methods ) === 1 ) {
					$check_method = $chosen_shipping_methods[0];
				}

				if ( ! $check_method ) {
					return false;
				}

				$found = false;

				// Shipping method instance.
				if ( $this->use_shipping_instances ) {
					$_check_method = explode( ':', $check_method, 2 );
					if ( ! isset( $_check_method[1] ) || ! is_numeric( $_check_method[1] ) ) {
						// "Flexible Shipping" plugin (e.g. `flexible_shipping_13_2`).
						$_check_method = explode( '_', strrev( $check_method ), 3 );
						if ( ! isset( $_check_method[1] ) || ! is_numeric( $_check_method[1] ) ) {
							return false;
						} else {
							$check_method = strrev( $_check_method[1] );
						}
					} else {
						$check_method = $_check_method[1];
					}
				}

				// Final check.
				foreach ( $enable_for_methods as $method_id ) {
					if ( $this->use_shipping_instances ) {
						if ( $check_method === $method_id ) {
							return true;
						}
					} else {
						if ( strpos( $check_method, $method_id ) === 0 ) {
							return true;
						}
					}
				}

				if ( ! $found ) {
					return false;
				}
			}

			return true;
		}

		/**
		 * Available_payment_gateways.
		 *
		 * @version 7.1.6
		 * @since   2.7.0
		 * @param array $_available_gateways defines the _available_gateways.
		 */
		public function available_payment_gateways( $_available_gateways ) {
			foreach ( $_available_gateways as $key => $gateway ) {
				$enable_for_methods = ( $this->use_shipping_instances ?
				get_option( 'wcj_gateways_by_shipping_enable_instance_' . $key, '' ) :
				get_option( 'wcj_gateways_by_shipping_enable_' . $key, '' ) );
				if ( ! empty( $enable_for_methods ) && ! $this->check_if_enabled_for_methods( $key, $enable_for_methods ) ) {
					unset( $_available_gateways[ $key ] );
					continue;
				}
			}
			return $_available_gateways;
		}

	}

endif;

return new WCJ_Payment_Gateways_By_Shipping();
