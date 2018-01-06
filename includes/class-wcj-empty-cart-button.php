<?php
/**
 * Booster for WooCommerce - Module - Empty Cart Button
 *
 * @version 2.8.0
 * @since   2.2.1
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Empty_Cart_Button' ) ) :

class WCJ_Empty_Cart_Button extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.8.0
	 * @todo    copy additional options form "Empty Cart Button" plugin
	 */
	function __construct() {

		$this->id         = 'empty_cart';
		$this->short_desc = __( 'Empty Cart Button', 'woocommerce-jetpack' );
		$this->desc       = __( 'Add (and customize) "Empty Cart" button to WooCommerce cart and checkout pages.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-empty-cart-button';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_action( 'init', array( $this, 'maybe_empty_cart' ) );
			if ( 'disable' != ( $empty_cart_cart_position = apply_filters( 'booster_option', 'woocommerce_after_cart',
				get_option( 'wcj_empty_cart_position', 'woocommerce_after_cart' ) ) )
			) {
				add_action( $empty_cart_cart_position, array( $this, 'add_empty_cart_link' ) );
			}
			if ( 'disable' != ( $empty_cart_checkout_position = get_option( 'wcj_empty_cart_checkout_position', 'disable' ) ) ) {
				add_action( $empty_cart_checkout_position, array( $this, 'add_empty_cart_link' ) );
			}
		}
	}

	/**
	 * add_empty_cart_link.
	 *
	 * @version 2.8.0
	 */
	function add_empty_cart_link() {
		echo wcj_empty_cart_button_html();
	}

	/**
	 * maybe_empty_cart.
	 *
	 * @version 2.8.0
	 */
	function maybe_empty_cart() {
		if ( isset( $_POST['wcj_empty_cart'] ) && isset( WC()->cart ) ) {
			WC()->cart->empty_cart();
		}
	}

}

endif;

return new WCJ_Empty_Cart_Button();
