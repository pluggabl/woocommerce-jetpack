<?php
/**
 * Booster for WooCommerce - Module - Shipping by Products
 *
 * @version 3.1.4
 * @since   3.1.4
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Shipping_By_Products' ) ) :

class WCJ_Shipping_By_Products extends WCJ_Module_Shipping_By_Condition {

	/**
	 * Constructor.
	 *
	 * @version 3.1.4
	 * @since   3.1.4
	 */
	function __construct() {

		$this->id         = 'shipping_by_products';
		$this->short_desc = __( 'Shipping Methods by Products', 'woocommerce-jetpack' );
		$this->desc       = __( 'Set products, product categories or tags to include/exclude for WooCommerce shipping methods to show up.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-shipping-methods-by-products';

		$this->condition_options = array(
			'products' => array(
				'title' => __( 'Products', 'woocommerce-jetpack' ),
				'desc'  => __( 'Shipping methods by <strong>products</strong>.', 'woocommerce-jetpack' ),
			),
			'product_cats' => array(
				'title' => __( 'Product Categories', 'woocommerce-jetpack' ),
				'desc'  => __( 'Shipping methods by <strong>products categories</strong>.', 'woocommerce-jetpack' ),
			),
			'product_tags' => array(
				'title' => __( 'Product Tags', 'woocommerce-jetpack' ),
				'desc'  => __( 'Shipping methods by <strong>products tags</strong>.', 'woocommerce-jetpack' ),
			),
		);

		parent::__construct();
	}

	/**
	 * check.
	 *
	 * @version 3.1.4
	 * @since   3.1.4
	 */
	function check( $options_id, $products_or_cats_or_tags ) {
		if ( ! isset( WC()->cart ) || WC()->cart->is_empty() ) {
			return true;
		}
		foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
			switch( $options_id ) {
				case 'products':
					if ( in_array( $values['product_id'], $products_or_cats_or_tags ) ) {
						return true;
					}
					break;
				case 'product_cats':
				case 'product_tags':
					$product_terms = get_the_terms( $values['product_id'], ( 'product_cats' === $options_id ? 'product_cat' : 'product_tag' ) );
					if ( empty( $product_terms ) ) {
						continue;
					}
					foreach( $product_terms as $product_term ) {
						if ( in_array( $product_term->term_id, $products_or_cats_or_tags ) ) {
							return true;
						}
					}
					break;
			}
		}
		return false;
	}

	/**
	 * get_condition_options.
	 *
	 * @version 3.1.4
	 * @since   3.1.4
	 */
	function get_condition_options( $options_id ) {
		switch( $options_id ) {
			case 'products':
				return wcj_get_products();
			case 'product_cats':
				return wcj_get_terms( 'product_cat' );
			case 'product_tags':
				return wcj_get_terms( 'product_tag' );
		}
	}

}

endif;

return new WCJ_Shipping_By_Products();
