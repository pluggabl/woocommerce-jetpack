<?php
/**
 * Booster for WooCommerce - Module - Gateways Min/Max Amounts
 *
 * @version 3.2.3
 * @since   2.4.1
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Payment_Gateways_Min_Max' ) ) :

class WCJ_Payment_Gateways_Min_Max extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.8.0
	 * @since   2.4.1
	 */
	function __construct() {

		$this->id         = 'payment_gateways_min_max';
		$this->short_desc = __( 'Gateways Min/Max Amounts', 'woocommerce-jetpack' );
		$this->desc       = __( 'Add min/max amounts for WooCommerce payment gateways to show up.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-payment-gateways-min-max-amounts';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_filter( 'woocommerce_available_payment_gateways', array( $this, 'available_payment_gateways' ), PHP_INT_MAX, 1 );
		}
	}

	/**
	 * available_payment_gateways.
	 *
	 * @version 3.2.3
	 * @since   2.4.1
	 * @todo    (maybe) `wc_clear_notices()`
	 */
	function available_payment_gateways( $_available_gateways ) {
		if ( ! function_exists( 'WC' ) || ! isset( WC()->cart ) ) {
			return $_available_gateways;
		}
		$total_in_cart = WC()->cart->cart_contents_total;
		if ( 'no' === get_option( 'wcj_payment_gateways_min_max_exclude_shipping', 'no' ) ) {
			$total_in_cart += WC()->cart->shipping_total;
		}
		$notices = array();
		$notices_template_min = get_option( 'wcj_payment_gateways_min_max_notices_template_min', __( 'Minimum amount for %gateway_title% is %min_amount%', 'woocommerce-jetpack') );
		$notices_template_max = get_option( 'wcj_payment_gateways_min_max_notices_template_max', __( 'Maximum amount for %gateway_title% is %max_amount%', 'woocommerce-jetpack') );
		foreach ( $_available_gateways as $key => $gateway ) {
			$min = get_option( 'wcj_payment_gateways_min_' . $key, 0 );
			$max = get_option( 'wcj_payment_gateways_max_' . $key, 0 );
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
		if ( function_exists( 'is_checkout' ) && is_checkout() && 'yes' === get_option( 'wcj_payment_gateways_min_max_notices_enable', 'yes' ) && ! empty( $notices ) ) {
			$notice_type = get_option( 'wcj_payment_gateways_min_max_notices_type', 'notice' );
			foreach ( $notices as $notice ) {
				if ( ! wc_has_notice( $notice, $notice_type ) ) {
					wc_add_notice( $notice, $notice_type );
				}
			}
		}
		return $_available_gateways;
	}

}

endif;

return new WCJ_Payment_Gateways_Min_Max();
