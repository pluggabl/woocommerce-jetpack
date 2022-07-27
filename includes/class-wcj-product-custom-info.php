<?php
/**
 * Booster for WooCommerce - Module - Product Info
 *
 * @version 5.6.2
 * @since   2.4.0
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Product_Custom_Info' ) ) :
	/**
	 * WCJ_Product_Custom_Info.
	 */
	class WCJ_Product_Custom_Info extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 5.2.0
		 */
		public function __construct() {

			$this->id         = 'product_custom_info';
			$this->short_desc = __( 'Product Info', 'woocommerce-jetpack' );
			$this->desc       = __( 'Add additional info to category and single product pages (1 block allowed in free version).', 'woocommerce-jetpack' );
			$this->desc_pro   = __( 'Add additional info to category and single product pages.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-product-info';
			parent::__construct();

			if ( $this->is_enabled() ) {
				$single_or_archive_array = array( 'single', 'archive' );
				foreach ( $single_or_archive_array as $single_or_archive ) {
					$default_hook                          = ( 'single' === $single_or_archive ) ? 'woocommerce_after_single_product_summary' : 'woocommerce_after_shop_loop_item_title';
					$wcj_product_custom_info_total_number_ = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_product_custom_info_total_number_' . $single_or_archive, 1 ) );
					for ( $i = 1; $i <= $wcj_product_custom_info_total_number_; $i++ ) {
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
		 * Is_visible.
		 *
		 * @version 2.9.0
		 * @since   2.9.0
		 * @param id     $product_id defines the product_id.
		 * @param string $current_filter defines the current_filter.
		 * @param string $current_filter_priority defines the current_filter_priority.
		 * @param string $default_hook defines the default_hook.
		 * @param string $single_or_archive defines the single_or_archive.
		 * @param int    $i defines the i.
		 */
		public function is_visible( $product_id, $current_filter, $current_filter_priority, $default_hook, $single_or_archive, $i ) {
			return (
			$this->check_content_and_filter( $current_filter, $current_filter_priority, $default_hook, $single_or_archive, $i ) &&
			$this->check_included_and_excluded( $product_id, $single_or_archive, $i )
			);
		}

		/**
		 * Check_content_and_filter.
		 *
		 * @version 5.6.2
		 * @since   2.9.0
		 * @param string $current_filter defines the current_filter.
		 * @param string $current_filter_priority defines the current_filter_priority.
		 * @param string $default_hook defines the default_hook.
		 * @param string $single_or_archive defines the single_or_archive.
		 * @param int    $i defines the i.
		 */
		public function check_content_and_filter( $current_filter, $current_filter_priority, $default_hook, $single_or_archive, $i ) {
			return (
			'' !== wcj_get_option( 'wcj_product_custom_info_content_' . $single_or_archive . '_' . $i ) &&
			wcj_get_option( 'wcj_product_custom_info_hook_' . $single_or_archive . '_' . $i, $default_hook ) === $current_filter &&
			wcj_get_option( 'wcj_product_custom_info_priority_' . $single_or_archive . '_' . $i, '10' ) === (string) $current_filter_priority
			);
		}

		/**
		 * Check_included_and_excluded.
		 *
		 * @version 5.6.2
		 * @since   2.9.0
		 * @param int    $product_id defines the product_id.
		 * @param string $single_or_archive defines the single_or_archive.
		 * @param int    $i defines the i.
		 */
		public function check_included_and_excluded( $product_id, $single_or_archive, $i ) {
			$product_cats_to_include = wcj_maybe_convert_string_to_array( wcj_get_option( 'wcj_product_custom_info_product_cats_to_include_' . $single_or_archive . '_' . $i ) );
			$product_cats_to_exclude = wcj_maybe_convert_string_to_array( wcj_get_option( 'wcj_product_custom_info_product_cats_to_exclude_' . $single_or_archive . '_' . $i ) );
			$product_tags_to_include = wcj_maybe_convert_string_to_array( wcj_get_option( 'wcj_product_custom_info_product_tags_to_include_' . $single_or_archive . '_' . $i ) );
			$product_tags_to_exclude = wcj_maybe_convert_string_to_array( wcj_get_option( 'wcj_product_custom_info_product_tags_to_exclude_' . $single_or_archive . '_' . $i ) );
			$products_to_exclude     = wcj_maybe_convert_string_to_array( wcj_get_option( 'wcj_product_custom_info_products_to_exclude_' . $single_or_archive . '_' . $i ) );
			$products_to_include     = wcj_maybe_convert_string_to_array( wcj_get_option( 'wcj_product_custom_info_products_to_include_' . $single_or_archive . '_' . $i ) );
			return (
			( empty( $product_cats_to_exclude ) || ! wcj_is_product_term( $product_id, $product_cats_to_exclude, 'product_cat' ) ) &&
			( empty( $product_cats_to_include ) || wcj_is_product_term( $product_id, $product_cats_to_include, 'product_cat' ) ) &&
			( empty( $product_tags_to_exclude ) || ! wcj_is_product_term( $product_id, $product_tags_to_exclude, 'product_tag' ) ) &&
			( empty( $product_tags_to_include ) || wcj_is_product_term( $product_id, $product_tags_to_include, 'product_tag' ) ) &&
			( empty( $products_to_exclude ) || ! in_array( (string) $product_id, $products_to_exclude, true ) ) &&
			( empty( $products_to_include ) || in_array( (string) $product_id, $products_to_include, true ) )
			);
		}

		/**
		 * Add_product_custom_info.
		 *
		 * @version 2.9.0
		 */
		public function add_product_custom_info() {
			$product_id              = get_the_ID();
			$current_filter          = current_filter();
			$current_filter_priority = wcj_current_filter_priority();
			$single_or_archive_array = array( 'single', 'archive' );
			foreach ( $single_or_archive_array as $single_or_archive ) {
				$default_hook                         = ( 'single' === $single_or_archive ) ? 'woocommerce_after_single_product_summary' : 'woocommerce_after_shop_loop_item_title';
				$wcj_product_custom_info_total_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_product_custom_info_total_number_' . $single_or_archive, 1 ) );
				for ( $i = 1; $i <= $wcj_product_custom_info_total_number; $i++ ) {
					if ( $this->is_visible( $product_id, $current_filter, $current_filter_priority, $default_hook, $single_or_archive, $i ) ) {
						echo do_shortcode( wcj_get_option( 'wcj_product_custom_info_content_' . $single_or_archive . '_' . $i ) );
					}
				}
			}
		}

	}

endif;

return new WCJ_Product_Custom_Info();
