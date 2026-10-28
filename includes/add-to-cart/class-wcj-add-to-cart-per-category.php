<?php
/**
 * Booster for WooCommerce Add to Cart per Category
 *
 * @version 2.2.6
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Add_To_Cart_Per_Category' ) ) :
	/**
	 * WCJ_Add_To_Cart_Per_Category.
	 */
	class WCJ_Add_To_Cart_Per_Category {

		/**
		 * Constructor.
		 */
		public function __construct() {
			if ( 'yes' === wcj_get_option( 'wcj_add_to_cart_per_category_enabled' ) ) {
				add_filter( 'woocommerce_product_single_add_to_cart_text', array( $this, 'change_add_to_cart_button_text_single' ), PHP_INT_MAX );
				add_filter( 'woocommerce_product_add_to_cart_text', array( $this, 'change_add_to_cart_button_text_archive' ), PHP_INT_MAX );
			}
		}

		/**
		 * Change_add_to_cart_button_text_single.
		 *
		 * @param string $add_to_cart_text Add to cart button text change.
		 */
		public function change_add_to_cart_button_text_single( $add_to_cart_text ) {
			return $this->change_add_to_cart_button_text( $add_to_cart_text, 'single' );
		}

		/**
		 * Change_add_to_cart_button_text_archive.
		 *
		 * @param string $add_to_cart_text Add to cart button text change.
		 */
		public function change_add_to_cart_button_text_archive( $add_to_cart_text ) {
			return $this->change_add_to_cart_button_text( $add_to_cart_text, 'archive' );
		}

		/**
		 * Change_add_to_cart_button_text.
		 *
		 * @param string $add_to_cart_text Add to cart button text change.
		 * @param string $single_or_archive Get single or archive product.
		 *
		 * @version 2.2.6
		 */
		public function change_add_to_cart_button_text( $add_to_cart_text, $single_or_archive ) {
			$product_categories = get_the_terms( get_the_ID(), 'product_cat' );
			if ( empty( $product_categories ) ) {
				return $add_to_cart_text;
			}
			$category_total_groups_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_add_to_cart_per_category_total_groups_number', 1 ) );
			for ( $i = 1; $i <= $category_total_groups_number; $i++ ) {
				if ( 'yes' !== wcj_get_option( 'wcj_add_to_cart_per_category_enabled_group_' . $i ) ) {
					continue;
				}
				$categories = wcj_get_option( 'wcj_add_to_cart_per_category_ids_group_' . $i );
				if ( empty( $categories ) ) {
					continue;
				}
				foreach ( $product_categories as $product_category ) {
					foreach ( $categories as $category ) {
						settype( $product_category->term_id, 'string' );

						if ( $product_category->term_id === $category ) {
							return wcj_get_option( 'wcj_add_to_cart_per_category_text_' . $single_or_archive . '_group_' . $i, $add_to_cart_text );
						}
					}
				}
			}
			return $add_to_cart_text;
		}
	}

endif;

return new WCJ_Add_To_Cart_Per_Category();
