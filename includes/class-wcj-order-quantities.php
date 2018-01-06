<?php
/**
 * Booster for WooCommerce - Module - Order Min/Max Quantities
 *
 * @version 3.2.3
 * @since   2.9.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Order_Quantities' ) ) :

class WCJ_Order_Quantities extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.2.2
	 * @since   2.9.0
	 * @todo    for cart: `apply_filters( 'woocommerce_quantity_input_args', wp_parse_args( $args, $defaults ), $product );`
	 * @todo    loop (`woocommerce_loop_add_to_cart_link`)
	 * @todo    (maybe) order quantities by user roles
	 * @todo    (maybe) `woocommerce_quantity_input_step`
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
				if (
					'yes' === apply_filters( 'booster_option', 'no', get_option( 'wcj_order_quantities_min_per_item_quantity_per_product', 'no' ) ) ||
					'yes' === apply_filters( 'booster_option', 'no', get_option( 'wcj_order_quantities_max_per_item_quantity_per_product', 'no' ) )
				) {
					add_action( 'add_meta_boxes',    array( $this, 'add_meta_box' ) );
					add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
				}
				add_filter( 'woocommerce_available_variation', array( $this, 'set_quantity_input_min_max_variation' ), PHP_INT_MAX, 3 );
				if ( 'yes' === get_option( 'wcj_order_quantities_min_section_enabled', 'no' ) ) {
					add_filter( 'woocommerce_quantity_input_min',  array( $this, 'set_quantity_input_min' ), PHP_INT_MAX, 2 );
				}
				if ( 'yes' === get_option( 'wcj_order_quantities_max_section_enabled', 'no' ) ) {
					add_filter( 'woocommerce_quantity_input_max',  array( $this, 'set_quantity_input_max' ), PHP_INT_MAX, 2 );
				}
				add_action( 'wp_enqueue_scripts',              array( $this, 'enqueue_script' ) );
			}
		}
	}

	/**
	 * enqueue_script.
	 *
	 * @version 3.2.3
	 * @since   3.2.2
	 * @todo    `force_on_add_to_cart` for simple products
	 * @todo    make this optional?
	 */
	function enqueue_script() {
		$_product = wc_get_product();
		if ( $_product && $_product->is_type( 'variable' ) ) {
			$quantities_options = array(
				'reset_to_min'         => ( 'reset_to_min' === get_option( 'wcj_order_quantities_variable_variation_change', 'do_nothing' ) ),
				'reset_to_max'         => ( 'reset_to_max' === get_option( 'wcj_order_quantities_variable_variation_change', 'do_nothing' ) ),
				'force_on_add_to_cart' => ( 'yes' === get_option( 'wcj_order_quantities_variable_force_on_add_to_cart', 'no' ) ),
			);
			$product_quantities = array();
			foreach ( $_product->get_available_variations() as $variation ) {
				$product_quantities[ $variation['variation_id'] ] = array(
					'min_qty' => $variation['min_qty'],
					'max_qty' => $variation['max_qty'],
				);
			}
			wp_enqueue_script(  'wcj-order-quantities',  trailingslashit( wcj_plugin_url() ) . 'includes/js/wcj-order-quantities.js', array( 'jquery' ), WCJ()->version, true );
			wp_localize_script( 'wcj-order-quantities', 'product_quantities', $product_quantities );
			wp_localize_script( 'wcj-order-quantities', 'quantities_options', $quantities_options );
		}
	}

	/**
	 * get_product_quantity.
	 *
	 * @version 3.2.2
	 * @since   3.2.2
	 */
	function get_product_quantity( $min_or_max, $_product, $default_qty ) {
		if ( 'no' === get_option( 'wcj_order_quantities_' . $min_or_max . '_section_enabled', 'no' ) ) {
			return $default_qty;
		}
		if (
			'yes' === apply_filters( 'booster_option', 'no', get_option( 'wcj_order_quantities_' . $min_or_max . '_per_item_quantity_per_product', 'no' ) ) &&
			0 != ( $max_or_max_per_item_quantity_per_product = get_post_meta( wcj_get_product_id( $_product ), '_' . 'wcj_order_quantities_' . $min_or_max, true ) )
		) {
			return $max_or_max_per_item_quantity_per_product;
		} elseif ( 0 != ( $max_or_max_per_item_quantity = apply_filters( 'booster_option', 0, get_option( 'wcj_order_quantities_' . $min_or_max . '_per_item_quantity', 0 ) ) ) ) {
			return $max_or_max_per_item_quantity;
		} else {
			return $default_qty;
		}
	}

	/**
	 * set_quantity_input_min_max_variation.
	 *
	 * @version 3.2.2
	 * @since   3.2.2
	 */
	function set_quantity_input_min_max_variation( $args, $_product, $_variation ) {
		$args['min_qty'] = $this->get_product_quantity( 'min', $_variation, $args['min_qty'] );
		$args['max_qty'] = $this->get_product_quantity( 'max', $_variation, $args['max_qty'] );
		$_max = $_variation->get_max_purchase_quantity();
		if ( -1 != $_max && $args['max_qty'] > $_max ) {
			$args['max_qty'] = $_max;
		}
		if ( $args['min_qty'] < 0 ) {
			$args['min_qty'] = '';
		}
		if ( $args['max_qty'] < 0 ) {
			$args['max_qty'] = '';
		}
		return $args;
	}

	/**
	 * set_quantity_input_min.
	 *
	 * @version 3.2.2
	 * @since   3.2.2
	 */
	function set_quantity_input_min( $qty, $_product ) {
		if ( ! $_product->is_type( 'variable' ) ) {
			$min  = $this->get_product_quantity( 'min', $_product, $qty );
			$_max = $_product->get_max_purchase_quantity();
			return ( -1 == $_max || $min < $_max ? $min : $_max );
		} else {
			return $qty;
		}
	}

	/**
	 * set_quantity_input_max.
	 *
	 * @version 3.2.2
	 * @since   3.2.2
	 */
	function set_quantity_input_max( $qty, $_product ) {
		if ( ! $_product->is_type( 'variable' ) ) {
			$max  = $this->get_product_quantity( 'max', $_product, $qty );
			$_max = $_product->get_max_purchase_quantity();
			return ( -1 == $_max || $max < $_max ? $max : $_max );
		} else {
			return $qty;
		}
	}

	/**
	 * stop_from_seeing_checkout.
	 *
	 * @version 3.2.3
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
				wp_safe_redirect( wc_get_cart_url() );
				exit;
			}
		}
		if ( 'yes' === get_option( 'wcj_order_quantities_min_section_enabled', 'no' ) ) {
			if ( ! $this->check_quantities( 'min', $cart_item_quantities, $cart_total_quantity, false, true ) ) {
				wp_safe_redirect( wc_get_cart_url() );
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
	 * @version 3.2.2
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
		if ( apply_filters( 'booster_option', 'no', get_option( 'wcj_order_quantities_' . $min_or_max . '_per_item_quantity_per_product', 'no' ) ) ) {
			foreach ( $cart_item_quantities as $_product_id => $cart_item_quantity ) {
				if ( 0 != ( $max_or_max_per_item_quantity = get_post_meta( $_product_id, '_' . 'wcj_order_quantities_' . $min_or_max, true ) ) ) {
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
		}
		if ( 0 != ( $max_or_max_per_item_quantity = apply_filters( 'booster_option', 0, get_option( 'wcj_order_quantities_' . $min_or_max . '_per_item_quantity', 0 ) ) ) ) {
			foreach ( $cart_item_quantities as $_product_id => $cart_item_quantity ) {
				if (
					'yes' === apply_filters( 'booster_option', 'no', get_option( 'wcj_order_quantities_' . $min_or_max . '_per_item_quantity_per_product', 'no' ) ) &&
					0 != get_post_meta( $_product_id, '_' . 'wcj_order_quantities_' . $min_or_max, true )
				) {
					continue;
				}
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
