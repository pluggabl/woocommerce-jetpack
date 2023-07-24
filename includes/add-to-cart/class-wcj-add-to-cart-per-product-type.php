<?php
/**
 * Booster for WooCommerce - Add to Cart Button Labels - Per Product Type
 *
 * @version 7.0.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Add_To_Cart_Per_Product_Type' ) ) :
		/**
		 * WCJ_Add_To_Cart_Per_Product_Type.
		 *
		 * @version 3.4.0
		 */
	class WCJ_Add_To_Cart_Per_Product_Type {

		/**
		 * Constructor.
		 *
		 * @version 3.4.0
		 */
		public function __construct() {
			if ( 'yes' === wcj_get_option( 'wcj_add_to_cart_text_enabled', 'no' ) ) {
				add_filter( 'woocommerce_product_single_add_to_cart_text', array( $this, 'custom_add_to_cart_button_text' ), 100 );
				add_filter( 'woocommerce_product_add_to_cart_text', array( $this, 'custom_add_to_cart_button_text' ), 100 );
			}
		}

		/**
		 * Custom_add_to_cart_button_text.
		 *
		 * @version 7.0.0
		 * @todo    (maybe) add checkbox options to enable/disable custom labels for each product type (or even for each label)
		 * @param string $add_to_cart_text Add to cart button text change.
		 */
		public function custom_add_to_cart_button_text( $add_to_cart_text ) {

			global $woocommerce, $product;
			$product = is_string( $product ) ? wc_get_product( get_the_ID() ) : $product;

			if ( ! $product || is_string( $product ) ) {
				return $add_to_cart_text;
			}

			$product_type = ( WCJ_IS_WC_VERSION_BELOW_3 ? $product->product_type : $product->get_type() );
			if ( ! in_array( $product_type, array( 'external', 'grouped', 'simple', 'variable' ), true ) ) {
				$product_type = 'other';
			}

			$single_or_archive = ( 'woocommerce_product_single_add_to_cart_text' === current_filter() ? 'single' : 'archives' );

			// Already in cart.
			$text_already_in_cart = wcj_get_option( 'wcj_add_to_cart_text_on_' . $single_or_archive . '_in_cart_' . $product_type, '' );
			if ( '' !== ( $text_already_in_cart ) && isset( $woocommerce->cart ) ) {
				foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {
					$_product = $values['data'];
					if ( get_the_ID() === wcj_get_product_id_or_variation_parent_id( $_product ) ) {
						return do_shortcode( $text_already_in_cart );
					}
				}
			}

			// Not in stock.
			$text_on_not_in_stock = wcj_get_option( 'wcj_add_to_cart_text_on_' . $single_or_archive . '_not_in_stock_' . $product_type, '' );
			if ( '' !== ( $text_on_not_in_stock ) && ! $product->is_in_stock() ) {
				return do_shortcode( $text_on_not_in_stock );
			}

			// On sale.
			$text_on_sale = wcj_get_option( 'wcj_add_to_cart_text_on_' . $single_or_archive . '_sale_' . $product_type, '' );
			if ( '' !== ( $text_on_sale ) && $product->is_on_sale() ) {
				return do_shortcode( $text_on_sale );
			}

			// Empty price.
			$text_on_no_price = wcj_get_option( 'wcj_add_to_cart_text_on_' . $single_or_archive . '_no_price_' . $product_type, '' );
			if ( '' !== ( $text_on_no_price ) && '' === $product->get_price() ) {
				return do_shortcode( $text_on_no_price );
			}

			// Free (i.e. zero price).
			$text_on_zero_price = wcj_get_option( 'wcj_add_to_cart_text_on_' . $single_or_archive . '_zero_price_' . $product_type, '' );
			if ( '' !== ( $text_on_zero_price ) && ( 0 === $product->get_price() || '0' === $product->get_price() ) ) {
				return do_shortcode( $text_on_zero_price );
			}

			// General.
			$text_general = wcj_get_option( 'wcj_add_to_cart_text_on_' . $single_or_archive . '_' . $product_type, '' );
			if ( '' !== ( $text_general ) ) {
				return do_shortcode( $text_general );
			}

			// Default.
			return $add_to_cart_text;
		}
	}

endif;

return new WCJ_Add_To_Cart_Per_Product_Type();
