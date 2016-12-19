<?php
/**
 * WooCommerce Jetpack Orders
 *
 * The WooCommerce Jetpack Orders class.
 *
 * @version 2.5.7
 * @since   2.5.7
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Order_Min_Amount' ) ) :

class WCJ_Order_Min_Amount extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	public function __construct() {

		$this->id         = 'order_min_amount';
		$this->short_desc = __( 'Order Minimum Amount', 'woocommerce-jetpack' );
		$this->desc       = __( 'Minimum WooCommerce order amount (optionally by user role).', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-order-minimum-amount/';
		parent::__construct();

		add_action( 'init', array( $this, 'add_settings_hook' ) );

		if ( $this->is_enabled() ) {
			add_action( 'init', array( $this, 'add_order_minimum_amount_hooks' ) );
		}
	}

	/**
	 * add_order_minimum_amount_hooks.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function add_order_minimum_amount_hooks() {
		$is_order_minimum_amount_enabled = false;
		if ( get_option( 'wcj_order_minimum_amount', 0 ) > 0 ) {
			$is_order_minimum_amount_enabled = true;
		} else {
			foreach ( wcj_get_user_roles() as $role_key => $role_data ) {
				if ( get_option( 'wcj_order_minimum_amount_by_user_role_' . $role_key, 0 ) > 0 ) {
					$is_order_minimum_amount_enabled = true;
					break;
				}
			}
		}
		if ( $is_order_minimum_amount_enabled ) {
			add_action( 'woocommerce_checkout_process', array( $this, 'order_minimum_amount' ) );
			add_action( 'woocommerce_before_cart',      array( $this, 'order_minimum_amount' ) );
			if ( 'yes' === get_option( 'wcj_order_minimum_amount_stop_from_seeing_checkout' ) ) {
				add_action( 'wp',                array( $this, 'stop_from_seeing_checkout' ), 100 );
//				add_action( 'template_redirect', array( $this, 'stop_from_seeing_checkout' ), 100 );
			}
		}
	}

	/**
	 * get_order_minimum_amount_with_user_roles.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function get_order_minimum_amount_with_user_roles() {
		$minimum = get_option( 'wcj_order_minimum_amount' );
		$current_user_role = wcj_get_current_user_first_role();
		foreach ( wcj_get_user_roles() as $role_key => $role_data ) {
			if ( $role_key === $current_user_role ) {
				$order_minimum_amount_by_user_role = get_option( 'wcj_order_minimum_amount_by_user_role_' . $role_key, 0 );
				if ( $order_minimum_amount_by_user_role > /* $minimum */ 0 ) {
					$minimum = $order_minimum_amount_by_user_role;
				}
				break;
			}
		}
		return $minimum;
	}

	/**
	 * get_cart_total_for_minimal_order_amount.
	 *
	 * @version 2.5.5
	 * @since   2.5.5
	 */
	private function get_cart_total_for_minimal_order_amount() {
		$cart_total = WC()->cart->total;
		if ( 'yes' === get_option( 'wcj_order_minimum_amount_exclude_shipping', 'no' ) ) {
			$shipping_total     = isset( WC()->cart->shipping_total )     ? WC()->cart->shipping_total     : 0;
			$shipping_tax_total = isset( WC()->cart->shipping_tax_total ) ? WC()->cart->shipping_tax_total : 0;
			$cart_total -= ( $shipping_total + $shipping_tax_total );
		}
		return $cart_total;
	}

	/**
	 * order_minimum_amount.
	 *
	 * @version 2.5.5
	 */
	public function order_minimum_amount() {
		$minimum = $this->get_order_minimum_amount_with_user_roles();
		if ( 0 == $minimum ) {
			return;
		}
		$cart_total = $this->get_cart_total_for_minimal_order_amount();
		if ( $cart_total < $minimum ) {
			if( is_cart() ) {
				if ( 'yes' === get_option( 'wcj_order_minimum_amount_cart_notice_enabled' ) ) {
					wc_print_notice(
						sprintf( apply_filters( 'booster_get_option', 'You must have an order with a minimum of %s to place your order, your current order total is %s.', get_option( 'wcj_order_minimum_amount_cart_notice_message' ) ),
							woocommerce_price( $minimum ),
							woocommerce_price( $cart_total )
						),
						'notice'
					);
				}
			} else {
				wc_add_notice(
					sprintf( apply_filters( 'booster_get_option', 'You must have an order with a minimum of %s to place your order, your current order total is %s.', get_option( 'wcj_order_minimum_amount_error_message' ) ),
						woocommerce_price( $minimum ),
						woocommerce_price( $cart_total )
					),
					'error'
				);
			}
		}
	}

	/**
	 * stop_from_seeing_checkout.
	 *
	 * @version 2.5.5
	 */
	public function stop_from_seeing_checkout( $wp ) {
//		if ( is_admin() ) return;
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
			wp_safe_redirect( $woocommerce->cart->get_cart_url() );
		}
	}

	/**
	 * add_settings_hook.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function add_settings_hook() {
		add_filter( 'wcj_order_min_amount_settings', array( $this, 'add_settings' ) );
	}

	/**
	 * get_settings.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function get_settings() {
		$settings = apply_filters( 'wcj_order_min_amount_settings', array() );
		return $this->add_standard_settings( $settings );
	}

	/**
	 * add_settings.
	 *
	 * @version 2.5.7
	 * @since   2.5.7
	 */
	function add_settings() {
		$settings = array(
			array(
				'title'    => __( 'Order Minimum Amount', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'desc'     => __( 'This section lets you set minimum order amount.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_order_minimum_amount_options',
			),
			array(
				'title'    => __( 'Amount', 'woocommerce-jetpack' ),
				'desc'     => __( 'Minimum order amount. Set to 0 to disable.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_order_minimum_amount',
				'default'  => 0,
				'type'     => 'number',
				'custom_attributes' => array(
					'step' => '0.0001',
					'min'  => '0',
				),
			),
			array(
				'title'    => __( 'Exclude Shipping from Cart Total', 'woocommerce-jetpack' ),
				'desc'     => __( 'Exclude', 'woocommerce-jetpack' ),
				'id'       => 'wcj_order_minimum_amount_exclude_shipping',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Error message', 'woocommerce-jetpack' ),
				'desc'     => apply_filters( 'booster_get_message', '', 'desc' ),
				'desc_tip' => __( 'Message to customer if order is below minimum amount. Default: You must have an order with a minimum of %s to place your order, your current order total is %s.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_order_minimum_amount_error_message',
				'default'  => 'You must have an order with a minimum of %s to place your order, your current order total is %s.',
				'type'     => 'textarea',
				'custom_attributes' => apply_filters( 'booster_get_message', '', 'readonly' ),
				'css'      => 'width:50%;min-width:300px;',
			),
			array(
				'title'    => __( 'Add notice to cart page also', 'woocommerce-jetpack' ),
				'desc'     => __( 'Add', 'woocommerce-jetpack' ),
				'id'       => 'wcj_order_minimum_amount_cart_notice_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Message on cart page', 'woocommerce-jetpack' ),
				'desc'     => apply_filters( 'booster_get_message', '', 'desc' ),
				'desc_tip' => __( 'Message to customer if order is below minimum amount. Default: You must have an order with a minimum of %s to place your order, your current order total is %s.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_order_minimum_amount_cart_notice_message',
				'default'  => 'You must have an order with a minimum of %s to place your order, your current order total is %s.',
				'type'     => 'textarea',
				'custom_attributes' => apply_filters( 'booster_get_message', '', 'readonly' ),
				'css'      => 'width:50%;min-width:300px;',
			),
			array(
				'title'    => __( 'Stop customer from seeing the Checkout page if minimum amount not reached.', 'woocommerce-jetpack' ),
				'desc'     => __( 'Redirect back to Cart page', 'woocommerce-jetpack' ),
				'id'       => 'wcj_order_minimum_amount_stop_from_seeing_checkout',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_order_minimum_amount_options',
			),
			array(
				'title'    => __( 'Order Minimum Amount by User Role', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_order_minimum_amount_by_ser_role_options',
				'desc'     => sprintf( __( 'Custom roles can be added via "Add/Manage Custom Roles" tool in Booster\'s <a href="%s">General</a> module.', 'woocommerce-jetpack' ),
					admin_url( 'admin.php?page=wc-settings&tab=jetpack&wcj-cat=emails_and_misc&section=general' ) ),
			),
		);
		$c = array( 'guest', 'administrator', 'customer' );
		$is_r = apply_filters( 'booster_get_message', '', 'readonly' );
		if ( '' == $is_r ) {
			$is_r = array();
		}
		foreach ( wcj_get_user_roles() as $role_key => $role_data ) {
			$settings = array_merge( $settings, array(
				array(
					'title'    => $role_data['name'],
					'id'       => 'wcj_order_minimum_amount_by_user_role_' . $role_key,
					'default'  => 0,
					'type'     => 'number',
					'custom_attributes' => ( ! in_array( $role_key, $c ) ? array_merge( array( 'step' => '0.0001', 'min'  => '0', ), $is_r ) : array( 'step' => '0.0001', 'min'  => '0', ) ),
					'desc_tip' => ( ! in_array( $role_key, $c ) ? apply_filters( 'booster_get_message', '', 'desc_no_link' ) : '' ),
				),
			) );
		}
		$settings = array_merge( $settings, array(
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_order_minimum_amount_by_ser_role_options',
			),
		) );
		return $settings;
	}
}

endif;

return new WCJ_Order_Min_Amount();
