<?php
/**
 * WooCommerce Jetpack Product Listings
 *
 * The WooCommerce Jetpack Product Listings class.
 *
 * @class		WCJ_Product_Listings
 * @version		1.2.0
 * @category	Class
 * @author 		Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Product_Listings' ) ) :

class WCJ_Product_Listings {

	/**
	 * Constructor.
	 */
	public function __construct() {
	    // Main hooks
		if ( 'yes' === get_option( 'wcj_product_listings_enabled' ) ) {
			// Exclude and Hide Empty
			add_filter( 'woocommerce_product_subcategories_args', 		array( $this, 'filter_subcategories' ), 100 );
			add_filter( 'woocommerce_product_subcategories_hide_empty', array( $this, 'filter_subcategories_show_empty' ), 100 );
			// Hide Count
			if ( 'yes' === get_option( 'wcj_product_listings_hide_cats_count_on_shop' ) || 'yes' === get_option( 'wcj_product_listings_hide_cats_count_on_archive' ) )
				add_filter( 'woocommerce_subcategory_count_html', array( $this, 'remove_subcategory_count' ), 100 );
			// Settings to "WooCommerce > Settings > Products > Product Listings"
			add_filter( 'woocommerce_product_settings', array( $this, 'add_fields_to_woocommerce_settings' ), 100 );
		}
	    // Settings hooks
	    add_filter( 'wcj_settings_sections', array( $this, 'settings_section' ) );
	    add_filter( 'wcj_settings_product_listings', array( $this, 'get_settings' ), 100 );
	    add_filter( 'wcj_features_status', array( $this, 'add_enabled_option' ), 100 );
	}

	/**
	 * add_enabled_option.
	 */
	public function add_enabled_option( $settings ) {
	    $all_settings = $this->get_settings();
	    $settings[] = $all_settings[1];
	    return $settings;
	}

	/**
	 * get_settings.
	 */
	function get_settings() {

	    $settings = array(

	        array( 'title' => __( 'Product Listings Options', 'woocommerce-jetpack' ), 'type' => 'title', 'desc' => __( '', 'woocommerce-jetpack' ), 'id' => 'wcj_product_listings_options' ),

	        array(
	            'title'    => __( 'Product Listings', 'woocommerce-jetpack' ),
	            'desc'     => '<strong>' . __( 'Enable Module', 'woocommerce-jetpack' ) . '</strong>',
	            'desc_tip' => __( 'Change WooCommerce display options for shop and category pages: show/hide categories count, exclude categories, show/hide empty categories.', 'woocommerce-jetpack' ),
	            'id'       => 'wcj_product_listings_enabled',
	            'default'  => 'no',
	            'type'     => 'checkbox',
	        ),

	        array( 'type'  => 'sectionend', 'id' => 'wcj_product_listings_options' ),

			array( 'title' => __( 'Shop Page Display Options', 'woocommerce-jetpack' ), 'type' => 'title', 'desc' => __( 'This will work only when "Shop Page Display" in "WooCommerce > Settings > Products > Product Listings" is set to "Show subcategories" or "Show both".', 'woocommerce-jetpack' ), 'id' => 'wcj_product_listings_shop_page_options' ),

			array(
	            'title'    => __( 'Categories Count', 'woocommerce-jetpack' ),
				'desc'	   => __( 'Hide categories count on shop page', 'woocommerce-jetpack' ),
	            'id'       => 'wcj_product_listings_hide_cats_count_on_shop',
	            'default'  => 'no',
	            'type'     => 'checkbox',
	        ),

	        array(
				'title'    => __( 'Exclude Categories', 'woocommerce-jetpack' ),
				'desc_tip' => __(' Excludes one or more categories from the shop page. This parameter takes a comma-separated list of categories by unique ID, in ascending order. Leave blank to disable.', 'woocommerce-jetpack' ),
	            'id'       => 'wcj_product_listings_exclude_cats_on_shop',
	            'default'  => '',
	            'type'     => 'text',
				'css'	   => 'width:50%;min-width:300px;',
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

			array( 'type'  => 'sectionend', 'id' => 'wcj_product_listings_shop_page_options' ),

			array( 'title' => __( 'Category Display Options', 'woocommerce-jetpack' ), 'type' => 'title', 'desc' => __( 'This will work only when "Default Category Display" in "WooCommerce > Settings > Products > Product Listings" is set to "Show subcategories" or "Show both".', 'woocommerce-jetpack' ), 'id' => 'wcj_product_listings_archive_pages_options' ),

			array(
	            'title'    => __( 'Subcategories Count', 'woocommerce-jetpack' ),
				'desc'     => __( 'Hide subcategories count on category pages', 'woocommerce-jetpack' ),
	            'id'       => 'wcj_product_listings_hide_cats_count_on_archive',
	            'default'  => 'no',
	            'type'     => 'checkbox',
				'custom_attributes'	=> apply_filters( 'get_wc_jetpack_plus_message', '', 'disabled' ),
				'desc_tip'	=> apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
	        ),

	        array(
	            'title'    => __( 'Exclude Subcategories', 'woocommerce-jetpack' ),
				'desc_tip' => __(' Excludes one or more categories from the category (archive) pages. This parameter takes a comma-separated list of categories by unique ID, in ascending order. Leave blank to disable.', 'woocommerce-jetpack' ),
	            'id'       => 'wcj_product_listings_exclude_cats_on_archives',
	            'default'  => '',
	            'type'     => 'text',
				'css'	   => 'width:50%;min-width:300px;',
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

			array( 'type'  => 'sectionend', 'id' => 'wcj_product_listings_archive_pages_options' ),
	    );

	    return $settings;
	}

	/**
	 * settings_section.
	 */
	function settings_section( $sections ) {
	    $sections['product_listings'] = __( 'Product Listings', 'woocommerce-jetpack' );
	    return $sections;
	}

	/**
	 * add_fields_to_woocommerce_settings.
	 */
	function add_fields_to_woocommerce_settings( $settings ) {

		$updated_settings = array();

		foreach ( $settings as $section ) {

			$updated_settings[] = $section;

			if ( isset( $section['id'] ) && 'woocommerce_shop_page_display' == $section['id'] ) {

				$updated_settings[] = array(
					'title'    => __( 'WooJetpack: Categories Count', 'woocommerce-jetpack' ),
					'desc'	   => __( 'Hide categories count on shop page', 'woocommerce-jetpack' ),
					'id'       => 'wcj_product_listings_hide_cats_count_on_shop',
					'default'  => 'no',
					'type'     => 'checkbox',
				);

				$updated_settings[] = array(
					'title'     => __( 'WooJetpack: Exclude Categories on Shop Page', 'woocommerce-jetpack' ),
					'desc_tip' => __(' Excludes one or more categories from the shop page. This parameter takes a comma-separated list of categories by unique ID, in ascending order. Leave blank to disable.', 'woocommerce-jetpack' ),
					'id'       => 'wcj_product_listings_exclude_cats_on_shop',
					'default'  => '',
					'type'     => 'text',
					'css'	   => 'width:50%;min-width:300px;',
				);

				$updated_settings[] = array(
					'title'     => __( 'WooJetpack: Hide Empty', 'woocommerce-jetpack' ),
					'desc'     => __( 'Hide empty categories on shop page', 'woocommerce-jetpack' ),
					'id'       => 'wcj_product_listings_hide_empty_cats_on_shop',
					'default'  => 'yes',
					'type'     => 'checkbox',
				);

				$updated_settings[] = array(
					'title'     => __( 'WooJetpack: Show Products', 'woocommerce-jetpack' ),
					'desc'     => __( 'Show products if no categories are displayed on shop page', 'woocommerce-jetpack' ),
					'id'       => 'wcj_product_listings_show_products_if_no_cats_on_shop',
					'default'  => 'yes',
					'type'     => 'checkbox',
				);
			}

			if ( isset( $section['id'] ) && 'woocommerce_category_archive_display' == $section['id'] ) {

				$updated_settings[] = array(
					'title'    => __( 'WooJetpack: Subcategories Count', 'woocommerce-jetpack' ),
					'desc'	   => __( 'Hide subcategories count on category pages', 'woocommerce-jetpack' ),
					'id'       => 'wcj_product_listings_hide_cats_count_on_archive',
					'default'  => 'no',
					'type'     => 'checkbox',
					'custom_attributes'	=> apply_filters( 'get_wc_jetpack_plus_message', '', 'disabled' ),
					'desc_tip'	=> apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				);

				$updated_settings[] = array(
					'title'     => __( 'WooJetpack: Exclude Subcategories on Category Pages', 'woocommerce-jetpack' ),
					'desc_tip' => __(' Excludes one or more categories from the category (archive) pages. This parameter takes a comma-separated list of categories by unique ID, in ascending order. Leave blank to disable.', 'woocommerce-jetpack' ),
					'id'       => 'wcj_product_listings_exclude_cats_on_archives',
					'default'  => '',
					'type'     => 'text',
					'css'	   => 'width:50%;min-width:300px;',
				);

				$updated_settings[] = array(
					'title'     => __( 'WooJetpack: Hide Empty', 'woocommerce-jetpack' ),
					'desc'     => __( 'Hide empty subcategories on category pages', 'woocommerce-jetpack' ),
					'id'       => 'wcj_product_listings_hide_empty_cats_on_archives',
					'default'  => 'yes',
					'type'     => 'checkbox',
				);

				$updated_settings[] = array(
					'title'     => __( 'WooJetpack: Show Products', 'woocommerce-jetpack' ),
					'desc'     => __( 'Show products if no categories are displayed on category page', 'woocommerce-jetpack' ),
					'id'       => 'wcj_product_listings_show_products_if_no_cats_on_archives',
					'default'  => 'yes',
					'type'     => 'checkbox',
				);
			}
		}

		return $updated_settings;
	}

	/**
	 * remove_subcategory_count.
	 */
	public function remove_subcategory_count( $count_html ) {
		if ( ( is_shop() && 'yes' === get_option( 'wcj_product_listings_hide_cats_count_on_shop' ) ) ||
			 ( ! is_shop() && 'yes' === apply_filters( 'wcj_get_option_filter', 'wcj', get_option( 'wcj_product_listings_hide_cats_count_on_archive' ) ) ) )
			return '';
		return $count_html;
	}

	/**
	 * filter_subcategories.
	 */
	public function filter_subcategories( $args ) {
		if ( is_shop() ) {
			$args['exclude'] = get_option( 'wcj_product_listings_exclude_cats_on_shop' );
			$args['hide_empty'] = ( 'yes' === get_option( 'wcj_product_listings_hide_empty_cats_on_shop' ) ) ? 1 : 0;		// depreciated?
		}
		else {
			$args['exclude'] = get_option( 'wcj_product_listings_exclude_cats_on_archives' );
			$args['hide_empty'] = ( 'yes' === get_option( 'wcj_product_listings_hide_empty_cats_on_archives' ) ) ? 1 : 0;	// depreciated?
		}
		return $args;
	}

	/**
	 * hide_products_by_disabling_loop.
	 */
	public function hide_products_by_disabling_loop() {
		// If we are hiding products disable the loop and pagination
		global $wp_query;
		if ( is_product_category() &&
			 get_option( 'woocommerce_category_archive_display' ) == 'subcategories' &&
			 'no' === get_option( 'wcj_product_listings_show_products_if_no_cats_on_archives' ) ) {
				$wp_query->post_count    = 0;
				$wp_query->max_num_pages = 0;
		}
		if ( is_shop() &&
			 get_option( 'woocommerce_shop_page_display' ) == 'subcategories' &&
			 'no' === get_option( 'wcj_product_listings_show_products_if_no_cats_on_shop' ) ) {
				$wp_query->post_count    = 0;
				$wp_query->max_num_pages = 0;
			}
	}

	/**
	 * filter_subcategories_show_empty.
	 */
	public function filter_subcategories_show_empty() {

		// Not the best solution, but it's the only place I found to put it...
		$this->hide_products_by_disabling_loop();

		$show_empty = false;
		if ( is_shop() )
			$show_empty = ( 'yes' === get_option( 'wcj_product_listings_hide_empty_cats_on_shop' ) ) ? false : true;
		else
			$show_empty = ( 'yes' === get_option( 'wcj_product_listings_hide_empty_cats_on_archives' ) ) ? false : true;

		return $show_empty;
	}
}

endif;

return new WCJ_Product_Listings();
