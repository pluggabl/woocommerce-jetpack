<?php
/**
 * Booster for WooCommerce - Module - More Button Labels
 *
 * @version 3.3.0
 * @since   2.2.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_More_Button_Labels' ) ) :

class WCJ_More_Button_Labels extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.3.0
	 */
	function __construct() {

		$this->id         = 'more_button_labels';
		$this->short_desc = __( 'More Button Labels', 'woocommerce-jetpack' );
		$this->desc       = __( 'Set WooCommerce "Place order" button label.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-more-button-labels';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_filter( 'woocommerce_order_button_text', array( $this, 'set_order_button_text' ), PHP_INT_MAX );
			if ( 'yes' === get_option( 'wcj_checkout_place_order_button_override', 'no' ) ) {
				add_action( 'init', array( $this, 'override_order_button_text' ), PHP_INT_MAX );
			}
		}
	}

	/**
	 * override_order_button_text.
	 *
	 * @version 3.3.0
	 * @since   3.3.0
	 */
	function override_order_button_text() {
		if ( function_exists( 'WC' ) && method_exists( WC(), 'payment_gateways' ) && isset( WC()->payment_gateways()->payment_gateways ) ) {
			foreach ( WC()->payment_gateways()->payment_gateways as &$payment_gateway ) {
				$payment_gateway->order_button_text = '';
			}
		}
	}

	/**
	 * set_order_button_text.
	 *
	 * @version 2.8.0
	 */
	function set_order_button_text( $current_text ) {
		return ( '' != ( $new_text = get_option( 'wcj_checkout_place_order_button_text', '' ) ) ) ? $new_text : $current_text;
	}

}

endif;

return new WCJ_More_Button_Labels();
