<?php
/**
 * Booster for WooCommerce - Module - Product Listings
 *
 * @version 2.9.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Product_Listings' ) ) :

class WCJ_Product_Listings extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.9.0
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

			// Admin list - custom columns
			if ( 'yes' === get_option( 'wcj_products_admin_list_custom_columns_enabled', 'no' ) ) {
				add_filter( 'manage_edit-product_columns',        array( $this, 'add_product_columns' ),   PHP_INT_MAX );
				add_action( 'manage_product_posts_custom_column', array( $this, 'render_product_column' ), PHP_INT_MAX );
			}

			// Admin list - columns order
			if ( 'yes' === get_option( 'wcj_products_admin_list_columns_order_enabled', 'no' ) ) {
				add_filter( 'manage_edit-product_columns', array( $this, 'rearange_product_columns' ), PHP_INT_MAX );
			}
		}
	}

	/**
	 * rearange_product_columns.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 */
	function rearange_product_columns( $columns ) {
		$reordered_columns = get_option( 'wcj_products_admin_list_columns_order', $this->get_products_default_columns_in_order() );
		$reordered_columns = explode( PHP_EOL, $reordered_columns );
		$reordered_columns_result = array();
		if ( ! empty( $reordered_columns ) ) {
			foreach ( $reordered_columns as $column_id ) {
				$column_id = str_replace( "\n", '', $column_id );
				$column_id = str_replace( "\r", '', $column_id );
				if ( '' != $column_id && isset( $columns[ $column_id ] ) ) {
					$reordered_columns_result[ $column_id ] = $columns[ $column_id ];
					unset( $columns[ $column_id ] );
				}
			}
		}
		return array_merge( $reordered_columns_result, $columns );
	}

	/**
	 * get_products_default_columns_in_order.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 */
	function get_products_default_columns_in_order() {
		$columns = array(
			'cb',
			'thumb',
			'name',
			'sku',
			'is_in_stock',
			'price',
			'product_cat',
			'product_tag',
			'featured',
			'product_type',
			'date',
		);
		return implode( PHP_EOL, $columns );
	}

	/**
	 * add_product_columns.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 */
	function add_product_columns( $columns ) {
		$total_number = apply_filters( 'booster_get_option', 1, get_option( 'wcj_products_admin_list_custom_columns_total_number', 1 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			if ( 'yes' === get_option( 'wcj_products_admin_list_custom_columns_enabled_' . $i, 'no' ) ) {
				$columns[ 'wcj_products_custom_column_' . $i ] = get_option( 'wcj_products_admin_list_custom_columns_label_' . $i, '' );
			}
		}
		return $columns;
	}

	/**
	 * render_product_column.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 */
	function render_product_column( $column ) {
		$total_number = apply_filters( 'booster_get_option', 1, get_option( 'wcj_products_admin_list_custom_columns_total_number', 1 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			if ( 'yes' === get_option( 'wcj_products_admin_list_custom_columns_enabled_' . $i, 'no' ) ) {
				if ( 'wcj_products_custom_column_' . $i === $column ) {
					echo do_shortcode( get_option( 'wcj_products_admin_list_custom_columns_value_' . $i, '' ) );
				}
			}
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
