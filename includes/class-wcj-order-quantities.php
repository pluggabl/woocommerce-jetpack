<?php
/**
 * Booster for WooCommerce - Module - Order Min/Max Quantities
 *
 * @version 2.9.0
 * @since   2.9.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Order_Quantities' ) ) :

class WCJ_Order_Quantities extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 * @todo    quantities per item - per product
	 * @todo    (maybe) do not allow to add/remove items (instead of just printing messages)
	 * @todo    (maybe) order quantities by user roles
	 */
	function __construct() {

		$this->id         = 'order_quantities';
		$this->short_desc = __( 'Order Min/Max Quantities', 'woocommerce-jetpack' );
		$this->desc       = __( 'Set min/max product quantities in WooCommerce order.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-order-min-max-quantities';
		parent::__construct();

		if ( $this->is_enabled() ) {
			if ( 'yes' === get_option( 'wcj_order_quantities_max_section_enabled', 'no' ) || 'yes' === get_option( 'wcj_order_quantities_min_section_enabled', 'no' ) ) {
				add_action( 'woocommerce_checkout_process', array( $this, 'check_order_quantities' ) );
				add_action( 'woocommerce_before_cart',      array( $this, 'check_order_quantities' ) );
				if ( 'yes' === get_option( 'wcj_order_quantities_stop_from_seeing_checkout', 'no' ) ) {
					add_action( 'wp', array( $this, 'stop_from_seeing_checkout' ), PHP_INT_MAX );
				}
			}
		}
	}

	/**
	 * stop_from_seeing_checkout.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 */
	function stop_from_seeing_checkout() {
		if ( ! isset( WC()->cart ) ) {
			return;
		}
		if ( ! is_checkout() ) {
			return;
		}
		$cart_item_quantities = WC()->cart->get_cart_item_quantities();
		if ( empty( $cart_item_quantities ) || ! is_array( $cart_item_quantities ) ) {
			return;
		}
		$cart_total_quantity = array_sum( $cart_item_quantities );
		if ( 'yes' === get_option( 'wcj_order_quantities_max_section_enabled', 'no' ) ) {
			if ( ! $this->check_quantities( 'max', $cart_item_quantities, $cart_total_quantity, false, true ) ) {
				wp_safe_redirect( WC()->cart->get_cart_url() );
				exit;
			}
		}
		if ( 'yes' === get_option( 'wcj_order_quantities_min_section_enabled', 'no' ) ) {
			if ( ! $this->check_quantities( 'min', $cart_item_quantities, $cart_total_quantity, false, true ) ) {
				wp_safe_redirect( WC()->cart->get_cart_url() );
				exit;
			}
		}
	}

	/**
	 * print_message.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 */
	function print_message( $message_type, $_is_cart, $required_quantity, $total_quantity, $_product_id = 0 ) {
		if ( $_is_cart ) {
			if ( 'no' === get_option( 'wcj_order_quantities_cart_notice_enabled', 'no' ) ) {
				return;
			}
		}
		switch ( $message_type ) {
			case 'max_cart_total_quantity':
				$replaced_values = array(
					'%max_cart_total_quantity%' => $required_quantity,
					'%cart_total_quantity%'     => $total_quantity,
				);
				$message_template = get_option( 'wcj_order_quantities_max_cart_total_message',
					__( 'Maximum allowed order quantity is %max_cart_total_quantity%. Your current order quantity is %cart_total_quantity%.', 'woocommerce-jetpack' ) );
				break;
			case 'min_cart_total_quantity':
				$replaced_values = array(
					'%min_cart_total_quantity%' => $required_quantity,
					'%cart_total_quantity%'     => $total_quantity,
				);
				$message_template = get_option( 'wcj_order_quantities_min_cart_total_message',
					__( 'Minimum allowed order quantity is %min_cart_total_quantity%. Your current order quantity is %cart_total_quantity%.', 'woocommerce-jetpack' ) );
				break;
			case 'max_per_item_quantity':
				$_product = wc_get_product( $_product_id );
				$replaced_values = array(
					'%max_per_item_quantity%' => $required_quantity,
					'%item_quantity%'         => $total_quantity,
					'%product_title%'         => $_product->get_title(),
				);
				$message_template = get_option( 'wcj_order_quantities_max_per_item_message',
					__( 'Maximum allowed quantity for %product_title% is %max_per_item_quantity%. Your current item quantity is %item_quantity%.', 'woocommerce-jetpack' ) );
				break;
			case 'min_per_item_quantity':
				$_product = wc_get_product( $_product_id );
				$replaced_values = array(
					'%min_per_item_quantity%' => $required_quantity,
					'%item_quantity%'         => $total_quantity,
					'%product_title%'         => $_product->get_title(),
				);
				$message_template = get_option( 'wcj_order_quantities_min_per_item_message',
					__( 'Minimum allowed quantity for %product_title% is %min_per_item_quantity%. Your current item quantity is %item_quantity%.', 'woocommerce-jetpack' ) );
				break;
		}
		$_notice = str_replace( array_keys( $replaced_values ), array_values( $replaced_values ), $message_template );
		if ( $_is_cart ) {
			wc_print_notice( $_notice, 'notice' );
		} else {
			wc_add_notice( $_notice, 'error' );
		}
	}

	/**
	 * check_quantities.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 */
	function check_quantities( $min_or_max, $cart_item_quantities, $cart_total_quantity, $_is_cart, $_return ) {
		if ( 0 != ( $min_or_max_cart_total_quantity = get_option( 'wcj_order_quantities_' . $min_or_max . '_cart_total_quantity', 0 ) ) ) {
			if (
				( 'max' === $min_or_max && $cart_total_quantity > $min_or_max_cart_total_quantity ) ||
				( 'min' === $min_or_max && $cart_total_quantity < $min_or_max_cart_total_quantity )
			) {
				if ( $_return ) {
					return false;
				} else {
					$this->print_message( $min_or_max . '_cart_total_quantity', $_is_cart, $min_or_max_cart_total_quantity, $cart_total_quantity );
				}
			}
		}
		if ( 0 != ( $max_or_max_per_item_quantity = apply_filters( 'booster_get_option', 0, get_option( 'wcj_order_quantities_' . $min_or_max . '_per_item_quantity', 0 ) ) ) ) {
			foreach ( $cart_item_quantities as $_product_id => $cart_item_quantity ) {
				if (
					( 'max' === $min_or_max && $cart_item_quantity > $max_or_max_per_item_quantity ) ||
					( 'min' === $min_or_max && $cart_item_quantity < $max_or_max_per_item_quantity )
				) {
					if ( $_return ) {
						return false;
					} else {
						$this->print_message( $min_or_max . '_per_item_quantity', $_is_cart, $max_or_max_per_item_quantity, $cart_item_quantity, $_product_id );
					}
				}
			}
		}
		if ( $_return ) {
			return true;
		}
	}

	/**
	 * check_order_quantities.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 */
	function check_order_quantities() {
		if ( ! isset( WC()->cart ) ) {
			return;
		}
		$cart_item_quantities = WC()->cart->get_cart_item_quantities();
		if ( empty( $cart_item_quantities ) || ! is_array( $cart_item_quantities ) ) {
			return;
		}
		$cart_total_quantity = array_sum( $cart_item_quantities );
		$_is_cart = is_cart();
		if ( 'yes' === get_option( 'wcj_order_quantities_max_section_enabled', 'no' ) ) {
			$this->check_quantities( 'max', $cart_item_quantities, $cart_total_quantity, $_is_cart, false );
		}
		if ( 'yes' === get_option( 'wcj_order_quantities_min_section_enabled', 'no' ) ) {
			$this->check_quantities( 'min', $cart_item_quantities, $cart_total_quantity, $_is_cart, false );
		}
	}

}

endif;

return new WCJ_Order_Quantities();
