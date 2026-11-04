<?php
/**
 * Booster for WooCommerce - Module - Shipping Methods by Min/Max Order Amount
 *
 * @version 7.3.0
 * @since   3.2.1
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Shipping_By_Order_Amount' ) ) :
		/**
		 * Constructor.
		 *
		 * @version 5.2.0
		 * @since   3.2.1
		 */
	class WCJ_Shipping_By_Order_Amount extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 7.3.0
		 * @since   3.2.1
		 * @todo    (maybe) add customer messages on cart and checkout pages (if some shipping method is not available)
		 */
		public function __construct() {

			$this->id         = 'shipping_by_order_amount';
			$this->short_desc = 'Shipping Methods by Min/Max Order Amount';
			wcj_init_presentation(
				function () {
					$this->short_desc = __( 'Shipping Methods by Min/Max Order Amount', 'woocommerce-jetpack' );
				}
			);
			$this->desc = 'Set minimum and/or maximum order amount for shipping methods to show up (Local pickup available in Elite).';
			wcj_init_presentation(
				function () {
					$this->desc = __( 'Set minimum and/or maximum order amount for shipping methods to show up (Local pickup available in Elite).', 'woocommerce-jetpack' );
				}
			);
			$this->desc_pro = 'Set minimum and/or maximum order amount for shipping methods to show up.';
			wcj_init_presentation(
				function () {
					$this->desc_pro = __( 'Set minimum and/or maximum order amount for shipping methods to show up.', 'woocommerce-jetpack' );
				}
			);
			$this->link_slug = 'woocommerce-shipping-methods-by-min-max-order-amount';
			parent::__construct();

			if ( $this->is_enabled() ) {
				$this->use_shipping_instances = ( 'yes' === wcj_get_option( 'wcj_shipping_by_order_amount_use_shipping_instance', 'no' ) );
				add_filter( 'woocommerce_package_rates', array( $this, 'available_shipping_methods' ), PHP_INT_MAX, 2 );
			}
		}

		/**
		 * Available_shipping_methods.
		 *
		 * @version 5.6.2
		 * @since   3.2.1
		 * @todo    [dev] currency conversion
		 * @todo    apply_filters( 'booster_option' )
		 * @todo    (maybe) add option to include or exclude taxes when calculating cart total
		 * @param int            $rates Get rates.
		 * @param string | array $package  Get packages.
		 */
		public function available_shipping_methods( $rates, $package ) {
			$wcj_ticket = WCJ_Rule_Explanations::shipping_start( $this, $rates );

			$wcj_present = isset( WC()->cart );
			if ( ! $wcj_present || WC()->cart->is_empty() ) {
				WCJ_Rule_Explanations::shipping_finish( $wcj_ticket, $rates, $wcj_present ? 'empty_cart' : 'no_cart' );
				return $rates;
			}
			$total_in_cart = WC()->cart->cart_contents_total;
			foreach ( $rates as $rate_key => $rate ) {
				$wcj_mode   = $this->use_shipping_instances;
				$min        = ( $wcj_mode ?
				get_option( 'wcj_shipping_by_order_amount_min_instance_' . WCJ_Rule_Explanations::shipping_capture_rule_key( $this, $rate, 'min', $wcj_mode, $rate->instance_id ), 0 ) : wcj_get_option( 'wcj_shipping_by_order_amount_min_' . WCJ_Rule_Explanations::shipping_capture_rule_key( $this, $rate, 'min', $wcj_mode, $rate->method_id ), 0 ) );
				$wcj_mode   = $this->use_shipping_instances;
				$max        = ( $wcj_mode ?
					get_option( 'wcj_shipping_by_order_amount_max_instance_' . WCJ_Rule_Explanations::shipping_capture_rule_key( $this, $rate, 'max', $wcj_mode, $rate->instance_id ), 0 ) : wcj_get_option( 'wcj_shipping_by_order_amount_max_' . WCJ_Rule_Explanations::shipping_capture_rule_key( $this, $rate, 'max', $wcj_mode, $rate->method_id ), 0 ) );
				$wcj_branch = null;
				if ( 0.0 !== (float) $min && $total_in_cart < $min ) {
					$wcj_branch = 'below_min';
					unset( $rates[ $rate_key ] );
				} elseif ( 0.0 !== (float) $max && $total_in_cart > $max ) {
					$wcj_branch = 'above_max';
					unset( $rates[ $rate_key ] );
				}
				WCJ_Rule_Explanations::shipping_capture_bounds( $this, $rate, $min, $max, $total_in_cart, $wcj_branch );
			}
			WCJ_Rule_Explanations::shipping_finish( $wcj_ticket, $rates );
			return $rates;
		}
	}

endif;

return new WCJ_Shipping_By_Order_Amount();
