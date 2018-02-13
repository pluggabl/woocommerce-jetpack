<?php
/**
 * Booster for WooCommerce - Module - Shipping Descriptions
 *
 * @version 3.4.1
 * @since   3.4.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Shipping_Descriptions' ) ) :

class WCJ_Shipping_Descriptions extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.4.1
	 * @since   3.4.0
	 */
	function __construct() {

		$this->id         = 'shipping_description';
		$this->short_desc = __( 'Shipping Descriptions', 'woocommerce-jetpack' );
		$this->desc       = __( 'Add descriptions to WooCommerce shipping methods on frontend.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-shipping-descriptions';
		parent::__construct();

		if ( $this->is_enabled() ) {
			add_filter( 'woocommerce_cart_shipping_method_full_label', array( $this, 'shipping_description' ), PHP_INT_MAX, 2 );
		}
	}

	/**
	 * shipping_description.
	 *
	 * @version 2.6.0
	 * @since   2.5.6
	 */
	function shipping_description( $label, $method ) {
		$shipping_descriptions_visibility = apply_filters( 'booster_option', 'both', get_option( 'wcj_shipping_descriptions_visibility', 'both' ) );
		if ( 'checkout_only' === $shipping_descriptions_visibility && is_cart() ) {
			return $label;
		}
		if ( 'cart_only' === $shipping_descriptions_visibility && is_checkout() ) {
			return $label;
		}
		if ( '' != ( $desc = get_option( 'wcj_shipping_description_' . $method->method_id, '' ) ) ) {
			$label .= $desc;
		}
		return $label;
	}

}

endif;

return new WCJ_Shipping_Descriptions();
