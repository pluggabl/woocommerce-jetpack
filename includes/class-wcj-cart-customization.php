<?php
/**
 * Booster for WooCommerce - Module - Cart Customization
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
		$this->desc       = __( 'Customize WooCommerce cart - hide coupon field; item remove link; change empty cart "Return to shop" button text.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-cart-customization';
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
			// Customize "Return to shop" button
			if ( 'yes' === get_option( 'wcj_cart_customization_return_to_shop_button_enabled', 'no' ) ) {
				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			}
		}
	}

	/**
	 * enqueue_scripts.
	 *
	 * @version 2.8.0
	 * @since   2.8.0
	 */
	function enqueue_scripts() {
		wp_enqueue_script(  'wcj-cart-customization', wcj_plugin_url() . '/includes/js/wcj-cart-customization.js', array( 'jquery' ), WCJ()->version, false );
		wp_localize_script( 'wcj-cart-customization', 'wcj_cart_customization', array(
			'return_to_shop_button_text' => get_option( 'wcj_cart_customization_return_to_shop_button_text', __( 'Return to shop', 'woocommerce' ) ),
		) );
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
