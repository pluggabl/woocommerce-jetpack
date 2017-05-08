<?php
/**
 * Booster for WooCommerce - Module - Checkout Customization
 *
 * @version 2.8.0
 * @since   2.7.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Checkout_Customization' ) ) :

class WCJ_Checkout_Customization extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.8.0
	 * @since   2.7.0
	 */
	function __construct() {

		$this->id         = 'checkout_customization';
		$this->short_desc = __( 'Checkout Customization', 'woocommerce-jetpack' );
		$this->desc       = __( 'Customize WooCommerce checkout - hide "Order Again" button etc.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-checkout-customization';
		parent::__construct();

		if ( $this->is_enabled() ) {
			// "Create an account?" Checkbox
			if ( 'default' != ( $create_account_default = get_option( 'wcj_checkout_create_account_default_checked', 'default' ) ) ) {
				if ( 'checked' === $create_account_default ) {
					add_filter( 'woocommerce_create_account_default_checked', '__return_true' );
				} elseif ( 'not_checked' === $create_account_default ) {
					add_filter( 'woocommerce_create_account_default_checked', '__return_false' );
				}
			}
			// Hide "Order Again" button
			if ( 'yes' === get_option( 'wcj_checkout_hide_order_again', 'no' ) ) {
				add_action( 'init', array( $this, 'checkout_hide_order_again' ), PHP_INT_MAX );
			}
		}
	}

	/**
	 * checkout_hide_order_again.
	 *
	 * @version 2.6.0
	 * @since   2.6.0
	 */
	function checkout_hide_order_again() {
		remove_action( 'woocommerce_order_details_after_order_table', 'woocommerce_order_again_button' );
	}

}

endif;

return new WCJ_Checkout_Customization();
