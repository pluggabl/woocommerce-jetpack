<?php
/**
 * WooCommerce Jetpack Product Listings
 *
 * The WooCommerce Jetpack Product Listings class.
 *
 * @version 2.5.3
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Product_Listings' ) ) :

class WCJ_Product_Listings extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.5.3
	 * @todo    products per page - position priority for every hook; post or get.
	 */
	public function __construct() {
		$this->id         = 'product_listings';
		$this->short_desc = __( 'Product Listings', 'woocommerce-jetpack' );
		$this->desc       = __( 'Change WooCommerce display options for shop and category pages: show/hide categories count, exclude categories, show/hide empty categories. Add "products per page" selector.', 'woocommerce-jetpack' );
		$this->link       = 'http://booster.io/features/woocommerce-product-listings/';
		parent::__construct();

		if ( $this->is_enabled() ) {

			// Exclude and Hide Empty
			add_filter( 'woocommerce_product_subcategories_args',       array( $this, 'filter_subcategories' ), 100 );
			add_filter( 'woocommerce_product_subcategories_hide_empty', array( $this, 'filter_subcategories_show_empty' ), 100 );

			// Hide Count
			if ( 'yes' === get_option( 'wcj_product_listings_hide_cats_count_on_shop' ) || 'yes' === get_option( 'wcj_product_listings_hide_cats_count_on_archive' ) ) {
				add_filter( 'woocommerce_subcategory_count_html', array( $this, 'remove_subcategory_count' ), 100 );
			}

			// Products per Page
			if ( 'yes' === get_option( 'wcj_products_per_page_enabled', 'no' ) ) {
				add_filter( 'loop_shop_per_page', array( $this, 'set_products_per_page_number' ), PHP_INT_MAX );
				$position_hooks = get_option( 'wcj_products_per_page_position', array( 'woocommerce_before_shop_loop' ) );
				foreach ( $position_hooks as $position_hook ) {
					add_action( $position_hook, array( $this, 'add_products_per_page_form' ), get_option( 'wcj_products_per_page_position_priority', 40 ) );
				}
			}

			// Settings to "WooCommerce > Settings > Products > Product Listings"
			add_filter( 'woocommerce_product_settings', array( $this, 'add_fields_to_woocommerce_settings' ), 100 );
		}
	}

	/**
	 * add_products_per_page_form.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function add_products_per_page_form() {

		global $wp_query;

		if ( isset( $_POST['wcj_products_per_page'] ) ) {
			$products_per_page = $_POST['wcj_products_per_page'];
		} elseif ( isset( $_COOKIE['wcj_products_per_page'] ) ) {
			$products_per_page = $_COOKIE['wcj_products_per_page'];
		} else {
			$products_per_page = get_option( 'wcj_products_per_page_default', get_option( 'posts_per_page' ) ); // default
		}

		$paged = get_query_var( 'paged' );
		if ( 0 == $paged ) {
			$paged = 1;
		}

		$products_from  = ( $paged - 1 ) * $products_per_page + 1;
		$products_to    = ( $paged - 1 ) * $products_per_page + $wp_query->post_count;
		$products_total = $wp_query->found_posts;

		$html = '';
		$html .= '<div class="clearfix"></div>';
		$html .= '<div>';
		$html .= '<form action="' . remove_query_arg( 'paged' ) . '" method="POST">';
		$the_text = get_option( 'wcj_products_per_page_text', __( 'Products <strong>%from% - %to%</strong> from <strong>%total%</strong>. Products on page %select_form%', 'woocommerce-jetpack' ) );
		$select_form = '<select name="wcj_products_per_page" id="wcj_products_per_page" class="sortby rounded_corners_class" onchange="this.form.submit()">';
		$html .= str_replace( array( '%from%', '%to%', '%total%', '%select_form%' ), array( $products_from, $products_to, $products_total, $select_form ), $the_text );
		$products_per_page_select_options = apply_filters( 'wcj_get_option_filter', '10|10' . PHP_EOL . '25|25' . PHP_EOL . '50|50' . PHP_EOL . '100|100' . PHP_EOL . 'All|-1', get_option( 'wcj_products_per_page_select_options', '10|10' . PHP_EOL . '25|25' . PHP_EOL . '50|50' . PHP_EOL . '100|100' . PHP_EOL . 'All|-1' ) );
		$products_per_page_select_options = explode( PHP_EOL, $products_per_page_select_options );
		foreach ( $products_per_page_select_options as $products_per_page_select_option ) {
			$the_option = explode( '|', $products_per_page_select_option );
			if ( 2 === count( $the_option ) ) {
				$sort_id   = $the_option[1];
				$sort_name = $the_option[0];
				$html .= '<option value="' . $sort_id . '" ' . selected( $products_per_page, $sort_id, false ) . ' >' . $sort_name . '</option>';
			}
		}
		$html .= '</select>';
		$html .= '</form>';
		$html .= '</div>';

		echo $html;
	}

	/**
	 * set_products_per_page_number.
	 *
	 * @version 2.5.3
	 * @since   2.5.3
	 */
	function set_products_per_page_number( $the_number ) {
		if ( isset( $_POST['wcj_products_per_page'] ) ) {
			$the_number = $_POST['wcj_products_per_page'];
			setcookie( 'wcj_products_per_page', $the_number, ( time() + 1209600 ), '/', $_SERVER['SERVER_NAME'], false );
		} elseif ( isset( $_COOKIE['wcj_products_per_page'] ) ) {
			$the_number = $_COOKIE['wcj_products_per_page'];
		} else {
			$the_number = get_option( 'wcj_products_per_page_default', get_option( 'posts_per_page' ) );
		}
		return $the_number;
	}

	/**
	 * remove_subcategory_count.
	 */
	public function remove_subcategory_count( $count_html ) {
		if (
			( is_shop() && 'yes' === get_option( 'wcj_product_listings_hide_cats_count_on_shop' ) ) ||
			( ! is_shop() && 'yes' === apply_filters( 'wcj_get_option_filter', 'wcj', get_option( 'wcj_product_listings_hide_cats_count_on_archive' ) ) )
		) {
			return '';
		}
		return $count_html;
	}

	/**
	 * filter_subcategories.
	 */
	public function filter_subcategories( $args ) {
		if ( is_shop() ) {
			$args['exclude'] = get_option( 'wcj_product_listings_exclude_cats_on_shop' );
			$args['hide_empty'] = ( 'yes' === get_option( 'wcj_product_listings_hide_empty_cats_on_shop' ) ) ? 1 : 0;     // depreciated?
		} else {
			$args['exclude'] = get_option( 'wcj_product_listings_exclude_cats_on_archives' );
			$args['hide_empty'] = ( 'yes' === get_option( 'wcj_product_listings_hide_empty_cats_on_archives' ) ) ? 1 : 0; // depreciated?
		}
		return $args;
	}

	/**
	 * hide_products_by_disabling_loop.
	 */
	public function hide_products_by_disabling_loop() {
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
	 * filter_subcategories_show_empty.
	 */
	public function filter_subcategories_show_empty() {

		// Not the best solution, but it's the only place I found to put it...
		$this->hide_products_by_disabling_loop();

		$show_empty = false;
		if ( is_shop() ) {
			$show_empty = ( 'yes' === get_option( 'wcj_product_listings_hide_empty_cats_on_shop' ) ) ? false : true;
		} else {
			$show_empty = ( 'yes' === get_option( 'wcj_product_listings_hide_empty_cats_on_archives' ) ) ? false : true;
		}

		return $show_empty;
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
					'desc'     => __( 'Hide categories count on shop page', 'woocommerce-jetpack' ),
					'id'       => 'wcj_product_listings_hide_cats_count_on_shop',
					'default'  => 'no',
					'type'     => 'checkbox',
				);
				$updated_settings[] = array(
					'title'    => __( 'WooJetpack: Exclude Categories on Shop Page', 'woocommerce-jetpack' ),
					'desc_tip' => __(' Excludes one or more categories from the shop page. This parameter takes a comma-separated list of categories by unique ID, in ascending order. Leave blank to disable.', 'woocommerce-jetpack' ),
					'id'       => 'wcj_product_listings_exclude_cats_on_shop',
					'default'  => '',
					'type'     => 'text',
					'css'      => 'width:50%;min-width:300px;',
				);
				$updated_settings[] = array(
					'title'    => __( 'WooJetpack: Hide Empty', 'woocommerce-jetpack' ),
					'desc'     => __( 'Hide empty categories on shop page', 'woocommerce-jetpack' ),
					'id'       => 'wcj_product_listings_hide_empty_cats_on_shop',
					'default'  => 'yes',
					'type'     => 'checkbox',
				);
				$updated_settings[] = array(
					'title'    => __( 'WooJetpack: Show Products', 'woocommerce-jetpack' ),
					'desc'     => __( 'Show products if no categories are displayed on shop page', 'woocommerce-jetpack' ),
					'id'       => 'wcj_product_listings_show_products_if_no_cats_on_shop',
					'default'  => 'yes',
					'type'     => 'checkbox',
				);
			}
			if ( isset( $section['id'] ) && 'woocommerce_category_archive_display' == $section['id'] ) {
				$updated_settings[] = array(
					'title'    => __( 'WooJetpack: Subcategories Count', 'woocommerce-jetpack' ),
					'desc'     => __( 'Hide subcategories count on category pages', 'woocommerce-jetpack' ),
					'id'       => 'wcj_product_listings_hide_cats_count_on_archive',
					'default'  => 'no',
					'type'     => 'checkbox',
					'custom_attributes' => apply_filters( 'get_wc_jetpack_plus_message', '', 'disabled' ),
					'desc_tip' => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
				);
				$updated_settings[] = array(
					'title'    => __( 'WooJetpack: Exclude Subcategories on Category Pages', 'woocommerce-jetpack' ),
					'desc_tip' => __(' Excludes one or more categories from the category (archive) pages. This parameter takes a comma-separated list of categories by unique ID, in ascending order. Leave blank to disable.', 'woocommerce-jetpack' ),
					'id'       => 'wcj_product_listings_exclude_cats_on_archives',
					'default'  => '',
					'type'     => 'text',
					'css'      => 'width:50%;min-width:300px;',
				);
				$updated_settings[] = array(
					'title'    => __( 'WooJetpack: Hide Empty', 'woocommerce-jetpack' ),
					'desc'     => __( 'Hide empty subcategories on category pages', 'woocommerce-jetpack' ),
					'id'       => 'wcj_product_listings_hide_empty_cats_on_archives',
					'default'  => 'yes',
					'type'     => 'checkbox',
				);
				$updated_settings[] = array(
					'title'    => __( 'WooJetpack: Show Products', 'woocommerce-jetpack' ),
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
	 * get_settings.
	 *
	 * @version 2.5.3
	 */
	function get_settings() {
		$settings = array(
			array(
				'title'    => __( 'Shop Page Display Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'desc'     => __( 'This will work only when "Shop Page Display" in "WooCommerce > Settings > Products > Product Listings" is set to "Show subcategories" or "Show both".', 'woocommerce-jetpack' ),
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
				'desc_tip' => __(' Excludes one or more categories from the shop page. This parameter takes a comma-separated list of categories by unique ID, in ascending order. Leave blank to disable.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_listings_exclude_cats_on_shop',
				'default'  => '',
				'type'     => 'text',
				'css'      => 'width:50%;min-width:300px;',
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
				'desc'     => __( 'This will work only when "Default Category Display" in "WooCommerce > Settings > Products > Product Listings" is set to "Show subcategories" or "Show both".', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_listings_archive_pages_options',
			),
			array(
				'title'    => __( 'Subcategories Count', 'woocommerce-jetpack' ),
				'desc'     => __( 'Hide subcategories count on category pages', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_listings_hide_cats_count_on_archive',
				'default'  => 'no',
				'type'     => 'checkbox',
				'custom_attributes' => apply_filters( 'get_wc_jetpack_plus_message', '', 'disabled' ),
				'desc_tip' => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc' ),
			),
			array(
				'title'    => __( 'Exclude Subcategories', 'woocommerce-jetpack' ),
				'desc_tip' => __(' Excludes one or more categories from the category (archive) pages. This parameter takes a comma-separated list of categories by unique ID, in ascending order. Leave blank to disable.', 'woocommerce-jetpack' ),
				'id'       => 'wcj_product_listings_exclude_cats_on_archives',
				'default'  => '',
				'type'     => 'text',
				'css'      => 'width:50%;min-width:300px;',
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
				'title'    => __( 'Products per Page Options', 'woocommerce-jetpack' ),
				'type'     => 'title',
				'id'       => 'wcj_products_per_page_options',
			),
			array(
				'title'    => __( 'Enable Products per Page', 'woocommerce-jetpack' ),
				'desc'     => __( 'Enable', 'woocommerce-jetpack' ),
				'id'       => 'wcj_products_per_page_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Select Options', 'woocommerce-jetpack' ),
				'desc'     => __( 'Name|Number; one per line; -1 for all products', 'woocommerce-jetpack' ),
				'id'       => 'wcj_products_per_page_select_options',
				'default'  => '10|10' . PHP_EOL . '25|25' . PHP_EOL . '50|50' . PHP_EOL . '100|100' . PHP_EOL . 'All|-1',
				'type'     => 'textarea',
				'css'      => 'height:200px;',
				'custom_attributes' => apply_filters( 'get_wc_jetpack_plus_message', '', 'readonly' ),
				'desc_tip' => apply_filters( 'get_wc_jetpack_plus_message', '', 'desc_no_link' ),
			),
			array(
				'title'    => __( 'Default', 'woocommerce-jetpack' ),
				'id'       => 'wcj_products_per_page_default',
				'default'  => get_option( 'posts_per_page' ),
				'type'     => 'number',
				'custom_attributes' => array( 'min' => -1 ),
			),
			array(
				'title'    => __( 'Position', 'woocommerce-jetpack' ),
				'id'       => 'wcj_products_per_page_position',
				'default'  => array( 'woocommerce_before_shop_loop' ),
				'type'     => 'multiselect',
				'class'    => 'chosen_select',
				'options'  => array(
					'woocommerce_before_shop_loop' => __( 'Before shop loop', 'woocommerce-jetpack' ),
					'woocommerce_after_shop_loop'  => __( 'After shop loop', 'woocommerce-jetpack' ),
				),
			),
			array(
				'title'    => __( 'Position Priority', 'woocommerce-jetpack' ),
				'id'       => 'wcj_products_per_page_position_priority',
				'default'  => 40,
				'type'     => 'number',
				'custom_attributes' => array( 'min' => 0 ),
			),
			array(
				'title'    => __( 'Text', 'woocommerce-jetpack' ),
				'id'       => 'wcj_products_per_page_text',
				'default'  => __( 'Products <strong>%from% - %to%</strong> from <strong>%total%</strong>. Products on page %select_form%', 'woocommerce-jetpack' ),
				'type'     => 'textarea',
				'css'      => 'width:66%;min-width:300px;',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'wcj_products_per_page_options',
			),
		);
		return $this->add_standard_settings( $settings );
	}

}

endif;

return new WCJ_Product_Listings();
