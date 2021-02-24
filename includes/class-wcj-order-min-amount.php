<?php
/**
 * Booster for WooCommerce - Module - Order Minimum Amount
 *
 * @version 5.3.8
 * @since   2.5.7
 * @author  Pluggabl LLC.
 * @todo    order max amount
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Order_Min_Amount' ) ) :

class WCJ_Order_Min_Amount extends WCJ_Module {

	private $yith_gift_card_discount = 0;

	/**
	 * Constructor.
	 *
	 * @version 5.2.0
	 * @since   2.5.7
	 */
	function __construct() {

		$this->id         = 'order_min_amount';
		$this->short_desc = __( 'Order Minimum Amount', 'woocommerce-jetpack' );
		$this->desc       = __( 'Minimum order amount. Order Minimum Amount by User Role (Administrator, Guest and Customer available in free version).', 'woocommerce-jetpack' );
		$this->desc_pro   = __( 'Minimum order amount (optionally by user role) .', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-order-minimum-amount';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_action( 'init', array( $this, 'add_order_minimum_amount_hooks' ) );
		}
	}

	/**
	 * add_order_minimum_amount_hooks.
	 *
	 * @version 5.3.8
	 * @since   2.5.3
	 * @todo    (maybe) `template_redirect` instead of `wp`
	 */
	function add_order_minimum_amount_hooks() {
		$is_order_minimum_amount_enabled = false;
		if ( wcj_get_option( 'wcj_order_minimum_amount', 0 ) > 0 ) {
			$is_order_minimum_amount_enabled = true;
		} else {
			foreach ( wcj_get_user_roles() as $role_key => $role_data ) {
				if ( wcj_get_option( 'wcj_order_minimum_amount_by_user_role_' . $role_key, 0 ) > 0 ) {
					$is_order_minimum_amount_enabled = true;
					break;
				}
			}
		}
		if ( $is_order_minimum_amount_enabled ) {
			add_action( 'woocommerce_checkout_process', array( $this, 'order_minimum_amount' ) );
			add_action( 'woocommerce_before_cart',      array( $this, 'order_minimum_amount' ) );
			if( wcj_is_plugin_activated( 'woo-gutenberg-products-block', 'woocommerce-gutenberg-products-block.php' ) ){
				// For the Woocommerce blocks plugin
				add_action( 'wooocommerce_store_api_validate_cart_item',      array( $this, 'order_minimum_amount' ) );
			}	
			if ( 'yes' === wcj_get_option( 'wcj_order_minimum_amount_stop_from_seeing_checkout', 'no' ) ) {
				add_action( 'wp', array( $this, 'stop_from_seeing_checkout' ), 100 );
			}
		}
		add_action( 'yith_ywgc_apply_gift_card_discount_after_cart_total', array( $this, 'get_yith_gift_cards_discount' ), 10, 2 );
	}

	/**
	 * get_yith_gift_cards_discount
	 *
	 * @version 4.9.0
	 * @since   4.9.0
	 *
	 * @param $cart
	 * @param $discount
	 */
	function get_yith_gift_cards_discount( $cart, $discount ) {
		$this->yith_gift_card_discount = $discount;
	}

	/**
	 * get_order_minimum_amount_with_user_roles.
	 *
	 * @version 5.1.0
	 * @since   2.5.3
	 */
	function get_order_minimum_amount_with_user_roles() {
		$minimum = wcj_get_option( 'wcj_order_minimum_amount', 0 );
		$current_user_role = wcj_get_current_user_first_role();
		foreach ( wcj_get_user_roles() as $role_key => $role_data ) {
			if ( $role_key === $current_user_role ) {
				$order_minimum_amount_by_user_role = wcj_get_option( 'wcj_order_minimum_amount_by_user_role_' . $role_key, 0 );
				if ( $order_minimum_amount_by_user_role > 0 ) {
					$minimum = $order_minimum_amount_by_user_role;
				}
				break;
			}
		}
		// Multicurrency (Currency Switcher) module
		if ( WCJ()->modules['multicurrency']->is_enabled() ) {
			$minimum = WCJ()->modules['multicurrency']->change_price( $minimum, null );
		}
		// Price by country module
		if ( WCJ()->modules['price_by_country']->is_enabled() ) {
			$minimum = WCJ()->modules['price_by_country']->core->change_price( $minimum, null );
		}
		// WooCommerce Multilingual
		if ( 'yes' === wcj_get_option( 'wcj_order_minimum_compatibility_wpml_multilingual', 'no' ) ) {
			global $woocommerce_wpml;
			$minimum = ! empty( $woocommerce_wpml ) ? $woocommerce_wpml->multi_currency->prices->convert_price_amount( $minimum ) : $minimum;
		}

		return $minimum;
	}

	/**
	 * get_cart_total_for_minimal_order_amount.
	 *
	 * @version 4.9.0
	 * @since   2.5.5
	 */
	function get_cart_total_for_minimal_order_amount() {
		if ( ! isset( WC()->cart ) ) {
			return 0;
		}
		WC()->cart->calculate_totals();
		$cart_total = WC()->cart->total;
		if ( 'yes' === wcj_get_option( 'wcj_order_minimum_amount_exclude_shipping', 'no' ) ) {
			$shipping_total     = isset( WC()->cart->shipping_total )     ? WC()->cart->shipping_total     : 0;
			$shipping_tax_total = isset( WC()->cart->shipping_tax_total ) ? WC()->cart->shipping_tax_total : 0;
			$cart_total -= ( $shipping_total + $shipping_tax_total );
		}
		if ( 'yes' === wcj_get_option( 'wcj_order_minimum_amount_exclude_discounts', 'no' ) ) {
			$cart_total += ( WC()->cart->get_cart_discount_total() + WC()->cart->get_cart_discount_tax_total() );
		}
		if ('yes' === wcj_get_option( 'wcj_order_minimum_amount_exclude_yith_gift_card_discount', 'no' ) ) {
			$cart_total += $this->yith_gift_card_discount;
		}
		return $cart_total;
	}

	/**
	 * order_minimum_amount.
	 *
	 * @version 2.9.0
	 * @todo    `wcj_order_minimum_amount_checkout_notice_type`
	 */
	function order_minimum_amount() {
		$minimum = $this->get_order_minimum_amount_with_user_roles();
		if ( 0 == $minimum ) {
			return;
		}
		$cart_total = $this->get_cart_total_for_minimal_order_amount();
		if ( $cart_total < $minimum ) {
			if ( is_cart() ) {
				if ( 'yes' === wcj_get_option( 'wcj_order_minimum_amount_cart_notice_enabled', 'no' ) ) {
					$notice_function = wcj_get_option( 'wcj_order_minimum_amount_cart_notice_function', 'wc_print_notice' );
					$notice_function(
						sprintf( apply_filters( 'booster_option', 'You must have an order with a minimum of %s to place your order, your current order total is %s.', wcj_get_option( 'wcj_order_minimum_amount_cart_notice_message' ) ),
							wc_price( $minimum ),
							wc_price( $cart_total )
						),
						get_option( 'wcj_order_minimum_amount_cart_notice_type', 'notice' )
					);
				}
			} else {
				wc_add_notice(
					sprintf( apply_filters( 'booster_option', 'You must have an order with a minimum of %s to place your order, your current order total is %s.', wcj_get_option( 'wcj_order_minimum_amount_error_message' ) ),
						wc_price( $minimum ),
						wc_price( $cart_total )
					),
					'error'
				);
			}
		}
	}

	/**
	 * stop_from_seeing_checkout.
	 *
	 * @version 3.2.3
	 * @todo    (maybe) `if ( is_admin() ) return;`
	 */
	function stop_from_seeing_checkout( $wp ) {
		global $woocommerce;
		if ( ! isset( $woocommerce ) || ! is_object( $woocommerce ) ) {
			return;
		}
		if ( ! isset( $woocommerce->cart ) || ! is_object( $woocommerce->cart ) ) {
			return;
		}
		if ( ! is_checkout() ) {
			return;
		}
		$minimum = $this->get_order_minimum_amount_with_user_roles();
		if ( 0 == $minimum ) {
			return;
		}
		$the_cart_total = $this->get_cart_total_for_minimal_order_amount();
		if ( 0 == $the_cart_total ) {
			return;
		}
		if ( $the_cart_total < $minimum ) {
			wp_safe_redirect( wc_get_cart_url() );
			exit;
		}
	}

}

endif;

return new WCJ_Order_Min_Amount();
