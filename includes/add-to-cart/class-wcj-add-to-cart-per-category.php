<?php
/**
 * Booster for WooCommerce Add to Cart per Category
 *
 * @version 2.2.6
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Add_To_Cart_Per_Category' ) ) :

class WCJ_Add_To_Cart_Per_Category {

	/**
	 * Constructor.
	 */
	function __construct() {
		if ( 'yes' === get_option( 'wcj_add_to_cart_per_category_enabled' ) ) {
			add_filter( 'woocommerce_product_single_add_to_cart_text', array( $this, 'change_add_to_cart_button_text_single' ),  PHP_INT_MAX );
			add_filter( 'woocommerce_product_add_to_cart_text',        array( $this, 'change_add_to_cart_button_text_archive' ), PHP_INT_MAX );
		}
	}

	/**
	 * change_add_to_cart_button_text_single.
	 */
	function change_add_to_cart_button_text_single( $add_to_cart_text ) {
		return $this->change_add_to_cart_button_text( $add_to_cart_text, 'single' );
	}

	/**
	 * change_add_to_cart_button_text_archive.
	 */
	function change_add_to_cart_button_text_archive( $add_to_cart_text ) {
		return $this->change_add_to_cart_button_text( $add_to_cart_text, 'archive' );
	}

	/**
	 * change_add_to_cart_button_text.
	 *
	 * @version 2.2.6
	 */
	function change_add_to_cart_button_text( $add_to_cart_text, $single_or_archive ) {
		$product_categories = get_the_terms( get_the_ID(), 'product_cat' );
		if ( empty( $product_categories ) ) return $add_to_cart_text;
		for ( $i = 1; $i <= apply_filters( 'booster_option', 1, get_option( 'wcj_add_to_cart_per_category_total_groups_number', 1 ) ); $i++ ) {
			if ( 'yes' !== get_option( 'wcj_add_to_cart_per_category_enabled_group_' . $i ) ) continue;
			$categories = get_option( 'wcj_add_to_cart_per_category_ids_group_' . $i );
			if ( empty(  $categories ) ) continue;
			foreach ( $product_categories as $product_category ) {
				foreach ( $categories as $category ) {
					if ( $product_category->term_id == $category ) {
						return get_option( 'wcj_add_to_cart_per_category_text_' . $single_or_archive . '_group_' . $i, $add_to_cart_text );
					}
				}
			}
		}
		return $add_to_cart_text;
	}
}

endif;

return new WCJ_Add_To_Cart_Per_Category();
