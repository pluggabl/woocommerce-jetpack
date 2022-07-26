<?php
/**
 * Booster for WooCommerce - Module - Product Info V1 (Deprecated)
 *
 * @version 5.6.2
 * @author  Pluggabl LLC.
 * @deprecated
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WCJ_Product_Info' ) ) :
	/**
	 * WCJ_Product_Info.
	 */
	class WCJ_Product_Info extends WCJ_Module {

		/**
		 * Search_and_replace_deprecated_shortcodes.
		 *
		 * @version 2.4.0
		 * @since   2.4.0
		 * @param string | array $data defines the data.
		 */
		private function search_and_replace_deprecated_shortcodes( $data ) {
			$search_and_replace_deprecated_shortcodes_array = array(
				'%sku%'                                  => '[wcj_product_sku]',
				'wcj_sku'                                => 'wcj_product_sku',
				'%title%'                                => '[wcj_product_title]',
				'wcj_title'                              => 'wcj_product_title',
				'%weight%'                               => '[wcj_product_weight]',
				'wcj_weight'                             => 'wcj_product_weight',
				'%total_sales%'                          => '[wcj_product_total_sales]',
				'wcj_total_sales'                        => 'wcj_product_total_sales',
				'%shipping_class%'                       => '[wcj_product_shipping_class]',
				'wcj_shipping_class'                     => 'wcj_product_shipping_class',
				'%dimensions%'                           => '[wcj_product_dimensions]',
				'wcj_dimensions'                         => 'wcj_product_dimensions',
				'%formatted_name%'                       => '[wcj_product_formatted_name]',
				'wcj_formatted_name'                     => 'wcj_product_formatted_name',
				'%stock_availability%'                   => '[wcj_product_stock_availability]',
				'wcj_stock_availability'                 => 'wcj_product_stock_availability',
				'%tax_class%'                            => '[wcj_product_tax_class]',
				'wcj_tax_class'                          => 'wcj_product_tax_class',
				'%average_rating%'                       => '[wcj_product_average_rating]',
				'wcj_average_rating'                     => 'wcj_product_average_rating',
				'%categories%'                           => '[wcj_product_categories]',
				'wcj_categories'                         => 'wcj_product_categories',
				'%list_attributes%'                      => '[wcj_product_list_attributes]',
				'wcj_list_attributes'                    => 'wcj_product_list_attributes',
				'wcj_list_attribute options='            => 'wcj_product_list_attribute name=',
				'wcjp_list_attribute attribute_name='    => 'wcj_product_list_attribute name=',
				'%stock_quantity%'                       => '[wcj_product_stock_quantity]',
				'wcj_stock_quantity'                     => 'wcj_product_stock_quantity',
				'%sale_price%'                           => '[wcj_product_sale_price hide_currency="yes"]',
				'wcj_sale_price'                         => 'wcj_product_sale_price hide_currency="yes"',
				'%sale_price_formatted%'                 => '[wcj_product_sale_price]',
				'wcj_sale_price_formatted'               => 'wcj_product_sale_price',
				'%regular_price%'                        => '[wcj_product_regular_price hide_currency="yes"]',
				'wcj_regular_price'                      => 'wcj_product_regular_price hide_currency="yes"',
				'%regular_price_formatted%'              => '[wcj_product_regular_price]',
				'wcj_regular_price_formatted'            => 'wcj_product_regular_price',
				'%regular_price_if_on_sale%'             => '[wcj_product_regular_price hide_currency="yes" show_always="no"]',
				'wcj_regular_price_if_on_sale'           => 'wcj_product_regular_price hide_currency="yes" show_always="no"',
				'%regular_price_if_on_sale_formatted%'   => '[wcj_product_regular_price show_always="no"]',
				'wcj_regular_price_if_on_sale_formatted' => 'wcj_product_regular_price show_always="no"',
				'%time_since_last_sale%'                 => '[wcj_product_time_since_last_sale]',
				'wcj_time_since_last_sale'               => 'wcj_product_time_since_last_sale',
				'%price_including_tax%'                  => '[wcj_product_price_including_tax hide_currency="yes"]',
				'wcj_price_including_tax'                => 'wcj_product_price_including_tax hide_currency="yes"',
				'%price_including_tax_formatted%'        => '[wcj_product_price_including_tax]',
				'wcj_price_including_tax_formatted'      => 'wcj_product_price_including_tax',
				'%price_excluding_tax%'                  => '[wcj_product_price_excluding_tax hide_currency="yes"]',
				'wcj_price_excluding_tax'                => 'wcj_product_price_excluding_tax hide_currency="yes"',
				'%price_excluding_tax_formatted%'        => '[wcj_product_price_excluding_tax]',
				'wcj_price_excluding_tax_formatted'      => 'wcj_product_price_excluding_tax',
				'%price%'                                => '[wcj_product_price hide_currency="yes"]',
				'wcj_price'                              => 'wcj_product_price hide_currency="yes"',
				'%price_formatted%'                      => '[wcj_product_price]',
				'wcj_price_formatted'                    => 'wcj_product_price',
				'%you_save%'                             => '[wcj_product_you_save hide_currency="yes"]',
				'wcj_you_save'                           => 'wcj_product_you_save hide_currency="yes"',
				'%you_save_formatted%'                   => '[wcj_product_you_save]',
				'wcj_you_save_formatted'                 => 'wcj_product_you_save',
				'%you_save_percent%'                     => '[wcj_product_you_save_percent]',
				'wcj_you_save_percent'                   => 'wcj_product_you_save_percent',
				'wcj_available_variations'               => 'wcj_product_available_variations',
			);
			return str_replace(
				array_keys( $search_and_replace_deprecated_shortcodes_array ),
				array_values( $search_and_replace_deprecated_shortcodes_array ),
				$data
			);
		}

		/**
		 * Constructor.
		 *
		 * @version 2.8.0
		 */
		public function __construct() {

			$this->id         = 'product_info';
			$this->short_desc = __( 'Product Info V1', 'woocommerce-jetpack' );
			$this->desc       = __( 'Add additional info to category and single product pages.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-product-info';
			parent::__construct();

			$this->product_info_on_archive_filters_array = $this->get_product_info_on_archive_filters_array();
			$this->product_info_on_single_filters_array  = $this->get_product_info_on_single_filters_array();

			if ( $this->is_enabled() ) {
				$this->add_product_info_filters( 'archive' );
				$this->add_product_info_filters( 'single' );
			}
		}

		/**
		 * Get_product_info_on_archive_filters_array.
		 *
		 * @version 2.4.0
		 * @since   2.4.0
		 */
		private function get_product_info_on_archive_filters_array() {
			return array(
				'woocommerce_before_shop_loop_item'       => __( 'Before product', 'woocommerce-jetpack' ),
				'woocommerce_before_shop_loop_item_title' => __( 'Before product title', 'woocommerce-jetpack' ),
				'woocommerce_after_shop_loop_item'        => __( 'After product', 'woocommerce-jetpack' ),
				'woocommerce_after_shop_loop_item_title'  => __( 'After product title', 'woocommerce-jetpack' ),
			);
		}

		/**
		 * Get_product_info_on_single_filters_array.
		 *
		 * @version 2.4.0
		 * @since   2.4.0
		 */
		private function get_product_info_on_single_filters_array() {
			return array(
				'woocommerce_single_product_summary'       => __( 'Inside single product summary', 'woocommerce-jetpack' ),
				'woocommerce_before_single_product_summary' => __( 'Before single product summary', 'woocommerce-jetpack' ),
				'woocommerce_after_single_product_summary' => __( 'After single product summary', 'woocommerce-jetpack' ),
			);
		}

		/**
		 * Add_product_info_filters.
		 *
		 * @param string | array $single_or_archive defines the single_or_archive.
		 */
		public function add_product_info_filters( $single_or_archive ) {
			// Product Info.
			if ( ( 'yes' === wcj_get_option( 'wcj_product_info_on_' . $single_or_archive . '_enabled' ) ) &&
			( '' !== wcj_get_option( 'wcj_product_info_on_' . $single_or_archive ) ) &&
			( '' !== wcj_get_option( 'wcj_product_info_on_' . $single_or_archive . '_filter' ) ) &&
			( '' !== wcj_get_option( 'wcj_product_info_on_' . $single_or_archive . '_filter_priority' ) ) ) {
				add_action( wcj_get_option( 'wcj_product_info_on_' . $single_or_archive . '_filter' ), array( $this, 'product_info' ), wcj_get_option( 'wcj_product_info_on_' . $single_or_archive . '_filter_priority' ) );
			}
			// More product Info.
			if ( 'yes' === wcj_get_option( 'wcj_more_product_info_on_' . $single_or_archive . '_enabled' ) ) {
				add_action( wcj_get_option( 'wcj_more_product_info_on_' . $single_or_archive . '_filter' ), array( $this, 'more_product_info' ), wcj_get_option( 'wcj_more_product_info_on_' . $single_or_archive . '_filter_priority' ) );
			}
		}

		/**
		 * Product_info.
		 *
		 * @version 2.4.0
		 */
		public function product_info() {
			$the_action_name = current_filter();
			if ( array_key_exists( $the_action_name, $this->product_info_on_archive_filters_array ) ) {
				$the_product_info = wcj_get_option( 'wcj_product_info_on_archive' );
				$the_product_info = $this->search_and_replace_deprecated_shortcodes( $the_product_info );
				$this->apply_product_info_short_codes( $the_product_info, false );
			} elseif ( array_key_exists( $the_action_name, $this->product_info_on_single_filters_array ) ) {
				$the_product_info = wcj_get_option( 'wcj_product_info_on_single' );
				$the_product_info = $this->search_and_replace_deprecated_shortcodes( $the_product_info );
				$this->apply_product_info_short_codes( $the_product_info, false );
			}
		}

		/**
		 * More_product_info.
		 */
		public function more_product_info() {
			$the_action_name = current_filter();
			if ( array_key_exists( $the_action_name, $this->product_info_on_archive_filters_array ) ) {
				$this->add_more_product_info( 'archive' );
			} elseif ( array_key_exists( $the_action_name, $this->product_info_on_single_filters_array ) ) {
				$this->add_more_product_info( 'single' );
			}
		}

		/**
		 * Add_more_product_info.
		 *
		 * @version 2.4.0
		 * @param string | array $single_or_archive defines the single_or_archive.
		 */
		public function add_more_product_info( $single_or_archive ) {
			$wcj_more_product_info_on_ = apply_filters( 'booster_option', 4, wcj_get_option( 'wcj_more_product_info_on_' . $single_or_archive . '_fields_total', 4 ) );
			for ( $i = 1; $i <= $wcj_more_product_info_on_; $i++ ) {
				$field_id         = 'wcj_more_product_info_on_' . $single_or_archive . '_' . $i;
				$the_product_info = wcj_get_option( $field_id );
				$the_product_info = $this->search_and_replace_deprecated_shortcodes( $the_product_info );
				$this->apply_product_info_short_codes( $the_product_info, true );
			}
		}

		/**
		 * Apply_product_info_short_codes.
		 *
		 * @version 5.6.2
		 * @param string | array $the_product_info defines the the_product_info.
		 * @param string         $remove_on_empty defines the remove_on_empty.
		 */
		public function apply_product_info_short_codes( $the_product_info, $remove_on_empty ) {

			$product_ids_to_exclude = wcj_get_option( 'wcj_product_info_products_to_exclude', '' );
			if ( '' !== $product_ids_to_exclude ) {
				$product_ids_to_exclude = str_replace( ' ', '', $product_ids_to_exclude );
				$product_ids_to_exclude = explode( ',', $product_ids_to_exclude );
				$product_id             = get_the_ID();
				if ( ! empty( $product_ids_to_exclude ) && is_array( $product_ids_to_exclude ) && in_array( $product_id, $product_ids_to_exclude, true ) ) {
					return;
				}
			}

			if ( '' === $the_product_info ) {
				return;
			}

			echo do_shortcode( $the_product_info );
		}

		/**
		 * Admin_add_product_info_fields_with_header.
		 *
		 * @param array  $settings defines the settings.
		 * @param string $single_or_archive defines the single_or_archive.
		 * @param string $title defines the title.
		 * @param string $filters_array defines the filters_array.
		 */
		public function admin_add_product_info_fields_with_header( &$settings, $single_or_archive, $title, $filters_array ) {
			$settings = array_merge(
				$settings,
				array(
					array(
						'title'   => $title,
						'desc'    => __( 'Enable', 'woocommerce-jetpack' ),
						'id'      => 'wcj_more_product_info_on_' . $single_or_archive . '_enabled',
						'default' => 'no',
						'type'    => 'checkbox',
					),
					array(
						'title'    => '',
						'desc'     => __( 'Position', 'woocommerce-jetpack' ),
						'id'       => 'wcj_more_product_info_on_' . $single_or_archive . '_filter',
						'css'      => 'min-width:350px;',
						'class'    => 'chosen_select',
						'default'  => 'woocommerce_after_shop_loop_item_title',
						'type'     => 'select',
						'options'  => $filters_array,
						'desc_tip' => true,
					),
					array(
						'title'    => '',
						'desc_tip' => __( 'Priority (i.e. Order)', 'woocommerce-jetpack' ),
						'id'       => 'wcj_more_product_info_on_' . $single_or_archive . '_filter_priority',
						'default'  => 10,
						'type'     => 'number',
					),
					array(
						'title'             => '',
						'desc_tip'          => __( 'Number of product info fields. Click "Save changes" after you change this number.', 'woocommerce-jetpack' ),
						'id'                => 'wcj_more_product_info_on_' . $single_or_archive . '_fields_total',
						'default'           => 4,
						'type'              => 'number',
						'desc'              => apply_filters( 'booster_message', '', 'desc' ),
						'custom_attributes' => apply_filters( 'booster_message', '', 'readonly' ),
					),
				)
			);
			$this->admin_add_product_info_fields( $settings, $single_or_archive );
		}

		/**
		 * Admin_add_product_info_fields.
		 *
		 * @param array  $settings defines the settings.
		 * @param string $single_or_archive defines the single_or_archive.
		 *
		 * @version 5.6.2
		 */
		public function admin_add_product_info_fields( &$settings, $single_or_archive ) {
			$wcj_more_product_info_on = apply_filters( 'booster_option', 4, wcj_get_option( 'wcj_more_product_info_on_' . $single_or_archive . '_fields_total', 4 ) );
			for ( $i = 1; $i <= $wcj_more_product_info_on; $i++ ) {
				$field_id      = 'wcj_more_product_info_on_' . $single_or_archive . '_' . $i;
				$default_value = '';
				switch ( $i ) {
					case 1:
						$default_value = '<ul>';
						break;
					case 2:
						$default_value = '<li>' . __( '[wcj_product_you_save before="You save: <strong>" hide_if_zero="yes" after="</strong>"][wcj_product_you_save_percent hide_if_zero="yes" before=" (" after="%)"]', 'woocommerce-jetpack' ) . '</li>';
						break;
					case 3:
						$default_value = '<li>' . __( '[wcj_product_total_sales before="Total sales: "]', 'woocommerce-jetpack' ) . '</li>';
						break;
					case 4:
						$default_value = '</ul>';
						break;
				}
				$settings[] = array(
					'title'   => '',
					'id'      => $field_id,
					'default' => $default_value,
					'type'    => 'textarea',
					'css'     => 'width:50%;min-width:300px;',
				);
			}
		}
	}

endif;

return new WCJ_Product_Info();
