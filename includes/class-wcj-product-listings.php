<?php
/**
 * Booster for WooCommerce - Module - Product Listings
 *
 * @version 2.8.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Product_Listings' ) ) :

class WCJ_Product_Listings extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.8.0
	 */
	function __construct() {
		$this->id         = 'product_listings';
		$this->short_desc = __( 'Product Listings', 'woocommerce-jetpack' );
		$this->desc       = __( 'Change WooCommerce display options for shop and category pages: show/hide categories count, exclude categories, show/hide empty categories.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-product-listings';
		parent::__construct();

		if ( $this->is_enabled() ) {

			// Exclude and Hide Empty
			add_filter( 'woocommerce_product_subcategories_args',       array( $this, 'filter_subcategories' ), 100 );
			add_filter( 'woocommerce_product_subcategories_hide_empty', array( $this, 'filter_subcategories_hide_empty' ), 100 );

			// Hide Count
			if ( 'yes' === get_option( 'wcj_product_listings_hide_cats_count_on_shop' ) || 'yes' === get_option( 'wcj_product_listings_hide_cats_count_on_archive' ) ) {
				add_filter( 'woocommerce_subcategory_count_html', array( $this, 'remove_subcategory_count' ), 100 );
			}

			// Tax Incl./Excl. by product/category
			add_filter( 'option_woocommerce_tax_display_shop', array( $this, 'tax_display' ), PHP_INT_MAX );
		}
	}

	/**
	 * tax_display.
	 *
	 * @version 2.5.5
	 * @since   2.5.5
	 */
	function tax_display( $value ) {
		$product_id = get_the_ID();
		if ( 'product' === get_post_type( $product_id ) ) {
			$products_incl_tax     = get_option( 'wcj_product_listings_display_taxes_products_incl_tax', '' );
			$products_excl_tax     = get_option( 'wcj_product_listings_display_taxes_products_excl_tax', '' );
			$product_cats_incl_tax = get_option( 'wcj_product_listings_display_taxes_product_cats_incl_tax', '' );
			$product_cats_excl_tax = get_option( 'wcj_product_listings_display_taxes_product_cats_excl_tax', '' );
			if ( '' != $products_incl_tax || '' != $products_incl_tax || '' != $products_incl_tax || '' != $products_incl_tax ) {
				// Products
				if ( ! empty( $products_incl_tax ) ) {
					if ( in_array( $product_id, $products_incl_tax ) ) {
						return 'incl';
					}
				}
				if ( ! empty( $products_excl_tax ) ) {
					if ( in_array( $product_id, $products_excl_tax ) ) {
						return 'excl';
					}
				}
				// Categories
				$product_categories = get_the_terms( $product_id, 'product_cat' );
				if ( ! empty( $product_cats_incl_tax ) ) {
					if ( ! empty( $product_categories ) ) {
						foreach ( $product_categories as $product_category ) {
							if ( in_array( $product_category->term_id, $product_cats_incl_tax ) ) {
								return 'incl';
							}
						}
					}
				}
				if ( ! empty( $product_cats_excl_tax ) ) {
					if ( ! empty( $product_categories ) ) {
						foreach ( $product_categories as $product_category ) {
							if ( in_array( $product_category->term_id, $product_cats_excl_tax ) ) {
								return 'excl';
							}
						}
					}
				}
			}
		}
		return $value;
	}

	/**
	 * remove_subcategory_count.
	 */
	function remove_subcategory_count( $count_html ) {
		if (
			( is_shop() && 'yes' === get_option( 'wcj_product_listings_hide_cats_count_on_shop' ) ) ||
			( ! is_shop() && 'yes' === apply_filters( 'booster_get_option', 'wcj', get_option( 'wcj_product_listings_hide_cats_count_on_archive' ) ) )
		) {
			return '';
		}
		return $count_html;
	}

	/**
	 * filter_subcategories.
	 *
	 * @version 2.7.0
	 */
	function filter_subcategories( $args ) {
		$args['exclude'] = ( is_shop() ) ?
			get_option( 'wcj_product_listings_exclude_cats_on_shop',     '' ) :
			get_option( 'wcj_product_listings_exclude_cats_on_archives', '' );
		return $args;
	}

	/**
	 * hide_products_by_disabling_loop.
	 */
	function hide_products_by_disabling_loop() {
		// If we are hiding products disable the loop and pagination
		global $wp_query;
		if (
			is_product_category() &&
			get_option( 'woocommerce_category_archive_display' ) == 'subcategories' &&
			'no' === get_option( 'wcj_product_listings_show_products_if_no_cats_on_archives' )
		) {
			$wp_query->post_count    = 0;
			$wp_query->max_num_pages = 0;
		}
		if (
			is_shop() &&
			get_option( 'woocommerce_shop_page_display' ) == 'subcategories' &&
			'no' === get_option( 'wcj_product_listings_show_products_if_no_cats_on_shop' )
		) {
			$wp_query->post_count    = 0;
			$wp_query->max_num_pages = 0;
		}
	}

	/**
	 * filter_subcategories_hide_empty.
	 *
	 * @version 2.7.0
	 */
	function filter_subcategories_hide_empty() {

		// Not the best solution, but it's the only place I found to put it...
		$this->hide_products_by_disabling_loop();

		$hide_empty = ( is_shop() ) ?
			( 'yes' === get_option( 'wcj_product_listings_hide_empty_cats_on_shop',     'yes' ) ) :
			( 'yes' === get_option( 'wcj_product_listings_hide_empty_cats_on_archives', 'yes' ) );

		return $hide_empty;
	}

}

endif;

return new WCJ_Product_Listings();
