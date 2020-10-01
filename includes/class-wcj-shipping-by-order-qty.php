<?php
/**
 * Booster for WooCommerce - Module - Shipping Methods by Min/Max Order Quantity
 *
 * @version 4.3.0
 * @since   4.3.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Shipping_By_Order_Qty' ) ) :

class WCJ_Shipping_By_Order_Qty extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 4.3.0
	 * @since   4.3.0
	 * @todo    (maybe) add customer messages on cart and checkout pages (if some shipping method is not available)
	 */
	function __construct() {

		$this->id         = 'shipping_by_order_qty';
		$this->short_desc = __( 'Shipping Methods by Min/Max Order Quantity', 'woocommerce-jetpack' );
		$this->desc       = __( 'Set minimum and/or maximum order quantity for shipping methods to show up (Local pickup available in Plus).', 'woocommerce-jetpack' );
		$this->desc_pro   = __( 'Set minimum and/or maximum order quantity for shipping methods to show up.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-shipping-methods-by-min-max-order-quantity';
		parent::__construct();

		if ( $this->is_enabled() ) {
			$this->use_shipping_instances = ( 'yes' === wcj_get_option( 'wcj_shipping_by_order_qty_use_shipping_instance', 'no' ) );
			$min_option_name = 'wcj_shipping_by_order_qty_min';
			$max_option_name = 'wcj_shipping_by_order_qty_max';
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
	 * get_min_max_qty.
	 *
	 * @version 4.3.0
	 * @since   4.3.0
	 */
	function get_min_max_qty( $rate, $min_or_max ) {
		$key = ( $this->use_shipping_instances ? $rate->instance_id : $rate->method_id );
		switch ( $min_or_max ) {
			case 'min':
				return ( isset( $this->min_qty[ $key ] ) ? $this->min_qty[ $key ] : 0 );
			case 'max':
				return ( isset( $this->max_qty[ $key ] ) ? $this->max_qty[ $key ] : 0 );
		}
	}

	/**
	 * available_shipping_methods.
	 *
	 * @version 4.3.0
	 * @since   4.3.0
	 * @todo    apply_filters( 'booster_option' )
	 */
	function available_shipping_methods( $rates, $package ) {
		if ( ! isset( WC()->cart ) || WC()->cart->is_empty() ) {
			return $rates;
		}
		$total_qty = WC()->cart->get_cart_contents_count();
		foreach ( $rates as $rate_key => $rate ) {
			if ( 0 != ( $min = $this->get_min_max_qty( $rate, 'min' ) ) && $total_qty < $min ) {
				unset( $rates[ $rate_key ] );
			} elseif ( 0 != ( $max = $this->get_min_max_qty( $rate, 'max' ) ) && $total_qty > $max ) {
				unset( $rates[ $rate_key ] );
			}
		}
		return $rates;
	}

}

endif;

return new WCJ_Shipping_By_Order_Qty();
