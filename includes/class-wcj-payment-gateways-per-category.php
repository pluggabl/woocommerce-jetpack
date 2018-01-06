<?php
/**
 * Booster for WooCommerce - Module - Gateways per Product or Category
 *
 * @version 3.1.0
 * @since   2.2.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Payment_Gateways_Per_Category' ) ) :

class WCJ_Payment_Gateways_Per_Category extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.8.0
	 */
	function __construct() {

		$this->id         = 'payment_gateways_per_category';
		$this->short_desc = __( 'Gateways per Product or Category', 'woocommerce-jetpack' );
		$this->desc       = __( 'Show WooCommerce gateway only if there is selected product or product category in cart.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-payment-gateways-per-product-or-category';
		parent::__construct();

		if ( $this->is_enabled() ) {
//			add_filter( 'woocommerce_payment_gateways_settings',  array( $this, 'add_per_category_settings' ), 100 );
			add_filter( 'woocommerce_available_payment_gateways', array( $this, 'filter_available_payment_gateways_per_category' ), 100 );
		}
	}

	/**
	 * filter_available_payment_gateways_per_category.
	 *
	 * @version 3.1.0
	 */
	function filter_available_payment_gateways_per_category( $available_gateways ) {

		/*
		if ( ! is_checkout() ) {
			return $available_gateways;
		}
		*/

		if ( ! isset( WC()->cart ) ) {
			return $available_gateways;
		}

		foreach ( $available_gateways as $gateway_id => $gateway ) {

			// Including by categories
			$categories_in = get_option( 'wcj_gateways_per_category_' . $gateway_id );
			if ( ! empty( $categories_in ) ) {
				$do_skip = true;
				foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
					$product_categories = get_the_terms( $values['product_id'], 'product_cat' );
					if ( empty( $product_categories ) ) {
						continue; // ... to next product in the cart
					}
					foreach( $product_categories as $product_category ) {
						if ( in_array( $product_category->term_id, $categories_in ) ) {
							// Current gateway is OK, breaking to check next gateway (no need to check other categories of the product)
							$do_skip = false;
							break;
						}
					}
					if ( ! $do_skip ) {
						// Current gateway is OK, breaking to check next gateway (no need to check other products in the cart)
						break;
					}
				}
				if ( $do_skip ) {
					// Skip (i.e. hide/unset) current gateway - no products of needed categories found in the cart
					if ( isset( $available_gateways[ $gateway_id ] ) ) {
						unset( $available_gateways[ $gateway_id ] );
					}
					continue; // ... to next gateway
				}
			}

			// Excluding by categories
			$categories_excl = get_option( 'wcj_gateways_per_category_excl_' . $gateway_id );
			if ( ! empty( $categories_excl ) ) {
				$do_skip = false;
				foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
					$product_categories = get_the_terms( $values['product_id'], 'product_cat' );
					if ( empty( $product_categories ) ) {
						continue; // ... to next product in the cart
					}
					foreach( $product_categories as $product_category ) {
						if ( in_array( $product_category->term_id, $categories_excl ) ) {
							// Skip (i.e. hide/unset) current gateway
							if ( isset( $available_gateways[ $gateway_id ] ) ) {
								unset( $available_gateways[ $gateway_id ] );
							}
							$do_skip = true;
							break;
						}
					}
					if ( $do_skip ) {
						break;
					}
				}
				if ( $do_skip ) {
					continue; // ... to next gateway
				}
			}

			// Including by products
			$products_in = wcj_maybe_convert_string_to_array( apply_filters( 'booster_option', array(), get_option( 'wcj_gateways_per_products_' . $gateway_id ) ) );
			if ( ! empty( $products_in ) ) {
				$do_skip = true;
				foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
					if ( in_array( $values['product_id'], $products_in ) ) {
						// Current gateway is OK
						$do_skip = false;
						break;
					}
				}
				if ( $do_skip ) {
					// Skip (i.e. hide/unset) current gateway
					if ( isset( $available_gateways[ $gateway_id ] ) ) {
						unset( $available_gateways[ $gateway_id ] );
					}
					continue; // ... to next gateway
				}
			}

			// Excluding by products
			$products_excl = wcj_maybe_convert_string_to_array( apply_filters( 'booster_option', array(), get_option( 'wcj_gateways_per_products_excl_' . $gateway_id ) ) );
			if ( ! empty( $products_excl ) ) {
				$do_skip = false;
				foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
					if ( in_array( $values['product_id'], $products_excl ) ) {
						// Skip (i.e. hide/unset) current gateway
						if ( isset( $available_gateways[ $gateway_id ] ) ) {
							unset( $available_gateways[ $gateway_id ] );
						}
						$do_skip = true;
						break;
					}
				}
				if ( $do_skip ) {
					continue; // ... to next gateway
				}
			}

		}

		return $available_gateways;
	}

}

endif;

return new WCJ_Payment_Gateways_Per_Category();
