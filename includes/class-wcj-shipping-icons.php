<?php
/**
 * Booster for WooCommerce - Module - Shipping Icons
 *
 * @version 3.3.1
 * @since   3.3.1
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Shipping_Icons' ) ) :

class WCJ_Shipping_Icons extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.3.1
	 * @since   3.3.1
	 */
	function __construct() {

		$this->id         = 'shipping_icons';
		$this->short_desc = __( 'Shipping Icons', 'woocommerce-jetpack' );
		$this->desc       = __( 'Add icons to shipping methods on frontend.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-shipping-icons';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_filter( 'woocommerce_cart_shipping_method_full_label', array( $this, 'shipping_icon' ), PHP_INT_MAX, 2 );
		}
	}

	/**
	 * shipping_icon.
	 *
	 * @version 2.6.0
	 * @since   2.5.6
	 */
	function shipping_icon( $label, $method ) {
		$shipping_icons_visibility = apply_filters( 'booster_option', 'both', get_option( 'wcj_shipping_icons_visibility', 'both' ) );
		if ( 'checkout_only' === $shipping_icons_visibility && is_cart() ) {
			return $label;
		}
		if ( 'cart_only' === $shipping_icons_visibility && is_checkout() ) {
			return $label;
		}
		if ( '' != ( $icon_url = get_option( 'wcj_shipping_icon_' . $method->method_id, '' ) ) ) {
			$style_html = ( '' != ( $style = get_option( 'wcj_shipping_icons_style', 'display:inline;' ) ) ) ?  'style="' . $style . '" ' : '';
			$img = '<img ' . $style_html . 'class="wcj_shipping_icon" id="wcj_shipping_icon_' . $method->method_id . '" src="' . $icon_url . '">';
			$label = ( 'before' === get_option( 'wcj_shipping_icons_position', 'before' ) ) ? $img . ' ' . $label : $label . ' ' . $img;
		}
		return $label;
	}

}

endif;

return new WCJ_Shipping_Icons();
