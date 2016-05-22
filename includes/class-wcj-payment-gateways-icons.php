<?php
/**
 * WooCommerce Jetpack Payment Gateways Icons
 *
 * The WooCommerce Jetpack Payment Gateways Icons class.
 *
 * @version 2.5.0
 * @since   2.2.2
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Payment_Gateways_Icons' ) ) :

class WCJ_Payment_Gateways_Icons extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.5.0
	 */
	function __construct() {

		$this->id         = 'payment_gateways_icons';
		$this->short_desc = __( 'Gateways Icons', 'woocommerce-jetpack' );
		$this->desc       = __( 'Change or completely remove icons (images) for any (default or custom) WooCommerce payment gateway.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-payment-gateways-icons/';
		parent::__construct();

		add_filter( 'init', array( $this, 'add_hooks' ) );

		if ( $this->is_enabled() ) {
			add_filter( 'woocommerce_gateway_icon', array( $this, 'set_icon' ), PHP_INT_MAX, 2 );

			// compatibility with 2.3.0 or below
			$default_gateways = array( 'cod', 'cheque', 'bacs', 'mijireh_checkout', 'paypal' );
			foreach ( $default_gateways as $key ) {
				$depreciated_option = get_option( 'wcj_payment_gateways_icons_' . 'woocommerce_' . $key . '_icon', '' );
				if ( '' != $depreciated_option ) {
					update_option( 'wcj_gateways_icons_' . $key . '_icon', $depreciated_option );
					delete_option( 'wcj_payment_gateways_icons_' . 'woocommerce_' . $key . '_icon' );
				}
			}
		}
	}

	/**
	 * get_settings.
	 *
	 * @version 2.5.0
	 */
	function get_settings() {
		$settings = array();
		$settings = apply_filters( 'wcj_payment_gateways_icons_settings', $settings );
		return $this->add_standard_settings( $settings );
	}

	/**
	 * add_hooks.
	 *
	 * @version 2.3.1
	 * @since   2.3.1
	 */
	function add_hooks() {
		add_filter( 'wcj_payment_gateways_icons_settings', array( $this, 'add_icons_settings' ) );
	}

	/**
	 * set_icon.
	 *
	 * @version 2.3.1
	 */
	function set_icon( $icon, $key ) {
		$default_gateways = apply_filters( 'wcj_get_option_filter', array( 'cod', 'cheque', 'bacs', 'mijireh_checkout', 'paypal' ), array() );
		if ( ! empty( $default_gateways ) && ! in_array( $key, $default_gateways ) ) {
			return $icon;
		}
		if ( 'yes' === get_option( 'wcj_gateways_icons_' . $key . '_icon_remove', 'no' ) ) {
			return '';
		}
		$custom_icon_url = get_option( 'wcj_gateways_icons_' . $key . '_icon', '' );
		return ( '' == $custom_icon_url ) ? $icon : '<img src="' . $custom_icon_url . '" alt="' . $key . '" />';
	}

	/**
	 * add_icons_settings.
	 *
	 * @version 2.3.1
	 * @since   2.3.1
	 */
	function add_icons_settings( $settings ) {
		$settings = array();
		$settings[] = array(
			'title' => __( 'Options', 'woocommerce-jetpack' ),
			'type'  => 'title',
			'desc'  => __( 'If you want to show an image next to the gateway\'s name on the frontend, enter a URL to an image.', 'woocommerce-jetpack' ),
			'id'    => 'wcj_payment_gateways_icons_options'
		);
		$available_gateways = WC()->payment_gateways->payment_gateways();
		foreach ( $available_gateways as $key => $gateway ) {
			$default_gateways = array( 'cod', 'cheque', 'bacs', 'mijireh_checkout', 'paypal' );
			if ( ! empty( $default_gateways ) && ! in_array( $key, $default_gateways ) ) {
				$custom_attributes = apply_filters( 'get_wc_jetpack_plus_message', '', 'disabled' );
				$desc_tip = apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' );
			} else {
				$custom_attributes = array();
				$desc_tip = '';
			}
			$current_icon_url = get_option( 'wcj_gateways_icons_' . $key . '_icon', '' );
			$desc = ( '' != $current_icon_url ) ? '<img width="16" src="' . $current_icon_url . '" alt="' . $gateway->title . '" title="' . $gateway->title . '" />' : '';
			$settings[] = array(
				'title'     => $gateway->title,
				'desc_tip'  => __( 'Leave blank to set WooCommerce default value', 'woocommerce-jetpack' ),
				'desc'      => ( '' != $desc_tip ) ? $desc_tip : $desc,
				'id'        => 'wcj_gateways_icons_' . $key . '_icon',
				'default'   => '',
				'type'      => 'text',
				'css'       => 'min-width:300px;width:50%;',
				'custom_attributes' => $custom_attributes,
			);
			$settings[] = array(
				'title'     => '',
				'desc_tip'  => $desc_tip,
				'desc'      => __( 'Remove Icon', 'woocommerce-jetpack' ),
				'id'        => 'wcj_gateways_icons_' . $key . '_icon_remove',
				'default'   => 'no',
				'type'      => 'checkbox',
				'custom_attributes' => $custom_attributes,
			);
		}
		$settings[] = array(
			'type' => 'sectionend',
			'id'   => 'wcj_payment_gateways_icons_options'
		);
		return $settings;
	}
}

endif;

return new WCJ_Payment_Gateways_Icons();
