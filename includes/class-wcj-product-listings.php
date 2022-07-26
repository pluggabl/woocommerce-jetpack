<?php
/**
 * Booster for WooCommerce - Module - Product Listings
 *
 * @version 5.6.2
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Product_Listings' ) ) :
	/**
	 * WCJ_Product_Listings.
	 */
	class WCJ_Product_Listings extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 5.3.6
		 * @todo    more descriptions in settings (i.e. Storefront etc.)
		 * @todo    add deprecated options (which were moved to Storefront)
		 * @todo    add options to enable/disable each section
		 * @todo    add "Exclude Categories Products" to "Category Display Options" also
		 * @todo    add "Exclude Products" and "Exclude Tags Products"
		 */
		public function __construct() {
			$this->id         = 'product_listings';
			$this->short_desc = __( 'Product Listings', 'woocommerce-jetpack' );
			$this->desc       = __( 'Change display options for shop and category pages: show/hide categories count (Hide subcategories count on category pages allowed in Plus), exclude categories, show/hide empty categories.', 'woocommerce-jetpack' );
			$this->desc_pro   = __( 'Change display options for shop and category pages: show/hide categories count, exclude categories, show/hide empty categories.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-product-listings';
			parent::__construct();

			if ( $this->is_enabled() ) {

				// Exclude and Hide Empty.
				add_filter( 'woocommerce_product_subcategories_args', array( $this, 'filter_subcategories' ), 100 );
				add_filter( 'woocommerce_product_subcategories_hide_empty', array( $this, 'filter_subcategories_hide_empty' ), 100 );

				// Hide Count.
				if ( 'yes' === wcj_get_option( 'wcj_product_listings_hide_cats_count_on_shop' ) || 'yes' === wcj_get_option( 'wcj_product_listings_hide_cats_count_on_archive' ) ) {
					add_filter( 'woocommerce_subcategory_count_html', array( $this, 'remove_subcategory_count' ), 100 );
				}

				// Product visibility by price.
				if ( 'yes' === wcj_get_option( 'wcj_product_listings_product_visibility_by_price_enabled', 'no' ) ) {
					add_filter( 'woocommerce_product_is_visible', array( $this, 'product_visibility_by_price' ), PHP_INT_MAX, 2 );
				}

				// Product visibility by category.
				$this->cats_products_to_hide_on_shop = wcj_get_option( 'wcj_product_listings_exclude_cats_products_on_shop', '' );
				if ( ! empty( $this->cats_products_to_hide_on_shop ) ) {
					add_action( 'woocommerce_product_query', array( $this, 'product_visibility_by_category' ), PHP_INT_MAX, 1 );
				}
			}
		}

		/**
		 * Product_visibility_by_category.
		 *
		 * @version 5.3.6
		 * @since   3.5.0
		 * @param  string $query defines the query.
		 */
		public function product_visibility_by_category( $query ) {
			$tax_query   = (array) $query->get( 'tax_query' );
			$tax_query[] = array(
				'taxonomy' => 'product_cat',
				'field'    => 'ID',
				'terms'    => wcj_get_option( 'wcj_product_listings_exclude_cats_products_on_shop', '' ),
				'operator' => 'NOT IN',
			);
			$query->set( 'tax_query', $tax_query );
		}

		/**
		 * Product_visibility_by_price.
		 *
		 * @version 5.6.2
		 * @since   3.2.3
		 * @todo    grouped products
		 * @todo    (maybe) as new "Product Visibility by Price" module
		 * @param string | bool $visible defines the visible.
		 * @param int           $product_id defines the product_id.
		 */
		public function product_visibility_by_price( $visible, $product_id ) {
			$product = wc_get_product( $product_id );
			if ( $product->is_type( 'variable' ) ) {
				$min_price = $product->get_variation_price( 'min' );
				$max_price = $product->get_variation_price( 'max' );
			} else {
				$min_price = $product->get_price();
				$max_price = $product->get_price();
			}
			$min_price_limit = wcj_get_option( 'wcj_product_listings_product_visibility_by_price_min', 0 );
			$max_price_limit = wcj_get_option( 'wcj_product_listings_product_visibility_by_price_max', 0 );

			return ( ( (string) 0 !== $min_price_limit && $min_price < $min_price_limit ) || ( (string) 0 !== $max_price_limit && $max_price > $max_price_limit && '' !== $max_price_limit ) ? false : $visible );
		}

		/**
		 * Remove_subcategory_count.
		 *
		 * @param int | string $count_html defines the count_html.
		 */
		public function remove_subcategory_count( $count_html ) {
			if (
			( is_shop() && 'yes' === wcj_get_option( 'wcj_product_listings_hide_cats_count_on_shop' ) ) ||
			( ! is_shop() && 'yes' === apply_filters( 'booster_option', 'wcj', wcj_get_option( 'wcj_product_listings_hide_cats_count_on_archive' ) ) )
			) {
				return '';
			}
			return $count_html;
		}

		/**
		 * Filter_subcategories.
		 *
		 * @param array $args defines the args.
		 *
		 * @version 2.7.0
		 */
		public function filter_subcategories( $args ) {
			$args['exclude'] = ( is_shop() ) ?
			get_option( 'wcj_product_listings_exclude_cats_on_shop', '' ) :
			get_option( 'wcj_product_listings_exclude_cats_on_archives', '' );
			return $args;
		}

		/**
		 * Hide_products_by_disabling_loop.
		 */
		public function hide_products_by_disabling_loop() {
			// If we are hiding products disable the loop and pagination.
			global $wp_query;
			if (
			is_product_category() &&
			get_option( 'woocommerce_category_archive_display' ) === 'subcategories' &&
			'no' === wcj_get_option( 'wcj_product_listings_show_products_if_no_cats_on_archives' )
			) {
				$wp_query->post_count    = 0;
				$wp_query->max_num_pages = 0;
			}
			if (
			is_shop() &&
			get_option( 'woocommerce_shop_page_display' ) === 'subcategories' &&
			'no' === wcj_get_option( 'wcj_product_listings_show_products_if_no_cats_on_shop' )
			) {
				$wp_query->post_count    = 0;
				$wp_query->max_num_pages = 0;
			}
		}

		/**
		 * Filter_subcategories_hide_empty.
		 *
		 * @version 2.7.0
		 */
		public function filter_subcategories_hide_empty() {

			// Not the best solution, but it's the only place I found to put it...
			$this->hide_products_by_disabling_loop();

			$hide_empty = ( is_shop() ) ?
			( 'yes' === wcj_get_option( 'wcj_product_listings_hide_empty_cats_on_shop', 'yes' ) ) :
			( 'yes' === wcj_get_option( 'wcj_product_listings_hide_empty_cats_on_archives', 'yes' ) );

			return $hide_empty;
		}

	}

endif;

return new WCJ_Product_Listings();
