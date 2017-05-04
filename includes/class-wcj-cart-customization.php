<?php
/**
 * WooCommerce Jetpack Cart Customization
 *
 * The WooCommerce Jetpack Cart Customization class.
 *
 * @version 2.8.0
 * @since   2.7.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Cart_Customization' ) ) :

class WCJ_Cart_Customization extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.8.0
	 * @since   2.7.0
	 */
	function __construct() {

		$this->id         = 'cart_customization';
		$this->short_desc = __( 'Cart Customization', 'woocommerce-jetpack' );
		$this->desc       = __( 'Customize WooCommerce cart - hide coupon field; item remove link.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-cart-customization/';
		parent::__construct();

		if ( $this->is_enabled() ) {
			// Hide coupon
			if ( 'yes' === get_option( 'wcj_cart_hide_coupon', 'no' ) ) {
				add_filter( 'woocommerce_coupons_enabled', array( $this, 'hide_coupon_field_on_cart' ), PHP_INT_MAX );
			}
			// Hide item remove link
			if ( 'yes' === get_option( 'wcj_cart_hide_item_remove_link', 'no' ) ) {
				add_filter( 'woocommerce_cart_item_remove_link', '__return_empty_string', PHP_INT_MAX );
			}
		}
	}

	/**
	 * hide_coupon_field_on_cart.
	 *
	 * @version 2.6.0
	 * @since   2.6.0
	 */
	function hide_coupon_field_on_cart( $enabled ) {
		return ( is_cart() ) ? false : $enabled;
	}

}

endif;

return new WCJ_Cart_Customization();
