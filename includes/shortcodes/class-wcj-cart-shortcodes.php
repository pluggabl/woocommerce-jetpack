<?php
/**
 * Booster for WooCommerce - Shortcodes - Cart
 *
 * @version 7.2.1
 * @since   3.5.1
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Cart_Shortcodes' ) ) :

		/**
		 * WCJ_Cart_Shortcodes.
		 *
		 * @version 7.2.1
		 * @since   3.5.1
		 */
	class WCJ_Cart_Shortcodes extends WCJ_Shortcodes {

		/**
		 * Constructor.
		 *
		 * @version 7.2.1
		 * @since   3.5.1
		 * @todo    (maybe) add `$atts['multiply_by']` to (all) shortcodes
		 */
		public function __construct() {

			$this->the_shortcodes = array(
				'wcj_cart_discount_tax',
				'wcj_cart_discount_total',
				'wcj_cart_items_total_quantity',
				'wcj_cart_items_total_weight',
				'wcj_cart_fee_tax',
				'wcj_cart_fee_total',
				'wcj_cart_function',
				'wcj_cart_shipping_total',
				'wcj_cart_shipping_tax',
				'wcj_cart_subtotal',
				'wcj_cart_subtotal_tax',
				'wcj_cart_tax',
				'wcj_cart_total',
				'wcj_cart_total_ex_tax',
			);

			$this->the_atts = array(
				'multiply_by'   => 1,
				'function_name' => null,
			);

			parent::__construct();

		}

		/**
		 * Inits shortcode atts and properties.
		 *
		 * @version 7.2.1
		 * @param   array $atts Shortcode atts.
		 * @return  array The (modified) shortcode atts.
		 */
		public function init_atts( $atts ) {
			$atts['function_name'] = wcj_sanitize_input_attribute_values( $atts['function_name'] );
			$atts['multiply_by']   = (int) wcj_sanitize_input_attribute_values( $atts['multiply_by'] );
			return $atts;

		}

		/**
		 * Wcj_cart_function.
		 *
		 * @version 3.5.1
		 * @since   3.5.1
		 * @todo    add `$atts['function_args']`
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_cart_function( $atts ) {
			$_cart = WC()->cart;
			if ( isset( $atts['function_name'] ) && '' !== $atts['function_name'] && ( $_cart ) ) {
				return $_cart->{$atts['function_name']}();
			}
			return '';
		}

		/**
		 * Wcj_cart_fee_tax.
		 *
		 * @version 3.5.1
		 * @since   3.5.1
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_cart_fee_tax( $atts ) {
			$_cart = WC()->cart;
			if ( $_cart ) {
				return wc_price( $_cart->get_fee_tax() );
			}
			return '';
		}

		/**
		 * Wcj_cart_fee_total.
		 *
		 * Get total fee amount.
		 *
		 * @version 3.5.1
		 * @since   3.5.1
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_cart_fee_total( $atts ) {
			$_cart = WC()->cart;
			if ( $_cart ) {
				return wc_price( $_cart->get_fee_total() );
			}
			return '';
		}

		/**
		 * Wcj_cart_discount_tax.
		 *
		 * Get discount_tax.
		 *
		 * @version 3.5.1
		 * @since   3.5.1
		 * @todo    check `$_cart->get_cart_discount_tax_total( )` // Get the total of all cart tax discounts (used for discounts on tax inclusive prices).
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_cart_discount_tax( $atts ) {
			$_cart = WC()->cart;
			if ( $_cart ) {
				return wc_price( $_cart->get_discount_tax() );
			}
			return '';
		}

		/**
		 * Wcj_cart_discount_total.
		 *
		 * Get discount_total.
		 *
		 * @version 3.5.1
		 * @since   3.5.1
		 * @todo    check `$_cart->get_cart_discount_total( )` // Get the total of all cart discounts.
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_cart_discount_total( $atts ) {
			$_cart = WC()->cart;
			if ( $_cart ) {
				return wc_price( $_cart->get_discount_total() );
			}
			return '';
		}

		/**
		 * Wcj_cart_items_total_quantity.
		 *
		 * @version 3.5.1
		 * @since   2.7.0
		 * @todo    (maybe) add alias `[wcj_cart_contents_count]`
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_cart_items_total_quantity( $atts ) {
			$_cart = WC()->cart;
			if ( $_cart ) {
				return $_cart->get_cart_contents_count();
			}
			return '';
		}

		/**
		 * Wcj_cart_items_total_weight.
		 *
		 * @version 3.5.1
		 * @todo    (maybe) add alias `[wcj_cart_contents_weight]`
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_cart_items_total_weight( $atts ) {
			$_cart = WC()->cart;
			if ( $_cart ) {
				return $_cart->get_cart_contents_weight();
			}
			return '';
		}

		/**
		 * Wcj_cart_total.
		 *
		 * Gets the cart contents total (after calculation).
		 *
		 * @version 3.5.0
		 * @since   2.8.0
		 * @todo    ! check `$_cart->get_total( string $context = 'view' )` // Gets cart total after calculation.
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_cart_total( $atts ) {
			$_cart = WC()->cart;
			if ( $_cart ) {
				if ( 1 !== $atts['multiply_by'] ) {
					// `get_cart_contents_total()` - Gets cart total. This is the total of items in the cart, but after discounts. Subtotal is before discounts.
					$cart_total = wc_prices_include_tax() ? WC()->cart->get_cart_contents_total() + WC()->cart->get_cart_contents_tax() : WC()->cart->get_cart_contents_total();
					return wc_price( $atts['multiply_by'] * $cart_total );
				} else {
					return $_cart->get_cart_total();
				}
			}
			return '';
		}

		/**
		 * Wcj_cart_subtotal.
		 *
		 * Gets the sub total (after calculation).
		 *
		 * @version 3.5.1
		 * @since   3.5.1
		 * @todo    `get_cart_subtotal( boolean $compound = false )` (i.e. add `$atts['compound']`)
		 * @todo    check `$_cart->get_displayed_subtotal()`
		 * @todo    check `$_cart->get_subtotal()`
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_cart_subtotal( $atts ) {
			$_cart = WC()->cart;
			if ( $_cart ) {
				return $_cart->get_cart_subtotal();
			}
			return '';
		}

		/**
		 * Wcj_cart_subtotal_tax.
		 *
		 * @version 3.5.1
		 * @since   3.5.1
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_cart_subtotal_tax( $atts ) {
			$_cart = WC()->cart;
			if ( $_cart ) {
				return wc_price( $_cart->get_subtotal_tax() );
			}
			return '';
		}

		/**
		 * Wcj_cart_tax.
		 *
		 * Gets the cart tax (after calculation).
		 *
		 * @version 3.5.1
		 * @since   3.5.1
		 * @todo    check `$_cart->get_total_tax()`
		 * @todo    check `$_cart->get_cart_contents_tax()`
		 * @todo    check `$_cart->get_tax_amount( string $tax_rate_id )` // Get a tax amount.
		 * @todo    check `$_cart->get_taxes_total( boolean $compound = true, boolean $display = true )` // Get tax row amounts with or without compound taxes includes.
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_cart_tax( $atts ) {
			$_cart = WC()->cart;
			if ( $_cart ) {
				return $_cart->get_cart_tax();
			}
			return '';
		}

		/**
		 * Wcj_cart_total_ex_tax.
		 *
		 * Gets the total excluding taxes.
		 *
		 * @version 3.5.1
		 * @since   3.5.1
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_cart_total_ex_tax( $atts ) {
			$_cart = WC()->cart;
			if ( $_cart ) {
				return $_cart->get_total_ex_tax();
			}
			return '';
		}

		/**
		 * Wcj_cart_shipping_total.
		 *
		 * @version 3.5.1
		 * @since   3.5.1
		 * @todo    check `$_cart->get_shipping_total()`
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_cart_shipping_total( $atts ) {
			$_cart = WC()->cart;
			if ( $_cart ) {
				return $_cart->get_cart_shipping_total();
			}
			return '';
		}

		/**
		 * Wcj_cart_shipping_tax.
		 *
		 * Get shipping_tax.
		 *
		 * @version 3.5.1
		 * @since   3.5.1
		 * @todo    check `$_cart->get_shipping_tax_amount( string $tax_rate_id )` // Get a tax amount
		 * @param array $atts The user defined shortcode attributes.
		 */
		public function wcj_cart_shipping_tax( $atts ) {
			$_cart = WC()->cart;
			if ( $_cart ) {
				return wc_price( $_cart->get_shipping_tax() );
			}
			return '';
		}

	}

endif;

return new WCJ_Cart_Shortcodes();
