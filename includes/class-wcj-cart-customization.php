<?php
/**
 * Booster for WooCommerce - Module - Cart Customization
 *
 * @version 3.1.0
 * @since   2.7.0
 * @author  Pluggabl LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Cart_Customization' ) ) :

class WCJ_Cart_Customization extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.1.0
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
			if ( 'yes' === wcj_get_option( 'wcj_cart_hide_coupon', 'no' ) ) {
				add_filter( 'woocommerce_coupons_enabled', array( $this, 'hide_coupon_field_on_cart' ), PHP_INT_MAX );
			}
			// Hide item remove link
			if ( 'yes' === wcj_get_option( 'wcj_cart_hide_item_remove_link', 'no' ) ) {
				add_filter( 'woocommerce_cart_item_remove_link', '__return_empty_string', PHP_INT_MAX );
			}
			// Customize "Return to shop" button text
			if ( 'yes' === wcj_get_option( 'wcj_cart_customization_return_to_shop_button_enabled', 'no' ) ) {
				if ( 'js' === wcj_get_option( 'wcj_cart_customization_return_to_shop_button_text_method', 'js' ) ) {
					add_action( 'wp_enqueue_scripts',      array( $this, 'enqueue_scripts' ) );
				} else { // 'template'
					add_filter( 'wc_get_template',         array( $this, 'replace_empty_cart_template' ), PHP_INT_MAX, 5 );
					add_filter( 'wcj_return_to_shop_text', array( $this, 'change_empty_cart_button_text' ), PHP_INT_MAX );
				}
			}
			// Customize "Return to shop" button link
			if ( 'yes' === wcj_get_option( 'wcj_cart_customization_return_to_shop_button_link_enabled', 'no' ) ) {
				add_action( 'woocommerce_return_to_shop_redirect', array( $this, 'change_empty_cart_return_to_shop_link' ) );
			}
		}
	}

	/**
	 * change_empty_cart_button_text.
	 *
	 * @version 3.1.0
	 * @since   3.1.0
	 */
	function change_empty_cart_button_text( $text ) {
		return wcj_get_option( 'wcj_cart_customization_return_to_shop_button_text', __( 'Return to shop', 'woocommerce' ) );
	}

	/**
	 * replace_empty_cart_template.
	 *
	 * @version 3.1.0
	 * @since   3.1.0
	 * @todo    [dev] fix folder structure in `/templates`
	 */
	function replace_empty_cart_template( $located, $template_name, $args, $template_path, $default_path ) {
		if ( 'cart/cart-empty.php' == $template_name ) {
			$located = untrailingslashit( realpath( plugin_dir_path( __FILE__ ) . '/..' ) ) . '/includes/templates/cart-empty.php';
		}
		return $located;
	}

	/**
	 * change_empty_cart_return_to_shop_link.
	 *
	 * @version 3.0.1
	 * @since   3.0.1
	 * @todo    [dev] (maybe) check if link is not empty
	 */
	function change_empty_cart_return_to_shop_link( $link ) {
		return ( is_cart() ? wcj_get_option( 'wcj_cart_customization_return_to_shop_button_link', '' ) : $link );
	}

	/**
	 * enqueue_scripts.
	 *
	 * @version 2.8.0
	 * @since   2.8.0
	 * @todo    [dev] maybe check `is_cart()`
	 */
	function enqueue_scripts() {
		wp_enqueue_script(  'wcj-cart-customization', wcj_plugin_url() . '/includes/js/wcj-cart-customization.js', array( 'jquery' ), WCJ()->version, false );
		wp_localize_script( 'wcj-cart-customization', 'wcj_cart_customization', array(
			'return_to_shop_button_text' => wcj_get_option( 'wcj_cart_customization_return_to_shop_button_text', __( 'Return to shop', 'woocommerce' ) ),
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
