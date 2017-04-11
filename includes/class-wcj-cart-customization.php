<?php
/**
 * WooCommerce Jetpack Cart Customization
 *
 * The WooCommerce Jetpack Cart Customization class.
 *
 * @version 2.7.0
 * @since   2.7.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Cart_Customization' ) ) :

class WCJ_Cart_Customization extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.7.0
	 * @since   2.7.0
	 */
	function __construct() {

		$this->id         = 'cart_customization';
		$this->short_desc = __( 'Cart Customization', 'woocommerce-jetpack' );
		$this->desc       = __( 'Customize WooCommerce cart - hide coupon field; item remove link.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-cart-customization/';
		parent::__construct();

		add_action( 'init', array( $this, 'add_settings_hook' ) );

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

	/*
	 * add_settings.
	 *
	 * @version 2.7.0
	 * @since   2.7.0
	 */
	function add_settings() {
		return array(
			array(
				'title'    => __( 'Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_cart_customization_options',
			),
			array(
				'title'    => __( 'Hide Coupon on Cart Page', 'woocommerce-jetpack' ),
				'desc'     => __( 'Hide', 'woocommerce-jetpack' ),
				'id'       => 'wcj_cart_hide_coupon',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Hide Item Remove Link', 'woocommerce-jetpack' ),
				'desc'     => __( 'Hide', 'woocommerce-jetpack' ),
				'id'       => 'wcj_cart_hide_item_remove_link',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_cart_customization_options',
			),
		);
	}

}

endif;

return new WCJ_Cart_Customization();
