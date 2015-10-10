<?php
/**
 * WooCommerce Jetpack Orders
 *
 * The WooCommerce Jetpack Orders class.
 *
 * @version 2.3.7
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Orders' ) ) :

class WCJ_Orders extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.3.7
	 */
	public function __construct() {

		$this->id         = 'orders';
		$this->short_desc = __( 'Orders', 'woocommerce-jetpack' );
		$this->desc       = __( 'Minimum WooCommerce order amount; orders auto-complete; custom admin order list columns.', 'woocommerce-jetpack' );
		parent::__construct();

		if ( $this->is_enabled() ) {

			if ( get_option( 'wcj_order_minimum_amount' ) > 0 ) {
				add_action( 'woocommerce_checkout_process', array( $this, 'order_minimum_amount' ) );
				add_action( 'woocommerce_before_cart',      array( $this, 'order_minimum_amount' ) );
				if ( 'yes' === get_option( 'wcj_order_minimum_amount_stop_from_seeing_checkout' ) ) {
					add_action( 'wp',                array( $this, 'stop_from_seeing_checkout' ), 100 );
//					add_action( 'template_redirect', array( $this, 'stop_from_seeing_checkout' ), 100 );
				}
			}

			if ( 'yes' === get_option( 'wcj_order_auto_complete_enabled' ) ) {
				add_action( 'woocommerce_thankyou', array( $this, 'auto_complete_order' ) );
			}

			if ( 'yes' === get_option( 'wcj_orders_list_custom_columns_country', 'no' ) ) {
				// Country column
				add_filter( 'manage_edit-shop_order_columns',        array( $this, 'add_order_column' ),     PHP_INT_MAX );
				add_action( 'manage_shop_order_posts_custom_column', array( $this, 'render_order_columns' ), PHP_INT_MAX );
				// Country filtering
				add_action( 'restrict_manage_posts', array( $this, 'restrict_manage_posts' ) );
				add_filter( 'parse_query',           array( $this, 'orders_by_country_admin_filter_query' ) );
			}
		}
	}

	/**
	 * Filter the orders in admin based on options
	 *
	 * @access public
	 * @param mixed $query
	 * @return void
	 */
	function orders_by_country_admin_filter_query( $query ) {
		global $typenow, $wp_query;
		if ( $typenow == 'shop_order' && isset( $_GET['country'] ) && 'all' != $_GET['country'] ) {
			$query->query_vars['meta_value'] = $_GET['country'];//'FR';
			$query->query_vars['meta_key']   = '_billing_country';
		}
	}

	/**
	 * Filters for post types
	 */
	public function restrict_manage_posts() {
		global $typenow, $wp_query;

		if ( in_array( $typenow, wc_get_order_types( 'order-meta-boxes' ) ) ) {

			$selected_coutry = isset( $_GET['country'] ) ? $_GET['country'] : 'all';

			$countries = array_merge( array( 'all' => __( 'All countries', 'woocommerce-jetpack' ) ), wcj_get_countries() );
			echo '<select id="country" name="country">';
			foreach ( $countries as $code => $name ) {
				echo '<option value="' . $code . '" ' . selected( $code, $selected_coutry, false ) . '>' . $name . '</option>';
			}
			echo '</select>';
		}
	}

	/**
	 * add_order_column.
	 */
	function add_order_column( $columns ) {
		$columns['country'] = __( 'Country', 'woocommerce-jetpack' );
		return $columns;
	}

	/**
	 * wcj_get_country_flag_by_code.
	 */
	public function wcj_get_country_flag_by_code( $country_code ) {
		$img_src = plugins_url() . '/' . 'woocommerce-jetpack' . '/assets/images/flag-icons/' . strtolower( $country_code ) . '.png';
		return '<img src="' . $img_src . '" title="' . wcj_get_country_name_by_code( $country_code ) . '">';
	}
	/**
	 * Output custom columns for orders
	 * @param  string $column
	 */
	public function render_order_columns( $column ) {

		if ( 'country' != $column ) {
			return;
		}

		$order = wc_get_order( get_the_ID() );
		//$country_code = wcj_get_customer_country( $order->customer_user );
		$country_code = $order->billing_country;

		echo ( 2 == strlen( $country_code ) )
			? $this->wcj_get_country_flag_by_code( $country_code ) . ' ' . wcj_get_country_name_by_code( $country_code )
			: wcj_get_country_name_by_code( $country_code );
	}

	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//																				AUTO COMPLETE																		  //
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	* Auto Complete all WooCommerce orders.
	*/
	public function auto_complete_order( $order_id ) {

		global $woocommerce;

		if ( !$order_id )
			return;
		$order = new WC_Order( $order_id );
		$order->update_status( 'completed' );
	}

	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//																				MINIMUM AMOUNT																		  //
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	/**
	 * order_minimum_amount.
	 */
	public function order_minimum_amount() {

		$minimum = get_option( 'wcj_order_minimum_amount' );
		if ( WC()->cart->total < $minimum ) {

			if( is_cart() ) {
				if ( 'yes' === get_option( 'wcj_order_minimum_amount_cart_notice_enabled' ) ) {
					wc_print_notice(
						sprintf( apply_filters( 'wcj_get_option_filter', 'You must have an order with a minimum of %s to place your order, your current order total is %s.', get_option( 'wcj_order_minimum_amount_cart_notice_message' ) ),
							woocommerce_price( $minimum ),
							woocommerce_price( WC()->cart->total )
						),
						'notice'
					);
				}
			} else {
				wc_add_notice(
					sprintf( apply_filters( 'wcj_get_option_filter', 'You must have an order with a minimum of %s to place your order, your current order total is %s.', get_option( 'wcj_order_minimum_amount_error_message' ) ),
						woocommerce_price( $minimum ),
						woocommerce_price( WC()->cart->total )
					),
					'error'
				);
			}
		}
	}

	/**
	 * stop_from_seeing_checkout.
	 *
	 * @version 2.3.7
	 */
	public function stop_from_seeing_checkout( $wp ) {
//		if ( is_admin() ) return;
		global $woocommerce;
		if ( ! isset( $woocommerce ) || ! is_object( $woocommerce ) ) return;
		if ( ! isset( $woocommerce->cart ) || ! is_object( $woocommerce->cart ) ) return;
		$the_cart_total = isset( $woocommerce->cart->cart_contents_total ) ? $woocommerce->cart->cart_contents_total : 0;
		if ( 0 != $the_cart_total && $the_cart_total < get_option( 'wcj_order_minimum_amount' ) && is_checkout() )
			wp_safe_redirect( $woocommerce->cart->get_cart_url() );
	}

	/**
	 * Add settings arrays to Jetpack Settings.
	 */
	function get_settings() {

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
				'title'    => __( 'Error message', 'woocommerce-jetpack' ),
				'desc'     => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'desc_tip' => __( 'Message to customer if order is below minimum amount. Default: You must have an order with a minimum of %s to place your order, your current order total is %s.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_order_minimum_amount_error_message',
				'default'  => 'You must have an order with a minimum of %s to place your order, your current order total is %s.',
				'type'     => 'textarea',
				'custom_attributes'
				           => apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),
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
				'desc'     => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'desc_tip' => __( 'Message to customer if order is below minimum amount. Default: You must have an order with a minimum of %s to place your order, your current order total is %s.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_order_minimum_amount_cart_notice_message',
				'default'  => 'You must have an order with a minimum of %s to place your order, your current order total is %s.',
				'type'     => 'textarea',
				'custom_attributes'
				           => apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),
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
				'title'    => __( 'Orders Auto-Complete', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'desc'     => __( 'This section lets you enable orders auto-complete function.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_order_auto_complete_options',
			),

			array(
				'title'    => __( 'Auto-complete all WooCommerce orders', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'E.g. if you sell digital products then you are not shipping anything and you may want auto-complete all your orders.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_order_auto_complete_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
			),

			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_order_auto_complete_options',
			),

			array(
				'title'    => __( 'Orders List Custom Columns', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'desc'     => __( 'This section lets you add custom columns to WooCommerce orders list.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_orders_list_custom_columns_options',
			),

			array(
				'title'    => __( 'Country', 'woocommerce-jetpack' ),
				'desc'     => __( 'Add', 'woocommerce-jetpack' ),
				'id'       => 'wcj_orders_list_custom_columns_country',
				'default'  => 'no',
				'type'     => 'checkbox',
			),

			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_orders_list_custom_columns_options',
			),

		);

		return $this->add_enable_module_setting( $settings );
	}
}

endif;

return new WCJ_Orders();
