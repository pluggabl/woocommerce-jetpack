<?php
/**
 * Booster for WooCommerce - Module - Checkout Customization
 *
 * @version 2.8.3
 * @since   2.7.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Checkout_Customization' ) ) :

class WCJ_Checkout_Customization extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.8.3
	 * @since   2.7.0
	 * @todo    add more (i.e. all) fields to "Disable Billing Email Fields on Checkout for Logged Users" (not only billing email)
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
			// Disable Billing Email Fields on Checkout for Logged Users
			if ( 'yes' === get_option( 'wcj_checkout_customization_disable_email_for_logged_enabled', 'no' ) ) {
				add_filter( 'woocommerce_checkout_fields' ,      array( $this, 'maybe_disable_email_field' ), PHP_INT_MAX );
				add_filter( 'woocommerce_form_field_' . 'email', array( $this, 'maybe_add_description' ),     PHP_INT_MAX, 4 );
			}
		}
	}

	/**
	 * maybe_add_description.
	 *
	 * @version 2.8.3
	 * @since   2.8.3
	 */
	function maybe_add_description( $field, $key, $args, $value ) {
		if ( is_user_logged_in() ) {
			if ( 'billing_email' == $key ) {
				$desc = get_option( 'wcj_checkout_customization_disable_email_for_logged_message',
					'<em>' . __( 'Email address can be changed on the "My Account" page', 'woocommerce-jetpack' ) . '</em>' );
				$field = str_replace( '__WCJ_TEMPORARY_VALUE_TO_REPLACE__', $desc, $field );
			}
		}
		return $field;
	}

	/**
	 * maybe_disable_email_field.
	 *
	 * @version 2.8.3
	 * @since   2.8.3
	 * @see     woocommerce_form_field
	 */
	function maybe_disable_email_field( $checkout_fields ) {
		if ( is_user_logged_in() ) {
			if ( isset( $checkout_fields['billing']['billing_email'] ) ) {
				if ( ! isset( $checkout_fields['billing']['billing_email']['custom_attributes'] ) ) {
					$checkout_fields['billing']['billing_email']['custom_attributes'] = array();
				}
				$checkout_fields['billing']['billing_email']['custom_attributes'] = array_merge(
					$checkout_fields['billing']['billing_email']['custom_attributes'],
					array( 'readonly' => 'readonly' )
				);
				$checkout_fields['billing']['billing_email']['description'] = '__WCJ_TEMPORARY_VALUE_TO_REPLACE__';
			}
		}
		return $checkout_fields;
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
