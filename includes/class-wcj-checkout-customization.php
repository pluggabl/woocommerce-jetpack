<?php
/**
 * WooCommerce Jetpack Checkout Customization
 *
 * The WooCommerce Jetpack Checkout Customization class.
 *
 * @version 2.7.0
 * @since   2.7.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Checkout_Customization' ) ) :

class WCJ_Checkout_Customization extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.7.0
	 * @since   2.7.0
	 */
	function __construct() {

		$this->id         = 'checkout_customization';
		$this->short_desc = __( 'Checkout Customization', 'woocommerce-jetpack' );
		$this->desc       = __( 'Customize WooCommerce checkout - hide "Order Again" button etc.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-checkout-customization/';
		parent::__construct();

		add_action( 'init', array( $this, 'add_settings_hook' ) );

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
				'id'       => 'wcj_checkout_customization_options',
			),
			array(
				'title'    => __( '"Create an account?" Checkbox', 'woocommerce-jetpack' ),
				'desc_tip' => __( '"Create an account?" checkbox default value', 'woocommerce-jetpack' ),
				'id'       => 'wcj_checkout_create_account_default_checked',
				'default'  => 'default',
				'type'     => 'select',
				'options'  => array(
					'default'     => __( 'WooCommerce default', 'woocommerce-jetpack' ),
					'checked'     => __( 'Checked', 'woocommerce-jetpack' ),
					'not_checked' => __( 'Not checked', 'woocommerce-jetpack' ),
				),
			),
			array(
				'title'    => __( 'Hide "Order Again" Button on "View Order" Page', 'woocommerce-jetpack' ),
				'desc'     => __( 'Hide', 'woocommerce-jetpack' ),
				'id'       => 'wcj_checkout_hide_order_again',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_checkout_customization_options',
			),
		);
	}

}

endif;

return new WCJ_Checkout_Customization();
