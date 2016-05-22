<?php
/**
 * WooCommerce Jetpack Payment Gateways Min Max
 *
 * The WooCommerce Jetpack Payment Gateways Min Max class.
 *
 * @version 2.5.0
 * @since   2.4.1
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Payment_Gateways_Min_Max' ) ) :

class WCJ_Payment_Gateways_Min_Max extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.5.0
	 */
	function __construct() {

		$this->id         = 'payment_gateways_min_max';
		$this->short_desc = __( 'Gateways Min/Max', 'woocommerce-jetpack' );
		$this->desc       = __( 'Add min/max amounts for WooCommerce payment gateways to show up.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-payment-gateways-min-max/';
		parent::__construct();

		add_filter( 'init', array( $this, 'add_hooks' ) );

		if ( $this->is_enabled() ) {
			add_filter( 'woocommerce_available_payment_gateways', array( $this, 'available_payment_gateways' ), PHP_INT_MAX, 1 );
		}
	}

	/**
	 * available_payment_gateways.
	 */
	function available_payment_gateways( $_available_gateways ) {
		foreach ( $_available_gateways as $key => $gateway ) {
			$min = get_option( 'wcj_payment_gateways_min_' . $key, 0 );
			$max = get_option( 'wcj_payment_gateways_max_' . $key, 0 );
			global $woocommerce;
			$total_in_cart = ( 'no' === get_option( 'wcj_payment_gateways_min_max_exclude_shipping', 'no' ) ) ?
				$woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total : $woocommerce->cart->cart_contents_total;
			if ( $min != 0 && $total_in_cart < $min ) {
				unset( $_available_gateways[ $key ] );
				continue;
			}
			if ( $max != 0 && $total_in_cart > $max ) {
				unset( $_available_gateways[ $key ] );
				continue;
			}
		}
		return $_available_gateways;
	}

	/**
	 * add_hooks.
	 */
	function add_hooks() {
		add_filter( 'wcj_payment_gateways_min_max_settings', array( $this, 'add_min_max_settings' ) );
	}

	/**
	 * add_min_max_settings.
	 */
	function add_min_max_settings( $settings ) {
		$settings = array(
			array(
				'title'     => __( 'General Options', 'woocommerce-jetpack' ),
				'type'      => 'title',
				'id'        => 'wcj_payment_gateways_min_max_general_options',
			),
			array(
				'title'     => __( 'Exclude Shipping', 'alg-woocommerce-fees' ),
				'desc'      => __( 'Exclude shipping from total cart sum, when comparing with min/max amounts.', 'alg-woocommerce-fees' ),
				'id'        => 'wcj_payment_gateways_min_max_exclude_shipping',
				'default'   => 'no',
				'type'      => 'checkbox',
			),
			array(
				'type'      => 'sectionend',
				'id'        => 'wcj_payment_gateways_min_max_general_options',
			),
		);
		$settings[] = array(
			'title' => __( 'Payment Gateways', 'woocommerce-jetpack' ),
			'type'  => 'title',
			'desc'  => __( 'Leave zero to disable.', 'woocommerce-jetpack' ),
			'id'    => 'wcj_payment_gateways_min_max_gateways_options',
		);
		$gateways = WC()->payment_gateways->payment_gateways();
		foreach ( $gateways as $key => $gateway ) {
			$default_gateways = array( 'bacs' );
			if ( ! empty( $default_gateways ) && ! in_array( $key, $default_gateways ) ) {
				$custom_attributes = apply_filters( 'get_wc_jetpack_plus_message', '', 'disabled' );
				if ( '' == $custom_attributes ) {
					$custom_attributes = array();
				}
				$desc_tip = apply_filters( 'get_wc_jetpack_plus_message', '', 'desc_no_link' );
			} else {
				$custom_attributes = array();
				$desc_tip = '';
			}
			$settings[] = array(
				'title'     => $gateway->title,
				'desc_tip'  => $desc_tip,
				'desc'      => __( 'Min', 'woocommerce-jetpack' ),
				'id'        => 'wcj_payment_gateways_min_' . $key,
				'default'   => 0,
				'type'      => 'number',
				'custom_attributes' => array_merge( array( 'step' => '0.000001', 'min'  => '0', ), $custom_attributes ),
			);
			$settings[] = array(
				'title'     => '',
				'desc_tip'  => $desc_tip,
				'desc'      => __( 'Max', 'woocommerce-jetpack' ),
				'id'        => 'wcj_payment_gateways_max_' . $key,
				'default'   => 0,
				'type'      => 'number',
				'custom_attributes' => array_merge( array( 'step' => '0.000001', 'min'  => '0', ), $custom_attributes ),
			);
		}
		$settings[] = array(
			'type'  => 'sectionend',
			'id'    => 'wcj_payment_gateways_min_max_gateways_options',
		);
		return $settings;
	}

	/**
	 * get_settings.
	 */
	function get_settings() {
		$settings = array();
		$settings = apply_filters( 'wcj_payment_gateways_min_max_settings', $settings );
		return $this->add_standard_settings( $settings );
	}
}

endif;

return new WCJ_Payment_Gateways_Min_Max();
