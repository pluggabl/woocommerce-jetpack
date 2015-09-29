<?php
/**
 * WooCommerce Jetpack Payment Gateways Icons
 *
 * The WooCommerce Jetpack Payment Gateways Icons class.
 *
 * @version 2.3.0
 * @since   2.2.2
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Payment_Gateways_Icons' ) ) :

class WCJ_Payment_Gateways_Icons extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.3.0
	 */
	function __construct() {

		$this->id         = 'payment_gateways_icons';
		$this->short_desc = __( 'Gateways Icons', 'woocommerce-jetpack' );
		$this->desc       = __( 'Change icons (images) for all default WooCommerce payment gateways.', 'woocommerce-jetpack' );
		parent::__construct();

		if ( $this->is_enabled() ) {
			$woocommerce_icon_filters = $this->get_woocommerce_icon_filters();
			foreach ( $woocommerce_icon_filters as $filter_name => $filter_title ) {
				add_filter( $filter_name, array( $this, 'set_icon' ) );
			}
		}
	}

	/**
	 * get_woocommerce_icon_filters
	 */
	function get_woocommerce_icon_filters() {
		return array(
			'woocommerce_cod_icon'              => 'COD',
			'woocommerce_cheque_icon'           => 'Cheque',
			'woocommerce_bacs_icon'             => 'BACS',
			'woocommerce_mijireh_checkout_icon' => 'Mijireh Checkout', //depreciated?
			'woocommerce_paypal_icon'           => 'PayPal',
//			'woocommerce_wcj_custom_icon'       => 'WooJetpack Custom',
		);
	}

	/**
	 * set_icon
	 */
	function set_icon( $value ) {
		$icon_url = get_option( 'wcj_payment_gateways_icons_' . current_filter(), '' );
		if ( '' === $icon_url ) {
			return $value;
		}
		return $icon_url;
	}

	/**
	 * get_settings.
	 */
	function get_settings() {

		$settings = array();

		$settings[] = array(
			'title' => __( 'Default WooCommerce Payment Gateways Icons', 'woocommerce-jetpack' ),
			'type'  => 'title',
			'desc'  => __( 'If you want to show an image next to the gateway\'s name on the frontend, enter a URL to an image.', 'woocommerce-jetpack' ),
			'id'    => 'wcj_payment_gateways_icons_options'
		);

		$woocommerce_icon_filters = $this->get_woocommerce_icon_filters();
		foreach ( $woocommerce_icon_filters as $filter_name => $filter_title ) {

			$desc = '';
			$icon_url = apply_filters( $filter_name, '' );
			if ( '' != $icon_url )
				$desc = '<img src="' . $icon_url . '" alt="' . $filter_title . '" title="' . $filter_title . '" />';
//				$desc = __( 'Current Icon: ', 'woocommerce-jetpack' ) . '<img src="' . $icon_url . '" alt="' . $filter_title . '" title="' . $filter_title . '" />';

			$settings[] = array(
					'title'     => $filter_title,
//					'title'     => sprintf( __( 'Icon for %s payment gateway', 'woocommerce-jetpack' ), $filter_title ),
					'desc'      => $desc,
//					'desc_tip'  => $filter_name,
					'id'        => 'wcj_payment_gateways_icons_' . $filter_name,
					'default'   => '',
					'type'      => 'text',
					'css'       => 'min-width:300px;width:50%;',
				);
		}

		$settings[] = array(
			'type' => 'sectionend',
			'id'   => 'wcj_payment_gateways_icons_options'
		);

		return $this->add_enable_module_setting( $settings );
	}
}

endif;

return new WCJ_Payment_Gateways_Icons();
