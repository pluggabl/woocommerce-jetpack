<?php
/**
 * Booster for WooCommerce - Module - Gateways Min/Max Amounts
 *
 * @version 5.2.0
 * @since   2.4.1
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Payment_Gateways_Min_Max' ) ) :

class WCJ_Payment_Gateways_Min_Max extends WCJ_Module {

	/**
	 * @version 4.7.0
	 *
	 * @var array
	 */
	private $notices = array();

	/**
	 * Constructor.
	 *
	 * @version 5.2.0
	 * @since   2.4.1
	 */
	function __construct() {

		$this->id         = 'payment_gateways_min_max';
		$this->short_desc = __( 'Gateways Min/Max Amounts', 'woocommerce-jetpack' );
		$this->desc       = __( 'Add min/max amounts for payment gateways to show up (Only Direct bank transfer allowed in free version).', 'woocommerce-jetpack' );
		$this->desc_pro   = __( 'Add min/max amounts for payment gateways to show up.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-payment-gateways-min-max-amounts';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_filter( 'woocommerce_available_payment_gateways', array( $this, 'available_payment_gateways' ), PHP_INT_MAX, 1 );
			add_filter( 'woocommerce_before_checkout_form', array( $this, 'add_notices' ), 1 );
		}
	}

	/**
	 * add_notices.
	 *
	 * @version 4.7.0
	 * @since   4.7.0
	 */
	function add_notices() {
		if ( ! function_exists( 'WC' ) ) {
			return;
		}
		WC()->payment_gateways->get_available_payment_gateways();
		$notices = $this->notices;
		if ( function_exists( 'is_checkout' ) && is_checkout() && 'yes' === wcj_get_option( 'wcj_payment_gateways_min_max_notices_enable', 'yes' ) && ! empty( $notices ) ) {
			$notice_type = wcj_get_option( 'wcj_payment_gateways_min_max_notices_type', 'notice' );
			foreach ( $notices as $notice ) {
				if ( ! wc_has_notice( $notice, $notice_type ) ) {
					wc_add_notice( $notice, $notice_type );
				}
			}
		}
	}

	/**
	 * remove_payment_gateways.
	 *
	 * @version 4.7.0
	 * @since   4.7.0
	 *
	 * @param $_available_gateways
	 *
	 * @return array
	 */
	function remove_payment_gateways( $_available_gateways ) {
		if ( ! function_exists( 'WC' ) || ! isset( WC()->cart ) ) {
			return array(
				'gateways' => $_available_gateways,
				'notices'  => null,
			);
		}
		$total_in_cart = WC()->cart->cart_contents_total;
		if ( 'no' === wcj_get_option( 'wcj_payment_gateways_min_max_exclude_shipping', 'no' ) ) {
			$total_in_cart += WC()->cart->shipping_total;
		}
		$notices              = array();
		$notices_template_min = wcj_get_option( 'wcj_payment_gateways_min_max_notices_template_min', __( 'Minimum amount for %gateway_title% is %min_amount%', 'woocommerce-jetpack' ) );
		$notices_template_max = wcj_get_option( 'wcj_payment_gateways_min_max_notices_template_max', __( 'Maximum amount for %gateway_title% is %max_amount%', 'woocommerce-jetpack' ) );
		foreach ( $_available_gateways as $key => $gateway ) {
			$min = wcj_get_option( 'wcj_payment_gateways_min_' . $key, 0 );
			$max = wcj_get_option( 'wcj_payment_gateways_max_' . $key, 0 );

			// Compatibility with other modules
			if ( 'yes' === wcj_get_option( 'wcj_payment_gateways_min_max_comp_mc', 'no' ) ) {
				if ( wcj_is_module_enabled( 'multicurrency' ) ) {
					$min = WCJ()->modules['multicurrency']->change_price( $min, null );
					$max = WCJ()->modules['multicurrency']->change_price( $max, null );
				}
			}

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
		return array(
			'gateways' => $_available_gateways,
			'notices'  => $notices,
		);
	}

	/**
	 * available_payment_gateways.
	 *
	 * @version 4.7.0
	 * @since   2.4.1
	 * @todo    (maybe) `wc_clear_notices()`
	 */
	function available_payment_gateways( $_available_gateways ) {
		$remove_response     = $this->remove_payment_gateways( $_available_gateways );
		$_available_gateways = $remove_response['gateways'];
		$this->notices = $remove_response['notices'];
		return $_available_gateways;
	}

}

endif;

return new WCJ_Payment_Gateways_Min_Max();
