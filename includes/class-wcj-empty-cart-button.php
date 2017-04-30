<?php
/**
 * WooCommerce Jetpack Empty Cart Button
 *
 * The WooCommerce Jetpack Empty Cart Button class.
 *
 * @version 2.7.2
 * @since   2.2.1
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Empty_Cart_Button' ) ) :

class WCJ_Empty_Cart_Button extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.7.2
	 * @todo    move shortcode to "General shortcodes"
	 */
	function __construct() {

		$this->id         = 'empty_cart';
		$this->short_desc = __( 'Empty Cart Button', 'woocommerce-jetpack' );
		$this->desc       = __( 'Add (and customize) "Empty Cart" button to WooCommerce cart and checkout pages.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-empty-cart-button/';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_action( 'init', array( $this, 'maybe_empty_cart' ) );
			if ( 'disable' != ( $empty_cart_cart_position = apply_filters( 'booster_get_option', 'woocommerce_after_cart',
				get_option( 'wcj_empty_cart_position', 'woocommerce_after_cart' ) ) )
			) {
				add_action( $empty_cart_cart_position, array( $this, 'add_empty_cart_link' ) );
			}
			if ( 'disable' != ( $empty_cart_checkout_position = get_option( 'wcj_empty_cart_checkout_position', 'disable' ) ) ) {
				add_action( $empty_cart_checkout_position, array( $this, 'add_empty_cart_link' ) );
			}
			add_shortcode( 'wcj_empty_cart_button', array( $this, 'get_empty_cart_link' ) );
		}
	}

	/**
	 * get_empty_cart_link.
	 *
	 * @version 2.7.2
	 * @version 2.7.2
	 */
	function get_empty_cart_link() {
		$confirmation_html = ( 'confirm_with_pop_up_box' == get_option( 'wcj_empty_cart_confirmation', 'no_confirmation' ) ) ?
			' onclick="return confirm(\'' . get_option( 'wcj_empty_cart_confirmation_text', __( 'Are you sure?', 'woocommerce-jetpack' ) ) . '\')"' : '';
		return '<div style="' . get_option( 'wcj_empty_cart_div_style', 'float: right;' ) . '">' .
			'<form action="" method="post"><input type="submit" class="button" name="empty_cart" value="' .
				apply_filters( 'booster_get_option', 'Empty Cart', get_option( 'wcj_empty_cart_text', 'Empty Cart' ) ) . '"' . $confirmation_html . '>' .
			'</form>' .
		'</div>';
	}

	/**
	 * add_empty_cart_link.
	 *
	 * @version 2.7.2
	 */
	function add_empty_cart_link() {
		echo $this->get_empty_cart_link();
	}

	/**
	 * maybe_empty_cart.
	 *
	 * @version 2.7.2
	 */
	function maybe_empty_cart() {
		if ( isset( $_POST['empty_cart'] ) && isset( WC()->cart ) ) {
			WC()->cart->empty_cart();
		}
	}

}

endif;

return new WCJ_Empty_Cart_Button();
