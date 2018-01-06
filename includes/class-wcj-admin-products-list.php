<?php
/**
 * Booster for WooCommerce - Module - Admin Products List
 *
 * @version 3.2.4
 * @since   3.2.4
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WCJ_Admin_Products_List' ) ) :

class WCJ_Admin_Products_List extends WCJ_Module {

	/**
	 * Constructor.
	 *
	 * @version 3.2.4
	 * @since   3.2.4
	 */
	function __construct() {

		$this->id         = 'admin_products_list';
		$this->short_desc = __( 'Admin Products List', 'woocommerce-jetpack' );
		$this->desc       = __( 'Customize WooCommerce admin products list.', 'woocommerce-jetpack' );
		$this->link_slug  = 'woocommerce-admin-products-list';
		parent::__construct();

		if ( $this->is_enabled() ) {
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
		$total_number = apply_filters( 'booster_option', 1, get_option( 'wcj_products_admin_list_custom_columns_total_number', 1 ) );
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
		$total_number = apply_filters( 'booster_option', 1, get_option( 'wcj_products_admin_list_custom_columns_total_number', 1 ) );
		for ( $i = 1; $i <= $total_number; $i++ ) {
			if ( 'yes' === get_option( 'wcj_products_admin_list_custom_columns_enabled_' . $i, 'no' ) ) {
				if ( 'wcj_products_custom_column_' . $i === $column ) {
					echo do_shortcode( get_option( 'wcj_products_admin_list_custom_columns_value_' . $i, '' ) );
				}
			}
		}
	}

}

endif;

return new WCJ_Admin_Products_List();
