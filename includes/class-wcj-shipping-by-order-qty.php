<?php
/**
 * Booster for WooCommerce - Module - Shipping Methods by Min/Max Order Quantity
 *
 * @version 6.0.1
 * @since   4.3.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Shipping_By_Order_Qty' ) ) :
	/**
	 * WCJ_Shipping_By_Order_Qty.
	 *
	 * @version 4.3.0
	 * @since   4.3.0
	 */
	class WCJ_Shipping_By_Order_Qty extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 4.3.0
		 * @since   4.3.0
		 * @todo    (maybe) add customer messages on cart and checkout pages (if some shipping method is not available)
		 */
		public function __construct() {

			$this->id         = 'shipping_by_order_qty';
			$this->short_desc = __( 'Shipping Methods by Min/Max Order Quantity', 'woocommerce-jetpack' );
			$this->desc       = __( 'Set minimum and/or maximum order quantity for shipping methods to show up (Local pickup available in Plus).', 'woocommerce-jetpack' );
			$this->desc_pro   = __( 'Set minimum and/or maximum order quantity for shipping methods to show up.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-shipping-methods-by-min-max-order-quantity';
			parent::__construct();

			if ( $this->is_enabled() ) {
				$this->use_shipping_instances = ( 'yes' === wcj_get_option( 'wcj_shipping_by_order_qty_use_shipping_instance', 'no' ) );
				$min_option_name              = 'wcj_shipping_by_order_qty_min';
				$max_option_name              = 'wcj_shipping_by_order_qty_max';
				if ( $this->use_shipping_instances ) {
					$min_option_name .= '_instance';
					$max_option_name .= '_instance';
				}
				$this->min_qty = wcj_get_option( $min_option_name, array() );
				$this->max_qty = wcj_get_option( $max_option_name, array() );
				add_filter( 'woocommerce_package_rates', array( $this, 'available_shipping_methods' ), PHP_INT_MAX, 2 );
			}
		}

		/**
		 * Get_min_max_qty.
		 *
		 * @version 4.3.0
		 * @since   4.3.0
		 * @param int    $rate defines the rate.
		 * @param string $min_or_max defines the min_or_max.
		 */
		public function get_min_max_qty( $rate, $min_or_max ) {
			$key = ( $this->use_shipping_instances ? $rate->instance_id : $rate->method_id );
			switch ( $min_or_max ) {
				case 'min':
					return ( isset( $this->min_qty[ $key ] ) ? $this->min_qty[ $key ] : 0 );
				case 'max':
					return ( isset( $this->max_qty[ $key ] ) ? $this->max_qty[ $key ] : 0 );
			}
		}

		/**
		 * Available_shipping_methods.
		 *
		 * @version 6.0.1
		 * @since   4.3.0
		 * @todo    apply_filters( 'booster_option' )
		 * @param array          $rates defines the rates.
		 * @param string | array $package defines the package.
		 */
		public function available_shipping_methods( $rates, $package ) {
			if ( ! isset( WC()->cart ) || WC()->cart->is_empty() ) {
				return $rates;
			}
			$total_qty = WC()->cart->get_cart_contents_count();
			foreach ( $rates as $rate_key => $rate ) {
				$min = $this->get_min_max_qty( $rate, 'min' );
				$max = $this->get_min_max_qty( $rate, 'max' );
				if ( 0 !== $min && '0' !== $min && $total_qty < $min ) {
					unset( $rates[ $rate_key ] );
				} elseif ( 0 !== $max && '0' !== $max && $total_qty > $max ) {
					unset( $rates[ $rate_key ] );
				}
			}
			return $rates;
		}

	}

endif;

return new WCJ_Shipping_By_Order_Qty();
