<?php
/**
 * Booster for WooCommerce - Module - Product Info
 *
 * @version 2.9.1
 * @since   2.4.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WCJ_Product_Custom_info' ) ) :

class WCJ_Product_Custom_info extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 2.8.0
	 */
	function __construct() {

		$this->id         = 'product_custom_info';
		$this->short_desc = __( 'Product Info', 'woocommerce-jetpack' );
		$this->desc       = __( 'Add additional info to WooCommerce category and single product pages.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-product-info';
		parent::__construct();

		if ( $this->is_enabled() ) {
			$single_or_archive_array = array( 'single', 'archive' );
			foreach ( $single_or_archive_array as $single_or_archive ) {
				$default_hook = ( 'single' === $single_or_archive ) ? 'woocommerce_after_single_product_summary' : 'woocommerce_after_shop_loop_item_title';
				for ( $i = 1; $i <= apply_filters( 'booster_option', 1, get_option( 'wcj_product_custom_info_total_number_' . $single_or_archive, 1 ) ); $i++ ) {
					add_action(
						get_option( 'wcj_product_custom_info_hook_' . $single_or_archive . '_' . $i, $default_hook ),
						array( $this, 'add_product_custom_info' ),
						get_option( 'wcj_product_custom_info_priority_' . $single_or_archive . '_' . $i, 10 )
					);
				}
			}
		}
	}

	/**
	 * is_visible.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 */
	function is_visible( $product_id, $current_filter, $current_filter_priority, $default_hook, $single_or_archive, $i ) {
		return (
			$this->check_content_and_filter( $current_filter, $current_filter_priority, $default_hook, $single_or_archive, $i ) &&
			$this->check_included_and_excluded( $product_id, $single_or_archive, $i )
		);
	}

	/**
	 * check_content_and_filter.
	 *
	 * @version 2.9.0
	 * @since   2.9.0
	 */
	function check_content_and_filter( $current_filter, $current_filter_priority, $default_hook, $single_or_archive, $i ) {
		return (
			''                       != get_option( 'wcj_product_custom_info_content_'  . $single_or_archive . '_' . $i ) &&
			$current_filter         === get_option( 'wcj_product_custom_info_hook_'     . $single_or_archive . '_' . $i, $default_hook ) &&
			$current_filter_priority == get_option( 'wcj_product_custom_info_priority_' . $single_or_archive . '_' . $i, 10 )
		);
	}

	/**
	 * check_included_and_excluded.
	 *
	 * @version 2.9.1
	 * @since   2.9.0
	 */
	function check_included_and_excluded( $product_id, $single_or_archive, $i ) {
		$product_cats_to_include = get_option( 'wcj_product_custom_info_product_cats_to_include_' . $single_or_archive . '_' . $i );
		$product_cats_to_exclude = get_option( 'wcj_product_custom_info_product_cats_to_exclude_' . $single_or_archive . '_' . $i );
		$product_tags_to_include = get_option( 'wcj_product_custom_info_product_tags_to_include_' . $single_or_archive . '_' . $i );
		$product_tags_to_exclude = get_option( 'wcj_product_custom_info_product_tags_to_exclude_' . $single_or_archive . '_' . $i );
		$products_to_exclude     = wcj_maybe_convert_string_to_array( get_option( 'wcj_product_custom_info_products_to_exclude_' . $single_or_archive . '_' . $i ) );
		$products_to_include     = wcj_maybe_convert_string_to_array( get_option( 'wcj_product_custom_info_products_to_include_' . $single_or_archive . '_' . $i ) );
		return (
			( empty( $product_cats_to_exclude ) || ! wcj_is_product_term( $product_id, $product_cats_to_exclude, 'product_cat' ) ) &&
			( empty( $product_cats_to_include ) ||   wcj_is_product_term( $product_id, $product_cats_to_include, 'product_cat' ) ) &&
			( empty( $product_tags_to_exclude ) || ! wcj_is_product_term( $product_id, $product_tags_to_exclude, 'product_tag' ) ) &&
			( empty( $product_tags_to_include ) ||   wcj_is_product_term( $product_id, $product_tags_to_include, 'product_tag' ) ) &&
			( empty( $products_to_exclude )     || ! in_array( $product_id, $products_to_exclude ) ) &&
			( empty( $products_to_include )     ||   in_array( $product_id, $products_to_include ) )
		);
	}

	/**
	 * add_product_custom_info.
	 *
	 * @version 2.9.0
	 */
	function add_product_custom_info() {
		$product_id              = get_the_ID();
		$current_filter          = current_filter();
		$current_filter_priority = wcj_current_filter_priority();
		$single_or_archive_array = array( 'single', 'archive' );
		foreach ( $single_or_archive_array as $single_or_archive ) {
			$default_hook = ( 'single' === $single_or_archive ) ? 'woocommerce_after_single_product_summary' : 'woocommerce_after_shop_loop_item_title';
			for ( $i = 1; $i <= apply_filters( 'booster_option', 1, get_option( 'wcj_product_custom_info_total_number_' . $single_or_archive, 1 ) ); $i++ ) {
				if ( $this->is_visible( $product_id, $current_filter, $current_filter_priority, $default_hook, $single_or_archive, $i ) ) {
					echo do_shortcode( get_option( 'wcj_product_custom_info_content_' . $single_or_archive . '_' . $i ) );
				}
			}
		}
	}

}

endif;

return new WCJ_Product_Custom_info();
