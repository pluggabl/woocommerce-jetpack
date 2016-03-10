<?php
/**
 * WooCommerce Jetpack Shipping
 *
 * The WooCommerce Jetpack Shipping class.
 *
 * @version 2.4.4
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Shipping' ) ) :

class WCJ_Shipping extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.4.4
	 */
	function __construct() {

		$this->id         = 'shipping';
		$this->short_desc = __( 'Shipping', 'woocommerce-jetpack' );
		$this->desc       = __( 'Hide WooCommerce shipping when free is available.', 'woocommerce-jetpack' );
		parent::__construct();

		if ( $this->is_enabled() ) {
//			include_once( 'shipping/class-wc-shipping-wcj-custom.php' );
//			add_filter( 'woocommerce_available_shipping_methods', array( $this, 'hide_all_shipping_when_free_is_available' ), 10, 1 );
			add_filter( 'woocommerce_package_rates',     array( $this, 'hide_shipping_when_free_is_available' ), 10, 2 );
			add_filter( 'woocommerce_shipping_settings', array( $this, 'add_hide_shipping_if_free_available_fields' ), 100 );

			if ( 'yes' === get_option( 'wcj_shipping_left_to_free_info_enabled_cart', 'no' ) ) {
				add_action(
					get_option( 'wcj_shipping_left_to_free_info_position_cart', 'woocommerce_after_cart_totals' ),
					array( $this, 'show_left_to_free_shipping_info_cart' ),
					get_option( 'wcj_shipping_left_to_free_info_priority_cart', 10 )
				);
			}
			if ( 'yes' === apply_filters( 'wcj_get_option_filter', 'no', get_option( 'wcj_shipping_left_to_free_info_enabled_mini_cart', 'no' ) ) ) {
				add_action(
					get_option( 'wcj_shipping_left_to_free_info_position_mini_cart', 'woocommerce_after_mini_cart' ),
					array( $this, 'show_left_to_free_shipping_info_mini_cart' ),
					get_option( 'wcj_shipping_left_to_free_info_priority_mini_cart', 10 )
				);
			}
			if ( 'yes' === apply_filters( 'wcj_get_option_filter', 'no', get_option( 'wcj_shipping_left_to_free_info_enabled_checkout', 'no' ) ) ) {
				add_action(
					get_option( 'wcj_shipping_left_to_free_info_position_checkout', 'woocommerce_checkout_after_order_review' ),
					array( $this, 'show_left_to_free_shipping_info_checkout' ),
					get_option( 'wcj_shipping_left_to_free_info_priority_checkout', 10 )
				);
			}
		}
	}

	/**
	 * show_left_to_free_shipping_info_checkout.
	 *
	 * @version 2.4.4
	 * @since   2.4.4
	 */
	function show_left_to_free_shipping_info_checkout() {
		$this->show_left_to_free_shipping_info( get_option( 'wcj_shipping_left_to_free_info_content_checkout', __( '%left_to_free% left to free shipping', 'woocommerce-jetpack' ) ) );
	}

	/**
	 * show_left_to_free_shipping_info_mini_cart.
	 *
	 * @version 2.4.4
	 * @since   2.4.4
	 */
	function show_left_to_free_shipping_info_mini_cart() {
		$this->show_left_to_free_shipping_info( get_option( 'wcj_shipping_left_to_free_info_content_mini_cart', __( '%left_to_free% left to free shipping', 'woocommerce-jetpack' ) ) );
	}

	/**
	 * show_left_to_free_shipping_info_cart.
	 *
	 * @version 2.4.4
	 * @since   2.4.4
	 */
	function show_left_to_free_shipping_info_cart() {
		$this->show_left_to_free_shipping_info( get_option( 'wcj_shipping_left_to_free_info_content_cart', __( '%left_to_free% left to free shipping', 'woocommerce-jetpack' ) ) );
	}

	/**
	 * show_left_to_free_shipping_info.
	 *
	 * @version 2.4.4
	 * @since   2.4.4
	 */
	function show_left_to_free_shipping_info( $content ) {
		echo wcj_get_left_to_free_shipping( $content );
	}

	/**
	 * hide_shipping_when_free_is_available.
	 *
	 * @version 2.4.4
	 */
	function hide_shipping_when_free_is_available( $rates, $package ) {
		// Only modify rates if free_shipping is present
		if ( isset( $rates['free_shipping'] ) ) {
			// To unset a single rate/method, do the following. This example unsets flat_rate shipping
			if ( 'yes' === get_option( 'wcj_shipping_hide_if_free_available_local_delivery' ) ) {
				unset( $rates['local_delivery'] );
			}
			if ( 'yes' === get_option( 'wcj_shipping_hide_if_free_available_all' ) ) {
				// To unset all methods except for free_shipping, do the following
				$free_shipping          = $rates['free_shipping'];
				$rates                  = array();
				$rates['free_shipping'] = $free_shipping;
			}
		}
		return $rates;
	}

	/**
	* Hide ALL Shipping option when free shipping is available
	*
	* @param array $available_methods
	*/
	/* function hide_all_shipping_when_free_is_available( $available_methods ) {
		if( isset( $available_methods['free_shipping'] ) ) {
			// Get Free Shipping array into a new array
			$freeshipping = array();
			$freeshipping = $available_methods['free_shipping'];
			// Empty the $available_methods array
			unset( $available_methods );
			// Add Free Shipping back into $avaialble_methods
			$available_methods = array();
			$available_methods[] = $freeshipping;
		}
		return $available_methods;
	} */

	/**
	 * add_hide_shipping_if_free_available_fields.
	 *
	 * @version 2.4.4
	 */
	function add_hide_shipping_if_free_available_fields( $settings ) {
		$updated_settings = array();
		foreach ( $settings as $section ) {
			if ( isset( $section['type'] ) && 'shipping_methods' === $section['type'] ) {
				$updated_settings[] = array(
					'title'    => __( 'Booster: Hide shipping', 'woocommerce-jetpack' ),
					'desc'     => __( 'Hide local delivery when free is available', 'woocommerce-jetpack' ),
					'desc_tip' => __( '', 'woocommerce-jetpack' ),
					'id'       => 'wcj_shipping_hide_if_free_available_local_delivery',
					'default'  => 'no',
					'type'     => 'checkbox',
					'checkboxgroup' => 'start',
				);
				$updated_settings[] = array(
					'desc'     => __( 'Hide all when free is available', 'woocommerce-jetpack' ),
					'desc_tip' => __( '', 'woocommerce-jetpack' ),
					'id'       => 'wcj_shipping_hide_if_free_available_all',
					'default'  => 'no',
					'type'     => 'checkbox',
					'checkboxgroup' => 'end',
				);
			}
			$updated_settings[] = $section;
		}
		return $updated_settings;
	}

	/**
	 * get_settings.
	 *
	 * @version 2.4.4
	 */
	function get_settings() {
		$settings = array(
			array(
				'title'    => __( 'Hide if free is available', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'desc'     => __( 'This section lets you hide other shipping options when free shipping is available on shop frontend.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_shipping_hide_if_free_available_options',
			),
			array(
				'title'    => __( 'Hide shipping', 'woocommerce-jetpack' ),
				'desc'     => __( 'Hide local delivery when free is available', 'woocommerce-jetpack' ),
				'id'       => 'wcj_shipping_hide_if_free_available_local_delivery',
				'default'  => 'no',
				'type'     => 'checkbox',
				'checkboxgroup' => 'start',
			),
			array(
				'desc'     => __( 'Hide all when free is available', 'woocommerce-jetpack' ),
				'id'       => 'wcj_shipping_hide_if_free_available_all',
				'default'  => 'no',
				'type'     => 'checkbox',
				'checkboxgroup' => 'end',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_shipping_hide_if_free_available_options',
			),
			array(
				'title'    => __( 'Left to Free Shipping Info Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'desc'     => __( 'This section lets you enable info or cart, mini cart and checkout pages.', 'woocommerce-jetpack' )
					. '<br>' . __( 'You can also use <em>Booster - Left to Free Shipping</em> widget, <em>[wcj_get_left_to_free_shipping content=""]</em> shortcode or <em>wcj_get_left_to_free_shipping( $content );</em> function.', 'woocommerce-jetpack' )
					. '<br>' . __( 'In content you can use: <em>%left_to_free%</em> and <em>%free_shipping_min_amount%</em> shortcodes.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_shipping_left_to_free_info_options',
			),
			array(
				'title'    => __( 'Info on Cart', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'id'       => 'wcj_shipping_left_to_free_info_enabled_cart',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'title'    => '',
				'desc'     => __( 'Content', 'woocommerce-jetpack' ),
				'id'       => 'wcj_shipping_left_to_free_info_content_cart',
				'default'  => __( '%left_to_free% left to free shipping', 'woocommerce-jetpack' ),
				'type'     => 'textarea',
				'css'      => 'width:30%;min-width:300px;height:100px;',
			),
			array(
				'title'    => '',
				'desc'     => __( 'Position', 'woocommerce-jetpack' ),
				'id'       => 'wcj_shipping_left_to_free_info_position_cart',
				'default'  => 'woocommerce_after_cart_totals',
				'type'     => 'select',
				'options'  => wcj_get_cart_filters(),
				'css'      => 'width:250px;',
			),
			array(
				'title'    => '',
				'desc'     => __( 'Order (Priority)', 'woocommerce-jetpack' ),
				'id'       => 'wcj_shipping_left_to_free_info_priority_cart',
				'default'  => 10,
				'type'     => 'number',
				'css'      => 'width:250px;',
			),
			array(
				'title'    => __( 'Info on Mini Cart', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'id'       => 'wcj_shipping_left_to_free_info_enabled_mini_cart',
				'default'  => 'no',
				'type'     => 'checkbox',
				'custom_attributes' => apply_filters( 'get_wc_jetpack_plus_message', '', 'disabled' ),
				'desc_tip' => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
			),
			array(
				'title'    => '',
				'desc'     => __( 'Content', 'woocommerce-jetpack' ),
				'id'       => 'wcj_shipping_left_to_free_info_content_mini_cart',
				'default'  => __( '%left_to_free% left to free shipping', 'woocommerce-jetpack' ),
				'type'     => 'textarea',
				'css'      => 'width:30%;min-width:300px;height:100px;',
			),
			array(
				'title'    => '',
				'desc'     => __( 'Position', 'woocommerce-jetpack' ),
				'id'       => 'wcj_shipping_left_to_free_info_position_mini_cart',
				'default'  => 'woocommerce_after_mini_cart',
				'type'     => 'select',
				'options'  => array(
					'woocommerce_before_mini_cart'                    => __( 'Before mini cart', 'woocommerce-jetpack' ),
					'woocommerce_widget_shopping_cart_before_buttons' => __( 'Before buttons', 'woocommerce-jetpack' ),
					'woocommerce_after_mini_cart'                     => __( 'After mini cart', 'woocommerce-jetpack' ),
				),
				'css'      => 'width:250px;',
			),
			array(
				'title'    => '',
				'desc'     => __( 'Order (Priority)', 'woocommerce-jetpack' ),
				'id'       => 'wcj_shipping_left_to_free_info_priority_mini_cart',
				'default'  => 10,
				'type'     => 'number',
				'css'      => 'width:250px;',
			),
			array(
				'title'    => __( 'Info on Checkout', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'id'       => 'wcj_shipping_left_to_free_info_enabled_checkout',
				'default'  => 'no',
				'type'     => 'checkbox',
				'custom_attributes' => apply_filters( 'get_wc_jetpack_plus_message', '', 'disabled' ),
				'desc_tip' => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
			),
			array(
				'title'    => '',
				'desc'     => __( 'Content', 'woocommerce-jetpack' ),
				'id'       => 'wcj_shipping_left_to_free_info_content_checkout',
				'default'  => __( '%left_to_free% left to free shipping', 'woocommerce-jetpack' ),
				'type'     => 'textarea',
				'css'      => 'width:30%;min-width:300px;height:100px;',
			),
			array(
				'title'    => '',
				'desc'     => __( 'Position', 'woocommerce-jetpack' ),
				'id'       => 'wcj_shipping_left_to_free_info_position_checkout',
				'default'  => 'woocommerce_checkout_after_order_review',
				'type'     => 'select',
				'options'  => array(
					'woocommerce_before_checkout_form'              => __( 'Before checkout form', 'woocommerce-jetpack' ),
					'woocommerce_checkout_before_customer_details'  => __( 'Before customer details', 'woocommerce-jetpack' ),
					'woocommerce_checkout_billing'                  => __( 'Billing', 'woocommerce-jetpack' ),
					'woocommerce_checkout_shipping'                 => __( 'Shipping', 'woocommerce-jetpack' ),
					'woocommerce_checkout_after_customer_details'   => __( 'After customer details', 'woocommerce-jetpack' ),
					'woocommerce_checkout_before_order_review'      => __( 'Before order review', 'woocommerce-jetpack' ),
					'woocommerce_checkout_order_review'             => __( 'Order review', 'woocommerce-jetpack' ),
					'woocommerce_checkout_after_order_review'       => __( 'After order review', 'woocommerce-jetpack' ),
					'woocommerce_after_checkout_form'               => __( 'After checkout form', 'woocommerce-jetpack' ),
				),
				'css'      => 'width:250px;',
			),
			array(
				'title'    => '',
				'desc'     => __( 'Order (Priority)', 'woocommerce-jetpack' ),
				'id'       => 'wcj_shipping_left_to_free_info_priority_checkout',
				'default'  => 10,
				'type'     => 'number',
				'css'      => 'width:250px;',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_shipping_left_to_free_info_options',
			),
		);
		return $this->add_standard_settings( $settings );
	}
}

endif;

return new WCJ_Shipping();
