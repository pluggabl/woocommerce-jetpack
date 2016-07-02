<?php
/**
 * WooCommerce Jetpack Shipping
 *
 * The WooCommerce Jetpack Shipping class.
 *
 * @version 2.5.3
 * @author  Algoritmika Ltd.
 * @todo    Redo custom shipping methods according to new (since WC v2.6.0) shipping zones mechanism
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Shipping' ) ) :

class WCJ_Shipping extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.5.3
	 */
	function __construct() {

		$this->id         = 'shipping';
		$this->short_desc = __( 'Shipping', 'woocommerce-jetpack' );
		$this->desc       =
			__( 'Add multiple custom shipping methods to WooCommerce.', 'woocommerce-jetpack' ) . ' ' .
			__( 'Hide WooCommerce shipping when free is available.', 'woocommerce-jetpack') . ' ' .
			__( 'Display "left to free shipping" info.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-shipping/';
		parent::__construct();

		if ( $this->is_enabled() ) {

			// Custom Shipping
			include_once( 'shipping/class-wc-shipping-wcj-custom.php' );

			// Hide if free is available
			if ( 'yes' === get_option( 'wcj_shipping_hide_if_free_available_all', 'no' ) ) {
				add_filter( 'woocommerce_package_rates', array( $this, 'hide_shipping_when_free_is_available' ), 10, 2 );
			}
			add_filter( 'woocommerce_shipping_settings', array( $this, 'add_hide_shipping_if_free_available_fields' ), 100 );

			// Left to Free Shipping
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
	 * @version 2.5.2
	 * @since   2.4.4
	 */
	function show_left_to_free_shipping_info_checkout() {
		$this->show_left_to_free_shipping_info( do_shortcode( get_option( 'wcj_shipping_left_to_free_info_content_checkout', __( '%left_to_free% left to free shipping', 'woocommerce-jetpack' ) ) ) );
	}

	/**
	 * show_left_to_free_shipping_info_mini_cart.
	 *
	 * @version 2.5.2
	 * @since   2.4.4
	 */
	function show_left_to_free_shipping_info_mini_cart() {
		$this->show_left_to_free_shipping_info( do_shortcode( get_option( 'wcj_shipping_left_to_free_info_content_mini_cart', __( '%left_to_free% left to free shipping', 'woocommerce-jetpack' ) ) ) );
	}

	/**
	 * show_left_to_free_shipping_info_cart.
	 *
	 * @version 2.5.2
	 * @since   2.4.4
	 */
	function show_left_to_free_shipping_info_cart() {
		$this->show_left_to_free_shipping_info( do_shortcode( get_option( 'wcj_shipping_left_to_free_info_content_cart', __( '%left_to_free% left to free shipping', 'woocommerce-jetpack' ) ) ) );
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
	 * @version 2.5.3
	 * @todo    if ( 'yes' === get_option( 'wcj_shipping_hide_if_free_available_local_delivery' ) ) { unset( $rates['local_delivery'] ); }
	 */
	function hide_shipping_when_free_is_available( $rates, $package ) {
		$free_shipping_rates = array();
		$is_free_shipping_available = false;
		foreach ( $rates as $rate_key => $rate ) {
			if ( false !== strpos( $rate_key, 'free_shipping' ) ) {
				$is_free_shipping_available = true;
				$free_shipping_rates[ $rate_key ] = $rate;
			}
		}
		return ( $is_free_shipping_available ) ? $free_shipping_rates : $rates;
	}

	/**
	 * add_hide_shipping_if_free_available_fields.
	 *
	 * @version 2.5.3
	 */
	function add_hide_shipping_if_free_available_fields( $settings ) {
		$updated_settings = array();
		foreach ( $settings as $section ) {
			$updated_settings[] = $section;
			if ( isset( $section['id'] ) && 'woocommerce_ship_to_destination' === $section['id'] ) {
				/* $updated_settings[] = array(
					'title'    => __( 'Booster: Hide shipping', 'woocommerce-jetpack' ),
					'desc'     => __( 'Hide local delivery when free is available', 'woocommerce-jetpack' ),
					'desc_tip' => __( '', 'woocommerce-jetpack' ),
					'id'       => 'wcj_shipping_hide_if_free_available_local_delivery',
					'default'  => 'no',
					'type'     => 'checkbox',
					'checkboxgroup' => 'start',
				); */
				$updated_settings[] = array(
					'title'    => __( 'Booster: Hide shipping', 'woocommerce-jetpack' ),
					'desc'     => __( 'Hide all when free is available', 'woocommerce-jetpack' ),
					'id'       => 'wcj_shipping_hide_if_free_available_all',
					'default'  => 'no',
					'type'     => 'checkbox',
					/* 'checkboxgroup' => 'end', */
				);
			}
		}
		return $updated_settings;
	}

	/**
	 * get_settings.
	 *
	 * @version 2.5.3
	 */
	function get_settings() {
		$wocommerce_shipping_settings_url = admin_url( 'admin.php?page=wc-settings&tab=shipping' );
		$wocommerce_shipping_settings_url = '<a href="' . $wocommerce_shipping_settings_url . '">' . __( 'WooCommerce > Settings > Shipping', 'woocommerce-jetpack' ) . '</a>';
		$settings = array(
			array(
				'title'    => __( 'Custom Shipping', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_shipping_custom_shipping_options',
				'desc'     => __( 'This section lets you set number of custom shipping methods to add.', 'woocommerce-jetpack' )
					. ' ' . sprintf( __( 'After setting the number, visit %s to set each method options.', 'woocommerce-jetpack' ), $wocommerce_shipping_settings_url ),
			),
			array(
				'title'    => __( 'Custom Shipping Methods Number', 'woocommerce-jetpack' ),
				'id'       => 'wcj_shipping_custom_shipping_total_number',
				'default'  => 1,
				'type'     => 'custom_number',
				'desc'     => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				'custom_attributes' => apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),
				'custom_attributes' => array_merge(
					is_array( apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ) ) ? apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ) : array(),
					array( 'step' => '1', 'min' => '0', 'max' => '10', )
				),
			),
		);
		$total_number = apply_filters( 'wcj_get_option_filter', 1, get_option( 'wcj_shipping_custom_shipping_total_number', 1 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			$settings [] = array(
				'title'    => __( 'Admin Title Custom Shipping', 'woocommerce-jetpack' ) . ' #' . $i,
				'id'       => 'wcj_shipping_custom_shipping_admin_title_' . $i,
				'default'  => __( 'Custom', 'woocommerce-jetpack' ) . ' #' . $i,
				'type'     => 'text',
			);
		}
		$settings = array_merge( $settings, array(
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_shipping_custom_shipping_options',
			),
		) );
		$settings = array_merge( $settings, array(
			array(
				'title'    => __( 'Hide if Free is Available', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'desc'     => __( 'This section lets you hide other shipping options when free shipping is available on shop frontend.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_shipping_hide_if_free_available_options',
			),
			/* array(
				'title'    => __( 'Hide shipping', 'woocommerce-jetpack' ),
				'desc'     => __( 'Hide local delivery when free is available', 'woocommerce-jetpack' ),
				'id'       => 'wcj_shipping_hide_if_free_available_local_delivery',
				'default'  => 'no',
				'type'     => 'checkbox',
				'checkboxgroup' => 'start',
			), */
			array(
				'title'    => __( 'Hide shipping', 'woocommerce-jetpack' ),
				'desc'     => __( 'Hide all when free is available', 'woocommerce-jetpack' ),
				'id'       => 'wcj_shipping_hide_if_free_available_all',
				'default'  => 'no',
				'type'     => 'checkbox',
				/* 'checkboxgroup' => 'end', */
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_shipping_hide_if_free_available_options',
			),
			array(
				'title'    => __( 'Left to Free Shipping Info Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'desc'     => __( 'This section lets you enable info on cart, mini cart and checkout pages.', 'woocommerce-jetpack' )
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
				'desc'     => __( 'Position Order (Priority)', 'woocommerce-jetpack' ),
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
				'desc'     => __( 'Position Order (Priority)', 'woocommerce-jetpack' ),
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
				'desc'     => __( 'Position Order (Priority)', 'woocommerce-jetpack' ),
				'id'       => 'wcj_shipping_left_to_free_info_priority_checkout',
				'default'  => 10,
				'type'     => 'number',
				'css'      => 'width:250px;',
			),
			array(
				'title'    => __( 'Message on Free Shipping Reached', 'woocommerce-jetpack' ),
				'desc_tip' => __( 'You can set it empty', 'woocommerce-jetpack' ),
				'id'       => 'wcj_shipping_left_to_free_info_content_reached',
				'default'  => __( 'You have Free delivery', 'woocommerce-jetpack' ),
				'type'     => 'textarea',
				'css'      => 'width:30%;min-width:300px;height:100px;',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_shipping_left_to_free_info_options',
			),
		) );
		return $this->add_standard_settings( $settings );
	}
}

endif;

return new WCJ_Shipping();
