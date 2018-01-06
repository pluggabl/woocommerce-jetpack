<?php
/**
 * Booster for WooCommerce - Module - Shipping Methods by Min/Max Order Amount
 *
 * @version 3.2.1
 * @since   3.2.1
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Shipping_By_Order_Amount' ) ) :

class WCJ_Shipping_By_Order_Amount extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.2.1
	 * @since   3.2.1
	 * @todo    (maybe) add customer messages on cart and checkout pages (if some shipping method is not available)
	 */
	function __construct() {

		$this->id         = 'shipping_by_order_amount';
		$this->short_desc = __( 'Shipping Methods by Min/Max Order Amount', 'woocommerce-jetpack' );
		$this->desc       = __( 'Set minimum and/or maximum order amount for WooCommerce shipping methods to show up.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-shipping-methods-by-min-max-order-amount';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_filter( 'woocommerce_package_rates', array( $this, 'available_shipping_methods' ), PHP_INT_MAX, 2 );
		}
	}

	/**
	 * available_shipping_methods.
	 *
	 * @version 3.2.1
	 * @since   3.2.1
	 * @todo    apply_filters( 'booster_option' )
	 * @todo    (maybe) add option to include or exclude taxes when calculating cart total
	 */
	function available_shipping_methods( $rates, $package ) {
		if ( ! isset( WC()->cart ) || WC()->cart->is_empty() ) {
			return $rates;
		}
		$total_in_cart = WC()->cart->cart_contents_total;
		foreach ( $rates as $rate_key => $rate ) {
			$min = get_option( 'wcj_shipping_by_order_amount_min_' . $rate->method_id, 0 );
			$max = get_option( 'wcj_shipping_by_order_amount_max_' . $rate->method_id, 0 );
			if ( 0 != $min && $total_in_cart < $min ) {
				unset( $rates[ $rate_key ] );
			} elseif ( 0 != $max && $total_in_cart > $max ) {
				unset( $rates[ $rate_key ] );
			}
		}
		return $rates;
	}

}

endif;

return new WCJ_Shipping_By_Order_Amount();
