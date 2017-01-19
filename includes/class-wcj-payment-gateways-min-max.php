<?php
/**
 * WooCommerce Jetpack Payment Gateways Min Max
 *
 * The WooCommerce Jetpack Payment Gateways Min Max class.
 *
 * @version 2.6.0
 * @since   2.4.1
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Payment_Gateways_Min_Max' ) ) :

class WCJ_Payment_Gateways_Min_Max extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.6.0
	 */
	function __construct() {

		$this->id         = 'payment_gateways_min_max';
		$this->short_desc = __( 'Gateways Min/Max Amounts', 'woocommerce-jetpack' );
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
	 *
	 * @version 2.6.0
	 */
	function available_payment_gateways( $_available_gateways ) {
		$notices = array();
		$notices_template_min = get_option( 'wcj_payment_gateways_min_max_notices_template_min', __( 'Minimum amount for %gateway_title% is %min_amount%', 'woocommerce-jetpack') );
		$notices_template_max = get_option( 'wcj_payment_gateways_min_max_notices_template_max', __( 'Maximum amount for %gateway_title% is %max_amount%', 'woocommerce-jetpack') );
		foreach ( $_available_gateways as $key => $gateway ) {
			$min = get_option( 'wcj_payment_gateways_min_' . $key, 0 );
			$max = get_option( 'wcj_payment_gateways_max_' . $key, 0 );
			global $woocommerce;
			$total_in_cart = ( 'no' === get_option( 'wcj_payment_gateways_min_max_exclude_shipping', 'no' ) ) ?
				$woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total : $woocommerce->cart->cart_contents_total;
			if ( $min != 0 && $total_in_cart < $min ) {
				$notices[] = str_replace( array( '%gateway_title%', '%min_amount%' ), array( $gateway->title, wc_price( $min ) ), $notices_template_min );
				unset( $_available_gateways[ $key ] );
				continue;
			}
			if ( $max != 0 && $total_in_cart > $max ) {
				$notices[] = str_replace( array( '%gateway_title%', '%max_amount%' ), array( $gateway->title, wc_price( $max ) ), $notices_template_max );
				unset( $_available_gateways[ $key ] );
				continue;
			}
		}
		if ( 'yes' === get_option( 'wcj_payment_gateways_min_max_notices_enable', 'yes' ) && ! empty( $notices ) ) {
//			wc_clear_notices();
			$notice_type = get_option( 'wcj_payment_gateways_min_max_notices_type', 'notice' );
			foreach ( $notices as $notice ) {
				if ( ! wc_has_notice( $notice, $notice_type ) ) {
					wc_add_notice( $notice, $notice_type );
				}
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
	 *
	 * @version 2.6.0
	 * @todo    checkout notices - add %diff_amount% replaced values (wc_has_notice won't work then, probably will need to use wc_clear_notices)
	 */
	function add_min_max_settings( $settings ) {
		$settings = array(
			array(
				'title'     => __( 'General Options', 'woocommerce-jetpack' ),
				'type'      => 'title',
				'id'        => 'wcj_payment_gateways_min_max_general_options',
			),
			array(
				'title'     => __( 'Exclude Shipping', 'woocommerce-jetpack'),
				'desc'      => __( 'Exclude shipping from total cart sum, when comparing with min/max amounts.', 'woocommerce-jetpack'),
				'id'        => 'wcj_payment_gateways_min_max_exclude_shipping',
				'default'   => 'no',
				'type'      => 'checkbox',
			),
			array(
				'title'     => __( 'Notices on Checkout', 'woocommerce-jetpack'),
				'desc'      => __( 'Enable Notices', 'woocommerce-jetpack'),
				'id'        => 'wcj_payment_gateways_min_max_notices_enable',
				'default'   => 'yes',
				'type'      => 'checkbox',
			),
			array(
				'desc'      => __( 'Notice Template (Minimum Amount)', 'woocommerce-jetpack'),
				'desc_tip'  => __( 'Replaced values: %gateway_title%, %min_amount%.', 'woocommerce-jetpack'),
				'id'        => 'wcj_payment_gateways_min_max_notices_template_min',
				'default'   => __( 'Minimum amount for %gateway_title% is %min_amount%', 'woocommerce-jetpack'),
				'type'      => 'textarea',
				'css'       => 'width:90%;min-width:300px',
			),
			array(
				'desc'      => __( 'Notice Template (Maximum Amount)', 'woocommerce-jetpack'),
				'desc_tip'  => __( 'Replaced values: %gateway_title%, %max_amount%.', 'woocommerce-jetpack'),
				'id'        => 'wcj_payment_gateways_min_max_notices_template_max',
				'default'   => __( 'Maximum amount for %gateway_title% is %max_amount%', 'woocommerce-jetpack'),
				'type'      => 'textarea',
				'css'       => 'width:90%;min-width:300px',
			),
			array(
				'desc'      => __( 'Notice Styling', 'woocommerce-jetpack'),
				'id'        => 'wcj_payment_gateways_min_max_notices_type',
				'default'   => 'notice',
				'type'      => 'select',
				'options'   => array(
					'notice'  => __( 'Notice', 'woocommerce-jetpack'),
					'error'   => __( 'Error', 'woocommerce-jetpack'),
					'success' => __( 'Success', 'woocommerce-jetpack'),
				),
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
				$custom_attributes = apply_filters( 'booster_get_message', '', 'disabled' );
				if ( '' == $custom_attributes ) {
					$custom_attributes = array();
				}
				$desc_tip = apply_filters( 'booster_get_message', '', 'desc_no_link' );
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
