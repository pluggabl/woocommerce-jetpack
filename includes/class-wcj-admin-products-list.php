<?php
/**
 * Booster for WooCommerce - Module - Admin Products List
 *
 * @version 5.2.0
 * @since   3.2.4
 * @author  Pluggabl LLC.
 * @package Booster_For_WooCommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCJ_Admin_Products_List' ) ) :

	/**
	 * WCJ_Admin_Products_List.
	 *
	 * @version 2.7.0
	 */
	class WCJ_Admin_Products_List extends WCJ_Module {

		/**
		 * Constructor.
		 *
		 * @version 5.2.0
		 * @since   3.2.4
		 */
		public function __construct() {

			$this->id         = 'admin_products_list';
			$this->short_desc = __( 'Admin Products List', 'woocommerce-jetpack' );
			$this->desc       = __( 'Customize admin products list (1 custom column allowed in free).', 'woocommerce-jetpack' );
			$this->desc_pro   = __( 'Customize admin products list.', 'woocommerce-jetpack' );
			$this->link_slug  = 'woocommerce-admin-products-list';
			parent::__construct();

			if ( $this->is_enabled() ) {
				// Admin list - custom columns.
				if ( 'yes' === wcj_get_option( 'wcj_products_admin_list_custom_columns_enabled', 'no' ) ) {
					add_filter( 'manage_edit-product_columns', array( $this, 'add_product_columns' ), PHP_INT_MAX );
					add_action( 'manage_product_posts_custom_column', array( $this, 'render_product_column' ), PHP_INT_MAX );
				}

				// Admin list - columns order.
				if ( 'yes' === wcj_get_option( 'wcj_products_admin_list_columns_order_enabled', 'no' ) ) {
					add_filter( 'manage_edit-product_columns', array( $this, 'rearange_product_columns' ), PHP_INT_MAX );
				}
			}
		}

		/**
		 * Rearange_product_columns.
		 *
		 * @version 2.9.0
		 * @since   2.9.0
		 * @param string $columns defines the columns.
		 */
		public function rearange_product_columns( $columns ) {
			$reordered_columns        = wcj_get_option( 'wcj_products_admin_list_columns_order', $this->get_products_default_columns_in_order() );
			$reordered_columns        = explode( PHP_EOL, $reordered_columns );
			$reordered_columns_result = array();
			if ( ! empty( $reordered_columns ) ) {
				foreach ( $reordered_columns as $column_id ) {
					$column_id = str_replace( "\n", '', $column_id );
					$column_id = str_replace( "\r", '', $column_id );
					if ( '' !== $column_id && isset( $columns[ $column_id ] ) ) {
						$reordered_columns_result[ $column_id ] = $columns[ $column_id ];
						unset( $columns[ $column_id ] );
					}
				}
			}
			return array_merge( $reordered_columns_result, $columns );
		}

		/**
		 * Get_products_default_columns_in_order.
		 *
		 * @version 2.9.0
		 * @since   2.9.0
		 */
		public function get_products_default_columns_in_order() {
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
		 * Add_product_columns.
		 *
		 * @version 2.9.0
		 * @since   2.9.0
		 * @param string $columns defines the columns.
		 */
		public function add_product_columns( $columns ) {
			$total_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_products_admin_list_custom_columns_total_number', 1 ) );
			for ( $i = 1; $i <= $total_number; $i++ ) {
				if ( 'yes' === wcj_get_option( 'wcj_products_admin_list_custom_columns_enabled_' . $i, 'no' ) ) {
					$columns[ 'wcj_products_custom_column_' . $i ] = wcj_get_option( 'wcj_products_admin_list_custom_columns_label_' . $i, '' );
				}
			}
			return $columns;
		}

		/**
		 * Render_product_column.
		 *
		 * @version 2.9.0
		 * @since   2.9.0
		 * @param string $column defines the column.
		 */
		public function render_product_column( $column ) {
			$total_number = apply_filters( 'booster_option', 1, wcj_get_option( 'wcj_products_admin_list_custom_columns_total_number', 1 ) );
			for ( $i = 1; $i <= $total_number; $i++ ) {
				if ( 'yes' === wcj_get_option( 'wcj_products_admin_list_custom_columns_enabled_' . $i, 'no' ) ) {
					if ( 'wcj_products_custom_column_' . $i === $column ) {
						echo do_shortcode( wcj_get_option( 'wcj_products_admin_list_custom_columns_value_' . $i, '' ) );
					}
				}
			}
		}

	}

endif;

return new WCJ_Admin_Products_List();
