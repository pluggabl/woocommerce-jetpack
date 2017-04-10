<?php
/**
 * WooCommerce Jetpack Product Listings
 *
 * The WooCommerce Jetpack Product Listings class.
 *
 * @version 2.6.1
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Product_Listings' ) ) :

class WCJ_Product_Listings extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.6.1
	 */
	function __construct() {
		$this->id         = 'product_listings';
		$this->short_desc = __( 'Product Listings', 'woocommerce-jetpack' );
		$this->desc       = __( 'Change WooCommerce display options for shop and category pages: show/hide categories count, exclude categories, show/hide empty categories.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-product-listings/';
		parent::__construct();

		add_action( 'init', array( $this, 'add_settings_hook' ) );

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
	 * @version 2.6.1
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
	 * @version 2.6.1
	 */
	function filter_subcategories_hide_empty() {

		// Not the best solution, but it's the only place I found to put it...
		$this->hide_products_by_disabling_loop();

		$hide_empty = ( is_shop() ) ?
			( 'yes' === get_option( 'wcj_product_listings_hide_empty_cats_on_shop',     'yes' ) ) :
			( 'yes' === get_option( 'wcj_product_listings_hide_empty_cats_on_archives', 'yes' ) );

		return $hide_empty;
	}

	/*
	 * add_settings.
	 *
	 * @version 2.6.1
	 * @since   2.5.5
	 */
	function add_settings() {

		// Handle deprecated option types
		$options = array(
			'wcj_product_listings_exclude_cats_on_shop',
			'wcj_product_listings_exclude_cats_on_archives',
		);
		foreach ( $options as $option ) {
			$value = get_option( $option, '' );
			if ( ! is_array( $value ) ) {
				$value = explode( ',', str_replace( ' ', '', $value ) );
				update_option( $option, $value );
			}
		}

		// Prepare categories
		$product_cats = array();
		$product_categories = get_terms( 'product_cat', 'orderby=name&hide_empty=0' );
		foreach ( $product_categories as $product_category ) {
			$product_cats[ $product_category->term_id ] = $product_category->name;
		}

		// Prepare products
		$products = wcj_get_products();

		// Settings
		$settings = array(
			array(
				'title'    => __( 'Shop Page Display Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
//				'desc'     => __( 'This will work only when "Shop Page Display" in "WooCommerce > Settings > Products > Product Listings" is set to "Show subcategories" or "Show both".', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_listings_shop_page_options',
			),
			array(
				'title'    => __( 'Categories Count', 'woocommerce-jetpack' ),
				'desc'     => __( 'Hide categories count on shop page', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_listings_hide_cats_count_on_shop',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Exclude Categories', 'woocommerce-jetpack' ),
				'desc_tip' => __(' Excludes one or more categories from the shop page. Leave blank to disable.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_listings_exclude_cats_on_shop',
				'default'  => '',
				'type'     => 'multiselect',
				'class'    => 'chosen_select',
				'css'      => 'width: 450px;',
				'options'  => $product_cats,
			),
			array(
				'title'    => __( 'Hide Empty', 'woocommerce-jetpack' ),
				'desc'     => __( 'Hide empty categories on shop page', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_listings_hide_empty_cats_on_shop',
				'default'  => 'yes',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Show Products', 'woocommerce-jetpack' ),
				'desc'     => __( 'Show products if no categories are displayed on shop page', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_listings_show_products_if_no_cats_on_shop',
				'default'  => 'yes',
				'type'     => 'checkbox',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_product_listings_shop_page_options',
			),
			array(
				'title'    => __( 'Category Display Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
//				'desc'     => __( 'This will work only when "Default Category Display" in "WooCommerce > Settings > Products > Product Listings" is set to "Show subcategories" or "Show both".', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_listings_archive_pages_options',
			),
			array(
				'title'    => __( 'Subcategories Count', 'woocommerce-jetpack' ),
				'desc'     => __( 'Hide subcategories count on category pages', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_listings_hide_cats_count_on_archive',
				'default'  => 'no',
				'type'     => 'checkbox',
				'custom_attributes' => apply_filters( 'booster_get_message', '', 'disabled' ),
				'desc_tip' => apply_filters( 'booster_get_message', '', 'desc' ),
			),
			array(
				'title'    => __( 'Exclude Subcategories', 'woocommerce-jetpack' ),
				'desc_tip' => __(' Excludes one or more categories from the category (archive) pages. Leave blank to disable.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_listings_exclude_cats_on_archives',
				'default'  => '',
				'type'     => 'multiselect',
				'class'    => 'chosen_select',
				'css'      => 'width: 450px;',
				'options'  => $product_cats,
			),
			array(
				'title'    => __( 'Hide Empty', 'woocommerce-jetpack' ),
				'desc'     => __( 'Hide empty subcategories on category pages', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_listings_hide_empty_cats_on_archives',
				'default'  => 'yes',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Show Products', 'woocommerce-jetpack' ),
				'desc'     => __( 'Show products if no categories are displayed on category page', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_listings_show_products_if_no_cats_on_archives',
				'default'  => 'yes',
				'type'     => 'checkbox',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_product_listings_archive_pages_options',
			),
			array(
				'title'    => __( 'TAX Display Prices in the Shop', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'desc'     => __( 'If you want to display part of your products including TAX and another part excluding TAX, you can set it here.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_listings_display_taxes_options',
			),
			array(
				'title'    => __( 'Products - Including TAX', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_listings_display_taxes_products_incl_tax',
				'desc_tip' => __( 'Select products to display including TAX.', 'woocommerce-jetpack' ),
				'default'  => '',
				'type'     => 'multiselect',
				'class'    => 'chosen_select',
				'css'      => 'width: 450px;',
				'options'  => $products,
			),
			array(
				'title'    => __( 'Products - Excluding TAX', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_listings_display_taxes_products_excl_tax',
				'desc_tip' => __( 'Select products to display excluding TAX.', 'woocommerce-jetpack' ),
				'default'  => '',
				'type'     => 'multiselect',
				'class'    => 'chosen_select',
				'css'      => 'width: 450px;',
				'options'  => $products,
			),
			array(
				'title'    => __( 'Product Categories - Including TAX', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_listings_display_taxes_product_cats_incl_tax',
				'desc_tip' => __( 'Select product categories to display including TAX.', 'woocommerce-jetpack' ),
				'default'  => '',
				'type'     => 'multiselect',
				'class'    => 'chosen_select',
				'css'      => 'width: 450px;',
				'options'  => $product_cats,
			),
			array(
				'title'    => __( 'Product Categories - Excluding TAX', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_listings_display_taxes_product_cats_excl_tax',
				'desc_tip' => __( 'Select product categories to display excluding TAX.', 'woocommerce-jetpack' ),
				'default'  => '',
				'type'     => 'multiselect',
				'class'    => 'chosen_select',
				'css'      => 'width: 450px;',
				'options'  => $product_cats,
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_product_listings_display_taxes_options',
			),
		);
		return $settings;
	}

}

endif;

return new WCJ_Product_Listings();
